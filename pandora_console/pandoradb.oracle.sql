-- Pandora FMS - the Flexible Monitoring System
-- ============================================
-- Copyright (c) 2005-2011 Artica Soluciones Tecnológicas, http://www.artica.es
-- Please see http://pandora.sourceforge.net for full contribution list

-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License
-- as published by the Free Software Foundation for version 2.
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

-- PLEASE NO NOT USE MULTILINE COMMENTS 
-- Because Pandora Installer don't understand them
-- and fails creating database !!!
-- -----------------------------------------------------------
-- Pandora FMS official tables for 4.0 version              --
-- -----------------------------------------------------------

-- Pandora schema creation script
-- Triggers must end with two semicolons because Pandora installer need it 

CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP (oracletime IN DATE DEFAULT SYSDATE) RETURN INTEGER AS unixtime INTEGER; BEGIN unixtime := (oracletime - to_date('19700101','YYYYMMDD')) * 86400; RETURN unixtime; END;;
CREATE OR REPLACE FUNCTION NOW RETURN TIMESTAMP AS t_now TIMESTAMP; BEGIN SELECT LOCALTIMESTAMP INTO t_now FROM dual; RETURN t_now; END;;

CREATE TABLE taddress (
	id_a NUMBER(10, 0) NOT NULL PRIMARY KEY,
	ip VARCHAR(60) default '',
	ip_pack NUMBER(10, 0) default 0 NOT NULL 
);
CREATE INDEX taddress_ip_idx ON taddress(ip);

CREATE SEQUENCE taddress_s INCREMENT BY 1 START WITH 1;

-- Triggers must end with double semicolons because Pandora installer need it 
CREATE OR REPLACE TRIGGER taddress_inc BEFORE INSERT ON taddress REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT taddress_s.nextval INTO :NEW.ID_A FROM dual; END;;

CREATE TABLE taddress_agent (
	id_ag NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_a NUMBER(19, 0) default 0 NOT NULL,
	id_agent NUMBER(19, 0) default 0 NOT NULL 
);

CREATE SEQUENCE taddress_agent_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER taddress_agent_inc BEFORE INSERT ON taddress_agent REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT taddress_agent_s.nextval INTO :NEW.ID_AG FROM dual; END;;

CREATE TABLE tagente (
	id_agente NUMBER(10, 0) NOT NULL PRIMARY KEY,
	nombre VARCHAR2(600) default '',
	direccion VARCHAR2(100) default NULL,
	comentarios VARCHAR2(255) default '',
	id_grupo NUMBER(10, 0) default 0 NOT NULL,
	ultimo_contacto TIMESTAMP default NULL,
	modo NUMBER(5, 0) default 0 NOT NULL,
	intervalo NUMBER(10, 0) default 300 NOT NULL,
	id_os NUMBER(10, 0) default 0,
	os_version VARCHAR2(100) default '',
	agent_version VARCHAR2(100) default '',
	ultimo_contacto_remoto TIMESTAMP default NULL,
	disabled NUMBER(5, 0) default 0 NOT NULL,
	id_parent NUMBER(10, 0) default 0,
	custom_id VARCHAR2(255) default '',
	server_name VARCHAR2(100) default '',
	cascade_protection NUMBER(5, 0) default 0 NOT NULL, 
	--number of hours of diference with the server timezone
	timezone_offset NUMBER(5, 0) DEFAULT 0 NULL,
	 --path in the server to the image of the icon representing the agent
	icon_path VARCHAR2(127) DEFAULT NULL NULL ,
	 --set it to one to update the position data (altitude, longitude, latitude) when getting information from the agent or to 0 to keep the last value and don\'t update it
	update_gis_data NUMBER(5, 0) DEFAULT 1 NOT NULL 
);
CREATE INDEX tagente_nombre_idx ON tagente(nombre);
CREATE INDEX tagente_direccion_idx ON tagente(direccion);
CREATE INDEX tagente_disabled_idx ON tagente(disabled);
CREATE INDEX tagente_id_grupo_idx ON tagente(id_grupo);

CREATE SEQUENCE tagente_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tagente_inc BEFORE INSERT ON tagente REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_s.nextval INTO :NEW.ID_AGENTE FROM dual; END;;

CREATE TABLE tagente_datos (
	id_agente_modulo NUMBER(10, 0) default 0 NOT NULL,
	datos BINARY_DOUBLE default NULL,
	utimestamp NUMBER(10, 0) default 0
);
CREATE INDEX tagente_datos_id_agent_mod_idx ON tagente_datos(id_agente_modulo);
CREATE INDEX tagente_datos_utimestamp_idx ON tagente_datos(utimestamp);

CREATE TABLE tagente_datos_inc (
	id_adi NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_agente_modulo NUMBER(10, 0) default 0 NOT NULL,
	datos BINARY_DOUBLE default NULL,
	utimestamp NUMBER(10, 0) default 0 NOT NULL
);
CREATE INDEX tagente_datos_inc_id_ag_mo_idx ON tagente_datos_inc(id_agente_modulo);

CREATE SEQUENCE tagente_datos_inc_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tagente_datos_inc_inc BEFORE INSERT ON tagente_datos_inc REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_datos_inc_s.nextval INTO :NEW.ID_ADI FROM dual; END;;

CREATE TABLE tagente_datos_string (
	id_agente_modulo NUMBER(10, 0) NOT NULL,
	datos CLOB NOT NULL,
	utimestamp NUMBER(10, 0) default 0 NOT NULL 
);
CREATE INDEX tagente_datos_string_utsta_idx ON tagente_datos_string(utimestamp);

CREATE TABLE tagente_datos_log4x (
	id_tagente_datos_log4x NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_agente_modulo NUMBER(10, 0) default 0 NOT NULL,
	severity CLOB NOT NULL,
	message CLOB NOT NULL,
	stacktrace CLOB NOT NULL,
	utimestamp NUMBER(10, 0) default 0 NOT NULL
);
CREATE INDEX tagente_datos_log4x_id_a_m_idx ON tagente_datos_log4x(id_agente_modulo);

CREATE SEQUENCE tagente_datos_log4x_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tagente_datos_log4x_inc BEFORE INSERT ON tagente_datos_log4x REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_datos_log4x_s.nextval INTO :NEW.ID_TAGENTE_DATOS_LOG4X FROM dual; END;;

CREATE TABLE tagente_estado (
	id_agente_estado NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_agente_modulo NUMBER(10, 0) default 0 NOT NULL,
	datos CLOB default '',
	timestamp TIMESTAMP default NULL,
	estado NUMBER(10, 0) default 0 NOT NULL,
	id_agente NUMBER(10, 0) default 0 NOT NULL,
	last_try TIMESTAMP default NULL,
	utimestamp NUMBER(19, 0) default 0 NOT NULL,
	current_interval NUMBER(10, 0) default 0 NOT NULL,
	running_by NUMBER(10, 0) default 0,
	last_execution_try NUMBER(19, 0) default 0 NOT NULL,
	status_changes NUMBER(10, 0) default 0,
	last_status NUMBER(10, 0) default 0
);
CREATE INDEX tagente_estado_id_agente_idx ON tagente_estado(id_agente);
CREATE INDEX tagente_estado_estado_idx ON tagente_estado(estado);
CREATE INDEX tagente_estado_curr_inter_idx ON tagente_estado(current_interval);
CREATE INDEX tagente_estado_running_by_idx ON tagente_estado(running_by);
CREATE INDEX tagente_estado_last_ex_try_idx ON tagente_estado(last_execution_try);

CREATE SEQUENCE tagente_estado_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tagente_estado_inc BEFORE INSERT ON tagente_estado REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_estado_s.nextval INTO :NEW.ID_AGENTE_ESTADO FROM dual; END;;

-- Probably last_execution_try index is not useful and loads more than benefits

-- id_modulo now uses tmodule 
-- ---------------------------
-- 1 - Data server modules (agent related modules)
-- 2 - Network server modules
-- 4 - Plugin server
-- 5 - Predictive server
-- 6 - WMI server
-- 7 - WEB Server (enteprise)

CREATE TABLE tagente_modulo (
	id_agente_modulo NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_agente NUMBER(10, 0) default 0 NOT NULL,
	id_tipo_modulo NUMBER(10, 0) default 0 NOT NULL,
	descripcion CLOB default '',
	extended_info CLOB default '',
	nombre CLOB default '',
	unit VARCHAR2(100) DEFAULT '',
	id_policy_module NUMBER(10, 0) default 0 NOT NULL,
	max NUMBER(19, 0) default 0 NOT NULL,
	min NUMBER(19, 0) default 0 NOT NULL,
	module_interval NUMBER(10, 0) default 0 NOT NULL,
	tcp_port NUMBER(10, 0) default 0 NOT NULL,
	tcp_send CLOB default '',
	tcp_rcv CLOB default '',
	snmp_community VARCHAR2(100) default '',
	snmp_oid VARCHAR2(255) default '0',
	ip_target VARCHAR2(100) default '',
	id_module_group NUMBER(10, 0) default 0 NOT NULL,
	flag NUMBER(5, 0) default 1 NOT NULL,
	id_modulo NUMBER(10, 0) default 0 NOT NULL,
	disabled NUMBER(5, 0) default 0 NOT NULL,
	id_export NUMBER(10, 0) default 0 NOT NULL,
	plugin_user CLOB default '',
	plugin_pass CLOB default '',
	plugin_parameter CLOB,
	id_plugin NUMBER(10, 0) default 0,
	post_process BINARY_DOUBLE default NULL,
	prediction_module NUMBER(19, 0) default 0,
	max_timeout NUMBER(10, 0) default 0,
	custom_id VARCHAR2(255) default '',
	history_data  NUMBER(5, 0) default 1,
	min_warning BINARY_DOUBLE default 0,
	max_warning BINARY_DOUBLE default 0,
	str_warning CLOB default '',
	min_critical BINARY_DOUBLE default 0,
	max_critical BINARY_DOUBLE default 0,
	str_critical CLOB default '',
	min_ff_event INTEGER default 0,
	delete_pending NUMBER(5, 0) default 0 NOT NULL,
	policy_linked NUMBER(5, 0) default 0 NOT NULL,
	policy_adopted NUMBER(5, 0) default 0 NOT NULL,
	custom_string_1 CLOB default '',
	custom_string_2 CLOB default '',
	custom_string_3 CLOB default '',
	custom_integer_1 NUMBER(10, 0) default 0,
	custom_integer_2 NUMBER(10, 0) default 0
);
CREATE INDEX tagente_modulo_id_agente_idx ON tagente_modulo(id_agente);
CREATE INDEX tagente_modulo_id_t_mod_idx ON tagente_modulo(id_tipo_modulo);
CREATE INDEX tagente_modulo_disabled_idx ON tagente_modulo(disabled);

CREATE SEQUENCE tagente_modulo_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tagente_modulo_inc BEFORE INSERT ON tagente_modulo REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_modulo_s.nextval INTO :NEW.ID_AGENTE_MODULO FROM dual; END;;

-- snmp_oid is also used for WMI query

CREATE TABLE tagent_access (
	id_agent NUMBER(10, 0) default 0 NOT NULL,
	utimestamp NUMBER(19, 0) default 0 NOT NULL
);
CREATE INDEX tagent_access_id_agent_idx ON tagent_access(id_agent);
CREATE INDEX tagent_access_utimestamp_idx ON tagent_access(utimestamp);

CREATE TABLE talert_snmp (
	id_as NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_alert NUMBER(10, 0) default 0 NOT NULL,
	al_field1 CLOB default '',
	al_field2 CLOB default '',
	al_field3 CLOB default '',
	description VARCHAR2(255) default '',
	alert_type NUMBER(5, 0) default 0 NOT NULL,
	agent VARCHAR2(100) default '',
	custom_oid CLOB default '',
	oid VARCHAR2(255) default '',
	time_threshold NUMBER(10, 0) default 0 NOT NULL,
	times_fired NUMBER(5, 0) default 0 NOT NULL,
	last_fired TIMESTAMP default NULL,
	max_alerts NUMBER(10, 0) default 1 NOT NULL,
	min_alerts NUMBER(10, 0) default 1 NOT NULL,
	internal_counter NUMBER(10, 0) default 0 NOT NULL,
	priority NUMBER(10, 0) default 0
);

CREATE SEQUENCE talert_snmp_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_snmp_inc BEFORE INSERT ON talert_snmp REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_snmp_s.nextval INTO :NEW.ID_AS FROM dual; END;;

CREATE TABLE talert_commands (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '',
	command CLOB default '',
	description CLOB default '',
	internal NUMBER(10, 0) default 0
);

CREATE SEQUENCE talert_commands_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_commands_inc BEFORE INSERT ON talert_commands REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_commands_s.nextval INTO :NEW.ID FROM dual; END;;

CREATE TABLE talert_actions (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name CLOB default '',
	id_alert_command NUMBER(10, 0) NOT NULL REFERENCES talert_commands(id)  ON DELETE CASCADE,
	field1 CLOB default '',
	field2 CLOB default '',
	field3 CLOB default '',
	id_group NUMBER(19, 0) default 0 NOT NULL,
	action_threshold NUMBER(19, 0) default 0 NOT NULL
);

CREATE SEQUENCE talert_actions_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_actions_inc BEFORE INSERT ON talert_actions REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_actions_s.nextval INTO :NEW.ID FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_actions_update AFTER UPDATE OF ID ON talert_commands FOR EACH ROW BEGIN UPDATE talert_actions SET ID_ALERT_COMMAND = :NEW.ID WHERE ID_ALERT_COMMAND = :OLD.ID; END;;

-- use to_char(time_from, 'hh24:mi:ss') function to retrieve time_from field info
-- use to_char(time_to,   'hh24:mi:ss') function to retrieve time_to field info
CREATE TABLE talert_templates (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name CLOB default '',
	description CLOB,
	id_alert_action NUMBER(10, 0) REFERENCES talert_actions(id)  ON DELETE SET NULL,
	field1 CLOB default '',
	field2 CLOB default '',
	field3 CLOB NOT NULL,
	type VARCHAR2(50), 
	value VARCHAR2(255) default '',
	matches_value NUMBER(5, 0) default 0,
	max_value DOUBLE PRECISION default NULL,
	min_value DOUBLE PRECISION default NULL,
	time_threshold NUMBER(10, 0) default 0 NOT NULL,
	max_alerts NUMBER(10, 0) default 1 NOT NULL,
	min_alerts NUMBER(10, 0) default 0 NOT NULL,
	time_from TIMESTAMP default to_date('00:00:00','hh24:mi:ss'), 
	time_to TIMESTAMP default to_date('00:00:00','hh24:mi:ss'),   
	monday NUMBER(5, 0) default 1,
	tuesday NUMBER(5, 0) default 1,
	wednesday NUMBER(5, 0) default 1,
	thursday NUMBER(5, 0) default 1,
	friday NUMBER(5, 0) default 1,
	saturday NUMBER(5, 0) default 1,
	sunday NUMBER(5, 0) default 1,
	recovery_notify NUMBER(5, 0) default 0,
	field2_recovery CLOB default '',
	field3_recovery CLOB NOT NULL,
	priority NUMBER(10, 0) default 0 NOT NULL,
	id_group NUMBER(10, 0) default 0 NOT NULL, 
	CONSTRAINT t_alert_templates_type_cons CHECK (type IN ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal', 'warning', 'critical', 'onchange', 'unknown', 'always'))
);
CREATE INDEX talert_templates_id_al_act_idx ON talert_templates(id_alert_action);

CREATE SEQUENCE talert_templates_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_templates_inc BEFORE INSERT ON talert_templates REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_templates_s.nextval INTO :NEW.ID FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_templates_update AFTER UPDATE OF ID ON talert_actions FOR EACH ROW BEGIN UPDATE talert_templates SET ID_ALERT_ACTION = :NEW.ID WHERE ID_ALERT_ACTION = :OLD.ID; END;;

CREATE TABLE talert_template_modules (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_agent_module NUMBER(10, 0) NOT NULL REFERENCES tagente_modulo(id_agente_modulo) ON DELETE CASCADE,
	id_alert_template NUMBER(10, 0) NOT NULL REFERENCES talert_templates(id) ON DELETE CASCADE, 
	id_policy_alerts NUMBER(10, 0) default 0 NOT NULL,
	internal_counter NUMBER(10, 0) default 0,
	last_fired NUMBER(19, 0) default 0 NOT NULL,
	last_reference NUMBER(19, 0) default 0 NOT NULL,
	times_fired NUMBER(10, 0) default 0 NOT NULL,
	disabled NUMBER(5, 0) default 0,
	standby NUMBER(5, 0) default 0,
	priority NUMBER(10, 0) default 0,
	force_execution NUMBER(5, 0) default 0
);
CREATE UNIQUE INDEX talert_template_modules_idx ON talert_template_modules(id_agent_module);

CREATE SEQUENCE talert_template_modules_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_template_modules_inc BEFORE INSERT ON talert_template_modules REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_template_modules_s.nextval INTO :NEW.ID FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_template_modules_update AFTER UPDATE OF ID_AGENTE_MODULO ON tagente_modulo FOR EACH ROW BEGIN UPDATE talert_template_modules SET ID_AGENT_MODULE = :NEW.ID_AGENTE_MODULO WHERE ID_AGENT_MODULE = :OLD.ID_AGENTE_MODULO; END;;

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_template_module_update1 AFTER UPDATE OF ID ON talert_templates FOR EACH ROW BEGIN UPDATE talert_template_modules SET ID_ALERT_TEMPLATE = :NEW.ID WHERE ID_ALERT_TEMPLATE = :OLD.ID; END;;

CREATE TABLE talert_template_module_actions (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_alert_template_module NUMBER(10, 0) NOT NULL REFERENCES talert_template_modules(id) ON DELETE CASCADE, 
	id_alert_action NUMBER(10, 0) NOT NULL REFERENCES talert_actions(id) ON DELETE CASCADE, 
	fires_min NUMBER(10, 0) default 0 NOT NULL,
	fires_max NUMBER(10, 0) default 0 NOT NULL,
	module_action_threshold NUMBER(10, 0) default 0 NOT NULL,
  	last_execution NUMBER(18, 0) default 0 NOT NULL 
);

CREATE SEQUENCE talert_template_modu_actions_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_template_mod_action_inc BEFORE INSERT ON talert_template_module_actions REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_template_modu_actions_s.nextval INTO :NEW.ID FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_template_mod_act_update AFTER UPDATE OF ID ON talert_template_modules FOR EACH ROW BEGIN UPDATE talert_template_module_actions SET ID_ALERT_TEMPLATE_MODULE = :NEW.ID WHERE ID_ALERT_TEMPLATE_MODULE = :OLD.ID; END;;

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_template_mod_ac_update1 AFTER UPDATE OF ID ON talert_actions FOR EACH ROW BEGIN UPDATE talert_template_module_actions SET ID_ALERT_ACTION = :NEW.ID WHERE ID_ALERT_ACTION = :OLD.ID; END;;

-- use to_char(time_from, 'hh24:mi:ss') function to retrieve time_from field info
-- use to_char(time_to,   'hh24:mi:ss') function to retrieve time_to field info
CREATE TABLE talert_compound (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(255) default '',
	description CLOB,
	id_agent NUMBER(10, 0) NOT NULL REFERENCES tagente(id_agente) ON DELETE CASCADE, 
	time_threshold NUMBER(10, 0) default 0 NOT NULL,
	max_alerts NUMBER(10, 0) default 1 NOT NULL,
	min_alerts NUMBER(10, 0) default 0 NOT NULL,
	time_from TIMESTAMP default to_date('00:00:00','hh24:mi:ss'), 
	time_to TIMESTAMP default to_date('00:00:00','hh24:mi:ss'),   
	monday NUMBER(5, 0) default 1,
	tuesday NUMBER(5, 0) default 1,
	wednesday NUMBER(5, 0) default 1,
	thursday NUMBER(5, 0) default 1,
	friday NUMBER(5, 0) default 1,
	saturday NUMBER(5, 0) default 1,
	sunday NUMBER(5, 0) default 1,
	recovery_notify NUMBER(5, 0) default 0,
	field2_recovery VARCHAR2(255) default '',
	field3_recovery CLOB NOT NULL,
	internal_counter NUMBER(10, 0) default 0,
	last_fired NUMBER(19, 0) default 0 NOT NULL,
	last_reference NUMBER(19, 0) default 0 NOT NULL,
	times_fired NUMBER(10, 0) default 0 NOT NULL,
	disabled NUMBER(5, 0) default 0,
	priority NUMBER(5, 0) default 0
);

CREATE SEQUENCE talert_compound_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_compound_inc BEFORE INSERT ON talert_compound REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_compound_s.nextval INTO :NEW.ID FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_compound_update AFTER UPDATE OF ID_AGENTE ON tagente FOR EACH ROW BEGIN UPDATE talert_compound SET ID_AGENT = :NEW.ID_AGENTE WHERE ID_AGENT = :OLD.ID_AGENTE; END;;

CREATE TABLE talert_compound_elements (
	id_alert_compound NUMBER(10, 0) NOT NULL REFERENCES talert_compound(id) ON DELETE CASCADE, 
	id_alert_template_module NUMBER(10, 0) NOT NULL REFERENCES talert_template_modules(id) ON DELETE CASCADE, 
	operation VARCHAR2(10),
	"order" NUMBER(5, 0) default 0,
	CONSTRAINT talert_compound_elements_cons CHECK (operation IN ('NOP', 'AND','OR','XOR','NAND','NOR','NXOR'))
);
CREATE UNIQUE INDEX talert_compound_elements_idx ON talert_compound_elements(id_alert_compound);

-- on update trigger
CREATE OR REPLACE TRIGGER talert_compound_elem_update AFTER UPDATE OF ID ON talert_compound FOR EACH ROW BEGIN UPDATE talert_compound_elements SET ID_ALERT_COMPOUND = :NEW.ID WHERE ID_ALERT_COMPOUND = :OLD.ID; END;;

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_compound_elem_update1 AFTER UPDATE OF ID ON talert_template_modules FOR EACH ROW BEGIN UPDATE talert_compound_elements SET ID_ALERT_TEMPLATE_MODULE = :NEW.ID WHERE ID_ALERT_TEMPLATE_MODULE = :OLD.ID; END;;

CREATE TABLE talert_compound_actions (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_alert_compound NUMBER(10, 0) NOT NULL REFERENCES talert_compound(id) ON DELETE CASCADE,
	id_alert_action NUMBER(10, 0) NOT NULL REFERENCES talert_actions(id) ON DELETE CASCADE, 
	fires_min NUMBER(10, 0) default 0,
	fires_max NUMBER(10, 0) default 0
);

CREATE SEQUENCE talert_compound_actions_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_compound_actions_inc BEFORE INSERT ON talert_compound_actions REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_compound_actions_s.nextval INTO :NEW.ID FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_compound_actions_update AFTER UPDATE OF ID ON talert_compound FOR EACH ROW BEGIN UPDATE talert_compound_actions SET ID_ALERT_COMPOUND = :NEW.ID WHERE ID_ALERT_COMPOUND = :OLD.ID; END;;

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_compound_action_update1 AFTER UPDATE OF ID ON talert_actions FOR EACH ROW BEGIN UPDATE talert_compound_actions SET ID_ALERT_ACTION = :NEW.ID WHERE ID_ALERT_ACTION = :OLD.ID; END;;

-- Priority : 0 - Maintance (grey)
-- Priority : 1 - Low (green)
-- Priority : 2 - Normal (blue)
-- Priority : 3 - Warning (yellow)
-- Priority : 4 - Critical (red)
CREATE TABLE tattachment (
	id_attachment NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_incidencia NUMBER(10, 0) default 0 NOT NULL,
	id_usuario VARCHAR2(60) default '',
	filename VARCHAR2(255) default '',
	description VARCHAR2(150) default '',
	"size" NUMBER(19, 0) default 0 NOT NULL
);

CREATE SEQUENCE tattachment_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tattachment_inc BEFORE INSERT ON tattachment REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tattachment_s.nextval INTO :NEW.ID_ATTACHMENT FROM dual; END;;

CREATE TABLE tconfig (
	id_config NUMBER(10, 0) NOT NULL PRIMARY KEY,
	token VARCHAR2(100) default '',
	value VARCHAR2(100) default '' 
);

CREATE SEQUENCE tconfig_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tconfig_inc BEFORE INSERT ON tconfig REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tconfig_s.nextval INTO :NEW.ID_CONFIG FROM dual; END;;

CREATE TABLE tconfig_os (
	id_os NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '',
	description VARCHAR2(250) default '',
	icon_name VARCHAR2(100) default ''
);

-- use to_char(timestamp, 'hh24:mi:ss') function to retrieve timestamp field info
CREATE TABLE tevento (
	id_evento NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_agente NUMBER(10, 0) default 0 NOT NULL,
	id_usuario VARCHAR2(100) default '0' NOT NULL,
	id_grupo NUMBER(10, 0) default 0 NOT NULL,
	estado NUMBER(10, 0) default 0 NOT NULL,
	timestamp TIMESTAMP default NULL, 			
	evento CLOB default '',
	utimestamp NUMBER(19, 0) default 0 NOT NULL,
	event_type VARCHAR2(50) default 'unknown',
	id_agentmodule NUMBER(10, 0) default 0 NOT NULL,
	id_alert_am NUMBER(10, 0) default 0 NOT NULL,
	criticity NUMBER(10, 0) default 0 NOT NULL,
	user_comment CLOB,
	tags CLOB,
	CONSTRAINT tevento_event_type_cons CHECK (event_type IN ('unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change'))
);
CREATE INDEX tevento_id_1_idx ON tevento(id_agente, id_evento);
CREATE INDEX tevento_id_2_idx ON tevento(utimestamp, id_evento);
CREATE INDEX tevento_id_agentmodule_idx ON tevento(id_agentmodule);

CREATE SEQUENCE tevento_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tevento_inc BEFORE INSERT ON tevento REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tevento_s.nextval INTO :NEW.ID_EVENTO FROM dual; END;;

-- Criticity: 0 - Maintance (grey)
-- Criticity: 1 - Informational (blue)
-- Criticity: 2 - Normal (green) (status 0)
-- Criticity: 3 - Warning (yellow) (status 2)
-- Criticity: 4 - Critical (red) (status 1)
CREATE TABLE tgrupo (
	id_grupo NUMBER(10, 0) NOT NULL PRIMARY KEY,
	nombre CLOB default '',
	icon VARCHAR2(50) default 'world',
	parent NUMBER(10, 0) default 0 NOT NULL,
	propagate NUMBER(5, 0) default 0,
	disabled NUMBER(5, 0) default 0,
	custom_id VARCHAR2(255) default '',
	id_skin NUMBER(10, 0) DEFAULT 0 NOT NULL
);

CREATE SEQUENCE tgrupo_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tgrupo_inc BEFORE INSERT ON tgrupo REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgrupo_s.nextval INTO :NEW.ID_GRUPO FROM dual; END;;

CREATE TABLE tincidencia (
	id_incidencia NUMBER(19, 0) NOT NULL PRIMARY KEY,
	inicio TIMESTAMP default NULL,
	cierre TIMESTAMP default NULL,
	titulo CLOB default '',
	descripcion CLOB NOT NULL,
	id_usuario VARCHAR2(60) default '',
	origen VARCHAR2(100) default '',
	estado NUMBER(10, 0) default 0 NOT NULL,
	prioridad NUMBER(10, 0) default 0 NOT NULL,
	id_grupo NUMBER(10, 0) default 0 NOT NULL,
	actualizacion TIMESTAMP default CURRENT_TIMESTAMP,
	id_creator VARCHAR2(60) default NULL,
	id_lastupdate VARCHAR2(60) default NULL,
	id_agente_modulo NUMBER(19, 0) NOT NULL,
	notify_email NUMBER(10, 0) default 0 NOT NULL
);
CREATE INDEX tincidencia_id_1_idx ON tincidencia(id_usuario,id_incidencia);
CREATE INDEX tincidencia_id_agente_mod_idx ON tincidencia(id_agente_modulo);

--This trigger is for tranlate "on update CURRENT_TIMESTAMP" of MySQL.
CREATE OR REPLACE TRIGGER tincidencia_actualizacion_ts BEFORE UPDATE ON tincidencia FOR EACH ROW BEGIN select CURRENT_TIMESTAMP into :NEW.ACTUALIZACION from dual; END;;

CREATE SEQUENCE tincidencia_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tincidencia_inc BEFORE INSERT ON tincidencia REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tincidencia_s.nextval INTO :NEW.ID_INCIDENCIA FROM dual; END;;

CREATE TABLE tlanguage (
	id_language VARCHAR2(6) default '',
	name VARCHAR2(100) default ''
);

CREATE TABLE tlink (
	id_link NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '',
	link VARCHAR2(255) default ''
);

CREATE SEQUENCE tlink_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tlink_inc BEFORE INSERT ON tlink REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tlink_s.nextval INTO :NEW.ID_LINK FROM dual; END;;

CREATE TABLE tmensajes (
	id_mensaje NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_usuario_origen VARCHAR2(60) default '',
	id_usuario_destino VARCHAR2(60) default '',
	mensaje CLOB NOT NULL,
	timestamp NUMBER(19, 0) default 0 NOT NULL,
	subject VARCHAR2(255) default '',
	estado NUMBER(10, 0) default 0 NOT NULL 
);

CREATE SEQUENCE tmensajes_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tmensajes_inc BEFORE INSERT ON tmensajes REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmensajes_s.nextval INTO :NEW.ID_MENSAJE FROM dual; END;;

CREATE TABLE tmodule_group (
	id_mg NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(150) default ''
);

CREATE SEQUENCE tmodule_group_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tmodule_group_inc BEFORE INSERT ON tmodule_group REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmodule_group_s.nextval INTO :NEW.ID_MG FROM dual; END;;

CREATE TABLE tnetwork_component (
	id_nc NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(80) NOT NULL,
	description VARCHAR2(300) default NULL,
	id_group NUMBER(10, 0) default 1 NOT NULL,
	type NUMBER(10, 0) default 6 NOT NULL,
	max NUMBER(10, 0) default 0 NOT NULL,
	min NUMBER(19, 0) default 0 NOT NULL,
	module_interval NUMBER(19, 0) default 0 NOT NULL,
	tcp_port NUMBER(10, 0) default 0 NOT NULL,
	tcp_send CLOB NOT NULL,
	tcp_rcv CLOB NOT NULL,
	snmp_community VARCHAR2(255) default 'NULL' NOT NULL,
	snmp_oid VARCHAR2(400) NOT NULL,
	id_module_group NUMBER(10, 0) default 0 NOT NULL,
	id_modulo NUMBER(10, 0) default 0 NOT NULL,
	id_plugin NUMBER(10, 0) default 0,
	plugin_user CLOB default '',
	plugin_pass CLOB default '',
	plugin_parameter CLOB,
	max_timeout NUMBER(10, 0) default 0,
	history_data NUMBER(5, 0) default 1,
	min_warning BINARY_DOUBLE default 0,
	max_warning BINARY_DOUBLE default 0,
	str_warning CLOB default '',
	min_critical BINARY_DOUBLE default 0,
	max_critical BINARY_DOUBLE default 0,
	str_critical CLOB default '',
	min_ff_event NUMBER(10, 0) default 0,
	custom_string_1 CLOB default '',
	custom_string_2 CLOB default '',
	custom_string_3 CLOB default '',
	custom_integer_1 INTEGER default 0,
	custom_integer_2 INTEGER default 0,
	post_process BINARY_DOUBLE default 0
);

CREATE SEQUENCE tnetwork_component_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tnetwork_component_inc BEFORE INSERT ON tnetwork_component REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetwork_component_s.nextval INTO :NEW.ID_NC FROM dual; END;;

CREATE TABLE tnetwork_component_group (
	id_sg NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(200) default '',
	parent NUMBER(19, 0) default 0 NOT NULL 
);


CREATE TABLE tnetwork_profile (
	id_np NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '',
	description VARCHAR2(250) default ''
);

CREATE SEQUENCE tnetwork_profile_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tnetwork_profile_inc BEFORE INSERT ON tnetwork_profile REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetwork_profile_s.nextval INTO :NEW.ID_NP FROM dual; END;;

CREATE TABLE tnetwork_profile_component (
	id_nc NUMBER(19, 0) default 0 NOT NULL,
	id_np NUMBER(19, 0) default 0 NOT NULL
);
CREATE INDEX tnetwork_profile_id_np_idx ON tnetwork_profile_component(id_np);

CREATE TABLE tnota (
	id_nota NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_incident NUMBER(19, 0) NOT NULL,
	id_usuario VARCHAR2(100) default '0' NOT NULL,
	timestamp TIMESTAMP  default CURRENT_TIMESTAMP, 
	nota CLOB NOT NULL
);
CREATE INDEX tnota_id_incident_idx ON tnota(id_incident);

CREATE TABLE torigen (
	origen VARCHAR2(100) default ''
);

CREATE TABLE tperfil (
	id_perfil NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name CLOB default '',
	incident_edit NUMBER(5, 0) default 0 NOT NULL,
	incident_view NUMBER(5, 0) default 0 NOT NULL,
	incident_management NUMBER(5, 0) default 0 NOT NULL,
	agent_view NUMBER(5, 0) default 0 NOT NULL,
	agent_edit NUMBER(5, 0) default 0 NOT NULL,
	alert_edit NUMBER(5, 0) default 0 NOT NULL,
	user_management NUMBER(5, 0) default 0 NOT NULL,
	db_management NUMBER(5, 0) default 0 NOT NULL,
	alert_management NUMBER(5, 0) default 0 NOT NULL,
	pandora_management NUMBER(5, 0) default 0 NOT NULL
);

CREATE SEQUENCE tperfil_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tperfil_inc BEFORE INSERT ON tperfil REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tperfil_s.nextval INTO :NEW.ID_PERFIL FROM dual; END;;

CREATE TABLE trecon_script (
	id_recon_script NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '',
	description CLOB default NULL,
	script VARCHAR2(250) default ''
);

CREATE SEQUENCE trecon_script_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER trecon_script_inc BEFORE INSERT ON trecon_script REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT trecon_script_s.nextval INTO :NEW.ID_RECON_SCRIPT FROM dual; END;;

CREATE TABLE trecon_task (
	id_rt NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '',
	description VARCHAR2(250) default '',
	subnet VARCHAR2(64) default '',
	id_network_profile NUMBER(10, 0) default 0 NOT NULL,
	create_incident NUMBER(10, 0) default 0 NOT NULL,
	id_group NUMBER(10, 0) default 1 NOT NULL,
	utimestamp NUMBER(19, 0) default 0 NOT NULL,
	status NUMBER(10, 0) default 0 NOT NULL,
	interval_sweep NUMBER(10, 0) default 0 NOT NULL,
	id_recon_server NUMBER(10, 0) default 0 NOT NULL,
	id_os NUMBER(10, 0) default 0 NOT NULL,
	recon_ports VARCHAR2(250) default '',
	snmp_community VARCHAR2(64) default 'public' NOT NULL,
	id_recon_script NUMBER(10, 0),
	field1 VARCHAR2(250) default '',
	field2 VARCHAR2(250) default '',
	field3 VARCHAR2(250) default '',
	field4 VARCHAR2(250) default '',
	os_detect NUMBER(5, 0) default 1 NOT NULL,
	resolve_names NUMBER(5, 0) default 1 NOT NULL,
	parent_detection NUMBER(5, 0) default 1 NOT NULL,
	parent_recursion NUMBER(5, 0) default 1 NOT NULL,
	disabled NUMBER(5, 0) default 1 NOT NULL
);
CREATE INDEX trecon_task_id_rec_serv_idx ON trecon_task(id_recon_server);

CREATE SEQUENCE trecon_task_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER trecon_task_inc BEFORE INSERT ON trecon_task REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT trecon_task_s.nextval INTO :NEW.ID_RT FROM dual; END trecon_task_inc;;

CREATE TABLE tserver (
	id_server NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '',
	ip_address VARCHAR2(100) default '',
	status NUMBER(10, 0) default 0 NOT NULL,
	laststart TIMESTAMP default NULL,
	keepalive TIMESTAMP default NULL,
	snmp_server NUMBER(10, 0) default 0 NOT NULL,
	network_server NUMBER(10, 0) default 0 NOT NULL,
	data_server NUMBER(10, 0) default 0 NOT NULL,
	master NUMBER(10, 0) default 0 NOT NULL,
	checksum NUMBER(10, 0) default 0 NOT NULL,
	description VARCHAR2(255) default NULL,
	recon_server NUMBER(10, 0) default 0 NOT NULL,
	version VARCHAR2(20) default '',
	plugin_server NUMBER(10, 0) default 0 NOT NULL,
	prediction_server NUMBER(10, 0) default 0 NOT NULL,
	wmi_server NUMBER(10, 0) default 0 NOT NULL,
	export_server NUMBER(10, 0) default 0 NOT NULL,
	server_type NUMBER(10, 0) default 0 NOT NULL,
	queued_modules NUMBER(10, 0) default 0 NOT NULL,
	threads NUMBER(10, 0) default 0 NOT NULL,
	lag_time NUMBER(10, 0) default 0 NOT NULL,
	lag_modules NUMBER(10, 0) default 0 NOT NULL,
	total_modules_running NUMBER(10, 0) default 0 NOT NULL,
	my_modules NUMBER(10, 0) default 0 NOT NULL,
	stat_utimestamp NUMBER(19, 0) default 0 NOT NULL
);
CREATE INDEX tserver_name_idx ON tserver(name);
CREATE INDEX tserver_keepalive_idx ON tserver(keepalive);
CREATE INDEX tserver_status_idx ON tserver(status);

CREATE SEQUENCE tserver_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tserver_inc BEFORE INSERT ON tserver REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tserver_s.nextval INTO :NEW.ID_SERVER FROM dual; END tserver_inc;;

-- server types:
-- 0 data
-- 1 network
-- 2 snmp trap console
-- 3 recon
-- 4 plugin
-- 5 prediction
-- 6 wmi
-- 7 export
-- 8 inventory
-- 9 web
-- TODO: drop 2.x xxxx_server fields, unused since server_type exists.

CREATE TABLE tsesion (
	id_sesion NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_usuario VARCHAR2(60) default '0' NOT NULL,
	ip_origen VARCHAR2(100) default '',
	accion VARCHAR2(100) default '',
	descripcion CLOB default '',
	fecha TIMESTAMP default NULL,
	utimestamp NUMBER(19, 0) default 0 NOT NULL
);
CREATE INDEX tsesion_utimestamp_idx ON tsesion(utimestamp);
CREATE INDEX tsesion_id_usuario_idx ON tsesion(id_usuario);

CREATE SEQUENCE tsesion_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tsesion_inc BEFORE INSERT ON tsesion REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tsesion_s.nextval INTO :NEW.ID_SESION FROM dual; END tsesion_inc;;

CREATE TABLE ttipo_modulo (
	id_tipo NUMBER(10, 0) NOT NULL PRIMARY KEY,
	nombre VARCHAR2(100) default '',
	categoria NUMBER(10, 0) default 0 NOT NULL,
	descripcion VARCHAR2(100) default '',
	icon VARCHAR2(100) default NULL
);

CREATE SEQUENCE ttipo_modulo_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER ttipo_modulo_inc BEFORE INSERT ON ttipo_modulo REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT ttipo_modulo_s.nextval INTO :NEW.ID_TIPO FROM dual; END ttipo_modulo_inc;;

CREATE TABLE ttrap (
	id_trap NUMBER(19, 0) NOT NULL PRIMARY KEY,
	source VARCHAR2(50) default '',
	oid CLOB default '',
	oid_custom CLOB default '',
	type NUMBER(10, 0) default 0 NOT NULL,
	type_custom VARCHAR2(100) default '',
	value CLOB default '',
	value_custom CLOB default '',
	alerted NUMBER(5, 0) default 0 NOT NULL,
	status NUMBER(5, 0) default 0 NOT NULL,
	id_usuario VARCHAR2(150) default '',
	timestamp TIMESTAMP default NULL,
	priority NUMBER(5, 0) default 2 NOT NULL,
	text VARCHAR2(255) default '',
	description VARCHAR2(255) default '',
	severity NUMBER(10, 0) default 2 NOT NULL
);

CREATE SEQUENCE ttrap_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER ttrap_inc BEFORE INSERT ON ttrap REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT ttrap_s.nextval INTO :NEW.ID_TRAP FROM dual; END ttrap_inc;;

CREATE TABLE tusuario (
	id_user VARCHAR2(60) NOT NULL PRIMARY KEY,
	fullname VARCHAR2(255) NOT NULL,
	firstname VARCHAR2(255),
	lastname VARCHAR2(255),
	middlename VARCHAR2(255) default '',
	password VARCHAR2(45) default NULL,
	comments VARCHAR2(200) default NULL,
	last_connect NUMBER(19, 0) default 0 NOT NULL,
	registered NUMBER(19, 0) default 0 NOT NULL,
	email VARCHAR2(100) default NULL,
	phone VARCHAR2(100) default NULL,
	is_admin NUMBER(5, 0) default 0 NOT NULL,
	language VARCHAR2(10) default NULL,
	timezone VARCHAR2(50) default '',
	block_size NUMBER(10, 0) default 20 NOT NULL,
	flash_chart NUMBER(10, 0) default 1 NOT NULL,
	id_skin NUMBER(10, 0) DEFAULT 0 NOT NULL
);

CREATE TABLE tusuario_perfil (
	id_up NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_usuario VARCHAR2(100) default '',
	id_perfil NUMBER(10, 0) default 0 NOT NULL,
	id_grupo NUMBER(10, 0) default 0 NOT NULL,
	assigned_by VARCHAR2(100) default '',
	id_policy NUMBER(10, 0) DEFAULT 0 NOT NULL
);

CREATE SEQUENCE tusuario_perfil_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tusuario_perfil_inc BEFORE INSERT ON tusuario_perfil REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tusuario_perfil_s.nextval INTO :NEW.ID_UP FROM dual; END tusuario_perfil_inc;;

CREATE TABLE tnews (
	id_news NUMBER(10, 0) NOT NULL PRIMARY KEY,
	author VARCHAR2(255) DEFAULT '',
	subject VARCHAR2(255) DEFAULT '',
	text CLOB NOT NULL,
	timestamp TIMESTAMP default NULL
);

CREATE SEQUENCE tnews_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tnews_inc BEFORE INSERT ON tnews REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnews_s.nextval INTO :NEW.ID_NEWS FROM dual; END tnews_inc;;

CREATE TABLE tgraph (
	id_graph NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_user VARCHAR2(100) default '',
	name VARCHAR2(150) default '',
	description CLOB NOT NULL,
	period NUMBER(10, 0) default 0 NOT NULL,
	width NUMBER(10, 0) default 0 NOT NULL,
	height NUMBER(10, 0) default 0 NOT NULL,
	private NUMBER(5, 0) default 0 NOT NULL,
	events NUMBER(5, 0) default 0 NOT NULL,
	stacked NUMBER(5, 0) default 0 NOT NULL,
	id_group NUMBER(19, 0) default 0 NOT NULL
);

CREATE SEQUENCE tgraph_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tgraph_inc BEFORE INSERT ON tgraph REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgraph_s.nextval INTO :NEW.ID_GRAPH FROM dual; END tgraph_inc;;

CREATE TABLE tgraph_source (
	id_gs NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_graph NUMBER(19, 0) default 0 NOT NULL,
	id_agent_module  NUMBER(19, 0) default 0 NOT NULL,
	weight BINARY_DOUBLE default 0
);

CREATE SEQUENCE tgraph_source_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tgraph_source_inc BEFORE INSERT ON tgraph_source REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgraph_source_s.nextval INTO :NEW.ID_GS FROM dual; END tgraph_source_inc;;

CREATE TABLE treport (
	id_report NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_user VARCHAR2(100) default '',
	name VARCHAR2(150) default '',
	description CLOB NOT NULL,
	private NUMBER(5, 0) default 0 NOT NULL,
	id_group NUMBER(19, 0) default 0 NOT NULL,
	custom_logo VARCHAR2(200)  default NULL,
	header CLOB  default NULL,
	first_page CLOB default NULL,
	footer CLOB default NULL,
	custom_font VARCHAR2(200) default NULL
);

CREATE SEQUENCE treport_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER treport_inc BEFORE INSERT ON treport REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_s.nextval INTO :NEW.ID_REPORT FROM dual; END treport_inc;;

-- -----------------------------------------------------
-- Table "treport_content"
-- -----------------------------------------------------
-- use to_char(time_from, 'hh24:mi:ss') function to retrieve time_from field info
-- use to_char(time_to,   'hh24:mi:ss') function to retrieve time_to field info
CREATE TABLE treport_content (
	id_rc NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_report NUMBER(10, 0) default 0 NOT NULL REFERENCES treport(id_report) ON DELETE CASCADE, 
	id_gs  NUMBER(10, 0) default NULL,
	id_agent_module NUMBER(19, 0) default NULL,
	type VARCHAR2(30) default 'simple_graph',
	period NUMBER(19, 0) default 0 NOT NULL,
	"order" NUMBER(19, 0) default 0 NOT NULL,
	description CLOB, 
	id_agent NUMBER(19, 0) default 0 NOT NULL,
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
	only_display_wrong NUMBER(5, 0) default 0 NOT NULL,
	top_n NUMBER(10, 0) default 0 NOT NULL,
	top_n_value NUMBER(10, 0) default 10 NOT NULL ,
	exception_condition NUMBER(10, 0) default 0 NOT NULL,
	exception_condition_value BINARY_DOUBLE default 0 NOT NULL,
	show_resume NUMBER(10, 0) default 0 NOT NULL,
	order_uptodown NUMBER(10, 0) default 0 NOT NULL,
	show_graph NUMBER(10, 0) default 0 NOT NULL,
	group_by_agent NUMBER(10, 0) default 0 NOT NULL,
	style CLOB default '',
	id_group NUMBER(10, 0) default 0 NOT NULL,
	id_module_group NUMBER(10, 0) default 0 NOT NULL,
	server_name CLOB default ''
);

CREATE SEQUENCE treport_content_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER treport_content_inc BEFORE INSERT ON treport_content REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_content_s.nextval INTO :NEW.ID_RC FROM dual; END treport_content_inc;;

-- on update trigger
CREATE OR REPLACE TRIGGER treport_content_update AFTER UPDATE OF ID_REPORT ON treport FOR EACH ROW BEGIN UPDATE treport_content SET ID_RC = :NEW.ID_REPORT WHERE ID_RC = :OLD.ID_REPORT; END;;

CREATE TABLE treport_content_sla_combined (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_report_content NUMBER(10, 0) NOT NULL  REFERENCES treport_content(id_rc) ON DELETE CASCADE,
	id_agent_module NUMBER(10, 0) NOT NULL,
	sla_max BINARY_DOUBLE default 0 NOT NULL,
	sla_min BINARY_DOUBLE default 0 NOT NULL,
	sla_limit BINARY_DOUBLE default 0 NOT NULL,
	server_name CLOB default ''
);

CREATE SEQUENCE treport_cont_sla_c_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER treport_content_sla_comb_inc BEFORE INSERT ON treport_content REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_cont_sla_c_s.nextval INTO :NEW.ID_RC FROM dual; END treport_content_sla_comb_inc;; 

-- on update trigger
CREATE OR REPLACE TRIGGER treport_cont_sla_comb_update AFTER UPDATE OF ID_RC ON treport_content FOR EACH ROW BEGIN UPDATE treport_content_sla_combined SET ID_REPORT_CONTENT = :NEW.ID_RC WHERE ID_REPORT_CONTENT = :OLD.ID_RC; END;;


CREATE TABLE treport_content_item (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_report_content NUMBER(10, 0) NOT NULL,
	id_agent_module NUMBER(10, 0) NOT NULL,
	server_name CLOB default ''
);

CREATE SEQUENCE treport_content_item_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER treport_content_item_inc BEFORE INSERT ON treport_content_item REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_content_item_s.nextval INTO :NEW.ID FROM dual; END treport_content_item_inc;; 


CREATE TABLE treport_custom_sql (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(150) default '',
	sql CLOB default NULL
);

CREATE SEQUENCE treport_custom_sql_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER treport_custom_sql_inc BEFORE INSERT ON treport_custom_sql REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_custom_sql_s.nextval INTO :NEW.ID FROM dual; END treport_custom_sql_inc;;

CREATE TABLE tlayout (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name varchar(50)  NOT NULL,
	id_group NUMBER(10, 0) NOT NULL,
	background varchar(200)  NOT NULL,
	fullscreen NUMBER(5, 0) default 0 NOT NULL,
	height NUMBER(10, 0) default 0 NOT NULL,
	width NUMBER(10, 0) default 0 NOT NULL
);

CREATE SEQUENCE tlayout_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tlayout_inc BEFORE INSERT ON tlayout REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tlayout_s.nextval INTO :NEW.ID FROM dual; END tlayout_inc;;

CREATE TABLE tlayout_data (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_layout NUMBER(10, 0) default 0 NOT NULL,
	pos_x NUMBER(10, 0) default 0 NOT NULL,
	pos_y NUMBER(10, 0) default 0 NOT NULL,
	height NUMBER(10, 0) default 0 NOT NULL,
	width NUMBER(10, 0) default 0 NOT NULL,
	label VARCHAR2(200) DEFAULT '',
	image VARCHAR2(200) DEFAULT '',
	type NUMBER(5, 0) default 0 NOT NULL,
	period NUMBER(10, 0) default 3600 NOT NULL,
	id_agente_modulo NUMBER(19, 0) default 0 NOT NULL,
	id_agent NUMBER(10, 0) default 0 NOT NULL,
	id_layout_linked NUMBER(10, 0) default 0 NOT NULL,
	parent_item NUMBER(10, 0) default 0 NOT NULL,
	label_color varchar(20) DEFAULT '',
	no_link_color NUMBER(5, 0) default 0 NOT NULL
);

CREATE SEQUENCE tlayout_data_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tlayout_data_inc BEFORE INSERT ON tlayout_data REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tlayout_data_s.nextval INTO :NEW.ID FROM dual; END tlayout_data_inc;;

CREATE TABLE tplugin (
  	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
  	name VARCHAR2(200) NOT NULL,
  	description CLOB,
  	max_timeout NUMBER(10, 0) default 0 NOT NULL,
  	execute VARCHAR2(250) NOT NULL,
  	net_dst_opt VARCHAR2(50) default '',
  	net_port_opt VARCHAR2(50) default '',
  	user_opt VARCHAR2(50) default '',
  	pass_opt VARCHAR2(50) default '',
  	plugin_type NUMBER(5, 0) default 0 NOT NULL
); 

CREATE SEQUENCE tplugin_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tplugin_inc BEFORE INSERT ON tplugin REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tplugin_s.nextval INTO :NEW.ID FROM dual; END tplugin_inc;;

CREATE TABLE tmodule (
	id_module NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '' 
);

CREATE SEQUENCE tmodule_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tmodule_inc BEFORE INSERT ON tmodule REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmodule_s.nextval INTO :NEW.ID_MODULE FROM dual; END tmodule_inc;;

CREATE TABLE tserver_export (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '',
	preffix VARCHAR2(100) default '',
	interval NUMBER(10, 0) default 300 NOT NULL,
	ip_server VARCHAR2(100) default '',
	connect_mode VARCHAR2(20) default 'local',
	id_export_server NUMBER(10, 0) default NULL ,
	"user" VARCHAR2(100) default '',
	pass VARCHAR2(100) default '',
	port NUMBER(10, 0) default 0 NOT NULL,
	directory VARCHAR2(100) default '',
	options VARCHAR2(100) default '',
	--Number of hours of diference with the server timezone
	timezone_offset NUMBER(5, 0) default 0 NOT NULL,
	CONSTRAINT tserver_export_conn_mode_cons CHECK (connect_mode IN ('tentacle', 'ssh', 'local'))
);

CREATE SEQUENCE tserver_export_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tserver_export_inc BEFORE INSERT ON tserver_export REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tserver_export_s.nextval INTO :NEW.ID FROM dual; END tserver_export_inc;;

-- id_export_server is real pandora fms export server process that manages this server
-- id is the "destination" server to export
CREATE TABLE tserver_export_data (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_export_server NUMBER(10, 0) default 0 NOT NULL,
	agent_name VARCHAR2(100) default '',
	module_name VARCHAR2(100) default '',
	module_type VARCHAR2(100) default '',
	data VARCHAR2(255) default NULL, 
	timestamp TIMESTAMP default NULL
);

CREATE SEQUENCE tserver_export_data_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tserver_export_data_inc BEFORE INSERT ON tserver_export_data REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tserver_export_data_s.nextval INTO :NEW.ID FROM dual; END tserver_export_data_inc;;

CREATE TABLE tplanned_downtime (
	id NUMBER(19, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) NOT NULL,
	description CLOB NOT NULL,
	date_from NUMBER(19, 0) default 0 NOT NULL,
	date_to NUMBER(19, 0) default 0 NOT NULL,
	executed NUMBER(5, 0) default 0 NOT NULL,
	id_group NUMBER(19, 0) default 0 NOT NULL,
	only_alerts NUMBER(5, 0) default 0 NOT NULL
);

CREATE SEQUENCE tplanned_downtime_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tplanned_downtime_inc BEFORE INSERT ON tplanned_downtime REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tplanned_downtime_s.nextval INTO :NEW.ID FROM dual; END tplanned_downtime_inc;;

CREATE TABLE tplanned_downtime_agents (
	id NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_agent NUMBER(19, 0) default 0 NOT NULL,
	id_downtime NUMBER(19, 0) default 0 NOT NULL
);

CREATE SEQUENCE tplanned_downtime_agents_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tplanned_downtime_agents_inc BEFORE INSERT ON tplanned_downtime_agents REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tplanned_downtime_agents_s.nextval INTO :NEW.ID FROM dual; END tplanned_downtime_agents_inc;;

-- GIS extension Tables

-- -----------------------------------------------------
-- Table "tgis_data_history"
-- -----------------------------------------------------
--Table to store historical GIS information of the agents
CREATE TABLE tgis_data_history (
	--key of the table
	id_tgis_data NUMBER(10, 0) NOT NULL PRIMARY KEY,
	longitude BINARY_DOUBLE NOT NULL,
	latitude BINARY_DOUBLE NOT NULL,
	altitude BINARY_DOUBLE NOT NULL,
	--timestamp on wich the agente started to be in this position
	start_timestamp  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	--timestamp on wich the agent was placed for last time on this position
	end_timestamp  TIMESTAMP default NULL,
	--description of the region correoponding to this placemnt
	description CLOB DEFAULT NULL,
	-- 0 to show that the position cames from the agent, 1 to show that the position was established manualy
	manual_placement NUMBER(5, 0) default 0 NOT NULL,
	-- Number of data packages received with this position from the start_timestampa to the_end_timestamp
	number_of_packages NUMBER(10, 0) default 1 NOT NULL,
	--reference to the agent
	tagente_id_agente NUMBER(10, 0) NOT NULL 
);
CREATE INDEX tgis_data_history_start_t_idx ON tgis_data_history(start_timestamp);
CREATE INDEX tgis_data_history_end_t_idx ON tgis_data_history(end_timestamp);
 
CREATE SEQUENCE tgis_data_history_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tgis_data_history_inc BEFORE INSERT ON tgis_data_history REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgis_data_history_s.nextval INTO :NEW.ID_TGIS_DATA FROM dual; END tgis_data_history_inc;;

-- -----------------------------------------------------
-- Table "tgis_data_status"
-- -----------------------------------------------------
--Table to store last GIS information of the agents
--ON UPDATE NO ACTION is implicit on Oracle DBMS for tagente_id_agente field 
CREATE TABLE tgis_data_status (
	--Reference to the agent
	tagente_id_agente NUMBER(10, 0) NOT NULL REFERENCES tagente(id_agente) ON DELETE CASCADE, 
	--Last received longitude
	current_longitude BINARY_DOUBLE NOT NULL,
	--Last received latitude 
	current_latitude BINARY_DOUBLE NOT NULL,
	--Last received altitude 
	current_altitude BINARY_DOUBLE NOT NULL,
	--Reference longitude to see if the agent has moved
	stored_longitude BINARY_DOUBLE NOT NULL,
	--Reference latitude to see if the agent has moved
	stored_latitude BINARY_DOUBLE NOT NULL,
	--Reference altitude to see if the agent has moved
	stored_altitude BINARY_DOUBLE DEFAULT NULL,
	--Number of data packages received with this position since start_timestampa
	number_of_packages NUMBER(10, 0) default 1 NOT NULL, 
	--Timestamp on wich the agente started to be in this position
	start_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL, 
	--0 to show that the position cames from the agent, 1 to show that the position was established manualy
	manual_placement NUMBER(5, 0) default 0 NOT NULL, 
	--description of the region correoponding to this placemnt
	description CLOB NULL,
  PRIMARY KEY(tagente_id_agente)
);
CREATE INDEX tgis_data_status_start_t_idx ON tgis_data_status(start_timestamp);

-- -----------------------------------------------------
-- Table "tgis_map"
-- -----------------------------------------------------
--Table containing information about a gis map
CREATE TABLE tgis_map (
    --table identifier
	id_tgis_map NUMBER(10, 0) NOT NULL PRIMARY KEY,
	--Name of the map
	map_name VARCHAR2(63) NOT NULL,
	--longitude of the center of the map when it\'s loaded
	initial_longitude BINARY_DOUBLE DEFAULT NULL,
	--latitude of the center of the map when it\'s loaded
	initial_latitude BINARY_DOUBLE DEFAULT NULL,
	--altitude of the center of the map when it\'s loaded
	initial_altitude BINARY_DOUBLE DEFAULT NULL,
	--Zoom level to show when the map is loaded.
	zoom_level NUMBER(5, 0) default 1 NOT NULL,
	--path on the server to the background image of the map
	map_background VARCHAR2(127) DEFAULT NULL,
	--default longitude for the agents placed on the map
	default_longitude BINARY_DOUBLE DEFAULT NULL,
	--default latitude for the agents placed on the map
	default_latitude BINARY_DOUBLE DEFAULT NULL,
	--default altitude for the agents placed on the map
	default_altitude DOUBLE PRECISION DEFAULT NULL,
	--Group that owns the map
	group_id NUMBER(10, 0) default 0 NOT NULL,
	--1 if this is the default map, 0 in other case
	default_map NUMBER(5, 0) default 0 NOT NULL
);
CREATE INDEX tgis_map_tagente_map_name_idx ON tgis_map(map_name);

CREATE SEQUENCE tgis_map_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tgis_map_inc BEFORE INSERT ON tgis_map REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgis_map_s.nextval INTO :NEW.ID_TGIS_MAP FROM dual; END tgis_map_inc;;

-- -----------------------------------------------------
-- Table "tgis_map_connection"
-- -----------------------------------------------------
--Table to store the map connection information
CREATE TABLE tgis_map_connection (
	--table id
	id_tmap_connection NUMBER(10, 0) NOT NULL PRIMARY KEY,
	--Name of the connection (name of the base layer)
	conection_name VARCHAR2(45) DEFAULT NULL,
	--Type of map server to connect
	connection_type VARCHAR2(45) DEFAULT NULL, 
	--connection information (this can probably change to fit better the possible connection parameters)
	conection_data CLOB DEFAULT NULL, 
	--Number of zoom levels available
	num_zoom_levels NUMBER(5, 0) DEFAULT NULL, 
	--Default Zoom Level for the connection
	default_zoom_level NUMBER(5, 0) default 16 NOT NULL,
	--default longitude for the agents placed on the map
	default_longitude BINARY_DOUBLE DEFAULT NULL,
	--default latitude for the agents placed on the map
	default_latitude BINARY_DOUBLE DEFAULT NULL,
	--default altitude for the agents placed on the map
	default_altitude BINARY_DOUBLE DEFAULT NULL,
	--longitude of the center of the map when it\'s loaded
	initial_longitude BINARY_DOUBLE DEFAULT NULL,
	--latitude of the center of the map when it\'s loaded
	initial_latitude BINARY_DOUBLE DEFAULT NULL, 
	--altitude of the center of the map when it\'s loaded
	initial_altitude BINARY_DOUBLE DEFAULT NULL, 
	--Group that owns the map
	group_id NUMBER(10, 0) default 0 NOT NULL  
);

CREATE SEQUENCE tgis_map_connection_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tgis_map_connection_inc BEFORE INSERT ON tgis_map_connection REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgis_map_connection_s.nextval INTO :NEW.ID_TMAP_CONNECTION FROM dual; END tgis_map_connection_inc;;

-- -----------------------------------------------------
-- Table "tgis_map_has_tgis_map_connection"
-- -----------------------------------------------------

-- This table is commented because table name length is more 30 chars. TODO: Change it's name 

--Table to asociate a connection to a gis map
--CREATE TABLE tgis_map_has_tgis_map_connection (
	--reference to tgis_map
--	tgis_map_id_tgis_map NUMBER(10, 0) NOT NULL REFERENCES tgis_map(id_tgis_map) ON DELETE CASCADE, 
	--reference to tgis_map_connection
--	tgis_map_connection_id_tmap_connection NUMBER(10, 0) NOT NULL REFERENCES tgis_map_connection (id_tmap_connection) ON DELETE CASCADE, 
	--Last Modification Time of the Connection
--	modification_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL, 
	--Flag to mark the default map connection of a map
--	default_map_connection NUMBER(5, 0) default 0 NOT NULL,
--  PRIMARY KEY (tgis_map_id_tgis_map, tgis_map_connection_id_tmap_connection)
--);
--CREATE INDEX tgis_map_has_tgis_map_connection_map_tgis_map_id_tgis_map_idx ON tgis_map_has_tgis_map_connection(tgis_map_id_tgis_map);
--CREATE INDEX tgis_map_has_tgis_map_connection_map_tgis_map_connection_id_tmap_connection_idx ON tgis_map_has_tgis_map_connection(tgis_map_connection_id_tmap_connection);

--This trigger is for tranlate "on update CURRENT_TIMESTAMP" of MySQL.
--CREATE OR REPLACE TRIGGER tgis_map_has_tgis_map_connection_ts BEFORE UPDATE ON tgis_map_has_tgis_map_connection FOR EACH ROW BEGIN select CURRENT_TIMESTAMP into :NEW.MODIFICATION_TIME from dual; END;;

-- -----------------------------------------------------
-- Table "tgis_map_layer"
-- -----------------------------------------------------
--Table containing information about the map layers
CREATE TABLE tgis_map_layer (
	--table id
	id_tmap_layer NUMBER(10, 0) NOT NULL PRIMARY KEY,
	--Name of the layer
	layer_name VARCHAR2(45) NOT NULL, 
	--True if the layer must be shown
	view_layer NUMBER(5, 0) default 1 NOT NULL, 
	--Number of order of the layer in the layer stack, bigger means upper on the stack.\n
	layer_stack_order NUMBER(5, 0) default 0 NOT NULL, 
	--reference to the map containing the layer
	tgis_map_id_tgis_map NUMBER(10, 0) default 0 NOT NULL REFERENCES tgis_map(id_tgis_map) ON DELETE CASCADE, 
	--reference to the group shown in the layer
	tgrupo_id_grupo NUMBER(19, 0) NOT NULL 
);

CREATE SEQUENCE tgis_map_layer_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tgis_map_layer_inc BEFORE INSERT ON tgis_map_layer REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgis_map_layer_s.nextval INTO :NEW.ID_TMAP_LAYER FROM dual; END tgis_map_layer_inc;;

-- -----------------------------------------------------
-- Table "tgis_map_layer_has_tagente"
-- -----------------------------------------------------
--Table to define wich agents are shown in a layer
CREATE TABLE tgis_map_layer_has_tagente (
	tgis_map_layer_id_tmap_layer NUMBER(10, 0) NOT NULL REFERENCES tgis_map_layer(id_tmap_layer) ON DELETE CASCADE,
	tagente_id_agente NUMBER(10, 0) NOT NULL REFERENCES tagente(id_agente) ON DELETE CASCADE,
  PRIMARY KEY (tgis_map_layer_id_tmap_layer, tagente_id_agente)
);
CREATE INDEX tgis_map_layer_has_tagente_idx ON tgis_map_layer_has_tagente(tgis_map_layer_id_tmap_layer);
CREATE INDEX tgis_map_layer_has_tagent1_idx ON tgis_map_layer_has_tagente(tagente_id_agente);

-- -----------------------------------------------------
-- Table "tgroup_stat"
-- -----------------------------------------------------
--Table to store global system stats per group
CREATE TABLE tgroup_stat (
	id_group NUMBER(10, 0) default 0 NOT NULL PRIMARY KEY,
	modules NUMBER(10, 0) default 0 NOT NULL,
	normal NUMBER(10, 0) default 0 NOT NULL,
	critical NUMBER(10, 0) default 0 NOT NULL,
	warning NUMBER(10, 0) default 0 NOT NULL,
	unknown NUMBER(10, 0) default 0 NOT NULL,
	"non-init" NUMBER(10, 0) default 0 NOT NULL,
	alerts NUMBER(10, 0) default 0 NOT NULL,
	alerts_fired NUMBER(10, 0) default 0 NOT NULL,
	agents NUMBER(10, 0) default 0 NOT NULL,
	agents_unknown NUMBER(10, 0) default 0 NOT NULL,
	utimestamp NUMBER(10, 0) default 0 NOT NULL
);

-- -----------------------------------------------------
-- Table "tnetwork_map"
-- -----------------------------------------------------
CREATE TABLE tnetwork_map (
	id_networkmap NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_user VARCHAR2(60)  NOT NULL,
	name VARCHAR2(100)  NOT NULL,
	type VARCHAR2(20)  NOT NULL,
	layout VARCHAR2(20)  NOT NULL,
	nooverlap NUMBER(5, 0) default 0 NOT NULL,
	simple NUMBER(5, 0) default 0 NOT NULL,
	regenerate NUMBER(5, 0) default 1 NOT NULL,
	font_size NUMBER(10, 0) default 12 NOT NULL,
	id_group NUMBER(10, 0) default 0 NOT NULL,
	id_module_group NUMBER(10, 0) default 0 NOT NULL,  
	id_policy NUMBER(10, 0) default 0 NOT NULL,
	depth VARCHAR2(20) NOT NULL,
	only_modules_with_alerts NUMBER(10, 0) default 0 NOT NULL,
	hide_policy_modules SMALLINT default 0 NOT NULL,
	zoom BINARY_DOUBLE default 1,
	distance_nodes BINARY_DOUBLE default 2.5,
	center NUMBER(10, 0) default 0 NOT NULL,
	contracted_nodes CLOB,
	show_snmp_modules NUMBER(5, 0) default 0 NOT NULL
);

CREATE SEQUENCE tnetwork_map_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tnetwork_map_inc BEFORE INSERT ON tnetwork_map REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetwork_map_s.nextval INTO :NEW.ID_NETWORKMAP FROM dual; END tnetwork_map_inc;;

-- -----------------------------------------------------
-- Table "tsnmp_filter"
-- -----------------------------------------------------
CREATE TABLE tsnmp_filter (
	id_snmp_filter NUMBER(10, 0) NOT NULL PRIMARY KEY,
	description VARCHAR2(255) default '',
	filter VARCHAR2(255) default ''
);

CREATE SEQUENCE tsnmp_filter_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tsnmp_filter_inc BEFORE INSERT ON tsnmp_filter REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tsnmp_filter_s.nextval INTO :NEW.ID_SNMP_FILTER FROM dual; END tsnmp_filter_inc;;

-- -----------------------------------------------------
-- Table "tagent_custom_fields"
-- -----------------------------------------------------
CREATE TABLE tagent_custom_fields (
	id_field NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(45) default '',
	display_on_front NUMBER(5, 0) default 0 NOT NULL
);

CREATE SEQUENCE tagent_custom_fields_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tagent_custom_fields_inc BEFORE INSERT ON tagent_custom_fields REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagent_custom_fields_s.nextval INTO :NEW.ID_FIELD FROM dual; END tagent_custom_fields_inc;;

-- -----------------------------------------------------
-- Table "tagent_custom_data"
-- -----------------------------------------------------
CREATE TABLE tagent_custom_data (
	id_field NUMBER(10, 0) NOT NULL REFERENCES tagent_custom_fields(id_field) ON DELETE CASCADE,
	id_agent NUMBER(10, 0) NOT NULL REFERENCES tagente(id_agente) ON DELETE CASCADE,
	description CLOB default '',
  PRIMARY KEY  (id_field, id_agent)
);

-- on update trigger
CREATE OR REPLACE TRIGGER tagent_custom_data_update AFTER UPDATE OF ID_FIELD ON tagent_custom_fields FOR EACH ROW BEGIN UPDATE tagent_custom_data SET ID_FIELD = :NEW.ID_FIELD WHERE ID_FIELD = :OLD.ID_FIELD; END;;

-- on update trigger 1
CREATE OR REPLACE TRIGGER tagent_custom_data_update1 AFTER UPDATE OF ID_AGENTE ON tagente FOR EACH ROW BEGIN UPDATE tagent_custom_data SET ID_AGENT = :NEW.ID_AGENTE WHERE ID_AGENT = :OLD.ID_AGENTE; END;;

-- Procedure for retrieve PK information after an insert statement
CREATE OR REPLACE PROCEDURE insert_id (table_name IN VARCHAR2, sql_insert IN VARCHAR2, id OUT NUMBER) IS BEGIN EXECUTE IMMEDIATE sql_insert; EXECUTE IMMEDIATE 'SELECT ' ||table_name||'_s.currval FROM DUAL' INTO id; EXCEPTION WHEN others THEN RAISE_APPLICATION_ERROR(-20001, 'ERROR on insert_id procedure, please check input parameters or procedure logic.'); END insert_id;;


-- -----------------------------------------------------
-- Table "ttag"
-- -----------------------------------------------------

CREATE TABLE ttag ( 
 id_tag NUMBER(10, 0) NOT NULL PRIMARY KEY, 
 name VARCHAR2(100) default '' NOT NULL, 
 description CLOB default '' NOT NULL, 
 url CLOB default '' NOT NULL
); 

CREATE SEQUENCE ttag_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER ttag_inc BEFORE INSERT ON ttag REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT ttag_s.nextval INTO :NEW.ID_TAG FROM dual; END ttag_inc;;

-- -----------------------------------------------------
-- Table "ttag_module"
-- -----------------------------------------------------

CREATE TABLE ttag_module ( 
 id_tag NUMBER(10, 0) NOT NULL, 
 id_agente_modulo NUMBER(10, 0) DEFAULT 0 NOT NULL, 
   PRIMARY KEY  (id_tag, id_agente_modulo)
); 

CREATE INDEX ttag_module_id_ag_modulo_idx ON ttag_module(id_agente_modulo);

-- -----------------------------------------------------
-- Table "ttag_policy_module"
-- -----------------------------------------------------

CREATE TABLE ttag_policy_module ( 
 id_tag NUMBER(10, 0) NOT NULL, 
 id_policy_module NUMBER(10, 0) DEFAULT 0 NOT NULL, 
   PRIMARY KEY  (id_tag, id_policy_module)
); 

CREATE INDEX ttag_poli_mod_id_pol_mo_idx ON ttag_policy_module(id_policy_module);

-- -----------------------------------------------------
-- Table "ttag_event"
-- -----------------------------------------------------

CREATE TABLE ttag_event ( 
 id_tag NUMBER(10, 0) NOT NULL, 
 id_evento NUMBER(19, 0) DEFAULT 0 NOT NULL, 
   PRIMARY KEY  (id_tag, id_evento)
); 

CREATE INDEX ttag_event_id_evento_idx ON ttag_event(id_evento);

-- ---------------------------------------------------------------------
-- Table `tupdate_settings`
-- ---------------------------------------------------------------------
CREATE TABLE tupdate_settings ( 
	key VARCHAR2(255) default '' PRIMARY KEY, 
	value VARCHAR2(255) default ''
);

-- ---------------------------------------------------------------------
-- Table `tupdate_package`
-- ---------------------------------------------------------------------
CREATE TABLE tupdate_package( 
	id NUMBER(10, 0) NOT NULL PRIMARY KEY, 
	timestamp  TIMESTAMP default NULL, 
	description VARCHAR2(255) default ''
);

CREATE SEQUENCE tupdate_package_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tupdate_package_inc BEFORE INSERT ON tupdate_package REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tupdate_package_s.nextval INTO :NEW.ID FROM dual; END tupdate_package_inc;;

-- ---------------------------------------------------------------------
-- Table `tupdate`
-- ---------------------------------------------------------------------
CREATE TABLE tupdate ( 
	id NUMBER(10, 0) NOT NULL PRIMARY KEY, 
	type VARCHAR2(15), 
	id_update_package NUMBER(10, 0) default 0 REFERENCES tupdate_package(id) ON DELETE CASCADE, 
	filename VARCHAR2(250) default '', 
	checksum VARCHAR2(250) default '', 
	previous_checksum VARCHAR2(250) default '', 
	svn_version NUMBER(10, 0) default 0, 
	data CLOB default '', 
	data_rollback CLOB default '', 
	description CLOB default '', 
	db_table_name VARCHAR2(140) default '', 
	db_field_name VARCHAR2(140) default '', 
	db_field_value VARCHAR2(1024) default '', 
	CONSTRAINT tupdate_type_cons CHECK (type IN ('code', 'db_data', 'db_schema', 'binary'))
);

CREATE SEQUENCE tupdate_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tupdate_inc BEFORE INSERT ON tupdate REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tupdate_s.nextval INTO :NEW.ID FROM dual; END;;
CREATE OR REPLACE TRIGGER tupdate_update AFTER UPDATE OF ID ON tupdate_package FOR EACH ROW BEGIN UPDATE tupdate SET ID_UPDATE_PACKAGE = :NEW.ID WHERE ID_UPDATE_PACKAGE = :OLD.ID; END;;

-- ---------------------------------------------------------------------
-- Table `tupdate_journal`
-- ---------------------------------------------------------------------
CREATE TABLE tupdate_journal ( 
	id NUMBER(10, 0) NOT NULL PRIMARY KEY, 
	id_update NUMBER(10, 0) default 0 REFERENCES tupdate(id) ON DELETE CASCADE
);

CREATE SEQUENCE tupdate_journal_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tupdate_journal_inc BEFORE INSERT ON tupdate_journal REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tupdate_journal_s.nextval INTO :NEW.ID FROM dual; END;;
CREATE OR REPLACE TRIGGER tupdate_journal_update AFTER UPDATE OF ID ON tupdate FOR EACH ROW BEGIN UPDATE tupdate_journal SET ID = :NEW.ID WHERE ID = :OLD.ID; END;;

