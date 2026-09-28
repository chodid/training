<?php

declare(strict_types=1);

namespace Training\Auth;

/** Passwort-Hash mit Argon2id, sonst bcrypt (D-33). */
final class Password
{
    public const MIN_LENGTH = 12;

    public static function algorithm(): string|int|null
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algorithm());
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::algorithm());
    }
}
