<?php

declare(strict_types=1);

namespace Training\Migration;

/**
 * Zerlegt eine SQL-Migrationsdatei in einzelne Anweisungen.
 * Semikolons in Zeichenketten, Bezeichnern und Kommentaren trennen nicht.
 * Nicht unterstützt: DELIMITER-Wechsel (Stored Procedures, Trigger) – dafür PHP-Migrationen nutzen.
 */
final class SqlSplitter
{
    /** @return list<string> */
    public static function split(string $sql): array
    {
        $statements = [];
        $current = '';
        $hasCode = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            // Zeilenkommentare: "-- " bzw. "--" am Zeilenende und "#"
            if (($c === '-' && $next === '-' && in_array($sql[$i + 2] ?? "\n", [' ', "\t", "\n", "\r"], true)) || $c === '#') {
                $end = strpos($sql, "\n", $i);
                $i = $end === false ? $len : $end;
                $current .= "\n";
                continue;
            }
            // Blockkommentare
            if ($c === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 1;
                $current .= ' ';
                continue;
            }
            // Zeichenketten und Bezeichner
            if ($c === "'" || $c === '"' || $c === '`') {
                $j = $i + 1;
                while ($j < $len) {
                    if ($sql[$j] === '\\' && $c !== '`') {
                        $j += 2;
                        continue;
                    }
                    if ($sql[$j] === $c) {
                        if (($sql[$j + 1] ?? '') === $c) {
                            $j += 2;
                            continue;
                        }
                        break;
                    }
                    $j++;
                }
                $current .= substr($sql, $i, $j - $i + 1);
                $hasCode = true;
                $i = $j;
                continue;
            }
            if ($c === ';') {
                if ($hasCode) {
                    $statements[] = trim($current);
                }
                $current = '';
                $hasCode = false;
                continue;
            }
            $current .= $c;
            if (!ctype_space($c)) {
                $hasCode = true;
            }
        }
        if ($hasCode) {
            $statements[] = trim($current);
        }

        return $statements;
    }
}
