<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\ConfigException;
use Training\Intervals\IntervalsClient;
use Training\Intervals\IntervalsException;
use Training\Intervals\TestWorkout;
use Training\Tests\Support\FakeTransport;

final class IntervalsClientTest extends TestCase
{
    private const P = '/api/v1/athlete/i123';

    public function testBasicAuthAndQuery(): void
    {
        $t = new FakeTransport(['GET ' . self::P . '/activities' => [['status' => 200, 'body' => '[{"id":"a1","type":"Run"}]']]]);
        $list = $this->client($t)->activities('2026-09-21', '2026-09-27');
        self::assertSame([['id' => 'a1', 'type' => 'Run']], $list);
        $r = $t->requests[0];
        self::assertSame('GET', $r['method']);
        self::assertSame('https://intervals.icu/api/v1/athlete/i123/activities?oldest=2026-09-21&newest=2026-09-27', $r['url']);
        self::assertSame('Basic ' . base64_encode('API_KEY:geheimer-key'), $r['headers']['Authorization']);
    }

    public function testEventCrud(): void
    {
        $t = new FakeTransport([
            'POST ' . self::P . '/events' => [['status' => 200, 'body' => '{"id":42}']],
            'PUT ' . self::P . '/events/42' => [['status' => 200, 'body' => '{"id":42,"name":"x"}']],
            'DELETE ' . self::P . '/events/42' => [['status' => 200, 'body' => '']],
            'GET ' . self::P . '/events' => [['status' => 200, 'body' => '[{"id":42,"external_id":"training-app-test"},{"id":7}]']],
        ]);
        $c = $this->client($t);
        self::assertSame(42, $c->createEvent(TestWorkout::event('2026-09-28'))['id']);
        $sent = json_decode((string) $t->requests[0]['body'], true);
        self::assertSame('WORKOUT', $sent['category']);
        self::assertSame('Run', $sent['type']);
        self::assertSame('2026-09-28T00:00:00', $sent['start_date_local']);
        self::assertStringContainsString("- 3m Z3 HR", $sent['description']);
        self::assertSame('application/json', $t->requests[0]['headers']['Content-Type']);
        $c->updateEvent(42, ['name' => 'x']);
        $c->deleteEvent(42);
        self::assertSame(['PUT', 'DELETE'], [$t->requests[1]['method'], $t->requests[2]['method']]);
        self::assertCount(1, TestWorkout::find($c->events('2026-09-27', '2026-10-11', 'WORKOUT')));
        self::assertStringEndsWith('?oldest=2026-09-27&newest=2026-10-11&category=WORKOUT', $t->requests[3]['url']);
    }

    public function testErrorsDoNotLeakKeyAndGiveHint(): void
    {
        $t = new FakeTransport(['GET ' . self::P => [['status' => 401, 'body' => '{"error":"Unauthorized"}']]]);
        try {
            $this->client($t)->athlete();
            self::fail('IntervalsException erwartet');
        } catch (IntervalsException $e) {
            self::assertSame(401, $e->status);
            self::assertStringContainsString('API-Key oder Athleten-ID prüfen', $e->getMessage());
            self::assertStringNotContainsString('geheimer-key', $e->getMessage());
        }
    }

    public function testRetriesOnceOn429(): void
    {
        $t = new FakeTransport(['GET ' . self::P . '/wellness' => [
            ['status' => 429, 'body' => '', 'headers' => ['retry-after' => '2']],
            ['status' => 200, 'body' => '[{"id":"2026-09-27","hrv":55}]'],
        ]]);
        $slept = [];
        $c = new IntervalsClient('geheimer-key', 'i123', $t, IntervalsClient::BASE_URL, function (int $s) use (&$slept): void { $slept[] = $s; });
        self::assertSame(55, $c->wellness('2026-09-27', '2026-09-27')[0]['hrv']);
        self::assertSame([2], $slept);
        self::assertCount(2, $t->requests);
    }

    public function testSecond429Fails(): void
    {
        $t = new FakeTransport(['GET ' . self::P . '/wellness' => [['status' => 429, 'body' => '']]]);
        $c = new IntervalsClient('k', 'i123', $t, IntervalsClient::BASE_URL, static function (int $s): void {});
        $this->expectException(IntervalsException::class);
        $c->wellness('2026-09-27', '2026-09-27');
    }

    public function testInvalidInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->client(new FakeTransport())->activities('27.09.2026', '2026-09-27');
    }

    public function testInvalidAthleteId(): void
    {
        $this->expectException(ConfigException::class);
        new IntervalsClient('k', '../x', new FakeTransport());
    }

    public function testUnexpectedShape(): void
    {
        $t = new FakeTransport(['GET ' . self::P . '/events' => [['status' => 200, 'body' => '{"id":1}']]]);
        $this->expectException(IntervalsException::class);
        $this->client($t)->events('2026-09-27', '2026-09-28');
    }

    private function client(FakeTransport $t): IntervalsClient
    {
        return new IntervalsClient('geheimer-key', 'i123', $t, IntervalsClient::BASE_URL, static function (int $s): void {});
    }
}
