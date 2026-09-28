<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Training\Plan\Ablaufplan;
use Training\Plan\PlanValidator;

/** AP-14 T3: Ablaufplan aus plan_json (docs/konzept/gefuehrte-einheit.md 6.3, Testfälle 8.1 A-01 bis A-12). */
final class AblaufplanTest extends TestCase
{
    /** @return array<string, array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}> */
    public static function cases(): array
    {
        return [
            'A-01 Wiederholungen mit Pause' => ['kraft', ['name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8', 'rest_s' => 120],
                ['art' => 'wiederholungen', 'saetze' => 3, 'pause_s' => 120, 'arbeit_s' => null]],
            'A-02 Bereich ohne Pause' => ['kraft', ['name' => 'Rudern', 'sets' => 3, 'reps' => '6-8'],
                ['art' => 'wiederholungen', 'saetze' => 3, 'pause_s' => null, 'arbeit_s' => null]],
            'A-03 Halten in Sekunden' => ['kraft', ['name' => 'Unterarmstütz', 'sets' => 3, 'reps' => '45s', 'rest_s' => 60],
                ['art' => 'halten', 'saetze' => 3, 'arbeit_s' => 45, 'pause_s' => 60]],
            'A-04 Halten in Minuten' => ['kraft', ['name' => 'Wandsitz', 'sets' => 2, 'reps' => '2 min'],
                ['art' => 'halten', 'saetze' => 2, 'arbeit_s' => 120, 'pause_s' => null]],
            'A-05 Haltebereich obere Grenze' => ['haltung', ['name' => 'Seitstütz', 'sets' => 3, 'reps' => '30-45 s'],
                ['art' => 'halten', 'saetze' => 3, 'arbeit_s' => 45]],
            'A-06 max' => ['kraft', ['name' => 'Liegestütz', 'sets' => 1, 'reps' => 'max'],
                ['art' => 'wiederholungen', 'saetze' => 1, 'arbeit_s' => null]],
            'A-07 Hangboard mit Sätzen und Pause' => ['klettern', ['kind' => 'hangboard', 'hang_s' => 7, 'rest_s' => 3, 'sets' => 6],
                ['art' => 'halten', 'arbeit_s' => 7, 'pause_s' => 3, 'saetze' => 6]],
            'A-08 Hangboard ohne Sätze' => ['klettern', ['kind' => 'hangboard', 'hang_s' => 10, 'sets' => null],
                ['art' => 'halten', 'arbeit_s' => 10, 'saetze' => 1, 'pause_s' => null]],
            'A-09 Block mit Dauer' => ['klettern', ['kind' => 'bouldern_volumen', 'duration_min' => 40],
                ['art' => 'block', 'arbeit_s' => 2400, 'saetze' => 1, 'pause_s' => null]],
            'A-10 Block ohne Zeiten' => ['klettern', ['kind' => 'technik'],
                ['art' => 'offen', 'arbeit_s' => null, 'saetze' => 1, 'pause_s' => null]],
        ];
    }

    /** @param array<string, mixed> $item @param array<string, mixed> $expected */
    #[DataProvider('cases')]
    public function testRules(string $type, array $item, array $expected): void
    {
        $key = $type === 'klettern' ? 'blocks' : 'exercises';
        $plan = [$key => [$item]];
        self::assertSame([], PlanValidator::default()->validatePlan($type, $plan), 'Testfall ist ein gültiger Plan');
        $steps = Ablaufplan::schritte($type, $plan);
        self::assertIsArray($steps);
        self::assertCount(1, $steps);
        foreach ($expected as $field => $value) {
            self::assertSame($value, $steps[0][$field], $field);
        }
    }

    public function testA11EnduranceRestAndEmptyPlansHaveNoSchedule(): void
    {
        self::assertNull(Ablaufplan::schritte('ausdauer', ['intervals_workout_text' => '- 10m Z2', 'target_type' => 'hf_zone', 'summary' => 'x']));
        self::assertNull(Ablaufplan::schritte('ruhe', null));
        self::assertNull(Ablaufplan::schritte('ruhe', ['notes' => 'frei']));
        self::assertNull(Ablaufplan::schritte('kraft', null), 'ohne Plan');
        self::assertNull(Ablaufplan::schritte('kraft', ['exercises' => []]));
        self::assertNull(Ablaufplan::schritte('klettern', ['exercises' => [['name' => 'x', 'sets' => 1, 'reps' => '1']]]), 'falscher Schlüssel');
        self::assertFalse(Ablaufplan::geeignet('ausdauer', ['summary' => 'x']));
        self::assertTrue(Ablaufplan::geeignet('mobilitaet', ['exercises' => [['name' => '90/90', 'sets' => 2, 'reps' => '8']]]));
    }

    public function testA12OrderIndexNamesSollAndIstFields(): void
    {
        $plan = ['exercises' => [
            ['name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8', 'load' => '40 kg', 'tempo' => '3-1-1', 'rest_s' => 120, 'notes' => ' Tiefe sauber '],
            ['name' => 'Unterarmstütz', 'sets' => 3, 'reps' => '45s', 'load' => 'KG', 'rest_s' => 60],
            ['name' => 'Rudern mit Band', 'sets' => 3, 'reps' => '10', 'load' => 'Band grün', 'rest_s' => 0],
        ]];
        $steps = Ablaufplan::schritte('kraft', $plan);
        self::assertSame([0, 1, 2], array_column($steps, 'index'));
        self::assertSame(['exercises[0]', 'exercises[1]', 'exercises[2]'], array_column($steps, 'quelle'));
        self::assertSame(['Kniebeuge', 'Unterarmstütz', 'Rudern mit Band'], array_column($steps, 'name'));
        self::assertSame('Soll 3 × 8 · 40 kg · Tempo 3-1-1 · Pause 120 s', $steps[0]['soll'], 'wie S3');
        self::assertSame('Tiefe sauber', $steps[0]['notiz']);
        self::assertNull($steps[1]['notiz']);
        self::assertSame(['sets', 'reps', 'load'], $steps[0]['ist_felder']);
        self::assertNull($steps[2]['pause_s'], 'rest_s 0 = keine Pausenphase');

        $climb = Ablaufplan::schritte('klettern', ['blocks' => [
            ['kind' => 'hangboard', 'edge_mm' => 20, 'grip' => 'halbkrimp', 'hang_s' => 10, 'rest_s' => 180, 'sets' => 5, 'duration_min' => 20],
            ['kind' => 'bouldern_volumen', 'duration_min' => 50, 'target' => 'Grad 5-6'],
            ['kind' => 'technik'],
        ]]);
        self::assertSame(['Hangboard', 'Bouldern Volumen', 'Technik'], array_column($climb, 'name'));
        self::assertSame(['halten', 'block', 'offen'], array_column($climb, 'art'), 'hang_s geht vor duration_min');
        self::assertSame('Soll 20 min · 20 mm, Halbkrimp, 10 s, 5 Sätze, Pause 180 s', $climb[0]['soll']);
        self::assertSame(['duration_min', 'sets', 'notes'], $climb[0]['ist_felder']);
        self::assertSame(['duration_min', 'notes'], $climb[1]['ist_felder'], 'Sätze nur, wenn geplant (wie S3)');

        $json = json_decode(Ablaufplan::json($steps), true);
        self::assertSame($steps, $json);
    }

    public function testHoldSecondsVariants(): void
    {
        $cases = ['45s' => 45, '45 s' => 45, '45 sek' => 45, '45 Sek.' => 45, '45sec' => 45, ' 30 S ' => 30, '2 min' => 120, '2min.' => 120,
            '30-45 s' => 45, '30 - 45s' => 45, '30–45 sek' => 45, '45-30 s' => 45, '8' => null, '6-8' => null, 'max' => null, '8 pro Seite' => null,
            '0s' => null, '1,5 min' => null, '' => null];
        foreach ($cases as $reps => $expected) {
            self::assertSame($expected, Ablaufplan::holdSeconds((string) $reps), '„' . $reps . '“');
        }
    }
}
