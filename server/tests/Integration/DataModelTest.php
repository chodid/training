<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use PDO;
use PDOException;
use Training\App;
use Training\Db;
use Training\Migration\Migrator;
use Training\Plan\PlanValidator;
use Training\Tests\Support\AppTestCase;

/** Trainingstabellen (AP-03): Beispielwoche mit allen Typen, Constraints, berechnete Last, Löschverhalten. */
final class DataModelTest extends AppTestCase
{
    public function testMigrationsAreIdempotent(): void
    {
        $m = new Migrator($this->pdo, dirname(__DIR__, 2) . '/migrations');
        self::assertSame(App::SCHEMA_VERSION, $m->currentVersion());
        self::assertSame([], $m->migrate());
        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach (['training_block', 'training_week', 'session', 'session_execution', 'pain_event', 'checkin', 'audit_log', 'ext_cache', 'user', 'oauth_token'] as $t) {
            self::assertContains($t, $tables);
        }
    }

    public function testExampleWeekWithAllTypesCanBeInserted(): void
    {
        $ids = $this->insertExampleWeek();
        self::assertCount(7, $ids);
        $types = $this->pdo->query('SELECT DISTINCT type FROM `session` ORDER BY type')->fetchAll(PDO::FETCH_COLUMN);
        self::assertEqualsCanonicalizing(PlanValidator::TYPES, $types);

        $plan = json_decode((string) $this->pdo->query("SELECT plan_json FROM `session` WHERE type = 'klettern'")->fetchColumn(), true);
        self::assertSame(20, $plan['blocks'][0]['edge_mm']);
        self::assertNull($this->pdo->query("SELECT plan_json FROM `session` WHERE type = 'ruhe'")->fetchColumn());
    }

    public function testSrpeLoadIsComputedAndOneExecutionPerSession(): void
    {
        $ids = $this->insertExampleWeek();
        $now = Db::ts($this->clock->now());
        $this->pdo->prepare("INSERT INTO session_execution (session_id, performed_at, duration_min, rpe_cr10, feel_1_5, source, created_at, updated_at) VALUES (?, ?, 55, 6, 2, 'web', ?, ?)")
            ->execute([$ids[0], $now, $now, $now]);
        self::assertSame(330, (int) $this->pdo->query('SELECT srpe_load FROM session_execution')->fetchColumn());

        $this->expectPdoError(fn () => $this->pdo->prepare("INSERT INTO session_execution (session_id, source, created_at, updated_at) VALUES (?, 'web', ?, ?)")->execute([$ids[0], $now, $now]));
        $this->expectPdoError(fn () => $this->pdo->exec("UPDATE session_execution SET srpe_load = 1"));
        $this->expectPdoError(fn () => $this->pdo->exec("UPDATE session_execution SET rpe_cr10 = 11"));
        $this->expectPdoError(fn () => $this->pdo->exec("UPDATE session_execution SET feel_1_5 = 0"));
    }

    public function testEnumsAndRangesAreEnforced(): void
    {
        $ids = $this->insertExampleWeek();
        $now = Db::ts($this->clock->now());
        $pain = $this->pdo->prepare('INSERT INTO pain_event (date, session_id, location, side, intensity_0_10, timing, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $pain->execute(['2026-09-23', $ids[2], 'finger_ringband', 'L', 3, 'danach', $now]);
        $pain->execute(['2026-09-23', null, 'ellbogen_medial', 'R', 2, 'naechster_morgen', $now]);
        $this->expectPdoError(fn () => $pain->execute(['2026-09-23', null, 'zeh', 'L', 3, 'danach', $now]));
        $this->expectPdoError(fn () => $pain->execute(['2026-09-23', null, 'knie', 'L', 11, 'danach', $now]));
        $this->expectPdoError(fn () => $pain->execute(['2026-09-23', null, 'knie', 'X', 1, 'danach', $now]));

        $checkin = $this->pdo->prepare('INSERT INTO checkin (date, recovery_1_5, soreness_1_5, pain_flag, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
        $checkin->execute(['2026-09-23', 2, 3, 1, $now, $now]);
        $this->expectPdoError(fn () => $checkin->execute(['2026-09-23', 2, 3, 0, $now, $now]));
        $this->expectPdoError(fn () => $checkin->execute(['2026-09-24', 6, 3, 0, $now, $now]));
        $this->expectPdoError(fn () => $this->pdo->exec("UPDATE `session` SET type = 'yoga' WHERE id = " . $ids[0]));
        $this->expectPdoError(fn () => $this->pdo->exec("UPDATE `session` SET status = 'fertig' WHERE id = " . $ids[0]));
        $this->expectPdoError(fn () => $this->pdo->exec("UPDATE training_block SET end_date = '2026-01-01'"));
    }

    public function testDeletingWeekCascadesButKeepsPainEvents(): void
    {
        $ids = $this->insertExampleWeek();
        $now = Db::ts($this->clock->now());
        $this->pdo->prepare("INSERT INTO session_execution (session_id, duration_min, rpe_cr10, source, created_at, updated_at) VALUES (?, 60, 5, 'web', ?, ?)")->execute([$ids[0], $now, $now]);
        $this->pdo->prepare("INSERT INTO pain_event (date, session_id, location, side, intensity_0_10, timing, created_at) VALUES ('2026-09-21', ?, 'knie', 'L', 2, 'waehrend', ?)")->execute([$ids[0], $now]);

        $this->expectPdoError(fn () => $this->pdo->exec('DELETE FROM training_block'));
        $this->pdo->exec('DELETE FROM training_week');
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM `session`')->fetchColumn());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM session_execution')->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM pain_event WHERE session_id IS NULL')->fetchColumn(), 'Schmerzereignis bleibt erhalten');
    }

    public function testInvalidJsonIsRejectedByDatabase(): void
    {
        $ids = $this->insertExampleWeek();
        $this->expectPdoError(fn () => $this->pdo->exec("UPDATE `session` SET plan_json = '{kaputt' WHERE id = " . $ids[0]));
    }

    /** @return list<int> Session-IDs in Reihenfolge der Fixture */
    private function insertExampleWeek(): array
    {
        $data = json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/beispielwoche.json'), true);
        $validator = PlanValidator::default();
        $now = Db::ts($this->clock->now());
        $b = $data['block'];
        $this->pdo->prepare('INSERT INTO training_block (name, start_date, end_date, status, doc_ref, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$b['name'], $b['start_date'], $b['end_date'], $b['status'], $b['doc_ref'], $now, $now]);
        $blockId = (int) $this->pdo->lastInsertId();
        $w = $data['week'];
        $this->pdo->prepare('INSERT INTO training_week (block_id, week_start, focus, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$blockId, $w['week_start'], $w['focus'], $w['status'], $w['created_by'], $now, $now]);
        $weekId = (int) $this->pdo->lastInsertId();

        $insert = $this->pdo->prepare('INSERT INTO `session` (week_id, date, type, title, priority, planned_duration_min, intervals_event_id, plan_json, status, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $ids = [];
        foreach ($data['sessions'] as $i => $s) {
            self::assertSame([], $validator->validatePlan($s['type'], $s['plan_json']));
            $insert->execute([$weekId, $s['date'], $s['type'], $s['title'], $s['priority'], $s['planned_duration_min'], $s['intervals_event_id'] ?? null,
                $s['plan_json'] === null ? null : json_encode($s['plan_json'], JSON_UNESCAPED_UNICODE), 'geplant', $i, $now, $now]);
            $ids[] = (int) $this->pdo->lastInsertId();
        }

        return $ids;
    }

    private function expectPdoError(callable $fn): void
    {
        try {
            $fn();
        } catch (PDOException) {
            self::assertTrue(true);

            return;
        }
        self::fail('PDOException erwartet');
    }
}
