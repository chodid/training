-- Übungskatalog (AP-16, docs/konzept/uebungskatalog.md 4.1, E-07/E-08/E-20): Übungen mit suchbaren Spalten und
-- schemageprüftem Inhalt (server/schemas/exercise.json), Aliase und Fassungen. plan_json verweist über exercise_id
-- (Slug) auf eine Übung; die Schemaänderung an plan_json braucht keine Datenänderung (bestehende Pläne bleiben gültig).
-- name_norm/alias_norm: normalisierte Schreibweise (Kleinschreibung, Umlaute ae/oe/ue/ss, Satzzeichen = Leerzeichen),
-- eindeutig je Tabelle; die Prüfung Name gegen Alias einer anderen Übung macht die Anwendung (E-09).
-- Keine Löschfunktion: status 'archiviert'.
-- Rückweg (manuell, nur wenn nötig):
--   DROP TABLE exercise_version; DROP TABLE exercise_alias; DROP TABLE exercise;
--   DELETE FROM schema_version WHERE version = 23;
CREATE TABLE exercise (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug           VARCHAR(60)  NOT NULL,
    name           VARCHAR(120) NOT NULL,
    name_norm      VARCHAR(240) NOT NULL,
    category       ENUM('kraft','haltung','mobilitaet','hangboard','campus','zugkraft','antagonisten') NOT NULL,
    pattern        ENUM('druecken_horizontal','druecken_vertikal','ziehen_horizontal','ziehen_vertikal','knie_dominant',
                        'huefte_dominant','rumpf','schulter','bws_haltung','unterarm_finger','sprunggelenk_fuss',
                        'mobilitaet','sonstiges') NOT NULL,
    equipment_json JSON         NOT NULL,
    variant_of     INT UNSIGNED NULL,
    difficulty     TINYINT UNSIGNED NULL,
    status         ENUM('aktiv','links_pruefen','archiviert') NOT NULL DEFAULT 'aktiv',
    konfidenz      ENUM('hoch','mittel','niedrig','einschaetzung') NOT NULL,
    content_json   JSON         NOT NULL,
    version        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_by     ENUM('mcp','web') NOT NULL,
    created_at     DATETIME     NOT NULL,
    updated_at     DATETIME     NOT NULL,
    UNIQUE KEY ux_exercise_slug (slug),
    UNIQUE KEY ux_exercise_name_norm (name_norm),
    KEY ix_exercise_category (category, status),
    KEY ix_exercise_pattern (pattern),
    CONSTRAINT fk_exercise_variant FOREIGN KEY (variant_of) REFERENCES exercise (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exercise_alias (
    exercise_id INT UNSIGNED NOT NULL,
    alias       VARCHAR(120) NOT NULL,
    alias_norm  VARCHAR(240) NOT NULL,
    PRIMARY KEY (exercise_id, alias_norm),
    UNIQUE KEY ux_exercise_alias_norm (alias_norm),
    CONSTRAINT fk_exercise_alias_exercise FOREIGN KEY (exercise_id) REFERENCES exercise (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exercise_version (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    exercise_id   INT UNSIGNED NOT NULL,
    version       SMALLINT UNSIGNED NOT NULL,
    snapshot_json JSON         NOT NULL,
    reason        VARCHAR(255) NOT NULL,
    created_by    ENUM('mcp','web') NOT NULL,
    created_at    DATETIME     NOT NULL,
    UNIQUE KEY ux_exercise_version (exercise_id, version),
    CONSTRAINT fk_exercise_version_exercise FOREIGN KEY (exercise_id) REFERENCES exercise (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
