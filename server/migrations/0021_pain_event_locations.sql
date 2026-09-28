-- Schmerzorte für Block 1 ergänzen (AP-12, D-53): Patellasehne, Sprunggelenk, Brustwirbelsäule (Konzept 7.2).
-- Bestehende Werte bleiben; neue Werte am Ende der Aufzählung.
-- Rückweg (nur wenn keine Zeile die neuen Werte nutzt): MODIFY mit der Liste aus 0011_pain_event.sql, DELETE FROM schema_version WHERE version = 21.
ALTER TABLE pain_event MODIFY location ENUM('finger_ringband','finger_gelenk','handgelenk','ellbogen_medial','ellbogen_lateral','schulter','nacken','lws','huefte','knie','achillessehne','wade','schienbein','fuss','sonstiges','patellasehne','sprunggelenk','bws') NOT NULL;
