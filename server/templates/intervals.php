<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var string $csrf */
/** @var string $login */
$fmtDuration = static fn ($s): string => is_numeric($s) ? sprintf('%d:%02d h', intdiv((int) $s, 3600), intdiv((int) $s % 3600, 60)) : '–';
?>
<div class="auth-card auth-card-wide stack-lg">
<?php $sub = 'Verbindungstest Intervals.icu'; include __DIR__ . '/_brand.php'; ?>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>
<?php if (isset($athlete)): ?>
  <div class="subcard">
    <dl class="kv">
      <dt>Athlet</dt><dd><b><?= $this->e($athlete) ?></b></dd>
      <dt>Aktivitäten 7 Tage</dt><dd><?= count($activities) ?></dd>
      <dt>Wellness 7 Tage</dt><dd><?= count($wellness) ?> Tage</dd>
      <dt>Events 14 Tage</dt><dd><?= count($events) ?></dd>
    </dl>
  </div>

  <div class="stack">
    <h2 class="h-card">Aktivitäten (letzte 7 Tage)</h2>
<?php if ($activities === []): ?>
    <p class="muted small">Keine Aktivitäten.</p>
<?php else: ?>
    <dl class="kv">
<?php foreach ($activities as $a): ?>
      <dt class="mono"><?= $this->e(substr((string) ($a['start_date_local'] ?? ''), 0, 10)) ?></dt>
      <dd><?= $this->e(($a['type'] ?? '?') . ' · ' . ($a['name'] ?? '')) ?> <span class="muted mono"><?= $this->e($fmtDuration($a['moving_time'] ?? null)) ?><?= isset($a['icu_rpe']) ? ' · RPE ' . $this->e($a['icu_rpe']) : '' ?><?= isset($a['feel']) ? ' · Feel ' . $this->e($a['feel']) : '' ?></span></dd>
<?php endforeach ?>
    </dl>
<?php endif ?>
  </div>

  <div class="stack">
    <h2 class="h-card">Wellness (letzte 7 Tage)</h2>
<?php if ($wellness === []): ?>
    <p class="muted small">Keine Wellness-Daten.</p>
<?php else: ?>
    <dl class="kv">
<?php foreach ($wellness as $w): ?>
      <dt class="mono"><?= $this->e($w['id'] ?? '') ?></dt>
      <dd class="mono small">HRV <?= $this->e($w['hrv'] ?? '–') ?> · Ruhepuls <?= $this->e($w['restingHR'] ?? '–') ?> · Schlaf <?= isset($w['sleepSecs']) ? $this->e(number_format($w['sleepSecs'] / 3600, 1, ',', '')) . ' h' : '–' ?></dd>
<?php endforeach ?>
    </dl>
<?php endif ?>
  </div>

  <div class="stack">
    <h2 class="h-card">Events (nächste 14 Tage)</h2>
<?php if ($events === []): ?>
    <p class="muted small">Keine Events.</p>
<?php else: ?>
    <dl class="kv">
<?php foreach ($events as $ev): ?>
      <dt class="mono"><?= $this->e(substr((string) ($ev['start_date_local'] ?? ''), 0, 10)) ?></dt>
      <dd><?= $this->e(($ev['category'] ?? '') . ' · ' . ($ev['type'] ?? '') . ' · ' . ($ev['name'] ?? '')) ?> <span class="muted mono">#<?= $this->e($ev['id'] ?? '') ?></span></dd>
<?php endforeach ?>
    </dl>
<?php endif ?>
  </div>

  <form method="post" action="/intervals" class="stack">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <h2 class="h-card">Test-Event</h2>
    <p class="muted small">Legt für <span class="mono"><?= $this->e($tomorrow) ?></span> eine Laufeinheit mit HF-Zonen an (Aufwärmen 10 min Z1, 3 × 3 min Z3 / 2 min Z1, Auslaufen 5 min Z1). Danach auf der Uhr prüfen; Ändern und Löschen testen das Nachziehen.</p>
    <div class="btn-row">
<?php if ($testEvents === []): ?>
      <button class="btn btn-primary" type="submit" name="action" value="create">Test-Event anlegen</button>
<?php else: ?>
      <button class="btn btn-secondary" type="submit" name="action" value="update">Test-Event ändern</button>
      <button class="btn btn-danger" type="submit" name="action" value="delete">Test-Event löschen</button>
<?php endif ?>
    </div>
  </form>
<?php endif ?>

  <div class="auth-foot"><a href="/">Zurück</a> · Angemeldet als <b><?= $this->e($login) ?></b></div>
</div>
