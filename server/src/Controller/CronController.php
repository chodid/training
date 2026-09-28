<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\App;
use Training\ConfigException;
use Training\Http\Request;
use Training\Http\Response;

/**
 * Zeitgesteuerte Aufgaben per URL-Aufruf (Lima-City-Cronjob, V-10: keine PHP-CLI).
 * GET /cron/backup-mail?key=<BACKUP_CRON_SECRET>[&force=1] – Backup per E-Mail, wenn das Intervall abgelaufen ist.
 */
final class CronController
{
    public function __construct(private readonly App $app)
    {
    }

    public function backupMail(Request $request): Response
    {
        $config = $this->app->config();
        $secret = (string) $config->get('BACKUP_CRON_SECRET', '');
        if (strlen($secret) < 32) {
            return Response::error(503, 'BACKUP_CRON_SECRET in .env fehlt oder ist kürzer als 32 Zeichen.');
        }
        if (!hash_equals($secret, (string) $request->query('key'))) {
            return Response::error(403, 'Schlüssel ungültig.');
        }
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
