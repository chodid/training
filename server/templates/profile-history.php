<?php
/** @var \Training\View\View $this */
/** @var string $label */
/** @var list<array<string, mixed>> $versions */
/** @var string $tz */
$fmt = static fn (string $v, string $tz): string => (new DateTimeImmutable($v . ' UTC'))->setTimezone(new DateTimeZone($tz))->format('d.m.Y, H:i') . ' Uhr';
?>
<div class="stack-lg">
<?php if ($versions === []): ?>
  <div class="card empty"><?= $this->icon('clock') ?><p>Noch keine Fassung.</p></div>
<?php endif ?>
<?php foreach ($versions as $i => $v): ?>
  <section class="card stack">
    <div class="card-head"><div><div class="t"><b><?= $this->e($fmt((string) $v['created_at'], $tz)) ?></b> · <?= $v['created_by'] === 'mcp' ? 'Claude' : 'Web' ?></div><?php if ($v['reason'] !== null): ?><div class="muted small"><?= $this->e((string) $v['reason']) ?></div><?php endif ?></div>
      <?php if ($i === 0): ?><span class="badge badge-success">aktuell</span><?php endif ?></div>
<?php if ($v['content'] !== ''): ?>
    <p class="plan-text profile-text"><?= $this->e((string) $v['content']) ?></p>
<?php else: ?>
    <p class="muted">Abschnitt geleert.</p>
<?php endif ?>
  </section>
<?php endforeach ?>
</div>
