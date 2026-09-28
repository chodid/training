<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Db;
use Training\Tests\Support\AppTestCase;

/** AP-14 T4: S9 geführte Einheit ohne Skript – Einstieg aus S3, Aufbau, Speichern über POST /einheit, Rückfall auf S3. */
final class GuidedSessionTest extends AppTestCase
{
    /** 2026-09-23 12:00 UTC = Mittwoch der Beispielwoche */
    private const NOW = 1790164800;

    /** @var array<string, int> Einheiten der Beispielwoche nach Typ */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->ids = $this->insertExampleWeek();
        $this->setupUser();
    }

    public function testStartButtonOnlyForSuitableTypes(): void
    {
        $kraft = $this->request('GET', '/einheit?id=' . $this->ids['kraft']);
        self::assertMatchesRegularExpression('#<a class="btn btn-primary" href="/einheit\?id=' . $this->ids['kraft'] . '&amp;modus=start"><svg[^>]*>.*?</svg>Einheit starten</a>#s', $kraft->body);
        foreach (['klettern', 'haltung', 'mobilitaet'] as $type) {
            self::assertStringContainsString('modus=start', $this->request('GET', '/einheit?id=' . $this->ids[$type])->body, $type);
        }
        self::assertStringNotContainsString('modus=start', $this->request('GET', '/einheit?id=' . $this->ids['ausdauer'])->body, 'Ausdauer läuft auf der Uhr');

        // Erledigt: „Erneut durchgehen“ als Sekundärknopf
        $this->pdo->exec("UPDATE `session` SET status = 'erledigt' WHERE id = " . $this->ids['kraft']);
        self::assertMatchesRegularExpression('#<a class="btn btn-secondary" href="/einheit\?id=\d+&amp;modus=start"><svg[^>]*>.*?</svg>Erneut durchgehen</a>#s', $this->request('GET', '/einheit?id=' . $this->ids['kraft'])->body);
    }

    public function testA11UnsuitableSessionsFallBackToS3(): void
    {
        $ausdauer = $this->request('GET', '/einheit?id=' . $this->ids['ausdauer'] . '&modus=start');
        self::assertSame(200, $ausdauer->status);
        self::assertStringNotContainsString('data-gefuehrt', $ausdauer->body);
        self::assertStringContainsString('<h2>Rückmeldung</h2>', $ausdauer->body, 'S3');
        self::assertSame(404, $this->request('GET', '/einheit?id=' . $this->ids['ruhe'] . '&modus=start')->status);

        // E-13: ohne Plan kein Startknopf, modus=start zeigt S3
        $this->pdo->exec('UPDATE `session` SET plan_json = NULL WHERE id = ' . $this->ids['mobilitaet']);
        self::assertStringNotContainsString('modus=start', $this->request('GET', '/einheit?id=' . $this->ids['mobilitaet'])->body, 'kein Startknopf ohne Plan');
        $ohnePlan = $this->request('GET', '/einheit?id=' . $this->ids['mobilitaet'] . '&modus=start');
        self::assertSame(200, $ohnePlan->status);
        self::assertStringNotContainsString('data-gefuehrt', $ohnePlan->body);
        self::assertStringContainsString('<h2>Rückmeldung</h2>', $ohnePlan->body, 'S3');
    }

    public function testStrengthStepsWithRepetitionsAndHolds(): void
    {
        $this->pdo->exec("UPDATE `session` SET coach_summary = 'Haltung erhaltend' WHERE id = " . $this->ids['haltung']);
        $r = $this->request('GET', '/einheit?id=' . $this->ids['haltung'] . '&modus=start');
        self::assertSame(200, $r->status, $r->body);
        $body = $r->body;
        self::assertStringContainsString('<title>Haltung und Rumpf – Training</title>', $body);
        self::assertStringContainsString('<main class="main gefuehrt">', $body);
        self::assertStringContainsString('href="/einheit?id=' . $this->ids['haltung'] . '" aria-label="Zurück zur Einheit"', $body);
        self::assertStringContainsString('id="gf-stumm" aria-pressed="false"', $body, 'Stummschalter in der Kopfzeile');
        self::assertStringContainsString('<p class="kurz gf-intro mb-4">Haltung erhaltend</p>', $body, 'Kurzsatz im Startschritt (5.3)');

        // Ablaufplan als JSON und ein Abschnitt je Übung plus Abschluss
        preg_match('/data-ablauf="([^"]+)"/', $body, $m);
        $steps = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
        self::assertSame(['wiederholungen', 'halten'], array_column($steps, 'art'));
        self::assertSame(30, $steps[1]['arbeit_s']);
        self::assertSame(2, substr_count($body, 'class="gf-step stack-lg" data-step="') - 1, 'zwei Übungen + Abschluss');
        self::assertStringContainsString('data-step="abschluss"', $body);
        self::assertStringContainsString('<div class="reps">15 Wdh.<small>Band grün</small></div>', $body, 'Wiederholungen ohne Timer');
        self::assertStringContainsString('<div class="timer" data-sekunden="30">00:30</div>', $body, 'Halten mit Timer');
        self::assertStringContainsString('Als Nächstes: <b>Seitstütz</b> · 3 × 30s · KG', $body);
        self::assertStringContainsString('Als Nächstes: <b>Abschluss</b>', $body);

        // Feldnamen wie S3, vorbelegt mit Soll; Rückmeldung wie S3; Skript-Bedienung ohne JavaScript ausgeblendet
        foreach (['ist[0][sets]" value="3"', 'ist[0][reps]" value="15"', 'ist[0][load]" value="Band grün"', 'ist[1][reps]" value="30s"'] as $field) {
            self::assertStringContainsString('name="' . $field, $body);
        }
        foreach (['name="duration_min"', 'name="rpe"', 'name="feel"', 'name="pain"', 'name="deviation"', 'name="notes"', 'name="status"', 'name="stand"', 'name="offline_label"', 'name="modus" value="start"', 'data-offline-form'] as $field) {
            self::assertStringContainsString($field, $body);
        }
        self::assertMatchesRegularExpression('/<div class="actions-sticky gf-actions needs-js" id="gf-aktionen" hidden>/', $body);
        self::assertStringContainsString('data-ton="an"', $body, 'Standard Timer-Signale an (E-18)');
        self::assertStringContainsString('data-dauer-plan="1"', $body, 'Dauer aus dem Plan darf das Skript ersetzen');
    }

    public function testClimbingStepsHangboardBlockAndOpen(): void
    {
        $plan = ['blocks' => [
            ['kind' => 'hangboard', 'edge_mm' => 20, 'grip' => 'halbkrimp', 'hang_s' => 7, 'rest_s' => 3, 'sets' => 6],
            ['kind' => 'bouldern_volumen', 'duration_min' => 40, 'target' => 'Grad 5'],
            ['kind' => 'technik', 'notes' => 'Fußtechnik'],
        ]];
        $this->pdo->prepare('UPDATE `session` SET plan_json = ? WHERE id = ?')->execute([json_encode($plan), $this->ids['klettern']]);
        $body = $this->request('GET', '/einheit?id=' . $this->ids['klettern'] . '&modus=start')->body;
        preg_match('/data-ablauf="([^"]+)"/', $body, $m);
        $steps = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
        self::assertSame(['halten', 'block', 'offen'], array_column($steps, 'art'));
        self::assertStringContainsString('<div class="timer" data-sekunden="7">00:07</div>', $body);
        self::assertStringContainsString('<div class="timer" data-sekunden="2400">40:00</div>', $body);
        self::assertStringContainsString('<div class="soll">Fußtechnik</div>', $body, 'Hinweis aus dem Plan');
        self::assertStringContainsString('name="ist[0][sets]"', $body);
        self::assertStringNotContainsString('name="ist[1][sets]"', $body, 'Sätze nur, wenn geplant');
        self::assertStringContainsString('name="ist[2][notes]"', $body);
        self::assertStringContainsString('6 Sätze · je 7 s · Pause 3 s', $body);
        self::assertStringContainsString('Block · 40 min', $body, 'Block ohne irreführende Satzzahl');
        self::assertStringContainsString('ohne Zeitvorgabe', $body);
    }

    public function testPostFromGuidedFormSavesLikeS3AndShowsErrorsInS9(): void
    {
        $id = $this->ids['kraft'];
        $form = $this->request('GET', '/einheit?id=' . $id . '&modus=start');
        preg_match('/name="stand" value="([^"]*)"/', $form->body, $stand);

        $bad = $this->request('POST', '/einheit', ['csrf' => self::csrfFrom($form), 'id' => (string) $id, 'stand' => $stand[1], 'modus' => 'start', 'status' => 'erledigt', 'duration_min' => '', 'pain' => 'nein',
            'ist' => [['sets' => '3', 'reps' => '8', 'load' => '62,5 kg']]]);
        self::assertSame(422, $bad->status);
        self::assertStringContainsString('data-gefuehrt', $bad->body, 'Fehler erscheinen wieder in S9');
        self::assertStringContainsString(' data-fehler>', $bad->body);
        self::assertStringContainsString('name="ist[0][load]" value="62,5 kg"', $bad->body, 'Eingaben bleiben');

        $ok = $this->request('POST', '/einheit', ['csrf' => self::csrfFrom($form), 'id' => (string) $id, 'stand' => $stand[1], 'modus' => 'start', 'status' => 'teilweise', 'duration_min' => '41', 'rpe' => '6', 'feel' => '2', 'pain' => 'nein', 'deviation' => '', 'notes' => 'geführt',
            'ist' => [['sets' => '3', 'reps' => '8', 'load' => '62,5 kg'], ['sets' => '3', 'reps' => '8', 'load' => '50 kg'], ['sets' => '0', 'reps' => '12', 'load' => 'KG']]]);
        self::assertSame(303, $ok->status, $ok->body);
        self::assertSame('/woche?start=2026-09-21&ok=einheit', $ok->headers['Location']);
        $e = $this->pdo->query('SELECT * FROM session_execution WHERE session_id = ' . $id)->fetch();
        self::assertSame([41, 6, 246, 'geführt'], [(int) $e['duration_min'], (int) $e['rpe_cr10'], (int) $e['srpe_load'], $e['notes']]);
        $actual = json_decode((string) $e['actual_json'], true);
        self::assertSame('62,5 kg', $actual['exercises'][0]['load']);
        self::assertSame(0, $actual['exercises'][2]['sets']);
        self::assertSame('teilweise', $this->pdo->query('SELECT status FROM `session` WHERE id = ' . $id)->fetchColumn());

        // Erneut öffnen: gespeicherte Dauer darf das Skript nicht ersetzen; Stand hat sich geändert
        $again = $this->request('GET', '/einheit?id=' . $id . '&modus=start');
        self::assertStringContainsString('data-dauer-plan="0"', $again->body);
        self::assertStringContainsString('name="duration_min" value="41"', $again->body);
        self::assertStringNotContainsString('name="stand" value="' . $stand[1] . '"', $again->body);
        // Veralteter Stand (anderes Gerät hat inzwischen gespeichert): 409, Anzeige wieder in S9
        $conflict = $this->request('POST', '/einheit', ['csrf' => self::csrfFrom($form), 'id' => (string) $id, 'stand' => $stand[1], 'modus' => 'start', 'status' => 'erledigt', 'duration_min' => '30', 'rpe' => '5', 'feel' => '2', 'pain' => 'nein']);
        self::assertSame(409, $conflict->status);
        self::assertStringContainsString('data-gefuehrt', $conflict->body);
    }

    public function testTimerSoundSettingIsDefaultForGuidedSession(): void
    {
        $this->pdo->prepare('INSERT INTO app_setting (setting_key, value, updated_at) VALUES (?, ?, ?)')->execute(['timer_ton', 'aus', Db::ts($this->clock->now())]);
        self::assertStringContainsString('data-ton="aus"', $this->request('GET', '/einheit?id=' . $this->ids['kraft'] . '&modus=start')->body);
    }

    /** @return array<string, int> */
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
            $ids[$s['type']] ??= (int) $this->pdo->lastInsertId();
        }

        return $ids;
    }
}
