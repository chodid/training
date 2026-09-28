<?php
/** Skalenwahl als Radio-Gruppe (Branding: .scale / .scale-11) */
/** @var \Training\View\View $this */
/** @var string $name */
/** @var int $min */
/** @var int $max */
/** @var ?int $value */
/** @var string $aria */
/** @var bool $clearable optional: erneutes Tippen leert den Wert (js/checkin.js) */
?>
<div class="scale<?= $max - $min > 4 ? ' scale-11' : '' ?>" role="radiogroup" aria-label="<?= $this->e($aria) ?>"<?= !empty($clearable) ? ' data-clearable' : '' ?>>
<?php for ($i = $min; $i <= $max; $i++): ?>
  <label><input type="radio" name="<?= $this->e($name) ?>" value="<?= $i ?>"<?= $value === $i ? ' checked' : '' ?>><span><?= $i ?></span></label>
<?php endfor ?>
</div>
