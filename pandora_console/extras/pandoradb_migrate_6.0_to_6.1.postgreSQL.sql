-- ----------------------------------------------------------------------
-- Table `tagente_estado`
-- ----------------------------------------------------------------------

ALTER TABLE tagente_estado RENAME COLUMN last_known_status TO known_status;
ALTER TABLE tagente_estado ADD COLUMN last_known_status NUMBER(10, 0) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_modulo ADD COLUMN dynamic_interval int(4) unsigned default 0;
ALTER TABLE tagente_modulo ADD COLUMN dynamic_max bigint(20) default 0;
ALTER TABLE tagente_modulo ADD COLUMN dynamic_min bigint(20) default 0;
ALTER TABLE tagente_modulo ADD COLUMN dynamic_next bigint(20) NOT NULL default 0;
ALTER TABLE tagente_modulo ADD COLUMN dynamic_two_tailed tinyint(1) unsigned default 0;

-- ---------------------------------------------------------------------
-- Table `tnetwork_component`
-- ---------------------------------------------------------------------
ALTER TABLE tnetwork_component ADD COLUMN dynamic_interval int(4) unsigned default 0;
ALTER TABLE tnetwork_component ADD COLUMN dynamic_max int(4) default 0;
ALTER TABLE tnetwork_component ADD COLUMN dynamic_min int(4) default 0;
ALTER TABLE tnetwork_component ADD COLUMN dynamic_next bigint(20) NOT NULL default 0;
ALTER TABLE tnetwork_component ADD COLUMN dynamic_two_tailed tinyint(1) unsigned default 0;

-- ---------------------------------------------------------------------
-- Table `tagente`
-- ---------------------------------------------------------------------
ALTER TABLE tagente ADD transactional_agent tinyint(1) NOT NULL default 0;