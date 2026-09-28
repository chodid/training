<?php
/** Schmerz-Kurzform (S3, S4, S5): Ort, Seite, Stärke, Zeitpunkt */
/** @var \Training\View\View $this */
/** @var array<string, mixed> $pain Vorbelegung */
/** @var array<string, bool> $invalid */
use Training\View\Labels;
?>
  <div class="field<?= !empty($invalid['location']) ? ' invalid' : '' ?>">
    <label for="pain-location">Ort</label>
    <div class="select-wrap">
      <select class="select" id="pain-location" name="pain_location">
        <option value="">Bitte wählen</option>
<?php foreach (Labels::LOCATIONS as $v => $label): ?>
        <option value="<?= $v ?>"<?= ($pain['location'] ?? '') === $v ? ' selected' : '' ?>><?= $this->e($label) ?></option>
<?php endforeach ?>
      </select>
      <?= $this->icon('chevron-right') ?>
    </div>
  </div>
  <div class="field">
    <div class="field-label">Seite</div>
<?php $name = 'pain_side'; $options = Labels::SIDES; $value = (string) ($pain['side'] ?? 'na'); $wrap = false; $icons = []; include __DIR__ . '/_seg.php'; ?>
  </div>
  <div class="field<?= !empty($invalid['intensity']) ? ' invalid' : '' ?>">
    <div class="field-label">Stärke (0–10)</div>
<?php $name = 'pain_intensity'; $min = 0; $max = 10; $value = isset($pain['intensity']) ? (int) $pain['intensity'] : null; $aria = 'Stärke'; include __DIR__ . '/_scale.php'; ?>
    <div class="scale-ends"><span>0 kein Schmerz</span><span>10 stärkster vorstellbarer</span></div>
  </div>
  <div class="field<?= !empty($invalid['timing']) ? ' invalid' : '' ?>">
    <div class="field-label">Wann</div>
<?php $name = 'pain_timing'; $options = Labels::TIMINGS; $value = isset($pain['timing']) ? (string) $pain['timing'] : null; $wrap = true; $icons = []; include __DIR__ . '/_seg.php'; ?>
  </div>
