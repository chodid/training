<?php

declare(strict_types=1);

namespace Training\OAuth;

use PDO;
use Training\Clock;
use Training\Db;

/**
 * Refresh-Tokens (D-32): zufällig, nur gehasht, Rotation bei jeder Nutzung (gleiche family_id);
 * die Wiederverwendung eines bereits rotierten Tokens widerruft die gesamte Familie.
 */
final class RefreshTokenRepository
{
    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    public function issue(string $clientId, int $userId, string $scope, ?string $familyId = null): string
    {
        $now = $this->clock->now();
        $token = Db::randomHex(32);
        $this->pdo->prepare('INSERT INTO oauth_token (token_hash, type, client_id, user_id, family_id, scope, created_at, expires_at, used_at, revoked) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, 0)')
            ->execute([Db::hash($token), 'refresh', $clientId, $userId, $familyId ?? Db::randomHex(16), $scope, Db::ts($now), Db::ts($now + OAuthConfig::REFRESH_TOKEN_TTL)]);

        return $token;
    }

    /**
     * Rotiert ein Refresh-Token.
     *
     * @return array{refresh_token: string, user_id: int, scope: string}
     * @throws OAuthException invalid_grant
     */
    public function rotate(string $token, string $clientId): array
    {
        $hash = Db::hash($token);
        $stmt = $this->pdo->prepare("SELECT * FROM oauth_token WHERE token_hash = ? AND type = 'refresh'");
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if ($row === false) {
            throw new OAuthException('invalid_grant', 'Refresh-Token unbekannt.');
        }
        if (!hash_equals((string) $row['client_id'], $clientId)) {
            throw new OAuthException('invalid_grant', 'Refresh-Token gehört zu einem anderen Client.');
        }
        if ((int) $row['revoked'] === 1) {
            throw new OAuthException('invalid_grant', 'Refresh-Token widerrufen.');
        }
        if ($row['used_at'] !== null) {
            $this->revokeFamily((string) $row['family_id']);
            throw new OAuthException('invalid_grant', 'Refresh-Token wurde bereits verwendet; alle Tokens dieser Anmeldung sind widerrufen.');
        }
        $now = $this->clock->now();
        if ((Db::time($row['expires_at']) ?? 0) <= $now) {
            throw new OAuthException('invalid_grant', 'Refresh-Token abgelaufen.');
        }

        $update = $this->pdo->prepare('UPDATE oauth_token SET used_at = ? WHERE token_hash = ? AND used_at IS NULL');
        $update->execute([Db::ts($now), $hash]);
        if ($update->rowCount() !== 1) {
            $this->revokeFamily((string) $row['family_id']);
            throw new OAuthException('invalid_grant', 'Refresh-Token wurde bereits verwendet; alle Tokens dieser Anmeldung sind widerrufen.');
        }

        $userId = (int) $row['user_id'];
        $scope = (string) $row['scope'];

        return [
            'refresh_token' => $this->issue($clientId, $userId, $scope, (string) $row['family_id']),
            'user_id' => $userId,
            'scope' => $scope,
        ];
    }

    public function revokeFamily(string $familyId): void
    {
        $this->pdo->prepare('UPDATE oauth_token SET revoked = 1 WHERE family_id = ?')->execute([$familyId]);
    }
}
