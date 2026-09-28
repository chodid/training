# Auftrag: Morgen-Check-in mit Morgentest – Erfassung, Ableitung, Bereitstellung im Morgen-Briefing und per MCP

Ablageort im Repo: `docs/konzept/morgen-checkin.md` (im Hauptkonzept: AP-12, D-53)
Status: Konzept bestätigt durch Philipp am 28.09.2026 – umgesetzt (Code-Stand 0.16.0, Schema 21), Abnahme durch Philipp offen
Versionsnummer: 0.16.0 (festgelegt in der Umsetzung)

---

## 1. Ziel

Philipp erfasst jeden Morgen vor dem Frühstück in der WebApp einen kurzen, strukturierten Check-in. Kern ist ein standardisierter Provokationstest für die Patellasehnen („Morgentest"). Die Werte werden serverseitig zu einer Ampel verdichtet und über das Morgen-Briefing sowie ein eigenes MCP-Tool für Claude abrufbar gemacht. Claude steuert damit den Trainingsplan (Anpassungen über `update_session` / `write_week_plan`).

## 2. Kontext

- Aktiver Block: „Block 1 · Patellasehne, OSG links, Haltung" (22.09.–13.12.2026). Grundlage ist ein belastungsbasiertes Sehnenprotokoll (Heavy Slow Resistance), dessen Steuerung auf dem Schmerz am Folgemorgen beruht.
- Bereits vorhanden laut MCP-Schnittstelle (v0.14.0): Check-in mit `recovery_1_5` und `soreness_1_5`, Schmerzereignisse mit Ort, Seite, Zeitpunkt und `intensity_0_10` (NRS), Tools `get_pain_history`, `get_wellness_trend`, `get_week_overview`.
- Das Repo ist für Claude (Chat) nicht einsehbar. Die Zuordnung zum bestehenden Schema trifft Claude Code in T1 und dokumentiert sie hier.

## 3. Geklärte Entscheidungen

| ID | Entscheidung |
|---|---|
| E-01 | Schmerzskala überall NRS 0–10, Ganzzahl – identisch mit dem bestehenden Feld `intensity_0_10`. |
| E-02 | „Nicht erhoben" ist `null` und niemals `0`. Die Eingabe hat keine Vorauswahl. |
| E-03 | Steuerwert eines Tages = Maximum der vorhandenen Morgentest-Werte links/rechts. Fehlt einer, zählt der andere. |
| E-04 | Die Ampelregeln stammen aus dem Trainingsplan (Abschnitt 5) und sind fest definiert, keine KI-Logik. |
| E-05 | Die Ampel ist reine Information. Die WebApp ändert den Plan **nicht** automatisch; Anpassungen macht Claude über die bestehenden Tools. |
| E-06 | Ein Check-in pro Kalendertag (Zeitzone Europe/Berlin). Am selben Tag editierbar, die letzte Fassung gilt. |
| E-07 | Warnzeichen setzen `abklaerung_empfohlen = true`. Es wird keine Diagnose abgeleitet. |
| E-08 | Das Feld „Hand rechts" ist zeitlich begrenzt sichtbar. Das Enddatum ist konfigurierbar, Standard 2026-11-23. |
| E-09 | Die bestehenden Check-in-Felder `recovery_1_5` und `soreness_1_5` bleiben unverändert erhalten. |
| E-10 | Speicherort (T1, O-01): Erweiterung der bestehenden Tabelle `checkin` (ein Eintrag je Tag) statt Schmerzereignissen oder eigener Tabelle – entschieden mit Philipp am 28.09.2026. |
| E-11 | Erholung und Muskelkater bleiben Pflicht (Philipp, 28.09.2026). Der Normalfall braucht damit 5 Taps (links, rechts, Erholung, Muskelkater, speichern) statt der 3 aus T3. |
| E-12 | Ein separates Morgen-Briefing gibt es nicht (O-02): Philipp öffnet morgens die Webseite. 6.2 wird durch die Zusammenfassung in der Karte „Morgen-Check-in“ nach dem Speichern erfüllt; Claude liest dasselbe über `get_morning_checks`. |
| E-13 | Schmerzorte `patellasehne`, `sprunggelenk`, `bws` für Schmerzereignisse ergänzt (Philipp, 28.09.2026; Konzept 7.2). |

## 4. Datenfelder (maschinenlesbar)

```yaml
morgen_checkin:
  datum: date            # Kalendertag Europe/Berlin, eindeutig
  erfasst_um: datetime   # Zeitstempel letzte Speicherung
  morgentest_patellasehne:
    anleitung: >
      Einbeiniger Squat langsam bis ca. 60° Kniebeugung, je Seite.
      Stärkster Schmerz an der Patellaspitze. 0 = kein Schmerz, 10 = stärkster vorstellbarer.
    links:  {typ: int, bereich: [0, 10], null_erlaubt: true, pflicht: true}
    rechts: {typ: int, bereich: [0, 10], null_erlaubt: true, pflicht: true}
  nacken_bws:
    frage: "Nacken/Brustwirbelsäule beim Aufstehen"
    wert: {typ: int, bereich: [0, 10], null_erlaubt: true, pflicht: false}
  sprunggelenk_links:
    umgeknickt_seit_letztem_checkin: {typ: bool, default: false}
    schwellung: {typ: bool, default: false, sichtbar_wenn: umgeknickt_seit_letztem_checkin}
  hand_rechts:
    wert: {typ: int, bereich: [0, 10], null_erlaubt: true, pflicht: false}
    sichtbar_bis: "2026-11-23"   # konfigurierbar
  warnzeichen:                    # Mehrfachauswahl, Standard leer
    typ: list[enum]
    werte:
      - sehne_scharfer_schmerz_kraftverlust   # plötzlich, Bein gestreckt nicht anhebbar
      - knie_schwellung_erguss
      - knie_ruhe_oder_nachtschmerz
      - blockade_knie_oder_osg
      - arm_ausstrahlung_kribbeln_schwaeche
      - schwindel_sehstoerung_bei_nackenuebung
  notiz: {typ: string, max_len: 500, pflicht: false}
  # bestehend, unverändert:
  recovery_1_5: {typ: int, bereich: [1, 5]}
  soreness_1_5: {typ: int, bereich: [1, 5]}
```

## 5. Ableitungen (serverseitig, deterministisch)

Definitionen für den Tag `d`, mit `v(d)` = Steuerwert nach E-03:

```yaml
ampel:
  keine_daten: v(d) ist null
  rot:   v(d) > 5
         ODER (v(d-2), v(d-1), v(d) alle vorhanden UND v(d-2) < v(d-1) < v(d) UND v(d) >= 4)
  gelb:  4 <= v(d) <= 5 (und nicht rot)
  gruen: v(d) <= 3
ampel_grund: Klartext, z. B. "Morgentest 6/10" oder "zwei Tage steigend (2→3→4)"
wochenausgangswert: erster nicht-leerer Steuerwert der Kalenderwoche (Mo–So)
ueber_wochenausgangswert: v(d) > wochenausgangswert        # 24-Stunden-Regel
vortag_einheiten: Typen der Einheiten vom Vortag mit Status erledigt/teilweise (z. B. ["kraft","ausdauer"])
abklaerung_empfohlen: warnzeichen nicht leer ODER (sprunggelenk_links.umgeknickt UND schwellung)
```

## 6. Bereitstellung für Claude

### 6.1 Neues MCP-Tool `get_morning_checks`

- Parameter: `days` (Standard 14, 7–90)
- Liefert je Tag alle Felder aus Abschnitt 4 und alle Ableitungen aus Abschnitt 5, neueste zuerst, plus eine Zusammenfassung:

```json
{
  "heute": "2026-10-01",
  "zusammenfassung": {
    "ampel": "gelb",
    "ampel_grund": "Morgentest 4/10",
    "morgentest": {"links": 2, "rechts": 4, "steuerwert": 4},
    "wochenausgangswert": 2,
    "ueber_wochenausgangswert": true,
    "vortag_einheiten": ["kraft"],
    "tage_gruen_letzte_7": 5,
    "abdeckung_letzte_7_pct": 86,
    "abklaerung_empfohlen": false
  },
  "tage": [
    {"datum": "2026-10-01", "morgentest": {"links": 2, "rechts": 4}, "steuerwert": 4,
     "ampel": "gelb", "nacken_bws": 2, "sprunggelenk_links": {"umgeknickt": false, "schwellung": false},
     "hand_rechts": 1, "warnzeichen": [], "recovery_1_5": 2, "soreness_1_5": 3, "notiz": ""}
  ],
  "skalen": {"schmerz": "NRS 0–10", "recovery_1_5": "1 sehr gut … 5 sehr schlecht", "soreness_1_5": "1 kein … 5 stark"}
}
```

`tage_gruen_letzte_7` wird für die Steigerungsregel im Laufplan gebraucht (Steigerung nur bei mindestens 5 grünen Tagen in der Vorwoche).

### 6.2 Morgen-Briefing

Die Zusammenfassung aus 6.1 erscheint als eigener Abschnitt am Anfang des bestehenden Morgen-Briefings: Ampel mit Grund, Werte links/rechts, Hinweis bei Überschreitung des Wochenausgangswerts und — hervorgehoben — `abklaerung_empfohlen`.

### 6.3 Erweiterung bestehender Tools

- `get_week_overview`: je Tag `steuerwert` und `ampel`, zusätzlich die Wochenwerte `tage_gruen` und `abdeckung_pct`.
- `get_pain_history`: Morgentest-Werte sind als Zeitpunkt „morgentest" unterscheidbar, falls sie als Schmerzereignisse gespeichert werden (siehe T1). **Entfällt** (E-10: Morgentest ist kein Schmerzereignis).

## 7. Unterpunkte

### T1 · Bestandsaufnahme und Mapping-Entscheidung
- Bestehendes Schema für Check-in und Schmerzereignisse sowie den Aufbau des Morgen-Briefings sichten.
- Entscheiden und hier dokumentieren: Morgentest als Schmerzereignisse (Ort `patellasehne`, Seite `links`/`rechts`, Zeitpunkt `morgentest`) **oder** als eigene Tabelle `morning_checks`. Bevorzugt ist die Wiederverwendung, falls Zeitpunkt und Seite sauber abbildbar sind und `null` ≠ `0` gewahrt bleibt.
- **Abnahme:** Entscheidung mit Begründung in Abschnitt 9 eingetragen.

### T2 · Datenmodell und Migration
- Felder aus Abschnitt 4 anlegen, bestehende Daten unverändert lassen.
- **Abnahme:** Migration läuft auf leerer und befüllter Datenbank, Rückweg dokumentiert.

### T3 · Erfassungsmaske (mobil)
- Eigene Karte „Morgen-Check-in" auf der Startseite, oberhalb der Tageseinheiten.
- Morgentest als zwei Zeilen mit je 11 Tipp-Feldern (0–10), ohne Vorauswahl. Erneutes Tippen auf den gewählten Wert setzt ihn zurück auf leer.
- Anleitungstext aus Abschnitt 4 sichtbar.
- Übrige Felder einklappbar. „Hand rechts" nur bis `sichtbar_bis`.
- Speichern mit höchstens drei Taps für den Normalfall (links, rechts, speichern).
- Nach dem Speichern die Ampel mit Grund anzeigen.
- **Abnahme:** auf 375 px Breite ohne horizontales Scrollen bedienbar; Werte bleiben nach Neuladen erhalten; nachträgliche Änderung am selben Tag überschreibt.

### T4 · Ableitungslogik
- Abschnitt 5 serverseitig als reine Funktion implementieren, mit Unit-Tests (Abschnitt 8).
- **Abnahme:** alle Testfälle grün; Zeitzone Europe/Berlin auch über die Umstellung auf Winterzeit (25.10.2026) korrekt.

### T5 · MCP und Briefing
- Tool `get_morning_checks` nach 6.1, Briefing-Abschnitt nach 6.2, Erweiterungen nach 6.3.
- **Abnahme:** Aufruf über den Claude-Connector liefert das Format aus 6.1; Tool-Beschreibung nennt Skalen und Ampelregeln.

### T6 · Dokumentation, Changelog, Version
- Version hochstufen, Changelog und Dokumentation aktualisieren (neue Felder, neues Tool, erweiterte Tools), Dokumente auf Konsistenz prüfen.
- Dieses Konzeptdokument je abgeschlossenem Unterpunkt fortschreiben (erledigt, Probleme, Lösung).
- **Abnahme:** Changelog-Eintrag vorhanden; MCP-Dokumentation listet `get_morning_checks`.

## 8. Testfälle Ampel

| # | v(d−2) | v(d−1) | links/rechts heute | Erwartung |
|---|---|---|---|---|
| 1 | – | – | 2 / 3 | grün |
| 2 | – | – | 4 / 1 | gelb |
| 3 | – | – | 6 / 0 | rot (> 5) |
| 4 | 2 | 3 | 4 / 2 | rot (steigend, ≥ 4) |
| 5 | 1 | 2 | 3 / 3 | grün (steigend, aber < 4) |
| 6 | 2 | – | 4 / 4 | gelb (Vortag fehlt → keine Steigungsprüfung) |
| 7 | – | – | null / null | keine_daten |
| 8 | – | – | null / 5 | gelb (E-03) |
| 9 | 4 | 4 | 4 / 4 | gelb (nicht streng steigend) |
| 10 | – | – | 0 / 0 | grün – und gespeichert als 0, nicht als null |

Zusätzlich: Wochenausgangswert Mo = 2, Mi = 3 → `ueber_wochenausgangswert = true`; Mo leer, Di = 1 → Ausgangswert 1.

## 9. Offene Punkte / Entscheidungen aus der Umsetzung

| ID | Thema | Status |
|---|---|---|
| O-01 | Mapping Morgentest: Schmerzereignisse oder eigene Tabelle (T1) | entschieden → E-10: Erweiterung von `checkin`. Begründung: `checkin` hat bereits „ein Eintrag je Tag, letzte Fassung gilt“ (E-06); Schmerzereignisse sind nur anhängend (Überschreiben am selben Tag nur über Löschen/Neuanlegen), `null ≠ 0` wäre nur über das Fehlen eines Eintrags abbildbar, Warnzeichen/Umknicken/Schwellung passen nicht, `get_pain_history` würde durch tägliche 0-Werte verwässert; eine eigene Tabelle hätte zwei Check-ins je Tag mit überlappenden Feldern bedeutet. Nebeneffekt: Offline-Puffer, Konfliktschutz, Export, Backup und Wochenübersicht gelten ohne Zusatzaufwand. |
| O-02 | Name und Aufbau des bestehenden Morgen-Briefings | entschieden → E-12: im Repo gibt es kein Briefing; die Karte auf der Startseite zeigt die Zusammenfassung. |

## 10. Nicht im Umfang

- Strukturierte Messwerte (VISA-P, Knee-to-wall, Einbeinstand, Wadenheben, Seitenfoto-Winkel) – eigener Auftrag.
- Automatische Planänderungen in der WebApp (siehe E-05).

## 11. Arbeitsweise für die Umsetzung

- Unterpunkte T1–T6 nacheinander abarbeiten.
- Nach jedem Unterpunkt: geänderte und neue Dateien als ZIP mit Repo-Ordnerstruktur (nur geänderte/neue Dateien), dieses Konzeptdokument aktualisiert, dazu ein Prüfdokument (was geprüft ist, was noch wie zu prüfen ist).

## 12. Umsetzungsstand (Code-Stand 0.16.0)

```yaml
T1:
  status: erledigt
  ergebnis: Entscheidung E-10 (checkin erweitern), O-02 → E-12; Ablage dieses Dokuments; AP-12 und D-53 im Hauptkonzept
T2:
  status: erledigt
  ergebnis: >
    Migration 0020_checkin_morning (Spalten mt_links, mt_rechts, nacken_bws, hand_rechts TINYINT NULL mit CHECK ≤ 10;
    osg_umgeknickt, osg_schwellung TINYINT(1) Standard 0; warnzeichen JSON NULL) und 0021_pain_event_locations (E-13).
    Rückweg als Kommentar in beiden Migrationen; Einstellung „Hand rechts bis“ in app_setting (Standard 2026-11-23).
  probleme_loesungen:
    - was: Rückweg-SQL mit DROP CHECK läuft auf MariaDB nicht
      loesung: DROP CONSTRAINT (MariaDB und MySQL 8.4)
    - was: Test-Hilfe „letzte Migration zurücksetzen“ erwartete eine neue Tabelle
      loesung: bei Spaltenänderungen wird nur der Schemastand zurückgesetzt
T3:
  status: erledigt
  ergebnis: >
    Karte „Morgen-Check-in“ oben in der Wochenansicht der aktuellen Woche: Formular, solange heute kein Morgentest erfasst
    ist, danach Zusammenfassung mit Ampel (Knopf „Ändern“ → /checkin). Gleiches Formular auf /checkin (Partial).
    Morgentest als zwei Zeilen 0–10 ohne Vorauswahl; erneutes Tippen leert (js/checkin.js); übrige Felder in
    „Weitere Angaben“ (aufgeklappt, sobald dort etwas erfasst ist); Schwellung erst nach „umgeknickt“; Hand rechts bis
    Einstellung. Offline-Puffer und Konfliktschutz wie beim bisherigen Check-in.
  probleme_loesungen:
    - was: T3 verlangt höchstens 3 Taps
      loesung: E-11 – Erholung/Muskelkater bleiben Pflicht, Normalfall 5 Taps
    - was: Leeren eines gewählten Werts ist mit reinen Radio-Knöpfen nicht möglich
      loesung: kleines Skript (Tippen auf den gewählten Wert leert); ohne JavaScript bleibt die Auswahl bedienbar
T4:
  status: erledigt
  ergebnis: >
    Training\Checkin\MorningStatus (reine Funktionen, Unit-Tests für alle 10 Fälle aus Abschnitt 8, Wochenausgangswert,
    Abklärung) und MorningChecks (Tage aus der Datenbank, Vortage für die Steigung, Wochenbeginn für den Ausgangswert,
    erledigte Einheiten vom Vortag). Kalendertage in der Zeitzone des Athleten; Test über die Umstellung 25.10.2026.
T5:
  status: erledigt
  ergebnis: >
    MCP-Tool get_morning_checks (days 7–90, Standard 14) im Format 6.1 plus „regeln“ und „hinweis“; in „tage“ sind leere
    Angaben weggelassen (Antwortbudget 8.3). get_week_overview: checkin.morgentest mit tage (Steuerwert, Ampel),
    tage_gruen, abdeckung_pct. get_pain_history unverändert (E-10). Briefing: E-12.
abnahme:
  - 2026-09-28: Erfassung auf dem Smartphone und Ampel auf der Startseite durch Philipp bestätigt
  - offen: get_morning_checks über den Claude-Connector (T5), Offline-Erfassung
T6:
  status: erledigt
  ergebnis: CHANGELOG 0.16.0, README, Konzept (D-53, AP-12, 7, 7.2, 8.2, 10), Datenmodell, Branding, Prüfprotokoll
```

