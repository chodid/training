<?php

declare(strict_types=1);

namespace Training\Auth;

final class LoginResult
{
    public const OK = 'ok';
    public const FAILED = 'failed';
    public const LOCKED = 'locked';

    private function __construct(
        public readonly string $status,
        public readonly ?int $userId = null,
        public readonly ?int $lockedUntil = null,
        public readonly int $remaining = 0,
    ) {
    }

    public static function ok(int $userId): self
    {
        return new self(self::OK, $userId);
    }

    public static function failed(int $remaining): self
    {
        return new self(self::FAILED, remaining: $remaining);
    }

    public static function locked(int $until): self
    {
        return new self(self::LOCKED, lockedUntil: $until);
    }
}
