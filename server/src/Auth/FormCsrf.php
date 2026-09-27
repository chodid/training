<?php

declare(strict_types=1);

namespace Training\Auth;

use Training\Http\Request;

/**
 * CSRF-Schutz für Formulare vor dem Login (Setup S0, Login S1) nach dem Double-Submit-Verfahren:
 * Zufallswert im Cookie und im versteckten Feld müssen übereinstimmen.
 */
final class FormCsrf
{
    public const COOKIE = 'training_csrf';
    public const FIELD = 'csrf';

    public function __construct(private readonly bool $secure)
    {
    }

    /**
     * Token für das Formular; $setCookie ist gesetzt, wenn ein neues Cookie nötig ist.
     *
     * @return array{token: string, setCookie: ?string}
     */
    public function token(Request $request): array
    {
        $existing = $request->cookie(self::COOKIE);
        if ($existing !== null && preg_match('/^[0-9a-f]{64}$/', $existing)) {
            return ['token' => $existing, 'setCookie' => null];
        }
        $token = bin2hex(random_bytes(32));

        return [
            'token' => $token,
            'setCookie' => self::COOKIE . '=' . $token . '; Path=/; HttpOnly; SameSite=Lax' . ($this->secure ? '; Secure' : ''),
        ];
    }

    public function verify(Request $request): bool
    {
        $cookie = $request->cookie(self::COOKIE);
        $field = $request->post(self::FIELD);

        return is_string($cookie) && is_string($field) && $cookie !== '' && hash_equals($cookie, $field);
    }
}
