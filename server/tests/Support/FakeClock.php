<?php

declare(strict_types=1);

namespace Training\Tests\Support;

use Training\Clock;

final class FakeClock implements Clock
{
    public function __construct(public int $now = 1_790_000_000)
    {
    }

    public function now(): int
    {
        return $this->now;
    }

    public function advance(int $seconds): void
    {
        $this->now += $seconds;
    }
}
