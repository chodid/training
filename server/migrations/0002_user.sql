-- Einziger Benutzer der Webseite (D-33, D-34). Anlage über /setup; Kontosperre über failed_logins/locked_until.
CREATE TABLE `user` (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login         VARCHAR(64)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    tz            VARCHAR(64)  NOT NULL DEFAULT 'Europe/Berlin',
    failed_logins INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until  DATETIME     NULL,
    created_at    DATETIME     NOT NULL,
    UNIQUE KEY uq_user_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
