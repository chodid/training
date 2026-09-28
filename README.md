# KI-Personal-Trainer

Privates Repo (D-23) für das System „KI-Personal-Trainer": PHP-Server (Webseite, MySQL, MCP-Endpunkt, OAuth) plus Dokumente (Konzept, Wissenskarten, Trainerregeln, Athletenprofil, Blockpläne).

Maßgeblich ist das Konzept: [`docs/konzept/konzept-ki-personal-trainer.md`](docs/konzept/konzept-ki-personal-trainer.md). Änderungen stehen im [`CHANGELOG.md`](CHANGELOG.md), der Prüfstand im [Prüfprotokoll](docs/pruefung/pruefprotokoll.md).

## Struktur

| Pfad | Inhalt | Arbeitspaket |
|---|---|---|
| `server/public/` | Document Root (einziger per HTTP erreichbarer Ordner), `index.php` als einziger Einstieg; `css/training.css` (Ergänzungen), `manifest.webmanifest`, `favicon.ico` und `icons/` (App-Icon, eingecheckt, siehe „Icons“); `assets/` wird gebaut (siehe unten) | AP-00, AP-01, AP-04, AP-13 |
| `server/src/` | PHP-Quellcode (Namespace `Training\`): `Auth/` Login und Session, `OAuth/` Autorisierungsserver, `Mcp/` MCP-Endpunkt, `Intervals/` Intervals.icu-Client, `Data/` Datenzugriff und Audit-Log, `Plan/` Plan-Validierung und Ablaufplan der geführten Einheit, `View/` Seiten | AP-00 ff. |
| `server/templates/` | Seitenvorlagen nach `docs/branding/` (S0, S1, S7 aus AP-01; S2–S5, S8 aus AP-04; S9 `session-start.php` aus AP-14); Teilvorlagen beginnen mit `_` | AP-01, AP-04, AP-14 |
| `server/bin/build-assets.php` | Kopiert Design-System, `app.css`, Icons und Logo aus `docs/branding/` nach `server/public/assets/` | AP-01 |
| `server/config/` | Konfiguration ohne Secrets (derzeit leer) | – |
| `server/migrations/` | Nummerierte Migrationen (D-20) | AP-00, AP-01, AP-03 |
| `server/schemas/` | JSON-Schemata für `plan_json`/`actual_json` je Einheitentyp (Konzept 7.1) | AP-03 |
| `server/tests/` | PHPUnit-Tests (Unit und Integration gegen MySQL); `js/` Node-Tests und `e2e/` Browser-Durchlauf der geführten Einheit | AP-00 ff., AP-14 |
| `.github/workflows/deploy.yml` | Test und Deployment (D-17) | AP-00 |
| `docs/konzept/` | Konzeptdokument; `datenmodell.md` mit ER-Diagramm | – , AP-03 |
| `docs/pruefung/` | Prüfprotokoll (Konzept Abschnitt 16) | alle |
| `docs/wissen/` | Wissenskarten (Sammeldateien, 13.1) | AP-06 |
| `docs/literatur/` | Literatur-Volltexte als PDF, Open Access und gekauft (D-31), je Block in Unterordnern, Bücher zusätzlich als Kapitel-PDFs (D-51); Verzeichnis `README.md`; nie ins Projektwissen | AP-06 |
| `docs/regeln/` | Trainerregeln (Abschnitt 14) | AP-07 |
| `docs/plaene/` | Blockpläne | AP-08 |
| `docs/branding/` | Branding-Dokument `branding.md` (D-19), Gestaltungsvorgaben in `chadid-design-system/` (Einstieg `readme.md`, `SKILL.md`), Mockups in `mockups/` (Einstieg `index.html`, Screenshots mit `mockups/screenshots.cjs`), Icon-Skript `build-icons.cjs` | AP-01a, AP-13 |

## Server-Layout (Lima-City, D-17)

```
/training.jennym.org/          ← FTP-Zielordner = Inhalt von server/
├── .env                       ← Konfiguration, nur auf dem Server, vom Deployment nie berührt
├── .htaccess                  ← sperrt den Ordner, falls der Document Root falsch gesetzt ist
├── backups/                   ← Datenbank-Backups (ab AP-10), vom Deployment nie berührt
├── var/                       ← Laufzeitdaten (MCP-Sitzungsdateien), vom Deployment nie berührt
├── public/                    ← Document Root der Subdomain training.gen-em.org (inkl. assets/)
├── src/  templates/  migrations/  vendor/
└── .ftp-deploy-sync-state.json  ← Statusdatei des Upload-Schritts
```

## Endpunkte (Stand AP-13)

| Methode | Pfad | Zweck |
|---|---|---|
| GET | `/` | Weiterleitung auf `/woche`; ohne Anmeldung auf `/login` (bzw. `/setup`, solange kein Benutzer existiert) |
| GET | `/woche` | S2 Wochenansicht (`?start=YYYY-MM-DD` für eine andere Woche); Kurzsatz der Woche mit „mehr“ |
| GET/POST | `/einheit` | S3 Einheit (`?id=…`): Kurzsatz mit „mehr“, Plan, Ist-Werte, Rückmeldung, Schmerz, Status; bei Ausdauer verknüpfte Intervals.icu-Aktivität |
| GET | `/einheit?id=…&modus=start` | S9 Einheit geführt (Kraft, Haltung, Mobilität, Klettern): dieselbe Rückmeldung schrittweise je Übung mit Timer, Tonsignalen und Vibration (Skript `js/gefuehrt.js`, Fortschritt im Browser), gespeichert über `POST /einheit`; für Ausdauer zeigt die Adresse S3 |
| GET/POST | `/checkin` | S4 Tages-Check-in mit Morgentest (`?datum=…`, nicht in der Zukunft); Formular bzw. Ampel auch oben in `/woche` |
| GET/POST | `/schmerz` | S5 Schmerzereignis (`?datum=…`, `?einheit=…`) |
| GET/POST | `/einstellungen` | S8 Athletenprofil (Link), Konto, Zeitzone, Passwort, Passkeys, Training (Timer-Signale der geführten Einheit an/aus), Kalender-Abgleich und -Erinnerung (`?bereich=erinnerung`), Morgen-Check-in „Hand rechts bis“ (`?bereich=checkin`), Backup herunterladen, JSON-Export, Status Backup-Mail und Pre-Migration-Dumps, Schemastand und Migration, Verbindungen, Widerruf von Claude-Freigaben |
| POST | `/passkey/register/options`, `/passkey/register` | Passkey anlegen (angemeldet, Header `X-CSRF-Token`; D-44) |
| POST | `/passkey/login/options`, `/passkey/login` | Anmelden mit Passkey; Relying-Party-ID ist der Host aus `APP_URL` |
| GET/POST | `/profil` | Athletenprofil (D-48): Abschnitte lesen und bearbeiten (`?abschnitt=…`), frühere Fassungen (`&verlauf=1`) |
| GET | `/offline/token` | Frisches CSRF-Token für offline gepufferte Eingaben (nur für den Service Worker, D-45) |
| GET | `/verlauf` | S6 Verlauf: Wochenlast je Bereich und Schmerz je Ort über 8 Wochen, Tabelle |
| GET | `/manifest.webmanifest` | Web-App-Manifest („Zum Startbildschirm“, `id` `/woche`); Icons unter `/icons/`, `/favicon.ico` (16/32/48) – statische Dateien, von Apache direkt ausgeliefert |
| GET | `/health` | Zustand als JSON: PHP-Erweiterungen, Konfiguration, `var/` beschreibbar, Datenbank, Schemastand. `200` = in Ordnung, `503` = Handlungsbedarf. Enthält keine Secrets. |
| POST | `/admin/migrate` | Führt ausstehende Migrationen aus, vorher verschlüsselter Pre-Migration-Dump nach `backups/` (die letzten 5 bleiben). Header `X-Migration-Secret` muss `MIGRATION_SECRET` entsprechen. `401` ohne Header, `403` bei falschem Secret, `409` wenn bereits eine Migration läuft oder die Datenbank neuer als der Code ist, `500` wenn der Dump fehlschlägt (dann keine Migration). |
| GET | `/cron/backup-mail?key=…` | Backup per E-Mail für den Lima-City-Cronjob (`CRON_SECRET`); versendet nur nach Ablauf des Intervalls, `&force=1` sofort |
| GET | `/cron/intervals-sync?key=…` | Spiegel Intervals.icu → MySQL (D-43): Aktivitäten und Wellness der letzten 14 Tage (`&tage=…` bis 400), entfernt dort gelöschte Aktivitäten; gleicht außerdem den CalDAV-Kalender ab (AP-11) |
| GET/POST | `/setup` | S0: legt den einzigen Benutzer an (verlangt `MIGRATION_SECRET`, D-34). Sobald ein Benutzer existiert: `404`. |
| GET/POST | `/login` | S1: Anmeldung, Session 30 Tage gleitend. Nach 10 Fehlversuchen 5 min Sperre, jeder weitere Fehlversuch verdoppelt bis 24 h (D-33). |
| POST | `/logout` | Abmelden (mit CSRF-Token) |
| GET/POST | `/intervals` | Verbindungstest Intervals.icu (nur nach Login): Athlet, Aktivitäten/Wellness 7 Tage, Events 14 Tage; Test-Event anlegen, ändern, löschen |
| GET | `/.well-known/oauth-authorization-server` | OAuth-Metadaten (RFC 8414); auch unter `…/mcp` |
| GET | `/.well-known/oauth-protected-resource` | Resource-Metadaten aus dem SDK; auch unter `…/mcp` |
| POST | `/oauth/register` | Offene Client-Registrierung (RFC 7591); Redirect-URIs nur `https://` oder `http://localhost` |
| GET/POST | `/oauth/authorize` | Login + Freigabeseite S7; PKCE `S256` Pflicht |
| POST | `/oauth/token` | Code-Einlösung und Refresh (Rotation, Familien-Widerruf) |
| POST | `/mcp` | MCP (Streamable HTTP, ohne SSE). Bearer-Token Pflicht: JWT aus `/oauth/token` oder – nur mit `MCP_STATIC_TOKEN_ENABLED=true` – `MCP_STATIC_TOKEN`. Tools siehe unten. |

### MCP-Tools (Konzept 8.2)

| Tool | Scope | Zweck |
|---|---|---|
| `ping` | – | Verbindungstest |
| `get_week_overview` | `training:read` | Woche aggregiert: Kurzsatz (`fokus`) und Begründung der Woche, je Einheit Kurzsatz (`kurz`), Plan vs. Ist, sRPE, Compliance, Aktivitäten, Schmerz, Check-in, Form |
| `get_session_detail` | `training:read` | Einheit mit `plan_json`, `actual_json`, Kurzsatz (`coach_summary`) und Begründung (`coach_rationale`), Rückmeldung, Schmerz, Aktivität |
| `get_pain_history` | `training:read` | Schmerz je Ort mit Trend (Standard 56 Tage) |
| `get_wellness_trend` | `training:read` | HRV, Ruhepuls, Schlaf, Check-in; Baseline 7/28 Tage |
| `get_block` | `training:read` | aktueller Block mit Wochenstatus |
| `get_athlete_profile` | `training:read` | Athletenprofil aus der Datenbank (D-48) je Abschnitt; optional ein Abschnitt, früherer Stand (`as_of`), Fassungen (`include_history`) |
| `upsert_block` | `training:write` | Block anlegen/ändern (Voraussetzung für Wochenpläne) |
| `write_week_plan` | `training:write` | Wochenplan schreiben, Ausdauer als Workout nach Intervals.icu; Pflicht: `focus` (Kurzsatz der Woche) und je Einheit außer Ruhetag `coach_summary` (≤ 200 Zeichen), dazu optional `coach_notes`/`coach_rationale` (≤ 1 500 Zeichen, D-56) |
| `update_session` | `training:write` | Einheit ändern (auch Kurzsatz und Begründung), Event nachziehen |
| `get_morning_checks` | `training:read` | Morgen-Check-ins: Ampel mit Grund, Morgentest links/rechts, Wochenausgangswert, Warnzeichen/Abklärung, je Tag alle Werte (Standard 14 Tage) |
| `update_athlete_profile` | `training:write` | Profilabschnitt ersetzen (neue Fassung, frühere bleiben erhalten) |

Schreib-Tools sind bei „Update erforderlich“ gesperrt; alle Schreibzugriffe stehen im `audit_log`.

## Einrichtung

### 1. Lima-City

1. Subdomain `training.gen-em.org` auf das Verzeichnis `/training.jennym.org/public` zeigen lassen, HTTPS-Zertifikat aktivieren.
2. PHP-Version 8.4 wählen.
3. MySQL-Datenbank mit eigenem Benutzer anlegen.
4. Per FTP die Datei `/training.jennym.org/.env` anlegen, Vorlage: [`server/.env.example`](server/.env.example). `MIGRATION_SECRET` und `OAUTH_JWT_SECRET` jeweils z. B. mit `openssl rand -hex 32` erzeugen (mindestens 32 Zeichen, zwei verschiedene Werte), `BACKUP_PASSWORD` mit mindestens 16 Zeichen (z. B. `openssl rand -base64 24`) und zusätzlich sicher außerhalb des Servers aufbewahren. **Ab Version 0.2.0 ist `OAUTH_JWT_SECRET` Pflicht, ab 0.6.0 auch `BACKUP_PASSWORD`** – beide vor dem Deployment eintragen, sonst schlagen Migration und Health-Check fehl.

### 2. GitHub

Settings → Environments → `production` anlegen und befüllen:

| Art | Name | Wert |
|---|---|---|
| Secret | `FTP_USERNAME` | FTP-Benutzer |
| Secret | `FTP_PASSWORD` | FTP-Passwort |
| Secret | `MIGRATION_SECRET` | identisch mit dem Wert in `.env` |
| Variable | `FTP_SERVER` | FTP-Hostname (muss zum FTPS-Zertifikat passen); alternativ als Secret |
| Variable | `FTP_PORT` | `21` (optional, Standard 21) |
| Variable | `FTP_SERVER_DIR` | Zielordner relativ zum FTP-Login: `/`, wenn der FTP-Benutzer direkt im Subdomain-Ordner landet (aktuelle Einrichtung); sonst `/training.jennym.org/` |
| Variable | `APP_URL` | `https://training.gen-em.org` |

Default Branch: `main`.

### 3. Deployment

Jeder Push auf `main` führt den Workflow „Test und Deploy" aus: Tests (inkl. Migrationen gegen MySQL 8.4) → Build mit `composer install --no-dev` → FTPS-Upload von `server/` → `POST /admin/migrate` → `GET /health`. Schlägt ein Schritt fehl, ist der Workflow rot. Manuell auslösbar über „Run workflow".

Pull Requests durchlaufen nur die Tests.

### 4. Benutzer anlegen (einmalig)

`https://training.gen-em.org/setup` öffnen, `MIGRATION_SECRET` aus der `.env`, Anmeldename, Passwort (mindestens 12 Zeichen) und Zeitzone eintragen. Danach ist `/setup` dauerhaft gesperrt. Zurücksetzen nur über die Datenbank (Tabelle `user` leeren).

Optional unter Einstellungen → Konto → „Passkey hinzufügen“ einen Passkey je Gerät anlegen (Fingerabdruck, Gesicht oder Geräte-PIN). Das Passwort bleibt gültig und ist der Rückfallweg, wenn das Gerät verloren geht. Passkeys sind an den Host aus `APP_URL` gebunden; ändert sich die Adresse, müssen sie neu angelegt werden.

### 5. Claude verbinden

- **claude.ai (Web und Mobile-App):** Einstellungen → Connectors → Custom Connector hinzufügen, URL `https://training.gen-em.org/mcp`, keine Client-ID/Secret eintragen (Claude registriert sich selbst). Beim Verbinden öffnet sich die Anmeldung, danach die Freigabeseite: „Freigeben“ wählen. Der Connector steht dann auch in der Mobile-App zur Verfügung.
- **Claude Desktop / Claude Code (Fallback, D-06):** in der `.env` `MCP_STATIC_TOKEN` (z. B. `openssl rand -hex 32`) und `MCP_STATIC_TOKEN_ENABLED=true` setzen; im Client den Server `https://training.gen-em.org/mcp` mit Header `Authorization: Bearer <MCP_STATIC_TOKEN>` eintragen. Nach dem Test `MCP_STATIC_TOKEN_ENABLED` wieder auf `false` setzen.
- **Intervals.icu (AP-02):** In Intervals.icu Garmin verbinden (Aktivitäten, Wellness, „Upload planned workouts“), Aktivitäten auf privat stellen (Q-03). Unter Einstellungen → Developer Settings API-Key erzeugen und Athleten-ID (z. B. `i12345`) ablesen; beide als `INTERVALS_API_KEY` und `INTERVALS_ATHLETE_ID` in die `.env`. Danach `https://training.gen-em.org/intervals` öffnen: zeigt Aktivitäten und Wellness der letzten 7 Tage und legt auf Knopfdruck ein Test-Event für morgen an.
- **Cronjobs bei Lima-City:** `CRON_SECRET` (mindestens 32 Zeichen) in die `.env`, dann zwei zeitgesteuerte URL-Aufrufe anlegen:
  - täglich `https://training.gen-em.org/cron/backup-mail?key=<CRON_SECRET>` – Backup per E-Mail (zusätzlich `BACKUP_MAIL_TO` und `SMTP_*` des Mailkontos). Stand unter Einstellungen → Backup; Fehler erscheinen auch in der Wochenansicht.
  - stündlich `https://training.gen-em.org/cron/intervals-sync?key=<CRON_SECRET>` – Spiegel Intervals.icu → MySQL (D-43). Einmalig `…&tage=365` im Browser aufrufen, um die Vorgeschichte zu übernehmen. Stand unter Einstellungen → Verbindungen.
- **Kalender (Nextcloud, AP-11):** In Nextcloud einen Kalender „Training“ anlegen und unter Einstellungen → Sicherheit ein App-Passwort erzeugen. In die `.env`: `CALDAV_URL=https://<nextcloud>/remote.php/dav/calendars/<benutzer>/training/`, `CALDAV_USER=<benutzer>`, `CALDAV_PASSWORD=<App-Passwort>`. Danach in den Einstellungen unter „Verbindungen → Kalender (CalDAV)“ auf „Abgleichen“ tippen. Einheiten erscheinen als ganztägige Termine und werden bei jeder Änderung und stündlich (Cronjob `intervals-sync`) aktualisiert; Änderungen im Kalender selbst werden überschrieben. Geplante Einheiten erinnern am Tag um 05:00 Uhr; Uhrzeit oder „keine Erinnerung“ unter „Erinnerung im Kalender“.
- **Offline nutzen (D-45, D-49):** Die Seite auf dem Smartphone „Zum Home-Bildschirm“ hinzufügen und die Woche einmal mit Netz öffnen – dann sind aktuelle und nächste Woche mit allen Einheiten sowie Check-in und Schmerz für heute auch im Funkloch verfügbar, die geführte Einheit für heute und morgen ebenfalls. Eingaben ohne Netz werden auf dem Gerät gepuffert („Offline gespeichert“) und automatisch gesendet, sobald Netz da ist; auf dem iPhone beim nächsten Öffnen der App. Wurde ein Eintrag inzwischen anders geändert, erscheint ein Hinweis mit „Öffnen“, „Trotzdem übernehmen“ und „Verwerfen“. Abmelden löscht die gespeicherten Seiten, nicht aber ungesendete Eingaben.
- **Notbremse:** `OAUTH_JWT_SECRET` wechseln macht alle Access-Tokens sofort ungültig; Refresh-Tokens lassen sich in der Tabelle `oauth_token` (`revoked = 1`) sperren.

## Entwicklung

```bash
cd server
composer install
vendor/bin/phpunit                      # Unit-Tests; Integrationstests werden ohne Datenbank übersprungen
TEST_DB_HOST=127.0.0.1 TEST_DB_NAME=training_test TEST_DB_USER=… TEST_DB_PASSWORD=… vendor/bin/phpunit
```

Achtung: Die Integrationstests löschen alle Tabellen der Testdatenbank.

Geführte Einheit (AP-14): Kern des Seitenskripts `public/js/gefuehrt.js` ohne Browser mit `node --test tests/js/*.test.cjs`; Browser-Durchlauf mit Playwright und gesteuerter Uhr mit `bash tests/e2e/run.sh` (startet die App auf Port 8089 mit eigener `.env` gegen die Testdatenbank aus `TEST_DB_*`; Playwright lokal, global oder per `npm install --no-save --prefix tests/e2e playwright`, anderer Browser über `CHROME_PATH`). Beides läuft auch in der CI.

Lokal starten (ohne `.htaccess`): `.env` in `server/` anlegen (für `http://` ist `APP_URL=http://localhost:8080` möglich, dann ohne `Secure`-Cookies), Assets bauen mit `php bin/build-assets.php`, dann `php -S 127.0.0.1:8080 -t public bin/dev-router.php` (liefert vorhandene Dateien aus `public/` direkt aus). Datenbank einmalig mit `curl -X POST -H "X-Migration-Secret: …" http://127.0.0.1:8080/admin/migrate` migrieren.

### Assets (Branding)

Einzige Quelle der Gestaltung ist `docs/branding/`. `php server/bin/build-assets.php` kopiert Design-System (`styles.css`, `tokens/`, `fonts/`), `mockups/app.css`, die Tabler-Icons und das Lama (`lama-symbol-flaeche.svg` als `assets/lama.svg`, App-Kennung) nach `server/public/assets/`. Der Ordner ist nicht im Repo; CI und Deploy-Workflow bauen ihn. Eigene Ergänzungen stehen in `server/public/css/training.css` (keine Inline-Styles wegen Content-Security-Policy).

### Icons (App-Icon und Favicon, D-59)

Der Icon-Satz ist eingecheckt, weil der Server kein SVG rendern kann. Nach einer Änderung an den Vorlagen `docs/branding/mockups/icon-optionen/v3.svg`, `v3-maskable.svg` (App-Icon, V3) oder `v2.svg` (Favicon, V2) neu erzeugen:

```bash
node docs/branding/build-icons.cjs      # braucht Playwright mit Chromium (lokal oder global)
```

Ergebnis: `server/public/icons/lama-48|96|192|512.png`, `lama-512-maskable.png`, `apple-touch-icon-180.png`, `favicon.svg` und `server/public/favicon.ico`. Ein neues Motiv bekommt neue Dateinamen (Icons liegen 7 Tage im Browser-Cache); Manifest, `templates/_head_icons.php` und `AppIconTest` dann mitziehen.

### Migrationen (D-20)

- Dateiname `NNNN_beschreibung.sql` oder `NNNN_beschreibung.php`, fortlaufend ohne Lücke ab `0001`.
- `App::SCHEMA_VERSION` in `server/src/App.php` muss der höchsten Nummer entsprechen (Test prüft das).
- Ein fachlicher Schritt pro Datei. MySQL beendet Transaktionen bei `CREATE`/`ALTER` implizit; `schema_version` wird nach jeder einzelnen Migration fortgeschrieben.
- SQL-Dateien: Anweisungen mit `;` trennen; kein `DELIMITER` (dafür PHP-Migration: `return function (PDO $pdo): void { … };`).
- Kein Rollback: Rückweg = vorherigen Git-Stand deployen und Pre-Migration-Dump einspielen (ab AP-10).

## Wiederherstellung

Backups (Download, E-Mail, `backups/*-vor-migration.sql.gz.enc`) sind gzip-komprimierte SQL-Dumps, verschlüsselt mit `BACKUP_PASSWORD` (AES-256-CBC, PBKDF2-SHA256 mit 200 000 Iterationen, OpenSSL-Format). Sessions, OAuth-Tokens und der Intervals-Cache sind nur als Struktur enthalten: nach einem Restore neu anmelden und den Connector in Claude neu freigeben.

1. Entschlüsseln (auf jedem Rechner mit OpenSSL ≥ 1.1.1; unter Windows z. B. über Git Bash):
   ```bash
   openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -md sha256 -in training-backup-….sql.gz.enc -out backup.sql.gz
   ```
   Falsches Passwort → „bad decrypt“. Eine beschädigte Datei fällt spätestens im nächsten Schritt auf (gzip-Prüfsumme).
2. Entpacken: `gunzip backup.sql.gz` → `backup.sql`.
3. Einspielen in die (leere oder zu ersetzende) Datenbank: phpMyAdmin bei Lima-City → Datenbank wählen → Importieren → `backup.sql`; oder `mysql -h … -u … -p datenbank < backup.sql`. Der Dump löscht und erstellt jede Tabelle neu (`DROP TABLE IF EXISTS`).
4. `/health` prüfen: `schema` muss `aktuell` sein.

**Rückweg nach einem fehlgeschlagenen Update** (D-20, kein automatisches Rollback):
1. Den vorherigen Stand deployen: auf GitHub den letzten funktionierenden Commit (oder Tag) nach `main` zurückholen (Revert-PR) – das Deployment läuft automatisch.
2. Den passenden Pre-Migration-Dump aus `backups/` (per FTP) wie oben entschlüsseln und einspielen; der Schemastand im Dateinamen (`-s<N>-`) muss zum Code passen.
3. `/health` prüfen.
