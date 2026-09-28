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
 * GET /cron/intervals-sync?key=<CRON_SECRET>[&tage=14] – Spiegel Intervals.icu → MySQL (D-43), Standard 14 Tage, höchstens 400.
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
        $config = $this->app->config();
        if (!\Training\Intervals\IntervalsClient::isConfigured($config)) {
            return Response::error(503, 'INTERVALS_API_KEY und INTERVALS_ATHLETE_ID in .env fehlen.');
        }
        $days = max(1, min(400, (int) ($request->query('tage') ?? '14')));
        $user = $this->app->users()->first();
        $today = \Training\Dates::today($this->app->clock(), $user?->tz ?? 'Europe/Berlin');
        $state = $this->syncState();
        $state['last_attempt'] = $this->app->clock()->now();
        try {
            $result = (new \Training\Intervals\Mirror($this->app->intervalsClient(), $this->app->pdo(), $this->app->clock()))
                ->sync(\Training\Dates::addDays($today, -($days - 1)), $today);
            $state['last_success'] = $state['last_attempt'];
            $state['last_result'] = $result;
            unset($state['error']);
            $response = Response::json(200, ['status' => 'ok'] + $result);
        } catch (\Training\Intervals\IntervalsException $e) {
            $state['error'] = $e->getMessage();
            error_log('[training] Intervals-Spiegel: ' . $e->getMessage());
            $response = Response::json(502, ['status' => 'error', 'message' => $e->getMessage()]);
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
