-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------
ALTER TABLE talert_templates ADD (field1_recovery CLOB default '' NULL);

-- ---------------------------------------------------------------------
-- Table `talert_actions`
-- ---------------------------------------------------------------------
ALTER TABLE talert_actions ADD (field1_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field2_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field3_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field4_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field5_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field6_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field7_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field8_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field9_recovery CLOB default '' NULL);
ALTER TABLE talert_actions ADD (field10_recovery CLOB default '' NULL);

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO tconfig (token, value) VALUES ('graph_color4', '#FF66CC');
INSERT INTO tconfig (token, value) VALUES ('graph_color5', '#CC0000');
INSERT INTO tconfig (token, value) VALUES ('graph_color6', '#0033FF');
INSERT INTO tconfig (token, value) VALUES ('graph_color7', '#99FF99');
INSERT INTO tconfig (token, value) VALUES ('graph_color8', '#330066');
INSERT INTO tconfig (token, value) VALUES ('graph_color9', '#66FFFF');
INSERT INTO tconfig (token, value) VALUES ('graph_color10', '#6666FF');

UPDATE tconfig SET value='#FFFF00' WHERE token='graph_color2';
UPDATE tconfig SET value='#FF6600' WHERE token='graph_color3';

-- ---------------------------------------------------------------------
-- Table `tconfig_os`
-- ---------------------------------------------------------------------
INSERT INTO tconfig_os VALUES (17, 'Router', 'Generic router', 'so_router.png');
INSERT INTO tconfig_os VALUES (18, 'Switch', 'Generic switch', 'so_switch.png');
INSERT INTO tconfig_os VALUES (19, 'Satellite', 'Satellite agent', 'satellite.png');

/* 2014/03/18 */
-- ----------------------------------------------------------------------
-- Table `tmodule_relationship`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tmodule_relationship (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	module_a NUMBER(10, 0) NOT NULL REFERENCES tagente_modulo(id_agente_modulo)
		ON DELETE CASCADE,
	module_b NUMBER(10, 0) NOT NULL REFERENCES tagente_modulo(id_agente_modulo)
		ON DELETE CASCADE,
	disable_update NUMBER(1, 0) default 0 NOT NULL
);

CREATE SEQUENCE tmodule_relationship_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tmodule_relationship_inc BEFORE INSERT ON tmodule_relationship REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmodule_relationship_s.nextval INTO :NEW.ID FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE talert_snmp ADD (id_group NUMBER(10, 0) default 0 NOT NULL);

/* 2014/03/19 */
-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE talert_snmp ADD (_snmp_f11_ CLOB default '');
ALTER TABLE talert_snmp ADD (_snmp_f12_ CLOB default '');
ALTER TABLE talert_snmp ADD (_snmp_f13_ CLOB default '');
ALTER TABLE talert_snmp ADD (_snmp_f14_ CLOB default '');
ALTER TABLE talert_snmp ADD (_snmp_f15_ CLOB default '');
ALTER TABLE talert_snmp ADD (_snmp_f16_ CLOB default '');
ALTER TABLE talert_snmp ADD (_snmp_f17_ CLOB default '');
ALTER TABLE talert_snmp ADD (_snmp_f18_ CLOB default '');
ALTER TABLE talert_snmp ADD (_snmp_f19_ CLOB default '');
ALTER TABLE talert_snmp ADD (_snmp_f20_ CLOB default '');

ALTER TABLE tnetwork_map ADD (l2_network NUMBER(1, 0) default 0 NOT NULL);

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
ALTER TABLE `tlayout_data` ADD COLUMN id_group NUMBER(10, 0) default 0 NOT NULL;

-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE talert_snmp ADD (order_1 NUMBER(10, 0) default 1 NOT NULL);
ALTER TABLE talert_snmp ADD (order_2 NUMBER(10, 0) default 2 NOT NULL);
ALTER TABLE talert_snmp ADD (order_3 NUMBER(10, 0) default 3 NOT NULL);
ALTER TABLE talert_snmp ADD (order_4 NUMBER(10, 0) default 4 NOT NULL);
ALTER TABLE talert_snmp ADD (order_5 NUMBER(10, 0) default 5 NOT NULL);
ALTER TABLE talert_snmp ADD (order_6 NUMBER(10, 0) default 6 NOT NULL);
ALTER TABLE talert_snmp ADD (order_7 NUMBER(10, 0) default 7 NOT NULL);
ALTER TABLE talert_snmp ADD (order_8 NUMBER(10, 0) default 8 NOT NULL);
ALTER TABLE talert_snmp ADD (order_9 NUMBER(10, 0) default 9 NOT NULL);
ALTER TABLE talert_snmp ADD (order_10 NUMBER(10, 0) default 10 NOT NULL);
ALTER TABLE talert_snmp ADD (order_11 NUMBER(10, 0) default 11 NOT NULL);
ALTER TABLE talert_snmp ADD (order_12 NUMBER(10, 0) default 12 NOT NULL);
ALTER TABLE talert_snmp ADD (order_13 NUMBER(10, 0) default 13 NOT NULL);
ALTER TABLE talert_snmp ADD (order_14 NUMBER(10, 0) default 14 NOT NULL);
ALTER TABLE talert_snmp ADD (order_15 NUMBER(10, 0) default 15 NOT NULL);
ALTER TABLE talert_snmp ADD (order_16 NUMBER(10, 0) default 16 NOT NULL);
ALTER TABLE talert_snmp ADD (order_17 NUMBER(10, 0) default 17 NOT NULL);
ALTER TABLE talert_snmp ADD (order_18 NUMBER(10, 0) default 18 NOT NULL);
ALTER TABLE talert_snmp ADD (order_19 NUMBER(10, 0) default 19 NOT NULL);
ALTER TABLE talert_snmp ADD (order_20 NUMBER(10, 0) default 20 NOT NULL);

-- ---------------------------------------------------------------------
-- Table talert_snmp_action
-- ---------------------------------------------------------------------
CREATE TABLE  talert_snmp_action (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_alert_snmp NUMBER(10, 0) NOT NULL default 0,
	alert_type NUMBER(2, 0) NOT NULL default 0,
	al_field1 CLOB default '' NOT NULL,
	al_field2 CLOB default '' NOT NULL,
	al_field3 CLOB default '' NOT NULL,
	al_field4 CLOB default '' NOT NULL,
	al_field5 CLOB default '' NOT NULL,
	al_field6 CLOB default '' NOT NULL,
	al_field7 CLOB default '' NOT NULL,
	al_field8 CLOB default '' NOT NULL,
	al_field9 CLOB default '' NOT NULL,
	al_field10 CLOB default '' NOT NULL
);

-- ---------------------------------------------------------------------
-- Table treport
-- ---------------------------------------------------------------------
ALTER TABLE treport ADD (non_interactive NUMBER(5, 0) default 0 NOT NULL);

/* 2014/04/10 */
ALTER TABLE treport_content ADD (name VARCHAR2(150) default NULL);

/* 2014/04/11 */
-- ---------------------------------------------------------------------
-- Table `trecon_script` and `trecon_task`
-- ---------------------------------------------------------------------
ALTER TABLE trecon_script ADD (macros CLOB default '' NOT NULL);
ALTER TABLE trecon_task ADD (macros CLOB default '' NOT NULL);

/* 2014/05/05 */
-- ---------------------------------------------------------------------
-- Table tlink
-- ---------------------------------------------------------------------
UPDATE tlink SET link='http://wiki.pandorafms.com/?title=Pandora' WHERE name='Pandora FMS Manual';

/* 2014/05/07 */
-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO tconfig (token, value) VALUES ('custom_report_front', 0);
INSERT INTO tconfig (token, value) VALUES ('custom_report_front_font', 'FreeSans.ttf');
INSERT INTO tconfig (token, value) VALUES ('custom_report_front_logo', 'images/pandora_logo_white.jpg');
INSERT INTO tconfig (token, value) VALUES ('custom_report_front_header', '');
INSERT INTO tconfig (token, value) VALUES ('custom_report_front_footer', '');

/* 2014/05/19 */
-- ---------------------------------------------------------------------
-- Table `tnetwork_profile`
-- ---------------------------------------------------------------------
DELETE FROM tnetwork_profile WHERE id_np=1;
DELETE FROM tnetwork_profile WHERE id_np=4;
DELETE FROM tnetwork_profile WHERE id_np=5;
DELETE FROM tnetwork_profile WHERE id_np=6;

/* 2014/05/19 */
-- ---------------------------------------------------------------------
-- Table `tnetwork_profile_component`
-- ---------------------------------------------------------------------
DELETE FROM tnetwork_profile_component WHERE id_np=1;
DELETE FROM tnetwork_profile_component WHERE id_np=4;
DELETE FROM tnetwork_profile_component WHERE id_np=5;
DELETE FROM tnetwork_profile_component WHERE id_np=6;
DELETE FROM tnetwork_profile_component WHERE id_nc=24 AND id_np=3;
