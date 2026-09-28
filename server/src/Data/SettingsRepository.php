<?php

declare(strict_types=1);

namespace Training\Data;

use PDO;
use Training\Clock;
use Training\Db;

/** Einstellungen der App (Tabelle app_setting, D-52); fehlende Schlüssel und fehlende Tabelle liefern den Standardwert. */
final class SettingsRepository
{
    /** Kalender-Erinnerung am Trainingstag: 'HH:MM' oder 'aus' (AP-11, D-52) */
    public const CALENDAR_REMINDER = 'calendar_reminder';
    public const CALENDAR_REMINDER_DEFAULT = '05:00';
    /** Check-in: „Hand rechts“ abfragen bis einschließlich (Y-m-d; AP-12, E-08) */
    public const CHECKIN_HAND_BIS = 'checkin_hand_rechts_bis';
    public const CHECKIN_HAND_BIS_DEFAULT = '2026-11-23';
    /** Timer-Signale (Ton und Vibration) im geführten Modus: 'an' oder 'aus' (AP-14, E-18) */
    public const TIMER_TON = 'timer_ton';
    public const TIMER_TON_DEFAULT = 'an';

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

    /**
     * Einträge mit Datum im Schlüssel (Präfix + Y-m-d) vor $date entfernen, z. B. die Fassungen der Kalender-Tagestermine
     * (kalender_tag_<Datum>, D-60).
     */
    public function forgetBefore(string $prefix, string $date): void
    {
        try {
            $this->pdo->prepare('DELETE FROM app_setting WHERE setting_key LIKE ? AND setting_key < ?')
                ->execute([addcslashes($prefix, '%_\\') . '%', $prefix . $date]);
        } catch (\PDOException) {
            // Schema älter als 19: nichts zu tun
        }
    }

    /** Erinnerungszeit 'HH:MM' oder null (aus). */
    public function calendarReminder(): ?string
    {
        $v = $this->get(self::CALENDAR_REMINDER, self::CALENDAR_REMINDER_DEFAULT);

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : null;
    }

    /** Timer-Signale im geführten Modus als Standard: true = an (E-18). */
    public function timerTon(): bool
    {
        return $this->get(self::TIMER_TON, self::TIMER_TON_DEFAULT) !== 'aus';
    }

    /** Letzter Tag, an dem „Hand rechts“ im Check-in abgefragt wird. */
    public function handRechtsBis(): string
    {
        $v = $this->get(self::CHECKIN_HAND_BIS, self::CHECKIN_HAND_BIS_DEFAULT);

        return \Training\Dates::isDate($v) ? $v : self::CHECKIN_HAND_BIS_DEFAULT;
    }
}
