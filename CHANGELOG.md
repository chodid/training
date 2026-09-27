# Changelog

Alle nennenswerten Änderungen werden hier dokumentiert. Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), Versionierung nach [SemVer](https://semver.org/lang/de/).

## [Unreleased]

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
