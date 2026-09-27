<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Migration\SqlSplitter;

final class SqlSplitterTest extends TestCase
{
    public function testSplitsStatementsAndIgnoresComments(): void
    {
        $sql = "-- Kopf; mit Semikolon\nCREATE TABLE a (id INT); # noch ein; Kommentar\n/* Block; */ INSERT INTO a VALUES (1);\n\n";

        self::assertSame(['CREATE TABLE a (id INT)', 'INSERT INTO a VALUES (1)'], SqlSplitter::split($sql));
    }

    public function testSemicolonsInStringsAndIdentifiersDoNotSplit(): void
    {
        $sql = "INSERT INTO `x;y` VALUES ('a;b', \"c;d\", 'it''s;', 'e\\';f');SELECT 1";

        self::assertSame(
            ["INSERT INTO `x;y` VALUES ('a;b', \"c;d\", 'it''s;', 'e\\';f')", 'SELECT 1'],
            SqlSplitter::split($sql),
        );
    }

    public function testDoubleDashWithoutSpaceIsNotAComment(): void
    {
        self::assertSame(['SELECT 1--1'], SqlSplitter::split('SELECT 1--1;'));
    }

    public function testCommentOnlyInputYieldsNothing(): void
    {
        self::assertSame([], SqlSplitter::split("-- nur Kommentar\n/* x */\n;\n"));
    }
}
