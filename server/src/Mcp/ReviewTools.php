<?php

declare(strict_types=1);

namespace Training\Mcp;

use PDO;
use Training\Clock;
use Training\Data\AuditLog;
use Training\Data\ProfileRepository;
use Training\Data\ReviewRepository;
use Training\Data\SettingsRepository;
use Training\Data\WeekRepository;
use Training\Dates;
use Training\Review\Faelligkeit;
use Training\Review\Kennzahlen;
use Training\Review\ReviewValidator;

/**
 * Übergabe, Blockbilanz, Zielklärung und Revision über MCP (AP-15, docs/konzept/blockbilanz.md 5.2, E-08/E-09/E-17).
 * get_handover stellt deterministisch zusammen (keine KI, kein Cron); Interpretationen stehen nur in den bestätigten
 * Datensätzen. Budget: ohne detail ≤ 8 000 Zeichen (≈ 2 000 Tokens), Zeilen je Eintrag gekürzt.
 */
final class ReviewTools
{
    /** Kürzungsstufen der Übergabe: [Zeichen je Zeile, Einträge je Liste]; die erste, die ins Budget passt, gilt */
    private const LEVELS = [[220, 12], [160, 10], [120, 8], [90, 6], [70, 4]];
    /** Budget der Übergabe ohne detail in Zeichen (E-17: ≈ 2 000 Tokens) */
    public const BUDGET = 8000;
    /** content_json je Datensatz in get_block_reviews höchstens so viele Zeichen (≈ 1 500 Tokens) */
    private const CONTENT_MAX = 6000;
    /** Revision ohne Zeitraum: so viele Tage bis zum Gesprächsdatum */
    private const REVISION_DAYS = 28;

    private int $lineMax = 220;
    private int $listMax = 12;

    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
        private readonly string $tz,
        private readonly ?\Training\Calendar\CalendarSync $calendar = null,
    ) {
    }

    /**
     * Übergabe; ohne detail gekürzt, bis sie ins Budget passt (Zeilenlänge und Listenlänge stufenweise kleiner, dann
     * Feld gekuerzt). Die Volltexte (detail) werden nicht gekürzt.
     * @return array<string, mixed>
     */
    public function handover(bool $detail): array
    {
        foreach (self::LEVELS as $i => [$this->lineMax, $this->listMax]) {
            $result = $this->buildHandover($detail);
            $size = mb_strlen((string) json_encode($detail ? array_diff_key($result, ['volltexte' => 1]) : $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ($size <= self::BUDGET - 200) {
                return $i > 0 ? $result + ['gekuerzt' => 'Listen und Zeilen gekürzt (Budget); vollständig mit get_block_reviews'] : $result;
            }
        }

        return $result + ['gekuerzt' => 'Listen und Zeilen gekürzt (Budget); vollständig mit get_block_reviews'];
    }

    /** @return array<string, mixed> */
    private function buildHandover(bool $detail): array
    {
        $today = Dates::today($this->clock, $this->tz);
        $reviews = new ReviewRepository($this->pdo, $this->clock);
        $blocks = $this->blocks();
        $active = null;
        foreach ($blocks as $b) {
            if ($b['status'] === 'aktiv') {
                $active = $b;
            }
        }
        $faellig = Faelligkeit::load($this->pdo, $this->clock, $today);

        $zk = $active !== null ? $reviews->latestConfirmed('zielklaerung', (int) $active['id']) : null;
        $zkFallback = false;
        if ($zk === null) {
            $zk = $reviews->latestConfirmed('zielklaerung');
            $zkFallback = $zk !== null;
        }
        $bilanzen = array_slice($this->sortedConfirmed($reviews, 'bilanz'), 0, 2);
        $revisionen = $active !== null ? $reviews->current((int) $active['id'], 'revision', true) : [];

        $result = [
            'heute' => $today,
            'block' => $active !== null ? $this->blockShort($active, $today) : null,
            'zielklaerung' => $zk !== null ? $this->zkShort($zk, $blocks) + ($zkFallback ? ['hinweis' => 'Zielklärung eines anderen Blocks (der aktive Block hat keine).'] : []) : null,
            'bilanzen' => array_map(fn (array $b): array => $this->bilanzShort($b, $blocks), $bilanzen),
            'revisionen' => array_map(fn (array $r): array => $this->revisionShort($r), $revisionen),
            'kennzahlen' => $active !== null ? (new Kennzahlen($this->pdo, $this->clock, $this->tz))->kurz($active) : null,
            'wochen_kurz' => $this->weeksShort($today),
            'faellig' => self::faelligOut($faellig),
            'offene_fragen' => $this->lines(array_values(array_unique([...($zk['content']['offene_fragen'] ?? []), ...($bilanzen[0]['content']['offene_fragen'] ?? [])]))),
            'profil_stand' => $this->profileState(),
        ];
        $drafts = $this->drafts($reviews);
        if ($drafts !== []) {
            $result['entwuerfe'] = $drafts;
        }
        if ($detail) {
            $result['volltexte'] = [
                'zielklaerung' => $zk !== null ? ['block_id' => $zk['block_id'], 'version' => $zk['version'], 'content_json' => $zk['content']] : null,
                'bilanz' => $bilanzen !== [] ? ['block_id' => $bilanzen[0]['block_id'], 'version' => $bilanzen[0]['version'], 'content_json' => $bilanzen[0]['content'], 'kennzahlen_auto' => $bilanzen[0]['kennzahlen']] : null,
            ];
        } else {
            $result['hinweis'] = 'Volltexte mit detail=true; alle Datensätze eines Blocks mit get_block_reviews.';
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function blockReviews(?int $blockId, ?string $kind, bool $fassungen): array
    {
        if ($kind !== null && !in_array($kind, ReviewValidator::KINDS, true)) {
            throw new ToolError('kind muss einer von ' . implode(', ', ReviewValidator::KINDS) . ' sein.');
        }
        $block = $blockId !== null ? (new WeekRepository($this->pdo, $this->clock))->block($blockId) : $this->activeBlock();
        if ($block === null) {
            throw new ToolError($blockId !== null ? 'Block ' . $blockId . ' nicht gefunden.' : 'Kein aktiver Block; block_id angeben.');
        }
        $repo = new ReviewRepository($this->pdo, $this->clock);
        $rows = $fassungen ? $repo->versions((int) $block['id'], $kind) : $repo->current((int) $block['id'], $kind);
        $out = [];
        foreach ($rows as $r) {
            $row = ['id' => $r['id'], 'kind' => $r['kind'], 'sequence' => $r['sequence'], 'version' => $r['version'], 'status' => $r['status'],
                'review_date' => $r['review_date'], 'summary' => $r['summary']];
            if ($r['period_start'] !== null) {
                $row['zeitraum'] = ['von' => $r['period_start'], 'bis' => $r['period_end']];
            }
            if ($fassungen) {
                $row['reason'] = $r['reason'];
                $row['erstellt'] = $r['created_at'];
            } else {
                if (isset($r['entwurf'])) {
                    $row['entwurf'] = $r['entwurf'];
                }
                $json = json_encode($r['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($json !== false && mb_strlen($json) <= self::CONTENT_MAX) {
                    $row['content_json'] = $r['content'];
                } else {
                    $row['hinweis'] = 'content_json zu lang für die Liste; mit get_handover(detail: true) bzw. kind einschränken.';
                }
                if ($r['kennzahlen'] !== null) {
                    $row['kennzahlen_auto'] = $r['kennzahlen'];
                }
            }
            $out[] = $row;
        }

        return [
            'block' => ['id' => (int) $block['id'], 'name' => $block['name'], 'start' => $block['start_date'], 'ende' => $block['end_date'], 'status' => $block['status']],
            'reviews' => $out,
        ] + ($out === [] ? ['hinweis' => 'Keine Datensätze' . ($kind !== null ? ' dieser Art' : '') . ' für diesen Block.'] : []);
    }

    /**
     * @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    public function write(int $blockId, string $kind, ?int $sequence, string $reviewDate, ?string $periodStart, ?string $periodEnd,
        string $summary, array $content, string $status, ?string $reason): array
    {
        $errors = [];
        if (!in_array($kind, ReviewValidator::KINDS, true)) {
            throw new ToolError('kind muss einer von ' . implode(', ', ReviewValidator::KINDS) . ' sein.');
        }
        if (!in_array($status, ReviewValidator::STATUSES, true)) {
            $errors[] = 'status muss entwurf oder bestaetigt sein';
        }
        if (!Dates::isDate($reviewDate)) {
            $errors[] = 'review_date muss ein Datum (YYYY-MM-DD) sein';
        }
        foreach (['period_start' => $periodStart, 'period_end' => $periodEnd] as $k => $v) {
            if ($v !== null && !Dates::isDate($v)) {
                $errors[] = $k . ' muss ein Datum (YYYY-MM-DD) sein';
            }
        }
        if ($kind === 'zielklaerung' && ($periodStart !== null || $periodEnd !== null)) {
            $errors[] = 'period_start/period_end nur bei bilanz und revision';
        }
        if (($periodStart === null) !== ($periodEnd === null)) {
            $errors[] = 'period_start und period_end nur gemeinsam angeben';
        } elseif ($periodStart !== null && $periodEnd !== null && $periodEnd < $periodStart) {
            $errors[] = 'period_end liegt vor period_start';
        }
        $summary = trim($summary);
        if ($summary === '' || mb_strlen($summary) > ReviewValidator::SUMMARY_MAX) {
            $errors[] = 'summary: Kurzsatz mit 1–' . ReviewValidator::SUMMARY_MAX . ' Zeichen';
        }
        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
        if ($reason !== null && mb_strlen($reason) > ReviewValidator::REASON_MAX) {
            $errors[] = 'reason: höchstens ' . ReviewValidator::REASON_MAX . ' Zeichen';
        }
        array_push($errors, ...ReviewValidator::default()->validate($kind, $content));
        if ($errors !== []) {
            throw new ToolError('Datensatz ungültig, nichts geschrieben.', $errors);
        }

        $block = (new WeekRepository($this->pdo, $this->clock))->block($blockId);
        if ($block === null) {
            throw new ToolError('Block ' . $blockId . ' nicht gefunden.');
        }
        if ($kind === 'zielklaerung' && !in_array($block['status'], ['geplant', 'aktiv'], true)) {
            throw new ToolError('Zielklärung nur für Blöcke mit Status geplant oder aktiv; Block ' . $blockId . ' ist ' . $block['status'] . '. Für den nächsten Block zuerst upsert_block (Status geplant).');
        }
        if ($kind === 'revision' && $block['status'] === 'abgeschlossen') {
            throw new ToolError('Block ' . $blockId . ' ist abgeschlossen; Revisionen nur für geplante oder aktive Blöcke.');
        }

        $repo = new ReviewRepository($this->pdo, $this->clock);
        if ($kind !== 'revision') {
            $sequence = 1;
        } elseif ($sequence === null) {
            $sequence = $repo->nextSequence($blockId);
        } elseif ($sequence < 1 || $sequence > $repo->nextSequence($blockId)) {
            throw new ToolError('sequence ' . $sequence . ' gibt es nicht; ohne sequence wird die nächste Revision (' . $repo->nextSequence($blockId) . ') angelegt.');
        }
        $version = $repo->nextVersion($blockId, $kind, $sequence);
        if ($version > 1 && $reason === null) {
            throw new ToolError('reason ab Fassung 2 Pflicht: kurz angeben, was sich gegenüber Fassung ' . ($version - 1) . ' ändert (z. B. „vom Athleten bestätigt“, „Test nachgetragen“).');
        }

        // Zeitraum und Kennzahlen (E-12): Bilanz ohne Zeitraum = Blockzeitraum; Revision ohne Zeitraum = 28 Tage bis zum Gesprächsdatum
        $kennzahlen = null;
        if ($kind !== 'zielklaerung') {
            if ($periodStart === null) {
                [$periodStart, $periodEnd] = $kind === 'bilanz'
                    ? [(string) $block['start_date'], (string) $block['end_date']]
                    : [max((string) $block['start_date'], Dates::addDays($reviewDate, -(self::REVISION_DAYS - 1))), $reviewDate];
                if ($periodEnd < $periodStart) {
                    $periodStart = $periodEnd;
                }
            }
            $kennzahlen = (new Kennzahlen($this->pdo, $this->clock, $this->tz))->compute((string) $periodStart, (string) $periodEnd);
        }

        $audit = new AuditLog($this->pdo, $this->clock);
        $this->pdo->beginTransaction();
        try {
            $id = $repo->insert(['block_id' => $blockId, 'kind' => $kind, 'sequence' => $sequence, 'version' => $version, 'status' => $status,
                'review_date' => $reviewDate, 'period_start' => $periodStart, 'period_end' => $periodEnd, 'summary' => $summary,
                'content' => $content, 'kennzahlen' => $kennzahlen, 'reason' => $reason, 'created_by' => 'mcp']);
            $audit->write('mcp', 'review_write', 'block_review', $id, $content, $kind . ' Block ' . $blockId . ' v' . $version . ' ' . $status
                . ($kind === 'revision' ? ' (Revision ' . $sequence . ')' : ''));
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        if ($status === 'bestaetigt') {
            // Quittierungen des Overlays dieser Art sind erledigt (E-14, U-04)
            (new SettingsRepository($this->pdo, $this->clock))->deletePrefix(SettingsRepository::ERINNERUNG_PREFIX . $kind . '_');
        }

        $faellig = Faelligkeit::load($this->pdo, $this->clock, Dates::today($this->clock, $this->tz));
        $result = ['id' => $id, 'block_id' => $blockId, 'kind' => $kind, 'sequence' => $sequence, 'version' => $version, 'status' => $status];
        if ($kennzahlen !== null) {
            $result['kennzahlen_auto'] = self::kennzahlenShort($kennzahlen);
        }
        $result['faellig'] = self::faelligOut($faellig);
        if ($status === 'entwurf') {
            $result['hinweis'] = 'Entwurf gespeichert; er zählt erst nach Bestätigung durch den Athleten (erneut mit status bestaetigt und reason schreiben).';
        }
        $calendarError = $this->calendar?->pushBlocks($this->calendarBlocks($blockId), 'mcp');
        if ($calendarError !== null) {
            $result['fehler_kalender'] = [$calendarError . ' Der stündliche Abgleich überträgt den Termin erneut.'];
        }

        return $result;
    }

    /**
     * Fälligkeiten in der Form der Tool-Antworten (mit Satz).
     * @param list<array<string, mixed>> $faellig
     * @return list<array<string, mixed>>
     */
    public static function faelligOut(array $faellig): array
    {
        return array_map(static fn (array $f): array => $f + ['text' => Faelligkeit::text($f)], $faellig);
    }

    /** Blöcke, deren Kalendertermin sich durch einen Datensatz von $blockId ändern kann (der Block und sein Vorgänger). @return list<int> */
    private function calendarBlocks(int $blockId): array
    {
        $ids = [$blockId];
        $block = (new WeekRepository($this->pdo, $this->clock))->block($blockId);
        if ($block !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM training_block WHERE start_date < ? AND id <> ? ORDER BY start_date DESC, id DESC LIMIT 1');
            $stmt->execute([$block['start_date'], $blockId]);
            $prev = $stmt->fetchColumn();
            if ($prev !== false) {
                $ids[] = (int) $prev;
            }
        }

        return $ids;
    }

    /** @param array<string, mixed> $k @return array<string, mixed> */
    private static function kennzahlenShort(array $k): array
    {
        $plan = [];
        foreach ($k['plan_erfuellung'] as $type => $p) {
            if ($p['geplant'] > 0) {
                $plan[$type] = ($p['erledigt'] + $p['teilweise']) . '/' . $p['geplant'];
            }
        }

        return array_filter([
            'zeitraum' => $k['zeitraum'],
            'wochen' => $k['wochen'],
            'erledigt_von_geplant' => $plan,
            'srpe_summe' => $k['last']['srpe_summe'],
            'schmerz_orte' => count($k['schmerz']['je_ort']),
            'morgentest' => $k['morgentest'],
            'checkin_abdeckung_prozent' => $k['checkin_abdeckung_prozent'],
        ], static fn ($v): bool => $v !== []) + ['hinweis' => 'Vollständig mit get_block_reviews.'];
    }

    /** @param array<string, mixed> $b @return array<string, mixed> */
    private function blockShort(array $b, string $today): array
    {
        $weeks = intdiv((int) ((strtotime((string) $b['end_date']) - strtotime((string) $b['start_date'])) / 86400), 7) + 1;
        $week = $today >= $b['start_date'] && $today <= $b['end_date'] ? intdiv((int) ((strtotime($today) - strtotime((string) $b['start_date'])) / 86400), 7) + 1 : null;
        $events = $b['goal_events_json'] !== null ? json_decode((string) $b['goal_events_json'], true) : [];

        return array_filter([
            'id' => (int) $b['id'],
            'name' => $b['name'],
            'start' => $b['start_date'],
            'ende' => $b['end_date'],
            'status' => $b['status'],
            'woche' => $week !== null ? $week . '/' . $weeks : null,
            'phasen' => $b['phase_notes'] !== null ? self::cut(trim((string) $b['phase_notes']), 400) : null,
            'zielevents' => is_array($events) && $events !== [] ? array_slice($events, 0, 5) : null,
        ], static fn ($v): bool => $v !== null);
    }

    /** @param array<string, mixed> $r @param list<array<string, mixed>> $blocks @return array<string, mixed> */
    private function zkShort(array $r, array $blocks): array
    {
        $c = $r['content'];

        return [
            'block_id' => $r['block_id'],
            'block' => $this->blockName($blocks, $r['block_id']),
            'version' => $r['version'],
            'review_date' => $r['review_date'],
            'phase' => $c['phase'] . ': ' . self::cut($c['phase_text'], $this->lineMax),
            'prioritaeten' => $c['prioritaeten'],
            'ziele' => $this->lines(array_map(static fn (array $z): string => $z['id'] . ' [' . $z['bereich'] . '] ' . $z['ziel'] . ' – ' . $z['messgroesse'] . '; erreicht wenn ' . $z['kriterium']
                . (($z['termin'] ?? null) !== null ? ' (bis ' . $z['termin'] . ')' : ''), $c['ziele'])),
            'entscheidungen' => $this->lines(array_map(static fn (array $e): string => $e['thema'] . ': ' . $e['entscheidung'], $c['entscheidungen'])),
            'risiken' => $this->lines(array_map(static fn (array $x): string => $x['risiko'] . ' → ' . $x['regel'], $c['risiken'])),
        ];
    }

    /** @param array<string, mixed> $r @param list<array<string, mixed>> $blocks @return array<string, mixed> */
    private function bilanzShort(array $r, array $blocks): array
    {
        $c = $r['content'];

        return array_filter([
            'block_id' => $r['block_id'],
            'block' => $this->blockName($blocks, $r['block_id']),
            'version' => $r['version'],
            'zeitraum' => $c['zeitraum']['von'] . ' – ' . $c['zeitraum']['bis'],
            'ziele' => $this->lines(array_map(static fn (array $z): string => ($z['ziel_id'] ?? '–') . ' ' . $z['bewertung'] . ': ' . $z['grund'], $c['ziele'])),
            'empfehlung' => self::cut($c['empfehlung'], 600),
            'annahmen_geaendert' => $this->lines(array_map(static fn (array $a): string => $a['was'] . ' → ' . $a['neu'] . ($a['vorschlag_trainerregel'] ? ' (Vorschlag Trainerregel)' : ''), $c['annahmen_geaendert'] ?? [])),
        ], static fn ($v): bool => $v !== []);
    }

    /** @param array<string, mixed> $r @return array<string, mixed> */
    private function revisionShort(array $r): array
    {
        $c = $r['content'];

        return [
            'nr' => $r['sequence'],
            'datum' => $r['review_date'],
            'anlass' => $c['anlass'],
            'aenderungen' => $this->lines(array_map(static fn (array $a): string => $a['was'] . ' – ' . $a['warum'] . (($a['bis'] ?? null) !== null ? ' (bis ' . $a['bis'] . ')' : ''), $c['aenderungen'])),
        ];
    }

    /** Wochentexte der letzten 4 Wochen bis einschließlich der laufenden (E-21). @return list<string> */
    private function weeksShort(string $today): array
    {
        $stmt = $this->pdo->prepare('SELECT week_start, focus FROM training_week WHERE week_start <= ? ORDER BY week_start DESC LIMIT 4');
        $stmt->execute([Dates::monday($today)]);

        return array_map(fn (array $w): string => $w['week_start'] . ': ' . self::cut(trim((string) ($w['focus'] ?? '')) !== '' ? (string) $w['focus'] : '–', $this->lineMax),
            array_reverse($stmt->fetchAll()));
    }

    /** Stand je Profilabschnitt (Datum in Ortszeit, kein Inhalt). @return array<string, ?string> */
    private function profileState(): array
    {
        $out = [];
        foreach ((new ProfileRepository($this->pdo, $this->clock))->current() as $section => $row) {
            $out[$section] = $row !== null && $row['content'] !== ''
                ? (new \DateTimeImmutable((string) $row['created_at'], new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($this->tz))->format('Y-m-d')
                : null;
        }

        return $out;
    }

    /** Entwürfe, die noch keine bestätigte Fassung abgelöst haben (Hinweis für die nächste Sitzung). @return list<string> */
    private function drafts(ReviewRepository $repo): array
    {
        $out = [];
        foreach ($repo->current() as $r) {
            if ($r['status'] === 'entwurf') {
                $out[] = $r['kind'] . ' Block ' . $r['block_id'] . ' v' . $r['version'] . ' (' . $r['review_date'] . '): ' . $r['summary'];
            } elseif (isset($r['entwurf'])) {
                $out[] = $r['kind'] . ' Block ' . $r['block_id'] . ' v' . $r['entwurf']['version'] . ' (' . $r['entwurf']['review_date'] . '): ' . $r['entwurf']['summary'];
            }
        }

        return $this->lines(array_slice($out, -5));
    }

    /** @return list<array<string, mixed>> */
    private function sortedConfirmed(ReviewRepository $repo, string $kind): array
    {
        $rows = $repo->current(null, $kind, true);
        usort($rows, static fn (array $a, array $b): int => [$b['review_date'], $b['id']] <=> [$a['review_date'], $a['id']]);

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function blocks(): array
    {
        return $this->pdo->query('SELECT * FROM training_block ORDER BY start_date, id')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    private function activeBlock(): ?array
    {
        $row = $this->pdo->query("SELECT * FROM training_block WHERE status = 'aktiv' ORDER BY start_date DESC LIMIT 1")->fetch();

        return $row === false ? null : $row;
    }

    /** @param list<array<string, mixed>> $blocks */
    private function blockName(array $blocks, int $id): ?string
    {
        foreach ($blocks as $b) {
            if ((int) $b['id'] === $id) {
                return (string) $b['name'];
            }
        }

        return null;
    }

    /** @param list<string> $lines @return list<string> */
    private function lines(array $lines): array
    {
        return array_map(fn (string $l): string => self::cut($l, $this->lineMax), array_slice($lines, 0, $this->listMax));
    }

    private static function cut(string $s, int $max): string
    {
        return mb_strlen($s) > $max ? rtrim(mb_substr($s, 0, $max - 1)) . '…' : $s;
    }
}
