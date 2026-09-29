/*
 * Service Worker der Training-App (D-45): Offline lesen und Eingaben puffern.
 * - Seiten /woche, /einheit (auch geführt: ?modus=start), /checkin, /schmerz, /uebung, /uebungen, /block (AP-15): erst Netz (5 s), sonst
 *   gespeicherter Stand (markiert mit data-offline-stand). Eine Übung (AP-16) passt aus jeder gespeicherten Einheit.
 * - Gestaltung (/assets, /css, /js, /app-icons, Manifest, /favicon.ico): aus dem Cache der jeweiligen Version.
 * - Formulare Check-in, Rückmeldung, Schmerz, Quittierung der Erinnerung (AP-15): ohne Netz in IndexedDB gepuffert und später mit frischem CSRF-Token gesendet
 *   (Kopfzeile X-Offline-Queue; Server antwortet 204/401/409/422). Geänderte Einträge werden nicht überschrieben (409).
 * Die Seite /login löscht die gespeicherten Seiten (Abmelden); der Puffer bleibt und wird nach dem Login gesendet.
 */
'use strict';

const VERSION = new URL(self.location.href).searchParams.get('v') || '0';
const STATIC = 'training-static-' + VERSION;
const PAGES = 'training-pages';
const PAGE_PATHS = ['/woche', '/einheit', '/checkin', '/schmerz', '/uebung', '/uebungen', '/block'];
const FORM_PATHS = ['/checkin', '/einheit', '/schmerz', '/erinnerung'];
const STATIC_PREFIXES = ['/assets/', '/css/', '/js/', '/app-icons/'];
const STATIC_FILES = ['/manifest.webmanifest', '/favicon.ico'];
const PRECACHE = ['/assets/ds/styles.css', '/assets/app.css', '/css/training.css', '/js/offline.js?v=' + VERSION, '/js/gefuehrt.js?v=' + VERSION, '/assets/lama.svg'];
const NET_TIMEOUT_MS = 5000;
const PREFETCH_AGE_MS = 10 * 60 * 1000;

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    for (const name of await caches.keys()) {
      if (name.startsWith('training-static-') && name !== STATIC) {
        await caches.delete(name);
      }
    }
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) {
    return;
  }
  if (req.method === 'POST' && req.mode === 'navigate' && FORM_PATHS.includes(url.pathname)) {
    event.respondWith(postForm(req));
    return;
  }
  if (req.method !== 'GET') {
    return;
  }
  if (req.mode === 'navigate') {
    event.respondWith(navigate(req, url));
    event.waitUntil(flush().catch(() => {}));
    return;
  }
  if (STATIC_PREFIXES.some((p) => url.pathname.startsWith(p)) || STATIC_FILES.includes(url.pathname)) {
    event.respondWith(staticAsset(req));
  }
});

self.addEventListener('message', (event) => {
  const m = event.data || {};
  if (m.type === 'flush') {
    event.waitUntil(flush());
  } else if (m.type === 'prefetch' && Array.isArray(m.urls)) {
    event.waitUntil(prefetch(m.urls));
  } else if (m.type === 'discard') {
    event.waitUntil(queueDelete(m.id).then(notify));
  } else if (m.type === 'force') {
    event.waitUntil(queueSetStatus(m.id, 'erzwingen', '').then(flush));
  }
});

self.addEventListener('sync', (event) => {
  if (event.tag === 'training-queue') {
    event.waitUntil(flush());
  }
});

// ---------- Seiten ----------

async function navigate(req, url) {
  const cacheable = PAGE_PATHS.includes(url.pathname);
  const net = fetch(req).then(async (res) => {
    if (cacheable && res.ok && !res.redirected && !url.searchParams.has('ok') && !url.searchParams.has('offline')) {
      await store(url, res.clone());
    }
    return res;
  });
  try {
    return await (cacheable ? withTimeout(net, NET_TIMEOUT_MS) : net);
  } catch (err) {
    const cached = await lookup(url);
    return cached ? offlineCopy(cached) : offlinePage();
  }
}

function withTimeout(promise, ms) {
  return new Promise((resolve, reject) => {
    const t = setTimeout(() => reject(new Error('timeout')), ms);
    promise.then((r) => { clearTimeout(t); resolve(r); }, (e) => { clearTimeout(t); reject(e); });
  });
}

function cacheKey(url) {
  const u = new URL(url.href);
  u.hash = '';
  for (const p of ['ok', 'offline', 'intervals']) {
    u.searchParams.delete(p);
  }
  return u.pathname + u.search;
}

async function store(url, res) {
  const body = await res.blob();
  const headers = new Headers(res.headers);
  headers.set('X-Cached-At', String(Date.now()));
  const cache = await caches.open(PAGES);
  await cache.put(cacheKey(url), new Response(body, { status: 200, headers }));
}

async function lookup(url) {
  const cache = await caches.open(PAGES);
  const candidates = [cacheKey(url)];
  const u = new URL(url.href);
  if (u.searchParams.has('einheit')) {
    u.searchParams.delete('einheit');
    candidates.push(cacheKey(u));
  }
  if (url.pathname === '/uebung') {
    // Übung (AP-16): jede gespeicherte Fassung der Seite, egal aus welcher Einheit sie geöffnet wurde
    const plain = '/uebung?id=' + encodeURIComponent(url.searchParams.get('id') || '');
    candidates.push(plain);
    const keys = await cache.keys();
    const other = keys.map((r) => new URL(r.url)).find((k) => k.pathname === '/uebung' && k.searchParams.get('id') === url.searchParams.get('id'));
    if (other) {
      candidates.push(other.pathname + other.search);
    }
  } else if (url.pathname !== '/einheit') {
    candidates.push(url.pathname);
  }
  for (const key of candidates) {
    const hit = await cache.match(key);
    if (hit) {
      return hit;
    }
  }
  return null;
}

async function offlineCopy(cached) {
  const html = await cached.text();
  const stamp = cached.headers.get('X-Cached-At') || '';
  return new Response(html.replace('<body', '<body data-offline-stand="' + stamp.replace(/\D/g, '') + '"'), { status: 200, headers: cached.headers });
}

function offlinePage() {
  const html = '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    + '<title>Offline – Training</title><link rel="stylesheet" href="/assets/ds/styles.css"><link rel="stylesheet" href="/assets/app.css">'
    + '<link rel="stylesheet" href="/css/training.css"></head><body class="auth"><main class="auth-card stack-lg">'
    + '<h1>Ohne Netz nicht verfügbar</h1><p class="muted">Diese Seite ist nicht für die Offline-Nutzung gespeichert. Woche, Einheiten, '
    + 'ihre Übungen, Check-in und Schmerz der aktuellen und nächsten Woche gehen auch ohne Netz, wenn die Woche vorher einmal mit Netz geöffnet wurde.</p>'
    + '<a class="btn btn-primary" href="/woche">Zur Woche</a></main></body></html>';
  return new Response(html, { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' } });
}

async function staticAsset(req) {
  const cache = await caches.open(STATIC);
  const hit = await cache.match(req);
  if (hit) {
    return hit;
  }
  const res = await fetch(req);
  if (res.ok) {
    await cache.put(req, res.clone());
  }
  return res;
}

async function prefetch(urls) {
  const cache = await caches.open(PAGES);
  for (const raw of urls) {
    const url = new URL(raw, self.location.origin);
    if (url.origin !== self.location.origin || !PAGE_PATHS.includes(url.pathname)) {
      continue;
    }
    const hit = await cache.match(cacheKey(url));
    if (hit && Date.now() - Number(hit.headers.get('X-Cached-At') || 0) < PREFETCH_AGE_MS) {
      continue;
    }
    try {
      const res = await fetch(url.href, { credentials: 'same-origin' });
      if (res.ok && !res.redirected) {
        await store(url, res);
      }
    } catch (err) {
      return;
    }
  }
}

// ---------- Eingaben puffern ----------

async function postForm(req) {
  const copy = req.clone();
  try {
    return await fetch(req);
  } catch (err) {
    const body = new URLSearchParams(await copy.text());
    body.set('offline_erfasst', String(Math.floor(Date.now() / 1000)));
    const path = new URL(req.url).pathname;
    await queuePut({
      path,
      body: body.toString(),
      key: path === '/checkin' ? 'checkin:' + body.get('datum') : (path === '/einheit' ? 'einheit:' + body.get('id') : (path === '/erinnerung' ? 'erinnerung' : null)),
      label: body.get('offline_label') || 'Eingabe',
      formUrl: path === '/einheit' ? '/einheit?id=' + encodeURIComponent(body.get('id') || '') : (path === '/erinnerung' ? '/woche' : path + '?datum=' + encodeURIComponent(body.get('datum') || '')),
      created: Date.now(),
      status: 'wartet',
      message: '',
    });
    try {
      await self.registration.sync.register('training-queue');
    } catch (e) {
      // Hintergrund-Synchronisation nicht verfügbar (z. B. Safari): Senden beim nächsten Öffnen der App.
    }
    await notify();
    return Response.redirect('/woche?offline=gespeichert', 303);
  }
}

let flushing = null;

function flush() {
  if (!flushing) {
    flushing = doFlush().finally(() => { flushing = null; });
  }
  return flushing;
}

async function doFlush() {
  const items = (await queueAll()).filter((i) => ['wartet', 'anmeldung', 'erzwingen'].includes(i.status));
  if (items.length === 0) {
    return;
  }
  let token;
  try {
    const r = await fetch('/offline/token', { credentials: 'same-origin', cache: 'no-store' });
    if (r.status === 401) {
      for (const item of items) {
        await queueSetStatus(item.id, 'anmeldung', '');
      }
      await notify();
      return;
    }
    if (!r.ok) {
      return;
    }
    token = (await r.json()).csrf;
  } catch (err) {
    return;
  }
  for (const item of items) {
    const body = new URLSearchParams(item.body);
    body.set('csrf', token);
    if (item.status === 'erzwingen') {
      body.delete('stand');
    }
    let r;
    try {
      r = await fetch(item.path, { method: 'POST', body, credentials: 'same-origin', headers: { 'X-Offline-Queue': '1' }, redirect: 'manual' });
    } catch (err) {
      break;
    }
    if (r.status === 204) {
      await queueDelete(item.id);
    } else if (r.status === 401) {
      await queueSetStatus(item.id, 'anmeldung', '');
      break;
    } else if (r.status === 503) {
      break; // Update erforderlich (Schreibsperre): später erneut
    } else if (r.status === 409) {
      await queueSetStatus(item.id, 'konflikt', 'Der Eintrag wurde inzwischen geändert; die Offline-Eingabe wurde nicht übernommen.');
    } else {
      await queueSetStatus(item.id, 'fehler', r.status === 422 ? 'Eingabe unvollständig oder ungültig; nicht übernommen.' : 'Nicht angenommen (HTTP ' + r.status + ').');
    }
  }
  await notify();
}

async function notify() {
  for (const client of await self.clients.matchAll({ type: 'window' })) {
    client.postMessage({ type: 'queue-changed' });
  }
}

// ---------- IndexedDB (Datenbank training-offline, Speicher queue) ----------

function openDb() {
  return new Promise((resolve, reject) => {
    const r = indexedDB.open('training-offline', 1);
    r.onupgradeneeded = () => r.result.createObjectStore('queue', { keyPath: 'id', autoIncrement: true });
    r.onsuccess = () => resolve(r.result);
    r.onerror = () => reject(r.error);
  });
}

async function withStore(mode, fn) {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction('queue', mode);
    let result;
    Promise.resolve(fn(tx.objectStore('queue'))).then((v) => { result = v; });
    tx.oncomplete = () => { db.close(); resolve(result); };
    tx.onerror = () => { db.close(); reject(tx.error); };
  });
}

function request(r) {
  return new Promise((resolve, reject) => { r.onsuccess = () => resolve(r.result); r.onerror = () => reject(r.error); });
}

function queueAll() {
  return withStore('readonly', (s) => request(s.getAll()));
}

function queueDelete(id) {
  return withStore('readwrite', (s) => request(s.delete(id)));
}

async function queuePut(item) {
  const all = await queueAll();
  await withStore('readwrite', (s) => {
    // Neuere Offline-Eingabe zum selben Eintrag ersetzt die ältere (sonst Konflikt mit sich selbst).
    for (const old of all) {
      if (item.key !== null && old.key === item.key) {
        s.delete(old.id);
      }
    }
    return request(s.add(item));
  });
}

async function queueSetStatus(id, status, message) {
  await withStore('readwrite', async (s) => {
    const item = await request(s.get(id));
    if (item) {
      item.status = status;
      item.message = message;
      await request(s.put(item));
    }
  });
}
