<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\App;
use Training\Migration\MigrationException;
use Training\Migration\Migrator;

final class MigrationFilesTest extends TestCase
{
    public function testSchemaVersionMatchesHighestMigration(): void
    {
        self::assertSame(App::SCHEMA_VERSION, Migrator::latestVersion(dirname(__DIR__, 2) . '/migrations'));
    }

    public function testGapInNumberingIsRejected(): void
    {
        $dir = $this->tempDir(['0001_a.sql', '0003_c.sql']);
        $this->expectException(MigrationException::class);
        Migrator::available($dir);
    }

    public function testInvalidFileNameIsRejected(): void
    {
        $dir = $this->tempDir(['0001_a.sql', 'notiz.txt']);
        $this->expectException(MigrationException::class);
        Migrator::available($dir);
    }

    public function testDuplicateNumberIsRejected(): void
    {
        $dir = $this->tempDir(['0001_a.sql', '0001_b.php']);
        $this->expectException(MigrationException::class);
        Migrator::available($dir);
    }

    /** @param list<string> $files */
    private function tempDir(array $files): string
    {
        $dir = sys_get_temp_dir() . '/migr-' . bin2hex(random_bytes(4));
        mkdir($dir);
        foreach ($files as $file) {
            touch($dir . '/' . $file);
        }

        return $dir;
    }
}
