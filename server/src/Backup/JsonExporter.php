<?php

declare(strict_types=1);

namespace Training\Backup;

use PDO;
use Training\App;
use Training\Clock;
use Training\Migration\Migrator;

/**
 * JSON-Export aller Trainingsdaten für Portabilität (AP-09, D-42): lesbar ohne Datenbank und ohne Passwort.
 * Enthält dieselben Tabellen wie das SQL-Backup (ohne Sessions, OAuth-Tokens/-Codes, Cache und Passwort-Hash);
 * JSON-Spalten werden als Objekte ausgegeben, Zeitwerte in UTC.
 */
final class JsonExporter
{
    public const FORMAT = 'training-export';
    public const FORMAT_VERSION = 1;
    private const EXCLUDED = ['web_session', 'oauth_token', 'oauth_auth_code', 'ext_cache', 'webauthn_credential'];
    private const JSON_COLUMNS = ['plan_json', 'actual_json', 'goal_events_json', 'redirect_uris_json', 'warnzeichen', 'equipment_json', 'content_json', 'snapshot_json', 'kennzahlen_auto'];
    private const HIDDEN_COLUMNS = ['user' => ['password_hash', 'failed_logins', 'locked_until']];

    public function __construct(private readonly PDO $pdo, private readonly Clock $clock, private readonly string $migrationsDir)
    {
    }

    /** @return array{name: string, data: string} */
    public function export(): array
    {
        $tables = $this->pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name")
            ->fetchAll(PDO::FETCH_COLUMN);
        $out = [
            'format' => self::FORMAT,
            'format_version' => self::FORMAT_VERSION,
            'app_version' => App::VERSION,
            'schema_version' => (new Migrator($this->pdo, $this->migrationsDir))->currentVersion(),
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z', $this->clock->now()),
            'zeitzone_zeitwerte' => 'UTC',
            'tables' => [],
        ];
        foreach ($tables as $table) {
            $table = (string) $table;
            if (in_array($table, self::EXCLUDED, true)) {
                continue;
            }
            $rows = $this->pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                foreach (self::HIDDEN_COLUMNS[$table] ?? [] as $col) {
                    unset($row[$col]);
                }
                foreach (self::JSON_COLUMNS as $col) {
                    if (isset($row[$col]) && is_string($row[$col])) {
                        $row[$col] = json_decode($row[$col], true);
                    }
                }
            }
            unset($row);
            $out['tables'][$table] = $rows;
        }

        return [
            'name' => sprintf('training-export-%s-s%d.json', gmdate('Ymd-His', $this->clock->now()), $out['schema_version']),
            'data' => json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        ];
    }
}
