<?php

declare(strict_types=1);

namespace Training\OAuth;

use PDO;
use Training\Clock;
use Training\Db;

/**
 * OAuth-Clients aus der offenen Dynamic Client Registration (RFC 7591, D-36).
 * Alle Clients sind öffentlich (kein Secret, PKCE Pflicht).
 */
final class ClientRepository
{
    private const MAX_REDIRECT_URIS = 10;

    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    /**
     * @param array<string, mixed> $metadata Registrierungsanfrage
     * @return array<string, mixed> Registrierungsantwort
     * @throws OAuthException invalid_redirect_uri / invalid_client_metadata
     */
    public function register(array $metadata): array
    {
        $uris = $metadata['redirect_uris'] ?? null;
        if (!is_array($uris) || $uris === [] || count($uris) > self::MAX_REDIRECT_URIS || !array_is_list($uris)) {
            throw new OAuthException('invalid_redirect_uri', 'redirect_uris muss eine Liste mit 1 bis 10 URIs sein.');
        }
        foreach ($uris as $uri) {
            if (!is_string($uri) || !RedirectUriPolicy::isAllowed($uri)) {
                throw new OAuthException('invalid_redirect_uri', 'Redirect-URI nicht zulässig (nur https:// oder http://localhost): ' . (is_string($uri) ? $uri : '?'));
            }
        }
        foreach (['grant_types' => ['authorization_code', 'refresh_token'], 'response_types' => ['code']] as $key => $allowed) {
            if (isset($metadata[$key]) && (!is_array($metadata[$key]) || array_diff($metadata[$key], $allowed) !== [])) {
                throw new OAuthException('invalid_client_metadata', $key . ' nicht unterstützt; erlaubt: ' . implode(', ', $allowed));
            }
        }
        $name = $metadata['client_name'] ?? null;
        $name = is_string($name) && trim($name) !== '' ? mb_substr(trim($name), 0, 255) : 'Unbenannter Client';

        $now = $this->clock->now();
        $this->pdo->prepare('DELETE FROM oauth_client WHERE last_used_at IS NULL AND created_at < ?')
            ->execute([Db::ts($now - OAuthConfig::UNUSED_CLIENT_TTL)]);

        $clientId = Db::randomHex(16);
        $this->pdo->prepare('INSERT INTO oauth_client (client_id, client_name, redirect_uris_json, created_at, last_used_at) VALUES (?, ?, ?, ?, NULL)')
            ->execute([$clientId, $name, json_encode(array_values($uris), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), Db::ts($now)]);

        return [
            'client_id' => $clientId,
            'client_id_issued_at' => $now,
            'client_name' => $name,
            'redirect_uris' => array_values($uris),
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
        ];
    }

    /** @return array{client_id: string, client_name: string, redirect_uris: list<string>, created_at: int, last_used_at: ?int}|null */
    public function find(string $clientId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM oauth_client WHERE client_id = ?');
        $stmt->execute([$clientId]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        $uris = json_decode((string) $row['redirect_uris_json'], true);

        return [
            'client_id' => (string) $row['client_id'],
            'client_name' => (string) $row['client_name'],
            'redirect_uris' => is_array($uris) ? array_values(array_filter($uris, 'is_string')) : [],
            'created_at' => (int) Db::time($row['created_at']),
            'last_used_at' => Db::time($row['last_used_at']),
        ];
    }

    public function touch(string $clientId): void
    {
        $this->pdo->prepare('UPDATE oauth_client SET last_used_at = ? WHERE client_id = ?')
            ->execute([Db::ts($this->clock->now()), $clientId]);
    }
}
