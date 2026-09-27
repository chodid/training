# KI-Personal-Trainer

Privates Repo (D-23) für das System „KI-Personal-Trainer": PHP-Server (Webseite, MySQL, MCP-Endpunkt, OAuth) plus Dokumente (Konzept, Wissenskarten, Trainerregeln, Athletenprofil, Blockpläne).

Maßgeblich ist das Konzept: [`docs/konzept/konzept-ki-personal-trainer.md`](docs/konzept/konzept-ki-personal-trainer.md). Änderungen stehen im [`CHANGELOG.md`](CHANGELOG.md), der Prüfstand im [Prüfprotokoll](docs/pruefung/pruefprotokoll.md).

## Struktur

| Pfad | Inhalt | Arbeitspaket |
|---|---|---|
| `server/public/` | Document Root (einziger per HTTP erreichbarer Ordner), `index.php` als einziger Einstieg | AP-00 |
| `server/src/` | PHP-Quellcode (Namespace `Training\`) | AP-00 ff. |
| `server/config/` | Konfiguration ohne Secrets (derzeit leer) | – |
| `server/migrations/` | Nummerierte Migrationen (D-20) | AP-00, AP-03 |
| `server/tests/` | PHPUnit-Tests (Unit und Integration gegen MySQL) | AP-00 ff. |
| `.github/workflows/deploy.yml` | Test und Deployment (D-17) | AP-00 |
| `docs/konzept/` | Konzeptdokument | – |
| `docs/pruefung/` | Prüfprotokoll (Konzept Abschnitt 16) | alle |
| `docs/wissen/` | Wissenskarten (Sammeldateien, 13.1) | AP-06 |
| `docs/literatur/` | Literatur-Volltexte als PDF, Open Access und gekauft (D-31); nie ins Projektwissen | AP-06 |
| `docs/regeln/` | Trainerregeln (Abschnitt 14) | AP-07 |
| `docs/athlet/` | Athletenprofil (D-15) | AP-08 |
| `docs/plaene/` | Blockpläne | AP-08 |
| `docs/branding/` | Branding-Dokument (D-19) | vor AP-04 |

## Server-Layout (Lima-City, D-17)

```
/training.jennym.org/          ← FTP-Zielordner = Inhalt von server/
├── .env                       ← Konfiguration, nur auf dem Server, vom Deployment nie berührt
├── .htaccess                  ← sperrt den Ordner, falls der Document Root falsch gesetzt ist
├── backups/                   ← Datenbank-Backups (ab AP-10), vom Deployment nie berührt
├── public/                    ← Document Root der Subdomain training.gen-em.org
├── src/  migrations/  vendor/
└── .ftp-deploy-sync-state.json  ← Statusdatei des Upload-Schritts
```

## Endpunkte (Stand AP-00)

| Methode | Pfad | Zweck |
|---|---|---|
| GET | `/` | Platzhalterseite |
| GET | `/health` | Zustand als JSON: PHP-Erweiterungen, Konfiguration, Datenbank, Schemastand. `200` = in Ordnung, `503` = Handlungsbedarf. Enthält keine Secrets. |
| POST | `/admin/migrate` | Führt ausstehende Migrationen aus. Header `X-Migration-Secret` muss `MIGRATION_SECRET` entsprechen. `401` ohne Header, `403` bei falschem Secret, `409` wenn bereits eine Migration läuft oder die Datenbank neuer als der Code ist. |

## Einrichtung

### 1. Lima-City

1. Subdomain `training.gen-em.org` auf das Verzeichnis `/training.jennym.org/public` zeigen lassen, HTTPS-Zertifikat aktivieren.
2. PHP-Version 8.4 wählen.
3. MySQL-Datenbank mit eigenem Benutzer anlegen.
4. Per FTP die Datei `/training.jennym.org/.env` anlegen, Vorlage: [`server/.env.example`](server/.env.example). `MIGRATION_SECRET` z. B. mit `openssl rand -hex 32` erzeugen (mindestens 32 Zeichen).

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

## Entwicklung

```bash
cd server
composer install
vendor/bin/phpunit                      # Unit-Tests; Integrationstests werden ohne Datenbank übersprungen
TEST_DB_HOST=127.0.0.1 TEST_DB_NAME=training_test TEST_DB_USER=… TEST_DB_PASSWORD=… vendor/bin/phpunit
```

Achtung: Die Integrationstests löschen alle Tabellen der Testdatenbank.

Lokal starten (ohne `.htaccess`): `.env` in `server/` anlegen, dann `php -S 127.0.0.1:8080 -t public public/index.php`.

### Migrationen (D-20)

- Dateiname `NNNN_beschreibung.sql` oder `NNNN_beschreibung.php`, fortlaufend ohne Lücke ab `0001`.
- `App::SCHEMA_VERSION` in `server/src/App.php` muss der höchsten Nummer entsprechen (Test prüft das).
- Ein fachlicher Schritt pro Datei. MySQL beendet Transaktionen bei `CREATE`/`ALTER` implizit; `schema_version` wird nach jeder einzelnen Migration fortgeschrieben.
- SQL-Dateien: Anweisungen mit `;` trennen; kein `DELIMITER` (dafür PHP-Migration: `return function (PDO $pdo): void { … };`).
- Kein Rollback: Rückweg = vorherigen Git-Stand deployen und Pre-Migration-Dump einspielen (ab AP-10).

## Wiederherstellung

Folgt mit AP-10 (Backup und Update-Mechanik).
