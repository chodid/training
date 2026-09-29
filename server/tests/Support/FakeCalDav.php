<?php

declare(strict_types=1);

namespace Training\Tests\Support;

use Training\Intervals\HttpTransport;
use Training\Intervals\IntervalsException;

/** Simuliert eine CalDAV-Kalendersammlung (Nextcloud): PUT/DELETE je Ressource, REPORT mit Zeitraum nach dem Tag von DTSTART. */
final class FakeCalDav implements HttpTransport
{
    /** @var array<string, string> Ressourcenname => iCalendar */
    public array $events = [];
    /** @var list<array{method: string, url: string, headers: array<string, string>}> */
    public array $requests = [];
    /** HTTP-Status für alle Anfragen erzwingen (z. B. 401) bzw. Netzfehler simulieren */
    public ?int $failStatus = null;
    public bool $networkDown = false;
    /** HTTP-Status nur für bestimmte Methoden erzwingen, z. B. ['DELETE' => 500] */
    public array $failMethod = [];
    /**
     * Nextcloud bis 34.0.1: Gelöschtes liegt im Papierkorb; eine gelöschte Adresse oder UID erneut anzulegen scheitert
     * (vereinfacht: schon beim ersten Mal) mit 403.
     */
    public bool $trash = false;
    /** @var list<string> gelöschte Ressourcennamen und UIDs (Papierkorb) */
    public array $trashed = [];

    public function request(string $method, string $url, array $headers, ?string $body, int $timeout): array
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers];
        if ($this->networkDown) {
            throw new IntervalsException('Kalender (CalDAV) nicht erreichbar: Connection refused');
        }
        if ($this->failStatus !== null) {
            return ['status' => $this->failStatus, 'body' => '', 'headers' => []];
        }
        if (isset($this->failMethod[$method])) {
            return ['status' => $this->failMethod[$method], 'body' => '', 'headers' => []];
        }
        $name = rawurldecode(basename((string) parse_url($url, PHP_URL_PATH)));
        switch ($method) {
            case 'PUT':
                $existed = isset($this->events[$name]);
                preg_match('/^UID:(.*)$/m', (string) $body, $uid);
                if ($this->trash && !$existed && array_intersect([$name, trim($uid[1] ?? '')], $this->trashed) !== []) {
                    return ['status' => 403, 'body' => '', 'headers' => []];
                }
                $this->events[$name] = (string) $body;

                return ['status' => $existed ? 204 : 201, 'body' => '', 'headers' => []];
            case 'DELETE':
                $existed = isset($this->events[$name]);
                if ($existed && $this->trash) {
                    preg_match('/^UID:(.*)$/m', $this->events[$name], $uid);
                    $this->trashed[] = $name;
                    $this->trashed[] = trim($uid[1] ?? '');
                }
                unset($this->events[$name]);

                return ['status' => $existed ? 204 : 404, 'body' => '', 'headers' => []];
            case 'REPORT':
                preg_match('/start="(\d{8})T/', (string) $body, $s);
                preg_match('/end="(\d{8})T/', (string) $body, $e);
                $xml = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:">';
                foreach ($this->events as $n => $ics) {
                    preg_match('/DTSTART(?:;VALUE=DATE)?:(\d{8})/', $ics, $d); // ganztägig oder mit Uhrzeit (Blocktermin, AP-15)
                    if (($d[1] ?? '') >= $s[1] && ($d[1] ?? '') < $e[1]) {
                        $xml .= '<d:response><d:href>/remote.php/dav/calendars/p/training/' . rawurlencode($n) . '</d:href></d:response>';
                    }
                }

                return ['status' => 207, 'body' => $xml . '</d:multistatus>', 'headers' => []];
        }

        return ['status' => 405, 'body' => '', 'headers' => []];
    }
}
