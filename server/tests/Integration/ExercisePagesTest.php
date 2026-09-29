<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Data\ExerciseRepository;
use Training\Exercise\ContentValidator;
use Training\Tests\Support\AppTestCase;

/** AP-16 T3: S10 Übung und S10a Übungskatalog (W-01 bis W-03, W-07), S8-Eintrag. */
final class ExercisePagesTest extends AppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupUser();
    }

    /** @param array<string, mixed> $content @param array<string, mixed> $over */
    private function add(string $slug, string $name, array $content = [], array $over = []): int
    {
        $prepared = ContentValidator::default()->prepare($content + ['kurz' => $name . ' kurz', 'ziel' => 'Ziel', 'ausfuehrung' => ['Schritt eins', 'Schritt zwei'], 'quellen' => ['L-T2-04']]);
        self::assertSame([], $prepared['errors']);
        foreach ($prepared['content']['links'] as &$l) {
            $l['status'] = str_contains($l['url'], 'weg') ? 'defekt' : 'ok';
            $l['geprueft_am'] = '2026-09-23';
        }
        unset($l);

        return (new ExerciseRepository($this->pdo, $this->clock))->create($over + ['slug' => $slug, 'name' => $name, 'aliases' => [], 'category' => 'kraft',
            'pattern' => 'knie_dominant', 'equipment' => ['kurzhantel'], 'variant_of' => null, 'difficulty' => 3, 'status' => 'aktiv', 'konfidenz' => 'mittel',
            'content' => $prepared['content']], 'mcp');
    }

    public function testExercisePageWithAllSections(): void
    {
        $id = $this->add('bulgarian-split-squat', 'Bulgarian Split Squat', [
            'muskeln' => ['Quadrizeps'], 'voraussetzung' => 'Bank, 50 cm', 'achten' => ['Knie über dem Fuß'], 'fehler' => ['Knie kippt nach innen'],
            'vorsicht' => ['Patellasehne: Schmerz ≤ 3/10 (Block R)'], 'progression' => 'Kurzhanteln schwerer', 'regression' => 'ohne Gewicht',
            'dosierung_hinweis' => '3 × 8–12 je Seite', 'notizen' => 'intern',
            'links' => [
                ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'titel' => 'Technik-Video', 'art' => 'video'],
                ['url' => 'https://www.dailymotion.com/video/x7tgad0', 'titel' => 'Anderes Video', 'art' => 'video'],
                ['url' => 'https://example.org/anleitung', 'titel' => 'Anleitung <b>', 'art' => 'text'],
            ],
        ], ['aliases' => ['Bulgarische Kniebeuge']]);
        $this->add('bss-weste', 'Split Squat mit Weste', [], ['variant_of' => $id]);

        // W-01 YouTube eingebettet, lazy, Fallback-Link sichtbar; Referrer für YouTube (E-17)
        $r = $this->request('GET', '/uebung?id=bulgarian-split-squat');
        self::assertSame(200, $r->status, $r->body);
        self::assertMatchesRegularExpression('#<iframe src="https://www\.youtube-nocookie\.com/embed/dQw4w9WgXcQ" title="Technik-Video" loading="lazy" allow="fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>#', $r->body);
        self::assertStringContainsString('<figcaption>', $r->body);
        self::assertStringContainsString('href="https://www.youtube.com/watch?v=dQw4w9WgXcQ"', $r->body);
        self::assertSame(1, substr_count($r->body, '<iframe'), 'nur Links mit embed als iframe (W-02)');
        self::assertStringContainsString('href="https://www.dailymotion.com/video/x7tgad0"', $r->body);
        self::assertStringContainsString('Anleitung &lt;b&gt;', $r->body, 'maskiert');
        // Abschnitte in der Reihenfolge 6.1
        $pos = [];
        foreach (['Bulgarian Split Squat kurz', 'Voraussetzung', 'Ausführung', 'Worauf achten', 'Fehlerquellen', 'Vorsicht', 'Progression und Regression', 'Dosierung', 'Videos', 'Links', 'Quellen', 'Fassung 1'] as $label) {
            $pos[$label] = strpos($r->body, $label);
            self::assertNotFalse($pos[$label], $label);
        }
        $sorted = $pos;
        asort($sorted);
        self::assertSame(array_keys($pos), array_keys($sorted), 'Reihenfolge der Abschnitte');
        self::assertStringContainsString('<section class="card vorsicht">', $r->body);
        self::assertStringContainsString('<ol class="ex-list"><li>Schritt eins</li>', $r->body);
        self::assertStringContainsString('href="/uebung?id=bss-weste"', $r->body, 'Variante verlinkt');
        self::assertStringContainsString('Auch: Bulgarische Kniebeuge', $r->body);
        self::assertStringContainsString('Kurzhantel', $r->body);
        self::assertStringContainsString('href="/uebungen" aria-label="Zurück zum Übungskatalog"', $r->body);
        // W-07 CSP: frame-src genau die zwei Hosts, sonst unverändert
        self::assertSame("default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; base-uri 'none'; frame-src https://www.youtube-nocookie.com https://player.vimeo.com", $r->headers['Content-Security-Policy']);
        self::assertStringNotContainsString('frame-src', $this->request('GET', '/woche')->headers['Content-Security-Policy'], 'nur S10 bettet ein');

        // Zurück zur Einheit (von) und zur geführten Einheit (modus=start); Variante behält den Rückweg
        $from = $this->request('GET', '/uebung?id=bulgarian-split-squat&von=12&modus=start');
        self::assertStringContainsString('href="/einheit?id=12&amp;modus=start" aria-label="Zurück zur Einheit"', $from->body);
        self::assertStringContainsString('href="/uebung?id=bss-weste&amp;von=12&amp;modus=start"', $from->body);
        $child = $this->request('GET', '/uebung?id=bss-weste&von=12');
        self::assertStringContainsString('href="/uebung?id=bulgarian-split-squat&amp;von=12"', $child->body, 'Grundübung verlinkt');
        self::assertStringNotContainsString('<iframe', $child->body);
        self::assertStringNotContainsString('Vorsicht', $child->body, 'leere Abschnitte entfallen');
    }

    public function testStatusNotFoundAndList(): void
    {
        $this->add('max-hang-20mm', 'Max Hang 20 mm', ['links' => [['url' => 'https://example.org/weg', 'titel' => 'Weg', 'art' => 'text']]],
            ['category' => 'hangboard', 'pattern' => 'unterarm_finger', 'equipment' => ['hangboard'], 'status' => 'links_pruefen']);
        $this->add('plank', 'Unterarmstütz', [], ['category' => 'haltung', 'pattern' => 'rumpf', 'equipment' => ['matte'], 'aliases' => ['Plank']]);
        $this->add('alt-uebung', 'Alte Übung', [], ['status' => 'archiviert']);

        $r = $this->request('GET', '/uebung?id=max-hang-20mm');
        self::assertStringContainsString('alert alert-warning', $r->body);
        self::assertStringContainsString('Links prüfen.', $r->body);
        self::assertStringContainsString('badge badge-error">defekt', $r->body);
        self::assertStringContainsString('Archiviert.', $this->request('GET', '/uebung?id=alt-uebung')->body);

        // W-03 unbekannter/ungültiger Slug → 404
        self::assertSame(404, $this->request('GET', '/uebung?id=gibt-es-nicht')->status);
        self::assertSame(404, $this->request('GET', '/uebung?id=../etc')->status);
        self::assertSame(404, $this->request('GET', '/uebung')->status);

        // S10a: Liste ohne Archiv, Suche über Alias, Filter Kategorie, Schalter Archiv
        $list = $this->request('GET', '/uebungen');
        self::assertSame(200, $list->status);
        self::assertStringContainsString('href="/uebung?id=max-hang-20mm"', $list->body);
        self::assertStringContainsString('Links prüfen', $list->body);
        self::assertStringNotContainsString('Alte Übung', $list->body);
        self::assertStringContainsString('Alte Übung', $this->request('GET', '/uebungen?archiv=1')->body);
        $search = $this->request('GET', '/uebungen?q=PLANK');
        self::assertStringContainsString('Unterarmstütz', $search->body);
        self::assertStringNotContainsString('Max Hang', $search->body);
        $filter = $this->request('GET', '/uebungen?kategorie=hangboard&q=');
        self::assertStringContainsString('Max Hang', $filter->body);
        self::assertStringNotContainsString('Unterarmstütz', $filter->body);
        self::assertStringContainsString('href="/uebungen?kategorie=hangboard" aria-current="true"', $filter->body);
        self::assertStringContainsString('Keine Übung gefunden.', $this->request('GET', '/uebungen?q=kreuzheben')->body);
        self::assertStringNotContainsString('frame-src', $list->headers['Content-Security-Policy']);

        // S8: Eintrag mit Anzahl und Hinweis auf defekte Links
        $s8 = $this->request('GET', '/einstellungen');
        self::assertStringContainsString('href="/uebungen"', $s8->body);
        self::assertStringContainsString('2 Übungen mit Ausführung', $s8->body);
        self::assertStringContainsString('1 Übung mit defekten Links', $s8->body);

        // Anmeldung nötig
        $this->cookies = [];
        self::assertSame(303, $this->request('GET', '/uebung?id=plank')->status);
        self::assertSame(303, $this->request('GET', '/uebungen')->status);
    }

    public function testSettingsLoadBeforeMigration(): void
    {
        $this->rollbackLastMigration();
        $this->pdo->exec('DROP TABLE exercise_version, exercise_alias, exercise'); // Rückweg 0023 zusätzlich (AP-16)
        $this->pdo->exec('DELETE FROM schema_version WHERE version = 23');
        $r = $this->request('GET', '/einstellungen');
        self::assertSame(200, $r->status, 'S8 lädt ohne Tabellen des Katalogs (Ort der Migration)');
        self::assertStringNotContainsString('href="/uebungen"', $r->body);
    }
}
