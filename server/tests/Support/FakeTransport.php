<?php

declare(strict_types=1);

namespace Training\Tests\Support;

use Training\Intervals\HttpTransport;

/** Simuliert Intervals.icu: Antworten je "METHODE pfad" (ohne Query), protokolliert alle Anfragen. */
final class FakeTransport implements HttpTransport
{
    /** @var list<array{method: string, url: string, headers: array<string, string>, body: ?string}> */
    public array $requests = [];

    /** @param array<string, list<array{status: int, body: string, headers?: array<string, string>}>> $responses */
    public function __construct(public array $responses = [])
    {
    }

    public function request(string $method, string $url, array $headers, ?string $body, int $timeout): array
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
        $key = $method . ' ' . (string) parse_url($url, PHP_URL_PATH);
        $queue = $this->responses[$key] ?? [];
        if ($queue === []) {
            return ['status' => 404, 'body' => '{"error":"not mocked: ' . $key . '"}', 'headers' => []];
        }
        $response = count($queue) > 1 ? array_shift($this->responses[$key]) : $queue[0];

        return ['status' => $response['status'], 'body' => $response['body'], 'headers' => $response['headers'] ?? []];
    }
}
