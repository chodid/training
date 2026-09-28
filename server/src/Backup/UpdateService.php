<?php

declare(strict_types=1);

namespace Training\Backup;

use PDO;
use Training\App;
use Training\Clock;
use Training\Migration\MigrationException;
use Training\Migration\Migrator;

/**
 * Migration mit vorherigem Pre-Migration-Dump (D-20, AP-10): nur wenn Migrationen ausstehen; schlägt der Dump fehl,
 * wird nicht migriert. Aufgerufen vom Deploy-Workflow (/admin/migrate) und über die Schaltfläche in den Einstellungen.
 */
final class UpdateService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly string $migrationsDir,
        private readonly string $backupDir,
        private readonly ?string $backupPassword,
    ) {
    }

    /**
     * @return array{before: int, after: int, applied: list<array{version: int, name: string}>, backup: ?string}
     * @throws BackupException wenn der Dump fehlschlägt (keine Migration ausgeführt)
     * @throws MigrationException
     */
    public function migrate(): array
    {
        $migrator = new Migrator($this->pdo, $this->migrationsDir);
        $before = $migrator->currentVersion();
        if ($migrator->pending() === []) {
            return ['before' => $before, 'after' => $before, 'applied' => [], 'backup' => null];
        }

        $backup = null;
        if ($before > 0) {
            // Leere Datenbank (Erstinstallation) braucht keinen Dump.
            $service = new BackupService($this->pdo, $this->clock, (string) $this->backupPassword, $this->migrationsDir);
            $backup = basename($service->store($this->backupDir, 'vor-migration'));
        }
        $applied = $migrator->migrate();

        return ['before' => $before, 'after' => $migrator->currentVersion(), 'applied' => $applied, 'backup' => $backup];
    }

    /** Schreibsperre (D-20): Code- und Datenbankstand weichen ab. */
    public static function writeLocked(PDO $pdo, string $migrationsDir): bool
    {
        try {
            return (new Migrator($pdo, $migrationsDir))->currentVersion() !== App::SCHEMA_VERSION;
        } catch (\Throwable) {
            return true;
        }
    }
}
