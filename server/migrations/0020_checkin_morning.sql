-- Morgen-Check-in mit Morgentest (AP-12, D-53, docs/konzept/morgen-checkin.md T2): Erweiterung des bestehenden
-- Check-ins (ein Eintrag je Tag, letzte Fassung gilt). Schmerzwerte NRS 0–10, NULL = nicht erhoben (E-02), nie 0.
-- Bestehende Zeilen bleiben unverändert (alle neuen Werte NULL bzw. 0/false).
-- Rückweg (manuell, nur wenn nötig):
--   ALTER TABLE checkin DROP CONSTRAINT ck_checkin_mt_links, DROP CONSTRAINT ck_checkin_mt_rechts, DROP CONSTRAINT ck_checkin_nacken_bws,
--     DROP CONSTRAINT ck_checkin_hand_rechts, DROP COLUMN mt_links, DROP COLUMN mt_rechts, DROP COLUMN nacken_bws,
--     DROP COLUMN osg_umgeknickt, DROP COLUMN osg_schwellung, DROP COLUMN hand_rechts, DROP COLUMN warnzeichen;
--   DELETE FROM schema_version WHERE version = 20;
ALTER TABLE checkin
    ADD COLUMN mt_links       TINYINT UNSIGNED NULL AFTER notes,
    ADD COLUMN mt_rechts      TINYINT UNSIGNED NULL AFTER mt_links,
    ADD COLUMN nacken_bws     TINYINT UNSIGNED NULL AFTER mt_rechts,
    ADD COLUMN osg_umgeknickt TINYINT(1)       NOT NULL DEFAULT 0 AFTER nacken_bws,
    ADD COLUMN osg_schwellung TINYINT(1)       NOT NULL DEFAULT 0 AFTER osg_umgeknickt,
    ADD COLUMN hand_rechts    TINYINT UNSIGNED NULL AFTER osg_schwellung,
    ADD COLUMN warnzeichen    JSON             NULL AFTER hand_rechts,
    ADD CONSTRAINT ck_checkin_mt_links CHECK (mt_links IS NULL OR mt_links <= 10),
    ADD CONSTRAINT ck_checkin_mt_rechts CHECK (mt_rechts IS NULL OR mt_rechts <= 10),
    ADD CONSTRAINT ck_checkin_nacken_bws CHECK (nacken_bws IS NULL OR nacken_bws <= 10),
    ADD CONSTRAINT ck_checkin_hand_rechts CHECK (hand_rechts IS NULL OR hand_rechts <= 10);
