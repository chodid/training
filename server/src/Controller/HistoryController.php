<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Dates;
use Training\Http\Request;
use Training\Http\Response;
use Training\View\Labels;

/**
 * S6 Verlauf (Abschnitt 10, AP-09): sRPE-Wochenlast je Bereich über 8 Wochen als kleine Vielfache mit gemeinsamer Achse,
 * Schmerz je Ort als Raster (stärkste Meldung der Woche), Kennzahlen und Tabelle (Branding Abschnitt 5); Abschnitt
 * „Blöcke“ mit Link auf die Blockseite S11 (AP-15).
 */
final class HistoryController extends AppController
{
    public const WEEKS = 8;
    /** Bereiche der kleinen Vielfachen: Titel, Icon, Einheitentypen */
    public const AREAS = [
        'ausdauer' => ['Ausdauer', 'run', ['ausdauer']],
        'klettern' => ['Klettern', 'mountain', ['klettern']],
        'kraft' => ['Kraft', 'barbell', ['kraft']],
        'haltung' => ['Haltung, Mobilität', 'yoga', ['haltung', 'mobilitaet']],
    ];

    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $today = $this->today();
        $current = Dates::monday($today);
        $weeks = [];
        for ($i = self::WEEKS - 1; $i >= 0; $i--) {
            $monday = Dates::addDays($current, -7 * $i);
            $weeks[$monday] = ['kw' => Dates::isoWeek($monday), 'load' => array_fill_keys(array_keys(self::AREAS), 0), 'checkins' => 0,
                'days' => $monday === $current ? Dates::weekdayIndex($today) + 1 : 7, 'pains' => 0];
        }
        $from = array_key_first($weeks);
        $to = min(Dates::addDays($current, 6), $today);

        $stmt = $this->app->pdo()->prepare('SELECT s.date, s.type, e.srpe_load FROM `session` s JOIN session_execution e ON e.session_id = s.id WHERE s.date BETWEEN ? AND ? AND e.srpe_load IS NOT NULL');
        $stmt->execute([$from, $to]);
        foreach ($stmt->fetchAll() as $row) {
            foreach (self::AREAS as $key => [, , $types]) {
                if (in_array($row['type'], $types, true)) {
                    $weeks[Dates::monday((string) $row['date'])]['load'][$key] += (int) $row['srpe_load'];
                }
            }
        }
        foreach ($this->feedback()->checkins($from, $to) as $date => $c) {
            $weeks[Dates::monday($date)]['checkins']++;
        }

        // Schmerz: Zeile je Ort und Seite, Zelle = stärkste Meldung der Woche
        $heat = [];
        $painTotal = 0;
        foreach ($this->feedback()->pains($from, $to) as $p) {
            $painTotal++;
            $weeks[Dates::monday((string) $p['date'])]['pains']++;
            $label = trim(Labels::LOCATIONS[$p['location']] . ' ' . Labels::SIDES_SHORT[$p['side']]);
            $heat[$label] ??= array_fill_keys(array_keys($weeks), null);
            $monday = Dates::monday((string) $p['date']);
            $cell = $heat[$label][$monday];
            if ($cell === null || (int) $p['intensity_0_10'] > $cell['i']) {
                $heat[$label][$monday] = ['i' => (int) $p['intensity_0_10'], 't' => 'KW ' . $weeks[$monday]['kw'] . ': ' . $p['intensity_0_10'] . ', ' . mb_strtolower(Labels::TIMINGS[$p['timing']])];
            }
        }
        ksort($heat);

        $max = 0;
        foreach ($weeks as $w) {
            $max = max($max, ...array_values($w['load']));
        }
        $axis = max(100, (int) (ceil($max / 100) * 100));
        $checkinDays = array_sum(array_column($weeks, 'days'));
        $prev = $weeks[Dates::addDays($current, -7)] ?? null;

        return $this->page('history', 'Verlauf', 'verlauf', [
            'wide' => true,
            'weeks' => $weeks,
            'current' => $current,
            'axis' => $axis,
            'heat' => $heat,
            'tiles' => [
                'srpe_now' => array_sum($weeks[$current]['load']),
                'srpe_prev' => $prev !== null ? array_sum($prev['load']) : 0,
                'checkin_pct' => $checkinDays > 0 ? (int) round(100 * array_sum(array_column($weeks, 'checkins')) / $checkinDays) : 0,
                'pains' => $painTotal,
            ],
            'weekday' => Dates::WEEKDAYS_SHORT[Dates::weekdayIndex($today)],
            'blocks' => $this->blocks($today),
        ]);
    }

    /**
     * Abschnitt „Blöcke“ (AP-15, E-18): alle Blöcke, neueste zuerst, mit Anzahl der Fälligkeiten je Block.
     * @return list<array<string, mixed>>
     */
    private function blocks(string $today): array
    {
        try {
            $faellig = \Training\Review\Faelligkeit::load($this->app->pdo(), $this->app->clock(), $today);
            $blocks = $this->app->pdo()->query('SELECT id, name, start_date, end_date, status FROM training_block ORDER BY start_date DESC, id DESC')->fetchAll();
        } catch (\PDOException) {
            return [];
        }

        return array_map(static fn (array $b): array => $b + ['faellig' => array_map(static fn (array $f): string => Labels::REVIEW_KINDS[$f['kind']], \Training\Review\Faelligkeit::forBlock($faellig, (int) $b['id']))], $blocks);
    }
}
