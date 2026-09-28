<?php
/**
 * S9 Einheit geführt (AP-14, D-57; docs/konzept/gefuehrte-einheit.md 6.1–6.8, Mockup s9-einheit-gefuehrt.html).
 * Dasselbe Formular wie S3 (gleiche Feldnamen, Speichern über POST /einheit), je Übung bzw. Block ein Abschnitt.
 * Ohne JavaScript sind alle Abschnitte sichtbar und das Formular ist wie S3 ausfüllbar; js/gefuehrt.js zeigt jeweils
 * einen Schritt, führt Timer, Signale und Farben und merkt den Fortschritt im Browser.
 *
 * @var \Training\View\View $this
 * @var ?array $alert
 * @var array $session
 * @var list<array<string, mixed>> $steps Ablaufplan
 * @var array $data
 * @var array $invalid
 * @var string $csrf
 * @var string $stand
 * @var string $offlineLabel
 * @var bool $timerTon
 */
use Training\Plan\Ablaufplan;

$plan = $session['plan'];
$items = $session['type'] === 'klettern' ? $plan['blocks'] : $plan['exercises'];
$count = count($steps);
$kurz = trim((string) ($session['coach_summary'] ?? ''));
$mmss = static fn (int $s): string => sprintf('%02d:%02d', intdiv($s, 60), $s % 60);
$ohneSoll = static fn (string $soll): string => preg_replace('/^Soll /', '', $soll) ?? $soll;
$saetze = static function (array $st): string {
    if ($st['art'] === Ablaufplan::BLOCK) {
        return 'Block · ' . intdiv((int) $st['arbeit_s'], 60) . ' min'; // Block: ein Timer über die Dauer, Sätze stehen im Soll
    }
    if ($st['art'] === Ablaufplan::OFFEN) {
        return ($st['saetze'] > 1 ? $st['saetze'] . ' Sätze · ' : '') . 'ohne Zeitvorgabe';
    }
    $parts = [$st['saetze'] === 1 ? '1 Satz' : $st['saetze'] . ' Sätze'];
    if ($st['art'] === Ablaufplan::HALTEN) {
        $parts[] = 'je ' . $st['arbeit_s'] . ' s';
    }
    if ($st['pause_s'] !== null && $st['saetze'] > 1) {
        $parts[] = 'Pause ' . $st['pause_s'] . ' s';
    }
    return implode(' · ', $parts);
};
?>
<script src="/js/gefuehrt.js?v=<?= $this->e(\Training\App::VERSION) ?>"></script>
<?php if ($alert !== null): ?>
  <div class="mb-4"><?php include __DIR__ . '/_alert.php'; ?></div>
<?php endif ?>

  <form class="gf-form" method="post" action="/einheit" data-offline-form data-gefuehrt
        data-session="<?= (int) $session['id'] ?>" data-ablauf="<?= $this->e(Ablaufplan::json($steps)) ?>"
        data-ton="<?= $timerTon ? 'an' : 'aus' ?>" data-dauer-plan="<?= !empty($data['duration_from_plan']) ? '1' : '0' ?>"<?= $alert !== null ? ' data-fehler' : '' ?><?= !empty($invalid['ist']) ? ' data-ist-fehler' : '' ?>>
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="id" value="<?= (int) $session['id'] ?>">
    <input type="hidden" name="stand" value="<?= $this->e($stand) ?>">
    <input type="hidden" name="offline_label" value="<?= $this->e($offlineLabel) ?>">
    <input type="hidden" name="modus" value="start">

    <div class="sr-only" id="gf-ansage" aria-live="polite"></div>
    <div class="gf-progress needs-js" id="gf-fortschritt">
      <div class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?= $count ?>" aria-valuenow="0" aria-label="Fortschritt"><i id="gf-balken"></i></div>
      <div class="between small muted"><span id="gf-fortschritt-text">Übung 1 von <?= $count ?></span><span id="gf-uhr" class="mono"></span></div>
    </div>

    <div class="alert alert-info needs-js mb-4" id="gf-fortsetzen" hidden>
      <?= $this->icon('info-circle') ?>
      <div><b>Einheit schon begonnen.</b> <span class="body" id="gf-fortsetzen-text">Der Fortschritt ist auf diesem Gerät gespeichert.</span>
        <div class="btn-row mt-4"><button class="btn btn-primary" type="button" data-aktion="fortsetzen"><?= $this->icon('player-play') ?>Fortsetzen</button><button class="btn btn-secondary" type="button" data-aktion="neu">Neu starten</button></div>
      </div>
    </div>

<?php if ($kurz !== ''): ?>
    <p class="kurz gf-intro mb-4"><?= $this->e($kurz) ?></p>
<?php endif ?>

    <div class="stack-lg">
<?php foreach ($steps as $n => $st):
    $i = $st['index'];
    $item = $items[$i];
    $ist = $data['ist'][$i] ?? $item;
    $next = $steps[$n + 1] ?? null;
    $timed = $st['arbeit_s'] !== null;
?>
      <section class="gf-step stack-lg" data-step="<?= $n ?>" id="gf-schritt-<?= $n ?>" aria-labelledby="gf-name-<?= $n ?>"<?= !empty($invalid['ist_schritte'][$i]) ? ' data-invalid' : '' ?>>
        <div class="card phase-card">
          <div class="satz" data-satz-text="<?= $this->e($saetze($st)) ?>">Übung <?= $n + 1 ?> von <?= $count ?> · <?= $this->e($saetze($st)) ?></div>
          <h2 id="gf-name-<?= $n ?>"><?= $this->e($st['name']) ?></h2>
          <span class="phase needs-js"><span class="gf-phase-icon"></span><span class="gf-phase-text">Bereit</span></span>
<?php if ($timed): ?>
          <div class="timer" data-sekunden="<?= (int) $st['arbeit_s'] ?>"><?= $mmss((int) $st['arbeit_s']) ?></div>
<?php elseif ($st['art'] === Ablaufplan::WIEDERHOLUNGEN): ?>
          <div class="reps"><?= $this->e($item['reps']) ?> Wdh.<?php $extra = array_filter([$item['load'] ?? null, isset($item['tempo']) && $item['tempo'] !== '' ? 'Tempo ' . $item['tempo'] : null]); if ($extra !== []): ?><small><?= $this->e(implode(' · ', $extra)) ?></small><?php endif ?></div>
<?php if ($st['pause_s'] !== null && $st['saetze'] > 1): // Pausentimer nach „Satz erledigt“ (E-03), nur mit Skript ?>
          <div class="timer needs-js" data-sekunden="<?= (int) $st['pause_s'] ?>" hidden><?= $mmss((int) $st['pause_s']) ?></div>
<?php endif ?>
<?php endif ?>
          <div class="soll"><?= $this->e($st['soll']) ?></div>
<?php if ($st['notiz'] !== null): ?>
          <div class="soll"><?= $this->e($st['notiz']) ?></div>
<?php endif ?>
        </div>

        <section class="card<?= !empty($invalid['ist_schritte'][$i]) || (!empty($invalid['ist']) && empty($invalid['ist_schritte'])) ? ' invalid' : '' ?>">
          <div class="card-head"><h2>Ist</h2><span class="hint">vorbelegt mit Soll</span></div>
          <div class="exercise">
<?php if ($session['type'] === 'klettern'): $b = $item; include __DIR__ . '/_ist_block.php'; else: $x = $item; include __DIR__ . '/_ist_exercise.php'; endif ?>
          </div>
        </section>

        <div class="next"><?= $this->icon('arrow-right') ?><span>Als Nächstes: <?php if ($next !== null): ?><b><?= $this->e($next['name']) ?></b> · <?= $this->e($ohneSoll($next['soll'])) ?><?php else: ?><b>Abschluss</b> · Rückmeldung und Speichern<?php endif ?></span></div>
      </section>
<?php endforeach ?>

      <section class="gf-step stack-lg" data-step="abschluss" id="gf-abschluss">
        <section class="card stack">
          <div class="card-head"><h2>Abschluss</h2><span class="badge badge-success needs-js" id="gf-dauer-marke" hidden><?= $this->icon('check') ?><span></span></span></div>
          <div class="done-list">
<?php foreach ($steps as $n => $st): ?>
            <div class="row between" data-uebersicht="<?= $n ?>"><span><?= $this->e($st['name']) ?></span><span class="muted gf-uebersicht-soll"><?= $this->e($ohneSoll($st['soll'])) ?></span><span class="gf-uebersicht-status needs-js"></span></div>
<?php endforeach ?>
          </div>
          <p class="hint">Ist-Werte lassen sich später in der Einheit ändern.</p>
        </section>

        <section class="card stack-lg">
          <div class="card-head"><h2>Rückmeldung</h2><span class="hint">30 min nach Ende</span></div>
<?php include __DIR__ . '/_feedback_fields.php'; ?>
        </section>

        <div class="actions-sticky btn-row gf-save">
          <button class="btn btn-secondary btn-icon needs-js" type="button" data-aktion="zurueck-abschluss" aria-label="Zurück zur letzten Übung"><?= $this->icon('player-skip-back') ?></button>
          <a class="btn btn-secondary" href="/einheit?id=<?= (int) $session['id'] ?>">Zur Einheit</a>
          <button class="btn btn-primary" type="submit">Speichern</button>
        </div>
      </section>
    </div>

    <div class="actions-sticky gf-actions needs-js" id="gf-aktionen" hidden>
      <button class="btn btn-secondary btn-icon" type="button" data-aktion="links" aria-label="Zurück"><span class="gf-icon"></span></button>
      <button class="btn btn-primary" type="button" data-aktion="haupt"><span class="gf-icon"></span><span class="gf-text">Start</span></button>
      <button class="btn btn-secondary" type="button" data-aktion="rechts" aria-label="Übung überspringen"><span class="gf-icon"></span><span class="gf-text hide-mobile">Überspringen</span></button>
    </div>

    <template id="gf-icons">
<?php foreach (['player-play', 'player-pause', 'player-skip-back', 'player-skip-forward', 'check', 'arrow-right', 'clock'] as $name): ?>
      <span data-icon="<?= $name ?>"><?= $this->icon($name) ?></span>
<?php endforeach ?>
    </template>
  </form>
