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
noch_zu_pruefen:
  - was: CI-Job test läuft grün (MySQL 8.4)
    wie: GitHub Actions am Pull Request
  - was: Push auf main führt ohne manuelle Schritte zu lauffähigem Stand (Upload, Migration, Health-Check grün)
    wie: GitHub Actions nach Merge; Workflow-Log prüfen
  - was: Health-Endpunkt über HTTPS erreichbar, status ok, schema aktuell
    wie: manuell (Browser https://training.gen-em.org/health)
  - was: HTTP wird auf HTTPS umgeleitet, HSTS-Header gesetzt
    wie: manuell (http://training.gen-em.org/health aufrufen; Header in Browser-Entwicklertools)
  - was: Migrations-Endpunkt lehnt Aufrufe ohne/mit falschem Secret ab
    wie: manuell (curl -X POST https://training.gen-em.org/admin/migrate → 401; mit falschem Header → 403)
  - was: .env und Subdomain-Ordner nicht per HTTP erreichbar
    wie: manuell (https://training.gen-em.org/../.env bzw. /.env → 404, kein Inhalt)
  - was: .env überlebt ein Deployment
    wie: manuell (zweiten Push auf main, danach /health weiterhin ok)
  - was: Secrets nicht im Repo
    wie: automatisiert (GitHub Secret Scanning) + Durchsicht
  - was: SMTP-Anhang-Größenlimit
    wie: Testversand in AP-10
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
