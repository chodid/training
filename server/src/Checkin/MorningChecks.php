<?php

declare(strict_types=1);

namespace Training\Checkin;

use PDO;
use Training\Clock;
use Training\Data\WeekRepository;
use Training\Dates;
use Training\View\Labels;

/**
 * Morgen-Check-in je Tag mit allen Ableitungen (AP-12, Abschnitt 5 von docs/konzept/morgen-checkin.md) für
 * Webseite und MCP (get_morning_checks, get_week_overview). Kalendertage in der Zeitzone des Athleten (E-06).
 */
final class MorningChecks
{
    public const SKALEN = [
        'schmerz' => 'NRS 0–10 (0 kein Schmerz, 10 stärkster vorstellbarer), null = nicht erhoben',
        'recovery_1_5' => '1 sehr gut … 5 sehr schlecht',
        'soreness_1_5' => '1 kein … 5 stark',
    ];

    /** @var array<string, array<string, mixed>> Datum → Check-in-Zeile */
    private array $rows = [];
    /** @var array<string, list<string>> Datum → Typen erledigter/teilweiser Einheiten */
    private array $doneTypes = [];

    public function __construct(private readonly PDO $pdo, private readonly Clock $clock, private readonly string $tz)
    {
    }

    /**
     * Einträge im Zeitraum (inklusive), neueste zuerst; nur Tage mit Check-in, außer $withEmpty.
     * @return list<array<string, mixed>>
     */
    public function range(string $from, string $to, bool $withEmpty = false): array
    {
        $this->load($from, $to);
        $out = [];
        for ($d = $to; $d >= $from; $d = Dates::addDays($d, -1)) {
            if ($withEmpty || isset($this->rows[$d])) {
                $out[] = $this->entry($d);
            }
        }

        return $out;
    }

    /** @return array<string, mixed> Zusammenfassung für Tag $date (Format 6.1) */
    public function summary(string $date): array
    {
        $this->load(Dates::addDays($date, -6), $date);
        $e = $this->entry($date);
        $green = 0;
        $covered = 0;
        for ($i = 0; $i < 7; $i++) {
            $x = $this->entry(Dates::addDays($date, -$i));
            $green += $x['ampel'] === 'gruen' ? 1 : 0;
            $covered += $x['steuerwert'] !== null ? 1 : 0;
        }

        return [
            'ampel' => $e['ampel'],
            'ampel_grund' => $e['ampel_grund'],
            'morgentest' => $e['morgentest'] + ['steuerwert' => $e['steuerwert']],
            'wochenausgangswert' => $e['wochenausgangswert'],
            'ueber_wochenausgangswert' => $e['ueber_wochenausgangswert'],
            'vortag_einheiten' => $e['vortag_einheiten'],
            'tage_gruen_letzte_7' => $green,
            'abdeckung_letzte_7_pct' => (int) round($covered / 7 * 100),
            'abklaerung_empfohlen' => $e['abklaerung_empfohlen'],
            'erfasst' => $e['erfasst_um'] !== null,
        ];
    }

    /** @return array<string, mixed> */
    public function entry(string $date): array
    {
        $r = $this->rows[$date] ?? null;
        $links = self::int($r['mt_links'] ?? null);
        $rechts = self::int($r['mt_rechts'] ?? null);
        $v = MorningStatus::steuerwert($links, $rechts);
        $ampel = MorningStatus::ampel($v, $this->steuerwertAm(Dates::addDays($date, -1)), $this->steuerwertAm(Dates::addDays($date, -2)));
        $monday = Dates::monday($date);
        $week = [];
        for ($d = $monday; $d <= $date; $d = Dates::addDays($d, 1)) {
            $week[$d] = $this->steuerwertAm($d);
        }
        $basis = MorningStatus::wochenausgangswert($week, $monday, $date);
        $warn = self::warnings($r['warnzeichen'] ?? null);
        $umgeknickt = (bool) ($r['osg_umgeknickt'] ?? false);
        $schwellung = $umgeknickt && (bool) ($r['osg_schwellung'] ?? false);

        return [
            'datum' => $date,
            'erfasst_um' => $r !== null ? $this->local((string) $r['updated_at']) : null,
            'morgentest' => ['links' => $links, 'rechts' => $rechts],
            'steuerwert' => $v,
            'ampel' => $ampel['ampel'],
            'ampel_grund' => $ampel['grund'],
            'wochenausgangswert' => $basis,
            'ueber_wochenausgangswert' => MorningStatus::ueberAusgangswert($v, $basis),
            'vortag_einheiten' => $this->doneTypes[Dates::addDays($date, -1)] ?? [],
            'nacken_bws' => self::int($r['nacken_bws'] ?? null),
            'sprunggelenk_links' => ['umgeknickt' => $umgeknickt, 'schwellung' => $schwellung],
            'hand_rechts' => self::int($r['hand_rechts'] ?? null),
            'warnzeichen' => $warn,
            'abklaerung_empfohlen' => MorningStatus::abklaerungEmpfohlen($warn, $umgeknickt, $schwellung),
            'recovery_1_5' => self::int($r['recovery_1_5'] ?? null),
            'soreness_1_5' => self::int($r['soreness_1_5'] ?? null),
            'notiz' => $r !== null ? (string) ($r['notes'] ?? '') : null,
        ];
    }

    /** Lädt Check-ins und erledigte Einheiten; zusätzlich 2 Vortage (Steigung) und den Wochenbeginn (Ausgangswert). */
    private function load(string $from, string $to): void
    {
        $start = min(Dates::addDays($from, -2), Dates::monday($from));
        $stmt = $this->pdo->prepare('SELECT * FROM checkin WHERE date BETWEEN ? AND ?');
        $stmt->execute([$start, $to]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->rows[(string) $row['date']] = $row;
        }
        foreach ((new WeekRepository($this->pdo, $this->clock))->sessions(Dates::addDays($from, -1), $to) as $s) {
            if (in_array($s['status'], ['erledigt', 'teilweise'], true) && !in_array($s['type'], $this->doneTypes[$s['date']] ?? [], true)) {
                $this->doneTypes[(string) $s['date']][] = (string) $s['type'];
            }
        }
    }

    private function steuerwertAm(string $date): ?int
    {
        $r = $this->rows[$date] ?? null;

        return $r === null ? null : MorningStatus::steuerwert(self::int($r['mt_links']), self::int($r['mt_rechts']));
    }

    /** @return list<string> */
    public static function warnings(mixed $json): array
    {
        $list = is_string($json) ? json_decode($json, true) : $json;

        return is_array($list) ? array_values(array_filter($list, static fn ($w): bool => is_string($w) && isset(Labels::WARNINGS[$w]))) : [];
    }

    private static function int(mixed $v): ?int
    {
        return $v === null || $v === '' ? null : (int) $v;
    }

    private function local(string $utc): string
    {
        return (new \DateTimeImmutable($utc, new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($this->tz))->format('Y-m-d H:i');
    }
}
