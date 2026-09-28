<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Dates;
use Training\Http\Request;
use Training\Http\Response;
use Training\View\Labels;

/** S5 Schmerz (Abschnitt 10): Kurzformular für ein Schmerzereignis; mehrere pro Tag möglich. */
final class PainController extends AppController
{
    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $today = $this->today();
        $date = $request->method === 'POST' ? $request->post('datum') : $request->query('datum');
        $date = Dates::isDate($date) && $date <= $today ? (string) $date : $today;
        $sessions = array_values(array_filter(
            $this->weeks()->sessions(Dates::addDays($date, -13), $date),
            static fn (array $s): bool => $s['type'] !== 'ruhe',
        ));

        if ($request->method !== 'POST') {
            $sessionId = self::intField($request->query('einheit'), 1, PHP_INT_MAX);

            return $this->form($date, $sessions, ['session_id' => $sessionId, 'pain_fields' => [], 'notes' => ''], [], null);
        }
        if (!$this->csrfOk($request)) {
            return Response::error(403, 'Ungültiges Formular.');
        }
        if ($locked = $this->lockedResponse()) {
            return $locked;
        }

        $invalid = [];
        $fields = [
            'location' => (string) $request->post('pain_location'),
            'side' => (string) $request->post('pain_side'),
            'intensity' => self::intField($request->post('pain_intensity'), 0, 10),
            'timing' => (string) $request->post('pain_timing'),
        ];
        $errors = SessionController::validatePain($fields, $invalid);
        $sessionId = self::intField($request->post('session_id'), 1, PHP_INT_MAX);
        if ($sessionId !== null && !in_array($sessionId, array_map(static fn (array $s): int => (int) $s['id'], $sessions), true)) {
            $sessionId = null;
        }
        $notes = self::textField($request->post('notes'), 1000);
        if ($errors !== []) {
            return $this->form($date, $sessions, ['session_id' => $sessionId, 'pain_fields' => $fields, 'notes' => (string) $notes], $invalid, implode(' ', $errors), 422);
        }

        $pain = [
            'date' => $date, 'session_id' => $sessionId, 'location' => $fields['location'],
            'side' => array_key_exists($fields['side'], Labels::SIDES) ? $fields['side'] : 'na',
            'intensity_0_10' => (int) $fields['intensity'], 'timing' => $fields['timing'], 'notes' => $notes,
        ];
        $pdo = $this->app->pdo();
        $pdo->beginTransaction();
        try {
            $id = $this->feedback()->addPain($pain);
            $this->audit()->write('web', 'pain_create', 'pain_event', $id, $pain, 'Schmerz ' . $pain['location'] . ' ' . $pain['intensity_0_10'] . '/10 am ' . $date);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Hinweis nach dem Speichern (Mockup S5): wiederholte Meldung, starker Schmerz oder Schmerz in Ruhe.
        $count = $this->feedback()->painCount14($pain['location'], $date);
        if ($count >= 3 || $pain['intensity_0_10'] > 5 || $pain['timing'] === 'ruhe') {
            $title = $count >= 2 ? self::ordinal($count) . ' Meldung an dieser Stelle in 14 Tagen.' : 'Schmerzereignis gespeichert.';

            return $this->form($date, $sessions, ['session_id' => null, 'pain_fields' => [], 'notes' => ''], [], null, 200, [
                'type' => 'warning', 'icon' => 'alert-triangle', 'title' => $title,
                'text' => 'Claude wird das bei der nächsten Wochenplanung berücksichtigen. Bei Stärke über 5 oder Schmerz in Ruhe bitte ärztlich abklären.',
            ]);
        }

        return Response::redirect('/woche?start=' . Dates::monday($date) . '&ok=schmerz');
    }

    private static function ordinal(int $n): string
    {
        return [2 => 'Zweite', 3 => 'Dritte', 4 => 'Vierte', 5 => 'Fünfte'][$n] ?? $n . '.';
    }

    /**
     * @param list<array<string, mixed>> $sessions
     * @param array<string, mixed> $data
     * @param array<string, bool> $invalid
     * @param ?array<string, string> $notice
     */
    private function form(string $date, array $sessions, array $data, array $invalid, ?string $message, int $status = 200, ?array $notice = null): Response
    {
        return $this->page('pain', 'Schmerz', 'checkin', [
            'backHref' => '/checkin?datum=' . $date,
            'backLabel' => 'Zurück zum Check-in',
            'alert' => $message === null ? $notice : ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => $message],
            'saved' => $notice !== null,
            'date' => $date,
            'today' => $this->today(),
            'sessions' => array_reverse($sessions),
            'data' => $data,
            'invalid' => $invalid,
        ], $status);
    }
}
