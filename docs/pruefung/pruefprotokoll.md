---
titel: Prüfprotokoll KI-Personal-Trainer
bezug: docs/konzept/konzept-ki-personal-trainer.md, Abschnitt 16
---

# Prüfprotokoll

Struktur je AP gemäß Konzept Abschnitt 16. Wird nach jedem abgeschlossenen AP aktualisiert.

## AP-00 Grundgerüst und Deployment

```yaml
ap: AP-00
geprueft:
  - was: V-08 PHP-Anforderung des MCP-SDK (logiscape/mcp-sdk-php v2.0.1)
    wie: automatisiert (Packagist-Metadaten)
    ergebnis: ok (PHP >= 8.1, ext-curl, ext-json)
    datum: 2026-09-27
  - was: Servertest Lima-City (test.php, Subdomain training.gen-em.org)
    wie: manuell durch Athlet
    ergebnis: ok – Apache 2.4, PHP 8.4.25, curl/json/openssl/pdo_mysql/zlib/mbstring aktiv, open_basedir leer, .env oberhalb des Docroots lesbar, .htaccess "Require all denied" → 403
    datum: 2026-09-27
  - was: V-10 Hosting-Funktionen
    wie: Angaben des Athleten + Servertest
    ergebnis: ok bis auf SMTP-Anhang-Limit (FTPS Port 21 explizit mit gültigem Zertifikat, SMTP vorhanden, Cronjob per URL-Aufruf, keine PHP-CLI)
    datum: 2026-09-27
  - was: Unit-Tests (Konfiguration, SQL-Zerlegung, Migrationsdateien, Routing, 401/403/503 am Migrations-Endpunkt)
    wie: automatisiert (PHPUnit, lokal PHP 8.4)
    ergebnis: ok
    datum: 2026-09-27
  - was: Integrationstests Migrationen (0 → 1, idempotent, Abbruch hält letzten Stand, Sperre gegen Parallellauf)
    wie: automatisiert (PHPUnit gegen lokale MariaDB 10.11; in CI gegen MySQL 8.4)
    ergebnis: ok (lokal)
    datum: 2026-09-27
  - was: HTTP-Durchlauf lokal – /health 503 vor Migration, /admin/migrate 401/403/200, /health 200 danach, zweiter Migrationsaufruf ohne Änderung, /.env → 404
    wie: manuell (PHP-Built-in-Server, curl)
    ergebnis: ok
    datum: 2026-09-27
  - was: Workflow-Datei syntaktisch gültig
    wie: automatisiert (YAML-Parser)
    ergebnis: ok
    datum: 2026-09-27
  - was: CI-Job test (PHPUnit inkl. Migrationen gegen MySQL 8.4)
    wie: automatisiert (GitHub Actions, PR #2/#3)
    ergebnis: ok (erster Lauf rot – Migrationsdatei durch .gitignore ausgeschlossen, behoben)
    datum: 2026-09-27
  - was: Push auf main führt ohne manuelle Schritte zu lauffähigem Stand (Upload, Migration, Health-Check)
    wie: GitHub Actions nach Merge PR #3
    ergebnis: ok (erster Deploy-Lauf rot – Schutzprüfung verbot FTP_SERVER_DIR "/", behoben in 0.1.1)
    datum: 2026-09-27
  - was: /health über HTTPS
    wie: manuell durch Athlet (Browser)
    ergebnis: ok – status ok, app_version 0.1.1, PHP 8.4.25, alle Erweiterungen, config/database ok, Schema code 1 = db 1
    datum: 2026-09-27
  - was: .env überlebt ein Deployment
    wie: Deployment mit vorhandener .env, danach /health config ok
    ergebnis: ok
    datum: 2026-09-27
  - was: Migrations-Endpunkt lehnt Aufrufe ohne/mit falschem Secret ab
    wie: manuell (curl.exe POST ohne Header / mit falschem Header)
    ergebnis: ok – 401 "Header X-Migration-Secret fehlt.", 403 "Secret ungültig."
    datum: 2026-09-27
  - was: HTTP → HTTPS, HSTS, nosniff
    wie: manuell (curl.exe -sI)
    ergebnis: ok – 301 auf https (bereits durch vorgeschalteten Lima-City-Proxy openresty, der selbst HSTS includeSubDomains; preload setzt); HTTPS-Antwort mit strict-transport-security, x-content-type-options nosniff, referrer-policy, cache-control no-store
    datum: 2026-09-27
  - was: .env nicht per HTTP abrufbar
    wie: manuell (Browser /.env)
    ergebnis: ok – JSON-Fehler 404, kein Inhalt
    datum: 2026-09-27
  - was: Secrets nicht im Repo
    wie: automatisiert (git grep über gesamte Historie von main)
    ergebnis: ok – nur Platzhalter in Tests
    datum: 2026-09-27
noch_zu_pruefen:
  - was: SMTP-Anhang-Größenlimit
    wie: Testversand in AP-10
```

## AP-01a Design-Mockups

```yaml
ap: AP-01a
geprueft:
  - was: Schriften lokal eingebunden (tokens/fonts.css, fonts/*.ttf)
    wie: automatisiert (Chromium, document.fonts auf guidelines/type-display, type-body, type-mono)
    ergebnis: ok – Young Serif, Source Sans 3 (normal, kursiv), Source Code Pro geladen
    datum: 2026-09-27
  - was: Keine Verweise auf entfernte Quelldateien (uploads/) und kein Google-Fonts-Import in styles.css-Closure
    wie: automatisiert (grep)
    ergebnis: ok – Google Fonts nur noch in explorations/Typografie.dc.html (Schriftvergleich, dokumentiert)
    datum: 2026-09-27
  - was: Mockups (13 Seiten/Zustände) in 390, 834, 1112 und 1280 px – horizontaler Überlauf, fehlende Ressourcen, Konsolenfehler
    wie: automatisiert (Chromium/Playwright, Screenshots in docs/branding/mockups/screenshots/)
    ergebnis: ok nach Korrekturen (Icon-Laden unter file://, Kennzahl-Umbruch, 7-Spalten-Woche erst ab 1280 px, Segmentwahl im Zweispaltenlayout)
    datum: 2026-09-27
  - was: Sichtprüfung Smartphone und Desktop aller Screens
    wie: manuell (Fable, anhand der Screenshots)
    ergebnis: ok
    datum: 2026-09-27
  - was: Abnahme der Mockups (Smartphone, Tablet, Desktop) und des Branding-Dokuments
    wie: Sichtprüfung durch Athlet
    ergebnis: abgenommen
    datum: 2026-09-27
noch_zu_pruefen:
  - was: Word-Vorlage mit installierten variablen TTF (Source Sans 3)
    wie: manuell durch Athlet (Brief.docx öffnen, Schriftersetzung prüfen)
```

## AP-01 MCP-Minimalserver mit OAuth

```yaml
ap: AP-01
geprueft:
  - was: Unit-Tests – Sperrstufen (10 Fehlversuche → 5 min, Verdopplung, max. 24 h, verkürzte Zeitbasis), Redirect-URI-Regeln (https, localhost/127.0.0.1/[::1], kein http sonst, kein Fragment/Userinfo), PKCE S256 (RFC-7636-Beispiel), Scope-Vergabe, JWT gegen SDK-JwtTokenValidator (Claims, abgelaufen, fremd signiert, falsche Audience, ohne exp), statisches Token nur mit Flag, Rücksprungziele ohne Open Redirect, Pflichtwert OAUTH_JWT_SECRET ≥ 32 Zeichen
    wie: automatisiert (PHPUnit, lokal PHP 8.4)
    ergebnis: ok
    datum: 2026-09-27
  - was: Integrationstests Web – /setup legt genau einen Benutzer an und ist danach 404 (auch POST); falsches Secret, fehlendes CSRF-Token, kurzes Passwort abgelehnt; Passwort als Argon2id-Hash; Session-Cookie HttpOnly/Secure/SameSite=Lax/30 Tage, Token nur gehasht in der DB; Session überlebt "Browser-Neustart", gleitend 20+20 Tage, abgelaufen nach 31 Tagen ohne Nutzung; Abmelden nur mit CSRF-Token; 10 Fehlversuche → 5 min (auch richtiges Passwort während Sperre abgewiesen, nicht gezählt) → 10 → 20 min; Erfolg setzt Zähler und Sperre zurück; Sperre max. 24 h; falscher Anmeldename zählt; Sicherheitsheader (CSP, X-Frame-Options)
    wie: automatisiert (PHPUnit gegen lokale MariaDB 10.11, simulierte Uhr)
    ergebnis: ok
    datum: 2026-09-27
  - was: Integrationstests OAuth/MCP – Metadaten (RFC 8414, Protected Resource, jeweils mit Suffix /mcp); Registrierung lehnt http:// außer localhost ab; Authorize ohne Login → Login mit Rücksprung → Freigabeseite S7 (Client-Name, Redirect-Host, Scope) → Code mit state und iss; Freigabe ohne CSRF → 403; Ablehnen → access_denied; PKCE plain und fehlende Challenge → invalid_request; unbekannter Client / nicht registrierte Redirect-URI → Fehlerseite ohne Weiterleitung; Code einmalig (auch nach falschem Verifier verbraucht), nach 10 min abgelaufen; Token-Antwort (Bearer, 3600 s, Scope); /mcp mit JWT: initialize + ping; Refresh rotiert; Wiederverwendung des alten Refresh-Tokens widerruft die Familie; /mcp ohne Token → 401 mit resource_metadata, abgelaufenes/fremd signiertes JWT → 401, keine Sitzungsdatei ohne gültiges Token; statisches Token ohne Flag / mit false → 401, mit true → ping; GET /mcp → 405
    wie: automatisiert (PHPUnit gegen lokale MariaDB 10.11)
    ergebnis: ok
    datum: 2026-09-27
  - was: MCP mit echtem Client (logiscape-SDK-Client) gegen lokalen Server, Protokoll-Epochen 2026-07-28 (zustandslos) und 2025-11-25 (Handshake) – tools/list, ping
    wie: manuell (PHP-Built-in-Server, statisches Token)
    ergebnis: ok
    datum: 2026-09-27
  - was: S0, S1 (normal, Fehler, gesperrt), S7 und Startseite in 390, 834 und 1280 px – horizontaler Überlauf, Schriften, fehlende Ressourcen; Sichtvergleich mit den Mockups
    wie: automatisiert (Chromium/Playwright) + Sichtprüfung der Screenshots (Code-Instanz)
    ergebnis: ok – kein Überlauf, Young Serif/Source Sans 3/Source Code Pro lokal geladen, Darstellung entspricht den Mockups bis auf die in branding.md Abschnitt 8 dokumentierten Abweichungen
    datum: 2026-09-27
  - was: Session überlebt Browser-Neustart (gespeicherte Cookies in neuem Browser-Kontext)
    wie: automatisiert (Playwright, lokal)
    ergebnis: ok – Startseite 200 mit Anmeldung, Cookie 30 Tage, HttpOnly, SameSite=Lax
    datum: 2026-09-27
  - was: HTTP-Header lokal – Set-Cookie, CSP, X-Frame-Options, CORS-Preflight 204, Schriften als font/ttf
    wie: manuell (curl)
    ergebnis: ok
    datum: 2026-09-27
  - was: CI-Job test (PHPUnit gegen MySQL 8.4) auf allen Ständen von PR #7 bis 0.13.0
    wie: automatisiert (GitHub Actions)
    ergebnis: ok
    datum: 2026-09-28
  - was: Deployment (Merge PR #7, 0.13.0): Upload per FTPS, Migration, Health-Check – /health status ok, schema code 18 = db 18, var und backups ok, PHP 8.4.25
    wie: GitHub Actions Lauf 36386578804 + /health durch Athlet
    ergebnis: ok
    datum: 2026-09-28
  - was: /setup legt den Benutzer an
    wie: manuell durch Athlet im Browser
    ergebnis: ok
    datum: 2026-09-28
  - was: Connector in claude.ai verbunden und freigegeben (S7), Tool-Aufruf funktioniert (V-05, Web)
    wie: manuell durch Athlet
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen:
  - was: .env und var/ überleben ein zweites Deployment; /setup nach Anlage gesperrt (404)
    wie: nächstes Deployment abwarten, /health; /setup im Browser aufrufen
  - was: Login auf Smartphone und Tablet nutzbar; Session überlebt Neustart des Browsers
    wie: manuell durch Athlet (anmelden, Browser/App schließen, erneut öffnen)
  - was: ping aus der Mobile-App (V-05)
    wie: manuell durch Athlet – Claude-App, Connector aktiv, "ping aufrufen"
  - was: Token-Refresh nach Ablauf mit claude.ai (V-05)
    wie: manuell durch Athlet – nach mehr als 1 h erneut ping aufrufen; Erwartung ohne neuen Login
  - was: Fallback über Claude Desktop funktioniert nur mit gesetztem Flag
    wie: manuell durch Athlet – MCP_STATIC_TOKEN setzen, Flag false → Fehler 401; Flag true → ping; danach Flag wieder false
  - was: Anfrage ohne Token gegen den Server → 401 mit Metadaten
    wie: manuell (curl.exe -si -X POST https://training.gen-em.org/mcp -H "Content-Type: application/json" -d "{\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"initialize\",\"params\":{}}") → 401 und www-authenticate mit resource_metadata
```

## AP-02 Intervals.icu-Anbindung

```yaml
ap: AP-02
geprueft:
  - was: Client – Basic-Auth API_KEY:<key>, URL und Query (oldest/newest, category), Events anlegen/ändern/löschen mit JSON-Körper, Liste/Objekt-Prüfung der Antworten, Fehler 401/403 mit Hinweis und ohne Key in der Meldung, eine Wiederholung bei 429 (Retry-After), zweiter 429 → Fehler, Datumsformat, ungültige Athleten-ID
    wie: automatisiert (PHPUnit, simulierter Transport)
    ergebnis: ok
    datum: 2026-09-27
  - was: Seite /intervals – nur nach Login; ohne Konfiguration Hinweis; mit Konfiguration Athlet, Aktivitäten, Wellness, Events; Test-Event anlegen (external_id), ändern, löschen; CSRF-Pflicht; API-Fehler als Meldung (502); /health intervals konfiguriert/nicht_konfiguriert
    wie: automatisiert (PHPUnit gegen MariaDB, simulierter Transport)
    ergebnis: ok
    datum: 2026-09-27
  - was: Erreichbarkeit der echten API und Dokumentation aus der Code-Umgebung
    wie: curl / Web-Abruf
    ergebnis: fehler – intervals.icu durch Netzwerkrichtlinie gesperrt; Endpunkte aus Sekundärquelle (V-04 vorläufig)
    datum: 2026-09-27
  - was: INTERVALS_API_KEY und INTERVALS_ATHLETE_ID in der .env; /health zeigt intervals konfiguriert
    wie: manuell durch Athlet, Deploy-Log
    ergebnis: ok
    datum: 2026-09-28
  - was: Aktivitäten und Wellness der letzten 7 Tage über /intervals abrufbar (V-04 GET)
    wie: manuell durch Athlet auf dem Server
    ergebnis: ok
    datum: 2026-09-28
  - was: Test-Event über /intervals angelegt; in Intervals.icu als strukturiertes Workout übernommen: 10 min Z1, 3 × (3 min Z3, 2 min Z1), 5 min Z1, HF-Bereiche Z1 103–129 und Z3 138–145 bpm, 30 min, Load 21 (V-04 POST, V-01 Syntax in Intervals.icu)
    wie: manuell durch Athlet, Screenshot aus Intervals.icu
    ergebnis: ok
    datum: 2026-09-28
  - was: Test-Event über Garmin Connect auf der Uhr angekommen (V-01)
    wie: manuell durch Athlet
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen:
  - was: Intervals.icu-Konto – Aktivitäten privat (Q-03)
    wie: manuell durch Athlet in Intervals.icu (Einstellungen)
  - was: Zonengrenzen des Test-Workouts auf der Uhr mit Intervals.icu vergleichen (Z1 103–129, Z3 138–145 bpm; V-12)
    wie: manuell – auf der Uhr die Schritte des Workouts öffnen und HF-Bereiche ablesen
  - was: Ändern und Löschen werden auf der Uhr nachgezogen (V-02)
    wie: manuell – „Test-Event ändern“ (Name mit „(geändert)“), synchronisieren, prüfen; „Test-Event löschen“, synchronisieren, prüfen; Zeitverzug notieren
  - was: V-03 Feldsemantik icu_rpe (Skala) und feel (Richtung 1–5)
    wie: manuell – nach einer Aktivität RPE und Feel in Intervals.icu setzen, in /intervals ablesen und mit der Eingabe vergleichen
  - was: V-09 Kraftaktivität von der Uhr (Typ, Dauer, HF)
    wie: manuell – Krafttraining auf der Uhr aufzeichnen, in /intervals Typ und Dauer ablesen
```

## AP-03 Datenmodell

```yaml
ap: AP-03
geprueft:
  - was: Migrationen 0001–0014 von leer, zweiter Lauf ohne Änderung (idempotent), alle Tabellen vorhanden
    wie: automatisiert (PHPUnit gegen MariaDB 10.11)
    ergebnis: ok
    datum: 2026-09-27
  - was: Beispielwoche mit allen sechs Einheitentypen (kraft, ausdauer, klettern, haltung, mobilitaet, ruhe) validiert und eingefügt; JSON bleibt erhalten, Ruhetag ohne Plan
    wie: automatisiert (Fixture beispielwoche.json)
    ergebnis: ok
    datum: 2026-09-27
  - was: JSON-Validierung lehnt fehlerhafte Pläne ab (leere Übungsliste, Sätze 0, reps als Zahl, unbekanntes Feld, falscher Griff/Blocktyp/Zieltyp, fehlender Workout-Text, Ruhetag mit Übungen, unbekannter Typ, kein Objekt); actual_json erlaubt Teilangaben, lehnt falsche Typen ab
    wie: automatisiert (PHPUnit)
    ergebnis: ok
    datum: 2026-09-27
  - was: Datenbank weist ab – ungültige ENUM-Werte (Ort, Seite, Typ, Status), Bereiche (RPE 11, Feel 0, Erholung 6, Schmerz 11), zweites Check-in am selben Tag, zweite Durchführung je Einheit, Schreiben von srpe_load, Enddatum vor Startdatum, ungültiges JSON; srpe_load = 6 × 55 = 330
    wie: automatisiert (PHPUnit gegen MariaDB 10.11, strikter Modus)
    ergebnis: ok
    datum: 2026-09-27
  - was: Löschen – Woche entfernt Einheiten und Durchführungen, Schmerzereignis bleibt (session_id NULL), Block mit Wochen nicht löschbar
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-27
  - was: ER-Diagramm rendert
    wie: automatisiert (Mermaid 11 im Browser)
    ergebnis: ok
    datum: 2026-09-27
  - was: Migrationen und Constraints gegen MySQL 8.4 (CHECK, berechnete Spalte, JSON)
    wie: automatisiert (CI-Job test, PR #7, Lauf 16)
    ergebnis: ok
    datum: 2026-09-28
  - was: Migration auf dem Server bis Schema 18, /health schema code 18 = db 18
    wie: Deployment nach Merge PR #7, /health
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen: []
```

## AP-04 Webseite

```yaml
ap: AP-04
geprueft:
  - was: Seiten nur nach Login (Weiterleitung mit Rücksprung); Woche mit allen sechs Typen, KW, Block/Woche, heute, Check-in-Status, Leerzustand, ±Woche
    wie: automatisiert (PHPUnit gegen MariaDB, simulierte Uhr)
    ergebnis: ok
    datum: 2026-09-28
  - was: Krafteinheit mit Ist-Werten abschließen (Soll vorbelegt, geänderte Last, 0 Sätze), RPE/Gefühl/Dauer, Schmerz, Abweichung → session_execution (sRPE 7 × 55 = 385), session.status, pain_event, audit_log; erneutes Öffnen zeigt gespeicherte Werte
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Validierung – fehlende Pflichtwerte, ungültige Ist-Werte, unvollständige Schmerzangabe → 422 mit Meldungen, Eingaben bleiben erhalten, nichts gespeichert; CSRF → 403; ausgelassen ohne RPE; Ruhetag/unbekannte Einheit → 404
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Ausdauereinheit mit verknüpfter Aktivität (paired_event_id) – Dauer, Distanz, Ø HF, Zonen, Link; Dauer vorbelegt; Woche zeigt „Aktivität vorhanden“; Cache verhindert zweiten Abruf
    wie: automatisiert (simulierter Intervals.icu-Transport)
    ergebnis: ok
    datum: 2026-09-28
  - was: Check-in anlegen und überschreiben (ein Eintrag pro Tag), mit Schmerz; Zukunftsdatum → heute; ungültige Werte → 422; Schmerzformular mit Einheit, Hinweis bei dritter Meldung
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Einstellungen – Zeitzone, Passwort (falsches aktuelles → 422; Erfolg beendet andere Sessions), Freigabe widerrufen, Audit-Log-Einträge
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Browser-Durchlauf lokal (Setup → Woche → Einheit speichern mit Schmerz → Check-in → Schmerz mit Hinweis → Einstellungen → Verlauf) in 390, 834, 1112 und 1280 px – kein horizontaler Überlauf, keine Konsolenfehler, Schmerz-Kurzform klappt ohne JavaScript auf, Manifest als application/manifest+json
    wie: automatisiert (Chromium/Playwright) + Sichtvergleich mit den Mockups (Code-Instanz)
    ergebnis: ok – Abweichungen in branding.md Abschnitt 8
    datum: 2026-09-28
  - was: Check-in-Dauer
    wie: automatisiert (Playwright, zwei Auswahlen + Speichern)
    ergebnis: ok – 0,2 s ohne Bedienzeit; realistische Bedienung ≤ 10 s durch Athlet zu bestätigen
    datum: 2026-09-28
noch_zu_pruefen:
  - was: Auf Smartphone und Tablet – Woche sehen, Krafteinheit mit Ist-Werten abschließen, Feedback und Schmerzereignis erfassen, Check-in in ≤ 10 s
    wie: manuell durch Athlet nach Deployment (Planwoche vorher per SQL oder ab AP-05 per Claude anlegen)
  - was: Alles in der DB nachvollziehbar
    wie: manuell (phpMyAdmin bei Lima-City – session_execution, pain_event, checkin, audit_log)
  - was: Ausdauereinheit der Woche mit verknüpfter Aktivität sichtbar (echte Intervals.icu-Daten, Feldnamen V-04)
    wie: manuell durch Athlet nach AP-02-Einrichtung – Ausdauereinheit mit intervals_event_id des Test-Events anlegen, Aktivität aufzeichnen, /einheit öffnen
  - was: „Zum Startbildschirm“ auf dem Smartphone (Icon, Name, Farben)
    wie: manuell durch Athlet
```

## AP-09 Betrieb und Optionen

```yaml
ap: AP-09
geprueft:
  - was: JSON-Export – Format, Schemastand, JSON-Spalten als Objekte (Sonderzeichen), ohne Passwort-Hash/Sessions/Tokens, srpe_load enthalten, Audit-Log
    wie: automatisiert (PHPUnit gegen MariaDB)
    ergebnis: ok
    datum: 2026-09-28
  - was: Verlauf S6 – Wochenlast je Bereich (Kraft 5 × 60 = 300, Klettern 6 × 90 = 540), gemeinsame Achse (600), Balkenhöhe, Schmerz-Raster mit stärkster Meldung je Woche und Hinweistext, Tabelle mit Summe, Check-in und Schmerz
    wie: automatisiert + Browser 390/1280 px (kein Überlauf, Sichtvergleich mit Mockup)
    ergebnis: ok
    datum: 2026-09-28
  - was: Spiegel – read-through speichert Zusammenfassung ohne Streams, zweiter Abruf innerhalb von 5 min ohne API, Rückfall auf Spiegel bei HTTP 500 mit Hinweis, Lesen ohne Konfiguration
    wie: automatisiert (PHPUnit, simulierte API)
    ergebnis: ok
    datum: 2026-09-28
  - was: Cron-Abgleich – Schlüsselprüfung, Zeitraum (tage), Wellness nur bekannte Felder, gelöschte Aktivität entfernt, Status in Einstellungen, Fehlerfall 502 mit Anzeige, ohne Intervals-Konfiguration 503; Spiegel in SQL-Backup enthalten
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Feedback-Rückschreiben – RPE/Gefühl per PUT auf die zugeordnete Aktivität, Notiz als Kommentar, kein Doppelkommentar bei gleicher Notiz, RPE 0 nicht übertragen, Fehler → Rückmeldung gespeichert und Hinweis
    wie: automatisiert (simulierte API)
    ergebnis: ok
    datum: 2026-09-28
  - was: Einstellungsseite bei veraltetem Schema erreichbar (Regression gefunden und behoben)
    wie: automatisiert (Schreibsperren-Test mit allgemeinem Zurücksetzen der letzten Migration)
    ergebnis: ok
    datum: 2026-09-28
  - was: Passkey – Anlegen nur angemeldet mit CSRF-Header, Anmelden mit Rücksprungziel, Signaturzähler, Wiederholung derselben Antwort, fremder Schlüssel mit gleicher ID, falscher Origin, abgelaufene Challenge (> 5 min) → 401; Anmeldung hebt Passwort-Sperre auf; Entfernen; Login-Knopf nur mit angelegtem Passkey; nicht im JSON-Export
    wie: automatisiert (PHPUnit mit Software-Authenticator ES256)
    ergebnis: ok
    datum: 2026-09-28
  - was: Passkey im Browser – Anlegen in den Einstellungen, Abmelden, „Mit Passkey anmelden“ mit Rücksprung auf /verlauf; Darstellung 390 px
    wie: Chromium mit virtuellem Authenticator (CDP WebAuthn)
    ergebnis: ok
    datum: 2026-09-28
  - was: Athletenprofil MCP – leeres Profil mit Hinweis, update_athlete_profile legt Fassung an, gleicher Text (auch mit CRLF/Leerraum) keine Fassung, Abschnitt einzeln, as_of (Stand am Tagesende in Zeitzone des Athleten), include_history (neueste zuerst, nur mit section), unbekannter Abschnitt/Datum/zu lang → Fehler, Audit-Log, Schreibsperre
    wie: automatisiert (PHPUnit über /mcp)
    ergebnis: ok
    datum: 2026-09-28
  - was: Athletenprofil Web – Übersicht leer/gefüllt, Bearbeiten mit Grund, HTML wird escaped, CSRF, Konflikt bei zwischenzeitlicher Änderung durch Claude (409, eigener Text bleibt, nichts überschrieben), unverändert, zu lang (422), Fassungen, unbekannter Abschnitt 404, Login nötig, Zusammenfassung in Einstellungen
    wie: automatisiert + Browser 390/1280 px (kein Überlauf)
    ergebnis: ok
    datum: 2026-09-28
  - was: Offline Server – Stand-Feld in Check-in/Einheit, Konflikt 409 mit erhaltenen Eingaben und ohne Überschreiben, erneutes Speichern mit aktuellem Stand übernimmt; X-Offline-Queue → 204/401/409/422 (Schmerz mit Warnhinweis 204); /offline/token mit und ohne Sitzung; offline_erfasst als performed_at (unplausibel alt → ignoriert); Vorladeliste nur in der aktuellen Woche, ohne Ruhetag; Skript in beiden Layouts
    wie: automatisiert (PHPUnit)
    ergebnis: ok
    datum: 2026-09-28
  - was: Offline Browser – Service Worker übernimmt, Vorladen (14 Seiten: 2 Wochen, Check-in/Schmerz heute, alle Einheiten), bei gestopptem Server Einheit aus dem Speicher mit Hinweis, nicht gespeicherte Seite → Hinweisseite, Check-in/Rückmeldung/Schmerz offline gepuffert, nach Serverstart automatisch gesendet; Einheit am „Rechner“ geändert → Konflikt-Hinweis, „Trotzdem übernehmen“ sendet ohne Stand; Sitzung weg → „wartet auf Anmeldung“, Login-Seite löscht gespeicherte Seiten, nach Login gesendet; ungültige Eingabe → „nicht übernommen“; Darstellung 390 px
    wie: Chromium/Playwright mit echtem Netzfehler (lokaler Server gestoppt; Playwrights Offline-Schalter erfasst den Service Worker nicht)
    ergebnis: ok
    datum: 2026-09-28
  - was: Cronjob /cron/intervals-sync bei Lima-City eingerichtet, Aufruf liefert status ok
    wie: manuell durch Athlet
    ergebnis: ok
    datum: 2026-09-28
  - was: Passkey auf echtem Gerät angelegt und damit angemeldet
    wie: manuell durch Athlet auf training.gen-em.org
    ergebnis: ok
    datum: 2026-09-28
  - was: Offline auf echtem Gerät (Flugmodus): Seiten lesbar, Eingabe gepuffert und nach Netzrückkehr übernommen
    wie: manuell durch Athlet auf training.gen-em.org
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen:
  - was: JSON-Export herunterladen und in einem Editor/Programm öffnen
    wie: manuell durch Athlet
  - was: Einstellungen zeigen Anzahl und letzten Abgleich des Spiegels; einmalig tage=365 für die Vorgeschichte
    wie: manuell durch Athlet
  - was: Rückschreiben mit echtem Konto (V-03/V-04) – nach Rückmeldung zu einer Einheit mit Aktivität in Intervals.icu RPE, Gefühl (Richtung!) und Kommentar prüfen
    wie: manuell durch Athlet
  - was: Passkey entfernen; weitere Geräte; Passwort-Login funktioniert weiter
    wie: manuell durch Athlet
  - was: Athletenprofil – in AP-08 von Claude über update_athlete_profile befüllen lassen; auf /profil lesen, einen Abschnitt korrigieren, frühere Fassung ansehen; in Claude get_athlete_profile mit as_of prüfen
    wie: manuell durch Athlet (nach Migration auf Schema 18; Connector ggf. neu verbinden, damit das neue Tool erscheint)
  - was: Offline-Konflikt auf echtem Gerät (Eintrag am Rechner ändern) und „Trotzdem übernehmen“
    wie: manuell durch Athlet
  - was: Verlauf mit echten Daten nach einigen Wochen Nutzung
    wie: manuell durch Athlet
```

## AP-10 Backup und Update-Mechanik

```yaml
ap: AP-10
geprueft:
  - was: Verschlüsselung OpenSSL-kompatibel – mit PHP verschlüsselte Datei per openssl 3.0 CLI entschlüsselt (richtiges Passwort ok, falsches "bad decrypt")
    wie: manuell (Code-Instanz, openssl enc -d …) und automatisiert im Restore-Test
    ergebnis: ok
    datum: 2026-09-28
  - was: Restore-Test – Beispieldaten (Block, Woche, Einheit mit Sonderzeichen/Semikolon/Anführungszeichen im JSON, Durchführung, Schmerz, Check-in, Audit-Log, Benutzer) sichern, mit openssl-CLI entschlüsseln, gunzip, alle Tabellen löschen, Dump einspielen → Daten identisch, Schemastand 14, Sessions/Tokens leer, srpe_load neu berechnet
    wie: automatisiert (PHPUnit gegen MariaDB 10.11)
    ergebnis: ok
    datum: 2026-09-28
  - was: Pre-Migration-Dump vor ausstehender Migration (Name mit altem Schemastand), kein Dump ohne ausstehende Migration, Rotation auf 5 Dateien
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Fehlgeschlagener Dump verhindert Migration – zu kurzes Passwort, Backup-Ordner nicht anlegbar; /admin/migrate antwortet 500 „keine Migration ausgeführt“, Schemastand unverändert
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Schreibsperre – Datenbank einen Stand zurück → Hinweis auf allen Seiten, Check-in und Zeitzone 503 ohne Speichern; Migrationsknopf legt Dump an und behebt die Abweichung; danach Speichern möglich
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Download aus den Einstellungen (Dateiname, Content-Type, entschlüsselbar, Audit-Log, CSRF)
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Schreibsperre für MCP-Schreibtools (upsert_block bei Abweichung → Fehler „Update erforderlich“)
    wie: automatisiert (McpToolsTest, AP-05)
    ergebnis: ok
    datum: 2026-09-28
  - was: Cron-Endpunkt – ohne Secret 503, falscher Schlüssel 403, Versand mit Anhang und Anleitung, Intervall (übersprungen), force, Fehler gespeichert und in Woche/Einstellungen angezeigt
    wie: automatisiert (simulierter Mailer)
    ergebnis: ok
    datum: 2026-09-28
  - was: BACKUP_PASSWORD in der .env; /health backups ok
    wie: Deploy-Log
    ergebnis: ok
    datum: 2026-09-28
  - was: Backup per E-Mail: Test-Mail mit Anhang kam an (21 KB), Cronjob täglich eingerichtet
    wie: manuell durch Athlet (/cron/backup-mail?…&force=1)
    ergebnis: ok
    datum: 2026-09-28
  - was: Backup aus der Mail (Schema 18, Anlass mail) außerhalb des Servers mit openssl entschlüsselt: kein bad decrypt, gzip-Prüfsumme ok, SQL-Dump mit allen 18 Tabellen, Sessions/Tokens nur als Struktur
    wie: openssl enc -d (README) in der Code-Umgebung mit vom Athleten übermitteltem Passwort; entschlüsselte Kopie danach gelöscht
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen:
  - was: Pre-Migration-Dump in backups/ vorhanden
    wie: manuell (FTP)
  - was: Dump in eine leere Test-DB einspielbar (Abnahmekriterium Restore)
    wie: manuell durch Athlet – gunzip, Import in eine leere Test-DB bei Lima-City
  - was: Neues BACKUP_PASSWORD (das alte wurde im Chat übermittelt) – eintragen, sicher ablegen, ein neues Backup selbst entschlüsseln; altes Passwort für ältere Backups aufbewahren
    wie: manuell durch Athlet
```

## AP-11 Kalender (CalDAV)

```yaml
ap: AP-11
nachtrag: Sammeltermin je Tag (D-60, 0.19.0) – Unterpunkt T8 in docs/konzept/gefuehrte-einheit.md; die Einträge bis 0.18.0 beschreiben den Termin je Einheit
geprueft:
  - was: (bis 0.18.0) Wochenplan → Termine je Einheit ohne Ruhetag (PUT mit Basic-Auth an CALDAV_URL), update_session (Datum, erledigt → „✓“), Rückmeldung „ausgelassen“ auf der Webseite → STATUS:CANCELLED, Woche ersetzen → ersetzte Termine gelöscht, neue angelegt
    wie: automatisiert (PHPUnit, simulierter CalDAV-Server)
    ergebnis: ok
    datum: 2026-09-28
  - was: Fehler (HTTP 401, Netz weg) – Einheit bleibt gespeichert, fehler_kalender mit Hinweis, Audit calendar_error, Anzeige in den Einstellungen; stündlicher Abgleich überträgt nach und löscht verwaiste eigene Termine, fremde bleiben; Knopf „Abgleichen“ mit Anzahl bzw. Fehlermeldung
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Ohne Konfiguration keine Anfragen; http-URL → Kalender aus, Schreib-Tools laufen, /health ungueltig_kein_https, Hinweis in den Einstellungen; Cron ohne Intervals und ohne Kalender 503
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: iCalendar – ganztägig (DTSTART/DTEND VALUE=DATE), UID, Escaping von ; , Zeilenumbruch, Zeilen ≤ 75 Oktette ohne geteilte UTF-8-Zeichen, Beschreibung mit Kurzplan und Link
    wie: automatisiert (Unit-Test)
    ergebnis: ok
    datum: 2026-09-28
  - was: Echter CalDAV-Server – Anlegen, Ersetzen (✓ im Titel), REPORT mit Zeitraum (nur passende Termine), Löschen (auch doppelt), iCalendar vom Server angenommen
    wie: Rauchtest gegen Radicale 3.8 (lokal, http nur für den Test)
    ergebnis: ok
    datum: 2026-09-28
  - was: Erinnerung (D-52) – Standard PT5H, Ändern auf 06:30 → sofort neu übertragen (PT6H30M), 00:00 → PT0S, Aus → kein VALARM, erledigt/ausgelassen ohne Erinnerung, ungültige Uhrzeit 422, Kalender nicht erreichbar → Einstellung gespeichert mit Hinweis; Seite 390 px ohne Überlauf; VALARM von Radicale angenommen
    wie: automatisiert (PHPUnit) + Browser + Radicale
    ergebnis: ok
    datum: 2026-09-28
  - was: Nextcloud – App-Passwort und CALDAV_* in der .env, Einstellungen → „Abgleichen“ erfolgreich
    wie: manuell durch Athlet (nach Deployment 0.14.0)
    ergebnis: ok
    datum: 2026-09-28
  - was: "T8/D-60 (0.19.0): Sammeltermin je Tag – eine Einheit „Typ: Titel“ ohne „✓“, mehrere „Training: A + B + C“ in Planreihenfolge, STATUS immer CONFIRMED (auch ausgelassen), URL zur Woche, CATEGORIES je Typ einmal, Beschreibung je Einheit mit Überschrift (bei mehreren), Kurzsatz, Kurzplan mit Status, Begründung, Link und Trennlinie (bei mehreren); Erinnerung nur, solange eine Einheit geplant/verschoben ist; Ruhetage nie im Termin (Testfälle K-01 bis K-07)"
    wie: automatisiert (DayEventTest: K-01–K-03, K-07; CalendarTest: K-04–K-07)
    ergebnis: ok
    datum: 2026-09-28
  - was: "T8/D-60: Wochenplan mit zwei Einheiten an einem Tag → ein Termin; update_session verschiebt eine Einheit → alter und neuer Tag neu geschrieben, letzte Einheit weg → Termin gelöscht; Rückmeldung „ausgelassen“ → Termin bleibt, Status in der Beschreibung; Woche ersetzen → Tage ersetzter Einheiten neu bzw. gelöscht; Abgleich löscht alte Einzeltermine (auch zu bestehenden Einheiten) und verwaiste Sammeltermine, fremde bleiben, Zählung in Tagen; Erinnerungstexte in S8"
    wie: automatisiert (CalendarTest, simulierter CalDAV-Server)
    ergebnis: ok
    datum: 2026-09-28
  - was: "Review T8: Tagestermin dreimal geleert und neu belegt mit nachgebildetem Nextcloud-Papierkorb – jede Neuanlage mit neuer Fassung (training-tag-<Datum>-1/-2/-3, UID passend), keine Fehler; Abgleich schreibt dieselbe Fassung, leerer Tag erhöht sie; Einzeltermine geänderter Einheiten auch außerhalb des Zeitraums entfernt, unberührte ältere bleiben; Ruhetage im Abgleich, Zählung in Tagen, Reihenfolge nach sort_order, ersetzte Woche mit behaltener Einheit; Abbruch nach dem ersten Fehler mit Audit je Tag; eine Erinnerung bei zwei offenen Einheiten, Begründung je Einheit"
    wie: automatisiert (CalendarTest, DayEventTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: "T8/D-60: Echter CalDAV-Server – Sammeltermin mit drei Einheiten, Sonderzeichen, Faltung und VALARM angenommen; Ersetzen; REPORT mit Zeitraum; alter Einzeltermin training-session-<id>.ics gelöscht, fremder Termin bleibt"
    wie: Rauchtest gegen Radicale (lokal, http nur für den Test)
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen:
  - was: Erinnerung um 05:00 kommt auf dem Handy an (Nextcloud-Kalender per DAVx⁵/iOS-Konto eingebunden); andere Uhrzeit in den Einstellungen wirkt nach dem nächsten Sync
    wie: manuell durch Athlet (nach Deployment 0.15.0 und erstem Wochenplan)
  - was: Termine nach dem ersten Wochenplan im Nextcloud-Web und auf dem Handy sichtbar; „zuletzt übertragen“ in den Einstellungen höchstens 1 h alt (stündlicher Cron)
    wie: manuell durch Athlet
  - was: Änderung aus Claude (update_session, Status) erscheint im Kalender; Rückmeldung auf der Webseite erscheint als Status in der Beschreibung des Tagestermins (seit 0.19.0 kein „✓“ im Titel)
    wie: manuell durch Athlet
  - was: "T8/D-60 nach Deploy 0.19.0: „Abgleichen“ in den Einstellungen (oder nächster stündlicher Abgleich) – im Nextcloud-Kalender (Web und Handy) je Trainingstag genau ein Termin, alte Einzeltermine im Zeitraum 7 Tage zurück bis 8 Wochen voraus verschwunden, ältere bleiben; Tag mit zwei Einheiten zeigt „Training: … + …“ und beide Einheiten in der Beschreibung; Erinnerung einmal je Tag"
    wie: manuell durch Athlet (Nextcloud-Web, Handy-Kalender per DAVx⁵/iOS-Konto)
  - was: "T8/D-60: Beschreibung mit mehreren Einheiten und Trennlinie auf dem Handy gut lesbar (Zeilenumbrüche, Links antippbar)"
    wie: manuell durch Athlet
```

## AP-12 Morgen-Check-in (Morgentest)

```yaml
ap: AP-12
auftrag: docs/konzept/morgen-checkin.md
geprueft:
  - was: Ampel – alle 10 Testfälle aus Abschnitt 8 (u. a. steigend 2→3→4 rot, steigend < 4 grün, Vortag fehlt, eine Seite, nicht streng steigend, 0/0 grün und als 0 gespeichert), Steuerwert null ≠ 0, Wochenausgangswert (Mo 2/Mi 3 → über; Mo leer/Di 1 → 1; Vorwoche zählt nicht), Abklärung
    wie: automatisiert (Unit-Test MorningStatusTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: Formular – ohne Vorauswahl, Pflicht Erholung/Muskelkater (422), Wert 11 abgelehnt, 0/0 als 0 und leere Felder als NULL gespeichert, Schwellung nur mit umgeknickt, unbekannte Warnzeichen verworfen, Überschreiben am selben Tag inkl. Leeren eines Werts, Audit-Zusammenfassung, Hand rechts nach Stichtag ausgeblendet und ignoriert, Einstellung Stichtag
    wie: automatisiert (MorningCheckinTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: Startseite – Formular solange kein Morgentest, danach Zusammenfassung (Ampel, Grund, Wochenausgangswert-Hinweis, Abklärung hervorgehoben); MCP get_morning_checks (Format 6.1, neueste zuerst, Vortagseinheiten, grüne Tage, Abdeckung, leere Felder weggelassen) und get_week_overview (Steuerwert/Ampel je Tag, Tage grün, Abdeckung)
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Zeitzone über die Umstellung am 25.10.2026 (00:30 MESZ bzw. MEZ → richtiger Kalendertag, Steigung über die Umstellung)
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Migration 0020/0021 auf befüllter Datenbank (Rückweg angewendet, Altbestand angelegt, erneut migriert – Altwerte unverändert, neue Spalten leer); neue Schmerzorte speicherbar; Export mit Warnzeichen als Liste
    wie: automatisiert; Migration auf leerer Datenbank in jedem Testlauf
    ergebnis: ok
    datum: 2026-09-28
  - was: 375 px – kein horizontales Scrollen, erneutes Tippen leert den Morgentest, Schwellung erscheint nach „umgeknickt“, nach dem Speichern Ampel auf der Startseite
    wie: Browser (Chromium/Playwright)
    ergebnis: ok
    datum: 2026-09-28
  - was: Stabilität der Testsuite (155 Tests)
    wie: 18 volle Läufe lokal (MariaDB)
    ergebnis: 17 grün; 1 Lauf mit einem einzelnen Fehler, nicht reproduzierbar und mangels Protokoll nicht zuzuordnen – beobachten (CI). Nachtrag 2026-09-28 (AP-14): Ursache gefunden (Aufräumen der MCP-Sitzungen mit verstellter Test-Uhr löschte die neue Sitzung, testDaylightSavingSwitch), behoben in 0.18.0; danach 25 Einzelläufe und 3 volle Läufe grün
    datum: 2026-09-28
noch_zu_pruefen:
  - was: Morgens auf dem Smartphone erfassen (auch offline), Ampel ansehen; nachträgliche Änderung am selben Tag
    wie: manuell durch Athlet (nach Deployment 0.16.0)
  - was: get_morning_checks über den Claude-Connector (Format 6.1, Tool-Beschreibung mit Skalen und Ampelregeln); Connector ggf. neu verbinden, damit das Tool erscheint
    wie: manuell durch Athlet im Trainer-Chat
```

## AP-13 App-Icon und Begründungstexte

```yaml
ap: AP-13
auftrag: docs/konzept/gefuehrte-einheit.md (Teile A und B, T1–T2)
geprueft:
  - was: P-A1 – Chrome Android installiert die App mit Lama-Icon (noch mit dem Kopf, vor der Umsetzung)
    wie: Gerätetest durch Athlet
    ergebnis: ok
    datum: 2026-09-28
  - was: I-01 Manifest – gültiges JSON, id /woche, description, jede Icon-Datei vorhanden, sizes = PNG-Kopf (48/96/192/512 any, 512 maskable getrennt)
    wie: automatisiert (AppIconTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: I-02 beide Seitenrahmen (Setup und Login = layout-auth, Woche = layout-app) mit theme-color, Manifest, SVG-Favicon, PNG-Icons mit sizes, apple-touch-icon 180; Kennung /assets/lama.svg in Topbar, Navigation und Login-Karte; kein Verweis mehr auf lama-kopf
    wie: automatisiert (AppIconPagesTest, AppIconTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: I-03 HEAD /favicon.ico über den Dev-Router → 200, Content-Type image/x-icon, Länge stimmt; Manifest application/manifest+json; favicon.ico enthält 16/32/48
    wie: automatisiert (AppIconTest, PHP-Built-in-Server)
    ergebnis: ok
    datum: 2026-09-28
  - was: Icon-Satz gerendert (V3 any/maskable/180, V2 Favicon 16 px), Sichtprüfung Kontaktbogen; Mockups nach Kennungswechsel ohne Überlauf, fehlende Ressourcen oder Skriptfehler
    wie: Chromium/Playwright (build-icons.cjs, screenshots.cjs), manuell
    ergebnis: ok
    datum: 2026-09-28
  - was: T2 Schreib-Tools – ohne focus und ohne coach_summary Fehler mit Liste der betroffenen Einheiten (Ruhetag ausgenommen), nichts geschrieben; Grenzlängen focus 255/256, coach_summary 200/201, coach_notes und coach_rationale 1500/1501 in Zeichen (Umlaute); Texte getrimmt gespeichert
    wie: automatisiert (McpToolsTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: T2 Lese-Tools und update_session – get_week_overview fokus/begruendung/kurz (auch Ruhetag mit Kurzsatz), get_session_detail coach_summary; update_session ändert Kurzsatz, leerer Kurzsatz abgelehnt (Ruhetag: entfernt), leere Begründung entfernt, focus dort unbekannt; überlange Altdaten blockieren andere Änderungen nicht und fehlen in der Übersicht
    wie: automatisiert (McpToolsTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: T2 Anzeige – S2 Kurzsatz in eigener Karte ohne „Fokus …“ in der Kopfzeile, „mehr“ als details (ohne JavaScript) nur mit ausführlichem Text, Woche ohne Plan unverändert, Altdaten „Begründung der Woche“; S3 Kurzsatz und „mehr“, Altdaten „Trainer-Notiz“, HTML maskiert; 375 px ohne Überlauf, auch mit einem langen Wort (Adresse) im Kurzsatz (Review, Umbruch nachgezogen)
    wie: automatisiert (WebsiteTest) + Browser (Chromium, lokale Instanz)
    ergebnis: ok
    datum: 2026-09-28
  - was: T2 Kalender – Beschreibung beginnt mit dem Kurzsatz, dann Kurzplan mit Priorität/Dauer, „Trainer: …“ gekürzt auf 1 000 Zeichen, Link; Altdaten ohne Kurzsatz beginnen mit dem Kurzplan
    wie: automatisiert (SessionEventTest; seit T8/0.19.0 DayEventTest, je Einheit im Sammeltermin)
    ergebnis: ok
    datum: 2026-09-28
  - was: Migration 0022 auf leerer Datenbank (jeder Testlauf) und auf befüllter Datenbank (Rückweg 0020–0022 angewendet, erneut migriert)
    wie: automatisiert (MigratorTest, MorningCheckinTest)
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen:
  - was: P-A2 – Chrome Desktop, DevTools → Application → Manifest ohne Fehler, alle Icons geladen; erwartet nur die zwei Hinweise „Richer PWA Install UI … desktop/mobile“ (keine Screenshots im Manifest, Auftrag O-05)
    wie: manuell durch Athlet (nach Deployment; 0.17.0 bis 0.19.0 kommen gemeinsam mit einem Pull Request)
  - was: P-A3 – LibreWolf Android, von /login und von /woche „Zum Startbildschirm“ → beide Male Lama-Icon (V3)
    wie: Gerätetest durch Athlet (alte Verknüpfung vorher entfernen); Screenshot ins Prüfprotokoll
  - was: P-A4 – LibreWolf about:config dom.serviceWorkers.enabled, dom.manifest.enabled notieren
    wie: manuell durch Athlet (nur Befund)
  - was: P-A5 – iOS Safari „Zum Home-Bildschirm“ → Lama 180 px (falls Gerät vorhanden)
    wie: Gerätetest durch Athlet
  - was: P-A6 – curl -I https://training.gen-em.org/manifest.webmanifest und /favicon.ico → 200, application/manifest+json bzw. image/x-icon, Cache-Control max-age=604800 bei /favicon.ico
    wie: manuell (curl) nach Deployment
  - was: P-A1 erneut mit dem neuen Icon (V3) in Chrome Android
    wie: Gerätetest durch Athlet
  - was: T2 – Wochenplan aus dem Projekt-Chat mit focus, coach_notes, coach_summary und coach_rationale schreiben (Connector ggf. neu verbinden); ohne Kurzsatz meldet das Tool die fehlenden Felder
    wie: manuell durch Athlet im Trainer-Chat (nach Deployment; 0.17.0 bis 0.19.0 kommen gemeinsam mit einem Pull Request)
  - was: T2 – S2 und S3 auf dem Smartphone: Kurzsatz sichtbar, „mehr“ klappt auf (auch mit abgeschaltetem JavaScript)
    wie: manuell durch Athlet
  - was: T2 – Kalendertermin im Nextcloud-Kalender zeigt den Kurzsatz als erste Zeile der Beschreibung (bei mehreren Einheiten eines Tages je Abschnitt nach der Überschrift „Typ: Titel“, D-60)
    wie: manuell durch Athlet
```

## AP-14 Geführte Einheit

```yaml
ap: AP-14
auftrag: docs/konzept/gefuehrte-einheit.md (Teil C, T3–T7; Nachtrag T8 unter AP-11)
geprueft:
  - was: T3 Ablaufplan – Testfälle 8.1 A-01 bis A-12 (Wiederholungen, Halten s/min/Bereich, max, Hangboard mit und ohne Sätze, Block, offen, Ausdauer/Ruhe ohne Plan, Reihenfolge und Index) und alle übrigen kind-Werte (campus, bouldern_limit, ausdauer_route, zugkraft, antagonisten); A-01 bis A-10, die kind-Fälle und A-12 gültig nach plan_json-Schema, A-11 zusätzlich mit leeren/ungültigen Plänen; Schreibweisen der Haltezeit (s, sek, sec, min, Bereich mit - und –, 0, Komma)
    wie: automatisiert (AblaufplanTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: S3-Soll-Texte nach Umzug in PlanFormat unverändert; S3 nach Umbau in Teilvorlagen (Ist-Felder, Rückmeldung) unverändert
    wie: automatisiert (WebsiteTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: T4 – Startknopf nur für Kraft/Haltung/Mobilität/Klettern mit Plan (ohne Plan kein Knopf), „Erneut durchgehen“ bei erledigt; A-11 Ausdauer mit modus=start → S3, Ruhetag 404, Einheit ohne Plan → S3 mit Rückmeldung
    wie: automatisiert (GuidedSessionTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: T4 – Aufbau S9 für Kraft (Wiederholungen ohne Timer, Halten mit Timer 00:30), Klettern (Hangboard 00:07, Block 40:00, offen mit Hinweis), Feldnamen wie S3 mit Soll vorbelegt, Kurzsatz oben, Skript-Bedienung versteckt; POST aus S9 speichert wie aus S3; 422 und 409 zeigen wieder S9 mit den Eingaben; Timer-Signale-Vorgabe aus app_setting
    wie: automatisiert (GuidedSessionTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: T4-Abnahme – ohne JavaScript alle Schritte sichtbar und keine Skript-Bedienung, ausgefüllt und gespeichert („Gespeichert.“, Werte in der Datenbank); 375 px ohne horizontales Scrollen (Kraft, Klettern); Speichern liegt über der unteren Navigation
    wie: Browser (Chromium/Playwright, JavaScript aus, lokale Instanz)
    ergebnis: ok
    datum: 2026-09-28
  - was: T5 Kern – Z-01 (45 s: Start, 10 s, 3-2-1, kein 30-s-Ton), Z-02 (Pause 60 s: 30/10/3-2-1, dann Start Satz 2 zeitstempelgenau), Z-03 (Abschlusston, fertig, normale Farbe, Weiter), Z-04 (Anhalten bei 20 s, Fortsetzen), Z-05 (2 min Hintergrund: Folgephase, ein Hinweiston), Z-06 (Fortsetzen-Frage, Verfall, Plan geändert, gespeichert), Z-07 (Satz erledigt → Pausentimer, Satz 3 fertig), Z-10/Z-11 (teilweise, Minuten), Z-12 (abgeschickt bleibt bis neuer Stand), Hangboard 7/3 × 6, Block, offen, Bedienung
    wie: automatisiert (node --test server/tests/js/, 14 Fälle)
    ergebnis: ok
    datum: 2026-09-28
  - was: T5 Browser – rot „bereit“, grün in der Arbeit (theme-color), Startton 2 Töne + Vibration + Wake Lock, Töne bei 10 s und 3-2-1, Pause mit 30/10/3-2-1 und automatischem Satz 2, Anhalten/Fortsetzen, Neu laden mit Fortsetzen (Schritt, Satz, Restzeit), Stumm (keine Töne/Vibration, Blinken), Wiederholungen mit Pausentimer, Überspringen → teilweise und gemessene Dauer, Speichern (Werte in der Datenbank, Fortschritt gelöscht), Hintergrund (ein Hinweiston), Speichern ohne Netz (Puffer, Fortschritt bis zur Zustellung, danach gelöscht), keine Skriptfehler, 375 px ohne Überlauf
    wie: automatisiert (Playwright mit gesteuerter Uhr, tests/e2e/run.sh; lokal Chromium, in der CI Chrome des Runners)
    ergebnis: ok (zwei Läufe hintereinander)
    datum: 2026-09-28
  - was: T5 Sichtprüfung – Zustände Wiederholungen, Pause, bereit, Arbeit, angehalten, Abschluss auf 375 px und 1280 px; Aktionsleiste über der unteren Navigation
    wie: Browser (Chromium, lokale Instanz), manuell
    ergebnis: ok
    datum: 2026-09-28
  - was: T6 – S8 Bereich „Training“ mit Timer-Signalen (Standard an, Speichern aus/an, ungültiger Wert abgelehnt, Audit), Vorgabe wirkt in S9 (data-ton); Woche lädt S9 für heute und morgen vor (nicht für vergangene oder spätere Tage); 375/1280 px ohne Überlauf
    wie: automatisiert (GuidedSessionTest) + Browser
    ergebnis: ok
    datum: 2026-09-28
  - was: T6 Browser – Z-09 (S8 aus → S9 stumm, Umschalten in S9 ändert S8 nicht); S9 öffnet bei Netzausfall aus dem Seiten-Cache (gespeicherter Stand, Skript läuft); Z-12 Speichern ohne Netz im Puffer, Nachsenden bei Netz, Fortschritt danach gelöscht
    wie: automatisiert (Playwright, tests/e2e/run.sh, Netzausfall über Proxy)
    ergebnis: ok
    datum: 2026-09-28
  - was: "Review T5 (Kern): Tipp nach abgelaufener Phase verworfen (letzte Arbeitsphase → keine nächste Übung, Pausenende → kein gezählter Satz, Arbeitsende → Pause nicht übersprungen), Sperre 500 ms; Zurück/Weiter behalten erledigte Übungen, nachgeholte Übung nicht mehr übersprungen (Status erledigt); Dauer endet mit dem Abschluss (auch nach Zurück), erneutes Training misst neu; Schritt öffnen nach Ist-Fehler ohne Änderung von Erledigt/Übersprungen; Fortsetzen nach Speichern ohne Takt nur Hinweiston"
    wie: automatisiert (node --test, 17 Fälle)
    ergebnis: ok
    datum: 2026-09-28
  - was: "Review T5 (Browser): Fokus nach Fortsetzen auf der Übung; Stumm-Schalter behält den Namen „Ton und Vibration aus“; Blinken erst in den letzten 3 s und Ende mit der Phase; Tipp auf „Anhalten“ nach abgelaufener, noch nicht neu gezeichneter Phase → Pause statt angehalten (Gegenprobe ohne Korrektur schlägt fehl); Dauer-Marke im Abschluss bleibt nach 10 min Warten; Stumm-Wahl vor der ersten Eingabe übersteht Neuladen ohne Fortsetzen-Frage"
    wie: automatisiert (Playwright, run.sh, 14 Prüfungen)
    ergebnis: ok
    datum: 2026-09-28
  - was: "Review T5 (Server): ungültiger Ist-Wert in Übung 2 → 422, data-ist-fehler, nur Schritt 2 mit data-invalid und nur dessen Ist-Karte rot; Fehler in der Rückmeldung ohne Ist-Markierung"
    wie: automatisiert (GuidedSessionTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: "Review T5 (Testaufbau): run.sh auf freiem Port, Abbruch mit Protokoll bei belegtem Port; Warten auf den Service Worker begrenzt; CI-Schritt mit 10 min Grenze"
    wie: manuell (run.sh mehrfach, Port frei gewählt) + Durchsicht des Workflows
    ergebnis: ok
    datum: 2026-09-28
  - was: T7 – Dokumente konsistent (Changelog 0.17.0–0.19.0, README, Hauptkonzept AP-11/AP-13/AP-14 und D-55 bis D-60, Datenmodell, Branding, Auftrag Abschnitt 12, Prüfprotokoll); Versionsnummer 0.19.0 in App.php
    wie: Durchsicht und Suche nach veralteten Angaben (Termin je Einheit, „✓“, SessionEvent, Zählerstände, Port) + unabhängiges Review der Dokumente zu T8
    ergebnis: ok
    datum: 2026-09-28
  - was: "Abschluss-Review T6: S9 mit „an“ vorgeladen, danach S8 auf „aus“ – ohne Netz startet die gespeicherte S9 stumm (Wert aus localStorage); S8 trägt data-timer-ton; Gegenprobe ohne Korrektur schlägt fehl"
    wie: automatisiert (Playwright, run.sh, Z-12-Teil; GuidedSessionTest)
    ergebnis: ok
    datum: 2026-09-28
  - was: "T9 (E-23): Kletterblock ohne Haltezeit und Dauer mit Sätzen → satzweise (Zugkraft 4 Sätze mit 120 s Pausentimer, Antagonisten 3 Sätze ohne Timer), ein Satz ohne Zeiten bleibt offen (A-13, A-14); S9 zeigt „n Sätze“ mit Ziel, im Browser Satz erledigt → rote Pause 02:00, 375 px ohne Überlauf"
    wie: automatisiert (AblaufplanTest, GuidedSessionTest) + Browser (Chromium, lokale Instanz, Screenshots)
    ergebnis: ok
    datum: 2026-09-28
  - was: "T9: Speichern-Leisten auf 375 px beim Scrollen über der unteren Navigation (Unterkante der Leiste = Oberkante der Navigation) in S3, Check-in, Schmerz und S9; Desktop unverändert"
    wie: Browser (Messung der Positionen, Screenshot S3 gescrollt)
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen:
  - was: T5 Gerätetest Android – Töne und Vibration bei Start, 30 s, 10 s, 3-2-1 und Abschluss hörbar/spürbar; Grün/Rot und Browserleiste; Bildschirm bleibt während der Einheit an; Stumm in der Einheit (Blinken auch in der 3-s-Pause bei Hangboard 7/3); Fortsetzen nach versehentlichem Neuladen; Tipp kurz vor Phasenende wirkt wie erwartet (kein Sprung zur nächsten Übung)
    wie: Gerätetest durch Athlet (nach Deployment 0.19.0), am besten mit einer Einheit mit Haltezeiten (z. B. Unterarmstütz 45 s)
  - was: Tonhöhen/-längen nach Gehör anpassen (O-04); 30-s-Ton erst bei Phasen über 45 s ist entschieden (E-23)
    wie: Rückmeldung des Athleten nach dem Gerätetest
  - was: T6 im Flugmodus auf dem Smartphone – Woche mit Netz öffnen, dann Flugmodus; geführte Einheit von heute öffnen (aus dem Cache), durchgehen, speichern („Offline gespeichert“), Netz an → Rückmeldung erscheint in der Woche; Einstellung „Timer-Signale aus“ → S9 startet stumm
    wie: Gerätetest durch Athlet (nach Deployment 0.19.0)
  - was: "T9 auf dem Smartphone: Kletterblock mit Sätzen und Pause im Training (Satz erledigt → Pausentimer, Töne); Speichern-Leisten in S3, Check-in und Schmerz beim Scrollen sichtbar"
    wie: Gerätetest durch Athlet (nach Deployment 0.20.0)
```

## AP-05 MCP-Tools produktiv

```yaml
ap: AP-05
geprueft:
  - was: tools/list enthält alle zehn Tools; Scope training:read darf lesen, aber nicht schreiben (Fehler mit Hinweis auf training:write)
    wie: automatisiert (PHPUnit über /mcp)
    ergebnis: ok
    datum: 2026-09-28
  - was: write_week_plan – ohne Block abgelehnt; ungültige Einheiten (Datum außerhalb der Woche, Plan ohne Workout-Text) → nichts geschrieben, alle Fehler gemeldet; gültiger Plan mit Kraft, zwei Ausdauer (Sportart TrailRun), Ruhetag → DB, Woche bestätigt, Events 5001/5002 mit external_id, Dauer und Sportart; zweites Schreiben ohne replace_existing abgelehnt
    wie: automatisiert (simulierte Intervals-API)
    ergebnis: ok
    datum: 2026-09-28
  - was: get_week_overview – Block, sRPE je Typ, Compliance, Abweichung, Aktivität per paired_event_id (Zonen in Minuten, Load), Aktivität ohne Plan, Schmerz der Woche, Check-in-Abdeckung, Form (CTL−ATL), Skalen; Antwort < 8 000 Zeichen
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: update_session – Verschieben aktualisiert Event (neues Datum), unbekannte Felder/Woche ohne Plan abgelehnt, „ausgelassen“ löscht Event; replace_existing behält Einheit mit Rückmeldung, ersetzt geplante (Event gelöscht); Intervals-Fehler 500 → Einheit bleibt ohne Event, status teilweise, audit_log intervals_error; erneuter Sync legt Event an
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: get_block, get_pain_history (Trend steigend), get_wellness_trend (Tageswerte, Baseline), get_athlete_profile (ohne/mit Profildatei), get_session_detail unbekannt → Fehler; Schreibsperre
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Audit-Log vollständig für MCP-Schreibzugriffe (block_create, week_plan_write, intervals_event_create …)
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Echter MCP-Client (logiscape SDK) in der zustandslosen Revision 2026-07-28 – tools/list, get_week_overview, write_week_plan mit verschachtelten Argumenten
    wie: manuell (lokaler Server, statisches Token)
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen:
  - was: Aus dem Projekt-Chat Wochenübersicht abrufen (≤ 2 000 Tokens) mit echten Daten
    wie: manuell durch Athlet nach Deployment und Connector-Freigabe
  - was: Wochenplan schreiben → Einheiten in DB (Webseite /woche), Ausdauer-Events in Intervals.icu und auf der Uhr
    wie: manuell – zuerst upsert_block, dann write_week_plan für eine Testwoche; Webseite, Intervals-Kalender und Uhr prüfen; danach Testwoche per update_session/replace_existing bereinigen
  - was: Intervals-Feldnamen (ctl, atl, hrv, restingHR, sleepSecs, icu_training_load, paired_event_id) mit echten Daten
    wie: manuell – get_week_overview/get_wellness_trend mit Werten in Intervals.icu vergleichen
```

## AP-06 Wissensbasis (übernommen aus Konzept)

```yaml
ap: AP-06
probleme_loesungen:
  - datum: 2026-09-27
    was: Drei Literatur-Sitzungen (übergreifend, T1, T2) arbeiteten parallel vom selben Konzeptstand; alle vergaben D-21 ff. und V-11 ff.; T2 editierte das Konzept direkt
    loesung: Zusammenführung durch die planende Instanz mit Renummerierung (T1 → D-25–D-27, V-11–V-12; T2 → D-28–D-30, V-13–V-14; T3 → Q-07, Q-08, V-15). Regel in 13.2 und Umfang 1 festgeschrieben
  - datum: 2026-09-27
    was: Block übergreifend – PubMed-Verifikation L-P01 bis L-P08
    ergebnis: ok; Korrekturen – L-P05 DOI ergänzt; L-P06 Zitierfassung festgelegt (MSSE 2013;45(1):186-205), widersprüchliche Seitenangaben stammten aus fehlerhaften Sekundärzitaten; L-P07 Heftnummer ergänzt (52(3)); L-P08 DOI ergänzt, Open Access
  - datum: 2026-09-27
    was: Held et al. 2026 (zuvor nur Sekundärquelle)
    ergebnis: per PubMed bestätigt, als L-P09 aufgenommen
  - datum: 2026-09-27
    was: L-A02 2. Auflage
    ergebnis: Auflage belegt, Jahr/ISBN nicht auffindbar → offen
  - datum: 2026-09-27
    was: Block T1 – Autorenliste Casado et al. 2022 zunächst aus dem Gedächtnis falsch angegeben
    loesung: per Websuche und PubMed korrigiert; bestätigt Zitierregel D-13 und Pflicht zur bibliografischen Prüfung (V-11)
  - datum: 2026-09-27
    was: Block T1 – Haugen et al. 2022 zunächst mit Artikelnummer 18 (Repositoriumsangabe)
    loesung: laut PubMed Artikelnummer 46; korrigiert
  - datum: 2026-09-27
    was: Block T1 – Aussage „alle Reviews außer Vernillo sind Open Access" war falsch
    loesung: Seiler 2010, Vernillo 2017, Giandolini 2016 nicht in PMC; Casado 2022 unklar; Zugang je Quelle ausgewiesen
  - datum: 2026-09-27
    was: Block T1 – Tønnessen 2024 und Sandbakk 2025 zunächst als Reviews eingeordnet
    loesung: laut PubMed qualitative Studie bzw. Multiple-Case-Study mit Trainern → Praxisquelle, konfidenz mittel (D-25)
  - datum: 2026-09-27
    was: Blöcke T1 und T2 – Human-Kinetics-E-Books nur mit DRM (VitalSource), unvereinbar mit PDF-Kapitelprozess 13.1
    loesung: Formatanforderung D-26, Formatprüfung je Titel V-13; Springer-Titel bevorzugt digital beschaffbar
  - datum: 2026-09-27
    was: Block T2 – kein wissenschaftliches Standardwerk für Calisthenics; Progressionsreihenfolgen nicht studienbasiert
    loesung: Übungskatalog aus L-T2-04 mit konfidenz niedrig, Dosierung ausschließlich aus Kernset (D-29)
  - datum: 2026-09-27
    was: Blöcke übergreifend und T1 – Kenney/Wilmore/Costill mit unterschiedlicher Auflage gemeldet (8. 2022 vs. 9. 2024)
    loesung: L-A01 bleibt Referenz; Auflage beim Erwerb klären (13.4)
  - datum: 2026-09-27
    was: Block T3 – abweichendes Evidenzschema (A/B/C) und strengere Regel für Praxisquellen (E4) gegenüber D-25/D-29
    loesung: Vereinheitlicht als D-31 (bestätigt); Feld `stufe` in 13.2 eingeführt
  - datum: 2026-09-28
    was: 29 PDFs unsortiert in docs/literatur/ mit Verlags- und Archivdateinamen (z. B. s40279-017-0823-y.pdf, „… Anna’s Archive.pdf“)
    loesung: jede Datei am Inhalt (Titel, Autoren, DOI) identifiziert und einer ID aus 13.2 zugeordnet; alle DOIs stimmen mit 13.2 überein; nach D-51 umbenannt und in Blockordner sortiert; Felder `datei`/`kapitel` und Spalte „vorhanden“ in 13.4 ergänzt
  - datum: 2026-09-28
    was: Kenney/Wilmore/Costill liegt in der 7. Aufl. (2019) vor, ausgewählt ist die 8. (2022)
    loesung: Athlet entscheidet – 7. Aufl. vorläufig, 8./9. Aufl. bleibt auf der Beschaffungsliste (D-51)
  - datum: 2026-09-28
    was: Bücher bis 1876 Seiten und 65 MB, für Chat-Sitzungen zu groß (13.1 Schritt 1)
    loesung: Kapitel-PDFs nach Lesezeichen (NSCA, Kenney, Climbing Medicine, Concurrent Training) bzw. nach im Text gefundenen Kapitelanfängen (Scans Uphill Athlete, Overcoming Gravity); 179 Dateien mit 4–59 Seiten, Seitensummen je Buch geprüft
  - datum: 2026-09-28
    was: Kapitel-PDFs der E-Books zunächst bis dreimal so groß wie das Buch (Vorspann Kenney 45 MB bei 34 Seiten)
    loesung: interne Sprungverweise (Inhaltsverzeichnis, Index) zogen die Zielseiten samt Ressourcen mit; Kapitel-PDFs ohne Link-Annotationen erzeugt → zusammen 258 MB, größte Datei 11 MB; Links bleiben im Original
  - datum: 2026-09-28
    was: Scans ohne Lesezeichen mit Unregelmäßigkeiten – Uphill Athlete – PDF-Seiten 88–89 wiederholen 86–87, Druckseiten 149–150 fehlen; Overcoming Gravity – fehlerhafte Texterkennung (z. B. „ANO“ statt „AND“), PDF-Seiten 577/578 vertauscht
    loesung: Druckseiten je Abschnitt aus dem Versatz berechnet und in docs/literatur/README.md dokumentiert; bei Zitaten aus Overcoming Gravity Wortlaut gegen das Seitenbild prüfen
  - datum: 2026-09-28
    was: Die Kapitel-PDFs vergrößern das Repo um 258 MB (zusammen mit den Originalen rund 540 MB PDFs); der CI-Lauf checkt das ganze Repo aus
    loesung: Deployment lädt nur server/ hoch (geprüft); beobachten, bei Bedarf Sparse-Checkout ohne docs/literatur im Workflow
  - datum: 2026-09-28
    was: L-A02 Ferrauti (2. Aufl.) als 355-MB-PDF geliefert (per HTTPS-Download), zu groß für GitHub (Grenze 100 MB je Datei)
    loesung: Das PDF ist aus Einzelkapiteln zusammengesetzt und bettete gleiche Schriften und Bilder mehrfach ein. Ohne Dubletten neu geschrieben → Gesamtbuch 94 MB, 25 Kapitel-PDFs zusammen 98 MB (größte 16 MB). Text und Bilddaten (Prüfsumme) aller 911 Seiten gleich dem Original; interne Links wie bei den anderen Kapitel-PDFs entfernt. Nebenbei geklärt – 2. Aufl. 2025, ISBN 978-3-662-69523-4, zweiter Herausgeber Wiewelhove
  - datum: 2026-09-28
    was: V-07 – Schwellenwerte des Schmerzmonitoring-Modells nicht im Abstract, Volltext nicht in PMC; einzige gefundene Angabe aus nicht begutachteter Sekundärquelle
    loesung: V-07 als teilweise geführt; Volltext in Beschaffungsliste; Abweichung zu 14.5 als Q-13 nach AP-07
  - datum: 2026-09-28
    was: L-T3-02 – Kernaussagen im Konzept stärker formuliert als im Original-Abstract („am effizientesten" statt Trend; Wiederholungsbereiche nicht im Abstract)
    loesung: Kernaussagen auf Abstract-Wortlaut zurückgeführt; Wiederholungsbereiche bleiben als Sekundärzitat markiert, Prüfung am Volltext (liegt im Repo)
  - datum: 2026-09-28
    was: L-T3-05 – Stufe A, aber n = 9 ohne signifikante Gruppenunterschiede
    loesung: konfidenz niedrig; größere Folgestudie derselben Autoren als L-T3-18 aufgenommen
  - datum: 2026-09-28
    was: Literatur-Sitzung – zwei PubMed-Aufrufe (Volltext, ID-Konvertierung) ohne Freigabe abgebrochen
    loesung: Ersatzweise Metadaten-Abruf bzw. Verlagsseiten/Bibliothekskataloge; Volltext Sprague 2021 (PMC7905015) für Modellschwellen nicht genutzt
  - datum: 2026-09-28
    was: V-07 in 5.2 bei AP-07 geführt, Abnahme AP-06 verlangt V-07
    loesung: V-07 auf AP-06 (Literatur) und AP-07 (Schwellen) aufgeteilt
  - datum: 2026-09-28
    was: Teilblock Haltung/Rücken – einzige Kandidatenquelle (McGill) Stufe C; Frage, ob Haltungskorrektur überhaupt Beschwerden reduziert
    loesung: Stufe-A-Kern über PubMed aufgebaut (L-T2-15 bis L-T2-18); Grenze „Haltung ≠ Schmerz" als Pflichtinhalt der Karte (D-54 c); McGill zurückgestellt
  - datum: 2026-09-28
    was: Im Chat vorgesehene IDs L-T2-19 bis L-T2-22 für vier optionale Quellen; nur eine aufgenommen
    loesung: Carrasco-Uribarren als L-T2-19, übrige ohne ID in 13.3
  - datum: 2026-09-28
    was: Übergaben Teil A und B beruhten auf einem älteren Konzeptstand (letzte IDs D-37, Q-08); D-38 und Q-09 waren inzwischen vergeben
    loesung: bei der Einarbeitung umnummeriert – D-38 → D-54 (zunächst D-53; AP-12 hat D-53 parallel belegt und wurde zuerst gemergt), Q-09 → Q-13; übrige neue IDs (L-P14, L-T2-15 bis L-T2-19, L-T3-18) waren frei
geprueft:
  - was: Zuordnung der 29 PDFs zu IDs aus 13.2 – Titel, Autoren und DOI auf den ersten Seiten gegen 13.2 abgeglichen
    wie: Textextraktion (pypdf) aller Dateien, Abgleich je Datei
    ergebnis: ok; alle DOIs stimmen; Abweichung nur L-A01 (7. statt 8. Aufl.) → D-51
    datum: 2026-09-28
  - was: Kapitel-PDFs vollständig – Summe der Seiten je Buch = Seitenzahl des Originals (NSCA 1876, Kenney 1379, Overcoming Gravity 600, Concurrent 408, Uphill 380, Climbing Medicine 319)
    wie: automatisiert beim Erzeugen
    ergebnis: ok
    datum: 2026-09-28
  - was: Jede Kapiteldatei beginnt mit dem richtigen Kapitel (erster Teil je Kapitel, 152 Dateien); Text ist extrahierbar; Lesezeichen des Kapitels vorhanden (außer bei den Scans)
    wie: Textextraktion der ersten Seiten, Vergleich mit dem Kapiteltitel
    ergebnis: ok
    datum: 2026-09-28
  - was: Dateigröße – keine Datei über 100 MB (D-31); Kapitel-PDFs höchstens 11 MB
    wie: automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Deployment lädt docs/literatur nicht auf den Webspace
    wie: Durchsicht .github/workflows/deploy.yml (FTPS-Upload nur ./server/)
    ergebnis: ok
    datum: 2026-09-28
  - was: L-A02 – Gesamtbuch (94 MB) und 25 Kapitel-PDFs gegen das 355-MB-Original; Seitensumme 911, Text und Bild-Prüfsummen je Seite gleich, 21 Kapitel-Lesezeichen im Gesamtbuch
    wie: automatisiert (pypdf, Seitenvergleich über alle 911 Seiten)
    ergebnis: ok
    datum: 2026-09-28
  - was: Bibliografie und Kernaussage L-P11 (V-06)
    wie: PubMed-Connector (Metadaten, Abstract) – Literatur-Sitzung AP-06 Teil B
    ergebnis: ok
    datum: 2026-09-28
  - was: Bibliografie L-P10, L-P12, L-P14
    wie: PubMed-Connector
    ergebnis: ok
    datum: 2026-09-28
  - was: Bibliografie L-P13 und Modellherkunft (V-07)
    wie: PubMed-Connector
    ergebnis: ok (Bibliografie); offen (Schwellen)
    datum: 2026-09-28
  - was: Bibliografie L-T2-11, L-T2-12 (V-14)
    wie: PubMed-Connector; Lizenz über Copyright-Status
    ergebnis: ok
    datum: 2026-09-28
  - was: Bibliografie T3 (V-15)
    wie: PubMed-Connector (L-T3-02, -03); Verlagsseite und Bibliothekskataloge (L-T3-03 Lizenz, -04, -05, -07, -08, -09, -12)
    ergebnis: ok für -03, -04, -05, -07, -09; teilweise für -02, -08, -12
    datum: 2026-09-28
  - was: Literaturrecherche Haltung/Rücken (Vorkopfhaltung, Kyphose, Kreuzschmerz-Prävention) – Literatur-Sitzung AP-06 Teil A
    wie: PubMed-Connector (Suche nach systematischen Reviews/Metaanalysen, Metadaten, Abstracts)
    ergebnis: ok – 4 Kernquellen, 1 optionale Quelle, 4 dokumentierte Ausschlüsse
    datum: 2026-09-28
  - was: Bibliografie und Kernaussage L-T2-14 (Cowley 2026)
    wie: PubMed-Connector
    ergebnis: ok
    datum: 2026-09-28
  - was: Zugang/Lizenz L-T2-15 bis L-T2-19
    wie: PubMed-Copyright-Status
    ergebnis: teilweise – L-T2-15, -16 in PMC ohne ausgewiesene Lizenz; L-T2-17, -18, -19 nicht in PMC
    datum: 2026-09-28
  - was: Einarbeitung der Übergaben Teil A und B ins Konzept – ID-Kollisionen (D-38 → D-54, Q-09 → Q-13), Pfade datei/kapitel erhalten, YAML-Blöcke parsebar wie zuvor
    wie: Code-Instanz, automatisiert
    ergebnis: ok
    datum: 2026-09-28
  - was: Sechs neue PDFs (Commit „Docs“) zugeordnet – L-P10, L-P12, L-P13, L-T2-17, L-T2-18, L-T3-04; Titel, Autoren, Jahrgang/Seiten und DOI (L-P10 ohne DOI, Zuordnung über Titel, Heft und Seiten) gegen 13.2 abgeglichen
    wie: Textextraktion der ersten Seiten, Seitenzahl gegen Seitenangabe im Zitat
    ergebnis: ok
    datum: 2026-09-28
noch_zu_pruefen:
  - was: Stichprobe Kapitel-PDFs im Alltag – Upload in eine claude.ai-Sitzung (Größe, Lesbarkeit von Tabellen und Abbildungen), besonders E-Book-Kapitel von NSCA und Kenney
    wie: manuell durch Athlet bei der ersten Kartensitzung
  - was: Druckseiten der Scans (Uphill Athlete, Overcoming Gravity) an zwei, drei Stellen gegen das Seitenbild prüfen, bevor Seitenangaben in Karten übernommen werden
    wie: manuell in der Kartensitzung
  - was: Restliche Beschaffung laut 13.4 (L-T1-01, L-T1-07, L-T3-08, L-A01 8./9. Aufl.); neue Dateien nach D-51 ablegen und eintragen
    wie: Athlet (D-26), Eintrag durch Code-Instanz
  - was: Schwellenwerte Schmerzmonitoring-Modell am Volltext L-P13 (V-07), danach Entscheidung Q-13
    wie: manuell in der Kartensitzung, der Volltext liegt vor; Entscheidung in AP-07
  - was: Wiederholungsbereiche L-T3-02 am Volltext (V-15)
    wie: manuell in der Kartensitzung; der Volltext liegt bereits im Repo
  - was: L-T3-08 aktuelle Auflage/ISBN, L-T3-12 Auflage
    wie: beim Kauf (Verlagsshop)
  - was: L-T3-18 PubMed-Metadaten
    wie: PubMed-Connector
  - was: Lizenz L-T2-15, L-T2-16 (für Ablage im Repo, D-31)
    wie: Verlagsseite (BMC/Springer Open)
  - was: Artikelnummer L-T2-16, Band/Heft L-T2-19
    wie: bei Beschaffung
  - was: Kernaussagen L-T2-15 bis L-T2-18 am Volltext (Dosierungsdetails für Karten)
    wie: manuell nach Beschaffung, Kartenerstellung 13.1
```
