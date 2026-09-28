<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var bool $saved */
/** @var string $date */
/** @var array $sessions */
/** @var array $data */
/** @var array $invalid */
/** @var string $csrf */
use Training\Dates;
?>
  <div class="page-head">
    <div class="eyebrow"><?= $this->e(Dates::long($date, true)) ?><?= $date === $today ? ' · heute' : '' ?></div>
    <h1>Schmerzereignis erfassen</h1>
    <p class="muted small">Ort, Seite, Stärke und Zeitpunkt reichen. Die Schmerzregeln des Trainers greifen auf diese Angaben zu.</p>
  </div>
<?php if ($alert !== null && !$saved) { include __DIR__ . '/_alert.php'; } ?>
<?php if ($saved): ?>
  <?php include __DIR__ . '/_alert.php'; ?>
  <div class="btn-row mt-4"><a class="btn btn-primary" href="/woche?start=<?= Dates::monday($date) ?>">Zur Woche</a><a class="btn btn-secondary" href="/schmerz?datum=<?= $this->e($date) ?>">Weiteres Ereignis</a></div>
<?php else: ?>

  <form class="stack-lg mt-4" method="post" action="/schmerz">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="datum" value="<?= $this->e($date) ?>">
    <section class="card stack-lg">
<?php $pain = $data['pain_fields']; include __DIR__ . '/_pain_fields.php'; ?>

      <div class="field">
        <label for="sess">Zugehörige Einheit <span class="muted label-light">(optional)</span></label>
        <div class="select-wrap">
          <select class="select" id="sess" name="session_id">
            <option value="">Keine</option>
<?php foreach ($sessions as $s): ?>
            <option value="<?= (int) $s['id'] ?>"<?= ($data['session_id'] ?? null) === (int) $s['id'] ? ' selected' : '' ?>><?= $this->e(Dates::short($s['date']) . ' · ' . $s['title']) ?></option>
<?php endforeach ?>
          </select>
          <?= $this->icon('chevron-right') ?>
        </div>
      </div>

      <div class="field">
        <label for="note">Notiz</label>
        <textarea class="textarea" id="note" name="notes" maxlength="1000"><?= $this->e($data['notes']) ?></textarea>
      </div>
    </section>

    <div class="actions-sticky btn-row">
      <a class="btn btn-secondary" href="/checkin?datum=<?= $this->e($date) ?>">Abbrechen</a>
      <button class="btn btn-primary" type="submit">Speichern</button>
    </div>
  </form>
<?php endif ?>
