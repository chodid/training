-- Einstellungen der App als Schlüssel/Wert (D-52), z. B. calendar_reminder = 'HH:MM' oder 'aus'.
-- Fehlender Schlüssel = Standardwert im Code.
CREATE TABLE app_setting (
    setting_key VARCHAR(64)  NOT NULL PRIMARY KEY,
    value       VARCHAR(255) NOT NULL,
    updated_at  DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
