/*
 * AP-14 T5: Kern der geführten Einheit ohne Browser (docs/konzept/gefuehrte-einheit.md 6.3–6.5, Testfälle 8.2).
 * Aufruf: node --test server/tests/js/
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const K = require('../../public/js/gefuehrt.js');

const HALTEN = { index: 0, name: 'Unterarmstütz', art: 'halten', saetze: 3, arbeit_s: 45, pause_s: 60 };
const WDH = { index: 1, name: 'Kniebeuge', art: 'wiederholungen', saetze: 3, arbeit_s: null, pause_s: 90 };
const BLOCK = { index: 2, name: 'Bouldern Volumen', art: 'block', saetze: 1, arbeit_s: 2400, pause_s: null };
const OFFEN = { index: 3, name: 'Technik', art: 'offen', saetze: 1, arbeit_s: null, pause_s: null };

function start(steps, o = {}) {
  return K.neuerZustand(steps, { stand: 'abc', jetzt: 0, ...o });
}

/** Takt alle 250 ms von „von“ bis „bis“; liefert Zustand und Signale mit Zeitpunkt. */
function simuliere(z, steps, von, bis, schritt = 250) {
  const signale = [];
  let vorher = von;
  for (let t = von + schritt; t <= bis; t += schritt) {
    const r = K.takt(z, steps, vorher, t);
    z = r.zustand;
    r.signale.forEach((m) => signale.push({ t, m }));
    vorher = t;
  }
  return { z, signale };
}

test('Signalplan: 30 s nur bei Phasen über 45 s (Z-01), 10 s ab 15 s, 3-2-1 soweit die Phase reicht', () => {
  assert.deepEqual(K.signalPlan(46000).map((p) => p.muster), ['s30', 's10', 't3', 't2', 't1']);
  assert.deepEqual(K.signalPlan(45000).map((p) => p.muster), ['s10', 't3', 't2', 't1']);
  assert.deepEqual(K.signalPlan(15000).map((p) => p.muster), ['s10', 't3', 't2', 't1']);
  assert.deepEqual(K.signalPlan(14000).map((p) => p.muster), ['t3', 't2', 't1']);
  assert.deepEqual(K.signalPlan(3000).map((p) => p.muster), ['t2', 't1'], 'kein Tick zeitgleich mit dem Phasenbeginn');
  assert.deepEqual(K.signaleZwischen(K.signalPlan(60000), 10200, 9950), ['s10']);
  assert.deepEqual(K.signaleZwischen(K.signalPlan(60000), 10000, 9750), [], 'Schwelle schon vorher erreicht');
});

test('Z-01 Halten 45 s: Start grün mit Startton, Töne bei 10 s und 3-2-1, kein 30-s-Ton', () => {
  const steps = [HALTEN];
  let r = K.aktion(start(steps), steps, 'haupt', 0);
  assert.deepEqual(r.signale, ['start']);
  assert.equal(r.zustand.phase, 'arbeit');
  assert.equal(r.zustand.begonnen_um, 0);
  const v = K.anzeige(r.zustand, steps, 0);
  assert.equal(v.farbe, 'arbeit');
  assert.equal(v.zeit_ms, 45000);
  assert.equal(v.haupt.text, 'Anhalten');
  assert.equal(v.satz_text, 'Satz 1 von 3 · dann 60 s Pause');
  const s = simuliere(r.zustand, steps, 0, 44999);
  assert.deepEqual(s.signale, [{ t: 35000, m: 's10' }, { t: 42000, m: 't3' }, { t: 43000, m: 't2' }, { t: 44000, m: 't1' }]);
});

test('Z-02 Ende Satz 1: Pause 60 s rot ohne eigenen Ton, dann 30 s, 10 s, 3-2-1 und Start von Satz 2', () => {
  const steps = [HALTEN];
  const z = K.aktion(start(steps), steps, 'haupt', 0).zustand;
  const s = simuliere(z, steps, 43000, 105000);
  const pause = K.takt(z, steps, 44750, 45000).zustand;
  assert.equal(pause.phase, 'pause');
  assert.equal(K.anzeige(pause, steps, 45000).farbe, 'pause');
  assert.equal(K.anzeige(pause, steps, 45000).satz_text, 'Pause · dann Satz 2 von 3');
  assert.deepEqual(s.signale.map((x) => x.t + ':' + x.m), ['44000:t1', '75000:s30', '95000:s10', '102000:t3', '103000:t2', '104000:t1', '105000:start']);
  assert.equal(s.z.phase, 'arbeit');
  assert.equal(s.z.satz, 2);
  assert.equal(s.z.end_at, 150000, 'Zeitstempel ab dem Pausenende, nicht ab dem Takt');
});

test('Z-03 Ende Satz 3: Abschlusston, fertig, normale Farbe, Knopf „Weiter“', () => {
  const steps = [HALTEN, OFFEN];
  const z = K.aktion(start(steps), steps, 'haupt', 0).zustand;
  const s = simuliere(z, steps, 0, 255000);
  assert.deepEqual(s.signale.filter((x) => x.m === 'start' || x.m === 'ende').map((x) => x.t + ':' + x.m), ['105000:start', '210000:start', '255000:ende']);
  assert.equal(s.z.phase, 'fertig');
  assert.deepEqual(s.z.fertig, [0]);
  const v = K.anzeige(s.z, steps, 255000);
  assert.equal(v.farbe, null);
  assert.equal(v.haupt.text, 'Weiter');
  assert.equal(v.rechts.sichtbar, false);
  const weiter = K.aktion(s.z, steps, 'haupt', 256000).zustand;
  assert.deepEqual([weiter.schritt, weiter.satz, weiter.phase], [1, 1, 'bereit'], 'Weiter von Hand zur nächsten Übung (E-03)');
});

test('Z-04 Anhalten bei 20 s Rest: rot, rest_ms 20 000; Fortsetzen läuft mit 20 s weiter', () => {
  const steps = [HALTEN];
  const z = K.aktion(start(steps), steps, 'haupt', 0).zustand;
  const halt = K.aktion(z, steps, 'haupt', 25000).zustand;
  assert.equal(halt.phase, 'angehalten');
  assert.equal(halt.rest_ms, 20000);
  assert.equal(K.anzeige(halt, steps, 90000).farbe, 'angehalten');
  assert.equal(K.anzeige(halt, steps, 90000).zeit_ms, 20000, 'Zeit steht');
  assert.deepEqual(K.takt(halt, steps, 25000, 90000).signale, [], 'angehalten: keine Signale, kein Übergang');
  const weiter = K.aktion(halt, steps, 'haupt', 90000).zustand;
  assert.equal(weiter.phase, 'arbeit');
  assert.equal(weiter.end_at, 110000);
  // Anhalten in der Pause (linker Knopf) und Fortsetzen kehrt in die Pause zurück
  const inPause = K.takt(z, steps, 44750, 50000).zustand;
  const haltPause = K.aktion(inPause, steps, 'links', 50000).zustand;
  assert.deepEqual([haltPause.phase, haltPause.rest_ms, haltPause.vor_anhalten], ['angehalten', 55000, 'pause']);
  assert.equal(K.aktion(haltPause, steps, 'haupt', 60000).zustand.phase, 'pause');
});

test('Z-05 2 min im Hintergrund während 45-s-Phase: Folgephase korrekt, ein Hinweiston, nichts nachgeholt', () => {
  const steps = [HALTEN];
  const z = K.aktion(start(steps), steps, 'haupt', 0).zustand;
  const r = K.takt(z, steps, 10000, 130000);
  assert.deepEqual(r.signale, ['hinweis']);
  assert.equal(r.uebergaenge, 2, 'Arbeit → Pause → Arbeit Satz 2');
  assert.deepEqual([r.zustand.phase, r.zustand.satz, r.zustand.end_at], ['arbeit', 2, 150000]);
  assert.equal(K.anzeige(r.zustand, steps, 130000).zeit_ms, 20000);
  // Lücke ohne Phasenende: kein Hinweiston
  assert.deepEqual(K.takt(z, steps, 1000, 20000).signale, []);
  // bis über das Ende der Übung hinaus: fertig, ein Hinweiston
  const lang = K.takt(z, steps, 1000, 400000);
  assert.deepEqual([lang.zustand.phase, lang.signale], ['fertig', ['hinweis']]);
});

test('Z-06 Neu laden: Fortsetzen-Frage mit gleichem Stand; neu bei Verfall, anderem Plan oder inzwischen gespeichert', () => {
  const steps = [HALTEN, WDH];
  const z = K.aktion(start(steps), steps, 'haupt', 0).zustand;
  z.gespeichert_am = 1000;
  const raw = JSON.stringify(z);
  const f = K.laden(raw, { steps, stand: 'abc', jetzt: 5000 });
  assert.equal(f.aktion, 'fragen');
  assert.deepEqual([f.zustand.schritt, f.zustand.satz, f.zustand.phase, f.zustand.end_at], [0, 1, 'arbeit', 45000]);
  assert.equal(K.laden(raw, { steps, stand: 'abc', jetzt: 1000 + K.VERFALL_MS + 1 }).aktion, 'neu', '12 h');
  assert.equal(K.laden(raw, { steps: [HALTEN], stand: 'abc', jetzt: 5000 }).aktion, 'neu', 'Plan geändert');
  assert.deepEqual(K.laden(raw, { steps, stand: 'neu', jetzt: 5000 }), { aktion: 'neu', grund: 'geaendert' });
  assert.deepEqual(K.laden(JSON.stringify({ ...z, abgeschickt: 2000 }), { steps, stand: 'neu', jetzt: 5000 }), { aktion: 'neu', grund: 'gespeichert' });
  assert.deepEqual(K.laden(null, { steps, stand: 'abc', jetzt: 5000 }), { aktion: 'neu', grund: 'leer' });
  assert.deepEqual(K.laden('{kaputt', { steps, stand: 'abc', jetzt: 5000 }), { aktion: 'neu', grund: 'leer' });
  const fehler = K.laden(JSON.stringify({ ...z, abgeschickt: 2000 }), { steps, stand: 'neu', jetzt: 5000, fehler: true });
  assert.deepEqual([fehler.aktion, fehler.zustand.stand, fehler.zustand.abgeschickt], ['fehler', 'neu', null], 'nach 422/409 bleibt der Fortschritt');
  // nach dem Neuladen rechnet der erste Takt ab dem letzten Merken nach: Phase inzwischen zu Ende → Hinweiston
  const r = K.takt(f.zustand, steps, 1000, 50000);
  assert.deepEqual([r.zustand.phase, r.signale], ['pause', ['hinweis']]);
});

test('Z-07 Wiederholungen 3 Sätze, Pause 90 s: „Satz erledigt“ startet den Pausentimer, nach Satz 3 fertig ohne Timer', () => {
  const steps = [WDH];
  let z = start(steps);
  let v = K.anzeige(z, steps, 0);
  assert.deepEqual([v.farbe, v.zeige_timer, v.haupt.text, v.phase_text], [null, false, 'Satz erledigt', null], 'ohne Timer normale Farbe (E-02)');
  z = K.aktion(z, steps, 'haupt', 0).zustand;
  assert.deepEqual([z.phase, z.end_at], ['pause', 90000]);
  v = K.anzeige(z, steps, 0);
  assert.deepEqual([v.farbe, v.zeige_timer, v.zeit_ms], ['pause', true, 90000]);
  const s = simuliere(z, steps, 0, 90000);
  assert.deepEqual(s.signale.map((x) => x.m), ['s30', 's10', 't3', 't2', 't1', 'start']);
  assert.deepEqual([s.z.phase, s.z.satz], ['bereit', 2]);
  z = K.aktion(s.z, steps, 'haupt', 100000).zustand;
  z = K.aktion(z, steps, 'haupt', 110000).zustand; // Pause beenden (vorzeitig)
  assert.deepEqual([z.phase, z.satz], ['bereit', 3]);
  z = K.aktion(z, steps, 'haupt', 130000).zustand;
  assert.deepEqual([z.phase, z.end_at], ['fertig', null]);
  assert.equal(K.anzeige(z, steps, 130000).zeige_timer, false);
});

test('Z-08/Z-09: Stumm ist Teil des Zustands, Vorgabe aus der Einstellung', () => {
  const steps = [HALTEN];
  assert.equal(start(steps, { stumm: true }).stumm, true, 'S8 timer_ton = aus → S9 startet stumm');
  assert.equal(start(steps).stumm, false);
  const z = { ...start(steps, { stumm: true }), gespeichert_am: 1 };
  assert.equal(K.laden(JSON.stringify(z), { steps, stand: 'abc', jetzt: 2 }).zustand.stumm, true, 'Umschalten gilt für diese Einheit und bleibt beim Neuladen');
});

test('Z-10 Überspringen: Status-Vorbelegung teilweise; Z-11 gemessene Dauer in Minuten', () => {
  const steps = [WDH, OFFEN];
  let z = K.aktion(start(steps), steps, 'haupt', 60000).zustand; // erster Satz 60 s nach dem Öffnen
  z = K.aktion(z, steps, 'rechts', 70000).zustand;
  assert.deepEqual([z.schritt, z.uebersprungen], [1, [0]]);
  z = K.aktion(z, steps, 'haupt', 80000).zustand; // Erledigt
  assert.equal(K.dauerMinuten(z, 61000), 1, 'mindestens 1');
  const ende = 60000 + 41 * 60000 + 20000;
  z = K.aktion(z, steps, 'haupt', ende).zustand; // Zum Abschluss
  const v = K.anzeige(z, steps, ende);
  assert.equal(v.abschluss, true);
  assert.equal(v.fortschritt.text, '1 von 2 Übungen erledigt');
  assert.equal(K.statusVorbelegung(z), 'teilweise');
  assert.equal(K.dauerMinuten(z, ende), 41);
  assert.equal(K.dauerMinuten(z, ende + 30 * 60000), 41, 'Warten im Abschluss zählt nicht (Review T5)');
  assert.equal(K.dauerMinuten(start(steps), 61000), null, 'ohne Start keine Messung');
  // zurück zur letzten Übung und nochmals vor: Messung bleibt, solange nicht wieder trainiert wird
  const zurueck = K.aktion(z, steps, 'zurueck-abschluss', ende + 60000).zustand;
  assert.deepEqual([zurueck.schritt, zurueck.phase], [1, 'fertig']);
  const wieder = K.aktion(zurueck, steps, 'haupt', ende + 120000).zustand;
  assert.equal(K.dauerMinuten(wieder, ende + 30 * 60000), 41);
  const nochmal = K.aktion(K.aktion(zurueck, steps, 'links', ende + 60000).zustand, steps, 'haupt', ende + 5 * 60000).zustand; // Übung wiederholen
  assert.equal(nochmal.ende_um, null, 'wieder trainiert: neu messen');
  assert.equal(K.dauerMinuten(K.aktion(nochmal, steps, 'haupt', ende + 9 * 60000).zustand, ende + 60 * 60000), 50);
  const alle = K.aktion(K.aktion(K.aktion(start(steps), steps, 'haupt', 0).zustand, steps, 'haupt', 1).zustand, steps, 'haupt', 2).zustand;
  assert.equal(alle.phase, 'pause');
});

test('Z-12 Offline abgeschickt: Fortschritt bleibt bis der Server den neuen Stand zeigt', () => {
  const steps = [OFFEN];
  const z = { ...K.aktion(start(steps), steps, 'haupt', 0).zustand, abgeschickt: 5000, gespeichert_am: 5000 };
  const offline = K.laden(JSON.stringify(z), { steps, stand: 'abc', jetzt: 60000 });
  assert.equal(offline.aktion, 'fragen', 'gespeicherte Seite ohne Netz: alter Stand → nicht löschen');
  assert.equal(offline.zustand.abgeschickt, 5000);
  assert.equal(K.laden(JSON.stringify(z), { steps, stand: 'geliefert', jetzt: 60000 }).grund, 'gespeichert');
});

test('Block, offen und Bedienung zurück/wiederholen', () => {
  const steps = [BLOCK, OFFEN];
  let z = K.aktion(start(steps), steps, 'haupt', 0).zustand;
  assert.equal(K.anzeige(z, steps, 0).satz_text, 'Block läuft');
  const s = simuliere(z, steps, 0, 2400000, 1000);
  assert.deepEqual(s.signale.map((x) => x.m), ['s30', 's10', 't3', 't2', 't1', 'ende']);
  z = s.z;
  assert.equal(K.anzeige(z, steps, 2400000).haupt.text, 'Weiter');
  z = K.aktion(z, steps, 'links', 2400001).zustand; // Übung wiederholen
  assert.deepEqual([z.phase, z.fertig], ['bereit', []]);
  assert.equal(K.anzeige(z, steps, 0).satz_text, 'Block · 40 min');
  z = K.aktion(z, steps, 'rechts', 2400002).zustand;
  let v = K.anzeige(z, steps, 0);
  assert.deepEqual([v.haupt.text, v.links.label, v.links.aktiv, v.satz_text], ['Erledigt', 'Vorige Übung', true, 'ohne Zeitvorgabe']);
  const mitSaetzen = [{ ...OFFEN, saetze: 4 }];
  assert.equal(K.anzeige(start(mitSaetzen), mitSaetzen, 0).satz_text, '4 Sätze · ohne Zeitvorgabe', 'offen mit geplanten Sätzen');
  z = K.aktion(z, steps, 'links', 2400003).zustand; // vorige Übung: nicht mehr übersprungen
  assert.deepEqual([z.schritt, z.uebersprungen], [0, []]);
  assert.equal(K.anzeige(start(steps), steps, 0).links.aktiv, false, 'erste Übung: kein Zurück');
  // Arbeit: links = Satz neu starten
  z = K.aktion(z, steps, 'haupt', 0).zustand;
  z = K.aktion(z, steps, 'links', 1000).zustand;
  assert.deepEqual([z.phase, z.end_at], ['bereit', null]);
  v = K.anzeige(z, steps, 1000);
  assert.equal(v.farbe, 'bereit', 'Timer vorhanden, nicht gestartet: rot (E-02)');
});

test('Hangboard 7 s / 3 s × 6: Ticks in der kurzen Pause, Start bei jedem Hang, Abschlusston am Ende', () => {
  const steps = [{ index: 0, name: 'Hangboard', art: 'halten', saetze: 6, arbeit_s: 7, pause_s: 3 }];
  const z = K.aktion(start(steps), steps, 'haupt', 0).zustand;
  const s = simuliere(z, steps, 0, 57000, 250);
  assert.equal(s.signale.filter((x) => x.m === 'start').length, 5, 'Satz 2–6');
  assert.equal(s.signale.filter((x) => x.m === 'ende').length, 1);
  assert.equal(s.signale.find((x) => x.m === 'ende').t, 57000, '6 × 7 s + 5 × 3 s');
  assert.equal(s.z.phase, 'fertig');
});

test('Halten ohne Pause mit mehreren Sätzen: nächste Arbeitsphase direkt mit Startton', () => {
  const steps = [{ index: 0, name: 'Wandsitz', art: 'halten', saetze: 2, arbeit_s: 120, pause_s: null }];
  const z = K.aktion(start(steps), steps, 'haupt', 0).zustand;
  const r = K.takt(z, steps, 119900, 120100);
  assert.deepEqual([r.zustand.phase, r.zustand.satz, r.signale], ['arbeit', 2, ['start']]);
});

test('Review T5: Tipp direkt nach einem automatischen Phasenwechsel gilt der angezeigten Phase und wird verworfen', () => {
  const wandsitz = { index: 0, name: 'Wandsitz', art: 'halten', saetze: 1, arbeit_s: 45, pause_s: null };
  let steps = [wandsitz, WDH];
  let z = K.takt(K.aktion(start(steps), steps, 'haupt', 0).zustand, steps, 0, 44900).zustand; // Anzeige „Anhalten“
  let r = K.tipp(z, steps, 'haupt', 44900, 45100, null);
  assert.equal(r.verworfen, true);
  assert.deepEqual([r.zustand.schritt, r.zustand.phase, r.signale], [0, 'fertig', ['ende']], 'kein Sprung zur nächsten Übung (E-03)');
  // Wiederholungen mit Pause: „Pause beenden“ genau am Pausenende zählt keinen Satz
  steps = [WDH];
  z = K.aktion(start(steps), steps, 'haupt', 0).zustand; // Satz 1 erledigt → Pause 90 s
  r = K.tipp(z, steps, 'haupt', 89900, 90100, null);
  assert.deepEqual([r.verworfen, r.zustand.satz, r.zustand.phase], [true, 2, 'bereit']);
  // Halten mit Pause: „Anhalten“ am Ende der Arbeit überspringt die Pause nicht
  steps = [HALTEN];
  z = K.aktion(start(steps), steps, 'haupt', 0).zustand;
  r = K.tipp(z, steps, 'haupt', 44900, 45100, null);
  assert.deepEqual([r.verworfen, r.zustand.phase, r.zustand.satz], [true, 'pause', 1]);
  // Sperre kurz nach einem automatischen Wechsel im Takt, danach wirkt der Tipp
  r = K.tipp(r.zustand, steps, 'haupt', 45100, 45300, 45100);
  assert.equal(r.verworfen, true, 'innerhalb von SPERRE_MS');
  r = K.tipp(r.zustand, steps, 'haupt', 45300, 45100 + K.SPERRE_MS, 45100);
  assert.deepEqual([r.verworfen, r.zustand.phase, r.zustand.satz], [false, 'arbeit', 2], 'Pause beenden');
  // gewöhnlicher Tipp ohne Wechsel
  r = K.tipp(K.aktion(start(steps), steps, 'haupt', 0).zustand, steps, 'haupt', 10000, 10100, null);
  assert.deepEqual([r.verworfen, r.zustand.phase], [false, 'angehalten']);
});

test('Review T5: Zurück und Weiter behalten erledigte Übungen; nachgeholte Übung ist nicht mehr übersprungen', () => {
  const steps = [HALTEN, OFFEN];
  let z = K.takt(K.aktion(start(steps), steps, 'haupt', 0).zustand, steps, 0, 400000).zustand;
  assert.deepEqual(z.fertig, [0]);
  z = K.aktion(z, steps, 'haupt', 400001).zustand; // Weiter
  z = K.aktion(z, steps, 'links', 400002).zustand; // Vorige Übung
  assert.deepEqual([z.schritt, z.phase, z.fertig, z.satz], [0, 'fertig', [0], 3], 'bleibt erledigt');
  assert.equal(K.anzeige(z, steps, 400002).haupt.text, 'Weiter');
  z = K.aktion(z, steps, 'haupt', 400003).zustand; // Weiter
  assert.deepEqual([z.schritt, z.phase], [1, 'bereit']);
  z = K.aktion(z, steps, 'rechts', 400004).zustand; // letzte Übung überspringen
  assert.deepEqual(z.uebersprungen, [1]);
  z = K.aktion(z, steps, 'zurueck-abschluss', 400005).zustand;
  assert.deepEqual([z.schritt, z.phase, z.uebersprungen], [1, 'bereit', []], 'zurück zur übersprungenen letzten Übung');
  z = K.aktion(K.aktion(z, steps, 'haupt', 400006).zustand, steps, 'haupt', 400007).zustand; // Erledigt, Zum Abschluss
  assert.equal(K.statusVorbelegung(z), 'erledigt');
  assert.equal(K.anzeige(z, steps, 400007).fortschritt.text, 'Alle 2 Übungen durch');
  // Weiter in eine schon erledigte Übung: bleibt erledigt
  let w = K.aktion(z, steps, 'zurueck-abschluss', 400008).zustand;
  w = K.aktion(w, steps, 'links', 400009).zustand; // Übung wiederholen (fertig → bereit)
  assert.deepEqual([w.schritt, w.phase, w.fertig], [1, 'bereit', [0]]);
  w = K.aktion(w, steps, 'links', 400010).zustand; // vorige Übung
  w = K.aktion(w, steps, 'haupt', 400011).zustand; // Weiter
  assert.deepEqual([w.schritt, w.phase], [1, 'bereit'], 'wiederholte Übung ist offen');
  // übersprungene Übung später doch erledigt
  let u = K.aktion(start(steps), steps, 'rechts', 0).zustand;
  u = K.aktion(u, steps, 'links', 1).zustand;
  u = K.takt(K.aktion(u, steps, 'haupt', 2).zustand, steps, 2, 400000).zustand;
  assert.deepEqual([u.fertig, u.uebersprungen], [[0], []]);
});

test('Review T5: Schritt öffnen nach Ist-Fehler; Fortsetzen nach Speichern ohne Takt holt keine Signale nach', () => {
  const steps = [HALTEN, OFFEN];
  let z = K.takt(K.aktion(start(steps), steps, 'haupt', 0).zustand, steps, 0, 400000).zustand;
  z = K.aktion(K.aktion(K.aktion(z, steps, 'haupt', 1).zustand, steps, 'rechts', 2).zustand, steps, 'haupt', 3).zustand;
  const o = K.oeffne({ ...z, schritt: steps.length }, steps, 0);
  assert.deepEqual([o.schritt, o.phase, o.fertig, o.uebersprungen], [0, 'fertig', [0], [1]], 'Erledigt/Übersprungen unverändert');
  assert.equal(K.oeffne(z, steps, 1).phase, 'bereit');
  // Stumm im Fortsetzen-Dialog speichert (gespeichert_am nach end_at), Fortsetzen innerhalb von 2 s
  const lauf = K.aktion(start([HALTEN]), [HALTEN], 'haupt', 0).zustand;
  const r = K.takt(lauf, [HALTEN], 300000, 301000);
  assert.deepEqual(r.signale, ['hinweis'], 'nur der Hinweiston, keine 23 Töne');
});

test('Review T9: letzter Satz nennt keine Pause mehr (Wiederholungen, Kletterblock satzweise, Halten)', () => {
  const zug = { index: 0, name: 'Zugkraft', art: 'wiederholungen', saetze: 4, arbeit_s: null, pause_s: 120 };
  let z = start([zug]);
  assert.equal(K.anzeige(z, [zug], 0).satz_text, 'Satz 1 von 4 · Pause 120 s nach „Satz erledigt“');
  for (let i = 0; i < 3; i++) {
    z = K.aktion(z, [zug], 'haupt', i * 200000).zustand; // Satz erledigt → Pause
    z = K.takt(z, [zug], i * 200000, i * 200000 + 121000).zustand; // Pause vorbei → nächster Satz bereit
  }
  const v = K.anzeige(z, [zug], 700000);
  assert.deepEqual([z.satz, z.phase, v.satz_text, v.haupt.text], [4, 'bereit', 'Satz 4 von 4', 'Satz erledigt']);
  z = K.aktion(z, [zug], 'haupt', 700001).zustand;
  assert.equal(z.phase, 'fertig', 'nach dem letzten Satz keine Pause');
  const halten = { ...HALTEN, saetze: 2 };
  const letzter = { ...start([halten]), satz: 2 };
  assert.equal(K.anzeige(letzter, [halten], 0).satz_text, 'Satz 2 von 2');
  assert.equal(K.anzeige(start([halten]), [halten], 0).satz_text, 'Satz 1 von 2 · Pause 60 s');
});
