<?php
/** S8 Unterseite: Erinnerung an Blockbilanz und Zielklärung, Vorlauf und Blocktermin im Kalender (AP-15, 6.3, E-22). */
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var string $csrf */
/** @var array{overlay: bool, bilanz: int, zielklaerung: int, beginn: string, dauer_min: int, erinnerung_h: int} $values */
?>
  <div class="page-head"><div class="eyebrow"><a href="/einstellungen">Einstellungen</a></div><h1>Blockbilanz und Zielklärung</h1><p class="muted small">Erinnerung auf der Webseite und Termin im Kalender, wenn am Blockende Bilanz und Zielklärung anstehen. Erstellt werden beide im Projekt-Chat.</p></div>
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>
  <form class="stack-lg mt-4" method="post" action="/einstellungen">
    <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
    <input type="hidden" name="action" value="blockreview">
    <section class="card stack-lg">
      <h2 class="h-card">Erinnerung auf der Webseite</h2>
      <label class="check"><input type="checkbox" name="overlay" value="an"<?= $values['overlay'] ? ' checked' : '' ?>> Hinweisfenster zeigen, solange Bilanz oder Zielklärung fällig sind (täglich, bis bestätigt)</label>
      <div class="field">
        <label for="bilanz_vorlauf">Vorlauf Blockbilanz (Tage vor Blockende, 0–28)</label>
        <input class="input" type="number" id="bilanz_vorlauf" name="bilanz_vorlauf" min="0" max="28" step="1" inputmode="numeric" value="<?= (int) $values['bilanz'] ?>">
      </div>
      <div class="field">
        <label for="zielklaerung_vorlauf">Vorlauf Zielklärung (Tage vor Blockende, 0–42)</label>
        <input class="input" type="number" id="zielklaerung_vorlauf" name="zielklaerung_vorlauf" min="0" max="42" step="1" inputmode="numeric" value="<?= (int) $values['zielklaerung'] ?>">
      </div>
    </section>
    <section class="card stack-lg">
      <h2 class="h-card">Termin im Kalender</h2>
      <p class="muted small">Ein Termin je Block am Blockende. Änderungen werden sofort für alle geplanten und aktiven Blöcke übertragen.</p>
      <div class="field">
        <label for="beginn">Beginn</label>
        <input class="input" type="time" id="beginn" name="beginn" step="300" value="<?= $this->e($values['beginn']) ?>">
      </div>
      <div class="field">
        <label for="dauer">Dauer (Minuten, 30–480)</label>
        <input class="input" type="number" id="dauer" name="dauer" min="30" max="480" step="15" inputmode="numeric" value="<?= (int) $values['dauer_min'] ?>">
      </div>
      <div class="field">
        <label for="erinnerung_h">Erinnerung (Stunden vor Beginn, 0–168; 24 = Vortag zur selben Uhrzeit)</label>
        <input class="input" type="number" id="erinnerung_h" name="erinnerung_h" min="0" max="168" step="1" inputmode="numeric" value="<?= (int) $values['erinnerung_h'] ?>">
      </div>
    </section>
    <div class="actions-sticky btn-row"><a class="btn btn-secondary" href="/einstellungen">Abbrechen</a><button class="btn btn-primary" type="submit">Speichern</button></div>
  </form>
