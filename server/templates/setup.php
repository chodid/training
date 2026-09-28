<?php
/** @var \Training\View\View $this */
/** @var string $csrf */
/** @var ?array $alert */
/** @var array<string, bool> $invalid */
/** @var string $login */
/** @var string $tz */
/** @var list<string> $timezones */
/** @var string $host */
$inv = static fn (string $f): string => !empty($invalid[$f]) ? ' invalid' : '';
?>
<form class="auth-card stack-lg" method="post" action="/setup">
<?php $sub = 'Einmalige Einrichtung'; include __DIR__ . '/_brand.php'; ?>
  <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">

  <div class="stack">
    <h1 class="h-card">Benutzer anlegen</h1>
    <p class="muted small">Diese Seite legt den einzigen Benutzer an. Danach ist sie nicht mehr erreichbar. Das Deploy-Secret aus der <span class="mono">.env</span> bestätigt, dass Du der Betreiber bist.</p>
  </div>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>

  <div class="field<?= $inv('secret') ?>">
    <label for="secret">Deploy-Secret</label>
    <input class="input mono" id="secret" name="secret" type="password" autocomplete="off" placeholder="MIGRATION_SECRET" required>
    <div class="hint">Steht in der <span class="mono">.env</span> auf dem Server.</div>
  </div>

  <div class="field<?= $inv('login') ?>">
    <label for="login">Anmeldename</label>
    <input class="input" id="login" name="login" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= $this->e($login) ?>" required maxlength="64">
    <div class="hint">Buchstaben, Ziffern, Punkt, Binde- und Unterstrich.</div>
  </div>

  <div class="field<?= $inv('password') ?>">
    <label for="pw1">Passwort</label>
    <input class="input" id="pw1" name="password" type="password" autocomplete="new-password" required minlength="12">
    <div class="hint">Mindestens 12 Zeichen. Ein Passwort-Manager ist die einfachste Wahl.</div>
  </div>

  <div class="field<?= $inv('password2') ?>">
    <label for="pw2">Passwort wiederholen</label>
    <input class="input" id="pw2" name="password2" type="password" autocomplete="new-password" required>
  </div>

  <div class="field<?= $inv('tz') ?>">
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

  <button class="btn btn-primary btn-block" type="submit">Benutzer anlegen</button>

  <div class="auth-foot"><?= $this->e($host) ?></div>
</form>
