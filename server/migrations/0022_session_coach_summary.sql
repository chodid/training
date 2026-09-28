-- Begründungstexte der Planung (AP-13, D-56, docs/konzept/gefuehrte-einheit.md 5.1, E-01): Kurzsatz je Einheit
-- (Was und warum, höchstens 200 Zeichen), Pflicht in write_week_plan außer bei Ruhetagen (Prüfung in der Anwendung).
-- coach_rationale bleibt der ausführliche Text; bestehende Einheiten behalten ihn, der Kurzsatz bleibt dort leer
-- (kein automatisches Aufteilen alter Texte). Woche: focus = Kurzsatz, coach_notes = ausführlicher Text (vorhanden).
-- Rückweg (manuell, nur wenn nötig):
--   ALTER TABLE `session` DROP COLUMN coach_summary;
--   DELETE FROM schema_version WHERE version = 22;
ALTER TABLE `session`
    ADD COLUMN coach_summary VARCHAR(200) NULL AFTER plan_json;
