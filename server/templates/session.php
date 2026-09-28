<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var array $session */
/** @var ?array $activity */
/** @var array $data */
/** @var array $invalid */
/** @var string $csrf */
use Training\Dates;
use Training\View\Labels;

[$typeLabel, $typeIcon] = Labels::TYPES[$session['type']];
$plan = $session['plan'] ?? [];
$fmtSoll = static function (array $x): string {
    $parts = [$x['sets'] . ' × ' . $x['reps']];
    foreach (['load' => '%s', 'tempo' => 'Tempo %s', 'rest_s' => 'Pause %s s'] as $k => $f) {
        if (isset($x[$k]) && $x[$k] !== '') { $parts[] = sprintf($f, $x[$k]); }
    }
    return 'Soll ' . implode(' · ', $parts);
};
$fmtBlock = static function (array $b): string {
    $parts = [];
    if (isset($b['duration_min'])) { $parts[] = $b['duration_min'] . ' min'; }
    if (isset($b['target'])) { $parts[] = $b['target']; }
    if ($b['kind'] === 'hangboard' || isset($b['edge_mm'])) {
        $h = [];
        if (isset($b['edge_mm'])) { $h[] = $b['edge_mm'] . ' mm'; }
        if (isset($b['grip'])) { $h[] = Labels::GRIPS[$b['grip']] ?? $b['grip']; }
        if (isset($b['hang_s'])) { $h[] = $b['hang_s'] . ' s'; }
        if (isset($b['sets'])) { $h[] = $b['sets'] . ' Sätze'; }
        if (isset($b['added_load_kg'])) { $h[] = ($b['added_load_kg'] >= 0 ? '+' : '') . str_replace('.', ',', (string) $b['added_load_kg']) . ' kg'; }
        if (isset($b['rest_s'])) { $h[] = 'Pause ' . $b['rest_s'] . ' s'; }
        if ($h !== []) { $parts[] = implode(', ', $h); }
    } elseif (isset($b['sets'])) {
        $parts[] = $b['sets'] . ' Sätze';
    }
    return 'Soll ' . ($parts === [] ? '–' : implode(' · ', $parts));
};
$meta = $typeLabel . ' · ' . Dates::long($session['date']) . ' · Priorität ' . $session['priority'] . ($session['planned_duration_min'] !== null ? ' · ' . $session['planned_duration_min'] . ' min' : '');
?>
  <div class="page-head">
    <div class="eyebrow"><?= $this->icon($typeIcon, 'ic ic-brand') ?><?= $this->e($meta) ?></div>
    <h1><?= $this->e($session['title']) ?></h1>
<?php if (!empty($session['coach_rationale'])): ?>
    <p class="muted small">Trainer-Notiz: <?= $this->e($session['coach_rationale']) ?></p>
<?php endif ?>
  </div>
<?php if ($alert !== null): ?>
  <div class="mt-4"><?php include __DIR__ . '/_alert.php'; ?></div>
<?php endif ?>

  <form class="two-col mt-4" method="post" action="/einheit">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="id" value="<?= (int) $session['id'] ?>">
    <div class="stack-lg">
<?php if (isset($plan['exercises'])): ?>
      <section class="card<?= !empty($invalid['ist']) ? ' invalid' : '' ?>">
        <div class="card-head"><h2>Plan und Ist</h2><span class="hint">Ist ist mit Soll vorbelegt</span></div>
<?php foreach ($plan['exercises'] as $i => $x): $ist = $data['ist'][$i] ?? $x; ?>
        <div class="exercise">
          <div class="between"><span class="name"><?= $this->e($x['name']) ?></span><span class="soll"><?= $this->e($fmtSoll($x)) ?></span></div>
<?php if (!empty($x['notes'])): ?><div class="soll"><?= $this->e($x['notes']) ?></div><?php endif ?>
          <div class="ist">
            <div class="field"><label for="ist-<?= $i ?>-sets">Sätze</label><input class="input mono" id="ist-<?= $i ?>-sets" name="ist[<?= $i ?>][sets]" value="<?= $this->e($ist['sets'] ?? '') ?>" inputmode="numeric"></div>
            <div class="field"><label for="ist-<?= $i ?>-reps">Wdh.</label><input class="input mono" id="ist-<?= $i ?>-reps" name="ist[<?= $i ?>][reps]" value="<?= $this->e($ist['reps'] ?? '') ?>"></div>
            <div class="field"><label for="ist-<?= $i ?>-load">Last</label><input class="input mono" id="ist-<?= $i ?>-load" name="ist[<?= $i ?>][load]" value="<?= $this->e($ist['load'] ?? '') ?>"></div>
          </div>
        </div>
<?php endforeach ?>
      </section>
<?php elseif (isset($plan['blocks'])): ?>
      <section class="card<?= !empty($invalid['ist']) ? ' invalid' : '' ?>">
        <div class="card-head"><h2>Blöcke</h2><span class="hint">Ist ist mit Soll vorbelegt</span></div>
<?php foreach ($plan['blocks'] as $i => $b): $ist = $data['ist'][$i] ?? $b; ?>
        <div class="exercise">
          <div class="between"><span class="name"><?= $this->e(Labels::BLOCK_KINDS[$b['kind']] ?? $b['kind']) ?></span><?php if (!empty($b['spezifitaet'])): ?><span class="badge badge-brand"><?= $this->e($b['spezifitaet']) ?></span><?php endif ?></div>
          <div class="soll"><?= $this->e($fmtBlock($b)) ?></div>
<?php if (!empty($b['notes'])): ?><div class="soll"><?= $this->e($b['notes']) ?></div><?php endif ?>
          <div class="ist">
            <div class="field"><label for="ist-<?= $i ?>-dur">Dauer (min)</label><input class="input mono" id="ist-<?= $i ?>-dur" name="ist[<?= $i ?>][duration_min]" value="<?= $this->e($ist['duration_min'] ?? '') ?>" inputmode="numeric"></div>
<?php if (isset($b['sets'])): ?>
            <div class="field"><label for="ist-<?= $i ?>-sets">Sätze</label><input class="input mono" id="ist-<?= $i ?>-sets" name="ist[<?= $i ?>][sets]" value="<?= $this->e($ist['sets'] ?? '') ?>" inputmode="numeric"></div>
            <div class="field"><label for="ist-<?= $i ?>-notes">Notiz</label><input class="input" id="ist-<?= $i ?>-notes" name="ist[<?= $i ?>][notes]" value="<?= $this->e(($data['ist'][$i]['notes'] ?? null) !== ($b['notes'] ?? null) ? ($ist['notes'] ?? '') : '') ?>" placeholder="optional"></div>
<?php else: ?>
            <div class="field span-2"><label for="ist-<?= $i ?>-notes">Notiz</label><input class="input" id="ist-<?= $i ?>-notes" name="ist[<?= $i ?>][notes]" value="<?= $this->e(($data['ist'][$i]['notes'] ?? null) !== ($b['notes'] ?? null) ? ($ist['notes'] ?? '') : '') ?>" placeholder="optional"></div>
<?php endif ?>
          </div>
        </div>
<?php endforeach ?>
      </section>
<?php elseif (isset($plan['intervals_workout_text'])): ?>
      <section class="card">
        <div class="card-head"><h2>Plan</h2><?php if ($session['intervals_event_id'] !== null): ?><span class="badge badge-info"><?= $this->icon('plug-connected') ?>auf der Uhr</span><?php endif ?></div>
        <div class="stack">
          <p><?= $this->e($plan['summary']) ?></p>
          <pre class="mono small plan-text"><?= $this->e($plan['intervals_workout_text']) ?></pre>
<?php if (!empty($plan['notes'])): ?><div class="hint"><?= $this->e($plan['notes']) ?></div><?php endif ?>
        </div>
      </section>
<?php include __DIR__ . '/_activity.php'; ?>
<?php endif ?>
    </div>

    <div class="stack-lg sticky">
      <section class="card stack-lg">
        <div class="card-head"><h2>Rückmeldung</h2><span class="hint">30 min nach Ende</span></div>

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
      </section>

      <div class="actions-sticky btn-row">
        <a class="btn btn-secondary" href="/woche?start=<?= Dates::monday($session['date']) ?>">Abbrechen</a>
        <button class="btn btn-primary" type="submit">Speichern</button>
      </div>
    </div>
  </form>
