# Changelog

Alle nennenswerten Änderungen werden hier dokumentiert. Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), Versionierung nach [SemVer](https://semver.org/lang/de/).

## [Unreleased]

### Dokumentation
- L-T1-07 Laursen/Buchheit, Science and Application of HIIT (2019), einsortiert: Gesamt-PDF und 34 Kapitel-PDFs; doppelt hochgeladene Kenney-Datei entfernt.

## [0.30.2] – 2026-09-29

AP-15: Erinnerung nicht während der geführten Einheit (Wunsch des Athleten).

### Geändert
- Das Overlay „Blockbilanz/Zielklärung fällig“ erscheint nicht mehr in der geführten Einheit (S9) und nicht auf einer Übungsseite, die aus S9 geöffnet wurde. Nach dem Abschluss leitet S9 wie bisher auf die Wochenansicht (S2), dort erscheint es sofort.

### Dokumentation
- Die Entscheidungen von AP-15 im Hauptkonzept heißen jetzt D-72 bis D-78, weil D-70/D-71 parallel für die Literatur-Nachsteuerung vergeben wurden (PR #23).

## [0.30.1] – 2026-09-29

AP-15 T6: Dokumentation zu Blockbilanz, Zielklärung und Übergabe.

### Dokumentation
- Hauptkonzept: D-70 bis D-76 (seit 0.30.2: D-72 bis D-78); Abschnitt 3.3 (System of Record), 6 (Wochenplanung beginnt mit `get_handover`, Revision als Datensatz, neuer Schritt 9 „Blockwechsel“), 7 (`block_review`, `app_setting`), 8.2/8.3 (neue Tools, Erweiterungen, Budget), 10 (S2-Karte, S6, S8, S11, Overlay), 14 (Kapitel 10), 15 (AP-15 mit Statusblock), 17.
- `datenmodell.md` mit `block_review` im ER-Diagramm und Schema 24; `branding.md` mit S11 und dem Overlay-Zustand von S2; README (Endpunkte `/block`, `/erinnerung`, Tools, Tests).
- `docs/regeln/trainerregeln.md`: Vorabkapitel 10 (R-UEB-01 bis R-UEB-06); die übrigen Kapitel folgen in AP-07.
- Auftrag `docs/konzept/blockbilanz.md`: Entscheidungen E-21/E-22, Status T1–T6, Korrektur K-B2.

## [0.30.0] – 2026-09-29

AP-15 T5: Blockseite und Blöcke im Verlauf.

### Hinzugefügt
- S11 Blockseite `/block?id=…` (ohne id der aktive Block): Kopf mit Status, Zeitraum, Restlaufzeit und Fälligkeiten; Zielklärung mit Ausgangslage, Phase, Prioritäten, Zielen, Zielevents, Entscheidungen als Tabelle mit verworfenen Alternativen, Risiken, Ableitung für den Block und offenen Fragen; Revisionen als Zeitleiste; Bilanz mit Ziel/Soll/Ist/Bewertung, Tests, geänderten Annahmen, Empfehlung und den eingefrorenen Kennzahlen; Fassungen je Datensatz aufklappbar; weitere Blöcke. Nur lesen, ohne JavaScript; Entwürfe sind markiert.
- S6 Verlauf: Abschnitt „Blöcke“ mit allen Blöcken, Status, Fälligkeiten und Link zur Blockseite.
- Offline: Die Woche lädt die Blockseite des aktiven Blocks vor; der Service Worker speichert `/block`.
- Mockup `s11-block.html` (aktiv mit Zielklärung, mit Bilanz, ohne Reviews); Browser-Test prüft S11 und S6 bei 375 px.

## [0.29.0] – 2026-09-29

AP-15 T4: Termin für Blockbilanz und Zielklärung im Kalender.

### Hinzugefügt
- Ein Termin je Block (`training-block-<id>.ics`) am Blockende, Standard 08:00–10:00 in der Zeitzone des Athleten mit Erinnerung am Vortag 08:00; Titel „Blockbilanz + Zielklärung: <Block>“ bzw. nur der noch fehlende Teil; Beschreibung mit Zweck, Blockzeitraum und Link zur Blockseite; Kategorie „Planung“. Liegt das Blockende mehr als 16 Wochen nach Blockbeginn, steht der Termin auf Blockbeginn + 112 Tage.
- Der Termin entsteht bzw. ändert sich mit `upsert_block` und `write_block_review` und wird gelöscht, sobald die Bilanz des Blocks und die Zielklärung eines Folgeblocks bestätigt sind (neue Fassung der Ressource nach dem Löschen wie bei den Tagesterminen). Beginn, Dauer und Erinnerung sind in den Einstellungen änderbar; eine Änderung überträgt die Termine sofort.
- Der stündliche Abgleich (und „Abgleichen“ in S8) schreibt die Termine aller geplanten und aktiven Blöcke und entfernt verwaiste Blocktermine; Ergebnis unter `kalender.blocktermine`. Audit `calendar_block_event`, Fehler als `calendar_error` (entity `kalender_block`) und `fehler_kalender` in der Tool-Antwort.

## [0.28.0] – 2026-09-29

AP-15 T3: Erinnerung an Blockbilanz und Zielklärung auf der Webseite.

### Hinzugefügt
- Overlay auf jeder Seite nach dem Login, solange eine Blockbilanz oder Zielklärung fällig ist: Karte mit Grund und Link zur Blockseite, „Morgen wieder erinnern“ (Fokus) oder „Diese Woche nicht mehr“. Die Seite dahinter ist gesperrt (`inert`); ohne JavaScript bedienbar. Es kommt am nächsten Tag bzw. nach 7 Tagen wieder und verschwindet, sobald die Fassung im Chat bestätigt ist. Nicht bei Schreibsperre. Quittierung `POST /erinnerung` (Audit `reminder_ack`).
- Offline: Die Quittierung läuft über den Formularpuffer des Service Workers; solange sie wartet, blendet die gespeicherte Seite das Overlay aus.
- S2: Karte „Block“ unter dem Morgen-Check-in mit Restlaufzeit, allen Fälligkeiten (auch Revision) und Link zur Blockseite; ohne aktiven Block „Kein aktiver Block – Zielklärung im Projekt-Chat“.
- S8 „Training“: Unterseite „Blockbilanz und Zielklärung“ mit Erinnerung an/aus, Vorlauf Bilanz (0–28 Tage) und Zielklärung (0–42 Tage) sowie Beginn, Dauer und Erinnerung des Blocktermins im Kalender (Termin selbst folgt in T4).
- Mockup `s2-woche.html` mit Blockkarte und Zustand `?state=erinnerung`; Browser-Test `tests/e2e/erinnerung.e2e.cjs`.

### Geändert
- Browser-Tests der geführten Einheit und des Übungskatalogs legen zum Testblock eine bestätigte Zielklärung an (sonst deckt die Erinnerung die Seiten ab).

## [0.27.0] – 2026-09-29

AP-15 T2: MCP-Tools für Übergabe, Blockbilanz, Zielklärung und Revision.

### Hinzugefügt
- `get_handover` (Pflichtaufruf zu Beginn jeder Planungssitzung): aktiver Block, gültige Zielklärung, die zwei jüngsten Bilanzen, Revisionen des Blocks, Kennzahlen der letzten 4 Wochen gegen das Blockmittel, Wochentexte der letzten 4 Wochen (`wochen_kurz`), Fälligkeiten, offene Fragen, Stand je Profilabschnitt und offene Entwürfe; höchstens 8 000 Zeichen (bei Bedarf stufenweise gekürzt, Feld `gekuerzt`). `detail: true` liefert zusätzlich die Volltexte der jüngsten Zielklärung und Bilanz.
- `get_block_reviews`: gültige Fassungen eines Blocks mit Inhalt und Kennzahlen; mit `fassungen` alle Versionen samt Entwürfen und Grund.
- `write_block_review`: neue Fassung einer Revision, Bilanz oder Zielklärung mit Schemaprüfung (Fehler mit Pfad, nichts geschrieben), `reason` ab Fassung 2, Revisionen fortlaufend nummeriert; der Server berechnet die Kennzahlen (Bilanz: Blockzeitraum, Revision: 28 Tage bis zum Gesprächsdatum, sonst `period_start`/`period_end`). Zielklärung nur für geplante oder aktive Blöcke. Audit `review_write`; eine bestätigte Fassung löscht die Quittierungen der Erinnerung dieser Art.

### Geändert
- `get_block` nennt die Reviews des Blocks (Kurzliste) und seine Fälligkeiten.
- `upsert_block` antwortet mit den Fälligkeiten des Blocks und dem Hinweis `zielklaerung_fehlt`.
- `write_week_plan` lehnt Wochen nach dem Ende des aktiven Blocks mit `blockwechsel_erforderlich` ab, solange kein Folgeblock mit bestätigter Zielklärung existiert; Wochen bis zum Blockende bleiben möglich. Die Antwort nennt offene Fälligkeiten (`faellig`).

## [0.26.0] – 2026-09-29

AP-15 T1: Datenmodell für Blockbilanz, Zielklärung und Revision.

### Hinzugefügt
- Migration 0024 `block_review`: je Trainingsblock Revisionen (1, 2, …), eine Blockbilanz und eine Zielklärung, jeweils mit Fassungen (`version`, `reason`), Status Entwurf/bestätigt, Zeitraum, Kurzsatz, Inhalt (`content_json`) und vom Server eingefrorenen Kennzahlen (`kennzahlen_auto`). Ein Block mit Reviews lässt sich nicht löschen.
- JSON-Schemata `server/schemas/review-zielklaerung.json`, `review-bilanz.json`, `review-revision.json` und `Training\Review\ReviewValidator` (Pfadangaben in Fehlermeldungen, Bilanz: Zeitraum von ≤ bis).
- `Training\Data\ReviewRepository`: Fassungen, gültige Fassung je Block/Art/Nummer (jüngste bestätigte, neuerer Entwurf zusätzlich), nächste Revisionsnummer.
- `Training\Review\Kennzahlen`: Planerfüllung je Typ, sRPE (Summe, je Woche, je Typ), Ausdauer aus dem Intervals.icu-Spiegel (km, Höhenmeter, Zeit je HF-Zone), Schmerz je Ort mit Trend, Morgentest (Mittel links/rechts, rote Tage), Check-in-Abdeckung, Wellness-Mittel; fehlende Quellen liefern null.
- `Training\Review\Faelligkeit`: fällige Bilanz, Zielklärung und Revision aus Blöcken, bestätigten Reviews, Vorlauftagen und heute.
- Einstellungen (ohne Oberfläche, folgt in T3): Vorlauf Bilanz (7 Tage) und Zielklärung (14 Tage), Overlay an/aus, Quittierung, Uhrzeit, Dauer und Erinnerung des Blocktermins.
- JSON-Export: `kennzahlen_auto` als Objekt.

## [0.25.1] – 2026-09-29

AP-16 T6: Dokumentation des Übungskatalogs.

### Dokumentation
- Hauptkonzept: Datenmodell (7), `exercise_id` in `plan_json` (7.1), Tools und Budgets (8.2, 8.3), Seiten S8/S9/S10/S10a (10), Trainerregeln Kapitel 9 „Übungskatalog“ (14); `datenmodell.md` mit ER-Diagramm und Schema 23; `gefuehrte-einheit.md` E-19 um den Link „Ausführung“ ergänzt.
- Neu `docs/regeln/trainerregeln.md` mit Vorabkapitel 9 (R-UEB-10 bis R-UEB-14); die übrigen Kapitel folgen in AP-07.

## [0.25.0] – 2026-09-29

AP-16 T5: wöchentliche Linkprüfung.

### Hinzugefügt
- Der stündliche Cron `/cron/intervals-sync` prüft einmal je 7 Tage bis zu 50 Links des Übungskatalogs (die am längsten ungeprüften zuerst). Defekte Links setzen die Übung auf „Links prüfen“ (Hinweis in den Einstellungen und in `find_exercise`/`get_exercise`), wieder erreichbare Links setzen sie zurück. Nicht erreichbare Links (Zeitüberschreitung) ändern nichts. Ergebnis in der Cron-Antwort (`linkpruefung`) und im Audit (`exercise_linkcheck`); Fehler brechen den Abgleich nicht ab.

## [0.24.0] – 2026-09-29

AP-16 T4: Verlinkung der Übungen und Offline.

### Hinzugefügt
- S3: Übungen und Kletterblöcke mit Katalogeintrag sind Links auf die Übungsseite (Icon Buch); Zurück führt in die Einheit.
- S9 geführte Einheit: Link „Ausführung“ in der Karte der aktuellen Übung; zurück geht es ohne Rückfrage an derselben Stelle weiter (Übung, Satz, Eingaben).
- Kalender: im Kurzplan unter jeder Übung mit Katalogeintrag der Link auf die Übungsseite; Kurzplan und Links zusammen höchstens 1 000 Zeichen, sonst entfallen Links.
- Offline: Die Woche lädt die Übungsseiten aller Einheiten der laufenden und nächsten Woche vor; ohne Netz zeigt die Übungsseite statt des Videos den Link. Der Service Worker speichert `/uebung` und `/uebungen`.
- Browser-Test `tests/e2e/uebung.e2e.cjs` (läuft über `run.sh` und in der CI).

## [0.23.0] – 2026-09-29

AP-16 T3: Übungsseiten auf der Webseite.

### Hinzugefügt
- S10 Übung (`/uebung?id=<slug>`): Kategorie, Bewegungsmuster, Ausrüstung, Konfidenz; Kurzbeschreibung und Ziel, Voraussetzung, Ausführung (nummeriert), Worauf achten, Fehlerquellen, Vorsicht (hervorgehoben), Progression/Regression mit Varianten, Dosierungshinweis, eingebettete Videos (16:9, erst beim Scrollen geladen, Link darunter), Links mit Prüfstatus, Quellen, Fassungen. Hinweis bei „Links prüfen“ und bei archivierten Übungen. Zurück führt zur aufrufenden Einheit.
- S10a Übungskatalog (`/uebungen`): Suche, Filter nach Kategorie, archivierte auf Wunsch; Einstieg aus den Einstellungen (mit Anzahl und Hinweis auf Übungen mit defekten Links).
- Content-Security-Policy: `frame-src https://www.youtube-nocookie.com https://player.vimeo.com` nur auf S10; der Video-Rahmen sendet die Domain als Referrer (sonst verweigert YouTube die Wiedergabe).
- Icons `book`, `search`, `video` (Tabler); Mockups `s10-uebung.html`, `s10a-uebungen.html`, S3 mit verlinktem Übungsnamen.

## [0.22.0] – 2026-09-29

AP-16 T2: MCP-Tools des Übungskatalogs und Linkprüfung.

### Hinzugefügt
- MCP-Tools `find_exercise` (Suche: exakter Name/Alias, Teilstring, ähnliche Übungen mit gleichem Bewegungsmuster), `get_exercise` (vollständiger Eintrag, Varianten, Fassungen, früherer Stand), `list_exercises` (Kompaktliste), `upsert_exercise` (anlegen/ändern mit Grund und Fassung, Duplikatschutz über Namen und Aliase, Varianten ohne Schleifen, Archivieren nur ohne geplante Verwendung, `hinweis_chat` für den Chat).
- Linkprüfung durch den Server: Videos über oEmbed (erkennt gelöschte und private Videos), Textseiten per GET; nur öffentliche https-Ziele auf Port 443, höchstens 3 Weiterleitungen, 64 kB, 5 s, alle Links parallel. Ein defekter Link setzt die Übung auf „Links prüfen“, ist aber kein Fehler.
- `write_week_plan`/`update_session`: Übungen ohne `exercise_id` werden angenommen und als `warnungen` gemeldet; unbekannte oder archivierte IDs sind Fehler (nichts geschrieben). `get_week_overview` nennt je Einheit die `exercise_ids`.

## [0.21.0] – 2026-09-29

AP-16 T1: Datenmodell, Schemata und Validator für den Übungskatalog (Auftrag `docs/konzept/uebungskatalog.md`, D-64 bis D-69).

### Hinzugefügt
- Migration 0023 (Schema 23): Tabellen `exercise` (Slug, Name, normalisierter Name, Kategorie, Bewegungsmuster, Ausrüstung, Variante von, Schwierigkeit, Status, Konfidenz, Inhalt `content_json`, Fassungsnummer), `exercise_alias` und `exercise_version` (Schnappschuss je Änderung mit Grund).
- Schema `server/schemas/exercise.json` für den Inhalt einer Übung: Kurzbeschreibung, Ziel, Muskeln, Voraussetzung, Ausführung (2–12 Schritte), Worauf achten, Fehlerquellen, Vorsicht, Progression/Regression, Dosierungshinweis, höchstens 2 Text- und 2 Videolinks (nur https), Quellen (mindestens eine), Notizen.
- `plan_json`: optionales `exercise_id` (Slug) je Übung in Kraft/Haltung/Mobilität und je Kletterblock; bei Bouldern, Ausdauerrouten und Technik ist es nicht erlaubt (E-05). Bestehende Pläne bleiben gültig.
- Normalisierung für Namen und Aliase (Kleinschreibung, Umlaute, Bindestriche, Akzente), Embed-Adressen für YouTube (youtube-nocookie.com) und Vimeo, Prüfung der Verweise aus `plan_json` (`ExerciseLink`: Fehler bei unbekannter oder archivierter Übung, Warnungen ohne Katalog oder bei abweichendem Namen). Die MCP-Tools und die Webseite folgen in T2 bis T4.
- JSON-Export gibt `equipment_json`, `content_json` und `snapshot_json` als Objekte aus.

### Dokumentation
- Auftrag `docs/konzept/uebungskatalog.md` im Repo, vom Athleten bestätigt; ergänzt um E-17 bis E-20 (Referrer am Video-iframe, oEmbed-Prüfung für Videos, Schutzregeln der Linkprüfung, `name_norm`). Hauptkonzept: AP-16, D-64 bis D-69.

## [0.20.2] – 2026-09-28

Favicon mit gerundeten Ecken (D-63, Wunsch des Athleten).

### Geändert
- Browser-Tab: Das Favicon ist jetzt V3 (Lama hell auf Pflaume) mit um 22 % gerundeten, transparenten Ecken – als `favicon-rund.svg`, PNG 32/48/96 (`favicon-rund-*.png`) und `favicon.ico` (16/32/48). Bisher waren im Kopfteil die eckigen V3-PNGs 48/96 und V2 (Papiergrund) als SVG/ICO eingetragen; Firefox nahm die PNGs, der Tab zeigte ein eckiges V3.
- Startbildschirm-Icons bleiben eckig (`lama-192/512.png`, `lama-512-maskable.png`, `apple-touch-icon-180.png`, Manifest unverändert): Android und iOS schneiden sie selbst zu, iOS färbt transparente Ecken schwarz.
- `favicon.svg` entfällt (neues Motiv, neuer Dateiname; Icons liegen 7 Tage im Browser-Cache). `favicon.ico` behält seinen Namen; Browser zeigen dort ggf. bis zu 7 Tage noch das alte Bild.
- Vorlage `docs/branding/mockups/icon-optionen/v3-favicon.svg`; `build-icons.cjs` rendert daraus mit transparentem Hintergrund, die übrigen PNGs unverändert ohne Alpha-Kanal. Mockups verwenden die neue Vorlage als Favicon.

### Hinzugefügt
- Test: Tab-Favicons mit Alpha-Kanal (auch `favicon.ico`), Startbildschirm-Icons ohne; gerundete SVG-Vorlage.

### Dokumentation
- Konzept: Übergabe AP-06 Teil D eingearbeitet – Hypertrophie-Ergänzung zu T2 (L-T2-20 bis L-T2-32, D-62, V-16; D-28 unverändert), L-T2-07 um die 3. Aufl. ergänzt, Beschaffungsliste 13.4 erweitert.

## [0.20.1] – 2026-09-28

Nachtrag zu AP-13 T1: Das App-Icon kam nie beim Browser an.

### Behoben
- App-Icons lagen unter `/icons/`. Diesen Pfad belegt Apache in der Standardkonfiguration serverweit (`Alias /icons/` auf die eigenen Server-Symbole, `autoindex.conf`); der Alias greift vor dem Document Root und der `.htaccess`, der Server antwortete deshalb mit seinem eigenen „Not Found“. Folge: IronFox (und andere Browser, die die Manifest-Icons nachladen) zeigten beim „Zum Startbildschirm“ ein „T“ statt des Lamas; auch PNG-Favicons, `favicon.svg` und `apple-touch-icon` fehlten. Der Ordner heißt jetzt `server/public/app-icons/`; Manifest, Kopfteil `_head_icons.php`, Service Worker (statische Pfade) und `docs/branding/build-icons.cjs` sind angepasst. Dateinamen bleiben gleich.

### Hinzugefügt
- Test: kein Ordner im Document Root und kein Icon-Verweis aus Manifest oder Kopfteil unter einem von Apache serverweit belegten Pfad (`/icons/`, `/error/`, `/manual/`, `/cgi-bin/`).

**Nach dem Deployment:** alte Verknüpfung vom Startbildschirm entfernen und neu anlegen (Prüfschritte P-A1, P-A3, P-A7).

## [0.20.0] – 2026-09-28

AP-14 Nachtrag T9: Entscheidungen des Athleten zu den offenen Punkten O-05 bis O-07 (Auftrag `docs/konzept/gefuehrte-einheit.md`, E-23).

### Geändert
- Geführte Einheit: Kletterblöcke ohne Haltezeit und ohne Dauer mit mindestens zwei Sätzen (z. B. Zugkraft 4 Sätze, Pause 120 s) werden wie Kraftübungen satzweise geführt – „Satz erledigt“ je Satz, danach der Pausentimer, wenn eine Pause geplant ist. S9 zeigt „n Sätze“ mit dem Ziel darunter. Ein Satz ohne Zeiten bleibt „offen“ mit einem „Erledigt“.

### Behoben
- Die fixierte Speichern-Leiste lag auf dem Smartphone beim Scrollen hinter der unteren Navigation (S3 Einheit, Check-in, Schmerz und weitere Formulare); sie sitzt jetzt wie in der geführten Einheit darüber, auf iPhones ohne doppelten Abstand zur Home-Leiste. In der Woche überdeckt die Leiste des Check-ins nicht mehr die Kacheln darunter.
- Geführte Einheit: Beim letzten Satz steht kein Hinweis auf eine Pause mehr, die danach nicht kommt.

### Dokumentation
- Entscheidungen E-23: 30-s-Ton erst bei Phasen über 45 s (wie umgesetzt, E-17 und 6.4 angeglichen), keine Screenshots im Manifest, Kletterblöcke satzweise; Testfälle A-13/A-14; D-57 ergänzt.
- Konzept: Übergabe AP-06 Teil C eingearbeitet – neuer Literaturblock R Reha/Prävention (13.2.5, L-R-01 bis L-R-28: Patellasehne, Sprunggelenk, Laufumfang); neu D-61, Q-15, Q-16; Q-13 ergänzt; Beschaffungsliste 13.4 erweitert.

## [0.19.0] – 2026-09-28

AP-11 geändert (D-60, ändert D-50): ein Sammeltermin je Tag im Kalender – Unterpunkt T8 des Auftrags `docs/konzept/gefuehrte-einheit.md` (Wunsch des Athleten).

**Hinweis zum Umstieg:** Der nächste stündliche Abgleich (oder „Abgleichen“ in den Einstellungen) ersetzt die bisherigen Einzeltermine je Einheit im Zeitraum 7 Tage zurück bis 8 Wochen voraus durch die Sammeltermine. Ältere Einzeltermine bleiben unverändert im Kalender, außer die App ändert die Einheit (dann wird ihr Einzeltermin sofort entfernt).

### Geändert
- Kalender (CalDAV): statt eines Termins je Einheit ein ganztägiger Termin je Trainingstag (Ressource `training-tag-JJJJ-MM-TT.ics`, feste UID je Tag; wird der Termin eines Tages gelöscht, bekommt der nächste eine neue Fassung `-1`, `-2` … mit eigener UID, weil Nextcloud bis 34.0.1 das erneute Anlegen einer gelöschten Adresse aus dem Papierkorb heraus ablehnt). Titel „Typ: Titel“ bei einer Einheit, bei mehreren „Training: Titel 1 + Titel 2“ in Planreihenfolge; kein „✓“ mehr im Titel und kein abgesagter Termin – der Status steht je Einheit in der Beschreibung. Die Beschreibung führt alle Einheiten des Tages (ohne Ruhetage) mit Kurzsatz, Kurzplan mit Priorität, Dauer und Status, Trainer-Begründung und Link zur Einheit auf, bei mehreren Einheiten je Abschnitt mit Überschrift „Typ: Titel“ und Trennlinie; der Termin selbst verlinkt die Woche. Kletterblöcke erscheinen mit ihrem Namen („Bouldern Volumen“ statt `bouldern_volumen`).
- Erinnerung (D-52): eine je Tag, solange mindestens eine Einheit des Tages geplant oder verschoben ist. Texte in den Einstellungen angepasst („Am Trainingstag um …“).
- Übertragen wird jeweils der ganze Tag: neue und ersetzte Einheiten, bei `update_session` alter und neuer Tag, bei der Rückmeldung auf der Webseite der Tag der Einheit. Tage ohne Einheiten (oder nur mit Ruhetag) verlieren ihren Termin. Der Abgleich zählt Termine (Tage) und entfernt außer verwaisten Sammelterminen auch die alten Einzeltermine (`training-session-<id>.ics`); ändert die App eine Einheit (Rückmeldung, `update_session`, Ersetzen), löscht sie deren alten Einzeltermin sofort, auch außerhalb des Abgleichzeitraums. Fremde Termine bleiben unberührt.
- Audit-Eintrag `calendar_error` bezieht sich auf den Tag (`kalender_tag`, Datum) statt auf eine Einheit. Fassung je Tag in `app_setting` (`kalender_tag_<Datum>`), Einträge älter als 60 Tage vor dem Abgleichzeitraum werden entfernt.
- Meldung bei HTTP 403 des Kalenders nennt zusätzlich einen möglichen Konflikt mit dem Nextcloud-Papierkorb.
- Tests: `DayEventTest` und `CalendarTest` auf Sammeltermine umgestellt (Testfälle K-01 bis K-07 des Auftrags: zwei Einheiten an einem Tag, Verschieben, leerer Tag, Woche ersetzen mit behaltener Einheit, Abgleich mit alten Einzelterminen, Ruhetage, Reihenfolge, Erinnerung); nachgebildeter Nextcloud-Papierkorb, alte Einzeltermine außerhalb des Zeitraums, Abbruch nach dem ersten Fehler; Rauchtest gegen Radicale.

### Entfernt
- `Training\Calendar\SessionEvent` (Termin je Einheit) und `CalendarSync::push()`/`remove()` für einzelne Einheiten; ersetzt durch `Training\Calendar\DayEvent` und `CalendarSync::pushDays()`. Test `SessionEventTest` durch `DayEventTest` ersetzt.

## [0.18.0] – 2026-09-28

AP-14: Geführte Einheit (D-57, D-58) – Auftrag `docs/konzept/gefuehrte-einheit.md`, Unterpunkte T3–T7.

### Hinzugefügt
- Ablaufplan der geführten Einheit (`Training\Plan\Ablaufplan`, T3): leitet aus `plan_json` für Kraft, Haltung, Mobilität und Klettern deterministisch die Schritte ab – je Übung bzw. Block Art (Wiederholungen, Halten, Block, offen), Sätze, Arbeits- und Pausenzeit, Soll-Text und Ist-Felder wie in S3. Halten bei Wiederholungsangaben in Sekunden oder Minuten („45s“, „2 min“, „30-45 s“ → obere Grenze) und bei `hang_s`, Block bei `duration_min`. Ausdauer und Ruhetage haben keinen Ablaufplan.
- Tests: alle Testfälle A-01 bis A-12 des Auftrags, alle Arten von Kletterblöcken und die Schreibweisen der Haltezeiten. Offene Kletterblöcke übernehmen die geplanten Sätze (ohne Timer).
- Seite S9 „Einheit geführt“ (T4): `GET /einheit?id=…&modus=start` zeigt für geeignete Einheiten dasselbe Formular wie S3 schrittweise – je Übung bzw. Block eine Phase-Karte (Übung, Sätze, Timer-Anzeige bzw. Wiederholungen, Soll, Hinweis), die Ist-Felder der Übung (gleiche Feldnamen wie S3, mit Soll vorbelegt), „Als Nächstes“ und am Ende Übersicht und Rückmeldung. Gespeichert wird wie aus S3 über `POST /einheit` (Konfliktschutz, Offline-Puffer, Prüfung unverändert); Fehler erscheinen wieder in S9. Ohne JavaScript sind alle Schritte sichtbar und das Formular ist vollständig ausfüllbar. Kopfzeile mit Zurück zu S3 und (mit JavaScript) Stummschalter; Kurzsatz der Einheit oben.
- S3: Knopf „Einheit starten“ (bzw. „Erneut durchgehen“ bei erledigten Einheiten) für Kraft, Haltung, Mobilität und Klettern mit Plan; Ausdauer, Ruhetage und Einheiten ohne Plan ohne Knopf; `modus=start` zeigt bei Ausdauer und ohne Plan S3, Ruhetage haben weiterhin keine Einheitenseite (404).
- Einstellungen → Training → „Timer-Signale“ An/Aus (T6, Einstellung `timer_ton` in `app_setting`, Standard „an“): Vorgabe für Ton und Vibration im geführten Modus; in der Einheit jederzeit für diese Einheit umschaltbar. Formular ohne JavaScript.
- Offline (T6): Die Woche lädt die geführte Einheit für heutige und morgige geeignete Einheiten vor; `js/gefuehrt.js` liegt im Versions-Cache des Service Workers. S9 öffnet damit auch ohne Netz, Speichern ohne Netz landet wie bisher im Puffer. Die Vorgabe der Timer-Signale merkt sich das Gerät (S8 und jede online geladene S9), sodass eine offline gezeigte S9 eine seit dem Vorladen geänderte Einstellung übernimmt.
- Seitenskript `js/gefuehrt.js` (T5): zeigt jeweils einen Schritt; innerhalb einer Übung laufen Arbeit, Pause und Sätze automatisch, zwischen Übungen „Weiter“ (E-03); nach „Satz erledigt“ startet der Pausentimer. Timer zeitstempelbasiert: nach Bildschirm aus oder Tabwechsel wird nachgerechnet, verpasste Signale werden nicht nachgeholt, ein Hinweiston meldet eine inzwischen beendete Phase. Signale per Web Audio (ohne Audiodateien) und Vibration: Start einer Arbeitsphase, 30 s (Phasen über 45 s) und 10 s vor Ende (ab 15 s), 3-2-1, Abschlusston. Seite und Browserleiste grün während der Arbeit, rot in Pause, „bereit“ und angehalten (Statusfarben, B-08). Bildschirm bleibt an (Wake Lock). Stummschalter in der Kopfzeile gilt für diese Einheit und wird auch vor dem Start gemerkt; stumm blinkt die Anzeige in den letzten 3 s (auch bei Phasen bis 3 s). Ein Tipp, der einer gerade abgelaufenen Phase galt (Phase endete vor dem Neuzeichnen oder vor weniger als 0,5 s), wird verworfen statt auf die neue Phase angewendet. Zurück zu einer erledigten Übung und „Weiter“ durch erledigte Übungen behalten die Erledigt-Markierung; eine nachgeholte Übung gilt nicht mehr als übersprungen. Fortschritt und Eingaben liegen im Browser (sessionStorage, 12 h): Neu laden fragt „Fortsetzen“ oder „Neu starten“, nach dem Speichern bzw. nach Zustellung eines offline gepufferten Speicherns beginnt die geführte Einheit neu. Im Abschluss Dauer (gemessen vom ersten Start bis zum Erreichen des Abschlusses) und Status (teilweise, wenn eine Übung übersprungen wurde) vorbelegt; Übersicht erledigt/übersprungen/geändert. Lehnt der Server einen Ist-Wert ab, öffnet S9 die betroffene Übung (nur deren Ist-Karte ist markiert).
- Tests: Kern des Skripts ohne Browser mit Node (`node --test server/tests/js/*.test.cjs`, alle Abläufe aus 8.2) und Browser-Durchlauf mit Playwright und gesteuerter Uhr (`bash server/tests/e2e/run.sh`: Farbwechsel, Signale, Anhalten, Neu laden mit Fokus, Stumm mit Blinken nur in den letzten 3 s, verworfener Tipp nach abgelaufener Phase, Hintergrund, Überspringen, gemessene Dauer, Speichern, Speichern ohne Netz über den Puffer). Beide laufen in der CI (Chrome des Runners).
- Tests: Startknopf je Typ, Rückfall auf S3, Aufbau für Kraft (Wiederholungen, Halten) und Klettern (Hangboard, Block, offen), Speichern aus S9, Fehler und Konflikt in S9, Vorgabe der Timer-Signale.

### Geändert
- Soll-Texte von Übungen und Kletterblöcken kommen aus `Training\View\PlanFormat` (bisher im Template von S3), damit S3 und die geführte Einheit dieselben Texte zeigen.
- Ist-Felder und Rückmeldung von S3 als gemeinsame Teilvorlagen (`_ist_exercise.php`, `_ist_block.php`, `_feedback_fields.php`) für S3 und S9; Seitenrahmen akzeptiert eine zusätzliche Klasse für `main`.
- Workflow „Test und Deploy“: zusätzlich Node-Tests und Browser-Durchlauf der geführten Einheit im Test-Job (höchstens 10 Minuten). `tests/e2e/run.sh` startet die App auf einem freien Port (oder `E2E_PORT`) und bricht ab, wenn der eigene Server nicht läuft.

### Behoben
- Sporadischer Testfehler in `MorningCheckinTest::testDaylightSavingSwitch` (AP-12 als „nicht reproduzierbar“ vermerkt): Das gelegentliche Aufräumen der MCP-Sitzungsdateien verglich echte Dateizeiten mit der im Test vorgestellten Uhr und löschte die gerade angelegte Sitzung. Verglichen wird jetzt mit der echten Zeit; im Betrieb ändert sich nichts.

## [0.17.0] – 2026-09-28

AP-13: App-Icon und Logo (D-55, D-59) sowie Begründungstexte der Planung (D-56) – Auftrag `docs/konzept/gefuehrte-einheit.md`, Unterpunkte T1 und T2.

**Achtung (Planung im Projekt-Chat):** `write_week_plan` verlangt jetzt `focus` (Kurzsatz der Woche) und je Einheit außer Ruhetagen `coach_summary` (Kurzsatz, höchstens 200 Zeichen); ohne sie wird nichts geschrieben. Den Connector in Claude ggf. neu verbinden, damit die neuen Tool-Beschreibungen geladen werden.

### Hinzugefügt
- App-Icon „ganzes Lama“ (D-59): V3 (Fläche hell auf Pflaume 600) als PNG 48, 96, 192, 512 (`any`), 512 `maskable` und `apple-touch-icon` 180; V2 (Fläche Pflaume auf Papier) als SVG-Favicon und `favicon.ico` (16/32/48) im Docroot. Das Skript `docs/branding/build-icons.cjs` (Playwright/Chromium) rendert den Satz aus `docs/branding/mockups/icon-optionen/`; das Ergebnis ist eingecheckt, weil der Server kein SVG rendern kann.
- Login, Setup und Freigabeseite tragen jetzt wie die App-Seiten Manifest, PNG-Icons mit Größenangabe, `apple-touch-icon` und `theme-color` (gemeinsamer Kopfteil `_head_icons.php`). „Zum Startbildschirm“ zeigt damit auch in LibreWolf/Firefox (Verknüpfung über die Seiten-Icons) und von der Login-Seite aus das Logo.
- Manifest mit `id` und `description`, getrennte Einträge `any`/`maskable`.
- `.htaccess`: `image/x-icon` für `.ico`, Icons und Favicon 7 Tage im Browser-Cache. Service Worker liefert `/favicon.ico` wie das Manifest aus dem Versions-Cache. Der Dev-Router setzt für `.ico` und `.webmanifest` dieselben Content-Types wie Apache.
- Skript `docs/branding/mockups/screenshots.cjs` erzeugt die Mockup-Screenshots neu (und prüft Überlauf, fehlende Ressourcen, Skriptfehler).
- Tests: Manifest (JSON, jede Datei vorhanden, `sizes` = PNG-Kopf), Kopfteil-Links, `favicon.ico` (16/32/48), beide Seitenrahmen, `HEAD /favicon.ico` über den Dev-Router.
- Begründungstexte je Woche und Einheit (E-01, E-10): neues Feld `session.coach_summary` (Kurzsatz, höchstens 200 Zeichen; Migration `0022`, `App::SCHEMA_VERSION` = 22). `write_week_plan` prüft Pflicht und Längen (Woche `focus` 1–255, `coach_notes` ≤ 1 500; Einheit `coach_summary` 1–200 außer `ruhe`, `coach_rationale` ≤ 1 500) und meldet alle betroffenen Einheiten auf einmal; `update_session` ändert `coach_summary` und `coach_rationale` (leerer Text entfernt die Begründung, bei Ruhetagen auch den Kurzsatz), Wochentexte nur über `write_week_plan`. Die Tool-Beschreibungen enthalten die Regel aus E-10.
- `get_week_overview` liefert zusätzlich `woche.begruendung` (ausführlicher Text der Woche, nur bis 1 500 Zeichen) und je Einheit (auch Ruhetag) `kurz`, wenn vorhanden; `get_session_detail` liefert `coach_summary`.
- S2 Woche: Karte unter der Kopfzeile mit dem Kurzsatz und „mehr“ (aufklappbar ohne JavaScript) für den ausführlichen Text. S3 Einheit: Kurzsatz im Seitenkopf, „mehr“ für die Begründung; Altdaten ohne Kurzsatz zeigen „Trainer-Notiz“ zum Aufklappen.
- Tests: Pflichtfelder mit Fehlerliste, Grenzlängen 200/255/1 500 (Zeichen, nicht Bytes), Lese-Tools, `update_session`, Altdaten mit überlangen Texten, Anzeige in S2/S3 inkl. Altdaten, Kalenderbeschreibung.

### Geändert
- App-Kennung in Kopfzeile, Navigation und Login-Karte: ganzes Lama (`/assets/lama.svg` aus `lama-symbol-flaeche.svg`) statt Lama-Kopf; Mockups und Screenshots entsprechend (Branding B-09).
- S2: die Angabe „Fokus …“ in der Kopfzeile der Woche entfällt (der Kurzsatz steht jetzt in eigener Zeile darunter). S3: „Trainer-Notiz: …“ als ganzer Absatz entfällt zugunsten von Kurzsatz und „mehr“.
- Kalendertermin: Beschreibung beginnt mit dem Kurzsatz, danach Kurzplan (mit Priorität, Dauer, Status), die Begründung („Trainer: …“, gekürzt auf 1 000 Zeichen) und der Link.

### Entfernt
- `server/public/icons/icon-192.png`, `icon-512.png`, `icon-512-maskable.png` (Lama-Kopf); der Build kopiert `lama-kopf.svg` nicht mehr (der Kopf bleibt im Design-System).

### Dokumentation
- Sechs weitere Literatur-Volltexte einsortiert und eingetragen: L-P10 Foster 2001, L-P12 Impellizzeri 2020, L-P13 Silbernagel 2007, L-T2-17 Shiri 2018, L-T2-18 Steffens 2016, L-T3-04 Draper 2015.
- Konzeptentwurf `docs/konzept/gefuehrte-einheit.md` (Fable): App-Icon und Logo auf das ganze Lama (D-55, Q-14), Begründungstexte je Woche und Einheit mit Kurzsatz und „mehr“ (D-56), geführte Einheit mit Timer, Farbwechsel und Signalen (D-57, D-58); AP-13 und AP-14 im Hauptkonzept; vom Athleten bestätigt, Logo-Variante D-59 (V3 App-Icon, V2 Favicon und Kennung). Mockups `s9-einheit-gefuehrt.html` (fünf Zustände) und `icon-optionen.html` (fünf Logo-Varianten), S2/S3/S8 angepasst, Icons `player-play`, `player-pause`, `player-skip-back`, `volume`, `volume-off` ergänzt (Branding B-08, B-09).
- Konzept: Übergaben AP-06 Teil B (Verifikation L-P10–L-P14, L-T2-11/-12, T3) und Teil A (Haltung/Rücken, L-T2-15–L-T2-19) eingearbeitet; neu D-54 und Q-13; Beschaffungsliste 13.4 ergänzt.

## [0.16.0] – 2026-09-28

AP-12: Morgen-Check-in mit Morgentest (D-53, `docs/konzept/morgen-checkin.md`).

### Hinzugefügt
- Check-in um den Morgentest Patellasehne erweitert: links/rechts je 0–10 ohne Vorauswahl, leer = nicht erhoben (erneutes Tippen leert), Anleitung im Formular; unter „Weitere Angaben“ Nacken/BWS, Sprunggelenk links (umgeknickt, Schwellung), Hand rechts (bis Stichtag, Einstellung, Standard 23.11.2026) und Warnzeichen.
- Karte „Morgen-Check-in“ oben in der Wochenansicht: Formular, bis heute ein Morgentest erfasst ist, danach Zusammenfassung mit Ampel und Grund, Werten, Wochenausgangswert (24-Stunden-Regel) und hervorgehobenem Hinweis „Abklärung empfohlen“.
- Ampel nach festen Regeln (rot > 5 oder zwei Tage streng steigend bis ≥ 4, gelb 4–5, grün ≤ 3), Steuerwert = Maximum links/rechts.
- MCP-Tool `get_morning_checks`; `get_week_overview` liefert je Tag Steuerwert und Ampel, Tage grün und Abdeckung.
- Schmerzorte Patellasehne, Sprunggelenk und Brustwirbelsäule.
- Migrationen `0020` (Spalten in `checkin`) und `0021` (Schmerzorte); `App::SCHEMA_VERSION` = 21.
- Tests: alle 10 Ampelfälle, Wochenausgangswert, Zeitumstellung 25.10.2026, Formular (null ≠ 0, Überschreiben, Hand-Stichtag), Wochenkarte, MCP, Migration auf befüllter Datenbank.

### Geändert
- Erholung und Muskelkater bleiben Pflicht (E-11); der bisherige Check-in speichert weiter wie gewohnt.

### Dokumentation
- L-A02 Ferrauti, Trainingswissenschaft für die Sportpraxis (2. Aufl. 2025) ergänzt: Gesamtbuch ohne doppelt eingebettete Schriften/Bilder (355 → 94 MB, seitengleich geprüft) und 25 Kapitel-PDFs.

## [0.15.0] – 2026-09-28

AP-11: Erinnerungen an Kalenderterminen (D-52).

### Hinzugefügt
- Termine geplanter und verschobener Einheiten tragen eine Erinnerung am Tag der Einheit, standardmäßig um 05:00 Uhr; erledigte und ausgelassene Einheiten erinnern nicht.
- Einstellungen → Verbindungen → „Erinnerung im Kalender“: Uhrzeit wählen oder abschalten; die Termine werden danach sofort neu übertragen.
- Migration `0019` `app_setting` (Einstellungen als Schlüssel/Wert); `App::SCHEMA_VERSION` = 19.
- Tests für Erinnerung (Standard, Ändern, Aus, Fehlerfall) und iCalendar-Alarm; Rauchtest gegen Radicale.

### Dokumentation
- Literatur-Volltexte (29 PDFs) mit der Literaturliste im Konzept abgeglichen, nach ID umbenannt und in Blockordner unter `docs/literatur/` sortiert (D-51); Bücher zusätzlich als Kapitel-PDFs in `<ID>_kapitel/` (179 Dateien). Verzeichnis `docs/literatur/README.md`; im Konzept Felder `datei`/`kapitel` und in der Beschaffungsliste die Spalte „vorhanden“.

## [0.14.0] – 2026-09-28

AP-11: Einheiten im Nextcloud-Kalender per CalDAV (D-50).

### Hinzugefügt
- Jede Einheit außer Ruhetagen wird als ganztägiger Termin in einen CalDAV-Kalender geschrieben (Nextcloud; `CALDAV_URL`, `CALDAV_USER`, `CALDAV_PASSWORD` in der `.env`, nur https): Titel „Typ: Titel“, Beschreibung mit Priorität, Dauer, Kurzplan, Trainer-Begründung und Link zur App; „✓“ bei erledigt/teilweise, abgesagter Termin bei ausgelassen.
- Übertragung bei jedem Wochenplan, `update_session`, Ersetzen einer Woche und jeder Rückmeldung auf der Webseite; Fehler brechen nichts ab (`fehler_kalender` in der Tool-Antwort, Audit-Log, Einstellungen).
- Abgleich 7 Tage zurück bis 8 Wochen voraus im stündlichen Cronjob `/cron/intervals-sync` und per Knopf „Abgleichen“ in den Einstellungen; entfernt verwaiste eigene Termine, fremde bleiben.
- `/health` meldet `kalender` (konfiguriert, nicht konfiguriert, ungültig ohne https).
- Tests mit simuliertem CalDAV-Server und iCalendar-Prüfung (Escaping, Zeilenfaltung); Rauchtest gegen Radicale.

### Geändert
- `/cron/intervals-sync` läuft auch ohne Intervals.icu-Konfiguration, wenn der Kalender eingerichtet ist.

### Behoben
- Konzept 3.1/3.3: Athletenprofil lag laut Text in K2 (Intervals.icu) statt K3 (MySQL); Spiegel (D-43) nachgetragen.

## [0.13.0] – 2026-09-28

AP-09 Teil 6: Offline-Fähigkeit (D-45, D-49). Damit sind alle AP-09-Teilpakete umgesetzt.

### Hinzugefügt
- Service Worker `/sw.js` und Seitenskript `/js/offline.js`: Woche, Einheiten, Check-in und Schmerz sind ohne Netz lesbar (erst Netz mit 5 s Wartezeit, sonst gespeicherter Stand mit Hinweis „Offline – Stand vom …“); Gestaltung liegt je Version im Cache.
- Beim Öffnen der aktuellen Woche werden aktuelle und nächste Woche mit allen Einheiten sowie Check-in und Schmerz für heute vorgeladen (höchstens alle 10 Minuten je Seite).
- Check-in, Rückmeldung und Schmerz werden ohne Netz auf dem Gerät gepuffert und automatisch gesendet, sobald Netz da ist (Android/Chrome auch im Hintergrund, iPhone beim nächsten Öffnen); Anzeige wartender, abgelehnter und kollidierender Eingaben mit „Öffnen“, „Trotzdem übernehmen“, „Verwerfen“.
- Schutz gegen Überschreiben: Check-in- und Einheitenformular tragen den Stand des Eintrags; wurde er inzwischen geändert, wird nicht gespeichert (409), die Eingaben bleiben im Formular.
- `GET /offline/token` (frisches CSRF-Token für gepufferte Eingaben); Kopfzeile `X-Offline-Queue` liefert Statuscodes statt Seiten.
- Erfassungszeit gepufferter Rückmeldungen wird als Durchführungszeit übernommen.
- Tests für Konflikt, Statusantworten, Token, Erfassungszeit und Vorladeliste.

### Geändert
- Die Login-Seite löscht die offline gespeicherten Seiten (Abmelden); ungesendete Eingaben bleiben und werden nach dem Login gesendet.

## [0.12.0] – 2026-09-28

AP-09 Teil 5: Athletenprofil als DB-Objekt (D-48, ersetzt D-15). Asymmetrische Backups gestrichen (D-47).

### Hinzugefügt
- Migration `0018` `athlete_profile`: Abschnitte Ziele, Zeitbudget, Ausrüstung, Einschränkungen, Leistungswerte, Sonstiges als Markdown-Text; jede Änderung als neue Fassung mit Datum, Urheber (Claude/Web) und Grund; `App::SCHEMA_VERSION` = 18.
- MCP-Tool `update_athlete_profile` (Scope `training:write`, Schreibsperre, Audit-Log); unveränderter Text legt keine Fassung an.
- Seite `/profil` (Link unter Einstellungen): Abschnitte lesen, einzeln bearbeiten, frühere Fassungen ansehen; Schutz gegen Überschreiben, wenn Claude den Abschnitt inzwischen geändert hat.
- Tests für Tools, Fassungen, früheren Stand, Konflikt und Webseite.

### Geändert
- `get_athlete_profile` liest aus der Datenbank; neue optionale Parameter `section`, `as_of` (Stand am Ende eines Tages) und `include_history`.

### Entfernt
- Build-Schritt `docs/athlet/*.md` → `server/resources/athlet/` und `App::profileFile()`; `docs/athlet/` entfällt.

## [0.11.0] – 2026-09-28

AP-09 Teil 4: Passkey-Login zusätzlich zum Passwort (D-44).

### Hinzugefügt
- Passkeys (WebAuthn) über `lbuchs/webauthn`: in den Einstellungen anlegen (mit Namen) und entfernen, auf der Login-Seite „Mit Passkey anmelden“ (nur sichtbar, wenn ein Passkey angelegt ist und der Browser WebAuthn kann). Das Passwort bleibt Rückfallweg.
- Migration `0017` `webauthn_credential` (öffentlicher Schlüssel, Signaturzähler, zuletzt genutzt); `App::SCHEMA_VERSION` = 17.
- Endpunkte `POST /passkey/register/options`, `/passkey/register` (angemeldet, CSRF-Header), `/passkey/login/options`, `/passkey/login`; Challenge im signierten, 5 Minuten gültigen Cookie; Relying-Party-ID ist der Host aus `APP_URL`.
- Anmelden mit Passkey hebt eine Passwort-Sperre auf; Anlegen und Entfernen im Audit-Log.
- `public/js/passkey.js` (erstes JavaScript der App, nur für Passkeys; Seiten funktionieren weiter ohne).
- Tests mit Software-Authenticator: Registrieren, Anmelden, Wiederholung, fremder Schlüssel, falscher Origin, abgelaufene Challenge, Sperre, Entfernen.

### Geändert
- Passkeys sind im SQL-Backup enthalten (nach einem Restore weiter nutzbar), nicht im JSON-Export.

### Behoben
- Einstellungen: „1 Dateien“ → „1 Datei“ bei den Pre-Migration-Dumps.

## [0.10.0] – 2026-09-28

AP-09 Teil 3: Feedback nach Intervals.icu (Q-02 → D-46).

### Hinzugefügt
- Beim Speichern einer Rückmeldung (S3) werden RPE (1–10) und Gefühl automatisch auf die zugeordnete Intervals.icu-Aktivität geschrieben, die Notiz als Kommentar (nur wenn neu oder geändert); Hinweis in der Wochenansicht bei Erfolg bzw. Fehler; Audit-Log.
- `IntervalsClient::updateActivity`, `addActivityMessage`.
- Test für Übertragung, keine Doppelkommentare, RPE 0 und Fehlerfall.

## [0.9.0] – 2026-09-28

AP-09 Teil 2: Spiegel Intervals.icu → MySQL (D-43).

### Hinzugefügt
- Migrationen `0015` `ext_activity`, `0016` `ext_wellness` (Zusammenfassungen, keine Streams); `App::SCHEMA_VERSION` = 16.
- Spiegel mit read-through: Webseite und MCP lesen aus MySQL; ein Zeitraum wird höchstens alle 5 Minuten live abgefragt und übernommen (in Intervals.icu gelöschte Aktivitäten werden entfernt); fällt Intervals.icu aus, kommen die Daten aus dem Spiegel.
- `GET /cron/intervals-sync?key=…` für einen stündlichen Lima-City-Cronjob; Status (Anzahl, Zeitraum, letzter Abgleich, Fehler) in den Einstellungen.
- Spiegeldaten sind in SQL-Backup und JSON-Export enthalten.
- Tests für read-through, Rückfall, Cron-Abgleich und Löschungen.

### Geändert
- `BACKUP_CRON_SECRET` heißt jetzt `CRON_SECRET` (gilt für alle Cron-Endpunkte).

### Behoben
- Einstellungsseite war bei veraltetem Schema nicht erreichbar, sobald neue Tabellen abgefragt wurden (gefunden durch den Schreibsperren-Test); Spiegelzugriffe tolerieren fehlende Tabellen.

## [0.8.0] – 2026-09-28

AP-09 Teil 1: JSON-Export und Verlauf (D-42).

### Hinzugefügt
- JSON-Export aller Trainingsdaten in den Einstellungen (unverschlüsselt, ohne Sessions, Tokens, Cache und Passwort-Hash; JSON-Spalten als Objekte; im Audit-Log).
- `/verlauf` (S6): Kennzahlen (sRPE diese Woche/Vorwoche, Check-in-Abdeckung, Schmerzereignisse), Wochenlast je Bereich über 8 Wochen als kleine Vielfache mit gemeinsamer Achse, Schmerz je Ort und Seite als Raster (stärkste Meldung der Woche, Hinweis beim Berühren), Tabelle aller Werte.
- Tests für Export und Verlauf.

### Dokumentation
- Konzept: Q-11 → D-40 (`upsert_block`), Q-12 → D-41 (Feld `sport`) bestätigt.

## [0.7.0] – 2026-09-28

AP-05 MCP-Tools produktiv.

### Hinzugefügt
- Lese-Tools (Abschnitt 8.2, aggregiert, Skalen benannt): `get_week_overview` (Plan vs. Ist, sRPE je Typ, Compliance, Aktivitäten aus Intervals.icu inkl. nicht geplanter, Schmerz der Woche, Check-in-Abdeckung, Fitness/Ermüdung/Form), `get_session_detail`, `get_pain_history` (je Ort mit 7-Tage-Trend), `get_wellness_trend` (HRV, Ruhepuls, Schlaf, Check-in, Baseline 7 vs. 28 Tage), `get_block`, `get_athlete_profile`.
- Schreib-Tools: `write_week_plan` (vollständige Prüfung vor dem Schreiben, DB-Transaktion, danach Intervals.icu-Workouts je Ausdauereinheit mit Fehlerbericht je Einheit; `replace_existing` ersetzt nur geplante Einheiten ohne Rückmeldung und löscht deren Events), `update_session` (inkl. Nachziehen, Löschen bei „ausgelassen“ oder Neuanlage des Events), `upsert_block` (D-40, Voraussetzung für Wochenpläne).
- Rechteprüfung je Tool über den Token-Scope (`training:read`, `training:write`); Schreibsperre (D-20) für alle Schreib-Tools; Audit-Log aller MCP-Schreibzugriffe inkl. Intervals-Events und -Fehler.
- `plan_json` Ausdauer: optionales Feld `sport` (Intervals.icu-Sportart, Standard Run; D-41).
- Build: `docs/athlet/*.md` → `server/resources/athlet/` für `get_athlete_profile`.
- Tests über den echten `/mcp`-Endpunkt (Scopes, Block, Wochenplan, Übersicht, Ersetzen, Intervals-Fehler und erneuter Sync, Schreibsperre, Lese-Tools).


## [0.6.0] – 2026-09-28

AP-10 Backup und Update-Mechanik.

### Hinzugefügt
- Backup (D-18): SQL-Dump per PHP (Struktur aller Tabellen; Daten ohne Sessions, OAuth-Tokens/-Codes und Cache; ohne berechnete Spalten) → gzip → Verschlüsselung kompatibel zu `openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -md sha256`. Dateiname mit Zeitstempel, Schemastand und Anlass.
- Download in den Einstellungen (nur angemeldet, im Audit-Log).
- Backup per E-Mail: `GET /cron/backup-mail?key=…` für den Lima-City-Cronjob, SMTP über PHPMailer, Intervall in `.env`; Zustand in `var/backup-mail.json`, Fehler in Einstellungen und Wochenansicht.
- Pre-Migration-Dump (D-20): vor jeder ausstehenden Migration (Deploy und Einstellungen) nach `backups/`, die letzten 5 bleiben; schlägt der Dump fehl, wird nicht migriert.
- Schreibsperre (D-20): Weichen Code- und Datenbankstand ab, sind alle Schreibzugriffe der Webseite gesperrt (Seite „Update erforderlich“ mit Migrationsknopf, Hinweis auf allen Seiten); Migrationsknopf in den Einstellungen.
- `/health` prüft `backups/`.
- Restore-Anleitung im README; Tests für Restore in eine leere Datenbank (inkl. Entschlüsseln mit der openssl-Kommandozeile), Rotation, Abbruch bei fehlgeschlagenem Dump, Schreibsperre, Download und E-Mail.

### Geändert
- Neuer Pflichtwert `BACKUP_PASSWORD` (mindestens 16 Zeichen). **Vor dem Deployment in die `.env` eintragen.**
- `/admin/migrate` legt vor Migrationen einen Dump an und meldet dessen Namen.


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
