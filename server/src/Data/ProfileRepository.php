<?php

declare(strict_types=1);

namespace Training\Data;

use PDO;
use Training\Clock;
use Training\Db;

/**
 * Athletenprofil (D-48): feste Abschnitte mit Markdown-Text. Jede Änderung legt eine neue Fassung an; der aktuelle
 * Stand ist die jüngste Fassung je Abschnitt. Ein leerer Text leert den Abschnitt (ebenfalls als Fassung).
 */
final class ProfileRepository
{
    /** Schlüssel => [Titel, Hinweis zum Inhalt] */
    public const SECTIONS = [
        'ziele' => ['Ziele', 'Zielevents, Prioritäten, langfristige Ziele'],
        'zeitbudget' => ['Zeitbudget', 'Trainingstage, Dauer je Tag, feste Termine'],
        'ausruestung' => ['Ausrüstung', 'Hangboard, Gewichte, Uhr, Brustgurt, Ski'],
        'einschraenkungen' => ['Einschränkungen', 'Verletzungen, Beschwerden, ärztliche Vorgaben'],
        'leistungswerte' => ['Leistungswerte', 'Testergebnisse, Schwellen (LTHR), Maximalkraft, Grade'],
        'sonstiges' => ['Sonstiges', 'Alles, was für die Planung sonst wichtig ist'],
    ];
    public const MAX_LENGTH = 6000;
    public const REASON_MAX = 255;

    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    /**
     * Jüngste Fassung je Abschnitt (optional: Stand vor einem UTC-Zeitpunkt) mit Anzahl der Fassungen.
     * @return array<string, ?array<string, mixed>> Schlüssel aus SECTIONS
     */
    public function current(?string $beforeUtc = null): array
    {
        $where = $beforeUtc !== null ? ' WHERE created_at < ?' : '';
        $stmt = $this->pdo->prepare('SELECT p.*, m.versions FROM athlete_profile p JOIN (SELECT section, MAX(id) AS id, COUNT(*) AS versions FROM athlete_profile'
            . $where . ' GROUP BY section) m ON p.id = m.id');
        $stmt->execute($beforeUtc !== null ? [$beforeUtc] : []);
        $result = array_fill_keys(array_keys(self::SECTIONS), null);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['id'] = (int) $row['id'];
            $row['versions'] = (int) $row['versions'];
            $result[(string) $row['section']] = $row;
        }

        return $result;
    }

    /** Alle Fassungen eines Abschnitts, neueste zuerst. @return list<array<string, mixed>> */
    public function history(string $section, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM athlete_profile WHERE section = ? ORDER BY id DESC LIMIT ' . max(1, $limit));
        $stmt->execute([$section]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Speichert eine neue Fassung, wenn sich der Text geändert hat.
     * @param 'mcp'|'web' $by
     * @return array{id: ?int, unchanged: bool}
     * @throws \InvalidArgumentException bei unbekanntem Abschnitt oder zu langem Text
     */
    public function save(string $section, string $content, string $by, ?string $reason): array
    {
        if (!isset(self::SECTIONS[$section])) {
            throw new \InvalidArgumentException('Unbekannter Abschnitt „' . $section . '“. Erlaubt: ' . implode(', ', array_keys(self::SECTIONS)) . '.');
        }
        $content = self::normalize($content);
        if (mb_strlen($content) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException('Der Abschnitt ist zu lang (' . mb_strlen($content) . ' Zeichen, höchstens ' . self::MAX_LENGTH . ').');
        }
        $reason = $reason === null || trim($reason) === '' ? null : mb_substr(trim($reason), 0, self::REASON_MAX);
        $current = $this->current()[$section];
        if (($current === null && $content === '') || ($current !== null && $current['content'] === $content)) {
            return ['id' => $current['id'] ?? null, 'unchanged' => true];
        }
        $this->pdo->prepare('INSERT INTO athlete_profile (section, content, reason, created_by, created_at) VALUES (?, ?, ?, ?, ?)')
            ->execute([$section, $content, $reason, $by, Db::ts($this->clock->now())]);

        return ['id' => (int) $this->pdo->lastInsertId(), 'unchanged' => false];
    }

    /** Zeilenenden vereinheitlichen, Leerraum am Anfang/Ende entfernen. */
    public static function normalize(string $content): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $content));
    }
}
