<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Calendar\DayEvent;

/** Sammeltermin je Tag (AP-11, D-60): ganztägig, Titel, Beschreibung je Einheit, Erinnerung, Escaping und Zeilenfaltung nach RFC 5545. */
final class DayEventTest extends TestCase
{
    public function testSingleSessionDay(): void
    {
        $s = self::kraft(['id' => 7, 'title' => 'Kraft; Beine, schwer', 'priority' => 'A', 'status' => 'erledigt',
            'coach_rationale' => "Zeile 1\nZeile 2 mit Ümlauten und einem sehr langen Text, der gefaltet werden muss, damit nichts abgeschnitten wird",
            'plan' => ['exercises' => [['name' => 'Kniebeuge', 'sets' => 3, 'reps' => '6-8', 'load' => '60 kg']]]]);
        $ics = DayEvent::ics('2026-09-30', [$s], 'https://training.example/', 'training.example', 1790000000);

        self::assertStringContainsString("UID:training-tag-2026-09-30@training.example\r\n", $ics);
        self::assertStringContainsString("DTSTART;VALUE=DATE:20260930\r\nDTEND;VALUE=DATE:20261001\r\n", $ics);
        self::assertStringContainsString("SUMMARY:Kraft: Kraft\; Beine\\, schwer\r\n", $ics, 'ohne Status-Markierung');
        self::assertStringContainsString('STATUS:CONFIRMED', $ics);
        self::assertStringContainsString("URL:https://training.example/woche?start=2026-09-28\r\n", $ics, 'Termin führt zur Woche');
        self::assertStringContainsString("CATEGORIES:Kraft\r\n", $ics);
        foreach (explode("\r\n", rtrim($ics)) as $line) {
            self::assertLessThanOrEqual(75, strlen($line), $line);
            self::assertTrue(mb_check_encoding($line, 'UTF-8'), 'keine geteilten UTF-8-Zeichen');
        }
        $unfolded = str_replace("\r\n ", '', $ics);
        self::assertStringContainsString('Kniebeuge: 3 × 6-8 · 60 kg', $unfolded);
        self::assertStringContainsString('Trainer: Zeile 1\nZeile 2 mit Ümlauten', $unfolded);
        self::assertStringContainsString('Priorität A · 60 min · erledigt', $unfolded);
        self::assertStringContainsString('In der App: https://training.example/einheit?id=7', $unfolded);
        self::assertStringNotContainsString('——', $unfolded, 'eine Einheit: keine Überschrift, keine Trennlinie');

        $skipped = DayEvent::ics('2026-09-30', [['status' => 'ausgelassen'] + $s], 'https://training.example', 'training.example', 1790000000);
        self::assertStringContainsString('STATUS:CONFIRMED', $skipped, 'ausgelassen: Termin bleibt');
        self::assertStringContainsString('Priorität A · 60 min · ausgelassen', str_replace("\r\n ", '', $skipped));

        self::assertStringNotContainsString('VALARM', $ics, 'ohne Erinnerung');
        $planned = ['status' => 'geplant'] + $s;
        $alarm = DayEvent::ics('2026-09-30', [$planned], 'https://training.example', 'training.example', 1790000000, '05:00');
        self::assertStringContainsString("BEGIN:VALARM\r\nACTION:DISPLAY\r\nDESCRIPTION:Kraft: Kraft\; Beine\\, schwer\r\nTRIGGER;RELATED=START:PT5H\r\nEND:VALARM\r\nEND:VEVENT", $alarm);
        self::assertStringContainsString('TRIGGER;RELATED=START:PT6H30M', DayEvent::ics('2026-09-30', [$planned], 'https://t', 't', 1, '06:30'));
        self::assertStringContainsString('TRIGGER;RELATED=START:PT0S', DayEvent::ics('2026-09-30', [$planned], 'https://t', 't', 1, '00:00'));
        self::assertStringContainsString('TRIGGER;RELATED=START:PT45M', DayEvent::ics('2026-09-30', [['status' => 'verschoben'] + $s], 'https://t', 't', 1, '00:45'));
        self::assertStringNotContainsString('VALARM', DayEvent::ics('2026-09-30', [$s], 'https://t', 't', 1, '05:00'), 'erledigt: keine Erinnerung');
        self::assertStringNotContainsString('VALARM', DayEvent::ics('2026-09-30', [['status' => 'ausgelassen'] + $s], 'https://t', 't', 1, '05:00'));
    }

    public function testSeveralSessionsShareOneEvent(): void
    {
        $kraft = self::kraft(['id' => 11, 'title' => 'Kraft Unterkörper', 'status' => 'erledigt', 'coach_summary' => 'Beine schwer',
            'coach_rationale' => 'Sehne ruhig, Last halten', 'updated_at' => '2026-09-28 10:00:00']);
        $lauf = self::kraft(['id' => 12, 'type' => 'ausdauer', 'title' => 'Lauf locker Z2', 'priority' => 'B', 'planned_duration_min' => 40,
            'status' => 'geplant', 'coach_summary' => 'Grundlage', 'coach_rationale' => null, 'updated_at' => '2026-09-28 11:00:00',
            'plan' => ['intervals_workout_text' => "- 40m Z2\n", 'summary' => 'Locker laufen']]);
        $boulder = self::kraft(['id' => 13, 'type' => 'klettern', 'title' => 'Boulder', 'status' => 'ausgelassen', 'coach_summary' => null,
            'coach_rationale' => null, 'plan' => ['blocks' => [['kind' => 'bouldern_volumen', 'duration_min' => 60, 'target' => 'Grad 5']]]]);
        $ics = DayEvent::ics('2026-09-30', [$kraft, $lauf, $boulder], 'https://training.example', 'training.example', 1790000000, '05:00');
        $unfolded = str_replace("\r\n ", '', $ics);

        self::assertSame(1, substr_count($ics, 'BEGIN:VEVENT'), 'ein Termin für den Tag');
        self::assertStringContainsString("SUMMARY:Training: Kraft Unterkörper + Lauf locker Z2 + Boulder\r\n", $unfolded);
        self::assertStringContainsString("CATEGORIES:Kraft,Ausdauer,Klettern\r\n", $ics);
        self::assertStringContainsString('STATUS:CONFIRMED', $ics, 'nie abgesagt');
        self::assertStringContainsString('LAST-MODIFIED:20260928T110000Z', $ics, 'jüngste Änderung des Tages');
        self::assertStringContainsString('TRIGGER;RELATED=START:PT5H', $ics, 'Lauf ist noch geplant');
        self::assertStringContainsString('DESCRIPTION:Training: Kraft Unterkörper + Lauf locker Z2 + Boulder', $unfolded, 'Erinnerung mit Titel');

        preg_match('/^DESCRIPTION:(.*)$/m', $unfolded, $m);
        $sections = explode('\n\n——————————\n\n', rtrim($m[1], "\r"));
        self::assertCount(3, $sections, 'je Einheit ein Abschnitt in Planreihenfolge');
        self::assertSame('Kraft: Kraft Unterkörper\nBeine schwer\n\nPriorität B · 60 min · erledigt\n- Kniebeuge: 3 × 8'
            . '\n\nTrainer: Sehne ruhig\, Last halten\n\nIn der App: https://training.example/einheit?id=11', $sections[0], 'Begründung je Einheit');
        self::assertSame('Ausdauer: Lauf locker Z2\nGrundlage\n\nPriorität B · 40 min\nLocker laufen\n- 40m Z2'
            . '\n\nIn der App: https://training.example/einheit?id=12', $sections[1]);
        self::assertSame('Klettern: Boulder\n\nPriorität B · 60 min · ausgelassen\n- Bouldern Volumen · 60 min · Grad 5'
            . '\n\nIn der App: https://training.example/einheit?id=13', $sections[2]);

        $zweiOffen = DayEvent::ics('2026-09-30', [['status' => 'geplant'] + $kraft, ['status' => 'verschoben'] + $lauf], 'https://t', 't', 1, '05:00');
        self::assertSame(1, substr_count($zweiOffen, 'BEGIN:VALARM'), 'eine Erinnerung je Tag, auch bei zwei offenen Einheiten');
        $done = DayEvent::ics('2026-09-30', [$kraft, ['status' => 'teilweise'] + $lauf, $boulder], 'https://t', 't', 1, '05:00');
        self::assertStringNotContainsString('VALARM', $done, 'alle Einheiten erledigt/teilweise/ausgelassen: keine Erinnerung');
        self::assertStringContainsString('SUMMARY:Training: Kraft Unterkörper + Lauf locker Z2 + Boulder', str_replace("\r\n ", '', $done), 'Titel unabhängig vom Status');

        $same = DayEvent::ics('2026-09-30', [$kraft, ['id' => 14, 'title' => 'Rumpf'] + $kraft], 'https://t', 't', 1);
        self::assertStringContainsString("CATEGORIES:Kraft\r\n", $same, 'Typ nur einmal');
    }

    /** AP-13 (5.3): Kurzsatz, Leerzeile, Kurzplan, Leerzeile, ausführlicher Text (≤ 1 000 Zeichen), Link. */
    public function testDescriptionStartsWithSummaryAndShortensLongRationale(): void
    {
        $s = self::kraft(['id' => 8, 'title' => 'Kraft', 'planned_duration_min' => 45, 'coach_summary' => 'Zweite Krafteinheit, Last wie letzte Woche',
            'coach_rationale' => str_repeat('a', 995) . ' bbbbbbbbbb']);
        $unfolded = str_replace("\r\n ", '', DayEvent::ics('2026-09-30', [$s], 'https://training.example', 'training.example', 1790000000));
        preg_match('/^DESCRIPTION:(.*)$/m', $unfolded, $m);
        $parts = explode('\n\n', rtrim($m[1], "\r"));
        self::assertSame('Zweite Krafteinheit\, Last wie letzte Woche', $parts[0]);
        self::assertSame('Priorität B · 45 min\n- Kniebeuge: 3 × 8', $parts[1]);
        self::assertSame('Trainer: ' . str_repeat('a', 995) . ' bbb…', $parts[2], 'gekürzt auf 1 000 Zeichen');
        self::assertSame(1000, mb_strlen(substr($parts[2], strlen('Trainer: '))));
        self::assertSame('In der App: https://training.example/einheit?id=8', $parts[3]);

        $old = DayEvent::ics('2026-09-30', [['coach_summary' => null, 'coach_rationale' => null] + $s], 'https://t', 't', 1);
        self::assertMatchesRegularExpression('/DESCRIPTION:Priorität B · 45 min\\\\n- Kniebeuge/u', str_replace("\r\n ", '', $old), 'Altdaten ohne Kurzsatz: Kurzplan zuerst');
    }

    /** AP-16 6.3: Link je Übung mit Katalogeintrag im Kurzplan; zu lang → Links entfallen von hinten, Kurzplan bleibt. */
    public function testExerciseLinksInShortPlan(): void
    {
        $s = self::kraft(['id' => 7, 'status' => 'geplant', 'plan' => ['exercises' => [
            ['name' => 'Kniebeuge', 'exercise_id' => 'kniebeuge', 'sets' => 3, 'reps' => '8'],
            ['name' => 'Wadenheben', 'sets' => 3, 'reps' => '15'],
        ]]]);
        $text = str_replace("\r\n ", '', DayEvent::ics('2026-09-30', [$s], 'https://training.example', 'training.example', 1));
        self::assertStringContainsString('- Kniebeuge: 3 × 8\n  https://training.example/uebung?id=kniebeuge\n- Wadenheben: 3 × 15', $text);
        self::assertSame(1, substr_count($text, '/uebung?id='), 'nur Übungen mit ID');

        $klettern = self::kraft(['id' => 8, 'type' => 'klettern', 'plan' => ['blocks' => [['kind' => 'hangboard', 'exercise_id' => 'max-hang-20mm', 'sets' => 5], ['kind' => 'bouldern_volumen', 'duration_min' => 30]]]]);
        self::assertStringContainsString('Sätze\n  https://training.example/uebung?id=max-hang-20mm\n- ', str_replace("\r\n ", '', DayEvent::ics('2026-09-30', [$klettern], 'https://training.example', 'training.example', 1)));

        // 30 Übungen: Kurzplan vollständig, Links nur solange Kurzplan + Links ≤ 1 000 Zeichen
        $many = [];
        for ($i = 1; $i <= 30; $i++) {
            $many[] = ['name' => 'Übung ' . $i, 'exercise_id' => 'uebung-nummer-' . $i, 'sets' => 3, 'reps' => '10'];
        }
        $long = str_replace("\r\n ", '', DayEvent::ics('2026-09-30', [self::kraft(['id' => 9, 'plan' => ['exercises' => $many]])], 'https://training.example', 'training.example', 1));
        self::assertStringContainsString('- Übung 30: 3 × 10', $long, 'Kurzplan ungekürzt');
        self::assertStringContainsString('uebung?id=uebung-nummer-1\n', $long, 'erste Links bleiben');
        self::assertStringNotContainsString('uebung?id=uebung-nummer-30', $long, 'hintere Links entfallen');
        // Kurzplan (Abschnitt ab „Priorität“ bis zur Leerzeile) höchstens 1 000 Zeichen
        preg_match('/DESCRIPTION:(.*?)\r\n[A-Z]/s', $long, $m);
        $description = str_replace('\\n', "\n", $m[1]);
        $kurzplan = explode("\n\n", substr($description, (int) strpos($description, 'Priorität')))[0];
        self::assertLessThanOrEqual(1000, mb_strlen($kurzplan));
        self::assertGreaterThan(900, mb_strlen($kurzplan), 'Platz wird genutzt');
    }

    public function testResourceNamesAndRestDays(): void
    {
        self::assertSame('training-tag-2026-09-30.ics', DayEvent::resource('2026-09-30'));
        self::assertSame('training-tag-2026-09-30-2.ics', DayEvent::resource('2026-09-30', 2), 'Fassung nach zweimaligem Löschen');
        self::assertSame('training-session-7.ics', DayEvent::legacyResource(7));
        self::assertSame('2026-09-30', DayEvent::dateFromResource('training-tag-2026-09-30.ics'));
        self::assertSame('2026-09-30', DayEvent::dateFromResource('training-tag-2026-09-30-12.ics'));
        self::assertNull(DayEvent::dateFromResource('training-tag-2026-09-30-0.ics'));
        self::assertNull(DayEvent::dateFromResource('training-tag-2026-09-30-x.ics'));
        self::assertStringContainsString("UID:training-tag-2026-09-30-2@t\r\n", DayEvent::ics('2026-09-30', [self::kraft([])], 'https://t', 't', 1, null, 2));
        self::assertNull(DayEvent::dateFromResource('training-session-7.ics'));
        self::assertTrue(DayEvent::isOwn('training-tag-2026-09-30.ics'));
        self::assertTrue(DayEvent::isOwn('training-session-7.ics'), 'alter Einzeltermin (bis 0.18.0)');
        self::assertFalse(DayEvent::isOwn('fremd.ics'));
        self::assertFalse(DayEvent::isOwn('training-tag-morgen.ics'));
        self::assertFalse(DayEvent::isOwn('training-session-7.ics.bak'));

        $ruhe = ['type' => 'ruhe'] + self::kraft(['id' => 9]);
        $kraft = self::kraft(['id' => 10]);
        self::assertSame([$kraft], DayEvent::relevant([$ruhe, $kraft]));
        self::assertSame([], DayEvent::relevant([$ruhe]));
        $this->expectException(\InvalidArgumentException::class);
        DayEvent::ics('2026-09-30', [], 'https://t', 't', 1);
    }

    /** @param array<string, mixed> $over @return array<string, mixed> */
    private static function kraft(array $over): array
    {
        return $over + ['id' => 1, 'date' => '2026-09-30', 'type' => 'kraft', 'title' => 'Kraft', 'priority' => 'B', 'planned_duration_min' => 60,
            'status' => 'geplant', 'coach_summary' => null, 'coach_rationale' => null,
            'plan' => ['exercises' => [['name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8']]], 'updated_at' => '2026-09-28 10:00:00'];
    }
}
