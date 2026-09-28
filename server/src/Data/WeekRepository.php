<?php

declare(strict_types=1);

namespace Training\Data;

use PDO;
use Training\Clock;
use Training\Db;

/** Lesen von Block, Woche und Einheiten; Statusänderung einer Einheit (AP-04). */
final class WeekRepository
{
    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    /** @return array<string, mixed>|null Woche mit Blockdaten und Wochennummer im Block */
    public function week(string $monday): ?array
    {
        $stmt = $this->pdo->prepare('SELECT w.*, b.name AS block_name, b.start_date AS block_start, b.end_date AS block_end,
                (SELECT COUNT(*) FROM training_block b2 WHERE b2.start_date <= b.start_date) AS block_no
            FROM training_week w JOIN training_block b ON b.id = w.block_id WHERE w.week_start = ?');
        $stmt->execute([$monday]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        $row['week_no'] = intdiv((int) ((strtotime($monday) - strtotime((string) $row['block_start'])) / 86400), 7) + 1;
        $row['week_count'] = intdiv((int) ((strtotime((string) $row['block_end']) - strtotime((string) $row['block_start'])) / 86400), 7) + 1;

        return $row;
    }

    /**
     * Einheiten im Zeitraum mit Kennzahlen der Durchführung.
     *
     * @return list<array<string, mixed>>
     */
    public function sessions(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare('SELECT s.*, e.id AS execution_id, e.rpe_cr10, e.srpe_load, e.duration_min, e.feel_1_5
            FROM `session` s LEFT JOIN session_execution e ON e.session_id = s.id
            WHERE s.date BETWEEN ? AND ? ORDER BY s.date, s.sort_order, s.id');
        $stmt->execute([$from, $to]);

        return array_map(self::decode(...), $stmt->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function session(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `session` WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : self::decode($row);
    }

    public function setStatus(int $sessionId, string $status): void
    {
        $this->pdo->prepare('UPDATE `session` SET status = ?, updated_at = ? WHERE id = ?')
            ->execute([$status, Db::ts($this->clock->now()), $sessionId]);
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private static function decode(array $row): array
    {
        $row['plan'] = isset($row['plan_json']) && $row['plan_json'] !== null ? json_decode((string) $row['plan_json'], true) : null;

        return $row;
    }
}
