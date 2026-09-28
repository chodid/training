<?php

declare(strict_types=1);

namespace Training\Backup;

use PDO;
use Training\Clock;
use Training\Migration\Migrator;

/**
 * Erzeugt verschlüsselte Backups (D-18): SQL-Dump → gzip → OpenSSL-kompatible Verschlüsselung.
 * Dateiname: training-backup-<JJJJMMTT-HHMMSS>-s<Schemastand>[-<Anlass>].sql.gz.enc
 */
final class BackupService
{
    public const KEEP_PRE_MIGRATION = 5;

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly string $password,
        private readonly string $migrationsDir,
    ) {
    }

    /** @return array{name: string, data: string, schema: int} */
    public function create(string $reason = ''): array
    {
        $schema = (new Migrator($this->pdo, $this->migrationsDir))->currentVersion();
        $sql = (new Dumper($this->pdo))->dump('Schemastand ' . $schema . ($reason !== '' ? ', Anlass: ' . $reason : ''));
        $gz = gzencode($sql, 9);
        if ($gz === false) {
            throw new BackupException('gzip fehlgeschlagen.');
        }
        $name = sprintf('training-backup-%s-s%d%s.sql.gz.enc', gmdate('Ymd-His', $this->clock->now()), $schema, $reason !== '' ? '-' . $reason : '');

        return ['name' => $name, 'data' => Encryptor::encrypt($gz, $this->password), 'schema' => $schema];
    }

    /**
     * Legt ein Backup in $dir ab (außerhalb des Docroots) und behält die neuesten $keep Dateien dieses Anlasses.
     *
     * @return string Pfad der Datei
     */
    public function store(string $dir, string $reason, int $keep = self::KEEP_PRE_MIGRATION): string
    {
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new BackupException('Backup-Ordner nicht anlegbar: ' . $dir);
        }
        $backup = $this->create($reason);
        $path = $dir . '/' . $backup['name'];
        if (@file_put_contents($path, $backup['data'], LOCK_EX) !== strlen($backup['data'])) {
            @unlink($path);
            throw new BackupException('Backup-Datei nicht schreibbar: ' . $path);
        }
        $files = glob($dir . '/training-backup-*-' . $reason . '.sql.gz.enc') ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }

        return $path;
    }
}
