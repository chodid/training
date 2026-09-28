<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var string $csrf */
/** @var string $section */
/** @var string $label */
/** @var string $hint */
/** @var string $content */
/** @var string $reason */
/** @var ?int $base */
/** @var int $max */
?>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>
  <form class="stack-lg" method="post" action="/profil">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="abschnitt" value="<?= $this->e($section) ?>">
    <input type="hidden" name="basis" value="<?= $base !== null ? (int) $base : '' ?>">
    <section class="card stack-lg">
      <div class="field">
        <label for="inhalt"><?= $this->e($label) ?> <span class="label-light muted">– <?= $this->e($hint) ?></span></label>
        <textarea class="textarea profile-edit" id="inhalt" name="inhalt" maxlength="<?= $max ?>" rows="14"><?= $this->e($content) ?></textarea>
        <div class="hint">Markdown möglich, höchstens <?= number_format($max, 0, ',', '.') ?> Zeichen. Leer speichern leert den Abschnitt.</div>
      </div>
      <div class="field">
        <label for="grund">Grund der Änderung <span class="label-light muted">(optional)</span></label>
        <input class="input" id="grund" name="grund" maxlength="255" value="<?= $this->e($reason) ?>" placeholder="z. B. neuer Hangboard-Test">
      </div>
    </section>
    <div class="actions-sticky btn-row"><a class="btn btn-secondary" href="/profil#<?= $this->e($section) ?>">Abbrechen</a><button class="btn btn-primary" type="submit">Speichern</button></div>
  </form>
