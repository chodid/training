<?php

declare(strict_types=1);

namespace Training\Tests\Integration;

use Training\Tests\Support\AppTestCase;
use Training\Tests\Support\FakeLinkFetcher;

/** AP-16 T2: MCP-Tools des Übungskatalogs über /mcp (Testfälle T-01 bis T-10, Budget, Schreibsperre). */
final class ExerciseToolsTest extends AppTestCase
{
    private const STATIC = 'statisches-token-statisches-token-0123';
    private const NOW = 1790164800; // Mi 2026-09-23 12:00 UTC

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->now = self::NOW;
        $this->writeEnv(['MCP_STATIC_TOKEN' => self::STATIC, 'MCP_STATIC_TOKEN_ENABLED' => 'true']);
        $this->linkFetcher = new FakeLinkFetcher();
        $this->setupUser();
    }

    /** @param array<string, mixed> $args @return array{isError: bool, data: mixed, text: string} */
    private function tool(string $name, array $args = []): array
    {
        return $this->mcpTool(self::STATIC, $name, $args);
    }

    /** @param array<string, mixed> $over @return array<string, mixed> */
    private static function exercise(string $slug, string $name, array $over = []): array
    {
        return $over + [
            'slug' => $slug, 'name' => $name, 'category' => 'kraft', 'pattern' => 'knie_dominant', 'equipment' => ['koerpergewicht'], 'konfidenz' => 'mittel',
            'content' => ['kurz' => $name . ' – kurz.', 'ziel' => 'Beinkraft', 'ausfuehrung' => ['Aufstellen.', 'Absenken und strecken.'], 'quellen' => ['L-T2-04']],
        ];
    }

    /** @return list<array<string, string>> 2 Text- und 2 Videolinks */
    private static function fourLinks(): array
    {
        return [
            ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'titel' => 'Video YouTube', 'art' => 'video'],
            ['url' => 'https://vimeo.com/76979871', 'titel' => 'Video Vimeo', 'art' => 'video'],
            ['url' => 'https://example.org/split-squat', 'titel' => 'Anleitung', 'art' => 'text'],
            ['url' => 'https://example.org/fehler', 'titel' => 'Fehlerquellen', 'art' => 'text'],
        ];
    }

    public function testFindCreateAndLinkCheck(): void
    {
        // T-01 leerer Katalog
        $r = $this->tool('find_exercise', ['query' => 'split squat']);
        self::assertFalse($r['isError'], $r['text']);
        self::assertSame([], $r['data']['treffer']);
        self::assertStringContainsString('upsert_exercise', $r['data']['hinweis']);

        // T-02 neu mit 2 Text- und 2 Videolinks, alle erreichbar
        $in = self::exercise('bulgarian-split-squat', 'Bulgarian Split Squat', ['equipment' => ['kurzhantel'], 'aliases' => ['Bulgarische Kniebeuge']]);
        $in['content']['links'] = self::fourLinks();
        $in['content']['links'][0]['status'] = 'ok'; // Serverfelder in der Eingabe werden ignoriert
        $r = $this->tool('upsert_exercise', $in);
        self::assertFalse($r['isError'], $r['text']);
        self::assertSame(['aktiv', 1, true], [$r['data']['status'], $r['data']['version'], $r['data']['neu']]);
        self::assertSame(['ok', 'ok', 'ok', 'ok'], array_column($r['data']['links'], 'status'));
        self::assertSame('Neu im Katalog: „Bulgarian Split Squat“ (kraft, knie_dominant, kurzhantel), 4 Links geprüft, Quelle L-T2-04.', $r['data']['hinweis_chat']);
        $fetched = $this->linkFetcher->calls[0];
        self::assertCount(4, $fetched, 'ein paralleler Durchgang');
        self::assertContains('https://www.youtube.com/oembed?format=json&url=' . rawurlencode('https://www.youtube.com/watch?v=dQw4w9WgXcQ'), $fetched, 'Video über oEmbed (E-18)');
        self::assertContains('https://vimeo.com/api/oembed.json?url=' . rawurlencode('https://vimeo.com/76979871'), $fetched);
        self::assertContains('https://example.org/split-squat', $fetched);

        $e = $this->tool('get_exercise', ['slug' => 'bulgarian-split-squat'])['data'];
        self::assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $e['content']['links'][0]['embed']);
        self::assertSame('https://player.vimeo.com/video/76979871', $e['content']['links'][1]['embed']);
        self::assertNull($e['content']['links'][2]['embed']);
        self::assertSame('2026-09-23', $e['content']['links'][0]['geprueft_am'], 'geprueft_am heute');
        self::assertSame(['Bulgarische Kniebeuge'], $e['aliases']);
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM audit_log WHERE action = 'exercise_write'")->fetchColumn());

        // T-03 ein Link antwortet 404 → angelegt, Link defekt, Übung links_pruefen, kein Toolfehler
        $this->linkFetcher->answers = ['example.org/weg' => 404, 'example.org/langsam' => 'nicht erreichbar: Timeout'];
        $in = self::exercise('ausfallschritt', 'Ausfallschritt');
        $in['content']['links'] = [['url' => 'https://example.org/weg', 'titel' => 'Weg', 'art' => 'text'], ['url' => 'https://example.org/langsam', 'titel' => 'Langsam', 'art' => 'text']];
        $r = $this->tool('upsert_exercise', $in);
        self::assertFalse($r['isError'], $r['text']);
        self::assertSame('links_pruefen', $r['data']['status']);
        self::assertSame(['defekt', 'ungeprueft'], array_column($r['data']['links'], 'status'));
        self::assertStringContainsString('davon 1 defekt', $r['data']['hinweis_chat']);

        // T-08 Suche: Treffer und ähnlich
        $r = $this->tool('find_exercise', ['query' => 'split']);
        self::assertSame(['bulgarian-split-squat', 'ausfallschritt'], array_column($r['data']['treffer'], 'slug'));
        self::assertSame([false, true], array_map(static fn (array $t): bool => $t['aehnlich'] ?? false, $r['data']['treffer']));
        self::assertArrayNotHasKey('status', $r['data']['treffer'][0], 'aktiv entfällt');
        self::assertSame('links_pruefen', $r['data']['treffer'][1]['status'], 'Status in find_exercise sichtbar');
    }

    public function testDuplicatesSchemaVersionsAndArchive(): void
    {
        self::assertFalse($this->tool('upsert_exercise', self::exercise('squat', 'Squat', ['aliases' => ['Kniebeuge']]))['isError']);

        // T-04 Name existiert als Alias
        $r = $this->tool('upsert_exercise', self::exercise('kniebeuge', 'Kniebeuge'));
        self::assertTrue($r['isError']);
        self::assertStringContainsString('„Squat“ (squat, Treffer „Kniebeuge“)', $r['data']['fehler']);
        // Alias existiert als Name (normalisiert)
        $r = $this->tool('upsert_exercise', self::exercise('tiefe-kniebeuge', 'Tiefe Kniebeuge', ['aliases' => ['SQUAT']]));
        self::assertTrue($r['isError']);

        // T-05 drei Videolinks → Schemafehler, nichts angelegt, nichts abgerufen
        $calls = count($this->linkFetcher->calls);
        $in = self::exercise('goblet-squat', 'Goblet Squat');
        $in['content']['links'] = [...array_slice(self::fourLinks(), 0, 2), ['url' => 'https://youtu.be/aaaaaaaaaaa', 'titel' => 'V3', 'art' => 'video']];
        $r = $this->tool('upsert_exercise', $in);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('/links', $r['text']);
        self::assertSame($calls, count($this->linkFetcher->calls));
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM exercise')->fetchColumn());

        // Pflichtfelder beim Anlegen
        $r = $this->tool('upsert_exercise', ['slug' => 'nur-slug']);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('content fehlt', $r['text']);
        self::assertStringContainsString('konfidenz', $r['text']);

        // T-06 ändern ohne reason
        $r = $this->tool('upsert_exercise', ['slug' => 'squat', 'name' => 'Kniebeuge tief', 'aliases' => ['Kniebeuge']]);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('reason fehlt', $r['data']['fehler']);

        // T-07 ändern mit reason → Fassung v1 als Schnappschuss, version 2, slug unverändert
        $r = $this->tool('upsert_exercise', ['slug' => 'squat', 'name' => 'Kniebeuge', 'aliases' => ['Squat'], 'difficulty' => 2, 'reason' => 'Deutscher Name']);
        self::assertFalse($r['isError'], $r['text']);
        self::assertSame(2, $r['data']['version']);
        self::assertSame('Katalog geändert: „Kniebeuge“, Fassung 2 (Deutscher Name), ohne Links, Quelle L-T2-04.', $r['data']['hinweis_chat']);
        $e = $this->tool('get_exercise', ['slug' => 'squat', 'fassungen' => true])['data'];
        self::assertSame(['Kniebeuge', 'squat', 2, 2], [$e['name'], $e['slug'], $e['version'], $e['difficulty']]);
        self::assertSame([['version' => 1, 'reason' => 'Deutscher Name']], array_map(static fn (array $v): array => array_diff_key($v, ['created_at' => 1]), $e['fassungen']));
        $v1 = $this->tool('get_exercise', ['slug' => 'squat', 'version' => 1])['data'];
        self::assertSame('Squat', $v1['fassung']['stand']['name']);
        self::assertSame(['Kniebeuge'], $v1['fassung']['stand']['aliases']);
        // gleicher Inhalt → keine neue Fassung
        $same = $this->tool('upsert_exercise', ['slug' => 'squat', 'name' => 'Kniebeuge', 'reason' => 'nochmal']);
        self::assertTrue($same['data']['unveraendert']);
        self::assertSame(2, $same['data']['version']);

        // Variante und Schleifenschutz
        self::assertFalse($this->tool('upsert_exercise', self::exercise('pistol-squat', 'Pistol Squat', ['variant_of' => 'squat']))['isError']);
        $loop = $this->tool('upsert_exercise', ['slug' => 'squat', 'variant_of' => 'pistol-squat', 'reason' => 'x']);
        self::assertTrue($loop['isError']);
        self::assertStringContainsString('Schleife', $loop['text']);
        self::assertSame([['slug' => 'pistol-squat', 'name' => 'Pistol Squat']], $this->tool('get_exercise', ['slug' => 'squat'])['data']['varianten']);

        // T-09 archivieren bei geplanter Verwendung → abgelehnt mit Einheit
        $this->block();
        $plan = $this->tool('write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Kraft', 'sessions' => [$this->kraft('2026-09-24', [['exercise_id' => 'squat', 'name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8']])]]);
        self::assertFalse($plan['isError'], $plan['text']);
        $sessionId = $plan['data']['einheiten'][0]['id'];
        $r = $this->tool('upsert_exercise', ['slug' => 'squat', 'status' => 'archiviert', 'reason' => 'ersetzt']);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('Einheit ' . $sessionId . ' (2026-09-24', $r['text']);
        // nach Erledigung archivierbar; danach nicht mehr planbar (V-08) und nur mit include_archived auffindbar
        $this->pdo->exec("UPDATE `session` SET status = 'erledigt'");
        $r = $this->tool('upsert_exercise', ['slug' => 'squat', 'status' => 'archiviert', 'reason' => 'ersetzt']);
        self::assertFalse($r['isError'], $r['text']);
        self::assertSame('archiviert', $r['data']['status']);
        self::assertSame(['pistol-squat'], array_column($this->tool('find_exercise', ['query' => 'squat'])['data']['treffer'], 'slug'));
        self::assertContains('squat', array_column($this->tool('find_exercise', ['query' => 'squat', 'include_archived' => true])['data']['treffer'], 'slug'));
        $u = $this->tool('update_session', ['session_id' => $sessionId, 'changes' => ['plan_json' => ['exercises' => [['exercise_id' => 'squat', 'name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8']]]]]);
        self::assertTrue($u['isError']);
        self::assertStringContainsString('archiviert', $u['text']);
    }

    public function testWeekPlanWarningsAndErrors(): void
    {
        $this->tool('upsert_exercise', self::exercise('kniebeuge', 'Kniebeuge'));
        $this->tool('upsert_exercise', self::exercise('max-hang-20mm', 'Max Hang 20 mm', ['category' => 'hangboard', 'pattern' => 'unterarm_finger', 'equipment' => ['hangboard']]));
        $this->block();

        // unbekanntes exercise_id → Fehler, nichts geschrieben
        $r = $this->tool('write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Kraft', 'sessions' => [
            $this->kraft('2026-09-22', [['exercise_id' => 'kniebeugen', 'name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8']]),
        ]]);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('sessions[0]: plan_json Position 0: exercise_id „kniebeugen“ gibt es im Katalog nicht', $r['text']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM `session`')->fetchColumn());
        // Kletterblock Bouldern mit ID → Schemafehler (V-07)
        $r = $this->tool('write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Klettern', 'sessions' => [
            ['date' => '2026-09-23', 'type' => 'klettern', 'title' => 'Halle', 'coach_summary' => 'x', 'plan_json' => ['blocks' => [['kind' => 'bouldern_volumen', 'exercise_id' => 'max-hang-20mm']]]],
        ]]);
        self::assertTrue($r['isError']);
        self::assertStringContainsString('/blocks/0/exercise_id', $r['text']);

        // T-10 gemischt (V-01 und V-02) → geschrieben, eine Warnung; Hangboard mit ID ohne Warnung
        $r = $this->tool('write_week_plan', ['week_start' => '2026-09-21', 'focus' => 'Kraft', 'sessions' => [
            $this->kraft('2026-09-22', [['exercise_id' => 'kniebeuge', 'name' => 'Kniebeuge', 'sets' => 3, 'reps' => '8'], ['name' => 'Wadenheben', 'sets' => 3, 'reps' => '15']]),
            ['date' => '2026-09-23', 'type' => 'klettern', 'title' => 'Hangboard', 'coach_summary' => 'Maxhang', 'plan_json' => ['blocks' => [['kind' => 'hangboard', 'exercise_id' => 'max-hang-20mm', 'edge_mm' => 20, 'hang_s' => 10, 'sets' => 5], ['kind' => 'bouldern_volumen', 'duration_min' => 30]]]],
        ]]);
        self::assertFalse($r['isError'], $r['text']);
        self::assertSame('ok', $r['data']['status']);
        self::assertSame([['session' => 0, 'datum' => '2026-09-22', 'position' => 1, 'name' => 'Wadenheben', 'code' => 'ohne_katalog']], $r['data']['warnungen']);
        self::assertStringContainsString('find_exercise', $r['data']['hinweis_warnungen']);
        $kraftId = $r['data']['einheiten'][0]['id'];

        // update_session: Namensabweichung → Warnung mit Hinweis; ohne plan_json keine Prüfung
        $u = $this->tool('update_session', ['session_id' => $kraftId, 'changes' => ['plan_json' => ['exercises' => [['exercise_id' => 'kniebeuge', 'name' => 'Squat', 'sets' => 3, 'reps' => '8']]]]]);
        self::assertFalse($u['isError'], $u['text']);
        self::assertSame('name_abweichend', $u['data']['warnungen'][0]['code']);
        self::assertStringContainsString('„Kniebeuge“', $u['data']['warnungen'][0]['hinweis']);
        self::assertArrayNotHasKey('warnungen', $this->tool('update_session', ['session_id' => $kraftId, 'changes' => ['title' => 'Kraft A']])['data']);

        // Lese-Tools: exercise_ids in der Wochenübersicht, plan_json im Detail
        $o = $this->tool('get_week_overview', ['week_start' => '2026-09-21'])['data'];
        $rows = array_column($o['einheiten'], null, 'id');
        self::assertSame(['kniebeuge'], $rows[$kraftId]['exercise_ids']);
        self::assertSame('kniebeuge', $this->tool('get_session_detail', ['session_id' => $kraftId])['data']['plan_json']['exercises'][0]['exercise_id']);
    }

    public function testBudgetListAndWriteLock(): void
    {
        $patterns = ['knie_dominant', 'huefte_dominant', 'rumpf', 'ziehen_vertikal'];
        for ($i = 1; $i <= 40; $i++) {
            $in = self::exercise('uebung-nummer-' . $i, 'Übung Nummer ' . $i . ' mit etwas längerem Namen', ['pattern' => $patterns[$i % 4], 'equipment' => ['kurzhantel', 'band']]);
            $in['content']['kurz'] = str_repeat('Kurzbeschreibung ', 11);
            // realistischer, reichhaltiger Eintrag (die Schemagrenzen 4.3 erlauben mehr, als das Budget E-16 fasst)
            $in['content']['ziel'] = str_repeat('Ziel ', 40);
            $in['content']['muskeln'] = ['Quadrizeps', 'Gluteus maximus', 'Adduktoren', 'Rumpf'];
            $in['content']['voraussetzung'] = str_repeat('Bank ', 30);
            $in['content']['ausfuehrung'] = array_fill(0, 8, str_repeat('Schritt ', 19));
            $in['content']['achten'] = array_fill(0, 5, str_repeat('Achten ', 14));
            $in['content']['fehler'] = array_fill(0, 5, str_repeat('Fehler ', 14));
            $in['content']['vorsicht'] = array_fill(0, 2, str_repeat('Vorsicht ', 20));
            $in['content']['progression'] = str_repeat('Schwerer ', 15);
            $in['content']['regression'] = str_repeat('Leichter ', 15);
            $in['content']['dosierung_hinweis'] = str_repeat('Dosis ', 20);
            $in['content']['links'] = self::fourLinks();
            self::assertFalse($this->tool('upsert_exercise', $in)['isError']);
        }
        $find = $this->tool('find_exercise', ['query' => 'übung nummer']);
        self::assertCount(10, $find['data']['treffer']);
        self::assertLessThan(4000, mb_strlen($find['text']), 'find_exercise ≤ 1 000 Tokens (E-16), Kurztexte fast 200 Zeichen');
        $get = $this->tool('get_exercise', ['slug' => 'uebung-nummer-7', 'fassungen' => true]);
        self::assertLessThan(6000, mb_strlen($get['text']), 'get_exercise ≤ 1 500 Tokens');
        $list = $this->tool('list_exercises');
        self::assertSame(40, $list['data']['anzahl']);
        self::assertLessThan(8000, strlen($list['text']), 'list_exercises ≤ 2 000 Tokens');
        self::assertSame(['uebung-nummer-1', 'Übung Nummer 1 mit etwas längerem Namen', 'kraft', 'huefte_dominant'], $list['data']['uebungen'][0]);
        self::assertSame(10, $this->tool('list_exercises', ['category' => 'kraft', 'status' => 'aktiv'])['data']['anzahl'] - 30);

        // Schreibsperre (D-20)
        $this->rollbackLastMigration();
        $this->mcpSessions = [];
        $r = $this->tool('upsert_exercise', self::exercise('neu-gesperrt', 'Gesperrt'));
        self::assertTrue($r['isError']);
        self::assertStringContainsString('Update erforderlich', $r['text']);
    }

    private function block(): void
    {
        $b = $this->tool('upsert_block', ['block' => ['name' => 'Herbst', 'start_date' => '2026-09-21', 'end_date' => '2026-11-15', 'status' => 'aktiv']]);
        self::assertFalse($b['isError'], $b['text']);
    }

    /** @param list<array<string, mixed>> $exercises @return array<string, mixed> */
    private function kraft(string $date, array $exercises): array
    {
        return ['date' => $date, 'type' => 'kraft', 'title' => 'Kraft', 'coach_summary' => 'Kraft', 'plan_json' => ['exercises' => $exercises]];
    }
}
