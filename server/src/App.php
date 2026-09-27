<?php

declare(strict_types=1);

namespace Training;

use PDO;
use Training\Controller\HealthController;
use Training\Controller\MigrateController;
use Training\Http\Request;
use Training\Http\Response;

final class App
{
    public const VERSION = '0.1.1';

    /** Muss der höchsten Nummer in server/migrations/ entsprechen (D-20). */
    public const SCHEMA_VERSION = 1;

    private ?Config $config = null;
    private ?PDO $pdo = null;

    /** @param string $baseDir Subdomain-Ordner (enthält .env, src/, migrations/, public/) */
    public function __construct(public readonly string $baseDir)
    {
    }

    public function run(): void
    {
        $request = Request::fromGlobals($_SERVER);
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
        $routes = [
            '/' => ['GET' => fn (): Response => $this->home()],
            '/health' => ['GET' => fn (): Response => (new HealthController($this))->handle()],
            '/admin/migrate' => ['POST' => fn (): Response => (new MigrateController($this))->handle($request)],
        ];

        $methods = $routes[$request->path] ?? null;
        if ($methods === null) {
            return Response::error(404, 'Nicht gefunden.');
        }
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        if (!isset($methods[$method])) {
            return new Response(405, '', ['Allow' => implode(', ', array_keys($methods))]);
        }

        return $methods[$method]();
    }

    public function config(): Config
    {
        return $this->config ??= Config::fromFile($this->baseDir . '/.env');
    }

    public function pdo(): PDO
    {
        return $this->pdo ??= Database::connect($this->config());
    }

    public function migrationsDir(): string
    {
        return $this->baseDir . '/migrations';
    }

    private function home(): Response
    {
        return new Response(
            200,
            "<!doctype html>\n<html lang=\"de\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
            . "<meta name=\"robots\" content=\"noindex\"><title>Training</title></head>"
            . "<body><p>Training – Grundgerüst. Die Webseite folgt mit AP-04.</p></body></html>\n",
            ['Content-Type' => 'text/html; charset=utf-8', 'X-Robots-Tag' => 'noindex'],
        );
    }
}
