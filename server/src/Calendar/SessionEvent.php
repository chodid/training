<?php

declare(strict_types=1);

namespace Training\Calendar;

use Training\Db;
use Training\View\Labels;

/**
 * Termin (iCalendar, RFC 5545) für eine Einheit (AP-11, D-50): ganztägig am Datum der Einheit, Titel mit Typ und
 * Status-Markierung (✓ erledigt/teilweise, ausgelassen = abgesagt), Beschreibung mit Kurzplan und Link zur App.
 * Eine Einheit = eine Ressource mit fester UID, damit Änderungen denselben Termin ersetzen.
 * Erinnerung (D-52): am Tag der Einheit zur eingestellten Uhrzeit, nur für geplante und verschobene Einheiten.
 */
final class SessionEvent
{
    public const PREFIX = 'training-session-';

    public static function resource(int $sessionId): string
    {
        return self::PREFIX . $sessionId . '.ics';
    }

    /** Einheiten-ID aus einem Ressourcennamen, null bei fremden Terminen. */
    public static function idFromResource(string $name): ?int
    {
        return preg_match('/^' . preg_quote(self::PREFIX, '/') . '(\d+)\.ics$/', $name, $m) ? (int) $m[1] : null;
    }

    /**
     * @param array<string, mixed> $s Einheit wie WeekRepository::session() (plan dekodiert)
     * @param ?string $reminder Uhrzeit 'HH:MM' am Tag der Einheit, null = keine Erinnerung
     */
    public static function ics(array $s, string $appUrl, string $host, int $now, ?string $reminder = null): string
    {
        $id = (int) $s['id'];
        $date = (string) $s['date'];
        $type = Labels::TYPES[$s['type']][0] ?? (string) $s['type'];
        $done = in_array($s['status'], ['erledigt', 'teilweise'], true);
        $summary = ($done ? '✓ ' : '') . $type . ': ' . $s['title'];
        $link = rtrim($appUrl, '/') . '/einheit?id=' . $id;
        $modified = Db::time(isset($s['updated_at']) ? (string) $s['updated_at'] : null) ?? $now;

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//training.gen-em.org//Training-App//DE',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:' . self::PREFIX . $id . '@' . $host,
            'DTSTAMP:' . gmdate('Ymd\THis\Z', $now),
            'LAST-MODIFIED:' . gmdate('Ymd\THis\Z', $modified),
            'SEQUENCE:' . max(0, $modified - 1_700_000_000),
            'DTSTART;VALUE=DATE:' . str_replace('-', '', $date),
            'DTEND;VALUE=DATE:' . (new \DateTimeImmutable($date))->modify('+1 day')->format('Ymd'),
            'SUMMARY:' . self::text($summary),
            'DESCRIPTION:' . self::text(self::description($s, $link)),
            'URL:' . $link,
            'CATEGORIES:' . self::text($type),
            'STATUS:' . ($s['status'] === 'ausgelassen' ? 'CANCELLED' : 'CONFIRMED'),
            'TRANSP:TRANSPARENT',
            ...self::alarm($s, $summary, $reminder),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), $lines)) . "\r\n";
    }

    /**
     * VALARM relativ zum Beginn des ganztägigen Termins (00:00 Ortszeit des Kalenders), z. B. PT5H = 05:00 am Tag.
     * @param array<string, mixed> $s
     * @return list<string>
     */
    private static function alarm(array $s, string $summary, ?string $reminder): array
    {
        if ($reminder === null || !in_array($s['status'], ['geplant', 'verschoben'], true) || !preg_match('/^(\d{2}):(\d{2})$/', $reminder, $m)) {
            return [];
        }
        $h = (int) $m[1];
        $min = (int) $m[2];
        $trigger = 'PT' . ($h === 0 && $min === 0 ? '0S' : ($h > 0 ? $h . 'H' : '') . ($min > 0 ? $min . 'M' : ''));

        return ['BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' . self::text($summary), 'TRIGGER;RELATED=START:' . $trigger, 'END:VALARM'];
    }

    /** @param array<string, mixed> $s */
    private static function description(array $s, string $link): string
    {
        $head = ['Priorität ' . $s['priority']];
        if ($s['planned_duration_min'] !== null) {
            $head[] = (int) $s['planned_duration_min'] . ' min';
        }
        if ($s['status'] !== 'geplant') {
            $head[] = Labels::STATUS[$s['status']][0] ?? (string) $s['status'];
        }
        $parts = [implode(' · ', $head)];
        $plan = is_array($s['plan'] ?? null) ? $s['plan'] : [];
        $rows = [];
        foreach ($plan['exercises'] ?? [] as $x) {
            $rows[] = '- ' . $x['name'] . ': ' . $x['sets'] . ' × ' . $x['reps'] . (isset($x['load']) ? ' · ' . $x['load'] : '');
        }
        foreach ($plan['blocks'] ?? [] as $b) {
            $bits = array_filter([
                $b['kind'] ?? null,
                isset($b['sets']) ? $b['sets'] . ' Sätze' : null,
                isset($b['duration_min']) ? $b['duration_min'] . ' min' : null,
                $b['target'] ?? null,
            ]);
            $rows[] = '- ' . implode(' · ', $bits);
        }
        if (isset($plan['summary'])) {
            $rows[] = (string) $plan['summary'];
        }
        if (isset($plan['intervals_workout_text'])) {
            $rows[] = trim((string) $plan['intervals_workout_text']);
        }
        if (isset($plan['notes'])) {
            $rows[] = (string) $plan['notes'];
        }
        if ($rows !== []) {
            $parts[] = implode("\n", $rows);
        }
        if (!empty($s['coach_rationale'])) {
            $parts[] = 'Trainer: ' . $s['coach_rationale'];
        }
        $parts[] = 'In der App: ' . $link;

        return implode("\n\n", $parts);
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
                $limit = 74; // Folgezeilen beginnen mit einem Leerzeichen
            }
            $current .= $char;
        }
        $out[] = $current;

        return implode("\r\n ", $out);
    }
}
