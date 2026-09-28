<?php

declare(strict_types=1);

namespace Training\Data;

use PDO;
use Training\Clock;
use Training\Db;

/** Durchführung, Schmerzereignisse und Check-ins (Abschnitt 7, 11; D-16). */
final class FeedbackRepository
{
    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    /** @return array<string, mixed>|null */
    public function execution(int $sessionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM session_execution WHERE session_id = ?');
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        $row['actual'] = $row['actual_json'] !== null ? json_decode((string) $row['actual_json'], true) : null;

        return $row;
    }

    /** @param array<string, mixed> $e Felder performed_at, duration_min, actual, rpe_cr10, feel_1_5, deviation_reason, notes */
    public function saveExecution(int $sessionId, array $e): int
    {
        $now = Db::ts($this->clock->now());
        $actual = $e['actual'] === null ? null : json_encode($e['actual'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->pdo->prepare('INSERT INTO session_execution (session_id, performed_at, duration_min, actual_json, rpe_cr10, feel_1_5, deviation_reason, notes, source, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'web\', ?, ?)
            ON DUPLICATE KEY UPDATE performed_at = VALUES(performed_at), duration_min = VALUES(duration_min), actual_json = VALUES(actual_json),
                rpe_cr10 = VALUES(rpe_cr10), feel_1_5 = VALUES(feel_1_5), deviation_reason = VALUES(deviation_reason), notes = VALUES(notes),
                source = \'web\', updated_at = VALUES(updated_at)')
            ->execute([$sessionId, $e['performed_at'], $e['duration_min'], $actual, $e['rpe_cr10'], $e['feel_1_5'], $e['deviation_reason'], $e['notes'], $now, $now]);
        $stmt = $this->pdo->prepare('SELECT id FROM session_execution WHERE session_id = ?');
        $stmt->execute([$sessionId]);

        return (int) $stmt->fetchColumn();
    }

    /** @param array{date: string, session_id: ?int, location: string, side: string, intensity_0_10: int, timing: string, notes: ?string} $p */
    public function addPain(array $p): int
    {
        $this->pdo->prepare('INSERT INTO pain_event (date, session_id, location, side, intensity_0_10, timing, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$p['date'], $p['session_id'], $p['location'], $p['side'], $p['intensity_0_10'], $p['timing'], $p['notes'], Db::ts($this->clock->now())]);

        return (int) $this->pdo->lastInsertId();
    }

    /** Anzahl Meldungen an einem Ort in den 14 Tagen bis einschließlich $date. */
    public function painCount14(string $location, string $date): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM pain_event WHERE location = ? AND date BETWEEN DATE_SUB(?, INTERVAL 13 DAY) AND ?');
        $stmt->execute([$location, $date, $date]);

        return (int) $stmt->fetchColumn();
    }

    /** @return list<array<string, mixed>> Schmerzereignisse im Zeitraum */
    public function pains(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pain_event WHERE date BETWEEN ? AND ? ORDER BY date, id');
        $stmt->execute([$from, $to]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function checkin(string $date): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM checkin WHERE date = ?');
        $stmt->execute([$date]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<string, array<string, mixed>> Check-ins im Zeitraum, Schlüssel = Datum */
    public function checkins(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM checkin WHERE date BETWEEN ? AND ? ORDER BY date DESC');
        $stmt->execute([$from, $to]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['date']] = $row;
        }

        return $out;
    }

    public function saveCheckin(string $date, int $recovery, int $soreness, bool $pain, ?string $notes): int
    {
        $now = Db::ts($this->clock->now());
        $this->pdo->prepare('INSERT INTO checkin (date, recovery_1_5, soreness_1_5, pain_flag, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE recovery_1_5 = VALUES(recovery_1_5), soreness_1_5 = VALUES(soreness_1_5), pain_flag = VALUES(pain_flag), notes = VALUES(notes), updated_at = VALUES(updated_at)')
            ->execute([$date, $recovery, $soreness, $pain ? 1 : 0, $notes, $now, $now]);
        $stmt = $this->pdo->prepare('SELECT id FROM checkin WHERE date = ?');
        $stmt->execute([$date]);

        return (int) $stmt->fetchColumn();
    }
}
