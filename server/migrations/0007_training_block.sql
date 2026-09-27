-- Trainingsblock (Abschnitt 7). doc_ref verweist auf den Blockplan unter docs/plaene/.
CREATE TABLE training_block (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(191) NOT NULL,
    start_date       DATE         NOT NULL,
    end_date         DATE         NOT NULL,
    goal_events_json JSON         NULL,
    phase_notes      TEXT         NULL,
    status           ENUM('geplant','aktiv','abgeschlossen') NOT NULL DEFAULT 'geplant',
    doc_ref          VARCHAR(255) NULL,
    created_at       DATETIME     NOT NULL,
    updated_at       DATETIME     NOT NULL,
    KEY ix_training_block_dates (start_date, end_date),
    CONSTRAINT ck_training_block_dates CHECK (end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
