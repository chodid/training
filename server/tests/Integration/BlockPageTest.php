<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Tests\Support\AppTestCase;

/** AP-15 T5: Blockseite S11 und Abschnitt „Blöcke“ in S6 (docs/konzept/blockbilanz.md T5, E-18). */
final class BlockPageTest extends AppTestCase
{
    private const STATIC = 'statisches-token-statisches-token-0123';
    private const NOW = 1792584000; // Mi 2026-10-21 12:00 UTC

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->writeEnv(['MCP_STATIC_TOKEN' => self::STATIC, 'MCP_STATIC_TOKEN_ENABLED' => 'true']);
        $this->setupUser();
    }

    /** @param array<string, mixed> $args @return array<string, mixed> */
    private function ok(string $tool, array $args): array
    {
        $r = $this->mcpTool(self::STATIC, $tool, $args);
        self::assertFalse($r['isError'], $r['text']);

        return $r['data'];
    }

    /** @return array<string, mixed> */
    private static function ex(string $kind): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/review-beispiele.json'), true)[$kind];
    }

    public function testEmptyStates(): void
    {
        $r = $this->request('GET', '/block');
        self::assertSame(200, $r->status);
        self::assertStringContainsString('Noch kein Trainingsblock.', $r->body);
        self::assertSame(404, $this->request('GET', '/block?id=99')->status);
        self::assertSame(404, $this->request('GET', '/block?id=abc')->status);

        $id = $this->ok('upsert_block', ['block' => ['name' => 'Herbst', 'start_date' => '2026-09-21', 'end_date' => '2026-12-13', 'status' => 'aktiv']])['id'];
        $page = $this->request('GET', '/block');
        self::assertStringContainsString('<h1>Herbst</h1>', $page->body);
        self::assertStringContainsString('Noch keine Zielklärung für diesen Block.', $page->body);
        self::assertStringContainsString('Noch keine Revision in diesem Block.', $page->body);
        self::assertStringContainsString('Noch keine Bilanz', $page->body);
        self::assertStringContainsString('Zielklärung fällig.', $page->body, 'Fälligkeit im Kopf');
        self::assertStringContainsString('noch 7 Wochen', $page->body);
        self::assertStringContainsString('<h1>Herbst</h1>', $this->request('GET', '/block?id=' . $id)->body);
    }

    public function testFullBlockWithVersionsAndOtherBlocks(): void
    {
        $b1 = $this->ok('upsert_block', ['block' => ['name' => 'Sommer', 'start_date' => '2026-07-01', 'end_date' => '2026-09-20', 'status' => 'aktiv']])['id'];
        $this->ok('write_block_review', ['block_id' => $b1, 'kind' => 'zielklaerung', 'review_date' => '2026-06-28', 'summary' => 'Grundlage und Sehne', 'content' => self::ex('zielklaerung'), 'status' => 'entwurf']);
        $this->ok('write_block_review', ['block_id' => $b1, 'kind' => 'zielklaerung', 'review_date' => '2026-06-29', 'summary' => 'Grundlage und Sehne', 'content' => self::ex('zielklaerung'), 'status' => 'bestaetigt', 'reason' => 'vom Athleten bestätigt']);
        $this->ok('write_block_review', ['block_id' => $b1, 'kind' => 'revision', 'review_date' => '2026-08-01', 'summary' => 'Sehne gereizt', 'content' => self::ex('revision'), 'status' => 'bestaetigt']);
        $this->ok('write_block_review', ['block_id' => $b1, 'kind' => 'bilanz', 'review_date' => '2026-09-19', 'summary' => 'Z2-Lauf fast erreicht', 'content' => self::ex('bilanz'), 'status' => 'bestaetigt']);
        $this->ok('write_block_review', ['block_id' => $b1, 'kind' => 'bilanz', 'review_date' => '2026-09-20', 'summary' => 'Z2-Lauf fast erreicht', 'content' => self::ex('bilanz'), 'status' => 'entwurf', 'reason' => 'Test nachgetragen']);
        $b2 = $this->ok('upsert_block', ['block' => ['name' => 'Herbst', 'start_date' => '2026-09-21', 'end_date' => '2026-12-13', 'status' => 'aktiv']])['id'];

        $r = $this->request('GET', '/block?id=' . $b1);
        self::assertSame(200, $r->status);
        $body = $r->body;
        self::assertStringContainsString('<h1>Sommer</h1>', $body);
        self::assertStringContainsString('badge-neutral">abgeschlossen', $body);
        // Zielklärung: Abschnitte des Schemas, Entscheidungen mit verworfenen Alternativen
        self::assertStringContainsString('Grundlagen', $body);
        self::assertStringContainsString('6–8 h/Woche', $body);
        self::assertStringContainsString('<span class="mono">z-1</span> Lockerer Lauf 60 min in Z2 ohne Kniereiz', $body);
        self::assertStringContainsString('10 % Wochenregel – reagiert zu spät auf Einzelspitzen', $body);
        self::assertStringContainsString('Skitour Silvretta', $body);
        self::assertStringContainsString('Sehnenreizung → Morgentest &gt; 5: Laufen streichen', $body);
        self::assertStringContainsString('Fassung 2 · bestätigt', $body);
        self::assertStringContainsString('vom Athleten bestätigt', $body);
        self::assertStringContainsString('Fassung 1 · Entwurf', $body);
        // Revision als Zeitleiste
        self::assertStringContainsString('01.08.2026 · Schmerz', $body);
        self::assertStringContainsString('Intervalle bergab gestrichen – Exzentrische Last auf die Sehne (bis 25.10.2026)', $body);
        // Bilanz: gültig v1, neuer Entwurf v2, Tabelle, Kennzahlen
        self::assertStringContainsString('neuer Entwurf v2', $body);
        self::assertStringContainsString('badge-warning">teilweise', $body);
        self::assertStringContainsString('badge-success">erreicht', $body);
        self::assertStringContainsString('vom Server eingefroren, 12 Wochen', $body);
        self::assertStringContainsString('Test nachgetragen', $body);
        // weitere Blöcke
        self::assertStringContainsString('href="/block?id=' . $b2 . '"', $body);

        // S6 Abschnitt „Blöcke“ mit Link und Fälligkeit
        $s6 = $this->request('GET', '/verlauf')->body;
        self::assertStringContainsString('id="bloecke"', $s6);
        self::assertStringContainsString('href="/block?id=' . $b1 . '"', $s6);
        self::assertStringContainsString('fällig: Zielklärung', $s6, 'Herbst ohne Zielklärung');

        // Woche: Blockseite im Vorladen, Karte verlinkt
        $week = $this->request('GET', '/woche')->body;
        self::assertStringContainsString('/block?id=' . $b2, $week);
        self::assertSame(1, preg_match('/id="offline-prefetch" data-urls="([^"]+)"/', $week, $m));
        self::assertContains('/block?id=' . $b2, json_decode(html_entity_decode($m[1]), true));
    }

    public function testDraftOnlyAndLoginRequired(): void
    {
        $b = $this->ok('upsert_block', ['block' => ['name' => 'Herbst', 'start_date' => '2026-09-21', 'end_date' => '2026-12-13', 'status' => 'aktiv']])['id'];
        $this->ok('write_block_review', ['block_id' => $b, 'kind' => 'zielklaerung', 'review_date' => '2026-09-20', 'summary' => 'Aufbau', 'content' => self::ex('zielklaerung'), 'status' => 'entwurf']);
        $body = $this->request('GET', '/block')->body;
        self::assertStringContainsString('Entwurf – noch nicht bestätigt', $body);
        self::assertStringContainsString('Zielklärung fällig.', $body, 'Entwurf zählt nicht');
        $this->request('POST', '/logout', ['csrf' => self::csrfFrom($this->request('GET', '/einstellungen'))]);
        self::assertStringStartsWith('/login', $this->request('GET', '/block')->headers['Location'] ?? '');
    }
}
