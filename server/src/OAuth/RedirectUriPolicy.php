<?php

declare(strict_types=1);

namespace Training\OAuth;

/**
 * Zulässige Redirect-URIs (D-36): https:// mit Host oder http:// nur für localhost bzw. 127.0.0.1/[::1];
 * ohne Fragment und ohne Benutzerangaben. Beim Authorize wird exakt gegen die registrierte URI verglichen.
 */
final class RedirectUriPolicy
{
    private const LOOPBACK = ['localhost', '127.0.0.1', '[::1]'];

    public static function isAllowed(string $uri): bool
    {
        if (strlen($uri) > 2000 || preg_match('/\s/', $uri)) {
            return false;
        }
        $parts = parse_url($uri);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
            return false;
        }
        if (isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);

        return $scheme === 'https' || ($scheme === 'http' && in_array($host, self::LOOPBACK, true));
    }
}
