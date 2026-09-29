<?php
/**
 * Erinnerung an fällige Blockbilanz/Zielklärung (AP-15, docs/konzept/blockbilanz.md 6.1, E-05/E-14): Karte über
 * abgedunkeltem Hintergrund, muss aktiv weggeklickt werden. Ohne JavaScript bedienbar (Formular), Seiteninhalt
 * dahinter inert. Offline puffert der Service Worker die Quittierung (FORM_PATHS).
 */
/** @var \Training\View\View $this */
/** @var list<array<string, mixed>> $reminders */
/** @var string $reminderBack */
/** @var string $csrf */
$kinds = array_values(array_unique(array_column($reminders, 'kind')));
$heading = count($kinds) > 1 ? 'Blockbilanz und Zielklärung fällig' : $reminders[0]['titel'];
?>
<div class="review-overlay" data-review-overlay>
  <section class="card stack review-dialog" role="dialog" aria-modal="true" aria-labelledby="review-overlay-title" aria-describedby="review-overlay-text">
    <div class="card-head"><h2 id="review-overlay-title"><?= $this->icon('mountain', 'ic ic-lg ic-warning') ?><?= $this->e($heading) ?></h2></div>
    <ul class="review-items" id="review-overlay-text">
<?php foreach ($reminders as $r): ?>
      <li><b><?= $this->e($r['titel']) ?>.</b> <?= $this->e($r['text']) ?> <a href="<?= $this->e($r['href']) ?>">Zur Blockseite</a></li>
<?php endforeach ?>
    </ul>
    <p class="muted small">Im Projekt-Chat erstellen – <code>get_handover</code> meldet die Fälligkeit.</p>
    <form method="post" action="/erinnerung" class="btn-row">
      <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
      <input type="hidden" name="zurueck" value="<?= $this->e($reminderBack) ?>">
      <input type="hidden" name="offline_label" value="Erinnerung quittiert">
<?php foreach ($reminders as $r): ?>
      <input type="hidden" name="eintrag[]" value="<?= $this->e($r['kind'] . ':' . ($r['block_id'] ?? 0)) ?>">
<?php endforeach ?>
      <button class="btn btn-primary" type="submit" name="bis" value="morgen" autofocus>Morgen wieder erinnern</button>
      <button class="btn btn-secondary" type="submit" name="bis" value="woche">Diese Woche nicht mehr</button>
    </form>
  </section>
</div>
