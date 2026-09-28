-- Optionaler Kurzcache für Intervals.icu-Antworten (Abschnitt 9), z. B. 5 Minuten.
CREATE TABLE ext_cache (
    cache_key    VARCHAR(191) NOT NULL PRIMARY KEY,
    payload_json LONGTEXT     NOT NULL,
    fetched_at   DATETIME     NOT NULL,
    KEY ix_ext_cache_fetched (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
