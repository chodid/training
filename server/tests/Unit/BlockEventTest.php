<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Calendar\BlockEvent;

/** AP-15 T4: Blocktermin (docs/konzept/blockbilanz.md 7, K-B1, K-B2, K-B6; E-22). */
final class BlockEventTest extends TestCase
{
    private const SETTINGS = ['beginn' => '08:00', 'dauer_min' => 120, 'erinnerung_h' => 24];

    /** @return array<string, mixed> */
    private static function block(string $start, string $end): array
    {
        return ['id' => 7, 'name' => 'Herbst 2026', 'start_date' => $start, 'end_date' => $end];
    }

    private static function unfold(string $ics): string
    {
        return str_replace("\r\n ", '', $ics);
    }

    public function testKB1WinterTimeAndAlarm(): void
    {
        $ics = self::unfold(BlockEvent::ics(self::block('2026-09-21', '2026-12-14'), ['bilanz', 'zielklaerung'], 'https://training.example/', 'training.example', 'Europe/Berlin', self::SETTINGS, 1_790_000_000, 1_790_000_000));
        self::assertSame('training-block-7.ics', BlockEvent::resource(7));
        self::assertStringContainsString("UID:training-block-7@training.example\r\n", $ics);
        self::assertStringContainsString("DTSTART:20261214T070000Z\r\n", $ics);
        self::assertStringContainsString("DTEND:20261214T090000Z\r\n", $ics);
        self::assertStringContainsString("TRIGGER:-P1D\r\n", $ics);
        self::assertStringContainsString("ACTION:DISPLAY\r\n", $ics);
        self::assertStringContainsString('SUMMARY:Blockbilanz + Zielklärung: Herbst 2026', $ics);
        self::assertStringContainsString('CATEGORIES:Planung', $ics);
        self::assertStringContainsString('STATUS:CONFIRMED', $ics);
        self::assertStringContainsString('URL:https://training.example/block?id=7', $ics);
        self::assertStringContainsString('DESCRIPTION:Rückblick auf den Block und Ziele für den nächsten – im Projekt-Chat mit get_handover beginnen.', $ics);
        self::assertStringContainsString('21.09.2026 – 14.12.2026', $ics);
        self::assertStringNotContainsString('VALUE=DATE', $ics);
        foreach (explode("\r\n", BlockEvent::ics(self::block('2026-09-21', '2026-12-14'), ['bilanz'], 'https://training.example', 'h', 'Europe/Berlin', self::SETTINGS, 1, 1)) as $line) {
            self::assertLessThanOrEqual(75, strlen($line), 'gefaltet');
        }
    }

    public function testKB2SummerTime(): void
    {
        // 08:00 MESZ (UTC+2) = 06:00 UTC (Konzept 11.3 nennt 05:00Z – Rechenfehler, siehe probleme_loesungen T4)
        $ics = BlockEvent::ics(self::block('2026-04-13', '2026-07-05'), ['bilanz'], 'https://t', 'h', 'Europe/Berlin', self::SETTINGS, 1, 1);
        self::assertStringContainsString("DTSTART:20260705T060000Z\r\n", $ics);
        self::assertStringContainsString("DTEND:20260705T080000Z\r\n", $ics);
        self::assertStringContainsString('SUMMARY:Blockbilanz: Herbst 2026', $ics);
    }

    public function testKB6SafetyNetAfter16Weeks(): void
    {
        $block = self::block('2026-09-21', '2027-03-28');
        self::assertSame('2027-01-11', BlockEvent::date($block));
        $ics = self::unfold(BlockEvent::ics($block, ['zielklaerung'], 'https://t', 'h', 'Europe/Berlin', self::SETTINGS, 1, 1));
        self::assertStringContainsString("DTSTART:20270111T070000Z\r\n", $ics);
        self::assertStringContainsString('SUMMARY:Zielklärung: Herbst 2026', $ics);
        self::assertStringContainsString('Termin nach 16 Wochen', $ics);
        self::assertSame('2026-12-14', BlockEvent::date(self::block('2026-09-21', '2026-12-14')));
    }

    public function testSettingsAndTriggers(): void
    {
        $ics = BlockEvent::ics(self::block('2026-09-21', '2026-12-14'), ['bilanz'], 'https://t', 'h', 'Europe/Berlin', ['beginn' => '18:30', 'dauer_min' => 90, 'erinnerung_h' => 5], 1, 1);
        self::assertStringContainsString("DTSTART:20261214T173000Z\r\n", $ics);
        self::assertStringContainsString("DTEND:20261214T190000Z\r\n", $ics);
        self::assertStringContainsString("TRIGGER:-PT5H\r\n", $ics);
        self::assertSame('PT0S', BlockEvent::trigger(0));
        self::assertSame('-P2D', BlockEvent::trigger(48));
        self::assertSame(7, BlockEvent::blockIdFromResource('training-block-7-2.ics'));
        self::assertNull(BlockEvent::blockIdFromResource('training-tag-2026-09-22.ics'));
        self::assertSame('training-block-7-2.ics', BlockEvent::resource(7, 2));
        $this->expectException(\InvalidArgumentException::class);
        BlockEvent::ics(self::block('2026-09-21', '2026-12-14'), [], 'https://t', 'h', 'Europe/Berlin', self::SETTINGS, 1, 1);
    }
}
