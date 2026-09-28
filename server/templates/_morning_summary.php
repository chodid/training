<?php
/** Zusammenfassung des Morgen-Check-ins (AP-12, 6.2): Ampel mit Grund, Werte, Wochenausgangswert, Abklärung. */
/** @var \Training\View\View $this */
/** @var array $summary Format MorningChecks::summary() */
/** @var ?string $editHref */
use Training\View\Labels;

[$ampelText, $ampelClass] = Labels::AMPEL[$summary['ampel']];
$mt = $summary['morgentest'];
$fmt = static fn (?int $v): string => $v === null ? '–' : (string) $v;
?>
  <section class="card stack morning">
    <div class="card-head"><h2>Morgen-Check-in</h2><span class="badge badge-<?= $ampelClass ?>">Ampel <?= $this->e($ampelText) ?></span></div>
<?php if ($summary['abklaerung_empfohlen']): ?>
    <div class="alert alert-error"><?= $this->icon('alert-triangle') ?><div><b>Abklärung empfohlen.</b> <span class="body">Warnzeichen erfasst bzw. Sprunggelenk umgeknickt mit Schwellung – bitte ärztlich abklären lassen, bevor die betroffene Struktur belastet wird.</span></div></div>
<?php endif ?>
    <p><b><?= $this->e($summary['ampel_grund']) ?></b></p>
    <p class="muted small">Links <?= $fmt($mt['links']) ?> · Rechts <?= $fmt($mt['rechts']) ?> · Steuerwert <?= $fmt($mt['steuerwert']) ?><?= $summary['wochenausgangswert'] !== null ? ' · Wochenausgangswert ' . (int) $summary['wochenausgangswert'] : '' ?> · grün an <?= (int) $summary['tage_gruen_letzte_7'] ?> von 7 Tagen</p>
<?php if ($summary['ueber_wochenausgangswert']): ?>
    <div class="alert alert-warning"><?= $this->icon('alert-circle') ?><div><b>Über dem Wochenausgangswert.</b> <span class="body">Der Morgentest liegt höher als am ersten Messtag dieser Woche (24-Stunden-Regel).</span></div></div>
<?php endif ?>
<?php if (!empty($editHref)): ?>
    <div class="btn-row"><a class="btn btn-secondary" href="<?= $this->e($editHref) ?>"><?= $this->icon('note') ?>Ändern</a></div>
<?php endif ?>
  </section>
