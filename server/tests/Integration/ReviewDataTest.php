<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use PDOException;
use Training\Data\ReviewRepository;
use Training\Db;
use Training\Review\Faelligkeit;
use Training\Review\Kennzahlen;
use Training\Tests\Support\AppTestCase;

/** AP-15 T1: Tabelle block_review, Fassungslogik, Kennzahlen aus der Beispielwoche, Fälligkeit aus der Datenbank. */
final class ReviewDataTest extends AppTestCase
{
    private const NOW = 1790596800; // Mo 2026-09-28 12:00 UTC

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
    }

    public function testMigrationBackAndForthKeepsData(): void
    {
        $blockId = $this->block('aktiv', '2026-09-21', '2026-12-13');
        $this->rollbackLastMigration(); // Rückweg 0024: DROP TABLE block_review
        self::assertSame([], $this->pdo->query("SHOW TABLES LIKE 'block_review'")->fetchAll());
        self::assertSame('block_ohne_zielklaerung', Faelligkeit::load($this->pdo, $this->clock, '2026-09-28')[0]['grund'], 'Fälligkeit ohne Tabelle ohne Fehler');
        $applied = (new \Training\Migration\Migrator($this->pdo, dirname(__DIR__, 2) . '/migrations'))->migrate();
        self::assertSame([24], array_column($applied, 'version'));
        self::assertSame('Herbst', $this->pdo->query('SELECT name FROM training_block WHERE id = ' . $blockId)->fetchColumn());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM block_review')->fetchColumn());
    }

    public function testConstraintsAndDeleteProtection(): void
    {
        $blockId = $this->block('aktiv', '2026-09-21', '2026-12-13');
        $repo = new ReviewRepository($this->pdo, $this->clock);
        $repo->insert($this->row($blockId, 'bilanz', 1, 1, 'entwurf'));
        foreach ([
            fn () => $repo->insert($this->row($blockId, 'bilanz', 2, 1, 'entwurf')),       // Bilanz nur sequence 1
            fn () => $repo->insert($this->row($blockId, 'zielklaerung', 2, 1, 'entwurf')), // Zielklärung nur sequence 1
            fn () => $repo->insert($this->row($blockId, 'bilanz', 1, 1, 'entwurf')),       // Fassung doppelt
            fn () => $repo->insert(['period_start' => '2026-10-10', 'period_end' => '2026-10-01'] + $this->row($blockId, 'revision', 1, 1, 'entwurf')),
            fn () => $this->pdo->exec('DELETE FROM training_block WHERE id = ' . $blockId), // Block mit Reviews
        ] as $i => $fn) {
            try {
                $fn();
                self::fail('Fall ' . $i . ' hätte abgelehnt werden müssen');
            } catch (PDOException) {
                self::assertTrue(true);
            }
        }
    }

    public function testVersionsCurrentAndConfirmedKeys(): void
    {
        $blockId = $this->block('aktiv', '2026-09-21', '2026-12-13');
        $repo = new ReviewRepository($this->pdo, $this->clock);
        self::assertSame(1, $repo->nextVersion($blockId, 'bilanz', 1));
        $repo->insert($this->row($blockId, 'bilanz', 1, 1, 'entwurf'));
        // F-02: nur ein Entwurf → nicht bestätigt, Bilanz bleibt fällig
        self::assertSame([], $repo->confirmedKeys());
        self::assertSame('entwurf', $repo->current($blockId, 'bilanz')[0]['status']);
        self::assertSame([], $repo->current($blockId, 'bilanz', true));

        $repo->insert($this->row($blockId, 'bilanz', 1, 2, 'bestaetigt') + ['reason' => 'bestätigt']);
        $repo->insert($this->row($blockId, 'bilanz', 1, 3, 'entwurf') + ['reason' => 'Test nachgetragen']);
        self::assertSame(4, $repo->nextVersion($blockId, 'bilanz', 1));
        $current = $repo->current($blockId, 'bilanz');
        self::assertCount(1, $current);
        self::assertSame(2, $current[0]['version']);
        self::assertSame(3, $current[0]['entwurf']['version']);
        self::assertSame(3, $current[0]['fassungen']);
        self::assertNotNull($current[0]['confirmed_at']);
        self::assertSame([['block_id' => $blockId, 'kind' => 'bilanz', 'sequence' => 1, 'review_date' => '2026-09-28']], $repo->confirmedKeys());

        // Revisionen: fortlaufende Nummer
        self::assertSame(1, $repo->nextSequence($blockId));
        $repo->insert($this->row($blockId, 'revision', 1, 1, 'bestaetigt'));
        self::assertSame(2, $repo->nextSequence($blockId));
        self::assertCount(4, $repo->versions($blockId));
        self::assertSame(['revision', 'bilanz', 'bilanz', 'bilanz'], array_column($repo->versions($blockId), 'kind'));
        self::assertSame('revision', $repo->latestConfirmed('revision')['kind']);

        // Fälligkeit aus der Datenbank: Bilanz bestätigt, Zielklärung fehlt
        $f = Faelligkeit::load($this->pdo, $this->clock, '2026-09-28');
        self::assertSame(['zielklaerung'], array_column($f, 'kind'));
        self::assertSame('block_ohne_zielklaerung', $f[0]['grund']);
    }

    public function testKennzahlenAusBeispielwoche(): void
    {
        $this->insertExampleWeekWithExecutions();
        $k = (new Kennzahlen($this->pdo, $this->clock, 'Europe/Berlin'))->compute('2026-09-21', '2026-09-27');
        self::assertSame(1, $k['wochen']);
        self::assertSame(['geplant' => 2, 'erledigt' => 2, 'teilweise' => 0, 'ausgelassen' => 0, 'verschoben' => 0], $k['plan_erfuellung']['ausdauer']);
        self::assertSame(['geplant' => 1, 'erledigt' => 0, 'teilweise' => 1, 'ausgelassen' => 0, 'verschoben' => 0], $k['plan_erfuellung']['klettern']);
        self::assertSame(1, $k['plan_erfuellung']['haltung']['ausgelassen']);
        self::assertSame(1, $k['plan_erfuellung']['mobilitaet']['verschoben']);
        self::assertSame(1480, $k['last']['srpe_summe']);
        self::assertSame([1480], $k['last']['srpe_je_woche']);
        self::assertSame(['ausdauer' => 630, 'kraft' => 360, 'klettern' => 490, 'haltung' => 0, 'mobilitaet' => 0], $k['last']['srpe_je_typ']);
        self::assertSame([18.5], $k['ausdauer']['km_je_woche']);
        self::assertSame([760], $k['ausdauer']['hm_je_woche']);
        self::assertSame(['z1' => 25, 'z2' => 60, 'z3' => 10, 'z4' => 20, 'z5' => 0], $k['ausdauer']['zeit_zone_min']);
        self::assertSame([
            ['ort' => 'knie', 'max' => 4, 'mittel' => 2.7, 'anzahl' => 3, 'trend' => 'steigend'],
            ['ort' => 'finger_ringband', 'max' => 3, 'mittel' => 3.0, 'anzahl' => 1, 'trend' => 'fallend'],
        ], $k['schmerz']['je_ort']);
        self::assertSame(['links_mittel' => 1.3, 'rechts_mittel' => 3.5, 'rot_tage' => 1], $k['morgentest']);
        self::assertSame(57, $k['checkin_abdeckung_prozent']);
        self::assertSame(['hrv_mittel' => 60.0, 'ruhepuls_mittel' => 48.0, 'schlaf_h_mittel' => 7.1], $k['wellness']);
        self::assertSame('2026-09-28T12:00:00Z', $k['berechnet_am']);

        // Fehlende Quellen: null, kein Fehler
        $empty = (new Kennzahlen($this->pdo, $this->clock, 'Europe/Berlin'))->compute('2026-06-01', '2026-06-14');
        self::assertSame(2, $empty['wochen']);
        self::assertNull($empty['ausdauer']);
        self::assertNull($empty['wellness']);
        self::assertSame([], $empty['schmerz']['je_ort']);
        self::assertSame(['links_mittel' => null, 'rechts_mittel' => null, 'rot_tage' => 0], $empty['morgentest']);
    }

    private function insertExampleWeekWithExecutions(): void
    {
        $data = json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/beispielwoche.json'), true);
        $now = Db::ts($this->clock->now());
        $b = $data['block'];
        $blockId = $this->block($b['status'], $b['start_date'], $b['end_date']);
        $this->pdo->prepare("INSERT INTO training_week (block_id, week_start, focus, status, created_by, created_at, updated_at) VALUES (?, ?, ?, 'bestaetigt', 'mcp', ?, ?)")
            ->execute([$blockId, $data['week']['week_start'], $data['week']['focus'], $now, $now]);
        $weekId = (int) $this->pdo->lastInsertId();
        $ids = [];
        foreach ($data['sessions'] as $i => $s) {
            $this->pdo->prepare("INSERT INTO `session` (week_id, date, type, title, priority, planned_duration_min, plan_json, status, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'geplant', ?, ?, ?)")
                ->execute([$weekId, $s['date'], $s['type'], $s['title'], $s['priority'], $s['planned_duration_min'], $s['plan_json'] === null ? null : json_encode($s['plan_json']), $i, $now, $now]);
            $ids[] = (int) $this->pdo->lastInsertId();
        }
        foreach ($data['ausfuehrungen'] as $e) {
            $this->pdo->prepare('UPDATE `session` SET status = ? WHERE id = ?')->execute([$e['status'], $ids[$e['session']]]);
            if (isset($e['rpe_cr10'])) {
                $this->pdo->prepare("INSERT INTO session_execution (session_id, duration_min, rpe_cr10, source, created_at, updated_at) VALUES (?, ?, ?, 'web', ?, ?)")
                    ->execute([$ids[$e['session']], $e['duration_min'], $e['rpe_cr10'], $now, $now]);
            }
        }
        foreach ($data['schmerz'] as $p) {
            $this->pdo->prepare('INSERT INTO pain_event (date, location, side, intensity_0_10, timing, created_at) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$p['date'], $p['location'], $p['side'], $p['intensity_0_10'], $p['timing'], $now]);
        }
        foreach ($data['checkins'] as $c) {
            $this->pdo->prepare('INSERT INTO checkin (date, recovery_1_5, soreness_1_5, pain_flag, mt_links, mt_rechts, created_at, updated_at) VALUES (?, ?, ?, 0, ?, ?, ?, ?)')
                ->execute([$c['date'], $c['recovery_1_5'], $c['soreness_1_5'], $c['mt_links'], $c['mt_rechts'], $now, $now]);
        }
        foreach ($data['aktivitaeten'] as $a) {
            $this->pdo->prepare('INSERT INTO ext_activity (id, date, start_date_local, type, data_json, updated_at) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$a['id'], substr($a['start_date_local'], 0, 10), str_replace('T', ' ', $a['start_date_local']), $a['type'], json_encode($a), $now]);
        }
        foreach ($data['wellness'] as $w) {
            $this->pdo->prepare('INSERT INTO ext_wellness (date, data_json, updated_at) VALUES (?, ?, ?)')->execute([$w['id'], json_encode($w), $now]);
        }
    }

    private function block(string $status, string $start, string $end): int
    {
        $now = Db::ts($this->clock->now());
        $this->pdo->prepare('INSERT INTO training_block (name, start_date, end_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute(['Herbst', $start, $end, $status, $now, $now]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed> */
    private function row(int $blockId, string $kind, int $sequence, int $version, string $status): array
    {
        return ['block_id' => $blockId, 'kind' => $kind, 'sequence' => $sequence, 'version' => $version, 'status' => $status, 'review_date' => '2026-09-28',
            'period_start' => null, 'period_end' => null, 'summary' => $kind . ' v' . $version, 'content' => ['x' => 1], 'kennzahlen' => null, 'reason' => null, 'created_by' => 'mcp'];
    }
}
