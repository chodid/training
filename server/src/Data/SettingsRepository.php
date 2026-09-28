<?php

declare(strict_types=1);

namespace Training\Data;

use PDO;
use Training\Clock;
use Training\Db;

/** Einstellungen der App (Tabelle app_setting, D-52); fehlende Schlüssel und fehlende Tabelle liefern den Standardwert. */
final class SettingsRepository
{
    /** Kalender-Erinnerung am Tag der Einheit: 'HH:MM' oder 'aus' (AP-11, D-52) */
    public const CALENDAR_REMINDER = 'calendar_reminder';
    public const CALENDAR_REMINDER_DEFAULT = '05:00';

    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    public function get(string $key, string $default): string
    {
        try {
            $stmt = $this->pdo->prepare('SELECT value FROM app_setting WHERE setting_key = ?');
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
        } catch (\PDOException) {
            return $default; // Schema älter als 19 (Update erforderlich)
        }

        return $value === false ? $default : (string) $value;
    }

    public function set(string $key, string $value): void
    {
        $this->pdo->prepare('INSERT INTO app_setting (setting_key, value, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = VALUES(updated_at)')
            ->execute([$key, $value, Db::ts($this->clock->now())]);
    }

    /** Erinnerungszeit 'HH:MM' oder null (aus). */
    public function calendarReminder(): ?string
    {
        $v = $this->get(self::CALENDAR_REMINDER, self::CALENDAR_REMINDER_DEFAULT);

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : null;
    }
}
