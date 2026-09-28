<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\App;
use Training\Http\Request;

final class AppTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/app-' . bin2hex(random_bytes(4));
        mkdir($this->baseDir);
        symlink(dirname(__DIR__, 2) . '/migrations', $this->baseDir . '/migrations');
    }

    public function testUnknownPathReturns404(): void
    {
        self::assertSame(404, $this->app()->handle(new Request('GET', '/gibtsnicht'))->status);
    }

    public function testWrongMethodReturns405(): void
    {
        $response = $this->app()->handle(new Request('GET', '/admin/migrate'));
        self::assertSame(405, $response->status);
        self::assertSame('POST', $response->headers['Allow']);
    }

    public function testHealthWithoutConfigReports503AndMissingKeys(): void
    {
        $response = $this->app()->handle(new Request('GET', '/health'));
        self::assertSame(503, $response->status);
        $data = json_decode($response->body, true);
        self::assertSame('error', $data['status']);
        self::assertSame('fehlt', $data['checks']['config']['status']);
        self::assertSame('nicht_geprueft', $data['checks']['database']);
        self::assertSame('ok', $data['checks']['migrations']);
    }

    public function testMigrateWithoutConfigReturns503(): void
    {
        self::assertSame(503, $this->app()->handle(new Request('POST', '/admin/migrate'))->status);
    }

    public function testMigrateRejectsShortSecret(): void
    {
        $this->writeEnv('kurz');
        self::assertSame(503, $this->app()->handle(new Request('POST', '/admin/migrate', ['x-migration-secret' => 'kurz']))->status);
    }

    public function testMigrateWithoutHeaderReturns401(): void
    {
        $this->writeEnv(str_repeat('a', 64));
        self::assertSame(401, $this->app()->handle(new Request('POST', '/admin/migrate'))->status);
    }

    public function testMigrateWithWrongSecretReturns403(): void
    {
        $this->writeEnv(str_repeat('a', 64));
        $response = $this->app()->handle(new Request('POST', '/admin/migrate', ['x-migration-secret' => str_repeat('b', 64)]));
        self::assertSame(403, $response->status);
        self::assertStringNotContainsString('aaaa', $response->body);
    }

    public function testRequestFromGlobalsNormalizesPathAndHeaders(): void
    {
        $request = Request::fromGlobals([
            'REQUEST_METHOD' => 'post',
            'REQUEST_URI' => '/admin/migrate/?x=1',
            'HTTP_X_MIGRATION_SECRET' => 's',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Bearer t',
            'HTTPS' => 'on',
        ]);
        self::assertSame('POST', $request->method);
        self::assertSame('/admin/migrate', $request->path);
        self::assertSame('s', $request->header('X-Migration-Secret'));
        self::assertSame('Bearer t', $request->header('Authorization'));
        self::assertTrue($request->https);
    }

    private function app(): App
    {
        return new App($this->baseDir);
    }

    private function writeEnv(string $secret): void
    {
        file_put_contents($this->baseDir . '/.env', implode("\n", [
            'APP_URL=https://example.org',
            'DB_HOST=127.0.0.1',
            'DB_NAME=x',
            'DB_USER=x',
            'DB_PASSWORD=x',
            'MIGRATION_SECRET=' . $secret,
            'OAUTH_JWT_SECRET=' . str_repeat('j', 32),
            'BACKUP_PASSWORD=' . str_repeat('b', 16),
        ]));
    }
}
