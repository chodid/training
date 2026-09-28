# Auftrag: App-Icon, Begründungstexte der Planung und geführte Trainingseinheit

Ablageort im Repo: `docs/konzept/gefuehrte-einheit.md` (im Hauptkonzept: AP-13 und AP-14, D-55 bis D-59, Q-14; Nachtrag T8: AP-11, D-60; Nachtrag T9: AP-14)
Status: Konzept bestätigt durch Philipp am 2026-09-28 (Entscheidungen E-01 bis E-21, Nachtrag T8 mit E-22, Nachtrag T9 mit E-23); Logo-Variante gewählt (Q-14 → D-59: V3 als App-Icon, V2 als Favicon und App-Kennung); T1–T9 umgesetzt (Code-Stand 0.17.0 bis 0.20.0), Abnahme durch den Athleten offen (Stand in Abschnitt 12)
Versionsnummer: keine im Konzept; wird in der Umsetzung festgelegt

---

## 1. Ziel

Drei Wünsche des Athleten (dazu ein Nachtrag D während der Umsetzung), die zusammen bearbeitet werden, weil sie dieselben Seiten berühren (Woche, Einheit, Einstellungen) und gemeinsame Mockups brauchen:

| teil | ziel |
|---|---|
| A · App-Icon und Logo | „Zum Startbildschirm“ zeigt auf Android (LibreWolf, idealerweise alle Browser) das App-Logo statt eines Platzhalters. Das Logo der App wechselt vom Lama-Kopf auf das **ganze Lama**; die Variante wählt der Athlet anhand einer Mockup-Seite mit mehreren Optionen. |
| B · Begründungstexte | Die planende Instanz (Claude im Projekt-Chat) schreibt je **Woche** und je **Einheit** einen kurzen Text zu „Was und warum“. Ein zusammenfassender Satz steht sichtbar bei Woche bzw. Einheit; „mehr“ daneben öffnet den restlichen Text. Klein gehalten: Ziele und Pläne, die dem Training zugrunde liegen, keine Abhandlung. |
| C · Geführte Einheit | Eine Einheit lässt sich „starten“. Die App führt dann Schritt für Schritt durch das Training: aktuelle Übung mit Dauer/Wiederholungen, was als Nächstes kommt, Timer für getimte Übungen (App wird grün, solange gearbeitet wird; Tonsignale beim Start, 30 s und 10 s vor Ende sowie in den letzten 3 Sekunden; stummschaltbar in den Einstellungen und in der laufenden Einheit). Ohne Timer wird zumindest die aktuelle Übung angezeigt, und die Ist-Werte (Wiederholungen, Gewicht …) lassen sich direkt eintragen – wie sonst in der Einheitenübersicht. |
| D · Kalender (Nachtrag T8) | Im CalDAV-Kalender erscheint je Tag nur **ein** Sammeltermin statt eines Termins je Einheit; alle Einheiten des Tages stehen in dessen Beschreibung (Wunsch des Athleten während der Umsetzung, 2026-09-28). |

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

Mit dem Athleten am 2026-09-28 geklärt (E-01 bis E-07). E-08 bis E-20 waren Vorschläge von Fable und gelten seit der Bestätigung des Konzepts am 2026-09-28. E-21 ist die Logo-Wahl. E-22 (Nachtrag T8) und E-23 (offene Punkte O-05 bis O-07, Nachtrag T9) hat der Athlet während bzw. nach der Umsetzung am 2026-09-28 festgelegt.

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
| E-11 | **Anzeige des Kurzsatzes:** Woche: eigene Zeile unter der Kopfzeile der Woche (Kurzsatz + „mehr“); die bisherige Angabe „Fokus …“ in der Kopfzeile entfällt. Einheit: Kurzsatz im Seitenkopf statt „Trainer-Notiz: …“ (+ „mehr“). Wochenliste: **kein** Kurzsatz je Einheit (bleibt kompakt). Kalendertermin: Kurzsatz als erste Zeile der Beschreibung, ausführlicher Text danach (seit E-22/D-60 bei mehreren Einheiten eines Tages je Abschnitt nach der Überschrift „Typ: Titel“). | Wunsch: „bei Woche / Einheit“; Wochenliste bleibt auf dem Handy lesbar. |
| E-12 | **„mehr“ ohne JavaScript:** `<details class="more">` mit `<summary>` (bereits im CSS für AP-12), aufklappbar per Tipp, keine Skripte. | Branding 7.3, CSP. |
| E-13 | **Einstieg in die geführte Einheit** nur auf S3 (Knopf „Einheit starten“ als Primäraktion im Seitenkopf) für die Typen `kraft`, `haltung`, `mobilitaet`, `klettern` mit vorhandenem Plan. Nicht für `ausdauer` (läuft auf der Uhr) und `ruhe`. In der Wochenliste kein Zusatzknopf. | Ein Tipp mehr, dafür bleibt S2 unverändert; Ausdauer ist auf der Uhr geführt. |
| E-14 | **Adresse:** `GET /einheit?id=<id>&modus=start` rendert S9; `POST /einheit` bleibt der einzige Speicherweg (gleiche Feldnamen wie S3). Kein neuer Controller-Endpunkt, nur ein zweites Template. | Konfliktschutz (`stand`), Offline-Puffer und Validierung werden wiederverwendet. |
| E-15 | **Ablaufplan serverseitig:** Der Server leitet aus `plan_json` deterministisch die Schrittfolge ab (Abschnitt 6.3) und gibt sie als HTML (ein Abschnitt je Übung) plus JSON in einem `data-ablauf`-Attribut aus. Das Skript zeigt jeweils einen Schritt; ohne JavaScript sind alle Abschnitte sichtbar und die Einheit bleibt wie S3 ausfüllbar. | Reine, testbare PHP-Funktion; Fallback ohne JS. |
| E-16 | **Timer zeitstempelbasiert:** Endzeit als Zeitstempel, Anzeige alle 250 ms; nach Bildschirm aus / Tabwechsel wird nachgerechnet, verpasste Signale werden nicht nachgeholt (ein Hinweiston beim Zurückkehren, wenn eine Phase inzwischen endete). | Browser drosseln Timer im Hintergrund; so bleibt die Zeit richtig. |
| E-17 | **Signale ohne Audiodateien:** Web Audio (Oszillator) mit vier Mustern: Start (zwei kurze hohe Töne), 30 s vor Ende (ein Ton, nur bei Phasen über 45 s; E-23), 10 s vor Ende (ein Ton, nur bei Phasen ≥ 15 s), letzte 3 s (drei kurze Ticks bei 3, 2, 1). Ende der letzten Phase einer Übung: ein längerer Abschlusston. Vibration mit denselben Mustern (`navigator.vibrate`). Audio wird beim ersten Tipp auf „Start“ freigeschaltet. | Keine Dateien, kein Cache, CSP-konform (`script-src 'self'`); Browser verlangen eine Nutzergeste für Audio. |
| E-18 | **Stummschalten zweistufig:** Einstellung `timer_ton` (`an`/`aus`, Standard `an`) in `app_setting`, änderbar in S8; in S9 ein Schalter in der Kopfzeile, der nur für diese Einheit gilt (im Browser gemerkt, nicht auf dem Server). | Wunsch: Einstellungen und in der Einheit. |
| E-19 | **Fortschritt im Browser:** `sessionStorage`-Eintrag je Einheit (Schritt, Satz, Phase, Endzeit, Ist-Werte, Startzeit, stumm). Neu laden setzt den Stand fort; „Neu starten“ löscht ihn. Nach Speichern gelöscht. Kein Serverzustand (E-07). | Robust gegen versehentliches Neuladen; keine Konflikte mit dem Offline-Puffer. |
| E-20 | **Farben nur über Statusfarben des Design-Systems:** Arbeit = `--status-success-bg` als Seitengrund, Zeit in `--status-success-text`; Pause/bereit/angehalten = `--status-error-bg` und `--status-error-text`; `theme-color` wird mitgeführt. Neue Branding-Entscheidung B-08 (Statusfarben dürfen als Flächen für den Timer-Zustand dienen). | Rot/Grün mit ausreichendem Kontrast, ohne neue Farben. |
| E-21 | **Logo-Wahl (Q-14 → D-59):** V3 (Lama Fläche hell `#F4EFF2` auf Pflaume 600 `#7A5C94`, Auge Orange) für App-Icon Android/iOS und `maskable`; V2 (Lama Fläche Pflaume 600 auf Papier) für Favicon 16/32 px, SVG-Favicon und App-Kennung in Topbar, Navigation und Login-Karte. Vorlagen: `docs/branding/mockups/icon-optionen/v3.svg`, `v3-maskable.svg`, `v2.svg`. | Entscheidung des Athleten am 2026-09-28, wie von Fable empfohlen. |
| E-22 | **Ein Sammeltermin je Tag (Nachtrag T8 → D-60):** Je Trainingstag ein ganztägiger Termin statt eines Termins je Einheit (Ruhetage weiter ohne Termin). Titel aus den Einheitentiteln: eine Einheit „Typ: Titel“ wie bisher, mehrere „Training: Titel 1 + Titel 2“ in Planreihenfolge. **Kein Status-Zeichen im Titel**, der Termin wird nie abgesagt; der Status steht je Einheit in der Beschreibung. Beschreibung: alle Einheiten mit Kurzsatz, Kurzplan, Begründung und Link; Erinnerung einmal je Tag, solange eine Einheit geplant oder verschoben ist. Umfang: Unterpunkt T8 in diesem Auftrag, eigener Code-Stand, derselbe Pull Request wie T1–T7. | Wunsch des Athleten („pro Tag nur ein Sammeltermin“); Titel, Status-Darstellung und Umfang am 2026-09-28 per Rückfrage gewählt (Titel und Umfang wie empfohlen; beim Status „kein Zeichen im Titel“ statt der Empfehlung „✓, sobald der Tag abgeschlossen ist; abgesagt, wenn alle ausgelassen“). |
| E-23 | **Offene Punkte aus der Umsetzung (Nachtrag T9):** O-06 – der 30-s-Ton kommt erst bei Phasen über 45 s (wie Testfall Z-01 und die Umsetzung; E-17 und 6.4 angeglichen). O-07 – Kletterblöcke ohne Haltezeit und ohne Dauer mit mindestens zwei Sätzen werden wie Kraftübungen satzweise geführt: „Satz erledigt“ je Satz, danach Pausentimer, wenn `rest_s` geplant ist (auch ohne Pause satzweise); ein Satz ohne Zeiten bleibt „offen“. O-05 – keine Screenshots im Manifest. Zusätzlich: Die fixierten Speichern-Leisten (S3, Check-in, Schmerz …) sitzen auf dem Smartphone über der unteren Navigation wie in S9. | Entscheidung des Athleten am 2026-09-28 nach der Rückfrage zu den offenen Punkten, jeweils wie empfohlen; für die Leisten „alle Seiten“ statt nur S3, weil dieselbe Regel die Ursache war. |

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
- **Kalender (AP-11):** Beschreibung = Kurzsatz, Leerzeile, Kurzplan, Leerzeile, ausführlicher Text (gekürzt auf 1 000 Zeichen), Link. Seit T8 (E-22) je Einheit ein solcher Abschnitt, bei mehreren Einheiten mit Überschrift „Typ: Titel“ davor und Trennlinie dazwischen.
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
    sonst sets ≥ 2                     -> wiederholungen wie Kraft, saetze = sets, pause_s = rest_s (E-23)
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
| 30 s vor Ende | 1 × 150 ms, 660 Hz | 150 ms | Phase über 45 s (E-23, Z-01) |
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
# Umsetzung (T5, Review): zusätzlich u. a. fertig [int], ende_um (Ende der Dauermessung) und ein eigener Schlüssel
# training.gefuehrt.<session_id>.stumm ('1'/'0') für die Stumm-Wahl vor der ersten Eingabe – Details Abschnitt 12, T5
```

### 6.6 Einstellungen (S8)

Bereich „Training“: Zeile „Timer-Signale“ mit Schalter Ton/Vibration `an`/`aus` (`app_setting.timer_ton`, Standard `an`); Untertext wie im Mockup S8: „Ton und Vibration im geführten Modus (Start, 30 s, 10 s, 3-2-1) · in der Einheit jederzeit umschaltbar“ (zuvor hier „Gilt für den geführten Modus; in der Einheit jederzeit umschaltbar“; angeglichen nach dem Abschluss-Review). Umsetzung als Formular mit zwei Radio-Optionen und „Speichern“ (kein JS nötig).

### 6.7 Offline

- `/einheit?id=…&modus=start` ist über `PAGE_PATHS` bereits cachefähig (Schlüssel enthält die Query). `WeekController::prefetch` nimmt für heutige und morgige geeignete Einheiten zusätzlich die Start-Adresse auf.
- Speichern offline: unverändert über den Formular-Puffer (`data-offline-form`); der Zustandsspeicher wird erst nach der Antwort 204/Weiterleitung gelöscht.
- Skript `js/gefuehrt.js` kommt in `PRECACHE` (mit Version).
- Die Vorgabe `timer_ton` steht in der gespeicherten Seite; S8 und jede online geladene S9 merken sie zusätzlich auf dem Gerät (`localStorage` `training.timer_ton`), eine offline gezeigte S9 nimmt diesen Wert (Abschluss-Review).

### 6.8 Zugänglichkeit und Gestaltung

- Timer-Ziffern mind. 56 px (Smartphone), `font-variant-numeric: tabular-nums`; Phase als Text, nicht nur Farbe (`aria-live="polite"` für Phasenwechsel, „Arbeit – 45 Sekunden“).
- Touch-Ziele 44 px; Primäraktion unten fixiert (`.actions-sticky`).
- Kontrast: Text in `-700`-Statusfarbe auf `-100`-Grund (≥ 4,5:1).
- Kein Vollbild-API (bleibt in der App-Hülle; Vollbild bei „installierter“ App über `display: standalone` ohnehin).

## 7. Unterpunkte

Reihenfolge: T1 kann parallel zu T2–T7 laufen; T3 vor T4, T4 vor T5. T8 (Nachtrag, unabhängig von T3–T6) nach T7, T9 (Nachtrag zu den offenen Punkten) nach T8.

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

### T8 · Sammeltermin je Tag im Kalender (Nachtrag, Teil D)
- `Training\Calendar\DayEvent` ersetzt `SessionEvent`: ein Termin je Tag (Ressource `training-tag-<Datum>.ics`, UID je Tag; nach einem Löschen neue Fassung `-1`, `-2` …, siehe Abschnitt 12), Titel und Beschreibung nach E-22, `STATUS:CONFIRMED`, `URL` = Woche, `CATEGORIES` = Typen des Tages, Erinnerung nach E-22.
- `CalendarSync`: `pushDays(Daten)` statt `push`/`remove` je Einheit; Abgleich je Tag, löscht verwaiste Sammeltermine und die alten Einzeltermine (`training-session-<id>.ics`) im Zeitraum.
- Aufrufer: `write_week_plan` (Tage der neuen und ersetzten Einheiten), `update_session` (alter und neuer Tag), Rückmeldung auf der Webseite (Tag der Einheit); Einstellungen und Cron unverändert (Abgleich).
- Texte in S8 („Am Trainingstag um …“), Hauptkonzept (D-60, AP-11, K8), README, Changelog, Prüfprotokoll; Version hochstufen.
- Tests: Testfälle 8.4.
- **Abnahme:** Nach dem Deploy und einem Abgleich zeigt der Nextcloud-Kalender je Trainingstag genau einen Termin; Tage mit zwei Einheiten tragen „Training: … + …“; Status und Verschieben wirken; alte Einzeltermine sind im Abgleichzeitraum verschwunden.

### T9 · Offene Punkte O-05 bis O-07 und Speichern-Leisten (Nachtrag, E-23)
- `Ablaufplan`: Kletterblock ohne `hang_s` und `duration_min` mit `sets` ≥ 2 → `wiederholungen` (Sätze wie geplant, `pause_s` = `rest_s`); S9 zeigt „n Sätze“ mit dem Ziel des Blocks darunter und den Pausentimer; Skript unverändert (Wiederholungslogik).
- CSS: `.actions-sticky` auf dem Smartphone über der unteren Navigation (alle Seiten mit fixierter Leiste).
- Dokumente: E-17, 6.3, 6.4, offene Punkte, Testfälle A-13/A-14; Hauptkonzept (D-57, AP-14), Changelog, Prüfprotokoll; Version hochstufen.
- Tests: Testfälle A-13/A-14 (AblaufplanTest), Aufbau in S9 (GuidedSessionTest); Sichtprüfung der Leisten auf 375 px (S3, Check-in, Schmerz, S9).
- **Abnahme:** Zugkraft-Block mit Pause wird satzweise mit Pausentimer geführt; Speichern bleibt beim Scrollen auf dem Smartphone sichtbar.

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
| A-13 | klettern `{kind:"zugkraft", sets:4, rest_s:120}` (ohne Haltezeit und Dauer, E-23) | wiederholungen, saetze 4, pause_s 120 |
| A-14 | klettern `{kind:"campus", sets:3}` (ohne Pause) bzw. `{kind:"technik", sets:1, rest_s:60}` | wiederholungen, saetze 3, pause_s null bzw. offen, saetze 1 |
| A-11 | ausdauer / ruhe | kein Ablaufplan; S3 ohne Startknopf; `modus=start` → S3 (Ruhetag: 404 wie bisher, er hat keine Einheitenseite) |
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

### 8.4 Kalender (T8)

| nr | eingabe | erwartung |
|---|---|---|
| K-01 | Tag mit einer Einheit (Kraft „Beine“, erledigt) | ein Termin, `SUMMARY:Kraft: Beine` ohne „✓“, `STATUS:CONFIRMED`, Beschreibung ohne Überschrift/Trennlinie, Status „erledigt“ in der Kopfzeile des Kurzplans |
| K-02 | Tag mit drei Einheiten (Kraft, Ausdauer, Klettern) | ein Termin `Training: A + B + C` in Planreihenfolge, `CATEGORIES` mit drei Typen, je Einheit ein Abschnitt mit Überschrift „Typ: Titel“, Link zur Einheit, Trennlinie dazwischen |
| K-03 | Erinnerung 05:00; eine Einheit geplant, übrige erledigt | ein `VALARM` `PT5H`; alle erledigt/teilweise/ausgelassen → kein `VALARM` |
| K-04 | `update_session` verschiebt eine von zwei Einheiten | alter Tag mit einer Einheit (`Typ: Titel`), neuer Tag angelegt; letzte Einheit weg → Termin des Tages gelöscht |
| K-05 | Woche ersetzen | Tage ersetzter Einheiten neu geschrieben bzw. gelöscht, neue Tage angelegt; Tage mit behaltenen Einheiten bleiben |
| K-06 | Abgleich mit alten Einzelterminen, verwaistem Sammeltermin und fremdem Termin | alte `training-session-<id>.ics` und verwaister Sammeltermin gelöscht, fremder bleibt; Zählung in Tagen |
| K-07 | Ruhetag allein bzw. neben einer Einheit | kein Termin bzw. Ruhetag nicht in Titel und Beschreibung |

## 9. Offene Punkte

| id | punkt | status |
|---|---|---|
| O-01 | Wahl der Logo-Variante (V1–V5, Mischung möglich) anhand `icon-optionen.html` | erledigt 2026-09-28 → E-21 / D-59 |
| O-02 | Hangboard mit Wiederholungen **und** Sätzen (z. B. Repeaters 7/3 × 6, 3 Sätze) im Schema `plan-klettern.json` (`reps` je Satz, `rest_between_sets_s`) | nicht im Umfang; bei Bedarf eigener kleiner Auftrag |
| O-03 | Ergebnis von P-A1/P-A3 vor der Umsetzung | P-A1 erledigt 2026-09-28: Chrome zeigt das Icon; Fehler ist LibreWolf-spezifisch (Favicon-Weg). P-A3 nach T1 |
| O-04 | Tonhöhen/-längen aus E-17 sind Startwerte; Feinabstimmung nach Gerätetest | Startwerte umgesetzt (T5); Feinabstimmung nach dem Gerätetest des Athleten |
| O-05 | Screenshots im Manifest (`screenshots` mit `form_factor` wide/narrow) für die ausführlichere Installationsansicht in Chrome; ohne sie zeigt DevTools zwei Hinweise (P-A2) | erledigt 2026-09-28 → E-23: nicht umsetzen |
| O-06 | 30-s-Ton: E-17/6.4 sagen „Phase ≥ 45 s“, Testfall Z-01 „bei 45 s kein 30-s-Ton“ | erledigt 2026-09-28 → E-23: 30-s-Ton erst bei Phasen über 45 s (wie umgesetzt) |
| O-07 | Kletterblöcke mit Sätzen und Pause, aber ohne Haltezeit und Dauer (z. B. Zugkraft 4 Sätze, Pause 120 s) sind nach 6.3 „offen“ (nur „Erledigt“, kein Pausentimer); ein Pausentimer wie bei Kraft-Wiederholungen wäre eine Regeländerung | erledigt 2026-09-28 → E-23: satzweise wie Kraft, umgesetzt in T9 |

## 10. Nicht im Umfang

- Geführte Ausdauereinheiten (laufen auf der Uhr, D-07).
- Speichern von Zwischenständen auf dem Server (E-07), Push-Benachrichtigungen (N6), Hintergrund-Audio-Garantie bei ausgeschaltetem Bildschirm.
- Vibration auf iOS (nicht unterstützt), Dunkelmodus (B-04), Vollbild-API.
- Automatische Aufteilung alter `coach_rationale`-Texte in Kurzsatz und Rest.
- Änderungen an `plan_json`-Schemata (O-02).

## 11. Arbeitsweise für die Umsetzung

- Unterpunkte T1–T9 in der Reihenfolge aus Abschnitt 7.
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
  tests: McpToolsTest::testPlanTextsAreRequiredLimitedAndReadable, WebsiteTest::testWeekAndSessionShowSummaryWithMore, SessionEventTest::testDescriptionStartsWithSummaryAndShortensLongRationale (seit T8 DayEventTest); bestehende Tests um focus/coach_summary ergänzt, Migrationstest AP-12 um den Rückweg von 0022
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
  tests: AblaufplanTest (A-01 bis A-10 und alle übrigen kind-Werte – campus, bouldern_limit, ausdauer_route, zugkraft, antagonisten – als Datenfälle, jeder zusätzlich gegen das plan_json-Schema geprüft; A-11 und A-12 als eigene Tests, A-11 auch mit leeren bzw. schemawidrigen Plänen, A-12 mit Schemaprüfung; Schreibweisen der Haltezeit)
  probleme_loesungen:
    - was: 6.3 lässt offen, was rest_s = 0 bedeutet
      loesung: 0 = keine Pausenphase (pause_s null), ebenso hang_s/duration_min 0 bzw. Haltezeit 0 → kein Timer
    - was: Die Regex in 6.3 kennt beim Haltebereich nur „-“ und die Einheiten s/sek; im Deutschen ist „30–45 s“ (Halbgeviertstrich) üblich, bei Einzelwerten erlaubt 6.3 auch „sec“ und einen Punkt
      loesung: Bereich akzeptiert zusätzlich „–“ und dieselben Einheiten wie der Einzelwert (s, sek, sec, mit Punkt); alle Testfälle aus 8.1 unverändert
    - was: Klettern-Block mit hang_s und duration_min (z. B. Hangboard 20 min, 10 s, 5 Sätze)
      loesung: Regelreihenfolge aus 6.3 – hang_s geht vor (halten), duration_min bleibt Ist-Feld wie in S3
    - was: Review nach T3/T4 – offene Kletterblöcke mit geplanten Sätzen (z. B. Zugkraft 4 Sätze) bekamen saetze 1; S9 zeigte „1 Satz“ neben „Soll … 4 Sätze“
      loesung: offen übernimmt sets (6.3 „Klettern: sets oder 1“), weiter ohne Timer und ohne Pausenphase; Blöcke mit Dauer zeigen „Block · n min“ statt einer Satzzahl (Sätze stehen im Soll)
    - was: Review nach T3/T4 – solche Blöcke haben oft auch rest_s; ein Pausentimer nach „Satz erledigt“ wie bei Kraft-Wiederholungen (E-03) sieht 6.3 für Klettern nicht vor
      loesung: nicht umgesetzt (wäre eine Regeländerung), als O-07 zur Entscheidung des Athleten
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
    - was: Review nach T3/T4 – „ohne Plan kein Startknopf“ (E-13) war nicht getestet
      loesung: GuidedSessionTest prüft S3 ohne Plan (kein Knopf) und modus=start ohne Plan (S3 mit Rückmeldung)
T5:
  status: umgesetzt          # Code-Stand 0.18.0; Gerätetest des Athleten (Android) offen
  datum: 2026-09-28
  ergebnis: >
    public/js/gefuehrt.js, synchron am Anfang von S9 geladen (setzt html.js vor dem Aufbau der Schritte, kein
    Aufblitzen). Kern als reine Funktionen (auch mit Node ladbar): signalPlan, signaleZwischen, takt (Zeitfortschritt
    über beliebig viele Phasen nach Zeitstempeln, Lücke > 2 s = Hintergrund: keine Signale, ein Hinweiston bei
    Phasenende), aktion (haupt/links/rechts/zurueck-abschluss), anzeige (Texte, Farbe, Zeit, Knöpfe, Fortschritt),
    statusVorbelegung, dauerMinuten, laden (Verfall 12 h, anderer Plan, Stand der Einheit geändert, Fehler nach POST).
    Seite: ein aktiver Abschnitt, Aktionsleiste links/Primär/rechts, body[data-phase] und meta theme-color
    (#DEF2D9 Arbeit, #FFE4E5 Pause/bereit/angehalten, sonst #7A5C94), Web Audio (Oszillator, Muster aus 6.4) und
    navigator.vibrate, Wake Lock ab dem ersten Start und nach Rückkehr in den Vordergrund, Stummschalter (aria-pressed)
    je Einheit mit Vorgabe aus timer_ton, Blinken statt Ton in den letzten 3 s, sessionStorage training.gefuehrt.<id>
    (Felder aus 6.5 plus signatur, stand, fertig, vor_anhalten, phase_ms, dauer_manuell, status_manuell, abgeschickt),
    Fortsetzen/Neu starten, Abschluss mit Dauer- und Statusvorbelegung und Übersicht, Ansage der Phasenwechsel in
    einer eigenen aria-live-Region (nicht jede Sekunde), Enter in Ist-Feldern schickt vor dem Abschluss nichts ab.
    Template: Pausentimer auch bei Wiederholungsübungen (nur mit Skript), aria-live von der Phase-Karte entfernt.
    CSS: Aktionsleisten auf dem Smartphone über der unteren Navigation.
  tests: >
    server/tests/js/gefuehrt.test.cjs (node --test, 17 Fälle: Z-01–Z-07, Z-10–Z-12 als Kern, Hangboard 7/3 × 6,
    Block, offen, Bedienung zurück/wiederholen, Halten ohne Pause; nach dem Review Tipp-Sperre, Zurück/Weiter mit
    erledigten Übungen, Schritt öffnen nach Ist-Fehler); server/tests/e2e/gefuehrt.e2e.cjs mit run.sh (Playwright,
    gesteuerte Uhr, Audio/Vibration/Wake Lock ersetzt: Z-01–Z-08, Z-10–Z-12, Speichern, 375 px, verworfener Tipp,
    Fokus, Stumm-Wahl vor dem Start; 14 Prüfungen); beide in der CI
  abnahme_offen: Gerätetest des Athleten auf Android (Töne und Vibration bei Start/30/10/3-2-1, Grün/Rot, Bildschirm bleibt an, Stumm in der Einheit und in S8)
  probleme_loesungen:
    - was: Widerspruch im Auftrag – E-17/6.4 „30 s vor Ende … nur bei Phasen ≥ 45 s“, Testfall Z-01 „halten 45 s … kein 30-s-Ton (< 45 s)“
      loesung: Z-01 ist Abnahmekriterium (8.2) und die Tonwerte sind Startwerte (O-04) – umgesetzt „Phase länger als 45 s“; zur Bestätigung durch den Athleten als O-06 geführt
    - was: 6.3 nennt für Wiederholungsübungen keinen Ton nach dem Pausentimer
      loesung: am Pausenende derselbe Startton wie vor einer Arbeitsphase (der nächste Satz beginnt); vorzeitiges „Pause beenden“ ohne Ton
    - was: Mockup-Aktionsleiste in der Pause (Satz wiederholen / Pause beenden / Überspringen) hätte Anhalten in der Pause nicht erlaubt; Z-04 und die Automatik (E-03) brauchen es
      loesung: linker Knopf in der Pause = Anhalten, Primär = Pause beenden; in der Arbeitsphase links = Satz neu starten, Primär = Anhalten; fertig: links = Übung wiederholen, Primär = Weiter bzw. Zum Abschluss
    - was: „Gemessene Dauer seit dem ersten Start“ bei einer Einheit, die mit einer Wiederholungsübung beginnt (dort gibt es keinen Start-Knopf)
      loesung: begonnen_um = erster Druck auf die Primäraktion (Start, Satz erledigt oder Erledigt); das Dauerfeld bleibt änderbar
    - was: Nach dem Speichern kann das Skript den Erfolg nicht sehen (Seitenwechsel); offline landet die Eingabe im Puffer (6.7, Z-12)
      loesung: beim Absenden wird abgeschickt gemerkt; der Fortschritt wird gelöscht, sobald S9 mit geändertem Stand der Einheit geladen wird (gespeichert bzw. zugestellt); mit altem Stand (gespeicherte Seite ohne Netz) fragt S9 weiter und nennt die wartende Rückmeldung
    - was: Neu laden während einer laufenden Phase – Zeit anhalten oder weiterlaufen lassen (Z-06 „Restzeit herstellen“)
      loesung: Zeitstempel laufen weiter (E-16); Fortsetzen rechnet ab dem letzten Merken nach, endete inzwischen eine Phase, kommt ein Hinweiston
    - was: aria-live auf der Phase-Karte hätte Screenreadern jede Viertelsekunde die Zeit angesagt
      loesung: eigene unsichtbare Ansage-Region (#gf-ansage), nur bei Wechsel von Schritt, Phase oder Satz
    - was: Die fixierte Aktionsleiste lag auf langen Seiten (Smartphone) hinter der unteren Navigation
      loesung: .gf-actions/.gf-save mit Abstand 61 px + Safe Area; in S3 besteht dasselbe Verhalten der Speichern-Leiste (vor AP-14) – dem Athleten gemeldet, nicht geändert
    - was: Playwrights Offline-Emulation erfasst Anfragen des Service Workers nicht (Speichern ging trotz „offline“ durch)
      loesung: Browser-Test schaltet ein kleiner Proxy vor der App ab (echter Netzausfall für Seite und Service Worker)
    - was: Playwright wartet bei pausierter Uhr beim Anklicken der (unter der Beschriftung liegenden) Skalenfelder vergeblich
      loesung: Test tippt auf die Beschriftung wie ein Nutzer und lässt die Uhr zum Ausfüllen wieder laufen
    - was: Mockup zeigt „≈ 32 min verbleibend“; die Dauer von Wiederholungsübungen ist unbekannt
      loesung: stattdessen die Zeit seit dem ersten Start (mm:ss) rechts über dem Fortschritt
    - was: "Review T5: Ein Tipp kurz nach einem automatischen Phasenwechsel wirkte auf die neue, noch nicht angezeigte Phase (Anhalten am Ende der letzten Arbeitsphase → nächste Übung, entgegen E-03; Pause beenden → Satz als erledigt gezählt)"
      loesung: tipp() zieht erst die Zeit nach; endete dabei eine Phase oder liegt der letzte automatische Wechsel unter 500 ms zurück, wird der Tipp verworfen und nur neu gezeichnet (Node- und Browser-Test, Gegenprobe ohne Korrektur schlägt fehl)
    - was: "Review T5: „Vorige Übung“ löschte die Erledigt-Markierung; „Zurück zur letzten Übung“ ließ eine übersprungene letzte Übung als übersprungen stehen (Status-Vorbelegung „teilweise“ trotz aller Übungen)"
      loesung: zurück und „Weiter“ öffnen eine erledigte Übung als erledigt (Primär „Weiter“); zurück zu einer übersprungenen Übung hebt die Markierung auf, eine nachgeholte Übung ist nicht mehr übersprungen; „Übung wiederholen“ setzt wie bisher zurück
    - was: "Review T5: Die gemessene Dauer lief im Abschluss weiter (Warten auf die Rückmeldung zählte mit, nach „Zurück“ neu vorbelegt)"
      loesung: Messung endet beim Erreichen des Abschlusses (ende_um im Zustand); Zurück ohne neues Training behält sie, erneutes Training misst neu; Uhr oben steht im Abschluss
    - was: "Review T5: Stumm – Blinken blieb als Klasse stehen (bei reduzierter Bewegung dauerhafte Umrandung) und fehlte bei Phasen bis 3 s (Pause 3 s bei 7/3); Wahl vor der ersten Eingabe ging beim Neuladen verloren; Name des Schalters wechselte mit aria-pressed"
      loesung: Blinken 3 s ab dem ersten Tick-Signal der Phase (t3, sonst t2/t1), endet nach 3 s und bei jedem Phasenwechsel; Stumm-Wahl zusätzlich unter training.gefuehrt.<id>.stumm (gelöscht, sobald die Einheit gespeichert ist); Name bleibt „Ton und Vibration aus“, gedrückt = stumm
    - was: "Review T5: Fokus ging nach „Fortsetzen“/„Neu starten“ und bei gesperrtem bzw. ausgeblendetem Knopf auf <body> verloren (6.8)"
      loesung: nach Fortsetzen/Neu starten Fokus auf die Überschrift des Schritts; wird der fokussierte Knopf gesperrt oder ausgeblendet, geht der Fokus auf die Primäraktion
    - was: "Review T5: Nach 422 wegen eines Ist-Werts öffnete S9 den Abschluss, das fehlerhafte Feld war nicht erreichbar"
      loesung: der Server markiert die Übung (data-invalid, nur deren Ist-Karte rot); S9 öffnet sie ohne Erledigt/Übersprungen zu ändern, „Weiter“ führt durch die erledigten Übungen zum Abschluss; Ist-Fehler ohne Zuordnung → Formular wie ohne JavaScript (alle Schritte)
    - was: "Review T5: Fortsetzen kurz nach einem Speichern ohne Takt (Stumm im Fortsetzen-Dialog) spielte alle verpassten Signale auf einmal"
      loesung: takt() wertet es als Lücke, wenn die laufende Phase schon vor dem letzten Takt endete (nur Hinweiston)
    - was: "Review T5: run.sh testete gegen einen schon laufenden Server auf dem Port, leerte ohne TEST_DB_HOST ggf. eine andere Datenbank (Socket); ein hängender Service Worker hätte die CI bis zu 6 h blockiert"
      loesung: freier Port (oder E2E_PORT), Abbruch mit Serverprotokoll, wenn der eigene Server nicht läuft; TEST_DB_HOST Standard 127.0.0.1 für App und Leeren; Warten auf den Service Worker mit 15 s Grenze, CI-Schritt timeout-minutes 10
    - was: "Review T5: Beim Zurück über mehrere Übungen bleibt „Übung wiederholen“ auf dem linken Knopf einer erledigten Übung (weiter zurück nur über Wiederholen)"
      loesung: bewusst so gelassen – eine eigene Navigation über mehrere Übungen wäre eine neue Bedienentscheidung; bei Bedarf mit dem Athleten klären
T6:
  status: umgesetzt          # Code-Stand 0.18.0; Abnahme auf dem Gerät (Flugmodus) durch den Athleten offen
  datum: 2026-09-28
  ergebnis: >
    S8: Bereich „Training“ nach „Konto“ mit Zeile „Timer-Signale“ (Segment An/Aus mit Icons volume/volume-off und
    Knopf „Speichern“, Formular ohne JavaScript, action=timer); SettingsRepository::TIMER_TON (Standard an), Prüfung
    an/aus, Audit setting_update, Schreibsperre wie bei den übrigen Einstellungen; S9 liest die Vorgabe (data-ton).
    Offline: WeekController::prefetch nimmt /einheit?id=…&modus=start für heutige und morgige geeignete Einheiten auf
    (Ablaufplan::geeignet); sw.js PRECACHE enthält /js/gefuehrt.js?v=VERSION; Seiten-Cache und Formular-Puffer
    unverändert (Schlüssel enthält modus=start). Prüfprotokoll: Einträge zu T1–T6 (AP-13, AP-14).
  tests: GuidedSessionTest::testTimerSettingInS8 (Standard, Speichern, ungültiger Wert, Wirkung in S9, Audit), ::testWeekPrefetchesGuidedPagesForTodayAndTomorrow; Browser-Durchlauf Z-09 (S8 aus → S9 stumm, Umschalten in S9 ändert S8 nicht) und S9 ohne Netz aus dem Cache
  abnahme: Einstellung wirkt als Standard in S9 (automatisiert und im Browser); S9 öffnet ohne Netz aus dem Cache (Browser, Netzausfall über Proxy); Speichern ohne Netz landet im Puffer und wird nachgesendet (Z-12, Browser)
  abnahme_offen: dieselben Punkte auf dem Smartphone im Flugmodus durch den Athleten
  probleme_loesungen:
    - was: 6.6 sieht einen Schalter ohne JavaScript vor; ein Radio-Segment speichert ohne Skript nicht von selbst
      loesung: kleines Formular in der Zeile mit Segment An/Aus und Knopf „Speichern“; auf schmalen Geräten steht die Bedienung unter dem Text
    - was: "Abschluss-Review: Eine vorgeladene S9 trägt timer_ton aus der Zeit des Vorladens; nach „Aus“ in S8 startete sie offline trotzdem mit Ton (Vorladen erneuert Seiten erst nach 10 min)"
      loesung: S8 (data-timer-ton, offline.js) und jede online geladene S9 merken den Wert in localStorage training.timer_ton; eine offline gezeigte S9 (data-offline-stand) nimmt diesen Wert. Browser-Test: S9 vorgeladen mit „an“, S8 auf „aus“, ohne Netz startet S9 stumm (Gegenprobe ohne Korrektur schlägt fehl)
    - was: "Abschluss-Review: Untertext in 6.6 wich von Mockup und Umsetzung ab"
      loesung: 6.6 an das Mockup S8 angeglichen (Umsetzung folgte dem Mockup)
T7:
  status: umgesetzt          # Code-Stand 0.18.0 (Dokumentation zu T1–T6; T8 mit 0.19.0)
  datum: 2026-09-28
  ergebnis: >
    Version je AP (AP-13 = 0.17.0, AP-14 = 0.18.0, Nachtrag T8 = 0.19.0); CHANGELOG mit allen drei Teilen und T8;
    README (Struktur mit js/ und sw.js, Endpunkte Stand AP-14, Icons, Tests mit Node und Browser-Durchlauf, Kalender
    mit Sammeltermin); Hauptkonzept (AP-11, AP-13, AP-14 mit Status und probleme_loesungen, D-60, Abschnitte 3, 7, 8.2,
    10, Änderungsprotokoll); datenmodell.md (Schema 22, coach_summary, app_setting-Schlüssel); branding.md (B-03, B-09,
    Abschnitt 8); dieses Dokument (Abschnitt 12 je Unterpunkt, E-22, T8, 8.4); Prüfprotokoll (AP-11, AP-13, AP-14).
    Jeder Unterpunkt und jede Review-Runde als eigener Commit; unabhängige Reviews zu T1–T5 und T8 mit
    Gegenprüfung jedes Befunds, bestätigte Befunde behoben.
  tests: PHPUnit 195, node --test 17, Browser-Durchlauf 14 Prüfungen – alle grün; Konsistenzprüfung der Dokumente
  abnahme: Dokumente konsistent (Feldnamen, Tool-Namen, Screens, Versionen); Changelog nennt alle drei Teile und T8
  probleme_loesungen:
    - was: Sporadischer Testfehler MorningCheckinTest::testDaylightSavingSwitch (AP-12 als nicht reproduzierbar vermerkt)
      loesung: Ursache gefunden (Aufräumen der MCP-Sitzungsdateien verglich echte Dateizeiten mit der verstellten Test-Uhr), behoben in 0.18.0
    - was: In S3 liegt die fixierte Speichern-Leiste auf dem Smartphone beim Scrollen hinter der unteren Navigation (seit vor AP-14)
      loesung: in S9 behoben (Abstand 61 px + Safe Area); S3 nicht geändert, dem Athleten gemeldet
T8:
  status: umgesetzt          # Code-Stand 0.19.0; Abnahme im Nextcloud-Kalender durch den Athleten offen
  datum: 2026-09-28
  ergebnis: >
    Ein Sammeltermin je Tag (E-22, D-60): Training\Calendar\DayEvent ersetzt SessionEvent – Ressource
    training-tag-<Datum>.ics (nach einem Löschen des Tagestermins -1, -2 …), UID je Tag und Fassung, SUMMARY „Typ: Titel“ bzw. „Training: Titel 1 + Titel 2“ (Planreihenfolge,
    ohne Ruhetage), STATUS immer CONFIRMED, URL zur Woche, CATEGORIES mit den Typen des Tages, LAST-MODIFIED/SEQUENCE
    aus der jüngsten Änderung der Einheiten; Beschreibung je Einheit mit Überschrift (bei mehreren), Kurzsatz, Kurzplan
    (Priorität, Dauer, Status, Übungen/Blöcke mit Namen), Begründung (≤ 1 000 Zeichen) und Link, Trennlinie zwischen
    den Einheiten; VALARM einmal je Tag, solange eine Einheit geplant oder verschoben ist.
    CalendarSync::pushDays(Daten) schreibt die Tage aus der Datenbank neu (Tag ohne Einheiten → Termin gelöscht);
    syncRange überträgt je Tag und löscht verwaiste Sammeltermine sowie alte Einzeltermine training-session-<id>.ics.
    Aufrufer: write_week_plan (Tage der neuen und ersetzten Einheiten), update_session (alter und neuer Tag),
    Rückmeldung auf der Webseite (Tag der Einheit). S8-Texte zur Erinnerung („Am Trainingstag um …“).
  tests: DayEventTest (K-01–K-03, K-07 – eine Einheit, mehrere Einheiten, Erinnerung auch bei zwei offenen Einheiten, Begründung je Einheit, Kurzsatz/Kürzung, Ressourcennamen mit Fassung, Ruhetage) und CalendarTest (K-04–K-07 – zwei Einheiten an einem Tag, Verschieben, letzter Termin eines Tages, ausgelassen, Woche ersetzen mit behaltener Einheit, Abgleich mit alten Einzelterminen und Zählung in Tagen, Ruhetage im Abgleich, Reihenfolge nach sort_order, Erinnerung; nach dem Review: nie wiederverwendete Adresse/UID mit nachgebildetem Nextcloud-Papierkorb, Einzeltermine geänderter Einheiten auch außerhalb des Zeitraums, Abbruch nach dem ersten Fehler mit Audit je Tag); Rauchtest gegen Radicale
  abnahme: automatisiert (PHPUnit 195 Tests grün) und Rauchtest gegen einen echten CalDAV-Server (Radicale, lokal) – Anlegen, Ersetzen, Zeitraum-Abfrage, Löschen des alten Einzeltermins, fremder Termin bleibt, iCalendar mit Erinnerung angenommen
  abnahme_offen: Nextcloud-Kalender nach Deploy und Abgleich (Web und Handy) durch den Athleten
  probleme_loesungen:
    - was: Bestehende Einzeltermine je Einheit im Kalender des Athleten
      loesung: der Abgleich erkennt training-session-<id>.ics weiter als eigene Termine und löscht sie im Zeitraum (7 Tage zurück bis 8 Wochen voraus); bis zum nächsten stündlichen Abgleich (oder Knopf in S8) kann ein Tag doppelt erscheinen; ältere Einzeltermine unberührter Einheiten bleiben als Verlauf
    - was: Nach Verschieben oder Ersetzen muss auch der alte Tag neu gebildet werden
      loesung: Termine werden nicht je Einheit, sondern je Tag aus der Datenbank gebildet; die Tools übergeben alle betroffenen Tage (alt und neu), ein leerer Tag verliert seinen Termin
    - was: Audit-Eintrag calendar_error bezog sich auf eine Einheit
      loesung: Bezug jetzt auf den Tag (entity kalender_tag, Datum; beim Abgleich ohne Bezug)
    - was: Kurzplan der Kletterblöcke zeigte den internen Schlüssel (bouldern_volumen)
      loesung: Anzeige mit dem Namen wie in S3 („Bouldern Volumen“), da die Beschreibung ohnehin neu aufgebaut wurde
    - was: "Review T8: Nextcloud bis 34.0.1 hält gelöschte Termine 30 Tage im Papierkorb; wird derselbe Tagestermin (Adresse und UID) mehrfach gelöscht und neu angelegt, antwortet es mit 403 – der Tag behält einen veralteten Termin und der stündliche Abgleich scheitert"
      loesung: Fassung je Tag in app_setting (kalender_tag_<Datum>), erhöht nach jedem tatsächlichen Löschen; Name und UID tragen die Fassung (training-tag-<Datum>-1.ics …), sodass keine gelöschte Adresse oder UID wiederkehrt; Einträge verfallen 60 Tage vor dem Abgleichzeitraum; Hinweis bei 403 nennt den Papierkorb. Getestet mit nachgebildetem Papierkorb (dreimal leeren und neu belegen)
    - was: "Review T8: Alte Einzeltermine blieben dauerhaft stehen, wenn die App eine Einheit außerhalb des Abgleichzeitraums änderte (späte Rückmeldung, Verschieben, Ersetzen einer alten Woche)"
      loesung: bei jeder direkten Änderung löscht die App auch den Einzeltermin der betroffenen Einheiten (ein DELETE, 404 ist kein Fehler)
    - was: "Review T8: Doku – Datenfluss 3.2 („Termine je Einheit“), „Kurzsatz als erste Zeile“ (D-56, E-11, 5.3, Prüfprotokoll) und „mit Überschrift“ im Changelog passten nicht mehr; Testfälle K-01–K-07 nur DayEventTest zugeschrieben"
      loesung: nachgezogen (Überschrift nur bei mehreren Einheiten, Kurzsatz dann je Abschnitt nach der Überschrift); Zuordnung der Testfälle zu DayEventTest und CalendarTest; fehlende Tests ergänzt
T9:
  status: umgesetzt          # Code-Stand 0.20.0; Abnahme auf dem Gerät durch den Athleten offen
  datum: 2026-09-28
  ergebnis: >
    Entscheidungen zu O-05 bis O-07 als E-23 festgehalten (O-05 nicht umsetzen, O-06 wie umgesetzt, O-07 satzweise).
    Ablaufplan: Kletterblock ohne hang_s und duration_min mit sets ≥ 2 → wiederholungen (Sätze wie geplant, pause_s =
    rest_s), ein Satz ohne Zeiten bleibt offen. S9 zeigt „n Sätze“ mit dem Ziel darunter und – mit Pause – den
    Pausentimer nach „Satz erledigt“; das Skript nutzt die vorhandene Wiederholungslogik. CSS: .actions-sticky auf dem
    Smartphone über der unteren Navigation (vorher nur .gf-actions/.gf-save in S9), damit Speichern in S3, Check-in und
    Schmerz beim Scrollen sichtbar bleibt. E-17, 6.3, 6.4 und Testfälle A-13/A-14 angepasst.
  tests: AblaufplanTest (A-13, A-14), GuidedSessionTest::testClimbingStepsHangboardBlockAndOpen (Zugkraft mit Pause, Antagonisten ohne Pause); Messung der Leisten gegenüber der Navigation auf 375 px (S3, Check-in, Schmerz, S9) und Screenshots (S3 gescrollt, S9 Zugkraft bereit und Pause)
  abnahme: automatisiert und im Browser (Leisten enden über der Navigation, Zugkraft 4 Sätze mit 2-min-Pause, kein Überlauf auf 375 px)
  abnahme_offen: Gerätetest des Athleten (Kletterblock mit Sätzen im Training, Speichern-Leisten beim Scrollen)
  probleme_loesungen:
    - was: S9 zeigte bei Wiederholungen „<reps> Wdh.“ – Kletterblöcke haben kein reps
      loesung: für Kletterblöcke „n Sätze“ groß und das Ziel (target) klein darunter; der Pausentimer steht für beide Arten gemeinsam
    - was: Die Speichern-Leiste lag nicht nur in S3, sondern auch im Check-in und bei „Schmerz“ hinter der Navigation (gemeinsame Regel .actions-sticky)
      loesung: Rückfrage beim Athleten – Korrektur für alle Seiten in training.css (Design-System app.css unverändert)
    - was: "Review T9: Beim letzten Satz stand „Pause 120 s nach „Satz erledigt““, danach kam keine Pause (bei Kraft schon vorher, jetzt auch bei Kletterblöcken)"
      loesung: Pausenhinweis in „bereit“ nur, wenn noch ein Satz folgt (auch bei Halten); Node-Test für den letzten Satz
    - was: "Review T9: Auf iPhones mit Home-Leiste hatte die Leiste unten zusätzlich den Safe-Area-Abstand (46 statt 12 px über der Navigation)"
      loesung: Innenabstand unten auf den normalen Wert gesetzt, die Navigation hält die Safe Area frei; gemessen mit simulierter Safe Area (34 px)
    - was: "Review T9: In der Woche überdeckte die Leiste des eingebetteten Check-ins die Kacheln darunter um 16 px (negativer Rand der Leiste, vor T9 schon vorhanden)"
      loesung: Abstand unter dem eingebetteten Check-in (Klasse week-checkin, nur Smartphone); gemessen ohne Überdeckung
    - was: "Review T9: S3 – die Leiste gehört zum Rückmeldungs-Abschnitt und erscheint erst, wenn dieser ins Bild scrollt"
      loesung: so gelassen (Aufbau von S3 unverändert; beim Scrollen durch die Ist-Werte ist Speichern wie vorher nicht fixiert)
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
