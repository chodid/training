<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var string $date */
/** @var array $data */
/** @var array $invalid */
/** @var string $csrf */
use Training\Dates;
use Training\View\Labels;
?>
  <div class="page-head">
    <div class="eyebrow" data-date-label><?= $this->e(Dates::long($date, true)) ?><?= $date === $today ? ' · heute' : '' ?></div>
    <h1><?= $date === $today ? 'Wie geht es Dir heute?' : 'Wie ging es Dir an diesem Tag?' ?></h1>
    <p class="muted small">Vor dem Frühstück: Morgentest, Erholung, Muskelkater – dann ist der Tag erfasst. Fehlende Tage zählen als fehlend, nicht als beschwerdefrei.<?= $exists ? ' Für diesen Tag gibt es schon einen Eintrag; Speichern überschreibt ihn.' : '' ?></p>
  </div>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>

<?php if (!empty($summary)) { $editHref = null; include __DIR__ . '/_morning_summary.php'; } ?>
  <div class="mt-4">
<?php include __DIR__ . '/_checkin_form.php'; ?>
  </div>

  <section class="mt-8x">
    <div class="section-title"><h2>Diese Woche</h2><span class="hint"><?= count($week) ?> von <?= $elapsed ?> Tagen</span></div>
<?php if ($week === []): ?>
    <p class="muted small">Noch keine Einträge in dieser Woche.</p>
<?php else: ?>
    <div class="card list">
<?php foreach ($week as $d => $c):
    $painText = 'kein Schmerz';
    if ((int) $c['pain_flag'] === 1) {
        $list = array_map(static fn (array $p): string => trim(Labels::LOCATIONS[$p['location']] . ' ' . Labels::SIDES_SHORT[$p['side']]) . ' ' . $p['intensity_0_10'], $pains[$d] ?? []);
        $painText = 'Schmerz' . ($list !== [] ? ' ' . implode(', ', $list) : '');
    }
?>
      <a class="list-item link-row" href="/checkin?datum=<?= $this->e($d) ?>"><div><div class="t"><?= $this->e(Dates::long($d)) ?></div><div class="s"><?= $c['mt_links'] !== null || $c['mt_rechts'] !== null ? 'Morgentest L ' . ($c['mt_links'] ?? '–') . ' / R ' . ($c['mt_rechts'] ?? '–') . ' · ' : '' ?>Erholung <?= (int) $c['recovery_1_5'] ?> · Muskelkater <?= (int) $c['soreness_1_5'] ?> · <?= $this->e($painText) ?></div></div><span class="badge badge-success"><?= $this->icon('check') ?>erfasst</span></a>
<?php endforeach ?>
    </div>
<?php endif ?>
  </section>
