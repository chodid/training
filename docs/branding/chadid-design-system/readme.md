# Chadid Design-System

Persönliches Design-System von **Philipp Chadid** – klinischer Notfallmediziner, Lehrender, Visionär & Entwickler.
Grundlage für Briefkopf, Berichte, E-Mail-Signatur, Website, Slides und eigene Apps.

Charakter: warm / persönlich, minimalistisch, modern. Sprache: Deutsch, Ansprache „Du“.

## Stand
Entwickelt im Dialog, Schritt für Schritt. Entschieden:
- Farben (Primär Pflaume, Akzent Orange, warme Neutrale) → `tokens/colors.css`
- Statusfarben (Variante „warm getönt“: Erfolg, Fehler, Warnung, Info) → `tokens/colors.css`
- Typografie: Young Serif (Display) + Source Sans 3 (Text/UI) + Source Code Pro (Mono) → `tokens/typography.css`

- Abstände, Radien, Tiefe, Bewegung, Icons → `tokens/spacing.css`

- Bildsprache: Fotografie, getönt (Duotone Pflaume / warmes Schwarzweiß)

- Ton & Sprache (aus echten Texten abgeleitet)

- Brief (DIN 5008 Form B) → `ui_kits/dokumente/Brief.dc.html`

Offen (später ergänzbar): Bericht, E-Mail-Signatur, Slides, Website, UI-Komponenten.

## Nutzung in Projekten
- CSS: `<link rel="stylesheet" href="styles.css">` lädt Tokens und Schriften (lokal aus `fonts/`, kein Google-Fonts-Aufruf). Nur Aliase verwenden (`--bg-page`, `--text-primary`, `--action-primary`, `--type-h2` …), nicht die Rohskalen.
- Word/PowerPoint: Farben als Hex → Pflaume 600 #7A5C94, Pflaume 800 #4B3D57, Orange 500 #F26A2E, Orange 700 #A8461F, Neutral 900 #26212A, Neutral 600 #77707A, Papier #F6F3F0.
- Schriften: Young Serif, Source Sans 3, Source Code Pro (SIL OFL), als TTF in `fonts/` (Source Sans 3 und Source Code Pro als variable Schnitte). Lizenztexte `fonts/OFL-*.txt`.
- Für KI-Assistenten: `SKILL.md` fasst alle Regeln zusammen.

## Brief – Regeln
- A4, Ränder 25 mm links / 20 mm rechts, Kopf 45 mm, Falz- und Lochmarken.
- Kopf zentriert: Name in Young Serif 24 pt Pflaume 600 mit Orange-Punkt, darunter Orange-Linie 0,5 pt über die Satzbreite (Abstand Name–Linie 4 mm), darunter Kontaktzeile 8,5 pt Neutral 600 (Abstand 1,5 mm): Adresse · Telefon · Mail, Trenner in Orange. Kein Logo, keine Berufsbezeichnung, keine Web-Adresse.
- Anschriftfeld mit Rücksendezeile 7 pt; Datum rechts „Berlin, 12. Mai 2026“. Kein Zeichen-/Infoblock.
- Betreff in Young Serif 15 pt, Fließtext Source Sans 11 pt / 1,5. Nummerierte Punkte mit fetter Kurzüberschrift, Abschluss „Kurz gesagt:“.
- Fußzeile nur Seitenzahl rechts, 8 pt.
- Word-Version: `ui_kits/dokumente/Brief.docx` (Schriften installieren, siehe `Schriften installieren.md`).

## Ton & Sprache
Quellen: Feedback-Mail Grundkurs Düsseldorf 10/2022, Feedback-Zusammenfassungen Düsseldorf 2022 / Fürth 2023 (PDF), „Vorschlag Schockraumtraining“ (DOCX). Die Quelldateien sind bewusst nicht Teil des Repos.

**Haltung.** Kollegial, offen, lösungsorientiert. Philipp schreibt als Person, nicht als Institution: Ich-Perspektive, eigene Meinung klar markiert („meiner Meinung nach“, „ich denke“), Lob vor Kritik, Kritik immer mit Vorschlag.

**Ansprache.** Du, im Plural „ihr“. Anrede persönlich: „Hallo Anna,“ / „Moin ihr Lieben,“ (intern). Schluss: „Beste Grüße“ oder „Viele Grüße“, dann nur der Vorname „Philipp“. Formell (Behörden, Unbekannte): „Guten Tag Frau Berger,“ … „Mit freundlichen Grüßen, Philipp Chadid“.

**Struktur.** Komplexe Inhalte werden nummeriert, jeder Punkt mit fetter Kurzüberschrift und am Ende einer Zusammenfassungszeile. Originalmuster: „Quintessenz: …“. Als Markenelement: **„Kurz gesagt:“** als Abschluss eines Abschnitts. Bitte um Rückmeldung ist Standard („Über kurzes Feedback würde ich mich freuen“).

**Satzbau.** Im Original lang und verschachtelt, oft konjunktivisch („wäre es schön, wenn“). Für Website, Slides, Vorlagen: dieselbe Wärme, kürzere Sätze. Ein Gedanke pro Satz. Vorschläge als Frage sind erlaubt („Hätten wir nicht …?“), Forderungen nicht.

**Wortwahl.** Fachsprache selbstverständlich, ohne Erklärung im Fachkontext (ACLS, Sono, TRM, Schockraum). Auf Website/für Laien: Fachbegriff plus kurze Erklärung. Anglizismen der Fachkultur bleiben (Debriefing, Flipped Classroom, Train the Trainer). Kein Marketing-Vokabular („innovativ“, „ganzheitlich“, „State of the Art“).

**Humor & Nähe.** Leichte Selbstironie in persönlicher Kommunikation („kranke Kinder, Kita-Ausfall, kurzer Ausflug in den Schnee“) ist Teil der Stimme. Smileys („:)“) nur in Mails an Bekannte. Auf Website, in Berichten, Slides und Vorlagen: keine Emoji.

**Formate.** Datum: 12. Mai 2026 (Brief) / 12.05.2026 (Tabellen). Uhrzeit: 14:00 Uhr. Dauer: 10 Minuten, 4 Stunden (in Tabellen: 10 min, 4 h, mit Leerzeichen). Zahlen bis zwölf ausgeschrieben, außer mit Einheit. Gedankenstrich –, keine Bindestriche als Gedankenstrich. Anführungszeichen „so“.

**Beispiele (Marken-Ton).**
- Betreff: „Schulungskonzept Notfallsimulation, Runde 2“ (Substantiv, kein Ausrufezeichen)
- Website-Claim: „Bessere Entscheidungen, wenn es zählt.“
- Button: „Kurse ansehen“, „Termin anfragen“ (Verb im Infinitiv, kein „Jetzt“, kein „!“)
- Fehlermeldung: „Bitte gültige E-Mail-Adresse eingeben.“ (was zu tun ist, kein „Ups“)
- Erfolgsmeldung: „Gespeichert.“ (ein Wort reicht)
- Abschluss eines Abschnitts: „Kurz gesagt: Dreigruppige Kurse nur mit passendem Dozentenpolster.“

## Farben – Regeln
- Pflaume führt (~85 %), Orange akzentuiert (~15 %). Nie im Gleichgewicht, keine Verläufe zwischen beiden.
- Bildschirm: kein reines Weiß (Seite `--neutral-100`, Karten `--neutral-50`), kein reines Schwarz (Text `--neutral-900`).
- Druck: Hintergrund weiß, Fließtext reines Schwarz; Pflaume bleibt in Linien/Namen.
- Text: Pflaume 600, Orange 700. Pflaume 500 nur für Überschriften/Icons. Stufe 300 beider Farben nur als Fläche oder auf Dunkel.
- Orange nie als Fließtext, nie großflächig in Apps; in Slides/Marketing als Fläche erlaubt.
- Status: gedämpft (Chroma ≈ Pflaume), warm getönt, Info als Petrol. Warnung ist Gelb (nicht Orange), Fehler ein kühles Rot. Orange bleibt „Hinweis“ ohne Wertung. Statusfarben nur für Status, nie dekorativ.
- Löschen/Destruktiv: `--action-danger` (Fehler-Rot), nie Orange.

## Typografie – Regeln
- Young Serif nur für Überschriften, Buttons, Navigation, Name im Briefkopf. Nie unter 13 px, nie als Fließtext.
- Young Serif hat einen Schnitt: Hierarchie entsteht über Größe und Farbe (Pflaume 800 / 600, Orange), nicht über Fettung.
- Source Sans 3 für alles andere (Brief, Bericht, Web, App). 400 normal, 600 betont, kursiv für Titel/Fremdwörter. Eine Textschrift für Bildschirm und Druck.
- Source Code Pro nur für Zahlenkolonnen, Zeitstempel, Code, Seitenzahlen.
- Labels: Source Sans 600, Versalien, 0.14em Sperrung, meist Orange 700.
- Druck: Fließtext 11 pt, Zeilenabstand 1.5, Überschrift 20 pt / Betreff 15 pt.
- Alle drei Schriften SIL OFL, als TTF in `fonts/` (für Web eingebunden, für Word/LibreOffice installierbar).

## Layout & Oberfläche – Regeln
- Abstände auf 4-px-Basis: 4/8/12/16/24/32/48/64. Karten innen 24, Stapel 12, inline 8.
- Radien weich: Buttons/Inputs 6, Karten/Menüs 8, Dialoge 16, Badges 4 oder Pille.
- Tiefe: Karten flach mit 1 px Rahmen (Neutral 300) und Hauch-Schatten; echte Schatten nur für Schwebendes (Menü, Dialog, Toast). Schatten immer pflaumefarben, nie grau.
- Bewegung: 150 ms ease-out für Hover, 200 ms Dialoge, nie über 300 ms. Nichts springt oder wackelt, keine Verkleinerung beim Drücken.
- Hover: Orange-Akzent (Rahmen, Ring, Unterstrich). Fokus: 2 px Orange-Ring mit 2 px Abstand. Gedrückt: eine Farbstufe dunkler. Deaktiviert: 45 % Deckkraft.
- Icons: Tabler Outline (MIT), Strich 2, 20 px in UI, 24 px in Navigation, Farbe Pflaume 600 oder Textfarbe. Keine Emoji.

## Bildsprache – Regeln
- Eigene Fotos: Menschen bei der Arbeit (Simulation, Lehre, Team), nie gestellt lächelnd. Ein Foto pro Fläche, Motiv nie mittig, großzügiger Beschnitt.
- Behandlung Bildschirm/Slides: Duotone Pflaume – Foto entsättigt, `mix-blend-mode: luminosity` auf Pflaume 600 (hell) oder Pflaume 800 (dunkel), Deckkraft 85–90 %. Text auf Foto nur mit Schutzverlauf (Pflaume 900, 90 % → transparent) darunter.
- Behandlung Druck/Bericht: Schwarzweiß, leicht warm (`grayscale(1) sepia(.25)`), kein Duotone (Toner).
- Orange nie im Foto; Orange steht daneben (Linie, Punkt, Label).
- Kein Foto als Hintergrund unter Fließtext. Porträts rund oder als Streifen.
- Fremdfotos nur mit klarer Lizenz (Public Domain / CC0; CC-BY mit Nennung). Klinikfotos: Einwilligungen beachten.

## Logo – Lama-Symbol
- Dateien: `assets/logo/lama-symbol.svg` (Quelle, umschaltbar), `lama-symbol-flaeche.svg`, `lama-symbol-linie.svg`, `lama-symbol-linie-schwarz.svg` (Druck, Auge schwarz), `lama-symbol-linie-negativ.svg` (weiß auf Pflaume 800, Auge weiß), `lama-symbol-linie-dunkel.svg` (Pflaume 300 auf Dunkel, Auge Orange), `lama-symbol-kopf.svg` (Kopf-Ausschnitt für Favicon/Avatar), `lama-symbol-vorschau.png`.
- Varianten und Einsatz: Linie ist die Hauptform (Wortmarke, Web, Slides). Fläche für kleine Größen unter 24 px und App-Icons. Schwarz nur im Druck ohne Farbe. Negativ auf Pflaume 800; dort Auge weiß oder Orange (`-dunkel`). Kopf für quadratische Flächen (Favicon, Profilbild).
- Wortmarke: Lama-Linie links, rechts „Philipp Chadid“ in Young Serif Pflaume 600, darunter „Notfallmedizin · Lehre · Entwicklung“ in Source Sans Neutral 600 mit Trennpunkten in Orange. Symbolhöhe ≈ 2 × Namenshöhe, Abstand Symbol–Text ≈ 0,6 × Namenshöhe. Karte: `guidelines/wortmarke.html`.
- Aufbau (viewBox 820 × 820): Gruppe `fuellung` (Körper inkl. Schwanz, vorderes Ohr), Gruppe `linie` (vier Mittellinien: Körper, vorderes Ohr, Schwanzspitze, Bauchbogen; Strichstärke 29, runde Enden und Ecken), Kreis `auge` (r 16,5).
- Farben in den Dateien: Pflaume 600 `#7A5C94`, Orange 500 `#F26A2E`, Schwarz-Variante Neutral 900 `#26212A`, Negativ `#F4EFF2`, Dunkel-Variante Pflaume 300 `#CDB8D6`. Kein reines Schwarz, kein reines Weiß. Die Werte der Vorlage (`#7A5C93`, `#F97A3C`) wurden auf die Markenwerte gesetzt.
- Umschalten: `display="none"` an `<g id="fuellung">` ergibt die Linienvariante; ohne Attribut die Flächenvariante.
- Füllung und Linie hängen zusammen: Die Füllung endet an den Füßen 7,25 Einheiten (halber Kappenradius) über dem Linienende, damit die runden Zehen sichtbar bleiben. Bei geänderter Strichstärke muss die Füllgrenze mitwandern.
- Farben: Pflaume 600 für Fläche und Linie, Auge Orange. Auf dunklem Grund Linie in Pflaume 300, Auge bleibt Orange. Kein Orange sonst im Symbol.
- Bewusst übernommene Eigenheiten: Knick im Nacken am Ohransatz, Knicke an beiden oberen Beinansätzen, Delle über dem Schwanz. Nicht glätten.

## Dateien
- `styles.css` – Einstiegspunkt (nur `@import`)
- `thumbnail.html` – Kachel des Design-Systems
- `assets/logo/` – Lama-Symbol (SVG Fläche/Linie, PNG-Vorschau)
- `guidelines/logo.html`, `guidelines/wortmarke.html` – Logo- und Wortmarken-Karte
- `fonts/` – Schriftdateien (TTF) und OFL-Lizenztexte, eingebunden über `tokens/fonts.css`
- `tokens/fonts.css` – `@font-face` für die lokalen Schriften
- `tokens/colors.css` – Farbskalen + semantische Aliase, Print-Overrides
- `tokens/typography.css` – Schriftfamilien, Skala, Rollen (`--type-h1` …)
- `tokens/spacing.css` – Abstände, Radien, Rahmen, Schatten, Bewegung, Icon-Größen
- `guidelines/spacing-*.html`, `radius.html`, `elevation.html`, `motion-states.html`, `icons.html`
- `guidelines/type-*.html` – Schriftkarten
- `guidelines/colors-*.html` – Farbkarten für den Design-System-Tab
- `explorations/Farbpaletten.dc.html` – Entscheidungsverlauf Farben (5a gewählt)
- `explorations/Semantik.dc.html` – Entscheidungsverlauf Status (1c gewählt)
- `explorations/Typografie.dc.html` – Entscheidungsverlauf Schrift (Young Serif + Source Sans 3)
- `explorations/Foundations.dc.html` – Entscheidungsverlauf Abstände/Radien/Tiefe/Icons/Bewegung (2b, 3c, 4c, 5c)
- `explorations/Logo.dc.html` – Konstruktionsstudien Lama (historisch; maßgeblich sind die SVGs in `assets/logo/`)
- `ui_kits/dokumente/Brief.dc.html` + `Brief.docx` – Briefvorlage
- `explorations/Brief.dc.html` – Entscheidungsverlauf Briefkopf (9b gewählt)
- `explorations/Bildsprache.dc.html` – Entscheidungsverlauf Bildsprache (1a gewählt; Beispielfotos US DoD, Public Domain)
- `_ds_bundle.js`, `_ds_manifest.json`, `_adherence.oxlintrc.json` – von Claude Design generiert, nicht von Hand ändern

Hinweis: `explorations/Typografie.dc.html` lädt für den Schriftvergleich weiterhin Google Fonts (verworfene Alternativen); das betrifft nur diese Entscheidungsdokumentation, nicht `styles.css`.
