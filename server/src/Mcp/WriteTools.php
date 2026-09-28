<?php

declare(strict_types=1);

namespace Training\Mcp;

use PDO;
use Training\Clock;
use Training\Data\AuditLog;
use Training\Data\ProfileRepository;
use Training\Data\WeekRepository;
use Training\Dates;
use Training\Intervals\IntervalsClient;
use Training\Intervals\IntervalsException;
use Training\Plan\PlanValidator;

/**
 * Schreib-Tools der MCP-Schnittstelle (Abschnitt 6 Schritt 4, 8.2; AP-05).
 * Ablauf: vollständige Prüfung aller Eingaben → DB-Transaktion → Intervals.icu-Events je Ausdauereinheit.
 * Fehler bei Intervals.icu werden je Einheit gemeldet; die Einheit bleibt in der DB (ohne Event-ID) und kann mit
 * update_session erneut synchronisiert werden. Jeder Schreibzugriff steht im audit_log (actor mcp).
 */
final class WriteTools
{
    private const PRIORITIES = ['A', 'B', 'C'];
    private const STATUSES = ['geplant', 'erledigt', 'teilweise', 'ausgelassen', 'verschoben'];

    /** Begründungstexte der Planung (AP-13, E-10): Kurzsatz der Woche/Einheit und ausführlicher Text, Länge in Zeichen */
    public const FOCUS_MAX = 255;
    public const SUMMARY_MAX = 200;
    public const TEXT_MAX = 1500;

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly ?IntervalsClient $intervals,
        private readonly PlanValidator $validator,
        private readonly ?\Training\Calendar\CalendarSync $calendar = null,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $sessions
     * @return array<string, mixed>
     */
    public function writeWeekPlan(string $weekStart, array $sessions, bool $replaceExisting, ?string $focus, ?string $coachNotes): array
    {
        if (!Dates::isDate($weekStart) || Dates::weekdayIndex($weekStart) !== 0) {
            throw new ToolError('week_start muss ein Montag im Format YYYY-MM-DD sein.');
        }
        $sunday = Dates::addDays($weekStart, 6);
        $weeks = new WeekRepository($this->pdo, $this->clock);
        $block = $weeks->blockFor($weekStart) ?? $weeks->blockFor($sunday);
        if ($block === null) {
            throw new ToolError('Kein Trainingsblock umfasst diese Woche. Zuerst den Block mit upsert_block anlegen.');
        }
        if ($sessions === []) {
            throw new ToolError('sessions ist leer.');
        }

        $clean = [];
        $errors = [];
        $focus = self::text($focus);
        if ($focus === null) {
            $errors[] = 'focus fehlt: Kurzsatz der Woche (Was und warum, 1–' . self::FOCUS_MAX . ' Zeichen)';
        } elseif (mb_strlen($focus) > self::FOCUS_MAX) {
            $errors[] = 'focus ist ' . mb_strlen($focus) . ' Zeichen lang, erlaubt sind ' . self::FOCUS_MAX;
        }
        $coachNotes = self::text($coachNotes);
        if ($coachNotes !== null && mb_strlen($coachNotes) > self::TEXT_MAX) {
            $errors[] = 'coach_notes ist ' . mb_strlen($coachNotes) . ' Zeichen lang, erlaubt sind ' . self::TEXT_MAX;
        }
        foreach (array_values($sessions) as $i => $s) {
            try {
                $clean[] = $this->validateSession(is_array($s) ? $s : [], $weekStart, $sunday, $i, true);
            } catch (ToolError $e) {
                $errors[] = 'sessions[' . $i . ']: ' . $e->getMessage() . ($e->details !== [] ? ' (' . implode('; ', $e->details) . ')' : '');
            }
        }
        if ($errors !== []) {
            throw new ToolError('Wochenplan ungültig, nichts geschrieben.', $errors);
        }

        $existing = $weeks->sessions($weekStart, $sunday);
        if ($existing !== [] && !$replaceExisting) {
            throw new ToolError('Für diese Woche gibt es bereits ' . count($existing) . ' Einheiten. Mit replace_existing=true ersetzen (Einheiten mit Rückmeldung bleiben erhalten) oder update_session verwenden.');
        }

        // Ersetzen: nur geplante Einheiten ohne Rückmeldung; alles mit Durchführung bleibt.
        $replaced = [];
        $kept = [];
        $eventsToDelete = [];
        foreach ($existing as $s) {
            if ($s['status'] === 'geplant' && $s['execution_id'] === null) {
                $replaced[] = (int) $s['id'];
                if ($s['intervals_event_id'] !== null) {
                    $eventsToDelete[] = (int) $s['intervals_event_id'];
                }
            } else {
                $kept[] = ['id' => (int) $s['id'], 'datum' => $s['date'], 'titel' => $s['title'], 'status' => $s['status']];
            }
        }

        $audit = new AuditLog($this->pdo, $this->clock);
        $this->pdo->beginTransaction();
        try {
            $weekId = $weeks->upsertWeek((int) $block['id'], $weekStart, $focus, $coachNotes, 'bestaetigt', 'mcp');
            foreach ($replaced as $id) {
                $weeks->deleteSession($id);
            }
            $ids = [];
            foreach ($clean as $s) {
                $ids[] = $weeks->insertSession($weekId, $s);
            }
            $audit->write('mcp', 'week_plan_write', 'training_week', $weekId, ['sessions' => $clean, 'replace' => $replaceExisting],
                sprintf('Wochenplan %s: %d Einheiten neu, %d ersetzt, %d behalten', $weekStart, count($ids), count($replaced), count($kept)));
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // Intervals.icu: ersetzte Events löschen, neue Ausdauer-Events anlegen (nach dem Commit).
        $intervalsErrors = [];
        foreach ($eventsToDelete as $eventId) {
            $err = $this->intervalsCall(fn () => $this->intervals?->deleteEvent($eventId));
            if ($err !== null) {
                $intervalsErrors[] = 'Event ' . $eventId . ' nicht gelöscht: ' . $err;
            } else {
                $audit->write('mcp', 'intervals_event_delete', 'intervals_event', $eventId, null, 'Event gelöscht (Wochenplan ersetzt)');
            }
        }
        $out = [];
        foreach ($clean as $i => $s) {
            $row = ['id' => $ids[$i], 'datum' => $s['date'], 'typ' => $s['type'], 'titel' => $s['title']];
            if ($s['type'] === 'ausdauer') {
                [$eventId, $err] = $this->syncEvent($ids[$i], $s, null);
                $row['intervals_event_id'] = $eventId;
                if ($err !== null) {
                    $row['fehler_intervals'] = $err;
                }
            }
            $out[] = $row;
        }
        $failed = count(array_filter($out, static fn (array $r): bool => isset($r['fehler_intervals'])));
        $calendarErrors = $this->calendarSync($ids, $replaced);

        return array_filter([
            'woche' => $weekStart,
            'week_id' => $weekId,
            'block' => $block['name'],
            'einheiten' => $out,
            'ersetzt' => $replaced,
            'behalten' => $kept,
            'fehler_intervals' => array_merge($intervalsErrors, $failed > 0 ? [$failed . ' Ausdauereinheit(en) ohne Event – mit update_session (sync_intervals) erneut versuchen.'] : []),
            'fehler_kalender' => $calendarErrors,
            'status' => $failed > 0 || $intervalsErrors !== [] || $calendarErrors !== [] ? 'teilweise' : 'ok',
        ], static fn ($v): bool => $v !== []);
    }

    /**
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    public function updateSession(int $sessionId, array $changes, bool $syncIntervals = true): array
    {
        $weeks = new WeekRepository($this->pdo, $this->clock);
        $s = $weeks->session($sessionId);
        if ($s === null) {
            throw new ToolError('Einheit ' . $sessionId . ' nicht gefunden.');
        }
        $allowed = ['date', 'title', 'priority', 'planned_duration_min', 'plan_json', 'coach_summary', 'coach_rationale', 'status', 'sort_order'];
        $unknown = array_diff(array_keys($changes), $allowed);
        if ($unknown !== []) {
            throw new ToolError('Unbekannte Felder: ' . implode(', ', $unknown) . '. Erlaubt: ' . implode(', ', $allowed) . '.');
        }
        $merged = [
            'date' => $s['date'], 'type' => $s['type'], 'title' => $s['title'], 'priority' => $s['priority'],
            'planned_duration_min' => $s['planned_duration_min'], 'plan_json' => $s['plan'], 'coach_summary' => $s['coach_summary'],
            'coach_rationale' => $s['coach_rationale'], 'sort_order' => $s['sort_order'],
        ];
        foreach ($changes as $k => $v) {
            if ($k !== 'status') {
                $merged[$k] = $v;
            }
        }
        if (array_key_exists('coach_summary', $changes) && self::text($changes['coach_summary']) === null) {
            throw new ToolError('coach_summary darf nicht leer sein (1–' . self::SUMMARY_MAX . ' Zeichen).');
        }
        // Unveränderte Begründungstexte nicht erneut prüfen (Altdaten vor AP-13 können länger sein)
        foreach (['coach_summary', 'coach_rationale'] as $k) {
            if (!array_key_exists($k, $changes)) {
                unset($merged[$k]);
            }
        }
        $clean = $this->validateSession($merged, null, null, 0, false);
        $fields = [];
        foreach (['date', 'title', 'priority', 'planned_duration_min', 'plan_json', 'coach_summary', 'coach_rationale', 'sort_order'] as $k) {
            if (array_key_exists($k, $changes)) {
                $fields[$k] = $clean[$k];
            }
        }
        if (array_key_exists('status', $changes)) {
            if (!in_array($changes['status'], self::STATUSES, true)) {
                throw new ToolError('status muss einer von ' . implode(', ', self::STATUSES) . ' sein.');
            }
            $fields['status'] = $changes['status'];
        }
        if (isset($fields['date']) && Dates::monday($fields['date']) !== Dates::monday((string) $s['date'])) {
            $weekId = $weeks->weekIdFor(Dates::monday($fields['date']));
            if ($weekId === null) {
                throw new ToolError('Für die Woche ab ' . Dates::monday($fields['date']) . ' gibt es keinen Wochenplan; zuerst write_week_plan.');
            }
            $fields['week_id'] = $weekId;
        }

        $this->pdo->beginTransaction();
        try {
            $weeks->updateSession($sessionId, $fields);
            (new AuditLog($this->pdo, $this->clock))->write('mcp', 'session_update', 'session', $sessionId, $fields, 'Einheit ' . $sessionId . ' geändert: ' . implode(', ', array_keys($fields)));
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $result = ['id' => $sessionId, 'geaendert' => array_keys($fields)];
        if ($s['type'] === 'ausdauer' && $syncIntervals) {
            $cancelled = in_array($fields['status'] ?? '', ['ausgelassen'], true);
            if ($cancelled && $s['intervals_event_id'] !== null) {
                $err = $this->intervalsCall(fn () => $this->intervals?->deleteEvent((int) $s['intervals_event_id']));
                $result['intervals'] = $err === null ? 'event_geloescht' : 'fehler: ' . $err;
                if ($err === null) {
                    $weeks->updateSession($sessionId, ['intervals_event_id' => null]);
                }
            } elseif (!$cancelled) {
                [$eventId, $err] = $this->syncEvent($sessionId, $clean, $s['intervals_event_id'] !== null ? (int) $s['intervals_event_id'] : null);
                $result['intervals_event_id'] = $eventId;
                $result['intervals'] = $err === null ? ($s['intervals_event_id'] !== null ? 'event_aktualisiert' : 'event_angelegt') : 'fehler: ' . $err;
            }
        }
        $calendarErrors = $this->calendarSync([$sessionId], []);
        if ($calendarErrors !== []) {
            $result['fehler_kalender'] = $calendarErrors;
        }
        $after = $weeks->session($sessionId);
        $result['einheit'] = ['datum' => $after['date'], 'titel' => $after['title'], 'status' => $after['status'], 'prio' => $after['priority'], 'plan_min' => $after['planned_duration_min']];

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function upsertBlock(?int $blockId, array $input): array
    {
        foreach (['name', 'start_date', 'end_date'] as $k) {
            if (!isset($input[$k]) || !is_string($input[$k]) || trim($input[$k]) === '') {
                throw new ToolError($k . ' fehlt.');
            }
        }
        if (!Dates::isDate($input['start_date']) || !Dates::isDate($input['end_date']) || $input['end_date'] < $input['start_date']) {
            throw new ToolError('start_date/end_date ungültig (YYYY-MM-DD, Ende nicht vor Start).');
        }
        $status = $input['status'] ?? 'geplant';
        if (!in_array($status, ['geplant', 'aktiv', 'abgeschlossen'], true)) {
            throw new ToolError('status muss geplant, aktiv oder abgeschlossen sein.');
        }
        $goals = $input['goal_events'] ?? null;
        if ($goals !== null && !is_array($goals)) {
            throw new ToolError('goal_events muss eine Liste sein.');
        }
        $now = \Training\Db::ts($this->clock->now());
        $values = [mb_substr(trim($input['name']), 0, 191), $input['start_date'], $input['end_date'],
            $goals === null ? null : json_encode($goals, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            isset($input['phase_notes']) ? (string) $input['phase_notes'] : null, $status,
            isset($input['doc_ref']) ? mb_substr((string) $input['doc_ref'], 0, 255) : null];
        $this->pdo->beginTransaction();
        try {
            if ($status === 'aktiv') {
                $this->pdo->prepare("UPDATE training_block SET status = 'abgeschlossen', updated_at = ? WHERE status = 'aktiv' AND id <> ?")->execute([$now, $blockId ?? 0]);
            }
            if ($blockId === null) {
                $this->pdo->prepare('INSERT INTO training_block (name, start_date, end_date, goal_events_json, phase_notes, status, doc_ref, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([...$values, $now, $now]);
                $blockId = (int) $this->pdo->lastInsertId();
                $action = 'block_create';
            } else {
                $stmt = $this->pdo->prepare('UPDATE training_block SET name = ?, start_date = ?, end_date = ?, goal_events_json = ?, phase_notes = ?, status = ?, doc_ref = ?, updated_at = ? WHERE id = ?');
                $stmt->execute([...$values, $now, $blockId]);
                if ($stmt->rowCount() === 0 && (new WeekRepository($this->pdo, $this->clock))->block($blockId) === null) {
                    throw new ToolError('Block ' . $blockId . ' nicht gefunden.');
                }
                $action = 'block_update';
            }
            (new AuditLog($this->pdo, $this->clock))->write('mcp', $action, 'training_block', $blockId, $input, 'Block ' . $values[0] . ' ' . $values[1] . '–' . $values[2] . ' (' . $status . ')');
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return ['id' => $blockId, 'name' => $values[0], 'start' => $values[1], 'ende' => $values[2], 'status' => $status];
    }

    /**
     * Kalender nachziehen (AP-11): Termine der Einheiten anlegen/aktualisieren, ersetzte entfernen. Fehler werden nur
     * gemeldet (einmal je Aufruf zusammengefasst); der stündliche Abgleich holt sie nach.
     * @param list<int> $push
     * @param list<int> $remove
     * @return list<string>
     */
    private function calendarSync(array $push, array $remove): array
    {
        if ($this->calendar === null || !$this->calendar->enabled()) {
            return [];
        }
        $errors = [];
        foreach ($remove as $id) {
            if (($e = $this->calendar->remove($id)) !== null) {
                $errors[] = $e;
                break;
            }
        }
        foreach ($errors === [] ? $push : [] as $id) {
            if (($e = $this->calendar->push($id)) !== null) {
                $errors[] = $e;
                break;
            }
        }

        return $errors === [] ? [] : [$errors[0] . ' Der stündliche Abgleich überträgt die Termine erneut.'];
    }

    /**
     * Neue Fassung eines Profilabschnitts (D-48); unveränderter Text legt keine Fassung an.
     * @return array<string, mixed>
     */
    public function updateAthleteProfile(string $section, string $content, ?string $reason): array
    {
        try {
            $saved = (new ProfileRepository($this->pdo, $this->clock))->save($section, $content, 'mcp', $reason);
        } catch (\InvalidArgumentException $e) {
            throw new ToolError($e->getMessage());
        }
        if ($saved['unchanged']) {
            return ['abschnitt' => $section, 'version' => $saved['id'], 'unveraendert' => true, 'hinweis' => 'Text unverändert, keine neue Fassung angelegt.'];
        }
        $label = ProfileRepository::SECTIONS[$section][0];
        (new AuditLog($this->pdo, $this->clock))->write('mcp', 'profile_update', 'athlete_profile', $saved['id'], $content,
            'Profil „' . $label . '“ geändert' . ($reason !== null && trim($reason) !== '' ? ': ' . trim($reason) : ''));

        return ['abschnitt' => $section, 'version' => $saved['id'], 'unveraendert' => false];
    }

    /**
     * @param array<string, mixed> $s
     * @return array<string, mixed>
     */
    private function validateSession(array $s, ?string $from, ?string $to, int $index, bool $requireSummary): array
    {
        $errors = [];
        $date = $s['date'] ?? null;
        if (!is_string($date) || !Dates::isDate($date)) {
            $errors[] = 'date fehlt oder ist kein Datum (YYYY-MM-DD)';
        } elseif ($from !== null && ($date < $from || $date > $to)) {
            $errors[] = 'date ' . $date . ' liegt nicht in der Woche ' . $from . '–' . $to;
        }
        $type = $s['type'] ?? null;
        if (!in_array($type, PlanValidator::TYPES, true)) {
            $errors[] = 'type muss einer von ' . implode(', ', PlanValidator::TYPES) . ' sein';
        }
        $title = is_string($s['title'] ?? null) ? trim($s['title']) : '';
        if ($title === '' && $type !== 'ruhe') {
            $errors[] = 'title fehlt';
        }
        $priority = $s['priority'] ?? 'B';
        if (!in_array($priority, self::PRIORITIES, true)) {
            $errors[] = 'priority muss A, B oder C sein';
        }
        $duration = $s['planned_duration_min'] ?? null;
        if ($duration !== null && (!is_int($duration) || $duration < 1 || $duration > 600)) {
            $errors[] = 'planned_duration_min muss eine ganze Zahl 1–600 sein';
        }
        $plan = $s['plan_json'] ?? null;
        if (in_array($type, PlanValidator::TYPES, true)) {
            foreach ($this->validator->validatePlan((string) $type, $plan) as $e) {
                $errors[] = 'plan_json ' . $e;
            }
        }
        // Begründungstexte (E-10): Kurzsatz Pflicht außer bei Ruhetagen (nur beim Wochenplan), ausführlicher Text optional
        $summary = self::text($s['coach_summary'] ?? null);
        if ($summary === null && $requireSummary && $type !== 'ruhe') {
            $errors[] = 'coach_summary fehlt: Kurzsatz der Einheit (Was und warum, 1–' . self::SUMMARY_MAX . ' Zeichen)';
        } elseif ($summary !== null && mb_strlen($summary) > self::SUMMARY_MAX) {
            $errors[] = 'coach_summary ist ' . mb_strlen($summary) . ' Zeichen lang, erlaubt sind ' . self::SUMMARY_MAX;
        }
        $rationale = self::text($s['coach_rationale'] ?? null);
        if ($rationale !== null && mb_strlen($rationale) > self::TEXT_MAX) {
            $errors[] = 'coach_rationale ist ' . mb_strlen($rationale) . ' Zeichen lang, erlaubt sind ' . self::TEXT_MAX;
        }
        if ($errors !== []) {
            throw new ToolError('ungültig', $errors);
        }

        return [
            'date' => $date,
            'type' => $type,
            'title' => mb_substr($title !== '' ? $title : 'Ruhetag', 0, 191),
            'priority' => $priority,
            'planned_duration_min' => $duration,
            'plan_json' => $plan === [] ? null : $plan,
            'coach_summary' => $summary,
            'coach_rationale' => $rationale,
            'sort_order' => is_int($s['sort_order'] ?? null) ? $s['sort_order'] : $index,
        ];
    }

    /** Text eines Begründungsfelds: getrimmt, leer bzw. kein Text = null. */
    private static function text(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Legt das Intervals.icu-Event einer Ausdauereinheit an oder aktualisiert es.
     *
     * @param array<string, mixed> $s
     * @return array{0: ?int, 1: ?string} Event-ID, Fehler
     */
    private function syncEvent(int $sessionId, array $s, ?int $eventId): array
    {
        if ($this->intervals === null) {
            return [$eventId, 'Intervals.icu nicht eingerichtet (INTERVALS_API_KEY, INTERVALS_ATHLETE_ID).'];
        }
        $plan = $s['plan_json'] ?? [];
        $event = [
            'category' => 'WORKOUT',
            'type' => $plan['sport'] ?? 'Run',
            'name' => $s['title'],
            'start_date_local' => $s['date'] . 'T00:00:00',
            'description' => (string) ($plan['intervals_workout_text'] ?? ''),
            'external_id' => 'training-session-' . $sessionId,
        ];
        if ($s['planned_duration_min'] !== null) {
            $event['moving_time'] = $s['planned_duration_min'] * 60;
        }
        $audit = new AuditLog($this->pdo, $this->clock);
        try {
            if ($eventId !== null) {
                $this->intervals->updateEvent($eventId, $event);
                $audit->write('mcp', 'intervals_event_update', 'intervals_event', $eventId, $event, 'Event zu Einheit ' . $sessionId . ' aktualisiert');

                return [$eventId, null];
            }
            $created = $this->intervals->createEvent($event);
            $newId = isset($created['id']) ? (int) $created['id'] : null;
            if ($newId === null) {
                return [null, 'Intervals.icu lieferte keine Event-ID.'];
            }
            (new WeekRepository($this->pdo, $this->clock))->updateSession($sessionId, ['intervals_event_id' => $newId]);
            $audit->write('mcp', 'intervals_event_create', 'intervals_event', $newId, $event, 'Event zu Einheit ' . $sessionId . ' angelegt');

            return [$newId, null];
        } catch (IntervalsException $e) {
            $audit->write('mcp', 'intervals_error', 'session', $sessionId, null, mb_substr($e->getMessage(), 0, 400));

            return [$eventId, $e->getMessage()];
        }
    }

    private function intervalsCall(callable $fn): ?string
    {
        if ($this->intervals === null) {
            return 'Intervals.icu nicht eingerichtet.';
        }
        try {
            $fn();

            return null;
        } catch (IntervalsException $e) {
            return $e->getMessage();
        }
    }
}
