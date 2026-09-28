<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\App;
use Training\Auth\Password;
use Training\Db;
use Training\Http\Request;
use Training\Http\Response;
use Training\Intervals\IntervalsClient;
use Training\Migration\Migrator;
use Training\OAuth\OAuthConfig;

/**
 * S8 Einstellungen (Abschnitt 10): Konto (Abmelden, Zeitzone, Passwort), Backup und Update (Anzeige; Funktionen
 * folgen mit AP-10), Verbindungen (Intervals.icu, freigegebene OAuth-Clients mit Widerruf, statisches Token).
 */
final class SettingsController extends AppController
{
    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $action = $request->method === 'POST' ? (string) $request->post('action') : (string) $request->query('bereich');
        if ($request->method === 'POST' && !$this->csrfOk($request)) {
            return Response::error(403, 'Ungültiges Formular.');
        }

        return match ($action) {
            'zeitzone' => $this->timezone($request),
            'passwort' => $this->password($request),
            'widerrufen' => $this->revoke($request),
            default => $this->overview($request),
        };
    }

    private function overview(Request $request, ?array $alert = null): Response
    {
        $pdo = $this->app->pdo();
        $config = $this->app->config();
        $session = $this->session;
        $stmt = $pdo->prepare('SELECT created_at FROM web_session WHERE token_hash = ?');
        $stmt->execute([$session->tokenHash]);
        $since = Db::time($stmt->fetchColumn() ?: null);

        $stmt = $pdo->prepare('SELECT c.client_id, c.client_name, c.redirect_uris_json, MIN(t.created_at) AS since, MAX(t.created_at) AS last_used, MAX(t.scope) AS scope
            FROM oauth_client c JOIN oauth_token t ON t.client_id = c.client_id
            WHERE t.revoked = 0 AND t.used_at IS NULL AND t.expires_at > ?
            GROUP BY c.client_id, c.client_name, c.redirect_uris_json ORDER BY last_used DESC');
        $stmt->execute([Db::ts($this->app->clock()->now())]);
        $clients = $stmt->fetchAll();

        $dbVersion = null;
        try {
            $dbVersion = (new Migrator($pdo, $this->app->migrationsDir()))->currentVersion();
        } catch (\Throwable) {
        }

        $notice = match ($request->query('ok')) {
            'zeitzone' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Zeitzone gespeichert.', 'text' => ''],
            'passwort' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Passwort geändert.', 'text' => 'Andere Geräte wurden abgemeldet.'],
            'widerrufen' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Freigabe widerrufen.', 'text' => 'Laufende Zugriffe enden spätestens nach einer Stunde.'],
            default => null,
        };

        return $this->page('settings', 'Einstellungen', 'einstellungen', [
            'alert' => $alert ?? $notice,
            'since' => $since,
            'tz' => $session->tz,
            'clients' => $clients,
            'schemaCode' => App::SCHEMA_VERSION,
            'schemaDb' => $dbVersion,
            'version' => App::VERSION,
            'intervals' => IntervalsClient::isConfigured($config) ? (string) $config->get('INTERVALS_ATHLETE_ID') : null,
            'staticToken' => OAuthConfig::fromConfig($config)->staticToken !== null,
            'mcpUrl' => OAuthConfig::fromConfig($config)->resource(),
        ]);
    }

    private function timezone(Request $request): Response
    {
        if ($request->method !== 'POST') {
            return $this->page('settings-timezone', 'Zeitzone', 'einstellungen', ['backHref' => '/einstellungen', 'tz' => $this->session->tz, 'timezones' => WebController::TIMEZONES]);
        }
        $tz = (string) $request->post('tz');
        if (!in_array($tz, WebController::TIMEZONES, true)) {
            return $this->page('settings-timezone', 'Zeitzone', 'einstellungen', [
                'tz' => $this->session->tz, 'timezones' => WebController::TIMEZONES,
                'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => 'Bitte eine Zeitzone aus der Liste wählen.'],
            ], 422);
        }
        $this->app->pdo()->prepare('UPDATE `user` SET tz = ? WHERE id = ?')->execute([$tz, $this->session->userId]);
        $this->audit()->write('web', 'user_timezone', 'user', $this->session->userId, ['tz' => $tz], 'Zeitzone ' . $tz);

        return Response::redirect('/einstellungen?ok=zeitzone');
    }

    private function password(Request $request): Response
    {
        if ($request->method !== 'POST') {
            return $this->page('settings-password', 'Passwort', 'einstellungen', ['backHref' => '/einstellungen', 'invalid' => []]);
        }
        $user = $this->app->users()->find($this->session->userId);
        $current = (string) $request->post('current');
        $new = (string) $request->post('password');
        $invalid = [];
        $message = null;
        if ($user === null || !password_verify($current, $user->passwordHash)) {
            $invalid['current'] = true;
            $message = 'Das aktuelle Passwort stimmt nicht.';
        } elseif (mb_strlen($new) < Password::MIN_LENGTH) {
            $invalid['password'] = true;
            $message = 'Das neue Passwort braucht mindestens 12 Zeichen.';
        } elseif (!hash_equals($new, (string) $request->post('password2'))) {
            $invalid['password2'] = true;
            $message = 'Die beiden neuen Passwörter stimmen nicht überein.';
        }
        if ($message !== null) {
            return $this->page('settings-password', 'Passwort', 'einstellungen', [
                'invalid' => $invalid,
                'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht geändert.', 'text' => $message],
            ], 422);
        }
        $pdo = $this->app->pdo();
        $this->app->users()->updatePasswordHash($this->session->userId, Password::hash($new));
        // Andere Web-Sessions beenden; die aktuelle bleibt angemeldet.
        $pdo->prepare('DELETE FROM web_session WHERE user_id = ? AND token_hash <> ?')->execute([$this->session->userId, $this->session->tokenHash]);
        $this->audit()->write('web', 'user_password', 'user', $this->session->userId, null, 'Passwort geändert, andere Sessions beendet');

        return Response::redirect('/einstellungen?ok=passwort');
    }

    private function revoke(Request $request): Response
    {
        $clientId = (string) $request->post('client_id');
        $stmt = $this->app->pdo()->prepare('UPDATE oauth_token SET revoked = 1 WHERE client_id = ?');
        $stmt->execute([$clientId]);
        $this->audit()->write('web', 'oauth_revoke', 'oauth_client', $clientId, null, 'Freigabe widerrufen (' . $stmt->rowCount() . ' Refresh-Tokens)');

        return Response::redirect('/einstellungen?ok=widerrufen');
    }
}
