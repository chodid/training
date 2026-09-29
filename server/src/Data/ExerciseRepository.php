<?php

declare(strict_types=1);

namespace Training\Data;

use PDO;
use Training\Clock;
use Training\Db;
use Training\Exercise\Catalog;

/**
 * Übungskatalog (AP-16, docs/konzept/uebungskatalog.md 4.1): Übungen mit Aliasen und Fassungen. Jede Änderung legt
 * vorher einen Schnappschuss (alle Spalten und Aliase) in exercise_version ab und erhöht exercise.version (E-07).
 * Keine Löschfunktion; archivierte Übungen bleiben lesbar. Der Katalog ist klein (verwendete Übungen, E-13); Suche und
 * Ähnlichkeit rechnen deshalb in PHP über alle Einträge.
 */
final class ExerciseRepository
{
    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    /** @return array<string, mixed>|null Übung mit dekodierten JSON-Spalten, Aliasen und Slug der Elternübung */
    public function bySlug(string $slug): ?array
    {
        return $this->one('e.slug = ?', [$slug]);
    }

    /** @return array<string, mixed>|null */
    public function byId(int $id): ?array
    {
        return $this->one('e.id = ?', [$id]);
    }

    /**
     * Ein Abruf für alle Slugs eines Plans (ExerciseLink, Webseite).
     * @param list<string> $slugs
     * @return array<string, array{id: int, slug: string, name: string, status: string, norms: list<string>}>
     */
    public function lookup(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter($slugs, Catalog::isSlug(...))));
        if ($slugs === []) {
            return [];
        }
        $stmt = $this->pdo->prepare('SELECT e.id, e.slug, e.name, e.name_norm, e.status, a.alias_norm FROM exercise e
            LEFT JOIN exercise_alias a ON a.exercise_id = e.id WHERE e.slug IN (' . implode(',', array_fill(0, count($slugs), '?')) . ')');
        $stmt->execute($slugs);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $slug = (string) $row['slug'];
            $out[$slug] ??= ['id' => (int) $row['id'], 'slug' => $slug, 'name' => (string) $row['name'], 'status' => (string) $row['status'], 'norms' => [(string) $row['name_norm']]];
            if ($row['alias_norm'] !== null) {
                $out[$slug]['norms'][] = (string) $row['alias_norm'];
            }
        }

        return $out;
    }

    /**
     * Übungen, deren Name oder Alias (normalisiert) mit einer der Schreibweisen übereinstimmt (Duplikatschutz E-09).
     * @param list<string> $norms
     * @return list<array{slug: string, name: string, treffer: string}>
     */
    public function conflicts(array $norms, ?int $excludeId = null): array
    {
        $norms = array_values(array_unique(array_filter($norms, static fn (string $n): bool => $n !== '')));
        if ($norms === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($norms), '?'));
        $stmt = $this->pdo->prepare("SELECT e.slug, e.name, e.name AS treffer FROM exercise e WHERE e.name_norm IN ($in) AND e.id <> ?
            UNION SELECT e.slug, e.name, a.alias AS treffer FROM exercise_alias a JOIN exercise e ON e.id = a.exercise_id WHERE a.alias_norm IN ($in) AND e.id <> ?");
        $stmt->execute([...$norms, $excludeId ?? 0, ...$norms, $excludeId ?? 0]);

        return array_map(static fn (array $r): array => ['slug' => (string) $r['slug'], 'name' => (string) $r['name'], 'treffer' => (string) $r['treffer']], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Suche (find_exercise, 5.1): exakter Slug/Name/Alias → Teilstring in Slug/Name/Alias → ähnlich (gleiches
     * Bewegungsmuster wie ein Treffer bzw. wie der Filter pattern, sortiert nach Überschneidung der Ausrüstung).
     * Filter gelten für direkte Treffer; ähnliche Übungen beachten nur category und include_archived.
     * @return list<array<string, mixed>> kompakte Einträge mit aehnlich
     */
    public function search(string $query, ?string $category = null, ?string $pattern = null, ?string $equipment = null, int $limit = 10, bool $includeArchived = false): array
    {
        $norm = Catalog::normalize($query);
        $slugQuery = str_replace(' ', '-', $norm);
        $rows = array_filter($this->compactAll(), static fn (array $r): bool => $includeArchived || $r['status'] !== 'archiviert');
        $inCategory = static fn (array $r): bool => $category === null || $r['category'] === $category;

        $ranked = [];
        foreach ($rows as $r) {
            if (!$inCategory($r) || ($pattern !== null && $r['pattern'] !== $pattern) || ($equipment !== null && !in_array($equipment, $r['equipment'], true))) {
                continue;
            }
            $norms = [$r['name_norm'], ...$r['alias_norms']];
            if ($norm === '') {
                continue;
            }
            if ($r['slug'] === $slugQuery || in_array($norm, $norms, true)) {
                $ranked[] = [0, $r];
            } elseif (str_contains($r['slug'], $slugQuery) || array_filter($norms, static fn (string $n): bool => str_contains($n, $norm)) !== []) {
                $ranked[] = [1, $r];
            }
        }
        usort($ranked, static fn (array $a, array $b): int => [$a[0], $a[1]['name']] <=> [$b[0], $b[1]['name']]);
        $direct = array_map(static fn (array $x): array => $x[1], $ranked);

        $patterns = $pattern !== null ? [$pattern] : array_values(array_unique(array_column($direct, 'pattern')));
        $equipmentSeen = $equipment !== null ? [$equipment] : array_values(array_unique(array_merge([], ...array_column($direct, 'equipment'))));
        $directSlugs = array_column($direct, 'slug');
        $similar = [];
        foreach ($rows as $r) {
            if (in_array($r['slug'], $directSlugs, true) || !$inCategory($r) || !in_array($r['pattern'], $patterns, true)) {
                continue;
            }
            $similar[] = [count(array_intersect($r['equipment'], $equipmentSeen)), $r];
        }
        usort($similar, static fn (array $a, array $b): int => [$b[0], $a[1]['name']] <=> [$a[0], $b[1]['name']]);

        $out = [];
        foreach ([[false, $direct], [true, array_map(static fn (array $x): array => $x[1], $similar)]] as [$isSimilar, $list]) {
            foreach ($list as $r) {
                if (count($out) >= $limit) {
                    break 2;
                }
                $out[] = self::compact($r) + ['aehnlich' => $isSimilar];
            }
        }

        return $out;
    }

    /**
     * Kompaktliste (list_exercises); ohne Statusfilter ohne archivierte Übungen.
     * @return list<array{slug: string, name: string, category: string, pattern: string, status: string}>
     */
    public function listCompact(?string $category = null, ?string $status = null): array
    {
        $where = [];
        $args = [];
        if ($category !== null) {
            $where[] = 'category = ?';
            $args[] = $category;
        }
        if ($status !== null) {
            $where[] = 'status = ?';
            $args[] = $status;
        } else {
            $where[] = "status <> 'archiviert'";
        }
        $stmt = $this->pdo->prepare('SELECT slug, name, category, pattern, status FROM exercise' . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY category, name');
        $stmt->execute($args);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Liste für die Webseite (S10a): Suche über Name/Alias (normalisiert), Filter Kategorie, archivierte optional.
     * @return list<array<string, mixed>>
     */
    public function listForPage(?string $query, ?string $category, bool $includeArchived): array
    {
        $norm = $query !== null ? Catalog::normalize($query) : '';
        $out = [];
        foreach ($this->compactAll() as $r) {
            if ((!$includeArchived && $r['status'] === 'archiviert') || ($category !== null && $r['category'] !== $category)) {
                continue;
            }
            if ($norm !== '' && !str_contains($r['slug'], str_replace(' ', '-', $norm))
                && array_filter([$r['name_norm'], ...$r['alias_norms']], static fn (string $n): bool => str_contains($n, $norm)) === []) {
                continue;
            }
            $out[] = self::compact($r);
        }
        usort($out, static fn (array $a, array $b): int => strcmp(Catalog::normalize($a['name']), Catalog::normalize($b['name'])));

        return $out;
    }

    /**
     * @param array{slug: string, name: string, aliases: list<string>, category: string, pattern: string, equipment: list<string>,
     *     variant_of: ?int, difficulty: ?int, status: string, konfidenz: string, content: array<string, mixed>} $data
     */
    public function create(array $data, string $by): int
    {
        $now = Db::ts($this->clock->now());
        $this->pdo->prepare('INSERT INTO exercise (slug, name, name_norm, category, pattern, equipment_json, variant_of, difficulty, status, konfidenz, content_json, version, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)')
            ->execute([$data['slug'], $data['name'], Catalog::normalize($data['name']), $data['category'], $data['pattern'], self::json($data['equipment']),
                $data['variant_of'], $data['difficulty'], $data['status'], $data['konfidenz'], self::json($data['content']), $by, $now, $now]);
        $id = (int) $this->pdo->lastInsertId();
        $this->writeAliases($id, $data['name'], $data['aliases']);

        return $id;
    }

    /**
     * Neue Fassung: Schnappschuss des bisherigen Stands mit Grund, dann Änderung und version + 1 (slug bleibt).
     * @param array{name: string, aliases: list<string>, category: string, pattern: string, equipment: list<string>,
     *     variant_of: ?int, difficulty: ?int, status: string, konfidenz: string, content: array<string, mixed>} $data
     * @return int neue Versionsnummer
     */
    public function update(int $id, array $data, string $reason, string $by): int
    {
        $before = $this->byId($id) ?? throw new \RuntimeException('Übung ' . $id . ' nicht gefunden.');
        $now = Db::ts($this->clock->now());
        $this->pdo->prepare('INSERT INTO exercise_version (exercise_id, version, snapshot_json, reason, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$id, $before['version'], self::json(self::snapshotOf($before)), mb_substr($reason, 0, 255), $by, $now]);
        $version = $before['version'] + 1;
        $this->pdo->prepare('UPDATE exercise SET name = ?, name_norm = ?, category = ?, pattern = ?, equipment_json = ?, variant_of = ?, difficulty = ?, status = ?, konfidenz = ?,
                content_json = ?, version = ?, updated_at = ? WHERE id = ?')
            ->execute([$data['name'], Catalog::normalize($data['name']), $data['category'], $data['pattern'], self::json($data['equipment']), $data['variant_of'],
                $data['difficulty'], $data['status'], $data['konfidenz'], self::json($data['content']), $version, $now, $id]);
        $this->pdo->prepare('DELETE FROM exercise_alias WHERE exercise_id = ?')->execute([$id]);
        $this->writeAliases($id, $data['name'], $data['aliases']);

        return $version;
    }

    /** Ergebnis einer Linkprüfung ohne neue Fassung (Serverfelder, E-10; Cron Teil D). @param array<string, mixed> $content */
    public function updateLinkState(int $id, array $content, string $status): void
    {
        $this->pdo->prepare('UPDATE exercise SET content_json = ?, status = ?, updated_at = ? WHERE id = ?')
            ->execute([self::json($content), $status, Db::ts($this->clock->now()), $id]);
    }

    /** @return list<array{version: int, reason: string, created_by: string, created_at: string}> neueste zuerst */
    public function versions(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT version, reason, created_by, created_at FROM exercise_version WHERE exercise_id = ? ORDER BY version DESC');
        $stmt->execute([$id]);

        return array_map(static fn (array $r): array => ['version' => (int) $r['version']] + $r, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<string, mixed>|null Schnappschuss einer früheren Fassung */
    public function snapshot(int $id, int $version): ?array
    {
        $stmt = $this->pdo->prepare('SELECT snapshot_json, reason, created_at FROM exercise_version WHERE exercise_id = ? AND version = ?');
        $stmt->execute([$id, $version]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : ['version' => $version, 'reason' => $row['reason'], 'ersetzt_am' => $row['created_at'], 'stand' => json_decode((string) $row['snapshot_json'], true)];
    }

    /** @return list<array{slug: string, name: string}> direkte Varianten (variant_of = id) */
    public function children(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT slug, name FROM exercise WHERE variant_of = ? ORDER BY name');
        $stmt->execute([$id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Geplante Einheiten, deren plan_json die Übung verwendet (Archivieren gesperrt, 4.1).
     * @return list<array{id: int, date: string, title: string}>
     */
    public function plannedUsage(string $slug): array
    {
        $stmt = $this->pdo->prepare("SELECT id, date, title, plan_json FROM `session` WHERE status = 'geplant' AND plan_json LIKE ? ORDER BY date, id");
        $stmt->execute(['%' . $slug . '%']);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (in_array($slug, self::slugsInPlan(json_decode((string) $row['plan_json'], true)), true)) {
                $out[] = ['id' => (int) $row['id'], 'date' => (string) $row['date'], 'title' => (string) $row['title']];
            }
        }

        return $out;
    }

    /** @return list<array<string, mixed>> aktive Übungen mit Links (Linkprüfung im Cron, Teil D), älteste Prüfung zuerst */
    public function withLinks(): array
    {
        $stmt = $this->pdo->query("SELECT id, slug, name, status, content_json FROM exercise WHERE status <> 'archiviert' AND JSON_LENGTH(content_json, '$.links') > 0 ORDER BY id");
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['id'] = (int) $row['id'];
            $row['content'] = json_decode((string) $row['content_json'], true);
            unset($row['content_json']);
            $out[] = $row;
        }

        return $out;
    }

    /** @return int Übungen mit Status links_pruefen (Hinweis in S8) */
    public function countLinksToCheck(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM exercise WHERE status = 'links_pruefen'")->fetchColumn();
    }

    /**
     * Slugs aus einem plan_json (exercises[].exercise_id, blocks[].exercise_id), in Reihenfolge ohne Doppelte.
     * @return list<string>
     */
    public static function slugsInPlan(mixed $plan): array
    {
        if (!is_array($plan)) {
            return [];
        }
        $slugs = [];
        foreach (['exercises', 'blocks'] as $list) {
            foreach (is_array($plan[$list] ?? null) ? $plan[$list] : [] as $item) {
                if (is_array($item) && Catalog::isSlug($item['exercise_id'] ?? null)) {
                    $slugs[] = $item['exercise_id'];
                }
            }
        }

        return array_values(array_unique($slugs));
    }

    /** @param list<mixed> $args @return array<string, mixed>|null */
    private function one(string $where, array $args): ?array
    {
        $stmt = $this->pdo->prepare('SELECT e.*, p.slug AS variant_of_slug, p.name AS variant_of_name FROM exercise e LEFT JOIN exercise p ON p.id = e.variant_of WHERE ' . $where);
        $stmt->execute($args);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        foreach (['id', 'version'] as $k) {
            $row[$k] = (int) $row[$k];
        }
        foreach (['variant_of', 'difficulty'] as $k) {
            $row[$k] = $row[$k] === null ? null : (int) $row[$k];
        }
        $row['equipment'] = json_decode((string) $row['equipment_json'], true);
        $row['content'] = json_decode((string) $row['content_json'], true);
        unset($row['equipment_json'], $row['content_json']);
        $aliases = $this->pdo->prepare('SELECT alias FROM exercise_alias WHERE exercise_id = ? ORDER BY alias');
        $aliases->execute([$row['id']]);
        $row['aliases'] = $aliases->fetchAll(PDO::FETCH_COLUMN);

        return $row;
    }

    /** @return list<array<string, mixed>> alle Übungen kompakt mit normalisierten Namen/Aliasen (Suche) */
    private function compactAll(): array
    {
        $rows = $this->pdo->query("SELECT e.id, e.slug, e.name, e.name_norm, e.category, e.pattern, e.equipment_json, e.status, e.konfidenz,
                JSON_UNQUOTE(JSON_EXTRACT(e.content_json, '$.kurz')) AS kurz, p.slug AS variant_of
            FROM exercise e LEFT JOIN exercise p ON p.id = e.variant_of")->fetchAll(PDO::FETCH_ASSOC);
        $aliases = [];
        foreach ($this->pdo->query('SELECT exercise_id, alias_norm FROM exercise_alias')->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $aliases[(int) $a['exercise_id']][] = (string) $a['alias_norm'];
        }
        foreach ($rows as &$r) {
            $r['equipment'] = json_decode((string) $r['equipment_json'], true) ?: [];
            $r['alias_norms'] = $aliases[(int) $r['id']] ?? [];
        }
        unset($r);

        return $rows;
    }

    /** @param array<string, mixed> $r @return array<string, mixed> */
    private static function compact(array $r): array
    {
        return [
            'slug' => $r['slug'], 'name' => $r['name'], 'category' => $r['category'], 'pattern' => $r['pattern'],
            'equipment' => $r['equipment'], 'status' => $r['status'], 'konfidenz' => $r['konfidenz'], 'kurz' => $r['kurz'],
            'variant_of' => $r['variant_of'],
        ];
    }

    /** @param array<string, mixed> $e @return array<string, mixed> */
    private static function snapshotOf(array $e): array
    {
        return [
            'slug' => $e['slug'], 'name' => $e['name'], 'aliases' => $e['aliases'], 'category' => $e['category'], 'pattern' => $e['pattern'],
            'equipment' => $e['equipment'], 'variant_of' => $e['variant_of_slug'], 'difficulty' => $e['difficulty'], 'status' => $e['status'],
            'konfidenz' => $e['konfidenz'], 'content' => $e['content'], 'version' => $e['version'], 'created_by' => $e['created_by'],
            'updated_at' => $e['updated_at'],
        ];
    }

    /** @param list<string> $aliases */
    private function writeAliases(int $id, string $name, array $aliases): void
    {
        $seen = [Catalog::normalize($name) => true];
        $stmt = $this->pdo->prepare('INSERT INTO exercise_alias (exercise_id, alias, alias_norm) VALUES (?, ?, ?)');
        foreach ($aliases as $alias) {
            $norm = Catalog::normalize($alias);
            if ($norm === '' || isset($seen[$norm])) {
                continue; // leer, doppelt oder gleich dem eigenen Namen
            }
            $seen[$norm] = true;
            $stmt->execute([$id, mb_substr(trim($alias), 0, 120), $norm]);
        }
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
