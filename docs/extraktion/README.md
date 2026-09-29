---
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
| geprüft | `offen` · `<datum> / fable / freigabe ja\|nein` (aus dem Kopf des Prüfprotokolls) · `entfällt` |
| bemerkung | Status (13.2) · Stufe (D-31) · Kern/optional (AP-06 Punkt 3) · Durchsuchbarkeit · nach der Extraktion: Zahl der Aussagen, davon `unsicher: true`, Zahl der offenen Stellen, Lesemethode · Hinweise (Seitenversatz, Lizenz, Muster aus Extraktion und Gegenprüfung) |

Durchsuchbarkeit (U1, 2026-09-29): `pdftotext -layout` auf Seite 1; ist Seite 1 leer (Kapitel-Titelbild), zusätzlich Seiten 2–3. „Text ✓“ = Seite 1 mit Text; „S. 1 ohne Text, ab S. 2 ✓“ = Titelseite ohne Text, Folgeseiten mit Text; Hinweis „x von n Seiten fast ohne Text“ ab einem Drittel (Abbruchregel U2 prüfen). Keine Datei war nicht durchsuchbar.

Quellen, die mehreren Zieldateien dienen, haben ihre Zeilen nur in der Tabelle der ersten Zieldatei (Reihenfolge W-04); spätere Tabellen verweisen darauf.

## 4. Statustabellen je Zieldatei

Reihenfolge nach W-04, T4 zuletzt. Innerhalb einer Tabelle: Kern vor optional in der Reihenfolge von AP-06 Punkt 3; die Extraktionsreihenfolge in U2 folgt Stufe A → B → C.

### 4.1 UB – `uebergreifend-belastung-monitoring-erholung`

Ablage: `docs/extraktion/uebergreifend/`. Zu extrahieren in dieser Tabelle: 41 Dateien. Davon extrahiert: 41. Nicht zu extrahieren (Vorspann/Anhang): 6; ausgelassen nach Kapitelauswahl: 19.

| L-ID | Zieldatei(en) | Datei/Kapitel | seiten | extrahiert (Datum/Modell) | geprüft (Datum/Modell/freigabe) | bemerkung |
|---|---|---|---|---|---|---|
| L-P03 | UB | `L-P03_Bourdon-2017_Monitoring-Training-Loads-Consensus.pdf` | 10 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 49 Aussagen · 1 unsicher · 0 offene Stellen · pdf_nativ · Muster: Seiten S2-161–S2-170 (Supplement-Paginierung), Versatz PDF n → S2-(160+n); Tab. 1 im PDF gedreht |
| L-P04 | UB | `L-P04_Impellizzeri-2019_Internal-and-External-Training-Load.pdf` | 4 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 33 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ · Muster: PDF ist Ahead-of-Print-Fassung, Seiten 1–4 der Vorabpaginierung (nicht Heftpaginierung), Versatz 0 |
| L-P05 | UB | `L-P05_Kellmann-2018_Recovery-and-Performance-Consensus.pdf` | 6 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 50 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 239+n |
| L-P06 | UB | `L-P06_Meeusen-2013_Overtraining-Syndrome-Consensus.pdf` | 20 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 125 Aussagen · 1 unsicher · 5 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 185+n; Abb. 3 nur als Bild (gerendert gelesen); pdftotext liest in Tab. 1 „95%“ statt „>5%“ |
| **L-A01** | UB, UP | **Ordner `uebergreifend/L-A01_kapitel/`** (31 Kapitel-PDFs, 1379 PDF-Seiten) | – | – | – | ausgewaehlt · B · Kern · 7. Aufl. 2019 vorläufig (D-51); **8./9. Aufl. fehlt (Beschaffung, Athlet)** · Muster: Druckseite = Gesamtbuch-PDF-Seite − 1 in allen 18 Kapiteln (Fußzeilen-Paginierung des E-Books; Übereinstimmung mit Druckausgabe nicht prüfbar); Kapiteldateien enthalten Teil-Einleitungen und Bildseiten am Rand, Literaturverzeichnis fehlt je Kapitel; viele Werte aus Grafiken abgelesen (unsicher); zahlreiche Widersprüche Text/Abbildung/Zusammenfassung im Buch selbst unter Offene Stellen |
| L-A01 | UB, UP | `00a` Vorspann | PDF 1–34 | entfällt | entfällt | nicht zu extrahieren (Vorspann) · S. 1 ohne Text, ab S. 2 ✓ |
| L-A01 | UB, UP | `00b` Introduction: An Introduction to Exercise and Sport Physiology | PDF 35–93 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Einführung ins Fach; Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-A01 | UB, UP | `01` Structure and Function of Exercising Muscle | PDF 94–143 | 2026-09-29 / opus | offen | Text ✓ · 66 Aussagen · 1 unsicher · 4 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `02` Fuel for Exercise: Bioenergetics and Muscle Metabolism | PDF 144–189 | 2026-09-29 / opus | offen | Text ✓ · 82 Aussagen · 5 unsicher · 5 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `03` Neural Control of Exercising Muscle | PDF 190–232 | 2026-09-29 / opus | offen | Text ✓ · 71 Aussagen · 0 unsicher · 2 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `04` Hormonal Control During Exercise | PDF 233–280 | 2026-09-29 / opus | offen | Text ✓ · 74 Aussagen · 3 unsicher · 6 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `05-1` Energy Expenditure, Fatigue, and Muscle Soreness (Teil 1/2) | PDF 281–311 | 2026-09-29 / opus | offen | Text ✓ · 76 Aussagen · 5 unsicher · 6 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `05-2` Energy Expenditure, Fatigue, and Muscle Soreness (Teil 2/2) | PDF 312–348 | 2026-09-29 / opus | offen | Text ✓ · 77 Aussagen · 7 unsicher · 5 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `06` The Cardiovascular System and Its Control | PDF 349–403 | 2026-09-29 / opus | offen | Text ✓ · 63 Aussagen · 4 unsicher · 6 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `07` The Respiratory System and Its Regulation | PDF 404–446 | 2026-09-29 / opus | offen | Text ✓ · 63 Aussagen · 5 unsicher · 7 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `08` Cardiorespiratory Responses to Acute Exercise | PDF 447–501 | 2026-09-29 / opus | offen | Text ✓ · 126 Aussagen · 14 unsicher · 0 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `09` Principles of Exercise Training | PDF 502–546 | 2026-09-29 / opus | offen | Text ✓ · 105 Aussagen · 1 unsicher · 5 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `10` Adaptations to Resistance Training | PDF 547–586 | 2026-09-29 / opus | offen | Text ✓ · 87 Aussagen · 1 unsicher · 2 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `11-1` Adaptations to Aerobic and Anaerobic Training (Teil 1/2) | PDF 587–620 | 2026-09-29 / opus | offen | Text ✓ · 75 Aussagen · 5 unsicher · 3 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `11-2` Adaptations to Aerobic and Anaerobic Training (Teil 2/2) | PDF 621–655 | 2026-09-29 / opus | offen | Text ✓ · 90 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `12-1` Exercise in Hot and Cold Environments (Teil 1/2) | PDF 656–684 | 2026-09-29 / opus | offen | Text ✓ · 69 Aussagen · 1 unsicher · 2 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `12-2` Exercise in Hot and Cold Environments (Teil 2/2) | PDF 685–722 | 2026-09-29 / opus | offen | Text ✓ · 66 Aussagen · 1 unsicher · 4 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `13` Exercise at Altitude | PDF 723–767 | 2026-09-29 / opus | offen | Text ✓ · 89 Aussagen · 6 unsicher · 4 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `14` Training for Sport | PDF 768–819 | 2026-09-29 / opus | offen | Text ✓ · 105 Aussagen · 7 unsicher · 0 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `15-1` Body Composition and Nutrition for Sport (Teil 1/2) | PDF 820–861 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Ernährung; Athlet 2026-09-29) · Text ✓ |
| L-A01 | UB, UP | `15-2` Body Composition and Nutrition for Sport (Teil 2/2) | PDF 862–902 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Ernährung; Athlet 2026-09-29) · Text ✓ |
| L-A01 | UB, UP | `16` Ergogenic Aids in Sport | PDF 903–960 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: ergogene Hilfsmittel; Athlet 2026-09-29) · Text ✓ |
| L-A01 | UB, UP | `17` Children and Adolescents in Sport and Exercise | PDF 961–1005 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Kinder; Athlet 2026-09-29) · Text ✓ |
| L-A01 | UB, UP | `18` Aging in Sport and Exercise | PDF 1006–1055 | 2026-09-29 / opus | offen | Text ✓ · 99 Aussagen · 8 unsicher · 7 offene Stellen · pdf_nativ |
| L-A01 | UB, UP | `19` Sex Differences in Sport and Exercise | PDF 1056–1103 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Geschlecht; Athlet 2026-09-29) · Text ✓ |
| L-A01 | UB, UP | `20` Prescription of Exercise for Health and Fitness | PDF 1104–1148 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Gesundheitssport; Athlet 2026-09-29) · Text ✓ |
| L-A01 | UB, UP | `21` Cardiovascular Disease and Physical Activity | PDF 1149–1197 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Herz-Kreislauf-Erkrankungen; Athlet 2026-09-29) · Text ✓ |
| L-A01 | UB, UP | `22` Obesity, Diabetes, and Physical Activity | PDF 1198–1247 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Adipositas/Diabetes; Athlet 2026-09-29) · Text ✓ |
| L-A01 | UB, UP | `90` Glossary | PDF 1248–1277 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| L-A01 | UB, UP | `91` References | PDF 1278–1321 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| L-A01 | UB, UP | `92` Index | PDF 1322–1379 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| **L-A02** | UB, UP, T1 | **Ordner `uebergreifend/L-A02_kapitel/`** (25 Kapitel-PDFs, 911 PDF-Seiten) | – | – | – | ausgewaehlt · B · Kern · Druckseiten je Kapitel in U2 bestimmen · Muster: Versatz Gesamtbuch-PDF → Druckseite je Kapitel verschieden (k01 −17, k02 −16, k03 −15, k04 −14, k06 −12, k07 −11, k09 −9, k12 −7), innerhalb der Kapiteldatei konstant; Kapiteldatei-Seite 1 = erste Druckseite des Kapitels; Kapitelteile beginnen/enden mitten im Abschnitt; Literaturverzeichnis je Kapitel enthalten; Tab. 4.9–4.11 nur als Bild |
| L-A02 | UB, UP, T1 | `00` Vorspann | PDF 1–17 | entfällt | entfällt | nicht zu extrahieren (Vorspann) · Text ✓ |
| L-A02 | UB, UP, T1 | `01` Aufgaben und Inhalte der Trainingswissenschaft | PDF 18–44 | 2026-09-29 / opus | offen | Text ✓ · 50 Aussagen · 1 unsicher · 0 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1, T1 | `02` Grundlagenwissen zum sportlichen Training | PDF 45–95 | 2026-09-29 / opus | offen | Text ✓ · 91 Aussagen · 3 unsicher · 0 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1 | `03-1` Leistungssteuerung (Teil 1/3) | PDF 96–140 | 2026-09-29 / opus | offen | Text ✓ · 86 Aussagen · 2 unsicher · 0 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1, T1 | `03-2` Leistungssteuerung (Teil 2/3) | PDF 141–183 | 2026-09-29 / opus | offen | Text ✓ · 82 Aussagen · 0 unsicher · 1 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1, T1 | `03-3` Leistungssteuerung (Teil 3/3) | PDF 184–226 | 2026-09-29 / opus | offen | Text ✓ · 76 Aussagen · 0 unsicher · 4 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1 | `04-1` Krafttraining (Teil 1/2) | PDF 227–267 | 2026-09-29 / opus | offen | Text ✓ · 102 Aussagen · 3 unsicher · 5 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1 | `04-2` Krafttraining (Teil 2/2) | PDF 268–307 | 2026-09-29 / opus | offen | Text ✓ · 107 Aussagen · 2 unsicher · 6 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1 | `05-1` Schnelligkeitstraining (Teil 1/2) | PDF 308–342 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Schnelligkeit; Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `05-2` Schnelligkeitstraining (Teil 2/2) | PDF 343–380 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Schnelligkeit; Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `06` Beweglichkeitstraining | PDF 381–407 | 2026-09-29 / opus | offen | Text ✓ · 81 Aussagen · 1 unsicher · 0 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1, T1 | `07-1` Ausdauertraining (Teil 1/2) | PDF 408–445 | 2026-09-29 / opus | offen | Text ✓ · 82 Aussagen · 4 unsicher · 1 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1, T1 | `07-2` Ausdauertraining (Teil 2/2) | PDF 446–482 | 2026-09-29 / opus | offen | Text ✓ · 109 Aussagen · 3 unsicher · 0 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1 | `08` Techniktraining | PDF 483–531 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Techniktraining; Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `09-1` Regenerationsmanagement und Ernährung (Teil 1/2) | PDF 532–563 | 2026-09-29 / opus | offen | Text ✓ · 103 Aussagen · 1 unsicher · 2 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1 | `09-2` Regenerationsmanagement und Ernährung (Teil 2/2) | PDF 564–594 | 2026-09-29 / opus | offen | Text ✓ · 90 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1 | `10` Training im Kindes- und Jugendalter | PDF 595–647 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Kinder und Jugendliche; Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `11-1` Training mit Frauen (Teil 1/2) | PDF 648–684 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Geschlecht; Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `11-2` Training mit Frauen (Teil 2/2) | PDF 685–719 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Geschlecht; Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `12` Training im mittleren und höheren Lebensalter | PDF 720–761 | 2026-09-29 / opus | offen | Text ✓ · 64 Aussagen · 2 unsicher · 2 offene Stellen · pdf_nativ |
| L-A02 | UB, UP, T1 | `13` Trainingswissenschaft in den Ausdauersportarten | PDF 762–784 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: nur Schwimmen/Triathlon (L-T1-15); Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `14-1` Trainingswissenschaft in den Mannschaftssportarten (Teil 1/2) | PDF 785–821 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Mannschaftssport; Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `14-2` Trainingswissenschaft in den Mannschaftssportarten (Teil 2/2) | PDF 822–860 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Mannschaftssport; Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `15` Trainingswissenschaft in den Rückschlagsportarten | PDF 861–900 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Rückschlagsport; Athlet 2026-09-29) · Text ✓ |
| L-A02 | UB, UP, T1 | `90` Serviceteil | PDF 901–911 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| L-P10 | UB | `L-P10_Foster-2001_Monitoring-Exercise-Training-sRPE.pdf` | 7 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 41 Aussagen · 2 unsicher · 0 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 108+n; Befund Extraktion: Tab. 5 Lastwerte teils ≠ RPE × Dauer (Druckfehler im Original?), Tab. 4 SD auffällig – Gegenprüfung beachten |
| L-P11 | UB | `L-P11_Saw-2016_Monitoring-Athlete-Training-Response.pdf` | 14 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 50 Aussagen · 0 unsicher · 3 offene Stellen · pdf_nativ · Muster: Zeitschriftenseiten 281–291 nur als Bereich in der Fußzeile, Einzelseiten „n of 13“; Stelle als „S. n von 13, Abschnitt …“; Online-Tab. S1 nicht im PDF |
| L-P12 | UB | `L-P12_Impellizzeri-2020_ACWR-Conceptual-Issues.pdf` | 7 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 43 Aussagen · 0 unsicher · 2 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 906+n; Befund: Gruppengrößen Text vs. Tab. 1 widersprüchlich (im Original), unter Offene Stellen |
| L-P13 | UB | `L-P13_Silbernagel-2007_Pain-Monitoring-Model-Achilles.pdf` | 10 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 48 Aussagen · 1 unsicher · 4 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 896+n; Befund: Widersprüche Text/Tabellen im Original (Baseline VISA-A-S 57/58, Tab. 7 Signifikanz, fehlende Einheiten), unter Offene Stellen |
| L-P15 | UB | `L-P15_Manresa-Rocamora-2021_HRV-Guided-Training-Meta-Analysis.pdf` | 22 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · **Lizenz vor Ablage prüfen (Athlet)** · 51 Aussagen · 1 unsicher · 2 offene Stellen · pdf_nativ · Muster: Artikelnummer 10299, Seiten „n of 22“, Versatz 0; Befund: Text vs. Tab. 2 widersprüchlich (Referenzfenster, Stabilisierung), unter Offene Stellen |
| L-P16 | UB | `L-P16_Dueking-2021_HRV-Guided-Training-Wearables.pdf` | 13 (PDF) | 2026-09-29 / opus | offen | optional · A · optional · Text ✓ · 43 Aussagen · 2 unsicher · 9 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 1179+n; Befund: mehrere Inkonsistenzen im Original (Abstract vertauscht g-Werte, N 198 vs. 228, Tab. 2), unter Offene Stellen |
| **Synthese startbereit** | UB | **nein** (W-10) | – | – | – | Fehlende Kernquellen: L-A01 (8./9. Aufl.; 7. Aufl. liegt vorläufig vor). Stand Extraktion: 41 von 41 extrahiert; gegengeprüft: 0. Die 7. Aufl. wird vorab extrahiert; nach Beschaffung der 8./9. Aufl. Abgleich bzw. Neuextraktion (Entscheidung Athlet). |

### 4.2 UP – `uebergreifend-planung-kombiniertes-training`

Ablage: `docs/extraktion/uebergreifend/` (Quellen anderer Blöcke unter deren Block, D-51). Zu extrahieren in dieser Tabelle: 9 Dateien; zusätzlich L-A01, L-A02 (Tabelle UB). Davon extrahiert: 9. Nicht zu extrahieren (Vorspann/Anhang): 0; ausgelassen nach Kapitelauswahl: 0.

| L-ID | Zieldatei(en) | Datei/Kapitel | seiten | extrahiert (Datum/Modell) | geprüft (Datum/Modell/freigabe) | bemerkung |
|---|---|---|---|---|---|---|
| L-P01 | UP | `L-P01_Kiely-2018_Periodization-Theory.pdf` | 12 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 53 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 752+n; Meinungsbeitrag ohne Studiendaten; Verweisnummern im Original teils falsch |
| L-P02 | UP | `L-P02_Mujika-2018_Integrated-Approach-to-Periodization.pdf` | 24 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 104 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 537+n; Teamsport-Abschnitte ausgelassen; Proteinverteilung 3–5 h vs. 3–4 h im Original |
| L-P07 | UP | `L-P07_Schumann-2022_Concurrent-Training-Meta-Analysis.pdf` | 12 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 42 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 600+n; Forest-Plots als Bild (hochaufgelöst gelesen); Befund: SMD Hypertrophie −0,01 (Text) vs. +0,01 (Abb. 4) u. a., in den Aussagen vermerkt; Supplement fehlt |
| L-P08 | UP, T2 | `L-P08_Currier-2026_ACSM-Resistance-Training-Prescription.pdf` | 22 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 51 Aussagen · 0 unsicher · 4 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 850+n; Befund: Power-Volumen ≤24 vs. <24, Hypertrophie-Volumen „pro Muskelgruppe“ nur im Text; Ergänzungsanhänge fehlen |
| L-P09 | UP | `L-P09_Held-2026_Concurrent-Training-Umbrella-Review.pdf` | 24 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 64 Aussagen · 5 unsicher · 6 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 1488+n; Befund: Quelle inkonsistent (p-Werte Abstract vs. Ergebnisse, Vorzeichen Text vs. Abb. 5, Referenznummern in Abb. 2–4) – Gegenprüfung mit vollem Umfang |
| L-A01 | UB, UP | siehe Tabelle UB | – | siehe Tabelle UB | siehe Tabelle UB | ausgewaehlt · B · Kern; Zeilen nur einmal geführt |
| L-A02 | UB, UP, T1 | siehe Tabelle UB | – | siehe Tabelle UB | siehe Tabelle UB | ausgewaehlt · B · Kern; Zeilen nur einmal geführt |
| L-T2-25 | UP | `L-T2-25_Lundberg-2022_Concurrent-Training-Fiber-Hypertrophy.pdf` | 13 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · **Lizenz vor Ablage prüfen (Athlet)** · 38 Aussagen · 0 unsicher · 3 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 2390+n; Befund: Referenznummern in Forest-Plots um eins verschoben, Fig. 3 Einzelwerte vertauscht, QM/p in Fig. 2 inkonsistent; Supplement fehlt |
| L-T2-26 | UP | `L-T2-26_Monserda-Vilaro-2023_Concurrent-Continuous-vs-Intermittent.pdf` | 22 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 62 Aussagen · 6 unsicher · 5 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 687+n; Befund: Forest-Plots 7/8/9/11/13 falsch beschriftet (nach Text extrahiert, unsicher); Abstract vs. Ergebnisse (p = 0.15); Studienzahl 22 vs. 25 |
| L-T2-31 | UP | `L-T2-31_Wilson-2012_Concurrent-Training-Interference.pdf` | 15 (PDF) | 2026-09-29 / opus | offen | optional · A · optional · Text ✓ · 62 Aussagen · 5 unsicher · 7 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 2292+n; Befund: Druckfehler in KI (Tab. 1, 3), Vorzeichen Korrelation Abstract vs. Ergebnis, Summe Effektstärken 330 vs. 422 |
| L-T2-32 | UP | `L-T2-32_Sabag-2018_Concurrent-HIIT-and-Resistance.pdf` | 13 (PDF) | 2026-09-29 / opus | offen | optional · A · optional · Text ✓ · 42 Aussagen · 2 unsicher · 5 offene Stellen · pdf_nativ · Muster: PDF-Seite 1 = Verlagsdeckblatt, gedruckt = PDF − 1 (Online-Paginierung 1–12); Befund: KI/p-Werte im Original inkonsistent (Rad-HIIT, Pause > 24 h) |
| **Synthese startbereit** | UP | **nein** (W-10) | – | – | – | Fehlende Kernquellen: L-A01 (8./9. Aufl.; 7. Aufl. liegt vorläufig vor). Stand Extraktion: 9 von 9 extrahiert; gegengeprüft: 0. Die 7. Aufl. wird vorab extrahiert; nach Beschaffung der 8./9. Aufl. Abgleich bzw. Neuextraktion (Entscheidung Athlet). |

### 4.3 T1 – `t1-ausdauer`

Ablage: `docs/extraktion/t1-ausdauer/`. Zu extrahieren in dieser Tabelle: 35 Dateien; zusätzlich L-A02 Kap. 02, 03-2, 03-3, 07-1, 07-2 (Tabelle UB). Davon extrahiert: 35. Nicht zu extrahieren (Vorspann/Anhang): 5; ausgelassen nach Kapitelauswahl: 17.

| L-ID | Zieldatei(en) | Datei/Kapitel | seiten | extrahiert (Datum/Modell) | geprüft (Datum/Modell/freigabe) | bemerkung |
|---|---|---|---|---|---|---|
| L-T1-02 | T1 | `L-T1-02_Seiler-2010_Intensity-and-Duration-Distribution.pdf` | 16 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 72 Aussagen · 2 unsicher · 5 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 275+n; Tab. 2 „?%“ im Original, Z1-Verteilung ergibt 101 % |
| L-T1-03 | T1 | `L-T1-03_Casado-2022_Periodization-Elite-Distance-Runners.pdf` | 14 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 70 Aussagen · 2 unsicher · 0 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 819+n; Tab. 3/4 gedreht (mit pdftotext abgeglichen); Grafikwerte Abb. 2 unsicher |
| L-T1-04 | T1 | `L-T1-04_Haugen-2022_World-Class-Distance-Runners.pdf` | 18 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 91 Aussagen · 1 unsicher · 3 offene Stellen · pdf_nativ · Muster: Artikel 46, Seiten „Page n of 18“, Versatz 0; Befund: Medaillensummen Tab. 1, 6- vs. 7-Zonen-Skala, Fußnoten b/c in Tab. 3 vertauscht |
| L-T1-05 | T1 | `L-T1-05_Vernillo-2017_Uphill-and-Downhill-Running.pdf` | 15 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 88 Aussagen · 7 unsicher · 6 offene Stellen · pdf_nativ · Muster: Online-First-Fassung ohne Seitenzahlen – Stelle als Abschnitt/Tabelle/Abbildung, seiten „–“; Befund: Studienzuordnung Tab. 2 (Padulo vs. Lussiana), Cr-Formel-Einheit, Tibialis-Richtung Text vs. Tab. 3 |
| L-T1-06 | T1 | `L-T1-06_Bortolan-2021_Ski-Mountaineering-Perspectives.pdf` | 7 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 61 Aussagen · 3 unsicher · 3 offene Stellen · pdf_nativ · Muster: Artikel 737249, Seiten 1–7, Versatz 0; Befund: Korrelationsrichtung Rennzeit–VO2max Abstract vs. Text widersprüchlich; Populationen teils aus Literaturliste (markiert) |
| **L-T1-07** | T1 | **Ordner `t1-ausdauer/L-T1-07_kapitel/`** (34 Kapitel-PDFs, 673 PDF-Seiten) | – | – | – | ausgewaehlt · B · Kern · Druckseiten je Kapitel in U2 bestimmen · Muster: Druckseite = Gesamtbuch-PDF-Seite − 7 in allen Kapiteln; Kapiteldateien enden teils mit Leerseite („intentionally left blank“); Literaturverzeichnis nicht in den Kapiteldateien; HIIT-Typen und Abkürzungen (VIFT, APR …) nur in einzelnen Kapiteln definiert; Text vs. Abbildung bei Intervallwerten mehrfach abweichend (k04, k10) |
| L-T1-07 | T1 | `00` Vorspann | PDF 1–7 | entfällt | entfällt | nicht zu extrahieren (Vorspann) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T1-07 | T1 | `01` Genesis and Evolution of High- Intensity Interval Training | PDF 8–23 | 2026-09-29 / opus | offen | S. 1 ohne Text, ab S. 2 ✓ · 48 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `02` Traditional Methods of HIIT Programming | PDF 24–39 | 2026-09-29 / opus | offen | Text ✓ · 54 Aussagen · 1 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `03` Physiological Targets of HIIT | PDF 40–57 | 2026-09-29 / opus | offen | Text ✓ · 88 Aussagen · 1 unsicher · 3 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `04` Manipulating HIIT Variables | PDF 58–79 | 2026-09-29 / opus | offen | Text ✓ · 63 Aussagen · 2 unsicher · 5 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `05` Using HIIT Weapons | PDF 80–125 | 2026-09-29 / opus | offen | Text ✓ · 94 Aussagen · 8 unsicher · 3 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `06` Incorporating HIIT Into a Concurrent Training Program | PDF 126–143 | 2026-09-29 / opus | offen | Text ✓ · 76 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `07` HIIT and Its Influence on Stress, Fatigue, and Athlete Health | PDF 144–167 | 2026-09-29 / opus | offen | Text ✓ · 88 Aussagen · 2 unsicher · 2 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `08` Quantifying Training Load | PDF 168–185 | 2026-09-29 / opus | offen | Text ✓ · 56 Aussagen · 3 unsicher · 3 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `09` Response to Load | PDF 186–219 | 2026-09-29 / opus | offen | Text ✓ · 83 Aussagen · 2 unsicher · 2 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `10` Putting It All Together | PDF 220–231 | 2026-09-29 / opus | offen | Text ✓ · 57 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `11` Combat Sports | PDF 232–253 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T1-07 | T1 | `12` Cross-Country Skiing | PDF 254–267 | 2026-09-29 / opus | offen | Text ✓ · 68 Aussagen · 2 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `13` Middle-Distance Running | PDF 268–289 | 2026-09-29 / opus | offen | Text ✓ · 62 Aussagen · 0 unsicher · 2 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `14` Road Running | PDF 290–303 | 2026-09-29 / opus | offen | Text ✓ · 65 Aussagen · 3 unsicher · 4 offene Stellen · pdf_nativ |
| L-T1-07 | T1 | `15` Road Cycling | PDF 304–317 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `16` Rowing | PDF 318–331 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `17` Swimming | PDF 332–353 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `18` Tennis | PDF 354–369 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `19` Triathlon | PDF 370–385 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `20` American Football | PDF 386–399 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `21` Australian Football | PDF 400–417 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `22` Baseball | PDF 418–431 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `23` Basketball | PDF 432–449 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `24` Cricket | PDF 450–461 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `25` Field Hockey | PDF 462–483 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `26` Ice Hockey | PDF 484–501 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `27` Handball | PDF 502–517 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `28` Rugby Union | PDF 518–531 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `29` Rugby Sevens | PDF 532–553 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `30` Soccer | PDF 554–571 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Sportart ohne Bezug; Athlet 2026-09-29) · Text ✓ |
| L-T1-07 | T1 | `90-1` References (Teil 1/2) | PDF 572–612 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| L-T1-07 | T1 | `90-2` References (Teil 2/2) | PDF 613–653 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| L-T1-07 | T1 | `91` Index and Contributors | PDF 654–673 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| **L-T1-08** | T1 | **Ordner `t1-ausdauer/L-T1-08_kapitel/`** (15 Kapitel-PDFs, 380 PDF-Seiten) | – | – | – | ausgewaehlt · C · Kern · Scan; Druckseite = PDF-Seite − 2, im Bereich PDF 88–152 − 4 · Muster: Scan; Druckseite = Gesamtbuch-PDF − 2, im Bereich PDF 88–152 − 4 (bestätigt); Druckseiten 149–150 fehlen im Scan (Athletengeschichte k04 bricht ab); viele Seiten ohne gedruckte Zahl (Kapitelanfang, Fotos) – aus Nachbarseiten erschlossen; Korrektur k08 (abgeleiteter Wert entfernt), k04 (typ erfahrung → praxis) |
| L-T1-08 | T1 | `00` Vorspann und Foreword | PDF 1–18 | 2026-09-29 / opus | offen | S. 1 ohne Text, ab S. 2 ✓; 6 von 18 Seiten fast ohne Text · Vorspann mit Einleitung – extrahieren (Entscheidung Athlet 2026-09-29) · 12 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `01` How to Use This Book | PDF 19–22; Druck 17–20 | 2026-09-29 / opus | offen | Text ✓ · 14 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `02` The Physiology of Endurance | PDF 23–70; Druck 21–68 | 2026-09-29 / opus | offen | S. 1 ohne Text, ab S. 2 ✓ · 69 Aussagen · 2 unsicher · 5 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `03` The Methodologies of Endurance Training | PDF 71–120; Druck 69–116 | 2026-09-29 / opus | offen | S. 1 ohne Text, ab S. 2 ✓ · 96 Aussagen · 2 unsicher · 4 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `04` Monitoring Your Training | PDF 121–152; Druck 117–148 | 2026-09-29 / opus | offen | Text ✓ · 66 Aussagen · 0 unsicher · 3 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `05` The Application Process | PDF 153–192; Druck 151–190 | 2026-09-29 / opus | offen | Text ✓ · 85 Aussagen · 2 unsicher · 4 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `06` Strength Training for the Uphill Athlete | PDF 193–204; Druck 191–202 | 2026-09-29 / opus | offen | S. 1 ohne Text, ab S. 2 ✓; 4 von 12 Seiten fast ohne Text · 37 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `07` General Strength Assessment and Improvement | PDF 205–240; Druck 203–238 | 2026-09-29 / opus | offen | Text ✓ · 48 Aussagen · 1 unsicher · 4 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `08` Specific Strength-Training Methods | PDF 241–256; Druck 239–254 | 2026-09-29 / opus | offen | Text ✓ · 58 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `09` Programming | PDF 257–270; Druck 255–268 | 2026-09-29 / opus | offen | **nicht durchsuchbar**; 6 von 14 Seiten fast ohne Text · 28 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `10` Transition Period Training | PDF 271–278; Druck 269–276 | 2026-09-29 / opus | offen | Text ✓ · 25 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `11` Introduction to the Base Period | PDF 279–312; Druck 277–310 | 2026-09-29 / opus | offen | Text ✓ · 74 Aussagen · 9 unsicher · 8 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `12` Special Considerations for Skimo and Ski Mountaineering | PDF 313–338; Druck 311–336 | 2026-09-29 / opus | offen | Text ✓ · 37 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `13` Special Considerations for Mountain Running | PDF 339–364; Druck 337–362 | 2026-09-29 / opus | offen | Text ✓ · 57 Aussagen · 3 unsicher · 7 offene Stellen · pdf_nativ |
| L-T1-08 | T1 | `90` Glossary and Index | PDF 365–380; Druck 363–378 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| L-T1-15 → L-A02 | T1 | L-A02 Kap. 02, 03-2, 03-3, 07-1, 07-2 (Abschnitte laut `kapitel_t1`) | – | siehe Tabelle UB | siehe Tabelle UB | Verweis (Kern); Kapitelzeilen stehen in 4.1 (UB) mit T1 in „Zieldatei(en)“ |
| L-T1-09 | T1 | `L-T1-09_Toennessen-2024_Training-Session-Models.pdf` | 19 (PDF) | 2026-09-29 / opus | offen | optional · C · optional · Text ✓ · 60 Aussagen · 0 unsicher · 2 offene Stellen · pdf_nativ |
| L-T1-10 | T1 | `L-T1-10_Sandbakk-2025_Best-Practice-Norwegian-Coaches.pdf` | 23 (PDF) | 2026-09-29 / opus | offen | optional · C · optional · Text ✓ · 66 Aussagen · 2 unsicher · 6 offene Stellen · pdf_nativ |
| L-T1-11 | T1 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T1-12 | T1 | `L-T1-12_Joyner-2008_Physiology-of-Champions.pdf` | 10 (PDF) | 2026-09-29 / opus | offen | optional · A · optional · Text ✓ · 55 Aussagen · 0 unsicher · 3 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 34+n; Laktatanstieg 75–90 % (Text) vs. 75–85 % (Abb. 4) im Original |
| L-T1-14 | T1 | – | – | – | – | optional · B · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T1-16 | T1 | – | – | – | – | optional · C · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| **Synthese startbereit** | T1 | **nein** (W-10) | – | – | – | Fehlende Kernquellen: keine. Stand Extraktion: 35 von 35 extrahiert; gegengeprüft: 0. |

### 4.4 T2 – `t2-kraft-haltung`

Ablage: `docs/extraktion/t2-kraft/`. Zu extrahieren in dieser Tabelle: 76 Dateien; zusätzlich L-P08 (Tabelle UP). Davon extrahiert: 22. Nicht zu extrahieren (Vorspann/Anhang): 5; ausgelassen nach Kapitelauswahl: 48.

| L-ID | Zieldatei(en) | Datei/Kapitel | seiten | extrahiert (Datum/Modell) | geprüft (Datum/Modell/freigabe) | bemerkung |
|---|---|---|---|---|---|---|
| L-P08 | UP, T2 | siehe Tabelle UP | – | siehe Tabelle UP | siehe Tabelle UP | ausgewaehlt · A · Kern; Zeilen nur einmal geführt |
| **L-A03** | T2 | **Ordner `uebergreifend/L-A03_kapitel/`** (49 Kapitel-PDFs, 1876 PDF-Seiten) | – | – | – | ausgewaehlt · B · Kern · Druckseiten je Kapitel in U2 bestimmen |
| L-A03 | T2 | `00` Vorspann | PDF 1–37 | entfällt | entfällt | nicht zu extrahieren (Vorspann) · S. 1 ohne Text, ab S. 2 ✓ |
| L-A03 | T2 | `01-1` Structure and Function of Body Systems (Teil 1/2) | PDF 38–67 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Grundlagenphysiologie (L-A01); Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `01-2` Structure and Function of Body Systems (Teil 2/2) | PDF 68–103 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Grundlagenphysiologie (L-A01); Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `02-1` Biomechanics of Resistance Exercise (Teil 1/2) | PDF 104–147 | offen | offen | Text ✓ |
| L-A03 | T2 | `02-2` Biomechanics of Resistance Exercise (Teil 2/2) | PDF 148–173 | 2026-09-29 / opus | offen | Text ✓ · 53 Aussagen · 0 unsicher · 1 offene Stellen · pdf_nativ |
| L-A03 | T2 | `03` Bioenergetics of Exercise and Training | PDF 174–226 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Grundlagenphysiologie (L-A01); Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `04` Endocrine Responses to Resistance Exercise and Training | PDF 227–280 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Grundlagenphysiologie (L-A01); Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `05` Adaptations to Anaerobic Training | PDF 281–338 | offen | offen | Text ✓ |
| L-A03 | T2 | `06` Adaptations to Aerobic Training | PDF 339–383 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Grundlagenphysiologie (L-A01); Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `07` Age-Related Differences and Their Implications for Resistance Training | PDF 384–437 | offen | offen | Text ✓ |
| L-A03 | T2 | `08` Sex-Related Differences and Their Implications for Resistance Training | PDF 438–464 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Geschlecht; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `09-1` Psychological Foundations of Performance (Teil 1/2) | PDF 465–504 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Psychologie; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `09-2` Psychological Foundations of Performance (Teil 2/2) | PDF 505–535 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Psychologie; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `10-1` Basic Nutritional Factors Affecting Health (Teil 1/2) | PDF 536–574 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Ernährung; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `10-2` Basic Nutritional Factors Affecting Health (Teil 2/2) | PDF 575–612 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Ernährung; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `11` Nutrition Strategies for Maximizing Performance | PDF 613–658 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Ernährung; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `12-1` Performance-Enhancing Substances and Methods (Teil 1/2) | PDF 659–697 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Doping/Substanzen; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `12-2` Performance-Enhancing Substances and Methods (Teil 2/2) | PDF 698–728 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Doping/Substanzen; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `13` Principles of Test Selection and Administration | PDF 729–763 | offen | offen | Text ✓ |
| L-A03 | T2 | `14-1` Administration, Scoring, and Interpretation of Selected Tests (Teil 1/2) | PDF 764–808 | offen | offen | Text ✓ |
| L-A03 | T2 | `14-2` Administration, Scoring, and Interpretation of Selected Tests (Teil 2/2) | PDF 809–853 | offen | offen | Text ✓ |
| L-A03 | T2 | `15-1` Performance Preparation, Mobility, and Flexibility (Teil 1/3) | PDF 854–878 | 2026-09-29 / opus | offen | Text ✓ · 64 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-A03 | T2 | `15-2` Performance Preparation, Mobility, and Flexibility (Teil 2/3) | PDF 879–915 | offen | offen | Text ✓ |
| L-A03 | T2 | `15-3` Performance Preparation, Mobility, and Flexibility (Teil 3/3) | PDF 916–946 | 2026-09-29 / opus | offen | Text ✓ · 34 Aussagen · 1 unsicher · 3 offene Stellen · pdf_nativ |
| L-A03 | T2 | `16-1` Exercise Technique for Free Weight and Machine Training (Teil 1/4) | PDF 947–981 | 2026-09-29 / opus | offen | Text ✓ · 58 Aussagen · 0 unsicher · 3 offene Stellen · pdf_nativ |
| L-A03 | T2 | `16-2` Exercise Technique for Free Weight and Machine Training (Teil 2/4) | PDF 982–1016 | offen | offen | Text ✓ |
| L-A03 | T2 | `16-3` Exercise Technique for Free Weight and Machine Training (Teil 3/4) | PDF 1017–1052 | offen | offen | Text ✓ |
| L-A03 | T2 | `16-4` Exercise Technique for Free Weight and Machine Training (Teil 4/4) | PDF 1053–1087 | offen | offen | Text ✓ |
| L-A03 | T2 | `17-1` Exercise Technique for Alternative Modes and Nontraditional Implement Training (Teil 1/3) | PDF 1088–1118 | offen | offen | Text ✓ |
| L-A03 | T2 | `17-2` Exercise Technique for Alternative Modes and Nontraditional Implement Training (Teil 2/3) | PDF 1119–1152 | offen | offen | Text ✓ |
| L-A03 | T2 | `17-3` Exercise Technique for Alternative Modes and Nontraditional Implement Training (Teil 3/3) | PDF 1153–1185 | offen | offen | Text ✓ |
| L-A03 | T2 | `18-1` Program Design for Resistance Training (Teil 1/2) | PDF 1186–1220 | offen | offen | Text ✓ |
| L-A03 | T2 | `18-2` Program Design for Resistance Training (Teil 2/2) | PDF 1221–1259 | offen | offen | Text ✓ |
| L-A03 | T2 | `19-1` Program Design and Technique for Plyometric Training (Teil 1/4) | PDF 1260–1288 | offen | offen | Text ✓ |
| L-A03 | T2 | `19-2` Program Design and Technique for Plyometric Training (Teil 2/4) | PDF 1289–1328 | offen | offen | Text ✓ |
| L-A03 | T2 | `19-3` Program Design and Technique for Plyometric Training (Teil 3/4) | PDF 1329–1363 | offen | offen | Text ✓ |
| L-A03 | T2 | `19-4` Program Design and Technique for Plyometric Training (Teil 4/4) | PDF 1364–1397 | offen | offen | Text ✓ |
| L-A03 | T2 | `20-1` Program Design and Technique for Speed and Agility Training (Teil 1/3) | PDF 1398–1437 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Speed/Agility; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `20-2` Program Design and Technique for Speed and Agility Training (Teil 2/3) | PDF 1438–1472 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Speed/Agility; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `20-3` Program Design and Technique for Speed and Agility Training (Teil 3/3) | PDF 1473–1511 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Speed/Agility; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `21-1` Program Design and Technique for Aerobic Endurance and Metabolic Training (Teil 1/2) | PDF 1512–1542 | offen | offen | Text ✓ |
| L-A03 | T2 | `21-2` Program Design and Technique for Aerobic Endurance and Metabolic Training (Teil 2/2) | PDF 1543–1578 | offen | offen | Text ✓ |
| L-A03 | T2 | `22` Periodization | PDF 1579–1629 | offen | offen | Text ✓ |
| L-A03 | T2 | `23` Rehabilitation, Reconditioning, and Medical Issues | PDF 1630–1678 | offen | offen | Text ✓ |
| L-A03 | T2 | `24` Overreaching, Overtraining, and Recovery | PDF 1679–1726 | offen | offen | Text ✓ |
| L-A03 | T2 | `25` Facility Design, Layout, and Organization | PDF 1727–1774 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Anlagen; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `26` Facility Policies, Procedures, and Legal Issues | PDF 1775–1819 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Anlagen/Recht; Athlet 2026-09-29) · Text ✓ |
| L-A03 | T2 | `90` Answers to Study Questions | PDF 1820–1823 | entfällt | entfällt | nicht zu extrahieren (Anhang) · S. 1 ohne Text, ab S. 2 ✓; 2 von 4 Seiten fast ohne Text |
| L-A03 | T2 | `91` Index and Contributors | PDF 1824–1876 | entfällt | entfällt | nicht zu extrahieren (Anhang) · S. 1 ohne Text, ab S. 2 ✓ |
| **L-T2-03** | T2 | **Ordner `t2-kraft/L-T2-03_kapitel/`** (28 Kapitel-PDFs, 408 PDF-Seiten) | – | – | – | ausgewaehlt · B · Kern · Druckseiten je Kapitel in U2 bestimmen |
| L-T2-03 | T2 | `00` Vorspann | PDF 1–9 | entfällt | entfällt | nicht zu extrahieren (Vorspann) · Text ✓ |
| L-T2-03 | T2 | `01` A Brief Historical Overview on the Science of Concurrent Aerobic and Strength Training | PDF 10–15 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Geschichte; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `02` The Functional Genome in Physical Exercise | PDF 16–26 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: molekulare Grundlagen; Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-03 | T2 | `03` Molecular and Physiological Adaptations to Endurance Training | PDF 27–42 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Grundlagenphysiologie; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `04` Neural Adaptations to Endurance Training | PDF 43–58 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Grundlagenphysiologie; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `05` Physiological and Molecular Adaptations to Strength Training | PDF 59–81 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Grundlagenphysiologie; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `06` Neural Adaptations to Strength Training | PDF 82–93 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Grundlagenphysiologie; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `07` Proposed Mechanisms Underlying the Interference Effect | PDF 94–103 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-03 | T2 | `08` Molecular Adaptations to Concurrent Strength and Endurance Training | PDF 104–128 | offen | offen | Text ✓ |
| L-T2-03 | T2 | `09` Effects of Endurance-, Strength-, and Concurrent Training on Cytokines and Inflammation | PDF 129–142 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: molekulare Grundlagen; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `10` Immediate Effects of Endurance Exercise on Subsequent Strength Performance | PDF 143–158 | offen | offen | Text ✓ |
| L-T2-03 | T2 | `11` Acute Effects of Strength Exercise on Subsequent Endurance Performance | PDF 159–169 | offen | offen | Text ✓ |
| L-T2-03 | T2 | `12` Long-Term Effects of Supplementary Aerobic Training on Muscle Hypertrophy | PDF 170–183 | offen | offen | Text ✓ |
| L-T2-03 | T2 | `13` Methodological Considerations for Concurrent Training | PDF 184–198 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-03 | T2 | `14` Effects of the Concurrent Training Mode on Physiological Adaptations and Performance | PDF 199–213 | offen | offen | Text ✓ |
| L-T2-03 | T2 | `15` Recovery Strategies to Optimise Adaptations to Concurrent Aerobic and Strength Training | PDF 214–228 | offen | offen | Text ✓ |
| L-T2-03 | T2 | `16` Nutritional Considerations for Concurrent Training | PDF 229–252 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Ernährung; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `17` Concurrent Training in Children and Adolescents | PDF 253–274 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Kinder; Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-03 | T2 | `18` Concurrent Training in Elderly | PDF 275–289 | offen | offen | Text ✓ |
| L-T2-03 | T2 | `19` Concurrent Aerobic and Strength Training for Body Composition and Health | PDF 290–304 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Körperzusammensetzung; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `20` Sex Differences in Concurrent Aerobic and Strength Training | PDF 305–317 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Geschlecht; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `21` Long-Term Effects of Strength Training on Aerobic Capacity and Endurance Performance | PDF 318–325 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-03 | T2 | `22` Strength Training for Endurance Cyclists | PDF 326–333 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Radfahrer; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `23` Strength Training for Endurance Runners | PDF 334–348 | offen | offen | Text ✓ |
| L-T2-03 | T2 | `24` Strength Training for Cross-Country Skiers | PDF 349–360 | offen | offen | Text ✓ |
| L-T2-03 | T2 | `25` Strength Training for Swimmers | PDF 361–378 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Schwimmer; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `26` General Aspects of Concurrent Aerobic and Strength Training for Performance in Team Sports | PDF 379–388 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Mannschaftssport; Athlet 2026-09-29) · Text ✓ |
| L-T2-03 | T2 | `27` Concurrent Aerobic and Strength Training for Performance in Soccer | PDF 389–408 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Fußball; Athlet 2026-09-29) · Text ✓ |
| L-T2-11 | T2 | `L-T2-11_Ronnestad-2014_Strength-Training-Running-and-Cycling.pdf` | 10 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 60 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T2-12 | T2 | `L-T2-12_Blagrove-2018_Strength-Training-Distance-Running.pdf` | 33 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 78 Aussagen · 0 unsicher · 2 offene Stellen · pdf_nativ · Muster: Online-First-Fassung ohne Zeitschriften-Paginierung – Stelle als Abschnitt/Tabelle, seiten „–“ |
| **L-T2-04** | T2 | **Ordner `t2-kraft/L-T2-04_kapitel/`** (32 Kapitel-PDFs, 600 PDF-Seiten) | – | – | – | ausgewaehlt · C · Kern · Scan, fehlerhafte Texterkennung; Druckseite = PDF-Seite − 14; PDF 577/578 vertauscht |
| L-T2-04 | T2 | `00` Vorspann und Introduction | PDF 1–14 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ · Vorspann mit Einleitung – extrahieren (Entscheidung Athlet 2026-09-29) |
| L-T2-04 | T2 | `01` Principles of Bodyweight Training | PDF 15–23; Druck 1–9 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-04 | T2 | `02` Physiology of Strength and Hypertrophy | PDF 24–34; Druck 10–20 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Physiologie; Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `03` Progression Charts and Goal Setting | PDF 35–48; Druck 21–34 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `04` Structural Balance Considerations | PDF 49–57; Druck 35–43 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `05` Intro to Programming, Attributes, Hierarchy of a Routine | PDF 58–72; Druck 44–58 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung (Dosierung nur Kernset, D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `06` Population Considerations | PDF 73–82; Druck 59–68 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung (D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `07` Constructing Your Workout Routine | PDF 83–94; Druck 69–80 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung (D-29); Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-04 | T2 | `08` Warm-up and Skill Work | PDF 95–103; Druck 81–89 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `09` Strength Work | PDF 104–128; Druck 90–114 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Dosierung (D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `10` Methods of Progression | PDF 129–150; Druck 115–136 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `11` Prehabilitation, Isolation, Flexibility, Cool Down | PDF 151–164; Druck 137–150 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `12` Mesocycle Planning | PDF 165–182; Druck 151–168 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung/Dosierung/Lebensstil (D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `13` Endurance, Cardio, Cross Training, Hybrid Templates | PDF 183–201; Druck 169–187 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung/Dosierung/Lebensstil (D-29); Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-04 | T2 | `14` Overreaching and Overtraining | PDF 202–208; Druck 188–194 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung/Dosierung/Lebensstil (D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `15` Health and Injury Management | PDF 209–225; Druck 195–211 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung/Dosierung/Lebensstil (D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `16` Lifestyle Factors | PDF 226–232; Druck 212–218 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung/Dosierung/Lebensstil (D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `17` Untrained Beginner | PDF 233–244; Druck 219–230 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung/Dosierung/Lebensstil (D-29); Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-04 | T2 | `18` Trained Beginner | PDF 245–252; Druck 231–238 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung/Dosierung/Lebensstil (D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `19` Intermediate | PDF 253–264; Druck 239–250 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung/Dosierung/Lebensstil (D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `20` Advanced | PDF 265–274; Druck 251–260 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Programmierung/Dosierung/Lebensstil (D-29); Athlet 2026-09-29) · Text ✓ |
| L-T2-04 | T2 | `21` Common Bodyweight Training Injuries | PDF 275–305; Druck 261–291 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Verletzungen (Stufe C; Block R); Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T2-04 | T2 | `22` Prehabilitation, Mobility, Flexibility Resources | PDF 306–326; Druck 292–312 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `23` Exercise Technique, Descriptions, Tips | PDF 327–331; Druck 313–317 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `24-1` Handstand Variations (Teil 1/2) | PDF 332–361; Druck 318–347 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `24-2` Handstand Variations (Teil 2/2) | PDF 362–392; Druck 348–378 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `25-1` Pulling Exercises (Teil 1/2) | PDF 393–431; Druck 379–417 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `25-2` Pulling Exercises (Teil 2/2) | PDF 432–470; Druck 418–456 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `26-1` Pushing Variations (Teil 1/2) | PDF 471–503; Druck 457–489 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `26-2` Pushing Variations (Teil 2/2) | PDF 504–536; Druck 490–522 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `27` Multi-Plane Exercises, Core, and Legs | PDF 537–594; Druck 523–580 | offen | offen | Text ✓ |
| L-T2-04 | T2 | `90` Resources | PDF 595–600; Druck 581–586 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓; 3 von 6 Seiten fast ohne Text |
| L-T2-08 | T2 | `L-T2-08_Kotarsky-2018_Progressive-Push-up-Training.pdf` | 9 (PDF) | 2026-09-29 / opus | offen | verifiziert · A · Kern · Text ✓ · 46 Aussagen · 0 unsicher · 1 offene Stellen · pdf_nativ |
| L-T2-09 | T2 | `L-T2-09_vandenTillaar-2019_Push-up-vs-Bench-Press.pdf` | 8 (PDF) | 2026-09-29 / opus | offen | verifiziert · A · Kern · Text ✓ · 36 Aussagen · 4 unsicher · 6 offene Stellen · pdf_nativ · Muster: Seiten mit Präfix E (E74–E81) |
| L-T2-10 | T2 | `L-T2-10_Wiedenmann-2025_Resistance-Training-Modalities-Older-Adults.pdf` | 13 (PDF) | 2026-09-29 / opus | offen | verifiziert · A · Kern · Text ✓ · 31 Aussagen · 0 unsicher · 3 offene Stellen · pdf_nativ |
| L-T2-15 | T2, T4 | `L-T2-15_Warneke-2024_Stretching-or-Strengthening-Posture.pdf` | 13 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · **Lizenz vor Ablage prüfen (Athlet)** · 50 Aussagen · 1 unsicher · 7 offene Stellen · pdf_nativ |
| L-T2-16 | T2 | `L-T2-16_Khorramroo-2026_Corrective-Exercises-Posture.pdf` | 30 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · **Lizenz vor Ablage prüfen (Athlet)** · 77 Aussagen · 3 unsicher · 5 offene Stellen · pdf_nativ |
| L-T2-17 | T2 | `L-T2-17_Shiri-2018_Exercise-Prevention-Low-Back-Pain.pdf` | 9 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 50 Aussagen · 0 unsicher · 0 offene Stellen · pdf_nativ |
| L-T2-18 | T2 | `L-T2-18_Steffens-2016_Prevention-of-Low-Back-Pain.pdf` | 10 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 43 Aussagen · 2 unsicher · 7 offene Stellen · pdf_nativ |
| L-T2-20 | T2 | `L-T2-20_Pelland-2026_Resistance-Training-Dose-Response.pdf` | 25 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 73 Aussagen · 4 unsicher · 3 offene Stellen · pdf_nativ |
| L-T2-21 | T2 | `L-T2-21_Robinson-2024_Proximity-to-Failure-Dose-Response.pdf` | 23 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · 56 Aussagen · 6 unsicher · 3 offene Stellen · pdf_nativ |
| L-T2-22 | T2 | `L-T2-22_Refalo-2023_Proximity-to-Failure-Hypertrophy.pdf` | 17 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · **Lizenz vor Ablage prüfen (Athlet)** · 50 Aussagen · 4 unsicher · 4 offene Stellen · pdf_nativ · Muster: Versatz PDF n → S. 648+n; Befund: Egger-Test Wortlaut vs. p, KI Text vs. Tab. 4 |
| L-T2-23 | T2 | `L-T2-23_Lopez-2021_Training-Load-Hypertrophy-Strength.pdf` | 13 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · Corrigendum als eigene Datei (Zeile darunter) · **Lizenz vor Ablage prüfen (Athlet)** · 55 Aussagen · 5 unsicher · 8 offene Stellen · pdf_nativ · Muster: Repositoriumsfassung, PDF 1–2 Deckblätter, PDF n → S. 1203+n; Corrigendum S. 370 (Abb. 4) eingearbeitet und markiert – hoch vs. mittel schwächer (0,16–0,17, P 0,13–0,15); I², Bias, 98,2 % unklar ob neu berechnet (unsicher); mehrere Widersprüche im Original |
| L-T2-23 | T2 | `L-T2-23_Lopez-2022_Corrigendum.pdf` | 1 (PDF) | in k00 | in k00 | Corrigendum zu L-T2-23 · Text ✓ · im selben Lauf wie der Artikel extrahiert (`L-T2-23_k00.md`, Entscheidung Athlet 2026-09-29) |
| L-T2-24 | T2 | `L-T2-24_Lopes-2019_Elastic-vs-Conventional-Resistance.pdf` | 7 (PDF) | 2026-09-29 / opus | offen | ausgewaehlt · A · Kern · Text ✓ · Corrigendum als eigene Datei (Zeile darunter) · 40 Aussagen · 1 unsicher · 11 offene Stellen · pdf_nativ |
| L-T2-24 | T2 | `L-T2-24_Lopes-2020_Corrigendum.pdf` | 2 (PDF) | in k00 | in k00 | Corrigendum zu L-T2-24 · Text ✓ · im selben Lauf wie der Artikel extrahiert (`L-T2-24_k00.md`, Entscheidung Athlet 2026-09-29) |
| L-T2-14 | T2 | `L-T2-14_Cowley-2026_Advanced-Resistance-Training-Methods.pdf` | 23 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-T2-19 | T2 | `L-T2-19_Carrasco-Uribarren-2026_Therapeutic-Exercise-Forward-Head-Posture.pdf` | 13 (PDF) | 2026-09-29 / opus | offen | optional · A · optional · Text ✓ · 47 Aussagen · 2 unsicher · 3 offene Stellen · pdf_nativ · Muster: Online-First, Verlagsdeckblatt, gedruckt = PDF − 1, S. 1 ohne Zahl (Abschnitt); Befund: n=256 vs. 156, Unpräzision Text vs. Tab. 4, Tab. 1 vs. 2 widersprüchlich |
| L-T2-33 | T2 | – | – | – | – | optional · B · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T2-27 | T2 | `L-T2-27_Schoenfeld-2019_Training-Frequency-Hypertrophy.pdf` | 11 (PDF) | 2026-09-29 / opus | offen | optional · A · optional · Text ✓ · 41 Aussagen · 0 unsicher · 3 offene Stellen · pdf_nativ · Muster: Online-First-Fassung (online 2018), Verlagsdeckblatt, gedruckt = PDF − 1, S. 1 ohne Zahl (Abschnitt); Befund: Omnibustest P = 0,08 (Ergebnis) vs. 0,04 (Diskussion) |
| L-T2-28 | T2 | `L-T2-28_Refalo-2021_Training-Load-Hypertrophy.pdf` | 24 (PDF) | 2026-09-29 / opus | offen | optional · A · optional · Text ✓ · 67 Aussagen · 4 unsicher · 6 offene Stellen · pdf_nativ |
| L-T2-29 | T2 | `L-T2-29_Carvalho-2022_Volume-Matched-Loads-Hypertrophy.pdf` | 58 (PDF) | offen | offen | optional · A · optional · Text ✓ · Autorenmanuskript, Seitenzahlen nicht zitierfähig |
| L-T2-30 | T2 | `L-T2-30_Grgic-2022_Failure-vs-Non-Failure.pdf` | 10 (PDF) | 2026-09-29 / opus | offen | optional · A · optional · Text ✓ · 39 Aussagen · 0 unsicher · 7 offene Stellen · pdf_nativ · Muster: Article in Press (J Sport Health Sci 2021, vorläufige Seiten 1–10), Versatz 0; Befund: Jahresangabe Rooney 2020 vs. 1994, KI Karsten ohne Minus u. a. |
| **Synthese startbereit** | T2 | **nein** (W-10) | – | – | – | Fehlende Kernquellen: keine. Stand Extraktion: 22 von 76 extrahiert; gegengeprüft: 0. |

### 4.5 T3 – `t3-klettern`

Ablage: `docs/extraktion/t3-klettern/`. Zu extrahieren in dieser Tabelle: 63 Dateien. Davon extrahiert: 0. Nicht zu extrahieren (Vorspann/Anhang): 10; ausgelassen nach Kapitelauswahl: 10.

| L-ID | Zieldatei(en) | Datei/Kapitel | seiten | extrahiert (Datum/Modell) | geprüft (Datum/Modell/freigabe) | bemerkung |
|---|---|---|---|---|---|---|
| L-T3-01 | T3 | `L-T3-01_Stien-2023_Climbing-and-Resistance-Training-Meta-Analysis.pdf` | 13 (PDF) | offen | offen | ausgewaehlt (kern) · A · Kern · Text ✓ |
| L-T3-02 | T3 | `L-T3-02_Langer-2023_Strength-Training-in-Climbing.pdf` | 17 (PDF) | offen | offen | ausgewaehlt (kern) · A · Kern · Text ✓ |
| L-T3-03 | T3 | `L-T3-03_Langer-2023_Performance-Testing-in-Climbing.pdf` | 23 (PDF) | offen | offen | ausgewaehlt (kern) · A · Kern · Text ✓ |
| **L-T3-06** | T3 | **Ordner `t3-klettern/L-T3-06_kapitel/`** (24 Kapitel-PDFs, 319 PDF-Seiten) | – | – | – | ausgewaehlt (kern) · B · Kern · Druckseiten je Kapitel in U2 bestimmen |
| L-T3-06 | T3 | `00` Vorspann | PDF 1–11 | entfällt | entfällt | nicht zu extrahieren (Vorspann) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T3-06 | T3 | `01` Introduction | PDF 12–20 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `02` Injury Statistics | PDF 21–34 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T3-06 | T3 | `03` Anatomy and Biomechanics of the Hand | PDF 35–48 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `04` Historical Development of a Physiological Model for Rock Climbing Performance | PDF 49–60 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `05` Imaging of Climbing Injuries | PDF 61–71 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Bildgebung/Diagnostik (N5); Athlet 2026-09-29) · Text ✓ |
| L-T3-06 | T3 | `06` Hand and Fingers | PDF 72–120 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T3-06 | T3 | `07` Wrist Injuries | PDF 121–131 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `08` Elbow and Forearm | PDF 132–142 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `09` Shoulder Injuries | PDF 143–152 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `10` Foot and Ankle | PDF 153–165 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T3-06 | T3 | `11` Hip and Knee Injuries | PDF 166–173 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `12` The Spine | PDF 174–186 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T3-06 | T3 | `13` Long-Term Effects of Intensive Rock Climbing to the Hand and Fingers | PDF 187–200 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T3-06 | T3 | `14` Pediatric Aspects in Young Rock Climbers | PDF 201–206 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Kinder; Athlet 2026-09-29) · Text ✓ |
| L-T3-06 | T3 | `15` Climbing in Older Athletes | PDF 207–211 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `16` Anorexia Athletica and Relative Energy Deficiency | PDF 212–217 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Medizin (RED-S); Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T3-06 | T3 | `17` Sport Climbing with Pre-existing Medical Conditions | PDF 218–234 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Vorerkrankungen; Athlet 2026-09-29) · Text ✓ |
| L-T3-06 | T3 | `18` Sport Climbing During Pregnancy | PDF 235–243 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Schwangerschaft; Athlet 2026-09-29) · Text ✓ |
| L-T3-06 | T3 | `19` Sports-Medical Supervision of Competition Climbers and Climbing Competitions | PDF 244–252 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Wettkampfbetreuung; Athlet 2026-09-29) · Text ✓ |
| L-T3-06 | T3 | `20` Climbing Injury Rehabilitation | PDF 253–277 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T3-06 | T3 | `21` Injury Prevention | PDF 278–294 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `22` Taping | PDF 295–313 | offen | offen | Text ✓ |
| L-T3-06 | T3 | `23` Future Aspects: Climbing in the Olympics | PDF 314–319 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Olympia; Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| **L-T3-19** | T3 | **Ordner `t3-klettern/L-T3-19_kapitel/`** (15 Markdown-Dateien + Ansichts-PDFs) | – | – | – | ausgewaehlt (kern) · B · Kern · EPUB → Markdown, keine Seitenmarken (Kapitel/Abschnitt, D-71) |
| L-T3-19 | T3 | `00` Vorspann | EPUB, ca. 1.500 Wörter | entfällt | entfällt | nicht zu extrahieren (Vorspann) · Markdown ✓ |
| L-T3-19 | T3 | `01` The Process of Training | EPUB, ca. 2.000 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `02` Understanding the Importance of Strength | EPUB, ca. 3.200 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `03` Understanding and Optimising Mobility | EPUB, ca. 3.600 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `04` Brief Notes on Anatomy | EPUB, ca. 2.300 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `05` Fascia, Muscle Chains and Biotensegrity | EPUB, ca. 1.000 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `06` Bioenergetics and Metabolism | EPUB, ca. 1.300 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `07` Physiological Factors in Climbing Performance | EPUB, ca. 3.100 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `08-1` What Can I Optimise in My Training Sessions? (Teil 1/3) | EPUB, ca. 7.900 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `08-2` What Can I Optimise in My Training Sessions? (Teil 2/3) | EPUB, ca. 6.900 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `08-3` What Can I Optimise in My Training Sessions? (Teil 3/3) | EPUB, ca. 10.200 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `09` Training Session Design | EPUB, ca. 1.300 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `10` Periodisation Models | EPUB, ca. 3.900 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `11` Detraining | EPUB, ca. 700 Wörter | offen | offen | Markdown ✓ |
| L-T3-19 | T3 | `90` Bibliography | EPUB, ca. 5.500 Wörter | entfällt | entfällt | nicht zu extrahieren (Anhang) · Markdown ✓ |
| **L-T3-09** | T3 | **Ordner `t3-klettern/L-T3-09_kapitel/`** (17 Kapitel-PDFs, 356 PDF-Seiten) | – | – | – | ausgewaehlt · B · Kern · Scan; Druckseite = PDF-Seite − 16; 3. Aufl. (Neuauflage ab 03/2027 zusätzlich, D-70) |
| L-T3-09 | T3 | `00` Vorspann, Foreword und Introduction | PDF 1–16 | offen | offen | Text ✓ · Vorspann mit Einleitung – extrahieren (Entscheidung Athlet 2026-09-29) |
| L-T3-09 | T3 | `01` An Overview of Training for Climbing | PDF 17–34; Druck 1–18 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `02` Self-Assessment and Goal Setting | PDF 35–46; Druck 19–30 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `03` Mental Training | PDF 47–72; Druck 31–56 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `04` Training Technique and Skill | PDF 73–104; Druck 57–88 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `05` The Physiology of Climbing | PDF 105–132; Druck 89–116 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `06` Mobility, Stability, Antagonist Training | PDF 133–162; Druck 117–146 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `07` Core, Legs, and Aerobic Training | PDF 163–180; Druck 147–164 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `08` Finger Training for Strength and Endurance | PDF 181–214; Druck 165–198 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `09` Pull-Muscle and Power Training | PDF 215–234; Druck 199–218 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `10` Designing Your Training Program | PDF 235–262; Druck 219–246 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `11` Performance Nutrition | PDF 263–278; Druck 247–262 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Ernährung; Athlet 2026-09-29) · Text ✓ |
| L-T3-09 | T3 | `12` Accelerating Recovery | PDF 279–294; Druck 263–278 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `13` Injury Treatment and Prevention | PDF 295–320; Druck 279–304 | offen | offen | Text ✓ |
| L-T3-09 | T3 | `90` Afterword and Appendices A-C | PDF 321–330; Druck 305–314 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| L-T3-09 | T3 | `91` Glossary, Suggested Reading, References | PDF 331–342; Druck 315–326 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| L-T3-09 | T3 | `92` Index and About the Author | PDF 343–356; Druck 327–340 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| **L-T3-10** | T3 | **Ordner `t3-klettern/L-T3-10_kapitel/`** (11 Markdown-Dateien + Ansichts-PDFs) | – | – | – | ausgewaehlt (ideenfundus, Stufe C) · C · Kern · EPUB → Markdown, keine Seitenmarken (Kapitel/Abschnitt, D-71) |
| L-T3-10 | T3 | `00` Vorspann | EPUB, ca. 3.400 Wörter | entfällt | entfällt | nicht zu extrahieren (Vorspann) · Markdown ✓ |
| L-T3-10 | T3 | `01-1` Technique (Teil 1/2) | EPUB, ca. 8.100 Wörter | offen | offen | Markdown ✓ |
| L-T3-10 | T3 | `01-2` Technique (Teil 2/2) | EPUB, ca. 8.000 Wörter | offen | offen | Markdown ✓ |
| L-T3-10 | T3 | `02-1` Physical Training (Teil 1/2) | EPUB, ca. 8.100 Wörter | offen | offen | Markdown ✓ |
| L-T3-10 | T3 | `02-2` Physical Training (Teil 2/2) | EPUB, ca. 7.900 Wörter | offen | offen | Markdown ✓ |
| L-T3-10 | T3 | `03` Mental Training | EPUB, ca. 9.600 Wörter | offen | offen | Markdown ✓ |
| L-T3-10 | T3 | `04` Tactics | EPUB, ca. 11.800 Wörter | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Taktik (kein Themenfeld der Quelle); Athlet 2026-09-29) · Markdown ✓ |
| L-T3-10 | T3 | `05-1` General Training and Injury Prevention (Teil 1/2) | EPUB, ca. 6.100 Wörter | offen | offen | Markdown ✓ |
| L-T3-10 | T3 | `05-2` General Training and Injury Prevention (Teil 2/2) | EPUB, ca. 6.500 Wörter | offen | offen | Markdown ✓ |
| L-T3-10 | T3 | `06` Training Plans | EPUB, ca. 10.300 Wörter | offen | offen | Markdown ✓ |
| L-T3-10 | T3 | `90` The Joy of Climbing, Ten Commandments, Epilogue, Glossary, Read More | EPUB, ca. 6.300 Wörter | entfällt | entfällt | nicht zu extrahieren (Anhang) · Markdown ✓ |
| **L-T3-20** | T3 | **Ordner `t3-klettern/L-T3-20_kapitel/`** (5 Markdown-Dateien + Ansichts-PDFs) | – | – | – | ausgewaehlt (ideenfundus, Stufe C) · C · Kern · EPUB → Markdown mit Seitenmarken [S. n] |
| L-T3-20 | T3 | `00a` Vorspann | Druck 2–11 | entfällt | entfällt | nicht zu extrahieren (Vorspann) · Markdown ✓ |
| L-T3-20 | T3 | `00b` Warming Up | Druck 14–19 | offen | offen | Markdown ✓ |
| L-T3-20 | T3 | `01` Technique | Druck 20–87 | offen | offen | Markdown ✓ |
| L-T3-20 | T3 | `02` Strength & Power | Druck 88–142 | offen | offen | Markdown ✓ |
| L-T3-20 | T3 | `03` Children & Youths | Druck 143–192 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Kinder und Jugendliche; Athlet 2026-09-29) · Markdown ✓ |
| **L-T3-21** | T3 | **Ordner `t3-klettern/L-T3-21_kapitel/`** (6 Markdown-Dateien + Ansichts-PDFs) | – | – | – | ausgewaehlt (Stufe C, vorläufig – Bestätigung Athlet offen) · C · Kern · EPUB → Markdown mit Seitenmarken [S. n]; vorläufig (Bestätigung Athlet offen) |
| L-T3-21 | T3 | `00` Vorspann und Introduction | Druck 2–11 | offen | offen | Markdown ✓ · Vorspann mit Einleitung – extrahieren (Entscheidung Athlet 2026-09-29) |
| L-T3-21 | T3 | `01` Handling of Acute Soft Tissue Injuries and Overuse Injuries | Druck 12–37 | offen | offen | Markdown ✓ |
| L-T3-21 | T3 | `02-1` Injuries and Body Parts (Teil 1/2) | Druck 38–86 | offen | offen | Markdown ✓ |
| L-T3-21 | T3 | `02-2` Injuries and Body Parts (Teil 2/2) | Druck 87–141 | offen | offen | Markdown ✓ |
| L-T3-21 | T3 | `03` What Is Pain, Really? | Druck 142–151 | offen | offen | Markdown ✓ |
| L-T3-21 | T3 | `90` Glossary, References and Bibliography | Druck 152–157 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Markdown ✓ |
| L-T3-16 | T3 | – | – | – | – | ausgewaehlt (planungsvorlage, Stufe C) · C · Kern · **fehlt (Beschaffung, Athlet)** |
| L-T3-18 | T3 | `L-T3-18_Lopez-Rivera-2019_Hangboard-Training-Programs.pdf` | 11 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T3-05 | T3 | `L-T3-05_Lopez-Rivera-2012_Grip-Strength-Edge-Depth.pdf` | 12 (PDF) | offen | offen | ausgewaehlt (ergaenzend) · A · Kern · Text ✓ |
| L-T3-07 | T3 | – | – | entfällt | entfällt | optional (Alternative zu L-T3-06, D-31) · B · keine Datei; nicht benötigt, solange L-T3-06 vorliegt |
| **Synthese startbereit** | T3 | **nein** (W-10) | – | – | – | Fehlende Kernquellen: L-T3-16. Stand Extraktion: 0 von 63 extrahiert; gegengeprüft: 0. L-T3-16 ist Stufe C (Planungsvorlage), aber `ausgewaehlt` – nach W-10 zählt es als Kernquelle; bestätigen. L-T3-09 in der Neuauflage (ab 03/2027) nicht gezählt, die 3. Aufl. gilt (D-70). L-T3-21 vorläufig (Bestätigung Athlet offen). |

### 4.6 R – `r-reha-praevention`

Ablage: `docs/extraktion/r-reha/`. Zu extrahieren in dieser Tabelle: 28 Dateien. Davon extrahiert: 0. Nicht zu extrahieren (Vorspann/Anhang): 0; ausgelassen nach Kapitelauswahl: 0.

| L-ID | Zieldatei(en) | Datei/Kapitel | seiten | extrahiert (Datum/Modell) | geprüft (Datum/Modell/freigabe) | bemerkung |
|---|---|---|---|---|---|---|
| L-R-01 | R | `L-R-01_Breda-2021_Progressive-Tendon-Loading.pdf` | 9 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-02 | R | `L-R-02_Kongsgaard-2009_Patellar-Tendinopathy-HSR.pdf` | 13 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-03 | R | `L-R-03_Agergaard-2021_Heavy-vs-Moderate-Loads-Patellar-Tendinopathy.pdf` | 12 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-04 | R | `L-R-04_Agergaard-2026_TEREX-Extended-Restitution.pdf` | 12 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-05 | R | `L-R-05_Challoumas-2023_Lower-Limb-Tendinopathy-Living-Review.pdf` | 14 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-06 | R | `L-R-06_Liu-2026_Patellar-Tendinopathy-Network-Meta-Analysis.pdf` | 14 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-07 | R | `L-R-07_Visentini-1998_VISA-Score.pdf` | 7 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-08 | R | `L-R-08_Lohrer-2011_VISA-P-German.pdf` | 12 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-09 | R | `L-R-09_Hernandez-Sanchez-2014_VISA-P-Responsiveness.pdf` | 7 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-13 | R | `L-R-13_Martin-2021_Lateral-Ankle-Sprain-Guideline.pdf` | 80 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-14 | R | `L-R-14_Hupperets-2009_Home-Programme-Ankle-Sprain-Recurrence.pdf` | 6 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-15 | R | `L-R-15_Schiftan-2015_Proprioceptive-Training-Ankle-Sprain.pdf` | 7 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-16 | R | `L-R-16_Tang-2024_Balance-Training-Dosage-Ankle.pdf` | 20 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-17 | R | `L-R-17_Donovan-2016_Destabilization-Devices-Ankle.pdf` | 19 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-23 | R | `L-R-23_Lopes-2025_Exercise-for-Patellar-Tendinopathy-Cochrane.pdf` | 65 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-24 | R | `L-R-24_SchusterBrandtFrandsen-2025_High-Risk-Running-Sessions.pdf` | 8 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-25 | R | `L-R-25_Wagemans-2022_Rehabilitation-Reinjury-Ankle-Sprain.pdf` | 20 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-R-10 | R | `L-R-10_Clifford-2020_Isometric-Exercise-Patellar-Tendinopathy.pdf` | 19 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-11 | R, T4 | `L-R-11_Sprague-2018_Patellar-Tendinopathy-Risk-Factors.pdf` | 12 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-12 | R | `L-R-12_Backman-2011_Ankle-Dorsiflexion-Patellar-Tendinopathy.pdf` | 9 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-18 | R | `L-R-18_Nielsen-2014_Running-Distance-Progression-Injuries.pdf` | 25 (PDF) | offen | offen | optional · A · optional · Text ✓ · Autorenmanuskript, Seitenzahlen nicht zitierfähig |
| L-R-19 | R | `L-R-19_Kiers-2012_Unstable-Surface-Ankle-Proprioception.pdf` | 9 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-20 | R | `L-R-20_Fakontis-2023_Elastic-Bands-vs-Proprioceptive-Training.pdf` | 11 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-21 | R | `L-R-21_Giboin-2018_Slackline-Training.pdf` | 9 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-22 | R | `L-R-22_Delahunt-2018_ROAST-Consensus.pdf` | 7 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-26 | R | `L-R-26_Doherty-2017_Ankle-Sprain-Overview-of-Reviews.pdf` | 18 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-27 | R | `L-R-27_Deng-2025_Patellar-Tendinopathy-Long-Term-Prognosis.pdf` | 9 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-28 | R | `L-R-28_Hjortshoej-2025_BFR-vs-HSR-Patellar-Tendinopathy.pdf` | 12 (PDF) | offen | offen | optional · A · optional · Text ✓ |
| L-R-29 | R | – | – | – | – | optional · B · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-R-30 | R | – | – | – | – | optional · B · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| **Synthese startbereit** | R | **nein** (W-10) | – | – | – | Fehlende Kernquellen: keine. Stand Extraktion: 0 von 28 extrahiert; gegengeprüft: 0. |

### 4.7 T4 – `t4-beweglichkeit`

Ablage: `docs/extraktion/t4-beweglichkeit/` (Quellen anderer Blöcke unter deren Block, D-51). Zu extrahieren in dieser Tabelle: 35 Dateien; zusätzlich L-R-11 (Tabelle R), L-T2-15 (Tabelle T2). Davon extrahiert: 0. Nicht zu extrahieren (Vorspann/Anhang): 4; ausgelassen nach Kapitelauswahl: 5.

| L-ID | Zieldatei(en) | Datei/Kapitel | seiten | extrahiert (Datum/Modell) | geprüft (Datum/Modell/freigabe) | bemerkung |
|---|---|---|---|---|---|---|
| L-T4-01 | T4 | `L-T4-01_Warneke-2025_Delphi-Consensus-Stretching.pdf` | 14 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-02 | T4 | `L-T4-02_Konrad-2024_Chronic-Stretching-ROM-Meta-Analysis.pdf` | 9 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-03 | T4 | `L-T4-03_Oba-2026_Moderators-Chronic-Static-Stretching.pdf` | 24 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-04 | T4 | `L-T4-04_Arntz-2023_Static-Stretching-Strength-and-Power.pdf` | 23 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-05 | T4 | `L-T4-05_Thomas-2018_Stretching-Typology-and-Duration.pdf` | 12 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-06 | T4 | `L-T4-06_Behm-2016_Acute-Effects-of-Stretching.pdf` | 11 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-08 | T4 | `L-T4-08_Warneke-2024_Foam-Rolling-Stretching-Warm-up.pdf` | 12 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-10 | T4 | `L-T4-10_Alizadeh-2023_Resistance-Training-ROM.pdf` | 16 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-12 | T4 | `L-T4-12_Konrad-2024_Stretching-vs-Foam-Rolling-ROM.pdf` | 16 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-14 | T4 | `L-T4-14_Lauersen-2014_Exercise-Interventions-Injury-Prevention.pdf` | 10 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-16 | T4 | `L-T4-16_Herbert-2011_Stretching-Muscle-Soreness-Cochrane.pdf` | 50 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| L-T4-17 | T4 | – | – | – | – | ausgewaehlt · A · Kern · **fehlt (Beschaffung, Athlet)** |
| L-T4-19 | T4 | `L-T4-19_Winters-2004_Passive-vs-Active-Hip-Flexor-Stretching.pdf` | 8 (PDF) | offen | offen | ausgewaehlt · A · Kern · Text ✓ |
| **L-T4-32** | T4 | **Ordner `t4-beweglichkeit/L-T4-32_kapitel/`** (19 Kapitel-PDFs, 281 PDF-Seiten) | – | – | – | ausgewaehlt · B · Kern · Druckseite = PDF-Seite − 15 |
| L-T4-32 | T4 | `00` Vorspann | PDF 1–15 | entfällt | entfällt | nicht zu extrahieren (Vorspann) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T4-32 | T4 | `01` My Personal Motivation for Stretching | PDF 16–23; Druck 1–8 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: persönliche Motivation; Athlet 2026-09-29) · S. 1 ohne Text, ab S. 2 ✓ |
| L-T4-32 | T4 | `02` History of Stretching | PDF 24–32; Druck 9–17 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Geschichte; Athlet 2026-09-29) · Text ✓ |
| L-T4-32 | T4 | `03` Types of Stretching and the Effects on Flexibility | PDF 33–67; Druck 18–52 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `04` Mechanisms Underlying Acute Changes in Range of Motion | PDF 68–97; Druck 53–82 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `05` Stretch Training-Related ROM Changes and Mechanisms | PDF 98–103; Druck 83–88 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `06` Global Effects of Stretching | PDF 104–113; Druck 89–98 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `07` Recommendations for Stretching Prescription | PDF 114–127; Druck 99–112 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `08` Stretching Effects on Injury Reduction and Health | PDF 128–143; Druck 113–128 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `09` Does Stretching Affect Performance | PDF 144–175; Druck 129–160 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `10` Effect of Stretch Training on Functional Performance | PDF 176–181; Druck 161–166 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `11` Effects of Stretch Training on Muscle Strength and Hypertrophy | PDF 182–189; Druck 167–174 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `12` Effects of Resistance Training on Range of Motion | PDF 190–205; Druck 175–190 | offen | offen | S. 1 ohne Text, ab S. 2 ✓ |
| L-T4-32 | T4 | `13` Foam Rolling Effects on Range of Motion and Performance | PDF 206–226; Druck 191–211 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `14` Local Vibration Effects on Range of Motion and Performance | PDF 227–231; Druck 212–216 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Vibration (außerhalb D-79); Athlet 2026-09-29) · Text ✓ |
| L-T4-32 | T4 | `15` Instrument-Assisted Soft Tissue Mobilization | PDF 232–240; Druck 217–225 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: IASTM (außerhalb D-79); Athlet 2026-09-29) · Text ✓ |
| L-T4-32 | T4 | `16` Flossing Effects on Range of Motion and Performance | PDF 241–246; Druck 226–231 | entfällt | entfällt | nicht extrahiert (außerhalb Zweck: Flossing (außerhalb D-79); Athlet 2026-09-29) · Text ✓ |
| L-T4-32 | T4 | `17` Stretching Exercise Illustration | PDF 247–270; Druck 232–255 | offen | offen | Text ✓ |
| L-T4-32 | T4 | `90` Index | PDF 271–281; Druck 256–266 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓ |
| **L-T4-34** | T4 | **Ordner `t4-beweglichkeit/L-T4-34_kapitel/`** (13 Kapitel-PDFs, 265 PDF-Seiten) | – | – | – | ausgewaehlt · C · Kern · Druckseite = PDF-Seite − 11; zusätzlich als EPUB |
| L-T4-34 | T4 | `00` Vorspann und Preface | PDF 1–11 | entfällt | entfällt | nicht zu extrahieren (Vorspann) · Text ✓ |
| L-T4-34 | T4 | `01` Stretching Fundamentals | PDF 12–19; Druck 1–8 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `02` Feet and Calves | PDF 20–47; Druck 9–36 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `03` Knees and Thighs | PDF 48–69; Druck 37–58 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `04` Hips | PDF 70–91; Druck 59–80 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `05` Lower Trunk | PDF 92–117; Druck 81–106 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `06` Arms, Wrists, and Hands | PDF 118–151; Druck 107–140 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `07` Shoulders, Back, and Chest | PDF 152–183; Druck 141–172 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `08` Neck | PDF 184–195; Druck 173–184 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `09` Dynamic Stretches | PDF 196–219; Druck 185–208 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `10` Programs for Daily Mobility and Flexibility | PDF 220–229; Druck 209–218 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `11` Sport-Specific Stretching Programs | PDF 230–257; Druck 219–246 | offen | offen | Text ✓ |
| L-T4-34 | T4 | `90` Stretch Finder and About the Authors | PDF 258–265; Druck 247–254 | entfällt | entfällt | nicht zu extrahieren (Anhang) · Text ✓; 3 von 8 Seiten fast ohne Text |
| L-T4-24 → L-R-11 | T4 | siehe L-R-11 | – | siehe Tabelle R | siehe Tabelle R | Verweis (Kern); optional in R, im T4-Kern als Verweis |
| L-T4-33 → L-T2-15 | T4 | siehe L-T2-15 | – | siehe Tabelle T2 | siehe Tabelle T2 | Verweis (Kern); Kern |
| L-T4-07 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-09 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-11 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-13 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-15 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-18 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-20 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-21 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-23 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-25 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-26 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-27 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-28 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-29 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-30 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-31 | T4 | – | – | – | – | optional · A · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-35 | T4 | – | – | – | – | optional · B · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| L-T4-36 | T4 | – | – | – | – | optional · C · optional · **fehlt (Beschaffung, Athlet)** · blockiert die Synthese nicht (W-10) |
| **Synthese startbereit** | T4 | **nein** (W-10) | – | – | – | Fehlende Kernquellen: L-T4-17. Stand Extraktion: 0 von 35 extrahiert; gegengeprüft: 0. |

## 5. Lizenz vor Ablage prüfen (Athlet)

Aus AP-06 Punkt 4 (offene Punkte). Die Code-Instanz entscheidet nicht; die Angabe stammt aus `docs/literatur/README.md` (Lizenz laut Volltext).

| L-ID | Zieldatei | Lizenz laut Volltext | Vermerk |
|---|---|---|---|
| L-P15 | UB | CC BY 4.0 | Lizenz vor Ablage prüfen (Athlet) |
| L-T2-15 | T2, T4 | CC BY 4.0 | Lizenz vor Ablage prüfen (Athlet) |
| L-T2-16 | T2 | CC BY 4.0 | Lizenz vor Ablage prüfen (Athlet) |
| L-T2-22 | T2 | CC BY 4.0 | Lizenz vor Ablage prüfen (Athlet) |
| L-T2-23 | T2 | CC BY-NC-ND 4.0 | Lizenz vor Ablage prüfen (Athlet) |
| L-T2-25 | UP | CC BY 4.0 | Lizenz vor Ablage prüfen (Athlet) |

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

| L-P03 | Seiten S2-161–S2-170 (Supplement-Paginierung), Versatz PDF n → S2-(160+n); Tab. 1 im PDF gedreht | U2-Extraktion (Rückmeldung Unteragent) |
| L-P04 | PDF ist Ahead-of-Print-Fassung, Seiten 1–4 der Vorabpaginierung (nicht Heftpaginierung), Versatz 0 | U2-Extraktion (Rückmeldung Unteragent) |
| L-P10 | Versatz PDF n → S. 108+n; Befund Extraktion: Tab. 5 Lastwerte teils ≠ RPE × Dauer (Druckfehler im Original?), Tab. 4 SD auffällig – Gegenprüfung beachten | U2-Extraktion (Rückmeldung Unteragent) |
| L-P05 | Versatz PDF n → S. 239+n | U2-Extraktion (Rückmeldung Unteragent) |
| L-P12 | Versatz PDF n → S. 906+n; Befund: Gruppengrößen Text vs. Tab. 1 widersprüchlich (im Original), unter Offene Stellen | U2-Extraktion (Rückmeldung Unteragent) |
| L-P13 | Versatz PDF n → S. 896+n; Befund: Widersprüche Text/Tabellen im Original (Baseline VISA-A-S 57/58, Tab. 7 Signifikanz, fehlende Einheiten), unter Offene Stellen | U2-Extraktion (Rückmeldung Unteragent) |
| L-P11 | Zeitschriftenseiten 281–291 nur als Bereich in der Fußzeile, Einzelseiten „n of 13“; Stelle als „S. n von 13, Abschnitt …“; Online-Tab. S1 nicht im PDF | U2-Extraktion (Rückmeldung Unteragent) |
| L-P15 | Artikelnummer 10299, Seiten „n of 22“, Versatz 0; Befund: Text vs. Tab. 2 widersprüchlich (Referenzfenster, Stabilisierung), unter Offene Stellen | U2-Extraktion (Rückmeldung Unteragent) |
| L-P16 | Versatz PDF n → S. 1179+n; Befund: mehrere Inkonsistenzen im Original (Abstract vertauscht g-Werte, N 198 vs. 228, Tab. 2), unter Offene Stellen | U2-Extraktion (Rückmeldung Unteragent) |
| L-P06 | Versatz PDF n → S. 185+n; Abb. 3 nur als Bild (gerendert gelesen); pdftotext liest in Tab. 1 „95%“ statt „>5%“ | U2-Extraktion (Rückmeldung Unteragent) |
| L-A01 | Druckseite = Gesamtbuch-PDF-Seite − 1 in allen 18 Kapiteln (Fußzeilen-Paginierung des E-Books; Übereinstimmung mit Druckausgabe nicht prüfbar); Kapiteldateien enthalten Teil-Einleitungen und Bildseiten am Rand, Literaturverzeichnis fehlt je Kapitel; viele Werte aus Grafiken abgelesen (unsicher); zahlreiche Widersprüche Text/Abbildung/Zusammenfassung im Buch selbst unter Offene Stellen | U2-Extraktion (Rückmeldung Unteragent) |
| L-P01 | Versatz PDF n → S. 752+n; Meinungsbeitrag ohne Studiendaten; Verweisnummern im Original teils falsch | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-25 | Versatz PDF n → S. 2390+n; Befund: Referenznummern in Forest-Plots um eins verschoben, Fig. 3 Einzelwerte vertauscht, QM/p in Fig. 2 inkonsistent; Supplement fehlt | U2-Extraktion (Rückmeldung Unteragent) |
| L-P07 | Versatz PDF n → S. 600+n; Forest-Plots als Bild (hochaufgelöst gelesen); Befund: SMD Hypertrophie −0,01 (Text) vs. +0,01 (Abb. 4) u. a., in den Aussagen vermerkt; Supplement fehlt | U2-Extraktion (Rückmeldung Unteragent) |
| L-P08 | Versatz PDF n → S. 850+n; Befund: Power-Volumen ≤24 vs. <24, Hypertrophie-Volumen „pro Muskelgruppe“ nur im Text; Ergänzungsanhänge fehlen | U2-Extraktion (Rückmeldung Unteragent) |
| L-P09 | Versatz PDF n → S. 1488+n; Befund: Quelle inkonsistent (p-Werte Abstract vs. Ergebnisse, Vorzeichen Text vs. Abb. 5, Referenznummern in Abb. 2–4) – Gegenprüfung mit vollem Umfang | U2-Extraktion (Rückmeldung Unteragent) |
| L-P02 | Versatz PDF n → S. 537+n; Teamsport-Abschnitte ausgelassen; Proteinverteilung 3–5 h vs. 3–4 h im Original | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-26 | Versatz PDF n → S. 687+n; Befund: Forest-Plots 7/8/9/11/13 falsch beschriftet (nach Text extrahiert, unsicher); Abstract vs. Ergebnisse (p = 0.15); Studienzahl 22 vs. 25 | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-31 | Versatz PDF n → S. 2292+n; Befund: Druckfehler in KI (Tab. 1, 3), Vorzeichen Korrelation Abstract vs. Ergebnis, Summe Effektstärken 330 vs. 422 | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-32 | PDF-Seite 1 = Verlagsdeckblatt, gedruckt = PDF − 1 (Online-Paginierung 1–12); Befund: KI/p-Werte im Original inkonsistent (Rad-HIIT, Pause > 24 h) | U2-Extraktion (Rückmeldung Unteragent) |
| L-A02 | Versatz Gesamtbuch-PDF → Druckseite je Kapitel verschieden (k01 −17, k02 −16, k03 −15, k04 −14, k06 −12, k07 −11, k09 −9, k12 −7), innerhalb der Kapiteldatei konstant; Kapiteldatei-Seite 1 = erste Druckseite des Kapitels; Kapitelteile beginnen/enden mitten im Abschnitt; Literaturverzeichnis je Kapitel enthalten; Tab. 4.9–4.11 nur als Bild | U2-Extraktion (Rückmeldung Unteragent) |
| L-T1-06 | Artikel 737249, Seiten 1–7, Versatz 0; Befund: Korrelationsrichtung Rennzeit–VO2max Abstract vs. Text widersprüchlich; Populationen teils aus Literaturliste (markiert) | U2-Extraktion (Rückmeldung Unteragent) |
| L-T1-03 | Versatz PDF n → S. 819+n; Tab. 3/4 gedreht (mit pdftotext abgeglichen); Grafikwerte Abb. 2 unsicher | U2-Extraktion (Rückmeldung Unteragent) |
| L-T1-12 | Versatz PDF n → S. 34+n; Laktatanstieg 75–90 % (Text) vs. 75–85 % (Abb. 4) im Original | U2-Extraktion (Rückmeldung Unteragent) |
| L-T1-04 | Artikel 46, Seiten „Page n of 18“, Versatz 0; Befund: Medaillensummen Tab. 1, 6- vs. 7-Zonen-Skala, Fußnoten b/c in Tab. 3 vertauscht | U2-Extraktion (Rückmeldung Unteragent) |
| L-T1-02 | Versatz PDF n → S. 275+n; Tab. 2 „?%“ im Original, Z1-Verteilung ergibt 101 % | U2-Extraktion (Rückmeldung Unteragent) |
| L-T1-05 | Online-First-Fassung ohne Seitenzahlen – Stelle als Abschnitt/Tabelle/Abbildung, seiten „–“; Befund: Studienzuordnung Tab. 2 (Padulo vs. Lussiana), Cr-Formel-Einheit, Tibialis-Richtung Text vs. Tab. 3 | U2-Extraktion (Rückmeldung Unteragent) |
| L-T1-07 | Druckseite = Gesamtbuch-PDF-Seite − 7 in allen Kapiteln; Kapiteldateien enden teils mit Leerseite („intentionally left blank“); Literaturverzeichnis nicht in den Kapiteldateien; HIIT-Typen und Abkürzungen (VIFT, APR …) nur in einzelnen Kapiteln definiert; Text vs. Abbildung bei Intervallwerten mehrfach abweichend (k04, k10) | U2-Extraktion (Rückmeldung Unteragent) |
| L-T1-08 | Scan; Druckseite = Gesamtbuch-PDF − 2, im Bereich PDF 88–152 − 4 (bestätigt); Druckseiten 149–150 fehlen im Scan (Athletengeschichte k04 bricht ab); viele Seiten ohne gedruckte Zahl (Kapitelanfang, Fotos) – aus Nachbarseiten erschlossen; Korrektur k08 (abgeleiteter Wert entfernt), k04 (typ erfahrung → praxis) | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-12 | Online-First-Fassung ohne Zeitschriften-Paginierung – Stelle als Abschnitt/Tabelle, seiten „–“ | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-09 | Seiten mit Präfix E (E74–E81) | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-22 | Versatz PDF n → S. 648+n; Befund: Egger-Test Wortlaut vs. p, KI Text vs. Tab. 4 | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-27 | Online-First-Fassung (online 2018), Verlagsdeckblatt, gedruckt = PDF − 1, S. 1 ohne Zahl (Abschnitt); Befund: Omnibustest P = 0,08 (Ergebnis) vs. 0,04 (Diskussion) | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-19 | Online-First, Verlagsdeckblatt, gedruckt = PDF − 1, S. 1 ohne Zahl (Abschnitt); Befund: n=256 vs. 156, Unpräzision Text vs. Tab. 4, Tab. 1 vs. 2 widersprüchlich | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-23 | Repositoriumsfassung, PDF 1–2 Deckblätter, PDF n → S. 1203+n; Corrigendum S. 370 (Abb. 4) eingearbeitet und markiert – hoch vs. mittel schwächer (0,16–0,17, P 0,13–0,15); I², Bias, 98,2 % unklar ob neu berechnet (unsicher); mehrere Widersprüche im Original | U2-Extraktion (Rückmeldung Unteragent) |
| L-T2-30 | Article in Press (J Sport Health Sci 2021, vorläufige Seiten 1–10), Versatz 0; Befund: Jahresangabe Rooney 2020 vs. 1994, KI Karsten ohne Minus u. a. | U2-Extraktion (Rückmeldung Unteragent) |
