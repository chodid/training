<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeTransport;

/** Verbindungstest-Seite /intervals (AP-02) mit simuliertem Intervals.icu. */
final class IntervalsPageTest extends AppTestCase
{
    private const P = '/api/v1/athlete/i123';

    public function testRequiresLogin(): void
    {
        $this->setupUser();
        $this->cookies = [];
        self::assertSame('/login?next=%2Fintervals', $this->request('GET', '/intervals')->headers['Location']);
    }

    public function testNotConfigured(): void
    {
        $this->setupUser();
        $r = $this->request('GET', '/intervals');
        self::assertSame(200, $r->status);
        self::assertStringContainsString('Nicht eingerichtet', $r->body);
        self::assertSame('nicht_konfiguriert', json_decode($this->request('GET', '/health')->body, true)['checks']['intervals']);
    }

    public function testShowsDataAndManagesTestEvent(): void
    {
        $this->writeEnv(['INTERVALS_API_KEY' => 'k', 'INTERVALS_ATHLETE_ID' => 'i123']);
        $this->intervalsTransport = $t = new FakeTransport([
            'GET ' . self::P => [['status' => 200, 'body' => '{"id":"i123","name":"Philipp"}']],
            'GET ' . self::P . '/activities' => [['status' => 200, 'body' => '[{"start_date_local":"2026-09-26T08:00:00","type":"Run","name":"Morgenlauf","moving_time":3600,"icu_rpe":4,"feel":2}]']],
            'GET ' . self::P . '/wellness' => [['status' => 200, 'body' => '[{"id":"2026-09-26","hrv":61,"restingHR":48,"sleepSecs":27000}]']],
            'GET ' . self::P . '/events' => [['status' => 200, 'body' => '[]']],
            'POST ' . self::P . '/events' => [['status' => 200, 'body' => '{"id":99}']],
        ]);
        $this->setupUser();
        $page = $this->request('GET', '/intervals');
        self::assertSame(200, $page->status, $page->body);
        self::assertStringContainsString('Philipp', $page->body);
        self::assertStringContainsString('Morgenlauf', $page->body);
        self::assertStringContainsString('HRV 61', $page->body);
        self::assertStringContainsString('Test-Event anlegen', $page->body);
        self::assertSame('konfiguriert', json_decode($this->request('GET', '/health')->body, true)['checks']['intervals']);

        self::assertSame(403, $this->request('POST', '/intervals', ['csrf' => 'x', 'action' => 'create'])->status);
        $created = $this->request('POST', '/intervals', ['csrf' => self::csrfFrom($page), 'action' => 'create']);
        self::assertStringContainsString('Test-Event angelegt', $created->body);
        $posts = array_values(array_filter($t->requests, static fn (array $r): bool => $r['method'] === 'POST'));
        self::assertCount(1, $posts);
        self::assertSame('training-app-test', json_decode((string) $posts[0]['body'], true)['external_id']);

        // Vorhandenes Test-Event: ändern und löschen.
        $t->responses['GET ' . self::P . '/events'] = [['status' => 200, 'body' => '[{"id":99,"external_id":"training-app-test","name":"Testeinheit Training-App","category":"WORKOUT","type":"Run","start_date_local":"2026-09-28T00:00:00"}]']];
        $t->responses['PUT ' . self::P . '/events/99'] = [['status' => 200, 'body' => '{"id":99}']];
        $t->responses['DELETE ' . self::P . '/events/99'] = [['status' => 200, 'body' => '']];
        self::assertStringContainsString('Test-Event geändert', $this->request('POST', '/intervals', ['csrf' => self::csrfFrom($page), 'action' => 'update'])->body);
        self::assertStringContainsString('Test-Event gelöscht', $this->request('POST', '/intervals', ['csrf' => self::csrfFrom($page), 'action' => 'delete'])->body);
    }

    public function testApiErrorIsShown(): void
    {
        $this->writeEnv(['INTERVALS_API_KEY' => 'k', 'INTERVALS_ATHLETE_ID' => 'i123']);
        $this->intervalsTransport = new FakeTransport(['GET ' . self::P => [['status' => 403, 'body' => '']]]);
        $this->setupUser();
        $r = $this->request('GET', '/intervals');
        self::assertSame(502, $r->status);
        self::assertStringContainsString('API-Key oder Athleten-ID prüfen', $r->body);
    }
}
