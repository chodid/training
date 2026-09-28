<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Training\Checkin\MorningStatus;

/** Ampel und Ableitungen des Morgen-Check-ins (AP-12, docs/konzept/morgen-checkin.md Abschnitt 5 und 8). */
final class MorningStatusTest extends TestCase
{
    /** @return array<string, array{?int, ?int, ?int, ?int, string, string}> */
    public static function cases(): array
    {
        return [
            '1 grün' => [null, null, 2, 3, 'gruen', 'Morgentest 3/10'],
            '2 gelb' => [null, null, 4, 1, 'gelb', 'Morgentest 4/10'],
            '3 rot > 5' => [null, null, 6, 0, 'rot', 'Morgentest 6/10'],
            '4 rot steigend ≥ 4' => [2, 3, 4, 2, 'rot', 'zwei Tage steigend (2→3→4)'],
            '5 grün steigend < 4' => [1, 2, 3, 3, 'gruen', 'Morgentest 3/10'],
            '6 gelb, Vortag fehlt' => [2, null, 4, 4, 'gelb', 'Morgentest 4/10'],
            '7 keine Daten' => [null, null, null, null, 'keine_daten', 'Kein Morgentest erfasst'],
            '8 gelb, eine Seite' => [null, null, null, 5, 'gelb', 'Morgentest 5/10'],
            '9 gelb, nicht streng steigend' => [4, 4, 4, 4, 'gelb', 'Morgentest 4/10'],
            '10 grün bei 0/0' => [null, null, 0, 0, 'gruen', 'Morgentest 0/10'],
        ];
    }

    #[DataProvider('cases')]
    public function testAmpel(?int $v2, ?int $v1, ?int $links, ?int $rechts, string $ampel, string $grund): void
    {
        $v = MorningStatus::steuerwert($links, $rechts);
        self::assertSame(['ampel' => $ampel, 'grund' => $grund], MorningStatus::ampel($v, $v1, $v2));
    }

    public function testSteuerwertNullIsNotZero(): void
    {
        self::assertNull(MorningStatus::steuerwert(null, null));
        self::assertSame(0, MorningStatus::steuerwert(0, 0), 'Fall 10: 0 bleibt 0');
        self::assertSame(5, MorningStatus::steuerwert(null, 5));
        self::assertSame(3, MorningStatus::steuerwert(3, null));
    }

    public function testWochenausgangswertUndUeberschreitung(): void
    {
        $week = ['2026-09-21' => 2, '2026-09-22' => null, '2026-09-23' => 3];
        $basis = MorningStatus::wochenausgangswert($week, '2026-09-21', '2026-09-23');
        self::assertSame(2, $basis);
        self::assertTrue(MorningStatus::ueberAusgangswert(3, $basis), 'Mo = 2, Mi = 3');
        self::assertSame(1, MorningStatus::wochenausgangswert(['2026-09-21' => null, '2026-09-22' => 1], '2026-09-21', '2026-09-22'), 'Mo leer, Di = 1');
        self::assertFalse(MorningStatus::ueberAusgangswert(1, 1));
        self::assertNull(MorningStatus::wochenausgangswert(['2026-09-20' => 4], '2026-09-21', '2026-09-23'), 'Vorwoche zählt nicht');
        self::assertFalse(MorningStatus::ueberAusgangswert(null, 2));
    }

    public function testAbklaerung(): void
    {
        self::assertFalse(MorningStatus::abklaerungEmpfohlen([], false, false));
        self::assertFalse(MorningStatus::abklaerungEmpfohlen([], true, false), 'umgeknickt ohne Schwellung');
        self::assertTrue(MorningStatus::abklaerungEmpfohlen([], true, true));
        self::assertTrue(MorningStatus::abklaerungEmpfohlen(['knie_schwellung_erguss'], false, false));
    }
}
