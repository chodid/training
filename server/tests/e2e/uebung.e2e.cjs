/*
 * AP-16 T4: Browser-Durchlauf Übungskatalog (docs/konzept/uebungskatalog.md 11.3): S3 → S10 (W-04), S9 „Ausführung“
 * und zurück mit Schritt und Satz (W-05), Übungsseite ohne Netz aus dem Seiten-Cache mit Link statt Video (W-06),
 * 375 px ohne seitliches Scrollen (S10, S10a).
 *
 * Voraussetzung wie gefuehrt.e2e.cjs (laufende, migrierte App, MCP_STATIC_TOKEN_ENABLED=true); wird von run.sh nach
 * dem Durchlauf der geführten Einheit gestartet (Benutzer existiert dann schon).
 *   E2E_BASE_URL=http://127.0.0.1:8080 E2E_MCP_TOKEN=… E2E_MIGRATION_SECRET=… node server/tests/e2e/uebung.e2e.cjs
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
const WEEK = '2030-01-14';

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

async function anmelden(page, base) {
  const setup = await page.goto(base + '/setup');
  if (setup.status() === 200 && SECRET) {
    await page.fill('input[name=secret]', SECRET);
    await page.fill('input[name=login]', LOGIN);
    await page.fill('input[name=password]', PASSWORD);
    await page.fill('input[name=password2]', PASSWORD);
    await page.click('button[type=submit]');
  }
  await page.goto(base + '/login');
  if (await page.locator('input[name=password]').count()) {
    await page.fill('input[name=login]', LOGIN);
    await page.fill('input[name=password]', PASSWORD);
    await Promise.all([page.waitForURL(/\/woche/), page.click('button[type=submit]')]);
  }
}

async function vorbereiten() {
  // Übung anlegen (die Linkprüfung darf scheitern: ohne Netz bleibt der Link „nicht geprüft“, die Einbettung steht trotzdem)
  await mcp('upsert_exercise', {
    slug: 'e2e-kniebeuge', name: 'E2E Kniebeuge', category: 'kraft', pattern: 'knie_dominant', equipment: ['koerpergewicht'], konfidenz: 'einschaetzung',
    content: {
      kurz: 'Beidbeinige Kniebeuge mit Körpergewicht.', ziel: 'Beinkraft', ausfuehrung: ['Hüftbreit stellen.', 'Tief beugen und strecken.'],
      vorsicht: ['Patellasehne: Schmerz höchstens 3/10.'], quellen: ['Einschätzung'],
      links: [{ url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', titel: 'E2E Technikvideo mit einem ziemlich langen Titel für den Umbruch', art: 'video' }],
    },
  }).catch(async (e) => {
    if (!String(e.message).includes('gibt es schon')) {
      throw e;
    }
  });
  await mcp('upsert_block', { block: { name: 'E2E Übungskatalog', start_date: WEEK, end_date: '2030-01-20', status: 'geplant' } });
  const plan = await mcp('write_week_plan', {
    week_start: WEEK, replace_existing: true, focus: 'Testwoche Übungskatalog',
    sessions: [{ date: WEEK, type: 'kraft', title: 'E2E Katalog', coach_summary: 'Testeinheit mit Katalogübung',
      plan_json: { exercises: [
        { name: 'E2E Kniebeuge', exercise_id: 'e2e-kniebeuge', sets: 3, reps: '8', rest_s: 60 },
        { name: 'Wadenheben', sets: 2, reps: '15' },
      ] } }],
  });
  return plan.einheiten[0].id;
}

const ueberlauf = (page) => page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

(async () => {
  assert.ok(TOKEN, 'E2E_MCP_TOKEN fehlt');
  const { chromium } = loadPlaywright();
  const browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
  let schritt = 0;
  const ok = (name) => console.log('ok ' + (++schritt) + ' – ' + name);
  const fehler = [];

  // Netz über einen Proxy abschaltbar (echter Netzausfall für den Service Worker, wie AP-14)
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

  const ctx = await browser.newContext({ viewport: { width: 375, height: 812 } });
  const page = await ctx.newPage();
  page.on('pageerror', (e) => fehler.push(e.message));
  await anmelden(page, P);
  const id = await vorbereiten();

  // W-04: S3 → S10 und zurück
  await page.goto(P + '/einheit?id=' + id);
  assert.equal(await page.locator('a.ex-link').count(), 1, 'nur die Übung mit exercise_id ist verlinkt');
  await Promise.all([page.waitForURL(/\/uebung\?id=e2e-kniebeuge&von=/), page.click('a.ex-link')]);
  assert.equal(await page.locator('iframe[src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"][loading="lazy"]').count(), 1, 'Video eingebettet');
  assert.ok(await page.locator('.vorsicht').isVisible(), 'Vorsicht sichtbar');
  assert.equal(await ueberlauf(page), 0, 'S10 375 px ohne seitliches Scrollen');
  await Promise.all([page.waitForURL(new RegExp('/einheit\\?id=' + id + '$')), page.click('.topbar a.btn-icon')]);
  ok('W-04 S3 → S10 (Video, Vorsicht, 375 px) → zurück zu S3');

  await page.goto(P + '/uebungen');
  assert.equal(await ueberlauf(page), 0, 'S10a 375 px');
  assert.ok((await page.textContent('main')).includes('E2E Kniebeuge'));
  ok('S10a 375 px ohne seitliches Scrollen');

  // W-05: S9 → Ausführung → zurück: Schritt und Satz wie zuvor, ohne Rückfrage
  await page.goto(P + '/einheit?id=' + id + '&modus=start');
  await page.click('#gf-aktionen [data-aktion="haupt"]'); // Satz 1 erledigt → Pause
  await page.fill('#ist-0-load', '10 kg');
  const vorher = await page.textContent('.gf-step.aktiv .satz');
  assert.ok(/Satz 2|Pause/.test(vorher), 'nach „Satz erledigt“: ' + vorher);
  assert.ok(await page.locator('.gf-step.aktiv a[data-ausfuehrung]').isVisible(), 'Link „Ausführung“ in der Phase-Karte');
  await Promise.all([page.waitForURL(/\/uebung\?id=e2e-kniebeuge&von=\d+&modus=start/), page.click('.gf-step.aktiv a[data-ausfuehrung]')]);
  await Promise.all([page.waitForURL(/modus=start$/), page.click('.topbar a.btn-icon')]);
  assert.equal(await page.isVisible('#gf-fortsetzen'), false, 'keine Rückfrage nach der Übungsseite');
  assert.equal(await page.getAttribute('.gf-step.aktiv', 'data-step'), '0', 'gleiche Übung');
  const nachher = await page.textContent('.gf-step.aktiv .satz');
  assert.ok(/Satz 2/.test(nachher) || nachher === vorher, 'Satz wie zuvor: ' + vorher + ' → ' + nachher);
  assert.equal(await page.inputValue('#ist-0-load'), '10 kg', 'Ist-Wert bleibt');
  // ein normales Neuladen fragt weiterhin (E-19 unverändert)
  await page.reload();
  assert.equal(await page.isVisible('#gf-fortsetzen'), true, 'Neuladen ohne Übungsseite fragt weiter');
  ok('W-05 S9 → Ausführung → zurück: Schritt und Satz bleiben, keine Rückfrage');

  // W-06: Übungsseite ohne Netz aus dem Seiten-Cache (auch aus einer anderen Einheit geöffnet), Link statt Video
  await page.waitForFunction(() => navigator.serviceWorker.getRegistration().then((r) => !!(r && r.active)), null, { timeout: 15000 });
  await page.goto(P + '/einheit?id=' + id);
  await page.goto(P + '/uebung?id=e2e-kniebeuge&von=' + id); // vom Service Worker gespeichert
  netzAus = true;
  await page.goto(P + '/uebung?id=e2e-kniebeuge&von=' + id);
  assert.ok(await page.evaluate(() => document.body.hasAttribute('data-offline-stand')), 'gespeicherter Stand');
  assert.ok((await page.textContent('main')).includes('Hüftbreit stellen.'), 'Ausführung lesbar');
  assert.ok(await page.locator('figure.video figcaption a[href="https://www.youtube.com/watch?v=dQw4w9WgXcQ"]').isVisible(), 'Link statt Video');
  await page.goto(P + '/uebung?id=e2e-kniebeuge');
  assert.ok((await page.textContent('main')).includes('Hüftbreit stellen.'), 'andere Adresse derselben Übung aus dem Cache');
  netzAus = false;
  ok('W-06 ohne Netz: Übung aus dem Cache, Ausführung lesbar, Link zum Video');

  proxy.close();
  assert.deepEqual(fehler, [], 'keine Skriptfehler');
  await browser.close();
  console.log('Alle ' + schritt + ' Prüfungen bestanden.');
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
