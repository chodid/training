/*
 * Icon-Satz der App (AP-13 T1, E-09, E-21/D-59) aus den gewählten Vorlagen rendern.
 * Der Deploy-Server kann kein SVG rendern; das Ergebnis wird deshalb eingecheckt (server/public/icons/, server/public/favicon.ico).
 *
 *   V3 (Fläche hell auf Pflaume 600)  icon-optionen/v3.svg          → lama-48/96/192/512.png (purpose any), apple-touch-icon-180.png
 *   V3 maskable (Motiv in 64 %)       icon-optionen/v3-maskable.svg → lama-512-maskable.png
 *   V2 (Fläche Pflaume 600 auf Papier) icon-optionen/v2.svg         → favicon.svg, favicon.ico (16/32/48)
 *
 * Aufruf aus dem Repo-Wurzelverzeichnis: node docs/branding/build-icons.cjs
 * Braucht Playwright mit Chromium (lokal oder global installiert; PLAYWRIGHT_BROWSERS_PATH wird beachtet).
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

const repo = path.resolve(__dirname, '..', '..');
const src = path.join(__dirname, 'mockups', 'icon-optionen');
const icons = path.join(repo, 'server', 'public', 'icons');
const docroot = path.join(repo, 'server', 'public');

const PNGS = [
  ['v3.svg', 48, 'lama-48.png'],
  ['v3.svg', 96, 'lama-96.png'],
  ['v3.svg', 192, 'lama-192.png'],
  ['v3.svg', 512, 'lama-512.png'],
  ['v3.svg', 180, 'apple-touch-icon-180.png'],
  ['v3-maskable.svg', 512, 'lama-512-maskable.png'],
];
const ICO = ['v2.svg', [16, 32, 48], 'favicon.ico'];

async function render(page, svgFile, size) {
  const svg = fs.readFileSync(path.join(src, svgFile)).toString('base64');
  await page.setViewportSize({ width: size, height: size });
  await page.setContent('<!doctype html><html><body style="margin:0;background:transparent">'
    + '<img id="i" width="' + size + '" height="' + size + '" style="display:block" src="data:image/svg+xml;base64,' + svg + '"></body></html>');
  await page.waitForFunction(() => document.getElementById('i').complete);
  return page.screenshot({ type: 'png', clip: { x: 0, y: 0, width: size, height: size } });
}

/** ICO mit eingebetteten PNG-Bildern (von allen aktuellen Browsern und Windows ab Vista gelesen). */
function ico(images) {
  const head = Buffer.alloc(6 + 16 * images.length);
  head.writeUInt16LE(0, 0);
  head.writeUInt16LE(1, 2);
  head.writeUInt16LE(images.length, 4);
  let offset = head.length;
  images.forEach(([size, png], i) => {
    const e = 6 + 16 * i;
    head.writeUInt8(size >= 256 ? 0 : size, e);
    head.writeUInt8(size >= 256 ? 0 : size, e + 1);
    head.writeUInt8(0, e + 2);
    head.writeUInt8(0, e + 3);
    head.writeUInt16LE(1, e + 4);
    head.writeUInt16LE(32, e + 6);
    head.writeUInt32LE(png.length, e + 8);
    head.writeUInt32LE(offset, e + 12);
    offset += png.length;
  });
  return Buffer.concat([head, ...images.map(([, png]) => png)]);
}

(async () => {
  const { chromium } = loadPlaywright();
  const browser = await chromium.launch();
  const page = await browser.newPage({ deviceScaleFactor: 1 });
  fs.mkdirSync(icons, { recursive: true });
  for (const [file, size, out] of PNGS) {
    fs.writeFileSync(path.join(icons, out), await render(page, file, size));
    console.log('icons/' + out + ' (' + size + ' px aus ' + file + ')');
  }
  const [icoSrc, sizes, icoOut] = ICO;
  const images = [];
  for (const size of sizes) {
    images.push([size, await render(page, icoSrc, size)]);
  }
  fs.writeFileSync(path.join(docroot, icoOut), ico(images));
  console.log(icoOut + ' (' + sizes.join('/') + ' px aus ' + icoSrc + ')');
  fs.copyFileSync(path.join(src, 'v2.svg'), path.join(icons, 'favicon.svg'));
  console.log('icons/favicon.svg (Kopie von v2.svg)');
  await browser.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
