<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Data\ProfileRepository;
use Training\Http\Request;
use Training\Http\Response;

/**
 * /profil – Athletenprofil lesen und korrigieren (D-48). Claude pflegt es über update_athlete_profile; hier gleiche
 * Abschnitte, jede Änderung als neue Fassung. Schutz gegen Überschreiben: das Formular trägt die gelesene Fassung mit.
 */
final class ProfileController extends AppController
{
    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $section = $request->method === 'POST' ? (string) $request->post('abschnitt') : (string) $request->query('abschnitt');
        if ($request->method === 'POST') {
            if (!$this->csrfOk($request)) {
                return Response::error(403, 'Ungültiges Formular.');
            }
            if ($locked = $this->lockedResponse()) {
                return $locked;
            }

            return $this->save($request, $section);
        }
        if ($section !== '' && !isset(ProfileRepository::SECTIONS[$section])) {
            return Response::error(404, 'Nicht gefunden.');
        }
        if ($section !== '' && $request->query('verlauf') !== null) {
            return $this->page('profile-history', ProfileRepository::SECTIONS[$section][0] . ' – Fassungen', 'einstellungen', [
                'backHref' => '/profil#' . $section, 'section' => $section, 'label' => ProfileRepository::SECTIONS[$section][0],
                'versions' => $this->repo()->history($section), 'tz' => $this->session->tz,
            ]);
        }
        if ($section !== '') {
            $current = $this->repo()->current()[$section];

            return $this->form($section, (string) ($current['content'] ?? ''), '', $current['id'] ?? null);
        }

        $ok = $request->query('ok');

        return $this->page('profile', 'Athletenprofil', 'einstellungen', [
            'backHref' => '/einstellungen', 'sections' => ProfileRepository::SECTIONS, 'current' => $this->repo()->current(), 'tz' => $this->session->tz,
            'alert' => match ($ok) {
                'gespeichert' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Gespeichert.', 'text' => 'Neue Fassung angelegt; die vorige bleibt unter „Frühere Fassungen“ erhalten.'],
                'unveraendert' => ['type' => 'info', 'icon' => 'info-circle', 'title' => 'Nichts geändert.', 'text' => 'Der Text war unverändert, es wurde keine Fassung angelegt.'],
                default => null,
            },
        ]);
    }

    private function save(Request $request, string $section): Response
    {
        if (!isset(ProfileRepository::SECTIONS[$section])) {
            return Response::error(404, 'Nicht gefunden.');
        }
        $content = (string) $request->post('inhalt');
        $reason = (string) $request->post('grund');
        $base = self::intField($request->post('basis'), 1, PHP_INT_MAX);
        $current = $this->repo()->current()[$section];
        if (($current['id'] ?? null) !== $base) {
            return $this->form($section, $content, $reason, $current['id'] ?? null, [
                'type' => 'warning', 'icon' => 'alert-triangle', 'title' => 'Inzwischen geändert.',
                'text' => 'Der Abschnitt wurde seit dem Öffnen geändert' . ($current !== null ? ' (' . ($current['created_by'] === 'mcp' ? 'von Claude' : 'auf der Webseite') . ')' : '')
                    . '. Dein Text steht unten, der aktuelle Stand unter „Frühere Fassungen“. Bitte abgleichen und erneut speichern.',
            ], 409);
        }
        try {
            $saved = $this->repo()->save($section, $content, 'web', $reason);
        } catch (\InvalidArgumentException $e) {
            return $this->form($section, $content, $reason, $base, ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => $e->getMessage()], 422);
        }
        if ($saved['unchanged']) {
            return Response::redirect('/profil?ok=unveraendert#' . $section);
        }
        $this->audit()->write('web', 'profile_update', 'athlete_profile', $saved['id'], ProfileRepository::normalize($content),
            'Profil „' . ProfileRepository::SECTIONS[$section][0] . '“ geändert' . (trim($reason) !== '' ? ': ' . trim($reason) : ''));

        return Response::redirect('/profil?ok=gespeichert#' . $section);
    }

    /** @param ?array<string, string> $alert */
    private function form(string $section, string $content, string $reason, ?int $base, ?array $alert = null, int $status = 200): Response
    {
        [$label, $hint] = ProfileRepository::SECTIONS[$section];

        return $this->page('profile-edit', $label . ' bearbeiten', 'einstellungen', [
            'backHref' => '/profil#' . $section, 'section' => $section, 'label' => $label, 'hint' => $hint,
            'content' => $content, 'reason' => $reason, 'base' => $base, 'alert' => $alert, 'max' => ProfileRepository::MAX_LENGTH,
        ], $status);
    }

    private function repo(): ProfileRepository
    {
        return new ProfileRepository($this->app->pdo(), $this->app->clock());
    }
}
