<?php

declare(strict_types=1);

namespace Training\Auth;

use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;
use PDO;
use Training\Clock;
use Training\Db;
use Training\Http\Request;

/**
 * Passkeys (WebAuthn, D-44) zusätzlich zum Passwort, mit lbuchs/webauthn (ohne Attestierungsprüfung, Format "none").
 * Die Challenge liegt in einem signierten, 5 Minuten gültigen Cookie (HttpOnly, SameSite=Strict), damit sie auch vor
 * dem Login zur Verfügung steht. Relying Party = Host aus APP_URL.
 */
final class Passkeys
{
    public const COOKIE = 'training_webauthn';
    private const TTL = 300;

    private readonly string $rpId;

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        string $appUrl,
        private readonly string $secret,
        private readonly bool $secure,
    ) {
        $this->rpId = (string) parse_url($appUrl, PHP_URL_HOST);
    }

    private function webAuthn(): WebAuthn
    {
        return new WebAuthn('Training', $this->rpId, ['none', 'packed', 'fido-u2f', 'apple', 'android-key', 'android-safetynet', 'tpm'], false);
    }

    /** @return array{options: mixed, cookie: string} */
    public function createOptions(int $userId, string $login): array
    {
        $wa = $this->webAuthn();
        $exclude = array_map(static fn (string $id): string => self::b64d($id), $this->credentialIds($userId));
        $args = $wa->getCreateArgs(pack('N', $userId), $login, $login, 60, true, false, null, $exclude);

        return ['options' => $args, 'cookie' => $this->challengeCookie($wa->getChallenge()->getBinaryString(), 'create')];
    }

    /**
     * @param array<string, mixed> $data clientDataJSON, attestationObject (base64url), name
     * @throws WebAuthnException
     */
    public function register(int $userId, array $data, Request $request): string
    {
        $challenge = $this->readChallenge($request, 'create');
        $result = $this->webAuthn()->processCreate(self::b64d((string) ($data['clientDataJSON'] ?? '')), self::b64d((string) ($data['attestationObject'] ?? '')), $challenge, false, true, false, false);
        $id = self::b64e((string) $result->credentialId);
        $name = mb_substr(trim((string) ($data['name'] ?? '')), 0, 100);
        $this->pdo->prepare('INSERT INTO webauthn_credential (id, user_id, name, public_key, sign_count, created_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$id, $userId, $name !== '' ? $name : 'Passkey', (string) $result->credentialPublicKey, (int) ($result->signatureCounter ?? 0), Db::ts($this->clock->now())]);

        return $id;
    }

    /** @return array{options: mixed, cookie: string} */
    public function getOptions(): array
    {
        $wa = $this->webAuthn();
        $ids = array_map(static fn (string $id): string => self::b64d($id), $this->credentialIds(null));
        $args = $wa->getGetArgs($ids, 60, true, true, true, true, true, false);

        return ['options' => $args, 'cookie' => $this->challengeCookie($wa->getChallenge()->getBinaryString(), 'get')];
    }

    /**
     * Prüft eine Anmeldung und gibt die Benutzer-ID zurück.
     *
     * @param array<string, mixed> $data id, clientDataJSON, authenticatorData, signature (base64url)
     * @throws WebAuthnException
     */
    public function verify(array $data, Request $request): int
    {
        $challenge = $this->readChallenge($request, 'get');
        $stmt = $this->pdo->prepare('SELECT * FROM webauthn_credential WHERE id = ?');
        $stmt->execute([(string) ($data['id'] ?? '')]);
        $cred = $stmt->fetch();
        if ($cred === false) {
            throw new WebAuthnException('Passkey unbekannt.');
        }
        $wa = $this->webAuthn();
        $wa->processGet(self::b64d((string) ($data['clientDataJSON'] ?? '')), self::b64d((string) ($data['authenticatorData'] ?? '')),
            self::b64d((string) ($data['signature'] ?? '')), (string) $cred['public_key'], $challenge, (int) $cred['sign_count'], false, true);
        $this->pdo->prepare('UPDATE webauthn_credential SET sign_count = ?, last_used_at = ? WHERE id = ?')
            ->execute([(int) ($wa->getSignatureCounter() ?? $cred['sign_count']), Db::ts($this->clock->now()), $cred['id']]);

        return (int) $cred['user_id'];
    }

    /** @return list<array<string, mixed>> */
    public function list(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, created_at, last_used_at FROM webauthn_credential WHERE user_id = ? ORDER BY created_at');
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function count(): int
    {
        try {
            return (int) $this->pdo->query('SELECT COUNT(*) FROM webauthn_credential')->fetchColumn();
        } catch (\PDOException) {
            return 0;
        }
    }

    public function delete(int $userId, string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM webauthn_credential WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() === 1;
    }

    public function clearCookie(): string
    {
        return self::COOKIE . '=; Path=/; Max-Age=0; HttpOnly; SameSite=Strict' . ($this->secure ? '; Secure' : '');
    }

    /** @return list<string> */
    private function credentialIds(?int $userId): array
    {
        $stmt = $userId === null
            ? $this->pdo->query('SELECT id FROM webauthn_credential')
            : $this->pdo->prepare('SELECT id FROM webauthn_credential WHERE user_id = ?');
        if ($userId !== null) {
            $stmt->execute([$userId]);
        }

        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function challengeCookie(string $challenge, string $purpose): string
    {
        $exp = $this->clock->now() + self::TTL;
        $value = self::b64e($challenge) . '.' . $exp . '.' . $this->mac($challenge, $exp, $purpose);

        return self::COOKIE . '=' . $value . '; Path=/; Max-Age=' . self::TTL . '; HttpOnly; SameSite=Strict' . ($this->secure ? '; Secure' : '');
    }

    /** @throws WebAuthnException */
    private function readChallenge(Request $request, string $purpose): ByteBuffer
    {
        $parts = explode('.', (string) $request->cookie(self::COOKIE));
        if (count($parts) !== 3) {
            throw new WebAuthnException('Anmeldevorgang abgelaufen, bitte erneut starten.');
        }
        [$c, $exp, $mac] = $parts;
        $challenge = self::b64d($c);
        if ((int) $exp < $this->clock->now() || !hash_equals($this->mac($challenge, (int) $exp, $purpose), $mac)) {
            throw new WebAuthnException('Anmeldevorgang abgelaufen, bitte erneut starten.');
        }

        return new ByteBuffer($challenge);
    }

    private function mac(string $challenge, int $exp, string $purpose): string
    {
        return hash_hmac('sha256', 'webauthn|' . $purpose . '|' . $exp . '|' . $challenge, $this->secret);
    }

    public static function b64e(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64d(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4), true);
    }
}
