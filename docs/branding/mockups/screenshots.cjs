/*
 * Screenshots der Mockups neu erzeugen (Smartphone 390 × 844, Desktop 1280 × 800, ganze Seite).
 * Die Liste ergibt sich aus den vorhandenen Dateien in screenshots/: <gerät>-<seite>[_<parameter>_<wert>…].png,
 * z. B. phone-s3-einheit_typ_klettern_schmerz_ja.png → s3-einheit.html?typ=klettern&schmerz=ja.
 * Prüft nebenbei auf horizontalen Überlauf, fehlende Ressourcen und Skriptfehler.
 *
 * Aufruf: node docs/branding/mockups/screenshots.cjs [Filter]   (Filter = Teil des Dateinamens, optional)
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

function loadPlaywright() {
  try {
    return require('playwright');
  } catch (e) {
    return require(path.join(execSync('npm root -g').toString().trim(), 'playwright'));
  }
}

const DEVICES = { phone: { width: 390, height: 844 }, desk: { width: 1280, height: 800 } };
const dir = path.join(__dirname, 'screenshots');
const filter = process.argv[2] || '';

(async () => {
  const { chromium } = loadPlaywright();
  const browser = await chromium.launch();
  const problems = [];
  const files = fs.readdirSync(dir).filter((f) => f.endsWith('.png') && f.includes(filter)).sort();
  for (const file of files) {
    const m = /^(phone|desk)-(.+)\.png$/.exec(file);
    if (!m) {
      continue;
    }
    const [page, ...params] = m[2].split('_');
    const query = [];
    for (let i = 0; i + 1 < params.length; i += 2) {
      query.push(params[i] + '=' + params[i + 1]);
    }
    const url = 'file://' + path.join(__dirname, page + '.html') + (query.length ? '?' + query.join('&') : '');
    const context = await browser.newContext({ viewport: DEVICES[m[1]], deviceScaleFactor: 1 });
    const tab = await context.newPage();
    tab.on('pageerror', (e) => problems.push(file + ': Skriptfehler ' + e.message));
    tab.on('requestfailed', (r) => problems.push(file + ': Ressource fehlt ' + r.url()));
    await tab.goto(url, { waitUntil: 'load' });
    await tab.evaluate(() => document.fonts.ready);
    const overflow = await tab.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    if (overflow > 0) {
      problems.push(file + ': horizontaler Überlauf ' + overflow + ' px');
    }
    await tab.screenshot({ path: path.join(dir, file), fullPage: true });
    await context.close();
    console.log(file);
  }
  await browser.close();
  if (problems.length) {
    console.error(problems.join('\n'));
    process.exit(1);
  }
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
