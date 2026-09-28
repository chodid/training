<?php

declare(strict_types=1);

namespace Training\Auth;

use PDO;
use Training\Clock;
use Training\Db;
use Training\Http\Request;

/**
 * 30-Tage-Session der Webseite in der Tabelle web_session (D-33), gleitend über last_seen_at.
 * Cookie HttpOnly, Secure (bei https), SameSite=Lax; in der DB nur der SHA-256 des Tokens.
 */
final class SessionManager
{
    public const COOKIE = 'training_session';
    public const LIFETIME = 30 * 86400;
    /** Session und Cookie werden höchstens einmal pro Stunde verlängert. */
    private const SLIDE_INTERVAL = 3600;

    /** Set-Cookie für die verlängerte Session, falls in diesem Request verlängert wurde. */
    public ?string $refreshCookie = null;

    private ?WebSession $current = null;
    private bool $loaded = false;

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly bool $secure,
    ) {
    }

    /** Legt eine neue Session an und gibt den Set-Cookie-Wert zurück. */
    public function create(int $userId): string
    {
        $now = $this->clock->now();
        $this->pdo->prepare('DELETE FROM web_session WHERE expires_at < ?')->execute([Db::ts($now)]);

        $token = Db::randomHex(32);
        $this->pdo->prepare('INSERT INTO web_session (token_hash, user_id, csrf_secret, created_at, last_seen_at, expires_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([Db::hash($token), $userId, Db::randomHex(32), Db::ts($now), Db::ts($now), Db::ts($now + self::LIFETIME)]);
        $this->loaded = false;
        $this->current = null;

        return $this->cookie($token, self::LIFETIME);
    }

    public function current(Request $request): ?WebSession
    {
        if ($this->loaded) {
            return $this->current;
        }
        $this->loaded = true;

        $token = $request->cookie(self::COOKIE);
        if ($token === null || !preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null;
        }
        $now = $this->clock->now();
        $stmt = $this->pdo->prepare('SELECT s.token_hash, s.user_id, s.csrf_secret, s.last_seen_at, u.login, u.tz
            FROM web_session s JOIN `user` u ON u.id = s.user_id WHERE s.token_hash = ? AND s.expires_at > ?');
        $stmt->execute([Db::hash($token), Db::ts($now)]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        if ((Db::time($row['last_seen_at']) ?? 0) + self::SLIDE_INTERVAL <= $now) {
            $this->pdo->prepare('UPDATE web_session SET last_seen_at = ?, expires_at = ? WHERE token_hash = ?')
                ->execute([Db::ts($now), Db::ts($now + self::LIFETIME), $row['token_hash']]);
            $this->refreshCookie = $this->cookie($token, self::LIFETIME);
        }

        return $this->current = new WebSession(
            (string) $row['token_hash'],
            (int) $row['user_id'],
            (string) $row['login'],
            (string) $row['tz'],
            (string) $row['csrf_secret'],
        );
    }

    /** Beendet die Session und gibt den Set-Cookie-Wert zum Löschen zurück. */
    public function destroy(WebSession $session): string
    {
        $this->pdo->prepare('DELETE FROM web_session WHERE token_hash = ?')->execute([$session->tokenHash]);
        $this->current = null;
        $this->refreshCookie = null;

        return $this->cookie('', 0);
    }

    private function cookie(string $value, int $maxAge): string
    {
        return self::COOKIE . '=' . $value . '; Path=/; Max-Age=' . $maxAge . '; HttpOnly; SameSite=Lax' . ($this->secure ? '; Secure' : '');
    }
}
