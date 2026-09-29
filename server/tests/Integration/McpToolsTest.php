<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use PDO;
use Training\Db;
use Training\OAuth\Jwt;
use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeTransport;

/** AP-05: MCP-Tools (Abschnitt 8.2) über den echten /mcp-Endpunkt. */
final class McpToolsTest extends AppTestCase
{
    private const STATIC = 'statisches-token-statisches-token-0123';
    private const NOW = 1790164800; // Mi 2026-09-23 12:00 UTC

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->writeEnv(['MCP_STATIC_TOKEN' => self::STATIC, 'MCP_STATIC_TOKEN_ENABLED' => 'true', 'INTERVALS_API_KEY' => 'k', 'INTERVALS_ATHLETE_ID' => 'i1']);
        $this->intervalsTransport = new FakeTransport([
            'POST /api/v1/athlete/i1/events' => [['status' => 200, 'body' => '{"id":5001}'], ['status' => 200, 'body' => '{"id":5002}'], ['status' => 200, 'body' => '{"id":5003}']],
            'PUT /api/v1/athlete/i1/events/5001' => [['status' => 200, 'body' => '{"id":5001}']],
            'DELETE /api/v1/athlete/i1/events/5001' => [['status' => 200, 'body' => '']],
            'DELETE /api/v1/athlete/i1/events/5002' => [['status' => 200, 'body' => '']],
            'GET /api/v1/athlete/i1/activities' => [['status' => 200, 'body' => '[{"id":"a1","type":"Run","start_date_local":"2026-09-22T07:00:00","moving_time":3000,"distance":8000,"average_heartrate":140,"paired_event_id":5001,"icu_hr_zone_times":[600,2400,0,0,0],"icu_training_load":45},{"id":"a2","type":"Walk","start_date_local":"2026-09-21T18:00:00","moving_time":1800}]']],
            'GET /api/v1/athlete/i1/wellness' => [['status' => 200, 'body' => '[{"id":"2026-09-22","hrv":62,"restingHR":47,"sleepSecs":27000,"ctl":40.2,"atl":45.9},{"id":"2026-09-23","hrv":58,"restingHR":49,"sleepSecs":24000,"ctl":40.8,"atl":47.1}]']],
        ]);
        $this->setupUser();
    }

    public function testToolListAndScopes(): void
    {
        $headers = ['Authorization' => 'Bearer ' . self::STATIC, 'Content-Type' => 'application/json'];
        $this->mcpTool(self::STATIC, 'get_athlete_profile');
        $r = $this->request('POST', '/mcp', [], $headers + ['Mcp-Session-Id' => $this->sessionFor(self::STATIC), 'MCP-Protocol-Version' => '2025-06-18'], '{"jsonrpc":"2.0","id":3,"method":"tools/list"}');
        $names = array_column(json_decode($r->body, true)['result']['tools'], 'name');
        foreach (['ping', 'get_week_overview', 'get_session_detail', 'get_pain_history', 'get_wellness_trend', 'get_block', 'get_athlete_profile', 'write_week_plan', 'update_session', 'upsert_block', 'update_athlete_profile', 'find_exercise', 'get_exercise', 'list_exercises', 'upsert_exercise'] as $t) {
            self::assertContains($t, $names);
        }

        $now = $this->clock->now();
        $readOnly = Jwt::encode(['iss' => self::APP_URL, 'aud' => self::APP_URL . '/mcp', 'sub' => '1', 'scope' => 'training:read', 'iat' => time(), 'exp' => time() + 600], self::JWT_SECRET);
        self::assertFalse($this->mcpTool($readOnly, 'get_pain_history')['isError']);
        $denied = $this->mcpTool($readOnly, 'upsert_block', ['block' => ['name' => 'X', 'start_date' => '2026-09-21', 'end_date' => '2026-10-18']]);
        self::assertTrue($denied['isError']);
        self::assertStringContainsString('training:write', $denied['text']);
        unset($now);
    }

    public function testBlockWeekPlanOverviewAndAudit(): void
    {
        // Ohne Block kein Wochenplan
        $noBlock = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'sessions' => [$this->kraft('2026-09-21')]]);
        self::assertTrue($noBlock['isError']);
        self::assertStringContainsString('upsert_block', $noBlock['text']);

        $block = $this->mcpTool(self::STATIC, 'upsert_block', ['block' => ['name' => 'Grundlage Herbst', 'start_date' => '2026-09-21', 'end_date' => '2026-11-15', 'status' => 'aktiv', 'goal_events' => [['name' => 'Skitour', 'date' => '2027-02-01']], 'doc_ref' => 'docs/plaene/block-01.md']]);
        self::assertFalse($block['isError'], $block['text']);

        // Ungültiger Plan: nichts geschrieben, alle Fehler gemeldet
        $bad = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'sessions' => [
            ['date' => '2026-09-28', 'type' => 'kraft', 'title' => 'X', 'plan_json' => ['exercises' => [['name' => 'A', 'sets' => 3, 'reps' => '8']]]],
            ['date' => '2026-09-22', 'type' => 'ausdauer', 'title' => 'Lauf', 'plan_json' => ['summary' => 'x']],
        ]]);
        self::assertTrue($bad['isError']);
        self::assertStringContainsString('sessions[0]', $bad['text']);
        self::assertStringContainsString('sessions[1]', $bad['text']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM `session`')->fetchColumn());

        $plan = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'sessions' => [
            $this->kraft('2026-09-21'),
            $this->lauf('2026-09-22', 'Lauf locker Z2'),
            ['date' => '2026-09-24', 'type' => 'ausdauer', 'title' => 'Intervalle', 'planned_duration_min' => 60, 'plan_json' => ['intervals_workout_text' => "- 15m Z1 HR\n\n5x\n- 4m Z4 HR\n- 3m Z1 HR", 'target_type' => 'hf_zone', 'summary' => '5 × 4 min Z4', 'sport' => 'TrailRun'], 'coach_summary' => 'Schwelle bergauf'],
            ['date' => '2026-09-27', 'type' => 'ruhe', 'title' => 'Ruhetag'],
        ]]);
        self::assertFalse($plan['isError'], $plan['text']);
        self::assertSame('ok', $plan['data']['status']);
        self::assertSame([null, 5001, 5002, null], array_map(static fn (array $e) => $e['intervals_event_id'] ?? null, $plan['data']['einheiten']));
        $posted = array_values(array_filter($this->intervalsTransport->requests, static fn (array $r): bool => $r['method'] === 'POST'));
        $event = json_decode((string) $posted[1]['body'], true);
        self::assertSame(['WORKOUT', 'TrailRun', 'Intervalle', '2026-09-24T00:00:00', 3600], [$event['category'], $event['type'], $event['name'], $event['start_date_local'], $event['moving_time']]);
        self::assertStringStartsWith('training-session-', $event['external_id']);
        self::assertSame('bestaetigt', $this->pdo->query('SELECT status FROM training_week')->fetchColumn());

        // Zweites Schreiben ohne replace_existing wird abgelehnt
        self::assertTrue($this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'sessions' => [$this->kraft('2026-09-21')]])['isError']);

        // Rückmeldung zur Krafteinheit, dann Übersicht
        $kraftId = (int) $plan['data']['einheiten'][0]['id'];
        $now = Db::ts($this->clock->now());
        $this->pdo->exec("UPDATE `session` SET status = 'erledigt' WHERE id = $kraftId");
        $this->pdo->exec("INSERT INTO session_execution (session_id, duration_min, rpe_cr10, feel_1_5, deviation_reason, source, created_at, updated_at) VALUES ($kraftId, 60, 6, 2, 'zeit', 'web', '$now', '$now')");
        $this->pdo->exec("INSERT INTO pain_event (date, location, side, intensity_0_10, timing, created_at) VALUES ('2026-09-22', 'knie', 'L', 3, 'danach', '$now')");
        $this->pdo->exec("INSERT INTO checkin (date, recovery_1_5, soreness_1_5, pain_flag, created_at, updated_at) VALUES ('2026-09-22', 2, 3, 1, '$now', '$now')");

        $o = $this->mcpTool(self::STATIC, 'get_week_overview', ['week_start' => '2026-09-23']);
        self::assertFalse($o['isError'], $o['text']);
        $d = $o['data'];
        self::assertSame('2026-09-21', $d['woche']['start']);
        self::assertSame('Grundlage Herbst', $d['woche']['block']);
        self::assertSame(['kraft' => 360], $d['summen']['srpe_je_typ']);
        self::assertSame(33, $d['summen']['compliance_pct']);
        self::assertSame('zeit', $d['einheiten'][0]['abweichung']);
        self::assertEquals(['id' => 'a1', 'typ' => 'Run', 'zuordnung' => 'intervals', 'dauer_min' => 50, 'distanz_km' => 8, 'hf_mittel' => 140, 'hf_zonen_min' => [10, 40, 0, 0, 0], 'load' => 45], $d['einheiten'][1]['aktivitaet']);
        self::assertSame('a2', $d['aktivitaeten_ohne_plan'][0]['id']);
        self::assertSame(['ort' => 'knie', 'seite' => 'L', 'anzahl' => 1, 'max_0_10' => 3, 'verlauf' => ['09-22:3:danach']], $d['schmerz'][0]);
        self::assertSame(33, $d['checkin']['abdeckung_pct']);
        self::assertSame(-6.3, $d['form']['form_tsb']);
        self::assertArrayHasKey('rpe_cr10', $d['skalen']);
        self::assertLessThan(8000, strlen($o['text']), 'Antwortbudget ≈ 2 000 Tokens');

        $actions = $this->pdo->query('SELECT action FROM audit_log WHERE actor = \'mcp\' ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        self::assertSame(['block_create', 'week_plan_write', 'intervals_event_create', 'intervals_event_create'], $actions);
    }

    public function testUpdateSessionReplaceAndIntervalsErrors(): void
    {
        $this->mcpTool(self::STATIC, 'upsert_block', ['block' => ['name' => 'B', 'start_date' => '2026-09-14', 'end_date' => '2026-10-11', 'status' => 'aktiv']]);
        $plan = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'sessions' => [$this->kraft('2026-09-21'), $this->lauf('2026-09-22', 'Lauf'), $this->lauf('2026-09-25', 'Lauf 2')]]);
        [$kraft, $lauf, $lauf2] = array_column($plan['data']['einheiten'], 'id');

        // Verschieben mit Event-Aktualisierung
        $u = $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $lauf, 'changes' => ['date' => '2026-09-23', 'title' => 'Lauf verschoben', 'status' => 'verschoben']]);
        self::assertFalse($u['isError'], $u['text']);
        self::assertSame('event_aktualisiert', $u['data']['intervals']);
        $put = array_values(array_filter($this->intervalsTransport->requests, static fn (array $r): bool => $r['method'] === 'PUT'));
        self::assertSame('2026-09-23T00:00:00', json_decode((string) $put[0]['body'], true)['start_date_local']);

        // Unbekanntes Feld, Woche ohne Plan
        self::assertTrue($this->mcpTool(self::STATIC, 'update_session', ['session_id' => $kraft, 'changes' => ['foo' => 1]])['isError']);
        self::assertStringContainsString('keinen Wochenplan', $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $kraft, 'changes' => ['date' => '2026-10-05']])['text']);

        // Ausgelassen → Event gelöscht
        $a = $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $lauf, 'changes' => ['status' => 'ausgelassen']]);
        self::assertSame('event_geloescht', $a['data']['intervals']);
        self::assertNull($this->pdo->query("SELECT intervals_event_id FROM `session` WHERE id = $lauf")->fetchColumn());

        // Ersetzen: Krafteinheit mit Rückmeldung bleibt, geplanter Lauf 2 wird ersetzt (Event 5002 gelöscht)
        $now = Db::ts($this->clock->now());
        $this->pdo->exec("INSERT INTO session_execution (session_id, duration_min, rpe_cr10, source, created_at, updated_at) VALUES ($kraft, 60, 6, 'web', '$now', '$now')");
        $this->intervalsTransport->responses['POST /api/v1/athlete/i1/events'] = [['status' => 500, 'body' => '{"error":"kaputt"}']];
        $r = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'replace_existing' => true, 'sessions' => [$this->lauf('2026-09-26', 'Neuer Lauf')]]);
        self::assertFalse($r['isError'], $r['text']);
        self::assertSame('teilweise', $r['data']['status']);
        self::assertContains($lauf2, $r['data']['ersetzt']);
        self::assertSame($kraft, $r['data']['behalten'][0]['id']);
        self::assertStringContainsString('HTTP 500', $r['data']['einheiten'][0]['fehler_intervals']);
        $newId = (int) $r['data']['einheiten'][0]['id'];
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM `session` WHERE id = $newId AND intervals_event_id IS NULL")->fetchColumn(), 'Einheit bleibt in der DB');
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'intervals_error'")->fetchColumn());

        // Erneuter Sync über update_session legt das Event an
        $this->intervalsTransport->responses['POST /api/v1/athlete/i1/events'] = [['status' => 200, 'body' => '{"id":5009}']];
        $retry = $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $newId, 'changes' => []]);
        self::assertSame('event_angelegt', $retry['data']['intervals']);
        self::assertSame(5009, (int) $this->pdo->query("SELECT intervals_event_id FROM `session` WHERE id = $newId")->fetchColumn());
    }

    public function testWriteLockAndReadTools(): void
    {
        $this->mcpTool(self::STATIC, 'upsert_block', ['block' => ['name' => 'B', 'start_date' => '2026-09-14', 'end_date' => '2026-10-11', 'status' => 'aktiv']]);
        $block = $this->mcpTool(self::STATIC, 'get_block');
        self::assertSame(2, $block['data']['aktuelle_woche']);
        self::assertSame(4, $block['data']['wochen_gesamt']);

        $now = Db::ts($this->clock->now());
        foreach (['2026-09-10' => 2, '2026-09-18' => 3, '2026-09-22' => 5] as $date => $int) {
            $this->pdo->exec("INSERT INTO pain_event (date, location, side, intensity_0_10, timing, created_at) VALUES ('$date', 'finger_ringband', 'R', $int, 'danach', '$now')");
        }
        $pain = $this->mcpTool(self::STATIC, 'get_pain_history', ['days' => 28]);
        self::assertSame('finger_ringband', $pain['data']['orte'][0]['ort']);
        self::assertSame(3, $pain['data']['orte'][0]['anzahl']);
        self::assertSame('steigend', $pain['data']['orte'][0]['trend_7_tage']);

        $w = $this->mcpTool(self::STATIC, 'get_wellness_trend', ['days' => 7]);
        self::assertFalse($w['isError'], $w['text']);
        self::assertCount(7, $w['data']['tage']);
        self::assertEquals(['datum' => '2026-09-23', 'hrv' => 58, 'ruhepuls' => 49, 'schlaf_h' => 6.7], end($w['data']['tage']));
        self::assertEquals(60, $w['data']['baseline']['hrv_7d']);

        $profile = $this->mcpTool(self::STATIC, 'get_athlete_profile');
        self::assertFalse($profile['data']['vorhanden']);
        self::assertCount(6, $profile['data']['abschnitte']);

        self::assertTrue($this->mcpTool(self::STATIC, 'get_session_detail', ['session_id' => 999])['isError']);

        // Schreibsperre
        $this->rollbackLastMigration();
        $locked = $this->mcpTool(self::STATIC, 'upsert_block', ['block' => ['name' => 'C', 'start_date' => '2026-10-12', 'end_date' => '2026-11-08']]);
        self::assertTrue($locked['isError']);
        self::assertStringContainsString('Update erforderlich', $locked['text']);
    }

    /** AP-13 T2 (E-01, E-10): Kurzsatz und Begründung je Woche und Einheit – Pflicht, Grenzlängen, Lese-Tools. */
    public function testPlanTextsAreRequiredLimitedAndReadable(): void
    {
        $this->mcpTool(self::STATIC, 'upsert_block', ['block' => ['name' => 'B', 'start_date' => '2026-09-14', 'end_date' => '2026-10-11', 'status' => 'aktiv']]);
        $ruhe = ['date' => '2026-09-27', 'type' => 'ruhe', 'title' => 'Ruhetag'];
        $ohneKurz = ['coach_summary' => null] + $this->kraft('2026-09-21');

        // Pflichtfelder fehlen: Fehler mit Liste der betroffenen Einheiten, Ruhetag braucht keinen Kurzsatz, nichts geschrieben
        $missing = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => '   ', 'sessions' => [$ohneKurz, $ruhe, ['coach_summary' => ''] + $this->lauf('2026-09-22', 'Lauf')]]);
        self::assertTrue($missing['isError']);
        $details = implode("\n", $missing['data']['details']);
        self::assertStringContainsString('focus fehlt', $details);
        self::assertMatchesRegularExpression('/^sessions\[0\]: .*coach_summary fehlt/m', $details);
        self::assertMatchesRegularExpression('/^sessions\[2\]: .*coach_summary fehlt/m', $details);
        self::assertStringNotContainsString('sessions[1]', $details, 'Ruhetag ohne Kurzsatz ist erlaubt');
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM `session`')->fetchColumn());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM training_week')->fetchColumn());

        // Grenzlängen in Zeichen (Umlaute zählen einfach): 255/200/1500 erlaubt, eins mehr nicht
        $tooLong = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => str_repeat('ä', 256), 'coach_notes' => str_repeat('ö', 1501),
            'sessions' => [['coach_summary' => str_repeat('ü', 201), 'coach_rationale' => str_repeat('ß', 1501)] + $this->kraft('2026-09-21')]]);
        self::assertTrue($tooLong['isError']);
        $details = implode("\n", $tooLong['data']['details']);
        foreach (['focus ist 256 Zeichen lang, erlaubt sind 255', 'coach_notes ist 1501 Zeichen lang, erlaubt sind 1500', 'coach_summary ist 201 Zeichen lang, erlaubt sind 200', 'coach_rationale ist 1501 Zeichen lang, erlaubt sind 1500'] as $msg) {
            self::assertStringContainsString($msg, $details);
        }

        $ok = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => ' ' . str_repeat('ä', 255) . ' ', 'coach_notes' => str_repeat('ö', 1500),
            'sessions' => [['coach_summary' => str_repeat('ü', 200), 'coach_rationale' => str_repeat('ß', 1500)] + $this->kraft('2026-09-21'), $ruhe, ['date' => '2026-09-26', 'type' => 'ruhe', 'title' => 'Ruhe', 'coach_summary' => 'Ruhe vor dem langen Lauf']]]);
        self::assertFalse($ok['isError'], $ok['text']);
        [$kraftId, $ruheId, $ruhe2Id] = array_column($ok['data']['einheiten'], 'id');
        $week = $this->pdo->query('SELECT focus, coach_notes FROM training_week')->fetch();
        self::assertSame(str_repeat('ä', 255), $week['focus'], 'getrimmt gespeichert');
        self::assertSame(1500, mb_strlen((string) $week['coach_notes']));
        self::assertSame(str_repeat('ü', 200), $this->pdo->query('SELECT coach_summary FROM `session` WHERE id = ' . $kraftId)->fetchColumn());
        self::assertNull($this->pdo->query('SELECT coach_summary FROM `session` WHERE id = ' . $ruheId)->fetchColumn());

        // Lese-Tools: fokus, begruendung, kurz je Einheit; Detail mit coach_summary
        $o = $this->mcpTool(self::STATIC, 'get_week_overview', ['week_start' => '2026-09-21'])['data'];
        self::assertSame(str_repeat('ä', 255), $o['woche']['fokus']);
        self::assertSame(str_repeat('ö', 1500), $o['woche']['begruendung']);
        self::assertSame(str_repeat('ü', 200), $o['einheiten'][0]['kurz']);
        $rows = array_column($o['einheiten'], null, 'id');
        self::assertArrayNotHasKey('kurz', $rows[$ruheId], 'Ruhetag ohne Kurzsatz');
        self::assertSame(['id' => $ruhe2Id, 'datum' => '2026-09-26', 'typ' => 'ruhe', 'kurz' => 'Ruhe vor dem langen Lauf'], $rows[$ruhe2Id], 'Ruhetag mit Kurzsatz');
        $d = $this->mcpTool(self::STATIC, 'get_session_detail', ['session_id' => $kraftId])['data'];
        self::assertSame([str_repeat('ü', 200), str_repeat('ß', 1500)], [$d['coach_summary'], $d['coach_rationale']]);

        // update_session: Kurzsatz ändern, leerer Kurzsatz abgelehnt, Begründung leeren, Wochenfelder nicht hier
        $u = $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $kraftId, 'changes' => ['coach_summary' => 'Last bleibt, Tiefe sauber', 'coach_rationale' => '']]);
        self::assertFalse($u['isError'], $u['text']);
        self::assertSame(['coach_summary', 'coach_rationale'], $u['data']['geaendert']);
        $row = $this->pdo->query('SELECT coach_summary, coach_rationale FROM `session` WHERE id = ' . $kraftId)->fetch();
        self::assertSame(['Last bleibt, Tiefe sauber', null], [$row['coach_summary'], $row['coach_rationale']]);
        self::assertStringContainsString('coach_summary darf nicht leer sein', $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $kraftId, 'changes' => ['coach_summary' => ' ']])['text']);
        self::assertFalse($this->mcpTool(self::STATIC, 'update_session', ['session_id' => $ruhe2Id, 'changes' => ['coach_summary' => '']])['isError'], 'Ruhetag: leer entfernt den Kurzsatz');
        self::assertNull($this->pdo->query('SELECT coach_summary FROM `session` WHERE id = ' . $ruhe2Id)->fetchColumn());
        self::assertStringContainsString('201 Zeichen', $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $kraftId, 'changes' => ['coach_summary' => str_repeat('x', 201)]])['text']);
        self::assertStringContainsString('Unbekannte Felder: focus', $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $kraftId, 'changes' => ['focus' => 'x']])['text']);

        // Altdaten: überlange Begründung aus der Zeit vor AP-13 blockiert andere Änderungen nicht und fehlt in der Übersicht
        $this->pdo->exec("UPDATE `session` SET coach_rationale = REPEAT('a', 2000), coach_summary = NULL WHERE id = " . $kraftId);
        $this->pdo->exec("UPDATE training_week SET coach_notes = REPEAT('b', 1600)");
        self::assertFalse($this->mcpTool(self::STATIC, 'update_session', ['session_id' => $kraftId, 'changes' => ['title' => 'Kraft neu']])['isError']);
        $o = $this->mcpTool(self::STATIC, 'get_week_overview', ['week_start' => '2026-09-21'])['data'];
        self::assertArrayNotHasKey('begruendung', $o['woche'], 'Antwortbudget 8.3');
        self::assertArrayNotHasKey('kurz', $o['einheiten'][0]);
    }

    private function sessionFor(string $token): string
    {
        $r = new \ReflectionProperty(\Training\Tests\Support\AppTestCase::class, 'mcpSessions');

        return $r->getValue($this)[$token];
    }

    /** @return array<string, mixed> */
    private function kraft(string $date): array
    {
        return ['date' => $date, 'type' => 'kraft', 'title' => 'Kraft', 'priority' => 'B', 'planned_duration_min' => 60,
            'plan_json' => ['exercises' => [['name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8', 'load' => '60 kg']]], 'coach_summary' => 'Grundkraft Beine', 'coach_rationale' => 'Grundkraft'];
    }

    /** @return array<string, mixed> */
    private function lauf(string $date, string $title): array
    {
        return ['date' => $date, 'type' => 'ausdauer', 'title' => $title, 'priority' => 'A', 'planned_duration_min' => 50,
            'plan_json' => ['intervals_workout_text' => '- 50m Z2 HR', 'target_type' => 'hf_zone', 'summary' => '50 min Z2'], 'coach_summary' => 'Lockerer Grundlagenlauf'];
    }
}
