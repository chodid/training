<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\App;
use Training\Http\Request;
use Training\Http\Response;
use Training\OAuth\AuthCodeRepository;
use Training\OAuth\ClientRepository;
use Training\OAuth\OAuthConfig;
use Training\OAuth\OAuthException;
use Training\OAuth\Pkce;
use Training\OAuth\RefreshTokenRepository;
use Training\OAuth\TokenIssuer;

/**
 * Autorisierungsserver (D-04, D-05, D-32, D-36): Metadaten (RFC 8414), offene Client-Registrierung
 * (RFC 7591), Authorize mit Login und Freigabeseite S7, Token-Endpunkt mit PKCE S256 und Refresh-Rotation.
 */
final class OAuthController
{
    /** CORS für Browser-Clients (z. B. MCP Inspector); ohne Cookies, daher unkritisch. */
    public const CORS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Authorization, Content-Type, MCP-Protocol-Version, Mcp-Session-Id',
        'Access-Control-Expose-Headers' => 'WWW-Authenticate, Mcp-Session-Id',
        'Access-Control-Max-Age' => '600',
    ];

    /** Parameter der Authorize-Anfrage, die an die Freigabeseite weitergereicht werden. */
    private const AUTHORIZE_PARAMS = ['response_type', 'client_id', 'redirect_uri', 'code_challenge', 'code_challenge_method', 'state', 'scope', 'resource'];

    public function __construct(private readonly App $app)
    {
    }

    public function metadata(): Response
    {
        return Response::json(200, $this->app->oauthConfig()->metadata())->withHeaders(self::CORS);
    }

    // ---------- /oauth/register (RFC 7591, offen, D-36) ----------

    public function register(Request $request): Response
    {
        $data = json_decode($request->body, true);
        if (!is_array($data)) {
            return self::oauthError(new OAuthException('invalid_client_metadata', 'Anfrage muss ein JSON-Objekt sein.'));
        }
        try {
            $client = (new ClientRepository($this->app->pdo(), $this->app->clock()))->register($data);
        } catch (OAuthException $e) {
            return self::oauthError($e);
        }

        return Response::json(201, $client)->withHeaders(self::CORS);
    }

    // ---------- /oauth/authorize (Login D-33 + Freigabeseite S7, D-36) ----------

    public function authorize(Request $request): Response
    {
        $isPost = $request->method === 'POST';
        $param = static fn (string $name): string => (string) ($isPost ? $request->post($name) : $request->query($name));

        // 1. Client und Redirect-URI prüfen: Fehler hier werden angezeigt, nie weitergeleitet.
        $clients = new ClientRepository($this->app->pdo(), $this->app->clock());
        $client = $param('client_id') !== '' ? $clients->find($param('client_id')) : null;
        if ($client === null) {
            return $this->errorPage('Unbekannte Anwendung.', 'Die Anwendung ist nicht registriert. Bitte die Verbindung in Claude neu einrichten.');
        }
        $redirectUri = $param('redirect_uri');
        if ($redirectUri === '' && count($client['redirect_uris']) === 1) {
            $redirectUri = $client['redirect_uris'][0];
        }
        if (!in_array($redirectUri, $client['redirect_uris'], true)) {
            return $this->errorPage('Weiterleitung nicht zulässig.', 'Die angegebene Rücksprungadresse ist für diese Anwendung nicht registriert.');
        }
        $state = $param('state');

        // 2. Übrige Parameter: Fehler gehen per Weiterleitung an den Client.
        if ($param('response_type') !== 'code') {
            return $this->redirectError($redirectUri, 'unsupported_response_type', 'Nur response_type=code wird unterstützt.', $state);
        }
        $challenge = $param('code_challenge');
        if ($param('code_challenge_method') !== 'S256' || !Pkce::isValidChallenge($challenge)) {
            return $this->redirectError($redirectUri, 'invalid_request', 'PKCE mit code_challenge_method=S256 ist Pflicht.', $state);
        }
        $scope = OAuthConfig::grantScope($param('scope'));

        // 3. Anmeldung verlangen.
        $session = $this->app->sessions()->current($request);
        if ($session === null) {
            if ($isPost) {
                return Response::redirect('/login');
            }

            return Response::redirect('/login?next=' . rawurlencode($request->uri !== '' ? $request->uri : '/oauth/authorize'));
        }

        // 4. Freigabeseite anzeigen bzw. Entscheidung verarbeiten.
        if (!$isPost) {
            $params = [];
            foreach (self::AUTHORIZE_PARAMS as $name) {
                if ($param($name) !== '') {
                    $params[$name] = $param($name);
                }
            }
            $params['redirect_uri'] = $redirectUri;
            $tz = new \DateTimeZone($session->tz);
            $registered = (new \DateTimeImmutable('@' . $client['created_at']))->setTimezone($tz);

            return Response::html(200, $this->app->view()->render('consent', [
                'title' => 'Zugriff freigeben',
                'csrf' => $session->csrfToken(),
                'params' => $params,
                'clientName' => $client['client_name'],
                'redirectHost' => (string) parse_url($redirectUri, PHP_URL_HOST),
                'registered' => $registered->format('d.m.Y, H:i') . ' Uhr',
                'scopeTexts' => array_merge(...array_map(static fn (string $s): array => OAuthConfig::SCOPES[$s], explode(' ', $scope))),
                'scope' => $scope,
                'login' => $session->login,
            ]));
        }

        if (!$session->verifyCsrf($request->post('csrf'))) {
            return $this->errorPage('Formular abgelaufen.', 'Bitte die Verbindung in Claude erneut starten.', 403);
        }
        if ($request->post('decision') !== 'approve') {
            return $this->redirectError($redirectUri, 'access_denied', 'Zugriff abgelehnt.', $state);
        }

        $code = (new AuthCodeRepository($this->app->pdo(), $this->app->clock()))
            ->create($client['client_id'], $session->userId, $challenge, $redirectUri, $scope);
        $clients->touch($client['client_id']);

        return Response::redirect(self::appendQuery($redirectUri, array_filter([
            'code' => $code,
            'state' => $state,
            'iss' => $this->app->oauthConfig()->issuer,
        ], static fn (string $v): bool => $v !== '')), 302);
    }

    // ---------- /oauth/token ----------

    public function token(Request $request): Response
    {
        try {
            $clientId = (string) $request->post('client_id');
            $basic = $request->header('Authorization');
            if ($clientId === '' && $basic !== null && preg_match('/^Basic\s+(\S+)/i', $basic, $m)) {
                $decoded = base64_decode($m[1], true);
                $clientId = $decoded !== false ? rawurldecode(explode(':', $decoded, 2)[0]) : '';
            }
            $clients = new ClientRepository($this->app->pdo(), $this->app->clock());
            if ($clientId === '' || $clients->find($clientId) === null) {
                throw new OAuthException('invalid_client', 'Client unbekannt.', 401);
            }

            $clock = $this->app->clock();
            $refresh = new RefreshTokenRepository($this->app->pdo(), $clock);
            $issuer = new TokenIssuer($this->app->oauthConfig(), $clock);

            $grant = (string) $request->post('grant_type');
            if ($grant === 'authorization_code') {
                $code = (string) $request->post('code');
                $verifier = (string) $request->post('code_verifier');
                if ($code === '' || $verifier === '') {
                    throw new OAuthException('invalid_request', 'code und code_verifier sind Pflicht.');
                }
                $redirectUri = $request->post('redirect_uri');
                $grantData = (new AuthCodeRepository($this->app->pdo(), $clock))
                    ->consume($code, $clientId, $redirectUri === '' ? null : $redirectUri, $verifier);
                $refreshToken = $refresh->issue($clientId, $grantData['user_id'], $grantData['scope']);
                $body = $issuer->response($grantData['user_id'], $clientId, $grantData['scope'], $refreshToken);
            } elseif ($grant === 'refresh_token') {
                $token = (string) $request->post('refresh_token');
                if ($token === '') {
                    throw new OAuthException('invalid_request', 'refresh_token ist Pflicht.');
                }
                $rotated = $refresh->rotate($token, $clientId);
                $body = $issuer->response($rotated['user_id'], $clientId, $rotated['scope'], $rotated['refresh_token']);
            } else {
                throw new OAuthException('unsupported_grant_type', 'Unterstützt: authorization_code, refresh_token.');
            }
            $clients->touch($clientId);

            return Response::json(200, $body)->withHeaders(['Pragma' => 'no-cache', ...self::CORS]);
        } catch (OAuthException $e) {
            return self::oauthError($e);
        }
    }

    public static function oauthError(OAuthException $e): Response
    {
        $response = Response::json($e->status, ['error' => $e->error, 'error_description' => $e->getMessage()])->withHeaders(self::CORS);

        return $e->status === 401 ? $response->withHeaders(['WWW-Authenticate' => 'Basic realm="training"']) : $response;
    }

    /** @param array<string, string> $params */
    public static function appendQuery(string $uri, array $params): string
    {
        return $uri . (str_contains($uri, '?') ? '&' : '?') . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private function redirectError(string $redirectUri, string $error, string $description, string $state): Response
    {
        return Response::redirect(self::appendQuery($redirectUri, array_filter([
            'error' => $error,
            'error_description' => $description,
            'state' => $state,
            'iss' => $this->app->oauthConfig()->issuer,
        ], static fn (string $v): bool => $v !== '')), 302);
    }

    private function errorPage(string $title, string $text, int $status = 400): Response
    {
        return Response::html($status, $this->app->view()->render('message', [
            'title' => 'Fehler',
            'alert' => ['type' => 'error', 'icon' => 'alert-circle', 'title' => $title, 'text' => $text],
            'host' => $this->app->host(),
        ]));
    }
}
