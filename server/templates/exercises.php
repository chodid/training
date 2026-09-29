<?php
/** @var \Training\View\View $this */
/** @var list<array<string, mixed>> $items */
/** @var ?string $query */
/** @var ?string $category */
/** @var bool $archived */
// S10a Übungskatalog (AP-16, docs/konzept/uebungskatalog.md 6.2): Suche (GET q), Filter Kategorie, archivierte optional.
use Training\View\Labels;

$url = static function (?string $q, ?string $cat, bool $arch): string {
    $params = array_filter(['q' => $q, 'kategorie' => $cat, 'archiv' => $arch ? '1' : null], static fn ($v): bool => $v !== null && $v !== '');

    return '/uebungen' . ($params !== [] ? '?' . http_build_query($params) : '');
};
?>
  <div class="page-head">
    <div class="eyebrow"><?= $this->icon('book', 'ic ic-brand') ?>Übungen, die Claude plant</div>
    <h1>Übungskatalog</h1>
    <p class="kurz">Ausführung, Fehlerquellen und Videos je Übung. Neue Übungen legt Claude im Chat an.</p>
  </div>

  <form class="search-row mt-4" method="get" action="/uebungen" role="search">
<?php if ($category !== null): ?><input type="hidden" name="kategorie" value="<?= $this->e($category) ?>"><?php endif ?>
<?php if ($archived): ?><input type="hidden" name="archiv" value="1"><?php endif ?>
    <label class="sr-only" for="q">Übung suchen</label>
    <input class="input" id="q" name="q" type="search" value="<?= $this->e($query ?? '') ?>" placeholder="Name oder Alias" autocomplete="off">
    <button class="btn btn-secondary" type="submit"><?= $this->icon('search') ?>Suchen</button>
  </form>

  <nav class="chips mt-4" aria-label="Kategorie">
    <a class="chip" href="<?= $this->e($url($query, null, $archived)) ?>"<?= $category === null ? ' aria-current="true"' : '' ?>>Alle</a>
<?php foreach (Labels::EXERCISE_CATEGORIES as $key => [$label]): ?>
    <a class="chip" href="<?= $this->e($url($query, $key, $archived)) ?>"<?= $category === $key ? ' aria-current="true"' : '' ?>><?= $this->e($label) ?></a>
<?php endforeach ?>
  </nav>

  <div class="stack mt-4">
<?php if ($items === []): ?>
    <div class="card empty"><p><?= $query !== null || $category !== null ? 'Keine Übung gefunden.' : 'Noch keine Übungen im Katalog. Claude legt sie bei der Wochenplanung an.' ?></p></div>
<?php endif ?>
<?php foreach ($items as $x): [$catLabel, $catIcon] = Labels::EXERCISE_CATEGORIES[$x['category']]; ?>
    <a class="card ex-card link-row" href="/uebung?id=<?= $this->e(rawurlencode($x['slug'])) ?>">
      <div class="between"><span class="name"><?= $this->icon($catIcon, 'ic ic-brand') ?><?= $this->e($x['name']) ?></span><?= $this->icon('chevron-right', 'ic chev') ?></div>
      <div class="s"><?= $this->e($catLabel . ' · ' . Labels::EXERCISE_PATTERNS[$x['pattern']] . ' · ' . implode(', ', array_map(static fn (string $e): string => Labels::EQUIPMENT[$e] ?? $e, $x['equipment']))) ?></div>
<?php if (!empty($x['kurz'])): ?>
      <p class="kurz"><?= $this->e($x['kurz']) ?></p>
<?php endif ?>
<?php if ($x['status'] === 'links_pruefen'): ?>
      <span class="badge badge-warning"><?= $this->icon('alert-triangle') ?>Links prüfen</span>
<?php elseif ($x['status'] === 'archiviert'): ?>
      <span class="badge badge-neutral">archiviert</span>
<?php endif ?>
    </a>
<?php endforeach ?>
  </div>

  <p class="mt-4"><a class="link-inline" href="<?= $this->e($url($query, $category, !$archived)) ?>"><?= $this->icon($archived ? 'eye-off' : 'eye', 'ic ic-sm') ?><?= $archived ? 'Archivierte ausblenden' : 'Archivierte zeigen' ?></a></p>
