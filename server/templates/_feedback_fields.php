<?php
/**
 * Rückmeldung (S3 und Abschluss S9): Dauer, RPE, Gefühl, Schmerz, Abweichung, Notiz, Status.
 * @var array $data @var array $invalid
 */
use Training\View\Labels;
?>
        <div class="field<?= !empty($invalid['duration']) ? ' invalid' : '' ?>">
          <label for="duration">Dauer (min)</label>
          <input class="input mono" id="duration" name="duration_min" value="<?= $this->e($data['duration_min'] ?? '') ?>" inputmode="numeric">
        </div>

        <div class="field<?= !empty($invalid['rpe']) ? ' invalid' : '' ?>">
          <div class="field-label">Anstrengung (RPE 0–10)</div>
<?php $name = 'rpe'; $min = 0; $max = 10; $value = $data['rpe'] !== null ? (int) $data['rpe'] : null; $aria = 'RPE'; include __DIR__ . '/_scale.php'; ?>
          <div class="scale-ends"><span>0 Ruhe</span><span>10 maximal</span></div>
          <div class="hint">Die Belastung (sRPE) wird automatisch berechnet: RPE × Dauer in Minuten.</div>
        </div>

        <div class="field<?= !empty($invalid['feel']) ? ' invalid' : '' ?>">
          <div class="field-label">Wie hat sich die Einheit angefühlt?</div>
<?php $name = 'feel'; $min = 1; $max = 5; $value = $data['feel'] !== null ? (int) $data['feel'] : null; $aria = 'Gefühl'; include __DIR__ . '/_scale.php'; ?>
          <div class="scale-ends"><span>1 sehr gut</span><span>5 sehr schlecht</span></div>
        </div>

        <div class="field">
          <div class="field-label">Schmerz während oder nach der Einheit?</div>
<?php $name = 'pain'; $options = ['nein' => 'Nein', 'ja' => 'Ja']; $value = $data['pain_choice'] ?? 'nein'; $wrap = false; $icons = []; include __DIR__ . '/_seg.php'; ?>
        </div>

        <div class="reveal subcard">
<?php $pain = $data['pain_fields']; include __DIR__ . '/_pain_fields.php'; ?>
        </div>

        <div class="field">
          <label for="dev">Abweichung vom Plan</label>
          <div class="select-wrap">
            <select class="select" id="dev" name="deviation">
<?php foreach (Labels::DEVIATIONS as $v => $label): ?>
              <option value="<?= $v ?>"<?= ($data['deviation'] ?? '') === $v ? ' selected' : '' ?>><?= $label ?></option>
<?php endforeach ?>
            </select>
            <?= $this->icon('chevron-right') ?>
          </div>
        </div>

        <div class="field">
          <label for="note">Notiz</label>
          <textarea class="textarea" id="note" name="notes" placeholder="Was war anders, was ist aufgefallen?"><?= $this->e($data['notes'] ?? '') ?></textarea>
        </div>

        <div class="field<?= !empty($invalid['status']) ? ' invalid' : '' ?>">
          <div class="field-label">Status</div>
<?php $name = 'status'; $options = ['erledigt' => 'Erledigt', 'teilweise' => 'Teilweise', 'ausgelassen' => 'Ausgelassen', 'verschoben' => 'Verschoben']; $value = $data['status']; $wrap = true; $icons = ['erledigt' => 'check', 'verschoben' => 'player-skip-forward']; include __DIR__ . '/_seg.php'; ?>
          <div class="hint">Bei „Ausgelassen“ und „Verschoben“ sind Dauer, RPE und Gefühl nicht nötig.</div>
        </div>
