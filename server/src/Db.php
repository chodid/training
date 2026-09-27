<?php

declare(strict_types=1);

namespace Training;

/**
 * Zeitwerte in der Datenbank: DATETIME in UTC.
 */
final class Db
{
    public static function ts(int $unix): string
    {
        return gmdate('Y-m-d H:i:s', $unix);
    }

    public static function time(?string $datetime): ?int
    {
        if ($datetime === null || $datetime === '') {
            return null;
        }
        $t = strtotime($datetime . ' UTC');

        return $t === false ? null : $t;
    }

    /** Zufallswert als Hex (für Tokens, Codes, IDs). */
    public static function randomHex(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /** Tokens werden nur als SHA-256 gespeichert (D-32, D-33, D-36). */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
