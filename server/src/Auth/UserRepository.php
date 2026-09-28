<?php

declare(strict_types=1);

namespace Training\Auth;

use PDO;
use Training\Clock;
use Training\Db;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    public function exists(): bool
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM `user`')->fetchColumn() > 0;
    }

    /** Einzelnutzer-System (N8): der erste (und einzige) Benutzer. */
    public function first(): ?User
    {
        $row = $this->pdo->query('SELECT * FROM `user` ORDER BY id LIMIT 1')->fetch();

        return $row === false ? null : self::map($row);
    }

    public function find(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `user` WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : self::map($row);
    }

    /**
     * Legt den einzigen Benutzer an (D-34). Gibt null zurück, wenn bereits einer existiert.
     */
    public function createFirst(string $login, string $password, string $tz): ?int
    {
        $lock = (int) $this->pdo->query("SELECT GET_LOCK('training_setup', 5)")->fetchColumn();
        if ($lock !== 1) {
            return null;
        }
        try {
            if ($this->exists()) {
                return null;
            }
            $stmt = $this->pdo->prepare('INSERT INTO `user` (login, password_hash, tz, failed_logins, locked_until, created_at) VALUES (?, ?, ?, 0, NULL, ?)');
            $stmt->execute([$login, Password::hash($password), $tz, Db::ts($this->clock->now())]);

            return (int) $this->pdo->lastInsertId();
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('training_setup')");
        }
    }

    /** Zählt einen Fehlversuch hoch und gibt den neuen Zählerstand zurück. */
    public function recordFailure(int $id, LoginThrottle $throttle): int
    {
        $this->pdo->prepare('UPDATE `user` SET failed_logins = failed_logins + 1 WHERE id = ?')->execute([$id]);
        $stmt = $this->pdo->prepare('SELECT failed_logins FROM `user` WHERE id = ?');
        $stmt->execute([$id]);
        $failures = (int) $stmt->fetchColumn();
        $lock = $throttle->lockSeconds($failures);
        if ($lock > 0) {
            $this->pdo->prepare('UPDATE `user` SET locked_until = ? WHERE id = ?')
                ->execute([Db::ts($this->clock->now() + $lock), $id]);
        }

        return $failures;
    }

    public function resetFailures(int $id): void
    {
        $this->pdo->prepare('UPDATE `user` SET failed_logins = 0, locked_until = NULL WHERE id = ?')->execute([$id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->pdo->prepare('UPDATE `user` SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
    }

    /** @param array<string, mixed> $row */
    private static function map(array $row): User
    {
        return new User(
            (int) $row['id'],
            (string) $row['login'],
            (string) $row['password_hash'],
            (string) $row['tz'],
            (int) $row['failed_logins'],
            Db::time($row['locked_until'] ?? null),
        );
    }
}
