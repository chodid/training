-- Audit-Log aller Schreibzugriffe über MCP und Web (Abschnitt 12.4). payload_hash = SHA-256 der geschriebenen Daten.
CREATE TABLE audit_log (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ts           DATETIME     NOT NULL,
    actor        ENUM('mcp','web','cron') NOT NULL,
    action       VARCHAR(64)  NOT NULL,
    entity       VARCHAR(64)  NOT NULL,
    entity_id    VARCHAR(64)  NULL,
    payload_hash CHAR(64)     NULL,
    summary      VARCHAR(500) NULL,
    KEY ix_audit_log_ts (ts),
    KEY ix_audit_log_entity (entity, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
