<?php

declare(strict_types=1);

namespace Training\Http;

final class Request
{
    /**
     * @param array<string, string> $headers Schlüssel in Kleinbuchstaben
     * @param array<string, mixed>  $query   GET-Parameter
     * @param array<string, mixed>  $post    Formularfelder (application/x-www-form-urlencoded)
     * @param array<string, string> $cookies
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $headers = [],
        public readonly bool $https = false,
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $cookies = [],
        public readonly string $body = '',
        public readonly string $uri = '',
    ) {
    }

    /**
     * @param array<string, mixed> $server
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $cookies
     */
    public static function fromGlobals(array $server, array $query = [], array $post = [], array $cookies = [], string $body = ''): self
    {
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
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
        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $key => $name) {
            if (is_string($server[$key] ?? null) && $server[$key] !== '') {
                $headers[$name] = $server[$key];
            }
        }
        if (!isset($headers['authorization']) && is_string($server['REDIRECT_HTTP_AUTHORIZATION'] ?? null)) {
            $headers['authorization'] = $server['REDIRECT_HTTP_AUTHORIZATION'];
        }

        $https = (($server['HTTPS'] ?? '') !== '' && strtolower((string) $server['HTTPS']) !== 'off')
            || strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

        $stringCookies = array_filter($cookies, 'is_string');

        return new self(
            strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $headers,
            $https,
            $query,
            $post,
            $stringCookies,
            $body,
            $uri,
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    /** Einzelner GET-Parameter als String (Arrays werden verworfen). */
    public function query(string $name): ?string
    {
        $value = $this->query[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    /** @return array<string, mixed> */
    public function queryAll(): array
    {
        return $this->query;
    }

    /** Einzelnes Formularfeld als String (Arrays werden verworfen). */
    public function post(string $name): ?string
    {
        $value = $this->post[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    /** Formularfeld als Array (z. B. ist[0][sets]); sonst leeres Array. @return array<mixed> */
    public function postArray(string $name): array
    {
        $value = $this->post[$name] ?? null;

        return is_array($value) ? $value : [];
    }

    public function cookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }
}
