<?php

declare(strict_types=1);

namespace Training\Checkin;

/**
 * Ableitungen des Morgen-Check-ins (AP-12, docs/konzept/morgen-checkin.md Abschnitt 5) als reine Funktionen:
 * feste Regeln aus dem Trainingsplan (E-04), keine KI-Logik, keine Datenbank. Schmerz NRS 0–10, null = nicht erhoben.
 */
final class MorningStatus
{
    /** Steuerwert eines Tages (E-03): Maximum der vorhandenen Werte links/rechts; fehlt einer, zählt der andere. */
    public static function steuerwert(?int $links, ?int $rechts): ?int
    {
        if ($links === null && $rechts === null) {
            return null;
        }

        return max($links ?? 0, $rechts ?? 0);
    }

    /**
     * Ampel für Tag d aus den Steuerwerten v(d), v(d−1), v(d−2).
     * @return array{ampel: string, grund: string}
     */
    public static function ampel(?int $v, ?int $v1, ?int $v2): array
    {
        if ($v === null) {
            return ['ampel' => 'keine_daten', 'grund' => 'Kein Morgentest erfasst'];
        }
        if ($v > 5) {
            return ['ampel' => 'rot', 'grund' => 'Morgentest ' . $v . '/10'];
        }
        if ($v1 !== null && $v2 !== null && $v2 < $v1 && $v1 < $v && $v >= 4) {
            return ['ampel' => 'rot', 'grund' => 'zwei Tage steigend (' . $v2 . '→' . $v1 . '→' . $v . ')'];
        }
        if ($v >= 4) {
            return ['ampel' => 'gelb', 'grund' => 'Morgentest ' . $v . '/10'];
        }

        return ['ampel' => 'gruen', 'grund' => 'Morgentest ' . $v . '/10'];
    }

    /**
     * Wochenausgangswert: erster nicht-leerer Steuerwert der Kalenderwoche (Mo–So) bis einschließlich Tag d.
     * @param array<string, ?int> $steuerwerte Datum (Y-m-d) → Steuerwert, beliebige Reihenfolge
     */
    public static function wochenausgangswert(array $steuerwerte, string $monday, string $date): ?int
    {
        ksort($steuerwerte);
        foreach ($steuerwerte as $d => $v) {
            if ($d >= $monday && $d <= $date && $v !== null) {
                return $v;
            }
        }

        return null;
    }

    /** 24-Stunden-Regel: Steuerwert über dem Wochenausgangswert. */
    public static function ueberAusgangswert(?int $v, ?int $ausgangswert): bool
    {
        return $v !== null && $ausgangswert !== null && $v > $ausgangswert;
    }

    /**
     * Abklärung empfohlen (E-07): Warnzeichen gesetzt oder links umgeknickt mit Schwellung. Keine Diagnose.
     * @param list<string> $warnzeichen
     */
    public static function abklaerungEmpfohlen(array $warnzeichen, bool $umgeknickt, bool $schwellung): bool
    {
        return $warnzeichen !== [] || ($umgeknickt && $schwellung);
    }
}
