<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Plan\PlanValidator;

final class PlanValidatorTest extends TestCase
{
    private PlanValidator $v;

    protected function setUp(): void
    {
        $this->v = PlanValidator::default();
    }

    public function testExampleWeekIsValid(): void
    {
        $week = json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/beispielwoche.json'), true);
        $types = [];
        foreach ($week['sessions'] as $s) {
            self::assertSame([], $this->v->validatePlan($s['type'], $s['plan_json']), $s['title']);
            $types[] = $s['type'];
        }
        self::assertEqualsCanonicalizing(PlanValidator::TYPES, array_values(array_unique($types)), 'alle Typen in der Beispielwoche');
    }

    /** @return array<string, array{string, mixed, string}> */
    public static function invalidPlans(): array
    {
        return [
            'kraft ohne Übungen' => ['kraft', ['exercises' => []], '/exercises'],
            'kraft Sätze 0' => ['kraft', ['exercises' => [['name' => 'X', 'sets' => 0, 'reps' => '8']]], '/exercises/0/sets'],
            'kraft reps als Zahl' => ['kraft', ['exercises' => [['name' => 'X', 'sets' => 3, 'reps' => 8]]], '/exercises/0/reps'],
            'kraft unbekanntes Feld' => ['kraft', ['exercises' => [['name' => 'X', 'sets' => 3, 'reps' => '8', 'gewicht' => 5]]], '/exercises/0'],
            'kraft fehlt' => ['kraft', null, 'fehlt'],
            'klettern falscher Griff' => ['klettern', ['blocks' => [['kind' => 'hangboard', 'grip' => 'daumen']]], '/blocks/0/grip'],
            'klettern falsche Art' => ['klettern', ['blocks' => [['kind' => 'yoga']]], '/blocks/0/kind'],
            'ausdauer ohne Workout-Text' => ['ausdauer', ['target_type' => 'hf_zone', 'summary' => 'x'], '/'],
            'ausdauer falscher Zieltyp' => ['ausdauer', ['intervals_workout_text' => '- 10m Z1 HR', 'target_type' => 'watt', 'summary' => 'x'], '/target_type'],
            'ruhe mit Übungen' => ['ruhe', ['exercises' => []], '/'],
            'unbekannter Typ' => ['yoga', [], 'Unbekannter'],
            'kein Objekt' => ['kraft', 'text', '/'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPlans')]
    public function testInvalidPlansAreRejected(string $type, mixed $plan, string $expectedFragment): void
    {
        $errors = $this->v->validatePlan($type, $plan);
        self::assertNotSame([], $errors);
        self::assertStringContainsString($expectedFragment, implode("\n", $errors));
    }

    public function testActualAllowsPartialValues(): void
    {
        self::assertSame([], $this->v->validateActual('kraft', ['exercises' => [['sets' => 2], ['load' => '45 kg']]]));
        self::assertSame([], $this->v->validateActual('ausdauer', null));
        self::assertSame([], $this->v->validateActual('klettern', ['blocks' => [['sets' => 4]]]));
        self::assertNotSame([], $this->v->validateActual('kraft', ['exercises' => [['sets' => 'drei']]]));
        self::assertNotSame([], $this->v->validateActual('kraft', ['uebungen' => []]));
    }

    public function testRuheMayBeEmptyOrNote(): void
    {
        self::assertSame([], $this->v->validatePlan('ruhe', null));
        self::assertSame([], $this->v->validatePlan('ruhe', []));
        self::assertSame([], $this->v->validatePlan('ruhe', ['notes' => 'Spaziergang erlaubt']));
    }
}
