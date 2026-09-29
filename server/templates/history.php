<?php
/** @var \Training\View\View $this */
/** @var array<string, array> $weeks */
/** @var string $current */
/** @var int $axis */
/** @var array<string, array> $heat */
/** @var array $tiles */
use Training\Controller\HistoryController;

$first = reset($weeks);
$last = end($weeks);
$fmt = static fn (int $n): string => number_format($n, 0, ',', ' ');
$h = static fn (int $v) => (int) (round(min(100, 100 * $v / $axis) / 5) * 5);
?>
<div class="stack-lg">
  <div class="page-head">
    <div class="eyebrow">Letzte <?= count($weeks) ?> Wochen · KW <?= (int) $first['kw'] ?>–<?= (int) $last['kw'] ?></div>
    <h1>Verlauf</h1>
  </div>

  <div class="tiles">
    <div class="stat"><div class="l">sRPE diese Woche</div><div class="v"><?= $fmt($tiles['srpe_now']) ?> <small>bis <?= $this->e($weekday) ?></small></div></div>
    <div class="stat"><div class="l">sRPE Vorwoche</div><div class="v"><?= $fmt($tiles['srpe_prev']) ?></div></div>
    <div class="stat"><div class="l">Check-in-Abdeckung</div><div class="v"><?= (int) $tiles['checkin_pct'] ?> <small>%</small></div></div>
    <div class="stat"><div class="l">Schmerzereignisse</div><div class="v"><?= (int) $tiles['pains'] ?> <small>in <?= count($weeks) ?> Wochen</small></div></div>
  </div>

  <section>
    <div class="section-title"><h2>Wochenlast (sRPE) je Bereich</h2><span class="hint">gleiche Achse, 0 – <?= $fmt($axis) ?></span></div>
    <div class="card">
      <div class="multiples">
<?php foreach (HistoryController::AREAS as $key => [$label, $icon]):
    $values = array_map(static fn (array $w): int => $w['load'][$key], $weeks);
    $avg = (int) round(array_sum($values) / count($values));
?>
        <div class="chart">
          <div class="title"><?= $this->icon($icon, 'ic ic-sm ic-brand') ?><b><?= $this->e($label) ?></b><span class="muted">Ø <?= $fmt($avg) ?></span></div>
          <div class="bars"><?php foreach ($weeks as $monday => $w): $v = $w['load'][$key]; ?><i class="h<?= $h($v) ?><?= $monday === $current ? ' cur' : '' ?>" data-v="<?= $v ?>"></i><?php endforeach ?></div>
          <div class="xaxis"><?php foreach ($weeks as $w): ?><span><?= (int) $w['kw'] ?></span><?php endforeach ?></div>
          <div class="end">KW <?= (int) $last['kw'] ?>: <?= $fmt(end($values)) ?></div>
        </div>
<?php endforeach ?>
      </div>
      <p class="hint mt-4">Die laufende Woche (dunkler) ist unvollständig. Gezählt werden Einheiten mit Rückmeldung (RPE × Dauer). Alle Werte in der <a href="#tabelle">Tabelle</a>.</p>
    </div>
  </section>

  <section>
    <div class="section-title"><h2>Schmerz je Ort</h2><span class="hint">stärkste Meldung der Woche, 0–10</span></div>
    <div class="card">
<?php if ($heat === []): ?>
      <p class="muted small">Keine Schmerzereignisse in diesem Zeitraum.</p>
<?php else: ?>
      <div class="heat-scroll">
      <div class="heat" role="table" aria-label="Schmerz je Ort und Woche">
        <span></span><?php foreach ($weeks as $w): ?><span class="ch"><?= (int) $w['kw'] ?></span><?php endforeach ?>
<?php foreach ($heat as $label => $cells): ?>
        <span class="rl"><?= $this->e($label) ?></span><?php foreach ($cells as $c): ?><?php if ($c === null): ?><span class="c"></span><?php else: ?><span class="c" data-i="<?= max(1, (int) $c['i']) ?>" data-t="<?= $this->e($c['t']) ?>" title="<?= $this->e($c['t']) ?>"></span><?php endif ?><?php endforeach ?>

<?php endforeach ?>
      </div>
      </div>
      <div class="heat-scale"><span>0</span><i class="hs0"></i><i class="hs1"></i><i class="hs2"></i><i class="hs3"></i><i class="hs4"></i><i class="hs5"></i><span>10</span></div>
<?php endif ?>
    </div>
  </section>

  <section id="tabelle">
    <div class="section-title"><h2>Tabelle</h2><span class="hint">alle Werte, auch für Kopie und Druck</span></div>
    <div class="card table-scroll">
      <table class="plan-table mono table-wide">
        <thead><tr><th>KW</th><th>Ausdauer</th><th>Klettern</th><th>Kraft</th><th>Haltung</th><th>Summe</th><th>Check-in</th><th>Schmerz</th></tr></thead>
        <tbody>
<?php foreach ($weeks as $w): ?>
          <tr><td><?= (int) $w['kw'] ?></td><?php foreach (array_keys(HistoryController::AREAS) as $k): ?><td><?= $fmt($w['load'][$k]) ?></td><?php endforeach ?><td><?= $fmt(array_sum($w['load'])) ?></td><td><?= (int) $w['checkins'] ?>/<?= (int) $w['days'] ?></td><td><?= (int) $w['pains'] ?></td></tr>
<?php endforeach ?>
        </tbody>
      </table>
    </div>
  </section>

  <section id="bloecke">
    <div class="section-title"><h2>Blöcke</h2><span class="hint">Zielklärung, Revisionen, Bilanz</span></div>
<?php if (empty($blocks)): ?>
    <div class="card"><p class="muted">Noch kein Trainingsblock – er entsteht im Projekt-Chat.</p></div>
<?php else: ?>
    <div class="card list">
<?php foreach ($blocks as $b): [$bl, $bc] = \Training\View\Labels::BLOCK_STATUS[$b['status']]; ?>
      <a class="list-item link-row" href="/block?id=<?= (int) $b['id'] ?>"><div><div class="t"><?= $this->e($b['name']) ?></div><div class="s"><?= $this->e((new DateTimeImmutable((string) $b['start_date']))->format('d.m.Y')) ?> – <?= $this->e((new DateTimeImmutable((string) $b['end_date']))->format('d.m.Y')) ?><?= $b['faellig'] !== [] ? ' · fällig: ' . $this->e(implode(', ', $b['faellig'])) : '' ?></div></div><span class="badge badge-<?= $bc ?>"><?= $this->e($bl) ?></span></a>
<?php endforeach ?>
    </div>
<?php endif ?>
  </section>
</div>
