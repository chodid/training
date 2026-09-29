---
titel: Literaturverzeichnis (Volltexte)
bezug: docs/konzept/konzept-ki-personal-trainer.md, Abschnitte 13.1, 13.2, 13.4; D-31, D-51, D-71, D-79
stand: 2026-09-29
---

# Literatur-Volltexte

Ablage der Volltexte, laut D-31 nur in einem privaten Repo. Das Repo ist derzeit öffentlich (Entscheidung des Athleten 2026-09-29, offener Widerspruch zu D-31, siehe Konzept AP-06). **Nie ins Projektwissen hochladen.** Dort liegen nur die Wissenskarten aus `docs/wissen/` (D-12, 13.1).
Maßgeblich für Auswahl, Status und Zitierfassung ist Konzept 13.2. Diese Datei zeigt nur, welche Datei zu welcher ID gehört.

## Ablage und Dateinamen (D-51, D-71)

- Unterordner je Block: `uebergreifend/`, `t1-ausdauer/`, `t2-kraft/`, `t3-klettern/`, `r-reha/` (Block R), künftig `t4-beweglichkeit/` (D-79). Eine Datei liegt in dem Block, in dem ihre ID definiert ist; L-P08 liegt also unter `uebergreifend/`, obwohl T2 per L-T2-01 darauf verweist.
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
| L-A01 | B | Kenney, Wilmore, Costill – Physiology of Sport and Exercise, **7. Aufl. 2019** | [`L-A01_Kenney-2019_Physiology-of-Sport-and-Exercise_7ed.pdf`](uebergreifend/L-A01_Kenney-2019_Physiology-of-Sport-and-Exercise_7ed.pdf) | 1379 | Ausgewählt ist die 8. Aufl. (2022). Die 7. Aufl. gilt vorläufig, die 8./9. Aufl. bleibt auf der Beschaffungsliste (D-51). E-Book, Kapitel-PDFs in `L-A01_kapitel/` |
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
| L-T2-23 | A | Lopez et al. 2021 – Resistance Training Load Effects on Muscle Hypertrophy and Strength Gain: Systematic Review and… | [`L-T2-23_Lopez-2021_Training-Load-Hypertrophy-Strength.pdf`](t2-kraft/L-T2-23_Lopez-2021_Training-Load-Hypertrophy-Strength.pdf) | 13 | CC BY-NC-ND 4.0; Corrigendum fehlt |
| L-T2-24 | A | Lopes et al. 2019 – Effects of training with elastic resistance versus conventional resistance on muscular strength… | [`L-T2-24_Lopes-2019_Elastic-vs-Conventional-Resistance.pdf`](t2-kraft/L-T2-24_Lopes-2019_Elastic-vs-Conventional-Resistance.pdf) | 7 | CC BY-NC 4.0; Corrigendum fehlt |
| L-T2-25 | A | Lundberg et al. 2022 – The Effects of Concurrent Aerobic and Strength Training on Muscle Fiber Hypertrophy: A Systemat… | [`L-T2-25_Lundberg-2022_Concurrent-Training-Fiber-Hypertrophy.pdf`](t2-kraft/L-T2-25_Lundberg-2022_Concurrent-Training-Fiber-Hypertrophy.pdf) | 13 | CC BY 4.0 |
| L-T2-26 | A | Monserdà-Vilaró et al. 2023 – Effects of Concurrent Resistance and Endurance Training Using Continuous or Intermittent Protoc… | [`L-T2-26_Monserda-Vilaro-2023_Concurrent-Continuous-vs-Intermittent.pdf`](t2-kraft/L-T2-26_Monserda-Vilaro-2023_Concurrent-Continuous-vs-Intermittent.pdf) | 22 |  |
| L-T2-27 | A | Schoenfeld et al. 2019 – How many times per week should a muscle be trained to maximize muscle hypertrophy? A systematic… (optional) | [`L-T2-27_Schoenfeld-2019_Training-Frequency-Hypertrophy.pdf`](t2-kraft/L-T2-27_Schoenfeld-2019_Training-Frequency-Hypertrophy.pdf) | 11 |  |
| L-T2-28 | A | Refalo et al. 2021 – Influence of resistance training load on measures of skeletal muscle hypertrophy and improvemen… (optional) | [`L-T2-28_Refalo-2021_Training-Load-Hypertrophy.pdf`](t2-kraft/L-T2-28_Refalo-2021_Training-Load-Hypertrophy.pdf) | 24 |  |
| L-T2-29 | A | Carvalho et al. 2022 – Muscle hypertrophy and strength gains after resistance training with different volume-matched l… (optional) | [`L-T2-29_Carvalho-2022_Volume-Matched-Loads-Hypertrophy.pdf`](t2-kraft/L-T2-29_Carvalho-2022_Volume-Matched-Loads-Hypertrophy.pdf) | 58 | Autorenmanuskript (Seitenzahlen nicht zitierfähig) |
| L-T2-30 | A | Grgic et al. 2022 – Effects of resistance training performed to repetition failure or non-failure on muscular stren… (optional) | [`L-T2-30_Grgic-2022_Failure-vs-Non-Failure.pdf`](t2-kraft/L-T2-30_Grgic-2022_Failure-vs-Non-Failure.pdf) | 10 |  |
| L-T2-31 | A | Wilson et al. 2012 – Concurrent training: a meta-analysis examining interference of aerobic and resistance exercises (optional) | [`L-T2-31_Wilson-2012_Concurrent-Training-Interference.pdf`](t2-kraft/L-T2-31_Wilson-2012_Concurrent-Training-Interference.pdf) | 15 |  |
| L-T2-32 | A | Sabag et al. 2018 – The compatibility of concurrent high intensity interval training and resistance training for mu… (optional) | [`L-T2-32_Sabag-2018_Concurrent-HIIT-and-Resistance.pdf`](t2-kraft/L-T2-32_Sabag-2018_Concurrent-HIIT-and-Resistance.pdf) | 13 |  |

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
| L-R-12 | A | Backman & Danielson 2011 – Low range of ankle dorsiflexion predisposes for patellar tendinopathy in junior elite basketbal… (optional) | [`L-R-12_Backman-2011_Ankle-Dorsiflexion-Patellar-Tendinopathy.pdf`](r-reha/L-R-12_Backman-2011_Ankle-Dorsiflexion-Patellar-Tendinopathy.pdf) | 9 |  |
| L-R-13 | A | Martin et al. 2021 – Lateral Ankle Ligament Sprains (JOSPT-Leitlinie) | [`L-R-13_Martin-2021_Lateral-Ankle-Sprain-Guideline.pdf`](r-reha/L-R-13_Martin-2021_Lateral-Ankle-Sprain-Guideline.pdf) | 80 |  |
| L-R-15 | A | Schiftan et al. 2015 – The effectiveness of proprioceptive training in preventing ankle sprains in sporting population… | [`L-R-15_Schiftan-2015_Proprioceptive-Training-Ankle-Sprain.pdf`](r-reha/L-R-15_Schiftan-2015_Proprioceptive-Training-Ankle-Sprain.pdf) | 7 |  |
| L-R-16 | A | Tang et al. 2024 – Meta-analysis of the dosage of balance training on ankle function and dynamic balance ability i… | [`L-R-16_Tang-2024_Balance-Training-Dosage-Ankle.pdf`](r-reha/L-R-16_Tang-2024_Balance-Training-Dosage-Ankle.pdf) | 20 | CC BY-NC-ND 4.0 |
| L-R-18 | A | Nielsen et al. 2014 – Excessive progression in weekly running distance and risk of running-related injuries: an assoc… (optional) | [`L-R-18_Nielsen-2014_Running-Distance-Progression-Injuries.pdf`](r-reha/L-R-18_Nielsen-2014_Running-Distance-Progression-Injuries.pdf) | 25 | Autorenmanuskript (Seitenzahlen nicht zitierfähig) |
| L-R-19 | A | Kiers et al. 2012 – Ankle proprioception is not targeted by exercises on an unstable surface (optional) | [`L-R-19_Kiers-2012_Unstable-Surface-Ankle-Proprioception.pdf`](r-reha/L-R-19_Kiers-2012_Unstable-Surface-Ankle-Proprioception.pdf) | 9 |  |
| L-R-20 | A | Fakontis et al. 2023 – Efficacy of resistance training with elastic bands compared to proprioceptive training on balan… (optional) | [`L-R-20_Fakontis-2023_Elastic-Bands-vs-Proprioceptive-Training.pdf`](r-reha/L-R-20_Fakontis-2023_Elastic-Bands-vs-Proprioceptive-Training.pdf) | 11 |  |
| L-R-22 | A | Delahunt et al. 2018 – Clinical assessment of acute lateral ankle sprain injuries (ROAST): 2019 consensus statement an… (optional) | [`L-R-22_Delahunt-2018_ROAST-Consensus.pdf`](r-reha/L-R-22_Delahunt-2018_ROAST-Consensus.pdf) | 7 |  |
| L-R-23 | A | Lopes et al. 2025 – Exercise for patellar tendinopathy | [`L-R-23_Lopes-2025_Exercise-for-Patellar-Tendinopathy-Cochrane.pdf`](r-reha/L-R-23_Lopes-2025_Exercise-for-Patellar-Tendinopathy-Cochrane.pdf) | 65 |  |
| L-R-24 | A | Schuster Brandt Frandsen et al. 2025 – How much running is too much? Identifying high-risk running sessions in a 5200-person cohort st… | [`L-R-24_SchusterBrandtFrandsen-2025_High-Risk-Running-Sessions.pdf`](r-reha/L-R-24_SchusterBrandtFrandsen-2025_High-Risk-Running-Sessions.pdf) | 8 | CC BY-NC 4.0 |
| L-R-25 | A | Wagemans et al. 2022 – Exercise-based rehabilitation reduces reinjury following acute lateral ankle sprain: A systemat… | [`L-R-25_Wagemans-2022_Rehabilitation-Reinjury-Ankle-Sprain.pdf`](r-reha/L-R-25_Wagemans-2022_Rehabilitation-Reinjury-Ankle-Sprain.pdf) | 20 |  |
| L-R-26 | A | Doherty et al. 2017 – Ankle Sprain, Overview of Reviews | [`L-R-26_Doherty-2017_Ankle-Sprain-Overview-of-Reviews.pdf`](r-reha/L-R-26_Doherty-2017_Ankle-Sprain-Overview-of-Reviews.pdf) | 18 |  |
| L-R-27 | A | Deng et al. 2025 – Long-term Prognosis of Athletes With Patellar Tendinopathy Receiving Physical Therapy: Patient-… (optional) | [`L-R-27_Deng-2025_Patellar-Tendinopathy-Long-Term-Prognosis.pdf`](r-reha/L-R-27_Deng-2025_Patellar-Tendinopathy-Long-Term-Prognosis.pdf) | 9 |  |
| L-R-28 | A | Hjortshoej et al. 2025 – Effect of Low-Load Blood-Flow Restricted Training Versus Heavy Slow Resistance Training in Unil… (optional) | [`L-R-28_Hjortshoej-2025_BFR-vs-HSR-Patellar-Tendinopathy.pdf`](r-reha/L-R-28_Hjortshoej-2025_BFR-vs-HSR-Patellar-Tendinopathy.pdf) | 12 |  |

Summe: 90 Werke (davon 9 Bücher mit Kapitel-PDFs, 4 EPUBs mit Kapitel-Markdown und Ansichts-PDFs).

## Noch nicht vorhanden

Stand nach Beschaffungsliste 13.4 (Übergaben AP-06 Teil A bis D, Literatur-Nachsteuerung und T4 Teil A eingearbeitet).

### Kaufen oder über die Bibliothek (nicht frei verfügbar)

| Prio | ID | Quelle | Wofür |
|---|---|---|---|
| 1 | L-A01 | Kenney/Wilmore/Costill, 8. (2022) oder 9. Aufl. (2024) (Buch) | ersetzt die vorläufige 7. Aufl. (D-51) |
| 2 | L-T3-16 | Bechtel, Logical Progression, 2. Aufl. (Buch) | Stufe C, Planungsvorlage; Kindle ungeeignet |
| 2 | L-T4-05 | Thomas et al. 2018, Int J Sports Med 39(4):243–254 | T4 Dosis (Wochendehnzeit, V-19) |
| 2 | L-T4-06 | Behm et al. 2016, Appl Physiol Nutr Metab 41(1):1–11 | T4 Dehnen im Aufwärmen |
| 2 | L-T4-14 | Lauersen et al. 2014, Br J Sports Med 48(11):871–877 | T4 Grenzen – Dehnen ohne Präventionseffekt |
| 2 | L-T4-16 | Herbert et al. 2011, Cochrane Database Syst Rev CD004577 | T4 Regeneration/Muskelkater (Abstract frei) |
| 2 | L-T4-17 | Behm et al. 2026, Eur J Appl Physiol 126(6):2977–2987 | T4 Wohlbefinden |
| 2 | L-T4-19 | Winters et al. 2004, Phys Ther 84(9):800–807 | T4 Hüftbeuger (DOI offen, V-22) |
| 2 | L-T4-22 | Witvrouw et al. 2001, Am J Sports Med 29(2):190–195 | T4 Knie/Patellasehne |
| 3 | L-T3-09 | Hörst, Training for Climbing, Neuauflage (Buch) | nach Erscheinen (angekündigt 02.03.2027), zusätzlich zur vorhandenen 3. Aufl. |

### Frei verfügbar (PubMed Central)

| ID | Quelle | PMC | Hinweis |
|---|---|---|---|
| L-R-10 | Clifford et al. 2020, BMJ Open Sport Exerc Med | PMC7406028 | Block R, optional |
| L-R-11 | Sprague et al. 2018, Br J Sports Med | PMC6269217 | Block R, optional |
| L-R-14 | Hupperets et al. 2009, BMJ | PMC2714677 | Block R |
| L-R-17 | Donovan et al. 2016, J Athl Train | PMC4852529 | Block R |
| L-R-21 | Giboin et al. 2018, PLoS One | PMC6261037 | Block R, optional |
| L-T4-01 | Warneke et al. 2025, J Sport Health Sci (Delphi-Konsens Dehnen) | PMC12305623 | T4 Anker (D-79) |
| L-T4-02 | Konrad et al. 2024, J Sport Health Sci | PMC10980866 | T4 |
| L-T4-03 | Oba et al. 2026, Sports Med Open | PMC13356130 | T4; Artikelnummer offen (V-23) |
| L-T4-04 | Arntz et al. 2023, Sports Med | PMC9935669 | T4 |
| L-T4-08 | Warneke et al. 2024, J Sport Health Sci | PMC11184403 | T4 |
| L-T4-10 | Alizadeh et al. 2023, Sports Med | PMC9935664 | T4 |
| L-T4-12 | Konrad et al. 2024, Sports Med | PMC11393112 | T4 |

### Nur bei Bedarf

- Corrigenda zu L-T2-23 (Med Sci Sports Exerc. 2022;54(2):370) und L-T2-24 (SAGE Open Med, 2020) – in den vorhandenen PDFs nicht enthalten
- T4 Beweglichkeit (optional, D-79): L-T4-07, -09, -11, -13, -15, -18, -20, -21, -23, -25 bis -31 (in PMC: -07, -09, -11, -15, -18, -21, -27 bis -30); Buch L-T4-32 Behm, The Science and Physiology of Flexibility and Stretching, 2. Aufl. (Format vor Kauf prüfen, V-21)
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
