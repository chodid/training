-- Durchführung einer Einheit (Abschnitt 7, 11): genau eine je Session; srpe_load wird berechnet, nie eingegeben.
CREATE TABLE session_execution (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    session_id       INT UNSIGNED NOT NULL,
    performed_at     DATETIME     NULL,
    duration_min     SMALLINT UNSIGNED NULL,
    actual_json      JSON         NULL,
    rpe_cr10         TINYINT UNSIGNED NULL,
    srpe_load        INT UNSIGNED AS (rpe_cr10 * duration_min) STORED,
    feel_1_5         TINYINT UNSIGNED NULL,
    deviation_reason ENUM('zeit','ermuedung','schmerz','wetter','sonstiges') NULL,
    notes            TEXT         NULL,
    source           ENUM('web','intervals') NOT NULL DEFAULT 'web',
    created_at       DATETIME     NOT NULL,
    updated_at       DATETIME     NOT NULL,
    UNIQUE KEY uq_session_execution_session (session_id),
    CONSTRAINT fk_session_execution_session FOREIGN KEY (session_id) REFERENCES `session` (id) ON DELETE CASCADE,
    CONSTRAINT ck_session_execution_rpe CHECK (rpe_cr10 IS NULL OR rpe_cr10 <= 10),
    CONSTRAINT ck_session_execution_feel CHECK (feel_1_5 IS NULL OR feel_1_5 BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
