-- Geplante Einheit (Abschnitt 7). plan_json nach Schema je Typ (7.1, server/schemas/), geprüft in der Anwendung.
CREATE TABLE `session` (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    week_id              INT UNSIGNED NOT NULL,
    date                 DATE         NOT NULL,
    type                 ENUM('ausdauer','kraft','klettern','haltung','mobilitaet','ruhe') NOT NULL,
    title                VARCHAR(191) NOT NULL,
    priority             ENUM('A','B','C') NOT NULL DEFAULT 'B',
    planned_duration_min SMALLINT UNSIGNED NULL,
    intervals_event_id   BIGINT UNSIGNED NULL,
    plan_json            JSON         NULL,
    coach_rationale      TEXT         NULL,
    status               ENUM('geplant','erledigt','teilweise','ausgelassen','verschoben') NOT NULL DEFAULT 'geplant',
    sort_order           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at           DATETIME     NOT NULL,
    updated_at           DATETIME     NOT NULL,
    KEY ix_session_week (week_id),
    KEY ix_session_date (date, sort_order),
    UNIQUE KEY uq_session_intervals_event (intervals_event_id),
    CONSTRAINT fk_session_week FOREIGN KEY (week_id) REFERENCES training_week (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
