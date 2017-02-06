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
ALTER TABLE tagente ADD remote tinyint(1) NOT NULL default 0;
ALTER TABLE tagente ADD cascade_protection_module int(10) unsigned default '0';

-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout ADD COLUMN background_color varchar(50) NOT NULL default '#FFF';

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout_data ADD COLUMN type_graph varchar(50) NOT NULL default 'area';
ALTER TABLE tlayout_data ADD COLUMN label_position varchar(50) NOT NULL default 'down';

-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------
ALTER TABLE talert_templates ADD COLUMN field11 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field12 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field13 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field14 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field15 TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field11_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field12_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field13_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field14_recovery TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field15_recovery TEXT NOT NULL DEFAULT "";
