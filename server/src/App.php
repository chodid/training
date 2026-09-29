<?php

declare(strict_types=1);

namespace Training;

use PDO;
use Training\Auth\LoginThrottle;
use Training\Auth\SessionManager;
use Training\Auth\UserRepository;
use Training\Controller\HealthController;
use Training\Controller\IntervalsController;
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
    public const VERSION = '0.30.2';

    /** Muss der höchsten Nummer in server/migrations/ entsprechen (D-20). */
    public const SCHEMA_VERSION = 24;

    private ?Config $config = null;
    private ?PDO $pdo = null;
    private ?SessionManager $sessions = null;
    private ?UserRepository $users = null;
    private ?View $view = null;
    private ?bool $writeLocked = null;
    private readonly Clock $clock;

    /** @param string $baseDir Subdomain-Ordner (enthält .env, src/, migrations/, public/, var/) */
    public function __construct(
        public readonly string $baseDir,
        ?Clock $clock = null,
        private readonly LoginThrottle $loginThrottle = new LoginThrottle(),
        /** Nur für Tests: Ersatz für den HTTP-Transport zu Intervals.icu */
        private readonly ?\Training\Intervals\HttpTransport $intervalsTransport = null,
        /** Nur für Tests: Ersatz für den SMTP-Versand */
        private readonly ?\Training\Backup\Mailer $mailer = null,
        /** Nur für Tests: Ersatz für den HTTP-Transport zum CalDAV-Kalender */
        private readonly ?\Training\Intervals\HttpTransport $calendarTransport = null,
        /** Nur für Tests: Ersatz für die Abrufe der Linkprüfung im Übungskatalog */
        private readonly ?\Training\Exercise\LinkFetcher $linkFetcher = null,
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
        $mcp = fn (): Response => (new McpEndpoint($this->oauthConfig(), $this->varDir(), $this->clock, $this))->handle($request);
        $mcpWithCors = fn (): Response => $mcp()->withHeaders(OAuthController::CORS);
        $preflight = fn (): Response => (new Response(204, ''))->withHeaders(OAuthController::CORS);

        $routes = [
            '/' => ['GET' => fn (): Response => $web()->home($request)],
            '/health' => ['GET' => fn (): Response => (new HealthController($this))->handle()],
            '/admin/migrate' => ['POST' => fn (): Response => (new MigrateController($this))->handle($request)],
            '/setup' => ['GET' => fn (): Response => $web()->setup($request), 'POST' => fn (): Response => $web()->setup($request)],
            '/login' => ['GET' => fn (): Response => $web()->login($request), 'POST' => fn (): Response => $web()->login($request)],
            '/logout' => ['POST' => fn (): Response => $web()->logout($request)],
            '/woche' => ['GET' => fn (): Response => (new \Training\Controller\WeekController($this))->handle($request)],
            '/einheit' => ['GET' => fn (): Response => (new \Training\Controller\SessionController($this))->handle($request), 'POST' => fn (): Response => (new \Training\Controller\SessionController($this))->handle($request)],
            '/checkin' => ['GET' => fn (): Response => (new \Training\Controller\CheckinController($this))->handle($request), 'POST' => fn (): Response => (new \Training\Controller\CheckinController($this))->handle($request)],
            '/schmerz' => ['GET' => fn (): Response => (new \Training\Controller\PainController($this))->handle($request), 'POST' => fn (): Response => (new \Training\Controller\PainController($this))->handle($request)],
            '/einstellungen' => ['GET' => fn (): Response => (new \Training\Controller\SettingsController($this))->handle($request), 'POST' => fn (): Response => (new \Training\Controller\SettingsController($this))->handle($request)],
            '/profil' => ['GET' => fn (): Response => (new \Training\Controller\ProfileController($this))->handle($request), 'POST' => fn (): Response => (new \Training\Controller\ProfileController($this))->handle($request)],
            '/offline/token' => ['GET' => fn (): Response => (new \Training\Controller\OfflineController($this))->token($request)],
            '/passkey/register/options' => ['POST' => fn (): Response => (new \Training\Controller\PasskeyController($this))->registerOptions($request)],
            '/passkey/register' => ['POST' => fn (): Response => (new \Training\Controller\PasskeyController($this))->register($request)],
            '/passkey/login/options' => ['POST' => fn (): Response => (new \Training\Controller\PasskeyController($this))->loginOptions()],
            '/passkey/login' => ['POST' => fn (): Response => (new \Training\Controller\PasskeyController($this))->login($request)],
            '/cron/intervals-sync' => ['GET' => fn (): Response => (new \Training\Controller\CronController($this))->intervalsSync($request)],
            '/cron/backup-mail' => ['GET' => fn (): Response => (new \Training\Controller\CronController($this))->backupMail($request)],
            '/uebung' => ['GET' => fn (): Response => (new \Training\Controller\ExerciseController($this))->show($request)],
            '/uebungen' => ['GET' => fn (): Response => (new \Training\Controller\ExerciseController($this))->list($request)],
            '/block' => ['GET' => fn (): Response => (new \Training\Controller\BlockController($this))->handle($request)],
            '/erinnerung' => ['POST' => fn (): Response => (new \Training\Controller\ReminderController($this))->handle($request)],
            '/verlauf' => ['GET' => fn (): Response => (new \Training\Controller\HistoryController($this))->handle($request)],
            '/intervals' => ['GET' => fn (): Response => (new IntervalsController($this, $this->intervalsTransport))->handle($request), 'POST' => fn (): Response => (new IntervalsController($this, $this->intervalsTransport))->handle($request)],
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

    public function intervalsClient(): \Training\Intervals\IntervalsClient
    {
        return $this->intervalsTransport !== null
            ? \Training\Intervals\IntervalsClient::fromConfig($this->config(), $this->intervalsTransport)
            : \Training\Intervals\IntervalsClient::fromConfig($this->config());
    }

    /** Kalender-Abgleich (AP-11, D-50); ohne CALDAV_*-Konfiguration wirkungslos. */
    public function calendar(): \Training\Calendar\CalendarSync
    {
        $config = $this->config();
        $client = null;
        if (\Training\Calendar\CalDavClient::isConfigured($config)) {
            try {
                $client = \Training\Calendar\CalDavClient::fromConfig($config, $this->calendarTransport);
            } catch (ConfigException $e) {
                error_log('[training] Kalender aus: ' . $e->getMessage()); // Anzeige in Einstellungen und /health
            }
        }

        $reminder = $client !== null ? (new \Training\Data\SettingsRepository($this->pdo(), $this->clock))->calendarReminder() : null;

        $tz = $client !== null ? ($this->users()->first()?->tz ?? 'Europe/Berlin') : 'Europe/Berlin';

        return new \Training\Calendar\CalendarSync($this->pdo(), $this->clock, $client, (string) $config->get('APP_URL'), $this->host(), $this->varDir() . '/calendar-sync.json', $reminder, $tz);
    }

    /** Linkprüfung des Übungskatalogs (AP-16, E-10/E-18/E-19). */
    public function linkChecker(): \Training\Exercise\LinkChecker
    {
        $fetcher = $this->linkFetcher ?? new \Training\Exercise\CurlLinkFetcher('Mozilla/5.0 (compatible; training-linkcheck; +' . $this->config()->get('APP_URL') . ')');

        return new \Training\Exercise\LinkChecker($fetcher, $this->clock);
    }

    public function backupDir(): string
    {
        return $this->baseDir . '/backups';
    }

    public function updates(): \Training\Backup\UpdateService
    {
        return new \Training\Backup\UpdateService($this->pdo(), $this->clock, $this->migrationsDir(), $this->backupDir(), $this->config()->get('BACKUP_PASSWORD'));
    }

    public function backups(): \Training\Backup\BackupService
    {
        return new \Training\Backup\BackupService($this->pdo(), $this->clock, $this->config()->require('BACKUP_PASSWORD'), $this->migrationsDir());
    }

    public function mailBackup(): \Training\Backup\MailBackup
    {
        return new \Training\Backup\MailBackup($this->backups(), $this->mailer ?? new \Training\Backup\SmtpMailer($this->config()), $this->clock, $this->varDir() . '/backup-mail.json');
    }

    /** Schreibsperre bei Abweichung Code/Datenbank (D-20); einmal je Request ermittelt. */
    public function writeLocked(): bool
    {
        return $this->writeLocked ??= \Training\Backup\UpdateService::writeLocked($this->pdo(), $this->migrationsDir());
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
