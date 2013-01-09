-- -----------------------------------------------------
-- Table tusuario
-- -----------------------------------------------------
ALTER TABLE tusuario ADD ((disabled NUMBER(10,0) DEFAULT 0 NOT NULL);
ALTER TABLE tusuario ADD ((shortcut NUMBER(5, 0) DEFAULT 0);
ALTER TABLE tusuario ADD ((force_change_pass NUMBER(5,0) DEFAULT 0 NOT NULL);
ALTER TABLE tusuario ADD ((last_pass_change TIMESTAMP DEFAULT 0);
ALTER TABLE tusuario ADD ((last_failed_login TIMESTAMP DEFAULT 0);
ALTER TABLE tusuario ADD ((failed_attempt NUMBER(5,0) DEFAULT 0 NOT NULL);
ALTER TABLE tusuario ADD ((login_blocked NUMBER(5,0) DEFAULT 0 NOT NULL);
ALTER TABLE tusuario ADD (disabled NUMBER(10, 0) NOT NULL DEFAULT 0;
ALTER TABLE tusuario ADD (shortcut NUMBER(5, 0) DEFAULT 0;
ALTER TABLE tusuario ADD (shortcut_data CLOB DEFAULT '';
ALTER TABLE tusuario ADD (section VARCHAR2(255) NOT NULL);
INSERT INTO tusuario (section) VALUES ('Default');
ALTER TABLE tusuario ADD ((data_section VARCHAR2(255) NOT NULL);
ALTER TABLE tusuario ADD ((metaconsole_access VARCHAR2(100) DEFAULT 'only_console' NOT NULL);
ALTER TABLE tusuario ADD CONSTRAINT t_usuario_metaconsole_access_cons CHECK (metaconsole_access IN ('basic','advanced','custom','all','only_console'));
ALTER TABLE tusuario ADD ((not_login NUMBER(5,0) default 0 NOT NULL);

-- ---------------------------------------------------------------------
-- Table "tnetflow_filter"
-- ---------------------------------------------------------------------
CREATE TABLE tnetflow_filter (
	id_sg NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_name VARCHAR2(600) NOT NULL,
	id_group NUMBER(10, 0),
	ip_dst CLOB NOT NULL,
	ip_src CLOB NOT NULL,
	dst_port CLOB NOT NULL,
	src_port CLOB NOT NULL,
	advanced_filter CLOB NOT NULL,
	filter_args CLOB NOT NULL,
	aggregate VARCHAR2(60),
	output VARCHAR2(60)
);
CREATE SEQUENCE tnetflow_filter_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_filter_inc BEFORE INSERT ON tnetflow_filter REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_filter_s.nextval INTO :NEW.ID_SG FROM dual; END tnetflow_filter_inc;;

-- -----------------------------------------------------
-- Table "tnetflow_report"
-- -----------------------------------------------------
CREATE TABLE tnetflow_report (
	id_report NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_name VARCHAR2(100) NOT NULL,
	description CLOB default '',
	id_group NUMBER(10, 0),
	server_name CLOB default ''
);
CREATE SEQUENCE tnetflow_report_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_report_inc BEFORE INSERT ON tnetflow_report REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_report_s.nextval INTO :NEW.ID_REPORT FROM dual; END tnetflow_report_inc;;

-- -----------------------------------------------------
-- Table "tnetflow_report_content"
-- -----------------------------------------------------
CREATE TABLE tnetflow_report_content (
	id_rc NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_report NUMBER(10, 0) NOT NULL REFERENCES tnetflow_report(id_report) ON DELETE CASCADE,
	id_filter NUMBER(10,0) NOT NULL REFERENCES tnetflow_filter(id_sg) ON DELETE CASCADE,
	description CLOB default '',
	"date" NUMBER(20, 0) default 0 NOT NULL,
	period NUMBER(11, 0) default 0 NOT NULL,
	max NUMBER(11, 0) default 0 NOT NULL,
	show_graph VARCHAR2(60),
	"order" NUMBER(11,0) default 0 NOT NULL
);
CREATE SEQUENCE tnetflow_report_content_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_report_content_inc BEFORE INSERT ON tnetflow_report_content REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_report_content_s.nextval INTO :NEW.ID_RC FROM dual; END tnetflow_report_content_inc;;

-- -----------------------------------------------------
-- Table tincidencia
-- -----------------------------------------------------
ALTER TABLE tincidencia ADD (id_agent NUMBER(10,0) default 0 NULL);

-- -----------------------------------------------------
-- Table tagente
-- -----------------------------------------------------
ALTER TABLE tagente ADD (url_address CLOB default '' NULL);
ALTER TABLE tagente ADD (quiet NUMBER(5, 0) default 0 NOT NULL);
ALTER TABLE tagente ADD (normal_count NUMBER(20, 0) default 0 NOT NULL);
ALTER TABLE tagente ADD (warning_count NUMBER(20, 0) default 0 NOT NULL);
ALTER TABLE tagente ADD (critical_count NUMBER(20, 0) default 0 NOT NULL);
ALTER TABLE tagente ADD (unknown_count NUMBER(20, 0) default 0 NOT NULL);
ALTER TABLE tagente ADD (notinit_count NUMBER(20, 0) default 0 NOT NULL);
ALTER TABLE tagente ADD (total_count NUMBER(20, 0) default 0 NOT NULL);
ALTER TABLE tagente ADD (fired_count NUMBER(20, 0) default 0 NOT NULL);

-- -----------------------------------------------------
-- Table talert_special_days
-- -----------------------------------------------------
CREATE TABLE talert_special_days (
	id NUMBER(10,0) NOT NULL PRIMARY KEY,
	date DATE default '0000-00-00' NOT NULL,
	same_day VARCHAR2(20) default 'sunday',
	description CLOB,
	CONSTRAINT talert_special_days_same_day_cons CHECK (same_day IN ('monday','tuesday','wednesday','thursday','friday','saturday','sunday'))
);

CREATE SEQUENCE talert_special_days_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER talert_special_days_inc BEFORE INSERT ON talert_special_days REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_special_days_s.nextval INTO :NEW.ID FROM dual; END talert_special_days_inc;;

-- -----------------------------------------------------
-- Table talert_templates
-- -----------------------------------------------------
ALTER TABLE talert_templates ADD (special_day NUMBER(5,0) default 0);
ALTER TABLE talert_templates ADD (wizard_level VARCHAR2(100) default 'nowizard' NOT NULL);
ALTER TABLE talert_templates ADD CONSTRAINT t_alert_templates_wizard_level_cons CHECK (wizard_level IN ('basic','advanced','custom','nowizard'));

-- -----------------------------------------------------
-- Table talert_templates
-- -----------------------------------------------------
ALTER TABLE tplanned_downtime ADD (monday NUMBER(5, 0) default 0);
ALTER TABLE tplanned_downtime ADD (tuesday NUMBER(5, 0) default 0);
ALTER TABLE tplanned_downtime ADD (wednesday NUMBER(5, 0) default 0);
ALTER TABLE tplanned_downtime ADD (thursday NUMBER(5, 0) default 0);
ALTER TABLE tplanned_downtime ADD (friday NUMBER(5, 0) default 0);
ALTER TABLE tplanned_downtime ADD (saturday NUMBER(5, 0) default 0);
ALTER TABLE tplanned_downtime ADD (sunday NUMBER(5, 0) default 0);
ALTER TABLE tplanned_downtime ADD (periodically_time_from DATE default NULL);
ALTER TABLE tplanned_downtime ADD (periodically_time_to DATE default NULL);
ALTER TABLE tplanned_downtime ADD (periodically_day_from NUMBER(19, 0) default NULL);
ALTER TABLE tplanned_downtime ADD (periodically_day_to NUMBER(19, 0) default NULL);
ALTER TABLE tplanned_downtime ADD (type_downtime VARCHAR2(100) NOT NULL default 'disabled_agents_alerts');
ALTER TABLE tplanned_downtime ADD (type_execution VARCHAR2(100) NOT NULL default 'once');
ALTER TABLE tplanned_downtime ADD (type_periodicity VARCHAR2(100) NOT NULL default 'weekly');

-- -----------------------------------------------------
-- Table tplanned_downtime_agents
-- -----------------------------------------------------
DELETE FROM tplanned_downtime_agents
WHERE id_downtime NOT IN (SELECT id FROM tplanned_downtime);

ALTER TABLE tplanned_downtime_agents
add constraint tplanned_downtimes_foreign_key
foreign key (id_downtime)
references tplanned_downtime (id);

ALTER TABLE tplanned_downtime_agents ADD (all_modules NUMBER(5, 0) default 1);

-- -----------------------------------------------------
-- Table tplanned_downtime_modules
-- -----------------------------------------------------
CREATE TABLE tplanned_downtime_modules (
	id NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_agent NUMBER(19, 0) default 0 NOT NULL,
	id_agent_module NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_downtime NUMBER(19, 0) default 0 NOT NULL REFERENCES tplanned_downtime(id) ON DELETE CASCADE
);
CREATE SEQUENCE tplanned_downtime_modules_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tplanned_downtime_modules_inc BEFORE INSERT ON tplanned_downtime_modules REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tplanned_downtime_modules_s.nextval INTO :NEW.ID FROM dual; END tplanned_downtime_modules_inc;;

-- ---------------------------------------------------------------------
-- Table tevento
-- ---------------------------------------------------------------------
ALTER TABLE tevento ADD (source VARCHAR2(100) default '' NOT NULL);
ALTER TABLE tevento ADD (id_extra VARCHAR2(100) default '' NOT NULL);
ALTER TABLE tevento ADD (critical_instructions VARCHAR2(255) default '');
ALTER TABLE tevento ADD (warning_instructions VARCHAR2(255) default '');
ALTER TABLE tevento ADD (unknown_instructions VARCHAR2(255) default '');
ALTER TABLE tevento MODIFY CONSTRAINT tevento_event_type_cons CHECK (event_type IN ('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change'))
ALTER TABLE tevento ADD (owner_user VARCHAR2(100) NOT NULL default '0');
ALTER TABLE tevento ADD (ack_utimestamp NUMBER(19, 0) NOT NULL default 0);

-- ---------------------------------------------------------------------
-- Table tgrupo
-- ---------------------------------------------------------------------
ALTER TABLE tgrupo ADD (description CLOB);
ALTER TABLE tgrupo ADD (contact CLOB);
ALTER TABLE tgrupo ADD (other CLOB);

-- ---------------------------------------------------------------------
-- Table talert_snmp
-- ---------------------------------------------------------------------

ALTER TABLE talert_snmp ADD (_snmp_f1_ CLOB default ''); 
ALTER TABLE talert_snmp ADD (_snmp_f2_ CLOB default ''); 
ALTER TABLE talert_snmp ADD (_snmp_f3_ CLOB default ''); 
ALTER TABLE talert_snmp ADD (_snmp_f4_ CLOB default ''); 
ALTER TABLE talert_snmp ADD (_snmp_f5_ CLOB default ''); 
ALTER TABLE talert_snmp ADD (_snmp_f6_ CLOB default '');
ALTER TABLE talert_snmp ADD (trap_type NUMBER(10, 0) DEFAULT -1 NOT NULL);
ALTER TABLE talert_snmp ADD (single_value VARCHAR2(255) DEFAULT '');

-- ---------------------------------------------------------------------
-- Table tevent_filter
-- ---------------------------------------------------------------------
CREATE TABLE tevent_filter (
	id_filter NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_group_filter NUMBER(10, 0) default 0 NOT NULL,
	id_name VARCHAR2(600) NOT NULL,
	id_group NUMBER(10, 0) default 0 NOT NULL,
	event_type CLOB default '' NOT NULL,
	severity NUMBER(10, 0) default -1 NOT NULL,
	status NUMBER(10, 0) default -1 NOT NULL,
	search CLOB default '',
	text_agent CLOB default '', 
	pagination NUMBER(10, 0) default 25 NOT NULL,
	event_view_hr NUMBER(10, 0) default 8 NOT NULL,
	id_user_ack CLOB,
	group_rep NUMBER(10, 0) default 0 NOT NULL,
	tag_with CLOB,
	tag_without CLOB,
	filter_only_alert NUMBER(10, 0) default -1 NOT NULL
);

-- -----------------------------------------------------
-- Table tconfig
-- -----------------------------------------------------
ALTER TABLE tconfig MODIFY value TEXT NOT NULL;
INSERT INTO tconfig (token, value) VALUES ('event_fields', 'evento,id_agente,estado,timestamp');

-- -----------------------------------------------------
-- Table treport_content_item
-- -----------------------------------------------------
ALTER TABLE treport_content_item ADD FOREIGN KEY (id_report_content) REFERENCES treport_content(id_rc) ON DELETE CASCADE;

-- -----------------------------------------------------
-- Table treport
-- -----------------------------------------------------
ALTER TABLE treport ADD (id_template NUMBER(10, 0) default 0 NOT NULL);
ALTER TABLE treport ADD (id_group_edit  NUMBER(19, 0) default 0 NOT NULL);
ALTER TABLE treport ADD (metaconsole NUMBER(5, 0) DEFAULT 0);

-- -----------------------------------------------------
-- Table tgraph
-- -----------------------------------------------------
ALTER TABLE tgraph ADD (id_graph_template NUMBER(11, 0) default 0 NOT NULL);

-- -----------------------------------------------------
-- Table ttipo_modulo
-- -----------------------------------------------------
UPDATE ttipo_modulo SET descripcion='Generic data' WHERE id_tipo=1;
UPDATE ttipo_modulo SET descripcion='Generic data incremental' WHERE id_tipo=4;

-- -----------------------------------------------------
-- Table treport_content_item
-- -----------------------------------------------------
ALTER TABLE treport_content_item ADD (operation CLOB default '');

-- -----------------------------------------------------
-- Table tmensajes
-- -----------------------------------------------------
ALTER TABLE tmensajes MODIFY mensaje VARCHAR2(255) NOT NULL DEFAULT '';

-- -----------------------------------------------------
-- Table talert_compound
-- -----------------------------------------------------

ALTER TABLE talert_compound ADD (special_day NUMBER(5,0) default 0);

-- -----------------------------------------------------
-- Table tnetwork_component
-- -----------------------------------------------------

ALTER TABLE tnetwork_component ADD (unit CLOB default '';
ALTER TABLE tnetwork_component ADD (id_category NUMBER(10, 0) default 0);

-- -----------------------------------------------------
-- Table talert_commands
-- -----------------------------------------------------

INSERT INTO talert_commands (name, command, description, internal) VALUES ('Validate Event','Internal type','This alert validate the events matched with a module given the agent name (_field1_) and module name (_field2_)', 1);

-- -----------------------------------------------------
-- Table tconfig
-- -----------------------------------------------------

INSERT INTO tconfig (token, value) VALUES ('enable_pass_policy', 0);
INSERT INTO tconfig (token, value) VALUES ('pass_size', 4);
INSERT INTO tconfig (token, value) VALUES ('pass_needs_numbers', 0);
INSERT INTO tconfig (token, value) VALUES ('pass_needs_symbols', 0);
INSERT INTO tconfig (token, value) VALUES ('pass_expire', 0);
INSERT INTO tconfig (token, value) VALUES ('first_login', 0);
INSERT INTO tconfig (token, value) VALUES ('mins_fail_pass', 5);
INSERT INTO tconfig (token, value) VALUES ('number_attempts', 5);
INSERT INTO tconfig (token, value) VALUES ('enable_pass_policy_admin', 0);
INSERT INTO tconfig (token, value) VALUES ('enable_pass_history', 0);
INSERT INTO tconfig (token, value) VALUES ('compare_pass', 3);
INSERT INTO tconfig (token, value) VALUES ('meta_style', 'meta_pandora');
INSERT INTO tconfig (token, value) VALUES ('enable_refr', 0);

-- -----------------------------------------------------
-- Table tpassword_history
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS tpassword_history (
  id_pass  NUMBER(10) NOT NULL PRIMARY KEY,
  id_user varchar2(60) NOT NULL,
  password varchar2(45) default '',
  date_begin TIMESTAMP DEFAULT 0,
  date_end TIMESTAMP DEFAULT 0
);
CREATE SEQUENCE tpassword_history_s INCREMENT BY 1 START WITH 1;

-- -----------------------------------------------------
-- Table tconfig
-- -----------------------------------------------------
UPDATE tconfig SET value='comparation'
WHERE token='prominent_time';

-- -----------------------------------------------------
-- Table tnetwork_component
-- -----------------------------------------------------
ALTER TABLE tnetwork_component ADD (wizard_level VARCHAR2(100) default 'nowizard' NOT NULL);
ALTER TABLE tnetwork_component ADD CONSTRAINT t_network_component_wizard_level_cons CHECK (wizard_level IN ('basic','advanced','custom','nowizard'));
ALTER TABLE tnetwork_component ADD (only_metaconsole NUMBER(5, 0) default 0 NOT NULL);
ALTER TABLE tnetwork_component ADD (macros CLOB default '');


-- -----------------------------------------------------
-- Table tagente_modulo
-- -----------------------------------------------------
ALTER TABLE tagente_modulo ADD (wizard_level VARCHAR2(100) default 'nowizard' NOT NULL);
ALTER TABLE tagente_modulo ADD CONSTRAINT t_agente_modulo_wizard_level_cons CHECK (wizard_level IN ('basic','advanced','custom','nowizard'));
ALTER TABLE tagente_modulo ADD (macros CLOB default '');
ALTER TABLE tagente_modulo ADD (quiet NUMBER(5, 0) default 0 NOT NULL);
ALTER TABLE tagente_modulo ADD (cron_interval VARCHAR2(100) DEFAULT '');
ALTER TABLE tagente_modulo ADD (max_retries NUMBER(10, 0) default 0);

-- Move the number of retries for web modules from plugin_pass to max_retries
UPDATE tagente_modulo SET max_retries=plugin_pass WHERE id_modulo=7;

-- -----------------------------------------------------
-- Table tplugin
-- -----------------------------------------------------
ALTER TABLE tplugin ADD (macros CLOB default '');
ALTER TABLE tplugin ADD (parameters CLOB default '');
ALTER TABLE tplugin ADD (max_retries NUMBER(10, 0) default 0);

-- -----------------------------------------------------
-- Table trecon_task
-- -----------------------------------------------------
ALTER TABLE trecon_task MODIFY subnet TEXT NOT NULL;
ALTER TABLE trecon_task MODIFY field1 TEXT NOT NULL;

-- -----------------------------------------------------
-- Table tlayout_data
-- -----------------------------------------------------
ALTER TABLE tlayout_data ADD (enable_link NUMBER(5, 0) NOT NULL default 1);
ALTER TABLE tlayout_data ADD (id_metaconsole NUMBER(10, 0) default 0 NOT NULL);

-- -----------------------------------------------------
-- Table tagente_modulo
-- -----------------------------------------------------
ALTER TABLE tagente_modulo ADD (critical_instructions VARCHAR2(255) default '');
ALTER TABLE tagente_modulo ADD (warning_instructions VARCHAR2(255) default '');
ALTER TABLE tagente_modulo ADD (unknown_instructions VARCHAR2(255) default '');
ALTER TABLE tagente_modulo ADD (critical_inverse NUMBER(1, 0) default 0 NOT NULL);
ALTER TABLE tagente_modulo ADD (warning_inverse NUMBER(1, 0) default 0 NOT NULL);
ALTER TABLE tagente_modulo ADD (id_category NUMBER(10, 0) default 0);

-- -----------------------------------------------------
-- Table tnetwork_component
-- -----------------------------------------------------
ALTER TABLE tnetwork_component ADD (critical_instructions VARCHAR2(255) default '');
ALTER TABLE tnetwork_component ADD (warning_instructions VARCHAR2(255) default '');
ALTER TABLE tnetwork_component ADD (unknown_instructions VARCHAR2(255) default '');
ALTER TABLE tnetwork_component ADD (critical_inverse NUMBER(1, 0) default 0 NOT NULL);
ALTER TABLE tnetwork_component ADD (warning_inverse NUMBER(1, 0) default 0 NOT NULL);
ALTER TABLE tnetwork_component ADD (max_retries NUMBER(10, 0) default 0);
ALTER TABLE tnetwork_component ADD (tags VARCHAR2(255) default '');

------------------------------------------------------------------------
-- Table tnetwork_map
------------------------------------------------------------------------
ALTER TABLE tnetwork_map ADD (text_filter VARCHAR(100) DEFAULT '');
ALTER TABLE tnetwork_map ADD (dont_show_subgroups NUMBER(10, 0) default 0 NOT NULL);
ALTER TABLE tnetwork_map ADD (pandoras_children NUMBER(10, 0) default 0 NOT NULL);
ALTER TABLE tnetwork_map ADD (show_modules NUMBER(10, 0) default 0 NOT NULL);
ALTER TABLE tnetwork_map ADD (show_groups NUMBER(10, 0) default 0 NOT NULL);
ALTER TABLE tnetwork_map ADD (id_agent NUMBER(10, 0) default 0 NOT NULL);
ALTER TABLE tnetwork_map ADD (server_name VARCHAR(100)  NOT NULL);
ALTER TABLE tnetwork_map ADD (show_modulegroup NUMBER(10, 0) default 0 NOT NULL);

------------------------------------------------------------------------
-- Table tagente_estado
------------------------------------------------------------------------
ALTER TABLE tagente_estado ADD (last_known_status  NUMBER(10, 0) default 0 NOT NULL);
ALTER TABLE tagente_estado ADD (last_error  NUMBER(10, 0) default 0 NOT NULL);

-- -----------------------------------------------------
-- Table tevent_response
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS tevent_response (
	id  NUMBER(10) NOT NULL PRIMARY KEY,
	name varchar2(600) NOT NULL default '',
	description CLOB,
	target CLOB,
	type varchar2(60) NOT NULL,
	id_group MEDIUMINT(4) NOT NULL default 0,
	modal_width NUMBER(10, 0) NOT NULL DEFAULT 0,
	modal_height NUMBER(10, 0) NOT NULL DEFAULT 0,
	new_window NUMBER(10, 0) NOT NULL DEFAULT 0,
	params CLOB
);
CREATE SEQUENCE tevent_response_s INCREMENT BY 1 START WITH 1;

-- ----------------------------------------------------------------------
-- Table talert_actions
-- ----------------------------------------------------------------------
ALTER TABLE talert_actions ADD (field4 CLOB NOT NULL;
ALTER TABLE talert_actions ADD (field5 CLOB NOT NULL;
ALTER TABLE talert_actions ADD (field6 CLOB NOT NULL;
ALTER TABLE talert_actions ADD (field7 CLOB NOT NULL;
ALTER TABLE talert_actions ADD (field8 CLOB NOT NULL;
ALTER TABLE talert_actions ADD (field9 CLOB NOT NULL;
ALTER TABLE talert_actions ADD (field10 CLOB NOT NULL;

-- ----------------------------------------------------------------------
-- Table talert_templates
-- ----------------------------------------------------------------------
ALTER TABLE talert_templates ADD (field4 CLOB default '');
ALTER TABLE talert_templates ADD (field5 CLOB default '');
ALTER TABLE talert_templates ADD (field6 CLOB default '');
ALTER TABLE talert_templates ADD (field7 CLOB default '');
ALTER TABLE talert_templates ADD (field8 CLOB default '');
ALTER TABLE talert_templates ADD (field9 CLOB default '');
ALTER TABLE talert_templates ADD (field10 CLOB default '');
ALTER TABLE talert_templates ADD (field4_recovery CLOB default '');
ALTER TABLE talert_templates ADD (field5_recovery CLOB default '');
ALTER TABLE talert_templates ADD (field6_recovery CLOB default '');
ALTER TABLE talert_templates ADD (field7_recovery CLOB default '');
ALTER TABLE talert_templates ADD (field8_recovery CLOB default '');
ALTER TABLE talert_templates ADD (field9_recovery CLOB default '');
ALTER TABLE talert_templates ADD (field10_recovery CLOB default '');

-- ----------------------------------------------------------------------
-- Table talert_commands
-- ----------------------------------------------------------------------
ALTER TABLE talert_commands ADD (fields_descriptions CLOB default '');
ALTER TABLE talert_commands ADD (fields_values CLOB default '');

-- ---------------------------------------------------------------------
-- Table "tcategory"
-- ---------------------------------------------------------------------
CREATE TABLE tcategory ( 
	id NUMBER(10, 0) NOT NULL PRIMARY KEY, 
	name VARCHAR2(600) default '' NOT NULL
); 

-- ----------------------------------------------------------------------
-- Table tperfil
-- ----------------------------------------------------------------------
ALTER TABLE tperfil ADD (report_view NUMBER(5, 0) default 0 NOT NULL);
ALTER TABLE tperfil ADD (report_edit NUMBER(5, 0) default 0 NOT NULL);
ALTER TABLE tperfil ADD (report_management NUMBER(5, 0) default 0 NOT NULL);
ALTER TABLE tperfil ADD (event_view NUMBER(5, 0) default 0 NOT NULL);
ALTER TABLE tperfil ADD (event_edit NUMBER(5, 0) default 0 NOT NULL);
ALTER TABLE tperfil ADD (event_management NUMBER(5, 0) default 0 NOT NULL);

UPDATE tperfil SET report_view= 1, event_view= 1 WHERE id_perfil = 1 AND name = 'Operator&#x20;&#40;Read&#41;';
UPDATE tperfil SET report_view= 1, report_edit= 1, event_view= 1, event_edit= 1 WHERE id_perfil = 2 AND name = 'Operator&#x20;&#40;Write&#41;';
UPDATE tperfil SET report_view= 1, report_edit= 1, report_management= 1, event_view= 1, event_edit= 1 WHERE id_perfil = 3 AND name = 'Chief&#x20;Operator';
UPDATE tperfil SET report_view= 1, report_edit= 1, report_management= 1, event_view= 1, event_edit= 1, event_management= 1 WHERE id_perfil = 4 AND name = 'Group&#x20;coordinator';
UPDATE tperfil SET report_view= 1, report_edit= 1, report_management= 1, event_view= 1, event_edit= 1, event_management= 1 WHERE id_perfil = 5 AND name = 'Pandora&#x20;Administrator';

-- ---------------------------------------------------------------------
-- Table `tusuario_perfil`
-- ---------------------------------------------------------------------
ALTER TABLE tusuario_perfil ADD (tags CLOB NOT NULL default '');

-- ---------------------------------------------------------------------
-- Table `ttag`
-- ---------------------------------------------------------------------
ALTER TABLE ttag ADD (email CLOB NULL);