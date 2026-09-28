/*
 * Geführte Einheit S9 (AP-14, D-57/D-58; docs/konzept/gefuehrte-einheit.md 6.3–6.8).
 * Zeigt jeweils einen Schritt des Ablaufplans (data-ablauf), führt Arbeit/Pause/Sätze einer Übung automatisch durch
 * (E-03), Timer zeitstempelbasiert (E-16), Signale per Web Audio und Vibration (E-17), Farben Arbeit grün,
 * Pause/bereit/angehalten rot (E-02, E-20), Bildschirm an (Wake Lock, E-06), Stumm je Einheit (E-18) und merkt den
 * Fortschritt im Browser (sessionStorage, E-19). Gespeichert wird einmal am Ende über das S3-Formular (E-07).
 * Ohne JavaScript bleibt die Seite ein vollständiges Formular (alle Schritte sichtbar).
 *
 * Der Kern (Zustand, Übergänge, Signalplanung, Anzeige) ist ohne Browser testbar: server/tests/js/gefuehrt.test.cjs.
 */
(function (global) {
  'use strict';

  // ---------- Kern: reine Funktionen ----------

  const LUECKE_MS = 2000;               // größerer Abstand zwischen zwei Takten = Seite war im Hintergrund (E-16)
  const SPERRE_MS = 500;                // Tipps so kurz nach einem automatischen Phasenwechsel galten der alten Phase
  const VERFALL_MS = 12 * 3600 * 1000;  // Fortschritt verfällt nach 12 h (6.2)
  const SCHLUESSEL = 'training.gefuehrt.';

  /** Signale (E-17, 6.4): Töne [Versatz ms, Dauer ms, Frequenz Hz] und Vibrationsmuster */
  const MUSTER = {
    start: { toene: [[0, 80, 880], [160, 80, 880]], vibration: [80, 80, 80] },
    s30: { toene: [[0, 150, 660]], vibration: [150] },
    s10: { toene: [[0, 150, 660]], vibration: [150] },
    t3: { toene: [[0, 60, 990]], vibration: [60] },
    t2: { toene: [[0, 60, 990]], vibration: [60] },
    t1: { toene: [[0, 60, 990]], vibration: [60] },
    ende: { toene: [[0, 400, 523]], vibration: [300] },
    hinweis: { toene: [[0, 250, 660]], vibration: [250] },
  };

  /**
   * Signale innerhalb einer Phase: 30 s vor Ende (Phase länger als 45 s – Testfall Z-01: bei 45 s kein 30-s-Ton),
   * 10 s vor Ende (Phase ab 15 s), 3-2-1 (immer, soweit die Phase so lang ist).
   */
  function signalPlan(dauerMs) {
    const plan = [];
    if (dauerMs > 45000) {
      plan.push({ vorEnde: 30000, muster: 's30' });
    }
    if (dauerMs >= 15000) {
      plan.push({ vorEnde: 10000, muster: 's10' });
    }
    [3, 2, 1].forEach((k) => {
      if (k * 1000 < dauerMs) {
        plan.push({ vorEnde: k * 1000, muster: 't' + k });
      }
    });
    return plan;
  }

  /** Signale, deren Zeitpunkt zwischen zwei Takten überschritten wurde (Restzeit vorher > Schwelle ≥ Restzeit nachher). */
  function signaleZwischen(plan, restVorher, restNachher) {
    return plan.filter((p) => restVorher > p.vorEnde && restNachher <= p.vorEnde).map((p) => p.muster);
  }

  function signatur(steps) {
    return steps.map((s) => [s.art, s.saetze, s.arbeit_s, s.pause_s].join(':')).join('|');
  }

  function neuerZustand(steps, o) {
    return {
      version: 1, signatur: signatur(steps), stand: o.stand || '', begonnen_um: null,
      schritt: 0, satz: 1, phase: 'bereit', end_at: null, phase_ms: null, rest_ms: null, vor_anhalten: null,
      uebersprungen: [], fertig: [], stumm: !!o.stumm, ist: {}, dauer_manuell: false, status_manuell: false, ende_um: null,
      abgeschickt: null, gespeichert_am: o.jetzt || 0,
    };
  }

  function kopie(z) {
    return JSON.parse(JSON.stringify(z));
  }

  function istAbschluss(z, steps) {
    return z.schritt >= steps.length;
  }

  function getimt(st) {
    return st.arbeit_s !== null; // halten, block
  }

  function laeuft(z) {
    return z.phase === 'arbeit' || z.phase === 'pause';
  }

  function pauseNachSatz(st, satz) {
    return st.pause_s !== null && satz < st.saetze ? st.pause_s * 1000 : null;
  }

  function ohne(liste, i) {
    return liste.filter((x) => x !== i);
  }

  function starteArbeit(z, st, ab) {
    z.phase = 'arbeit';
    z.phase_ms = st.arbeit_s * 1000;
    z.end_at = ab + z.phase_ms;
  }

  function startePause(z, ms, ab) {
    z.phase = 'pause';
    z.phase_ms = ms;
    z.end_at = ab + ms;
  }

  function setzeBereit(z, schritt, satz) {
    Object.assign(z, { schritt, satz, phase: 'bereit', end_at: null, phase_ms: null, rest_ms: null, vor_anhalten: null });
  }

  function setzeFertig(z) {
    Object.assign(z, { phase: 'fertig', end_at: null, phase_ms: null, rest_ms: null, vor_anhalten: null });
    if (!z.fertig.includes(z.schritt)) {
      z.fertig.push(z.schritt);
    }
    z.uebersprungen = ohne(z.uebersprungen, z.schritt); // nachgeholt
  }

  /** Schritt i öffnen: eine schon erledigte Übung bleibt erledigt („Weiter“), sonst „bereit“ ab Satz 1. */
  function oeffneSchritt(z, steps, i) {
    setzeBereit(z, i, 1);
    if (i < steps.length && z.fertig.includes(i)) {
      z.satz = steps[i].saetze;
      setzeFertig(z);
    }
  }

  /** Ende der laufenden Phase zum Zeitpunkt end_at: Folgephase zeitstempelgenau, Signale des Übergangs. */
  function phaseEnde(z, steps) {
    const st = steps[z.schritt];
    const ende = z.end_at;
    if (z.phase === 'arbeit') {
      const p = pauseNachSatz(st, z.satz);
      if (p !== null) {
        startePause(z, p, ende);
        return [];
      }
      if (z.satz < st.saetze) {
        z.satz++;
        starteArbeit(z, st, ende);
        return ['start'];
      }
      setzeFertig(z);
      return ['ende'];
    }
    // Pause zu Ende: nächster Satz – getimt sofort die nächste Arbeitsphase, bei Wiederholungen „bereit“ für den Satz
    z.satz++;
    if (getimt(st)) {
      starteArbeit(z, st, ende);
    } else {
      Object.assign(z, { phase: 'bereit', end_at: null, phase_ms: null });
    }
    return ['start'];
  }

  /**
   * Zeitfortschritt zwischen zwei Takten (E-16): Phasen enden nach ihren Zeitstempeln, auch mehrere hintereinander.
   * Signale nur bei regelmäßigem Takt; nach einer Lücke (Hintergrund, Bildschirm aus) werden keine Signale nachgeholt,
   * sondern ein Hinweiston, falls inzwischen eine Phase endete.
   */
  function takt(z0, steps, vorher, jetzt) {
    const z = kopie(z0);
    const signale = [];
    let uebergaenge = 0;
    // Lücke auch, wenn die laufende Phase schon vor dem letzten Takt endete (z. B. Fortsetzen nach einem Speichern
    // ohne Takt): sonst kämen alle Signale der Zwischenzeit auf einmal
    const luecke = vorher !== null && (jetzt - vorher > LUECKE_MS || (laeuft(z0) && z0.end_at !== null && z0.end_at < vorher));
    let t = vorher === null ? jetzt : vorher;
    while (laeuft(z) && !istAbschluss(z, steps)) {
      const bis = Math.min(jetzt, z.end_at);
      if (!luecke) {
        signale.push(...signaleZwischen(signalPlan(z.phase_ms), z.end_at - t, z.end_at - bis));
      }
      if (z.end_at > jetzt) {
        break;
      }
      t = z.end_at;
      const s = phaseEnde(z, steps);
      uebergaenge++;
      if (!luecke) {
        signale.push(...s);
      }
    }
    if (luecke && uebergaenge > 0) {
      signale.push('hinweis');
    }
    return { zustand: z, signale, uebergaenge };
  }

  /**
   * Bedienung (6.3): haupt (Start, Satz erledigt, Erledigt, Anhalten, Pause beenden, Fortsetzen, Weiter), links
   * (Satz bzw. Übung zurück, Satz neu starten, Anhalten in der Pause, Übung wiederholen), rechts (Übung überspringen),
   * zurueck-abschluss (vom Abschluss zur letzten Übung).
   */
  function aktion(z0, steps, name, jetzt) {
    const z = bedienung(z0, steps, name, jetzt);
    if (!istAbschluss(z0, steps) && istAbschluss(z.zustand, steps) && z.zustand.ende_um == null) {
      z.zustand.ende_um = jetzt; // Ende der Messung (Z-11): Warten auf die Rückmeldung zählt nicht mit
    }
    return z;
  }

  function bedienung(z0, steps, name, jetzt) {
    const z = kopie(z0);
    const signale = [];
    if (name === 'zurueck-abschluss') {
      if (istAbschluss(z, steps) && steps.length > 0) {
        const i = steps.length - 1;
        z.uebersprungen = ohne(z.uebersprungen, i);
        oeffneSchritt(z, steps, i);
      }
      return { zustand: z, signale };
    }
    const st = steps[z.schritt];
    if (!st) {
      return { zustand: z, signale };
    }
    const anhalten = () => {
      Object.assign(z, { vor_anhalten: z.phase, rest_ms: Math.max(0, z.end_at - jetzt), end_at: null, phase: 'angehalten' });
    };
    if (name === 'haupt') {
      if (z.phase === 'bereit') {
        if (z.begonnen_um === null) {
          z.begonnen_um = jetzt;
        }
        z.ende_um = null; // es wird wieder trainiert: Dauer beim nächsten Abschluss neu messen
        if (getimt(st)) {
          starteArbeit(z, st, jetzt);
          signale.push('start');
        } else if (st.art === 'wiederholungen') {
          if (z.satz < st.saetze) {
            const p = pauseNachSatz(st, z.satz);
            if (p !== null) {
              startePause(z, p, jetzt); // Pausentimer startet nach „Satz erledigt“ automatisch (E-03)
            } else {
              z.satz++;
            }
          } else {
            setzeFertig(z);
          }
        } else {
          setzeFertig(z);
        }
      } else if (z.phase === 'arbeit') {
        anhalten();
      } else if (z.phase === 'pause') {
        z.satz++;
        if (getimt(st)) {
          starteArbeit(z, st, jetzt);
          signale.push('start');
        } else {
          Object.assign(z, { phase: 'bereit', end_at: null, phase_ms: null });
        }
      } else if (z.phase === 'angehalten') {
        Object.assign(z, { phase: z.vor_anhalten || 'arbeit', end_at: jetzt + z.rest_ms, rest_ms: null, vor_anhalten: null });
      } else if (z.phase === 'fertig') {
        oeffneSchritt(z, steps, z.schritt + 1); // nächste Übung; eine schon erledigte bleibt erledigt
      }
    } else if (name === 'links') {
      if (z.phase === 'bereit') {
        if (z.satz > 1) {
          z.satz--;
        } else if (z.schritt > 0) {
          const i = z.schritt - 1; // vorige Übung: erledigt bleibt erledigt, übersprungen wird wieder offen
          z.uebersprungen = ohne(z.uebersprungen, i);
          oeffneSchritt(z, steps, i);
        }
      } else if (z.phase === 'arbeit' || z.phase === 'angehalten') {
        setzeBereit(z, z.schritt, z.satz); // Satz neu starten
      } else if (z.phase === 'pause') {
        anhalten();
      } else if (z.phase === 'fertig') {
        z.fertig = ohne(z.fertig, z.schritt);
        setzeBereit(z, z.schritt, 1); // Übung wiederholen
      }
    } else if (name === 'rechts' && z.phase !== 'fertig') {
      if (!z.uebersprungen.includes(z.schritt)) {
        z.uebersprungen.push(z.schritt);
      }
      z.fertig = ohne(z.fertig, z.schritt);
      setzeBereit(z, z.schritt + 1, 1);
    }
    return { zustand: z, signale };
  }

  /**
   * Tipp auf einen Knopf: erst die offene Zeit nachziehen (takt), dann die Bedienung anwenden. Hat sich dabei die Phase
   * geändert oder liegt der letzte automatische Wechsel weniger als SPERRE_MS zurück, galt der Tipp der zuvor
   * angezeigten Phase und wird verworfen (sonst z. B. „Anhalten“ → nächste Übung, „Pause beenden“ → Satz erledigt).
   */
  function tipp(z0, steps, name, vorher, jetzt, letzterWechsel) {
    const r0 = takt(z0, steps, vorher, jetzt);
    if (r0.uebergaenge > 0 || (letzterWechsel !== null && jetzt - letzterWechsel < SPERRE_MS)) {
      return { zustand: r0.zustand, signale: r0.signale, uebergaenge: r0.uebergaenge, verworfen: true };
    }
    const r1 = aktion(r0.zustand, steps, name, jetzt);
    return { zustand: r1.zustand, signale: r0.signale.concat(r1.signale), uebergaenge: 0, verworfen: false };
  }

  /** Schritt i öffnen, ohne Erledigt/Übersprungen zu ändern (z. B. Ist-Fehler nach dem Speichern). */
  function oeffne(z0, steps, i) {
    const z = kopie(z0);
    oeffneSchritt(z, steps, i);
    return z;
  }

  function sekunden(ms) {
    return Math.max(0, Math.ceil(ms / 1000));
  }

  function mmss(s) {
    return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
  }

  /** Anzeige für den aktuellen Zustand: Texte, Farbe, Zeit, Knöpfe, Fortschritt (6.2, 6.4, 6.8). */
  function anzeige(z, steps, jetzt) {
    const n = steps.length;
    const erledigt = steps.filter((s, i) => z.fertig.includes(i) || z.uebersprungen.includes(i)).length;
    const v = {
      abschluss: istAbschluss(z, steps), schritt: z.schritt, farbe: null, zeit_ms: null, zeige_timer: false,
      satz_text: '', phase_text: null, phase_icon: null, ansage: '',
      links: { icon: 'player-skip-back', label: 'Vorige Übung', aktiv: z.schritt > 0 },
      haupt: { icon: 'player-play', text: 'Start' },
      rechts: { icon: 'player-skip-forward', text: 'Überspringen', label: 'Übung überspringen', sichtbar: true },
      fortschritt: { anteil: n > 0 ? erledigt / n : 0, text: 'Übung ' + Math.min(z.schritt + 1, n) + ' von ' + n },
    };
    if (v.abschluss) {
      const uebersprungen = z.uebersprungen.length;
      v.fortschritt = { anteil: 1, text: uebersprungen === 0 ? 'Alle ' + n + ' Übungen durch' : (n - uebersprungen) + ' von ' + n + ' Übungen erledigt' };
      v.ansage = 'Abschluss: Rückmeldung und Speichern';
      return v;
    }
    const st = steps[z.schritt];
    const timed = getimt(st);
    const satzVon = 'Satz ' + z.satz + ' von ' + st.saetze;
    const pause = st.pause_s !== null && st.saetze > 1 ? st.pause_s : null;
    const phase = z.phase === 'angehalten' ? z.vor_anhalten : z.phase;

    if (z.phase === 'bereit') {
      if (timed) {
        v.farbe = 'bereit';
        v.zeige_timer = true;
        v.zeit_ms = st.arbeit_s * 1000;
        v.phase_text = 'Bereit';
        v.phase_icon = 'clock';
        v.satz_text = st.art === 'block' ? 'Block · ' + Math.round(st.arbeit_s / 60) + ' min' : satzVon + (pause !== null ? ' · Pause ' + pause + ' s' : '');
        v.haupt = { icon: 'player-play', text: 'Start' };
      } else if (st.art === 'wiederholungen') {
        v.satz_text = satzVon + (pause !== null ? ' · Pause ' + pause + ' s nach „Satz erledigt“' : '');
        v.haupt = { icon: 'check', text: 'Satz erledigt' };
      } else {
        v.satz_text = (st.saetze > 1 ? st.saetze + ' Sätze · ' : '') + 'ohne Zeitvorgabe';
        v.haupt = { icon: 'check', text: 'Erledigt' };
      }
      if (z.satz > 1) {
        v.links = { icon: 'player-skip-back', label: 'Satz zurück', aktiv: true };
      }
      v.ansage = st.name + ': ' + (v.phase_text || v.satz_text);
    } else if (z.phase === 'fertig') {
      v.satz_text = 'Übung erledigt';
      v.phase_text = 'Erledigt';
      v.phase_icon = 'check';
      v.haupt = { icon: 'arrow-right', text: z.schritt + 1 < n ? 'Weiter' : 'Zum Abschluss' };
      v.links = { icon: 'player-skip-back', label: 'Übung wiederholen', aktiv: true };
      v.rechts.sichtbar = false;
      v.zeige_timer = timed;
      v.zeit_ms = timed ? 0 : null;
      v.ansage = st.name + ': erledigt';
    } else {
      // arbeit, pause, angehalten
      const rest = z.phase === 'angehalten' ? z.rest_ms : z.end_at - jetzt;
      v.zeige_timer = true;
      v.zeit_ms = Math.max(0, rest);
      if (phase === 'arbeit') {
        const naechste = pause !== null && z.satz < st.saetze ? ' · dann ' + pause + ' s Pause' : (z.satz < st.saetze ? ' · dann nächster Satz' : ' · letzter Satz');
        v.satz_text = st.art === 'block' ? 'Block läuft' : satzVon + naechste;
        v.phase_text = 'Arbeit';
        v.phase_icon = 'player-play';
      } else {
        v.satz_text = 'Pause · dann Satz ' + (z.satz + 1) + ' von ' + st.saetze;
        v.phase_text = 'Pause';
        v.phase_icon = 'player-pause';
      }
      if (z.phase === 'angehalten') {
        v.farbe = 'angehalten';
        v.satz_text = 'Angehalten · ' + v.satz_text;
        v.phase_text = 'Angehalten';
        v.phase_icon = 'player-pause';
        v.haupt = { icon: 'player-play', text: 'Fortsetzen' };
        v.links = { icon: 'player-skip-back', label: 'Satz neu starten', aktiv: true };
      } else if (z.phase === 'arbeit') {
        v.farbe = 'arbeit';
        v.haupt = { icon: 'player-pause', text: 'Anhalten' };
        v.links = { icon: 'player-skip-back', label: 'Satz neu starten', aktiv: true };
      } else {
        v.farbe = 'pause';
        v.haupt = { icon: 'player-play', text: 'Pause beenden' };
        v.links = { icon: 'player-pause', label: 'Anhalten', aktiv: true };
      }
      v.ansage = st.name + ': ' + v.phase_text + ' – ' + sekunden(v.zeit_ms) + ' Sekunden';
    }
    return v;
  }

  /** Status-Vorbelegung im Abschluss (6.2): teilweise, wenn eine Übung übersprungen wurde. */
  function statusVorbelegung(z) {
    return z.uebersprungen.length > 0 ? 'teilweise' : 'erledigt';
  }

  /** Ende der Messung: Erreichen des Abschlusses, sonst jetzt. */
  function messEnde(z, jetzt) {
    return z.ende_um !== null && z.ende_um !== undefined ? z.ende_um : jetzt;
  }

  /** Gemessene Dauer vom ersten Start bis zum Abschluss in ganzen Minuten (mindestens 1), null ohne Start (6.2, Z-11). */
  function dauerMinuten(z, jetzt) {
    return z.begonnen_um === null ? null : Math.max(1, Math.round((messEnde(z, jetzt) - z.begonnen_um) / 60000));
  }

  /**
   * Gespeicherten Fortschritt prüfen (E-19): verfallen (12 h), anderer Plan, inzwischen gespeichert (Stand der Einheit
   * geändert) → neu; nach einem Fehler beim Speichern (data-fehler) gilt der Fortschritt, die Eingaben kommen vom Server.
   */
  function laden(raw, o) {
    let z = null;
    try {
      z = raw ? JSON.parse(raw) : null;
    } catch (e) {
      z = null;
    }
    const gueltig = z !== null && typeof z === 'object' && z.version === 1 && z.signatur === signatur(o.steps) && o.jetzt - (z.gespeichert_am || 0) <= VERFALL_MS;
    if (o.fehler) {
      if (!gueltig) {
        return { aktion: 'fehler', zustand: null };
      }
      z.stand = o.stand;
      z.abgeschickt = null;
      return { aktion: 'fehler', zustand: z };
    }
    if (!gueltig) {
      return { aktion: 'neu', grund: z === null ? 'leer' : 'ungueltig' };
    }
    if (z.stand !== o.stand) {
      return { aktion: 'neu', grund: z.abgeschickt ? 'gespeichert' : 'geaendert' };
    }
    return { aktion: 'fragen', zustand: z };
  }

  const Kern = {
    LUECKE_MS, SPERRE_MS, VERFALL_MS, SCHLUESSEL, MUSTER, signalPlan, signaleZwischen, signatur, neuerZustand, takt,
    aktion, tipp, oeffne, anzeige, statusVorbelegung, dauerMinuten, messEnde, laden, mmss, sekunden,
  };

  if (typeof module === 'object' && module.exports) {
    module.exports = Kern;
  }
  if (typeof document === 'undefined') {
    return;
  }

  // ---------- Seite ----------

  // Sofort (Skript steht vor den Schritten): Schritte erst nach der Einrichtung zeigen, kein Aufblitzen aller Übungen
  document.documentElement.classList.add('js');
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', sicherEinrichten);
  } else {
    sicherEinrichten();
  }

  /** Scheitert die Einrichtung, bleibt die Seite ein vollständiges Formular (wie ohne JavaScript). */
  function sicherEinrichten() {
    try {
      einrichten();
    } catch (e) {
      document.documentElement.classList.remove('js');
      document.body.removeAttribute('data-phase');
      if (window.console) {
        console.error('Geführte Einheit:', e);
      }
    }
  }

  function einrichten() {
    const form = document.querySelector('form[data-gefuehrt]');
    if (!form) {
      document.documentElement.classList.remove('js');
      return;
    }
    let steps;
    try {
      steps = JSON.parse(form.getAttribute('data-ablauf') || '[]');
    } catch (e) {
      steps = null;
    }
    if (!Array.isArray(steps) || steps.length === 0) {
      document.documentElement.classList.remove('js'); // Rückfall: Formular wie ohne JavaScript
      return;
    }
    const schluessel = SCHLUESSEL + form.getAttribute('data-session');
    const stand = (form.querySelector('input[name="stand"]') || {}).value || '';
    const tonStandard = form.getAttribute('data-ton') !== 'aus';
    const dauerAusPlan = form.getAttribute('data-dauer-plan') === '1';
    const abschnitte = Array.from(form.querySelectorAll('.gf-step'));
    const aktionen = document.getElementById('gf-aktionen');
    const knopf = { links: aktionen.querySelector('[data-aktion="links"]'), haupt: aktionen.querySelector('[data-aktion="haupt"]'), rechts: aktionen.querySelector('[data-aktion="rechts"]') };
    const fortsetzen = document.getElementById('gf-fortsetzen');
    const intro = form.querySelector('.gf-intro');
    const ansage = document.getElementById('gf-ansage');
    const stummKnopf = document.getElementById('gf-stumm');
    const themeColor = document.querySelector('meta[name="theme-color"]');
    const icons = document.getElementById('gf-icons');
    const THEME = { arbeit: '#DEF2D9', pause: '#FFE4E5', bereit: '#FFE4E5', angehalten: '#FFE4E5', normal: '#7A5C94' }; // --status-*-bg, Pflaume 600
    const OHNE = ['csrf', 'id', 'stand', 'offline_label', 'modus'];

    let z = null;
    let modus = 'lauf'; // lauf | fragen | aus (Rückfall auf das Formular ohne Skript)
    let gemerkt = false; // Fortschritt liegt im Speicher (erst nach der ersten Eingabe)
    let letzterTakt = null;
    let letzterWechsel = null; // Zeitpunkt des letzten automatischen Phasenwechsels (Tipp-Sperre)
    let letzteAnsage = '';
    let letzterSchritt = null;
    let audio = null;
    let sperre = null;
    let blinkUhr = null;
    const stummSchluessel = schluessel + '.stumm'; // Stumm-Wahl dieser Einheit, auch vor der ersten Eingabe (E-18)

    function icon(name) {
      const quelle = icons && icons.content.querySelector('[data-icon="' + name + '"]');
      return quelle ? quelle.cloneNode(true) : document.createElement('span');
    }

    function setzeIcon(ziel, name) {
      if (ziel.getAttribute('data-icon-name') === name) {
        return;
      }
      ziel.textContent = '';
      ziel.appendChild(icon(name));
      ziel.setAttribute('data-icon-name', name);
    }

    function text(el, t) {
      if (el && el.textContent !== t) {
        el.textContent = t;
      }
    }

    // ----- Speicher (sessionStorage; fehlt er, läuft die Einheit ohne Merken) -----
    function speichern() {
      z.gespeichert_am = Date.now();
      try {
        sessionStorage.setItem(schluessel, JSON.stringify(z));
        gemerkt = true;
      } catch (e) {
        // privater Modus o. Ä.: nur ohne Fortsetzen nach dem Neuladen
      }
    }

    function vergessen() {
      try {
        sessionStorage.removeItem(schluessel);
      } catch (e) {
        // nichts zu tun
      }
      gemerkt = false;
    }

    // ----- Formularwerte -----
    function merkeFeld(el) {
      if (!el.name || OHNE.includes(el.name) || el.type === 'hidden' || el.type === 'submit' || el.type === 'button') {
        return false;
      }
      if (el.type === 'radio') {
        if (!el.checked) {
          return false;
        }
      }
      z.ist[el.name] = el.type === 'checkbox' ? (el.checked ? el.value : '') : el.value;
      return true;
    }

    function stelleFelderHer() {
      Object.keys(z.ist).forEach((name) => {
        const el = form.elements.namedItem(name);
        if (el && !OHNE.includes(name)) {
          try {
            el.value = z.ist[name];
          } catch (e) {
            // Feld passt nicht mehr zum gespeicherten Wert
          }
        }
      });
    }

    // ----- Signale -----
    function audioBereit() {
      const AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) {
        return null;
      }
      if (!audio) {
        try {
          audio = new AC();
        } catch (e) {
          audio = null;
        }
      }
      if (audio && audio.state === 'suspended' && typeof audio.resume === 'function') {
        audio.resume().catch(() => {});
      }
      return audio;
    }

    function spiele(name) {
      const m = MUSTER[name];
      document.dispatchEvent(new CustomEvent('gefuehrt:signal', { detail: { muster: name, stumm: z.stumm } }));
      if (!m) {
        return;
      }
      if (z.stumm) {
        if (['t3', 't2', 't1'].includes(name) && blinkUhr === null) {
          blinken(); // stumm: Anzeige blinkt in den letzten 3 s (6.4), auch bei Phasen bis 3 s (erstes Tick-Signal)
        }
        return;
      }
      const ctx = audioBereit();
      if (ctx) {
        const t0 = ctx.currentTime + 0.01;
        m.toene.forEach(([versatz, dauer, freq]) => {
          const osc = ctx.createOscillator();
          const gain = ctx.createGain();
          const a = t0 + versatz / 1000;
          const e = a + dauer / 1000;
          osc.type = 'sine';
          osc.frequency.value = freq;
          gain.gain.setValueAtTime(0.0001, a);
          gain.gain.exponentialRampToValueAtTime(0.5, a + 0.01);
          gain.gain.setValueAtTime(0.5, Math.max(a + 0.01, e - 0.02));
          gain.gain.exponentialRampToValueAtTime(0.0001, e);
          osc.connect(gain);
          gain.connect(ctx.destination);
          osc.start(a);
          osc.stop(e + 0.02);
        });
      }
      if (navigator.vibrate) {
        try {
          navigator.vibrate(m.vibration);
        } catch (e) {
          // Vibration nicht erlaubt (z. B. ohne Nutzergeste)
        }
      }
    }

    function blinken() {
      const karte = aktiverAbschnitt() && aktiverAbschnitt().querySelector('.phase-card');
      if (karte) {
        blinkAus();
        void karte.offsetWidth; // Animation neu starten
        karte.classList.add('blink');
        // nach 3 s wieder aus (bei reduzierter Bewegung gibt es kein animationend: Umrandung statt Blinken)
        blinkUhr = window.setTimeout(blinkAus, 3000);
      }
    }

    function blinkAus() {
      if (blinkUhr !== null) {
        window.clearTimeout(blinkUhr);
        blinkUhr = null;
      }
      form.querySelectorAll('.phase-card.blink').forEach((k) => k.classList.remove('blink'));
    }

    // ----- Bildschirm an (Wake Lock) -----
    function bildschirmAn() {
      if (!('wakeLock' in navigator) || sperre !== null || document.visibilityState !== 'visible') {
        return;
      }
      navigator.wakeLock.request('screen').then((s) => {
        sperre = s;
        s.addEventListener('release', () => {
          sperre = null;
        });
      }).catch(() => {});
    }

    function bildschirmFrei() {
      if (sperre) {
        sperre.release().catch(() => {});
        sperre = null;
      }
    }

    // ----- Anzeige -----
    function aktiverAbschnitt() {
      return abschnitte.find((a) => a.classList.contains('aktiv')) || null;
    }

    function zeige(fokus) {
      if (modus === 'fragen') {
        blinkAus();
        abschnitte.forEach((a) => a.classList.remove('aktiv'));
        aktionen.hidden = true;
        fortsetzen.hidden = false;
        if (intro) {
          intro.hidden = true;
        }
        setzeFarbe(null);
        return;
      }
      fortsetzen.hidden = true;
      const jetzt = Date.now();
      const v = anzeige(z, steps, jetzt);
      const ziel = v.abschluss ? 'abschluss' : String(z.schritt);
      abschnitte.forEach((a) => a.classList.toggle('aktiv', a.getAttribute('data-step') === ziel));
      aktionen.hidden = v.abschluss;
      if (intro) {
        intro.hidden = !(z.begonnen_um === null && z.schritt === 0 && !v.abschluss);
      }
      setzeFarbe(v.farbe);
      zeigeFortschritt(v, jetzt);
      if (v.abschluss) {
        zeigeAbschluss(jetzt);
      } else {
        zeigeSchritt(v);
        zeigeKnoepfe(v);
      }
      const ansageSchluessel = ziel + '|' + z.phase + '|' + z.satz;
      if (ansageSchluessel !== letzteAnsage) {
        text(ansage, v.ansage); // nur Phasenwechsel ansagen, nicht jede Sekunde (6.8)
        if (letzteAnsage !== '') {
          blinkAus(); // Blinken gehört zu den letzten 3 s der vorigen Phase
        }
        letzteAnsage = ansageSchluessel;
      }
      if (fokus && letzterSchritt !== ziel) {
        const h = aktiverAbschnitt() && aktiverAbschnitt().querySelector('h2');
        if (h) {
          h.setAttribute('tabindex', '-1');
          h.focus({ preventScroll: false });
        }
        window.scrollTo(0, 0);
      }
      letzterSchritt = ziel;
    }

    function setzeFarbe(farbe) {
      if (farbe) {
        document.body.setAttribute('data-phase', farbe);
      } else {
        document.body.removeAttribute('data-phase');
      }
      if (themeColor) {
        themeColor.setAttribute('content', THEME[farbe || 'normal']);
      }
    }

    function zeigeFortschritt(v, jetzt) {
      const balken = document.getElementById('gf-balken');
      if (balken) {
        balken.style.width = Math.round(v.fortschritt.anteil * 100) + '%'; // CSSOM, kein Inline-Style-Attribut (CSP)
        balken.parentElement.setAttribute('aria-valuenow', String(z.fertig.length + z.uebersprungen.length));
      }
      text(document.getElementById('gf-fortschritt-text'), v.fortschritt.text);
      text(document.getElementById('gf-uhr'), z.begonnen_um === null ? '' : mmss(Math.floor((messEnde(z, jetzt) - z.begonnen_um) / 1000)));
    }

    function zeigeSchritt(v) {
      const a = aktiverAbschnitt();
      if (!a) {
        return;
      }
      const satz = a.querySelector('.satz');
      text(satz, v.satz_text);
      const phase = a.querySelector('.phase');
      if (phase) {
        phase.hidden = v.phase_text === null;
        if (v.phase_text !== null) {
          setzeIcon(phase.querySelector('.gf-phase-icon'), v.phase_icon);
          text(phase.querySelector('.gf-phase-text'), v.phase_text);
        }
      }
      const timer = a.querySelector('.timer');
      const reps = a.querySelector('.reps');
      if (timer) {
        timer.hidden = !v.zeige_timer;
        if (v.zeige_timer && v.zeit_ms !== null) {
          text(timer, mmss(sekunden(v.zeit_ms)));
        }
      }
      if (reps) {
        reps.hidden = v.zeige_timer && timer !== null;
      }
    }

    function zeigeKnoepfe(v) {
      const fokus = document.activeElement;
      setzeIcon(knopf.links.querySelector('.gf-icon'), v.links.icon);
      knopf.links.setAttribute('aria-label', v.links.label);
      knopf.links.title = v.links.label;
      knopf.links.disabled = !v.links.aktiv;
      setzeIcon(knopf.haupt.querySelector('.gf-icon'), v.haupt.icon);
      text(knopf.haupt.querySelector('.gf-text'), v.haupt.text);
      knopf.rechts.hidden = !v.rechts.sichtbar;
      setzeIcon(knopf.rechts.querySelector('.gf-icon'), v.rechts.icon);
      knopf.rechts.setAttribute('aria-label', v.rechts.label);
      knopf.rechts.title = v.rechts.label;
      // Fokus nicht verlieren, wenn der fokussierte Knopf gesperrt oder ausgeblendet wird (6.8)
      if ((fokus === knopf.links && knopf.links.disabled) || (fokus === knopf.rechts && knopf.rechts.hidden)) {
        knopf.haupt.focus();
      }
    }

    function zeigeAbschluss(jetzt) {
      steps.forEach((st, i) => {
        const zeile = form.querySelector('[data-uebersicht="' + i + '"]');
        if (!zeile) {
          return;
        }
        const soll = zeile.querySelector('.gf-uebersicht-soll');
        const status = zeile.querySelector('.gf-uebersicht-status');
        if (!soll.hasAttribute('data-soll')) {
          soll.setAttribute('data-soll', soll.textContent);
        }
        const uebersprungen = z.uebersprungen.includes(i);
        text(soll, uebersprungen ? 'übersprungen' : soll.getAttribute('data-soll'));
        const art = uebersprungen ? 'uebersprungen' : (z.fertig.includes(i) ? (geaendert(i) ? 'geaendert' : 'fertig') : 'offen');
        if (status.getAttribute('data-art') !== art) {
          status.setAttribute('data-art', art);
          status.textContent = '';
          if (art === 'fertig') {
            const ic = icon('check');
            ic.setAttribute('aria-label', 'erledigt');
            status.appendChild(ic);
          } else if (art === 'geaendert' || art === 'uebersprungen') {
            const marke = document.createElement('span');
            marke.className = art === 'geaendert' ? 'badge badge-warning' : 'badge badge-neutral';
            marke.textContent = art === 'geaendert' ? 'geändert' : '–';
            status.appendChild(marke);
          }
        }
      });
      const minuten = dauerMinuten(z, jetzt);
      const marke = document.getElementById('gf-dauer-marke');
      if (marke) {
        marke.hidden = minuten === null;
        if (minuten !== null) {
          text(marke.querySelector('span'), minuten + ' min');
        }
      }
    }

    /** Ist-Werte eines Schritts weichen von der Vorbelegung (Soll bzw. gespeicherter Stand) ab. */
    function geaendert(i) {
      const a = form.querySelector('.gf-step[data-step="' + i + '"]');
      return !!a && Array.from(a.querySelectorAll('input[name^="ist["]')).some((el) => el.value !== el.defaultValue);
    }

    /** Beim Wechsel in den Abschluss: Dauer (gemessen) und Status vorbelegen, solange nicht selbst geändert (6.2). */
    function abschlussVorbelegen() {
      const jetzt = Date.now();
      const dauer = form.elements.namedItem('duration_min');
      const minuten = dauerMinuten(z, jetzt);
      if (dauer && dauerAusPlan && !z.dauer_manuell && minuten !== null) {
        dauer.value = String(minuten);
        z.ist.duration_min = dauer.value;
      }
      const status = form.elements.namedItem('status');
      if (status && !z.status_manuell) {
        status.value = statusVorbelegung(z);
        z.ist.status = status.value;
      }
    }

    // ----- Ablauf -----
    function uebernehme(ergebnis) {
      const warAbschluss = istAbschluss(z, steps);
      z = ergebnis.zustand;
      if (!warAbschluss && istAbschluss(z, steps)) {
        abschlussVorbelegen();
      }
      ergebnis.signale.forEach(spiele);
    }

    function tick() {
      if (modus !== 'lauf' || z === null) {
        return;
      }
      const jetzt = Date.now();
      const r = takt(z, steps, letzterTakt, jetzt);
      letzterTakt = jetzt;
      uebernehme(r);
      if (r.uebergaenge > 0) {
        letzterWechsel = jetzt;
        speichern();
      }
      zeige(false);
    }

    function bediene(name) {
      audioBereit(); // Nutzergeste schaltet Audio frei (E-17)
      const vorher = z.begonnen_um;
      const jetzt = Date.now();
      // offene Zeit nachziehen, dann die Bedienung anwenden – außer der Tipp galt einer inzwischen beendeten Phase
      const r = tipp(z, steps, name, letzterTakt, jetzt, letzterWechsel);
      letzterTakt = jetzt;
      if (r.uebergaenge > 0) {
        letzterWechsel = jetzt;
      }
      uebernehme(r);
      if (vorher === null && z.begonnen_um !== null) {
        bildschirmAn();
      }
      speichern();
      zeige(true);
    }

    aktionen.addEventListener('click', (e) => {
      const b = e.target.closest('[data-aktion]');
      if (b && !b.disabled) {
        bediene(b.getAttribute('data-aktion'));
      }
    });
    form.addEventListener('click', (e) => {
      const b = e.target.closest('[data-aktion="zurueck-abschluss"]');
      if (b) {
        bediene('zurueck-abschluss');
      }
    });
    fortsetzen.addEventListener('click', (e) => {
      const b = e.target.closest('[data-aktion]');
      if (!b) {
        return;
      }
      audioBereit();
      if (b.getAttribute('data-aktion') === 'neu') {
        vergessen();
        z = neuerZustand(steps, { stand, stumm: z.stumm, jetzt: Date.now() });
        form.reset();
        letzterWechsel = null;
      } else {
        stelleFelderHer();
        letzterTakt = z.gespeichert_am || null; // Zeit seit dem letzten Merken nachrechnen (Hinweiston, falls eine Phase endete)
        if (z.begonnen_um !== null && !istAbschluss(z, steps)) {
          bildschirmAn();
        }
      }
      modus = 'lauf';
      zeigeStumm();
      tick();
      letzterSchritt = null; // tick() hat schon gezeichnet: Fokus trotzdem auf die Überschrift des Schritts (6.8)
      zeige(true);
    });

    form.addEventListener('input', feldGeaendert);
    form.addEventListener('change', feldGeaendert);
    function feldGeaendert(e) {
      if (z === null || !e.target || !merkeFeld(e.target)) {
        return;
      }
      if (e.target.name === 'duration_min') {
        z.dauer_manuell = true;
      }
      if (e.target.name === 'status') {
        z.status_manuell = true;
      }
      speichern();
    }

    // Enter in einem Ist-Feld schickt das Formular sonst vor dem Abschluss ab
    form.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && e.target.tagName === 'INPUT' && modus === 'lauf' && !istAbschluss(z, steps)) {
        e.preventDefault();
        e.target.blur();
      }
    });

    form.addEventListener('submit', (e) => {
      if (modus === 'aus') {
        return;
      }
      if (modus !== 'lauf' || !istAbschluss(z, steps)) {
        e.preventDefault(); // gespeichert wird aus dem Abschluss
        return;
      }
      Array.from(form.elements).forEach(merkeFeld);
      z.abgeschickt = Date.now(); // gelöscht wird erst, wenn der Server den neuen Stand zeigt (6.7)
      speichern();
      bildschirmFrei();
    });

    // Umschalter: Name bleibt „Ton und Vibration aus“, gedrückt = stumm (aria-pressed); nur der Titel wechselt
    function zeigeStumm() {
      if (!stummKnopf) {
        return;
      }
      stummKnopf.setAttribute('aria-pressed', z.stumm ? 'true' : 'false');
      stummKnopf.title = z.stumm ? 'Signale einschalten (nur diese Einheit)' : 'Stumm für diese Einheit';
    }
    if (stummKnopf) {
      stummKnopf.addEventListener('click', () => {
        z.stumm = !z.stumm;
        zeigeStumm();
        try {
          sessionStorage.setItem(stummSchluessel, z.stumm ? '1' : '0');
        } catch (e) {
          // ohne Speicher gilt die Wahl bis zum Neuladen
        }
        if (gemerkt) {
          speichern();
        }
        if (!z.stumm) {
          audioBereit();
        }
      });
    }
    /** Stumm-Wahl dieser Einheit aus dem Browser, sonst die Vorgabe aus S8 (E-18). */
    function stummVorgabe() {
      try {
        const w = sessionStorage.getItem(stummSchluessel);
        if (w === '1' || w === '0') {
          return w === '1';
        }
      } catch (e) {
        // kein Speicher
      }
      return !tonStandard;
    }

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        tick(); // nachrechnen; verpasste Signale nicht nachholen (E-16)
        if (z && z.begonnen_um !== null && !istAbschluss(z, steps) && modus === 'lauf') {
          bildschirmAn();
        }
        if (audio && audio.state === 'suspended') {
          audio.resume().catch(() => {});
        }
      }
    });
    window.addEventListener('pagehide', bildschirmFrei);

    // ----- Start: gespeicherten Fortschritt prüfen -----
    let roh = null;
    try {
      roh = sessionStorage.getItem(schluessel);
    } catch (e) {
      roh = null;
    }
    const geladen = laden(roh, { steps, stand, jetzt: Date.now(), fehler: form.hasAttribute('data-fehler') });
    if (geladen.aktion === 'fragen') {
      z = geladen.zustand;
      gemerkt = true;
      modus = 'fragen';
      const st = steps[z.schritt];
      text(document.getElementById('gf-fortsetzen-text'), (st ? 'Übung ' + (z.schritt + 1) + ' von ' + steps.length + ' (' + st.name + ')' : 'Abschluss')
        + (z.abgeschickt ? ' – die Rückmeldung wurde abgeschickt, ist aber noch nicht auf dem Server angekommen (z. B. ohne Netz).' : ' – Fortschritt und Eingaben sind auf diesem Gerät gespeichert.'));
    } else if (geladen.aktion === 'fehler') {
      // Speichern abgelehnt (422/409): Werte kommen vom Server, Fortschritt bleibt; weiter im Abschluss bzw. bei einem
      // ungültigen Ist-Wert in dessen Übung (Erledigt/Übersprungen bleiben, „Weiter“ führt durch erledigte Übungen)
      z = geladen.zustand || neuerZustand(steps, { stand, stumm: stummVorgabe(), jetzt: Date.now() });
      if (z.ende_um === null || z.ende_um === undefined) {
        z.ende_um = Date.now();
      }
      const fehlerSchritt = abschnitte.findIndex((a) => a.hasAttribute('data-invalid'));
      if (fehlerSchritt >= 0) {
        z = oeffne(z, steps, Number(abschnitte[fehlerSchritt].getAttribute('data-step')));
      } else if (form.hasAttribute('data-ist-fehler')) {
        // Ist-Fehler ohne Zuordnung zu einer Übung: alle Schritte zeigen (Formular wie ohne JavaScript)
        modus = 'aus';
        document.documentElement.classList.remove('js');
        return;
      } else {
        z.schritt = steps.length;
        Object.assign(z, { phase: 'bereit', end_at: null, phase_ms: null, rest_ms: null, vor_anhalten: null });
      }
      z.ist = {};
      Array.from(form.elements).forEach(merkeFeld);
      z.dauer_manuell = true;
      z.status_manuell = true;
      speichern();
    } else {
      if (geladen.grund !== 'leer') {
        vergessen();
      }
      if (geladen.grund === 'gespeichert') {
        try {
          sessionStorage.removeItem(stummSchluessel); // Einheit gespeichert: beim nächsten Durchgang gilt wieder S8
        } catch (e) {
          // nichts zu tun
        }
      }
      z = neuerZustand(steps, { stand, stumm: stummVorgabe(), jetzt: Date.now() });
    }
    zeigeStumm();
    zeige(false);
    window.setInterval(tick, 250);
  }
})(typeof self !== 'undefined' ? self : this);
