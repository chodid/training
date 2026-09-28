<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Dates;
use Training\Http\Request;
use Training\Http\Response;
use Training\Intervals\ActivityLookup;
use Training\Intervals\IntervalsClient;
use Training\Plan\PlanValidator;
use Training\View\Labels;

/**
 * S3 Einheit (Abschnitt 10, 11): Plan mit Soll, Ist-Eingabe (vorbelegt mit Soll bzw. letzter Eingabe),
 * Rückmeldung (RPE, Feel, Schmerz, Abweichung, Notiz, Status); bei Ausdauer die verknüpfte Aktivität.
 */
final class SessionController extends AppController
{
    private const DONE = ['erledigt', 'teilweise'];

    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $id = self::intField($request->method === 'POST' ? $request->post('id') : $request->query('id'), 1, PHP_INT_MAX);
        $session = $id !== null ? $this->weeks()->session($id) : null;
        if ($session === null || $session['type'] === 'ruhe') {
            return Response::html(404, $this->app->view()->render('message', [
                'title' => 'Nicht gefunden',
                'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Einheit nicht gefunden.', 'text' => 'Zurück zur Wochenansicht.'],
                'host' => $this->app->host(),
            ]));
        }
        $activity = $this->activity($session);

        if ($request->method !== 'POST') {
            return $this->form($session, $activity, $this->prefill($session, $activity), [], null);
        }
        if (!$this->csrfOk($request)) {
            return Response::error(403, 'Ungültiges Formular.');
        }
        if ($locked = $this->lockedResponse()) {
            return $locked;
        }

        [$data, $invalid, $message] = $this->parse($request, $session);
        if (self::standConflict($request, $this->standOf($session))) {
            return $this->form($session, $activity, $data, $invalid, 'Die Rückmeldung zu dieser Einheit wurde inzwischen geändert (z. B. auf einem anderen Gerät). Deine Eingaben stehen unten; Speichern übernimmt sie.', 409);
        }
        if ($message !== null) {
            return $this->form($session, $activity, $data, $invalid, $message, 422);
        }

        $previousNotes = $this->feedback()->execution((int) $session['id'])['notes'] ?? null;
        $pdo = $this->app->pdo();
        $pdo->beginTransaction();
        try {
            $executionId = $this->feedback()->saveExecution((int) $session['id'], $data['execution']);
            $this->weeks()->setStatus((int) $session['id'], $data['status']);
            $this->audit()->write('web', 'session_feedback', 'session', (int) $session['id'], $data['execution'] + ['status' => $data['status']],
                sprintf('Rückmeldung %s: Status %s, RPE %s, Dauer %s min', $session['title'], $data['status'], $data['execution']['rpe_cr10'] ?? '–', $data['execution']['duration_min'] ?? '–'));
            if ($data['pain'] !== null) {
                $painId = $this->feedback()->addPain($data['pain']);
                $this->audit()->write('web', 'pain_create', 'pain_event', $painId, $data['pain'], 'Schmerz ' . $data['pain']['location'] . ' ' . $data['pain']['intensity_0_10'] . '/10 zu Einheit ' . $session['id']);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        unset($executionId);

        $pushed = $this->pushFeedback($session, $activity, $data['execution'], $previousNotes);

        return self::saved($request, '/woche?start=' . Dates::monday((string) $session['date']) . '&ok=einheit' . ($pushed === false ? '&intervals=fehler' : ($pushed ? '&intervals=ok' : '')));
    }

    /**
     * Rückschreiben nach Intervals.icu (Q-02, D-46): RPE (ohne 0) und Gefühl auf die zugeordnete Aktivität, die Notiz
     * als Kommentar – nur wenn sie neu oder geändert ist. Fehler brechen nichts ab (Rückmeldung ist gespeichert).
     *
     * @param array<string, mixed> $session
     * @param ?array<string, mixed> $activity
     * @param array<string, mixed> $execution
     * @return ?bool null = nichts zu tun, true = übertragen, false = Fehler
     */
    private function pushFeedback(array $session, ?array $activity, array $execution, ?string $previousNotes): ?bool
    {
        if ($activity === null || !isset($activity['id']) || !IntervalsClient::isConfigured($this->app->config())) {
            return null;
        }
        $fields = [];
        if (($execution['rpe_cr10'] ?? null) !== null && (int) $execution['rpe_cr10'] > 0) {
            $fields['icu_rpe'] = (int) $execution['rpe_cr10'];
        }
        if (($execution['feel_1_5'] ?? null) !== null) {
            $fields['feel'] = (int) $execution['feel_1_5'];
        }
        $notes = $execution['notes'] ?? null;
        $comment = $notes !== null && $notes !== $previousNotes ? 'Training-App: ' . $notes : null;
        if ($fields === [] && $comment === null) {
            return null;
        }
        $client = $this->app->intervalsClient();
        $id = (string) $activity['id'];
        try {
            if ($fields !== []) {
                $client->updateActivity($id, $fields);
            }
            if ($comment !== null) {
                $client->addActivityMessage($id, $comment);
            }
            $this->audit()->write('web', 'intervals_feedback', 'intervals_activity', $id, $fields + ['kommentar' => $comment !== null],
                'Rückmeldung zu Einheit ' . $session['id'] . ' nach Intervals.icu übertragen');

            return true;
        } catch (\Training\Intervals\IntervalsException $e) {
            $this->audit()->write('web', 'intervals_error', 'session', (int) $session['id'], null, mb_substr($e->getMessage(), 0, 400));

            return false;
        }
    }

    /** @param array<string, mixed> $session @return array<string, mixed>|null */
    private function activity(array $session): ?array
    {
        if ($session['type'] !== 'ausdauer' || $session['date'] > $this->today()) {
            return null;
        }
        $client = IntervalsClient::isConfigured($this->app->config()) ? $this->app->intervalsClient() : null;
        $lookup = new ActivityLookup($client, $this->app->pdo(), $this->app->clock());

        return ActivityLookup::match($session, $lookup->activities((string) $session['date'], (string) $session['date']));
    }

    /**
     * Vorbelegung: gespeicherte Rückmeldung, sonst Soll aus dem Plan bzw. Dauer der Aktivität.
     *
     * @param array<string, mixed> $session
     * @param ?array<string, mixed> $activity
     * @return array<string, mixed>
     */
    private function prefill(array $session, ?array $activity): array
    {
        $e = $this->feedback()->execution((int) $session['id']);
        $plan = $session['plan'] ?? [];
        $items = $plan['exercises'] ?? $plan['blocks'] ?? [];
        $actualItems = $e['actual']['exercises'] ?? $e['actual']['blocks'] ?? [];
        $ist = [];
        foreach ($items as $i => $item) {
            $ist[$i] = array_merge($item, $actualItems[$i] ?? []);
        }
        $duration = $e['duration_min'] ?? null;
        if ($duration === null && $activity !== null && isset($activity['moving_time'])) {
            $duration = (int) round($activity['moving_time'] / 60);
        }

        return [
            'status' => $session['status'] === 'geplant' ? 'erledigt' : $session['status'],
            'ist' => $ist,
            'duration_min' => $duration ?? $session['planned_duration_min'],
            'rpe' => $e['rpe_cr10'] ?? null,
            'feel' => $e['feel_1_5'] ?? null,
            'deviation' => $e['deviation_reason'] ?? '',
            'notes' => $e['notes'] ?? '',
            'pain_choice' => 'nein',
            'pain_fields' => [],
        ];
    }

    /**
     * @param array<string, mixed> $session
     * @return array{0: array<string, mixed>, 1: array<string, bool>, 2: ?string}
     */
    private function parse(Request $request, array $session): array
    {
        $invalid = [];
        $errors = [];
        $status = (string) $request->post('status');
        $done = in_array($status, self::DONE, true);
        if (!in_array($status, ['erledigt', 'teilweise', 'ausgelassen', 'verschoben'], true)) {
            $invalid['status'] = true;
            $errors[] = 'Bitte einen Status wählen.';
        }
        $rpe = self::intField($request->post('rpe'), 0, 10);
        $feel = self::intField($request->post('feel'), 1, 5);
        $duration = self::intField($request->post('duration_min'), 1, 600);
        if ($done && $rpe === null) {
            $invalid['rpe'] = true;
            $errors[] = 'Bitte die Anstrengung (RPE 0–10) angeben.';
        }
        if ($done && $feel === null) {
            $invalid['feel'] = true;
            $errors[] = 'Bitte angeben, wie sich die Einheit angefühlt hat (1–5).';
        }
        if ($done && $duration === null) {
            $invalid['duration'] = true;
            $errors[] = 'Bitte die Dauer in Minuten angeben (1–600).';
        }
        $deviation = (string) $request->post('deviation');
        if (!array_key_exists($deviation, Labels::DEVIATIONS)) {
            $deviation = '';
        }

        // Ist-Werte je Übung bzw. Block
        $plan = $session['plan'] ?? [];
        $key = isset($plan['exercises']) ? 'exercises' : (isset($plan['blocks']) ? 'blocks' : null);
        $istInput = $request->postArray('ist');
        $ist = [];
        $actual = null;
        if ($key !== null) {
            $items = [];
            foreach ($plan[$key] as $i => $item) {
                $in = is_array($istInput[$i] ?? null) ? $istInput[$i] : [];
                $row = $key === 'exercises' ? ['name' => $item['name']] : ['kind' => $item['kind']];
                foreach (['sets' => [0, 30], 'duration_min' => [1, 300]] as $field => [$min, $max]) {
                    if (array_key_exists($field, $in) && (string) $in[$field] !== '') {
                        $v = self::intField((string) $in[$field], $min, $max);
                        if ($v === null) {
                            $invalid['ist'] = true;
                            $errors[] = sprintf('%s: „%s“ ist kein gültiger Wert.', $item['name'] ?? Labels::BLOCK_KINDS[$item['kind']] ?? '?', (string) $in[$field]);
                        } else {
                            $row[$field] = $v;
                        }
                    }
                }
                foreach (['reps' => 20, 'load' => 40, 'notes' => 500] as $field => $max) {
                    if (array_key_exists($field, $in) && trim((string) $in[$field]) !== '') {
                        $row[$field] = mb_substr(trim((string) $in[$field]), 0, $max);
                    }
                }
                $items[] = $row;
                $ist[$i] = array_merge($item, $row);
            }
            $actual = [$key => $items];
            $schemaErrors = PlanValidator::default()->validateActual((string) $session['type'], $actual);
            if ($schemaErrors !== []) {
                $invalid['ist'] = true;
                $errors[] = 'Ist-Werte ungültig: ' . implode('; ', $schemaErrors);
            }
        }

        // Schmerz
        $painYes = $request->post('pain') === 'ja';
        $painFields = [
            'location' => (string) $request->post('pain_location'),
            'side' => (string) $request->post('pain_side'),
            'intensity' => self::intField($request->post('pain_intensity'), 0, 10),
            'timing' => (string) $request->post('pain_timing'),
        ];
        $pain = null;
        if ($painYes) {
            $painErrors = self::validatePain($painFields, $invalid);
            $errors = [...$errors, ...$painErrors];
            if ($painErrors === []) {
                $pain = [
                    'date' => (string) $session['date'],
                    'session_id' => (int) $session['id'],
                    'location' => $painFields['location'],
                    'side' => array_key_exists($painFields['side'], Labels::SIDES) ? $painFields['side'] : 'na',
                    'intensity_0_10' => (int) $painFields['intensity'],
                    'timing' => $painFields['timing'],
                    'notes' => null,
                ];
            }
        }

        // Zeitpunkt der Eingabe; bei offline gepufferten Eingaben der Moment der Erfassung, nicht des Sendens (D-45).
        $at = $this->offlineTime($request) ?? $this->app->clock()->now();
        $atDate = (new \DateTimeImmutable('@' . $at))->setTimezone(new \DateTimeZone($this->session->tz ?? 'Europe/Berlin'))->format('Y-m-d');
        $performed = null;
        if ($done) {
            $performed = $session['date'] === $atDate ? gmdate('Y-m-d H:i:s', $at) : $session['date'] . ' 12:00:00';
        }

        $data = [
            'status' => $status,
            'ist' => $ist,
            'duration_min' => $request->post('duration_min'),
            'rpe' => $rpe,
            'feel' => $feel,
            'deviation' => $deviation,
            'notes' => (string) $request->post('notes'),
            'pain' => $pain,
            'pain_choice' => $painYes ? 'ja' : 'nein',
            'pain_fields' => $painFields,
            'execution' => [
                'performed_at' => $performed,
                'duration_min' => $done ? $duration : null,
                'actual' => $done ? $actual : null,
                'rpe_cr10' => $done ? $rpe : null,
                'feel_1_5' => $done ? $feel : null,
                'deviation_reason' => $deviation === '' ? null : $deviation,
                'notes' => self::textField($request->post('notes')),
            ],
        ];

        return [$data, $invalid, $errors === [] ? null : implode(' ', $errors)];
    }

    /**
     * @param array{location: string, side: string, intensity: ?int, timing: string} $p
     * @param array<string, bool> $invalid
     * @return list<string>
     */
    public static function validatePain(array $p, array &$invalid): array
    {
        $errors = [];
        if (!array_key_exists($p['location'], Labels::LOCATIONS)) {
            $invalid['location'] = true;
            $errors[] = 'Bitte den Ort des Schmerzes wählen.';
        }
        if ($p['intensity'] === null) {
            $invalid['intensity'] = true;
            $errors[] = 'Bitte die Stärke des Schmerzes (0–10) wählen.';
        }
        if (!array_key_exists($p['timing'], Labels::TIMINGS)) {
            $invalid['timing'] = true;
            $errors[] = 'Bitte angeben, wann der Schmerz auftrat.';
        }

        return $errors;
    }

    /** @param array<string, mixed> $session */
    private function standOf(array $session): string
    {
        return self::stand(['status' => $session['status'], 'execution' => $this->feedback()->execution((int) $session['id'])]);
    }

    /**
     * @param array<string, mixed> $session
     * @param ?array<string, mixed> $activity
     * @param array<string, mixed> $data
     * @param array<string, bool> $invalid
     */
    private function form(array $session, ?array $activity, array $data, array $invalid, ?string $message, int $status = 200): Response
    {
        [$statusLabel, $statusClass] = Labels::STATUS[$session['status']];

        return $this->page('session', 'Einheit', 'woche', [
            'wide' => true,
            'backHref' => '/woche?start=' . Dates::monday((string) $session['date']),
            'backLabel' => 'Zurück zur Woche',
            'topAction' => '<span class="badge badge-' . $statusClass . ' hide-mobile">' . $statusLabel . '</span>',
            'alert' => $message === null ? null : ($status === 409
                ? ['type' => 'warning', 'icon' => 'alert-triangle', 'title' => 'Inzwischen geändert.', 'text' => $message]
                : ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => $message]),
            'stand' => $this->standOf($this->weeks()->session((int) $session['id']) ?? $session),
            'offlineLabel' => 'Rückmeldung „' . $session['title'] . '“ (' . Dates::short((string) $session['date']) . ')',
            'session' => $session,
            'activity' => $activity,
            'data' => $data,
            'invalid' => $invalid,
            'athleteId' => (string) $this->app->config()->get('INTERVALS_ATHLETE_ID', ''),
        ], $status);
    }
}
