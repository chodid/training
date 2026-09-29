<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Review\ReviewValidator;

/** AP-15 T1: Schemata review-<kind>.json mit gültigen und ungültigen Beispielen je Art (docs/konzept/blockbilanz.md 4.3). */
final class ReviewValidatorTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function examples(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/review-beispiele.json'), true);
    }

    public function testValidExamples(): void
    {
        $v = ReviewValidator::default();
        foreach (ReviewValidator::KINDS as $kind) {
            self::assertSame([], $v->validate($kind, self::examples()[$kind]), $kind);
        }
    }

    public function testZielklaerungErrors(): void
    {
        $v = ReviewValidator::default();
        $zk = self::examples()['zielklaerung'];
        $bad = $zk;
        $bad['phase'] = 'sommerpause';
        $bad['entscheidungen'][0]['verworfen'] = [];
        unset($bad['ziele'][0]['kriterium']);
        $bad['extra'] = 1;
        $errors = implode("\n", $v->validate('zielklaerung', $bad));
        self::assertStringContainsString('content/phase', $errors);
        self::assertStringContainsString('content/entscheidungen/0/verworfen', $errors);
        self::assertStringContainsString('content/ziele/0', $errors);

        $bad = $zk;
        $bad['entscheidungen'][0]['rationale'] = str_repeat('x', 1501);
        self::assertNotSame([], $v->validate('zielklaerung', $bad));
        $bad = $zk;
        $bad['offene_fragen'] = array_fill(0, 21, 'Frage');
        self::assertNotSame([], $v->validate('zielklaerung', $bad));
        $bad = $zk;
        $bad['ziele'] = [];
        self::assertNotSame([], $v->validate('zielklaerung', $bad));
    }

    public function testBilanzErrors(): void
    {
        $v = ReviewValidator::default();
        $b = self::examples()['bilanz'];
        $bad = $b;
        unset($bad['ziele'][0]['bewertung']);
        $errors = $v->validate('bilanz', $bad);
        self::assertNotSame([], $errors);
        self::assertStringContainsString('content/ziele/0', implode("\n", $errors));

        $bad = $b;
        $bad['zeitraum'] = ['von' => '2026-12-13', 'bis' => '2026-09-21'];
        self::assertSame(['content/zeitraum: bis liegt vor von'], $v->validate('bilanz', $bad));
        $bad = $b;
        $bad['ziele'][0]['bewertung'] = 'super';
        self::assertNotSame([], $v->validate('bilanz', $bad));
    }

    public function testRevisionErrors(): void
    {
        $v = ReviewValidator::default();
        $r = self::examples()['revision'];
        $bad = $r;
        $bad['anlass'] = 'laune';
        $bad['aenderungen'] = [];
        self::assertCount(2, $v->validate('revision', $bad));
        self::assertSame(['content: muss ein Objekt sein'], $v->validate('revision', ['a', 'b']));
        self::assertNotSame([], $v->validate('revision', []));
    }
}
