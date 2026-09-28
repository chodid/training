<?php
/** Check-in-Formular mit Morgen-Check-in (AP-12), genutzt auf /checkin und in der Wochenansicht. */
/** @var \Training\View\View $this */
/** @var string $csrf */
/** @var string $date */
/** @var string $stand */
/** @var string $offlineLabel */
/** @var array $data */
/** @var array $invalid */
/** @var bool $handVisible */
use Training\View\Labels;

$more = !empty($invalid['nacken_bws']) || !empty($invalid['hand_rechts']) || $data['nacken_bws'] !== null || $data['hand_rechts'] !== null
    || $data['osg_umgeknickt'] || $data['warnzeichen'] !== [] || $data['pain_choice'] === 'ja' || $data['notes'] !== '';
?>
  <form class="stack-lg" method="post" action="/checkin" data-offline-form>
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="datum" value="<?= $this->e($date) ?>">
    <input type="hidden" name="stand" value="<?= $this->e($stand) ?>">
    <input type="hidden" name="offline_label" value="<?= $this->e($offlineLabel) ?>">
    <section class="card stack-lg">
      <div class="field<?= !empty($invalid['mt_links']) || !empty($invalid['mt_rechts']) ? ' invalid' : '' ?>">
        <div class="field-label">Morgentest Patellasehne</div>
        <p class="muted small">Einbeiniger Squat langsam bis ca. 60° Kniebeugung, je Seite. Stärkster Schmerz an der Patellaspitze. 0 = kein Schmerz, 10 = stärkster vorstellbarer. Nicht getestet: leer lassen; erneutes Tippen leert den Wert.</p>
        <div class="label">Links</div>
<?php $name = 'mt_links'; $min = 0; $max = 10; $value = $data['mt_links']; $aria = 'Morgentest links'; $clearable = true; include __DIR__ . '/_scale.php'; ?>
        <div class="label">Rechts</div>
<?php $name = 'mt_rechts'; $min = 0; $max = 10; $value = $data['mt_rechts']; $aria = 'Morgentest rechts'; include __DIR__ . '/_scale.php'; $clearable = false; ?>
      </div>

      <div class="field<?= !empty($invalid['recovery']) ? ' invalid' : '' ?>">
        <div class="field-label">Erholt und leistungsbereit</div>
<?php $name = 'recovery'; $min = 1; $max = 5; $value = $data['recovery'] !== null ? (int) $data['recovery'] : null; $aria = 'Erholung'; include __DIR__ . '/_scale.php'; ?>
        <div class="scale-ends"><span>1 sehr gut</span><span>5 sehr schlecht</span></div>
      </div>

      <div class="field<?= !empty($invalid['soreness']) ? ' invalid' : '' ?>">
        <div class="field-label">Muskelkater</div>
<?php $name = 'soreness'; $min = 1; $max = 5; $value = $data['soreness'] !== null ? (int) $data['soreness'] : null; $aria = 'Muskelkater'; include __DIR__ . '/_scale.php'; ?>
        <div class="scale-ends"><span>1 keiner</span><span>5 stark</span></div>
      </div>

      <details class="more"<?= $more ? ' open' : '' ?>>
        <summary>Weitere Angaben <span class="hint">Nacken, Sprunggelenk, <?= $handVisible ? 'Hand, ' : '' ?>Warnzeichen, Schmerz, Notiz</span></summary>
        <div class="stack-lg mt-4">
          <div class="field<?= !empty($invalid['nacken_bws']) ? ' invalid' : '' ?>">
            <div class="field-label">Nacken/Brustwirbelsäule beim Aufstehen <span class="muted label-light">(optional, 0–10)</span></div>
<?php $name = 'nacken_bws'; $min = 0; $max = 10; $value = $data['nacken_bws']; $aria = 'Nacken/BWS'; $clearable = true; include __DIR__ . '/_scale.php'; $clearable = false; ?>
          </div>

          <div class="field osg">
            <div class="field-label">Sprunggelenk links</div>
            <label class="check"><input type="checkbox" name="osg_umgeknickt" value="1"<?= $data['osg_umgeknickt'] ? ' checked' : '' ?>> Seit dem letzten Check-in umgeknickt</label>
            <label class="check osg-schwellung"><input type="checkbox" name="osg_schwellung" value="1"<?= $data['osg_schwellung'] ? ' checked' : '' ?>> Schwellung</label>
          </div>
<?php if ($handVisible): ?>

          <div class="field<?= !empty($invalid['hand_rechts']) ? ' invalid' : '' ?>">
            <div class="field-label">Hand rechts <span class="muted label-light">(optional, 0–10)</span></div>
<?php $name = 'hand_rechts'; $min = 0; $max = 10; $value = $data['hand_rechts']; $aria = 'Hand rechts'; $clearable = true; include __DIR__ . '/_scale.php'; $clearable = false; ?>
          </div>
<?php endif ?>

          <div class="field">
            <div class="field-label">Warnzeichen <span class="muted label-light">(falls zutreffend)</span></div>
<?php foreach (Labels::WARNINGS as $key => $text): ?>
            <label class="check"><input type="checkbox" name="warnzeichen[]" value="<?= $this->e($key) ?>"<?= in_array($key, $data['warnzeichen'], true) ? ' checked' : '' ?>> <?= $this->e($text) ?></label>
<?php endforeach ?>
          </div>

          <div class="field">
            <div class="field-label">Schmerz <span class="muted label-light">(Ereignis)</span></div>
<?php $name = 'pain'; $options = ['nein' => 'Nein', 'ja' => 'Ja']; $value = $data['pain_choice']; $wrap = false; $icons = []; include __DIR__ . '/_seg.php'; ?>
          </div>

          <div class="reveal subcard">
            <div class="between"><b>Schmerzereignis</b><span class="hint">Kurzform, Details in <a href="/schmerz?datum=<?= $this->e($date) ?>">Schmerz</a></span></div>
<?php $pain = $data['pain_fields']; include __DIR__ . '/_pain_fields.php'; ?>
          </div>

          <div class="field">
            <label for="note">Notiz <span class="muted label-light">(optional)</span></label>
            <input class="input" id="note" name="notes" value="<?= $this->e($data['notes']) ?>" placeholder="Schlaf, Stress, Besonderes" maxlength="500">
          </div>
        </div>
      </details>
    </section>

    <div class="actions-sticky btn-row">
      <button class="btn btn-primary btn-block" type="submit">Speichern</button>
    </div>
  </form>
  <script src="/js/checkin.js" defer></script>
