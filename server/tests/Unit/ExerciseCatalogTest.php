<?php

declare(strict_types=1);

namespace Training\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Training\Exercise\Catalog;
use Training\Exercise\ContentValidator;
use Training\Plan\PlanValidator;

/** AP-16 T1: Normalisierung (E-09), Slug (E-08), Embed-Ableitung (E-04), Schemata content_json und plan_json. */
final class ExerciseCatalogTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function normalized(): array
    {
        return [
            'Groß/Klein' => ['Kniebeuge', 'kniebeuge'],
            'Umlaute' => ['Rückenstrecker Übung Größe', 'rueckenstrecker uebung groesse'],
            'ß' => ['Fußheber', 'fussheber'],
            'Bindestrich = Leerzeichen' => ['Bulgarian-Split  Squat', 'bulgarian split squat'],
            'Satzzeichen' => ['Kniebeuge (Rückenlage), exzentrisch!', 'kniebeuge rueckenlage exzentrisch'],
            'Akzente' => ['Relevé', 'releve'],
            'außen getrimmt' => ['  - Plank -  ', 'plank'],
        ];
    }

    #[DataProvider('normalized')]
    public function testNormalize(string $in, string $expected): void
    {
        self::assertSame($expected, Catalog::normalize($in));
    }

    public function testSlug(): void
    {
        foreach (['kniebeuge', 'max-hang-20mm', 'abc', str_repeat('a', 60)] as $ok) {
            self::assertTrue(Catalog::isSlug($ok), $ok);
        }
        foreach (['ab', str_repeat('a', 61), 'Kniebeuge', 'knie_beuge', '-knie', 'knie-', 'knie--beuge', 'kniebeuge ', 'übung', null, 5] as $bad) {
            self::assertFalse(Catalog::isSlug($bad), var_export($bad, true));
        }
    }

    /** @return array<string, array{string, ?string}> */
    public static function embeds(): array
    {
        $yt = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ';

        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', $yt],
            'watch mit Zeit' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s', $yt],
            'ohne www' => ['https://youtube.com/watch?v=dQw4w9WgXcQ', $yt],
            'mobil' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', $yt],
            'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ', $yt],
            'youtu.be mit Query' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', $yt],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', $yt],
            'vimeo' => ['https://vimeo.com/76979871', 'https://player.vimeo.com/video/76979871'],
            'vimeo www' => ['https://www.vimeo.com/76979871/', 'https://player.vimeo.com/video/76979871'],
            'http' => ['http://www.youtube.com/watch?v=dQw4w9WgXcQ', null],
            'falsche ID' => ['https://www.youtube.com/watch?v=kurz', null],
            'Playlist' => ['https://www.youtube.com/playlist?list=PL123', null],
            'Kanal' => ['https://vimeo.com/channels/staffpicks', null],
            'anderer Host' => ['https://www.dailymotion.com/video/x7tgad0', null],
            'ähnlicher Host' => ['https://youtube.com.example.org/watch?v=dQw4w9WgXcQ', null],
        ];
    }

    #[DataProvider('embeds')]
    public function testEmbedUrl(string $url, ?string $expected): void
    {
        self::assertSame($expected, Catalog::embedUrl($url));
    }

    /** @return array<string, mixed> */
    public static function content(): array
    {
        return [
            'kurz' => 'Einbeinige Kniebeuge mit erhöhtem hinterem Fuß.',
            'ziel' => 'Quadrizeps und Gesäß einbeinig, Kniestabilität.',
            'muskeln' => ['Quadrizeps', 'Gluteus maximus'],
            'ausfuehrung' => ['Hinteren Fuß auf die Bank legen.', 'Senkrecht absenken, bis das hintere Knie fast den Boden berührt.'],
            'achten' => ['Knie über dem Fuß'],
            'fehler' => ['Knie fällt nach innen'],
            'vorsicht' => ['Patellasehne: Schmerz ≤ 3/10 während und am Folgetag (Block R).'],
            'links' => [
                ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'titel' => 'Video 1', 'art' => 'video'],
                ['url' => 'https://vimeo.com/76979871', 'titel' => 'Video 2', 'art' => 'video'],
                ['url' => 'https://example.org/split-squat', 'titel' => 'Beschreibung', 'art' => 'text'],
                ['url' => 'https://example.org/b', 'titel' => 'Beschreibung 2', 'art' => 'text'],
            ],
            'quellen' => ['L-T2-04'],
        ];
    }

    public function testContentPrepareSetsServerFields(): void
    {
        $input = self::content();
        $input['links'][0] += ['embed' => 'https://evil.example/x', 'status' => 'ok', 'geprueft_am' => '2020-01-01'];
        $r = ContentValidator::default()->prepare($input);
        self::assertSame([], $r['errors']);
        self::assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $r['content']['links'][0]['embed'], 'Eingabe überschrieben');
        self::assertSame('ungeprueft', $r['content']['links'][0]['status']);
        self::assertNull($r['content']['links'][0]['geprueft_am']);
        self::assertSame('https://player.vimeo.com/video/76979871', $r['content']['links'][1]['embed']);
        self::assertNull($r['content']['links'][2]['embed'], 'Textlink ohne Einbettung');
        $minimal = ContentValidator::default()->prepare(['kurz' => 'x', 'ziel' => 'y', 'ausfuehrung' => ['a', 'b'], 'quellen' => ['Einschätzung']]);
        self::assertSame([], $minimal['errors']);
        self::assertSame([], $minimal['content']['links']);
    }

    /** @return array<string, array{callable(array<string, mixed>): array<string, mixed>, string}> */
    public static function invalidContent(): array
    {
        $video = ['url' => 'https://youtu.be/dQw4w9WgXcQ', 'titel' => 'V', 'art' => 'video'];
        $text = ['url' => 'https://example.org/c', 'titel' => 'T', 'art' => 'text'];

        return [
            '3 Videolinks' => [static fn (array $c): array => ['links' => [...$c['links'], $video]] + $c, '/links'],
            '3 Textlinks' => [static fn (array $c): array => ['links' => [$c['links'][0], $text, $text, $text]] + $c, '/links'],
            '5 Links' => [static fn (array $c): array => ['links' => [...$c['links'], $text]] + $c, '/links'],
            'http' => [static fn (array $c): array => ['links' => [['url' => 'http://example.org', 'titel' => 'T', 'art' => 'text']]] + $c, '/links/0/url'],
            'keine URL' => [static fn (array $c): array => ['links' => [['url' => 'https://', 'titel' => 'T', 'art' => 'text']]] + $c, '/links/0/url'],
            'Art unbekannt' => [static fn (array $c): array => ['links' => [['url' => 'https://example.org', 'titel' => 'T', 'art' => 'bild']]] + $c, '/links/0/art'],
            'kurz zu lang' => [static fn (array $c): array => ['kurz' => str_repeat('x', 201)] + $c, '/kurz'],
            'Ausführung 1 Schritt' => [static fn (array $c): array => ['ausfuehrung' => ['nur einer']] + $c, '/ausfuehrung'],
            'Ausführung 13 Schritte' => [static fn (array $c): array => ['ausfuehrung' => array_fill(0, 13, 'x')] + $c, '/ausfuehrung'],
            'Schritt zu lang' => [static fn (array $c): array => ['ausfuehrung' => ['a', str_repeat('x', 301)]] + $c, '/ausfuehrung/1'],
            'vorsicht 7' => [static fn (array $c): array => ['vorsicht' => array_fill(0, 7, 'x')] + $c, '/vorsicht'],
            'ohne Quelle' => [static fn (array $c): array => ['quellen' => []] + $c, '/quellen'],
            'Quelle fehlt' => [static function (array $c): array { unset($c['quellen']); return $c; }, 'quellen'],
            'unbekanntes Feld' => [static fn (array $c): array => ['bild' => 'x'] + $c, 'bild'],
        ];
    }

    /** @param callable(array<string, mixed>): array<string, mixed> $mutate */
    #[DataProvider('invalidContent')]
    public function testInvalidContentIsRejected(callable $mutate, string $fragment): void
    {
        $errors = ContentValidator::default()->prepare($mutate(self::content()))['errors'];
        self::assertNotSame([], $errors);
        self::assertStringContainsString($fragment, implode("\n", $errors));
    }

    public function testPlanSchemaExerciseId(): void
    {
        $v = PlanValidator::default();
        $ex = static fn (array $e): array => ['exercises' => [['name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8'] + $e]];
        self::assertSame([], $v->validatePlan('kraft', $ex(['exercise_id' => 'kniebeuge'])));
        self::assertSame([], $v->validatePlan('mobilitaet', $ex(['exercise_id' => null])));
        self::assertSame([], $v->validatePlan('haltung', $ex([])), 'ohne exercise_id weiterhin gültig (E-03)');
        self::assertNotSame([], $v->validatePlan('kraft', $ex(['exercise_id' => 'Knie Beuge'])));
        self::assertNotSame([], $v->validatePlan('kraft', $ex(['exercise_id' => 'kb'])));

        foreach (['hangboard', 'campus', 'zugkraft', 'antagonisten'] as $kind) {
            self::assertSame([], $v->validatePlan('klettern', ['blocks' => [['kind' => $kind, 'exercise_id' => 'max-hang-20mm']]]), $kind); // V-06
        }
        foreach (['bouldern_volumen', 'bouldern_limit', 'ausdauer_route', 'technik'] as $kind) {
            $errors = $v->validatePlan('klettern', ['blocks' => [['kind' => $kind, 'exercise_id' => 'max-hang-20mm']]]); // V-07 (E-05)
            self::assertStringContainsString('/blocks/0/exercise_id', implode("\n", $errors), $kind);
            self::assertSame([], $v->validatePlan('klettern', ['blocks' => [['kind' => $kind, 'exercise_id' => null]]]), $kind);
            self::assertSame([], $v->validatePlan('klettern', ['blocks' => [['kind' => $kind]]]), $kind);
        }
        self::assertNotSame([], $v->validatePlan('klettern', ['blocks' => [['kind' => 'bouldern_volumen', 'exercise_id' => 'x']]]), 'V-07 wörtlich');
    }
}
