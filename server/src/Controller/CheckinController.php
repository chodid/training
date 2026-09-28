<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Checkin\MorningChecks;
use Training\Data\SettingsRepository;
use Training\Dates;
use Training\Http\Request;
use Training\Http\Response;
use Training\View\Labels;

/**
 * S4 Check-in (D-16) mit Morgen-Check-in (AP-12, D-53): Morgentest Patellasehne links/rechts (NRS 0–10, leer = nicht
 * erhoben), Erholung 1–5 und Muskelkater 1–5 (Pflicht), weitere Angaben (Nacken/BWS, Sprunggelenk links, Hand rechts,
 * Warnzeichen, Schmerz → Kurzform S5, Notiz); ein Eintrag pro Tag, erneutes Speichern ersetzt ihn.
 */
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
            return $this->form($date, self::dataFrom($existing), [], null, $existing !== null);
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

        // Morgen-Check-in: leer = nicht erhoben (E-02), sonst 0–10
        $morning = [];
        foreach (['mt_links' => 'Morgentest links', 'mt_rechts' => 'Morgentest rechts', 'nacken_bws' => 'Nacken/BWS', 'hand_rechts' => 'Hand rechts'] as $field => $label) {
            $raw = trim((string) $request->post($field));
            $morning[$field] = $raw === '' ? null : self::intField($raw, 0, 10);
            if ($raw !== '' && $morning[$field] === null) {
                $invalid[$field] = true;
                $errors[] = $label . ': bitte einen Wert von 0 bis 10 wählen.';
            }
        }
        if (!self::handVisible($this->settings(), $date)) {
            $morning['hand_rechts'] = null;
        }
        $morning['osg_umgeknickt'] = $request->post('osg_umgeknickt') === '1';
        $morning['osg_schwellung'] = $morning['osg_umgeknickt'] && $request->post('osg_schwellung') === '1';
        $warn = array_values(array_unique(array_filter($request->postArray('warnzeichen'), 'is_string')));
        $morning['warnzeichen'] = array_values(array_filter($warn, static fn (string $w): bool => isset(Labels::WARNINGS[$w])));

        $data = [
            'recovery' => $recovery, 'soreness' => $soreness, 'pain_choice' => $painYes ? 'ja' : 'nein',
            'pain_fields' => $painFields, 'notes' => (string) $notes,
        ] + $morning;
        if (self::standConflict($request, self::stand($existing))) {
            return $this->form($date, $data, $invalid, 'Der Check-in für diesen Tag wurde inzwischen geändert (z. B. auf einem anderen Gerät). Deine Eingaben stehen unten; Speichern übernimmt sie.', true, 409);
        }
        if ($errors !== []) {
            return $this->form($date, $data, $invalid, implode(' ', $errors), $existing !== null, 422);
        }

        $pdo = $this->app->pdo();
        $pdo->beginTransaction();
        try {
            $id = $this->feedback()->saveCheckin($date, (int) $recovery, (int) $soreness, $painYes, $notes, $morning);
            $mt = static fn (?int $v): string => $v === null ? '–' : (string) $v;
            $this->audit()->write('web', $existing === null ? 'checkin_create' : 'checkin_update', 'checkin', $id,
                ['date' => $date, 'recovery' => $recovery, 'soreness' => $soreness, 'pain' => $painYes, 'notes' => $notes] + $morning,
                sprintf('Check-in %s: Morgentest L %s / R %s, Erholung %d, Muskelkater %d, Schmerz %s%s', $date, $mt($morning['mt_links']), $mt($morning['mt_rechts']),
                    $recovery, $soreness, $painYes ? 'ja' : 'nein', $morning['warnzeichen'] !== [] ? ', Warnzeichen: ' . implode(', ', $morning['warnzeichen']) : ''));
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

        return self::saved($request, '/woche?start=' . Dates::monday($date) . '&ok=checkin');
    }

    /**
     * Formularwerte aus einem gespeicherten Check-in (oder leer, ohne Vorauswahl – E-02).
     * @param ?array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function dataFrom(?array $row): array
    {
        $int = static fn (mixed $v): ?int => $v === null || $v === '' ? null : (int) $v;

        return [
            'recovery' => $int($row['recovery_1_5'] ?? null),
            'soreness' => $int($row['soreness_1_5'] ?? null),
            'pain_choice' => 'nein',
            'pain_fields' => [],
            'notes' => (string) ($row['notes'] ?? ''),
            'mt_links' => $int($row['mt_links'] ?? null),
            'mt_rechts' => $int($row['mt_rechts'] ?? null),
            'nacken_bws' => $int($row['nacken_bws'] ?? null),
            'hand_rechts' => $int($row['hand_rechts'] ?? null),
            'osg_umgeknickt' => (bool) ($row['osg_umgeknickt'] ?? false),
            'osg_schwellung' => (bool) ($row['osg_schwellung'] ?? false),
            'warnzeichen' => MorningChecks::warnings($row['warnzeichen'] ?? null),
        ];
    }

    /** „Hand rechts“ nur bis zum eingestellten Datum abfragen (E-08). */
    public static function handVisible(SettingsRepository $settings, string $date): bool
    {
        return $date <= $settings->handRechtsBis();
    }

    /**
     * Werte für das Formular-Partial _checkin_form.php (auch auf der Wochenansicht).
     * @return array<string, mixed>
     */
    public function formVars(string $date): array
    {
        $existing = $this->feedback()->checkin($date);

        return [
            'date' => $date,
            'data' => self::dataFrom($existing),
            'invalid' => [],
            'stand' => self::stand($existing),
            'offlineLabel' => 'Check-in ' . Dates::short($date),
            'handVisible' => self::handVisible($this->settings(), $date),
        ];
    }

    private function settings(): SettingsRepository
    {
        return new SettingsRepository($this->app->pdo(), $this->app->clock());
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
        $morning = new MorningChecks($this->app->pdo(), $this->app->clock(), $this->session->tz ?? 'Europe/Berlin');

        return $this->page('checkin', 'Check-in', 'checkin', [
            'alert' => $message === null ? null : ($status === 409
                ? ['type' => 'warning', 'icon' => 'alert-triangle', 'title' => 'Inzwischen geändert.', 'text' => $message]
                : ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Nicht gespeichert.', 'text' => $message]),
            'stand' => self::stand($this->feedback()->checkin($date)),
            'offlineLabel' => 'Check-in ' . Dates::short($date),
            'handVisible' => self::handVisible($this->settings(), $date),
            'summary' => $exists && $status === 200 ? $morning->summary($date) : null,
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
