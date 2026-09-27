<?php

declare(strict_types=1);

namespace Training\Intervals;

use Training\Config;
use Training\ConfigException;

/**
 * Serverseitiger Client für die Intervals.icu-API (Konzept Abschnitt 9, D-02, AP-02).
 * Auth: HTTP Basic mit Benutzer "API_KEY" und dem persönlichen Key; Key und Athleten-ID aus .env.
 * Endpunkte nach V-04 (Stand: aus öffentlichem Client-Quellcode abgeleitet, gegen die API zu bestätigen).
 * Datumsangaben: YYYY-MM-DD bzw. lokale Zeit YYYY-MM-DDTHH:MM:SS (start_date_local).
 */
final class IntervalsClient
{
    public const BASE_URL = 'https://intervals.icu/api/v1';
    private const TIMEOUT = 15;
    private const MAX_RETRIES = 1;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $athleteId,
        private readonly HttpTransport $transport = new CurlTransport(),
        private readonly string $baseUrl = self::BASE_URL,
        /** @var \Closure(int): void Wartefunktion für Wiederholungen (Tests ohne Pause) */
        private readonly ?\Closure $sleep = null,
    ) {
        if (!preg_match('/^[A-Za-z0-9]+$/', $athleteId)) {
            throw new ConfigException('INTERVALS_ATHLETE_ID ist ungültig (z. B. i12345).', ['INTERVALS_ATHLETE_ID']);
        }
    }

    public static function isConfigured(Config $config): bool
    {
        return $config->get('INTERVALS_API_KEY') !== null && $config->get('INTERVALS_ATHLETE_ID') !== null;
    }

    /** @throws ConfigException wenn Key oder Athleten-ID fehlen */
    public static function fromConfig(Config $config, HttpTransport $transport = new CurlTransport()): self
    {
        return new self($config->require('INTERVALS_API_KEY'), $config->require('INTERVALS_ATHLETE_ID'), $transport);
    }

    /** @return array<string, mixed> Athletenprofil (Name, Zeitzone, Sporteinstellungen) */
    public function athlete(): array
    {
        return $this->object($this->call('GET', $this->athletePath()));
    }

    /** @return list<array<string, mixed>> Geplante Einheiten, Notizen usw. im Zeitraum */
    public function events(string $oldest, string $newest, ?string $category = null): array
    {
        $query = ['oldest' => self::date($oldest), 'newest' => self::date($newest)];
        if ($category !== null) {
            $query['category'] = $category;
        }

        return $this->list($this->call('GET', $this->athletePath('/events'), $query));
    }

    /** @param array<string, mixed> $event @return array<string, mixed> */
    public function createEvent(array $event): array
    {
        return $this->object($this->call('POST', $this->athletePath('/events'), [], $event));
    }

    /** @param array<string, mixed> $changes @return array<string, mixed> */
    public function updateEvent(int $eventId, array $changes): array
    {
        return $this->object($this->call('PUT', $this->athletePath('/events/' . $eventId), [], $changes));
    }

    public function deleteEvent(int $eventId): void
    {
        $this->call('DELETE', $this->athletePath('/events/' . $eventId));
    }

    /** @return list<array<string, mixed>> Aktivitäten (Zusammenfassungen, keine Streams) im Zeitraum */
    public function activities(string $oldest, string $newest): array
    {
        return $this->list($this->call('GET', $this->athletePath('/activities'), ['oldest' => self::date($oldest), 'newest' => self::date($newest)]));
    }

    /** @return list<array<string, mixed>> Wellness-Einträge je Tag im Zeitraum */
    public function wellness(string $oldest, string $newest): array
    {
        return $this->list($this->call('GET', $this->athletePath('/wellness'), ['oldest' => self::date($oldest), 'newest' => self::date($newest)]));
    }

    /**
     * @param array<string, string> $query
     * @param array<string, mixed>|list<mixed>|null $json
     */
    private function call(string $method, string $path, array $query = [], ?array $json = null): mixed
    {
        $url = rtrim($this->baseUrl, '/') . $path . ($query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        $headers = [
            'Authorization' => 'Basic ' . base64_encode('API_KEY:' . $this->apiKey),
            'Accept' => 'application/json',
            'User-Agent' => 'training.gen-em.org',
        ];
        $body = null;
        if ($json !== null) {
            $headers['Content-Type'] = 'application/json';
            $body = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        for ($attempt = 0; ; $attempt++) {
            $response = $this->transport->request($method, $url, $headers, $body, self::TIMEOUT);
            $status = $response['status'];
            if (($status === 429 || $status >= 500) && $attempt < self::MAX_RETRIES) {
                $wait = max(1, min(5, (int) ($response['headers']['retry-after'] ?? 1)));
                ($this->sleep ?? static fn (int $s) => sleep($s))($wait);
                continue;
            }
            break;
        }

        if ($status < 200 || $status >= 300) {
            throw new IntervalsException(self::errorMessage($method, $path, $status, $response['body']), $status);
        }
        if ($response['body'] === '' || $method === 'DELETE') {
            return null;
        }
        try {
            return json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new IntervalsException('Intervals.icu lieferte kein gültiges JSON (' . $method . ' ' . $path . ').', $status);
        }
    }

    private function athletePath(string $suffix = ''): string
    {
        return '/athlete/' . rawurlencode($this->athleteId) . $suffix;
    }

    private static function errorMessage(string $method, string $path, int $status, string $body): string
    {
        $hint = match (true) {
            $status === 401, $status === 403 => 'API-Key oder Athleten-ID prüfen (INTERVALS_API_KEY, INTERVALS_ATHLETE_ID).',
            $status === 404 => 'Nicht gefunden.',
            $status === 429 => 'Zu viele Anfragen (Rate-Limit).',
            default => '',
        };
        $detail = '';
        $data = json_decode($body, true);
        if (is_array($data)) {
            $detail = (string) ($data['error'] ?? $data['message'] ?? '');
        }

        return trim(sprintf('Intervals.icu %s %s → HTTP %d. %s %s', $method, $path, $status, $hint, mb_substr($detail, 0, 200)));
    }

    private static function date(string $date): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException('Datum im Format YYYY-MM-DD erwartet: ' . $date);
        }

        return $date;
    }

    /** @return array<string, mixed> */
    private function object(mixed $data): array
    {
        if (!is_array($data)) {
            throw new IntervalsException('Intervals.icu: unerwartete Antwort (Objekt erwartet).');
        }

        return $data;
    }

    /** @return list<array<string, mixed>> */
    private function list(mixed $data): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw new IntervalsException('Intervals.icu: unerwartete Antwort (Liste erwartet).');
        }

        return array_values(array_filter($data, 'is_array'));
    }
}
