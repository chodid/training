<?php

declare(strict_types=1);

namespace Training\Calendar;

use PDO;
use Training\Clock;
use Training\Data\AuditLog;
use Training\Data\WeekRepository;

/**
 * Überträgt die Einheiten als Sammeltermin je Tag in den CalDAV-Kalender (AP-11, D-50, D-60). Fehler brechen nichts ab: sie werden gemeldet,
 * im Audit-Log und im Zustand (var/calendar-sync.json) festgehalten; der Abgleich (Cron, Knopf) holt Verpasstes nach.
 * Ohne Konfiguration (Client null) tut die Klasse nichts.
 */
final class CalendarSync
{
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
     * Beim ersten Fehler bricht die Übertragung ab (eine Meldung je Aufruf); der Abgleich holt den Rest nach.
     * @param list<string> $dates Tage im Format Y-m-d (doppelte werden zusammengefasst)
     * @return ?string Fehlermeldung, null bei Erfolg oder ohne Konfiguration
     */
    public function pushDays(array $dates, string $actor = 'mcp'): ?string
    {
        if ($this->client === null || $dates === []) {
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
        $this->record(null);

        return null;
    }

    /**
     * Abgleich eines Zeitraums: je Tag mit Einheiten (ohne Ruhetage) einen Sammeltermin übertragen, eigene Termine ohne
     * Einheiten entfernen – auch die Einzeltermine je Einheit aus der Zeit vor D-60. Fremde Termine bleiben unberührt.
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
                $wanted[DayEvent::resource((string) $date)] = true;
                $this->writeDay((string) $date, $sessions);
                $result['uebertragen']++;
            }
            foreach ($this->client->resources($from, $to) as $name) {
                if (DayEvent::isOwn($name) && !isset($wanted[$name])) {
                    $this->client->delete($name);
                    $result['geloescht']++;
                }
            }
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
     * Sammeltermin eines Tages schreiben oder – ohne Einheiten – entfernen.
     * @param list<array<string, mixed>> $sessions Einheiten des Tages ohne Ruhetage
     */
    private function writeDay(string $date, array $sessions): void
    {
        if ($sessions === []) {
            $this->client?->delete(DayEvent::resource($date));
        } else {
            $this->client?->put(DayEvent::resource($date), DayEvent::ics($date, $sessions, $this->appUrl, $this->host, $this->clock->now(), $this->reminder));
        }
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
