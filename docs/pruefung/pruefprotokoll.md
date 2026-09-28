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
noch_zu_pruefen:
  - was: CI-Job test (PHPUnit inkl. neuer Integrationstests gegen MySQL 8.4)
    wie: automatisiert (GitHub Actions im Pull Request)
  - was: Deployment von 0.2.0 – vorher OAUTH_JWT_SECRET in die .env auf dem Server eintragen; danach /health status ok, schema code 6 = db 6, var ok; .env und var/ überleben ein zweites Deployment
    wie: Merge auf main, /health im Browser, zweites Deployment (z. B. "Run workflow") und erneut /health
  - was: /setup legt den Benutzer an und ist danach gesperrt (404)
    wie: manuell durch Athlet im Browser (Smartphone), danach /setup erneut aufrufen
  - was: Login auf Smartphone und Tablet nutzbar; Session überlebt Neustart des Browsers
    wie: manuell durch Athlet (anmelden, Browser/App schließen, erneut öffnen)
  - was: ping aus dem Projekt-Chat (Web) nach Freigabe auf S7 (V-05)
    wie: manuell durch Athlet – claude.ai → Connector https://training.gen-em.org/mcp hinzufügen, verbinden, anmelden, freigeben; im Chat "ping aufrufen"
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
noch_zu_pruefen:
  - was: Intervals.icu-Konto – Garmin-Verknüpfung (Aktivitäten, Wellness, geplante Workouts hochladen) aktiv, Aktivitäten privat (Q-03)
    wie: manuell durch Athlet in Intervals.icu (Einstellungen)
  - was: INTERVALS_API_KEY und INTERVALS_ATHLETE_ID in der .env; /health zeigt intervals konfiguriert
    wie: manuell durch Athlet (FTP, Browser)
  - was: Aktivitäten und Wellness der letzten 7 Tage abrufbar (Abnahmekriterium, bestätigt V-04)
    wie: manuell – /intervals öffnen, Werte mit Intervals.icu vergleichen (HRV, Ruhepuls, Schlaf, Aktivitäten)
  - was: Test-Event erscheint auf der Uhr mit korrekten Zielen (V-01, V-12)
    wie: manuell – /intervals → „Test-Event anlegen“, Garmin Connect synchronisieren, auf der Uhr Training für morgen öffnen: 10 min Z1, 3 × (3 min Z3, 2 min Z1), 5 min Z1 als HF-Ziele; Zonengrenzen auf der Uhr mit Intervals.icu vergleichen
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
noch_zu_pruefen:
  - was: Migration auf dem Server 1 → 14, /health schema code 14 = db 14
    wie: Deployment nach Merge, /health im Browser
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
noch_zu_pruefen:
  - was: JSON-Export herunterladen und in einem Editor/Programm öffnen
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
noch_zu_pruefen:
  - was: BACKUP_PASSWORD in der .env (vor dem Deployment); Pre-Migration-Dump beim ersten Deployment in backups/
    wie: manuell (FTP, /health backups ok, Datei in backups/)
  - was: Heruntergeladene Datei auf einem anderen Rechner nur mit dem Passwort entschlüsselbar, Dump in leere DB einspielbar (Abnahmekriterium)
    wie: manuell durch Athlet – Einstellungen → Herunterladen, openssl enc -d … (README), gunzip, Import in eine leere Test-DB bei Lima-City
  - was: E-Mail mit Anhang kommt an (V-10 Anhang-Limit)
    wie: manuell – SMTP_* und BACKUP_* in .env, Aufruf /cron/backup-mail?key=…&force=1, Postfach prüfen; Cronjob bei Lima-City täglich einrichten
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
```
