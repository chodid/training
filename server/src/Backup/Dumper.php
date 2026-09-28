<?php

declare(strict_types=1);

namespace Training\Backup;

use PDO;

/**
 * SQL-Dump der Datenbank per PHP (D-18, AP-10): Struktur aller Tabellen, Daten ohne Anmelde- und Token-Tabellen
 * (nach einem Restore wird neu angemeldet und der Connector neu freigegeben) und ohne Cache.
 * Berechnete Spalten (srpe_load) werden nicht geschrieben. Ergebnis ist in phpMyAdmin oder mit dem mysql-Client einspielbar.
 */
final class Dumper
{
    /** Tabellen, deren Daten nicht gesichert werden (Struktur schon). */
    public const DATA_EXCLUDED = ['web_session', 'oauth_token', 'oauth_auth_code', 'ext_cache'];
    private const BATCH = 100;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function dump(string $comment = ''): string
    {
        $tables = $this->pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name")
            ->fetchAll(PDO::FETCH_COLUMN);
        $out = "-- Training – Datenbank-Backup\n-- " . $comment . "\n-- Erstellt " . gmdate('Y-m-d H:i:s') . " UTC\n\n"
            . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET time_zone = '+00:00';\n\n";

        foreach ($tables as $table) {
            $table = (string) $table;
            $q = '`' . str_replace('`', '``', $table) . '`';
            $create = $this->pdo->query('SHOW CREATE TABLE ' . $q)->fetch(PDO::FETCH_NUM);
            $out .= "DROP TABLE IF EXISTS $q;\n" . $create[1] . ";\n\n";
            if (in_array($table, self::DATA_EXCLUDED, true)) {
                continue;
            }
            $columns = $this->writableColumns($table);
            if ($columns === []) {
                continue;
            }
            $colList = implode(', ', array_map(static fn (string $c): string => '`' . str_replace('`', '``', $c) . '`', $columns));
            $stmt = $this->pdo->query("SELECT $colList FROM $q");
            $rows = [];
            while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $rows[] = '(' . implode(', ', array_map($this->literal(...), $row)) . ')';
                if (count($rows) === self::BATCH) {
                    $out .= "INSERT INTO $q ($colList) VALUES\n" . implode(",\n", $rows) . ";\n";
                    $rows = [];
                }
            }
            if ($rows !== []) {
                $out .= "INSERT INTO $q ($colList) VALUES\n" . implode(",\n", $rows) . ";\n";
            }
            $out .= "\n";
        }

        return $out . "SET FOREIGN_KEY_CHECKS = 1;\n";
    }

    /** @return list<string> */
    private function writableColumns(string $table): array
    {
        $stmt = $this->pdo->prepare("SELECT column_name, extra FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ordinal_position");
        $stmt->execute([$table]);
        $columns = [];
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as [$name, $extra]) {
            if (stripos((string) $extra, 'GENERATED') === false) {
                $columns[] = (string) $name;
            }
        }

        return $columns;
    }

    private function literal(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return (string) $this->pdo->quote((string) $value);
    }
}
