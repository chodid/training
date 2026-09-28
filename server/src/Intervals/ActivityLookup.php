<?php

declare(strict_types=1);

namespace Training\Intervals;

use PDO;
use Training\Clock;
use Training\Db;

/**
 * Aktivitäten aus Intervals.icu für die Webseite, mit Kurzcache in ext_cache (5 Minuten, Abschnitt 9)
 * und Zuordnung zu geplanten Ausdauereinheiten (D-08, Abschnitt 9 „Matching“).
 * Fehler der API werden nicht nach außen geworfen: die Webseite zeigt dann nur die eigenen Daten.
 */
final class ActivityLookup
{
    private const TTL = 300;
    /** Aktivitätstypen, die als Ausdauer gelten (heuristisches Matching ohne Event-Verknüpfung). */
    private const ENDURANCE = ['Run', 'TrailRun', 'VirtualRun', 'Walk', 'Hike', 'Ride', 'VirtualRide', 'GravelRide', 'MountainBikeRide', 'EBikeRide', 'BackcountrySki', 'NordicSki', 'Snowshoe', 'Swim', 'Rowing', 'Elliptical'];

    public ?string $error = null;

    public function __construct(private readonly ?IntervalsClient $client, private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    /** @return list<array<string, mixed>> */
    public function activities(string $from, string $to): array
    {
        if ($this->client === null) {
            return [];
        }
        $key = 'activities:' . $from . ':' . $to;
        $stmt = $this->pdo->prepare('SELECT payload_json FROM ext_cache WHERE cache_key = ? AND fetched_at > ?');
        $stmt->execute([$key, Db::ts($this->clock->now() - self::TTL)]);
        $cached = $stmt->fetchColumn();
        if (is_string($cached)) {
            $data = json_decode($cached, true);
            if (is_array($data)) {
                return $data;
            }
        }
        try {
            $data = $this->client->activities($from, $to);
        } catch (IntervalsException $e) {
            $this->error = $e->getMessage();

            return [];
        }
        $this->pdo->prepare('REPLACE INTO ext_cache (cache_key, payload_json, fetched_at) VALUES (?, ?, ?)')
            ->execute([$key, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), Db::ts($this->clock->now())]);
        $this->pdo->prepare('DELETE FROM ext_cache WHERE fetched_at < ?')->execute([Db::ts($this->clock->now() - 86400)]);

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
