<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Calendar\SessionEvent;

/** iCalendar-Aufbau eines Termins (AP-11): ganztägig, Markierung, Escaping und Zeilenfaltung nach RFC 5545. */
final class SessionEventTest extends TestCase
{
    public function testIcsForStrengthSession(): void
    {
        $s = ['id' => 7, 'date' => '2026-09-30', 'type' => 'kraft', 'title' => 'Kraft; Beine, schwer', 'priority' => 'A', 'planned_duration_min' => 60,
            'status' => 'erledigt', 'coach_rationale' => "Zeile 1\nZeile 2 mit Ümlauten und einem sehr langen Text, der gefaltet werden muss, damit nichts abgeschnitten wird",
            'plan' => ['exercises' => [['name' => 'Kniebeuge', 'sets' => 3, 'reps' => '6-8', 'load' => '60 kg']]], 'updated_at' => '2026-09-28 10:00:00'];
        $ics = SessionEvent::ics($s, 'https://training.example/', 'training.example', 1790000000);

        self::assertStringContainsString("UID:training-session-7@training.example\r\n", $ics);
        self::assertStringContainsString("DTSTART;VALUE=DATE:20260930\r\nDTEND;VALUE=DATE:20261001\r\n", $ics);
        self::assertStringContainsString('SUMMARY:✓ Kraft: Kraft\; Beine\, schwer', $ics);
        self::assertStringContainsString('STATUS:CONFIRMED', $ics);
        self::assertStringContainsString('URL:https://training.example/einheit?id=7', $ics);
        foreach (explode("\r\n", rtrim($ics)) as $line) {
            self::assertLessThanOrEqual(75, strlen($line), $line);
            self::assertTrue(mb_check_encoding($line, 'UTF-8'), 'keine geteilten UTF-8-Zeichen');
        }
        $unfolded = str_replace("\r\n ", '', $ics);
        self::assertStringContainsString('Kniebeuge: 3 × 6-8 · 60 kg', $unfolded);
        self::assertStringContainsString('Trainer: Zeile 1\nZeile 2 mit Ümlauten', $unfolded);
        self::assertStringContainsString('Priorität A · 60 min · erledigt', $unfolded);

        $skipped = SessionEvent::ics(['status' => 'ausgelassen'] + $s, 'https://training.example', 'training.example', 1790000000);
        self::assertStringContainsString('STATUS:CANCELLED', $skipped);
        self::assertStringContainsString('SUMMARY:Kraft:', $skipped);

        self::assertSame(7, SessionEvent::idFromResource('training-session-7.ics'));
        self::assertNull(SessionEvent::idFromResource('fremd.ics'));
    }
}
