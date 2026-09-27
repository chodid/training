---
titel: Branding-Dokument – Trainings-Web-App
bezug: docs/konzept/konzept-ki-personal-trainer.md (D-19, D-37, Abschnitt 10), AP-01a
dokumentstand: 2026-09-27
status: abgenommen
abgenommen_am: 2026-09-27
erstellt_von: Fable (Design-Mockups, Rollenverteilung Abschnitt 0)
---

# Branding-Dokument

Gilt für alle Webseiten-Screens ab AP-01 (S0, S1, S7) über AP-04 (S2–S5, Einstellungen) bis AP-09 (S6). Die Code-Instanz liest dieses Dokument vor Beginn von AP-01.

## 1. Grundlage: Chadid Design-System

Alle Gestaltungsvorgaben des Athleten liegen in `docs/branding/chadid-design-system/`:

| Datei | Inhalt |
|---|---|
| `readme.md` | Regeln zu Farben, Typografie, Layout, Bildsprache, Logo, Ton |
| `SKILL.md` | Kurzfassung für KI-Assistenten |
| `styles.css` | Einstiegspunkt, lädt `tokens/fonts.css`, `colors.css`, `typography.css`, `spacing.css` |
| `fonts/` | Young Serif, Source Sans 3, Source Code Pro als TTF (SIL OFL) |
| `assets/logo/` | Lama-Symbol in sechs Varianten, `lama-symbol-kopf.svg` für App-Icon und Favicon |
| `guidelines/*.html` | Spezimen-Karten (Farben, Schrift, Abstände, Zustände, Icons) |

Kernregeln, die in der App überall gelten:

- Nur semantische Aliase verwenden (`--bg-page`, `--text-primary`, `--action-primary`, `--status-*` …), keine Hex-Werte, keine Rohskalen.
- Pflaume führt, Orange akzentuiert (Fokusring, Marker, Hinweise ohne Wertung). Kein reines Weiß oder Schwarz am Bildschirm.
- Young Serif nur für Überschriften, Buttons, Navigation und den Titel „Training“, nie unter 13 px, nie als Fließtext. Source Sans 3 für alles andere, Source Code Pro für Zahlenkolonnen, Zeitstempel, Code.
- Statusfarben nur für Status (Erfolg, Fehler, Warnung, Info). Orange bleibt Hinweis ohne Wertung (z. B. „Feedback offen“). Löschen und Widerrufen in Fehler-Rot, nie Orange.
- Karten flach mit 1-px-Rahmen, Schatten nur für Schwebendes (Menü, Dialog), immer pflaumefarben.
- Hover Orange-Akzent am Rahmen, Fokus 2-px-Orange-Ring mit 2 px Abstand, Gedrückt eine Stufe dunkler, Deaktiviert 45 % Deckkraft. 150 ms ease-out, nichts springt.
- Deutsch, Du, keine Emoji, keine Ausrufezeichen. Buttons als Infinitiv („Speichern“, „Freigeben“), Fehlermeldungen sagen, was zu tun ist.

## 2. Mockups

Ablage: `docs/branding/mockups/`. Jede Seite ist eine eigenständige HTML-Datei, die `../chadid-design-system/styles.css` und `app.css` lädt. `index.html` zeigt jede Seite in vier Größen (Smartphone 390 × 844, Tablet hoch 834 × 1112, Tablet quer 1112 × 834, Desktop 1280 × 800). Screenshots für Smartphone und Desktop liegen in `screenshots/`.

| Screen | Datei | Zustände (Parameter nur im Mockup) | AP |
|---|---|---|---|
| S0 Setup | `s0-setup.html` | – | AP-01 |
| S1 Login | `s1-login.html` | `?state=fehler`, `?state=gesperrt` | AP-01 |
| S7 OAuth-Freigabe | `s7-freigabe.html` | – | AP-01 |
| S2 Woche | `s2-woche.html` | `?state=leer` | AP-04 |
| S3 Einheit | `s3-einheit.html` | `?typ=kraft` (Standard), `?typ=ausdauer`, `?typ=klettern`, zusätzlich `&schmerz=ja` | AP-04 |
| S4 Check-in | `s4-checkin.html` | `?schmerz=ja` | AP-04 |
| S5 Schmerz | `s5-schmerz.html` | – | AP-04 |
| S6 Verlauf | `s6-verlauf.html` | – | AP-09 |
| S8 Einstellungen | `s8-einstellungen.html` | `?state=update` | AP-04 (Backup/Update AP-10) |

Die Beispieldaten (Block 2 „Grundlage Herbst“, KW 39, Athlet „philipp“) sind erfunden und zeigen typische Fälle: erledigte Einheit ohne Feedback, teilweise erledigte Krafteinheit, verschobene Ausdauereinheit, Ruhetag, wiederholte Schmerzmeldung an einer Stelle.

## 3. Layout und Navigation

| Breite | Rahmen | Navigation |
|---|---|---|
| < 768 px (Smartphone) | Kopfzeile mit Lama-Kopf + „Training“, Inhalt, Tab-Leiste unten (sticky, mit `safe-area-inset-bottom`) | vier Tabs: Woche, Check-in, Verlauf, Einstellungen; Icon über Text |
| 768–1023 px (Tablet) | Schmale Leiste links (96 px) mit Lama-Kopf, Kopfzeile mit Seitentitel | Icon über Text, aktives Ziel mit `--bg-subtle` hinterlegt |
| ≥ 1024 px (Desktop, Tablet quer) | Seitenleiste 240 px mit Wortzeichen „Training“, Kopfzeile mit Seitentitel und Aktionen | Icon neben Text, Fußzeile mit Benutzer und Blockstand |

- Inhaltsbreite: Formulare max. 760 px, breite Seiten (Woche, Einheit, Verlauf) max. 1160 px, jeweils zentriert.
- Woche: bis 1023 px eine Tagesliste, 1024–1279 px zwei Spalten, ab 1280 px sieben Spalten (kompakt, Bereichswort ausgeblendet, Icon trägt den Typ).
- Einheit: ab 1024 px zweispaltig, Plan links (3/5), Rückmeldung rechts (2/5, sticky).
- Auth-Seiten (S0, S1, S7) haben keine Navigation: eine zentrierte Karte (max. 420 px) mit Lama-Kopf und „Training“.
- Primäraktionen auf dem Smartphone in einer sticky Leiste am unteren Rand des Formulars (`.actions-sticky`), auf Tablet und Desktop normal im Fluss.
- Kein horizontaler Scrollbereich in keiner Größe (geprüft, Abschnitt 6). Tabellen mit vielen Spalten scrollen innerhalb ihrer Karte.

## 4. Bausteine (`mockups/app.css`)

Die Bausteine bauen ausschließlich auf den Aliasen des Design-Systems auf. Sie bleiben Teil des Branding-Dokuments; das Design-System selbst wird nicht erweitert.

| Baustein | Klassen | Regeln |
|---|---|---|
| Button | `.btn` + `.btn-primary` / `.btn-secondary` / `.btn-ghost` / `.btn-danger`, `.btn-icon`, `.btn-block`, `.btn-row` | Young Serif, Höhe ≥ 44 px, Radius 6. Genau eine Primäraktion je Ansicht. Danger nur für Widerrufen, Löschen. |
| Feld | `.field` > `label` + `.input` / `.select` (in `.select-wrap`) / `.textarea` + `.hint` | Höhe ≥ 44 px, Rahmen Neutral 400, Hover/Fokus Orange. Fehler: `.field.invalid` (roter Rahmen, Hinweistext in Fehler-Rot). Zahlen mit `.mono` und `inputmode="numeric"`. |
| Skalenwahl | `.scale` (1–5), `.scale.scale-11` (0–10) mit Radio-Inputs, `.scale-ends` | Jede Stufe ein Touch-Ziel ≥ 48 px, gewählt Pflaume 600 gefüllt. 0–10 bricht unter 480 px in 6 + 5 um. Endpunkte immer beschriftet („1 sehr gut … 5 sehr schlecht“). |
| Segmentwahl | `.seg`, `.seg.wrap` mit Radio-Inputs | Für Ja/Nein, Seite, Zeitpunkt, Status. Gewählt: `--bg-subtle` + Pflaume-Rahmen. |
| Karte | `.card`, `.card-head`, `.subcard` | Flach, Rahmen Neutral 300, Radius 8, innen 16 px (Smartphone) / 24 px. |
| Hinweis | `.alert` + `-error` / `-warning` / `-success` / `-info` / `-hint` | Linker 3-px-Balken, Icon, fette Kurzaussage, dann Text. `-hint` (Orange) für Hinweise ohne Wertung. |
| Marke | `.badge` + `-neutral` / `-success` / `-warning` / `-error` / `-info` / `-hint` / `-brand` | Status je Einheit: geplant neutral, erledigt Erfolg, teilweise Warnung, verschoben Info, ausgelassen neutral; „Feedback“ als Hinweis. |
| Kennzahl | `.stat` (`.l` Beschriftung, `.v` Wert) | Wert in Source Sans 600, nie in Young Serif. |
| Woche | `.week-nav`, `.week-sum`, `.days`, `.day`, `.day.today`, `.day-head`, `.checkin.done/.open/.none`, `.session`, `.rest`, `.empty` | Heute: Orange-Punkt vor dem Tag, Pflaume-Rahmen. Einheit: 40-px-Kachel mit Typ-Icon, Titel + Priorität, Meta, Statusmarke, Chevron. |
| Einheit | `.page-head`, `.exercise` (`.name`, `.soll`, `.ist`), `.two-col`, `.activity`, `.zones` | Ist-Felder mit Soll vorbelegt. Zonenbalken in Pflaume-Stufen 300–800. |
| Verlauf | `.tiles`, `.multiples`, `.chart`, `.bars`, `.xaxis`, `.heat`, `.heat-scale` | Siehe Abschnitt 5. |
| Einstellungen | `.list`, `.list-item` | Zeile: Titel + Nebentext links, Aktion oder Marke rechts. |
| Icons | `<svg class="ic"><use href="#i-name"/></svg>`, `.ic-sm` 16, `.ic` 20, `.ic-lg` 24 | Tabler Outline 3.21.0 (MIT), lokal in `icons/`, als Inline-Sprite über `icons.js`. Farbe über `currentColor`. |

Typ-Icons: Ausdauer `run`, Kraft `barbell`, Klettern `mountain`, Haltung `yoga`, Mobilität `stretching-2`, Ruhe `zzz`. Navigation: `calendar-week`, `checkup-list`, `chart-line`, `settings`.

## 5. Diagramme (S6)

- **Wochenlast je Bereich**: vier kleine Vielfache (Ausdauer, Klettern, Kraft, Haltung/Mobilität) auf einer gemeinsamen Achse 0–900, eine Farbe (Pflaume 600), laufende Woche Pflaume 800. Balken ≤ 24 px, oben 4 px gerundet, unten an der Grundlinie. Keine zweite Achse, keine gestapelten Farben: die Markenpalette hat nur zwei Hues, und die Statusfarben dürfen keine Serien tragen.
- **Schmerz je Ort**: Raster Ort × Woche, Zellwert = stärkste Meldung der Woche, sequenzielle Pflaume-Skala (100 → 800), leer = Seitenhintergrund mit Rahmen. Tooltip beim Berühren, Legende darunter.
- **Tabelle**: alle Werte zusätzlich als Tabelle (Source Code Pro, tabellarische Ziffern), auch für Druck und Kopie.
- Werte tragen Textfarben, nie die Datenfarbe. Kennzahlen in Source Sans 600.

## 6. Entscheidungen im Rahmen von AP-01a

| id | entscheidung | begründung | datum |
|---|---|---|---|
| B-01 | Umfang: alle Screens aus Abschnitt 10 inkl. S6 Verlauf sowie eine Einstellungen-Seite (Konto, Backup, Update, Verbindungen). | Wunsch des Athleten; AP-04 und AP-10 verlangen den Bereich „Einstellungen“ ohnehin. | 2026-09-27 |
| B-02 | Navigation: Tab-Leiste unten (Smartphone), Leiste links (Tablet), Seitenleiste (Desktop). Zusätzlich zur Vorgabe „mobil und Tablet“ eine **Desktop-Ansicht**. | Wunsch des Athleten. D-19 wird um Desktop ergänzt. | 2026-09-27 |
| B-03 | App-Kennung: Lama-Kopf (`lama-symbol-kopf.svg`) + „Training“ in Young Serif; als Favicon und App-Icon der Lama-Kopf. Die volle Wortmarke „Philipp Chadid“ wird in der App nicht verwendet. | Werkzeug für eine Person, schlank auf dem Smartphone. | 2026-09-27 |
| B-04 | Nur helles Farbschema. | Das Design-System definiert keine dunklen Aliase; ein Dunkelmodus gehört, wenn überhaupt, ins Design-System. | 2026-09-27 |
| B-05 | Icons als lokales Inline-Sprite (`icons.js`), nicht per CSS-Mask oder CDN. | CSS-Mask lädt unter `file://` nicht (CORS), CDN ist im Betrieb nicht nötig. | 2026-09-27 |
| B-06 | Diagramme in einer Farbe (Pflaume) als kleine Vielfache statt gestapelter Mehrfarbenbalken. | Markenpalette hat nur Pflaume und Orange; Orange ist Akzent, Statusfarben sind reserviert. | 2026-09-27 |
| B-07 | Schriften lokal aus `chadid-design-system/fonts/`, kein Google-Fonts-Aufruf. | Entscheidung des Athleten beim Ablegen des Design-Systems. | 2026-09-27 |

## 7. Umsetzungshinweise für die Code-Instanz

1. **Assets ausliefern**: `chadid-design-system/styles.css` samt `tokens/` und `fonts/` sowie `mockups/app.css`, `mockups/icons.js` und `assets/logo/lama-symbol-kopf.svg` nach `server/public/assets/` übernehmen (Build-Schritt oder Kopie; Pfade in `fonts.css` sind relativ zu `tokens/`). Keine externen Aufrufe (kein Google Fonts, kein CDN).
2. **Templates**: Die HTML-Struktur der Mockups ist als Vorlage für die serverseitig gerenderten PHP-Seiten gedacht (Seitenrahmen `body.app` mit `header.topbar`, `nav.nav`, `main.main`; Auth-Seiten `body.auth`). Die Mockup-Parameter (`?state=`, `?typ=`) sind nicht zu übernehmen; die Zustände kommen aus der Anwendung.
3. **Formulare ohne JavaScript nutzbar**: Skalen und Segmente sind echte Radio-Inputs, Selects echte Selects. Das Aufklappen der Schmerz-Kurzform (S3, S4) darf per JS erfolgen, muss aber ohne JS als sichtbarer Block funktionieren.
4. **Touch-Ziele**: Buttons, Felder, Skalenstufen, Tabs und Listenzeilen mindestens 44 px hoch. Skalenstufen 48 px.
5. **Zustände**: Fehler immer als `.alert.alert-error` über dem Formular plus `.field.invalid` am Feld. Sperre (D-33) als `.alert.alert-warning` mit Uhrzeit des nächsten Versuchs, Passwortfeld ausgeblendet. Leerzustände mit Erklärung und einem Weg weiter (S2 leer).
6. **Status je Einheit**: Marken wie in Abschnitt 4; „Feedback offen“ als Orange-Hinweis, zusätzlich als Alert oberhalb der Woche, solange eine erledigte Einheit ohne Rückmeldung ist.
7. **sRPE** wird serverseitig berechnet und nur angezeigt (D-16, Abschnitt 11); kein Eingabefeld.
8. **Web-App-Manifest** (AP-04): Name „Training“, `theme_color` Pflaume 600 `#7A5C94`, `background_color` Papier `#F6F3F0`, Icons aus `lama-symbol-kopf.svg` (PNG in 192 und 512 px ableiten). Die beiden Hex-Werte sind die im Design-System dokumentierten Word-/Manifest-Werte.
9. **Druck** ist für die App nicht vorgesehen; `styles.css` setzt im Druck Papier weiß und Text schwarz automatisch.
10. **Abweichungen** von den Mockups (z. B. weil ein Feld fehlt) in `probleme_loesungen` des jeweiligen AP dokumentieren und hier unter Abschnitt 8 nachtragen.

## 8. Abnahme und offene Punkte

Abnahmekriterien (AP-01a): Athlet hat die Mockups bestätigt; Branding-Dokument liegt im Repo; jeder Screen aus Abschnitt 10 ist abgedeckt; Tablet- und Smartphone-Ansicht (und Desktop) vorhanden.

Abgenommen durch den Athleten am 2026-09-27. Konzept ergänzt: D-19 (Desktop), Abschnitt 10 (S8 Einstellungen).

Abweichungen in der Umsetzung (nach Hinweis 7.10):

| screen | abweichung | grund | ap / datum |
|---|---|---|---|
| alle | Icons serverseitig als Inline-SVG aus `public/assets/icons/` statt über `icons.js` (B-05 bleibt: lokal, kein CDN, keine CSS-Maske) | Seiten funktionieren ohne JavaScript; Content-Security-Policy erlaubt keine Inline-Skripte | AP-01, 2026-09-27 |
| alle | Inline-Styles der Mockups als Klassen in `server/public/css/training.css` (`h-card`, `center`, `gap-6`, `mt-8`, `ic-brand`) | Content-Security-Policy ohne `unsafe-inline` | AP-01, 2026-09-27 |
| S1 | Kein Knopf „Passwort anzeigen“ | braucht JavaScript; Passwort-Manager und Browser bieten die Funktion | AP-01, 2026-09-27 |
| S1 gesperrt | Statt „Anmelden“ ein Sekundärknopf „Erneut versuchen“ (lädt die Seite neu) | ohne Passwortfeld ist Anmelden nicht möglich | AP-01, 2026-09-27 |
| S7 | Hinweistext „Prüfe, ob Du die Verbindung gerade selbst eingerichtet hast. Die Freigabe gilt, bis sie widerrufen wird.“ statt Audit-Log/Einstellungen | Audit-Log (AP-03/AP-05) und Einstellungen (AP-04) gibt es noch nicht; Text wird mit AP-04 wieder angeglichen | AP-01, 2026-09-27 |
| S7 | Fußzeile ohne Link „Abmelden“ | Abmelden braucht ein Formular mit CSRF-Token; auf der Freigabeseite reicht „Ablehnen“ | AP-01, 2026-09-27 |
| S7 | Zugriffsliste nach Scope gruppiert (erst Lesen, dann Schreiben) | ergibt sich aus der Scope-Zuordnung | AP-01, 2026-09-27 |
| Startseite | Übergangsseite nach dem Login (Hinweis mit Connector-Adresse, Abmelden) im Auth-Layout | Seitenrahmen mit Navigation kommt mit AP-04 | AP-01, 2026-09-27 |

Offen (unabhängig von den Mockups):
- Word-Vorlage mit variablen TTF prüfen (aus dem Ablegen des Design-Systems).

## 9. Prüfung

| was | wie | ergebnis | datum |
|---|---|---|---|
| Alle 13 Seiten/Zustände in 390, 834, 1112 und 1280 px gerendert | automatisiert (Chromium/Playwright, Screenshots in `mockups/screenshots/`) | kein horizontaler Überlauf, keine fehlenden Ressourcen | 2026-09-27 |
| Schriften laden lokal | automatisiert (`document.fonts`) | ok | 2026-09-27 |
| Sichtprüfung Smartphone/Desktop | manuell (Fable) | Befunde behoben: Kennzahl-Umbruch auf Smartphone, 7-Spalten-Woche unter 1280 px zu eng (jetzt zwei Spalten), Segmentwahl im Zweispaltenlayout | 2026-09-27 |
| Sichtprüfung und Abnahme | manuell durch Athlet | abgenommen | 2026-09-27 |
