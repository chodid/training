<?php
/** @var \Training\View\View $this */
/** @var array $exercise */
/** @var list<array{slug: string, name: string}> $children */
/** @var list<array{version: int, reason: string, created_at: string}> $versions */
/** @var ?int $from aufrufende Einheit */
/** @var bool $guided aus der geführten Einheit (S9) */
// S10 Übung (AP-16, docs/konzept/uebungskatalog.md 6.1): Abschnitte in fester Reihenfolge, leere entfallen; ohne JavaScript.
use Training\View\Labels;

$c = $exercise['content'];
[$catLabel, $catIcon] = Labels::EXERCISE_CATEGORIES[$exercise['category']];
[$konfLabel, $konfClass] = Labels::KONFIDENZ[$exercise['konfidenz']];
$links = $c['links'] ?? [];
$videos = array_values(array_filter($links, static fn (array $l): bool => $l['embed'] !== null));
$otherLinks = array_values(array_filter($links, static fn (array $l): bool => $l['embed'] === null));
$suffix = $from !== null ? '&von=' . $from . ($guided ? '&modus=start' : '') : '';
$exLink = fn (string $slug): string => '/uebung?id=' . rawurlencode($slug) . $suffix;
$list = function (string $title, array $items, string $tag = 'ul'): void {
    if ($items === []) {
        return;
    }
    echo '<section class="card"><div class="card-head"><h2>' . $this->e($title) . '</h2></div><' . $tag . ' class="ex-list">';
    foreach ($items as $item) {
        echo '<li>' . $this->e($item) . '</li>';
    }
    echo '</' . $tag . '></section>';
};
$statusBadge = static fn (string $s): array => match ($s) { 'ok' => ['geprüft', 'success'], 'defekt' => ['defekt', 'error'], default => ['nicht geprüft', 'neutral'] };
?>
  <div class="page-head">
    <div class="eyebrow"><?= $this->icon($catIcon, 'ic ic-brand') ?><?= $this->e($catLabel . ' · ' . Labels::EXERCISE_PATTERNS[$exercise['pattern']]) ?></div>
    <h1><?= $this->e($exercise['name']) ?></h1>
    <div class="chips mt-8">
<?php foreach ($exercise['equipment'] as $eq): ?>
      <span class="badge badge-brand"><?= $this->e(Labels::EQUIPMENT[$eq] ?? $eq) ?></span>
<?php endforeach ?>
      <span class="badge badge-<?= $konfClass ?>"><?= $this->e($konfLabel) ?></span>
<?php if ($exercise['difficulty'] !== null): ?>
      <span class="badge badge-neutral">Stufe <?= (int) $exercise['difficulty'] ?>/5</span>
<?php endif ?>
    </div>
<?php if ($exercise['aliases'] !== []): ?>
    <p class="hint mt-8">Auch: <?= $this->e(implode(', ', $exercise['aliases'])) ?></p>
<?php endif ?>
  </div>
<?php if ($exercise['status'] === 'links_pruefen'): ?>
  <div class="alert alert-warning mt-4"><?= $this->icon('alert-triangle') ?><div><b>Links prüfen.</b> <span class="body">Mindestens ein Link war bei der letzten Prüfung nicht erreichbar. Claude kann ihn im Chat ersetzen.</span></div></div>
<?php elseif ($exercise['status'] === 'archiviert'): ?>
  <div class="alert alert-info mt-4"><?= $this->icon('info-circle') ?><div><b>Archiviert.</b> <span class="body">Diese Übung wird nicht mehr geplant.</span></div></div>
<?php endif ?>

  <div class="stack-lg mt-4 exercise-page">
    <section class="card stack">
      <p class="lead"><?= $this->e($c['kurz']) ?></p>
      <p><?= $this->e($c['ziel']) ?></p>
<?php if (!empty($c['muskeln'])): ?>
      <p class="hint">Muskeln: <?= $this->e(implode(', ', $c['muskeln'])) ?></p>
<?php endif ?>
    </section>
<?php if (!empty($c['voraussetzung'])): ?>
    <section class="card"><div class="card-head"><h2>Voraussetzung</h2></div><p class="pre-line"><?= $this->e($c['voraussetzung']) ?></p></section>
<?php endif ?>
<?php $list('Ausführung', $c['ausfuehrung'], 'ol'); ?>
<?php $list('Worauf achten', $c['achten'] ?? []); ?>
<?php $list('Fehlerquellen', $c['fehler'] ?? []); ?>
<?php if (!empty($c['vorsicht'])): ?>
    <section class="card vorsicht"><div class="card-head"><h2><?= $this->icon('alert-triangle') ?>Vorsicht</h2></div>
      <ul class="ex-list">
<?php foreach ($c['vorsicht'] as $v): ?>
        <li><?= $this->e($v) ?></li>
<?php endforeach ?>
      </ul>
    </section>
<?php endif ?>
<?php if (!empty($c['progression']) || !empty($c['regression']) || $exercise['variant_of_slug'] !== null || $children !== []): ?>
    <section class="card"><div class="card-head"><h2>Progression und Regression</h2></div>
      <dl class="kv">
<?php if (!empty($c['progression'])): ?>
        <dt>Schwerer</dt><dd class="pre-line"><?= $this->e($c['progression']) ?></dd>
<?php endif ?>
<?php if (!empty($c['regression'])): ?>
        <dt>Leichter</dt><dd class="pre-line"><?= $this->e($c['regression']) ?></dd>
<?php endif ?>
<?php if ($exercise['variant_of_slug'] !== null): ?>
        <dt>Grundübung</dt><dd><a href="<?= $this->e($exLink($exercise['variant_of_slug'])) ?>"><?= $this->e($exercise['variant_of_name']) ?></a></dd>
<?php endif ?>
<?php if ($children !== []): ?>
        <dt>Varianten</dt><dd><?php foreach ($children as $i => $child): ?><?= $i > 0 ? ', ' : '' ?><a href="<?= $this->e($exLink($child['slug'])) ?>"><?= $this->e($child['name']) ?></a><?php endforeach ?></dd>
<?php endif ?>
      </dl>
    </section>
<?php endif ?>
<?php if (!empty($c['dosierung_hinweis'])): ?>
    <section class="card"><div class="card-head"><h2>Dosierung</h2><span class="hint">Standardbereich, die Einheit gibt die Dosis vor</span></div><p class="pre-line"><?= $this->e($c['dosierung_hinweis']) ?></p></section>
<?php endif ?>
<?php if ($videos !== []): ?>
    <section class="card stack"><div class="card-head"><h2>Videos</h2></div>
<?php foreach ($videos as $v): ?>
      <figure class="video">
        <div class="video-frame"><iframe src="<?= $this->e($v['embed']) ?>" title="<?= $this->e($v['titel']) ?>" loading="lazy" allow="fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
        <figcaption><?= $this->icon('video', 'ic ic-sm') ?><a href="<?= $this->e($v['url']) ?>" rel="noopener noreferrer" target="_blank"><?= $this->e($v['titel']) ?></a><?php if ($v['status'] === 'defekt'): ?> <span class="badge badge-error">defekt</span><?php endif ?></figcaption>
      </figure>
<?php endforeach ?>
    </section>
<?php endif ?>
<?php if ($otherLinks !== []): ?>
    <section class="card"><div class="card-head"><h2>Links</h2></div>
      <div class="list">
<?php foreach ($otherLinks as $l): [$sLabel, $sClass] = $statusBadge($l['status']); ?>
        <div class="list-item">
          <div><a class="link-inline" href="<?= $this->e($l['url']) ?>" rel="noopener noreferrer" target="_blank"><?= $this->icon($l['art'] === 'video' ? 'video' : 'external-link', 'ic ic-sm') ?><?= $this->e($l['titel']) ?></a><div class="s link-host"><?= $this->e((string) parse_url($l['url'], PHP_URL_HOST)) ?><?= $l['geprueft_am'] !== null ? ' · geprüft ' . $this->e(\Training\Dates::short($l['geprueft_am'])) : '' ?></div></div>
          <span class="badge badge-<?= $sClass ?>"><?= $this->e($sLabel) ?></span>
        </div>
<?php endforeach ?>
      </div>
    </section>
<?php endif ?>
    <section class="card"><div class="card-head"><h2>Quellen</h2></div>
      <p><?= $this->e(implode(' · ', $c['quellen'])) ?></p>
<?php if (!empty($c['notizen'])): ?>
      <p class="hint pre-line mt-8"><?= $this->e($c['notizen']) ?></p>
<?php endif ?>
    </section>
    <details class="more card">
      <summary>Fassung <?= (int) $exercise['version'] ?><span class="hint">· zuletzt geändert <?= $this->e(\Training\Dates::short(substr((string) $exercise['updated_at'], 0, 10))) ?></span></summary>
<?php if ($versions === []): ?>
      <p class="hint">Keine früheren Fassungen.</p>
<?php else: ?>
      <div class="list">
<?php foreach ($versions as $v): ?>
        <div class="list-item"><div><span class="t">Fassung <?= (int) $v['version'] + 1 ?></span><div class="s"><?= $this->e($v['reason']) ?></div></div><span class="hint"><?= $this->e(\Training\Dates::short(substr((string) $v['created_at'], 0, 10))) ?></span></div>
<?php endforeach ?>
      </div>
<?php endif ?>
    </details>
  </div>
