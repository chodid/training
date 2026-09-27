# Changelog

Alle nennenswerten Änderungen werden hier dokumentiert. Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), Versionierung nach [SemVer](https://semver.org/lang/de/).

## [Unreleased]

### Dokumentation
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
