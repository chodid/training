<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var array $session */
/** @var ?array $activity */
/** @var array $data */
/** @var array $invalid */
/** @var string $csrf */
use Training\Dates;
use Training\View\Labels;
use Training\View\PlanFormat;

[$typeLabel, $typeIcon] = Labels::TYPES[$session['type']];
$plan = $session['plan'] ?? [];
$fmtSoll = PlanFormat::exercise(...);
$fmtBlock = PlanFormat::block(...);
$meta = $typeLabel . ' · ' . Dates::long($session['date']) . ' · Priorität ' . $session['priority'] . ($session['planned_duration_min'] !== null ? ' · ' . $session['planned_duration_min'] . ' min' : '');
?>
  <div class="page-head">
    <div class="eyebrow"><?= $this->icon($typeIcon, 'ic ic-brand') ?><?= $this->e($meta) ?></div>
    <h1><?= $this->e($session['title']) ?></h1>
<?php
// Begründung der Einheit (AP-13, E-11): Kurzsatz im Seitenkopf, ausführlicher Text hinter „mehr“; Altdaten ohne Kurzsatz: „Trainer-Notiz“
$kurz = trim((string) ($session['coach_summary'] ?? ''));
$mehr = trim((string) ($session['coach_rationale'] ?? ''));
?>
<?php if ($kurz !== ''): ?>
    <p class="kurz"><?= $this->e($kurz) ?></p>
<?php endif ?>
<?php if ($mehr !== ''): ?>
    <details class="more mehr"><summary><?= $this->icon('chevron-right', 'ic ic-sm') ?><?= $kurz !== '' ? 'mehr' : 'Trainer-Notiz' ?></summary><p><?= $this->e($mehr) ?></p></details>
<?php endif ?>
<?php if (!empty($guidedHref)): // Einstieg in die geführte Einheit (E-13, 6.1) ?>
    <div class="btn-row start-row"><a class="btn <?= $session['status'] === 'erledigt' ? 'btn-secondary' : 'btn-primary' ?>" href="<?= $this->e($guidedHref) ?>"><?= $this->icon('player-play') ?><?= $session['status'] === 'erledigt' ? 'Erneut durchgehen' : 'Einheit starten' ?></a></div>
<?php endif ?>
  </div>
<?php if ($alert !== null): ?>
  <div class="mt-4"><?php include __DIR__ . '/_alert.php'; ?></div>
<?php endif ?>

  <form class="two-col mt-4" method="post" action="/einheit" data-offline-form>
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="id" value="<?= (int) $session['id'] ?>">
    <input type="hidden" name="stand" value="<?= $this->e($stand) ?>">
    <input type="hidden" name="offline_label" value="<?= $this->e($offlineLabel) ?>">
    <div class="stack-lg">
<?php if (isset($plan['exercises'])): ?>
      <section class="card<?= !empty($invalid['ist']) ? ' invalid' : '' ?>">
        <div class="card-head"><h2>Plan und Ist</h2><span class="hint">Ist ist mit Soll vorbelegt</span></div>
<?php foreach ($plan['exercises'] as $i => $x): $ist = $data['ist'][$i] ?? $x; ?>
        <div class="exercise">
          <div class="between"><?php if (isset($exerciseLinks[$i])): // Übungskatalog (AP-16, 6.3) ?><a class="name ex-link" href="/uebung?id=<?= $this->e(rawurlencode($exerciseLinks[$i]['slug'])) ?>&amp;von=<?= (int) $session['id'] ?>"><?= $this->icon('book', 'ic ic-sm') ?><?= $this->e($x['name']) ?></a><?php else: ?><span class="name"><?= $this->e($x['name']) ?></span><?php endif ?><span class="soll"><?= $this->e($fmtSoll($x)) ?></span></div>
<?php if (!empty($x['notes'])): ?><div class="soll"><?= $this->e($x['notes']) ?></div><?php endif ?>
<?php include __DIR__ . '/_ist_exercise.php'; ?>
        </div>
<?php endforeach ?>
      </section>
<?php elseif (isset($plan['blocks'])): ?>
      <section class="card<?= !empty($invalid['ist']) ? ' invalid' : '' ?>">
        <div class="card-head"><h2>Blöcke</h2><span class="hint">Ist ist mit Soll vorbelegt</span></div>
<?php foreach ($plan['blocks'] as $i => $b): $ist = $data['ist'][$i] ?? $b; ?>
        <div class="exercise">
          <div class="between"><?php if (isset($exerciseLinks[$i])): ?><a class="name ex-link" href="/uebung?id=<?= $this->e(rawurlencode($exerciseLinks[$i]['slug'])) ?>&amp;von=<?= (int) $session['id'] ?>"><?= $this->icon('book', 'ic ic-sm') ?><?= $this->e(Labels::BLOCK_KINDS[$b['kind']] ?? $b['kind']) ?> · <?= $this->e($exerciseLinks[$i]['name']) ?></a><?php else: ?><span class="name"><?= $this->e(Labels::BLOCK_KINDS[$b['kind']] ?? $b['kind']) ?></span><?php endif ?><?php if (!empty($b['spezifitaet'])): ?><span class="badge badge-brand"><?= $this->e($b['spezifitaet']) ?></span><?php endif ?></div>
          <div class="soll"><?= $this->e($fmtBlock($b)) ?></div>
<?php if (!empty($b['notes'])): ?><div class="soll"><?= $this->e($b['notes']) ?></div><?php endif ?>
<?php include __DIR__ . '/_ist_block.php'; ?>
        </div>
<?php endforeach ?>
      </section>
<?php elseif (isset($plan['intervals_workout_text'])): ?>
      <section class="card">
        <div class="card-head"><h2>Plan</h2><?php if ($session['intervals_event_id'] !== null): ?><span class="badge badge-info"><?= $this->icon('plug-connected') ?>auf der Uhr</span><?php endif ?></div>
        <div class="stack">
          <p><?= $this->e($plan['summary']) ?></p>
          <pre class="mono small plan-text"><?= $this->e($plan['intervals_workout_text']) ?></pre>
<?php if (!empty($plan['notes'])): ?><div class="hint"><?= $this->e($plan['notes']) ?></div><?php endif ?>
        </div>
      </section>
<?php include __DIR__ . '/_activity.php'; ?>
<?php endif ?>
    </div>

    <div class="stack-lg sticky">
      <section class="card stack-lg">
        <div class="card-head"><h2>Rückmeldung</h2><span class="hint">30 min nach Ende</span></div>

<?php include __DIR__ . '/_feedback_fields.php'; ?>
      </section>

      <div class="actions-sticky btn-row">
        <a class="btn btn-secondary" href="/woche?start=<?= Dates::monday($session['date']) ?>">Abbrechen</a>
        <button class="btn btn-primary" type="submit">Speichern</button>
      </div>
    </div>
  </form>
