-- Stand des Datenbankschemas (D-20). Eine Zeile pro ausgeführter Migration.
CREATE TABLE schema_version (
    version    INT UNSIGNED NOT NULL PRIMARY KEY,
    name       VARCHAR(191) NOT NULL,
    applied_at DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
