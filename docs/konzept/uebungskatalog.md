# Auftrag: Übungskatalog (AP-16)

Stand: 2026-09-29 · Auftraggeber: Athlet · Konzept: Fable (Projekt-Chat) · Umsetzung: Code-Instanz (Claude Code)

Dieses Dokument ist der Auftrag an die Code-Instanz und wird von ihr fortgeschrieben (Abschnitt 13). Versionsnummern der Anwendung legt die Code-Instanz fest. Nummern der Entscheidungen (E-nn) und offenen Punkte (O-nn) gelten nur in diesem Dokument; im Hauptkonzept wird das Paket als AP-16 geführt, die Kernentscheidungen erhalten dort die nächsten freien D-Nummern (Abschnitt 10). Unabhängig von AP-15 (`blockbilanz.md`).

## 1. Aufgabe

Übungen in Einheiten werden heute als Freitext geplant (`plan_json.exercises[].name`, Kletterblöcke `blocks[].kind` mit `target`/`notes`). Ausführung, Hinweise und Fehlerquellen müssten in jeder Einheit wiederholt werden oder fehlen. Ziel:

1. Eine **Datenbank der verwendeten Übungen** im Tool: je Übung Ausrüstung, Ausführung, Achtungspunkte, Fehlerquellen, Vorsicht/Gegenanzeigen, Progression/Regression, 1–2 beschreibende Links, 1–2 Videos (eingebettet), Quellenbezug und Konfidenz, mit Fassungen.
2. **Verlinkung** statt Beschreibung: `plan_json` erhält ein optionales `exercise_id`; die Webseite (S3, S9) verlinkt auf die Übungsseite; die Einheit selbst beschreibt nur Dosierung.
3. **Pflicht für planende Instanzen:** vor der Planung prüfen, ob die Übung oder eine ähnliche schon existiert (`find_exercise`); neue Übungen legt die Instanz selbst an (`upsert_exercise`), nach Bestätigung des Wochenplans und sichtbar im Chat.
4. **Weiche Regel** in `write_week_plan`: Übungen ohne `exercise_id` werden angenommen, aber als Warnung gemeldet; ein ungültiges `exercise_id` ist ein Fehler.

Nicht im Umfang: Bearbeiten auf der Webseite (O-01); Massenimport eines Katalogs aus der Literatur (E-13); Videos als eigene Dateien hosten (O-02); Übungen für Ausdauer (Workout-Text bleibt, wie bisher).

## 2. Ausgangslage (Bezug auf das Hauptkonzept)

| bereich | stand | betroffen |
|---|---|---|
| `plan_json` | Schema 7.1: `kraft_oder_haltung.exercises[]` mit `name, sets, reps, load, tempo, rest_s, notes` (auch `mobilitaet`, D-39); `klettern.blocks[]` mit `kind` (hangboard, campus, bouldern_volumen, bouldern_limit, ausdauer_route, technik, zugkraft, antagonisten), Hangboard-Feldern, `target`, `notes`; JSON-Schemata in `server/schemas/` mit `additionalProperties: false`; `PlanValidator` | Teil A |
| Schreibtools | `write_week_plan` (Prüfung aller Einheiten vor dem Schreiben, alles oder nichts; Antwort mit Fehlern je Session), `update_session` | Teil B |
| Lese-Tools | `get_week_overview`, `get_session_detail`; Antwortbudget 8.3 | Teil B |
| Wissensbasis | AP-06: Abschnitt `uebungskatalog_calisthenics` in `docs/wissen/t2-kraft-haltung.md` aus L-T2-04 (konfidenz niedrig, D-29); Kletterprogression nach L-T3-02/03; Reha-Regeln Block R (Patellasehne, Sprunggelenk) | Quellenbezug (Teil A), E-13 |
| Webseite | S3 Einheit (Plan mit Soll je Übung, Ist-Eingabe), S9 geführt (Phase-Karte mit Übungsname, „Als Nächstes“, `notiz` aus `plan_json`), Kalenderbeschreibung mit Übungsnamen (AP-11) | Teil C |
| Offline | `PAGE_PATHS`, `WeekController::prefetch` (heutige/morgige Einheiten), `PRECACHE` | Teil C |
| Sicherheit | CSP der Webseite (nur eigene Quellen); kein `frame-src` | Teil C (Einbettung) |
| Cron | `/cron/intervals-sync` stündlich, Secret als Query-Parameter (V-10) | Teil D (Linkprüfung) |
| Fassungen | Muster `athlete_profile` (D-48) | Teil A |

## 3. Geklärte Entscheidungen

Mit dem Athleten am 2026-09-29 geklärt (E-01 bis E-06). E-07 bis E-16 sind Vorschläge von Fable; mit der Bestätigung des Konzepts durch den Athleten am 2026-09-29 gelten sie. E-17 bis E-20 hat die Code-Instanz beim Abgleich mit dem Code vorgeschlagen, der Athlet hat sie mit dem Konzept bestätigt (2026-09-29).

| id | entscheidung | begruendung |
|---|---|---|
| E-01 | **Katalog in der Datenbank, Verlinkung aus `plan_json`** über ein optionales `exercise_id` (Slug) in `exercises[]` und in `blocks[]`. | Wunsch des Athleten; Beschreibung nur an einer Stelle. |
| E-02 | **Die planende Instanz legt Übungen selbst an** (`upsert_exercise`), erst nach Bestätigung des Wochenplans, und zeigt den Eintrag im Chat in Kurzform. Ähnlichkeitsprüfung mit `find_exercise` ist Pflicht vor jeder Planung. | Konsistent mit `upsert_block` (D-40) und `update_athlete_profile`; keine Handarbeit in der Datenbank. |
| E-03 | **Weiche Regel:** `write_week_plan` nimmt Übungen ohne `exercise_id` an und meldet sie als `warnungen`; ein unbekanntes `exercise_id` ist ein Fehler (Tippfehler). Bestehende Einheiten bleiben gültig. | Entscheidung des Athleten (Freitext bleibt möglich, Katalog wird trotzdem eingefordert). |
| E-04 | **Videos werden eingebettet** (YouTube über `youtube-nocookie.com`, Vimeo über `player.vimeo.com`), nur diese beiden Hosts; andere Videolinks bleiben Links. Offline zeigt die Übungsseite statt des Videos einen Platzhalter mit Link. | Wunsch des Athleten; nur er hat Zugriff, Datenschutzabwägung akzeptiert. Einbettung braucht `frame-src` in der CSP. |
| E-05 | **Kletterblöcke:** Katalogeinträge nur für `hangboard`, `campus`, `zugkraft`, `antagonisten` (Übungen mit Technik, Fehlerquellen und Verletzungsrisiko). `bouldern_volumen`, `bouldern_limit`, `ausdauer_route`, `technik` sind Einheitenformate und bleiben ohne `exercise_id` (Validator lehnt `exercise_id` dort ab). | Empfehlung Fable, vom Athleten angenommen; am Hangboard ist die Ausführung die Hauptprävention. |
| E-06 | **Quellenbezug Pflicht:** jede Übung trägt `konfidenz` (hoch, mittel, niedrig, einschaetzung) und `quellen` (Literatur-IDs wie L-T2-04, Regel-IDs oder „Einschätzung“), wie die Trainerregeln (Abschnitt 14). | Sonst entsteht ein Katalog ohne Begründungsspur. Entscheidung des Athleten. |
| E-07 | **Datenmodell:** Tabelle `exercise` mit suchbaren Spalten (Slug, Name, Kategorie, Bewegungsmuster, Ausrüstung, Status) und `content_json` (Schema 4.3); Aliase in `exercise_alias`; Fassungen in `exercise_version` (Schnappschuss je Änderung mit `reason`). | Suchbare Spalten für `find_exercise`, Inhalt schemageprüft, Historie ohne Aufblähen der Haupttabelle. |
| E-08 | **Slug** als Kennung in `plan_json`: ASCII, Kleinbuchstaben, `a–z0–9-`, 3–60 Zeichen, eindeutig, unveränderlich nach Anlage (Umbenennen ändert `name`, nicht `slug`). | Lesbar im Plan und in Tool-Antworten; stabil gegen Umbenennung. |
| E-09 | **Duplikatschutz im Tool:** `upsert_exercise` lehnt einen neuen Eintrag ab, wenn `name` oder ein Alias (normalisiert: Kleinschreibung, Umlaute ae/oe/ue/ss, Bindestriche/Leerzeichen gleich) mit einer bestehenden Übung oder deren Alias übereinstimmt; Antwort nennt den Treffer. Varianten sind über `variant_of` abzubilden. | Verhindert die häufigsten Dubletten mechanisch; die inhaltliche Ähnlichkeit bleibt Aufgabe der KI (`find_exercise` liefert Kandidaten). |
| E-10 | **Linkprüfung durch den Server:** beim Anlegen/Ändern ruft der Server jede URL ab (HTTP GET, max. 64 kB, Timeout 5 s, https Pflicht) und setzt `geprueft_am` und `status` (`ok`, `defekt`, `ungeprueft` bei Netzfehler). Ein `defekt`er Link ist kein Fehler des Tools, sondern setzt die Übung auf `links_pruefen`. Wöchentliche Nachprüfung im Cron (Teil D). | Der Server hat Netz (Intervals.icu, CalDAV); die KI muss nichts selbst abrufen. Videoplattformen antworten auf GET auch ohne Wiedergabe. |
| E-11 | **Höchstzahl 2 Text- und 2 Videolinks** je Übung (Schemaprüfung). | Vorgabe des Athleten (1–2). |
| E-12 | **`name` bleibt Pflicht in `plan_json`** auch mit `exercise_id`; der Validator meldet als Warnung, wenn `name` nicht mit `exercise.name` oder einem Alias übereinstimmt. | Offline-Anzeige und Kalenderbeschreibung brauchen den Namen ohne Nachschlagen; alte Pläne bleiben gültig. |
| E-13 | **Kein Massenimport.** Der Katalog wächst mit den tatsächlich geplanten Übungen. Die Wissenskarten (z. B. `uebungskatalog_calisthenics`) sind Quellen für Einträge, nicht Einträge. | Wunsch des Athleten („Datenbank der verwendeten Übungen“); hält den Katalog klein und geprüft. |
| E-14 | **Übungsseite S10** (`/uebung?id=<slug>`) mit allen Abschnitten, eingebetteten Videos, Links, Quellen, Fassungen; Liste S10a (`/uebungen`) mit Filter nach Kategorie und Suchfeld. Verlinkung aus S3 (Übungsname als Link mit Icon), S9 (Phase-Karte: Link „Ausführung“) und der Kalenderbeschreibung (Link je Übung, gekürzt). | Lesen ohne Umweg; Bearbeiten über den Chat (O-01). |
| E-15 | **Offline:** Übungsseiten aller Einheiten der laufenden und nächsten Woche werden vorgeladen (`prefetch`, wie Einheiten); Video-`iframe` bekommt `loading="lazy"` und ohne Netz den Platzhalter (E-04). | Halle und Gebirge ohne Netz (D-45). |
| E-16 | **Budget:** `find_exercise` ≤ 1 000 Tokens (kompakte Treffer), `get_exercise` ≤ 1 500 Tokens je Übung, `list_exercises` ≤ 2 000 Tokens (nur Slug, Name, Kategorie, Bewegungsmuster). | Regel 8.3. |
| E-17 | **Referrer für eingebettete Videos:** Der Video-`iframe` bekommt `referrerpolicy="strict-origin-when-cross-origin"`; die Seite behält `Referrer-Policy: same-origin`. | Die App sendet `same-origin` (`Response.php`); YouTube verweigert eingebettete Videos ohne Referrer (Fehler 153). Die Plattform erfährt nur die Domain der App, keinen Pfad. |
| E-18 | **Videoprüfung über oEmbed:** Links mit Embed-Adresse werden über `https://www.youtube.com/oembed?url=…&format=json` bzw. `https://vimeo.com/api/oembed.json?url=…` geprüft (200 = ok, 401/403/404 = defekt); Textlinks per GET (E-10). | Watch-Seiten antworten auch bei gelöschten oder privaten Videos mit 200; oEmbed erkennt sie. |
| E-19 | **Schutz der Linkprüfung:** nur https, Port 443, kein Ziel im internen Netz (localhost, private und reservierte IP-Bereiche, geprüft für jede aufgelöste Adresse), höchstens 3 Weiterleitungen (jede erneut geprüft), Antwort ≤ 64 kB, Timeout 5 s je Link; die Links einer Übung werden parallel geprüft. | Der Server ruft Adressen ab, die die KI angibt (Schutz vor Abrufen ins interne Netz, SSRF); vier Links nacheinander könnten `upsert_exercise` bis 20 s blockieren. |
| E-20 | **Spalte `name_norm`** (eindeutig) in `exercise` neben `alias_norm`; beide 240 Zeichen, weil die Normalisierung Umlaute verdoppelt. | Duplikatschutz (E-09) und Suche per Datenbank statt nur in der Anwendung. |

## 4. Teil A · Datenmodell

### 4.1 Tabellen

```yaml
exercise:
  id:           INT PK
  slug:         VARCHAR(60) UNIQUE               # E-08
  name:         VARCHAR(120) NOT NULL
  name_norm:    VARCHAR(240) UNIQUE              # normalisiert (E-09, E-20); Name gegen Alias einer anderen Übung prüft die Anwendung
  category:     ENUM(kraft, haltung, mobilitaet, hangboard, campus, zugkraft, antagonisten)
  pattern:      ENUM(druecken_horizontal, druecken_vertikal, ziehen_horizontal, ziehen_vertikal,
                     knie_dominant, huefte_dominant, rumpf, schulter, bws_haltung, unterarm_finger,
                     sprunggelenk_fuss, mobilitaet, sonstiges)
  equipment_json: JSON NOT NULL                  # Liste aus Vokabular 4.2
  variant_of:   INT NULL FK exercise             # Progressionsleiter (E-09)
  difficulty:   TINYINT NULL                     # 1–5, informativ
  status:       ENUM(aktiv, links_pruefen, archiviert)   # archiviert: nicht mehr planen, bleibt lesbar
  konfidenz:    ENUM(hoch, mittel, niedrig, einschaetzung)
  content_json: JSON NOT NULL                    # Schema 4.3
  version:      SMALLINT NOT NULL DEFAULT 1
  created_by:   ENUM(mcp, web)
  created_at, updated_at: DATETIME (UTC)

exercise_alias:
  exercise_id:  INT FK exercise ON DELETE CASCADE
  alias:        VARCHAR(120)
  alias_norm:   VARCHAR(240)                     # normalisiert (E-09, E-20), UNIQUE über alle Übungen
  PRIMARY KEY (exercise_id, alias_norm)

exercise_version:
  id:           INT PK
  exercise_id:  INT FK exercise ON DELETE CASCADE
  version:      SMALLINT
  snapshot_json: JSON                            # alle Spalten von exercise + Aliase zum Zeitpunkt vor der Änderung
  reason:       VARCHAR(255) NOT NULL
  created_by:   ENUM(mcp, web)
  created_at:   DATETIME
  UNIQUE (exercise_id, version)
```

Löschen: keine Löschfunktion; stattdessen `status: archiviert`. Eine Übung, die in einem `plan_json` referenziert ist, darf nicht archiviert werden, solange die Einheit `geplant` ist (Prüfung im Tool).

Backup/JSON-Export: alle drei Tabellen gehören zu den fachlichen Tabellen.

### 4.2 Vokabular `equipment`

`koerpergewicht, band, kettlebell, kurzhantel, langhantel, klimmzugstange, ringe, hangboard, campusboard, box, matte, faszienrolle, stab, gymnastikball, gewichtsweste, sonstiges` – als JSON-Schema-Enum, Erweiterung braucht eine Schemaänderung (bewusst, wie ENUMs in D-38).

### 4.3 `content_json` (Schema `server/schemas/exercise.json`, Draft 2020-12, `additionalProperties: false`)

```yaml
kurz:          string             # ein Satz, was die Übung ist (≤ 200), erscheint in find_exercise
ziel:          string             # Zweck / Zielmuskulatur in Worten (≤ 300)
muskeln:       [string]           # freie Bezeichner, ≤ 8
voraussetzung: string|null        # Ausrüstung im Detail, Aufbau, Platz (≤ 500)
ausfuehrung:   [string]           # Schritte, 2–12 Einträge à ≤ 300 Zeichen
achten:        [string]           # worauf achten, ≤ 8 à ≤ 200
fehler:        [string]           # typische Fehlerquellen, ≤ 8 à ≤ 200
vorsicht:      [string]           # Gegenanzeigen, Schmerzbezug (z. B. Patellasehne, Sprunggelenk), ≤ 6 à ≤ 300
progression:   string|null        # wie schwerer machen (Verweis auf variant_of möglich)
regression:    string|null        # wie leichter machen
dosierung_hinweis: string|null    # Standardbereiche, keine Vorgabe je Einheit (≤ 300)
links:
  - url: string (https)
    titel: string
    art: enum [text, video]
    embed: string|null            # vom Server gesetzt: Embed-URL für youtube-nocookie/vimeo, sonst null (E-04)
    geprueft_am: date|null        # vom Server gesetzt (E-10)
    status: enum [ok, defekt, ungeprueft]   # vom Server gesetzt
  # max. 2 mit art text, max. 2 mit art video (E-11)
quellen:       [string]           # L-…, R-…, D-… oder "Einschätzung"; mind. 1 (E-06)
notizen:       string|null        # interne Bemerkungen (≤ 500)
```

Server-gesetzte Felder (`embed`, `geprueft_am`, `status`) werden bei Eingabe ignoriert und überschrieben.

Embed-Regeln (E-04): `https://www.youtube.com/watch?v=<id>`, `https://youtu.be/<id>`, `https://www.youtube.com/shorts/<id>` → `https://www.youtube-nocookie.com/embed/<id>`; `https://vimeo.com/<id>` → `https://player.vimeo.com/video/<id>`; alles andere `embed: null`.

### 4.4 `plan_json` (Änderung an 7.1 und den Schemata)

```yaml
kraft_oder_haltung.exercises[]:
  exercise_id: string|null        # Slug (E-08); null/fehlend = Freitext (E-03)
  # übrige Felder unverändert; name bleibt Pflicht (E-12)
klettern.blocks[]:
  exercise_id: string|null        # nur bei kind in [hangboard, campus, zugkraft, antagonisten] (E-05), sonst Schemafehler
```

`actual_json` unverändert (kein `exercise_id`; Zuordnung über die Position).

### 4.5 Migration

Zwei Migrationen (Tabellen; Schemaänderung `plan_json` ohne Datenänderung). Rückweg: Tabellen entfernen, Schemata zurück. Bestehende Pläne bleiben gültig (kein `exercise_id`).

## 5. Teil B · MCP-Schnittstelle und Validator

### 5.1 Tools

| tool | scope | eingabe | ausgabe / verhalten |
|---|---|---|---|
| `find_exercise` | read | `query: string` (≥ 2 Zeichen), `category: enum|null`, `pattern: enum|null`, `equipment: string|null`, `limit: int = 10` | Treffer nach Rang: exakter Slug/Name/Alias → Teilstring in Name/Alias (normalisiert) → gleiche `pattern` und überlappende `equipment` (als `aehnlich: true` markiert). Je Treffer `slug, name, category, pattern, equipment, status, konfidenz, kurz, aehnlich, variant_of`. Leer → `treffer: []` und `hinweis: "keine Übung gefunden – upsert_exercise anlegen"`. Archivierte nur mit `include_archived`. Budget E-16. |
| `get_exercise` | read | `slug: string` oder `id: int`, `fassungen: bool = false` | Vollständiger Datensatz (Spalten + `content_json` + Aliase + Varianten: Eltern und Kinder als Kurzliste); mit `fassungen` Liste `version, reason, created_at` (ohne Schnappschüsse; ein Schnappschuss per `version: n`). |
| `list_exercises` | read | `category: enum|null`, `status: enum|null` | Kompaktliste `slug, name, category, pattern` für den Gesamtüberblick. Budget E-16. |
| `upsert_exercise` | write | `slug: string` (Pflicht; existiert er, wird geändert, sonst angelegt), `name`, `aliases: [string]`, `category`, `pattern`, `equipment: [string]`, `variant_of: string|null` (Slug), `difficulty`, `konfidenz`, `content: object`, `reason: string|null` (Pflicht beim Ändern) | Anlegen: Slug frei, Name/Alias frei (E-09), Schema (4.3), Linkprüfung (E-10), Embed-URL (E-04), `status` = `aktiv` oder `links_pruefen`. Ändern: vorheriger Stand nach `exercise_version` mit `reason`, `version + 1`; `slug` unveränderlich. Archivieren über `status: archiviert` (nur ohne geplante Verwendung). Audit `exercise_write`. Antwort: Datensatz in Kurzform (Slug, Name, Kategorie, Links mit Status, Version) und `hinweis_chat` – ein vorformulierter Satz für die Anzeige im Chat (E-02), z. B. „Neu im Katalog: ‚Bulgarian Split Squat‘ (kraft, knie_dominant, kurzhantel), 2 Links geprüft, Quelle L-A03.“ Schreibsperre `App::writeLocked`. |
| `write_week_plan`, `update_session` | write | unverändert | Validator (5.2): unbekanntes/archiviertes `exercise_id` → Fehler je Session (nichts geschrieben); fehlendes `exercise_id` → `warnungen: [{session, position, name, hinweis}]` in der Antwort; Namensabweichung (E-12) → Warnung. Warnungen verhindern das Schreiben nicht. |
| `get_week_overview`, `get_session_detail` | read | unverändert | je Übung zusätzlich `exercise_id` (Slug), sofern gesetzt; keine Katalogtexte (Budget). |

Tool-Beschreibungen nennen die Regel in einem Satz: `write_week_plan` „Übungen sollen ein `exercise_id` aus dem Katalog tragen (find_exercise/upsert_exercise); ohne ID kommt eine Warnung“; `upsert_exercise` „nach Bestätigung des Plans aufrufen; `hinweis_chat` dem Athleten zeigen“.

### 5.2 Validator

`PlanValidator` prüft weiterhin die JSON-Schemata; neu `Training\Plan\ExerciseLink` (nach der Schemaprüfung, vor dem Schreiben): löst `exercise_id` gegen `exercise` auf (ein Abruf je Plan), liefert `fehler` (unbekannt, archiviert, `kind` ohne Katalog) und `warnungen` (ohne ID, Namensabweichung). Die Webseite nutzt dieselbe Klasse für die Verlinkung (S3/S9).

## 6. Teil C · Webseite

### 6.1 S10 Übung (`/uebung?id=<slug>`)

- `layout-app`, Zurück-Pfeil (zur aufrufenden Einheit, wenn `von=<session_id>` übergeben, sonst zur Liste), Titel = Name; Kopf: Kategorie, Bewegungsmuster, Ausrüstung als Chips, Konfidenz, Status-Hinweis bei `links_pruefen` (Warnfarbe).
- Abschnitte in dieser Reihenfolge: Kurz/Ziel · Voraussetzung · Ausführung (nummeriert) · Worauf achten · Fehlerquellen · Vorsicht (hervorgehoben, Statusfarbe Fehler-Hintergrund) · Progression/Regression (mit Links auf Varianten) · Dosierungshinweis · Videos (eingebettet, 16:9, `loading="lazy"`, `title`, `allow="fullscreen"`, kein Autoplay; darunter der Link) · Links · Quellen · Fassungen (`<details>`: Version, Grund, Datum).
- Kein JavaScript nötig; Videos sind `iframe`. Offline: Der Service Worker liefert für den `iframe` nichts; deshalb steht der `iframe` in einer `<figure>` mit immer sichtbarem Fallback-Text (Titel + Link) darunter – ohne Netz bleibt so der Platzhalter (E-04, E-15), online ist der Link ohnehin nützlich.
- CSP: `frame-src https://www.youtube-nocookie.com https://player.vimeo.com`; sonst unverändert (`default-src 'self'`).

### 6.2 S10a Liste (`/uebungen`)

Suchfeld (GET `q`), Filterchips Kategorie, Liste als Karten (Name, Kategorie, Muster, Ausrüstung, Kurz, Status); archivierte ausgeblendet mit Schalter. Einstieg aus S8 („Übungskatalog“) und aus der unteren Navigation nicht (bleibt 4 Einträge); Link in S2-Kopfmenü prüft die Code-Instanz gegen das Design-System.

### 6.3 Verlinkung

- **S3:** Übungsname mit `exercise_id` wird Link auf S10 (`?von=<id>`) mit Icon `book`; ohne ID unverändert.
- **S9:** in der Phase-Karte unter dem Soll ein Textlink „Ausführung“ (öffnet S10 in derselben Ansicht; Zurück führt zur laufenden Einheit, der Fortschritt bleibt erhalten – E-19 in `gefuehrte-einheit.md`); in „Als Nächstes“ kein Link.
- **Kalender (AP-11):** im Kurzplan je Übung mit ID der Link `…/uebung?id=<slug>` (Beschreibung bleibt ≤ 1 000 Zeichen, Links zählen mit; bei Überschreitung entfallen Links zuerst).
- **Offline:** `WeekController::prefetch` nimmt die S10-Adressen der Übungen aller Einheiten der laufenden und nächsten Woche in den Seiten-Cache (E-15); `PAGE_PATHS` um `/uebung` und `/uebungen`.

### 6.4 Mockups

Code-Instanz ergänzt `docs/branding/mockups/s10-uebung.html` (Zustände: vollständig mit Video, `links_pruefen`, offline-Platzhalter) und `s10a-uebungen.html` aus vorhandenen Bausteinen (branding.md Abschnitt 8); S3-Mockup mit verlinktem Übungsnamen.

## 7. Teil D · Linkprüfung im Cron

- Im stündlichen `/cron/intervals-sync` einmal je 7 Tage (Marke `linkcheck_zuletzt` in `app_setting`): alle Links aktiver Übungen prüfen (E-10, höchstens 50 je Lauf, Rest beim nächsten Wochenlauf); `defekt` → Übung `links_pruefen`, Hinweis in S8 („n Übungen mit defekten Links“); der Status ist außerdem in `find_exercise`/`get_exercise` sichtbar (nicht in `get_handover`); Audit `exercise_linkcheck` mit Zusammenfassung.
- Wieder erreichbare Links setzen die Übung zurück auf `aktiv`, wenn kein Link mehr `defekt` ist.
- Keine Ausfälle durch die Prüfung: Timeout 5 s je Link, Fehler werden gezählt, der Cron läuft weiter.

## 8. Teil E · Trainerregeln (Kapitel „Übungskatalog“, AP-07)

```yaml
- id: R-UEB-10
  regel: Vor jeder Wochenplanung wird für jede Kraft-, Haltungs-, Mobilitäts- und Kletterübung (hangboard, campus, zugkraft, antagonisten) find_exercise aufgerufen; vorhandene oder ähnliche Einträge werden verwendet, Varianten über variant_of angelegt.
  konfidenz: Verfahrensregel
- id: R-UEB-11
  regel: Neue Übungen werden nach Bestätigung des Wochenplans mit upsert_exercise angelegt; hinweis_chat wird dem Athleten gezeigt. Warnungen aus write_week_plan werden im selben Chat aufgelöst (Anlegen oder begründeter Freitext).
  konfidenz: Verfahrensregel
- id: R-UEB-12
  regel: Jeder Katalogeintrag nennt mindestens eine Quelle und eine Konfidenz; Ausführung und Fehlerquellen stammen aus der Wissensbasis oder sind als Einschätzung gekennzeichnet; Links werden nicht erfunden (nur URLs, die die Instanz tatsächlich kennt oder nachgeschlagen hat – der Server prüft die Erreichbarkeit, nicht den Inhalt).
  quelle: D-13 (Zitierregel), D-62 e
- id: R-UEB-13
  regel: Der Abschnitt „vorsicht“ nennt bei Übungen mit Bezug zu Knie, Sprunggelenk oder Fingern die relevante Reha-Regel (Block R) und die Schmerzgrenze.
  quelle: Block R (L-R-02, L-R-13), Abschnitt 14 Schmerzregeln
- id: R-UEB-14
  regel: Die Einheit beschreibt nur Dosierung (Sätze, Wiederholungen, Last, Tempo, Pause) und einen kurzen Hinweis; Ausführungsdetails gehören in den Katalog.
  konfidenz: Verfahrensregel
```

## 9. Unterpunkte

Reihenfolge: T1 → T2; T3 nach T1 (parallel zu T2 möglich); T4 nach T2 und T3; T5 nach T2; T6 zuletzt.

### T1 · Datenmodell, Schemata, Validator
- Migrationen (4.1, 4.4); `ExerciseRepository` (Suche mit Normalisierung, Aliase, Fassungen); `server/schemas/exercise.json`; `plan_json`-Schemata um `exercise_id`; `ExerciseLink` (5.2); Normalisierungsfunktion (E-09) mit Tests; Embed-Ableitung (4.3).
- Tests: Schema gültig/ungültig (Linkzahl, https, Längen); Normalisierung (Umlaute, Bindestrich, Groß/Klein); `ExerciseLink` Fälle V-01 bis V-08 (11.1); Embed-Ableitung für alle URL-Formen.
- **Abnahme:** Testfälle 11.1 grün; Migration vor/zurück.

### T2 · MCP-Tools und Linkprüfung
- `find_exercise`, `get_exercise`, `list_exercises`, `upsert_exercise` (mit `LinkChecker`, E-10); Änderungen `write_week_plan`/`update_session` (Warnungen, Fehler), Lese-Tools (`exercise_id`); Audit; Tool-Beschreibungen; `ToolRegistry`.
- Tests: T-01 bis T-10 (11.2) mit simulierten HTTP-Antworten für Links; Budgetmessung mit 40 Übungen; Schreibsperre.
- **Abnahme:** Aus dem Projekt-Chat: `find_exercise("split squat")` findet nichts → `upsert_exercise` legt an, Links werden geprüft, `hinweis_chat` erscheint; `write_week_plan` mit `exercise_id` ohne Warnung, ohne ID mit Warnung.

### T3 · Webseite S10/S10a und CSP
- `ExerciseController` (`GET /uebung`, `GET /uebungen`), Templates, CSS für Chips/Vorsicht-Abschnitt/Video-`figure`, CSP `frame-src`, Mockups (6.4), S8-Eintrag.
- Tests: Rendering mit Fixture (alle Abschnitte, Video-`iframe` nur für `embed`, Fallback-Text immer da); Suche/Filter; 404 für unbekannten Slug; CSP-Header enthält genau die zwei Hosts; 375 px ohne horizontales Scrollen.
- **Abnahme:** Sichtprüfung durch den Athleten auf dem Smartphone: Video spielt eingebettet, Vorsicht-Abschnitt sichtbar.

### T4 · Verlinkung aus S3, S9, Kalender und Offline
- S3-Link, S9-Link „Ausführung“ (Fortschritt bleibt erhalten – Test), Kalenderbeschreibung (AP-11, Längenregel), `prefetch` und `PAGE_PATHS` (E-15).
- Tests: S3 zeigt Link nur mit ID; S9 Rückkehr zur laufenden Einheit stellt Schritt/Satz her (Node-Test Fortschrittsspeicher unverändert, Browser-Test); Kalender: Links im Kurzplan, Kürzung; Service Worker cached die Übungsseiten der Woche (Playwright wie in AP-14: echter Netzausfall, Übungsseite lädt, Video-Platzhalter mit Link).
- **Abnahme:** Auf dem Smartphone ohne Netz: Einheit öffnen → Übung öffnen → Ausführung lesbar, Platzhalter statt Video.

### T5 · Wöchentliche Linkprüfung im Cron
- `LinkChecker` im Cron (Teil D), `app_setting` `linkcheck_zuletzt`, Statuswechsel, S8-Hinweis, Audit.
- Tests: Lauf nur einmal je 7 Tage; `defekt` → `links_pruefen` → nach Erfolg zurück `aktiv`; Timeout bricht den Lauf nicht ab; Obergrenze 50.
- **Abnahme:** Cron-Lauf auf dem Server meldet im Audit die Zusammenfassung.

### T6 · Dokumentation und Regeln
- Hauptkonzept: AP-16 mit Statusblock; Abschnitt 7 (Tabellen), 7.1 (`exercise_id`), 8.3 (Tools, Budgets), 10 (S10, S10a, S8), 14 (Kapitel 8 mit Verweis auf AP-07), D-Einträge für E-01 bis E-06 (Abschnitt 10 dieses Dokuments); `datenmodell.md` (Tabellen, Schemata, ER-Diagramm); `gefuehrte-einheit.md` (Hinweis auf den Link in S9); README (Endpunkte, Tools, CSP); CHANGELOG; Prüfprotokoll; `docs/regeln/trainerregeln.md` (Kapitel, ggf. als Vorabkapitel).
- **Abnahme:** Dokumente konsistent; Prüfprotokoll listet T1–T5.

## 10. Änderungen am Hauptkonzept (durch die Code-Instanz einzutragen)

- Neues Arbeitspaket **AP-16 Übungskatalog** mit Verweis auf dieses Dokument; Abhängigkeiten: AP-03 (Schemata), AP-05 (Schreibtools), AP-09 (Offline), AP-11 (Kalenderbeschreibung, Cron), AP-14 (S9).
- Entscheidungen E-01 (Katalog in DB, `exercise_id`), E-02 (KI legt an), E-03 (weiche Regel), E-04 (Einbettung), E-05 (Kletterblöcke), E-06 (Quellenpflicht) als D-Einträge mit Datum 2026-09-29, Begründung „Entscheidung des Athleten“.
- Abschnitt 7.1 ergänzt um `exercise_id`; Abschnitt 14 um das Kapitel „Übungskatalog“.

## 11. Testfälle

### 11.1 Validator `ExerciseLink` (T1)

| nr | eingabe | erwartung |
|---|---|---|
| V-01 | kraft `{exercise_id:"kniebeuge", name:"Kniebeuge"}`, Übung vorhanden | ok, keine Warnung |
| V-02 | kraft `{name:"Kniebeuge"}` ohne ID | Warnung `ohne_katalog` |
| V-03 | kraft `{exercise_id:"kniebeugen"}` unbekannt | Fehler `unbekannt` mit Session und Position |
| V-04 | kraft `{exercise_id:"kniebeuge", name:"Squat"}`, „Squat“ kein Alias | Warnung `name_abweichend` |
| V-05 | wie V-04, „Squat“ ist Alias | ok |
| V-06 | klettern `{kind:"hangboard", exercise_id:"max-hang-20mm"}` | ok |
| V-07 | klettern `{kind:"bouldern_volumen", exercise_id:"x"}` | Schemafehler (E-05) |
| V-08 | kraft `{exercise_id:"archiviert-1"}`, Übung archiviert | Fehler `archiviert` |

### 11.2 Tools (T2)

| nr | ablauf | erwartung |
|---|---|---|
| T-01 | `find_exercise("split squat")`, Katalog leer | `treffer: []`, Hinweis auf `upsert_exercise` |
| T-02 | `upsert_exercise` neu mit 2 Text-, 2 Videolinks (YouTube, Vimeo), Links erreichbar | Status `aktiv`, `embed` gesetzt, `geprueft_am` heute, `hinweis_chat` gefüllt |
| T-03 | wie T-02, ein Link antwortet 404 | Übung angelegt, Link `defekt`, Status `links_pruefen`; Tool kein Fehler |
| T-04 | `upsert_exercise` neu, Name „Kniebeuge“ existiert als Alias „kniebeuge“ | abgelehnt mit Treffer |
| T-05 | `upsert_exercise` neu mit 3 Videolinks | Schemafehler |
| T-06 | `upsert_exercise` ändern ohne `reason` | abgelehnt |
| T-07 | `upsert_exercise` ändern mit `reason` | `exercise_version` v1 mit Schnappschuss, `exercise.version` = 2, `slug` unverändert |
| T-08 | `find_exercise("split")` mit Übungen „Bulgarian Split Squat“ (knie_dominant, kurzhantel) und „Ausfallschritt“ (knie_dominant, koerpergewicht) | erster als Treffer, zweiter als `aehnlich` |
| T-09 | `upsert_exercise` `status: archiviert` für eine Übung in geplanter Einheit | abgelehnt mit Session-ID |
| T-10 | `write_week_plan` mit V-02 und V-01 gemischt | geschrieben; `warnungen` mit einem Eintrag |

### 11.3 Webseite (T3, T4)

| nr | ablauf | erwartung |
|---|---|---|
| W-01 | S10 für Übung mit YouTube-Link | `iframe src=https://www.youtube-nocookie.com/embed/<id>`, `loading=lazy`, Fallback-Text mit Link sichtbar |
| W-02 | S10 für Übung mit Link ohne Embed | nur Link, kein `iframe` |
| W-03 | S10 unbekannter Slug | 404 |
| W-04 | S3 mit Übung mit/ohne ID | Link nur mit ID |
| W-05 | S9: Link „Ausführung“ öffnen, zurück | Schritt und Satz wie zuvor |
| W-06 | Netzausfall (Playwright-Proxy), Übung der heutigen Einheit | Seite aus dem Cache, Platzhalter statt Video |
| W-07 | CSP-Header | `frame-src` genau die zwei Hosts |

## 12. Offene Punkte

| id | punkt | stand |
|---|---|---|
| O-01 | Bearbeiten von Übungen auf der Webseite (Formular mit Fassung/Grund) | zurückgestellt; erst nach Praxiserfahrung |
| O-02 | Eigene Videos hosten (Speicher auf Lima-City, Upload über S8) statt Plattform-Einbettung | offen; nur bei Bedarf |
| O-03 | Bilder/Skizzen je Übung (Upload, Anzeige in S10 und S9) | offen; Vorschlag: nach O-01 |
| O-04 | Übungsnamen in `get_week_overview` aus dem Katalog anreichern (Kurz-Text) | nein wegen Budget; bei Bedarf `get_exercise` |
| O-05 | Soll die Kalenderbeschreibung Links je Übung tragen (E-14) oder reicht der Link zur Einheit? | Vorschlag Fable: je Übung, mit Kürzungsregel; Entscheidung Athlet bei der Abnahme von T4 |

## 13. Status je Unterpunkt (von der Code-Instanz zu pflegen)

```yaml
T1:
  status: erledigt
  datum: 2026-09-29
  ergebnis: >-
    Migration 0023 (exercise mit name_norm, exercise_alias, exercise_version); Schema server/schemas/exercise.json;
    plan_json-Schemata um exercise_id (Kletterblöcke nur hangboard/campus/zugkraft/antagonisten über if/then);
    Training\Exercise\Catalog (Vokabular, Slug, Normalisierung, Embed-Ableitung), Training\Exercise\ContentValidator
    (Serverfelder verwerfen/setzen, Schemaprüfung), Training\Data\ExerciseRepository (Anlegen, Fassungen mit Schnappschuss,
    Aliase, Duplikatsuche, Suche mit Rang und Ähnlichkeit, Varianten, geplante Verwendung), Training\Plan\ExerciseLink
    (Fehler/Warnungen, Verlinkung für die Webseite); JSON-Export mit den neuen JSON-Spalten. Code-Stand 0.21.0, Schema 23.
  tests: >-
    Unit ExerciseCatalogTest (Normalisierung, Slug, Embed für alle URL-Formen, content_json gültig/ungültig inkl. Linkzahl,
    https, Längen, Quellenpflicht; plan_json exercise_id inkl. V-06/V-07); Integration ExerciseCatalogTest (V-01 bis V-08,
    Fassungen, Aliase, Duplikate, Suche, geplante Verwendung); Migration vor/zurück über die bestehenden Rückweg-Tests
    (BackupTest, McpToolsTest, ProfileTest, MorningCheckinTest); gesamte Suite 242 Tests grün gegen MariaDB 10.11.
  abnahme: automatisiert (Testfälle 11.1 grün); CI gegen MySQL 8.4 mit dem Pull Request
  probleme_loesungen:
    - was: 4.5 nennt zwei Migrationen; die Änderung an plan_json liegt nur in den JSON-Schemata, eine SQL-Migration hätte nichts zu tun
      loesung: eine Migration 0023 für die drei Tabellen; die Schemaänderung ist rückwärtskompatibel (exercise_id optional)
    - was: Opis JSON Schema wertet contains mit minContains 0 bei leerer Linkliste als Fehler
      loesung: Obergrenze je Art als not/contains mit minContains 3 (E-11 unverändert)
    - was: name_norm/alias_norm mit 120 Zeichen zu kurz, weil ä/ö/ü/ß zu zwei Zeichen werden
      loesung: 240 Zeichen (E-20)
    - was: Normalisierung mit intl (Normalizer) wäre je nach Server unterschiedlich und die gespeicherte Form damit nicht stabil
      loesung: feste Akzenttabelle (à, é, ñ, ø …) statt intl
    - was: T-08 erwartet „Ausfallschritt“ (koerpergewicht) als ähnlich zu „Bulgarian Split Squat“ (kurzhantel), 5.1 verlangt „gleiche pattern und überlappende equipment“
      loesung: ähnlich = gleiches Bewegungsmuster wie ein Treffer (oder wie der Filter pattern); die Überschneidung der Ausrüstung bestimmt nur die Reihenfolge
    - was: Pflichtfelder in content_json nicht festgelegt
      loesung: Pflicht kurz, ziel, ausfuehrung (2–12 Schritte), quellen (≥ 1); übrige Felder optional, links fehlend = []
    - was: Testhilfe rollbackLastMigration löschte nur die erste angelegte Tabelle; Rückweg-Test Morgen-Check-in kannte 0023 nicht
      loesung: alle Tabellen der letzten Migration in umgekehrter Reihenfolge löschen; Rückweg 0023 im Test ergänzt
T2:
  status: erledigt
  datum: 2026-09-29
  ergebnis: >-
    Tools find_exercise, get_exercise, list_exercises, upsert_exercise (Training\Mcp\ExerciseTools, ToolRegistry);
    Linkprüfung Training\Exercise\LinkChecker mit CurlLinkFetcher (curl_multi, oEmbed für Videos E-18, Schutzregeln E-19);
    write_week_plan/update_session mit ExerciseLink (Fehler vor dem Schreiben, warnungen und hinweis_warnungen in der Antwort);
    get_week_overview je Einheit exercise_ids; Tool-Beschreibungen mit der Regel (5.1); Audit exercise_write. Code-Stand 0.22.0.
  tests: >-
    Integration ExerciseToolsTest (T-01 bis T-10 mit simulierten Linkantworten, Budget mit 40 Übungen, Schreibsperre,
    Varianten/Schleifenschutz, unveränderter Inhalt ohne neue Fassung, Lese-Tools); Unit LinkCheckerTest (Bewertung,
    früheres Ergebnis bei Netzfehler, ein Durchgang für mehrere Übungen, Schutzregeln); gesamte Suite grün (MariaDB 10.11).
  abnahme: >-
    automatisiert ok; offen: aus dem Projekt-Chat find_exercise („split squat“) → upsert_exercise mit echten Links
    (Prüfung auf dem Server), hinweis_chat; write_week_plan mit/ohne exercise_id (nach Deployment)
  probleme_loesungen:
    - was: curl_errno liefert bei curl_multi immer 0; Abrufe ohne Antwort erschienen als „Fehler 0“
      loesung: Ergebniscode je Abruf über curl_multi_info_read
    - was: Die Code-Umgebung darf YouTube, Vimeo und beliebige Seiten nicht abrufen (Netzrichtlinie); Live-Test der Linkprüfung nicht möglich
      loesung: Bewertung und Schutzregeln mit simuliertem Abruf getestet; Live-Prüfung Teil der Abnahme auf dem Server (Prüfprotokoll)
    - was: 5.1 nennt warnungen als {session, position, name, hinweis}; bei vielen Freitext-Übungen wiederholt sich derselbe Hinweis (Budget 8.3)
      loesung: je Warnung session, datum (write_week_plan), position, name, code; hinweis nur bei name_abweichend, für ohne_katalog einmal hinweis_warnungen
    - was: find_exercise mit 10 Treffern und fast 200 Zeichen Kurztext knapp über 1 000 Tokens
      loesung: status nur, wenn nicht aktiv, aehnlich nur, wenn true (in der Tool-Beschreibung genannt)
    - was: get_exercise – die Schemagrenzen 4.3 erlauben Einträge von rund 2 800 Tokens, E-16 nennt 1 500
      loesung: Budget gilt für typische Einträge (Test mit reichhaltigem, realistischem Eintrag < 6 000 Zeichen); Schemagrenzen unverändert
    - was: list_exercises ohne Statusfilter – archivierte Übungen im Überblick unnötig
      loesung: ohne status ohne archivierte; Zeilen als Liste [slug, name, category, pattern, status nur wenn nicht aktiv] mit Feldnamen einmal
    - was: Bewertung einzelner Antworten nicht festgelegt (E-10 nennt ok/defekt/ungeprueft)
      loesung: 2xx ok; 404/410 und übrige 4xx defekt; 401/403 bei oEmbed defekt (privat, Einbettung gesperrt), bei Textseiten ungeprueft (Bot-Schutz); 429/5xx, Netzfehler und mehr als 3 Weiterleitungen ungeprueft; gesperrtes Ziel defekt; bei ungeprueft bleibt ein früheres Ergebnis derselben Adresse
    - was: Änderung ohne inhaltlichen Unterschied hätte bei jeder Linkprüfung eine Fassung erzeugt (geprueft_am)
      loesung: Vergleich ohne Linkstatus; unverändert → keine Fassung, nur Linkstatus nachgetragen (unveraendert true)
    - was: status als Eingabe von upsert_exercise nicht abgegrenzt
      loesung: nur aktiv oder archiviert; links_pruefen setzt ausschließlich der Server; eine archivierte Übung bleibt archiviert, bis status aktiv kommt
    - was: variant_of könnte eine Schleife bilden
      loesung: Prüfung der Elternkette beim Setzen
    - was: 5.1 get_session_detail „je Übung exercise_id“ – plan_json enthält die ID bereits
      loesung: get_session_detail unverändert; get_week_overview je Einheit exercise_ids (nur Slugs)
T3:
  status: offen
  datum: null
  ergebnis: null
  tests: null
  abnahme: null
  probleme_loesungen: []
T4:
  status: offen
  datum: null
  ergebnis: null
  tests: null
  abnahme: null
  probleme_loesungen: []
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
| 2026-09-29 | Fable | Erstfassung nach Klärung E-01 bis E-06 mit dem Athleten |
| 2026-09-29 | Code-Instanz | Konzept vom Athleten bestätigt (E-07 bis E-16 gelten); E-17 bis E-20 aus dem Abgleich mit dem Code ergänzt und bestätigt; 4.1 um `name_norm` ergänzt |
| 2026-09-29 | Code-Instanz | T1 umgesetzt (Code-Stand 0.21.0), Befunde in Abschnitt 13 |
| 2026-09-29 | Code-Instanz | T2 umgesetzt (Code-Stand 0.22.0), Befunde in Abschnitt 13 |
