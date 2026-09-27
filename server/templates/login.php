<?php
/** @var \Training\View\View $this */
/** @var string $csrf */
/** @var ?array $alert */
/** @var bool $locked */
/** @var bool $invalid */
/** @var string $login */
/** @var string $next */
/** @var string $host */
?>
<form class="auth-card stack-lg" method="post" action="/login">
<?php include __DIR__ . '/_brand.php'; ?>
  <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
  <input type="hidden" name="next" value="<?= $this->e($next) ?>">
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>

  <div class="field<?= $invalid ? ' invalid' : '' ?>">
    <label for="login">Anmeldename</label>
    <input class="input" id="login" name="login" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= $this->e($login) ?>" required>
  </div>

<?php if (!$locked): ?>
  <div class="field pw<?= $invalid ? ' invalid' : '' ?>">
    <label for="pw">Passwort</label>
    <input class="input" id="pw" name="password" type="password" autocomplete="current-password" required>
  </div>

  <button class="btn btn-primary btn-block" type="submit">Anmelden</button>
  <p class="hint center">Angemeldet bleiben für 30 Tage auf diesem Gerät.</p>
<?php else: ?>
  <a class="btn btn-secondary btn-block" href="<?= $this->e('/login' . ($next !== '' ? '?next=' . rawurlencode($next) : '')) ?>">Erneut versuchen</a>
<?php endif ?>

  <div class="auth-foot"><?= $this->e($host) ?></div>
</form>
