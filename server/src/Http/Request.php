<?php

declare(strict_types=1);

namespace Training\Http;

final class Request
{
    /** @param array<string, string> $headers Schlüssel in Kleinbuchstaben */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $headers = [],
        public readonly bool $https = false,
    ) {
    }

    /** @param array<string, mixed> $server */
    public static function fromGlobals(array $server): self
    {
        $path = parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $headers = [];
        foreach ($server as $key => $value) {
            if (is_string($value) && str_starts_with((string) $key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr((string) $key, 5)))] = $value;
            }
        }
        if (!isset($headers['authorization']) && is_string($server['REDIRECT_HTTP_AUTHORIZATION'] ?? null)) {
            $headers['authorization'] = $server['REDIRECT_HTTP_AUTHORIZATION'];
        }

        $https = (($server['HTTPS'] ?? '') !== '' && strtolower((string) $server['HTTPS']) !== 'off')
            || strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

        return new self(strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET')), $path, $headers, $https);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
