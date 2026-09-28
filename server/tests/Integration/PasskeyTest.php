<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Auth\SessionManager;
use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\SoftAuthenticator;

/** Passkeys (D-44): Registrieren, Anmelden, Replay/Fremdschlüssel/abgelaufene Challenge, Entfernen, Sperre. */
final class PasskeyTest extends AppTestCase
{
    public function testRegisterLoginAndDelete(): void
    {
        $this->setupUser();
        $settings = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('data-passkey="register"', $settings->body);
        $csrf = self::csrfFrom($settings);
        $json = ['Content-Type' => 'application/json'];

        self::assertSame(403, $this->request('POST', '/passkey/register/options', [], $json + ['X-CSRF-Token' => 'falsch'], '{}')->status);
        $opts = $this->request('POST', '/passkey/register/options', [], $json + ['X-CSRF-Token' => $csrf], '{}');
        self::assertSame(200, $opts->status, $opts->body);
        $o = json_decode($opts->body, true);
        self::assertSame('training.example', $o['publicKey']['rp']['id']);

        $auth = new SoftAuthenticator(self::APP_URL);
        $reg = $this->request('POST', '/passkey/register', [], $json + ['X-CSRF-Token' => $csrf], (string) json_encode($auth->create($o, 'iPhone')));
        self::assertSame(201, $reg->status, $reg->body);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM webauthn_credential')->fetchColumn());
        self::assertStringContainsString('Passkey „iPhone“', $this->request('GET', '/einstellungen')->body);

        // Abmelden, mit Passkey anmelden (inkl. Rücksprung)
        $this->cookies = [];
        $login = $this->request('GET', '/login');
        self::assertStringContainsString('Mit Passkey anmelden', $login->body);
        $lo = json_decode($this->request('POST', '/passkey/login/options', [], $json, '{}')->body, true);
        $assertion = $auth->get($lo, 'training.example', '/einheit?id=1');
        $ok = $this->request('POST', '/passkey/login', [], $json, (string) json_encode($assertion));
        self::assertSame(200, $ok->status, $ok->body);
        self::assertSame('/einheit?id=1', json_decode($ok->body, true)['redirect']);
        self::assertArrayHasKey(SessionManager::COOKIE, $this->cookies);
        self::assertSame(1, (int) $this->pdo->query('SELECT sign_count FROM webauthn_credential')->fetchColumn());

        // Wiederholung derselben Antwort (Challenge-Cookie verbraucht) scheitert
        $this->cookies = [];
        self::assertSame(401, $this->request('POST', '/passkey/login', [], $json, (string) json_encode($assertion))->status);

        // Fremder Schlüssel mit gleicher ID scheitert
        $lo = json_decode($this->request('POST', '/passkey/login/options', [], $json, '{}')->body, true);
        $other = new SoftAuthenticator(self::APP_URL);
        $other->credentialId = $auth->credentialId;
        self::assertSame(401, $this->request('POST', '/passkey/login', [], $json, (string) json_encode($other->get($lo, 'training.example')))->status);

        // Falscher Origin scheitert
        $lo = json_decode($this->request('POST', '/passkey/login/options', [], $json, '{}')->body, true);
        $evil = new SoftAuthenticator('https://evil.example');
        $evil->credentialId = $auth->credentialId;
        self::assertSame(401, $this->request('POST', '/passkey/login', [], $json, (string) json_encode($evil->get($lo, 'training.example')))->status);

        // Abgelaufene Challenge
        $lo = json_decode($this->request('POST', '/passkey/login/options', [], $json, '{}')->body, true);
        $this->clock->advance(301);
        self::assertSame(401, $this->request('POST', '/passkey/login', [], $json, (string) json_encode($auth->get($lo, 'training.example')))->status);

        // Passkey hebt Passwort-Sperre auf
        $this->pdo->exec("UPDATE `user` SET failed_logins = 12, locked_until = '2099-01-01 00:00:00'");
        $lo = json_decode($this->request('POST', '/passkey/login/options', [], $json, '{}')->body, true);
        self::assertSame(200, $this->request('POST', '/passkey/login', [], $json, (string) json_encode($auth->get($lo, 'training.example')))->status);
        self::assertSame(0, (int) $this->pdo->query('SELECT failed_logins FROM `user`')->fetchColumn());

        // Entfernen
        $settings = $this->request('GET', '/einstellungen');
        $this->request('POST', '/einstellungen', ['csrf' => self::csrfFrom($settings), 'action' => 'passkey_loeschen', 'passkey_id' => \Training\Auth\Passkeys::b64e($auth->credentialId)]);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM webauthn_credential')->fetchColumn());
        self::assertStringNotContainsString('Mit Passkey anmelden', $this->request('GET', '/login')->body);
        self::assertSame(404, $this->request('POST', '/passkey/login/options', [], $json, '{}')->status);
    }
}
