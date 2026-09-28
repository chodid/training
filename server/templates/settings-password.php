<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var array $invalid */
/** @var string $csrf */
$inv = static fn (string $f): string => !empty($invalid[$f]) ? ' invalid' : '';
?>
  <div class="page-head"><div class="eyebrow"><a href="/einstellungen">Einstellungen</a></div><h1>Passwort ändern</h1><p class="muted small">Andere Geräte werden danach abgemeldet; dieses Gerät bleibt angemeldet.</p></div>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>
  <form class="stack-lg mt-4" method="post" action="/einstellungen">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="action" value="passwort">
    <section class="card stack-lg">
      <div class="field<?= $inv('current') ?>"><label for="cur">Aktuelles Passwort</label><input class="input" id="cur" name="current" type="password" autocomplete="current-password" required></div>
      <div class="field<?= $inv('password') ?>"><label for="pw1">Neues Passwort</label><input class="input" id="pw1" name="password" type="password" autocomplete="new-password" required minlength="12"><div class="hint">Mindestens 12 Zeichen.</div></div>
      <div class="field<?= $inv('password2') ?>"><label for="pw2">Neues Passwort wiederholen</label><input class="input" id="pw2" name="password2" type="password" autocomplete="new-password" required></div>
    </section>
    <div class="actions-sticky btn-row"><a class="btn btn-secondary" href="/einstellungen">Abbrechen</a><button class="btn btn-primary" type="submit">Speichern</button></div>
  </form>
