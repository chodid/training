<?php
/** @var \Training\View\View $this */
/** @var array{type: string, icon: string, title: string, text: string} $alert */
/** @var ?string $login */
/** @var ?string $csrf */
/** @var ?string $host */
?>
<div class="auth-card stack-lg">
<?php include __DIR__ . '/_brand.php'; ?>
<?php include __DIR__ . '/_alert.php'; ?>
<?php if (!empty($login) && !empty($csrf)): ?>
  <form method="post" action="/logout" class="stack">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <button class="btn btn-secondary btn-block" type="submit"><?= $this->icon('logout') ?>Abmelden</button>
  </form>
  <div class="auth-foot">Angemeldet als <b><?= $this->e($login) ?></b></div>
<?php elseif (!empty($host)): ?>
  <div class="auth-foot"><?= $this->e($host) ?></div>
<?php endif ?>
</div>
