<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Data\ExerciseRepository;
use Training\Exercise\LinkCheckRun;
use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeLinkFetcher;

/** AP-16 T5: wöchentliche Linkprüfung im Cron (Teil D). */
final class ExerciseLinkCheckCronTest extends AppTestCase
{
    private const NOW = 1790164800; // Mi 2026-09-23 12:00 UTC
    private const KEY = 'cron-secret-cron-secret-cron-secret-0123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->writeEnv(['CRON_SECRET' => self::KEY]); // weder Intervals noch Kalender: der Abgleich antwortet 503, die Linkprüfung läuft trotzdem
        $this->linkFetcher = new FakeLinkFetcher();
    }

    /** @param list<string> $urls */
    private function add(string $slug, array $urls, string $status = 'aktiv', ?string $checked = '2026-09-01'): int
    {
        $links = array_map(static fn (string $u): array => ['url' => $u, 'titel' => 'T', 'art' => 'text', 'embed' => null, 'geprueft_am' => $checked, 'status' => 'ok'], $urls);

        return (new ExerciseRepository($this->pdo, $this->clock))->create(['slug' => $slug, 'name' => 'Übung ' . $slug, 'aliases' => [], 'category' => 'kraft', 'pattern' => 'rumpf',
            'equipment' => ['matte'], 'variant_of' => null, 'difficulty' => null, 'status' => $status, 'konfidenz' => 'mittel',
            'content' => ['kurz' => 'k', 'ziel' => 'z', 'ausfuehrung' => ['a', 'b'], 'quellen' => ['Einschätzung'], 'links' => $links]], 'mcp');
    }

    private function cron(): array
    {
        $r = $this->request('GET', '/cron/intervals-sync?key=' . self::KEY);

        return json_decode($r->body, true);
    }

    private function exerciseStatus(string $slug): string
    {
        return (new ExerciseRepository($this->pdo, $this->clock))->bySlug($slug)['status'];
    }

    public function testWeeklyRunStatusChangesAndLimit(): void
    {
        $this->add('eins', ['https://example.org/a', 'https://example.org/weg']);
        $this->add('zwei', ['https://example.org/b']);
        $this->add('archiv', ['https://example.org/archiv'], 'archiviert');
        $this->linkFetcher->answers = ['/weg' => 404, '/b' => 'nicht erreichbar: Timeout'];

        $r = $this->cron();
        self::assertSame(['links' => 3, 'offen' => 0, 'ok' => 1, 'defekt' => 1, 'ungeprueft' => 1, 'uebungen' => 2, 'links_pruefen' => 1, 'wieder_aktiv' => 0], $r['linkpruefung']);
        self::assertSame('links_pruefen', $this->exerciseStatus('eins'), 'defekt → links_pruefen');
        self::assertSame('aktiv', $this->exerciseStatus('zwei'), 'Timeout ist kein Defekt, früheres Ergebnis bleibt');
        $zwei = (new ExerciseRepository($this->pdo, $this->clock))->bySlug('zwei')['content']['links'][0];
        self::assertSame(['ok', '2026-09-01'], [$zwei['status'], $zwei['geprueft_am']]);
        self::assertNotContains('https://example.org/archiv', $this->linkFetcher->calls[0], 'archivierte nicht geprüft');
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'exercise_linkcheck' AND actor = 'cron'")->fetchColumn());
        self::assertStringContainsString('Linkprüfung: 3 Links in 2 Übungen (ok 1, defekt 1', (string) $this->pdo->query("SELECT summary FROM audit_log WHERE action = 'exercise_linkcheck'")->fetchColumn());
        self::assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM exercise_version")->fetchColumn(), 'keine neue Fassung');
        self::assertStringContainsString('1 Übung mit defekten Links', $this->settingsPage());

        // nur einmal je 7 Tage
        $this->clock->now = self::NOW + 6 * 86400;
        self::assertArrayNotHasKey('linkpruefung', $this->cron());
        self::assertCount(1, $this->linkFetcher->calls);

        // nach 7 Tagen: Link wieder erreichbar → Übung wieder aktiv
        $this->clock->now = self::NOW + 7 * 86400;
        $this->linkFetcher->answers = [];
        $r = $this->cron();
        self::assertSame(1, $r['linkpruefung']['wieder_aktiv']);
        self::assertSame('aktiv', $this->exerciseStatus('eins'));
    }

    public function testLimitOldestFirstAndFailures(): void
    {
        for ($i = 1; $i <= 26; $i++) {
            $this->add('uebung-' . $i, ['https://example.org/' . $i . '/a', 'https://example.org/' . $i . '/b'], 'aktiv', $i === 26 ? null : '2026-09-' . sprintf('%02d', min(28, $i)));
        }
        $r = (new LinkCheckRun($this->pdo, $this->clock, $this->app()->linkChecker()))->runIfDue();
        self::assertSame([50, 2], [$r['links'], $r['offen']], 'höchstens 50 Links je Lauf');
        self::assertContains('https://example.org/26/a', $this->linkFetcher->calls[0], 'nie geprüfte zuerst');
        self::assertNotContains('https://example.org/25/a', $this->linkFetcher->calls[0], 'zuletzt geprüfte warten');
        self::assertNull((new LinkCheckRun($this->pdo, $this->clock, $this->app()->linkChecker()))->runIfDue(), 'Marke gesetzt');

        // Fehler (z. B. Tabelle fehlt) brechen den Cron nicht ab
        $this->pdo->exec("DELETE FROM app_setting WHERE setting_key = 'linkcheck_zuletzt'");
        $this->pdo->exec('RENAME TABLE exercise_alias TO exercise_alias_x');
        $this->pdo->exec('RENAME TABLE exercise TO exercise_x');
        $r = $this->request('GET', '/cron/intervals-sync?key=' . self::KEY);
        self::assertSame(503, $r->status, 'Abgleich meldet wie bisher fehlende Konfiguration');
        self::assertArrayHasKey('fehler', json_decode($r->body, true)['linkpruefung']);
        $this->pdo->exec('RENAME TABLE exercise_x TO exercise');
        $this->pdo->exec('RENAME TABLE exercise_alias_x TO exercise_alias');
    }

    private function settingsPage(): string
    {
        $this->setupUser();

        return $this->request('GET', '/einstellungen')->body;
    }
}
