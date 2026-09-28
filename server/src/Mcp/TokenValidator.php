<?php

declare(strict_types=1);

namespace Training\Mcp;

use Mcp\Server\Auth\JwtTokenValidator;
use Mcp\Server\Auth\TokenValidationResult;
use Mcp\Server\Auth\TokenValidatorInterface;
use Training\OAuth\OAuthConfig;

/**
 * Bearer-Prüfung am /mcp-Endpunkt: zuerst das statische Fallback-Token (nur wenn
 * MCP_STATIC_TOKEN_ENABLED=true, D-06), sonst JWT über den SDK-JwtTokenValidator (iss, aud, exp; D-32).
 * Tokens ohne exp werden zusätzlich abgelehnt.
 */
final class TokenValidator implements TokenValidatorInterface
{
    private readonly JwtTokenValidator $jwt;

    public function __construct(private readonly OAuthConfig $config)
    {
        $this->jwt = new JwtTokenValidator($config->jwtSecret, 'HS256', $config->issuer, $config->resource());
    }

    public function validate(string $token): TokenValidationResult
    {
        $static = $this->config->staticToken;
        if ($static !== null && $static !== '' && hash_equals($static, $token)) {
            return new TokenValidationResult(true, [
                'sub' => 'static',
                'client_id' => 'static',
                'scope' => implode(' ', array_keys(OAuthConfig::SCOPES)),
            ]);
        }

        $result = $this->jwt->validate($token);
        if ($result->valid && !isset($result->claims['exp'])) {
            return new TokenValidationResult(false, [], 'Token without exp');
        }

        return $result;
    }
}
