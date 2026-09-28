-- Spiegel der Intervals.icu-Aktivitäten (D-43): Zusammenfassung je Aktivität, keine Streams (N7).
-- data_json enthält die übernommenen Felder der API-Antwort; Abgleich per Cronjob und beim Lesen (read-through).
CREATE TABLE ext_activity (
    id               VARCHAR(64)  NOT NULL PRIMARY KEY,
    date             DATE         NOT NULL,
    start_date_local DATETIME     NULL,
    type             VARCHAR(40)  NULL,
    paired_event_id  BIGINT UNSIGNED NULL,
    data_json        JSON         NOT NULL,
    updated_at       DATETIME     NOT NULL,
    KEY ix_ext_activity_date (date),
    KEY ix_ext_activity_event (paired_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
