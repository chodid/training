<?php

declare(strict_types=1);

namespace Training\Calendar;

use Training\Db;

/**
 * Termin für Blockbilanz und Zielklärung (iCalendar, AP-15, docs/konzept/blockbilanz.md 7, E-06/E-15/E-22): ein Termin
 * je Block am Blockende mit Uhrzeit (Standard 08:00–10:00 in der Zeitzone des Athleten, als UTC-Zeiten) und Erinnerung
 * vor Beginn (Standard 24 h = Vortag 08:00). Titel nach dem, was noch fehlt: „Blockbilanz + Zielklärung: <Name>“,
 * „Blockbilanz: …“ oder „Zielklärung: …“. Sicherheitsnetz E-03: liegt das Blockende später als 16 Wochen nach
 * Blockbeginn, steht der Termin auf Blockbeginn + 112 Tage. Fassung je Ressource wie bei den Tagesterminen
 * (Nextcloud-Papierkorb, DayEvent).
 */
final class BlockEvent
{
    public const PREFIX = 'training-block-';
    public const MAX_DAYS = 112;
    private const MISSING = ['bilanz' => 'Blockbilanz', 'zielklaerung' => 'Zielklärung'];

    public static function resource(int $blockId, int $generation = 0): string
    {
        return self::name($blockId, $generation) . '.ics';
    }

    /** Block-ID aus dem Ressourcennamen (jede Fassung), null bei anderen Namen. */
    public static function blockIdFromResource(string $name): ?int
    {
        return preg_match('/^' . preg_quote(self::PREFIX, '/') . '(\d+)(?:-[1-9]\d*)?\.ics$/', $name, $m) ? (int) $m[1] : null;
    }

    private static function name(int $blockId, int $generation): string
    {
        return self::PREFIX . $blockId . ($generation > 0 ? '-' . $generation : '');
    }

    /** Tag des Termins: Blockende, höchstens Blockbeginn + 112 Tage (E-03). @param array<string, mixed> $block */
    public static function date(array $block): string
    {
        $limit = \Training\Dates::addDays((string) $block['start_date'], self::MAX_DAYS);

        return min((string) $block['end_date'], $limit);
    }

    /** Titel nach dem, was fehlt. @param list<string> $missing Teilmenge von bilanz, zielklaerung (nicht leer) */
    public static function summary(string $blockName, array $missing): string
    {
        $parts = array_values(array_intersect_key(self::MISSING, array_flip($missing)));

        return implode(' + ', $parts) . ': ' . $blockName;
    }

    /**
     * @param array<string, mixed> $block Zeile aus training_block
     * @param list<string> $missing fehlende Teile (bilanz, zielklaerung), mindestens einer
     * @param array{beginn: string, dauer_min: int, erinnerung_h: int} $settings Einstellungen E-22
     * @param int $modified letzte Änderung (Unix-Sekunden) für LAST-MODIFIED und SEQUENCE
     */
    public static function ics(array $block, array $missing, string $appUrl, string $host, string $tz, array $settings, int $now, int $modified, int $generation = 0): string
    {
        if ($missing === []) {
            throw new \InvalidArgumentException('Blocktermin ohne fehlenden Teil: ' . $block['id']);
        }
        $date = self::date($block);
        $start = new \DateTimeImmutable($date . ' ' . $settings['beginn'], new \DateTimeZone($tz));
        $end = $start->modify('+' . $settings['dauer_min'] . ' minutes');
        $utc = new \DateTimeZone('UTC');
        $base = rtrim($appUrl, '/');
        $link = $base . '/block?id=' . (int) $block['id'];
        $summary = self::summary((string) $block['name'], $missing);
        $period = (new \DateTimeImmutable((string) $block['start_date']))->format('d.m.Y') . ' – ' . (new \DateTimeImmutable((string) $block['end_date']))->format('d.m.Y');
        $purpose = in_array('bilanz', $missing, true) && in_array('zielklaerung', $missing, true)
            ? 'Rückblick auf den Block und Ziele für den nächsten'
            : (in_array('bilanz', $missing, true) ? 'Rückblick auf den Block (Blockbilanz)' : 'Ziele für den nächsten Block (Zielklärung)');
        $description = $purpose . ' – im Projekt-Chat mit get_handover beginnen.'
            . "\n\nBlock „" . $block['name'] . '“: ' . $period
            . ($date !== (string) $block['end_date'] ? "\nTermin nach 16 Wochen (Blockende liegt später)." : '')
            . "\n\nIn der App: " . $link;

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//training.gen-em.org//Training-App//DE',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:' . self::name((int) $block['id'], $generation) . '@' . $host,
            'DTSTAMP:' . gmdate('Ymd\THis\Z', $now),
            'LAST-MODIFIED:' . gmdate('Ymd\THis\Z', $modified),
            'SEQUENCE:' . max(0, $modified - 1_700_000_000),
            'DTSTART:' . $start->setTimezone($utc)->format('Ymd\THis\Z'),
            'DTEND:' . $end->setTimezone($utc)->format('Ymd\THis\Z'),
            'SUMMARY:' . self::text($summary),
            'DESCRIPTION:' . self::text($description),
            'URL:' . $link,
            'CATEGORIES:Planung',
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE',
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:' . self::text($summary),
            'TRIGGER:' . self::trigger((int) $settings['erinnerung_h']),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), $lines)) . "\r\n";
    }

    /** Erinnerung $hours Stunden vor Beginn: -P1D bei 24 h, -PT5H, 0 = zu Beginn. */
    public static function trigger(int $hours): string
    {
        return match (true) {
            $hours <= 0 => 'PT0S',
            $hours % 24 === 0 => '-P' . intdiv($hours, 24) . 'D',
            default => '-PT' . $hours . 'H',
        };
    }

    /** Letzte Änderung aus Block, Reviews und Einstellungen (UTC-Zeitstempel der Datenbank). @param list<?string> $timestamps */
    public static function modified(array $timestamps, int $fallback): int
    {
        $times = array_filter(array_map(static fn (?string $t): ?int => Db::time($t), $timestamps));

        return $times === [] ? $fallback : max($times);
    }

    /** TEXT-Wert escapen (RFC 5545 3.3.11). */
    private static function text(string $v): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n", "\r"], ["\\\\", '\;', '\,', '\n', '\n', '\n'], $v);
    }

    /** Zeilen nach 75 Oktetten falten, ohne UTF-8-Zeichen zu teilen. */
    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }
        $out = [];
        $current = '';
        $limit = 75;
        foreach (mb_str_split($line) as $char) {
            if (strlen($current) + strlen($char) > $limit) {
                $out[] = $current;
                $current = '';
                $limit = 74;
            }
            $current .= $char;
        }
        $out[] = $current;

        return implode("\r\n ", $out);
    }
}
