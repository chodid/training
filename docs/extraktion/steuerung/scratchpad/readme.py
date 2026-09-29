import json,re
P='/tmp/claude-0/-home-user-training/72795619-03c6-5f88-9236-4c2b1fc033c6/scratchpad/'
g=json.load(open(P+'gen.json'))
R=g['rows']; M=g['missing']
NAMES=[('UB','uebergreifend-belastung-monitoring-erholung','uebergreifend'),('UP','uebergreifend-planung-kombiniertes-training','uebergreifend'),
('T1','t1-ausdauer','t1-ausdauer'),('T2','t2-kraft-haltung','t2-kraft'),('T3','t3-klettern','t3-klettern'),('R','r-reha-praevention','r-reha'),('T4','t4-beweglichkeit','t4-beweglichkeit')]
FREMD={'UP':'L-A01, L-A02 (Tabelle UB)','T1':'L-A02 Kap. 02, 03-2, 03-3, 07-1, 07-2 (Tabelle UB)','T2':'L-P08 (Tabelle UP)','T4':'L-R-11 (Tabelle R), L-T2-15 (Tabelle T2)'}
out=[]
A=out.append
A('''---
titel: Extraktion der Literatur – Ablage und Prüfdokument
bezug: docs/konzept/wissenskarten.md (Abschnitte 2, 4, 5, 9 U1–U3, 11); docs/konzept/konzept-ki-personal-trainer.md (13.1, 13.2, AP-06 Punkt 3); docs/literatur/README.md
stand: 2026-09-29
---

# Extraktion (Zwischenergebnisse)

## 1. Zweck

- Hier liegen die **Zwischenergebnisse** der Wissenskarten nach `docs/konzept/wissenskarten.md`: Extraktionen je Kapitel bzw. Artikel (Schritt 2, Opus), Prüfprotokolle der Gegenprüfung (Schritt 3, Fable), Stichproben des Athleten, Lückenregister und Übergabedokumente der Synthese.
- Sie sind Arbeitsmaterial mit Seitenverweisen, kein Trainerwissen: **nie ins Projektwissen** (W-05), nie nach `docs/wissen/`. Gespiegelt werden ausschließlich die Zieldateien aus `docs/wissen/`.
- Eingecheckt ins private Repo (W-11): eigene Zusammenfassungen, kein Volltext (D-23); Wortlaut nur nach W-09.
- Diese Datei ist das **Prüfdokument** für Extraktion und Gegenprüfung: Aus den Tabellen in Abschnitt 4 ist jederzeit ablesbar, was extrahiert, was geprüft und was freigegeben ist und was noch fehlt. `docs/pruefung/pruefprotokoll.md` (AP-06) verweist nur hierher.

## 2. Ablage

```text
docs/extraktion/
  README.md                         # dieses Dokument
  luecken.md                        # Lückenregister (wissenskarten.md 8.3)
  <block>/<L-ID>/<L-ID>_k<nn>.md             # Extraktion je Kapitel; Artikel: <L-ID>_k00.md
  <block>/<L-ID>/<L-ID>_k<nn>_pruefung.md    # Prüfprotokoll der Gegenprüfung (Fable)
  stichprobe/<block>_stichprobe.md           # Stichprobe des Athleten je Block (W-03)
  uebergabe/<zieldatei>_uebergabe.md         # Übergabedokument der Synthese-Sitzung
```

- **Block-Ordner** wie `docs/literatur/`: `uebergreifend`, `t1-ausdauer`, `t2-kraft`, `t3-klettern`, `r-reha`, `t4-beweglichkeit` (Entscheidung Athlet 2026-09-29; `wissenskarten.md` Abschnitt 2 nennt Kurzformen `t1` … `r` und „wie docs/literatur/“ – aufgelöst zugunsten der Literaturordner). Eine Quelle liegt im Block ihrer ID-Definition (D-51), auch wenn sie mehreren Zieldateien dient (z. B. L-P08 unter `uebergreifend/`, L-T2-25 unter `t2-kraft/`), und wird **einmal** extrahiert.
- **T4** (`t4-beweglichkeit`, D-79) ist aufgenommen, als letzte Zieldatei nach R (Entscheidung Athlet 2026-09-29; `wissenskarten.md` kennt nur sechs Zieldateien).
- **Kapitelnummer** `<nn>` = Nummer der Kapiteldatei in `docs/literatur/<block>/<L-ID>_kapitel/` (`<L-ID>_<nn>[-<teil>]_<titel>.pdf|.md`), auch für Teile und Buchstaben-Nummern: je Kapiteldatei genau eine Extraktion, z. B. `<L-ID>_k05-1.md`, `<L-ID>_k00b.md` (Entscheidung Athlet 2026-09-29). Artikel: `<L-ID>_k00.md`; ein Corrigendum wird im selben Lauf mit dem Artikel extrahiert, korrigierte Werte markiert (L-T2-23, L-T2-24).
- **Vorspann** (`00`, `00a`) und **Anhänge** (`9x`: Glossar, Literatur, Index) werden gelistet, aber nicht extrahiert; ausgenommen die vier Dateien, die Vorspann und Einleitung/Foreword bündeln (L-T1-08, L-T2-04, L-T3-09, L-T3-21 jeweils `00`) – diese werden extrahiert (Entscheidungen Athlet 2026-09-29).
- **Kapitelauswahl der Bücher** (Entscheidung Athlet 2026-09-29): Kapitel ohne Bezug zu Zweck/Themenfeldern der Quelle (13.2), Zuschnitt (AP-06 Punkt 3) oder Profil werden nicht extrahiert; Grund steht in der Zeile („nicht extrahiert (außerhalb Zweck: …)“). Nachtrag bei konkreter Planungsfrage über den Lücken-Workflow (wissenskarten.md 8, U8).
- **EPUB-Quellen** (L-T3-10, -19, -20, -21; D-71): Eingabe ist die Markdown-Datei, `lesemethode: markdown_epub`; Stelle = „S. n“ aus den Seitenmarken (L-T3-20, -21) bzw. Kapitel und Abschnitt (L-T3-10, -19). Das Ansichts-PDF gleichen Namens zeigt nur Abbildungen, seine Seitenzahlen werden nie zitiert (Entscheidung Athlet 2026-09-29).
- **PDF-Quellen**: `lesemethode: pdf_nativ` (PDF direkt gelesen) bzw. `pdftotext_layout` (wissenskarten.md 4.1 Regel 9).

## 3. Legende der Statustabellen

| spalte | inhalt |
|---|---|
| L-ID | ID aus 13.2; fett = Kopfzeile eines Buchs (Kapitelordner); `A → B` = Verweis-ID auf Quelle B |
| Zieldatei(en) | UB = `uebergreifend-belastung-monitoring-erholung`, UP = `uebergreifend-planung-kombiniertes-training`, T1 = `t1-ausdauer`, T2 = `t2-kraft-haltung`, T3 = `t3-klettern`, R = `r-reha-praevention`, T4 = `t4-beweglichkeit` (Zuordnung AP-06 Punkt 3) |
| Datei/Kapitel | Artikel: Dateiname in `docs/literatur/<block>/`; Buch: `<nn>` und Kapiteltitel (Datei `<L-ID>_<nn>_…` im Kapitelordner der Kopfzeile) |
| seiten | Artikel: Seitenzahl des PDF; Kapitel: PDF-Seiten im Gesamtbuch, dazu Druckseiten, soweit in `docs/literatur/README.md` bestimmt; EPUB: Druckseiten laut Marken bzw. Wortzahl |
| extrahiert | `offen` · `<datum> / opus` (aus dem Kopf der Extraktionsdatei) · `entfällt` (Vorspann/Anhang oder Kapitelauswahl) · `in k00` (Corrigendum) |
| geprüft | `offen` · `<datum> / fable / freigabe ja\\|nein` (aus dem Kopf des Prüfprotokolls) · `entfällt` |
| bemerkung | Status (13.2) · Stufe (D-31) · Kern/optional (AP-06 Punkt 3) · Durchsuchbarkeit · nach der Extraktion: Zahl der Aussagen, davon `unsicher: true`, Zahl der offenen Stellen, Lesemethode · Hinweise (Seitenversatz, Lizenz, Muster aus Extraktion und Gegenprüfung) |

Durchsuchbarkeit (U1, 2026-09-29): `pdftotext -layout` auf Seite 1; ist Seite 1 leer (Kapitel-Titelbild), zusätzlich Seiten 2–3. „Text ✓“ = Seite 1 mit Text; „S. 1 ohne Text, ab S. 2 ✓“ = Titelseite ohne Text, Folgeseiten mit Text; Hinweis „x von n Seiten fast ohne Text“ ab einem Drittel (Abbruchregel U2 prüfen). Keine Datei war nicht durchsuchbar.

Quellen, die mehreren Zieldateien dienen, haben ihre Zeilen nur in der Tabelle der ersten Zieldatei (Reihenfolge W-04); spätere Tabellen verweisen darauf.

## 4. Statustabellen je Zieldatei

Reihenfolge nach W-04, T4 zuletzt. Innerhalb einer Tabelle: Kern vor optional in der Reihenfolge von AP-06 Punkt 3; die Extraktionsreihenfolge in U2 folgt Stufe A → B → C.
''')
HDR='| L-ID | Zieldatei(en) | Datei/Kapitel | seiten | extrahiert (Datum/Modell) | geprüft (Datum/Modell/freigabe) | bemerkung |\n|---|---|---|---|---|---|---|'
summary={}
for n,(k,name,block) in enumerate(NAMES,1):
    rows=R[k]
    ext=sum(1 for r in rows if re.search(r'\| (offen|\d{4}-\d\d-\d\d / [^|]*) \| (offen|\d{4}-[^|]*) \|',r))
    ent=sum(1 for r in rows if '| entfällt | entfällt |' in r and 'nicht zu extrahieren' in r)
    ausg=sum(1 for r in rows if '| entfällt | entfällt |' in r and 'außerhalb Zweck' in r)
    done=sum(1 for r in rows if re.search(r'\| \d{4}-\d\d-\d\d / ',r))
    fehl=[re.match(r'\| (\S+)',r).group(1) for r in rows if 'fehlt (Beschaffung' in r]
    prf=sum(1 for r in rows if 'freigabe ja' in r)
    summary[k]=(ext,ent,fehl,done,prf)
    A(f'### 4.{n} {k} – `{name}`\n')
    A(f'Ablage: `docs/extraktion/{block}/`' + (' (Quellen anderer Blöcke unter deren Block, D-51)' if k in ('UP','T4') else '') + '. Zu extrahieren in dieser Tabelle: ' + f'{ext} Dateien' + (f'; zusätzlich {FREMD[k]}' if k in FREMD else '') + f'. Davon extrahiert: {done}. Nicht zu extrahieren (Vorspann/Anhang): {ent}; ausgelassen nach Kapitelauswahl: {ausg}.\n')
    A(HDR)
    for r in rows: A(r)
    miss=M[k]
    fehlt_txt=', '.join(miss) if miss else 'keine'
    extra=''
    if k=='T3': extra=' L-T3-16 ist Stufe C (Planungsvorlage), aber `ausgewaehlt` – nach W-10 zählt es als Kernquelle; bestätigen. L-T3-09 in der Neuauflage (ab 03/2027) nicht gezählt, die 3. Aufl. gilt (D-70). L-T3-21 vorläufig (Bestätigung Athlet offen).'
    if k in ('UB','UP'): extra=' Die 7. Aufl. wird vorab extrahiert; nach Beschaffung der 8./9. Aufl. Abgleich bzw. Neuextraktion (Entscheidung Athlet).'
    A(f'| **Synthese startbereit** | {k} | **nein** (W-10) | – | – | – | Fehlende Kernquellen: {fehlt_txt}. Stand Extraktion: {summary[k][3]} von {ext} extrahiert; gegengeprüft: {summary[k][4]}.{extra} |\n')
A('''## 5. Lizenz vor Ablage prüfen (Athlet)

Aus AP-06 Punkt 4 (offene Punkte). Die Code-Instanz entscheidet nicht; die Angabe stammt aus `docs/literatur/README.md` (Lizenz laut Volltext).

| L-ID | Zieldatei | Lizenz laut Volltext | Vermerk |
|---|---|---|---|''')
for i,z in [('L-P15','UB'),('L-T2-15','T2, T4'),('L-T2-16','T2'),('L-T2-22','T2'),('L-T2-23','T2'),('L-T2-25','UP')]:
    A(f'| {i} | {z} | {g["liz"][i]} | Lizenz vor Ablage prüfen (Athlet) |')
A('''
L-P15 steht nicht in der Liste des Auftrags, aber in AP-06 Punkt 4 („Lizenz L-P15 vor Ablage prüfen“); daher aufgenommen. 13.4 vermerkt außerdem allgemein „Lizenzen vor Ablage prüfen“ für die frei verfügbaren Artikel aus Block R und T4 (D-31, V-20).

## 6. Seitenbezug je Quelle (Muster für die Gegenprüfung)

Zitiert wird die **gedruckte Seite** (docs/literatur/README.md), bei EPUB nach D-71. Bekannte Abweichungen PDF-Seite ≠ Druckseite; Befunde aus U2/U3 werden hier je Quelle ergänzt.

| L-ID | seitenbezug | quelle des befunds |
|---|---|---|
| L-T1-08 | Scan; Druckseite = PDF-Seite − 2, im Bereich PDF 88–152 − 4 (PDF 88–89 wiederholen 86–87; Druck 149–150 fehlen) | docs/literatur/README.md |
| L-T2-04 | Scan, fehlerhafte Texterkennung; Druckseite = PDF-Seite − 14; PDF 577/578 vertauscht (Druck 564/563) | docs/literatur/README.md |
| L-T3-09 | Scan; Druckseite = PDF-Seite − 16; Vorspann ohne Druckseiten | docs/literatur/README.md |
| L-T4-32 | Druckseite = PDF-Seite − 15 | docs/literatur/README.md |
| L-T4-34 | Druckseite = PDF-Seite − 11 | docs/literatur/README.md |
| L-T3-20, L-T3-21 | EPUB mit Seitenmarken „[S. n]“ im Markdown | D-71 |
| L-T3-10, L-T3-19 | EPUB ohne Seitenmarken: Kapitel und Abschnitt (V-17) | D-71 |
| L-T2-29, L-R-18 | Autorenmanuskript, Seitenzahlen nicht zitierfähig | docs/literatur/README.md |
| alle übrigen Bücher | Versatz je Kapitel in U2 bestimmen (erste Druckseite des Kapitels im PDF) | – |
| Artikel | Seite laut Zeitschriften-Paginierung auf dem PDF | – |
''')
import os
NOTES=json.load(open(P+'notes.json')) if os.path.exists(P+'notes.json') else {}
for i,t in NOTES.items():
    if t.startswith('Muster:'): A(f'| {i} | {t[7:].strip()} | U2-Extraktion (Rückmeldung Unteragent) |')
A('')
open('docs/extraktion/README.md','w',encoding='utf-8').write('\n'.join(out))
json.dump(summary,open(P+'summary.json','w'),ensure_ascii=False)
print(summary)
