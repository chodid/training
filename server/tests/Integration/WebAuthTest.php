<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Auth\SessionManager;
use Training\Tests\Support\AppTestCase;

/** S0 Setup (D-34), S1 Login mit Kontosperre und 30-Tage-Session (D-33). */
final class WebAuthTest extends AppTestCase
{
    public function testSetupCreatesExactlyOneUserAndIsThen404(): void
    {
        self::assertSame(303, $this->request('GET', '/')->status);
        self::assertSame('/setup', $this->request('GET', '/')->headers['Location']);
        $form = $this->request('GET', '/setup');
        self::assertSame(200, $form->status);
        self::assertStringContainsString('Benutzer anlegen', $form->body);

        $wrong = $this->request('POST', '/setup', [
            'csrf' => self::csrfFrom($form), 'secret' => 'falsch', 'login' => 'philipp',
            'password' => 'richtig-langes-passwort', 'password2' => 'richtig-langes-passwort', 'tz' => 'Europe/Berlin',
        ]);
        self::assertSame(422, $wrong->status);
        self::assertStringContainsString('Deploy-Secret stimmt nicht', $wrong->body);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM `user`')->fetchColumn());

        $noCsrf = $this->request('POST', '/setup', [
            'csrf' => 'x', 'secret' => self::MIGRATION_SECRET, 'login' => 'philipp',
            'password' => 'richtig-langes-passwort', 'password2' => 'richtig-langes-passwort', 'tz' => 'Europe/Berlin',
        ]);
        self::assertSame(422, $noCsrf->status);

        $short = $this->request('POST', '/setup', [
            'csrf' => self::csrfFrom($form), 'secret' => self::MIGRATION_SECRET, 'login' => 'philipp',
            'password' => 'kurz', 'password2' => 'kurz', 'tz' => 'Europe/Berlin',
        ]);
        self::assertSame(422, $short->status);

        $this->setupUser();
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM `user`')->fetchColumn());
        $hash = (string) $this->pdo->query('SELECT password_hash FROM `user`')->fetchColumn();
        self::assertTrue(password_verify('richtig-langes-passwort', $hash));
        self::assertArrayHasKey(SessionManager::COOKIE, $this->cookies, 'Setup meldet direkt an');

        self::assertSame(404, $this->request('GET', '/setup')->status);
        self::assertSame(404, $this->request('POST', '/setup', ['secret' => self::MIGRATION_SECRET])->status);
    }

    public function testLoginSessionSurvivesAndExpiresAfter30Days(): void
    {
        $this->setupUser();
        $this->cookies = [];
        self::assertSame('/login', $this->request('GET', '/')->headers['Location']);

        $this->login('philipp', 'richtig-langes-passwort', 303);
        $cookie = $this->cookies[SessionManager::COOKIE];
        $raw = $this->lastSetCookie;
        self::assertStringContainsString('HttpOnly', $raw);
        self::assertStringContainsString('Secure', $raw);
        self::assertStringContainsString('SameSite=Lax', $raw);
        self::assertStringContainsString('Max-Age=2592000', $raw);
        self::assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM web_session WHERE token_hash = '" . $cookie . "'")->fetchColumn(), 'Token nur gehasht');

        // "Browser-Neustart": nur das persistente Cookie bleibt.
        $this->cookies = [SessionManager::COOKIE => $cookie];
        $home = $this->request('GET', '/');
        self::assertSame(200, $home->status);
        self::assertStringContainsString('philipp', $home->body);

        // Gleitend: nach 20 Tagen Nutzung weitere 20 Tage gültig.
        $this->clock->advance(20 * 86400);
        self::assertSame(200, $this->request('GET', '/')->status);
        $this->clock->advance(20 * 86400);
        self::assertSame(200, $this->request('GET', '/')->status);

        // Ohne Nutzung nach 30 Tagen abgelaufen.
        $this->clock->advance(31 * 86400);
        self::assertSame(303, $this->request('GET', '/')->status);
    }

    public function testLogoutNeedsCsrfAndEndsSession(): void
    {
        $this->setupUser();
        $home = $this->request('GET', '/');
        self::assertSame(403, $this->request('POST', '/logout', ['csrf' => 'falsch'])->status);
        self::assertSame(200, $this->request('GET', '/')->status);
        $out = $this->request('POST', '/logout', ['csrf' => self::csrfFrom($home)]);
        self::assertSame(303, $out->status);
        self::assertArrayNotHasKey(SessionManager::COOKIE, $this->cookies);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM web_session')->fetchColumn());
    }

    public function testTenFailuresLockFiveMinutesThenDoublingAndSuccessResets(): void
    {
        $this->setupUser();
        $this->cookies = [];

        for ($i = 1; $i <= 9; $i++) {
            $r = $this->login('philipp', 'falsch', 401);
            self::assertStringContainsString($i === 9 ? 'Noch 1 Versuch' : 'Noch ' . (10 - $i) . ' Versuche', $r->body);
        }
        $locked = $this->login('philipp', 'falsch', 429);
        self::assertStringContainsString('Konto vorübergehend gesperrt', $locked->body);
        self::assertStringContainsString('in 5 Minuten', $locked->body);
        self::assertStringNotContainsString('name="password"', $locked->body, 'Passwortfeld bei Sperre ausgeblendet');

        // Während der Sperre hilft auch das richtige Passwort nicht, und es wird nicht gezählt.
        $this->clock->advance(299);
        $this->login('philipp', 'richtig-langes-passwort', 429);
        self::assertSame(10, $this->failures());
        self::assertStringContainsString('gesperrt', $this->request('GET', '/login')->body);

        // Nach Ablauf: weiterer Fehlversuch → 10 Minuten.
        $this->clock->advance(1);
        $r = $this->login('philipp', 'falsch', 429);
        self::assertStringContainsString('in 10 Minuten', $r->body);
        $this->clock->advance(600);
        $r = $this->login('philipp', 'falsch', 429);
        self::assertStringContainsString('in 20 Minuten', $r->body);
        self::assertSame(12, $this->failures());

        // Nach Ablauf: Erfolg setzt Zähler und Sperre zurück.
        $this->clock->advance(1200);
        $this->login('philipp', 'richtig-langes-passwort', 303);
        self::assertSame(0, $this->failures());
        self::assertNull($this->pdo->query('SELECT locked_until FROM `user`')->fetchColumn());
    }

    public function testLockIsCappedAt24Hours(): void
    {
        $this->setupUser();
        $this->pdo->exec('UPDATE `user` SET failed_logins = 40');
        $this->cookies = [];
        $this->login('philipp', 'falsch', 429);
        $until = strtotime($this->pdo->query('SELECT locked_until FROM `user`')->fetchColumn() . ' UTC');
        self::assertSame(86400, $until - $this->clock->now());
    }

    public function testWrongLoginNameCountsAsFailure(): void
    {
        $this->setupUser();
        $this->cookies = [];
        $this->login('jemand', 'richtig-langes-passwort', 401);
        self::assertSame(1, $this->failures());
    }

    public function testLoginRedirectsOnlyToLocalNext(): void
    {
        $this->setupUser();
        $this->cookies = [];
        $form = $this->request('GET', '/login', ['next' => '/oauth/authorize?x=1']);
        self::assertStringContainsString('value="/oauth/authorize?x=1"', $form->body);
        $r = $this->request('POST', '/login', ['csrf' => self::csrfFrom($form), 'login' => 'philipp', 'password' => 'richtig-langes-passwort', 'next' => '//evil.example']);
        self::assertSame('/', $r->headers['Location']);
    }

    public function testHtmlPagesSendSecurityHeaders(): void
    {
        $r = $this->request('GET', '/setup');
        self::assertSame('DENY', $r->headers['X-Frame-Options']);
        self::assertStringContainsString("frame-ancestors 'none'", $r->headers['Content-Security-Policy']);
    }

    private string $lastSetCookie = '';

    private function login(string $login, string $password, int $expectedStatus): \Training\Http\Response
    {
        $form = $this->request('GET', '/login');
        $r = $this->request('POST', '/login', ['csrf' => self::csrfFrom($form), 'login' => $login, 'password' => $password, 'next' => '']);
        self::assertSame($expectedStatus, $r->status, $r->body);
        foreach ($r->cookies as $c) {
            if (str_starts_with($c, SessionManager::COOKIE . '=')) {
                $this->lastSetCookie = $c;
            }
        }

        return $r;
    }

    private function failures(): int
    {
        return (int) $this->pdo->query('SELECT failed_logins FROM `user`')->fetchColumn();
    }
}
