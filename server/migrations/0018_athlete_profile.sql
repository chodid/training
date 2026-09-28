-- Athletenprofil als DB-Objekt (D-48, ersetzt D-15): feste Abschnitte mit Markdown-Text, jede Änderung als neue Fassung.
-- Aktueller Stand = höchste id je Abschnitt; ältere Fassungen bleiben lesbar (Nachvollziehbarkeit früherer Pläne).
CREATE TABLE athlete_profile (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    section     ENUM('ziele','zeitbudget','ausruestung','einschraenkungen','leistungswerte','sonstiges') NOT NULL,
    content     MEDIUMTEXT   NOT NULL,
    reason      VARCHAR(255) NULL,
    created_by  ENUM('mcp','web') NOT NULL,
    created_at  DATETIME     NOT NULL,
    KEY ix_athlete_profile_section (section, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
