<?php

declare(strict_types=1);

namespace Training\OAuth;

/**
 * Ausstellen von Access-Tokens als JWT mit HS256 (D-32). Geprüft wird am /mcp-Endpunkt mit dem
 * JwtTokenValidator des SDK.
 */
final class Jwt
{
    /** @param array<string, mixed> $claims */
    public static function encode(array $claims, string $secret): string
    {
        $header = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $payload = self::b64(json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $signature = self::b64(hash_hmac('sha256', $header . '.' . $payload, $secret, true));

        return $header . '.' . $payload . '.' . $signature;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
