<?php

declare(strict_types=1);

namespace Training\Tests\Support;

use Training\Auth\Passkeys;

/**
 * Software-Authenticator für Tests (WebAuthn, ES256, Attestierung "none"): erzeugt Registrierungs- und
 * Anmeldeantworten, wie sie ein Browser an den Server schickt.
 */
final class SoftAuthenticator
{
    private \OpenSSLAsymmetricKey $key;
    public string $credentialId;
    public int $signCount = 0;

    public function __construct(private readonly string $origin)
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        if ($key === false) {
            throw new \RuntimeException('EC-Schlüssel nicht erzeugbar');
        }
        $this->key = $key;
        $this->credentialId = random_bytes(16);
    }

    /** @param array<string, mixed> $options Antwort von /passkey/register/options @return array<string, string> */
    public function create(array $options, string $name = 'Test'): array
    {
        $challenge = self::binary($options['publicKey']['challenge']);
        $rpId = $options['publicKey']['rp']['id'];
        $clientData = json_encode(['type' => 'webauthn.create', 'challenge' => Passkeys::b64e($challenge), 'origin' => $this->origin, 'crossOrigin' => false]);
        $d = openssl_pkey_get_details($this->key)['ec'];
        $x = str_pad($d['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($d['y'], 32, "\0", STR_PAD_LEFT);
        $cose = "\xA5" . "\x01\x02" . "\x03\x26" . "\x20\x01" . "\x21\x58\x20" . $x . "\x22\x58\x20" . $y;
        $authData = hash('sha256', $rpId, true) . "\x45" . pack('N', $this->signCount) . str_repeat("\0", 16)
            . pack('n', strlen($this->credentialId)) . $this->credentialId . $cose;
        $att = "\xA3" . "\x63fmt" . "\x64none" . "\x67attStmt" . "\xA0" . "\x68authData" . "\x59" . pack('n', strlen($authData)) . $authData;

        return ['name' => $name, 'clientDataJSON' => Passkeys::b64e((string) $clientData), 'attestationObject' => Passkeys::b64e($att)];
    }

    /** @param array<string, mixed> $options Antwort von /passkey/login/options @return array<string, string> */
    public function get(array $options, string $rpId, string $next = ''): array
    {
        $challenge = self::binary($options['publicKey']['challenge']);
        $this->signCount++;
        $clientData = (string) json_encode(['type' => 'webauthn.get', 'challenge' => Passkeys::b64e($challenge), 'origin' => $this->origin, 'crossOrigin' => false]);
        $authData = hash('sha256', $rpId, true) . "\x05" . pack('N', $this->signCount);
        openssl_sign($authData . hash('sha256', $clientData, true), $sig, $this->key, OPENSSL_ALGO_SHA256);

        return ['id' => Passkeys::b64e($this->credentialId), 'clientDataJSON' => Passkeys::b64e($clientData), 'authenticatorData' => Passkeys::b64e($authData),
            'signature' => Passkeys::b64e($sig), 'next' => $next];
    }

    private static function binary(string $v): string
    {
        if (str_starts_with($v, '=?BINARY?B?')) {
            return (string) base64_decode(substr($v, 11, -2), true);
        }

        return Passkeys::b64d($v);
    }
}
