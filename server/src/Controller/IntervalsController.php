<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\App;
use Training\Http\Request;
use Training\Http\Response;
use Training\Intervals\HttpTransport;
use Training\Intervals\IntervalsClient;
use Training\Intervals\IntervalsException;
use Training\Intervals\TestWorkout;

/**
 * /intervals – Verbindungstest Intervals.icu (AP-02), nur nach Login: Athlet, Aktivitäten und Wellness der
 * letzten 7 Tage, Events der nächsten 14 Tage; Test-Event anlegen, ändern, löschen (Abnahme auf der Uhr).
 * Wird mit AP-04 in die Einstellungen (S8, Bereich Verbindungen) übernommen.
 */
final class IntervalsController
{
    public function __construct(private readonly App $app, private readonly ?HttpTransport $transport = null)
    {
    }

    public function handle(Request $request): Response
    {
        $session = $this->app->sessions()->current($request);
        if ($session === null) {
            return Response::redirect('/login?next=' . rawurlencode('/intervals'));
        }
        $config = $this->app->config();
        $vars = ['title' => 'Intervals.icu', 'login' => $session->login, 'csrf' => $session->csrfToken(), 'host' => $this->app->host(), 'alert' => null];

        if (!IntervalsClient::isConfigured($config)) {
            return $this->page([...$vars, 'alert' => [
                'type' => 'warning', 'icon' => 'plug-connected', 'title' => 'Nicht eingerichtet.',
                'text' => 'INTERVALS_API_KEY und INTERVALS_ATHLETE_ID in der .env eintragen (Intervals.icu → Einstellungen → Developer Settings).',
            ]]);
        }
        $client = $this->transport !== null ? IntervalsClient::fromConfig($config, $this->transport) : IntervalsClient::fromConfig($config);
        $tz = new \DateTimeZone($session->tz);
        $today = (new \DateTimeImmutable('@' . $this->app->clock()->now()))->setTimezone($tz);
        $tomorrow = $today->modify('+1 day')->format('Y-m-d');

        try {
            if ($request->method === 'POST') {
                if (!$session->verifyCsrf($request->post('csrf'))) {
                    return Response::error(403, 'Ungültiges Formular.');
                }
                $vars['alert'] = $this->action((string) $request->post('action'), $client, $today->format('Y-m-d'), $today->modify('+14 day')->format('Y-m-d'), $tomorrow);
            }

            $athlete = $client->athlete();
            $activities = $client->activities($today->modify('-6 day')->format('Y-m-d'), $today->format('Y-m-d'));
            $wellness = $client->wellness($today->modify('-6 day')->format('Y-m-d'), $today->format('Y-m-d'));
            $events = $client->events($today->format('Y-m-d'), $today->modify('+14 day')->format('Y-m-d'));
        } catch (IntervalsException $e) {
            return $this->page([...$vars, 'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Intervals.icu-Fehler.', 'text' => $e->getMessage()]], 502);
        }

        return $this->page([
            ...$vars,
            'athlete' => (string) ($athlete['name'] ?? $athlete['id'] ?? '?'),
            'activities' => $activities,
            'wellness' => $wellness,
            'events' => $events,
            'testEvents' => TestWorkout::find($events),
            'tomorrow' => $tomorrow,
        ]);
    }

    /** @return array<string, string> */
    private function action(string $action, IntervalsClient $client, string $from, string $to, string $tomorrow): array
    {
        $existing = TestWorkout::find($client->events($from, $to));
        switch ($action) {
            case 'create':
                if ($existing !== []) {
                    return ['type' => 'info', 'icon' => 'info-circle', 'title' => 'Test-Event vorhanden.', 'text' => 'Es gibt bereits ein Test-Event; erst löschen, dann neu anlegen.'];
                }
                $event = $client->createEvent(TestWorkout::event($tomorrow));

                return ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Test-Event angelegt.', 'text' => 'Event ' . ($event['id'] ?? '?') . ' für ' . $tomorrow . '. Auf der Uhr prüfen (Garmin Connect synchronisieren).'];
            case 'update':
                foreach ($existing as $event) {
                    $client->updateEvent((int) $event['id'], ['name' => TestWorkout::NAME . ' (geändert)']);
                }

                return ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Test-Event geändert.', 'text' => count($existing) . ' Event(s) umbenannt. Prüfen, ob die Uhr den neuen Namen übernimmt.'];
            case 'delete':
                foreach ($existing as $event) {
                    $client->deleteEvent((int) $event['id']);
                }

                return ['type' => 'success', 'icon' => 'circle-check', 'title' => 'Test-Event gelöscht.', 'text' => count($existing) . ' Event(s) gelöscht. Prüfen, ob es von der Uhr verschwindet.'];
            default:
                return ['type' => 'error', 'icon' => 'alert-circle', 'title' => 'Unbekannte Aktion.', 'text' => ''];
        }
    }

    /** @param array<string, mixed> $vars */
    private function page(array $vars, int $status = 200): Response
    {
        return Response::html($status, $this->app->view()->render('intervals', $vars));
    }
}
