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
    <p class="muted small">Drei Angaben, dann ist der Tag erfasst. Fehlende Tage zählen als fehlend, nicht als beschwerdefrei.<?= $exists ? ' Für diesen Tag gibt es schon einen Eintrag; Speichern überschreibt ihn.' : '' ?></p>
  </div>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>

  <form class="stack-lg mt-4" method="post" action="/checkin" data-offline-form>
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="datum" value="<?= $this->e($date) ?>">
    <input type="hidden" name="stand" value="<?= $this->e($stand) ?>">
    <input type="hidden" name="offline_label" value="<?= $this->e($offlineLabel) ?>">
    <section class="card stack-lg">
      <div class="field<?= !empty($invalid['recovery']) ? ' invalid' : '' ?>">
        <div class="field-label">Erholt und leistungsbereit</div>
<?php $name = 'recovery'; $min = 1; $max = 5; $value = $data['recovery'] !== null ? (int) $data['recovery'] : null; $aria = 'Erholung'; include __DIR__ . '/_scale.php'; ?>
        <div class="scale-ends"><span>1 sehr gut</span><span>5 sehr schlecht</span></div>
      </div>

      <div class="field<?= !empty($invalid['soreness']) ? ' invalid' : '' ?>">
        <div class="field-label">Muskelkater</div>
<?php $name = 'soreness'; $min = 1; $max = 5; $value = $data['soreness'] !== null ? (int) $data['soreness'] : null; $aria = 'Muskelkater'; include __DIR__ . '/_scale.php'; ?>
        <div class="scale-ends"><span>1 keiner</span><span>5 stark</span></div>
      </div>

      <div class="field">
        <div class="field-label">Schmerz</div>
<?php $name = 'pain'; $options = ['nein' => 'Nein', 'ja' => 'Ja']; $value = $data['pain_choice']; $wrap = false; $icons = []; include __DIR__ . '/_seg.php'; ?>
      </div>

      <div class="reveal subcard">
        <div class="between"><b>Schmerzereignis</b><span class="hint">Kurzform, Details in <a href="/schmerz?datum=<?= $this->e($date) ?>">Schmerz</a></span></div>
<?php $pain = $data['pain_fields']; include __DIR__ . '/_pain_fields.php'; ?>
      </div>

      <div class="field">
        <label for="note">Notiz <span class="muted label-light">(optional)</span></label>
        <input class="input" id="note" name="notes" value="<?= $this->e($data['notes']) ?>" placeholder="Schlaf, Stress, Besonderes" maxlength="500">
      </div>
    </section>

    <div class="actions-sticky btn-row">
      <button class="btn btn-primary btn-block" type="submit">Speichern</button>
    </div>
  </form>

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
      <a class="list-item link-row" href="/checkin?datum=<?= $this->e($d) ?>"><div><div class="t"><?= $this->e(Dates::long($d)) ?></div><div class="s">Erholung <?= (int) $c['recovery_1_5'] ?> · Muskelkater <?= (int) $c['soreness_1_5'] ?> · <?= $this->e($painText) ?></div></div><span class="badge badge-success"><?= $this->icon('check') ?>erfasst</span></a>
<?php endforeach ?>
    </div>
<?php endif ?>
  </section>
