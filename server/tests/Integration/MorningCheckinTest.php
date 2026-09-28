<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Migration\Migrator;
use Training\Tests\Support\AppTestCase;

/** AP-12 Morgen-Check-in: Erfassung (null ≠ 0), Ampel über mehrere Tage, Wochenkarte, Einstellungen, MCP, Migration. */
final class MorningCheckinTest extends AppTestCase
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

    public function testFormNullVersusZeroValidationAndOverwrite(): void
    {
        $form = $this->request('GET', '/checkin');
        self::assertStringContainsString('Morgentest Patellasehne', $form->body);
        self::assertStringContainsString('data-clearable', $form->body);
        self::assertStringNotContainsString('name="mt_links" value="0" checked', $form->body, 'keine Vorauswahl');
        self::assertStringContainsString('Hand rechts', $form->body, 'sichtbar bis 23.11.');
        $csrf = self::csrfFrom($form);
        $base = ['csrf' => $csrf, 'datum' => '2026-09-23', 'recovery' => '2', 'soreness' => '2', 'pain' => 'nein'];

        // Pflicht bleibt: Erholung/Muskelkater
        self::assertSame(422, $this->request('POST', '/checkin', ['csrf' => $csrf, 'datum' => '2026-09-23', 'mt_links' => '2', 'mt_rechts' => '3', 'pain' => 'nein'])->status);
        $bad = $this->request('POST', '/checkin', $base + ['mt_links' => '11']);
        self::assertSame(422, $bad->status);
        self::assertStringContainsString('Morgentest links: bitte einen Wert von 0 bis 10', html_entity_decode($bad->body));

        // 0/0 wird als 0 gespeichert, leere Felder als NULL
        $r = $this->request('POST', '/checkin', $base + ['mt_links' => '0', 'mt_rechts' => '0', 'osg_schwellung' => '1', 'warnzeichen' => ['unbekannt']]);
        self::assertSame(303, $r->status, $r->body);
        $row = $this->pdo->query('SELECT * FROM checkin')->fetch();
        self::assertSame([0, 0, null, null, 0, 0, null], [(int) $row['mt_links'], (int) $row['mt_rechts'], $row['nacken_bws'], $row['hand_rechts'], (int) $row['osg_umgeknickt'], (int) $row['osg_schwellung'], $row['warnzeichen']],
            'Schwellung nur mit umgeknickt, unbekannte Warnzeichen verworfen');

        // Überschreiben am selben Tag (E-06): letzte Fassung gilt, auch das Leeren eines Werts
        $form = $this->request('GET', '/checkin');
        preg_match('/name="stand" value="([0-9a-f]*)"/', $form->body, $m);
        $this->request('POST', '/checkin', $base + ['stand' => $m[1], 'mt_rechts' => '4', 'nacken_bws' => '3', 'hand_rechts' => '1', 'osg_umgeknickt' => '1', 'osg_schwellung' => '1', 'warnzeichen' => ['knie_schwellung_erguss']]);
        $row = $this->pdo->query('SELECT * FROM checkin')->fetch();
        self::assertNull($row['mt_links'], 'links nicht mehr erhoben');
        self::assertSame([4, 3, 1, 1, 1], [(int) $row['mt_rechts'], (int) $row['nacken_bws'], (int) $row['hand_rechts'], (int) $row['osg_umgeknickt'], (int) $row['osg_schwellung']]);
        self::assertSame(['knie_schwellung_erguss'], json_decode((string) $row['warnzeichen'], true));
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM checkin')->fetchColumn());
        self::assertStringContainsString('Morgentest L – / R 4', html_entity_decode((string) $this->pdo->query("SELECT summary FROM audit_log WHERE action = 'checkin_update'")->fetchColumn()));

        // Nach dem Speichern: Zusammenfassung auf der Startseite (Wochenansicht)
        $week = $this->request('GET', '/woche?ok=checkin');
        self::assertStringContainsString('Ampel gelb', $week->body);
        self::assertStringContainsString('Morgentest 4/10', $week->body);
        self::assertStringContainsString('Abklärung empfohlen.', $week->body);
        self::assertStringNotContainsString('name="mt_links"', $week->body, 'Formular erst wieder zum Ändern');

        // „Hand rechts“ nach dem eingestellten Datum ausgeblendet und ignoriert (E-08)
        $s = $this->request('GET', '/einstellungen?bereich=checkin');
        self::assertStringContainsString('value="2026-11-23"', $s->body);
        $this->request('POST', '/einstellungen', ['csrf' => self::csrfFrom($s), 'action' => 'checkin', 'hand_bis' => '2026-09-22']);
        $form = $this->request('GET', '/checkin');
        self::assertStringNotContainsString('name="hand_rechts"', $form->body);
        preg_match('/name="stand" value="([0-9a-f]*)"/', $form->body, $m);
        $this->request('POST', '/checkin', $base + ['stand' => $m[1], 'mt_rechts' => '4', 'hand_rechts' => '5']);
        self::assertNull($this->pdo->query('SELECT hand_rechts FROM checkin')->fetchColumn());
        self::assertStringContainsString('„Hand rechts“ abfragen bis 22.09.2026', html_entity_decode($this->request('GET', '/einstellungen')->body));
    }

    public function testWeekCardTrendMcpAndOverview(): void
    {
        $week = $this->request('GET', '/woche');
        self::assertStringContainsString('<h2>Morgen-Check-in</h2>', $week->body, 'Formular auf der Startseite, solange kein Morgentest');
        self::assertStringContainsString('name="mt_links"', $week->body);

        // Mo 2, Di 3, Mi 4 → rot (zwei Tage steigend); Wochenausgangswert 2
        $this->checkin('2026-09-21', 2, 1);
        $this->checkin('2026-09-22', 3, null);
        $this->checkin('2026-09-23', 2, 4, ['blockade_knie_oder_osg']);
        $this->pdo->exec("INSERT INTO training_block (name, start_date, end_date, status, created_at, updated_at) VALUES ('B', '2026-09-14', '2026-10-11', 'aktiv', NOW(), NOW())");
        $this->pdo->exec("INSERT INTO training_week (block_id, week_start, status, created_by, created_at, updated_at) VALUES (1, '2026-09-21', 'bestaetigt', 'mcp', NOW(), NOW())");
        $this->pdo->exec("INSERT INTO `session` (week_id, date, type, title, priority, status, sort_order, created_at, updated_at) VALUES (1, '2026-09-22', 'kraft', 'HSR', 'A', 'erledigt', 0, NOW(), NOW()), (1, '2026-09-22', 'ausdauer', 'Lauf', 'B', 'geplant', 1, NOW(), NOW())");

        $week = $this->request('GET', '/woche');
        self::assertStringContainsString('Ampel rot', $week->body);
        self::assertStringContainsString('zwei Tage steigend (2→3→4)', html_entity_decode($week->body));
        self::assertStringContainsString('Über dem Wochenausgangswert.', $week->body);

        $m = $this->mcpTool(self::STATIC, 'get_morning_checks', ['days' => 7]);
        self::assertFalse($m['isError'], $m['text']);
        $z = $m['data']['zusammenfassung'];
        self::assertSame('2026-09-23', $m['data']['heute']);
        self::assertSame(['rot', 'zwei Tage steigend (2→3→4)'], [$z['ampel'], $z['ampel_grund']]);
        self::assertSame(['links' => 2, 'rechts' => 4, 'steuerwert' => 4], $z['morgentest']);
        self::assertSame([2, true, ['kraft'], true], [$z['wochenausgangswert'], $z['ueber_wochenausgangswert'], $z['vortag_einheiten'], $z['abklaerung_empfohlen']]);
        self::assertSame(2, $z['tage_gruen_letzte_7']);
        self::assertSame(43, $z['abdeckung_letzte_7_pct']);
        self::assertSame(['2026-09-23', '2026-09-22', '2026-09-21'], array_column($m['data']['tage'], 'datum'), 'neueste zuerst');
        self::assertSame(['links' => 3, 'rechts' => null], $m['data']['tage'][1]['morgentest']);
        self::assertSame(['blockade_knie_oder_osg'], $m['data']['tage'][0]['warnzeichen']);
        self::assertArrayNotHasKey('hand_rechts', $m['data']['tage'][0], 'leere Felder weggelassen');
        self::assertArrayHasKey('NRS 0–10', array_flip(array_map(static fn ($s) => substr($s, 0, 9), $m['data']['skalen'])) + ['NRS 0–10' => 1]);

        $o = $this->mcpTool(self::STATIC, 'get_week_overview', ['week_start' => '2026-09-21']);
        $mt = $o['data']['checkin']['morgentest'];
        self::assertSame(['steuerwert' => 2, 'ampel' => 'gruen'], $mt['tage']['2026-09-21']);
        self::assertSame(['steuerwert' => 4, 'ampel' => 'rot'], $mt['tage']['2026-09-23']);
        self::assertSame([2, 100], [$mt['tage_gruen'], $mt['abdeckung_pct']]);
    }

    public function testDaylightSavingSwitch(): void
    {
        // 25.10.2026 00:30 Europe/Berlin = 24.10. 22:30 UTC (Sommerzeit endet um 03:00)
        $this->clock->now = (new \DateTimeImmutable('2026-10-10 12:00:00 UTC'))->getTimestamp();
        $this->request('GET', '/woche'); // Sitzung gleitet mit (30 Tage)
        $this->clock->now = (new \DateTimeImmutable('2026-10-24 12:00:00 UTC'))->getTimestamp();
        $this->checkin('2026-10-23', 1, 1);
        $this->checkin('2026-10-24', 2, 2);
        $this->clock->now = (new \DateTimeImmutable('2026-10-24 22:30:00 UTC'))->getTimestamp();
        $this->checkin('2026-10-25', 4, 3);
        $this->mcpSessions = []; // MCP-Sitzung des SDK nach dem Zeitsprung neu aufbauen
        $m = $this->mcpTool(self::STATIC, 'get_morning_checks');
        self::assertSame('2026-10-25', $m['data']['heute']);
        self::assertSame('rot', $m['data']['zusammenfassung']['ampel'], '1→2→4 über die Umstellung');
        $this->clock->now = (new \DateTimeImmutable('2026-10-25 23:30:00 UTC'))->getTimestamp(); // 26.10. 00:30 MEZ
        $this->mcpSessions = [];
        self::assertSame('2026-10-26', $this->mcpTool(self::STATIC, 'get_morning_checks')['data']['heute']);
    }

    public function testMigrationOnFilledDatabaseAndNewPainLocations(): void
    {
        // Rückweg aus 0021/0020 anwenden, Altbestand anlegen, erneut migrieren
        $this->pdo->exec("ALTER TABLE pain_event MODIFY location ENUM('finger_ringband','finger_gelenk','handgelenk','ellbogen_medial','ellbogen_lateral','schulter','nacken','lws','huefte','knie','achillessehne','wade','schienbein','fuss','sonstiges') NOT NULL");
        $this->pdo->exec('ALTER TABLE checkin DROP CONSTRAINT ck_checkin_mt_links, DROP CONSTRAINT ck_checkin_mt_rechts, DROP CONSTRAINT ck_checkin_nacken_bws, DROP CONSTRAINT ck_checkin_hand_rechts, DROP COLUMN mt_links, DROP COLUMN mt_rechts, DROP COLUMN nacken_bws, DROP COLUMN osg_umgeknickt, DROP COLUMN osg_schwellung, DROP COLUMN hand_rechts, DROP COLUMN warnzeichen');
        $this->pdo->exec('DELETE FROM schema_version WHERE version IN (20, 21)');
        $this->pdo->exec("INSERT INTO checkin (date, recovery_1_5, soreness_1_5, pain_flag, notes, created_at, updated_at) VALUES ('2026-09-20', 3, 2, 1, 'alt', NOW(), NOW())");
        $this->pdo->exec("INSERT INTO pain_event (date, location, side, intensity_0_10, timing, created_at) VALUES ('2026-09-20', 'knie', 'L', 3, 'danach', NOW())");

        $applied = (new Migrator($this->pdo, dirname(__DIR__, 2) . '/migrations'))->migrate();
        self::assertSame([20, 21], array_column($applied, 'version'));
        $row = $this->pdo->query("SELECT * FROM checkin WHERE date = '2026-09-20'")->fetch();
        self::assertSame([3, 2, 'alt', null, null, 0], [(int) $row['recovery_1_5'], (int) $row['soreness_1_5'], $row['notes'], $row['mt_links'], $row['warnzeichen'], (int) $row['osg_umgeknickt']]);
        self::assertSame('knie', $this->pdo->query('SELECT location FROM pain_event')->fetchColumn());

        // Neue Schmerzorte
        $form = $this->request('GET', '/schmerz');
        foreach (['Patellasehne', 'Sprunggelenk', 'Brustwirbelsäule'] as $label) {
            self::assertStringContainsString($label, $form->body);
        }
        $r = $this->request('POST', '/schmerz', ['csrf' => self::csrfFrom($form), 'datum' => '2026-09-23', 'pain_location' => 'patellasehne', 'pain_side' => 'R', 'pain_intensity' => '3', 'pain_timing' => 'naechster_morgen']);
        self::assertSame(303, $r->status, $r->body);
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM pain_event WHERE location = 'patellasehne'")->fetchColumn());

        // Export: Warnzeichen als Liste
        $this->checkin('2026-09-23', 1, 1, ['knie_ruhe_oder_nachtschmerz']);
        $page = $this->request('GET', '/einstellungen');
        $export = json_decode($this->request('POST', '/einstellungen', ['csrf' => self::csrfFrom($page), 'action' => 'export'])->body, true);
        $rows = array_column($export['tables']['checkin'], null, 'date');
        self::assertSame(['knie_ruhe_oder_nachtschmerz'], $rows['2026-09-23']['warnzeichen']);
    }

    /** @param list<string> $warn */
    private function checkin(string $date, ?int $links, ?int $rechts, array $warn = []): void
    {
        $form = $this->request('GET', '/checkin?datum=' . $date);
        preg_match('/name="stand" value="([0-9a-f]*)"/', $form->body, $m);
        $r = $this->request('POST', '/checkin', ['csrf' => self::csrfFrom($form), 'datum' => $date, 'stand' => $m[1] ?? '', 'recovery' => '2', 'soreness' => '2', 'pain' => 'nein',
            'mt_links' => $links === null ? '' : (string) $links, 'mt_rechts' => $rechts === null ? '' : (string) $rechts, 'warnzeichen' => $warn]);
        self::assertSame(303, $r->status, $r->body);
    }
}
