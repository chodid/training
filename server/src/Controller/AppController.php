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
