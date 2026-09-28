# Auftrag: App-Icon, Begründungstexte der Planung und geführte Trainingseinheit

Ablageort im Repo: `docs/konzept/gefuehrte-einheit.md` (im Hauptkonzept: AP-13 und AP-14, D-55 bis D-58, Q-14)
Status: Konzept bestätigt durch Philipp am 2026-09-28 (Entscheidungen E-01 bis E-21); Logo-Variante gewählt (Q-14 → D-59: V3 als App-Icon, V2 als Favicon und App-Kennung); Umsetzung in Arbeit (Stand in Abschnitt 12)
Versionsnummer: keine im Konzept; wird in der Umsetzung festgelegt

---

## 1. Ziel

Drei Wünsche des Athleten, die zusammen bearbeitet werden, weil sie dieselben Seiten berühren (Woche, Einheit, Einstellungen) und gemeinsame Mockups brauchen:

| teil | ziel |
|---|---|
| A · App-Icon und Logo | „Zum Startbildschirm“ zeigt auf Android (LibreWolf, idealerweise alle Browser) das App-Logo statt eines Platzhalters. Das Logo der App wechselt vom Lama-Kopf auf das **ganze Lama**; die Variante wählt der Athlet anhand einer Mockup-Seite mit mehreren Optionen. |
| B · Begründungstexte | Die planende Instanz (Claude im Projekt-Chat) schreibt je **Woche** und je **Einheit** einen kurzen Text zu „Was und warum“. Ein zusammenfassender Satz steht sichtbar bei Woche bzw. Einheit; „mehr“ daneben öffnet den restlichen Text. Klein gehalten: Ziele und Pläne, die dem Training zugrunde liegen, keine Abhandlung. |
| C · Geführte Einheit | Eine Einheit lässt sich „starten“. Die App führt dann Schritt für Schritt durch das Training: aktuelle Übung mit Dauer/Wiederholungen, was als Nächstes kommt, Timer für getimte Übungen (App wird grün, solange gearbeitet wird; Tonsignale beim Start, 30 s und 10 s vor Ende sowie in den letzten 3 Sekunden; stummschaltbar in den Einstellungen und in der laufenden Einheit). Ohne Timer wird zumindest die aktuelle Übung angezeigt, und die Ist-Werte (Wiederholungen, Gewicht …) lassen sich direkt eintragen – wie sonst in der Einheitenübersicht. |

## 2. Kontext und Bestand (Code-Stand 0.16.0, Schema 21)

| bereich | bestand | relevanz |
|---|---|---|
| Manifest und Icons | `server/public/manifest.webmanifest` (Name „Training“, `display: standalone`, `start_url: /woche`, `theme_color #7A5C94`, Icons 192/512 `any` und 512 `maskable`, PNG aus dem Lama-Kopf, RGB ohne Alpha). Apache liefert Manifest und `/icons/` direkt aus (`.htaccess`: `AddType application/manifest+json .webmanifest`, keine Anmeldung nötig). | A |
| Seitenrahmen | `layout-app.php` (angemeldete Seiten): `theme-color`, SVG-Favicon `/assets/lama-kopf.svg`, `apple-touch-icon` 192, `manifest`. `layout-auth.php` (Login, Setup, Freigabe): **nur** SVG-Favicon, kein Manifest, kein `apple-touch-icon`, keine `theme-color`. | A – wahrscheinliche Ursache, siehe 4.1 |
| Logo-Quellen | `docs/branding/chadid-design-system/assets/logo/`: `lama-symbol-linie.svg`, `-flaeche.svg`, `-linie-negativ.svg` (hell auf Pflaume 800), `-linie-dunkel.svg`, `-linie-schwarz.svg`, `lama-symbol-kopf.svg` (Ausschnitt viewBox 440 10 280 360). `server/bin/build-assets.php` kopiert nur den Kopf nach `public/assets/lama-kopf.svg`; die App-Kennung (Topbar, Navigation, Login-Karte) nutzt diese Datei. | A |
| Service Worker | `sw.js` (D-45): Seiten `/woche`, `/einheit`, `/checkin`, `/schmerz` erst Netz, dann Cache; Schlüssel ist Pfad + Query ohne `ok`/`offline`/`intervals`; statische Pfade inkl. `/icons/` und `/manifest.webmanifest` aus dem Versions-Cache; Formulare per POST-Puffer (`X-Offline-Queue`). | A, C |
| Content-Security-Policy | `default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'` – keine Inline-Skripte, keine Inline-Styles. Seiten funktionieren ohne JavaScript (Branding 7.3). | C |
| Datenmodell Planung | `training_week.focus` VARCHAR(255) und `coach_notes` TEXT (beide vorhanden, `focus` wird als „Fokus …“ in der Kopfzeile der Woche gezeigt, `coach_notes` nirgends). `session.coach_rationale` TEXT wird auf S3 komplett als „Trainer-Notiz“ gezeigt und im Kalendertermin (AP-11) als „Trainer: …“. | B |
| MCP-Schreibtools | `write_week_plan(week_start, sessions[], replace_existing, focus, coach_notes)`; je Session `coach_rationale`; `update_session(session_id, changes{…, coach_rationale})`. Lese-Tools: `get_week_overview` liefert `fokus`, `get_session_detail` liefert `coach_rationale`. Tool-Beschreibungen sagen nichts über Inhalt oder Länge der Texte. | B |
| plan_json | Kraft/Haltung/Mobilität: `exercises[{name, sets, reps ("8", "6-8", "30s"), load, tempo, rest_s, notes}]`; Klettern: `blocks[{kind, hang_s, rest_s, sets, added_load_kg, duration_min, target, notes, …}]`; Ausdauer: Workout-Text für die Uhr. Ist-Werte in `actual_json` spiegeln die Struktur. | C |
| S3 Einheit | Ein Formular (`POST /einheit`): Ist je Übung/Block (vorbelegt mit Soll), Rückmeldung (Dauer, RPE, Gefühl, Schmerz, Abweichung, Notiz, Status), Konfliktschutz über `stand`, Offline-Puffer über `data-offline-form`. | C – die geführte Einheit nutzt dasselbe Formular |
| Einstellungen | `app_setting` (Schlüssel/Wert, D-52) mit `SettingsRepository`; S8 zeigt Konto/Backup/Update/Verbindungen als Listenkarten. | C – Ton-Einstellung |
| Farben | Design-System kennt Statusfarben `--status-success` (grün) und `--status-error` (rot) mit `-bg` und `-text`; Branding B-06: Statusfarben sind reserviert und tragen keine Serien. Nur helles Schema (B-04). | C – Farbwechsel ist eine Statusanzeige |
| Mockups | `docs/branding/mockups/` (S0–S8, `index.html` mit vier Geräterahmen, Screenshots Smartphone/Desktop per Playwright). | A, B, C |

## 3. Geklärte Entscheidungen

Mit dem Athleten am 2026-09-28 geklärt (E-01 bis E-07). E-08 bis E-20 waren Vorschläge von Fable und gelten seit der Bestätigung des Konzepts am 2026-09-28. E-21 ist die Logo-Wahl.

| id | entscheidung | begruendung |
|---|---|---|
| E-01 | **Eigene Kurzfelder.** Woche: `focus` = Kurzsatz, `coach_notes` = ausführlicher Text. Einheit: neues Feld `coach_summary` (Kurzsatz, max. 200 Zeichen), `coach_rationale` bleibt der ausführliche Text. Die MCP-Tools nehmen beide Felder an und verlangen den Kurzsatz. | Verlässlich kurz, maschinell prüfbar; eine kleine Migration. (Alternative „erster Absatz“ verworfen.) |
| E-02 | **Grün = Arbeitsphase.** Grün nur, während gehalten/gehangen/gearbeitet wird. Rot in jeder Pause, im Zustand „bereit“ (Timer vorhanden, noch nicht gestartet) und wenn angehalten. Schritte ohne Timer behalten die normale Farbe (Pflaume). | Auf einen Blick erkennbar, ob man gerade dran ist. |
| E-03 | **Automatik innerhalb einer Übung, manuell dazwischen.** Innerhalb einer getimten Übung laufen Arbeit/Pause/Sätze automatisch durch. Zwischen Übungen wechselt der Athlet mit „Weiter“. Bei Übungen mit Wiederholungen startet nach „Satz erledigt“ automatisch der Pausentimer (`rest_s`), sofern gesetzt. | Wenige Tipps im Training, aber kein ungewolltes Weiterspringen zur nächsten Übung. |
| E-04 | **Mockups:** neuer Screen S9 „Einheit geführt“ mit Zuständen (bereit, Arbeit läuft, Pause, Übung ohne Timer, Abschluss); S2 Woche und S3 Einheit mit Kurzsatz + „mehr“; S8 mit Ton-Schalter; zusätzlich eine Seite „Icon-Optionen“ (Teil A). | Wunsch des Athleten. |
| E-05 | **Icon-Befund:** Chrome auf Android zeigt das Lama als App-Icon (Athlet, 2026-09-28). Manifest und PNG-Icons sind damit in Ordnung; das Problem liegt am Verknüpfungsweg von LibreWolf über das Favicon (4.1, Ursachen 1 und 2). Der Icon-Satz nach E-09 bleibt, weil er genau diesen Weg abdeckt; Ursache 3 entfällt. | Prüfschritt P-A1 erledigt. |
| E-06 | **Bildschirm an und Vibration.** Im geführten Modus bleibt der Bildschirm an (Wake Lock); Signale kommen als Ton und – wo der Browser es kann – als Vibration. Stummschalten deaktiviert beides. | Ohne Wake Lock kommen Töne im Hintergrund nicht sicher; Vibration hilft im lauten Raum. |
| E-07 | **Einmal am Ende speichern.** Die geführte Einheit ist dasselbe Rückmelde-Formular wie S3, nur schrittweise angezeigt. Fortschritt und Eingaben liegen bis zum Abschluss im Browser (überleben Neuladen), ein Speichern am Ende. Offline-Puffer und Konfliktschutz bleiben unverändert. | Kein neuer Endpunkt, keine Zwischenzustände auf dem Server. |
| E-08 | **Logo überall:** Die gewählte Lama-Variante ersetzt den Kopf als App-Icon, Favicon und App-Kennung (Topbar, Navigation, Login-Karte). Bis zur Wahl (Q-14) bleibt der Kopf; Teil A wird erst nach der Wahl umgesetzt. | Wunsch des Athleten („der Kopf gefällt mir nicht“); eine Kennung, nicht zwei. |
| E-09 | **Icon-Satz:** PNG 48, 96, 192, 512 (`purpose: any`), 512 maskable (Motiv in der sicheren Zone, Grund Pflaume 600 bzw. je Variante), `apple-touch-icon` 180 PNG, `favicon.ico` (16/32/48 mehrfach) im Docroot, SVG-Favicon bleibt zusätzlich. Beide Layouts (`layout-app`, `layout-auth`) tragen Manifest, PNG-Icon-Links mit `sizes`, `apple-touch-icon` und `theme-color`. Manifest zusätzlich mit `id` und `description`. | Deckt Verknüpfung per Favicon (Firefox-Familie), Manifest (Chrome/Edge/Samsung), iOS und Desktop ab; Login-Seite ist oft die Seite, von der aus verknüpft wird. |
| E-10 | **Kurzsatz-Regeln:** Ein Satz, max. 200 Zeichen, sagt Was und Warum („Zweite Krafteinheit, Last wie letzte Woche, Fokus Tiefe – Sehne noch reizbar“). Ausführlich: 2–6 Sätze, max. 1 500 Zeichen, Bezug auf Blockziel, Belastungssteuerung, Befunde (Morgentest, Schmerz, Wellness), ohne Literaturzitate. Pflicht: Woche `focus`, Einheit `coach_summary` außer bei `ruhe`; die ausführlichen Texte sind erwartet, aber nicht erzwungen. | „Nicht ausführlich, nur kleines Darlegen der Ziele.“ Die Regel steht in den Tool-Beschreibungen (Claude sieht sie beim Planen) und später in den Trainerregeln (AP-07). |
| E-11 | **Anzeige des Kurzsatzes:** Woche: eigene Zeile unter der Kopfzeile der Woche (Kurzsatz + „mehr“); die bisherige Angabe „Fokus …“ in der Kopfzeile entfällt. Einheit: Kurzsatz im Seitenkopf statt „Trainer-Notiz: …“ (+ „mehr“). Wochenliste: **kein** Kurzsatz je Einheit (bleibt kompakt). Kalendertermin: Kurzsatz als erste Zeile der Beschreibung, ausführlicher Text danach. | Wunsch: „bei Woche / Einheit“; Wochenliste bleibt auf dem Handy lesbar. |
| E-12 | **„mehr“ ohne JavaScript:** `<details class="more">` mit `<summary>` (bereits im CSS für AP-12), aufklappbar per Tipp, keine Skripte. | Branding 7.3, CSP. |
| E-13 | **Einstieg in die geführte Einheit** nur auf S3 (Knopf „Einheit starten“ als Primäraktion im Seitenkopf) für die Typen `kraft`, `haltung`, `mobilitaet`, `klettern` mit vorhandenem Plan. Nicht für `ausdauer` (läuft auf der Uhr) und `ruhe`. In der Wochenliste kein Zusatzknopf. | Ein Tipp mehr, dafür bleibt S2 unverändert; Ausdauer ist auf der Uhr geführt. |
| E-14 | **Adresse:** `GET /einheit?id=<id>&modus=start` rendert S9; `POST /einheit` bleibt der einzige Speicherweg (gleiche Feldnamen wie S3). Kein neuer Controller-Endpunkt, nur ein zweites Template. | Konfliktschutz (`stand`), Offline-Puffer und Validierung werden wiederverwendet. |
| E-15 | **Ablaufplan serverseitig:** Der Server leitet aus `plan_json` deterministisch die Schrittfolge ab (Abschnitt 6.3) und gibt sie als HTML (ein Abschnitt je Übung) plus JSON in einem `data-ablauf`-Attribut aus. Das Skript zeigt jeweils einen Schritt; ohne JavaScript sind alle Abschnitte sichtbar und die Einheit bleibt wie S3 ausfüllbar. | Reine, testbare PHP-Funktion; Fallback ohne JS. |
| E-16 | **Timer zeitstempelbasiert:** Endzeit als Zeitstempel, Anzeige alle 250 ms; nach Bildschirm aus / Tabwechsel wird nachgerechnet, verpasste Signale werden nicht nachgeholt (ein Hinweiston beim Zurückkehren, wenn eine Phase inzwischen endete). | Browser drosseln Timer im Hintergrund; so bleibt die Zeit richtig. |
| E-17 | **Signale ohne Audiodateien:** Web Audio (Oszillator) mit vier Mustern: Start (zwei kurze hohe Töne), 30 s vor Ende (ein Ton, nur bei Phasen ≥ 45 s), 10 s vor Ende (ein Ton, nur bei Phasen ≥ 15 s), letzte 3 s (drei kurze Ticks bei 3, 2, 1). Ende der letzten Phase einer Übung: ein längerer Abschlusston. Vibration mit denselben Mustern (`navigator.vibrate`). Audio wird beim ersten Tipp auf „Start“ freigeschaltet. | Keine Dateien, kein Cache, CSP-konform (`script-src 'self'`); Browser verlangen eine Nutzergeste für Audio. |
| E-18 | **Stummschalten zweistufig:** Einstellung `timer_ton` (`an`/`aus`, Standard `an`) in `app_setting`, änderbar in S8; in S9 ein Schalter in der Kopfzeile, der nur für diese Einheit gilt (im Browser gemerkt, nicht auf dem Server). | Wunsch: Einstellungen und in der Einheit. |
| E-19 | **Fortschritt im Browser:** `sessionStorage`-Eintrag je Einheit (Schritt, Satz, Phase, Endzeit, Ist-Werte, Startzeit, stumm). Neu laden setzt den Stand fort; „Neu starten“ löscht ihn. Nach Speichern gelöscht. Kein Serverzustand (E-07). | Robust gegen versehentliches Neuladen; keine Konflikte mit dem Offline-Puffer. |
| E-20 | **Farben nur über Statusfarben des Design-Systems:** Arbeit = `--status-success-bg` als Seitengrund, Zeit in `--status-success-text`; Pause/bereit/angehalten = `--status-error-bg` und `--status-error-text`; `theme-color` wird mitgeführt. Neue Branding-Entscheidung B-08 (Statusfarben dürfen als Flächen für den Timer-Zustand dienen). | Rot/Grün mit ausreichendem Kontrast, ohne neue Farben. |
| E-21 | **Logo-Wahl (Q-14 → D-59):** V3 (Lama Fläche hell `#F4EFF2` auf Pflaume 600 `#7A5C94`, Auge Orange) für App-Icon Android/iOS und `maskable`; V2 (Lama Fläche Pflaume 600 auf Papier) für Favicon 16/32 px, SVG-Favicon und App-Kennung in Topbar, Navigation und Login-Karte. Vorlagen: `docs/branding/mockups/icon-optionen/v3.svg`, `v3-maskable.svg`, `v2.svg`. | Entscheidung des Athleten am 2026-09-28, wie von Fable empfohlen. |

## 4. Teil A · App-Icon und Logo

### 4.1 Befund und Diagnose

Beobachtung: Beim Hinzufügen zum Startbildschirm unter Android (LibreWolf) erscheint kein Logo.

Wahrscheinlichste Ursachen, in dieser Reihenfolge:

1. **Login-Seite ohne Manifest und PNG-Icon.** Wer die Seite vom Login aus (abgelaufene Sitzung, erster Aufruf) verknüpft, bietet dem Browser nur ein SVG-Favicon. Android-Browser der Firefox-Familie rendern SVG-Favicons nicht als Verknüpfungs-Icon und fallen auf einen Buchstaben-Platzhalter zurück.
2. **Verknüpfung statt Installation.** Firefox-Abkömmlinge (LibreWolf, Mull, IronFox) schalten Service Worker oder die Manifest-Verarbeitung teils ab. Dann entsteht keine „installierte“ Web-App aus dem Manifest, sondern eine einfache Verknüpfung, deren Icon aus den `<link rel="icon">`-Einträgen der Seite kommt – dort fehlt ein PNG mit `sizes`.
3. ~~Manifest wird nicht verwendet~~ – ausgeschlossen: Chrome auf Android zeigt das Icon (P-A1, 2026-09-28). Manifest, Icons und Auslieferung sind in Ordnung.

Prüfschritte (Athlet, vor und nach der Umsetzung, je Browser):

| schritt | wie | erwartung |
|---|---|---|
| P-A1 | Chrome Android: `training.gen-em.org/woche` öffnen → Menü → „App installieren“ / „Zum Startbildschirm“ | Lama-Icon; App öffnet ohne Browserleiste – **ok, 2026-09-28** (vor der Umsetzung, noch mit dem Kopf) |
| P-A2 | Chrome Desktop: DevTools → Application → Manifest | keine Fehler, alle Icons geladen; erwartet sind nur die zwei Hinweise „Richer PWA Install UI won't be available on desktop/mobile“ (keine `screenshots` im Manifest, O-05) |
| P-A3 | LibreWolf Android: von `/login` **und** von `/woche` verknüpfen | beide Male Lama-Icon |
| P-A4 | LibreWolf `about:config`: `dom.serviceWorkers.enabled`, `dom.manifest.enabled` | nur Befund für die Doku, keine Änderung nötig |
| P-A5 | iOS Safari (falls vorhanden): „Zum Home-Bildschirm“ | Lama-Icon 180 px, kein Screenshot-Icon |
| P-A6 | `curl -I https://training.gen-em.org/manifest.webmanifest` und `/favicon.ico` | 200, `Content-Type: application/manifest+json` bzw. `image/x-icon` (Apache setzt beide) |

### 4.2 Maßnahmen (unabhängig von der Logo-Wahl)

1. `layout-auth.php` erhält dieselben Kopf-Einträge wie `layout-app.php`: `theme-color`, `manifest`, `apple-touch-icon`, PNG-Icon-Links.
2. Beide Layouts: `<link rel="icon" type="image/png" sizes="48x48|96x96|192x192|512x512">` zusätzlich zum SVG; `<link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon-180.png">`.
3. Manifest: `id: "/woche"`, `description`, Icons 48/96/192/512 `any` und 512 `maskable` (getrennte Einträge, kein `any maskable` in einem Eintrag), PNG mit Alpha-Kanal, wo das Motiv freigestellt ist; `maskable` mit vollflächigem Grund.
4. `server/public/favicon.ico` (16/32/48) – wird von Apache direkt ausgeliefert (Datei vorhanden → kein `index.php`).
5. `.htaccess`: `AddType image/x-icon .ico` (falls nicht global gesetzt) und `Cache-Control` für `/icons/` (z. B. 7 Tage) – Icons ändern sich mit Dateinamen.
6. Service Worker: `/favicon.ico` in `STATIC_PREFIXES`-Behandlung aufnehmen (Pfadgleichheit wie beim Manifest).
7. Icon-Erzeugung als reproduzierbarer Schritt: Skript `server/bin/build-icons.php` (oder Playwright-Skript in `docs/branding/`) rendert die gewählte SVG-Variante in alle Größen; Ergebnis wird eingecheckt (`server/public/icons/`), weil der Deploy-Server kein Rendering hat.

### 4.3 Logo-Varianten (Mockup `docs/branding/mockups/icon-optionen.html`)

Die Mockup-Seite zeigt jede Variante als Android-Icon (Kreis und abgerundetes Quadrat, maskable-Zone), iOS-Icon, Favicon 16/32 px, im Browser-Tab und in der Topbar der App. Varianten:

| id | variante | motiv | grund | eignung |
|---|---|---|---|---|
| V1 | Lama Linie auf Papier | `lama-symbol-linie.svg`, Pflaume 600, Auge Orange | Papier `#F6F3F0` | Hauptform der Marke; unter 24 px wird die Linie dünn (Favicon 16 px: V2 nutzen) |
| V2 | Lama Fläche auf Papier | `lama-symbol-flaeche.svg` | Papier | Design-System-Empfehlung für App-Icons und kleine Größen; kräftig, gut bei 16 px |
| V3 | Lama Fläche hell auf Pflaume | Fläche in `#F4EFF2`, Auge Orange | Pflaume 600 `#7A5C94` | hoher Kontrast auf dem Startbildschirm, passt zur `theme_color`; maskable ohne Rand |
| V4 | Lama Linie hell auf Pflaume 800 | `lama-symbol-linie-negativ.svg` (Auge weiß) bzw. `-dunkel` (Auge Orange) | Pflaume 800 | ruhig, dunkel; Linie bei 16 px schwach |
| V5 | Kopf (bisher, Vergleich) | `lama-symbol-kopf.svg` | Papier | zum Vergleich |

Entschieden (E-21, D-59): **V3** für App-Icon und maskable, **V2** für Favicon 16/32 px, SVG-Favicon und die App-Kennung in Topbar/Navigation/Login.

Folgen: `build-assets.php` kopiert `lama-symbol-flaeche.svg` als `public/assets/lama.svg` (ersetzt `lama-kopf.svg`); Templates, Login-Karte, Mockups (Kennung in Topbar/Navigation/Login) und Branding (B-03/B-09) werden angepasst; PNG-Icons aus `icon-optionen/v3.svg` (any) und `v3-maskable.svg` (maskable), Favicon aus `v2.svg`; der Kopf bleibt im Design-System für Avatar-Zwecke.

## 5. Teil B · Begründungstexte der Planung

### 5.1 Datenfelder (maschinenlesbar)

```yaml
training_week:
  focus:        VARCHAR(255) NULL   # Kurzsatz der Woche (Was und warum), Pflicht in write_week_plan (E-10)
  coach_notes:  TEXT NULL           # ausführlicher Text der Woche, max. 1500 Zeichen (Prüfung in der Anwendung)
session:
  coach_summary:   VARCHAR(200) NULL  # NEU (Migration): Kurzsatz der Einheit, Pflicht außer ruhe (E-10)
  coach_rationale: TEXT NULL          # ausführlicher Text der Einheit, max. 1500 Zeichen
```

Migration: neue Spalte `coach_summary` in `session`; bestehende Einheiten behalten `coach_rationale`, die Anzeige zeigt dann den Kurzsatz leer und den Text hinter „mehr“ (kein automatisches Aufteilen alter Texte). Rückweg: Spalte entfernen.

### 5.2 MCP-Schnittstelle

| tool | änderung |
|---|---|
| `write_week_plan` | `focus` **Pflicht** (1–255 Zeichen), `coach_notes` optional (≤ 1500). Je Session `coach_summary` Pflicht außer `ruhe` (1–200), `coach_rationale` optional (≤ 1500). Fehlende Pflichtfelder → Tool-Fehler mit Liste der betroffenen Sessions (nichts wird geschrieben). Tool-Beschreibung nennt die Regel E-10 in einem Satz je Feld. |
| `update_session` | `changes.coach_summary` und `coach_rationale` änderbar (gleiche Grenzen). Neu: `changes.focus`/`coach_notes` **nicht** hier – Wochenfelder nur über `write_week_plan` (bestehendes `COALESCE`-Verhalten bleibt: nicht übergebene Felder bleiben erhalten). |
| `get_week_overview` | Woche: `fokus` (bisher) und `begruendung` (= `coach_notes`); je Session zusätzlich `kurz` (= `coach_summary`). Budget 8.3 beachten: `begruendung` nur, wenn ≤ 1500 Zeichen (ist durch Prüfung garantiert). |
| `get_session_detail` | zusätzlich `coach_summary`. |
| `get_block` | unverändert (`phase_notes` bleibt die Blockbegründung). |

Trainerregeln (AP-07, `docs/regeln/`): Abschnitt „Begründung je Woche und Einheit“ mit dem Wortlaut aus E-10; bis AP-07 vorliegt, gilt die Tool-Beschreibung.

### 5.3 Anzeige

- **S2 Woche:** unter der Wochen-Kopfzeile eine Zeile `Kurzsatz` mit `<details class="more"><summary>mehr</summary>…</details>` für `coach_notes`. Ohne Text: Zeile entfällt. Bei Wochen ohne Plan unverändert (Leerzustand).
- **S3 Einheit:** im Seitenkopf `coach_summary` als Absatz, daneben „mehr“ → `coach_rationale`. Ist nur `coach_rationale` vorhanden (Altdaten), steht „Trainer-Notiz“ als Summary-Text und der Text dahinter.
- **S9 geführt:** Kurzsatz der Einheit im Startschritt („bereit“), nicht in den Übungsschritten.
- **Kalender (AP-11):** Beschreibung = Kurzsatz, Leerzeile, Kurzplan, Leerzeile, ausführlicher Text (gekürzt auf 1 000 Zeichen), Link.
- Offline: die Seiten sind ohnehin im Seiten-Cache; keine Änderung.

## 6. Teil C · Geführte Einheit (S9)

### 6.1 Einstieg und Adresse

- S3 zeigt für geeignete Einheiten (E-13) im Seitenkopf den Primärknopf „Einheit starten“ (Icon `player-play`), Link auf `/einheit?id=<id>&modus=start`. Bei Status `erledigt` heißt er „Erneut durchgehen“ (sekundär).
- S9 nutzt `layout-app` mit Zurück-Pfeil (zu S3), Titel der Einheit, Kopfzeilen-Aktion „Ton aus/an“ (Icon `volume`/`volume-off`, `aria-pressed`). Die untere Navigation bleibt (ohne JS-Vollbild); der Inhalt ist auf eine Spalte ausgelegt, auf Tablet/Desktop zentriert mit max. 720 px.

### 6.2 Aufbau der Seite (ein Formular, wie S3)

```
[Fortschritt: Übung 2 von 5 ───────────]
[Phase-Karte]  Übungsname · Satz 2 von 3
               ⏱ 00:37  (groß, mono)         ← nur getimte Schritte
               Phase: Arbeit | Pause | bereit
               Soll: 3 × 45 s · Pause 60 s
[Ist-Felder der aktuellen Übung: Sätze · Wdh./Dauer · Last · Notiz]
[Als Nächstes: Rudern mit Band · 3 × 10 · Band grün]
[Aktionen: Start | Pause | Satz erledigt | Weiter | Zurück | Überspringen]
[Abschlussschritt: Rückmeldung wie S3 + Speichern]
```

- Alle Übungsabschnitte stehen im HTML (`<section class="step" data-step="n">`); das Skript blendet alle außer dem aktuellen aus (`hidden`). Ohne JavaScript sind alle sichtbar, Timer-Knöpfe ausgeblendet (`.needs-js`), der Abschluss ist normal ausfüllbar → Verhalten wie S3 in Schrittform.
- Feldnamen identisch mit S3 (`ist[i][sets]`, `ist[i][reps]`, `ist[i][load]`, `ist[i][duration_min]`, `ist[i][notes]`, `duration_min`, `rpe`, `feel`, `pain`, `deviation`, `notes`, `status`, `csrf`, `id`, `stand`, `offline_label`).
- Abschlussschritt: `duration_min` wird, wenn leer, mit der gemessenen Dauer seit dem ersten „Start“ vorbelegt (auf ganze Minuten gerundet, änderbar); `status` vorbelegt `erledigt`, wenn kein Schritt übersprungen wurde, sonst `teilweise`.
- „Abbrechen“ führt zurück zu S3; der Fortschritt bleibt 12 h im Browser (E-19), S9 fragt beim erneuten Öffnen „Fortsetzen“ oder „Neu starten“.

### 6.3 Ablaufplan aus `plan_json` (deterministisch, serverseitig)

```yaml
schritt:
  index: int                # Reihenfolge = Reihenfolge im Plan
  quelle: exercises[i] | blocks[i]
  name: string
  soll: string              # wie S3 (fmtSoll/fmtBlock)
  art: wiederholungen | halten | block | offen
  saetze: int               # ≥ 1 (Kraft: sets; Klettern: sets oder 1)
  arbeit_s: int|null        # halten: Sekunden je Satz; block: duration_min*60; wiederholungen: null
  pause_s: int|null         # rest_s zwischen den Sätzen; null = keine Pause-Phase
  ist_felder: [sets, reps|duration, load, notes]   # wie S3

regeln:
  kraft_haltung_mobilitaet:
    reps ~ /^\s*(\d+)\s*(s|sek|sec)\.?\s*$/i      -> halten, arbeit_s = N
    reps ~ /^\s*(\d+)\s*min\.?\s*$/i             -> halten, arbeit_s = N*60
    reps ~ /^\s*(\d+)\s*-\s*(\d+)\s*(s|sek)\s*$/i -> halten, arbeit_s = obere Grenze
    sonst (z. B. "8", "6-8", "max")              -> wiederholungen
    saetze = sets; pause_s = rest_s
  klettern:
    hang_s gesetzt                     -> halten, arbeit_s = hang_s, saetze = sets ?? 1, pause_s = rest_s
    sonst duration_min gesetzt         -> block, arbeit_s = duration_min*60, saetze = 1, pause_s = null
    sonst                              -> offen (nur Anzeige + Ist-Felder, „Erledigt“)
  ausdauer, ruhe:                      -> kein Ablaufplan (S9 nicht angeboten, E-13)

phasen je schritt (E-02, E-03):
  halten/block:  [bereit] -> Start -> (arbeit arbeit_s -> pause pause_s) × saetze, letzte ohne pause -> [fertig]
  wiederholungen: [bereit] -> „Satz erledigt“ -> pause pause_s (auto, falls pause_s) -> nächster Satz … -> [fertig]
  offen:         [bereit] -> „Erledigt“ -> [fertig]
  fertig         -> „Weiter“ (manuell) zum nächsten Schritt; letzter Schritt -> Abschluss
```

Hinweis Hangboard: Wiederholungen innerhalb eines Satzes (z. B. Repeaters 7 s/3 s × 6) kennt das Schema nicht; sie werden als `sets` mit `rest_s` geplant. Sollte die Planung Sätze **und** Wiederholungen brauchen, ist das eine Schemaerweiterung (O-02), nicht Teil dieses Auftrags.

### 6.4 Timer, Signale, Farbe

| ereignis | ton (Web Audio) | vibration | bedingung |
|---|---|---|---|
| Start einer Arbeitsphase | 2 × 80 ms, 880 Hz | 2 × 80 ms | immer |
| 30 s vor Ende | 1 × 150 ms, 660 Hz | 150 ms | Phase ≥ 45 s |
| 10 s vor Ende | 1 × 150 ms, 660 Hz | 150 ms | Phase ≥ 15 s |
| 3, 2, 1 s vor Ende | je 1 × 60 ms, 990 Hz | je 60 ms | immer |
| Ende der letzten Phase einer Übung | 1 × 400 ms, 523 Hz | 300 ms | immer |
| Start einer Pausenphase | kein eigener Ton (Endticks reichen) | – | – |

- Signale gelten je Phase (Arbeit und Pause), nicht je Übung.
- Stumm (E-18): kein Ton, keine Vibration; Anzeige blinkt stattdessen in den letzten 3 s (Phase-Karte wechselt kurz die Deckkraft).
- Farbe (E-02, E-20): `body[data-phase="arbeit"]` grün, `body[data-phase="pause"|"bereit"|"angehalten"]` rot, sonst normal. Das Skript setzt `data-phase` und `<meta name="theme-color">`.
- Wake Lock (E-06): beim ersten Start anfordern, bei `visibilitychange` erneut; Freigabe beim Verlassen/Speichern. Browser ohne Wake Lock: kein Fehler, nur Hinweis in der Doku.
- Zeit (E-16): `endAt = Date.now() + rest`; Anzeige über `requestAnimationFrame`/`setInterval(250)`; Pause speichert `rest = endAt - now`.

### 6.5 Zustandsspeicher (E-19)

```yaml
key: training.gefuehrt.<session_id>
value:
  version: 1
  begonnen_um: epoch_ms
  schritt: int
  satz: int
  phase: bereit | arbeit | pause | angehalten | fertig
  end_at: epoch_ms|null
  rest_ms: int|null           # bei angehalten
  uebersprungen: [int]
  stumm: bool
  ist: { "<feldname>": "<wert>" }   # alle Formularfelder, bei Eingabe gespeichert
  gespeichert_am: epoch_ms    # Ablauf nach 12 h
```

### 6.6 Einstellungen (S8)

Bereich „Training“: Zeile „Timer-Signale“ mit Schalter Ton/Vibration `an`/`aus` (`app_setting.timer_ton`, Standard `an`); Untertext „Gilt für den geführten Modus; in der Einheit jederzeit umschaltbar“. Umsetzung als Formular mit zwei Radio-Optionen (kein JS nötig).

### 6.7 Offline

- `/einheit?id=…&modus=start` ist über `PAGE_PATHS` bereits cachefähig (Schlüssel enthält die Query). `WeekController::prefetch` nimmt für heutige und morgige geeignete Einheiten zusätzlich die Start-Adresse auf.
- Speichern offline: unverändert über den Formular-Puffer (`data-offline-form`); der Zustandsspeicher wird erst nach der Antwort 204/Weiterleitung gelöscht.
- Skript `js/gefuehrt.js` kommt in `PRECACHE` (mit Version).

### 6.8 Zugänglichkeit und Gestaltung

- Timer-Ziffern mind. 56 px (Smartphone), `font-variant-numeric: tabular-nums`; Phase als Text, nicht nur Farbe (`aria-live="polite"` für Phasenwechsel, „Arbeit – 45 Sekunden“).
- Touch-Ziele 44 px; Primäraktion unten fixiert (`.actions-sticky`).
- Kontrast: Text in `-700`-Statusfarbe auf `-100`-Grund (≥ 4,5:1).
- Kein Vollbild-API (bleibt in der App-Hülle; Vollbild bei „installierter“ App über `display: standalone` ohnehin).

## 7. Unterpunkte

Reihenfolge: T1 kann parallel zu T2–T7 laufen; T3 vor T4, T4 vor T5.

### T1 · App-Icon und Logo (Teil A)
- Variante: V3 App-Icon, V2 Favicon und Kennung (E-21).
- Icon-Satz nach E-09 erzeugen (Skript einchecken), `build-assets.php` auf das gewählte SVG umstellen, beide Layouts ergänzen, Manifest erweitern, `favicon.ico`, `.htaccess`, Service Worker.
- Tests: Manifest gültiges JSON mit allen Icon-Dateien vorhanden und Größen stimmig (PHPUnit liest PNG-Header); Layout-Tests auf die Link-Einträge; `HEAD /favicon.ico` 200 über den Dev-Router.
- **Abnahme:** P-A1 bis P-A6 (4.1) durch den Athleten; Screenshots im Prüfprotokoll.

### T2 · Begründungstexte (Teil B)
- Migration `coach_summary`; Repositories; `PlanValidator`/Schreibtools mit Längen- und Pflichtprüfung (E-10); Lese-Tools; Tool-Beschreibungen; Kalenderbeschreibung; S2/S3 mit `<details class="more">`.
- Tests: Schreibtool ohne `focus`/`coach_summary` → Fehler mit Liste; Grenzlängen 200/255/1500; Overview enthält `kurz`/`begruendung`; Templates zeigen Kurzsatz und „mehr“; Altdaten ohne Kurzsatz.
- **Abnahme:** Plan aus dem Projekt-Chat mit beiden Texten erscheint in S2 und S3; „mehr“ klappt ohne JavaScript auf; Kalendertermin zeigt den Kurzsatz.

### T3 · Ablaufplan (Teil C, Logik)
- Klasse `Training\Plan\Ablaufplan` (reine Funktion `plan_json` + Typ → Schritte, 6.3) mit Unit-Tests für alle Regeln und alle `kind`-Werte; Schrittfolge als JSON für das Skript.
- **Abnahme:** Testfälle 8.1 grün.

### T4 · Seite S9 ohne Skript (Teil C, Grundgerüst)
- Route-Modus `modus=start`, Template `session-start.php`, Startknopf in S3, Fortschritt/Phase-Karte/Ist-Felder/Als-Nächstes/Abschluss, `.needs-js`-Elemente, Speichern über den bestehenden POST.
- Tests: Rendering für Kraft (Wiederholungen + Halten), Klettern (Hangboard, Block, offen); POST aus S9 speichert wie aus S3; Ausdauer/Ruhe → kein Startknopf, `modus=start` fällt auf S3 zurück.
- **Abnahme:** ohne JavaScript vollständig ausfüllbar und speicherbar; Layout auf 375 px ohne horizontales Scrollen.

### T5 · Skript: Timer, Signale, Farbe, Zustand (Teil C)
- `js/gefuehrt.js`: Schrittanzeige, Zustandsautomat (6.3), Timer (E-16), Signale (E-17), Farbe/`theme-color` (E-20), Wake Lock und Vibration (E-06), Stummschalter (E-18), Zustandsspeicher (E-19), Fortsetzen/Neu starten, Dauer-Vorbelegung.
- Tests: Zustandsautomat und Signalplanung als reine Funktionen mit Node-Tests (ohne Browser); Playwright-Durchlauf (vorhandenes Chromium in CI) mit verkürzten Zeiten: Farbwechsel, Signal-Aufrufe (gemockt), Neuladen setzt fort.
- **Abnahme:** Gerätetest durch den Athleten (Android): Töne und Vibration bei Start/30/10/3-2-1, Grün/Rot, Bildschirm bleibt an, Stumm in der Einheit und in S8.

### T6 · Einstellungen, Offline, Prüfprotokoll
- S8-Bereich „Training“ (6.6); Prefetch der Start-Adresse (6.7); `PRECACHE`; Prüfprotokoll-Einträge für T1–T6.
- **Abnahme:** Einstellung wirkt als Standard in S9; S9 öffnet im Flugmodus aus dem Cache; Speichern offline landet im Puffer und wird nachgesendet.

### T7 · Dokumentation, Changelog, Version
- Version hochstufen; CHANGELOG; README (neue Seite, neue Einstellung, neues Skript, Icons); Hauptkonzept (Statusblöcke AP-13/AP-14, Abschnitte 7, 8.2, 10, Änderungsprotokoll); `datenmodell.md` (`coach_summary`); `branding.md` (B-03 angepasst, B-08, Abschnitt 8); dieses Dokument (Abschnitt 12) je erledigtem Unterpunkt.
- **Abnahme:** Dokumente konsistent (Feldnamen, Tool-Namen, Screens); Changelog nennt alle drei Teile.

## 8. Testfälle

### 8.1 Ablaufplan (T3)

| nr | eingabe | erwartung |
|---|---|---|
| A-01 | kraft `{name:"Kniebeuge", sets:3, reps:"8", rest_s:120}` | wiederholungen, saetze 3, pause_s 120, arbeit_s null |
| A-02 | kraft `{sets:3, reps:"6-8"}` | wiederholungen, pause_s null |
| A-03 | kraft `{sets:3, reps:"45s", rest_s:60}` | halten, arbeit_s 45, pause_s 60 |
| A-04 | kraft `{sets:2, reps:"2 min"}` | halten, arbeit_s 120 |
| A-05 | kraft `{sets:3, reps:"30-45 s"}` | halten, arbeit_s 45 |
| A-06 | kraft `{sets:1, reps:"max"}` | wiederholungen, saetze 1 |
| A-07 | klettern `{kind:"hangboard", hang_s:7, rest_s:3, sets:6}` | halten, arbeit_s 7, pause_s 3, saetze 6 |
| A-08 | klettern `{kind:"hangboard", hang_s:10, sets:null}` | halten, saetze 1, pause_s null |
| A-09 | klettern `{kind:"bouldern_volumen", duration_min:40}` | block, arbeit_s 2400, saetze 1 |
| A-10 | klettern `{kind:"technik"}` (ohne Zeiten) | offen |
| A-11 | ausdauer / ruhe | kein Ablaufplan; S3 ohne Startknopf; `modus=start` → S3 |
| A-12 | Reihenfolge | Schritte in Planreihenfolge, Index = Feldindex `ist[i]` |

### 8.2 Zustandsautomat und Signale (T5)

| nr | ablauf | erwartung |
|---|---|---|
| Z-01 | halten 45 s, 3 Sätze, Pause 60 s: Start | Phase arbeit, grün, Startton; bei 10 s und 3/2/1 Töne; kein 30-s-Ton (< 45 s) |
| Z-02 | … Ende Satz 1 | Phase pause 60 s, rot; Töne 30 s, 10 s, 3/2/1 |
| Z-03 | … Ende Satz 3 | Abschlusston, Phase fertig, Farbe normal, Knopf „Weiter“ |
| Z-04 | Pause drücken bei 20 s Rest | angehalten, rot, `rest_ms` 20 000; Fortsetzen → 20 s laufen weiter |
| Z-05 | Tab 2 min im Hintergrund während 45-s-Phase | bei Rückkehr: Phase beendet, Folgephase korrekt berechnet, ein Hinweiston, keine nachgeholten Signale |
| Z-06 | Neu laden während Phase arbeit | Fortsetzen-Dialog; Fortsetzen stellt Schritt/Satz/Restzeit her |
| Z-07 | wiederholungen, 3 Sätze, Pause 90 s: „Satz erledigt“ | Pausentimer startet automatisch (rot); nach Satz 3 kein Timer, fertig |
| Z-08 | Stumm in S9 | keine Töne/Vibration; Blinken in den letzten 3 s; Einstellung S8 unverändert |
| Z-09 | S8 `timer_ton = aus` | S9 startet stumm; Schalter in S9 kann für diese Einheit einschalten |
| Z-10 | Überspringen einer Übung | Ist-Felder bleiben Soll (wie S3), Status-Vorbelegung `teilweise` |
| Z-11 | Abschluss ohne Eingabe der Dauer | `duration_min` = gemessene Minuten seit erstem Start |
| Z-12 | Speichern offline | Puffer-Eintrag; Zustandsspeicher erst nach Zustellung gelöscht |

### 8.3 Icon (T1)

| nr | prüfung | erwartung |
|---|---|---|
| I-01 | Manifest-Test | JSON gültig, jede `src` existiert, `sizes` = PNG-Kopf |
| I-02 | Layout-Tests | beide Layouts enthalten Manifest, PNG-Icons mit `sizes`, `apple-touch-icon`, `theme-color` |
| I-03 | Dev-Router | `HEAD /favicon.ico` 200, `Content-Type image/x-icon` |
| I-04 | Gerätetests P-A1 bis P-A6 | Lama-Icon in Chrome, LibreWolf (von `/login` und `/woche`), iOS |

## 9. Offene Punkte

| id | punkt | status |
|---|---|---|
| O-01 | Wahl der Logo-Variante (V1–V5, Mischung möglich) anhand `icon-optionen.html` | erledigt 2026-09-28 → E-21 / D-59 |
| O-02 | Hangboard mit Wiederholungen **und** Sätzen (z. B. Repeaters 7/3 × 6, 3 Sätze) im Schema `plan-klettern.json` (`reps` je Satz, `rest_between_sets_s`) | nicht im Umfang; bei Bedarf eigener kleiner Auftrag |
| O-03 | Ergebnis von P-A1/P-A3 vor der Umsetzung | P-A1 erledigt 2026-09-28: Chrome zeigt das Icon; Fehler ist LibreWolf-spezifisch (Favicon-Weg). P-A3 nach T1 |
| O-04 | Tonhöhen/-längen aus E-17 sind Startwerte; Feinabstimmung nach Gerätetest | in T5 |
| O-05 | Screenshots im Manifest (`screenshots` mit `form_factor` wide/narrow) für die ausführlichere Installationsansicht in Chrome; ohne sie zeigt DevTools zwei Hinweise (P-A2) | nicht im Umfang (4.2 verlangt sie nicht); bei Wunsch des Athleten kleiner Nachtrag |

## 10. Nicht im Umfang

- Geführte Ausdauereinheiten (laufen auf der Uhr, D-07).
- Speichern von Zwischenständen auf dem Server (E-07), Push-Benachrichtigungen (N6), Hintergrund-Audio-Garantie bei ausgeschaltetem Bildschirm.
- Vibration auf iOS (nicht unterstützt), Dunkelmodus (B-04), Vollbild-API.
- Automatische Aufteilung alter `coach_rationale`-Texte in Kurzsatz und Rest.
- Änderungen an `plan_json`-Schemata (O-02).

## 11. Arbeitsweise für die Umsetzung

- Unterpunkte T1–T7 in der Reihenfolge aus Abschnitt 7.
- Nach jedem Unterpunkt: geänderte und neue Dateien als ZIP mit Repo-Ordnerstruktur (nur geänderte/neue Dateien), dieses Dokument (Abschnitt 12) aktualisiert, dazu ein Prüfdokument (was geprüft ist, was noch wie zu prüfen ist; Struktur wie `docs/pruefung/pruefprotokoll.md`).
- Konzeptänderungen aus der Umsetzung in Abschnitt 12 (`probleme_loesungen`) und im Hauptkonzept (AP-13/AP-14) nachziehen.

## 12. Umsetzungsstand

```yaml
T1:
  status: umgesetzt          # Code-Stand 0.17.0; Abnahme P-A1 (erneut mit V3) bis P-A6 durch den Athleten offen
  datum: 2026-09-28
  ergebnis: >
    Icon-Satz nach E-09/E-21: V3 als lama-48/96/192/512.png (any), lama-512-maskable.png, apple-touch-icon-180.png;
    V2 als icons/favicon.svg und favicon.ico (16/32/48, PNG-Einträge) im Docroot. Skript docs/branding/build-icons.cjs
    (Playwright/Chromium) rendert aus docs/branding/mockups/icon-optionen/; Ergebnis eingecheckt. Kopfteil
    templates/_head_icons.php in layout-app und layout-auth (theme-color, SVG-Favicon, PNG-Icons mit sizes,
    apple-touch-icon 180, Manifest). Manifest mit id /woche und description. build-assets.php kopiert
    lama-symbol-flaeche.svg als assets/lama.svg (Kennung in Topbar, Navigation, Login-Karte). .htaccess: AddType
    image/x-icon, Cache-Control 7 Tage für Icons/Favicon. Service Worker: /favicon.ico wie das Manifest aus dem
    Versions-Cache, PRECACHE lama.svg. Dev-Router liefert .ico/.webmanifest mit Apache-Typen. Mockups (Kennung,
    Favicon) angepasst; alle 38 Screenshots neu gerendert, 27 davon geändert (docs/branding/mockups/screenshots.cjs).
  tests: AppIconTest (I-01 Manifest und PNG-Köpfe, Kopfteil-Links, favicon.ico, I-03 HEAD /favicon.ico über den Dev-Router), AppIconPagesTest (I-02 beide Layouts)
  abnahme_offen: P-A1 erneut mit V3, P-A2 bis P-A6 (Abschnitt 4.1), Screenshots ins Prüfprotokoll
  probleme_loesungen:
    - was: SVG lässt sich auf dem Server (PHP ohne Imagick) nicht rendern; ein PHP-Skript build-icons.php ist damit nicht möglich
      loesung: Node-Skript mit Playwright/Chromium in docs/branding/ (im Auftrag als Alternative genannt); ICO wird im Skript aus PNG-Einträgen zusammengesetzt
    - was: Die alten Icon-Dateien icon-192/512(-maskable).png hätten beim Motivwechsel denselben Namen behalten; Browser und Launcher halten Icons lange im Cache
      loesung: neue Dateinamen lama-*.png (4.2 Punkt 5 „Icons ändern sich mit Dateinamen“), alte Dateien entfernt
    - was: Apache (mime.types) liefert .ico als image/vnd.microsoft.icon, der PHP-Dev-Server ebenso; P-A6 und I-03 erwarten image/x-icon
      loesung: AddType image/x-icon .ico in public/.htaccess; der Dev-Router setzt den Typ für .ico und .webmanifest selbst
    - was: Welche Datei ist das SVG-Favicon – die freigestellte Kennung oder V2 mit Papiergrund?
      loesung: V2 mit Papiergrund (icons/favicon.svg, E-21 „V2 … auf Papier“ für das SVG-Favicon), damit das Lama auch in dunklen Browserleisten lesbar bleibt; die Kennung in der App ist freigestellt (lama-symbol-flaeche.svg, 4.3 „Folgen“)
    - was: Cache-Regel nur für /icons/ per eigener .htaccess im Unterordner würde die Rewrite-Regeln des Docroots für diesen Ordner aufheben
      loesung: FilesMatch auf die Icon-Dateinamen in public/.htaccess
    - was: Review nach T1 – P-A2 erwartet „keine Warnungen“; Chrome-DevTools meldet ohne `screenshots` im Manifest immer zwei Hinweise zur ausführlicheren Installationsansicht (Desktop/Mobil). 4.2 verlangt keine Screenshots
      loesung: Erwartung in P-A2 präzisiert (keine Fehler, nur diese zwei Hinweise); Screenshots als O-05 offen, nicht umgesetzt
    - was: Review nach T1 – Dev-Router-Test nahm jeden erreichbaren Port als eigenen Server an, auch wenn php -S wegen belegtem Port sofort endete
      loesung: freier Port vom System (stream_socket_server Port 0), Server gilt nur als gestartet, solange der Prozess läuft; fehlgeschlagene Versuche werden beendet
    - was: Versionsnummer je Unterpunkt oder je AP?
      loesung: je AP (CLAUDE.md „nach jedem AP“): AP-13 (T1, T2) = 0.17.0, AP-14 (T3–T7) = 0.18.0; Changelog, README, Konzept und Prüfprotokoll werden nach jedem Unterpunkt nachgezogen
T2:
  status: umgesetzt          # Code-Stand 0.17.0; Abnahme (Plan aus dem Projekt-Chat, S2/S3, Kalender) durch den Athleten offen
  datum: 2026-09-28
  ergebnis: >
    Migration 0022_session_coach_summary (session.coach_summary VARCHAR(200) NULL nach plan_json, Rückweg als Kommentar),
    Schema 22. WriteTools: write_week_plan verlangt focus (1–255) und je Einheit außer ruhe coach_summary (1–200),
    coach_notes/coach_rationale ≤ 1500; Längen in Zeichen (mb_strlen), Texte getrimmt, leerer Text = NULL; alle Fehler
    als Liste (sessions[i]: …), nichts geschrieben. update_session: coach_summary (nicht leer, ≤ 200) und coach_rationale
    (leer = entfernen, ≤ 1500) änderbar, focus/coach_notes dort unbekannte Felder (bestehendes COALESCE in upsertWeek
    bleibt). Tool-Beschreibungen mit der Regel aus E-10 je Feld (Konstanten FOCUS_MAX, SUMMARY_MAX, TEXT_MAX).
    ReadTools: get_week_overview woche.begruendung (nur ≤ 1500 Zeichen) und je Einheit kurz (nur wenn vorhanden),
    get_session_detail coach_summary. Kalender: Beschreibung Kurzsatz, Kurzplan (Priorität/Dauer/Status als erste
    Zeile), „Trainer: …“ (≤ 1000 Zeichen), Link. S2: Karte .begruendung unter der Kopfzeile (Kurzsatz, details.more.mehr),
    „Fokus …“ in der Kopfzeile entfällt. S3: Kurzsatz im Seitenkopf, „mehr“ bzw. „Trainer-Notiz“ bei Altdaten.
    CSS in training.css (.kurz, details.mehr mit drehendem Chevron, Zeilenumbrüche des Texts bleiben).
  tests: McpToolsTest::testPlanTextsAreRequiredLimitedAndReadable, WebsiteTest::testWeekAndSessionShowSummaryWithMore, SessionEventTest::testDescriptionStartsWithSummaryAndShortensLongRationale; bestehende Tests um focus/coach_summary ergänzt, Migrationstest AP-12 um den Rückweg von 0022
  abnahme_offen: Plan aus dem Projekt-Chat mit beiden Texten erscheint in S2 und S3; „mehr“ klappt ohne JavaScript auf; Kalendertermin zeigt den Kurzsatz
  probleme_loesungen:
    - was: update_session prüft die zusammengeführte Einheit; eine überlange Begründung aus der Zeit vor AP-13 hätte danach jede Änderung (z. B. Status) blockiert
      loesung: unveränderte Begründungstexte werden nicht erneut geprüft; nur übergebene Felder unterliegen den Grenzen
    - was: Kurzsatz-Pflicht auch in update_session? Altdaten haben keinen Kurzsatz
      loesung: Pflicht nur in write_week_plan (5.2); in update_session darf coach_summary fehlen, wird er übergeben, muss er 1–200 Zeichen haben
    - was: 5.2 „begruendung nur, wenn ≤ 1500 Zeichen“ – Altdaten können länger sein
      loesung: wörtlich umgesetzt – längere Altwochen-Texte fehlen in get_week_overview (Budget 8.3), bleiben aber in S2 sichtbar
    - was: Woche mit ausführlichem Text, aber ohne Kurzsatz (Altdaten) – wie „mehr“ beschriften?
      loesung: analog S3 („Trainer-Notiz“): Summary „Begründung der Woche“
    - was: Wochen-Kurzsatz focus: E-10 nennt 200 Zeichen für Kurzsätze, 5.1/5.2 für focus 1–255 (Spaltenlänge)
      loesung: Prüfung 1–255 wie 5.2 und Testfall „Grenzlängen 200/255/1500“; die Tool-Beschreibung nennt 255
    - was: Review nach T2 – ein Kurzsatz an einem Ruhetag (erlaubt, E-10) fehlte in get_week_overview und ließ sich mit update_session nicht mehr entfernen
      loesung: Ruhetag-Zeile der Übersicht trägt kurz, wenn vorhanden; update_session entfernt den Kurzsatz eines Ruhetags mit leerem Text (bei anderen Typen bleibt leer ein Fehler)
    - was: Review nach T2 – ein langes Wort (z. B. eine Adresse) im Kurzsatz ließ S2/S3 auf 375 px seitlich scrollen
      loesung: .kurz bricht lange Wörter um (overflow-wrap:anywhere)
    - was: Test-Hilfe rollbackLastMigration setzte bei Spaltenänderungen nur den Schemastand zurück; 0022 (ADD COLUMN) ließ sich danach nicht erneut einspielen (BackupTest rot)
      loesung: die Hilfe entfernt beim Zurücksetzen neu angelegte Spalten (ADD COLUMN); Seiten und Lese-Tools vertragen die fehlende Spalte während der Schreibsperre
T3:
  status: umgesetzt          # Code-Stand 0.18.0
  datum: 2026-09-28
  ergebnis: >
    Training\Plan\Ablaufplan: schritte(type, plan) liefert je Übung/Block {index, quelle, name, soll, notiz, art,
    saetze, arbeit_s, pause_s, ist_felder} in Planreihenfolge (index = Feldindex ist[i]) oder null (ausdauer, ruhe,
    kein Plan, leere Liste); geeignet() für E-13; json() für data-ablauf; holdSeconds() für die Regeln aus 6.3.
    Soll-Texte über Training\View\PlanFormat (aus dem S3-Template herausgelöst, S3 nutzt dieselbe Klasse).
    Zusätzlich zum Schema 6.3: Feld notiz (Hinweis der Übung aus plan_json, für die Anzeige in S9).
  tests: AblaufplanTest (A-01 bis A-12 als Datenfälle, jeder Fall zusätzlich gegen das plan_json-Schema geprüft; Schreibweisen der Haltezeit)
  probleme_loesungen:
    - was: 6.3 lässt offen, was rest_s = 0 bedeutet
      loesung: 0 = keine Pausenphase (pause_s null), ebenso hang_s/duration_min 0 bzw. Haltezeit 0 → kein Timer
    - was: Die Regex in 6.3 kennt beim Haltebereich nur „-“ und die Einheiten s/sek; im Deutschen ist „30–45 s“ (Halbgeviertstrich) üblich, bei Einzelwerten erlaubt 6.3 auch „sec“ und einen Punkt
      loesung: Bereich akzeptiert zusätzlich „–“ und dieselben Einheiten wie der Einzelwert (s, sek, sec, mit Punkt); alle Testfälle aus 8.1 unverändert
    - was: Klettern-Block mit hang_s und duration_min (z. B. Hangboard 20 min, 10 s, 5 Sätze)
      loesung: Regelreihenfolge aus 6.3 – hang_s geht vor (halten), duration_min bleibt Ist-Feld wie in S3
T4:
  status: umgesetzt          # Code-Stand 0.18.0; Abnahme (ohne JavaScript ausfüllbar, 375 px) automatisiert und im Browser geprüft
  datum: 2026-09-28
  ergebnis: >
    SessionController: GET mit modus=start und Ablaufplan::geeignet → Template session-start.php (S9) mit denselben
    Vorbelegungen wie S3, sonst S3 (Ausdauer, Einheit ohne Plan); Ruhetag weiter 404. POST unverändert; das Formular
    trägt modus=start, damit 422/409 wieder S9 zeigen. S3: Knopf „Einheit starten“ (primär) bzw. „Erneut durchgehen“
    (sekundär, Status erledigt) im Seitenkopf. S9: layout-app mit Zurück-Pfeil zu S3, Titel der Einheit, Stummschalter
    (needs-js) in der Kopfzeile, main.gefuehrt (720 px); Kurzsatz oben (gf-intro); je Schritt section.gf-step mit
    Phase-Karte (Übung x von n und Sätze, Name, Phase-Marke, Timer mm:ss bzw. „n Wdh.“ mit Last/Tempo, Soll, Hinweis),
    Ist-Karte (Teilvorlagen wie S3) und „Als Nächstes“; Abschluss mit Übersicht, Rückmeldung (Teilvorlage wie S3) und
    Speichern. Skript-Bedienung (Fortschritt, Fortsetzen-Hinweis, Aktionsleiste, Phase-Marke) als .needs-js, ohne
    JavaScript ausgeblendet. data-Attribute für das Skript: data-ablauf (JSON), data-session, data-ton (Einstellung
    timer_ton), data-dauer-plan (Dauer aus dem Plan darf durch die gemessene ersetzt werden), data-fehler.
    Ist-Felder und Rückmeldung als Teilvorlagen _ist_exercise, _ist_block, _feedback_fields (S3 nutzt sie ebenfalls).
  tests: GuidedSessionTest (Startknopf je Typ und „Erneut durchgehen“, A-11 Rückfall, Kraft mit Wiederholungen und Halten, Klettern mit Hangboard/Block/offen, POST aus S9 speichert wie S3, 422 und 409 in S9, data-ton aus timer_ton); WebsiteTest (S3 unverändert nach Umbau in Teilvorlagen)
  abnahme: ohne JavaScript alle Schritte sichtbar, keine Skript-Bedienelemente sichtbar, ausgefüllt und gespeichert (Weiterleitung „Gespeichert.“, Werte in der Datenbank); 375 px ohne horizontales Scrollen (Kraft und Klettern) – Chromium/Playwright mit abgeschaltetem JavaScript, lokale Instanz
  probleme_loesungen:
    - was: 6.2 sagt „duration_min wird, wenn leer, mit der gemessenen Dauer vorbelegt“; S3 belegt die Dauer aber mit der geplanten Dauer vor, das Feld ist also nie leer
      loesung: ohne JavaScript wie S3 (geplante Dauer); das Skript ersetzt die Dauer nur, wenn sie aus dem Plan stammt (data-dauer-plan="1"), nie eine bereits gespeicherte oder vom Athleten geänderte
    - was: Fehler beim Speichern aus S9 (422/409) hätten bisher S3 gezeigt
      loesung: verstecktes Feld modus=start im S9-Formular; der Controller zeigt dann wieder S9 mit Hinweis und den Eingaben (data-fehler für das Skript)
    - was: Ohne JavaScript steht die Schaltfläche „Zur Einheit“ wie im Mockup neben „Speichern“; der Zurück-Pfeil in der Kopfzeile führt ebenfalls zu S3
      loesung: beibehalten (Mockup); mit JavaScript zusätzlich ein Symbolknopf „Zurück zur letzten Übung“
T5: {status: offen}
T6: {status: offen}
T7: {status: offen}
probleme_loesungen: []
```

## 13. Mockups (Fable, 2026-09-28)

| datei | inhalt |
|---|---|
| `docs/branding/mockups/s9-einheit-gefuehrt.html` | S9 mit Zuständen `?state=bereit` (Standard), `laeuft` (Arbeit, grün), `pause` (rot), `offen` (Übung ohne Timer, Ist-Eingabe), `abschluss` (Rückmeldung) |
| `docs/branding/mockups/s2-woche.html` | Kurzsatz der Woche + „mehr“ unter der Kopfzeile |
| `docs/branding/mockups/s3-einheit.html` | Kurzsatz + „mehr“ im Seitenkopf, Knopf „Einheit starten“ |
| `docs/branding/mockups/s8-einstellungen.html` | Bereich „Training“ mit Timer-Signalen an/aus |
| `docs/branding/mockups/icon-optionen.html` | Varianten V1–V5 als Android-/iOS-Icon, Favicon, Tab und Topbar |
| `docs/branding/mockups/index.html` | Übersicht um S9, Icon-Optionen und die geänderten Zustände ergänzt |
