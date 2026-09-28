-- Spiegel der Intervals.icu-Wellness (D-43): ein Eintrag je Tag (HRV, Ruhepuls, Schlaf, Fitness/Ermüdung …).
CREATE TABLE ext_wellness (
    date       DATE     NOT NULL PRIMARY KEY,
    data_json  JSON     NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
