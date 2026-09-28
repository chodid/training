<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Intervals\IntervalsClient;
use Training\Intervals\Mirror;
use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeTransport;

/** Spiegel Intervals.icu → MySQL (D-43): read-through, Rückfall bei Ausfall, Cron-Abgleich, Löschungen. */
final class MirrorTest extends AppTestCase
{
    private const NOW = 1790164800; // Mi 2026-09-23 12:00 UTC
    private const ACT = '/api/v1/athlete/i1/activities';
    private const WELL = '/api/v1/athlete/i1/wellness';

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
    }

    public function testReadThroughStoresAndFallsBackWhenApiFails(): void
    {
        $t = new FakeTransport(['GET ' . self::ACT => [
            ['status' => 200, 'body' => '[{"id":"a1","type":"Run","start_date_local":"2026-09-22T07:00:00","moving_time":3000,"paired_event_id":5,"streams":[1,2,3]},{"id":"a2","type":"Ride","start_date_local":"2026-09-23T18:00:00"}]'],
            ['status' => 500, 'body' => ''],
        ]]);
        $mirror = new Mirror(new IntervalsClient('k', 'i1', $t, IntervalsClient::BASE_URL, static function (int $s): void {}), $this->pdo, $this->clock);
        $list = $mirror->activities('2026-09-21', '2026-09-23');
        self::assertSame(['a1', 'a2'], array_column($list, 'id'));
        self::assertArrayNotHasKey('streams', $list[0], 'nur Zusammenfassung (N7)');
        self::assertSame(5, (int) $this->pdo->query("SELECT paired_event_id FROM ext_activity WHERE id = 'a1'")->fetchColumn());

        // Innerhalb von 5 Minuten kein weiterer Abruf
        $mirror->activities('2026-09-21', '2026-09-23');
        self::assertCount(1, $t->requests);

        // Danach Ausfall der API → Daten aus dem Spiegel, Fehler gemeldet
        $this->clock->advance(301);
        $again = new Mirror(new IntervalsClient('k', 'i1', $t, IntervalsClient::BASE_URL, static function (int $s): void {}), $this->pdo, $this->clock);
        self::assertSame(['a1', 'a2'], array_column($again->activities('2026-09-21', '2026-09-23'), 'id'));
        self::assertStringContainsString('HTTP 500', (string) $again->error);

        // Ohne Konfiguration: nur Spiegel
        self::assertCount(2, (new Mirror(null, $this->pdo, $this->clock))->activities('2026-09-01', '2026-09-30'));
    }

    public function testCronSyncRemovesDeletedActivitiesAndStoresWellness(): void
    {
        $secret = str_repeat('c', 40);
        $this->writeEnv(['CRON_SECRET' => $secret, 'INTERVALS_API_KEY' => 'k', 'INTERVALS_ATHLETE_ID' => 'i1']);
        $this->intervalsTransport = $t = new FakeTransport([
            'GET ' . self::ACT => [
                ['status' => 200, 'body' => '[{"id":"a1","type":"Run","start_date_local":"2026-09-20T07:00:00"},{"id":"a2","type":"Walk","start_date_local":"2026-09-22T07:00:00"}]'],
                ['status' => 200, 'body' => '[{"id":"a1","type":"Run","start_date_local":"2026-09-20T07:00:00"}]'],
            ],
            'GET ' . self::WELL => [['status' => 200, 'body' => '[{"id":"2026-09-22","hrv":61,"restingHR":48,"sleepSecs":27000,"ctl":40,"atl":45,"unbekannt":1}]']],
        ]);
        self::assertSame(403, $this->request('GET', '/cron/intervals-sync?key=falsch')->status);
        $r = $this->request('GET', '/cron/intervals-sync?key=' . $secret . '&tage=30');
        self::assertSame(200, $r->status, $r->body);
        self::assertSame(['status' => 'ok', 'aktivitaeten' => 2, 'wellness_tage' => 1, 'von' => '2026-08-25', 'bis' => '2026-09-23'], json_decode($r->body, true));
        self::assertStringContainsString('oldest=2026-08-25&newest=2026-09-23', $t->requests[0]['url']);
        $w = json_decode((string) $this->pdo->query("SELECT data_json FROM ext_wellness WHERE date = '2026-09-22'")->fetchColumn(), true);
        self::assertSame(61, $w['hrv']);
        self::assertArrayNotHasKey('unbekannt', $w);

        // a2 wurde in Intervals.icu gelöscht
        $this->request('GET', '/cron/intervals-sync?key=' . $secret . '&tage=30');
        self::assertSame(['a1'], $this->pdo->query('SELECT id FROM ext_activity')->fetchAll(\PDO::FETCH_COLUMN));

        // Status in den Einstellungen; Spiegel im Backup und Export enthalten
        $this->setupUser();
        $settings = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('1 Aktivitäten, 1 Wellness-Tage', $settings->body);
        self::assertStringContainsString('aktiv', $settings->body);
        $dump = (new \Training\Backup\Dumper($this->pdo))->dump();
        self::assertStringContainsString('INSERT INTO `ext_activity`', $dump);
        self::assertStringContainsString('INSERT INTO `ext_wellness`', $dump);
    }

    public function testCronSyncErrorIsStoredAndMissingConfig(): void
    {
        $secret = str_repeat('c', 40);
        $this->writeEnv(['CRON_SECRET' => $secret]);
        self::assertSame(503, $this->request('GET', '/cron/intervals-sync?key=' . $secret)->status);
        $this->writeEnv(['CRON_SECRET' => $secret, 'INTERVALS_API_KEY' => 'k', 'INTERVALS_ATHLETE_ID' => 'i1']);
        $this->intervalsTransport = new FakeTransport(['GET ' . self::ACT => [['status' => 401, 'body' => '']]]);
        self::assertSame(502, $this->request('GET', '/cron/intervals-sync?key=' . $secret)->status);
        $this->setupUser();
        self::assertStringContainsString('API-Key oder Athleten-ID', $this->request('GET', '/einstellungen')->body);
    }
}
