<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var string $csrf */
?>
  <div class="page-head"><div class="eyebrow"><a href="/einstellungen">Einstellungen</a></div><h1>Zeitzone ändern</h1><p class="muted small">Bestimmt, welcher Tag „heute“ ist (Woche, Check-in).</p></div>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>
  <form class="stack-lg mt-4" method="post" action="/einstellungen">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="action" value="zeitzone">
    <section class="card stack-lg">
      <div class="field">
        <label for="tz">Zeitzone</label>
        <div class="select-wrap">
          <select class="select" id="tz" name="tz">
<?php foreach ($timezones as $zone): ?>
            <option<?= $zone === $tz ? ' selected' : '' ?>><?= $this->e($zone) ?></option>
<?php endforeach ?>
          </select>
          <?= $this->icon('chevron-right') ?>
        </div>
      </div>
    </section>
    <div class="actions-sticky btn-row"><a class="btn btn-secondary" href="/einstellungen">Abbrechen</a><button class="btn btn-primary" type="submit">Speichern</button></div>
  </form>
