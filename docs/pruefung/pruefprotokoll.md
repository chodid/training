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
