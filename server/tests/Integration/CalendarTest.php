<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeCalDav;

/** AP-11 (D-50, D-60): ein Sammeltermin je Tag per CalDAV im Nextcloud-Kalender – MCP, Webseite, Fehler, Cron- und Knopf-Abgleich. */
final class CalendarTest extends AppTestCase
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
        $this->mcpTool(self::STATIC, 'upsert_block', ['block' => ['name' => 'B', 'start_date' => '2026-09-14', 'end_date' => '2026-10-11', 'status' => 'aktiv']]);
    }

    public function testPlanUpdateReplaceAndWebFeedbackAreMirrored(): void
    {
        $plan = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'sessions' => [
            $this->session('2026-09-22', 'kraft', 'Beine'), $this->session('2026-09-22', 'mobilitaet', 'Hüfte morgens'),
            $this->session('2026-09-24', 'klettern', 'Boulder'), ['date' => '2026-09-27', 'type' => 'ruhe', 'title' => 'Ruhe'],
        ]]);
        self::assertSame('ok', $plan['data']['status'], $plan['text']);
        [$kraft, $mobil, $klettern, $ruhe] = array_column($plan['data']['einheiten'], 'id');
        self::assertSame(['training-tag-2026-09-22.ics', 'training-tag-2026-09-24.ics'], array_keys($this->cal->events), 'ein Termin je Tag, Ruhetag nicht im Kalender');
        self::assertSame('Basic ' . base64_encode('p:app-passwort'), $this->cal->requests[0]['headers']['Authorization']);
        self::assertStringStartsWith(self::CAL, $this->cal->requests[0]['url']);
        $ics = $this->unfold('2026-09-22');
        self::assertStringContainsString('SUMMARY:Training: Beine + Hüfte morgens', $ics);
        self::assertStringContainsString('DTSTART;VALUE=DATE:20260922', $ics);
        self::assertStringContainsString('einheit?id=' . $kraft, $ics);
        self::assertStringContainsString('einheit?id=' . $mobil, $ics);
        self::assertStringContainsString('SUMMARY:Klettern: Boulder', $this->unfold('2026-09-24'), 'eine Einheit: Typ und Titel');

        // Änderung: Datum und Status – alter und neuer Tag werden neu geschrieben
        $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $kraft, 'changes' => ['date' => '2026-09-23', 'status' => 'erledigt']]);
        self::assertStringContainsString('SUMMARY:Mobilität: Hüfte morgens', $this->unfold('2026-09-22'));
        $ics = $this->unfold('2026-09-23');
        self::assertStringContainsString('SUMMARY:Kraft: Beine', $ics, 'ohne Status-Markierung');
        self::assertStringContainsString('Priorität B · 60 min · erledigt', $ics, 'Status in der Beschreibung');

        // Letzte Einheit eines Tages verschoben: Termin des Tages verschwindet
        $this->mcpTool(self::STATIC, 'update_session', ['session_id' => $mobil, 'changes' => ['date' => '2026-09-25']]);
        self::assertArrayNotHasKey('training-tag-2026-09-22.ics', $this->cal->events);
        self::assertArrayHasKey('training-tag-2026-09-25.ics', $this->cal->events);

        // Webseite: ausgelassen → Termin bleibt, Status in der Beschreibung
        $form = $this->request('GET', '/einheit?id=' . $klettern);
        $this->request('POST', '/einheit', ['csrf' => self::csrfFrom($form), 'id' => (string) $klettern, 'status' => 'ausgelassen', 'deviation' => 'zeit', 'pain' => 'nein']);
        $ics = $this->unfold('2026-09-24');
        self::assertStringContainsString('STATUS:CONFIRMED', $ics);
        self::assertStringContainsString('Priorität B · 60 min · ausgelassen', $ics);

        // Woche ersetzen: geplante Einheiten ohne Rückmeldung verschwinden (Kraft ist erledigt, Klettern hat Rückmeldung)
        $this->mcpTool(self::STATIC, 'upsert_block', ['block' => ['name' => 'B', 'start_date' => '2026-09-14', 'end_date' => '2026-10-11', 'status' => 'aktiv'], 'block_id' => 1]);
        $plan2 = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'replace_existing' => true, 'sessions' => [$this->session('2026-09-26', 'haltung', 'Rücken')]]);
        self::assertSame([$mobil, $ruhe], $plan2['data']['ersetzt']);
        $names = array_keys($this->cal->events);
        sort($names);
        self::assertSame(['training-tag-2026-09-23.ics', 'training-tag-2026-09-24.ics', 'training-tag-2026-09-26.ics'], $names, 'ersetzter Tag entfernt, neuer Tag angelegt');
        self::assertStringContainsString('SUMMARY:Haltung: Rücken', $this->unfold('2026-09-26'));
    }

    public function testErrorsAreReportedButDoNotBlockAndSyncRepairs(): void
    {
        $this->cal->failStatus = 401;
        $plan = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'sessions' => [$this->session('2026-09-22', 'kraft', 'Beine')]]);
        self::assertFalse($plan['isError']);
        self::assertSame('teilweise', $plan['data']['status']);
        self::assertStringContainsString('HTTP 401', $plan['data']['fehler_kalender'][0]);
        self::assertStringContainsString('App-Passwort', $plan['data']['fehler_kalender'][0]);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM `session`')->fetchColumn(), 'Einheit trotzdem gespeichert');
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'calendar_error'")->fetchColumn());
        $settings = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('Kalender (CalDAV)', $settings->body);
        self::assertStringContainsString('Fehler: Kalender cloud.example: PUT → HTTP 401', html_entity_decode($settings->body));

        // Netz zurück: stündlicher Abgleich überträgt nach und entfernt verwaiste eigene Termine sowie die alten
        // Einzeltermine je Einheit (bis 0.18.0, auch zu bestehenden Einheiten); fremde bleiben
        $this->cal->failStatus = null;
        $this->cal->events['training-session-1.ics'] = "BEGIN:VCALENDAR\r\nDTSTART;VALUE=DATE:20260922\r\n";
        $this->cal->events['training-session-999.ics'] = "BEGIN:VCALENDAR\r\nDTSTART;VALUE=DATE:20260924\r\n";
        $this->cal->events['training-tag-2026-09-25.ics'] = "BEGIN:VCALENDAR\r\nDTSTART;VALUE=DATE:20260925\r\n";
        $this->cal->events['fremd.ics'] = "BEGIN:VCALENDAR\r\nDTSTART;VALUE=DATE:20260924\r\n";
        $r = $this->request('GET', '/cron/intervals-sync?key=' . str_repeat('c', 32));
        self::assertSame(200, $r->status, $r->body);
        self::assertSame(['uebertragen' => 1, 'geloescht' => 3, 'fehler' => []], json_decode($r->body, true)['kalender']);
        $names = array_keys($this->cal->events);
        sort($names);
        self::assertSame(['fremd.ics', 'training-tag-2026-09-22.ics'], $names);
        self::assertStringContainsString('zuletzt übertragen', $this->request('GET', '/einstellungen')->body);

        // Knopf in den Einstellungen
        $settings = $this->request('GET', '/einstellungen');
        $b = $this->request('POST', '/einstellungen', ['csrf' => self::csrfFrom($settings), 'action' => 'kalender']);
        self::assertSame('/einstellungen?ok=kalender&n=1&d=0', $b->headers['Location']);
        self::assertStringContainsString('1 Termine übertragen, 0 entfernt.', $this->request('GET', $b->headers['Location'])->body);

        // Netzfehler beim Knopf
        $this->cal->networkDown = true;
        $f = $this->request('POST', '/einstellungen', ['csrf' => self::csrfFrom($settings), 'action' => 'kalender']);
        self::assertStringContainsString('Kalender-Abgleich fehlgeschlagen.', $f->body);
        self::assertStringContainsString('nicht erreichbar', $f->body);
    }

    public function testReminderDefaultChangeAndOff(): void
    {
        $plan = $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'sessions' => [$this->session('2026-09-24', 'kraft', 'Beine')]]);
        self::assertSame('ok', $plan['data']['status'], $plan['text']);
        $res = 'training-tag-2026-09-24.ics';
        self::assertStringContainsString('TRIGGER;RELATED=START:PT5H', $this->cal->events[$res], 'Standard 05:00');

        $settings = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('Am Trainingstag um 05:00 Uhr', $settings->body);
        $form = $this->request('GET', '/einstellungen?bereich=erinnerung');
        self::assertStringContainsString('value="05:00"', $form->body);
        $csrf = self::csrfFrom($form);
        self::assertSame(422, $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'erinnerung', 'uhrzeit' => '25:00'])->status);

        $r = $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'erinnerung', 'uhrzeit' => '06:30']);
        self::assertSame('/einstellungen?ok=erinnerung&n=1', $r->headers['Location']);
        self::assertStringContainsString('TRIGGER;RELATED=START:PT6H30M', $this->cal->events[$res], 'sofort neu übertragen');
        self::assertStringContainsString('1 Termine im Kalender aktualisiert.', $this->request('GET', $r->headers['Location'])->body);

        $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'erinnerung', 'uhrzeit' => '06:30', 'aus' => '1']);
        self::assertStringNotContainsString('VALARM', $this->cal->events[$res]);
        self::assertStringContainsString('Erinnerung im Kalender</div><div class="s">Aus', $this->request('GET', '/einstellungen')->body);
        self::assertSame('aus', $this->pdo->query("SELECT value FROM app_setting WHERE setting_key = 'calendar_reminder'")->fetchColumn());
        self::assertSame(2, (int) $this->pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'setting_update'")->fetchColumn());

        // Kalender nicht erreichbar: Einstellung gespeichert, Hinweis
        $this->cal->networkDown = true;
        $w = $this->request('POST', '/einstellungen', ['csrf' => $csrf, 'action' => 'erinnerung', 'uhrzeit' => '05:15']);
        self::assertStringContainsString('Erinnerung gespeichert, Kalender nicht aktualisiert.', $w->body);
        self::assertSame('05:15', $this->pdo->query("SELECT value FROM app_setting WHERE setting_key = 'calendar_reminder'")->fetchColumn());
    }

    public function testNotConfiguredOrNotHttps(): void
    {
        $this->writeEnv(['MCP_STATIC_TOKEN' => self::STATIC, 'MCP_STATIC_TOKEN_ENABLED' => 'true', 'CRON_SECRET' => str_repeat('c', 32)]);
        $this->mcpTool(self::STATIC, 'write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Grundlage', 'sessions' => [$this->session('2026-09-22', 'kraft', 'Beine')]]);
        self::assertSame([], $this->cal->requests);
        self::assertSame('nicht_konfiguriert', json_decode($this->request('GET', '/health')->body, true)['checks']['kalender']);
        self::assertSame(503, $this->request('GET', '/cron/intervals-sync?key=' . str_repeat('c', 32))->status, 'weder Intervals noch Kalender');

        $this->writeEnv(['MCP_STATIC_TOKEN' => self::STATIC, 'MCP_STATIC_TOKEN_ENABLED' => 'true', 'CALDAV_URL' => 'http://cloud.example/cal/', 'CALDAV_USER' => 'p', 'CALDAV_PASSWORD' => 'x']);
        $u = $this->mcpTool(self::STATIC, 'update_session', ['session_id' => 1, 'changes' => ['title' => 'Neu']]);
        self::assertFalse($u['isError'], $u['text']);
        self::assertSame([], $this->cal->requests, 'ohne https bleibt der Kalender aus');
        self::assertSame('ungueltig_kein_https', json_decode($this->request('GET', '/health')->body, true)['checks']['kalender']);
        self::assertStringContainsString('CALDAV_URL muss mit https:// beginnen', $this->request('GET', '/einstellungen')->body);
    }

    /** Sammeltermin eines Tages mit entfalteten Zeilen (RFC 5545). */
    private function unfold(string $date): string
    {
        self::assertArrayHasKey('training-tag-' . $date . '.ics', $this->cal->events);

        return str_replace("\r\n ", '', $this->cal->events['training-tag-' . $date . '.ics']);
    }

    /** @return array<string, mixed> */
    private function session(string $date, string $type, string $title): array
    {
        $plan = match ($type) {
            'klettern' => ['blocks' => [['kind' => 'bouldern_volumen', 'duration_min' => 60]]],
            default => ['exercises' => [['name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8', 'load' => '60 kg']]],
        };

        return ['date' => $date, 'type' => $type, 'title' => $title, 'priority' => 'B', 'planned_duration_min' => 60, 'plan_json' => $plan, 'coach_summary' => $title . ' als Grundlage'];
    }
}
