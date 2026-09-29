<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Dates;
use Training\Http\Request;
use Training\Http\Response;
use Training\Intervals\ActivityLookup;
use Training\Intervals\IntervalsClient;
use Training\Plan\Ablaufplan;

/** S2 Woche (Abschnitt 10): 7 Tage mit Einheiten, Check-in-Status, ±Woche, sRPE-Summe, offene Rückmeldungen. */
final class WeekController extends AppController
{
    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        $today = $this->today();
        $start = $request->query('start');
        $monday = Dates::monday(Dates::isDate($start) ? (string) $start : $today);
        $sunday = Dates::addDays($monday, 6);

        $week = $this->weeks()->week($monday);
        $sessions = $this->weeks()->sessions($monday, $sunday);
        $checkins = $this->feedback()->checkins($monday, $sunday);

        // Ausdauer: verknüpfte Aktivität aus Intervals.icu (nur für vergangene/heutige Tage).
        $lookup = $this->activityLookup();
        $activities = $monday <= $today ? $lookup->activities($monday, min($sunday, $today)) : [];
        $taken = [];
        foreach ($sessions as &$s) {
            $s['activity'] = null;
            if ($s['type'] === 'ausdauer' && $activities !== []) {
                $s['activity'] = ActivityLookup::match($s, $activities, $taken);
                if ($s['activity'] !== null) {
                    $taken[] = (string) ($s['activity']['id'] ?? '');
                }
            }
        }
        unset($s);

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Dates::addDays($monday, $i);
            $days[$date] = ['date' => $date, 'sessions' => [], 'checkin' => $checkins[$date] ?? null];
        }
        $srpe = 0;
        $done = 0;
        $total = 0;
        $openFeedback = [];
        foreach ($sessions as $s) {
            $days[$s['date']]['sessions'][] = $s;
            if ($s['type'] === 'ruhe') {
                continue;
            }
            $total++;
            $srpe += (int) ($s['srpe_load'] ?? 0);
            if (in_array($s['status'], ['erledigt', 'teilweise'], true)) {
                $done++;
                if ($s['rpe_cr10'] === null) {
                    $openFeedback[] = $s;
                }
            }
        }
        $elapsed = $today < $monday ? 0 : ($today > $sunday ? 7 : Dates::weekdayIndex($today) + 1);

        $prev = Dates::addDays($monday, -7);
        $next = Dates::addDays($monday, 7);

        return $this->page('week', 'Woche', 'woche', [
            'wide' => true,
            'topAction' => '<a class="btn btn-ghost hide-mobile" href="/checkin">' . $this->app->view()->icon('checkup-list') . 'Check-in heute</a>',
            'alert' => $this->notice($request),
            'monday' => $monday,
            'today' => $today,
            'week' => $week,
            'days' => $days,
            'hasSessions' => $sessions !== [],
            'srpe' => $srpe,
            'done' => $done,
            'total' => $total,
            'checkinCount' => count(array_filter($checkins, static fn (array $c): bool => $c['date'] <= $today)),
            'elapsed' => $elapsed,
            'openFeedback' => $openFeedback,
            'prev' => $prev,
            'next' => $next,
            'prefetch' => $monday === Dates::monday($today) ? $this->prefetch($monday, $next, $today, $sessions) : [],
            'morning' => $monday === Dates::monday($today) ? $this->morning($today) : null,
            'blockCard' => $monday === Dates::monday($today) ? $this->blockCard($today) : null,
            'intervalsError' => $lookup->error,
            'mailError' => $this->app->mailBackup()->state()['error'] ?? null,
        ]);
    }

    /**
     * Seiten, die der Service Worker für die Offline-Nutzung vorlädt (D-45): aktuelle und nächste Woche mit ihren
     * Einheiten, Check-in und Schmerz für heute, dazu die geführte Einheit (S9) für heutige und morgige geeignete
     * Einheiten (AP-14, 6.7) und die Übungsseiten (S10) aller Einheiten beider Wochen (AP-16, E-15). Nur in der Ansicht
     * der aktuellen Woche.
     * @param list<array<string, mixed>> $sessions
     * @return list<string>
     */
    private function prefetch(string $monday, string $next, string $today, array $sessions): array
    {
        $urls = ['/woche', '/woche?start=' . $monday, '/woche?start=' . $next, '/checkin', '/checkin?datum=' . $today, '/schmerz', '/schmerz?datum=' . $today];
        $tomorrow = Dates::addDays($today, 1);
        foreach ([...$sessions, ...$this->weeks()->sessions($next, Dates::addDays($next, 6))] as $s) {
            if ($s['type'] !== 'ruhe') {
                $urls[] = '/einheit?id=' . (int) $s['id'];
            }
            $guided = in_array($s['date'], [$today, $tomorrow], true) && Ablaufplan::geeignet((string) $s['type'], $s['plan'] ?? null);
            if ($guided) {
                $urls[] = '/einheit?id=' . (int) $s['id'] . '&modus=start';
            }
            // Übungsseiten (AP-16, E-15) mit derselben Adresse wie die Links aus S3 bzw. S9
            foreach (\Training\Data\ExerciseRepository::slugsInPlan($s['plan'] ?? null) as $slug) {
                $urls[] = '/uebung?id=' . rawurlencode($slug) . '&von=' . (int) $s['id'];
                if ($guided) {
                    $urls[] = '/uebung?id=' . rawurlencode($slug) . '&von=' . (int) $s['id'] . '&modus=start';
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Morgen-Check-in auf der Startseite (AP-12, T3): Formular, solange heute kein Morgentest erfasst ist, danach die
     * Zusammenfassung mit Ampel (6.2).
     * @return array{summary: ?array<string, mixed>, form: ?array<string, mixed>}
     */
    private function morning(string $today): array
    {
        $checkin = $this->feedback()->checkin($today);
        $hasTest = $checkin !== null && ($checkin['mt_links'] !== null || $checkin['mt_rechts'] !== null);
        if ($hasTest) {
            return ['summary' => (new \Training\Checkin\MorningChecks($this->app->pdo(), $this->app->clock(), $this->session->tz ?? 'Europe/Berlin'))->summary($today), 'form' => null];
        }

        return ['summary' => null, 'form' => (new CheckinController($this->app))->formVars($today)];
    }

    /**
     * Karte „Block“ unter dem Morgen-Check-in (AP-15, 6.2): aktiver Block mit Restlaufzeit und allen Fälligkeiten
     * (auch Revision, E-07). Fehler unterdrücken nur die Karte.
     * @return ?array{block: ?array<string, mixed>, rest: ?string, faellig: list<array<string, mixed>>}
     */
    private function blockCard(string $today): ?array
    {
        try {
            $pdo = $this->app->pdo();
            $block = $pdo->query("SELECT * FROM training_block WHERE status = 'aktiv' ORDER BY start_date DESC LIMIT 1")->fetch() ?: null;

            return [
                'block' => $block,
                'rest' => $block !== null ? self::restText($today, (string) $block['end_date']) : null,
                'faellig' => \Training\Review\Faelligkeit::load($pdo, $this->app->clock(), $today),
            ];
        } catch (\Throwable $e) {
            error_log('[training] Blockkarte: ' . $e->getMessage());

            return null;
        }
    }

    /** Restlaufzeit: „noch 3 Wochen“, „noch 5 Tage“, „endet heute“, „seit 2 Tagen beendet“. */
    public static function restText(string $today, string $end): string
    {
        $days = (int) ((strtotime($end) - strtotime($today)) / 86400);

        return match (true) {
            $days < 0 => $days === -1 ? 'seit gestern beendet' : 'seit ' . -$days . ' Tagen beendet',
            $days === 0 => 'endet heute',
            $days < 14 => 'noch ' . ($days + 1) . ' Tage',
            default => 'noch ' . intdiv($days + 1, 7) . ' Wochen',
        };
    }

    /** Aktivitäten aus dem Spiegel (D-43); ohne Intervals-Konfiguration nur der vorhandene Spiegel. */
    private function activityLookup(): ActivityLookup
    {
        $client = IntervalsClient::isConfigured($this->app->config()) ? $this->app->intervalsClient() : null;

        return new ActivityLookup($client, $this->app->pdo(), $this->app->clock());
    }

    /** @return ?array<string, string> Rückmeldung nach dem Speichern (Weiterleitung mit ?ok=…) */
    private function notice(Request $request): ?array
    {
        return match ($request->query('ok')) {
            'einheit' => match ($request->query('intervals')) {
                'ok' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Gespeichert.', 'text' => 'Die Rückmeldung ist erfasst und nach Intervals.icu übertragen.'],
                'fehler' => ['type' => 'warning', 'icon' => 'alert-triangle', 'title' => 'Gespeichert, aber nicht übertragen.', 'text' => 'Die Rückmeldung ist erfasst; die Übertragung nach Intervals.icu ist fehlgeschlagen. RPE und Gefühl werden beim nächsten Speichern erneut übertragen.'],
                default => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Gespeichert.', 'text' => 'Die Rückmeldung zur Einheit ist erfasst.'],
            },
            'checkin' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Check-in gespeichert.', 'text' => 'Der Tag ist erfasst.'],
            'schmerz' => ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Schmerzereignis gespeichert.', 'text' => ''],
            default => null,
        };
    }
}
