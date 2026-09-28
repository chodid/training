<?php

declare(strict_types=1);

namespace Training\Calendar;

use PDO;
use Training\Clock;
use Training\Data\AuditLog;
use Training\Data\WeekRepository;

/**
 * Überträgt Einheiten in den CalDAV-Kalender (AP-11, D-50). Fehler brechen nichts ab: sie werden gemeldet,
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
    ) {
    }

    public function enabled(): bool
    {
        return $this->client !== null;
    }

    /**
     * Termin einer Einheit anlegen/aktualisieren; Ruhetage und gelöschte Einheiten werden entfernt.
     * @return ?string Fehlermeldung, null bei Erfolg oder ohne Konfiguration
     */
    public function push(int $sessionId, string $actor = 'mcp'): ?string
    {
        if ($this->client === null) {
            return null;
        }
        $s = (new WeekRepository($this->pdo, $this->clock))->session($sessionId);
        try {
            if ($s === null || $s['type'] === 'ruhe') {
                $this->client->delete(SessionEvent::resource($sessionId));
            } else {
                $this->client->put(SessionEvent::resource($sessionId), SessionEvent::ics($s, $this->appUrl, $this->host, $this->clock->now()));
            }
            $this->record(null);

            return null;
        } catch (CalendarException $e) {
            return $this->fail($e, $actor, $sessionId);
        }
    }

    /** @return ?string Fehlermeldung */
    public function remove(int $sessionId, string $actor = 'mcp'): ?string
    {
        if ($this->client === null) {
            return null;
        }
        try {
            $this->client->delete(SessionEvent::resource($sessionId));
            $this->record(null);

            return null;
        } catch (CalendarException $e) {
            return $this->fail($e, $actor, $sessionId);
        }
    }

    /**
     * Abgleich eines Zeitraums: alle Einheiten (ohne Ruhetage) übertragen, eigene Termine ohne Einheit entfernen.
     * Fremde Termine im Kalender bleiben unberührt.
     * @return array{uebertragen: int, geloescht: int, fehler: list<string>}
     */
    public function syncRange(string $from, string $to, string $actor = 'cron'): array
    {
        $result = ['uebertragen' => 0, 'geloescht' => 0, 'fehler' => []];
        if ($this->client === null) {
            return $result;
        }
        $sessions = (new WeekRepository($this->pdo, $this->clock))->sessions($from, $to);
        $wanted = [];
        try {
            foreach ($sessions as $s) {
                if ($s['type'] === 'ruhe') {
                    continue;
                }
                $wanted[(int) $s['id']] = true;
                $this->client->put(SessionEvent::resource((int) $s['id']), SessionEvent::ics($s, $this->appUrl, $this->host, $this->clock->now()));
                $result['uebertragen']++;
            }
            foreach ($this->client->resources($from, $to) as $name) {
                $id = SessionEvent::idFromResource($name);
                if ($id !== null && !isset($wanted[$id])) {
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

    private function fail(CalendarException $e, string $actor, ?int $sessionId): string
    {
        $message = $e->getMessage();
        error_log('[training] Kalender: ' . $message);
        (new AuditLog($this->pdo, $this->clock))->write($actor, 'calendar_error', 'session', $sessionId, null, mb_substr($message, 0, 400));
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
