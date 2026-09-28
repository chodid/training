<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Tests\Support\AppTestCase;

/** AP-13 T1 (I-02): Beide Seitenrahmen tragen Manifest, PNG-Icons mit Größe, apple-touch-icon und theme-color. */
final class AppIconPagesTest extends AppTestCase
{
    private const HEAD = [
        '<meta name="theme-color" content="#7A5C94">',
        '<link rel="manifest" href="/manifest.webmanifest">',
        '<link rel="icon" href="/icons/favicon.svg" type="image/svg+xml">',
        '<link rel="icon" href="/icons/lama-48.png" type="image/png" sizes="48x48">',
        '<link rel="icon" href="/icons/lama-96.png" type="image/png" sizes="96x96">',
        '<link rel="icon" href="/icons/lama-192.png" type="image/png" sizes="192x192">',
        '<link rel="icon" href="/icons/lama-512.png" type="image/png" sizes="512x512">',
        '<link rel="apple-touch-icon" href="/icons/apple-touch-icon-180.png" sizes="180x180">',
    ];

    public function testAuthAndAppLayoutsCarryIconsAndManifest(): void
    {
        $setup = $this->request('GET', '/setup');
        self::assertSame(200, $setup->status);
        $this->assertHead($setup->body, 'Setup (layout-auth)');

        $this->setupUser();
        $this->cookies = [];
        $login = $this->request('GET', '/login');
        self::assertSame(200, $login->status);
        $this->assertHead($login->body, 'Login (layout-auth)');
        self::assertStringContainsString('<img src="/assets/lama.svg" alt="">', $login->body, 'Login-Karte mit ganzem Lama (D-59)');

        $done = $this->request('POST', '/login', ['csrf' => self::csrfFrom($login), 'login' => 'philipp', 'password' => 'richtig-langes-passwort']);
        self::assertSame(303, $done->status, $done->body);
        $week = $this->request('GET', '/woche');
        self::assertSame(200, $week->status, $week->body);
        $this->assertHead($week->body, 'Woche (layout-app)');
        self::assertSame(2, substr_count($week->body, '<img src="/assets/lama.svg" alt="">'), 'Topbar und Navigation');
        self::assertStringNotContainsString('lama-kopf', $week->body . $login->body);
    }

    private function assertHead(string $html, string $page): void
    {
        $head = (string) strstr($html, '</head>', true);
        foreach (self::HEAD as $line) {
            self::assertStringContainsString($line, $head, $page);
        }
    }
}
