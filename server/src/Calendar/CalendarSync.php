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

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly ?CalDavClient $client,
        private readonly string $appUrl,
        private readonly string $host,
        private readonly string $stateFile,
        /** Erinnerung 'HH:MM' am Trainingstag, null = aus (D-52) */
        private readonly ?string $reminder = null,
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
     * Fremde Termine bleiben unberührt. Gezählt werden Termine (Tage).
     * @return array{uebertragen: int, geloescht: int, fehler: list<string>}
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
            $this->record(null, $result);
        } catch (CalendarException $e) {
            $result['fehler'][] = $this->fail($e, $actor, null);
        }

        return $result;
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

    private function fail(CalendarException $e, string $actor, ?string $date): string
    {
        $message = $e->getMessage();
        error_log('[training] Kalender: ' . $message);
        (new AuditLog($this->pdo, $this->clock))->write($actor, 'calendar_error', 'kalender_tag', $date, null, mb_substr($message, 0, 400));
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
