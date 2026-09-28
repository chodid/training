<?php

declare(strict_types=1);

namespace Training\Intervals;

/** Minimaler HTTP-Transport (austauschbar für Tests). */
interface HttpTransport
{
    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string, headers: array<string, string>}
     * @throws IntervalsException bei Netzwerkfehlern
     */
    public function request(string $method, string $url, array $headers, ?string $body, int $timeout): array;
}
