-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------

ALTER TABLE talert_templates ADD COLUMN min_alerts_reset_counter NUMBER(5, 0) DEFAULT 0;

-- ----------------------------------------------------------------------
-- Table `tserver`
-- ----------------------------------------------------------------------

ALTER TABLE tserver ADD COLUMN server_keepalive NUMBER(10, 0) DEFAULT 0;
