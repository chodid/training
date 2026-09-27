<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Config;
use Training\ConfigException;

final class ConfigTest extends TestCase
{
    public function testParseHandlesCommentsQuotesAndBom(): void
    {
        $values = Config::parse("\xEF\xBB\xBF# Kommentar\n\nAPP_URL=https://example.org\nDB_PASSWORD=\"a#b c\"\nDB_USER='user'\nDB_NAME=training # Kommentar\nexport DB_HOST=localhost\nkaputt\nlower=x\r\nEMPTY=\n");

        self::assertSame([
            'APP_URL' => 'https://example.org',
            'DB_PASSWORD' => 'a#b c',
            'DB_USER' => 'user',
            'DB_NAME' => 'training',
            'DB_HOST' => 'localhost',
            'EMPTY' => '',
        ], $values);
    }

    public function testValueWithEqualsSignAndHashWithoutSpace(): void
    {
        self::assertSame(['KEY' => 'a=b#c'], Config::parse('KEY=a=b#c'));
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(ConfigException::class);
        Config::fromFile(sys_get_temp_dir() . '/gibt-es-nicht-' . bin2hex(random_bytes(4)) . '/.env');
    }

    public function testMissingRequiredKeysAreListedWithoutValues(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($file, "APP_URL=https://example.org\nDB_PASSWORD=geheim\nDB_NAME=\n");
        try {
            Config::fromFile($file);
            self::fail('ConfigException erwartet');
        } catch (ConfigException $e) {
            self::assertSame(['DB_HOST', 'DB_NAME', 'DB_USER', 'MIGRATION_SECRET', 'OAUTH_JWT_SECRET'], $e->missingKeys);
            self::assertStringNotContainsString('geheim', $e->getMessage());
        } finally {
            unlink($file);
        }
    }

    public function testShortJwtSecretIsRejected(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($file, "APP_URL=https://example.org\nDB_HOST=h\nDB_NAME=n\nDB_USER=u\nDB_PASSWORD=p\nMIGRATION_SECRET=m\nOAUTH_JWT_SECRET=" . str_repeat('x', 31) . "\n");
        try {
            Config::fromFile($file);
            self::fail('ConfigException erwartet');
        } catch (ConfigException $e) {
            self::assertSame(['OAUTH_JWT_SECRET'], $e->missingKeys);
            self::assertStringNotContainsString('xxxx', $e->getMessage());
        } finally {
            unlink($file);
        }
    }

    public function testBoolFlag(): void
    {
        $config = Config::fromArray(['A' => 'true', 'B' => 'false', 'C' => '1', 'D' => 'TRUE']);
        self::assertTrue($config->bool('A'));
        self::assertFalse($config->bool('B'));
        self::assertTrue($config->bool('C'));
        self::assertTrue($config->bool('D'));
        self::assertFalse($config->bool('X'));
    }

    public function testGetTreatsEmptyAsDefault(): void
    {
        $config = Config::fromArray(['A' => '', 'B' => 'x']);
        self::assertSame('d', $config->get('A', 'd'));
        self::assertSame('x', $config->get('B'));
        self::assertNull($config->get('C'));
    }
}
