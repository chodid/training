<?php

declare(strict_types=1);

namespace Training\OAuth;

use PDO;
use Training\Clock;
use Training\Db;

/** Autorisierungscodes (D-36): gehasht, 10 Minuten, einmalig, PKCE S256. */
final class AuthCodeRepository
{
    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    public function create(string $clientId, int $userId, string $challenge, string $redirectUri, string $scope): string
    {
        $now = $this->clock->now();
        $this->pdo->prepare('DELETE FROM oauth_auth_code WHERE expires_at < ?')->execute([Db::ts($now - 86400)]);

        $code = Db::randomHex(32);
        $this->pdo->prepare('INSERT INTO oauth_auth_code (code_hash, client_id, user_id, code_challenge, method, redirect_uri, scope, expires_at, used) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)')
            ->execute([Db::hash($code), $clientId, $userId, $challenge, 'S256', $redirectUri, $scope, Db::ts($now + OAuthConfig::AUTH_CODE_TTL)]);

        return $code;
    }

    /**
     * Löst einen Code ein. Der Code wird vor allen weiteren Prüfungen verbraucht, damit auch ein
     * fehlgeschlagener Versuch ihn entwertet.
     *
     * @return array{user_id: int, scope: string}
     * @throws OAuthException invalid_grant
     */
    public function consume(string $code, string $clientId, ?string $redirectUri, string $verifier): array
    {
        $hash = Db::hash($code);
        $stmt = $this->pdo->prepare('SELECT * FROM oauth_auth_code WHERE code_hash = ?');
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if ($row === false) {
            throw new OAuthException('invalid_grant', 'Autorisierungscode unbekannt.');
        }
        $update = $this->pdo->prepare('UPDATE oauth_auth_code SET used = 1 WHERE code_hash = ? AND used = 0');
        $update->execute([$hash]);
        if ($update->rowCount() !== 1) {
            throw new OAuthException('invalid_grant', 'Autorisierungscode wurde bereits verwendet.');
        }
        if ((Db::time($row['expires_at']) ?? 0) <= $this->clock->now()) {
            throw new OAuthException('invalid_grant', 'Autorisierungscode abgelaufen.');
        }
        if (!hash_equals((string) $row['client_id'], $clientId)) {
            throw new OAuthException('invalid_grant', 'Autorisierungscode gehört zu einem anderen Client.');
        }
        if ($redirectUri !== null && $redirectUri !== (string) $row['redirect_uri']) {
            throw new OAuthException('invalid_grant', 'redirect_uri stimmt nicht mit der Autorisierung überein.');
        }
        if (!Pkce::verify($verifier, (string) $row['code_challenge'])) {
            throw new OAuthException('invalid_grant', 'PKCE-Prüfung fehlgeschlagen (code_verifier).');
        }

        return ['user_id' => (int) $row['user_id'], 'scope' => (string) $row['scope']];
    }
}
