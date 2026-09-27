<?php

declare(strict_types=1);

namespace Training;

use PDO;
use Training\Auth\LoginThrottle;
use Training\Auth\SessionManager;
use Training\Auth\UserRepository;
use Training\Controller\HealthController;
use Training\Controller\MigrateController;
use Training\Controller\OAuthController;
use Training\Controller\WebController;
use Training\Http\Request;
use Training\Http\Response;
use Training\Mcp\McpEndpoint;
use Training\OAuth\OAuthConfig;
use Training\View\View;

final class App
{
    public const VERSION = '0.2.0';

    /** Muss der höchsten Nummer in server/migrations/ entsprechen (D-20). */
    public const SCHEMA_VERSION = 6;

    private ?Config $config = null;
    private ?PDO $pdo = null;
    private ?SessionManager $sessions = null;
    private ?UserRepository $users = null;
    private ?View $view = null;
    private readonly Clock $clock;

    /** @param string $baseDir Subdomain-Ordner (enthält .env, src/, migrations/, public/, var/) */
    public function __construct(
        public readonly string $baseDir,
        ?Clock $clock = null,
        private readonly LoginThrottle $loginThrottle = new LoginThrottle(),
    ) {
        $this->clock = $clock ?? new SystemClock();
    }

    public function run(): void
    {
        $body = (string) file_get_contents('php://input');
        $request = Request::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $body);
        try {
            $response = $this->handle($request);
        } catch (\Throwable $e) {
            error_log('[training] ' . $e::class . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $response = Response::error(500, 'Interner Fehler.');
        }
        $response->send($request->https);
    }

    public function handle(Request $request): Response
    {
        $web = fn (): WebController => new WebController($this);
        $oauth = fn (): OAuthController => new OAuthController($this);
        $mcp = fn (): Response => (new McpEndpoint($this->oauthConfig(), $this->varDir(), $this->clock))->handle($request);
        $mcpWithCors = fn (): Response => $mcp()->withHeaders(OAuthController::CORS);
        $preflight = fn (): Response => (new Response(204, ''))->withHeaders(OAuthController::CORS);

        $routes = [
            '/' => ['GET' => fn (): Response => $web()->home($request)],
            '/health' => ['GET' => fn (): Response => (new HealthController($this))->handle()],
            '/admin/migrate' => ['POST' => fn (): Response => (new MigrateController($this))->handle($request)],
            '/setup' => ['GET' => fn (): Response => $web()->setup($request), 'POST' => fn (): Response => $web()->setup($request)],
            '/login' => ['GET' => fn (): Response => $web()->login($request), 'POST' => fn (): Response => $web()->login($request)],
            '/logout' => ['POST' => fn (): Response => $web()->logout($request)],
            '/.well-known/oauth-authorization-server' => ['GET' => fn (): Response => $oauth()->metadata(), 'OPTIONS' => $preflight],
            '/.well-known/oauth-authorization-server/mcp' => ['GET' => fn (): Response => $oauth()->metadata(), 'OPTIONS' => $preflight],
            '/.well-known/oauth-protected-resource' => ['GET' => $mcpWithCors, 'OPTIONS' => $preflight],
            '/.well-known/oauth-protected-resource/mcp' => ['GET' => $mcpWithCors, 'OPTIONS' => $preflight],
            '/oauth/register' => ['POST' => fn (): Response => $oauth()->register($request), 'OPTIONS' => $preflight],
            '/oauth/authorize' => ['GET' => fn (): Response => $oauth()->authorize($request), 'POST' => fn (): Response => $oauth()->authorize($request)],
            '/oauth/token' => ['POST' => fn (): Response => $oauth()->token($request), 'OPTIONS' => $preflight],
            // Streamable HTTP, stateless ohne SSE: GET/DELETE beantwortet das SDK (405 bzw. Sitzungsende für ältere Clients).
            '/mcp' => ['POST' => $mcpWithCors, 'GET' => $mcpWithCors, 'DELETE' => $mcpWithCors, 'OPTIONS' => $preflight],
        ];

        $methods = $routes[$request->path] ?? null;
        if ($methods === null) {
            return Response::error(404, 'Nicht gefunden.');
        }
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        if (!isset($methods[$method])) {
            return new Response(405, '', ['Allow' => implode(', ', array_keys($methods))]);
        }

        $response = $methods[$method]();
        if ($this->sessions?->refreshCookie !== null) {
            $response = $response->withCookie($this->sessions->refreshCookie);
        }

        return $response;
    }

    public function config(): Config
    {
        return $this->config ??= Config::fromFile($this->baseDir . '/.env');
    }

    public function pdo(): PDO
    {
        return $this->pdo ??= Database::connect($this->config());
    }

    public function clock(): Clock
    {
        return $this->clock;
    }

    public function loginThrottle(): LoginThrottle
    {
        return $this->loginThrottle;
    }

    public function users(): UserRepository
    {
        return $this->users ??= new UserRepository($this->pdo(), $this->clock);
    }

    public function sessions(): SessionManager
    {
        return $this->sessions ??= new SessionManager($this->pdo(), $this->clock, $this->secureCookies());
    }

    public function oauthConfig(): OAuthConfig
    {
        return OAuthConfig::fromConfig($this->config());
    }

    public function view(): View
    {
        return $this->view ??= new View($this->baseDir . '/templates', $this->baseDir . '/public/assets');
    }

    /** Cookies mit Secure-Flag, sobald die App über https läuft (APP_URL). */
    public function secureCookies(): bool
    {
        return str_starts_with(strtolower((string) $this->config()->get('APP_URL')), 'https://');
    }

    public function host(): string
    {
        return (string) parse_url((string) $this->config()->get('APP_URL'), PHP_URL_HOST);
    }

    public function migrationsDir(): string
    {
        return $this->baseDir . '/migrations';
    }

    /** Laufzeitdaten außerhalb des Docroots (D-17), z. B. MCP-Sitzungsdateien. */
    public function varDir(): string
    {
        return $this->baseDir . '/var';
    }
}
