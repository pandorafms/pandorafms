-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------

ALTER TABLE talert_templates ADD COLUMN `min_alerts_reset_counter` tinyint(1) DEFAULT 0;

-- ----------------------------------------------------------------------
-- Table `tserver`
-- ----------------------------------------------------------------------

ALTER TABLE tserver ADD COLUMN `server_keepalive` int(11) DEFAULT 0;
