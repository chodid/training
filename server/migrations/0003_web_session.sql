-- 30-Tage-Session der Webseite (D-33). Token nur als SHA-256; csrf_secret je Session.
CREATE TABLE web_session (
    token_hash   CHAR(64)     NOT NULL PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    csrf_secret  CHAR(64)     NOT NULL,
    created_at   DATETIME     NOT NULL,
    last_seen_at DATETIME     NOT NULL,
    expires_at   DATETIME     NOT NULL,
    KEY ix_web_session_user (user_id),
    KEY ix_web_session_expires (expires_at),
    CONSTRAINT fk_web_session_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
