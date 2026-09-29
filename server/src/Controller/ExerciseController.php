<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Data\ExerciseRepository;
use Training\Exercise\Catalog;
use Training\Http\Request;
use Training\Http\Response;

/**
 * Übungskatalog auf der Webseite (AP-16, docs/konzept/uebungskatalog.md 6.1/6.2): S10 Übung (/uebung?id=<slug>) und
 * S10a Liste (/uebungen). Nur lesen; Bearbeiten über den Chat (O-01). Ohne JavaScript; Videos als iframe.
 */
final class ExerciseController extends AppController
{
    /** Einbettung nur von diesen beiden Hosts (E-04, 6.1); gilt nur für S10. */
    public const FRAME_SRC = 'https://www.youtube-nocookie.com https://player.vimeo.com';

    public function show(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $slug = (string) $request->query('id');
        $repo = new ExerciseRepository($this->app->pdo(), $this->app->clock());
        $exercise = Catalog::isSlug($slug) ? $repo->bySlug($slug) : null;
        if ($exercise === null) {
            return Response::html(404, $this->app->view()->render('message', [
                'title' => 'Nicht gefunden',
                'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Übung nicht gefunden.', 'text' => 'Zurück zum Übungskatalog.'],
                'host' => $this->app->host(),
            ]));
        }

        $back = $this->back($request);
        $response = $this->page('exercise', $exercise['name'], $back['from'] !== null ? 'woche' : 'einstellungen', [
            'exercise' => $exercise,
            'children' => $repo->children($exercise['id']),
            'versions' => $repo->versions($exercise['id']),
            ...$back,
        ]);

        return $response->withHeaders(['Content-Security-Policy' => Response::HTML_HEADERS['Content-Security-Policy'] . '; frame-src ' . self::FRAME_SRC]);
    }

    public function list(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $query = self::textField($request->query('q'), 100);
        $category = $request->query('kategorie');
        $category = in_array($category, Catalog::CATEGORIES, true) ? $category : null;
        $archived = $request->query('archiv') === '1';
        $repo = new ExerciseRepository($this->app->pdo(), $this->app->clock());

        return $this->page('exercises', 'Übungskatalog', 'einstellungen', [
            'items' => $repo->listForPage($query, $category, $archived),
            'query' => $query, 'category' => $category, 'archived' => $archived,
            'backHref' => '/einstellungen', 'backLabel' => 'Zurück zu den Einstellungen',
        ]);
    }

    /**
     * Zurück zur aufrufenden Einheit (von=<Einheit>, bei S9 mit modus=start, damit der Fortschritt im Browser weiterläuft),
     * sonst zur Liste. Die Einheit wird nicht geladen; eine falsche ID führt auf deren 404-Seite.
     * @return array{backHref: string, backLabel: string, from: ?int, guided: bool}
     */
    private function back(Request $request): array
    {
        $from = self::intField($request->query('von'), 1, PHP_INT_MAX);
        $guided = $request->query('modus') === 'start';
        if ($from === null) {
            return ['backHref' => '/uebungen', 'backLabel' => 'Zurück zum Übungskatalog', 'from' => null, 'guided' => false];
        }

        return ['backHref' => '/einheit?id=' . $from . ($guided ? '&modus=start' : ''), 'backLabel' => 'Zurück zur Einheit', 'from' => $from, 'guided' => $guided];
    }
}
