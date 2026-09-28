<?php

declare(strict_types=1);

namespace Training\Mcp;

use Mcp\Server\HttpServerRunner;
use Mcp\Server\McpServer;
use Mcp\Server\NotificationOptions;
use Mcp\Server\Transport\Http\BufferedIo;
use Mcp\Server\Transport\Http\FileSessionStore;
use Mcp\Server\Transport\Http\HttpMessage;
use Mcp\Server\Transport\Http\InMemorySessionStore;
use Training\App;
use Training\Clock;
use Training\Http\Request;
use Training\Http\Response;
use Training\OAuth\OAuthConfig;

/**
 * /mcp (Streamable HTTP, stateless, ohne SSE) und /.well-known/oauth-protected-resource über das
 * logiscape-SDK (D-04). Der SDK-Runner arbeitet mit BufferedIo; die Antwort wird in eine Response übersetzt.
 * Sitzungsdateien für Clients älterer Protokollrevisionen liegen in <Ordner>/var/mcp_sessions (D-17).
 */
final class McpEndpoint
{
    private const SESSION_TTL = 3600;
    /** Scopes des geprüften Tokens (für die Rechteprüfung je Tool). */
    private string $scope = '';

    public function __construct(
        private readonly OAuthConfig $config,
        private readonly string $varDir,
        private readonly Clock $clock,
        private readonly ?App $app = null,
    ) {
    }

    public function handle(Request $request): Response
    {
        // Das SDK legt für Clients älterer Protokollrevisionen Sitzungsdateien an, auch vor der Token-Prüfung.
        // Ohne gültiges Token wird daher nur ein flüchtiger Speicher benutzt; die 401-Antwort erzeugt weiterhin das SDK.
        $validator = new TokenValidator($this->config);
        $authorized = $this->hasValidToken($request, $validator);
        if ($authorized) {
            $this->cleanupSessions();
        }
        $mcp = new McpServer('training', null, App::VERSION);
        $this->registerTools($mcp);
        if ($this->app !== null) {
            (new ToolRegistry($this->app, $this->clock, fn (): string => $this->scope))->register($mcp);
        }

        $options = [
            'enable_sse' => false,
            'shared_hosting' => true,
            'session_timeout' => self::SESSION_TTL,
            'auth_enabled' => true,
            'token_validator' => $validator,
            'authorization_servers' => [$this->config->issuer],
            'resource' => $this->config->resource(),
        ];
        $server = $mcp->getServer();
        $runner = new HttpServerRunner(
            $server,
            $server->createInitializationOptions(new NotificationOptions()),
            $options,
            null,
            $authorized ? new FileSessionStore($this->sessionDir()) : new InMemorySessionStore(),
            new BufferedIo(),
        );

        $result = $runner->handleRequest($this->toMessage($request));

        $headers = [];
        foreach ($result->getHeaders() as $name => $value) {
            $headers[self::headerName($name)] = $value;
        }
        if (!$authorized) {
            unset($headers['Mcp-Session-Id']);
        }
        $headers['Cache-Control'] ??= 'no-store';

        return new Response($result->getStatusCode(), $result->getBody() ?? '', $headers);
    }

    private function hasValidToken(Request $request, TokenValidator $validator): bool
    {
        $header = $request->header('Authorization');
        if ($header === null || preg_match('/^Bearer\s+(\S+)/i', $header, $m) !== 1) {
            return false;
        }
        $result = $validator->validate($m[1]);
        $this->scope = $result->valid ? (string) ($result->claims['scope'] ?? '') : '';

        return $result->valid;
    }

    private function sessionDir(): string
    {
        return $this->varDir . '/mcp_sessions';
    }

    /** Entfernt gelegentlich (etwa jeder 50. Request) Sitzungsdateien, die älter als ein Tag sind. */
    private function cleanupSessions(): void
    {
        if (random_int(1, 50) !== 1 || !is_dir($this->sessionDir())) {
            return;
        }
        // Dateizeiten sind echte Zeit; die App-Uhr kann in Tests verstellt sein (sonst verschwindet eine neue Sitzung)
        $limit = time() - 86400;
        foreach (glob($this->sessionDir() . '/*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $limit) {
                @unlink($file);
            }
        }
    }

    private function registerTools(McpServer $mcp): void
    {
        $clock = $this->clock;
        $mcp->tool(
            'ping',
            'Verbindungstest: gibt den aktuellen Zeitstempel des Trainingsservers zurück.',
            static fn (): array => [
                'time' => gmdate('Y-m-d\TH:i:s\Z', $clock->now()),
                'unix' => $clock->now(),
                'server' => 'training',
                'version' => App::VERSION,
            ],
            title: 'Ping',
            outputSchema: [
                'type' => 'object',
                'properties' => [
                    'time' => ['type' => 'string', 'description' => 'Serverzeit ISO 8601 (UTC)'],
                    'unix' => ['type' => 'integer', 'description' => 'Serverzeit als Unix-Zeitstempel (Sekunden)'],
                    'server' => ['type' => 'string'],
                    'version' => ['type' => 'string', 'description' => 'Code-Stand des Servers'],
                ],
                'required' => ['time', 'unix', 'server', 'version'],
            ],
            annotations: ['readOnlyHint' => true, 'openWorldHint' => false],
        );
    }

    private function toMessage(Request $request): HttpMessage
    {
        $message = new HttpMessage($request->body === '' ? null : $request->body);
        $message->setMethod($request->method);
        $message->setUri($request->uri !== '' ? $request->uri : $request->path);
        $message->setQueryParams($request->queryAll());
        foreach ($request->headers() as $name => $value) {
            $message->setHeader($name, $value);
        }
        // Metadaten-URL im WWW-Authenticate-Header aus APP_URL bilden, nicht aus Proxy-Headern.
        $parts = parse_url($this->config->issuer);
        $message->setHeader('Host', ($parts['host'] ?? 'localhost') . (isset($parts['port']) ? ':' . $parts['port'] : ''));
        $message->setHeader('X-Forwarded-Proto', $parts['scheme'] ?? 'https');

        return $message;
    }

    private static function headerName(string $name): string
    {
        return implode('-', array_map('ucfirst', explode('-', strtolower($name))));
    }
}
