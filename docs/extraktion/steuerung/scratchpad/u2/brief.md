# Briefing Extraktion (Schritt 2, U2) – gilt für jeden Lauf

Du bist die Extraktions-Instanz für **genau eine** Eingabedatei (ein Buchkapitel oder einen Artikel). Den konkreten Auftrag (Datei, Quelle, Ausgabe) nennt dir die Auftragsdatei.

## Grenzen
- Lies nur die Eingabedatei(en) aus der Auftragsdatei. Lies **nichts** unter `docs/extraktion/`, `docs/wissen/`, `docs/konzept/` und keine anderen Kapitel oder Quellen.
- Keine Ergänzung aus eigenem Wissen (Regel 1), auch nicht bei bibliografischen Angaben, Autorennamen, Studienpopulationen oder Zahlen.
- Schreibe genau **eine** Datei (Pfad aus der Auftragsdatei). Lege den Ordner bei Bedarf an (`mkdir -p`). Ändere keine andere Datei, führe kein `git` aus.

## Athletenprofil für den Relevanzfilter (4.1 Regel 8)
Freizeitsportler (kein Leistungssport), Bereiche: T1 Ausdauer für Trailrunning und Skitouren (bergauf-orientiert); T2 Kraft/Haltung/Calisthenics (Heimtraining, Rumpf, Rücken); T3 Bouldern/Klettern (Fingerkraft, Zugkraft); T4 Beweglichkeit/Mobilität (Hüfte, Knie); R Reha/Prävention Patellasehne und Sprunggelenk, Laufumfang.

## Lesen
- PDF: direkt mit dem Read-Werkzeug lesen (Parameter `pages`, höchstens 20 Seiten je Aufruf, bis das ganze PDF gelesen ist) → `lesemethode: pdf_nativ`. Nur wenn das nicht gelingt: `pdftotext -layout <datei> -` über Bash → `lesemethode: pdftotext_layout`. Zum Nachschlagen einzelner Wörter darfst du zusätzlich `pdftotext` nutzen; maßgeblich bleibt die gelesene Seite.
- EPUB-Quelle (Eingabe `.md`): die Markdown-Datei lesen → `lesemethode: markdown_epub`. Stelle = „S. n“ nach der letzten Marke „[S. n]“ vor der Textstelle; ohne Marken „Kap. <nr>, Abschnitt ‚<Überschrift>‘“ (D-71). Das Ansichts-PDF gleichen Namens nur für Abbildungen öffnen; seine Seitenzahlen nie zitieren.
- **Stelle = gedruckte Seitenzahl** des Werks (die auf der Seite gedruckte Zahl bzw. die Zeitschriften-Paginierung), nie die PDF-Seite der Kapiteldatei. Die Auftragsdatei nennt bekannte Versätze. Nicht bestimmbar → `stelle: unklar` und Eintrag unter „Offene Stellen“ (Regel 2).
- Front matter `seiten`: gedruckter Seitenbereich des Kapitels bzw. Artikels (von–bis); bei EPUB ohne Seitenmarken „–“.

## Form
- Aussagen auf Deutsch paraphrasieren (Karten sind deutsch, D-26); wörtliche Übernahme nur nach W-09, dann in Originalsprache in Anführungszeichen mit Seite.
- Keine senkrechten Striche `|` innerhalb von Tabellenzellen (stattdessen „;“ oder „/“). Spalte `unsicher` nur `true` oder `false`.
- Spalte `typ` nur `befund`, `modell`, `praxis`, `definition` (Abschnitt 3) oder `methodik` (Entscheidung Athlet) für reine Angaben zu Studiendesign, Suche, Einschlusskriterien und Population (nie `methode`); Erfahrungsberichte/Anekdoten als `praxis` mit „Erfahrungsbericht, Einzelfall“ in `population`.
- Keine abgeleiteten, üblichen oder „Standard“-Werte ergänzen: Fehlt eine Angabe im Text, steht „im Text nicht genannt“.
- Alle Abschnitte des Templates anlegen; leere Abschnitte mit „- keine“.
- Front matter vollständig: `quelle`, `kapitel` (z. B. `k03`, `k05-1`, `k00b`; Artikel `k00`), `kapiteltitel`, `seiten`, `stufe`, `lesemethode`, `modell: opus`, `datum: 2026-09-29`.

## Abbruchregel
Ist mehr als etwa ein Drittel des Textes nicht lesbar (Scan, zerfallene Tabellen), **nicht raten**: Datei trotzdem anlegen, lesbare Aussagen extrahieren, betroffene Aussagen `unsicher: true`, unter „Offene Stellen“ als erste Zeile „- Kapitel nicht verwertbar: <Grund, betroffene Seiten>“, und im Rückgabebericht melden.

## Rückgabe (deine letzte Nachricht, knapp, ohne Inhaltszusammenfassung)
1. Pfad der geschriebenen Datei
2. `seiten` (gedruckt) und lesemethode
3. Zahl der Aussagen; davon `unsicher: true`; Zahl der Einträge unter „Offene Stellen“
4. Seitenbezug: beobachteter Versatz PDF-Seite (Kapiteldatei bzw. Artikel) → gedruckte Seite, und ob er zum Hinweis der Auftragsdatei passt
5. Probleme (nicht lesbare Seiten/Tabellen, „Kapitel nicht verwertbar“, Auffälligkeiten) oder „keine“

## Entscheidung W-09 (aus docs/konzept/wissenskarten.md, Abschnitt 1)
| W-09 | Wörtliche Übernahmen aus den Quellen nur, wenn der Wortlaut entscheidend ist (Definitionen, Schwellenwerte), kurz, mit Seite. Sonst Paraphrase. Karten sind eigene Zusammenfassungen (D-12, D-23). | Urheberrecht; Karten liegen im Projektwissen und ggf. später offen. | 2026-09-29 |

## Abschnitt 4 aus docs/konzept/wissenskarten.md (wörtlich)

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
