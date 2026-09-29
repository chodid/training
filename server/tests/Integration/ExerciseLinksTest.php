<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Data\ExerciseRepository;
use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeLinkFetcher;

/** AP-16 T4: Verlinkung aus S3 (W-04) und S9, Vorladen der Übungsseiten (E-15). */
final class ExerciseLinksTest extends AppTestCase
{
    private const STATIC = 'statisches-token-statisches-token-0123';
    private const NOW = 1790164800; // Mi 2026-09-23 12:00 UTC

    /** @var array<string, int> */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->writeEnv(['MCP_STATIC_TOKEN' => self::STATIC, 'MCP_STATIC_TOKEN_ENABLED' => 'true']);
        $this->linkFetcher = new FakeLinkFetcher();
        $this->setupUser();
        $repo = new ExerciseRepository($this->pdo, $this->clock);
        foreach ([['kniebeuge', 'Kniebeuge', 'kraft', 'knie_dominant'], ['max-hang-20mm', 'Max Hang 20 mm', 'hangboard', 'unterarm_finger']] as [$slug, $name, $cat, $pattern]) {
            $repo->create(['slug' => $slug, 'name' => $name, 'aliases' => [], 'category' => $cat, 'pattern' => $pattern, 'equipment' => ['koerpergewicht'], 'variant_of' => null,
                'difficulty' => null, 'status' => 'aktiv', 'konfidenz' => 'mittel', 'content' => ['kurz' => 'k', 'ziel' => 'z', 'ausfuehrung' => ['a', 'b'], 'quellen' => ['Einschätzung'], 'links' => []]], 'mcp');
        }
        self::assertFalse($this->mcpTool(self::STATIC, 'upsert_block', ['block' => ['name' => 'Herbst', 'start_date' => '2026-09-21', 'end_date' => '2026-11-15', 'status' => 'aktiv']])['isError']);
        $plan = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Test', 'sessions' => [
            ['date' => '2026-09-23', 'type' => 'kraft', 'title' => 'Kraft heute', 'coach_summary' => 'x', 'plan_json' => ['exercises' => [
                ['name' => 'Kniebeuge', 'exercise_id' => 'kniebeuge', 'sets' => 3, 'reps' => '8', 'rest_s' => 90],
                ['name' => 'Wadenheben', 'sets' => 3, 'reps' => '15'],
            ]]],
            ['date' => '2026-09-26', 'type' => 'klettern', 'title' => 'Hangboard', 'coach_summary' => 'x', 'plan_json' => ['blocks' => [
                ['kind' => 'hangboard', 'exercise_id' => 'max-hang-20mm', 'hang_s' => 10, 'rest_s' => 180, 'sets' => 5, 'edge_mm' => 20],
                ['kind' => 'bouldern_volumen', 'duration_min' => 30],
            ]]],
        ]]);
        self::assertFalse($plan['isError'], $plan['text']);
        $this->ids = ['kraft' => $plan['data']['einheiten'][0]['id'], 'klettern' => $plan['data']['einheiten'][1]['id']];
    }

    public function testSessionPageLinksOnlyExercisesWithId(): void
    {
        // W-04 S3: Link nur mit ID, mit Rückweg zur Einheit
        $s3 = $this->request('GET', '/einheit?id=' . $this->ids['kraft']);
        self::assertStringContainsString('<a class="name ex-link" href="/uebung?id=kniebeuge&amp;von=' . $this->ids['kraft'] . '">', $s3->body);
        self::assertStringContainsString('<span class="name">Wadenheben</span>', $s3->body);
        self::assertSame(1, substr_count($s3->body, 'href="/uebung?'));
        $c3 = $this->request('GET', '/einheit?id=' . $this->ids['klettern']);
        self::assertStringContainsString('href="/uebung?id=max-hang-20mm&amp;von=' . $this->ids['klettern'] . '"', $c3->body);
        self::assertStringContainsString('Hangboard · Max Hang 20 mm</a>', $c3->body);

        // S9: Link „Ausführung“ in der Phase-Karte, Rückweg in die geführte Einheit; „Als Nächstes“ ohne Link
        $s9 = $this->request('GET', '/einheit?id=' . $this->ids['kraft'] . '&modus=start');
        self::assertStringContainsString('data-ausfuehrung href="/uebung?id=kniebeuge&amp;von=' . $this->ids['kraft'] . '&amp;modus=start"', $s9->body);
        self::assertSame(1, substr_count($s9->body, 'data-ausfuehrung'));
        $back = $this->request('GET', '/uebung?id=kniebeuge&von=' . $this->ids['kraft'] . '&modus=start');
        self::assertStringContainsString('href="/einheit?id=' . $this->ids['kraft'] . '&amp;modus=start"', $back->body);
        self::assertStringContainsString('aria-current="page"><svg', $back->body);
    }

    public function testWeekPrefetchesExercisePages(): void
    {
        $week = $this->request('GET', '/woche');
        self::assertSame(1, preg_match('/id="offline-prefetch" data-urls="([^"]+)"/', $week->body, $m));
        $urls = json_decode(html_entity_decode($m[1]), true);
        self::assertContains('/uebung?id=kniebeuge&von=' . $this->ids['kraft'], $urls, 'wie der Link aus S3');
        self::assertContains('/uebung?id=kniebeuge&von=' . $this->ids['kraft'] . '&modus=start', $urls, 'heute: wie der Link aus S9');
        self::assertContains('/uebung?id=max-hang-20mm&von=' . $this->ids['klettern'], $urls, 'Samstag: nur S3');
        self::assertNotContains('/uebung?id=max-hang-20mm&von=' . $this->ids['klettern'] . '&modus=start', $urls);
        self::assertSame(count($urls), count(array_unique($urls)));
    }
}
