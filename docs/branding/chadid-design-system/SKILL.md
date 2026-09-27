---
name: chadid-design-system
description: Use this skill to generate well-branded interfaces and assets for Philipp Chadid (Notfallmedizin, Lehre, Entwicklung), either for production or throwaway prototypes/mocks. Contains design guidelines, colors, type, fonts, assets and templates. Persönliches Design-System für Briefe, Berichte, Slides, Website und eigene Apps.
user-invocable: true
---

Read the readme.md within this skill, and explore the other available files. If creating visual artifacts (slides, mocks, prototypes), copy assets out and create static HTML files. If working on production code, copy assets and read the rules here. If invoked without other guidance, ask what to build, then act as an expert designer for this brand.

# Chadid Design-System

## Sofort anwenden
1. `styles.css` einbinden (lädt `tokens/*.css` und die lokalen Schriften aus `fonts/`). In DCs: `<link rel="stylesheet" href="../styles.css">` im Helmet.
2. Ausschließlich semantische Aliase nutzen, keine Hex-Werte, keine Rohskalen.
3. Deutsch, Ansprache Du, keine Emoji, keine Ausrufezeichen in Betreff/Buttons.

## Charakter
Warm, persönlich, minimalistisch, modern. Pflaume führt (~85 %), Orange ist Akzent (~15 %). Viel Ruhe, wenig Elemente. Keine Verläufe zwischen den Markenfarben, keine grauen Schatten, kein reines Weiß/Schwarz am Bildschirm.

## Farben (Aliase)
- Flächen: `--bg-page` (Seite), `--bg-surface` (Karten), `--bg-surface-raised` (Menüs/Dialoge), `--bg-subtle` (Pflaume-Hauch), `--bg-inverse` (dunkle Fläche, Pflaume 800), `--bg-accent-soft`
- Text: `--text-primary`, `--text-secondary`, `--text-tertiary`, `--text-brand` (Pflaume 600), `--text-accent` (Orange 700), `--text-on-brand`, `--text-on-inverse`
- Aktionen: `--action-primary` (+ `-hover`, `-active`), `--action-accent` (Orange, sparsam), `--action-danger`, `--link`, `--link-hover`, `--focus-ring` (Orange), `--marker` (Orange)
- Rahmen: `--border-subtle`, `--border-strong`, `--border-brand`
- Status: `--status-success|error|warning|info` je mit `-bg` und `-text`. Warnung ist Gelb, nicht Orange. Statusfarben nie dekorativ.
- Kontrast: Text in Pflaume 600 / Orange 700. Pflaume 500 nur ≥ 18 px. Stufe 300 nur als Fläche oder auf Dunkel. Orange nie als Fließtext.
- Druck: `@media print` setzt Papier weiß und Text schwarz automatisch.

## Typografie
- `--font-display` Young Serif: Überschriften, Buttons, Navigation, Name. Ein Schnitt, Hierarchie über Größe/Farbe. Nie < 13 px, nie Fließtext.
- `--font-body` Source Sans 3: alles andere. 400 / 600. `--font-mono` Source Code Pro nur für Zahlen, Zeitstempel, Code.
- Rollen: `font: var(--type-h1|h2|h3|h4|button|body|body-sm|label|mono)`. Labels: Versalien, `--tracking-label`, meist `--text-accent`.
- Druck: 11 pt / 1,5; H1 20 pt; Betreff 15 pt.

## Layout
- Abstände `--space-1…16` (4-px-Basis). Karten innen `--space-card` 24, Stapel 12, inline 8.
- Radien: `--radius-md` 6 Buttons/Inputs, `--radius-lg` 8 Karten/Menüs, `--radius-xl` 16 Dialoge, `--radius-sm`/`--radius-full` Badges.
- Tiefe: Karten `border: var(--border-default)` + `--shadow-card`. `--shadow-menu`, `--shadow-dialog` nur für Schwebendes.
- Zustände: Hover Orange-Akzent (Rahmen/Ring), Fokus `outline: var(--border-focus); outline-offset: 2px`, Gedrückt eine Stufe dunkler, Deaktiviert `--opacity-disabled`.
- Bewegung: `--transition` (150 ms ease-out), Dialoge 200 ms, nie > 300 ms. Kein Skalieren beim Drücken.
- Icons: Tabler Outline, Strich 2, `--icon-size` 20 / `--icon-size-lg` 24, Farbe Pflaume 600 oder Textfarbe.

## Bildsprache
Eigene Fotos von Arbeit (Simulation, Lehre, Team), nie gestellt. Bildschirm/Slides: Duotone Pflaume (`filter: grayscale(1); mix-blend-mode: luminosity` auf Pflaume 600 oder 800, 85–90 %), Text nur über Schutzverlauf. Druck: Schwarzweiß warm (`grayscale(1) sepia(.25)`). Orange nie im Foto. Kein Foto unter Fließtext.

## Logo
Lama-Symbol in `assets/logo/`: `lama-symbol-linie.svg` (Hauptform), `-flaeche` (klein, App-Icon), `-linie-schwarz` (Druck), `-linie-negativ` / `-linie-dunkel` (auf Pflaume 800), `-kopf` (Favicon, Avatar). Quelle `lama-symbol.svg`, Umschalten per `display="none"` an `<g id="fuellung">`. Wortmarke: Symbol links, „Philipp Chadid“ Young Serif Pflaume 600, darunter „Notfallmedizin · Lehre · Entwicklung“ Source Sans Neutral 600, Punkte Orange (`guidelines/wortmarke.html`). Pflaume 600, Auge Orange, Strichstärke 29 auf 820er-Raster. Fläche und Linie hängen zusammen (Füllgrenze an den Füßen = halber Kappenradius über dem Linienende), Strichstärke nicht separat ändern. Wortzeichen: „Philipp Chadid“ in Young Serif, Pflaume 600, mit Punkt in Orange 500. Im Brief kein Logo.

## Ton & Sprache
Ich-Perspektive, kollegial, Lob vor Kritik, Kritik mit Vorschlag. Nummerierte Punkte mit fetter Kurzüberschrift, Abschluss „Kurz gesagt: …“. Anrede „Hallo Anna,“, Schluss „Beste Grüße, Philipp“ (formell: „Guten Tag Frau Berger,“ / „Mit freundlichen Grüßen, Philipp Chadid“). Buttons als Infinitiv („Kurse ansehen“), Fehlermeldungen sagen, was zu tun ist, Erfolg kurz („Gespeichert.“). Kein Marketing-Vokabular. Formate: 12. Mai 2026, 14:00 Uhr, 10 min, „Anführungszeichen“, Gedankenstrich –.

## Vorlagen
- Brief DIN 5008: `ui_kits/dokumente/Brief.dc.html` (druckbar) und `Brief.docx`. Kopf zentriert: Name 24 pt Pflaume 600 + Orange-Punkt, Orange-Linie 0,5 pt (4 mm darunter), Kontaktzeile 8,5 pt Neutral 600 (1,5 mm darunter), Trenner in Orange. Kein Logo, keine Berufszeile, keine Web-Adresse. Datum rechts, Fuß nur Seitenzahl.

## Referenz
Regeln ausführlich in `readme.md`. Farbkarten, Schriftkarten, Abstände, Zustände, Icons, Bildbehandlung, Ton: `guidelines/*.html`. Entscheidungsverläufe: `explorations/*.dc.html`.
