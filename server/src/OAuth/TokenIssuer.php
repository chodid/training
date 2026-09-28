<?php

declare(strict_types=1);

namespace Training\OAuth;

use Training\Clock;
use Training\Db;

/** Stellt Token-Antworten aus: Access-Token als JWT (D-32) plus Refresh-Token. */
final class TokenIssuer
{
    public function __construct(private readonly OAuthConfig $config, private readonly Clock $clock)
    {
    }

    public function accessToken(int $userId, string $clientId, string $scope): string
    {
        $now = $this->clock->now();

        return Jwt::encode([
            'iss' => $this->config->issuer,
            'aud' => $this->config->resource(),
            'sub' => (string) $userId,
            'client_id' => $clientId,
            'scope' => $scope,
            'iat' => $now,
            'exp' => $now + OAuthConfig::ACCESS_TOKEN_TTL,
            'jti' => Db::randomHex(16),
        ], $this->config->jwtSecret);
    }

    /** @return array<string, mixed> */
    public function response(int $userId, string $clientId, string $scope, string $refreshToken): array
    {
        return [
            'access_token' => $this->accessToken($userId, $clientId, $scope),
            'token_type' => 'Bearer',
            'expires_in' => OAuthConfig::ACCESS_TOKEN_TTL,
            'refresh_token' => $refreshToken,
            'scope' => $scope,
        ];
    }
}
