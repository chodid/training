<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\App;
use Training\ConfigException;
use Training\Http\Response;
use Training\Intervals\IntervalsClient;
use Training\Migration\Migrator;

/**
 * GET /health – Zustand ohne Secrets. 200 = alles in Ordnung, 503 = Handlungsbedarf.
 */
final class HealthController
{
    public const REQUIRED_EXTENSIONS = ['curl', 'json', 'mbstring', 'openssl', 'pdo_mysql', 'zlib'];

    public function __construct(private readonly App $app)
    {
    }

    public function handle(): Response
    {
        $ok = true;
        $checks = [];

        $extensions = [];
        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $extensions[$ext] = extension_loaded($ext);
            $ok = $ok && $extensions[$ext];
        }
        $checks['extensions'] = $extensions;

        try {
            $latest = Migrator::latestVersion($this->app->migrationsDir());
            $checks['migrations'] = $latest === App::SCHEMA_VERSION ? 'ok' : 'inkonsistent';
        } catch (\Throwable) {
            $checks['migrations'] = 'fehlerhaft';
        }
        $ok = $ok && $checks['migrations'] === 'ok';

        try {
            $this->app->config();
            $checks['config'] = 'ok';
        } catch (ConfigException $e) {
            $checks['config'] = ['status' => 'fehlt', 'keys' => $e->missingKeys];
            $ok = false;
        }

        $var = $this->app->varDir();
        if (!is_dir($var)) {
            @mkdir($var, 0750, true);
        }
        $checks['var'] = is_dir($var) && is_writable($var) ? 'ok' : 'nicht_beschreibbar';
        $ok = $ok && $checks['var'] === 'ok';

        $backups = $this->app->backupDir();
        if (!is_dir($backups)) {
            @mkdir($backups, 0750, true);
        }
        $checks['backups'] = is_dir($backups) && is_writable($backups) ? 'ok' : 'nicht_beschreibbar';
        $ok = $ok && $checks['backups'] === 'ok';

        // Intervals.icu (AP-02): nur Konfiguration, kein Netzwerkaufruf; Fehlen macht Health nicht rot.
        if ($checks['config'] === 'ok') {
            $checks['intervals'] = IntervalsClient::isConfigured($this->app->config()) ? 'konfiguriert' : 'nicht_konfiguriert';
            $checks['kalender'] = !\Training\Calendar\CalDavClient::isConfigured($this->app->config()) ? 'nicht_konfiguriert'
                : (str_starts_with(strtolower((string) $this->app->config()->get('CALDAV_URL')), 'https://') ? 'konfiguriert' : 'ungueltig_kein_https');
        }

        $schema = ['code' => App::SCHEMA_VERSION, 'db' => null, 'status' => 'unbekannt'];
        if ($checks['config'] === 'ok') {
            try {
                $db = (new Migrator($this->app->pdo(), $this->app->migrationsDir()))->currentVersion();
                $checks['database'] = 'ok';
                $schema['db'] = $db;
                $schema['status'] = match (true) {
                    $db === App::SCHEMA_VERSION => 'aktuell',
                    $db < App::SCHEMA_VERSION => 'migration_erforderlich',
                    default => 'datenbank_neuer_als_code',
                };
            } catch (\Throwable $e) {
                error_log('[training] Health: Datenbank nicht erreichbar: ' . $e->getMessage());
                $checks['database'] = 'nicht_erreichbar';
            }
        } else {
            $checks['database'] = 'nicht_geprueft';
        }
        $checks['schema'] = $schema;
        $ok = $ok && $schema['status'] === 'aktuell';

        return Response::json($ok ? 200 : 503, [
            'status' => $ok ? 'ok' : 'error',
            'app_version' => App::VERSION,
            'php_version' => PHP_VERSION,
            'checks' => $checks,
        ]);
    }
}
