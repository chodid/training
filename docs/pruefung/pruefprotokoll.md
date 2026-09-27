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
noch_zu_pruefen:
  - was: Abnahme der Mockups (Smartphone, Tablet, Desktop) und des Branding-Dokuments
    wie: Sichtprüfung durch Athlet (docs/branding/mockups/index.html oder screenshots/)
  - was: Word-Vorlage mit installierten variablen TTF (Source Sans 3)
    wie: manuell durch Athlet (Brief.docx öffnen, Schriftersetzung prüfen)
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
