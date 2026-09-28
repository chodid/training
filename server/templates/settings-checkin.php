<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var string $csrf */
/** @var string $handBis */
?>
  <div class="page-head"><div class="eyebrow"><a href="/einstellungen">Einstellungen</a></div><h1>Morgen-Check-in</h1><p class="muted small">Das Feld „Hand rechts“ erscheint im Check-in bis einschließlich zu diesem Tag.</p></div>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>
  <form class="stack-lg mt-4" method="post" action="/einstellungen">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="action" value="checkin">
    <section class="card stack-lg">
      <div class="field">
        <label for="hand_bis">„Hand rechts“ abfragen bis</label>
        <input class="input" type="date" id="hand_bis" name="hand_bis" value="<?= $this->e($handBis) ?>">
      </div>
    </section>
    <div class="actions-sticky btn-row"><a class="btn btn-secondary" href="/einstellungen">Abbrechen</a><button class="btn btn-primary" type="submit">Speichern</button></div>
  </form>
