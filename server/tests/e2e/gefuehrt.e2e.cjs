/*
 * AP-14 T5: Browser-Durchlauf der geführten Einheit S9 mit Playwright (Chromium) und gesteuerter Uhr
 * (docs/konzept/gefuehrte-einheit.md 8.2). Web Audio, Vibration und Wake Lock sind ersetzt und werden mitgezählt.
 *
 * Voraussetzung: laufende App (z. B. php -S 127.0.0.1:8080 -t public bin/dev-router.php) mit migrierter Datenbank,
 * MCP_STATIC_TOKEN_ENABLED=true. Legt bei Bedarf den Benutzer an und schreibt eine Testwoche im Jahr 2030.
 *
 *   E2E_BASE_URL=http://127.0.0.1:8080 E2E_MCP_TOKEN=… E2E_MIGRATION_SECRET=… node server/tests/e2e/gefuehrt.e2e.cjs
 *   optional: E2E_LOGIN, E2E_PASSWORD, CHROME_PATH (Chromium/Chrome statt des Playwright-Browsers)
 */
'use strict';

const path = require('path');
const assert = require('node:assert/strict');
const http = require('http');
const { execSync } = require('child_process');

function loadPlaywright() {
  try {
    return require('playwright');
  } catch (e) {
    return require(path.join(execSync('npm root -g').toString().trim(), 'playwright'));
  }
}

const BASE = (process.env.E2E_BASE_URL || 'http://127.0.0.1:8080').replace(/\/$/, '');
const LOGIN = process.env.E2E_LOGIN || 'philipp';
const PASSWORD = process.env.E2E_PASSWORD || 'richtig-langes-passwort';
const TOKEN = process.env.E2E_MCP_TOKEN || '';
const SECRET = process.env.E2E_MIGRATION_SECRET || '';
const WEEK = '2030-01-07';

// ---------- Hilfen: MCP und Formulare über HTTP ----------

async function mcp(tool, args) {
  const h = { Authorization: 'Bearer ' + TOKEN, 'Content-Type': 'application/json', Accept: 'application/json, text/event-stream' };
  const init = await fetch(BASE + '/mcp', { method: 'POST', headers: h, body: JSON.stringify({ jsonrpc: '2.0', id: 1, method: 'initialize', params: { protocolVersion: '2025-06-18', capabilities: {}, clientInfo: { name: 'e2e', version: '1' } } }) });
  const sid = init.headers.get('mcp-session-id');
  const h2 = { ...h, 'Mcp-Session-Id': sid, 'MCP-Protocol-Version': '2025-06-18' };
  await fetch(BASE + '/mcp', { method: 'POST', headers: h2, body: JSON.stringify({ jsonrpc: '2.0', method: 'notifications/initialized' }) });
  const r = await fetch(BASE + '/mcp', { method: 'POST', headers: h2, body: JSON.stringify({ jsonrpc: '2.0', id: 2, method: 'tools/call', params: { name: tool, arguments: args } }) });
  const json = await r.json();
  const data = JSON.parse(json.result.content[0].text);
  if (json.result.isError) {
    throw new Error(tool + ': ' + JSON.stringify(data));
  }
  return data;
}

async function vorbereiten(page) {
  // Benutzer anlegen, falls es noch keinen gibt (Setup verlangt das Migrations-Secret)
  const setup = await page.goto(BASE + '/setup');
  if (setup.status() === 200 && SECRET) {
    await page.fill('input[name=secret]', SECRET);
    await page.fill('input[name=login]', LOGIN);
    await page.fill('input[name=password]', PASSWORD);
    await page.fill('input[name=password2]', PASSWORD);
    await page.click('button[type=submit]');
  }
  await page.goto(BASE + '/login');
  if (await page.locator('input[name=password]').count()) {
    await page.fill('input[name=login]', LOGIN);
    await page.fill('input[name=password]', PASSWORD);
    await Promise.all([page.waitForURL(/\/woche/), page.click('button[type=submit]')]);
  }
  await mcp('upsert_block', { block: { name: 'E2E geführte Einheit', start_date: WEEK, end_date: '2030-01-13', status: 'geplant' } });
  const plan = await mcp('write_week_plan', {
    week_start: WEEK, replace_existing: true, focus: 'Testwoche für die geführte Einheit',
    sessions: [
      { date: WEEK, type: 'kraft', title: 'E2E Kraft', planned_duration_min: 40, coach_summary: 'Testeinheit mit Halten und Wiederholungen',
        plan_json: { exercises: [
          { name: 'Unterarmstütz', sets: 3, reps: '45s', load: 'KG', rest_s: 60 },
          { name: 'Kniebeuge', sets: 3, reps: '8', load: '40 kg', rest_s: 90 },
          { name: 'Seitstütz', sets: 1, reps: '30s' },
        ] } },
      { date: '2030-01-08', type: 'haltung', title: 'E2E Haltung', planned_duration_min: 20, coach_summary: 'Kurze Einheit für Offline',
        plan_json: { exercises: [{ name: 'Face Pulls', sets: 1, reps: '15', load: 'Band grün' }] } },
    ],
  });
  return plan.einheiten.map((e) => e.id);
}

const MOCKS = () => {
  window.__log = { toene: 0, vibration: [], wake: 0, signale: [] };
  class Osc { constructor() { this.frequency = { value: 0 }; } connect() {} start() { window.__log.toene++; } stop() {} }
  class Gain { constructor() { this.gain = { setValueAtTime() {}, exponentialRampToValueAtTime() {} }; } connect() {} }
  window.AudioContext = class { constructor() { this.state = 'running'; this.currentTime = 0; this.destination = {}; } createOscillator() { return new Osc(); } createGain() { return new Gain(); } resume() { return Promise.resolve(); } };
  navigator.vibrate = (p) => { window.__log.vibration.push(p); return true; };
  Object.defineProperty(navigator, 'wakeLock', { configurable: true, value: { request: async () => { window.__log.wake++; return { release: async () => {}, addEventListener() {} }; } } });
  document.addEventListener('gefuehrt:signal', (e) => window.__log.signale.push(e.detail.muster + (e.detail.stumm ? '(stumm)' : '')));
};

async function zustand(page) {
  return page.evaluate(() => {
    const aktiv = document.querySelector('.gf-step.aktiv');
    return {
      phase: document.body.getAttribute('data-phase'),
      theme: document.querySelector('meta[name="theme-color"]').getAttribute('content'),
      schritt: aktiv ? aktiv.getAttribute('data-step') : null,
      sichtbar: Array.from(document.querySelectorAll('.gf-step')).filter((s) => s.offsetParent !== null).length,
      satz: aktiv && aktiv.querySelector('.satz') ? aktiv.querySelector('.satz').textContent : null,
      timer: aktiv && aktiv.querySelector('.timer') && !aktiv.querySelector('.timer').hidden ? aktiv.querySelector('.timer').textContent : null,
      haupt: document.querySelector('#gf-aktionen [data-aktion="haupt"] .gf-text').textContent,
      aktionen: !document.getElementById('gf-aktionen').hidden,
      frage: !document.getElementById('gf-fortsetzen').hidden,
      log: window.__log,
      blink: !!(aktiv && aktiv.querySelector('.phase-card.blink')),
      ueberlauf: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    };
  });
}

/** Radiowert wählen wie ein Tipp auf die Skala (die Eingabe selbst liegt unter der Beschriftung). */
async function waehle(page, name, value) {
  await page.locator('label:has(input[name="' + name + '"][value="' + value + '"])').click();
  assert.ok(await page.isChecked('input[name="' + name + '"][value="' + value + '"]'), name + ' = ' + value);
}

async function haupt(page) {
  await page.click('#gf-aktionen [data-aktion="haupt"]');
}

(async () => {
  assert.ok(TOKEN, 'E2E_MCP_TOKEN fehlt');
  const { chromium } = loadPlaywright();
  const browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
  const context = await browser.newContext({ viewport: { width: 375, height: 812 } });
  await context.addInitScript(MOCKS);
  const page = await context.newPage();
  const fehler = [];
  page.on('pageerror', (e) => fehler.push(e.message));
  const [kraftId, haltungId] = await vorbereiten(page);
  let schritt = 0;
  const ok = (name) => console.log('ok ' + (++schritt) + ' – ' + name);

  // ---------- Z-01 bis Z-04, Z-06, Z-08, Z-07, Z-10, Z-11 in der Krafteinheit ----------
  await page.clock.install({ time: new Date('2030-01-07T07:00:00+01:00') });
  await page.clock.pauseAt(new Date('2030-01-07T07:00:01+01:00')); // Zeit läuft nur noch über runFor/fastForward
  await page.goto(BASE + '/einheit?id=' + kraftId + '&modus=start');
  let s = await zustand(page);
  assert.deepEqual([s.phase, s.theme, s.schritt, s.sichtbar, s.timer, s.haupt, s.aktionen, s.frage], ['bereit', '#FFE4E5', '0', 1, '00:45', 'Start', true, false]);
  assert.equal(s.ueberlauf, 0, '375 px ohne seitliches Scrollen');
  assert.ok(await page.locator('.gf-intro').isVisible(), 'Kurzsatz im Startschritt');
  ok('bereit: rot, ein Schritt sichtbar, Timer 00:45, Kurzsatz');

  await haupt(page);
  s = await zustand(page);
  assert.deepEqual([s.phase, s.theme, s.haupt], ['arbeit', '#DEF2D9', 'Anhalten']);
  assert.deepEqual(s.log.signale, ['start']);
  assert.equal(s.log.toene, 2, 'Startton 2 × 80 ms');
  assert.deepEqual(s.log.vibration, [[80, 80, 80]]);
  assert.equal(s.log.wake, 1, 'Bildschirm bleibt an');
  assert.ok(!(await page.locator('.gf-intro').isVisible()), 'Kurzsatz nach dem Start ausgeblendet');
  ok('Z-01 Start: grün, Startton, Vibration, Wake Lock');

  await page.clock.runFor(35000);
  s = await zustand(page);
  assert.equal(s.timer, '00:10');
  await page.clock.runFor(10000);
  s = await zustand(page);
  assert.deepEqual(s.log.signale, ['start', 's10', 't3', 't2', 't1'], 'kein 30-s-Ton bei 45 s');
  assert.deepEqual([s.phase, s.satz, s.timer], ['pause', 'Pause · dann Satz 2 von 3', '01:00']);
  ok('Z-01/Z-02 Töne bei 10 s und 3-2-1, dann Pause rot');

  await page.clock.runFor(60000);
  s = await zustand(page);
  assert.deepEqual(s.log.signale.slice(5), ['s30', 's10', 't3', 't2', 't1', 'start']);
  assert.deepEqual([s.phase, s.satz], ['arbeit', 'Satz 2 von 3 · dann 60 s Pause']);
  ok('Z-02 Pause mit 30 s, 10 s, 3-2-1, dann automatisch Satz 2');

  await page.clock.runFor(25000);
  await haupt(page); // Anhalten bei 20 s Rest
  s = await zustand(page);
  assert.deepEqual([s.phase, s.timer, s.haupt], ['angehalten', '00:20', 'Fortsetzen']);
  await page.clock.runFor(30000);
  assert.equal((await zustand(page)).timer, '00:20', 'Zeit steht');
  await haupt(page);
  assert.equal((await zustand(page)).phase, 'arbeit');
  ok('Z-04 Anhalten bei 20 s, Fortsetzen mit 20 s');

  // Z-06: Neu laden während der Arbeitsphase
  await page.clock.runFor(5000);
  await page.reload();
  s = await zustand(page);
  assert.deepEqual([s.frage, s.sichtbar, s.aktionen], [true, 0, false]);
  assert.match(await page.textContent('#gf-fortsetzen-text'), /Übung 1 von 3 \(Unterarmstütz\)/);
  await page.click('#gf-fortsetzen [data-aktion="fortsetzen"]');
  s = await zustand(page);
  assert.deepEqual([s.frage, s.phase, s.schritt, s.satz, s.timer], [false, 'arbeit', '0', 'Satz 2 von 3 · dann 60 s Pause', '00:15']);
  assert.equal(await page.evaluate(() => document.activeElement.id), 'gf-name-0', 'Fokus auf der Übung, nicht auf <body> (6.8)');
  ok('Z-06 Neu laden: Frage, Fortsetzen stellt Schritt, Satz und Restzeit her, Fokus auf der Übung');

  // Z-08: stumm in S9 – keine Töne, keine Vibration, Blinken nur in den letzten 3 s
  await page.click('#gf-stumm');
  assert.equal(await page.getAttribute('#gf-stumm', 'aria-pressed'), 'true');
  assert.equal(await page.getAttribute('#gf-stumm', 'aria-label'), 'Ton und Vibration aus', 'Name bleibt, gedrückt = stumm');
  const vorher = await page.evaluate(() => ({ toene: window.__log.toene, vib: window.__log.vibration.length }));
  await page.clock.runFor(11500);
  assert.ok(!(await zustand(page)).blink, 'noch nicht in den letzten 3 s');
  await page.clock.runFor(1000);
  s = await zustand(page);
  assert.deepEqual([s.log.toene, s.log.vibration.length], [vorher.toene, vorher.vib]);
  assert.ok(s.log.signale.includes('t3(stumm)'));
  assert.ok(s.blink, 'Anzeige blinkt');
  await page.clock.runFor(2500);
  s = await zustand(page);
  assert.deepEqual([s.phase, s.blink], ['pause', false], 'Blinken endet mit der Phase');
  await page.click('#gf-stumm');
  ok('Z-08 stumm: keine Töne und Vibration, Blinken nur in den letzten 3 s');

  // Z-07: Wiederholungen – zur Kniebeuge überspringen wäre Z-10; hier regulär weiter
  await page.clock.runFor(60000 + 45000); // Pause, Satz 3
  s = await zustand(page);
  assert.deepEqual([s.phase, s.haupt], [null, 'Weiter'], 'nach Satz 3 fertig, normale Farbe');
  assert.ok(s.log.signale.includes('ende'), 'Abschlusston');
  await page.clock.runFor(1000); // Tipps direkt nach einem automatischen Wechsel gelten der alten Phase (Sperre)
  await haupt(page);
  s = await zustand(page);
  assert.deepEqual([s.schritt, s.phase, s.timer, s.haupt], ['1', null, null, 'Satz erledigt']);
  await page.fill('input[name="ist[1][load]"]', '42,5 kg');
  await haupt(page);
  s = await zustand(page);
  assert.deepEqual([s.phase, s.timer], ['pause', '01:30']);
  await page.clock.runFor(90000);
  s = await zustand(page);
  assert.deepEqual([s.phase, s.satz, s.haupt], [null, 'Satz 2 von 3 · Pause 90 s nach „Satz erledigt“', 'Satz erledigt']);
  ok('Z-07 Satz erledigt startet den Pausentimer, danach Satz 2 ohne Timer');

  // Z-10: letzte Übung überspringen → Abschluss mit „teilweise“ und gemessener Dauer (Z-11)
  await page.clock.runFor(1000);
  await haupt(page);
  await page.click('#gf-aktionen [data-aktion="rechts"]'); // Rest der Kniebeuge überspringen
  await page.click('#gf-aktionen [data-aktion="rechts"]'); // Seitstütz überspringen
  s = await zustand(page);
  assert.deepEqual([s.schritt, s.aktionen], ['abschluss', false]);
  assert.equal(await page.inputValue('input[name="duration_min"]'), '6', 'gemessene Minuten seit dem ersten Start (377 s)');
  await page.clock.runFor(10 * 60000);
  assert.equal(await page.textContent('#gf-dauer-marke span'), '6 min', 'Warten im Abschluss zählt nicht');
  assert.ok(await page.isChecked('input[name="status"][value="teilweise"]'));
  assert.equal(await page.textContent('[data-uebersicht="2"] .gf-uebersicht-soll'), 'übersprungen');
  ok('Z-10/Z-11 Abschluss: teilweise, Dauer gemessen');

  await page.clock.resume(); // Formular ausfüllen mit normal laufender Zeit (Playwright wartet sonst auf pausierte Frames)
  await waehle(page, 'rpe', '6');
  await waehle(page, 'feel', '2');
  await Promise.all([page.waitForURL(/\/woche\?start=2030-01-07/), page.click('#gf-abschluss button[type=submit]')]);
  const detail = await mcp('get_session_detail', { session_id: kraftId });
  assert.deepEqual([detail.status, detail.durchfuehrung.ist_min, detail.durchfuehrung.rpe_cr10], ['teilweise', 6, 6]);
  assert.equal(detail.durchfuehrung.actual_json.exercises[1].load, '42,5 kg');
  await page.goto(BASE + '/einheit?id=' + kraftId + '&modus=start');
  s = await zustand(page);
  assert.deepEqual([s.frage, s.schritt], [false, '0'], 'nach dem Speichern beginnt die geführte Einheit neu');
  ok('Speichern: Werte in der Datenbank, Fortschritt danach gelöscht');

  // Z-05: Seite im Hintergrund (Takte gedrosselt) – Nachrechnen, ein Hinweiston
  await page.clock.pauseAt(new Date('2030-01-07T09:00:00+01:00'));
  await haupt(page);
  const n0 = (await zustand(page)).log.signale.length;
  await page.clock.fastForward(120000);
  await page.clock.runFor(500);
  s = await zustand(page);
  assert.deepEqual(s.log.signale.slice(n0), ['hinweis']);
  assert.deepEqual([s.phase, s.satz, s.timer], ['arbeit', 'Satz 2 von 3 · dann 60 s Pause', '00:30']);
  ok('Z-05 Hintergrund: Folgephase richtig, nur ein Hinweiston');

  // Tipp auf „Anhalten“, nachdem die Arbeitsphase schon abgelaufen, aber noch nicht neu gezeichnet ist: gilt der
  // angezeigten Phase und wird verworfen (Review T5) – keine angehaltene Pause
  await page.clock.runFor(29000);
  assert.equal((await zustand(page)).haupt, 'Anhalten');
  await page.clock.setSystemTime(await page.evaluate(() => Date.now() + 1500)); // Zeit weiter, ohne Takt
  await haupt(page);
  s = await zustand(page);
  assert.deepEqual([s.phase, s.haupt], ['pause', 'Pause beenden']);
  ok('Tipp nach abgelaufener Phase wird verworfen');

  // ---------- Z-09: Einstellung S8 „aus“ → S9 startet stumm; Umschalten in S9 gilt nur für diese Einheit ----------
  await page.goto(BASE + '/einstellungen');
  await page.locator('label:has(input[name="timer_ton"][value="aus"])').click();
  await Promise.all([page.waitForURL(/ok=timer/), page.click('form:has(input[name="timer_ton"]) button[type=submit]')]);
  await page.goto(BASE + '/einheit?id=' + haltungId + '&modus=start');
  assert.equal(await page.getAttribute('#gf-stumm', 'aria-pressed'), 'true', 'startet stumm');
  await page.click('#gf-stumm');
  assert.equal(await page.getAttribute('#gf-stumm', 'aria-pressed'), 'false');
  await page.reload();
  s = await zustand(page);
  assert.deepEqual([await page.getAttribute('#gf-stumm', 'aria-pressed'), s.frage], ['false', false], 'Wahl vor der ersten Eingabe bleibt (E-18), keine Frage');
  await page.goto(BASE + '/einstellungen');
  assert.ok(await page.isChecked('input[name="timer_ton"][value="aus"]'), 'S8 unverändert');
  await page.locator('label:has(input[name="timer_ton"][value="an"])').click();
  await Promise.all([page.waitForURL(/ok=timer/), page.click('form:has(input[name="timer_ton"]) button[type=submit]')]);
  ok('Z-09 Vorgabe aus S8, Schalter in S9 nur für die Einheit');

  // ---------- Z-12: Speichern ohne Netz über den Puffer ----------
  // Playwrights Offline-Emulation erfasst Anfragen des Service Workers nicht; ein Proxy vor der App kappt deshalb
  // auf Kommando jede Verbindung (echter Netzausfall für Seite und Service Worker).
  await page.clock.resume();
  let netzAus = false;
  const ziel = new URL(BASE);
  const proxy = http.createServer((req, res) => {
    if (netzAus) {
      req.socket.destroy();
      return;
    }
    const weiter = http.request({ host: ziel.hostname, port: ziel.port, method: req.method, path: req.url, headers: { ...req.headers, host: ziel.host } }, (r) => {
      res.writeHead(r.statusCode, r.headers);
      r.pipe(res);
    });
    weiter.on('error', () => res.destroy());
    req.pipe(weiter);
  });
  await new Promise((resolve) => proxy.listen(0, '127.0.0.1', resolve));
  const P = 'http://127.0.0.1:' + proxy.address().port;
  const ctx2 = await browser.newContext({ viewport: { width: 375, height: 812 } });
  await ctx2.addInitScript(MOCKS);
  const offline = await ctx2.newPage();
  offline.on('pageerror', (e) => fehler.push(e.message));
  await offline.goto(P + '/login');
  await offline.fill('input[name=login]', LOGIN);
  await offline.fill('input[name=password]', PASSWORD);
  await Promise.all([offline.waitForURL(/\/woche/), offline.click('button[type=submit]')]);
  await offline.goto(P + '/einheit?id=' + haltungId + '&modus=start');
  // begrenzt warten: scheitert die Installation des Service Workers, wird ready nie erfüllt
  await offline.waitForFunction(() => navigator.serviceWorker.getRegistration().then((r) => !!(r && r.active)), null, { timeout: 15000 });
  await offline.reload(); // jetzt vom Service Worker kontrolliert
  assert.ok(await offline.evaluate(() => !!navigator.serviceWorker.controller), 'Service Worker aktiv');
  assert.equal(await offline.getAttribute('#gf-stumm', 'aria-pressed'), 'false', 'S8 „an“ beim Vorladen');
  // S8 nach dem Vorladen auf „aus“: die offline gezeigte S9 (gespeichert mit „an“) übernimmt die Einstellung
  await offline.goto(P + '/einstellungen');
  await offline.locator('label:has(input[name="timer_ton"][value="aus"])').click();
  await Promise.all([offline.waitForURL(/ok=timer/), offline.click('form:has(input[name="timer_ton"]) button[type=submit]')]);
  netzAus = true; // T6: S9 öffnet ohne Netz aus dem Seiten-Cache
  await offline.goto(P + '/einheit?id=' + haltungId + '&modus=start');
  assert.ok(await offline.evaluate(() => document.body.hasAttribute('data-offline-stand')), 'gespeicherter Stand');
  assert.equal(await offline.textContent('#gf-aktionen [data-aktion="haupt"] .gf-text'), 'Satz erledigt', 'Skript läuft offline');
  assert.equal(await offline.getAttribute('#gf-stumm', 'aria-pressed'), 'true', 'Timer-Signale „aus“ aus S8 gilt auch offline');
  netzAus = false;
  await offline.click('#gf-aktionen [data-aktion="haupt"]'); // Satz erledigt → fertig
  await offline.click('#gf-aktionen [data-aktion="haupt"]'); // Zum Abschluss
  await waehle(offline, 'rpe', '4');
  await waehle(offline, 'feel', '1');
  netzAus = true;
  await Promise.all([offline.waitForURL(/offline=gespeichert/), offline.click('#gf-abschluss button[type=submit]')]);
  const puffer = () => offline.evaluate(() => new Promise((resolve) => {
    const r = indexedDB.open('training-offline', 1);
    r.onsuccess = () => { const q = r.result.transaction('queue').objectStore('queue').getAll(); q.onsuccess = () => resolve(q.result.map((i) => i.key)); };
  }));
  assert.deepEqual(await puffer(), ['einheit:' + haltungId], 'Eingabe im Puffer');
  const gemerkt = await offline.evaluate((id) => JSON.parse(sessionStorage.getItem('training.gefuehrt.' + id)), haltungId);
  assert.ok(gemerkt && gemerkt.abgeschickt, 'Fortschritt bleibt bis zur Zustellung');
  assert.equal((await mcp('get_session_detail', { session_id: haltungId })).durchfuehrung, null, 'noch nicht auf dem Server');
  netzAus = false;
  await offline.goto(P + '/woche?start=' + WEEK); // online: offline.js stößt das Senden des Puffers an
  for (let i = 0; i < 50 && (await puffer()).length > 0; i++) {
    await new Promise((r) => setTimeout(r, 200));
  }
  assert.deepEqual(await puffer(), [], 'Puffer gesendet');
  assert.equal((await mcp('get_session_detail', { session_id: haltungId })).durchfuehrung.rpe_cr10, 4, 'nachgesendet');
  await offline.goto(P + '/einheit?id=' + haltungId + '&modus=start');
  assert.equal(await offline.isVisible('#gf-fortsetzen'), false, 'nach Zustellung kein Fortsetzen mehr');
  assert.equal(await offline.evaluate((id) => sessionStorage.getItem('training.gefuehrt.' + id), haltungId), null, 'Fortschritt gelöscht');
  proxy.close();
  ok('Z-12 offline: Puffer, Fortschritt bis zur Zustellung, danach gelöscht');

  assert.deepEqual(fehler, [], 'keine Skriptfehler');
  await browser.close();
  console.log('Alle ' + schritt + ' Prüfungen bestanden.');
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
