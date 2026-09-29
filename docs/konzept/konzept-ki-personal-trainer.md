---
titel: Konzept KI-Personal-Trainer – Trainingsplanung, Feedback und Garmin-Anbindung
projekt: Personal Training & Trainingsdokumentation
dokumentstand: 2026-09-29
status: bestaetigt
bestaetigt_am: 2026-09-27
repo: chodid/training (privat, keine Lizenz; seit 2026-09-29 vorübergehend öffentlich für die Recherche, wird wieder privat – Q-21)
subdomain: training.gen-em.org
hosting: Lima-City (Webspace, Apache 2.4, PHP 8.4)
sprache: de
hinweis_version: Keine Versionsnummer im Konzept; Versionierung erfolgt in der Umsetzung.
---

# 0. Zweck und Arbeitsweise mit diesem Dokument

- Dieses Dokument definiert Aufgabe, Architektur, Entscheidungen und Arbeitspakete (AP) für das System „KI-Personal-Trainer".
- Die **Codearbeit** findet in einer separaten Chat-Instanz statt. Diese Instanz arbeitet die AP in der angegebenen Reihenfolge ab und aktualisiert nach jedem AP:
  1. den Statusblock des AP in diesem Dokument (`status`, `probleme_loesungen`),
  2. das separate Prüfprotokoll `docs/pruefung/pruefprotokoll.md` (Struktur in Abschnitt 16),
  3. Changelog und Dokumentation des Repos.
- **Trainingsfachliche Arbeit** (Wissensbasis, Trainerregeln, Athletenprofil, Blockpläne) findet im claude.ai-Projekt „Personal Training & Trainingsdokumentation" statt, nicht in der Code-Instanz.
- **Rollenverteilung (Modelle):** Konzepterstellung (neue Konzepte, Entscheidungsvorlagen, neue AP, grundlegender Zuschnitt) und Design-Mockups (AP-01a, D-37) übernimmt das Modell **Fable**; Codearbeit, Tests, Deployment und Pull Requests übernimmt die **Code-Instanz**. Die Code-Instanz darf bestehende Konzepte **in Rücksprache mit dem Athleten** ändern und ergänzen (z. B. bestätigte Entscheidungen eintragen, Befunde aus der Umsetzung nachziehen) und dokumentiert Befunde in `probleme_loesungen`.
- Regel für Entscheidungen: Alles, was in Abschnitt 4 als `entschieden` steht, ist nicht mehr zu diskutieren. Alles unter Abschnitt 5 (`offen`) ist vor Umsetzung des betroffenen AP zu klären.

# 1. Aufgabenstellung

## 1.1 Ziel

Ein System, mit dem Claude (im Projekt-Chat) als Personal Trainer fungiert:

1. Trainingspläne für die kombinierten Ziele erstellt und wöchentlich anpasst,
2. Entscheidungen auf hinterlegte Literatur stützt (nachprüfbar, zitiert),
3. Rückmeldungen und Messdaten des Athleten strukturiert erhält (Ausführung, Belastung, Schmerz, Erholung, Uhrdaten),
4. Pläne so ausgibt, dass Ausdauereinheiten auf der Garmin-Uhr und alle anderen Einheiten auf dem Handy nutzbar sind.

## 1.2 Trainingsbereiche

| id | bereich | beschreibung |
|---|---|---|
| T1 | Ausdauer | Lauftraining für Trailrunning und Skitouren (bergauf-orientierte Ausdauer) |
| T2 | Kraft/Haltung | Haltungstraining für den Alltag, allgemeine Kräftigung |
| T3 | Klettern | Gezieltes Training für Bouldern und Klettern (Fingerkraft, Zugkraft, Technik-Volumen) |
| T4 | Beweglichkeit/Mobilität | Dehnen (statisch, dynamisch, PNF), Mobility (Kräftigung in Endposition), Faszienrolle, Yoga als Übungsfundus; Schwerpunkt Hüfte in allen Bewegungsrichtungen, dazu Beweglichkeit rund ums Knie; Ganzkörper als Reserve (D-79) |

Alle vier Bereiche werden in einem gemeinsamen Wochenplan geführt; Priorisierung erfolgt blockweise (Abschnitt 14).

## 1.3 Nutzer

Ein einzelner Athlet (= Betreiber des Systems). Kein Mehrbenutzerbetrieb.

## 1.4 Rahmenbedingungen

- Bestehendes Webhosting bei Lima-City (Apache 2.4, PHP, MySQL, FTPS, zeitgesteuerter URL-Aufruf als Cronjob); kein Plesk; kein dauerhaft laufender Node/Python-Prozess vorausgesetzt.
- Garmin-Uhr vorhanden.
- GitHub für Code und Dokumente; Deployment per Push aus GitHub auf den Server (D-17).
- claude.ai-Projekt mit Projekt-Wissen (Dateien), Projekt-Memory und Custom Connectors (MCP).

# 2. Nicht-Ziele und Ausschlüsse

| id | ausschluss | begruendung |
|---|---|---|
| N1 | Strava-API | API-Bedingungen seit 11/2024 untersagen Nutzung der Daten in KI-Anwendungen; Änderungen 06/2026: Gebühren, keine Intermediär-Plattformen mehr. |
| N2 | Garmin Connect Developer Program | Nur für geschäftliche Nutzung; neue Anträge derzeit pausiert. |
| N3 | Inoffizielle Garmin-Bibliotheken | Verstoß gegen Nutzungsbedingungen, brechen regelmäßig; nicht als Fundament. |
| N4 | KI-Logik in der Webseite | Keine LLM-Aufrufe aus dem Server; alle „Intelligenz" liegt im Projekt-Chat. Server = Daten + Schnittstelle. |
| N5 | Medizinische Diagnostik | System steuert Belastung; Diagnostik und Therapie bleiben bei Fachpersonen (Schwellenregeln in Abschnitt 14). |
| N6 | Hintergrund-Monitoring durch Claude | Claude arbeitet ausschließlich reaktiv im Chat; keine Push-Benachrichtigungen, keine automatische Anpassung ohne Chat. |
| N7 | Rohdaten im Chat | Keine FIT-Files, keine Sekunden-Streams; nur Aggregate über MCP (Budget in Abschnitt 8.3). |
| N8 | Mehrbenutzerbetrieb | Bewusst ausgeschlossen; vereinfacht Auth und Datenmodell. |

# 3. Systemarchitektur

## 3.1 Komponenten

| id | komponente | ort | aufgabe |
|---|---|---|---|
| K1 | Garmin-Uhr + Garmin Connect | Athlet | Aufzeichnung aller Aktivitäten; Anzeige geplanter Ausdauer-Workouts; Schlaf/HRV/Ruhepuls |
| K2 | Intervals.icu | Drittanbieter | Hub für Ausdauer: empfängt Aktivitäten und Wellness von Garmin; hält geplante Ausdauereinheiten (Kalender); überträgt geplante Workouts an Garmin Connect (Vorschau eine Woche) |
| K3 | PHP-Server (Lima-City) | Eigenes Hosting | Webseite (mobil und Tablet), MySQL-Datenbank, Intervals.icu-Client, MCP-Endpunkt, OAuth-Server |
| K4 | claude.ai-Projekt | Anthropic | Trainer-Instanz: Planung, Anpassung, Begründung; Zugriff auf K3 über Custom Connector (MCP) |
| K5 | Projekt-Wissen (Dateien im Projekt) | Anthropic | Wissenskarten (Literatur), Trainerregeln, Blockpläne (Athletenprofil seit D-48 in K3) |
| K6 | Projekt-Memory | Anthropic | Nur stabile, nicht gesundheitsbezogene Fakten (Ziele, Ausrüstung, Präferenzen) |
| K7 | GitHub-Repo | GitHub | Code (server/), Dokumente (docs/), Wissenskarten (docs/wissen/), Regeln (docs/regeln/) |
| K8 | Nextcloud-Kalender (CalDAV) | Eigenes Hosting des Athleten | Kopie der Einheiten als ganztägiger Sammeltermin je Tag (AP-11, D-50, D-60); wird nur von K3 beschrieben |

## 3.2 Datenflüsse

```mermaid
flowchart LR
  Uhr[K1 Garmin-Uhr / Connect]
  ICU[K2 Intervals.icu]
  SRV[K3 PHP-Server\nWebseite + MySQL + MCP + OAuth]
  CHAT[K4 claude.ai Projekt-Chat]
  WISSEN[K5 Projekt-Wissen\nWissenskarten, Regeln, Blockpläne]
  CAL[K8 Nextcloud-Kalender]
  REPO[K7 GitHub-Repo]
  HANDY[Handy-Browser]

  Uhr -- Aktivitäten, Wellness --> ICU
  ICU -- geplante Ausdauer-Workouts --> Uhr
  SRV <-- API-Key --> ICU
  HANDY -- Login, Wochenansicht, Feedback, Check-in --> SRV
  CHAT <-- MCP über OAuth 2.1 --> SRV
  WISSEN --> CHAT
  REPO -- Deploy --> SRV
  REPO -- Sync der docs --> WISSEN
  SRV -- CalDAV: Sammeltermin je Tag --> CAL
```

## 3.3 Was wo gespeichert wird (System of Record)

| daten | system_of_record | bemerkung |
|---|---|---|
| Geplante Ausdauereinheiten | K2 Intervals.icu (Event) | K3 hält nur `intervals_event_id` als Referenz |
| Ausgeführte Ausdauer-Aktivitäten, HF, Pace, Höhe, Load | K2 | K3 spiegelt Zusammenfassungen in `ext_activity` (D-43, read-through und stündlicher Abgleich) |
| Objektive Wellness (HRV, Ruhepuls, Schlaf) | K2 (aus Garmin) | K3 spiegelt in `ext_wellness` (D-43) |
| Geplante Nicht-Ausdauer-Einheiten (Kraft, Klettern, Haltung) | K3 MySQL | Strukturierte Inhalte (Übungen, Sätze, Kante, Last) |
| Ausführungslog aller Einheiten (Ist-Werte) | K3 MySQL | Auch für Ausdauereinheiten (Feedback) |
| Feedback (RPE, Feel, Schmerz, Abweichung, Notiz) | K3 MySQL | Ein Eingabeort für alles |
| Tägliches Check-in | K3 MySQL | D-16 |
| Kalendertermine der Einheiten | K3 MySQL (Einheit) | K8 hält nur eine Kopie; Änderungen im Kalender werden nicht zurückgelesen und beim nächsten Abgleich überschrieben (D-50, D-60) |
| Backups (verschlüsselte DB-Dumps) | Manuell heruntergeladen bzw. per E-Mail beim Athleten; Pre-Migration-Dumps lokal außerhalb Docroot | D-18 |
| Branding-Dokument | K7 Repo (docs/branding/) | D-19 |
| Blockplan, Begründungen, Trainerregeln, Wissenskarten | K7 Repo + K5 Projekt-Wissen | Textdokumente |
| Zielklärung, Revisionen und Blockbilanz je Block (Ziele, Entscheidungen mit verworfenen Alternativen, Bewertung, Kennzahlen) | K3 MySQL (`block_review`, D-72; Zugriff über `get_handover`, `get_block_reviews`, `write_block_review`) | `docs/plaene/` und K5 nur Spiegel; `training_block.doc_ref` optional (AP-15) |
| Athletenprofil (Ziele, Zeitbudget, Ausrüstung, Einschränkungen, Leistungswerte) | K3 MySQL (`athlete_profile`, D-48; Zugriff über `get_athlete_profile`/`update_athlete_profile`) | Nicht in K6 Memory; nicht mehr als Datei in K7/K5 (vorher D-15) |
| OAuth-Clients, Auth-Codes, Refresh-Tokens (gehasht), Web-Sessions, Audit-Log der MCP-Schreibzugriffe | K3 MySQL | Access-Tokens sind signierte JWT und werden nicht gespeichert (D-32); Web-Session in `web_session` (D-33) |
| MCP-Sitzungsdateien des SDK (nur für Clients älterer Protokollrevisionen) | K3 Dateisystem `<Ordner>/var/` | Außerhalb Docroot, vom Deployment nicht berührt (D-17) |

# 4. Entscheidungen (entschieden)

| id | entscheidung | begruendung | datum |
|---|---|---|---|
| D-01 | Zielarchitektur ist Ausbaustufe 3: eigene Web-App mit Datenbank plus MCP-Server, Intervals.icu als Hub für Ausdauer und Garmin. | Intervals.icu modelliert Kraft/Klettern nur als Textnotiz; strukturierte Nicht-Ausdauer-Einheiten und einheitliches Feedback brauchen eigene DB. | 2026-09-27 |
| D-02 | Intervals.icu ist die einzige Verbindung zu Garmin (Aktivitäten, Wellness, Workout-Push). | Strava und Garmin-API ausgeschlossen (N1–N3); Intervals.icu bietet API mit persönlichem Key und direkten Garmin-Sync. | 2026-09-27 |
| D-03 | Server in PHP auf bestehendem Webhosting (Lima-City); MCP über Streamable HTTP im stateless-Modus (Protokollrevision 2026-07-28), kein Dauerprozess. | Aktuelle MCP-Revision ist sitzungslos, jeder POST ein abgeschlossener JSON-RPC-Austausch; passt zu PHP/Apache. | 2026-09-27 |
| D-04 | MCP-SDK: `logiscape/mcp-sdk-php` (Erstwahl); offizielles PHP-SDK als Alternative, falls logiscape Blocker zeigt. Präzisierung (Befund 2026-09-27, geprüft an v2.0.1): Das SDK liefert nur die **Resource-Server-Seite** von OAuth 2.1 – Bearer-Prüfung über `TokenValidatorInterface` (mitgeliefert `JwtTokenValidator` für HS256/RS256 mit iss/aud/exp-Prüfung, eigene Validatoren möglich), Auslieferung von `/.well-known/oauth-protected-resource`, 401 mit `WWW-Authenticate: Bearer resource_metadata=...`. Es enthält **keinen Autorisierungsserver** (Metadaten nach RFC 8414, Dynamic Client Registration, Authorize, Token). Der Autorisierungsserver wird in AP-01 selbst gebaut (D-32, D-36). | logiscape ist explizit für PHP/Apache/Shared-Hosting gebaut, hohe Konformanz; die Resource-Server-Bausteine (Validator, Metadaten, 401) werden genutzt, die Autorisierungsserver-Seite ist für einen Einzelnutzer überschaubar (vier Endpunkte, vier Tabellen). Offizielles SDK ist noch experimentell. | 2026-09-27 |
| D-05 | Authentifizierung claude.ai ↔ MCP: OAuth 2.1 (Single-User-Implementierung: Metadaten, Dynamic Client Registration, Authorize mit Webseiten-Login, Token mit PKCE, Bearer-Prüfung). Konkretisierung: Token-Format D-32, Login D-33, Client-Registrierung und Freigabe D-36. | claude.ai-Custom-Connectors unterstützen ausschließlich OAuth, keine eigenen Header. OAuth ist zudem für Gesundheitsdaten und Schreibzugriff das angemessene Verfahren. | 2026-09-27 |
| D-06 | Fallback bis OAuth-Flow stabil: Claude Desktop (oder Claude Code) mit statischem Bearer-Token-Header gegen denselben MCP-Endpunkt. Konkretisierung: Das statische Token steht in `.env` als `MCP_STATIC_TOKEN` und wird nur akzeptiert, wenn zusätzlich `MCP_STATIC_TOKEN_ENABLED=true` gesetzt ist (Standard: nicht gesetzt = aus). Die Prüfung erfolgt im selben Validator-Pfad vor der JWT-Prüfung (D-32); ein akzeptiertes statisches Token gilt mit vollem Scope. | Bekannte Flakiness des claude.ai-OAuth-Handshakes; Desktop/Code erlauben Header. Fallback ist nicht mobil. Eigenes Aktivierungsflag verhindert, dass ein vergessenes Token dauerhaft eine zweite Tür offenhält. | 2026-09-27 |
| D-07 | Ausdauereinheiten werden in den Intervals.icu-Kalender geschrieben und erscheinen auf der Uhr; alle anderen Einheiten leben auf der Webseite. | Nutzeranforderung; Garmin-Push für strukturierte Kraft-Workouts ist unklar und nicht nötig. | 2026-09-27 |
| D-08 | Die Webseite zeigt eine Wochenansicht für alle Einheiten inkl. Ausdauer (serverseitig aus Intervals.icu geladen) und nimmt Feedback für alle Einheiten auf. | Ein Eingabeort; vermeidet doppelte Erfassung. | 2026-09-27 |
| D-09 | Phase 1: Live-Proxy auf Intervals.icu (kein Cron-Spiegel). Cron-Spiegel nach MySQL optional in AP-09 (entschieden: D-43). | Vermeidet Sync-Logik; Rate-Limit (10 req/s) ist unkritisch. Spiegel nur bei Backup-/Performance-Bedarf. | 2026-09-27 |
| D-10 | Der Trainingsplan ist Daten, kein Code. Trainerregeln und Literatur sind Dokumente im Projekt-Wissen. GitHub/Claude Code dienen nur dem Bau des Werkzeugs. | Regeln bleiben lesbar, versionierbar, im Chat hinterfragbar; keine Logik-Duplikation im Server. | 2026-09-27 |
| D-11 | Claude schreibt Pläne erst nach expliziter Bestätigung im Chat in DB und Intervals.icu. Jeder Schreibzugriff wird im Audit-Log protokolliert. | Nutzerpräferenz (Bestätigung vor Umsetzung); Nachvollziehbarkeit. | 2026-09-27 |
| D-12 | Literatur wird als strukturierte Wissenskarten (Markdown mit Quellenangabe) hinterlegt, nicht als vollständige Bücher. PubMed-Connector für Primärstudien. | Größe, Urheberrecht, Zitierfähigkeit. | 2026-09-27 |
| D-13 | Zitierregel: Jede trainingsfachliche Aussage von Claude wird entweder mit Wissenskarte/DOI belegt oder ausdrücklich als „Einschätzung ohne Quelle" markiert. | Verhindert erfundene Referenzen. | 2026-09-27 |
| D-14 | Gesundheitsbezogene Daten (Schmerz, Verletzungen, Einschränkungen) werden ausschließlich in K3 (MySQL) und K7/K5 (Profil-Dokument) gehalten, nie im Projekt-Memory (K6). | Datensparsamkeit; Memory hält nur stabile, nicht sensible Fakten. | 2026-09-27 |
| D-15 | ~~Athletenprofil ist ein Dokument (docs/athlet/profil.md), kein DB-Objekt (Phase 1).~~ **Ersetzt durch D-48 (2026-09-28).** | Geringer Aufwand, im Chat direkt lesbar; DB-Abbildung bei Bedarf in AP-09. | 2026-09-27 |
| D-16 | Tägliches Check-in auf der Webseite mit genau drei Feldern: `recovery_1_5`, `soreness_1_5`, `pain_flag` (bei ja → Schmerzereignis). Keine separate Schlafqualität. Fehlende Einträge gelten als fehlend; MCP meldet Abdeckungsquote. | Subjektive Marker sind sensitiver als objektive (L-P11, verifiziert V-06); Schmerz hat keinen objektiven Ersatz; minimaler Umfang sichert Compliance. Bestätigt durch Athlet. | 2026-09-27 |
| D-17 | Deployment: GitHub-Repo ist Quelle; ein GitHub-Actions-Workflow baut (`composer install --no-dev`) und überträgt `server/` per FTPS (explizit, Port 21) in den Subdomain-Ordner auf dem Webspace. Kein Klartext-FTP. Layout auf dem Server: Subdomain-Ordner = Inhalt von `server/` (`src/`, `vendor/`, `migrations/`, …), Document Root = `<Ordner>/public`, `.env` direkt in `<Ordner>/`, Backups in `<Ordner>/backups/`, Laufzeitdaten (z. B. MCP-Sitzungsdateien des SDK für Clients älterer Protokollrevisionen) in `<Ordner>/var/`; nur `public/` ist per HTTP erreichbar. `.env`, `backups/` und `var/` werden vom Deployment nie überschrieben oder gelöscht. Migrationen werden nach dem Upload über einen geschützten Endpunkt vom Workflow ausgelöst (Secret in GitHub Actions). Optional zweiter Workflow für eine Staging-Subdomain. | Kein SSH/Composer auf dem Server vorausgesetzt; reproduzierbarer Build; Nutzeranforderung „GitHub + FTP-Push". | 2026-09-27 |
| D-18 | Backup betrifft nur die Datenbank (Code und Dokumente liegen in GitHub). Mechanik: SQL-Dump per PHP (Schema + Daten, portabel) → gzip → Verschlüsselung mit Passwort. Format OpenSSL-kompatibel (AES-256-CBC, PBKDF2 mit dokumentierter Iterationszahl, `Salted__`-Header), damit die Datei ohne eigenes Werkzeug per `openssl enc -d` entschlüsselbar ist. Auslöser: (a) manuell als Download auf der Webseite (nur eingeloggt); (b) zeitgesteuert per E-Mail (Lima-City-Cronjob ruft einen geschützten Endpunkt auf, Intervall konfigurierbar, Standard wöchentlich); (c) automatisch vor jeder Migration (in `backups/` außerhalb Docroot, Rotation der letzten 5). Backup-Passwort liegt in `.env`. Restore-Anleitung im README; Restore-Test Pflicht in AP-10. | Nutzeranforderung; DB ist klein (KB bis wenige MB), E-Mail-Anhang unkritisch; Standardformat sichert Wiederherstellbarkeit auf jedem Rechner. Tradeoff: symmetrisches Passwort auf dem Server bedeutet, dass ein Serverkompromiss auch das Backup-Passwort preisgibt – da der Server die DB ohnehin hält, entsteht kein zusätzlicher Verlust. Asymmetrische Variante (Public Key auf dem Server) optional in AP-09 – gestrichen (D-47). CBC ohne Authentifizierung: Integrität wird über die gzip-Prüfsumme nach dem Entschlüsseln erkannt. | 2026-09-27 |
| D-19 | Das Branding-Dokument wird im Repo unter `docs/branding/` abgelegt und gilt für **alle** Webseiten-Screens ab AP-01 (S0 Setup, S1 Login, S7 Freigabe) über AP-04 (S2–S5) bis AP-09 (S6). Es enthält die Gestaltungsvorgaben des Athleten und die Mockups aus AP-01a (D-37); alle Screens sind **mobil- und tabletfreundlich** (responsive, Touch-Bedienung, Hoch- und Querformat) und haben zusätzlich eine **Desktop-Ansicht** (Seitenleiste ab 1024 px; Ergänzung 2026-09-27, branding.md B-02). Die Code-Instanz liest es vor Beginn von AP-01. | Gestaltung ist Umsetzungsdetail, keine Konzeptentscheidung; da bereits AP-01 sichtbare Seiten baut, muss die Vorgabe vorher vorliegen. | 2026-09-27 |
| D-20 | Update-Mechanik: Schemaänderungen ausschließlich als nummerierte Migrationsdateien in `server/migrations/` (SQL oder PHP), Tabelle `schema_version` hält den Stand. Der Code trägt eine `APP_SCHEMA_VERSION`; bei jedem Request prüft die App, ob Code- und DB-Stand übereinstimmen – bei Abweichung wird eine „Update erforderlich"-Seite angezeigt und jeder Schreibzugriff (Web und MCP) blockiert, bis migriert ist. Migration wird ausgelöst (a) vom Deploy-Workflow über den geschützten Endpunkt (D-17) oder (b) manuell über eine Schaltfläche nach Login. Ablauf jeder Migration: Wartungsflag setzen → Pre-Migration-Dump (D-18 c) → Migrationen der Reihe nach, `schema_version` nach jeder einzelnen Migration fortschreiben → Wartungsflag lösen. MySQL beendet Transaktionen bei DDL (`CREATE`/`ALTER`) implizit; daher: ein fachlicher Schritt pro Migrationsdatei, Datenänderungen in Transaktionen, Schemaänderungen ohne; bricht eine Migration ab, bleibt `schema_version` auf der letzten erfolgreichen. Kein automatisches Rollback: Rückweg = vorheriger Git-Tag deployen + Pre-Migration-Dump einspielen. | Verhindert Code/Schema-Mismatch nach einem FTP-Upload, dessen Migrationsaufruf fehlschlug; Dump vor Migration ist der einzige zuverlässige Rückweg. Down-Migrationen sind Aufwand ohne Nutzen für ein Einzelnutzer-System. | 2026-09-27 |
| D-21 | Englischsprachige Literatur ist der deutschsprachigen gleichgestellt; Auswahl nach Eignung, nicht nach Sprache. | Die maßgeblichen Konsenspapiere und Praxisbücher (Klettern, Bergausdauer) sind englischsprachig. | 2026-09-27 |
| D-22 | Evidenzhierarchie für Regelquellen: Consensus Statements / Position Stands / systematische Reviews > wissenschaftliche Lehrbücher > Praxisliteratur. Lehrbücher liefern Grundlagen und Begriffe; Regeln in `docs/regeln/` stützen sich vorrangig auf Paper. | Klassische Standardwerke mischen empirische Befunde mit tradierten Modellen (Superkompensation, klassische Periodisierung); Paper sind per DOI/PMID prüfbar, oft Open Access und kurz (Tokenbudget 13.1). | 2026-09-27 |
| D-23 | Das GitHub-Repo ist privat. | Wissenskarten sind eigene Zusammenfassungen mit Seitenangaben. PDFs dürfen seit D-31 (Fassung 2026-09-27, Nachtrag) im Repo liegen. Stand 2026-09-29: vorübergehend öffentlich, wird wieder privat (Q-21). | 2026-09-27 |
| D-24 | Bei parallel publizierten Konsenspapieren gilt die in PubMed indexierte Fassung als Zitierfassung. | Anlass L-P06 (Meeusen et al. 2013): widersprüchliche Seitenangaben aus Sekundärzitaten. | 2026-09-27 |
| D-25 | Quellenhierarchie Ausdauer (T1), Konkretisierung von D-22: Systematische Reviews und Übersichtsarbeiten bilden den Kern der Wissenskarten, Lehrbücher das Fundament für Begriffe und Physiologie. Praxisquellen (Trainerbücher, Trainerbefragungen) sind zulässig, werden in Karten aber mit `quellentyp: praxisquelle` und `konfidenz: niedrig` (Bücher) bzw. `mittel` (peer-reviewte qualitative Studien mit Trainern) geführt. Jede Karte benennt unter „Grenzen" die Übertragung von Elite-/Hochleistungsdaten auf den Athleten (Freizeitsport, drei Trainingsbereiche). „Training for the Uphill Athlete" bleibt auf Wunsch des Athleten als Praxisquelle. | Aktuelle Evidenz zu Intensitätsverteilung/Periodisierung steht in Reviews; Lehrbücher tradieren teils schwach belegte Modelle. Ausschluss Weineck: Einschätzung ohne Quelle (kompilatorisch). | 2026-09-27 |
| D-26 | Wissenskarten werden auf Deutsch verfasst (Quellen deutsch oder englisch, D-21). Beschaffung der Bücher und nicht frei verfügbaren Artikel übernimmt der Athlet. Für den Erstellungsprozess 13.1 muss jede Quelle als durchsuchbares PDF vorliegen; DRM-geschützte E-Books (z. B. VitalSource) sind ungeeignet. Formatprüfung je Titel vor dem Kauf (V-13). | Formatanforderung folgt aus 13.1 Schritt (1)–(3). Human-Kinetics-E-Books laufen über VitalSource mit DRM (festgestellt in T1 und T2). Ergänzt durch D-71 (EPUB, 2026-09-29). | 2026-09-27 |
| D-27 | Zonenreferenz der Wissenskarten ist das internationale Drei-Zonen-Modell (Zone 1 ≤ LT1/VT1, Zone 2 zwischen LT1 und LT2, Zone 3 > LT2/VT2), wie in L-T1-02 und L-T1-03. In Plänen und auf der Uhr werden die fünf Garmin-Herzfrequenzzonen genutzt, definiert als %LTHR. Abbildung 3 → 5 Zonen und Grenzwerte werden in AP-07 (Regel) und AP-08 (Ausgangstests, LT1-Bestimmung) festgelegt. Das deutsche GA1/GA2/WSA-System ist keine Referenz; Neumann/Pfützner/Berbalk nur optional. | Garmin bietet fünf HF-Zonen (BPM, %HFmax, %HFR oder %LTHR, je Sportprofil); %LTHR verankert die Zonen an einer Schwelle und ist mit der Schwellendefinition der Kernquellen kompatibel; GA-Bezeichnungen existieren auf der Uhr nicht. Einschränkung: Garmin kennt nur eine Schwelle (LTHR ≈ LT2); LT1 muss separat bestimmt werden. | 2026-09-27 |
| D-28 | Literatur Krafttraining (Geltungsbereich T2 und Kraft als Ergänzung zu T1; fingerspezifische Kraft gehört zu T3). Kernset: L-P08 (ACSM Position Stand 2026, Anker Dosierung), L-A03 (NSCA Essentials, 5. Aufl., Breite: Programmgestaltung, Testung, Technik), L-T2-03 (Schumann/Rønnestad, Concurrent Aerobic and Strength Training, Kombination Ausdauer + Kraft). Optional kapitelweise: L-T2-05 (Zatsiorsky/Kraemer/Fry), L-T2-06 (Güllich/Krüger, Sport – Lehrbuch). Zurückgestellt: L-T2-07 (Schoenfeld, Hypertrophie kein Primärziel). Weineck und Bompa nicht als Evidenzbasis. Ergänzend Primärliteratur über PubMed (V-14). **Ergänzung Hypertrophie – D-62 (2026-09-28).** | Positionspapier = aktuellste Evidenzsynthese (137 systematische Reviews, GRADE); Lehrbücher bündeln Konsens mit Verzögerung und mischen Evidenz mit Praxiserfahrung; Tokenbudget 13.1 erlaubt kein Vollprogramm. | 2026-09-27 |
| D-29 | Calisthenics ist Teil von T2. Übungskatalog mit Progressionsleitern aus L-T2-04 (Low, Overcoming Gravity, 2. Aufl. 2016), abgelegt als Abschnitt `uebungskatalog_calisthenics` in der T2-Sammeldatei, nicht als eigene Karte; Konfidenz niedrig (Praxiswissen). Dosierung (Sätze, Nähe zum Muskelversagen, Frequenz, Volumen) ausschließlich aus dem Kernset D-28. Ausgeschlossen: Wade, Convict Conditioning. | Kein wissenschaftliches Standardwerk zu Calisthenics; Progression über Hebelvarianten praktisch nicht untersucht. Belastungsprinzipien gelten unabhängig vom Widerstand (ACSM 2026 schließt Körpergewicht/Band/Heimtraining ein). Direkte Studien L-T2-08 bis L-T2-10 (per PubMed geprüft). Convict Conditioning: anonymer Autor, unbelegte Behauptungen. | 2026-09-27 |
| D-30 | Zugübungen mit Körpergewicht (Klimmzug-Varianten, Front Lever u. ä.) werden unter T3 geplant (`session.type = klettern`, Block `zugkraft`). Calisthenics unter T2 umfasst Druckübungen, Beine und Rumpf. | Starke Überschneidung mit Kletter-Zugkraft; nur bei Zuordnung zu T3 greifen die Sequenzierungsregeln (Abschnitt 14 Kap. 2). | 2026-09-27 |
| D-31 | Einheitliches Evidenzschema für alle Literaturblöcke: Stufe A = Paper/Konsens (konfidenz hoch), B = wissenschaftliche Lehrbücher (mittel), C = Praxisquellen (niedrig). Stufe C darf in Wissenskarten als Übungs-/Ideenfundus und mit Kennzeichnung zitiert werden, aber nie allein einen Belastungsparameter (Dosierung, Progression, Schwelle) begründen. Volltexte (Open Access und gekaufte PDFs) dürfen im privaten Repo unter `docs/literatur/` liegen, nie im Projektwissen; einzelne Dateien < 100 MB (GitHub-Grenze), große Bücher als Kapitel-PDFs. Evidenzkern T3 gemäß E6 übernommen (L-T3-01, -02, -03, -06, -08; -09 optional). Klettermedizin: englische Ausgabe 2022 (L-T3-06) bevorzugt, deutsche 2020 (L-T3-07) als Alternative. | Vereinheitlicht D-22, D-25, D-29 und die T3-Vorschläge E3–E6; bestätigt durch Athlet (Q-07, Q-08). Evidenzkern T3 geändert durch D-70 (2026-09-29). | 2026-09-27 |
| D-32 | Token-Format OAuth (AP-01). **Access-Token** = JWT, signiert mit HS256, Laufzeit 1 h, Secret aus `.env` (`OAUTH_JWT_SECRET`, Pflicht, ≥ 32 Zeichen). Claims: `iss` (= `APP_URL`), `aud` (= `APP_URL` + `/mcp`), `sub` (Benutzer-ID), `client_id`, `scope`, `iat`, `exp`, `jti`. Prüfung am `/mcp`-Endpunkt über den SDK-`JwtTokenValidator` (iss/aud/exp); Access-Tokens werden nicht in der DB gespeichert. **Refresh-Token** = zufälliger Wert (≥ 32 Byte), nur gehasht in `oauth_token` abgelegt, widerrufbar, **Rotation bei jeder Nutzung** (altes Token wird als benutzt markiert, neues ausgegeben, gleiche `family_id`); Wiederverwendung eines bereits rotierten Refresh-Tokens widerruft die **gesamte Token-Familie**. Tabelle `oauth_token` hält damit nur Refresh-Tokens (Feld `type` bleibt für Erweiterbarkeit). | Der SDK-Validator kann JWT direkt prüfen, ohne DB-Zugriff pro Request; HS256 genügt, da Aussteller und Prüfer derselbe Server sind. Rotation mit Familien-Widerruf ist die OAuth-2.1-Empfehlung gegen gestohlene Refresh-Tokens. **Tradeoff:** Ein Widerruf wirkt für bereits ausgegebene Access-Tokens erst nach deren Ablauf (≤ 1 h), weil sie nicht gegen die DB geprüft werden – für einen Einzelnutzer akzeptiert; im Notfall wird `OAUTH_JWT_SECRET` gewechselt, was alle Access-Tokens sofort ungültig macht. | 2026-09-27 |
| D-33 | Login der Webseite (Q-05): **Passwort** mit langer Session (**30 Tage**, gleitend über `last_seen_at`). Die Session liegt in der eigenen Tabelle `web_session` (`token_hash`, `user_id`, `csrf_secret`, `created_at`, `last_seen_at`, `expires_at`), das Session-Token im Cookie (`HttpOnly`, `Secure`, `SameSite=Lax`), in der DB nur gehasht. Schutz gegen Raten (Werte vom Athleten bestätigt 2026-09-27): `user.failed_logins` und `user.locked_until` – nach **10 Fehlversuchen** wird das Konto **5 Minuten** gesperrt; bei weiteren Fehlversuchen nach Ablauf der Sperre verdoppelt sich die Sperrdauer jeweils (10, 20, 40 min …, **maximal 24 h**); der Zähler `failed_logins` läuft dabei weiter und bestimmt die Stufe. Ein **erfolgreicher Login setzt** `failed_logins` **auf 0** und löscht `locked_until`. Passwort-Hash mit `password_hash()` (Argon2id, sonst bcrypt). Passkey (WebAuthn) optional in AP-09. Der Login wird bereits in AP-01 gebaut, weil `/oauth/authorize` ihn voraussetzt. | PHP-Standardsessions auf Shared Hosting leben nicht zuverlässig 30 Tage (Garbage Collection, Sitzungsverzeichnis des Hosters); eigene Tabelle macht Laufzeit und Widerruf kontrollierbar. Passkey ist Komfort, kein Sicherheitsgewinn, solange nur ein Gerät angemeldet wird. | 2026-09-27 |
| D-34 | Erstes Passwort: einmalige Seite `/setup`, nur aktiv, **solange kein Benutzer existiert**; verlangt das `MIGRATION_SECRET` aus `.env` und legt den einzigen Benutzer (Login, Passwort, Zeitzone) an. Sobald ein Benutzer existiert, antwortet `/setup` dauerhaft mit 404; ein Zurücksetzen erfolgt nur über die Datenbank (Benutzer löschen). | Kein Seed-Passwort im Repo oder in Migrationen; das ohnehin vorhandene Deploy-Secret beweist Betreiberrechte. Einzelnutzer (N8): kein Einladungs- oder Registrierungsfluss nötig. | 2026-09-27 |
| D-35 | Die Tabellen `user`, `web_session`, `oauth_client`, `oauth_auth_code`, `oauth_token` werden in **AP-01** als Migrationen angelegt (vorgezogen aus AP-03). AP-03 legt nur noch die Trainingstabellen an (`training_block`, `training_week`, `session`, `session_execution`, `pain_event`, `checkin`, `audit_log`, optional `ext_cache`). | AP-01 braucht Login und OAuth-Persistenz produktiv; Q-05 ist entschieden (D-33), damit entfällt die frühere Wartebedingung für AP-03. | 2026-09-27 |
| D-36 | Dynamic Client Registration (`/oauth/register`) ist **offen** (jeder Client darf sich registrieren, RFC 7591, ohne Vorab-Secret). Schutz liegt im Authorize-Schritt: Login (D-33) und eine **ausdrückliche Freigabeseite**, die Client-Name und Redirect-Host anzeigt und eine Bestätigung verlangt; ohne Bestätigung kein Code. Regeln: Redirect-URIs nur `https://` oder `http://localhost` bzw. `http://127.0.0.1` (exakter Vergleich gegen die registrierte URI); **PKCE mit `S256` Pflicht** (`plain` abgelehnt); Autorisierungscodes 10 min gültig, einmalig, gehasht gespeichert; unbenutzte Client-Registrierungen dürfen nach 30 Tagen aufgeräumt werden. | claude.ai registriert seinen Client selbst und erwartet offene DCR; eine Registrierung allein verschafft keinen Zugriff, weil jeder Zugriff die Freigabe des angemeldeten Athleten braucht. Localhost-Ausnahme für Claude Desktop/Code und lokale Tests. | 2026-09-27 |
| D-37 | **Design-Mockups vor AP-01** (eigenes Vorpaket AP-01a, Fable): Mockups aller Screens – S0 Setup, S1 Login, S2 Woche, S3 Einheit, S4 Check-in, S5 Schmerz, S7 OAuth-Freigabe – mobil- und tabletfreundlich, auf Grundlage der Gestaltungsvorgaben des Athleten (werden noch geliefert). Ergebnis ist das Branding-Dokument unter `docs/branding/` (D-19); Abnahme durch den Athleten ist Voraussetzung für AP-01. | Bereits AP-01 baut drei sichtbare Seiten (Setup, Login, Freigabe); eine spätere Umgestaltung wäre doppelte Arbeit. Die Code-Instanz setzt Mockups um, entwirft sie aber nicht (Rollenverteilung Abschnitt 0). | 2026-09-27 |
| D-38 | Laufzeit der Refresh-Tokens (Q-09): **90 Tage**, jede Rotation beginnt die Laufzeit neu. Nach Ablauf muss der Connector in Claude neu verbunden werden (Login + Freigabe). | Bei wöchentlicher Nutzung nie ein erneuter Login; ein verlorenes oder vergessenes Gerät verliert den Zugang spätestens nach 90 Tagen ohne Nutzung. Bestätigt durch Athlet. | 2026-09-28 |
| D-39 | `plan_json` für die in 7.1 fehlenden Typen (Q-10): `mobilitaet` nutzt das Schema `kraft_oder_haltung` (Übungsliste; Haltezeiten als `reps` z. B. „30s“); `ruhe` hat kein `plan_json` (leer oder nur `notes`). | Passt zu den Mockups (S3) und hält Webseite und MCP-Schnittstelle einfach. Bestätigt durch Athlet. | 2026-09-28 |
| D-40 | MCP-Tool `upsert_block` (Q-11): legt einen Trainingsblock an oder ändert ihn (Name, Zeitraum, Status, Zielevents, Phasen, Verweis auf `docs/plaene/`); Status „aktiv“ schließt andere aktive Blöcke ab. Voraussetzung für `write_week_plan`. | Der Blockplan entsteht im Projekt-Chat (AP-08); Claude legt ihn nach Bestätigung selbst an, ohne Handarbeit in der Datenbank. Bestätigt durch Athlet. | 2026-09-28 |
| D-41 | Optionales Feld `sport` in `plan_json.ausdauer` (Q-12): Intervals.icu-Sportart des Events (Run, TrailRun, Hike, Walk, Ride, MountainBikeRide, GravelRide, BackcountrySki, NordicSki, Snowshoe, Swim, Rowing; Standard Run). | Skitour und Wandern kommen mit dem richtigen Sportprofil und den passenden Zonen (V-12) auf die Uhr. Bestätigt durch Athlet. | 2026-09-28 |
| D-42 | AP-09 wird umgesetzt mit: asymmetrischer Backup-Verschlüsselung, JSON-Export, Cron-Spiegel Intervals.icu → MySQL, Feedback-Rückschreiben nach Intervals.icu (Q-02), Verlauf S6, Passkey-Login, Athletenprofil als DB-Objekt, Offline-Fähigkeit. Vorgehen: direkte Umsetzung in der Reihenfolge JSON-Export → S6 → Spiegel → Rückschreiben → Passkey → asymmetrische Backups → Profil → Offline; Detailfragen zu einzelnen Punkten vorab einzeln. Asymmetrische Backups nachträglich gestrichen (D-47). | Entscheidung des Athleten nach Erklärung der Optionen. | 2026-09-28 |
| D-43 | Cron-Spiegel (ändert D-09): Aktivitäten und Wellness werden zusätzlich regelmäßig per Lima-City-Cronjob in eigene Tabellen übernommen; Webseite und MCP lesen aus dem Spiegel, Live-Abfrage nur als Rückfall. | Datenhoheit (auch im Backup), schnellere Seiten, Verläufe über lange Zeiträume (S6). | 2026-09-28 |
| D-44 | Passkey (WebAuthn) zusätzlich zum Passwort (ergänzt D-33); das Passwort bleibt Rückfallweg bei Geräteverlust. | Komfort im Alltag ohne Aussperr-Risiko. | 2026-09-28 |
| D-45 | Offline-Fähigkeit (ändert Abschnitt 10 „kein Offline-Modus in Phase 1“): Woche und Einheiten offline lesbar; Check-in, Rückmeldung und Schmerz offline erfassbar, werden gepuffert und bei Netz automatisch gesendet. Dafür Service Worker und JavaScript über das bisherige Minimum hinaus. | Nutzung in Halle/Gebirge ohne Netz. | 2026-09-28 |
| D-46 | Feedback-Rückschreiben nach Intervals.icu (Q-02): beim Speichern einer Rückmeldung auf der Webseite werden RPE (nur 1–10; RPE 0 wird nicht übertragen) und Gefühl (1–5, gleiche Richtung wie D-16/Abschnitt 11, V-03) automatisch auf die zugeordnete Intervals.icu-Aktivität geschrieben; die Notiz wird als Kommentar an die Aktivität angehängt, nur wenn sie neu oder geändert ist. Fehler brechen das Speichern nicht ab und werden angezeigt. | Intervals.icu-Diagramme vollständig; eigene Beschreibung in Intervals.icu bleibt unberührt. Entscheidung des Athleten. | 2026-09-28 |
| D-47 | Asymmetrische Backup-Verschlüsselung wird nicht umgesetzt (ändert D-42); Backups bleiben passwortverschlüsselt nach D-18 (`openssl enc`, AES-256-CBC, PBKDF2). Auch kein Wechsel auf AES-ZIP. | Ein Serverkompromiss legt die Live-Datenbank ohnehin offen, ein Postfachkompromiss enthält das Passwort nicht; Gewinn nur im Randfall „.env und alte Backups“ (ältere/gelöschte Stände). Kosten: Verwaltung eines privaten Schlüssels, dessen Verlust alle Backups unlesbar macht, umständlicherer Restore. ZIP wäre ebenfalls symmetrisch; der Restore braucht ohnehin die Kommandozeile, zum Ansehen gibt es den JSON-Export. Entscheidung des Athleten nach Erklärung. | 2026-09-28 |
| D-48 | Athletenprofil als DB-Objekt (ersetzt D-15): Tabelle `athlete_profile` mit festen Abschnitten `ziele`, `zeitbudget`, `ausruestung`, `einschraenkungen`, `leistungswerte`, `sonstiges`, je Abschnitt Markdown-Text (höchstens 6 000 Zeichen). Jede Änderung legt eine neue Fassung an (Datum, Urheber Claude/Web, optionaler Grund); frühere Stände bleiben lesbar. Die DB ist die einzige Quelle: `docs/athlet/profil.md` und die Kopie im Projekt-Wissen entfallen. Bearbeiten durch Claude (`update_athlete_profile`, Scope `training:write`) und auf der Webseite (`/profil`, erreichbar über S8); Schutz gegen gegenseitiges Überschreiben. | Profil ändert sich mit Tests und Lebensumständen; Claude kann Werte direkt im Chat eintragen, ohne Repo und Deployment. Abschnitte statt eines Dokuments, damit Änderungen gezielt sind; Fassungen, damit spätere Auswertungen den damaligen Stand kennen. Entscheidung des Athleten (Struktur, Bearbeiter, Quelle, Verlauf). | 2026-09-28 |
| D-49 | Ausgestaltung Offline (konkretisiert D-45): (a) Vorgeladen werden beim Öffnen der aktuellen Woche die aktuelle und die nächste Woche mit allen Einheiten sowie Check-in und Schmerz für heute; weitere besuchte Seiten dieser Art werden beim Aufruf gespeichert, andere Seiten sind offline nicht verfügbar. (b) Abmelden löscht die gespeicherten Seiten auf dem Gerät; noch nicht gesendete Eingaben bleiben und werden nach dem nächsten Login gesendet. (c) Wurde ein Eintrag (Check-in, Rückmeldung) seit dem Laden des Formulars geändert, wird eine gepufferte Eingabe nicht übernommen, sondern als Hinweis mit „Öffnen“, „Trotzdem übernehmen“ und „Verwerfen“ angezeigt; dieselbe Prüfung gilt online (Formular bleibt mit den Eingaben stehen, erneutes Speichern übernimmt). Schmerzereignisse sind immer neue Einträge und kollidieren nicht. | Entscheidung des Athleten (Umfang, Abmelden, Konflikt); „Trotzdem übernehmen“ ergänzt in der Umsetzung, damit eine Eingabe nach Prüfung nicht neu getippt werden muss. | 2026-09-28 |
| D-50 | Kalender per CalDAV-Push (AP-11): Die App schreibt jede Einheit außer Ruhetagen als ganztägigen Termin in einen Nextcloud-Kalender (eigener Kalender empfohlen), mit fester UID je Einheit; Titel „Typ: Titel“, Beschreibung mit Priorität, Dauer, Kurzplan, Trainer-Begründung und Link zur App. Status: erledigt/teilweise mit „✓“ im Titel, ausgelassen als abgesagter Termin, verschoben wandert mit dem Datum. Übertragen wird bei jeder Änderung (Wochenplan, update_session, Ersetzen einer Woche, Rückmeldung auf der Webseite); zusätzlich Abgleich 7 Tage zurück bis 8 Wochen voraus im stündlichen Cronjob und per Knopf in den Einstellungen, dabei werden verwaiste eigene Termine entfernt, fremde nie. Zugang über Nextcloud-App-Passwort in der .env (CALDAV_URL nur https); Fehler brechen nichts ab. Einbahnstraße: Änderungen im Kalender werden nicht zurückgelesen. **Zuschnitt geändert durch D-60 (2026-09-28): ein Sammeltermin je Tag statt je Einheit, ohne Status-Markierung und ohne abgesagte Termine.** | Entscheidung des Athleten (CalDAV statt ICS-Abo: sofort sichtbar; Umfang ohne Ruhetage; Markierung; Abgleich im bestehenden Cronjob; direkte Umsetzung durch die Code-Instanz). Einheiten haben keine Uhrzeit, daher ganztägig. | 2026-09-28 |
| D-51 | Ablage der Volltexte in `docs/literatur/`: Unterordner je Block (`uebergreifend/`, `t1-ausdauer/`, `t2-kraft/`, `t3-klettern/`), eine Datei im Block ihrer ID-Definition; Dateiname `<ID>_<Erstautor>-<Jahr>_<Kurztitel>[_<Auflage>].pdf` (ASCII). Bücher zusätzlich als Kapitel-PDFs in `<ID>_kapitel/` (Schritt 1 in 13.1; Kapitel über 60 PDF-Seiten in etwa gleich große Teile, möglichst an Abschnittsgrenzen); das Originalbuch bleibt liegen. Im Konzept verweisen die Felder `datei`/`kapitel` auf die Dateien, 13.4 führt die Spalte „vorhanden“, `docs/literatur/README.md` ist das Verzeichnis. L-A01 liegt in der 7. Aufl. (2019) vor: gilt vorläufig, die 8./9. Aufl. bleibt auf der Beschaffungsliste. | Entscheidung des Athleten 2026-09-28 (Unterordner statt flacher Ablage; Kapitel-PDFs zusätzlich zum Original statt Ersatz; 7. Aufl. nur vorläufig). Kapitel-PDFs sind nötig, weil die Bücher (bis 1876 Seiten, 65 MB) für Chat-Sitzungen zu groß sind. Für EPUB ergänzt durch D-71 (2026-09-29). Nachtrag 2026-09-29 (Entscheidung Athlet): vorhandene Ausgaben werden verwendet, auf neuere Auflagen wird nicht gewartet – L-A01 bleibt in der 7. Aufl. (nicht mehr vorläufig), L-T3-09 in der 3. Aufl., L-T2-33 und L-R-30 in der 3. Aufl. | 2026-09-28 |
| D-52 | Erinnerung an Kalenderterminen (ergänzt D-50): Jeder Termin einer geplanten oder verschobenen Einheit trägt eine Erinnerung (VALARM) am Tag der Einheit zur eingestellten Uhrzeit, Standard 05:00; erledigte und ausgelassene Einheiten erinnern nicht (seit D-60: eine Erinnerung je Tag, solange mindestens eine Einheit des Tages geplant oder verschoben ist). Uhrzeit oder „keine Erinnerung“ in den Einstellungen (S8), gespeichert in der neuen Tabelle `app_setting` (Schlüssel/Wert); nach dem Ändern werden die Termine im Abgleichzeitraum sofort neu übertragen. | Wunsch des Athleten (Uhrzeit einstellbar, Standard 05:00); Ausnahmen für erledigt/ausgelassen und die Aus-Option in der Umsetzung ergänzt, weil eine Erinnerung dort keinen Nutzen hat. | 2026-09-28 |
| D-53 | Morgen-Check-in mit Morgentest (AP-12): Auftrag und Entscheidungen E-01–E-13 in `docs/konzept/morgen-checkin.md`. Kern: Morgentest Patellasehne links/rechts (NRS 0–10, leer = nicht erhoben), weitere Angaben (Nacken/BWS, Sprunggelenk links, Hand rechts bis Stichtag, Warnzeichen) als Erweiterung des bestehenden Check-ins (ein Eintrag je Tag); feste Ampelregeln (rot > 5 oder zwei Tage streng steigend bis ≥ 4, gelb 4–5, grün ≤ 3) als reine Information, Planänderungen macht Claude; Bereitstellung über die Karte auf der Startseite und `get_morning_checks`. Erholung/Muskelkater bleiben Pflicht. Schmerzorte um Patellasehne, Sprunggelenk und BWS ergänzt. | Auftrag aus dem Trainer-Chat, bestätigt durch den Athleten; Speicherort, Pflichtfelder, Briefing und Schmerzorte in Rücksprache mit dem Athleten festgelegt. | 2026-09-28 |
| D-54 | Literatur Haltung/Rücken (Teilblock T2). Kern: L-T2-15 (Warneke 2024, Kräftigung vs. Dehnung, Anker), L-T2-16 (Khorramroo 2026, Korrekturübungen bei Upper Crossed Syndrome), L-T2-17 (Shiri 2018, Prävention Kreuzschmerz mit Dosierung), L-T2-18 (Steffens 2016, Prävention Kreuzschmerz). Optional: L-T2-19 (Carrasco-Uribarren 2026, Nacken vs. Nacken + BWS), L-T2-14 (Cowley 2026). Zurückgestellt: L-T2-13 (McGill, Stufe C). Übungsbeispiele aus Praxisquellen (z. B. McGill „Big 3“) dürfen in Karten nur als gekennzeichnete Beispiele stehen (D-31). Geltungsbereich: thorakale und zervikale Extension (Vorkopfhaltung, thorakale Kyphose) plus Rumpfkraft und Prävention von Kreuzschmerzen. Planungsfolgen (in AP-07 als Regeln auszuformulieren): (a) Haltungsarbeit als Kräftigung (thorakale/zervikale Extensoren, Schulterblattmuskulatur), nicht als Dehnprogramm; (b) Kreuzschmerz-Prävention über Kräftigung kombiniert mit Dehnung oder Ausdauer, 2–3× pro Woche, eingebettet in bestehende Kraft-/Haltungseinheiten, kein eigener Block; (c) Karten führen unter „Grenzen“, dass verbesserte Haltungswinkel nicht zuverlässig weniger Schmerz oder bessere Funktion bedeuten. | L-T2-15: 23 Studien, gesunde Personen, GRADE moderat – Dehnen ohne Effekt auf Haltung, Kräftigung wirksam an BWS/HWS, nicht an LWS/Becken. L-T2-16: 28 RCTs – große Effekte auf Haltungswinkel, Schmerz/Funktion inkonsistent. L-T2-17/18: Training (mit oder ohne Aufklärung) senkt das Risiko von Kreuzschmerz-Episoden; Aufklärung allein, Rückengurte, Einlagen wirkungslos. Stufe-C-Buch nicht nötig, da Dosierung vollständig aus Stufe A (D-31). Bestätigt durch Athlet (Literatur-Sitzung AP-06 Teil A; dort als D-38 vergeben, wegen Kollision umnummeriert; zunächst D-53, nach Merge von AP-12 D-54). Ergänzung D-80: L-T2-33 (McGill, Low Back Disorders, Stufe B, optional) als Referenz für Biomechanik und Übungsauswahl; Dosierung unverändert aus dem Kern. | 2026-09-28 |
| D-55 | App-Icon und Logo: Das Logo der App wechselt vom Lama-Kopf auf das ganze Lama (Variante D-59). Icon-Satz für alle Browser: PNG 48/96/192/512 `any`, 512 `maskable`, `apple-touch-icon` 180, `favicon.ico`, SVG; Manifest, PNG-Icon-Links, `apple-touch-icon` und `theme-color` in **beiden** Layouts (auch Login). Details `docs/konzept/gefuehrte-einheit.md` Teil A (AP-13). | Wunsch des Athleten (Kopf gefällt nicht; Verknüpfung auf Android ohne Logo). Die Login-Seite hatte nur ein SVG-Favicon; Firefox-Abkömmlinge nutzen für Verknüpfungen das Favicon, nicht das Manifest. | 2026-09-28 |
| D-56 | Begründungstexte der Planung: je Woche und Einheit ein Kurzsatz (Was und warum) und ein ausführlicher Text. Woche: `focus` (Kurzsatz, Pflicht) + `coach_notes`; Einheit: neues Feld `coach_summary` (max. 200 Zeichen, Pflicht außer `ruhe`) + `coach_rationale` (≤ 1 500 Zeichen). Anzeige: Kurzsatz bei Woche (unter der Kopfzeile) und Einheit (Seitenkopf), „mehr“ als `<details>` ohne JavaScript; nicht in der Wochenliste. Kalendertermin führt den Kurzsatz als erste Zeile (seit D-60 bei mehreren Einheiten eines Tages je Abschnitt nach der Überschrift „Typ: Titel“). Regel für den Inhalt in den Tool-Beschreibungen und in AP-07. | Wunsch des Athleten; eigene Kurzfelder statt Konvention „erster Absatz“ (Entscheidung 2026-09-28). | 2026-09-28 |
| D-57 | Geführte Einheit (S9): `GET /einheit?id=…&modus=start` zeigt für `kraft`, `haltung`, `mobilitaet`, `klettern` dasselbe Formular wie S3 schrittweise (eine Übung je Schritt, Ist-Felder, „Als Nächstes“, Abschluss mit Rückmeldung), gespeichert einmal am Ende über `POST /einheit`. Ablaufplan aus `plan_json` serverseitig und deterministisch (Halten bei `reps` in s/min und `hang_s`, Block bei `duration_min`, sonst Wiederholungen – bei Kletterblöcken ab zwei Sätzen, sonst „offen“, E-23 im Auftrag); Automatik innerhalb einer Übung, „Weiter“ zwischen Übungen; Pausentimer nach „Satz erledigt“. Ohne JavaScript alle Schritte sichtbar. Nicht für `ausdauer` (Uhr) und `ruhe`. Details `docs/konzept/gefuehrte-einheit.md` Teil C (AP-14). | Wunsch des Athleten; Wiederverwendung von Formular, Konfliktschutz und Offline-Puffer; kein Serverzustand während des Trainings (Entscheidung 2026-09-28). | 2026-09-28 |
| D-58 | Timer und Signale in S9: Grün nur in der Arbeitsphase, Rot in Pause/bereit/angehalten, sonst normale Farbe (Statusfarben des Design-Systems, B-08). Töne über Web Audio (Start, 30 s und 10 s vor Ende, letzte 3 s, Abschlusston) plus Vibration; Bildschirm bleibt an (Wake Lock). Stumm: Einstellung `timer_ton` in `app_setting` (S8) und Schalter in der Einheit. Zeit zeitstempelbasiert, Fortschritt im Browser (`sessionStorage`, 12 h). | Vorgabe des Athleten (Rot → Grün, Signalzeitpunkte, stummschaltbar an zwei Stellen); Grün = Arbeit, Wake Lock und Vibration am 2026-09-28 bestätigt. | 2026-09-28 |
| D-59 | Logo-Variante (Q-14): V3 (Lama Fläche hell auf Pflaume 600) als App-Icon Android/iOS und `maskable`; V2 (Lama Fläche Pflaume 600 auf Papier) als Favicon 16/32 px, SVG-Favicon und App-Kennung in Topbar, Navigation und Login. Vorlagen in `docs/branding/mockups/icon-optionen/`. **Favicon geändert durch D-63** (V3 gerundet). | Entscheidung des Athleten, wie von Fable empfohlen: Kontrast auf dem Startbildschirm, Lesbarkeit bei 16 px, Kennung auf Papier wie bisher. | 2026-09-28 |
| D-60 | Ein Sammeltermin je Tag im Kalender (ändert D-50, passt D-52 an): Statt eines Termins je Einheit schreibt die App je Trainingstag einen ganztägigen Termin (Ressource `training-tag-<Datum>.ics`, feste UID je Tag; nach dem Löschen eines Tagestermins bekommt der nächste eine neue Fassung `-1`, `-2` … mit eigener UID, weil Nextcloud Gelöschtes im Papierkorb hält). Titel „Typ: Titel“ bei einer Einheit, sonst „Training: Titel 1 + Titel 2“ in Planreihenfolge; kein Status-Zeichen im Titel, der Termin wird nie abgesagt – der Status steht je Einheit in der Beschreibung. Beschreibung: alle Einheiten des Tages ohne Ruhetage, je Einheit Überschrift (bei mehreren), Kurzsatz, Kurzplan mit Priorität/Dauer/Status, Trainer-Begründung (gekürzt) und Link; der Termin verlinkt die Woche. Erinnerung einmal je Tag, solange eine Einheit geplant oder verschoben ist. Bei jeder Änderung wird der ganze Tag neu geschrieben (beim Verschieben alter und neuer Tag); Tage ohne Einheiten verlieren ihren Termin. Der Abgleich ersetzt die alten Einzeltermine im Abgleichzeitraum; ändert die App eine Einheit, entfernt sie deren Einzeltermin sofort, auch außerhalb des Zeitraums; ältere Einzeltermine unberührter Einheiten bleiben. | Wunsch des Athleten (ein Termin je Tag, übersichtlicher Kalender); Titel aus den Einheitentiteln, kein Status-Zeichen und Umfang (Unterpunkt T8, eigener Code-Stand) am 2026-09-28 gewählt. | 2026-09-28 |
| D-61 | Neuer Literaturblock R Reha/Prävention (13.2.5), themenübergreifend zu T1 und T2, IDs L-R-nn, Wissenskarte docs/wissen/r-reha-praevention.md (Dateiname Vorschlag). Teilthemen: Patellatendinopathie (Übungstherapie, Lastdosierung, Messinstrument VISA-P), Sprunggelenksinstabilität/Rezidivprophylaxe, Laufumfang und Verletzungsrisiko. Kern: L-R-01 bis L-R-09, L-R-13 bis L-R-17, L-R-23 bis L-R-25. Optional: L-R-10 bis L-R-12, L-R-18 bis L-R-22, L-R-26 bis L-R-28. Planungsfolgen (in AP-07 als Regeln auszuformulieren, nicht hier entschieden): (a) Sehnentraining: progressive Belastung, Lasthöhe moderat bis schwer gleichwertig; 1 Trainingstag/Woche als Einzelstudienbefund (TEREX) kennzeichnen; (b) Isometrik als verträglicher Einstieg, nicht als überlegene Schmerztherapie; (c) Sprunggelenk: neuromuskuläres/Balance-Training zur Rezidivprophylaxe; Dosis nach Tang gilt für Funktion/Balance, nicht für Rezidivschutz; Gerätetyp zweitrangig; (d) Laufprogression: Einzellauf-Spitzen statt Wochenprozent (→ Q-15); (e) Karte führt unter „Grenzen": Übungstherapie vs. keine Behandlung laut Cochrane sehr unsicher (L-R-23); Dorsalextension als Risikofaktor nur begrenzt/widersprüchlich belegt (L-R-11). | Begründung in den Einträgen 13.2.5 und im Prüfprotokoll AP-06. Bestätigt durch Athlet (Literatur-Sitzung AP-06 Teil C; dort als D-39 vergeben, wegen Kollision umnummeriert). Ergänzung D-80: L-R-29 und L-R-30 (Stufe B, optional) als Nachschlagewerke; Regeln unverändert aus Stufe A. | 2026-09-28 |
| D-62 | Literatur Muskelhypertrophie als Ergänzung zu T2. D-28 bleibt unverändert: Hypertrophie ist kein Primärziel, L-T2-07 bleibt zurückgestellt. Schwerpunkte: Dosierung, Heimtraining mit leichten Lasten und Band, Interferenz mit Ausdauer; Ernährung ist nicht im Umfang. Kern: L-T2-20 bis L-T2-26. Optional: L-T2-27 bis L-T2-32. Planungsfolgen (in AP-07 als Regeln auszuformulieren, nicht hier entschieden): (a) Das Wochenvolumen je Muskelgruppe ist der Haupthebel für Hypertrophie, mit abnehmendem Grenznutzen; die Frequenz ist für Hypertrophie nachrangig, für Kraft wirksam (L-T2-20). (b) Sätze nahe am Muskelversagen beenden; Training bis zum Versagen bringt allenfalls trivialen Zusatznutzen (L-T2-21, L-T2-22). (c) Leichte Lasten wirken hypertrophiewirksam, wenn die Sätze nahe ans Versagen gehen; Maximalkraft braucht höhere Lasten bzw. schwerere Varianten (L-T2-23; Band bei Kraft gleichwertig L-T2-24; Calisthenics D-29). (d) Interferenz: Die Hypertrophie des ganzen Muskels ist im kombinierten Training nicht beeinträchtigt (L-T2-26). Auf Faserebene gibt es einen kleinen Nachteil, vorläufig ausgeprägter mit Laufen (L-T2-25) und mit HIIT im Ausdauerteil (L-T2-26). (e) Die Karte führt unter „Grenzen“: Für Hypertrophie mit Band oder Kettlebell bei gesunden Erwachsenen wurde keine Metaanalyse gefunden; die Übertragung über L-T2-23 ist ein Schluss (Kennzeichnung „Einschätzung“, D-13). L-T2-21 ist explorativ mit geschätzten RIR. Die Befunde zur Modalität sind widersprüchlich (L-T2-25/L-T2-31 vs. L-T2-32). | Anfrage des Athleten nach Hypertrophie-Literatur. L-P08 bleibt Anker; die Ergänzung liefert Dosis-Wirkungs-Befunde und die im Plan relevanten Sonderfragen (leichte Lasten im Heimtraining, Kombination mit Trailrunning). Bibliografie per PubMed geprüft. Bestätigt durch Athlet (Literatur-Sitzung AP-06 Teil D). | 2026-09-28 |
| D-63 | Favicon gerundet (ändert D-59 für das Favicon): Im Browser-Tab steht V3 (Lama hell auf Pflaume 600) mit um 22 % gerundeten, transparenten Ecken (SVG, PNG 32/48/96, `favicon.ico` 16/32/48; Vorlage `icon-optionen/v3-favicon.svg`). Startbildschirm-Icons (PNG 192/512 `any`, 512 `maskable`, `apple-touch-icon` 180) bleiben eckig; App-Kennung in Topbar, Navigation und Login bleibt wie in D-59. | Wunsch des Athleten („so eckig sieht es nicht so gut aus“), Varianten im Vergleich gewählt (C mittel statt leicht oder Kreis; Kreis schneidet Ohren und Beine an). V3 statt V2, weil der Papiergrund im hellen Tab verschwindet und Firefox ohnehin die V3-PNGs zeigte. Launcher (Android, iOS) schneiden selbst zu; iOS färbt Transparenz schwarz. | 2026-09-28 |
| D-64 | Übungskatalog in der Datenbank (AP-16): Tabellen `exercise`, `exercise_alias`, `exercise_version`; `plan_json` verweist über ein optionales `exercise_id` (Slug) in `exercises[]` und `blocks[]` auf die Übung. Die Einheit beschreibt nur die Dosierung, Ausführung, Achtungspunkte, Fehlerquellen, Vorsicht, Progression, Links und Videos stehen einmal im Katalog. Details `docs/konzept/uebungskatalog.md` (E-01, E-07, E-08). | Entscheidung des Athleten; Beschreibung nur an einer Stelle. | 2026-09-29 |
| D-65 | Die planende Instanz legt Übungen selbst an (`upsert_exercise`), erst nach Bestätigung des Wochenplans, und zeigt den Eintrag im Chat in Kurzform (`hinweis_chat`). Vor jeder Planung ist die Ähnlichkeitsprüfung mit `find_exercise` Pflicht (E-02). | Entscheidung des Athleten; konsistent mit `upsert_block` (D-40) und `update_athlete_profile`. | 2026-09-29 |
| D-66 | Weiche Regel (E-03): `write_week_plan`/`update_session` nehmen Übungen ohne `exercise_id` an und melden sie als `warnungen`; ein unbekanntes oder archiviertes `exercise_id` ist ein Fehler. Bestehende Einheiten bleiben gültig. | Entscheidung des Athleten (Freitext bleibt möglich, Katalog wird trotzdem eingefordert). | 2026-09-29 |
| D-67 | Videos werden eingebettet (E-04): YouTube über `youtube-nocookie.com`, Vimeo über `player.vimeo.com`, nur diese beiden Hosts (CSP `frame-src`); andere Videolinks bleiben Links. Offline zeigt die Übungsseite statt des Videos einen Platzhalter mit Link. Der `iframe` sendet einen Referrer mit der Domain (E-17). | Entscheidung des Athleten; nur er hat Zugriff, Datenschutzabwägung akzeptiert. | 2026-09-29 |
| D-68 | Kletterblöcke (E-05): Katalogeinträge nur für `hangboard`, `campus`, `zugkraft`, `antagonisten`; `bouldern_volumen`, `bouldern_limit`, `ausdauer_route`, `technik` sind Einheitenformate ohne `exercise_id` (Schemafehler). | Entscheidung des Athleten (Empfehlung Fable); am Hangboard ist die Ausführung die Hauptprävention. | 2026-09-29 |
| D-69 | Quellenpflicht im Katalog (E-06): jede Übung trägt `konfidenz` (hoch, mittel, niedrig, einschaetzung) und mindestens eine Quelle (Literatur-, Regel- oder Entscheidungs-ID oder „Einschätzung“). | Entscheidung des Athleten; Begründungsspur wie bei den Trainerregeln (Abschnitt 14). | 2026-09-29 |
| D-70 | Literatur-Nachsteuerung T1/T3 und HRV: (a) L-T1-01 wird nicht aufgenommen; seine Rolle übernimmt L-A02 über den Verweis L-T1-15 (Kapitelangaben dort), Periodisierung tragen L-P01, L-P02, L-A03 Kap. 22, L-T1-03 und L-T1-08. (b) L-T3-08 wird zurückgestellt; L-T3-19 (Consuegra) ersetzt L-T3-08 im Evidenzkern T3 (ändert D-31/E6): Kern L-T3-01, -02, -03, -06, -19 und L-T3-09 mit der vorhandenen 3. Aufl. 2016; die Neuauflage (angekündigt 02.03.2027) wird nach Erscheinen zusätzlich beschafft. (c) Stufe-C-Quellen T3: L-T3-10 (Ideenfundus), L-T3-20 (Übungsband zu L-T3-10), L-T3-21 (Verletzungsmanagement, Ergänzung zu L-T3-06 und Block R; vorläufig bis zur Bestätigung durch den Athleten) und L-T3-16 (Planungsvorlage, 2. Aufl.); D-31 gilt unverändert (nie alleiniger Beleg für Belastungsparameter). (d) L-P15 (Kern) und L-P16 (optional) sind die Literaturbasis für HRV-gestützte Steuerung (Abschnitt 14 Kap. 4; Baseline in `get_wellness_trend`). | L-T1-01 und L-T3-08 nicht digital erhältlich, Gesamtscan nicht vorgesehen. Abdeckung L-T1-01 am Inhaltsverzeichnis L-A02 geprüft; für L-T3-08 kein gleichwertiges deutschsprachiges Werk digital verfügbar. HRV-Abweichung als Deload-Trigger war ohne Quelle. Bestätigt durch Athlet (Übergabe AP-06 Literatur-Nachsteuerung, dort D-64). Abweichend von der Übergabe (E-3) auf Entscheidung des Athleten 2026-09-29: 3. Aufl. L-T3-09 behalten (lag bereits vor), Übungsband als eigene ID L-T3-20. L-T3-10 lag entgegen der Übergabe als EPUB ohne DRM vor. L-T3-21 wurde ohne Übergabe bereitgestellt; ID und Stufe hat die Code-Instanz analog L-T3-20 vergeben. Nachtrag 2026-09-29: Die Hörst-Neuauflage wird nicht beschafft (vorhandene 3. Aufl. gilt, Nachtrag D-51). | 2026-09-29 |
| D-71 | EPUB als Volltext (ergänzt D-26 und D-51): DRM-freie oder nur wasserzeichengeschützte EPUBs sind zulässig; Kindle und Adobe DRM bleiben ungeeignet. Ablage wie D-51 mit Endung .epub. Kapitel als Markdown in `<ID>_kapitel/` (`<ID>_<Kapitelnr>[-<Teil>]_<Kapiteltitel>.md`); Kapitel über ca. 12 000 Wörter an Abschnittsgrenzen in etwa gleich große Teile. Seitenangaben: Enthält das EPUB eine Seitenliste der Druckausgabe (page-list bzw. pagebreak-Marken), wird die Druckseite zitiert; die Marken stehen im Markdown als „[S. n]“ an der Stelle des Seitenwechsels. Sonst Kapitel und Abschnittsüberschrift. Zusätzlich je Kapiteldatei ein Ansichts-PDF gleichen Namens (aus dem EPUB gerendert, mit Abbildungen); es dient nur zum Betrachten der Abbildungen, seine Seitenzahlen werden nie zitiert. | Mehrere Titel sind nur als E-Book erhältlich; EPUB-Text ist ohne Texterkennung durchsuchbar. Reflowable EPUBs haben keine festen Seiten, daher die Zitierregel (Übergabe AP-06 Literatur-Nachsteuerung, dort D-65). Ansichts-PDF zusätzlich zum Markdown: Der Athlet hat beide Varianten zugelassen und die Abwägung der Code-Instanz überlassen (2026-09-29). Abbildungen tragen Inhalt (Diagramme in L-T3-19 Kap. 10, Übungsfotos in L-T3-10, -20, -21), der im Markdown nur als Bildunterschrift erscheint. L-T3-19 und L-T3-10 haben keine Seitenliste, L-T3-20 und L-T3-21 haben Seitenmarken (geprüft 2026-09-29). | 2026-09-29 |
| D-72 | Blockbilanz, Zielklärung und Revision sind Datensätze in der Datenbank (Tabelle `block_review`, AP-15, E-01): Master ist die Datenbank, zugänglich über MCP; `docs/plaene/` und das Projekt-Wissen sind nur Spiegel; `training_block.doc_ref` bleibt optional. | Die planende Instanz arbeitet über MCP; eine Repo-Datei veraltet. Entscheidung des Athleten. | 2026-09-29 |
| D-73 | Drei Ebenen mit getrennter Funktion (AP-15, E-02/E-03): Revision alle 3–4 Wochen (nur Belastungssteuerung), Blockbilanz am Blockende (Rückblick), Zielklärung vor jedem Block (Ausblick); getrennte Datensätze, typischerweise im selben Chat. Kein fester Kalendertakt: Blockgrenze plus Sicherheitsnetz (Zielklärung fällig, wenn die letzte bestätigte älter als 16 Wochen ist oder ein Trigger eintritt). | Rückblick und Ausblick beantworten verschiedene Fragen; Zielphasen laufen über Monate, die 3–4-Wochen-Revision bleibt für Deload/Schmerz/Ausfall (L-A03, L-P08). Entscheidung des Athleten. | 2026-09-29 |
| D-74 | Overlay-Erinnerung auf der Webseite (AP-15, E-05/E-14): Ist eine Bilanz oder Zielklärung fällig, zeigt jede Seite nach dem Login ein Overlay, das aktiv weggeklickt werden muss („Morgen wieder erinnern“ bzw. „Diese Woche nicht mehr“); es kommt wieder, bis die Fassung bestätigt ist; abschaltbar in S8; nicht während der geführten Einheit (S9, S10 aus S9), dann unmittelbar danach auf S2 (E-23, Entscheidung des Athleten 2026-09-29). Revisionen erinnern nicht per Overlay oder Kalender (E-07). | Wunsch des Athleten (Erinnerung über mehrere Tage). | 2026-09-29 |
| D-75 | Kalendertermin für Bilanz und Zielklärung (AP-15, E-06/E-15/E-22): je Block ein Termin am Blockende mit Uhrzeit (Standard 08:00–10:00, Zeitzone des Athleten) und Erinnerung am Vortag (24 h vorher); Beginn, Dauer und Vorlauf der Erinnerung in S8 einstellbar; gelöscht, sobald Bilanz und Zielklärung des Folgeblocks bestätigt sind. | Wunsch des Athleten; Einstellbarkeit sofort statt später (O-05). | 2026-09-29 |
| D-76 | Übergabe durch Code, nicht durch KI (AP-15, E-08): `get_handover` stellt bei Aufruf deterministisch zusammen (Block, Zielklärung, Bilanzen, Revisionen, Kennzahlen, Wochentexte, Fälligkeiten); Interpretationen stehen nur in den bestätigten Datensätzen; Kennzahlen friert der Server beim Schreiben ein (E-12). Kein Cron. | Eine KI-Zusammenfassung wäre teuer, unbestätigt und entkoppelt von der Bestätigung des Athleten. Entscheidung des Athleten. | 2026-09-29 |
| D-77 | Entwurf durch KI, Bestätigung im Chat (AP-15, E-09, wie D-11): `write_block_review` schreibt mit Status `entwurf` oder `bestaetigt`; nur bestätigte Fassungen zählen für Übergabe, Fälligkeit, Overlay und Kalender. Wochen nach dem Ende des aktiven Blocks lehnt `write_week_plan` ab, solange kein Folgeblock mit bestätigter Zielklärung existiert (E-19). | Wie Wochenplan und Athletenprofil. Entscheidung des Athleten. | 2026-09-29 |
| D-78 | Wochentexte in der Übergabe (AP-15, E-21, O-04): `get_handover` liefert `wochen_kurz` – die letzten 4 Wochen bis zur laufenden, je Woche eine Zeile „Wochenbeginn: Kurzsatz“. | Günstiger Kontext ohne zusätzlichen Aufruf von `get_week_overview`. Entscheidung des Athleten (Vorschlag Fable). | 2026-09-29 |
| D-79 | Neuer Bereich T4 Beweglichkeit/Mobilität (Tabelle 1.2) mit Literaturblock 13.2.6, IDs L-T4-nn, Wissenskarte `docs/wissen/t4-beweglichkeit.md` (Dateiname Vorschlag); 13.1 erlaubt dafür 4–7 Sammeldateien. Vorgaben des Athleten: Ziele ROM, Wohlbefinden und Verletzungsprävention; alle Hüftrichtungen gleichrangig; Methoden Dehnen (statisch, dynamisch, PNF), Mobility (Kräftigung in Endposition, CARs u. ä.), Faszienrolle, Yoga als Übungsfundus; Knie (Quadrizeps, Hamstrings) mit Verweis auf Block R; eigener Bereich, nicht Teilblock von T2. Teil A (Evidenz und Dosierung) – Kern L-T4-01 bis -06, -08, -10, -12, -14, -16, -17, -19, Lehrbuch L-T4-32 (Stufe B) und Übungskatalog L-T4-34 (Stufe C, Dosierung nur aus Stufe A) (Verweise -24 auf L-R-11, -33 auf L-T2-15); optional L-T4-07, -09, -11, -13, -15, -18, -20, -21, -23, -25 bis -31. Teil B (Übungsquellen) teilweise mit L-T4-34, Rest folgt als eigene Übergabe. Planungsfolgen (in AP-07 als Regeln auszuformulieren, nicht hier entschieden): (a) ROM chronisch mit statischem Dehnen oder PNF, dynamisch nur zur Vorbereitung; (b) Dosis-Richtwert ≥ 5 min je Zielmuskelgruppe und Woche auf möglichst viele Tage verteilt (≥ 2, besser ≥ 5), widersprüchlich belegt – keine harte Schwelle; (c) Kräftigung mit Zusatzlast über den vollen Bewegungsumfang ist gleichwertige ROM-Maßnahme, reine Körpergewichtsübungen zählen nicht als ROM-Maßnahme; (d) Dehnen im Aufwärmen optional, statisch < 60 s je Muskelgruppe mit anschließender dynamischer Aktivität, vor dem Laufen kein Verbot; (e) Dehnen nicht als Verletzungsprävention begründen, Prävention über Kraft und Balance (T2, Block R), Knie-Beweglichkeit nur als Einzelbefund (L-T4-23, optional); (f) Dehnen nicht zur Regeneration oder gegen Muskelkater einplanen; (g) ruhige Dehn-/Mobility-Einheiten als Wohlbefindens-Baustein mit Kennzeichnung „schwache Evidenz“; (h) Dehnen nicht zur Haltungskorrektur (D-54 a); (i) Hüftrichtungen einzeln planen, Übertragung der Dosis aus Hamstring-/Wadenstudien als Einschätzung kennzeichnen (D-13); (j) Klettern – Hüftbeweglichkeit bis funktionelles Niveau, Verlauf mit kletterspezifischen Tests (Einschätzung); (k) Blöcke ≤ 4 Wochen – Dehnen statt Faszienrolle für ROM. | Übergabe Literaturblock T4 Teil A (2026-09-29), alle Quellen per PubMed geprüft; vom Athleten am 2026-09-29 bestätigt und zur sofortigen Einarbeitung freigegeben (abweichend von der Übergabe, die die Einarbeitung erst nach Teil B vorsah). Zielzahl der Sammeldateien von 6 auf 7 erhöht, weil T4 ein eigener Bereich ist (bei ca. 13 Dateien schaltet das Projektwissen in den Retrieval-Modus, 13.1). Nachtrag 2026-09-29 (aktualisierte Übergabe): L-T4-32 in den Kern, L-T4-34 neu (K-8); L-T4-22 nicht aufgenommen (Entscheidung Athlet). Ergänzung D-80: L-T4-35 Freiwald (Stufe B, optional; K-8 für Freiwald revidiert) und L-T4-36 Schleip/Wilke (Stufe C, optional). | 2026-09-29 |
| D-80 | Lückenprüfung Standardwerke (englisch/deutsch) über alle Blöcke. Aufgenommen, alle optional, als Nachschlage- bzw. Hintergrundwerke, nie Regelquelle (D-22) und nie alleiniger Beleg für Belastungsparameter (D-31): L-R-29 Brukner & Khan, Managing Injuries, 6. Aufl. (B); L-R-30 GOTS-Manual, 4. Aufl. (B); L-T2-33 McGill, Low Back Disorders, 4. Aufl. (B); L-T1-16 Koop, Training Essentials for Ultrarunning, 2. Aufl. (C); L-T4-35 Freiwald, Optimales Dehnen, 3. Aufl. (B; revidiert K-8 für Freiwald); L-T4-36 Schleip/Wilke, Fascia in Sport and Movement, 2. Aufl. (C). Beschaffung in 13.4 mit Priorität 2. 13.3 um sieben geprüfte Werke ergänzt. Befunde – kein Standardwerk zum Skibergsteigen-Training; kein aktuelles deutsches Sportphysiologie-Lehrbuch; Kettlebell und Band über L-A03 Kap. 17 abgedeckt. | Block R hatte kein Lehrbuch; Teilblock Rücken kannte nur McGills Praxisbücher (L-T2-13, C); Trailrunning hatte nur eine Praxisquelle (L-T1-08); T4 hatte kein deutschsprachiges Werk. Bestätigt durch Athlet (Übergabe Lückenprüfung Standardwerke, SW-E1 bis SW-E6). Nachtrag 2026-09-29 (Entscheidung Athlet, Abschluss der Literatursuche): L-T4-35 Freiwald nicht aufgenommen (nicht beschaffbar); Q-22 entschieden (Band 1 nicht aufnehmen); L-T3-15 Anderson als Stufe C optional aufgenommen; L-R-29 in der vorhandenen 5. Aufl. (Band 1 Injuries, 2017) statt 6. Aufl. (Nachtrag D-51). | 2026-09-29 |

# 5. Offene Fragen und Verifikationen

## 5.1 Offene Entscheidungen

| id | frage | empfehlung | status |
|---|---|---|---|
| Q-01 | Tägliches subjektives Check-in erheben? Welche Felder? Wo? | Ja, minimal: `erholung_1_5`, `muskelkater_1_5`, `schmerz_ja_nein` (bei ja → Schmerzereignis). Auf der Webseite, integriert in Wochenansicht, ≤ 10 s. Keine separate Schlafqualität (Garmin liefert Schlaf; fließt in Erholung ein). Begründung: subjektive Marker sind sensitiver als objektive (Saw/Main/Gastin 2016, verifiziert als L-P11); Schmerz hat keinen objektiven Ersatz. Fehlende Einträge gelten als fehlend, nicht als beschwerdefrei; MCP meldet Abdeckungsquote. | entschieden → D-16 |
| Q-02 | Feedback zu Ausdauereinheiten zusätzlich zurück nach Intervals.icu schreiben (RPE/Feel/Kommentar auf der Aktivität)? | Optional in AP-09; Nutzen: Intervals.icu-Charts vollständig. Kosten: Feld-Semantik abgleichen (V-03). | entschieden → D-46 (2026-09-28) |
| Q-03 | Sichtbarkeit der Aktivitäten in Intervals.icu | Auf privat stellen. | offen |
| Q-04 | Repo-Name und Lizenz | Repo `chodid/training`, privat, keine Lizenz. | entschieden (2026-09-27) |
| Q-05 | Login-Verfahren Webseite: Passwort oder Passkey (WebAuthn) | Passwort + lange Session (30 Tage, eigene Tabelle `web_session`) in Phase 1; Passkey optional AP-09. | entschieden → D-33 (2026-09-27) |
| Q-06 | Welche Literatur ist bereits vorhanden (PDF/ePub/Print)? | Antwort: keine. Auswahl, Priorisierung und Beschaffung vollständig in AP-06; Kandidatenliste 13.2 ist Ausgangspunkt, nicht Vorgabe. | beantwortet |
| Q-07 | Einheitliches Evidenzschema über alle Blöcke: Block T3 schlägt Stufen A/B/C vor (E3) und will Stufe C ohne Begründungsfunktion (E4); Block T1 führt Praxisquellen mit `konfidenz: niedrig` in Karten (D-25); Block T2 nutzt eine Stufe-C-Quelle als Übungskatalog mit Dosierung aus Stufe A (D-29). | Vereinheitlichen als D-31: A = Paper/Konsens (konfidenz hoch), B = wissenschaftliche Lehrbücher (mittel), C = Praxisquellen (niedrig). Stufe C darf in Karten als Übungs-/Ideenfundus und mit Kennzeichnung zitiert werden, aber nie allein einen Belastungsparameter (Dosierung, Progression, Schwelle) begründen. Damit sind D-25, D-29 und E4 deckungsgleich. Ebenso E5: Open-Access-Volltexte (nur CC BY) dürfen im privaten Repo unter `docs/literatur/` liegen, nie im Projektwissen (D-12, Budget 13.1). E6 (Evidenzkern T3) übernehmen. | entschieden → D-31 |
| Q-08 | Klettermedizin: deutsche (L-T3-07, 2020) oder englische Ausgabe (L-T3-06, 2022)? Nur eine wird beschafft. | Englische Ausgabe (neuer, ISBN/DOI verifiziert, Springer-Kapitel-PDFs); deutsche nur, wenn Sprache im Alltag wichtiger ist als Aktualität. | entschieden → D-31: 2022 bevorzugt, 2020 als Alternative |
| Q-09 | Laufzeit der Refresh-Tokens (D-32 legt keine fest). Jede Rotation beginnt die Laufzeit neu; nach Ablauf muss der Connector in Claude neu verbunden werden (Login + Freigabe). | 90 Tage: bei regelmäßiger Nutzung (wöchentlicher Zyklus) nie ein erneuter Login, ein verlorenes Gerät verliert den Zugang spätestens nach 90 Tagen ohne Nutzung. Kürzer (30 Tage) nur, wenn Pausen > 30 Tage einen neuen Login rechtfertigen. | entschieden → D-38 (2026-09-28) |
| Q-10 | `plan_json` für `mobilitaet` und `ruhe` (Abschnitt 7.1 definiert nur kraft/haltung, klettern, ausdauer). | `mobilitaet` wie kraft/haltung (Übungsliste mit Sätzen/Wiederholungen bzw. Haltezeit „30s“); `ruhe` ohne Plan, höchstens Notiz. Passt zu den Mockups (S3) und hält die Webseite einfach. | entschieden → D-39 (2026-09-28) |
| Q-11 | Neues MCP-Tool `upsert_block` (Block anlegen/ändern: Name, Zeitraum, Status, Zielevents, Phasen, Verweis auf docs/plaene/). Abschnitt 8.2 sieht kein Tool dafür vor, `write_week_plan` braucht aber einen Block. | Tool aufnehmen (so umgesetzt): Blockplan entsteht im Projekt-Chat (AP-08), Claude legt ihn nach Bestätigung per Tool an. Alternative: Block per SQL/Webseite anlegen. | entschieden → D-40 (2026-09-28) |
| Q-12 | Sportart für Intervals.icu-Events: optionales Feld `sport` in `plan_json.ausdauer` (Run, TrailRun, Hike, Walk, Ride, MountainBikeRide, GravelRide, BackcountrySki, NordicSki, Snowshoe, Swim, Rowing; Standard Run). | Aufnehmen (so umgesetzt): Skitour und Wandern brauchen eigene Typen, damit Garmin das richtige Sportprofil (und die Zonen, V-12) nutzt. | entschieden → D-41 (2026-09-28) |
| Q-13 | Schmerzregeln 14.5 (≤ 3/10 fortfahren; 4–5/10 reduzieren; > 5/10 stoppen) sind strenger als das Schmerzmonitoring-Modell nach L-P13 (laut Sekundärquelle ≤ 5/10 zulässig, Abklingen bis Folgemorgen, keine Zunahme von Woche zu Woche). Beibehalten, an das Modell angleichen oder je Struktur unterscheiden (Sehnen untere Extremität vs. Finger/Ringbänder)? Zusätzlich: Schmerzregel aus L-R-02 (Kongsgaard 2009, laut Reha-Chat VAS ≤ 30/100 während Belastung) – nicht im Abstract; am Volltext verifizieren und mit 14.5 und L-P13 abgleichen (Ergänzung Teil C). | Entscheidung in AP-07 nach Volltextprüfung L-P13. Für Finger/Ringbänder strengere Schwellen beibehalten, da das Modell dort nicht validiert ist (Einschätzung); für Sehnen der unteren Extremität Angleichung an das Modell prüfen. (Literatur-Sitzung AP-06 Teil B; dort als Q-09 vergeben, wegen Kollision umnummeriert.) | offen (AP-07) |
| Q-14 | Welche Lama-Variante als App-Icon, Favicon und App-Kennung (Topbar, Navigation, Login)? Mockup `docs/branding/mockups/icon-optionen.html` mit V1 Linie auf Papier, V2 Fläche auf Papier, V3 Fläche hell auf Pflaume 600, V4 Linie hell auf Pflaume 800, V5 Kopf (bisher). | Empfehlung Fable: V3 als App-Icon (Android/iOS, maskable), V2 als Favicon 16/32 px und App-Kennung. Mischung oder eine Variante überall möglich. AP-13 Teil A wartet auf die Wahl. | entschieden → D-59 (2026-09-28) |
| Q-15 | Laufprogression als Regel je Einheit statt als Wochenprozent? Vorschlag laut L-R-24 – Einzellauf höchstens 10 % länger als der längste Lauf der letzten 30 Tage; Wochenumfang nur noch als grobe Leitplanke. Automatische Prüfung aus Intervals.icu-/Garmin-Daten in der WebApp (Hinweis bei Planung oder nach Import)? | Regel in AP-07 übernehmen (mit Kennzeichnung „explorativer Kohortenbefund“); automatische Prüfung als Kandidat für AP-09 bzw. eigenes Arbeitspaket, Entscheidung durch Athlet. Einschätzung: Garmin-Streckendaten liegen bereits vor, Aufwand gering. (Teil C: Q-10) | offen (AP-07 Regel, AP-09 Automatik) |
| Q-16 | Orthese oder Tape beim Trailrunning (und ggf. weiteren Sportarten mit Umknickrisiko) als Rezidivschutz zusätzlich zum Balancetraining? | In AP-07 als Option aufnehmen; L-R-26 bewertet die Evidenz für Orthesen zur Rezidivprophylaxe als stark. Entscheidung durch Athlet; Details (Orthesentyp, Einsatzbereich) am Volltext L-R-13/L-R-26 prüfen. (Teil C: Q-11) | offen (AP-07) |
| Q-17 | Bereichskennung und ID-Präfix für Beweglichkeit/Mobilität | T4 „Beweglichkeit/Mobilität“ in Tabelle 1.2, IDs `L-T4-nn` | entschieden → D-79 (2026-09-29) |
| Q-18 | Wie wird T4 im Wochenplan geführt: eigene Einheiten (Typ `mobilitaet`, D-39) oder als Block in Kraft-/Klettereinheiten? | Beides zulassen; Entscheidung in AP-07 | offen |
| Q-19 | Dehnintensität in Plan/Rückmeldung erfassen? | Nur subjektiv (z. B. Hinweis „deutlich spürbar, nicht schmerzhaft“), kein neues Pflichtfeld (L-T4-30) | offen |
| Q-20 | Verlaufsmessung Hüft-ROM (AP-08) | Tests aus L-T4-26 prüfen, ergänzt um einfache Heimtests; eigene Recherche nötig | offen |
| Q-21 | Repo öffentlich – Umgang mit Volltexten unter `docs/literatur/` (gekaufte bzw. nicht frei lizenzierte Werke; D-23, D-31 setzen ein privates Repo voraus) | Repo wieder privat **oder** Volltexte in ein privates Repo bzw. lokal auslagern und aus der Git-Historie entfernen (Löschen im aktuellen Stand genügt nicht); danach Konzeptkopf, D-23, Q-04 angleichen | entschieden 2026-09-29 (Athlet, Übergabe T4 K-9): Repo wird wieder privat, sobald für die Recherche kein Zugriff mehr nötig ist; bis dahin vorübergehend öffentlich. D-23 und D-31 gelten unverändert. Kopien aus der öffentlichen Zeit lassen sich nicht zurückholen |
| Q-22 | Brukner & Khan, 6. Aufl., Band 1 „Foundations of Clinical Practice“ (2025, ISBN 9781761000010) zusätzlich aufnehmen? Laut Autorenseite u. a. Kapitel zu Trainingsprinzipien, Belastungssteuerung, Erholung, Verletzungsprävention und Schmerz. Optionen (a) aufnehmen als L-R-31, optional, Stufe B; (b) 13.3 | (b) – Belastungssteuerung, Erholung und Schmerz sind über Stufe-A-Quellen abgedeckt (L-P03 bis L-P05, L-P12, L-P13); Einschätzung | entschieden 2026-09-29 (Athlet): (b) – nicht aufnehmen, 13.3 |

## 5.2 Zu verifizieren (vor/in dem jeweiligen AP)

| id | verifikation | ap | status |
|---|---|---|---|
| V-01 | Intervals.icu-Workout-Textsyntax für strukturierte Ausdauer-Einheiten (Schritte mit HF-Zone bzw. Pace-Ziel); Verhalten beim Push auf die konkrete Uhr; Einschränkung „mehrere Zieltypen pro Schritt". Design-Regel vorläufig: ein Zieltyp pro Schritt. | AP-02 | offen |
| V-02 | Zeitpunkt/Umfang des Intervals.icu→Garmin-Pushes (Vorschau eine Woche; wann muss der Plan spätestens geschrieben sein). | AP-02 | offen |
| V-03 | Semantik der Intervals.icu-Felder `icu_rpe` (Skala) und `feel` (Richtung der 1–5-Skala) sowie verfügbare Wellness-Felder für dieses Konto über die API. | AP-02 | offen |
| V-04 | Endpunkte/Parameter für Events (GET/POST/PUT/DELETE), Aktivitäten (Zeitraum), Wellness (Zeitraum) anhand der aktuellen API-Dokumentation. Stand 2026-09-27 (vorläufig, Sekundärquelle Client-Quellcode, da intervals.icu aus der Code-Umgebung gesperrt): Basis `https://intervals.icu/api/v1`, Basic-Auth `API_KEY:<key>`, `/athlete/{id}/events` (GET mit `oldest`/`newest`, POST), `/athlete/{id}/events/{eventId}` (PUT, DELETE), `/athlete/{id}/activities` und `/athlete/{id}/wellness` (GET mit `oldest`/`newest`); Event-Felder `category` WORKOUT, `type`, `name`, `start_date_local`, `description`, `external_id`. Bestätigung über `/intervals` auf dem Server. Stand 2026-09-28: auf dem Server mit echtem Konto bestätigt für Aktivitäten und Wellness (GET) sowie Event anlegen (POST, Workout-Text korrekt als strukturiertes Workout übernommen); PUT/DELETE und Rückschreiben (Aktivität) offen. | AP-02 | in Arbeit |
| V-05 | OAuth-Flow claude.ai (Web und Mobile) gegen PHP-Server: DCR, Callback-URLs (`claude.ai/api/mcp/auth_callback`, ggf. `claude.com/...`), Token-Refresh. Stand 2026-09-27: Ablauf lokal automatisiert geprüft (DCR, Authorize, PKCE, Token, Refresh-Rotation, `/mcp` mit JWT) und mit dem SDK-Client in beiden Protokoll-Epochen; beide Callback-Hosts sind als https-URIs zulässig. Stand 2026-09-28: Connector in claude.ai (Web) verbunden und freigegeben, Tool-Aufruf funktioniert; Mobile-App und Token-Refresh nach > 1 h offen. | AP-01 | in Arbeit |
| V-06 | Referenz Saw AE, Main LC, Gastin PB. Monitoring the athlete training response: subjective self-reported measures trump commonly used objective measures. Br J Sports Med 2016 – DOI und Kernaussage über PubMed-Connector prüfen. | AP-06 | erledigt 2026-09-28 (L-P11 per PubMed verifiziert, Kernaussage bestätigt) |
| V-07 | Schmerzmonitoring-Modell für Sehnenbelastung (Silbernagel/Thomeé 2007) als Grundlage der Schmerzregeln – Quelle und Schwellenwerte prüfen. | AP-06 (Literatur), AP-07 (Schwellen) | teilweise 2026-09-28: Bibliografie L-P13 und Modellherkunft (Thomeé 1997) verifiziert; Schwellenwerte nur aus Sekundärquelle, Abweichung zu 14.5 → Q-13; Rest: Schwellen am Original prüfen (Volltext L-P13 liegt seit 2026-09-28 vor) |
| V-08 | PHP-Version auf dem Hosting vs. Anforderungen des SDK; Composer-Verfügbarkeit. Ergebnis: Test auf dem Server 2026-09-27: PHP 8.4.25, Apache 2.4, Erweiterungen curl, json, openssl, pdo_mysql, zlib, mbstring aktiv; logiscape/mcp-sdk-php v2.0.1 verlangt PHP ≥ 8.1, ext-curl, ext-json (Packagist, 2026-09-27). Composer auf dem Server nicht nötig (Build in GitHub Actions, D-17). Health-Endpunkt prüft die Erweiterungen laufend. | AP-00 | erledigt 2026-09-27 |
| V-09 | Verhalten von Garmin-Kraftaktivitäten (auf der Uhr gestartet) in Intervals.icu: Typ, Dauer, HF – für heuristisches Matching mit Webseiten-Einheiten. | AP-02 | offen |
| V-10 | Auf dem Hosting verfügbar: FTPS oder SFTP für den Deploy-Workflow; PHP-CLI für „Geplante Aufgaben" (sonst HTTP-Aufruf eines geschützten Endpunkts); E-Mail-Versand aus PHP mit Anhang (SMTP über Mailkonto des Hostings bevorzugt, Größenlimit des Anhangs); PHP-OpenSSL-Erweiterung aktiv. Ergebnis (Angaben Athlet 2026-09-27): FTPS vorhanden (Port 21, explizit, gültiges Zertifikat); SMTP vorhanden; keine PHP-CLI-Aufgaben, aber zeitgesteuerter Aufruf von URLs → E-Mail-Backup über geschützten Endpunkt (D-18 b); OpenSSL aktiv (Servertest). Servertest zusätzlich: `open_basedir` leer, Datei oberhalb des Docroots lesbar, `.htaccess` wird ausgewertet (`Require all denied` → 403). Rest: Anhang-Größenlimit SMTP → Testversand in AP-10. | AP-00 | erledigt 2026-09-27 (bis auf Anhang-Limit) |
| V-11 | Bibliografische Prüfung der T1-Quellen (Autoren, Jahr, Band/Seiten, DOI/ISBN, freie Verfügbarkeit) per PubMed-Connector bzw. Bibliothekskataloge. Ergebnis in 13.2.2 (Felder `zugang`, `verifikation`). Hinweis Lizenz: PubMed liefert keinen Lizenztyp; „frei" heißt Volltext in PMC; CC BY 4.0 nur für L-T1-04 belegt. | AP-06 | erledigt 2026-09-27 |
| V-12 | Zonendefinition in Garmin Connect (Laufprofil, ggf. eigenes Profil Skitour) und in Intervals.icu identisch halten (%LTHR, gleiche Grenzen), damit HF-Ziele aus Intervals.icu-Workouts auf der Uhr dieselbe Zone treffen. Prüfen, ob Intervals.icu Zonen nach Garmin überträgt oder beide getrennt gepflegt werden müssen (D-27). | AP-02 (mit V-01), AP-08 | offen |
| V-13 | Format und Kopierschutz je Titel vor Beschaffung (D-26, D-71): Human-Kinetics-Titel (L-A01, L-A03, L-T1-07, L-T2-05, L-T2-07) laufen über VitalSource mit DRM → Print oder anderer Anbieter; Springer-Titel (L-A02, L-T2-03, L-T2-06, L-T3-06/07) kapitelweise als PDF über SpringerLink bzw. Bibliothekszugang. Stand 2026-09-29: vorhanden L-A01 (7. Aufl., gilt – Nachtrag D-51), L-A02, L-A03, L-T1-07, L-T1-08 (Scan), L-T2-03, L-T2-04 (Scan, Texterkennung fehlerhaft), L-T3-06, L-T3-09 (3. Aufl., Scan), L-T3-10, L-T3-19, L-T3-20, L-T3-21 (je EPUB ohne DRM). Entfallen: L-T1-01 (D-70); L-T3-08 zurückgestellt (keine digitale Ausgabe). Offen: keine – L-A01 bleibt 7. Aufl., L-T3-09 3. Aufl. (Nachtrag D-51); L-T3-16 2. Aufl. als PDF vorhanden (2026-09-29). | AP-06 | teilweise |
| V-14 | PubMed-Verifikation Kraftliteratur: Rønnestad & Mujika 2014 (Scand J Med Sci Sports) und Blagrove et al. 2018 (Sports Med) → L-T2-11, L-T2-12. ACSM 2026 und Schumann 2022 bereits verifiziert als L-P08 und L-P07. | AP-06 | erledigt 2026-09-28 (L-T2-11, L-T2-12 verifiziert) |
| V-15 | Bibliografische Vervollständigung T3: L-T3-03 (Band, Lizenz – erledigt 2026-09-28: Bd. 5, Art. 1130812, CC BY laut Volltext), L-T3-04 (Band, Seiten, DOI, Zugang), L-T3-05 (Titel, Journal, Band, Seiten, DOI), L-T3-07 (ISBN), L-T3-08 (aktuelle Auflage/ISBN), L-T3-09 (Jahr), L-T3-12 (Jahr, Auflage, ISBN); Kernaussagen L-T3-02 am Original statt Sekundärzitat prüfen (Original liegt seit 2026-09-28 vor). | AP-06 | weitgehend erledigt 2026-09-28/29: L-T3-03, -04, -05, -07, -09 verifiziert; L-T3-02 Kernaussagen am Abstract korrigiert; L-T3-08 aktuelle Auflage 2026-09-29 geklärt (9. überarb. Aufl. 2019, ISBN 978-3-945271-41-4, Händlerangaben). Rest: L-T3-02 Wiederholungsbereiche am Volltext, L-T3-12 Auflage |
| V-16 | Redundanz der Hypertrophie-Ergänzung zu L-P08: Am Volltext von L-P08 (eingeschlossene Reviews) prüfen, ob L-T2-20, L-T2-22 und L-T2-23 dort enthalten sind. L-T2-20 erschien online 12/2025, L-P08 im Heft 58(4) 2026. Sind sie enthalten, zitiert die Karte L-P08 als Anker und die Einzelarbeiten nur für Zahlen und Dosis-Wirkung; sonst jeweils eine eigene Kernaussage. Zusätzlich das Corrigendum zu L-T2-23 sichten (Inhalt nicht geprüft). | AP-06 | offen; Corrigendum L-T2-23 gesichtet 2026-09-29 (Abb. 4, Hauptbefunde unverändert) |
| V-17 | Seitenbezug L-T3-19 und L-T3-10: Beide EPUBs haben keine Seitenliste (D-71). Bei L-T3-19 deuten die Bilddateinamen (a003 … a179) auf Druckseiten hin – nicht verifiziert. Optional an Druckausgabe oder Leseprobe prüfen; bis dahin Zitat nur mit Kapitel und Abschnitt. Bilddateinamen und Seitenzahlen der Ansichts-PDFs nie als Seitenangabe verwenden. | AP-06 | offen |
| V-18 | Praxisempfehlungen und Dosierung im Delphi-Konsens L-T4-01 | Volltext PMC12305623 lesen, Aussagen mit Seitenangabe in Karte | AP-06 | offen |
| V-19 | Thomas 2018 (L-T4-05): Bezug der 5 min (je Muskelgruppe oder gesamt) | Volltext | AP-06 | offen |
| V-20 | Lizenzen der PMC-Volltexte T4 (nur L-T4-07 und L-T4-11 als CC BY 4.0 ausgewiesen) | Verlagsseite je Titel; relevant für Ablage im Repo (Q-21) | AP-06 | teilweise 2026-09-29: laut Volltext L-T4-01, -02, -08 CC BY-NC-ND 4.0, L-T4-03 CC BY 4.0; L-T4-04, -10, -12 ohne Angabe im Text |
| V-21 | Behm 2025 (L-T4-32) und Nelson/Kokkonen (L-T4-34): E-Book-Format/DRM | vor Kauf (D-26, V-13, D-71); bei Nelson/Kokkonen zuerst die PDF-Ausgabe | AP-06 | erledigt 2026-09-29: beide als durchsuchbares PDF vorhanden, L-T4-34 zusätzlich als EPUB ohne DRM |
| V-22 | Winters 2004 (L-T4-19): DOI | Verlagsseite | AP-06 | offen – auch im Volltext keine DOI |
| V-23 | Oba 2026 (L-T4-03): Artikelnummer | beim Abruf | AP-06 | erledigt 2026-09-29: 12:95 (Volltext) |
| V-24 | Formatprüfung (D-26, D-71, V-13) für L-R-29, L-R-30, L-T2-33, L-T1-16, L-T4-35, L-T4-36; beim Erwerb zusätzlich Inhaltsverzeichnis L-R-29 (Kapitel Patellatendinopathie und Sprunggelenk in der 6. Aufl.) und ISBN der 3. Aufl. L-T4-35 bestätigen | vor Kauf | AP-06 | teilweise 2026-09-29: L-T1-16 (EPUB ohne DRM), L-T2-33 und L-R-30 (je 3. Aufl. als PDF), L-T4-36 (Verlags-PDF) vorhanden; offen L-R-29, L-T4-35 |

# 6. Betriebsablauf (Wochenzyklus)

1. **Blockplan** (8–16 Wochen): Phasen, Prioritäten je Bereich T1–T4, Zielevents, Begründung mit Quellen. Erarbeitet im Projekt-Chat, vom Athleten bestätigt, begründet durch die Zielklärung in der Datenbank (AP-15, D-72); `docs/plaene/` und das Projekt-Wissen sind nur Spiegel.
2. **Wochenplanung** (Chat, typischerweise Sonntag): Claude ruft **zuerst `get_handover`** (AP-15, D-76), dann `get_week_overview` (Vorwoche), `get_wellness_trend`, `get_pain_history`, `get_athlete_profile`; liest Trainerregeln; meldet Fälligkeiten (Bilanz, Zielklärung, Revision) dem Athleten, bevor ein Wochenvorschlag mit Begründung im Chat entsteht.
3. **Bestätigung**: Athlet bestätigt oder ändert im Chat (D-11).
4. **Schreiben**: Claude ruft `write_week_plan`. Server legt Einheiten in MySQL an; Ausdauereinheiten zusätzlich als Events in Intervals.icu (Workout-Syntax); Event-IDs werden gespeichert; Audit-Log-Eintrag.
5. **Sync**: Intervals.icu überträgt Ausdauer-Workouts an Garmin Connect; nach Sync der Uhr sind sie dort sichtbar.
6. **Ausführung**: Ausdauer über die Uhr; Kraft/Klettern/Haltung über Webseite (Einheit öffnen, Ist-Werte eintragen).
7. **Feedback**: Nach jeder Einheit auf der Webseite (RPE, Feel, Schmerz, Abweichung, Notiz); täglich Check-in (D-16).
8. **Rückkopplung**: nächster Chat → Schritt 2. **Revision** alle 3–4 Wochen oder bei Schmerzereignis/Ausfall (Trigger in Abschnitt 14): Ergebnis als `write_block_review(kind: revision)` (AP-15, D-73).
9. **Blockwechsel** (ab Fälligkeit, spätestens am Blockende; AP-15, D-73/D-77): (a) Bilanz im Chat erarbeiten – Claude ruft `get_handover(detail: true)` und `get_block_reviews`, schlägt die Bewertung je Ziel vor, Athlet bestätigt → `write_block_review(kind: bilanz, status: bestaetigt)`; (b) Zielklärung – Fragen aus dem Schema (`docs/konzept/blockbilanz.md` 4.3) der Reihe nach, Entscheidungen mit verworfenen Alternativen protokollieren, Athlet bestätigt; (c) `upsert_block` für den Folgeblock (Status `geplant`), dann `write_block_review(kind: zielklaerung)` für diesen Block; (d) Athletenprofil abgleichen (`update_athlete_profile`, wenn sich die Ausgangslage geändert hat); (e) erste Woche nach Schritt 2–4. Wochen nach dem Blockende lehnt `write_week_plan` bis dahin ab.

Ad-hoc-Anpassung unter der Woche: Athlet meldet sich im Chat; Claude ruft `get_week_overview` (laufende Woche) und schreibt Änderungen per `update_session` nach Bestätigung.

# 7. Datenmodell (Entitäten, konzeptionell)

Feldtypen sind konzeptionell. Die konkreten Migrationen entstehen in zwei Schritten: Benutzer-, Session- und OAuth-Tabellen in AP-01 (D-35), Trainingstabellen in AP-03. Umgesetztes Schema mit ER-Diagramm: `docs/konzept/datenmodell.md`.

| entitaet | felder (auszug) | bemerkung |
|---|---|---|
| `user` | id, login, password_hash, tz, failed_logins, locked_until(null), created_at | genau ein Datensatz; Anlage über `/setup` (D-34); Sperre nach 10 Fehlversuchen, 5 min verdoppelnd bis 24 h, Reset bei Erfolg (D-33); AP-01 |
| `web_session` | token_hash, user_id, csrf_secret, created_at, last_seen_at, expires_at | 30-Tage-Session der Webseite (D-33); Token nur gehasht; AP-01 |
| `training_block` | id, name, start_date, end_date, goal_events_json, phase_notes, status(`geplant`,`aktiv`,`abgeschlossen`), doc_ref | doc_ref → docs/plaene/ |
| `training_week` | id, block_id, week_start(Mo), focus, coach_notes, status(`entwurf`,`bestaetigt`,`abgeschlossen`), created_by(`mcp`,`web`), created_at | `focus` = Kurzsatz der Woche, `coach_notes` = ausführliche Begründung (D-56) |
| `session` | id, week_id, date, type(`ausdauer`,`kraft`,`klettern`,`haltung`,`mobilitaet`,`ruhe`), title, priority(`A`,`B`,`C`), planned_duration_min, intervals_event_id(null), plan_json, coach_summary (D-56, AP-13), coach_rationale, status(`geplant`,`erledigt`,`teilweise`,`ausgelassen`,`verschoben`), sort_order | plan_json-Schema in 7.1; `coach_summary` Kurzsatz ≤ 200 Zeichen, `coach_rationale` ausführlich ≤ 1 500 Zeichen (D-56) |
| `session_execution` | id, session_id, performed_at, duration_min, actual_json, rpe_cr10(0–10), srpe_load(=rpe×min, berechnet), feel_1_5, deviation_reason(`zeit`,`ermuedung`,`schmerz`,`wetter`,`sonstiges`,null), notes, source(`web`,`intervals`) | genau eine pro Session |
| `pain_event` | id, date, session_id(null), location(enum 7.2), side(`L`,`R`,`beide`,`na`), intensity_0_10, timing(`waehrend`,`danach`,`naechster_morgen`,`ruhe`), notes | mehrere pro Tag möglich |
| `checkin` | id, date(unique), recovery_1_5, soreness_1_5, pain_flag(bool), notes; Morgen-Check-in: mt_links, mt_rechts, nacken_bws, hand_rechts (0–10, null = nicht erhoben), osg_umgeknickt, osg_schwellung (bool), warnzeichen (Liste) | D-16, D-53 |
| `oauth_client` | client_id, client_name, redirect_uris_json, created_at, last_used_at | offene DCR (D-36); Redirect-URIs nur https bzw. localhost; AP-01 |
| `oauth_auth_code` | code_hash, client_id, user_id, code_challenge, method(`S256`), redirect_uri, scope, expires_at, used | PKCE S256 Pflicht; 10 min, einmalig (D-36); AP-01 |
| `oauth_token` | token_hash, type(`refresh`; `access` reserviert), client_id, user_id, family_id, scope, expires_at, used_at(null), revoked | Hält nur Refresh-Tokens, gehasht; Access-Tokens sind JWT ohne DB-Eintrag (D-32); `family_id` für Rotation und Familien-Widerruf; AP-01 |
| `audit_log` | id, ts, actor(`mcp`,`web`,`cron`), action, entity, entity_id, payload_hash, summary | alle Schreibzugriffe |
| `ext_cache` (optional) | cache_key, payload_json, fetched_at | Kurzcache Intervals.icu (z. B. 5 min) |
| `ext_activity`, `ext_wellness` | id bzw. date, Kernfelder, data_json, updated_at | Spiegel Intervals.icu (D-43); AP-09 |
| `webauthn_credential` | id, user_id, name, public_key, sign_count, created_at, last_used_at | Passkeys (D-44); AP-09 |
| `athlete_profile` | id, section(`ziele`,`zeitbudget`,`ausruestung`,`einschraenkungen`,`leistungswerte`,`sonstiges`), content (Markdown), reason, created_by(`mcp`,`web`), created_at | Athletenprofil mit Fassungen, jüngste je Abschnitt gilt (D-48); AP-09 |
| `exercise` | id, slug (eindeutig, unveränderlich), name, name_norm (eindeutig), category(`kraft`,`haltung`,`mobilitaet`,`hangboard`,`campus`,`zugkraft`,`antagonisten`), pattern (13 Bewegungsmuster), equipment_json, variant_of(null), difficulty(1–5, null), status(`aktiv`,`links_pruefen`,`archiviert`), konfidenz(`hoch`,`mittel`,`niedrig`,`einschaetzung`), content_json (Schema `exercise.json`), version, created_by, created_at, updated_at | Übungskatalog (D-64 bis D-69); keine Löschfunktion; AP-16 |
| `exercise_alias` | exercise_id, alias, alias_norm (eindeutig über alle Übungen) | Duplikatschutz und Suche (E-09); AP-16 |
| `exercise_version` | id, exercise_id, version, snapshot_json, reason, created_by, created_at | Schnappschuss vor jeder Änderung (E-07); AP-16 |
| `block_review` | id, block_id, kind(`revision`,`bilanz`,`zielklaerung`), sequence, version, status(`entwurf`,`bestaetigt`), review_date, period_start, period_end, summary, content_json (Schema je Art, `server/schemas/review-<kind>.json`), kennzahlen_auto (vom Server), reason, created_by(`mcp`,`web`), created_at, confirmed_at | Revision, Blockbilanz, Zielklärung mit Fassungen (D-72); gültig je (block_id, kind, sequence) die jüngste bestätigte Fassung; Bilanz/Zielklärung nur sequence 1; Block mit Reviews nicht löschbar; AP-15 |
| `app_setting` | setting_key, value, updated_at | Einstellungen (D-52): `calendar_reminder`, `checkin_hand_rechts_bis`, `timer_ton`, Fassungen `kalender_tag_<Datum>`; AP-15: `bilanz_vorlauf_tage` (7), `zielklaerung_vorlauf_tage` (14), `review_overlay` (an/aus), `erinnerung_<kind>_<block_id>` (Tag, ab dem das Overlay wieder erscheint), `kalender_block_beginn` (08:00), `kalender_block_dauer_min` (120), `kalender_block_erinnerung_h` (24), Fassung `kalender_block_<id>` |

## 7.1 `plan_json` / `actual_json` (Schema je Typ)

```yaml
kraft_oder_haltung:
  exercises:
    - name: string
      exercise_id: string|null   # Slug im Übungskatalog (D-64, AP-16); fehlt = Freitext mit Warnung (D-66)
      sets: int
      reps: string        # z. B. "8" oder "6-8" oder "30s"
      load: string        # z. B. "20 kg", "KG", "Band grün"
      tempo: string|null
      rest_s: int|null
      notes: string|null
klettern:
  blocks:
    - kind: enum [hangboard, campus, bouldern_volumen, bouldern_limit, ausdauer_route, technik, zugkraft, antagonisten]
      exercise_id: string|null     # nur bei hangboard, campus, zugkraft, antagonisten (D-68); sonst Schemafehler
      spezifitaet: enum|null [spezifisch, halbspezifisch, unspezifisch]   # nach L-T3-02; zugkraft umfasst auch Körpergewicht-Zugübungen (D-30)
      # hangboard-spezifisch:
      edge_mm: int|null
      grip: enum|null [halbkrimp, offen, vollkrimp, zange]
      hang_s: int|null
      rest_s: int|null
      sets: int|null
      added_load_kg: number|null   # negativ = Entlastung
      # allgemein:
      duration_min: int|null
      target: string|null           # z. B. "Grad 5-6, 20 Boulder"
      notes: string|null
ausdauer:
  intervals_workout_text: string   # Intervals.icu-Syntax (V-01)
  sport: enum|null [Run, TrailRun, Hike, Walk, Ride, MountainBikeRide, GravelRide, BackcountrySki, NordicSki, Snowshoe, Swim, Rowing]  # D-41, Standard Run
  target_type: enum [hf_zone, pace, rpe]
  summary: string                  # Klartext für Wochenansicht
```

`actual_json` spiegelt die Struktur von `plan_json` mit Ist-Werten; leere Felder = wie geplant. `actual_json` trägt kein `exercise_id` (Zuordnung über die Position).

Zuordnung der übrigen Typen (D-39): `mobilitaet` nutzt das Schema `kraft_oder_haltung`; `ruhe` hat kein `plan_json` (leer oder nur `notes`). Umsetzung als JSON-Schema in `server/schemas/` (AP-03).

## 7.2 Enum `pain_event.location`

`finger_ringband, finger_gelenk, handgelenk, ellbogen_medial, ellbogen_lateral, schulter, nacken, lws, huefte, knie, achillessehne, wade, schienbein, fuss, sonstiges, patellasehne, sprunggelenk, bws` (die letzten drei ab D-53)

# 8. MCP-Schnittstelle

## 8.1 Endpunkt und Transport

- Pfad: `/mcp` (Streamable HTTP, stateless; GET/DELETE → 405). Für Clients älterer Protokollrevisionen legt das SDK Sitzungsdateien an; Ablage in `<Ordner>/var/` außerhalb des Docroots (D-17).
- Auth: Bearer (OAuth 2.1, D-05). Access-Token ist ein JWT HS256 (D-32), geprüft über den SDK-`JwtTokenValidator` (iss = `APP_URL`, aud = `APP_URL` + `/mcp`, exp); ohne oder mit ungültigem Token → 401 mit `WWW-Authenticate: Bearer resource_metadata=...` (vom SDK erzeugt).
- Aus dem SDK (Resource-Server-Seite, D-04): Bearer-Prüfung, `/.well-known/oauth-protected-resource`, 401-Antwort.
- Selbst gebaut (Autorisierungsserver, D-04, D-36): `/.well-known/oauth-authorization-server` (RFC 8414), `/oauth/register` (offene DCR), `/oauth/authorize` (Login D-33 + Freigabeseite S7), `/oauth/token` (Code-Einlösung mit PKCE S256, Refresh mit Rotation D-32).
- Fallback (D-06): derselbe Endpunkt akzeptiert zusätzlich das statische Token `MCP_STATIC_TOKEN` aus `.env`, aber nur wenn `MCP_STATIC_TOKEN_ENABLED=true`.
- Scopes: `training:read`, `training:write` (AP-01); Metadaten zusätzlich unter dem Pfad-Suffix `/mcp` (`/.well-known/oauth-authorization-server/mcp`, `/.well-known/oauth-protected-resource/mcp`) für Clients, die nach RFC 9728/8414 pfadbezogen suchen.

## 8.2 Tools (konzeptionell)

| tool | eingabe | ausgabe (aggregiert) | schreibt |
|---|---|---|---|
| `get_week_overview` | week_start | je Session: Typ, Titel, Status, geplant vs. Ist (Dauer, sRPE-Load), Feel, Abweichungsgrund; Wochensummen sRPE je Typ; Compliance %; Schmerzereignisse der Woche (Ort, max, Verlauf); Check-in-Mittelwerte + Abdeckung %; Ausdauer aus Intervals.icu: je Aktivität Dauer, Distanz, Höhenmeter, Zeit in HF-Zonen (komprimiert), Load; Fitness/Fatigue/Form-Werte; Matching Aktivität↔Event; Woche `fokus` und `begruendung`, je Session `kurz` (D-56) | nein |
| `get_session_detail` | session_id | plan_json, actual_json, Feedback, Notizen, coach_summary, coach_rationale (D-56) | nein |
| `get_pain_history` | days (default 56) | je Ort: Ereignisse (Datum, Intensität, Timing), 7-Tage-Trend | nein |
| `get_wellness_trend` | days (default 28) | tageweise: HRV, Ruhepuls, Schlaf (h, Score), Check-in-Werte; 7d-vs-28d-Baseline für HRV/Ruhepuls | nein |
| `get_block` | block_id (optional) | aktiver Block, Wochenstatus, Phase; Kurzliste der Reviews und Fälligkeiten des Blocks (AP-15) | nein |
| `write_week_plan` | week_start, sessions[], replace_existing(bool), focus (Pflicht, D-56), coach_notes | angelegte Session-IDs, Intervals.icu-Event-IDs, Fehler je Session; je Session `coach_summary` Pflicht außer `ruhe`, `coach_rationale` optional (D-56); Wochen nach dem Ende des aktiven Blocks ohne Folgeblock mit bestätigter Zielklärung → Fehler `blockwechsel_erforderlich` mit Fälligkeiten, sonst `faellig` in der Antwort (AP-15) | ja (DB + Intervals.icu) |
| `update_session` | session_id, changes (inkl. coach_summary, coach_rationale; D-56) | aktualisierte Session; bei Ausdauer auch Event-Update | ja |
| `get_morning_checks` (D-53) | days (Standard 14, 7–90) | Zusammenfassung für heute (Ampel mit Grund, Morgentest links/rechts/Steuerwert, Wochenausgangswert, Vortagseinheiten, grüne Tage und Abdeckung der letzten 7 Tage, abklaerung_empfohlen) und je Tag alle Felder und Ableitungen, neueste zuerst; Format `docs/konzept/morgen-checkin.md` 6.1 | nein |
| `get_athlete_profile` (D-48) | section, as_of, include_history (alle optional) | Abschnitte (Markdown) mit Stand, Urheber, Grund und Anzahl Fassungen; mit as_of der Stand am Ende dieses Tages; mit include_history die Fassungen eines Abschnitts (höchstens 20) | nein |
| `update_athlete_profile` (D-48) | section, content (vollständiger Abschnitt), reason (optional) | Version; `unveraendert`, wenn der Text gleich ist | ja (neue Fassung) |
| `upsert_block` (D-40) | block_id (optional), block {name, start_date, end_date, status, goal_events, phase_notes, doc_ref} | Block-ID; Fälligkeiten des Blocks, `zielklaerung_fehlt`; Blocktermin im Kalender (AP-15) | ja |
| `get_handover` (AP-15, D-76) | detail (bool) | Pflichtaufruf zu Beginn jeder Planungssitzung: aktiver Block, gültige Zielklärung (Phase, Prioritäten, Ziele, Entscheidungen, Risiken), zwei jüngste Bilanzen, Revisionen des Blocks, Kennzahlen der letzten 4 Wochen gegen das Blockmittel, `wochen_kurz` (D-78), `faellig`, offene Fragen, Stand je Profilabschnitt, offene Entwürfe; mit detail die Volltexte der jüngsten Zielklärung und Bilanz | nein |
| `get_block_reviews` (AP-15) | block_id (optional = aktiver), kind, fassungen | gültige Fassungen mit content_json und kennzahlen_auto (neuerer Entwurf markiert); mit fassungen alle Versionen mit reason | nein |
| `write_block_review` (AP-15, D-77) | block_id, kind, sequence (nur Revision), review_date, period_start/period_end, summary, content, status (`entwurf`/`bestaetigt`), reason (ab Fassung 2 Pflicht) | id, version, status, Kennzahlen (Kurzform), `faellig`; Schemafehler mit Pfad, nichts geschrieben; Zielklärung nur für Blöcke geplant/aktiv; Kalendertermin nachgezogen | ja (neue Fassung) |
| `find_exercise` (AP-16) | query, category, pattern, equipment, limit, include_archived | Treffer nach Rang (exakt, Teilstring, ähnlich über das Bewegungsmuster) kompakt: slug, name, category, pattern, equipment, konfidenz, kurz, variant_of; status nur wenn nicht aktiv, aehnlich nur wenn true; leer → Hinweis auf `upsert_exercise` | nein |
| `get_exercise` (AP-16) | slug oder id, fassungen, version | vollständiger Eintrag mit Aliasen, Varianten, Inhalt und Linkstatus; Fassungen bzw. früherer Stand | nein |
| `list_exercises` (AP-16) | category, status | Kompaktliste slug, name, category, pattern (ohne status ohne archivierte) | nein |
| `upsert_exercise` (AP-16, D-65) | slug, name, aliases, category, pattern, equipment, variant_of, difficulty, konfidenz, content, reason (Pflicht beim Ändern), status (aktiv/archiviert) | Kurzform mit Linkstatus und Version, `hinweis_chat`; Duplikatschutz, Linkprüfung durch den Server, Archivieren nur ohne geplante Verwendung | ja (neue Fassung) |

Übungskatalog in den Schreibtools (D-66): `write_week_plan` und `update_session` (bei geändertem `plan_json`) melden Übungen ohne `exercise_id` als `warnungen` (mit `hinweis_warnungen`), unbekannte oder archivierte IDs sind Fehler. `get_week_overview` nennt je Einheit `exercise_ids`.

Enum-Werte und Skalen in Antworten immer mit Einheit/Skala kennzeichnen (z. B. `rpe_cr10`), damit Claude sie nicht verwechselt.

Rechte (AP-05): Lese-Tools verlangen den Scope `training:read`, Schreib-Tools `training:write`. Schreib-Tools sind bei Code/Schema-Abweichung gesperrt (D-20).

## 8.3 Antwortbudget

- Jede Tool-Antwort ≤ ca. 3 000 Tokens; `get_week_overview` Ziel ≤ 2 000.
- Übungskatalog (AP-16, E-16): `find_exercise` ≤ 1 000, `get_exercise` ≤ 1 500 für typische Einträge (die Schemagrenzen erlauben mehr), `list_exercises` ≤ 2 000.
- Übergabe (AP-15, E-17): `get_handover` ohne detail ≤ 2 000 Tokens (Prüfgrenze 8 000 Zeichen; bei Bedarf stufenweise gekürzt, Feld `gekuerzt`), mit detail ≤ 8 000 Tokens; `get_block_reviews` je Datensatz content_json ≤ 1 500 Tokens, sonst Kurzsatz mit Hinweis.
- Keine Streams, keine Rohlisten über 60 Einträge; bei Bedarf Paginierung über `days`/`week_start`.
- Zahlen gerundet (Dauer min, Distanz 0,1 km, Höhenmeter 10 m).

# 9. Intervals.icu-Anbindung

- Auth: HTTP Basic mit `API_KEY:<key>`, ausschließlich serverseitig; Key außerhalb des Docroots.
- Genutzte Bereiche (V-04): Events (geplante Workouts) lesen/schreiben/löschen; Aktivitäten nach Zeitraum (Zusammenfassung, keine Streams); Wellness nach Zeitraum.
- Workout-Erzeugung: `plan_json.ausdauer.intervals_workout_text` in Intervals.icu-Syntax; ein Zieltyp pro Schritt (V-01); `summary` als Klartext für Uhr-Titel und Webseite.
- HF-Ziele beziehen sich auf die fünf Garmin-Zonen nach %LTHR (D-27); Zonengrenzen in Garmin Connect und Intervals.icu identisch pflegen (V-12).
- Sync-Verhalten: Intervals.icu überträgt geplante Workouts der kommenden Woche an Garmin Connect; Änderungen werden automatisch nachgezogen (V-02 für genaues Zeitfenster). Regel: Wochenplan spätestens am Vorabend des ersten Ausdauertags schreiben.
- Matching Aktivität↔geplante Einheit: für Ausdauer übernimmt Intervals.icu das Pairing (Compliance); für Kraft/Klettern (auf der Uhr aufgezeichnet, aber nicht als Event gepusht) heuristisch über Datum + Aktivitätstyp (V-09).
- Rate-Limit 10 req/s pro IP: unkritisch; optionaler Kurzcache (`ext_cache`).
- Privatsphäre: Aktivitäten auf privat (Q-03).

# 10. Webseite (mobil und Tablet)

Technik: serverseitig gerenderte PHP-Seiten, responsive, minimales JS (Formulare ohne Reload optional), Web-App-Manifest für „Zum Startbildschirm", kein Offline-Modus in Phase 1 (ab AP-09 Offline-Fähigkeit nach D-45/D-49: Service Worker `/sw.js`, Seitenskript `/js/offline.js`; Seiten funktionieren weiterhin ohne JavaScript).

Anforderung Gestaltung: Alle Screens sind **mobil- und tabletfreundlich** (Smartphone hochkant als Primärfall; Tablet hoch und quer ohne Layoutbrüche; Touch-Ziele, lesbare Schrift, keine horizontalen Scrollbereiche). Gestaltung nach Branding-Dokument und Mockups (D-19, D-37, AP-01a).

| screen | inhalt | felder/aktionen |
|---|---|---|
| S0 Setup (einmalig) | Anlage des einzigen Benutzers, nur solange kein Benutzer existiert (D-34) | `MIGRATION_SECRET`, Login, Passwort (2×), Zeitzone |
| S1 Login | Passwort (D-33), Session 30 Tage; Hinweis bei gesperrtem Konto | Login, Passwort; Abmelden (widerruft die Web-Session) |
| S2 Woche | 7 Tage, je Tag Einheiten (Typ-Icon, Titel, Dauer, Status); heutiger Tag hervorgehoben; Check-in-Status pro Tag; Navigation ±Woche; Wochensumme sRPE; Kurzsatz der Woche mit „mehr“ unter der Kopfzeile (D-56); Karte „Block“ unter dem Morgen-Check-in mit Restlaufzeit und Fälligkeiten (AP-15) | Einheit öffnen; Check-in öffnen |
| S3 Einheit | Kurzsatz der Planung mit „mehr“ (D-56); Knopf „Einheit starten“ → S9 (D-57); Plan (Übungen/Blöcke mit Soll), Ist-Eingabe pro Übung (vorbelegt mit Soll), Feedback-Block | RPE 0–10; Feel 1–5; Schmerz ja/nein → Ort, Seite, Stärke, Timing; Abweichungsgrund; Notiz; Status setzen (erledigt/teilweise/ausgelassen/verschoben); bei Ausdauer: verknüpfte Intervals.icu-Aktivität anzeigen |
| S4 Check-in | Tagesformular vor dem Frühstück mit Morgentest (D-53); Formular bzw. Zusammenfassung mit Ampel auch als Karte oben in S2 | Morgentest links/rechts 0–10, Erholung 1–5, Muskelkater 1–5; unter „Weitere Angaben“ Nacken/BWS, Sprunggelenk links, Hand rechts, Warnzeichen, Schmerz ja/nein (→ S5-Kurzform), Notiz optional |
| S5 Schmerz | Kurzformular | Ort (Enum 7.2), Seite, 0–10, Timing, Notiz |
| S6 Verlauf (optional, AP-09) | Schmerz je Ort über 8 Wochen; sRPE-Wochenlast je Typ; Abschnitt „Blöcke“ mit Status, Fälligkeiten und Link auf S11 (AP-15) | |
| S7 OAuth-Freigabe | Freigabeseite im Authorize-Schritt (D-36): zeigt Client-Name, Redirect-Host und angeforderten Scope | Freigeben / Ablehnen |
| Profil (AP-09, D-48) | Athletenprofil je Abschnitt mit Stand und Urheber; Bearbeiten je Abschnitt; frühere Fassungen | Abschnitt bearbeiten (Text, Grund); Fassungen ansehen |
| S8 Einstellungen | Athletenprofil (Link), Übungskatalog (Link, Hinweis auf Übungen mit defekten Links, AP-16), Konto (Abmelden, Zeitzone, Passwort, Passkeys, Morgen-Check-in), Training (Timer-Signale an/aus, D-58; Blockbilanz und Zielklärung: Erinnerung an/aus, Vorlauf Bilanz/Zielklärung, Beginn/Dauer/Erinnerung des Blocktermins, AP-15), Backup (Download, JSON-Export, E-Mail-Status), Update (Schemastand, Migration), Verbindungen (Intervals.icu, Spiegel, Kalender mit Erinnerung, freigegebene OAuth-Clients, statisches Token) | Abmelden; Timer-Signale speichern; Backup herunterladen; Migration ausführen; Freigabe widerrufen |
| S9 Einheit geführt (AP-14, D-57/D-58) | Schrittweise Führung durch eine Einheit: Fortschritt, aktuelle Übung mit Satz, Soll und Timer (Arbeit grün, Pause rot), Ist-Felder der Übung, „Als Nächstes“, Abschluss mit Rückmeldung wie S3; Stummschalter in der Kopfzeile; Link „Ausführung“ auf S10 (AP-16, Rückkehr ohne Rückfrage) | Start/Anhalten/Pause beenden/Satz erledigt/Weiter/Zurück/Überspringen; Speichern (wie S3) |
| S10 Übung (AP-16, D-64/D-67) | Katalogeintrag: Kategorie, Muster, Ausrüstung, Konfidenz; Kurz/Ziel, Voraussetzung, Ausführung, Worauf achten, Fehlerquellen, Vorsicht, Progression/Regression mit Varianten, Dosierungshinweis, eingebettete Videos (YouTube-nocookie, Vimeo) mit Link, Links mit Prüfstatus, Quellen, Fassungen; offline mit Link statt Video | nur lesen (Bearbeiten über den Chat, O-01); aus S3 (Übungsname), S9 („Ausführung“), Kalender und S10a |
| S10a Übungskatalog (AP-16) | Liste der Übungen als Karten mit Status | Suche, Filter Kategorie, archivierte zeigen |
| S11 Block (AP-15, D-72/D-74) | Block mit Status, Zeitraum, Restlaufzeit und Fälligkeiten; Zielklärung (Ausgangslage, Phase, Prioritäten, Ziele, Zielevents, Entscheidungen als Tabelle mit verworfenen Alternativen, Risiken, Ableitung, offene Fragen); Revisionen als Zeitleiste; Bilanz (Ziel/Soll/Ist/Bewertung, Tests, geänderte Annahmen, Empfehlung, eingefrorene Kennzahlen); Fassungen je Datensatz; weitere Blöcke | nur lesen (O-01 im Auftrag); aus S2-Karte, S6, Overlay und Kalendertermin |
| Overlay Erinnerung (AP-15, D-74) | auf allen Seiten nach dem Login (nicht S0, S1, S7 und nicht während der geführten Einheit S9 bzw. S10 aus S9 – dann nach dem Abschluss auf S2), solange Bilanz oder Zielklärung fällig und nicht quittiert: Karte mit Grund und Link auf S11, Seite dahinter gesperrt | „Morgen wieder erinnern“ (Fokus), „Diese Woche nicht mehr“ (`POST /erinnerung`, offline gepuffert) |

Screens S0, S1 und S7 entstehen in AP-01, S2–S5 und S8 in AP-04 (Backup/Update-Funktionen in S8 aus AP-10), S6 und Profil in AP-09; Mockups in AP-01a (Profil ohne Mockup, aus vorhandenen Bausteinen – branding.md Abschnitt 8); S9 in AP-14 mit Mockup `s9-einheit-gefuehrt.html` (Fable, 2026-09-28), Anpassungen S2/S3/S8 in AP-13/AP-14; S10/S10a in AP-16 mit Mockups `s10-uebung.html`, `s10a-uebungen.html` (Code-Instanz aus vorhandenen Bausteinen), Anpassungen S3/S8/S9. S11 und das Overlay in AP-15 mit Mockup `s11-block.html` bzw. `s2-woche.html?state=erinnerung` (Code-Instanz aus vorhandenen Bausteinen), Anpassungen S2/S6/S8.

# 11. Feedback- und Check-in-Definitionen

| feld | skala | definition | quelle_regel |
|---|---|---|---|
| `rpe_cr10` | 0–10 (CR-10) | Gesamtanstrengung der Einheit, 30 min nach Ende bewertet | Foster sRPE-Methode (Wissenskarte AP-06) |
| `srpe_load` | rpe × Dauer(min) | berechnet, nie manuell | |
| `feel_1_5` | 1–5 | Wie hat sich die Einheit angefühlt (1 = sehr gut, 5 = sehr schlecht; Richtung gegen Intervals.icu abgleichen V-03) | |
| `recovery_1_5` | 1–5 | Erholt/leistungsbereit heute (1 = sehr gut, 5 = sehr schlecht) | D-16 |
| `soreness_1_5` | 1–5 | Muskelkater (1 = keiner, 5 = stark) | D-16 |
| `pain intensity` | 0–10 (NRS) | Schmerz am Ort, zum Timing | Schmerzregeln Abschnitt 14 |

Regeln für fehlende Daten:
- Fehlendes Check-in = fehlend, nicht = beschwerdefrei. `get_week_overview` liefert Abdeckung %.
- Bei Abdeckung < 50 % in einer Woche kennzeichnet Claude die Wochenbewertung als „geringe Konfidenz" und fragt nach, statt zu progressieren.
- Fehlendes Session-Feedback bei Status `erledigt` → Claude fragt beim nächsten Chat gezielt nach (Liste der offenen Feedbacks im Overview).

# 12. Sicherheit und Datenschutz

1. HTTPS (Zertifikat über Lima-City; TLS endet am vorgeschalteten Proxy, der auch auf HTTPS umleitet); HSTS.
1a. Auf dem Webspace ist `open_basedir` nicht gesetzt: PHP-Skripte anderer Websites desselben Lima-City-Accounts können `.env`, `backups/` und `var/` lesen. Hinnehmbar, solange im Account keine fremde oder veraltete Software läuft; Backups sind zusätzlich verschlüsselt. Zusätzlich sperrt eine `.htaccess` im Subdomain-Ordner jeden HTTP-Zugriff, falls der Document Root versehentlich auf den Ordner selbst zeigt.
2. Secrets ausschließlich in `.env` außerhalb des Docroots, nie im Repo: Intervals.icu-Key, `OAUTH_JWT_SECRET` (Pflicht, ≥ 32 Zeichen, D-32), `CRON_SECRET` (Cron-Endpunkte), `MCP_STATIC_TOKEN` mit `MCP_STATIC_TOKEN_ENABLED` (D-06), `MIGRATION_SECRET` (D-17, D-34), Backup-Passwort (D-18).
3. OAuth 2.1 Single-User: Authorize nur nach Webseiten-Login und ausdrücklicher Freigabe (D-36); Access-Tokens = JWT HS256, 1 h, ohne DB-Eintrag; Refresh-Tokens zufällig, nur gehasht, rotierend, Familien-Widerruf bei Wiederverwendung (D-32); PKCE S256 Pflicht, Codes 10 min einmalig, Redirect-URIs nur https bzw. localhost (D-36). Tradeoff: Widerruf greift für laufende Access-Tokens erst nach Ablauf (≤ 1 h); Notbremse ist der Wechsel von `OAUTH_JWT_SECRET`.
3a. Webseiten-Login (D-33): Passwort-Hash per `password_hash()`; Session 30 Tage in `web_session`, Cookie `HttpOnly`/`Secure`/`SameSite=Lax`, CSRF-Schutz über `csrf_secret` je Session; Kontosperre nach 10 Fehlversuchen für 5 Minuten, bei weiteren Fehlversuchen Verdopplung bis maximal 24 h, Rücksetzung des Zählers bei erfolgreichem Login (`failed_logins`, `locked_until`; D-33). Erstanlage nur über `/setup` mit `MIGRATION_SECRET`, danach dauerhaft gesperrt (D-34).
4. Audit-Log für alle Schreibzugriffe über MCP und Web.
5. Backups gemäß D-18: verschlüsselte Dumps (manuell, per E-Mail, vor Migrationen); Backup-Passwort nur in `.env`; Pre-Migration-Dumps außerhalb des Docroots, nicht per HTTP erreichbar; Restore-Test in AP-10.
5a. Deployment gemäß D-17: FTPS/SFTP-Zugangsdaten und Migrations-Secret nur als GitHub-Actions-Secrets; Workflow überschreibt weder `.env` noch `backups/` noch `var/` (Ausschlussliste ab AP-01 um `var/` ergänzt).
5b. Updates gemäß D-20: Schreibsperre bei Code/Schema-Abweichung; Migrationen nur nach erfolgreichem Pre-Migration-Dump.
6. Datenklassifikation: Schmerz-/Verletzungsdaten = sensibel → nur K3 und Profil-Dokument (D-14); Projekt-Memory hält keine Gesundheitsdaten.
7. Intervals.icu: Datenschutzeinstellungen prüfen; Bewusstsein, dass ein Drittanbieter Aktivitäts- und Wellness-Daten hält (bewusste Entscheidung D-02).

# 13. Wissensbasis

## 13.1 Struktur

- Ablage: `docs/wissen/<thema>.md`, gespiegelt ins Projekt-Wissen (K5).
- Front matter je Karte: `thema`, `geltungsbereich` (T1/T2/T3/T4), `quellen[]` (Typ, Titel, Autor, Jahr, Seiten bzw. Kapitel/Abschnitt (EPUB ohne Seitenliste, D-71)/DOI), `stand`, `konfidenz` (hoch/mittel/niedrig).
- Aufbau: Kernaussagen (je mit Quellenverweis) → Zahlen/Protokolle → Anwendung im Plan → Grenzen/Widersprüche in der Literatur.
- PubMed-Workflow: Bei Entscheidungen, die eine Primärquelle brauchen, Abfrage über den PubMed-Connector im Projekt-Chat; DOI in die Karte übernehmen.
- Zitierregel D-13 gilt in jedem Chat.
- Bündelung und Budget: Projektwissen wird vollständig in jeden Chat geladen, solange es unter dem Kontextlimit bleibt; darüber (und beobachtet bereits ab etwa 13 Dateien) schaltet das Projekt in den Retrieval-Modus, in dem nur gefundene Passagen sichtbar sind. Daher: wenige Sammeldateien (je Bereich T1–T4 plus übergreifend und R, 4–7 Dateien; 7 seit D-79) statt vieler Einzelkarten; Gesamtbudget des Projektwissens inkl. Regeln, Profil und aktuellem Blockplan unter ca. 40 000 Tokens halten.
- Erstellungsprozess (in eigenen Sitzungen, nicht im Trainingsprojekt): (1) PDF kapitelweise aufteilen (20–40 Seiten; für die vorhandenen Bücher erledigt, `docs/literatur/<block>/<ID>_kapitel/`, D-51); EPUB kapitelweise als Markdown mit Ansichts-PDF (D-71); (2) Extraktion je Kapitel mit Template und Regeln: nur Textinhalt, Seitenzahl je Aussage, Zahlen exakt mit Einheit, Modellschlüsse markiert, Lücken des Kapitels aufgelistet; (3) Prüfung: 3–5 Aussagen je Karte gegen das PDF, dann `konfidenz` setzen; (4) Synthesekarte je Thema über alle Quellen mit Widersprüchen und geltender Regel (Vorarbeit AP-07); (5) Ablage in `docs/wissen/`, Spiegelung ins Projektwissen. PDFs liegen lokal und dürfen zusätzlich im privaten Repo unter `docs/literatur/` liegen (D-31), nie im Projektwissen.

## 13.2 Literaturkandidaten und -auswahl (AP-06)

Literatur wird blockweise ausgewählt (ein Block je Bereich), in eigenen Sitzungen erarbeitet und per Übergabedokument in diesen Abschnitt eingearbeitet. Dieses Dokument ist die einzige Quelle für IDs; neue Blöcke nehmen die nächsten freien Nummern. Jede Literatur-Sitzung startet mit der aktuellen Konzeptfassung – parallel begonnene Sitzungen haben bereits zu ID-Kollisionen geführt (D-21–D-23, Änderungsprotokoll).

ID-Konvention:
- übergreifend: `L-A<nn>` Bücher, `L-P<nn>` Paper
- Bereiche: `L-T1-<nn>` (Ausdauer), `L-T2-<nn>` (Kraft/Haltung), `L-T3-<nn>` (Klettern), `L-T4-<nn>` (Beweglichkeit/Mobilität, D-79), `L-R-<nn>` (Reha/Prävention, themenübergreifend, D-61); der Quellentyp steht im Feld `typ`
- Verweise auf einen Eintrag eines anderen Blocks: eigenes `id` mit `status: verweis` und Feld `verweis: <id>`, nie eine zweite Definition

Statuswerte: `kandidat` (unverifiziert) · `verifiziert` (bibliografisch bzw. PubMed) · `vorgeschlagen` (von der Sitzung empfohlen, vom Athleten noch nicht bestätigt) · `ausgewaehlt` (vom Athleten bestätigt) · `optional` · `zurueckgestellt` · `verweis` · `nicht_aufgenommen`

Volltext (Felder `datei`, `kapitel`, D-51): Pfad relativ zu `docs/literatur/`; fehlt `datei`, liegt noch kein Volltext vor. Verzeichnis aller Dateien: `docs/literatur/README.md`.

Evidenzstufe (Feld `stufe`, D-31): `A` Paper/Konsens (konfidenz hoch) · `B` wissenschaftliches Lehrbuch (mittel) · `C` Praxisquelle (niedrig, kein alleiniger Beleg für Belastungsparameter)

### 13.2.1 Übergreifend – Allgemeine Trainingslehre (Block bestätigt 2026-09-27)

Bücher:

```yaml
- id: L-A01
  status: ausgewaehlt
  datei: uebergreifend/L-A01_Kenney-2019_Physiology-of-Sport-and-Exercise_7ed.pdf
  vorhanden_auflage: 7. Aufl. 2019, ISBN 978-1-4925-7229-9 – gilt (Entscheidung Athlet 2026-09-29 – vorhandene Ausgaben verwenden, 8./9. Aufl. werden nicht beschafft)
  kapitel: uebergreifend/L-A01_kapitel/
  typ: Lehrbuch
  autor: Kenney WL, Wilmore JH, Costill DL
  titel: Physiology of Sport and Exercise
  auflage: 8
  jahr: 2022
  verlag: Human Kinetics
  isbn: 978-1-7182-0172-9
  sprache: en
  zweck: Physiologische Grundlagen von Belastung und Anpassung
  verifikation: bibliografisch (Bibliothekskataloge)
  hinweis: E-Book-Format (epub/HKPropel) vor Kauf auf Eignung für den PDF-Kapitel-Workflow (13.1) prüfen. Block T1 meldet eine 9. Aufl. 2024 (ISBN 978-1-7182-2842-9, nur Händlerangabe) – Auflage und Autorenliste beim Erwerb prüfen (AP-06 offener Punkt).
- id: L-A02
  status: ausgewaehlt
  datei: uebergreifend/L-A02_Ferrauti-2025_Trainingswissenschaft-fuer-die-Sportpraxis_2ed.pdf
  kapitel: uebergreifend/L-A02_kapitel/
  typ: Lehrbuch
  autor: Ferrauti A, Wiewelhove T (Hrsg.)
  titel: "Trainingswissenschaft für die Sportpraxis. Lehrbuch für Studium, Ausbildung und Unterricht im Sport"
  auflage: 2
  jahr: 2025 (1. Aufl. 2020, ISBN 9783662582268, DOI 10.1007/978-3-662-58227-5)
  isbn: 978-3-662-69523-4 (Print), 978-3-662-69524-1 (E-Book)
  doi: 10.1007/978-3-662-69524-1
  verlag: Springer (1. Aufl. Springer Spektrum)
  sprache: de
  zweck: Integrierte Trainingswissenschaft – Leistungsdiagnostik, Trainingssteuerung, Monitoring, Regenerationsmanagement
  verifikation: bibliografisch laut Titelei und Impressum des Volltexts (2026-09-28); 2. Aufl. mit Wiewelhove als zweitem Herausgeber
- id: L-A03
  status: ausgewaehlt
  datei: uebergreifend/L-A03_NSCA-2026_Essentials-of-Strength-Training-and-Conditioning_5ed.pdf
  kapitel: uebergreifend/L-A03_kapitel/
  entschieden_in: Block T2 (2026-09-27), Kernset D-28
  stufe: B
  typ: Lehrbuch
  autor: NSCA (Hrsg.)
  titel: Essentials of Strength Training and Conditioning
  auflage: 5
  jahr: ©2027 (erschienen 2026)
  verlag: Human Kinetics
  isbn: 9781718216273
  sprache: en
  zweck: Programmgestaltung; Kapitel zu Overreaching/Übertraining
  einschraenkung: CSCS-Prüfungsbuch, konservativer Konsens, S&C-Fokus
  hinweis_heimausruestung: Kap. 17 enthält Abschnitte zu Widerstandsbändern mit Belastungsbestimmung (17-1) und zum Kettlebell-Training mit Technikbeschreibungen (17-2/17-3) – deckt die Heimausrüstung (Kettlebell, TheraBand) ab; keine eigene Quelle nötig (geprüft am Kapiteltext 2026-09-29, D-80)
  verifikation: bibliografisch (Verlag, Bibliothekskatalog)
```

Paper (Kern der Regelbasis; L-P01–L-P09 per PubMed verifiziert am 2026-09-27, L-P10–L-P14 am 2026-09-28):

```yaml
- id: L-P01
  status: ausgewaehlt
  datei: uebergreifend/L-P01_Kiely-2018_Periodization-Theory.pdf
  thema: Planung/Periodisierung – Kritik
  zitat: "Kiely J. Periodization Theory: Confronting an Inconvenient Truth. Sports Med. 2018;48(4):753-764."
  doi: 10.1007/s40279-017-0823-y
  pmid: "29189930"
  pmcid: PMC5856877
  zugang: Open Access (PMC)
- id: L-P02
  status: ausgewaehlt
  datei: uebergreifend/L-P02_Mujika-2018_Integrated-Approach-to-Periodization.pdf
  thema: Planung/Periodisierung – integrierte Periodisierung (Gegenposition zu L-P01)
  zitat: "Mujika I, Halson S, Burke LM, Balagué G, Farrow D. An Integrated, Multifactorial Approach to Periodization for Optimal Performance in Individual and Team Sports. Int J Sports Physiol Perform. 2018;13(5):538-561."
  doi: 10.1123/ijspp.2018-0093
  pmid: "29848161"
  zugang: kein PMC-Volltext
- id: L-P03
  status: ausgewaehlt
  datei: uebergreifend/L-P03_Bourdon-2017_Monitoring-Training-Loads-Consensus.pdf
  thema: Belastungsmonitoring – Konsens
  zitat: "Bourdon PC, Cardinale M, Murray A, et al. Monitoring Athlete Training Loads: Consensus Statement. Int J Sports Physiol Perform. 2017;12(Suppl 2):S2161-S2170."
  doi: 10.1123/IJSPP.2017-0208
  pmid: "28463642"
  zugang: kein PMC-Volltext
- id: L-P04
  status: ausgewaehlt
  datei: uebergreifend/L-P04_Impellizzeri-2019_Internal-and-External-Training-Load.pdf
  thema: Belastungsbegriff intern/extern
  zitat: "Impellizzeri FM, Marcora SM, Coutts AJ. Internal and External Training Load: 15 Years On. Int J Sports Physiol Perform. 2019;14(2):270-273."
  doi: 10.1123/ijspp.2018-0935
  pmid: "30614348"
  zugang: kein PMC-Volltext
- id: L-P05
  status: ausgewaehlt
  datei: uebergreifend/L-P05_Kellmann-2018_Recovery-and-Performance-Consensus.pdf
  thema: Erholung – Konsens
  zitat: "Kellmann M, Bertollo M, Bosquet L, et al. Recovery and Performance in Sport: Consensus Statement. Int J Sports Physiol Perform. 2018;13(2):240-245."
  doi: 10.1123/ijspp.2017-0759
  pmid: "29345524"
  zugang: kein PMC-Volltext
- id: L-P06
  status: ausgewaehlt
  datei: uebergreifend/L-P06_Meeusen-2013_Overtraining-Syndrome-Consensus.pdf
  thema: Übertraining – Konsens
  zitat: "Meeusen R, Duclos M, Foster C, et al. Prevention, diagnosis, and treatment of the overtraining syndrome: joint consensus statement of the European College of Sport Science and the American College of Sports Medicine. Med Sci Sports Exerc. 2013;45(1):186-205."
  doi: 10.1249/MSS.0b013e318279a10a
  pmid: "23247672"
  zugang: kein PMC-Volltext
  hinweis: Zitierfassung ist die in PubMed indexierte MSSE-Fassung (D-24)
- id: L-P07
  status: ausgewaehlt
  datei: uebergreifend/L-P07_Schumann-2022_Concurrent-Training-Meta-Analysis.pdf
  thema: Kombiniertes Training (Interferenz Ausdauer/Kraft)
  zitat: "Schumann M, Feuerbacher JF, Sünkeler M, et al. Compatibility of Concurrent Aerobic and Strength Training for Skeletal Muscle Size and Function: An Updated Systematic Review and Meta-Analysis. Sports Med. 2022;52(3):601-612."
  doi: 10.1007/s40279-021-01587-7
  pmid: "34757594"
  pmcid: PMC8891239
  zugang: Open Access (PMC)
  relevanz: hoch – T1, T2 und T3 laufen parallel
- id: L-P08
  status: ausgewaehlt
  datei: uebergreifend/L-P08_Currier-2026_ACSM-Resistance-Training-Prescription.pdf
  thema: Krafttraining – Prinzipien (ersetzt ACSM Position Stand 2009)
  zitat: "Currier BS, D'Souza AC, Singh MAF, et al. American College of Sports Medicine Position Stand. Resistance Training Prescription for Muscle Function, Hypertrophy, and Physical Performance in Healthy Adults: An Overview of Reviews. Med Sci Sports Exerc. 2026;58(4):851-872."
  doi: 10.1249/MSS.0000000000003897
  pmid: "41843416"
  pmcid: PMC12965823
  zugang: Open Access (PMC)
  bezug: auch T2; laut Abstract kein konsistenter Effekt von Periodisierung auf Trainingsergebnisse → relevant für Kontroverse L-P01/L-P02
- id: L-P09
  status: ausgewaehlt
  datei: uebergreifend/L-P09_Held-2026_Concurrent-Training-Umbrella-Review.pdf
  thema: Kombiniertes Training – Umbrella-Review (Ergänzung zu L-P07)
  zitat: "Held S, Wolf L, Rappelt L, et al. Maximizing Adaptations in Concurrent Training: An Umbrella Review of Meta-analyses. Sports Med. 2026;56(6):1489-1512."
  doi: 10.1007/s40279-026-02401-y
  pmid: "41762427"
  zugang: kein PMC-Volltext
  relevanz: Reihenfolge Kraft vor Ausdauer in derselben Einheit (Trend, nicht signifikant); Datenlage bei Hochtrainierten dünn
- id: L-P10
  status: ausgewaehlt
  datei: uebergreifend/L-P10_Foster-2001_Monitoring-Exercise-Training-sRPE.pdf
  stufe: A
  typ: validierungsstudie
  thema: sRPE-Methode (Belastungsmaß, Abschnitt 11)
  zitat: "Foster C, Florhaug JA, Franklin J, Gottschall L, Hrovatin LA, Parker S, Doleshal P, Dodge C. A new approach to monitoring exercise training. J Strength Cond Res. 2001;15(1):109-15."
  pmid: "11708692"
  doi: keine in PubMed hinterlegt
  zugang: Volltext vorhanden (datei, 2026-09-28)
  kernaussage_abstract: Session-RPE korreliert konsistent mit HF-basiertem Belastungsmaß über Dauer-, Intervall- und Spielbelastung; absolute Werte liegen mit sRPE höher
  verifikation: PubMed 2026-09-28
- id: L-P11
  status: ausgewaehlt
  datei: uebergreifend/L-P11_Saw-2016_Monitoring-Athlete-Training-Response.pdf
  stufe: A
  typ: systematischer_review
  thema: Erholungsmonitoring – subjektiv vs. objektiv
  zitat: "Saw AE, Main LC, Gastin PB. Monitoring the athlete training response: subjective self-reported measures trump commonly used objective measures: a systematic review. Br J Sports Med. 2016;50(5):281-91."
  pmid: "26423706"
  pmcid: PMC4789708
  doi: 10.1136/bjsports-2015-094758
  zugang: Volltext in PMC; laut Volltext Open Access CC BY-NC 4.0 (BMJ)
  kernaussage_abstract: 56 Studien; subjektive und objektive Marker korrelierten meist nicht; subjektive Marker bildeten akute und chronische Last sensitiver und konsistenter ab
  hinweis: Online-Vorabveröffentlichung 2015, Heft 2016 – Zitierjahr 2016
  verifikation: PubMed 2026-09-28 (V-06)
- id: L-P12
  status: ausgewaehlt
  datei: uebergreifend/L-P12_Impellizzeri-2020_ACWR-Conceptual-Issues.pdf
  stufe: A
  typ: kommentar_kritische_analyse
  thema: ACWR-Kritik (Begründung, warum keine ACWR-Automatik)
  zitat: "Impellizzeri FM, Tenan MS, Kempton T, Novak A, Coutts AJ. Acute:Chronic Workload Ratio: Conceptual Issues and Fundamental Pitfalls. Int J Sports Physiol Perform. 2020;15(6):907-913."
  pmid: "32502973"
  doi: 10.1123/ijspp.2019-0864
  zugang: Volltext vorhanden (datei, 2026-09-28)
  ersatz: "Impellizzeri FM, McCall A, Ward P, Bornn L, Coutts AJ. Training Load and Its Role in Injury Prevention, Part 2. J Athl Train. 2020;55(9):893-901. DOI 10.4085/1062-6050-501-19, PMC7534938 – Open Access, inhaltlich redundant; nur falls die Beschaffung von L-P12 scheitert"
  kernaussage_abstract: keine Evidenz für ACWR in Laststeuerungssystemen oder Empfehlungen zur Verletzungsreduktion; Kausalität nicht belegt; Ratio erzeugt statistische Artefakte
  verifikation: PubMed 2026-09-28
- id: L-P13
  status: ausgewaehlt
  datei: uebergreifend/L-P13_Silbernagel-2007_Pain-Monitoring-Model-Achilles.pdf
  stufe: A
  typ: rct
  thema: Schmerzmonitoring bei Sehnenbelastung (Grundlage Schmerzregeln 14.5)
  zitat: "Silbernagel KG, Thomeé R, Eriksson BI, Karlsson J. Continued sports activity, using a pain-monitoring model, during rehabilitation in patients with Achilles tendinopathy: a randomized controlled study. Am J Sports Med. 2007;35(6):897-906."
  pmid: "17307888"
  doi: 10.1177/0363546506298279
  zugang: Volltext vorhanden (datei, 2026-09-28)
  kernaussage_abstract: n = 38; Weiterlaufen/Springen nach Schmerzmonitoring-Modell ohne Nachteil gegenüber aktiver Pause (VISA-A-S, 12 Monate)
  herkunft_modell: "Thomeé R. A comprehensive treatment approach for patellofemoral pain syndrome in young women. Phys Ther. 1997;77(12):1690-703. PMID 9413448, DOI 10.1093/ptj/77.12.1690"
  schwellenwerte: nicht im Abstract; nur nicht begutachtete Sekundärquelle (Physiotherapie-Blog) – Schmerz bis 5/10 (VAS) während der Belastung zulässig, bis zum Folgemorgen abgeklungen, keine Zunahme von Schmerz/Steifigkeit von Woche zu Woche → am Volltext prüfen
  geltungsbereich: Sehnenreha untere Extremität; Übertragung auf Finger/Ringbänder unbelegt (→ Q-13)
  verifikation: teilweise (V-07)
- id: L-P14
  status: optional
  datei: uebergreifend/L-P14_Impellizzeri-2021_Chronic-Workloads-ACWR.pdf
  stufe: A
  typ: reanalyse
  thema: ACWR-Kritik – statistische Widerlegung (Ergänzung zu L-P12)
  zitat: "Impellizzeri FM, Woodcock S, Coutts AJ, Fanchini M, McCall A, Vigotsky AD. What Role Do Chronic Workloads Play in the Acute to Chronic Workload Ratio? Time to Dismiss ACWR and Its Underlying Theory. Sports Med. 2021;51(3):581-592."
  pmid: "33332011"
  doi: 10.1007/s40279-020-01378-6
  zugang: kein PMC-Volltext
  kernaussage_abstract: zufällige bzw. fixe chronische Last im Nenner erzeugt ähnliche Effekte wie echte; ACWR ohne prädiktiven Mehrwert
  verifikation: PubMed 2026-09-28
- id: L-P15
  status: ausgewaehlt
  datei: uebergreifend/L-P15_Manresa-Rocamora-2021_HRV-Guided-Training-Meta-Analysis.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  thema: HRV-gesteuertes vs. vorab geplantes Ausdauertraining; Methodik der Baseline
  zitat: "Manresa-Rocamora A, Sarabia JM, Javaloyes A, Flatt AA, Moya-Ramón M. Heart Rate Variability-Guided Training for Enhancing Cardiac-Vagal Modulation, Aerobic Fitness, and Endurance Performance: A Methodological Systematic Review with Meta-Analysis. Int J Environ Res Public Health. 2021;18(19):10299."
  pmid: "34639599"
  pmcid: PMC8507742
  doi: 10.3390/ijerph181910299
  zugang: Open Access (PMC); CC BY 4.0 laut Volltext (geprüft 2026-09-29)
  kernaussage_abstract: HRV-gesteuert überlegen für vagale HRV-Indizes (SMD 0,50; 95%-KI 0,09–0,91), nicht für Ruhepuls (SMD 0,04); kleine, nicht signifikante Vorteile für VO2max (0,20), Leistung an der zweiten Ventilationsschwelle (0,26) und Ausdauerleistung (0,20); HRV-Index, Messposition und Baseline-Bildung (fix vs. gleitend) laut Autoren weiter zu untersuchen; Suche bis 10/2020
  bezug: Abschnitt 14 Kap. 4 (Deload-Trigger); 7d-vs-28d-Baseline in get_wellness_trend
  verifikation: PubMed 2026-09-29
- id: L-P16
  status: optional
  datei: uebergreifend/L-P16_Dueking-2021_HRV-Guided-Training-Wearables.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  thema: HRV-gesteuertes Ausdauertraining mit Wearables
  zitat: "Düking P, Zinner C, Trabelsi K, Reed JL, Holmberg HC, Kunz P, Sperlich B. Monitoring and adapting endurance training on the basis of heart rate variability monitored by wearable technologies: A systematic review with meta-analysis. J Sci Med Sport. 2021;24(11):1180-1192."
  pmid: "34489178"
  doi: 10.1016/j.jsams.2021.04.012
  zugang: kein PMC-Volltext → Bibliothekszugang
  kernaussage_abstract: 8 Studien (198 Teilnehmende), 9 Interventionen; HRV-gesteuerte Interventionen meist mit weniger moderaten/hohen Einheiten; positiver Effekt auf submaximale Parameter (g = 0,296; 95%-KI 0,031–0,562), kleine, nicht signifikante Effekte auf Leistung (g = 0,079) und VO2peak (g = 0,171); weniger Non-Responder bei der Leistung
  verifikation: PubMed 2026-09-29
```

### 13.2.2 T1 Ausdauer (Block bestätigt 2026-09-27)

Artikel per PubMed verifiziert am 2026-09-27 (V-11). Budget: Kern = L-A02 auszugsweise über Verweis L-T1-15, 5 Artikel, 2 Bücher (auszugsweise) ≈ 8 000–10 000 Tokens für die T1-Sammeldatei; optionale Quellen nur bei konkreter Planungsfrage.

Kern:

```yaml
- id: L-T1-01
  status: nicht_aufgenommen
  stufe: B
  typ: lehrbuch
  zitat: "Hottenrott K, Seidel I (Hrsg.). Handbuch Trainingswissenschaft – Trainingslehre. Beiträge zur Lehre und Forschung im Sport, Bd. 200. Schorndorf: Hofmann; 2., überarb. Aufl. 2025."
  isbn: 978-3-7780-4005-8 (1. Aufl. 2017: 978-3-7780-4004-1)
  sprache: de
  zweck: Begriffe, Adaptationsmodelle, Methoden des Ausdauertrainings, Periodisierung
  grund: keine digitale Ausgabe; Rolle durch L-A02 abgedeckt (Verweis L-T1-15, D-70, 2026-09-29)
  verifikation: bibliografisch (Bibliothekskataloge)
- id: L-T1-02
  status: ausgewaehlt
  datei: t1-ausdauer/L-T1-02_Seiler-2010_Intensity-and-Duration-Distribution.pdf
  stufe: A
  typ: review
  zitat: "Seiler S. What is best practice for training intensity and duration distribution in endurance athletes? Int J Sports Physiol Perform. 2010;5(3):276-291."
  doi: 10.1123/ijspp.5.3.276
  pmid: "20861519"
  zweck: Intensitätsverteilung, Ausgangspunkt polarisiert/80-20; Zonenreferenz D-27
  zugang: nicht in PMC → Beschaffung
- id: L-T1-03
  status: ausgewaehlt
  datei: t1-ausdauer/L-T1-03_Casado-2022_Periodization-Elite-Distance-Runners.pdf
  stufe: A
  typ: systematischer_review
  zitat: "Casado A, González-Mohíno F, González-Ravé JM, Foster C. Training Periodization, Methods, Intensity Distribution, and Volume in Highly Trained and Elite Distance Runners: A Systematic Review. Int J Sports Physiol Perform. 2022;17(6):820-833."
  doi: 10.1123/ijspp.2021-0435
  pmid: "35418513"
  zweck: Pyramidal vs. polarisiert je Phase, Einheitenformate Zone 2/3
  zugang: nicht in PMC; Repositorium Univ. Nebrija weist Open Access aus → prüfen
- id: L-T1-04
  status: ausgewaehlt
  datei: t1-ausdauer/L-T1-04_Haugen-2022_World-Class-Distance-Runners.pdf
  stufe: A
  typ: review
  zitat: "Haugen T, Sandbakk Ø, Seiler S, Tønnessen E. The Training Characteristics of World-Class Distance Runners: An Integration of Scientific Literature and Results-Proven Practice. Sports Med Open. 2022;8(1):46."
  doi: 10.1186/s40798-022-00438-7
  pmid: "35362850"
  pmcid: PMC8975965
  zweck: Umfang, Intensitätsverteilung, Tapering, Wochenstruktur
  zugang: Open Access (PMC), CC BY 4.0
- id: L-T1-05
  status: ausgewaehlt
  datei: t1-ausdauer/L-T1-05_Vernillo-2017_Uphill-and-Downhill-Running.pdf
  stufe: A
  typ: review
  zitat: "Vernillo G, Giandolini M, Edwards WB, Morin JB, Samozino P, Horvais N, Millet GY. Biomechanics and Physiology of Uphill and Downhill Running. Sports Med. 2017;47(4):615-629."
  doi: 10.1007/s40279-016-0605-y
  pmid: "27501719"
  zweck: Bergauf-/Bergablauf – Energiekosten, Muskelarbeit, Verletzungsrelevanz
  zugang: nicht in PMC → Beschaffung
- id: L-T1-06
  status: ausgewaehlt
  datei: t1-ausdauer/L-T1-06_Bortolan-2021_Ski-Mountaineering-Perspectives.pdf
  stufe: A
  typ: perspective
  zitat: "Bortolan L, Savoldelli A, Pellegrini B, Modena R, Sacchi M, Holmberg HC, Supej M. Ski Mountaineering: Perspectives on a Novel Sport to Be Introduced at the 2026 Winter Olympic Games. Front Physiol. 2021;12:737249."
  doi: 10.3389/fphys.2021.737249
  pmid: "34744777"
  pmcid: PMC8566874
  zweck: Skitour-Spezifik – Leistungsdeterminanten, Gewicht, Schrittmuster
  zugang: Open Access (PMC)
- id: L-T1-07
  status: ausgewaehlt
  datei: t1-ausdauer/L-T1-07_Laursen-2019_Science-and-Application-of-HIIT.pdf
  kapitel: t1-ausdauer/L-T1-07_kapitel/
  stufe: B
  typ: lehrbuch
  zitat: "Laursen P, Buchheit M (Hrsg.). Science and Application of High-Intensity Interval Training: Solutions to the Programming Puzzle. Champaign, IL: Human Kinetics; 2019."
  isbn: 978-1-4925-5212-3 (Print), 978-1-4925-8689-0 (E-Book)
  sprache: en
  zweck: Programmierung von Intervalleinheiten (Zone 3)
  zugang: Volltext vorhanden (datei, 2026-09-29; durchsuchbares PDF mit Lesezeichen, 673 S.)
  verifikation: bibliografisch (Verlag, Bibliothekskatalog)
- id: L-T1-08
  status: ausgewaehlt
  datei: t1-ausdauer/L-T1-08_House-2019_Training-for-the-Uphill-Athlete.pdf
  kapitel: t1-ausdauer/L-T1-08_kapitel/
  stufe: C
  typ: praxisquelle
  konfidenz: niedrig (D-25)
  zitat: "House S, Johnston S, Jornet K. Training for the Uphill Athlete: A Manual for Mountain Runners and Ski Mountaineers. Ventura, CA: Patagonia; 2019."
  isbn: 978-1-938340-84-0 (Paperback), 978-1-938340-85-7 (E-Book)
  sprache: en
  zweck: Bergauf-Ausdauer, Blockstruktur, Skitour-Praxis
  zugang: Kauf; E-Book-Format/DRM prüfen (V-13)
  verifikation: bibliografisch (Händler-/Bibliothekskataloge)
```

Optional (nur bei Bedarf und Tokenbudget):

```yaml
- id: L-T1-09
  status: optional
  datei: t1-ausdauer/L-T1-09_Toennessen-2024_Training-Session-Models.pdf
  stufe: C
  typ: qualitative_studie (Trainer als Informanten) → praxisquelle
  konfidenz: mittel (D-25)
  zitat: "Tønnessen E, Sandbakk Ø, Sandbakk SB, Seiler S, Haugen T. Training Session Models in Endurance Sports: A Norwegian Perspective on Best Practice Recommendations. Sports Med. 2024;54(11):2935-2953."
  doi: 10.1007/s40279-024-02067-4
  pmid: "39012575"
  pmcid: PMC11560996
  zweck: Vorlagen für Einheiten (Intervallformate, Dauer)
  zugang: Open Access (PMC)
- id: L-T1-10
  status: optional
  datei: t1-ausdauer/L-T1-10_Sandbakk-2025_Best-Practice-Norwegian-Coaches.pdf
  stufe: C
  typ: multiple_case_study (Trainer) → praxisquelle
  konfidenz: mittel (D-25)
  zitat: "Sandbakk Ø, Tønnessen E, Sandbakk SB, Losnegard T, Seiler S, Haugen T. Best-Practice Training Characteristics Within Olympic Endurance Sports as Described by Norwegian World-Class Coaches. Sports Med Open. 2025;11(1):45."
  doi: 10.1186/s40798-025-00848-3
  pmid: "40278987"
  pmcid: PMC12031707
  zweck: Wochenstruktur, Key-Workout-Tage, Periodisierung
  zugang: Open Access (PMC)
- id: L-T1-11
  status: optional
  stufe: A
  typ: review
  zitat: "Giandolini M, Vernillo G, Samozino P, Horvais N, Edwards WB, Morin JB, Millet GY. Fatigue associated with prolonged graded running. Eur J Appl Physiol. 2016;116(10):1859-1873."
  doi: 10.1007/s00421-016-3437-4
  pmid: "27456477"
  zweck: Ermüdung/Muskelschaden bergab, Trail-Belastung
  zugang: nicht in PMC → Beschaffung
- id: L-T1-12
  status: optional
  datei: t1-ausdauer/L-T1-12_Joyner-2008_Physiology-of-Champions.pdf
  stufe: A
  typ: review
  zitat: "Joyner MJ, Coyle EF. Endurance exercise performance: the physiology of champions. J Physiol. 2008;586(1):35-44."
  doi: 10.1113/jphysiol.2007.143834
  pmid: "17901124"
  pmcid: PMC2375555
  zweck: Leistungsdeterminanten (VO2max, Schwelle, Ökonomie)
  zugang: Open Access (PMC)
- id: L-T1-13
  status: verweis
  verweis: L-A01
  hinweis: Kenney/Wilmore/Costill ist übergreifend bereits ausgewählt; Block T1 meldet 9. Aufl. 2024 (nur Händlerangabe) – Auflage beim Erwerb klären
- id: L-T1-14
  status: optional
  stufe: B
  typ: lehrbuch
  zitat: "Neumann G, Pfützner A, Berbalk A. Optimiertes Ausdauertraining. Aachen: Meyer & Meyer."
  sprache: de
  zweck: Nur falls GA-Terminologie gemappt werden muss (D-27)
  zugang: Kauf; Auflage/ISBN beim Erwerb prüfen
  verifikation: teilweise (Verlagsleseprobe)
- id: L-T1-15
  status: verweis
  verweis: L-A02
  rolle: T1-Lehrbuch (ersetzt L-T1-01, D-70)
  kapitel_t1: >-
    Abschn. 2.2.5 Belastungsverteilung (S. 41 ff.); 2.3.6 Periodisierung und Zyklisierung (S. 53 f.);
    2.3.7 Belastung und Erholung inkl. Exkurs „Superkompensation ist die Erdscheibe der Trainingslehre!“
    (ab S. 55); 2.4 Anpassungsprozesse (ab S. 63; 2.4.2 antagonistische Modelle S. 66, 2.4.3 komplexe
    Konzepte S. 67, 2.4.6 Übertrainingssyndrom S. 73); 3.4.9 Ausdauerdiagnostik (ab S. 155);
    3.5 Trainingssteuerung (ab S. 172; 3.5.3 Belastungsdosierung S. 182, 3.5.4 Monitoring S. 185);
    Kap. 7 Ausdauertraining (ab S. 397; 7.3 Anpassungseffekte S. 423, 7.4 Methoden und
    Belastungsdosierung S. 431, 7.4.7 Höhentraining S. 443, 7.4.8 Belastungsdosierung S. 447,
    7.5 Trainingszonen und -programme S. 456)
  hinweis: Seitenzahlen = Druckseiten laut Inhaltsverzeichnis (L-A02_00_Vorspann.pdf). Periodisierung in L-A02 nur knapp; tragend L-P01, L-P02, L-A03 Kap. 22, L-T1-03, L-T1-08. Kap. 13 (Ausdauersportarten) behandelt nur Schwimmen und Triathlon.
- id: L-T1-16
  status: optional
  datei: t1-ausdauer/L-T1-16_Koop-2021_Training-Essentials-for-Ultrarunning_2ed.epub
  kapitel: t1-ausdauer/L-T1-16_kapitel/
  stufe: C
  typ: praxisquelle
  konfidenz: niedrig (D-25)
  zitat: "Koop J, Rutberg J, Malcolm C. Training Essentials for Ultrarunning. 2. Aufl. Colorado Springs: Koop Endurance Services; 2021."
  isbn: 979-8-9850948-0-0
  umfang: 518 S.; nach Angabe einer Rezension über 400 wissenschaftliche Zitate
  sprache: en
  zweck: Trail-Spezifik als Ergänzung zu L-T1-08 – Bergauf-/Bergablaufen, Krafttraining für Trailläufer, Hitze und Höhe, Langzeitplanung
  einschraenkung: Ultra-Fokus (Wettkampfdistanzen über Marathon) – Übertragung auf den Athleten unter „Grenzen“ (D-25); nie alleiniger Beleg für Belastungsparameter (D-31); Zonenangaben auf D-27 abbilden
  zugang: EPUB ohne DRM vorhanden (2026-09-29, Scribd-Konvertierung ohne ISBN in den Metadaten); Kapitel-Markdown und Ansichts-PDFs (D-71)
  seitenbezug: keine Seitenmarken → Zitat mit Kapitel und Abschnitt (D-71)
  verifikation: Bibliothekskatalog (UTB Zlín), Verlags-/Autorenseite trainright.com 2026-09-29
  bestaetigt: Athlet 2026-09-29 (SW-E3)
```

### 13.2.3 T2 Kraft/Haltung (Block Kraft/Calisthenics bestätigt 2026-09-27; Haltung/Rücken bestätigt 2026-09-28, D-54; Hypertrophie-Ergänzung bestätigt 2026-09-28, D-62)

Kernset und Regeln in D-28 bis D-30; Haltung/Rücken in D-54; Hypertrophie-Ergänzung in D-62 (D-28 unverändert).

```yaml
- id: L-T2-01
  status: verweis
  verweis: L-P08
  rolle: Anker Dosierung (ACSM Position Stand 2026), stufe A, konfidenz hoch
- id: L-T2-02
  status: verweis
  verweis: L-A03
  rolle: NSCA Essentials – Grundlagen, Programmgestaltung, Testung, Technik
- id: L-T2-03
  status: ausgewaehlt
  datei: t2-kraft/L-T2-03_Schumann-2019_Concurrent-Aerobic-and-Strength-Training.pdf
  kapitel: t2-kraft/L-T2-03_kapitel/
  stufe: B
  typ: lehrbuch (Herausgeberwerk)
  zitat: "Schumann M, Rønnestad BR (Hrsg.). Concurrent Aerobic and Strength Training: Scientific Basics and Practical Applications. Cham: Springer; 2019."
  doi: 10.1007/978-3-319-75547-2
  sprache: en
  zweck: Kombination Ausdauer + Kraft, Interferenz; zentral für T1–T3 im gemeinsamen Wochenplan
  zugang: Springer, kapitelweise PDF (V-13)
- id: L-T2-04
  status: ausgewaehlt
  datei: t2-kraft/L-T2-04_Low-2016_Overcoming-Gravity_2ed.pdf
  kapitel: t2-kraft/L-T2-04_kapitel/
  stufe: C
  typ: praxisquelle
  konfidenz: niedrig (D-29)
  zitat: "Low S. Overcoming Gravity: A Systematic Approach to Gymnastics and Bodyweight Strength. 2. Aufl. 2016."
  isbn: 9780990873853
  sprache: en
  zweck: Übungskatalog Calisthenics mit Progressionsleitern (Abschnitt `uebungskatalog_calisthenics` der T2-Sammeldatei); Dosierung ausschließlich aus Kernset
  zugang: Kauf; Digitalausgabe PDF/ePUB beim Autor prüfen (V-13)
- id: L-T2-05
  status: optional
  stufe: B
  typ: lehrbuch
  zitat: "Zatsiorsky VM, Kraemer WJ, Fry AC. Science and Practice of Strength Training. 3. Aufl. Human Kinetics; 2021."
  sprache: en
  zweck: Vertiefung Dosierung/Monitoring, einzelne Kapitel
  zugang: Human Kinetics, VitalSource-DRM (V-13)
- id: L-T2-06
  status: optional
  stufe: B
  typ: lehrbuch
  zitat: "Güllich A, Krüger M (Hrsg.). Sport – Das Lehrbuch für das Sportstudium. 2. Aufl. Springer; 2022."
  doi: 10.1007/978-3-662-64695-3
  sprache: de
  zweck: Deutsche Fachterminologie
  zugang: Springer, kapitelweise PDF
- id: L-T2-07
  status: zurueckgestellt
  stufe: B
  typ: lehrbuch
  zitat: "Schoenfeld BJ. Science and Development of Muscle Hypertrophy. 3. Aufl. Champaign, IL: Human Kinetics; ©2027."
  isbn: 9781718258839 (Hardback, 368 S.), 9781718258846 (E-Book epub, 344 S.); 2. Aufl. 2021 – 978-1-4925-9767-4
  sprache: en
  zweck: Hypertrophie (kein Primärziel, D-28); bleibt auch nach der Ergänzung D-62 zurückgestellt
  erscheinen: laut Verlagsseite Human Kinetics (US) 23.10.2026; ein australischer Vertrieb nennt lokal 23.01.2027
  zugang: Kauf nur bei Aktivierung; epub-Format und DRM vor Kauf prüfen (D-26, V-13)
  verifikation: Verlagsseite Human Kinetics und Händlerkatalog 2026-09-28; 2. Aufl. Bibliothekskatalog
- id: L-T2-08
  status: verifiziert
  datei: t2-kraft/L-T2-08_Kotarsky-2018_Progressive-Push-up-Training.pdf
  stufe: A
  typ: interventionsstudie
  zitat: "Kotarsky CJ, Christensen BK, Miller JS, Hackney KJ. Effect of Progressive Calisthenic Push-up Training on Muscle Strength and Thickness. J Strength Cond Res. 2018;32(3):651-659."
  doi: 10.1519/JSC.0000000000002345
  zweck: Liegestütz-Progression ≈ Bankdrücken (n = 23, 4 Wochen); Beleg für Calisthenics-Wirksamkeit (D-29)
- id: L-T2-09
  status: verifiziert
  datei: t2-kraft/L-T2-09_vandenTillaar-2019_Push-up-vs-Bench-Press.pdf
  stufe: A
  typ: studie (akut)
  zitat: "van den Tillaar R. Comparison of Kinematics and Muscle Activation between Push-up and Bench Press. Sports Med Int Open. 2019;3(3):E74-E81."
  doi: 10.1055/a-1001-2526
  zweck: Liegestütz mit Weste ≈ Bankdrücken in Kinematik/EMG (D-29)
- id: L-T2-10
  status: verifiziert
  datei: t2-kraft/L-T2-10_Wiedenmann-2025_Resistance-Training-Modalities-Older-Adults.pdf
  stufe: A
  typ: netzwerk_metaanalyse
  zitat: "Wiedenmann T, Held S, Morat T, Rappelt L, Isenmann E, Berndsen E, Hopp NH, Donath L. The Effects of Different Resistance Training Modalities on Muscle Strength in Community-Dwelling Older Adults: A Network Meta-Analysis. Gerontology. 2025;71(7):576-588."
  doi: 10.1159/000546346
  zweck: Körpergewichtstraining wirksam, kleinster Effekt; Population Ältere – Übertragung eingeschränkt (D-29)
- id: L-T2-11
  status: ausgewaehlt
  datei: t2-kraft/L-T2-11_Ronnestad-2014_Strength-Training-Running-and-Cycling.pdf
  stufe: A
  typ: review
  zitat: "Rønnestad BR, Mujika I. Optimizing strength training for running and cycling endurance performance: A review. Scand J Med Sci Sports. 2014;24(4):603-12."
  pmid: "23914932"
  doi: 10.1111/sms.12104
  zugang: kein PMC-Volltext → Beschaffung
  zweck: Krafttraining für Lauf-/Radleistung (T2 als Ergänzung zu T1)
  kernaussage_abstract: Laufökonomie verbessert durch schweres oder explosives Krafttraining; Effekte auf Schwellenleistung uneinheitlich
  hinweis: Online-Vorabveröffentlichung 2013, Heft 2014
  verifikation: PubMed 2026-09-28 (V-14)
- id: L-T2-12
  status: ausgewaehlt
  datei: t2-kraft/L-T2-12_Blagrove-2018_Strength-Training-Distance-Running.pdf
  stufe: A
  typ: systematischer_review
  zitat: "Blagrove RC, Howatson G, Hayes PR. Effects of Strength Training on the Physiological Determinants of Middle- and Long-Distance Running Performance: A Systematic Review. Sports Med. 2018;48(5):1117-1149."
  pmid: "29249083"
  pmcid: PMC5889786
  doi: 10.1007/s40279-017-0835-7
  zugang: Open Access, CC BY 4.0 (PMC) – Volltext im Repo zulässig (D-31)
  zweck: Krafttraining bei Mittel-/Langstreckenläufern
  kernaussage_abstract: 24 Studien; Laufökonomie meist +2–8 %; Zeitfahrleistung und Sprint tendenziell besser; VO2max und Körperzusammensetzung unverändert; Empfehlung 2–3 Krafteinheiten/Woche mit gemischten Modalitäten
  verifikation: PubMed 2026-09-28 (V-14)
- id: L-T2-13
  status: zurueckgestellt
  stufe: C
  typ: praxisquelle
  zitat: "McGill S. Back Mechanic / Ultimate Back Fitness and Performance"
  zweck: nur Übungsbeispiele (gekennzeichnet); Dosierung aus L-T2-15 bis L-T2-18 (D-54)
- id: L-T2-14
  status: optional
  datei: t2-kraft/L-T2-14_Cowley-2026_Advanced-Resistance-Training-Methods.pdf
  stufe: A
  typ: systematischer_review_netzwerk_metaanalyse
  zitat: "Cowley N, Nicholson V, Timmins R, Munteanu G, Weakley J. The Effects of Advanced Resistance Training Prescription Methods on Strength, Power, Hypertrophy, and Performance Adaptations in Healthy Adults: A Systematic Review and Bayesian Network Meta-analysis. Sports Med. 2026;56(8):1955-1977."
  doi: 10.1007/s40279-026-02428-1
  pmid: "41951916"
  pmcid: PMC13457283
  zugang: Volltext in PMC
  zweck: Beleg für einfache, klassische Programmierung – fortgeschrittene Methoden (u. a. Cluster/Rest-Redistribution, Flywheel) ohne klaren Vorteil für Kraft, Schnellkraft, Hypertrophie bei untrainierten bis mäßig trainierten Personen
  verifikation: PubMed 2026-09-28
- id: L-T2-15
  status: ausgewaehlt
  datei: t2-kraft/L-T2-15_Warneke-2024_Stretching-or-Strengthening-Posture.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Warneke K, Lohmann LH, Wilke J. Effects of Stretching or Strengthening Exercise on Spinal and Lumbopelvic Posture: A Systematic Review with Meta-Analysis. Sports Med Open. 2024;10(1):65."
  doi: 10.1186/s40798-024-00733-5
  pmid: "38834878"
  pmcid: PMC11150224
  zugang: Volltext in PMC; Lizenz laut PubMed nicht ausgewiesen (vor Ablage im Repo prüfen); laut Volltext CC BY 4.0 (2026-09-29)
  themenfelder: [haltung, kraeftigung, dehnung]
  kernaussagen_abstract: 23 Studien, 969 gesunde Teilnehmer; Dehnen akut (d = 0,01) und chronisch (d = −0,19) ohne Effekt auf Haltung; chronische Kräftigung große Verbesserung (d = −0,83); Kräftigung Dehnen überlegen (d = 0,81); wirksam an BWS/HWS (d = −1,04), nicht an LWS/Becken (d = −0,23); Evidenzsicherheit moderat (GRADE)
  rolle: Anker Haltung (D-54)
  verifikation: PubMed 2026-09-28
- id: L-T2-16
  status: ausgewaehlt
  datei: t2-kraft/L-T2-16_Khorramroo-2026_Corrective-Exercises-Posture.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Khorramroo F, Rostami M, Bafrouei MJ. Corrective exercises strongly improve posture but fail to produce consistent clinical or functional benefits in patients with upper crossed syndrome: a systematic review and meta-analysis of randomized controlled trials. BMC Sports Sci Med Rehabil. 2026;18:302."
  doi: 10.1186/s13102-026-01707-8
  pmid: "42210327"
  pmcid: PMC13326462
  zugang: Volltext in PMC; Lizenz laut PubMed nicht ausgewiesen; laut Volltext CC BY 4.0 (2026-09-29)
  themenfelder: [haltung, vorkopfhaltung, kyphose]
  kernaussagen_abstract: 28 RCTs (n = 901); Korrekturübungen verbessern Vorkopfwinkel (SMD −1,49), Schulterwinkel (SMD −1,53), Kyphosewinkel (SMD −1,70) bei hoher Heterogenität; Effekte auf Muskelaktivierung, Funktion, Gleichgewicht und Schmerz nicht schlüssig
  hinweis: Artikelnummer in PubMed nicht hinterlegt – bei Beschaffung ergänzen
  verifikation: PubMed 2026-09-28
- id: L-T2-17
  status: ausgewaehlt
  datei: t2-kraft/L-T2-17_Shiri-2018_Exercise-Prevention-Low-Back-Pain.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Shiri R, Coggon D, Falah-Hassani K. Exercise for the Prevention of Low Back Pain: Systematic Review and Meta-Analysis of Controlled Trials. Am J Epidemiol. 2018;187(5):1093-1101."
  doi: 10.1093/aje/kwx337
  pmid: "29053873"
  zugang: Volltext vorhanden (datei, 2026-09-28)
  themenfelder: [rumpf, praevention_kreuzschmerz]
  kernaussagen_abstract: Training allein senkt Kreuzschmerz-Risiko um 33 % (RR 0,67), mit Aufklärung um 27 % (RR 0,73); Schwere und Beeinträchtigung geringer; Empfehlung Kräftigung kombiniert mit Dehnung oder Ausdauer, 2–3× pro Woche
  rolle: Dosierung Prävention (D-54)
  verifikation: PubMed 2026-09-28
- id: L-T2-18
  status: ausgewaehlt
  datei: t2-kraft/L-T2-18_Steffens-2016_Prevention-of-Low-Back-Pain.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Steffens D, Maher CG, Pereira LSM, Stevens ML, Oliveira VC, Chapple M, Teixeira-Salmela LF, Hancock MJ. Prevention of Low Back Pain: A Systematic Review and Meta-analysis. JAMA Intern Med. 2016;176(2):199-208."
  doi: 10.1001/jamainternmed.2015.7431
  pmid: "26752509"
  zugang: Volltext vorhanden (datei, 2026-09-28)
  themenfelder: [rumpf, praevention_kreuzschmerz]
  kernaussagen_abstract: 21 RCTs, 30 850 Teilnehmer; Training + Aufklärung senkt Risiko einer Kreuzschmerz-Episode (RR 0,55, moderate Evidenz); Training allein RR 0,65 (niedrige bis sehr niedrige Evidenz); Aufklärung allein, Rückengurte, Einlagen ohne Effekt
  verifikation: PubMed 2026-09-28
- id: L-T2-19
  status: optional
  datei: t2-kraft/L-T2-19_Carrasco-Uribarren-2026_Therapeutic-Exercise-Forward-Head-Posture.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Carrasco-Uribarren A, Ceballos-Laita L, Pérez-Guillén S, Jiménez-Del-Barrio S, Pantaleón-Hernández D, Cabanillas-Barea S. Impact of therapeutic exercise on craniovertebral angle in forward head posture: a systematic review and meta-analysis. J Man Manip Ther. 2026:1-12."
  doi: 10.1080/10669817.2026.2686739
  pmid: "42281352"
  zugang: kein PMC-Volltext; Band/Heft noch nicht vergeben (Online-Vorabveröffentlichung)
  themenfelder: [haltung, vorkopfhaltung]
  kernaussagen_abstract: 13 RCTs (n = 819); Nacken-Übungen allein und kombiniert mit BWS-Übungen verbessern kurzfristig Kraniovertebralwinkel und Nackenbeeinträchtigung; nur die Kombination senkt Schmerz; Evidenzsicherheit niedrig bis sehr niedrig
  zweck: Begründung für Kombination HWS + BWS in Haltungseinheiten
  verifikation: PubMed 2026-09-28
```

Hypertrophie-Ergänzung (D-62): Alle Einträge per PubMed verifiziert am 2026-09-28; Kernaussagen aus den Abstracts, Prüfung am Volltext in der Kartensitzung (13.1 Schritt 3).

Kern – Dosierung:

```yaml
- id: L-T2-20
  status: ausgewaehlt
  datei: t2-kraft/L-T2-20_Pelland-2026_Resistance-Training-Dose-Response.pdf
  stufe: A
  typ: systematischer_review_metaregression
  zitat: "Pelland JC, Remmert JF, Robinson ZP, Hinson SR, Zourdos MC. The Resistance Training Dose Response: Meta-Regressions Exploring the Effects of Weekly Volume and Frequency on Muscle Hypertrophy and Strength Gains. Sports Med. 2026;56(2):481-505."
  doi: 10.1007/s40279-025-02344-w
  pmid: "41343037"
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [hypertrophie, volumen, frequenz]
  kernaussagen_abstract: "67 Studien, 2 058 Teilnehmende (79 % Männer, im Mittel 25 Jahre). Muskelgröße und Kraft steigen mit dem wöchentlichen Satzvolumen, jeweils mit abnehmendem Grenznutzen (bei Kraft deutlich stärker). Für Hypertrophie ist die Frequenz mit einem vernachlässigbaren Effekt vereinbar; die Kraft steigt mit der Frequenz. Indirekte Sätze werden am besten als halber Satz gezählt („fraktional“)."
  rolle: Dosis-Wirkung Volumen und Frequenz (D-62 a)
  hinweis: Online-Vorabveröffentlichung 12/2025, Heft 2026 – Zitierjahr 2026; Population überwiegend junge Männer; möglicherweise in L-P08 enthalten (V-16)
  verifikation: PubMed 2026-09-28
- id: L-T2-21
  status: ausgewaehlt
  datei: t2-kraft/L-T2-21_Robinson-2024_Proximity-to-Failure-Dose-Response.pdf
  stufe: A
  typ: metaregression
  konfidenz: mittel – explorativ, RIR aus Studienbeschreibungen geschätzt, mäßige Modellgüte
  zitat: "Robinson ZP, Pelland JC, Remmert JF, Refalo MC, Jukic I, Steele J, Zourdos MC. Exploring the Dose-Response Relationship Between Estimated Resistance Training Proximity to Failure, Strength Gain, and Muscle Hypertrophy: A Series of Meta-Regressions. Sports Med. 2024;54(9):2209-2231."
  doi: 10.1007/s40279-024-02069-2
  pmid: "38970765"
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [hypertrophie, naehe_muskelversagen]
  kernaussagen_abstract: "Die Hypertrophie nimmt zu, je näher am Muskelversagen die Sätze enden (negative Steigung für RIR, Konfidenzintervall ohne Null). Der Kraftzuwachs ist über einen weiten RIR-Bereich ähnlich. Die Modelle sind adjustiert für Last, Art des Volumenausgleichs, Dauer und Trainingsstatus."
  rolle: Dosis-Wirkung Nähe zum Muskelversagen (D-62 b)
  verifikation: PubMed 2026-09-28
- id: L-T2-22
  status: ausgewaehlt
  datei: t2-kraft/L-T2-22_Refalo-2023_Proximity-to-Failure-Hypertrophy.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Refalo MC, Helms ER, Trexler ET, Hamilton DL, Fyfe JJ. Influence of Resistance Training Proximity-to-Failure on Skeletal Muscle Hypertrophy: A Systematic Review with Meta-analysis. Sports Med. 2023;53(3):649-665."
  doi: 10.1007/s40279-022-01784-y
  pmid: "36334240"
  pmcid: PMC9935748
  zugang: Volltext in PMC; Lizenz laut PubMed nicht ausgewiesen (© Autoren); laut Volltext CC BY 4.0 (2026-09-29)
  themenfelder: [hypertrophie, naehe_muskelversagen]
  kernaussagen_abstract: "15 Studien. Satzversagen (jede Definition) gegenüber keinem Versagen bringt einen trivialen Vorteil (ES 0,19; 95%-KI 0,00–0,37), unabhängig von Volumenlast und relativer Last. Momentanes Muskelversagen gegenüber keinem Versagen bringt keinen Vorteil (ES 0,12; −0,13 bis 0,37). Hohe (> 25 %) und moderate (20–25 %) Geschwindigkeitsverlustschwellen unterscheiden sich nicht. Die Autoren sehen Hinweise auf eine nichtlineare Beziehung."
  rolle: Versagen nicht nötig (D-62 b)
  hinweis: Online 11/2022, Heft 2023 – Zitierjahr 2023; möglicherweise in L-P08 enthalten (V-16)
  verifikation: PubMed 2026-09-28
```

Kern – Heimtraining mit leichten Lasten und Band:

```yaml
- id: L-T2-23
  status: ausgewaehlt
  datei: t2-kraft/L-T2-23_Lopez-2021_Training-Load-Hypertrophy-Strength.pdf
  corrigendum_datei: t2-kraft/L-T2-23_Lopez-2022_Corrigendum.pdf
  stufe: A
  typ: systematischer_review_netzwerk_metaanalyse
  zitat: "Lopez P, Radaelli R, Taaffe DR, Newton RU, Galvão DA, Trajano GS, Teodoro JL, Kraemer WJ, Häkkinen K, Pinto RS. Resistance Training Load Effects on Muscle Hypertrophy and Strength Gain: Systematic Review and Network Meta-analysis. Med Sci Sports Exerc. 2021;53(6):1206-1216."
  doi: 10.1249/MSS.0000000000002585
  pmid: "33433148"
  pmcid: PMC8126497
  corrigendum: "Med Sci Sports Exerc. 2022;54(2):370. PMID 35029596, DOI 10.1249/MSS.0000000000002838 – Inhalt geprüft 2026-09-29 (korrigierte Effektstärken dreier Studien in Abb. 4, Hauptbefunde unverändert)"
  zugang: Volltext in PMC; Lizenz laut PubMed nicht ausgewiesen (© Autoren); laut Volltext CC BY-NC-ND 4.0 (2026-09-29); Corrigendum als eigene Datei (corrigendum_datei) – korrigierte Effektstärken dreier Studien in Abb. 4, Hauptbefunde unverändert
  themenfelder: [hypertrophie, last, maximalkraft]
  kernaussagen_abstract: "28 Studien, 747 gesunde Erwachsene, nur Sätze bis zum willentlichen Versagen. Die Hypertrophie unterscheidet sich nicht zwischen niedriger (> 15 RM), mittlerer (9–15 RM) und hoher Last (≤ 8 RM). Die Kraft steigt bei hoher und mittlerer Last stärker als bei niedriger (SMD 0,60–0,63 bzw. 0,34–0,35). Untrainierte zeigen größere Hypertrophie."
  rolle: Hauptbeleg für leichte Lasten im Heimtraining unter der Voraussetzung, dass die Sätze nahe ans Versagen gehen (D-62 c); Übertragung auf Band/Kettlebell ist ein Schluss (D-62 e)
  hinweis: möglicherweise in L-P08 enthalten (V-16)
  verifikation: PubMed 2026-09-28
- id: L-T2-24
  status: ausgewaehlt
  datei: t2-kraft/L-T2-24_Lopes-2019_Elastic-vs-Conventional-Resistance.pdf
  corrigendum_datei: t2-kraft/L-T2-24_Lopes-2020_Corrigendum.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Lopes JSS, Machado AF, Micheletti JK, de Almeida AC, Cavina AP, Pastre CM. Effects of training with elastic resistance versus conventional resistance on muscular strength: A systematic review and meta-analysis. SAGE Open Med. 2019;7:2050312119831116."
  doi: 10.1177/2050312119831116
  pmid: "30815258"
  pmcid: PMC6383082
  corrigendum: "SAGE Open Med. 2020;8:2050312120961220. PMID 32953119, DOI 10.1177/2050312120961220 – das PubMed-Abstract enthält die korrigierten Werte"
  zugang: Open Access, CC BY-NC 4.0 (PMC) – Volltext im Repo zulässig (D-31); Corrigendum als eigene Datei (corrigendum_datei, CC BY-NC 4.0) – korrigiert einen Textabschnitt der Diskussion
  themenfelder: [elastischer_widerstand, maximalkraft]
  kernaussagen_abstract: "8 Studien, Suche bis 12/2017, verschiedene Populationen. Elastischer Widerstand (Schläuche, TheraBand) und Geräte/Hanteln unterscheiden sich nicht bei der Kraft der unteren (SMD −0,11; −0,40 bis 0,19) und der oberen Extremität (SMD 0,09; −0,18 bis 0,35)."
  grenze: nur Kraft, keine Hypertrophie-Endpunkte
  rolle: Band ist für Kraft gleichwertig (D-62 c)
  verifikation: PubMed 2026-09-28; Lizenz über PMC
```

Kern – Interferenz mit Ausdauer:

```yaml
- id: L-T2-25
  status: ausgewaehlt
  datei: t2-kraft/L-T2-25_Lundberg-2022_Concurrent-Training-Fiber-Hypertrophy.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Lundberg TR, Feuerbacher JF, Sünkeler M, Schumann M. The Effects of Concurrent Aerobic and Strength Training on Muscle Fiber Hypertrophy: A Systematic Review and Meta-Analysis. Sports Med. 2022;52(10):2391-2403."
  doi: 10.1007/s40279-022-01688-x
  pmid: "35476184"
  pmcid: PMC9474354
  zugang: Volltext in PMC; Lizenz laut PubMed nicht ausgewiesen (© Autoren); laut Volltext CC BY 4.0 (2026-09-29)
  themenfelder: [interferenz, faserhypertrophie]
  kernaussagen_abstract: "15 Studien, kombiniertes Training gegenüber Krafttraining allein. Faserhypertrophie gesamt SMD −0,23 (95%-KI −0,46 bis −0,00; p = 0,050); Typ I −0,34 und Typ II −0,13 (beide n. s.). Nachteil bei Typ-I-Fasern, wenn die Ausdauer gelaufen wird (SMD −0,81; −1,26 bis −0,36), nicht bei Radfahren. Frequenz, Trainingsstatus, Trainingsmodalität und Reihenfolge machen keinen Unterschied."
  konfidenz: hoch für den Gesamtbefund; Subgruppe Laufen laut Autoren vorläufig
  bezug: gleiche Arbeitsgruppe wie L-P07; ergänzt deren Befunde zum ganzen Muskel um die Faserebene
  rolle: Interferenz, relevant für Trailrunning (D-62 d)
  verifikation: PubMed 2026-09-28
- id: L-T2-26
  status: ausgewaehlt
  datei: t2-kraft/L-T2-26_Monserda-Vilaro-2023_Concurrent-Continuous-vs-Intermittent.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  konfidenz: mittel – auch nicht randomisierte Studien eingeschlossen
  zitat: "Monserdà-Vilaró A, Balsalobre-Fernández C, Hoffman JR, Alix-Fages C, Jiménez SL. Effects of Concurrent Resistance and Endurance Training Using Continuous or Intermittent Protocols on Muscle Hypertrophy: Systematic Review With Meta-Analysis. J Strength Cond Res. 2023;37(3):688-709."
  doi: 10.1519/JSC.0000000000004304
  pmid: "36508686"
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [interferenz, faserhypertrophie, hypertrophie]
  kernaussagen_abstract: "25 Studien (randomisiert und nicht randomisiert). Beim ganzen Muskel kein Unterschied zwischen Krafttraining allein und kombiniertem Training (SMD < 0,03). Faserhypertrophie Typ I und II größer bei Krafttraining allein, wenn der Ausdauerteil nur HIIT (SMD > 0,33) oder HIIT plus kontinuierliche Ausdauer (SMD > 0,27) enthält, nicht bei nur kontinuierlicher Ausdauer (SMD < 0,16)."
  rolle: Interferenz abhängig von der Ausdauerform (D-62 d)
  hinweis: Online 11/2022, Heft 2023 – Zitierjahr 2023
  verifikation: PubMed 2026-09-28
```

Optional:

```yaml
- id: L-T2-27
  status: optional
  datei: t2-kraft/L-T2-27_Schoenfeld-2019_Training-Frequency-Hypertrophy.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Schoenfeld BJ, Grgic J, Krieger J. How many times per week should a muscle be trained to maximize muscle hypertrophy? A systematic review and meta-analysis of studies examining the effects of resistance training frequency. J Sports Sci. 2019;37(11):1286-1295."
  doi: 10.1080/02640414.2018.1555906
  pmid: "30558493"
  zugang: kein PMC-Volltext
  themenfelder: [hypertrophie, frequenz]
  kernaussagen_abstract: "25 Studien. Bei gleichem Volumen kein relevanter Frequenzeffekt auf die Hypertrophie, auch bei Trainierten und getrennt für Ober- und Unterkörper. Ohne Volumenausgleich moderater Vorteil höherer Frequenz (1 vs. ≥ 3 Tage/Woche)."
  hinweis: Online 12/2018, Heft 2019; durch L-T2-20 weitgehend abgedeckt
  verifikation: PubMed 2026-09-28
- id: L-T2-28
  status: optional
  datei: t2-kraft/L-T2-28_Refalo-2021_Training-Load-Hypertrophy.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Refalo MC, Hamilton DL, Paval DR, Gallagher IJ, Feros SA, Fyfe JJ. Influence of resistance training load on measures of skeletal muscle hypertrophy and improvements in maximal strength and neuromuscular task performance: A systematic review and meta-analysis. J Sports Sci. 2021;39(15):1723-1745."
  doi: 10.1080/02640414.2021.1898094
  pmid: "33874848"
  zugang: kein PMC-Volltext
  themenfelder: [hypertrophie, faserhypertrophie, last, maximalkraft]
  kernaussagen_abstract: "45 Studien. Höhere (> 60 % 1RM bzw. < 15 RM) und niedrigere Last führen zu ähnlicher Hypertrophie auf Ganzkörper-, Ganzmuskel- und Faserebene. Höhere Last ist besser für 1RM- und isometrische Kraft; der Vorteil bei 1RM ist bei Jüngeren größer."
  zweck: Ergänzung zu L-T2-23 (mehr Studien, Faserebene)
  verifikation: PubMed 2026-09-28
- id: L-T2-29
  status: optional
  datei: t2-kraft/L-T2-29_Carvalho-2022_Volume-Matched-Loads-Hypertrophy.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Carvalho L, Junior RM, Barreira J, Schoenfeld BJ, Orazem J, Barroso R. Muscle hypertrophy and strength gains after resistance training with different volume-matched loads: a systematic review and meta-analysis. Appl Physiol Nutr Metab. 2022;47(4):357-368."
  doi: 10.1139/apnm-2021-0515
  pmid: "35015560"
  zugang: kein PMC-Volltext; vorliegend nur das akzeptierte Autorenmanuskript (APNM, Manuskript R2, 58 S.) – Seitenzahlen nicht die der Zeitschrift, Zitat mit Abschnitt
  themenfelder: [hypertrophie, last, maximalkraft]
  kernaussagen_abstract: "Bei gleicher Volumenlast (Sätze × Wiederholungen × Gewicht) kein Unterschied in der Hypertrophie zwischen sehr niedriger, niedriger, mittlerer und hoher Last; 1RM bei hoher Last besser."
  zweck: Last bei gleichem Volumen (Gegenstück zu L-T2-23 mit Versagen)
  verifikation: PubMed 2026-09-28
- id: L-T2-30
  status: optional
  datei: t2-kraft/L-T2-30_Grgic-2022_Failure-vs-Non-Failure.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Grgic J, Schoenfeld BJ, Orazem J, Sabol F. Effects of resistance training performed to repetition failure or non-failure on muscular strength and hypertrophy: A systematic review and meta-analysis. J Sport Health Sci. 2022;11(2):202-211."
  doi: 10.1016/j.jshs.2021.01.007
  pmid: "33497853"
  pmcid: PMC9068575
  zugang: Volltext in PMC; Lizenz laut PubMed nicht ausgewiesen (Elsevier)
  themenfelder: [hypertrophie, naehe_muskelversagen]
  kernaussagen_abstract: "15 Studien, junge Erwachsene. Versagen und kein Versagen unterscheiden sich nicht bei Kraft (ES −0,09) und Hypertrophie (ES 0,22; −0,11 bis 0,55). Ohne Volumenausgleich Kraftvorteil ohne Versagen. Bei Trainierten kleiner Hypertrophievorteil mit Versagen (ES 0,15)."
  hinweis: Online 01/2021, Heft 2022
  verifikation: PubMed 2026-09-28
- id: L-T2-31
  status: optional
  datei: t2-kraft/L-T2-31_Wilson-2012_Concurrent-Training-Interference.pdf
  stufe: A
  typ: metaanalyse
  zitat: "Wilson JM, Marin PJ, Rhea MR, Wilson SM, Loenneke JP, Anderson JC. Concurrent training: a meta-analysis examining interference of aerobic and resistance exercises. J Strength Cond Res. 2012;26(8):2293-2307."
  doi: 10.1519/JSC.0b013e31823a3e2d
  pmid: "22002517"
  zugang: kein PMC-Volltext
  themenfelder: [interferenz, hypertrophie]
  kernaussagen_abstract: "21 Studien, 422 Effektstärken. Laufen, nicht Radfahren, geht mit Einbußen bei Hypertrophie und Kraft einher. Frequenz und Dauer der Ausdauer sind negativ mit Hypertrophie, Kraft und Schnellkraft korreliert."
  hinweis: älter; durch L-P07, L-P09 und L-T2-25 im Wesentlichen abgelöst
  verifikation: PubMed 2026-09-28
- id: L-T2-32
  status: optional
  datei: t2-kraft/L-T2-32_Sabag-2018_Concurrent-HIIT-and-Resistance.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Sabag A, Najafi A, Michael S, Esgin T, Halaki M, Hackett D. The compatibility of concurrent high intensity interval training and resistance training for muscular strength and hypertrophy: a systematic review and meta-analysis. J Sports Sci. 2018;36(21):2472-2483."
  doi: 10.1080/02640414.2018.1464636
  pmid: "29658408"
  zugang: kein PMC-Volltext
  themenfelder: [interferenz, hypertrophie, maximalkraft]
  kernaussagen_abstract: "HIIT plus Krafttraining gegenüber Kraft allein: ähnliche Hypertrophie und Oberkörperkraft, geringerer Zuwachs der Unterkörperkraft (ES −0,248). Trend zu stärkerem Nachteil bei Rad-HIIT (ES −0,377; p = 0,074) als bei Lauf-HIIT."
  hinweis: Die Richtung des Modalitätsbefunds widerspricht L-T2-25 und L-T2-31 (D-62 e)
  verifikation: PubMed 2026-09-28
- id: L-T2-33
  status: optional
  datei: t2-kraft/L-T2-33_McGill-2016_Low-Back-Disorders_3ed.pdf
  kapitel: t2-kraft/L-T2-33_kapitel/
  stufe: B
  typ: lehrbuch
  zitat: "McGill S. Low Back Disorders: Evidence-Based Prevention and Rehabilitation. 3. Aufl. Champaign, IL: Human Kinetics; 2016."
  isbn: 978-1-4504-7291-3 (3. Aufl., vorliegende Ausgabe)
  neuauflage: 4. Aufl. 2026, ISBN 9781718236257 (Paperback), xvi, 447 S. – nicht beschafft (Entscheidung Athlet 2026-09-29, vorhandene Ausgabe verwenden)
  sprache: en
  themenfelder: [rumpf, praevention_kreuzschmerz, kraeftigung]
  zweck: wissenschaftliches Hauptwerk des Autors – Biomechanik der LWS, Risikobewertung, Aufbau von Übungsprogrammen; Referenz für Übungsauswahl in der Karte Haltung und Rücken
  einschraenkung: Dosierung ausschließlich aus L-T2-15 bis L-T2-18 (D-54); Übungsempfehlungen (z. B. „Big 3“) nur als gekennzeichnete Beispiele; das biomechanische Belastungsmodell des Autors ist in der Literatur nicht unumstritten (Einschätzung, D-13) → unter „Grenzen“
  bezug: L-T2-13 (Praxisbücher desselben Autors, Stufe C) bleibt zurückgestellt
  zugang: E-Book-PDF (Calibre-Konvertierung) mit Lesezeichen und Textebene vorhanden (2026-09-29); Seitenzahlen im PDF = Druckseiten
  verifikation: Bibliothekskatalog East Carolina University (LCCN 2025009710) 2026-09-29
  bestaetigt: Athlet 2026-09-29 (SW-E2)
```

Themenfeld-Vokabular T2 Haltung/Rücken (für Karten): haltung, vorkopfhaltung, kyphose, kraeftigung, dehnung, rumpf, praevention_kreuzschmerz.

Themenfeld-Vokabular T2 Hypertrophie (für Karten): hypertrophie, faserhypertrophie, volumen, frequenz, naehe_muskelversagen, last, maximalkraft, elastischer_widerstand, interferenz.

### 13.2.4 T3 Klettern/Bouldern (Block bestätigt 2026-09-27; E1/E2 bestätigt, E3–E6 → D-31)

Evidenzlage laut beiden Reviews begrenzt (je ca. 11–12 Studien, kleine Stichproben, heterogene Designs): Karten kennzeichnen Empfehlungen als „Evidenz: begrenzt"; keine Scheingenauigkeit bei Belastungsparametern. Evidenzkern (E6, geändert durch D-70): L-T3-01, -02, -03, -06, -19 und L-T3-09 (vorhandene 3. Aufl. 2016; Neuauflage, angekündigt 02.03.2027, nach Erscheinen zusätzlich). Stufe-C-Ergänzungen: L-T3-10 (Ideenfundus), L-T3-20 (Übungsband), L-T3-21 (Verletzungsmanagement, vorläufig), L-T3-16 (Planungsvorlage). L-T3-08 zurückgestellt. Das Spezifitätsschema aus L-T3-02 (spezifisch = Vorstieg/Bouldern, halbspezifisch = Fingerboard/Campusboard, unspezifisch = klassisches Krafttraining) wird als Attribut `spezifitaet` der Kletterblöcke im Datenmodell (7.1) übernommen.

```yaml
- id: L-T3-01
  status: ausgewaehlt (kern)
  datei: t3-klettern/L-T3-01_Stien-2023_Climbing-and-Resistance-Training-Meta-Analysis.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Stien N, Riiser A, Shaw MP, Saeterbakken AH, Andersen V. Effects of climbing- and resistance-training on climbing-specific performance: a systematic review and meta-analysis. Biol Sport. 2023;40(1):179-191."
  doi: 10.5114/biolsport.2023.113295
  zugang: Open Access, CC BY 4.0 (Volltext im Repo zulässig, D-31)
  themenfelder: [fingerkraft, kraftanstiegsrate, unterarmausdauer, kraftausdauer]
  kernaussagen: kletterspezifisches Krafttraining verbessert kletterbezogene Testleistungen stärker als reines Klettern (SDM 0,57; 95%-KI 0,24–0,91); Fingerbeuger-Krafttraining verbessert Fingerkraft, Kraftanstiegsrate und Unterarmausdauer; 11 RCTs, 5 meta-analysiert, Suche bis 01/2021
  verifikation: verifiziert
- id: L-T3-02
  status: ausgewaehlt (kern)
  datei: t3-klettern/L-T3-02_Langer-2023_Strength-Training-in-Climbing.pdf
  stufe: A
  typ: systematischer_review
  zitat: "Langer K, Simon C, Wiemeyer J. Strength Training in Climbing: A Systematic Review. J Strength Cond Res. 2023;37(3):751-767."
  pmid: "36820707"
  doi: 10.1519/JSC.0000000000004286
  zugang: Volltext vorhanden (datei)
  themenfelder: [fingerkraft, maximalkraft, hypertrophie, kraftausdauer, kraftanstiegsrate]
  kernaussagen: "Laut Abstract: 12 Studien, Effektstärken für 9; positive Effekte des Krafttrainings auf alle Zielgrößen; Trend zu Kombination Maximalkraft mit Hypertrophie oder Ausdauer; Trend zu halbspezifischen Übungen; statisch und dynamisch ähnlich, Trend zur Mischung. Wiederholungsbereiche (1–5 Wdh./s; 8–15 Wdh. bzw. 3–30 s) stehen nicht im Abstract und bleiben Sekundärzitat (Front Physiol 2024, doi 10.3389/fphys.2024.1461820), bis sie am Volltext geprüft sind (V-15)"
  verifikation: teilweise (Abstract PubMed 2026-09-28; Wiederholungsbereiche am Volltext offen)
- id: L-T3-03
  status: ausgewaehlt (kern)
  datei: t3-klettern/L-T3-03_Langer-2023_Performance-Testing-in-Climbing.pdf
  stufe: A
  typ: systematischer_review
  zitat: "Langer K, Simon C, Wiemeyer J. Physical performance testing in climbing – A systematic review. Front Sports Act Living. 2023;5:1130812."
  pmid: "37229362"
  pmcid: PMC10203485
  doi: 10.3389/fspor.2023.1130812
  zugang: Open Access, CC BY (Verlags-PDF; Zweitveröffentlichung TU Darmstadt als CC BY 4.0) – Volltext im Repo zulässig (D-31)
  themenfelder: [leistungsdiagnostik]
  kernaussagen: 156 Studien, 63 verschiedene Tests; keine einheitlichen Standardverfahren für Kraft-, Ausdauer-, Beweglichkeitstests; kaum Gütekriterien berichtet → präzise Testempfehlungen nicht möglich; Grundlage für Verlaufstests (AP-08)
  verifikation: verifiziert 2026-09-28 (Band 5, Artikel 1130812, Lizenz CC BY laut Volltext und Verlag)
- id: L-T3-04
  status: ausgewaehlt (ergaenzend)
  datei: t3-klettern/L-T3-04_Draper-2015_IRCRA-Grading-Position-Statement.pdf
  stufe: A
  typ: positionspapier
  zitat: "Draper N, Giles D, Schöffl V, et al. Comparative grading scales, statistical analyses, climber descriptors and ability grouping: International Rock Climbing Research Association position statement. Sports Technology. 2015;8(3-4):88-94."
  doi: 10.1080/19346182.2015.1107081
  zugang: Volltext vorhanden (datei, 2026-09-28)
  themenfelder: [leistungsniveau_klassifikation]
  zweck: Umrechnung von Schwierigkeitsgraden, Einordnung des Leistungsniveaus (Datenmodell)
  verifikation: verifiziert 2026-09-28 (Hochschulbibliografien Bayreuth, Cádiz)
- id: L-T3-05
  status: ausgewaehlt (ergaenzend)
  datei: t3-klettern/L-T3-05_Lopez-Rivera-2012_Grip-Strength-Edge-Depth.pdf
  stufe: A
  konfidenz: niedrig trotz Stufe A (sehr kleine Stichprobe, keine Signifikanz) – Protokollvorlage, kein Wirksamkeitsbeleg
  typ: interventionsstudie
  zitat: "López-Rivera E, González-Badillo JJ. The effects of two maximum grip strength training methods using the same effort duration and different edge depth on grip endurance in elite climbers. Sports Technology. 2012;5(3-4):100-110."
  doi: 10.1080/19346182.2012.716061
  zugang: Taylor & Francis; nicht in PubMed indexiert
  themenfelder: [fingerkraft, kraftausdauer]
  zweck: Vergleich von Hangboard-Maximalkraftmethoden (Leistentiefe/Zusatzlast); Hauptbeleg für Hangboard-Protokolle ist L-T3-18
  kernaussagen: n = 9 (8a+/b); Reihenfolge Zusatzgewicht an 18-mm-Leiste vor minimaler Leistentiefe tendenziell besser; Unterschiede nicht signifikant (p > 0,05)
  verifikation: verifiziert 2026-09-28 (Datenbank BISp/IAT)
- id: L-T3-06
  status: ausgewaehlt (kern)
  datei: t3-klettern/L-T3-06_Schoeffl-2022_Climbing-Medicine.pdf
  kapitel: t3-klettern/L-T3-06_kapitel/
  stufe: B
  typ: fachbuch
  zitat: "Schöffl V, Schöffl I, Lutter C, Hochholzer T (Hrsg.). Climbing Medicine – A Practical Guide. Cham: Springer; 2022. 329 S."
  isbn: 978-3-030-72183-1
  doi: 10.1007/978-3-030-72184-8
  sprache: en
  themenfelder: [physiologie, biomechanik, verletzungspraevention, verletzungen_therapie]
  zweck: Sportmedizinisches Referenzwerk; bevorzugte Ausgabe (D-31), Alternative L-T3-07
  zugang: Springer, kapitelweise PDF
  verifikation: verifiziert
- id: L-T3-07
  status: optional (Alternative zu L-T3-06, D-31)
  stufe: B
  typ: fachbuch
  zitat: "Schöffl V, Schöffl I, Hochholzer T, Lutter C (Hrsg.). Klettermedizin – Grundlagen, Unfälle, Verletzungen und Therapie. Berlin, Heidelberg: Springer; 2020. IX, 255 S."
  isbn: 978-3-662-61090-9 (E-Book)
  doi: 10.1007/978-3-662-61090-9
  sprache: de
  zweck: Deutsche Originalausgabe von L-T3-06; Alternative, falls die englische nicht beschafft wird (D-31)
  hinweis: Print-ISBN nicht ermittelt; für Springer-Kapitel-PDF genügen E-ISBN/DOI. Nicht nötig, L-T3-06 liegt vor
  verifikation: verifiziert 2026-09-28 (Katalog UB TUM)
- id: L-T3-08
  status: zurueckgestellt
  stufe: B
  typ: fachbuch
  zitat: "Köstermeyer G. Peak Performance – Klettertechnik und Klettertraining von A–Z. 9., überarb. Aufl. Korb: tmms-Verlag; 2019."
  isbn: 978-3-945271-41-4
  umfang: 268 S. (Händlerangabe); Nachdrucke 2022 und 2025 (Händlerangaben)
  sprache: de
  themenfelder: [periodisierung, fingerkraft, kraftausdauer, technik, taktik]
  zweck: Deutschsprachiges Standardwerk; Übertragung der Trainingslehre aufs Klettern, nichtlineare Periodisierung; Autor FAU Erlangen-Nürnberg, DAV-Trainer
  grund_zurueckstellung: keine digitale Ausgabe, Gesamtscan nicht vorgesehen; Rolle im Evidenzkern übernimmt L-T3-19 (D-70)
  verifikation: bibliografisch 2026-09-29 (Händlerangaben; passt zur FAU-Angabe „Peak Performance (2019)“)
- id: L-T3-09
  status: ausgewaehlt
  datei: t3-klettern/L-T3-09_Hoerst-2016_Training-for-Climbing_3ed.pdf
  kapitel: t3-klettern/L-T3-09_kapitel/
  stufe: B
  typ: fachbuch
  zitat: "Hörst EJ. Training for Climbing – The Definitive Guide to Improving Your Performance. 3. Aufl. Guilford, CT: FalconGuides; 2016. xiii, 335 S."
  isbn: 978-1-4930-1761-4
  sprache: en
  themenfelder: [periodisierung, fingerkraft, kraftausdauer, unterarmausdauer, mental, verletzungspraevention]
  zweck: Energiesystemtraining, Trainingszonen, DUP, Hangboard-Protokolle, Tapering
  einschraenkung: Label „evidenzbasiert" stammt vom Verlag; Autor ist Coach, kein Hochschulforscher – Aussagen gegen Stufe A abgleichen
  beschaffung: keine Neuauflage – die vorhandene 3. Aufl. gilt (Entscheidung Athlet 2026-09-29, vorhandene Ausgaben verwenden); Neuauflage laut Händler 02.03.2027 (ISBN 978-1-4930-8618-4) nur zur Information
  zugang: 3. Aufl. als Scan (Internet Archive) mit Texterkennung vorhanden (datei, 2026-09-29); Druckseite = PDF-Seite − 16
  verifikation: verifiziert 2026-09-28 (Bibliothekskataloge); Auflage am Impressum bestätigt (© 2003, 2008, 2016); Neuauflage Händlerangabe 2026-09-29
- id: L-T3-10
  status: ausgewaehlt (ideenfundus, Stufe C)
  datei: t3-klettern/L-T3-10_Mobraten-2020_Climbing-Bible.epub
  kapitel: t3-klettern/L-T3-10_kapitel/
  stufe: C
  typ: praxisbuch
  zitat: "Mobråten M, Christophersen S. The Climbing Bible – Technical, Physical and Mental Training for Rock Climbing. Vertebrate Publishing; 2020."
  isbn: 978-1-912560-70-7 (Paperback); 978-1-83981-033-6 (E-Book, vorliegendes EPUB)
  originalausgabe: "Klatrebibelen. Klatreboka AS; 2018."
  sprache: en
  themenfelder: [technik, fingerkraft, mental, verletzungspraevention, periodisierung]
  hinweis: Übungsbibliothek, Co-Autor Physiotherapeut; nur Ideenfundus (D-31); Übungsband dazu L-T3-20
  zugang: EPUB ohne DRM (geprüft 2026-09-29, keine META-INF/encryption.xml); die Kobo-Ausgabe mit Adobe DRM wäre ungeeignet (D-71, V-13)
  seitenbezug: keine Seitenliste → Zitat mit Kapitel und Abschnitt (D-71, V-17)
  verifikation: verifiziert; Impressum des EPUB 2026-09-29 (© 2020, digitale Ausgabe 2021, Übers. Sætnan B.)
- id: L-T3-11
  status: optional (ideenfundus, Stufe C)
  stufe: C
  typ: praxisbuch
  zitat: "Köstermeyer G. Der Boulder-Coach. BLV; 2017."
  isbn: 978-3-8354-1705-2
  sprache: de
  themenfelder: [bouldern_spezifisch, technik, taktik, mental]
  verifikation: verifiziert
- id: L-T3-12
  status: optional (ideenfundus, Stufe C)
  stufe: C
  typ: praxisbuch
  zitat: "Hochholzer T, Schöffl V. So weit die Hände greifen (engl. One Move Too Many). Ebenhausen: Lochner-Verlag; 2014. 320 S."
  isbn: 9783928026345
  sprache: de
  themenfelder: [verletzungspraevention, verletzungen_therapie]
  hinweis: Weitgehend redundant zu L-T3-06/07; nur relevant, falls Klettermedizin nicht beschafft wird
  verifikation: teilweise – Jahr/ISBN aus Händlerangabe; Auflage unklar (laut Händler „Ausgabe Nr. 6")
- id: L-T3-15
  status: optional
  stufe: C
  typ: praxisbuch
  konfidenz: niedrig (D-31)
  zitat: "Anderson ML, Anderson ML. The Rock Climber's Training Manual – A Guide to Continuous Improvement. Boulder, CO: Fixed Pin Publishing; 2014. 304 S."
  isbn: 978-0-9895156-1-0
  sprache: en
  themenfelder: [periodisierung, fingerkraft, kraftausdauer]
  zweck: Periodisierung (lineare Blockperiodisierung im Jahresplan), Hangboard- und Campusboard-Protokolle; Planungsvorlage neben L-T3-16
  einschraenkung: Stufe C (Praktiker, Eigenverlag) – nie alleiniger Beleg für Belastungsparameter (D-31); Periodisierungs- und Hangboard-Aussagen gegen L-T3-19, L-T3-18 und L-P01/L-P02 abgleichen
  zugang: Kauf; Format prüfen (D-26, D-71)
  verifikation: Händlerangaben (AbeBooks, Biblio) 2026-09-29
  bestaetigt: Athlet 2026-09-29 (Abschluss der Literatursuche)
- id: L-T3-16
  status: ausgewaehlt (planungsvorlage, Stufe C)
  datei: t3-klettern/L-T3-16_Bechtel-2020_Logical-Progression_2ed.pdf
  kapitel: t3-klettern/L-T3-16_kapitel/
  stufe: C
  typ: praxisbuch
  zitat: "Bechtel S, Stewart K. Logical Progression – Building Training Programs for Year-Round Climbing Performance. 2. Aufl. Lander, WY: Climb Strong; 2020."
  erstauflage: "Bechtel S. Logical Progression – Using Nonlinear Periodization for Year-Round Climbing Performance. CreateSpace; 2017. 140 S. ISBN 978-1-5441-1953-3"
  sprache: en
  themenfelder: [periodisierung, fingerkraft, kraftausdauer]
  zweck: Nichtlineare Periodisierung; die 2. Aufl. ergänzt Block-Programmierung als Mischform aus nichtlinearem und klassischem Modell; Sitzungsvorlagen für die Wochen- und Jahresstruktur Klettern neben Ausdauertraining
  einschraenkung: Stufe C (Trainer, Eigenverlag) – nie alleiniger Beleg für Belastungsparameter (D-31); Periodisierungsaussagen gegen L-P01, L-P02 und L-T3-19 Kap. 10 abgleichen
  zugang: PDF mit Textebene, ohne Lesezeichen, vorhanden (2026-09-29); Kapitelgrenzen aus dem Inhaltsverzeichnis, Druckseite = PDF-Seite − 14
  verifikation: teilweise – Jahr 2020 aus dem Vorwort erschlossen (Erstausgabe 2016 gedruckt, „4 years later“); ISBN im PDF nicht angegeben
- id: L-T3-17
  status: kandidat (Sammelplatzhalter)
  stufe: A
  thema: Fingerbeuger-/Sehnen-/Ringbandadaptation, Hangboard-Protokolle, Belastungsdosierung
  zweck: Progressionsregeln und Schmerzregeln (14.5); von Block T3 nur über L-T3-01/02/05 abgedeckt – Primärstudien bei Bedarf über PubMed
- id: L-T3-18
  status: ausgewaehlt
  datei: t3-klettern/L-T3-18_Lopez-Rivera-2019_Hangboard-Training-Programs.pdf
  stufe: A
  typ: rct
  zitat: "López-Rivera E, González-Badillo JJ. Comparison of the Effects of Three Hangboard Strength and Endurance Training Programs on Grip Endurance in Sport Climbers. J Hum Kinet. 2019;66:183-195."
  pmid: "30988852"
  pmcid: PMC6458579
  doi: 10.2478/hukin-2018-0057
  zugang: Open Access (PMC)
  themenfelder: [fingerkraft, kraftausdauer]
  zweck: Größere Folgestudie zu L-T3-05 (n = 26, 8 Wochen, drei Hangboard-Programme); Hauptbeleg für Hangboard-Protokolle
  kernaussage: Griffausdauer +34 % (maximale Hangs), +45 % (intermittierende Hangs), +7 % (Kombination)
  verifikation: PMC-Seite 2026-09-28; PubMed-Metadaten nicht separat abgerufen
- id: L-T3-19
  status: ausgewaehlt (kern)
  datei: t3-klettern/L-T3-19_Consuegra-2023_Science-of-Climbing-Training.epub
  kapitel: t3-klettern/L-T3-19_kapitel/
  stufe: B
  typ: fachbuch
  zitat: "Consuegra S. The Science of Climbing Training – An evidence-based guide to improving your climbing performance. Übers. Stainthorpe R. Sheffield: Vertebrate Publishing; 2023."
  isbn: 978-1-83981-182-1 (Paperback); 978-1-83981-183-8 (E-Book, vorliegendes EPUB)
  originalausgabe: "Consuegra S. Entrenamiento de Escalada basado en la Evidencia Científica. Ediciones Desnivel; 2020."
  umfang: Druckausgabe 216 S. (Händlerangabe); 11 Kapitel in drei Teilen, Literaturverzeichnis
  sprache: en
  themenfelder: [periodisierung, fingerkraft, kraftausdauer, unterarmausdauer, verletzungspraevention]
  zweck: Ersatz für L-T3-08 im Evidenzkern (D-70) – Übertragung der Trainingslehre aufs Klettern (Teil I), Fingerkraft-, Zugkraft- und Unterarmausdauer-Methoden (Kap. 8), Einheitengestaltung (Kap. 9), Periodisierungsmodelle (Kap. 10), Detraining (Kap. 11)
  einschraenkung: Autor Sportwissenschaftler (Abschluss UPM Madrid) und Klettertrainer, kein Hochschulforscher – Aussagen gegen Stufe A (L-T3-01, -02, -18) abgleichen; Kap. 1 enthält den Abschnitt „Homeostasis, GAS and Supercompensation“ und der Verlagstext begründet Tapering mit Superkompensation → Darstellung gegen L-A02 Abschn. 2.3.7 und L-P01 prüfen und in der Karte unter „Grenzen/Widersprüche“ führen; Literaturstand entspricht der spanischen Originalausgabe 2020
  zugang: EPUB ohne DRM (geprüft 2026-09-29, keine META-INF/encryption.xml)
  seitenbezug: keine Seitenliste → Zitat mit Kapitel und Abschnitt (D-71, V-17)
  hinweis: Kap. 8 in drei Teilen; Teil 2 beginnt mit dem Unterabschnitt „Power and RFD Training“, der den Campus-Board-Abschnitt einleitet (Übergabe nannte „Campus Board Training“). Danksagung und Verlagswerbung (s023, s024) nicht übernommen
  verifikation: bibliografisch am Impressum des EPUB 2026-09-29; Druckumfang Händlerangabe
- id: L-T3-20
  status: ausgewaehlt (ideenfundus, Stufe C)
  datei: t3-klettern/L-T3-20_Mobraten-2022_Climbing-Bible-Practical-Exercises.epub
  kapitel: t3-klettern/L-T3-20_kapitel/
  stufe: C
  typ: praxisbuch
  zitat: "Mobråten M, Christophersen S. The Climbing Bible – Practical Exercises. Übers. Sætnan B. Sheffield: Vertebrate Publishing; 2022."
  isbn: 978-1-83981-104-3 (Paperback); 978-1-83981-105-0 (E-Book, vorliegendes EPUB)
  originalausgabe: "Klatrebibelen Praktiske Øvelser. Klatreboka AS; 2020."
  sprache: en
  themenfelder: [technik, fingerkraft, maximalkraft, kraftanstiegsrate]
  zweck: Übungsband zu L-T3-10 – Aufwärmen, Technikübungen (Kap. 1), Kraft und Schnellkraft (Kap. 2), Kinder und Jugendliche (Kap. 3); Ideenfundus für Übungen (D-31)
  einschraenkung: Stufe C – nie alleiniger Beleg für Belastungsparameter (D-31)
  zugang: EPUB ohne DRM (geprüft 2026-09-29, keine META-INF/encryption.xml)
  seitenbezug: Seitenmarken der Druckausgabe (S. 2–192) → Druckseite zitieren, im Markdown als „[S. n]“ (D-71)
  verifikation: bibliografisch am Impressum des EPUB 2026-09-29
  entscheidung: eigene ID statt Ersatz für L-T3-10 (Athlet 2026-09-29)
- id: L-T3-21
  status: ausgewaehlt (Stufe C, vorläufig – Bestätigung Athlet offen)
  datei: t3-klettern/L-T3-21_Christophersen-2024_Climbing-Bible-Managing-Injuries.epub
  kapitel: t3-klettern/L-T3-21_kapitel/
  stufe: C
  typ: praxisbuch
  zitat: "Christophersen S. The Climbing Bible – Managing Injuries. Sheffield: Vertebrate Publishing; 2024."
  isbn: 978-1-83981-200-2 (Paperback); 978-1-83981-201-9 (E-Book)
  sprache: en
  themenfelder: [verletzungspraevention, verletzungen_therapie]
  zweck: Umgang mit akuten und Überlastungsverletzungen (Kap. 1), Verletzungen nach Körperregion (Kap. 2), Schmerzverständnis (Kap. 3); Ergänzung zu L-T3-06 und Block R (Schmerzregeln 14.5)
  einschraenkung: Stufe C (Praxisbuch; Autor Physiotherapeut und Klettertrainer) – Diagnose-, Therapie- und Belastungsaussagen gegen L-T3-06 und Block R abgleichen, nie alleiniger Beleg (D-31)
  zugang: EPUB ohne DRM (geprüft 2026-09-29, keine META-INF/encryption.xml)
  seitenbezug: Seitenmarken der Druckausgabe (S. 2–157) → Druckseite zitieren, im Markdown als „[S. n]“ (D-71)
  hinweis: ohne Übergabe bereitgestellt (2026-09-29); ID und Stufe von der Code-Instanz analog L-T3-20 vergeben
  verifikation: bibliografisch am Impressum des EPUB 2026-09-29
```

Nummernlücke: L-T3-13 (MacLeod) und L-T3-14 (Neumann U) stehen als ausgeschlossene Werke in 13.3.

Themenfeld-Vokabular T3 (für Karten und Datenmodell): fingerkraft, maximalkraft, hypertrophie, kraftausdauer, kraftanstiegsrate, unterarmausdauer, periodisierung, leistungsdiagnostik, leistungsniveau_klassifikation, verletzungspraevention, verletzungen_therapie, physiologie, biomechanik, technik, taktik, mental, bouldern_spezifisch.

### 13.2.5 R Reha/Prävention (Block bestätigt 2026-09-28, D-61)

Themenübergreifender Block zu T1 und T2: Patellatendinopathie, Sprunggelenksinstabilität/Rezidivprophylaxe, Laufumfang und Verletzungsrisiko. Ausgangsmaterial ist der Reha-Chat „Trainingsgeräte für Sprunggelenksinstabilität nach Supinationstrauma“ (Plan Rev. 6); alle Quellen per PubMed geprüft (Literatur-Sitzung AP-06 Teil C).

Evidenzlage: Übungstherapie Patellasehne und Balancetraining Sprunggelenk sind durch RCTs und Reviews gestützt, aber mit niedriger bis moderater Evidenzsicherheit; Einzelbefunde (TEREX, Donovan, Frandsen) sind als solche zu kennzeichnen.

Patellatendinopathie:

```yaml
- id: L-R-01
  status: ausgewaehlt
  datei: r-reha/L-R-01_Breda-2021_Progressive-Tendon-Loading.pdf
  stufe: A
  typ: rct
  zitat: "Breda SJ, Oei EHG, Zwerver J, et al. Effectiveness of progressive tendon-loading exercise therapy in patients with patellar tendinopathy: a randomised clinical trial. Br J Sports Med. 2021;55(9):501-509."
  pmid: "33219115"
  pmcid: PMC8070614
  doi: 10.1136/bjsports-2020-103403
  zugang: Volltext in PMC; Lizenz nicht geprüft; laut Volltext CC BY-NC 4.0 (2026-09-29)
  themenfelder: [patellasehne, progressive_belastung]
  kernaussagen_abstract: n = 76; PTLE vs. exzentrisch nach 24 Wochen VISA-P +28 vs. +18 (Differenz 9, p = 0,023); Return to Sport 43 % vs. 27 % (Trend, p = 0,13); Adhärenz 40 % vs. 49 %
  rolle: Stufenmodell (isometrisch → isotonisch → energiespeichernd → sportspezifisch)
- id: L-R-02
  status: ausgewaehlt
  datei: r-reha/L-R-02_Kongsgaard-2009_Patellar-Tendinopathy-HSR.pdf
  stufe: A
  typ: rct
  zitat: "Kongsgaard M, Kovanen V, Aagaard P, et al. Corticosteroid injections, eccentric decline squat training and heavy slow resistance training in patellar tendinopathy. Scand J Med Sci Sports. 2009;19(6):790-802."
  pmid: "19793213"
  doi: 10.1111/j.1600-0838.2009.00949.x
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [patellasehne, hsr]
  kernaussagen_abstract: n = 39 Männer, 12 Wochen; HSR mit guten kurz- und langfristigen Effekten, höchste Zufriedenheit; Kortison kurzfristig gut, langfristig schlechter
  hinweis: Schmerzregel (VAS ≤ 30/100) und Protokolldetails nicht im Abstract → Volltext (Q-13)
- id: L-R-03
  status: ausgewaehlt
  datei: r-reha/L-R-03_Agergaard-2021_Heavy-vs-Moderate-Loads-Patellar-Tendinopathy.pdf
  stufe: A
  typ: rct
  zitat: "Agergaard AS, Svensson RB, Malmgaard-Clausen NM, et al. Clinical Outcomes, Structure, and Function Improve With Both Heavy and Moderate Loads in the Treatment of Patellar Tendinopathy: A Randomized Clinical Trial. Am J Sports Med. 2021;49(4):982-993."
  pmid: "33616456"
  doi: 10.1177/0363546520988741
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [patellasehne, lastdosierung]
  kernaussagen_abstract: n = 44; 55 % vs. 90 % 1RM bei gleichem Volumen; keine Unterschiede in Klinik, Struktur, Funktion; Verbesserung bis 52 Wochen, Normalwerte nicht erreicht
- id: L-R-04
  status: ausgewaehlt
  datei: r-reha/L-R-04_Agergaard-2026_TEREX-Extended-Restitution.pdf
  stufe: A
  typ: rct
  zitat: "Agergaard AS, Svensson RB, Hoeffner R, Gillani SZ, Magnusson SP. Extended Restitution Between Sessions Does Not Enhance the Benefits of 12 Weeks Exercise-Based Treatment for Patellar Tendinopathy: A Randomized Controlled Clinical Trial (The TEREX Trial). Scand J Med Sci Sports. 2026;36(3):e70235."
  pmid: "41796988"
  pmcid: PMC12968374
  doi: 10.1111/sms.70235
  zugang: Volltext in PMC; Lizenz nicht geprüft; laut Volltext CC BY (2026-09-29)
  themenfelder: [patellasehne, trainingsfrequenz]
  kernaussagen_abstract: n = 52; 1 vs. 3 Trainingstage/Woche (Beinpresse, Knieextension, ~60 → ~75 % 1RM; Impact in beiden Gruppen eingeschränkt); gleiche klinische und Kraftverbesserung; keine Verbesserung von Sprunghöhe und Sehnenstruktur
  konfidenz: mittel – Einzelstudie, keine Replikation gefunden (Gegenrecherche 2026-09-28)
- id: L-R-05
  status: ausgewaehlt
  datei: r-reha/L-R-05_Challoumas-2023_Lower-Limb-Tendinopathy-Living-Review.pdf
  stufe: A
  typ: systematischer_review_netzwerk_metaanalyse
  zitat: "Challoumas D, Crosbie G, O'Neill S, Pedret C, Millar NL. Effectiveness of Exercise Treatments with or without Adjuncts for Common Lower Limb Tendinopathies: A Living Systematic Review and Network Meta-analysis. Sports Med Open. 2023;9(1):71."
  pmid: "37553459"
  pmcid: PMC10409676
  doi: 10.1186/s40798-023-00616-1
  zugang: Volltext in PMC; Lizenz nicht geprüft; laut Volltext CC BY 4.0 (2026-09-29)
  themenfelder: [patellasehne, erstlinie]
  kernaussagen_abstract: 68 RCTs; kein Zusatzverfahren überzeugend besser als Übungstherapie allein; Empfehlung Übungstherapie allein mindestens 3 Monate als Erstlinie; Stoßwelle zusätzlich zu exzentrischem Training ohne Kurzzeitnutzen (moderate Evidenz)
- id: L-R-06
  status: ausgewaehlt
  datei: r-reha/L-R-06_Liu-2026_Patellar-Tendinopathy-Network-Meta-Analysis.pdf
  stufe: A
  typ: systematischer_review_netzwerk_metaanalyse
  zitat: "Liu Y, Li C, Yang F. Comparative effectiveness of exercise interventions for patellar tendinopathy: a systematic review and network meta-analysis of randomized controlled trials. BMC Sports Sci Med Rehabil. 2026;18:296."
  pmid: "42192475"
  pmcid: PMC13308153
  doi: 10.1186/s13102-026-01743-4
  zugang: Volltext in PMC; Lizenz nicht geprüft; laut Volltext CC BY-NC-ND 4.0 (2026-09-29)
  themenfelder: [patellasehne, methodenvergleich]
  kernaussagen_abstract: 17 RCTs, Primärnetz 10 Studien/313 Teilnehmer; keine Methode HSR überlegen; keine klinisch bedeutsame Rangfolge; Flywheel, exzentrisches Step-Training und konzentrisches Training schlechter als HSR geschätzt
  hinweis: Artikelnummer in PubMed nicht hinterlegt
- id: L-R-07
  status: ausgewaehlt
  datei: r-reha/L-R-07_Visentini-1998_VISA-Score.pdf
  stufe: A
  typ: validierungsstudie
  zitat: "Visentini PJ, Khan KM, Cook JL, Kiss ZS, Harcourt PR, Wark JD. The VISA score: an index of severity of symptoms in patients with jumper's knee (patellar tendinosis). J Sci Med Sport. 1998;1(1):22-28."
  pmid: "9732118"
  doi: 10.1016/s1440-2440(98)80005-4
  zugang: kein PMC-Volltext
  themenfelder: [messinstrument, visa_p]
  kernaussagen_abstract: 0–100 Punkte; Test-Retest und Inter-Tester r > 0,95; Gesunde 95, Klinikpatienten 55, präoperativ 22 Punkte
- id: L-R-08
  status: ausgewaehlt
  datei: r-reha/L-R-08_Lohrer-2011_VISA-P-German.pdf
  stufe: A
  typ: validierungsstudie
  zitat: "Lohrer H, Nauck T. Cross-cultural adaptation and validation of the VISA-P questionnaire for German-speaking patients with patellar tendinopathy. J Orthop Sports Phys Ther. 2011;41(3):180-190."
  pmid: "21289458"
  doi: 10.2519/jospt.2011.3354
  zugang: kein PMC-Volltext → Beschaffung (Wortlaut VISA-P-G)
  themenfelder: [messinstrument, visa_p]
  kernaussagen_abstract: VISA-P-G reliabel (ICC 0,88) und valide
  hinweis: Der VISA-P-Rechner aus dem Reha-Artefakt nutzt eine sinngemäße Übersetzung, nicht den validierten Wortlaut (dort bereits vermerkt)
  erratum: Das Volltext-PDF enthält das Erratum JOSPT 2013;43(9):679 – die Punktwerte der Items 8b und 8c waren im Artikel falsch (0, 7, 14, 21, 30). Richtig sind für 8b 0, 4, 10, 14, 20 und für 8c 0, 2, 5, 7, 10 (englische und deutsche Fassung). Der korrigierte Fragebogen liegt laut Erratum auf jospt.org; ein VISA-P-Rechner muss die korrigierten Werte verwenden
- id: L-R-09
  status: ausgewaehlt
  datei: r-reha/L-R-09_Hernandez-Sanchez-2014_VISA-P-Responsiveness.pdf
  stufe: A
  typ: validierungsstudie
  zitat: "Hernandez-Sanchez S, Hidalgo MD, Gomez A. Responsiveness of the VISA-P scale for patellar tendinopathy in athletes. Br J Sports Med. 2014;48(6):453-457."
  pmid: "23012320"
  doi: 10.1136/bjsports-2012-091163
  zugang: kein PMC-Volltext
  themenfelder: [messinstrument, visa_p, mcid]
  kernaussagen_abstract: n = 98; MCID > 13 Punkte absolut bzw. 15,4–27 % relativ; abhängig vom Ausgangswert
  hinweis: Online-Vorabveröffentlichung 2012, Heft 2014; im Reha-Artefakt bereits korrekt zitiert
- id: L-R-10
  status: optional
  datei: r-reha/L-R-10_Clifford-2020_Isometric-Exercise-Patellar-Tendinopathy.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Clifford C, Challoumas D, Paul L, Syme G, Millar NL. Effectiveness of isometric exercise in the management of tendinopathy: a systematic review and meta-analysis of randomised trials. BMJ Open Sport Exerc Med. 2020;6(1):e000760."
  pmid: "32818059"
  pmcid: PMC7406028
  doi: 10.1136/bmjsem-2020-000760
  zugang: Volltext in PMC; laut Volltext CC BY 4.0 (2026-09-29)
  themenfelder: [patellasehne, isometrie]
  kernaussagen_abstract: 10 RCTs (4 Patellasehne); Isometrik bei chronischer Tendinopathie nicht überlegen gegenüber isotonischem Training; Ansprechen variabel; als Teil progressiver Belastung nutzbar
- id: L-R-11
  status: optional
  datei: r-reha/L-R-11_Sprague-2018_Patellar-Tendinopathy-Risk-Factors.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Sprague AL, Smith AH, Knox P, Pohlig RT, Grävare Silbernagel K. Modifiable risk factors for patellar tendinopathy in athletes: a systematic review and meta-analysis. Br J Sports Med. 2018;52(24):1575-1585."
  pmid: "30054341"
  pmcid: PMC6269217
  doi: 10.1136/bjsports-2017-099000
  zugang: Volltext in PMC
  themenfelder: [patellasehne, risikofaktoren]
  kernaussagen_abstract: 31 Studien; keine starke Evidenz für irgendeinen Risikofaktor; begrenzte/widersprüchliche Evidenz u. a. für verminderte Dorsalextension und hohes Sprung-/Aktivitätsvolumen
- id: L-R-12
  status: optional
  datei: r-reha/L-R-12_Backman-2011_Ankle-Dorsiflexion-Patellar-Tendinopathy.pdf
  stufe: A
  typ: prospektive_kohorte
  zitat: "Backman LJ, Danielson P. Low range of ankle dorsiflexion predisposes for patellar tendinopathy in junior elite basketball players: a 1-year prospective study. Am J Sports Med. 2011;39(12):2626-2633."
  pmid: "21917610"
  doi: 10.1177/0363546511420552
  zugang: kein PMC-Volltext
  themenfelder: [patellasehne, dorsalextension]
  kernaussagen_abstract: n = 75 Junioren-Basketballer; 12 entwickelten Patellatendinopathie; Dorsalextension < 36,5° mit Risiko 18,5–29,4 % vs. 1,8–2,1 %
  konfidenz: niedrig für Übertragung (Population, kleine Fallzahl)
- id: L-R-23
  status: ausgewaehlt
  datei: r-reha/L-R-23_Lopes-2025_Exercise-for-Patellar-Tendinopathy-Cochrane.pdf
  stufe: A
  typ: cochrane_review
  zitat: "Lopes AD, Rizzo RR, Hespanhol L, Costa LO, Kamper SJ. Exercise for patellar tendinopathy. Cochrane Database Syst Rev. 2025;5(5):CD013078."
  pmid: "40421598"
  pmcid: PMC12107522
  doi: 10.1002/14651858.CD013078.pub2
  zugang: Volltext in PMC
  themenfelder: [patellasehne, evidenzgrenzen]
  kernaussagen_abstract: 7 RCTs, 211 Athleten; Training vs. keine Behandlung – Schmerz sehr unsicher (sehr niedrige Evidenz), Funktion evtl. kein Unterschied (niedrige Evidenz); vs. Kortison und vs. Operation kaum Unterschiede; keine Placebo-Studien; keine Unerwünschte-Ereignis-Daten
  rolle: Pflichtinhalt „Grenzen" der Karte (D-61 e); vergleicht nicht Trainingsformen untereinander
- id: L-R-27
  status: optional
  datei: r-reha/L-R-27_Deng-2025_Patellar-Tendinopathy-Long-Term-Prognosis.pdf
  stufe: A
  typ: kohorte_5_jahre
  zitat: "Deng J, Oosterhof JJ, Eygendaal D, Breda SJ, Oei EHG, de Vos RJ. Long-term Prognosis of Athletes With Patellar Tendinopathy Receiving Physical Therapy: Patient-Reported Outcomes at 5-Year Follow-up. Am J Sports Med. 2025;53(7):1568-1576."
  pmid: "40356204"
  pmcid: PMC12125489
  doi: 10.1177/03635465251336466
  zugang: Volltext in PMC
  themenfelder: [patellasehne, prognose]
  kernaussagen_abstract: 58 von 76 Teilnehmern der Breda-Studie; nach 5 Jahren 76 % genesen, VISA-P Median 57 → 82, 71 % zurück im gewünschten Sport; keine Prognosefaktoren identifiziert
- id: L-R-28
  status: optional
  datei: r-reha/L-R-28_Hjortshoej-2025_BFR-vs-HSR-Patellar-Tendinopathy.pdf
  stufe: A
  typ: rct
  zitat: "Hjortshoej MH, Juneja H, Svensson RB, et al. Effect of Low-Load Blood-Flow Restricted Training Versus Heavy Slow Resistance Training in Unilateral Patellar Tendinopathy: A Randomized Clinical Trial. Scand J Med Sci Sports. 2025;35(12):e70186."
  pmid: "41452311"
  doi: 10.1111/sms.70186
  zugang: kein PMC-Volltext
  themenfelder: [patellasehne, lastdosierung, blutflussrestriktion]
  kernaussagen_abstract: n = 36 Männer; Niedriglast mit Blutflussrestriktion und HSR vergleichbar bis 52 Wochen (Schmerz, VISA-P)
  rolle: stützt D-61 (a) Lasthöhe nicht entscheidend
- id: L-R-29
  status: optional
  stufe: B
  typ: lehrbuch
  zitat: "Brukner P, Khan K (Hrsg.). Brukner & Khan's Clinical Sports Medicine: Managing Injuries. 6. Aufl. McGraw-Hill Education (Australia); 2026."
  isbn: 9781761000027 (Print)
  umfang: 32 Kapitel, 86 Autorinnen und Autoren (Verlagsangabe)
  reihe: 6. Aufl. dreibändig – Foundations of Clinical Practice (2025, ISBN 9781761000010), Managing Injuries, The Medicine of Exercise; aufgenommen nur Managing Injuries (Band 1 → Q-22; Band 3 ohne Bezug, Gesundheitssport/Erkrankungen)
  inhalt: laut Autorenseite u. a. Kap. 25 Patellar tendinopathy, Kap. 30 Acute ankle injuries, Kap. 31 Ankle pain – Zuordnung zur 6. Aufl. beim Erwerb prüfen (V-24)
  sprache: en
  themenfelder: [patellasehne, sprunggelenk, progressive_belastung, rezidivprophylaxe, befunderhebung]
  zweck: internationales Standardwerk der Sportmedizin; Nachschlagewerk zu Verletzungsbildern und Rehabilitationsverlauf für Block R
  einschraenkung: keine Regelquelle (D-22) – Belastungsregeln aus Stufe A (L-R-01 ff.); Diagnostik und Therapie bleiben bei Fachpersonen (N5)
  zugang: Kauf; E-Book-Format/DRM prüfen (V-24)
  verifikation: Verlagsseite McGraw-Hill Education Australia (erhältlich ab 28.04.2026), Autorenseite clinicalsportsmedicine.com 2026-09-29
  bestaetigt: Athlet 2026-09-29 (SW-E1)
- id: L-R-30
  status: optional
  datei: r-reha/L-R-30_Engelhardt-2016_Sportverletzungen-GOTS-Manual_3ed.pdf
  kapitel: r-reha/L-R-30_kapitel/
  stufe: B
  typ: lehrbuch
  zitat: "Engelhardt M (Hrsg.). Sportverletzungen – GOTS Manual. Diagnose, Management und Begleitmaßnahmen. 3. Aufl. München: Elsevier (Urban & Fischer); 2016."
  isbn: 978-3-437-24092-8 (Print, 3. Aufl.); 978-3-437-29902-5 (E-Book)
  neuauflage: 4. Aufl. 2022, ISBN 978-3-437-24093-5, 1045 S. – nicht beschafft (Entscheidung Athlet 2026-09-29, vorhandene Ausgabe verwenden)
  inhalt: 3. Aufl. (90 Kapitel) u. a. Kap. 20 Das Kniegelenk, 21 Unterschenkel, Sprunggelenk und Fuß, 25 Sehnenverletzungen, 29 Laufen, 44 Sportklettern, 84 Rehabilitation nach Sportverletzungen (Kapitelnummern der 4. Aufl. weichen ab)
  sprache: de
  geltungsbereich: R; Kap. 44 auch T3
  themenfelder: [patellasehne, sprunggelenk, rezidivprophylaxe, befunderhebung]
  zweck: deutschsprachiges Gegenstück zu L-R-29; offizielles Manual der GOTS; deutsche Fachbegriffe
  einschraenkung: Schwerpunkt ärztliche Diagnostik und Therapie (N5); keine Regelquelle (D-22)
  zugang: PDF mit Textebene vorhanden (2026-09-29), Lesezeichen unbrauchbar; Kapitelgrenzen und Druckseiten aus den Kopfzeilen (Versatz nicht konstant, Druckseiten je Kapitel im README)
  verifikation: Verlagsseite Elsevier (4. Aufl. 19.09.2022), Händlerangaben 2026-09-29
  bestaetigt: Athlet 2026-09-29 (SW-E1)
```

Sprunggelenksinstabilität:

```yaml
- id: L-R-13
  status: ausgewaehlt
  datei: r-reha/L-R-13_Martin-2021_Lateral-Ankle-Sprain-Guideline.pdf
  stufe: A
  typ: leitlinie
  zitat: "Martin RL, Davenport TE, Fraser JJ, et al. Ankle Stability and Movement Coordination Impairments: Lateral Ankle Ligament Sprains Revision 2021. J Orthop Sports Phys Ther. 2021;51(4):CPG1-CPG80."
  pmid: "33789434"
  doi: 10.2519/jospt.2021.0302
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [sprunggelenk, leitlinie]
  hinweis: Abstract enthält keine Einzelempfehlungen; Empfehlungen (Balance-/Übungstherapie, Orthesen) am Volltext prüfen
- id: L-R-14
  status: ausgewaehlt
  datei: r-reha/L-R-14_Hupperets-2009_Home-Programme-Ankle-Sprain-Recurrence.pdf
  stufe: A
  typ: rct
  zitat: "Hupperets MD, Verhagen EA, van Mechelen W. Effect of unsupervised home based proprioceptive training on recurrences of ankle sprain: randomised controlled trial. BMJ. 2009;339:b2684."
  pmid: "19589822"
  pmcid: PMC2714677
  doi: 10.1136/bmj.b2684
  zugang: Volltext in PMC
  themenfelder: [sprunggelenk, rezidivprophylaxe]
  kernaussagen_abstract: n = 522; 8 Wochen Heimprogramm; Rezidive 22 % vs. 33 %; RR 0,63; NNT 9; Effekt v. a. bei nicht ärztlich behandelten Erstverletzungen
- id: L-R-15
  status: ausgewaehlt
  datei: r-reha/L-R-15_Schiftan-2015_Proprioceptive-Training-Ankle-Sprain.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Schiftan GS, Ross LA, Hahne AJ. The effectiveness of proprioceptive training in preventing ankle sprains in sporting populations: a systematic review and meta-analysis. J Sci Med Sport. 2015;18(3):238-244."
  pmid: "24831756"
  doi: 10.1016/j.jsams.2014.04.005
  zugang: kein PMC-Volltext
  themenfelder: [sprunggelenk, rezidivprophylaxe]
  kernaussagen_abstract: 7 RCTs, 3726 Teilnehmer; RR 0,65 gesamt, 0,64 bei vorheriger Verstauchung (NNT 13 laut Rivera et al. J Athl Train 2017), Primärprävention nicht schlüssig
- id: L-R-16
  status: ausgewaehlt
  datei: r-reha/L-R-16_Tang-2024_Balance-Training-Dosage-Ankle.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Tang F, Xiang M, Yin S, Li X, Gao P. Meta-analysis of the dosage of balance training on ankle function and dynamic balance ability in patients with chronic ankle instability. BMC Musculoskelet Disord. 2024;25(1):689."
  pmid: "39217316"
  pmcid: PMC11365157
  doi: 10.1186/s12891-024-07800-8
  zugang: Volltext in PMC; laut Volltext CC BY-NC-ND 4.0 (2026-09-29)
  themenfelder: [sprunggelenk, dosierung]
  kernaussagen_abstract: 20 Studien, 682 Teilnehmer; wirksamste Kombination 3×/Woche, 20–30 min, 4–6 Wochen (Funktionsscores, SEBT); Einheitsdauer wichtigster Einflussfaktor
  konfidenz: mittel – Subgruppenanalysen, hohe Heterogenität (I² 55–84 %); gilt für Funktion/Balance, nicht für Rezidive (vgl. L-R-25)
- id: L-R-17
  status: ausgewaehlt
  datei: r-reha/L-R-17_Donovan-2016_Destabilization-Devices-Ankle.pdf
  stufe: A
  typ: rct
  zitat: "Donovan L, Hart JM, Saliba SA, et al. Rehabilitation for Chronic Ankle Instability With or Without Destabilization Devices: A Randomized Controlled Trial. J Athl Train. 2016;51(3):233-251."
  pmid: "26934211"
  pmcid: PMC4852529
  doi: 10.4085/1062-6050-51.3.09
  zugang: Volltext in PMC
  themenfelder: [sprunggelenk, geraete]
  kernaussagen_abstract: n = 26; 4 Wochen Reha mit vs. ohne Destabilisierungsgeräte; keine Gruppenunterschiede; beide Gruppen große Verbesserung von Funktion und Kraft
  konfidenz: mittel – kleine Stichprobe
- id: L-R-19
  status: optional
  datei: r-reha/L-R-19_Kiers-2012_Unstable-Surface-Ankle-Proprioception.pdf
  stufe: A
  typ: laborstudie
  zitat: "Kiers H, Brumagne S, van Dieën J, van der Wees P, Vanhees L. Ankle proprioception is not targeted by exercises on an unstable surface. Eur J Appl Physiol. 2012;112(4):1577-1585."
  pmid: "21858665"
  doi: 10.1007/s00421-011-2124-8
  zugang: kein PMC-Volltext
  themenfelder: [sprunggelenk, unterlage, propriozeption]
  kernaussagen_abstract: n = 100 Gesunde; auf Schaumstoff geringerer Einfluss der Wadenvibration, größerer der Rückenvibration → Übungen auf weicher Unterlage adressieren nicht primär die periphere Sprunggelenkspropriozeption
  konfidenz: niedrig für Trainingsableitung – Gesunde, Akutmessung, kein Trainingseffekt
- id: L-R-20
  status: optional
  datei: r-reha/L-R-20_Fakontis-2023_Elastic-Bands-vs-Proprioceptive-Training.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Fakontis C, Iakovidis P, Kasimis K, et al. Efficacy of resistance training with elastic bands compared to proprioceptive training on balance and self-report measures in patients with chronic ankle instability: A systematic review and meta-analysis. Phys Ther Sport. 2023;64:74-84."
  pmid: "37801793"
  doi: 10.1016/j.ptsp.2023.09.009
  zugang: kein PMC-Volltext
  themenfelder: [sprunggelenk, kraeftigung, balance]
  kernaussagen_abstract: 5 Studien, 259 Patienten; Theraband- und propriozeptives Training bei SEBT und FAAM gleich; CAIT-Vorteil für Propriozeption unter MCID; niedrige Evidenzqualität
- id: L-R-21
  status: optional
  datei: r-reha/L-R-21_Giboin-2018_Slackline-Training.pdf
  stufe: A
  typ: kontrollierte_studie
  zitat: "Giboin LS, Gruber M, Kramer A. Three months of slackline training elicit only task-specific improvements in balance performance. PLoS One. 2018;13(11):e0207542."
  pmid: "30475850"
  pmcid: PMC6261037
  doi: 10.1371/journal.pone.0207542
  zugang: Volltext in PMC
  themenfelder: [balance, aufgabenspezifitaet]
  kernaussagen_abstract: n = 12 vs. 14; große Verbesserung auf der Slackline, kein Transfer auf 5 untrainierte Balanceaufgaben
- id: L-R-22
  status: optional
  datei: r-reha/L-R-22_Delahunt-2018_ROAST-Consensus.pdf
  stufe: A
  typ: konsensus
  zitat: "Delahunt E, Bleakley CM, Bossard DS, et al. Clinical assessment of acute lateral ankle sprain injuries (ROAST): 2019 consensus statement and recommendations of the International Ankle Consortium. Br J Sports Med. 2018;52(20):1304-1310."
  pmid: "29886432"
  doi: 10.1136/bjsports-2017-098885
  zugang: kein PMC-Volltext
  themenfelder: [sprunggelenk, befunderhebung]
  zweck: Struktur der Befunderhebung mechanischer und sensomotorischer Defizite → Ausgangstests AP-08
- id: L-R-25
  status: ausgewaehlt
  datei: r-reha/L-R-25_Wagemans-2022_Rehabilitation-Reinjury-Ankle-Sprain.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Wagemans J, Bleakley C, Taeymans J, et al. Exercise-based rehabilitation reduces reinjury following acute lateral ankle sprain: A systematic review update with meta-analysis. PLoS One. 2022;17(2):e0262023."
  pmid: "35134061"
  pmcid: PMC8824326
  doi: 10.1371/journal.pone.0262023
  zugang: Volltext in PMC
  themenfelder: [sprunggelenk, rezidivprophylaxe]
  kernaussagen_abstract: 14 RCTs, 2182 Teilnehmer; erneute Verletzung nach 12 Monaten OR 0,60 vs. übliche Versorgung; Trainingsumfang ohne Zusammenhang mit Rezidivrisiko (Meta-Regression); optimaler Inhalt unklar
- id: L-R-26
  status: optional
  datei: r-reha/L-R-26_Doherty-2017_Ankle-Sprain-Overview-of-Reviews.pdf
  stufe: A
  typ: overview_of_reviews
  zitat: "Doherty C, Bleakley C, Delahunt E, Holden S. Treatment and prevention of acute and recurrent ankle sprain: an overview of systematic reviews with meta-analysis. Br J Sports Med. 2017;51(2):113-125."
  pmid: "28053200"
  doi: 10.1136/bjsports-2016-096178
  zugang: kein PMC-Volltext
  themenfelder: [sprunggelenk, rezidivprophylaxe, orthese]
  kernaussagen_abstract: 46 Reviews; Rezidivprophylaxe – starke Evidenz für Orthesen, moderate für neuromuskuläres Training
  rolle: Grundlage Q-16
```

Laufumfang und Verletzungsrisiko:

```yaml
- id: L-R-18
  status: optional
  datei: r-reha/L-R-18_Nielsen-2014_Running-Distance-Progression-Injuries.pdf
  stufe: A
  typ: prospektive_kohorte
  zitat: "Nielsen RØ, Parner ET, Nohr EA, Sørensen H, Lind M, Rasmussen S. Excessive progression in weekly running distance and risk of running-related injuries: an association which varies according to type of injury. J Orthop Sports Phys Ther. 2014;44(10):739-747."
  pmid: "25155475"
  doi: 10.2519/jospt.2014.5164
  zugang: kein PMC-Volltext; vorliegend nur das Autorenmanuskript (mit Zeilennummern, 25 S.) – Seitenzahlen nicht die der Zeitschrift, Zitat mit Abschnitt
  themenfelder: [laufumfang, verletzungsrisiko]
  kernaussagen_abstract: 874 Laufanfänger (selbst gestaltetes Training), explorativ; keine Unterschiede über alle Verletzungen; distanzbezogene Verletzungen (inkl. Patellatendinopathie) bei > 30 % vs. < 10 % Steigerung HR 1,59 (95 % KI 0,96–2,66; p = 0,07)
  statuswechsel: Kern → optional (durch L-R-24 ersetzt; Population Anfänger)
- id: L-R-24
  status: ausgewaehlt
  datei: r-reha/L-R-24_SchusterBrandtFrandsen-2025_High-Risk-Running-Sessions.pdf
  stufe: A
  typ: prospektive_kohorte
  zitat: "Schuster Brandt Frandsen J, Hulme A, Parner ET, et al. How much running is too much? Identifying high-risk running sessions in a 5200-person cohort study. Br J Sports Med. 2025;59(17):1203-1210."
  pmid: "40623829"
  pmcid: PMC12421110
  doi: 10.1136/bjsports-2024-109380
  zugang: Volltext in PMC; Lizenz nicht geprüft; laut Volltext CC BY-NC 4.0 (2026-09-29)
  themenfelder: [laufumfang, verletzungsrisiko, belastungssteuerung]
  kernaussagen_abstract: 5205 Läufer (Mittel 45,8 Jahre), 588 071 Einheiten, Garmin-Daten, 18 Monate; Einzellauf > 10 % länger als längster Lauf der letzten 30 Tage → HRR 1,64 (> 10–30 %), 1,52 (> 30–100 %), 2,28 (> 100 %); Woche-zu-Woche-Verhältnis ohne Zusammenhang; ACWR negative Dosis-Wirkung
  konfidenz: mittel – explorativ, beobachtend, Verletzungen selbst berichtet
  bezug: stützt L-P12 (keine ACWR-Automatik); Grundlage Q-15
```

Themenfeld-Vokabular R (für Karten und Datenmodell): patellasehne, progressive_belastung, hsr, lastdosierung, trainingsfrequenz, isometrie, erstlinie, methodenvergleich, evidenzgrenzen, prognose, risikofaktoren, dorsalextension, blutflussrestriktion, messinstrument, visa_p, mcid, sprunggelenk, rezidivprophylaxe, dosierung, geraete, unterlage, propriozeption, kraeftigung, balance, aufgabenspezifitaet, befunderhebung, orthese, leitlinie, laufumfang, verletzungsrisiko, belastungssteuerung.

### 13.2.6 T4 Beweglichkeit/Mobilität (Teil A bestätigt 2026-09-29, D-79)

Eigener Bereich (Tabelle 1.2) mit Schwerpunkt Hüftmobilität in allen Bewegungsrichtungen, Beweglichkeit rund ums Knie (Verweis Block R) und Ganzkörper als Reserve. Teil A (Evidenz und Dosierung) ist eingearbeitet; Teil B (Übungsquellen) mit dem Übungsbuch L-T4-34 abgeschlossen (K-8; Abschluss der Literatursuche 2026-09-29, keine weiteren Übungsquellen). Alle Quellen per PubMed geprüft (Literatur-Sitzung T4 Teil A).

Mit dem Athleten geklärt (2026-09-29): Ziele ROM, Wohlbefinden, Verletzungsprävention · Hüftrichtungen gleichrangig (Flexion, Extension, Ab-/Adduktion, Innen-/Außenrotation) · Methoden Dehnen (statisch, dynamisch, PNF), Mobility (Kräftigung in Endposition, CARs u. ä.), Faszienrolle, Yoga wenn sinnvoll · Lizenzen der Übungsquellen unwichtig (nur persönlicher Gebrauch) – gilt nur bei privatem Repo (Q-21) · eigener Bereich · Knie (Quadrizeps, Hamstrings) mit Verweis auf Block R · Standardwerke als Buch – aufgenommen Behm 2025 (L-T4-32, Stufe B), Nelson/Kokkonen (L-T4-34, Stufe C), Schleip/Wilke (L-T4-36, Stufe C, optional); nicht aufgenommen Alter, van der Poel und – nicht beschaffbar – Freiwald (L-T4-35) (13.3) (K-8, D-80) · Repo wird wieder privat, sobald für die Recherche kein Zugriff mehr nötig ist (K-9, Q-21).

Evidenzlage (Kurzfassung): Dehnen erhöht ROM akut klein und chronisch moderat; statisch und PNF sind chronisch dynamisch/ballistisch überlegen (L-T4-02). Krafttraining mit Zusatzlast über den vollen Bewegungsumfang wirkt gleich stark, nur Körpergewicht war nicht signifikant (L-T4-10, L-T4-11); Faszienrolle chronisch ähnlich, bei ≤ 4 Wochen schwächer (L-T4-12). Dosis widersprüchlich (L-T4-02 vs. L-T4-04/-05; ACSM L-T4-31 ≥ 2 Tage/Woche, 60 s je Übung); Intensität nicht einheitlich definiert (L-T4-30). Statisches Dehnen ≥ 60 s je Muskelgruppe mindert akut die Leistung (L-T4-06); Dehnen/Rolle im Aufwärmen ohne Vorteil (L-T4-08), kein Effekt auf Laufökonomie (L-T4-09). Kein präventiver Effekt (RR 0,96, L-T4-14), keine Wirkung auf Erholung oder Muskelkater (L-T4-15, L-T4-16), keine Haltungsverbesserung (L-T2-15, L-T4-01). Wohlbefinden nur schwach belegt (L-T4-17, L-T4-18). Hüftspezifische Evidenz dünn (L-T4-19, L-T4-07) – Übertragung als „Einschätzung“ (D-13). Klettern: Beweglichkeit als Schwellenfaktor (L-T4-26, L-T4-27). Yoga für gesunde Sportler kaum belastbar (L-T4-13) – nur Übungsfundus (Teil B).

Kern:

```yaml
- id: L-T4-01
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-01_Warneke-2025_Delphi-Consensus-Stretching.pdf
  stufe: A
  typ: konsens_delphi
  zitat: "Warneke K, Thomas E, Blazevich AJ, et al. Practical recommendations on stretching exercise: A Delphi consensus statement of international research experts. J Sport Health Sci. 2025;14:101067."
  pmid: "40513717"
  pmcid: PMC12305623
  doi: 10.1016/j.jshs.2025.101067
  zugang: Volltext in PMC; Lizenz laut PubMed nicht ausgewiesen (Elsevier-Hosting) – vor Ablage prüfen; laut Volltext CC BY-NC-ND 4.0 (2026-09-29)
  themenfelder: [rom_chronisch, rom_akut, verletzungspraevention, regeneration, dosierung]
  kernaussagen_abstract: 20 Experten, Delphi (≥ 80 % Zustimmung), 8 Themen. Konsens – akutes und chronisches Dehnen verbessert ROM (Alternativen existieren) und senkt Muskelsteifigkeit (nicht immer erwünscht); chronisch evtl. günstig für Gefäßgesundheit. Kein substanzieller Beitrag zu Hypertrophie, keine umfassende Verletzungsprävention, keine Haltungsverbesserung, keine akute Verbesserung der Erholung.
  rolle: Anker T4
  verifikation: PubMed 2026-09-29; konkrete Dosierungsempfehlungen nur im Volltext (V-18)
- id: L-T4-02
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-02_Konrad-2024_Chronic-Stretching-ROM-Meta-Analysis.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Konrad A, Alizadeh S, Daneshjoo A, et al. Chronic effects of stretching on range of motion with consideration of potential moderating variables: A systematic review with meta-analysis. J Sport Health Sci. 2024;13(2):186-194."
  pmid: "37301370"
  pmcid: PMC10980866
  doi: 10.1016/j.jshs.2023.06.002
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen; laut Volltext CC BY-NC-ND 4.0 (2026-09-29)
  themenfelder: [rom_chronisch, methodenvergleich, dosierung]
  kernaussagen_abstract: 77 Studien, 186 Effektstärken; moderater ROM-Gewinn (ES −1,00); PNF und statisch > ballistisch/dynamisch; Frauen höhere Zugewinne; Volumen, Intensität und Frequenz ohne signifikanten Einfluss
  rolle: Methodenwahl für ROM-Ziel
  hinweis: Online 2023, Heft 2024 – Zitierjahr 2024
  verifikation: PubMed 2026-09-29
- id: L-T4-03
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-03_Oba-2026_Moderators-Chronic-Static-Stretching.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Oba K, Matsuo S, Nakamura M, et al. Moderating Effects of Individual Characteristics and the Target Lower Limb Muscle Group on Flexibility Adaptations to Chronic Static Stretching in Healthy Individuals: A Systematic Review and Meta-Analysis of Randomized Controlled Trials. Sports Med Open. 2026;12:95."
  pmid: "42435098"
  pmcid: PMC13356130
  doi: 10.1186/s40798-026-01066-1
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen (Springer Open, vermutlich CC BY – prüfen); laut Volltext CC BY 4.0 (2026-09-29)
  themenfelder: [rom_chronisch, dosierung]
  kernaussagen_abstract: 79 RCTs, n = 3287; statisches Dehnen chronisch g = 0,85; keine Moderation durch Alter, Geschlecht, Trainingsstatus, Ausgangsbeweglichkeit oder Muskelgruppe (Kniebeuger, Kniestrecker, Plantarflexoren); hohe Heterogenität, mögliche Small-Study-Effekte
  grenzen: Hüftmuskulatur als Muskelgruppe nicht untersucht
  verifikation: PubMed 2026-09-29
- id: L-T4-04
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-04_Arntz-2023_Static-Stretching-Strength-and-Power.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Arntz F, Markov A, Behm DG, et al. Chronic Effects of Static Stretching Exercises on Muscle Strength and Power in Healthy Individuals Across the Lifespan: A Systematic Review with Multi-level Meta-analysis. Sports Med. 2023;53(3):723-745."
  pmid: "36719536"
  pmcid: PMC9935669
  doi: 10.1007/s40279-022-01806-9
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen
  themenfelder: [rom_chronisch, dosierung]
  kernaussagen_abstract: 41 Studien; Kraft SMD 0,21 und Schnellkraft 0,19 (trivial bis klein), Beweglichkeit SMD 0,96; passives > aktives statisches Dehnen (0,97 vs. 0,59); mehr Wiederholungen je Einheit, mehr Dehnzeit je Einheit und mehr Gesamtdehnzeit → mehr ROM
  rolle: Dosis-Wirkung (Gesamtzeit)
  verifikation: PubMed 2026-09-29
- id: L-T4-05
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-05_Thomas-2018_Stretching-Typology-and-Duration.pdf
  stufe: A
  typ: systematischer_review
  zitat: "Thomas E, Bianco A, Paoli A, Palma A. The Relation Between Stretching Typology and Stretching Duration: The Effects on Range of Motion. Int J Sports Med. 2018;39(4):243-254."
  pmid: "29506306"
  doi: 10.1055/s-0044-101146
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [dosierung, methodenvergleich]
  kernaussagen_abstract: 23 Studien; statisch > ballistisch/PNF; Wochendehnzeit ≥ 5 min entscheidend, Zeit je Einheit nicht; Frequenz positiv; Empfehlung ≥ 5 Tage/Woche, ≥ 5 min/Woche statisch
  grenzen: Widerspricht L-T4-02 (PNF gleichwertig, Frequenz ohne Einfluss) – als Richtwert, nicht als harte Regel führen
  verifikation: PubMed 2026-09-29; Bezug „je Muskelgruppe“ im Volltext prüfen (V-19)
- id: L-T4-06
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-06_Behm-2016_Acute-Effects-of-Stretching.pdf
  stufe: A
  typ: systematischer_review
  zitat: "Behm DG, Blazevich AJ, Kay AD, McHugh M. Acute effects of muscle stretching on physical performance, range of motion, and injury incidence in healthy active individuals: a systematic review. Appl Physiol Nutr Metab. 2016;41(1):1-11."
  pmid: "26642915"
  doi: 10.1139/apnm-2015-0235
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [aufwaermen, rom_akut, verletzungspraevention]
  kernaussagen_abstract: statisch −3,7 %, PNF −4,4 %, dynamisch +1,3 % Leistung direkt danach; ≥ 60 s statisch je Muskelgruppe −4,6 %, < 60 s −1,1 %; mit anschließender dynamischer Aktivität kein klarer Effekt; akuter ROM-Gewinn hält meist < 30 min; statisch/PNF ohne klaren Effekt auf Verletzungen
  rolle: Regeln für Dehnen im Aufwärmen
  hinweis: Online 2015, Heft 2016 – Zitierjahr 2016
  verifikation: PubMed 2026-09-29
- id: L-T4-08
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-08_Warneke-2024_Foam-Rolling-Stretching-Warm-up.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Warneke K, Plöschberger G, Lohmann LH, et al. Foam rolling and stretching do not provide superior acute flexibility and stiffness improvements compared to any other warm-up intervention: A systematic review with meta-analysis. J Sport Health Sci. 2024;13(4):509-520."
  pmid: "38244921"
  pmcid: PMC11184403
  doi: 10.1016/j.jshs.2024.01.006
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen; laut Volltext CC BY-NC-ND 4.0 (2026-09-29)
  themenfelder: [aufwaermen, rom_akut, faszienrolle]
  kernaussagen_abstract: 38 Studien, 1134 Teilnehmer; kein Unterschied zwischen Dehnen/Rolle und anderen Aufwärmformen bei ROM (ES 0,01), Steifigkeit, passivem Drehmoment; Studienqualität mäßig
  rolle: Dehnen im Aufwärmen optional, nicht nötig
  verifikation: PubMed 2026-09-29
- id: L-T4-10
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-10_Alizadeh-2023_Resistance-Training-ROM.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Alizadeh S, Daneshjoo A, Zahiri A, et al. Resistance Training Induces Improvements in Range of Motion: A Systematic Review and Meta-Analysis. Sports Med. 2023;53(3):707-722."
  pmid: "36622555"
  pmcid: PMC9935664
  doi: 10.1007/s40279-022-01804-x
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen
  themenfelder: [mobility, methodenvergleich, rom_chronisch]
  kernaussagen_abstract: 55 Studien; Krafttraining ES 0,73 für ROM; nur Körpergewicht nicht signifikant; kein Unterschied Kraft vs. Dehnen (ES 0,08) und Kraft + Dehnen vs. Dehnen; Untrainierte größere Zugewinne
  rolle: Beleg für Mobility über Kräftigung im vollen Bewegungsumfang
  grenzen: Befund „Körpergewicht nicht signifikant“ berührt Calisthenics-Einheiten (D-29) – nicht als ROM-Maßnahme zählen, bis geprüft
  verifikation: PubMed 2026-09-29
- id: L-T4-12
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-12_Konrad-2024_Stretching-vs-Foam-Rolling-ROM.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Konrad A, Alizadeh S, Anvar SH, Fischer J, Manieu J, Behm DG. Static Stretch Training versus Foam Rolling Training Effects on Range of Motion: A Systematic Review and Meta-Analysis. Sports Med. 2024;54(9):2311-2326."
  pmid: "38760635"
  pmcid: PMC11393112
  doi: 10.1007/s40279-024-02041-0
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen
  themenfelder: [faszienrolle, methodenvergleich, rom_chronisch]
  kernaussagen_abstract: 85 Studien (72 Dehnen, 13 Rolle); beide moderat wirksam (ES −1,01 bzw. −0,73), kein Unterschied; bei ≤ 4 Wochen nur Dehnen wirksam
  verifikation: PubMed 2026-09-29
- id: L-T4-14
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-14_Lauersen-2014_Exercise-Interventions-Injury-Prevention.pdf
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Lauersen JB, Bertelsen DM, Andersen LB. The effectiveness of exercise interventions to prevent sports injuries: a systematic review and meta-analysis of randomised controlled trials. Br J Sports Med. 2014;48(11):871-7."
  pmid: "24100287"
  doi: 10.1136/bjsports-2013-092538
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [verletzungspraevention]
  kernaussagen_abstract: 25 RCTs, 26 610 Teilnehmer; Dehnen RR 0,96 (kein Effekt); Krafttraining RR 0,32; Propriozeption RR 0,55; Überlastungsverletzungen durch Trainingsprogramme fast halbiert
  rolle: Pflichtinhalt „Grenzen“ – Dehnen nicht als Präventionsmaßnahme begründen
  hinweis: Online 2013, Heft 2014 – Zitierjahr 2014
  verifikation: PubMed 2026-09-29
- id: L-T4-16
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-16_Herbert-2011_Stretching-Muscle-Soreness-Cochrane.pdf
  stufe: A
  typ: cochrane_review
  zitat: "Herbert RD, de Noronha M, Kamper SJ. Stretching to prevent or reduce muscle soreness after exercise. Cochrane Database Syst Rev. 2011;(7):CD004577."
  pmid: "21735398"
  doi: 10.1002/14651858.CD004577.pub3
  zugang: Cochrane Library, Abstract frei
  themenfelder: [regeneration]
  kernaussagen_abstract: 12 Studien; Dehnen vor oder nach Belastung senkt Muskelkater nicht klinisch relevant (≈ 1 Punkt auf 100-Punkte-Skala; Großstudie 4 Punkte)
  verifikation: PubMed 2026-09-29
- id: L-T4-17
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-17_Behm-2026_Responses-to-Stretching.pdf
  stufe: A
  typ: narrativer_review
  konfidenz: mittel
  zitat: "Behm DG, Warneke K, Blazevich AJ, Afonso J. What are the neurological, neurovascular, and psychological responses to stretching across the general population? Eur J Appl Physiol. 2026;126(6):2977-2987."
  pmid: "42133057"
  doi: 10.1007/s00421-026-06231-8
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [wohlbefinden]
  kernaussagen_abstract: Dehnen aktiviert akut kurz sympathisch; nach akutem und chronischem Dehnen Verbesserungen direkter und indirekter Stressmarker (Herzfrequenz, Blutdruck, Cortisol, Angstskalen, HRV-Veränderungen); Praxisvorschläge ausdrücklich noch zu prüfen
  rolle: einzige Quelle für das Ziel Wohlbefinden
  grenzen: narrativ, keine Metaanalyse; Kennzeichnung „schwache Evidenz“
  verifikation: PubMed 2026-09-29
- id: L-T4-19
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-19_Winters-2004_Passive-vs-Active-Hip-Flexor-Stretching.pdf
  stufe: A
  typ: rct
  zitat: "Winters MV, Blake CG, Trost JS, et al. Passive versus active stretching of hip flexor muscles in subjects with limited hip extension: a randomized clinical trial. Phys Ther. 2004;84(9):800-7."
  pmid: "15330693"
  zugang: kein PMC-Volltext; DOI in PubMed nicht angegeben → Beschaffung; DOI auch im Volltext nicht angegeben (V-22)
  themenfelder: [hueftbeuger, methodenvergleich]
  kernaussagen_abstract: n = 33 (Kreuzschmerz/Beinverletzungen mit eingeschränkter Hüftextension), Heimprogramm 6 Wochen; aktives und passives Dehnen der Hüftbeuger gleich wirksam (modifizierter Thomas-Test)
  rolle: einzige chronische Hüftbeuger-RCT bei jungen Erwachsenen im Block
  grenzen: kleine Stichprobe, Patienten statt Gesunde, keine Kontrollgruppe ohne Dehnen
  verifikation: PubMed 2026-09-29
- id: L-T4-22
  status: nicht_aufgenommen
  grund: Entscheidung Athlet 2026-09-29 – nicht beschaffen und nicht verwenden; Eintrag bleibt stehen, ID wird nicht neu vergeben
  stufe: A
  typ: prospektive_kohortenstudie
  zitat: "Witvrouw E, Bellemans J, Lysens R, Danneels L, Cambier D. Intrinsic risk factors for the development of patellar tendinitis in an athletic population. A two-year prospective study. Am J Sports Med. 2001;29(2):190-5."
  pmid: "11292044"
  doi: 10.1177/03635465010290021201
  themenfelder: [knie, verletzungspraevention]
  kernaussagen_abstract: 138 Sportstudierende, 2 Jahre, 19 Fälle; einziger signifikanter Faktor geringere Beweglichkeit von Quadrizeps und Hamstrings
  rolle: Verbindung zu Block R (Patellasehne)
  grenzen: Einzelstudie, wenige Fälle, Assoziation; L-R-11 findet für keinen Risikofaktor starke Evidenz. Kein Beleg, dass Dehnen das Risiko senkt.
  hinweis: Nicht verwechseln mit Witvrouw et al. 2000, Am J Sports Med 28(4):480–489 (vorderer Knieschmerz) – diese Datei wurde irrtümlich hochgeladen und entfernt (2026-09-29)
  verifikation: PubMed 2026-09-29
- id: L-T4-32
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-32_Behm-2025_Science-and-Physiology-of-Flexibility-and-Stretching_2ed.pdf
  kapitel: t4-beweglichkeit/L-T4-32_kapitel/
  stufe: B
  typ: lehrbuch
  zitat: "Behm DG. The Science and Physiology of Flexibility and Stretching: Implications and Applications in Sport Performance and Health. 2. Aufl. New York: Routledge; ©2025."
  isbn: 978-1-032-70907-9 (Paperback); 978-1-032-70908-6 (E-Book, Taylor & Francis)
  sprache: en
  themenfelder: [rom_chronisch, rom_akut, faszienrolle, mobility, methodenvergleich, begriffe]
  zweck: Grundlagen und Begriffe; 2. Aufl. mit neuen Kapiteln zu Faszienrolle, Vibration, globalen Dehneffekten und Krafttraining als Alternative; enthält bebilderten Übungsteil
  grenzen: Einzelautor, der viele Primärstudien aus Teil A mitverfasst hat (Selbstzitation); Regeln stützen sich weiter vorrangig auf Stufe A (D-22)
  zugang: E-Book-PDF mit Lesezeichen und Textebene vorhanden (2026-09-29); Druckseite = PDF-Seite − 15
  verifikation: Händler- und Bibliothekskataloge 2026-09-29 (Erscheinen 30.10.2024, ©2025; 1. Aufl. 2018/2019)
  bestaetigt: Athlet 2026-09-29 (K-8)
- id: L-T4-34
  status: ausgewaehlt
  datei: t4-beweglichkeit/L-T4-34_Nelson-2021_Stretching-Anatomy_3ed.pdf
  datei_epub: t4-beweglichkeit/L-T4-34_Nelson-2021_Stretching-Anatomy_3ed.epub
  kapitel: t4-beweglichkeit/L-T4-34_kapitel/
  stufe: C
  typ: praxisquelle
  konfidenz: niedrig (D-31)
  zitat: "Nelson AG, Kokkonen J. Stretching Anatomy. 3. Aufl. Champaign, IL: Human Kinetics; ©2021."
  isbn: 978-1-4925-9364-5 (Paperback); 978-1-4925-9365-2 (ePub); 978-1-4925-9366-9 (PDF)
  sprache: en
  themenfelder: [uebungskatalog, hueftbeuger, hueftrotation, adduktoren, knie]
  zweck: Übungskatalog (79 Dehnübungen, alle großen Gelenkbereiche; je Übung Anleitung, gedehnte Muskeln, Sicherheitshinweise, Varianten); Dosierung ausschließlich aus Stufe A (D-31), analog L-T2-04
  hinweis: Autoren sind Dehnforscher (Nelson Mitglied des Delphi-Panels L-T4-01); Programmvorschläge im Buch sind keine Dosierungsquelle
  zugang: PDF mit Lesezeichen und Textebene (Druckseite = PDF-Seite − 11) und EPUB (ohne encryption.xml, Seitenmarken doc-pagebreak) vorhanden (2026-09-29); beide Fassungen im Repo (Entscheidung Athlet); zitiert wird nach dem PDF
  verifikation: Verlagsseite Human Kinetics und Impressum (LCCN 2019039889) 2026-09-29; Erstverkauf Print Feb. 2020, Copyright 2021
  bestaetigt: Athlet 2026-09-29 (K-8)
- id: L-T4-24
  status: verweis
  verweis: L-R-11
  rolle: Sprague 2018 – Risikofaktoren Patellatendinopathie, keine starke Evidenz; Gegengewicht zu Knie-Einzelbefunden (L-T4-23; L-T4-22 nicht aufgenommen)
- id: L-T4-33
  status: verweis
  verweis: L-T2-15
  rolle: Warneke 2024 – Dehnen ohne Effekt auf Haltung; begründet, dass T4 keine Haltungsziele verfolgt (D-54 a)
```

Optional:

```yaml
- id: L-T4-07
  status: optional
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Behm DG, Alizadeh S, Daneshjoo A, et al. Acute Effects of Various Stretching Techniques on Range of Motion: A Systematic Review with Meta-Analysis. Sports Med Open. 2023;9(1):107."
  pmid: "37962709"
  pmcid: PMC10645614
  doi: 10.1186/s40798-023-00652-x
  zugang: Open Access, CC BY 4.0 (PMC)
  themenfelder: [rom_akut, adduktoren]
  kernaussagen_abstract: 47 Studien; akuter ROM-Gewinn klein (ES −0,56); Sit-and-Reach, Hamstrings, Wade signifikant, Hüftadduktoren-Test nicht (p = 0,40); keine Unterschiede nach Technik, Intensität, Dauer, Geschlecht, Trainingsstatus; GRADE moderat
  rolle: Beleg für die dünne Hüft-Evidenz (Adduktoren)
  verifikation: PubMed 2026-09-29
- id: L-T4-09
  status: optional
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Warneke K, Zechner M, Siegel SD, et al. Acute and Chronic Effects of Stretching on Running Economy: A Systematic Review with Meta-Analysis. Sports Med Open. 2025;11(1):61."
  pmid: "40442558"
  pmcid: PMC12122984
  doi: 10.1186/s40798-025-00859-0
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen
  themenfelder: [laufen, aufwaermen]
  kernaussagen_abstract: 15 Studien, 181 Teilnehmer; akut kein Effekt auf Laufökonomie (keine Dehnart); chronisch nur qualitativ; niedrige Evidenzsicherheit
  rolle: Dehnen vor dem Laufen muss nicht vermieden werden (T1-Schnittstelle)
  verifikation: PubMed 2026-09-29
- id: L-T4-11
  status: optional
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Afonso J, Ramirez-Campillo R, Moscão J, et al. Strength Training versus Stretching for Improving Range of Motion: A Systematic Review and Meta-Analysis. Healthcare (Basel). 2021;9(4):427."
  pmid: "33917036"
  pmcid: PMC8067745
  doi: 10.3390/healthcare9040427
  zugang: Open Access, CC BY 4.0 (PMC)
  themenfelder: [mobility, methodenvergleich]
  kernaussagen_abstract: 11 RCTs, n = 452; kein Unterschied Krafttraining vs. Dehnen für ROM (ES −0,22, p = 0,21); hohe Heterogenität
  rolle: stützt L-T4-10 (kleiner, RCT-only)
  verifikation: PubMed 2026-09-29
- id: L-T4-13
  status: optional
  datei: t4-beweglichkeit/L-T4-13_Skopal-2024_Mobility-Training-Methods.pdf
  stufe: A
  typ: systematischer_review
  zitat: "Skopal LK, Drinkwater EJ, Behm DG. Application of mobility training methods in sporting populations: A systematic review of performance adaptations. J Sports Sci. 2024;42(1):46-60."
  pmid: "38433623"
  doi: 10.1080/02640414.2024.2321006
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [mobility, yoga]
  kernaussagen_abstract: 22 Studien in Sportpopulationen (Dehnen, Yoga, Pilates u. a.), ≥ 3 Wochen; in 20 von 22 Studien Leistung verbessert oder besser erhalten als Kontrolle; Studienqualität niedrig
  rolle: Mobility/Yoga beeinträchtigt die Leistung nicht
  verifikation: PubMed 2026-09-29
- id: L-T4-15
  status: optional
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Afonso J, Clemente FM, Nakamura FY, et al. The Effectiveness of Post-exercise Stretching in Short-Term and Delayed Recovery of Strength, Range of Motion and Delayed Onset Muscle Soreness: A Systematic Review and Meta-Analysis of Randomized Controlled Trials. Front Physiol. 2021;12:677581."
  pmid: "34025459"
  pmcid: PMC8133317
  doi: 10.3389/fphys.2021.677581
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen (Frontiers, vermutlich CC BY – prüfen)
  themenfelder: [regeneration]
  kernaussagen_abstract: 11 RCTs; Dehnen nach Belastung ohne Effekt auf Kraft-Erholung und Muskelkater (24–72 h) gegenüber passiver Erholung; Evidenz sehr unsicher
  verifikation: PubMed 2026-09-29
- id: L-T4-18
  status: optional
  stufe: A
  typ: systematischer_review
  zitat: "Konrad A, Nakamura M, Sardroodian M, Aboozari N, Anvar SH, Behm DG. The effects of chronic stretch training on musculoskeletal pain. Eur J Appl Physiol. 2025;125(8):2037-2048."
  pmid: "40059246"
  pmcid: PMC12354564
  doi: 10.1007/s00421-025-05747-9
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen
  themenfelder: [schmerz, wohlbefinden]
  kernaussagen_abstract: 6 Studien, 658 Teilnehmer, 4 Wochen bis 6 Monate; 5 von 6 mit weniger Schmerz bzw. geringerer Prävalenz; Ergebnisse gemischt
  grenzen: kleine Evidenzbasis; Schmerz-/Therapiefragen bleiben bei Fachpersonen (N5)
  verifikation: PubMed 2026-09-29
- id: L-T4-20
  status: optional
  stufe: A
  typ: rct
  zitat: "Kerrigan DC, Xenopoulos-Oddsson A, Sullivan MJ, Lelas JJ, Riley PO. Effect of a hip flexor-stretching program on gait in the elderly. Arch Phys Med Rehabil. 2003;84(1):1-6."
  pmid: "12589613"
  doi: 10.1053/apmr.2003.50056
  zugang: kein PMC-Volltext
  themenfelder: [hueftbeuger]
  kernaussagen_abstract: n = 96 Ältere, 2× täglich 10 Wochen Hüftbeuger-Dehnen; statische Hüftextension leicht verbessert, dynamische nur Trend
  grenzen: ältere Population, Übertragung fraglich
  verifikation: PubMed 2026-09-29
- id: L-T4-21
  status: optional
  stufe: A
  typ: rct_crossover
  zitat: "González-de-la-Flor Á, Cotteret C, García-Pérez-de-Sevilla G, Domínguez-Balmaseda D, Del-Blanco-Muñiz JÁ. Comparison of two different stretching strategies to improve hip extension mobility in healthy and active adults: a crossover clinical trial. BMC Musculoskelet Disord. 2024;25(1):853."
  pmid: "39462416"
  pmcid: PMC11515218
  doi: 10.1186/s12891-024-07988-9
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen (BMC, vermutlich CC BY – prüfen)
  themenfelder: [hueftbeuger]
  kernaussagen_abstract: n = 26 aktive Erwachsene; Hüftbeuger-Dehnen im Halbkniestand mit hinterer Beckenkippung senkt akut die Hüftbeuger-Rückstellkraft, klassische Variante (Becken nach vorn) nicht
  rolle: Ausführungshinweis für den Übungskatalog (akuter Befund)
  verifikation: PubMed 2026-09-29
- id: L-T4-23
  status: optional
  stufe: A
  typ: fall_kontroll_studie
  zitat: "Morton S, Williams S, Valle X, Diaz-Cueli D, Malliaras P, Morrissey D. Patellar Tendinopathy and Potential Risk Factors: An International Database of Cases and Controls. Clin J Sport Med. 2017;27(5):468-474."
  pmid: "28151759"
  doi: 10.1097/JSM.0000000000000397
  zugang: kein PMC-Volltext
  themenfelder: [knie]
  kernaussagen_abstract: 825 Datensätze; flexible Hamstrings OR 0,61 (schützend); > 20 h Training/Woche OR 8,94; frühere Knieverletzung OR 2,10
  grenzen: Fall-Kontroll-Design, Fragebogen, keine Kausalität
  verifikation: PubMed 2026-09-29
- id: L-T4-25
  status: optional
  stufe: A
  typ: systematischer_review_metaanalyse
  zitat: "Rudisill SS, Varady NH, Kucharik MP, Eberlin CT, Martin SD. Evidence-Based Hamstring Injury Prevention and Risk Factor Management: A Systematic Review and Meta-analysis of Randomized Controlled Trials. Am J Sports Med. 2023;51(7):1927-1942."
  pmid: "35384731"
  doi: 10.1177/03635465221083998
  zugang: kein PMC-Volltext
  themenfelder: [verletzungspraevention, methodenvergleich]
  kernaussagen_abstract: 108 RCTs; exzentrisches Training senkt Hamstring-Verletzungen um 57–70 %; Beweglichkeit – statisch +10,9°, PNF +9,7°, dynamisch +6,3°, statische Effekte flüchtiger
  hinweis: Online 2022, Heft 2023 – Zitierjahr 2023
  verifikation: PubMed 2026-09-29
- id: L-T4-26
  status: optional
  stufe: A
  typ: validierungsstudie_delphi
  zitat: "Draper N, Giles D, Taylor N, et al. Performance Assessment for Rock Climbers: The International Rock Climbing Research Association Sport-Specific Test Battery. Int J Sports Physiol Perform. 2021;16(9):1242-1252."
  pmid: "33652414"
  doi: 10.1123/ijspp.2020-0672
  zugang: kein PMC-Volltext → Beschaffung
  themenfelder: [klettern, hueftrotation]
  kernaussagen_abstract: 10 Tests, 132 Kletterer; Beweglichkeits- und Krafttests reliabel, unterscheiden Leistungsniveaus nur teilweise; Beweglichkeit vermutlich Schwellenwert
  rolle: kletterspezifische Hüft-Beweglichkeitstests als Verlaufsmessung (Schnittstelle AP-08, T3)
  hinweis: Überschneidung mit L-T3-03 (Langer 2023) prüfen
  verifikation: PubMed 2026-09-29
- id: L-T4-27
  status: optional
  stufe: A
  typ: querschnittstudie
  zitat: "Mermier CM, Janot JM, Parker DL, Swan JG. Physiological and anthropometric determinants of sport climbing performance. Br J Sports Med. 2000;34(5):359-65."
  pmid: "11049146"
  pmcid: PMC1756253
  doi: 10.1136/bjsm.34.5.359
  zugang: Volltext in PMC
  themenfelder: [klettern]
  kernaussagen_abstract: 44 Kletterer; Trainingskomponente erklärt 58,9 % der Leistungsvarianz, Beweglichkeitskomponente (Hüfte, Schulter) 1,8 %
  verifikation: PubMed 2026-09-29
- id: L-T4-28
  status: optional
  stufe: A
  typ: scoping_review
  zitat: "Afonso J, Andrade R, Rocha-Rodrigues S, et al. What We Do Not Know About Stretching in Healthy Athletes: A Scoping Review with Evidence Gap Map from 300 Trials. Sports Med. 2024;54(6):1517-1551."
  pmid: "38457105"
  pmcid: PMC11239752
  doi: 10.1007/s40279-024-02002-7
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen
  themenfelder: [evidenzgrenzen]
  kernaussagen_abstract: 300 Studien, 7080 Sportler; < 20 % chronisch, max. 6 Monate; ~85 % im Aufwärmen; nur 5 Studien mit Verletzungsoutcomes; Dosisangaben uneinheitlich
  rolle: Pflichtinhalt „Grenzen“ der Karte
  verifikation: PubMed 2026-09-29
- id: L-T4-29
  status: optional
  stufe: A
  typ: current_opinion
  konfidenz: mittel
  zitat: "Afonso J, Blazevich AJ, Behm DG, Tilp M, Warneke K. One of These Things Is Not Like the Others: Disentangling the Concepts of Range of Motion Versus Flexibility, and Flexibility Training Versus Stretching. Sports Med. 2026;56(8):1869-1875."
  pmid: "41879914"
  pmcid: PMC13457238
  doi: 10.1007/s40279-026-02425-4
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen
  themenfelder: [begriffe]
  kernaussagen_abstract: ROM umfasst veränderbare und nicht veränderbare Faktoren (z. B. Knochenform); Beweglichkeit ist nur eine Komponente; Beweglichkeitstraining ≠ Dehnen (Kraft in langer Muskellänge, Rolle wirken vergleichbar)
  rolle: Begriffsdefinitionen für Karte und Übungskatalog
  verifikation: PubMed 2026-09-29
- id: L-T4-30
  status: optional
  stufe: A
  typ: current_opinion
  konfidenz: mittel
  zitat: "Warneke K, Blazevich AJ, Jochum D, et al. Perception-Based Methods and Beyond: A Current Opinion on How to Assess Static Stretching Intensity. Sports Med. 2025;55(12):2977-2986."
  pmid: "40952622"
  pmcid: PMC12628444
  doi: 10.1007/s40279-025-02307-1
  zugang: Volltext in PMC; Lizenz nicht ausgewiesen
  themenfelder: [dosierung]
  kernaussagen_abstract: keine konsentierte Definition der Dehnintensität; Schmerz-/Unbehagen-Skalen problematisch; Alternativen (Winkel, % Spitzendrehmoment) im Alltag kaum messbar
  rolle: Begründung, warum Intensität in Plan/Rückmeldung nur subjektiv geführt werden kann (Q-19)
  verifikation: PubMed 2026-09-29
- id: L-T4-31
  status: optional
  stufe: A
  typ: position_stand
  zitat: "Garber CE, Blissmer B, Deschenes MR, et al. American College of Sports Medicine position stand. Quantity and quality of exercise for developing and maintaining cardiorespiratory, musculoskeletal, and neuromotor fitness in apparently healthy adults: guidance for prescribing exercise. Med Sci Sports Exerc. 2011;43(7):1334-59."
  pmid: "21694556"
  doi: 10.1249/MSS.0b013e318213fefb
  zugang: kein PMC-Volltext; frei beim Verlag möglich – prüfen
  themenfelder: [dosierung]
  kernaussagen_abstract: Beweglichkeitsübungen für alle großen Muskel-Sehnen-Gruppen an ≥ 2 Tagen/Woche, insgesamt 60 s je Übung
  grenzen: Gesundheitsempfehlung für Erwachsene, nicht leistungsorientiert; Details (Haltezeit je Wiederholung, Intensität) nur im Volltext
  verifikation: PubMed 2026-09-29
- id: L-T4-35
  status: nicht_aufgenommen
  grund: nicht beschaffbar (Athlet findet keine Ausgabe, 2026-09-29) – Entscheidung Athlet; Eintrag bleibt stehen, ID wird nicht neu vergeben
  stufe: B
  typ: lehrbuch
  zitat: "Freiwald J. Optimales Dehnen. Sport – Prävention – Rehabilitation. 3. Aufl. Balingen: Spitta; 2020."
  isbn: 978-3-941964-71-6 (aus dem bisherigen 13.3-Eintrag übernommen; beim Erwerb prüfen, V-24)
  vorauflagen: 1. Aufl. 2009; 2. Aufl. 2013 (ISBN 978-3-941964-18-1)
  sprache: de
  themenfelder: [begriffe, rom_chronisch, rom_akut, methodenvergleich]
  zweck: deutsches Standardwerk zum Dehnen; deutsche Fachbegriffe; Planung von Dehnprogrammen
  einschraenkung: Stand 2020, vor den Metaanalysen 2023–2026 (L-T4-01 bis L-T4-12) – Dosierungs- und Wirkungsaussagen gegen Stufe A abgleichen; bei Widerspruch gilt Stufe A (D-22)
  revision: ersetzt den 13.3-Eintrag Freiwald (K-8), Entscheidung Athlet 2026-09-29 (SW-E4)
  verifikation: Literaturverzeichnisse Thieme (Zitat 3. Aufl. 2020), Bibliothekskataloge KIT/Palucca (2. Aufl.) 2026-09-29
  bestaetigt: Athlet 2026-09-29 (SW-E4)
- id: L-T4-36
  status: optional
  kapitel: t4-beweglichkeit/L-T4-36_kapitel/
  stufe: C
  typ: sammelwerk (Forschung und Praxis)
  konfidenz: niedrig (D-31)
  zitat: "Schleip R, Wilke J (Hrsg.). Fascia in Sport and Movement. 2. Aufl. Pencaitland: Handspring Publishing; 2021."
  isbn: 9781912085774 (Paperback); 9781912085781 (E-Book)
  umfang: 624 S. (Paperback), 51 Beitragende, 21 neue Kapitel gegenüber der 1. Aufl.
  sprache: en
  themenfelder: [faszienrolle, mobility, begriffe]
  zweck: Hintergrund Faszie und Faszienrolle, Übungsideen
  einschraenkung: gemischte Autorenschaft aus Forschung und Bewegungspraxis – deshalb konservativ Stufe C; Wirkungsaussagen zur Faszienrolle nur aus L-T4-08 und L-T4-12 (Stufe A)
  zugang: Verlags-PDF (618 S., 159 MB, über dem GitHub-Limit von 100 MB, D-31) – im Repo nur als 50 Kapitel-PDFs ohne Gesamtdatei; Druckseite = PDF-Seite − 19
  verifikation: Bibliothekskataloge British Library, Zetech University; Händlerangaben 2026-09-29
  bestaetigt: Athlet 2026-09-29 (SW-E4)
```

Themenfeld-Vokabular T4 (für Karten und Datenmodell): rom_chronisch, rom_akut, dosierung, methodenvergleich, aufwaermen, verletzungspraevention, regeneration, wohlbefinden, schmerz, hueftbeuger, hueftrotation, adduktoren, knie, klettern, laufen, mobility, faszienrolle, yoga, begriffe, evidenzgrenzen, uebungskatalog.

## 13.3 Bewusst nicht aufgenommen

```yaml
- werk: Hohmann/Lames/Letzelter/Pfeiffer – Einführung in die Trainingswissenschaft, 7. Aufl., Limpert 2020
  grund: Theoriekritik wird durch L-P01/L-P02 aktueller und zitierfähig abgedeckt; für T1 redundant zu L-A02 (L-T1-15) und nicht ausdauerspezifisch
- werk: Güllich/Krüger (Hrsg.) – Handbuch Bewegung, Training, Leistung und Gesundheit, Springer 2023
  grund: nur bei Bedarf kapitelweise über SpringerLink; kein Kauf (nicht zu verwechseln mit L-T2-06, Sport – Das Lehrbuch). 2026-09-29 Kapitel Faude O, Donath L – Ausdauer und Ausdauertraining im Sport (Kapitel © 2023) als Alternative zu L-T1-01 geprüft – redundant zu L-A02.
- werk: Weineck – Optimales Training
  grund: kompilatorisch, viele ältere Quellen, tradierte Modelle (Einschätzung); Block T1 und T2 bestätigen den Ausschluss (D-25, D-28) – allenfalls Ideengeber, nie Quelle in Karten
- werk: Grosser/Starischka/Zimmermann – Das neue Konditionstraining; Schnabel/Harre/Krug – Trainingslehre – Trainingswissenschaft
  grund: stark traditionsgebunden (Einschätzung)
- werk: Bompa/Buzzichelli – Periodization
  grund: verkörpert das von L-P01 kritisierte Paradigma; stark präskriptiv (Einschätzung); Block T2 bestätigt (D-28) – allenfalls Ideengeber
- werk: Wade – Convict Conditioning
  grund: anonymer Autor, unbelegte Behauptungen (D-29)
- werk: MacLeod – 9 von 10 Kletterern machen die gleichen Fehler; Make or Break
  grund: Stufe C, meinungsstark; nicht als Begründungsbasis (Block T3, unverifiziert)
- werk: Neumann U – Lizenz zum Klettern
  grund: Klassiker der Bewegungslehre, Stufe C; nicht als Begründungsbasis (Block T3, unverifiziert)
- werk: "Sheikhhoseini R, Shahrbanian S, Sayyadi P, O'Sullivan K. Effectiveness of Therapeutic Exercise on Forward Head Posture: A Systematic Review and Meta-analysis. J Manipulative Physiol Ther. 2018;41(6):530-539. DOI 10.1016/j.jmpt.2018.02.002"
  grund: 7 RCTs; durch L-T2-16 (28 RCTs, 2026) inhaltlich überholt; im Reha-Plan (anderer Chat) bereits verwendet
- werk: "de Campos TF, Maher CG, Fuller JT, et al. Prevention strategies to reduce future impact of low back pain. Br J Sports Med. 2021;55(9):468-476. DOI 10.1136/bjsports-2019-101436"
  grund: bestätigt L-T2-17/18 (Training bzw. Training + Aufklärung reduziert künftige Schmerzintensität/Beeinträchtigung); redundant, Tokenbudget 13.1
- werk: "Huang R, Ning J, Chuter VH, et al. Exercise alone and exercise combined with education both prevent episodes of low back pain and related absenteeism. Br J Sports Med. 2020;54(13):766-770. DOI 10.1136/bjsports-2018-100035"
  grund: Netzwerk-Metaanalyse, 40 RCTs; bestätigt L-T2-17/18; redundant
- werk: "Sepehri S, Sheikhhoseini R, Piri H, Sayyadi P. BMC Musculoskelet Disord. 2024;25(1):105. DOI 10.1186/s12891-024-07224-4"
  grund: Upper Crossed Syndrome, 22 Studien inkl. nicht indexierter Google-Scholar-Funde; durch L-T2-16 überholt
- werk: "Challoumas D, et al. Management of patellar tendinopathy: a systematic review and network meta-analysis of randomised studies. BMJ Open Sport Exerc Med. 2021;7(4):e001110. DOI 10.1136/bmjsem-2021-001110"
  grund: durch L-R-05 (Living Review 2023) überholt; nennt exzentrisches Training als Erstlinie, was durch L-R-01 relativiert ist
- werk: "Rio E, et al. Br J Sports Med. 2015;49(19):1277-83. DOI 10.1136/bjsports-2014-094386"
  grund: n = 6 (Crossover); Replikation (Holden 2020) und Reviews (L-R-10, Soliman 2026) ohne Überlegenheit der Isometrik
- werk: "Holden S, et al. J Sci Med Sport. 2020;23(3):208-214. DOI 10.1016/j.jsams.2019.09.015"
  grund: durch L-R-10 abgedeckt
- werk: "Soliman EFF, et al. Isometric Exercises for Tendinopathies. Clin Ther. 2026;48(10):952-957. DOI 10.1016/j.clinthera.2026.04.027"
  grund: bestätigt L-R-10; redundant
- werk: "Malliaras P, Cook JL, Kent P. J Sci Med Sport. 2006;9(4):304-9. DOI 10.1016/j.jsams.2006.03.015"
  grund: Querschnittstudie; durch L-R-11/L-R-12 abgedeckt
- werk: "van der Worp H, et al. Risk factors for patellar tendinopathy: a systematic review. Br J Sports Med. 2011;45(5):446-52. DOI 10.1136/bjsm.2011.084079"
  grund: durch L-R-11 überholt; im Reha-Chat als „KSSTA 2011" zitiert – passt zu keiner Arbeit exakt (KSSTA-Arbeit von van der Worp ist ein Stoßwellen-Review 2013, DOI 10.1007/s00167-012-2009-3)
- werk: "Guo NY, et al. ESWT in Tendinopathy: Network Meta-Analysis. Orthop Surg. 2026. DOI 10.1111/os.70408"
  grund: bestätigt L-R-05 (Stoßwelle bei Patellasehne ohne Effekt); Nebenthema
- werk: "Metaanalyse „9 RCTs, 341 Patienten, CAIT MD 3,95“ (Reha-Chat, ohne Autor/Journal)"
  grund: per PubMed nicht identifizierbar; Aussagen durch L-R-16, L-R-20 abgedeckt
- werk: "Metaanalyse „33 RCTs, 1154 CAI-Patienten, kombiniertes Training tendenziell besser“ (Reha-Chat, ohne Autor/Journal)"
  grund: per PubMed nicht identifizierbar; Aussagen durch L-R-16, L-R-20 abgedeckt
- werk: "Sánchez-Barbadora M, et al. PeerJ. 2025;13:e19461. DOI 10.7717/peerj.19461"
  grund: vermutliche Quelle der Reha-Chat-Aussage zur „anatomisch kippenden Unterlage"; Akut-EMG an 30 Gesunden, kein Trainingseffekt, kein Vergleich mit Schaumstoff im Abstract
- werk: "Donovan L, Hart JM, Hertel J. J Orthop Sports Phys Ther. 2015;45(3):220-32. DOI 10.2519/jospt.2015.5222"
  grund: Akut-EMG mit Destabilisierungsschuhen; durch L-R-17 (Trainingsstudie) abgedeckt
- werk: "Donovan L, et al. Phys Ther Sport. 2016;21:46-56. DOI 10.1016/j.ptsp.2016.02.006"
  grund: Gangbild-Auswertung derselben Studie wie L-R-17
- werk: "Liu S, et al. Exploratory Analysis of Unstable Surface Training for CAI. Arch Rehabil Res Clin Transl. 2024;6(4):100365. DOI 10.1016/j.arrct.2024.100365"
  grund: explorativ; kein direkter Vergleich stabil vs. instabil; bestätigt Balanceeffekt, keinen Effekt auf Sprungfunktion
- werk: "Giboin LS, et al. NeuroImage. 2019;202:116061. DOI 10.1016/j.neuroimage.2019.116061"
  grund: bestätigt L-R-21 (Aufgabenspezifität); redundant
- werk: "Buist I, et al. Am J Sports Med. 2008;36(1):33-9. DOI 10.1177/0363546507307505"
  grund: RCT, 10-%-Regel ohne präventive Wirkung bei Anfängern; durch L-R-24 abgedeckt
- werk: "Damsted C, et al. Int J Sports Phys Ther. 2018;13(6):931-942. PMC6253751"
  grund: systematischer Review, sehr begrenzte Evidenz für Laststeigerung als Risikofaktor; durch L-R-24 abgedeckt
- werk: "Metaanalysen zu Blutflussrestriktion (Liu J 2026, Wu 2026, Liu M 2025), Hüftkräftigung (Chen 2026), stroboskopischem Training (Luo 2025), Balance mit geschlossenen Augen (Chen 2025), Gangtraining (Ortega 2025), manueller Therapie (Salminen 2026) bei CAI"
  grund: Randthemen, überwiegend niedrige Evidenzqualität; Tokenbudget 13.1
- werk: "Breda SJ, et al. J Sci Med Sport. 2022;25(5):372-378; Fendri T, et al. J ISAKOS. 2026;18:101105; López-Royo MP, et al. 2021/2024; Herrero C, et al. 2024 (PRP)"
  grund: Nebenfragen (Sehnensteifigkeit, Nadelverfahren, PRP) ohne Planungsrelevanz
- werk: "Schoenfeld BJ, Grgic J, Ogborn D, Krieger JW. Strength and Hypertrophy Adaptations Between Low- vs. High-Load Resistance Training: A Systematic Review and Meta-analysis. J Strength Cond Res. 2017;31(12):3508-3523. DOI 10.1519/JSC.0000000000002200"
  grund: durch L-T2-23 (Netzwerk-Metaanalyse 2021) und L-T2-28 abgedeckt
- werk: "Schoenfeld BJ, Ogborn D, Krieger JW. Effects of Resistance Training Frequency on Measures of Muscle Hypertrophy. Sports Med. 2016;46(11):1689-1697. DOI 10.1007/s40279-016-0543-8"
  grund: 10 Studien; durch L-T2-27 (2019) und L-T2-20 (2026) überholt
- werk: "Vieira AF, et al. Effects of Resistance Training Performed to Failure or Not to Failure on Muscle Strength, Hypertrophy, and Power Output. J Strength Cond Res. 2021;35(4):1165-1175. DOI 10.1519/JSC.0000000000003936"
  grund: durch L-T2-22 und L-T2-30 abgedeckt
- werk: "Grgic J. The Effects of Low-Load Vs. High-Load Resistance Training on Muscle Fiber Hypertrophy: A Meta-Analysis. J Hum Kinet. 2020;74:51-58. DOI 10.2478/hukin-2020-0013"
  grund: 10 Studiengruppen, sehr weite Intervalle; durch L-T2-23 und L-T2-28 abgedeckt
- werk: "de Oliveira PA, et al. Effects of Elastic Resistance Exercise on Muscle Strength and Functional Performance in Healthy Adults. J Phys Act Health. 2017;14(4):317-327. DOI 10.1123/jpah.2016-0415"
  grund: 5 Studien; durch L-T2-24 (8 Studien) abgedeckt
- werk: "Moesgaard L, et al. Effects of Periodization on Strength and Muscle Hypertrophy in Volume-Equated Resistance Training Programs. Sports Med. 2022;52(7):1647-1666. DOI 10.1007/s40279-021-01636-1"
  grund: Periodisierungsfrage ohne Hypertrophie-Effekt; Kontroverse durch L-P01, L-P02, L-P08 abgedeckt
- werk: "Hickmott LM, et al. The Effect of Load and Volume Autoregulation on Muscular Strength and Hypertrophy. Sports Med Open. 2022;8(1):9. DOI 10.1186/s40798-021-00404-9"
  grund: Autoregulation über RPE/Geschwindigkeit; Nebenfrage ohne Planungsrelevanz für die Ergänzung
- werk: "Metaanalysen zu Blutflussrestriktion im Vergleich zu hoher Last (Chang 2024, Life, DOI 10.3390/life14111442; Geng 2024, Sports Med Open, DOI 10.1186/s40798-024-00719-3; Fabero-Garrido 2022, J Clin Med, DOI 10.3390/jcm11247389)"
  grund: Methode mit Manschetten, außerhalb des Schwerpunkts leichte Lasten/Band; Blutflussrestriktion bei Patellasehne bereits als L-R-28 geführt
- werk: "Metaanalysen an Älteren, Gebrechlichen oder Patienten zu Volumen, Frequenz, Band und Heimtraining (Radaelli 2024, Sports Med, DOI 10.1007/s40279-024-02123-z; Kneffel 2021, J Sports Sci, DOI 10.1080/02640414.2020.1822595; Nunes 2024, Arch Gerontol Geriatr, DOI 10.1016/j.archger.2024.105474; Zhao 2022, IJERPH, DOI 10.3390/ijerph192315491; Meng 2025, Front Sports Act Living, DOI 10.3389/fspor.2025.1649305; Puelles-Diaz 2026, Rehabilitacion, DOI 10.1016/j.rh.2026.100961; Zhu 2026, Front Public Health, DOI 10.3389/fpubh.2026.1910792; Costa 2023, J Aging Phys Act, DOI 10.1123/japa.2022-0221)"
  grund: Population (Ältere, Gebrechliche, Typ-2-Diabetes); Übertragung auf den Athleten eingeschränkt
- werk: "Ferraro-Farro D, et al. Does Sprint Interval Training Cause Interference in Concurrent Training? Int J Sports Med. 2026;47(10):747-759. DOI 10.1055/a-2820-4527"
  grund: Hypertrophie im Abstract ohne gepooltes Ergebnis; Nebenthema
- werk: "L-T1-01 Hottenrott K, Seidel I (Hrsg.) – Handbuch Trainingswissenschaft – Trainingslehre, 2. Aufl., Hofmann 2025"
  grund: keine digitale Ausgabe; Rolle durch L-A02 abgedeckt (L-T1-15, D-70); Eintrag bleibt in 13.2.2 mit Status nicht_aufgenommen
- werk: "Hottenrott K, Hoos O – Sportmotorische Fähigkeiten und sportliche Leistungen – Trainingswissenschaft. In: Güllich A, Krüger M (Hrsg.), Sport – Das Lehrbuch für das Sportstudium, 2. Aufl., Springer, S. 563–634"
  grund: geprüfte Alternative zu L-T1-01 (2026-09-29); redundant zu L-A02; Buch bereits als L-T2-06 optional – Kapitel bei Bedarf dort
- werk: "Hottenrott K, Neumann G – Trainingswissenschaft. Ein Lehrbuch in 14 Lektionen, Meyer & Meyer 2016"
  grund: geprüfte Alternative zu L-T1-01 (2026-09-29); älter und inhaltlich durch L-A02 abgedeckt; E-Book-Format nicht geprüft
- werk: "Seifert L, Wolf P, Schweizer A (Hrsg.) – The Science of Climbing and Mountaineering, Routledge 2016"
  grund: geprüfte Alternative zu L-T3-08 (2026-09-29); keine Trainingsplanung/Periodisierung, Überschneidung mit L-T3-06
- werk: "Augste C, Winkler M, Künzell S – Leistungsstrukturanalyse Sportklettern, German Journal of Exercise and Sport Research 2022 (Open Access, CC BY 4.0); dazu BISp-Projektbericht zur Leistungsdiagnostik im Sportklettern und Dissertation Winkler 2023 (Augsburg)"
  grund: Leistungsstruktur und Diagnostik, keine Trainingsmethodik; bei Bedarf als Ergänzung zu L-T3-03 reaktivierbar; Titel und Bibliografie nicht vollständig geprüft
- werk: "L-T4-22 Witvrouw E et al. Intrinsic risk factors for the development of patellar tendinitis in an athletic population. Am J Sports Med. 2001;29(2):190-5"
  grund: Entscheidung Athlet 2026-09-29 – nicht aufgenommen; Eintrag bleibt in 13.2.6 mit Status nicht_aufgenommen; Knie-Bezug über L-T4-23 (optional) und L-R-11
- werk: "Alter MJ. Science of Flexibility. 3. Aufl. Champaign, IL: Human Kinetics; 2004. ISBN 978-0-7360-4898-9"
  grund: früherer Klassiker, Stand 2004 überholt; Autor ist kein Forscher
- werk: "van der Poel G. The Science of Flexibility, Stretching and Mobility. London: Handspring; 2026. ISBN 978-1-80501-754-7"
  grund: zu neu für eine Qualitätseinschätzung (Erscheinen 21.05.2026); Kandidat für später
- werk: "Thacker SB et al. The impact of stretching on sports injury risk. Med Sci Sports Exerc. 2004;36(3):371-8. PMID 15076777"
  grund: durch L-T4-14 und L-T4-01 überholt
- werk: "Arntz F et al. Chronic Effects of Static Stretching Exercises on Skeletal Muscle Hypertrophy. Sports Med Open. 2024;10:106. PMID 39340744"
  grund: Hypertrophie kein Ziel (D-28, D-62); Konsens L-T4-01 deckt die Aussage ab
- werk: "Konrad A et al. Antagonist stretching – agonist performance and ROM. Sports Med Open. 2026. PMID 42426324"
  grund: Spezialfrage ohne Planungsrelevanz; Effekt trivial
- werk: "Warneke K et al. Discussing Conflicting Explanatory Approaches in Flexibility Training. Sports Med. 2024;54(7):1785-1799. PMID 38819597"
  grund: Mechanismen, für Planung nicht nötig; bei Bedarf nachziehen
- werk: "Behm DG et al. Potential Effects of Dynamic Stretching on Injury Incidence. Sports Med. 2023;53(7):1359-1373. PMID 37162736"
  grund: narrativ, laut Autoren kaum Daten; Aussage in L-T4-01/L-T4-06 enthalten
- werk: "Guo W et al. Stretching techniques and balance (Meta-Analyse). Ann Hum Biol. 2025. PMID 40643578"
  grund: Balance ist in Block R abgedeckt; Effekt minimal
- werk: "Yoga-Reviews Singh 2025 (PMID 41316576), Rathore 2024 (PMID 39593648), Hot Yoga Willmott 2025 (PMID 41032153)"
  grund: niedrige Qualität bzw. andere Population/Fragestellung; Yoga-Übungen werden in Teil B als Stufe C gesucht
- werk: "Akut-/Gerätestudien Hüfte: Cotteret 2025 (Lightback, PMID 39926033), Metgud 2022 (MWM, PMID 36180152), Yan 2024 (Jugendliche, PMID 39121315)"
  grund: Gerät/Technik nicht verfügbar, akut oder andere Population
- werk: "Patellasehne mit Dehnanteil: Sannasi 2025 (PMID 41316610), Niering 2023 (PMID 36752745), Ragone 2024 (PMID 38391804)"
  grund: Dehnen nur als Teil von Kombinationsprogrammen, Einzeleffekt nicht bestimmbar; Therapie liegt bei Block R und Fachpersonen (N5)
- werk: "Brukner P, Khan K (Hrsg.). Brukner & Khan's Clinical Sports Medicine: Foundations of Clinical Practice. 6. Aufl. McGraw-Hill Education (Australia); 2025. ISBN 9781761000010"
  grund: Q-22 (Entscheidung Athlet 2026-09-29) – Belastungssteuerung, Erholung und Schmerz über Stufe-A-Quellen abgedeckt (L-P03 bis L-P05, L-P12, L-P13)
- werk: "L-T4-35 Freiwald J. Optimales Dehnen. 3. Aufl. Balingen: Spitta; 2020"
  grund: nicht beschaffbar (2026-09-29, Entscheidung Athlet); Eintrag bleibt in 13.2.6 mit Status nicht_aufgenommen; deutsche Fachbegriffe über L-A02
- werk: "Daniels J. Daniels' Running Formula. 4. Aufl. Champaign, IL: Human Kinetics; ©2022. ISBN 978-1-7182-0366-2"
  grund: Straßenlauf mit Pace-/VDOT-System; Zonenreferenz ist D-27 (Herzfrequenz, Drei-Zonen-Modell) und Gelände – nicht übertragbar
- werk: "Brennauer M. Handbuch Trailrunning. Training – Vorbereitung – Wettkampf. Aachen: Meyer & Meyer; 2022. ISBN 978-3-8403-7796-9"
  grund: deutsches Praxisbuch (Stufe C); Standardwerk-Status nicht belegt; Trail-Praxis über L-T1-08 und L-T1-16 abgedeckt
- werk: "Deutscher Alpenverein (Hrsg.). Skimo Handbuch – Erfolgreiche Nachwuchsarbeit im Skibergsteigen. München: DAV; online Januar 2025"
  grund: Leitfaden für Nachwuchs- und Kaderarbeit, für einen erwachsenen Freizeitathleten ungeeignet. Befund der Recherche – es gibt kein Standardwerk zum Skibergsteigen-Training (weder englisch noch deutsch); Skimo bleibt bei L-T1-06 und L-T1-08 Kap. 12
- werk: "de Marées H. Sportphysiologie. Köln: Sportverlag Strauß; 9., vollst. überarb. Aufl. 2002/2003, korrigierte Nachdrucke (u. a. 2006, ISBN 978-3-939390-00-8)"
  grund: deutscher Klassiker, seit 2003 nicht überarbeitet. Befund – kein aktuelles deutsches Pendant zu L-A01; deutsche Terminologie über L-A02
- werk: "Fleck SJ, Kraemer WJ. Designing Resistance Training Programs. 4. Aufl. Champaign, IL: Human Kinetics; 2014. ISBN 978-0-7360-8170-2"
  grund: Stand 2014; redundant zu L-A03 (5. Aufl.), L-T2-05 und L-P08
- werk: "Freiwald J, Greiwing A. Optimales Krafttraining. Sport – Prävention – Rehabilitation. Balingen: Spitta; 2016. ISBN 978-3-938509-18-0"
  grund: deutsche Krafttrainings-Terminologie über L-A02 Kap. 4 und L-T2-06 abgedeckt; Dosierung aus L-P08 (D-28)
- werk: "Issurin VB. Block Periodization: Breakthrough in Sports Training. Hrsg. Yessis M. Michigan: Ultimate Athlete Concepts; 2008. ISBN 978-0-9817180-0-2"
  grund: wie Bompa – präskriptives Periodisierungsmodell aus dem Hochleistungssport; Kontroverse über L-P01, L-P02, L-P08 abgedeckt (D-28); allenfalls Ideengeber
hinweis: Auflagen der nicht aufgenommenen Werke wurden nicht geprüft (Ausnahme: Einträge aus D-80, geprüft 2026-09-29).
```

## 13.4 Beschaffungsliste (Verantwortung Athlet, D-26)

Formatprüfung je Titel vor dem Kauf (V-13). EPUB ohne DRM ist zulässig (D-71). Alle Blöcke sind bestätigt (D-31). Spalte „vorhanden“: Abgleich mit `docs/literatur/` (D-51).

| prio | block | quelle | benötigt | formatanforderung | bemerkung | vorhanden (2026-09-29) |
|---|---|---|---|---|---|---|
| 1 | übergreifend | L-A01 Kenney/Wilmore/Costill | 7. Aufl. 2019 (Nachtrag D-51) | durchsuchbares PDF | vorhandene Ausgabe gilt | ✓ 7. Aufl. 2019 als E-Book-PDF |
| 1 | übergreifend | L-A02 Ferrauti, 2. Aufl. | Jahr/ISBN offen | Springer-Kapitel-PDF | | ✓ 2. Aufl. 2025, Gesamt-PDF; Jahr und ISBN geklärt |
| 1 | übergreifend | L-P02, L-P03, L-P04, L-P05, L-P06, L-P09 | Artikel | PDF | nicht in PMC → Bibliothekszugang | ✓ alle |
| 1 | T1 | L-T1-02 Seiler 2010, L-T1-03 Casado 2022, L-T1-05 Vernillo 2017 | Artikel | PDF | Casado: zuerst freie Repositoriumsfassung prüfen | ✓ alle |
| 1 | T2 | L-A03 NSCA, 5. Aufl. | Buch | kein VitalSource-DRM → Print oder anderer Anbieter | | ✓ E-Book-PDF mit Lesezeichen |
| 1 | T2 | L-T2-03 Schumann/Rønnestad 2019 | Buch | Springer-Kapitel-PDF | | ✓ Gesamt-PDF |
| 1 | T2 | L-T2-04 Low, Overcoming Gravity, 2. Aufl. | Buch | PDF/ePUB beim Autor prüfen | | ✓ Scan; Texterkennung fehlerhaft |
| 1 | übergreifend | L-P13 Silbernagel 2007 (AJSM) | Artikel | PDF | Pflicht für V-07 | ✓ |
| 1 | übergreifend | L-P10 Foster 2001 (JSCR), L-P12 Impellizzeri 2020 (IJSPP) | Artikel | PDF | nicht in PMC → Bibliothekszugang; Open-Access-Ersatz für L-P12 im Eintrag | ✓ beide |
| 1 | T2 | L-T2-17 Shiri 2018 (AJE), L-T2-18 Steffens 2016 (JAMA IM) | Artikel | PDF | nicht in PMC → Bibliothekszugang | ✓ beide |
| 2 | T1 | L-T1-07 Laursen/Buchheit | Buch | kein VitalSource-DRM | Teil Grundlagen Intervallprogrammierung + Kapitel Lauf/Ausdauer | ✓ PDF mit Lesezeichen, Kapitel-PDFs |
| 2 | T1 | L-T1-08 Uphill Athlete | Buch | PDF bevorzugt; E-Book-DRM prüfen | Praxisquelle | ✓ Scan mit Texterkennung |
| 2 | T3 | L-T3-02 Langer 2023 (JSCR) | Artikel | PDF | kostenpflichtig | ✓ |
| 2 | T3 | L-T3-06 Climbing Medicine 2022 (bevorzugt) oder L-T3-07 Klettermedizin 2020 (Alternative) | Buch, eine Ausgabe (D-31) | Springer-Kapitel-PDF | | ✓ L-T3-06, Gesamt-PDF |
| 2 | T3 | L-T3-19 Consuegra, The Science of Climbing Training (2023) | Buch | EPUB ohne DRM (D-71) | ersetzt L-T3-08 im Evidenzkern (D-70) | ✓ EPUB 2026-09-29, Kapitel als Markdown und Ansichts-PDF |
| 2 | T3 | L-T3-16 Bechtel, Logical Progression, 2. Aufl. | Buch | DRM-freies EPUB oder PDF; Kindle ungeeignet | Stufe C, Planungsvorlage | ✓ PDF 2. Aufl., Kapitel-PDFs |
| 2 | T3 | L-T3-15 Anderson/Anderson, The Rock Climber's Training Manual (2014) | Buch | durchsuchbares PDF oder EPUB ohne DRM (D-26, D-71) | Stufe C, optional; Entscheidung Athlet 2026-09-29 | offen |
| 2 | T3 | L-T3-10 Mobråten/Christophersen, The Climbing Bible | Buch | DRM-freies EPUB oder PDF; Kobo-EPUB (Adobe DRM) ungeeignet | Stufe C, Ideenfundus | ✓ EPUB ohne DRM 2026-09-29, Kapitel als Markdown und Ansichts-PDF |
| 2 | T3 | L-T3-20 Mobråten/Christophersen, The Climbing Bible – Practical Exercises; L-T3-21 Christophersen, Managing Injuries | Buch | EPUB ohne DRM (D-71) | Stufe C; L-T3-21 vorläufig | ✓ beide EPUB 2026-09-29, Kapitel als Markdown und Ansichts-PDF |
| 2 | T3 | L-T3-09 Hörst, Training for Climbing, 3. Aufl. 2016 | Buch | durchsuchbares PDF | Evidenzkern (D-70) | ✓ 3. Aufl., Scan mit Texterkennung, Kapitel-PDFs |
| 2 | T2 | L-T2-11 Rønnestad & Mujika 2014 | Artikel | PDF | nicht in PMC | ✓ |
| 2 | T3 | L-T3-05 López-Rivera 2012 (Sports Technology) | Artikel | PDF | nur falls L-T3-18 nicht genügt | ✓ |
| 2 | T2 | L-T2-10 Wiedenmann et al. 2025 (Gerontology 71(7):576–588) | Artikel | PDF | Beleg Körpergewichtstraining (D-29); Population Ältere; Zugang nicht geprüft | ✓ |
| 2 | T3 | L-T3-04 Draper et al. 2015 (Sports Technology 8(3-4):88–94) | Artikel | PDF | IRCRA-Positionspapier, Graduierung/Leistungsniveau (Datenmodell); Taylor & Francis | ✓ |
| frei | alle | L-P01, L-P07, L-P08, L-T1-04, L-T1-06, L-T3-01, L-T3-03; optional L-T1-09, L-T1-10, L-T1-12 | – | PDF aus PMC bzw. Verlag (OA) | kein Kauf | ✓ alle |
| frei | übergreifend/T2/T3 | L-P11 (PMC), L-T2-12 (CC BY 4.0), L-T3-03 (CC BY), L-T3-18 (PMC) | – | PDF aus PMC/Verlag | L-P11 CC BY-NC 4.0 laut Volltext | ✓ alle |
| frei | T2 | L-T2-15 Warneke 2024, L-T2-16 Khorramroo 2026 | – | PDF aus PMC | Lizenz vor Ablage im Repo prüfen | ✓ beide (CC BY 4.0) |
| bei Bedarf | – | L-T1-11, L-T1-14, L-T2-05, L-T2-06, L-T3-11 | – | – | nur wenn optional aktiviert | – |
| frei | übergreifend | L-P15 Manresa-Rocamora 2021 | Artikel | PDF aus PMC | HRV-gesteuertes Training (D-70); Lizenz vor Ablage prüfen | ✓ (CC BY 4.0) |
| bei Bedarf | übergreifend | L-P14 Impellizzeri 2021 | Artikel | PDF | optional | ✓ |
| bei Bedarf | übergreifend | L-P16 Düking 2021 (JSAMS) | Artikel | PDF | optional; nicht in PMC → Bibliothekszugang | ✓ Verlagsfassung (Pre-Proof-Dublette entfernt) |
| bei Bedarf | T2 | L-T2-14 Cowley 2026 (PMC), L-T2-19 Carrasco-Uribarren 2026 | – | PDF | optional | ✓ beide |
| 1 | R | L-R-02 Kongsgaard 2009 | Artikel | PDF | Schmerzregel für Q-13 | ✓ |
| 1 | R | L-R-13 Martin 2021 (JOSPT-Leitlinie) | Artikel | PDF | Einzelempfehlungen, Q-16 | ✓ |
| 1 | R | L-R-08 Lohrer & Nauck 2011 | Artikel | PDF | validierter Wortlaut VISA-P-G für WebApp | ✓ mit Erratum 2013 (Punktwerte 8b/8c) |
| 2 | R | L-R-03 Agergaard 2021, L-R-26 Doherty 2017 | Artikel | PDF | nicht in PMC | ✓ beide |
| frei | R | L-R-01, -04, -05, -06, -10, -11, -14, -16, -17, -21, -23, -24, -25, -27 | – | PDF aus PMC | Lizenzen vor Ablage im Repo prüfen (D-31) | ✓ alle |
| bei Bedarf | R | L-R-07, -09, -12, -15, -18, -19, -20, -22, -28 | Artikel | PDF | optional bzw. Kernaussage aus Abstract ausreichend | ✓ alle (L-R-18 Autorenmanuskript) |
| 2 | T2 | L-T2-20 Pelland 2026, L-T2-21 Robinson 2024 (Sports Med), L-T2-26 Monserdà-Vilaró 2023 (JSCR) | Artikel | PDF | nicht in PMC → Bibliothekszugang | ✓ alle |
| frei | T2 | L-T2-22 Refalo 2023, L-T2-23 Lopez 2021 (mit Corrigendum), L-T2-24 Lopes 2019 (mit Corrigendum), L-T2-25 Lundberg 2022 | – | PDF aus PMC | L-T2-24 CC BY-NC 4.0; übrige ohne Lizenzangabe → vor Ablage im Repo prüfen (D-31) | ✓ alle; Corrigenda L-T2-23/-24 nicht enthalten |
| bei Bedarf | T2 | L-T2-27 bis L-T2-32 | Artikel | PDF | optional; L-T2-30 in PMC | ✓ alle (L-T2-29 Autorenmanuskript) |
| 1 | T4 | L-T4-32 Behm 2025, 2. Aufl.; L-T4-34 Nelson/Kokkonen, Stretching Anatomy, 3. Aufl. | Buch | durchsuchbares PDF oder EPUB ohne DRM (D-26, D-71) | Lehrbuch Stufe B; Übungskatalog Stufe C (K-8) | ✓ beide als PDF mit Kapitel-PDFs, L-T4-34 zusätzlich als EPUB |
| 2 | T4 | L-T4-05 Thomas 2018 (IJSM), L-T4-06 Behm 2016 (APNM), L-T4-13 Skopal 2024 (J Sports Sci), L-T4-14 Lauersen 2014 (BJSM), L-T4-17 Behm 2026 (EJAP), L-T4-19 Winters 2004 (Phys Ther) | Artikel | PDF | nicht in PMC → Bibliothekszugang (D-79) | ✓ alle |
| 2 | T4 | L-T4-16 Herbert 2011 (Cochrane) | Artikel | PDF | Abstract frei, Volltext Cochrane Library | ✓ |
| frei | T4 | L-T4-01, -02, -03, -04, -08, -10, -12 | – | PDF aus PMC | Lizenzen vor Ablage prüfen (V-20) | ✓ alle |
| bei Bedarf | T4 | L-T4-07, -09, -11, -15, -18, -20, -21, -23, -25 bis -31 | Artikel | PDF | optional; in PMC: -07, -09, -11, -15, -18, -21, -27 bis -30; L-T4-31 ggf. frei beim Verlag | – |
| 2 | R | L-R-29 Brukner & Khan, Managing Injuries, 6. Aufl. | Buch | DRM-freies PDF oder EPUB (D-26, D-71) | Stufe B, optional; Inhaltsverzeichnis prüfen (V-24) | offen |
| 2 | R | L-R-30 GOTS-Manual (3. Aufl. 2016 statt 4. Aufl.) | Buch | DRM-freies PDF oder EPUB | Stufe B, optional; deutsches Gegenstück zu L-R-29 | ✓ 3. Aufl., Kapitel-PDFs |
| 2 | T2 | L-T2-33 McGill, Low Back Disorders (3. Aufl. 2016 statt 4. Aufl.) | Buch | kein VitalSource-DRM (D-26) | Stufe B, optional | ✓ 3. Aufl., Kapitel-PDFs |
| 2 | T1 | L-T1-16 Koop, Training Essentials for Ultrarunning, 2. Aufl. | Buch | Kindle ungeeignet (D-71); Hardcover beim Autor | Stufe C, optional | ✓ EPUB, Kapitel-Markdown und Ansichts-PDFs |
| 2 | T4 | L-T4-36 Schleip/Wilke, Fascia in Sport and Movement, 2. Aufl. | Buch | DRM-freies PDF oder EPUB | Stufe C, optional | ✓ nur Kapitel-PDFs (Gesamt-PDF 159 MB) |

Stand 2026-09-29: 116 Werke als Volltext vorhanden (D-51, D-71; L-T4-36 nur als Kapitel-PDFs), Verzeichnis in `docs/literatur/README.md`. Vorhandene Ausgaben gelten, auf neuere Auflagen wird nicht gewartet (Nachtrag D-51). Offen sind 2 Bücher (L-R-29 Brukner & Khan, L-T4-35 Freiwald). Bei Bedarf: T4 15 Artikel; optionale Bücher L-T1-11, L-T1-14, L-T2-05, L-T2-06, L-T3-11.

# 14. Trainerregeln (Struktur; Inhalte in AP-07)

Ablage: `docs/regeln/trainerregeln.md`. Jede Regel mit `id`, `regel`, `quelle`, `konfidenz`.

Vorgesehene Kapitel:
1. Prioritäten je Blockphase (welcher Bereich T1–T4 hat Vorrang; Konfliktauflösung im Wochenplan).
2. Sequenzierung (Abstände zwischen intensiven Finger-Einheiten; harte Läufe nicht am Vortag von Limit-Bouldern; Haltungsarbeit als niedrigschwelliger Filler).
3. Progression je Bereich (Ausdauer: Volumen-/Intensitätsschritte, Entlastungswochen; Kraft: doppelte Progression; Hangboard: Last-/Kanten-/Zeitprogression).
4. Deload-Trigger (Kombination aus subjektiven Markern, sRPE-Wochenlast, HRV/Ruhepuls-Abweichung, Schmerzereignissen). Literatur: L-P15 (Kern), L-P16 (optional); Baseline-Bildung (fix vs. gleitend) laut L-P15 ungeklärt.
5. Schmerzregeln (vorläufig, V-07; Abgleich mit dem Modell nach L-P13 offen, Q-13): Schmerz ≤ 3/10 während der Belastung und am Folgemorgen abgeklungen → fortfahren; 4–5/10 oder Morgenschmerz → Belastung der Struktur reduzieren; > 5/10, über eine Woche steigend oder akutes Ereignis (z. B. Knall/Riss-Gefühl am Finger) → Belastung der Struktur stoppen, klinische Abklärung vor Weitertraining.
6. Datenqualitätsregeln (Abschnitt 11).
7. Schreibregel D-11 (nur nach Bestätigung).
8. Zonenmodell: Abbildung des Drei-Zonen-Modells (LT1/LT2) auf die fünf Garmin-Zonen (%LTHR), Grenzwerte, Umgang mit nur einer Schwelle auf der Uhr (D-27). Kletterregeln tragen die Kennzeichnung „Evidenz: begrenzt“ (13.2.4).
9. Übungskatalog (AP-16, D-64 bis D-69): R-UEB-10 bis R-UEB-14 – `find_exercise` vor jeder Planung, Anlage nach Bestätigung mit `hinweis_chat`, Quellen- und Konfidenzpflicht, keine erfundenen Links, Vorsicht mit Reha-Regel und Schmerzgrenze, die Einheit beschreibt nur die Dosierung. Als Vorabkapitel in `docs/regeln/trainerregeln.md` (Wortlaut aus `docs/konzept/uebungskatalog.md` Abschnitt 8); AP-07 übernimmt es beim Ausformulieren.
10. Übergabe, Revision, Bilanz und Zielklärung (AP-15, D-72 bis D-78): R-UEB-01 bis R-UEB-06 – jede Planungssitzung beginnt mit `get_handover`, Fälligkeiten vor dem Wochenvorschlag nennen; Zielklärung nie ohne den Athleten, jede Entscheidung mit mindestens einer verworfenen Alternative oder „keine“; die Bilanz bewertet jedes Ziel („nicht bewertbar“ mit Grund); Änderungen an Trainerregeln nur aus einer Bilanz oder einem Schmerzereignis; Revisionen ändern Belastung, nicht Ziele; keine Wochen über das Blockende ohne bestätigte Zielklärung des Folgeblocks. Als Vorabkapitel in `docs/regeln/trainerregeln.md` (Wortlaut aus `docs/konzept/blockbilanz.md` 8.2); bis AP-07 gelten die Tool-Beschreibungen.

# 15. Arbeitspakete

Reihenfolge Code-Instanz: AP-00 → **AP-01a (Fable, Vorarbeit)** → AP-01 → AP-02 → AP-03 → AP-04 → AP-10 → AP-05 → AP-09 → AP-11 (ergänzt 2026-09-28) → AP-12 (ergänzt 2026-09-28) → AP-13 → AP-14 (beide ergänzt 2026-09-28, Auftrag `docs/konzept/gefuehrte-einheit.md`) → AP-16 (ergänzt 2026-09-29, Auftrag `docs/konzept/uebungskatalog.md`; AP-15 ist ein eigener Auftrag und davon unabhängig) → AP-15 (ergänzt 2026-09-29, Auftrag `docs/konzept/blockbilanz.md`; nach AP-16 umgesetzt).
Parallel im Projekt-Chat: AP-06 → AP-07 → AP-08. Training kann mit AP-06 bis AP-08 und Plan-als-Dokument (Übergangslösung) starten, bevor der Code fertig ist.
Hinweis zur Nummerierung: AP-10 wurde nachträglich eingefügt und steht bewusst vor AP-05, weil Migrationen und Backups produktiv sein müssen, bevor Claude über MCP schreibt. AP-01a wurde nachträglich als eigenes Vorpaket eingefügt (D-37), weil es von einem anderen Modell (Fable) bearbeitet und vom Athleten abgenommen wird und damit einen eigenen Statusblock braucht; AP-01 hängt davon ab.

Statusblock je AP (von der Code-Instanz zu pflegen):
```yaml
status: offen | in_arbeit | erledigt | blockiert
begonnen: null
abgeschlossen: null
probleme_loesungen: []
```

## AP-00 Grundgerüst und Deployment

- **Ziel:** Repo, Ordnerstruktur, Konfiguration, Deployment auf Subdomain (Lima-City), HTTPS, Datenbank.
- **Umfang:** Repo anlegen (Q-04); Struktur `server/` (public/, src/, config/, migrations/), `docs/` (konzept/, pruefung/, wissen/, regeln/, athlet/, plaene/, branding/), Changelog; `.env`-Konfiguration außerhalb Docroot; PHP-Version und Composer prüfen (V-08); Hosting-Fähigkeiten prüfen (V-10); MySQL-DB anlegen; Deploy-Workflow gemäß D-17 (GitHub Actions: Build mit `composer install --no-dev`, Übertragung von `server/` per FTPS/SFTP, Ausschlussliste für `.env`, Daten- und Backup-Verzeichnisse, anschließender Aufruf des geschützten Migrations-Endpunkts); Migrationsgrundgerüst gemäß D-20 (Ordner `server/migrations/`, Tabelle `schema_version`, `APP_SCHEMA_VERSION`, geschützter Endpunkt, Runner in Transaktionen – ohne Pre-Migration-Dump und Schreibsperre, die kommen in AP-10).
- **Abhängigkeiten:** keine.
- **Abnahmekriterien:** Push auf `main` führt zu lauffähigem Stand auf dem Server ohne manuelle Schritte; Health-Endpunkt über HTTPS erreichbar; Secrets nicht im Repo; Migrations-Endpunkt lehnt Aufrufe ohne Secret ab; `.env` überlebt ein Deployment.
- **Status:**
```yaml
status: erledigt         # Code-Stand 0.1.1, alle Abnahmekriterien erfüllt (Prüfprotokoll)
begonnen: 2026-09-27
abgeschlossen: 2026-09-27
umsetzung:
  - Endpunkte: GET /health, POST /admin/migrate (Header X-Migration-Secret), GET / (Platzhalter)
  - Konfiguration: .env im Subdomain-Ordner; Pflichtwerte APP_URL, DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, MIGRATION_SECRET
  - Deploy: .github/workflows/deploy.yml, GitHub-Environment production (Secrets FTP_USERNAME, FTP_PASSWORD, MIGRATION_SECRET; Variablen FTP_SERVER, FTP_PORT, FTP_SERVER_DIR, APP_URL); FTP-Benutzer ist auf den Subdomain-Ordner beschränkt → FTP_SERVER_DIR "/"
  - Migrationen: server/migrations/0001_schema_version.sql, App::SCHEMA_VERSION = 1
probleme_loesungen:
  - datum: 2026-09-27
    was: Hosting ist Lima-City, nicht Plesk; Recherche (Forenbeiträge 2012–2017) ließ open_basedir-Sperre oberhalb des Docroots erwarten
    loesung: Servertest (test.php) – open_basedir leer, .env oberhalb des Docroots lesbar, .htaccess wirksam; ursprüngliches Layout beibehalten, Konzept auf Lima-City umgestellt, Restrisiko als 12.1a dokumentiert
  - datum: 2026-09-27
    was: D-20 forderte Migrationen in Transaktionen; MySQL beendet Transaktionen bei DDL implizit
    loesung: D-20 präzisiert – ein Schritt pro Migrationsdatei, schema_version nach jeder Migration fortschreiben, Sperre per GET_LOCK gegen parallele Läufe
  - datum: 2026-09-27
    was: Repo hatte keinen Branch main (Deploy-Auslöser)
    loesung: main als leerer Initial-Commit angelegt, Arbeit per Pull Request
  - datum: 2026-09-27
    was: Subdomain und FTP-Ordner heißen unterschiedlich (training.gen-em.org vs. /training.jennym.org)
    loesung: bewusst so belassen; APP_URL und FTP_SERVER_DIR getrennt konfiguriert
  - datum: 2026-09-27
    was: Erster CI-Lauf rot – .gitignore-Regel *.sql (für Backups) schloss server/migrations/0001_schema_version.sql aus; lokal unbemerkt, Migrator meldete still Schemastand 0
    loesung: .gitignore auf Backup-Muster mit Ausnahme !server/migrations/*.sql umgestellt; Migrator wirft bei fehlendem Ordner einen Fehler (Test ergänzt)
  - datum: 2026-09-27
    was: Erstes Deployment auf main rot – Workflow-Schutzprüfung verbot FTP_SERVER_DIR "/"; der FTP-Benutzer ist aber auf den Subdomain-Ordner beschränkt, "/" ist dort korrekt
    loesung: Prüfung entfernt (FTP-Deploy-Action löscht nur selbst hochgeladene Dateien), README präzisiert; Version 0.1.1
  - datum: 2026-09-27
    was: Lima-City schaltet einen Proxy (openresty) vor Apache; TLS und HTTP→HTTPS-Weiterleitung erfolgen dort, inkl. eigenem HSTS-Header (includeSubDomains; preload)
    loesung: keine Änderung nötig; Weiterleitung in public/.htaccess bleibt als Rückfallebene, PHP erkennt HTTPS korrekt (HSTS gesetzt)
  - datum: 2026-09-27
    was: Apache reicht den Authorization-Header bei PHP als CGI/FPM oft nicht durch (relevant für Bearer-Token in AP-01)
    loesung: vorsorglich Weitergabe per RewriteRule in public/.htaccess, Request liest auch REDIRECT_HTTP_AUTHORIZATION
```

## AP-01a Design-Mockups (Fable, Vorarbeit)

- **Ziel:** Gestaltungsgrundlage für alle Webseiten-Screens, bevor die erste sichtbare Seite gebaut wird (D-37).
- **Umfang:** Gestaltungsvorgaben des Athleten entgegennehmen (Farben, Schrift, Tonalität, ggf. Logo – werden noch geliefert); Mockups für S0 Setup, S1 Login, S2 Woche, S3 Einheit, S4 Check-in, S5 Schmerz, S7 OAuth-Freigabe, je in Smartphone-Hochformat und Tablet-Ansicht; Zustände Fehler/gesperrt/leer, wo relevant; Ergebnis als Branding-Dokument `docs/branding/` (Vorgaben, Komponenten, Mockups, Umsetzungshinweise für die Code-Instanz) gemäß D-19.
- **Abhängigkeiten:** Gestaltungsvorgaben des Athleten liegen vor.
- **Abnahmekriterien:** Athlet hat die Mockups bestätigt; Branding-Dokument liegt im Repo; jeder Screen aus Abschnitt 10 ist abgedeckt; Tablet- und Smartphone-Ansicht vorhanden.
- **Status:**
```yaml
status: erledigt
begonnen: 2026-09-27
abgeschlossen: 2026-09-27
probleme_loesungen:
  - datum: 2026-09-27
    was: Gestaltungsvorgaben des Athleten geliefert (Chadid Design-System aus Claude Design)
    loesung: Abgelegt unter docs/branding/chadid-design-system/ (Tokens, Richtlinien, Logo, Schriften lokal, SKILL.md); Quelldateien (uploads) bewusst nicht übernommen; keine UI-Komponenten. Grundlage für die Mockups durch Fable.
  - datum: 2026-09-27
    was: Mockups erstellt (Fable) – S0, S1 (normal, Fehler, gesperrt), S2 (inkl. leer), S3 (Kraft, Ausdauer, Klettern mit Schmerz), S4 (inkl. Schmerz), S5, S6, S7, Einstellungen (inkl. Update erforderlich) als HTML unter docs/branding/mockups/, Übersicht index.html, Screenshots Smartphone/Desktop, Branding-Dokument docs/branding/branding.md
    loesung: In Rücksprache mit dem Athleten festgelegt (branding.md B-01 bis B-07) – Umfang inkl. S6 und Einstellungen; zusätzlich zu Smartphone/Tablet eine Desktop-Ansicht (Seitenleiste); App-Kennung Lama-Kopf + „Training“; nur helles Farbschema; Icons als lokales Sprite; Diagramme in einer Farbe als kleine Vielfache. Abnahme durch den Athleten am 2026-09-27; D-19 um Desktop und Abschnitt 10 um S8 Einstellungen ergänzt.
```

## AP-01 MCP-Minimalserver mit OAuth (Risikotest)

- **Ziel:** Nachweis, dass claude.ai (Web und Mobile) den PHP-MCP-Server über OAuth 2.1 erreicht, bevor Trainingslogik gebaut wird.
- **Umfang:**
  1. logiscape-SDK einbinden; `/mcp` stateless; ein Dummy-Tool `ping` (gibt Zeitstempel zurück); SDK-Sitzungsdateien nach `<Ordner>/var/`; Deploy-Ausschlussliste (AP-00-Workflow) um `var/` ergänzen (D-17).
  2. Migrationen für `user`, `web_session`, `oauth_client`, `oauth_auth_code`, `oauth_token` (D-35, Abschnitt 7).
  3. Seite `/setup` (S0) zur Erstanlage des Benutzers mit `MIGRATION_SECRET` (D-34); Login S1 mit 30-Tage-Session, CSRF-Schutz, Kontosperre nach Fehlversuchen, Abmelden (D-33).
  4. Autorisierungsserver selbst bauen (D-04, D-36): `/.well-known/oauth-authorization-server`, `/oauth/register` (offene DCR, Redirect-URI-Regeln), `/oauth/authorize` (Login + Freigabeseite S7), `/oauth/token` (PKCE S256, Codes 10 min einmalig; Refresh mit Rotation und Familien-Widerruf).
  5. Access-Token als JWT HS256 (1 h, Claims gemäß D-32) ausstellen; Bearer-Prüfung am `/mcp` über den SDK-`JwtTokenValidator`; `/.well-known/oauth-protected-resource` und 401 aus dem SDK.
  6. Statisches Fallback-Token `MCP_STATIC_TOKEN`, nur bei `MCP_STATIC_TOKEN_ENABLED=true` (D-06).
  7. Neue `.env`-Schlüssel dokumentieren (README, `.env.example`): `OAUTH_JWT_SECRET` (Pflicht, ≥ 32 Zeichen; Start verweigert, wenn kürzer oder fehlend), `MCP_STATIC_TOKEN` (optional), `MCP_STATIC_TOKEN_ENABLED` (optional, Standard aus).
  8. Gestaltung von S0, S1, S7 nach `docs/branding/` (AP-01a); Custom Connector in claude.ai einrichten (V-05); Fallback über Claude Desktop testen.
- **Abhängigkeiten:** AP-00; AP-01a (Branding-Dokument abgenommen).
- **Abnahmekriterien:** `/setup` legt genau einen Benutzer an und ist danach gesperrt (404); Login funktioniert, Session überlebt einen Browser-Neustart; zehn Fehlversuche sperren 5 Minuten, weitere Fehlversuche verdoppeln die Sperre (Test mit verkürzter Zeitbasis), erfolgreicher Login setzt den Zähler zurück; `ping` aus dem Projekt-Chat (Web) und aus der Mobile-App aufrufbar, jeweils nach Freigabe auf S7; Token-Refresh nach Ablauf funktioniert, Wiederverwendung eines rotierten Refresh-Tokens widerruft die Familie (Test); Anfrage ohne Token → 401 mit korrekten Metadaten; abgelaufenes oder fremd signiertes JWT → 401; Redirect-URI mit `http://` außer localhost wird bei Registrierung abgelehnt; PKCE `plain` wird abgelehnt; Fallback über Desktop funktioniert nur bei gesetztem Flag; S0/S1/S7 auf Smartphone und Tablet nutzbar; `.env` und `var/` überleben ein Deployment.
- **Status:**
```yaml
status: in_arbeit         # Code-Stand 0.2.0 fertig, automatisierte Tests grün; Abnahme auf dem Server und mit claude.ai offen (Prüfprotokoll)
begonnen: 2026-09-27
abgeschlossen: null
umsetzung:
  - Endpunkte: /setup (S0), /login (S1), /logout, / (Startseite), /.well-known/oauth-authorization-server[/mcp], /.well-known/oauth-protected-resource[/mcp], /oauth/register, /oauth/authorize (S7), /oauth/token, /mcp (Tool ping)
  - Migrationen 0002–0006 (user, web_session, oauth_client, oauth_auth_code, oauth_token), App::SCHEMA_VERSION = 6
  - .env: OAUTH_JWT_SECRET (Pflicht, ≥ 32 Zeichen), MCP_STATIC_TOKEN, MCP_STATIC_TOKEN_ENABLED (Standard aus)
  - Scopes training:read, training:write (Bezeichnungen aus Mockup S7); ohne Angabe beide
  - Laufzeiten: Access-Token 1 h, Code 10 min, Refresh-Token 90 Tage je Rotation (D-38), Web-Session 30 Tage gleitend (Verlängerung höchstens stündlich)
  - Deploy: var/** und bin/** vom Upload ausgeschlossen; Assets aus docs/branding/ per server/bin/build-assets.php in CI gebaut
  - Tests: 68 (Unit + Integration gegen MariaDB 10.11 lokal, MySQL 8.4 in CI); zusätzlich lokal SDK-Client in beiden Protokoll-Epochen (2026-07-28 zustandslos, 2025-11-25 mit Handshake) und Browser-Durchlauf S0/S1/S7 in 390/834/1280 px
probleme_loesungen:
  - datum: 2026-09-27
    was: Vorbereitung – logiscape/mcp-sdk-php v2.0.1 geprüft. Das SDK enthält nur die Resource-Server-Seite von OAuth 2.1 (TokenValidatorInterface mit JwtTokenValidator HS256/RS256 inkl. iss/aud/exp-Prüfung, /.well-known/oauth-protected-resource, 401 mit WWW-Authenticate resource_metadata), aber keinen Autorisierungsserver (RFC-8414-Metadaten, DCR, Authorize, Token). Für Clients älterer Protokollrevisionen legt es Sitzungsdateien an.
    loesung: D-04 präzisiert, Autorisierungsserver wird in AP-01 selbst gebaut (D-32, D-36); Access-Token als JWT HS256, damit der SDK-Validator direkt genutzt werden kann; Sitzungsdateien nach var/ außerhalb Docroot (D-17); Auth-Tabellen aus AP-03 vorgezogen (D-35); Login und Erstanlage festgelegt (D-33, D-34)
  - datum: 2026-09-27
    was: McpServer::runHttp() schreibt Header und Body direkt (SAPI) und liest die Globals; passt nicht zum eigenen Router und ist so nicht testbar
    loesung: HttpServerRunner des SDK direkt mit BufferedIo betreiben (McpEndpoint), Request → HttpMessage → Response übersetzen; Host und Schema für die resource_metadata-URL aus APP_URL statt aus Proxy-Headern
  - datum: 2026-09-27
    was: Das SDK legt für Clients älterer Protokollrevisionen eine Sitzungsdatei an, bevor es das Token prüft – unauthentifizierte Anfragen hätten var/ füllen können
    loesung: Token vorab mit demselben Validator prüfen; ohne gültiges Token nutzt das SDK nur einen flüchtigen Speicher (die 401-Antwort erzeugt weiterhin das SDK), Mcp-Session-Id wird dann nicht ausgegeben; Sitzungsdateien älter als ein Tag werden gelegentlich gelöscht (Test)
  - datum: 2026-09-27
    was: D-32 legt keine Laufzeit für Refresh-Tokens fest
    loesung: 90 Tage, jede Rotation beginnt neu (bei regelmäßiger Nutzung kein erneuter Login); als Q-09 vorgelegt, vom Athleten bestätigt → D-38 (2026-09-28)
  - datum: 2026-09-27
    was: Scope-Namen waren im Konzept nicht festgelegt; Clients fordern teils eigene Scopes an
    loesung: training:read und training:write aus Mockup S7 übernommen; unbekannte Scopes werden ignoriert statt abgelehnt, ohne bekannte Angabe werden beide vergeben (verhindert Abbruch bei Clients mit Standardwerten); geprüft wird am /mcp derzeit nur aud/iss/exp, eine Scope-Prüfung je Tool folgt mit AP-05
  - datum: 2026-09-27
    was: Clients können bei der Registrierung eine andere Client-Authentifizierung als "none" wünschen
    loesung: Alle Clients sind öffentlich; die Registrierungsantwort meldet immer token_endpoint_auth_method "none" (RFC 7591 erlaubt die Abweichung), ein trotzdem gesendetes Secret wird ignoriert; Schutz über PKCE S256
  - datum: 2026-09-27
    was: Fehlversuche mit falschem Anmeldenamen – zählen oder nicht?
    loesung: zählen gegen den einzigen Benutzer (Einzelnutzer, keine Unterscheidung nach außen sichtbar); während einer Sperre wird weder geprüft noch gezählt, damit die Sperre nicht durch weitere Versuche verlängert wird
  - datum: 2026-09-27
    was: Branding-Hinweis 7.1 (Assets nach public/assets übernehmen) – Kopie im Repo hätte Schriften und Icons doppelt gehalten
    loesung: Build-Schritt server/bin/build-assets.php in CI und Deploy; public/assets/ ist nicht im Repo, einzige Quelle bleibt docs/branding/
  - datum: 2026-09-27
    was: Content-Security-Policy ohne Inline-Styles/-Skripte vs. Inline-Styles und icons.js in den Mockups
    loesung: Ergänzungsklassen in server/public/css/training.css; Icons serverseitig inline aus public/assets/icons (kein JavaScript nötig); Abweichungen im Branding-Dokument Abschnitt 8 nachgetragen
  - datum: 2026-09-27
    was: POST /mcp mit ungültigem JSON-Körper antwortet ohne Token mit 400 statt 401 (Reihenfolge im SDK)
    loesung: hingenommen – kein Datenabfluss, gültige JSON-RPC-Anfragen ohne Token erhalten 401 mit Metadaten (Test)
```

## AP-02 Intervals.icu-Anbindung

- **Ziel:** Serverseitiger Client für Events, Aktivitäten, Wellness; Garmin-Verknüpfung produktiv.
- **Umfang:** Intervals.icu-Konto: Garmin-Verknüpfung und Wellness-Sync aktivieren, Privatsphäre (Q-03); API-Client (Auth, Fehlerbehandlung, optionaler Kurzcache); Verifikationen V-01, V-02, V-03, V-04, V-09, V-12 durchführen und Ergebnisse in Abschnitt 5.2 eintragen; Test-Event mit Workout-Syntax schreiben und auf der Uhr prüfen.
- **Abhängigkeiten:** AP-00.
- **Abnahmekriterien:** Test-Event erscheint auf der Uhr mit korrekten Zielen; Aktivitäten und Wellness der letzten 7 Tage per Client abrufbar; Event löschen/ändern wird auf der Uhr nachgezogen.
- **Status:**
```yaml
status: in_arbeit         # Code-Stand 0.3.0: Client und Verbindungstest fertig, Tests mit simulierter API grün; Prüfung gegen die echte API, Kontoeinstellungen und Uhr offen
begonnen: 2026-09-27
abgeschlossen: null
umsetzung:
  - Client server/src/Intervals/IntervalsClient.php – Basis https://intervals.icu/api/v1, Basic-Auth API_KEY:<key>; GET /athlete/{id}, GET/POST /athlete/{id}/events, PUT/DELETE /athlete/{id}/events/{eventId}, GET /athlete/{id}/activities, GET /athlete/{id}/wellness (Query oldest/newest YYYY-MM-DD)
  - eine Wiederholung bei 429/5xx; Fehlermeldungen ohne Key; kein Kurzcache (ext_cache optional in AP-03, bei Bedarf)
  - Seite /intervals (nach Login) als Werkzeug für die Abnahme, da auf dem Hosting keine PHP-CLI verfügbar ist (V-10); wandert mit AP-04 nach S8 „Verbindungen“
  - .env INTERVALS_API_KEY, INTERVALS_ATHLETE_ID optional; /health meldet nur den Konfigurationsstand
probleme_loesungen:
  - datum: 2026-09-27
    was: Die Code-Umgebung erreicht intervals.icu nicht (Netzwerkrichtlinie), weder API noch Dokumentation/Forum; V-01, V-03, V-04 lassen sich hier nicht am Original prüfen
    loesung: Endpunkte und Auth aus dem Quellcode eines öffentlichen Intervals.icu-Clients (github.com/mvilanova/intervals-mcp-server) abgeleitet und in V-04 als vorläufig markiert; Client gegen simulierte Antworten getestet; Bestätigung erfolgt über /intervals auf dem Server (Athlet, Aktivitäten, Wellness, Test-Event)
  - datum: 2026-09-27
    was: Workout-Textsyntax (V-01) nicht am Original prüfbar
    loesung: Test-Event nutzt die bekannte Form „- 10m Z1 HR“, Wiederholungsblock „Hauptteil 3x“, ein Zieltyp pro Schritt; ob Schritte und HF-Zonen korrekt auf der Uhr ankommen, prüft der Athlet (Abnahmekriterium)
  - datum: 2026-09-27
    was: Pflicht oder optional für die Intervals-Schlüssel in .env?
    loesung: optional – fehlende Schlüssel dürfen Deployment und Health nicht blockieren, solange die Anbindung noch nicht genutzt wird; /health zeigt den Stand, /intervals erklärt die Einrichtung
```

## AP-03 Datenmodell

- **Ziel:** MySQL-Schema der Trainingsdaten gemäß Abschnitt 7, Migrationen, Enum-Seeds.
- **Umfang:** Migrationen für die Trainingstabellen `training_block`, `training_week`, `session`, `session_execution`, `pain_event`, `checkin`, `audit_log`, optional `ext_cache` (Benutzer-, Session- und OAuth-Tabellen sind bereits in AP-01 angelegt, D-35); JSON-Schemata für `plan_json`/`actual_json` als Validierungsgrundlage; ER-Diagramm aller Tabellen (inkl. der aus AP-01) in `docs/`.
- **Abhängigkeiten:** AP-00, AP-01 (Migrationsreihenfolge). Q-05 ist entschieden (D-33).
- **Abnahmekriterien:** Migrationen idempotent; Beispiel-Woche mit allen Session-Typen einfügbar; JSON-Validierung lehnt fehlerhafte Pläne ab.
- **Status:**
```yaml
status: in_arbeit         # Code-Stand 0.4.0, alle Abnahmekriterien lokal automatisiert erfüllt (MariaDB 10.11); offen: CI gegen MySQL 8.4, Migration auf dem Server
begonnen: 2026-09-27
abgeschlossen: null
umsetzung:
  - Migrationen 0007–0014, App::SCHEMA_VERSION = 14; ER-Diagramm docs/konzept/datenmodell.md
  - Schemata server/schemas/plan-*.json, actual-*.json; Validator Training\Plan\PlanValidator (opis/json-schema)
  - Beispielwoche server/tests/fixtures/beispielwoche.json (alle sechs Typen)
probleme_loesungen:
  - datum: 2026-09-27
    was: „Enum-Seeds“ im Ziel nicht näher bestimmt (eigene Wertetabellen oder Aufzählungen im Schema?)
    loesung: Aufzählungen als ENUM-Spalten mit den Werten aus Abschnitt 7 und 7.2; keine Seed-Tabellen nötig. Neue Werte brauchen eine Migration (bewusst: Claude und Webseite sollen nur bekannte Werte schreiben)
  - datum: 2026-09-27
    was: Ohne strikten SQL-Modus speichert MySQL ungültige ENUM-Werte als Leerstring und schneidet Texte still ab; Voreinstellung bei Lima-City unbekannt
    loesung: Verbindung setzt sql_mode STRICT_ALL_TABLES u. a. und time_zone +00:00 selbst (Database::connect); Tests prüfen die Ablehnung
  - datum: 2026-09-27
    was: Abschnitt 7.1 definiert kein plan_json für mobilitaet und ruhe
    loesung: mobilitaet = Schema kraft_oder_haltung, ruhe = leer oder nur notes; als Q-10 vorgelegt, vom Athleten bestätigt → D-39 (2026-09-28)
  - datum: 2026-09-27
    was: srpe_load „berechnet, nie manuell“ (Abschnitt 11)
    loesung: berechnete Spalte (STORED) rpe_cr10 × duration_min; Schreiben wird von der Datenbank abgewiesen (Test)
  - datum: 2026-09-27
    was: Löschverhalten nicht festgelegt
    loesung: Woche → Einheiten → Durchführung kaskadierend (für replace_existing in write_week_plan, AP-05); Schmerzereignisse bleiben mit session_id NULL erhalten (Schmerzverlauf darf nicht verloren gehen); Block mit Wochen nicht löschbar
  - datum: 2026-09-27
    was: Ergänzungen gegenüber Abschnitt 7
    loesung: created_at/updated_at je Tabelle, optionales notes auf oberster Ebene je plan_json, Plausibilitätsgrenzen in den Schemata (keine Trainingsregeln); in datenmodell.md dokumentiert
```

## AP-04 Webseite

- **Ziel:** Screens S2–S5 gemäß Abschnitt 10, mobil und auf dem Tablet nutzbar (S0, S1, S7 stammen aus AP-01).
- **Umfang:** Gestaltung nach `docs/branding/` (D-19, AP-01a); Login aus AP-01 (D-33) einbinden; Wochenansicht inkl. Ausdauereinheiten aus Intervals.icu; Einheit mit Ist-Eingabe und Feedback; Check-in; Schmerz-Kurzformular; Bereich „Einstellungen" nach Login (Platzhalter für Backup/Update aus AP-10); Web-App-Manifest.
- **Abhängigkeiten:** AP-01, AP-02, AP-03; Branding-Dokument im Repo vorhanden (AP-01a).
- **Abnahmekriterien:** Auf dem Smartphone und auf dem Tablet: Woche sehen, Krafteinheit mit Ist-Werten abschließen, Feedback und Schmerzereignis erfassen, Check-in in ≤ 10 s; alles in DB nachvollziehbar; Ausdauereinheit der Woche mit verknüpfter Aktivität sichtbar.
- **Status:**
```yaml
status: in_arbeit         # Code-Stand 0.5.0: alle Screens umgesetzt, automatisierte Tests und Browser-Durchlauf grün; Abnahme auf Smartphone/Tablet durch den Athleten und mit echten Intervals.icu-Daten offen
begonnen: 2026-09-28
abgeschlossen: null
umsetzung:
  - Seiten /woche (S2), /einheit (S3), /checkin (S4), /schmerz (S5), /einstellungen (S8), /verlauf (Platzhalter AP-09); / leitet nach Login auf /woche
  - Datenzugriff server/src/Data/ (WeekRepository, FeedbackRepository, AuditLog); alle Schreibzugriffe der Webseite im audit_log (actor web, payload_hash)
  - Intervals.icu-Aktivitäten mit 5-Minuten-Cache (ext_cache) und Zuordnung zu Ausdauereinheiten
  - Web-App-Manifest mit Icons (192, 512, maskierbar)
probleme_loesungen:
  - datum: 2026-09-28
    was: Mockup S3 hat kein Feld für die Dauer; sRPE = RPE × Dauer braucht sie (Abschnitt 11)
    loesung: Feld „Dauer (min)“ im Rückmeldungsblock, vorbelegt mit gespeicherter Dauer, sonst Dauer der verknüpften Aktivität, sonst geplanter Dauer; Pflicht bei erledigt/teilweise
  - datum: 2026-09-28
    was: RPE, Gefühl und Dauer sind bei „ausgelassen“/„verschoben“ sinnlos
    loesung: nur bei erledigt/teilweise Pflicht und gespeichert; Abweichungsgrund und Notiz werden immer gespeichert
  - datum: 2026-09-28
    was: performed_at ist aus der Webseite nicht genau bekannt
    loesung: bei Eingabe am selben Tag Zeitpunkt der Eingabe, sonst Tag der Einheit 12:00 (Näherung); genaue Zeit liefert bei Ausdauer die Intervals.icu-Aktivität
  - datum: 2026-09-28
    was: actual_json „leere Felder = wie geplant“ – vollständige oder nur abweichende Werte speichern?
    loesung: die angezeigten Ist-Werte werden vollständig gespeichert (eindeutig, ohne Vergleichslogik); das Schema erlaubt 0 Sätze für nicht gemachte Übungen
  - datum: 2026-09-28
    was: Zuordnung Aktivität ↔ geplante Ausdauereinheit; Feldnamen der Intervals.icu-Aktivität (paired_event_id, icu_hr_zone_times, average_speed) nicht am Original prüfbar (V-04)
    loesung: zuerst über paired_event_id = intervals_event_id, sonst erste Ausdauer-Aktivität am selben Tag; fehlende Felder blenden die jeweilige Kennzahl aus; Prüfung mit echten Daten im Prüfprotokoll
  - datum: 2026-09-28
    was: Hinweistext nach dem Speichern eines Schmerzereignisses (Mockup S5) braucht eine Auslöseregel; Schmerzregeln entstehen erst in AP-07
    loesung: vorläufige Anzeigeregel ohne Trainingswirkung – ab der dritten Meldung am selben Ort in 14 Tagen, bei Stärke über 5 oder Schmerz in Ruhe; wird mit AP-07 an die Schmerzregeln angeglichen
  - datum: 2026-09-28
    was: Schmerz-Kurzform soll ohne JavaScript funktionieren (Branding 7.3); Content-Security-Policy verbietet Inline-Skripte und Inline-Styles (Zonenbalken im Mockup per style-Attribut)
    loesung: Aufklappen per CSS :has() (Browser ohne :has() zeigen die Kurzform immer); Zonenbalken als SVG mit Breiten-Attributen und Farbklassen
  - datum: 2026-09-28
    was: Einstellungen verlangen Backup, Update und „Verlauf“ – Funktionen aus AP-09/AP-10
    loesung: Anzeige des Schemastands und der Version jetzt; Backup und Migrationsknopf als deaktivierte Platzhalter; Navigationspunkt Verlauf mit Platzhalterseite
  - datum: 2026-09-28
    was: Widerruf einer Claude-Freigabe (S8)
    loesung: setzt alle Refresh-Tokens des Clients auf revoked; laufende Access-Tokens enden nach ≤ 1 h (D-32); die Client-Registrierung bleibt und wird nach 30 Tagen ohne Nutzung aufgeräumt
  - datum: 2026-09-28
    was: CI (MySQL 8.4) rot, lokal (MariaDB) grün – MySQL speichert JSON-Objekte mit sortierten Schlüsseln, ein Test verglich die Reihenfolge
    loesung: Test vergleicht ohne Reihenfolge; fachlich ohne Folgen (Zugriff immer über Schlüssel)
```

## AP-05 MCP-Tools produktiv

- **Ziel:** Tools gemäß Abschnitt 8.2 inkl. Schreibpfad und Audit-Log.
- **Umfang:** Lese-Tools mit Aggregation und Antwortbudget (8.3); `write_week_plan` (DB + Intervals.icu-Events, Fehlerbericht je Session, `replace_existing`); `update_session`; `get_athlete_profile`; Audit-Log.
- **Abhängigkeiten:** AP-01, AP-02, AP-03.
- **Abnahmekriterien:** Aus dem Projekt-Chat: Wochenübersicht abrufen (≤ 2 000 Tokens); Wochenplan schreiben → Einheiten in DB, Ausdauer-Events auf der Uhr; Audit-Log vollständig; Fehler in Intervals.icu brechen den DB-Schreibvorgang nicht unbemerkt ab.
- **Status:**
```yaml
status: in_arbeit         # Code-Stand 0.7.0, Tests über /mcp grün (simulierte Intervals-API), SDK-Client beide Protokoll-Epochen ok; offen: Projekt-Chat mit echten Daten, Events auf der Uhr
begonnen: 2026-09-28
abgeschlossen: null
umsetzung:
  - server/src/Mcp/ReadTools.php, WriteTools.php, ToolRegistry.php; Registrierung in McpEndpoint
  - Scope je Tool (training:read / training:write) aus dem geprüften Token; Schreibsperre D-20 für Schreib-Tools
  - write_week_plan – Prüfung aller Einheiten vor dem Schreiben (alles oder nichts), DB-Transaktion, danach Intervals-Events (external_id training-session-<id>); Fehler je Einheit, Einheit bleibt ohne Event-ID, erneuter Sync mit update_session
  - Audit-Log – block_create/update, week_plan_write, session_update, intervals_event_create/update/delete, intervals_error (actor mcp)
probleme_loesungen:
  - datum: 2026-09-28
    was: Kein Tool legt einen Trainingsblock an; write_week_plan braucht aber einen Block (training_week.block_id)
    loesung: Tool upsert_block ergänzt (Anlegen/Ändern, „aktiv“ schließt andere aktive Blöcke ab); als Q-11 vorgelegt, vom Athleten bestätigt → D-40 (2026-09-28)
  - datum: 2026-09-28
    was: Das Intervals.icu-Event braucht eine Sportart; plan_json.ausdauer (7.1) hat keine
    loesung: optionales Feld sport (Run, TrailRun, Hike, Walk, Ride, …, BackcountrySki, NordicSki; Standard Run) im Schema; als Q-12 vorgelegt, vom Athleten bestätigt → D-41 (2026-09-28)
  - datum: 2026-09-28
    was: replace_existing würde Einheiten mit Rückmeldung löschen (Datenverlust)
    loesung: ersetzt werden nur Einheiten mit Status geplant und ohne Durchführung; alle anderen bleiben und werden in „behalten“ gemeldet; Events ersetzter Einheiten werden in Intervals.icu gelöscht
  - datum: 2026-09-28
    was: Reihenfolge DB ↔ Intervals.icu bei Teilfehlern
    loesung: erst DB-Transaktion (vollständig oder gar nicht), dann Events; Intervals-Fehler landen je Einheit in der Antwort (status teilweise) und im audit_log; update_session legt fehlende Events nachträglich an
  - datum: 2026-09-28
    was: Status „ausgelassen“ bzw. Verschieben einer Ausdauereinheit
    loesung: ausgelassen löscht das Event (verschwindet von der Uhr), Datumsänderung aktualisiert es; Verschieben in eine Woche ohne Plan wird abgelehnt
  - datum: 2026-09-28
    was: Antwortbudget (8.3)
    loesung: kompakte deutsche Schlüssel, Aktivitäten zusammengefasst (Zonen in Minuten), Schmerzverlauf als „MM-TT:Stärke:Zeitpunkt“, Listen begrenzt; Beispielwoche ≈ 2 000 Zeichen (Test < 8 000 Zeichen ≈ 2 000 Tokens)
  - datum: 2026-09-28
    was: Feldnamen der Intervals-Wellness (ctl, atl, hrv, restingHR, sleepSecs, sleepScore) und Aktivität (icu_training_load, total_elevation_gain) nicht am Original prüfbar (V-04)
    loesung: fehlende Felder werden weggelassen statt Fehler; Prüfung mit echten Daten im Prüfprotokoll
```

## AP-06 Wissensbasis (Projekt-Chat / eigene Literatur-Sitzungen)

- **Ziel:** Literaturauswahl je Block, Beschaffung, Wissenskarten gemäß 13.1.
- **Umfang:**
  1. Literaturblöcke: übergreifend (bestätigt), T1 Ausdauer (bestätigt), T2 Kraft/Calisthenics (bestätigt), Haltung/Rücken (bestätigt, D-54) und Hypertrophie-Ergänzung (bestätigt, D-62), T3 Klettern/Bouldern (bestätigt; E3–E6 → D-31), R Reha/Prävention (bestätigt, D-61: Patellasehne, Sprunggelenk, Laufumfang), T4 Beweglichkeit/Mobilität (Teil A bestätigt, D-79; Teil B mit L-T4-34 abgeschlossen). Literatursuche abgeschlossen am 2026-09-29 (Entscheidung Athlet). Regel für weitere Sitzungen: aktuelle Konzeptfassung laden, Übergabedokument liefern, Konzept nicht direkt editieren.
  2. Beschaffung nach 13.4 (Athlet, D-26); Formatprüfung je Titel (V-13); EPUB ohne DRM zulässig (D-71).
  3. Kartenzuschnitt (Bündelungsregel 13.1, Zielzahl 7 Dateien seit D-79):
     - `docs/wissen/uebergreifend-belastung-monitoring-erholung.md` ← L-P03, L-P04, L-P05, L-P06, L-A01, L-A02, L-P10, L-P11, L-P12, L-P13; L-P15, optional L-P16 (HRV-gesteuerte Steuerung, D-70)
     - `docs/wissen/uebergreifend-planung-kombiniertes-training.md` ← L-P01, L-P02, L-P07, L-P08, L-P09, L-A01, L-A02; L-T2-25, L-T2-26 (Interferenz auf Faserebene, D-62 d), optional L-T2-31, L-T2-32; Widerspruch zur Modalität (Laufen vs. Rad) unter „Grenzen/Widersprüche“
     - `docs/wissen/t1-ausdauer.md` ← L-T1-02 bis L-T1-08 und L-A02 über L-T1-15 (Kapitelangaben dort) (Karten: Intensitätsverteilung und Zonenmodell D-27; Bergauf-Ausdauer und Skitour-Spezifik; Intervallprogrammierung); optionale Quellen nur bei konkreter Planungsfrage, darunter L-T1-16 (Stufe C, nur mit Kennzeichnung; Trail-Spezifik, D-80); Budget ca. 8 000–10 000 Tokens
     - `docs/wissen/t2-kraft-haltung.md` ← L-P08, L-A03, L-T2-03 (Karten: Dosierung und Progression; kombiniertes Training Kraft/Ausdauer), L-T2-11, L-T2-12 (Kraft für Läufer), Abschnitt `uebungskatalog_calisthenics` aus L-T2-04 mit Belegen L-T2-08 bis L-T2-10 (D-29), Karte Haltung und Rücken aus L-T2-15 bis L-T2-18 (D-54); optional L-T2-14, L-T2-19, L-T2-33 (Biomechanik, Übungsauswahl; Dosierung aus L-T2-15 bis L-T2-18, D-80); Abschnitt Hypertrophie (Ergänzung, D-62) aus L-T2-20 bis L-T2-24, optional L-T2-27 bis L-T2-30; Pflichtinhalt „Grenzen“ gemäß D-62 (e); Abschnitt knapp halten, Gesamtbudget 13.1 prüfen
     - `docs/wissen/t3-klettern.md` ← L-T3-01, -02, -03, -06 (bzw. -07), -19, -09 (3. Aufl., Neuauflage nach Erscheinen); Stufe C L-T3-10 und L-T3-20 (Ideenfundus), L-T3-21 (Verletzungsmanagement, vorläufig), L-T3-16 und L-T3-15 (Planungsvorlagen Periodisierung) nur mit Kennzeichnung (D-31) (Karten: kletterspezifisches Krafttraining und Spezifitätsschema; Leistungsdiagnostik und Verlaufstests; Verletzungsprävention/Schmerz); L-T3-18 als Beleg für Hangboard-Protokolle, L-T3-05 mit konfidenz niedrig; Stufe-C-Quellen nur als Ideenfundus (D-31); Kennzeichnung „Evidenz: begrenzt"
     - `docs/wissen/r-reha-praevention.md` ← L-R-01 bis L-R-09, L-R-13 bis L-R-17, L-R-23 bis L-R-25 (Kern); optional L-R-10 bis L-R-12, L-R-18 bis L-R-22, L-R-26 bis L-R-28, L-R-29 und L-R-30 (Nachschlagewerke, nicht für Regeln, D-80); Pflichtabschnitt „Grenzen“ gemäß D-61 (e)
     - `docs/wissen/t4-beweglichkeit.md` ← L-T4-01 bis -06, -08, -10, -12, -14, -16, -17, -19 und Lehrbuch L-T4-32 (Kern), Verweise L-R-11 und L-T2-15; optional L-T4-07, -09, -11, -13, -15, -18, -20, -21, -23, -25 bis -31 und L-T4-36 (Stufe C, Faszienrolle-Hintergrund) (D-80); Übungskatalog L-T4-34 und weitere Übungsquellen aus Teil B nur als Ideenfundus (D-31); Pflichtabschnitt „Grenzen“: Dosis widersprüchlich, Hüftevidenz dünn (Einschätzung), kein Präventionseffekt, Wohlbefinden schwach belegt (L-T4-28)
  4. Offene Punkte: V-07 Rest (Schwellen am Volltext L-P13, Entscheidung Q-13 in AP-07); V-15 Rest (L-T3-02 Wiederholungsbereiche, L-T3-12); V-17 (Seitenbezug L-T3-19, L-T3-10); Lizenz L-P15 vor Ablage prüfen; Bestätigung L-T3-21 durch den Athleten; V-18 bis V-23; Beschaffung L-T3-15; L-T3-17 bleibt Platzhalter (PubMed bei konkretem Bedarf); Skimo-Reviews ab 2021 bei Bedarf per PubMed (nicht beauftragt); Lizenz L-T2-15, L-T2-16 vor Ablage prüfen; Karten-Template (Schema: Kernaussage + Quelle + Seite + Stufe + konfidenz + Themenfeld); V-16 (Redundanz Hypertrophie-Ergänzung zu L-P08, Corrigendum L-T2-23); Lizenz L-T2-22, L-T2-23, L-T2-25 vor Ablage prüfen; Kartenerstellung nach Beschaffung.
- **Abhängigkeiten:** keine (Chat-Arbeit); Kartenerstellung erst nach Beschaffung.
- **Abnahmekriterien:** Karten liegen in `docs/wissen/` und im Projekt-Wissen; jede Kernaussage hat Quelle mit Seite (EPUB ohne Seitenliste: Kapitel/Abschnitt, D-71) bzw. DOI/PMID und Evidenzstufe; V-06, V-07 (Literaturteil), V-14, V-15 erledigt; Gesamtbudget 13.1 eingehalten.
- **Status:**
```yaml
status: in_arbeit
begonnen: 2026-09-27
abgeschlossen: null
teilschritte:
  - Literaturauswahl übergreifend: erledigt (D-21–D-24)
  - Literaturauswahl T1 Ausdauer: erledigt (D-25–D-27, V-11)
  - Literaturauswahl T2 Kraft/Calisthenics: erledigt (D-28–D-30)
  - Literaturauswahl T2 Haltung/Rücken: erledigt (D-54)
  - Literaturauswahl T3 Klettern/Bouldern: erledigt (D-31)
  - Literaturauswahl Block R Reha/Prävention: erledigt (D-61)
  - Literaturauswahl T2 Hypertrophie-Ergänzung: erledigt (D-62)
  - Literaturauswahl T4 Beweglichkeit/Mobilität Teil A: erledigt (D-79), Teil B Übungsquellen erledigt (L-T4-34)
  - Lückenprüfung Standardwerke (en/de): erledigt (D-80)
  - Literatursuche insgesamt: abgeschlossen 2026-09-29 (Entscheidung Athlet; Q-22 entschieden, L-T3-15 aufgenommen, L-T4-35 entfallen)
  - Beschaffung und Formatprüfung: weitgehend erledigt (Stand 2026-09-29 – 116 Werke, Kapitel-PDFs für 15 Bücher, Kapitel-Markdown mit Ansichts-PDF für 5 EPUBs (L-T1-16, L-T3-10, -19, -20, -21), D-51, D-71; vorhandene Ausgaben gelten; offen nach 13.4 sind L-R-29 und L-T4-35)
  - Literatur-Nachsteuerung L-T1-01/L-T3-08, HRV, EPUB: erledigt (D-70, D-71)
  - Primärquellen verifizieren: weitgehend erledigt (V-06, V-14 erledigt; V-07, V-15 teilweise, Rest nach Beschaffung)
  - Karten-Template und Karten: offen
probleme_loesungen:
  - datum: 2026-09-27
    was: Drei Literatur-Sitzungen (übergreifend, T1, T2) arbeiteten parallel vom selben Konzeptstand; alle vergaben D-21 ff. und V-11 ff.; T2 editierte das Konzept direkt
    loesung: Zusammenführung durch die planende Instanz mit Renummerierung (T1 → D-25–D-27, V-11–V-12; T2 → D-28–D-30, V-13–V-14; T3 → Q-07, Q-08, V-15). Regel in 13.2 und Umfang 1 festgeschrieben
  - datum: 2026-09-27
    was: Block übergreifend – PubMed-Verifikation L-P01 bis L-P08
    ergebnis: ok; Korrekturen – L-P05 DOI ergänzt; L-P06 Zitierfassung festgelegt (MSSE 2013;45(1):186-205), widersprüchliche Seitenangaben stammten aus fehlerhaften Sekundärzitaten; L-P07 Heftnummer ergänzt (52(3)); L-P08 DOI ergänzt, Open Access
  - datum: 2026-09-27
    was: Held et al. 2026 (zuvor nur Sekundärquelle)
    ergebnis: per PubMed bestätigt, als L-P09 aufgenommen
  - datum: 2026-09-27
    was: L-A02 2. Auflage
    ergebnis: Auflage belegt, Jahr/ISBN nicht auffindbar → offen
  - datum: 2026-09-27
    was: Block T1 – Autorenliste Casado et al. 2022 zunächst aus dem Gedächtnis falsch angegeben
    loesung: per Websuche und PubMed korrigiert; bestätigt Zitierregel D-13 und Pflicht zur bibliografischen Prüfung (V-11)
  - datum: 2026-09-27
    was: Block T1 – Haugen et al. 2022 zunächst mit Artikelnummer 18 (Repositoriumsangabe)
    loesung: laut PubMed Artikelnummer 46; korrigiert
  - datum: 2026-09-27
    was: Block T1 – Aussage „alle Reviews außer Vernillo sind Open Access" war falsch
    loesung: Seiler 2010, Vernillo 2017, Giandolini 2016 nicht in PMC; Casado 2022 unklar; Zugang je Quelle ausgewiesen
  - datum: 2026-09-27
    was: Block T1 – Tønnessen 2024 und Sandbakk 2025 zunächst als Reviews eingeordnet
    loesung: laut PubMed qualitative Studie bzw. Multiple-Case-Study mit Trainern → Praxisquelle, konfidenz mittel (D-25)
  - datum: 2026-09-27
    was: Blöcke T1 und T2 – Human-Kinetics-E-Books nur mit DRM (VitalSource), unvereinbar mit PDF-Kapitelprozess 13.1
    loesung: Formatanforderung D-26, Formatprüfung je Titel V-13; Springer-Titel bevorzugt digital beschaffbar
  - datum: 2026-09-27
    was: Block T2 – kein wissenschaftliches Standardwerk für Calisthenics; Progressionsreihenfolgen nicht studienbasiert
    loesung: Übungskatalog aus L-T2-04 mit konfidenz niedrig, Dosierung ausschließlich aus Kernset (D-29)
  - datum: 2026-09-27
    was: Blöcke übergreifend und T1 – Kenney/Wilmore/Costill mit unterschiedlicher Auflage gemeldet (8. 2022 vs. 9. 2024)
    loesung: L-A01 bleibt Referenz; Auflage beim Erwerb klären (13.4)
  - datum: 2026-09-27
    was: Block T3 – abweichendes Evidenzschema (A/B/C) und strengere Regel für Praxisquellen (E4) gegenüber D-25/D-29
    loesung: Vereinheitlicht als D-31 (bestätigt); Feld `stufe` in 13.2 eingeführt
  - datum: 2026-09-28
    was: 29 PDFs unsortiert in docs/literatur/ mit Verlags- und Archivdateinamen (z. B. s40279-017-0823-y.pdf, „… Anna’s Archive.pdf“)
    loesung: jede Datei am Inhalt (Titel, Autoren, DOI) identifiziert und einer ID aus 13.2 zugeordnet; alle DOIs stimmen mit 13.2 überein; nach D-51 umbenannt und in Blockordner sortiert; Felder `datei`/`kapitel` und Spalte „vorhanden“ in 13.4 ergänzt
  - datum: 2026-09-28
    was: Kenney/Wilmore/Costill liegt in der 7. Aufl. (2019) vor, ausgewählt ist die 8. (2022)
    loesung: Athlet entscheidet – 7. Aufl. vorläufig, 8./9. Aufl. bleibt auf der Beschaffungsliste (D-51)
  - datum: 2026-09-28
    was: Bücher bis 1876 Seiten und 65 MB, für Chat-Sitzungen zu groß (13.1 Schritt 1)
    loesung: Kapitel-PDFs nach Lesezeichen (NSCA, Kenney, Climbing Medicine, Concurrent Training) bzw. nach im Text gefundenen Kapitelanfängen (Scans Uphill Athlete, Overcoming Gravity); 179 Dateien mit 4–59 Seiten, Seitensummen je Buch geprüft
  - datum: 2026-09-28
    was: Kapitel-PDFs der E-Books zunächst bis dreimal so groß wie das Buch (Vorspann Kenney 45 MB bei 34 Seiten)
    loesung: interne Sprungverweise (Inhaltsverzeichnis, Index) zogen die Zielseiten samt Ressourcen mit; Kapitel-PDFs ohne Link-Annotationen erzeugt → zusammen 258 MB, größte Datei 11 MB; Links bleiben im Original
  - datum: 2026-09-28
    was: Scans ohne Lesezeichen mit Unregelmäßigkeiten – Uphill Athlete – PDF-Seiten 88–89 wiederholen 86–87, Druckseiten 149–150 fehlen; Overcoming Gravity – fehlerhafte Texterkennung (z. B. „ANO“ statt „AND“), PDF-Seiten 577/578 vertauscht
    loesung: Druckseiten je Abschnitt aus dem Versatz berechnet und in docs/literatur/README.md dokumentiert; bei Zitaten aus Overcoming Gravity Wortlaut gegen das Seitenbild prüfen
  - datum: 2026-09-28
    was: Die Kapitel-PDFs vergrößern das Repo um 258 MB (zusammen mit den Originalen rund 540 MB PDFs); der CI-Lauf checkt das ganze Repo aus
    loesung: Deployment lädt nur server/ hoch (geprüft); beobachten, bei Bedarf Sparse-Checkout ohne docs/literatur im Workflow
  - datum: 2026-09-28
    was: L-A02 Ferrauti (2. Aufl.) als 355-MB-PDF geliefert (per HTTPS-Download), zu groß für GitHub (Grenze 100 MB je Datei)
    loesung: Das PDF ist aus Einzelkapiteln zusammengesetzt und bettete gleiche Schriften und Bilder mehrfach ein. Ohne Dubletten neu geschrieben → Gesamtbuch 94 MB, 25 Kapitel-PDFs zusammen 98 MB (größte 16 MB). Text und Bilddaten (Prüfsumme) aller 911 Seiten gleich dem Original; interne Links wie bei den anderen Kapitel-PDFs entfernt. Nebenbei geklärt – 2. Aufl. 2025, ISBN 978-3-662-69523-4, zweiter Herausgeber Wiewelhove
  - datum: 2026-09-28
    was: V-07 – Schwellenwerte des Schmerzmonitoring-Modells nicht im Abstract, Volltext nicht in PMC; einzige gefundene Angabe aus nicht begutachteter Sekundärquelle
    loesung: V-07 als teilweise geführt; Volltext in Beschaffungsliste; Abweichung zu 14.5 als Q-13 nach AP-07
  - datum: 2026-09-28
    was: L-T3-02 – Kernaussagen im Konzept stärker formuliert als im Original-Abstract („am effizientesten" statt Trend; Wiederholungsbereiche nicht im Abstract)
    loesung: Kernaussagen auf Abstract-Wortlaut zurückgeführt; Wiederholungsbereiche bleiben als Sekundärzitat markiert, Prüfung am Volltext (liegt im Repo)
  - datum: 2026-09-28
    was: L-T3-05 – Stufe A, aber n = 9 ohne signifikante Gruppenunterschiede
    loesung: konfidenz niedrig; größere Folgestudie derselben Autoren als L-T3-18 aufgenommen
  - datum: 2026-09-28
    was: Literatur-Sitzung – zwei PubMed-Aufrufe (Volltext, ID-Konvertierung) ohne Freigabe abgebrochen
    loesung: Ersatzweise Metadaten-Abruf bzw. Verlagsseiten/Bibliothekskataloge; Volltext Sprague 2021 (PMC7905015) für Modellschwellen nicht genutzt
  - datum: 2026-09-28
    was: V-07 in 5.2 bei AP-07 geführt, Abnahme AP-06 verlangt V-07
    loesung: V-07 auf AP-06 (Literatur) und AP-07 (Schwellen) aufgeteilt
  - datum: 2026-09-28
    was: Teilblock Haltung/Rücken – einzige Kandidatenquelle (McGill) Stufe C; Frage, ob Haltungskorrektur überhaupt Beschwerden reduziert
    loesung: Stufe-A-Kern über PubMed aufgebaut (L-T2-15 bis L-T2-18); Grenze „Haltung ≠ Schmerz" als Pflichtinhalt der Karte (D-54 c); McGill zurückgestellt
  - datum: 2026-09-28
    was: Im Chat vorgesehene IDs L-T2-19 bis L-T2-22 für vier optionale Quellen; nur eine aufgenommen
    loesung: Carrasco-Uribarren als L-T2-19, übrige ohne ID in 13.3
  - datum: 2026-09-28
    was: Übergaben Teil A und B beruhten auf einem älteren Konzeptstand (letzte IDs D-37, Q-08); D-38 und Q-09 waren inzwischen vergeben
    loesung: bei der Einarbeitung umnummeriert – D-38 → D-54 (zunächst D-53; AP-12 hat D-53 parallel belegt und wurde zuerst gemergt), Q-09 → Q-13; übrige neue IDs (L-P14, L-T2-15 bis L-T2-19, L-T3-18) waren frei
  - datum: 2026-09-28
    was: Befunde aus Durchgang 1 zunächst gegen die Chat-Zusammenfassung statt gegen den Chatverlauf geprüft; zwei „Abweichungen" (Isometrik überschätzt, MCID-Quelle falsch) waren im Reha-Chat bereits korrekt dargestellt
    loesung: Gegenprüfung am Chatverlauf (conversation_search innerhalb des Chats); beide Befunde zurückgezogen; Regel für Folgesitzungen – Aussagen aus Vorchats nur am Verlauf, nie an Zusammenfassungen prüfen
  - datum: 2026-09-28
    was: Zwei Metaanalysen und eine EMG-Aussage aus dem Reha-Chat ohne Autor/Journal zitiert, per PubMed nicht identifizierbar
    loesung: nach 13.3 mit Vermerk; Aussagen durch verifizierte Quellen (L-R-16, L-R-20) ersetzt
  - datum: 2026-09-28
    was: Zitat „van der Worp, KSSTA 2011" passt zu keiner Arbeit exakt
    loesung: beide Kandidaten (BJSM 2011, KSSTA 2013) in 13.3; inhaltlich durch L-R-11 abgedeckt
  - datum: 2026-09-28
    was: Nielsen 2014 als Beleg der 30-%-Wochenregel untersuchte Laufanfänger; Übertragung unsicher
    loesung: Gegenrecherche ergab Frandsen 2025 (erfahrene Läufer, Einzellauf-Spitzen) → Kern L-R-24, Nielsen optional, Regelfrage Q-15
  - datum: 2026-09-28
    was: Evidenz für Übungstherapie bei Patellatendinopathie laut Cochrane 2025 deutlich unsicherer als in Einzelreviews dargestellt
    loesung: L-R-23 als Kern; Pflichtabschnitt „Grenzen" in der Karte (D-61 e)
  - datum: 2026-09-28
    was: Dosis nach Tang 2024 wurde im Reha-Chat als Rezidivschutz-Dosis gelesen; Wagemans 2022 findet keinen Zusammenhang Umfang–Rezidiv
    loesung: Geltungsbereich in L-R-16 präzisiert (Funktion/Balance, nicht Rezidiv)
  - datum: 2026-09-28
    was: Übergabe Teil C beruhte auf einem älteren Konzeptstand; D-39, Q-10, Q-11 waren inzwischen vergeben, Q-09 aus Teil B war bereits Q-13
    loesung: umnummeriert – D-39 → D-61, Q-10 → Q-15, Q-11 → Q-16, Ergänzung Q-09 → Q-13; Kartenzuschnitt von 5 auf 6 Sammeldateien (13.1 erlaubt 4–6)
  - datum: 2026-09-28
    was: Hypertrophie war in D-28 als „kein Primärziel“ zurückgestellt; der Athlet fragt nach Hypertrophie-Literatur
    loesung: Rückfrage – Ergänzung, D-28 bleibt; L-T2-07 weiter zurückgestellt (D-62)
  - datum: 2026-09-28
    was: Keine Metaanalyse zu Hypertrophie mit Band oder Kettlebell bei gesunden Erwachsenen gefunden (PubMed); Treffer nur zu Kraft (L-T2-24) oder an Älteren/Patienten
    loesung: Übertragung über L-T2-23 als gekennzeichneter Schluss; Pflichtinhalt „Grenzen“ (D-62 e)
  - datum: 2026-09-28
    was: L-P08 (Übersicht über Reviews) enthält vermutlich einen Teil der neuen Arbeiten
    loesung: auf Wunsch des Athleten trotzdem aufgenommen; Klärung am Volltext (V-16)
  - datum: 2026-09-28
    was: Widersprüchliche Befunde zur Modalität der Ausdauer – Nachteil beim Laufen (L-T2-25, L-T2-31) vs. Trend zu Nachteil bei Rad-HIIT für Unterkörperkraft (L-T2-32)
    loesung: in der Karte unter „Grenzen/Widersprüche“; keine Regel aus der Modalität allein ableiten (D-62 e)
  - datum: 2026-09-28
    was: Die Sitzung prüfte zunächst die Konzeptkopie im Projektwissen; diese war veraltet (Stand vor D-38). Die aktuelle Fassung lag als Anhang im Chat vor
    loesung: IDs aus der aktuellen Fassung (D-61) abgeleitet; Athlet hat das Projektwissen aktualisiert; U1 prüft die IDs vor der Einarbeitung erneut
  - datum: 2026-09-29
    was: Zwei neue PDFs im Commit „aa“ – Laursen/Buchheit (L-T1-07) und erneut Kenney 7. Aufl.; die Kenney-Datei ist bytegleich mit L-A01 (gleicher Git-Blob)
    loesung: L-T1-07 nach D-51 umbenannt und in 34 Kapitel-PDFs geteilt; Kenney-Dublette entfernt, L-A01 bleibt vorläufig (8./9. Aufl. weiter offen)
  - datum: 2026-09-29
    was: Commit „Literatur“ mit drei Dateien – Hörst, Training for Climbing (PDF, 3. Aufl., Scan ohne Lesezeichen) sowie zwei EPUBs (Mobråten/Christophersen, The Climbing Bible – Practical Exercises, 2022; Consuegra, The Science of Climbing Training, 2023), beide nicht im Konzept
    loesung: Hörst als L-T3-09 eingeordnet (Status bleibt optional) und nach Inhaltsverzeichnis in 17 Kapitel-PDFs geteilt; die EPUBs bleiben unsortiert liegen, bis der Athlet Angaben zur Einordnung nachliefert; die EPUBs werden danach in PDF umgewandelt (Entscheidung des Athleten)
  - datum: 2026-09-29
    was: L-T1-01 und L-T3-08 nicht digital erhältlich; Gesamtscan nicht vorgesehen
    loesung: Abdeckung L-T1-01 am Inhaltsverzeichnis L-A02 geprüft → nicht aufgenommen, Verweis L-T1-15; Ersatzsuche Klettertraining → L-T3-19 Consuegra (EPUB), dazu L-T3-10, L-T3-16, L-T3-09 (D-70)
  - datum: 2026-09-29
    was: Beim Abgleich fiel auf, dass HRV-/Ruhepuls-Abweichung als Deload-Trigger (Abschnitt 14 Kap. 4) ohne Literatur geführt wurde
    loesung: L-P15 ausgewählt, L-P16 optional (D-70)
  - datum: 2026-09-29
    was: EPUB ohne Seitenliste – Druckseiten nicht zitierfähig; Abbildungen fehlen im Markdown
    loesung: D-71 (Zitat mit Kapitel und Abschnitt, Seitenmarken als „[S. n]“, wo vorhanden), V-17 für optionale Seitenzuordnung; Ansichts-PDF je Kapitel mit Abbildungen, Seitenzahlen nicht zitierfähig
  - datum: 2026-09-29
    was: Übergabe Literatur-Nachsteuerung passte nicht zum Repo-Stand – Hörst 3. Aufl. lag bereits vor (Übergabe – nicht beschaffen), L-T3-10 lag als EPUB ohne DRM vor (Übergabe – Kobo-DRM ungeeignet), dazu zwei EPUBs ohne Konzepteintrag (Practical Exercises, Managing Injuries); D-64/D-65 der Übergabe waren im Konzept schon vergeben
    loesung: Athlet entschied 3. Aufl. behalten und Übungsband als L-T3-20; Managing Injuries als L-T3-21 Stufe C vorläufig (Code-Instanz, Bestätigung offen); umnummeriert D-64 → D-70, D-65 → D-71
  - datum: 2026-09-29
    was: Repo öffentlich; Verlagswerke unter docs/literatur/ sind öffentlich abrufbar (widerspricht D-31)
    loesung: als Q-21 geführt – Athlet hat am 2026-09-29 entschieden, dass das Repo vorerst öffentlich bleibt und wieder privat wird, sobald die Recherche keinen Zugriff mehr braucht (Übergabe T4, K-9); D-23 und D-31 bleiben unverändert. Die Git-History enthält alle Volltexte – vor einer dauerhaften Veröffentlichung Literatur auslagern und History bereinigen
  - datum: 2026-09-29
    was: Commit „Lit“ mit 13 PDFs – 11 Titel der Beschaffungsliste, erneut Hörst 3. Aufl. (bytegleich mit L-T3-09, gleicher Git-Blob) und VISA-P (L-R-08) doppelt; für Block R gab es noch keinen Ordner
    loesung: 11 PDFs nach D-51 umbenannt und einsortiert, neuer Ordner `r-reha/`; Dubletten entfernt (behalten wurde die VISA-P-Fassung mit Metadaten). Befund – das VISA-P-PDF enthält das Erratum 2013 zu den Punktwerten der Items 8b/8c (bei L-R-08 vermerkt)
  - datum: 2026-09-29
    was: Übergabe T4 Teil A stand auf „vorgeschlagen“ und sah die Einarbeitung erst nach Teil B vor; ein eigener T4-Bereich braucht eine 7. Sammeldatei, 13.1 erlaubte 4–6
    loesung: Athlet bestätigte Teil A und die sofortige Einarbeitung; 13.1 auf 4–7 Dateien erweitert (D-79; zunächst als D-72 vergeben, wegen paralleler Vergabe D-72 bis D-78 in AP-15 umnummeriert); IDs der Übergabe umnummeriert – Q-T4-1 bis -5 → Q-17 bis Q-21, V-T4-1 bis -6 → V-18 bis V-23, PF-T4-a bis -k → D-79 (a)–(k)
  - datum: 2026-09-29
    was: Hüftspezifische Evidenz ist dünn; chronische Studien fast nur Hamstrings, Quadrizeps, Wade; einzige chronische Hüftbeuger-RCT (L-T4-19) klein
    loesung: Hüftrichtungen über allgemeine Metaanalysen (L-T4-02, L-T4-03) begründen, Übertragung als „Einschätzung“ kennzeichnen (D-79 i)
  - datum: 2026-09-29
    was: Dosisbefunde T4 widersprechen sich (L-T4-02 vs. L-T4-04/L-T4-05)
    loesung: Dosis als Richtwert, Widerspruch in der Karte unter „Grenzen“ (D-79 b)
  - datum: 2026-09-29
    was: Yoga – keine belastbare Evidenz für gesunde Sportler gefunden
    loesung: Yoga nur als Übungsfundus in Teil B (Stufe C); Begründung über L-T4-13
  - datum: 2026-09-29
    was: Commit „Literatur“ mit 37 PDFs, überwiegend mit Verlags- oder DOI-Dateinamen; L-P16 doppelt (Pre-Proof und Verlagsfassung); L-T2-29 und L-R-18 nur als Autorenmanuskript; Corrigenda zu L-T2-23 und L-T2-24 fehlen
    loesung: Zuordnung über DOI (32) bzw. Titel (5), 36 PDFs nach D-51 umbenannt und einsortiert; Pre-Proof entfernt; Manuskriptfassungen und fehlende Corrigenda beim Eintrag (`zugang`) vermerkt; Lizenzen aus dem Volltext übernommen, wo angegeben
  - datum: 2026-09-29
    was: Commit „Literatur“ mit 26 Dateien – witvrouw2001.pdf ist nicht L-T4-22, sondern Witvrouw et al. 2000 (Am J Sports Med 28(4), vorderer Knieschmerz); Climbing-Bible-EPUB bytegleich mit L-T3-10; ein Stretching-Anatomy-EPUB mit 0 Byte; Stretching Anatomy zusätzlich als PDF
    loesung: falsches Witvrouw-PDF entfernt, L-T4-22 bleibt offen (Entscheidung Athlet); Dubletten entfernt; Stretching Anatomy als PDF (Kapitel-PDFs) und EPUB abgelegt (Entscheidung Athlet); neuer Ordner `t4-beweglichkeit/`; Corrigenda zu L-T2-23/-24 als eigene Dateien (Feld `corrigendum_datei`)
  - datum: 2026-09-29
    was: Aktualisierte Übergabe T4 (K-8, K-9) – Behm 2025 in den Kern, Stretching Anatomy neu (L-T4-34), drei Bücher in 13.3; Repo soll wieder privat werden
    loesung: eingearbeitet (13.2.6, D-79 Nachtrag, 13.3, 13.4, V-21, Q-21, Konzeptkopf, D-23)
  - datum: 2026-09-29
    was: L-T4-22 (Witvrouw 2001) war nach dem falschen Upload weiter offen
    loesung: Entscheidung Athlet – nicht aufnehmen; Status nicht_aufgenommen, aus Kern, 13.4, Kartenzuschnitt und Regelvorschlag D-79 (e) entfernt (dort L-T4-23 als Knie-Einzelbefund), 13.3 ergänzt
  - datum: 2026-09-29
    was: Übergabe Lückenprüfung Standardwerke nimmt Freiwald auf, den die Übergabe T4 (K-8) am selben Tag in 13.3 gestellt hatte
    loesung: Entscheidung Athlet (SW-E4) – K-8 für Freiwald revidiert, Eintrag von 13.3 nach 13.2.6 (L-T4-35); Alter und van der Poel bleiben in 13.3
  - datum: 2026-09-29
    was: Lückenprüfung auf Konzeptstand 6234515 begonnen; Einarbeitung der T4-Übergabe (c758efa) vergab zwischenzeitlich L-T4-34
    loesung: Übergabe auf c758efa abgeglichen; T4-Nummern ab L-T4-35; alle vorgeschlagenen IDs (D-80, Q-22, V-24, L-T1-16, L-T2-33, L-R-29, L-R-30, L-T4-35, L-T4-36) bei Einarbeitung frei, keine Umnummerierung
  - datum: 2026-09-29
    was: Commit „Literatur“ und CIQ-Server – McGill und GOTS nur in der 3. Aufl. (ausgewählt war die 4.); Brukner & Khan nur Band 2 „Medicine of Exercise“ (5. Aufl.) als Text-PDF und als Scan; Consuegra-EPUB bytegleich mit L-T3-19; Fascia in Sport and Movement 159 MB (über dem GitHub-Limit)
    loesung: Entscheidung Athlet – vorhandene Ausgaben verwenden, nicht auf Neuauflagen warten (Nachtrag D-51; betrifft auch L-A01 7. Aufl. und L-T3-09 3. Aufl.); Brukner Band 2 beide gelöscht (in D-80 als ohne Bezug bewertet), Verletzungsband folgt; Consuegra-Dublette gelöscht; Fascia nur als 50 Kapitel-PDFs abgelegt (D-31); L-T3-16, L-T2-33, L-R-30 in Kapitel-PDFs geteilt (GOTS-Kapitel und Druckseiten aus den Kopfzeilen), L-T1-16 als Kapitel-Markdown mit Ansichts-PDFs
  - datum: 2026-09-29
    was: Beim Teilen lief das Hilfsskript split.py versehentlich vollständig mit (Import ohne Main-Schutz)
    loesung: Arbeitsbaum geprüft – keine bestehende Datei verändert; Teilfunktion in das neue Skript übernommen
```
Hinweis Prüfprotokoll: Die Einträge unter `probleme_loesungen` sind bei Anlage von `docs/pruefung/pruefprotokoll.md` als AP-06-Block zu übernehmen.

## AP-07 Trainerregeln (Projekt-Chat)

- **Ziel:** `docs/regeln/trainerregeln.md` gemäß Abschnitt 14.
- **Vorgaben aus AP-06** (Kapitel 2 und 3 in 14; D-54, D-61, D-62, D-79, Q-13, Q-15, Q-16, Q-18, Q-19):
```yaml
- regelvorschlag: Haltungsarbeit = Kräftigung BWS/HWS-Extensoren und Schulterblattmuskulatur, kombiniert HWS + BWS; Dehnen nicht als Haltungskorrektur einplanen
  quelle: L-T2-15, L-T2-16, L-T2-19
- regelvorschlag: Kreuzschmerz-Prävention 2–3× pro Woche als Teil bestehender Kraft-/Haltungseinheiten (Kräftigung + Dehnung oder Ausdauer)
  quelle: L-T2-17, L-T2-18
- regelvorschlag: Erfolgskriterium Haltungsarbeit nicht über Schmerzfreiheit definieren
  quelle: L-T2-16
- offene_frage: Q-13 Schmerzschwellen 14.5 vs. Modell L-P13 (nach Volltextprüfung L-P13)
- regelvorschlag: Sehnentraining progressiv (Stufenmodell L-R-01); Last moderat bis schwer gleichwertig (L-R-03, L-R-28); 1 schwerer Tag/Woche möglich, als Einzelstudienbefund gekennzeichnet (L-R-04)
  quelle: D-61 (a)
- regelvorschlag: Isometrik als Einstieg und an schmerzhaften Tagen, nicht als Hauptstrategie (L-R-10)
  quelle: D-61 (b)
- regelvorschlag: Verlaufsmessung VISA-P (L-R-07/08), klinisch relevante Änderung ≥ 13 Punkte (L-R-09)
  quelle: L-R-07, L-R-08, L-R-09
- regelvorschlag: Balance-/neuromuskuläres Training zur Rezidivprophylaxe beibehalten (L-R-14, L-R-15, L-R-25); Dosis für Funktion 3×/Woche 20–30 min (L-R-16); Gerätetyp zweitrangig (L-R-17, L-R-20)
  quelle: D-61 (c)
- regelvorschlag: Laufprogression je Einheit (Q-15, L-R-24)
  quelle: D-61 (d)
- regelvorschlag: Orthese/Tape als Option (Q-16, L-R-26)
- regelvorschlag: Schmerzschwellen erst nach Klärung Q-13 (L-P13, L-R-02)
- hinweis: Bestehender Plan Rev. 6 (WebApp) nutzt Wochen-km-Progression mit Morgentest-Bedingung; bei Annahme von Q-15 ist die Laufprogression im laufenden Block zu prüfen
- regelvorschlag: Hypertrophie als Nebenziel über das Wochenvolumen je Muskelgruppe steuern; mehr Sätze mit abnehmendem Grenznutzen; die Frequenz nach Planbarkeit wählen (für Hypertrophie nachrangig, für Kraft wirksam)
  quelle: L-T2-20 (optional L-T2-27); Anker L-P08
- regelvorschlag: Sätze nahe am Muskelversagen beenden; Training bis zum Versagen nicht als Standard einplanen
  quelle: L-T2-21 (explorativ), L-T2-22 (optional L-T2-30)
- regelvorschlag: Im Heimtraining mit leichten Lasten und Band Sätze nahe ans Versagen führen, damit sie hypertrophiewirksam sind; für Maximalkraft höhere Lasten bzw. schwerere Hebelvarianten (Calisthenics)
  quelle: L-T2-23, L-T2-24, D-29
  kennzeichnung: Übertragung auf Band/Kettlebell ist „Einschätzung“ (D-62 e)
- regelvorschlag: Bei Blöcken mit Kraft- oder Hypertrophie-Anteil die Interferenz beachten – die Hypertrophie des ganzen Muskels bleibt erhalten, auf Faserebene kleiner Nachteil, vorläufig stärker bei Laufen und HIIT; mit der Reihenfolgeregel aus L-P09 abgleichen
  quelle: L-T2-25, L-T2-26 (optional L-T2-31, L-T2-32)
- hinweis: Ernährung/Protein ist nicht im Umfang der Ergänzung (D-62)
- regelvorschlag: T4 ROM chronisch mit statischem Dehnen oder PNF; dynamisches Dehnen nur für Aufwärmen/Vorbereitung
  quelle: D-79 (a); L-T4-02, L-T4-25
- regelvorschlag: T4 Dosis-Richtwert ≥ 5 min Dehnzeit je Zielmuskelgruppe und Woche, verteilt auf möglichst viele Tage (≥ 2, besser ≥ 5); als Richtwert kennzeichnen (widersprüchlich zu L-T4-02)
  quelle: D-79 (b); L-T4-05, L-T4-04, L-T4-31
- regelvorschlag: Kräftigung mit Zusatzlast über den vollen Bewegungsumfang darf Dehnzeit ersetzen; reine Körpergewichtsübungen nicht als ROM-Maßnahme zählen
  quelle: D-79 (c); L-T4-10, L-T4-11, L-T4-29
- regelvorschlag: Dehnen im Aufwärmen optional; statisch < 60 s je Muskelgruppe, danach dynamische, sportspezifische Aktivität; vor dem Laufen kein Verbot
  quelle: D-79 (d); L-T4-06, L-T4-08, L-T4-09
- regelvorschlag: Dehnen nicht als Verletzungsprävention begründen; Prävention über Kraft und Balance (T2, Block R); Knie-Beweglichkeit als Zusatzbaustein mit Kennzeichnung „Einzelbefund“ (L-T4-23)
  quelle: D-79 (e); L-T4-14, L-T4-01, L-T4-23, L-R-11
- regelvorschlag: Dehnen nach Belastung nicht zur Erholung oder gegen Muskelkater einplanen; aus Gründen des Wohlbefindens zulässig
  quelle: D-79 (f); L-T4-15, L-T4-16
- regelvorschlag: Ruhige Dehn-/Mobility-Einheiten (z. B. abends, Ruhetag) als Wohlbefindens-Baustein, Kennzeichnung „schwache Evidenz“
  quelle: D-79 (g); L-T4-17, L-T4-18
- regelvorschlag: Dehnen nicht zur Haltungskorrektur
  quelle: D-79 (h), D-54 (a); L-T2-15, L-T4-01
- regelvorschlag: Hüftrichtungen einzeln planen; Dosisübertragung aus Hamstring-/Wadenstudien als Einschätzung kennzeichnen; Ausführungsdetails (z. B. Beckenkippung beim Hüftbeuger) im Übungskatalog
  quelle: D-79 (i); L-T4-19, L-T4-21, L-T4-07, L-T4-03
- regelvorschlag: Klettern – Hüftbeweglichkeit bis funktionelles Niveau entwickeln, darüber keine Priorität; Verlauf mit kletterspezifischen Tests (Einschätzung)
  quelle: D-79 (j); L-T4-26, L-T4-27
- regelvorschlag: Blöcke ≤ 4 Wochen – Dehnen statt Faszienrolle für das ROM-Ziel
  quelle: D-79 (k); L-T4-12
- offene_frage: Q-18 T4 als eigene Einheiten (`mobilitaet`) oder als Block in Kraft-/Klettereinheiten; Q-19 Dehnintensität nur subjektiv
```
- **Abhängigkeiten:** AP-06 (Quellen).
- **Abnahmekriterien:** Jede Regel mit Quelle oder Kennzeichnung „Einschätzung"; Schmerz- und Deload-Regeln vom Athleten bestätigt.
- **Status:**
```yaml
status: offen
begonnen: null
abgeschlossen: null
probleme_loesungen: []
```

## AP-08 Athletenprofil und erster Blockplan (Projekt-Chat)

- **Ziel:** Athletenprofil in der Datenbank (D-48, über `update_athlete_profile` im Projekt-Chat) und `docs/plaene/block-01.md`.
- **Umfang:** Ziele mit Datum, Trainingsalter je Bereich, Zeitbudget/Wochenstruktur, Ausrüstung (Hangboard, Gym, Halle), aktuelle Einschränkungen, Ausgangstests (LTHR-Test und LT1-Bestimmung für das Zonenmodell D-27; Maximalhang und Verlaufstests nach L-T3-03; Leistungsniveau nach L-T3-04; Hüft-ROM-Verlaufsmessung nach Q-20); erster Block mit Phasen und Prioritäten.
- **Abhängigkeiten:** AP-07.
- **Abnahmekriterien:** Profil (alle Abschnitte) und Block vom Athleten bestätigt; Block ins Projekt-Wissen gespiegelt.
- **Status:**
```yaml
status: offen
begonnen: null
abgeschlossen: null
probleme_loesungen: []
```

## AP-09 Betrieb und Optionen

- **Ziel:** Betriebsreife und optionale Erweiterungen nach Praxiserfahrung.
- **Umfang (jeweils einzeln zu entscheiden):** Asymmetrische Backup-Verschlüsselung (Public Key auf dem Server) statt Passwort; JSON-Export aller Daten für Portabilität; Cron-Spiegel Intervals.icu → MySQL (D-09); Feedback-Rückschreiben nach Intervals.icu (Q-02); Verlauf-Screen S6; Passkey-Login (D-33); Athletenprofil als DB-Objekt; Offline-Fähigkeit der Webseite.
- **Abhängigkeiten:** AP-05.
- **Entscheidung (2026-09-28, D-42–D-45):** alle Optionen außer keiner werden umgesetzt; Reihenfolge und Detailfragen siehe D-42.
- **Status:**
```yaml
status: in_arbeit
begonnen: 2026-09-28
abgeschlossen: null
teilpakete:
  - JSON-Export: erledigt (Code-Stand 0.8.0, Abnahme durch Athlet offen)
  - Verlauf S6: erledigt (Code-Stand 0.8.0, Abnahme durch Athlet offen)
  - Cron-Spiegel (D-43): erledigt (Code-Stand 0.9.0, Cronjob und Abnahme durch Athlet offen)
  - Feedback-Rückschreiben (Q-02 → D-46): erledigt (Code-Stand 0.10.0, Prüfung mit echtem Konto offen – V-03/V-04)
  - Passkey (D-44): erledigt (Code-Stand 0.11.0, Anlegen und Anmelden auf Smartphone/Tablet durch Athlet offen)
  - Asymmetrische Backups: gestrichen (D-47)
  - Athletenprofil als DB-Objekt (D-48): erledigt (Code-Stand 0.12.0, Befüllen in AP-08, Abnahme durch Athlet offen)
  - Offline-Fähigkeit (D-45, D-49): erledigt (Code-Stand 0.13.0, Prüfung auf iPhone/Android im Funkloch durch Athlet offen)
probleme_loesungen:
  - datum: 2026-09-28
    was: JSON-Export enthält Gesundheitsdaten unverschlüsselt
    loesung: bewusst unverschlüsselt (Portabilität ist der Zweck); nur nach Login, Hinweis „unverschlüsselt, enthält Gesundheitsdaten“ am Knopf, Audit-Log; Passwort-Hash, Sessions, Tokens und Cache sind nicht enthalten
  - datum: 2026-09-28
    was: S6-Mockup setzt Balkenhöhen und Legendenfarben per Inline-Style (CSP verbietet das)
    loesung: Höhenklassen in 5-%-Schritten und Farbklassen in training.css; Rasterzellen über die vorhandenen data-i-Selektoren aus app.css
  - datum: 2026-09-28
    was: Wochenlast Ausdauer in S6 – aus Rückmeldung (sRPE) oder aus Intervals-Load?
    loesung: einheitlich sRPE aus der Rückmeldung (RPE × Dauer) für alle Bereiche, damit die Achse vergleichbar bleibt; Intervals-Load folgt mit dem Cron-Spiegel optional
  - datum: 2026-09-28
    was: Hinweis „steigt seit vier Wochen“ im Mockup braucht eine Trendregel (AP-07)
    loesung: vorerst weggelassen; Trend liefert get_pain_history an Claude
  - datum: 2026-09-28
    was: Spiegel – wann live, wann aus MySQL? (D-43 „Live-Abfrage nur als Rückfall“)
    loesung: read-through – ein Zeitraum wird höchstens alle 5 Minuten live geholt und dabei gespiegelt (auch heute aufgezeichnete Aktivitäten sofort sichtbar); sonst Spiegel; bei API-Fehlern Spiegel mit Hinweis. Stündlicher Cronjob hält die letzten 14 Tage aktuell, einmalig tage=365 für die Vorgeschichte
  - datum: 2026-09-28
    was: In Intervals.icu gelöschte oder zusammengeführte Aktivitäten blieben sonst im Spiegel
    loesung: bei jedem Abgleich eines Zeitraums werden dort nicht mehr vorhandene Aktivitäten entfernt
  - datum: 2026-09-28
    was: Zweiter Cron-Endpunkt bräuchte ein weiteres Secret
    loesung: ein gemeinsames CRON_SECRET für alle Cron-Endpunkte (vorher BACKUP_CRON_SECRET; noch nicht produktiv, daher umbenannt)
  - datum: 2026-09-28
    was: Einstellungsseite (mit dem Migrationsknopf) stürzte bei veraltetem Schema ab, weil sie die neue Spiegeltabelle abfragte
    loesung: Spiegelzugriffe fangen fehlende Tabellen ab; Test „Schreibsperre“ setzt jetzt allgemein die letzte Migration zurück und hat den Fehler aufgedeckt
  - datum: 2026-09-28
    was: Endpunkte und Feldsemantik für das Rückschreiben nicht am Original prüfbar (PUT /activity/{id} mit icu_rpe/feel, POST /activity/{id}/messages mit content; Richtung von feel V-03)
    loesung: Kommentar-Endpunkt aus Sekundärquelle (Client-Quellcode), Aktivitätsänderung angenommen; beides im Prüfprotokoll zur Verifikation mit dem echten Konto; bei Fehlern bleibt die Rückmeldung gespeichert (Hinweis „Gespeichert, aber nicht übertragen“, audit_log intervals_error)
  - datum: 2026-09-28
    was: Doppelte Kommentare bei erneutem Speichern
    loesung: Kommentar nur, wenn sich die Notiz gegenüber der gespeicherten geändert hat; RPE/Gefühl werden bei jedem Speichern (idempotent) gesetzt
  - datum: 2026-09-28
    was: WebAuthn braucht JavaScript (navigator.credentials), die Seiten sind bisher ohne JavaScript gebaut; CSP ohne Inline-Skripte
    loesung: eine externe Datei public/js/passkey.js nur auf Login und Einstellungen; Passkey-Knöpfe sind versteckt und erscheinen nur, wenn der Browser WebAuthn kann; Passwort-Login bleibt ohne JavaScript
  - datum: 2026-09-28
    was: Wo liegt die Challenge zwischen Options- und Antwort-Request? (Login ohne Session)
    loesung: im Cookie training_webauthn als Challenge + Ablauf + HMAC (OAUTH_JWT_SECRET, Zweck create/get), 5 Minuten, HttpOnly, SameSite=Strict; nach Gebrauch gelöscht – keine neue Tabelle, kein Zustand auf dem Server
  - datum: 2026-09-28
    was: Passkey und Passwort-Sperre (D-33)
    loesung: erfolgreiche Passkey-Anmeldung setzt failed_logins zurück und hebt locked_until auf (wie ein erfolgreicher Passwort-Login); Passkey-Fehlversuche zählen nicht, weil ohne privaten Schlüssel nicht zu raten
  - datum: 2026-09-28
    was: Passkeys in Backup und Export?
    loesung: im SQL-Backup enthalten (öffentliche Schlüssel, nach Restore weiter nutzbar); nicht im JSON-Export (Anmeldedaten wie der Passwort-Hash)
  - datum: 2026-09-28
    was: Browser-Prüfung ohne echtes Gerät
    loesung: automatisiert mit Software-Authenticator (ES256, Attestierung none) in PHPUnit und virtuellem Authenticator in Chromium; echte Geräte (iPhone/Android, Synchronisierung über iCloud/Google) prüft der Athlet
  - datum: 2026-09-28
    was: Asymmetrische Backups – Nutzen gegenüber Aufwand (Rückfrage des Athleten „Was spricht gegen ZIP?“)
    loesung: gestrichen (D-47) nach Abwägung der Bedrohungsfälle; ZIP wäre ebenfalls symmetrisch
  - datum: 2026-09-28
    was: Claude und Webseite ändern dasselbe Profil – gegenseitiges Überschreiben
    loesung: Webformular trägt die gelesene Fassung mit; hat sich der Abschnitt inzwischen geändert, wird nicht gespeichert (409) und der eigene Text bleibt im Formular; Claude wird im Tool-Text angewiesen, vorher zu lesen und den vollständigen Abschnitt zu schicken
  - datum: 2026-09-28
    was: Markdown-Darstellung auf der Webseite
    loesung: Text wird unverändert (mit Zeilenumbrüchen) angezeigt, keine Markdown-Bibliothek; Überschriften/Listen bleiben als Zeichen lesbar
  - datum: 2026-09-28
    was: Antwortbudget (8.3) bei langen Profilen
    loesung: höchstens 6 000 Zeichen je Abschnitt; Fassungen in include_history auf 1 500 Zeichen gekürzt, höchstens 20
  - datum: 2026-09-28
    was: Build-Schritt docs/athlet → server/resources/athlet und App::profileFile
    loesung: entfernt (DB ist einzige Quelle); docs/athlet/profil.md existierte noch nicht, daher kein Import nötig
  - datum: 2026-09-28
    was: Offline gepufferte Formulare tragen ein CSRF-Token, das nach Abmelden/neuer Sitzung nicht mehr gilt
    loesung: der Service Worker holt vor dem Senden ein frisches Token (GET /offline/token, nur gleiche Herkunft lesbar) und ersetzt es; ohne Sitzung bleiben Eingaben „wartet auf Anmeldung“
  - datum: 2026-09-28
    was: Gepufferte Sendungen brauchen eine auswertbare Antwort statt Seite/Weiterleitung
    loesung: Kopfzeile X-Offline-Queue – Server antwortet 204 (übernommen), 401 (Anmeldung), 409 (inzwischen geändert), 422 (ungültig), 503 (Schreibsperre, später erneut)
  - datum: 2026-09-28
    was: Konflikterkennung ohne Versionsspalten
    loesung: Feld „stand“ im Formular = gekürzter SHA-256 über den gespeicherten Eintrag (Check-in-Zeile bzw. Status + Durchführung der Einheit); leer, wenn es noch keinen Eintrag gibt; Formulare ohne das Feld werden nicht geprüft
  - datum: 2026-09-28
    was: Zwei Offline-Eingaben zum selben Eintrag würden mit sich selbst kollidieren
    loesung: eine neuere Offline-Eingabe zum selben Check-in-Tag bzw. zur selben Einheit ersetzt die ältere im Puffer
  - datum: 2026-09-28
    was: Gespeichertes Formular „heute“ (Check-in, Schmerz) an einem späteren Tag offline genutzt
    loesung: Seitenskript setzt bei Formularen ohne ?datum= das Datum auf den heutigen Gerätetag und passt Überschrift und Stand an (Hinweis „Datum auf heute gesetzt“)
  - datum: 2026-09-28
    was: Zeitpunkt der Durchführung bei spätem Senden
    loesung: Service Worker ergänzt offline_erfasst (Erfassungszeit); der Server übernimmt sie für performed_at, wenn sie höchstens 14 Tage alt ist und auf den Tag der Einheit fällt
  - datum: 2026-09-28
    was: Safari (iPhone/iPad) kennt keine Hintergrund-Synchronisation
    loesung: Senden zusätzlich bei jedem Seitenaufruf und beim Ereignis „online“; auf dem iPhone gehen Eingaben also raus, sobald die App mit Netz geöffnet ist
  - datum: 2026-09-28
    was: Playwrights Offline-Schalter erfasst den Service Worker nicht (Seiten kamen weiter aus dem Netz)
    loesung: Browser-Prüfung mit gestopptem lokalen Server (echter Netzfehler); dabei Vorladen, Offline-Lesen, Puffern, Konflikt, Erzwingen, Abmelden und Senden nach Login geprüft
```

## AP-10 Backup und Update-Mechanik

- **Ziel:** Verschlüsselte DB-Backups (manuell, per E-Mail, vor Migrationen) und sichere Schema-Updates gemäß D-18 und D-20.
- **Umfang:**
  1. Dump-Modul: SQL-Dump per PHP (Schema + Daten, alle Tabellen inkl. `schema_version` und `audit_log`; `oauth_token`, `oauth_auth_code` und `web_session` ausgenommen – nach einem Restore wird neu angemeldet und der Connector neu freigegeben), gzip.
  2. Verschlüsselungsmodul: OpenSSL-kompatibel (AES-256-CBC, PBKDF2, Iterationszahl als Konstante dokumentiert, `Salted__`-Header); Passwort aus `.env`; Dateiname mit Zeitstempel und Schema-Version.
  3. Download: Schaltfläche in „Einstellungen" (nur eingeloggt), liefert die verschlüsselte Datei.
  4. E-Mail-Versand: geschützter Endpunkt, aufgerufen von einem Lima-City-Cronjob (V-10: keine PHP-CLI), Empfängeradresse und Intervall in `.env`, Versand mit Anhang; Fehler werden geloggt und beim nächsten Login angezeigt.
  5. Pre-Migration-Dump: Migrations-Runner aus AP-00 erweitern – Dump vor der ersten ausstehenden Migration, Ablage außerhalb Docroot, Rotation der letzten 5; Migration bricht ab, wenn der Dump fehlschlägt.
  6. Schreibsperre: Prüfung `APP_SCHEMA_VERSION` gegen `schema_version` bei jedem Request; „Update erforderlich"-Seite; MCP-Tools antworten mit klarer Fehlermeldung statt zu schreiben; manuelle Migrations-Schaltfläche in „Einstellungen".
  7. Restore-Anleitung im README: `openssl enc -d …` → `gunzip` → Import über phpMyAdmin (Lima-City) oder MySQL-Client; Reihenfolge bei Rückweg (Git-Tag zurück, Dump einspielen).
- **Abhängigkeiten:** AP-03, AP-04; V-10 erledigt.
- **Abnahmekriterien:** Manuell heruntergeladene Datei lässt sich auf einem anderen Rechner nur mit dem Passwort entschlüsseln und ergibt einen Dump, aus dem die Beispiel-Woche in eine leere DB zurückgespielt werden kann (Restore-Test dokumentiert im Prüfprotokoll); E-Mail mit Anhang kommt an; absichtliche Code/Schema-Abweichung blockiert Web- und MCP-Schreibzugriffe und wird durch die Migrations-Schaltfläche behoben; vor der Migration liegt ein neuer Pre-Migration-Dump; fehlgeschlagener Dump verhindert die Migration.
- **Status:**
```yaml
status: in_arbeit         # Code-Stand 0.6.0; Restore-Test, Schreibsperre, Pre-Migration-Dump und Abbruch automatisiert geprüft; offen: Entschlüsseln auf dem Rechner des Athleten, E-Mail über Lima-City-SMTP (Anhang-Limit V-10), MCP-Schreibsperre mit AP-05
begonnen: 2026-09-28
abgeschlossen: null
umsetzung:
  - server/src/Backup/ – Dumper, Encryptor (OpenSSL-Format, PBKDF2-SHA256 200 000 Iterationen), BackupService, UpdateService (Dump vor Migration, Schreibsperre), MailBackup + SmtpMailer (PHPMailer)
  - Einstellungen – Download, Migrationsknopf, Status Backup-Mail und Pre-Migration-Dumps; Seite „Update erforderlich“ für gesperrte Schreibzugriffe
  - GET /cron/backup-mail?key=… (CRON_SECRET), Intervall BACKUP_MAIL_INTERVAL_DAYS (Standard 7); Zustand var/backup-mail.json
  - .env – BACKUP_PASSWORD (Pflicht, ≥ 16 Zeichen), CRON_SECRET, BACKUP_MAIL_TO, BACKUP_MAIL_INTERVAL_DAYS, SMTP_HOST/PORT/SECURE/USER/PASSWORD/FROM
probleme_loesungen:
  - datum: 2026-09-28
    was: „Migration bricht ab, wenn der Dump fehlschlägt“ – ohne Backup-Passwort kann kein Dump entstehen
    loesung: BACKUP_PASSWORD ist Pflichtwert der Konfiguration (≥ 16 Zeichen); fehlt er, meldet /health config fehlt und das Deployment schlägt sichtbar fehl, statt ohne Dump zu migrieren. Erstinstallation (Schemastand 0) braucht keinen Dump
  - datum: 2026-09-28
    was: Iterationszahl PBKDF2 und Hash waren nicht festgelegt (D-18 „dokumentierte Iterationszahl“)
    loesung: 200 000 Iterationen, SHA-256 (Encryptor::ITERATIONS); Befehl zum Entschlüsseln im README, in jeder Backup-Mail und im Code; Kompatibilität mit openssl 3.0 getestet
  - datum: 2026-09-28
    was: Lima-City-Cronjob kann nur URLs aufrufen (V-10), keine eigenen Header
    loesung: Secret als Query-Parameter (CRON_SECRET ≥ 32 Zeichen, Vergleich mit hash_equals); Endpunkt versendet nur nach Ablauf des Intervalls, damit ein täglicher Cronjob reicht
  - datum: 2026-09-28
    was: Wo speichern, wann zuletzt versendet wurde und ob ein Fehler auftrat (Anzeige „beim nächsten Login“)?
    loesung: Datei var/backup-mail.json statt Tabelle (keine Migration, überlebt Restore unabhängig); Fehler als Hinweis in Wochenansicht und Einstellungen
  - datum: 2026-09-28
    was: ext_cache ist im Umfang nicht als ausgenommen genannt
    loesung: nur Struktur gesichert (Cache-Daten sind nach 5 Minuten wertlos)
  - datum: 2026-09-28
    was: Schreibsperre – welche Schreibzugriffe?
    loesung: gesperrt sind alle fachlichen Schreibzugriffe (Einheit, Check-in, Schmerz, Zeitzone, Passwort); erlaubt bleiben Anmelden/Abmelden, OAuth, Backup-Download, Freigabe widerrufen und der Migrationsknopf selbst. MCP-Schreibtools prüfen die Sperre ab AP-05 (App::writeLocked)
```

## AP-11 Kalender (CalDAV, Nextcloud)

- **Ziel:** Alle Einheiten (außer Ruhetagen) erscheinen im Nextcloud-Kalender des Athleten – ein ganztägiger Sammeltermin je Trainingstag (D-60) – und bleiben mit Plan und Status aktuell (D-50).
- **Umfang:**
  1. CalDAV-Client: PUT/DELETE je Tag (`training-tag-<Datum>[-<Fassung>].ics`, Fassung in `app_setting` `kalender_tag_<Datum>`; vor D-60 je Einheit `training-session-<id>.ics`), REPORT calendar-query für einen Zeitraum; Basic-Auth mit App-Passwort; nur https.
  2. Termin-Aufbau (iCalendar): ganztägig, UID je Tag und Fassung (nach jedem Löschen neu, siehe Punkt 1 und D-60), Titel „Typ: Titel“ bzw. „Training: Titel 1 + Titel 2“ ohne Status-Markierung, Beschreibung je Einheit mit Kurzsatz, Kurzplan (Priorität, Dauer, Status), Begründung und Link, nie abgesagt (D-60; vorher nach D-50 Termin je Einheit mit „✓“ und STATUS:CANCELLED); Escaping und Zeilenfaltung nach RFC 5545.
  3. Übertragen bei jeder Änderung (MCP `write_week_plan`, `update_session`, Ersetzen; Rückmeldung auf der Webseite), jeweils der ganze betroffene Tag (beim Verschieben alter und neuer Tag); Fehler als `fehler_kalender` in der Tool-Antwort, im Audit-Log und in den Einstellungen.
  4. Abgleich 7 Tage zurück bis 8 Wochen voraus im stündlichen Cronjob `/cron/intervals-sync` und per Knopf in S8; verwaiste eigene Termine (auch alte Einzeltermine je Einheit) löschen, fremde nie.
  5. Konfiguration `CALDAV_URL`, `CALDAV_USER`, `CALDAV_PASSWORD` (optional; ohne sie aus), Anzeige in S8 und `/health`.
  6. Erinnerung (D-52): VALARM am Trainingstag zur eingestellten Uhrzeit (Standard 05:00, abschaltbar), einmal je Tag, solange eine Einheit geplant oder verschoben ist (D-60); Einstellung in S8, Tabelle `app_setting`.
  7. Sammeltermin je Tag (D-60, Code-Stand 0.19.0): Unterpunkt T8 in `docs/konzept/gefuehrte-einheit.md`.
- **Abhängigkeiten:** AP-05, AP-09 (Cronjob).
- **Abnahmekriterien:** Nach einem Wochenplan aus Claude stehen die Einheiten im Nextcloud-Kalender (Web und Handy); Statusänderung und Verschieben werden sichtbar; Abgleich-Knopf meldet Anzahl.
- **Status:**
```yaml
status: in_arbeit
begonnen: 2026-09-28
abgeschlossen: null
probleme_loesungen:
  - datum: 2026-09-28
    was: Kein Nextcloud in der Code-Umgebung
    loesung: Tests mit simuliertem CalDAV-Server (PUT/DELETE/REPORT mit Zeitraum); zusätzlich Rauchtest gegen einen echten CalDAV-Server (Radicale 3.8, lokal): Anlegen, Ersetzen, Zeitraum-Abfrage, Löschen und iCalendar-Prüfung ok. Nextcloud (Sabre/DAV) prüft der Athlet
  - datum: 2026-09-28
    was: Ungültige CALDAV_URL (http) darf die Schreib-Tools nicht lahmlegen
    loesung: Kalender bleibt dann aus; Hinweis in den Einstellungen, /health meldet ungueltig_kein_https
  - datum: 2026-09-28
    was: Welche Termine darf der Abgleich löschen?
    loesung: nur Ressourcen mit dem Namensmuster training-session-<id>.ics im Zeitraum, deren Einheit fehlt oder ein Ruhetag ist; fremde Termine bleiben
  - datum: 2026-09-28
    was: Viele Fehler bei ausgefallenem Kalender (eine Meldung je Einheit)
    loesung: je Tool-Aufruf nur die erste Fehlermeldung, weitere Versuche entfallen; der stündliche Abgleich holt alles nach
  - datum: 2026-09-28
    was: Erinnerung bei ganztägigen Terminen – absolute Uhrzeit oder relativ?
    loesung: TRIGGER;RELATED=START relativ zum Tagesbeginn (PT5H = 05:00 in der Zeitzone des Kalenders); so zeigen Nextcloud und Handy-Kalender „am Tag um 05:00“ ohne Zeitzonen-Umrechnung. Mit Radicale geprüft
  - datum: 2026-09-28
    was: Wo die Uhrzeit speichern? (bisher keine Einstellungstabelle; rollbackLastMigration in den Tests erwartet eine neue Tabelle je Migration)
    loesung: neue Tabelle app_setting (Schlüssel/Wert) statt Spalte in user; wiederverwendbar für weitere Einstellungen; fehlt die Tabelle (Schema alt), gilt der Standard
  - datum: 2026-09-28
    was: D-60 – bestehende Einzeltermine je Einheit im Kalender des Athleten
    loesung: der Abgleich behandelt training-session-<id>.ics weiterhin als eigene Termine und löscht sie im Zeitraum (7 Tage zurück bis 8 Wochen voraus), auch wenn die Einheit noch besteht; bis zum nächsten stündlichen Abgleich (oder Knopf in S8) kann ein Tag doppelt erscheinen. Ändert die App eine Einheit (Rückmeldung, update_session, Ersetzen), löscht sie deren Einzeltermin sofort – auch außerhalb des Zeitraums (Review T8). Ältere Einzeltermine unberührter Einheiten bleiben als Verlauf
  - datum: 2026-09-28
    was: Review T8 – Nextcloud bis 34.0.1 hält gelöschte Termine im Papierkorb und lehnt das erneute Anlegen derselben Adresse bzw. UID ab (HTTP 403); ein Tag, der mehrfach leer und wieder belegt wird, hätte den Abgleich blockiert
    loesung: Fassung je Tag in app_setting (kalender_tag_<Datum>), erhöht nach jedem tatsächlichen Löschen; Name und UID tragen die Fassung (training-tag-<Datum>-<n>.ics), eine gelöschte Adresse wird nie wieder verwendet. Einträge älter als 60 Tage vor dem Abgleichzeitraum werden entfernt. Hinweis bei HTTP 403 nennt zusätzlich den Papierkorb
  - datum: 2026-09-28
    was: D-60 – Welche Tage schreiben die Tools neu?
    loesung: write_week_plan die Tage der neuen und der ersetzten Einheiten, update_session alten und neuen Tag, die Rückmeldung auf der Webseite den Tag der Einheit; der Termin wird aus den Einheiten des Tages in der Datenbank neu gebildet, ein Tag ohne Einheiten (oder nur Ruhetag) verliert ihn. Fehler im Audit-Log jetzt mit Bezug auf den Tag (kalender_tag)
```

## AP-12 Morgen-Check-in mit Morgentest

- **Ziel:** Täglicher Morgen-Check-in mit standardisiertem Provokationstest der Patellasehnen, zu einer Ampel verdichtet und für Claude abrufbar (D-53).
- **Umfang:** Auftrag, Unterpunkte T1–T6, Testfälle und Entscheidungen in `docs/konzept/morgen-checkin.md` (dort fortgeschrieben).
- **Abhängigkeiten:** AP-04, AP-05, AP-11 (Einstellungstabelle `app_setting`).
- **Abnahmekriterien:** siehe T1–T6 im Auftragsdokument; Abnahme durch den Athleten auf dem Smartphone und über den Claude-Connector.
- **Status:**
```yaml
status: in_arbeit
begonnen: 2026-09-28
abgeschlossen: null
teilpakete: T1–T6 umgesetzt (Code-Stand 0.16.0, Schema 21), Abnahme offen – Details in docs/konzept/morgen-checkin.md Abschnitt 12
probleme_loesungen:
  - datum: 2026-09-28
    was: Auftrag nennt ein „bestehendes Morgen-Briefing“, das es im Repo nicht gibt
    loesung: Rückfrage beim Athleten – er öffnet morgens die Webseite; Zusammenfassung in der Karte der Startseite (E-12)
  - datum: 2026-09-28
    was: T3 (≤ 3 Taps) und Pflichtfelder Erholung/Muskelkater widersprechen sich
    loesung: Athlet entscheidet – Pflicht bleibt (E-11)
```

## AP-13 App-Icon und Begründungstexte

- **Ziel:** Das App-Logo erscheint beim Verknüpfen auf dem Startbildschirm in allen Browsern (Android, iOS, Desktop) und wechselt auf das ganze Lama (D-55); die Planung liefert je Woche und Einheit einen Kurzsatz und eine Begründung, sichtbar mit „mehr“ (D-56).
- **Umfang:** Auftrag `docs/konzept/gefuehrte-einheit.md`, Teile A und B, Unterpunkte T1 (Icon, Variante D-59) und T2 (Begründungstexte); Mockups `icon-optionen.html`, S2/S3 angepasst.
- **Abhängigkeiten:** AP-04, AP-05, AP-11 (Kalenderbeschreibung).
- **Abnahmekriterien:** Prüfschritte P-A1 bis P-A8 des Auftrags (Lama-Icon in Chrome, LibreWolf und IronFox, gerundetes Favicon im Tab, von `/login` und `/woche`); Plan aus dem Projekt-Chat mit Kurzsatz und Begründung erscheint in S2 und S3, „mehr“ klappt ohne JavaScript auf.
- **Status:**
```yaml
status: in_arbeit
begonnen: 2026-09-28
abgeschlossen: null
teilpakete: T1 und T2 umgesetzt (Code-Stand 0.17.0, Schema 22; Icon-Pfad korrigiert in 0.20.1, Favicon gerundet in 0.20.2 nach D-63), Abnahme durch den Athleten offen – Details in docs/konzept/gefuehrte-einheit.md Abschnitt 12
probleme_loesungen:
  - datum: 2026-09-28
    was: Icon-Erzeugung als PHP-Skript nicht möglich (kein SVG-Renderer auf Server und in PHP)
    loesung: Playwright-Skript docs/branding/build-icons.cjs, Ergebnis eingecheckt (Alternative aus Auftrag 4.2 Punkt 7)
  - datum: 2026-09-28
    was: Motivwechsel bei gleichbleibenden Icon-Dateinamen bliebe in Browser-Caches hängen
    loesung: neue Dateinamen lama-*.png, alte icon-*.png entfernt; Icons 7 Tage im Cache
  - datum: 2026-09-28
    was: Überlange Begründungen aus der Zeit vor AP-13 hätten update_session blockiert (Prüfung der zusammengeführten Einheit)
    loesung: nur übergebene Texte werden geprüft; Kurzsatz-Pflicht nur in write_week_plan
  - datum: 2026-09-28
    was: App-Icons unter /icons/ wurden nie ausgeliefert – der Hoster hat den serverweiten Apache-Alias /icons/ aktiv (vor Document Root und .htaccess); IronFox zeigte beim Verknüpfen ein „T“
    loesung: Ordner public/app-icons/, Verweise angepasst, Test gegen Apache-Aliase, Prüfschritt P-A7 (Code-Stand 0.20.1; Auftrag 4.1 Punkt 4)
  - datum: 2026-09-28
    was: Nach 0.20.1 zeigte der Browser-Tab ein eckiges V3 statt des vorgesehenen V2-Favicons – Firefox wählte die PNG-Links (V3) statt SVG/ICO (V2); der Athlet wünscht abgerundete Ecken
    loesung: Rückfrage mit gerenderter Vorschau (Rundung 0/12/22/50 %, V3 und V2); Entscheidung D-63: V3 mit 22 % Rundung für alle Tab-Favicons, Startbildschirm-Icons eckig (Code-Stand 0.20.2)
  - datum: 2026-09-28
    was: Beim Zusammenführen mit main war D-62 inzwischen für die Hypertrophie-Literatur (AP-06 Teil D) vergeben
    loesung: Favicon-Entscheidung als D-63 geführt, alle Verweise (Konzept, Auftrag E-24, Branding B-10, Code-Kommentare, Changelog, Prüfprotokoll) angepasst
```

## AP-14 Geführte Einheit

- **Ziel:** Eine Einheit lässt sich starten und Schritt für Schritt durchführen, mit Timer, Farbwechsel, Signalen und direkter Ist-Eingabe (D-57, D-58).
- **Umfang:** Auftrag `docs/konzept/gefuehrte-einheit.md`, Teil C, Unterpunkte T3 (Ablaufplan), T4 (S9 ohne Skript), T5 (Skript: Timer, Signale, Farbe, Zustand), T6 (Einstellungen, Offline, Prüfprotokoll), T7 (Dokumentation); Nachtrag T9 (E-23: Kletterblöcke ohne Haltezeit und Dauer mit mindestens zwei Sätzen satzweise wie Kraft, Speichern-Leisten auf dem Smartphone über der unteren Navigation); Mockup `s9-einheit-gefuehrt.html` (fünf Zustände), S8 angepasst.
- **Abhängigkeiten:** AP-13 T2 (Kurzsatz im Startschritt), AP-09 (Offline), AP-11 (`app_setting`).
- **Abnahmekriterien:** Testfälle 8.1 und 8.2 des Auftrags grün; Gerätetest des Athleten auf Android (Töne, Vibration, Grün/Rot, Bildschirm an, Stumm in S8 und in der Einheit); ohne JavaScript vollständig ausfüllbar; Speichern offline landet im Puffer; T9: Zugkraft-Block mit Pause wird satzweise mit Pausentimer geführt, Speichern bleibt beim Scrollen auf dem Smartphone sichtbar.
- **Status:**
```yaml
status: in_arbeit
begonnen: 2026-09-28
abgeschlossen: null
teilpakete: T3 bis T7 umgesetzt (Code-Stand 0.18.0), Nachtrag T9 zu den offenen Punkten O-05 bis O-07 (Code-Stand 0.20.0), Abnahme durch den Athleten offen (Gerätetest Android, Flugmodus) – Details in docs/konzept/gefuehrte-einheit.md Abschnitt 12
probleme_loesungen:
  - datum: 2026-09-28
    was: Haltebereiche mit Halbgeviertstrich („30–45 s“) und rest_s = 0 sind in 6.3 nicht geregelt
    loesung: „–“ wie „-“; 0 = keine Pause bzw. kein Timer (Auftrag Abschnitt 12, T3)
  - datum: 2026-09-28
    was: 6.2/Z-11 „duration_min wird, wenn leer, mit der gemessenen Dauer vorbelegt“ – S3 belegt die Dauer mit der geplanten vor, das Feld ist nie leer
    loesung: ohne JavaScript wie S3; das Skript ersetzt die Dauer nur, wenn sie aus dem Plan stammt, nie eine gespeicherte oder geänderte (Auftrag Abschnitt 12, T4)
  - datum: 2026-09-28
    was: Fehler beim Speichern aus S9 (422/409) hätten S3 gezeigt
    loesung: verstecktes Feld modus=start im S9-Formular, Speicherweg bleibt POST /einheit (Auftrag Abschnitt 12, T4)
  - datum: 2026-09-28
    was: Kletterblöcke mit Sätzen und Pause ohne Haltezeit sind nach 6.3 „offen“ (kein Pausentimer)
    loesung: Sätze werden übernommen und angezeigt; Pausentimer für solche Blöcke als O-07 zur Entscheidung des Athleten – entschieden (E-23): satzweise wie Kraft mit Pausentimer, umgesetzt in T9
  - datum: 2026-09-28
    was: E-17 („30-s-Ton bei Phasen ≥ 45 s“) widerspricht Testfall Z-01 („bei 45 s kein 30-s-Ton“)
    loesung: umgesetzt nach Z-01 (Phase länger als 45 s); vom Athleten bestätigt (E-23), E-17 und 6.4 angeglichen
  - datum: 2026-09-28
    was: Review T5 – Tipps direkt nach einem automatischen Phasenwechsel, Zurück-Navigation, Dauermessung im Abschluss, Stumm (Blinken, Wahl vor der ersten Eingabe, Name des Schalters), Fokus, Ist-Fehler nach 422, Browser-Test und CI
    loesung: Tipp-Sperre (verworfen, wenn die angezeigte Phase inzwischen endete oder < 500 ms nach einem automatischen Wechsel), erledigte Übungen bleiben beim Zurück erledigt, Messung endet mit dem Abschluss, Blinken nur in den letzten 3 s, Ist-Fehler öffnen die Übung; run.sh mit freiem Port, CI-Zeitlimit – Einzelheiten im Auftrag Abschnitt 12, T5
  - datum: 2026-09-28
    was: Abschluss-Review (T6 und Dokumente) – offline gezeigte S9 übernahm eine seit dem Vorladen geänderte Timer-Einstellung nicht; Untertext 6.6, Deploy-Hinweise und Versionsangaben im Konzept uneinheitlich
    loesung: timer_ton zusätzlich auf dem Gerät gemerkt (localStorage) und offline bevorzugt; Dokumente angeglichen – Einzelheiten im Auftrag Abschnitt 12, T6
  - datum: 2026-09-28
    was: Nachtrag T9 – Entscheidungen zu O-05 bis O-07 und fixierte Speichern-Leisten hinter der unteren Navigation (S3, Check-in, Schmerz)
    loesung: E-23 im Auftrag; Kletterblöcke mit Sätzen satzweise wie Kraft; .actions-sticky auf dem Smartphone über der Navigation (alle Seiten, Rückfrage beim Athleten)
  - datum: 2026-09-28
    was: Review T9 – letzter Satz kündigte eine Pause an, die nicht kommt; auf iPhones doppelter Abstand zur Safe Area unter der Speichern-Leiste; in der Woche überdeckte die Check-in-Leiste die Kacheln
    loesung: Pausenhinweis nur, wenn noch ein Satz folgt; Leiste unten nur mit normalem Innenabstand (die Navigation hält die Safe Area frei); Abstand unter dem eingebetteten Check-in (Einzelheiten Auftrag Abschnitt 12, T9)
```

## AP-15 Blockbilanz, Zielklärung und Übergabe

- **Ziel:** Die planende Instanz kennt zu Beginn jeder Planungssitzung die bisherigen Ziele, ihre Begründung, verworfene Alternativen, Ergebnisse und Fälligkeiten – aus der Datenbank, deterministisch zusammengestellt (D-72 bis D-78).
- **Umfang:** Auftrag `docs/konzept/blockbilanz.md`, Unterpunkte T1 (Tabelle `block_review`, Schemata je Art, Kennzahlen, Fälligkeit), T2 (MCP-Tools `get_handover`, `get_block_reviews`, `write_block_review`; Erweiterungen `get_block`, `upsert_block`, `write_week_plan`), T3 (Overlay-Erinnerung, Karte „Block“ in S2, Einstellungen), T4 (Kalendertermin je Block), T5 (Blockseite S11, Abschnitt „Blöcke“ in S6), T6 (Dokumentation, Trainerregeln Kapitel 10, Betriebsablauf).
- **Abhängigkeiten:** AP-05 (Schreibtools), AP-09 (Verlauf S6, Spiegel für Kennzahlen, Offline), AP-11 (Kalender, `app_setting`); AP-08 nutzt das Schema der Zielklärung für die erste Zielklärung.
- **Abnahmekriterien:** Testfälle 11.1 bis 11.4 des Auftrags grün; aus dem Projekt-Chat liefert `get_handover` Block, Zielklärung, Bilanz und Fälligkeiten in ≤ 8 000 Zeichen und `write_block_review` legt eine Fassung mit Kennzahlen an; auf dem Smartphone erscheint das Overlay bei fälliger Bilanz, verschwindet nach „Morgen wieder erinnern“ bis zum nächsten Tag und kommt dann wieder; im Nextcloud-Kalender steht der Termin am Blockende 08:00–10:00 mit Erinnerung am Vortag; Sichtprüfung S11.
- **Status:**
```yaml
status: in_arbeit
begonnen: 2026-09-29
abgeschlossen: null
teilpakete: T1 (Code-Stand 0.26.0, Schema 24), T2 (0.27.0), T3 (0.28.0), T4 (0.29.0), T5 (0.30.0) und T6 (Dokumentation, 0.30.1) umgesetzt, Nachtrag E-23 (0.30.2); Abnahme durch den Athleten offen (Projekt-Chat, Smartphone, Nextcloud-Kalender) – Details in docs/konzept/blockbilanz.md Abschnitt 13
probleme_loesungen:
  - datum: 2026-09-29
    was: O-04 und O-05 des Auftrags offen
    loesung: vom Athleten entschieden – wochen_kurz in der Übergabe (E-21, D-78), Uhrzeit, Dauer und Erinnerung des Blocktermins sofort in S8 einstellbar (E-22, D-75)
  - datum: 2026-09-29
    was: T1 – Dauererinnerung für alte Blöcke ohne Bilanz, Bezugsblock der Zielklärung, Grenzfälle 28 Tage/16 Wochen, Kennzahlen der Revision, Schmerztrend
    loesung: Einzelheiten im Auftrag Abschnitt 13, T1
  - datum: 2026-09-29
    was: T2 – Budget der Übergabe bei vollen Listen, Zeitraum der Revision, Quittierungen des Folgeblocks, zielklaerung_fehlt, Reihenfolge der Blockwechsel-Prüfung
    loesung: Einzelheiten im Auftrag Abschnitt 13, T2
  - datum: 2026-09-29
    was: T3 – Bedeutung des Quittierungsdatums, zwei Knöpfe statt Radio, Browser-Tests vom Overlay verdeckt, Overlay auch in S9, Schreibsperre
    loesung: Einzelheiten im Auftrag Abschnitt 13, T3
  - datum: 2026-09-29
    was: T4 – Rechenfehler in K-B2 (08:00 MESZ = 06:00Z), Folgeblock-Regel des Termins, Zählung im Abgleich, Suchfenster für verwaiste Termine
    loesung: Einzelheiten im Auftrag Abschnitt 13, T4
  - datum: 2026-09-29
    was: T5 – breite Tabellen bei 375 px, Navigationsbereich von S11
    loesung: Einzelheiten im Auftrag Abschnitt 13, T5
  - datum: 2026-09-29
    was: Overlay unterbrach die geführte Einheit (S9)
    loesung: Entscheidung des Athleten (E-23, 0.30.2) – kein Overlay in S9 und S10 aus S9, unmittelbar nach dem Abschluss auf S2
  - datum: 2026-09-29
    was: D-70/D-71 wurden parallel für die Literatur-Nachsteuerung vergeben (PR #23)
    loesung: Entscheidungen von AP-15 auf D-72 bis D-78 umnummeriert (Konzept, Auftrag, Dokumente)
  - datum: 2026-09-29
    was: T6 – Präfix R-UEB in Kapitel 9 (Übungskatalog, 10–14) und Kapitel 10 (Übergabe, 01–06)
    loesung: Nummern überschneiden sich nicht; Wortlaut und IDs wie im Auftrag übernommen, Hinweis im Kapitel
```

## AP-16 Übungskatalog

- **Ziel:** Übungen stehen einmal mit Ausführung, Achtungspunkten, Fehlerquellen, Vorsicht, Progression, Links und Videos in der Datenbank; Einheiten verlinken sie über `exercise_id` und beschreiben nur die Dosierung (D-64 bis D-69).
- **Umfang:** Auftrag `docs/konzept/uebungskatalog.md`, Unterpunkte T1 (Datenmodell, Schemata, Validator), T2 (MCP-Tools `find_exercise`, `get_exercise`, `list_exercises`, `upsert_exercise`, Linkprüfung, Warnungen in `write_week_plan`/`update_session`), T3 (Webseite S10/S10a, CSP), T4 (Verlinkung aus S3, S9, Kalender, Offline), T5 (wöchentliche Linkprüfung im Cron), T6 (Dokumentation, Trainerregeln Kapitel „Übungskatalog“).
- **Abhängigkeiten:** AP-03 (Schemata), AP-05 (Schreibtools), AP-09 (Offline), AP-11 (Kalenderbeschreibung, Cron), AP-14 (S9).
- **Abnahmekriterien:** Testfälle 11.1 bis 11.3 des Auftrags grün; aus dem Projekt-Chat `find_exercise` → `upsert_exercise` mit geprüften Links und `hinweis_chat`; Sichtprüfung S10 auf dem Smartphone (Video eingebettet, Vorsicht sichtbar); ohne Netz Einheit → Übung lesbar mit Platzhalter statt Video; Cron-Lauf mit Linkprüfung im Audit.
- **Status:**
```yaml
status: in_arbeit
begonnen: 2026-09-29
abgeschlossen: null
teilpakete: T1 (Code-Stand 0.21.0, Schema 23), T2 (0.22.0), T3 (0.23.0), T4 (0.24.0), T5 (0.25.0) und T6 (Dokumentation, 0.25.1) umgesetzt; Abnahme durch den Athleten offen (Projekt-Chat, Smartphone, Cron auf dem Server, O-05) – Details in docs/konzept/uebungskatalog.md Abschnitt 13
probleme_loesungen:
  - datum: 2026-09-29
    was: Abgleich des Auftrags mit dem Code – YouTube-Einbettung scheitert an Referrer-Policy same-origin, Linkprüfung per GET erkennt gelöschte Videos nicht, Server-Abruf beliebiger URLs (SSRF), normalisierter Name ohne eigene Spalte
    loesung: E-17 bis E-20 im Auftrag (Referrer am iframe, oEmbed, Schutzregeln der Linkprüfung, name_norm), mit dem Konzept vom Athleten bestätigt
  - datum: 2026-09-29
    was: T1 – eine statt zwei Migrationen, Linkzahl-Regel mit Opis, Länge der normalisierten Spalten, Normalisierung ohne intl, Ähnlichkeit nach T-08, Pflichtfelder content_json
    loesung: Einzelheiten im Auftrag Abschnitt 13, T1
  - datum: 2026-09-29
    was: T2 – Ergebniscodes bei curl_multi, Live-Linkprüfung in der Code-Umgebung gesperrt, Format der Warnungen und Budget (find/get/list), Bewertung einzelner HTTP-Antworten, Fassungen bei unverändertem Inhalt, Schleifen in variant_of
    loesung: Einzelheiten im Auftrag Abschnitt 13, T2
  - datum: 2026-09-29
    was: T3 – frame-src nur für S10, fehlende Icons, S8 vor der Migration, Anzeige der Fassungen, Navigation von S10/S10a
    loesung: Einzelheiten im Auftrag Abschnitt 13, T3
  - datum: 2026-09-29
    was: T4 – Rückkehr aus S10 in S9 ohne Rückfrage, Cache-Adressen der Übungsseiten, Längenregel der Kalenderbeschreibung, Name der Kletterblöcke
    loesung: Einzelheiten im Auftrag Abschnitt 13, T4
  - datum: 2026-09-29
    was: T5 – Linkprüfung im Spiegel-Cron ohne Rückwirkung auf dessen Antwort, Reihenfolge bei mehr als 50 Links, Zählung nicht prüfbarer Links, Schreibsperre
    loesung: Einzelheiten im Auftrag Abschnitt 13, T5
  - datum: 2026-09-29
    was: T6 – Kapitel 8 in Abschnitt 14 ist schon vergeben; docs/regeln/trainerregeln.md fehlte noch
    loesung: Kapitel 9 „Übungskatalog“; trainerregeln.md mit Vorabkapitel 9 angelegt (Kapitel 1–8 bleiben AP-07)
```

# 16. Prüfprotokoll (separates Dokument)

Datei: `docs/pruefung/pruefprotokoll.md`. Struktur je AP:

```yaml
ap: AP-xx
geprueft:
  - was: ...
    wie: ...        # manuell / automatisiert / Gerätetest
    ergebnis: ok | fehler | offen
    datum: ...
noch_zu_pruefen:
  - was: ...
    wie: ...
```

# 17. Änderungsprotokoll des Konzepts

| datum | aenderung |
|---|---|
| 2026-09-27 | Erstfassung nach Klärung von Architektur (D-01…D-15), Garmin-Weg, Hosting, OAuth, Check-in (Q-01 offen). |
| 2026-09-27 | Bestätigung durch Athlet (D-01…D-15, AP-Struktur). Q-01 → D-16 (tägliches Check-in). Neu: D-17 Deployment GitHub → FTPS/SFTP, D-18 einfaches Backup, D-19 Branding-Dokument im Repo; V-10; AP-00, AP-03, AP-04, AP-09 entsprechend erweitert. Status: bestätigt. |
| 2026-09-27 | D-18 neu gefasst: Backup nur DB, verschlüsselt (OpenSSL-kompatibel), manuell/per E-Mail/vor Migrationen. Neu: D-20 Update-Mechanik (Migrationen, `schema_version`, Schreibsperre bei Abweichung, Pre-Migration-Dump). Neu: AP-10 Backup und Update-Mechanik (Reihenfolge vor AP-05). AP-00, AP-03, AP-04, AP-09, V-10, Abschnitt 12 angepasst. |
| 2026-09-27 | Q-06 beantwortet: keine Literatur vorhanden; AP-06 startet bei null. |
| 2026-09-27 | 13.1 ergänzt: Bündelungsregel (4–6 Sammeldateien), Tokenbudget für Projektwissen, fünfstufiger Erstellungsprozess für Wissenskarten. |
| 2026-09-27 | 13.2 umgebaut (ID-Konvention, Statuswerte, Blöcke je Bereich); Literaturblock „übergreifend – Allgemeine Trainingslehre" eingearbeitet (L-A01–L-A03, L-P01–L-P09, unverifizierte Alt-Kandidaten als L-P10–L-P13); 13.3 „bewusst nicht aufgenommen" neu. Neue Entscheidungen D-21 (Sprache), D-22 (Evidenzhierarchie), D-23 (Repo privat), D-24 (Zitierfassung). PubMed-Verifikation aller Paper dokumentiert (PMID/DOI ergänzt, L-P09 neu). AP-06 auf `in_arbeit`; Kartenzuschnitt und offene Punkte aufgenommen. Nebenbefund Cowley 2026 als L-T2P01 (seit Umstellung der Konvention: L-T2-14). |
| 2026-09-27 | Literaturblöcke T1, T2, T3 eingearbeitet, ID-Kollisionen dreier paralleler Sitzungen aufgelöst (Renummerierung: T1 → D-25–D-27, V-11–V-12; T2 → D-28–D-30, V-13–V-14; T3 → V-15, Q-07, Q-08). ID-Konvention der Bereiche vereinfacht auf `L-T<n>-<nn>` mit Feld `typ`; Feld `stufe` (A/B/C) und Statuswerte `vorgeschlagen`, `zurueckgestellt`, `verweis` eingeführt. 13.2.2 T1 (L-T1-01–14), 13.2.3 T2 (L-T2-01–14, L-A03 ausgewählt), 13.2.4 T3 (L-T3-01–17) angelegt; 13.3 erweitert; 13.4 Beschaffungsliste neu. Querverweise: 7.1 `spezifitaet`, 9 Zonen %LTHR, 14 Kap. 8 Zonenmodell, AP-02 V-12, AP-08 Tests. AP-06 Teilschritte und Probleme/Lösungen konsolidiert. Regel: Literatur-Sitzungen editieren das Konzept nicht direkt. |
| 2026-09-27 | Q-07 und Q-08 entschieden → D-31 (Evidenzschema A/B/C, Stufe-C-Regel, Volltexte nur im Repo, Evidenzkern T3, Klettermedizin 2022 bevorzugt). T3-Einträge auf `ausgewaehlt`/`optional` gesetzt; 13.4 und AP-06 angepasst. |
| 2026-09-27 | Vorbereitung AP-00: Q-04 entschieden (Repo `chodid/training`, privat, keine Lizenz); Subdomain `training.gen-em.org`; V-08 und V-10 teilweise geklärt (PHP 8.4, FTPS, SMTP, zeitgesteuerter URL-Aufruf statt PHP-CLI). D-31 geändert: auch gekaufte PDFs dürfen im privaten Repo unter `docs/literatur/` liegen (nie im Projektwissen); D-23 und 13.1 angeglichen. |
| 2026-09-27 | Servertest AP-00: Hosting ist Lima-City, nicht Plesk → alle Plesk-Bezüge ersetzt (1.4, K3, D-03, D-17, D-18, V-08, V-10, 12, AP-00, AP-10). Layout in D-17 festgeschrieben (Docroot `public/`, `.env` und `backups/` im Subdomain-Ordner). D-20 präzisiert (MySQL-DDL ohne Transaktion, `schema_version` je Migration). 12.1a neu (`open_basedir` leer). V-08 erledigt, V-10 bis auf Anhang-Limit erledigt. FTP-Ordner heißt `/training.jennym.org`, Subdomain ist `training.gen-em.org`. |
| 2026-09-27 | AP-00 umgesetzt (Code-Stand 0.1.0), Status `in_arbeit` bis zur Abnahme auf dem Server; Probleme/Lösungen im AP-00-Block. |
| 2026-09-27 | AP-00 Korrektur (Code-Stand 0.1.1): FTP-Benutzer ist auf den Subdomain-Ordner beschränkt, `FTP_SERVER_DIR` = `/`. |
| 2026-09-27 | AP-00 abgenommen (Prüfprotokoll), Status `erledigt`. |
| 2026-09-27 | Vorbereitung AP-01 (Befund SDK v2.0.1 und Entscheidungen des Athleten). D-04 präzisiert: SDK liefert nur die Resource-Server-Seite, Autorisierungsserver wird selbst gebaut. Neu: D-32 Token-Format (JWT HS256 1 h, Refresh-Rotation mit Familien-Widerruf), D-33 Login Passwort + 30-Tage-Session in `web_session` (Q-05 entschieden), D-34 Erstanlage über `/setup` mit `MIGRATION_SECRET`, D-35 Auth-Tabellen in AP-01 vorgezogen, D-36 offene DCR mit Freigabeseite/PKCE S256/Redirect-Regeln, D-37 Design-Mockups vor AP-01. D-06 konkretisiert (`MCP_STATIC_TOKEN`, `MCP_STATIC_TOKEN_ENABLED`), D-17 um `var/` ergänzt, D-19 auf alle Screens ab AP-01 und Tablet erweitert. Abschnitt 0 Rollenverteilung (Fable/Code-Instanz); 3.3, 7 (`web_session`, `user.failed_logins/locked_until`, `oauth_token` nur Refresh), 8.1, 10 (S0, S7, Tablet), 12 (2, 3, 3a) angepasst. Neues Vorpaket AP-01a Design-Mockups als eigenes AP statt Voraussetzung in AP-01, weil es ein anderes Modell bearbeitet und der Athlet es separat abnimmt (eigener Statusblock). AP-01 Umfang/Abnahme erweitert, AP-03 nur noch Trainingstabellen, AP-04/AP-09/AP-10 Querverweise. |
| 2026-09-27 | D-33 Sperrwerte vom Athleten bestätigt: Sperre nach 10 Fehlversuchen für 5 min, Verdopplung bei weiteren Fehlversuchen bis max. 24 h, Rücksetzung des Zählers bei erfolgreichem Login (ersetzt die Richtwerte 5 Versuche / 15 min). Angepasst: D-33, Abschnitt 7 (`user`), 12.3a, AP-01 Abnahmekriterien. |
| 2026-09-27 | Abschnitt 0 präzisiert (Rücksprache mit dem Athleten): Konzepterstellung durch Fable; Code-Instanz darf bestehende Konzepte in Rücksprache ändern und ergänzen. |
| 2026-09-27 | AP-01a begonnen: Gestaltungsvorgaben (Chadid Design-System) unter `docs/branding/chadid-design-system/` abgelegt; Status AP-01a `in_arbeit`. |
| 2026-09-27 | AP-01a: Mockups aller Screens (S0–S7 und Einstellungen) sowie Branding-Dokument `docs/branding/branding.md` erstellt; Entscheidungen B-01 bis B-07 dort dokumentiert (u. a. Desktop-Ansicht zusätzlich zu Smartphone/Tablet). Abnahme offen. |
| 2026-09-27 | AP-01a abgenommen (Athlet). D-19 um Desktop-Ansicht ergänzt; Abschnitt 10 um S8 Einstellungen ergänzt (AP-04, Backup/Update aus AP-10). AP-01a `erledigt`. |
| 2026-09-27 | AP-01 umgesetzt (Code-Stand 0.2.0), Status `in_arbeit` bis zur Abnahme auf dem Server und mit claude.ai. Befunde im AP-01-Block (SDK-Einbindung über HttpServerRunner, Sitzungsdateien nur mit gültigem Token, Scope-Namen, Client-Authentifizierung `none`, Assets-Build, CSP). Neu: Q-09 Laufzeit Refresh-Token (vorläufig 90 Tage). 8.1 um Scopes und Pfad-Suffix-Metadaten ergänzt; V-05 in Arbeit. |
| 2026-09-27 | AP-02 umgesetzt (Code-Stand 0.3.0), Status `in_arbeit`: Intervals.icu-Client und Verbindungstest `/intervals`. V-04 vorläufig aus Sekundärquelle (intervals.icu aus der Code-Umgebung nicht erreichbar); Befunde im AP-02-Block. |
| 2026-09-27 | AP-03 umgesetzt (Code-Stand 0.4.0), Status `in_arbeit` bis CI gegen MySQL 8.4 und Migration auf dem Server. Neu: `docs/konzept/datenmodell.md` (ER-Diagramm), Q-10 (plan_json für mobilitaet/ruhe, vorläufig umgesetzt), Verweise in Abschnitt 7 und 7.1. |
| 2026-09-28 | Q-09 → D-38 (Refresh-Token 90 Tage) und Q-10 → D-39 (`plan_json` für mobilitaet/ruhe) vom Athleten bestätigt; Umsetzung unverändert. Auslieferung: AP-01 bis AP-03 gemeinsam in einem Pull Request (Entscheidung Athlet). |
| 2026-09-28 | AP-04 umgesetzt (Code-Stand 0.5.0), Status `in_arbeit` bis zur Abnahme auf Smartphone/Tablet. Befunde im AP-04-Block (Dauerfeld in S3, Speichern bei ausgelassen/verschoben, performed_at, actual_json vollständig, Aktivitätszuordnung, vorläufige Schmerz-Hinweisregel, CSS statt JavaScript, Platzhalter für AP-09/AP-10). |
| 2026-09-28 | AP-10 umgesetzt (Code-Stand 0.6.0), Status `in_arbeit` bis Entschlüsseln beim Athleten, E-Mail-Test über Lima-City und MCP-Schreibsperre (AP-05). Befunde im AP-10-Block (BACKUP_PASSWORD Pflicht, 200 000 Iterationen, Cron-Secret im Query, Zustand in var/, Umfang der Sperre). |
| 2026-09-28 | AP-05 umgesetzt (Code-Stand 0.7.0), Status `in_arbeit`. Neu vorgeschlagen: Q-11 Tool `upsert_block` (8.2 ergänzt), Q-12 Feld `sport` in `plan_json.ausdauer` (7.1 ergänzt); Rechte je Tool über Scopes. Befunde im AP-05-Block. |
| 2026-09-28 | Q-11 → D-40 (Tool `upsert_block`) und Q-12 → D-41 (Feld `sport` im Ausdauerplan) vom Athleten bestätigt; Umsetzung unverändert. |
| 2026-09-28 | AP-09 entschieden (D-42): alle Optionen; Cron-Spiegel (D-43, ändert D-09), Passkey zusätzlich (D-44), Offline lesen + Eingaben puffern (D-45, ändert Abschnitt 10). AP-09 `in_arbeit` mit Teilpaketen. |
| 2026-09-28 | AP-09 Teil 1 umgesetzt (Code-Stand 0.8.0): JSON-Export, Verlauf S6. |
| 2026-09-28 | AP-09 Teil 2 umgesetzt (Code-Stand 0.9.0): Spiegel Intervals.icu → MySQL (D-43) mit read-through und Cron-Abgleich; CRON_SECRET statt BACKUP_CRON_SECRET. |
| 2026-09-28 | AP-09 Teil 3 umgesetzt (Code-Stand 0.10.0): Feedback-Rückschreiben nach Intervals.icu; Q-02 → D-46. |
| 2026-09-28 | AP-09 Teil 4 umgesetzt (Code-Stand 0.11.0): Passkey-Login zusätzlich zum Passwort (D-44); Befunde im AP-09-Block. |
| 2026-09-28 | Asymmetrische Backups gestrichen (D-47, ändert D-42/D-18). Athletenprofil als DB-Objekt entschieden (D-48, ersetzt D-15) und umgesetzt (AP-09 Teil 5, Code-Stand 0.12.0): Abschnitt 3.1/3.3, Ablauf Wochenplanung, 8.2 (`get_athlete_profile` neu, `update_athlete_profile`), Abschnitt 10 (Profilseite), AP-08 angepasst. |
| 2026-09-28 | AP-09 Teil 6 umgesetzt (Code-Stand 0.13.0): Offline-Fähigkeit (D-45) mit D-49 (Umfang, Abmelden, Konflikt); alle AP-09-Teilpakete umgesetzt, Abnahme durch Athlet offen. |
| 2026-09-28 | Deployment 0.13.0 auf training.gen-em.org (Merge PR #7), Schema 18. Prüfungen durch den Athleten eingetragen: Setup, Intervals.icu (GET, Event-POST), Claude-Connector (Web), Cron-Abgleich, Backup-Mail, Entschlüsselung, Passkey, Offline; V-04 und V-05 teilweise bestätigt. |
| 2026-09-28 | Konsistenz: Athletenprofil und K5 verwiesen fälschlich auf K2 statt K3; 3.3 beschreibt jetzt den Spiegel (D-43) statt „live“. |
| 2026-09-28 | Neu: AP-11 Kalender per CalDAV (D-50) auf Wunsch des Athleten, direkt umgesetzt (Code-Stand 0.14.0); K8 in 3.1/3.2/3.3 ergänzt. |
| 2026-09-28 | Literatur-Volltexte abgeglichen, umbenannt und in Blockordner sortiert (D-51); Bücher zusätzlich als Kapitel-PDFs. 13.1 Schritt 1, 13.2 (Felder `datei`/`kapitel`, L-A01 vorhandene Auflage, L-T3-03 Band und Lizenz), 13.4 Spalte „vorhanden“, V-13 teilweise, V-15 L-T3-03 erledigt, AP-06 Teilschritt Beschaffung teilweise. |
| 2026-09-28 | AP-11 ergänzt um Erinnerungen (D-52, Code-Stand 0.15.0, Schema 19: `app_setting`). Zunächst als D-51 vergeben; beim Zusammenführen mit der parallelen Literatur-Sitzung (PR #9, ebenfalls D-51) in D-52 umbenannt. |
| 2026-09-28 | L-A02 Ferrauti, 2. Aufl. 2025, ergänzt (Gesamtbuch ohne doppelt eingebettete Ressourcen, 94 MB, plus Kapitel-PDFs); Eintrag 13.2.1 vervollständigt (Herausgeber, Jahr, ISBN, DOI, Verlag), 13.4, V-13, AP-06 nachgezogen. |
| 2026-09-28 | Neu: AP-12 Morgen-Check-in (D-53, Auftrag `docs/konzept/morgen-checkin.md` aus dem Trainer-Chat) umgesetzt, Code-Stand 0.16.0, Schema 21; 7 (`checkin`), 7.2 (Schmerzorte), 8.2 (`get_morning_checks`), 10 (S4/S2) ergänzt. |
| 2026-09-28 | Übergaben AP-06 Teil B (Verifikation) und Teil A (Haltung/Rücken) eingearbeitet: 13.2.1 L-P10–L-P13 ausgewählt und verifiziert, L-P14 neu (optional); 13.2.3 L-T2-11/-12 ausgewählt, Teilblock Haltung/Rücken mit L-T2-15–L-T2-19, L-T2-13 zurückgestellt, L-T2-14 optional; 13.2.4 L-T3-02 Kernaussagen korrigiert, L-T3-03/-04/-05/-07/-08/-09/-12 bibliografisch ergänzt, L-T3-18 neu; 13.3 vier Ausschlüsse; 13.4 ergänzt. Neu D-54 (Haltung/Rücken, in der Übergabe D-38) und Q-13 (Schmerzschwellen, in der Übergabe Q-09); V-06 und V-14 erledigt, V-07 teilweise (AP-06/AP-07), V-15 weitgehend erledigt; 14.5 Verweis auf Q-13; AP-06 Umfang, Abnahme, Status; AP-07 Vorgaben aus AP-06. |
| 2026-09-28 | 13.4: L-T2-10 und L-T3-04 auf Wunsch des Athleten in die Beschaffungsliste aufgenommen (Prio 2); Haltung/Rücken nach Merge von AP-12 (D-53) als D-54 geführt. |
| 2026-09-28 | Neu (Fable, Konzeptentwurf, Bestätigung offen): Auftrag `docs/konzept/gefuehrte-einheit.md` mit Teil A App-Icon/Logo (D-55, Q-14 Logo-Variante), Teil B Begründungstexte je Woche/Einheit (D-56, `coach_summary`), Teil C geführte Einheit S9 (D-57, D-58); AP-13 und AP-14 angelegt; 7, 8.2, 10, 15 ergänzt. Mockups: `s9-einheit-gefuehrt.html`, `icon-optionen.html`, S2/S3/S8 angepasst, fünf Tabler-Icons ergänzt (Branding B-08, Abschnitt 8). |
| 2026-09-28 | Auftrag `gefuehrte-einheit.md` vom Athleten bestätigt (E-08 bis E-20 gelten); Q-14 → D-59 (V3 App-Icon, V2 Favicon und App-Kennung); AP-13 kann ohne Wartepunkt starten. |
| 2026-09-28 | Icon-Befund (Auftrag gefuehrte-einheit.md, E-05/O-03): Chrome auf Android zeigt das App-Icon; Fehler ist auf den Favicon-Weg von LibreWolf eingegrenzt, Manifest ausgeschlossen. |
| 2026-09-28 | AP-13 begonnen: T1 App-Icon und Logo umgesetzt (Code-Stand 0.17.0, D-59); AP-13 `in_arbeit`, Befunde im AP-13-Block und im Auftrag (Abschnitt 12). |
| 2026-09-28 | AP-13 T2 Begründungstexte umgesetzt (Code-Stand 0.17.0, Schema 22: `session.coach_summary`); 7, 8.2 und 10 entsprechen der Umsetzung (bereits mit D-56 eingetragen). AP-13 bleibt `in_arbeit` bis zur Abnahme durch den Athleten. |
| 2026-09-28 | AP-14 begonnen: T3 Ablaufplan umgesetzt (Code-Stand 0.18.0); AP-14 `in_arbeit`. |
| 2026-09-28 | AP-14 T4: Seite S9 ohne Skript (`/einheit?id=…&modus=start`, Template `session-start.php`), Startknopf in S3. |
| 2026-09-28 | AP-14 T5: Seitenskript `js/gefuehrt.js` (Timer, Signale, Farben, Wake Lock, Stumm, Fortschritt im Browser), Node- und Browser-Tests in der CI; offener Punkt O-06 (30-s-Ton) im Auftrag. |
| 2026-09-28 | AP-14 T6: Einstellungen → Training (Timer-Signale, `timer_ton`), Vorladen der geführten Einheit für heute und morgen, Skript im Versions-Cache. |
| 2026-09-28 | Neu: D-60 (ändert D-50, passt D-52 an) – ein Sammeltermin je Tag im Kalender, auf Wunsch des Athleten als Unterpunkt T8 des Auftrags `gefuehrte-einheit.md` umgesetzt (Code-Stand 0.19.0); AP-11 Umfang und Status, K8 in 3.1/3.3 nachgezogen. |
| 2026-09-28 | AP-14 T5: Befunde des Reviews eingearbeitet (Tipp-Sperre nach automatischem Phasenwechsel, Zurück-Navigation, Dauermessung, Stumm, Fokus, Ist-Fehler, Browser-Test/CI); Auftrag Abschnitt 12 und 6.5 ergänzt. |
| 2026-09-28 | AP-11/T8: Befunde des Reviews eingearbeitet – Fassung je Tagestermin gegen den Nextcloud-Papierkorb (D-60 ergänzt, AP-11 Umfang und `probleme_loesungen`), alte Einzeltermine geänderter Einheiten sofort entfernen, Datenfluss 3.2 und D-56 an D-60 angepasst. |
| 2026-09-28 | AP-14 T7: Dokumentation abgeschlossen (Changelog, README, Hauptkonzept, Datenmodell, Branding, Auftrag Abschnitt 12, Prüfprotokoll auf Konsistenz geprüft); AP-13, AP-14 und AP-11 bleiben `in_arbeit` bis zur Abnahme durch den Athleten. |
| 2026-09-28 | Abschluss-Review eingearbeitet: Timer-Einstellung auch für offline gezeigte S9 (AP-14 T6), AP-11 Umfang ohne Versionsnummern und mit UID je Fassung, D-60 zu alten Einzelterminen präzisiert. |
| 2026-09-28 | AP-14 Nachtrag T9 (Code-Stand 0.20.0): Entscheidungen zu O-05 bis O-07 (Auftrag E-23), D-57 um Kletterblöcke mit Sätzen ergänzt; Speichern-Leisten über der unteren Navigation. |
| 2026-09-28 | Sechs weitere Volltexte einsortiert (L-P10, L-P12, L-P13, L-T2-17, L-T2-18, L-T3-04; D-51): Felder `datei`, `zugang`, 13.4 „vorhanden“, V-07 (Volltext liegt vor), AP-06 Teilschritt Beschaffung. |
| 2026-09-28 | Übergabe AP-06 Teil C eingearbeitet: neuer Block 13.2.5 R Reha/Prävention (L-R-01 bis L-R-28; Patellasehne, Sprunggelenk, Laufumfang), ID-Konvention `L-R-<nn>`, 13.3 Ausschlüsse, 13.4 ergänzt. Neu D-61 (in der Übergabe D-39), Q-15 und Q-16 (dort Q-10, Q-11), Q-13 um Kongsgaard-Schmerzregel ergänzt; AP-06 Umfang, Kartenzuschnitt (6 Sammeldateien), Teilschritt, `probleme_loesungen`; AP-07 Vorgaben ergänzt. |
| 2026-09-28 | AP-13 Nachtrag (Code-Stand 0.20.1): App-Icons von `/icons/` nach `/app-icons/`, weil Apache `/icons/` serverweit per Alias belegt (Befund IronFox, Auftrag 4.1 Punkt 4); AP-13 `probleme_loesungen` ergänzt. |
| 2026-09-28 | Übergabe AP-06 Teil D (Hypertrophie-Ergänzung) eingearbeitet: 13.2.3 L-T2-20 bis L-T2-26 ausgewählt, L-T2-27 bis L-T2-32 optional, L-T2-07 um die bibliografischen Daten der 3. Aufl. ergänzt (weiter zurückgestellt), Themenfeld-Vokabular; 13.3 Ausschlüsse; 13.4 ergänzt. Neu D-62 (D-28 unverändert, Verweis ergänzt) und V-16; AP-06 Umfang, Kartenzuschnitt, Teilschritt, `probleme_loesungen`; AP-07 Vorgaben ergänzt. |
| 2026-09-28 | Neu D-63 (Favicon V3 gerundet, ändert D-59 für das Favicon), AP-13 `probleme_loesungen` ergänzt (Code-Stand 0.20.2). |
| 2026-09-29 | L-T1-07 Laursen/Buchheit einsortiert (Gesamt-PDF und Kapitel-PDFs, D-51): `datei`/`kapitel`/`zugang`, 13.4 „vorhanden“ und Stand (37 Volltexte), V-13, AP-06 Teilschritt und `probleme_loesungen`. Doppelt hochgeladene Kenney-Datei entfernt. |
| 2026-09-29 | Neu (Fable, Konzept; vom Athleten bestätigt): Auftrag `docs/konzept/uebungskatalog.md`, AP-16 Übungskatalog in der Reihenfolge nach AP-14; D-64 bis D-69 aus E-01 bis E-06; Code-Instanz ergänzt E-17 bis E-20 (Referrer am Video-iframe, oEmbed-Prüfung, Schutz der Linkprüfung, `name_norm`). AP-16 begonnen: T1 umgesetzt (Code-Stand 0.21.0, Schema 23). |
| 2026-09-29 | AP-16 T6 (Code-Stand 0.25.1): Abschnitte 7, 7.1, 8.2, 8.3, 10 und 14 (Kapitel 9 Übungskatalog) nachgezogen; `datenmodell.md`, `gefuehrte-einheit.md` (E-19), `docs/regeln/trainerregeln.md` (Vorabkapitel 9). AP-16 bleibt `in_arbeit` bis zur Abnahme. |
| 2026-09-29 | AP-16 T5 umgesetzt (Code-Stand 0.25.0): wöchentliche Linkprüfung im Cron. |
| 2026-09-29 | AP-16 T4 umgesetzt (Code-Stand 0.24.0): Verlinkung aus S3, S9 und Kalender, Übungsseiten offline. |
| 2026-09-29 | AP-16 T3 umgesetzt (Code-Stand 0.23.0): S10 Übung, S10a Übungskatalog, S8-Eintrag, CSP `frame-src` nur für S10. |
| 2026-09-29 | AP-16 T2 umgesetzt (Code-Stand 0.22.0): MCP-Tools des Übungskatalogs, Linkprüfung, Warnungen in `write_week_plan`/`update_session`. |
| 2026-09-29 | L-T3-09 Hörst (3. Aufl. 2016) einsortiert: Gesamt-PDF und 17 Kapitel-PDFs, `datei`/`kapitel`/`zugang`, 13.4 „vorhanden“ und Stand (38 Volltexte), AP-06. Zwei EPUBs (Climbing Bible – Practical Exercises; Consuegra) warten auf Angaben des Athleten. |
| 2026-09-29 | Übergabe AP-06 Literatur-Nachsteuerung eingearbeitet: L-T1-01 nicht aufgenommen (Abdeckung durch L-A02, Verweis L-T1-15); L-T3-08 zurückgestellt (9. Aufl. 2019 bibliografisch korrigiert), L-T3-19 Consuegra neu im Evidenzkern T3, L-T3-09 mit der 3. Aufl. ausgewählt (Neuauflage nach Erscheinen), L-T3-10 und L-T3-16 als Stufe C ausgewählt, neu L-T3-20 (Übungsband) und L-T3-21 (Managing Injuries, vorläufig); L-P15/L-P16 zu HRV-gesteuertem Training; neu D-70 (ändert D-31/E6), D-71 (EPUB mit Markdown und Ansichts-PDF, ergänzt D-26/D-51), V-17 (IDs der Übergabe D-64/D-65 umnummeriert); V-13, V-15, 13.1, 13.3, 13.4 (42 Volltexte), Abschnitt 14 Kap. 4, AP-06, README und Prüfprotokoll angepasst. Repo bleibt vorerst öffentlich (Entscheidung Athlet, Widerspruch zu D-31 in AP-06 `probleme_loesungen`). |
| 2026-09-29 | AP-15 Blockbilanz, Zielklärung und Übergabe (Auftrag `docs/konzept/blockbilanz.md`, Fable; O-04/O-05 vom Athleten entschieden) umgesetzt, Code-Stand 0.26.0–0.30.1, Schema 24: D-72 bis D-78; Abschnitt 6 (Schritte 1, 2, 8 neu gefasst, Schritt 9 Blockwechsel), 7 (`block_review`, `app_setting`), 8.2/8.3 (drei neue Tools, Erweiterungen, Budget), 10 (S2-Karte, S6, S8, S11, Overlay), 14 (Kapitel 10), 15 (AP-15 mit Statusblock). |
| 2026-09-29 | AP-15 Nachtrag (Code-Stand 0.30.2): kein Overlay während der geführten Einheit, danach auf S2 (E-23, Entscheidung des Athleten); Entscheidungen von AP-15 auf D-72 bis D-78 umnummeriert (D-70/D-71 parallel vergeben). |
| 2026-09-29 | Commit „Lit“ einsortiert: 11 Volltexte (L-P11, L-P15, L-T2-10, -11, -12, -20, -21, L-R-02, -03, -08, -13, -26), neuer Ordner `r-reha/`, Dubletten Hörst und VISA-P entfernt; L-R-08 um das Erratum 2013 (Punktwerte 8b/8c) ergänzt; L-T2-10 Zitat vervollständigt; Lizenzen L-P11 (CC BY-NC 4.0) und L-P15 (CC BY 4.0) aus dem Volltext; 13.4 und Stand (54 Volltexte). |
| 2026-09-29 | Übergabe Literaturblock T4 Beweglichkeit/Dehnen Teil A eingearbeitet (vom Athleten bestätigt): Tabelle 1.2 um T4 erweitert, neuer Abschnitt 13.2.6 (L-T4-01 bis L-T4-33, Kern und optional), D-79 (Planungsfolgen a–k; wegen paralleler Vergabe in AP-15 nicht D-72), Q-17 bis Q-21 (Q-21 Repo öffentlich, entschieden: vorerst öffentlich), V-18 bis V-23; 13.1 (4–7 Sammeldateien, `geltungsbereich` T1–T4, ID-Konvention), 13.3, 13.4, Abschnitte 6 und 14 (T1–T4), AP-06, AP-07, AP-08; Konzeptkopf und D-23 um den Hinweis auf Q-21 ergänzt. |
| 2026-09-29 | Commit „Literatur“ einsortiert: 36 Volltexte (L-P14, L-P16, L-T2-15, -16, -19, -22 bis -32, L-T3-05, L-T3-18, L-R-01, -04 bis -07, -09, -12, -15, -16, -18 bis -20, -22 bis -25, -27, -28), Pre-Proof-Dublette L-P16 entfernt; Lizenzen aus dem Volltext, Hinweise zu Autorenmanuskripten (L-T2-29, L-R-18) und fehlenden Corrigenda (L-T2-23, L-T2-24); 13.4 und Stand (90 Volltexte). |
| 2026-09-29 | Aktualisierte Übergabe T4 eingearbeitet (K-8, K-9): L-T4-32 Behm in den Kern (ausgewählt), neu L-T4-34 Nelson/Kokkonen, Stretching Anatomy (Stufe C, Übungskatalog), 13.3 um Freiwald, Alter, van der Poel ergänzt, Vokabular `uebungskatalog`, Q-21 (Repo wird wieder privat), V-21 erledigt; Commit „Literatur“ einsortiert – 19 Volltexte (L-T4-01 bis -06, -08, -10, -12, -14, -16, -19, -32, -34; L-R-10, -11, -14, -17, -21), Corrigenda L-T2-23/-24, neuer Ordner `t4-beweglichkeit/`, Kapitel-PDFs für L-T4-32 und L-T4-34; falsches Witvrouw-PDF und Dubletten entfernt; V-20 teilweise, V-23 erledigt; 13.4 und Stand (109 Werke). |
| 2026-09-29 | L-T4-22 Witvrouw 2001 nicht aufgenommen (Entscheidung Athlet): Status `nicht_aufgenommen`, aus D-79-Kernliste, 13.4, Kartenzuschnitt AP-06 und Regelvorschlag D-79 (e) entfernt (Knie-Einzelbefund jetzt L-T4-23), 13.3 ergänzt. |
| 2026-09-29 | Übergabe Lückenprüfung Standardwerke eingearbeitet (D-80): neu optional L-T1-16 Koop, L-T2-33 McGill, L-R-29 Brukner & Khan, L-R-30 GOTS-Manual, L-T4-35 Freiwald (aus 13.3 übernommen, K-8 revidiert), L-T4-36 Schleip/Wilke; L-A03 Hinweis Heimausrüstung (Kap. 17); 13.3 um sieben Werke ergänzt; Nachträge D-54, D-61, D-79; Q-22, V-24; 13.4 (sechs Bücher Prio 2), 13.2.6-Einleitung, AP-06. |
| 2026-09-29 | Vorhandene Ausgaben gelten (Nachtrag D-51, D-70; Entscheidung Athlet): L-A01 7. Aufl. nicht mehr vorläufig, keine Hörst-Neuauflage, L-T2-33 und L-R-30 in der 3. Aufl.; eingeordnet L-T3-16 (2. Aufl., Kapitel-PDFs), L-T2-33, L-R-30 (je Kapitel-PDFs), L-T1-16 (EPUB, Kapitel-Markdown), L-T4-36 (nur Kapitel-PDFs), L-T4-13, L-T4-17; Brukner Band 2 und Consuegra-Dublette entfernt; V-13, V-24, 13.4 (Stand 116 Werke), AP-06. |
