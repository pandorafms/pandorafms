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
CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP (oracletime IN DATE DEFAULT NULL ) RETURN INTEGER AS utcdate DATE; unixtime INTEGER; BEGIN IF (oracletime IS NULL) THEN utcdate := SYS_EXTRACT_UTC(SYSTIMESTAMP); ELSE utcdate := oracletime; END IF; unixtime := (utcdate - to_date('19700101','YYYYMMDD')) * 86400; RETURN unixtime; END;;
CREATE OR REPLACE FUNCTION NOW RETURN TIMESTAMP AS t_now TIMESTAMP; BEGIN SELECT LOCALTIMESTAMP INTO t_now FROM dual; RETURN t_now; END;;

CREATE OR REPLACE FUNCTION FROM_UNIXTIME (p_unix_ts IN NUMBER) RETURN TIMESTAMP IS l_date TIMESTAMP; BEGIN l_date := date '1970-01-01' + p_unix_ts/60/60/24; RETURN l_date; END;;

CREATE OR REPLACE FUNCTION DAYOFWEEK (p_date IN TIMESTAMP) RETURN NUMBER IS l_number_week NUMBER; BEGIN l_number_week := to_char(p_date, 'd'); RETURN l_number_week; END;;

CREATE OR REPLACE FUNCTION TIME (p_date IN TIMESTAMP) RETURN VARCHAR2 IS l_time VARCHAR2(20); BEGIN l_time := TO_CHAR(p_date,'hh24:mi:ss'); RETURN l_time; END;;

-- Procedure for retrieve PK information after an insert statement
CREATE OR REPLACE PROCEDURE insert_id (table_name IN VARCHAR2, sql_insert IN VARCHAR2, id OUT NUMBER ) IS v_count NUMBER; BEGIN EXECUTE IMMEDIATE sql_insert; EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM user_sequences WHERE sequence_name = UPPER(''' || table_name || '_s'')' INTO v_count; IF v_count >= 1 THEN EXECUTE IMMEDIATE 'SELECT ' || table_name || '_s.currval FROM DUAL' INTO id; ELSE id := 0; END IF; EXCEPTION WHEN others THEN RAISE_APPLICATION_ERROR(-20001, 'ERROR on insert_id procedure, please check input parameters or procedure logic.'); END insert_id;;

-- Procedure for update curr val of sequence
CREATE OR REPLACE PROCEDURE update_currval (table_name IN VARCHAR2, column_name IN VARCHAR2 ) IS key_max NUMBER; BEGIN EXECUTE IMMEDIATE 'SELECT MAX(' || column_name || ') FROM ' || table_name INTO key_max; IF (key_max IS NULL) THEN key_max := 1; ELSE key_max := key_max + 1; END IF; EXECUTE IMMEDIATE 'DROP SEQUENCE ' || table_name || '_s'; EXECUTE IMMEDIATE 'CREATE SEQUENCE ' || table_name || '_s INCREMENT BY 1 START WITH ' || key_max; EXECUTE IMMEDIATE 'SELECT ' || table_name || '_s.nextval FROM dual'; END update_currval;;

-- Type which constists in a table of VARCHAR2
CREATE OR REPLACE TYPE t_varchar2_tab AS TABLE OF VARCHAR2(4000);

-- Function that uses the 't_varchar2_tab' type to concat elements and return them as 'CLOB', without the limitation of 'LISTAGG()' (VARCHAR2(4000))
CREATE OR REPLACE FUNCTION tab_to_string (v_varchar2_tab IN t_varchar2_tab, v_delimiter IN CLOB DEFAULT ',') RETURN CLOB IS v_tmp_clob CLOB; v_clob CLOB := ''; BEGIN IF (v_varchar2_tab.COUNT > 0) THEN FOR i IN v_varchar2_tab.FIRST .. v_varchar2_tab.LAST LOOP IF (i != v_varchar2_tab.FIRST) THEN v_clob := v_clob || v_delimiter; END IF; v_tmp_clob := v_varchar2_tab(i); v_clob := v_clob || v_tmp_clob; END LOOP; END IF; RETURN v_clob; END tab_to_string;;

-- ---------------------------------------------------------------------
-- Table `taddress`
-- ---------------------------------------------------------------------
CREATE TABLE taddress (
	id_a NUMBER(10, 0) PRIMARY KEY,
	ip VARCHAR(60) DEFAULT '',
	ip_pack NUMBER(10, 0) DEFAULT 0 
);
CREATE INDEX taddress_ip_idx ON taddress(ip);

CREATE SEQUENCE taddress_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER taddress_inc BEFORE INSERT ON taddress REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT taddress_s.nextval INTO :NEW.id_a FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `taddress_agent`
-- ---------------------------------------------------------------------
CREATE TABLE taddress_agent (
	id_ag NUMBER(19, 0) PRIMARY KEY,
	id_a NUMBER(19, 0) DEFAULT 0,
	id_agent NUMBER(19, 0) DEFAULT 0 
);

CREATE SEQUENCE taddress_agent_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER taddress_agent_inc BEFORE INSERT ON taddress_agent REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT taddress_agent_s.nextval INTO :NEW.id_ag FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tagente`
-- ---------------------------------------------------------------------
CREATE TABLE tagente (
	id_agente NUMBER(10, 0) PRIMARY KEY,
	nombre VARCHAR2(600) DEFAULT '',
	direccion VARCHAR2(100) DEFAULT '',
	comentarios VARCHAR2(255) DEFAULT '',
	id_grupo NUMBER(10, 0) DEFAULT 0,
	ultimo_contacto TIMESTAMP DEFAULT to_timestamp('1970-01-01 00:00:00', 'yyyy-mm-dd hh24:mi:ss'),
	modo NUMBER(5, 0) DEFAULT 0,
	intervalo NUMBER(10, 0) DEFAULT 300,
	id_os NUMBER(10, 0) DEFAULT 0,
	os_version VARCHAR2(100) DEFAULT '',
	agent_version VARCHAR2(100) DEFAULT '',
	ultimo_contacto_remoto TIMESTAMP DEFAULT to_timestamp('1970-01-01 00:00:00', 'yyyy-mm-dd hh24:mi:ss'),
	disabled NUMBER(5, 0) DEFAULT 0,
	remote NUMBER(5, 0) DEFAULT 0,
	id_parent NUMBER(10, 0) DEFAULT 0,
	custom_id VARCHAR2(255) DEFAULT '',
	server_name VARCHAR2(100) DEFAULT '',
	cascade_protection NUMBER(5, 0) DEFAULT 0,
	--number of hours of diference with the server timezone
	timezone_offset NUMBER(5, 0) DEFAULT 0,
	--path in the server to the image of the icon representing the agent
	icon_path VARCHAR2(127) DEFAULT '',
	--set it to one to update the position data (altitude, longitude, latitude) whenetting information from the agent or to 0 to keep the last value and don\'t update it
	update_gis_data NUMBER(5, 0) DEFAULT 1,
	url_address CLOB DEFAULT '',
	quiet NUMBER(5, 0) DEFAULT 0,
	normal_count NUMBER(20, 0) DEFAULT 0,
	warning_count NUMBER(20, 0) DEFAULT 0,
	critical_count NUMBER(20, 0) DEFAULT 0,
	unknown_count NUMBER(20, 0) DEFAULT 0,
	notinit_count NUMBER(20, 0) DEFAULT 0,
	total_count NUMBER(20, 0) DEFAULT 0,
	fired_count NUMBER(20, 0) DEFAULT 0,
	update_module_count NUMBER(5, 0) DEFAULT 0,
	update_alert_count NUMBER(5, 0) DEFAULT 0,
	transactional_agent NUMBER(5,0) DEFAULT 0
);
CREATE INDEX tagente_nombre_idx ON tagente(nombre);
CREATE INDEX tagente_direccion_idx ON tagente(direccion);
CREATE INDEX tagente_disabled_idx ON tagente(disabled);
CREATE INDEX tagente_id_grupo_idx ON tagente(id_grupo);

CREATE SEQUENCE tagente_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tagente_inc BEFORE INSERT ON tagente REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_s.nextval INTO :NEW.id_agente FROM dual; END;;

-- -----------------------------------------------------
-- Table `tagente_datos`
-- -----------------------------------------------------
CREATE TABLE tagente_datos (
	id_agente_modulo NUMBER(10, 0) DEFAULT 0,
	datos BINARY_DOUBLE DEFAULT NULL,
	utimestamp NUMBER(10, 0) DEFAULT 0
);
CREATE INDEX tagente_datos_id_agent_mod_idx ON tagente_datos(id_agente_modulo);
CREATE INDEX tagente_datos_utimestamp_idx ON tagente_datos(utimestamp);

-- This sequence will not work with the 'insert_id' procedure

-- -----------------------------------------------------
-- Table `tagente_datos_inc`
-- -----------------------------------------------------
CREATE TABLE tagente_datos_inc (
	id_agente_modulo NUMBER(10, 0) DEFAULT 0,
	datos BINARY_DOUBLE DEFAULT NULL,
	utimestamp NUMBER(10, 0) DEFAULT 0
);
CREATE INDEX tagente_datos_inc_id_ag_mo_idx ON tagente_datos_inc(id_agente_modulo);

-- This sequence will not work with the 'insert_id' procedure

-- -----------------------------------------------------
-- Table `tagente_datos_string`
-- -----------------------------------------------------
CREATE TABLE tagente_datos_string (
	id_agente_modulo NUMBER(10, 0),
	datos CLOB,
	utimestamp NUMBER(10, 0) DEFAULT 0 
);
CREATE INDEX tagente_datos_string_utsta_idx ON tagente_datos_string(utimestamp);

-- This sequence will not work with the 'insert_id' procedure

-- -----------------------------------------------------
-- Table `tagente_datos_log4x`
-- -----------------------------------------------------
CREATE TABLE tagente_datos_log4x (
	id_tagente_datos_log4x NUMBER(19, 0) PRIMARY KEY,
	id_agente_modulo NUMBER(10, 0) DEFAULT 0,
	severity CLOB,
	message CLOB,
	stacktrace CLOB,
	utimestamp NUMBER(10, 0) DEFAULT 0
);
CREATE INDEX tagente_datos_log4x_id_a_m_idx ON tagente_datos_log4x(id_agente_modulo);

CREATE SEQUENCE tagente_datos_log4x_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tagente_datos_log4x_inc BEFORE INSERT ON tagente_datos_log4x REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_datos_log4x_s.nextval INTO :NEW.id_tagente_datos_log4x FROM dual; END;;

-- -----------------------------------------------------
-- Table `tagente_estado`
-- -----------------------------------------------------
CREATE TABLE tagente_estado (
	id_agente_estado NUMBER(10, 0) PRIMARY KEY,
	id_agente_modulo NUMBER(10, 0) DEFAULT 0,
	datos CLOB DEFAULT '',
	timestamp TIMESTAMP DEFAULT NULL,
	estado NUMBER(10, 0) DEFAULT 0,
	known_status NUMBER(10, 0) DEFAULT 0,
	id_agente NUMBER(10, 0) DEFAULT 0,
	last_try TIMESTAMP DEFAULT NULL,
	utimestamp NUMBER(19, 0) DEFAULT 0,
	current_interval NUMBER(10, 0) DEFAULT 0,
	running_by NUMBER(10, 0) DEFAULT 0,
	last_execution_try NUMBER(19, 0) DEFAULT 0,
	status_changes NUMBER(10, 0) DEFAULT 0,
	last_status NUMBER(10, 0) DEFAULT 0,
	last_known_status NUMBER(10, 0) DEFAULT 0,
	last_error NUMBER(10, 0) DEFAULT 0,
	ff_start_utimestamp NUMBER(10, 0) DEFAULT 0
);
CREATE INDEX tagente_estado_id_agente_idx ON tagente_estado(id_agente);
CREATE INDEX tagente_estado_estado_idx ON tagente_estado(estado);
CREATE INDEX tagente_estado_curr_inter_idx ON tagente_estado(current_interval);
CREATE INDEX tagente_estado_running_by_idx ON tagente_estado(running_by);
CREATE INDEX tagente_estado_last_ex_try_idx ON tagente_estado(last_execution_try);

CREATE SEQUENCE tagente_estado_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tagente_estado_inc BEFORE INSERT ON tagente_estado REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_estado_s.nextval INTO :NEW.id_agente_estado FROM dual; END;;

-- Probably last_execution_try index is not useful and loads more than benefits

-- id_modulo now uses tmodule 
-- ---------------------------
-- 1 - Data server modules (agent related modules)
-- 2 - Network server modules
-- 4 - Plugin server
-- 5 - Predictive server
-- 6 - WMI server
-- 7 - WEB Server (enteprise)

-- -----------------------------------------------------
-- Table `tagente_modulo`
-- -----------------------------------------------------
CREATE TABLE tagente_modulo (
	id_agente_modulo NUMBER(10, 0) PRIMARY KEY,
	id_agente NUMBER(10, 0) DEFAULT 0,
	id_tipo_modulo NUMBER(10, 0) DEFAULT 0,
	descripcion CLOB DEFAULT '',
	extended_info CLOB DEFAULT '',
	nombre VARCHAR2(4000) DEFAULT '',
	unit VARCHAR2(100) DEFAULT '',
	id_policy_module NUMBER(10, 0) DEFAULT 0,
	max NUMBER(19, 0) DEFAULT 0,
	min NUMBER(19, 0) DEFAULT 0,
	module_interval NUMBER(10, 0) DEFAULT 0,
	module_ff_interval NUMBER(10, 0) DEFAULT 0,
	cron_interval VARCHAR2(100) DEFAULT '',
	tcp_port NUMBER(10, 0) DEFAULT 0,
	tcp_send CLOB DEFAULT '',
	tcp_rcv CLOB DEFAULT '',
	snmp_community VARCHAR2(100) DEFAULT '',
	snmp_oid VARCHAR2(255) DEFAULT '0',
	ip_target VARCHAR2(100) DEFAULT '',
	id_module_group NUMBER(10, 0) DEFAULT 0,
	flag NUMBER(5, 0) DEFAULT 1,
	id_modulo NUMBER(10, 0) DEFAULT 0,
	disabled NUMBER(5, 0) DEFAULT 0,
	id_export NUMBER(10, 0) DEFAULT 0,
	plugin_user CLOB DEFAULT '',
	plugin_pass CLOB DEFAULT '',
	plugin_parameter CLOB DEFAULT '',
	id_plugin NUMBER(10, 0) DEFAULT 0,
	post_process BINARY_DOUBLE DEFAULT 0,
	prediction_module NUMBER(19, 0) DEFAULT 0,
	max_timeout NUMBER(10, 0) DEFAULT 0,
	max_retries NUMBER(10, 0) DEFAULT 0,
	custom_id VARCHAR2(255) DEFAULT '',
	history_data NUMBER(5, 0) DEFAULT 1,
	min_warning BINARY_DOUBLE DEFAULT 0,
	max_warning BINARY_DOUBLE DEFAULT 0,
	str_warning CLOB DEFAULT '',
	min_critical BINARY_DOUBLE DEFAULT 0,
	max_critical BINARY_DOUBLE DEFAULT 0,
	str_critical CLOB DEFAULT '',
	min_ff_event INTEGER DEFAULT 0,
	delete_pending NUMBER(5, 0) DEFAULT 0,
	policy_linked NUMBER(5, 0) DEFAULT 0,
	policy_adopted NUMBER(5, 0) DEFAULT 0,
	custom_string_1 CLOB DEFAULT '',
	custom_string_2 CLOB DEFAULT '',
	custom_string_3 CLOB DEFAULT '',
	custom_integer_1 NUMBER(10, 0) DEFAULT 0,
	custom_integer_2 NUMBER(10, 0) DEFAULT 0,
	wizard_level VARCHAR2(100) DEFAULT 'nowizard',
	macros CLOB DEFAULT '',
	critical_instructions CLOB DEFAULT '',
	warning_instructions CLOB DEFAULT '',
	unknown_instructions CLOB DEFAULT '',
	quiet NUMBER(5, 0) DEFAULT 0,
	critical_inverse NUMBER(1, 0) DEFAULT 0,
	warning_inverse NUMBER(1, 0) DEFAULT 0,
	id_category NUMBER(10, 0) DEFAULT 0,
	disabled_types_event CLOB DEFAULT '',
	module_macros CLOB DEFAULT '',
	min_ff_event_normal INTEGER DEFAULT 0,
	min_ff_event_warning INTEGER DEFAULT 0,
	min_ff_event_critical INTEGER DEFAULT 0,
	each_ff NUMBER(1, 0) DEFAULT 0,
	ff_timeout INTEGER DEFAULT 0,
	dynamic_interval INTEGER default 0,
	dynamic_max INTEGER default 0,
	dynamic_min INTEGER default 0,
	dynamic_next INTEGER NOT NULL default 0,
	dynamic_two_tailed INTEGER default 0,
	prediction_sample_window INTEGER default 0,
	prediction_samples INTEGER default 0,
	prediction_threshold INTEGER default 0,
	parent_module_id NUMBER(10, 0),
	CONSTRAINT t_agente_modulo_wizard_cons CHECK (wizard_level IN ('basic','advanced','nowizard'))
);
CREATE INDEX tagente_modulo_id_agente_idx ON tagente_modulo(id_agente);
CREATE INDEX tagente_modulo_id_t_mod_idx ON tagente_modulo(id_tipo_modulo);
CREATE INDEX tagente_modulo_disabled_idx ON tagente_modulo(disabled);

CREATE SEQUENCE tagente_modulo_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tagente_modulo_inc BEFORE INSERT ON tagente_modulo REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagente_modulo_s.nextval INTO :NEW.id_agente_modulo FROM dual; END;;

-- snmp_oid is also used for WMI query

-- -----------------------------------------------------
-- Table `tagent_access`
-- -----------------------------------------------------
CREATE TABLE tagent_access (
	id_agent NUMBER(10, 0) DEFAULT 0,
	utimestamp NUMBER(19, 0) DEFAULT 0
);
CREATE INDEX tagent_access_id_agent_idx ON tagent_access(id_agent);
CREATE INDEX tagent_access_utimestamp_idx ON tagent_access(utimestamp);

-- This sequence will not work with the 'insert_id' procedure

-- -----------------------------------------------------
-- Table `talert_snmp`
-- -----------------------------------------------------
CREATE TABLE talert_snmp (
	id_as NUMBER(10, 0) PRIMARY KEY,
	id_alert NUMBER(10, 0) DEFAULT 0,
	al_field1 CLOB DEFAULT '',
	al_field2 CLOB DEFAULT '',
	al_field3 CLOB DEFAULT '',
	al_field4 CLOB DEFAULT '',
	al_field5 CLOB DEFAULT '',
	al_field6 CLOB DEFAULT '',
	al_field7 CLOB DEFAULT '',
	al_field8 CLOB DEFAULT '',
	al_field9 CLOB DEFAULT '',
	al_field10 CLOB DEFAULT '',
	description VARCHAR2(255) DEFAULT '',
	alert_type NUMBER(5, 0) DEFAULT 0,
	agent VARCHAR2(100) DEFAULT '',
	custom_oid CLOB DEFAULT '',
	oid VARCHAR2(255) DEFAULT '',
	time_threshold NUMBER(10, 0) DEFAULT 0,
	times_fired NUMBER(5, 0) DEFAULT 0,
	last_fired TIMESTAMP DEFAULT NULL,
	max_alerts NUMBER(10, 0) DEFAULT 1,
	min_alerts NUMBER(10, 0) DEFAULT 1,
	internal_counter NUMBER(10, 0) DEFAULT 0,
	priority NUMBER(10, 0) DEFAULT 0,
	"_snmp_f1_" CLOB DEFAULT '', 
	"_snmp_f2_" CLOB DEFAULT '', 
	"_snmp_f3_" CLOB DEFAULT '',
	"_snmp_f4_" CLOB DEFAULT '', 
	"_snmp_f5_" CLOB DEFAULT '', 
	"_snmp_f6_" CLOB DEFAULT '',
	"_snmp_f7_" CLOB DEFAULT '',
	"_snmp_f8_" CLOB DEFAULT '',
	"_snmp_f9_" CLOB DEFAULT '',
	"_snmp_f10_" CLOB DEFAULT '',
	"_snmp_f11_" CLOB DEFAULT '',
	"_snmp_f12_" CLOB DEFAULT '',
	"_snmp_f13_" CLOB DEFAULT '',
	"_snmp_f14_" CLOB DEFAULT '',
	"_snmp_f15_" CLOB DEFAULT '',
	"_snmp_f16_" CLOB DEFAULT '',
	"_snmp_f17_" CLOB DEFAULT '',
	"_snmp_f18_" CLOB DEFAULT '',
	"_snmp_f19_" CLOB DEFAULT '',
	"_snmp_f20_" CLOB DEFAULT '',
	trap_type NUMBER(10, 0) DEFAULT -1,
	single_value VARCHAR2(255) DEFAULT '',
	position NUMBER(10, 0) DEFAULT 0,
	id_group NUMBER(10, 0) DEFAULT 0,
	order_1 NUMBER(10, 0) DEFAULT 1 ,
	order_2 NUMBER(10, 0) DEFAULT 2 ,
	order_3 NUMBER(10, 0) DEFAULT 3 ,
	order_4 NUMBER(10, 0) DEFAULT 4 ,
	order_5 NUMBER(10, 0) DEFAULT 5 ,
	order_6 NUMBER(10, 0) DEFAULT 6 ,
	order_7 NUMBER(10, 0) DEFAULT 7 ,
	order_8 NUMBER(10, 0) DEFAULT 8 ,
	order_9 NUMBER(10, 0) DEFAULT 9 ,
	order_10 NUMBER(10, 0) DEFAULT 10 ,
	order_11 NUMBER(10, 0) DEFAULT 11 ,
	order_12 NUMBER(10, 0) DEFAULT 12 ,
	order_13 NUMBER(10, 0) DEFAULT 13 ,
	order_14 NUMBER(10, 0) DEFAULT 14 ,
	order_15 NUMBER(10, 0) DEFAULT 15 ,
	order_16 NUMBER(10, 0) DEFAULT 16 ,
	order_17 NUMBER(10, 0) DEFAULT 17 ,
	order_18 NUMBER(10, 0) DEFAULT 18 ,
	order_19 NUMBER(10, 0) DEFAULT 19 ,
	order_20 NUMBER(10, 0) DEFAULT 20 
);

CREATE SEQUENCE talert_snmp_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER talert_snmp_inc BEFORE INSERT ON talert_snmp REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_snmp_s.nextval INTO :NEW.id_as FROM dual; END;;

-- -----------------------------------------------------
-- Table `talert_commands`
-- -----------------------------------------------------
CREATE TABLE talert_commands (
	id NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(100) DEFAULT '',
	command CLOB DEFAULT '',
	description CLOB DEFAULT '',
	internal NUMBER(10, 0) DEFAULT 0,
	fields_descriptions CLOB DEFAULT '',
	fields_values CLOB DEFAULT ''
);

CREATE SEQUENCE talert_commands_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER talert_commands_inc BEFORE INSERT ON talert_commands REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_commands_s.nextval INTO :NEW.id FROM dual; END;;

-- -----------------------------------------------------
-- Table `talert_actions`
-- -----------------------------------------------------
CREATE TABLE talert_actions (
	id NUMBER(10, 0) PRIMARY KEY,
	name CLOB DEFAULT '',
	id_alert_command NUMBER(10, 0) DEFAULT 0 REFERENCES talert_commands(id)  ON DELETE CASCADE,
	field1 CLOB DEFAULT '',
	field2 CLOB DEFAULT '',
	field3 CLOB DEFAULT '',
	field4 CLOB DEFAULT '',
	field5 CLOB DEFAULT '',
	field6 CLOB DEFAULT '',
	field7 CLOB DEFAULT '',
	field8 CLOB DEFAULT '',
	field9 CLOB DEFAULT '',
	field10 CLOB DEFAULT '',
	id_group NUMBER(19, 0) DEFAULT 0,
	action_threshold NUMBER(19, 0) DEFAULT 0,
	field1_recovery CLOB DEFAULT '',
	field2_recovery CLOB DEFAULT '',
	field3_recovery CLOB DEFAULT '',
	field4_recovery CLOB DEFAULT '',
	field5_recovery CLOB DEFAULT '',
	field6_recovery CLOB DEFAULT '',
	field7_recovery CLOB DEFAULT '',
	field8_recovery CLOB DEFAULT '',
	field9_recovery CLOB DEFAULT '',
	field10_recovery CLOB DEFAULT ''
);

CREATE SEQUENCE talert_actions_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER talert_actions_inc BEFORE INSERT ON talert_actions REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_actions_s.nextval INTO :NEW.id FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_actions_update AFTER UPDATE OF id ON talert_commands FOR EACH ROW BEGIN UPDATE talert_actions SET id_alert_command = :NEW.id WHERE id_alert_command = :OLD.id; END;;

-- -----------------------------------------------------
-- Table `talert_templates`
-- -----------------------------------------------------
-- use to_char(time_from, 'hh24:mi:ss') function to retrieve time_from field info
-- use to_char(time_to,   'hh24:mi:ss') function to retrieve time_to field info
CREATE TABLE talert_templates (
	id NUMBER(10, 0) PRIMARY KEY,
	name CLOB DEFAULT '',
	description CLOB DEFAULT '',
	id_alert_action NUMBER(10, 0) REFERENCES talert_actions(id) ON DELETE SET NULL,
	field1 CLOB DEFAULT '',
	field2 CLOB DEFAULT '',
	field3 CLOB DEFAULT '',
	field4 CLOB DEFAULT '',
	field5 CLOB DEFAULT '',
	field6 CLOB DEFAULT '',
	field7 CLOB DEFAULT '',
	field8 CLOB DEFAULT '',
	field9 CLOB DEFAULT '',
	field10 CLOB DEFAULT '',
	type VARCHAR2(50), 
	value VARCHAR2(255) DEFAULT '',
	matches_value NUMBER(5, 0) DEFAULT 0,
	max_value DOUBLE PRECISION DEFAULT NULL,
	min_value DOUBLE PRECISION DEFAULT NULL,
	time_threshold NUMBER(10, 0) DEFAULT 0,
	max_alerts NUMBER(10, 0) DEFAULT 1,
	min_alerts NUMBER(10, 0) DEFAULT 0,
	time_from TIMESTAMP DEFAULT to_date('00:00:00','hh24:mi:ss'), 
	time_to TIMESTAMP DEFAULT to_date('00:00:00','hh24:mi:ss'),   
	monday NUMBER(5, 0) DEFAULT 1,
	tuesday NUMBER(5, 0) DEFAULT 1,
	wednesday NUMBER(5, 0) DEFAULT 1,
	thursday NUMBER(5, 0) DEFAULT 1,
	friday NUMBER(5, 0) DEFAULT 1,
	saturday NUMBER(5, 0) DEFAULT 1,
	sunday NUMBER(5, 0) DEFAULT 1,
	recovery_notify NUMBER(5, 0) DEFAULT 0,
	field1_recovery CLOB DEFAULT '',
	field2_recovery CLOB DEFAULT '',
	field3_recovery CLOB DEFAULT '',
	field4_recovery CLOB DEFAULT '',
	field5_recovery CLOB DEFAULT '',
	field6_recovery CLOB DEFAULT '',
	field7_recovery CLOB DEFAULT '',
	field8_recovery CLOB DEFAULT '',
	field9_recovery CLOB DEFAULT '',
	field10_recovery CLOB DEFAULT '',
	priority NUMBER(10, 0) DEFAULT 0,
	id_group NUMBER(10, 0) DEFAULT 0, 
	special_day NUMBER(5, 0) DEFAULT 0,
	wizard_level VARCHAR2(100) DEFAULT 'nowizard',
	min_alerts_reset_counter NUMBER(5, 0) DEFAULT 0,
	CONSTRAINT t_alert_templates_type_cons CHECK (type IN ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal', 'warning', 'critical', 'onchange', 'unknown', 'always')),
	CONSTRAINT t_alert_templates_wizard_cons CHECK (wizard_level IN ('basic','advanced','nowizard'))
);
CREATE INDEX talert_templates_id_al_act_idx ON talert_templates(id_alert_action);

CREATE SEQUENCE talert_templates_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER talert_templates_inc BEFORE INSERT ON talert_templates REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_templates_s.nextval INTO :NEW.id FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_templates_update AFTER UPDATE OF id ON talert_actions FOR EACH ROW BEGIN UPDATE talert_templates SET id_alert_action = :NEW.id WHERE id_alert_action = :OLD.id; END;;

-- -----------------------------------------------------
-- Table `talert_template_modules`
-- -----------------------------------------------------
CREATE TABLE talert_template_modules (
	id NUMBER(10, 0) PRIMARY KEY,
	id_agent_module NUMBER(10, 0) REFERENCES tagente_modulo(id_agente_modulo) ON DELETE CASCADE,
	id_alert_template NUMBER(10, 0) REFERENCES talert_templates(id) ON DELETE CASCADE, 
	id_policy_alerts NUMBER(10, 0) DEFAULT 0,
	internal_counter NUMBER(10, 0) DEFAULT 0,
	last_fired NUMBER(19, 0) DEFAULT 0,
	last_reference NUMBER(19, 0) DEFAULT 0,
	times_fired NUMBER(10, 0) DEFAULT 0,
	disabled NUMBER(5, 0) DEFAULT 0,
	standby NUMBER(5, 0) DEFAULT 0,
	priority NUMBER(10, 0) DEFAULT 0,
	force_execution NUMBER(5, 0) DEFAULT 0
);
CREATE UNIQUE INDEX talert_template_modules_idx ON talert_template_modules(id_agent_module);

CREATE SEQUENCE talert_template_modules_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_template_modules_inc BEFORE INSERT ON talert_template_modules REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_template_modules_s.nextval INTO :NEW.id FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_template_modules_update AFTER UPDATE OF id_agente_modulo ON tagente_modulo FOR EACH ROW BEGIN UPDATE talert_template_modules SET id_agent_module = :NEW.id_agente_modulo WHERE id_agent_module = :OLD.id_agente_modulo; END;;

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_template_module_update1 AFTER UPDATE OF id ON talert_templates FOR EACH ROW BEGIN UPDATE talert_template_modules SET id_alert_template = :NEW.id WHERE id_alert_template = :OLD.id; END;;

-- -----------------------------------------------------
-- Table `talert_template_module_actions`
-- -----------------------------------------------------
CREATE TABLE talert_template_module_actions (
	id NUMBER(10, 0) PRIMARY KEY,
	id_alert_template_module NUMBER(10, 0) REFERENCES talert_template_modules(id) ON DELETE CASCADE, 
	id_alert_action NUMBER(10, 0) REFERENCES talert_actions(id) ON DELETE CASCADE, 
	fires_min NUMBER(10, 0) DEFAULT 0,
	fires_max NUMBER(10, 0) DEFAULT 0,
	module_action_threshold NUMBER(10, 0) DEFAULT 0,
  	last_execution NUMBER(18, 0) DEFAULT 0 
);
-- This sequence will not work with the 'insert_id' procedure
CREATE SEQUENCE talert_template_modu_actions_s INCREMENT BY 1 START WITH 1;

CREATE OR REPLACE TRIGGER talert_template_mod_action_inc BEFORE INSERT ON talert_template_module_actions REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_template_modu_actions_s.nextval INTO :NEW.id FROM dual; END;;

-- on update trigger
CREATE OR REPLACE TRIGGER talert_template_mod_act_update AFTER UPDATE OF id ON talert_template_modules FOR EACH ROW BEGIN UPDATE talert_template_module_actions SET id_alert_template_module = :NEW.id WHERE id_alert_template_module = :OLD.id; END;;

-- on update trigger 1
CREATE OR REPLACE TRIGGER talert_template_mod_ac_update1 AFTER UPDATE OF id ON talert_actions FOR EACH ROW BEGIN UPDATE talert_template_module_actions SET id_alert_action = :NEW.id WHERE id_alert_action = :OLD.id; END;;

-- -----------------------------------------------------
-- Table `talert_special_days`
-- -----------------------------------------------------
CREATE TABLE talert_special_days (
	id NUMBER(10,0) PRIMARY KEY,
	id_group NUMBER(10, 0) DEFAULT 0, 
	"date" DATE,
	same_day VARCHAR2(20) DEFAULT 'sunday',
	description CLOB,
	CONSTRAINT talert_special_days_sday_cons CHECK (same_day IN ('monday','tuesday','wednesday','thursday','friday','saturday','sunday'))
);

-- on update trigger
CREATE SEQUENCE talert_special_days_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER talert_special_days_inc BEFORE INSERT ON talert_special_days REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_special_days_s.nextval INTO :NEW.id FROM dual; END talert_special_days_inc;;

-- ---------------------------------------------------------------------
-- Table `tattachment`
-- ---------------------------------------------------------------------
-- Priority : 0 - Maintance (grey)
-- Priority : 1 - Low (green)
-- Priority : 2 - Normal (blue)
-- Priority : 3 - Warning (yellow)
-- Priority : 4 - Critical (red)
CREATE TABLE tattachment (
	id_attachment NUMBER(10, 0) PRIMARY KEY,
	id_incidencia NUMBER(10, 0) DEFAULT 0,
	id_usuario VARCHAR2(60) DEFAULT '',
	filename VARCHAR2(255) DEFAULT '',
	description VARCHAR2(150) DEFAULT '',
	"size" NUMBER(19, 0) DEFAULT 0
);

CREATE SEQUENCE tattachment_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tattachment_inc BEFORE INSERT ON tattachment REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tattachment_s.nextval INTO :NEW.id_attachment FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
CREATE TABLE tconfig (
	id_config NUMBER(10, 0) PRIMARY KEY,
	token VARCHAR2(100) DEFAULT '',
	value CLOB DEFAULT '' 
);

CREATE SEQUENCE tconfig_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tconfig_inc BEFORE INSERT ON tconfig REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tconfig_s.nextval INTO :NEW.id_config FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tconfig_os`
-- ---------------------------------------------------------------------
CREATE TABLE tconfig_os (
	id_os NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(100) DEFAULT '',
	description VARCHAR2(250) DEFAULT '',
	icon_name VARCHAR2(100) DEFAULT ''
);

CREATE SEQUENCE tconfig_os_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tconfig_os_inc BEFORE INSERT ON tconfig_os REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tconfig_os_s.nextval INTO :NEW.id_os FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tevento`
-- ---------------------------------------------------------------------
-- use to_char(timestamp, 'hh24:mi:ss') function to retrieve timestamp field info
CREATE TABLE tevento (
	id_evento NUMBER(19, 0) PRIMARY KEY,
	id_agente NUMBER(10, 0) DEFAULT 0,
	id_usuario VARCHAR2(100) DEFAULT '0',
	id_grupo NUMBER(10, 0) DEFAULT 0,
	estado NUMBER(10, 0) DEFAULT 0,
	timestamp TIMESTAMP DEFAULT NULL,
	evento CLOB DEFAULT '',
	utimestamp NUMBER(19, 0) DEFAULT 0,
	event_type VARCHAR2(50) DEFAULT 'unknown',
	id_agentmodule NUMBER(10, 0) DEFAULT 0,
	id_alert_am NUMBER(10, 0) DEFAULT 0,
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
	custom_data CLOB,
	CONSTRAINT tevento_event_type_cons CHECK (event_type IN ('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change'))
);
CREATE INDEX tevento_id_1_idx ON tevento(id_agente, id_evento);
CREATE INDEX tevento_id_2_idx ON tevento(utimestamp, id_evento);
CREATE INDEX tevento_id_agentmodule_idx ON tevento(id_agentmodule);

CREATE SEQUENCE tevento_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tevento_inc BEFORE INSERT ON tevento REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tevento_s.nextval INTO :NEW.id_evento FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tgrupo`
-- ---------------------------------------------------------------------
-- Criticity: 0 - Maintance (grey)
-- Criticity: 1 - Informational (blue)
-- Criticity: 2 - Normal (green) (status 0)
-- Criticity: 3 - Warning (yellow) (status 2)
-- Criticity: 4 - Critical (red) (status 1)
CREATE TABLE tgrupo (
	id_grupo NUMBER(10, 0) PRIMARY KEY,
	nombre VARCHAR2(100) DEFAULT '',
	icon VARCHAR2(50) DEFAULT 'world',
	parent NUMBER(10, 0) DEFAULT 0,
	propagate NUMBER(5, 0) DEFAULT 0,
	disabled NUMBER(5, 0) DEFAULT 0,
	custom_id VARCHAR2(255) DEFAULT '',
	id_skin NUMBER(10, 0) DEFAULT 0,
	description CLOB,
	contact CLOB,
	other CLOB
);

CREATE SEQUENCE tgrupo_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tgrupo_inc BEFORE INSERT ON tgrupo REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgrupo_s.nextval INTO :NEW.id_grupo FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tincidencia`
-- ---------------------------------------------------------------------
CREATE TABLE tincidencia (
	id_incidencia NUMBER(19, 0) PRIMARY KEY,
	inicio TIMESTAMP DEFAULT NULL,
	cierre TIMESTAMP DEFAULT NULL,
	titulo CLOB DEFAULT '',
	descripcion CLOB,
	id_usuario VARCHAR2(60) DEFAULT '',
	origen VARCHAR2(100) DEFAULT '',
	estado NUMBER(10, 0) DEFAULT 0,
	prioridad NUMBER(10, 0) DEFAULT 0,
	id_grupo NUMBER(10, 0) DEFAULT 0,
	actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	id_creator VARCHAR2(60) DEFAULT NULL,
	id_lastupdate VARCHAR2(60) DEFAULT NULL,
	id_agente_modulo NUMBER(19, 0),
	notify_email NUMBER(10, 0) DEFAULT 0,
	id_agent NUMBER(19, 0) DEFAULT 0 NULL 
);
CREATE INDEX tincidencia_id_1_idx ON tincidencia(id_usuario,id_incidencia);
CREATE INDEX tincidencia_id_agente_mod_idx ON tincidencia(id_agente_modulo);

--This trigger is for tranlate on update CURRENT_TIMESTAMP of MySQL.
CREATE OR REPLACE TRIGGER tincidencia_actualizacion_ts BEFORE UPDATE ON tincidencia FOR EACH ROW BEGIN select CURRENT_TIMESTAMP into :NEW.actualizacion FROM dual; END;;

CREATE SEQUENCE tincidencia_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tincidencia_inc BEFORE INSERT ON tincidencia REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tincidencia_s.nextval INTO :NEW.id_incidencia FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tlanguage`
-- ---------------------------------------------------------------------
CREATE TABLE tlanguage (
	id_language VARCHAR2(6) DEFAULT '',
	name VARCHAR2(100) DEFAULT ''
);
-- This sequence will not work with the 'insert_id' procedure

-- ---------------------------------------------------------------------
-- Table `tlink`
-- ---------------------------------------------------------------------
CREATE TABLE tlink (
	id_link NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(100) DEFAULT '',
	link VARCHAR2(255) DEFAULT ''
);

CREATE SEQUENCE tlink_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tlink_inc BEFORE INSERT ON tlink REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tlink_s.nextval INTO :NEW.id_link FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tmensajes`
-- ---------------------------------------------------------------------
CREATE TABLE tmensajes (
	id_mensaje NUMBER(10, 0) PRIMARY KEY,
	id_usuario_origen VARCHAR2(60) DEFAULT '',
	id_usuario_destino VARCHAR2(60) DEFAULT '',
	mensaje CLOB,
	timestamp NUMBER(19, 0) DEFAULT 0,
	subject VARCHAR2(255) DEFAULT '',
	estado NUMBER(10, 0) DEFAULT 0 
);

CREATE SEQUENCE tmensajes_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmensajes_inc BEFORE INSERT ON tmensajes REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmensajes_s.nextval INTO :NEW.id_mensaje FROM dual; END;;

-- ---------------------------------------------------------------------
-- Table `tmodule_group`
-- ---------------------------------------------------------------------
CREATE TABLE tmodule_group (
	id_mg NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(150) DEFAULT ''
);

CREATE SEQUENCE tmodule_group_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmodule_group_inc BEFORE INSERT ON tmodule_group REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmodule_group_s.nextval INTO :NEW.id_mg FROM dual; END;;

-- This table was moved cause the `tmodule_relationship` will add
-- a foreign key for the trecon_task(id_rt)
-- ----------------------------------------------------------------------
-- Table `trecon_task`
-- ----------------------------------------------------------------------
CREATE TABLE trecon_task (
	id_rt NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(100) DEFAULT '',
	description VARCHAR2(250) DEFAULT '',
	subnet CLOB DEFAULT NULL,
	id_network_profile NUMBER(10, 0) DEFAULT 0,
	create_incident NUMBER(10, 0) DEFAULT 0,
	id_group NUMBER(10, 0) DEFAULT 1,
	utimestamp NUMBER(19, 0) DEFAULT 0,
	status NUMBER(10, 0) DEFAULT 0,
	interval_sweep NUMBER(10, 0) DEFAULT 0,
	id_recon_server NUMBER(10, 0) DEFAULT 0,
	id_os NUMBER(10, 0) DEFAULT 0,
	recon_ports VARCHAR2(250) DEFAULT '',
	snmp_community VARCHAR2(64) DEFAULT 'public',
	id_recon_script NUMBER(10, 0),
	field1 CLOB DEFAULT NULL,
	field2 VARCHAR2(250) DEFAULT '',
	field3 VARCHAR2(250) DEFAULT '',
	field4 VARCHAR2(250) DEFAULT '',
	os_detect NUMBER(5, 0) DEFAULT 1,
	resolve_names NUMBER(5, 0) DEFAULT 1,
	parent_detection NUMBER(5, 0) DEFAULT 1,
	parent_recursion NUMBER(5, 0) DEFAULT 1,
	disabled NUMBER(5, 0) DEFAULT 0,
	macros CLOB DEFAULT ''
);
CREATE INDEX trecon_task_id_rec_serv_idx ON trecon_task(id_recon_server);

CREATE SEQUENCE trecon_task_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER trecon_task_inc BEFORE INSERT ON trecon_task REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT trecon_task_s.nextval INTO :NEW.id_rt FROM dual; END trecon_task_inc;;

-- ----------------------------------------------------------------------
-- Table `tmodule_relationship`
-- ----------------------------------------------------------------------
CREATE TABLE tmodule_relationship (
	id NUMBER(10, 0) PRIMARY KEY,
	id_rt NUMBER(10, 0) DEFAULT 0 REFERENCES trecon_task(id_rt) ON DELETE CASCADE,
	module_a NUMBER(10, 0) REFERENCES tagente_modulo(id_agente_modulo) ON DELETE CASCADE,
	module_b NUMBER(10, 0) REFERENCES tagente_modulo(id_agente_modulo) ON DELETE CASCADE,
	disable_update NUMBER(1, 0) DEFAULT 0
);

CREATE SEQUENCE tmodule_relationship_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmodule_relationship_inc BEFORE INSERT ON tmodule_relationship REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmodule_relationship_s.nextval INTO :NEW.id FROM dual; END;;

-- ----------------------------------------------------------------------
-- Table `tnetwork_component`
-- ----------------------------------------------------------------------
CREATE TABLE tnetwork_component (
	id_nc NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(4000) DEFAULT '',
	description VARCHAR2(300) DEFAULT NULL,
	id_group NUMBER(10, 0) DEFAULT 1,
	type NUMBER(10, 0) DEFAULT 6,
	max NUMBER(10, 0) DEFAULT 0,
	min NUMBER(19, 0) DEFAULT 0,
	module_interval NUMBER(19, 0) DEFAULT 0,
	tcp_port NUMBER(10, 0) DEFAULT 0,
	tcp_send CLOB,
	tcp_rcv CLOB,
	snmp_community VARCHAR2(255) DEFAULT '',
	snmp_oid VARCHAR2(400) DEFAULT '',
	id_module_group NUMBER(10, 0) DEFAULT 0,
	id_modulo NUMBER(10, 0) DEFAULT 0,
	id_plugin NUMBER(10, 0) DEFAULT 0,
	plugin_user CLOB DEFAULT '',
	plugin_pass CLOB DEFAULT '',
	plugin_parameter CLOB,
	max_timeout NUMBER(10, 0) DEFAULT 0,
	max_retries NUMBER(10, 0) DEFAULT 0,
	history_data NUMBER(5, 0) DEFAULT 1,
	min_warning BINARY_DOUBLE DEFAULT 0,
	max_warning BINARY_DOUBLE DEFAULT 0,
	str_warning CLOB DEFAULT '',
	min_critical BINARY_DOUBLE DEFAULT 0,
	max_critical BINARY_DOUBLE DEFAULT 0,
	str_critical CLOB DEFAULT '',
	min_ff_event NUMBER(10, 0) DEFAULT 0,
	custom_string_1 CLOB DEFAULT '',
	custom_string_2 CLOB DEFAULT '',
	custom_string_3 CLOB DEFAULT '',
	custom_integer_1 INTEGER DEFAULT 0,
	custom_integer_2 INTEGER DEFAULT 0,
	post_process BINARY_DOUBLE DEFAULT 0,
	unit CLOB DEFAULT '',
	wizard_level VARCHAR2(100) DEFAULT 'nowizard',
	macros CLOB DEFAULT '',
	critical_instructions CLOB DEFAULT '',
	warning_instructions CLOB DEFAULT '',
	unknown_instructions CLOB DEFAULT '',
	critical_inverse NUMBER(1, 0) DEFAULT 0,
	warning_inverse NUMBER(1, 0) DEFAULT 0,
	id_category NUMBER(10, 0) DEFAULT 0,
	tags CLOB,
	disabled_types_event CLOB DEFAULT '',
	module_macros CLOB DEFAULT '',
	min_ff_event_normal INTEGER DEFAULT 0,
	min_ff_event_warning INTEGER DEFAULT 0,
	min_ff_event_critical INTEGER DEFAULT 0,
	each_ff NUMBER(1, 0) DEFAULT 0,
	dynamic_interval INTEGER default 0,
	dynamic_max INTEGER default 0,
	dynamic_min INTEGER default 0,
	dynamic_next INTEGER NOT NULL default 0,
	dynamic_two_tailed INTEGER default 0,
	CONSTRAINT t_network_component_wiz_cons CHECK (wizard_level IN ('basic','advanced','nowizard'))
);

CREATE SEQUENCE tnetwork_component_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetwork_component_inc BEFORE INSERT ON tnetwork_component REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetwork_component_s.nextval INTO :NEW.id_nc FROM dual; END;;

-- ----------------------------------------------------------------------
-- Table `tnetwork_component_group`
-- ----------------------------------------------------------------------
CREATE TABLE tnetwork_component_group (
	id_sg NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(200) DEFAULT '',
	parent NUMBER(19, 0) DEFAULT 0 
);

CREATE SEQUENCE tnetwork_component_group_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetwork_component_group_inc BEFORE INSERT ON tnetwork_component_group REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetwork_component_group_s.nextval INTO :NEW.id_sg FROM dual; END;;

-- ----------------------------------------------------------------------
-- Table `tnetwork_profile`
-- ----------------------------------------------------------------------
CREATE TABLE tnetwork_profile (
	id_np NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(100) DEFAULT '',
	description VARCHAR2(250) DEFAULT ''
);

CREATE SEQUENCE tnetwork_profile_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetwork_profile_inc BEFORE INSERT ON tnetwork_profile REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetwork_profile_s.nextval INTO :NEW.id_np FROM dual; END;;

-- ----------------------------------------------------------------------
-- Table `tnetwork_profile_component`
-- ----------------------------------------------------------------------
CREATE TABLE tnetwork_profile_component (
	id_nc NUMBER(19, 0) DEFAULT 0,
	id_np NUMBER(19, 0) DEFAULT 0
);
CREATE INDEX tnetwork_profile_id_np_idx ON tnetwork_profile_component(id_np);

-- This sequence will not work with the 'insert_id' procedure

-- ----------------------------------------------------------------------
-- Table `tnota`
-- ----------------------------------------------------------------------
CREATE TABLE tnota (
	id_nota NUMBER(19, 0) PRIMARY KEY,
	id_incident NUMBER(19, 0),
	id_usuario VARCHAR2(100) DEFAULT '0',
	timestamp TIMESTAMP  DEFAULT CURRENT_TIMESTAMP, 
	nota CLOB
);
CREATE INDEX tnota_id_incident_idx ON tnota(id_incident);

-- This sequence will not work with the 'insert_id' procedure

-- ----------------------------------------------------------------------
-- Table `torigen`
-- ----------------------------------------------------------------------
CREATE TABLE torigen (
	origen VARCHAR2(100) DEFAULT ''
);

-- This sequence will not work with the 'insert_id' procedure

-- ----------------------------------------------------------------------
-- Table `tperfil`
-- ----------------------------------------------------------------------
CREATE TABLE tperfil (
	id_perfil NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(200) NOT NULL,
	incident_edit NUMBER(1, 0) DEFAULT 0,
	incident_view NUMBER(1, 0) DEFAULT 0,
	incident_management NUMBER(1, 0) DEFAULT 0,
	agent_view NUMBER(1, 0) DEFAULT 0,
	agent_edit NUMBER(1, 0) DEFAULT 0,
	alert_edit NUMBER(1, 0) DEFAULT 0,
	user_management NUMBER(1, 0) DEFAULT 0,
	db_management NUMBER(1, 0) DEFAULT 0,
	alert_management NUMBER(1, 0) DEFAULT 0,
	pandora_management NUMBER(1, 0) DEFAULT 0,
	report_view NUMBER(1, 0) DEFAULT 0,
	report_edit NUMBER(1, 0) DEFAULT 0,
	report_management NUMBER(1, 0) DEFAULT 0,
	event_view NUMBER(1, 0) DEFAULT 0,
	event_edit NUMBER(1, 0) DEFAULT 0,
	event_management NUMBER(1, 0) DEFAULT 0,
	agent_disable NUMBER(1, 0) DEFAULT 0,
	map_view NUMBER(1, 0) DEFAULT 0,
	map_edit NUMBER(1, 0) DEFAULT 0,
	map_management NUMBER(1, 0) DEFAULT 0,
	vconsole_view NUMBER(1, 0) DEFAULT 0,
	vconsole_edit NUMBER(1, 0) DEFAULT 0,
	vconsole_management NUMBER(1, 0) DEFAULT 0
);

CREATE SEQUENCE tperfil_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tperfil_inc BEFORE INSERT ON tperfil REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tperfil_s.nextval INTO :NEW.id_perfil FROM dual; END;;

-- ----------------------------------------------------------------------
-- Table `trecon_script`
-- ----------------------------------------------------------------------
CREATE TABLE trecon_script (
	id_recon_script NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(100) DEFAULT '',
	description CLOB DEFAULT NULL,
	script VARCHAR2(250) DEFAULT '',
	macros CLOB DEFAULT ''
);

CREATE SEQUENCE trecon_script_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER trecon_script_inc BEFORE INSERT ON trecon_script REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT trecon_script_s.nextval INTO :NEW.id_recon_script FROM dual; END;;

-- ----------------------------------------------------------------------
-- Table `tserver`
-- ----------------------------------------------------------------------
CREATE TABLE tserver (
	id_server NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(100) DEFAULT '',
	ip_address VARCHAR2(100) DEFAULT '',
	status NUMBER(10, 0) DEFAULT 0,
	laststart TIMESTAMP DEFAULT NULL,
	keepalive TIMESTAMP DEFAULT NULL,
	snmp_server NUMBER(10, 0) DEFAULT 0,
	network_server NUMBER(10, 0) DEFAULT 0,
	data_server NUMBER(10, 0) DEFAULT 0,
	master NUMBER(10, 0) DEFAULT 0,
	checksum NUMBER(10, 0) DEFAULT 0,
	description VARCHAR2(255) DEFAULT NULL,
	recon_server NUMBER(10, 0) DEFAULT 0,
	version VARCHAR2(20) DEFAULT '',
	plugin_server NUMBER(10, 0) DEFAULT 0,
	prediction_server NUMBER(10, 0) DEFAULT 0,
	wmi_server NUMBER(10, 0) DEFAULT 0,
	export_server NUMBER(10, 0) DEFAULT 0,
	server_type NUMBER(10, 0) DEFAULT 0,
	queued_modules NUMBER(10, 0) DEFAULT 0,
	threads NUMBER(10, 0) DEFAULT 0,
	lag_time NUMBER(10, 0) DEFAULT 0,
	lag_modules NUMBER(10, 0) DEFAULT 0,
	total_modules_running NUMBER(10, 0) DEFAULT 0,
	my_modules NUMBER(10, 0) DEFAULT 0,
	server_keepalive NUMBER(10, 0) DEFAULT 0,
	stat_utimestamp NUMBER(19, 0) DEFAULT 0
);
CREATE INDEX tserver_name_idx ON tserver(name);
CREATE INDEX tserver_keepalive_idx ON tserver(keepalive);
CREATE INDEX tserver_status_idx ON tserver(status);

CREATE SEQUENCE tserver_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tserver_inc BEFORE INSERT ON tserver REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tserver_s.nextval INTO :NEW.id_server FROM dual; END tserver_inc;;

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

-- ----------------------------------------------------------------------
-- Table `tsesion`
-- ----------------------------------------------------------------------
CREATE TABLE tsesion (
	id_sesion NUMBER(19, 0) PRIMARY KEY,
	id_usuario VARCHAR2(60) DEFAULT '0',
	ip_origen VARCHAR2(100) DEFAULT '',
	accion VARCHAR2(100) DEFAULT '',
	descripcion CLOB DEFAULT '',
	fecha TIMESTAMP DEFAULT NULL,
	utimestamp NUMBER(19, 0) DEFAULT 0
);
CREATE INDEX tsesion_utimestamp_idx ON tsesion(utimestamp);
CREATE INDEX tsesion_id_usuario_idx ON tsesion(id_usuario);

CREATE SEQUENCE tsesion_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tsesion_inc BEFORE INSERT ON tsesion REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tsesion_s.nextval INTO :NEW.id_sesion FROM dual; END tsesion_inc;;

-- ---------------------------------------------------------------------
-- Table `ttipo_modulo`
-- ---------------------------------------------------------------------
CREATE TABLE ttipo_modulo (
	id_tipo NUMBER(10, 0) PRIMARY KEY,
	nombre VARCHAR2(100) DEFAULT '',
	categoria NUMBER(10, 0) DEFAULT 0,
	descripcion VARCHAR2(100) DEFAULT '',
	icon VARCHAR2(100) DEFAULT NULL
);

CREATE SEQUENCE ttipo_modulo_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER ttipo_modulo_inc BEFORE INSERT ON ttipo_modulo REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT ttipo_modulo_s.nextval INTO :NEW.id_tipo FROM dual; END ttipo_modulo_inc;;

-- ---------------------------------------------------------------------
-- Table `ttrap`
-- ---------------------------------------------------------------------
CREATE TABLE ttrap (
	id_trap NUMBER(19, 0) PRIMARY KEY,
	source VARCHAR2(50) DEFAULT '',
	oid VARCHAR2(1024) DEFAULT '',
	oid_custom VARCHAR2(1024) DEFAULT '',
	type NUMBER(10, 0) DEFAULT 0,
	type_custom VARCHAR2(100) DEFAULT '',
	value CLOB DEFAULT '',
	value_custom CLOB DEFAULT '',
	alerted NUMBER(5, 0) DEFAULT 0,
	status NUMBER(5, 0) DEFAULT 0,
	id_usuario VARCHAR2(150) DEFAULT '',
	timestamp TIMESTAMP DEFAULT NULL,
	priority NUMBER(5, 0) DEFAULT 2,
	text VARCHAR2(255) DEFAULT '',
	description VARCHAR2(255) DEFAULT '',
	severity NUMBER(10, 0) DEFAULT 2
);

CREATE SEQUENCE ttrap_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER ttrap_inc BEFORE INSERT ON ttrap REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT ttrap_s.nextval INTO :NEW.id_trap FROM dual; END ttrap_inc;;

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------
CREATE TABLE tusuario (
	id_user VARCHAR2(60) PRIMARY KEY,
	fullname VARCHAR2(255),
	firstname VARCHAR2(255),
	lastname VARCHAR2(255),
	middlename VARCHAR2(255) DEFAULT '',
	password VARCHAR2(45) DEFAULT NULL,
	comments VARCHAR2(200) DEFAULT NULL,
	last_connect NUMBER(19, 0) DEFAULT 0,
	registered NUMBER(19, 0) DEFAULT 0,
	email VARCHAR2(100) DEFAULT NULL,
	phone VARCHAR2(100) DEFAULT NULL,
	is_admin NUMBER(5, 0) DEFAULT 0,
	language VARCHAR2(10) DEFAULT NULL,
	timezone VARCHAR2(50) DEFAULT '',
	block_size NUMBER(10, 0) DEFAULT 20,
	flash_chart NUMBER(10, 0) DEFAULT 1,
	id_skin NUMBER(10, 0) DEFAULT 0,
	disabled NUMBER(10, 0) DEFAULT 0,
	shortcut NUMBER(5, 0) DEFAULT 0,
	shortcut_data CLOB DEFAULT '',
	section VARCHAR2(255) DEFAULT '',
	data_section VARCHAR2(255) DEFAULT '',
	force_change_pass NUMBER(5,0) DEFAULT 0,
	last_pass_change TIMESTAMP,
	last_failed_login TIMESTAMP,
	failed_attempt NUMBER(5,0) DEFAULT 0,
	login_blocked NUMBER(5,0) DEFAULT 0,
	metaconsole_access VARCHAR2(100) DEFAULT 'basic',
	not_login NUMBER(5,0) DEFAULT 0,
	metaconsole_agents_manager NUMBER(10, 0) DEFAULT 0,
	metaconsole_assigned_server NUMBER(10, 0) DEFAULT 0,
	metaconsole_access_node NUMBER(10, 0) DEFAULT 0,
	strict_acl NUMBER(5,0) DEFAULT 0,
	session_time NUMBER(10,0) DEFAULT 0,
	CONSTRAINT t_usuario_metaconsole_acc_cons CHECK (metaconsole_access IN ('basic','advanced'))
);

-- This sequence will not work with the 'insert_id' procedure

-- -----------------------------------------------------
-- Table `tusuario_perfil`
-- -----------------------------------------------------
CREATE TABLE tusuario_perfil (
	id_up NUMBER(19, 0) PRIMARY KEY,
	id_usuario VARCHAR2(100) DEFAULT '',
	id_perfil NUMBER(10, 0) DEFAULT 0,
	id_grupo NUMBER(10, 0) DEFAULT 0,
	assigned_by VARCHAR2(100) DEFAULT '',
	id_policy NUMBER(10, 0) DEFAULT 0,
	tags VARCHAR2(255) DEFAULT ''
);

CREATE SEQUENCE tusuario_perfil_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tusuario_perfil_inc BEFORE INSERT ON tusuario_perfil REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tusuario_perfil_s.nextval INTO :NEW.id_up FROM dual; END tusuario_perfil_inc;;

-- ----------------------------------------------------------------------
-- Table `tuser_double_auth`
-- ----------------------------------------------------------------------
CREATE TABLE tuser_double_auth (
	id NUMBER(10, 0) PRIMARY KEY,
	id_user VARCHAR2(60) REFERENCES tusuario(id_user) ON DELETE CASCADE,
	secret VARCHAR2(20)
);

CREATE SEQUENCE tuser_double_auth_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tuser_double_auth_inc BEFORE INSERT ON tuser_double_auth REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tuser_double_auth_s.nextval INTO :NEW.id FROM dual; END tuser_double_auth_inc;;

-- ---------------------------------------------------------------------
-- Table `tnews`
-- ---------------------------------------------------------------------
CREATE TABLE tnews (
	id_news NUMBER(10, 0) PRIMARY KEY,
	author VARCHAR2(255) DEFAULT '',
	subject VARCHAR2(255) DEFAULT '',
	text CLOB,
	timestamp TIMESTAMP DEFAULT NULL,
	id_group NUMBER(10, 0) DEFAULT 0,
	modal NUMBER(5, 0) DEFAULT 0,
	expire NUMBER(5, 0) DEFAULT 0,
	expire_timestamp TIMESTAMP DEFAULT NULL
);

CREATE SEQUENCE tnews_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnews_inc BEFORE INSERT ON tnews REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnews_s.nextval INTO :NEW.id_news FROM dual; END tnews_inc;;

-- ---------------------------------------------------------------------
-- Table `tgraph`
-- ---------------------------------------------------------------------
CREATE TABLE tgraph (
	id_graph NUMBER(10, 0) PRIMARY KEY,
	id_user VARCHAR2(100) DEFAULT '',
	name VARCHAR2(150) DEFAULT '',
	description CLOB,
	period NUMBER(10, 0) DEFAULT 0,
	width NUMBER(10, 0) DEFAULT 0,
	height NUMBER(10, 0) DEFAULT 0,
	private NUMBER(5, 0) DEFAULT 0,
	events NUMBER(5, 0) DEFAULT 0,
	stacked NUMBER(5, 0) DEFAULT 0,
	id_group NUMBER(19, 0) DEFAULT 0,
	id_graph_template NUMBER(11, 0) DEFAULT 0 
);

CREATE SEQUENCE tgraph_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tgraph_inc BEFORE INSERT ON tgraph REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgraph_s.nextval INTO :NEW.id_graph FROM dual; END tgraph_inc;;

-- ---------------------------------------------------------------------
-- Table `tgraph_source`
-- ---------------------------------------------------------------------
CREATE TABLE tgraph_source (
	id_gs NUMBER(10, 0) PRIMARY KEY,
	id_graph NUMBER(19, 0) DEFAULT 0,
	id_server  NUMBER(19, 0) DEFAULT 0,
	id_agent_module  NUMBER(19, 0) DEFAULT 0,
	weight BINARY_DOUBLE DEFAULT 0,
	label VARCHAR2(150) DEFAULT ''
);

CREATE SEQUENCE tgraph_source_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tgraph_source_inc BEFORE INSERT ON tgraph_source REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgraph_source_s.nextval INTO :NEW.id_gs FROM dual; END tgraph_source_inc;;

-- ---------------------------------------------------------------------
-- Table `treport`
-- ---------------------------------------------------------------------
CREATE TABLE treport (
	id_report NUMBER(10, 0) PRIMARY KEY,
	id_user VARCHAR2(100) DEFAULT '',
	name VARCHAR2(150) DEFAULT '',
	description CLOB,
	private NUMBER(5, 0) DEFAULT 0,
	id_group NUMBER(19, 0) DEFAULT 0,
	custom_logo VARCHAR2(200)  DEFAULT NULL,
	header CLOB  DEFAULT NULL,
	first_page CLOB DEFAULT NULL,
	footer CLOB DEFAULT NULL,
	custom_font VARCHAR2(200) DEFAULT NULL,
	id_template NUMBER(10, 0) DEFAULT 0,
	id_group_edit NUMBER(19, 0) DEFAULT 0,
	metaconsole NUMBER(5, 0) DEFAULT 0,
	non_interactive NUMBER(5, 0) DEFAULT 0
);

CREATE SEQUENCE treport_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER treport_inc BEFORE INSERT ON treport REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_s.nextval INTO :NEW.id_report FROM dual; END treport_inc;;

-- -----------------------------------------------------
-- Table `treport_content`
-- -----------------------------------------------------
-- use to_char(time_from, 'hh24:mi:ss') function to retrieve time_from field info
-- use to_char(time_to,   'hh24:mi:ss') function to retrieve time_to field info
CREATE TABLE treport_content (
	id_rc NUMBER(10, 0) PRIMARY KEY,
	id_report NUMBER(10, 0) DEFAULT 0 REFERENCES treport(id_report) ON DELETE CASCADE, 
	id_gs  NUMBER(10, 0) DEFAULT NULL,
	id_agent_module NUMBER(19, 0) DEFAULT NULL,
	"type" VARCHAR2(30) DEFAULT 'simple_graph',
	period NUMBER(19, 0) DEFAULT 0,
	"order" NUMBER(19, 0) DEFAULT 0,
	name VARCHAR2(150) DEFAULT NULL,
	description CLOB, 
	id_agent NUMBER(19, 0) DEFAULT 0,
	text CLOB DEFAULT NULL,
	external_source CLOB DEFAULT NULL,
	treport_custom_sql_id NUMBER(10, 0) DEFAULT 0,
	header_definition CLOB DEFAULT NULL,
	column_separator CLOB DEFAULT NULL,
	line_separator CLOB DEFAULT NULL,
	time_from TIMESTAMP DEFAULT to_date('00:00:00','hh24:mi:ss'), 
	time_to TIMESTAMP DEFAULT to_date('00:00:00','hh24:mi:ss'),   	
	monday NUMBER(5, 0) DEFAULT 1,
	tuesday NUMBER(5, 0) DEFAULT 1,
	wednesday NUMBER(5, 0) DEFAULT 1,
	thursday NUMBER(5, 0) DEFAULT 1,
	friday NUMBER(5, 0) DEFAULT 1,
	saturday NUMBER(5, 0) DEFAULT 1,
	sunday NUMBER(5, 0) DEFAULT 1,
	only_display_wrong NUMBER(5, 0) DEFAULT 0,
	top_n NUMBER(10, 0) DEFAULT 0,
	top_n_value NUMBER(10, 0) DEFAULT 10 ,
	exception_condition NUMBER(10, 0) DEFAULT 0,
	exception_condition_value BINARY_DOUBLE DEFAULT 0,
	show_resume NUMBER(10, 0) DEFAULT 0,
	order_uptodown NUMBER(10, 0) DEFAULT 0,
	show_graph NUMBER(10, 0) DEFAULT 0,
	group_by_agent NUMBER(10, 0) DEFAULT 0,
	style CLOB DEFAULT '',
	id_group NUMBER(10, 0) DEFAULT 0,
	id_module_group NUMBER(10, 0) DEFAULT 0,
	server_name VARCHAR2(1000) default ''
);

CREATE SEQUENCE treport_content_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER treport_content_inc BEFORE INSERT ON treport_content REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_content_s.nextval INTO :NEW.id_rc FROM dual; END treport_content_inc;;

-- on update trigger
CREATE OR REPLACE TRIGGER treport_content_update AFTER UPDATE OF id_report ON treport FOR EACH ROW BEGIN UPDATE treport_content SET id_rc = :NEW.id_report WHERE id_rc = :OLD.id_report; END;;

-- -----------------------------------------------------
-- Table `treport_content_sla_combined`
-- -----------------------------------------------------
CREATE TABLE treport_content_sla_combined (
	id NUMBER(10, 0) PRIMARY KEY,
	id_report_content NUMBER(10, 0)  REFERENCES treport_content(id_rc) ON DELETE CASCADE,
	id_agent_module NUMBER(10, 0),
	sla_max BINARY_DOUBLE DEFAULT 0,
	sla_min BINARY_DOUBLE DEFAULT 0,
	sla_limit BINARY_DOUBLE DEFAULT 0,
	server_name CLOB DEFAULT ''
);

-- This sequence will not work with the 'insert_id' procedure

CREATE SEQUENCE treport_cont_sla_c_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER treport_content_sla_comb_inc BEFORE INSERT ON treport_content_sla_combined REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_cont_sla_c_s.nextval INTO :NEW.id FROM dual; END treport_content_sla_comb_inc;; 

-- on update trigger
CREATE OR REPLACE TRIGGER treport_cont_sla_comb_update AFTER UPDATE OF id on treport_content_sla_combined FOR EACH ROW BEGIN UPDATE treport_content_sla_combined SET id = :NEW.id WHERE id = :OLD.id; END;;

-- -----------------------------------------------------
-- Table `treport_content_item`
-- -----------------------------------------------------
CREATE TABLE treport_content_item (
	id NUMBER(10, 0) PRIMARY KEY,
	id_report_content NUMBER(10, 0) REFERENCES treport_content(id_rc) ON DELETE CASCADE,
	id_agent_module NUMBER(10, 0),
	server_name CLOB DEFAULT '',
	operation CLOB DEFAULT ''
);

CREATE SEQUENCE treport_content_item_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER treport_content_item_inc BEFORE INSERT ON treport_content_item REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_content_item_s.nextval INTO :NEW.id FROM dual; END treport_content_item_inc;; 

-- ---------------------------------------------------------------------
-- Table `treport_custom_sql`
-- ---------------------------------------------------------------------
CREATE TABLE treport_custom_sql (
	id NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(150) DEFAULT '',
	sql CLOB DEFAULT NULL
);

CREATE SEQUENCE treport_custom_sql_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER treport_custom_sql_inc BEFORE INSERT ON treport_custom_sql REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT treport_custom_sql_s.nextval INTO :NEW.id FROM dual; END treport_custom_sql_inc;;

-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
CREATE TABLE tlayout (
	id NUMBER(10, 0) PRIMARY KEY,
	name varchar(50) ,
	id_group NUMBER(10, 0),
	background VARCHAR2(200) ,
	height NUMBER(10, 0) DEFAULT 0,
	background_color VARCHAR2(50) DEFAULT '#FFF',
	width NUMBER(10, 0) DEFAULT 0
);

CREATE SEQUENCE tlayout_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tlayout_inc BEFORE INSERT ON tlayout REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tlayout_s.nextval INTO :NEW.id FROM dual; END tlayout_inc;;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
CREATE TABLE tlayout_data (
	id NUMBER(10, 0) PRIMARY KEY,
	id_layout NUMBER(10, 0) DEFAULT 0,
	pos_x NUMBER(10, 0) DEFAULT 0,
	pos_y NUMBER(10, 0) DEFAULT 0,
	height NUMBER(10, 0) DEFAULT 0,
	width NUMBER(10, 0) DEFAULT 0,
	label CLOB DEFAULT '',
	image VARCHAR2(200) DEFAULT '',
	type NUMBER(5, 0) DEFAULT 0,
	period NUMBER(10, 0) DEFAULT 3600,
	id_agente_modulo NUMBER(19, 0) DEFAULT 0,
	id_agent NUMBER(10, 0) DEFAULT 0,
	id_layout_linked NUMBER(10, 0) DEFAULT 0,
	parent_item NUMBER(10, 0) DEFAULT 0,
	enable_link NUMBER(5, 0) DEFAULT 1,
	id_metaconsole NUMBER(10, 0) DEFAULT 0,
	id_group NUMBER(10, 0) DEFAULT 0,
	id_custom_graph NUMBER(10, 0) DEFAULT 0,
	border_width NUMBER(10, 0) DEFAULT 0,
	type_graph VARCHAR2(50) DEFAULT 'area',
	border_color VARCHAR2(200) DEFAULT '',
	fill_color VARCHAR2(200) DEFAULT ''
);

CREATE SEQUENCE tlayout_data_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tlayout_data_inc BEFORE INSERT ON tlayout_data REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tlayout_data_s.nextval INTO :NEW.id FROM dual; END tlayout_data_inc;;

-- ---------------------------------------------------------------------
-- Table `tplugin`
-- ---------------------------------------------------------------------
CREATE TABLE tplugin (
	id NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(200),
	description CLOB,
	max_timeout NUMBER(10, 0) DEFAULT 0,
	max_retries NUMBER(10, 0) DEFAULT 0,
	execute VARCHAR2(250),
	net_dst_opt VARCHAR2(50) DEFAULT '',
	net_port_opt VARCHAR2(50) DEFAULT '',
	user_opt VARCHAR2(50) DEFAULT '',
	pass_opt VARCHAR2(50) DEFAULT '',
	plugin_type NUMBER(5, 0) DEFAULT 0,
	macros CLOB DEFAULT '',
	parameters CLOB DEFAULT ''
);

CREATE SEQUENCE tplugin_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tplugin_inc BEFORE INSERT ON tplugin REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tplugin_s.nextval INTO :NEW.id FROM dual; END tplugin_inc;;

-- ---------------------------------------------------------------------
-- Table `tmodule`
-- ---------------------------------------------------------------------
CREATE TABLE tmodule (
	id_module NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(100) DEFAULT '' 
);

CREATE SEQUENCE tmodule_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmodule_inc BEFORE INSERT ON tmodule REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmodule_s.nextval INTO :NEW.id_module FROM dual; END tmodule_inc;;

-- ---------------------------------------------------------------------
-- Table `tserver_export`
-- ---------------------------------------------------------------------
CREATE TABLE tserver_export (
	id NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(100) DEFAULT '',
	preffix VARCHAR2(100) DEFAULT '',
	interval NUMBER(10, 0) DEFAULT 300,
	ip_server VARCHAR2(100) DEFAULT '',
	connect_mode VARCHAR2(20) DEFAULT 'local',
	id_export_server NUMBER(10, 0) DEFAULT NULL ,
	"user" VARCHAR2(100) DEFAULT '',
	pass VARCHAR2(100) DEFAULT '',
	port NUMBER(10, 0) DEFAULT 0,
	directory VARCHAR2(100) DEFAULT '',
	options VARCHAR2(100) DEFAULT '',
	--Number of hours of diference with the server timezone
	timezone_offset NUMBER(5, 0) DEFAULT 0,
	CONSTRAINT tserver_export_conn_mode_cons CHECK (connect_mode IN ('tentacle', 'ssh', 'local'))
);

CREATE SEQUENCE tserver_export_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tserver_export_inc BEFORE INSERT ON tserver_export REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tserver_export_s.nextval INTO :NEW.id FROM dual; END tserver_export_inc;;

-- ---------------------------------------------------------------------
-- Table `tserver_export_data`
-- ---------------------------------------------------------------------
-- id_export_server is real pandora fms export server process that manages this server
-- id is the destination server to export
CREATE TABLE tserver_export_data (
	id NUMBER(10, 0) PRIMARY KEY,
	id_export_server NUMBER(10, 0) DEFAULT 0,
	agent_name VARCHAR2(100) DEFAULT '',
	module_name VARCHAR2(100) DEFAULT '',
	module_type VARCHAR2(100) DEFAULT '',
	data VARCHAR2(255) DEFAULT NULL, 
	timestamp TIMESTAMP DEFAULT NULL
);

CREATE SEQUENCE tserver_export_data_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tserver_export_data_inc BEFORE INSERT ON tserver_export_data REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tserver_export_data_s.nextval INTO :NEW.id FROM dual; END tserver_export_data_inc;;

-- -----------------------------------------------------
-- Table `tplanned_downtime`
-- -----------------------------------------------------
CREATE TABLE tplanned_downtime (
	id NUMBER(19, 0) PRIMARY KEY,
	name VARCHAR2(100),
	description CLOB,
	date_from NUMBER(19, 0) DEFAULT 0,
	date_to NUMBER(19, 0) DEFAULT 0,
	executed NUMBER(5, 0) DEFAULT 0,
	id_group NUMBER(19, 0) DEFAULT 0,
	only_alerts NUMBER(5, 0) DEFAULT 0,
	monday NUMBER(5, 0) DEFAULT 0,
	tuesday NUMBER(5, 0) DEFAULT 0,
	wednesday NUMBER(5, 0) DEFAULT 0,
	thursday NUMBER(5, 0) DEFAULT 0,
	friday NUMBER(5, 0) DEFAULT 0,
	saturday NUMBER(5, 0) DEFAULT 0,
	sunday NUMBER(5, 0) DEFAULT 0,
	-- Need to set better datatype
	periodically_time_from DATE DEFAULT NULL,
	periodically_time_to DATE DEFAULT NULL,
	--
	periodically_day_from NUMBER(19, 0) DEFAULT NULL,
	periodically_day_to NUMBER(19, 0) DEFAULT NULL,
	type_downtime VARCHAR2(100) DEFAULT 'disabled_agents_alerts',
	type_execution VARCHAR2(100) DEFAULT 'once',
	type_periodicity VARCHAR2(100) DEFAULT 'weekly',
	id_user VARCHAR2(100) DEFAULT '0'
);

CREATE SEQUENCE tplanned_downtime_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tplanned_downtime_inc BEFORE INSERT ON tplanned_downtime REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tplanned_downtime_s.nextval INTO :NEW.id FROM dual; END tplanned_downtime_inc;;

-- -----------------------------------------------------
-- Table `tplanned_downtime_agents`
-- -----------------------------------------------------
CREATE TABLE tplanned_downtime_agents (
	id NUMBER(19, 0) PRIMARY KEY,
	id_agent NUMBER(19, 0) DEFAULT 0,
	id_downtime NUMBER(19, 0) DEFAULT 0 REFERENCES tplanned_downtime(id) ON DELETE CASCADE,
	all_modules NUMBER(5, 0) DEFAULT 1
);

CREATE SEQUENCE tplanned_downtime_agents_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tplanned_downtime_agents_inc BEFORE INSERT ON tplanned_downtime_agents REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tplanned_downtime_agents_s.nextval INTO :NEW.id FROM dual; END tplanned_downtime_agents_inc;;

-- -----------------------------------------------------
-- Table `tplanned_downtime_modules`
-- -----------------------------------------------------
CREATE TABLE tplanned_downtime_modules (
	id NUMBER(19, 0) PRIMARY KEY,
	id_agent NUMBER(19, 0) DEFAULT 0,
	id_agent_module NUMBER(10, 0) REFERENCES tagente_modulo(id_agente_modulo) ON DELETE CASCADE,
	id_downtime NUMBER(19, 0) DEFAULT 0 REFERENCES tplanned_downtime(id) ON DELETE CASCADE
);

CREATE SEQUENCE tplanned_downtime_modules_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tplanned_downtime_modules_inc BEFORE INSERT ON tplanned_downtime_modules REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tplanned_downtime_modules_s.nextval INTO :NEW.id FROM dual; END tplanned_downtime_modules_inc;;

--IS extension Tables

-- -----------------------------------------------------
-- Table `tgis_data_history`
-- -----------------------------------------------------
--Table to store historicalIS information of the agents
CREATE TABLE tgis_data_history (
	--key of the table
	id_tgis_data NUMBER(10, 0) PRIMARY KEY,
	longitude BINARY_DOUBLE,
	latitude BINARY_DOUBLE,
	altitude BINARY_DOUBLE,
	--timestamp on wich the agente started to be in this position
	start_timestamp  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	--timestamp on wich the agent was placed for last time on this position
	end_timestamp  TIMESTAMP DEFAULT NULL,
	--description of the region correoponding to this placemnt
	description CLOB DEFAULT NULL,
	-- 0 to show that the position cames from the agent, 1 to show that the position was established manualy
	manual_placement NUMBER(5, 0) DEFAULT 0,
	-- Number of data packages received with this position from the start_timestampa to the_end_timestamp
	number_of_packages NUMBER(10, 0) DEFAULT 1,
	--reference to the agent
	tagente_id_agente NUMBER(10, 0) 
);
CREATE INDEX tgis_data_history_start_t_idx ON tgis_data_history(start_timestamp);
CREATE INDEX tgis_data_history_end_t_idx ON tgis_data_history(end_timestamp);
 
CREATE SEQUENCE tgis_data_history_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tgis_data_history_inc BEFORE INSERT ON tgis_data_history REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgis_data_history_s.nextval INTO :NEW.id_tgis_data FROM dual; END tgis_data_history_inc;;

-- -----------------------------------------------------
-- Table `tgis_data_status`
-- -----------------------------------------------------
--Table to store lastIS information of the agents
--ON UPDATE NO ACTION is implicit on Oracle DBMS for tagente_id_agente field 
CREATE TABLE tgis_data_status (
	--Reference to the agent
	tagente_id_agente NUMBER(10, 0) REFERENCES tagente(id_agente) ON DELETE CASCADE, 
	--Last received longitude
	current_longitude BINARY_DOUBLE,
	--Last received latitude 
	current_latitude BINARY_DOUBLE,
	--Last received altitude 
	current_altitude BINARY_DOUBLE,
	--Reference longitude to see if the agent has moved
	stored_longitude BINARY_DOUBLE,
	--Reference latitude to see if the agent has moved
	stored_latitude BINARY_DOUBLE,
	--Reference altitude to see if the agent has moved
	stored_altitude BINARY_DOUBLE DEFAULT NULL,
	--Number of data packages received with this position since start_timestampa
	number_of_packages NUMBER(10, 0) DEFAULT 1, 
	--Timestamp on wich the agente started to be in this position
	start_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
	--0 to show that the position cames from the agent, 1 to show that the position was established manualy
	manual_placement NUMBER(5, 0) DEFAULT 0, 
	--description of the region correoponding to this placemnt
	description CLOB NULL,
  PRIMARY KEY(tagente_id_agente)
);
CREATE INDEX tgis_data_status_start_t_idx ON tgis_data_status(start_timestamp);

-- This sequence will not work with the 'insert_id' procedure

-- -----------------------------------------------------
-- Table `tgis_map`
-- -----------------------------------------------------
--Table containing information about ais map
CREATE TABLE tgis_map (
    --table identifier
	id_tgis_map NUMBER(10, 0) PRIMARY KEY,
	--Name of the map
	map_name VARCHAR2(63),
	--longitude of the center of the map when it\'s loaded
	initial_longitude BINARY_DOUBLE DEFAULT NULL,
	--latitude of the center of the map when it\'s loaded
	initial_latitude BINARY_DOUBLE DEFAULT NULL,
	--altitude of the center of the map when it\'s loaded
	initial_altitude BINARY_DOUBLE DEFAULT NULL,
	--Zoom level to show when the map is loaded.
	zoom_level NUMBER(5, 0) DEFAULT 1,
	--path on the server to the background image of the map
	map_background VARCHAR2(127) DEFAULT NULL,
	--Default longitude for the agents placed on the map
	default_longitude BINARY_DOUBLE DEFAULT NULL,
	--Default latitude for the agents placed on the map
	default_latitude BINARY_DOUBLE DEFAULT NULL,
	--Default altitude for the agents placed on the map
	default_altitude DOUBLE PRECISION DEFAULT NULL,
	--Group that owns the map
	group_id NUMBER(10, 0) DEFAULT 0,
	--1 if this is the default map, 0 in other case
	default_map NUMBER(5, 0) DEFAULT 0
);
CREATE INDEX tgis_map_tagente_map_name_idx ON tgis_map(map_name);

CREATE SEQUENCE tgis_map_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tgis_map_inc BEFORE INSERT ON tgis_map REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgis_map_s.nextval INTO :NEW.id_tgis_map FROM dual; END tgis_map_inc;;

-- -----------------------------------------------------
-- Table `tgis_map_connection`
-- -----------------------------------------------------
--Table to store the map connection information
CREATE TABLE tgis_map_connection (
	--table id
	id_tmap_connection NUMBER(10, 0) PRIMARY KEY,
	--Name of the connection (name of the base layer)
	conection_name VARCHAR2(45) DEFAULT NULL,
	--Type of map server to connect
	connection_type VARCHAR2(45) DEFAULT NULL, 
	--connection information (this can probably change to fit better the possible connection parameters)
	conection_data CLOB DEFAULT NULL, 
	--Number of zoom levels available
	num_zoom_levels NUMBER(5, 0) DEFAULT NULL, 
	--Default Zoom Level for the connection
	default_zoom_level NUMBER(5, 0) DEFAULT 16,
	--Default longitude for the agents placed on the map
	default_longitude BINARY_DOUBLE DEFAULT NULL,
	--Default latitude for the agents placed on the map
	default_latitude BINARY_DOUBLE DEFAULT NULL,
	--Default altitude for the agents placed on the map
	default_altitude BINARY_DOUBLE DEFAULT NULL,
	--longitude of the center of the map when it\'s loaded
	initial_longitude BINARY_DOUBLE DEFAULT NULL,
	--latitude of the center of the map when it\'s loaded
	initial_latitude BINARY_DOUBLE DEFAULT NULL, 
	--altitude of the center of the map when it\'s loaded
	initial_altitude BINARY_DOUBLE DEFAULT NULL, 
	--Group that owns the map
	group_id NUMBER(10, 0) DEFAULT 0  
);

CREATE SEQUENCE tgis_map_connection_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tgis_map_connection_inc BEFORE INSERT ON tgis_map_connection REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgis_map_connection_s.nextval INTO :NEW.id_tmap_connection FROM dual; END tgis_map_connection_inc;;

-- -----------------------------------------------------
-- Table `tgis_map_has_tgis_map_con` (tgis_map_has_tgis_map_connection)
-- -----------------------------------------------------
--Table to associate a connection to gis map
CREATE TABLE tgis_map_has_tgis_map_con (
	--reference to tgis_map
	tgis_map_id_tgis_map NUMBER(10, 0) REFERENCES tgis_map(id_tgis_map) ON DELETE CASCADE, 
	--reference to tgis_map_connection
	tgis_map_con_id_tmap_con NUMBER(10, 0) REFERENCES tgis_map_connection (id_tmap_connection) ON DELETE CASCADE, 
	--Last Modification Time of the Connection
	modification_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
	--Flag to mark the default map connection of a map
	default_map_connection NUMBER(5, 0) DEFAULT 0,
	PRIMARY KEY (tgis_map_id_tgis_map, tgis_map_con_id_tmap_con)
);
CREATE INDEX tgis_map_has_tgis_map_con1_idx ON tgis_map_has_tgis_map_con(tgis_map_id_tgis_map);
CREATE INDEX tgis_map_has_tgis_map_con2_idx ON tgis_map_has_tgis_map_con(tgis_map_con_id_tmap_con);

--This trigger is for tranlate on update CURRENT_TIMESTAMP of MySQL.
CREATE OR REPLACE TRIGGER tgis_map_has_tgis_map_con_ts BEFORE UPDATE ON tgis_map_has_tgis_map_con FOR EACH ROW BEGIN SELECT CURRENT_TIMESTAMP INTO :NEW.modification_time FROM DUAL; END;;

-- -----------------------------------------------------
-- Table `tgis_map_layer`
-- -----------------------------------------------------
--Table containing information about the map layers
CREATE TABLE tgis_map_layer (
	--table id
	id_tmap_layer NUMBER(10, 0) PRIMARY KEY,
	--Name of the layer
	layer_name VARCHAR2(45), 
	--True if the layer must be shown
	view_layer NUMBER(5, 0) DEFAULT 1, 
	--Number of order of the layer in the layer stack, bigger means upper on the stack.\n
	layer_stack_order NUMBER(5, 0) DEFAULT 0, 
	--reference to the map containing the layer
	tgis_map_id_tgis_map NUMBER(10, 0) DEFAULT 0 REFERENCES tgis_map(id_tgis_map) ON DELETE CASCADE, 
	--reference to theroup shown in the layer
	tgrupo_id_grupo NUMBER(19, 0) 
);

CREATE SEQUENCE tgis_map_layer_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tgis_map_layer_inc BEFORE INSERT ON tgis_map_layer REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tgis_map_layer_s.nextval INTO :NEW.id_tmap_layer FROM dual; END tgis_map_layer_inc;;

-- -----------------------------------------------------
-- Table `tgis_map_layer_has_tagente`
-- -----------------------------------------------------
--Table to define wich agents are shown in a layer
CREATE TABLE tgis_map_layer_has_tagente (
	tgis_map_layer_id_tmap_layer NUMBER(10, 0) REFERENCES tgis_map_layer(id_tmap_layer) ON DELETE CASCADE,
	tagente_id_agente NUMBER(10, 0) REFERENCES tagente(id_agente) ON DELETE CASCADE,
  PRIMARY KEY (tgis_map_layer_id_tmap_layer, tagente_id_agente)
);
CREATE INDEX tgis_map_layer_has_tagente_idx ON tgis_map_layer_has_tagente(tgis_map_layer_id_tmap_layer);
CREATE INDEX tgis_map_layer_has_tagent1_idx ON tgis_map_layer_has_tagente(tagente_id_agente);

-- This sequence will not work with the 'insert_id' procedure

-- ---------------------------------------------------------------------
-- Table `tgroup_stat`
-- ---------------------------------------------------------------------
--Table to storelobal system stats perroup
CREATE TABLE tgroup_stat (
	id_group NUMBER(10, 0) DEFAULT 0 PRIMARY KEY,
	modules NUMBER(10, 0) DEFAULT 0,
	normal NUMBER(10, 0) DEFAULT 0,
	critical NUMBER(10, 0) DEFAULT 0,
	warning NUMBER(10, 0) DEFAULT 0,
	unknown NUMBER(10, 0) DEFAULT 0,
	"non-init" NUMBER(10, 0) DEFAULT 0,
	alerts NUMBER(10, 0) DEFAULT 0,
	alerts_fired NUMBER(10, 0) DEFAULT 0,
	agents NUMBER(10, 0) DEFAULT 0,
	agents_unknown NUMBER(10, 0) DEFAULT 0,
	utimestamp NUMBER(10, 0) DEFAULT 0
);

-- This sequence will not work with the 'insert_id' procedure

------------------------------------------------------------------------
-- Table `tnetwork_map`
------------------------------------------------------------------------
CREATE TABLE tnetwork_map (
	id_networkmap NUMBER(10, 0) PRIMARY KEY,
	id_user VARCHAR2(60) ,
	name VARCHAR2(100) ,
	type VARCHAR2(20) ,
	layout VARCHAR2(20) ,
	nooverlap NUMBER(5, 0) DEFAULT 0,
	simple NUMBER(5, 0) DEFAULT 0,
	regenerate NUMBER(5, 0) DEFAULT 1,
	font_size NUMBER(10, 0) DEFAULT 12,
	id_group NUMBER(10, 0) DEFAULT 0,
	id_module_group NUMBER(10, 0) DEFAULT 0,  
	id_policy NUMBER(10, 0) DEFAULT 0,
	depth VARCHAR2(20),
	only_modules_with_alerts NUMBER(10, 0) DEFAULT 0,
	hide_policy_modules SMALLINT DEFAULT 0,
	zoom BINARY_DOUBLE DEFAULT 1,
	distance_nodes BINARY_DOUBLE DEFAULT 2.5,
	center NUMBER(10, 0) DEFAULT 0,
	contracted_nodes CLOB,
	show_snmp_modules NUMBER(5, 0) DEFAULT 0,
	text_filter VARCHAR(100) DEFAULT '',
	dont_show_subgroups NUMBER(10, 0) DEFAULT 0,
	pandoras_children NUMBER(10, 0) DEFAULT 0,
	show_groups NUMBER(10, 0) DEFAULT 0,
	show_modules NUMBER(10, 0) DEFAULT 0,
	id_agent NUMBER(10, 0) DEFAULT 0,
	server_name VARCHAR(100),
	show_modulegroup NUMBER(10, 0) DEFAULT 0,
	l2_network NUMBER(1, 0) DEFAULT 0,
	id_tag NUMBER(11, 0) DEFAULT 0,
	store_group NUMBER(11, 0) DEFAULT 0
);

CREATE SEQUENCE tnetwork_map_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetwork_map_inc BEFORE INSERT ON tnetwork_map REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetwork_map_s.nextval INTO :NEW.id_networkmap FROM dual; END tnetwork_map_inc;;

-- ---------------------------------------------------------------------
-- Table `tsnmp_filter`
-- ---------------------------------------------------------------------
CREATE TABLE tsnmp_filter (
	id_snmp_filter NUMBER(10, 0) PRIMARY KEY,
	description VARCHAR2(255) DEFAULT '',
	filter VARCHAR2(255) DEFAULT ''
);

CREATE SEQUENCE tsnmp_filter_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tsnmp_filter_inc BEFORE INSERT ON tsnmp_filter REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tsnmp_filter_s.nextval INTO :NEW.id_snmp_filter FROM dual; END tsnmp_filter_inc;;

-- ---------------------------------------------------------------------
-- Table `tagent_custom_fields`
-- ---------------------------------------------------------------------
CREATE TABLE tagent_custom_fields (
	id_field NUMBER(10, 0) PRIMARY KEY,
	name VARCHAR2(45) DEFAULT '',
	display_on_front NUMBER(5, 0) DEFAULT 0
);

CREATE SEQUENCE tagent_custom_fields_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tagent_custom_fields_inc BEFORE INSERT ON tagent_custom_fields REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tagent_custom_fields_s.nextval INTO :NEW.id_field FROM dual; END tagent_custom_fields_inc;;

-- ---------------------------------------------------------------------
-- Table `tagent_custom_data`
-- ---------------------------------------------------------------------
CREATE TABLE tagent_custom_data (
	id_field NUMBER(10, 0) REFERENCES tagent_custom_fields(id_field) ON DELETE CASCADE,
	id_agent NUMBER(10, 0) REFERENCES tagente(id_agente) ON DELETE CASCADE,
	description CLOB DEFAULT '',
  PRIMARY KEY (id_field, id_agent)
);

-- This sequence will not work with the 'insert_id' procedure

-- on update trigger
CREATE OR REPLACE TRIGGER tagent_custom_data_update AFTER UPDATE OF id_field on tagent_custom_fields FOR EACH ROW BEGIN UPDATE tagent_custom_data SET id_field = :NEW.id_field WHERE id_field = :OLD.id_field; END;;

-- on update trigger 1
CREATE OR REPLACE TRIGGER tagent_custom_data_update1 AFTER UPDATE OF id_agente on tagente FOR EACH ROW BEGIN UPDATE tagent_custom_data SET id_agent = :NEW.id_agente WHERE id_agent = :OLD.id_agente; END;;

-- ---------------------------------------------------------------------
-- Table `ttag`
-- ---------------------------------------------------------------------
CREATE TABLE ttag ( 
	id_tag NUMBER(10, 0) PRIMARY KEY, 
	name VARCHAR2(100) DEFAULT '', 
	description CLOB DEFAULT '', 
	url CLOB DEFAULT '',
	email CLOB NULL,
	phone CLOB NULL
); 

CREATE SEQUENCE ttag_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER ttag_inc BEFORE INSERT ON ttag REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT ttag_s.nextval INTO :NEW.id_tag FROM dual; END ttag_inc;;

-- ---------------------------------------------------------------------
-- Table `ttag_module`
-- ---------------------------------------------------------------------
CREATE TABLE ttag_module (
	id_tag NUMBER(10, 0),
	id_agente_modulo NUMBER(10, 0) DEFAULT 0,
	id_policy_module NUMBER(10, 0) DEFAULT 0,
	PRIMARY KEY  (id_tag, id_agente_modulo)
); 
CREATE INDEX ttag_module_id_ag_modulo_idx ON ttag_module(id_agente_modulo);

-- This sequence will not work with the 'insert_id' procedure

-- ---------------------------------------------------------------------
-- Table `ttag_policy_module`
-- ---------------------------------------------------------------------
CREATE TABLE ttag_policy_module ( 
	id_tag NUMBER(10, 0), 
	id_policy_module NUMBER(10, 0) DEFAULT 0, 
	PRIMARY KEY  (id_tag, id_policy_module)
);
CREATE INDEX ttag_poli_mod_id_pol_mo_idx ON ttag_policy_module(id_policy_module);

-- This sequence will not work with the 'insert_id' procedure

-- -----------------------------------------------------
-- Table `tnetflow_filter`
-- -----------------------------------------------------
CREATE TABLE tnetflow_filter (
	id_sg NUMBER(10, 0) PRIMARY KEY,
	id_name VARCHAR2(600),
	id_group NUMBER(10, 0),
	ip_dst CLOB,
	ip_src CLOB,
	dst_port CLOB,
	src_port CLOB,
	advanced_filter CLOB,
	filter_args CLOB,
	aggregate VARCHAR2(60),
	output VARCHAR2(60)
);

CREATE SEQUENCE tnetflow_filter_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_filter_inc BEFORE INSERT ON tnetflow_filter REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_filter_s.nextval INTO :NEW.id_sg FROM dual; END tnetflow_filter_inc;;

-- -----------------------------------------------------
-- Table `tnetflow_report`
-- -----------------------------------------------------
CREATE TABLE tnetflow_report (
	id_report NUMBER(10, 0) PRIMARY KEY,
	id_name VARCHAR2(100),
	description CLOB DEFAULT '',
	id_group NUMBER(10, 0),
	server_name CLOB DEFAULT ''
);

CREATE SEQUENCE tnetflow_report_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_report_inc BEFORE INSERT ON tnetflow_report REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_report_s.nextval INTO :NEW.id_report FROM dual; END tnetflow_report_inc;;

-- -----------------------------------------------------
-- Table `tnetflow_report_content`
-- -----------------------------------------------------
CREATE TABLE tnetflow_report_content (
	id_rc NUMBER(10, 0) PRIMARY KEY,
	id_report NUMBER(10, 0) REFERENCES tnetflow_report(id_report) ON DELETE CASCADE,
	id_filter NUMBER(10,0) REFERENCES tnetflow_filter(id_sg) ON DELETE CASCADE,
	description CLOB DEFAULT '',
	"date" NUMBER(20, 0) DEFAULT 0,
	period NUMBER(11, 0) DEFAULT 0,
	max NUMBER(11, 0) DEFAULT 0,
	show_graph VARCHAR2(60),
	"order" NUMBER(11,0) DEFAULT 0
);

CREATE SEQUENCE tnetflow_report_content_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tnetflow_report_content_inc BEFORE INSERT ON tnetflow_report_content REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tnetflow_report_content_s.nextval INTO :NEW.id_rc FROM dual; END tnetflow_report_content_inc;;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------
CREATE TABLE tevent_filter (
	id_filter NUMBER(10, 0) PRIMARY KEY,
	id_group_filter NUMBER(10, 0) DEFAULT 0,
	id_name VARCHAR2(600),
	id_group NUMBER(10, 0) DEFAULT 0,
	event_type CLOB DEFAULT '',
	severity NUMBER(10, 0) DEFAULT -1,
	status NUMBER(10, 0) DEFAULT -1,
	search CLOB DEFAULT '',
	text_agent CLOB DEFAULT '',
	id_agent NUMBER(10, 0) DEFAULT 0,
	id_agent_module NUMBER(10, 0) DEFAULT 0,
	pagination NUMBER(10, 0) DEFAULT 25,
	event_view_hr NUMBER(10, 0) DEFAULT 8,
	id_user_ack CLOB,
	group_rep NUMBER(10, 0) DEFAULT 0,
	tag_with CLOB,
	tag_without CLOB,
	date_from DATE DEFAULT NULL,
	date_to DATE DEFAULT NULL,
	filter_only_alert NUMBER(10, 0) DEFAULT -1
);

CREATE SEQUENCE tevent_filter_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tevent_filter_inc BEFORE INSERT ON tevent_filter REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tevent_filter_s.nextval INTO :NEW.id_filter FROM dual; END tevent_filter_inc;;

-- ---------------------------------------------------------------------
-- Table `tpassword_history`
-- ---------------------------------------------------------------------
CREATE TABLE tpassword_history (
	id_pass  NUMBER(10) PRIMARY KEY,
	id_user varchar2(60),
	password varchar2(45) DEFAULT '',
	date_begin TIMESTAMP,
	date_end TIMESTAMP
);

CREATE SEQUENCE tpassword_history_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tpassword_history_inc BEFORE INSERT ON tpassword_history REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tpassword_history_s.nextval INTO :NEW.id_pass FROM dual; END tpassword_history_inc;;

-- ---------------------------------------------------------------------
-- Table `tevent_response`
-- ---------------------------------------------------------------------
CREATE TABLE tevent_response (
	id  NUMBER(10) PRIMARY KEY,
	name varchar2(600) DEFAULT '',
	description CLOB,
	target CLOB,
	type varchar2(60),
	id_group NUMBER(10, 0) DEFAULT 0,
	modal_width NUMBER(10, 0) DEFAULT 0,
	modal_height NUMBER(10, 0) DEFAULT 0,
	new_window NUMBER(10, 0) DEFAULT 0,
	params CLOB
);

CREATE SEQUENCE tevent_response_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tevent_response_inc BEFORE INSERT ON tevent_response REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tevent_response_s.nextval INTO :NEW.id FROM dual; END tevent_response_inc;;

-- ---------------------------------------------------------------------
-- Table `tcategory`
-- ---------------------------------------------------------------------
CREATE TABLE tcategory ( 
	id NUMBER(10, 0) PRIMARY KEY, 
	name VARCHAR2(600) DEFAULT ''
);

CREATE SEQUENCE tcategory_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tcategory_inc BEFORE INSERT ON tcategory REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tcategory_s.nextval INTO :NEW.ID FROM dual; END tcategory_inc;;

-- ---------------------------------------------------------------------
-- Table `tupdate_settings`
-- ---------------------------------------------------------------------
CREATE TABLE tupdate_settings (
	"key" VARCHAR2(255) DEFAULT '' PRIMARY KEY,
	"value" VARCHAR2(255) DEFAULT ''
);

-- This sequence will not work with the 'insert_id' procedure

-- ---------------------------------------------------------------------
-- Table `tupdate_package`
-- ---------------------------------------------------------------------
CREATE TABLE tupdate_package ( 
	id NUMBER(10, 0) PRIMARY KEY, 
	timestamp  TIMESTAMP DEFAULT NULL, 
	description VARCHAR2(255) DEFAULT ''
);

CREATE SEQUENCE tupdate_package_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tupdate_package_inc BEFORE INSERT ON tupdate_package REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tupdate_package_s.nextval INTO :NEW.id FROM dual; END tupdate_package_inc;;

-- ---------------------------------------------------------------------
-- Table `tupdate`
-- ---------------------------------------------------------------------
CREATE TABLE tupdate ( 
	id NUMBER(10, 0) PRIMARY KEY, 
	type VARCHAR2(15), 
	id_update_package NUMBER(10, 0) DEFAULT 0 REFERENCES tupdate_package(id) ON DELETE CASCADE, 
	filename VARCHAR2(250) DEFAULT '', 
	checksum VARCHAR2(250) DEFAULT '', 
	previous_checksum VARCHAR2(250) DEFAULT '', 
	svn_version NUMBER(10, 0) DEFAULT 0, 
	data CLOB DEFAULT '', 
	data_rollback CLOB DEFAULT '', 
	description CLOB DEFAULT '', 
	db_table_name VARCHAR2(140) DEFAULT '', 
	db_field_name VARCHAR2(140) DEFAULT '', 
	db_field_value VARCHAR2(1024) DEFAULT '', 
	CONSTRAINT tupdate_type_cons CHECK (type IN ('code', 'db_data', 'db_schema', 'binary'))
);

CREATE SEQUENCE tupdate_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tupdate_inc BEFORE INSERT ON tupdate REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tupdate_s.nextval INTO :NEW.id FROM dual; END;;
CREATE OR REPLACE TRIGGER tupdate_update AFTER UPDATE OF id on tupdate_package FOR EACH ROW BEGIN UPDATE tupdate SET id_update_package = :NEW.id WHERE id_update_package = :OLD.id; END;;

-- ---------------------------------------------------------------------
-- Table `tupdate_journal`
-- ---------------------------------------------------------------------
CREATE TABLE tupdate_journal ( 
	id NUMBER(10, 0) PRIMARY KEY, 
	id_update NUMBER(10, 0) DEFAULT 0 REFERENCES tupdate(id) ON DELETE CASCADE
);

CREATE SEQUENCE tupdate_journal_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tupdate_journal_inc BEFORE INSERT ON tupdate_journal REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tupdate_journal_s.nextval INTO :NEW.id FROM dual; END;;
CREATE OR REPLACE TRIGGER tupdate_journal_update AFTER UPDATE OF id on tupdate FOR EACH ROW BEGIN UPDATE tupdate_journal SET id = :NEW.id WHERE id = :OLD.id; END;;

-- ---------------------------------------------------------------------
-- Table `talert_snmp_action`
-- ---------------------------------------------------------------------
CREATE TABLE talert_snmp_action (
	id NUMBER(10, 0) PRIMARY KEY,
	id_alert_snmp NUMBER(10, 0) DEFAULT 0,
	alert_type NUMBER(2, 0) DEFAULT 0,
	al_field1 CLOB DEFAULT '',
	al_field2 CLOB DEFAULT '',
	al_field3 CLOB DEFAULT '',
	al_field4 CLOB DEFAULT '',
	al_field5 CLOB DEFAULT '',
	al_field6 CLOB DEFAULT '',
	al_field7 CLOB DEFAULT '',
	al_field8 CLOB DEFAULT '',
	al_field9 CLOB DEFAULT '',
	al_field10 CLOB DEFAULT ''
);
CREATE SEQUENCE talert_snmp_action_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER talert_snmp_action_inc BEFORE INSERT ON talert_snmp_action REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT talert_snmp_action_s.nextval INTO :NEW.id FROM dual; END;;

-- This sequence will not work with the 'insert_id' procedure

-- ---------------------------------------------------------------------
-- Table `tsessions_php`
-- ---------------------------------------------------------------------
CREATE TABLE tsessions_php (
	id_session VARCHAR2(52) PRIMARY KEY,
	last_active NUMBER(20, 0),
	data CLOB DEFAULT ''
);

-- This sequence will not work with the 'insert_id' procedure

-- ---------------------------------------------------------------------
-- Table `tmap`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tmap (
	id NUMBER(10, 0) PRIMARY KEY,
	id_group NUMBER(10, 0) DEFAULT 0,
	id_user VARCHAR2(100) DEFAULT '',
	type NUMBER(5, 0) DEFAULT 0,
	subtype NUMBER(5, 0) DEFAULT 0,
	name VARCHAR2(100) DEFAULT '',
	description CLOB DEFAULT '',
	height NUMBER(10, 0) DEFAULT 0,
	width NUMBER(10, 0) DEFAULT 0,
	center_x NUMBER(10, 0) DEFAULT 0,
	center_y NUMBER(10, 0) DEFAULT 0,
	background VARCHAR2(100) DEFAULT '',
	background_options NUMBER(10, 0) DEFAULT 0,
	source_period NUMBER(10, 0) DEFAULT 0,
	source NUMBER(10, 0) DEFAULT 0,
	source_data VARCHAR2(250) DEFAULT '',
	generation_method NUMBER(10, 0) DEFAULT 0,
	generated NUMBER(10, 0) DEFAULT 0,
	filter CLOB DEFAULT '',
);

CREATE SEQUENCE tmap_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER tmap_inc BEFORE INSERT ON tmap REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT tmap_s.nextval INTO :NEW.id FROM dual; END tmap_inc;;

-- ---------------------------------------------------------------------
-- Table titem
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS titem (
	id NUMBER(10, 0) PRIMARY KEY,
	id_map NUMBER(10, 0) DEFAULT 0,
	x NUMBER(10, 0) DEFAULT 0,
	y NUMBER(10, 0) DEFAULT 0,
	z NUMBER(10, 0) DEFAULT 0,
	deleted NUMBER(1, 0) DEFAULT 0,
	type NUMBER(5, 0) DEFAULT 0,
	refresh NUMBER(10, 0) DEFAULT 0,
	source NUMBER(10, 0) DEFAULT 0,
	source_data VARCHAR2(250) DEFAULT '',
	options CLOB DEFAULT '',
	style CLOB DEFAULT ''
);
CREATE SEQUENCE titem_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER titem_inc BEFORE INSERT ON titem REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT titem_s.nextval INTO :NEW.id FROM dual; END titem_inc;;

-- ---------------------------------------------------------------------
-- Table trel_item
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS trel_item (
	id NUMBER(10, 0) PRIMARY KEY,
	id_parent NUMBER(10, 0) DEFAULT 0,
	id_child NUMBER(10, 0) DEFAULT 0,
	id_parent_source_data NUMBER(10, 0) DEFAULT 0,
	id_child_source_data NUMBER(10, 0) DEFAULT 0,
	parent_type NUMBER(5, 0) DEFAULT 0,
	child_type NUMBER(5, 0) DEFAULT 0,
	id_item NUMBER(10, 0) DEFAULT 0,
	deleted NUMBER(1, 0) DEFAULT 0
);
CREATE SEQUENCE trel_item_s INCREMENT BY 1 START WITH 1;
CREATE OR REPLACE TRIGGER trel_item_inc BEFORE INSERT ON trel_item REFERENCING NEW AS NEW FOR EACH ROW BEGIN SELECT trel_item_s.nextval INTO :NEW.id FROM dual; END trel_item_inc;;
