<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Training\Controller\OAuthController;
use Training\Controller\WebController;
use Training\Mcp\TokenValidator;
use Training\OAuth\Jwt;
use Training\OAuth\OAuthConfig;
use Training\OAuth\Pkce;
use Training\OAuth\RedirectUriPolicy;
use Training\OAuth\TokenIssuer;
use Training\Tests\Support\FakeClock;

final class OAuthUnitTest extends TestCase
{
    private const SECRET = 'geheimgeheimgeheimgeheimgeheim-32';

    /** @return list<array{string, bool}> */
    public static function redirectUris(): array
    {
        return [
            ['https://claude.ai/api/mcp/auth_callback', true],
            ['https://claude.com/api/mcp/auth_callback', true],
            ['http://localhost:6274/oauth/callback', true],
            ['http://127.0.0.1/cb', true],
            ['http://[::1]:8080/cb', true],
            ['http://claude.ai/api/mcp/auth_callback', false],
            ['http://localhost.evil.com/cb', false],
            ['https://claude.ai/cb#frag', false],
            ['https://user:pw@claude.ai/cb', false],
            ['javascript:alert(1)', false],
            ['claude://callback', false],
            ['/relativ', false],
            ['', false],
        ];
    }

    #[DataProvider('redirectUris')]
    public function testRedirectUriPolicy(string $uri, bool $allowed): void
    {
        self::assertSame($allowed, RedirectUriPolicy::isAllowed($uri));
    }

    public function testPkceS256MatchesRfc7636Example(): void
    {
        $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
        self::assertSame('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM', Pkce::challenge($verifier));
        self::assertTrue(Pkce::verify($verifier, 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM'));
        self::assertFalse(Pkce::verify($verifier . 'x', 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM'));
        self::assertFalse(Pkce::verify('zu-kurz', Pkce::challenge('zu-kurz')));
        self::assertFalse(Pkce::isValidChallenge('plain-verifier'));
    }

    public function testGrantScope(): void
    {
        self::assertSame('training:read training:write', OAuthConfig::grantScope(null));
        self::assertSame('training:read training:write', OAuthConfig::grantScope(''));
        self::assertSame('training:read', OAuthConfig::grantScope('training:read'));
        self::assertSame('training:read training:write', OAuthConfig::grantScope('mcp openid'));
        self::assertSame('training:write', OAuthConfig::grantScope('foo training:write'));
    }

    public function testIssuedJwtIsAcceptedBySdkValidator(): void
    {
        $config = new OAuthConfig('https://training.example', self::SECRET);
        $token = (new TokenIssuer($config, new FakeClock(time())))->accessToken(1, 'client', 'training:read');
        $result = (new TokenValidator($config))->validate($token);
        self::assertTrue($result->valid, (string) $result->error);
        self::assertSame('https://training.example', $result->claims['iss']);
        self::assertSame('https://training.example/mcp', $result->claims['aud']);
        self::assertSame('1', $result->claims['sub']);
        self::assertSame('client', $result->claims['client_id']);
        self::assertSame('training:read', $result->claims['scope']);
        self::assertSame(3600, $result->claims['exp'] - $result->claims['iat']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result->claims['jti']);
    }

    public function testExpiredForeignOrWrongAudienceJwtIsRejected(): void
    {
        $config = new OAuthConfig('https://training.example', self::SECRET);
        $validator = new TokenValidator($config);

        $expired = (new TokenIssuer($config, new FakeClock(time() - 3601)))->accessToken(1, 'c', 'training:read');
        self::assertFalse($validator->validate($expired)->valid);

        $foreign = (new TokenIssuer(new OAuthConfig('https://training.example', str_repeat('z', 32)), new FakeClock(time())))->accessToken(1, 'c', 'training:read');
        self::assertFalse($validator->validate($foreign)->valid);

        $otherAudience = Jwt::encode(['iss' => 'https://training.example', 'aud' => 'https://anders.example/mcp', 'exp' => time() + 60], self::SECRET);
        self::assertFalse($validator->validate($otherAudience)->valid);

        $noExp = Jwt::encode(['iss' => 'https://training.example', 'aud' => 'https://training.example/mcp'], self::SECRET);
        self::assertFalse($validator->validate($noExp)->valid);

        self::assertFalse($validator->validate('kein.jwt')->valid);
    }

    public function testStaticTokenOnlyWhenEnabled(): void
    {
        $static = str_repeat('s', 40);
        self::assertFalse((new TokenValidator(new OAuthConfig('https://t.example', self::SECRET, null)))->validate($static)->valid);
        $result = (new TokenValidator(new OAuthConfig('https://t.example', self::SECRET, $static)))->validate($static);
        self::assertTrue($result->valid);
        self::assertSame('training:read training:write', $result->claims['scope']);
    }

    public function testSafeNextAllowsOnlyLocalPaths(): void
    {
        self::assertSame('/oauth/authorize?x=1', WebController::safeNext('/oauth/authorize?x=1'));
        self::assertSame('', WebController::safeNext('//evil.example'));
        self::assertSame('', WebController::safeNext('https://evil.example'));
        self::assertSame('', WebController::safeNext('/\\evil.example'));
        self::assertSame('', WebController::safeNext("/a\nb"));
        self::assertSame('', WebController::safeNext(null));
    }

    public function testAppendQuery(): void
    {
        self::assertSame('https://a.example/cb?code=1&state=a%20b', OAuthController::appendQuery('https://a.example/cb', ['code' => '1', 'state' => 'a b']));
        self::assertSame('https://a.example/cb?x=1&code=1', OAuthController::appendQuery('https://a.example/cb?x=1', ['code' => '1']));
    }
}
