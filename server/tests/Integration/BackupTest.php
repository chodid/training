<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use PDO;
use Training\App;
use Training\Backup\BackupException;
use Training\Backup\BackupService;
use Training\Backup\Encryptor;
use Training\Backup\UpdateService;
use Training\Db;
use Training\Migration\Migrator;
use Training\Migration\SqlSplitter;
use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeMailer;

/** AP-10: Backup (D-18), Restore, Pre-Migration-Dump und Schreibsperre (D-20). */
final class BackupTest extends AppTestCase
{
    public function testRestoreFromEncryptedBackupIntoEmptyDatabase(): void
    {
        $this->setupUser();
        $this->seed();
        $before = $this->snapshot();
        $backup = (new BackupService($this->pdo, $this->clock, self::BACKUP_PASSWORD, dirname(__DIR__, 2) . '/migrations'))->create('download');
        self::assertMatchesRegularExpression('/^training-backup-\d{8}-\d{6}-s' . App::SCHEMA_VERSION . '-download\.sql\.gz\.enc$/', $backup['name']);
        self::assertStringStartsWith('Salted__', $backup['data']);

        // Entschlüsseln wie auf einem anderen Rechner: openssl-Kommandozeile, falls vorhanden.
        $gz = $this->decryptWithOpenssl($backup['data']) ?? Encryptor::decrypt($backup['data'], self::BACKUP_PASSWORD);
        $sql = gzdecode($gz);
        self::assertIsString($sql);
        self::assertStringContainsString('CREATE TABLE `session`', $sql);
        self::assertStringNotContainsString('srpe_load`, `', $sql, 'berechnete Spalte nicht im INSERT');
        try {
            Encryptor::decrypt($backup['data'], 'falsches-passwort-123');
            self::fail('falsches Passwort muss scheitern');
        } catch (BackupException) {
        }

        // Leere Datenbank, Dump einspielen
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
            $this->pdo->exec('DROP TABLE `' . $t . '`');
        }
        foreach (SqlSplitter::split($sql) as $statement) {
            $this->pdo->exec($statement);
        }
        self::assertSame($before, $this->snapshot());
        self::assertSame(App::SCHEMA_VERSION, (new Migrator($this->pdo, dirname(__DIR__, 2) . '/migrations'))->currentVersion());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM web_session')->fetchColumn(), 'Sessions nicht gesichert');
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM oauth_token')->fetchColumn(), 'Tokens nicht gesichert');
        self::assertSame(330, (int) $this->pdo->query('SELECT srpe_load FROM session_execution')->fetchColumn(), 'srpe_load neu berechnet');
    }

    public function testPreMigrationDumpRotationAndAbortOnDumpFailure(): void
    {
        $this->seed();
        $dir = $this->migrationsWithExtra(3);
        $backups = $this->baseDir . '/backups';

        $update = new UpdateService($this->pdo, $this->clock, $dir, $backups, self::BACKUP_PASSWORD);
        $r = $update->migrate();
        self::assertSame([App::SCHEMA_VERSION, App::SCHEMA_VERSION + 3], [$r['before'], $r['after']]);
        self::assertNotNull($r['backup']);
        self::assertFileExists($backups . '/' . $r['backup']);
        self::assertStringContainsString('-s' . App::SCHEMA_VERSION . '-vor-migration', $r['backup']);
        self::assertNull($update->migrate()['backup'], 'nichts ausstehend → kein Dump');

        // Rotation: höchstens 5 Pre-Migration-Dumps
        $service = new BackupService($this->pdo, $this->clock, self::BACKUP_PASSWORD, $dir);
        for ($i = 0; $i < 7; $i++) {
            $this->clock->advance(1);
            $service->store($backups, 'vor-migration');
        }
        self::assertCount(5, glob($backups . '/*-vor-migration.sql.gz.enc') ?: []);

        // Dump schlägt fehl → keine Migration
        $dir2 = $this->migrationsWithExtra(4);
        $failing = new UpdateService($this->pdo, $this->clock, $dir2, $backups, 'zu-kurz');
        try {
            $failing->migrate();
            self::fail('BackupException erwartet');
        } catch (BackupException) {
        }
        self::assertSame(App::SCHEMA_VERSION + 3, (new Migrator($this->pdo, $dir2))->currentVersion());

        $blocked = $this->baseDir . '/datei-statt-ordner';
        file_put_contents($blocked, 'x');
        try {
            (new UpdateService($this->pdo, $this->clock, $dir2, $blocked, self::BACKUP_PASSWORD))->migrate();
            self::fail('BackupException erwartet');
        } catch (BackupException) {
        }
        self::assertSame(App::SCHEMA_VERSION + 3, (new Migrator($this->pdo, $dir2))->currentVersion());
    }

    public function testWriteLockBlocksWritesUntilMigrationButtonFixesIt(): void
    {
        $this->setupUser();
        // Datenbank einen Stand zurück: letzte Migration entfernen
        $this->rollbackLastMigration();

        $week = $this->request('GET', '/woche');
        self::assertStringContainsString('Update erforderlich', $week->body);
        $form = $this->request('GET', '/checkin');
        $csrf = self::csrfFrom($form);
        $r = $this->request('POST', '/checkin', ['csrf' => $csrf, 'datum' => Db::ts($this->clock->now()) === '' ? '' : gmdate('Y-m-d', $this->clock->now()), 'recovery' => '2', 'soreness' => '2', 'pain' => 'nein']);
        self::assertSame(503, $r->status);
        self::assertStringContainsString('nichts gespeichert', $r->body);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM checkin')->fetchColumn());
        self::assertSame(503, $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'zeitzone', 'tz' => 'UTC'])->status);

        $settings = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('Datenbank ' . (App::SCHEMA_VERSION - 1), $settings->body);
        $m = $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'migrieren']);
        self::assertStringContainsString('Migration ausgeführt.', $m->body);
        self::assertCount(1, glob($this->baseDir . '/backups/*-vor-migration.sql.gz.enc') ?: [], 'Pre-Migration-Dump angelegt');
        self::assertSame(App::SCHEMA_VERSION, (new Migrator($this->pdo, dirname(__DIR__, 2) . '/migrations'))->currentVersion());

        $r = $this->request('POST', '/checkin', ['csrf' => $csrf, 'recovery' => '2', 'soreness' => '2', 'pain' => 'nein']);
        self::assertSame(303, $r->status);
        self::assertStringNotContainsString('Update erforderlich', $this->request('GET', '/woche')->body);
    }

    public function testMigrateEndpointReportsBackupAndAbortsWhenDumpFails(): void
    {
        $this->rollbackLastMigration();
        file_put_contents($this->baseDir . '/backups', 'kein Ordner');
        $r = $this->request('POST', '/admin/migrate', [], ['X-Migration-Secret' => self::MIGRATION_SECRET]);
        self::assertSame(500, $r->status);
        self::assertStringContainsString('keine Migration', $r->body);
        self::assertSame(App::SCHEMA_VERSION - 1, (new Migrator($this->pdo, dirname(__DIR__, 2) . '/migrations'))->currentVersion());

        unlink($this->baseDir . '/backups');
        $r = $this->request('POST', '/admin/migrate', [], ['X-Migration-Secret' => self::MIGRATION_SECRET]);
        self::assertSame(200, $r->status, $r->body);
        self::assertStringContainsString('vor-migration.sql.gz.enc', $r->body);
    }

    public function testDownloadFromSettings(): void
    {
        $this->setupUser();
        $page = $this->request('GET', '/einstellungen');
        $r = $this->request('POST', '/einstellungen', ['csrf' => self::csrfFrom($page), 'action' => 'backup']);
        self::assertSame(200, $r->status);
        self::assertSame('application/octet-stream', $r->headers['Content-Type']);
        self::assertMatchesRegularExpression('/attachment; filename="training-backup-.*-download\.sql\.gz\.enc"/', $r->headers['Content-Disposition']);
        self::assertStringContainsString('CREATE TABLE', (string) gzdecode(Encryptor::decrypt($r->body, self::BACKUP_PASSWORD)));
        self::assertSame('backup_download', $this->pdo->query('SELECT action FROM audit_log')->fetchColumn());
        self::assertSame(403, $this->request('POST', '/einstellungen', ['csrf' => 'x', 'action' => 'backup'])->status);
    }

    public function testJsonExport(): void
    {
        $this->setupUser();
        $this->seed();
        $page = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('Daten exportieren (JSON)', $page->body);
        $r = $this->request('POST', '/einstellungen', ['csrf' => self::csrfFrom($page), 'action' => 'export']);
        self::assertSame(200, $r->status);
        self::assertMatchesRegularExpression('/attachment; filename="training-export-\d{8}-\d{6}-s\d+\.json"/', $r->headers['Content-Disposition']);
        $data = json_decode($r->body, true);
        self::assertSame('training-export', $data['format']);
        self::assertSame(\Training\App::SCHEMA_VERSION, $data['schema_version']);
        self::assertSame("Knie'beuge \\ \"x\"", $data['tables']['session'][0]['plan_json']['exercises'][0]['name'], 'JSON-Spalten als Objekte');
        self::assertArrayNotHasKey('password_hash', $data['tables']['user'][0]);
        self::assertArrayNotHasKey('web_session', $data['tables']);
        self::assertArrayNotHasKey('oauth_token', $data['tables']);
        self::assertSame(330, (int) $data['tables']['session_execution'][0]['srpe_load']);
        self::assertSame('json_export', $this->pdo->query("SELECT action FROM audit_log WHERE action = 'json_export'")->fetchColumn());
    }

    public function testCronMailIntervalAndErrorDisplay(): void
    {
        $this->mailer = new FakeMailer();
        self::assertSame(503, $this->request('GET', '/cron/backup-mail?key=x')->status, 'ohne CRON_SECRET');
        $secret = str_repeat('c', 40);
        $this->writeEnv(['CRON_SECRET' => $secret, 'BACKUP_MAIL_TO' => 'athlet@example.org', 'BACKUP_MAIL_INTERVAL_DAYS' => '7']);
        self::assertSame(403, $this->request('GET', '/cron/backup-mail?key=falsch')->status);

        $r = $this->request('GET', '/cron/backup-mail?key=' . $secret);
        self::assertSame(200, $r->status, $r->body);
        self::assertSame('sent', json_decode($r->body, true)['status']);
        self::assertCount(1, $this->mailer->sent);
        self::assertSame('athlet@example.org', $this->mailer->sent[0]['to']);
        self::assertStringContainsString('openssl enc -d -aes-256-cbc -pbkdf2 -iter ' . Encryptor::ITERATIONS, $this->mailer->sent[0]['body']);
        self::assertStringContainsString('CREATE TABLE', (string) gzdecode(Encryptor::decrypt($this->mailer->sent[0]['data'], self::BACKUP_PASSWORD)));

        $this->clock->advance(86400);
        self::assertSame('skipped', json_decode($this->request('GET', '/cron/backup-mail?key=' . $secret)->body, true)['status']);
        self::assertSame('sent', json_decode($this->request('GET', '/cron/backup-mail?key=' . $secret . '&force=1')->body, true)['status']);

        // Fehler wird gemerkt und nach dem Login angezeigt
        $this->mailer->fail = 'SMTP-Anmeldung abgelehnt';
        $this->clock->advance(8 * 86400);
        self::assertSame(500, $this->request('GET', '/cron/backup-mail?key=' . $secret)->status);
        $this->setupUser();
        self::assertStringContainsString('Backup per E-Mail fehlgeschlagen', $this->request('GET', '/woche')->body);
        self::assertStringContainsString('SMTP-Anmeldung abgelehnt', $this->request('GET', '/einstellungen')->body);
    }

    private function decryptWithOpenssl(string $data): ?string
    {
        if (trim((string) shell_exec('command -v openssl')) === '') {
            return null;
        }
        $in = tempnam(sys_get_temp_dir(), 'enc');
        file_put_contents($in, $data);
        $cmd = sprintf('openssl enc -d -aes-256-cbc -pbkdf2 -iter %d -md sha256 -pass pass:%s -in %s', Encryptor::ITERATIONS, escapeshellarg(self::BACKUP_PASSWORD), escapeshellarg($in));
        $out = shell_exec($cmd);
        unlink($in);
        self::assertIsString($out, 'openssl konnte nicht entschlüsseln');

        return $out;
    }

    /** Kopie der echten Migrationen plus $n zusätzliche. */
    private function migrationsWithExtra(int $n): string
    {
        $dir = sys_get_temp_dir() . '/migr-extra-' . bin2hex(random_bytes(4));
        mkdir($dir);
        foreach (glob(dirname(__DIR__, 2) . '/migrations/*') ?: [] as $f) {
            copy($f, $dir . '/' . basename($f));
        }
        for ($i = 1; $i <= $n; $i++) {
            $v = App::SCHEMA_VERSION + $i;
            file_put_contents(sprintf('%s/%04d_extra_%d.sql', $dir, $v, $v), "CREATE TABLE t_extra_$v (id INT);");
        }

        return $dir;
    }

    private function seed(): void
    {
        $now = Db::ts($this->clock->now());
        $this->pdo->exec("INSERT INTO training_block (name, start_date, end_date, status, created_at, updated_at) VALUES ('Block „Ä“', '2026-09-21', '2026-11-15', 'aktiv', '$now', '$now')");
        $this->pdo->exec("INSERT INTO training_week (block_id, week_start, status, created_by, created_at, updated_at) VALUES (1, '2026-09-21', 'bestaetigt', 'mcp', '$now', '$now')");
        $plan = $this->pdo->quote((string) json_encode(['exercises' => [['name' => "Knie'beuge \\ \"x\"", 'sets' => 3, 'reps' => '8']]], JSON_UNESCAPED_UNICODE));
        $this->pdo->exec("INSERT INTO `session` (week_id, date, type, title, plan_json, created_at, updated_at) VALUES (1, '2026-09-21', 'kraft', 'Kraft; mit Semikolon', $plan, '$now', '$now')");
        $this->pdo->exec("INSERT INTO session_execution (session_id, duration_min, rpe_cr10, notes, source, created_at, updated_at) VALUES (1, 55, 6, 'Zeile1\nZeile2 -- kein Kommentar', 'web', '$now', '$now')");
        $this->pdo->exec("INSERT INTO pain_event (date, session_id, location, side, intensity_0_10, timing, created_at) VALUES ('2026-09-21', 1, 'knie', 'L', 3, 'danach', '$now')");
        $this->pdo->exec("INSERT INTO checkin (date, recovery_1_5, soreness_1_5, pain_flag, created_at, updated_at) VALUES ('2026-09-21', 2, 3, 1, '$now', '$now')");
        $this->pdo->exec("INSERT INTO audit_log (ts, actor, action, entity, summary) VALUES ('$now', 'web', 'test', 'x', 'Ümlaut')");
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function snapshot(): array
    {
        $out = [];
        foreach (['training_block', 'training_week', 'session', 'session_execution', 'pain_event', 'checkin', 'audit_log', 'user', 'schema_version'] as $t) {
            $out[$t] = $this->pdo->query('SELECT * FROM `' . $t . '` ORDER BY 1')->fetchAll();
        }

        return $out;
    }
}
