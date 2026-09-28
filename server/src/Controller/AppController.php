<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\App;
use Training\Auth\WebSession;
use Training\Data\AuditLog;
use Training\Data\FeedbackRepository;
use Training\Data\WeekRepository;
use Training\Dates;
use Training\Http\Request;
use Training\Http\Response;

/** Gemeinsame Hilfen der App-Seiten nach dem Login (S2–S5, S8; AP-04). */
abstract class AppController
{
    protected ?WebSession $session = null;

    public function __construct(protected readonly App $app)
    {
    }

    /** Liefert eine Weiterleitung zum Login, wenn niemand angemeldet ist. */
    protected function requireLogin(Request $request): ?Response
    {
        $this->session = $this->app->sessions()->current($request);
        if ($this->session === null && self::queued($request)) {
            return new Response(401, '');
        }
        if ($this->session === null) {
            $target = $request->uri !== '' ? $request->uri : $request->path;

            return Response::redirect('/login?next=' . rawurlencode($target));
        }

        return null;
    }

    /** Antwort bei Schreibsperre (D-20): Code- und Datenbankstand weichen ab, Schreiben erst nach der Migration. */
    protected function lockedResponse(): ?Response
    {
        if (!$this->app->writeLocked()) {
            return null;
        }

        return $this->page('locked', 'Update erforderlich', '', [], 503);
    }

    /** Gepufferte Offline-Sendung des Service Workers (D-45): Antwort als Status statt Seite bzw. Weiterleitung. */
    protected static function queued(Request $request): bool
    {
        return $request->header('X-Offline-Queue') === '1';
    }

    /** Nach erfolgreichem Speichern: Weiterleitung, bei gepufferter Sendung 204. */
    protected static function saved(Request $request, string $location): Response
    {
        return self::queued($request) ? new Response(204, '', ['Cache-Control' => 'no-store']) : Response::redirect($location);
    }

    /** Änderungsstand eines Eintrags für das Formular (D-45): kurzer Hash, leer wenn es den Eintrag noch nicht gibt. */
    protected static function stand(mixed $row): string
    {
        return $row === null ? '' : substr(hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)), 0, 16);
    }

    /** Wurde der Eintrag seit dem Laden des Formulars geändert? Formulare ohne Feld „stand“ werden nicht geprüft. */
    protected static function standConflict(Request $request, string $current): bool
    {
        $sent = $request->post('stand');

        return $sent !== null && !hash_equals($current, $sent);
    }

    /** Erfassungszeit einer offline gepufferten Eingabe (Unix-Sekunden), nur wenn plausibel (höchstens 14 Tage alt). */
    protected function offlineTime(Request $request): ?int
    {
        $at = self::intField($request->post('offline_erfasst'), 0, PHP_INT_MAX);
        $now = $this->app->clock()->now();

        return $at !== null && $at <= $now && $at >= $now - 14 * 86400 ? $at : null;
    }

    protected function csrfOk(Request $request): bool
    {
        return $this->session !== null && $this->session->verifyCsrf($request->post('csrf'));
    }

    protected function today(): string
    {
        return Dates::today($this->app->clock(), $this->session?->tz ?? 'Europe/Berlin');
    }

    protected function weeks(): WeekRepository
    {
        return new WeekRepository($this->app->pdo(), $this->app->clock());
    }

    protected function feedback(): FeedbackRepository
    {
        return new FeedbackRepository($this->app->pdo(), $this->app->clock());
    }

    protected function audit(): AuditLog
    {
        return new AuditLog($this->app->pdo(), $this->app->clock());
    }

    /** @param array<string, mixed> $vars */
    protected function page(string $template, string $title, string $nav, array $vars = [], int $status = 200): Response
    {
        $week = $this->weeks()->week(Dates::monday($this->today()));
        $navFoot = $week !== null ? 'Block ' . $week['block_no'] . ', Woche ' . $week['week_no'] : null;

        return Response::html($status, $this->app->view()->render($template, [
            'title' => $title,
            'nav' => $nav,
            'login' => $this->session?->login ?? '',
            'csrf' => $this->session?->csrfToken() ?? '',
            'navFoot' => $navFoot,
            'writeLocked' => $this->app->writeLocked(),
            'alert' => null,
            ...$vars,
        ], 'layout-app'));
    }

    /** Ganzzahl aus einem Formularfeld innerhalb der Grenzen, sonst null. */
    protected static function intField(?string $value, int $min, int $max): ?int
    {
        if ($value === null || $value === '' || !preg_match('/^-?\d+$/', trim($value))) {
            return null;
        }
        $i = (int) trim($value);

        return $i >= $min && $i <= $max ? $i : null;
    }

    protected static function textField(?string $value, int $max = 2000): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
