-- Refresh-Tokens (D-32): nur gehasht, Rotation bei jeder Nutzung, Familien-Widerruf bei Wiederverwendung.
-- Access-Tokens sind JWT ohne DB-Eintrag; type bleibt für Erweiterbarkeit.
CREATE TABLE oauth_token (
    token_hash CHAR(64)     NOT NULL PRIMARY KEY,
    type       VARCHAR(16)  NOT NULL DEFAULT 'refresh',
    client_id  VARCHAR(64)  NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    family_id  CHAR(32)     NOT NULL,
    scope      VARCHAR(255) NOT NULL,
    created_at DATETIME     NOT NULL,
    expires_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL,
    revoked    TINYINT(1)   NOT NULL DEFAULT 0,
    KEY ix_oauth_token_family (family_id),
    KEY ix_oauth_token_client (client_id),
    CONSTRAINT fk_oauth_token_client FOREIGN KEY (client_id) REFERENCES oauth_client (client_id) ON DELETE CASCADE,
    CONSTRAINT fk_oauth_token_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
