<?php

declare(strict_types=1);

namespace Training\Mcp;

use PDO;
use Training\Clock;
use Training\Data\FeedbackRepository;
use Training\Data\WeekRepository;
use Training\Dates;
use Training\Intervals\ActivityLookup;
use Training\Intervals\IntervalsClient;
use Training\Intervals\Mirror;

/**
 * Lese-Tools der MCP-Schnittstelle (Abschnitt 8.2, AP-05). Antworten sind aggregiert und kompakt (8.3):
 * Wochenübersicht ≤ ca. 2 000 Tokens, übrige ≤ ca. 3 000. Skalen sind in den Feldnamen bzw. in `skalen` benannt.
 */
final class ReadTools
{
    public const SCALES = [
        'rpe_cr10' => '0–10 (CR-10), 30 min nach Ende',
        'srpe_load' => 'rpe_cr10 × Dauer in min (berechnet)',
        'feel_1_5' => '1 sehr gut … 5 sehr schlecht',
        'recovery_1_5' => '1 sehr gut erholt … 5 sehr schlecht',
        'soreness_1_5' => '1 kein Muskelkater … 5 stark',
        'intensity_0_10' => 'Schmerz NRS 0–10',
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly string $tz,
        private readonly ?IntervalsClient $intervals,
        private readonly string $profileFile,
    ) {
    }

    /** @return array<string, mixed> */
    public function weekOverview(?string $weekStart): array
    {
        $today = Dates::today($this->clock, $this->tz);
        $monday = Dates::monday(Dates::isDate($weekStart) ? (string) $weekStart : $today);
        $sunday = Dates::addDays($monday, 6);
        $weeks = new WeekRepository($this->pdo, $this->clock);
        $feedback = new FeedbackRepository($this->pdo, $this->clock);
        $week = $weeks->week($monday);
        $sessions = $weeks->sessions($monday, $sunday);
        $errors = [];

        $activities = [];
        $wellness = [];
        if ($monday <= $today) {
            $lookup = new ActivityLookup($this->intervals, $this->pdo, $this->clock);
            $activities = $lookup->activities($monday, min($sunday, $today));
            if ($lookup->error !== null) {
                $errors[] = $lookup->error;
            }
            $mirror = new Mirror($this->intervals, $this->pdo, $this->clock);
            $wellness = $mirror->wellness($monday, min($sunday, $today));
            if ($mirror->error !== null) {
                $errors[] = $mirror->error;
            }
        }

        $out = [];
        $srpeByType = [];
        $planned = 0;
        $done = 0;
        $taken = [];
        $openFeedback = [];
        foreach ($sessions as $s) {
            $row = [
                'id' => (int) $s['id'],
                'datum' => $s['date'],
                'typ' => $s['type'],
                'titel' => $s['title'],
                'prio' => $s['priority'],
                'status' => $s['status'],
                'plan_min' => $s['planned_duration_min'] !== null ? (int) $s['planned_duration_min'] : null,
            ];
            if ($s['type'] === 'ruhe') {
                $out[] = ['id' => $row['id'], 'datum' => $row['datum'], 'typ' => 'ruhe'];
                continue;
            }
            $planned++;
            if (in_array($s['status'], ['erledigt', 'teilweise'], true)) {
                $done++;
            }
            if ($s['execution_id'] !== null) {
                $row['ist_min'] = $s['duration_min'] !== null ? (int) $s['duration_min'] : null;
                $row['rpe_cr10'] = $s['rpe_cr10'] !== null ? (int) $s['rpe_cr10'] : null;
                $row['srpe_load'] = $s['srpe_load'] !== null ? (int) $s['srpe_load'] : null;
                $row['feel_1_5'] = $s['feel_1_5'] !== null ? (int) $s['feel_1_5'] : null;
                $e = $feedback->execution((int) $s['id']);
                if ($e !== null && $e['deviation_reason'] !== null) {
                    $row['abweichung'] = $e['deviation_reason'];
                }
                $srpeByType[$s['type']] = ($srpeByType[$s['type']] ?? 0) + (int) $s['srpe_load'];
            }
            if (in_array($s['status'], ['erledigt', 'teilweise'], true) && $s['rpe_cr10'] === null) {
                $row['feedback_offen'] = true;
                $openFeedback[] = (int) $s['id'];
            }
            if ($s['type'] === 'ausdauer') {
                $row['intervals_event_id'] = $s['intervals_event_id'] !== null ? (int) $s['intervals_event_id'] : null;
                $a = $activities !== [] ? ActivityLookup::match($s, $activities, $taken) : null;
                if ($a !== null) {
                    $taken[] = (string) ($a['id'] ?? '');
                    $row['aktivitaet'] = self::activity($a);
                }
            }
            $out[] = $row;
        }
        $unmatched = [];
        foreach ($activities as $a) {
            if (!in_array((string) ($a['id'] ?? ''), $taken, true)) {
                $unmatched[] = self::activity($a) + ['datum' => substr((string) ($a['start_date_local'] ?? ''), 0, 10)];
            }
        }

        // Schmerz der Woche je Ort
        $pain = [];
        foreach ($feedback->pains($monday, $sunday) as $p) {
            $key = $p['location'] . ($p['side'] !== 'na' ? '_' . $p['side'] : '');
            $pain[$key] ??= ['ort' => $p['location'], 'seite' => $p['side'], 'anzahl' => 0, 'max_0_10' => 0, 'verlauf' => []];
            $pain[$key]['anzahl']++;
            $pain[$key]['max_0_10'] = max($pain[$key]['max_0_10'], (int) $p['intensity_0_10']);
            $pain[$key]['verlauf'][] = substr((string) $p['date'], 5) . ':' . $p['intensity_0_10'] . ':' . $p['timing'];
        }

        // Check-ins
        $checkins = $feedback->checkins($monday, min($sunday, $today));
        $elapsed = $today < $monday ? 0 : ($today > $sunday ? 7 : Dates::weekdayIndex($today) + 1);
        $checkin = ['tage_erfasst' => count($checkins), 'tage_bisher' => $elapsed,
            'abdeckung_pct' => $elapsed > 0 ? (int) round(100 * count($checkins) / $elapsed) : null];
        if ($checkins !== []) {
            $checkin['recovery_1_5_mittel'] = round(array_sum(array_column($checkins, 'recovery_1_5')) / count($checkins), 1);
            $checkin['soreness_1_5_mittel'] = round(array_sum(array_column($checkins, 'soreness_1_5')) / count($checkins), 1);
            $checkin['tage_mit_schmerz'] = count(array_filter($checkins, static fn (array $c): bool => (int) $c['pain_flag'] === 1));
        }

        $result = [
            'woche' => ['start' => $monday, 'ende' => $sunday, 'kw' => Dates::isoWeek($monday)],
            'einheiten' => $out,
            'summen' => [
                'srpe_je_typ' => $srpeByType,
                'srpe_gesamt' => array_sum($srpeByType),
                'einheiten_geplant' => $planned,
                'einheiten_erledigt' => $done,
                'compliance_pct' => $planned > 0 ? (int) round(100 * $done / $planned) : null,
            ],
            'feedback_offen' => $openFeedback,
            'schmerz' => array_values($pain),
            'checkin' => $checkin,
        ];
        if ($week !== null) {
            $result['woche'] += ['block' => $week['block_name'], 'block_woche' => (int) $week['week_no'] . '/' . (int) $week['week_count'],
                'fokus' => $week['focus'], 'status' => $week['status']];
        } else {
            $result['woche']['hinweis'] = 'Kein Wochenplan in der Datenbank.';
        }
        if ($unmatched !== []) {
            $result['aktivitaeten_ohne_plan'] = array_slice($unmatched, 0, 10);
        }
        if ($wellness !== []) {
            $last = end($wellness);
            $result['form'] = array_filter([
                'datum' => $last['id'] ?? null,
                'fitness_ctl' => isset($last['ctl']) ? round((float) $last['ctl'], 1) : null,
                'ermuedung_atl' => isset($last['atl']) ? round((float) $last['atl'], 1) : null,
                'form_tsb' => isset($last['ctl'], $last['atl']) ? round((float) $last['ctl'] - (float) $last['atl'], 1) : null,
            ], static fn ($v): bool => $v !== null);
        }
        if ($errors !== []) {
            $result['fehler_intervals'] = array_values(array_unique($errors));
        }
        $result['skalen'] = self::SCALES;

        return $result;
    }

    /** @return array<string, mixed> */
    public function sessionDetail(int $sessionId): array
    {
        $weeks = new WeekRepository($this->pdo, $this->clock);
        $s = $weeks->session($sessionId);
        if ($s === null) {
            throw new ToolError('Einheit ' . $sessionId . ' nicht gefunden.');
        }
        $feedback = new FeedbackRepository($this->pdo, $this->clock);
        $e = $feedback->execution($sessionId);
        $stmt = $this->pdo->prepare('SELECT date, location, side, intensity_0_10, timing, notes FROM pain_event WHERE session_id = ? ORDER BY date, id');
        $stmt->execute([$sessionId]);
        $result = [
            'id' => (int) $s['id'],
            'datum' => $s['date'],
            'typ' => $s['type'],
            'titel' => $s['title'],
            'prio' => $s['priority'],
            'status' => $s['status'],
            'plan_min' => $s['planned_duration_min'] !== null ? (int) $s['planned_duration_min'] : null,
            'plan_json' => $s['plan'],
            'coach_rationale' => $s['coach_rationale'],
            'intervals_event_id' => $s['intervals_event_id'] !== null ? (int) $s['intervals_event_id'] : null,
            'durchfuehrung' => $e === null ? null : [
                'ist_min' => $e['duration_min'] !== null ? (int) $e['duration_min'] : null,
                'actual_json' => $e['actual'],
                'rpe_cr10' => $e['rpe_cr10'] !== null ? (int) $e['rpe_cr10'] : null,
                'srpe_load' => $e['srpe_load'] !== null ? (int) $e['srpe_load'] : null,
                'feel_1_5' => $e['feel_1_5'] !== null ? (int) $e['feel_1_5'] : null,
                'abweichung' => $e['deviation_reason'],
                'notiz' => $e['notes'],
                'quelle' => $e['source'],
            ],
            'schmerz' => $stmt->fetchAll(),
        ];
        if ($s['type'] === 'ausdauer' && $s['date'] <= Dates::today($this->clock, $this->tz)) {
            $lookup = new ActivityLookup($this->intervals, $this->pdo, $this->clock);
            $a = ActivityLookup::match($s, $lookup->activities((string) $s['date'], (string) $s['date']));
            $result['aktivitaet'] = $a !== null ? self::activity($a) : null;
            if ($lookup->error !== null) {
                $result['fehler_intervals'] = $lookup->error;
            }
        }
        $result['skalen'] = self::SCALES;

        return $result;
    }

    /** @return array<string, mixed> */
    public function painHistory(int $days): array
    {
        $days = max(7, min(180, $days));
        $today = Dates::today($this->clock, $this->tz);
        $from = Dates::addDays($today, -($days - 1));
        $byLocation = [];
        foreach ((new FeedbackRepository($this->pdo, $this->clock))->pains($from, $today) as $p) {
            $key = $p['location'];
            $byLocation[$key] ??= ['ort' => $key, 'ereignisse' => [], 'max_0_10' => 0];
            $byLocation[$key]['ereignisse'][] = [
                'datum' => $p['date'], 'staerke' => (int) $p['intensity_0_10'], 'zeitpunkt' => $p['timing'], 'seite' => $p['side'],
            ] + ($p['session_id'] !== null ? ['einheit' => (int) $p['session_id']] : []);
            $byLocation[$key]['max_0_10'] = max($byLocation[$key]['max_0_10'], (int) $p['intensity_0_10']);
        }
        $last7 = Dates::addDays($today, -6);
        $prev7 = Dates::addDays($today, -13);
        foreach ($byLocation as &$loc) {
            $a = array_column(array_filter($loc['ereignisse'], static fn (array $e): bool => $e['datum'] >= $last7), 'staerke');
            $b = array_column(array_filter($loc['ereignisse'], static fn (array $e): bool => $e['datum'] >= $prev7 && $e['datum'] < $last7), 'staerke');
            $ma = $a === [] ? null : array_sum($a) / count($a);
            $mb = $b === [] ? null : array_sum($b) / count($b);
            $loc['anzahl'] = count($loc['ereignisse']);
            $loc['trend_7_tage'] = match (true) {
                $ma === null && $mb === null => 'keine_meldung_14_tage',
                $mb === null => 'neu',
                $ma === null => 'keine_meldung_7_tage',
                $ma > $mb + 0.5 => 'steigend',
                $ma < $mb - 0.5 => 'fallend',
                default => 'gleich',
            };
            $loc['ereignisse'] = array_slice($loc['ereignisse'], -20);
        }
        unset($loc);
        usort($byLocation, static fn (array $x, array $y): int => $y['anzahl'] <=> $x['anzahl']);

        return ['zeitraum' => ['von' => $from, 'bis' => $today, 'tage' => $days], 'orte' => $byLocation, 'skala' => self::SCALES['intensity_0_10']];
    }

    /** @return array<string, mixed> */
    public function wellnessTrend(int $days): array
    {
        $days = max(7, min(90, $days));
        $today = Dates::today($this->clock, $this->tz);
        $from = Dates::addDays($today, -($days - 1));
        $checkins = (new FeedbackRepository($this->pdo, $this->clock))->checkins($from, $today);
        $wellness = [];
        $error = null;
        $mirror = new Mirror($this->intervals, $this->pdo, $this->clock);
        foreach ($mirror->wellness(min($from, Dates::addDays($today, -27)), $today) as $w) {
            $wellness[(string) ($w['id'] ?? '')] = $w;
        }
        $error = $mirror->error;
        $rows = [];
        for ($d = $from; $d <= $today; $d = Dates::addDays($d, 1)) {
            $w = $wellness[$d] ?? [];
            $c = $checkins[$d] ?? null;
            $row = array_filter([
                'hrv' => isset($w['hrv']) ? round((float) $w['hrv'], 1) : null,
                'ruhepuls' => isset($w['restingHR']) ? (int) $w['restingHR'] : null,
                'schlaf_h' => isset($w['sleepSecs']) ? round($w['sleepSecs'] / 3600, 1) : null,
                'schlaf_score' => isset($w['sleepScore']) ? (int) $w['sleepScore'] : null,
                'recovery_1_5' => $c !== null ? (int) $c['recovery_1_5'] : null,
                'soreness_1_5' => $c !== null ? (int) $c['soreness_1_5'] : null,
                'schmerz' => $c !== null ? (bool) $c['pain_flag'] : null,
            ], static fn ($v): bool => $v !== null);
            $rows[] = ['datum' => $d] + ($row === [] ? ['fehlt' => true] : $row);
        }
        $baseline = static function (string $field, int $n) use ($wellness, $today): ?float {
            $vals = [];
            for ($i = 0; $i < $n; $i++) {
                $w = $wellness[Dates::addDays($today, -$i)] ?? null;
                if ($w !== null && isset($w[$field])) {
                    $vals[] = (float) $w[$field];
                }
            }

            return $vals === [] ? null : round(array_sum($vals) / count($vals), 1);
        };
        $result = [
            'zeitraum' => ['von' => $from, 'bis' => $today],
            'tage' => $rows,
            'baseline' => [
                'hrv_7d' => $baseline('hrv', 7), 'hrv_28d' => $baseline('hrv', 28),
                'ruhepuls_7d' => $baseline('restingHR', 7), 'ruhepuls_28d' => $baseline('restingHR', 28),
            ],
            'checkin_abdeckung_pct' => (int) round(100 * count($checkins) / $days),
            'skalen' => ['recovery_1_5' => self::SCALES['recovery_1_5'], 'soreness_1_5' => self::SCALES['soreness_1_5']],
        ];
        if ($this->intervals === null && $wellness === []) {
            $result['hinweis'] = 'Intervals.icu nicht eingerichtet – nur Check-in-Werte.';
        }
        if ($error !== null) {
            $result['fehler_intervals'] = $error;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function block(?int $blockId): array
    {
        $weeks = new WeekRepository($this->pdo, $this->clock);
        $block = $blockId !== null ? $weeks->block($blockId) : $weeks->blockFor(Dates::today($this->clock, $this->tz));
        if ($block === null && $blockId === null) {
            $block = $this->pdo->query("SELECT * FROM training_block ORDER BY status = 'aktiv' DESC, start_date DESC LIMIT 1")->fetch() ?: null;
        }
        if ($block === null) {
            throw new ToolError($blockId !== null ? 'Block ' . $blockId . ' nicht gefunden.' : 'Noch kein Trainingsblock angelegt (upsert_block).');
        }
        $today = Dates::today($this->clock, $this->tz);
        $weekNo = $today >= $block['start_date'] && $today <= $block['end_date']
            ? intdiv((int) ((strtotime($today) - strtotime((string) $block['start_date'])) / 86400), 7) + 1 : null;

        return [
            'id' => (int) $block['id'],
            'name' => $block['name'],
            'start' => $block['start_date'],
            'ende' => $block['end_date'],
            'status' => $block['status'],
            'aktuelle_woche' => $weekNo,
            'wochen_gesamt' => intdiv((int) ((strtotime((string) $block['end_date']) - strtotime((string) $block['start_date'])) / 86400), 7) + 1,
            'zielevents' => $block['goal_events_json'] !== null ? json_decode((string) $block['goal_events_json'], true) : [],
            'phasen' => $block['phase_notes'],
            'doc_ref' => $block['doc_ref'],
            'wochen' => array_map(static fn (array $w): array => [
                'start' => $w['week_start'], 'status' => $w['status'], 'fokus' => $w['focus'],
                'einheiten' => (int) $w['sessions'], 'erledigt' => (int) $w['done'],
            ], $weeks->weeksOfBlock((int) $block['id'])),
        ];
    }

    /** @return array<string, mixed> */
    public function athleteProfile(): array
    {
        if (!is_file($this->profileFile)) {
            return ['vorhanden' => false, 'hinweis' => 'Athletenprofil noch nicht angelegt (docs/athlet/profil.md, AP-08).'];
        }

        return ['vorhanden' => true, 'quelle' => 'docs/athlet/profil.md', 'stand' => gmdate('Y-m-d', (int) filemtime($this->profileFile)), 'inhalt' => mb_substr((string) file_get_contents($this->profileFile), 0, 12000)];
    }

    /** @param array<string, mixed> $a @return array<string, mixed> */
    public static function activity(array $a): array
    {
        $zones = is_array($a['icu_hr_zone_times'] ?? null) ? array_map(static fn ($s): int => (int) round(((int) $s) / 60), array_slice($a['icu_hr_zone_times'], 0, 5)) : null;

        return array_filter([
            'id' => $a['id'] ?? null,
            'typ' => $a['type'] ?? null,
            'zuordnung' => $a['match'] ?? null,
            'dauer_min' => isset($a['moving_time']) ? (int) round($a['moving_time'] / 60) : null,
            'distanz_km' => isset($a['distance']) ? round($a['distance'] / 1000, 1) : null,
            'hoehenmeter' => isset($a['total_elevation_gain']) ? (int) round((float) $a['total_elevation_gain']) : null,
            'hf_mittel' => isset($a['average_heartrate']) ? (int) round((float) $a['average_heartrate']) : null,
            'hf_zonen_min' => $zones,
            'load' => isset($a['icu_training_load']) ? (int) $a['icu_training_load'] : null,
            'rpe_intervals' => $a['icu_rpe'] ?? null,
            'feel_intervals' => $a['feel'] ?? null,
        ], static fn ($v): bool => $v !== null);
    }
}
