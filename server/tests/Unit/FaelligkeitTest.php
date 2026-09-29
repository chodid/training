<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Review\Faelligkeit;

/** AP-15 T1: Fälligkeit (docs/konzept/blockbilanz.md 5.1, Testfälle F-01 bis F-10 in 11.1; heute = 2026-10-01). */
final class FaelligkeitTest extends TestCase
{
    private const TODAY = '2026-10-01';

    public function testF01BilanzFaelligAbVorlauf(): void
    {
        $f = $this->compute([$this->block(1, 'aktiv', '2026-08-10', '2026-10-05')], [$this->zk(1, '2026-09-10')]);
        self::assertSame(['kind' => 'bilanz', 'block_id' => 1, 'block_name' => 'Block 1', 'grund' => 'blockende', 'seit' => '2026-09-28', 'faellig_am' => '2026-10-05'], $this->find($f, 'bilanz'));
        // Vorlauf 3 Tage: erst ab 2026-10-02
        self::assertNull($this->find(Faelligkeit::compute([$this->block(1, 'aktiv', '2026-08-10', '2026-10-05')], [$this->zk(1, '2026-09-10')], self::TODAY, 3), 'bilanz'));
    }

    public function testF02EntwurfZaehltNicht(): void
    {
        // Entwürfe erreichen compute() nicht (ReviewRepository::confirmedKeys liefert nur bestätigte, geprüft in ReviewDataTest)
        $f = $this->compute([$this->block(1, 'aktiv', '2026-08-10', '2026-10-05')], [$this->zk(1, '2026-09-10')]);
        self::assertSame('blockende', $this->find($f, 'bilanz')['grund']);
    }

    public function testF03BestaetigteBilanz(): void
    {
        $f = $this->compute([$this->block(1, 'aktiv', '2026-08-10', '2026-10-05')], [$this->zk(1, '2026-09-10'), ['block_id' => 1, 'kind' => 'bilanz', 'review_date' => '2026-09-30']]);
        self::assertNull($this->find($f, 'bilanz'));
    }

    public function testF04AbgeschlossenOhneBilanz(): void
    {
        $f = $this->compute([$this->block(1, 'abgeschlossen', '2026-07-01', '2026-09-20')], []);
        $b = $this->find($f, 'bilanz');
        self::assertSame('block_abgeschlossen_ohne_bilanz', $b['grund']);
        self::assertSame(1, $b['block_id']);
        self::assertSame('2026-09-20', $b['seit']);

        // Nur der zuletzt beendete abgeschlossene Block meldet sich, ältere nicht mehr
        $f = $this->compute([$this->block(1, 'abgeschlossen', '2026-03-01', '2026-06-30'), $this->block(2, 'abgeschlossen', '2026-07-01', '2026-09-20'), $this->block(3, 'aktiv', '2026-09-21', '2026-12-13')], [$this->zk(3, '2026-09-20')]);
        self::assertSame([2], array_column(array_filter($f, static fn (array $x): bool => $x['kind'] === 'bilanz'), 'block_id'));
    }

    public function testF05FolgeblockOhneZielklaerung(): void
    {
        $f = $this->compute([$this->block(1, 'aktiv', '2026-08-01', '2026-10-12')], [$this->zk(1, '2026-08-01'), $this->rev(1, '2026-09-20')]);
        $z = $this->find($f, 'zielklaerung');
        self::assertSame('folgeblock_ohne_zielklaerung', $z['grund']);
        self::assertSame(1, $z['block_id']);
        self::assertSame('2026-09-28', $z['seit']);
        self::assertSame('2026-10-12', $z['faellig_am']);

        // Folgeblock ohne Zielklärung wird genannt
        $f = $this->compute([$this->block(1, 'aktiv', '2026-08-01', '2026-10-12'), $this->block(2, 'geplant', '2026-10-13', '2026-12-31')], [$this->zk(1, '2026-08-01')]);
        self::assertSame(2, $this->find($f, 'zielklaerung')['folgeblock_id']);
    }

    public function testF06FolgeblockMitZielklaerung(): void
    {
        $f = $this->compute([$this->block(1, 'aktiv', '2026-08-01', '2026-10-12'), $this->block(2, 'geplant', '2026-10-13', '2026-12-31')], [$this->zk(1, '2026-08-01'), $this->zk(2, '2026-09-30')]);
        self::assertNull($this->find($f, 'zielklaerung'));
    }

    public function testF07AktiverBlockOhneZielklaerung(): void
    {
        $f = $this->compute([$this->block(1, 'aktiv', '2026-09-21', '2026-12-13')], []);
        $z = $this->find($f, 'zielklaerung');
        self::assertSame('block_ohne_zielklaerung', $z['grund']);
        self::assertSame(1, $z['block_id']);
        self::assertNull($this->find($f, 'bilanz'));
    }

    public function testF08ZielklaerungAelter16Wochen(): void
    {
        $f = $this->compute([$this->block(1, 'aktiv', '2026-05-01', '2026-12-31')], [$this->zk(1, '2026-05-01')]);
        $z = $this->find($f, 'zielklaerung');
        self::assertSame('zielklaerung_aelter_16_wochen', $z['grund']);
        self::assertSame('2026-08-21', $z['seit']);
    }

    public function testF09RevisionTurnus(): void
    {
        $f = $this->compute([$this->block(1, 'aktiv', '2026-07-01', '2026-12-31')], [$this->zk(1, '2026-07-01'), $this->rev(1, '2026-08-30')]);
        $r = $this->find($f, 'revision');
        self::assertSame('revision_turnus', $r['grund']);
        self::assertSame('2026-09-27', $r['seit']);

        // Jüngster Datensatz vor weniger als 28 Tagen: nicht fällig
        self::assertNull($this->find($this->compute([$this->block(1, 'aktiv', '2026-07-01', '2026-12-31')], [$this->zk(1, '2026-07-01'), $this->rev(1, '2026-09-10')]), 'revision'));
    }

    public function testF10KeinAktiverBlock(): void
    {
        $f = $this->compute([], []);
        self::assertCount(1, $f);
        self::assertSame(['kind' => 'zielklaerung', 'block_id' => null, 'block_name' => null, 'grund' => 'block_ohne_zielklaerung', 'seit' => self::TODAY, 'faellig_am' => self::TODAY], $f[0]);

        // Geplanter Block mit Zielklärung, noch nicht aktiv: nichts fällig
        self::assertSame([], $this->compute([$this->block(2, 'geplant', '2026-10-05', '2026-12-31')], [$this->zk(2, '2026-09-30')]));
    }

    public function testTextAndForBlock(): void
    {
        $f = $this->compute([$this->block(1, 'aktiv', '2026-08-10', '2026-10-05')], [$this->zk(1, '2026-09-10')]);
        self::assertSame('Block „Block 1“ endet am 05.10.', Faelligkeit::text($this->find($f, 'bilanz')));
        self::assertCount(count($f), Faelligkeit::forBlock($f, 1));
        self::assertSame([], Faelligkeit::forBlock($f, 2));
    }

    /** @param list<array<string, mixed>> $blocks @param list<array<string, mixed>> $confirmed @return list<array<string, mixed>> */
    private function compute(array $blocks, array $confirmed): array
    {
        return Faelligkeit::compute($blocks, $confirmed, self::TODAY);
    }

    /** @param list<array<string, mixed>> $f @return array<string, mixed>|null */
    private function find(array $f, string $kind): ?array
    {
        foreach ($f as $x) {
            if ($x['kind'] === $kind) {
                return $x;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function block(int $id, string $status, string $start, string $end): array
    {
        return ['id' => $id, 'name' => 'Block ' . $id, 'start_date' => $start, 'end_date' => $end, 'status' => $status];
    }

    /** @return array<string, mixed> */
    private function zk(int $blockId, string $date): array
    {
        return ['block_id' => $blockId, 'kind' => 'zielklaerung', 'review_date' => $date];
    }

    /** @return array<string, mixed> */
    private function rev(int $blockId, string $date): array
    {
        return ['block_id' => $blockId, 'kind' => 'revision', 'review_date' => $date];
    }
}
