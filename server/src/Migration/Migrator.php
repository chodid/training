<?php

declare(strict_types=1);

namespace Training\Migration;

use PDO;

/**
 * Führt nummerierte Migrationen aus server/migrations/ aus (D-20).
 *
 * Dateiname: NNNN_beschreibung.sql oder NNNN_beschreibung.php, fortlaufend ab 0001.
 * PHP-Migrationen geben eine Funktion function (PDO $pdo): void zurück.
 * MySQL beendet Transaktionen bei DDL implizit; deshalb wird schema_version nach jeder einzelnen
 * Migration fortgeschrieben. Bricht eine Migration ab, bleibt der Stand auf der letzten erfolgreichen.
 * Pre-Migration-Dump und Schreibsperre folgen in AP-10.
 */
final class Migrator
{
    private const LOCK_NAME = 'training_migrate';
    private const FILE_PATTERN = '/^(\d{4})_([a-z0-9_]+)\.(sql|php)$/';

    public function __construct(private readonly PDO $pdo, private readonly string $directory)
    {
    }

    /**
     * Alle Migrationsdateien, geprüft auf lückenlose Nummerierung ab 1.
     *
     * @return list<array{version: int, name: string, path: string, type: string}>
     */
    public static function available(string $directory): array
    {
        $migrations = [];
        foreach (scandir($directory) ?: [] as $file) {
            if ($file[0] === '.') {
                continue;
            }
            if (!preg_match(self::FILE_PATTERN, $file, $m)) {
                throw new MigrationException('Ungültiger Dateiname im Migrationsordner: ' . $file);
            }
            $version = (int) $m[1];
            if (isset($migrations[$version])) {
                throw new MigrationException(sprintf('Migrationsnummer %04d ist doppelt vergeben.', $version));
            }
            $migrations[$version] = ['version' => $version, 'name' => $m[2], 'path' => $directory . '/' . $file, 'type' => $m[3]];
        }
        ksort($migrations);

        $expected = 1;
        foreach (array_keys($migrations) as $version) {
            if ($version !== $expected) {
                throw new MigrationException(sprintf('Migrationen nicht lückenlos: %04d erwartet, %04d gefunden.', $expected, $version));
            }
            $expected++;
        }

        return array_values($migrations);
    }

    public static function latestVersion(string $directory): int
    {
        $migrations = self::available($directory);

        return $migrations === [] ? 0 : $migrations[count($migrations) - 1]['version'];
    }

    public function currentVersion(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'schema_version'"
        );
        if ((int) $stmt->fetchColumn() === 0) {
            return 0;
        }

        return (int) $this->pdo->query('SELECT COALESCE(MAX(version), 0) FROM schema_version')->fetchColumn();
    }

    /** @return list<array{version: int, name: string, path: string, type: string}> */
    public function pending(): array
    {
        $current = $this->currentVersion();

        return array_values(array_filter(
            self::available($this->directory),
            static fn (array $m): bool => $m['version'] > $current,
        ));
    }

    /**
     * @return list<array{version: int, name: string}> ausgeführte Migrationen
     * @throws MigrationLockedException wenn bereits eine Migration läuft
     * @throws MigrationException wenn eine Migration fehlschlägt
     */
    public function migrate(): array
    {
        $locked = (int) $this->pdo->query("SELECT GET_LOCK('" . self::LOCK_NAME . "', 0)")->fetchColumn();
        if ($locked !== 1) {
            throw new MigrationLockedException('Es läuft bereits eine Migration.');
        }

        $applied = [];
        try {
            foreach ($this->pending() as $migration) {
                try {
                    $this->run($migration);
                } catch (\Throwable $e) {
                    throw new MigrationException(
                        sprintf('Migration %04d_%s fehlgeschlagen: %s', $migration['version'], $migration['name'], $e->getMessage()),
                        $applied,
                        $e,
                    );
                }
                $stmt = $this->pdo->prepare('INSERT INTO schema_version (version, name, applied_at) VALUES (?, ?, UTC_TIMESTAMP())');
                $stmt->execute([$migration['version'], $migration['name']]);
                $applied[] = ['version' => $migration['version'], 'name' => $migration['name']];
            }
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('" . self::LOCK_NAME . "')");
        }

        return $applied;
    }

    /** @param array{version: int, name: string, path: string, type: string} $migration */
    private function run(array $migration): void
    {
        if ($migration['type'] === 'php') {
            $callback = require $migration['path'];
            if (!is_callable($callback)) {
                throw new MigrationException('PHP-Migration gibt keine Funktion zurück.');
            }
            $callback($this->pdo);

            return;
        }

        foreach (SqlSplitter::split((string) file_get_contents($migration['path'])) as $statement) {
            $this->pdo->exec($statement);
        }
    }
}
