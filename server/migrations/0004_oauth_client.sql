-- Per Dynamic Client Registration angelegte OAuth-Clients (D-36). Öffentliche Clients ohne Secret (PKCE).
CREATE TABLE oauth_client (
    client_id          VARCHAR(64)  NOT NULL PRIMARY KEY,
    client_name        VARCHAR(255) NOT NULL,
    redirect_uris_json TEXT         NOT NULL,
    created_at         DATETIME     NOT NULL,
    last_used_at       DATETIME     NULL,
    KEY ix_oauth_client_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
