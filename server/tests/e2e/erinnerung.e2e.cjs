/*
 * AP-15 T3: Browser-Durchlauf der Erinnerung an Blockbilanz und Zielklärung (docs/konzept/blockbilanz.md 6.1, 11.4):
 * Overlay mit zwei Punkten auf S2, Fokus auf „Morgen wieder erinnern“, Seite dahinter inert, 375 px ohne seitliches
 * Scrollen; Wegklicken führt zurück auf die Seite, danach Karte „Block“ mit den Fälligkeiten und kein Overlay mehr.
 * T5: Blockseite S11 mit Zielklärung, Revision und Bilanz sowie S6 „Blöcke“ bei 375 px.
 *
 * Voraussetzung wie gefuehrt.e2e.cjs; wird von run.sh zuletzt gestartet (legt einen aktiven Block an).
 *   E2E_BASE_URL=http://127.0.0.1:8080 E2E_MCP_TOKEN=… E2E_MIGRATION_SECRET=… node server/tests/e2e/erinnerung.e2e.cjs
 * Optional E2E_SCREENSHOT_DIR: Bildschirmfotos des Overlays (375 px) und der Karte.
 */
'use strict';

const path = require('path');
const assert = require('node:assert/strict');
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
const SHOTS = process.env.E2E_SCREENSHOT_DIR || '';
// Gültige Beispiele je Art (wie tests/fixtures/review-beispiele.json)
const BEISPIELE = {"zielklaerung": {"ausgangslage": {"zeitbudget": "6–8 h/Woche, Mo/Do abends Halle", "umstaende": "Herbst, kaum Reisen", "einschraenkungen": "Patellasehne rechts reizbar (Morgentest ≤ 3)", "ausruestung": null}, "phase": "grundlagen", "phase_text": "Aerobe Basis und Kraftgrundlage aufbauen, Sehne belastbar machen.", "prioritaeten": {"t1": "A", "t2": "B", "t3": "B"}, "ziele": [{"id": "z-1", "bereich": "t1", "ziel": "Lockerer Lauf 60 min in Z2 ohne Kniereiz", "messgroesse": "Dauer Z2-Lauf, Morgentest", "kriterium": "60 min, Morgentest am Folgetag ≤ 3", "termin": "2026-12-13"}, {"id": "z-2", "bereich": "reha", "ziel": "Patellasehne belastbar", "messgroesse": "Morgentest rechts", "kriterium": "4 Wochen Mittel ≤ 2", "termin": null}], "zielevents": [{"name": "Skitour Silvretta", "datum": "2027-02-01", "art": "Tour"}], "entscheidungen": [{"thema": "Laufumfang", "entscheidung": "Steigerung nur über Einzellauflänge", "rationale": "Einzellauf-Regel statt Wochenprozent, weil die Sehne auf Spitzen reagiert.", "verworfen": ["10 % Wochenregel – reagiert zu spät auf Einzelspitzen"], "quelle": "L-R-24"}], "risiken": [{"risiko": "Sehnenreizung", "regel": "Morgentest > 5: Laufen streichen"}], "block": {"dauer_wochen": 12, "phasen": [{"name": "Einstieg", "wochen": "1–4", "fokus": "Gewöhnung"}], "tests_start": ["Morgentest-Baseline", "20-min-Lauf Z2"]}, "offene_fragen": ["Orthese beim Trailrunning?"]}, "revision": {"anlass": "schmerz", "befund": "Morgentest rechts zwei Tage in Folge 5.", "aenderungen": [{"was": "Intervalle bergab gestrichen", "warum": "Exzentrische Last auf die Sehne", "bis": "2026-10-25"}], "wirkung_pruefen": "Morgentest in zwei Wochen wieder ≤ 3."}, "bilanz": {"zeitraum": {"von": "2026-09-21", "bis": "2026-12-13"}, "ziele": [{"ziel_id": "z-1", "ziel": "Lockerer Lauf 60 min in Z2", "soll": "60 min", "ist": "55 min", "bewertung": "teilweise", "grund": "Zwei Wochen Ausfall durch Erkältung."}, {"ziel_id": "z-2", "ziel": "Patellasehne belastbar", "soll": "Mittel ≤ 2", "ist": "Mittel 1,8", "bewertung": "erreicht", "grund": "Morgentest stabil."}], "tests": [{"name": "20-min-Lauf Z2", "start": "4,2 km", "ende": "4,6 km", "datum": "2026-12-10", "bewertung": "besser"}], "gelungen": ["Krafteinheiten regelmäßig"], "nicht_gelungen": ["Mobilität oft ausgelassen"], "annahmen_geaendert": [{"was": "Mobilität am Ende der Krafteinheit", "neu": "Mobilität morgens 10 min", "begruendung": "Am Abend oft ausgelassen.", "vorschlag_trainerregel": false}], "empfehlung": "Nächster Block: Aufbau mit einer Schwelleneinheit je Woche.", "offene_fragen": ["Zweite Klettereinheit möglich?"]}};

async function mcp(tool, args) {
  const h = { Authorization: 'Bearer ' + TOKEN, 'Content-Type': 'application/json', Accept: 'application/json, text/event-stream' };
  const init = await fetch(BASE + '/mcp', { method: 'POST', headers: h, body: JSON.stringify({ jsonrpc: '2.0', id: 1, method: 'initialize', params: { protocolVersion: '2025-06-18', capabilities: {}, clientInfo: { name: 'e2e', version: '1' } } }) });
  const h2 = { ...h, 'Mcp-Session-Id': init.headers.get('mcp-session-id'), 'MCP-Protocol-Version': '2025-06-18' };
  await fetch(BASE + '/mcp', { method: 'POST', headers: h2, body: JSON.stringify({ jsonrpc: '2.0', method: 'notifications/initialized' }) });
  const r = await fetch(BASE + '/mcp', { method: 'POST', headers: h2, body: JSON.stringify({ jsonrpc: '2.0', id: 2, method: 'tools/call', params: { name: tool, arguments: args } }) });
  const json = await r.json();
  const data = JSON.parse(json.result.content[0].text);
  if (json.result.isError) {
    throw new Error(tool + ': ' + JSON.stringify(data));
  }
  return data;
}

function tag(offset) {
  const d = new Date(Date.now() + offset * 86400000);
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

async function anmelden(page) {
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
}

(async () => {
  assert.ok(TOKEN, 'E2E_MCP_TOKEN fehlt');
  const { chromium } = loadPlaywright();
  const browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
  let schritt = 0;
  const ok = (name) => console.log('ok ' + (++schritt) + ' – ' + name);
  const fehler = [];
  try {
    const ctx = await browser.newContext({ viewport: { width: 375, height: 812 } });
    const page = await ctx.newPage();
    page.on('pageerror', (e) => fehler.push(e.message));
    await anmelden(page);
    // Aktiver Block endet in 3 Tagen, ohne Zielklärung: Bilanz und Zielklärung fällig
    await mcp('upsert_block', { block: { name: 'E2E Erinnerung', start_date: tag(-30), end_date: tag(3), status: 'aktiv' } });

    await page.goto(BASE + '/woche');
    const overlay = page.locator('[data-review-overlay]');
    assert.ok(await overlay.isVisible(), 'Overlay sichtbar');
    assert.ok((await overlay.textContent()).includes('Blockbilanz und Zielklärung fällig'), 'ein Overlay mit beiden Punkten');
    assert.equal(await overlay.locator('li').count(), 2);
    assert.equal(await page.evaluate(() => document.activeElement && document.activeElement.value), 'morgen', 'Fokus auf „Morgen wieder erinnern“');
    assert.equal(await page.evaluate(() => document.querySelector('.nav').hasAttribute('inert') && document.querySelector('main').hasAttribute('inert')), true, 'Seite dahinter inert');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth), 0, '375 px ohne seitliches Scrollen');
    const box = await page.locator('.review-dialog').boundingBox();
    assert.ok(box.x >= 0 && box.x + box.width <= 375, 'Karte in der Breite');
    if (SHOTS) {
      await page.screenshot({ path: path.join(SHOTS, 'erinnerung-375.png') });
    }
    ok('U-01/U-06 Overlay mit zwei Punkten, Fokus, inert, 375 px');

    await Promise.all([page.waitForURL(/\/woche$/), page.click('button[value=morgen]')]);
    assert.equal(await page.locator('[data-review-overlay]').count(), 0, 'Overlay weg');
    const karte = page.locator('.block-card');
    assert.ok(await karte.isVisible(), 'Karte „Block“');
    assert.ok((await karte.textContent()).includes('Blockbilanz fällig'), 'Karte nennt die Fälligkeit');
    assert.ok((await karte.textContent()).includes('noch 4 Tage'), 'Restlaufzeit');
    if (SHOTS) {
      await karte.screenshot({ path: path.join(SHOTS, 'blockkarte-375.png') });
    }
    await page.goto(BASE + '/einstellungen');
    assert.equal(await page.locator('[data-review-overlay]').count(), 0, 'auch auf anderen Seiten bis morgen Ruhe');
    ok('U-02 Morgen wieder erinnern: Overlay weg, Karte zeigt die Fälligkeit');

    // T5: Blockseite S11 mit Zielklärung, Revision und Bilanz, S6 Abschnitt „Blöcke“ – 375 px ohne seitliches Scrollen
    const block = (await mcp('get_block', {})).id;
    for (const [kind, content] of Object.entries(BEISPIELE)) {
      await mcp('write_block_review', { block_id: block, kind, review_date: tag(0), summary: 'E2E ' + kind, content, status: 'bestaetigt', reason: 'E2E' });
    }
    await page.goto(BASE + '/block?id=' + block);
    assert.ok((await page.textContent('main')).includes('Verworfen'), 'Entscheidungstabelle');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth), 0, 'S11 375 px ohne seitliches Scrollen');
    await page.click('#bilanz details summary');
    assert.ok(await page.locator('#bilanz details .list-item').first().isVisible(), 'Fassungen aufklappbar');
    if (SHOTS) {
      await page.screenshot({ path: path.join(SHOTS, 'block-375.png'), fullPage: true });
    }
    await page.goto(BASE + '/verlauf#bloecke');
    assert.ok(await page.locator('#bloecke a[href="/block?id=' + block + '"]').isVisible(), 'S6 Abschnitt Blöcke mit Link');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth), 0, 'S6 375 px');
    ok('T5 Blockseite mit allen Abschnitten und S6 „Blöcke“ bei 375 px');

    assert.deepEqual(fehler, [], 'keine Skriptfehler');
    console.log('Alle ' + schritt + ' Prüfungen bestanden.');
  } finally {
    await browser.close();
  }
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
