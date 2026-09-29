<?php

declare(strict_types=1);

namespace Training\Calendar;

use PDO;
use Training\Clock;
use Training\Data\AuditLog;
use Training\Data\SettingsRepository;
use Training\Data\WeekRepository;

/**
 * Überträgt die Einheiten als Sammeltermin je Tag in den CalDAV-Kalender (AP-11, D-50, D-60). Fehler brechen nichts ab: sie werden gemeldet,
 * im Audit-Log und im Zustand (var/calendar-sync.json) festgehalten; der Abgleich (Cron, Knopf) holt Verpasstes nach.
 * Ohne Konfiguration (Client null) tut die Klasse nichts.
 */
final class CalendarSync
{
    /** Schlüssel-Präfix in app_setting für die Fassung eines Tagestermins */
    private const GENERATION_KEY = 'kalender_tag_';
    /** Schlüssel-Präfix in app_setting für die Fassung eines Blocktermins (AP-15, E-15) */
    private const BLOCK_GENERATION_KEY = 'kalender_block_';
    /** Suchfenster für eigene Blocktermine beim Abgleich (Tage vor bzw. nach heute) */
    private const BLOCK_WINDOW_PAST = 400;
    private const BLOCK_WINDOW_FUTURE = 800;

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly ?CalDavClient $client,
        private readonly string $appUrl,
        private readonly string $host,
        private readonly string $stateFile,
        /** Erinnerung 'HH:MM' am Trainingstag, null = aus (D-52) */
        private readonly ?string $reminder = null,
        /** Zeitzone des Athleten für die Uhrzeit des Blocktermins (AP-15, E-15) */
        private readonly string $tz = 'Europe/Berlin',
    ) {
    }

    public function enabled(): bool
    {
        return $this->client !== null;
    }

    /**
     * Sammeltermine der genannten Tage neu schreiben (D-60): Tage ohne Einheiten bzw. nur mit Ruhetag werden entfernt.
     * Dazu die Einzeltermine der betroffenen Einheiten aus der Zeit vor D-60 entfernen (auch außerhalb des
     * Abgleichzeitraums, z. B. späte Rückmeldung). Beim ersten Fehler bricht die Übertragung ab (eine Meldung je Aufruf);
     * der Abgleich holt den Rest nach.
     * @param list<string> $dates Tage im Format Y-m-d (doppelte werden zusammengefasst)
     * @param list<int> $sessionIds geänderte, verschobene, ersetzte oder zurückgemeldete Einheiten
     * @return ?string Fehlermeldung, null bei Erfolg oder ohne Konfiguration
     */
    public function pushDays(array $dates, string $actor = 'mcp', array $sessionIds = []): ?string
    {
        if ($this->client === null || ($dates === [] && $sessionIds === [])) {
            return null;
        }
        $dates = array_values(array_unique($dates));
        sort($dates);
        $weeks = new WeekRepository($this->pdo, $this->clock);
        foreach ($dates as $date) {
            try {
                $this->writeDay($date, DayEvent::relevant($weeks->sessions($date, $date)));
            } catch (CalendarException $e) {
                return $this->fail($e, $actor, $date);
            }
        }
        try {
            foreach (array_values(array_unique($sessionIds)) as $id) {
                $this->client->delete(DayEvent::legacyResource((int) $id));
            }
        } catch (CalendarException $e) {
            return $this->fail($e, $actor, null);
        }
        $this->record(null);

        return null;
    }

    /**
     * Abgleich eines Zeitraums: je Tag mit Einheiten (ohne Ruhetage) einen Sammeltermin übertragen, eigene Termine ohne
     * Einheiten entfernen – auch ältere Fassungen eines Tages und die Einzeltermine je Einheit aus der Zeit vor D-60.
     * Fremde Termine bleiben unberührt. Gezählt werden Termine (Tage); die Blocktermine (AP-15, E-16) stehen gesondert
     * unter blocktermine.
     * @return array{uebertragen: int, geloescht: int, fehler: list<string>, blocktermine?: array{uebertragen: int, geloescht: int}}
     */
    public function syncRange(string $from, string $to, string $actor = 'cron'): array
    {
        $result = ['uebertragen' => 0, 'geloescht' => 0, 'fehler' => []];
        if ($this->client === null) {
            return $result;
        }
        $days = [];
        foreach (DayEvent::relevant((new WeekRepository($this->pdo, $this->clock))->sessions($from, $to)) as $s) {
            $days[(string) $s['date']][] = $s;
        }
        $wanted = [];
        try {
            foreach ($days as $date => $sessions) {
                $wanted[DayEvent::resource((string) $date, $this->generation((string) $date))] = true;
                $this->writeDay((string) $date, $sessions);
                $result['uebertragen']++;
            }
            foreach ($this->client->resources($from, $to) as $name) {
                if (!DayEvent::isOwn($name) || isset($wanted[$name])) {
                    continue;
                }
                $date = DayEvent::dateFromResource($name);
                if ($date !== null && ($date < $from || $date > $to)) {
                    continue; // Nachbartag am Rand (Server vergleichen ganztägige Termine in ihrer Zeitzone)
                }
                if ($this->client->delete($name) && $date !== null && $name === DayEvent::resource($date, $this->generation($date))) {
                    $this->nextGeneration($date);
                }
                $result['geloescht']++;
            }
            $this->settings()->forgetBefore(self::GENERATION_KEY, (new \DateTimeImmutable($from))->modify('-60 days')->format('Y-m-d'));
            // Blocktermine (AP-15, E-16): alle Blöcke geplant/aktiv unabhängig vom Zeitraum, verwaiste entfernen
            $result['blocktermine'] = $this->syncBlockEvents($actor);
            $this->record(null, $result);
        } catch (CalendarException $e) {
            $result['fehler'][] = $this->fail($e, $actor, null);
        }

        return $result;
    }

    /**
     * Blocktermine nach einer Änderung übertragen (upsert_block, write_block_review, Einstellungen E-22): alle Blöcke
     * mit Termin neu schreiben, dazu die genannten Blöcke (Termin ggf. löschen). Fehler wie bei pushDays.
     * @param list<int> $blockIds zusätzlich zu prüfende Blöcke (z. B. gerade abgeschlossene)
     * @return ?string Fehlermeldung, null bei Erfolg oder ohne Konfiguration
     */
    public function pushBlocks(array $blockIds, string $actor = 'mcp'): ?string
    {
        if ($this->client === null) {
            return null;
        }
        try {
            $wanted = $this->wantedBlockEvents();
            $audit = new AuditLog($this->pdo, $this->clock);
            foreach (array_values(array_unique([...array_keys($wanted), ...$blockIds])) as $id) {
                $done = $this->writeBlock((int) $id, $wanted[$id] ?? null);
                if ($done !== null) {
                    $audit->write($actor, 'calendar_block_event', 'kalender_block', (int) $id, null, 'Blocktermin ' . $id . ' ' . $done);
                }
            }
        } catch (CalendarException $e) {
            return $this->fail($e, $actor, null, 'kalender_block');
        }
        $this->record(null);

        return null;
    }

    /**
     * Abgleich der Blocktermine: gewünschte Termine schreiben, eigene Termine ohne Block bzw. ohne Bedarf und ältere
     * Fassungen entfernen. Wirft CalendarException (Aufrufer meldet).
     * @return array{uebertragen: int, geloescht: int}
     */
    private function syncBlockEvents(string $actor): array
    {
        $result = ['uebertragen' => 0, 'geloescht' => 0];
        if ($this->client === null) {
            return $result;
        }
        $wanted = $this->wantedBlockEvents();
        $names = [];
        foreach ($wanted as $id => $w) {
            $this->writeBlock($id, $w);
            $names[BlockEvent::resource($id, $this->blockGeneration($id))] = true;
            $result['uebertragen']++;
        }
        $today = (new \DateTimeImmutable('@' . $this->clock->now()))->setTimezone(new \DateTimeZone($this->tz));
        foreach ($this->client->resources($today->modify('-' . self::BLOCK_WINDOW_PAST . ' days')->format('Y-m-d'), $today->modify('+' . self::BLOCK_WINDOW_FUTURE . ' days')->format('Y-m-d')) as $name) {
            $id = BlockEvent::blockIdFromResource($name);
            if ($id === null || isset($names[$name])) {
                continue;
            }
            if ($this->client->delete($name) && $name === BlockEvent::resource($id, $this->blockGeneration($id))) {
                $this->nextBlockGeneration($id);
            }
            $result['geloescht']++;
            (new AuditLog($this->pdo, $this->clock))->write($actor, 'calendar_block_event', 'kalender_block', $id, null, 'Blocktermin ' . $id . ' entfernt (Abgleich)');
        }

        return $result;
    }

    /**
     * Blöcke mit Termin (E-15): Status geplant/aktiv sowie der zuletzt beendete abgeschlossene Block, solange die Bilanz
     * des Blocks oder die Zielklärung eines Folgeblocks (geplant/aktiv, späterer Beginn) nicht bestätigt ist.
     * @return array<int, array{block: array<string, mixed>, missing: list<string>, modified: int}>
     */
    private function wantedBlockEvents(): array
    {
        try {
            $blocks = $this->pdo->query('SELECT * FROM training_block ORDER BY start_date, id')->fetchAll();
            $confirmed = (new \Training\Data\ReviewRepository($this->pdo, $this->clock))->current(null, null, true);
            $reviewTimes = $this->pdo->query('SELECT block_id, MAX(created_at) FROM block_review GROUP BY block_id')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (\PDOException) {
            return []; // Schema älter als 24
        }
        $has = [];
        foreach ($confirmed as $r) {
            $has[$r['block_id']][$r['kind']] = true;
        }
        $closed = null;
        foreach ($blocks as $b) {
            if ($b['status'] === 'abgeschlossen' && ($closed === null || [(string) $b['end_date'], (int) $b['id']] > [(string) $closed['end_date'], (int) $closed['id']])) {
                $closed = $b;
            }
        }
        $settingsTime = $this->pdo->query("SELECT MAX(updated_at) FROM app_setting WHERE setting_key IN ('kalender_block_beginn', 'kalender_block_dauer_min', 'kalender_block_erinnerung_h')")->fetchColumn();
        $out = [];
        foreach ($blocks as $b) {
            $id = (int) $b['id'];
            if (!in_array($b['status'], ['geplant', 'aktiv'], true) && ($closed === null || $id !== (int) $closed['id'])) {
                continue;
            }
            $missing = [];
            if (!isset($has[$id]['bilanz'])) {
                $missing[] = 'bilanz';
            }
            $next = array_filter($blocks, static fn (array $x): bool => (int) $x['id'] !== $id && in_array($x['status'], ['geplant', 'aktiv'], true)
                && (string) $x['start_date'] > (string) $b['start_date'] && isset($has[(int) $x['id']]['zielklaerung']));
            if ($next === []) {
                $missing[] = 'zielklaerung';
            }
            if ($missing === []) {
                continue;
            }
            $times = [(string) $b['updated_at'], $reviewTimes[$id] ?? null, $settingsTime !== false ? (string) $settingsTime : null];
            foreach ($blocks as $x) {
                if ((string) $x['start_date'] > (string) $b['start_date']) {
                    $times[] = $reviewTimes[(int) $x['id']] ?? null;
                    $times[] = (string) $x['updated_at'];
                }
            }
            $out[$id] = ['block' => $b, 'missing' => $missing, 'modified' => BlockEvent::modified($times, $this->clock->now())];
        }

        return $out;
    }

    /**
     * Termin eines Blocks schreiben oder – ohne Bedarf – entfernen (neue Fassung nach dem Löschen, Papierkorb).
     * @param ?array{block: array<string, mixed>, missing: list<string>, modified: int} $wanted
     * @return ?string „übertragen“, „gelöscht“ oder null (nichts zu tun)
     */
    private function writeBlock(int $id, ?array $wanted): ?string
    {
        if ($this->client === null) {
            return null;
        }
        $generation = $this->blockGeneration($id);
        if ($wanted === null) {
            if ($this->client->delete(BlockEvent::resource($id, $generation))) {
                $this->nextBlockGeneration($id);

                return 'gelöscht';
            }

            return null;
        }
        $this->client->put(BlockEvent::resource($id, $generation), BlockEvent::ics($wanted['block'], $wanted['missing'], $this->appUrl, $this->host, $this->tz,
            $this->settings()->kalenderBlock(), $this->clock->now(), $wanted['modified'], $generation));

        return 'übertragen (' . BlockEvent::summary((string) $wanted['block']['name'], $wanted['missing']) . ')';
    }

    private function blockGeneration(int $id): int
    {
        return max(0, (int) $this->settings()->get(self::BLOCK_GENERATION_KEY . $id, '0'));
    }

    private function nextBlockGeneration(int $id): void
    {
        $this->settings()->set(self::BLOCK_GENERATION_KEY . $id, (string) ($this->blockGeneration($id) + 1));
    }

    /** @return array<string, mixed> Zustand: last_success, last_error, last_error_at, last_result */
    public function state(): array
    {
        $data = is_file($this->stateFile) ? json_decode((string) file_get_contents($this->stateFile), true) : null;

        return is_array($data) ? $data : [];
    }

    /**
     * Sammeltermin eines Tages schreiben oder – ohne Einheiten – entfernen. Nach dem Entfernen zählt die Fassung des
     * Tages weiter, damit ein späterer Termin weder Adresse noch UID des gelöschten wiederverwendet (Nextcloud-Papierkorb).
     * @param list<array<string, mixed>> $sessions Einheiten des Tages ohne Ruhetage
     */
    private function writeDay(string $date, array $sessions): void
    {
        if ($this->client === null) {
            return;
        }
        $generation = $this->generation($date);
        if ($sessions === []) {
            if ($this->client->delete(DayEvent::resource($date, $generation))) {
                $this->nextGeneration($date);
            }
        } else {
            $this->client->put(DayEvent::resource($date, $generation),
                DayEvent::ics($date, $sessions, $this->appUrl, $this->host, $this->clock->now(), $this->reminder, $generation));
        }
    }

    /** Fassung des Tagestermins (Zahl der bisherigen Löschungen; app_setting kalender_tag_<Datum>). */
    private function generation(string $date): int
    {
        return max(0, (int) $this->settings()->get(self::GENERATION_KEY . $date, '0'));
    }

    private function nextGeneration(string $date): void
    {
        $this->settings()->set(self::GENERATION_KEY . $date, (string) ($this->generation($date) + 1));
    }

    private function settings(): SettingsRepository
    {
        return new SettingsRepository($this->pdo, $this->clock);
    }

    private function fail(CalendarException $e, string $actor, ?string $date, string $entity = 'kalender_tag'): string
    {
        $message = $e->getMessage();
        error_log('[training] Kalender: ' . $message);
        (new AuditLog($this->pdo, $this->clock))->write($actor, 'calendar_error', $entity, $date, null, mb_substr($message, 0, 400));
        $this->record($message);

        return $message;
    }

    /** @param ?array<string, mixed> $result */
    private function record(?string $error, ?array $result = null): void
    {
        $state = $this->state();
        if ($error === null) {
            $state['last_success'] = $this->clock->now();
            unset($state['last_error'], $state['last_error_at']);
            if ($result !== null) {
                $state['last_result'] = $result;
            }
        } else {
            $state['last_error'] = $error;
            $state['last_error_at'] = $this->clock->now();
        }
        $dir = dirname($this->stateFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        @file_put_contents($this->stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
