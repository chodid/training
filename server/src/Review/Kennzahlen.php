<?php

declare(strict_types=1);

namespace Training\Review;

use PDO;
use Training\Checkin\MorningChecks;
use Training\Clock;
use Training\Dates;

/**
 * Kennzahlen eines Zeitraums für Bilanz und Revision (AP-15, docs/konzept/blockbilanz.md 4.4, E-12). Der Server
 * friert sie beim Schreiben im Datensatz ein (kennzahlen_auto); die KI liefert keine Kennzahlen. Quellen: Einheiten
 * mit Durchführung, Schmerzereignisse, Check-ins (Morgentest), Spiegel der Intervals.icu-Aktivitäten und -Wellness
 * (D-43, nur der Spiegel – keine Live-Abfrage). Fehlende Quellen liefern null, nie einen Fehler.
 * Wochen sind Kalenderwochen (Montag) ab der Woche von $from; Tage nach heute zählen für die Abdeckung nicht.
 */
final class Kennzahlen
{
    public const TYPES = ['ausdauer', 'kraft', 'klettern', 'haltung', 'mobilitaet'];
    private const STATUSES = ['erledigt', 'teilweise', 'ausgelassen', 'verschoben'];
    private const MAX_WEEKS = 16;

    public function __construct(private readonly PDO $pdo, private readonly Clock $clock, private readonly string $tz)
    {
    }

    /** @return array<string, mixed> Format 4.4 */
    public function compute(string $from, string $to): array
    {
        $today = Dates::today($this->clock, $this->tz);
        $mondays = [];
        for ($m = Dates::monday($from); $m <= $to; $m = Dates::addDays($m, 7)) {
            $mondays[] = $m;
        }

        // Planerfüllung und Last (sRPE) aus Einheiten und Durchführung
        $plan = array_fill_keys(self::TYPES, null);
        $srpeType = array_fill_keys(self::TYPES, 0);
        $srpeWeek = array_fill_keys($mondays, 0);
        $stmt = $this->pdo->prepare("SELECT s.date, s.type, s.status, e.srpe_load FROM `session` s LEFT JOIN session_execution e ON e.session_id = s.id
            WHERE s.date BETWEEN ? AND ? AND s.type <> 'ruhe'");
        $stmt->execute([$from, $to]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $type = (string) $s['type'];
            $plan[$type] ??= ['geplant' => 0, 'erledigt' => 0, 'teilweise' => 0, 'ausgelassen' => 0, 'verschoben' => 0];
            $plan[$type]['geplant']++;
            if (in_array($s['status'], self::STATUSES, true)) {
                $plan[$type][$s['status']]++;
            }
            $load = (int) ($s['srpe_load'] ?? 0);
            $srpeType[$type] = ($srpeType[$type] ?? 0) + $load;
            $srpeWeek[Dates::monday((string) $s['date'])] = ($srpeWeek[Dates::monday((string) $s['date'])] ?? 0) + $load;
        }
        foreach ($plan as $type => $p) {
            $plan[$type] = $p ?? ['geplant' => 0, 'erledigt' => 0, 'teilweise' => 0, 'ausgelassen' => 0, 'verschoben' => 0];
        }

        $elapsedTo = min($to, $today);
        $days = $elapsedTo >= $from ? (int) ((strtotime($elapsedTo) - strtotime($from)) / 86400) + 1 : 0;

        return [
            'zeitraum' => ['von' => $from, 'bis' => $to],
            'wochen' => count($mondays),
            'plan_erfuellung' => $plan,
            'last' => [
                'srpe_summe' => array_sum($srpeType),
                'srpe_je_woche' => array_slice(array_values($srpeWeek), -self::MAX_WEEKS),
                'srpe_je_typ' => $srpeType,
            ],
            'ausdauer' => $this->ausdauer($from, $to, $mondays),
            'schmerz' => ['je_ort' => $this->schmerz($from, $to)],
            'morgentest' => $days > 0 ? $this->morgentest($from, $elapsedTo) : ['links_mittel' => null, 'rechts_mittel' => null, 'rot_tage' => 0],
            'checkin_abdeckung_prozent' => $days > 0 ? (int) round(100 * $this->count('SELECT COUNT(*) FROM checkin WHERE date BETWEEN ? AND ?', [$from, $elapsedTo]) / $days) : 0,
            'wellness' => $this->wellness($from, $to),
            'berechnet_am' => gmdate('Y-m-d\TH:i:s\Z', $this->clock->now()),
        ];
    }

    /**
     * Kurzform für get_handover (5.2): die letzten 4 Wochen bis heute im Vergleich zum Wochenmittel des Blocks.
     * @param array<string, mixed> $block Zeile aus training_block
     * @return array<string, mixed>
     */
    public function kurz(array $block): array
    {
        $today = Dates::today($this->clock, $this->tz);
        $end = min((string) $block['end_date'], $today);
        $start = (string) $block['start_date'];
        if ($end < $start) {
            return ['hinweis' => 'Block hat noch nicht begonnen.'];
        }
        $recentFrom = max($start, Dates::addDays(Dates::monday($end), -21));
        $recent = $this->compute($recentFrom, $end);
        $all = $this->compute($start, $end);
        $mean = static fn (array $k): ?float => $k['wochen'] > 0 ? round($k['last']['srpe_summe'] / $k['wochen']) : null;
        $done = static function (array $k): array {
            $out = [];
            foreach ($k['plan_erfuellung'] as $type => $p) {
                if ($p['geplant'] > 0) {
                    $out[$type] = ($p['erledigt'] + $p['teilweise']) . '/' . $p['geplant'];
                }
            }

            return $out;
        };

        return array_filter([
            'zeitraum' => ['von' => $recentFrom, 'bis' => $end],
            'srpe_je_woche' => $recent['last']['srpe_je_woche'],
            'srpe_block_mittel_je_woche' => $mean($all),
            'srpe_je_typ' => array_filter($recent['last']['srpe_je_typ']),
            'erledigt_von_geplant' => $done($recent),
            'km_je_woche' => $recent['ausdauer']['km_je_woche'] ?? null,
            'km_block_mittel_je_woche' => isset($all['ausdauer']['km_je_woche']) ? round(array_sum($all['ausdauer']['km_je_woche']) / max(1, count($all['ausdauer']['km_je_woche'])), 1) : null,
            'schmerz' => array_map(static fn (array $p): string => $p['ort'] . ' max ' . $p['max'] . ', ' . $p['anzahl'] . '×, ' . $p['trend'], $recent['schmerz']['je_ort']),
            'morgentest' => $recent['morgentest'],
            'checkin_abdeckung_prozent' => $recent['checkin_abdeckung_prozent'],
            'wellness' => $recent['wellness'],
        ], static fn ($v): bool => $v !== null && $v !== []);
    }

    /**
     * @param list<string> $mondays
     * @return array<string, mixed>|null
     */
    private function ausdauer(string $from, string $to, array $mondays): ?array
    {
        $rows = $this->rows('SELECT date, data_json FROM ext_activity WHERE date BETWEEN ? AND ? ORDER BY date', [$from, $to]);
        if ($rows === []) {
            return null;
        }
        $km = array_fill_keys($mondays, 0.0);
        $hm = array_fill_keys($mondays, 0);
        $zones = [0, 0, 0, 0, 0];
        $withZones = false;
        foreach ($rows as $r) {
            $a = json_decode((string) $r['data_json'], true);
            $a = is_array($a) ? $a : [];
            $m = Dates::monday((string) $r['date']);
            $km[$m] = ($km[$m] ?? 0.0) + (float) ($a['distance'] ?? 0) / 1000;
            $hm[$m] = ($hm[$m] ?? 0) + (int) round((float) ($a['total_elevation_gain'] ?? 0));
            if (is_array($a['icu_hr_zone_times'] ?? null)) {
                $withZones = true;
                foreach (array_slice(array_values($a['icu_hr_zone_times']), 0, 5) as $i => $sec) {
                    $zones[$i] += (int) $sec;
                }
            }
        }

        return [
            'km_je_woche' => array_slice(array_map(static fn (float $v): float => round($v, 1), array_values($km)), -self::MAX_WEEKS),
            'hm_je_woche' => array_slice(array_values($hm), -self::MAX_WEEKS),
            'zeit_zone_min' => $withZones ? ['z1' => intdiv($zones[0], 60), 'z2' => intdiv($zones[1], 60), 'z3' => intdiv($zones[2], 60), 'z4' => intdiv($zones[3], 60), 'z5' => intdiv($zones[4], 60)] : null,
        ];
    }

    /**
     * Schmerz je Ort: Maximum, Mittel, Anzahl; Trend = Mittel der zweiten gegenüber der ersten Hälfte des Zeitraums
     * (± 0,5; nur in der zweiten Hälfte = steigend, nur in der ersten = fallend).
     * @return list<array<string, mixed>>
     */
    private function schmerz(string $from, string $to): array
    {
        $mid = Dates::addDays($from, intdiv((int) ((strtotime($to) - strtotime($from)) / 86400) + 1, 2));
        $by = [];
        foreach ($this->rows('SELECT date, location, intensity_0_10 FROM pain_event WHERE date BETWEEN ? AND ? ORDER BY date, id', [$from, $to]) as $p) {
            $by[(string) $p['location']][(string) $p['date'] < $mid ? 0 : 1][] = (int) $p['intensity_0_10'];
        }
        $out = [];
        foreach ($by as $ort => $halves) {
            $all = [...($halves[0] ?? []), ...($halves[1] ?? [])];
            $a = isset($halves[0]) ? array_sum($halves[0]) / count($halves[0]) : null;
            $b = isset($halves[1]) ? array_sum($halves[1]) / count($halves[1]) : null;
            $out[] = [
                'ort' => $ort,
                'max' => max($all),
                'mittel' => round(array_sum($all) / count($all), 1),
                'anzahl' => count($all),
                'trend' => match (true) {
                    $a === null => 'steigend',
                    $b === null => 'fallend',
                    $b > $a + 0.5 => 'steigend',
                    $b < $a - 0.5 => 'fallend',
                    default => 'gleich',
                },
            ];
        }
        usort($out, static fn (array $x, array $y): int => [$y['anzahl'], $x['ort']] <=> [$x['anzahl'], $y['ort']]);

        return $out;
    }

    /** @return array{links_mittel: ?float, rechts_mittel: ?float, rot_tage: int} Morgentest (D-53) */
    private function morgentest(string $from, string $to): array
    {
        $left = [];
        $right = [];
        $red = 0;
        foreach ((new MorningChecks($this->pdo, $this->clock, $this->tz))->range($from, $to) as $d) {
            if ($d['morgentest']['links'] !== null) {
                $left[] = $d['morgentest']['links'];
            }
            if ($d['morgentest']['rechts'] !== null) {
                $right[] = $d['morgentest']['rechts'];
            }
            $red += $d['ampel'] === 'rot' ? 1 : 0;
        }

        return [
            'links_mittel' => $left === [] ? null : round(array_sum($left) / count($left), 1),
            'rechts_mittel' => $right === [] ? null : round(array_sum($right) / count($right), 1),
            'rot_tage' => $red,
        ];
    }

    /** @return array{hrv_mittel: ?float, ruhepuls_mittel: ?float, schlaf_h_mittel: ?float}|null */
    private function wellness(string $from, string $to): ?array
    {
        $rows = $this->rows('SELECT data_json FROM ext_wellness WHERE date BETWEEN ? AND ?', [$from, $to]);
        if ($rows === []) {
            return null;
        }
        $vals = ['hrv' => [], 'restingHR' => [], 'sleepSecs' => []];
        foreach ($rows as $r) {
            $w = json_decode((string) $r['data_json'], true);
            foreach (array_keys($vals) as $k) {
                if (is_array($w) && is_numeric($w[$k] ?? null)) {
                    $vals[$k][] = (float) $w[$k];
                }
            }
        }
        $mean = static fn (array $v, int $digits = 1): ?float => $v === [] ? null : round(array_sum($v) / count($v), $digits);

        return [
            'hrv_mittel' => $mean($vals['hrv']),
            'ruhepuls_mittel' => $mean($vals['restingHR']),
            'schlaf_h_mittel' => $vals['sleepSecs'] === [] ? null : round(array_sum($vals['sleepSecs']) / count($vals['sleepSecs']) / 3600, 1),
        ];
    }

    /** @param list<string> $params @return list<array<string, mixed>> */
    private function rows(string $sql, array $params): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            return []; // Tabelle fehlt (Schema veraltet): Quelle fehlt → null
        }
    }

    /** @param list<string> $params */
    private function count(string $sql, array $params): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return (int) $stmt->fetchColumn();
        } catch (\PDOException) {
            return 0;
        }
    }
}
