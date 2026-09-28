<?php

declare(strict_types=1);

namespace Training\Intervals;

use PDO;
use Training\Clock;
use Training\Db;

/**
 * Spiegel der Intervals.icu-Daten in MySQL (D-43, ändert D-09). Lesen „read-through“: ein Zeitraum wird höchstens
 * alle 5 Minuten live abgefragt und dabei in den Spiegel übernommen (inkl. Entfernen dort gelöschter Aktivitäten);
 * sonst und bei Fehlern der API kommen die Daten aus dem Spiegel. Der Cronjob (/cron/intervals-sync) hält den Spiegel
 * auch für nie angesehene Zeiträume aktuell. Antworten haben das Format der API (Felder wie dort benannt).
 */
final class Mirror
{
    private const TTL = 300;

    /** Übernommene Felder (Zusammenfassung, keine Streams – N7). */
    public const ACTIVITY_FIELDS = ['id', 'type', 'name', 'start_date_local', 'moving_time', 'elapsed_time', 'distance', 'total_elevation_gain',
        'average_heartrate', 'max_heartrate', 'average_speed', 'icu_training_load', 'icu_rpe', 'feel', 'paired_event_id', 'icu_hr_zone_times', 'trainer'];
    public const WELLNESS_FIELDS = ['id', 'hrv', 'hrvSDNN', 'restingHR', 'sleepSecs', 'sleepScore', 'sleepQuality', 'ctl', 'atl', 'rampRate',
        'weight', 'readiness', 'soreness', 'fatigue', 'stress', 'mood', 'motivation', 'spO2', 'respiration', 'steps', 'comments'];

    /** Fehler der letzten Live-Abfrage (Daten kamen dann aus dem Spiegel). */
    public ?string $error = null;

    public function __construct(private readonly ?IntervalsClient $client, private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    /** @return list<array<string, mixed>> */
    public function activities(string $from, string $to, bool $forceLive = false): array
    {
        if ($this->client !== null && ($forceLive || !$this->fresh('activities', $from, $to))) {
            try {
                $this->storeActivities($from, $to, $this->client->activities($from, $to));
                $this->markFresh('activities', $from, $to);
            } catch (IntervalsException $e) {
                $this->error = $e->getMessage();
            } catch (\PDOException) {
                $this->error = 'Spiegel nicht beschreibbar (Update erforderlich?)';
            }
        }
        return $this->read('SELECT data_json FROM ext_activity WHERE date BETWEEN ? AND ? ORDER BY start_date_local, id', [$from, $to]);
    }

    /** @return list<array<string, mixed>> */
    public function wellness(string $from, string $to, bool $forceLive = false): array
    {
        if ($this->client !== null && ($forceLive || !$this->fresh('wellness', $from, $to))) {
            try {
                $this->storeWellness($this->client->wellness($from, $to));
                $this->markFresh('wellness', $from, $to);
            } catch (IntervalsException $e) {
                $this->error = $e->getMessage();
            } catch (\PDOException) {
                $this->error = 'Spiegel nicht beschreibbar (Update erforderlich?)';
            }
        }
        return $this->read('SELECT data_json FROM ext_wellness WHERE date BETWEEN ? AND ? ORDER BY date', [$from, $to]);
    }

    /**
     * Abgleich für den Cronjob.
     *
     * @return array{aktivitaeten: int, wellness_tage: int, von: string, bis: string}
     * @throws IntervalsException
     */
    public function sync(string $from, string $to): array
    {
        if ($this->client === null) {
            throw new IntervalsException('Intervals.icu nicht eingerichtet.');
        }
        $activities = $this->client->activities($from, $to);
        $this->storeActivities($from, $to, $activities);
        $wellness = $this->client->wellness($from, $to);
        $this->storeWellness($wellness);
        $this->markFresh('activities', $from, $to);
        $this->markFresh('wellness', $from, $to);

        return ['aktivitaeten' => count($activities), 'wellness_tage' => count($wellness), 'von' => $from, 'bis' => $to];
    }

    /** @return array{aktivitaeten: int, wellness_tage: int, erste: ?string, letzte: ?string} */
    public function stats(): array
    {
        $a = $this->pdo->query('SELECT COUNT(*) AS n, MIN(date) AS von, MAX(date) AS bis FROM ext_activity')->fetch();
        $w = (int) $this->pdo->query('SELECT COUNT(*) FROM ext_wellness')->fetchColumn();

        return ['aktivitaeten' => (int) $a['n'], 'wellness_tage' => $w, 'erste' => $a['von'], 'letzte' => $a['bis']];
    }

    /** @param list<array<string, mixed>> $list */
    private function storeActivities(string $from, string $to, array $list): void
    {
        $now = Db::ts($this->clock->now());
        $ids = [];
        $upsert = $this->pdo->prepare('REPLACE INTO ext_activity (id, date, start_date_local, type, paired_event_id, data_json, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($list as $a) {
            $id = (string) ($a['id'] ?? '');
            $start = (string) ($a['start_date_local'] ?? '');
            if ($id === '' || !preg_match('/^\d{4}-\d{2}-\d{2}/', $start)) {
                continue;
            }
            $ids[] = $id;
            $data = array_intersect_key($a, array_flip(self::ACTIVITY_FIELDS));
            $upsert->execute([$id, substr($start, 0, 10), str_replace('T', ' ', substr($start, 0, 19)), isset($a['type']) ? (string) $a['type'] : null,
                isset($a['paired_event_id']) && is_numeric($a['paired_event_id']) ? (int) $a['paired_event_id'] : null,
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $now]);
        }
        // In Intervals.icu gelöschte Aktivitäten des Zeitraums entfernen
        $sql = 'DELETE FROM ext_activity WHERE date BETWEEN ? AND ?';
        $params = [$from, $to];
        if ($ids !== []) {
            $sql .= ' AND id NOT IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            $params = [...$params, ...$ids];
        }
        $this->pdo->prepare($sql)->execute($params);
    }

    /** @param list<array<string, mixed>> $list */
    private function storeWellness(array $list): void
    {
        $now = Db::ts($this->clock->now());
        $upsert = $this->pdo->prepare('REPLACE INTO ext_wellness (date, data_json, updated_at) VALUES (?, ?, ?)');
        foreach ($list as $w) {
            $date = (string) ($w['id'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            $upsert->execute([$date, json_encode(array_intersect_key($w, array_flip(self::WELLNESS_FIELDS)), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $now]);
        }
    }

    /**
     * Liest aus dem Spiegel; fehlt die Tabelle (Schema veraltet, D-20), gibt es keine Daten statt eines Fehlers.
     *
     * @param list<string> $params
     * @return list<array<string, mixed>>
     */
    private function read(string $sql, array $params): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException $e) {
            $this->error ??= 'Spiegel nicht lesbar (Update erforderlich?)';

            return [];
        }

        return array_map(static fn ($j): array => json_decode((string) $j, true), $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function fresh(string $kind, string $from, string $to): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM ext_cache WHERE cache_key = ? AND fetched_at > ?');
        $stmt->execute([$kind . ':' . $from . ':' . $to, Db::ts($this->clock->now() - self::TTL)]);

        return $stmt->fetchColumn() !== false;
    }

    private function markFresh(string $kind, string $from, string $to): void
    {
        $now = $this->clock->now();
        $this->pdo->prepare('REPLACE INTO ext_cache (cache_key, payload_json, fetched_at) VALUES (?, ?, ?)')
            ->execute([$kind . ':' . $from . ':' . $to, '{}', Db::ts($now)]);
        $this->pdo->prepare('DELETE FROM ext_cache WHERE fetched_at < ?')->execute([Db::ts($now - 86400)]);
    }
}
