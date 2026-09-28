<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Dates;
use Training\Http\Request;
use Training\Http\Response;
use Training\View\Labels;

/** S4 Check-in (D-16): Erholung 1–5, Muskelkater 1–5, Schmerz ja/nein (→ Kurzform S5), Notiz; ein Eintrag pro Tag. */
final class CheckinController extends AppController
{
    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $today = $this->today();
        $date = $request->method === 'POST' ? $request->post('datum') : $request->query('datum');
        $date = Dates::isDate($date) && $date <= $today ? (string) $date : $today;
        $existing = $this->feedback()->checkin($date);

        if ($request->method !== 'POST') {
            return $this->form($date, [
                'recovery' => $existing['recovery_1_5'] ?? null,
                'soreness' => $existing['soreness_1_5'] ?? null,
                'pain_choice' => 'nein',
                'pain_fields' => [],
                'notes' => $existing['notes'] ?? '',
            ], [], null, $existing !== null);
        }
        if (!$this->csrfOk($request)) {
            return Response::error(403, 'Ungültiges Formular.');
        }
        if ($locked = $this->lockedResponse()) {
            return $locked;
        }

        $invalid = [];
        $errors = [];
        $recovery = self::intField($request->post('recovery'), 1, 5);
        $soreness = self::intField($request->post('soreness'), 1, 5);
        if ($recovery === null) {
            $invalid['recovery'] = true;
            $errors[] = 'Bitte die Erholung (1–5) wählen.';
        }
        if ($soreness === null) {
            $invalid['soreness'] = true;
            $errors[] = 'Bitte den Muskelkater (1–5) wählen.';
        }
        $painYes = $request->post('pain') === 'ja';
        $painFields = [
            'location' => (string) $request->post('pain_location'),
            'side' => (string) $request->post('pain_side'),
            'intensity' => self::intField($request->post('pain_intensity'), 0, 10),
            'timing' => (string) $request->post('pain_timing'),
        ];
        if ($painYes) {
            $errors = [...$errors, ...SessionController::validatePain($painFields, $invalid)];
        }
        $notes = self::textField($request->post('notes'), 500);
        if ($errors !== []) {
            return $this->form($date, [
                'recovery' => $recovery, 'soreness' => $soreness, 'pain_choice' => $painYes ? 'ja' : 'nein',
                'pain_fields' => $painFields, 'notes' => (string) $notes,
            ], $invalid, implode(' ', $errors), $existing !== null, 422);
        }

        $pdo = $this->app->pdo();
        $pdo->beginTransaction();
        try {
            $id = $this->feedback()->saveCheckin($date, (int) $recovery, (int) $soreness, $painYes, $notes);
            $this->audit()->write('web', $existing === null ? 'checkin_create' : 'checkin_update', 'checkin', $id,
                ['date' => $date, 'recovery' => $recovery, 'soreness' => $soreness, 'pain' => $painYes, 'notes' => $notes],
                sprintf('Check-in %s: Erholung %d, Muskelkater %d, Schmerz %s', $date, $recovery, $soreness, $painYes ? 'ja' : 'nein'));
            if ($painYes) {
                $pain = [
                    'date' => $date, 'session_id' => null, 'location' => $painFields['location'],
                    'side' => array_key_exists($painFields['side'], Labels::SIDES) ? $painFields['side'] : 'na',
                    'intensity_0_10' => (int) $painFields['intensity'], 'timing' => $painFields['timing'], 'notes' => null,
                ];
                $painId = $this->feedback()->addPain($pain);
                $this->audit()->write('web', 'pain_create', 'pain_event', $painId, $pain, 'Schmerz ' . $pain['location'] . ' ' . $pain['intensity_0_10'] . '/10 (Check-in ' . $date . ')');
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return Response::redirect('/woche?start=' . Dates::monday($date) . '&ok=checkin');
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, bool> $invalid
     */
    private function form(string $date, array $data, array $invalid, ?string $message, bool $exists, int $status = 200): Response
    {
        $monday = Dates::monday($date);
        $today = $this->today();
        $week = $this->feedback()->checkins($monday, min(Dates::addDays($monday, 6), $today));
        $pains = [];
        foreach ($this->feedback()->pains($monday, Dates::addDays($monday, 6)) as $p) {
            $pains[(string) $p['date']][] = $p;
        }
        $elapsed = min(7, Dates::weekdayIndex(min($today, Dates::addDays($monday, 6))) + 1);

        return $this->page('checkin', 'Check-in', 'checkin', [
            'alert' => $message === null ? null : ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => $message],
            'date' => $date,
            'today' => $today,
            'data' => $data,
            'invalid' => $invalid,
            'exists' => $exists,
            'week' => $week,
            'pains' => $pains,
            'elapsed' => $elapsed,
        ], $status);
    }
}
