<?php

declare(strict_types=1);

namespace Training\Review;

use PDO;
use Training\Clock;
use Training\Data\ReviewRepository;
use Training\Data\SettingsRepository;
use Training\Dates;

/**
 * Fälligkeit von Blockbilanz, Zielklärung und Revision (AP-15, docs/konzept/blockbilanz.md 5.1, E-03/E-13).
 * compute() ist eine reine Funktion aus Blöcken, gültigen bestätigten Reviews, Vorlauftagen und heute; dieselbe
 * Liste nutzen get_handover, get_block, write_week_plan, das Overlay, die Karte in S2, die Blockseite und der Kalender.
 *
 * Regeln:
 * - bilanz/blockende: aktiver Block ab end_date − Vorlauf Bilanz ohne bestätigte Bilanz (seit = end_date − Vorlauf).
 * - bilanz/block_abgeschlossen_ohne_bilanz: der zuletzt beendete abgeschlossene Block ohne bestätigte Bilanz
 *   (nur dieser – ältere Blöcke vor dem Vorgänger melden nichts mehr).
 * - zielklaerung/block_ohne_zielklaerung: aktiver Block ohne bestätigte Zielklärung; ohne aktiven Block, wenn auch kein
 *   geplanter Block mit bestätigter Zielklärung existiert (block_id des geplanten Blocks bzw. null).
 * - zielklaerung/folgeblock_ohne_zielklaerung: ab end_date − Vorlauf Zielklärung des aktiven Blocks, solange kein
 *   anderer Block (geplant/aktiv, start_date > heute − 7) eine bestätigte Zielklärung hat. block_id = aktiver Block.
 * - zielklaerung/zielklaerung_aelter_16_wochen: jüngste bestätigte Zielklärung (alle Blöcke) ab 112 Tagen alt.
 *   Je Aufruf höchstens eine Zielklärung (Vorrang in dieser Reihenfolge).
 * - revision/revision_turnus: im aktiven Block ab 28 Tagen seit dem jüngsten bestätigten Datensatz gleich welcher
 *   Art (ohne Datensatz: seit Blockbeginn).
 */
final class Faelligkeit
{
    public const ZIELKLAERUNG_MAX_TAGE = 112;
    public const REVISION_TAGE = 28;

    /**
     * @param list<array<string, mixed>> $blocks Zeilen aus training_block (id, name, start_date, end_date, status)
     * @param list<array{block_id: int, kind: string, sequence?: int, review_date: string}> $confirmed gültige bestätigte Reviews
     * @return list<array{kind: string, block_id: ?int, block_name: ?string, grund: string, seit: string, faellig_am: string, folgeblock_id?: int}>
     */
    public static function compute(array $blocks, array $confirmed, string $today, int $bilanzVorlauf = 7, int $zielklaerungVorlauf = 14): array
    {
        $byBlock = [];
        foreach ($confirmed as $r) {
            $byBlock[(int) $r['block_id']][$r['kind']][] = (string) $r['review_date'];
        }
        $has = static fn (array $b, string $kind): bool => isset($byBlock[(int) $b['id']][$kind]);
        $active = null;
        $closed = null;
        foreach ($blocks as $b) {
            if ($b['status'] === 'aktiv') {
                $active = $b;
            } elseif ($b['status'] === 'abgeschlossen' && ($closed === null || [(string) $b['end_date'], (int) $b['id']] > [(string) $closed['end_date'], (int) $closed['id']])) {
                $closed = $b;
            }
        }
        $entry = static fn (string $kind, ?array $b, string $grund, string $seit, string $faelligAm): array
            => ['kind' => $kind, 'block_id' => $b !== null ? (int) $b['id'] : null, 'block_name' => $b !== null ? (string) $b['name'] : null,
                'grund' => $grund, 'seit' => $seit, 'faellig_am' => $faelligAm];
        $out = [];

        // Bilanz
        if ($active !== null && !$has($active, 'bilanz')) {
            $from = Dates::addDays((string) $active['end_date'], -$bilanzVorlauf);
            if ($today >= $from) {
                $out[] = $entry('bilanz', $active, 'blockende', $from, (string) $active['end_date']);
            }
        }
        if ($closed !== null && !$has($closed, 'bilanz') && ($active === null || (string) $closed['start_date'] < (string) $active['start_date'])) {
            $end = min((string) $closed['end_date'], $today);
            $out[] = $entry('bilanz', $closed, 'block_abgeschlossen_ohne_bilanz', $end, $end);
        }

        // Zielklärung
        $zk = null;
        if ($active === null) {
            $planned = array_values(array_filter($blocks, static fn (array $b): bool => $b['status'] === 'geplant'));
            usort($planned, static fn (array $a, array $b): int => [(string) $a['start_date'], (int) $a['id']] <=> [(string) $b['start_date'], (int) $b['id']]);
            $withZk = array_filter($planned, static fn (array $b): bool => $has($b, 'zielklaerung'));
            if ($withZk === []) {
                $seit = $closed !== null ? min(Dates::addDays((string) $closed['end_date'], 1), $today) : $today;
                $zk = $entry('zielklaerung', $planned[0] ?? null, 'block_ohne_zielklaerung', $seit, $planned !== [] ? max((string) $planned[0]['start_date'], $seit) : $seit);
            }
        } elseif (!$has($active, 'zielklaerung')) {
            $zk = $entry('zielklaerung', $active, 'block_ohne_zielklaerung', min((string) $active['start_date'], $today), (string) $active['start_date']);
        } else {
            $from = Dates::addDays((string) $active['end_date'], -$zielklaerungVorlauf);
            $minStart = Dates::addDays($today, -7);
            $next = null;
            $nextDone = false;
            foreach ($blocks as $b) {
                if ((int) $b['id'] === (int) $active['id'] || !in_array($b['status'], ['geplant', 'aktiv'], true) || (string) $b['start_date'] <= $minStart) {
                    continue;
                }
                if ($has($b, 'zielklaerung')) {
                    $nextDone = true;
                } elseif ($next === null || (string) $b['start_date'] < (string) $next['start_date']) {
                    $next = $b;
                }
            }
            if ($today >= $from && !$nextDone) {
                $zk = $entry('zielklaerung', $active, 'folgeblock_ohne_zielklaerung', $from, (string) $active['end_date']) + ($next !== null ? ['folgeblock_id' => (int) $next['id']] : []);
            }
            $latest = null;
            foreach ($byBlock as $kinds) {
                foreach ($kinds['zielklaerung'] ?? [] as $d) {
                    $latest = $latest === null ? $d : max($latest, $d);
                }
            }
            if ($zk === null && $latest !== null) {
                $limit = Dates::addDays($latest, self::ZIELKLAERUNG_MAX_TAGE);
                if ($today >= $limit) {
                    $zk = $entry('zielklaerung', $active, 'zielklaerung_aelter_16_wochen', $limit, $limit);
                }
            }
        }
        if ($zk !== null) {
            $out[] = $zk;
        }

        // Revision (E-07: nur Hinweis in get_handover und S2)
        if ($active !== null) {
            $ref = (string) $active['start_date'];
            foreach ($byBlock[(int) $active['id']] ?? [] as $dates) {
                $ref = max($ref, ...$dates);
            }
            $due = Dates::addDays($ref, self::REVISION_TAGE);
            if ($today >= $due) {
                $out[] = $entry('revision', $active, 'revision_turnus', $due, $due);
            }
        }

        return $out;
    }

    /**
     * Fälligkeiten aus der Datenbank (Blöcke, Reviews, Vorlauftage aus app_setting).
     * @return list<array<string, mixed>>
     */
    public static function load(PDO $pdo, Clock $clock, string $today): array
    {
        try {
            $blocks = $pdo->query('SELECT id, name, start_date, end_date, status FROM training_block ORDER BY start_date, id')->fetchAll();
        } catch (\PDOException) {
            return [];
        }
        $settings = new SettingsRepository($pdo, $clock);

        return self::compute($blocks, (new ReviewRepository($pdo, $clock))->confirmedKeys(), $today, $settings->bilanzVorlauf(), $settings->zielklaerungVorlauf());
    }

    /**
     * Nur die Einträge eines Blocks (inklusive Zielklärung ohne Block, wenn $blockId null ist).
     * @param list<array<string, mixed>> $faellig
     * @return list<array<string, mixed>>
     */
    public static function forBlock(array $faellig, ?int $blockId): array
    {
        return array_values(array_filter($faellig, static fn (array $f): bool => $f['block_id'] === $blockId || ($f['folgeblock_id'] ?? null) === $blockId));
    }

    /** Ein Satz je Fälligkeit für Webseite und Kalender (z. B. „Block ‚Herbst‘ endet am 12.10.“). @param array<string, mixed> $f */
    public static function text(array $f): string
    {
        $name = $f['block_name'] !== null ? '„' . $f['block_name'] . '“' : null;
        $date = (new \DateTimeImmutable((string) $f['faellig_am']))->format('d.m.');

        return match ($f['grund']) {
            'blockende' => 'Block ' . $name . ' endet am ' . $date,
            'block_abgeschlossen_ohne_bilanz' => 'Block ' . $name . ' ist abgeschlossen, die Bilanz fehlt noch.',
            'folgeblock_ohne_zielklaerung' => 'Block ' . $name . ' endet am ' . $date . ' – Ziele für den nächsten Block klären.',
            'block_ohne_zielklaerung' => $name !== null ? 'Block ' . $name . ' hat noch keine bestätigte Zielklärung.' : 'Kein aktiver Block – Ziele für den nächsten Block klären.',
            'zielklaerung_aelter_16_wochen' => 'Die letzte Zielklärung ist älter als 16 Wochen.',
            'revision_turnus' => 'Seit 4 Wochen keine Revision in Block ' . $name . '.',
            default => (string) $f['grund'],
        };
    }
}
