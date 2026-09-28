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
| S2 Woche | `s2-woche.html` | `?state=leer` | AP-04; Kurzsatz mit „mehr“ ergänzt (AP-13) |
| S3 Einheit | `s3-einheit.html` | `?typ=kraft` (Standard), `?typ=ausdauer`, `?typ=klettern`, zusätzlich `&schmerz=ja` | AP-04; Kurzsatz mit „mehr“ und „Einheit starten“ ergänzt (AP-13/AP-14) |
| S4 Check-in | `s4-checkin.html` | `?schmerz=ja` | AP-04 |
| S5 Schmerz | `s5-schmerz.html` | – | AP-04 |
| S6 Verlauf | `s6-verlauf.html` | – | AP-09 |
| S8 Einstellungen | `s8-einstellungen.html` | `?state=update` | AP-04 (Backup/Update AP-10) |
| S9 Einheit geführt | `s9-einheit-gefuehrt.html` | `?state=bereit` (Standard), `laeuft`, `pause`, `offen`, `abschluss` | AP-14 (Fable, 2026-09-28) |
| Icon-Optionen | `icon-optionen.html` (Varianten-SVGs in `icon-optionen/`) | – | AP-13, Q-14 (Fable, 2026-09-28) |

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
| B-08 | Statusfarben Erfolg (grün) und Fehler (rot) dürfen im geführten Modus (S9) als Seitenfläche und Timer-Farbe den Zustand tragen: grün = Arbeitsphase, rot = Pause/bereit/angehalten, sonst normale Farbe. Text auf diesen Flächen in der `-700`-Stufe. B-06 bleibt: keine Serien in Statusfarben. | Wunsch des Athleten (Rot → Grün); Zustand ist eine Statusinformation, keine Datenserie. | 2026-09-28 |
| B-09 | App-Kennung wechselt vom Lama-Kopf auf das ganze Lama (ändert B-03); Variante nach Q-14 im Hauptkonzept (`mockups/icon-optionen.html`, Empfehlung V3 als App-Icon, V2 als Favicon und Kennung). Bis zur Wahl bleibt der Kopf. | Der Kopf gefällt dem Athleten nicht. | 2026-09-28 |

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
| Startseite | ~~Übergangsseite nach dem Login~~ – seit AP-04 Weiterleitung auf `/woche` | Seitenrahmen mit Navigation kommt mit AP-04 | AP-01, 2026-09-27; ersetzt AP-04, 2026-09-28 |
| S2 | Kennzahl „sRPE bisher“ ohne „≈ geplant“ | geplante sRPE-Last ist im Datenmodell nicht vorhanden (nur geplante Dauer) | AP-04, 2026-09-28 |
| S2 | Vergangene Tage ohne Check-in als „Check-in fehlt“ (Hinweis-Stil wie „offen“), Tage ohne Einheit als „Keine Einheit“ | Mockup zeigt nur Wochenmitte; fehlende Tage sollen sichtbar sein (Abschnitt 11) | AP-04, 2026-09-28 |
| S2 | Marke „verschoben“ ohne Icon | passt sonst nicht in die 7-Spalten-Woche | AP-04, 2026-09-28 |
| S3 | Zusätzliches Feld „Dauer (min)“ im Rückmeldungsblock; Hinweis zu ausgelassen/verschoben unter dem Status | sRPE braucht die Dauer | AP-04, 2026-09-28 |
| S3 | Ist-Felder Kraft: Sätze, Wdh., Last (Haltezeiten in „Wdh.“, z. B. „30s“); Klettern: Dauer, Sätze (nur wenn geplant), Notiz | folgt dem Schema 7.1; mockup-spezifische Felder wie „Boulder“/„Grad“ gibt es im Schema nicht | AP-04, 2026-09-28 |
| S3 | Zonenbalken als SVG, Legendenfarben über Klassen | Content-Security-Policy ohne Inline-Styles | AP-04, 2026-09-28 |
| S3, S4 | Schmerz-Kurzform klappt per CSS `:has()` auf (ohne JavaScript) | Hinweis 7.3 | AP-04, 2026-09-28 |
| S5 | Warnhinweis erst nach dem Speichern, mit Knöpfen „Zur Woche“/„Weiteres Ereignis“ | Hinweis hängt vom gespeicherten Verlauf ab; Anzeigeregel vorläufig bis AP-07 | AP-04, 2026-09-28 |
| S8 | Backup und „Migrieren“ als deaktivierte Platzhalter; Zeitzone/Passwort auf eigenen Unterseiten; Widerrufen als Formular | Funktionen aus AP-10; Formulare ohne JavaScript | AP-04, 2026-09-28 |
| S1, S8 | Passkey (D-44): S1 zusätzlicher Sekundärknopf „Mit Passkey anmelden“ (Icon `key`) unter „Anmelden“; S8 Konto mit je einer Zeile pro Passkey („Entfernen“ in Fehlerfarbe) und „Passkey hinzufügen“ mit Namensfeld. Knöpfe nur sichtbar, wenn der Browser WebAuthn kann | nicht in den Mockups (AP-09); Gestaltung mit vorhandenen Bausteinen | AP-09, 2026-09-28 |
| Profil (neu) | Seite `/profil` ohne Mockup, aus vorhandenen Bausteinen: je Abschnitt Abschnittstitel mit Stand, Karte mit Text im Stil `plan-text`, Knöpfe „Bearbeiten“/„Frühere Fassungen“; Bearbeiten wie die S8-Unterseiten (Textfeld, Grund, fixierte Knopfleiste); Fassungen als Karten mit Marke „aktuell“. Markdown wird als Text angezeigt. Einstieg über S8 (eigener Abschnitt „Athletenprofil“), keine eigene Navigationsposition | D-48 (AP-09) kam nach AP-01a; Mockup durch Fable auf Wunsch nachträglich | AP-09, 2026-09-28 |
| alle App-Seiten | Offline (D-45/D-49): Hinweise oben im Inhalt als vorhandene Alerts – „Offline. Gespeicherter Stand vom …“ (Warnung), „Offline gespeichert.“ (Erfolg), „N Eingaben warten auf Netz“ (Info), abgelehnte Eingabe (Fehler) mit Knöpfen „Öffnen“, „Trotzdem übernehmen“ (sekundär) und „Verwerfen“ (Ghost, Fehlerfarbe); Alerts ohne Icon, da vom Skript erzeugt (Icons sind serverseitige SVG). Nicht gespeicherte Seiten ohne Netz: schlichte Hinweisseite im Anmelde-Layout | nicht in den Mockups | AP-09, 2026-09-28 |
| S3, S4 | Formular „Inzwischen geändert.“ (Warnung) bei zwischenzeitlicher Änderung; Eingaben bleiben stehen | Schutz gegen Überschreiben (D-49) | AP-09, 2026-09-28 |
| S2 | Karte „Morgen-Check-in“ oben in der aktuellen Woche (AP-12): Formular (Morgentest als zwei 0–10-Skalen, Erholung, Muskelkater, „Weitere Angaben“ als aufklappbarer Bereich) bzw. Zusammenfassung mit Ampel-Marke (grün/gelb/rot/keine Daten = Erfolg/Warnung/Fehler/neutral), Warnhinweis „Abklärung empfohlen“ als Fehler-Alert | nicht in den Mockups; vorhandene Bausteine | AP-12, 2026-09-28 |
| S4 | Morgentest oben, übrige neue Felder unter „Weitere Angaben“ (`details`), Warnzeichen als Kontrollkästchen; 0–10-Skala bricht bei 375 px in zwei Zeilen um (wie die Schmerzstärke) | AP-12 | AP-12, 2026-09-28 |
| S8 | Unterseite „Erinnerung im Kalender“ wie „Zeitzone ändern“: Uhrzeitfeld (Browser-Zeitauswahl, 5-Minuten-Schritte), Kontrollkästchen „Keine Erinnerung“, fixierte Knopfleiste; Zeile in Verbindungen mit „Ändern“ | AP-11, D-52 | AP-11, 2026-09-28 |
| S8 | Verbindungen: Zeile „Kalender (CalDAV)“ mit Host, letzter Übertragung bzw. Fehler und Sekundärknopf „Abgleichen“ (Icon `refresh`); ohne Konfiguration Marke „aus“ | AP-11 kam nach AP-01a; vorhandene Bausteine | AP-11, 2026-09-28 |
| S2, S3 | Begründung der Planung (D-56): S2 Karte unter der Kopfzeile mit Kurzsatz und `details.more` „mehr“ (Chevron dreht sich beim Öffnen); die Angabe „Fokus …“ in der Kopfzeile entfällt. S3 Kurzsatz im Seitenkopf statt „Trainer-Notiz“, darunter „mehr“ und Primärknopf „Einheit starten“ (Icon `player-play`) | AP-13 (Mockup Fable) | geplant, AP-13 |
| S8 | Bereich „Training“ vor „Backup“: Zeile „Timer-Signale“ mit Segment An/Aus (Icons `volume`/`volume-off`) | AP-14 (D-58) | geplant, AP-14 |
| S9 (neu) | Geführte Einheit: Fortschrittsbalken, Phase-Karte (Satz, Übung, Phase-Marke, Timer 64 px mono bzw. Wiederholungen 40 px, Soll), Ist-Karte, „Als Nächstes“, fixierte Aktionsleiste (Zurück-Icon, Primäraktion, Überspringen/Weiter als Icon mit Text ab Tablet), Stummschalter in der Kopfzeile (`aria-pressed`). Neue Icons `player-play`, `player-pause`, `player-skip-back`, `volume`, `volume-off` (Tabler) | AP-14 (D-57, D-58) | geplant, AP-14 |
| S6 | ~~Platzhalterseite~~ – seit 0.8.0 umgesetzt; Balkenhöhen als Klassen in 5-%-Schritten, Legende über Klassen; Hinweis „steigt seit … Wochen“ weggelassen (Trendregel erst mit AP-07); Raster scrollt auf dem Smartphone waagrecht innerhalb der Karte | CSP ohne Inline-Styles; Trendregel fehlt noch | AP-04, 2026-09-28; AP-09, 2026-09-28 |

Offen (unabhängig von den Mockups):
- Word-Vorlage mit variablen TTF prüfen (aus dem Ablegen des Design-Systems).

## 9. Prüfung

| was | wie | ergebnis | datum |
|---|---|---|---|
| Alle 13 Seiten/Zustände in 390, 834, 1112 und 1280 px gerendert | automatisiert (Chromium/Playwright, Screenshots in `mockups/screenshots/`) | kein horizontaler Überlauf, keine fehlenden Ressourcen | 2026-09-27 |
| S9 (5 Zustände), Icon-Optionen, S2, S3 (3 Varianten), S8 in 390 und 1280 px gerendert | automatisiert (Chromium/Playwright, Screenshots in `mockups/screenshots/`) | kein horizontaler Überlauf nach Korrektur (Aktionsleiste S9, Startbildschirm-Streifen), keine fehlenden Ressourcen, keine Skriptfehler | 2026-09-28 |
| Sichtprüfung S9, Icon-Optionen, S2, S3, S8 | manuell (Fable) | Befunde behoben: Icon-SVGs mit ungeschlossenen Pfaden, Klassenkollision `.app` im Startbildschirm-Streifen; Abnahme durch Athlet offen | 2026-09-28 |
| Schriften laden lokal | automatisiert (`document.fonts`) | ok | 2026-09-27 |
| Sichtprüfung Smartphone/Desktop | manuell (Fable) | Befunde behoben: Kennzahl-Umbruch auf Smartphone, 7-Spalten-Woche unter 1280 px zu eng (jetzt zwei Spalten), Segmentwahl im Zweispaltenlayout | 2026-09-27 |
| Sichtprüfung und Abnahme | manuell durch Athlet | abgenommen | 2026-09-27 |
