# Auftrag: Wissenskarten aus der Literatur (AP-06, Schritte 13.1 (2)–(5))

Stand: 2026-09-29 · Gehört zu `docs/konzept/konzept-ki-personal-trainer.md` (Abschnitte 13.1, 13.2, AP-06). Dieses Dokument regelt, **womit**, **in welchen Sitzungen** und **mit welcher Prüfung** die sechs Zieldateien in `docs/wissen/` entstehen. Es ändert nichts an Literaturauswahl (13.2), Kartenzuschnitt (AP-06 Punkt 3) oder den Evidenzstufen (D-31).

Kennungen in diesem Dokument: Entscheidungen `W-nn`, Unterpunkte `U1 …`, Lücken `LK-nn`, offene Fragen `WQ-nn`. IDs für Literatur (`L-…`), Entscheidungen des Hauptkonzepts (`D-…`), Fragen (`Q-…`) und Verifikationen (`V-…`) vergibt weiterhin **nur** das Hauptkonzept bei der Einarbeitung eines Übergabedokuments.

---

## 0. Ausgangslage und Rollen

Vorhanden (AP-06 Status 2026-09-28): Literaturauswahl aller Blöcke bestätigt; 36 Volltexte sortiert; Kapitel-PDFs für 7 Bücher unter `docs/literatur/<block>/<ID>_kapitel/` (D-51). Offen: vier Bücher (L-T1-01, L-T1-07, L-T3-08, L-A01 in 8./9. Aufl.), zehn Artikel ohne freien Zugang, Karten-Template, Karten.

Rollen in diesem Auftrag:

| rolle | wer | aufgabe |
|---|---|---|
| Athlet | Philipp | Beschaffung (D-26), Bestätigung von Entscheidungen, Stichprobenprüfung (Abschnitt 6), Ablage/Commit, Spiegelung ins Projektwissen |
| Extraktion | Claude Code, Modell **Opus** | Schritt (2): Extraktion je Kapitel nach Template (Abschnitt 4) |
| Gegenprüfung | eigener Agent, Modell **Fable**, frischer Kontext | Schritt (3): Prüfung der Extraktion gegen das PDF (Abschnitt 5) |
| Synthese | Chat-Sitzung außerhalb des Trainingsprojekts, Modell Fable | Schritt (4): eine Sitzung je Zieldatei, Dialog mit dem Athleten, Übergabedokument |
| Planende Instanz | Projekt-Chat | Einarbeitung der Übergabedokumente ins Hauptkonzept, ID-Vergabe, Statuspflege |

Der Trainer-Chat (Planung mit Trainingsdaten) ist an keinem Schritt beteiligt; er nutzt die fertigen Karten (D-10, D-13).

---

## 1. Entscheidungen

| id | entscheidung | begründung | datum |
|---|---|---|---|
| W-01 | Extraktion (Schritt 2) läuft in **Claude Code** mit Modell **Opus**, ein Kapitel je Unteragent bzw. je frischem Kontext. | Kapitel-PDFs liegen im Repo; Ergebnisse landen direkt als Dateien; 30–40 Kapitel sind nur mit frischem Kontext je Kapitel zuverlässig. Lesen/strukturieren braucht kein Spitzenmodell. Bestätigt durch Athlet 2026-09-29. | 2026-09-29 |
| W-02 | Gegenprüfung (Schritt 3) durch einen **unabhängigen Agenten mit anderem Modell (Fable)**, ohne Zugriff auf den Kontext der Extraktion. Eingabe: Extraktionsdatei + Kapitel-PDF. | Eine Instanz, die ihre eigene Extraktion prüft, ist keine Prüfung. Modellwechsel senkt gleichlaufende Fehler. Vorgabe des Athleten 2026-09-29. | 2026-09-29 |
| W-03 | Zusätzlich prüft der **Athlet stichprobenartig 20 Aussagen je Block** am PDF (Abschnitt 6): übergreifend (beide Zieldateien gemeinsam), T1, T2, T3, R – fünf Blöcke, 100 Aussagen. | Vorgabe des Athleten 2026-09-29 (WQ-01 entschieden). Menschliche Stichprobe deckt Fehler ab, die beide Modelle teilen (Tabellen, Einheiten, Studienpopulation). | 2026-09-29 |
| W-04 | Synthese (Schritt 4) in **einer Chat-Sitzung je Zieldatei**, außerhalb des Trainingsprojekts, sequenziell in der Reihenfolge übergreifend (Belastung/Monitoring) → übergreifend (Planung/kombiniert) → T1 → T2 → T3 → R. Keine parallelen Synthese-Sitzungen. | Entscheidungen zu Widersprüchen und Grenzen sind Dialogarbeit. Parallele Sitzungen haben am 27.09. zu ID-Kollisionen geführt. Übergreifend zuerst, weil T1–T3 auf Zonenmodell und Belastungsmonitoring verweisen. | 2026-09-29 |
| W-05 | Zwischenergebnisse (Extraktionen, Prüfprotokolle) liegen unter `docs/extraktion/` im Repo, **nicht** unter `docs/wissen/` und **nie** im Projektwissen. Gespiegelt werden ausschließlich die sechs Zieldateien. | Bündelungs-/Budgetregel 13.1 (Retrieval-Modus ab etwa 13 Dateien). Extraktionen sind Arbeitsmaterial mit Seitenverweisen, kein Trainerwissen. | 2026-09-29 |
| W-06 | `konfidenz` wird **je Kernaussage** gesetzt (Regel 5.4) und in der Synthese zu einem Kartenwert verdichtet. Eine Aussage ohne bestandene Gegenprüfung bekommt höchstens `mittel`, ohne Seitenangabe höchstens `niedrig`. | 13.1 (3): konfidenz erst nach Prüfung. Je-Aussage-Werte machen die Grenzen nachvollziehbar (D-13). | 2026-09-29 |
| W-07 | Jede Karte wird als **Fassung 1 fertiggestellt, auch wenn Lücken bestehen**; Lücken werden markiert (Abschnitt 8), nicht abgewartet. Nachträge erzeugen Fassung 2 ff. | Verhindert eine offene Literaturschleife; der Trainer-Chat kann mit gekennzeichneten Lücken arbeiten (D-13: „Einschätzung ohne Quelle“). | 2026-09-29 |
| W-08 | Literaturbedarf aus der Synthese wird **im Übergabedokument als Nachtrag ohne endgültige L-ID** gemeldet (Arbeitsschlüssel `neu-<n>`); die planende Instanz vergibt die IDs. Synthese-Sitzungen editieren weder Hauptkonzept noch 13.2. | Regel AP-06 Punkt 1; ID-Kollisionen vermeiden. | 2026-09-29 |
| W-09 | Wörtliche Übernahmen aus den Quellen nur, wenn der Wortlaut entscheidend ist (Definitionen, Schwellenwerte), kurz, mit Seite. Sonst Paraphrase. Karten sind eigene Zusammenfassungen (D-12, D-23). | Urheberrecht; Karten liegen im Projektwissen und ggf. später offen. | 2026-09-29 |
| W-10 | Eine Quelle wird erst extrahiert, wenn sie als durchsuchbares PDF vorliegt (D-26). Die **Synthese einer Zieldatei startet erst, wenn alle ihr zugeordneten Quellen mit Status `ausgewaehlt` (AP-06 Punkt 3, Kern) beschafft, extrahiert und gegengeprüft sind**. Extraktion und Gegenprüfung der bereits vorhandenen Quellen laufen davon unabhängig vorab. Optionale Quellen (`optional`, „bei Bedarf“) blockieren nicht. | Entscheidung des Athleten 2026-09-29 (WQ-05, Option A) gegen die Empfehlung (Start bei vorliegenden Stufe-A-Kernquellen). Folge: der Zeitpunkt der Synthese hängt an der Beschaffung von L-T1-01, L-T1-07, L-T3-08, L-A01 (8./9. Aufl.) und den zehn nicht frei zugänglichen Artikeln; W-07 gilt weiterhin für Lücken, die **trotz** vollständiger Auswahl bestehen (8.1 a–c, e). | 2026-09-29 |
| W-11 | Extraktionen und Prüfprotokolle (`docs/extraktion/`) werden **ins private Git-Repo** eingecheckt. | Eigene Zusammenfassungen, kein Volltext (D-23); Nachtragsrunden und Gegenprüfung greifen direkt zu; Historie. WQ-04 entschieden. | 2026-09-29 |
| W-12 | Das Tokenbudget je Zieldatei wird **nach der ersten fertigen Zieldatei gemessen** und dann im Hauptkonzept (13.1) festgeschrieben; bis dahin gelten die Richtwerte in Abschnitt 3 nur als Orientierung. | Richtwerte sind Schätzung; das Gesamtlimit 40 000 (13.1) ist verbindlich. WQ-02 entschieden. | 2026-09-29 |
| W-13 | Die Gegenprüfung läuft in **Claude Code mit Modellwechsel auf Fable**, als eigener Lauf ohne den Kontext der Extraktion. | Dateien im Repo, Protokoll landet direkt; Fable ist dort wählbar (Athlet). WQ-03 entschieden. | 2026-09-29 |

---

## 2. Artefakte und Ablage

```yaml
eingabe:
  kapitel_pdfs: docs/literatur/<block>/<L-ID>_kapitel/<L-ID>_k<nn>_<kurztitel>.pdf   # D-51, vorhanden
  artikel_pdfs: docs/literatur/<block>/<L-ID>_<autor>-<jahr>_<kurztitel>.pdf          # Artikel werden nicht geteilt (< 40 Seiten)
  literaturliste: Hauptkonzept 13.2 (IDs, Stufe, Themenfelder) und docs/literatur/README.md
zwischenergebnisse:                        # W-05, nicht ins Projektwissen
  extraktion:  docs/extraktion/<block>/<L-ID>/<L-ID>_k<nn>.md        # eine Datei je Kapitel bzw. je Artikel (<L-ID>_k00.md)
  pruefung:    docs/extraktion/<block>/<L-ID>/<L-ID>_k<nn>_pruefung.md
  stichprobe:  docs/extraktion/stichprobe/<block>_stichprobe.md       # je Block (W-03), Vorlage für den Athleten mit dessen Ergebnis
  luecken:     docs/extraktion/luecken.md                             # Lückenregister (Abschnitt 8)
  uebergabe:   docs/extraktion/uebergabe/<zieldatei>_uebergabe.md     # Ergebnis der Synthese-Sitzung
ergebnis:
  karten:      docs/wissen/<zieldatei>.md                             # sechs Dateien nach AP-06 Punkt 3, gespiegelt ins Projektwissen
zieldateien:                              # Quellen je Datei: AP-06 Punkt 3 (unverändert)
  - uebergreifend-belastung-monitoring-erholung
  - uebergreifend-planung-kombiniertes-training
  - t1-ausdauer
  - t2-kraft-haltung
  - t3-klettern
  - r-reha-praevention
```

Block-Ordner: `uebergreifend`, `t1`, `t2`, `t3`, `r` (wie `docs/literatur/`).

---

## 3. Karten-Template (Zieldatei)

Eine Zieldatei = eine Markdown-Datei mit Front matter und mehreren **Karten** (Themen) als Abschnitte. Struktur nach 13.1: Kernaussagen → Zahlen/Protokolle → Anwendung im Plan → Grenzen/Widersprüche → Lücken.

```markdown
---
datei: t1-ausdauer
geltungsbereich: [T1]                 # T1 | T2 | T3 | R | uebergreifend; Liste
fassung: 1                            # zählt je Nachtrag hoch (W-07); keine Software-Version
stand: 2026-10-xx
konfidenz: mittel                     # Kartenwert nach 5.4; niedrigster Wert der Karten dieser Datei
quellen:                              # nur Quellen, aus denen Aussagen stammen; Details in 13.2
  - id: L-T1-02
    stufe: A
    kurz: "Seiler 2010, Sports Med"
    volltext: docs/literatur/t1/L-T1-02_…pdf
tokens_ca: 8000
karten: [intensitaetsverteilung, bergauf-skitour, intervalle]
---

# T1 Ausdauer

## Karte: intensitaetsverteilung
themenfelder: [intensitaetsverteilung, zonenmodell]
konfidenz: hoch

### Kernaussagen
| id | aussage | quelle | stelle | typ | stufe | konfidenz |
|---|---|---|---|---|---|---|
| T1-IV-01 | … (eine prüfbare Aussage, mit Zahl und Einheit, falls vorhanden) | L-T1-02 | S. 12; Abb. 2 | befund | A | hoch |
| T1-IV-02 | … | L-A01 | S. 234 | modell | B | mittel |

### Zahlen und Protokolle
- … (jede Zahl mit Einheit, Population, Dauer, Quelle+Stelle; Elite/Freizeit kennzeichnen)

### Anwendung im Plan
- … (Regelkandidaten für AP-07, formuliert als „wenn … dann …“; Verweis auf Kernaussage-IDs; keine neuen Fakten)

### Grenzen und Widersprüche
- … (Übertragung Elite → Freizeitsportler, drei Bereiche; Widersprüche zwischen Quellen mit beiden Positionen und Stellen; Modellannahmen)

### Lücken
- LK-nn (kurz), status, siehe docs/extraktion/luecken.md
```

Feldregeln:

- `id` je Kernaussage: `<Gebiet>-<Kartenkürzel>-<nn>`, stabil über Fassungen; gelöschte IDs werden nicht neu vergeben.
- `typ`: `befund` (empirisches Ergebnis) · `modell` (theoretischer Schluss, Lehrbuchmodell) · `praxis` (Empfehlung aus Praxisquelle, D-25/D-31; auch Erfahrungsberichte mit Vermerk „Einzelfall“) · `definition` · `methodik` (nur in Extraktionen: Angaben zu Studiendesign, Suche, Einschluss, Population – keine Wirkaussage; Entscheidung Athlet 2026-09-29).
- `stelle`: Seite, ggf. Tabelle/Abbildung; bei Artikeln Seite oder Abschnitt; Buch mit Auflage (13.2).
- `stufe` aus 13.2 (A/B/C); Stufe C nie alleiniger Beleg für Belastungsparameter (D-31).
- Themenfeld-Vokabular T3 aus 13.2.4; für T1/T2/R vergibt die erste Synthese-Sitzung das Vokabular und meldet es im Übergabedokument (Aufnahme ins Hauptkonzept durch die planende Instanz).
- Pflichtabschnitte „Grenzen“ nach D-25 (T1), D-54 (c) und D-62 (e) (T2), D-61 (e) (R); „Evidenz: begrenzt“ in T3 (13.2.4).
- Budget je Datei (Orientierung bis zur Messung nach W-12): übergreifend 2 × 3 500, T1 8 000, T2 7 000, T3 5 000, R 5 000 Tokens; verbindlich ist nur das Gesamtlimit 13.1.

---

## 4. Extraktion je Kapitel (Schritt 2, Opus in Claude Code)

### 4.1 Auftrag an die Extraktions-Instanz

Eingabe je Lauf: genau **ein** Kapitel-PDF (bzw. ein Artikel), die Zeile der Quelle aus 13.2 (ID, Stufe, Zweck, Themenfelder) und dieses Template. Kein Zugriff auf andere Extraktionen, keine Vorkenntnis aus anderen Kapiteln. Ausgabe: `docs/extraktion/<block>/<L-ID>/<L-ID>_k<nn>.md`.

Regeln (13.1 (2), präzisiert):

1. Nur Textinhalt des Kapitels; keine Ergänzung aus eigenem Wissen. Was nicht im Kapitel steht, steht nicht in der Extraktion.
2. Jede Aussage mit Seitenzahl (Buch) bzw. Seite/Abschnitt (Artikel). Ohne Seite → Feld `stelle: unklar` und Aufnahme in `offene_stellen`.
3. Zahlen exakt mit Einheit, Streuung (SD/CI, falls angegeben), Population (n, Niveau, Alter, Geschlecht), Dauer/Protokoll.
4. Modellschlüsse und Lehrmeinungen markieren (`typ: modell`), Praxisempfehlungen (`typ: praxis`), reine Studienbeschreibungen (`typ: methodik`). Keine abgeleiteten oder „Standard“-Werte ergänzen; fehlt eine Angabe, steht „im Text nicht genannt“.
5. Tabellen und Abbildungen: Inhalt in Worte fassen, Nummer angeben; bei unsicherer Textextraktion (zerfallene Tabelle) `unsicher: true`.
6. Wörtliche Übernahme nur nach W-09.
7. Lücken des Kapitels: Was zum Zweck der Quelle (13.2 `zweck`) erwartet, aber nicht enthalten ist.
8. Relevanzfilter: Aussagen ohne Bezug zu den Themenfeldern der Quelle oder zum Athletenprofil (Freizeitsport, Trailrunning/Skitouren, Kraft/Haltung, Bouldern, Reha Patellasehne/Sprunggelenk) werden nur als Überschrift gelistet (`ausgelassen`), nicht extrahiert.
9. PDF-Lesen: nativ, falls die Umgebung PDF liest; sonst Textextraktion mit Layout-Erhalt. Das Verfahren steht im Kopf der Datei (`lesemethode`).

### 4.2 Extraktionstemplate

```markdown
---
quelle: L-T1-02
kapitel: k03
kapiteltitel: "…"
seiten: 45-71
stufe: A
lesemethode: pdf_nativ | pdftotext_layout | markdown_epub   # markdown_epub: EPUB-Quelle, Eingabe Kapitel-Markdown (D-71); Stelle „S. n“ aus den Seitenmarken, sonst Kapitel und Abschnitt
modell: opus
datum: 2026-10-xx
---

## Aussagen
| nr | aussage | stelle | typ | zahlen | population | unsicher |
|---|---|---|---|---|---|---|
| 1 | … | S. 47 | befund | "80/20; 4–6 Einheiten/Woche" | Elite-Läufer, n=… | false |

## Zahlen und Protokolle
- (nr) …: Wert, Einheit, Streuung, Bedingung, Stelle

## Definitionen
- Begriff (S. …): …

## Lücken des Kapitels
- …

## Ausgelassen (Überschriften ohne Relevanz)
- …

## Offene Stellen
- nr …: Seite nicht bestimmbar, weil …
```

---

## 5. Gegenprüfung (Schritt 3, Fable, unabhängig)

### 5.1 Ablauf

- Eigener Agent mit Modell Fable, frischer Kontext; Eingabe: Extraktionsdatei + dasselbe Kapitel-PDF + dieses Kapitel (Abschnitt 5). Kein Zugriff auf den Extraktionsverlauf. Umgebung: Claude Code mit Modellwechsel auf Fable (W-13), eigener Lauf je Kapitel.
- Umfang je Kapitel: **alle** Aussagen mit `zahlen` oder `unsicher: true` und **alle** mit `typ: befund`, die Dosierung/Schwellen betreffen; von den übrigen mindestens 5 oder 20 %, nach Zufall (Nummern im Protokoll nennen).
- Ausgabe: `docs/extraktion/<block>/<L-ID>/<L-ID>_k<nn>_pruefung.md`.

### 5.2 Prüfergebnis je Aussage

`ok` · `stelle_falsch` (Inhalt stimmt, Seite nicht) · `zahl_falsch` · `sinn_verzerrt` (Aussage überdehnt, Population/Bedingung fehlt) · `nicht_gefunden` · `nicht_pruefbar` (Tabelle nicht lesbar).

```markdown
---
quelle: L-T1-02
kapitel: k03
modell: fable
geprueft: 23 von 41
datum: 2026-10-xx
ergebnis: ok=19, stelle_falsch=2, zahl_falsch=1, sinn_verzerrt=1, nicht_gefunden=0
freigabe: ja | nein   # nein bei ≥ 2 zahl_falsch/sinn_verzerrt oder ≥ 1 nicht_gefunden → Kapitel erneut extrahieren (5.3)
---
| nr | ergebnis | befund des prüfers | korrektur |
|---|---|---|---|
| 7 | zahl_falsch | PDF S. 52 nennt 6–8 %, Extraktion 8–10 % | 6–8 % |
```

### 5.3 Korrekturregel

- Freigabe `ja`: Korrekturen aus dem Protokoll werden in die Extraktion übernommen (durch die Extraktions-Instanz in einem kurzen Lauf, mit Vermerk `korrigiert_nach_pruefung: true`), nicht neu geprüft.
- Freigabe `nein`: Kapitel neu extrahieren (frischer Kontext), Zweitprüfung mit gleichem Umfang. Nach zweitem `nein` → Eintrag ins Lückenregister (`typ: quelle_unzuverlaessig`), Entscheidung durch den Athleten.
- Systematische Fehlerbilder (z. B. Seitenversatz durch Vorspann) werden im Protokoll als `muster` benannt und für alle Kapitel der Quelle korrigiert.

### 5.4 Konfidenz je Kernaussage (W-06)

| stufe | gegenprüfung | konfidenz |
|---|---|---|
| A | ok | hoch |
| A | nicht geprüft / stelle_falsch (korrigiert) | mittel |
| B | ok | mittel |
| B | nicht geprüft | niedrig |
| C | beliebig | niedrig |
| jede | nicht_pruefbar oder Stelle unklar | niedrig |

Kartenwert: `hoch`, wenn alle Kernaussagen mit Zahlen `hoch` sind und Stufe A überwiegt; sonst `mittel`; `niedrig`, wenn Kernaussagen mit Zahlen nur Stufe C haben oder mehr als ein Drittel `niedrig`.

---

## 6. Stichprobe des Athleten (20 je Block, W-03)

- Blöcke: übergreifend (beide Zieldateien zusammen, je 10), T1, T2, T3, R → 100 Aussagen.
- Zeitpunkt: nach der Synthese der Zieldatei(en) des Blocks, vor der Spiegelung ins Projektwissen (die Kernaussagen stehen dann fest). Für übergreifend erst nach beiden Zieldateien.
- Auswahl (durch die Synthese-Sitzung, deterministisch beschrieben): aus jeder Quelle des Blocks mindestens eine Aussage; alle Aussagen, die in „Anwendung im Plan“ eine Zahl tragen, bis 20 erreicht sind; Rest per Zufall aus den übrigen Kernaussagen. Bei mehr als 20 Zahlenaussagen: Vorrang für Dosierung/Schwellen/Progression.
- Vorlage `docs/extraktion/stichprobe/<block>_stichprobe.md`:

```markdown
| nr | kernaussage-id | aussage (kurz) | quelle | stelle | pdf | ergebnis (ok / falsch / unklar) | bemerkung |
|---|---|---|---|---|---|---|---|
| 1 | T1-IV-01 | … | L-T1-02 | S. 12, Abb. 2 | docs/literatur/t1/L-T1-02_k01….pdf | | |
```

- Schwellen: 0–1 `falsch` → Freigabe; 2–3 `falsch` → betroffene Quellen erneut gegenprüfen (5.1, voller Umfang), Karte Fassung 1 nach Korrektur; ≥ 4 `falsch` → Zieldatei zurück in die Synthese, Ursache im Statusblock dokumentieren.
- Ergebnis wird im Übergabedokument und im Statusblock (Abschnitt 11) festgehalten.

---

## 7. Synthese-Sitzung (Schritt 4, eine je Zieldatei)

### 7.1 Briefing (Sitzungsstart)

Die Sitzung erhält: Hauptkonzept (aktuelle Fassung, mindestens 13.1, 13.2 des Blocks, AP-06, betroffene D-Entscheidungen), dieses Dokument, alle freigegebenen Extraktionen der Zieldatei (Abschnitt 2), das Lückenregister, das Karten-Template. Nicht: Trainingsdaten, Projektwissen des Trainingsprojekts, PDFs (nur bei Rückfragen einzelne Seiten).

Startsatz: „Du erstellst die Zieldatei `<name>` nach Abschnitt 3 des Auftrags Wissenskarten. Grundlage sind ausschließlich die Extraktionen. Du vergibst keine L-, D-, Q-, V-IDs und editierst kein Konzeptdokument. Entscheidungen zu Widersprüchen, Grenzen und Regelkandidaten legst du mir einzeln zur Bestätigung vor.“

### 7.2 Ablauf

1. Kartenzuschnitt der Zieldatei bestätigen (Kartenliste aus AP-06 Punkt 3; Änderungen nur mit Bestätigung).
2. Je Karte: Kernaussagen aus den Extraktionen zusammenführen (Aussage-IDs vergeben, Extraktions-nr als Herkunft notieren), Zahlen/Protokolle, dann Widersprüche zwischen Quellen sammeln.
3. Widersprüche und Übertragung Elite → Freizeit dem Athleten vorlegen: beide Positionen mit Stelle, Vorschlag für die „geltende Regel“ (Regelkandidat für AP-07), Bestätigung abwarten.
4. Lücken erfassen (Abschnitt 8) und sofort klassifizieren.
5. Budget prüfen (Abschnitt 3); bei Überschreitung kürzen: zuerst „Ausgelassen“-Inhalte, dann Redundanz zu anderen Zieldateien (Verweis statt Wiederholung).
6. Stichprobenvorlage erzeugen (Abschnitt 6).
7. Übergabedokument schreiben (7.3).

### 7.3 Übergabedokument

```markdown
---
zieldatei: t1-ausdauer
fassung: 1
sitzung: 2026-10-xx, Fable
---
## Ergebnis
- Datei docs/wissen/t1-ausdauer.md, Karten: …, Tokens ca. …
## Entscheidungen des Athleten in dieser Sitzung   # von der planenden Instanz als D-… einzuarbeiten
- E1: … (Widerspruch X: geltende Regel …; Begründung; Stellen)
## Regelkandidaten für AP-07
- RK1: „wenn … dann …“ ← T1-IV-01, T1-IV-04; konfidenz …
## Themenfeld-Vokabular (neu)
- …
## Lücken (neu oder geändert)   # Abschnitt 8, Einträge für docs/extraktion/luecken.md
- LK-neu-1: …
## Literaturbedarf (Nachtrag, ohne L-ID)   # W-08
- neu-1: Zitat, PMID/DOI, Stufe, Zweck, gehört zu LK-…, Zugang
## Stichprobe
- Vorlage: docs/extraktion/stichprobe/t1_stichprobe.md (20 Aussagen; übergreifend: Anteil dieser Zieldatei)
## Offene Punkte / Probleme
- …
```

Die planende Instanz arbeitet das Übergabedokument ins Hauptkonzept ein (D-IDs, L-IDs für Nachträge, Statusblock AP-06, Themenfelder) und trägt neue `LK`-Einträge ins Register ein.

---

## 8. Lücken-Workflow (Wissenslücken und Literaturbedarf)

### 8.1 Auslöser

Eine Lücke liegt vor, wenn

- (a) eine Planungsfrage des Zwecks der Zieldatei (13.2 `zweck`, AP-06 Kartenzuschnitt) durch keine Kernaussage beantwortet wird,
- (b) zwei Quellen sich widersprechen und keine Quelle der Auswahl den Widerspruch auflöst,
- (c) eine Zahl/Dosierung nur aus Stufe C belegt ist (D-31) oder nur für Elite gilt und die Übertragung unbelegt ist,
- (d) eine ausgewählte Quelle als unzuverlässig ausgeschieden ist (5.3) – nicht beschaffte Kernquellen sind nach W-10 kein Lückenfall, sondern verzögern die Synthese; nicht beschaffte optionale Quellen werden als `typ: d` geführt,
- (e) die Gegenprüfung oder die Stichprobe eine Aussage entfernt hat, die für eine Karte tragend war.

Entdeckungsorte: Synthese-Sitzung (Regelfall), AP-07 (Regel ohne Beleg), Trainer-Chat (Planungsfrage ohne Karte, D-13 „Einschätzung ohne Quelle“).

### 8.2 Klassifikation

| klasse | bedeutung | behandlung |
|---|---|---|
| `blockierend` | eine Regel in AP-07 oder eine anstehende Blockplanung hängt daran | Recherche in der Sitzung (8.4), Nachtrag mit Vorrang |
| `ergaenzend` | würde Konfidenz erhöhen oder Grenzen schärfen, Planung geht auch ohne | registrieren, Bearbeitung gesammelt (8.6) |
| `akzeptiert` | Athlet entscheidet, ohne Beleg zu arbeiten; Aussage bleibt `konfidenz: niedrig` bzw. „Einschätzung ohne Quelle“ | nur Register und Karte |

Die Klasse legt der Athlet fest; die Sitzung schlägt vor.

### 8.3 Register `docs/extraktion/luecken.md`

```yaml
- id: LK-01
  gebiet: t1-ausdauer
  karte: intensitaetsverteilung
  frage: "Intensitätsverteilung bei ≤ 4 Ausdauereinheiten/Woche (Freizeit), nicht Elite"
  typ: a | b | c | d | e        # nach 8.1
  klasse: blockierend | ergaenzend | akzeptiert
  entdeckt_in: synthese | ap07 | trainerchat
  entdeckt_am: 2026-10-xx
  betroffen: [T1-IV-03, RK2]     # Kernaussage-IDs, Regelkandidaten
  status: offen | recherche | vorgeschlagen | beschaffung | extraktion | nachtrag | geschlossen | akzeptiert
  vorhandene_option: L-T1-09     # Quelle aus optional/zurueckgestellt/„bei Bedarf“ (13.4), falls passend
  kandidaten: [neu-1]            # Arbeitsschlüssel aus dem Übergabedokument, bis L-ID vergeben
  quelle_neu: null               # L-ID nach Vergabe
  geschlossen_am: null
  geschlossen_mit: null          # Fassung der Zieldatei
```

Statusfolge: `offen` → (`recherche`) → `vorgeschlagen` (Kandidat im Übergabedokument) → Athlet bestätigt → `beschaffung` → `extraktion` (Abschnitte 4–5 nur für diese Quelle) → `nachtrag` (8.6) → `geschlossen`. Alternativ `akzeptiert` an jeder Stelle.

### 8.4 Behandlung in der Synthese-Sitzung

1. **Vorhandenen Pool zuerst prüfen**: Quellen mit Status `optional`, `zurueckgestellt`, die „bei Bedarf“-Listen aus 13.4 und Sammelplatzhalter (z. B. L-T3-17). Passt eine Quelle: `vorhandene_option` setzen, Athlet bestätigt in der Sitzung → Status `beschaffung` (falls kein Volltext) oder `extraktion`. Keine neue Literatursuche.
2. Sonst **PubMed-Recherche in der Sitzung** (PubMed-Connector, 13.1): höchstens drei Suchen je Lücke, Vorrang systematische Reviews/Konsens (D-22), dann RCTs; Ergebnis als Kandidaten mit PMID/DOI, Stufe, Zugang, `zweck` und Bezug zur `LK`. Bibliografische Angaben nur aus PubMed, nie aus dem Gedächtnis (D-13, V-11-Erfahrung).
3. Kandidaten als **Nachtrag ohne L-ID** ins Übergabedokument (W-08), Lücke auf `vorgeschlagen`.
4. **Karte trotzdem fertigstellen** (W-07): betroffene Kernaussagen tragen `konfidenz: niedrig` und den Vermerk `luecke: LK-nn`; „Anwendung im Plan“ formuliert den Regelkandidaten als vorläufig („bis LK-nn geschlossen: …“).
5. Kein Kandidat gefunden: Lücke bleibt `offen`, Vorschlag an den Athleten: `akzeptiert` oder Suche außerhalb PubMed (Bücher, Verbandsliteratur) durch den Athleten.

Begrenzung: Je Zieldatei höchstens **fünf** `blockierend`-Lücken in Recherche; weitere werden `ergaenzend` und in 8.6 gesammelt (Budget der Sitzung, Tokenbudget 13.1).

### 8.5 Behandlung außerhalb der Synthese

- **AP-07**: Regel ohne Beleg → `LK` mit `entdeckt_in: ap07`, Regel erhält `konfidenz: niedrig`, Verweis auf die LK-ID.
- **Trainer-Chat**: Claude kennzeichnet die Aussage nach D-13 und formuliert am Ende der Antwort einen fertigen Registereintrag (YAML nach 8.3, `entdeckt_in: trainerchat`, ohne ID); der Athlet legt ihn im Register ab. Der Trainer-Chat sucht keine Literatur und schreibt kein Repo.

### 8.6 Nachtragsrunde (Fassung 2 ff.)

- Auslöser: mindestens eine `blockierend`-Lücke mit beschaffter Quelle **oder** drei `ergaenzend`-Lücken mit beschafften Quellen für dieselbe Zieldatei **oder** Freigabe durch den Athleten.
- Ablauf: Extraktion und Gegenprüfung nur der neuen Quelle(n) (Abschnitte 4–5) → kurze Synthese-Sitzung (Briefing wie 7.1, zusätzlich die bestehende Zieldatei und die betroffenen LK-Einträge) → Karte ändern: Kernaussagen ergänzen, `konfidenz` neu setzen, `luecke:`-Vermerke entfernen, `fassung` erhöhen, Änderungsvermerk in der Datei (`## Änderungen`: Datum, Fassung, LK-IDs, geänderte Aussage-IDs) → Stichprobe **3 Aussagen je neuer Quelle** durch den Athleten → Übergabedokument (Fassung n) → Spiegelung.
- Regelkandidaten, die sich ändern, werden im Übergabedokument als „RK-Änderung“ gemeldet, damit AP-07 nachzieht.
- Nach der Nachtragsrunde bleibt das Budget der Zieldatei (Abschnitt 3) einzuhalten; Erweiterung nur mit Kürzung an anderer Stelle.

### 8.7 Wann eine Lücke geschlossen ist

`geschlossen`, wenn (1) die neue Quelle extrahiert und gegengeprüft ist, (2) die betroffenen Kernaussagen mit Stelle und Stufe belegt sind, (3) der Regelkandidat ohne „vorläufig“ formuliert ist, (4) die Stichprobe für die Quelle bestanden ist. Sonst bleibt der Status stehen; „teilweise geschlossen“ gibt es nicht – dann wird die Lücke in eine geschlossene und eine neue, engere `LK` geteilt.

---

## 9. Unterpunkte

Reihenfolge: U1 → U2 (je Quelle: U2 vor U3) → U3 → U4 (je Zieldatei, sequenziell nach W-04) → U5 → U6 → U7; U8 bei Bedarf; U9 laufend.

### U1 · Vorbereitung (Athlet + planende Instanz)
- Ordner `docs/extraktion/` mit README (Ablage nach Abschnitt 2), leeres Register `luecken.md`, dieses Dokument nach `docs/konzept/wissenskarten.md`.
- Artikel-PDFs prüfen: Text durchsuchbar (D-26); Lizenzprüfung L-T2-15, L-T2-16, L-T2-22, L-T2-23, L-T2-25 (AP-06 offene Punkte) vor Ablage.
- Liste der zu extrahierenden Dateien je Zieldatei (aus AP-06 Punkt 3 und `docs/literatur/README.md`), mit Kennzeichnung „fehlt“; je Zieldatei ist damit sichtbar, welche Kernquellen die Synthese noch blockieren (W-10).
- **Abnahme:** Liste liegt vor; jede Datei darin existiert und ist durchsuchbar; fehlende Quellen stehen im Register.

### U2 · Extraktion (Claude Code, Opus)
- Auftragstext für Claude Code aus Abschnitt 4 (ein Lauf je Kapitel; Batch je Quelle mit Unteragenten; Ausgabe nach Abschnitt 2).
- Reihenfolge nach W-04: Quellen der Zieldatei übergreifend-belastung zuerst; vorhandene Quellen werden sofort extrahiert, unabhängig davon, ob die Zieldatei schon synthesefähig ist (W-10).
- Nach jeder Quelle: Vollständigkeitsprüfung (jedes Kapitel hat eine Datei, `offene_stellen` gelistet).
- **Abnahme:** je Quelle alle Kapitel extrahiert; keine Datei ohne `lesemethode`; Stichprobe des Athleten: eine Extraktionsdatei je Quelle geöffnet und plausibel (formal, nicht inhaltlich).

### U3 · Gegenprüfung (Fable)
- Auftragstext aus Abschnitt 5; Claude Code, Modell Fable (W-13).
- Korrekturläufe nach 5.3; Muster je Quelle dokumentieren.
- **Abnahme:** je Kapitel ein Prüfprotokoll mit `freigabe`; keine Extraktion ohne Freigabe geht in U4.

### U4 · Synthese je Zieldatei (Chat, Fable)
- Startbedingung W-10: alle Kernquellen der Zieldatei beschafft, extrahiert, gegengeprüft (Liste aus U1).
- Briefing 7.1, Ablauf 7.2, Übergabedokument 7.3; Lücken nach Abschnitt 8.
- **Abnahme:** Zieldatei entspricht Abschnitt 3 (Front matter, Pflichtabschnitte, Budget); jede Kernaussage hat Quelle, Stelle, Stufe, konfidenz; Übergabedokument vollständig; Stichprobenvorlage vorhanden. Nach der ersten Zieldatei: Tokenzahl gemessen und der planenden Instanz gemeldet (W-12).

### U5 · Stichprobe des Athleten
- 20 Aussagen je Block nach Abschnitt 6 (übergreifend nach beiden Zieldateien); Ergebnis in die Vorlage.
- **Abnahme:** Schwelle 6 eingehalten oder Rückläufe nach 5.3/Abschnitt 6 dokumentiert.

### U6 · Einarbeitung (planende Instanz)
- Übergabedokumente ins Hauptkonzept: D-/L-/Q-/V-IDs, Statusblock AP-06, Themenfelder in 13.2, Register aktualisieren.
- Spiegelung der Zieldatei ins Projektwissen; Gesamtbudget 13.1 messen und im Statusblock notieren.
- **Abnahme:** Hauptkonzept und Register konsistent (jede LK-ID, jeder Nachtrag einmal; keine doppelten IDs); Projektwissen enthält genau die sechs Zieldateien (plus Regeln/Profil/Blockplan).

### U7 · Übergabe an AP-07
- Regelkandidaten aller Zieldateien gesammelt an AP-07 (mit Kernaussage-IDs, konfidenz, offenen LK-Verweisen).
- **Abnahme:** Liste liegt AP-07 vor; jeder Regelkandidat verweist auf mindestens eine Kernaussage oder eine LK-ID.

### U8 · Nachtragsrunden (bei Bedarf)
- Nach 8.6 je Zieldatei; Fassung erhöhen; Änderungsvermerk.
- **Abnahme:** 8.7 je Lücke; Stichprobe 3 je neuer Quelle.

### U9 · Dokumentation (laufend)
- Statusblock (Abschnitt 11) je erledigtem Unterpunkt; Probleme/Lösungen; `docs/extraktion/README.md`; Hauptkonzept 13.1 um den Verweis auf dieses Dokument ergänzen (durch die planende Instanz).

---

## 10. Offene Fragen

Alle fünf Fragen der Erstfassung wurden am 2026-09-29 mit dem Athleten geklärt; die Ergebnisse stehen als W-03, W-10 bis W-13 in Abschnitt 1.

| id | frage | entscheidung | status |
|---|---|---|---|
| WQ-01 | „20 je Gebiet“: Zieldatei oder Block? | Block (5 × 20 = 100; übergreifend gemeinsam) → W-03 | entschieden |
| WQ-02 | Tokenbudget je Zieldatei | Nach der ersten Zieldatei messen, dann festschreiben → W-12 | entschieden |
| WQ-03 | Umgebung der Gegenprüfung | Claude Code mit Modellwechsel auf Fable → W-13 | entschieden |
| WQ-04 | Extraktionen ins Repo? | Ja, privates Repo → W-11 | entschieden |
| WQ-05 | Synthese vor vollständiger Beschaffung? | Nein, warten bis alle Kernquellen beschafft sind (Option A, gegen die Empfehlung B) → W-10 | entschieden |

Derzeit keine offenen Fragen.

## 11. Status

```yaml
status: in_arbeit
begonnen: 2026-09-29
abgeschlossen: null
unterpunkte:
  U1: erledigt     # 2026-09-29, docs/extraktion/ mit README (Statustabellen) und luecken.md
  U2: in_arbeit    # je Quelle: siehe docs/extraktion/README.md; 287 Läufe (195 Buchkapitel, 92 Artikel); Zieldateien UB, UP, T1 und T2 extrahiert (2026-09-29)
  U3: offen
  U4: offen        # je Zieldatei: uebergreifend-belastung, uebergreifend-planung, t1, t2, t3, r, t4
  U5: offen
  U6: offen
  U7: offen
  U8: bei_bedarf
  U9: laufend
projektwissen_tokens_gemessen: null
probleme_loesungen:
  - datum: 2026-09-29
    was: Dieses Dokument kennt sechs Zieldateien; seit D-79 gibt es als siebte t4-beweglichkeit (Block T4, 15 Dateien, Kapitel-PDFs L-T4-32, L-T4-34); Relevanzfilter 4.1 Regel 8 nennt T4 nicht
    loesung: Entscheidung Athlet – T4 wird in U1/U2 aufgenommen, als letzte Zieldatei nach R (T4 verweist auf R und T2); W-04 und Abschnitt 2 bleiben im Wortlaut unverändert, die Abweichung gilt ab U1
  - datum: 2026-09-29
    was: Abschnitt 2 nennt Block-Ordner t1, t2, t3, r „wie docs/literatur/“; dort heißen sie t1-ausdauer, t2-kraft, t3-klettern, r-reha, t4-beweglichkeit
    loesung: Entscheidung Athlet – Ordner unter docs/extraktion/ wie docs/literatur/ (uebergreifend, t1-ausdauer, t2-kraft, t3-klettern, r-reha, t4-beweglichkeit)
  - datum: 2026-09-29
    was: Kapitelordner enthalten Vorspann (00) und Anhänge (9x)
    loesung: Entscheidung Athlet – in der Statustabelle gelistet, nicht extrahiert; Dateien, die Vorspann und Einleitung/Foreword bündeln (L-T1-08_00, L-T2-04_00, L-T3-09_00, L-T3-21_00), sind markiert – Extraktion vor U2 klären
  - datum: 2026-09-29
    was: CLAUDE.md verlangt die Pflege von docs/pruefung/pruefprotokoll.md; der Auftrag nennt docs/extraktion/README.md als Prüfdokument
    loesung: Entscheidung Athlet – im Prüfprotokoll (AP-06) nur ein Verweis-Eintrag, Details ausschließlich im README
  - datum: 2026-09-29
    was: Ausgangslage (Abschnitt 0) und W-10 beruhen auf einem älteren Stand – L-T1-01 nicht aufgenommen, L-T3-08 zurückgestellt (D-70), L-T1-07 liegt vor, 11 Bücher mit Kapitel-PDFs plus 4 EPUBs (D-71), von den nicht frei zugänglichen Artikeln fehlen nur L-T4-13 (optional) und L-T4-17 (Kern)
    loesung: Statustabellen nach aktuellem Konzeptstand; fehlende Kernquellen je Zieldatei – UB/UP L-A01 8./9. Aufl. (7. Aufl. vorläufig vorhanden), T3 L-T3-16 (Stufe C, ausgewaehlt – Zählung als Kernquelle bestätigen), T4 L-T4-17; T1, T2, R vollständig
  - datum: 2026-09-29
    was: U1-Prüfung Durchsuchbarkeit – pdftotext auf Seite 1 ist bei 56 PDFs leer (49 Kapitel-PDFs, 7 Gesamtbücher; meist Titelbild), Text ab Seite 2
    loesung: zusätzlich Seiten 2–3 geprüft; keine Datei nicht durchsuchbar; L-T1-08_09 (Programming) hat Text erst ab S. 5 und 6 von 14 Seiten fast ohne Text (Grafiken/Tabellen?) – Abbruchregel in U2 prüfen
  - datum: 2026-09-29
    was: Offene Punkte für U2 – EPUB-Quellen (L-T3-10, -19, -20, -21) haben Markdown statt PDF als Eingabe, lesemethode kennt nur pdf_nativ/pdftotext_layout; geteilte Kapitel (-1, -2) und 00b ohne Namensregel für <L-ID>_k<nn>.md; Corrigenda L-T2-23/-24 als eigene Dateien; vier Vorspann-Dateien mit Einleitung
    loesung: Entscheidungen Athlet – EPUB über Markdown mit `lesemethode: markdown_epub` (in 4.2 ergänzt), Stelle nach D-71; je Kapiteldatei eine Extraktion (`<L-ID>_k05-1.md`, `<L-ID>_k00b.md`); Corrigendum im selben Lauf wie der Artikel (`<L-ID>_k00.md`), korrigierte Werte markiert; L-T1-08_00, L-T2-04_00, L-T3-09_00, L-T3-21_00 werden extrahiert
  - datum: 2026-09-29
    was: U2 hätte 290 Buchkapitel plus 94 Artikel umfasst (W-01 ging von 30–40 Kapiteln aus); viele Buchkapitel ohne Bezug zu Zweck, Zuschnitt oder Profil (Kinder, Mannschafts- und Rückschlagsport, Ernährung, Anlagen/Recht)
    loesung: Entscheidung Athlet – Kapitelauswahl je Buch vor U2; 99 Kapitel nicht extrahiert (88 nach Vorschlag, dazu die Gruppen Geschlecht, Medizin Klettern, Vibration/IASTM/Flossing), extrahiert zusätzlich die Gruppen Alter, Umwelt (Hitze/Kälte, Höhe), Freihantel/Maschine, Zugübungen Calisthenics; Grund je Kapitel in docs/extraktion/README.md; Nachtrag über den Lücken-Workflow (U8). U2 umfasst 195 Buchkapitel und 92 Artikel (287 Läufe; die beiden Corrigenda laufen mit dem Artikel)
  - datum: 2026-09-29
    was: U2 Zieldateien UB (41 Dateien – 10 Artikel, L-A01 18 und L-A02 13 Kapitel) und UP (9 Artikel) extrahiert; 3 657 Aussagen, davon 122 `unsicher: true` (fast ausschließlich aus Grafiken abgelesene Werte), 144 offene Stellen; keine Datei mit Formfehler, kein Kapitel „nicht verwertbar“, alle mit lesemethode pdf_nativ
    loesung: Muster je Quelle im README (Abschnitt 6): L-A01 Druckseite = Gesamt-PDF − 1 in allen Kapiteln (E-Book-Paginierung); L-A02 Versatz je Kapitel verschieden (−17 bis −7), innerhalb der Kapiteldatei konstant; Artikel mit Zeitschriften-Paginierung, Sonderfälle L-P04 (Ahead-of-Print), L-P11 (Seiten „n of 13“ + Abschnitt), L-T2-32 (Verlagsdeckblatt, PDF − 1). Viele offene Stellen sind Widersprüche in den Quellen selbst (Text vs. Tabelle/Abbildung/Abstract), wie gedruckt übernommen – Schwerpunkt für die Gegenprüfung (U3); zwei fachlich zweifelhafte Buchaussagen in L-A01 k03 (muskarinische Rezeptoren an der Endplatte, „extrapyramidal tracts“) wie gedruckt übernommen
  - datum: 2026-09-29
    was: U2 – Unteragenten nutzten Typwerte außerhalb des Templates (`methode`/`methodik` für Studienbeschreibungen in 11 Artikeln, `erfahrung` in L-T1-08 k04) und ergänzten in L-T1-08 k08 eine abgeleitete „Standard“-Pause (Verstoß gegen Regel 1)
    loesung: Entscheidung Athlet – `methodik` als fünfter Typ (Abschnitt 3, 4.1 Regel 4), `methode` vereinheitlicht; `erfahrung` → `praxis` mit Vermerk „Erfahrungsbericht, Einzelfall“; abgeleiteter Wert durch „Pause im Text nicht genannt“ ersetzt (Korrektur durch die jeweilige Extraktions-Instanz); Briefing der Unteragenten um beide Regeln ergänzt
  - datum: 2026-09-29
    was: Container-Neustart während U2; 27 laufende Extraktionen waren abgeschlossen, ihre Rückmeldungen gingen verloren
    loesung: Dateien vollständig und formal geprüft (check), Seitenbezug aus den Dateien übernommen; keine Neuextraktion nötig
  - datum: 2026-09-29
    was: U2 Zieldatei T1 extrahiert – 35 Dateien (8 Artikel, L-T1-07 13 und L-T1-08 14 Kapitel); 2 171 Aussagen, davon 60 `unsicher: true` in 21 Dateien, 87 offene Stellen; keine Formfehler, kein Kapitel „nicht verwertbar“
    loesung: Muster im README – L-T1-07 Druckseite = Gesamt-PDF − 7 (alle Kapitel), L-T1-08 Scan-Versatz −2/−4 bestätigt, Druckseiten 149–150 fehlen im Scan; L-T1-05 und L-T2-12 Online-First ohne Paginierung (Stelle als Abschnitt); L-T1-08 k09 (Seiten ohne Text) sind Titel-/Fotoseiten, Abbruchregel nicht ausgelöst; L-T1-07 Text vs. Abbildung bei Intervallwerten mehrfach abweichend
  - datum: 2026-09-29
    was: U2 Zieldatei T2 extrahiert – 76 Dateien (20 Artikel, L-A03 28, L-T2-03 12 und L-T2-04 16 Kapitel; L-P08 in UP); 4 876 Aussagen, davon 87 `unsicher: true`, 204 offene Stellen; keine Formfehler, kein Kapitel „nicht verwertbar“, alle pdf_nativ
    loesung: Muster im README – L-A03 zitiert die E-Book-Paginierung (= Gesamt-PDF-Seite), Druckseiten nicht bestimmbar (interne Querverweise auf Druckseiten, Versatz nicht konstant); L-T2-03 Versatz je Kapitel (−6 bis +8); L-T2-04 Scan, Druckseite = Gesamt-PDF − 14, am Seitenbild gelesen, Seite xv der Einleitung fehlt im Scan, PDF 577/578 vertauscht, Progressionscharts („Page n, Column m“) nicht in den Kapiteldateien, Übungsteile ohne Dosierung
  - datum: 2026-09-29
    was: Nebenbefunde Hauptkonzept – YAML-Block T1 in 13.2 nicht parsebar (ISBN-Zeile L-T1-01 mit „: “); in 13.2 fehlen `stufe` bei L-A01, L-A02, L-P01 bis L-P09 und `themenfelder` für übergreifend und T1; L-T3-04 (ausgewaehlt) und L-P14 (optional) keiner Zieldatei in AP-06 Punkt 3 zugeordnet; L-T2-08 bis L-T2-10 mit Status `verifiziert` als Belege im T2-Zuschnitt
    loesung: gemeldet, nicht geändert (Hauptkonzept nur an drei Stellen änderbar); Stufe für die Tabellen aus docs/literatur/README.md übernommen
```

## 12. Änderungsprotokoll

| datum | änderung |
|---|---|
| 2026-09-29 | Erstfassung nach Rücksprache (W-01 bis W-10; Gegenprüfung durch Fable und Athleten-Stichprobe 20 je Gebiet vorgegeben; Lücken-Workflow Abschnitt 8 ergänzt). |
| 2026-09-29 | WQ-01 bis WQ-05 mit dem Athleten geklärt: W-03 auf Block umgestellt; W-10 umgedreht (Synthese wartet auf vollständige Beschaffung der Kernquellen, Extraktion läuft vorab); W-11 bis W-13 neu; Abschnitte 2, 3, 5.1, 6, 8.1 (d), U1, U2, U4, U5 angepasst. |
| 2026-09-29 | Nach `docs/konzept/wissenskarten.md` verschoben (Stand-Kopfzeile unverändert). U1 erledigt: `docs/extraktion/` mit README (Statustabellen je Zieldatei, Quelle und Kapitel; Prüfdokument), leerem Lückenregister und Ordnern; Entscheidungen des Athleten zu T4, Ordnernamen, Vorspann/Anhängen und Prüfprotokoll im Statusblock (Abschnitt 11). |
| 2026-09-29 | Vor U2: Entscheidungen des Athleten zu EPUB (`lesemethode: markdown_epub` in 4.2), Dateinamen je Kapiteldatei, Corrigenda, Einleitungen im Vorspann und Kapitelauswahl der Bücher im Statusblock; U2 in Arbeit. |
