// Passkeys (WebAuthn, D-44). Wird nur geladen, wo Passkey-Knöpfe sind; ohne WebAuthn-Unterstützung bleiben sie verborgen.
(function () {
  'use strict';
  if (!window.PublicKeyCredential || !navigator.credentials) { return; }

  var b64d = function (s) {
    s = s.replace(/-/g, '+').replace(/_/g, '/');
    while (s.length % 4) { s += '='; }
    var bin = atob(s), buf = new Uint8Array(bin.length);
    for (var i = 0; i < bin.length; i++) { buf[i] = bin.charCodeAt(i); }
    return buf.buffer;
  };
  var b64e = function (buf) {
    var bytes = new Uint8Array(buf), s = '';
    for (var i = 0; i < bytes.length; i++) { s += String.fromCharCode(bytes[i]); }
    return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  };
  // lbuchs/webauthn liefert Binärwerte als "=?BINARY?B?…?=" (Base64URL); rekursiv in ArrayBuffer wandeln
  var decode = function (v) {
    if (typeof v === 'string' && v.indexOf('=?BINARY?B?') === 0) { return b64d(v.substring(11, v.length - 2)); }
    if (Array.isArray(v)) { return v.map(decode); }
    if (v && typeof v === 'object') { var o = {}; Object.keys(v).forEach(function (k) { o[k] = decode(v[k]); }); return o; }
    return v;
  };
  var post = function (url, body, csrf) {
    var headers = { 'Content-Type': 'application/json' };
    if (csrf) { headers['X-CSRF-Token'] = csrf; }
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: headers, body: JSON.stringify(body || {}) })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) { throw new Error(j.message || 'Fehler'); } return j; }); });
  };
  var show = function (el, text) { if (el) { el.textContent = text; el.hidden = false; } };

  document.querySelectorAll('[data-passkey]').forEach(function (btn) { btn.hidden = false; });

  var reg = document.querySelector('[data-passkey="register"]');
  if (reg) {
    reg.addEventListener('click', function () {
      var err = document.getElementById('passkey-error'), csrf = reg.getAttribute('data-csrf');
      var nameInput = document.getElementById('passkey-name');
      post('/passkey/register/options', {}, csrf)
        .then(function (opts) { return navigator.credentials.create(decode(opts)); })
        .then(function (cred) {
          return post('/passkey/register', {
            name: nameInput ? nameInput.value : '',
            clientDataJSON: b64e(cred.response.clientDataJSON),
            attestationObject: b64e(cred.response.attestationObject)
          }, csrf);
        })
        .then(function () { window.location.href = '/einstellungen?ok=passkey'; })
        .catch(function (e) { show(err, 'Passkey nicht angelegt: ' + e.message); });
    });
  }

  var login = document.querySelector('[data-passkey="login"]');
  if (login) {
    login.addEventListener('click', function () {
      var err = document.getElementById('passkey-error');
      post('/passkey/login/options', {})
        .then(function (opts) { return navigator.credentials.get(decode(opts)); })
        .then(function (cred) {
          return post('/passkey/login', {
            id: b64e(cred.rawId),
            clientDataJSON: b64e(cred.response.clientDataJSON),
            authenticatorData: b64e(cred.response.authenticatorData),
            signature: b64e(cred.response.signature),
            next: login.getAttribute('data-next') || ''
          });
        })
        .then(function (res) { window.location.href = res.redirect || '/'; })
        .catch(function (e) { show(err, 'Anmeldung mit Passkey nicht möglich: ' + e.message); });
    });
  }
})();
