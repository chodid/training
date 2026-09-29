<?php

declare(strict_types=1);

namespace Training\Data;

use PDO;
use Training\Clock;
use Training\Db;

/**
 * Revision, Blockbilanz und Zielklärung je Block (Tabelle block_review, AP-15, E-10/E-11). Jede Änderung ist eine neue
 * Zeile; gültig ist je (block_id, kind, sequence) die bestätigte Zeile mit der höchsten version. Ein Entwurf mit
 * höherer version erscheint zusätzlich (Feld `entwurf`).
 */
final class ReviewRepository
{
    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    public function nextVersion(int $blockId, string $kind, int $sequence): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(version), 0) FROM block_review WHERE block_id = ? AND kind = ? AND sequence = ?');
        $stmt->execute([$blockId, $kind, $sequence]);

        return (int) $stmt->fetchColumn() + 1;
    }

    /** Nächste freie Nummer einer Revision im Block. */
    public function nextSequence(int $blockId): int
    {
        $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(sequence), 0) FROM block_review WHERE block_id = ? AND kind = 'revision'");
        $stmt->execute([$blockId]);

        return (int) $stmt->fetchColumn() + 1;
    }

    /**
     * @param array{block_id: int, kind: string, sequence: int, version: int, status: string, review_date: string,
     *     period_start: ?string, period_end: ?string, summary: string, content: array<string, mixed>,
     *     kennzahlen: ?array<string, mixed>, reason: ?string, created_by: string} $r
     */
    public function insert(array $r): int
    {
        $now = Db::ts($this->clock->now());
        $this->pdo->prepare('INSERT INTO block_review (block_id, kind, sequence, version, status, review_date, period_start, period_end, summary,
                content_json, kennzahlen_auto, reason, created_by, created_at, confirmed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$r['block_id'], $r['kind'], $r['sequence'], $r['version'], $r['status'], $r['review_date'], $r['period_start'], $r['period_end'],
                $r['summary'], json_encode($r['content'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $r['kennzahlen'] === null ? null : json_encode($r['kennzahlen'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $r['reason'], $r['created_by'], $now, $r['status'] === 'bestaetigt' ? $now : null]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Alle Fassungen, sortiert nach Block, Art (Zielklärung, Revision, Bilanz), Nummer und Fassung.
     * @return list<array<string, mixed>>
     */
    public function versions(?int $blockId = null, ?string $kind = null): array
    {
        $where = [];
        $params = [];
        if ($blockId !== null) {
            $where[] = 'block_id = ?';
            $params[] = $blockId;
        }
        if ($kind !== null) {
            $where[] = 'kind = ?';
            $params[] = $kind;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM block_review' . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
            . " ORDER BY block_id, FIELD(kind, 'zielklaerung', 'revision', 'bilanz'), sequence, version");
        $stmt->execute($params);

        return array_map(self::decode(...), $stmt->fetchAll());
    }

    /**
     * Gültige Fassungen je (block_id, kind, sequence): die jüngste bestätigte; ein neuerer Entwurf steht unter `entwurf`.
     * Schlüssel nur mit Entwürfen erscheinen mit `status` entwurf (noch nicht gültig) – außer mit $confirmedOnly.
     * @return list<array<string, mixed>>
     */
    public function current(?int $blockId = null, ?string $kind = null, bool $confirmedOnly = false): array
    {
        $groups = [];
        foreach ($this->versions($blockId, $kind) as $r) {
            $groups[$r['block_id'] . '|' . $r['kind'] . '|' . $r['sequence']][] = $r;
        }
        $out = [];
        foreach ($groups as $rows) {
            $confirmed = array_values(array_filter($rows, static fn (array $r): bool => $r['status'] === 'bestaetigt'));
            $valid = $confirmed !== [] ? end($confirmed) : null;
            $last = end($rows);
            if ($valid === null) {
                if (!$confirmedOnly) {
                    $out[] = $last + ['fassungen' => count($rows)];
                }
                continue;
            }
            $valid['fassungen'] = count($rows);
            if ($last['version'] > $valid['version']) {
                $valid['entwurf'] = ['version' => $last['version'], 'review_date' => $last['review_date'], 'summary' => $last['summary'], 'reason' => $last['reason']];
            }
            $out[] = $valid;
        }

        return $out;
    }

    /** @return array<string, mixed>|null jüngste gültige bestätigte Fassung einer Art (über alle Blöcke, nach review_date) */
    public function latestConfirmed(string $kind, ?int $blockId = null): ?array
    {
        $rows = $this->current($blockId, $kind, true);
        usort($rows, static fn (array $a, array $b): int => [$b['review_date'], $b['id']] <=> [$a['review_date'], $a['id']]);

        return $rows[0] ?? null;
    }

    /**
     * Gültige bestätigte Fassungen jeder Art in knapper Form für die Fälligkeit (Faelligkeit::compute).
     * @return list<array{block_id: int, kind: string, sequence: int, review_date: string}>
     */
    public function confirmedKeys(): array
    {
        try {
            return array_map(static fn (array $r): array => ['block_id' => $r['block_id'], 'kind' => $r['kind'], 'sequence' => $r['sequence'], 'review_date' => $r['review_date']],
                $this->current(null, null, true));
        } catch (\PDOException) {
            return []; // Schema älter als 24 (Update erforderlich)
        }
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private static function decode(array $row): array
    {
        foreach (['id', 'block_id', 'sequence', 'version'] as $k) {
            $row[$k] = (int) $row[$k];
        }
        $row['content'] = json_decode((string) $row['content_json'], true);
        $row['kennzahlen'] = $row['kennzahlen_auto'] !== null ? json_decode((string) $row['kennzahlen_auto'], true) : null;
        unset($row['content_json'], $row['kennzahlen_auto']);

        return $row;
    }
}
