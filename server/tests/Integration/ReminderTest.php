<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Data\ReviewRepository;
use Training\Data\SettingsRepository;
use Training\Db;
use Training\Tests\Support\AppTestCase;

/** AP-15 T3: Overlay-Erinnerung, Quittierung, Karte in S2, Einstellungen (docs/konzept/blockbilanz.md 6, U-01 bis U-06). */
final class ReminderTest extends AppTestCase
{
    private const NOW = 1790856000; // Do 2026-10-01 12:00 UTC

    private int $blockId;
    private int $nextId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->setupUser();

        // aktiver Block endet 2026-10-05 (Bilanz fällig ab 28.09.), Zielklärung und Revision vorhanden
        $this->blockId = $this->block('Herbst', 'aktiv', '2026-08-10', '2026-10-05');
        $this->review($this->blockId, 'zielklaerung', '2026-09-20');
        // Folgeblock mit bestätigter Zielklärung: nur die Bilanz ist fällig
        $this->nextId = $this->block('Winter', 'geplant', '2026-10-06', '2026-12-31');
        $this->review($this->nextId, 'zielklaerung', '2026-09-30');
    }

    private function dropNextBlock(): void
    {
        $this->pdo->exec('DELETE FROM block_review WHERE block_id = ' . $this->nextId);
        $this->pdo->exec('DELETE FROM training_block WHERE id = ' . $this->nextId);
    }

    private function login(): void
    {
        $form = $this->request('GET', '/login');
        $r = $this->request('POST', '/login', ['csrf' => self::csrfFrom($form), 'login' => 'philipp', 'password' => 'richtig-langes-passwort']);
        self::assertSame(303, $r->status);
    }

    private function block(string $name, string $status, string $start, string $end): int
    {
        $now = Db::ts($this->clock->now());
        $this->pdo->prepare('INSERT INTO training_block (name, start_date, end_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)')->execute([$name, $start, $end, $status, $now, $now]);

        return (int) $this->pdo->lastInsertId();
    }

    private function review(int $blockId, string $kind, string $date): void
    {
        $repo = new ReviewRepository($this->pdo, $this->clock);
        $repo->insert(['block_id' => $blockId, 'kind' => $kind, 'sequence' => 1, 'version' => $repo->nextVersion($blockId, $kind, 1), 'status' => 'bestaetigt', 'review_date' => $date,
            'period_start' => null, 'period_end' => null, 'summary' => $kind, 'content' => ['x' => 1], 'kennzahlen' => null, 'reason' => 'Test', 'created_by' => 'mcp']);
    }

    private function settings(): SettingsRepository
    {
        return new SettingsRepository($this->pdo, $this->clock);
    }

    /** @param list<string> $entries */
    private function ack(string $page, string $bis, array $entries): \Training\Http\Response
    {
        $csrf = self::csrfFrom($this->request('GET', $page));

        return $this->request('POST', '/erinnerung', ['csrf' => $csrf, 'bis' => $bis, 'eintrag' => $entries, 'zurueck' => $page]);
    }

    public function testU01OverlayOnAppPagesNotOnLogin(): void
    {
        foreach (['/woche', '/checkin', '/einstellungen', '/verlauf', '/schmerz'] as $page) {
            $r = $this->request('GET', $page);
            self::assertSame(200, $r->status, $page);
            self::assertStringContainsString('data-review-overlay', $r->body, $page);
            self::assertStringContainsString('Blockbilanz fällig', $r->body);
            self::assertStringContainsString('Block „Herbst“ endet am 05.10.', $r->body);
            self::assertStringContainsString('role="dialog" aria-modal="true"', $r->body);
            self::assertMatchesRegularExpression('/<main class="main[^"]*" inert aria-hidden="true">/', $r->body);
            self::assertMatchesRegularExpression('/value="morgen" autofocus>Morgen wieder erinnern/', $r->body, 'Fokus auf der ersten Schaltfläche');
            self::assertStringContainsString('name="eintrag[]" value="bilanz:' . $this->blockId . '"', $r->body);
            self::assertStringContainsString('href="/block?id=' . $this->blockId . '"', $r->body);
        }
        self::assertStringNotContainsString('Zielklärung fällig', $this->request('GET', '/woche')->body);
        $this->request('POST', '/logout', ['csrf' => self::csrfFrom($this->request('GET', '/einstellungen'))]);
        self::assertStringNotContainsString('data-review-overlay', $this->request('GET', '/login')->body);
    }

    public function testU02U03AcknowledgeAndReturn(): void
    {
        $r = $this->ack('/checkin', 'morgen', ['bilanz:' . $this->blockId]);
        self::assertSame(303, $r->status);
        self::assertSame('/checkin', $r->headers['Location']);
        self::assertSame('2026-10-02', $this->settings()->erinnerungBis('bilanz', $this->blockId));
        self::assertStringNotContainsString('data-review-overlay', $this->request('GET', '/woche')->body);
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'reminder_ack' AND actor = 'web'")->fetchColumn());

        $this->clock->advance(86400); // 2026-10-02
        self::assertStringContainsString('data-review-overlay', $this->request('GET', '/woche')->body, 'U-02 am nächsten Tag wieder da');

        $this->ack('/woche', 'woche', ['bilanz:' . $this->blockId]);
        self::assertSame('2026-10-09', $this->settings()->erinnerungBis('bilanz', $this->blockId));
        $this->clock->advance(6 * 86400); // 2026-10-08
        self::assertStringNotContainsString('data-review-overlay', $this->request('GET', '/woche')->body, 'U-03 sieben Tage Ruhe');
        $this->clock->advance(86400); // 2026-10-09
        self::assertStringContainsString('data-review-overlay', $this->request('GET', '/woche')->body);
    }

    public function testU04ConfirmedReviewRemovesOverlayAndAck(): void
    {
        $this->ack('/woche', 'morgen', ['bilanz:' . $this->blockId]);
        // Bilanz über MCP bestätigen
        $this->writeEnv(['MCP_STATIC_TOKEN' => 'statisches-token-statisches-token-0123', 'MCP_STATIC_TOKEN_ENABLED' => 'true']);
        $bilanz = json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/review-beispiele.json'), true)['bilanz'];
        $w = $this->mcpTool('statisches-token-statisches-token-0123', 'write_block_review', ['block_id' => $this->blockId, 'kind' => 'bilanz', 'review_date' => '2026-10-01', 'summary' => 'Bilanz', 'content' => $bilanz, 'status' => 'bestaetigt']);
        self::assertFalse($w['isError'], $w['text']);
        self::assertNull($this->settings()->erinnerungBis('bilanz', $this->blockId), 'Quittierung gelöscht');
        $this->clock->advance(86400);
        self::assertStringNotContainsString('Blockbilanz fällig', $this->request('GET', '/woche')->body);
    }

    public function testNoOverlayDuringGuidedSessionButOnHomeAfterwards(): void
    {
        $token = 'statisches-token-statisches-token-0123';
        $this->writeEnv(['MCP_STATIC_TOKEN' => $token, 'MCP_STATIC_TOKEN_ENABLED' => 'true']);
        $ex = $this->mcpTool($token, 'upsert_exercise', ['slug' => 'test-kniebeuge', 'name' => 'Test-Kniebeuge', 'category' => 'kraft', 'pattern' => 'knie_dominant',
            'equipment' => ['koerpergewicht'], 'konfidenz' => 'einschaetzung', 'content' => ['kurz' => 'Kniebeuge', 'ziel' => 'Beine', 'ausfuehrung' => ['Stehen', 'Beugen'], 'quellen' => ['Einschätzung']]]);
        self::assertFalse($ex['isError'], $ex['text']);
        $plan = $this->mcpTool($token, 'write_week_plan', ['week_start' => '2026-09-28', 'focus' => 'Test', 'sessions' => [
            ['date' => '2026-10-01', 'type' => 'kraft', 'title' => 'Kraft', 'coach_summary' => 'Test', 'plan_json' => ['exercises' => [['name' => 'Test-Kniebeuge', 'exercise_id' => 'test-kniebeuge', 'sets' => 2, 'reps' => '8']]]],
        ]]);
        self::assertFalse($plan['isError'], $plan['text']);
        $id = $plan['data']['einheiten'][0]['id'];

        // S9 geführt und S10 aus S9 heraus: kein Overlay (Training läuft)
        $s9 = $this->request('GET', '/einheit?id=' . $id . '&modus=start');
        self::assertSame(200, $s9->status);
        self::assertStringContainsString('data-gefuehrt', $s9->body);
        self::assertStringNotContainsString('data-review-overlay', $s9->body);
        self::assertStringNotContainsString('data-review-overlay', $this->request('GET', '/uebung?id=test-kniebeuge&von=' . $id . '&modus=start')->body);
        // S3 und S10 aus S3: Overlay wie überall
        self::assertStringContainsString('data-review-overlay', $this->request('GET', '/einheit?id=' . $id)->body);
        self::assertStringContainsString('data-review-overlay', $this->request('GET', '/uebung?id=test-kniebeuge&von=' . $id)->body);
        // Nach dem Abschluss leitet S9 auf S2 (/woche?…&ok=einheit): dort erscheint es sofort
        self::assertStringContainsString('data-review-overlay', $this->request('GET', '/woche?start=2026-09-28&ok=einheit')->body);
    }

    public function testU05OverlayOffCardStillShowsDueItems(): void
    {
        $this->settings()->set(SettingsRepository::REVIEW_OVERLAY, 'aus');
        $r = $this->request('GET', '/woche');
        self::assertStringNotContainsString('data-review-overlay', $r->body);
        self::assertStringContainsString('block-card', $r->body);
        self::assertStringContainsString('Blockbilanz fällig:', $r->body);
        self::assertStringContainsString('noch 5 Tage', $r->body);
        self::assertStringContainsString('Zur Blockseite', $r->body);
    }

    public function testU06TwoDueItemsOneOverlay(): void
    {
        $this->dropNextBlock(); // ohne Folgeblock: Zielklärung ab 21.09. fällig (Vorlauf 14)
        $r = $this->request('GET', '/woche');
        self::assertStringContainsString('Blockbilanz und Zielklärung fällig', $r->body);
        self::assertSame(1, substr_count($r->body, 'data-review-overlay'));
        self::assertStringContainsString('value="zielklaerung:' . $this->blockId . '"', $r->body);
        $this->ack('/woche', 'morgen', ['bilanz:' . $this->blockId, 'zielklaerung:' . $this->blockId]);
        self::assertSame('2026-10-02', $this->settings()->erinnerungBis('bilanz', $this->blockId));
        self::assertSame('2026-10-02', $this->settings()->erinnerungBis('zielklaerung', $this->blockId));
    }

    public function testNoActiveBlockCardAndOverlayWithoutBlock(): void
    {
        $this->dropNextBlock();
        $this->pdo->exec("UPDATE training_block SET status = 'abgeschlossen'");
        $this->review($this->blockId, 'bilanz', '2026-10-01');
        $r = $this->request('GET', '/woche');
        self::assertStringContainsString('Kein aktiver Block – Zielklärung im Projekt-Chat.', $r->body);
        self::assertStringContainsString('Zielklärung fällig', $r->body);
        self::assertStringContainsString('name="eintrag[]" value="zielklaerung:0"', $r->body);
        $this->ack('/woche', 'morgen', ['zielklaerung:0']);
        self::assertSame('2026-10-02', $this->settings()->erinnerungBis('zielklaerung', null));
    }

    public function testAckValidationQueueAndRedirectTarget(): void
    {
        $csrf = self::csrfFrom($this->request('GET', '/woche'));
        self::assertSame(422, $this->request('POST', '/erinnerung', ['csrf' => $csrf, 'bis' => 'immer', 'eintrag' => ['bilanz:1']])->status);
        self::assertSame(422, $this->request('POST', '/erinnerung', ['csrf' => $csrf, 'bis' => 'morgen', 'eintrag' => ['revision:1']])->status);
        self::assertSame(403, $this->request('POST', '/erinnerung', ['csrf' => 'falsch', 'bis' => 'morgen', 'eintrag' => ['bilanz:1']])->status);
        $r = $this->request('POST', '/erinnerung', ['csrf' => $csrf, 'bis' => 'morgen', 'eintrag' => ['bilanz:' . $this->blockId], 'zurueck' => '//evil.example/x']);
        self::assertSame('/woche', $r->headers['Location']);
        $q = $this->request('POST', '/erinnerung', ['csrf' => $csrf, 'bis' => 'morgen', 'eintrag' => ['bilanz:' . $this->blockId]], ['X-Offline-Queue' => '1']);
        self::assertSame(204, $q->status, 'gepufferte Sendung des Service Workers');
        // Schreibsperre: kein Overlay, Quittieren gesperrt
        $this->clock->advance(86400);
        $this->rollbackLastMigration();
        self::assertStringNotContainsString('data-review-overlay', $this->request('GET', '/woche')->body);
    }

    public function testSettingsSubpage(): void
    {
        $page = $this->request('GET', '/einstellungen?bereich=blockreview');
        self::assertSame(200, $page->status);
        self::assertStringContainsString('value="08:00"', $page->body);
        $overview = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('Blockbilanz und Zielklärung', $overview->body);
        self::assertStringContainsString('Vorlauf 7/14 Tage', $overview->body);

        $csrf = self::csrfFrom($page);
        $bad = $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'blockreview', 'overlay' => 'an', 'bilanz_vorlauf' => '29', 'zielklaerung_vorlauf' => '14', 'beginn' => '08:00', 'dauer' => '120', 'erinnerung_h' => '24']);
        self::assertSame(422, $bad->status);
        self::assertStringContainsString('value="14"', $bad->body);
        $ok = $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'blockreview', 'bilanz_vorlauf' => '3', 'zielklaerung_vorlauf' => '21', 'beginn' => '18:30', 'dauer' => '90', 'erinnerung_h' => '0']);
        self::assertSame(303, $ok->status);
        $s = $this->settings();
        self::assertFalse($s->reviewOverlay());
        self::assertSame([3, 21], [$s->bilanzVorlauf(), $s->zielklaerungVorlauf()]);
        self::assertSame(['beginn' => '18:30', 'dauer_min' => 90, 'erinnerung_h' => 0], $s->kalenderBlock());
        // Vorlauf 3: Bilanz erst ab 02.10. fällig (Overlay ohnehin aus)
        self::assertStringNotContainsString('Blockbilanz fällig:', $this->request('GET', '/woche')->body);
    }
}
