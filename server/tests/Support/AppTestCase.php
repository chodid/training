<?php

declare(strict_types=1);

namespace Training\Tests\Support;

use PDO;
use PHPUnit\Framework\TestCase;
use Training\App;
use Training\Auth\LoginThrottle;
use Training\Config;
use Training\Database;
use Training\Http\Request;
use Training\Http\Response;
use Training\Migration\Migrator;

/**
 * Basis für Integrationstests über App::handle() gegen eine echte MySQL/MariaDB-Datenbank.
 * Achtung: Alle Tabellen der Testdatenbank werden gelöscht.
 */
abstract class AppTestCase extends TestCase
{
    protected const MIGRATION_SECRET = 'migration-secret-migration-secret-0123';
    protected const JWT_SECRET = 'jwt-secret-jwt-secret-jwt-secret-0123456';
    protected const APP_URL = 'https://training.example';

    protected PDO $pdo;
    protected FakeClock $clock;
    protected string $baseDir;
    /** @var array<string, string> Browser-Cookies */
    protected array $cookies = [];

    protected function setUp(): void
    {
        if (getenv('TEST_DB_NAME') === false) {
            self::markTestSkipped('Keine Testdatenbank konfiguriert (TEST_DB_NAME).');
        }
        $this->pdo = Database::connect(Config::fromArray(self::dbValues()));
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec('DROP TABLE `' . str_replace('`', '``', $table) . '`');
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $root = dirname(__DIR__, 2);
        (new Migrator($this->pdo, $root . '/migrations'))->migrate();

        $this->baseDir = sys_get_temp_dir() . '/training-app-' . bin2hex(random_bytes(4));
        mkdir($this->baseDir . '/public', 0777, true);
        symlink($root . '/migrations', $this->baseDir . '/migrations');
        symlink($root . '/templates', $this->baseDir . '/templates');
        if (is_dir($root . '/public/assets')) {
            symlink($root . '/public/assets', $this->baseDir . '/public/assets');
        }
        $this->writeEnv([]);
        $this->clock = new FakeClock(time());
        $this->cookies = [];
    }

    /** @param array<string, string> $extra */
    protected function writeEnv(array $extra): void
    {
        $values = [
            ...self::dbValues(),
            'APP_URL' => self::APP_URL,
            'MIGRATION_SECRET' => self::MIGRATION_SECRET,
            'OAUTH_JWT_SECRET' => self::JWT_SECRET,
            ...$extra,
        ];
        $lines = [];
        foreach ($values as $k => $v) {
            $lines[] = $k . '=' . $v;
        }
        file_put_contents($this->baseDir . '/.env', implode("\n", $lines) . "\n");
    }

    protected ?\Training\Intervals\HttpTransport $intervalsTransport = null;

    protected function app(): App
    {
        return new App($this->baseDir, $this->clock, new LoginThrottle(), $this->intervalsTransport);
    }

    /**
     * Führt einen Request aus und übernimmt Set-Cookie wie ein Browser.
     *
     * @param array<string, string> $data Formularfelder (POST) bzw. Query (GET)
     * @param array<string, string> $headers
     */
    protected function request(string $method, string $uri, array $data = [], array $headers = [], string $body = ''): Response
    {
        $path = (string) parse_url($uri, PHP_URL_PATH);
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        if ($method === 'GET') {
            $query = [...$query, ...$data];
            $data = [];
        }
        $lower = [];
        foreach ($headers as $k => $v) {
            $lower[strtolower($k)] = $v;
        }
        $request = new Request($method, $path === '/' ? '/' : rtrim($path, '/'), $lower, true, $query, $data, $this->cookies, $body, $uri);
        $response = $this->app()->handle($request);
        foreach ($response->cookies as $cookie) {
            [$pair] = explode(';', $cookie, 2);
            [$name, $value] = explode('=', $pair, 2);
            if ($value === '' || str_contains($cookie, 'Max-Age=0')) {
                unset($this->cookies[$name]);
            } else {
                $this->cookies[$name] = $value;
            }
        }

        return $response;
    }

    /** CSRF-Token aus dem versteckten Feld einer HTML-Seite. */
    protected static function csrfFrom(Response $response): string
    {
        self::assertMatchesRegularExpression('/name="csrf" value="([^"]+)"/', $response->body);
        preg_match('/name="csrf" value="([^"]+)"/', $response->body, $m);

        return html_entity_decode($m[1]);
    }

    protected function setupUser(string $login = 'philipp', string $password = 'richtig-langes-passwort'): void
    {
        $form = $this->request('GET', '/setup');
        $response = $this->request('POST', '/setup', [
            'csrf' => self::csrfFrom($form),
            'secret' => self::MIGRATION_SECRET,
            'login' => $login,
            'password' => $password,
            'password2' => $password,
            'tz' => 'Europe/Berlin',
        ]);
        self::assertSame(303, $response->status, $response->body);
    }

    /** @return array<string, string> */
    private static function dbValues(): array
    {
        return [
            'DB_HOST' => (string) (getenv('TEST_DB_HOST') ?: '127.0.0.1'),
            'DB_PORT' => (string) (getenv('TEST_DB_PORT') ?: '3306'),
            'DB_NAME' => (string) getenv('TEST_DB_NAME'),
            'DB_USER' => (string) getenv('TEST_DB_USER'),
            'DB_PASSWORD' => (string) getenv('TEST_DB_PASSWORD'),
        ];
    }
}
