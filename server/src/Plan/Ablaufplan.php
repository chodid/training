<?php

declare(strict_types=1);

namespace Training\Plan;

use Training\View\Labels;
use Training\View\PlanFormat;

/**
 * Ablaufplan der geführten Einheit (AP-14, D-57; docs/konzept/gefuehrte-einheit.md 6.3): leitet aus plan_json
 * deterministisch die Schrittfolge ab – ein Schritt je Übung bzw. Block in Planreihenfolge, Index = Feldindex ist[i]
 * des Formulars. Reine Funktion ohne Datenbank; das Seitenskript bekommt die Schritte als JSON (data-ablauf).
 *
 * Arten: wiederholungen („Satz erledigt“, danach Pausentimer), halten (Timer Arbeit/Pause je Satz),
 * block (ein Timer über die Blockdauer), offen (nur Anzeige und Ist-Felder, „Erledigt“).
 */
final class Ablaufplan
{
    /** Typen mit geführter Einheit (E-13); nicht ausdauer (läuft auf der Uhr) und ruhe */
    public const TYPES = ['kraft', 'haltung', 'mobilitaet', 'klettern'];

    public const WIEDERHOLUNGEN = 'wiederholungen';
    public const HALTEN = 'halten';
    public const BLOCK = 'block';
    public const OFFEN = 'offen';

    /** Einheit lässt sich geführt durchgehen: geeigneter Typ und Plan mit mindestens einer Übung bzw. einem Block. */
    public static function geeignet(string $type, mixed $plan): bool
    {
        return self::schritte($type, $plan) !== null;
    }

    /**
     * @param mixed $plan dekodiertes plan_json
     * @return list<array{index: int, quelle: string, name: string, soll: string, notiz: ?string, art: string,
     *     saetze: int, arbeit_s: ?int, pause_s: ?int, ist_felder: list<string>}>|null null = kein Ablaufplan
     */
    public static function schritte(string $type, mixed $plan): ?array
    {
        if (!in_array($type, self::TYPES, true) || !is_array($plan)) {
            return null;
        }
        $key = $type === 'klettern' ? 'blocks' : 'exercises';
        $items = $plan[$key] ?? null;
        if (!is_array($items) || $items === [] || !array_is_list($items)) {
            return null;
        }
        $steps = [];
        foreach ($items as $i => $item) {
            if (!is_array($item)) {
                return null;
            }
            $steps[] = $key === 'exercises' ? self::exercise($i, $item) : self::block($i, $item);
        }

        return $steps;
    }

    /** Schritte als JSON für das Attribut data-ablauf (Seitenskript js/gefuehrt.js). @param list<array<string, mixed>> $steps */
    public static function json(array $steps): string
    {
        return json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Haltezeit in Sekunden aus einer Wiederholungsangabe („45s“, „45 sek.“, „2 min“, „30-45 s“ → obere Grenze),
     * sonst null (z. B. „8“, „6-8“, „max“).
     */
    public static function holdSeconds(string $reps): ?int
    {
        $unit = '(?:s|sek|sec)\.?';
        if (preg_match('/^\s*(\d+)\s*' . $unit . '\s*$/iu', $reps, $m)) {
            $s = (int) $m[1];
        } elseif (preg_match('/^\s*(\d+)\s*min\.?\s*$/iu', $reps, $m)) {
            $s = (int) $m[1] * 60;
        } elseif (preg_match('/^\s*(\d+)\s*[-–]\s*(\d+)\s*' . $unit . '\s*$/iu', $reps, $m)) {
            $s = max((int) $m[1], (int) $m[2]);
        } else {
            return null;
        }

        return $s > 0 ? $s : null;
    }

    /** @param array<string, mixed> $x @return array<string, mixed> */
    private static function exercise(int $i, array $x): array
    {
        $hold = self::holdSeconds((string) ($x['reps'] ?? ''));

        return [
            'index' => $i,
            'quelle' => 'exercises[' . $i . ']',
            'name' => (string) ($x['name'] ?? ''),
            'soll' => PlanFormat::exercise($x),
            'notiz' => self::note($x),
            'art' => $hold !== null ? self::HALTEN : self::WIEDERHOLUNGEN,
            'saetze' => max(1, (int) ($x['sets'] ?? 1)),
            'arbeit_s' => $hold,
            'pause_s' => self::seconds($x['rest_s'] ?? null),
            'ist_felder' => ['sets', 'reps', 'load'],
        ];
    }

    /** @param array<string, mixed> $b @return array<string, mixed> */
    private static function block(int $i, array $b): array
    {
        $hang = self::seconds($b['hang_s'] ?? null);
        $minutes = self::seconds($b['duration_min'] ?? null);
        if ($hang !== null) {
            [$art, $sets, $work, $rest] = [self::HALTEN, max(1, (int) ($b['sets'] ?? 1)), $hang, self::seconds($b['rest_s'] ?? null)];
        } elseif ($minutes !== null) {
            [$art, $sets, $work, $rest] = [self::BLOCK, 1, $minutes * 60, null];
        } else {
            [$art, $sets, $work, $rest] = [self::OFFEN, 1, null, null];
        }
        $kind = (string) ($b['kind'] ?? '');

        return [
            'index' => $i,
            'quelle' => 'blocks[' . $i . ']',
            'name' => Labels::BLOCK_KINDS[$kind] ?? $kind,
            'soll' => PlanFormat::block($b),
            'notiz' => self::note($b),
            'art' => $art,
            'saetze' => $sets,
            'arbeit_s' => $work,
            'pause_s' => $rest,
            'ist_felder' => isset($b['sets']) ? ['duration_min', 'sets', 'notes'] : ['duration_min', 'notes'],
        ];
    }

    /** Positive ganze Zahl, sonst null (0 = keine Pause bzw. kein Timer). */
    private static function seconds(mixed $v): ?int
    {
        return is_int($v) && $v > 0 ? $v : null;
    }

    /** @param array<string, mixed> $item */
    private static function note(array $item): ?string
    {
        $n = trim((string) ($item['notes'] ?? ''));

        return $n === '' ? null : $n;
    }
}
