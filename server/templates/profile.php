<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var array<string, array{0: string, 1: string}> $sections */
/** @var array<string, ?array<string, mixed>> $current */
/** @var string $tz */
$fmt = static fn (string $v, string $tz): string => (new DateTimeImmutable($v . ' UTC'))->setTimezone(new DateTimeZone($tz))->format('d.m.Y');
?>
<div class="stack-lg">
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>
  <p class="muted small">Grundlage für die Planung. Claude liest das Profil über <span class="mono">get_athlete_profile</span> und trägt Änderungen selbst ein (z. B. nach Tests); hier kannst du lesen und korrigieren. Jede Änderung wird als neue Fassung gespeichert.</p>
<?php foreach ($sections as $key => [$label, $hint]): $row = $current[$key]; ?>
  <section id="<?= $this->e($key) ?>">
    <div class="section-title"><h2><?= $this->e($label) ?></h2><?php if ($row !== null): ?><span class="hint">Stand <?= $this->e($fmt((string) $row['created_at'], $tz)) ?> · <?= $row['created_by'] === 'mcp' ? 'Claude' : 'Web' ?></span><?php endif ?></div>
    <div class="card stack">
<?php if ($row !== null && $row['content'] !== ''): ?>
      <p class="plan-text profile-text"><?= $this->e((string) $row['content']) ?></p>
<?php else: ?>
      <p class="muted">Noch leer – <?= $this->e($hint) ?>.</p>
<?php endif ?>
      <div class="btn-row"><a class="btn btn-secondary" href="/profil?abschnitt=<?= $this->e($key) ?>"><?= $this->icon('note') ?>Bearbeiten</a>
<?php if ($row !== null && $row['versions'] > 1): ?>
        <a class="btn btn-ghost" href="/profil?abschnitt=<?= $this->e($key) ?>&amp;verlauf=1"><?= $this->icon('clock') ?>Frühere Fassungen (<?= (int) $row['versions'] - 1 ?>)</a>
<?php endif ?>
      </div>
    </div>
  </section>
<?php endforeach ?>
</div>
