<?php
/** Segmentwahl als Radio-Gruppe (Branding: .seg) */
/** @var \Training\View\View $this */
/** @var string $name */
/** @var array<string, string> $options */
/** @var ?string $value */
/** @var bool $wrap */
/** @var array<string, string> $icons */
?>
<div class="seg<?= !empty($wrap) ? ' wrap' : '' ?>" role="radiogroup">
<?php foreach ($options as $v => $label): ?>
  <label><input type="radio" name="<?= $this->e($name) ?>" value="<?= $this->e($v) ?>"<?= $value === (string) $v ? ' checked' : '' ?>><?= isset($icons[$v]) ? $this->icon($icons[$v], 'ic ic-sm') : '' ?><span><?= $this->e($label) ?></span></label>
<?php endforeach ?>
</div>
