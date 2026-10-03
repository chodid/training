---
titel: Modelltest Extraktion – Aufbau, Ablauf, Bewertungsschema
stand: 2026-10-03 (vorbereitet; Start am Testtag 2026-10-04 auf „Go“ des Athleten)
bezug: docs/extraktion/steuerung/UEBERGABE.md; docs/konzept/wissenskarten.md (Abschnitt 4)
entscheidung: Athlet 2026-10-03 – 12 Einheiten (je 3 erzählend, Scan, dichte Tabellen, Reviews mit vielen Kennzahlen), mehrere Modelle in eigenen Instanzen, blinde Auswertung in einer eigenen Instanz, Goldstandard durch den Athleten an ausgewählten Stellen
---

# Modelltest Extraktion

## 1. Zweck

Geprüft wird, welches Modell für die Extraktion (U2, Schritt 2 aus `wissenskarten.md`) ausreicht, aufgeschlüsselt nach Quellentyp. Mehrere Modelle extrahieren dieselben 12 Einheiten mit demselben Briefing. Eine eigene Auswertungs-Instanz prüft alle Ergebnisse blind am Original-PDF. Der Athlet legt an ausgewählten Stellen den Goldstandard fest.

## 2. Aufbau

| Pfad | Inhalt |
|---|---|
| `briefing.md` | Regeln für jede Extraktion (aus U2 übernommen; angepasst sind nur Pfade, Blindheit und Front matter) |
| `einheiten.md` | Die 12 Einheiten mit Kategorie, Auftragsdatei und Eingabedatei(en) |
| `auftraege/<EINHEIT>.md` | Auftrag je Einheit (Eingabe, Kapitel, Seitenhinweis, Quelle, Ausgabe mit Platzhalter `<LAUF-CODE>`) |
| `lauf-<CODE>/<EINHEIT>.md` | Ergebnisse je Lauf; die Kennung ist ein Buchstabe, kein Modellname |
| `auswertung/` | Wird von der Auswertungs-Instanz angelegt: Referenz je Einheit, Urteile, Goldstandard, Bericht |
| `PROMPT_EXTRAKTION.md` | Prompt für jede Extraktions-Instanz (identisch, nur `<LAUF-CODE>` einsetzen) |
| `PROMPT_AUSWERTUNG.md` | Prompt für die Auswertungs-Instanz |

Läufe: `V`, `K`, `T`, `F`, `R`. Welcher Lauf zu welchem Modell gehört, weiß nur der Athlet. Die Zuordnung steht nicht im Repo und wird der Auswertung erst nach dem Goldstandard genannt.

## 3. Ablauf

1. **Vorbereitung (Athlet):** Jede Extraktions-Instanz bekommt `PROMPT_EXTRAKTION.md` mit ihrem Lauf-Code, die Auswertungs-Instanz bekommt `PROMPT_AUSWERTUNG.md`. Die Instanzen antworten nur „Bereit“ und laden noch nichts.
   - Denktiefe in allen Extraktions-Instanzen: **high**. Bei GPT ist das „reasoning effort: high“.
   - Die Auswertungs-Instanz läuft mit hoher Denktiefe und kann PDF-Seiten als Bild lesen.
2. **Extraktion (nach „Go“):** Jede Instanz bearbeitet die 12 Einheiten, committet nur ihren Ordner `lauf-<CODE>/` und meldet im Chat das Laufprotokoll mit Kosten und Branch. Der Athlet sammelt die Kostenangaben.
3. **Auswertung Phase A (nach „Go“, wenn alle Läufe gepusht sind):**
   - Die Läufe aus allen Remote-Branches einsammeln.
   - Die Form prüfen.
   - Je Einheit eine Referenz bilden: alle Aussagen der Läufe zusammenführen und jede am Original prüfen.
4. **Phase B, Goldstandard:** Die Auswertung legt dem Athleten 30 Stellen vor: 15 zufällig gezogene und 15 der strittigsten. Gezeigt werden nur die konkurrierenden Fassungen, ohne Lauf-Kennung und ohne eigenes Urteil. Der Athlet entscheidet am PDF.
5. **Phase C, Bericht:**
   - Die Urteile der Auswertung werden mit dem Goldstandard abgeglichen und, falls nötig, nachgeprüft.
   - Kennzahlen je Lauf und Kategorie berechnen.
   - Danach nennt der Athlet die Zuordnung und die Kosten.
   - Bericht mit Empfehlung je Quellentyp (Modell und Denktiefe).
6. **Entscheidung (Athlet):** Modell je Quellentyp für den Rest von U2. Die Entscheidung wird in `wissenskarten.md` Abschnitt 11 und in `docs/extraktion/steuerung/UEBERGABE.md` eingetragen.

## 4. Blindheit

- Ergebnisdateien und Commit-Nachrichten enthalten weder Modell- noch Anbieternamen. Front matter `modell: lauf-<CODE>`, `datum: 2026-10-04` für alle.
- Die Auswertung liest keine Commit-Historie und keine Commit-Metadaten. Sie liest nichts unter `docs/extraktion/` außerhalb von `modelltest/` (also keine Extraktionen in den Blockordnern) und keine Chatprotokolle anderer Instanzen.
- Dem Athleten werden konkurrierende Fassungen als „Fassung 1, 2, …“ gezeigt, in zufälliger Reihenfolge, nie mit Lauf-Kennung.

## 5. Bewertungsschema

**Urteil je Aussage, Zahl oder Angabe** (am Original geprüft):

| Urteil | Bedeutung | schwer |
|---|---|---|
| `korrekt` | inhaltlich und in der Stelle richtig | – |
| `ungenau` | Kern richtig, Detail verkürzt oder unscharf (z. B. Einheit, Streuung oder Population fehlt, obwohl im Text) | nein |
| `stelle_falsch` | Inhalt richtig, Seite bzw. Abschnitt falsch | nein |
| `zahl_falsch` | Zahl, Einheit oder Vorzeichen falsch übernommen | ja |
| `sinn_falsch` | Aussage verkehrt oder falsch zugeordnet (Gruppe, Studie, Bedingung) | ja |
| `nicht_im_text` | steht nicht in der Quelle (Ergänzung aus eigenem Wissen, abgeleiteter Wert) | ja |
| `nicht_pruefbar` | am Original nicht entscheidbar (z. B. unleserlich) | – |

**Kennzahlen je Lauf**, gesamt und je Kategorie:

1. **Fehlerquote schwer:** (`zahl_falsch` + `sinn_falsch` + `nicht_im_text`) / geprüfte Angaben.
2. **Präzision:** `korrekt` / geprüfte Angaben, ohne `nicht_pruefbar`.
3. **Vollständigkeit:** Anteil der geprüften Referenzeinträge, die der Lauf enthält. Gewichtet: Zahlen und Protokolle sowie Kernaussagen doppelt.
4. **Stellen-Genauigkeit:** Anteil der Angaben mit richtiger Seite bzw. richtigem Abschnitt.
5. **Fallen erkannt:** Anteil der am Original bestätigten Widersprüche oder Fehler in der Quelle selbst, die der Lauf markiert. Als markiert zählen `unsicher: true`, ein Eintrag unter „Offene Stellen“ oder unter „Lücken“.
6. **Kalibrierung `unsicher`:** Anteil der schweren Fehler, die als `unsicher` markiert waren, und Anteil der `unsicher`-Markierungen an korrekten Angaben.
7. **Formverstöße:**
   - fehlende Felder oder Abschnitte, falsche Spaltenzahl;
   - `typ` außerhalb von `befund`/`modell`/`praxis`/`definition`/`methodik`;
   - `unsicher` nicht `true`/`false`;
   - falsche `lesemethode`.
8. **Kosten** (erst nach der Entblindung, Angaben aus dem Laufprotokoll): Tokens, Dauer, Unteragenten.

**Hochrechnung der Kette Extraktion → Gegenprüfung** (Entscheidung Athlet 2026-10-03): In U2/U3 prüft immer ein festes, anderes Modell gegen (Gegenprüfung, eigener Lauf je Kapitel). Ihre Regeln sind hier übernommen, damit die Auswertung sie anwenden kann:

- **Prüfumfang je Kapitel:**
  - alle Aussagen mit Eintrag in `zahlen` oder mit `unsicher: true`;
  - alle mit `typ: befund`, die Dosierung oder Schwellen betreffen;
  - von den übrigen eine Zufallsauswahl von 20 %, mindestens 5.
- **Prüfergebnisse:** `ok`, `stelle_falsch`, `zahl_falsch`, `sinn_verzerrt`, `nicht_gefunden`, `nicht_pruefbar`.
- **Freigabe `nein`**, d. h. das Kapitel wird neu extrahiert und erneut geprüft: bei mindestens 2 `zahl_falsch`/`sinn_verzerrt` oder mindestens 1 `nicht_gefunden`. Bei Freigabe `ja` werden die gefundenen Fehler korrigiert.
- **Grenze:** Die Gegenprüfung prüft nur, was in der Extraktion steht. Fehlende Inhalte ergänzt sie nicht.

Die Urteile dieses Tests werden so zugeordnet: `zahl_falsch` → `zahl_falsch`; `sinn_falsch` → `sinn_verzerrt`; `nicht_im_text` → `nicht_gefunden`; `stelle_falsch` → `stelle_falsch`.

Daraus folgen **je Lauf (gesamt und je Kategorie)** diese Kennzahlen:

9. **Abgefangene Fehler:** Anteil der schweren Fehler, die im Prüfumfang liegen. Für den Zufallsanteil gilt der Erwartungswert: 20 % der schweren Fehler außerhalb des festen Umfangs bzw. der entsprechende Anteil bei der Mindestzahl 5.
10. **Restfehler nach Gegenprüfung:** schwere Fehler, die voraussichtlich unentdeckt bleiben, je 100 Aussagen.
11. **Quote Freigabe `nein`:** Anteil der Einheiten, die nach der Regel neu extrahiert werden müssten. Gerechnet wird mit den Fehlern im festen Prüfumfang; der Zufallsanteil geht als Erwartungswert ein. Das ist der Kostentreiber der Kette.
12. **Vollständigkeit nach Gegenprüfung:** unverändert gleich Kennzahl 3.

Eine echte Gegenprüfung aller Läufe (Stufe 2) wird nur angesetzt, wenn ein günstigeres Modell in der Hochrechnung knapp an der Vergleichsgröße liegt, und dann nur für dieses Modell. Darüber entscheidet der Athlet.

**Maßstab für die Empfehlung:** Ein Modell gilt für eine Kategorie als ausreichend, wenn seine Restfehler nach Gegenprüfung (Kennzahl 10) und seine Vollständigkeit innerhalb der Streuung liegen und die Quote Freigabe `nein` die Kosten nicht über die des stärkeren Modells treibt; Vergleichsgröße ist die Streuung, die zwischen zwei Läufen desselben starken Modells auftritt. Die Streuung wird nach der Entblindung bestimmt. Die Entscheidung trifft der Athlet.

## 6. Für Extraktionsläufe

- Bitte nur `briefing.md`, `einheiten.md`, `auftraege/` und die Eingabedateien lesen.
- Ausgabe nur nach `lauf-<CODE>/`.
- Alles Weitere steht in `PROMPT_EXTRAKTION.md`.

## 7. Für die Auswertung

Alles Nötige steht in `PROMPT_AUSWERTUNG.md`. Schema und Kennzahlen: Abschnitt 5.
