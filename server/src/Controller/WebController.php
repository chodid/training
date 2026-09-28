<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\App;
use Training\Auth\FormCsrf;
use Training\Auth\LoginResult;
use Training\Auth\LoginService;
use Training\Auth\LoginThrottle;
use Training\Auth\Password;
use Training\Http\Request;
use Training\Http\Response;

/**
 * Webseiten ohne Trainingsinhalt: S0 Setup (D-34), S1 Login (D-33), Abmelden und Startseite.
 * Die eigentliche App (S2–S5, S8) folgt mit AP-04.
 */
final class WebController
{
    public const TIMEZONES = ['Europe/Berlin', 'Europe/Vienna', 'Europe/Zurich', 'UTC'];

    public function __construct(private readonly App $app)
    {
    }

    public function home(Request $request): Response
    {
        if ($this->app->sessions()->current($request) === null) {
            return Response::redirect($this->app->users()->exists() ? '/login' : '/setup');
        }

        return Response::redirect('/woche');
    }

    // ---------- S0 Setup (D-34) ----------

    public function setup(Request $request): Response
    {
        if ($this->app->users()->exists()) {
            return Response::error(404, 'Nicht gefunden.');
        }
        if ($request->method !== 'POST') {
            return $this->setupForm($request, null, [], '', 'Europe/Berlin');
        }

        $login = trim((string) $request->post('login'));
        $tz = (string) $request->post('tz');
        $password = (string) $request->post('password');
        $invalid = [];
        $message = null;

        if (!$this->csrf()->verify($request)) {
            $message = 'Das Formular ist abgelaufen. Bitte die Seite neu laden und erneut absenden.';
        } elseif (!hash_equals($this->app->config()->require('MIGRATION_SECRET'), (string) $request->post('secret'))) {
            $invalid['secret'] = true;
            $message = 'Das Deploy-Secret stimmt nicht. Bitte den Wert MIGRATION_SECRET aus der .env eintragen.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{1,64}$/', $login)) {
            $invalid['login'] = true;
            $message = 'Der Anmeldename darf nur Buchstaben, Ziffern, Punkt, Binde- und Unterstrich enthalten.';
        } elseif (mb_strlen($password) < Password::MIN_LENGTH) {
            $invalid['password'] = true;
            $message = 'Das Passwort braucht mindestens 12 Zeichen.';
        } elseif (!hash_equals($password, (string) $request->post('password2'))) {
            $invalid['password2'] = true;
            $message = 'Die beiden Passwörter stimmen nicht überein.';
        } elseif (!in_array($tz, self::TIMEZONES, true)) {
            $invalid['tz'] = true;
            $message = 'Bitte eine Zeitzone aus der Liste wählen.';
        }
        if ($message !== null) {
            return $this->setupForm($request, $message, $invalid, $login, $tz, 422);
        }

        $userId = $this->app->users()->createFirst($login, $password, $tz);
        if ($userId === null) {
            return Response::error(404, 'Nicht gefunden.');
        }

        return Response::redirect('/')->withCookie($this->app->sessions()->create($userId));
    }

    /** @param array<string, bool> $invalid */
    private function setupForm(Request $request, ?string $message, array $invalid, string $login, string $tz, int $status = 200): Response
    {
        $csrf = $this->csrf()->token($request);
        $response = $this->page('Einrichtung', 'setup', [
            'csrf' => $csrf['token'],
            'alert' => $message === null ? null : ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Einrichtung nicht möglich.', 'text' => $message],
            'invalid' => $invalid,
            'login' => $login,
            'tz' => $tz,
            'timezones' => self::TIMEZONES,
        ], $status);

        return $csrf['setCookie'] !== null ? $response->withCookie($csrf['setCookie']) : $response;
    }

    // ---------- S1 Login (D-33) ----------

    public function login(Request $request): Response
    {
        $users = $this->app->users();
        if (!$users->exists()) {
            return Response::redirect('/setup');
        }
        $next = self::safeNext($request->method === 'POST' ? $request->post('next') : $request->query('next'));
        if ($this->app->sessions()->current($request) !== null && $request->method !== 'POST') {
            return Response::redirect($next !== '' ? $next : '/');
        }

        $service = new LoginService($users, $this->app->clock(), $this->app->loginThrottle());
        if ($request->method !== 'POST') {
            $until = $service->lockedUntil();

            return $this->loginForm($request, $next, '', $until !== null ? $this->lockedAlert($until) : null, $until !== null, false);
        }

        $login = trim((string) $request->post('login'));
        if (!$this->csrf()->verify($request)) {
            return $this->loginForm($request, $next, $login, [
                'type' => 'error', 'icon' => 'alert-circle', 'title' => 'Formular abgelaufen.',
                'text' => 'Bitte erneut anmelden.',
            ], false, false, 422);
        }

        $result = $service->attempt($login, (string) $request->post('password'));
        if ($result->status === LoginResult::OK && $result->userId !== null) {
            return Response::redirect($next !== '' ? $next : '/')->withCookie($this->app->sessions()->create($result->userId));
        }
        if ($result->status === LoginResult::LOCKED && $result->lockedUntil !== null) {
            return $this->loginForm($request, $next, $login, $this->lockedAlert($result->lockedUntil), true, false, 429);
        }

        $remaining = $result->remaining;
        $text = 'Bitte Anmeldename und Passwort prüfen.';
        if ($remaining < LoginThrottle::MAX_FAILURES) {
            $text .= $remaining === 1
                ? ' Noch 1 Versuch, dann wird das Konto vorübergehend gesperrt.'
                : ' Noch ' . $remaining . ' Versuche, dann wird das Konto vorübergehend gesperrt.';
        }

        return $this->loginForm($request, $next, $login, [
            'type' => 'error', 'icon' => 'alert-circle', 'title' => 'Anmeldung fehlgeschlagen.', 'text' => $text,
        ], false, true, 401);
    }

    public function logout(Request $request): Response
    {
        $session = $this->app->sessions()->current($request);
        if ($session === null) {
            return Response::redirect('/login');
        }
        if (!$session->verifyCsrf($request->post('csrf'))) {
            return Response::error(403, 'Ungültiges Formular.');
        }

        return Response::redirect('/login')->withCookie($this->app->sessions()->destroy($session));
    }

    /** @param ?array<string, string> $alert */
    private function loginForm(Request $request, string $next, string $login, ?array $alert, bool $locked, bool $invalid, int $status = 200): Response
    {
        $csrf = $this->csrf()->token($request);
        $response = $this->page('Anmelden', 'login', [
            'csrf' => $csrf['token'],
            'alert' => $alert,
            'locked' => $locked,
            'invalid' => $invalid,
            'login' => $login,
            'next' => $next,
            'passkeys' => (new PasskeyController($this->app))->passkeys()->count() > 0,
        ], $status);

        return $csrf['setCookie'] !== null ? $response->withCookie($csrf['setCookie']) : $response;
    }

    /** @return array<string, string> */
    private function lockedAlert(int $until): array
    {
        $user = $this->app->users()->first();
        $tz = new \DateTimeZone($user !== null ? $user->tz : 'Europe/Berlin');
        $time = (new \DateTimeImmutable('@' . $until))->setTimezone($tz);
        $minutes = max(1, (int) ceil(($until - $this->app->clock()->now()) / 60));
        $when = $minutes >= 24 * 60 - 1 || $time->format('Y-m-d') !== (new \DateTimeImmutable('@' . $this->app->clock()->now()))->setTimezone($tz)->format('Y-m-d')
            ? $time->format('d.m. H:i') . ' Uhr'
            : $time->format('H:i') . ' Uhr';

        return [
            'type' => 'warning',
            'icon' => 'lock',
            'title' => 'Konto vorübergehend gesperrt.',
            'text' => 'Zu viele Fehlversuche. Nächster Versuch möglich um ' . $when . ' (in ' . $minutes . ' Minute' . ($minutes === 1 ? '' : 'n') . '). Jeder weitere Fehlversuch verdoppelt die Sperrdauer.',
        ];
    }

    /** Nur lokale Pfade als Rücksprungziel (kein Open Redirect). */
    public static function safeNext(?string $next): string
    {
        if ($next === null || $next === '' || $next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '\\')
            || preg_match('/[\x00-\x1F]/', $next)) {
            return '';
        }

        return $next;
    }

    private function csrf(): FormCsrf
    {
        return new FormCsrf($this->app->secureCookies());
    }

    /** @param array<string, mixed> $vars */
    private function page(string $title, string $template, array $vars, int $status = 200): Response
    {
        return Response::html($status, $this->app->view()->render($template, [
            'title' => $title,
            'host' => $this->app->host(),
            ...$vars,
        ]));
    }
}
