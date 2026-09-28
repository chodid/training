-- Autorisierungscodes (D-36): nur gehasht, PKCE S256, 10 Minuten gültig, einmalig.
CREATE TABLE oauth_auth_code (
    code_hash      CHAR(64)     NOT NULL PRIMARY KEY,
    client_id      VARCHAR(64)  NOT NULL,
    user_id        INT UNSIGNED NOT NULL,
    code_challenge VARCHAR(128) NOT NULL,
    method         VARCHAR(10)  NOT NULL DEFAULT 'S256',
    redirect_uri   TEXT         NOT NULL,
    scope          VARCHAR(255) NOT NULL,
    expires_at     DATETIME     NOT NULL,
    used           TINYINT(1)   NOT NULL DEFAULT 0,
    KEY ix_oauth_auth_code_expires (expires_at),
    CONSTRAINT fk_oauth_auth_code_client FOREIGN KEY (client_id) REFERENCES oauth_client (client_id) ON DELETE CASCADE,
    CONSTRAINT fk_oauth_auth_code_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
