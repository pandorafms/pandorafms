-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------

ALTER TABLE talert_templates ADD COLUMN min_alerts_reset_counter NUMBER(5, 0) DEFAULT 0;

-- ----------------------------------------------------------------------
-- Table `tserver`
-- ----------------------------------------------------------------------

ALTER TABLE tserver ADD COLUMN server_keepalive NUMBER(10, 0) DEFAULT 0;

-- ----------------------------------------------------------------------
-- Table `tagente_estado`
-- ----------------------------------------------------------------------

ALTER TABLE tagente_estado RENAME COLUMN last_known_status TO known_status;
ALTER TABLE tagente_estado ADD COLUMN last_known_status NUMBER(10, 0) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `talert_actions`
-- ---------------------------------------------------------------------
UPDATE talert_actions SET   field4 = 'integria',
							field5 = '_agent_:&#x20;_alert_name_',
							field6 = '1',
							field7 = '3',
							field8 = 'copy@dom.com',
							field9 = 'admin',
							field10 = '_alert_description_'
WHERE id = 4 AND id_alert_command = 11;

-- ---------------------------------------------------------------------
-- Table `talert_commands`
-- ---------------------------------------------------------------------
UPDATE talert_commands SET fields_descriptions = '[\"Integria&#x20;IMS&#x20;API&#x20;path\",\"Integria&#x20;IMS&#x20;API&#x20;pass\",\"Integria&#x20;IMS&#x20;user\",\"Integria&#x20;IMS&#x20;user&#x20;pass\",\"Ticket&#x20;title\",\"Ticket&#x20;group&#x20;ID\",\"Ticket&#x20;priority\",\"Email&#x20;copy\",\"Ticket&#x20;owner\",\"Ticket&#x20;description\"]', fields_values = '[\"\",\"\",\"\",\"\",\"\",\"\",\"10,Maintenance;0,Informative;1,Low;2,Medium;3,Serious;4,Very&#x20;Serious\",\"\",\"\",\"\"]' WHERE id = 11 AND name = 'Integria&#x20;IMS&#x20;Ticket';

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------

INSERT INTO tconfig (token, value) VALUES ('big_operation_step_datos_purge', '100');
INSERT INTO tconfig (token, value) VALUES ('small_operation_step_datos_purge', '1000');
INSERT INTO tconfig (token, value) VALUES ('days_autodisable_deletion', '30');

-- ---------------------------------------------------------------------
-- Table `tplanned_downtime_agents`
-- ---------------------------------------------------------------------
ALTER TABLE tplanned_downtime_agents ADD COLUMN manually_disabled NUMBER(5, 0) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tlink`
-- ---------------------------------------------------------------------
UPDATE tlink SET link = 'http://library.pandorafms.com/' WHERE name = 'Module library';
UPDATE tlink SET name = 'Enterprise Edition' WHERE id_link = 0000000002;
UPDATE tlink SET name = 'Documentation', link = 'http://wiki.pandorafms.com/' WHERE id_link = 0000000001;
UPDATE tlink SET link = 'http://forums.pandorafms.com/index.php?board=22.0' WHERE id_link = 0000000004;
UPDATE tlink SET link = 'https://github.com/pandorafms/pandorafms/issues' WHERE id_link = 0000000003;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------
ALTER TABLE tevent_filter ADD COLUMN date_from date DEFAULT NULL;
ALTER TABLE tevent_filter ADD COLUMN date_to date DEFAULT NULL;

-- ---------------------------------------------------------------------
-- Table `tusuario`
-- ---------------------------------------------------------------------
ALTER TABLE tusuario ADD (id_filter int(10) unsigned NULL default NULL);
ALTER TABLE tusuario ADD CONSTRAINT fk_id_filter FOREIGN KEY (id_filter) REFERENCES tevent_filter(id_filter) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_modulo ADD COLUMN dynamic_interval int(4) unsigned default '0';
ALTER TABLE tagente_modulo ADD COLUMN dynamic_max bigint(20) default '0';
ALTER TABLE tagente_modulo ADD COLUMN dynamic_min bigint(20) default '0';
ALTER TABLE tagente_modulo ADD COLUMN dynamic_next bigint(20) NOT NULL default '0';
ALTER TABLE tagente_modulo ADD COLUMN dynamic_two_tailed tinyint(1) unsigned default '0';
ALTER TABLE tagente_modulo ADD COLUMN parent_module_id NUMBER(10, 0);

-- ---------------------------------------------------------------------
-- Table `tnetwork_component`
-- ---------------------------------------------------------------------
ALTER TABLE tnetwork_component ADD COLUMN dynamic_interval int(4) unsigned default '0';
ALTER TABLE tnetwork_component ADD COLUMN dynamic_max int(4) default '0';
ALTER TABLE tnetwork_component ADD COLUMN dynamic_min int(4) default '0';
ALTER TABLE tnetwork_component ADD COLUMN dynamic_next bigint(20) NOT NULL default '0';
ALTER TABLE tnetwork_component ADD COLUMN dynamic_two_tailed tinyint(1) unsigned default '0';

-- ---------------------------------------------------------------------
-- Table `tagente`
-- ---------------------------------------------------------------------
ALTER TABLE tagente ADD transactional_agent tinyint(1) NOT NULL default 0;
ALTER TABLE tagente ADD remoteto tinyint(1) NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout ADD COLUMN background_color varchar(50) NOT NULL default '#FFF';

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout_data ADD COLUMN type_graph varchar(50) NOT NULL default 'area';

-- ---------------------------------------------------------------------
-- Table `tagent_custom_fields`
-- ---------------------------------------------------------------------
INSERT INTO tagent_custom_fields (name) VALUES ('eHorusID');
