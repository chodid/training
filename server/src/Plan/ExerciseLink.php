<?php

declare(strict_types=1);

namespace Training\Plan;

use Training\Data\ExerciseRepository;
use Training\Exercise\Catalog;

/**
 * Verknüpfung plan_json → Übungskatalog (AP-16, docs/konzept/uebungskatalog.md 5.2). Läuft nach der Schemaprüfung
 * (PlanValidator) und vor dem Schreiben; löst alle exercise_id eines Aufrufs mit einem Datenbankabruf auf.
 *
 * Fehler (nichts wird geschrieben): unbekannt, archiviert, kind_ohne_katalog (E-05).
 * Warnungen (Schreiben erlaubt, E-03/E-12): ohne_katalog (Übung ohne exercise_id), name_abweichend (name weder Name
 * noch Alias der Übung). Position = Index in exercises[] bzw. blocks[] (ab 0, wie in den Schemafehlern).
 */
final class ExerciseLink
{
    /** Einheitentypen mit exercises[] (Schema kraft_oder_haltung) */
    private const EXERCISE_TYPES = ['kraft', 'haltung', 'mobilitaet'];

    public function __construct(private readonly ExerciseRepository $exercises)
    {
    }

    /**
     * @param list<array{session: int|string, type: string, plan: mixed}> $sessions session = Kennung für die Meldung
     *     (Index im Wochenplan oder Einheiten-ID)
     * @return array{fehler: list<array<string, mixed>>, warnungen: list<array<string, mixed>>}
     */
    public function check(array $sessions): array
    {
        $slugs = [];
        foreach ($sessions as $s) {
            $slugs = [...$slugs, ...ExerciseRepository::slugsInPlan($s['plan'])];
        }
        $known = $this->exercises->lookup($slugs);

        $errors = [];
        $warnings = [];
        foreach ($sessions as $s) {
            foreach ($this->items($s['type'], $s['plan']) as [$position, $item, $isBlock]) {
                $slug = $item['exercise_id'] ?? null;
                $label = $isBlock ? (string) ($item['kind'] ?? '') : (string) ($item['name'] ?? '');
                $where = ['session' => $s['session'], 'position' => $position];
                if ($isBlock && !in_array($item['kind'] ?? null, Catalog::BLOCK_KINDS, true)) {
                    if ($slug !== null) {
                        $errors[] = $where + ['exercise_id' => $slug, 'code' => 'kind_ohne_katalog',
                            'hinweis' => 'Kletterblock „' . $label . '“ ist ein Einheitenformat ohne Katalogeintrag; exercise_id nur bei ' . implode(', ', Catalog::BLOCK_KINDS) . '.'];
                    }
                    continue;
                }
                if ($slug === null) {
                    $warnings[] = $where + ['name' => $label, 'code' => 'ohne_katalog',
                        'hinweis' => 'Ohne exercise_id: mit find_exercise suchen, sonst nach Bestätigung mit upsert_exercise anlegen (oder Freitext begründen).'];
                    continue;
                }
                $exercise = is_string($slug) ? ($known[$slug] ?? null) : null;
                if ($exercise === null) {
                    $errors[] = $where + ['exercise_id' => $slug, 'code' => 'unbekannt', 'hinweis' => 'exercise_id „' . (is_string($slug) ? $slug : '?') . '“ gibt es im Katalog nicht (find_exercise).'];
                } elseif ($exercise['status'] === 'archiviert') {
                    $errors[] = $where + ['exercise_id' => $slug, 'code' => 'archiviert', 'hinweis' => 'Übung „' . $exercise['name'] . '“ ist archiviert und darf nicht mehr geplant werden.'];
                } elseif (!$isBlock && !in_array(Catalog::normalize($label), $exercise['norms'], true)) {
                    $warnings[] = $where + ['name' => $label, 'code' => 'name_abweichend',
                        'hinweis' => 'name weicht vom Katalog ab („' . $exercise['name'] . '“); Katalognamen verwenden oder Alias ergänzen.'];
                }
            }
        }

        return ['fehler' => $errors, 'warnungen' => $warnings];
    }

    /**
     * Übungen eines Plans mit Katalogverweis für die Anzeige (S3, S9, Kalender): Position → Übung (Slug, Name).
     * Archivierte Übungen bleiben verlinkt (lesbar); unbekannte Slugs werden ignoriert.
     * @return array<int, array{slug: string, name: string}>
     */
    public function linksFor(string $type, mixed $plan): array
    {
        $known = $this->exercises->lookup(ExerciseRepository::slugsInPlan($plan));
        $out = [];
        foreach ($this->items($type, $plan) as [$position, $item]) {
            $slug = $item['exercise_id'] ?? null;
            if (is_string($slug) && isset($known[$slug])) {
                $out[$position] = ['slug' => $slug, 'name' => $known[$slug]['name']];
            }
        }

        return $out;
    }

    /** @return list<array{0: int, 1: array<string, mixed>, 2: bool}> Position, Eintrag, ist Kletterblock */
    private function items(string $type, mixed $plan): array
    {
        if (!is_array($plan)) {
            return [];
        }
        $list = in_array($type, self::EXERCISE_TYPES, true) ? 'exercises' : ($type === 'klettern' ? 'blocks' : null);
        $out = [];
        foreach ($list !== null && is_array($plan[$list] ?? null) ? array_values($plan[$list]) : [] as $i => $item) {
            if (is_array($item)) {
                $out[] = [$i, $item, $list === 'blocks'];
            }
        }

        return $out;
    }
}
