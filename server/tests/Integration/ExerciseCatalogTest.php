<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Data\ExerciseRepository;
use Training\Plan\ExerciseLink;
use Training\Tests\Support\AppTestCase;

/** AP-16 T1: Repository (Aliase, Fassungen, Suche) und Validator ExerciseLink (Testfälle V-01 bis V-08). */
final class ExerciseCatalogTest extends AppTestCase
{
    private ExerciseRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ExerciseRepository($this->pdo, $this->clock);
    }

    /** @param array<string, mixed> $over */
    private function add(string $slug, string $name, array $over = []): int
    {
        return $this->repo->create($over + [
            'slug' => $slug, 'name' => $name, 'aliases' => [], 'category' => 'kraft', 'pattern' => 'knie_dominant', 'equipment' => ['koerpergewicht'],
            'variant_of' => null, 'difficulty' => null, 'status' => 'aktiv', 'konfidenz' => 'mittel',
            'content' => ['kurz' => $name . ' kurz', 'ziel' => 'z', 'ausfuehrung' => ['a', 'b'], 'quellen' => ['Einschätzung'], 'links' => []],
        ], 'mcp');
    }

    /** @param array<string, mixed> $exercise */
    private static function kraft(array $exercise): array
    {
        return ['session' => 0, 'type' => 'kraft', 'plan' => ['exercises' => [$exercise + ['sets' => 3, 'reps' => '8']]]];
    }

    public function testExerciseLinkCases(): void
    {
        $this->add('kniebeuge', 'Kniebeuge', ['aliases' => ['Squat']]);
        $this->add('max-hang-20mm', 'Max Hang 20 mm', ['category' => 'hangboard', 'pattern' => 'unterarm_finger', 'equipment' => ['hangboard']]);
        $this->add('archiviert-1', 'Alte Übung', ['status' => 'archiviert']);
        $link = new ExerciseLink($this->repo);

        // V-01 vorhanden, Name gleich
        self::assertSame(['fehler' => [], 'warnungen' => []], $link->check([self::kraft(['exercise_id' => 'kniebeuge', 'name' => 'Kniebeuge'])]));
        // V-02 ohne ID → Warnung
        $r = $link->check([self::kraft(['name' => 'Kniebeuge'])]);
        self::assertSame([], $r['fehler']);
        self::assertSame(['session' => 0, 'position' => 0, 'name' => 'Kniebeuge', 'code' => 'ohne_katalog'], array_diff_key($r['warnungen'][0], ['hinweis' => 1]));
        // V-03 unbekannt → Fehler mit Session und Position
        $r = $link->check([['session' => 4, 'type' => 'haltung', 'plan' => ['exercises' => [['name' => 'A', 'exercise_id' => 'kniebeuge'], ['name' => 'B', 'exercise_id' => 'kniebeugen']]]]]);
        self::assertCount(1, $r['fehler']);
        self::assertSame(['session' => 4, 'position' => 1, 'exercise_id' => 'kniebeugen', 'code' => 'unbekannt'], array_diff_key($r['fehler'][0], ['hinweis' => 1]));
        self::assertSame('name_abweichend', $r['warnungen'][0]['code'], 'Position 0 „A“ ist nicht der Katalogname');
        // V-04 Name weicht ab, kein Alias → Warnung
        $this->add('bankdruecken', 'Bankdrücken', ['pattern' => 'druecken_horizontal']);
        $r = $link->check([self::kraft(['exercise_id' => 'bankdruecken', 'name' => 'Bench'])]);
        self::assertSame([], $r['fehler']);
        self::assertSame('name_abweichend', $r['warnungen'][0]['code']);
        // V-05 Name ist Alias (auch normalisiert) → ok
        self::assertSame(['fehler' => [], 'warnungen' => []], $link->check([self::kraft(['exercise_id' => 'kniebeuge', 'name' => 'Squat']), self::kraft(['exercise_id' => 'bankdruecken', 'name' => 'bankdruecken'])]));
        // V-06 Hangboard mit Katalog
        self::assertSame(['fehler' => [], 'warnungen' => []], $link->check([['session' => 1, 'type' => 'klettern', 'plan' => ['blocks' => [['kind' => 'hangboard', 'exercise_id' => 'max-hang-20mm'], ['kind' => 'bouldern_volumen']]]]]));
        // Hangboard ohne ID → Warnung; Bouldern ohne ID → nichts
        $r = $link->check([['session' => 1, 'type' => 'klettern', 'plan' => ['blocks' => [['kind' => 'bouldern_limit'], ['kind' => 'campus']]]]]);
        self::assertSame([['session' => 1, 'position' => 1, 'name' => 'campus', 'code' => 'ohne_katalog']], array_map(static fn (array $w): array => array_diff_key($w, ['hinweis' => 1]), $r['warnungen']));
        // V-07 (Schemafehler, zusätzlich hier als Fehler)
        $r = $link->check([['session' => 1, 'type' => 'klettern', 'plan' => ['blocks' => [['kind' => 'bouldern_volumen', 'exercise_id' => 'max-hang-20mm']]]]]);
        self::assertSame('kind_ohne_katalog', $r['fehler'][0]['code']);
        // V-08 archiviert → Fehler
        $r = $link->check([self::kraft(['exercise_id' => 'archiviert-1', 'name' => 'Alte Übung'])]);
        self::assertSame('archiviert', $r['fehler'][0]['code']);
        // Ausdauer und Ruhe: nichts zu prüfen
        self::assertSame(['fehler' => [], 'warnungen' => []], $link->check([['session' => 2, 'type' => 'ausdauer', 'plan' => ['summary' => 'x']], ['session' => 3, 'type' => 'ruhe', 'plan' => null]]));

        // Verlinkung für die Webseite: nur bekannte IDs, archivierte bleiben lesbar
        self::assertSame([0 => ['slug' => 'kniebeuge', 'name' => 'Kniebeuge'], 2 => ['slug' => 'archiviert-1', 'name' => 'Alte Übung']],
            $link->linksFor('kraft', ['exercises' => [['exercise_id' => 'kniebeuge'], ['name' => 'frei'], ['exercise_id' => 'archiviert-1'], ['exercise_id' => 'gibt-es-nicht']]]));
    }

    public function testRepositoryVersionsAliasesAndConflicts(): void
    {
        $id = $this->add('bulgarian-split-squat', 'Bulgarian Split Squat', ['aliases' => ['Bulgarische Kniebeuge', 'bulgarian-split squat', ' '], 'equipment' => ['kurzhantel']]);
        $e = $this->repo->bySlug('bulgarian-split-squat');
        self::assertSame(['Bulgarische Kniebeuge'], $e['aliases'], 'eigener Name und Leeres nicht als Alias');
        self::assertSame(1, $e['version']);
        self::assertSame(['kurzhantel'], $e['equipment']);

        self::assertSame([['slug' => 'bulgarian-split-squat', 'name' => 'Bulgarian Split Squat', 'treffer' => 'Bulgarische Kniebeuge']], $this->repo->conflicts(['bulgarische kniebeuge']));
        self::assertCount(1, $this->repo->conflicts(['bulgarian split squat']));
        self::assertSame([], $this->repo->conflicts(['bulgarian split squat'], $id), 'eigene Übung ausgenommen');

        $v = $this->repo->update($id, ['name' => 'Bulgarische Kniebeuge', 'aliases' => ['Bulgarian Split Squat'], 'content' => ['kurz' => 'neu'] + $e['content']] + $e, 'Deutscher Name', 'mcp');
        self::assertSame(2, $v);
        $after = $this->repo->bySlug('bulgarian-split-squat');
        self::assertSame('Bulgarische Kniebeuge', $after['name']);
        self::assertSame(['Bulgarian Split Squat'], $after['aliases']);
        self::assertSame([['version' => 1, 'reason' => 'Deutscher Name', 'created_by' => 'mcp']], array_map(static fn (array $r): array => array_diff_key($r, ['created_at' => 1]), $this->repo->versions($id)));
        $snap = $this->repo->snapshot($id, 1);
        self::assertSame('Bulgarian Split Squat', $snap['stand']['name']);
        self::assertSame(['Bulgarische Kniebeuge'], $snap['stand']['aliases']);
        self::assertSame('Bulgarian Split Squat kurz', $snap['stand']['content']['kurz']);
        self::assertNull($this->repo->snapshot($id, 2));

        // Varianten
        $child = $this->add('split-squat-gewichtsweste', 'Split Squat mit Weste', ['variant_of' => $id]);
        self::assertSame('bulgarian-split-squat', $this->repo->byId($child)['variant_of_slug']);
        self::assertSame([['slug' => 'split-squat-gewichtsweste', 'name' => 'Split Squat mit Weste']], $this->repo->children($id));
    }

    public function testSearchRanking(): void
    {
        $this->add('bulgarian-split-squat', 'Bulgarian Split Squat', ['equipment' => ['kurzhantel']]);
        $this->add('ausfallschritt', 'Ausfallschritt', ['equipment' => ['koerpergewicht']]);
        $this->add('split-jump', 'Sprung-Ausfallschritt', ['aliases' => ['Split Jump'], 'pattern' => 'sonstiges']);
        $this->add('plank', 'Unterarmstütz', ['aliases' => ['Plank'], 'pattern' => 'rumpf', 'category' => 'haltung']);
        $this->add('alte-kniebeuge', 'Split Alt', ['status' => 'archiviert']);

        $r = $this->repo->search('split');
        self::assertSame(['bulgarian-split-squat', 'split-jump', 'ausfallschritt'], array_column($r, 'slug'));
        self::assertSame([false, false, true], array_column($r, 'aehnlich'));
        self::assertSame('Bulgarian Split Squat kurz', $r[0]['kurz']);
        self::assertSame(['Split Alt'], array_column(array_filter($this->repo->search('split', includeArchived: true), static fn (array $x): bool => $x['status'] === 'archiviert'), 'name'));

        self::assertSame('plank', $this->repo->search('PLANK')[0]['slug'], 'exakter Alias vor Teilstring');
        self::assertSame('split-jump', $this->repo->search('Split Jump')[0]['slug'], 'exakter Alias zuerst');
        self::assertSame([], $this->repo->search('Kreuzheben'));
        $r = $this->repo->search('ausfall', equipment: 'koerpergewicht');
        self::assertSame(['ausfallschritt', 'split-jump', 'bulgarian-split-squat'], array_column($r, 'slug'), 'Filter Ausrüstung für Treffer; ähnlich über das Muster');
        self::assertSame([false, false, true], array_column($r, 'aehnlich'));
        self::assertSame(['bulgarian-split-squat'], array_column($this->repo->search('split', limit: 1), 'slug'));
        self::assertSame([], $this->repo->search('split', category: 'hangboard'));
        self::assertCount(4, $this->repo->listCompact());
        self::assertCount(1, $this->repo->listCompact('haltung'));
        self::assertSame(['ausfallschritt', 'bulgarian-split-squat', 'split-jump', 'plank'], array_column($this->repo->listForPage(null, null, false), 'slug'));
        self::assertSame(['plank'], array_column($this->repo->listForPage('plank', null, false), 'slug'));
    }

    public function testPlannedUsageAndSlugsInPlan(): void
    {
        self::assertSame(['a-b', 'max-hang'], ExerciseRepository::slugsInPlan(['blocks' => [['exercise_id' => 'a-b'], ['exercise_id' => 'max-hang'], ['exercise_id' => 'a-b'], ['exercise_id' => null]]]));
        self::assertSame([], ExerciseRepository::slugsInPlan(null));
        $this->pdo->exec("INSERT INTO training_block (name, start_date, end_date, status, created_at, updated_at) VALUES ('B', '2026-09-21', '2026-10-18', 'aktiv', NOW(), NOW())");
        $this->pdo->exec("INSERT INTO training_week (block_id, week_start, status, created_by, created_at, updated_at) VALUES (1, '2026-09-28', 'bestaetigt', 'mcp', NOW(), NOW())");
        $plan = json_encode(['exercises' => [['name' => 'K', 'sets' => 3, 'reps' => '8', 'exercise_id' => 'kniebeuge']]]);
        foreach ([['2026-09-28', 'geplant'], ['2026-09-29', 'erledigt']] as $i => [$date, $status]) {
            $this->pdo->prepare("INSERT INTO `session` (week_id, date, type, title, priority, plan_json, status, sort_order, created_at, updated_at) VALUES (1, ?, 'kraft', ?, 'B', ?, ?, 0, NOW(), NOW())")->execute([$date, 'Kraft ' . $i, $plan, $status]);
        }
        $this->pdo->prepare("INSERT INTO `session` (week_id, date, type, title, priority, plan_json, status, sort_order, created_at, updated_at) VALUES (1, '2026-09-30', 'kraft', 'Andere', 'B', ?, 'geplant', 0, NOW(), NOW())")
            ->execute([json_encode(['exercises' => [['name' => 'kniebeuge-tief', 'sets' => 1, 'reps' => '1', 'exercise_id' => 'kniebeuge-tief']]])]);
        self::assertSame([['id' => 1, 'date' => '2026-09-28', 'title' => 'Kraft 0']], $this->repo->plannedUsage('kniebeuge'));
    }
}
