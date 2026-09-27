-- Trainingswoche (Abschnitt 7). week_start ist ein Montag (Prüfung in der Anwendung), je Woche nur ein Eintrag.
CREATE TABLE training_week (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    block_id    INT UNSIGNED NOT NULL,
    week_start  DATE         NOT NULL,
    focus       VARCHAR(255) NULL,
    coach_notes TEXT         NULL,
    status      ENUM('entwurf','bestaetigt','abgeschlossen') NOT NULL DEFAULT 'entwurf',
    created_by  ENUM('mcp','web') NOT NULL,
    created_at  DATETIME     NOT NULL,
    updated_at  DATETIME     NOT NULL,
    UNIQUE KEY uq_training_week_start (week_start),
    KEY ix_training_week_block (block_id),
    CONSTRAINT fk_training_week_block FOREIGN KEY (block_id) REFERENCES training_block (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
