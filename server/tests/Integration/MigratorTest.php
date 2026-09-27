<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Training\App;
use Training\Config;
use Training\Database;
use Training\Migration\MigrationException;
use Training\Migration\MigrationLockedException;
use Training\Migration\Migrator;

/**
 * Läuft gegen eine echte MySQL/MariaDB-Datenbank (CI: Service-Container).
 * Umgebungsvariablen: TEST_DB_HOST, TEST_DB_PORT, TEST_DB_NAME, TEST_DB_USER, TEST_DB_PASSWORD.
 * Achtung: Alle Tabellen der Testdatenbank werden gelöscht.
 */
final class MigratorTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        if (getenv('TEST_DB_NAME') === false) {
            self::markTestSkipped('Keine Testdatenbank konfiguriert (TEST_DB_NAME).');
        }
        $this->pdo = self::connect();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec('DROP TABLE `' . str_replace('`', '``', $table) . '`');
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testMigratesRealMigrationsAndIsIdempotent(): void
    {
        $migrator = new Migrator($this->pdo, dirname(__DIR__, 2) . '/migrations');
        self::assertSame(0, $migrator->currentVersion());

        $applied = $migrator->migrate();
        self::assertSame(App::SCHEMA_VERSION, count($applied));
        self::assertSame(App::SCHEMA_VERSION, $migrator->currentVersion());

        self::assertSame([], $migrator->migrate());
        self::assertSame(App::SCHEMA_VERSION, $migrator->currentVersion());
    }

    public function testFailedMigrationKeepsLastSuccessfulVersion(): void
    {
        $dir = $this->migrationDir([
            '0001_schema_version.sql' => (string) file_get_contents(dirname(__DIR__, 2) . '/migrations/0001_schema_version.sql'),
            '0002_ok.sql' => "CREATE TABLE t_ok (id INT);\nINSERT INTO t_ok VALUES (1);",
            '0003_kaputt.sql' => 'CREATE TABLE t_kaputt (id INT); SELECT * FROM gibt_es_nicht;',
            '0004_danach.php' => '<?php return function (PDO $pdo): void { $pdo->exec("CREATE TABLE t_danach (id INT)"); };',
        ]);
        $migrator = new Migrator($this->pdo, $dir);

        try {
            $migrator->migrate();
            self::fail('MigrationException erwartet');
        } catch (MigrationException $e) {
            self::assertStringContainsString('0003_kaputt', $e->getMessage());
            self::assertSame([['version' => 1, 'name' => 'schema_version'], ['version' => 2, 'name' => 'ok']], $e->applied);
        }
        self::assertSame(2, $migrator->currentVersion());
        self::assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 't_danach'")->fetchColumn());
    }

    public function testConcurrentMigrationIsRejected(): void
    {
        $other = self::connect();
        self::assertSame(1, (int) $other->query("SELECT GET_LOCK('training_migrate', 0)")->fetchColumn());
        try {
            $this->expectException(MigrationLockedException::class);
            (new Migrator($this->pdo, dirname(__DIR__, 2) . '/migrations'))->migrate();
        } finally {
            $other->query("SELECT RELEASE_LOCK('training_migrate')");
        }
    }

    private static function connect(): PDO
    {
        return Database::connect(Config::fromArray([
            'DB_HOST' => (string) getenv('TEST_DB_HOST') ?: '127.0.0.1',
            'DB_PORT' => (string) getenv('TEST_DB_PORT') ?: '3306',
            'DB_NAME' => (string) getenv('TEST_DB_NAME'),
            'DB_USER' => (string) getenv('TEST_DB_USER'),
            'DB_PASSWORD' => (string) getenv('TEST_DB_PASSWORD'),
        ]));
    }

    /** @param array<string, string> $files */
    private function migrationDir(array $files): string
    {
        $dir = sys_get_temp_dir() . '/migr-' . bin2hex(random_bytes(4));
        mkdir($dir);
        foreach ($files as $name => $content) {
            file_put_contents($dir . '/' . $name, $content);
        }

        return $dir;
    }
}
