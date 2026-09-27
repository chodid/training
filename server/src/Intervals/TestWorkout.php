<?php

declare(strict_types=1);

namespace Training\Intervals;

/**
 * Test-Event für die Abnahme von AP-02: strukturierte Laufeinheit mit HF-Zonen (ein Zieltyp pro Schritt, V-01)
 * in der Workout-Textsyntax von Intervals.icu. Erkennbar an external_id, damit Ändern/Löschen gezielt möglich ist.
 */
final class TestWorkout
{
    public const EXTERNAL_ID = 'training-app-test';
    public const NAME = 'Testeinheit Training-App';

    public const DESCRIPTION = <<<'TXT'
Testeinheit aus der Training-App (AP-02). Nicht laufen, nur prüfen, ob Schritte und Ziele auf der Uhr stimmen.

Aufwärmen
- 10m Z1 HR

Hauptteil 3x
- 3m Z3 HR
- 2m Z1 HR

Auslaufen
- 5m Z1 HR
TXT;

    /** @return array<string, mixed> */
    public static function event(string $date): array
    {
        return [
            'category' => 'WORKOUT',
            'type' => 'Run',
            'name' => self::NAME,
            'start_date_local' => $date . 'T00:00:00',
            'description' => self::DESCRIPTION,
            'external_id' => self::EXTERNAL_ID,
        ];
    }

    /**
     * @param list<array<string, mixed>> $events
     * @return list<array<string, mixed>>
     */
    public static function find(array $events): array
    {
        return array_values(array_filter($events, static fn (array $e): bool => ($e['external_id'] ?? null) === self::EXTERNAL_ID));
    }
}
