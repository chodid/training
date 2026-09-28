# Changelog

Alle nennenswerten Änderungen werden hier dokumentiert. Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), Versionierung nach [SemVer](https://semver.org/lang/de/).

## [Unreleased]

## [0.5.0] – 2026-09-28

AP-04 Webseite.

### Hinzugefügt
- App-Rahmen nach Branding: Tab-Leiste (Smartphone), Leiste links (Tablet), Seitenleiste (Desktop); Kopfzeile mit Zurück-Pfeil auf Unterseiten.
- `/woche` (S2): 7 Tage mit Einheiten (Typ-Icon, Titel, Priorität, Dauer, Status bzw. „Feedback“), heutiger Tag hervorgehoben, Check-in-Status je Tag, ±Woche, Kennzahlen (sRPE, erledigte Einheiten, Check-in-Abdeckung), Hinweis auf offene Rückmeldungen, Leerzustand.
- `/einheit` (S3): Plan mit Soll je Übung bzw. Block, Ist-Eingabe (vorbelegt mit Soll oder letzter Eingabe), Rückmeldung (Dauer, RPE 0–10, Gefühl 1–5, Schmerz mit Kurzform, Abweichung, Notiz, Status); bei Ausdauer Plan-Text und verknüpfte Aktivität aus Intervals.icu (Dauer, Distanz, Ø HF, Ø Pace, Zeit in Zonen). Speichern in `session_execution` (sRPE berechnet), `session.status`, `pain_event`.
- `/checkin` (S4): Erholung, Muskelkater, Schmerz (Kurzform), Notiz; ein Eintrag pro Tag (überschreibbar), Liste der Woche.
- `/schmerz` (S5): Schmerzereignis mit optionaler Einheit der letzten 14 Tage; Hinweis bei wiederholter Meldung, Stärke über 5 oder Schmerz in Ruhe.
- `/einstellungen` (S8): Abmelden, Zeitzone, Passwort (beendet andere Sessions), Schemastand und Version, Intervals.icu-Status, aktive Claude-Freigaben mit Widerruf, statisches Token; Backup/Migration als Platzhalter bis AP-10.
- `/verlauf`: Platzhalter bis AP-09.
- Audit-Log für alle Schreibzugriffe der Webseite (`audit_log`, Hash statt Inhalt).
- Kurzcache für Intervals.icu-Aktivitäten (`ext_cache`, 5 min) und Zuordnung Aktivität ↔ Ausdauereinheit (`paired_event_id`, sonst gleicher Tag).
- Web-App-Manifest (`/manifest.webmanifest`, Name „Training“, Farben laut Branding) mit Icons 192/512 px und maskierbarem Icon aus dem Lama-Kopf.
- Tests: Seiten, Speichern, Validierung, Aktivitätsanzeige, Check-in, Schmerz, Einstellungen.

### Geändert
- `/` leitet nach dem Login auf `/woche` (vorher Übergangsseite).
- Status „verschoben“ ohne Icon (passt in die 7-Spalten-Woche).

### Dokumentation
- README (Endpunkte), Konzept (AP-04 `in_arbeit`, Befunde), Prüfprotokoll AP-04, Branding-Dokument Abschnitt 8 (Abweichungen).
- Konzept: Q-09 → D-38 (Refresh-Token 90 Tage), Q-10 → D-39 (`plan_json` für Mobilität wie Kraft, Ruhetag leer) bestätigt.

## [0.4.0] – 2026-09-27

AP-03 Datenmodell.

### Hinzugefügt
- Migrationen `0007`–`0014`: `training_block`, `training_week`, `session`, `session_execution` (mit berechneter Spalte `srpe_load`), `pain_event`, `checkin`, `audit_log`, `ext_cache`; Aufzählungen als `ENUM`, Wertebereiche als `CHECK`, Eindeutigkeit (Woche je Montag, eine Durchführung je Einheit, ein Check-in je Tag); `App::SCHEMA_VERSION` = 14.
- JSON-Schemata (Draft 2020-12) für `plan_json` und `actual_json` je Typ in `server/schemas/`; `Training\Plan\PlanValidator` (Bibliothek `opis/json-schema`).
- Beispielwoche mit allen Einheitentypen (`server/tests/fixtures/beispielwoche.json`); Tests für Validator, Constraints, berechnete Last und Löschverhalten.

### Geändert
- Datenbankverbindung im strikten SQL-Modus (`STRICT_ALL_TABLES`, `NO_ZERO_DATE` u. a.) und mit Zeitzone UTC, unabhängig von der Voreinstellung des Hosters.

### Dokumentation
- `docs/konzept/datenmodell.md` mit ER-Diagramm (Mermaid) aller Tabellen und Umsetzungsdetails; Konzept (AP-03, Abschnitt 7/7.1, Q-10), Prüfprotokoll AP-03, README.

## [0.3.0] – 2026-09-27

AP-02 Intervals.icu-Anbindung (Client fertig; Prüfung gegen die echte API und auf der Uhr steht aus).

### Hinzugefügt
- `Training\Intervals\IntervalsClient`: HTTP Basic (`API_KEY:<key>`), Athlet, Events lesen/anlegen/ändern/löschen, Aktivitäten und Wellness nach Zeitraum; eine Wiederholung bei HTTP 429/5xx (Retry-After, max. 5 s); Fehlermeldungen mit Hinweis, ohne Key. Transport austauschbar (curl im Betrieb, simuliert in Tests).
- `/intervals` (nur nach Login): Verbindungstest mit Athlet, Aktivitäten und Wellness der letzten 7 Tage, Events der nächsten 14 Tage; Test-Event (Laufeinheit mit HF-Zonen, `external_id` `training-app-test`) anlegen, ändern und löschen für die Abnahme auf der Uhr.
- `.env`: `INTERVALS_API_KEY`, `INTERVALS_ATHLETE_ID` (optional); `/health` meldet `intervals: konfiguriert | nicht_konfiguriert`, ohne Netzwerkaufruf und ohne Health rot zu machen.
- Tests: Client (Auth, Query, CRUD, Fehler, Retry, Eingaben) und Seite `/intervals` mit simuliertem Intervals.icu.

### Dokumentation
- README (Einrichtung Intervals.icu, Endpunkt `/intervals`), Konzept (AP-02 `in_arbeit`, Befunde, V-04 vorläufig), Prüfprotokoll AP-02.

## [0.2.0] – 2026-09-27

AP-01 MCP-Minimalserver mit OAuth (Code fertig; Abnahme auf dem Server und mit claude.ai steht aus).

### Hinzugefügt
- MCP-Endpunkt `/mcp` über `logiscape/mcp-sdk-php` (v2.0.x): Streamable HTTP ohne SSE, zustandslos für Clients der Revision 2026-07-28, Sitzungsdateien für ältere Revisionen in `var/mcp_sessions/` (außerhalb des Docroots, Dateien älter als ein Tag werden aufgeräumt). Dummy-Tool `ping` (Serverzeit, Code-Stand).
- Bearer-Prüfung am `/mcp` über den SDK-`JwtTokenValidator` (iss, aud, exp; zusätzlich `exp` Pflicht); 401 mit `WWW-Authenticate: Bearer resource_metadata=…` aus dem SDK; `/.well-known/oauth-protected-resource` (auch mit Suffix `/mcp`).
- Statisches Fallback-Token `MCP_STATIC_TOKEN`, nur aktiv mit `MCP_STATIC_TOKEN_ENABLED=true` (D-06).
- Eigener OAuth-2.1-Autorisierungsserver (D-32, D-36): `/.well-known/oauth-authorization-server` (RFC 8414, auch mit Suffix `/mcp`), `/oauth/register` (offene Dynamic Client Registration, nur `https://` bzw. `http://localhost`/`127.0.0.1`/`[::1]` als Redirect-URI), `/oauth/authorize` (Login + Freigabeseite S7, Ablehnen möglich), `/oauth/token` (PKCE S256 Pflicht, Codes 10 min einmalig; Access-Token als JWT HS256 1 h; Refresh-Token mit Rotation und Familien-Widerruf bei Wiederverwendung). Scopes `training:read` und `training:write`.
- Webseite: `/setup` (S0, einmalige Anlage des einzigen Benutzers mit `MIGRATION_SECRET`, danach 404), `/login` (S1, Passwort mit Argon2id, 30-Tage-Session gleitend, Kontosperre nach 10 Fehlversuchen für 5 min mit Verdopplung bis 24 h), `/logout`, Startseite nach Login. Gestaltung nach `docs/branding/` (Smartphone, Tablet, Desktop).
- Migrationen `0002`–`0006`: `user`, `web_session`, `oauth_client`, `oauth_auth_code`, `oauth_token` (D-35); `App::SCHEMA_VERSION` = 6.
- CSRF-Schutz: Double-Submit-Cookie für Setup und Login, Session-gebundenes Token für Abmelden und Freigabe.
- Sicherheitsheader für HTML-Seiten (Content-Security-Policy ohne Inline-Skripte/-Styles, `frame-ancestors 'none'`, `X-Frame-Options: DENY`, `no-store`); CORS für Metadaten, Registrierung, Token und `/mcp` (ohne Cookies).
- `server/bin/build-assets.php`: übernimmt Design-System, `app.css`, Icons und Logo aus `docs/branding/` nach `server/public/assets/` (Build-Schritt in CI und Deploy, nicht im Repo).
- `/health` prüft zusätzlich, ob `var/` beschreibbar ist.
- Tests: Unit-Tests (Sperrstufen, Redirect-Regeln, PKCE nach RFC-7636-Beispiel, JWT gegen SDK-Validator, statisches Token, Rücksprungziele) und Integrationstests für Setup, Login/Sperre/Session sowie den vollständigen OAuth- und MCP-Ablauf.

### Geändert
- Neuer Pflichtwert `OAUTH_JWT_SECRET` in `.env` (mindestens 32 Zeichen); fehlt er oder ist er zu kurz, meldet `/health` `config` als fehlend und die App startet nicht. **Vor dem Deployment in die `.env` auf dem Server eintragen.**
- Deploy-Workflow: `var/**` und `bin/**` vom Upload ausgeschlossen, Assets-Build in Test- und Deploy-Job, Syntaxprüfung auch für `templates/` und `bin/`.
- `Request` liest Query, Formularfelder, Cookies und Rohdaten; `Response` kann Cookies setzen, HTML ausliefern und weiterleiten.

### Dokumentation
- AP-01: README (Endpunkte, neue `.env`-Schlüssel, Ersteinrichtung, Connector in claude.ai und Claude Desktop), Konzept (Status AP-01, Befunde, offene Frage Q-09 Laufzeit Refresh-Token), Prüfprotokoll AP-01, Branding-Dokument Abschnitt 8 (Abweichungen von den Mockups).
- AP-01a: Design-Mockups aller Webseiten-Screens (S0 Setup, S1 Login mit Fehler/gesperrt, S2 Woche inkl. leer, S3 Einheit für Kraft/Ausdauer/Klettern, S4 Check-in, S5 Schmerz, S6 Verlauf, S7 Freigabe, Einstellungen inkl. „Update erforderlich“) als HTML unter `docs/branding/mockups/` mit Übersicht `index.html`, Bausteinen `app.css`, lokalem Icon-Sprite (Tabler, MIT) und Screenshots; Branding-Dokument `docs/branding/branding.md` (Vorgaben, Bausteine, Layout, Entscheidungen B-01–B-07, Umsetzungshinweise). Vom Athleten abgenommen; Konzept D-19 (Desktop-Ansicht) und Abschnitt 10 (S8 Einstellungen) ergänzt, AP-01a `erledigt`.
- AP-01a begonnen: Gestaltungsvorgaben als Chadid Design-System unter `docs/branding/chadid-design-system/` (Farb-, Schrift- und Abstands-Tokens, Richtlinien-Karten, Lama-Logo, Briefvorlage, `SKILL.md`). Schriften (Young Serif, Source Sans 3, Source Code Pro, SIL OFL) lokal in `fonts/` statt über Google Fonts. Grundlage für die Mockups (Fable).
- AP-00 abgenommen: Prüfprotokoll mit Servertests ergänzt, Konzept-Status `erledigt`, Hinweis auf vorgeschalteten Lima-City-Proxy.
- Konzept: Entscheidungen zu AP-01 (D-32 bis D-37: JWT-Access-Token mit Refresh-Rotation, Passwort-Login mit 30-Tage-Session, Setup-Seite, Auth-Tabellen in AP-01, offene Client-Registrierung mit Freigabeseite, Design-Mockups als AP-01a); D-04 präzisiert (SDK ohne Autorisierungsserver); Rollenverteilung Fable/Code-Instanz.
- `CLAUDE.md` mit Arbeitsweise (Rückfragen mit Auswahl und Empfehlung, Rollenverteilung).

## [0.1.1] – 2026-09-27

### Behoben
- Deploy-Workflow lehnte `FTP_SERVER_DIR=/` ab. Bei Lima-City ist der FTP-Benutzer auf den Subdomain-Ordner beschränkt, `/` ist dort das richtige Ziel. Die Prüfung wurde entfernt; der Upload löscht ohnehin nur Dateien, die er selbst hochgeladen hat.

## [0.1.0] – 2026-09-27

AP-00 Grundgerüst und Deployment.

### Hinzugefügt
- PHP-Grundgerüst in `server/` (PHP 8.4, Composer, Namespace `Training\`): Front-Controller `public/index.php`, Routing, JSON-Antworten, Sicherheitsheader (HSTS bei HTTPS, `nosniff`).
- Konfiguration aus `.env` im Subdomain-Ordner außerhalb des Docroots; Vorlage `server/.env.example`; fehlende Pflichtwerte werden ohne Werte gemeldet.
- `.htaccess` in `public/` (HTTPS-Weiterleitung, Routing, Durchreichen des `Authorization`-Headers) und im Subdomain-Ordner (vollständige Sperre).
- `GET /health`: PHP-Erweiterungen, Konfiguration, Datenbank, Schemastand Code vs. Datenbank.
- Migrationsgerüst nach D-20: `server/migrations/`, Tabelle `schema_version` (Migration `0001`), `App::SCHEMA_VERSION`, Prüfung auf lückenlose Nummerierung, Sperre gegen parallele Läufe, Fortschreiben nach jeder Migration.
- `POST /admin/migrate`, geschützt über Header `X-Migration-Secret`.
- GitHub-Actions-Workflow `deploy.yml`: Tests (PHPUnit, Migrationen gegen MySQL 8.4), Build ohne Dev-Abhängigkeiten, FTPS-Upload von `server/` ohne `.env` und `backups/`, Migration und Health-Check. `FTP_SERVER` wird als Variable oder Secret akzeptiert.
- Tests: Unit-Tests (Konfiguration, SQL-Zerlegung, Migrationsdateien, Routing, Authentifizierung) und Integrationstests (Migrationen, Abbruchverhalten, Sperre).
- `.gitignore` schließt SQL-Dumps aus, aber nicht die Migrationen in `server/migrations/`; fehlender Migrationsordner führt zu einem Fehler statt zu Schemastand 0.
- README: Server-Layout, Endpunkte, Einrichtung Lima-City und GitHub-Environment, Migrationsregeln.

### Geändert
- Konzept: Hosting Lima-City statt Plesk; Layout in D-17 festgeschrieben; D-20 präzisiert (MySQL-DDL ohne Transaktion); 12.1a (`open_basedir`); V-08 erledigt, V-10 bis auf Anhang-Limit erledigt; Q-04 entschieden; D-31 erweitert (gekaufte PDFs im Repo erlaubt).

### Vorbereitung (ohne Version)
- Repository-Grundstruktur, Konzeptdokument, Prüfprotokoll.
