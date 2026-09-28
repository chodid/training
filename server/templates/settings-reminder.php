<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var string $csrf */
/** @var string $reminder */
/** @var bool $off */
?>
  <div class="page-head"><div class="eyebrow"><a href="/einstellungen">Einstellungen</a></div><h1>Erinnerung im Kalender</h1><p class="muted small">Benachrichtigung am Trainingstag, z. B. um 05:00 Uhr. Sind alle Einheiten des Tages erledigt oder ausgelassen, entfällt sie.</p></div>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>
  <form class="stack-lg mt-4" method="post" action="/einstellungen">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="action" value="erinnerung">
    <section class="card stack-lg">
      <div class="field">
        <label for="uhrzeit">Uhrzeit</label>
        <input class="input" type="time" id="uhrzeit" name="uhrzeit" step="300" value="<?= $this->e($reminder) ?>">
      </div>
      <label class="check"><input type="checkbox" name="aus" value="1"<?= $off ? ' checked' : '' ?>> Keine Erinnerung</label>
    </section>
    <div class="actions-sticky btn-row"><a class="btn btn-secondary" href="/einstellungen">Abbrechen</a><button class="btn btn-primary" type="submit">Speichern</button></div>
  </form>
