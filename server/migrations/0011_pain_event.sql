-- Schmerzereignis (Abschnitt 7, Enum 7.2); mehrere pro Tag möglich, optional einer Einheit zugeordnet.
CREATE TABLE pain_event (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    date           DATE         NOT NULL,
    session_id     INT UNSIGNED NULL,
    location       ENUM('finger_ringband','finger_gelenk','handgelenk','ellbogen_medial','ellbogen_lateral','schulter','nacken','lws','huefte','knie','achillessehne','wade','schienbein','fuss','sonstiges') NOT NULL,
    side           ENUM('L','R','beide','na') NOT NULL DEFAULT 'na',
    intensity_0_10 TINYINT UNSIGNED NOT NULL,
    timing         ENUM('waehrend','danach','naechster_morgen','ruhe') NOT NULL,
    notes          TEXT         NULL,
    created_at     DATETIME     NOT NULL,
    KEY ix_pain_event_date (date),
    KEY ix_pain_event_location (location, date),
    CONSTRAINT fk_pain_event_session FOREIGN KEY (session_id) REFERENCES `session` (id) ON DELETE SET NULL,
    CONSTRAINT ck_pain_event_intensity CHECK (intensity_0_10 <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
