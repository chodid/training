<?php

declare(strict_types=1);

namespace Training\Mcp;

use Mcp\Server\McpServer;
use Mcp\Types\CallToolResult;
use Mcp\Types\TextContent;
use Training\App;
use Training\Clock;
use Training\Data\ProfileRepository;
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
            'Trainingsblock (ohne block_id der aktive bzw. aktuelle): Zeitraum, Phasen, Zielevents, Wochenstatus.',
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
                'plan_json' => ['type' => ['object', 'null'], 'description' => 'Schema je Typ (Konzept 7.1): kraft/haltung/mobilitaet {exercises:[{name,sets,reps,load,tempo,rest_s,notes}]}, klettern {blocks:[{kind,spezifitaet,edge_mm,grip,hang_s,rest_s,sets,added_load_kg,duration_min,target,notes}]}, ausdauer {intervals_workout_text,target_type(hf_zone|pace|rpe),summary,sport(Run…)}, ruhe null'],
                'coach_summary' => ['type' => 'string', 'maxLength' => WriteTools::SUMMARY_MAX, 'description' => $summaryRule],
                'coach_rationale' => ['type' => 'string', 'maxLength' => WriteTools::TEXT_MAX, 'description' => 'Ausführliche Begründung der Einheit („mehr“ in der App): ' . $textRule],
                'sort_order' => ['type' => 'integer'],
            ],
        ];
        $mcp->tool('write_week_plan',
            'Schreibt den bestätigten Wochenplan (D-11): Einheiten in die Datenbank, Ausdauereinheiten zusätzlich als Workout in Intervals.icu (→ Uhr). Alle Einheiten werden vorher geprüft; bei Fehlern wird nichts geschrieben. replace_existing ersetzt nur geplante Einheiten ohne Rückmeldung. Intervals-Fehler werden je Einheit gemeldet. Begründung (E-10): focus ist Pflicht (Kurzsatz der Woche, Was und warum), coach_notes der ausführliche Text der Woche; je Einheit coach_summary Pflicht außer bei ruhe, coach_rationale ausführlich. Die App zeigt den Kurzsatz, den ausführlichen Text hinter „mehr“.',
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
            'Ändert eine Einheit (Datum, Titel, Priorität, Dauer, plan_json, Kurzsatz coach_summary, Begründung coach_rationale, Status, Reihenfolge); nicht übergebene Felder bleiben. Wochentexte (focus, coach_notes) nur über write_week_plan. Bei Ausdauer wird das Intervals.icu-Event nachgezogen (bei „ausgelassen“ gelöscht) bzw. neu angelegt, falls es fehlt.',
            fn (int $session_id, array $changes, bool $sync_intervals = true): CallToolResult
                => $this->run('training:write', fn () => $this->writes()->updateSession($session_id, $changes, $sync_intervals), true),
            title: 'Einheit ändern',
            inputSchema: ['properties' => [
                'session_id' => ['type' => 'integer'],
                'changes' => ['type' => 'object', 'properties' => [
                    'date' => $date, 'title' => ['type' => 'string'], 'priority' => ['enum' => ['A', 'B', 'C']],
                    'planned_duration_min' => ['type' => 'integer'], 'plan_json' => ['type' => ['object', 'null']],
                    'coach_summary' => ['type' => 'string', 'minLength' => 1, 'maxLength' => WriteTools::SUMMARY_MAX, 'description' => $summaryRule],
                    'coach_rationale' => ['type' => 'string', 'maxLength' => WriteTools::TEXT_MAX, 'description' => 'Ausführliche Begründung: ' . $textRule . ' Leerer Text entfernt sie.'],
                    'status' => ['enum' => ['geplant', 'erledigt', 'teilweise', 'ausgelassen', 'verschoben']],
                    'sort_order' => ['type' => 'integer'],
                ], 'additionalProperties' => false],
                'sync_intervals' => ['type' => 'boolean', 'default' => true],
            ], 'required' => ['session_id', 'changes']],
            annotations: ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => true]);

        $mcp->tool('upsert_block',
            'Legt einen Trainingsblock an oder ändert ihn (ohne block_id: neu). Voraussetzung für write_week_plan. Status „aktiv“ schließt andere aktive Blöcke ab.',
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

    private function writes(): WriteTools
    {
        return new WriteTools($this->app->pdo(), $this->clock, $this->intervals(), PlanValidator::default(), $this->app->calendar());
    }
}
