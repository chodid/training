<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Auth\LoginThrottle;

final class LoginThrottleTest extends TestCase
{
    public function testNoLockBelowTenFailures(): void
    {
        $t = new LoginThrottle();
        self::assertSame(0, $t->lockSeconds(0));
        self::assertSame(0, $t->lockSeconds(9));
        self::assertSame(1, $t->remaining(9));
        self::assertSame(10, $t->remaining(0));
    }

    public function testFiveMinutesDoublingUpTo24Hours(): void
    {
        $t = new LoginThrottle();
        self::assertSame(300, $t->lockSeconds(10));
        self::assertSame(600, $t->lockSeconds(11));
        self::assertSame(1200, $t->lockSeconds(12));
        self::assertSame(2400, $t->lockSeconds(13));
        self::assertSame(76800, $t->lockSeconds(18));
        self::assertSame(86400, $t->lockSeconds(19));
        self::assertSame(86400, $t->lockSeconds(500));
        self::assertSame(0, $t->remaining(12));
    }

    public function testShortenedTimeBase(): void
    {
        $t = new LoginThrottle(1, 8);
        self::assertSame(1, $t->lockSeconds(10));
        self::assertSame(2, $t->lockSeconds(11));
        self::assertSame(8, $t->lockSeconds(14));
    }
}
