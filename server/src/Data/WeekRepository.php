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

    /** Block, in dessen Zeitraum das Datum liegt (bei mehreren der aktive bzw. zuletzt begonnene). @return array<string, mixed>|null */
    public function blockFor(string $date): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM training_block WHERE ? BETWEEN start_date AND end_date ORDER BY status = 'aktiv' DESC, start_date DESC LIMIT 1");
        $stmt->execute([$date]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<string, mixed>|null */
    public function block(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM training_block WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return list<array<string, mixed>> Wochen eines Blocks mit Anzahl Einheiten */
    public function weeksOfBlock(int $blockId): array
    {
        $stmt = $this->pdo->prepare("SELECT w.week_start, w.status, w.focus,
                (SELECT COUNT(*) FROM `session` s WHERE s.week_id = w.id AND s.type <> 'ruhe') AS sessions,
                (SELECT COUNT(*) FROM `session` s WHERE s.week_id = w.id AND s.status IN ('erledigt','teilweise')) AS done
            FROM training_week w WHERE w.block_id = ? ORDER BY w.week_start");
        $stmt->execute([$blockId]);

        return $stmt->fetchAll();
    }

    /** Legt die Woche an oder aktualisiert Fokus/Notizen/Status; gibt die ID zurück. */
    public function upsertWeek(int $blockId, string $monday, ?string $focus, ?string $notes, string $status, string $createdBy): int
    {
        $now = Db::ts($this->clock->now());
        $stmt = $this->pdo->prepare('SELECT id FROM training_week WHERE week_start = ?');
        $stmt->execute([$monday]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            $this->pdo->prepare('INSERT INTO training_week (block_id, week_start, focus, coach_notes, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$blockId, $monday, $focus, $notes, $status, $createdBy, $now, $now]);

            return (int) $this->pdo->lastInsertId();
        }
        $this->pdo->prepare('UPDATE training_week SET block_id = ?, focus = COALESCE(?, focus), coach_notes = COALESCE(?, coach_notes), status = ?, updated_at = ? WHERE id = ?')
            ->execute([$blockId, $focus, $notes, $status, $now, (int) $id]);

        return (int) $id;
    }

    /** @param array<string, mixed> $s */
    public function insertSession(int $weekId, array $s): int
    {
        $now = Db::ts($this->clock->now());
        $this->pdo->prepare('INSERT INTO `session` (week_id, date, type, title, priority, planned_duration_min, plan_json, coach_rationale, status, sort_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'geplant\', ?, ?, ?)')
            ->execute([$weekId, $s['date'], $s['type'], $s['title'], $s['priority'], $s['planned_duration_min'],
                $s['plan_json'] === null ? null : json_encode($s['plan_json'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $s['coach_rationale'], $s['sort_order'], $now, $now]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $fields Spalte → Wert (plan_json als Array) */
    public function updateSession(int $id, array $fields): void
    {
        if ($fields === []) {
            return;
        }
        $sets = [];
        $values = [];
        foreach ($fields as $col => $value) {
            $sets[] = '`' . $col . '` = ?';
            $values[] = $col === 'plan_json' && $value !== null ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : $value;
        }
        $sets[] = 'updated_at = ?';
        $values[] = Db::ts($this->clock->now());
        $values[] = $id;
        $this->pdo->prepare('UPDATE `session` SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($values);
    }

    public function deleteSession(int $id): void
    {
        $this->pdo->prepare('DELETE FROM `session` WHERE id = ?')->execute([$id]);
    }

    public function weekIdFor(string $monday): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM training_week WHERE week_start = ?');
        $stmt->execute([$monday]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private static function decode(array $row): array
    {
        $row['plan'] = isset($row['plan_json']) && $row['plan_json'] !== null ? json_decode((string) $row['plan_json'], true) : null;

        return $row;
    }
}
