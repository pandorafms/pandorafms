-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------

ALTER TABLE tlayout DROP COLUMN fullscreen;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------

ALTER TABLE tlayout_data DROP COLUMN no_link_color;
ALTER TABLE tlayout_data DROP COLUMN label_color;
ALTER TABLE tlayout_data ADD COLUMN border_width INTEGER NOT NULL default 0;
ALTER TABLE tlayout_data ADD COLUMN border_color varchar(200) DEFAULT "";
ALTER TABLE tlayout_data ADD COLUMN fill_color varchar(200) DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `tconfig_os`
-- ---------------------------------------------------------------------

INSERT INTO tconfig_os (name, description, icon_name) VALUES ('Mainframe', 'Mainframe agent', 'so_mainframe.png');

-- ---------------------------------------------------------------------
-- Table `ttag_module`
-- ---------------------------------------------------------------------

ALTER TABLE tlayout_data ADD COLUMN id_policy_module NUMBER(10, 0) DEFAULT 0 NOT NULL;

UPDATE ttag_module AS t1
SET t1.id_policy_module = (
	SELECT t2.id_policy_module
	FROM tagente_modulo AS t2
	WHERE t1.id_agente_modulo = t2.id_agente_modulo);

/* 2014/12/10 */
-- ----------------------------------------------------------------------
-- Table `tuser_double_auth`
-- ----------------------------------------------------------------------
CREATE TABLE tuser_double_auth (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_user VARCHAR2(60) NOT NULL REFERENCES tusuario(id_user) ON DELETE CASCADE,
	secret VARCHAR2(20) NOT NULL
);
CREATE SEQUENCE tuser_double_auth_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tuser_double_auth_inc BEFORE INSERT ON tuser_double_auth REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tuser_double_auth_s.nextval INTO :NEW.ID FROM dual; END tuser_double_auth_inc;;

-- ----------------------------------------------------------------------
-- Table `ttipo_modulo`
-- ----------------------------------------------------------------------
INSERT INTO ttipo_modulo VALUES (5,'generic_data_inc_abs',0,'Generic numeric incremental (absolute)','mod_data_inc_abs.png');

-- ---------------------------------------------------------------------
-- Table `tusuario`
-- ---------------------------------------------------------------------
ALTER TABLE tusuario ADD COLUMN strict_acl NUMBER(5, 0) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `talert_commands`
-- ---------------------------------------------------------------------
UPDATE talert_commands SET fields_descriptions = '[\"Destination&#x20;address\",\"Subject\",\"Text\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]', fields_values = '[\"\",\"\",\"_html_editor_\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]' WHERE id = 1 AND name = 'eMail';
