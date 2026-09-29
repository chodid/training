<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\App;
use Training\Auth\Password;
use Training\Calendar\CalDavClient;
use Training\Dates;
use Training\Data\SettingsRepository;
use Training\Db;
use Training\Http\Request;
use Training\Http\Response;
use Training\Intervals\IntervalsClient;
use Training\Migration\Migrator;
use Training\OAuth\OAuthConfig;

/**
 * S8 Einstellungen (Abschnitt 10): Konto (Abmelden, Zeitzone, Passwort), Training (Timer-Signale der geführten
 * Einheit, AP-14), Backup und Update, Verbindungen (Intervals.icu, freigegebene OAuth-Clients mit Widerruf,
 * statisches Token).
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

        if ($request->method === 'POST' && in_array($action, ['zeitzone', 'passwort', 'erinnerung', 'checkin', 'timer', 'blockreview'], true) && ($locked = $this->lockedResponse())) {
            return $locked;
        }

        return match ($action) {
            'backup' => $this->download(),
            'export' => $this->export(),
            'migrieren' => $this->migrate(),
            'zeitzone' => $this->timezone($request),
            'passwort' => $this->password($request),
            'widerrufen' => $this->revoke($request),
            'passkey_loeschen' => $this->deletePasskey($request),
            'kalender' => $this->calendarSync(),
            'erinnerung' => $this->reminder($request),
            'checkin' => $this->checkinSettings($request),
            'timer' => $this->timerSettings($request),
            'blockreview' => $this->blockReviewSettings($request),
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
            'passkey' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Passkey angelegt.', 'text' => 'Beim nächsten Login „Mit Passkey anmelden“ wählen.'],
            'passkey_geloescht' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Passkey entfernt.', 'text' => ''],
            'passwort' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Passwort geändert.', 'text' => 'Andere Geräte wurden abgemeldet.'],
            'widerrufen' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Freigabe widerrufen.', 'text' => 'Laufende Zugriffe enden spätestens nach einer Stunde.'],
            'checkin' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Check-in-Einstellung gespeichert.', 'text' => ''],
            'timer' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Timer-Signale gespeichert.', 'text' => 'Gilt ab der nächsten geführten Einheit.'],
            'blockreview' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Blockbilanz und Zielklärung gespeichert.', 'text' => $request->query('n') !== null ? sprintf('%d Blocktermine im Kalender aktualisiert.', (int) $request->query('n')) : ''],
            'erinnerung' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Erinnerung gespeichert.', 'text' => sprintf('%d Termine im Kalender aktualisiert.', (int) $request->query('n'))],
            'kalender' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Kalender abgeglichen.', 'text' => sprintf('%d Termine übertragen, %d entfernt.', (int) $request->query('n'), (int) $request->query('d'))],
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
            'mail' => $this->app->mailBackup()->state() + ['to' => $config->get('BACKUP_MAIL_TO'), 'cron' => $config->get('CRON_SECRET') !== null, 'interval' => (int) $config->get('BACKUP_MAIL_INTERVAL_DAYS', '7')],
            'preMigration' => $this->preMigration(),
            'passkeyList' => $this->passkeyList(),
            'profile' => $this->profileSummary(),
            'catalog' => $this->catalogSummary(),
            'handBis' => (new SettingsRepository($this->app->pdo(), $this->app->clock()))->handRechtsBis(),
            'timerTon' => (new SettingsRepository($this->app->pdo(), $this->app->clock()))->timerTon(),
            'blockReview' => $this->blockReviewValues(),
            'calendar' => [
                'host' => CalDavClient::isConfigured($config) ? (string) parse_url((string) $config->get('CALDAV_URL'), PHP_URL_HOST) : null,
                'https' => str_starts_with(strtolower((string) $config->get('CALDAV_URL')), 'https://'),
                'reminder' => (new SettingsRepository($this->app->pdo(), $this->app->clock()))->calendarReminder(),
            ] + $this->app->calendar()->state(),
            'mirror' => $this->mirrorStats() + (new CronController($this->app))->syncState(),
        ]);
    }

    private function download(): Response
    {
        try {
            $backup = $this->app->backups()->create('download');
        } catch (\Throwable $e) {
            error_log('[training] Backup-Download: ' . $e->getMessage());

            return $this->overview(new Request('GET', '/einstellungen'), ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Backup fehlgeschlagen.', 'text' => $e->getMessage()]);
        }
        $this->audit()->write('web', 'backup_download', 'backup', null, null, $backup['name'] . ' (' . strlen($backup['data']) . ' Byte)');

        return new Response(200, $backup['data'], [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $backup['name'] . '"',
            'Content-Length' => (string) strlen($backup['data']),
            'Cache-Control' => 'no-store',
        ]);
    }

    private function export(): Response
    {
        $export = (new \Training\Backup\JsonExporter($this->app->pdo(), $this->app->clock(), $this->app->migrationsDir()))->export();
        $this->audit()->write('web', 'json_export', 'export', null, null, $export['name'] . ' (' . strlen($export['data']) . ' Byte)');

        return new Response(200, $export['data'], [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $export['name'] . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function migrate(): Response
    {
        try {
            $result = $this->app->updates()->migrate();
        } catch (\Throwable $e) {
            error_log('[training] Migration (Einstellungen): ' . $e->getMessage());

            return $this->overview(new Request('GET', '/einstellungen'), ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Migration nicht ausgeführt.', 'text' => $e->getMessage()]);
        }
        $this->audit()->write('web', 'migrate', 'schema_version', $result['after'], $result, sprintf('Migration %d → %d, Backup %s', $result['before'], $result['after'], $result['backup'] ?? '–'));

        return $this->overview(new Request('GET', '/einstellungen'), $result['applied'] === []
            ? ['type' => 'info', 'icon' => 'info-circle', 'title' => 'Keine Migration nötig.', 'text' => 'Datenbank ist aktuell.']
            : ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Migration ausgeführt.', 'text' => sprintf('Schemastand %d → %d. Backup vorher: %s', $result['before'], $result['after'], $result['backup'] ?? '–')]);
    }

    /** Spiegel-Kennzahlen; bei veraltetem Schema (Tabellen fehlen noch) leer, damit die Seite zum Migrieren erreichbar bleibt. @return array<string, mixed> */
    private function mirrorStats(): array
    {
        try {
            return (new \Training\Intervals\Mirror(null, $this->app->pdo(), $this->app->clock()))->stats();
        } catch (\PDOException) {
            return ['aktivitaeten' => 0, 'wellness_tage' => 0, 'erste' => null, 'letzte' => null];
        }
    }

    /** Kalender abgleichen (AP-11): 7 Tage zurück bis 8 Wochen voraus. */
    private function calendarSync(): Response
    {
        $calendar = $this->app->calendar();
        if (!$calendar->enabled()) {
            return Response::redirect('/einstellungen');
        }
        if ($locked = $this->lockedResponse()) {
            return $locked;
        }
        $today = $this->today();
        $result = $calendar->syncRange(Dates::addDays($today, -7), Dates::addDays($today, 56), 'web');
        if ($result['fehler'] !== []) {
            return $this->overview(new Request('GET', '/einstellungen'), [
                'type' => 'error', 'icon' => 'alert-circle', 'title' => 'Kalender-Abgleich fehlgeschlagen.', 'text' => $result['fehler'][0],
            ]);
        }
        $this->audit()->write('web', 'calendar_sync', 'session', null, $result, sprintf('Kalender abgeglichen: %d übertragen, %d entfernt', $result['uebertragen'], $result['geloescht']));

        return Response::redirect('/einstellungen?ok=kalender&n=' . $result['uebertragen'] . '&d=' . $result['geloescht']);
    }

    /** Morgen-Check-in (AP-12, E-08): „Hand rechts“ abfragen bis einschließlich Datum. */
    private function checkinSettings(Request $request): Response
    {
        $settings = new SettingsRepository($this->app->pdo(), $this->app->clock());
        if ($request->method !== 'POST') {
            return $this->page('settings-checkin', 'Check-in', 'einstellungen', ['backHref' => '/einstellungen', 'handBis' => $settings->handRechtsBis()]);
        }
        $date = (string) $request->post('hand_bis');
        if (!Dates::isDate($date)) {
            return $this->page('settings-checkin', 'Check-in', 'einstellungen', [
                'backHref' => '/einstellungen', 'handBis' => $settings->handRechtsBis(),
                'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => 'Bitte ein Datum wählen.'],
            ], 422);
        }
        $settings->set(SettingsRepository::CHECKIN_HAND_BIS, $date);
        $this->audit()->write('web', 'setting_update', 'app_setting', SettingsRepository::CHECKIN_HAND_BIS, ['value' => $date], 'Check-in: Hand rechts abfragen bis ' . $date);

        return Response::redirect('/einstellungen?ok=checkin');
    }

    /** Timer-Signale im geführten Modus (AP-14, E-18): Ton und Vibration an oder aus als Vorgabe für S9. */
    private function timerSettings(Request $request): Response
    {
        if ($request->method !== 'POST') {
            return Response::redirect('/einstellungen');
        }
        $value = (string) $request->post('timer_ton');
        if (!in_array($value, ['an', 'aus'], true)) {
            return $this->overview($request, ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => 'Bitte „An“ oder „Aus“ wählen.']);
        }
        (new SettingsRepository($this->app->pdo(), $this->app->clock()))->set(SettingsRepository::TIMER_TON, $value);
        $this->audit()->write('web', 'setting_update', 'app_setting', SettingsRepository::TIMER_TON, ['value' => $value], 'Timer-Signale im geführten Modus: ' . $value);

        return Response::redirect('/einstellungen?ok=timer');
    }

    /** @return array{overlay: bool, bilanz: int, zielklaerung: int, beginn: string, dauer_min: int, erinnerung_h: int} Werte der Unterseite Blockbilanz/Zielklärung (AP-15) */
    private function blockReviewValues(): array
    {
        $settings = new SettingsRepository($this->app->pdo(), $this->app->clock());

        return ['overlay' => $settings->reviewOverlay(), 'bilanz' => $settings->bilanzVorlauf(), 'zielklaerung' => $settings->zielklaerungVorlauf()] + $settings->kalenderBlock();
    }

    /**
     * Blockbilanz und Zielklärung (AP-15, 6.3, E-22): Overlay an/aus, Vorlauftage (wirken sofort auf Fälligkeit und
     * Overlay), Beginn/Dauer/Erinnerung des Blocktermins (überträgt die Blocktermine neu).
     */
    private function blockReviewSettings(Request $request): Response
    {
        $values = $this->blockReviewValues();
        if ($request->method !== 'POST') {
            return $this->page('settings-blockreview', 'Blockbilanz und Zielklärung', 'einstellungen', ['backHref' => '/einstellungen', 'values' => $values]);
        }
        $new = [
            'overlay' => $request->post('overlay') === 'an',
            'bilanz' => self::intField($request->post('bilanz_vorlauf'), 0, 28),
            'zielklaerung' => self::intField($request->post('zielklaerung_vorlauf'), 0, 42),
            'beginn' => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $request->post('beginn')) ? (string) $request->post('beginn') : null,
            'dauer_min' => self::intField($request->post('dauer'), 30, 480),
            'erinnerung_h' => self::intField($request->post('erinnerung_h'), 0, 168),
        ];
        if (in_array(null, $new, true)) {
            return $this->page('settings-blockreview', 'Blockbilanz und Zielklärung', 'einstellungen', [
                'backHref' => '/einstellungen', 'values' => array_combine(array_keys($new), array_map(static fn ($v, $old) => $v ?? $old, $new, $values)),
                'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => 'Bitte die Bereiche beachten: Vorlauf Bilanz 0–28, Zielklärung 0–42 Tage, Beginn HH:MM, Dauer 30–480 Minuten, Erinnerung 0–168 Stunden.'],
            ], 422);
        }
        $settings = new SettingsRepository($this->app->pdo(), $this->app->clock());
        $settings->set(SettingsRepository::REVIEW_OVERLAY, $new['overlay'] ? 'an' : 'aus');
        $settings->set(SettingsRepository::BILANZ_VORLAUF, (string) $new['bilanz']);
        $settings->set(SettingsRepository::ZIELKLAERUNG_VORLAUF, (string) $new['zielklaerung']);
        $settings->set(SettingsRepository::KALENDER_BLOCK_BEGINN, $new['beginn']);
        $settings->set(SettingsRepository::KALENDER_BLOCK_DAUER, (string) $new['dauer_min']);
        $settings->set(SettingsRepository::KALENDER_BLOCK_ERINNERUNG, (string) $new['erinnerung_h']);
        $this->audit()->write('web', 'setting_update', 'app_setting', 'blockreview', $new, sprintf('Blockbilanz/Zielklärung: Overlay %s, Vorlauf %d/%d Tage, Termin %s, %d min, Erinnerung %d h vorher',
            $new['overlay'] ? 'an' : 'aus', $new['bilanz'], $new['zielklaerung'], $new['beginn'], $new['dauer_min'], $new['erinnerung_h']));

        // Blocktermine neu übertragen, wenn sich Beginn, Dauer oder Erinnerung geändert haben (E-22)
        $calendar = $this->app->calendar();
        $changed = [$new['beginn'], $new['dauer_min'], $new['erinnerung_h']] !== [$values['beginn'], $values['dauer_min'], $values['erinnerung_h']];
        if (!$changed || !$calendar->enabled()) {
            return Response::redirect('/einstellungen?ok=blockreview');
        }
        $error = $calendar->pushBlocks([], 'web');
        if ($error !== null) {
            return $this->overview(new Request('GET', '/einstellungen'), [
                'type' => 'warning', 'icon' => 'alert-triangle', 'title' => 'Gespeichert, Kalender nicht aktualisiert.',
                'text' => $error . ' Der stündliche Abgleich versucht es erneut.',
            ]);
        }

        return Response::redirect('/einstellungen?ok=blockreview&n=' . $this->app->pdo()->query("SELECT COUNT(*) FROM training_block WHERE status IN ('geplant', 'aktiv')")->fetchColumn());
    }

    /** Kalender-Erinnerung (D-52): Uhrzeit am Trainingstag oder aus; danach Termine im Zeitraum neu übertragen. */
    private function reminder(Request $request): Response
    {
        $settings = new SettingsRepository($this->app->pdo(), $this->app->clock());
        $current = $settings->calendarReminder();
        if ($request->method !== 'POST') {
            return $this->page('settings-reminder', 'Erinnerung', 'einstellungen', ['backHref' => '/einstellungen', 'reminder' => $current ?? SettingsRepository::CALENDAR_REMINDER_DEFAULT, 'off' => $current === null]);
        }
        $off = $request->post('aus') === '1';
        $time = (string) $request->post('uhrzeit');
        if (!$off && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            return $this->page('settings-reminder', 'Erinnerung', 'einstellungen', [
                'backHref' => '/einstellungen', 'reminder' => $current ?? SettingsRepository::CALENDAR_REMINDER_DEFAULT, 'off' => false,
                'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => 'Bitte eine Uhrzeit im Format HH:MM wählen.'],
            ], 422);
        }
        $value = $off ? 'aus' : $time;
        $settings->set(SettingsRepository::CALENDAR_REMINDER, $value);
        $this->audit()->write('web', 'setting_update', 'app_setting', SettingsRepository::CALENDAR_REMINDER, ['value' => $value], 'Kalender-Erinnerung: ' . ($off ? 'aus' : $value . ' Uhr'));

        $calendar = $this->app->calendar();
        if (!$calendar->enabled()) {
            return Response::redirect('/einstellungen?ok=erinnerung&n=0');
        }
        $today = $this->today();
        $result = $calendar->syncRange(Dates::addDays($today, -7), Dates::addDays($today, 56), 'web');
        if ($result['fehler'] !== []) {
            return $this->overview(new Request('GET', '/einstellungen'), [
                'type' => 'warning', 'icon' => 'alert-triangle', 'title' => 'Erinnerung gespeichert, Kalender nicht aktualisiert.',
                'text' => $result['fehler'][0] . ' Der stündliche Abgleich versucht es erneut.',
            ]);
        }

        return Response::redirect('/einstellungen?ok=erinnerung&n=' . $result['uebertragen']);
    }

    /** Athletenprofil (D-48): ausgefüllte Abschnitte und letzte Änderung; tolerant bei veraltetem Schema. @return array{filled: int, total: int, last: ?string} */
    private function profileSummary(): array
    {
        $total = count(\Training\Data\ProfileRepository::SECTIONS);
        try {
            $current = (new \Training\Data\ProfileRepository($this->app->pdo(), $this->app->clock()))->current();
        } catch (\PDOException) {
            return ['filled' => 0, 'total' => $total, 'last' => null];
        }
        $rows = array_filter($current);
        $last = $rows === [] ? null : max(array_map(static fn (array $r): string => (string) $r['created_at'], $rows));

        return ['filled' => count(array_filter($rows, static fn (array $r): bool => $r['content'] !== '')), 'total' => $total, 'last' => $last];
    }

    /** @return list<array<string, mixed>> */
    private function passkeyList(): array
    {
        try {
            return (new PasskeyController($this->app))->passkeys()->list($this->session->userId);
        } catch (\PDOException) {
            return [];
        }
    }

    private function deletePasskey(Request $request): Response
    {
        $id = (string) $request->post('passkey_id');
        if ((new PasskeyController($this->app))->passkeys()->delete($this->session->userId, $id)) {
            $this->audit()->write('web', 'passkey_delete', 'webauthn_credential', mb_substr($id, 0, 64), null, 'Passkey entfernt');
        }

        return Response::redirect('/einstellungen?ok=passkey_geloescht');
    }

    /** @return array{count: int, last: ?string} */
    private function preMigration(): array
    {
        $files = glob($this->app->backupDir() . '/training-backup-*-vor-migration.sql.gz.enc') ?: [];
        rsort($files);

        return ['count' => count($files), 'last' => $files === [] ? null : basename($files[0])];
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

    /**
     * Übungskatalog in S8 (AP-16): Anzahl ohne archivierte, Übungen mit defekten Links (Teil D). Null vor der Migration
     * (Tabelle fehlt), damit S8 als Ort der Migration immer lädt.
     * @return ?array{count: int, links_pruefen: int}
     */
    private function catalogSummary(): ?array
    {
        $repo = new \Training\Data\ExerciseRepository($this->app->pdo(), $this->app->clock());
        try {
            return ['count' => count($repo->listCompact()), 'links_pruefen' => $repo->countLinksToCheck()];
        } catch (\PDOException) {
            return null;
        }
    }
}
