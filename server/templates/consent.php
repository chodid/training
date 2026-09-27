<?php
/** @var \Training\View\View $this */
/** @var string $csrf */
/** @var array<string, string> $params */
/** @var string $clientName */
/** @var string $redirectHost */
/** @var string $registered */
/** @var list<string> $scopeTexts */
/** @var string $scope */
/** @var string $login */
?>
<form class="auth-card stack-lg" method="post" action="/oauth/authorize">
<?php include __DIR__ . '/_brand.php'; ?>
  <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
<?php foreach ($params as $name => $value): ?>
  <input type="hidden" name="<?= $this->e($name) ?>" value="<?= $this->e($value) ?>">
<?php endforeach ?>

  <div class="stack">
    <h1 class="h-card">Zugriff freigeben?</h1>
    <p class="muted small">Eine Anwendung möchte über die MCP-Schnittstelle auf Deine Trainingsdaten zugreifen. Ohne Freigabe wird kein Zugang erteilt.</p>
  </div>

  <div class="subcard">
    <dl class="kv">
      <dt>Anwendung</dt><dd><b><?= $this->e($clientName) ?></b></dd>
      <dt>Weiterleitung an</dt><dd class="mono"><?= $this->e($redirectHost) ?></dd>
      <dt>Registriert</dt><dd><?= $this->e($registered) ?></dd>
      <dt>Zugriff</dt>
      <dd>
        <div class="stack gap-6">
<?php foreach ($scopeTexts as $text): ?>
          <span class="row"><?= $this->icon('check', 'ic ic-sm ic-brand') ?><?= $this->e($text) ?></span>
<?php endforeach ?>
        </div>
        <div class="hint mono mt-8">scope: <?= $this->e($scope) ?></div>
      </dd>
    </dl>
  </div>

  <div class="alert alert-hint">
    <?= $this->icon('info-circle') ?>
    <div><b>Hinweis.</b> <span class="body">Prüfe, ob Du die Verbindung gerade selbst eingerichtet hast. Die Freigabe gilt, bis sie widerrufen wird.</span></div>
  </div>

  <div class="btn-row">
    <button class="btn btn-secondary" type="submit" name="decision" value="deny">Ablehnen</button>
    <button class="btn btn-primary" type="submit" name="decision" value="approve">Freigeben</button>
  </div>

  <div class="auth-foot">Angemeldet als <b><?= $this->e($login) ?></b></div>
</form>
