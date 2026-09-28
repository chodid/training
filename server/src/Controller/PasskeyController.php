<?php

declare(strict_types=1);

namespace Training\Controller;

use lbuchs\WebAuthn\WebAuthnException;
use Training\App;
use Training\Auth\Passkeys;
use Training\Http\Request;
use Training\Http\Response;

/**
 * JSON-Endpunkte für Passkeys (D-44), aufgerufen von public/js/passkey.js:
 * POST /passkey/register/options, /passkey/register (angemeldet, Header X-CSRF-Token),
 * POST /passkey/login/options, /passkey/login (Anmeldung; Origin-Bindung durch WebAuthn).
 */
final class PasskeyController
{
    public function __construct(private readonly App $app)
    {
    }

    public function passkeys(): Passkeys
    {
        $config = $this->app->config();

        return new Passkeys($this->app->pdo(), $this->app->clock(), (string) $config->get('APP_URL'), $config->require('OAUTH_JWT_SECRET'), $this->app->secureCookies());
    }

    public function registerOptions(Request $request): Response
    {
        $session = $this->app->sessions()->current($request);
        if ($session === null || !$session->verifyCsrf($request->header('X-CSRF-Token'))) {
            return Response::error(403, 'Nicht angemeldet oder Formular abgelaufen.');
        }
        $o = $this->passkeys()->createOptions($session->userId, $session->login);

        return Response::json(200, (array) json_decode((string) json_encode($o['options']), true))->withCookie($o['cookie']);
    }

    public function register(Request $request): Response
    {
        $session = $this->app->sessions()->current($request);
        if ($session === null || !$session->verifyCsrf($request->header('X-CSRF-Token'))) {
            return Response::error(403, 'Nicht angemeldet oder Formular abgelaufen.');
        }
        $data = json_decode($request->body, true);
        $passkeys = $this->passkeys();
        try {
            $id = $passkeys->register($session->userId, is_array($data) ? $data : [], $request);
        } catch (WebAuthnException $e) {
            return Response::error(400, 'Passkey nicht angelegt: ' . $e->getMessage())->withCookie($passkeys->clearCookie());
        }
        (new \Training\Data\AuditLog($this->app->pdo(), $this->app->clock()))->write('web', 'passkey_create', 'webauthn_credential', mb_substr($id, 0, 64), null, 'Passkey angelegt');

        return Response::json(201, ['status' => 'ok'])->withCookie($passkeys->clearCookie());
    }

    public function loginOptions(): Response
    {
        $passkeys = $this->passkeys();
        if ($passkeys->count() === 0) {
            return Response::error(404, 'Kein Passkey eingerichtet.');
        }
        $o = $passkeys->getOptions();

        return Response::json(200, (array) json_decode((string) json_encode($o['options']), true))->withCookie($o['cookie']);
    }

    public function login(Request $request): Response
    {
        $data = json_decode($request->body, true);
        $data = is_array($data) ? $data : [];
        $passkeys = $this->passkeys();
        try {
            $userId = $passkeys->verify($data, $request);
        } catch (WebAuthnException $e) {
            return Response::error(401, 'Anmeldung mit Passkey fehlgeschlagen: ' . $e->getMessage())->withCookie($passkeys->clearCookie());
        }
        // Ein Passkey ist stärker als das Passwort: eine Passwort-Sperre wird aufgehoben.
        $this->app->users()->resetFailures($userId);
        $next = WebController::safeNext(is_string($data['next'] ?? null) ? $data['next'] : null);

        return Response::json(200, ['status' => 'ok', 'redirect' => $next !== '' ? $next : '/'])
            ->withCookie($this->app->sessions()->create($userId))
            ->withCookie($passkeys->clearCookie());
    }
}
