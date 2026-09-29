<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Training\Exercise\CurlLinkFetcher;
use Training\Exercise\LinkChecker;
use Training\Tests\Support\FakeClock;
use Training\Tests\Support\FakeLinkFetcher;

/** AP-16 T2: Bewertung der Linkprüfung (E-10, E-18) und Schutzregeln (E-19). */
final class LinkCheckerTest extends TestCase
{
    public function testRatingAndPreviousResult(): void
    {
        $fetcher = new FakeLinkFetcher(['/ok' => 200, '/weg' => 404, '/gone' => 410, '/bot' => 403, '/fehler' => 500, '/viel' => 429, '/timeout' => 'nicht erreichbar: Timeout',
            '/intern' => 'Ziel im internen Netz gesperrt', '/umleitung' => 'zu viele Weiterleitungen', 'oembed' => 403]);
        $checker = new LinkChecker($fetcher, new FakeClock(1790164800));
        $link = static fn (string $path, string $art = 'text', ?string $embed = null): array => ['url' => 'https://example.org' . $path, 'titel' => 't', 'art' => $art, 'embed' => $embed];
        $r = $checker->check([$link('/ok'), $link('/weg'), $link('/gone'), $link('/bot'), $link('/fehler'), $link('/viel'), $link('/timeout'), $link('/intern'), $link('/umleitung'),
            $link('/video', 'video', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')]);
        self::assertSame(['ok', 'defekt', 'defekt', 'ungeprueft', 'ungeprueft', 'ungeprueft', 'ungeprueft', 'defekt', 'ungeprueft', 'defekt'], array_column($r, 'status'));
        self::assertSame(['2026-09-23', null], [$r[0]['geprueft_am'], $r[3]['geprueft_am']]);
        self::assertSame('links_pruefen', LinkChecker::exerciseStatus($r));
        self::assertSame('aktiv', LinkChecker::exerciseStatus([$r[0], $r[3]]));

        // Netzfehler: früheres Ergebnis derselben Adresse bleibt
        $again = $checker->check([$link('/timeout')], [['url' => 'https://example.org/timeout', 'status' => 'ok', 'geprueft_am' => '2026-09-01']]);
        self::assertSame(['ok', '2026-09-01'], [$again[0]['status'], $again[0]['geprueft_am']]);
        // mehrere Übungen in einem Durchgang
        $fetcher->calls = [];
        $many = $checker->checkMany([7 => [$link('/ok')], 9 => [$link('/weg'), $link('/ok')]]);
        self::assertSame([7, 9], array_keys($many));
        self::assertCount(1, $fetcher->calls);
        self::assertCount(2, $fetcher->calls[0], 'gleiche Adresse nur einmal');
    }

    public function testCheckUrl(): void
    {
        self::assertSame('https://www.youtube.com/oembed?format=json&url=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DdQw4w9WgXcQ',
            LinkChecker::checkUrl(['url' => 'https://youtu.be/dQw4w9WgXcQ', 'embed' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ']));
        self::assertSame('https://vimeo.com/api/oembed.json?url=https%3A%2F%2Fvimeo.com%2F76979871',
            LinkChecker::checkUrl(['url' => 'https://vimeo.com/76979871', 'embed' => 'https://player.vimeo.com/video/76979871']));
        self::assertSame('https://www.dailymotion.com/video/x', LinkChecker::checkUrl(['url' => 'https://www.dailymotion.com/video/x', 'embed' => null]));
    }

    public function testTargetProtection(): void
    {
        foreach (['http://example.org/' => 'nur https', 'https://example.org:8443/' => 'Port 443', 'https://user:pw@example.org/' => 'Zugangsdaten',
            'https://127.0.0.1/' => 'internen Netz', 'https://10.0.0.8/' => 'internen Netz', 'https://[::1]/' => 'internen Netz', 'https://169.254.169.254/latest' => 'internen Netz',
            'https://localhost/' => 'internen Netz', 'ftp://example.org/' => 'nur https'] as $url => $reason) {
            $t = CurlLinkFetcher::target($url);
            self::assertIsString($t, $url);
            self::assertStringContainsString($reason, $t, $url);
        }
        self::assertSame(['host' => '93.184.215.14', 'ip' => '93.184.215.14'], CurlLinkFetcher::target('https://93.184.215.14/pfad'));
        self::assertSame(['host' => '93.184.215.14', 'ip' => '93.184.215.14'], CurlLinkFetcher::target('https://93.184.215.14:443/'));
        // gesperrte Ziele werden nicht abgerufen
        self::assertSame(['https://127.0.0.1/x' => 'Ziel im internen Netz gesperrt'], (new CurlLinkFetcher())->fetchAll(['https://127.0.0.1/x']));
    }
}
