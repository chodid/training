-- Blockbilanz, Zielklärung und Revision (AP-15, docs/konzept/blockbilanz.md 4.1, E-10/E-11/E-12): je Trainingsblock
-- Datensätze mit Fassungen. Jede Änderung ist eine neue Zeile (version + 1, reason ab Fassung 2); gültig ist je
-- (block_id, kind, sequence) die bestätigte Zeile mit der höchsten version. Bilanz und Zielklärung haben immer
-- sequence 1, Revisionen 1, 2, … content_json nach server/schemas/review-<kind>.json; kennzahlen_auto berechnet der
-- Server (Bilanz, Revision). Ein Block mit Reviews ist nicht löschbar (ON DELETE RESTRICT). Keine Datenübernahme.
-- Rückweg (manuell, nur wenn nötig):
--   DROP TABLE block_review; DELETE FROM schema_version WHERE version = 24;
CREATE TABLE block_review (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    block_id        INT UNSIGNED NOT NULL,
    kind            ENUM('revision','bilanz','zielklaerung') NOT NULL,
    sequence        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    version         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status          ENUM('entwurf','bestaetigt') NOT NULL,
    review_date     DATE         NOT NULL,
    period_start    DATE         NULL,
    period_end      DATE         NULL,
    summary         VARCHAR(255) NOT NULL,
    content_json    JSON         NOT NULL,
    kennzahlen_auto JSON         NULL,
    reason          VARCHAR(255) NULL,
    created_by      ENUM('mcp','web') NOT NULL,
    created_at      DATETIME     NOT NULL,
    confirmed_at    DATETIME     NULL,
    UNIQUE KEY ux_block_review_version (block_id, kind, sequence, version),
    KEY ix_block_review_kind (kind, status, review_date),
    CONSTRAINT fk_block_review_block FOREIGN KEY (block_id) REFERENCES training_block (id) ON DELETE RESTRICT,
    CONSTRAINT ck_block_review_bilanz CHECK (kind <> 'bilanz' OR sequence = 1),
    CONSTRAINT ck_block_review_zielklaerung CHECK (kind <> 'zielklaerung' OR sequence = 1),
    CONSTRAINT ck_block_review_period CHECK (period_end IS NULL OR period_start IS NULL OR period_end >= period_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
