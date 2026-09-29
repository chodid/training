<?php

declare(strict_types=1);

namespace Training\Calendar;

use Training\Db;
use Training\Dates;
use Training\View\Labels;

/**
 * Sammeltermin (iCalendar, RFC 5545) für einen Trainingstag (AP-11, D-60 ändert D-50): ein ganztägiger Termin je Tag
 * mit allen Einheiten außer Ruhetagen. Titel „Typ: Titel“ bei einer Einheit, sonst „Training: Titel 1 + Titel 2“;
 * ohne Status-Markierung und nie abgesagt – der Status steht je Einheit in der Beschreibung.
 * Ein Tag = eine Ressource mit fester UID, damit Änderungen denselben Termin ersetzen. Wird der Termin eines Tages
 * gelöscht, bekommt der nächste eine neue Fassung (Name und UID mit Zähler): Nextcloud legt Gelöschtes in den
 * Papierkorb und lehnt bis Version 34.0.1 das wiederholte Löschen und Anlegen derselben Adresse/UID mit HTTP 403 ab.
 * Erinnerung (D-52): am Tag zur eingestellten Uhrzeit, solange mindestens eine Einheit geplant oder verschoben ist.
 */
final class DayEvent
{
    public const PREFIX = 'training-tag-';
    /** Ressourcen der Einzeltermine bis 0.18.0 (eine je Einheit); der Abgleich ersetzt sie durch Sammeltermine */
    public const LEGACY_PREFIX = 'training-session-';
    /** Ausführlicher Text je Einheit in der Terminbeschreibung, höchstens so viele Zeichen (AP-13, 5.3) */
    private const TEXT_MAX = 1000;
    /** Kurzplan mit Links auf die Übungen im Katalog, höchstens so viele Zeichen (AP-16, 6.3; Links entfallen zuerst) */
    private const LINKS_MAX = 1000;
    /** Trennlinie zwischen den Einheiten eines Tages */
    private const SEPARATOR = '——————————';

    /** Ressourcenname des Tages in Fassung $generation (0 = erste, danach „-1“, „-2“ … nach jedem Löschen). */
    public static function resource(string $date, int $generation = 0): string
    {
        return self::name($date, $generation) . '.ics';
    }

    /** Einzeltermin einer Einheit aus der Zeit vor D-60 (bis 0.18.0). */
    public static function legacyResource(int $sessionId): string
    {
        return self::LEGACY_PREFIX . $sessionId . '.ics';
    }

    /** Datum aus dem Ressourcennamen eines Sammeltermins (jede Fassung), null bei anderen Namen. */
    public static function dateFromResource(string $name): ?string
    {
        return preg_match('/^' . preg_quote(self::PREFIX, '/') . '(\d{4}-\d{2}-\d{2})(?:-[1-9]\d*)?\.ics$/', $name, $m) ? $m[1] : null;
    }

    private static function name(string $date, int $generation): string
    {
        return self::PREFIX . $date . ($generation > 0 ? '-' . $generation : '');
    }

    /** Eigener Termin der App (Sammeltermin oder alter Einzeltermin); fremde Termine bleiben unberührt. */
    public static function isOwn(string $name): bool
    {
        return self::dateFromResource($name) !== null
            || preg_match('/^' . preg_quote(self::LEGACY_PREFIX, '/') . '\d+\.ics$/', $name) === 1;
    }

    /** Einheiten, die in den Termin gehören (ohne Ruhetage), in Planreihenfolge. @param list<array<string, mixed>> $sessions @return list<array<string, mixed>> */
    public static function relevant(array $sessions): array
    {
        return array_values(array_filter($sessions, static fn (array $s): bool => $s['type'] !== 'ruhe'));
    }

    /**
     * @param list<array<string, mixed>> $sessions Einheiten des Tages ohne Ruhetage in Planreihenfolge, wie
     *     WeekRepository::sessions() (plan dekodiert); mindestens eine
     * @param ?string $reminder Uhrzeit 'HH:MM' am Tag, null = keine Erinnerung
     * @param int $generation Fassung des Tagestermins (bestimmt die UID wie den Ressourcennamen)
     */
    public static function ics(string $date, array $sessions, string $appUrl, string $host, int $now, ?string $reminder = null, int $generation = 0): string
    {
        if ($sessions === []) {
            throw new \InvalidArgumentException('Sammeltermin ohne Einheiten: ' . $date);
        }
        $summary = self::summary($sessions);
        $base = rtrim($appUrl, '/');
        $modified = $now;
        $times = array_filter(array_map(static fn (array $s): ?int => Db::time(isset($s['updated_at']) ? (string) $s['updated_at'] : null), $sessions));
        if ($times !== []) {
            $modified = max($times);
        }
        $types = array_values(array_unique(array_map(self::type(...), $sessions)));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//training.gen-em.org//Training-App//DE',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:' . self::name($date, $generation) . '@' . $host,
            'DTSTAMP:' . gmdate('Ymd\THis\Z', $now),
            'LAST-MODIFIED:' . gmdate('Ymd\THis\Z', $modified),
            'SEQUENCE:' . max(0, $modified - 1_700_000_000),
            'DTSTART;VALUE=DATE:' . str_replace('-', '', $date),
            'DTEND;VALUE=DATE:' . (new \DateTimeImmutable($date))->modify('+1 day')->format('Ymd'),
            'SUMMARY:' . self::text($summary),
            'DESCRIPTION:' . self::text(self::description($sessions, $base)),
            'URL:' . $base . '/woche?start=' . Dates::monday($date),
            'CATEGORIES:' . implode(',', array_map(self::text(...), $types)),
            'STATUS:CONFIRMED',
            'TRANSP:TRANSPARENT',
            ...self::alarm($sessions, $summary, $reminder),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), $lines)) . "\r\n";
    }

    /** Titel: „Typ: Titel“ bei einer Einheit, sonst „Training: Titel 1 + Titel 2“ (Entscheidung des Athleten, D-60). @param list<array<string, mixed>> $sessions */
    private static function summary(array $sessions): string
    {
        if (count($sessions) === 1) {
            return self::type($sessions[0]) . ': ' . $sessions[0]['title'];
        }

        return 'Training: ' . implode(' + ', array_map(static fn (array $s): string => (string) $s['title'], $sessions));
    }

    /** @param array<string, mixed> $s */
    private static function type(array $s): string
    {
        return Labels::TYPES[$s['type']][0] ?? (string) $s['type'];
    }

    /**
     * VALARM relativ zum Beginn des ganztägigen Termins (00:00 Ortszeit des Kalenders), z. B. PT5H = 05:00 am Tag.
     * @param list<array<string, mixed>> $sessions
     * @return list<string>
     */
    private static function alarm(array $sessions, string $summary, ?string $reminder): array
    {
        $open = array_filter($sessions, static fn (array $s): bool => in_array($s['status'], ['geplant', 'verschoben'], true));
        if ($reminder === null || $open === [] || !preg_match('/^(\d{2}):(\d{2})$/', $reminder, $m)) {
            return [];
        }
        $h = (int) $m[1];
        $min = (int) $m[2];
        $trigger = 'PT' . ($h === 0 && $min === 0 ? '0S' : ($h > 0 ? $h . 'H' : '') . ($min > 0 ? $min . 'M' : ''));

        return ['BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' . self::text($summary), 'TRIGGER;RELATED=START:' . $trigger, 'END:VALARM'];
    }

    /**
     * Beschreibung: je Einheit ein Abschnitt in Planreihenfolge, bei mehreren Einheiten mit Überschrift „Typ: Titel“
     * und Trennlinie dazwischen.
     * @param list<array<string, mixed>> $sessions
     */
    private static function description(array $sessions, string $base): string
    {
        $several = count($sessions) > 1;
        $blocks = [];
        foreach ($sessions as $s) {
            $blocks[] = self::section($s, $base, $several);
        }

        return implode("\n\n" . self::SEPARATOR . "\n\n", $blocks);
    }

    /**
     * Abschnitt einer Einheit (AP-13, 5.3): [Überschrift,] Kurzsatz, Leerzeile, Kurzplan (Priorität, Dauer, Status,
     * Übungen), Leerzeile, ausführlicher Text (höchstens 1 000 Zeichen), Link zur Einheit.
     * @param array<string, mixed> $s
     */
    private static function section(array $s, string $base, bool $heading): string
    {
        $head = ['Priorität ' . $s['priority']];
        if ($s['planned_duration_min'] !== null) {
            $head[] = (int) $s['planned_duration_min'] . ' min';
        }
        if ($s['status'] !== 'geplant') {
            $head[] = Labels::STATUS[$s['status']][0] ?? (string) $s['status'];
        }
        $parts = [];
        $first = array_filter([
            $heading ? self::type($s) . ': ' . $s['title'] : null,
            trim((string) ($s['coach_summary'] ?? '')),
        ], static fn (?string $v): bool => $v !== null && $v !== '');
        if ($first !== []) {
            $parts[] = implode("\n", $first);
        }
        $plan = is_array($s['plan'] ?? null) ? $s['plan'] : [];
        $rows = [implode(' · ', $head)];
        $links = []; // Zeilenindex → Link auf die Übung im Katalog (AP-16, 6.3)
        foreach ($plan['exercises'] ?? [] as $x) {
            $rows[] = '- ' . $x['name'] . ': ' . $x['sets'] . ' × ' . $x['reps'] . (isset($x['load']) ? ' · ' . $x['load'] : '');
            if (is_string($x['exercise_id'] ?? null)) {
                $links[count($rows) - 1] = '  ' . $base . '/uebung?id=' . rawurlencode($x['exercise_id']);
            }
        }
        foreach ($plan['blocks'] ?? [] as $b) {
            $kind = (string) ($b['kind'] ?? '');
            $bits = array_filter([
                $kind === '' ? null : (Labels::BLOCK_KINDS[$kind] ?? $kind),
                isset($b['sets']) ? $b['sets'] . ' Sätze' : null,
                isset($b['duration_min']) ? $b['duration_min'] . ' min' : null,
                $b['target'] ?? null,
            ]);
            $rows[] = '- ' . implode(' · ', $bits);
            if (is_string($b['exercise_id'] ?? null)) {
                $links[count($rows) - 1] = '  ' . $base . '/uebung?id=' . rawurlencode($b['exercise_id']);
            }
        }
        $rows = self::withLinks($rows, $links);
        if (isset($plan['summary'])) {
            $rows[] = (string) $plan['summary'];
        }
        if (isset($plan['intervals_workout_text'])) {
            $rows[] = trim((string) $plan['intervals_workout_text']);
        }
        if (isset($plan['notes'])) {
            $rows[] = (string) $plan['notes'];
        }
        $parts[] = implode("\n", $rows);
        $text = trim((string) ($s['coach_rationale'] ?? ''));
        if ($text !== '') {
            $parts[] = 'Trainer: ' . (mb_strlen($text) > self::TEXT_MAX ? rtrim(mb_substr($text, 0, self::TEXT_MAX - 1)) . '…' : $text);
        }
        $parts[] = 'In der App: ' . $base . '/einheit?id=' . (int) $s['id'];

        return implode("\n\n", $parts);
    }

    /**
     * Links je Übung unter die Zeile des Kurzplans (AP-16, 6.3): Kurzplan und Links zusammen höchstens LINKS_MAX Zeichen;
     * reicht der Platz nicht, entfallen Links (von hinten), der Kurzplan selbst wird nie gekürzt.
     * @param list<string> $rows
     * @param array<int, string> $links Zeilenindex → Linkzeile
     * @return list<string>
     */
    private static function withLinks(array $rows, array $links): array
    {
        $length = mb_strlen(implode("\n", $rows));
        $keep = [];
        foreach ($links as $i => $line) {
            if ($length + 1 + mb_strlen($line) > self::LINKS_MAX) {
                break;
            }
            $length += 1 + mb_strlen($line);
            $keep[$i] = $line;
        }
        $out = [];
        foreach ($rows as $i => $row) {
            $out[] = $row;
            if (isset($keep[$i])) {
                $out[] = $keep[$i];
            }
        }

        return $out;
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
