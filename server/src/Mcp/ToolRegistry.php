<?php

declare(strict_types=1);

namespace Training\Mcp;

use Mcp\Server\McpServer;
use Mcp\Types\CallToolResult;
use Mcp\Types\TextContent;
use Training\App;
use Training\Clock;
use Training\Data\ProfileRepository;
use Training\Exercise\Catalog;
use Training\Intervals\IntervalsClient;
use Training\Plan\PlanValidator;

/**
 * Registriert die Trainings-Tools (Abschnitt 8.2) am MCP-Server. Rechte: Lese-Tools brauchen training:read,
 * Schreib-Tools training:write (Scope des Tokens). Schreib-Tools prüfen die Schreibsperre (D-20).
 * Antworten sind JSON (structuredContent + Text); fachliche Fehler kommen als Ergebnis mit isError.
 */
final class ToolRegistry
{
    /** @param \Closure(): string $scope */
    public function __construct(private readonly App $app, private readonly Clock $clock, private readonly \Closure $scope)
    {
    }

    public function register(McpServer $mcp): void
    {
        $read = ['readOnlyHint' => true, 'openWorldHint' => false];
        $date = ['type' => 'string', 'pattern' => '^\\d{4}-\\d{2}-\\d{2}$'];

        $mcp->tool('get_week_overview',
            'Wochenübersicht (aggregiert): Kurzsatz (fokus) und Begründung der Woche, je Einheit Kurzsatz (kurz) und Plan vs. Ist (Dauer, RPE, sRPE-Last, Gefühl, Abweichung), Ausdauer mit Aktivität aus Intervals.icu, Summen und Compliance, Schmerz der Woche, Check-in-Mittel und Abdeckung, Morgentest je Tag (Steuerwert, Ampel; Tage grün, Abdeckung), Fitness/Ermüdung/Form. Ohne week_start die laufende Woche.',
            fn (?string $week_start = null): CallToolResult => $this->run('training:read', fn () => $this->reads()->weekOverview($week_start)),
            title: 'Wochenübersicht', inputSchema: ['properties' => ['week_start' => $date + ['description' => 'Montag der Woche (andere Tage werden auf Montag gerundet)']]], annotations: $read);

        $mcp->tool('get_session_detail',
            'Details einer Einheit: plan_json, actual_json, Rückmeldung, Schmerzereignisse, Kurzsatz (coach_summary) und ausführliche Begründung (coach_rationale), bei Ausdauer die Aktivität.',
            fn (int $session_id): CallToolResult => $this->run('training:read', fn () => $this->reads()->sessionDetail($session_id)),
            title: 'Einheit', inputSchema: ['properties' => ['session_id' => ['type' => 'integer']], 'required' => ['session_id']], annotations: $read);

        $mcp->tool('get_pain_history',
            'Schmerzverlauf je Ort: Ereignisse (Datum, Stärke 0–10, Zeitpunkt, Seite), Maximum, Trend der letzten 7 gegenüber den 7 Tagen davor.',
            fn (int $days = 56): CallToolResult => $this->run('training:read', fn () => $this->reads()->painHistory($days)),
            title: 'Schmerzverlauf', inputSchema: ['properties' => ['days' => ['type' => 'integer', 'minimum' => 7, 'maximum' => 180, 'default' => 56]]], annotations: $read);

        $mcp->tool('get_wellness_trend',
            'Tageswerte HRV, Ruhepuls, Schlaf (Intervals.icu) und Check-in (Erholung, Muskelkater, Schmerz); Baseline 7 vs. 28 Tage für HRV und Ruhepuls.',
            fn (int $days = 28): CallToolResult => $this->run('training:read', fn () => $this->reads()->wellnessTrend($days)),
            title: 'Wellness-Trend', inputSchema: ['properties' => ['days' => ['type' => 'integer', 'minimum' => 7, 'maximum' => 90, 'default' => 28]]], annotations: $read);

        $mcp->tool('get_block',
            'Trainingsblock (ohne block_id der aktive bzw. aktuelle): Zeitraum, Phasen, Zielevents, Wochenstatus, Kurzliste der Reviews (Revision, Bilanz, Zielklärung) und Fälligkeiten des Blocks.',
            fn (?int $block_id = null): CallToolResult => $this->run('training:read', fn () => $this->reads()->block($block_id)),
            title: 'Block', inputSchema: ['properties' => ['block_id' => ['type' => 'integer']]], annotations: $read);

        $mcp->tool('get_morning_checks',
            'Morgen-Check-ins (Morgentest Patellasehne, NRS 0–10, null = nicht erhoben): Zusammenfassung für heute (Ampel mit Grund, Werte links/rechts und Steuerwert = Maximum, Wochenausgangswert und 24-Stunden-Regel, Einheiten vom Vortag, grüne Tage und Abdeckung der letzten 7 Tage, abklaerung_empfohlen) und je Tag alle Felder (Nacken/BWS, Sprunggelenk links, Hand rechts, Warnzeichen, Erholung 1–5, Muskelkater 1–5, Notiz), neueste zuerst. Ampel: rot > 5 oder zwei Tage streng steigend bis ≥ 4, gelb 4–5, grün ≤ 3. Die Ampel ist Information; Planänderungen über update_session/write_week_plan.',
            fn (int $days = 14): CallToolResult => $this->run('training:read', fn () => $this->reads()->morningChecks($days)),
            title: 'Morgen-Check-ins', inputSchema: ['properties' => ['days' => ['type' => 'integer', 'minimum' => 7, 'maximum' => 90, 'default' => 14]]], annotations: $read);

        $sectionEnum = ['enum' => array_keys(ProfileRepository::SECTIONS)];
        $mcp->tool('get_athlete_profile',
            'Athletenprofil aus der Datenbank (D-48), Abschnitte ziele, zeitbudget, ausruestung, einschraenkungen, leistungswerte, sonstiges (Markdown) mit Stand und Urheber. Optional nur ein Abschnitt, ein früherer Stand (as_of: Ende dieses Tages) oder mit include_history die Fassungen eines Abschnitts.',
            fn (?string $section = null, ?string $as_of = null, bool $include_history = false): CallToolResult
                => $this->run('training:read', fn () => $this->reads()->athleteProfile($section, $as_of, $include_history)),
            title: 'Athletenprofil', inputSchema: ['properties' => [
                'section' => $sectionEnum, 'as_of' => $date + ['description' => 'Profil wie am Ende dieses Tages'],
                'include_history' => ['type' => 'boolean', 'default' => false, 'description' => 'Fassungen des Abschnitts (nur mit section), neueste zuerst, höchstens 20'],
            ]], annotations: $read);

        // Begründungstexte der Planung (AP-13, E-10): Regel je Feld in der Beschreibung, damit Claude sie beim Planen sieht
        $summaryRule = 'Kurzsatz der Einheit: ein Satz, höchstens ' . WriteTools::SUMMARY_MAX . ' Zeichen, sagt Was und Warum (z. B. „Zweite Krafteinheit, Last wie letzte Woche, Fokus Tiefe – Sehne noch reizbar“). Pflicht außer bei ruhe.';
        $textRule = 'höchstens ' . WriteTools::TEXT_MAX . ' Zeichen, 2–6 Sätze mit Bezug auf Blockziel, Belastungssteuerung und Befunde (Morgentest, Schmerz, Wellness), ohne Literaturzitate; erwartet, aber nicht Pflicht.';
        $sessionSchema = [
            'type' => 'object',
            'required' => ['date', 'type', 'title'],
            'properties' => [
                'date' => $date,
                'type' => ['enum' => PlanValidator::TYPES],
                'title' => ['type' => 'string', 'maxLength' => 191],
                'priority' => ['enum' => ['A', 'B', 'C'], 'default' => 'B'],
                'planned_duration_min' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 600],
                'plan_json' => ['type' => ['object', 'null'], 'description' => 'Schema je Typ (Konzept 7.1): kraft/haltung/mobilitaet {exercises:[{name,exercise_id,sets,reps,load,tempo,rest_s,notes}]}, klettern {blocks:[{kind,exercise_id,spezifitaet,edge_mm,grip,hang_s,rest_s,sets,added_load_kg,duration_min,target,notes}]}, ausdauer {intervals_workout_text,target_type(hf_zone|pace|rpe),summary,sport(Run…)}, ruhe null. exercise_id = Slug aus dem Übungskatalog (find_exercise); bei Kletterblöcken nur für hangboard, campus, zugkraft, antagonisten. Die Einheit beschreibt nur Dosierung und einen kurzen Hinweis, die Ausführung steht im Katalog.'],
                'coach_summary' => ['type' => 'string', 'maxLength' => WriteTools::SUMMARY_MAX, 'description' => $summaryRule],
                'coach_rationale' => ['type' => 'string', 'maxLength' => WriteTools::TEXT_MAX, 'description' => 'Ausführliche Begründung der Einheit („mehr“ in der App): ' . $textRule],
                'sort_order' => ['type' => 'integer'],
            ],
        ];
        $mcp->tool('write_week_plan',
            'Schreibt den bestätigten Wochenplan (D-11): Einheiten in die Datenbank, Ausdauereinheiten zusätzlich als Workout in Intervals.icu (→ Uhr). Alle Einheiten werden vorher geprüft; bei Fehlern wird nichts geschrieben. replace_existing ersetzt nur geplante Einheiten ohne Rückmeldung. Intervals-Fehler werden je Einheit gemeldet. Begründung (E-10): focus ist Pflicht (Kurzsatz der Woche, Was und warum), coach_notes der ausführliche Text der Woche; je Einheit coach_summary Pflicht außer bei ruhe, coach_rationale ausführlich. Die App zeigt den Kurzsatz, den ausführlichen Text hinter „mehr“. Übungen sollen ein exercise_id aus dem Katalog tragen (find_exercise/upsert_exercise); ohne ID kommt eine Warnung (warnungen), eine unbekannte oder archivierte ID ist ein Fehler. Wochen nach dem Ende des aktiven Blocks werden abgelehnt (blockwechsel_erforderlich), solange kein Folgeblock mit bestätigter Zielklärung existiert (R-UEB-06); die Antwort nennt offene Fälligkeiten (faellig).',
            fn (string $week_start, array $sessions, bool $replace_existing = false, ?string $focus = null, ?string $coach_notes = null): CallToolResult
                => $this->run('training:write', fn () => $this->writes()->writeWeekPlan($week_start, $sessions, $replace_existing, $focus, $coach_notes), true),
            title: 'Wochenplan schreiben',
            inputSchema: ['properties' => [
                'week_start' => $date + ['description' => 'Montag der Woche'],
                'sessions' => ['type' => 'array', 'items' => $sessionSchema, 'minItems' => 1],
                'replace_existing' => ['type' => 'boolean', 'default' => false],
                'focus' => ['type' => 'string', 'minLength' => 1, 'maxLength' => WriteTools::FOCUS_MAX, 'description' => 'Kurzsatz der Woche: ein Satz, höchstens ' . WriteTools::FOCUS_MAX . ' Zeichen, sagt Was und Warum (z. B. „Ausdauerwoche mit zwei Intervalleinheiten, Kraft nur erhaltend – die Sehne war ruhig“). Pflicht.'],
                'coach_notes' => ['type' => 'string', 'maxLength' => WriteTools::TEXT_MAX, 'description' => 'Ausführliche Begründung der Woche („mehr“ in der App): ' . $textRule],
            ], 'required' => ['week_start', 'sessions', 'focus']],
            annotations: ['readOnlyHint' => false, 'destructiveHint' => true, 'idempotentHint' => false, 'openWorldHint' => true]);

        $mcp->tool('update_session',
            'Ändert eine Einheit (Datum, Titel, Priorität, Dauer, plan_json, Kurzsatz coach_summary, Begründung coach_rationale, Status, Reihenfolge); nicht übergebene Felder bleiben. Wochentexte (focus, coach_notes) nur über write_week_plan. Bei Ausdauer wird das Intervals.icu-Event nachgezogen (bei „ausgelassen“ gelöscht) bzw. neu angelegt, falls es fehlt. plan_json wie bei write_week_plan: exercise_id aus dem Katalog, ohne ID kommt eine Warnung.',
            fn (int $session_id, array $changes, bool $sync_intervals = true): CallToolResult
                => $this->run('training:write', fn () => $this->writes()->updateSession($session_id, $changes, $sync_intervals), true),
            title: 'Einheit ändern',
            inputSchema: ['properties' => [
                'session_id' => ['type' => 'integer'],
                'changes' => ['type' => 'object', 'properties' => [
                    'date' => $date, 'title' => ['type' => 'string'], 'priority' => ['enum' => ['A', 'B', 'C']],
                    'planned_duration_min' => ['type' => 'integer'], 'plan_json' => ['type' => ['object', 'null']],
                    'coach_summary' => ['type' => 'string', 'maxLength' => WriteTools::SUMMARY_MAX, 'description' => $summaryRule . ' Leer nur bei ruhe (entfernt ihn).'],
                    'coach_rationale' => ['type' => 'string', 'maxLength' => WriteTools::TEXT_MAX, 'description' => 'Ausführliche Begründung: ' . $textRule . ' Leerer Text entfernt sie.'],
                    'status' => ['enum' => ['geplant', 'erledigt', 'teilweise', 'ausgelassen', 'verschoben']],
                    'sort_order' => ['type' => 'integer'],
                ], 'additionalProperties' => false],
                'sync_intervals' => ['type' => 'boolean', 'default' => true],
            ], 'required' => ['session_id', 'changes']],
            annotations: ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => true]);

        $mcp->tool('upsert_block',
            'Legt einen Trainingsblock an oder ändert ihn (ohne block_id: neu). Voraussetzung für write_week_plan. Status „aktiv“ schließt andere aktive Blöcke ab. Beim Blockwechsel zuerst Bilanz des alten Blocks, dann Zielklärung, dann den Folgeblock anlegen (Status geplant) und die Zielklärung mit write_block_review für diesen Block schreiben. Antwort mit faellig.',
            fn (array $block, ?int $block_id = null): CallToolResult => $this->run('training:write', fn () => $this->writes()->upsertBlock($block_id, $block), true),
            title: 'Block anlegen/ändern',
            inputSchema: ['properties' => [
                'block_id' => ['type' => 'integer'],
                'block' => ['type' => 'object', 'required' => ['name', 'start_date', 'end_date'], 'properties' => [
                    'name' => ['type' => 'string'], 'start_date' => $date, 'end_date' => $date,
                    'status' => ['enum' => ['geplant', 'aktiv', 'abgeschlossen']],
                    'goal_events' => ['type' => 'array', 'items' => ['type' => 'object']],
                    'phase_notes' => ['type' => 'string'], 'doc_ref' => ['type' => 'string', 'description' => 'z. B. docs/plaene/block-01.md'],
                ]],
            ], 'required' => ['block']],
            annotations: ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => false]);

        $mcp->tool('update_athlete_profile',
            'Ersetzt den Text eines Profilabschnitts (Markdown, höchstens ' . ProfileRepository::MAX_LENGTH . ' Zeichen) durch eine neue Fassung; frühere Fassungen bleiben erhalten. Immer den vollständigen Abschnitt schicken (vorher mit get_athlete_profile lesen). Leerer Text leert den Abschnitt. reason kurz angeben (z. B. „Test 12.10.: LTHR 172“).',
            fn (string $section, string $content, ?string $reason = null): CallToolResult
                => $this->run('training:write', fn () => $this->writes()->updateAthleteProfile($section, $content, $reason), true),
            title: 'Athletenprofil ändern',
            inputSchema: ['properties' => [
                'section' => $sectionEnum,
                'content' => ['type' => 'string', 'maxLength' => ProfileRepository::MAX_LENGTH],
                'reason' => ['type' => 'string', 'maxLength' => ProfileRepository::REASON_MAX],
            ], 'required' => ['section', 'content']],
            annotations: ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => false]);

        // Übergabe, Blockbilanz, Zielklärung, Revision (AP-15, docs/konzept/blockbilanz.md 5.2)
        $mcp->tool('get_handover',
            'Übergabe für die planende Instanz – zu Beginn jeder Planungssitzung aufrufen und Fälligkeiten dem Athleten vor dem Wochenvorschlag nennen (R-UEB-01). Deterministisch aus der Datenbank: aktiver Block (Zeitraum, Woche, Phasen, Zielevents), gültige Zielklärung (Phase, Prioritäten, Ziele mit Messgröße und Kriterium, Entscheidungen, Risiken), die zwei jüngsten Bilanzen (Bewertung je Ziel, Empfehlung, geänderte Annahmen), Revisionen des Blocks, Kennzahlen der letzten 4 Wochen gegen das Blockmittel, Wochentexte der letzten 4 Wochen (wochen_kurz), Fälligkeiten (faellig), offene Fragen, Stand je Profilabschnitt, offene Entwürfe. Mit detail=true zusätzlich content_json der jüngsten Zielklärung und Bilanz.',
            fn (bool $detail = false): CallToolResult => $this->run('training:read', fn () => $this->reviews()->handover($detail)),
            title: 'Übergabe', inputSchema: ['properties' => ['detail' => ['type' => 'boolean', 'default' => false, 'description' => 'Volltexte der jüngsten Zielklärung und Bilanz']]], annotations: $read);

        $kindEnum = ['enum' => \Training\Review\ReviewValidator::KINDS];
        $mcp->tool('get_block_reviews',
            'Revisionen, Blockbilanz und Zielklärung eines Blocks (ohne block_id der aktive): gültige Fassungen (jüngste bestätigte; neuerer Entwurf unter entwurf) mit content_json und kennzahlen_auto; mit fassungen alle Versionen inklusive Entwürfen mit reason (ohne Inhalt).',
            fn (?int $block_id = null, ?string $kind = null, bool $fassungen = false): CallToolResult
                => $this->run('training:read', fn () => $this->reviews()->blockReviews($block_id, $kind, $fassungen)),
            title: 'Block-Reviews', inputSchema: ['properties' => [
                'block_id' => ['type' => 'integer'], 'kind' => $kindEnum,
                'fassungen' => ['type' => 'boolean', 'default' => false],
            ]], annotations: $read);

        $reviewSchemas = [];
        foreach (\Training\Review\ReviewValidator::KINDS as $k) {
            $reviewSchemas[$k] = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/schemas/review-' . $k . '.json'), true);
            unset($reviewSchemas[$k]['$schema'], $reviewSchemas[$k]['$id']);
        }
        $mcp->tool('write_block_review',
            'Schreibt eine Revision (Belastungssteuerung alle 3–4 Wochen oder bei Schmerz/Ausfall), Blockbilanz (Rückblick am Blockende) oder Zielklärung (Ausblick vor einem Block) als neue Fassung. Nur nach Bestätigung des Athleten mit status bestaetigt schreiben; Entwürfe sind erlaubt. Die Zielklärung gehört zu dem Block, den sie begründet (vorher upsert_block, Status geplant oder aktiv); jede Entscheidung nennt mindestens eine verworfene Alternative oder „keine“ (R-UEB-02); die Bilanz bewertet jedes Ziel der Zielklärung (R-UEB-03). Revisionen ändern Belastung, nicht Ziele (R-UEB-05). reason ab Fassung 2 Pflicht. Kennzahlen berechnet der Server (Bilanz: Blockzeitraum, Revision: 28 Tage bis review_date, sonst period_start/period_end). content nach dem Schema der Art (anyOf in der Eingabe: zielklaerung, bilanz, revision).',
            fn (int $block_id, string $kind, string $review_date, string $summary, array $content, string $status, ?int $sequence = null,
                ?string $period_start = null, ?string $period_end = null, ?string $reason = null): CallToolResult
                => $this->run('training:write', fn () => $this->reviews()->write($block_id, $kind, $sequence, $review_date, $period_start, $period_end, $summary, $content, $status, $reason), true),
            title: 'Block-Review schreiben',
            inputSchema: ['properties' => [
                'block_id' => ['type' => 'integer'], 'kind' => $kindEnum,
                'sequence' => ['type' => 'integer', 'minimum' => 1, 'description' => 'nur Revision: bestehende Revision neu fassen; ohne = nächste Revision'],
                'review_date' => $date + ['description' => 'Datum des Gesprächs'],
                'period_start' => $date, 'period_end' => $date,
                'summary' => ['type' => 'string', 'minLength' => 1, 'maxLength' => \Training\Review\ReviewValidator::SUMMARY_MAX, 'description' => 'Kurzsatz für Listen und Übergabe'],
                'content' => ['description' => 'Inhalt nach dem Schema der Art (docs/konzept/blockbilanz.md 4.3): zielklaerung, bilanz bzw. revision in dieser Reihenfolge', 'anyOf' => array_values($reviewSchemas)],
                'status' => ['enum' => \Training\Review\ReviewValidator::STATUSES],
                'reason' => ['type' => 'string', 'maxLength' => \Training\Review\ReviewValidator::REASON_MAX, 'description' => 'Grund der neuen Fassung (ab Fassung 2 Pflicht)'],
            ], 'required' => ['block_id', 'kind', 'review_date', 'summary', 'content', 'status']],
            annotations: ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => false, 'openWorldHint' => false]);

        // Übungskatalog (AP-16, docs/konzept/uebungskatalog.md 5.1)
        $categoryEnum = ['enum' => Catalog::CATEGORIES];
        $patternEnum = ['enum' => Catalog::PATTERNS];
        $slug = ['type' => 'string', 'pattern' => '^[a-z0-9]+(-[a-z0-9]+)*$', 'minLength' => 3, 'maxLength' => 60];
        $mcp->tool('find_exercise',
            'Sucht im Übungskatalog (vor jeder Wochenplanung Pflicht für jede Kraft-, Haltungs-, Mobilitäts- und Kletterübung hangboard/campus/zugkraft/antagonisten). Rang: exakter Slug/Name/Alias, Teilstring (Umlaute, Bindestriche und Groß/Klein egal), dann ähnlich (gleiches Bewegungsmuster, aehnlich: true). Vorhandene oder ähnliche Einträge verwenden, Varianten mit variant_of anlegen. Je Treffer slug, name, category, pattern, equipment, konfidenz, kurz, variant_of; status nur wenn nicht aktiv, aehnlich nur wenn true. Leer → hinweis auf upsert_exercise.',
            fn (string $query, ?string $category = null, ?string $pattern = null, ?string $equipment = null, int $limit = 10, bool $include_archived = false): CallToolResult
                => $this->run('training:read', fn () => $this->exercises()->find($query, $category, $pattern, $equipment, $limit, $include_archived)),
            title: 'Übung suchen', inputSchema: ['properties' => [
                'query' => ['type' => 'string', 'minLength' => 2, 'description' => 'Name, Alias oder Teil davon (z. B. „split squat“)'],
                'category' => $categoryEnum, 'pattern' => $patternEnum, 'equipment' => ['enum' => Catalog::EQUIPMENT],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 10],
                'include_archived' => ['type' => 'boolean', 'default' => false],
            ], 'required' => ['query']], annotations: $read);

        $mcp->tool('get_exercise',
            'Vollständiger Katalogeintrag einer Übung: Spalten, Aliase, Varianten (Eltern und Kinder), Inhalt (kurz, ziel, muskeln, voraussetzung, ausfuehrung, achten, fehler, vorsicht, progression, regression, dosierung_hinweis, links mit Prüfstatus, quellen, notizen). Mit fassungen die Liste der früheren Fassungen (version, reason, created_at), mit version den Stand vor einer Änderung.',
            fn (?string $slug = null, ?int $id = null, bool $fassungen = false, ?int $version = null): CallToolResult
                => $this->run('training:read', fn () => $this->exercises()->get($slug, $id, $fassungen, $version)),
            title: 'Übung', inputSchema: ['properties' => [
                'slug' => $slug, 'id' => ['type' => 'integer'], 'fassungen' => ['type' => 'boolean', 'default' => false],
                'version' => ['type' => 'integer', 'minimum' => 1, 'description' => 'frühere Fassung (Schnappschuss)'],
            ]], annotations: $read);

        $mcp->tool('list_exercises',
            'Kompaktliste des Übungskatalogs (slug, name, category, pattern; status nur wenn nicht aktiv) für den Gesamtüberblick. Ohne status ohne archivierte Übungen.',
            fn (?string $category = null, ?string $status = null): CallToolResult => $this->run('training:read', fn () => $this->exercises()->list($category, $status)),
            title: 'Übungskatalog', inputSchema: ['properties' => ['category' => $categoryEnum, 'status' => ['enum' => Catalog::STATUSES]]], annotations: $read);

        $contentSchema = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/schemas/exercise.json'), true);
        unset($contentSchema['$schema'], $contentSchema['$id']);
        // Eingabe ohne Serverfelder: embed, geprueft_am und status setzt der Server (werden ignoriert)
        $contentSchema['properties']['links']['items']['required'] = ['url', 'titel', 'art'];
        $mcp->tool('upsert_exercise',
            'Legt eine Übung im Katalog an (slug neu) oder ändert sie (slug vorhanden, reason Pflicht, neue Fassung; slug bleibt). Nach Bestätigung des Wochenplans aufrufen; hinweis_chat dem Athleten zeigen. Name und Aliase dürfen keiner anderen Übung gleichen (sonst Ablehnung mit Treffer). content nach Schema (Pflicht: kurz, ziel, ausfuehrung 2–12 Schritte, quellen ≥ 1 mit L-/R-/D-ID oder „Einschätzung“; vorsicht bei Knie, Sprunggelenk, Fingern mit Reha-Regel und Schmerzgrenze); höchstens 2 Text- und 2 Videolinks, nur https, nur tatsächlich bekannte URLs – der Server prüft die Erreichbarkeit (Videos über oEmbed) und bettet YouTube/Vimeo ein; ein defekter Link setzt die Übung auf links_pruefen. Beim Ändern content immer vollständig schicken (vorher get_exercise). Archivieren mit status archiviert, nur ohne geplante Verwendung.',
            fn (string $slug, ?string $name = null, ?array $aliases = null, ?string $category = null, ?string $pattern = null, ?array $equipment = null,
                ?string $variant_of = null, ?int $difficulty = null, ?string $konfidenz = null, ?array $content = null, ?string $reason = null, ?string $status = null): CallToolResult
                => $this->run('training:write', fn () => $this->exercises()->upsert(array_filter([
                    'slug' => $slug, 'name' => $name, 'aliases' => $aliases, 'category' => $category, 'pattern' => $pattern, 'equipment' => $equipment,
                    'variant_of' => $variant_of, 'difficulty' => $difficulty, 'konfidenz' => $konfidenz, 'content' => $content, 'reason' => $reason, 'status' => $status,
                ], static fn ($v): bool => $v !== null)), true),
            title: 'Übung anlegen/ändern',
            inputSchema: ['properties' => [
                'slug' => $slug + ['description' => 'Kennung, unveränderlich (z. B. bulgarian-split-squat)'],
                'name' => ['type' => 'string', 'maxLength' => 120], 'aliases' => ['type' => 'array', 'items' => ['type' => 'string', 'maxLength' => 120], 'maxItems' => 10],
                'category' => $categoryEnum, 'pattern' => $patternEnum,
                'equipment' => ['type' => 'array', 'items' => ['enum' => Catalog::EQUIPMENT], 'minItems' => 1],
                'variant_of' => $slug + ['description' => 'Slug der Grundübung (Progressionsleiter)'],
                'difficulty' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5], 'konfidenz' => ['enum' => Catalog::KONFIDENZ],
                'content' => $contentSchema, 'reason' => ['type' => 'string', 'maxLength' => ExerciseTools::REASON_MAX, 'description' => 'Pflicht beim Ändern'],
                'status' => ['enum' => ['aktiv', 'archiviert']],
            ], 'required' => ['slug']],
            annotations: ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => true]);
    }

    /** @param callable(): array<string, mixed> $fn */
    private function run(string $requiredScope, callable $fn, bool $write = false): CallToolResult
    {
        $scopes = preg_split('/\s+/', ($this->scope)()) ?: [];
        if (!in_array($requiredScope, $scopes, true)) {
            return self::error('Fehlende Berechtigung: Scope ' . $requiredScope . ' nicht freigegeben.');
        }
        try {
            if ($write && $this->app->writeLocked()) {
                return self::error('Update erforderlich: Code- und Datenbankstand weichen ab, Schreibzugriffe sind gesperrt. Der Athlet muss unter Einstellungen migrieren.');
            }
            $data = $fn();
        } catch (ToolError $e) {
            return self::error($e->getMessage(), $e->details);
        } catch (\Throwable $e) {
            error_log('[training] MCP-Tool: ' . $e::class . ': ' . $e->getMessage());

            return self::error('Interner Fehler: ' . $e->getMessage());
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new CallToolResult(content: [new TextContent(text: $json)], structuredContent: $data);
    }

    /** @param list<string> $details */
    private static function error(string $message, array $details = []): CallToolResult
    {
        $text = json_encode(['fehler' => $message] + ($details !== [] ? ['details' => $details] : []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new CallToolResult(content: [new TextContent(text: (string) $text)], isError: true);
    }

    private function intervals(): ?IntervalsClient
    {
        return IntervalsClient::isConfigured($this->app->config()) ? $this->app->intervalsClient() : null;
    }

    private function reads(): ReadTools
    {
        $user = $this->app->users()->first();

        return new ReadTools($this->app->pdo(), $this->clock, $user?->tz ?? 'Europe/Berlin', $this->intervals());
    }

    private function reviews(): ReviewTools
    {
        return new ReviewTools($this->app->pdo(), $this->clock, $this->app->users()->first()?->tz ?? 'Europe/Berlin');
    }

    private function exercises(): ExerciseTools
    {
        return new ExerciseTools($this->app->pdo(), $this->clock, $this->app->linkChecker());
    }

    private function writes(): WriteTools
    {
        return new WriteTools($this->app->pdo(), $this->clock, $this->intervals(), PlanValidator::default(), $this->app->calendar(), $this->app->users()->first()?->tz ?? 'Europe/Berlin');
    }
}
