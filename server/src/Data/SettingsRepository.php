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
    /** Blockbilanz fällig ab Blockende minus so viele Tage (AP-15, E-13; 0–28) */
    public const BILANZ_VORLAUF = 'bilanz_vorlauf_tage';
    public const BILANZ_VORLAUF_DEFAULT = 7;
    /** Zielklärung fällig ab Blockende minus so viele Tage (AP-15, E-13; 0–42) */
    public const ZIELKLAERUNG_VORLAUF = 'zielklaerung_vorlauf_tage';
    public const ZIELKLAERUNG_VORLAUF_DEFAULT = 14;
    /** Overlay-Erinnerung an Bilanz und Zielklärung: 'an' oder 'aus' (AP-15, E-05) */
    public const REVIEW_OVERLAY = 'review_overlay';
    /** Quittierung des Overlays: Präfix + <kind>_<block_id> = Datum, bis zu dem Ruhe ist (AP-15, E-14) */
    public const ERINNERUNG_PREFIX = 'erinnerung_';
    /** Blocktermin im Kalender (AP-15, E-22): Beginn 'HH:MM', Dauer in Minuten (30–480), Erinnerung in Stunden vor Beginn (0–168) */
    public const KALENDER_BLOCK_BEGINN = 'kalender_block_beginn';
    public const KALENDER_BLOCK_BEGINN_DEFAULT = '08:00';
    public const KALENDER_BLOCK_DAUER = 'kalender_block_dauer_min';
    public const KALENDER_BLOCK_DAUER_DEFAULT = 120;
    public const KALENDER_BLOCK_ERINNERUNG = 'kalender_block_erinnerung_h';
    public const KALENDER_BLOCK_ERINNERUNG_DEFAULT = 24;

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

    public function delete(string $key): void
    {
        try {
            $this->pdo->prepare('DELETE FROM app_setting WHERE setting_key = ?')->execute([$key]);
        } catch (\PDOException) {
            // Schema älter als 19: nichts zu tun
        }
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

    public function bilanzVorlauf(): int
    {
        return $this->int(self::BILANZ_VORLAUF, self::BILANZ_VORLAUF_DEFAULT, 0, 28);
    }

    public function zielklaerungVorlauf(): int
    {
        return $this->int(self::ZIELKLAERUNG_VORLAUF, self::ZIELKLAERUNG_VORLAUF_DEFAULT, 0, 42);
    }

    /** Overlay-Erinnerung an fällige Bilanz/Zielklärung (E-05): true = an. */
    public function reviewOverlay(): bool
    {
        return $this->get(self::REVIEW_OVERLAY, 'an') !== 'aus';
    }

    /** Datum (Y-m-d), bis zu dem die Erinnerung ruht, oder null (E-14). Schlüssel erinnerung_<kind>_<block_id>, 0 = ohne Block. */
    public function erinnerungBis(string $kind, ?int $blockId): ?string
    {
        $v = $this->get(self::erinnerungKey($kind, $blockId), '');

        return \Training\Dates::isDate($v) ? $v : null;
    }

    public static function erinnerungKey(string $kind, ?int $blockId): string
    {
        return self::ERINNERUNG_PREFIX . $kind . '_' . ($blockId ?? 0);
    }

    /** @return array{beginn: string, dauer_min: int, erinnerung_h: int} Blocktermin im Kalender (E-22) */
    public function kalenderBlock(): array
    {
        $beginn = $this->get(self::KALENDER_BLOCK_BEGINN, self::KALENDER_BLOCK_BEGINN_DEFAULT);

        return [
            'beginn' => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $beginn) ? $beginn : self::KALENDER_BLOCK_BEGINN_DEFAULT,
            'dauer_min' => $this->int(self::KALENDER_BLOCK_DAUER, self::KALENDER_BLOCK_DAUER_DEFAULT, 30, 480),
            'erinnerung_h' => $this->int(self::KALENDER_BLOCK_ERINNERUNG, self::KALENDER_BLOCK_ERINNERUNG_DEFAULT, 0, 168),
        ];
    }

    /** Ganze Zahl im Bereich, sonst Standardwert. */
    private function int(string $key, int $default, int $min, int $max): int
    {
        $v = $this->get($key, (string) $default);

        return preg_match('/^\d{1,4}$/', $v) && (int) $v >= $min && (int) $v <= $max ? (int) $v : $default;
    }
}
