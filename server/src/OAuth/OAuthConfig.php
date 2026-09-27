<?php

declare(strict_types=1);

namespace Training\OAuth;

use Training\Config;

/**
 * Festwerte des Autorisierungsservers (D-32, D-36) und aus .env abgeleitete URLs.
 */
final class OAuthConfig
{
    public const ACCESS_TOKEN_TTL = 3600;
    public const AUTH_CODE_TTL = 600;
    /** Laufzeit eines Refresh-Tokens; jede Rotation beginnt neu (vorläufiger Wert, siehe Konzept AP-01). */
    public const REFRESH_TOKEN_TTL = 90 * 86400;
    /** Unbenutzte Client-Registrierungen werden nach 30 Tagen aufgeräumt (D-36). */
    public const UNUSED_CLIENT_TTL = 30 * 86400;

    /** Unterstützte Scopes mit Beschreibung für die Freigabeseite S7. */
    public const SCOPES = [
        'training:read' => ['Wochenplan, Einheiten und Feedback lesen', 'Check-ins und Schmerzverlauf lesen'],
        'training:write' => ['Wochenpläne schreiben und Einheiten ändern'],
    ];

    public function __construct(
        public readonly string $issuer,
        public readonly string $jwtSecret,
        public readonly ?string $staticToken = null,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        $static = $config->bool('MCP_STATIC_TOKEN_ENABLED') ? $config->get('MCP_STATIC_TOKEN') : null;

        return new self(rtrim($config->require('APP_URL'), '/'), $config->require('OAUTH_JWT_SECRET'), $static);
    }

    /** Resource-Identifier und Audience der Access-Tokens (D-32). */
    public function resource(): string
    {
        return $this->issuer . '/mcp';
    }

    /**
     * Bekannte Scopes aus der Anfrage; ohne (bekannte) Angabe alle Scopes.
     * Unbekannte Scopes werden ignoriert statt abgelehnt, damit Clients mit Standardwerten nicht scheitern.
     */
    public static function grantScope(?string $requested): string
    {
        $known = array_keys(self::SCOPES);
        $wanted = array_values(array_intersect($known, preg_split('/\s+/', trim((string) $requested)) ?: []));

        return implode(' ', $wanted === [] ? $known : $wanted);
    }

    /** @return array<string, mixed> Metadaten nach RFC 8414 */
    public function metadata(): array
    {
        return [
            'issuer' => $this->issuer,
            'authorization_endpoint' => $this->issuer . '/oauth/authorize',
            'token_endpoint' => $this->issuer . '/oauth/token',
            'registration_endpoint' => $this->issuer . '/oauth/register',
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'scopes_supported' => array_keys(self::SCOPES),
            'authorization_response_iss_parameter_supported' => true,
        ];
    }
}
