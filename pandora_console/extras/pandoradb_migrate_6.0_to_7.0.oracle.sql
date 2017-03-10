-- -----------------------------------------------------
-- Table "tlocal_component"
-- -----------------------------------------------------
-- tlocal_component is a repository of local modules for
-- physical agents on Windows / Unix physical agents
CREATE TABLE tlocal_component (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name CLOB NOT NULL,
	data CLOB NOT NULL,
	description VARCHAR2(1024) default NULL,
	id_os NUMBER(10, 0) default 0 NOT NULL,
	os_version VARCHAR2(100) default '',
	id_network_component_group NUMBER(10, 0) default 0 NOT NULL REFERENCES tnetwork_component_group(id_sg) ON DELETE CASCADE,
	type NUMBER(6, 0) default 6 NOT NULL,
	max NUMBER(19, 0) default 0 NOT NULL,
	min NUMBER(19, 0) default 0 NOT NULL,
	module_interval NUMBER(10, 0) default 0 NULL,
	id_module_group NUMBER(10, 0) default 0 NOT NULL,
	history_data NUMBER(5, 0) default 1 NOT NULL,
	min_warning DOUBLE PRECISION default 0 NOT NULL,
	max_warning DOUBLE PRECISION default 0 NOT NULL,
	str_warning CLOB default '',
	min_critical DOUBLE PRECISION default 0 NOT NULL,
	max_critical DOUBLE PRECISION default 0 NOT NULL,
	str_critical CLOB default '',
	min_ff_event NUMBER(10, 0) default 0 NOT NULL,
	post_process DOUBLE PRECISION default 0 NOT NULL,
	unit CLOB default '',
	wizard_level VARCHAR2(100) default 'nowizard' NOT NULL,
	macros CLOB default '',
	critical_instructions VARCHAR2(255) default '',
	warning_instructions VARCHAR2(255) default '',
	unknown_instructions VARCHAR2(255) default '',
	critical_inverse NUMBER(1, 0) default 0 NOT NULL,
	warning_inverse NUMBER(1, 0) default 0 NOT NULL,
	id_category NUMBER(10, 0) default 0 NOT NULL,
	tags CLOB default '',
	disabled_types_event CLOB default '',
	min_ff_event_normal INTEGER default 0,
	min_ff_event_warning INTEGER default 0,
	min_ff_event_critical INTEGER default 0,
	each_ff NUMBER(1, 0) default 0,
	ff_timeout INTEGER default 0,
	dynamic_interval INTEGER default 0,
	dynamic_max INTEGER default 0,
	dynamic_min INTEGER default 0,
	dynamic_next INTEGER default 0 NOT NULL,
	dynamic_two_tailed NUMBER(1, 0) default 0,
	prediction_sample_window INTEGER default 0,
	prediction_samples INTEGER default 0,
	prediction_threshold INTEGER default 0,
	CONSTRAINT t_local_component_wizard_cons CHECK (wizard_level IN ('basic','advanced','nowizard'))
);

CREATE SEQUENCE tlocal_component_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tlocal_component_inc BEFORE INSERT ON tlocal_component REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tlocal_component_s.nextval INTO :NEW.ID FROM dual; END tlocal_component_inc;;
-- on update trigger
CREATE OR REPLACE TRIGGER tlocal_component_update AFTER UPDATE OF ID_SG ON tnetwork_component_group FOR EACH ROW BEGIN UPDATE tlocal_component SET ID_NETWORK_COMPONENT_GROUP = :NEW.ID_SG WHERE ID_NETWORK_COMPONENT_GROUP = :OLD.ID_SG; END;;


-- -----------------------------------------------------
-- Table "tpolicy_modules"
-- -----------------------------------------------------
CREATE TABLE tpolicy_modules (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_policy NUMBER(10, 0) default 0 NOT NULL,
	configuration_data CLOB default '',
	id_tipo_modulo NUMBER(5, 0) default 0 NOT NULL,
	description VARCHAR2(1024) default '',
	name VARCHAR2(200) default '' NOT NULL,
	unit CLOB default '',
	max NUMBER(19, 0) default 0 NOT NULL,
	min NUMBER(19, 0) default 0 NOT NULL,
	module_interval NUMBER(10, 0) default 0 NOT NULL,
	tcp_port NUMBER(10, 0) default 0 NOT NULL,
	tcp_send CLOB default '',
	tcp_rcv CLOB default '',
	snmp_community VARCHAR2(100) default '',
	snmp_oid VARCHAR2(255) default '0',
	id_module_group NUMBER(10, 0) default 0 NOT NULL,
	flag NUMBER(5, 0) default 0 NOT NULL,
	id_module NUMBER(10, 0) default 0 NOT NULL,
	disabled NUMBER(5, 0) default 0 NOT NULL,
	id_export NUMBER(5, 0) default 0 NOT NULL,
	plugin_user CLOB default '',
	plugin_pass CLOB default '',
	plugin_parameter CLOB,
	id_plugin NUMBER(10, 0) default 0 NOT NULL,
	post_process BINARY_DOUBLE default NULL,
	prediction_module NUMBER(19, 0) default 0 NOT NULL,
	max_timeout NUMBER(10, 0) default 0 NOT NULL,
	max_retries NUMBER(10, 0) default 0 NOT NULL,
	custom_id VARCHAR2(255) default '',
	history_data NUMBER(5, 0) default 1 NOT NULL,
	min_warning BINARY_DOUBLE default 0,
	max_warning BINARY_DOUBLE default 0,
	str_warning CLOB default '',
	min_critical BINARY_DOUBLE default 0,
	max_critical BINARY_DOUBLE default 0,
	str_critical CLOB default '',
	min_ff_event NUMBER(10, 0) default 0 NOT NULL,
	custom_string_1 CLOB default '',
	custom_string_2 CLOB default '',
	custom_string_3 CLOB default '',
	custom_integer_1 NUMBER(10, 0) default 0 NOT NULL,
	custom_integer_2 NUMBER(10, 0) default 0 NOT NULL,
	pending_delete NUMBER(5, 0) default 0 NOT NULL,
	critical_instructions CLOB default '',
	warning_instructions CLOB default '',
	unknown_instructions CLOB default '',
	critical_inverse NUMBER(1, 0) default 0 NOT NULL,
	warning_inverse NUMBER(1, 0) default 0 NOT NULL,
	id_category NUMBER(10, 0) default 0 NOT NULL,
	module_ff_interval NUMBER(19, 0) default 0 NOT NULL,
	quiet NUMBER(5, 0) default 0 NOT NULL,
	cron_interval VARCHAR2(100) DEFAULT '',
	macros CLOB default '',
	disabled_types_event CLOB default '',
	module_macros CLOB default '',
	min_ff_event_normal INTEGER default 0,
	min_ff_event_warning INTEGER default 0,
	min_ff_event_critical INTEGER default 0,
	each_ff NUMBER(1, 0) default 0,
	ff_timeout INTEGER default 0,
	dynamic_interval INTEGER default 0,
	dynamic_max INTEGER default 0,
	dynamic_min INTEGER default 0,
	dynamic_next INTEGER NOT NULL default 0,
	dynamic_two_tailed INTEGER default 0,
	prediction_sample_window INTEGER default 0,
	prediction_samples INTEGER default 0,
	prediction_threshold INTEGER default 0
);
CREATE UNIQUE INDEX tpolicy_modules_id_pol_na_idx ON tpolicy_modules(id_policy, name);
CREATE INDEX tpolicy_modules_id_policy_idx ON tpolicy_modules(id_policy);

CREATE SEQUENCE tpolicy_modules_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpolicy_modules_inc BEFORE INSERT ON tpolicy_modules REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpolicy_modules_s.nextval INTO :NEW.ID FROM dual; END tpolicy_modules_inc;;


-- -----------------------------------------------------
-- Table "tpolicies"
-- -----------------------------------------------------
-- 'status' could be 0 (without changes, updated), 1 (needy update only database) or 2 (needy update database and conf files)
CREATE TABLE tpolicies (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(255) NOT NULL,
	description VARCHAR2(255) default '',
	id_group NUMBER(10, 0) default 0 NOT NULL,
	status NUMBER(10, 0) default 0 NOT NULL
);

CREATE SEQUENCE tpolicies_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpolicies_inc BEFORE INSERT ON tpolicies REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpolicies_s.nextval INTO :NEW.ID FROM dual; END tpolicies_inc;;


-- -----------------------------------------------------
-- Table "tpolicy_alerts"
-- -----------------------------------------------------
CREATE TABLE tpolicy_alerts (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_policy NUMBER(10, 0) default 0 NOT NULL REFERENCES tpolicies(id) ON DELETE CASCADE,
	id_policy_module NUMBER(10, 0) default 0 NOT NULL,
	id_alert_template NUMBER(10, 0) default 0 NOT NULL REFERENCES talert_templates(id) ON DELETE CASCADE,
	name_extern_module VARCHAR2(300) default '',
	disabled NUMBER(5, 0) default 0 NOT NULL,
	standby NUMBER(5, 0) default 0 NOT NULL,
	pending_delete NUMBER(5, 0) default 0 NOT NULL
);

CREATE SEQUENCE tpolicy_alerts_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpolicy_alerts_inc BEFORE INSERT ON tpolicy_alerts REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpolicy_alerts_s.nextval INTO :NEW.ID FROM dual; END tpolicy_alerts_inc;;
-- on update trigger
CREATE OR REPLACE TRIGGER tpolicy_alerts_update AFTER UPDATE OF ID ON tpolicies FOR EACH ROW BEGIN UPDATE tpolicy_alerts SET ID_POLICY = :NEW.ID WHERE ID_POLICY = :OLD.ID; END;;
-- on update trigger 1
CREATE OR REPLACE TRIGGER tpolicy_alerts_update1 AFTER UPDATE OF ID ON talert_templates FOR EACH ROW BEGIN UPDATE tpolicy_alerts SET ID_ALERT_TEMPLATE = :NEW.ID WHERE ID_ALERT_TEMPLATE = :OLD.ID; END;;


-- -----------------------------------------------------
-- Table "tpolicy_agents"
-- -----------------------------------------------------
CREATE TABLE tpolicy_agents (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_policy NUMBER(10, 0) default 0 NOT NULL,
	id_agent NUMBER(10, 0) default 0 NOT NULL,
	policy_applied NUMBER(5, 0) default 0 NOT NULL,
	pending_delete NUMBER(5, 0) default 0 NOT NULL,
	last_apply_utimestamp NUMBER(10, 0) default 0 NOT NULL
);
CREATE UNIQUE INDEX tpolicy_agents_id_po_id_ag_idx ON tpolicy_agents(id_policy, id_agent);

CREATE SEQUENCE tpolicy_agents_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpolicy_agents_inc BEFORE INSERT ON tpolicy_agents REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpolicy_agents_s.nextval INTO :NEW.ID FROM dual; END tpolicy_agents_inc;;


-- -----------------------------------------------------
-- Table "tdashboard"
-- -----------------------------------------------------
CREATE TABLE tdashboard (
	id NUMBER(19, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(60) default '' NOT NULL,
	id_user VARCHAR2(60) default '' NOT NULL,
	id_group NUMBER(10, 0) default 0 NOT NULL,
	active NUMBER(5, 0) default 0 NOT NULL,
	cells CLOB default ''
);

CREATE SEQUENCE tdashboard_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tdashboard_inc BEFORE INSERT ON tdashboard REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tdashboard_s.nextval INTO :NEW.ID FROM dual; END tdashboard_inc;;


-- -----------------------------------------------------
-- Table "twidget"
-- -----------------------------------------------------
CREATE TABLE twidget (
	id NUMBER(19, 0) NOT NULL PRIMARY KEY,
	class_name VARCHAR2(60) default '' NOT NULL,
	unique_name VARCHAR2(60) default '' NOT NULL,
	description CLOB default '' NOT NULL,
	options CLOB default '' NOT NULL,
	page VARCHAR2(120) default '' NOT NULL
);

CREATE SEQUENCE twidget_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER twidget_inc BEFORE INSERT ON twidget REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT twidget_s.nextval INTO :NEW.ID FROM dual; END twidget_inc;;


-- -----------------------------------------------------
-- Table "twidget_dashboard"
-- -----------------------------------------------------
CREATE TABLE twidget_dashboard (
	id NUMBER(19, 0) NOT NULL PRIMARY KEY,
	options CLOB default '',
	"order" NUMBER(10, 0) default 0 NOT NULL,
	id_dashboard NUMBER(19, 0) default 0 NOT NULL REFERENCES tdashboard(id) ON DELETE CASCADE,
	id_widget NUMBER(19, 0) default 0 NOT NULL,
	prop_width DOUBLE PRECISION default 0.32 NOT NULL,
	prop_height DOUBLE PRECISION default 0.32 NOT NULL
);

CREATE SEQUENCE twidget_dashboard_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER twidget_dashboard_inc BEFORE INSERT ON twidget_dashboard REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT twidget_dashboard_s.nextval INTO :NEW.ID FROM dual; END twidget_dashboard_inc;;
-- on update trigger
CREATE OR REPLACE TRIGGER twidget_dashboard_update AFTER UPDATE OF ID ON tdashboard FOR EACH ROW BEGIN UPDATE twidget_dashboard SET ID_DASHBOARD = :NEW.ID WHERE ID_DASHBOARD = :OLD.ID; END;;
-- on update trigger 1
CREATE OR REPLACE TRIGGER twidget_dashboard_update1 AFTER UPDATE OF ID ON twidget FOR EACH ROW BEGIN UPDATE twidget_dashboard SET ID_WIDGET = :NEW.ID WHERE ID_WIDGET = :OLD.ID; END;;


-- -----------------------------------------------------
-- Table "tmodule_inventory"
-- -----------------------------------------------------
CREATE TABLE tmodule_inventory (
	id_module_inventory NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_os NUMBER(10, 0) default NULL REFERENCES tconfig_os(id_os) ON DELETE CASCADE,
	name VARCHAR2(100) default '',
	description VARCHAR2(100) default '',
	interpreter VARCHAR2(100) default '',
	data_format VARCHAR2(100) default '',
	code CLOB default '',
	block_mode NUMBER(3, 0) default 0 NOT NULL
);

CREATE SEQUENCE tmodule_inventory_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmodule_inventory_inc BEFORE INSERT ON tmodule_inventory REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmodule_inventory_s.nextval INTO :NEW.ID_MODULE_INVENTORY FROM dual; END tmodule_inventory_inc;;


-- -----------------------------------------------------
-- Table "tagente_datos_inventory"
-- -----------------------------------------------------
CREATE TABLE tagente_datos_inventory (
	id_agent_module_inventory NUMBER (10, 0) NOT NULL,
	data CLOB default '',
	utimestamp NUMBER(10, 0) default 0 NOT NULL,
	timestamp TIMESTAMP default NULL
);
CREATE INDEX tagente_datos_inventory_id ON tagente_datos_inventory(id_agent_module_inventory);
CREATE INDEX tagente_datos_inventory_ut ON tagente_datos_inventory(utimestamp);

-- This sequence will not work with the 'insert_id' procedure

-- on update trigger
CREATE OR REPLACE TRIGGER tmodule_inventory_update AFTER UPDATE OF ID_OS ON tconfig_os FOR EACH ROW BEGIN UPDATE tmodule_inventory SET ID_OS = :NEW.ID_OS WHERE ID_OS = :OLD.ID_OS; END;;


-- -----------------------------------------------------
-- Table "tagent_module_inventory"
-- -----------------------------------------------------
CREATE TABLE tagent_module_inventory (
	id_agent_module_inventory NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_agente NUMBER(10, 0) NOT NULL REFERENCES tagente(id_agente) ON DELETE CASCADE,
	id_module_inventory NUMBER(10, 0) NOT NULL REFERENCES tmodule_inventory(id_module_inventory) ON DELETE CASCADE,
	target VARCHAR2(100) default '',
	"interval" NUMBER(10, 0) default 3600 NOT NULL,
	username VARCHAR2(100) default '',
	password VARCHAR2(100) default '',
	data CLOB default '',
	timestamp TIMESTAMP default NULL,
	utimestamp NUMBER(19, 0) default 0 NOT NULL,
	flag NUMBER(5, 0) default 1 NOT NULL,
	id_policy_module_inventory NUMBER(10, 0) default 0 NOT NULL
);

CREATE SEQUENCE tagent_module_inventory_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tagent_module_inventory_inc BEFORE INSERT ON tagent_module_inventory REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagent_module_inventory_s.nextval INTO :NEW.ID_AGENT_MODULE_INVENTORY FROM dual; END tagent_module_inventory_inc;;
-- on update trigger
CREATE OR REPLACE TRIGGER tagent_module_inventory_update AFTER UPDATE OF ID_AGENTE ON tagente FOR EACH ROW BEGIN UPDATE tagent_module_inventory SET ID_AGENTE = :NEW.ID_AGENTE WHERE ID_AGENTE = :OLD.ID_AGENTE; END;;
-- on update trigger 1
CREATE OR REPLACE TRIGGER tagent_module_inventor_update1 AFTER UPDATE OF ID_MODULE_INVENTORY ON tmodule_inventory FOR EACH ROW BEGIN UPDATE tagent_module_inventory SET ID_MODULE_INVENTORY = :NEW.ID_MODULE_INVENTORY WHERE ID_MODULE_INVENTORY = :OLD.ID_MODULE_INVENTORY; END;;


-- -----------------------------------------------------
-- Table "ttrap_custom_values"
-- -----------------------------------------------------
CREATE TABLE ttrap_custom_values (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	oid VARCHAR2(255) default '' NOT NULL,
	custom_oid VARCHAR2(255) default '' NOT NULL,
	text VARCHAR2(255) default '',
	description VARCHAR2(255) default '',
	severity NUMBER(10, 0) default 2 NOT NULL,
	CONSTRAINT oid_custom_oid UNIQUE(oid, custom_oid)
);

CREATE SEQUENCE ttrap_custom_values_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER ttrap_custom_values_inc BEFORE INSERT ON ttrap_custom_values REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT ttrap_custom_values_s.nextval INTO :NEW.ID FROM dual; END ttrap_custom_values_inc;;


-- -----------------------------------------------------
-- Table "tmetaconsole_setup"
-- -----------------------------------------------------
--Table to store metaconsole sources
CREATE TABLE tmetaconsole_setup (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	server_name VARCHAR2(1000) default '',
	server_url CLOB default '',
	dbuser CLOB default '',
	dbpass CLOB default '',
	dbhost CLOB default '',
	dbport CLOB default '',
	dbname CLOB default '',
	auth_token CLOB default '',
	id_group NUMBER(10, 0) default 0 NOT NULL,
	api_password CLOB default '',
	disabled NUMBER(10, 0) default 0,
	last_event_replication NUMBER(19, 0) default 0 NOT NULL
);

CREATE SEQUENCE tmetaconsole_setup_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmetaconsole_setup_inc BEFORE INSERT ON tmetaconsole_setup REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmetaconsole_setup_s.nextval INTO :NEW.ID FROM dual; END tmetaconsole_setup_inc;;


-- -----------------------------------------------------
-- Table "tprofile_view"
-- -----------------------------------------------------
--Table to define by each profile defined in Pandora, to which sec/page has access independently of its ACL (for showing in the console or not). By default have access to all pages allowed by ACL, if forbidden here, then pages are not shown.
CREATE TABLE tprofile_view (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_profile NUMBER(10, 0) default 0 NOT NULL,
	sec CLOB default '',
	sec2 CLOB default '',
	sec3 CLOB default ''
);

CREATE SEQUENCE tprofile_view_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tprofile_view_inc BEFORE INSERT ON tprofile_view REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tprofile_view_s.nextval INTO :NEW.ID FROM dual; END tprofile_view_inc;;


-- -----------------------------------------------------
-- Table "tservice"
-- -----------------------------------------------------
--Table to define services to monitor
CREATE TABLE tservice (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '' NOT NULL,
	description CLOB default '' NOT NULL,
	id_group NUMBER(10, 0) default 0 NOT NULL,
	critical BINARY_DOUBLE default 0 NOT NULL,
	warning BINARY_DOUBLE default 0 NOT NULL,
	service_interval BINARY_DOUBLE default 0 NOT NULL,
	service_value BINARY_DOUBLE default 0 NOT NULL,
	status NUMBER(10, 0) default -1 NOT NULL,
	utimestamp NUMBER(10, 0) default 0 NOT NULL,
	auto_calculate NUMBER(10, 0) default 1 NOT NULL,
	id_agent_module NUMBER(10, 0) default 0 NOT NULL,
	sla_interval DOUBLE PRECISION default 0 NOT NULL,
	sla_id_module NUMBER(10, 0) default 0 NOT NULL,
	sla_value_id_module NUMBER(10, 0) default 0 NOT NULL,
	sla_limit DOUBLE PRECISION default 100 NOT NULL,
	id_template_alert_warning NUMBER(10, 0) default 0 NOT NULL,
	id_template_alert_critical NUMBER(10, 0) default 0 NOT NULL,
	id_template_alert_unknown NUMBER(10, 0) default 0 NOT NULL,
	id_template_alert_critical_sla NUMBER(10, 0) default 0 NOT NULL
);

CREATE SEQUENCE tservice_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tservice_inc BEFORE INSERT ON tservice REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tservice_s.nextval INTO :NEW.ID FROM dual; END tservice_inc;;


-- -----------------------------------------------------
-- Table "tservice_element"
-- -----------------------------------------------------
--Table to define the modules and the weights of the modules that define a service
CREATE TABLE tservice_element (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_service NUMBER(10, 0) NOT NULL,
	weight_ok BINARY_DOUBLE default 0 NOT NULL,
	weight_warning BINARY_DOUBLE default 0 NOT NULL,
	weight_critical DOUBLE PRECISION default 0 NOT NULL,
	weight_unknown DOUBLE PRECISION default 0 NOT NULL,
	description CLOB default '',
	id_agente_modulo NUMBER(10, 0) default 0 NOT NULL,
	id_agent NUMBER(10, 0) default 0 NOT NULL,
	id_service_child NUMBER(10, 0) default 0 NOT NULL,
	id_server_meta NUMBER(10, 0) default 0 NOT NULL
);

CREATE SEQUENCE tservice_element_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tservice_element_inc BEFORE INSERT ON tservice_element REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tservice_element_s.nextval INTO :NEW.ID FROM dual; END tservice_element_inc;;


-- -----------------------------------------------------
-- Table "tcollection"
-- -----------------------------------------------------
CREATE TABLE tcollection (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '' NOT NULL,
	short_name VARCHAR2(100) default '' NOT NULL,
	id_group NUMBER(10, 0) default 0 NOT NULL,
	description CLOB,
	status NUMBER(10, 0) default 0 NOT NULL
);
-- status: 0 - Not apply
-- status: 1 - Applied

CREATE SEQUENCE tcollection_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tcollection_inc BEFORE INSERT ON tcollection REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tcollection_s.nextval INTO :NEW.ID FROM dual; END tcollection_inc;;


-- -----------------------------------------------------
-- Table "tpolicy_collections"
-- -----------------------------------------------------
CREATE TABLE tpolicy_collections (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_policy NUMBER(10, 0) default 0 NOT NULL REFERENCES tpolicies (id) ON DELETE CASCADE,
	id_collection NUMBER(10, 0) default 0 NOT NULL REFERENCES tcollection (id) ON DELETE CASCADE,
	pending_delete NUMBER(5, 0) default 0 NOT NULL
);

CREATE SEQUENCE tpolicy_collections_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpolicy_collections_inc BEFORE INSERT ON tpolicy_collections REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpolicy_collections_s.nextval INTO :NEW.ID FROM dual; END tpolicy_collections_inc;;
-- on update trigger
CREATE OR REPLACE TRIGGER tpolicy_collections_update AFTER UPDATE OF ID ON tpolicies FOR EACH ROW BEGIN UPDATE tpolicy_collections SET ID_POLICY = :NEW.ID WHERE ID_POLICY = :OLD.ID; END;;
-- on update trigger 1
CREATE OR REPLACE TRIGGER tpolicy_collections_update1 AFTER UPDATE OF ID ON tcollection FOR EACH ROW BEGIN UPDATE tpolicy_collections SET ID_COLLECTION = :NEW.ID WHERE ID_COLLECTION = :OLD.ID; END;;


-- -----------------------------------------------------
-- Table "tpolicy_alerts_actions"
-- -----------------------------------------------------
CREATE TABLE tpolicy_alerts_actions (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_policy_alert NUMBER(10, 0) default 0 NOT NULL REFERENCES tpolicy_alerts (id) ON DELETE CASCADE,
	id_alert_action NUMBER(10, 0) default 0 NOT NULL REFERENCES talert_actions (id) ON DELETE CASCADE,
	fires_min NUMBER(10, 0) default 0,
	fires_max NUMBER(10, 0) default 0
);

CREATE SEQUENCE tpolicy_alerts_actions_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpolicy_alerts_actions_inc BEFORE INSERT ON tpolicy_alerts_actions REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpolicy_alerts_actions_s.nextval INTO :NEW.ID FROM dual; END tpolicy_alerts_actions_inc;;
-- on update trigger
CREATE OR REPLACE TRIGGER tpolicy_alerts_actions_update AFTER UPDATE OF ID ON tpolicy_alerts FOR EACH ROW BEGIN UPDATE tpolicy_alerts_actions SET ID_POLICY_ALERT = :NEW.ID WHERE ID_POLICY_ALERT = :OLD.ID; END;;
-- on update trigger 1
CREATE OR REPLACE TRIGGER tpolicy_alerts_actions_update1 AFTER UPDATE OF ID ON talert_actions FOR EACH ROW BEGIN UPDATE tpolicy_alerts_actions SET ID_ALERT_ACTION = :NEW.ID WHERE ID_ALERT_ACTION = :OLD.ID; END;;

-- -----------------------------------------------------
-- Table "tpolicy_plugins"
-- -----------------------------------------------------
CREATE TABLE tpolicy_plugins (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_policy NUMBER(10, 0) default 0 NOT NULL,
	plugin_exec CLOB default '',
	pending_delete NUMBER(5, 0) default 0 NOT NULL
);

CREATE SEQUENCE tpolicy_plugins_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpolicy_plugins_inc BEFORE INSERT ON tpolicy_plugins REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpolicy_plugins_s.nextval INTO :NEW.ID FROM dual; END tpolicy_plugins_inc;;


-- -----------------------------------------------------
-- Table "tsesion_extended"
-- -----------------------------------------------------
CREATE TABLE tsesion_extended (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_sesion NUMBER(10, 0) NOT NULL,
	extended_info CLOB default '',
	hash VARCHAR2(255) default ''
);

CREATE SEQUENCE tsesion_extended_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tsesion_extended_inc BEFORE INSERT ON tsesion_extended REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tsesion_extended_s.nextval INTO :NEW.ID FROM dual; END tsesion_extended_inc;;


-- -----------------------------------------------------
-- Table `tskin`
-- -----------------------------------------------------
CREATE TABLE tskin (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name CLOB DEFAULT '' NOT NULL,
	relative_path CLOB DEFAULT '' NOT NULL,
	description CLOB DEFAULT '' NOT NULL,
	disabled NUMBER(10,0) default 0 NOT NULL
);

CREATE SEQUENCE tskin_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tskin_inc BEFORE INSERT ON tskin REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tskin_s.nextval INTO :NEW.ID FROM dual; END tskin_inc;;


-- -----------------------------------------------------
-- Table `tpolicy_queue`
-- -----------------------------------------------------
CREATE TABLE tpolicy_queue (
	id NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_policy NUMBER(10, 0) default 0 NOT NULL,
	id_agent NUMBER(10, 0) default 0 NOT NULL,
	operation VARCHAR2(15) default '' NOT NULL,
	progress NUMBER(10, 0) default 0 NOT NULL,
	end_utimestamp NUMBER(10, 0) default 0 NOT NULL,
	priority NUMBER(10, 0) default 0 NOT NULL
);

CREATE SEQUENCE tpolicy_queue_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpolicy_queue_inc BEFORE INSERT ON tpolicy_queue REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpolicy_queue_s.nextval INTO :NEW.ID FROM dual; END tpolicy_queue_inc;;


-- -----------------------------------------------------
-- Table `tmodule_synth`
-- -----------------------------------------------------
CREATE TABLE tmodule_synth (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_agent_module_source NUMBER(10, 0) default 0 NOT NULL,
	id_agent_module_target NUMBER(10, 0) default 0 NOT NULL REFERENCES tagente_modulo(id_agente_modulo) ON DELETE CASCADE,
	fixed_value BINARY_DOUBLE default NULL,
	operation VARCHAR2(20) default 'NOP',
	"order" NUMBER(11, 0) default 0 NOT NULL,
	CONSTRAINT t_module_synth_operation_cons CHECK (operation IN ('ADD', 'SUB', 'DIV', 'MUL', 'AVG', 'NOP'))
);

CREATE SEQUENCE tmodule_synth_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmodule_synth_inc BEFORE INSERT ON tmodule_synth REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmodule_synth_s.nextval INTO :NEW.ID FROM dual; END tmodule_synth_inc;;


-- -----------------------------------------------------
-- Table `tevent_rule`
-- -----------------------------------------------------
CREATE TABLE tevent_rule (
	id_event_rule NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_event_alert NUMBER(10, 0) NOT NULL,
	operation VARCHAR2(20) NOT NULL,
	"order" NUMBER(10, 0) default '0',
	window NUMBER(10, 0) default '0' NOT NULL,
	count NUMBER(10, 0) default '1' NOT NULL,
	agent CLOB default '',
	id_usuario VARCHAR2(100) default '' NOT NULL,
	id_grupo NUMBER(10, 0) default '0' NOT NULL,
	evento CLOB default '' NOT NULL,
	event_type VARCHAR2(50) default 'unknown',
	module CLOB default '',
	alert CLOB default '',
	criticity NUMBER(10, 0) default '0' NOT NULL,
	user_comment CLOB NOT NULL,
	id_tag NUMBER(10, 0) default '0' NOT NULL,
	name CLOB default '',
	CONSTRAINT t_event_rule_operation_cons CHECK (operation IN ('NOP', 'AND','OR','XOR','NAND','NOR','NXOR')),
	CONSTRAINT t_event_rule_event_type CHECK (event_type IN ('','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal'))
);
CREATE INDEX tevent_rule_id_event_alert_idx ON tevent_rule(id_event_alert);

CREATE SEQUENCE tevent_rule_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tevent_rule_inc BEFORE INSERT ON tevent_rule REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tevent_rule_s.nextval INTO :NEW.ID_EVENT_RULE FROM dual; END tevent_rule_inc;;


-- -----------------------------------------------------
-- Table `tevent_alert`
-- -----------------------------------------------------
-- use to_char(time_from, 'hh24:mi:ss') function to retrieve time_from field info
-- use to_char(time_to,   'hh24:mi:ss') function to retrieve time_to field info
CREATE TABLE tevent_alert (
	id NUMBER(10, 0)  NOT NULL PRIMARY KEY,
	name CLOB default '',
	description CLOB,
	"order" NUMBER(10, 0) default 0,
	"mode" VARCHAR2(20),
	field1 CLOB default '' NOT NULL,
	field2 CLOB default '' NOT NULL,
	field3 CLOB default '' NOT NULL,
	field4 CLOB default '' NOT NULL,
	field5 CLOB default '' NOT NULL,
	field6 CLOB default '' NOT NULL,
	field7 CLOB default '' NOT NULL,
	field8 CLOB default '' NOT NULL,
	field9 CLOB default '' NOT NULL,
	field10 CLOB default '' NOT NULL,
	time_threshold NUMBER(10, 0) default 0 NOT NULL,
	max_alerts NUMBER(10, 0) default 1 NOT NULL,
	min_alerts NUMBER(10, 0) default 0 NOT NULL,
	time_from TIMESTAMP default to_date('00:00:00','hh24:mi:ss'),
	time_to TIMESTAMP default to_date('00:00:00','hh24:mi:ss'),
	monday NUMBER(10, 0) default 1,
	tuesday NUMBER(10, 0) default 1,
	wednesday NUMBER(10, 0) default 1,
	thursday NUMBER(10, 0) default 1,
	friday NUMBER(10, 0) default 1,
	saturday NUMBER(10, 0) default 1,
	sunday NUMBER(10, 0) default 1,
	recovery_notify NUMBER(10, 0) default '0',
	field2_recovery CLOB default '' NOT NULL,
	field3_recovery CLOB NOT NULL,
	id_group NUMBER(10, 0) default 0 NULL,
	internal_counter NUMBER(10, 0) default 0,
	last_fired NUMBER(19, 0) default 0 NOT NULL,
	last_reference NUMBER(19, 0) default 0 NOT NULL,
	times_fired NUMBER(10, 0) default 0 NOT NULL,
	disabled NUMBER(10, 0) default 0,
	standby NUMBER(10, 0) default 0,
	priority NUMBER(10, 0) default 0,
	force_execution NUMBER(10, 0) default 0,
	"group_by" VARCHAR2(20) default '',
	CONSTRAINT t_event_alert_group_by CHECK ("group_by" IN ('','id_agente','id_agentmodule','id_alert_am','id_grupo')),
	CONSTRAINT t_event_alert_mode_cons CHECK ("mode" IN ('PASS','DROP'))
);

CREATE SEQUENCE tevent_alert_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tevent_alert_inc BEFORE INSERT ON tevent_alert REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tevent_alert_s.nextval INTO :NEW.ID FROM dual; END tevent_alert_inc;;


-- -----------------------------------------------------
-- Table `tevent_alert_action`
-- -----------------------------------------------------
CREATE TABLE tevent_alert_action (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_event_alert NUMBER(10, 0) NOT NULL REFERENCES tevent_alert(id)  ON DELETE CASCADE,
	id_alert_action NUMBER(10, 0) NOT NULL REFERENCES talert_actions(id)  ON DELETE CASCADE,
	fires_min NUMBER(10, 0) default 0,
	fires_max NUMBER(10, 0) default 0,
	module_action_threshold NUMBER(10, 0) default 0 NOT NULL,
	last_execution NUMBER(19, 0) default 0 NOT NULL
);

CREATE SEQUENCE tevent_alert_action_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tevent_alert_action_inc BEFORE INSERT ON tevent_alert_action REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tevent_alert_action_s.nextval INTO :NEW.ID FROM dual; END tevent_alert_action_inc;;

-- on update trigger
CREATE OR REPLACE TRIGGER tevent_alert_action_update AFTER UPDATE OF ID ON tevent_alert FOR EACH ROW BEGIN UPDATE tevent_alert_action SET ID_EVENT_ALERT = :NEW.ID WHERE ID_EVENT_ALERT = :OLD.ID; END;;
-- on update trigger 1
CREATE OR REPLACE TRIGGER tevent_alert_action_update1 AFTER UPDATE OF ID ON talert_actions FOR EACH ROW BEGIN UPDATE tevent_alert_action SET ID_ALERT_ACTION = :NEW.ID WHERE ID_ALERT_ACTION = :OLD.ID; END;;


-- -----------------------------------------------------
-- Table `tpolicy_modules_inventory`
-- -----------------------------------------------------
CREATE TABLE tpolicy_modules_inventory (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_policy NUMBER(10, 0) NOT NULL REFERENCES tpolicies(id) ON DELETE CASCADE,
	id_module_inventory NUMBER(10, 0) NOT NULL REFERENCES tmodule_inventory(id_module_inventory) ON DELETE CASCADE,
	interval NUMBER(10, 0) default 3600 NOT NULL,
	username VARCHAR2(100) default '',
	password VARCHAR2(100) default '',
	pending_delete NUMBER(5, 0) default 0 NOT NULL
);

CREATE SEQUENCE tpolicy_modules_inventory_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpolicy_modules_inventory_inc BEFORE INSERT ON tpolicy_modules_inventory REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpolicy_modules_inventory_s.nextval INTO :NEW.ID FROM dual; END tpolicy_modules_inventory_inc;;


-- -----------------------------------------------------
-- Table `tnetworkmap_enterprise`
-- -----------------------------------------------------
CREATE TABLE tnetworkmap_enterprise (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(500) default '',
	id_group NUMBER(10, 0) default 0 NOT NULL,
	options CLOB default ''
);

CREATE SEQUENCE tnetworkmap_enterprise_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetworkmap_enterprise_inc BEFORE INSERT ON tnetworkmap_enterprise REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetworkmap_enterprise_s.nextval INTO :NEW.ID FROM dual; END tnetworkmap_enterprise_inc;;


-- -----------------------------------------------------
-- Table `tnetworkmap_enterprise_nodes`
-- -----------------------------------------------------
CREATE TABLE tnetworkmap_enterprise_nodes (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_networkmap_enterprise NUMBER(10, 0) NOT NULL REFERENCES tnetworkmap_enterprise(id) ON DELETE CASCADE,
	x NUMBER(10, 0) default 0 NOT NULL,
	y NUMBER(10, 0) default 0 NOT NULL,
	z NUMBER(10, 0) default 0  NOT NULL,
	id_agent NUMBER(10, 0) default 0 NOT NULL,
	id_module NUMBER(10, 0) default 0 NOT NULL,
	id_agent_module NUMBER(10, 0) default 0 NOT NULL,
	parent NUMBER(10, 0) default 0,
	options CLOB default '',
	deleted NUMBER(10, 0) default 0 NOT NULL,
	state CLOB default ''
);

CREATE SEQUENCE tnetworkmap_enterprise_nodes_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetworkmap_enter_nod_inc BEFORE INSERT ON tnetworkmap_enterprise_nodes REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetworkmap_enterprise_nodes_s.nextval INTO :NEW.ID FROM dual; END tnetworkmap_enter_nod_inc;;
-- on update trigger
CREATE OR REPLACE TRIGGER tnetworkmap_enter_nod_update AFTER UPDATE OF ID ON tnetworkmap_enterprise FOR EACH ROW BEGIN UPDATE tnetworkmap_enterprise_nodes SET ID_NETWORKMAP_ENTERPRISE = :NEW.ID WHERE ID_NETWORKMAP_ENTERPRISE = :OLD.ID; END;;


-- -----------------------------------------------------
-- Table `tnetworkmap_ent_rel_nodes` (Before `tnetworkmap_enterprise_relation_nodes`)
-- -----------------------------------------------------
CREATE TABLE tnetworkmap_ent_rel_nodes (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_networkmap_enterprise NUMBER(10, 0) NOT NULL REFERENCES tnetworkmap_enterprise(id) ON DELETE CASCADE,
	parent NUMBER(10, 0) default 0,
	parent_type VARCHAR2(30) default 'node',
	child NUMBER(10, 0) default 0,
	child_type VARCHAR2(30) default 'node',
	deleted NUMBER(10, 0) default 0 NOT NULL
);

CREATE SEQUENCE tnetworkmap_ent_rel_nodes_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetworkmap_ent_rel_nodes_inc BEFORE INSERT ON tnetworkmap_ent_rel_nodes REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetworkmap_ent_rel_nodes_s.nextval INTO :NEW.ID FROM dual; END tnetworkmap_ent_rel_nodes_inc;;
-- on update trigger
CREATE OR REPLACE TRIGGER tnetworkmap_enter_rel_nod_upd AFTER UPDATE OF ID ON tnetworkmap_enterprise FOR EACH ROW BEGIN UPDATE tnetworkmap_ent_rel_nodes SET ID_NETWORKMAP_ENTERPRISE = :NEW.ID WHERE ID_NETWORKMAP_ENTERPRISE = :OLD.ID; END;;


-- -----------------------------------------------------
-- Table `treport_template`
-- -----------------------------------------------------
CREATE TABLE treport_template (
	id_report NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_user varchar2(100) default '' NOT NULL,
	name varchar2(150) default '' NOT NULL,
	description CLOB NOT NULL,
	private NUMBER(10, 0) default 0 NOT NULL,
	id_group NUMBER(10, 0) default NULL NULL,
	custom_logo varchar2(200) default NULL,
	header CLOB default NULL,
	first_page CLOB default NULL,
	footer CLOB default NULL,
	custom_font varchar2(200) default NULL,
	metaconsole NUMBER(5, 0) DEFAULT 0
);

CREATE SEQUENCE treport_template_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER treport_template_inc BEFORE INSERT ON treport_template REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_template_s.nextval INTO :NEW.ID_REPORT FROM dual; END treport_template_inc;;


-- -----------------------------------------------------
-- Table `treport_content_template`
-- -----------------------------------------------------
CREATE TABLE treport_content_template (
	id_rc NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_report NUMBER(10, 0) default 0 NOT NULL REFERENCES treport_template(id_report) ON DELETE CASCADE,
	id_gs NUMBER(10, 0) default NULL NULL,
	text_agent_module CLOB default NULL,
	type varchar2(30) default 'simple_graph',
	period NUMBER(10, 0) default 0 NOT NULL,
	"order" NUMBER(10, 0) default 0 NOT NULL,
	description CLOB,
	text_agent CLOB default '',
	text CLOB default NULL,
	external_source CLOB default NULL,
	treport_custom_sql_id NUMBER(10, 0) default 0,
	header_definition CLOB default NULL,
	column_separator CLOB default NULL,
	line_separator CLOB default NULL,
	time_from TIMESTAMP default to_date('00:00:00','hh24:mi:ss'),
	time_to TIMESTAMP default to_date('00:00:00','hh24:mi:ss'),
	monday NUMBER(5, 0) default 1 NOT NULL,
	tuesday NUMBER(5, 0) default 1 NOT NULL,
	wednesday NUMBER(5, 0) default 1 NOT NULL,
	thursday NUMBER(5, 0) default 1 NOT NULL,
	friday NUMBER(5, 0) default 1 NOT NULL,
	saturday NUMBER(5, 0) default 1 NOT NULL,
	sunday NUMBER(5, 0) default 1 NOT NULL,
	only_display_wrong NUMBER(5, 0) default 0 not null,
	top_n NUMBER(10, 0) default 0 NOT NULL,
	top_n_value NUMBER(10, 0) default 10 NOT NULL,
	exception_condition NUMBER(10, 0) default 0 NOT NULL,
	exception_condition_value DOUBLE PRECISION default 0 NOT NULL,
	show_resume DOUBLE PRECISION default 0 NOT NULL,
	order_uptodown DOUBLE PRECISION default 0 NOT NULL,
	show_graph DOUBLE PRECISION default 0 NOT NULL,
	group_by_agent DOUBLE PRECISION default 0 NOT NULL,
	style CLOB DEFAULT '' NOT NULL,
	id_group NUMBER(10, 0) DEFAULT 0 NOT NULL,
	id_module_group NUMBER(10, 0) DEFAULT 0 NOT NULL,
	server_name CLOB default '',
	exact_match NUMBER(10, 0) default 0,
	module_names CLOB default NULL,
	module_free_text CLOB default NULL,
	each_agent NUMBER(5, 0) default 1 NOT NULL
);

CREATE SEQUENCE treport_content_template_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER treport_content_template_inc BEFORE INSERT ON treport_content_template REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_content_template_s.nextval INTO :NEW.ID_RC FROM dual; END treport_content_template_inc;;


-- -----------------------------------------------------
-- Table `treport_content_sla_com_temp` (treport_content_sla_combined_template)
-- -----------------------------------------------------
CREATE TABLE treport_content_sla_com_temp (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_report_content NUMBER(10, 0) NOT NULL REFERENCES treport_content_template(id_rc) ON DELETE CASCADE,
	text_agent CLOB default '',
	text_agent_module CLOB default '',
	sla_max DOUBLE PRECISION default 0 NOT NULL,
	sla_min DOUBLE PRECISION default 0 NOT NULL,
	sla_limit DOUBLE PRECISION default 0 NOT NULL,
	server_name CLOB default '',
	exact_match NUMBER(5, 0) default 0
);

CREATE SEQUENCE treport_content_sla_com_temp_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER treport_content_sla_c_t_inc BEFORE INSERT ON treport_content_sla_com_temp REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_content_sla_com_temp_s.nextval INTO :NEW.ID FROM dual; END treport_content_sla_c_t_inc;;


-- -----------------------------------------------------
-- Table `treport_content_item_temp` (treport_content_item_template)
-- -----------------------------------------------------
CREATE TABLE treport_content_item_temp (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_report_content NUMBER(10, 0) NOT NULL REFERENCES treport_content_template(id_rc) ON DELETE CASCADE,
	text_agent CLOB default '',
	text_agent_module CLOB default '',
	server_name CLOB default '',
	exact_match NUMBER(5, 0) default 0,
	operation CLOB
);

CREATE SEQUENCE treport_content_item_temp_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER treport_content_item_temp_inc BEFORE INSERT ON treport_content_item_temp REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_content_item_temp_s.nextval INTO :NEW.ID FROM dual; END treport_content_item_temp_inc;;


-- -----------------------------------------------------
-- Table `tgraph_template`
-- -----------------------------------------------------
CREATE TABLE tgraph_template (
	id_graph_template NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_user VARCHAR2(255) NOT NULL,
	name VARCHAR2(255) NOT NULL,
	description CLOB default '',
	period NUMBER(11, 0) default 0 NOT NULL,
	width SMALLINT default 0 NOT NULL,
	height SMALLINT default 0 NOT NULL,
	private SMALLINT default 0 NOT NULL,
	events SMALLINT default 0 NOT NULL,
	stacked SMALLINT default 0 NOT NULL,
	id_group NUMBER(8, 0) default 0
);

CREATE SEQUENCE tgraph_template_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tgraph_template_inc BEFORE INSERT ON tgraph_template REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgraph_template_s.nextval INTO :NEW.ID_GRAPH_TEMPLATE FROM dual; END tgraph_template_inc;;


-- ---------------------------------------------------------------------
-- Table `tgraph_source_template`
-- ---------------------------------------------------------------------
CREATE TABLE tgraph_source_template (
	id_gs_template NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_template NUMBER(10, 0) default 0 NOT NULL,
	agent VARCHAR2(255),
	module VARCHAR2(255),
	weight FLOAT(5) DEFAULT 2 NOT NULL,
	exact_match SMALLINT default 0 NOT NULL
);

CREATE SEQUENCE tgraph_source_template_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tgraph_source_template_inc BEFORE INSERT ON tgraph_source_template REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgraph_source_template_s.nextval INTO :NEW.ID_GS_TEMPLATE FROM dual; END tgraph_source_template_inc;;


-- ---------------------------------------------------------------------
-- Table `textension_translate_string`
-- ---------------------------------------------------------------------
CREATE TABLE textension_translate_string (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	lang VARCHAR2(50) NOT NULL,
	string VARCHAR2(4000) NOT NULL,
	translation VARCHAR2(4000) NOT NULL
);

CREATE SEQUENCE textension_translate_string_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER textension_translate_str_inc BEFORE INSERT ON textension_translate_string REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT textension_translate_string_s.nextval INTO :NEW.ID FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_event`
-- ---------------------------------------------------------------------
-- use to_char(timestamp, 'hh24:mi:ss') function to retrieve timestamp field info
CREATE TABLE tmetaconsole_event (
	id_evento NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_source_event NUMBER(19, 0) NOT NULL,
	id_agente NUMBER(10, 0) DEFAULT 0,
	agent_name VARCHAR2(600) DEFAULT '',
	id_usuario VARCHAR2(100) DEFAULT '0',
	id_grupo NUMBER(10, 0) DEFAULT 0,
	group_name CLOB DEFAULT '',
	estado NUMBER(10, 0) DEFAULT 0,
	timestamp TIMESTAMP DEFAULT NULL,
	evento CLOB DEFAULT '',
	utimestamp NUMBER(19, 0) DEFAULT 0,
	event_type VARCHAR2(50) DEFAULT 'unknown',
	id_agentmodule NUMBER(10, 0) DEFAULT 0,
	module_name CLOB DEFAULT '',
	id_alert_am NUMBER(10, 0) DEFAULT 0,
	alert_template_name CLOB DEFAULT '',
	criticity NUMBER(10, 0) DEFAULT 0,
	user_comment CLOB,
	tags CLOB,
	source VARCHAR2(100) DEFAULT '',
	id_extra VARCHAR2(100) DEFAULT '',
	critical_instructions CLOB DEFAULT '',
	warning_instructions CLOB DEFAULT '',
	unknown_instructions CLOB DEFAULT '',
	owner_user VARCHAR2(100) DEFAULT '0',
	ack_utimestamp NUMBER(19, 0) DEFAULT 0,
	server_id NUMBER(10, 0) DEFAULT 0 NOT NULL,
	custom_data CLOB DEFAULT '',
	CONSTRAINT tmetaconsole_e_event_type_cons CHECK (event_type IN ('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change'))
);
CREATE INDEX tmetaconsole_e_id_1_idx ON tmetaconsole_event(id_agente, id_evento);
CREATE INDEX tmetaconsole_e_id_am_idx ON tmetaconsole_event(id_agentmodule);
CREATE INDEX tmetaconsole_e_id_server_idx ON tmetaconsole_event(server_id);
CREATE INDEX tmetaconsole_e_id_grupo_idx ON tmetaconsole_event(id_grupo);
CREATE INDEX tmetaconsole_e_criticity_idx ON tmetaconsole_event(criticity);
CREATE INDEX tmetaconsole_e_estado_idx ON tmetaconsole_event(estado);

CREATE SEQUENCE tmetaconsole_event_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmetaconsole_event_inc BEFORE INSERT ON tmetaconsole_event REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmetaconsole_event_s.nextval INTO :NEW.ID_EVENTO FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_event_history`
-- ---------------------------------------------------------------------
-- use to_char(timestamp, 'hh24:mi:ss') function to retrieve timestamp field info
CREATE TABLE tmetaconsole_event_history (
	id_evento NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_source_event NUMBER(19, 0) NOT NULL,
	id_agente NUMBER(10, 0) default 0 NOT NULL,
	agent_name VARCHAR2(600) default '',
	id_usuario VARCHAR2(100) default '0' NOT NULL,
	id_grupo NUMBER(10, 0) default 0 NOT NULL,
	group_name CLOB default '',
	estado NUMBER(10, 0) default 0 NOT NULL,
	timestamp TIMESTAMP default NULL,
	evento CLOB default '',
	utimestamp NUMBER(19, 0) default 0 NOT NULL,
	event_type VARCHAR2(50) default 'unknown',
	id_agentmodule NUMBER(10, 0) default 0 NOT NULL,
	module_name CLOB default '',
	id_alert_am NUMBER(10, 0) default 0 NOT NULL,
	alert_template_name CLOB default '',
	criticity NUMBER(10, 0) default 0 NOT NULL,
	user_comment CLOB,
	tags CLOB,
	source VARCHAR2(100) default '' NOT NULL,
	id_extra VARCHAR2(100) default '' NOT NULL,
	critical_instructions VARCHAR2(255) default '',
	warning_instructions VARCHAR2(255) default '',
	unknown_instructions VARCHAR2(255) default '',
	owner_user VARCHAR2(100) default '0' NOT NULL,
	ack_utimestamp NUMBER(19, 0) default 0 NOT NULL,
	server_id NUMBER(10, 0) default 0 NOT NULL,
	custom_data CLOB default '',
	CONSTRAINT tmeta_eh_event_type_cons CHECK (event_type IN ('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change'))
);
CREATE INDEX tmetaconsole_eh_id_1_idx ON tmetaconsole_event_history(id_agente, id_evento);
CREATE INDEX tmetaconsole_eh_id_am_idx ON tmetaconsole_event_history(id_agentmodule);
CREATE INDEX tmetaconsole_eh_id_server_idx ON tmetaconsole_event_history(server_id);
CREATE INDEX tmetaconsole_eh_id_grupo_idx ON tmetaconsole_event_history(id_grupo);
CREATE INDEX tmetaconsole_eh_criticity_idx ON tmetaconsole_event_history(criticity);
CREATE INDEX tmetaconsole_eh_estado_idx ON tmetaconsole_event_history(estado);

CREATE SEQUENCE tmetaconsole_event_h_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmetaconsole_event_h_inc BEFORE INSERT ON tmetaconsole_event_history REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmetaconsole_event_h_s.nextval INTO :NEW.ID_EVENTO FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tagent_module_log`
-- ---------------------------------------------------------------------
CREATE TABLE tagent_module_log (
  id_agent_module_log NUMBER(10, 0) NOT NULL PRIMARY KEY,
  id_agent NUMBER(10, 0) NOT NULL REFERENCES tagente(id_agente) ON DELETE CASCADE,
  source VARCHAR2(4000) NOT NULL,
  timestamp TIMESTAMP NULL,
  utimestamp NUMBER(10, 0) default 0 NOT NULL
);

CREATE SEQUENCE tagent_module_log_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tagent_module_log_inc BEFORE INSERT ON tagent_module_log REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagent_module_log_s.nextval INTO :NEW.ID_AGENT_MODULE_LOG FROM dual; END tagent_module_log_inc;;

-- ---------------------------------------------------------------------
-- Table `tevent_custom_field`
-- ---------------------------------------------------------------------
CREATE TABLE tevent_custom_field (
	id_group NUMBER(4, 0) default 0 NOT NULL PRIMARY KEY,
	value VARCHAR2(255) default '' NOT NULL
);

-- This sequence will not work with the 'insert_id' procedure

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent`
-- ---------------------------------------------------------------------
CREATE TABLE tmetaconsole_agent (
	id_agente NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_tagente NUMBER(10, 0) NOT NULL,
	id_tmetaconsole_setup NUMBER(10) NOT NULL REFERENCES tmetaconsole_setup(id) ON DELETE CASCADE,
	nombre VARCHAR2(600) default '' NOT NULL,
	direccion VARCHAR2(100) default '' NULL,
	comentarios VARCHAR2(255) default '' NULL,
	id_grupo NUMBER(10, 0) default 0 NOT NULL,
	ultimo_contacto TIMESTAMP NOT NULL,
	modo NUMBER(1, 0) default 0 NOT NULL,
	intervalo NUMBER(11, 0) default 300 NOT NULL,
	id_os NUMBER(10, 0) default 0 NOT NULL,
	os_version VARCHAR2(100) default '',
	agent_version VARCHAR2(100) default '',
	ultimo_contacto_remoto TIMESTAMP default NULL,
	disabled NUMBER(1, 0) default 0 NOT NULL,
	remote NUMBER(1) default 0 NOT NULL,
	id_parent NUMBER(10, 0) default 0 NOT NULL,
	custom_id VARCHAR2(255) default '',
	server_name VARCHAR2(100) default '',
	cascade_protection NUMBER(1, 0) default 0 NOT NULL,
	cascade_protection_module NUMBER(10, 0) default 0 NOT NULL,
	-- Number of hours of diference with the server timezone
	timezone_offset NUMBER(2, 2) default 0 NULL,
	-- Path in the server to the image of the icon representing the agent
	icon_path VARCHAR2(127) default NULL,
	-- Set it to one to update the position data (altitude, longitude, latitude) when getting information from the agent or to 0 to keep the last value and do not update it
	update_gis_data NUMBER(1) default 1 NOT NULL,
	url_address CLOB NULL,
	quiet NUMBER(1) default 0 NOT NULL,
	normal_count NUMBER(20, 0) default 0 NOT NULL,
	warning_count NUMBER(20, 0) default 0 NOT NULL,
	critical_count NUMBER(20, 0) default 0 NOT NULL,
	unknown_count NUMBER(20, 0) default 0 NOT NULL,
	notinit_count NUMBER(20, 0) default 0 NOT NULL,
	total_count NUMBER(20, 0) default 0 NOT NULL,
	fired_count NUMBER(20, 0) default 0 NOT NULL,
	update_module_count NUMBER(1, 0) default 0 NOT NULL,
	update_alert_count NUMBER(1, 0) default 0 NOT NULL,
	alias varchar2(600) NOT NULL default '',
	transactional_agent NUMBER(1) default 0 NOT NULL
);
CREATE INDEX tmetaconsole_agent_nombre_idx ON tmetaconsole_agent(nombre);
CREATE INDEX tmetaconsole_agent_dir_idx ON tmetaconsole_agent(direccion);
CREATE INDEX tmetaconsole_agent_dis_idx ON tmetaconsole_agent(disabled);
CREATE INDEX tmetaconsole_agent_id_g_idx ON tmetaconsole_agent(id_grupo);

CREATE SEQUENCE tmetaconsole_agent_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmetaconsole_agent_inc BEFORE INSERT ON tmetaconsole_agent REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmetaconsole_agent_s.nextval INTO :NEW.ID_AGENTE FROM dual; END tmetaconsole_agent_inc;;

-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------

ALTER TABLE talert_templates ADD COLUMN min_alerts_reset_counter NUMBER(5, 0) DEFAULT 0;
ALTER TABLE talert_templates ADD COLUMN field11 CLOB DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field12 CLOB DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field13 CLOB DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field14 CLOB DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field15 CLOB DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field11_recovery CLOB DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field12_recovery CLOB DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field13_recovery CLOB DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field14_recovery CLOB DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN field15_recovery CLOB DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE talert_snmp ADD COLUMN al_field11 CLOB DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN al_field12 CLOB DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN al_field13 CLOB DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN al_field14 CLOB DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN al_field15 CLOB DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `talert_snmp_action`
-- ---------------------------------------------------------------------
ALTER TABLE talert_snmp_action ADD COLUMN al_field11 CLOB DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN al_field12 CLOB DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN al_field13 CLOB DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN al_field14 CLOB DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN al_field15 CLOB DEFAULT "";

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
ALTER TABLE talert_actions ADD COLUMN field11 CLOB DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field12 CLOB DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field13 CLOB DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field14 CLOB DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field15 CLOB DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field11_recovery CLOB DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field12_recovery CLOB DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field13_recovery CLOB DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field14_recovery CLOB DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN field15_recovery CLOB DEFAULT "";

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
INSERT INTO tconfig (token, value) VALUES ('MR', 0);
UPDATE tconfig SET value = 'https://licensing.artica.es/pandoraupdate7/server.php' WHERE token='url_update_manager';
DELETE FROM tconfig WHERE token = 'current_package_enterprise';
INSERT INTO tconfig (token, value) VALUES ('current_package_enterprise', 700);

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
ALTER TABLE tusuario ADD COLUMN id_filter int(10) unsigned default NULL;
ALTER TABLE tusuario ADD COLUMN CONSTRAINT fk_id_filter FOREIGN KEY (id_filter) REFERENCES tevent_filter(id_filter) ON DELETE SET NULL;
ALTER TABLE tusuario ADD COLUMN session_time INTEGER NOT NULL default '0';

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
ALTER TABLE tagente ADD cascade_protection_module int(10) unsigned default '0';
ALTER TABLE tagente ADD COLUMN (alias VARCHAR2(600) not null DEFAULT '');

UPDATE `tagente` SET tagente.alias = tagente.nombre;
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
-- Table `tagent_custom_fields`
-- ---------------------------------------------------------------------
INSERT INTO tagent_custom_fields (name) VALUES ('eHorusID');

-- ---------------------------------------------------------------------
-- Table `tgraph`
-- ---------------------------------------------------------------------
ALTER TABLE tgraph ADD COLUMN percentil int(4) unsigned default '0';

-- ---------------------------------------------------------------------
-- Table `tnetflow_filter`
-- ---------------------------------------------------------------------
ALTER TABLE tnetflow_filter ADD COLUMN router_ip CLOB DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `tlocal_component`
-- ---------------------------------------------------------------------
ALTER TABLE tlocal_component ADD dynamic_interval INTEGER default 0;
ALTER TABLE tlocal_component ADD dynamic_max INTEGER default 0;
ALTER TABLE tlocal_component ADD dynamic_min INTEGER default 0;
ALTER TABLE tlocal_component ADD dynamic_next INTEGER default 0 NOT NULL;
ALTER TABLE tlocal_component ADD dynamic_two_tailed NUMBER(1, 0) default 0;

-- ---------------------------------------------------------------------
-- Table `tpolicy_module`
-- ---------------------------------------------------------------------
ALTER TABLE tpolicy_modules ADD dynamic_interval INTEGER default 0;
ALTER TABLE tpolicy_modules ADD dynamic_max INTEGER default 0;
ALTER TABLE tpolicy_modules ADD dynamic_min INTEGER default 0;
ALTER TABLE tpolicy_modules ADD dynamic_next INTEGER default 0 NOT NULL;
ALTER TABLE tpolicy_modules ADD dynamic_two_tailed NUMBER(1, 0) default 0;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent`
-- ---------------------------------------------------------------------
ALTER TABLE tmetaconsole_agent ADD remote INTEGER default 0 NOT NULL;
ALTER TABLE tmetaconsole_agent ADD cascade_protection_module NUMBER(10, 0) default 0 NOT NULL;
ALTER TABLE tmetaconsole_agent ADD transactional_agent INTEGER default 0 NOT NULL;
ALTER TABLE tmetaconsole_agent ADD COLUMN (alias VARCHAR2(600) not null DEFAULT '');

UPDATE `tmetaconsole_agent` SET tagente.alias = tagente.nombre;
