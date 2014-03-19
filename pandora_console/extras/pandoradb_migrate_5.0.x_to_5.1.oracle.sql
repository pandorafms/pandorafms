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
