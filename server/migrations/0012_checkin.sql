-- Tägliches Check-in (D-16): genau drei Felder, ein Eintrag pro Tag.
CREATE TABLE checkin (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    date         DATE         NOT NULL,
    recovery_1_5 TINYINT UNSIGNED NOT NULL,
    soreness_1_5 TINYINT UNSIGNED NOT NULL,
    pain_flag    TINYINT(1)   NOT NULL DEFAULT 0,
    notes        TEXT         NULL,
    created_at   DATETIME     NOT NULL,
    updated_at   DATETIME     NOT NULL,
    UNIQUE KEY uq_checkin_date (date),
    CONSTRAINT ck_checkin_recovery CHECK (recovery_1_5 BETWEEN 1 AND 5),
    CONSTRAINT ck_checkin_soreness CHECK (soreness_1_5 BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
