<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use PDO;
use Training\Db;
use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeTransport;

/** AP-04: Woche (S2), Einheit (S3), Check-in (S4), Schmerz (S5), Einstellungen (S8). */
final class WebsiteTest extends AppTestCase
{
    /** 2026-09-23 12:00 UTC = Mittwoch der Beispielwoche */
    private const NOW = 1790164800;

    /** @var list<int> */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->ids = $this->insertExampleWeek();
        $this->setupUser();
    }

    public function testPagesRequireLogin(): void
    {
        $this->cookies = [];
        foreach (['/woche', '/einheit?id=1', '/checkin', '/schmerz', '/einstellungen', '/verlauf'] as $path) {
            $r = $this->request('GET', $path);
            self::assertSame(303, $r->status, $path);
            self::assertStringStartsWith('/login?next=', $r->headers['Location']);
        }
    }

    public function testWeekShowsAllSessionsTodayAndEmptyWeek(): void
    {
        $r = $this->request('GET', '/woche');
        self::assertSame(200, $r->status, $r->body);
        foreach (['Kraft Unterkörper', 'Lauf locker Z2', 'Hangboard + Bouldern Volumen', 'Haltung und Rumpf', 'Mobilität Hüfte und Schulter', 'Intervalle Berg', 'Ruhetag'] as $title) {
            self::assertStringContainsString($this->html($title), $r->body);
        }
        self::assertStringContainsString('21. – 27. September', $r->body);
        self::assertStringContainsString('KW 39', $r->body);
        self::assertStringContainsString('„Block 2 Grundlage Herbst“, Woche 1 von 8', $r->body);
        self::assertStringContainsString('23.09. · heute', $r->body);
        self::assertStringContainsString('Check-in offen', $r->body);
        self::assertStringContainsString('auf der Uhr', $r->body);

        $empty = $this->request('GET', '/woche?start=2026-10-12');
        self::assertStringContainsString('Noch kein Plan für diese Woche', $empty->body);
        self::assertStringContainsString('/woche?start=2026-10-05', $empty->body, 'Navigation ±Woche');
    }

    public function testCompleteStrengthSessionWithActualValuesFeedbackAndPain(): void
    {
        $id = $this->ids[0];
        $form = $this->request('GET', '/einheit?id=' . $id);
        self::assertSame(200, $form->status);
        self::assertStringContainsString('Soll 3 × 6-8 · 60 kg · Tempo 3-1-1 · Pause 150 s', html_entity_decode($form->body));
        self::assertStringContainsString('name="ist[0][load]" value="60 kg"', $form->body, 'Ist mit Soll vorbelegt');

        $r = $this->request('POST', '/einheit', [
            'csrf' => self::csrfFrom($form), 'id' => (string) $id, 'status' => 'teilweise', 'duration_min' => '55', 'rpe' => '7', 'feel' => '2',
            'ist' => [['sets' => '2', 'reps' => '8', 'load' => '62,5 kg'], ['sets' => '3', 'reps' => '8', 'load' => '50 kg'], ['sets' => '0', 'reps' => '', 'load' => '']],
            'pain' => 'ja', 'pain_location' => 'knie', 'pain_side' => 'L', 'pain_intensity' => '3', 'pain_timing' => 'danach',
            'deviation' => 'zeit', 'notes' => 'Letzte Übung weggelassen',
        ]);
        self::assertSame(303, $r->status, $r->body);
        self::assertSame('/woche?start=2026-09-21&ok=einheit', $r->headers['Location']);

        $e = $this->pdo->query('SELECT * FROM session_execution WHERE session_id = ' . $id)->fetch();
        self::assertSame(385, (int) $e['srpe_load']);
        self::assertSame('zeit', $e['deviation_reason']);
        $actual = json_decode((string) $e['actual_json'], true);
        self::assertEquals(['name' => 'Kniebeuge', 'sets' => 2, 'reps' => '8', 'load' => '62,5 kg'], $actual['exercises'][0], 'Schlüsselreihenfolge egal (MySQL sortiert JSON-Schlüssel)');
        self::assertSame(0, $actual['exercises'][2]['sets']);
        self::assertSame('teilweise', $this->pdo->query('SELECT status FROM `session` WHERE id = ' . $id)->fetchColumn());
        $pain = $this->pdo->query('SELECT * FROM pain_event')->fetch();
        self::assertSame(['2026-09-21', $id, 'knie', 'L', 3, 'danach'], [$pain['date'], (int) $pain['session_id'], $pain['location'], $pain['side'], (int) $pain['intensity_0_10'], $pain['timing']]);
        self::assertSame(['pain_create', 'session_feedback'], $this->pdo->query('SELECT action FROM audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN));

        // Woche zeigt Status und sRPE, erneutes Öffnen zeigt die gespeicherten Werte.
        $week = $this->request('GET', '/woche?start=2026-09-21&ok=einheit');
        self::assertStringContainsString('Gespeichert.', $week->body);
        self::assertStringContainsString('sRPE 385', $week->body);
        self::assertStringContainsString('teilweise', $week->body);
        $again = $this->request('GET', '/einheit?id=' . $id);
        self::assertStringContainsString('name="ist[0][load]" value="62,5 kg"', $again->body);
        self::assertMatchesRegularExpression('/name="rpe" value="7" checked/', $again->body);
    }

    public function testValidationErrorsKeepInput(): void
    {
        $id = $this->ids[0];
        $form = $this->request('GET', '/einheit?id=' . $id);
        $r = $this->request('POST', '/einheit', [
            'csrf' => self::csrfFrom($form), 'id' => (string) $id, 'status' => 'erledigt', 'duration_min' => '', 'feel' => '9',
            'ist' => [['sets' => 'drei']], 'pain' => 'ja', 'pain_location' => '', 'notes' => 'bleibt stehen',
        ]);
        self::assertSame(422, $r->status);
        $body = html_entity_decode($r->body);
        foreach (['Bitte die Anstrengung', 'Bitte angeben, wie sich', 'Bitte die Dauer', '„drei“ ist kein gültiger Wert', 'Bitte den Ort', 'Bitte die Stärke', 'Bitte angeben, wann'] as $msg) {
            self::assertStringContainsString($msg, $body);
        }
        self::assertStringContainsString('bleibt stehen', $r->body);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM session_execution')->fetchColumn());
        self::assertSame(403, $this->request('POST', '/einheit', ['csrf' => 'x', 'id' => (string) $id, 'status' => 'erledigt'])->status);
    }

    public function testSkippedSessionNeedsNoRpe(): void
    {
        $id = $this->ids[4];
        $form = $this->request('GET', '/einheit?id=' . $id);
        $r = $this->request('POST', '/einheit', ['csrf' => self::csrfFrom($form), 'id' => (string) $id, 'status' => 'ausgelassen', 'deviation' => 'ermuedung', 'pain' => 'nein']);
        self::assertSame(303, $r->status, $r->body);
        $e = $this->pdo->query('SELECT * FROM session_execution WHERE session_id = ' . $id)->fetch();
        self::assertNull($e['rpe_cr10']);
        self::assertNull($e['srpe_load']);
        self::assertSame('ermuedung', $e['deviation_reason']);
    }

    public function testRestDayAndUnknownSessionAre404(): void
    {
        self::assertSame(404, $this->request('GET', '/einheit?id=' . $this->ids[6])->status);
        self::assertSame(404, $this->request('GET', '/einheit?id=999999')->status);
    }

    public function testEnduranceSessionShowsLinkedActivity(): void
    {
        $this->writeEnv(['INTERVALS_API_KEY' => 'k', 'INTERVALS_ATHLETE_ID' => 'i1']);
        $this->intervalsTransport = $t = new FakeTransport(['GET /api/v1/athlete/i1/activities' => [['status' => 200, 'body' => (string) json_encode([
            ['id' => 'i777', 'type' => 'Run', 'name' => 'Morgenlauf', 'start_date_local' => '2026-09-22T07:12:00', 'moving_time' => 3492, 'distance' => 9800,
             'average_heartrate' => 152.4, 'average_speed' => 2.8, 'paired_event_id' => 100001, 'icu_hr_zone_times' => [1560, 480, 1260, 180, 0]],
        ])]]]);
        $r = $this->request('GET', '/einheit?id=' . $this->ids[1]);
        self::assertSame(200, $r->status, $r->body);
        foreach (['Morgenlauf', '58:12', '9,8', '152', 'zugeordnet', 'Zone 3: 21 min', 'intervals.icu/activities/i777'] as $s) {
            self::assertStringContainsString($s, $r->body);
        }
        self::assertStringContainsString('name="duration_min" value="58"', $r->body, 'Dauer aus der Aktivität vorbelegt');
        $week = $this->request('GET', '/woche');
        self::assertStringContainsString('Aktivität vorhanden', $week->body);
        $this->request('GET', '/woche');
        self::assertCount(2, array_filter($t->requests, static fn (array $q): bool => str_contains($q['url'], '/activities')), 'Einheit und Woche je ein Abruf, zweite Wochenansicht aus dem Cache');
    }

    public function testCheckinCreateUpdateWithPain(): void
    {
        $form = $this->request('GET', '/checkin');
        self::assertStringContainsString('Wie geht es Dir heute?', $form->body);
        $r = $this->request('POST', '/checkin', ['csrf' => self::csrfFrom($form), 'datum' => '2026-09-23', 'recovery' => '2', 'soreness' => '3', 'pain' => 'nein']);
        self::assertSame('/woche?start=2026-09-21&ok=checkin', $r->headers['Location']);
        $r = $this->request('POST', '/checkin', ['csrf' => self::csrfFrom($form), 'datum' => '2026-09-23', 'recovery' => '4', 'soreness' => '2', 'pain' => 'ja',
            'pain_location' => 'schulter', 'pain_side' => 'L', 'pain_intensity' => '3', 'pain_timing' => 'naechster_morgen', 'notes' => 'wenig Schlaf']);
        self::assertSame(303, $r->status, $r->body);
        $c = $this->pdo->query('SELECT * FROM checkin')->fetchAll();
        self::assertCount(1, $c);
        self::assertSame([4, 2, 1, 'wenig Schlaf'], [(int) $c[0]['recovery_1_5'], (int) $c[0]['soreness_1_5'], (int) $c[0]['pain_flag'], $c[0]['notes']]);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM pain_event WHERE session_id IS NULL')->fetchColumn());
        self::assertStringContainsString('Schulter links 3', $this->request('GET', '/checkin')->body);
        self::assertStringContainsString('Check-in', $this->request('GET', '/woche')->body);

        // Zukunft nicht erlaubt → heute; fehlende Werte → 422
        $future = $this->request('GET', '/checkin?datum=2026-12-24');
        self::assertStringContainsString('Mittwoch, 23.09.2026', $future->body);
        self::assertSame(422, $this->request('POST', '/checkin', ['csrf' => self::csrfFrom($form), 'recovery' => '6', 'pain' => 'nein'])->status);
    }

    public function testPainFormWithSessionAndRepeatWarning(): void
    {
        $form = $this->request('GET', '/schmerz?einheit=' . $this->ids[2]);
        self::assertMatchesRegularExpression('/value="' . $this->ids[2] . '" selected/', $form->body);
        for ($i = 0; $i < 2; $i++) {
            $r = $this->request('POST', '/schmerz', ['csrf' => self::csrfFrom($form), 'datum' => '2026-09-23', 'pain_location' => 'finger_ringband', 'pain_side' => 'R',
                'pain_intensity' => '2', 'pain_timing' => 'danach', 'session_id' => (string) $this->ids[2]]);
            self::assertSame(303, $r->status, $r->body);
        }
        $third = $this->request('POST', '/schmerz', ['csrf' => self::csrfFrom($form), 'datum' => '2026-09-23', 'pain_location' => 'finger_ringband', 'pain_side' => 'R',
            'pain_intensity' => '2', 'pain_timing' => 'danach']);
        self::assertSame(200, $third->status);
        self::assertStringContainsString('Dritte Meldung an dieser Stelle in 14 Tagen', $third->body);
        self::assertSame(3, (int) $this->pdo->query("SELECT COUNT(*) FROM pain_event WHERE location = 'finger_ringband'")->fetchColumn());
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM pain_event WHERE session_id = ' . $this->ids[2])->fetchColumn());
    }

    public function testSettingsTimezonePasswordAndRevoke(): void
    {
        $page = $this->request('GET', '/einstellungen');
        self::assertSame(200, $page->status);
        foreach (['Angemeldet als philipp', 'Code 14 · Datenbank 14', 'Nicht eingerichtet', 'nicht verbunden'] as $s) {
            self::assertStringContainsString($s, $page->body);
        }
        $csrf = self::csrfFrom($page);

        self::assertSame('/einstellungen?ok=zeitzone', $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'zeitzone', 'tz' => 'Europe/Zurich'])->headers['Location']);
        self::assertSame('Europe/Zurich', $this->pdo->query('SELECT tz FROM `user`')->fetchColumn());
        self::assertSame(422, $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'zeitzone', 'tz' => 'Mars/Olympus'])->status);

        // Zweite Session (anderes Gerät) wird beim Passwortwechsel beendet.
        $mine = $this->cookies;
        $this->cookies = [];
        $login = $this->request('GET', '/login');
        $this->request('POST', '/login', ['csrf' => self::csrfFrom($login), 'login' => 'philipp', 'password' => 'richtig-langes-passwort', 'next' => '']);
        $this->cookies = $mine;
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM web_session')->fetchColumn());
        self::assertSame(422, $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'passwort', 'current' => 'falsch', 'password' => 'neues-langes-passwort', 'password2' => 'neues-langes-passwort'])->status);
        $ok = $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'passwort', 'current' => 'richtig-langes-passwort', 'password' => 'neues-langes-passwort', 'password2' => 'neues-langes-passwort']);
        self::assertSame('/einstellungen?ok=passwort', $ok->headers['Location']);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM web_session')->fetchColumn());
        self::assertTrue(password_verify('neues-langes-passwort', (string) $this->pdo->query('SELECT password_hash FROM `user`')->fetchColumn()));

        // OAuth-Freigabe widerrufen
        $now = Db::ts($this->clock->now());
        $this->pdo->exec("INSERT INTO oauth_client (client_id, client_name, redirect_uris_json, created_at) VALUES ('c1', 'Claude', '[\"https://claude.ai/api/mcp/auth_callback\"]', '$now')");
        $this->pdo->exec("INSERT INTO oauth_token (token_hash, type, client_id, user_id, family_id, scope, created_at, expires_at) VALUES ('" . str_repeat('a', 64) . "', 'refresh', 'c1', 1, 'f', 'training:read training:write', '$now', '2027-01-01 00:00:00')");
        $page = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('Claude (claude.ai)', $page->body);
        self::assertStringContainsString('Lesen und Schreiben', $page->body);
        $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'widerrufen', 'client_id' => 'c1']);
        self::assertSame(1, (int) $this->pdo->query('SELECT revoked FROM oauth_token')->fetchColumn());
        self::assertStringNotContainsString('Claude (claude.ai)', $this->request('GET', '/einstellungen')->body);
        self::assertSame(['oauth_revoke', 'user_password', 'user_timezone'], $this->pdo->query('SELECT action FROM audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN));
    }

    public function testHistoryShowsWeeklyLoadPainHeatAndTable(): void
    {
        $now = Db::ts($this->clock->now());
        // Krafteinheit (Mo 21.09.) und Klettern (Mi 23.09.) mit Rückmeldung
        $this->pdo->exec("INSERT INTO session_execution (session_id, duration_min, rpe_cr10, source, created_at, updated_at) VALUES ({$this->ids[0]}, 60, 5, 'web', '$now', '$now'), ({$this->ids[2]}, 90, 6, 'web', '$now', '$now')");
        $this->pdo->exec("INSERT INTO pain_event (date, location, side, intensity_0_10, timing, created_at) VALUES ('2026-09-22', 'schulter', 'L', 4, 'danach', '$now'), ('2026-09-23', 'schulter', 'L', 2, 'waehrend', '$now'), ('2026-09-01', 'knie', 'na', 7, 'ruhe', '$now')");
        $this->pdo->exec("INSERT INTO checkin (date, recovery_1_5, soreness_1_5, pain_flag, created_at, updated_at) VALUES ('2026-09-22', 2, 2, 1, '$now', '$now')");
        $r = $this->request('GET', '/verlauf');
        self::assertSame(200, $r->status, $r->body);
        self::assertStringContainsString('KW 32–39', $r->body);
        self::assertStringContainsString('data-v="300"', $r->body, 'Kraft 5 × 60');
        self::assertStringContainsString('data-v="540"', $r->body, 'Klettern 6 × 90');
        self::assertStringContainsString('gleiche Achse, 0 – 600', $r->body);
        self::assertMatchesRegularExpression('/class="h50 cur" data-v="300"/', $r->body, 'Achse 600 → 50 %');
        self::assertStringContainsString('Schulter links', $r->body);
        self::assertStringContainsString('data-i="4" data-t="KW 39: 4, danach"', $r->body, 'stärkste Meldung der Woche');
        self::assertStringContainsString('data-i="7" data-t="KW 36: 7, in ruhe"', $r->body);
        self::assertStringContainsString('<td>39</td><td>0</td><td>540</td><td>300</td><td>0</td><td>840</td><td>1/3</td><td>2</td>', str_replace(' ', '', $r->body) === '' ? '' : preg_replace('/\s+(?=<)/', '', $r->body));
    }

    private function html(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES);
    }

    /** @return list<int> */
    private function insertExampleWeek(): array
    {
        $data = json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/beispielwoche.json'), true);
        $now = Db::ts($this->clock->now());
        $b = $data['block'];
        $this->pdo->prepare('INSERT INTO training_block (name, start_date, end_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$b['name'], $b['start_date'], $b['end_date'], $b['status'], $now, $now]);
        $blockId = (int) $this->pdo->lastInsertId();
        $w = $data['week'];
        $this->pdo->prepare('INSERT INTO training_week (block_id, week_start, focus, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$blockId, $w['week_start'], $w['focus'], $w['status'], $w['created_by'], $now, $now]);
        $weekId = (int) $this->pdo->lastInsertId();
        $ids = [];
        $insert = $this->pdo->prepare('INSERT INTO `session` (week_id, date, type, title, priority, planned_duration_min, intervals_event_id, plan_json, status, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($data['sessions'] as $i => $s) {
            $insert->execute([$weekId, $s['date'], $s['type'], $s['title'], $s['priority'], $s['planned_duration_min'], $s['intervals_event_id'] ?? null,
                $s['plan_json'] === null ? null : json_encode($s['plan_json'], JSON_UNESCAPED_UNICODE), 'geplant', $i, $now, $now]);
            $ids[] = (int) $this->pdo->lastInsertId();
        }

        return $ids;
    }
}
