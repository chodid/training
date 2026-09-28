<?php

declare(strict_types=1);

namespace Training\View;

/** Soll-Angaben einer Übung bzw. eines Kletterblocks als Text (S3 Einheit, S9 geführte Einheit). */
final class PlanFormat
{
    /** @param array<string, mixed> $x Übung aus plan_json.exercises */
    public static function exercise(array $x): string
    {
        $parts = [$x['sets'] . ' × ' . $x['reps']];
        foreach (['load' => '%s', 'tempo' => 'Tempo %s', 'rest_s' => 'Pause %s s'] as $k => $f) {
            if (isset($x[$k]) && $x[$k] !== '') {
                $parts[] = sprintf($f, $x[$k]);
            }
        }

        return 'Soll ' . implode(' · ', $parts);
    }

    /** @param array<string, mixed> $b Block aus plan_json.blocks */
    public static function block(array $b): string
    {
        $parts = [];
        if (isset($b['duration_min'])) {
            $parts[] = $b['duration_min'] . ' min';
        }
        if (isset($b['target'])) {
            $parts[] = $b['target'];
        }
        if ($b['kind'] === 'hangboard' || isset($b['edge_mm'])) {
            $h = [];
            if (isset($b['edge_mm'])) {
                $h[] = $b['edge_mm'] . ' mm';
            }
            if (isset($b['grip'])) {
                $h[] = Labels::GRIPS[$b['grip']] ?? $b['grip'];
            }
            if (isset($b['hang_s'])) {
                $h[] = $b['hang_s'] . ' s';
            }
            if (isset($b['sets'])) {
                $h[] = $b['sets'] . ' Sätze';
            }
            if (isset($b['added_load_kg'])) {
                $h[] = ($b['added_load_kg'] >= 0 ? '+' : '') . str_replace('.', ',', (string) $b['added_load_kg']) . ' kg';
            }
            if (isset($b['rest_s'])) {
                $h[] = 'Pause ' . $b['rest_s'] . ' s';
            }
            if ($h !== []) {
                $parts[] = implode(', ', $h);
            }
        } elseif (isset($b['sets'])) {
            $parts[] = $b['sets'] . ' Sätze';
        }

        return 'Soll ' . ($parts === [] ? '–' : implode(' · ', $parts));
    }
}
