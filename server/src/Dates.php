<?php

declare(strict_types=1);

namespace Training;

/** Kalenderhilfen für die Webseite (deutsche Namen, Wochen ab Montag, Zeitzone des Athleten). */
final class Dates
{
    public const WEEKDAYS = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
    public const WEEKDAYS_SHORT = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
    public const MONTHS = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

    public static function today(Clock $clock, string $tz): string
    {
        return (new \DateTimeImmutable('@' . $clock->now()))->setTimezone(new \DateTimeZone($tz))->format('Y-m-d');
    }

    public static function isDate(?string $date): bool
    {
        if ($date === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $date));

        return checkdate($m, $d, $y);
    }

    public static function monday(string $date): string
    {
        $d = new \DateTimeImmutable($date);

        return $d->modify('-' . ((int) $d->format('N') - 1) . ' day')->format('Y-m-d');
    }

    public static function addDays(string $date, int $days): string
    {
        return (new \DateTimeImmutable($date))->modify(($days >= 0 ? '+' : '') . $days . ' day')->format('Y-m-d');
    }

    /** Montag = 0 … Sonntag = 6 */
    public static function weekdayIndex(string $date): int
    {
        return (int) (new \DateTimeImmutable($date))->format('N') - 1;
    }

    /** „Donnerstag, 24.09.“ bzw. mit Jahr „Donnerstag, 24.09.2026“ */
    public static function long(string $date, bool $year = false): string
    {
        $d = new \DateTimeImmutable($date);

        return self::WEEKDAYS[self::weekdayIndex($date)] . ', ' . $d->format($year ? 'd.m.Y' : 'd.m.');
    }

    /** „Mi 23.09.“ */
    public static function short(string $date): string
    {
        return self::WEEKDAYS_SHORT[self::weekdayIndex($date)] . ' ' . (new \DateTimeImmutable($date))->format('d.m.');
    }

    /** „21. – 27. September“ bzw. „29. September – 5. Oktober“ */
    public static function weekRange(string $monday): string
    {
        $a = new \DateTimeImmutable($monday);
        $b = $a->modify('+6 day');
        $ma = self::MONTHS[(int) $a->format('n') - 1];
        $mb = self::MONTHS[(int) $b->format('n') - 1];

        return $ma === $mb
            ? $a->format('j') . '. – ' . $b->format('j') . '. ' . $mb
            : $a->format('j') . '. ' . $ma . ' – ' . $b->format('j') . '. ' . $mb;
    }

    public static function isoWeek(string $date): int
    {
        return (int) (new \DateTimeImmutable($date))->format('W');
    }
}
