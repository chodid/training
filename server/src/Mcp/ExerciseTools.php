<?php

declare(strict_types=1);

namespace Training\Mcp;

use PDO;
use Training\Clock;
use Training\Data\AuditLog;
use Training\Data\ExerciseRepository;
use Training\Exercise\Catalog;
use Training\Exercise\ContentValidator;
use Training\Exercise\LinkChecker;

/**
 * MCP-Tools des Übungskatalogs (AP-16, docs/konzept/uebungskatalog.md 5.1): find_exercise, get_exercise,
 * list_exercises, upsert_exercise. Antworten kompakt nach Budget E-16.
 */
final class ExerciseTools
{
    public const REASON_MAX = 255;

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly ?LinkChecker $links = null,
        private readonly ?ContentValidator $validator = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function find(string $query, ?string $category, ?string $pattern, ?string $equipment, int $limit, bool $includeArchived): array
    {
        if (mb_strlen(trim($query)) < 2 || Catalog::normalize($query) === '') {
            throw new ToolError('query braucht mindestens 2 Zeichen (Buchstaben oder Ziffern).');
        }
        self::enum('category', $category, Catalog::CATEGORIES);
        self::enum('pattern', $pattern, Catalog::PATTERNS);
        self::enum('equipment', $equipment, Catalog::EQUIPMENT);
        $hits = $this->repo()->search($query, $category, $pattern, $equipment, max(1, min(20, $limit)), $includeArchived);
        if ($hits === []) {
            return ['treffer' => [], 'hinweis' => 'keine Übung gefunden – upsert_exercise anlegen (nach Bestätigung des Wochenplans)'];
        }

        // kompakt (Budget E-16): null-Felder, aehnlich = false und status = aktiv entfallen
        return ['treffer' => array_map(static fn (array $h): array => array_filter($h, static fn ($v, string $k): bool => $v !== null
            && !($k === 'aehnlich' && $v === false) && !($k === 'status' && $v === 'aktiv'), ARRAY_FILTER_USE_BOTH), $hits)];
    }

    /** @return array<string, mixed> */
    public function get(?string $slug, ?int $id, bool $versions, ?int $version): array
    {
        $repo = $this->repo();
        $e = $slug !== null ? $repo->bySlug($slug) : ($id !== null ? $repo->byId($id) : throw new ToolError('slug oder id angeben.'));
        if ($e === null) {
            throw new ToolError('Übung ' . ($slug ?? (string) $id) . ' nicht gefunden (find_exercise).');
        }
        if ($version !== null) {
            if ($version === $e['version']) {
                throw new ToolError('Fassung ' . $version . ' ist der aktuelle Stand; ohne version abrufen.');
            }

            return ['slug' => $e['slug'], 'fassung' => $repo->snapshot($e['id'], $version) ?? throw new ToolError('Fassung ' . $version . ' gibt es nicht.')];
        }
        $out = [
            'slug' => $e['slug'], 'id' => $e['id'], 'name' => $e['name'], 'aliases' => $e['aliases'],
            'category' => $e['category'], 'pattern' => $e['pattern'], 'equipment' => $e['equipment'],
            'variant_of' => $e['variant_of_slug'] !== null ? ['slug' => $e['variant_of_slug'], 'name' => $e['variant_of_name']] : null,
            'varianten' => $repo->children($e['id']),
            'difficulty' => $e['difficulty'], 'status' => $e['status'], 'konfidenz' => $e['konfidenz'], 'version' => $e['version'],
            'content' => $e['content'], 'aktualisiert' => $e['updated_at'],
        ];
        if ($versions) {
            $out['fassungen'] = array_map(static fn (array $v): array => ['version' => $v['version'], 'reason' => $v['reason'], 'created_at' => $v['created_at']], $repo->versions($e['id']));
        }

        return $out;
    }

    /** @return array<string, mixed> */
    public function list(?string $category, ?string $status): array
    {
        self::enum('category', $category, Catalog::CATEGORIES);
        self::enum('status', $status, Catalog::STATUSES);
        $rows = $this->repo()->listCompact($category, $status);

        return ['anzahl' => count($rows), 'uebungen' => array_map(static fn (array $r): array => [$r['slug'], $r['name'], $r['category'], $r['pattern']]
            + ($r['status'] !== 'aktiv' ? [4 => $r['status']] : []), $rows), 'felder' => ['slug', 'name', 'category', 'pattern', 'status (nur wenn nicht aktiv)']];
    }

    /**
     * Anlegen (Slug frei) oder ändern (Slug vorhanden, reason Pflicht). Linkprüfung vor dem Schreiben, außerhalb der
     * Transaktion; ein defekter Link ist kein Fehler, sondern setzt die Übung auf links_pruefen (E-10).
     * @param array<string, mixed> $in
     * @return array<string, mixed>
     */
    public function upsert(array $in): array
    {
        $slug = $in['slug'] ?? null;
        if (!Catalog::isSlug($slug)) {
            throw new ToolError('slug ungültig: a–z, 0–9 und einzelne Bindestriche, 3–60 Zeichen (z. B. „bulgarian-split-squat“).');
        }
        $repo = $this->repo();
        $existing = $repo->bySlug($slug);
        $reason = is_string($in['reason'] ?? null) ? trim($in['reason']) : '';
        $errors = [];
        if ($existing !== null && $reason === '') {
            throw new ToolError('reason fehlt: Übung „' . $existing['name'] . '“ gibt es schon (Fassung ' . $existing['version'] . '); Änderungen brauchen einen Grund.');
        }
        if (mb_strlen($reason) > self::REASON_MAX) {
            $errors[] = 'reason ist länger als ' . self::REASON_MAX . ' Zeichen';
        }

        $base = $existing ?? ['name' => null, 'aliases' => [], 'category' => null, 'pattern' => null, 'equipment' => null, 'variant_of' => null,
            'variant_of_slug' => null, 'difficulty' => null, 'status' => 'aktiv', 'konfidenz' => null, 'content' => null];
        $name = array_key_exists('name', $in) ? (is_string($in['name']) ? trim($in['name']) : '') : (string) $base['name'];
        if ($name === '' || mb_strlen($name) > 120 || Catalog::normalize($name) === '') {
            $errors[] = 'name fehlt oder ist länger als 120 Zeichen';
        }
        $aliases = $in['aliases'] ?? $base['aliases'];
        if (!is_array($aliases) || count($aliases) > 10 || array_filter($aliases, static fn ($a): bool => !is_string($a) || mb_strlen(trim($a)) > 120) !== []) {
            $errors[] = 'aliases muss eine Liste mit höchstens 10 Texten à 120 Zeichen sein';
            $aliases = [];
        }
        $category = $in['category'] ?? $base['category'];
        if (!in_array($category, Catalog::CATEGORIES, true)) {
            $errors[] = 'category muss einer von ' . implode(', ', Catalog::CATEGORIES) . ' sein';
        }
        $pattern = $in['pattern'] ?? $base['pattern'];
        if (!in_array($pattern, Catalog::PATTERNS, true)) {
            $errors[] = 'pattern muss einer von ' . implode(', ', Catalog::PATTERNS) . ' sein';
        }
        $equipment = $in['equipment'] ?? $base['equipment'];
        if (!is_array($equipment) || $equipment === [] || array_diff($equipment, Catalog::EQUIPMENT) !== []) {
            $errors[] = 'equipment muss eine nicht leere Liste aus ' . implode(', ', Catalog::EQUIPMENT) . ' sein';
            $equipment = [];
        }
        $difficulty = array_key_exists('difficulty', $in) ? $in['difficulty'] : $base['difficulty'];
        if ($difficulty !== null && (!is_int($difficulty) || $difficulty < 1 || $difficulty > 5)) {
            $errors[] = 'difficulty muss 1–5 oder null sein';
        }
        $konfidenz = $in['konfidenz'] ?? $base['konfidenz'];
        if (!in_array($konfidenz, Catalog::KONFIDENZ, true)) {
            $errors[] = 'konfidenz muss einer von ' . implode(', ', Catalog::KONFIDENZ) . ' sein (E-06)';
        }
        $variantOf = $base['variant_of'];
        if (array_key_exists('variant_of', $in)) {
            $variantOf = $in['variant_of'] === null ? null : $this->variantId($in['variant_of'], $existing['id'] ?? null, $errors);
        }
        $status = $in['status'] ?? null;
        if ($status !== null && !in_array($status, ['aktiv', 'archiviert'], true)) {
            $errors[] = 'status darf nur aktiv oder archiviert sein (links_pruefen setzt der Server)';
        }

        $content = $base['content'];
        $linksChanged = false;
        if (array_key_exists('content', $in)) {
            if (!is_array($in['content'])) {
                $errors[] = 'content muss ein Objekt sein';
            } else {
                $prepared = ($this->validator ?? ContentValidator::default())->prepare($in['content']);
                $errors = [...$errors, ...$prepared['errors']];
                $content = $prepared['content'];
                $linksChanged = true;
            }
        } elseif ($existing === null) {
            $errors[] = 'content fehlt (Schema exercise.json: kurz, ziel, ausfuehrung, quellen …)';
        }
        if ($errors !== []) {
            throw new ToolError($existing === null ? 'Übung nicht angelegt.' : 'Übung nicht geändert.', $errors);
        }

        // Duplikatschutz (E-09): Name und Aliase gegen Namen und Aliase anderer Übungen
        $conflicts = $repo->conflicts([Catalog::normalize($name), ...array_map(Catalog::normalize(...), $aliases)], $existing['id'] ?? null);
        if ($conflicts !== []) {
            $c = $conflicts[0];
            throw new ToolError('Übung gibt es schon: „' . $c['name'] . '“ (' . $c['slug'] . ', Treffer „' . $c['treffer'] . '“). Diese verwenden, ändern oder eine Variante mit variant_of anlegen.',
                array_map(static fn (array $x): string => $x['slug'] . ': ' . $x['treffer'], $conflicts));
        }
        // Archivieren nur ohne geplante Verwendung (4.1)
        if ($status === 'archiviert' && $existing !== null && $existing['status'] !== 'archiviert') {
            $usage = $repo->plannedUsage($slug);
            if ($usage !== []) {
                throw new ToolError('Archivieren nicht möglich: Übung ist in geplanten Einheiten verwendet (erst dort ersetzen).',
                    array_map(static fn (array $u): string => 'Einheit ' . $u['id'] . ' (' . $u['date'] . ', ' . $u['title'] . ')', $usage));
            }
        }

        if ($linksChanged && $content['links'] !== []) {
            $content['links'] = ($this->links ?? throw new \LogicException('Linkprüfung fehlt'))->check($content['links'], $existing['content']['links'] ?? []);
        }
        $newStatus = match (true) {
            $status === 'archiviert' => 'archiviert',
            $status === null && ($existing['status'] ?? null) === 'archiviert' => 'archiviert',
            default => LinkChecker::exerciseStatus($content['links']),
        };
        $data = ['slug' => $slug, 'name' => $name, 'aliases' => array_values(array_map('trim', $aliases)), 'category' => $category, 'pattern' => $pattern,
            'equipment' => array_values(array_unique($equipment)), 'variant_of' => $variantOf, 'difficulty' => $difficulty, 'status' => $newStatus,
            'konfidenz' => $konfidenz, 'content' => $content];

        if ($existing !== null && self::same($existing, $data) && ($status === null || $status === $existing['status'])) {
            // Inhalt gleich: nur das Ergebnis der Linkprüfung nachtragen, keine neue Fassung
            if ($existing['content'] != $data['content'] || $existing['status'] !== $data['status']) {
                $repo->updateLinkState($existing['id'], $data['content'], $data['status']);
            }

            return self::summary($data, $existing['version'], false) + ['unveraendert' => true, 'hinweis' => 'Inhalt unverändert, keine neue Fassung angelegt (Linkstatus aktualisiert).'];
        }

        $audit = new AuditLog($this->pdo, $this->clock);
        $this->pdo->beginTransaction();
        try {
            if ($existing === null) {
                $id = $repo->create($data, 'mcp');
                $version = 1;
            } else {
                $id = $existing['id'];
                $version = $repo->update($id, $data, $reason, 'mcp');
            }
            $audit->write('mcp', 'exercise_write', 'exercise', $id, $data, ($existing === null ? 'Übung angelegt: ' : 'Übung geändert (Fassung ' . $version . '): ') . $name
                . ' (' . $slug . ')' . ($reason !== '' ? ' – ' . $reason : '') . ($newStatus !== 'aktiv' ? ' [' . $newStatus . ']' : ''));
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return self::summary($data, $version, $existing === null) + ['hinweis_chat' => self::chatNote($data, $version, $existing === null, $reason)];
    }

    /** @param list<string> $errors */
    private function variantId(mixed $slug, ?int $selfId, array &$errors): ?int
    {
        if (!Catalog::isSlug($slug)) {
            $errors[] = 'variant_of muss der Slug einer vorhandenen Übung sein';

            return null;
        }
        $repo = $this->repo();
        $parent = $repo->bySlug($slug);
        if ($parent === null) {
            $errors[] = 'variant_of „' . $slug . '“ gibt es nicht';

            return null;
        }
        // keine Schleife in der Progressionsleiter
        for ($p = $parent, $depth = 0; $p !== null && $depth < 50; $p = $p['variant_of'] !== null ? $repo->byId($p['variant_of']) : null, $depth++) {
            if ($selfId !== null && $p['id'] === $selfId) {
                $errors[] = 'variant_of „' . $slug . '“ ergäbe eine Schleife (Übung wäre ihre eigene Variante)';

                return null;
            }
        }

        return $parent['id'];
    }

    /** @param array<string, mixed> $existing @param array<string, mixed> $data */
    private static function same(array $existing, array $data): bool
    {
        foreach (['name', 'category', 'pattern', 'equipment', 'variant_of', 'difficulty', 'konfidenz'] as $k) {
            if ($existing[$k] != $data[$k]) {
                return false;
            }
        }
        if (self::withoutServerFields($existing['content']) != self::withoutServerFields($data['content'])) {
            return false;
        }
        $a = array_map(Catalog::normalize(...), $existing['aliases']);
        $b = array_values(array_filter(array_map(Catalog::normalize(...), $data['aliases']), static fn (string $n): bool => $n !== '' && $n !== Catalog::normalize($data['name'])));
        sort($a);
        sort($b);

        return $a === array_values(array_unique($b));
    }

    /** Inhalt ohne Ergebnis der Linkprüfung (status, geprueft_am). @param array<string, mixed> $content @return array<string, mixed> */
    private static function withoutServerFields(array $content): array
    {
        foreach ($content['links'] ?? [] as $i => $link) {
            unset($content['links'][$i]['status'], $content['links'][$i]['geprueft_am']);
        }

        return $content;
    }

    /** @param array<string, mixed> $d @return array<string, mixed> */
    private static function summary(array $d, int $version, bool $new): array
    {
        return [
            'slug' => $d['slug'], 'name' => $d['name'], 'category' => $d['category'], 'version' => $version, 'status' => $d['status'], 'neu' => $new,
            'links' => array_map(static fn (array $l): array => ['titel' => $l['titel'], 'art' => $l['art'], 'status' => $l['status']] + ($l['embed'] !== null ? ['eingebettet' => true] : []), $d['content']['links']),
        ];
    }

    /** Vorformulierter Satz für den Chat (E-02). @param array<string, mixed> $d */
    private static function chatNote(array $d, int $version, bool $new, string $reason): string
    {
        $links = $d['content']['links'];
        $defect = count(array_filter($links, static fn (array $l): bool => $l['status'] === 'defekt'));
        $open = count(array_filter($links, static fn (array $l): bool => $l['status'] === 'ungeprueft'));
        $linkText = match (true) {
            $links === [] => 'ohne Links',
            $defect > 0 => count($links) . ' ' . (count($links) === 1 ? 'Link' : 'Links') . ', davon ' . $defect . ' defekt – bitte ersetzen',
            $open > 0 => count($links) . ' ' . (count($links) === 1 ? 'Link' : 'Links') . ', davon ' . $open . ' nicht prüfbar',
            default => count($links) . ' ' . (count($links) === 1 ? 'Link' : 'Links') . ' geprüft',
        };
        $sources = implode(', ', $d['content']['quellen']);
        $head = $new
            ? 'Neu im Katalog: „' . $d['name'] . '“ (' . $d['category'] . ', ' . $d['pattern'] . ', ' . implode('/', $d['equipment']) . ')'
            : 'Katalog geändert: „' . $d['name'] . '“, Fassung ' . $version . ' (' . $reason . ')';

        return $head . ', ' . $linkText . ', ' . (count($d['content']['quellen']) === 1 ? 'Quelle ' : 'Quellen ') . $sources . ($d['status'] === 'archiviert' ? ', archiviert' : '') . '.';
    }

    /** @param list<string> $allowed */
    private static function enum(string $field, ?string $value, array $allowed): void
    {
        if ($value !== null && !in_array($value, $allowed, true)) {
            throw new ToolError($field . ' muss einer von ' . implode(', ', $allowed) . ' sein.');
        }
    }

    private function repo(): ExerciseRepository
    {
        return new ExerciseRepository($this->pdo, $this->clock);
    }
}
