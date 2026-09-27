<?php

declare(strict_types=1);

namespace Training\Controller;

use PDO;
use Training\App;
use Training\ConfigException;
use Training\Http\Request;
use Training\Http\Response;
use Training\Migration\MigrationException;
use Training\Migration\MigrationLockedException;
use Training\Migration\Migrator;

/**
 * POST /admin/migrate – vom Deploy-Workflow aufgerufen (D-17, D-20).
 * Authentifizierung über Header X-Migration-Secret (Wert = MIGRATION_SECRET aus .env).
 */
final class MigrateController
{
    public const HEADER = 'X-Migration-Secret';
    public const MIN_SECRET_LENGTH = 32;

    public function __construct(private readonly App $app)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $secret = $this->app->config()->require('MIGRATION_SECRET');
        } catch (ConfigException) {
            return Response::error(503, 'Server nicht konfiguriert.');
        }
        if (strlen($secret) < self::MIN_SECRET_LENGTH) {
            return Response::error(503, 'MIGRATION_SECRET in .env ist zu kurz (mindestens 32 Zeichen).');
        }

        $given = $request->header(self::HEADER);
        if ($given === null || $given === '') {
            return Response::error(401, 'Header ' . self::HEADER . ' fehlt.');
        }
        if (!hash_equals($secret, $given)) {
            return Response::error(403, 'Secret ungültig.');
        }

        $latest = Migrator::latestVersion($this->app->migrationsDir());
        if ($latest !== App::SCHEMA_VERSION) {
            return Response::error(500, sprintf(
                'Code inkonsistent: APP_SCHEMA_VERSION %d, höchste Migration %d.',
                App::SCHEMA_VERSION,
                $latest,
            ));
        }

        $pdo = $this->app->pdo();
        $migrator = new Migrator($pdo, $this->app->migrationsDir());
        $before = $migrator->currentVersion();
        if ($before > App::SCHEMA_VERSION) {
            return Response::error(409, sprintf(
                'Datenbank (Schema %d) ist neuer als der Code (Schema %d). Richtigen Stand deployen.',
                $before,
                App::SCHEMA_VERSION,
            ));
        }

        try {
            $applied = $migrator->migrate();
        } catch (MigrationLockedException $e) {
            return Response::error(409, $e->getMessage());
        } catch (MigrationException $e) {
            error_log('[training] ' . $e->getMessage());

            return Response::json(500, [
                'status' => 'error',
                'message' => $e->getMessage(),
                'applied' => $e->applied,
                'schema_version' => $migrator->currentVersion(),
            ]);
        }

        return Response::json(200, [
            'status' => 'ok',
            'schema_before' => $before,
            'schema_version' => $migrator->currentVersion(),
            'applied' => $applied,
            'db_server' => (string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
        ]);
    }
}
