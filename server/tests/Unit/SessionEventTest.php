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

        self::assertStringNotContainsString('VALARM', $ics, 'ohne Erinnerung');
        $planned = ['status' => 'geplant'] + $s;
        $alarm = SessionEvent::ics($planned, 'https://training.example', 'training.example', 1790000000, '05:00');
        self::assertStringContainsString("BEGIN:VALARM\r\nACTION:DISPLAY\r\nDESCRIPTION:Kraft: Kraft\\; Beine\\, schwer\r\nTRIGGER;RELATED=START:PT5H\r\nEND:VALARM\r\nEND:VEVENT", $alarm);
        self::assertStringContainsString('TRIGGER;RELATED=START:PT6H30M', SessionEvent::ics($planned, 'https://t', 't', 1, '06:30'));
        self::assertStringContainsString('TRIGGER;RELATED=START:PT0S', SessionEvent::ics($planned, 'https://t', 't', 1, '00:00'));
        self::assertStringContainsString('TRIGGER;RELATED=START:PT45M', SessionEvent::ics(['status' => 'verschoben'] + $s, 'https://t', 't', 1, '00:45'));
        self::assertStringNotContainsString('VALARM', SessionEvent::ics($s, 'https://t', 't', 1, '05:00'), 'erledigt: keine Erinnerung');
        self::assertStringNotContainsString('VALARM', SessionEvent::ics(['status' => 'ausgelassen'] + $s, 'https://t', 't', 1, '05:00'));

        self::assertSame(7, SessionEvent::idFromResource('training-session-7.ics'));
        self::assertNull(SessionEvent::idFromResource('fremd.ics'));
    }

    /** AP-13 (5.3): Kurzsatz, Leerzeile, Kurzplan, Leerzeile, ausführlicher Text (≤ 1 000 Zeichen), Link. */
    public function testDescriptionStartsWithSummaryAndShortensLongRationale(): void
    {
        $s = ['id' => 8, 'date' => '2026-09-30', 'type' => 'kraft', 'title' => 'Kraft', 'priority' => 'B', 'planned_duration_min' => 45, 'status' => 'geplant',
            'coach_summary' => 'Zweite Krafteinheit, Last wie letzte Woche', 'coach_rationale' => str_repeat('a', 995) . ' bbbbbbbbbb',
            'plan' => ['exercises' => [['name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8']]], 'updated_at' => '2026-09-28 10:00:00'];
        $unfolded = str_replace("\r\n ", '', SessionEvent::ics($s, 'https://training.example', 'training.example', 1790000000));
        preg_match('/^DESCRIPTION:(.*)$/m', $unfolded, $m);
        $parts = explode('\n\n', rtrim($m[1], "\r"));
        self::assertSame('Zweite Krafteinheit\, Last wie letzte Woche', $parts[0]);
        self::assertSame('Priorität B · 45 min\n- Kniebeuge: 3 × 8', $parts[1]);
        self::assertSame('Trainer: ' . str_repeat('a', 995) . ' bbb…', $parts[2], 'gekürzt auf 1 000 Zeichen');
        self::assertSame(1000, mb_strlen(substr($parts[2], strlen('Trainer: '))));
        self::assertSame('In der App: https://training.example/einheit?id=8', $parts[3]);

        $old = SessionEvent::ics(['coach_summary' => null, 'coach_rationale' => null] + $s, 'https://t', 't', 1);
        self::assertMatchesRegularExpression('/DESCRIPTION:Priorität B · 45 min\\\\n- Kniebeuge/u', str_replace("\r\n ", '', $old), 'Altdaten ohne Kurzsatz: Kurzplan zuerst');
    }
}
