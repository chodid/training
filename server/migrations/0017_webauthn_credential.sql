-- Passkeys (WebAuthn) zusätzlich zum Passwort (D-44). Öffentlicher Schlüssel als PEM, Signaturzähler gegen geklonte Schlüssel.
CREATE TABLE webauthn_credential (
    id            VARCHAR(255) NOT NULL PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    name          VARCHAR(100) NOT NULL,
    public_key    TEXT         NOT NULL,
    sign_count    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL,
    last_used_at  DATETIME     NULL,
    KEY ix_webauthn_credential_user (user_id),
    CONSTRAINT fk_webauthn_credential_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
