<?php

declare(strict_types=1);

namespace Training\Intervals;

use PDO;
use Training\Clock;

/**
 * Aktivitäten für Webseite und MCP aus dem Spiegel (D-43, read-through über Mirror) und Zuordnung zu geplanten
 * Ausdauereinheiten (D-08, Abschnitt 9 „Matching“). Fehler der API werden nicht nach außen geworfen: die Daten
 * kommen dann aus dem Spiegel, $error enthält die Meldung.
 */
final class ActivityLookup
{
    /** Aktivitätstypen, die als Ausdauer gelten (heuristisches Matching ohne Event-Verknüpfung). */
    private const ENDURANCE = ['Run', 'TrailRun', 'VirtualRun', 'Walk', 'Hike', 'Ride', 'VirtualRide', 'GravelRide', 'MountainBikeRide', 'EBikeRide', 'BackcountrySki', 'NordicSki', 'Snowshoe', 'Swim', 'Rowing', 'Elliptical'];

    public ?string $error = null;
    private readonly Mirror $mirror;

    public function __construct(?IntervalsClient $client, PDO $pdo, Clock $clock)
    {
        $this->mirror = new Mirror($client, $pdo, $clock);
    }

    /** @return list<array<string, mixed>> */
    public function activities(string $from, string $to): array
    {
        $data = $this->mirror->activities($from, $to);
        $this->error = $this->mirror->error;

        return $data;
    }

    /**
     * Aktivität zu einer Ausdauereinheit: zuerst über die Verknüpfung von Intervals.icu (paired_event_id),
     * sonst die erste Ausdauer-Aktivität am selben Tag, die keiner anderen Einheit zugeordnet ist.
     *
     * @param array<string, mixed>       $session
     * @param list<array<string, mixed>> $activities
     * @param list<string>               $taken IDs bereits zugeordneter Aktivitäten
     * @return array<string, mixed>|null
     */
    public static function match(array $session, array $activities, array $taken = []): ?array
    {
        $eventId = $session['intervals_event_id'] ?? null;
        if ($eventId !== null) {
            foreach ($activities as $a) {
                if ((string) ($a['paired_event_id'] ?? '') === (string) $eventId) {
                    return $a + ['match' => 'intervals'];
                }
            }
        }
        foreach ($activities as $a) {
            if (substr((string) ($a['start_date_local'] ?? ''), 0, 10) === $session['date']
                && in_array($a['type'] ?? '', self::ENDURANCE, true)
                && !in_array((string) ($a['id'] ?? ''), $taken, true)) {
                return $a + ['match' => 'datum'];
            }
        }

        return null;
    }
}
