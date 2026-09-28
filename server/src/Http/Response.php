<?php

declare(strict_types=1);

namespace Training\Http;

final class Response
{
    /** Sicherheitsheader für HTML-Seiten: keine Einbettung in fremde Seiten (Freigabeseite S7), nur eigene Ressourcen. */
    public const HTML_HEADERS = [
        'Content-Type' => 'text/html; charset=utf-8',
        'Cache-Control' => 'no-store',
        'X-Robots-Tag' => 'noindex',
        'X-Frame-Options' => 'DENY',
        'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; base-uri 'none'",
    ];

    /**
     * @param array<string, string> $headers
     * @param list<string>          $cookies Werte für Set-Cookie
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
        public readonly array $cookies = [],
    ) {
    }

    /** @param array<string, mixed>|list<mixed> $data */
    public static function json(int $status, array $data): self
    {
        return new self(
            $status,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n",
            ['Content-Type' => 'application/json; charset=utf-8', 'Cache-Control' => 'no-store'],
        );
    }

    public static function error(int $status, string $message): self
    {
        return self::json($status, ['status' => 'error', 'message' => $message]);
    }

    public static function html(int $status, string $html): self
    {
        return new self($status, $html, self::HTML_HEADERS);
    }

    public static function redirect(string $location, int $status = 303): self
    {
        return new self($status, '', ['Location' => $location, 'Cache-Control' => 'no-store']);
    }

    public function withCookie(string $setCookie): self
    {
        return new self($this->status, $this->body, $this->headers, [...$this->cookies, $setCookie]);
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): self
    {
        return new self($this->status, $this->body, [...$this->headers, ...$headers], $this->cookies);
    }

    public function send(bool $https): void
    {
        http_response_code($this->status);
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000');
        }
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        foreach ($this->cookies as $cookie) {
            header('Set-Cookie: ' . $cookie, false);
        }
        echo $this->body;
    }
}
