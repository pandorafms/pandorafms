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

-- The charset is for all DB not only table.
--CREATE DATABASE pandora CHARACTER SET WE8ISO8859P1 NATIONAL CHARACTER SET UTF8;

-- Pandora schema creation script

CREATE TABLE taddress (
	id_a NUMBER(10, 0) NOT NULL PRIMARY KEY,
	ip VARCHAR(60) default '' NOT NULL,
	ip_pack NUMBER(10, 0) default 0 NOT NULL 
);
CREATE INDEX taddress_ip_idx ON taddress(ip);

CREATE SEQUENCE taddress_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER taddress_inc BEFORE INSERT ON taddress REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT taddress_s.nextval INTO :NEW.ID_A FROM dual; END; /

CREATE TABLE taddress_agent (
	id_ag NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_a NUMBER(19, 0) default 0 NOT NULL,
	id_agent NUMBER(19, 0) default 0 NOT NULL 
);

CREATE SEQUENCE taddress_agent_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER taddress_agent_inc BEFORE INSERT ON taddress_agent REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT taddress_agent_s.nextval INTO :NEW.ID_AG FROM dual; END; /

CREATE TABLE tagente (
	id_agente NUMBER(10, 0) NOT NULL PRIMARY KEY,
	nombre VARCHAR2(600) default '' NOT NULL,
	direccion VARCHAR2(100) default NULL,
	comentarios VARCHAR2(255) default '',
	id_grupo NUMBER(10, 0) default 0 NOT NULL,
	ultimo_contacto DATE default NULL,
	modo NUMBER(5, 0) default 0 NOT NULL,
	intervalo NUMBER(10, 0) default 300 NOT NULL,
	id_os NUMBER(10, 0) default 0,
	os_version VARCHAR2(100) default '',
	agent_version VARCHAR2(100) default '',
	ultimo_contacto_remoto DATE default NULL,
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

CREATE OR REPLACE TRIGGER tagente_inc BEFORE INSERT ON tagente REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_s.nextval INTO :NEW.ID_AGENTE FROM dual; END; /

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

CREATE OR REPLACE TRIGGER tagente_datos_inc_inc BEFORE INSERT ON tagente_datos_inc REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_datos_inc_s.nextval INTO :NEW.ID_ADI FROM dual; END; /

CREATE TABLE tagente_datos_string (
	id_agente_modulo NUMBER(10, 0) NOT NULL PRIMARY KEY,
	datos NCLOB NOT NULL,
	utimestamp NUMBER(10, 0) default 0 NOT NULL 
);
CREATE INDEX tagente_datos_string_utsta_idx ON tagente_datos_string(utimestamp);

CREATE TABLE tagente_datos_log4x (
	id_tagente_datos_log4x NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_agente_modulo NUMBER(10, 0) default 0 NOT NULL,
	severity NCLOB NOT NULL,
	message NCLOB NOT NULL,
	stacktrace NCLOB NOT NULL,
	utimestamp NUMBER(10, 0) default 0 NOT NULL
);
CREATE INDEX tagente_datos_log4x_id_a_m_idx ON tagente_datos_log4x(id_agente_modulo);

CREATE SEQUENCE tagente_datos_log4x_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tagente_datos_log4x_inc BEFORE INSERT ON tagente_datos_log4x REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_datos_log4x_s.nextval INTO :NEW.ID_TAGENTE_DATOS_LOG4X FROM dual; END; /

CREATE TABLE tagente_estado (
	id_agente_estado NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_agente_modulo NUMBER(10, 0) default 0 NOT NULL,
	datos NCLOB default '' NOT NULL,
	timestamp DATE default NULL,
	estado NUMBER(10, 0) default 0 NOT NULL,
	id_agente NUMBER(10, 0) default 0 NOT NULL,
	last_try DATE default NULL,
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

CREATE OR REPLACE TRIGGER tagente_estado_inc BEFORE INSERT ON tagente_estado REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_estado_s.nextval INTO :NEW.ID_AGENTE_ESTADO FROM dual; END; /

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
	descripcion NCLOB default '' NOT NULL,
	nombre NCLOB default '' NOT NULL,
	id_policy_module NUMBER(10, 0) default 0 NOT NULL,
	max NUMBER(19, 0) default 0 NOT NULL,
	min NUMBER(19, 0) default 0 NOT NULL,
	module_interval NUMBER(10, 0) default 0 NOT NULL,
	tcp_port NUMBER(10, 0) default 0 NOT NULL,
	tcp_send NCLOB default '',
	tcp_rcv NCLOB default '',
	snmp_community VARCHAR2(100) default '',
	snmp_oid VARCHAR2(255) default '0',
	ip_target VARCHAR2(100) default '',
	id_module_group NUMBER(10, 0) default 0 NOT NULL,
	flag NUMBER(5, 0) default 1 NOT NULL,
	id_modulo NUMBER(10, 0) default 0 NOT NULL,
	disabled NUMBER(5, 0) default 0 NOT NULL,
	id_export NUMBER(10, 0) default 0 NOT NULL,
	plugin_user NCLOB default '',
	plugin_pass NCLOB default '',
	plugin_parameter NCLOB,
	id_plugin NUMBER(10, 0) default 0,
	post_process BINARY_DOUBLE default NULL,
	prediction_module NUMBER(19, 0) default 0,
	max_timeout NUMBER(10, 0) default 0,
	custom_id VARCHAR2(255) default '',
	history_data  NUMBER(5, 0) default 1,
	min_warning BINARY_DOUBLE default 0,
	max_warning BINARY_DOUBLE default 0,
	min_critical BINARY_DOUBLE default 0,
	max_critical BINARY_DOUBLE default 0,
	min_ff_event INTEGER default 0,
	delete_pending NUMBER(5, 0) default 0 NOT NULL,
	policy_linked NUMBER(5, 0) default 0 NOT NULL,
	policy_adopted NUMBER(5, 0) default 0 NOT NULL,
	custom_string_1 NCLOB default '',
	custom_string_2 NCLOB default '',
	custom_string_3 NCLOB default '',
	custom_integer_1 NUMBER(10, 0) default 0,
	custom_integer_2 NUMBER(10, 0) default 0
);
CREATE INDEX tagente_modulo_id_agente_idx ON tagente_modulo(id_agente);
CREATE INDEX tagente_modulo_id_t_mod_idx ON tagente_modulo(id_tipo_modulo);
CREATE INDEX tagente_modulo_disabled_idx ON tagente_modulo(disabled);

CREATE SEQUENCE tagente_modulo_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tagente_modulo_inc BEFORE INSERT ON tagente_modulo REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_modulo_s.nextval INTO :NEW.ID_AGENTE_MODULO FROM dual; END; /

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
	al_field1 NCLOB default '' NOT NULL,
	al_field2 NCLOB default '' NOT NULL,
	al_field3 NCLOB default '' NOT NULL,
	description VARCHAR2(255) default '',
	alert_type NUMBER(5, 0) default 0 NOT NULL,
	agent VARCHAR2(100) default '',
	custom_oid VARCHAR2(200) default '',
	oid VARCHAR2(255) default '' NOT NULL,
	time_threshold NUMBER(10, 0) default 0 NOT NULL,
	times_fired NUMBER(5, 0) default 0 NOT NULL,
	last_fired DATE default NULL,
	max_alerts NUMBER(10, 0) default 1 NOT NULL,
	min_alerts NUMBER(10, 0) default 1 NOT NULL,
	internal_counter NUMBER(10, 0) default 0 NOT NULL,
	priority NUMBER(10, 0) default 0
);

CREATE SEQUENCE talert_snmp_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_snmp_inc BEFORE INSERT ON talert_snmp REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_snmp_s.nextval INTO :NEW.ID_AS FROM dual; END; /

CREATE TABLE talert_commands (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '' NOT NULL,
	command NCLOB default '',
	description NCLOB default '',
	internal NUMBER(10, 0) default 0
);

CREATE SEQUENCE talert_commands_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_commands_inc BEFORE INSERT ON talert_commands REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_commands_s.nextval INTO :NEW.ID FROM dual; END; /

CREATE TABLE talert_actions (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name NCLOB default '',
	id_alert_command NUMBER(10, 0) NOT NULL REFERENCES talert_commands(id)  ON DELETE CASCADE,
	field1 NCLOB default '' NOT NULL,
	field2 NCLOB default '',
	field3 NCLOB default '',
	id_group NUMBER(19, 0) default 0 NOT NULL,
	action_threshold NUMBER(19, 0) default 0 NOT NULL
);

CREATE SEQUENCE talert_actions_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_actions_inc BEFORE INSERT ON talert_actions REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_actions_s.nextval INTO :NEW.ID FROM dual; END; /

-- on update trigger
CREATE OR REPLACE TRIGGER talert_actions_update AFTER UPDATE OF ID ON talert_commands FOR EACH ROW BEGIN UPDATE talert_actions SET ID_ALERT_COMMAND = :NEW.ID WHERE ID_ALERT_COMMAND = :OLD.ID; END; /

CREATE TABLE talert_templates (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name NCLOB default '',
	description NCLOB,
	id_alert_action NUMBER(10, 0) REFERENCES talert_actions(id)  ON DELETE SET NULL,
	field1 NCLOB default '',
	field2 NCLOB default '',
	field3 NCLOB NOT NULL,
	type VARCHAR2(50), 
	value VARCHAR2(255) default '',
	matches_value NUMBER(5, 0) default 0,
	max_value BINARY_DOUBLE default NULL,
	min_value DOUBLE PRECISION default NULL,
	time_threshold NUMBER(10, 0) default 0 NOT NULL,
	max_alerts NUMBER(10, 0) default 1 NOT NULL,
	min_alerts NUMBER(10, 0) default 0 NOT NULL,
	time_from TIMESTAMP default to_date('00:00:00','hh24:mi:ss'), -- use to_char(time_from, 'hh24:mi:ss') function to retrieve info
	time_to TIMESTAMP default to_date('00:00:00','hh24:mi:ss'),   -- use to_char(time_to,   'hh24:mi:ss') function to retrieve info
	monday NUMBER(5, 0) default 1,
	tuesday NUMBER(5, 0) default 1,
	wednesday NUMBER(5, 0) default 1,
	thursday NUMBER(5, 0) default 1,
	friday NUMBER(5, 0) default 1,
	saturday NUMBER(5, 0) default 1,
	sunday NUMBER(5, 0) default 1,
	recovery_notify NUMBER(5, 0) default 0,
	field2_recovery NCLOB default '' NOT NULL,
	field3_recovery NCLOB NOT NULL,
	priority NUMBER(10, 0) default 0 NOT NULL,
	id_group NUMBER(10, 0) default 0 NOT NULL, 
	CONSTRAINT t_alert_templates_type_cons CHECK (type IN ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal', 'warning', 'critical', 'onchange', 'unknown'))
);
CREATE INDEX talert_templates_id_al_act_idx ON talert_templates(id_alert_action);

CREATE SEQUENCE talert_templates_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_templates_inc BEFORE INSERT ON talert_templates REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_templates_s.nextval INTO :NEW.ID FROM dual; END; /

-- on update trigger
CREATE OR REPLACE TRIGGER talert_templates_update AFTER UPDATE OF ID ON talert_actions FOR EACH ROW BEGIN UPDATE talert_templates SET ID_ALERT_ACTION = :NEW.ID WHERE ID_ALERT_ACTION = :OLD.ID; END; /

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

CREATE OR REPLACE TRIGGER talert_template_modules_inc BEFORE INSERT ON talert_template_modules REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_template_modules_s.nextval INTO :NEW.ID FROM dual; END; /

-- on update trigger
CREATE OR REPLACE TRIGGER talert_template_modules_update AFTER UPDATE OF ID_AGENTE_MODULO ON tagente_modulo FOR EACH ROW BEGIN UPDATE talert_template_modules SET ID_AGENT_MODULE = :NEW.ID_AGENTE_MODULO WHERE ID_AGENT_MODULE = :OLD.ID_AGENTE_MODULO; END; /

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_template_modules_update1 AFTER UPDATE OF ID ON talert_templates FOR EACH ROW BEGIN UPDATE talert_template_modules SET ID_ALERT_TEMPLATE = :NEW.ID WHERE ID_ALERT_TEMPLATE = :OLD.ID; END; /

CREATE TABLE talert_template_module_actions (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_alert_template_module NUMBER(10, 0) NOT NULL REFERENCES talert_template_modules(id) ON DELETE CASCADE, 
	id_alert_action NUMBER(10, 0) NOT NULL REFERENCES talert_actions(id) ON DELETE CASCADE, 
	fires_min NUMBER(10, 0) default 0 NOT NULL,
	fires_max NUMBER(10, 0) default 0 NOT NULL 
);

CREATE SEQUENCE talert_template_modu_actions_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_template_mod_action_inc BEFORE INSERT ON talert_template_module_actions REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_template_modu_actions_s.nextval INTO :NEW.ID FROM dual; END; /

-- on update trigger
CREATE OR REPLACE TRIGGER talert_template_mod_act_update AFTER UPDATE OF ID ON talert_template_modules FOR EACH ROW BEGIN UPDATE talert_template_module_actions SET ID_ALERT_TEMPLATE_MODULE = :NEW.ID WHERE ID_ALERT_TEMPLATE_MODULE = :OLD.ID; END; /

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_template_mod_ac_update1 AFTER UPDATE OF ID ON talert_actions FOR EACH ROW BEGIN UPDATE talert_template_module_actions SET ID_ALERT_ACTION = :NEW.ID WHERE ID_ALERT_ACTION = :OLD.ID; END; /

CREATE TABLE talert_compound (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(255) default '',
	description NCLOB,
	id_agent NUMBER(10, 0) NOT NULL REFERENCES tagente(id_agente) ON DELETE CASCADE, 
	time_threshold NUMBER(10, 0) default 0 NOT NULL,
	max_alerts NUMBER(10, 0) default 1 NOT NULL,
	min_alerts NUMBER(10, 0) default 0 NOT NULL,
	time_from TIMESTAMP default to_date('00:00:00','hh24:mi:ss'), -- use to_char(time_from, 'hh24:mi:ss') function to retrieve info
	time_to TIMESTAMP default to_date('00:00:00','hh24:mi:ss'),   -- use to_char(time_to,   'hh24:mi:ss') function to retrieve info
	monday NUMBER(5, 0) default 1,
	tuesday NUMBER(5, 0) default 1,
	wednesday NUMBER(5, 0) default 1,
	thursday NUMBER(5, 0) default 1,
	friday NUMBER(5, 0) default 1,
	saturday NUMBER(5, 0) default 1,
	sunday NUMBER(5, 0) default 1,
	recovery_notify NUMBER(5, 0) default 0,
	field2_recovery VARCHAR2(255) default '' NOT NULL,
	field3_recovery NCLOB NOT NULL,
	internal_counter NUMBER(10, 0) default 0,
	last_fired NUMBER(19, 0) default 0 NOT NULL,
	last_reference NUMBER(19, 0) default 0 NOT NULL,
	times_fired NUMBER(10, 0) default 0 NOT NULL,
	disabled NUMBER(5, 0) default 0,
	priority NUMBER(5, 0) default 0
);

CREATE SEQUENCE talert_compound_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_compound_inc BEFORE INSERT ON talert_compound REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_compound_s.nextval INTO :NEW.ID FROM dual; END; /

-- on update trigger
CREATE OR REPLACE TRIGGER talert_compound_update AFTER UPDATE OF ID_AGENTE ON tagente FOR EACH ROW BEGIN UPDATE talert_compound SET ID_AGENT = :NEW.ID_AGENTE WHERE ID_AGENT = :OLD.ID_AGENTE; END; /

CREATE TABLE talert_compound_elements (
	id_alert_compound NUMBER(10, 0) NOT NULL REFERENCES talert_compound(id) ON DELETE CASCADE, 
	id_alert_template_module NUMBER(10, 0) NOT NULL REFERENCES talert_template_modules(id) ON DELETE CASCADE, 
	operation VARCHAR2(10),
	"order" NUMBER(5, 0) default 0,
	CONSTRAINT talert_compound_elements_cons CHECK (operation IN ('NOP', 'AND','OR','XOR','NAND','NOR','NXOR'))
);
CREATE UNIQUE INDEX talert_compound_elements_idx ON talert_compound_elements(id_alert_compound);

-- on update trigger
CREATE OR REPLACE TRIGGER talert_compound_elem_update AFTER UPDATE OF ID ON talert_compound FOR EACH ROW BEGIN UPDATE talert_compound_elements SET ID_ALERT_COMPOUND = :NEW.ID WHERE ID_ALERT_COMPOUND = :OLD.ID; END; /

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_compound_elem_update1 AFTER UPDATE OF ID ON talert_template_modules FOR EACH ROW BEGIN UPDATE talert_compound_elements SET ID_ALERT_TEMPLATE_MODULE = :NEW.ID WHERE ID_ALERT_TEMPLATE_MODULE = :OLD.ID; END; /

CREATE TABLE talert_compound_actions (
	id NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_alert_compound NUMBER(10, 0) NOT NULL REFERENCES talert_compound(id) ON DELETE CASCADE,
	id_alert_action NUMBER(10, 0) NOT NULL REFERENCES talert_actions(id) ON DELETE CASCADE, 
	fires_min NUMBER(10, 0) default 0,
	fires_max NUMBER(10, 0) default 0
);

CREATE SEQUENCE talert_compound_actions_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_compound_actions_inc BEFORE INSERT ON talert_compound_actions REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_compound_actions_s.nextval INTO :NEW.ID FROM dual; END; /

-- on update trigger
CREATE OR REPLACE TRIGGER talert_compound_actions_update AFTER UPDATE OF ID ON talert_compound FOR EACH ROW BEGIN UPDATE talert_compound_actions SET ID_ALERT_COMPOUND = :NEW.ID WHERE ID_ALERT_COMPOUND = :OLD.ID; END; /

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_compound_action_update1 AFTER UPDATE OF ID ON talert_actions FOR EACH ROW BEGIN UPDATE talert_compound_actions SET ID_ALERT_ACTION = :NEW.ID WHERE ID_ALERT_ACTION = :OLD.ID; END; /

-- Priority : 0 - Maintance (grey)
-- Priority : 1 - Low (green)
-- Priority : 2 - Normal (blue)
-- Priority : 3 - Warning (yellow)
-- Priority : 4 - Critical (red)
CREATE TABLE tattachment (
	id_attachment NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_incidencia NUMBER(10, 0) default 0 NOT NULL,
	id_usuario VARCHAR2(60) default '' NOT NULL,
	filename VARCHAR2(255) default '' NOT NULL,
	description VARCHAR2(150) default '',
	"size" NUMBER(19, 0) default 0 NOT NULL
);

CREATE SEQUENCE tattachment_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tattachment_inc BEFORE INSERT ON tattachment REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tattachment_s.nextval INTO :NEW.ID_ATTACHMENT FROM dual; END; /

CREATE TABLE tconfig (
	id_config NUMBER(10, 0) NOT NULL PRIMARY KEY,
	token VARCHAR2(100) default '' NOT NULL,
	value VARCHAR2(100) default '' NOT NULL 
);

CREATE SEQUENCE tconfig_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tconfig_inc BEFORE INSERT ON tconfig REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tconfig_s.nextval INTO :NEW.ID_CONFIG FROM dual; END; /

CREATE TABLE tconfig_os (
	id_os NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '' NOT NULL,
	description VARCHAR2(250) default '',
	icon_name VARCHAR2(100) default ''
);

CREATE SEQUENCE tconfig_os_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tconfig_os_inc BEFORE INSERT ON tconfig_os REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tconfig_os_s.nextval INTO :NEW.ID_OS FROM dual; END; /

CREATE TABLE tevento (
	id_evento NUMBER(19, 0) NOT NULL PRIMARY KEY,
	id_agente NUMBER(10, 0) default 0 NOT NULL,
	id_usuario VARCHAR2(100) default '0' NOT NULL,
	id_grupo NUMBER(10, 0) default 0 NOT NULL,
	estado NUMBER(10, 0) default 0 NOT NULL,
	timestamp TIMESTAMP default NULL, 			-- use to_char(timestamp, 'hh24:mi:ss') function to retrieve info
	evento NCLOB default '' NOT NULL,
	utimestamp NUMBER(19, 0) default 0 NOT NULL,
	event_type VARCHAR2(50) default 'unknown',
	id_agentmodule NUMBER(10, 0) default 0 NOT NULL,
	id_alert_am NUMBER(10, 0) default 0 NOT NULL,
	criticity NUMBER(10, 0) default 0 NOT NULL,
	user_comment NCLOB NOT NULL,
	CONSTRAINT tevento_event_type_cons CHECK (event_type IN ('unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal'))
);
CREATE INDEX tevento_id_1_idx ON tevento(id_agente, id_evento);
CREATE INDEX tevento_id_2_idx ON tevento(utimestamp, id_evento);
CREATE INDEX tevento_id_agentmodule_idx ON tevento(id_agentmodule);

CREATE SEQUENCE tevento_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tevento_inc BEFORE INSERT ON tevento REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tevento_s.nextval INTO :NEW.ID_EVENTO FROM dual; END; /

-- Criticity: 0 - Maintance (grey)
-- Criticity: 1 - Informational (blue)
-- Criticity: 2 - Normal (green) (status 0)
-- Criticity: 3 - Warning (yellow) (status 2)
-- Criticity: 4 - Critical (red) (status 1)
CREATE TABLE tgrupo (
	id_grupo NUMBER(10, 0) NOT NULL PRIMARY KEY,
	nombre NCLOB default '' NOT NULL,
	icon VARCHAR2(50) default 'world',
	parent NUMBER(10, 0) default 0 NOT NULL,
	propagate NUMBER(5, 0) default 0,
	disabled NUMBER(5, 0) default 0,
	custom_id VARCHAR2(255) default ''
);

CREATE SEQUENCE tgrupo_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tgrupo_inc BEFORE INSERT ON tgrupo REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgrupo_s.nextval INTO :NEW.ID_GRUPO FROM dual; END; /

CREATE TABLE tincidencia (
	id_incidencia NUMBER(19, 0) NOT NULL PRIMARY KEY,
	inicio TIMESTAMP default NULL,
	cierre TIMESTAMP default NULL,
	titulo NCLOB default '' NOT NULL,
	descripcion NCLOB NOT NULL,
	id_usuario VARCHAR2(60) default '' NOT NULL,
	origen VARCHAR2(100) default '' NOT NULL,
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
CREATE OR REPLACE TRIGGER tincidencia_actualizacion_ts BEFORE UPDATE ON tincidencia FOR EACH ROW BEGIN select CURRENT_TIMESTAMP into :NEW.ACTUALIZACION from dual; END; /

CREATE SEQUENCE tincidencia_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tincidencia_inc BEFORE INSERT ON tincidencia REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tincidencia_s.nextval INTO :NEW.ID_INCIDENCIA FROM dual; END; /

CREATE TABLE tlanguage (
	id_language VARCHAR2(6) default '' NOT NULL,
	name VARCHAR2(100) default '' NOT NULL
);

CREATE TABLE tlink (
	id_link NUMBER(10, 0) NOT NULL PRIMARY KEY,
	name VARCHAR2(100) default '' NOT NULL,
	link VARCHAR2(255) default '' NOT NULL
);

CREATE SEQUENCE tlink_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tlink_inc BEFORE INSERT ON tlink REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tlink_s.nextval INTO :NEW.ID_LINK FROM dual; END; /

CREATE TABLE tmensajes (
	id_mensaje NUMBER(10, 0) NOT NULL PRIMARY KEY,
	id_usuario_origen VARCHAR2(60) default '' NOT NULL,
	id_usuario_destino VARCHAR2(60) default '' NOT NULL,
	mensaje NCLOB NOT NULL,
	timestamp NUMBER(19, 0) default 0 NOT NULL,
	subject VARCHAR2(255) default '' NOT NULL,
	estado NUMBER(10, 0) default 0 NOT NULL 
);

CREATE SEQUENCE tmensajes_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER tmensajes_inc BEFORE INSERT ON tmensajes REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmensajes_s.nextval INTO :NEW.ID_MENSAJE FROM dual; END; /

