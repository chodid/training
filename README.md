# KI-Personal-Trainer

Privates Repo (D-23) für das System „KI-Personal-Trainer": PHP-Server (Webseite, MySQL, MCP-Endpunkt, OAuth) plus Dokumente (Konzept, Wissenskarten, Trainerregeln, Athletenprofil, Blockpläne).

Maßgeblich ist das Konzept: [`docs/konzept/konzept-ki-personal-trainer.md`](docs/konzept/konzept-ki-personal-trainer.md). Änderungen stehen im [`CHANGELOG.md`](CHANGELOG.md), der Prüfstand im [Prüfprotokoll](docs/pruefung/pruefprotokoll.md).

## Struktur

| Pfad | Inhalt | Arbeitspaket |
|---|---|---|
| `server/public/` | Document Root (einziger per HTTP erreichbarer Ordner), `index.php` als einziger Einstieg; `css/training.css` (Ergänzungen); `assets/` wird gebaut (siehe unten) | AP-00, AP-01 |
| `server/src/` | PHP-Quellcode (Namespace `Training\`): `Auth/` Login und Session, `OAuth/` Autorisierungsserver, `Mcp/` MCP-Endpunkt, `View/` Seiten | AP-00 ff. |
| `server/templates/` | Seitenvorlagen (S0 Setup, S1 Login, S7 Freigabe) nach `docs/branding/` | AP-01 |
| `server/bin/build-assets.php` | Kopiert Design-System, `app.css`, Icons und Logo aus `docs/branding/` nach `server/public/assets/` | AP-01 |
| `server/config/` | Konfiguration ohne Secrets (derzeit leer) | – |
| `server/migrations/` | Nummerierte Migrationen (D-20) | AP-00, AP-01, AP-03 |
| `server/tests/` | PHPUnit-Tests (Unit und Integration gegen MySQL) | AP-00 ff. |
| `.github/workflows/deploy.yml` | Test und Deployment (D-17) | AP-00 |
| `docs/konzept/` | Konzeptdokument | – |
| `docs/pruefung/` | Prüfprotokoll (Konzept Abschnitt 16) | alle |
| `docs/wissen/` | Wissenskarten (Sammeldateien, 13.1) | AP-06 |
| `docs/literatur/` | Literatur-Volltexte als PDF, Open Access und gekauft (D-31); nie ins Projektwissen | AP-06 |
| `docs/regeln/` | Trainerregeln (Abschnitt 14) | AP-07 |
| `docs/athlet/` | Athletenprofil (D-15) | AP-08 |
| `docs/plaene/` | Blockpläne | AP-08 |
| `docs/branding/` | Branding-Dokument `branding.md` (D-19), Gestaltungsvorgaben in `chadid-design-system/` (Einstieg `readme.md`, `SKILL.md`), Mockups in `mockups/` (Einstieg `index.html`) | AP-01a |

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

## Endpunkte (Stand AP-01)

| Methode | Pfad | Zweck |
|---|---|---|
| GET | `/` | Startseite; ohne Anmeldung Weiterleitung auf `/login` (bzw. `/setup`, solange kein Benutzer existiert) |
| GET | `/health` | Zustand als JSON: PHP-Erweiterungen, Konfiguration, `var/` beschreibbar, Datenbank, Schemastand. `200` = in Ordnung, `503` = Handlungsbedarf. Enthält keine Secrets. |
| POST | `/admin/migrate` | Führt ausstehende Migrationen aus. Header `X-Migration-Secret` muss `MIGRATION_SECRET` entsprechen. `401` ohne Header, `403` bei falschem Secret, `409` wenn bereits eine Migration läuft oder die Datenbank neuer als der Code ist. |
| GET/POST | `/setup` | S0: legt den einzigen Benutzer an (verlangt `MIGRATION_SECRET`, D-34). Sobald ein Benutzer existiert: `404`. |
| GET/POST | `/login` | S1: Anmeldung, Session 30 Tage gleitend. Nach 10 Fehlversuchen 5 min Sperre, jeder weitere Fehlversuch verdoppelt bis 24 h (D-33). |
| POST | `/logout` | Abmelden (mit CSRF-Token) |
| GET | `/.well-known/oauth-authorization-server` | OAuth-Metadaten (RFC 8414); auch unter `…/mcp` |
| GET | `/.well-known/oauth-protected-resource` | Resource-Metadaten aus dem SDK; auch unter `…/mcp` |
| POST | `/oauth/register` | Offene Client-Registrierung (RFC 7591); Redirect-URIs nur `https://` oder `http://localhost` |
| GET/POST | `/oauth/authorize` | Login + Freigabeseite S7; PKCE `S256` Pflicht |
| POST | `/oauth/token` | Code-Einlösung und Refresh (Rotation, Familien-Widerruf) |
| POST | `/mcp` | MCP (Streamable HTTP, ohne SSE). Bearer-Token Pflicht: JWT aus `/oauth/token` oder – nur mit `MCP_STATIC_TOKEN_ENABLED=true` – `MCP_STATIC_TOKEN`. Tool: `ping`. |

## Einrichtung

### 1. Lima-City

1. Subdomain `training.gen-em.org` auf das Verzeichnis `/training.jennym.org/public` zeigen lassen, HTTPS-Zertifikat aktivieren.
2. PHP-Version 8.4 wählen.
3. MySQL-Datenbank mit eigenem Benutzer anlegen.
4. Per FTP die Datei `/training.jennym.org/.env` anlegen, Vorlage: [`server/.env.example`](server/.env.example). `MIGRATION_SECRET` und `OAUTH_JWT_SECRET` jeweils z. B. mit `openssl rand -hex 32` erzeugen (mindestens 32 Zeichen, zwei verschiedene Werte). **Ab Version 0.2.0 ist `OAUTH_JWT_SECRET` Pflicht** – vor dem ersten Deployment von 0.2.0 eintragen, sonst schlagen Migration und Health-Check fehl.

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

### 5. Claude verbinden

- **claude.ai (Web und Mobile-App):** Einstellungen → Connectors → Custom Connector hinzufügen, URL `https://training.gen-em.org/mcp`, keine Client-ID/Secret eintragen (Claude registriert sich selbst). Beim Verbinden öffnet sich die Anmeldung, danach die Freigabeseite: „Freigeben“ wählen. Der Connector steht dann auch in der Mobile-App zur Verfügung.
- **Claude Desktop / Claude Code (Fallback, D-06):** in der `.env` `MCP_STATIC_TOKEN` (z. B. `openssl rand -hex 32`) und `MCP_STATIC_TOKEN_ENABLED=true` setzen; im Client den Server `https://training.gen-em.org/mcp` mit Header `Authorization: Bearer <MCP_STATIC_TOKEN>` eintragen. Nach dem Test `MCP_STATIC_TOKEN_ENABLED` wieder auf `false` setzen.
- **Notbremse:** `OAUTH_JWT_SECRET` wechseln macht alle Access-Tokens sofort ungültig; Refresh-Tokens lassen sich in der Tabelle `oauth_token` (`revoked = 1`) sperren.

## Entwicklung

```bash
cd server
composer install
vendor/bin/phpunit                      # Unit-Tests; Integrationstests werden ohne Datenbank übersprungen
TEST_DB_HOST=127.0.0.1 TEST_DB_NAME=training_test TEST_DB_USER=… TEST_DB_PASSWORD=… vendor/bin/phpunit
```

Achtung: Die Integrationstests löschen alle Tabellen der Testdatenbank.

Lokal starten (ohne `.htaccess`): `.env` in `server/` anlegen (für `http://` ist `APP_URL=http://localhost:8080` möglich, dann ohne `Secure`-Cookies), Assets bauen mit `php bin/build-assets.php`, dann `php -S 127.0.0.1:8080 -t public bin/dev-router.php` (liefert vorhandene Dateien aus `public/` direkt aus). Datenbank einmalig mit `curl -X POST -H "X-Migration-Secret: …" http://127.0.0.1:8080/admin/migrate` migrieren.

### Assets (Branding)

Einzige Quelle der Gestaltung ist `docs/branding/`. `php server/bin/build-assets.php` kopiert Design-System (`styles.css`, `tokens/`, `fonts/`), `mockups/app.css`, die Tabler-Icons und den Lama-Kopf nach `server/public/assets/`. Der Ordner ist nicht im Repo; CI und Deploy-Workflow bauen ihn. Eigene Ergänzungen stehen in `server/public/css/training.css` (keine Inline-Styles wegen Content-Security-Policy).

### Migrationen (D-20)

- Dateiname `NNNN_beschreibung.sql` oder `NNNN_beschreibung.php`, fortlaufend ohne Lücke ab `0001`.
- `App::SCHEMA_VERSION` in `server/src/App.php` muss der höchsten Nummer entsprechen (Test prüft das).
- Ein fachlicher Schritt pro Datei. MySQL beendet Transaktionen bei `CREATE`/`ALTER` implizit; `schema_version` wird nach jeder einzelnen Migration fortgeschrieben.
- SQL-Dateien: Anweisungen mit `;` trennen; kein `DELIMITER` (dafür PHP-Migration: `return function (PDO $pdo): void { … };`).
- Kein Rollback: Rückweg = vorherigen Git-Stand deployen und Pre-Migration-Dump einspielen (ab AP-10).

## Wiederherstellung

Folgt mit AP-10 (Backup und Update-Mechanik).
