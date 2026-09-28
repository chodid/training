<?php

declare(strict_types=1);

namespace Training\Auth;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $login,
        public readonly string $passwordHash,
        public readonly string $tz,
        public readonly int $failedLogins,
        public readonly ?int $lockedUntil,
    ) {
    }
}
