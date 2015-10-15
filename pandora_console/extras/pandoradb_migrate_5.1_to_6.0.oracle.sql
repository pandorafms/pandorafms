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

UPDATE ttag_module t1
SET t1.id_policy_module = (
	SELECT t2.id_policy_module
	FROM tagente_modulo t2
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
UPDATE talert_commands SET fields_descriptions = '["Destination&#x20;address","Subject","Text","","","","","","",""]', fields_values = '["\",\"\",\"_html_editor_\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]' WHERE id = 1 AND name = 'eMail';

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO tconfig (token, value) VALUES ('post_process_custom_values', '{"0.00000038580247":"Seconds&#x20;to&#x20;months","0.00000165343915":"Seconds&#x20;to&#x20;weeks","0.00001157407407":"Seconds&#x20;to&#x20;days","0.01666666666667":"Seconds&#x20;to&#x20;minutes","0.00000000093132":"Bytes&#x20;to&#x20;Gigabytes","0.00000095367432":"Bytes&#x20;to&#x20;Megabytes","0.0009765625":"Bytes&#x20;to&#x20;Kilobytes","0.00000001653439":"Timeticks&#x20;to&#x20;weeks","0.00000011574074":"Timeticks&#x20;to&#x20;days"}');
UPDATE tconfig SET value = 'v6.0dev' WHERE token = 'db_scheme_version';
UPDATE tconfig SET value = 'https://artica.es/pandoraupdate6/server.php' WHERE token='url_update_manager';

-- ---------------------------------------------------------------------
-- Table `tnetwork_map`
-- ---------------------------------------------------------------------
ALTER TABLE tnetwork_map ADD COLUMN id_tag NUMBER(11, 0) DEFAULT 0;
ALTER TABLE tnetwork_map ADD COLUMN store_group NUMBER(11, 0) DEFAULT 0;
UPDATE tnetwork_map SET store_group = id_group;

-- ---------------------------------------------------------------------
-- Table `tperfil`
-- ---------------------------------------------------------------------
ALTER TABLE tperfil ADD COLUMN map_view NUMBER(1, 0) DEFAULT 0 NOT NULL;
ALTER TABLE tperfil ADD COLUMN map_edit NUMBER(1, 0) DEFAULT 0 NOT NULL;
ALTER TABLE tperfil ADD COLUMN map_management NUMBER(1, 0) DEFAULT 0 NOT NULL;
ALTER TABLE tperfil ADD COLUMN vconsole_view NUMBER(1, 0) DEFAULT 0 NOT NULL;
ALTER TABLE tperfil ADD COLUMN vconsole_edit NUMBER(1, 0) DEFAULT 0 NOT NULL;
ALTER TABLE tperfil ADD COLUMN vconsole_management NUMBER(1, 0) DEFAULT 0 NOT NULL;

UPDATE tperfil SET map_view = 1, vconsole_view = 1 WHERE report_view = 1;
UPDATE tperfil SET map_edit = 1, vconsole_edit = 1 WHERE report_edit = 1;
UPDATE tperfil SET map_management = 1, vconsole_management = 1 WHERE report_management = 1;

-- ---------------------------------------------------------------------
-- Table tsessions_php
-- ---------------------------------------------------------------------
CREATE TABLE tsessions_php (
	id_session VARCHAR2(52) NOT NULL PRIMARY KEY,
	last_active NUMBER(20, 0) NOT NULL,
	data CLOB default ''
);

-- ---------------------------------------------------------------------
-- Table tplugin
-- ---------------------------------------------------------------------
UPDATE tplugin
	SET macros = '{"1":{"macro":"_field1_","desc":"Target&#x20;IP","help":"","value":"","hide":""},"2":{"macro":"_field2_","desc":"Username","help":"","value":"","hide":""},"3":{"macro":"_field3_","desc":"Password","help":"","value":"","hide":""},"4":{"macro":"_field4_","desc":"Sensor","help":"","value":"","hide":""},"5":{"macro":"_field5_","desc":"Additional&#x20;Options","help":"","value":"","hide":""}}',
	SET parameters = '-h&#x20;_field1_&#x20;-u&#x20;_field2_&#x20;-p&#x20;_field3_&#x20;-s&#x20;_field4_&#x20;--&#x20;_field5_'
WHERE id = 1 AND name = 'IPMI&#x20;Plugin';

-- ---------------------------------------------------------------------
-- Table trecon_script
-- ---------------------------------------------------------------------
UPDATE trecon_script SET
	description = 'Specific&#x20;Pandora&#x20;FMS&#x20;Intel&#x20;DCM&#x20;Discovery&#x20;&#40;c&#41;&#x20;Artica&#x20;ST&#x20;2011&#x20;&lt;info@artica.es&gt;&#x0d;&#x0a;&#x0d;&#x0a;Usage:&#x20;./ipmi-recon.pl&#x20;&lt;task_id&gt;&#x20;&lt;group_id&gt;&#x20;&lt;create_incident_flag&gt;&#x20;&lt;custom_field1&gt;&#x20;&lt;custom_field2&gt;&#x20;&lt;custom_field3&gt;&#x20;&lt;custom_field4&gt;&#x0d;&#x0a;&#x0d;&#x0a;*&#x20;custom_field1&#x20;=&#x20;Network&#x20;i.e.:&#x20;192.168.100.0/24&#x0d;&#x0a;*&#x20;custom_field2&#x20;=&#x20;Username&#x0d;&#x0a;*&#x20;custom_field3&#x20;=&#x20;Password&#x0d;&#x0a;*&#x20;custom_field4&#x20;=&#x20;Additional&#x20;parameters&#x20;i.e.:&#x20;-D&#x20;LAN_2_0',
	macros = '{"1":{"macro":"_field1_","desc":"Network","help":"i.e.:&#x20;192.168.100.0/24","value":"","hide":""},"2":{"macro":"_field2_","desc":"Username","help":"","value":"","hide":""},"3":{"macro":"_field3_","desc":"Password","help":"","value":"","hide":"1"},"4":{"macro":"_field4_","desc":"Additional&#x20;parameters","help":"Optional&#x20;additional&#x20;parameters&#x20;such&#x20;as&#x20;-D&#x20;LAN_2_0&#x20;to&#x20;use&#x20;IPMI&#x20;ver&#x20;2.0&#x20;instead&#x20;of&#x20;1.5.&#x20;&#x20;These&#x20;options&#x20;will&#x20;also&#x20;be&#x20;passed&#x20;to&#x20;the&#x20;IPMI&#x20;plugin&#x20;when&#x20;the&#x20;current&#x20;values&#x20;are&#x20;read.","value":"","hide":""}}'
WHERE id_recon_script = 2 AND name = 'IPMI&#x20;Recon';

-- ---------------------------------------------------------------------
-- Table `tnetwork_component`
-- ---------------------------------------------------------------------

UPDATE tnetwork_component SET snmp_oid ='SELECT&#x20;DNSHostName&#x20;FROM&#x20;Win32_ComputerSystem' WHERE id_nc = 204 AND name = 'Hostname';
UPDATE `tnetwork_component` set `tcp_port`=0 WHERE id_nc=207;
UPDATE `tnetwork_component` set `tcp_port`=0 WHERE id_nc=219;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------
ALTER TABLE tevent_filter ADD COLUMN id_agent_module int(25) DEFAULT 0;
ALTER TABLE tevent_filter ADD COLUMN id_agent int(25) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tgraph_source`
-- ---------------------------------------------------------------------	
ALTER TABLE tgraph_source ADD COLUMN label VARCHAR2(150) DEFAULT '';
