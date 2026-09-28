<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Tests\Support\AppTestCase;

/** Athletenprofil als DB-Objekt (D-48): MCP lesen/ändern mit Fassungen, Webseite /profil, Schutz gegen Überschreiben. */
final class ProfileTest extends AppTestCase
{
    private const STATIC = 'statisches-token-statisches-token-0123';
    private const NOW = 1790164800; // Mi 2026-09-23 12:00 UTC

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->writeEnv(['MCP_STATIC_TOKEN' => self::STATIC, 'MCP_STATIC_TOKEN_ENABLED' => 'true']);
        $this->setupUser();
    }

    public function testMcpVersionsAsOfAndHistory(): void
    {
        $empty = $this->mcpTool(self::STATIC, 'get_athlete_profile');
        self::assertFalse($empty['data']['vorhanden']);
        self::assertStringContainsString('update_athlete_profile', $empty['data']['hinweis']);
        self::assertSame(['ziele', 'zeitbudget', 'ausruestung', 'einschraenkungen', 'leistungswerte', 'sonstiges'], array_column($empty['data']['abschnitte'], 'abschnitt'));

        $v1 = $this->mcpTool(self::STATIC, 'update_athlete_profile', ['section' => 'leistungswerte', 'content' => "LTHR 168\r\nMaxhang 20 mm: +10 kg\n", 'reason' => 'Eingangstest']);
        self::assertFalse($v1['isError'], $v1['text']);
        self::assertFalse($v1['data']['unveraendert']);
        $same = $this->mcpTool(self::STATIC, 'update_athlete_profile', ['section' => 'leistungswerte', 'content' => "LTHR 168\nMaxhang 20 mm: +10 kg"]);
        self::assertTrue($same['data']['unveraendert'], 'nur Zeilenenden/Leerraum anders');

        $this->clock->advance(3 * 86400);
        $this->mcpTool(self::STATIC, 'update_athlete_profile', ['section' => 'leistungswerte', 'content' => "LTHR 172\nMaxhang 20 mm: +12 kg", 'reason' => 'Test 26.09.']);

        $p = $this->mcpTool(self::STATIC, 'get_athlete_profile', ['section' => 'leistungswerte']);
        self::assertTrue($p['data']['vorhanden']);
        self::assertCount(1, $p['data']['abschnitte']);
        $s = $p['data']['abschnitte'][0];
        self::assertSame("LTHR 172\nMaxhang 20 mm: +12 kg", $s['inhalt']);
        self::assertSame(['claude', 'Test 26.09.', 2, '2026-09-26 14:00'], [$s['von'], $s['grund'], $s['fassungen'], $s['geaendert']]);

        $old = $this->mcpTool(self::STATIC, 'get_athlete_profile', ['section' => 'leistungswerte', 'as_of' => '2026-09-25']);
        self::assertSame("LTHR 168\nMaxhang 20 mm: +10 kg", $old['data']['abschnitte'][0]['inhalt']);
        $before = $this->mcpTool(self::STATIC, 'get_athlete_profile', ['as_of' => '2026-09-22']);
        self::assertFalse($before['data']['vorhanden']);

        $h = $this->mcpTool(self::STATIC, 'get_athlete_profile', ['section' => 'leistungswerte', 'include_history' => true]);
        self::assertSame(['Test 26.09.', 'Eingangstest'], array_column($h['data']['fassungen'], 'grund'));

        self::assertTrue($this->mcpTool(self::STATIC, 'get_athlete_profile', ['include_history' => true])['isError']);
        self::assertTrue($this->mcpTool(self::STATIC, 'get_athlete_profile', ['as_of' => '26.09.2026'])['isError']);
        self::assertTrue($this->mcpTool(self::STATIC, 'update_athlete_profile', ['section' => 'hobbys', 'content' => 'x'])['isError']);
        $long = $this->mcpTool(self::STATIC, 'update_athlete_profile', ['section' => 'ziele', 'content' => str_repeat('ä', 6001)]);
        self::assertTrue($long['isError']);
        self::assertStringContainsString('zu lang', $long['text']);

        self::assertSame(2, (int) $this->pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'profile_update' AND actor = 'mcp'")->fetchColumn());

        // Schreibsperre
        $this->rollbackLastMigration();
        $locked = $this->mcpTool(self::STATIC, 'update_athlete_profile', ['section' => 'ziele', 'content' => 'x']);
        self::assertTrue($locked['isError']);
        self::assertStringContainsString('Update erforderlich', $locked['text']);
    }

    public function testWebViewEditConflictAndHistory(): void
    {
        $settings = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('0 von 6 Abschnitten ausgefüllt', $settings->body);

        $page = $this->request('GET', '/profil');
        self::assertSame(200, $page->status);
        self::assertStringContainsString('Noch leer – Zielevents', $page->body);

        $form = $this->request('GET', '/profil?abschnitt=ziele');
        self::assertStringContainsString('name="basis" value=""', $form->body);
        $csrf = self::csrfFrom($form);
        self::assertSame(403, $this->request('POST', '/profil', ['csrf' => 'x', 'abschnitt' => 'ziele', 'inhalt' => 'a', 'basis' => ''])->status);
        $r = $this->request('POST', '/profil', ['csrf' => $csrf, 'abschnitt' => 'ziele', 'inhalt' => "Skitour <Haute Route> 2027\n- Klettern 7a", 'grund' => 'erste Fassung', 'basis' => '']);
        self::assertSame(303, $r->status);
        self::assertSame('/profil?ok=gespeichert#ziele', $r->headers['Location']);
        $page = $this->request('GET', '/profil?ok=gespeichert');
        self::assertStringContainsString('Skitour &lt;Haute Route&gt; 2027', $page->body);
        self::assertStringContainsString('Stand 23.09.2026 · Web', $page->body);
        self::assertStringNotContainsString('Frühere Fassungen (', $page->body);

        // Claude ändert, während das Formular offen ist → kein Überschreiben
        $form = $this->request('GET', '/profil?abschnitt=ziele');
        $id = (int) $this->pdo->query("SELECT MAX(id) FROM athlete_profile")->fetchColumn();
        self::assertStringContainsString('name="basis" value="' . $id . '"', $form->body);
        $this->clock->advance(60);
        $this->mcpTool(self::STATIC, 'update_athlete_profile', ['section' => 'ziele', 'content' => 'Skitour 2027', 'reason' => 'gekürzt']);
        $conflict = $this->request('POST', '/profil', ['csrf' => self::csrfFrom($form), 'abschnitt' => 'ziele', 'inhalt' => 'Mein Text', 'basis' => (string) $id]);
        self::assertSame(409, $conflict->status);
        self::assertStringContainsString('Inzwischen geändert', $conflict->body);
        self::assertStringContainsString('von Claude', $conflict->body);
        self::assertStringContainsString('Mein Text', $conflict->body);
        self::assertSame('Skitour 2027', $this->pdo->query("SELECT content FROM athlete_profile ORDER BY id DESC LIMIT 1")->fetchColumn());

        // Unverändert speichern legt keine Fassung an
        $form = $this->request('GET', '/profil?abschnitt=ziele');
        $r = $this->request('POST', '/profil', ['csrf' => self::csrfFrom($form), 'abschnitt' => 'ziele', 'inhalt' => "Skitour 2027\r\n", 'basis' => (string) ($id + 1)]);
        self::assertSame('/profil?ok=unveraendert#ziele', $r->headers['Location']);

        // Zu lang
        $long = $this->request('POST', '/profil', ['csrf' => self::csrfFrom($form), 'abschnitt' => 'ziele', 'inhalt' => str_repeat('x', 6001), 'basis' => (string) ($id + 1)]);
        self::assertSame(422, $long->status);

        $page = $this->request('GET', '/profil');
        self::assertStringContainsString('Frühere Fassungen (1)', $page->body);
        self::assertStringContainsString('Stand 23.09.2026 · Claude', $page->body);
        $hist = $this->request('GET', '/profil?abschnitt=ziele&verlauf=1');
        self::assertStringContainsString('gekürzt', $hist->body);
        self::assertStringContainsString('erste Fassung', $hist->body);
        self::assertSame(1, substr_count($hist->body, 'aktuell'));

        self::assertSame(404, $this->request('GET', '/profil?abschnitt=hobbys')->status);
        self::assertStringContainsString('1 von 6 Abschnitten ausgefüllt', $this->request('GET', '/einstellungen')->body);
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'profile_update' AND actor = 'web'")->fetchColumn());

        // Ohne Login
        $this->cookies = [];
        self::assertSame('/login?next=%2Fprofil', $this->request('GET', '/profil')->headers['Location']);
    }
}
