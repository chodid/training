<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Data\ReviewRepository;
use Training\Http\Request;
use Training\Http\Response;
use Training\Review\Faelligkeit;

/**
 * S11 Blockseite (AP-15, docs/konzept/blockbilanz.md T5, E-18): Block mit Fälligkeiten, Zielklärung, Revisionen,
 * Bilanz und Fassungen; Liste der übrigen Blöcke. Nur lesen – Bilanz, Zielklärung und Revision entstehen im Chat (O-01).
 * Ohne id der aktive Block, sonst der zuletzt begonnene.
 */
final class BlockController extends AppController
{
    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $pdo = $this->app->pdo();
        $id = self::intField($request->query('id'), 1, PHP_INT_MAX);
        if ($request->query('id') !== null && $id === null) {
            return $this->notFound();
        }
        $blocks = $pdo->query("SELECT * FROM training_block ORDER BY start_date DESC, id DESC")->fetchAll();
        $block = null;
        foreach ($blocks as $b) {
            if ($id !== null ? (int) $b['id'] === $id : $b['status'] === 'aktiv') {
                $block = $b;
                break;
            }
        }
        if ($block === null && $id === null) {
            $block = $blocks[0] ?? null;
        }
        if ($block === null && $id !== null) {
            return $this->notFound();
        }
        $today = $this->today();
        $faellig = [];
        $reviews = ['zielklaerung' => null, 'bilanz' => null, 'revisionen' => [], 'fassungen' => []];
        try {
            $all = Faelligkeit::load($pdo, $this->app->clock(), $today);
            $faellig = $block !== null ? Faelligkeit::forBlock($all, (int) $block['id']) : $all;
            if ($block !== null) {
                $reviews = $this->reviews((int) $block['id']);
            }
        } catch (\PDOException $e) {
            error_log('[training] Blockseite: ' . $e->getMessage()); // Schema älter als 24: nur Block
        }

        return $this->page('block', $block !== null ? 'Block ' . $block['name'] : 'Blöcke', 'verlauf', [
            'backHref' => '/verlauf#bloecke',
            'backLabel' => 'Zum Verlauf',
            'block' => $block,
            'today' => $today,
            'rest' => $block !== null ? WeekController::restText($today, (string) $block['end_date']) : null,
            'faellig' => $faellig,
            'others' => array_values(array_filter($blocks, static fn (array $b): bool => $block === null || (int) $b['id'] !== (int) $block['id'])),
            ...$reviews,
        ]);
    }

    /**
     * Gültige Fassungen (oder nur Entwurf) je Art und alle Fassungen je Datensatz.
     * @return array{zielklaerung: ?array<string, mixed>, bilanz: ?array<string, mixed>, revisionen: list<array<string, mixed>>, fassungen: array<string, list<array<string, mixed>>>}
     */
    private function reviews(int $blockId): array
    {
        $repo = new ReviewRepository($this->app->pdo(), $this->app->clock());
        $out = ['zielklaerung' => null, 'bilanz' => null, 'revisionen' => [], 'fassungen' => []];
        foreach ($repo->current($blockId) as $r) {
            if ($r['kind'] === 'revision') {
                $out['revisionen'][] = $r;
            } else {
                $out[$r['kind']] = $r;
            }
        }
        usort($out['revisionen'], static fn (array $a, array $b): int => [$b['review_date'], $b['sequence']] <=> [$a['review_date'], $a['sequence']]);
        foreach ($repo->versions($blockId) as $v) {
            $out['fassungen'][$v['kind'] . '-' . $v['sequence']][] = $v;
        }

        return $out;
    }

    private function notFound(): Response
    {
        return Response::html(404, $this->app->view()->render('message', [
            'title' => 'Nicht gefunden',
            'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Block nicht gefunden.', 'text' => 'Zurück zum Verlauf.'],
            'host' => $this->app->host(),
        ]));
    }
}
