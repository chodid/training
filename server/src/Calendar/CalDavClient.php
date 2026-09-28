<?php

declare(strict_types=1);

namespace Training\Calendar;

use Training\Config;
use Training\ConfigException;
use Training\Intervals\CurlTransport;
use Training\Intervals\HttpTransport;
use Training\Intervals\IntervalsException;

/**
 * Minimaler CalDAV-Client (AP-11, D-50): Termine als einzelne .ics-Ressourcen in einer Kalender-Sammlung anlegen bzw.
 * ersetzen (PUT), löschen (DELETE) und die eigenen Ressourcen eines Zeitraums auflisten (REPORT calendar-query).
 * Getestet gegen das Verhalten von Nextcloud (Sabre/DAV); Anmeldung per HTTP Basic mit App-Passwort.
 */
final class CalDavClient
{
    private const TIMEOUT = 15;

    public function __construct(
        private readonly string $collectionUrl,
        private readonly string $user,
        private readonly string $password,
        private readonly HttpTransport $transport = new CurlTransport('Kalender (CalDAV)'),
    ) {
    }

    public static function isConfigured(Config $config): bool
    {
        return $config->get('CALDAV_URL') !== null && $config->get('CALDAV_USER') !== null && $config->get('CALDAV_PASSWORD') !== null;
    }

    /** @throws ConfigException wenn Werte fehlen oder die Adresse nicht https ist */
    public static function fromConfig(Config $config, ?HttpTransport $transport = null): self
    {
        $url = $config->require('CALDAV_URL');
        if (!str_starts_with(strtolower($url), 'https://')) {
            throw new ConfigException('CALDAV_URL muss mit https:// beginnen.', ['CALDAV_URL']);
        }
        $url = rtrim($url, '/') . '/';

        return $transport !== null
            ? new self($url, $config->require('CALDAV_USER'), $config->require('CALDAV_PASSWORD'), $transport)
            : new self($url, $config->require('CALDAV_USER'), $config->require('CALDAV_PASSWORD'));
    }

    public function host(): string
    {
        return (string) parse_url($this->collectionUrl, PHP_URL_HOST);
    }

    /** Legt die Ressource an oder ersetzt sie. */
    public function put(string $resource, string $ics): void
    {
        $r = $this->call('PUT', $resource, ['Content-Type' => 'text/calendar; charset=utf-8'], $ics);
        if (!in_array($r['status'], [200, 201, 204], true)) {
            throw new CalendarException($this->describe('PUT', $r['status']));
        }
    }

    /** Löscht die Ressource; fehlt sie schon, ist das kein Fehler. @return bool true, wenn es sie gab */
    public function delete(string $resource): bool
    {
        $r = $this->call('DELETE', $resource, [], null);
        if (!in_array($r['status'], [200, 204, 404], true)) {
            throw new CalendarException($this->describe('DELETE', $r['status']));
        }

        return $r['status'] !== 404;
    }

    /**
     * Namen der Ressourcen mit Terminen im Zeitraum (Datumsangaben inklusive), z. B. „training-tag-2026-09-30.ics“.
     * @return list<string>
     */
    public function resources(string $from, string $to): array
    {
        $start = str_replace('-', '', $from) . 'T000000Z';
        $end = str_replace('-', '', (new \DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d')) . 'T000000Z';
        $body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">'
            . '<d:prop><d:getetag/></d:prop>'
            . '<c:filter><c:comp-filter name="VCALENDAR"><c:comp-filter name="VEVENT">'
            . '<c:time-range start="' . $start . '" end="' . $end . '"/>'
            . '</c:comp-filter></c:comp-filter></c:filter></c:calendar-query>';
        $r = $this->call('REPORT', '', ['Content-Type' => 'application/xml; charset=utf-8', 'Depth' => '1'], $body);
        if ($r['status'] !== 207) {
            throw new CalendarException($this->describe('REPORT', $r['status']));
        }
        $names = [];
        if (preg_match_all('#<(?:[A-Za-z0-9]+:)?href>([^<]+)</(?:[A-Za-z0-9]+:)?href>#i', $r['body'], $m)) {
            foreach ($m[1] as $href) {
                $name = rawurldecode(basename(html_entity_decode(trim($href), ENT_XML1)));
                if (str_ends_with($name, '.ics')) {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string, headers: array<string, string>}
     */
    private function call(string $method, string $resource, array $headers, ?string $body): array
    {
        $headers['Authorization'] = 'Basic ' . base64_encode($this->user . ':' . $this->password);
        try {
            return $this->transport->request($method, $this->collectionUrl . rawurlencode($resource), $headers, $body, self::TIMEOUT);
        } catch (IntervalsException $e) {
            throw new CalendarException($e->getMessage(), 0, $e);
        }
    }

    private function describe(string $method, int $status): string
    {
        $hint = match (true) {
            $status === 401 => 'Anmeldung abgelehnt – CALDAV_USER und App-Passwort (CALDAV_PASSWORD) prüfen.',
            $status === 403 => 'Keine Schreibrechte auf den Kalender oder (Nextcloud) Konflikt mit einem Termin im Papierkorb des Kalenders – Rechte prüfen bzw. Papierkorb leeren.',
            $status === 404, $status === 405 => 'Kalender nicht gefunden – CALDAV_URL prüfen (Adresse des Kalenders, nicht der Nextcloud).',
            default => 'Server antwortete unerwartet.',
        };

        return 'Kalender ' . $this->host() . ': ' . $method . ' → HTTP ' . $status . '. ' . $hint;
    }
}
