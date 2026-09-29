<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\App;
use Training\ConfigException;
use Training\Http\Request;
use Training\Http\Response;

/**
 * Zeitgesteuerte Aufgaben per URL-Aufruf (Lima-City-Cronjob, V-10: keine PHP-CLI).
 * GET /cron/backup-mail?key=<CRON_SECRET>[&force=1] – Backup per E-Mail, wenn das Intervall abgelaufen ist.
 * GET /cron/intervals-sync?key=<CRON_SECRET>[&tage=14] – Spiegel Intervals.icu → MySQL (D-43), Standard 14 Tage, höchstens 400;
 *   gleicht außerdem den CalDAV-Kalender ab (AP-11, D-50), wenn CALDAV_* gesetzt ist, und prüft einmal je 7 Tage die
 *   Links des Übungskatalogs (AP-16 Teil D; Ergebnis unter linkpruefung).
 */
final class CronController
{
    public function __construct(private readonly App $app)
    {
    }

    public function intervalsSync(Request $request): Response
    {
        if ($denied = $this->checkKey($request)) {
            return $denied;
        }
        $links = $this->linkCheck();
        $response = $this->sync($request);
        if ($links === null || ($links['links'] ?? null) === 0) {
            return $response; // nicht fällig oder nichts zu prüfen: Antwort wie bisher
        }
        $data = json_decode($response->body, true);

        return Response::json($response->status, (is_array($data) ? $data : []) + ['linkpruefung' => $links]);
    }

    /**
     * Wöchentliche Linkprüfung (AP-16 Teil D): Fehler brechen den Cron nicht ab (z. B. vor der Migration ohne Tabelle).
     * @return array<string, mixed>|null Zusammenfassung, null wenn nicht fällig
     */
    private function linkCheck(): ?array
    {
        if ($this->app->writeLocked()) {
            return null;
        }
        try {
            return (new \Training\Exercise\LinkCheckRun($this->app->pdo(), $this->app->clock(), $this->app->linkChecker()))->runIfDue();
        } catch (\Throwable $e) {
            error_log('[training] Linkprüfung: ' . $e::class . ': ' . $e->getMessage());

            return ['fehler' => $e->getMessage()];
        }
    }

    private function sync(Request $request): Response
    {
        $config = $this->app->config();
        $calendar = $this->app->calendar();
        $intervals = \Training\Intervals\IntervalsClient::isConfigured($config);
        if (!$intervals && !$calendar->enabled()) {
            return Response::error(503, 'INTERVALS_API_KEY und INTERVALS_ATHLETE_ID in .env fehlen.');
        }
        $user = $this->app->users()->first();
        $today = \Training\Dates::today($this->app->clock(), $user?->tz ?? 'Europe/Berlin');
        // Kalender (AP-11, D-50): 7 Tage zurück bis 8 Wochen voraus abgleichen.
        $calendarResult = $calendar->enabled() ? $calendar->syncRange(\Training\Dates::addDays($today, -7), \Training\Dates::addDays($today, 56)) : null;
        if (!$intervals) {
            return Response::json($calendarResult['fehler'] === [] ? 200 : 502, ['status' => $calendarResult['fehler'] === [] ? 'ok' : 'error', 'kalender' => $calendarResult]);
        }
        $days = max(1, min(400, (int) ($request->query('tage') ?? '14')));
        $state = $this->syncState();
        $state['last_attempt'] = $this->app->clock()->now();
        try {
            $result = (new \Training\Intervals\Mirror($this->app->intervalsClient(), $this->app->pdo(), $this->app->clock()))
                ->sync(\Training\Dates::addDays($today, -($days - 1)), $today);
            $state['last_success'] = $state['last_attempt'];
            $state['last_result'] = $result;
            unset($state['error']);
            $response = Response::json($calendarResult !== null && $calendarResult['fehler'] !== [] ? 502 : 200,
                ['status' => $calendarResult !== null && $calendarResult['fehler'] !== [] ? 'error' : 'ok'] + $result + ($calendarResult !== null ? ['kalender' => $calendarResult] : []));
        } catch (\Training\Intervals\IntervalsException $e) {
            $state['error'] = $e->getMessage();
            error_log('[training] Intervals-Spiegel: ' . $e->getMessage());
            $response = Response::json(502, ['status' => 'error', 'message' => $e->getMessage()] + ($calendarResult !== null ? ['kalender' => $calendarResult] : []));
        }
        $this->writeSyncState($state);

        return $response;
    }

    /** @return array<string, mixed> */
    public function syncState(): array
    {
        $file = $this->app->varDir() . '/intervals-sync.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $state */
    private function writeSyncState(array $state): void
    {
        $dir = $this->app->varDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        @file_put_contents($dir . '/intervals-sync.json', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function checkKey(Request $request): ?Response
    {
        $secret = (string) $this->app->config()->get('CRON_SECRET', '');
        if (strlen($secret) < 32) {
            return Response::error(503, 'CRON_SECRET in .env fehlt oder ist kürzer als 32 Zeichen.');
        }
        if (!hash_equals($secret, (string) $request->query('key'))) {
            return Response::error(403, 'Schlüssel ungültig.');
        }

        return null;
    }

    public function backupMail(Request $request): Response
    {
        if ($denied = $this->checkKey($request)) {
            return $denied;
        }
        $config = $this->app->config();
        try {
            $to = $config->require('BACKUP_MAIL_TO');
        } catch (ConfigException) {
            return Response::error(503, 'BACKUP_MAIL_TO in .env fehlt.');
        }
        $interval = max(1, (int) $config->get('BACKUP_MAIL_INTERVAL_DAYS', '7'));
        $result = $this->app->mailBackup()->run($to, $interval, $request->query('force') === '1');

        return Response::json($result['status'] === 'error' ? 500 : 200, $result);
    }
}
