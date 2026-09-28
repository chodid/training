/*
 * Offline-Fähigkeit (D-45), Seitenteil: registriert den Service Worker, stößt Vorladen und Senden des Puffers an und zeigt
 * den Offline-Zustand sowie wartende, abgelehnte oder kollidierende Eingaben an. Die Seiten funktionieren auch ohne.
 */
(function () {
  'use strict';
  if (!('serviceWorker' in navigator)) {
    return;
  }
  var script = document.currentScript;
  var version = script ? script.getAttribute('data-version') : '';
  navigator.serviceWorker.register('/sw.js?v=' + encodeURIComponent(version || '0')).catch(function () {});

  if (script && script.hasAttribute('data-auth')) {
    // Abmelden bzw. abgelaufene Sitzung: gespeicherte Seiten löschen, den Puffer behalten (Entscheidung Athlet).
    if (location.pathname === '/login' && window.caches) {
      caches.delete('training-pages').catch(function () {});
    }
    return;
  }

  var WEEKDAYS = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  var SHORT = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];

  function send(msg) {
    navigator.serviceWorker.ready.then(function (reg) {
      if (reg.active) {
        reg.active.postMessage(msg);
      }
    });
  }

  function pad(n) {
    return (n < 10 ? '0' : '') + n;
  }

  function el(tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) {
      e.className = cls;
    }
    if (text) {
      e.textContent = text;
    }
    return e;
  }

  function alertBox(type, title, text) {
    var box = el('div', 'alert alert-' + type);
    var inner = el('div');
    inner.appendChild(el('b', '', title));
    if (text) {
      inner.appendChild(document.createTextNode(' '));
      inner.appendChild(el('span', 'body', text));
    }
    box.appendChild(inner);
    return box;
  }

  document.addEventListener('DOMContentLoaded', init);
  if (document.readyState !== 'loading') {
    init();
  }

  var started = false;
  function init() {
    if (started) {
      return;
    }
    started = true;
    var stand = document.body.getAttribute('data-offline-stand');
    var status = document.getElementById('offline-status');
    var notes = [];

    if (stand) {
      var d = new Date(Number(stand));
      notes.push(alertBox('warning', 'Offline.', 'Gespeicherter Stand vom ' + pad(d.getDate()) + '.' + pad(d.getMonth() + 1) + '., '
        + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ' Uhr. Eingaben werden auf dem Gerät gepuffert und gesendet, sobald Netz da ist.'));
      shiftDate(notes);
    }
    var params = new URLSearchParams(location.search);
    if (params.get('offline') === 'gespeichert') {
      notes.push(alertBox('success', 'Offline gespeichert.', 'Die Eingabe liegt auf dem Gerät und wird gesendet, sobald Netz da ist.'));
      params.delete('offline');
      history.replaceState(null, '', location.pathname + (params.toString() ? '?' + params.toString() : '') + location.hash);
    }
    if (status) {
      status.__fixed = notes;
    }

    var prefetchEl = document.getElementById('offline-prefetch');
    if (prefetchEl && !stand && navigator.onLine) {
      try {
        send({ type: 'prefetch', urls: JSON.parse(prefetchEl.getAttribute('data-urls') || '[]') });
      } catch (e) {
        // ungültige Liste: nichts vorladen
      }
    }
    if (navigator.onLine) {
      send({ type: 'flush' });
    }
    window.addEventListener('online', function () { send({ type: 'flush' }); render(); });
    window.addEventListener('offline', render);
    navigator.serviceWorker.addEventListener('message', function (e) {
      if (e.data && e.data.type === 'queue-changed') {
        render();
      }
    });
    render();
  }

  // Formulare „heute“ (ohne ?datum=) aus einem älteren gespeicherten Stand auf das heutige Datum setzen.
  function shiftDate(notes) {
    if (new URLSearchParams(location.search).has('datum')) {
      return;
    }
    var form = document.querySelector('form[data-offline-form]');
    var datum = form ? form.querySelector('input[name="datum"]') : null;
    if (!datum) {
      return;
    }
    var now = new Date();
    var today = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
    if (datum.value === today) {
      return;
    }
    datum.value = today;
    var standField = form.querySelector('input[name="stand"]');
    if (standField) {
      standField.value = '';
    }
    var label = form.querySelector('input[name="offline_label"]');
    if (label) {
      label.value = label.value.replace(/\S+ \d\d\.\d\d\.$/, SHORT[now.getDay()] + ' ' + pad(now.getDate()) + '.' + pad(now.getMonth() + 1) + '.');
    }
    var head = document.querySelector('[data-date-label]');
    if (head) {
      head.textContent = WEEKDAYS[now.getDay()] + ', ' + pad(now.getDate()) + '.' + pad(now.getMonth() + 1) + '.' + now.getFullYear() + ' · heute';
    }
    notes.push(alertBox('info', 'Datum auf heute gesetzt.', 'Das gespeicherte Formular stammt von einem früheren Tag.'));
  }

  function readQueue() {
    return new Promise(function (resolve) {
      if (!window.indexedDB) {
        resolve([]);
        return;
      }
      var r = indexedDB.open('training-offline', 1);
      r.onupgradeneeded = function () { r.result.createObjectStore('queue', { keyPath: 'id', autoIncrement: true }); };
      r.onerror = function () { resolve([]); };
      r.onsuccess = function () {
        var db = r.result;
        var q = db.transaction('queue', 'readonly').objectStore('queue').getAll();
        q.onsuccess = function () { db.close(); resolve(q.result || []); };
        q.onerror = function () { db.close(); resolve([]); };
      };
    });
  }

  function render() {
    var status = document.getElementById('offline-status');
    if (!status) {
      return;
    }
    readQueue().then(function (items) {
      status.textContent = '';
      (status.__fixed || []).forEach(function (n) { status.appendChild(n); });
      if (!navigator.onLine && !document.body.getAttribute('data-offline-stand')) {
        status.appendChild(alertBox('warning', 'Kein Netz.', 'Eingaben werden auf dem Gerät gepuffert und gesendet, sobald Netz da ist.'));
      }
      var waiting = items.filter(function (i) { return i.status === 'wartet' || i.status === 'erzwingen'; });
      var login = items.filter(function (i) { return i.status === 'anmeldung'; });
      if (waiting.length) {
        status.appendChild(alertBox('info', waiting.length === 1 ? '1 Eingabe wartet auf Netz:' : waiting.length + ' Eingaben warten auf Netz:',
          waiting.map(function (i) { return i.label; }).join(', ')));
      }
      if (login.length) {
        status.appendChild(alertBox('warning', login.length === 1 ? '1 Eingabe wartet auf Anmeldung:' : login.length + ' Eingaben warten auf Anmeldung:',
          login.map(function (i) { return i.label; }).join(', ') + '. Sie werden nach dem Anmelden gesendet.'));
      }
      items.filter(function (i) { return i.status === 'konflikt' || i.status === 'fehler'; }).forEach(function (i) {
        var box = alertBox('error', i.label + ' – nicht übernommen.', i.message);
        var row = el('div', 'btn-row mt-4');
        var open = el('a', 'btn btn-secondary', 'Öffnen');
        open.href = i.formUrl;
        row.appendChild(open);
        if (i.status === 'konflikt') {
          var force = el('button', 'btn btn-secondary', 'Trotzdem übernehmen');
          force.type = 'button';
          force.addEventListener('click', function () { send({ type: 'force', id: i.id }); });
          row.appendChild(force);
        }
        var drop = el('button', 'btn btn-ghost danger-text', 'Verwerfen');
        drop.type = 'button';
        drop.addEventListener('click', function () { send({ type: 'discard', id: i.id }); });
        row.appendChild(drop);
        box.lastChild.appendChild(row);
        status.appendChild(box);
      });
      status.hidden = status.childNodes.length === 0;
    });
  }
})();
