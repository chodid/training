<?php

declare(strict_types=1);

namespace Training\OAuth;

/** PKCE nach RFC 7636, nur S256 (D-36). */
final class Pkce
{
    public static function isValidChallenge(string $challenge): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{43}$/', $challenge);
    }

    public static function isValidVerifier(string $verifier): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier);
    }

    public static function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public static function verify(string $verifier, string $challenge): bool
    {
        return self::isValidVerifier($verifier) && hash_equals($challenge, self::challenge($verifier));
    }
}
