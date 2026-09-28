<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Http\Response;
use Training\OAuth\Jwt;
use Training\OAuth\Pkce;
use Training\Tests\Support\AppTestCase;

/** Autorisierungsserver (D-32, D-36), Bearer-Prüfung am /mcp (D-04) und statisches Token (D-06). */
final class OAuthFlowTest extends AppTestCase
{
    private const CALLBACK = 'https://claude.ai/api/mcp/auth_callback';
    private const VERIFIER = 'verifier-verifier-verifier-verifier-verifier-0123';

    public function testMetadataEndpoints(): void
    {
        foreach (['/.well-known/oauth-authorization-server', '/.well-known/oauth-authorization-server/mcp'] as $path) {
            $meta = json_decode($this->request('GET', $path)->body, true);
            self::assertSame(self::APP_URL, $meta['issuer']);
            self::assertSame(self::APP_URL . '/oauth/token', $meta['token_endpoint']);
            self::assertSame(self::APP_URL . '/oauth/register', $meta['registration_endpoint']);
            self::assertSame(['S256'], $meta['code_challenge_methods_supported']);
            self::assertSame(['none'], $meta['token_endpoint_auth_methods_supported']);
        }
        foreach (['/.well-known/oauth-protected-resource', '/.well-known/oauth-protected-resource/mcp'] as $path) {
            $r = $this->request('GET', $path);
            self::assertSame(200, $r->status);
            self::assertSame(['resource' => self::APP_URL . '/mcp', 'authorization_servers' => [self::APP_URL]], json_decode($r->body, true));
        }
    }

    public function testRegistrationRejectsInsecureRedirectUris(): void
    {
        $r = $this->register(['http://claude.ai/callback']);
        self::assertSame(400, $r->status);
        self::assertSame('invalid_redirect_uri', json_decode($r->body, true)['error']);
        self::assertSame(400, $this->request('POST', '/oauth/register', [], [], 'kein json')->status);
        self::assertSame(400, $this->register([])->status);
        self::assertSame(201, $this->register(['http://localhost:6274/oauth/callback'])->status);
        self::assertSame(0 + 1, (int) $this->pdo->query('SELECT COUNT(*) FROM oauth_client')->fetchColumn());
    }

    public function testFullFlowWithConsentTokenRefreshAndMcpPing(): void
    {
        $this->setupUser();
        $this->cookies = [];
        $clientId = $this->registeredClient();

        // Ohne Anmeldung: Weiterleitung zum Login mit Rücksprung.
        $query = $this->authorizeQuery($clientId);
        $r = $this->request('GET', '/oauth/authorize?' . http_build_query($query));
        self::assertSame(303, $r->status);
        self::assertStringStartsWith('/login?next=%2Foauth%2Fauthorize%3F', $r->headers['Location']);

        $form = $this->request('GET', $r->headers['Location']);
        $login = $this->request('POST', '/login', ['csrf' => self::csrfFrom($form), 'login' => 'philipp', 'password' => 'richtig-langes-passwort', 'next' => rawurldecode(substr($r->headers['Location'], strlen('/login?next=')))]);
        self::assertSame(303, $login->status);
        self::assertStringStartsWith('/oauth/authorize?', $login->headers['Location']);

        // Freigabeseite S7.
        $consent = $this->request('GET', $login->headers['Location']);
        self::assertSame(200, $consent->status);
        self::assertStringContainsString('Zugriff freigeben?', $consent->body);
        self::assertStringContainsString('Claude', $consent->body);
        self::assertStringContainsString('claude.ai', $consent->body);
        self::assertStringContainsString('training:read training:write', $consent->body);

        // Freigabe ohne gültiges CSRF-Token scheitert.
        self::assertSame(403, $this->request('POST', '/oauth/authorize', [...$query, 'csrf' => 'x', 'decision' => 'approve'])->status);

        $approve = $this->request('POST', '/oauth/authorize', [...$query, 'csrf' => self::csrfFrom($consent), 'decision' => 'approve']);
        self::assertSame(302, $approve->status);
        $params = $this->callbackParams($approve);
        self::assertSame('xyz', $params['state']);
        self::assertSame(self::APP_URL, $params['iss']);
        $code = $params['code'];

        // Falscher Verifier verbraucht den Code.
        $bad = $this->token(['grant_type' => 'authorization_code', 'client_id' => $clientId, 'code' => $code, 'redirect_uri' => self::CALLBACK, 'code_verifier' => str_repeat('a', 43)]);
        self::assertSame('invalid_grant', json_decode($bad->body, true)['error']);
        $again = $this->token(['grant_type' => 'authorization_code', 'client_id' => $clientId, 'code' => $code, 'redirect_uri' => self::CALLBACK, 'code_verifier' => self::VERIFIER]);
        self::assertSame('invalid_grant', json_decode($again->body, true)['error']);

        // Neuer Durchlauf, korrekt.
        $tokens = $this->obtainTokens($clientId);
        self::assertSame('Bearer', $tokens['token_type']);
        self::assertSame(3600, $tokens['expires_in']);
        self::assertSame('training:read training:write', $tokens['scope']);

        // /mcp mit Access-Token: initialize + ping.
        $this->assertPing($tokens['access_token']);

        // Refresh mit Rotation.
        $this->clock->advance(3700);
        $rotated = json_decode($this->token(['grant_type' => 'refresh_token', 'client_id' => $clientId, 'refresh_token' => $tokens['refresh_token']])->body, true);
        self::assertArrayHasKey('access_token', $rotated);
        self::assertNotSame($tokens['refresh_token'], $rotated['refresh_token']);

        // Wiederverwendung des alten Refresh-Tokens widerruft die ganze Familie.
        $reuse = $this->token(['grant_type' => 'refresh_token', 'client_id' => $clientId, 'refresh_token' => $tokens['refresh_token']]);
        self::assertSame(400, $reuse->status);
        self::assertSame('invalid_grant', json_decode($reuse->body, true)['error']);
        $afterRevoke = $this->token(['grant_type' => 'refresh_token', 'client_id' => $clientId, 'refresh_token' => $rotated['refresh_token']]);
        self::assertSame('invalid_grant', json_decode($afterRevoke->body, true)['error']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM oauth_token WHERE revoked = 0')->fetchColumn());
    }

    public function testCodeExpiresAfterTenMinutes(): void
    {
        $this->setupUser();
        $clientId = $this->registeredClient();
        $code = $this->approve($clientId);
        $this->clock->advance(601);
        $r = $this->token(['grant_type' => 'authorization_code', 'client_id' => $clientId, 'code' => $code, 'redirect_uri' => self::CALLBACK, 'code_verifier' => self::VERIFIER]);
        self::assertSame('invalid_grant', json_decode($r->body, true)['error']);
    }

    public function testDenyRedirectsWithAccessDenied(): void
    {
        $this->setupUser();
        $clientId = $this->registeredClient();
        $query = $this->authorizeQuery($clientId);
        $consent = $this->request('GET', '/oauth/authorize?' . http_build_query($query));
        $r = $this->request('POST', '/oauth/authorize', [...$query, 'csrf' => self::csrfFrom($consent), 'decision' => 'deny']);
        self::assertSame('access_denied', $this->callbackParams($r)['error']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM oauth_auth_code')->fetchColumn());
    }

    public function testPkcePlainAndMissingChallengeAreRejected(): void
    {
        $this->setupUser();
        $clientId = $this->registeredClient();
        $plain = $this->request('GET', '/oauth/authorize?' . http_build_query([...$this->authorizeQuery($clientId), 'code_challenge_method' => 'plain', 'code_challenge' => self::VERIFIER]));
        self::assertSame(302, $plain->status);
        self::assertSame('invalid_request', $this->callbackParams($plain)['error']);

        $query = $this->authorizeQuery($clientId);
        unset($query['code_challenge'], $query['code_challenge_method']);
        self::assertSame('invalid_request', $this->callbackParams($this->request('GET', '/oauth/authorize?' . http_build_query($query)))['error']);
    }

    public function testUnknownClientOrUnregisteredRedirectIsShownNotRedirected(): void
    {
        $this->setupUser();
        $clientId = $this->registeredClient();
        $r = $this->request('GET', '/oauth/authorize?' . http_build_query([...$this->authorizeQuery($clientId), 'redirect_uri' => 'https://evil.example/cb']));
        self::assertSame(400, $r->status);
        self::assertArrayNotHasKey('Location', $r->headers);
        self::assertSame(400, $this->request('GET', '/oauth/authorize?' . http_build_query([...$this->authorizeQuery($clientId), 'client_id' => 'unbekannt']))->status);
    }

    public function testMcpWithoutOrWithInvalidTokenReturns401WithMetadata(): void
    {
        $body = self::initializeBody();
        $none = $this->request('POST', '/mcp', [], ['Content-Type' => 'application/json'], $body);
        self::assertSame(401, $none->status);
        self::assertSame('Bearer resource_metadata="' . self::APP_URL . '/.well-known/oauth-protected-resource"', $none->headers['Www-Authenticate']);
        self::assertArrayNotHasKey('Mcp-Session-Id', $none->headers);

        $now = $this->clock->now();
        $claims = ['iss' => self::APP_URL, 'aud' => self::APP_URL . '/mcp', 'sub' => '1', 'scope' => 'training:read', 'iat' => $now - 7200, 'exp' => $now - 3600];
        $expired = $this->request('POST', '/mcp', [], ['Authorization' => 'Bearer ' . Jwt::encode($claims, self::JWT_SECRET), 'Content-Type' => 'application/json'], $body);
        self::assertSame(401, $expired->status);
        self::assertStringContainsString('invalid_token', $expired->headers['Www-Authenticate']);

        $claims['exp'] = $now + 3600;
        $foreign = $this->request('POST', '/mcp', [], ['Authorization' => 'Bearer ' . Jwt::encode($claims, str_repeat('f', 40)), 'Content-Type' => 'application/json'], $body);
        self::assertSame(401, $foreign->status);

        self::assertSame(0, count(glob($this->baseDir . '/var/mcp_sessions/*') ?: []), 'keine Sitzungsdateien ohne gültiges Token');
    }

    public function testStaticTokenOnlyWithFlag(): void
    {
        $static = str_repeat('s', 40);
        $this->writeEnv(['MCP_STATIC_TOKEN' => $static]);
        $r = $this->request('POST', '/mcp', [], ['Authorization' => 'Bearer ' . $static, 'Content-Type' => 'application/json'], self::initializeBody());
        self::assertSame(401, $r->status);

        $this->writeEnv(['MCP_STATIC_TOKEN' => $static, 'MCP_STATIC_TOKEN_ENABLED' => 'false']);
        self::assertSame(401, $this->request('POST', '/mcp', [], ['Authorization' => 'Bearer ' . $static, 'Content-Type' => 'application/json'], self::initializeBody())->status);

        $this->writeEnv(['MCP_STATIC_TOKEN' => $static, 'MCP_STATIC_TOKEN_ENABLED' => 'true']);
        $this->assertPing($static);
    }

    public function testMcpGetIs405(): void
    {
        $static = str_repeat('s', 40);
        $this->writeEnv(['MCP_STATIC_TOKEN' => $static, 'MCP_STATIC_TOKEN_ENABLED' => 'true']);
        self::assertSame(405, $this->request('GET', '/mcp', [], ['Authorization' => 'Bearer ' . $static, 'Accept' => 'text/event-stream'])->status);
    }

    // ---------- Hilfen ----------

    /** @param list<string> $uris */
    private function register(array $uris): Response
    {
        return $this->request('POST', '/oauth/register', [], ['Content-Type' => 'application/json'], (string) json_encode([
            'client_name' => 'Claude',
            'redirect_uris' => $uris,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
        ]));
    }

    private function registeredClient(): string
    {
        $r = $this->register([self::CALLBACK]);
        self::assertSame(201, $r->status, $r->body);
        $data = json_decode($r->body, true);
        self::assertSame('none', $data['token_endpoint_auth_method']);

        return $data['client_id'];
    }

    /** @return array<string, string> */
    private function authorizeQuery(string $clientId): array
    {
        return [
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => self::CALLBACK,
            'code_challenge' => Pkce::challenge(self::VERIFIER),
            'code_challenge_method' => 'S256',
            'state' => 'xyz',
            'scope' => 'training:read training:write',
            'resource' => self::APP_URL . '/mcp',
        ];
    }

    private function approve(string $clientId): string
    {
        $query = $this->authorizeQuery($clientId);
        $consent = $this->request('GET', '/oauth/authorize?' . http_build_query($query));
        self::assertSame(200, $consent->status, $consent->body);
        $r = $this->request('POST', '/oauth/authorize', [...$query, 'csrf' => self::csrfFrom($consent), 'decision' => 'approve']);

        return $this->callbackParams($r)['code'];
    }

    /** @return array<string, mixed> */
    private function obtainTokens(string $clientId): array
    {
        $r = $this->token(['grant_type' => 'authorization_code', 'client_id' => $clientId, 'code' => $this->approve($clientId), 'redirect_uri' => self::CALLBACK, 'code_verifier' => self::VERIFIER]);
        self::assertSame(200, $r->status, $r->body);

        return json_decode($r->body, true);
    }

    /** @param array<string, string> $data */
    private function token(array $data): Response
    {
        return $this->request('POST', '/oauth/token', $data, ['Content-Type' => 'application/x-www-form-urlencoded']);
    }

    /** @return array<string, string> */
    private function callbackParams(Response $r): array
    {
        self::assertSame(302, $r->status, $r->body);
        self::assertStringStartsWith(self::CALLBACK . '?', $r->headers['Location']);
        parse_str((string) parse_url($r->headers['Location'], PHP_URL_QUERY), $params);

        return $params;
    }

    private static function initializeBody(): string
    {
        return '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"test","version":"1"}}}';
    }

    private function assertPing(string $token): void
    {
        $headers = ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json', 'Accept' => 'application/json, text/event-stream'];
        $init = $this->request('POST', '/mcp', [], $headers, self::initializeBody());
        self::assertSame(200, $init->status, $init->body);
        $headers += ['Mcp-Session-Id' => $init->headers['Mcp-Session-Id'], 'MCP-Protocol-Version' => '2025-06-18'];
        self::assertSame(202, $this->request('POST', '/mcp', [], $headers, '{"jsonrpc":"2.0","method":"notifications/initialized"}')->status);
        $call = $this->request('POST', '/mcp', [], $headers, '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"ping","arguments":{}}}');
        self::assertSame(200, $call->status, $call->body);
        $result = json_decode($call->body, true)['result'];
        self::assertFalse($result['isError']);
        self::assertSame('training', $result['structuredContent']['server']);
        self::assertSame($this->clock->now(), $result['structuredContent']['unix']);
    }
}
