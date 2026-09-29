<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeCalDav;

/** AP-15 T4: Blocktermin im Kalender über MCP, Einstellungen und Abgleich (docs/konzept/blockbilanz.md 7, K-B1 bis K-B6). */
final class BlockCalendarTest extends AppTestCase
{
    private const STATIC = 'statisches-token-statisches-token-0123';
    private const NOW = 1790164800; // Mi 2026-09-23 12:00 UTC
    private const CAL = 'https://cloud.example/remote.php/dav/calendars/p/training/';

    private FakeCalDav $cal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->cal = new FakeCalDav();
        $this->calendarTransport = $this->cal;
        $this->writeEnv(['MCP_STATIC_TOKEN' => self::STATIC, 'MCP_STATIC_TOKEN_ENABLED' => 'true', 'CRON_SECRET' => str_repeat('c', 32),
            'CALDAV_URL' => self::CAL, 'CALDAV_USER' => 'p', 'CALDAV_PASSWORD' => 'app-passwort']);
        $this->setupUser();
    }

    /** @param array<string, mixed> $args @return array<string, mixed> */
    private function ok(string $tool, array $args): array
    {
        $r = $this->mcpTool(self::STATIC, $tool, $args);
        self::assertFalse($r['isError'], $r['text']);

        return $r['data'];
    }

    private function ics(string $name): string
    {
        self::assertArrayHasKey($name, $this->cal->events, implode(', ', array_keys($this->cal->events)));

        return str_replace("\r\n ", '', $this->cal->events[$name]);
    }

    /** @return array<string, mixed> */
    private static function example(string $kind): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/review-beispiele.json'), true)[$kind];
    }

    private function review(int $blockId, string $kind): void
    {
        $this->ok('write_block_review', ['block_id' => $blockId, 'kind' => $kind, 'review_date' => '2026-09-23', 'summary' => $kind, 'content' => self::example($kind), 'status' => 'bestaetigt', 'reason' => 'Test']);
    }

    public function testKB1ToKB5Lifecycle(): void
    {
        // K-B1: aktiver Block, Termin am Blockende 08:00–10:00 Berlin, Erinnerung am Vortag
        $b1 = $this->ok('upsert_block', ['block' => ['name' => 'Herbst', 'start_date' => '2026-09-21', 'end_date' => '2026-12-14', 'status' => 'aktiv']])['id'];
        $ics = $this->ics('training-block-' . $b1 . '.ics');
        self::assertStringContainsString("DTSTART:20261214T070000Z\r\n", $ics);
        self::assertStringContainsString("DTEND:20261214T090000Z\r\n", $ics);
        self::assertStringContainsString("TRIGGER:-P1D\r\n", $ics);
        self::assertStringContainsString('SUMMARY:Blockbilanz + Zielklärung: Herbst', $ics);
        preg_match('/SEQUENCE:(\d+)/', $ics, $seq1);
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'calendar_block_event'")->fetchColumn());

        // K-B3: Blockende verschoben → gleiche Ressource, SEQUENCE höher
        $this->clock->advance(60);
        $this->ok('upsert_block', ['block_id' => $b1, 'block' => ['name' => 'Herbst', 'start_date' => '2026-09-21', 'end_date' => '2026-12-07', 'status' => 'aktiv']]);
        $ics = $this->ics('training-block-' . $b1 . '.ics');
        self::assertStringContainsString("DTSTART:20261207T070000Z\r\n", $ics);
        preg_match('/SEQUENCE:(\d+)/', $ics, $seq2);
        self::assertGreaterThan((int) $seq1[1], (int) $seq2[1]);

        // Zielklärung des eigenen Blocks ändert den Termin nicht (es fehlen Bilanz und Zielklärung des Folgeblocks)
        $this->review($b1, 'zielklaerung');
        self::assertStringContainsString('SUMMARY:Blockbilanz + Zielklärung: Herbst', $this->ics('training-block-' . $b1 . '.ics'));

        // K-B4: Bilanz bestätigt, Zielklärung des Folgeblocks fehlt
        $this->review($b1, 'bilanz');
        self::assertStringContainsString('SUMMARY:Zielklärung: Herbst', $this->ics('training-block-' . $b1 . '.ics'));
        // Folgeblock (geplant) bekommt eigenen Termin
        $b2 = $this->ok('upsert_block', ['block' => ['name' => 'Winter', 'start_date' => '2026-12-08', 'end_date' => '2027-03-07', 'status' => 'geplant']])['id'];
        self::assertStringContainsString('SUMMARY:Blockbilanz + Zielklärung: Winter', $this->ics('training-block-' . $b2 . '.ics'));
        self::assertArrayHasKey('training-block-' . $b1 . '.ics', $this->cal->events, 'Folgeblock ohne Zielklärung: Termin bleibt');

        // K-B5: Zielklärung des Folgeblocks bestätigt → Termin gelöscht, Fassung erhöht
        $this->review($b2, 'zielklaerung');
        self::assertArrayNotHasKey('training-block-' . $b1 . '.ics', $this->cal->events);
        self::assertSame('1', $this->pdo->query("SELECT value FROM app_setting WHERE setting_key = 'kalender_block_" . $b1 . "'")->fetchColumn());
        self::assertArrayHasKey('training-block-' . $b2 . '.ics', $this->cal->events);
        $audit = $this->pdo->query("SELECT summary FROM audit_log WHERE action = 'calendar_block_event' ORDER BY id DESC LIMIT 5")->fetchAll(\PDO::FETCH_COLUMN);
        self::assertContains('Blocktermin ' . $b1 . ' gelöscht', $audit);
    }

    public function testKB6AndTrashGenerationAndSync(): void
    {
        $this->cal->trash = true;
        // K-B6: Block länger als 16 Wochen → Termin auf Beginn + 112 Tage
        $b = $this->ok('upsert_block', ['block' => ['name' => 'Lang', 'start_date' => '2026-09-21', 'end_date' => '2027-03-28', 'status' => 'aktiv']])['id'];
        self::assertStringContainsString("DTSTART:20270111T070000Z\r\n", $this->ics('training-block-' . $b . '.ics'));

        // Einstellungen (E-22): Beginn, Dauer, Erinnerung → Termin sofort neu
        $csrf = self::csrfFrom($this->request('GET', '/einstellungen?bereich=blockreview'));
        $r = $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'blockreview', 'overlay' => 'an', 'bilanz_vorlauf' => '7', 'zielklaerung_vorlauf' => '14', 'beginn' => '18:00', 'dauer' => '60', 'erinnerung_h' => '2']);
        self::assertSame('/einstellungen?ok=blockreview&n=1', $r->headers['Location']);
        $ics = $this->ics('training-block-' . $b . '.ics');
        self::assertStringContainsString("DTSTART:20270111T170000Z\r\n", $ics);
        self::assertStringContainsString("DTEND:20270111T180000Z\r\n", $ics);
        self::assertStringContainsString("TRIGGER:-PT2H\r\n", $ics);

        // Bilanz und Folgeblock mit Zielklärung → gelöscht (Papierkorb); danach wieder Bedarf → neue Fassung -1
        $this->review($b, 'bilanz');
        $next = $this->ok('upsert_block', ['block' => ['name' => 'Danach', 'start_date' => '2027-03-29', 'end_date' => '2027-06-20', 'status' => 'geplant']])['id'];
        $this->review($next, 'zielklaerung');
        self::assertArrayNotHasKey('training-block-' . $b . '.ics', $this->cal->events);
        $this->pdo->exec('DELETE FROM block_review WHERE block_id = ' . $next);
        $sync = json_decode($this->request('GET', '/cron/intervals-sync?key=' . str_repeat('c', 32))->body, true)['kalender'];
        self::assertSame([], $sync['fehler']);
        self::assertArrayHasKey('training-block-' . $b . '-1.ics', $this->cal->events, 'neue Fassung nach dem Löschen (Papierkorb)');

        // Abgleich: Block gelöscht → verwaister Termin wird entfernt, fremde bleiben
        $this->cal->events['fremd.ics'] = "BEGIN:VCALENDAR\r\nDTSTART:20261010T080000Z\r\nEND:VCALENDAR\r\n";
        $this->pdo->exec('DELETE FROM training_block WHERE id = ' . $next);
        self::assertArrayHasKey('training-block-' . $next . '.ics', $this->cal->events);
        $sync = json_decode($this->request('GET', '/cron/intervals-sync?key=' . str_repeat('c', 32))->body, true)['kalender'];
        self::assertSame(['uebertragen' => 1, 'geloescht' => 1], $sync['blocktermine']);
        self::assertArrayNotHasKey('training-block-' . $next . '.ics', $this->cal->events);
        self::assertArrayHasKey('fremd.ics', $this->cal->events);
    }

    public function testCalendarErrorIsReportedNotBlocking(): void
    {
        $this->cal->failMethod = ['PUT' => 500];
        $r = $this->mcpTool(self::STATIC, 'upsert_block', ['block' => ['name' => 'Herbst', 'start_date' => '2026-09-21', 'end_date' => '2026-12-14', 'status' => 'aktiv']]);
        self::assertFalse($r['isError']);
        self::assertCount(1, $r['data']['fehler_kalender']);
        self::assertSame('kalender_block', $this->pdo->query("SELECT entity FROM audit_log WHERE action = 'calendar_error'")->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM training_block')->fetchColumn());
        $this->cal->failMethod = [];
        $sync = json_decode($this->request('GET', '/cron/intervals-sync?key=' . str_repeat('c', 32))->body, true)['kalender'];
        self::assertSame(1, $sync['blocktermine']['uebertragen']);
        self::assertArrayHasKey('training-block-1.ics', $this->cal->events);
    }
}
