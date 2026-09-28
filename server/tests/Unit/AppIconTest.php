<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\TestCase;

/** AP-13 T1 (I-01, I-03): Manifest, Icon-Dateien und Favicon über den Dev-Router. */
final class AppIconTest extends TestCase
{
    private static function public(): string
    {
        return dirname(__DIR__, 2) . '/public';
    }

    /** @return array{0: int, 1: int} Breite und Höhe aus dem IHDR-Block einer PNG-Datei */
    private static function pngSize(string $data): array
    {
        self::assertSame("\x89PNG\r\n\x1a\n", substr($data, 0, 8), 'PNG-Signatur');
        self::assertSame('IHDR', substr($data, 12, 4));
        $size = unpack('Nw/Nh', substr($data, 16, 8));

        return [$size['w'], $size['h']];
    }

    public function testManifestIsValidAndIconSizesMatchPngHeaders(): void
    {
        $manifest = json_decode((string) file_get_contents(self::public() . '/manifest.webmanifest'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('/woche', $manifest['id']);
        self::assertSame('/woche', $manifest['start_url']);
        self::assertSame('standalone', $manifest['display']);
        self::assertSame('#7A5C94', $manifest['theme_color']);
        self::assertNotEmpty($manifest['description']);

        $any = [];
        $maskable = [];
        foreach ($manifest['icons'] as $icon) {
            $file = self::public() . $icon['src'];
            self::assertFileExists($file, $icon['src']);
            self::assertSame('image/png', $icon['type']);
            [$w, $h] = self::pngSize((string) file_get_contents($file));
            self::assertSame($icon['sizes'], $w . 'x' . $h, $icon['src']);
            self::assertContains($icon['purpose'], ['any', 'maskable'], 'getrennte Einträge, kein „any maskable“');
            if ($icon['purpose'] === 'any') {
                $any[] = $w;
            } else {
                $maskable[] = $w;
            }
        }
        self::assertSame([48, 96, 192, 512], $any);
        self::assertSame([512], $maskable);
    }

    public function testHeadIconsReferenceExistingFilesWithMatchingSizes(): void
    {
        $partial = (string) file_get_contents(dirname(__DIR__, 2) . '/templates/_head_icons.php');
        self::assertSame(7, preg_match_all('/<link rel="([a-z-]+)" href="([^"]+)"(?: type="([^"]+)")?(?: sizes="(\d+)x(\d+)")?>/', $partial, $m, PREG_SET_ORDER));
        foreach ($m as $link) {
            $file = self::public() . $link[2];
            self::assertFileExists($file, $link[2]);
            if (isset($link[4]) && $link[4] !== '') {
                self::assertSame([(int) $link[4], (int) $link[5]], self::pngSize((string) file_get_contents($file)), $link[2]);
            }
        }
        self::assertStringContainsString('<link rel="apple-touch-icon" href="/icons/apple-touch-icon-180.png" sizes="180x180">', $partial);
        self::assertStringContainsString('<link rel="icon" href="/icons/favicon.svg" type="image/svg+xml">', $partial);
        self::assertStringContainsString('<meta name="theme-color" content="#7A5C94">', $partial);
    }

    public function testFaviconIcoContainsSixteenThirtyTwoAndFortyEightPixels(): void
    {
        $ico = (string) file_get_contents(self::public() . '/favicon.ico');
        $head = unpack('vreserved/vtype/vcount', substr($ico, 0, 6));
        self::assertSame([0, 1, 3], [$head['reserved'], $head['type'], $head['count']]);
        $sizes = [];
        for ($i = 0; $i < $head['count']; $i++) {
            $e = unpack('Cw/Ch/Ccolors/Creserved/vplanes/vbits/Vbytes/Voffset', substr($ico, 6 + 16 * $i, 16));
            [$w, $h] = self::pngSize(substr($ico, $e['offset'], $e['bytes']));
            self::assertSame([$e['w'], $e['h']], [$w, $h]);
            $sizes[] = $w;
        }
        self::assertSame([16, 32, 48], $sizes);
    }

    public function testDevRouterServesFaviconAndManifestWithApacheTypes(): void
    {
        $root = dirname(__DIR__, 2);
        $server = null;
        $port = 0;
        for ($try = 0; $try < 5 && $server === null; $try++) {
            // Freien Port vom System holen; gilt nur, solange der gestartete Server selbst läuft (sonst belegt ihn ein anderer)
            $probe = stream_socket_server('tcp://127.0.0.1:0');
            self::assertNotFalse($probe);
            $port = (int) substr((string) strrchr((string) stream_socket_get_name($probe, false), ':'), 1);
            fclose($probe);
            $proc = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $root . '/public', $root . '/bin/dev-router.php'],
                [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
            self::assertIsResource($proc);
            for ($i = 0; $i < 50; $i++) {
                if (!proc_get_status($proc)['running']) {
                    break;
                }
                $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
                if ($socket !== false) {
                    fclose($socket);
                    if (proc_get_status($proc)['running']) {
                        $server = $proc;
                    }
                    break;
                }
                usleep(100_000);
            }
            if ($server === null) {
                proc_terminate($proc);
                proc_close($proc);
            }
        }
        self::assertNotNull($server, 'Dev-Server startet nicht');
        try {
            $head = self::head('http://127.0.0.1:' . $port . '/favicon.ico');
            self::assertStringContainsString(' 200 ', $head[0]);
            self::assertContains('Content-Type: image/x-icon', $head);
            self::assertContains('Content-Length: ' . filesize($root . '/public/favicon.ico'), $head);
            self::assertContains('Content-Type: application/manifest+json', self::head('http://127.0.0.1:' . $port . '/manifest.webmanifest'));
            $png = self::head('http://127.0.0.1:' . $port . '/icons/lama-192.png');
            self::assertStringContainsString(' 200 ', $png[0]);
            self::assertContains('Content-Type: image/png', $png);
        } finally {
            proc_terminate($server);
            proc_close($server);
        }
    }

    /** @return list<string> Statuszeile und Kopfzeilen einer HEAD-Anfrage */
    private static function head(string $url): array
    {
        $context = stream_context_create(['http' => ['method' => 'HEAD', 'ignore_errors' => true, 'timeout' => 5]]);
        $stream = @fopen($url, 'r', false, $context);
        self::assertNotFalse($stream, $url);
        $headers = stream_get_meta_data($stream)['wrapper_data'];
        fclose($stream);

        return array_map(static fn (string $h): string => preg_replace('/;\s*charset=.*$/i', '', $h) ?? $h, $headers);
    }
}
