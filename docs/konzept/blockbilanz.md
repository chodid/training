# Auftrag: Blockbilanz, Zielklärung und Übergabe (AP-15)

Stand: 2026-09-29 · Auftraggeber: Athlet · Konzept: Fable (Projekt-Chat) · Umsetzung: Code-Instanz (Claude Code)

Dieses Dokument ist der Auftrag an die Code-Instanz und wird von ihr fortgeschrieben (Abschnitt 13). Versionsnummern der Anwendung legt die Code-Instanz fest. Nummern der Entscheidungen (E-nn) und offenen Punkte (O-nn) gelten nur in diesem Dokument; im Hauptkonzept `konzept-ki-personal-trainer.md` wird das Paket als AP-15 geführt, die Kernentscheidungen erhalten dort die nächsten freien D-Nummern (Abschnitt 10).

## 1. Aufgabe

Eine planende KI-Instanz muss zu Beginn jeder Planungssitzung nachvollziehen können, **welche Ziele bisher verfolgt wurden, wie sie erreicht werden sollten, was die Rationale war, was erreicht wurde und was nicht**. Heute steckt dieses Wissen verstreut in `training_block.phase_notes`, den Wochentexten (`focus`, `coach_notes`), den Einheitentexten und einer Markdown-Datei im Repo (`doc_ref` → `docs/plaene/`), die nicht mit der Datenbank synchron sein muss. Es gibt keine Stelle, an der das *Ergebnis* einer Blockrevision oder eines Zielgesprächs strukturiert abgelegt wird.

Ziel des Auftrags:

1. Drei Datensatzarten je Trainingsblock in der Datenbank (Master): **Revision** (Belastungssteuerung alle 3–4 Wochen), **Blockbilanz** (Rückblick am Blockende) und **Zielklärung** (Ausblick vor einem Block) – jeweils mit Fassungen, Entwurf/Bestätigung und einem vom Code eingefrorenen Kennzahlenblock.
2. Ein Lese-Tool `get_handover`, das der planenden Instanz die Vorgeschichte gebündelt und budgetiert liefert und Fälligkeiten meldet.
3. Erinnerungen an fällige Bilanz oder Zielklärung: als Overlay auf der Webseite (täglich, muss aktiv weggeklickt werden) und als Termin im Nextcloud-Kalender (08:00–10:00 mit Erinnerung am Vortag 08:00).
4. Änderungen am Betriebsablauf (Hauptkonzept Abschnitt 6) und an den Trainerregeln (Abschnitt 14): Übergabe ist Pflichtaufruf, Bilanz und Zielklärung sind Pflichtschritte am Blockwechsel.

Nicht im Umfang: Bearbeiten der Datensätze auf der Webseite (nur lesen, O-01); automatische Bewertung durch Code (Interpretation bleibt bei der KI und wird vom Athleten bestätigt); Rückschreiben nach `docs/plaene/` (O-03).

## 2. Ausgangslage (Bezug auf das Hauptkonzept)

| bereich | stand | betroffen |
|---|---|---|
| `training_block` | `id, name, start_date, end_date, goal_events_json, phase_notes, status (geplant, aktiv, abgeschlossen), doc_ref`; Tool `upsert_block` (D-40) legt an/ändert, „aktiv“ schließt andere aktive Blöcke ab | Teil A, D |
| Begründungstexte | Woche `focus`/`coach_notes`, Einheit `coach_summary`/`coach_rationale` (D-56, E-10 in `gefuehrte-einheit.md`) | Teil B (werden in der Übergabe zusammengefasst) |
| Athletenprofil | `athlete_profile` je Abschnitt mit Fassungen, `reason`, `created_by`; jüngste Fassung je Abschnitt gilt (D-48) | Muster für Fassungen (Teil A) |
| Betriebsablauf | Abschnitt 6: Blockplan 8–16 Wochen; Schritt 8 „Blockplan-Revision alle 3–4 Wochen oder bei Schmerzereignis/Ausfall“ – ohne Ablage des Ergebnisses | Teil E |
| Lese-Tools | `get_week_overview`, `get_wellness_trend`, `get_pain_history`, `get_block`, `get_athlete_profile`; Antwortbudget 8.3 (Wochenübersicht ≤ 2 000 Tokens) | Teil B |
| Kalender | AP-11: CalDAV zu Nextcloud, ein ganztägiger Sammeltermin je Trainingstag (`Training\Calendar\DayEvent`, `CalendarSync::pushDays`, Fassung je Ressource in `app_setting` `kalender_tag_<Datum>` wegen Nextcloud-Papierkorb), Abgleich 7 Tage zurück bis 8 Wochen voraus im stündlichen Cron `/cron/intervals-sync` und per Knopf in S8; VALARM relativ (`RELATED=START`) | Teil D |
| Einstellungen | `app_setting` (Schlüssel/Wert, D-52), `SettingsRepository`, S8 mit Listenkarten | Teil C |
| Webseite | `layout-app` für alle Seiten nach Login; Startseite/S2 mit Karten (Ampel Morgen-Check-in); S6 Verlauf (AP-09: Schmerz je Ort, sRPE je Typ); Offline: `PAGE_PATHS`, `PRECACHE`, `data-offline-form` | Teil C, E |
| Schreibsperre | `App::writeLocked` sperrt fachliche Schreibzugriffe während Migration | Teil B |
| Audit | `audit_log` (actor, action, entity, entity_id, payload_hash, summary) | Teil B |
| Offene Pakete | AP-07 Trainerregeln (offen), AP-08 Athletenprofil und erster Blockplan (offen) | AP-08 erzeugt die erste Zielklärung nach diesem Schema |

## 3. Geklärte Entscheidungen

Mit dem Athleten am 2026-09-29 geklärt (E-01 bis E-09, E-21, E-22). E-10 bis E-20 sind Vorschläge von Fable in diesem Dokument und gelten mit der Bestätigung des Konzepts; die Code-Instanz meldet Abweichungen als offene Punkte.

| id | entscheidung | begruendung |
|---|---|---|
| E-01 | **Master ist die Datenbank.** Bilanz, Zielklärung und Revision sind DB-Objekte, zugänglich über MCP. `docs/plaene/` und das Projekt-Wissen sind nur Spiegel (JSON-Export, O-03). `training_block.doc_ref` bleibt optional. | Die planende Instanz arbeitet über MCP; eine Repo-Datei veraltet. Entscheidung des Athleten. |
| E-02 | **Drei Ebenen mit getrennter Funktion:** Revision alle 3–4 Wochen (nur Belastungssteuerung, kein Zielgespräch), Blockbilanz am Blockende (Rückblick), Zielklärung vor jedem Block (Ausblick). Bilanz und Zielklärung liegen an derselben Blockgrenze und laufen typischerweise im selben Chat, sind aber getrennte Datensätze. | Rückblick und Ausblick beantworten verschiedene Fragen; die nächste Instanz soll beides getrennt lesen. Trainingswissenschaftlich: Zielphasen (Reha, Aufbau, Ausdauerfokus) laufen über Monate; ein 4-Wochen-Takt für Ziele wäre zu kurz, die 3–4-Wochen-Revision bleibt für Deload/Schmerz/Ausfall (L-A03, L-P08: Zyklen sind Mittel, nicht Selbstzweck; entscheidend sind Progression und Monitoring). |
| E-03 | **Kein fester Kalendertakt, sondern Blockgrenze plus Sicherheitsnetz:** Zielklärung ist zusätzlich fällig, wenn die letzte bestätigte Zielklärung älter als 16 Wochen ist (Obergrenze der Blocklänge) oder ein Trigger eintritt (Verletzung, neues Zielevent, geänderte Lebensumstände – gemeldet vom Athleten im Chat). | Der 3-Monats-Wunsch des Athleten ist mit 12-Wochen-Blöcken abgedeckt; die 16 Wochen verhindern, dass ein Block ohne Zielgespräch weiterläuft. |
| E-04 | **Inhalte unterscheiden sich je Art** (Schemata in 4.3): Bilanz = Soll/Ist/Bewertung je Ziel, Kennzahlen, Tests, gelungen/nicht gelungen, geänderte Annahmen/Regeln, Empfehlung, offene Fragen. Zielklärung = Ausgangslage, Phase, Prioritäten je Bereich, Ziele mit Messgröße und Kriterium, Zielevents, **Entscheidungen mit Rationale und verworfenen Alternativen**, Risiken/Abbruchkriterien, Ableitung für den Block. Revision = Anlass, Änderungen mit Grund. | Vorschlag Fable, vom Athleten angenommen. Die verworfenen Alternativen sind für die Übergabe zentral, damit eine spätere Instanz sie nicht erneut vorschlägt. |
| E-05 | **Overlay-Erinnerung auf der Webseite:** Ist eine Bilanz oder Zielklärung fällig, zeigt jede Seite nach dem Login ein Overlay, das aktiv weggeklickt werden muss; es kommt an jedem Tag wieder, bis die fällige Fassung bestätigt ist. | Wunsch des Athleten (Erinnerung über mehrere Tage, nicht nur einmal). |
| E-06 | **Kalendertermin für Bilanz/Zielklärung:** je Block ein Termin mit Uhrzeit (08:00–10:00 in der Zeitzone des Athleten, nicht ganztägig) und Erinnerung am Vortag um 08:00. | Wunsch des Athleten. |
| E-07 | **Revisionen erinnern nicht per Overlay oder Kalender.** Sie erscheinen nur als Fälligkeitshinweis in `get_handover` und als kleine Karte in S2. | Der Athlet hat Overlay und Termin für Bilanz und Zielklärung bestellt; die Revision gehört zur Wochenplanung im Chat. |
| E-08 | **Übergabe durch Code, nicht durch KI, kein Cron.** `get_handover` stellt bei Aufruf deterministisch zusammen (Block, Zielklärung, Bilanzen, Revisionen, Kennzahlen, Fälligkeiten). Interpretationen stehen nur in den bestätigten Datensätzen. | Eine KI-erzeugte Zusammenfassung wäre teuer, unbestätigt und würde die Übergabe von der Bestätigung des Athleten entkoppeln. |
| E-09 | **Entwurf durch KI, Bestätigung durch den Athleten im Chat (D-11).** Tool `write_block_review` schreibt mit `status: entwurf` oder `bestaetigt`; nur bestätigte Fassungen zählen für Übergabe und Fälligkeit. | Wie Wochenplan und Athletenprofil. |
| E-10 | **Jede Art ist einem Block zugeordnet** (`block_id` Pflicht). Die Zielklärung gehört zu dem Block, den sie begründet; der Block wird zuerst mit `upsert_block` angelegt (Status `geplant` oder `aktiv`), dann die Zielklärung geschrieben. | Einfaches Modell; die Übergabe findet die Zielklärung über den Block. |
| E-11 | **Fassungen wie beim Athletenprofil:** jede Änderung schreibt eine neue Zeile mit `version + 1` und `reason`; die jüngste **bestätigte** Fassung je (`block_id`, `kind`, `sequence`) gilt. Ein Block hat höchstens eine Bilanz und eine Zielklärung (`sequence` 1), aber mehrere Revisionen (`sequence` 1, 2, …). | Nachvollziehbar, kein Löschen; Muster ist vorhanden (D-48). |
| E-12 | **Kennzahlen friert der Code ein.** Beim Schreiben einer Bilanz (jeder Fassung) berechnet der Server `kennzahlen_auto` für den Bilanzzeitraum und legt sie im Datensatz ab; die KI liefert keine Kennzahlen. | Die Bilanz bleibt auch dann korrekt, wenn Rohdaten später korrigiert werden; die KI muss nichts abschreiben. |
| E-13 | **Fälligkeitsregeln** (Berechnung in 5.1): Bilanz fällig ab `end_date − bilanz_vorlauf_tage` (Standard 7) des aktiven Blocks bzw. sofort bei abgeschlossenem Block ohne bestätigte Bilanz. Zielklärung fällig ab `end_date − zielklaerung_vorlauf_tage` (Standard 14), wenn kein Folgeblock (`geplant`/`aktiv`, `start_date > heute − 7`) mit bestätigter Zielklärung existiert; außerdem, wenn der aktive Block keine bestätigte Zielklärung hat, oder die jüngste bestätigte Zielklärung älter als 16 Wochen ist (E-03). Revision fällig, wenn im aktiven Block der jüngste bestätigte Datensatz gleich welcher Art älter als 28 Tage ist. | Vorlauf, damit der Termin am Blockende vorbereitet ist; die Vorlauftage sind Einstellungen. |
| E-14 | **Overlay-Bedienung ohne JavaScript:** Overlay als Abschnitt oben im Seiteninhalt mit CSS-Überlagerung (`role="dialog"`, `aria-modal`), Fokus auf der ersten Schaltfläche, zwei Formularknöpfe: „Morgen wieder erinnern“ (Standard) und „Diese Woche nicht mehr“ (7 Tage). Quittierung als `POST /erinnerung`, gespeichert in `app_setting` `erinnerung_<kind>_<block_id>` = Datum, bis zu dem Ruhe ist. Nach Ablauf erscheint das Overlay wieder; ist die Fassung bestätigt, verschwindet es ohne Zutun. | Konsistent mit dem JS-Minimum der Webseite; wirklich wegklicken statt nur schließen. |
| E-15 | **Kalender: ein Termin je Block** (`training-block-<id>[-<Fassung>].ics`), Titel „Blockbilanz + Zielklärung: <Blockname>“ (bzw. nur der noch fehlende Teil), am `end_date` 08:00–10:00 (Zeitzone `user.tz`, als UTC-Zeiten im iCalendar), Beschreibung mit Ziel des Termins und Link zur Blockseite, `VALARM` `TRIGGER:-P1D` (24 h vor Beginn = Vortag 08:00). Der Termin entsteht mit `upsert_block` (Status `geplant`/`aktiv`) und im Abgleich; er wird gelöscht, sobald Bilanz und Zielklärung des Folgeblocks bestätigt sind oder der Block `abgeschlossen` ist und beides vorliegt. Sicherheitsnetz E-03: hat der aktive Block kein `end_date` innerhalb von 16 Wochen nach `start_date`, liegt der Termin auf `start_date + 16 Wochen`. | Umsetzt E-06 mit den Mitteln aus AP-11; die Papierkorb-Regel (Fassung je Ressource) gilt auch hier. |
| E-16 | **Abgleichzeitraum für Blocktermine** = alle Blöcke mit Status `geplant`/`aktiv` (unabhängig von den 8 Wochen der Tagestermine). | Blockenden liegen oft weiter als 8 Wochen voraus. |
| E-17 | **Budget:** `get_handover` ohne `detail` ≤ 2 000 Tokens (Prüfgrenze 8 000 Zeichen); mit `detail: true` die vollständigen `content_json` der jüngsten Zielklärung und Bilanz, ≤ 8 000 Tokens. | Regel 8.3 des Hauptkonzepts. |
| E-18 | **Webseite nur lesend:** Blockseite (S11) zeigt Block, Zielklärung, Revisionen, Bilanz und Fassungen; S6 bekommt einen Abschnitt „Blöcke“ mit Liste und Fälligkeiten. Kein Bearbeiten (O-01). | Umfang klein halten; Änderungen laufen über den Chat. |
| E-19 | **Betriebsablauf:** Schritt 2 beginnt mit `get_handover`; neuer Schritt 9 „Blockwechsel“ (Bilanz → Zielklärung → `upsert_block` → erste Woche). Ein Wochenplan für eine Woche nach `end_date` des aktiven Blocks wird von `write_week_plan` mit Hinweis abgelehnt, solange kein Folgeblock mit bestätigter Zielklärung existiert. | Erzwingt den Blockwechsel im Ablauf, ohne die laufende Woche zu blockieren. |
| E-20 | **Trainerregeln:** neues Kapitel „Übergabe, Revision, Bilanz und Zielklärung“ (Inhalt in Abschnitt 8); bis AP-07 vorliegt, gelten die Tool-Beschreibungen. | Wie bei E-10 in `gefuehrte-einheit.md`. |
| E-21 | **Wochentexte im Handover (O-04):** `get_handover` liefert zusätzlich `wochen_kurz` – die letzten 4 Wochen bis einschließlich der laufenden, je Woche eine Zeile `<week_start>: <focus>` (fehlender Fokus = „–“). | Entscheidung des Athleten 2026-09-29 (Vorschlag Fable angenommen). Günstiger Kontext (≈ 100–200 Tokens) ohne zusätzlichen Aufruf von `get_week_overview`. |
| E-22 | **Uhrzeit des Blocktermins einstellbar (O-05):** S8 „Training“ erhält drei Einstellungen: Beginn (`kalender_block_beginn`, `HH:MM`, Standard `08:00`), Dauer (`kalender_block_dauer_min`, 30–480, Standard 120) und Vorlauf der Erinnerung (`kalender_block_erinnerung_h`, 0–168 Stunden vor Beginn, Standard 24 = Vortag zur selben Uhrzeit; 0 = zu Beginn). Eine Änderung überträgt die Blocktermine aller Blöcke `geplant`/`aktiv` sofort neu. | Entscheidung des Athleten 2026-09-29 (statt „fest, später einstellbar“). Zuschnitt der drei Werte durch die Code-Instanz; E-06/E-15 gelten mit den Standardwerten unverändert. |

## 4. Teil A · Datenmodell

### 4.1 Tabelle `block_review`

```yaml
block_review:
  id:            INT PK
  block_id:      INT FK training_block, NOT NULL, ON DELETE RESTRICT   # E-10; ein Block mit Reviews ist nicht löschbar
  kind:          ENUM(revision, bilanz, zielklaerung)
  sequence:      SMALLINT NOT NULL DEFAULT 1        # bilanz/zielklaerung immer 1; revision 1, 2, …
  version:       SMALLINT NOT NULL DEFAULT 1        # Fassung (E-11)
  status:        ENUM(entwurf, bestaetigt)
  review_date:   DATE NOT NULL                      # Datum des Gesprächs
  period_start:  DATE NULL                          # Bilanz/Revision: betrachteter Zeitraum
  period_end:    DATE NULL
  summary:       VARCHAR(255) NOT NULL              # Kurzsatz für Listen und Übergabe
  content_json:  JSON NOT NULL                      # Schema je kind (4.3)
  kennzahlen_auto: JSON NULL                        # nur bilanz und revision, vom Code (E-12)
  reason:        VARCHAR(255) NULL                  # Grund der neuen Fassung (ab version 2 Pflicht)
  created_by:    ENUM(mcp, web) NOT NULL
  created_at:    DATETIME (UTC)
  confirmed_at:  DATETIME NULL                      # gesetzt, wenn status bestaetigt
UNIQUE (block_id, kind, sequence, version)
CHECK  (kind <> 'bilanz' OR sequence = 1), (kind <> 'zielklaerung' OR sequence = 1)
CHECK  (period_end IS NULL OR period_start IS NULL OR period_end >= period_start)
```

Gültige Fassung: je (`block_id`, `kind`, `sequence`) die Zeile mit `status = bestaetigt` und höchster `version`; ein Entwurf mit höherer Version wird in Listen als „Entwurf“ zusätzlich gezeigt.

`training_block` unverändert. Neue Schlüssel in `app_setting`: `bilanz_vorlauf_tage` (Standard 7), `zielklaerung_vorlauf_tage` (Standard 14), `review_overlay` (`an`/`aus`, Standard `an`), `erinnerung_<kind>_<block_id>` (Datum, E-14), `kalender_block_<id>` (Fassung der Kalenderressource, wie `kalender_tag_<Datum>`).

Backup/JSON-Export (AP-10, AP-09): `block_review` gehört zu den fachlichen Tabellen (Dump, Export, Schreibsperre).

### 4.2 Migration

Eine Migration (neue Tabelle, Rückweg: Tabelle entfernen). Keine Datenübernahme – bestehende Blöcke haben keine Reviews; die erste Zielklärung entsteht in AP-08.

### 4.3 `content_json` je Art (JSON-Schemata in `server/schemas/review-<kind>.json`, Draft 2020-12, `additionalProperties: false`)

```yaml
zielklaerung:
  ausgangslage:
    zeitbudget: string                 # z. B. "6–8 h/Woche, Mo/Do abends Halle"
    umstaende: string|null             # Lebensumstände, Reisen, Saison
    einschraenkungen: string|null      # aktueller Stand Schmerz/Reha (Bezug auf Profil, kein Duplikat)
    ausruestung: string|null
  phase: enum [reha, grundlagen, aufbau, spezifisch, wettkampf, erhalt, uebergang]
  phase_text: string                   # ein Satz, was die Phase bedeutet
  prioritaeten: {t1: enum[A,B,C], t2: enum[A,B,C], t3: enum[A,B,C]}
  ziele:
    - id: string                       # z-1, z-2 … (Bilanz verweist darauf)
      bereich: enum [t1, t2, t3, reha, allgemein]
      ziel: string
      messgroesse: string              # was gemessen wird (Test, Kennzahl)
      kriterium: string                # ab wann erreicht
      termin: date|null
  zielevents: [{name: string, datum: date, art: string|null}]
  entscheidungen:
    - thema: string
      entscheidung: string
      rationale: string
      verworfen: [string]              # verworfene Alternativen mit Grund, mind. leer erlaubt
      quelle: string|null              # L-…, D-…, Regel-ID oder "Einschätzung"
  risiken: [{risiko: string, regel: string}]   # Vorsichtsregel oder Abbruchkriterium
  block:
    dauer_wochen: int
    phasen: [{name: string, wochen: string, fokus: string}]   # spiegelt training_block.phase_notes
    tests_start: [string]              # Ausgangstests, die zu Beginn gemacht werden
  offene_fragen: [string]

bilanz:
  zeitraum: {von: date, bis: date}
  ziele:
    - ziel_id: string|null             # Verweis auf zielklaerung.ziele[].id
      ziel: string
      soll: string
      ist: string
      bewertung: enum [erreicht, teilweise, nicht, nicht_bewertbar]
      grund: string
  tests: [{name: string, start: string|null, ende: string|null, datum: date|null, bewertung: string|null}]
  gelungen: [string]
  nicht_gelungen: [string]
  annahmen_geaendert:
    - was: string                      # Annahme oder Regel (Regel-ID, wenn vorhanden)
      neu: string
      begruendung: string
      vorschlag_trainerregel: bool
  empfehlung: string                   # für die nächsten Wochen bzw. den Folgeblock
  offene_fragen: [string]

revision:
  anlass: enum [turnus, schmerz, ausfall, ermuedung, sonstiges]
  befund: string                       # ein bis drei Sätze, worauf die Änderung reagiert
  aenderungen: [{was: string, warum: string, bis: date|null}]
  wirkung_pruefen: string|null         # woran man in 2–4 Wochen sieht, ob es gewirkt hat
```

Längen: Strings in Listen ≤ 500 Zeichen, `rationale`/`grund`/`empfehlung` ≤ 1 500 Zeichen; Listen ≤ 20 Einträge. Prüfung in `Training\Review\ReviewValidator` (analog `PlanValidator`).

### 4.4 `kennzahlen_auto` (Berechnung `Training\Review\Kennzahlen`, für `period_start`–`period_end`; bei Bilanz ohne Zeitraum = Blockzeitraum)

```yaml
wochen: int
plan_erfuellung:                    # je Typ: geplant, erledigt, teilweise, ausgelassen, verschoben
  ausdauer: {geplant: int, erledigt: int, teilweise: int, ausgelassen: int}
  kraft: {…}; klettern: {…}; haltung: {…}; mobilitaet: {…}
last:
  srpe_summe: int
  srpe_je_woche: [int]              # Verlauf, max. 16 Werte
  srpe_je_typ: {ausdauer: int, kraft: int, klettern: int, haltung: int, mobilitaet: int}
ausdauer:                           # aus ext_activity (Spiegel D-43), sonst null
  km_je_woche: [number]|null
  hm_je_woche: [int]|null
  zeit_zone_min: {z1: int, z2: int, z3: int, z4: int, z5: int}|null
schmerz:                            # je Ort: max, mittel, anzahl, Trend
  je_ort: [{ort: string, max: int, mittel: number, anzahl: int, trend: enum[steigend, fallend, gleich]}]
morgentest: {links_mittel: number|null, rechts_mittel: number|null, rot_tage: int}   # D-53
checkin_abdeckung_prozent: int
wellness:                           # aus ext_wellness, sonst null
  hrv_mittel: number|null
  ruhepuls_mittel: number|null
  schlaf_h_mittel: number|null
berechnet_am: datetime
```

Fehlende Quellen liefern `null`, nie Fehler (wie `get_week_overview`).

## 5. Teil B · MCP-Schnittstelle

### 5.1 Fälligkeit (`Training\Review\Faelligkeit`, reine Funktion aus Blöcken, Reviews, Einstellungen, heute)

Liefert eine Liste `[{kind, block_id, block_name, grund, seit: date, faellig_am: date}]` mit `grund` aus: `blockende` (Bilanz), `block_abgeschlossen_ohne_bilanz`, `folgeblock_ohne_zielklaerung`, `block_ohne_zielklaerung`, `zielklaerung_aelter_16_wochen`, `revision_turnus` (28 Tage). Regeln nach E-13. Dieselbe Funktion nutzen `get_handover`, `get_block`, das Overlay, die Karte in S2 und der Kalender.

### 5.2 Tools

| tool | scope | eingabe | ausgabe / verhalten |
|---|---|---|---|
| `get_handover` | read | `detail: bool = false` | Kompakt (E-17): `block` (Name, Zeitraum, Status, Phasen aus `phase_notes` gekürzt, Zielevents); `zielklaerung` (Phase, Prioritäten, Ziele mit Messgröße/Kriterium, Entscheidungen als `thema: entscheidung` je eine Zeile, Risiken, `review_date`, Version); `bilanzen` (jüngste zwei bestätigte Bilanzen, auch früherer Blöcke: Zeitraum, je Ziel Bewertung + Grund in einer Zeile, `empfehlung`, `annahmen_geaendert` als Zeilen); `revisionen` (des aktiven Blocks: Datum, Anlass, `aenderungen` als Zeilen); `kennzahlen` (letzte 4 Wochen, Format wie 4.4 verkürzt, plus Blockmittel je Woche zum Vergleich); `faellig` (5.1); `offene_fragen` (aus Zielklärung und jüngster Bilanz zusammengeführt); `profil_stand` (Datum je Profilabschnitt, kein Inhalt); `hinweis` bei `detail=false`: „Volltexte mit detail=true“. Mit `detail: true` zusätzlich `content_json` der jüngsten bestätigten Zielklärung und Bilanz ungekürzt. Kein aktiver Block → `block: null`, `faellig` enthält `block_ohne_zielklaerung` mit `block_id: null`. |
| `get_block_reviews` | read | `block_id: int|null` (null = aktiver Block), `kind: enum|null`, `fassungen: bool = false` | Liste der gültigen Fassungen (`id, kind, sequence, version, status, review_date, summary`) plus `content_json`; mit `fassungen` alle Versionen inkl. Entwürfe mit `reason`. Budget: `content_json` je Datensatz ≤ 1 500 Tokens, sonst `summary` + Hinweis. |
| `write_block_review` | write | `block_id: int`, `kind`, `sequence: int|null` (Revision: null = nächste; Bilanz/Zielklärung: ignoriert), `review_date`, `period_start/period_end` (Bilanz/Revision), `summary`, `content: object`, `status: enum[entwurf, bestaetigt]`, `reason: string|null` | Prüft Schema (4.3), Block vorhanden und nicht `abgeschlossen` (außer Bilanz: auch bei `abgeschlossen` erlaubt); Zielklärung nur für Blöcke mit Status `geplant`/`aktiv`; legt neue Fassung an (`version = max + 1`, `reason` ab Version 2 Pflicht); berechnet `kennzahlen_auto` (Bilanz, Revision); bei `bestaetigt` setzt `confirmed_at`; Audit `review_write`; aktualisiert den Kalendertermin (Teil D) und löscht `erinnerung_<kind>_<block_id>`. Antwort: `id, version, status, kennzahlen_auto` (gekürzt), `faellig` (Rest). Schreibsperre `App::writeLocked` wie alle Schreibtools. |
| `get_block` | read | unverändert | zusätzlich `reviews` (Kurzliste: kind, sequence, version, status, review_date, summary) und `faellig` (5.1, nur dieser Block). |
| `upsert_block` | write | unverändert | zusätzlich: legt/aktualisiert den Kalendertermin (E-15); Antwort enthält `faellig` (Zielklärung fehlt noch). |
| `write_week_plan` | write | unverändert | Ablehnung nach E-19 mit Fehler `blockwechsel_erforderlich` und Liste der Fälligkeiten, wenn `week_start > end_date` des aktiven Blocks und kein Folgeblock mit bestätigter Zielklärung existiert. Sonst unverändert; Antwort enthält `faellig`, falls nicht leer. |

Tool-Beschreibungen nennen die Pflicht aus Abschnitt 8 in je einem Satz: `get_handover` „zu Beginn jeder Planungssitzung aufrufen“; `write_block_review` „nur nach Bestätigung des Athleten mit status bestaetigt schreiben; Entwürfe sind erlaubt“.

Audit-Log: `review_write` (entity `block_review`, summary „<kind> Block <id> v<version> <status>“), `calendar_block_event` (Anlegen/Ändern/Löschen), `reminder_ack` (Quittierung, actor web).

## 6. Teil C · Erinnerung auf der Webseite

### 6.1 Overlay (E-05, E-14)

- Nach dem Login prüft `layout-app` bei jedem Seitenaufbau `Faelligkeit` für `bilanz` und `zielklaerung`; für jede fällige Art ohne gültige Quittierung (`erinnerung_<kind>_<block_id>` < heute oder fehlend) wird ein Overlay gerendert (bei zwei fälligen Arten ein Overlay mit beiden Punkten). Einstellung `review_overlay = aus` unterdrückt es (S8).
- Aufbau: Karte über abgedunkeltem Hintergrund (Design-System: Statusfarbe Warnung für den Rand, keine Serienfarbe), Überschrift „Blockbilanz fällig“ / „Zielklärung fällig“, ein Satz Grund (aus 5.1, z. B. „Block ‚Herbst 2026‘ endet am 12.10.“), Hinweis „Im Projekt-Chat erstellen – `get_handover` meldet die Fälligkeit“, Link zur Blockseite (S11). Formular `POST /erinnerung` mit `kind`, `block_id`, `bis` (Radio: „Morgen wieder erinnern“ vorgewählt, „Diese Woche nicht mehr“), CSRF. Erste Schaltfläche erhält `autofocus`; Seiteninhalt darunter `inert` (Attribut) und `aria-hidden`.
- Ohne JavaScript vollständig bedienbar (Formular). Kein Timer, keine automatische Wiederholung – erst der nächste Seitenaufbau nach Ablauf zeigt es erneut.
- Offline: Die gespeicherte Seite zeigt den Stand zum Zeitpunkt des Caches; die Quittierung läuft über `data-offline-form` in den Puffer. Bis zur Zustellung kann das Overlay offline erneut erscheinen (akzeptiert, O-02).
- Nicht auf S0, S1, S7 und `/erinnerung` selbst.

### 6.2 Karte in S2

Unter der Ampelkarte eine Karte „Block“: Name, Restlaufzeit („noch 3 Wochen“), Fälligkeiten in Kurzform (auch `revision_turnus`, E-07), Link zur Blockseite. Ohne aktiven Block: „Kein aktiver Block – Zielklärung im Projekt-Chat“.

### 6.3 Einstellungen (S8)

Bereich „Training“: „Erinnerung an Bilanz und Zielklärung“ (an/aus), „Vorlauf Blockbilanz“ (Tage, 0–28, Standard 7), „Vorlauf Zielklärung“ (Tage, 0–42, Standard 14). Änderung der Vorlauftage übertragen den Kalendertermin nicht neu (der Termin liegt am Blockende), aber Fälligkeit und Overlay reagieren sofort.

## 7. Teil D · Kalender (E-06, E-15, E-16)

- Neue Klasse `Training\Calendar\BlockEvent` (neben `DayEvent`): Ressource `training-block-<id>[-<n>].ics`, Fassung in `app_setting` `kalender_block_<id>` (gleiche Papierkorb-Regel wie Tagestermine); UID je Block und Fassung; `DTSTART`/`DTEND` als UTC aus 08:00–10:00 in `user.tz` (Sommer-/Winterzeit über PHP `DateTimeZone`); `SUMMARY` „Blockbilanz + Zielklärung: <Name>“, bei nur einem fehlenden Teil „Blockbilanz: …“ bzw. „Zielklärung: …“; `DESCRIPTION` mit dem Zweck („Rückblick auf den Block und Ziele für den nächsten – im Projekt-Chat mit get_handover beginnen“), Blockzeitraum und Link zur Blockseite; `URL` Blockseite; `CATEGORIES` `Planung`; `STATUS:CONFIRMED`; `VALARM` `ACTION:DISPLAY`, `TRIGGER:-P1D`.
- Datum: `end_date` des Blocks; Sicherheitsnetz `start_date + 112 Tage`, wenn `end_date` später liegt oder fehlt.
- Anlegen/Ändern in `upsert_block` (Status `geplant`/`aktiv`), Löschen in `write_block_review`, sobald für den Block die Bilanz und für einen Folgeblock die Zielklärung bestätigt sind (bzw. bei Status `abgeschlossen` und bestätigter Bilanz). `CalendarSync::syncRange` gleicht zusätzlich alle Blocktermine der Blöcke mit Status `geplant`/`aktiv` ab (E-16) und löscht verwaiste `training-block-*.ics` (Block gelöscht, abgeschlossen mit Bilanz).
- Fehler wie bei Tagesterminen: `fehler_kalender` in der Tool-Antwort, Audit `calendar_error` (entity `kalender_block`), Hinweis in S8; nichts bricht ab.

## 8. Teil E · Betriebsablauf und Trainerregeln

### 8.1 Hauptkonzept Abschnitt 6 (neue Fassung der Schritte 2, 8, 9)

2. **Wochenplanung**: Claude ruft **zuerst `get_handover`**, dann `get_week_overview` (Vorwoche), `get_wellness_trend`, `get_pain_history`, `get_athlete_profile`; meldet Fälligkeiten dem Athleten, bevor ein Wochenvorschlag entsteht.
8. **Rückkopplung**: nächster Chat → Schritt 2. **Revision** alle 3–4 Wochen oder bei Schmerzereignis/Ausfall: Ergebnis als `write_block_review(kind: revision)`.
9. **Blockwechsel** (ab Fälligkeit, spätestens am Blockende): (a) Bilanz im Chat erarbeiten – Claude ruft `get_handover(detail: true)` und `get_block_reviews`, schlägt Bewertung je Ziel vor, Athlet bestätigt → `write_block_review(kind: bilanz, status: bestaetigt)`; (b) Zielklärung – Fragen aus dem Schema 4.3 der Reihe nach, Entscheidungen mit verworfenen Alternativen protokollieren, Athlet bestätigt; (c) `upsert_block` für den Folgeblock, dann `write_block_review(kind: zielklaerung)` für diesen Block; (d) Athletenprofil abgleichen (`update_athlete_profile`, wenn sich Ausgangslage geändert hat); (e) erste Woche nach Schritt 2–4.

### 8.2 Trainerregeln (Kapitel „Übergabe, Revision, Bilanz und Zielklärung“, AP-07)

```yaml
- id: R-UEB-01
  regel: Jede Planungssitzung beginnt mit get_handover; Fälligkeiten werden dem Athleten vor dem Wochenvorschlag genannt.
  konfidenz: Verfahrensregel
- id: R-UEB-02
  regel: Eine Zielklärung wird nie ohne den Athleten geschrieben; jede Entscheidung nennt mindestens eine verworfene Alternative oder „keine“.
  konfidenz: Verfahrensregel
- id: R-UEB-03
  regel: Die Bilanz bewertet jedes Ziel der Zielklärung; „nicht bewertbar“ braucht einen Grund (fehlender Test, fehlende Daten).
  konfidenz: Verfahrensregel
- id: R-UEB-04
  regel: Änderungen an Trainerregeln entstehen nur aus einer Bilanz (annahmen_geaendert mit vorschlag_trainerregel) oder einem Schmerzereignis, nie aus einer Wochenplanung.
  konfidenz: Verfahrensregel
- id: R-UEB-05
  regel: Revisionen ändern Belastung, nicht Ziele; wer Ziele ändern will, macht eine Zielklärung (auch außerplanmäßig, E-03).
  quelle: L-P03/L-P04 (Belastungssteuerung), Einschätzung
- id: R-UEB-06
  regel: Wochenpläne über das Blockende hinaus gibt es nicht ohne bestätigte Zielklärung des Folgeblocks (Tool lehnt ab, E-19).
  konfidenz: Verfahrensregel
```

## 9. Unterpunkte

Reihenfolge: T1 → T2 → T3; T4 nach T2; T5 nach T2 (parallel zu T3/T4 möglich); T6 zuletzt.

### T1 · Datenmodell und Schemata
- Migration `block_review`; `ReviewRepository` (Fassungen, gültige Fassung je Schlüssel, Listen); `ReviewValidator` mit den drei Schemata in `server/schemas/`; `Kennzahlen` (4.4) mit Nullverhalten; `Faelligkeit` (5.1) als reine Funktion.
- Tests: Schemata mit gültigen/ungültigen Beispielen je Art; Fassungslogik (Entwurf über bestätigt, `reason` ab v2); Kennzahlen gegen eine Fixture-Woche (`beispielwoche.json` erweitert um Ausführungen und Schmerz); Fälligkeitsfälle F-01 bis F-10 (11.1).
- **Abnahme:** Testfälle 11.1 grün; Migration vor/zurück ohne Datenverlust.

### T2 · MCP-Tools
- `get_handover`, `get_block_reviews`, `write_block_review`; Erweiterungen `get_block`, `upsert_block` (ohne Kalender, kommt in T4), `write_week_plan` (E-19); Audit; Tool-Beschreibungen; Registrierung in `ToolRegistry`.
- Tests: H-01 bis H-08 (11.2); Budgetmessung `get_handover` mit gefüllter Fixture (zwei Blöcke, drei Revisionen); Schreibsperre.
- **Abnahme:** Aus dem Projekt-Chat: `get_handover` liefert Block, Zielklärung, Bilanz, Fälligkeiten in ≤ 8 000 Zeichen; `write_block_review` legt Fassung an und meldet Kennzahlen.

### T3 · Overlay, Karte, Einstellungen
- `POST /erinnerung`; Overlay-Teilvorlage in `layout-app` mit `inert`; Karte in S2; S8-Einträge; Offline-Verhalten (Formularpuffer); Mockup `docs/branding/mockups/s2-woche.html` um Overlay-Zustand (`?state=erinnerung`) und Blockkarte ergänzen.
- Tests: Rendering bei fällig/quittiert/aus; Quittierung setzt `app_setting`; Overlay nach Ablauf wieder da; nach bestätigter Fassung weg; Fokus auf erster Schaltfläche; Sichtprüfung 375 px.
- **Abnahme:** Auf dem Smartphone erscheint das Overlay bei fälliger Bilanz, verschwindet nach „Morgen wieder erinnern“ bis zum nächsten Tag und kommt dann wieder.

### T4 · Kalendertermin
- `BlockEvent`, Anbindung in `upsert_block`, `write_block_review`, `CalendarSync` (E-16), Papierkorb-Fassung `kalender_block_<id>`.
- Tests: K-B1 bis K-B6 (11.3) gegen den simulierten CalDAV-Server; Rauchtest gegen Radicale (Anlegen, Ändern des Enddatums, Löschen nach Bestätigung, Alarm-Zeitpunkt).
- **Abnahme:** Im Nextcloud-Kalender (Web und Handy) erscheint der Termin am Blockende 08:00–10:00 mit Erinnerung am Vortag 08:00.

### T5 · Blockseite S11 und S6
- `GET /block?id=` (Standard: aktiver Block): Kopf (Name, Zeitraum, Status, Fälligkeiten), Zielklärung (Abschnitte des Schemas als Karten, Entscheidungen als Tabelle mit verworfenen Alternativen), Revisionen (Zeitleiste), Bilanz (Tabelle Ziel/Soll/Ist/Bewertung, Kennzahlen kompakt), Fassungen je Datensatz mit `reason` und Datum aufklappbar (`<details>`); Liste früherer Blöcke. S6 bekommt den Abschnitt „Blöcke“ (Liste mit Link). Offline: S11 des aktiven Blocks in `PAGE_PATHS`.
- Mockup `docs/branding/mockups/s11-block.html` (Zustände: aktiv mit Zielklärung, mit Bilanz, ohne Reviews) durch die Code-Instanz aus vorhandenen Bausteinen (branding.md Abschnitt 8).
- Tests: Rendering mit Fixture; Fassungen; Leerzustände; 375 px ohne horizontales Scrollen.
- **Abnahme:** Sichtprüfung durch den Athleten auf dem Smartphone.

### T6 · Dokumentation, Regeln, Betriebsablauf
- Hauptkonzept: AP-15 mit Statusblock, Abschnitt 6 (8.1), Abschnitt 7 (Tabelle `block_review`, `app_setting`-Schlüssel), Abschnitt 8.3 (Tool-Liste, Budget), Abschnitt 10 (S11, S2-Karte, S8), Abschnitt 14 (Kapitel 8.2 mit Verweis auf AP-07), neue D-Nummern für E-01–E-09 (Abschnitt 10 dieses Dokuments); `datenmodell.md` (Tabelle, Schemata, ER-Diagramm); README (Endpunkte, Tools); CHANGELOG; Prüfprotokoll; `docs/regeln/trainerregeln.md` (Kapitel anlegen, falls AP-07 noch nicht vorliegt: als Vorabkapitel markieren).
- **Abnahme:** Dokumente konsistent (Tool-Namen, Feldnamen, Screens); Prüfprotokoll listet T1–T5.

## 10. Änderungen am Hauptkonzept (durch die Code-Instanz einzutragen)

- Neues Arbeitspaket **AP-15 Blockbilanz, Zielklärung und Übergabe** mit Verweis auf dieses Dokument; Abhängigkeiten: AP-05, AP-09 (Verlauf S6, Spiegel für Kennzahlen), AP-11 (Kalender, `app_setting`); AP-08 nutzt das Schema 4.3 für die erste Zielklärung.
- Entscheidungen E-01 (DB als Master), E-02/E-03 (drei Ebenen, Blockgrenze + 16 Wochen), E-05/E-06 (Overlay, Kalendertermin), E-08 (Übergabe durch Code), E-09 (Bestätigung im Chat) als D-Einträge mit Datum 2026-09-29 und Begründung „Entscheidung des Athleten“.
- Abschnitt 6 neu gefasst (8.1); Abschnitt 14 um das Kapitel 8.2 ergänzt.

## 11. Testfälle

### 11.1 Fälligkeit (T1)

| nr | lage (heute = 2026-10-01) | erwartung |
|---|---|---|
| F-01 | aktiver Block endet 2026-10-05, keine Bilanz, Vorlauf 7 | `bilanz` fällig, grund `blockende`, seit 2026-09-28 |
| F-02 | wie F-01, Bilanz-Entwurf v1 vorhanden | weiterhin fällig (nur bestätigt zählt) |
| F-03 | wie F-01, Bilanz bestätigt | nicht fällig |
| F-04 | Block abgeschlossen 2026-09-20 ohne Bilanz | `bilanz` fällig, grund `block_abgeschlossen_ohne_bilanz` |
| F-05 | aktiver Block endet 2026-10-12, Vorlauf Zielklärung 14, kein Folgeblock | `zielklaerung` fällig, grund `folgeblock_ohne_zielklaerung` |
| F-06 | wie F-05, Folgeblock `geplant` ab 2026-10-13 mit bestätigter Zielklärung | nicht fällig |
| F-07 | aktiver Block ohne bestätigte Zielklärung | `zielklaerung` fällig, grund `block_ohne_zielklaerung` |
| F-08 | aktiver Block seit 2026-05-01 (> 16 Wochen), Zielklärung vom 2026-05-01, end_date 2026-12-31 | `zielklaerung` fällig, grund `zielklaerung_aelter_16_wochen` |
| F-09 | aktiver Block, jüngster bestätigter Datensatz 2026-08-30 | `revision` fällig (28 Tage), grund `revision_turnus` |
| F-10 | kein aktiver Block | `zielklaerung` fällig mit `block_id: null`; keine Bilanz |

### 11.2 Handover und Schreiben (T2)

| nr | ablauf | erwartung |
|---|---|---|
| H-01 | `get_handover` mit Fixture (Block 1 abgeschlossen mit Bilanz, Block 2 aktiv mit Zielklärung, 2 Revisionen) | alle Abschnitte gefüllt; `bilanzen` enthält Block 1; ≤ 8 000 Zeichen |
| H-02 | `get_handover(detail: true)` | `content_json` der Zielklärung Block 2 und Bilanz Block 1 vollständig |
| H-03 | `write_block_review` Bilanz ohne `ziele[].bewertung` | Fehler mit Pfad; nichts geschrieben |
| H-04 | `write_block_review` Bilanz v1 bestätigt, dann v2 ohne `reason` | v2 abgelehnt („reason ab Fassung 2“) |
| H-05 | Bilanz v1 bestätigt, v2 Entwurf | `get_block_reviews` zeigt v1 als gültig, v2 als Entwurf; `faellig` ohne Bilanz |
| H-06 | Revision ohne `sequence` zweimal | sequence 1, dann 2; jeweils `kennzahlen_auto` für `period_*` |
| H-07 | Zielklärung für Block mit Status `abgeschlossen` | abgelehnt |
| H-08 | `write_week_plan` für Woche nach `end_date` ohne Folgeblock-Zielklärung | Fehler `blockwechsel_erforderlich` mit `faellig`; Woche innerhalb des Blocks weiterhin möglich |

### 11.3 Kalender (T4)

| nr | ablauf | erwartung |
|---|---|---|
| K-B1 | `upsert_block` aktiv, end_date 2026-12-14, tz Europe/Berlin | `training-block-<id>.ics` mit DTSTART 2026-12-14T07:00:00Z, DTEND 09:00:00Z, VALARM `-P1D` |
| K-B2 | wie K-B1 im Sommer (end_date 2026-07-05) | DTSTART 06:00:00Z (08:00 MESZ = UTC+2; korrigiert in T4, vorher 05:00Z) |
| K-B3 | end_date per `upsert_block` verschoben | Termin aktualisiert (gleiche Ressource, `SEQUENCE` +1) |
| K-B4 | Bilanz bestätigt, Zielklärung Folgeblock fehlt | Termin bleibt, SUMMARY „Zielklärung: …“ |
| K-B5 | Bilanz und Zielklärung Folgeblock bestätigt | Termin gelöscht; Fassung `kalender_block_<id>` erhöht |
| K-B6 | Block ohne end_date bzw. > 16 Wochen | Termin auf `start_date + 112 Tage` |

### 11.4 Overlay (T3)

| nr | ablauf | erwartung |
|---|---|---|
| U-01 | Bilanz fällig, keine Quittierung | Overlay auf S2, S3, S8; nicht auf S1 |
| U-02 | „Morgen wieder erinnern“ | `erinnerung_bilanz_<id>` = morgen; Overlay weg; Testuhr +1 Tag → Overlay wieder |
| U-03 | „Diese Woche nicht mehr“ | 7 Tage Ruhe |
| U-04 | Bilanz bestätigt während Quittierung läuft | Overlay weg, Einstellung gelöscht |
| U-05 | `review_overlay = aus` | kein Overlay, Karte in S2 zeigt Fälligkeit weiterhin |
| U-06 | zwei Fälligkeiten | ein Overlay mit zwei Punkten, Quittierung je Art |

## 12. Offene Punkte

| id | punkt | stand |
|---|---|---|
| O-01 | Bearbeiten und Bestätigen von Reviews auf der Webseite (statt nur im Chat) | zurückgestellt; erst nach Praxiserfahrung |
| O-02 | Overlay offline: gespeicherte Seite kann ein bereits quittiertes Overlay erneut zeigen, bis der Puffer zugestellt ist | umgesetzt in T3: der Puffer zeigt „Erinnerung quittiert“ als wartende Eingabe, und die gespeicherte Seite blendet das Overlay aus, solange die Quittierung wartet; Prüfung auf dem Smartphone offen |
| O-03 | Spiegel nach `docs/plaene/` (Markdown-Export einer Zielklärung/Bilanz) für das Projekt-Wissen | offen; Vorschlag: Export-Format `markdown` in `get_block_reviews`, Athlet legt die Datei ab |
| O-04 | Sollen frühere Wochentexte (`focus`) im Handover erscheinen (letzte 4 Wochen, je eine Zeile)? | entschieden 2026-09-29: ja → E-21 |
| O-05 | Uhrzeit des Kalendertermins (08:00–10:00) und Erinnerung (Vortag 08:00) als Einstellung in S8 | entschieden 2026-09-29: sofort einstellbar → E-22 |

## 13. Status je Unterpunkt (von der Code-Instanz zu pflegen)

```yaml
T1:
  status: erledigt
  datum: 2026-09-29
  ergebnis: >-
    Migration 0024 block_review (CHECK für sequence und Zeitraum, FK RESTRICT); Schemata server/schemas/review-<kind>.json;
    Training\Review\ReviewValidator, Training\Data\ReviewRepository (Fassungen, gültige Fassung, Entwurf zusätzlich,
    nächste Revisionsnummer), Training\Review\Kennzahlen (4.4, nur Spiegel, fehlende Quellen null; Kurzform für
    get_handover), Training\Review\Faelligkeit (reine Funktion + Laden aus der DB, Satz je Fälligkeit);
    SettingsRepository um Vorlauftage, Overlay, Quittierung und Blocktermin (E-22); JSON-Export kennzahlen_auto.
    Code-Stand 0.26.0, Schema 24.
  tests: >-
    Unit FaelligkeitTest (F-01 bis F-10 plus Vorlauf, ältere abgeschlossene Blöcke, Folgeblock ohne Zielklärung,
    Texte), Unit ReviewValidatorTest (gültige Beispiele je Art aus tests/fixtures/review-beispiele.json, Fehler mit Pfad,
    Längen, Listen, Enum, Zeitraum); Integration ReviewDataTest (Constraints, Löschschutz, Fassungslogik, Entwurf zählt
    nicht, Kennzahlen gegen die erweiterte Beispielwoche, Nullverhalten, Migration zurück/vor ohne Datenverlust);
    gesamte Suite 276 Tests grün gegen MariaDB 10.11.
  abnahme: automatisiert (Testfälle 11.1 grün); CI gegen MySQL 8.4 mit dem Pull Request
  probleme_loesungen:
    - was: >-
        E-13 „sofort bei abgeschlossenem Block ohne bestätigte Bilanz“ würde für jeden früheren Block ohne Bilanz (auch
        Blöcke vor AP-15) dauerhaft erinnern
      loesung: nur der zuletzt beendete abgeschlossene Block meldet eine fehlende Bilanz (und nur, wenn er vor dem aktiven Block begann)
    - was: 5.1 lässt offen, welcher Block bei folgeblock_ohne_zielklaerung und ohne aktiven Block genannt wird
      loesung: >-
        folgeblock_ohne_zielklaerung trägt block_id des aktiven Blocks (stabil für Quittierung und Kalender) und
        folgeblock_id, wenn ein Folgeblock ohne Zielklärung existiert; ohne aktiven Block der früheste geplante Block
        ohne Zielklärung, sonst block_id null (F-10). Je Aufruf höchstens eine Zielklärung (Vorrang block_ohne → folgeblock → 16 Wochen)
    - was: „älter als 28 Tage / 16 Wochen“ ist an der Grenze mehrdeutig
      loesung: fällig ab dem 28. bzw. 112. Tag (seit = Referenzdatum + 28/112); Revision ohne Datensatz ab Blockbeginn gerechnet
    - was: 4.4 nennt für plan_erfuellung „verschoben“ nur im Kommentar; E-12 nennt Kennzahlen nur für die Bilanz, 4.1/5.2 auch für die Revision
      loesung: verschoben aufgenommen; Kennzahlen für Bilanz und Revision (wie H-06); zusätzlich Feld zeitraum im Kennzahlenblock
    - was: Schmerztrend in 4.4 ohne Rechenregel
      loesung: Mittel der zweiten gegen die erste Hälfte des Zeitraums (± 0,5); nur in einer Hälfte gemeldet = steigend bzw. fallend
    - was: Rückweg-Tests (ExercisePagesTest, MorningCheckinTest) gingen von 0023 als letzter Migration aus
      loesung: um den Rückweg von 0024 ergänzt
T2:
  status: erledigt
  datum: 2026-09-29
  ergebnis: >-
    Training\Mcp\ReviewTools mit get_handover (kompakt mit Kürzungsstufen bis ≤ 8 000 Zeichen, detail mit Volltexten,
    wochen_kurz nach E-21, offene Entwürfe), get_block_reviews (gültige Fassungen mit Inhalt und Kennzahlen, Fassungsliste)
    und write_block_review (Prüfung, Fassung, Kennzahlen, Audit review_write, Quittierungen löschen); get_block um Reviews
    und Fälligkeiten, upsert_block um faellig und zielklaerung_fehlt, write_week_plan um die Sperre
    blockwechsel_erforderlich (E-19) und faellig; Tool-Beschreibungen mit den Pflichten aus Abschnitt 8. Code-Stand 0.27.0.
  tests: >-
    Integration ReviewToolsTest (H-01 bis H-08, Budget mit gefüllter Fixture, get_block, Schreibsperre); McpToolsTest
    (Tool-Liste); gesamte Suite 285 Tests grün gegen MariaDB 10.11.
  abnahme: automatisiert; Abnahme aus dem Projekt-Chat nach Deployment offen
  probleme_loesungen:
    - was: Die Übergabe mit vollen Listen an den Längengrenzen der Schemata ergab gut 10 000 Zeichen
      loesung: >-
        Kürzungsstufen (Zeilenlänge 220 → 70 Zeichen, Listen 12 → 4 Einträge); die erste Stufe, die ins Budget passt,
        gilt, dann Feld gekuerzt mit Verweis auf get_block_reviews; die Volltexte mit detail bleiben ungekürzt
    - was: 5.2 legt für Bilanz ohne Zeitraum den Blockzeitraum fest, für die Revision nichts
      loesung: Revision ohne Zeitraum = 28 Tage bis review_date (nicht vor Blockbeginn); period_start/period_end nur gemeinsam
    - was: Die Zielklärung des Folgeblocks ist bei Quittierungen unter dem aktiven Block abgelegt (Schlüssel erinnerung_<kind>_<block_id>)
      loesung: eine bestätigte Fassung löscht alle Quittierungen dieser Art (erinnerung_<kind>_*); das Overlay richtet sich ohnehin nach der Fälligkeit
    - was: "upsert_block soll laut 5.2 faellig mit „Zielklärung fehlt noch“ melden; nach 5.1 ist ein geplanter Folgeblock vor dem Vorlauf aber nicht fällig"
      loesung: faellig des Blocks (5.1) plus eigenes Feld zielklaerung_fehlt mit Hinweis, solange der Block keine bestätigte Zielklärung hat
    - was: write_week_plan meldete für Wochen nach dem Blockende zuerst „Kein Trainingsblock umfasst diese Woche“
      loesung: Prüfung E-19 vor der Blocksuche
    - was: Das MCP-SDK prüft Eingaben nicht gegen das inputSchema
      loesung: content im inputSchema als anyOf der drei Schemata (Hilfe für Claude), Prüfung serverseitig im ReviewValidator
T3:
  status: erledigt
  datum: 2026-09-29
  ergebnis: >-
    Training\Controller\ReminderController (POST /erinnerung, Fälligkeit ohne gültige Quittierung), Overlay
    templates/_review_overlay.php in layout-app (Header, Navigation und Inhalt inert + aria-hidden, autofocus, zwei
    Formularknöpfe), Karte „Block“ in S2 (Restlaufzeit, alle Fälligkeiten, Link /block), S8-Unterseite
    „Blockbilanz und Zielklärung“ (Overlay, Vorlauftage, Blocktermin nach E-22), Service Worker puffert /erinnerung,
    offline.js blendet das Overlay bei wartender Quittierung aus (O-02); Mockup s2-woche.html (?state=erinnerung,
    Blockkarte). Code-Stand 0.28.0.
  tests: >-
    Integration ReminderTest (U-01 bis U-06, ohne aktiven Block, Validierung, Offline-Puffer 204, Rücksprungziel,
    Schreibsperre, S8-Unterseite); Browser tests/e2e/erinnerung.e2e.cjs (Fokus, inert, 375 px, Wegklicken, Karte);
    gesamte Suite 293 Tests grün, Browser-Tests 14 + 4 + 2 grün.
  abnahme: automatisiert; Sichtprüfung durch den Athleten auf dem Smartphone offen
  probleme_loesungen:
    - was: >-
        6.1 „erinnerung_<kind>_<block_id> < heute“ passt nicht zu U-02 (Wert = morgen, am nächsten Tag wieder da)
      loesung: Der gespeicherte Wert ist der Tag, ab dem das Overlay wieder erscheint (Anzeige, wenn Wert ≤ heute); morgen = heute + 1, Woche = heute + 7
    - was: 6.1 nennt Radio + Formular, E-14 zwei Formularknöpfe
      loesung: zwei Submit-Knöpfe (name bis, morgen/woche), erster mit autofocus; ein Formular für alle Punkte, gespeichert je Art (U-06)
    - was: ohne aktiven Block hat die fällige Zielklärung keinen Block
      loesung: Schlüssel erinnerung_zielklaerung_0
    - was: "Das Attribut data-offline-form am Overlay-Formular hätte offline.js (Datum der Check-in-Seite auf heute) auf das falsche Formular gelenkt"
      loesung: kein data-offline-form; gepuffert wird über FORM_PATHS im Service Worker (Pfad /erinnerung)
    - was: Die Browser-Tests der geführten Einheit und des Katalogs scheiterten am Overlay (Testblock ohne Zielklärung)
      loesung: Testvorbereitung schreibt eine bestätigte Zielklärung zum Testblock (entspricht dem Ablauf ab AP-15)
    - was: Das Overlay erscheint auch in der geführten Einheit (S9), weil 6.1 nur S0, S1, S7 ausnimmt
      loesung: wie beauftragt umgesetzt; Quittieren ist ein Klick; bei Bedarf S9 ausnehmen (Rückmeldung des Athleten)
    - was: Bei Schreibsperre wäre das Overlay nicht quittierbar
      loesung: kein Overlay, solange Code- und Datenbankstand abweichen
T4:
  status: erledigt
  datum: 2026-09-29
  ergebnis: >-
    Training\Calendar\BlockEvent (Ressource, Datum mit Sicherheitsnetz 112 Tage, UTC-Zeiten aus Zeitzone und E-22,
    Titel nach fehlendem Teil, VALARM, Faltung); CalendarSync::pushBlocks (upsert_block, write_block_review,
    Einstellungen) und Abgleich der Blocktermine in syncRange (alle Blöcke geplant/aktiv plus zuletzt abgeschlossener
    Block, verwaiste Termine entfernen, Fassung kalender_block_<id>); App::calendar mit Zeitzone des Athleten.
    Code-Stand 0.29.0.
  tests: >-
    Unit BlockEventTest (K-B1, K-B2, K-B6, Einstellungen, Trigger), Integration BlockCalendarTest (K-B1 bis K-B6,
    E-22, Papierkorb, verwaiste Termine, Fehler), CalendarTest angepasst; Rauchtest gegen Radicale 3.8.1; gesamte Suite
    300 Tests grün, Browser-Tests grün.
  abnahme: automatisiert; Sichtprüfung im Nextcloud-Kalender durch den Athleten offen
  probleme_loesungen:
    - was: K-B2 erwartet für 08:00 MESZ den Beginn 05:00Z; MESZ ist UTC+2, richtig ist 06:00Z
      loesung: Test auf 06:00Z; Konzept 11.3 korrigiert
    - was: K-B6 „Block ohne end_date“ gibt es nicht (training_block.end_date ist NOT NULL)
      loesung: nur der Fall „Blockende später als 16 Wochen“ geprüft
    - was: Der Termin fragt nach der Zielklärung „des Folgeblocks“; welcher Block Folgeblock ist, lässt E-15 offen
      loesung: Zielklärung gilt als vorhanden, wenn ein anderer Block (geplant/aktiv) mit späterem Beginn eine bestätigte Zielklärung hat
    - was: Die bestehenden Kalendertests zählten alle Termine bzw. PUT-Anfragen und gingen von Tagesterminen allein aus
      loesung: Ergebnis des Abgleichs um blocktermine ergänzt (Tageszählung unverändert); Tests auf Tagestermine eingegrenzt
    - was: Der simulierte CalDAV-Server erkannte im REPORT nur ganztägige Termine
      loesung: FakeCalDav wertet auch DTSTART mit Uhrzeit aus
    - was: Das Suchfenster für verwaiste Blocktermine ist ohne Zeitraum nicht begrenzt
      loesung: REPORT von heute − 400 bis heute + 800 Tage (deckt Blöcke bis gut zwei Jahre voraus ab)
T5:
  status: offen
  datum: null
  ergebnis: null
  tests: null
  abnahme: null
  probleme_loesungen: []
T6:
  status: offen
  datum: null
  ergebnis: null
  tests: null
  abnahme: null
  probleme_loesungen: []
```

## 14. Änderungsprotokoll dieses Dokuments

| datum | wer | was |
|---|---|---|
| 2026-09-29 | Fable | Erstfassung nach Klärung E-01 bis E-09 mit dem Athleten |
