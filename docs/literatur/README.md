---
titel: Literaturverzeichnis (Volltexte)
bezug: docs/konzept/konzept-ki-personal-trainer.md, Abschnitte 13.1, 13.2, 13.4; D-31, D-51, D-71, D-79
stand: 2026-09-29
---

# Literatur-Volltexte

Ablage der Volltexte, laut D-31 nur in einem privaten Repo. Das Repo ist vorübergehend öffentlich und wird wieder privat, sobald die Recherche keinen Zugriff mehr braucht (Konzept Q-21). **Nie ins Projektwissen hochladen.** Dort liegen nur die Wissenskarten aus `docs/wissen/` (D-12, 13.1).
Maßgeblich für Auswahl, Status und Zitierfassung ist Konzept 13.2. Diese Datei zeigt nur, welche Datei zu welcher ID gehört.

## Ablage und Dateinamen (D-51, D-71)

- Unterordner je Block: `uebergreifend/`, `t1-ausdauer/`, `t2-kraft/`, `t3-klettern/`, `r-reha/` (Block R), `t4-beweglichkeit/` (D-79). Eine Datei liegt in dem Block, in dem ihre ID definiert ist; L-P08 liegt also unter `uebergreifend/`, obwohl T2 per L-T2-01 darauf verweist.
- Dateiname: `<ID>_<Erstautor>-<Jahr>_<Kurztitel>[_<Auflage>].pdf`, nur ASCII (ø → oe, ö → oe). Das Jahr ist das Jahr der Zitierfassung im Konzept.
- Bücher zusätzlich als Kapitel-PDFs in `<ID>_kapitel/` (13.1 Schritt 1): `<ID>_<Kapitelnr>[-<Teil>]_<Kapiteltitel>.pdf`. `00` ist der Vorspann (Titelei, Inhaltsverzeichnis), `9x` sind Anhänge (Glossar, Literatur, Index). Kapitel mit mehr als 60 PDF-Seiten sind in etwa gleich große Teile (`-1`, `-2`, …) geteilt, möglichst an Abschnittsgrenzen. Teil-Titelseiten gehören zum folgenden Kapitel.
- Das Originalbuch bleibt vollständig liegen, zum Durchsuchen und Zitieren über das ganze Werk. Die Kapitel-PDFs haben die Lesezeichen des Kapitels, aber keine internen Verweise (Inhaltsverzeichnis- und Index-Links); die bleiben im Original.
- Seitenangaben in Wissenskarten beziehen sich auf die **gedruckte Seitenzahl** des Werks, nicht auf die PDF-Seite. Bei den beiden Scans steht der Versatz unten.
- EPUB (D-71): Dateiname wie PDF mit Endung `.epub`; nur ohne DRM oder mit Wasserzeichen. Kapitel als Markdown in `<ID>_kapitel/`: `<ID>_<Kapitelnr>[-<Teil>]_<Kapiteltitel>.md`; `00` Vorspann (bei L-T3-20 `00a` Vorspann und `00b` Aufwärmen), `9x` Anhänge; Kapitel über ca. 12 000 Wörter an Abschnittsgrenzen geteilt. Jede Markdown-Datei hat einen Kopf mit `id`, `kapitel`, `titel`, `teil`, `quelle_datei`, `quelle_xhtml`, `seitenbezug`; Abbildungen stehen als „[Abbildung: …]“.
- Zu jeder Kapiteldatei gibt es ein **Ansichts-PDF** gleichen Namens mit Abbildungen, aus dem EPUB gerendert. Es dient nur zum Betrachten; seine Seitenzahlen werden nie zitiert.
- Seitenangaben bei EPUB: Druckseite nur, wenn das EPUB Seitenmarken der Druckausgabe hat; sie stehen im Markdown als „[S. n]“ am Seitenwechsel (L-T3-20, L-T3-21). Sonst Kapitel und Abschnittsüberschrift (L-T3-10, L-T3-19; V-17).
- Neue Dateien: nach diesem Schema benennen, hier eintragen, im Konzept beim Eintrag `datei:` ergänzen und in 13.4 als vorhanden markieren.

## Bestand

### Übergreifend (13.2.1)

| ID | Stufe | Quelle | Datei | Seiten | Hinweis |
|---|---|---|---|---|---|
| L-A01 | B | Kenney, Wilmore, Costill – Physiology of Sport and Exercise, **7. Aufl. 2019** | [`L-A01_Kenney-2019_Physiology-of-Sport-and-Exercise_7ed.pdf`](uebergreifend/L-A01_Kenney-2019_Physiology-of-Sport-and-Exercise_7ed.pdf) | 1379 | Ausgabe gilt (Nachtrag D-51, 2026-09-29). E-Book, Kapitel-PDFs in `L-A01_kapitel/` |
| L-A02 | B | Ferrauti, Wiewelhove (Hrsg.) – Trainingswissenschaft für die Sportpraxis, 2. Aufl. 2025 | [`L-A02_Ferrauti-2025_Trainingswissenschaft-fuer-die-Sportpraxis_2ed.pdf`](uebergreifend/L-A02_Ferrauti-2025_Trainingswissenschaft-fuer-die-Sportpraxis_2ed.pdf) | 911 | Geliefert als 355-MB-PDF mit mehrfach eingebetteten Schriften und Bildern; hier ohne Dubletten (94 MB), Text und Bilder aller Seiten gleich dem Original. Kapitel-PDFs in `L-A02_kapitel/` |
| L-A03 | B | NSCA (Hrsg.) – Essentials of Strength Training and Conditioning, 5. Aufl. | [`L-A03_NSCA-2026_Essentials-of-Strength-Training-and-Conditioning_5ed.pdf`](uebergreifend/L-A03_NSCA-2026_Essentials-of-Strength-Training-and-Conditioning_5ed.pdf) | 1876 | E-Book, Kapitel-PDFs in `L-A03_kapitel/` |
| L-P01 | A | Kiely 2018 – Periodization Theory | [`L-P01_Kiely-2018_Periodization-Theory.pdf`](uebergreifend/L-P01_Kiely-2018_Periodization-Theory.pdf) | 12 | Open Access |
| L-P02 | A | Mujika et al. 2018 – Integrated Approach to Periodization | [`L-P02_Mujika-2018_Integrated-Approach-to-Periodization.pdf`](uebergreifend/L-P02_Mujika-2018_Integrated-Approach-to-Periodization.pdf) | 24 |  |
| L-P03 | A | Bourdon et al. 2017 – Monitoring Training Loads (Consensus) | [`L-P03_Bourdon-2017_Monitoring-Training-Loads-Consensus.pdf`](uebergreifend/L-P03_Bourdon-2017_Monitoring-Training-Loads-Consensus.pdf) | 10 |  |
| L-P04 | A | Impellizzeri et al. 2019 – Internal and External Training Load | [`L-P04_Impellizzeri-2019_Internal-and-External-Training-Load.pdf`](uebergreifend/L-P04_Impellizzeri-2019_Internal-and-External-Training-Load.pdf) | 4 |  |
| L-P05 | A | Kellmann et al. 2018 – Recovery and Performance (Consensus) | [`L-P05_Kellmann-2018_Recovery-and-Performance-Consensus.pdf`](uebergreifend/L-P05_Kellmann-2018_Recovery-and-Performance-Consensus.pdf) | 6 |  |
| L-P06 | A | Meeusen et al. 2013 – Overtraining Syndrome (Consensus, MSSE) | [`L-P06_Meeusen-2013_Overtraining-Syndrome-Consensus.pdf`](uebergreifend/L-P06_Meeusen-2013_Overtraining-Syndrome-Consensus.pdf) | 20 |  |
| L-P07 | A | Schumann et al. 2022 – Concurrent Training (Meta-Analyse) | [`L-P07_Schumann-2022_Concurrent-Training-Meta-Analysis.pdf`](uebergreifend/L-P07_Schumann-2022_Concurrent-Training-Meta-Analysis.pdf) | 12 | Open Access |
| L-P08 | A | Currier et al. 2026 – ACSM Position Stand Resistance Training | [`L-P08_Currier-2026_ACSM-Resistance-Training-Prescription.pdf`](uebergreifend/L-P08_Currier-2026_ACSM-Resistance-Training-Prescription.pdf) | 22 | CC BY-NC-ND 4.0 |
| L-P09 | A | Held et al. 2026 – Concurrent Training (Umbrella-Review) | [`L-P09_Held-2026_Concurrent-Training-Umbrella-Review.pdf`](uebergreifend/L-P09_Held-2026_Concurrent-Training-Umbrella-Review.pdf) | 24 |  |
| L-P10 | A | Foster et al. 2001 – A New Approach to Monitoring Exercise Training (sRPE) | [`L-P10_Foster-2001_Monitoring-Exercise-Training-sRPE.pdf`](uebergreifend/L-P10_Foster-2001_Monitoring-Exercise-Training-sRPE.pdf) | 7 |  |
| L-P11 | A | Saw et al. 2016 – Monitoring the Athlete Training Response | [`L-P11_Saw-2016_Monitoring-Athlete-Training-Response.pdf`](uebergreifend/L-P11_Saw-2016_Monitoring-Athlete-Training-Response.pdf) | 14 | CC BY-NC 4.0 |
| L-P12 | A | Impellizzeri et al. 2020 – Acute:Chronic Workload Ratio, Conceptual Issues | [`L-P12_Impellizzeri-2020_ACWR-Conceptual-Issues.pdf`](uebergreifend/L-P12_Impellizzeri-2020_ACWR-Conceptual-Issues.pdf) | 7 |  |
| L-P13 | A | Silbernagel et al. 2007 – Pain-Monitoring Model, Achilles Tendinopathy (RCT) | [`L-P13_Silbernagel-2007_Pain-Monitoring-Model-Achilles.pdf`](uebergreifend/L-P13_Silbernagel-2007_Pain-Monitoring-Model-Achilles.pdf) | 10 | Grundlage Schmerzregeln (V-07, Q-13) |
| L-P14 | A | Impellizzeri et al. 2021 – What Role Do Chronic Workloads Play in the Acute to Chronic Workload Ratio? Time to Dismiss ACW… (optional) | [`L-P14_Impellizzeri-2021_Chronic-Workloads-ACWR.pdf`](uebergreifend/L-P14_Impellizzeri-2021_Chronic-Workloads-ACWR.pdf) | 12 |  |
| L-P15 | A | Manresa-Rocamora et al. 2021 – HRV-Guided Training (Meta-Analyse) | [`L-P15_Manresa-Rocamora-2021_HRV-Guided-Training-Meta-Analysis.pdf`](uebergreifend/L-P15_Manresa-Rocamora-2021_HRV-Guided-Training-Meta-Analysis.pdf) | 22 | CC BY 4.0 |
| L-P16 | A | Düking et al. 2021 – Monitoring and adapting endurance training on the basis of heart rate variability monitored by … (optional) | [`L-P16_Dueking-2021_HRV-Guided-Training-Wearables.pdf`](uebergreifend/L-P16_Dueking-2021_HRV-Guided-Training-Wearables.pdf) | 13 | Verlagsfassung |

### T1 Ausdauer (13.2.2)

| ID | Stufe | Quelle | Datei | Seiten | Hinweis |
|---|---|---|---|---|---|
| L-T1-02 | A | Seiler 2010 – Intensity and Duration Distribution | [`L-T1-02_Seiler-2010_Intensity-and-Duration-Distribution.pdf`](t1-ausdauer/L-T1-02_Seiler-2010_Intensity-and-Duration-Distribution.pdf) | 16 |  |
| L-T1-03 | A | Casado et al. 2022 – Periodization in Elite Distance Runners | [`L-T1-03_Casado-2022_Periodization-Elite-Distance-Runners.pdf`](t1-ausdauer/L-T1-03_Casado-2022_Periodization-Elite-Distance-Runners.pdf) | 14 |  |
| L-T1-04 | A | Haugen et al. 2022 – World-Class Distance Runners | [`L-T1-04_Haugen-2022_World-Class-Distance-Runners.pdf`](t1-ausdauer/L-T1-04_Haugen-2022_World-Class-Distance-Runners.pdf) | 18 | CC BY 4.0 |
| L-T1-05 | A | Vernillo et al. 2017 – Uphill and Downhill Running | [`L-T1-05_Vernillo-2017_Uphill-and-Downhill-Running.pdf`](t1-ausdauer/L-T1-05_Vernillo-2017_Uphill-and-Downhill-Running.pdf) | 15 | online 2016, Heft 2017 |
| L-T1-06 | A | Bortolan et al. 2021 – Ski Mountaineering | [`L-T1-06_Bortolan-2021_Ski-Mountaineering-Perspectives.pdf`](t1-ausdauer/L-T1-06_Bortolan-2021_Ski-Mountaineering-Perspectives.pdf) | 7 | Open Access |
| L-T1-07 | B | Laursen P, Buchheit M (Hrsg.) – Science and Application of High-Intensity Interval Training, 2019 | [`L-T1-07_Laursen-2019_Science-and-Application-of-HIIT.pdf`](t1-ausdauer/L-T1-07_Laursen-2019_Science-and-Application-of-HIIT.pdf) | 673 | Kapitel-PDFs in `L-T1-07_kapitel/` |
| L-T1-08 | C | House, Johnston, Jornet 2019 – Training for the Uphill Athlete | [`L-T1-08_House-2019_Training-for-the-Uphill-Athlete.pdf`](t1-ausdauer/L-T1-08_House-2019_Training-for-the-Uphill-Athlete.pdf) | 380 | Scan (Internet Archive) mit Texterkennung, ohne Lesezeichen; Druckseite = PDF-Seite − 2, im Bereich PDF 88–152 − 4 (PDF-Seiten 88–89 wiederholen 86–87; Druckseiten 149–150 fehlen im Scan). Kapitel-PDFs in `L-T1-08_kapitel/` |
| L-T1-09 | C | Tønnessen et al. 2024 – Training Session Models (optional) | [`L-T1-09_Toennessen-2024_Training-Session-Models.pdf`](t1-ausdauer/L-T1-09_Toennessen-2024_Training-Session-Models.pdf) | 19 | Open Access |
| L-T1-10 | C | Sandbakk et al. 2025 – Norwegian World-Class Coaches (optional) | [`L-T1-10_Sandbakk-2025_Best-Practice-Norwegian-Coaches.pdf`](t1-ausdauer/L-T1-10_Sandbakk-2025_Best-Practice-Norwegian-Coaches.pdf) | 23 | CC BY 4.0 |
| L-T1-12 | A | Joyner & Coyle 2008 – Physiology of Champions (optional) | [`L-T1-12_Joyner-2008_Physiology-of-Champions.pdf`](t1-ausdauer/L-T1-12_Joyner-2008_Physiology-of-Champions.pdf) | 10 | Open Access |
| L-T1-16 | C | Koop, Rutberg, Malcolm 2021 – Training Essentials for Ultrarunning, 2. Aufl. (optional) | [`L-T1-16_Koop-2021_Training-Essentials-for-Ultrarunning_2ed.epub`](t1-ausdauer/L-T1-16_Koop-2021_Training-Essentials-for-Ultrarunning_2ed.epub) | EPUB | ohne DRM, keine Seitenmarken; Kapitel-Markdown und Ansichts-PDFs in `L-T1-16_kapitel/` |

### T2 Kraft/Haltung (13.2.3)

| ID | Stufe | Quelle | Datei | Seiten | Hinweis |
|---|---|---|---|---|---|
| L-T2-03 | B | Schumann & Rønnestad (Hrsg.) 2019 – Concurrent Aerobic and Strength Training | [`L-T2-03_Schumann-2019_Concurrent-Aerobic-and-Strength-Training.pdf`](t2-kraft/L-T2-03_Schumann-2019_Concurrent-Aerobic-and-Strength-Training.pdf) | 408 | Kapitel-PDFs in `L-T2-03_kapitel/` |
| L-T2-04 | C | Low 2016 – Overcoming Gravity, 2. Aufl. | [`L-T2-04_Low-2016_Overcoming-Gravity_2ed.pdf`](t2-kraft/L-T2-04_Low-2016_Overcoming-Gravity_2ed.pdf) | 600 | Scan mit fehlerhafter Texterkennung (z. B. „ANO“ statt „AND“), ohne Lesezeichen; Druckseite = PDF-Seite − 14; PDF-Seiten 577/578 sind vertauscht (Druckseiten 564/563). Kapitel-PDFs in `L-T2-04_kapitel/` |
| L-T2-08 | A | Kotarsky et al. 2018 – Progressive Push-up Training | [`L-T2-08_Kotarsky-2018_Progressive-Push-up-Training.pdf`](t2-kraft/L-T2-08_Kotarsky-2018_Progressive-Push-up-Training.pdf) | 9 |  |
| L-T2-09 | A | van den Tillaar 2019 – Push-up vs. Bench Press | [`L-T2-09_vandenTillaar-2019_Push-up-vs-Bench-Press.pdf`](t2-kraft/L-T2-09_vandenTillaar-2019_Push-up-vs-Bench-Press.pdf) | 8 |  |
| L-T2-10 | A | Wiedenmann et al. 2025 – Resistance Training Modalities in Older Adults (Netzwerk-Metaanalyse) | [`L-T2-10_Wiedenmann-2025_Resistance-Training-Modalities-Older-Adults.pdf`](t2-kraft/L-T2-10_Wiedenmann-2025_Resistance-Training-Modalities-Older-Adults.pdf) | 13 |  |
| L-T2-11 | A | Rønnestad & Mujika 2014 – Strength Training for Running and Cycling | [`L-T2-11_Ronnestad-2014_Strength-Training-Running-and-Cycling.pdf`](t2-kraft/L-T2-11_Ronnestad-2014_Strength-Training-Running-and-Cycling.pdf) | 10 |  |
| L-T2-12 | A | Blagrove et al. 2018 – Strength Training and Distance Running (Systematic Review) | [`L-T2-12_Blagrove-2018_Strength-Training-Distance-Running.pdf`](t2-kraft/L-T2-12_Blagrove-2018_Strength-Training-Distance-Running.pdf) | 33 | CC BY 4.0 |
| L-T2-14 | A | Cowley et al. 2026 – Advanced Resistance Training Methods (optional) | [`L-T2-14_Cowley-2026_Advanced-Resistance-Training-Methods.pdf`](t2-kraft/L-T2-14_Cowley-2026_Advanced-Resistance-Training-Methods.pdf) | 23 | optional (D-54) |
| L-T2-15 | A | Warneke et al. 2024 – Effects of Stretching or Strengthening Exercise on Spinal and Lumbopelvic Posture: A Systematic… | [`L-T2-15_Warneke-2024_Stretching-or-Strengthening-Posture.pdf`](t2-kraft/L-T2-15_Warneke-2024_Stretching-or-Strengthening-Posture.pdf) | 13 | CC BY 4.0 |
| L-T2-16 | A | Khorramroo et al. 2026 – Corrective exercises strongly improve posture but fail to produce consistent clinical or functi… | [`L-T2-16_Khorramroo-2026_Corrective-Exercises-Posture.pdf`](t2-kraft/L-T2-16_Khorramroo-2026_Corrective-Exercises-Posture.pdf) | 30 | CC BY 4.0 |
| L-T2-17 | A | Shiri et al. 2018 – Exercise for the Prevention of Low Back Pain | [`L-T2-17_Shiri-2018_Exercise-Prevention-Low-Back-Pain.pdf`](t2-kraft/L-T2-17_Shiri-2018_Exercise-Prevention-Low-Back-Pain.pdf) | 9 |  |
| L-T2-18 | A | Steffens et al. 2016 – Prevention of Low Back Pain | [`L-T2-18_Steffens-2016_Prevention-of-Low-Back-Pain.pdf`](t2-kraft/L-T2-18_Steffens-2016_Prevention-of-Low-Back-Pain.pdf) | 10 |  |
| L-T2-19 | A | Carrasco-Uribarren et al. 2026 – Impact of therapeutic exercise on craniovertebral angle in forward head posture: a systematic r… (optional) | [`L-T2-19_Carrasco-Uribarren-2026_Therapeutic-Exercise-Forward-Head-Posture.pdf`](t2-kraft/L-T2-19_Carrasco-Uribarren-2026_Therapeutic-Exercise-Forward-Head-Posture.pdf) | 13 |  |
| L-T2-20 | A | Pelland et al. 2026 – Resistance Training Dose Response (Meta-Regressionen) | [`L-T2-20_Pelland-2026_Resistance-Training-Dose-Response.pdf`](t2-kraft/L-T2-20_Pelland-2026_Resistance-Training-Dose-Response.pdf) | 25 |  |
| L-T2-21 | A | Robinson et al. 2024 – Proximity to Failure, Dose Response | [`L-T2-21_Robinson-2024_Proximity-to-Failure-Dose-Response.pdf`](t2-kraft/L-T2-21_Robinson-2024_Proximity-to-Failure-Dose-Response.pdf) | 23 |  |
| L-T2-22 | A | Refalo et al. 2023 – Influence of Resistance Training Proximity-to-Failure on Skeletal Muscle Hypertrophy: A Systema… | [`L-T2-22_Refalo-2023_Proximity-to-Failure-Hypertrophy.pdf`](t2-kraft/L-T2-22_Refalo-2023_Proximity-to-Failure-Hypertrophy.pdf) | 17 | CC BY 4.0 |
| L-T2-23 | A | Lopez et al. 2021 – Resistance Training Load Effects on Muscle Hypertrophy and Strength Gain: Systematic Review and… | [`L-T2-23_Lopez-2021_Training-Load-Hypertrophy-Strength.pdf`](t2-kraft/L-T2-23_Lopez-2021_Training-Load-Hypertrophy-Strength.pdf) | 13 | CC BY-NC-ND 4.0; Corrigendum 2022: korrigierte Effektstärken in Abb. 4, Hauptbefunde unverändert – [`L-T2-23_Lopez-2022_Corrigendum.pdf`](t2-kraft/L-T2-23_Lopez-2022_Corrigendum.pdf) |
| L-T2-24 | A | Lopes et al. 2019 – Effects of training with elastic resistance versus conventional resistance on muscular strength… | [`L-T2-24_Lopes-2019_Elastic-vs-Conventional-Resistance.pdf`](t2-kraft/L-T2-24_Lopes-2019_Elastic-vs-Conventional-Resistance.pdf) | 7 | CC BY-NC 4.0; Corrigendum 2020: korrigierter Textabschnitt der Diskussion – [`L-T2-24_Lopes-2020_Corrigendum.pdf`](t2-kraft/L-T2-24_Lopes-2020_Corrigendum.pdf) |
| L-T2-25 | A | Lundberg et al. 2022 – The Effects of Concurrent Aerobic and Strength Training on Muscle Fiber Hypertrophy: A Systemat… | [`L-T2-25_Lundberg-2022_Concurrent-Training-Fiber-Hypertrophy.pdf`](t2-kraft/L-T2-25_Lundberg-2022_Concurrent-Training-Fiber-Hypertrophy.pdf) | 13 | CC BY 4.0 |
| L-T2-26 | A | Monserdà-Vilaró et al. 2023 – Effects of Concurrent Resistance and Endurance Training Using Continuous or Intermittent Protoc… | [`L-T2-26_Monserda-Vilaro-2023_Concurrent-Continuous-vs-Intermittent.pdf`](t2-kraft/L-T2-26_Monserda-Vilaro-2023_Concurrent-Continuous-vs-Intermittent.pdf) | 22 |  |
| L-T2-27 | A | Schoenfeld et al. 2019 – How many times per week should a muscle be trained to maximize muscle hypertrophy? A systematic… (optional) | [`L-T2-27_Schoenfeld-2019_Training-Frequency-Hypertrophy.pdf`](t2-kraft/L-T2-27_Schoenfeld-2019_Training-Frequency-Hypertrophy.pdf) | 11 |  |
| L-T2-28 | A | Refalo et al. 2021 – Influence of resistance training load on measures of skeletal muscle hypertrophy and improvemen… (optional) | [`L-T2-28_Refalo-2021_Training-Load-Hypertrophy.pdf`](t2-kraft/L-T2-28_Refalo-2021_Training-Load-Hypertrophy.pdf) | 24 |  |
| L-T2-29 | A | Carvalho et al. 2022 – Muscle hypertrophy and strength gains after resistance training with different volume-matched l… (optional) | [`L-T2-29_Carvalho-2022_Volume-Matched-Loads-Hypertrophy.pdf`](t2-kraft/L-T2-29_Carvalho-2022_Volume-Matched-Loads-Hypertrophy.pdf) | 58 | Autorenmanuskript (Seitenzahlen nicht zitierfähig) |
| L-T2-30 | A | Grgic et al. 2022 – Effects of resistance training performed to repetition failure or non-failure on muscular stren… (optional) | [`L-T2-30_Grgic-2022_Failure-vs-Non-Failure.pdf`](t2-kraft/L-T2-30_Grgic-2022_Failure-vs-Non-Failure.pdf) | 10 |  |
| L-T2-31 | A | Wilson et al. 2012 – Concurrent training: a meta-analysis examining interference of aerobic and resistance exercises (optional) | [`L-T2-31_Wilson-2012_Concurrent-Training-Interference.pdf`](t2-kraft/L-T2-31_Wilson-2012_Concurrent-Training-Interference.pdf) | 15 |  |
| L-T2-32 | A | Sabag et al. 2018 – The compatibility of concurrent high intensity interval training and resistance training for mu… (optional) | [`L-T2-32_Sabag-2018_Concurrent-HIIT-and-Resistance.pdf`](t2-kraft/L-T2-32_Sabag-2018_Concurrent-HIIT-and-Resistance.pdf) | 13 |  |
| L-T2-33 | B | McGill 2016 – Low Back Disorders, 3. Aufl. (optional) | [`L-T2-33_McGill-2016_Low-Back-Disorders_3ed.pdf`](t2-kraft/L-T2-33_McGill-2016_Low-Back-Disorders_3ed.pdf) | 905 | 3. statt 4. Aufl. (Nachtrag D-51); Druckseite = PDF-Seite. Kapitel-PDFs in `L-T2-33_kapitel/` |

### T3 Klettern/Bouldern (13.2.4)

| ID | Stufe | Quelle | Datei | Seiten | Hinweis |
|---|---|---|---|---|---|
| L-T3-01 | A | Stien et al. 2023 – Climbing and Resistance Training (Meta-Analyse) | [`L-T3-01_Stien-2023_Climbing-and-Resistance-Training-Meta-Analysis.pdf`](t3-klettern/L-T3-01_Stien-2023_Climbing-and-Resistance-Training-Meta-Analysis.pdf) | 13 | CC BY 4.0 |
| L-T3-02 | A | Langer, Simon, Wiemeyer 2023 – Strength Training in Climbing | [`L-T3-02_Langer-2023_Strength-Training-in-Climbing.pdf`](t3-klettern/L-T3-02_Langer-2023_Strength-Training-in-Climbing.pdf) | 17 |  |
| L-T3-03 | A | Langer, Simon, Wiemeyer 2023 – Performance Testing in Climbing | [`L-T3-03_Langer-2023_Performance-Testing-in-Climbing.pdf`](t3-klettern/L-T3-03_Langer-2023_Performance-Testing-in-Climbing.pdf) | 23 | CC BY; Front Sports Act Living Bd. 5, Art. 1130812 |
| L-T3-04 | A | Draper et al. 2015 – IRCRA Position Statement (Grading Scales, Ability Grouping) | [`L-T3-04_Draper-2015_IRCRA-Grading-Position-Statement.pdf`](t3-klettern/L-T3-04_Draper-2015_IRCRA-Grading-Position-Statement.pdf) | 8 |  |
| L-T3-05 | A | López-Rivera & González-Badillo 2012 – The effects of two maximum grip strength training methods using the same effort duration and di… | [`L-T3-05_Lopez-Rivera-2012_Grip-Strength-Edge-Depth.pdf`](t3-klettern/L-T3-05_Lopez-Rivera-2012_Grip-Strength-Edge-Depth.pdf) | 12 |  |
| L-T3-06 | B | Schöffl et al. (Hrsg.) 2022 – Climbing Medicine | [`L-T3-06_Schoeffl-2022_Climbing-Medicine.pdf`](t3-klettern/L-T3-06_Schoeffl-2022_Climbing-Medicine.pdf) | 319 | Kapitel-PDFs in `L-T3-06_kapitel/` |
| L-T3-09 | B | Hörst EJ – Training for Climbing, 3. Aufl. 2016 | [`L-T3-09_Hoerst-2016_Training-for-Climbing_3ed.pdf`](t3-klettern/L-T3-09_Hoerst-2016_Training-for-Climbing_3ed.pdf) | 356 | Scan (Internet Archive) mit Texterkennung, ohne Lesezeichen; Druckseite = PDF-Seite − 16. Kapitel-PDFs in `L-T3-09_kapitel/` |
| L-T3-10 | C | Mobråten, Christophersen 2020 – The Climbing Bible | [`L-T3-10_Mobraten-2020_Climbing-Bible.epub`](t3-klettern/L-T3-10_Mobraten-2020_Climbing-Bible.epub) | EPUB | ohne DRM; keine Seitenmarken; Kapitel-Markdown und Ansichts-PDFs in `L-T3-10_kapitel/` |
| L-T3-15 | C | Anderson, Anderson 2014 – The Rock Climber's Training Manual (optional) | [`L-T3-15_Anderson-2014_Rock-Climbers-Training-Manual.pdf`](t3-klettern/L-T3-15_Anderson-2014_Rock-Climbers-Training-Manual.pdf) | 308 | Scan (Internet Archive) mit Texterkennung, ohne Lesezeichen; Druckseite = PDF-Seite − 2. Kapitel-PDFs in `L-T3-15_kapitel/` |
| L-T3-16 | C | Bechtel 2020 – Logical Progression, 2. Aufl. | [`L-T3-16_Bechtel-2020_Logical-Progression_2ed.pdf`](t3-klettern/L-T3-16_Bechtel-2020_Logical-Progression_2ed.pdf) | 238 | ohne Lesezeichen; Druckseite = PDF-Seite − 14. Kapitel-PDFs in `L-T3-16_kapitel/` |
| L-T3-18 | A | López-Rivera & González-Badillo 2019 – Comparison of the Effects of Three Hangboard Strength and Endurance Training Programs on Grip E… | [`L-T3-18_Lopez-Rivera-2019_Hangboard-Training-Programs.pdf`](t3-klettern/L-T3-18_Lopez-Rivera-2019_Hangboard-Training-Programs.pdf) | 11 |  |
| L-T3-19 | B | Consuegra 2023 – The Science of Climbing Training | [`L-T3-19_Consuegra-2023_Science-of-Climbing-Training.epub`](t3-klettern/L-T3-19_Consuegra-2023_Science-of-Climbing-Training.epub) | EPUB (216 S. Druck) | ohne DRM; keine Seitenmarken; Kapitel-Markdown und Ansichts-PDFs in `L-T3-19_kapitel/` |
| L-T3-20 | C | Mobråten, Christophersen 2022 – The Climbing Bible: Practical Exercises | [`L-T3-20_Mobraten-2022_Climbing-Bible-Practical-Exercises.epub`](t3-klettern/L-T3-20_Mobraten-2022_Climbing-Bible-Practical-Exercises.epub) | EPUB (Seitenmarken bis S. 192) | ohne DRM; Kapitel-Markdown und Ansichts-PDFs in `L-T3-20_kapitel/` |
| L-T3-21 | C | Christophersen 2024 – The Climbing Bible: Managing Injuries | [`L-T3-21_Christophersen-2024_Climbing-Bible-Managing-Injuries.epub`](t3-klettern/L-T3-21_Christophersen-2024_Climbing-Bible-Managing-Injuries.epub) | EPUB (Seitenmarken bis S. 157) | ohne DRM; vorläufig aufgenommen (Bestätigung offen); Kapitel-Markdown und Ansichts-PDFs in `L-T3-21_kapitel/` |

### R Reha/Prävention (13.2.5)

| ID | Stufe | Quelle | Datei | Seiten | Hinweis |
|---|---|---|---|---|---|
| L-R-01 | A | Breda et al. 2021 – Effectiveness of progressive tendon-loading exercise therapy in patients with patellar tendinop… | [`L-R-01_Breda-2021_Progressive-Tendon-Loading.pdf`](r-reha/L-R-01_Breda-2021_Progressive-Tendon-Loading.pdf) | 9 | CC BY-NC 4.0 |
| L-R-02 | A | Kongsgaard et al. 2009 – Kortison, exzentrisch, HSR bei Patellatendinopathie | [`L-R-02_Kongsgaard-2009_Patellar-Tendinopathy-HSR.pdf`](r-reha/L-R-02_Kongsgaard-2009_Patellar-Tendinopathy-HSR.pdf) | 13 |  |
| L-R-03 | A | Agergaard et al. 2021 – Heavy vs. Moderate Loads, Patellar Tendinopathy (RCT) | [`L-R-03_Agergaard-2021_Heavy-vs-Moderate-Loads-Patellar-Tendinopathy.pdf`](r-reha/L-R-03_Agergaard-2021_Heavy-vs-Moderate-Loads-Patellar-Tendinopathy.pdf) | 12 |  |
| L-R-04 | A | Agergaard et al. 2026 – Extended Restitution Between Sessions Does Not Enhance the Benefits of 12 Weeks Exercise-Based … | [`L-R-04_Agergaard-2026_TEREX-Extended-Restitution.pdf`](r-reha/L-R-04_Agergaard-2026_TEREX-Extended-Restitution.pdf) | 12 | CC BY |
| L-R-05 | A | Challoumas et al. 2023 – Effectiveness of Exercise Treatments with or without Adjuncts for Common Lower Limb Tendinopath… | [`L-R-05_Challoumas-2023_Lower-Limb-Tendinopathy-Living-Review.pdf`](r-reha/L-R-05_Challoumas-2023_Lower-Limb-Tendinopathy-Living-Review.pdf) | 14 | CC BY 4.0 |
| L-R-06 | A | Liu et al. 2026 – Comparative effectiveness of exercise interventions for patellar tendinopathy: a systematic rev… | [`L-R-06_Liu-2026_Patellar-Tendinopathy-Network-Meta-Analysis.pdf`](r-reha/L-R-06_Liu-2026_Patellar-Tendinopathy-Network-Meta-Analysis.pdf) | 14 | CC BY-NC-ND 4.0 |
| L-R-07 | A | Visentini et al. 1998 – The VISA score: an index of severity of symptoms in patients with jumper's knee (patellar tendi… | [`L-R-07_Visentini-1998_VISA-Score.pdf`](r-reha/L-R-07_Visentini-1998_VISA-Score.pdf) | 7 |  |
| L-R-08 | A | Lohrer & Nauck 2011 – VISA-P deutsch (VISA-P-G) | [`L-R-08_Lohrer-2011_VISA-P-German.pdf`](r-reha/L-R-08_Lohrer-2011_VISA-P-German.pdf) | 12 | letzte Seite: Erratum 2013 – Punktwerte Items 8b/8c im Artikel falsch |
| L-R-09 | A | Hernandez-Sanchez et al. 2014 – Responsiveness of the VISA-P scale for patellar tendinopathy in athletes | [`L-R-09_Hernandez-Sanchez-2014_VISA-P-Responsiveness.pdf`](r-reha/L-R-09_Hernandez-Sanchez-2014_VISA-P-Responsiveness.pdf) | 7 |  |
| L-R-10 | A | Clifford et al. 2020 – Isometric Exercise, Patellar Tendinopathy (optional) | [`L-R-10_Clifford-2020_Isometric-Exercise-Patellar-Tendinopathy.pdf`](r-reha/L-R-10_Clifford-2020_Isometric-Exercise-Patellar-Tendinopathy.pdf) | 19 | CC BY 4.0 |
| L-R-11 | A | Sprague et al. 2018 – Risk Factors for Patellar Tendinopathy (optional) | [`L-R-11_Sprague-2018_Patellar-Tendinopathy-Risk-Factors.pdf`](r-reha/L-R-11_Sprague-2018_Patellar-Tendinopathy-Risk-Factors.pdf) | 12 |  |
| L-R-12 | A | Backman & Danielson 2011 – Low range of ankle dorsiflexion predisposes for patellar tendinopathy in junior elite basketbal… (optional) | [`L-R-12_Backman-2011_Ankle-Dorsiflexion-Patellar-Tendinopathy.pdf`](r-reha/L-R-12_Backman-2011_Ankle-Dorsiflexion-Patellar-Tendinopathy.pdf) | 9 |  |
| L-R-13 | A | Martin et al. 2021 – Lateral Ankle Ligament Sprains (JOSPT-Leitlinie) | [`L-R-13_Martin-2021_Lateral-Ankle-Sprain-Guideline.pdf`](r-reha/L-R-13_Martin-2021_Lateral-Ankle-Sprain-Guideline.pdf) | 80 |  |
| L-R-14 | A | Hupperets et al. 2009 – Home Programme, Ankle Sprain Recurrence (RCT) | [`L-R-14_Hupperets-2009_Home-Programme-Ankle-Sprain-Recurrence.pdf`](r-reha/L-R-14_Hupperets-2009_Home-Programme-Ankle-Sprain-Recurrence.pdf) | 6 |  |
| L-R-15 | A | Schiftan et al. 2015 – The effectiveness of proprioceptive training in preventing ankle sprains in sporting population… | [`L-R-15_Schiftan-2015_Proprioceptive-Training-Ankle-Sprain.pdf`](r-reha/L-R-15_Schiftan-2015_Proprioceptive-Training-Ankle-Sprain.pdf) | 7 |  |
| L-R-16 | A | Tang et al. 2024 – Meta-analysis of the dosage of balance training on ankle function and dynamic balance ability i… | [`L-R-16_Tang-2024_Balance-Training-Dosage-Ankle.pdf`](r-reha/L-R-16_Tang-2024_Balance-Training-Dosage-Ankle.pdf) | 20 | CC BY-NC-ND 4.0 |
| L-R-17 | A | Donovan et al. 2016 – Destabilization Devices, Ankle Instability | [`L-R-17_Donovan-2016_Destabilization-Devices-Ankle.pdf`](r-reha/L-R-17_Donovan-2016_Destabilization-Devices-Ankle.pdf) | 19 |  |
| L-R-18 | A | Nielsen et al. 2014 – Excessive progression in weekly running distance and risk of running-related injuries: an assoc… (optional) | [`L-R-18_Nielsen-2014_Running-Distance-Progression-Injuries.pdf`](r-reha/L-R-18_Nielsen-2014_Running-Distance-Progression-Injuries.pdf) | 25 | Autorenmanuskript (Seitenzahlen nicht zitierfähig) |
| L-R-19 | A | Kiers et al. 2012 – Ankle proprioception is not targeted by exercises on an unstable surface (optional) | [`L-R-19_Kiers-2012_Unstable-Surface-Ankle-Proprioception.pdf`](r-reha/L-R-19_Kiers-2012_Unstable-Surface-Ankle-Proprioception.pdf) | 9 |  |
| L-R-20 | A | Fakontis et al. 2023 – Efficacy of resistance training with elastic bands compared to proprioceptive training on balan… (optional) | [`L-R-20_Fakontis-2023_Elastic-Bands-vs-Proprioceptive-Training.pdf`](r-reha/L-R-20_Fakontis-2023_Elastic-Bands-vs-Proprioceptive-Training.pdf) | 11 |  |
| L-R-21 | A | Giboin et al. 2018 – Slackline Training (optional) | [`L-R-21_Giboin-2018_Slackline-Training.pdf`](r-reha/L-R-21_Giboin-2018_Slackline-Training.pdf) | 9 |  |
| L-R-22 | A | Delahunt et al. 2018 – Clinical assessment of acute lateral ankle sprain injuries (ROAST): 2019 consensus statement an… (optional) | [`L-R-22_Delahunt-2018_ROAST-Consensus.pdf`](r-reha/L-R-22_Delahunt-2018_ROAST-Consensus.pdf) | 7 |  |
| L-R-23 | A | Lopes et al. 2025 – Exercise for patellar tendinopathy | [`L-R-23_Lopes-2025_Exercise-for-Patellar-Tendinopathy-Cochrane.pdf`](r-reha/L-R-23_Lopes-2025_Exercise-for-Patellar-Tendinopathy-Cochrane.pdf) | 65 |  |
| L-R-24 | A | Schuster Brandt Frandsen et al. 2025 – How much running is too much? Identifying high-risk running sessions in a 5200-person cohort st… | [`L-R-24_SchusterBrandtFrandsen-2025_High-Risk-Running-Sessions.pdf`](r-reha/L-R-24_SchusterBrandtFrandsen-2025_High-Risk-Running-Sessions.pdf) | 8 | CC BY-NC 4.0 |
| L-R-25 | A | Wagemans et al. 2022 – Exercise-based rehabilitation reduces reinjury following acute lateral ankle sprain: A systemat… | [`L-R-25_Wagemans-2022_Rehabilitation-Reinjury-Ankle-Sprain.pdf`](r-reha/L-R-25_Wagemans-2022_Rehabilitation-Reinjury-Ankle-Sprain.pdf) | 20 |  |
| L-R-26 | A | Doherty et al. 2017 – Ankle Sprain, Overview of Reviews | [`L-R-26_Doherty-2017_Ankle-Sprain-Overview-of-Reviews.pdf`](r-reha/L-R-26_Doherty-2017_Ankle-Sprain-Overview-of-Reviews.pdf) | 18 |  |
| L-R-27 | A | Deng et al. 2025 – Long-term Prognosis of Athletes With Patellar Tendinopathy Receiving Physical Therapy: Patient-… (optional) | [`L-R-27_Deng-2025_Patellar-Tendinopathy-Long-Term-Prognosis.pdf`](r-reha/L-R-27_Deng-2025_Patellar-Tendinopathy-Long-Term-Prognosis.pdf) | 9 |  |
| L-R-28 | A | Hjortshoej et al. 2025 – Effect of Low-Load Blood-Flow Restricted Training Versus Heavy Slow Resistance Training in Unil… (optional) | [`L-R-28_Hjortshoej-2025_BFR-vs-HSR-Patellar-Tendinopathy.pdf`](r-reha/L-R-28_Hjortshoej-2025_BFR-vs-HSR-Patellar-Tendinopathy.pdf) | 12 |  |
| L-R-29 | B | Brukner & Khan 2017 – Clinical Sports Medicine, Vol. 1 Injuries, 5. Aufl. (optional) | nur Kapitel-PDFs: [`L-R-29_kapitel/`](r-reha/L-R-29_kapitel/) | 1227 | 5. statt 6. Aufl. (Nachtrag D-51); Scan mit Texterkennung durch die Code-Instanz (Tesseract, kann Erkennungsfehler enthalten); Gesamt 120 MB, daher nur Kapitel; Druckseite = PDF-Seite − 41 |
| L-R-30 | B | Engelhardt (Hrsg.) 2016 – Sportverletzungen (GOTS-Manual), 3. Aufl. (optional) | [`L-R-30_Engelhardt-2016_Sportverletzungen-GOTS-Manual_3ed.pdf`](r-reha/L-R-30_Engelhardt-2016_Sportverletzungen-GOTS-Manual_3ed.pdf) | 912 | 3. statt 4. Aufl. (Nachtrag D-51); Druckseiten je Kapitel siehe unten (Versatz nicht konstant). Kapitel-PDFs in `L-R-30_kapitel/` |

### T4 Beweglichkeit/Mobilität (13.2.6)

| ID | Stufe | Quelle | Datei | Seiten | Hinweis |
|---|---|---|---|---|---|
| L-T4-01 | A | Warneke et al. 2025 – Delphi Consensus on Stretching | [`L-T4-01_Warneke-2025_Delphi-Consensus-Stretching.pdf`](t4-beweglichkeit/L-T4-01_Warneke-2025_Delphi-Consensus-Stretching.pdf) | 14 | CC BY-NC-ND 4.0 |
| L-T4-02 | A | Konrad et al. 2024 – Chronic Stretching and ROM (Meta-Analyse) | [`L-T4-02_Konrad-2024_Chronic-Stretching-ROM-Meta-Analysis.pdf`](t4-beweglichkeit/L-T4-02_Konrad-2024_Chronic-Stretching-ROM-Meta-Analysis.pdf) | 9 | CC BY-NC-ND 4.0 |
| L-T4-03 | A | Oba et al. 2026 – Moderators of Chronic Static Stretching | [`L-T4-03_Oba-2026_Moderators-Chronic-Static-Stretching.pdf`](t4-beweglichkeit/L-T4-03_Oba-2026_Moderators-Chronic-Static-Stretching.pdf) | 24 | CC BY 4.0 |
| L-T4-04 | A | Arntz et al. 2023 – Static Stretching, Strength and Power | [`L-T4-04_Arntz-2023_Static-Stretching-Strength-and-Power.pdf`](t4-beweglichkeit/L-T4-04_Arntz-2023_Static-Stretching-Strength-and-Power.pdf) | 23 |  |
| L-T4-05 | A | Thomas et al. 2018 – Stretching Typology and Duration | [`L-T4-05_Thomas-2018_Stretching-Typology-and-Duration.pdf`](t4-beweglichkeit/L-T4-05_Thomas-2018_Stretching-Typology-and-Duration.pdf) | 12 |  |
| L-T4-06 | A | Behm et al. 2016 – Acute Effects of Muscle Stretching | [`L-T4-06_Behm-2016_Acute-Effects-of-Stretching.pdf`](t4-beweglichkeit/L-T4-06_Behm-2016_Acute-Effects-of-Stretching.pdf) | 11 |  |
| L-T4-08 | A | Warneke et al. 2024 – Foam Rolling and Stretching in the Warm-up | [`L-T4-08_Warneke-2024_Foam-Rolling-Stretching-Warm-up.pdf`](t4-beweglichkeit/L-T4-08_Warneke-2024_Foam-Rolling-Stretching-Warm-up.pdf) | 12 | CC BY-NC-ND 4.0 |
| L-T4-10 | A | Alizadeh et al. 2023 – Resistance Training and ROM | [`L-T4-10_Alizadeh-2023_Resistance-Training-ROM.pdf`](t4-beweglichkeit/L-T4-10_Alizadeh-2023_Resistance-Training-ROM.pdf) | 16 |  |
| L-T4-12 | A | Konrad et al. 2024 – Static Stretching vs. Foam Rolling | [`L-T4-12_Konrad-2024_Stretching-vs-Foam-Rolling-ROM.pdf`](t4-beweglichkeit/L-T4-12_Konrad-2024_Stretching-vs-Foam-Rolling-ROM.pdf) | 16 |  |
| L-T4-13 | A | Skopal et al. 2024 – Mobility Training Methods in Sporting Populations (optional) | [`L-T4-13_Skopal-2024_Mobility-Training-Methods.pdf`](t4-beweglichkeit/L-T4-13_Skopal-2024_Mobility-Training-Methods.pdf) | 16 |  |
| L-T4-14 | A | Lauersen et al. 2014 – Exercise Interventions to Prevent Sports Injuries | [`L-T4-14_Lauersen-2014_Exercise-Interventions-Injury-Prevention.pdf`](t4-beweglichkeit/L-T4-14_Lauersen-2014_Exercise-Interventions-Injury-Prevention.pdf) | 10 |  |
| L-T4-16 | A | Herbert et al. 2011 – Stretching and Muscle Soreness (Cochrane) | [`L-T4-16_Herbert-2011_Stretching-Muscle-Soreness-Cochrane.pdf`](t4-beweglichkeit/L-T4-16_Herbert-2011_Stretching-Muscle-Soreness-Cochrane.pdf) | 50 |  |
| L-T4-17 | A | Behm et al. 2026 – Responses to Stretching (narrativer Review) | [`L-T4-17_Behm-2026_Responses-to-Stretching.pdf`](t4-beweglichkeit/L-T4-17_Behm-2026_Responses-to-Stretching.pdf) | 11 |  |
| L-T4-19 | A | Winters et al. 2004 – Passive vs. Active Hip Flexor Stretching (RCT) | [`L-T4-19_Winters-2004_Passive-vs-Active-Hip-Flexor-Stretching.pdf`](t4-beweglichkeit/L-T4-19_Winters-2004_Passive-vs-Active-Hip-Flexor-Stretching.pdf) | 8 | DOI nicht ermittelt (V-22) |
| L-T4-32 | B | Behm 2025 – The Science and Physiology of Flexibility and Stretching, 2. Aufl. | [`L-T4-32_Behm-2025_Science-and-Physiology-of-Flexibility-and-Stretching_2ed.pdf`](t4-beweglichkeit/L-T4-32_Behm-2025_Science-and-Physiology-of-Flexibility-and-Stretching_2ed.pdf) | 281 | E-Book-PDF; Druckseite = PDF-Seite − 15. Kapitel-PDFs in `L-T4-32_kapitel/` |
| L-T4-34 | C | Nelson, Kokkonen 2021 – Stretching Anatomy, 3. Aufl. | [`L-T4-34_Nelson-2021_Stretching-Anatomy_3ed.pdf`](t4-beweglichkeit/L-T4-34_Nelson-2021_Stretching-Anatomy_3ed.pdf) | 265 | Übungskatalog; Druckseite = PDF-Seite − 11. Kapitel-PDFs in `L-T4-34_kapitel/`; zusätzlich als EPUB |
| L-T4-34 | C | Nelson, Kokkonen 2021 – Stretching Anatomy, 3. Aufl. (EPUB) | [`L-T4-34_Nelson-2021_Stretching-Anatomy_3ed.epub`](t4-beweglichkeit/L-T4-34_Nelson-2021_Stretching-Anatomy_3ed.epub) | EPUB | ohne DRM, Seitenmarken der Druckausgabe; nicht umgewandelt (Entscheidung Athlet) |
| L-T4-36 | C | Schleip, Wilke (Hrsg.) 2021 – Fascia in Sport and Movement, 2. Aufl. (optional) | nur Kapitel-PDFs: [`L-T4-36_kapitel/`](t4-beweglichkeit/L-T4-36_kapitel/) | 618 | Gesamt-PDF 159 MB über dem GitHub-Limit (D-31), daher nur 50 Kapitel-PDFs; Druckseite = PDF-Seite − 19 |

Summe: 118 Werke in 119 Dateien (davon 17 Bücher mit Kapitel-PDFs – L-T4-36 und L-R-29 nur als Kapitel –, 5 EPUBs mit Kapitel-Markdown und Ansichts-PDFs; dazu 2 Corrigenda und L-T4-34 zusätzlich als EPUB).

## Noch nicht vorhanden

Stand nach Beschaffungsliste 13.4 (Übergaben AP-06 Teil A bis D, Literatur-Nachsteuerung, T4 und Lückenprüfung Standardwerke eingearbeitet). Vorhandene Ausgaben gelten, auf Neuauflagen wird nicht gewartet (Nachtrag D-51). Die Literatursuche ist abgeschlossen (2026-09-29); L-T4-35 Freiwald entfällt (nicht beschaffbar). Kein Titel ist mehr offen.

### Kaufen oder über die Bibliothek (nicht frei verfügbar)

| Prio | ID | Quelle | Wofür |
|---|---|---|---|

### Frei verfügbar (PubMed Central)

Keine offenen Titel mehr.

### Nur bei Bedarf

- T4 Beweglichkeit (optional, D-79): L-T4-07, -09, -11, -15, -18, -20, -21, -23, -25 bis -31 (in PMC: -07, -09, -11, -15, -18, -21, -27 bis -30)
- optionale Bücher aus 13.4 („bei Bedarf“): L-T1-11, L-T1-14, L-T2-05, L-T2-06, L-T3-11


## Kapitel-PDFs

### L-A03 NSCA – Essentials (5. Aufl.) – `uebergreifend/L-A03_kapitel/`

49 Dateien, 1876 PDF-Seiten.

| Nr. | Titel | PDF-Seiten | Datei |
|---|---|---|---|
| 00 | Vorspann | 1–37 | [`L-A03_00_Vorspann.pdf`](uebergreifend/L-A03_kapitel/L-A03_00_Vorspann.pdf) |
| 01-1 | Structure and Function of Body Systems (Teil 1/2) | 38–67 | [`L-A03_01-1_Structure-and-Function-of-Body-Systems.pdf`](uebergreifend/L-A03_kapitel/L-A03_01-1_Structure-and-Function-of-Body-Systems.pdf) |
| 01-2 | Structure and Function of Body Systems (Teil 2/2) | 68–103 | [`L-A03_01-2_Structure-and-Function-of-Body-Systems.pdf`](uebergreifend/L-A03_kapitel/L-A03_01-2_Structure-and-Function-of-Body-Systems.pdf) |
| 02-1 | Biomechanics of Resistance Exercise (Teil 1/2) | 104–147 | [`L-A03_02-1_Biomechanics-of-Resistance-Exercise.pdf`](uebergreifend/L-A03_kapitel/L-A03_02-1_Biomechanics-of-Resistance-Exercise.pdf) |
| 02-2 | Biomechanics of Resistance Exercise (Teil 2/2) | 148–173 | [`L-A03_02-2_Biomechanics-of-Resistance-Exercise.pdf`](uebergreifend/L-A03_kapitel/L-A03_02-2_Biomechanics-of-Resistance-Exercise.pdf) |
| 03 | Bioenergetics of Exercise and Training | 174–226 | [`L-A03_03_Bioenergetics-of-Exercise-and-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_03_Bioenergetics-of-Exercise-and-Training.pdf) |
| 04 | Endocrine Responses to Resistance Exercise and Training | 227–280 | [`L-A03_04_Endocrine-Responses-to-Resistance-Exercise-and-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_04_Endocrine-Responses-to-Resistance-Exercise-and-Training.pdf) |
| 05 | Adaptations to Anaerobic Training | 281–338 | [`L-A03_05_Adaptations-to-Anaerobic-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_05_Adaptations-to-Anaerobic-Training.pdf) |
| 06 | Adaptations to Aerobic Training | 339–383 | [`L-A03_06_Adaptations-to-Aerobic-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_06_Adaptations-to-Aerobic-Training.pdf) |
| 07 | Age-Related Differences and Their Implications for Resistance Training | 384–437 | [`L-A03_07_Age-Related-Differences-and-Their-Implications-for.pdf`](uebergreifend/L-A03_kapitel/L-A03_07_Age-Related-Differences-and-Their-Implications-for.pdf) |
| 08 | Sex-Related Differences and Their Implications for Resistance Training | 438–464 | [`L-A03_08_Sex-Related-Differences-and-Their-Implications-for.pdf`](uebergreifend/L-A03_kapitel/L-A03_08_Sex-Related-Differences-and-Their-Implications-for.pdf) |
| 09-1 | Psychological Foundations of Performance (Teil 1/2) | 465–504 | [`L-A03_09-1_Psychological-Foundations-of-Performance.pdf`](uebergreifend/L-A03_kapitel/L-A03_09-1_Psychological-Foundations-of-Performance.pdf) |
| 09-2 | Psychological Foundations of Performance (Teil 2/2) | 505–535 | [`L-A03_09-2_Psychological-Foundations-of-Performance.pdf`](uebergreifend/L-A03_kapitel/L-A03_09-2_Psychological-Foundations-of-Performance.pdf) |
| 10-1 | Basic Nutritional Factors Affecting Health (Teil 1/2) | 536–574 | [`L-A03_10-1_Basic-Nutritional-Factors-Affecting-Health.pdf`](uebergreifend/L-A03_kapitel/L-A03_10-1_Basic-Nutritional-Factors-Affecting-Health.pdf) |
| 10-2 | Basic Nutritional Factors Affecting Health (Teil 2/2) | 575–612 | [`L-A03_10-2_Basic-Nutritional-Factors-Affecting-Health.pdf`](uebergreifend/L-A03_kapitel/L-A03_10-2_Basic-Nutritional-Factors-Affecting-Health.pdf) |
| 11 | Nutrition Strategies for Maximizing Performance | 613–658 | [`L-A03_11_Nutrition-Strategies-for-Maximizing-Performance.pdf`](uebergreifend/L-A03_kapitel/L-A03_11_Nutrition-Strategies-for-Maximizing-Performance.pdf) |
| 12-1 | Performance-Enhancing Substances and Methods (Teil 1/2) | 659–697 | [`L-A03_12-1_Performance-Enhancing-Substances-and-Methods.pdf`](uebergreifend/L-A03_kapitel/L-A03_12-1_Performance-Enhancing-Substances-and-Methods.pdf) |
| 12-2 | Performance-Enhancing Substances and Methods (Teil 2/2) | 698–728 | [`L-A03_12-2_Performance-Enhancing-Substances-and-Methods.pdf`](uebergreifend/L-A03_kapitel/L-A03_12-2_Performance-Enhancing-Substances-and-Methods.pdf) |
| 13 | Principles of Test Selection and Administration | 729–763 | [`L-A03_13_Principles-of-Test-Selection-and-Administration.pdf`](uebergreifend/L-A03_kapitel/L-A03_13_Principles-of-Test-Selection-and-Administration.pdf) |
| 14-1 | Administration, Scoring, and Interpretation of Selected Tests (Teil 1/2) | 764–808 | [`L-A03_14-1_Administration-Scoring-and-Interpretation-of-Selected-Tests.pdf`](uebergreifend/L-A03_kapitel/L-A03_14-1_Administration-Scoring-and-Interpretation-of-Selected-Tests.pdf) |
| 14-2 | Administration, Scoring, and Interpretation of Selected Tests (Teil 2/2) | 809–853 | [`L-A03_14-2_Administration-Scoring-and-Interpretation-of-Selected-Tests.pdf`](uebergreifend/L-A03_kapitel/L-A03_14-2_Administration-Scoring-and-Interpretation-of-Selected-Tests.pdf) |
| 15-1 | Performance Preparation, Mobility, and Flexibility (Teil 1/3) | 854–878 | [`L-A03_15-1_Performance-Preparation-Mobility-and-Flexibility.pdf`](uebergreifend/L-A03_kapitel/L-A03_15-1_Performance-Preparation-Mobility-and-Flexibility.pdf) |
| 15-2 | Performance Preparation, Mobility, and Flexibility (Teil 2/3) | 879–915 | [`L-A03_15-2_Performance-Preparation-Mobility-and-Flexibility.pdf`](uebergreifend/L-A03_kapitel/L-A03_15-2_Performance-Preparation-Mobility-and-Flexibility.pdf) |
| 15-3 | Performance Preparation, Mobility, and Flexibility (Teil 3/3) | 916–946 | [`L-A03_15-3_Performance-Preparation-Mobility-and-Flexibility.pdf`](uebergreifend/L-A03_kapitel/L-A03_15-3_Performance-Preparation-Mobility-and-Flexibility.pdf) |
| 16-1 | Exercise Technique for Free Weight and Machine Training (Teil 1/4) | 947–981 | [`L-A03_16-1_Exercise-Technique-for-Free-Weight-and-Machine-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_16-1_Exercise-Technique-for-Free-Weight-and-Machine-Training.pdf) |
| 16-2 | Exercise Technique for Free Weight and Machine Training (Teil 2/4) | 982–1016 | [`L-A03_16-2_Exercise-Technique-for-Free-Weight-and-Machine-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_16-2_Exercise-Technique-for-Free-Weight-and-Machine-Training.pdf) |
| 16-3 | Exercise Technique for Free Weight and Machine Training (Teil 3/4) | 1017–1052 | [`L-A03_16-3_Exercise-Technique-for-Free-Weight-and-Machine-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_16-3_Exercise-Technique-for-Free-Weight-and-Machine-Training.pdf) |
| 16-4 | Exercise Technique for Free Weight and Machine Training (Teil 4/4) | 1053–1087 | [`L-A03_16-4_Exercise-Technique-for-Free-Weight-and-Machine-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_16-4_Exercise-Technique-for-Free-Weight-and-Machine-Training.pdf) |
| 17-1 | Exercise Technique for Alternative Modes and Nontraditional Implement Training (Teil 1/3) | 1088–1118 | [`L-A03_17-1_Exercise-Technique-for-Alternative-Modes-and-Nontraditional.pdf`](uebergreifend/L-A03_kapitel/L-A03_17-1_Exercise-Technique-for-Alternative-Modes-and-Nontraditional.pdf) |
| 17-2 | Exercise Technique for Alternative Modes and Nontraditional Implement Training (Teil 2/3) | 1119–1152 | [`L-A03_17-2_Exercise-Technique-for-Alternative-Modes-and-Nontraditional.pdf`](uebergreifend/L-A03_kapitel/L-A03_17-2_Exercise-Technique-for-Alternative-Modes-and-Nontraditional.pdf) |
| 17-3 | Exercise Technique for Alternative Modes and Nontraditional Implement Training (Teil 3/3) | 1153–1185 | [`L-A03_17-3_Exercise-Technique-for-Alternative-Modes-and-Nontraditional.pdf`](uebergreifend/L-A03_kapitel/L-A03_17-3_Exercise-Technique-for-Alternative-Modes-and-Nontraditional.pdf) |
| 18-1 | Program Design for Resistance Training (Teil 1/2) | 1186–1220 | [`L-A03_18-1_Program-Design-for-Resistance-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_18-1_Program-Design-for-Resistance-Training.pdf) |
| 18-2 | Program Design for Resistance Training (Teil 2/2) | 1221–1259 | [`L-A03_18-2_Program-Design-for-Resistance-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_18-2_Program-Design-for-Resistance-Training.pdf) |
| 19-1 | Program Design and Technique for Plyometric Training (Teil 1/4) | 1260–1288 | [`L-A03_19-1_Program-Design-and-Technique-for-Plyometric-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_19-1_Program-Design-and-Technique-for-Plyometric-Training.pdf) |
| 19-2 | Program Design and Technique for Plyometric Training (Teil 2/4) | 1289–1328 | [`L-A03_19-2_Program-Design-and-Technique-for-Plyometric-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_19-2_Program-Design-and-Technique-for-Plyometric-Training.pdf) |
| 19-3 | Program Design and Technique for Plyometric Training (Teil 3/4) | 1329–1363 | [`L-A03_19-3_Program-Design-and-Technique-for-Plyometric-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_19-3_Program-Design-and-Technique-for-Plyometric-Training.pdf) |
| 19-4 | Program Design and Technique for Plyometric Training (Teil 4/4) | 1364–1397 | [`L-A03_19-4_Program-Design-and-Technique-for-Plyometric-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_19-4_Program-Design-and-Technique-for-Plyometric-Training.pdf) |
| 20-1 | Program Design and Technique for Speed and Agility Training (Teil 1/3) | 1398–1437 | [`L-A03_20-1_Program-Design-and-Technique-for-Speed-and-Agility-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_20-1_Program-Design-and-Technique-for-Speed-and-Agility-Training.pdf) |
| 20-2 | Program Design and Technique for Speed and Agility Training (Teil 2/3) | 1438–1472 | [`L-A03_20-2_Program-Design-and-Technique-for-Speed-and-Agility-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_20-2_Program-Design-and-Technique-for-Speed-and-Agility-Training.pdf) |
| 20-3 | Program Design and Technique for Speed and Agility Training (Teil 3/3) | 1473–1511 | [`L-A03_20-3_Program-Design-and-Technique-for-Speed-and-Agility-Training.pdf`](uebergreifend/L-A03_kapitel/L-A03_20-3_Program-Design-and-Technique-for-Speed-and-Agility-Training.pdf) |
| 21-1 | Program Design and Technique for Aerobic Endurance and Metabolic Training (Teil 1/2) | 1512–1542 | [`L-A03_21-1_Program-Design-and-Technique-for-Aerobic-Endurance-and.pdf`](uebergreifend/L-A03_kapitel/L-A03_21-1_Program-Design-and-Technique-for-Aerobic-Endurance-and.pdf) |
| 21-2 | Program Design and Technique for Aerobic Endurance and Metabolic Training (Teil 2/2) | 1543–1578 | [`L-A03_21-2_Program-Design-and-Technique-for-Aerobic-Endurance-and.pdf`](uebergreifend/L-A03_kapitel/L-A03_21-2_Program-Design-and-Technique-for-Aerobic-Endurance-and.pdf) |
| 22 | Periodization | 1579–1629 | [`L-A03_22_Periodization.pdf`](uebergreifend/L-A03_kapitel/L-A03_22_Periodization.pdf) |
| 23 | Rehabilitation, Reconditioning, and Medical Issues | 1630–1678 | [`L-A03_23_Rehabilitation-Reconditioning-and-Medical-Issues.pdf`](uebergreifend/L-A03_kapitel/L-A03_23_Rehabilitation-Reconditioning-and-Medical-Issues.pdf) |
| 24 | Overreaching, Overtraining, and Recovery | 1679–1726 | [`L-A03_24_Overreaching-Overtraining-and-Recovery.pdf`](uebergreifend/L-A03_kapitel/L-A03_24_Overreaching-Overtraining-and-Recovery.pdf) |
| 25 | Facility Design, Layout, and Organization | 1727–1774 | [`L-A03_25_Facility-Design-Layout-and-Organization.pdf`](uebergreifend/L-A03_kapitel/L-A03_25_Facility-Design-Layout-and-Organization.pdf) |
| 26 | Facility Policies, Procedures, and Legal Issues | 1775–1819 | [`L-A03_26_Facility-Policies-Procedures-and-Legal-Issues.pdf`](uebergreifend/L-A03_kapitel/L-A03_26_Facility-Policies-Procedures-and-Legal-Issues.pdf) |
| 90 | Answers to Study Questions | 1820–1823 | [`L-A03_90_Answers-to-Study-Questions.pdf`](uebergreifend/L-A03_kapitel/L-A03_90_Answers-to-Study-Questions.pdf) |
| 91 | Index and Contributors | 1824–1876 | [`L-A03_91_Index-and-Contributors.pdf`](uebergreifend/L-A03_kapitel/L-A03_91_Index-and-Contributors.pdf) |

### L-A01 Kenney – Physiology of Sport and Exercise (7. Aufl.) – `uebergreifend/L-A01_kapitel/`

31 Dateien, 1379 PDF-Seiten.

| Nr. | Titel | PDF-Seiten | Datei |
|---|---|---|---|
| 00a | Vorspann | 1–34 | [`L-A01_00a_Vorspann.pdf`](uebergreifend/L-A01_kapitel/L-A01_00a_Vorspann.pdf) |
| 00b | Introduction: An Introduction to Exercise and Sport Physiology | 35–93 | [`L-A01_00b_Introduction-An-Introduction-to-Exercise-and-Sport.pdf`](uebergreifend/L-A01_kapitel/L-A01_00b_Introduction-An-Introduction-to-Exercise-and-Sport.pdf) |
| 01 | Structure and Function of Exercising Muscle | 94–143 | [`L-A01_01_Structure-and-Function-of-Exercising-Muscle.pdf`](uebergreifend/L-A01_kapitel/L-A01_01_Structure-and-Function-of-Exercising-Muscle.pdf) |
| 02 | Fuel for Exercise: Bioenergetics and Muscle Metabolism | 144–189 | [`L-A01_02_Fuel-for-Exercise-Bioenergetics-and-Muscle-Metabolism.pdf`](uebergreifend/L-A01_kapitel/L-A01_02_Fuel-for-Exercise-Bioenergetics-and-Muscle-Metabolism.pdf) |
| 03 | Neural Control of Exercising Muscle | 190–232 | [`L-A01_03_Neural-Control-of-Exercising-Muscle.pdf`](uebergreifend/L-A01_kapitel/L-A01_03_Neural-Control-of-Exercising-Muscle.pdf) |
| 04 | Hormonal Control During Exercise | 233–280 | [`L-A01_04_Hormonal-Control-During-Exercise.pdf`](uebergreifend/L-A01_kapitel/L-A01_04_Hormonal-Control-During-Exercise.pdf) |
| 05-1 | Energy Expenditure, Fatigue, and Muscle Soreness (Teil 1/2) | 281–311 | [`L-A01_05-1_Energy-Expenditure-Fatigue-and-Muscle-Soreness.pdf`](uebergreifend/L-A01_kapitel/L-A01_05-1_Energy-Expenditure-Fatigue-and-Muscle-Soreness.pdf) |
| 05-2 | Energy Expenditure, Fatigue, and Muscle Soreness (Teil 2/2) | 312–348 | [`L-A01_05-2_Energy-Expenditure-Fatigue-and-Muscle-Soreness.pdf`](uebergreifend/L-A01_kapitel/L-A01_05-2_Energy-Expenditure-Fatigue-and-Muscle-Soreness.pdf) |
| 06 | The Cardiovascular System and Its Control | 349–403 | [`L-A01_06_The-Cardiovascular-System-and-Its-Control.pdf`](uebergreifend/L-A01_kapitel/L-A01_06_The-Cardiovascular-System-and-Its-Control.pdf) |
| 07 | The Respiratory System and Its Regulation | 404–446 | [`L-A01_07_The-Respiratory-System-and-Its-Regulation.pdf`](uebergreifend/L-A01_kapitel/L-A01_07_The-Respiratory-System-and-Its-Regulation.pdf) |
| 08 | Cardiorespiratory Responses to Acute Exercise | 447–501 | [`L-A01_08_Cardiorespiratory-Responses-to-Acute-Exercise.pdf`](uebergreifend/L-A01_kapitel/L-A01_08_Cardiorespiratory-Responses-to-Acute-Exercise.pdf) |
| 09 | Principles of Exercise Training | 502–546 | [`L-A01_09_Principles-of-Exercise-Training.pdf`](uebergreifend/L-A01_kapitel/L-A01_09_Principles-of-Exercise-Training.pdf) |
| 10 | Adaptations to Resistance Training | 547–586 | [`L-A01_10_Adaptations-to-Resistance-Training.pdf`](uebergreifend/L-A01_kapitel/L-A01_10_Adaptations-to-Resistance-Training.pdf) |
| 11-1 | Adaptations to Aerobic and Anaerobic Training (Teil 1/2) | 587–620 | [`L-A01_11-1_Adaptations-to-Aerobic-and-Anaerobic-Training.pdf`](uebergreifend/L-A01_kapitel/L-A01_11-1_Adaptations-to-Aerobic-and-Anaerobic-Training.pdf) |
| 11-2 | Adaptations to Aerobic and Anaerobic Training (Teil 2/2) | 621–655 | [`L-A01_11-2_Adaptations-to-Aerobic-and-Anaerobic-Training.pdf`](uebergreifend/L-A01_kapitel/L-A01_11-2_Adaptations-to-Aerobic-and-Anaerobic-Training.pdf) |
| 12-1 | Exercise in Hot and Cold Environments (Teil 1/2) | 656–684 | [`L-A01_12-1_Exercise-in-Hot-and-Cold-Environments.pdf`](uebergreifend/L-A01_kapitel/L-A01_12-1_Exercise-in-Hot-and-Cold-Environments.pdf) |
| 12-2 | Exercise in Hot and Cold Environments (Teil 2/2) | 685–722 | [`L-A01_12-2_Exercise-in-Hot-and-Cold-Environments.pdf`](uebergreifend/L-A01_kapitel/L-A01_12-2_Exercise-in-Hot-and-Cold-Environments.pdf) |
| 13 | Exercise at Altitude | 723–767 | [`L-A01_13_Exercise-at-Altitude.pdf`](uebergreifend/L-A01_kapitel/L-A01_13_Exercise-at-Altitude.pdf) |
| 14 | Training for Sport | 768–819 | [`L-A01_14_Training-for-Sport.pdf`](uebergreifend/L-A01_kapitel/L-A01_14_Training-for-Sport.pdf) |
| 15-1 | Body Composition and Nutrition for Sport (Teil 1/2) | 820–861 | [`L-A01_15-1_Body-Composition-and-Nutrition-for-Sport.pdf`](uebergreifend/L-A01_kapitel/L-A01_15-1_Body-Composition-and-Nutrition-for-Sport.pdf) |
| 15-2 | Body Composition and Nutrition for Sport (Teil 2/2) | 862–902 | [`L-A01_15-2_Body-Composition-and-Nutrition-for-Sport.pdf`](uebergreifend/L-A01_kapitel/L-A01_15-2_Body-Composition-and-Nutrition-for-Sport.pdf) |
| 16 | Ergogenic Aids in Sport | 903–960 | [`L-A01_16_Ergogenic-Aids-in-Sport.pdf`](uebergreifend/L-A01_kapitel/L-A01_16_Ergogenic-Aids-in-Sport.pdf) |
| 17 | Children and Adolescents in Sport and Exercise | 961–1005 | [`L-A01_17_Children-and-Adolescents-in-Sport-and-Exercise.pdf`](uebergreifend/L-A01_kapitel/L-A01_17_Children-and-Adolescents-in-Sport-and-Exercise.pdf) |
| 18 | Aging in Sport and Exercise | 1006–1055 | [`L-A01_18_Aging-in-Sport-and-Exercise.pdf`](uebergreifend/L-A01_kapitel/L-A01_18_Aging-in-Sport-and-Exercise.pdf) |
| 19 | Sex Differences in Sport and Exercise | 1056–1103 | [`L-A01_19_Sex-Differences-in-Sport-and-Exercise.pdf`](uebergreifend/L-A01_kapitel/L-A01_19_Sex-Differences-in-Sport-and-Exercise.pdf) |
| 20 | Prescription of Exercise for Health and Fitness | 1104–1148 | [`L-A01_20_Prescription-of-Exercise-for-Health-and-Fitness.pdf`](uebergreifend/L-A01_kapitel/L-A01_20_Prescription-of-Exercise-for-Health-and-Fitness.pdf) |
| 21 | Cardiovascular Disease and Physical Activity | 1149–1197 | [`L-A01_21_Cardiovascular-Disease-and-Physical-Activity.pdf`](uebergreifend/L-A01_kapitel/L-A01_21_Cardiovascular-Disease-and-Physical-Activity.pdf) |
| 22 | Obesity, Diabetes, and Physical Activity | 1198–1247 | [`L-A01_22_Obesity-Diabetes-and-Physical-Activity.pdf`](uebergreifend/L-A01_kapitel/L-A01_22_Obesity-Diabetes-and-Physical-Activity.pdf) |
| 90 | Glossary | 1248–1277 | [`L-A01_90_Glossary.pdf`](uebergreifend/L-A01_kapitel/L-A01_90_Glossary.pdf) |
| 91 | References | 1278–1321 | [`L-A01_91_References.pdf`](uebergreifend/L-A01_kapitel/L-A01_91_References.pdf) |
| 92 | Index | 1322–1379 | [`L-A01_92_Index.pdf`](uebergreifend/L-A01_kapitel/L-A01_92_Index.pdf) |

### L-A02 Ferrauti/Wiewelhove – Trainingswissenschaft für die Sportpraxis (2. Aufl.) – `uebergreifend/L-A02_kapitel/`

25 Dateien, 911 PDF-Seiten.

| Nr. | Titel | PDF-Seiten | Datei |
|---|---|---|---|
| 00 | Vorspann | 1–17 | [`L-A02_00_Vorspann.pdf`](uebergreifend/L-A02_kapitel/L-A02_00_Vorspann.pdf) |
| 01 | Aufgaben und Inhalte der Trainingswissenschaft | 18–44 | [`L-A02_01_Aufgaben-und-Inhalte-der-Trainingswissenschaft.pdf`](uebergreifend/L-A02_kapitel/L-A02_01_Aufgaben-und-Inhalte-der-Trainingswissenschaft.pdf) |
| 02 | Grundlagenwissen zum sportlichen Training | 45–95 | [`L-A02_02_Grundlagenwissen-zum-sportlichen-Training.pdf`](uebergreifend/L-A02_kapitel/L-A02_02_Grundlagenwissen-zum-sportlichen-Training.pdf) |
| 03-1 | Leistungssteuerung (Teil 1/3) | 96–140 | [`L-A02_03-1_Leistungssteuerung.pdf`](uebergreifend/L-A02_kapitel/L-A02_03-1_Leistungssteuerung.pdf) |
| 03-2 | Leistungssteuerung (Teil 2/3) | 141–183 | [`L-A02_03-2_Leistungssteuerung.pdf`](uebergreifend/L-A02_kapitel/L-A02_03-2_Leistungssteuerung.pdf) |
| 03-3 | Leistungssteuerung (Teil 3/3) | 184–226 | [`L-A02_03-3_Leistungssteuerung.pdf`](uebergreifend/L-A02_kapitel/L-A02_03-3_Leistungssteuerung.pdf) |
| 04-1 | Krafttraining (Teil 1/2) | 227–267 | [`L-A02_04-1_Krafttraining.pdf`](uebergreifend/L-A02_kapitel/L-A02_04-1_Krafttraining.pdf) |
| 04-2 | Krafttraining (Teil 2/2) | 268–307 | [`L-A02_04-2_Krafttraining.pdf`](uebergreifend/L-A02_kapitel/L-A02_04-2_Krafttraining.pdf) |
| 05-1 | Schnelligkeitstraining (Teil 1/2) | 308–342 | [`L-A02_05-1_Schnelligkeitstraining.pdf`](uebergreifend/L-A02_kapitel/L-A02_05-1_Schnelligkeitstraining.pdf) |
| 05-2 | Schnelligkeitstraining (Teil 2/2) | 343–380 | [`L-A02_05-2_Schnelligkeitstraining.pdf`](uebergreifend/L-A02_kapitel/L-A02_05-2_Schnelligkeitstraining.pdf) |
| 06 | Beweglichkeitstraining | 381–407 | [`L-A02_06_Beweglichkeitstraining.pdf`](uebergreifend/L-A02_kapitel/L-A02_06_Beweglichkeitstraining.pdf) |
| 07-1 | Ausdauertraining (Teil 1/2) | 408–445 | [`L-A02_07-1_Ausdauertraining.pdf`](uebergreifend/L-A02_kapitel/L-A02_07-1_Ausdauertraining.pdf) |
| 07-2 | Ausdauertraining (Teil 2/2) | 446–482 | [`L-A02_07-2_Ausdauertraining.pdf`](uebergreifend/L-A02_kapitel/L-A02_07-2_Ausdauertraining.pdf) |
| 08 | Techniktraining | 483–531 | [`L-A02_08_Techniktraining.pdf`](uebergreifend/L-A02_kapitel/L-A02_08_Techniktraining.pdf) |
| 09-1 | Regenerationsmanagement und Ernährung (Teil 1/2) | 532–563 | [`L-A02_09-1_Regenerationsmanagement-und-Ernaehrung.pdf`](uebergreifend/L-A02_kapitel/L-A02_09-1_Regenerationsmanagement-und-Ernaehrung.pdf) |
| 09-2 | Regenerationsmanagement und Ernährung (Teil 2/2) | 564–594 | [`L-A02_09-2_Regenerationsmanagement-und-Ernaehrung.pdf`](uebergreifend/L-A02_kapitel/L-A02_09-2_Regenerationsmanagement-und-Ernaehrung.pdf) |
| 10 | Training im Kindes- und Jugendalter | 595–647 | [`L-A02_10_Training-im-Kindes-und-Jugendalter.pdf`](uebergreifend/L-A02_kapitel/L-A02_10_Training-im-Kindes-und-Jugendalter.pdf) |
| 11-1 | Training mit Frauen (Teil 1/2) | 648–684 | [`L-A02_11-1_Training-mit-Frauen.pdf`](uebergreifend/L-A02_kapitel/L-A02_11-1_Training-mit-Frauen.pdf) |
| 11-2 | Training mit Frauen (Teil 2/2) | 685–719 | [`L-A02_11-2_Training-mit-Frauen.pdf`](uebergreifend/L-A02_kapitel/L-A02_11-2_Training-mit-Frauen.pdf) |
| 12 | Training im mittleren und höheren Lebensalter | 720–761 | [`L-A02_12_Training-im-mittleren-und-hoeheren-Lebensalter.pdf`](uebergreifend/L-A02_kapitel/L-A02_12_Training-im-mittleren-und-hoeheren-Lebensalter.pdf) |
| 13 | Trainingswissenschaft in den Ausdauersportarten | 762–784 | [`L-A02_13_Trainingswissenschaft-in-den-Ausdauersportarten.pdf`](uebergreifend/L-A02_kapitel/L-A02_13_Trainingswissenschaft-in-den-Ausdauersportarten.pdf) |
| 14-1 | Trainingswissenschaft in den Mannschaftssportarten (Teil 1/2) | 785–821 | [`L-A02_14-1_Trainingswissenschaft-in-den-Mannschaftssportarten.pdf`](uebergreifend/L-A02_kapitel/L-A02_14-1_Trainingswissenschaft-in-den-Mannschaftssportarten.pdf) |
| 14-2 | Trainingswissenschaft in den Mannschaftssportarten (Teil 2/2) | 822–860 | [`L-A02_14-2_Trainingswissenschaft-in-den-Mannschaftssportarten.pdf`](uebergreifend/L-A02_kapitel/L-A02_14-2_Trainingswissenschaft-in-den-Mannschaftssportarten.pdf) |
| 15 | Trainingswissenschaft in den Rückschlagsportarten | 861–900 | [`L-A02_15_Trainingswissenschaft-in-den-Rueckschlagsportarten.pdf`](uebergreifend/L-A02_kapitel/L-A02_15_Trainingswissenschaft-in-den-Rueckschlagsportarten.pdf) |
| 90 | Serviceteil | 901–911 | [`L-A02_90_Serviceteil.pdf`](uebergreifend/L-A02_kapitel/L-A02_90_Serviceteil.pdf) |

### L-T3-06 Climbing Medicine – `t3-klettern/L-T3-06_kapitel/`

24 Dateien, 319 PDF-Seiten.

| Nr. | Titel | PDF-Seiten | Datei |
|---|---|---|---|
| 00 | Vorspann | 1–11 | [`L-T3-06_00_Vorspann.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_00_Vorspann.pdf) |
| 01 | Introduction | 12–20 | [`L-T3-06_01_Introduction.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_01_Introduction.pdf) |
| 02 | Injury Statistics | 21–34 | [`L-T3-06_02_Injury-Statistics.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_02_Injury-Statistics.pdf) |
| 03 | Anatomy and Biomechanics of the Hand | 35–48 | [`L-T3-06_03_Anatomy-and-Biomechanics-of-the-Hand.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_03_Anatomy-and-Biomechanics-of-the-Hand.pdf) |
| 04 | Historical Development of a Physiological Model for Rock Climbing Performance | 49–60 | [`L-T3-06_04_Historical-Development-of-a-Physiological-Model-for-Rock.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_04_Historical-Development-of-a-Physiological-Model-for-Rock.pdf) |
| 05 | Imaging of Climbing Injuries | 61–71 | [`L-T3-06_05_Imaging-of-Climbing-Injuries.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_05_Imaging-of-Climbing-Injuries.pdf) |
| 06 | Hand and Fingers | 72–120 | [`L-T3-06_06_Hand-and-Fingers.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_06_Hand-and-Fingers.pdf) |
| 07 | Wrist Injuries | 121–131 | [`L-T3-06_07_Wrist-Injuries.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_07_Wrist-Injuries.pdf) |
| 08 | Elbow and Forearm | 132–142 | [`L-T3-06_08_Elbow-and-Forearm.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_08_Elbow-and-Forearm.pdf) |
| 09 | Shoulder Injuries | 143–152 | [`L-T3-06_09_Shoulder-Injuries.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_09_Shoulder-Injuries.pdf) |
| 10 | Foot and Ankle | 153–165 | [`L-T3-06_10_Foot-and-Ankle.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_10_Foot-and-Ankle.pdf) |
| 11 | Hip and Knee Injuries | 166–173 | [`L-T3-06_11_Hip-and-Knee-Injuries.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_11_Hip-and-Knee-Injuries.pdf) |
| 12 | The Spine | 174–186 | [`L-T3-06_12_The-Spine.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_12_The-Spine.pdf) |
| 13 | Long-Term Effects of Intensive Rock Climbing to the Hand and Fingers | 187–200 | [`L-T3-06_13_Long-Term-Effects-of-Intensive-Rock-Climbing-to-the-Hand.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_13_Long-Term-Effects-of-Intensive-Rock-Climbing-to-the-Hand.pdf) |
| 14 | Pediatric Aspects in Young Rock Climbers | 201–206 | [`L-T3-06_14_Pediatric-Aspects-in-Young-Rock-Climbers.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_14_Pediatric-Aspects-in-Young-Rock-Climbers.pdf) |
| 15 | Climbing in Older Athletes | 207–211 | [`L-T3-06_15_Climbing-in-Older-Athletes.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_15_Climbing-in-Older-Athletes.pdf) |
| 16 | Anorexia Athletica and Relative Energy Deficiency | 212–217 | [`L-T3-06_16_Anorexia-Athletica-and-Relative-Energy-Deficiency.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_16_Anorexia-Athletica-and-Relative-Energy-Deficiency.pdf) |
| 17 | Sport Climbing with Pre-existing Medical Conditions | 218–234 | [`L-T3-06_17_Sport-Climbing-with-Pre-existing-Medical-Conditions.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_17_Sport-Climbing-with-Pre-existing-Medical-Conditions.pdf) |
| 18 | Sport Climbing During Pregnancy | 235–243 | [`L-T3-06_18_Sport-Climbing-During-Pregnancy.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_18_Sport-Climbing-During-Pregnancy.pdf) |
| 19 | Sports-Medical Supervision of Competition Climbers and Climbing Competitions | 244–252 | [`L-T3-06_19_Sports-Medical-Supervision-of-Competition-Climbers-and.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_19_Sports-Medical-Supervision-of-Competition-Climbers-and.pdf) |
| 20 | Climbing Injury Rehabilitation | 253–277 | [`L-T3-06_20_Climbing-Injury-Rehabilitation.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_20_Climbing-Injury-Rehabilitation.pdf) |
| 21 | Injury Prevention | 278–294 | [`L-T3-06_21_Injury-Prevention.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_21_Injury-Prevention.pdf) |
| 22 | Taping | 295–313 | [`L-T3-06_22_Taping.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_22_Taping.pdf) |
| 23 | Future Aspects: Climbing in the Olympics | 314–319 | [`L-T3-06_23_Future-Aspects-Climbing-in-the-Olympics.pdf`](t3-klettern/L-T3-06_kapitel/L-T3-06_23_Future-Aspects-Climbing-in-the-Olympics.pdf) |

### L-T3-09 Hörst – Training for Climbing (3. Aufl.) – `t3-klettern/L-T3-09_kapitel/`

17 Dateien, 356 PDF-Seiten. Scan: Druckseiten aus dem Seitenversatz berechnet (Vorspann ohne Druckseiten).

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann, Foreword und Introduction | 1–16 | – | [`L-T3-09_00_Vorspann-Foreword-und-Introduction.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_00_Vorspann-Foreword-und-Introduction.pdf) |
| 01 | An Overview of Training for Climbing | 17–34 | 1–18 | [`L-T3-09_01_An-Overview-of-Training-for-Climbing.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_01_An-Overview-of-Training-for-Climbing.pdf) |
| 02 | Self-Assessment and Goal Setting | 35–46 | 19–30 | [`L-T3-09_02_Self-Assessment-and-Goal-Setting.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_02_Self-Assessment-and-Goal-Setting.pdf) |
| 03 | Mental Training | 47–72 | 31–56 | [`L-T3-09_03_Mental-Training.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_03_Mental-Training.pdf) |
| 04 | Training Technique and Skill | 73–104 | 57–88 | [`L-T3-09_04_Training-Technique-and-Skill.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_04_Training-Technique-and-Skill.pdf) |
| 05 | The Physiology of Climbing | 105–132 | 89–116 | [`L-T3-09_05_The-Physiology-of-Climbing.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_05_The-Physiology-of-Climbing.pdf) |
| 06 | Mobility, Stability, Antagonist Training | 133–162 | 117–146 | [`L-T3-09_06_Mobility-Stability-Antagonist-Training.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_06_Mobility-Stability-Antagonist-Training.pdf) |
| 07 | Core, Legs, and Aerobic Training | 163–180 | 147–164 | [`L-T3-09_07_Core-Legs-and-Aerobic-Training.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_07_Core-Legs-and-Aerobic-Training.pdf) |
| 08 | Finger Training for Strength and Endurance | 181–214 | 165–198 | [`L-T3-09_08_Finger-Training-for-Strength-and-Endurance.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_08_Finger-Training-for-Strength-and-Endurance.pdf) |
| 09 | Pull-Muscle and Power Training | 215–234 | 199–218 | [`L-T3-09_09_Pull-Muscle-and-Power-Training.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_09_Pull-Muscle-and-Power-Training.pdf) |
| 10 | Designing Your Training Program | 235–262 | 219–246 | [`L-T3-09_10_Designing-Your-Training-Program.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_10_Designing-Your-Training-Program.pdf) |
| 11 | Performance Nutrition | 263–278 | 247–262 | [`L-T3-09_11_Performance-Nutrition.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_11_Performance-Nutrition.pdf) |
| 12 | Accelerating Recovery | 279–294 | 263–278 | [`L-T3-09_12_Accelerating-Recovery.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_12_Accelerating-Recovery.pdf) |
| 13 | Injury Treatment and Prevention | 295–320 | 279–304 | [`L-T3-09_13_Injury-Treatment-and-Prevention.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_13_Injury-Treatment-and-Prevention.pdf) |
| 90 | Afterword and Appendices A-C | 321–330 | 305–314 | [`L-T3-09_90_Afterword-and-Appendices-A-C.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_90_Afterword-and-Appendices-A-C.pdf) |
| 91 | Glossary, Suggested Reading, References | 331–342 | 315–326 | [`L-T3-09_91_Glossary-Suggested-Reading-References.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_91_Glossary-Suggested-Reading-References.pdf) |
| 92 | Index and About the Author | 343–356 | 327–340 | [`L-T3-09_92_Index-and-About-the-Author.pdf`](t3-klettern/L-T3-09_kapitel/L-T3-09_92_Index-and-About-the-Author.pdf) |

### L-T2-03 Concurrent Aerobic and Strength Training – `t2-kraft/L-T2-03_kapitel/`

28 Dateien, 408 PDF-Seiten.

| Nr. | Titel | PDF-Seiten | Datei |
|---|---|---|---|
| 00 | Vorspann | 1–9 | [`L-T2-03_00_Vorspann.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_00_Vorspann.pdf) |
| 01 | A Brief Historical Overview on the Science of Concurrent Aerobic and Strength Training | 10–15 | [`L-T2-03_01_A-Brief-Historical-Overview-on-the-Science-of-Concurrent.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_01_A-Brief-Historical-Overview-on-the-Science-of-Concurrent.pdf) |
| 02 | The Functional Genome in Physical Exercise | 16–26 | [`L-T2-03_02_The-Functional-Genome-in-Physical-Exercise.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_02_The-Functional-Genome-in-Physical-Exercise.pdf) |
| 03 | Molecular and Physiological Adaptations to Endurance Training | 27–42 | [`L-T2-03_03_Molecular-and-Physiological-Adaptations-to-Endurance.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_03_Molecular-and-Physiological-Adaptations-to-Endurance.pdf) |
| 04 | Neural Adaptations to Endurance Training | 43–58 | [`L-T2-03_04_Neural-Adaptations-to-Endurance-Training.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_04_Neural-Adaptations-to-Endurance-Training.pdf) |
| 05 | Physiological and Molecular Adaptations to Strength Training | 59–81 | [`L-T2-03_05_Physiological-and-Molecular-Adaptations-to-Strength-Training.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_05_Physiological-and-Molecular-Adaptations-to-Strength-Training.pdf) |
| 06 | Neural Adaptations to Strength Training | 82–93 | [`L-T2-03_06_Neural-Adaptations-to-Strength-Training.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_06_Neural-Adaptations-to-Strength-Training.pdf) |
| 07 | Proposed Mechanisms Underlying the Interference Effect | 94–103 | [`L-T2-03_07_Proposed-Mechanisms-Underlying-the-Interference-Effect.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_07_Proposed-Mechanisms-Underlying-the-Interference-Effect.pdf) |
| 08 | Molecular Adaptations to Concurrent Strength and Endurance Training | 104–128 | [`L-T2-03_08_Molecular-Adaptations-to-Concurrent-Strength-and-Endurance.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_08_Molecular-Adaptations-to-Concurrent-Strength-and-Endurance.pdf) |
| 09 | Effects of Endurance-, Strength-, and Concurrent Training on Cytokines and Inflammation | 129–142 | [`L-T2-03_09_Effects-of-Endurance-Strength-and-Concurrent-Training-on.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_09_Effects-of-Endurance-Strength-and-Concurrent-Training-on.pdf) |
| 10 | Immediate Effects of Endurance Exercise on Subsequent Strength Performance | 143–158 | [`L-T2-03_10_Immediate-Effects-of-Endurance-Exercise-on-Subsequent.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_10_Immediate-Effects-of-Endurance-Exercise-on-Subsequent.pdf) |
| 11 | Acute Effects of Strength Exercise on Subsequent Endurance Performance | 159–169 | [`L-T2-03_11_Acute-Effects-of-Strength-Exercise-on-Subsequent-Endurance.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_11_Acute-Effects-of-Strength-Exercise-on-Subsequent-Endurance.pdf) |
| 12 | Long-Term Effects of Supplementary Aerobic Training on Muscle Hypertrophy | 170–183 | [`L-T2-03_12_Long-Term-Effects-of-Supplementary-Aerobic-Training-on.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_12_Long-Term-Effects-of-Supplementary-Aerobic-Training-on.pdf) |
| 13 | Methodological Considerations for Concurrent Training | 184–198 | [`L-T2-03_13_Methodological-Considerations-for-Concurrent-Training.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_13_Methodological-Considerations-for-Concurrent-Training.pdf) |
| 14 | Effects of the Concurrent Training Mode on Physiological Adaptations and Performance | 199–213 | [`L-T2-03_14_Effects-of-the-Concurrent-Training-Mode-on-Physiological.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_14_Effects-of-the-Concurrent-Training-Mode-on-Physiological.pdf) |
| 15 | Recovery Strategies to Optimise Adaptations to Concurrent Aerobic and Strength Training | 214–228 | [`L-T2-03_15_Recovery-Strategies-to-Optimise-Adaptations-to-Concurrent.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_15_Recovery-Strategies-to-Optimise-Adaptations-to-Concurrent.pdf) |
| 16 | Nutritional Considerations for Concurrent Training | 229–252 | [`L-T2-03_16_Nutritional-Considerations-for-Concurrent-Training.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_16_Nutritional-Considerations-for-Concurrent-Training.pdf) |
| 17 | Concurrent Training in Children and Adolescents | 253–274 | [`L-T2-03_17_Concurrent-Training-in-Children-and-Adolescents.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_17_Concurrent-Training-in-Children-and-Adolescents.pdf) |
| 18 | Concurrent Training in Elderly | 275–289 | [`L-T2-03_18_Concurrent-Training-in-Elderly.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_18_Concurrent-Training-in-Elderly.pdf) |
| 19 | Concurrent Aerobic and Strength Training for Body Composition and Health | 290–304 | [`L-T2-03_19_Concurrent-Aerobic-and-Strength-Training-for-Body.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_19_Concurrent-Aerobic-and-Strength-Training-for-Body.pdf) |
| 20 | Sex Differences in Concurrent Aerobic and Strength Training | 305–317 | [`L-T2-03_20_Sex-Differences-in-Concurrent-Aerobic-and-Strength-Training.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_20_Sex-Differences-in-Concurrent-Aerobic-and-Strength-Training.pdf) |
| 21 | Long-Term Effects of Strength Training on Aerobic Capacity and Endurance Performance | 318–325 | [`L-T2-03_21_Long-Term-Effects-of-Strength-Training-on-Aerobic-Capacity.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_21_Long-Term-Effects-of-Strength-Training-on-Aerobic-Capacity.pdf) |
| 22 | Strength Training for Endurance Cyclists | 326–333 | [`L-T2-03_22_Strength-Training-for-Endurance-Cyclists.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_22_Strength-Training-for-Endurance-Cyclists.pdf) |
| 23 | Strength Training for Endurance Runners | 334–348 | [`L-T2-03_23_Strength-Training-for-Endurance-Runners.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_23_Strength-Training-for-Endurance-Runners.pdf) |
| 24 | Strength Training for Cross-Country Skiers | 349–360 | [`L-T2-03_24_Strength-Training-for-Cross-Country-Skiers.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_24_Strength-Training-for-Cross-Country-Skiers.pdf) |
| 25 | Strength Training for Swimmers | 361–378 | [`L-T2-03_25_Strength-Training-for-Swimmers.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_25_Strength-Training-for-Swimmers.pdf) |
| 26 | General Aspects of Concurrent Aerobic and Strength Training for Performance in Team Sports | 379–388 | [`L-T2-03_26_General-Aspects-of-Concurrent-Aerobic-and-Strength-Training.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_26_General-Aspects-of-Concurrent-Aerobic-and-Strength-Training.pdf) |
| 27 | Concurrent Aerobic and Strength Training for Performance in Soccer | 389–408 | [`L-T2-03_27_Concurrent-Aerobic-and-Strength-Training-for-Performance-in.pdf`](t2-kraft/L-T2-03_kapitel/L-T2-03_27_Concurrent-Aerobic-and-Strength-Training-for-Performance-in.pdf) |

### L-T1-07 Laursen/Buchheit – Science and Application of HIIT – `t1-ausdauer/L-T1-07_kapitel/`

34 Dateien, 673 PDF-Seiten.

| Nr. | Titel | PDF-Seiten | Datei |
|---|---|---|---|
| 00 | Vorspann | 1–7 | [`L-T1-07_00_Vorspann.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_00_Vorspann.pdf) |
| 01 | Genesis and Evolution of High- Intensity Interval Training | 8–23 | [`L-T1-07_01_Genesis-and-Evolution-of-High-Intensity-Interval-Training.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_01_Genesis-and-Evolution-of-High-Intensity-Interval-Training.pdf) |
| 02 | Traditional Methods of HIIT Programming | 24–39 | [`L-T1-07_02_Traditional-Methods-of-HIIT-Programming.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_02_Traditional-Methods-of-HIIT-Programming.pdf) |
| 03 | Physiological Targets of HIIT | 40–57 | [`L-T1-07_03_Physiological-Targets-of-HIIT.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_03_Physiological-Targets-of-HIIT.pdf) |
| 04 | Manipulating HIIT Variables | 58–79 | [`L-T1-07_04_Manipulating-HIIT-Variables.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_04_Manipulating-HIIT-Variables.pdf) |
| 05 | Using HIIT Weapons | 80–125 | [`L-T1-07_05_Using-HIIT-Weapons.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_05_Using-HIIT-Weapons.pdf) |
| 06 | Incorporating HIIT Into a Concurrent Training Program | 126–143 | [`L-T1-07_06_Incorporating-HIIT-Into-a-Concurrent-Training-Program.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_06_Incorporating-HIIT-Into-a-Concurrent-Training-Program.pdf) |
| 07 | HIIT and Its Influence on Stress, Fatigue, and Athlete Health | 144–167 | [`L-T1-07_07_HIIT-and-Its-Influence-on-Stress-Fatigue-and-Athlete-Health.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_07_HIIT-and-Its-Influence-on-Stress-Fatigue-and-Athlete-Health.pdf) |
| 08 | Quantifying Training Load | 168–185 | [`L-T1-07_08_Quantifying-Training-Load.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_08_Quantifying-Training-Load.pdf) |
| 09 | Response to Load | 186–219 | [`L-T1-07_09_Response-to-Load.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_09_Response-to-Load.pdf) |
| 10 | Putting It All Together | 220–231 | [`L-T1-07_10_Putting-It-All-Together.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_10_Putting-It-All-Together.pdf) |
| 11 | Combat Sports | 232–253 | [`L-T1-07_11_Combat-Sports.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_11_Combat-Sports.pdf) |
| 12 | Cross-Country Skiing | 254–267 | [`L-T1-07_12_Cross-Country-Skiing.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_12_Cross-Country-Skiing.pdf) |
| 13 | Middle-Distance Running | 268–289 | [`L-T1-07_13_Middle-Distance-Running.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_13_Middle-Distance-Running.pdf) |
| 14 | Road Running | 290–303 | [`L-T1-07_14_Road-Running.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_14_Road-Running.pdf) |
| 15 | Road Cycling | 304–317 | [`L-T1-07_15_Road-Cycling.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_15_Road-Cycling.pdf) |
| 16 | Rowing | 318–331 | [`L-T1-07_16_Rowing.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_16_Rowing.pdf) |
| 17 | Swimming | 332–353 | [`L-T1-07_17_Swimming.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_17_Swimming.pdf) |
| 18 | Tennis | 354–369 | [`L-T1-07_18_Tennis.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_18_Tennis.pdf) |
| 19 | Triathlon | 370–385 | [`L-T1-07_19_Triathlon.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_19_Triathlon.pdf) |
| 20 | American Football | 386–399 | [`L-T1-07_20_American-Football.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_20_American-Football.pdf) |
| 21 | Australian Football | 400–417 | [`L-T1-07_21_Australian-Football.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_21_Australian-Football.pdf) |
| 22 | Baseball | 418–431 | [`L-T1-07_22_Baseball.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_22_Baseball.pdf) |
| 23 | Basketball | 432–449 | [`L-T1-07_23_Basketball.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_23_Basketball.pdf) |
| 24 | Cricket | 450–461 | [`L-T1-07_24_Cricket.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_24_Cricket.pdf) |
| 25 | Field Hockey | 462–483 | [`L-T1-07_25_Field-Hockey.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_25_Field-Hockey.pdf) |
| 26 | Ice Hockey | 484–501 | [`L-T1-07_26_Ice-Hockey.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_26_Ice-Hockey.pdf) |
| 27 | Handball | 502–517 | [`L-T1-07_27_Handball.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_27_Handball.pdf) |
| 28 | Rugby Union | 518–531 | [`L-T1-07_28_Rugby-Union.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_28_Rugby-Union.pdf) |
| 29 | Rugby Sevens | 532–553 | [`L-T1-07_29_Rugby-Sevens.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_29_Rugby-Sevens.pdf) |
| 30 | Soccer | 554–571 | [`L-T1-07_30_Soccer.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_30_Soccer.pdf) |
| 90-1 | References (Teil 1/2) | 572–612 | [`L-T1-07_90-1_References.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_90-1_References.pdf) |
| 90-2 | References (Teil 2/2) | 613–653 | [`L-T1-07_90-2_References.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_90-2_References.pdf) |
| 91 | Index and Contributors | 654–673 | [`L-T1-07_91_Index-and-Contributors.pdf`](t1-ausdauer/L-T1-07_kapitel/L-T1-07_91_Index-and-Contributors.pdf) |

### L-T1-08 Training for the Uphill Athlete – `t1-ausdauer/L-T1-08_kapitel/`

15 Dateien, 380 PDF-Seiten. Scan: Druckseiten aus dem Seitenversatz berechnet (Vorspann ohne Druckseiten).

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann und Foreword | 1–18 | – | [`L-T1-08_00_Vorspann-und-Foreword.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_00_Vorspann-und-Foreword.pdf) |
| 01 | How to Use This Book | 19–22 | 17–20 | [`L-T1-08_01_How-to-Use-This-Book.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_01_How-to-Use-This-Book.pdf) |
| 02 | The Physiology of Endurance | 23–70 | 21–68 | [`L-T1-08_02_The-Physiology-of-Endurance.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_02_The-Physiology-of-Endurance.pdf) |
| 03 | The Methodologies of Endurance Training | 71–120 | 69–116 | [`L-T1-08_03_The-Methodologies-of-Endurance-Training.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_03_The-Methodologies-of-Endurance-Training.pdf) |
| 04 | Monitoring Your Training | 121–152 | 117–148 | [`L-T1-08_04_Monitoring-Your-Training.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_04_Monitoring-Your-Training.pdf) |
| 05 | The Application Process | 153–192 | 151–190 | [`L-T1-08_05_The-Application-Process.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_05_The-Application-Process.pdf) |
| 06 | Strength Training for the Uphill Athlete | 193–204 | 191–202 | [`L-T1-08_06_Strength-Training-for-the-Uphill-Athlete.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_06_Strength-Training-for-the-Uphill-Athlete.pdf) |
| 07 | General Strength Assessment and Improvement | 205–240 | 203–238 | [`L-T1-08_07_General-Strength-Assessment-and-Improvement.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_07_General-Strength-Assessment-and-Improvement.pdf) |
| 08 | Specific Strength-Training Methods | 241–256 | 239–254 | [`L-T1-08_08_Specific-Strength-Training-Methods.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_08_Specific-Strength-Training-Methods.pdf) |
| 09 | Programming | 257–270 | 255–268 | [`L-T1-08_09_Programming.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_09_Programming.pdf) |
| 10 | Transition Period Training | 271–278 | 269–276 | [`L-T1-08_10_Transition-Period-Training.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_10_Transition-Period-Training.pdf) |
| 11 | Introduction to the Base Period | 279–312 | 277–310 | [`L-T1-08_11_Introduction-to-the-Base-Period.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_11_Introduction-to-the-Base-Period.pdf) |
| 12 | Special Considerations for Skimo and Ski Mountaineering | 313–338 | 311–336 | [`L-T1-08_12_Special-Considerations-for-Skimo-and-Ski-Mountaineering.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_12_Special-Considerations-for-Skimo-and-Ski-Mountaineering.pdf) |
| 13 | Special Considerations for Mountain Running | 339–364 | 337–362 | [`L-T1-08_13_Special-Considerations-for-Mountain-Running.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_13_Special-Considerations-for-Mountain-Running.pdf) |
| 90 | Glossary and Index | 365–380 | 363–378 | [`L-T1-08_90_Glossary-and-Index.pdf`](t1-ausdauer/L-T1-08_kapitel/L-T1-08_90_Glossary-and-Index.pdf) |

### L-T2-04 Overcoming Gravity (2. Aufl.) – `t2-kraft/L-T2-04_kapitel/`

32 Dateien, 600 PDF-Seiten. Scan: Druckseiten aus dem Seitenversatz berechnet (Vorspann ohne Druckseiten).

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann und Introduction | 1–14 | – | [`L-T2-04_00_Vorspann-und-Introduction.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_00_Vorspann-und-Introduction.pdf) |
| 01 | Principles of Bodyweight Training | 15–23 | 1–9 | [`L-T2-04_01_Principles-of-Bodyweight-Training.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_01_Principles-of-Bodyweight-Training.pdf) |
| 02 | Physiology of Strength and Hypertrophy | 24–34 | 10–20 | [`L-T2-04_02_Physiology-of-Strength-and-Hypertrophy.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_02_Physiology-of-Strength-and-Hypertrophy.pdf) |
| 03 | Progression Charts and Goal Setting | 35–48 | 21–34 | [`L-T2-04_03_Progression-Charts-and-Goal-Setting.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_03_Progression-Charts-and-Goal-Setting.pdf) |
| 04 | Structural Balance Considerations | 49–57 | 35–43 | [`L-T2-04_04_Structural-Balance-Considerations.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_04_Structural-Balance-Considerations.pdf) |
| 05 | Intro to Programming, Attributes, Hierarchy of a Routine | 58–72 | 44–58 | [`L-T2-04_05_Intro-to-Programming-Attributes-Hierarchy-of-a-Routine.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_05_Intro-to-Programming-Attributes-Hierarchy-of-a-Routine.pdf) |
| 06 | Population Considerations | 73–82 | 59–68 | [`L-T2-04_06_Population-Considerations.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_06_Population-Considerations.pdf) |
| 07 | Constructing Your Workout Routine | 83–94 | 69–80 | [`L-T2-04_07_Constructing-Your-Workout-Routine.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_07_Constructing-Your-Workout-Routine.pdf) |
| 08 | Warm-up and Skill Work | 95–103 | 81–89 | [`L-T2-04_08_Warm-up-and-Skill-Work.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_08_Warm-up-and-Skill-Work.pdf) |
| 09 | Strength Work | 104–128 | 90–114 | [`L-T2-04_09_Strength-Work.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_09_Strength-Work.pdf) |
| 10 | Methods of Progression | 129–150 | 115–136 | [`L-T2-04_10_Methods-of-Progression.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_10_Methods-of-Progression.pdf) |
| 11 | Prehabilitation, Isolation, Flexibility, Cool Down | 151–164 | 137–150 | [`L-T2-04_11_Prehabilitation-Isolation-Flexibility-Cool-Down.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_11_Prehabilitation-Isolation-Flexibility-Cool-Down.pdf) |
| 12 | Mesocycle Planning | 165–182 | 151–168 | [`L-T2-04_12_Mesocycle-Planning.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_12_Mesocycle-Planning.pdf) |
| 13 | Endurance, Cardio, Cross Training, Hybrid Templates | 183–201 | 169–187 | [`L-T2-04_13_Endurance-Cardio-Cross-Training-Hybrid-Templates.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_13_Endurance-Cardio-Cross-Training-Hybrid-Templates.pdf) |
| 14 | Overreaching and Overtraining | 202–208 | 188–194 | [`L-T2-04_14_Overreaching-and-Overtraining.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_14_Overreaching-and-Overtraining.pdf) |
| 15 | Health and Injury Management | 209–225 | 195–211 | [`L-T2-04_15_Health-and-Injury-Management.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_15_Health-and-Injury-Management.pdf) |
| 16 | Lifestyle Factors | 226–232 | 212–218 | [`L-T2-04_16_Lifestyle-Factors.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_16_Lifestyle-Factors.pdf) |
| 17 | Untrained Beginner | 233–244 | 219–230 | [`L-T2-04_17_Untrained-Beginner.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_17_Untrained-Beginner.pdf) |
| 18 | Trained Beginner | 245–252 | 231–238 | [`L-T2-04_18_Trained-Beginner.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_18_Trained-Beginner.pdf) |
| 19 | Intermediate | 253–264 | 239–250 | [`L-T2-04_19_Intermediate.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_19_Intermediate.pdf) |
| 20 | Advanced | 265–274 | 251–260 | [`L-T2-04_20_Advanced.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_20_Advanced.pdf) |
| 21 | Common Bodyweight Training Injuries | 275–305 | 261–291 | [`L-T2-04_21_Common-Bodyweight-Training-Injuries.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_21_Common-Bodyweight-Training-Injuries.pdf) |
| 22 | Prehabilitation, Mobility, Flexibility Resources | 306–326 | 292–312 | [`L-T2-04_22_Prehabilitation-Mobility-Flexibility-Resources.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_22_Prehabilitation-Mobility-Flexibility-Resources.pdf) |
| 23 | Exercise Technique, Descriptions, Tips | 327–331 | 313–317 | [`L-T2-04_23_Exercise-Technique-Descriptions-Tips.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_23_Exercise-Technique-Descriptions-Tips.pdf) |
| 24-1 | Handstand Variations (Teil 1/2) | 332–361 | 318–347 | [`L-T2-04_24-1_Handstand-Variations.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_24-1_Handstand-Variations.pdf) |
| 24-2 | Handstand Variations (Teil 2/2) | 362–392 | 348–378 | [`L-T2-04_24-2_Handstand-Variations.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_24-2_Handstand-Variations.pdf) |
| 25-1 | Pulling Exercises (Teil 1/2) | 393–431 | 379–417 | [`L-T2-04_25-1_Pulling-Exercises.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_25-1_Pulling-Exercises.pdf) |
| 25-2 | Pulling Exercises (Teil 2/2) | 432–470 | 418–456 | [`L-T2-04_25-2_Pulling-Exercises.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_25-2_Pulling-Exercises.pdf) |
| 26-1 | Pushing Variations (Teil 1/2) | 471–503 | 457–489 | [`L-T2-04_26-1_Pushing-Variations.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_26-1_Pushing-Variations.pdf) |
| 26-2 | Pushing Variations (Teil 2/2) | 504–536 | 490–522 | [`L-T2-04_26-2_Pushing-Variations.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_26-2_Pushing-Variations.pdf) |
| 27 | Multi-Plane Exercises, Core, and Legs | 537–594 | 523–580 | [`L-T2-04_27_Multi-Plane-Exercises-Core-and-Legs.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_27_Multi-Plane-Exercises-Core-and-Legs.pdf) |
| 90 | Resources | 595–600 | 581–586 | [`L-T2-04_90_Resources.pdf`](t2-kraft/L-T2-04_kapitel/L-T2-04_90_Resources.pdf) |

### L-T4-32 Behm – The Science and Physiology of Flexibility and Stretching (2. Aufl.) – `t4-beweglichkeit/L-T4-32_kapitel/`

19 Dateien, 281 PDF-Seiten. Druckseite = PDF-Seite − 15 (Vorspann ohne Druckseiten).

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann | 1–15 | – | [`L-T4-32_00_Vorspann.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_00_Vorspann.pdf) |
| 01 | My Personal Motivation for Stretching | 16–23 | 1–8 | [`L-T4-32_01_My-Personal-Motivation-for-Stretching.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_01_My-Personal-Motivation-for-Stretching.pdf) |
| 02 | History of Stretching | 24–32 | 9–17 | [`L-T4-32_02_History-of-Stretching.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_02_History-of-Stretching.pdf) |
| 03 | Types of Stretching and the Effects on Flexibility | 33–67 | 18–52 | [`L-T4-32_03_Types-of-Stretching-and-the-Effects-on-Flexibility.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_03_Types-of-Stretching-and-the-Effects-on-Flexibility.pdf) |
| 04 | Mechanisms Underlying Acute Changes in Range of Motion | 68–97 | 53–82 | [`L-T4-32_04_Mechanisms-Underlying-Acute-Changes-in-Range-of-Motion.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_04_Mechanisms-Underlying-Acute-Changes-in-Range-of-Motion.pdf) |
| 05 | Stretch Training-Related ROM Changes and Mechanisms | 98–103 | 83–88 | [`L-T4-32_05_Stretch-Training-Related-ROM-Changes-and-Mechanisms.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_05_Stretch-Training-Related-ROM-Changes-and-Mechanisms.pdf) |
| 06 | Global Effects of Stretching | 104–113 | 89–98 | [`L-T4-32_06_Global-Effects-of-Stretching.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_06_Global-Effects-of-Stretching.pdf) |
| 07 | Recommendations for Stretching Prescription | 114–127 | 99–112 | [`L-T4-32_07_Recommendations-for-Stretching-Prescription.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_07_Recommendations-for-Stretching-Prescription.pdf) |
| 08 | Stretching Effects on Injury Reduction and Health | 128–143 | 113–128 | [`L-T4-32_08_Stretching-Effects-on-Injury-Reduction-and-Health.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_08_Stretching-Effects-on-Injury-Reduction-and-Health.pdf) |
| 09 | Does Stretching Affect Performance | 144–175 | 129–160 | [`L-T4-32_09_Does-Stretching-Affect-Performance.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_09_Does-Stretching-Affect-Performance.pdf) |
| 10 | Effect of Stretch Training on Functional Performance | 176–181 | 161–166 | [`L-T4-32_10_Effect-of-Stretch-Training-on-Functional-Performance.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_10_Effect-of-Stretch-Training-on-Functional-Performance.pdf) |
| 11 | Effects of Stretch Training on Muscle Strength and Hypertrophy | 182–189 | 167–174 | [`L-T4-32_11_Effects-of-Stretch-Training-on-Muscle-Strength-and-Hypertrophy.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_11_Effects-of-Stretch-Training-on-Muscle-Strength-and-Hypertrophy.pdf) |
| 12 | Effects of Resistance Training on Range of Motion | 190–205 | 175–190 | [`L-T4-32_12_Effects-of-Resistance-Training-on-Range-of-Motion.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_12_Effects-of-Resistance-Training-on-Range-of-Motion.pdf) |
| 13 | Foam Rolling Effects on Range of Motion and Performance | 206–226 | 191–211 | [`L-T4-32_13_Foam-Rolling-Effects-on-Range-of-Motion-and-Performance.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_13_Foam-Rolling-Effects-on-Range-of-Motion-and-Performance.pdf) |
| 14 | Local Vibration Effects on Range of Motion and Performance | 227–231 | 212–216 | [`L-T4-32_14_Local-Vibration-Effects-on-Range-of-Motion-and-Performance.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_14_Local-Vibration-Effects-on-Range-of-Motion-and-Performance.pdf) |
| 15 | Instrument-Assisted Soft Tissue Mobilization | 232–240 | 217–225 | [`L-T4-32_15_Instrument-Assisted-Soft-Tissue-Mobilization.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_15_Instrument-Assisted-Soft-Tissue-Mobilization.pdf) |
| 16 | Flossing Effects on Range of Motion and Performance | 241–246 | 226–231 | [`L-T4-32_16_Flossing-Effects-on-Range-of-Motion-and-Performance.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_16_Flossing-Effects-on-Range-of-Motion-and-Performance.pdf) |
| 17 | Stretching Exercise Illustration | 247–270 | 232–255 | [`L-T4-32_17_Stretching-Exercise-Illustration.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_17_Stretching-Exercise-Illustration.pdf) |
| 90 | Index | 271–281 | 256–266 | [`L-T4-32_90_Index.pdf`](t4-beweglichkeit/L-T4-32_kapitel/L-T4-32_90_Index.pdf) |

### L-T4-34 Nelson/Kokkonen – Stretching Anatomy (3. Aufl.) – `t4-beweglichkeit/L-T4-34_kapitel/`

13 Dateien, 265 PDF-Seiten. Druckseite = PDF-Seite − 11 (Vorspann ohne Druckseiten).

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann und Preface | 1–11 | – | [`L-T4-34_00_Vorspann-und-Preface.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_00_Vorspann-und-Preface.pdf) |
| 01 | Stretching Fundamentals | 12–19 | 1–8 | [`L-T4-34_01_Stretching-Fundamentals.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_01_Stretching-Fundamentals.pdf) |
| 02 | Feet and Calves | 20–47 | 9–36 | [`L-T4-34_02_Feet-and-Calves.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_02_Feet-and-Calves.pdf) |
| 03 | Knees and Thighs | 48–69 | 37–58 | [`L-T4-34_03_Knees-and-Thighs.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_03_Knees-and-Thighs.pdf) |
| 04 | Hips | 70–91 | 59–80 | [`L-T4-34_04_Hips.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_04_Hips.pdf) |
| 05 | Lower Trunk | 92–117 | 81–106 | [`L-T4-34_05_Lower-Trunk.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_05_Lower-Trunk.pdf) |
| 06 | Arms, Wrists, and Hands | 118–151 | 107–140 | [`L-T4-34_06_Arms-Wrists-and-Hands.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_06_Arms-Wrists-and-Hands.pdf) |
| 07 | Shoulders, Back, and Chest | 152–183 | 141–172 | [`L-T4-34_07_Shoulders-Back-and-Chest.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_07_Shoulders-Back-and-Chest.pdf) |
| 08 | Neck | 184–195 | 173–184 | [`L-T4-34_08_Neck.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_08_Neck.pdf) |
| 09 | Dynamic Stretches | 196–219 | 185–208 | [`L-T4-34_09_Dynamic-Stretches.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_09_Dynamic-Stretches.pdf) |
| 10 | Programs for Daily Mobility and Flexibility | 220–229 | 209–218 | [`L-T4-34_10_Programs-for-Daily-Mobility-and-Flexibility.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_10_Programs-for-Daily-Mobility-and-Flexibility.pdf) |
| 11 | Sport-Specific Stretching Programs | 230–257 | 219–246 | [`L-T4-34_11_Sport-Specific-Stretching-Programs.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_11_Sport-Specific-Stretching-Programs.pdf) |
| 90 | Stretch Finder and About the Authors | 258–265 | 247–254 | [`L-T4-34_90_Stretch-Finder-and-About-the-Authors.pdf`](t4-beweglichkeit/L-T4-34_kapitel/L-T4-34_90_Stretch-Finder-and-About-the-Authors.pdf) |

### L-T3-16 Bechtel – Logical Progression (2. Aufl.) – `t3-klettern/L-T3-16_kapitel/`

12 Dateien, 238 PDF-Seiten. Druckseite = PDF-Seite − 14 (Vorspann mit römischen Seitenzahlen).

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann, Preface und Introduction | 1–13 | – | [`L-T3-16_00_Vorspann-Preface-und-Introduction.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_00_Vorspann-Preface-und-Introduction.pdf) |
| 01 | Philosophy of Training | 14–33 | 1–19 | [`L-T3-16_01_Philosophy-of-Training.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_01_Philosophy-of-Training.pdf) |
| 02 | Periodization and Planning | 34–63 | 20–49 | [`L-T3-16_02_Periodization-and-Planning.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_02_Periodization-and-Planning.pdf) |
| 03 | The Climb Strong Nonlinear Programs | 64–71 | 50–57 | [`L-T3-16_03_The-Climb-Strong-Nonlinear-Programs.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_03_The-Climb-Strong-Nonlinear-Programs.pdf) |
| 04 | Block Programming | 72–97 | 58–83 | [`L-T3-16_04_Block-Programming.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_04_Block-Programming.pdf) |
| 05 | Simple Testing | 98–107 | 84–93 | [`L-T3-16_05_Simple-Testing.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_05_Simple-Testing.pdf) |
| 06-1 | Methods of Training (Teil 1/2) | 108–147 | 94–133 | [`L-T3-16_06-1_Methods-of-Training.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_06-1_Methods-of-Training.pdf) |
| 06-2 | Methods of Training (Teil 2/2) | 148–187 | 134–173 | [`L-T3-16_06-2_Methods-of-Training.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_06-2_Methods-of-Training.pdf) |
| 07 | Performance on the Rock | 188–195 | 174–181 | [`L-T3-16_07_Performance-on-the-Rock.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_07_Performance-on-the-Rock.pdf) |
| 08 | Detailed Program Design | 196–209 | 182–195 | [`L-T3-16_08_Detailed-Program-Design.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_08_Detailed-Program-Design.pdf) |
| 09 | Exercises | 210–232 | 196–218 | [`L-T3-16_09_Exercises.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_09_Exercises.pdf) |
| 90 | Final Thoughts, Acknowledgements, About the Author | 233–238 | 219–224 | [`L-T3-16_90_Final-Thoughts-Acknowledgements-About-the-Author.pdf`](t3-klettern/L-T3-16_kapitel/L-T3-16_90_Final-Thoughts-Acknowledgements-About-the-Author.pdf) |

### L-T2-33 McGill – Low Back Disorders (3. Aufl.) – `t2-kraft/L-T2-33_kapitel/`

24 Dateien, 905 PDF-Seiten. Druckseite = PDF-Seite. Kapitel über 60 Seiten an Abschnittsgrenzen geteilt.

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann und Preface | 1–24 | 1–24 | [`L-T2-33_00_Vorspann-und-Preface.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_00_Vorspann-und-Preface.pdf) |
| 01 | Introduction to the Issues and Scientific Approach | 25–82 | 25–82 | [`L-T2-33_01_Introduction-to-the-Issues-and-Scientific-Approach.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_01_Introduction-to-the-Issues-and-Scientific-Approach.pdf) |
| 02 | Epidemiological Studies and What They Really Mean | 83–121 | 83–121 | [`L-T2-33_02_Epidemiological-Studies-and-What-They-Really-Mean.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_02_Epidemiological-Studies-and-What-They-Really-Mean.pdf) |
| 03-1 | Functional Anatomy of the Lumbar Spine (Teil 1/3) | 122–157 | 122–157 | [`L-T2-33_03-1_Functional-Anatomy-of-the-Lumbar-Spine.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_03-1_Functional-Anatomy-of-the-Lumbar-Spine.pdf) |
| 03-2 | Functional Anatomy of the Lumbar Spine (Teil 2/3) | 158–198 | 158–198 | [`L-T2-33_03-2_Functional-Anatomy-of-the-Lumbar-Spine.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_03-2_Functional-Anatomy-of-the-Lumbar-Spine.pdf) |
| 03-3 | Functional Anatomy of the Lumbar Spine (Teil 3/3) | 199–236 | 199–236 | [`L-T2-33_03-3_Functional-Anatomy-of-the-Lumbar-Spine.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_03-3_Functional-Anatomy-of-the-Lumbar-Spine.pdf) |
| 04-1 | Normal and Injury Mechanics of the Lumbar Spine (Teil 1/3) | 237–272 | 237–272 | [`L-T2-33_04-1_Normal-and-Injury-Mechanics-of-the-Lumbar-Spine.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_04-1_Normal-and-Injury-Mechanics-of-the-Lumbar-Spine.pdf) |
| 04-2 | Normal and Injury Mechanics of the Lumbar Spine (Teil 2/3) | 273–308 | 273–308 | [`L-T2-33_04-2_Normal-and-Injury-Mechanics-of-the-Lumbar-Spine.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_04-2_Normal-and-Injury-Mechanics-of-the-Lumbar-Spine.pdf) |
| 04-3 | Normal and Injury Mechanics of the Lumbar Spine (Teil 3/3) | 309–344 | 309–344 | [`L-T2-33_04-3_Normal-and-Injury-Mechanics-of-the-Lumbar-Spine.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_04-3_Normal-and-Injury-Mechanics-of-the-Lumbar-Spine.pdf) |
| 05 | Myths and Realities of Lumbar Spine Stability | 345–375 | 345–375 | [`L-T2-33_05_Myths-and-Realities-of-Lumbar-Spine-Stability.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_05_Myths-and-Realities-of-Lumbar-Spine-Stability.pdf) |
| 06 | LBD Risk Assessment | 376–398 | 376–398 | [`L-T2-33_06_LBD-Risk-Assessment.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_06_LBD-Risk-Assessment.pdf) |
| 07-1 | Reducing the Risk of Low Back Injury (Teil 1/2) | 399–442 | 399–442 | [`L-T2-33_07-1_Reducing-the-Risk-of-Low-Back-Injury.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_07-1_Reducing-the-Risk-of-Low-Back-Injury.pdf) |
| 07-2 | Reducing the Risk of Low Back Injury (Teil 2/2) | 443–487 | 443–487 | [`L-T2-33_07-2_Reducing-the-Risk-of-Low-Back-Injury.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_07-2_Reducing-the-Risk-of-Low-Back-Injury.pdf) |
| 08-1 | Building Better Rehabilitation Programs (Teil 1/2) | 488–527 | 488–527 | [`L-T2-33_08-1_Building-Better-Rehabilitation-Programs.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_08-1_Building-Better-Rehabilitation-Programs.pdf) |
| 08-2 | Building Better Rehabilitation Programs (Teil 2/2) | 528–568 | 528–568 | [`L-T2-33_08-2_Building-Better-Rehabilitation-Programs.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_08-2_Building-Better-Rehabilitation-Programs.pdf) |
| 09-1 | Evaluating the Patient (Teil 1/3) | 569–601 | 569–601 | [`L-T2-33_09-1_Evaluating-the-Patient.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_09-1_Evaluating-the-Patient.pdf) |
| 09-2 | Evaluating the Patient (Teil 2/3) | 602–652 | 602–652 | [`L-T2-33_09-2_Evaluating-the-Patient.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_09-2_Evaluating-the-Patient.pdf) |
| 09-3 | Evaluating the Patient (Teil 3/3) | 653–684 | 653–684 | [`L-T2-33_09-3_Evaluating-the-Patient.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_09-3_Evaluating-the-Patient.pdf) |
| 10 | Developing the Exercise Program | 685–744 | 685–744 | [`L-T2-33_10_Developing-the-Exercise-Program.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_10_Developing-the-Exercise-Program.pdf) |
| 11 | Advanced Exercises | 745–782 | 745–782 | [`L-T2-33_11_Advanced-Exercises.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_11_Advanced-Exercises.pdf) |
| 90 | Epilogue and Handouts | 783–815 | 783–815 | [`L-T2-33_90_Epilogue-and-Handouts.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_90_Epilogue-and-Handouts.pdf) |
| 91 | Appendix and Glossary | 816–825 | 816–825 | [`L-T2-33_91_Appendix-and-Glossary.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_91_Appendix-and-Glossary.pdf) |
| 92-1 | References and About the Author (Teil 1/2) | 826–865 | 826–865 | [`L-T2-33_92-1_References-and-About-the-Author.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_92-1_References-and-About-the-Author.pdf) |
| 92-2 | References and About the Author (Teil 2/2) | 866–905 | 866–905 | [`L-T2-33_92-2_References-and-About-the-Author.pdf`](t2-kraft/L-T2-33_kapitel/L-T2-33_92-2_References-and-About-the-Author.pdf) |

### L-R-30 Engelhardt – Sportverletzungen, GOTS-Manual (3. Aufl.) – `r-reha/L-R-30_kapitel/`

93 Dateien, 912 PDF-Seiten. Kapitelgrenzen und Druckseiten aus den Kopfzeilen; der Versatz zwischen PDF- und Druckseite ist nicht konstant.

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann | 1–15 | – | [`L-R-30_00_Vorspann.pdf`](r-reha/L-R-30_kapitel/L-R-30_00_Vorspann.pdf) |
| 01 | Reaktion und Anpassung an sportliche Beanspruchung | 16–24 | 4–11 | [`L-R-30_01_Reaktion-und-Anpassung-an-sportliche-Beanspruchung.pdf`](r-reha/L-R-30_kapitel/L-R-30_01_Reaktion-und-Anpassung-an-sportliche-Beanspruchung.pdf) |
| 02 | Alters- und geschlechtsspezifische Aspekte | 25–40 | 14–28 | [`L-R-30_02_Alters-und-geschlechtsspezifische-Aspekte.pdf`](r-reha/L-R-30_kapitel/L-R-30_02_Alters-und-geschlechtsspezifische-Aspekte.pdf) |
| 03 | Sport bei Erkrankungen | 41–46 | 30–34 | [`L-R-30_03_Sport-bei-Erkrankungen.pdf`](r-reha/L-R-30_kapitel/L-R-30_03_Sport-bei-Erkrankungen.pdf) |
| 04 | Anti-Doping-Vorgaben im Leistungssport | 47–55 | 36–43 | [`L-R-30_04_Anti-Doping-Vorgaben-im-Leistungssport.pdf`](r-reha/L-R-30_kapitel/L-R-30_04_Anti-Doping-Vorgaben-im-Leistungssport.pdf) |
| 05 | Behindertensport | 56–67 | 46–56 | [`L-R-30_05_Behindertensport.pdf`](r-reha/L-R-30_kapitel/L-R-30_05_Behindertensport.pdf) |
| 06 | Klinische und funktionelle Untersuchung | 68–74 | 60–65 | [`L-R-30_06_Klinische-und-funktionelle-Untersuchung.pdf`](r-reha/L-R-30_kapitel/L-R-30_06_Klinische-und-funktionelle-Untersuchung.pdf) |
| 07 | Sonografie | 75–88 | 68–80 | [`L-R-30_07_Sonografie.pdf`](r-reha/L-R-30_kapitel/L-R-30_07_Sonografie.pdf) |
| 08 | Bildgebung | 89–126 | 82–118 | [`L-R-30_08_Bildgebung.pdf`](r-reha/L-R-30_kapitel/L-R-30_08_Bildgebung.pdf) |
| 09 | Arthroskopie | 127–146 | 120–138 | [`L-R-30_09_Arthroskopie.pdf`](r-reha/L-R-30_kapitel/L-R-30_09_Arthroskopie.pdf) |
| 10 | Klinische Biomechanik | 147–156 | 140–148 | [`L-R-30_10_Klinische-Biomechanik.pdf`](r-reha/L-R-30_kapitel/L-R-30_10_Klinische-Biomechanik.pdf) |
| 11 | Bewegungsanalyse | 157–172 | 152–165 | [`L-R-30_11_Bewegungsanalyse.pdf`](r-reha/L-R-30_kapitel/L-R-30_11_Bewegungsanalyse.pdf) |
| 12 | Zentrales und peripheres Nervensystem | 173–219 | 170–215 | [`L-R-30_12_Zentrales-und-peripheres-Nervensystem.pdf`](r-reha/L-R-30_kapitel/L-R-30_12_Zentrales-und-peripheres-Nervensystem.pdf) |
| 13 | Augen | 220–239 | 218–236 | [`L-R-30_13_Augen.pdf`](r-reha/L-R-30_kapitel/L-R-30_13_Augen.pdf) |
| 14 | Ohren, Gesichtsschädel und Halsweichteile | 240–246 | 238–243 | [`L-R-30_14_Ohren-Gesichtsschaedel-und-Halsweichteile.pdf`](r-reha/L-R-30_kapitel/L-R-30_14_Ohren-Gesichtsschaedel-und-Halsweichteile.pdf) |
| 15 | Schultergelenk | 247–263 | 246–261 | [`L-R-30_15_Schultergelenk.pdf`](r-reha/L-R-30_kapitel/L-R-30_15_Schultergelenk.pdf) |
| 16 | Ellenbogen und Unterarm | 264–271 | 264–270 | [`L-R-30_16_Ellenbogen-und-Unterarm.pdf`](r-reha/L-R-30_kapitel/L-R-30_16_Ellenbogen-und-Unterarm.pdf) |
| 17 | Hand und Handgelenk | 272–288 | 272–287 | [`L-R-30_17_Hand-und-Handgelenk.pdf`](r-reha/L-R-30_kapitel/L-R-30_17_Hand-und-Handgelenk.pdf) |
| 18 | Becken und Hüftgelenk | 289–304 | 290–304 | [`L-R-30_18_Becken-und-Hueftgelenk.pdf`](r-reha/L-R-30_kapitel/L-R-30_18_Becken-und-Hueftgelenk.pdf) |
| 19 | Leiste | 305–313 | 306–313 | [`L-R-30_19_Leiste.pdf`](r-reha/L-R-30_kapitel/L-R-30_19_Leiste.pdf) |
| 20 | Das Kniegelenk | 314–330 | 316–331 | [`L-R-30_20_Das-Kniegelenk.pdf`](r-reha/L-R-30_kapitel/L-R-30_20_Das-Kniegelenk.pdf) |
| 21 | Unterschenkel, Sprunggelenk und Fuß | 331–361 | 334–363 | [`L-R-30_21_Unterschenkel-Sprunggelenk-und-Fuss.pdf`](r-reha/L-R-30_kapitel/L-R-30_21_Unterschenkel-Sprunggelenk-und-Fuss.pdf) |
| 22 | Muskulatur | 362–382 | 366–385 | [`L-R-30_22_Muskulatur.pdf`](r-reha/L-R-30_kapitel/L-R-30_22_Muskulatur.pdf) |
| 23 | Stressreaktionen des Knochens | 383–389 | 388–393 | [`L-R-30_23_Stressreaktionen-des-Knochens.pdf`](r-reha/L-R-30_kapitel/L-R-30_23_Stressreaktionen-des-Knochens.pdf) |
| 24 | Knorpel | 390–399 | 396–404 | [`L-R-30_24_Knorpel.pdf`](r-reha/L-R-30_kapitel/L-R-30_24_Knorpel.pdf) |
| 25 | Sehnenverletzungen | 400–407 | 406–412 | [`L-R-30_25_Sehnenverletzungen.pdf`](r-reha/L-R-30_kapitel/L-R-30_25_Sehnenverletzungen.pdf) |
| 26 | Biathlon | 408–411 | 416–418 | [`L-R-30_26_Biathlon.pdf`](r-reha/L-R-30_kapitel/L-R-30_26_Biathlon.pdf) |
| 27 | Eisschnelllauf – Shorttrack | 412–415 | 420–422 | [`L-R-30_27_Eisschnelllauf-Shorttrack.pdf`](r-reha/L-R-30_kapitel/L-R-30_27_Eisschnelllauf-Shorttrack.pdf) |
| 28 | Kanu | 416–420 | 424–427 | [`L-R-30_28_Kanu.pdf`](r-reha/L-R-30_kapitel/L-R-30_28_Kanu.pdf) |
| 29 | Laufen | 421–427 | 430–435 | [`L-R-30_29_Laufen.pdf`](r-reha/L-R-30_kapitel/L-R-30_29_Laufen.pdf) |
| 30 | Orientierungslauf | 428–432 | 438–441 | [`L-R-30_30_Orientierungslauf.pdf`](r-reha/L-R-30_kapitel/L-R-30_30_Orientierungslauf.pdf) |
| 31 | Radsport | 433–437 | 444–447 | [`L-R-30_31_Radsport.pdf`](r-reha/L-R-30_kapitel/L-R-30_31_Radsport.pdf) |
| 32 | Rudern | 438–444 | 450–455 | [`L-R-30_32_Rudern.pdf`](r-reha/L-R-30_kapitel/L-R-30_32_Rudern.pdf) |
| 33 | Schwimmen | 445–450 | 458–462 | [`L-R-30_33_Schwimmen.pdf`](r-reha/L-R-30_kapitel/L-R-30_33_Schwimmen.pdf) |
| 34 | Skilanglauf | 451–456 | 464–468 | [`L-R-30_34_Skilanglauf.pdf`](r-reha/L-R-30_kapitel/L-R-30_34_Skilanglauf.pdf) |
| 35 | Triathlon | 457–461 | 470–473 | [`L-R-30_35_Triathlon.pdf`](r-reha/L-R-30_kapitel/L-R-30_35_Triathlon.pdf) |
| 36 | Bobsport | 462–464 | 476–477 | [`L-R-30_36_Bobsport.pdf`](r-reha/L-R-30_kapitel/L-R-30_36_Bobsport.pdf) |
| 37 | Bodybuilding | 465–468 | 480–482 | [`L-R-30_37_Bodybuilding.pdf`](r-reha/L-R-30_kapitel/L-R-30_37_Bodybuilding.pdf) |
| 38 | Gewichtheben | 469–473 | 484–487 | [`L-R-30_38_Gewichtheben.pdf`](r-reha/L-R-30_kapitel/L-R-30_38_Gewichtheben.pdf) |
| 39 | Leichtathletik (Sprung und Wurf) | 474–482 | 490–497 | [`L-R-30_39_Leichtathletik-Sprung-und-Wurf.pdf`](r-reha/L-R-30_kapitel/L-R-30_39_Leichtathletik-Sprung-und-Wurf.pdf) |
| 40 | Rennrodeln | 483–486 | 500–502 | [`L-R-30_40_Rennrodeln.pdf`](r-reha/L-R-30_kapitel/L-R-30_40_Rennrodeln.pdf) |
| 41 | Skeleton | 487–489 | 504–505 | [`L-R-30_41_Skeleton.pdf`](r-reha/L-R-30_kapitel/L-R-30_41_Skeleton.pdf) |
| 42 | Carving-Skifahren | 490–494 | 508–511 | [`L-R-30_42_Carving-Skifahren.pdf`](r-reha/L-R-30_kapitel/L-R-30_42_Carving-Skifahren.pdf) |
| 43 | Skisprunglauf | 495–498 | 514–516 | [`L-R-30_43_Skisprunglauf.pdf`](r-reha/L-R-30_kapitel/L-R-30_43_Skisprunglauf.pdf) |
| 44 | Sportklettern | 499–502 | 518–520 | [`L-R-30_44_Sportklettern.pdf`](r-reha/L-R-30_kapitel/L-R-30_44_Sportklettern.pdf) |
| 45 | Aikido | 503–509 | 522–527 | [`L-R-30_45_Aikido.pdf`](r-reha/L-R-30_kapitel/L-R-30_45_Aikido.pdf) |
| 46 | Boxen | 510–514 | 530–533 | [`L-R-30_46_Boxen.pdf`](r-reha/L-R-30_kapitel/L-R-30_46_Boxen.pdf) |
| 47 | Fechten | 515–518 | 536–538 | [`L-R-30_47_Fechten.pdf`](r-reha/L-R-30_kapitel/L-R-30_47_Fechten.pdf) |
| 48 | Judo | 519–529 | 540–549 | [`L-R-30_48_Judo.pdf`](r-reha/L-R-30_kapitel/L-R-30_48_Judo.pdf) |
| 49 | Karate | 530–532 | 552–553 | [`L-R-30_49_Karate.pdf`](r-reha/L-R-30_kapitel/L-R-30_49_Karate.pdf) |
| 50 | Ringen | 533–539 | 556–561 | [`L-R-30_50_Ringen.pdf`](r-reha/L-R-30_kapitel/L-R-30_50_Ringen.pdf) |
| 51 | Taekwondo | 540–543 | 564–566 | [`L-R-30_51_Taekwondo.pdf`](r-reha/L-R-30_kapitel/L-R-30_51_Taekwondo.pdf) |
| 52 | American Football | 544–547 | 568–570 | [`L-R-30_52_American-Football.pdf`](r-reha/L-R-30_kapitel/L-R-30_52_American-Football.pdf) |
| 53 | Baseball | 548–555 | 572–578 | [`L-R-30_53_Baseball.pdf`](r-reha/L-R-30_kapitel/L-R-30_53_Baseball.pdf) |
| 54 | Basketball | 556–565 | 580–588 | [`L-R-30_54_Basketball.pdf`](r-reha/L-R-30_kapitel/L-R-30_54_Basketball.pdf) |
| 55 | Beach-Soccer | 566–570 | 590–593 | [`L-R-30_55_Beach-Soccer.pdf`](r-reha/L-R-30_kapitel/L-R-30_55_Beach-Soccer.pdf) |
| 56 | Eishockey | 571–575 | 596–599 | [`L-R-30_56_Eishockey.pdf`](r-reha/L-R-30_kapitel/L-R-30_56_Eishockey.pdf) |
| 57 | Fußball | 576–593 | 602–618 | [`L-R-30_57_Fussball.pdf`](r-reha/L-R-30_kapitel/L-R-30_57_Fussball.pdf) |
| 58 | Handball | 594–601 | 620–626 | [`L-R-30_58_Handball.pdf`](r-reha/L-R-30_kapitel/L-R-30_58_Handball.pdf) |
| 59 | Feldhockey | 602–605 | 628–630 | [`L-R-30_59_Feldhockey.pdf`](r-reha/L-R-30_kapitel/L-R-30_59_Feldhockey.pdf) |
| 60 | Rugby | 606–610 | 632–635 | [`L-R-30_60_Rugby.pdf`](r-reha/L-R-30_kapitel/L-R-30_60_Rugby.pdf) |
| 61 | Badminton | 611–617 | 638–643 | [`L-R-30_61_Badminton.pdf`](r-reha/L-R-30_kapitel/L-R-30_61_Badminton.pdf) |
| 62 | Beachvolleyball | 618–623 | 646–650 | [`L-R-30_62_Beachvolleyball.pdf`](r-reha/L-R-30_kapitel/L-R-30_62_Beachvolleyball.pdf) |
| 63 | Squash | 624–627 | 652–654 | [`L-R-30_63_Squash.pdf`](r-reha/L-R-30_kapitel/L-R-30_63_Squash.pdf) |
| 64 | Tennis | 628–635 | 656–662 | [`L-R-30_64_Tennis.pdf`](r-reha/L-R-30_kapitel/L-R-30_64_Tennis.pdf) |
| 65 | Tischtennis | 636–639 | 664–666 | [`L-R-30_65_Tischtennis.pdf`](r-reha/L-R-30_kapitel/L-R-30_65_Tischtennis.pdf) |
| 66 | Volleyball | 640–647 | 668–674 | [`L-R-30_66_Volleyball.pdf`](r-reha/L-R-30_kapitel/L-R-30_66_Volleyball.pdf) |
| 67 | Balletttanz | 648–652 | 676–679 | [`L-R-30_67_Balletttanz.pdf`](r-reha/L-R-30_kapitel/L-R-30_67_Balletttanz.pdf) |
| 68 | Eiskunstlauf | 653–657 | 682–685 | [`L-R-30_68_Eiskunstlauf.pdf`](r-reha/L-R-30_kapitel/L-R-30_68_Eiskunstlauf.pdf) |
| 69 | Gerätturnen | 658–667 | 688–696 | [`L-R-30_69_Geraetturnen.pdf`](r-reha/L-R-30_kapitel/L-R-30_69_Geraetturnen.pdf) |
| 70 | Rhythmische Sportgymnastik | 668–671 | 698–700 | [`L-R-30_70_Rhythmische-Sportgymnastik.pdf`](r-reha/L-R-30_kapitel/L-R-30_70_Rhythmische-Sportgymnastik.pdf) |
| 71 | Tanzsport | 672–677 | 702–706 | [`L-R-30_71_Tanzsport.pdf`](r-reha/L-R-30_kapitel/L-R-30_71_Tanzsport.pdf) |
| 72 | Wasserspringen | 678–681 | 708–710 | [`L-R-30_72_Wasserspringen.pdf`](r-reha/L-R-30_kapitel/L-R-30_72_Wasserspringen.pdf) |
| 73 | Inlineskating | 682–685 | 712–714 | [`L-R-30_73_Inlineskating.pdf`](r-reha/L-R-30_kapitel/L-R-30_73_Inlineskating.pdf) |
| 74 | Kitesurfen | 686–692 | 716–721 | [`L-R-30_74_Kitesurfen.pdf`](r-reha/L-R-30_kapitel/L-R-30_74_Kitesurfen.pdf) |
| 75 | Mountainbiken | 693–696 | 724–726 | [`L-R-30_75_Mountainbiken.pdf`](r-reha/L-R-30_kapitel/L-R-30_75_Mountainbiken.pdf) |
| 76 | Paragliding | 697–700 | 728–730 | [`L-R-30_76_Paragliding.pdf`](r-reha/L-R-30_kapitel/L-R-30_76_Paragliding.pdf) |
| 77 | Snowboarden | 701–710 | 732–740 | [`L-R-30_77_Snowboarden.pdf`](r-reha/L-R-30_kapitel/L-R-30_77_Snowboarden.pdf) |
| 78 | Golf | 711–714 | 742–744 | [`L-R-30_78_Golf.pdf`](r-reha/L-R-30_kapitel/L-R-30_78_Golf.pdf) |
| 79 | Motorsport | 715–725 | 746–755 | [`L-R-30_79_Motorsport.pdf`](r-reha/L-R-30_kapitel/L-R-30_79_Motorsport.pdf) |
| 80 | Reitsport | 726–730 | 758–761 | [`L-R-30_80_Reitsport.pdf`](r-reha/L-R-30_kapitel/L-R-30_80_Reitsport.pdf) |
| 81 | Zielsportarten | 731–735 | 764–767 | [`L-R-30_81_Zielsportarten.pdf`](r-reha/L-R-30_kapitel/L-R-30_81_Zielsportarten.pdf) |
| 82 | Segeln | 736–739 | 770–772 | [`L-R-30_82_Segeln.pdf`](r-reha/L-R-30_kapitel/L-R-30_82_Segeln.pdf) |
| 83 | Tauchen | 740–744 | 774–777 | [`L-R-30_83_Tauchen.pdf`](r-reha/L-R-30_kapitel/L-R-30_83_Tauchen.pdf) |
| 84 | Rehabilitation nach Sportverletzungen | 745–769 | 782–805 | [`L-R-30_84_Rehabilitation-nach-Sportverletzungen.pdf`](r-reha/L-R-30_kapitel/L-R-30_84_Rehabilitation-nach-Sportverletzungen.pdf) |
| 85 | Todesfälle im Sport | 770–789 | 810–828 | [`L-R-30_85_Todesfaelle-im-Sport.pdf`](r-reha/L-R-30_kapitel/L-R-30_85_Todesfaelle-im-Sport.pdf) |
| 86 | Ernährung | 790–815 | 832–856 | [`L-R-30_86_Ernaehrung.pdf`](r-reha/L-R-30_kapitel/L-R-30_86_Ernaehrung.pdf) |
| 87 | Sportbekleidung | 816–822 | 858–863 | [`L-R-30_87_Sportbekleidung.pdf`](r-reha/L-R-30_kapitel/L-R-30_87_Sportbekleidung.pdf) |
| 88 | Sportschuhe | 823–830 | 866–872 | [`L-R-30_88_Sportschuhe.pdf`](r-reha/L-R-30_kapitel/L-R-30_88_Sportschuhe.pdf) |
| 89 | Orthesen | 831–844 | 874–886 | [`L-R-30_89_Orthesen.pdf`](r-reha/L-R-30_kapitel/L-R-30_89_Orthesen.pdf) |
| 90 | Ausgewählte Rechtsfragen in der (Sport-)Medizin | 845–868 | 890–912 | [`L-R-30_90_Ausgewaehlte-Rechtsfragen-in-der-Sport-Medizin.pdf`](r-reha/L-R-30_kapitel/L-R-30_90_Ausgewaehlte-Rechtsfragen-in-der-Sport-Medizin.pdf) |
| 91 | Register | 869–896 | – | [`L-R-30_91_Register.pdf`](r-reha/L-R-30_kapitel/L-R-30_91_Register.pdf) |
| 92 | Farbtafel | 897–912 | – | [`L-R-30_92_Farbtafel.pdf`](r-reha/L-R-30_kapitel/L-R-30_92_Farbtafel.pdf) |

### L-T4-36 Schleip/Wilke – Fascia in Sport and Movement (2. Aufl.) – `t4-beweglichkeit/L-T4-36_kapitel/`

50 Dateien, 618 PDF-Seiten. Nur Kapitel-PDFs (Gesamt-PDF 159 MB). Druckseite = PDF-Seite − 19.

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann | 1–19 | – | [`L-T4-36_00_Vorspann.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_00_Vorspann.pdf) |
| 01 | Highlights of fascial anatomy, morphology and function | 20–35 | 1–16 | [`L-T4-36_01_Highlights-of-fascial-anatomy-morphology-and-function.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_01_Highlights-of-fascial-anatomy-morphology-and-function.pdf) |
| 02 | Surprising facts about fascial physiology and biochemistry | 36–49 | 17–30 | [`L-T4-36_02_Surprising-facts-about-fascial-physiology-and-biochemistry.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_02_Surprising-facts-about-fascial-physiology-and-biochemistry.pdf) |
| 03 | Sex hormonal effects on tendons and ligaments | 50–63 | 31–44 | [`L-T4-36_03_Sex-hormonal-effects-on-tendons-and-ligaments.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_03_Sex-hormonal-effects-on-tendons-and-ligaments.pdf) |
| 04 | Stress loading and matrix remodeling in tendon and skeletal muscle: Cellular mechano-stimulation and tissue remodeling | 64–71 | 45–52 | [`L-T4-36_04_Stress-loading-and-matrix-remodeling-in-tendon-and-skeletal.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_04_Stress-loading-and-matrix-remodeling-in-tendon-and-skeletal.pdf) |
| 05 | Mechanical loading and adaptive responses of tendinous tissues | 72–81 | 53–62 | [`L-T4-36_05_Mechanical-loading-and-adaptive-responses-of-tendinous-tissu.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_05_Mechanical-loading-and-adaptive-responses-of-tendinous-tissu.pdf) |
| 06 | Nutrition and loading to improve fascia function | 82–95 | 63–76 | [`L-T4-36_06_Nutrition-and-loading-to-improve-fascia-function.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_06_Nutrition-and-loading-to-improve-fascia-function.pdf) |
| 07 | Hypo- and hypermobility | 96–115 | 77–96 | [`L-T4-36_07_Hypo-and-hypermobility.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_07_Hypo-and-hypermobility.pdf) |
| 08 | Elastic storage and recoil dynamics | 116–125 | 97–106 | [`L-T4-36_08_Elastic-storage-and-recoil-dynamics.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_08_Elastic-storage-and-recoil-dynamics.pdf) |
| 09 | Water and fluid dynamics in fascia | 126–135 | 107–116 | [`L-T4-36_09_Water-and-fluid-dynamics-in-fascia.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_09_Water-and-fluid-dynamics-in-fascia.pdf) |
| 10 | What is it good for? An evidence-based review of stretching in sport and movement | 136–147 | 117–128 | [`L-T4-36_10_What-is-it-good-for-An-evidence-based-review-of-stretching-i.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_10_What-is-it-good-for-An-evidence-based-review-of-stretching-i.pdf) |
| 11 | Biotensegrity in sport and movement | 148–159 | 129–140 | [`L-T4-36_11_Biotensegrity-in-sport-and-movement.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_11_Biotensegrity-in-sport-and-movement.pdf) |
| 12 | Myofascial continuity: Towards a new understanding of human anatomy | 160–165 | 141–146 | [`L-T4-36_12_Myofascial-continuity-Towards-a-new-understanding-of-human-a.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_12_Myofascial-continuity-Towards-a-new-understanding-of-human-a.pdf) |
| 13 | Mechanical force transmission across myofascial chains | 166–175 | 147–156 | [`L-T4-36_13_Mechanical-force-transmission-across-myofascial-chains.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_13_Mechanical-force-transmission-across-myofascial-chains.pdf) |
| 14 | Myofascial force transmission to synergistic and antagonistic muscles | 176–187 | 157–168 | [`L-T4-36_14_Myofascial-force-transmission-to-synergistic-and-antagonisti.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_14_Myofascial-force-transmission-to-synergistic-and-antagonisti.pdf) |
| 15 | Fascia as sensory organ | 188–199 | 169–180 | [`L-T4-36_15_Fascia-as-sensory-organ.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_15_Fascia-as-sensory-organ.pdf) |
| 16 | Fascia and musculoskeletal injury: An underestimated association? | 200–209 | 181–190 | [`L-T4-36_16_Fascia-and-musculoskeletal-injury-An-underestimated-associat.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_16_Fascia-and-musculoskeletal-injury-An-underestimated-associat.pdf) |
| 17 | Classification of athletic injuries to muscular tissues | 210–217 | 191–198 | [`L-T4-36_17_Classification-of-athletic-injuries-to-muscular-tissues.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_17_Classification-of-athletic-injuries-to-muscular-tissues.pdf) |
| 18 | Fascia, exercise and oncology | 218–231 | 199–212 | [`L-T4-36_18_Fascia-exercise-and-oncology.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_18_Fascia-exercise-and-oncology.pdf) |
| 19 | Assessment of joint mobility | 232–243 | 213–224 | [`L-T4-36_19_Assessment-of-joint-mobility.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_19_Assessment-of-joint-mobility.pdf) |
| 20 | Imaging techniques (ultrasound) | 244–253 | 225–234 | [`L-T4-36_20_Imaging-techniques-ultrasound.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_20_Imaging-techniques-ultrasound.pdf) |
| 21 | Mechanical assessment | 254–263 | 235–244 | [`L-T4-36_21_Mechanical-assessment.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_21_Mechanical-assessment.pdf) |
| 22 | Palpation and functional assessment methods for fascia-related dysfunction | 264–279 | 245–260 | [`L-T4-36_22_Palpation-and-functional-assessment-methods-for-fascia-relat.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_22_Palpation-and-functional-assessment-methods-for-fascia-relat.pdf) |
| 23 | Integrating clinical experience and scientific evidence: Roadmap for a healthy dialog between health practitioners and academic researchers | 280–287 | 261–268 | [`L-T4-36_23_Integrating-clinical-experience-and-scientific-evidence-Road.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_23_Integrating-clinical-experience-and-scientific-evidence-Road.pdf) |
| 24 | Fascial Fitness | 288–299 | 269–280 | [`L-T4-36_24_Fascial-Fitness.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_24_Fascial-Fitness.pdf) |
| 25 | Basic principles of plyometric training | 300–309 | 281–290 | [`L-T4-36_25_Basic-principles-of-plyometric-training.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_25_Basic-principles-of-plyometric-training.pdf) |
| 26 | Eccentric training: The key for a stronger, more resilient athlete? | 310–319 | 291–300 | [`L-T4-36_26_Eccentric-training-The-key-for-a-stronger-more-resilient-ath.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_26_Eccentric-training-The-key-for-a-stronger-more-resilient-ath.pdf) |
| 27 | Foam rolling and roller massage effects and mechanisms | 320–333 | 301–314 | [`L-T4-36_27_Foam-rolling-and-roller-massage-effects-and-mechanisms.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_27_Foam-rolling-and-roller-massage-effects-and-mechanisms.pdf) |
| 28 | Fascial stretching | 334–345 | 315–326 | [`L-T4-36_28_Fascial-stretching.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_28_Fascial-stretching.pdf) |
| 29 | Food for the fascia: Molecular and biochemical processes | 346–357 | 327–338 | [`L-T4-36_29_Food-for-the-fascia-Molecular-and-biochemical-processes.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_29_Food-for-the-fascia-Molecular-and-biochemical-processes.pdf) |
| 30 | Walking: The benefit of being on two legs | 358–371 | 339–352 | [`L-T4-36_30_Walking-The-benefit-of-being-on-two-legs.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_30_Walking-The-benefit-of-being-on-two-legs.pdf) |
| 31 | Functional training methods for the runner’s myofascial systems | 372–389 | 353–370 | [`L-T4-36_31_Functional-training-methods-for-the-runners-myofascial-syste.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_31_Functional-training-methods-for-the-runners-myofascial-syste.pdf) |
| 32 | Shoes or no shoes during locomotion and exercise: Training potential for fascial structures of the lower extremity | 390–403 | 371–384 | [`L-T4-36_32_Shoes-or-no-shoes-during-locomotion-and-exercise-Training-po.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_32_Shoes-or-no-shoes-during-locomotion-and-exercise-Training-po.pdf) |
| 33 | Overarm throwing in humans | 404–411 | 385–392 | [`L-T4-36_33_Overarm-throwing-in-humans.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_33_Overarm-throwing-in-humans.pdf) |
| 34 | The secret role of fascia in the martial arts | 412–421 | 393–402 | [`L-T4-36_34_The-secret-role-of-fascia-in-the-martial-arts.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_34_The-secret-role-of-fascia-in-the-martial-arts.pdf) |
| 35 | The world as a playground: Ninja and parkour training | 422–429 | 403–410 | [`L-T4-36_35_The-world-as-a-playground-Ninja-and-parkour-training.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_35_The-world-as-a-playground-Ninja-and-parkour-training.pdf) |
| 36 | Anatomy Trains in motion | 430–443 | 411–424 | [`L-T4-36_36_Anatomy-Trains-in-motion.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_36_Anatomy-Trains-in-motion.pdf) |
| 37 | Fascial form in yoga | 444–455 | 425–436 | [`L-T4-36_37_Fascial-form-in-yoga.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_37_Fascial-form-in-yoga.pdf) |
| 38 | Yin yoga as a fascia-oriented practice | 456–469 | 437–450 | [`L-T4-36_38_Yin-yoga-as-a-fascia-oriented-practice.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_38_Yin-yoga-as-a-fascia-oriented-practice.pdf) |
| 39 | Fascia-focused Pilates training | 470–509 | 451–490 | [`L-T4-36_39_Fascia-focused-Pilates-training.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_39_Fascia-focused-Pilates-training.pdf) |
| 40 | Three-dimensional fascia-oriented training | 510–521 | 491–502 | [`L-T4-36_40_Three-dimensional-fascia-oriented-training.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_40_Three-dimensional-fascia-oriented-training.pdf) |
| 41 | Dance | 522–531 | 503–512 | [`L-T4-36_41_Dance.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_41_Dance.pdf) |
| 42 | Kettlebell training | 532–539 | 513–520 | [`L-T4-36_42_Kettlebell-training.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_42_Kettlebell-training.pdf) |
| 43 | Fascia-oriented strength training in a conventional gym environment | 540–547 | 521–528 | [`L-T4-36_43_Fascia-oriented-strength-training-in-a-conventional-gym-envi.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_43_Fascia-oriented-strength-training-in-a-conventional-gym-envi.pdf) |
| 44 | Rehabilitation in sport medicine | 548–559 | 529–540 | [`L-T4-36_44_Rehabilitation-in-sport-medicine.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_44_Rehabilitation-in-sport-medicine.pdf) |
| 45 | How to train fascia in soccer | 560–571 | 541–552 | [`L-T4-36_45_How-to-train-fascia-in-soccer.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_45_How-to-train-fascia-in-soccer.pdf) |
| 46 | Movement therapy for breast cancer survivors | 572–587 | 553–568 | [`L-T4-36_46_Movement-therapy-for-breast-cancer-survivors.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_46_Movement-therapy-for-breast-cancer-survivors.pdf) |
| 47 | Mental imagery, fascia and movement | 588–597 | 569–578 | [`L-T4-36_47_Mental-imagery-fascia-and-movement.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_47_Mental-imagery-fascia-and-movement.pdf) |
| 48 | Periodized fascia training for speed, power, and injury resilience | 598–608 | 579–589 | [`L-T4-36_48_Periodized-fascia-training-for-speed-power-and-injury-resili.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_48_Periodized-fascia-training-for-speed-power-and-injury-resili.pdf) |
| 90 | Permissions and Index | 609–618 | 590–599 | [`L-T4-36_90_Permissions-and-Index.pdf`](t4-beweglichkeit/L-T4-36_kapitel/L-T4-36_90_Permissions-and-Index.pdf) |

### L-R-29 Brukner & Khan – Clinical Sports Medicine, Vol. 1 Injuries (5. Aufl.) – `r-reha/L-R-29_kapitel/`

55 Dateien, 1227 PDF-Seiten. Nur Kapitel-PDFs (Gesamt 120 MB). Scan mit eigener Texterkennung. Druckseite = PDF-Seite − 41; Teil-Titelseiten gehören zum folgenden Kapitel; das Literaturverzeichnis am Ende hat eine eigene Zählung.

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann | 1–41 | – | [`L-R-29_00_Vorspann.pdf`](r-reha/L-R-29_kapitel/L-R-29_00_Vorspann.pdf) |
| 01 | Sport and exercise medicine – the team approach | 42–49 | 1–8 | [`L-R-29_01_Sport-and-exercise-medicine-the-team-approach.pdf`](r-reha/L-R-29_kapitel/L-R-29_01_Sport-and-exercise-medicine-the-team-approach.pdf) |
| 02 | Integrating evidence into shared decision making with patients | 50–53 | 9–12 | [`L-R-29_02_Integrating-evidence-into-shared-decision-making-with-patien.pdf`](r-reha/L-R-29_kapitel/L-R-29_02_Integrating-evidence-into-shared-decision-making-with-patien.pdf) |
| 03 | Sports injuries – acute | 54–69 | 13–28 | [`L-R-29_03_Sports-injuries-acute.pdf`](r-reha/L-R-29_kapitel/L-R-29_03_Sports-injuries-acute.pdf) |
| 04 | Sports injuries – overuse | 70–95 | 29–54 | [`L-R-29_04_Sports-injuries-overuse.pdf`](r-reha/L-R-29_kapitel/L-R-29_04_Sports-injuries-overuse.pdf) |
| 05 | Pain – why and how does it hurt? | 96–105 | 55–64 | [`L-R-29_05_Pain-why-and-how-does-it-hurt.pdf`](r-reha/L-R-29_kapitel/L-R-29_05_Pain-why-and-how-does-it-hurt.pdf) |
| 06 | Pain – the clinical aspects | 106–117 | 65–76 | [`L-R-29_06_Pain-the-clinical-aspects.pdf`](r-reha/L-R-29_kapitel/L-R-29_06_Pain-the-clinical-aspects.pdf) |
| 07 | Beware – conditions that masquerade as sports injuries | 118–125 | 77–84 | [`L-R-29_07_Beware-conditions-that-masquerade-as-sports-injuries.pdf`](r-reha/L-R-29_kapitel/L-R-29_07_Beware-conditions-that-masquerade-as-sports-injuries.pdf) |
| 08 | Introduction to clinical biomechanics | 126–161 | 85–120 | [`L-R-29_08_Introduction-to-clinical-biomechanics.pdf`](r-reha/L-R-29_kapitel/L-R-29_08_Introduction-to-clinical-biomechanics.pdf) |
| 09 | Biomechanical aspects of injury in specific sports | 162–179 | 121–138 | [`L-R-29_09_Biomechanical-aspects-of-injury-in-specific-sports.pdf`](r-reha/L-R-29_kapitel/L-R-29_09_Biomechanical-aspects-of-injury-in-specific-sports.pdf) |
| 10 | Training programming and prescription | 180–193 | 139–152 | [`L-R-29_10_Training-programming-and-prescription.pdf`](r-reha/L-R-29_kapitel/L-R-29_10_Training-programming-and-prescription.pdf) |
| 11 | Core stability | 194–205 | 153–164 | [`L-R-29_11_Core-stability.pdf`](r-reha/L-R-29_kapitel/L-R-29_11_Core-stability.pdf) |
| 12 | Preventing injury | 206–229 | 165–188 | [`L-R-29_12_Preventing-injury.pdf`](r-reha/L-R-29_kapitel/L-R-29_12_Preventing-injury.pdf) |
| 13 | Recovery | 230–241 | 189–200 | [`L-R-29_13_Recovery.pdf`](r-reha/L-R-29_kapitel/L-R-29_13_Recovery.pdf) |
| 14 | Clinical assessment – moving from rote to rigorous | 242–249 | 201–208 | [`L-R-29_14_Clinical-assessment-moving-from-rote-to-rigorous.pdf`](r-reha/L-R-29_kapitel/L-R-29_14_Clinical-assessment-moving-from-rote-to-rigorous.pdf) |
| 15 | How to make the diagnosis | 250–271 | 209–230 | [`L-R-29_15_How-to-make-the-diagnosis.pdf`](r-reha/L-R-29_kapitel/L-R-29_15_How-to-make-the-diagnosis.pdf) |
| 16 | Patient-reported outcome measures in sports medicine | 272–279 | 231–238 | [`L-R-29_16_Patient-reported-outcome-measures-in-sports-medicine.pdf`](r-reha/L-R-29_kapitel/L-R-29_16_Patient-reported-outcome-measures-in-sports-medicine.pdf) |
| 17 | Treatment of sports injuries | 280–317 | 239–276 | [`L-R-29_17_Treatment-of-sports-injuries.pdf`](r-reha/L-R-29_kapitel/L-R-29_17_Treatment-of-sports-injuries.pdf) |
| 18 | Principles of sports injury rehabilitation | 318–325 | 277–284 | [`L-R-29_18_Principles-of-sports-injury-rehabilitation.pdf`](r-reha/L-R-29_kapitel/L-R-29_18_Principles-of-sports-injury-rehabilitation.pdf) |
| 19 | Return to play | 326–335 | 285–294 | [`L-R-29_19_Return-to-play.pdf`](r-reha/L-R-29_kapitel/L-R-29_19_Return-to-play.pdf) |
| 20 | Sports concussion | 336–357 | 295–316 | [`L-R-29_20_Sports-concussion.pdf`](r-reha/L-R-29_kapitel/L-R-29_20_Sports-concussion.pdf) |
| 21 | Headache | 358–371 | 317–330 | [`L-R-29_21_Headache.pdf`](r-reha/L-R-29_kapitel/L-R-29_21_Headache.pdf) |
| 22 | Face, eyes and teeth | 372–387 | 331–346 | [`L-R-29_22_Face-eyes-and-teeth.pdf`](r-reha/L-R-29_kapitel/L-R-29_22_Face-eyes-and-teeth.pdf) |
| 23 | Neck pain | 388–417 | 347–376 | [`L-R-29_23_Neck-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_23_Neck-pain.pdf) |
| 24-1 | Shoulder pain (Teil 1/2) | 418–448 | 377–407 | [`L-R-29_24-1_Shoulder-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_24-1_Shoulder-pain.pdf) |
| 24-2 | Shoulder pain (Teil 2/2) | 449–479 | 408–438 | [`L-R-29_24-2_Shoulder-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_24-2_Shoulder-pain.pdf) |
| 25 | Elbow and arm pain | 480–503 | 439–462 | [`L-R-29_25_Elbow-and-arm-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_25_Elbow-and-arm-pain.pdf) |
| 26 | Wrist pain | 504–529 | 463–488 | [`L-R-29_26_Wrist-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_26_Wrist-pain.pdf) |
| 27 | Hand and finger injuries | 530–547 | 489–506 | [`L-R-29_27_Hand-and-finger-injuries.pdf`](r-reha/L-R-29_kapitel/L-R-29_27_Hand-and-finger-injuries.pdf) |
| 28 | Thoracic and chest pain | 548–561 | 507–520 | [`L-R-29_28_Thoracic-and-chest-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_28_Thoracic-and-chest-pain.pdf) |
| 29 | Low back pain | 562–607 | 521–566 | [`L-R-29_29_Low-back-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_29_Low-back-pain.pdf) |
| 30 | Buttock pain | 608–633 | 567–592 | [`L-R-29_30_Buttock-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_30_Buttock-pain.pdf) |
| 31 | Hip pain | 634–669 | 593–628 | [`L-R-29_31_Hip-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_31_Hip-pain.pdf) |
| 32 | Groin pain | 670–699 | 629–658 | [`L-R-29_32_Groin-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_32_Groin-pain.pdf) |
| 33 | Anterior thigh pain | 700–719 | 659–678 | [`L-R-29_33_Anterior-thigh-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_33_Anterior-thigh-pain.pdf) |
| 34 | Posterior thigh pain | 720–753 | 679–712 | [`L-R-29_34_Posterior-thigh-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_34_Posterior-thigh-pain.pdf) |
| 35 | Acute knee injuries | 754–809 | 713–768 | [`L-R-29_35_Acute-knee-injuries.pdf`](r-reha/L-R-29_kapitel/L-R-29_35_Acute-knee-injuries.pdf) |
| 36 | Anterior knee pain | 810–845 | 769–804 | [`L-R-29_36_Anterior-knee-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_36_Anterior-knee-pain.pdf) |
| 37 | Lateral, medial and posterior knee pain | 846–865 | 805–824 | [`L-R-29_37_Lateral-medial-and-posterior-knee-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_37_Lateral-medial-and-posterior-knee-pain.pdf) |
| 38 | Leg pain | 866–887 | 825–846 | [`L-R-29_38_Leg-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_38_Leg-pain.pdf) |
| 39 | Calf pain | 888–905 | 847–864 | [`L-R-29_39_Calf-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_39_Calf-pain.pdf) |
| 40 | Pain in the Achilles region | 906–933 | 865–892 | [`L-R-29_40_Pain-in-the-Achilles-region.pdf`](r-reha/L-R-29_kapitel/L-R-29_40_Pain-in-the-Achilles-region.pdf) |
| 41 | Acute ankle injuries | 934–957 | 893–916 | [`L-R-29_41_Acute-ankle-injuries.pdf`](r-reha/L-R-29_kapitel/L-R-29_41_Acute-ankle-injuries.pdf) |
| 42 | Ankle pain | 958–977 | 917–936 | [`L-R-29_42_Ankle-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_42_Ankle-pain.pdf) |
| 43 | Foot pain | 978–1013 | 937–972 | [`L-R-29_43_Foot-pain.pdf`](r-reha/L-R-29_kapitel/L-R-29_43_Foot-pain.pdf) |
| 44 | The younger athlete | 1014–1031 | 973–990 | [`L-R-29_44_The-younger-athlete.pdf`](r-reha/L-R-29_kapitel/L-R-29_44_The-younger-athlete.pdf) |
| 45 | Military personnel | 1032–1043 | 991–1002 | [`L-R-29_45_Military-personnel.pdf`](r-reha/L-R-29_kapitel/L-R-29_45_Military-personnel.pdf) |
| 46 | Periodic medical assessment of athletes | 1044–1057 | 1003–1016 | [`L-R-29_46_Periodic-medical-assessment-of-athletes.pdf`](r-reha/L-R-29_kapitel/L-R-29_46_Periodic-medical-assessment-of-athletes.pdf) |
| 47 | Working and travelling with teams | 1058–1067 | 1017–1026 | [`L-R-29_47_Working-and-travelling-with-teams.pdf`](r-reha/L-R-29_kapitel/L-R-29_47_Working-and-travelling-with-teams.pdf) |
| 48 | Career development | 1068–1075 | 1027–1034 | [`L-R-29_48_Career-development.pdf`](r-reha/L-R-29_kapitel/L-R-29_48_Career-development.pdf) |
| 90 | Quotation sources | 1076–1079 | – | [`L-R-29_90_Quotation-sources.pdf`](r-reha/L-R-29_kapitel/L-R-29_90_Quotation-sources.pdf) |
| 91 | Index | 1080–1097 | – | [`L-R-29_91_Index.pdf`](r-reha/L-R-29_kapitel/L-R-29_91_Index.pdf) |
| 92-1 | References (Teil 1/3) | 1098–1140 | – | [`L-R-29_92-1_References.pdf`](r-reha/L-R-29_kapitel/L-R-29_92-1_References.pdf) |
| 92-2 | References (Teil 2/3) | 1141–1184 | – | [`L-R-29_92-2_References.pdf`](r-reha/L-R-29_kapitel/L-R-29_92-2_References.pdf) |
| 92-3 | References (Teil 3/3) | 1185–1227 | – | [`L-R-29_92-3_References.pdf`](r-reha/L-R-29_kapitel/L-R-29_92-3_References.pdf) |

### L-T3-15 Anderson/Anderson – The Rock Climber's Training Manual – `t3-klettern/L-T3-15_kapitel/`

19 Dateien, 308 PDF-Seiten. Druckseite = PDF-Seite − 2; jedes Kapitel beginnt mit einer Foto-Doppelseite. Im Scan steht das Glossar vor dem Literaturverzeichnis.

| Nr. | Titel | PDF-Seiten | Druckseiten | Datei |
|---|---|---|---|---|
| 00 | Vorspann und Foreword | 1–7 | 1–5 | [`L-T3-15_00_Vorspann-und-Foreword.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_00_Vorspann-und-Foreword.pdf) |
| 01 | Introduction | 8–23 | 6–21 | [`L-T3-15_01_Introduction.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_01_Introduction.pdf) |
| 02 | Goal Setting and Planning | 24–43 | 22–41 | [`L-T3-15_02_Goal-Setting-and-Planning.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_02_Goal-Setting-and-Planning.pdf) |
| 03 | Skill Development | 44–71 | 42–69 | [`L-T3-15_03_Skill-Development.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_03_Skill-Development.pdf) |
| 04 | Foundations of Physical Training | 72–85 | 70–83 | [`L-T3-15_04_Foundations-of-Physical-Training.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_04_Foundations-of-Physical-Training.pdf) |
| 05 | Base Fitness | 86–105 | 84–103 | [`L-T3-15_05_Base-Fitness.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_05_Base-Fitness.pdf) |
| 06 | Strength | 106–129 | 104–127 | [`L-T3-15_06_Strength.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_06_Strength.pdf) |
| 07 | Power | 130–151 | 128–149 | [`L-T3-15_07_Power.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_07_Power.pdf) |
| 08 | Power-Endurance | 152–169 | 150–167 | [`L-T3-15_08_Power-Endurance.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_08_Power-Endurance.pdf) |
| 09 | Rest, Injury Prevention, and Rehabilitation | 170–185 | 168–183 | [`L-T3-15_09_Rest-Injury-Prevention-and-Rehabilitation.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_09_Rest-Injury-Prevention-and-Rehabilitation.pdf) |
| 10 | Building a Training Plan and Other Training Considerations | 186–199 | 184–197 | [`L-T3-15_10_Building-a-Training-Plan-and-Other-Training-Considerations.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_10_Building-a-Training-Plan-and-Other-Training-Considerations.pdf) |
| 11 | Weight Management | 200–219 | 198–217 | [`L-T3-15_11_Weight-Management.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_11_Weight-Management.pdf) |
| 12 | Preparing to Perform | 220–237 | 218–235 | [`L-T3-15_12_Preparing-to-Perform.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_12_Preparing-to-Perform.pdf) |
| 13 | Redpoint and Onsight Climbing | 238–257 | 236–255 | [`L-T3-15_13_Redpoint-and-Onsight-Climbing.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_13_Redpoint-and-Onsight-Climbing.pdf) |
| 14 | Traditional and Big-Wall Free Climbing | 258–277 | 256–275 | [`L-T3-15_14_Traditional-and-Big-Wall-Free-Climbing.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_14_Traditional-and-Big-Wall-Free-Climbing.pdf) |
| 15 | Bouldering | 278–289 | 276–287 | [`L-T3-15_15_Bouldering.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_15_Bouldering.pdf) |
| 90 | Glossary | 290–294 | 288–292 | [`L-T3-15_90_Glossary.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_90_Glossary.pdf) |
| 91 | References | 295–298 | 293–296 | [`L-T3-15_91_References.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_91_References.pdf) |
| 92 | Index of Routines, Training Log, Model Bios, About the Authors | 299–308 | 297–306 | [`L-T3-15_92_Index-of-Routines-Training-Log-Model-Bios-About-the-Authors.pdf`](t3-klettern/L-T3-15_kapitel/L-T3-15_92_Index-of-Routines-Training-Log-Model-Bios-About-the-Authors.pdf) |

## Kapitel-Markdown mit Ansichts-PDF (EPUB, D-71)

Markdown ist die Arbeitsfassung für die Kartensitzungen (13.1). Das Ansichts-PDF gleichen Namens zeigt die Abbildungen; seine Seitenzahlen sind nicht zitierfähig.

### L-T3-19 Consuegra – The Science of Climbing Training – `t3-klettern/L-T3-19_kapitel/`

15 Kapiteldateien, zusammen ca. 54.386 Wörter. Keine Seitenmarken: zitiert wird mit Kapitel und Abschnitt (D-71, V-17). Kapitel 8 ist in drei Teile geteilt; Danksagung und Verlagswerbung sind nicht übernommen.

| Nr. | Titel | Wörter (ca.) | Markdown | Ansichts-PDF |
|---|---|---|---|---|
| 00 | Vorspann | 1.500 | [`L-T3-19_00_Vorspann.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_00_Vorspann.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_00_Vorspann.pdf) |
| 01 | The Process of Training | 2.000 | [`L-T3-19_01_The-Process-of-Training.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_01_The-Process-of-Training.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_01_The-Process-of-Training.pdf) |
| 02 | Understanding the Importance of Strength | 3.200 | [`L-T3-19_02_Understanding-the-Importance-of-Strength.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_02_Understanding-the-Importance-of-Strength.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_02_Understanding-the-Importance-of-Strength.pdf) |
| 03 | Understanding and Optimising Mobility | 3.600 | [`L-T3-19_03_Understanding-and-Optimising-Mobility.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_03_Understanding-and-Optimising-Mobility.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_03_Understanding-and-Optimising-Mobility.pdf) |
| 04 | Brief Notes on Anatomy | 2.300 | [`L-T3-19_04_Brief-Notes-on-Anatomy.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_04_Brief-Notes-on-Anatomy.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_04_Brief-Notes-on-Anatomy.pdf) |
| 05 | Fascia, Muscle Chains and Biotensegrity | 1.000 | [`L-T3-19_05_Fascia-Muscle-Chains-and-Biotensegrity.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_05_Fascia-Muscle-Chains-and-Biotensegrity.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_05_Fascia-Muscle-Chains-and-Biotensegrity.pdf) |
| 06 | Bioenergetics and Metabolism | 1.300 | [`L-T3-19_06_Bioenergetics-and-Metabolism.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_06_Bioenergetics-and-Metabolism.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_06_Bioenergetics-and-Metabolism.pdf) |
| 07 | Physiological Factors in Climbing Performance | 3.100 | [`L-T3-19_07_Physiological-Factors-in-Climbing-Performance.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_07_Physiological-Factors-in-Climbing-Performance.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_07_Physiological-Factors-in-Climbing-Performance.pdf) |
| 08-1 | What Can I Optimise in My Training Sessions? (Teil 1/3) | 7.900 | [`L-T3-19_08-1_What-Can-I-Optimise-in-My-Training-Sessions.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_08-1_What-Can-I-Optimise-in-My-Training-Sessions.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_08-1_What-Can-I-Optimise-in-My-Training-Sessions.pdf) |
| 08-2 | What Can I Optimise in My Training Sessions? (Teil 2/3) | 6.900 | [`L-T3-19_08-2_What-Can-I-Optimise-in-My-Training-Sessions.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_08-2_What-Can-I-Optimise-in-My-Training-Sessions.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_08-2_What-Can-I-Optimise-in-My-Training-Sessions.pdf) |
| 08-3 | What Can I Optimise in My Training Sessions? (Teil 3/3) | 10.200 | [`L-T3-19_08-3_What-Can-I-Optimise-in-My-Training-Sessions.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_08-3_What-Can-I-Optimise-in-My-Training-Sessions.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_08-3_What-Can-I-Optimise-in-My-Training-Sessions.pdf) |
| 09 | Training Session Design | 1.300 | [`L-T3-19_09_Training-Session-Design.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_09_Training-Session-Design.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_09_Training-Session-Design.pdf) |
| 10 | Periodisation Models | 3.900 | [`L-T3-19_10_Periodisation-Models.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_10_Periodisation-Models.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_10_Periodisation-Models.pdf) |
| 11 | Detraining | 700 | [`L-T3-19_11_Detraining.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_11_Detraining.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_11_Detraining.pdf) |
| 90 | Bibliography | 5.500 | [`L-T3-19_90_Bibliography.md`](t3-klettern/L-T3-19_kapitel/L-T3-19_90_Bibliography.md) | [PDF](t3-klettern/L-T3-19_kapitel/L-T3-19_90_Bibliography.pdf) |

### L-T3-10 Mobråten/Christophersen – The Climbing Bible – `t3-klettern/L-T3-10_kapitel/`

11 Kapiteldateien, zusammen ca. 85.971 Wörter. Keine Seitenmarken: zitiert wird mit Kapitel und Abschnitt (D-71, V-17).

| Nr. | Titel | Wörter (ca.) | Markdown | Ansichts-PDF |
|---|---|---|---|---|
| 00 | Vorspann | 3.400 | [`L-T3-10_00_Vorspann.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_00_Vorspann.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_00_Vorspann.pdf) |
| 01-1 | Technique (Teil 1/2) | 8.100 | [`L-T3-10_01-1_Technique.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_01-1_Technique.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_01-1_Technique.pdf) |
| 01-2 | Technique (Teil 2/2) | 8.000 | [`L-T3-10_01-2_Technique.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_01-2_Technique.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_01-2_Technique.pdf) |
| 02-1 | Physical Training (Teil 1/2) | 8.100 | [`L-T3-10_02-1_Physical-Training.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_02-1_Physical-Training.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_02-1_Physical-Training.pdf) |
| 02-2 | Physical Training (Teil 2/2) | 7.900 | [`L-T3-10_02-2_Physical-Training.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_02-2_Physical-Training.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_02-2_Physical-Training.pdf) |
| 03 | Mental Training | 9.600 | [`L-T3-10_03_Mental-Training.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_03_Mental-Training.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_03_Mental-Training.pdf) |
| 04 | Tactics | 11.800 | [`L-T3-10_04_Tactics.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_04_Tactics.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_04_Tactics.pdf) |
| 05-1 | General Training and Injury Prevention (Teil 1/2) | 6.100 | [`L-T3-10_05-1_General-Training-and-Injury-Prevention.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_05-1_General-Training-and-Injury-Prevention.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_05-1_General-Training-and-Injury-Prevention.pdf) |
| 05-2 | General Training and Injury Prevention (Teil 2/2) | 6.500 | [`L-T3-10_05-2_General-Training-and-Injury-Prevention.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_05-2_General-Training-and-Injury-Prevention.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_05-2_General-Training-and-Injury-Prevention.pdf) |
| 06 | Training Plans | 10.300 | [`L-T3-10_06_Training-Plans.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_06_Training-Plans.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_06_Training-Plans.pdf) |
| 90 | The Joy of Climbing, Ten Commandments, Epilogue, Glossary, Read More | 6.300 | [`L-T3-10_90_Epilogue-Glossary-Read-More.md`](t3-klettern/L-T3-10_kapitel/L-T3-10_90_Epilogue-Glossary-Read-More.md) | [PDF](t3-klettern/L-T3-10_kapitel/L-T3-10_90_Epilogue-Glossary-Read-More.pdf) |

### L-T3-20 Mobråten/Christophersen – The Climbing Bible: Practical Exercises – `t3-klettern/L-T3-20_kapitel/`

5 Kapiteldateien, zusammen ca. 30.235 Wörter. Seitenmarken der Druckausgabe als „[S. n]“ im Markdown; Druckseiten laut Marken.

| Nr. | Titel | Wörter (ca.) | Druckseiten | Markdown | Ansichts-PDF |
|---|---|---|---|---|---|
| 00a | Vorspann | 2.100 | 2–11 | [`L-T3-20_00a_Vorspann.md`](t3-klettern/L-T3-20_kapitel/L-T3-20_00a_Vorspann.md) | [PDF](t3-klettern/L-T3-20_kapitel/L-T3-20_00a_Vorspann.pdf) |
| 00b | Warming Up | 900 | 14–19 | [`L-T3-20_00b_Warming-Up.md`](t3-klettern/L-T3-20_kapitel/L-T3-20_00b_Warming-Up.md) | [PDF](t3-klettern/L-T3-20_kapitel/L-T3-20_00b_Warming-Up.pdf) |
| 01 | Technique | 10.100 | 20–87 | [`L-T3-20_01_Technique.md`](t3-klettern/L-T3-20_kapitel/L-T3-20_01_Technique.md) | [PDF](t3-klettern/L-T3-20_kapitel/L-T3-20_01_Technique.pdf) |
| 02 | Strength & Power | 10.400 | 88–142 | [`L-T3-20_02_Strength-and-Power.md`](t3-klettern/L-T3-20_kapitel/L-T3-20_02_Strength-and-Power.md) | [PDF](t3-klettern/L-T3-20_kapitel/L-T3-20_02_Strength-and-Power.pdf) |
| 03 | Children & Youths | 6.700 | 143–192 | [`L-T3-20_03_Children-and-Youths.md`](t3-klettern/L-T3-20_kapitel/L-T3-20_03_Children-and-Youths.md) | [PDF](t3-klettern/L-T3-20_kapitel/L-T3-20_03_Children-and-Youths.pdf) |

### L-T3-21 Christophersen – The Climbing Bible: Managing Injuries – `t3-klettern/L-T3-21_kapitel/`

6 Kapiteldateien, zusammen ca. 37.625 Wörter. Seitenmarken der Druckausgabe als „[S. n]“ im Markdown; Druckseiten laut Marken.

| Nr. | Titel | Wörter (ca.) | Druckseiten | Markdown | Ansichts-PDF |
|---|---|---|---|---|---|
| 00 | Vorspann und Introduction | 2.700 | 2–11 | [`L-T3-21_00_Vorspann-und-Introduction.md`](t3-klettern/L-T3-21_kapitel/L-T3-21_00_Vorspann-und-Introduction.md) | [PDF](t3-klettern/L-T3-21_kapitel/L-T3-21_00_Vorspann-und-Introduction.pdf) |
| 01 | Handling of Acute Soft Tissue Injuries and Overuse Injuries | 6.200 | 12–37 | [`L-T3-21_01_Handling-Acute-Soft-Tissue-and-Overuse-Injuries.md`](t3-klettern/L-T3-21_kapitel/L-T3-21_01_Handling-Acute-Soft-Tissue-and-Overuse-Injuries.md) | [PDF](t3-klettern/L-T3-21_kapitel/L-T3-21_01_Handling-Acute-Soft-Tissue-and-Overuse-Injuries.pdf) |
| 02-1 | Injuries and Body Parts (Teil 1/2) | 11.200 | 38–86 | [`L-T3-21_02-1_Injuries-and-Body-Parts.md`](t3-klettern/L-T3-21_kapitel/L-T3-21_02-1_Injuries-and-Body-Parts.md) | [PDF](t3-klettern/L-T3-21_kapitel/L-T3-21_02-1_Injuries-and-Body-Parts.pdf) |
| 02-2 | Injuries and Body Parts (Teil 2/2) | 10.800 | 87–141 | [`L-T3-21_02-2_Injuries-and-Body-Parts.md`](t3-klettern/L-T3-21_kapitel/L-T3-21_02-2_Injuries-and-Body-Parts.md) | [PDF](t3-klettern/L-T3-21_kapitel/L-T3-21_02-2_Injuries-and-Body-Parts.pdf) |
| 03 | What Is Pain, Really? | 3.600 | 142–151 | [`L-T3-21_03_What-Is-Pain-Really.md`](t3-klettern/L-T3-21_kapitel/L-T3-21_03_What-Is-Pain-Really.md) | [PDF](t3-klettern/L-T3-21_kapitel/L-T3-21_03_What-Is-Pain-Really.pdf) |
| 90 | Glossary, References and Bibliography | 3.200 | 152–157 | [`L-T3-21_90_Glossary-and-References.md`](t3-klettern/L-T3-21_kapitel/L-T3-21_90_Glossary-and-References.md) | [PDF](t3-klettern/L-T3-21_kapitel/L-T3-21_90_Glossary-and-References.pdf) |

### L-T1-16 Koop/Rutberg/Malcolm – Training Essentials for Ultrarunning (2. Aufl.) – `t1-ausdauer/L-T1-16_kapitel/`

22 Kapiteldateien, zusammen ca. 164.180 Wörter. Keine Seitenmarken: zitiert wird mit Kapitel und Abschnitt (D-71). Kapitel 16 ist in zwei Teile geteilt.

| Nr. | Titel | Wörter (ca.) | Markdown | Ansichts-PDF |
|---|---|---|---|---|
| 00 | Vorspann und Foreword | 1.100 | [`L-T1-16_00_Vorspann-und-Foreword.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_00_Vorspann-und-Foreword.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_00_Vorspann-und-Foreword.pdf) |
| 01 | The Ultrarunning Revolution | 2.800 | [`L-T1-16_01_The-Ultrarunning-Revolution.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_01_The-Ultrarunning-Revolution.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_01_The-Ultrarunning-Revolution.pdf) |
| 02 | The Physiology of a Better Engine | 11.000 | [`L-T1-16_02_The-Physiology-of-a-Better-Engine.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_02_The-Physiology-of-a-Better-Engine.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_02_The-Physiology-of-a-Better-Engine.pdf) |
| 03 | The Anatomy of Ultramarathon Performance | 5.300 | [`L-T1-16_03_The-Anatomy-of-Ultramarathon-Performance.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_03_The-Anatomy-of-Ultramarathon-Performance.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_03_The-Anatomy-of-Ultramarathon-Performance.pdf) |
| 04 | Failure Points and How to Fix Them | 10.400 | [`L-T1-16_04_Failure-Points-and-How-to-Fix-Them.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_04_Failure-Points-and-How-to-Fix-Them.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_04_Failure-Points-and-How-to-Fix-Them.pdf) |
| 05 | The Four Disciplines of Ultrarunning | 4.200 | [`L-T1-16_05_The-Four-Disciplines-of-Ultrarunning.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_05_The-Four-Disciplines-of-Ultrarunning.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_05_The-Four-Disciplines-of-Ultrarunning.pdf) |
| 06 | Tracking Training in Ultrarunning | 9.500 | [`L-T1-16_06_Tracking-Training-in-Ultrarunning.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_06_Tracking-Training-in-Ultrarunning.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_06_Tracking-Training-in-Ultrarunning.pdf) |
| 07 | Environmental Conditions and How to Adapt | 5.600 | [`L-T1-16_07_Environmental-Conditions-and-How-to-Adapt.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_07_Environmental-Conditions-and-How-to-Adapt.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_07_Environmental-Conditions-and-How-to-Adapt.pdf) |
| 08 | Recovery Modalities and When to Use Them | 8.200 | [`L-T1-16_08_Recovery-Modalities-and-When-to-Use-Them.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_08_Recovery-Modalities-and-When-to-Use-Them.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_08_Recovery-Modalities-and-When-to-Use-Them.pdf) |
| 09 | Train Smarter, Not More: Key Workouts | 6.500 | [`L-T1-16_09_Train-Smarter-Not-More-Key-Workouts.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_09_Train-Smarter-Not-More-Key-Workouts.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_09_Train-Smarter-Not-More-Key-Workouts.pdf) |
| 10 | Organizing Your Training: The Long-Range Plan | 8.100 | [`L-T1-16_10_Organizing-Your-Training-The-Long-Range-Plan.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_10_Organizing-Your-Training-The-Long-Range-Plan.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_10_Organizing-Your-Training-The-Long-Range-Plan.pdf) |
| 11 | Strength Training for Ultrarunning | 3.800 | [`L-T1-16_11_Strength-Training-for-Ultrarunning.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_11_Strength-Training-for-Ultrarunning.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_11_Strength-Training-for-Ultrarunning.pdf) |
| 12 | Activating Your Training: The Short-Range Plan | 5.500 | [`L-T1-16_12_Activating-Your-Training-The-Short-Range-Plan.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_12_Activating-Your-Training-The-Short-Range-Plan.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_12_Activating-Your-Training-The-Short-Range-Plan.pdf) |
| 13 | Fueling and Hydrating for the Long Haul | 10.300 | [`L-T1-16_13_Fueling-and-Hydrating-for-the-Long-Haul.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_13_Fueling-and-Hydrating-for-the-Long-Haul.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_13_Fueling-and-Hydrating-for-the-Long-Haul.pdf) |
| 14 | Adapting Sports Nutrition Guidelines for Ultrarunning Events | 7.800 | [`L-T1-16_14_Adapting-Sports-Nutrition-Guidelines-for-Ultrarunning-Events.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_14_Adapting-Sports-Nutrition-Guidelines-for-Ultrarunning-Events.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_14_Adapting-Sports-Nutrition-Guidelines-for-Ultrarunning-Events.pdf) |
| 15 | Mental Skills for Ultrarunning | 9.600 | [`L-T1-16_15_Mental-Skills-for-Ultrarunning.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_15_Mental-Skills-for-Ultrarunning.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_15_Mental-Skills-for-Ultrarunning.pdf) |
| 16-1 | Creating Your Personal Race Strategies (Teil 1/2) | 7.000 | [`L-T1-16_16-1_Creating-Your-Personal-Race-Strategies.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_16-1_Creating-Your-Personal-Race-Strategies.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_16-1_Creating-Your-Personal-Race-Strategies.pdf) |
| 16-2 | Creating Your Personal Race Strategies (Teil 2/2) | 6.900 | [`L-T1-16_16-2_Creating-Your-Personal-Race-Strategies.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_16-2_Creating-Your-Personal-Race-Strategies.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_16-2_Creating-Your-Personal-Race-Strategies.pdf) |
| 17 | Racing Wisely | 1.900 | [`L-T1-16_17_Racing-Wisely.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_17_Racing-Wisely.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_17_Racing-Wisely.pdf) |
| 18 | Coaching Guide to Major Ultramarathons | 11.800 | [`L-T1-16_18_Coaching-Guide-to-Major-Ultramarathons.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_18_Coaching-Guide-to-Major-Ultramarathons.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_18_Coaching-Guide-to-Major-Ultramarathons.pdf) |
| 90 | References | 13.300 | [`L-T1-16_90_References.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_90_References.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_90_References.pdf) |
| 91 | About the Authors and Index | 13.400 | [`L-T1-16_91_About-the-Authors-and-Index.md`](t1-ausdauer/L-T1-16_kapitel/L-T1-16_91_About-the-Authors-and-Index.md) | [PDF](t1-ausdauer/L-T1-16_kapitel/L-T1-16_91_About-the-Authors-and-Index.pdf) |
