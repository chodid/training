---
titel: Übergabe U2 (Extraktion) – Wiederaufnahme nach Pause
stand: 2026-09-29 (Pause auf Wunsch des Athleten, Fortsetzung ca. 2026-10-01)
bezug: docs/konzept/wissenskarten.md (Abschnitte 4, 9 U2, 11); docs/extraktion/README.md; docs/konzept/konzept-ki-personal-trainer.md (13.1, 13.2, AP-06)
zweck: Eine neue Claude-Code-Sitzung (Opus) stellt mit diesem Dokument die Umgebung wieder her und setzt U2 ohne Rückfragen zum Verfahren genau an der Stelle fort, an der pausiert wurde.
---

# Übergabe U2 – Extraktion der Literatur

## 0. Kurzfassung für die neue Sitzung

1. `bash docs/extraktion/steuerung/wiederherstellen.sh <dein-scratchpad> <heutiges-datum>` ausführen (Abschnitt 3).
2. Dieses Dokument vollständig lesen, danach `docs/konzept/wissenskarten.md` Abschnitte 4, 9 (U2) und 11.
3. Warteschlange abarbeiten (Abschnitt 4). Die nächste Einheit ist `L-T3-09_k13`.
4. Wenn T3 fertig ist: T3-Bericht an den Athleten geben (Abschnitt 6.1). Danach ohne Pause mit R und T4 weitermachen.
5. Abschluss nach Abschnitt 7.

## 1. Auftrag (Athlet, 2026-09-29) – verbindliche Arbeitsregeln

Die Code-/Dokumentations-Instanz (Opus) führt AP-06 „Wissensbasis“, Teil Extraktion, aus. Maßgeblich ist `docs/konzept/wissenskarten.md`: Bei Widerspruch zum Hauptkonzept gilt für die Extraktion wissenskarten.md. Widersprüche werden dem Athleten gemeldet.

- **Keine Änderungen unter `server/`**, keine Versionsnummer hochstufen (reine Dokumentationsarbeit). CHANGELOG nur unter `[Unreleased] ### Dokumentation` ergänzen.
- **Nichts unter `docs/wissen/` anlegen oder ändern** (das ist der Synthese vorbehalten).
- **Hauptkonzept nur an drei Stellen ändern:**
  1. 13.1: Verweis auf den Erstellungsprozess (erledigt).
  2. AP-06, Statusblock, Teilschritt „Karten-Template und Karten“.
  3. Änderungsprotokoll.
  Keine neuen D-/Q-/V-IDs vergeben.
- **Statusblock in wissenskarten.md, Abschnitt 11**, nach jedem Unterpunkt pflegen: Status, Datum, `probleme_loesungen`.
- **Schritt 3 (U2):**
  - 3.1 Reihenfolge nach W-04: UB → UP → T1 → T2 → T3 → R, danach T4 (Entscheidung Athlet). Je Zieldatei Stufe A, B, C. Nur vorhandene, durchsuchbare Dateien.
  - 3.2 Ein Unteragent je Kapitel bzw. Artikel, mit frischem Kontext.
  - 3.3 Ausgabe nach `docs/extraktion/<block>/<L-ID>/<L-ID>_k<nn>.md`.
  - 3.4 Nach jeder Quelle:
    - Vollständigkeit prüfen und README aktualisieren.
    - Commit `docs(extraktion): <L-ID> extrahiert (<n> Kapitel)`, bei Artikeln `(Artikel)`.
  - 3.5 Abbruchregel beachten. Nichts aus eigenem Wissen ergänzen.
  - 3.6 Nach jeder Zieldatei den Statusblock U2 pflegen und einen nummerierten Bericht an den Athleten geben. Der Bericht nennt Dateien, `unsicher`, offene Stellen sowie Muster und Seitenversatz; diese Angaben stehen auch im README je Quelle. Danach **ohne Pause weiter**.
  - 3.7 Am Ende:
    - Statusblock U2 auf `erledigt` setzen.
    - AP-06-Statusblock, Teilschritt, im Hauptkonzept nachziehen.
    - Änderungsprotokolle in Hauptkonzept und wissenskarten.md ergänzen.
    - Commit und Push.
- **Arbeitsweise (CLAUDE.md):**
  - Antworten auf Deutsch, Schritte nummerieren.
  - Rückfragen immer per AskUserQuestion, mit Hintergrund und einer Option „(Empfehlung)“ an erster Stelle.
  - Keine eigenen Annahmen.
  - Keinen PR ohne Auftrag.
- **Branch:** `claude/ecstatic-johnson-g3mxel`. Push immer mit `git push -u origin claude/ecstatic-johnson-g3mxel`.
- **Commit-Trailer** (jede Commit-Nachricht endet damit; die Session-Zeile trägt die URL der jeweils aktuellen Sitzung):
  ```
  Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
  Claude-Session: <URL der aktuellen Sitzung>
  ```
  Die Skripte `commit_source.sh` tragen noch die alte Session-URL. Bei Wiederaufnahme dort die Zeile `Claude-Session:` auf die neue Sitzung ändern.

## 2. Entscheidungen des Athleten (alle 2026-09-29, in wissenskarten.md Abschnitt 11 hinterlegt)

1. T4 wird aufgenommen, als letzte Zieldatei nach R.
2. Die Ordner heißen wie unter `docs/literatur/`: `uebergreifend`, `t1-ausdauer`, `t2-kraft`, `t3-klettern`, `r-reha`, `t4-beweglichkeit`, dazu `stichprobe`, `uebergabe` und diese `steuerung`.
3. Vorspann und Anhänge werden gelistet, nicht extrahiert. Beim Register fehlender Quellen gibt es nur einen Verweiseintrag.
4. Die Kapitelauswahl ist vorab bestätigt (`scratchpad/auswahl.py`: SKIP und EINLEITUNG). Extrahiert werden zusätzlich die Gruppen Alter, Umwelt (Hitze/Kälte, Höhe), Freihantel/Maschine und Zugübungen Calisthenics. Nicht extrahiert werden Geschlecht, Medizin Klettern sowie Vibration/IASTM/Flossing.
5. EPUB-Quellen (L-T3-10, -19, -20, -21) werden aus Markdown gelesen, mit `lesemethode: markdown_epub`. Stelle ist „S. n“ nach den Marken [S. n], ohne Marken „Kap. n, Abschnitt ‚…‘“ (D-71).
6. Jede Datei bekommt eine eigene Extraktion. Geteilte Kapitel heißen `k05-1`, `k05-2`, …, dazu `k00b`. Corrigenda laufen mit dem Artikel.
7. Einleitungen in Vorspann-Dateien werden extrahiert.
8. `methodik` ist als fünfter `typ` zugelassen (reine Studienbeschreibungen). `erfahrung` wird zu `praxis` mit „Erfahrungsbericht, Einzelfall“.
9. L-A01 liegt nur in der 7. Aufl. vor. Sie wird vorab extrahiert; nach Beschaffung der 8./9. Aufl. folgt ein Abgleich bzw. eine Neuextraktion.
10. Seitenbezug L-A03: Die E-Book-Seite (= Gesamt-PDF-Seite) wird zitiert, ein Vermerk im README genügt.
11. Pause am 2026-09-29: Der Zwischenstand wird vollständig im Branch gesichert (teilweise extrahierte Bücher sind committet, die Steuerung liegt in diesem Ordner).

## 3. Umgebung wiederherstellen

1. Repo auf Branch `claude/ecstatic-johnson-g3mxel` auschecken (`git fetch origin claude/ecstatic-johnson-g3mxel && git checkout claude/ecstatic-johnson-g3mxel && git pull`).
2. Wiederherstellen:
   `bash docs/extraktion/steuerung/wiederherstellen.sh <scratchpad-der-neuen-sitzung> <JJJJ-MM-TT>`
   Das Skript erledigt Folgendes:
   - Es kopiert `docs/extraktion/steuerung/scratchpad/` ins neue Scratchpad.
   - Es ersetzt den alten absoluten Pfad `/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad` in allen Skripten und in den 287 Auftragsdateien.
   - Es setzt im Briefing `datum:` auf das neue Datum.
   - Es installiert bei Bedarf `poppler-utils` (pdftotext, pdfinfo).
   - Es trägt den lokalen git-Ausschluss `docs/extraktion/*/L-*/` in `.git/info/exclude` ein.
   - Es legt noch eingetragene laufende Einheiten zurück an den Anfang der Warteschlange.
   Das Skript wurde am 2026-09-29 in einem Testverzeichnis geprüft: Die Pfade werden ersetzt, `check.py` läuft, und README-Generator und Statusgenerator liefern unveränderte Ausgaben.
3. In der neuen Sitzung `S=<scratchpad>` setzen. Alle Skripte werden als `$S/<skript>` aufgerufen und arbeiten mit `/home/user/training` als Repo-Pfad. Liegt das Repo woanders, `grep -rl /home/user/training $S` prüfen und anpassen.
4. In `$S/commit_source.sh` die Zeile `Claude-Session:` auf die neue Session-URL setzen.
5. Prüfen:
   - `python3 $S/check.py L-T3-09` zeigt 12 Dateien ohne Fehler und `k13` mit „fehlt“.
   - `wc -l $S/u2/queue.txt` ergibt 77.
   - `git status` ist sauber. Die laufenden Quellordner sind lokal ausgeschlossen, Commits dafür deshalb mit `git add -f`.

**Warum der lokale Ausschluss:** Ein Stop-Hook bemängelt nicht getrackte Dateien. Halbfertige Buchordner sollen aber erst committet werden, wenn das Buch vollständig ist (3.4). Deshalb ist `docs/extraktion/*/L-*/` lokal ausgeschlossen, und `commit_source.sh` committet mit `git add -f`.

## 4. Arbeitsablauf je Einheit

**Warteschlange:** `$S/u2/queue.txt`, eine Einheit je Zeile (`<L-ID>_k<nr>`). **Laufend:** `$S/u2/running.txt`. Höchstens **20 Unteragenten gleichzeitig** (Grenze der Umgebung).

1. **Starten:** Die Einheit laufend setzen (siehe Punkt 5) und einen Unteragenten starten, mit `subagent_type: general-purpose`, `model: opus`, `run_in_background: true`, Beschreibung `Extraktion <L-ID> <kNN>`. Der Prompt lautet wörtlich (Pfad = neues Scratchpad):
   ```
   Lies die Auftragsdatei `<S>/u2/auftrag/<EINHEIT>.md` und führe den Auftrag vollständig aus. Sie verweist auf ein Briefing, das du zuerst vollständig liest und befolgst.
   ```
   Die Auftragsdatei nennt Eingabedatei(en), Kapitel, Seitenbezugshinweis, Quelle aus 13.2 (Stufe, Zweck, Themenfelder) und den Ausgabepfad. Das Briefing (`u2/brief.md`) enthält die Grenzen, das Athletenprofil, Lese- und Formregeln, die Abbruchregel, das Rückgabeformat, W-09 und Abschnitt 4 aus wissenskarten.md im Wortlaut.
2. **Rückmeldung prüfen:** Der Agent meldet Pfad, Seiten und Lesemethode, Aussagen, Zahl `unsicher` und offene Stellen, Seitenbezug und Probleme.
   - Verstöße gegen die Regeln korrigiert der Agent selbst: per SendMessage an ihn zurückgeben. Beispiele sind abgeleitete Standardwerte oder `typ` außerhalb der fünf erlaubten Werte.
   - Meldet ein Agent „Datei von außen verändert“, `python3 $S/check.py <L-ID>` ausführen. Bisher war die Datei immer konsistent, die Meldung war harmlos.
3. **Protokollieren:** Eine Zeile an `$S/reports.md` anhängen, zum Beispiel `L-T3-09 k13: S. a-b, A<n> U<n> O<n>; Versatz …; Auffälligkeiten`.
4. **Committen:**
   - **Artikel:** Zuerst das Muster in `$S/notes.json` eintragen (Schlüssel = L-ID, Text beginnt mit „Muster: …“: Versatz, Befunde, fehlende Teile). Dann `$S/commit_source.sh <L-ID>` aufrufen.
   - **Buch:** Erst committen, wenn **alle** Kapitel da sind. Dann den notes.json-Eintrag mit dem Muster über alle Kapitel anlegen (Stoff aus reports.md) und `$S/commit_source.sh <L-ID>` aufrufen.
   - `commit_source.sh` prüft formal (`check.py`) und bricht bei „fehlt“ oder Formfehlern ab. Es erzeugt das README neu (`status_gen.py` + `readme.py`, die notes.json-Muster landen in Spalte „bemerkung“ und Abschnitt 6), committet mit `git add -f` und pusht.
   - **Ausgabe nicht mit `| head` kürzen.** Das bricht das Skript per SIGPIPE vor dem Push ab (einmal passiert, Push nachgeholt). Besser nach `$S/c.txt` umleiten.
5. **Nachrücken:** `$S/next.sh <fertige-einheit>` entfernt die Einheit aus running.txt, holt die nächste aus der Warteschlange und gibt sie aus. Die ausgegebene Einheit sofort starten.
   - **Nie `next.sh` mit einem Platzhalter aufrufen.** Das holt eine Einheit zu viel (einmal passiert, zurückgelegt).
   - Zum Nachfüllen mehrerer Plätze `next.sh <einheit> <n>` verwenden.
6. **Nach einem Container-Neustart:** Alle Einheiten aus `running.txt` mit `check.py` prüfen. Formal vollständige Dateien werden übernommen (Präzedenz 2026-09-29), die anderen kommen zurück in die Warteschlange.

## 5. Stand bei der Pause (2026-09-29)

**287 Läufe insgesamt:** 195 Buchkapitel und 92 Artikel.

| Zieldatei | Stand | Commits |
|---|---|---|
| UB | fertig: 41 Dateien, 3 657 Aussagen (UB+UP), Bericht gegeben | je Quelle |
| UP | fertig: 9 Artikel, Bericht gegeben | je Quelle |
| T1 | fertig: 35 Dateien, 2 171 Aussagen, Bericht gegeben | je Quelle |
| T2 | fertig: 76 Dateien, 4 876 Aussagen, 87 unsicher, 204 offene Stellen, Bericht gegeben | je Quelle |
| T3 | **teilweise:** 49 von 63 extrahiert, Bericht **ausstehend** | siehe unten |
| R | offen: 28 Artikel | – |
| T4 | offen: 35 Dateien (12 Artikel, L-T4-32 12 Kapitel, L-T4-34 11 Kapitel) | – |

**T3 im Einzelnen:**
- Vollständig committet: L-T3-01, -02, -03, -05, -06 (16 Kap.), -18, -19 (13 Kap., EPUB).
- Teilweise committet (Pause, Commit „teilweise extrahiert“):
  - L-T3-09: 12 von 13, es fehlt k13.
  - L-T3-10: 2 von 8, vorhanden k01-1 und k05-1; es fehlen k01-2, k02-1, k02-2, k03, k05-2, k06.
  - L-T3-20: 1 von 3, vorhanden k00b; es fehlen k01 und k02.
- Offen: L-T3-21 (5 Einheiten: k00, k01, k02-1, k02-2, k03).
- Nach Abschluss dieser Bücher den normalen Commit „`<L-ID>` extrahiert (`<n>` Kapitel)“ mit `commit_source.sh` machen. Er nimmt die bereits committeten Kapitel mit. Vorher den Muster-Eintrag in notes.json anlegen.

**Warteschlange (77 Einheiten), in dieser Reihenfolge:**
- L-T3-09_k13
- L-T3-10: k01-2, k02-1, k02-2, k03, k05-2, k06
- L-T3-20: k01, k02
- L-T3-21: k00, k01, k02-1, k02-2, k03
- R: L-R-01 … L-R-28 (Reihenfolge Stufe A, B, C wie in units.py)
- T4: 12 Artikel, danach L-T4-32 (12 Kapitel) und L-T4-34 (11 Kapitel)

**Bei der Pause gestoppte Agenten:**
- 15 Einheiten waren angefangen und liegen wieder vorn in der Warteschlange.
- 3 gestoppte Agenten hatten ihre Datei schon vollständig geschrieben: L-T3-09 k05, L-T3-10 k01-1, k05-1. Sie bestehen die Formprüfung und sind übernommen, eine Rückmeldung fehlt. Das ist in reports.md vermerkt; ihr Seitenbezug ist in der Gegenprüfung (U3) mit zu prüfen.

**Muster, die für die notes.json-Einträge schon bekannt sind** (aus reports.md):
- L-T3-09: Scan, gedruckt = Gesamtbuch-PDF − 16, arabische Zählung ab PDF 17.
  - Vorspann: PDF n = römisch n − 2 (x–xiii).
  - Kapitelenden sind oft ganzseitige Fotos ohne Zahl.
  - Einzelne Abbildungen (3.3, 10.4) sind handschriftlich.
  - Keine Literaturliste im Buch.
  - Einheiten in pound/inch.
  - Risskletter- und Jugendteile ausgelassen.
- L-T3-20: EPUB mit Seitenmarken. Das Ansichts-PDF ist um +13 versetzt, eine Abschnittsgrenze liegt laut Marke eine Seite anders als im PDF; zitiert wird nach den Marken.
- L-T3-10 und L-T3-21: noch keine Muster.

## 6. Offene Punkte

### 6.1 T3-Bericht (fällig, sobald T3 fertig ist)

1. Die Zahlen mit `check.py` über alle T3-Quellen bilden: Dateien, Aussagen, `unsicher`, offene Stellen, Lesemethoden. Die T3-Tabelle im README (4.5) listet die Quellen.
2. In wissenskarten.md, Abschnitt 11:
   - Kommentar U2 ergänzen: „Zieldateien UB, UP, T1, T2 und T3 extrahiert“.
   - Einen neuen `probleme_loesungen`-Eintrag anlegen (Muster wie beim T2-Eintrag).
3. Nummerierten Bericht an den Athleten, mit:
   - Dateien, `unsicher`, offene Stellen.
   - Seitenmuster je Quelle:
     - L-T3-06: Versatz je Kapitel −11 … +8.
     - L-T3-19/-20/-21/-10: EPUB.
     - L-T3-09: −16.
   - Auffälligkeiten: viele Widersprüche Text vs. Tabelle, Abbildungsverweise in L-T3-06 falsch, Summen in Tabellen stimmen nicht.
   - Gestoppte Agenten (Abschnitt 5).
   - **Rückfrage L-T3-16** per AskUserQuestion: Stufe C (Planungsvorlage), aber `ausgewaehlt`, und die Datei fehlt. Nach W-10 blockiert sie die T3-Synthese. Optionen:
     - (Empfehlung) beschaffen,
     - auf `optional` setzen (Entscheidung Athlet, Eintrag in 13.2 wäre eine Änderung im Hauptkonzept außerhalb der drei erlaubten Stellen, also nur mit ausdrücklicher Zustimmung),
     - Synthese ohne L-T3-16.
4. Danach ohne Pause mit R weitermachen.

### 6.2 R- und T4-Bericht

Gleiches Vorgehen wie 6.1. T4: Die Kernquelle L-T4-17 fehlt (Beschaffung durch den Athleten), im README vermerkt.

### 6.3 Weitere offene Punkte

1. **L-A01, 8./9. Aufl.:** Beschaffung durch den Athleten. Danach Abgleich bzw. Neuextraktion (UB/UP bleiben bis dahin „Synthese startbereit: nein“).
2. **L-T2-04:** Seite xv der Einleitung fehlt im Scan (Befund, keine Aktion in U2).
3. **Nebenbefunde Hauptkonzept** (gemeldet, nicht geändert):
   - Der YAML-Block T1 in 13.2 ist nicht parsebar.
   - Es fehlen Felder `stufe`/`themenfelder`.
   - L-T3-04 und L-P14 sind keiner Zieldatei zugeordnet.
   - L-T2-08 bis -10 sind `verifiziert` als Belege.
   Siehe wissenskarten.md, Abschnitt 11.
4. **Ordner `docs/extraktion/steuerung/`:** Am Ende von U2 entscheidet der Athlet, ob der Ordner als Nachweis bleibt oder entfernt wird. Frage per AskUserQuestion stellen, Empfehlung: bleibt bis zum Abschluss von U3 (Gegenprüfung), weil check.py und die Auftragsdateien dafür nützlich sind.

## 7. Abschluss U2 (Schritt 3.7)

1. Alle Zieldateien sind extrahiert und committet (README: „x von x extrahiert“ je Tabelle, außer den fehlenden Quellen).
2. In wissenskarten.md, Abschnitt 11:
   - `U2: erledigt` mit Datum und Kommentar (nur verfügbare Quellen; fehlend sind L-A01 8./9. Aufl., L-T3-16, L-T4-17, R-/T1-/T2-Quellen laut README).
   - Abschlusseintrag in `probleme_loesungen`.
   - Zeile im Änderungsprotokoll (Abschnitt 12).
3. Im Hauptkonzept, AP-06-Statusblock, den Teilschritt „Karten-Template und Karten“ nachziehen, z. B. „Auftrag docs/konzept/wissenskarten.md; U1/U2 erledigt, U3 offen“. Außerdem eine Zeile im Änderungsprotokoll. Sonst nichts ändern.
4. CHANGELOG `[Unreleased] ### Dokumentation`: eine Zeile zu U2.
5. `docs/pruefung/pruefprotokoll.md`: Eintrag noch_zu_pruefen (Gegenprüfung U3, Stichprobe Athlet) aktualisieren.
6. Konsistenzprüfung der Dokumente, danach Commit und Push. Abschlussbericht an den Athleten.

## 8. Dateien der Steuerung (`docs/extraktion/steuerung/scratchpad/`)

| Datei | Zweck |
|---|---|
| `u2/brief.md` | Gemeinsames Briefing aller Extraktions-Agenten (Regeln, Profil, Form, Abbruch, Rückgabe, W-09, Abschnitt 4 wörtlich) |
| `u2/auftrag/<EINHEIT>.md` | 287 Auftragsdateien (Eingabe, Kapitel, Seitenhinweis, Quelle, Ausgabe) |
| `u2/units.json`, `u2/units.py` | Einheitenliste (Zieldatei, L-ID, Nr., Block, Dateien, Titel, Hinweis, EPUB, Stufe, Ausgabe) und Generator (Reihenfolge ORDER nach W-04) |
| `u2/queue.txt`, `u2/running.txt` | Warteschlange und laufende Einheiten |
| `check.py` | Formale Prüfung je Quelle (Front matter, Abschnitte, Tabelle, typ, unsicher, Zählung); Ausgabe je Datei als JSON-Zeile |
| `commit_source.sh` | Prüfen → README neu erzeugen → `git add -f` → Commit → Push, je Quelle |
| `next.sh`, `pop.sh` | Nachrücken aus der Warteschlange |
| `status_gen.py`, `readme.py`, `gen.json`, `summary.json` | Erzeugen `docs/extraktion/README.md` (Statustabellen, Synthese startbereit, Seitenbezug) aus den Front matters und notes.json |
| `notes.json` | Muster je Quelle (README-Spalte „bemerkung“, Abschnitt 6) |
| `reports.md` | Protokoll aller Rückmeldungen je Kapitel/Artikel |
| `auswahl.py` | Bestätigte Kapitelauswahl (SKIP mit Grund, EINLEITUNG) |
| `entries.json`, `inv.json`, `litreadme.json`, `parse.py`, `inv.py`, `gen.py`, `cov.py` | Grunddaten (13.2 geparst, Dateiinventar mit Seitenzahlen und Durchsuchbarkeit, Kapitelverzeichnis aus docs/literatur/README.md) und deren Erzeuger |

Nicht gesichert sind Bildausschnitte und Zwischenausgaben der Agenten (nicht nötig).
