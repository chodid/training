<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Tests\Support\AppTestCase;

/** AP-15 T2: get_handover, get_block_reviews, write_block_review und Erweiterungen (docs/konzept/blockbilanz.md 11.2, H-01 bis H-08). */
final class ReviewToolsTest extends AppTestCase
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

    /** @return array<string, mixed> */
    private static function examples(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/review-beispiele.json'), true);
    }

    /** @param array<string, mixed> $args @return array{isError: bool, data: mixed, text: string} */
    private function tool(string $name, array $args = []): array
    {
        return $this->mcpTool(self::STATIC, $name, $args);
    }

    /** @return array{0: int, 1: int} Block 1 (abgeschlossen, Zielklärung + Bilanz), Block 2 (aktiv, Zielklärung, Revisionen) */
    private function fixture(int $revisions = 2): array
    {
        $ex = self::examples();
        $b1 = $this->tool('upsert_block', ['block' => ['name' => 'Sommer 2026', 'start_date' => '2026-07-01', 'end_date' => '2026-09-20', 'status' => 'aktiv', 'phase_notes' => 'Wochen 1–4 Einstieg, 5–11 Aufbau, 12 Entlastung']]);
        self::assertFalse($b1['isError'], $b1['text']);
        $b1 = $b1['data']['id'];
        $this->ok('write_block_review', ['block_id' => $b1, 'kind' => 'zielklaerung', 'review_date' => '2026-06-28', 'summary' => 'Grundlage und Sehne', 'content' => $ex['zielklaerung'], 'status' => 'bestaetigt']);
        $bilanz = $ex['bilanz'];
        $bilanz['zeitraum'] = ['von' => '2026-07-01', 'bis' => '2026-09-20'];
        $this->ok('write_block_review', ['block_id' => $b1, 'kind' => 'bilanz', 'review_date' => '2026-09-19', 'summary' => 'Z2-Lauf fast erreicht, Sehne stabil', 'content' => $bilanz, 'status' => 'bestaetigt']);
        $b2 = $this->tool('upsert_block', ['block' => ['name' => 'Herbst 2026', 'start_date' => '2026-09-21', 'end_date' => '2026-12-13', 'status' => 'aktiv', 'goal_events' => [['name' => 'Skitour', 'date' => '2027-02-01']], 'phase_notes' => 'Aufbau mit Schwelle']]);
        self::assertFalse($b2['isError'], $b2['text']);
        $b2 = $b2['data']['id'];
        $zk = $ex['zielklaerung'];
        $zk['phase'] = 'aufbau';
        $zk['offene_fragen'] = ['Zweite Klettereinheit?'];
        $this->ok('write_block_review', ['block_id' => $b2, 'kind' => 'zielklaerung', 'review_date' => '2026-09-19', 'summary' => 'Aufbau Schwelle', 'content' => $zk, 'status' => 'bestaetigt']);
        for ($i = 0; $i < $revisions; $i++) {
            $this->ok('write_block_review', ['block_id' => $b2, 'kind' => 'revision', 'review_date' => '2026-10-1' . $i, 'summary' => 'Revision ' . ($i + 1), 'content' => $ex['revision'], 'status' => 'bestaetigt']);
        }

        return [$b1, $b2];
    }

    /** @param array<string, mixed> $args @return array<string, mixed> */
    private function ok(string $name, array $args): array
    {
        $r = $this->tool($name, $args);
        self::assertFalse($r['isError'], $r['text']);

        return $r['data'];
    }

    public function testH01H02HandoverCompactAndDetail(): void
    {
        [$b1, $b2] = $this->fixture();
        $this->pdo->exec("INSERT INTO training_week (block_id, week_start, focus, status, created_by, created_at, updated_at) VALUES ($b2, '2026-10-12', 'Schwelle einführen', 'bestaetigt', 'mcp', NOW(), NOW()), ($b2, '2026-10-19', NULL, 'bestaetigt', 'mcp', NOW(), NOW())");
        $h = $this->tool('get_handover');
        self::assertFalse($h['isError'], $h['text']);
        $d = $h['data'];
        self::assertSame($b2, $d['block']['id']);
        self::assertSame('5/12', $d['block']['woche']);
        self::assertSame($b2, $d['zielklaerung']['block_id']);
        self::assertStringStartsWith('aufbau: ', $d['zielklaerung']['phase']);
        self::assertStringStartsWith('z-1 [t1] ', $d['zielklaerung']['ziele'][0]);
        self::assertSame(['Laufumfang: Steigerung nur über Einzellauflänge'], $d['zielklaerung']['entscheidungen']);
        self::assertCount(1, $d['bilanzen']);
        self::assertSame($b1, $d['bilanzen'][0]['block_id']);
        self::assertSame('z-1 teilweise: Zwei Wochen Ausfall durch Erkältung.', $d['bilanzen'][0]['ziele'][0]);
        self::assertCount(2, $d['revisionen']);
        self::assertSame([1, 2], array_column($d['revisionen'], 'nr'));
        self::assertArrayHasKey('srpe_je_woche', $d['kennzahlen']);
        self::assertSame(['2026-10-12: Schwelle einführen', '2026-10-19: –'], $d['wochen_kurz']);
        self::assertContains('Zweite Klettereinheit?', $d['offene_fragen']);
        self::assertContains('Zweite Klettereinheit möglich?', $d['offene_fragen']);
        self::assertArrayHasKey('ziele', $d['profil_stand']);
        self::assertSame([], $d['faellig']);
        self::assertArrayNotHasKey('volltexte', $d);
        self::assertLessThanOrEqual(8000, mb_strlen($h['text']), 'H-01 Budget');

        $full = $this->tool('get_handover', ['detail' => true])['data'];
        self::assertSame('aufbau', $full['volltexte']['zielklaerung']['content_json']['phase']);
        self::assertSame(self::examples()['bilanz']['empfehlung'], $full['volltexte']['bilanz']['content_json']['empfehlung']);
        self::assertSame($b1, $full['volltexte']['bilanz']['block_id']);
        self::assertGreaterThan(0, $full['volltexte']['bilanz']['kennzahlen_auto']['wochen']);
    }

    public function testHandoverBudgetWithTwoBlocksAndThreeRevisions(): void
    {
        [, $b2] = $this->fixture(3);
        // lange Texte bis an die Grenzen der Schemata
        $zk = self::examples()['zielklaerung'];
        $zk['ziele'] = array_map(static fn (int $i): array => ['id' => 'z-' . $i, 'bereich' => 't1', 'ziel' => str_repeat('Ziel ', 90), 'messgroesse' => str_repeat('M ', 200), 'kriterium' => str_repeat('K ', 200), 'termin' => null], range(1, 20));
        $zk['entscheidungen'] = array_fill(0, 20, ['thema' => str_repeat('T', 400), 'entscheidung' => str_repeat('E', 400), 'rationale' => str_repeat('R', 1500), 'verworfen' => ['keine'], 'quelle' => null]);
        $zk['risiken'] = array_fill(0, 20, ['risiko' => str_repeat('r', 400), 'regel' => str_repeat('g', 400)]);
        $zk['offene_fragen'] = array_fill(0, 20, str_repeat('F', 400));
        $this->ok('write_block_review', ['block_id' => $b2, 'kind' => 'zielklaerung', 'review_date' => '2026-10-20', 'summary' => 'lang', 'content' => $zk, 'status' => 'bestaetigt', 'reason' => 'Budgettest']);
        $h = $this->tool('get_handover');
        self::assertFalse($h['isError'], $h['text']);
        self::assertCount(3, $h['data']['revisionen']);
        self::assertLessThanOrEqual(8000, mb_strlen($h['text']), 'Budget ohne detail (E-17): ' . mb_strlen($h['text']));
        self::assertArrayHasKey('gekuerzt', $h['data']);
    }

    public function testH03SchemaErrorWithPathNothingWritten(): void
    {
        [$b1] = $this->fixture(0);
        $bilanz = self::examples()['bilanz'];
        unset($bilanz['ziele'][0]['bewertung']);
        $r = $this->tool('write_block_review', ['block_id' => $b1, 'kind' => 'bilanz', 'review_date' => '2026-10-21', 'summary' => 'x', 'content' => $bilanz, 'status' => 'entwurf', 'reason' => 'x']);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('content/ziele/0', $r['text']);
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM block_review WHERE kind = 'bilanz'")->fetchColumn());
    }

    public function testH04H05ReasonAndDraftOverConfirmed(): void
    {
        [$b1] = $this->fixture(0);
        $bilanz = self::examples()['bilanz'];
        $r = $this->tool('write_block_review', ['block_id' => $b1, 'kind' => 'bilanz', 'review_date' => '2026-10-21', 'summary' => 'Test nachgetragen', 'content' => $bilanz, 'status' => 'entwurf']);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('reason ab Fassung 2', $r['text']);

        $d = $this->ok('write_block_review', ['block_id' => $b1, 'kind' => 'bilanz', 'review_date' => '2026-10-21', 'summary' => 'Test nachgetragen', 'content' => $bilanz, 'status' => 'entwurf', 'reason' => 'Test nachgetragen']);
        self::assertSame(2, $d['version']);
        self::assertSame('entwurf', $d['status']);
        self::assertArrayHasKey('hinweis', $d);
        self::assertSame([], array_values(array_filter($d['faellig'], static fn (array $f): bool => $f['kind'] === 'bilanz')), 'H-05 faellig ohne Bilanz');

        $list = $this->ok('get_block_reviews', ['block_id' => $b1, 'kind' => 'bilanz']);
        self::assertCount(1, $list['reviews']);
        self::assertSame(1, $list['reviews'][0]['version']);
        self::assertSame('bestaetigt', $list['reviews'][0]['status']);
        self::assertSame(2, $list['reviews'][0]['entwurf']['version']);
        self::assertArrayHasKey('content_json', $list['reviews'][0]);
        self::assertArrayHasKey('kennzahlen_auto', $list['reviews'][0]);
        $versions = $this->ok('get_block_reviews', ['block_id' => $b1, 'fassungen' => true]);
        self::assertSame(['zielklaerung', 'bilanz', 'bilanz'], array_column($versions['reviews'], 'kind'));
        self::assertSame('Test nachgetragen', $versions['reviews'][2]['reason']);
        self::assertArrayNotHasKey('content_json', $versions['reviews'][2]);
        self::assertStringContainsString('entwuerfe', $this->tool('get_handover')['text']);
    }

    public function testH06RevisionSequenceAndKennzahlen(): void
    {
        [, $b2] = $this->fixture(0);
        $rev = self::examples()['revision'];
        $a = $this->ok('write_block_review', ['block_id' => $b2, 'kind' => 'revision', 'review_date' => '2026-10-19', 'summary' => 'Schmerz', 'content' => $rev, 'status' => 'bestaetigt']);
        $b = $this->ok('write_block_review', ['block_id' => $b2, 'kind' => 'revision', 'review_date' => '2026-10-21', 'summary' => 'Turnus', 'content' => $rev, 'status' => 'bestaetigt', 'period_start' => '2026-10-05', 'period_end' => '2026-10-18']);
        self::assertSame([1, 2], [$a['sequence'], $b['sequence']]);
        self::assertSame(['von' => '2026-09-22', 'bis' => '2026-10-19'], $a['kennzahlen_auto']['zeitraum']);
        self::assertSame(['von' => '2026-10-05', 'bis' => '2026-10-18'], $b['kennzahlen_auto']['zeitraum']);
        $stored = $this->pdo->query("SELECT period_start, period_end, kennzahlen_auto FROM block_review WHERE kind = 'revision' ORDER BY sequence")->fetchAll();
        self::assertSame(['2026-09-22', '2026-10-19'], [$stored[0]['period_start'], $stored[0]['period_end']]);
        self::assertSame(5, json_decode((string) $stored[0]['kennzahlen_auto'], true)['wochen'], 'Kalenderwochen 21.09.–19.10.');
        // Neue Fassung einer bestehenden Revision
        $c = $this->ok('write_block_review', ['block_id' => $b2, 'kind' => 'revision', 'sequence' => 1, 'review_date' => '2026-10-19', 'summary' => 'Schmerz', 'content' => $rev, 'status' => 'bestaetigt', 'reason' => 'Datum der Änderung ergänzt']);
        self::assertSame([1, 2], [$c['sequence'], $c['version']]);
        self::assertTrue($this->tool('write_block_review', ['block_id' => $b2, 'kind' => 'revision', 'sequence' => 9, 'review_date' => '2026-10-19', 'summary' => 'x', 'content' => $rev, 'status' => 'entwurf'])['isError']);
        $audit = $this->pdo->query("SELECT summary FROM audit_log WHERE action = 'review_write' ORDER BY id DESC LIMIT 1")->fetchColumn();
        self::assertSame('revision Block ' . $b2 . ' v2 bestaetigt (Revision 1)', $audit);
    }

    public function testH07ZielklaerungForClosedBlockRejected(): void
    {
        [$b1] = $this->fixture(0);
        $r = $this->tool('write_block_review', ['block_id' => $b1, 'kind' => 'zielklaerung', 'review_date' => '2026-10-21', 'summary' => 'x', 'content' => self::examples()['zielklaerung'], 'status' => 'entwurf', 'reason' => 'x']);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('geplant oder aktiv', $r['text']);
        self::assertTrue($this->tool('write_block_review', ['block_id' => $b1, 'kind' => 'revision', 'review_date' => '2026-10-21', 'summary' => 'x', 'content' => self::examples()['revision'], 'status' => 'entwurf'])['isError']);
        self::assertTrue($this->tool('write_block_review', ['block_id' => 999, 'kind' => 'bilanz', 'review_date' => '2026-10-21', 'summary' => 'x', 'content' => self::examples()['bilanz'], 'status' => 'entwurf'])['isError']);
    }

    public function testH08WeekAfterBlockEndNeedsFollowUpZielklaerung(): void
    {
        [, $b2] = $this->fixture(0);
        $week = fn (string $monday): array => $this->tool('write_week_plan', ['week_start' => $monday, 'focus' => 'Test', 'sessions' => [['date' => $monday, 'type' => 'ruhe', 'title' => 'Ruhetag']]]);
        $r = $week('2026-12-14');
        self::assertTrue($r['isError']);
        self::assertStringContainsString('blockwechsel_erforderlich', $r['text']);
        self::assertStringContainsString('details', $r['text']);
        // Woche innerhalb des Blocks weiter möglich
        self::assertFalse($week('2026-12-07')['isError']);

        // Folgeblock ohne Zielklärung reicht nicht; mit bestätigter Zielklärung ist die Woche möglich
        $b3 = $this->ok('upsert_block', ['block' => ['name' => 'Winter', 'start_date' => '2026-12-14', 'end_date' => '2027-03-07', 'status' => 'geplant']]);
        self::assertTrue($b3['zielklaerung_fehlt']);
        self::assertTrue($week('2026-12-14')['isError']);
        $this->ok('write_block_review', ['block_id' => $b3['id'], 'kind' => 'zielklaerung', 'review_date' => '2026-10-21', 'summary' => 'Winter', 'content' => self::examples()['zielklaerung'], 'status' => 'bestaetigt']);
        $ok = $week('2026-12-14');
        self::assertFalse($ok['isError'], $ok['text']);
        unset($b2);
    }

    public function testGetBlockListsReviewsAndDueItems(): void
    {
        $this->clock->now = self::NOW + 50 * 86400; // 2026-12-10: Blockende naht
        [, $b2] = $this->fixture(1);
        $block = $this->ok('get_block', ['block_id' => $b2]);
        self::assertSame(['zielklaerung', 'revision'], array_column($block['reviews'], 'kind'));
        $kinds = array_column($block['faellig'], 'kind');
        self::assertContains('bilanz', $kinds);
        self::assertContains('zielklaerung', $kinds);
        self::assertSame('blockende', $block['faellig'][0]['grund']);
        self::assertStringContainsString('endet am 13.12.', $block['faellig'][0]['text']);
    }

    public function testWriteLockAndToolList(): void
    {
        [$b1] = $this->fixture(0);
        $this->rollbackLastMigration();
        $r = $this->tool('write_block_review', ['block_id' => $b1, 'kind' => 'bilanz', 'review_date' => '2026-10-21', 'summary' => 'x', 'content' => self::examples()['bilanz'], 'status' => 'entwurf', 'reason' => 'x']);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('Update erforderlich', $r['text']);
    }
}
