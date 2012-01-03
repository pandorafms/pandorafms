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
-- Pandora FMS official tables for 3.2 version              --
-- -----------------------------------------------------------

-- The charset is for all DB not only table.
--CREATE DATABASE "pandora" WITH ENCODING 'utf8';

--\c "pandora"

CREATE LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION unix_timestamp(TIMESTAMP without time zone = CURRENT_TIMESTAMP) RETURNS double precision AS 'SELECT ceil(date_part(''epoch'', $1)); ' LANGUAGE SQL;

CREATE TABLE "taddress" ("id_a" SERIAL NOT NULL PRIMARY KEY,"ip" VARCHAR(60) NOT NULL default '',"ip_pack" INTEGER NOT NULL default 0);
CREATE INDEX "taddress_ip_idx" ON "taddress"("ip");

CREATE TABLE "taddress_agent" ("id_ag" BIGSERIAL NOT NULL PRIMARY KEY,"id_a" BIGINT NOT NULL default 0,"id_agent" BIGINT NOT NULL default 0);

CREATE TABLE "tagente" (
	"id_agente" SERIAL NOT NULL PRIMARY KEY,
	"nombre" varchar(600) NOT NULL default '',
	"direccion" varchar(100) default NULL,
	"comentarios" varchar(255) default '',
	"id_grupo" INTEGER NOT NULL default 0,
	"ultimo_contacto" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"modo" SMALLINT NOT NULL default 0,
	"intervalo" INTEGER NOT NULL default 300,
	"id_os" INTEGER default 0,
	"os_version" varchar(100) default '',
	"agent_version" varchar(100) default '',
	"ultimo_contacto_remoto" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"disabled" SMALLINT NOT NULL default 0,
	"id_parent" INTEGER default 0,
	"custom_id" varchar(255) default '',
	"server_name" varchar(100) default '',
	"cascade_protection" SMALLINT NOT NULL default 0, 
	--number of hours of diference with the server timezone
	"timezone_offset" SMALLINT NULL DEFAULT 0,
	 --path in the server to the image of the icon representing the agent
	"icon_path" VARCHAR(127) NULL DEFAULT NULL,
	 --set it to one to update the position data (altitude, longitude, latitude) when getting information from the agent or to 0 to keep the last value and don\'t update it
	"update_gis_data" SMALLINT NOT NULL DEFAULT 1
);
CREATE INDEX "tagente_nombre_idx" ON "tagente"("nombre");
CREATE INDEX "tagente_direccion_idx" ON "tagente"("direccion");
CREATE INDEX "tagente_disabled_idx" ON "tagente"("disabled");
CREATE INDEX "tagente_id_grupo_idx" ON "tagente"("id_grupo");

CREATE TABLE "tagente_datos" (
	"id_agente_modulo" INTEGER NOT NULL default 0,
	"datos" DOUBLE PRECISION default NULL,
	"utimestamp" BIGINT default 0
);
CREATE INDEX "tagente_datos_id_agente_modulo_idx" ON "tagente_datos"("id_agente_modulo");
CREATE INDEX "tagente_datos_utimestamp_idx" ON "tagente_datos"("utimestamp");

CREATE TABLE "tagente_datos_inc" (
	"id_adi" SERIAL NOT NULL PRIMARY KEY,
	"id_agente_modulo" INTEGER NOT NULL default 0,
	"datos" DOUBLE PRECISION default NULL,
	"utimestamp" INTEGER NOT NULL default 0
);
CREATE INDEX "tagente_datos_inc_id_agente_modulo_idx" ON "tagente_datos_inc"("id_agente_modulo");



CREATE TABLE "tagente_datos_string" (
	"id_agente_modulo" INTEGER NOT NULL default 0,
	"datos" TEXT NOT NULL,
	"utimestamp" INTEGER NOT NULL default 0
);
CREATE INDEX "tagente_datos_string_id_agente_modulo_idx" ON "tagente_datos_string"("id_agente_modulo");
CREATE INDEX "tagente_datos_string_utimestamp_idx" ON "tagente_datos_string"("utimestamp");



CREATE TABLE "tagente_datos_log4x" (
	"id_tagente_datos_log4x" BIGSERIAL NOT NULL PRIMARY KEY,
	"id_agente_modulo" INTEGER NOT NULL default 0,

	"severity" text NOT NULL,
	"message" text NOT NULL,
	"stacktrace" text NOT NULL,

	"utimestamp" INTEGER NOT NULL default 0
);
CREATE INDEX "tagente_datos_log4x_id_agente_modulo_idx" ON "tagente_datos_log4x"("id_agente_modulo");

CREATE TABLE "tagente_estado" (
	"id_agente_estado" SERIAL NOT NULL PRIMARY KEY,
	"id_agente_modulo" INTEGER NOT NULL default 0,
	"datos" text NOT NULL default '',
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"estado" INTEGER NOT NULL default 0,
	"id_agente" INTEGER NOT NULL default 0,
	"last_try" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"utimestamp" BIGINT NOT NULL default 0,
	"current_interval" INTEGER NOT NULL default 0,
	"running_by" INTEGER default 0,
	"last_execution_try" BIGINT NOT NULL default 0,
	"status_changes" INTEGER default 0,
	"last_status" INTEGER default 0
);
CREATE INDEX "tagente_estado_id_agente_modulo_idx" ON "tagente_estado"("id_agente_modulo");
CREATE INDEX "tagente_estado_id_agente_idx" ON "tagente_estado"("id_agente");
CREATE INDEX "tagente_estado_estado_idx" ON "tagente_estado"("estado");
CREATE INDEX "tagente_estado_current_interval_idx" ON "tagente_estado"("current_interval");
CREATE INDEX "tagente_estado_running_by_idx" ON "tagente_estado"("running_by");
CREATE INDEX "tagente_estado_last_execution_try_idx" ON "tagente_estado"("last_execution_try");

-- Probably last_execution_try index is not useful and loads more than benefits

-- id_modulo now uses tmodule 
-- ---------------------------
-- 1 - Data server modules (agent related modules)
-- 2 - Network server modules
-- 4 - Plugin server
-- 5 - Predictive server
-- 6 - WMI server
-- 7 - WEB Server (enteprise)

CREATE TABLE "tagente_modulo" (
	"id_agente_modulo" SERIAL NOT NULL PRIMARY KEY,
	"id_agente" INTEGER NOT NULL default 0,
	"id_tipo_modulo" INTEGER NOT NULL default 0,
	"descripcion" TEXT NOT NULL default '',
	"extended_info" TEXT NOT NULL default '',
	"nombre" TEXT NOT NULL default '',
	"unit" TEXT default '',
	"id_policy_module" INTEGER NOT NULL default 0,
	"max" BIGINT NOT NULL default 0,
	"min" BIGINT NOT NULL default 0,
	"module_interval" INTEGER NOT NULL default 0,
	"tcp_port" INTEGER NOT NULL default 0,
	"tcp_send" TEXT default '',
	"tcp_rcv" TEXT default '',
	"snmp_community" varchar(100) default '',
	"snmp_oid" varchar(255) default '0',
	"ip_target" varchar(100) default '',
	"id_module_group" INTEGER NOT NULL default 0,
	"flag" SMALLINT NOT NULL default 1,
	"id_modulo" INTEGER NOT NULL default 0,
	"disabled" SMALLINT NOT NULL default 0,
	"id_export" INTEGER NOT NULL default 0,
	"plugin_user" text default '',
	"plugin_pass" text default '',
	"plugin_parameter" text,
	"id_plugin" INTEGER default 0,
	"post_process" DOUBLE PRECISION default NULL,
	"prediction_module" BIGINT default 0,
	"max_timeout" INTEGER default 0,
	"custom_id" varchar(255) default '',
	"history_data"  SMALLINT default 1,
	"min_warning" DOUBLE PRECISION default 0,
	"max_warning" DOUBLE PRECISION default 0,
	"str_warning" text,
	"min_critical" DOUBLE PRECISION default 0,
	"max_critical" DOUBLE PRECISION default 0,
	"str_critical" text,
	"min_ff_event" INTEGER default 0,
	"delete_pending" SMALLINT NOT NULL default 0,
	"policy_linked" SMALLINT NOT NULL default 0,
	"policy_adopted" SMALLINT NOT NULL default 0,
	"custom_string_1" text default '',
	"custom_string_2" text default '',
	"custom_string_3" text default '',
	"custom_integer_1" INTEGER default 0,
	"custom_integer_2" INTEGER default 0
);
CREATE INDEX "tagente_modulo_id_agente_idx" ON "tagente_modulo"("id_agente");
CREATE INDEX "tagente_modulo_id_tipo_modulo_idx" ON "tagente_modulo"("id_tipo_modulo");
CREATE INDEX "tagente_modulo_disabled_idx" ON "tagente_modulo"("disabled");

-- snmp_oid is also used for WMI query

CREATE TABLE "tagent_access" (
	"id_agent" INTEGER NOT NULL default 0,
	"utimestamp" BIGINT NOT NULL default 0
);
CREATE INDEX "tagent_access_id_agent_idx" ON "tagent_access"("id_agent");
CREATE INDEX "tagent_access_utimestamp_idx" ON "tagent_access"("utimestamp");

CREATE TABLE "talert_snmp" (
	"id_as" SERIAL NOT NULL PRIMARY KEY,
	"id_alert" INTEGER NOT NULL default 0,
	"al_field1" text NOT NULL default '',
	"al_field2" text NOT NULL default '',
	"al_field3" text NOT NULL default '',
	"description" varchar(255) default '',
	"alert_type" SMALLINT NOT NULL default 0,
	"agent" varchar(100) default '',
	"custom_oid" text default '',
	"oid" varchar(255) NOT NULL default '',
	"time_threshold" INTEGER NOT NULL default 0,
	"times_fired" SMALLINT NOT NULL default 0,
	"last_fired" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"max_alerts" INTEGER NOT NULL default 1,
	"min_alerts" INTEGER NOT NULL default 1,
	"internal_counter" INTEGER NOT NULL default 0,
	"priority" INTEGER default 0
);

CREATE TABLE "talert_commands" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default '',
	"command" text default '',
	"description" text default '',
	"internal" SMALLINT default 0
);

CREATE TABLE "talert_actions" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" text default '',
	"id_alert_command" INTEGER NOT NULL REFERENCES talert_commands("id")  ON DELETE CASCADE ON UPDATE CASCADE,
	"field1" text NOT NULL default '',
	"field2" text default '',
	"field3" text default '',
	"id_group" BIGINT NOT NULL default 0,
	"action_threshold" BIGINT NOT NULL default 0
);

CREATE TYPE type_talert_templates_alert_template AS ENUM ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal', 'warning', 'critical', 'onchange', 'unknown', 'always');
CREATE TABLE "talert_templates" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" text default '',
	"description" TEXT,
	"id_alert_action" INTEGER REFERENCES talert_actions("id")  ON DELETE SET NULL ON UPDATE CASCADE,
	"field1" text default '',
	"field2" text default '',
	"field3" text NOT NULL,
	"type" type_talert_templates_alert_template,
	"value" varchar(255) default '',
	"matches_value" SMALLINT default 0,
	"max_value" DOUBLE PRECISION default NULL,
	"min_value" DOUBLE PRECISION default NULL,
	"time_threshold" INTEGER NOT NULL default 0,
	"max_alerts" INTEGER NOT NULL default 1,
	"min_alerts" INTEGER NOT NULL default 0,
	"time_from" TIME without time zone default '00:00:00',
	"time_to" TIME without time zone default '00:00:00',
	"monday" SMALLINT default 1,
	"tuesday" SMALLINT default 1,
	"wednesday" SMALLINT default 1,
	"thursday" SMALLINT default 1,
	"friday" SMALLINT default 1,
	"saturday" SMALLINT default 1,
	"sunday" SMALLINT default 1,
	"recovery_notify" SMALLINT default 0,
	"field2_recovery" text NOT NULL default '',
	"field3_recovery" text NOT NULL,
	"priority" INTEGER NOT NULL default 0,
	"id_group" INTEGER NOT NULL default 0
);
CREATE INDEX "talert_templates_id_alert_action_idx" ON "talert_templates"("id_alert_action");

CREATE TABLE "talert_template_modules" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_agent_module" INTEGER NOT NULL REFERENCES tagente_modulo("id_agente_modulo") ON DELETE CASCADE ON UPDATE CASCADE,
	"id_alert_template" INTEGER NOT NULL REFERENCES talert_templates("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"id_policy_alerts" INTEGER NOT NULL default 0,
	"internal_counter" INTEGER default 0,
	"last_fired" BIGINT NOT NULL default 0,
	"last_reference" BIGINT NOT NULL default 0,
	"times_fired" INTEGER NOT NULL default 0,
	"disabled" SMALLINT default 0,
	"standby" SMALLINT default 0,
	"priority" INTEGER default 0,
	"force_execution" SMALLINT default 0
);
CREATE UNIQUE INDEX "talert_template_modules_id_agent_module_idx" ON "talert_template_modules"("id_agent_module");

CREATE TABLE "talert_template_module_actions" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_alert_template_module" INTEGER NOT NULL REFERENCES talert_template_modules("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"id_alert_action" INTEGER NOT NULL REFERENCES talert_actions("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"fires_min" INTEGER NOT NULL default 0,
	"fires_max" INTEGER NOT NULL default 0
);

CREATE TABLE "talert_compound" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(255) default '',
	"description" TEXT,
	"id_agent" INTEGER NOT NULL REFERENCES tagente("id_agente") ON DELETE CASCADE ON UPDATE CASCADE,
	"time_threshold" INTEGER NOT NULL default 0,
	"max_alerts" INTEGER NOT NULL default 1,
	"min_alerts" INTEGER NOT NULL default 0,
	"time_from" TIME without time zone default '00:00:00',
	"time_to" TIME without time zone default '00:00:00',
	"monday" SMALLINT default 1,
	"tuesday" SMALLINT default 1,
	"wednesday" SMALLINT default 1,
	"thursday" SMALLINT default 1,
	"friday" SMALLINT default 1,
	"saturday" SMALLINT default 1,
	"sunday" SMALLINT default 1,
	"recovery_notify" SMALLINT default 0,
	"field2_recovery" varchar(255) NOT NULL default '',
	"field3_recovery" TEXT NOT NULL,
	"internal_counter" INTEGER default 0,
	"last_fired" BIGINT NOT NULL default 0,
	"last_reference" BIGINT NOT NULL default 0,
	"times_fired" INTEGER NOT NULL default 0,
	"disabled" SMALLINT default 0,
	"priority" SMALLINT default 0
);

CREATE TYPE type_talert_compound_elements_operation AS ENUM ('NOP', 'AND','OR','XOR','NAND','NOR','NXOR');
CREATE TABLE "talert_compound_elements" (
	"id_alert_compound" INTEGER NOT NULL REFERENCES talert_compound("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"id_alert_template_module" INTEGER NOT NULL REFERENCES talert_template_modules("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"operation" type_talert_compound_elements_operation,
	"order"  SMALLINT default 0
);
CREATE UNIQUE INDEX "talert_compound_elements_id_alert_compound_idx" ON "talert_compound_elements"("id_alert_compound");

CREATE TABLE "talert_compound_actions" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_alert_compound" INTEGER NOT NULL REFERENCES talert_compound("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"id_alert_action" INTEGER NOT NULL REFERENCES talert_actions("id") ON DELETE CASCADE ON UPDATE CASCADE,
	"fires_min"  INTEGER default 0,
	"fires_max"  INTEGER default 0
);

-- Priority : 0 - Maintance (grey)
-- Priority : 1 - Low (green)
-- Priority : 2 - Normal (blue)
-- Priority : 3 - Warning (yellow)
-- Priority : 4 - Critical (red)
CREATE TABLE "tattachment" (
	"id_attachment" SERIAL NOT NULL PRIMARY KEY,
	"id_incidencia" INTEGER NOT NULL default 0,
	"id_usuario" varchar(60) NOT NULL default '',
	"filename" varchar(255) NOT NULL default '',
	"description" varchar(150) default '',
	"size" BIGINT NOT NULL default 0
);

CREATE TABLE "tconfig" (
	"id_config" SERIAL NOT NULL PRIMARY KEY,
	"token" varchar(100) NOT NULL default '',
	"value" varchar(100) NOT NULL default ''
);

CREATE TABLE "tconfig_os" (
	"id_os" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default '',
	"description" varchar(250) default '',
	"icon_name" varchar(100) default ''
);

CREATE TYPE type_tevento_event AS ENUM ('unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change');
CREATE TABLE "tevento" (
	"id_evento" BIGSERIAL NOT NULL PRIMARY KEY,
	"id_agente" INTEGER NOT NULL default 0,
	"id_usuario" varchar(100) NOT NULL default '0',
	"id_grupo" INTEGER NOT NULL default 0,
	"estado" INTEGER NOT NULL default 0,
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"evento" text NOT NULL default '',
	"utimestamp" BIGINT NOT NULL default 0,
	"event_type" type_tevento_event default 'unknown',
	"id_agentmodule" INTEGER NOT NULL default 0,
	"id_alert_am" INTEGER NOT NULL default 0,
	"criticity" INTEGER NOT NULL default 0,
	"user_comment" text NOT NULL,
	"tags" text NOT NULL
);
CREATE INDEX "tevento_id_1_idx" ON "tevento"("id_agente", "id_evento");
CREATE INDEX "tevento_id_2_idx" ON "tevento"("utimestamp", "id_evento");
CREATE INDEX "tevento_id_agentmodule_idx" ON "tevento"("id_agentmodule");

-- Criticity: 0 - Maintance (grey)
-- Criticity: 1 - Informational (blue)
-- Criticity: 2 - Normal (green) (status 0)
-- Criticity: 3 - Warning (yellow) (status 2)
-- Criticity: 4 - Critical (red) (status 1)
CREATE TABLE "tgrupo" (
	"id_grupo" SERIAL NOT NULL PRIMARY KEY,
	"nombre" text NOT NULL default '',
	"icon" varchar(50) default 'world',
	"parent" INTEGER NOT NULL default 0,
	"propagate" SMALLINT default 0,
	"disabled" SMALLINT default 0,
	"custom_id" varchar(255) default '',
	"id_skin" INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE "tincidencia" (
	"id_incidencia" BIGSERIAL NOT NULL PRIMARY KEY,
	"inicio" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"cierre" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"titulo" text NOT NULL default '',
	"descripcion" text NOT NULL,
	"id_usuario" varchar(60) NOT NULL default '',
	"origen" varchar(100) NOT NULL default '',
	"estado" INTEGER NOT NULL default 0,
	"prioridad" INTEGER NOT NULL default 0,
	"id_grupo" INTEGER NOT NULL default 0,
	"actualizacion" TIMESTAMP without time zone default CURRENT_TIMESTAMP,
	"id_creator" varchar(60) default NULL,
	"id_lastupdate" varchar(60) default NULL,
	"id_agente_modulo" BIGINT NOT NULL,
	"notify_email" INTEGER NOT NULL default 0
);
CREATE INDEX "tincidencia_id_1_idx" ON "tincidencia"("id_usuario","id_incidencia");
CREATE INDEX "tincidencia_id_agente_modulo_idx" ON "tincidencia"("id_agente_modulo");
--This function is for to tranlate "on update CURRENT_TIMESTAMP" of MySQL.
	--It is in only one line because the parser of Pandora installer execute the code at the end with ;
CREATE OR REPLACE FUNCTION update_tincidencia_actualizacion() RETURNS TRIGGER AS $$ BEGIN NEW.actualizacion = now(); RETURN NEW; END; $$ language 'plpgsql';
CREATE TRIGGER trigger_tincidencia_actualizacion BEFORE UPDATE ON tincidencia FOR EACH ROW EXECUTE PROCEDURE update_tincidencia_actualizacion();

CREATE TABLE "tlanguage" (
	"id_language" varchar(6) NOT NULL default '',
	"name" varchar(100) NOT NULL default ''
);

CREATE TABLE "tlink" (
	"id_link" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default '',
	"link" varchar(255) NOT NULL default ''
);

CREATE TABLE "tmensajes" (
	"id_mensaje" SERIAL NOT NULL PRIMARY KEY,
	"id_usuario_origen" varchar(60) NOT NULL default '',
	"id_usuario_destino" varchar(60) NOT NULL default '',
	"mensaje" TEXT NOT NULL,
	"timestamp" BIGINT NOT NULL default 0,
	"subject" varchar(255) NOT NULL default '',
	"estado" INTEGER NOT NULL default 0
);

CREATE TABLE "tmodule_group" (
	"id_mg" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(150) NOT NULL default ''
);

CREATE TABLE "tnetwork_component" (
	"id_nc" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(80) NOT NULL,
	"description" varchar(250) default NULL,
	"id_group" INTEGER NOT NULL default 1,
	"type" INTEGER NOT NULL default 6,
	"max" BIGINT NOT NULL default 0,
	"min" BIGINT NOT NULL default 0,
	"module_interval" BIGINT NOT NULL default 0,
	"tcp_port" INTEGER NOT NULL default 0,
	"tcp_send" text NOT NULL,
	"tcp_rcv" text NOT NULL,
	"snmp_community" varchar(255) NOT NULL default 'NULL',
	"snmp_oid" varchar(400) NOT NULL,
	"id_module_group" INTEGER NOT NULL default 0,
	"id_modulo" INTEGER NOT NULL default 0,
	"id_plugin" INTEGER default 0,
	"plugin_user" text default '',
	"plugin_pass" text default '',
	"plugin_parameter" text,
	"max_timeout" INTEGER default 0,
	"history_data" SMALLINT default 1,
	"min_warning" DOUBLE PRECISION default 0,
	"max_warning" DOUBLE PRECISION default 0,
	"str_warning" text,
	"min_critical" DOUBLE PRECISION default 0,
	"max_critical" DOUBLE PRECISION default 0,
	"str_critical" text,
	"min_ff_event" INTEGER default 0,
	"custom_string_1" text default '',
	"custom_string_2" text default '',
	"custom_string_3" text default '',
	"custom_integer_1" INTEGER default 0,
	"custom_integer_2" INTEGER default 0,
	"post_process" DOUBLE PRECISION default 0
);

CREATE TABLE "tnetwork_component_group" (
	"id_sg" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(200) NOT NULL default '',
	"parent" BIGINT NOT NULL default 0
);

CREATE TABLE "tnetwork_profile" (
	"id_np" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default '',
	"description" varchar(250) default ''
);

CREATE TABLE "tnetwork_profile_component" (
	"id_nc" BIGINT NOT NULL default 0,
	"id_np" BIGINT NOT NULL default 0
);
CREATE INDEX "tnetwork_profile_id_np_idx" ON "tnetwork_profile_component"("id_np");

CREATE TABLE "tnota" (
	"id_nota" BIGSERIAL NOT NULL PRIMARY KEY,
	"id_incident" BIGINT NOT NULL,
	"id_usuario" varchar(100) NOT NULL default '0',
	"timestamp" TIMESTAMP without time zone default CURRENT_TIMESTAMP,
	"nota" TEXT NOT NULL
);
CREATE INDEX "tnota_id_incident_idx" ON "tnota"("id_incident");

CREATE TABLE "torigen" (
	"origen" varchar(100) NOT NULL default ''
);

CREATE TABLE "tperfil" (
	"id_perfil" SERIAL NOT NULL PRIMARY KEY,
	"name" TEXT NOT NULL default '',
	"incident_edit" SMALLINT NOT NULL default 0,
	"incident_view" SMALLINT NOT NULL default 0,
	"incident_management" SMALLINT NOT NULL default 0,
	"agent_view" SMALLINT NOT NULL default 0,
	"agent_edit" SMALLINT NOT NULL default 0,
	"alert_edit" SMALLINT NOT NULL default 0,
	"user_management" SMALLINT NOT NULL default 0,
	"db_management" SMALLINT NOT NULL default 0,
	"alert_management" SMALLINT NOT NULL default 0,
	"pandora_management" SMALLINT NOT NULL default 0
);

CREATE TABLE "trecon_script" (
	"id_recon_script" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) default '',
	"description" TEXT default NULL,
	"script" varchar(250) default ''
);

CREATE TABLE "trecon_task" (
	"id_rt" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default '',
	"description" varchar(250) NOT NULL default '',
	"subnet" varchar(64) NOT NULL default '',
	"id_network_profile" INTEGER NOT NULL default 0,
	"create_incident" INTEGER NOT NULL default 0,
	"id_group" INTEGER NOT NULL default 1,
	"utimestamp" BIGINT NOT NULL default 0,
	"status" INTEGER NOT NULL default 0,
	"interval_sweep" INTEGER NOT NULL default 0,
	"id_recon_server" INTEGER NOT NULL default 0,
	"id_os" INTEGER NOT NULL default 0,
	"recon_ports" varchar(250) NOT NULL default '',
	"snmp_community" varchar(64) NOT NULL default 'public',
	"id_recon_script" INTEGER,
	"field1" varchar(250) NOT NULL default '',
	"field2" varchar(250) NOT NULL default '',
	"field3" varchar(250) NOT NULL default '',
	"field4" varchar(250) NOT NULL default '',
	"os_detect" SMALLINT NOT NULL default 1,
	"resolve_names" SMALLINT NOT NULL default 1,
	"parent_detection" SMALLINT NOT NULL default 1,
	"parent_recursion" SMALLINT NOT NULL default 1,
	"disabled" SMALLINT NOT NULL default 1
);
CREATE INDEX "trecon_task_id_recon_server_idx" ON "trecon_task"("id_recon_server");

CREATE TABLE "tserver" (
	"id_server" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default '',
	"ip_address" varchar(100) NOT NULL default '',
	"status" INTEGER NOT NULL default 0,
	"laststart" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"keepalive" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"snmp_server" INTEGER NOT NULL default 0,
	"network_server" INTEGER NOT NULL default 0,
	"data_server" INTEGER NOT NULL default 0,
	"master" INTEGER NOT NULL default 0,
	"checksum" INTEGER NOT NULL default 0,
	"description" varchar(255) default NULL,
	"recon_server" INTEGER NOT NULL default 0,
	"version" varchar(20) NOT NULL default '',
	"plugin_server" INTEGER NOT NULL default 0,
	"prediction_server" INTEGER NOT NULL default 0,
	"wmi_server" INTEGER NOT NULL default 0,
	"export_server" INTEGER NOT NULL default 0,
	"server_type" INTEGER NOT NULL default 0,
	"queued_modules" INTEGER NOT NULL default 0,
	"threads" INTEGER NOT NULL default 0,
	"lag_time" INTEGER NOT NULL default 0,
	"lag_modules" INTEGER NOT NULL default 0,
	"total_modules_running" INTEGER NOT NULL default 0,
	"my_modules" INTEGER NOT NULL default 0,
	"stat_utimestamp" BIGINT NOT NULL default 0
);
CREATE INDEX "tserver_name_idx" ON "tserver"("name");
CREATE INDEX "tserver_keepalive_idx" ON "tserver"("keepalive");
CREATE INDEX "tserver_status_idx" ON "tserver"("status");

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

CREATE TABLE "tsesion" (
	"id_sesion" BIGSERIAL NOT NULL PRIMARY KEY,
	"id_usuario" varchar(60) NOT NULL default '0',
	"ip_origen" varchar(100) NOT NULL default '',
	"accion" varchar(100) NOT NULL default '',
	"descripcion" text NOT NULL default '',
	"fecha" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"utimestamp" BIGINT NOT NULL default 0
);
CREATE INDEX "tsesion_utimestamp_idx" ON "tsesion"("utimestamp");
CREATE INDEX "tsesion_id_usuario_idx" ON "tsesion"("id_usuario");

CREATE TABLE "ttipo_modulo" (
	"id_tipo" SERIAL NOT NULL PRIMARY KEY,
	"nombre" varchar(100) NOT NULL default '',
	"categoria" INTEGER NOT NULL default 0,
	"descripcion" varchar(100) NOT NULL default '',
	"icon" varchar(100) default NULL
);

CREATE TABLE "ttrap" (
	"id_trap" BIGSERIAL NOT NULL PRIMARY KEY,
	"source" varchar(50) NOT NULL default '',
	"oid" text NOT NULL default '',
	"oid_custom" text default '',
	"type" INTEGER NOT NULL default 0,
	"type_custom" varchar(100) default '',
	"value" text default '',
	"value_custom" text default '',
	"alerted" SMALLINT NOT NULL default 0,
	"status" SMALLINT NOT NULL default 0,
	"id_usuario" varchar(150) default '',
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00',
	"priority" INTEGER NOT NULL default 2,
	"text" varchar(255) default '',
	"description" varchar(255) default '',
	"severity" INTEGER NOT NULL default 2
);

CREATE TABLE "tusuario" (
	"id_user" varchar(60) NOT NULL PRIMARY KEY,
	"fullname" varchar(255) NOT NULL,
	"firstname" varchar(255) NOT NULL,
	"lastname" varchar(255) NOT NULL,
	"middlename" varchar(255) NOT NULL default '',
	"password" varchar(45) default NULL,
	"comments" varchar(200) default NULL,
	"last_connect" BIGINT NOT NULL default 0,
	"registered" BIGINT NOT NULL default 0,
	"email" varchar(100) default NULL,
	"phone" varchar(100) default NULL,
	"is_admin" SMALLINT NOT NULL default 0,
	"language" varchar(10) default NULL,
	"timezone" varchar(50) default '',
	"block_size" INTEGER NOT NULL default 20,
	"flash_chart" INTEGER NOT NULL default 1,
	"id_skin" INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE "tusuario_perfil" (
	"id_up" BIGSERIAL NOT NULL PRIMARY KEY,
	"id_usuario" varchar(100) NOT NULL default '',
	"id_perfil" INTEGER NOT NULL default 0,
	"id_grupo" INTEGER NOT NULL default 0,
	"assigned_by" varchar(100) NOT NULL default '',
	"id_policy" INTEGER DEFAULT 0 NOT NULL
);

CREATE TABLE "tnews" (
	"id_news" SERIAL NOT NULL PRIMARY KEY,
	"author" varchar(255)  NOT NULL DEFAULT '',
	"subject" varchar(255)  NOT NULL DEFAULT '',
	"text" TEXT NOT NULL,
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00'
);

CREATE TABLE "tgraph" (
	"id_graph" SERIAL NOT NULL PRIMARY KEY,
	"id_user" varchar(100) NOT NULL default '',
	"name" varchar(150) NOT NULL default '',
	"description" TEXT NOT NULL,
	"period" INTEGER NOT NULL default 0,
	"width" INTEGER NOT NULL default 0,
	"height" INTEGER NOT NULL default 0,
	"private" SMALLINT NOT NULL default 0,
	"events" SMALLINT NOT NULL default 0,
	"stacked" SMALLINT NOT NULL default 0,
	"id_group" BIGINT NOT NULL default 0
);

CREATE TABLE "tgraph_source" (
	"id_gs" SERIAL NOT NULL PRIMARY KEY,
	"id_graph" BIGINT NOT NULL default 0,
	"id_agent_module"  BIGINT NOT NULL default 0,
	"weight" DOUBLE PRECISION default 0
);

CREATE TABLE "treport" (
	"id_report" SERIAL NOT NULL PRIMARY KEY,
	"id_user" varchar(100) NOT NULL default '',
	"name" varchar(150) NOT NULL default '',
	"description" TEXT NOT NULL,
	"private" SMALLINT NOT NULL default 0,
	"id_group" BIGINT NOT NULL default 0,
	"custom_logo" varchar(200)  default NULL,
	"header" TEXT  default NULL,
	"first_page" TEXT default NULL,
	"footer" TEXT default NULL,
	"custom_font" varchar(200) default NULL
);

-- -----------------------------------------------------
-- Table "treport_content"
-- -----------------------------------------------------
CREATE TABLE "treport_content" (
	"id_rc" SERIAL NOT NULL PRIMARY KEY,
	"id_report" INTEGER NOT NULL default 0 REFERENCES treport("id_report") ON UPDATE CASCADE ON DELETE CASCADE,
	"id_gs"  INTEGER default NULL,
	"id_agent_module" BIGINT default NULL,
	"type" varchar(30) default 'simple_graph',
	"period" BIGINT NOT NULL default 0,
	"order" BIGINT NOT NULL default 0,
	"description" TEXT, 
	"id_agent" BIGINT NOT NULL default 0,
	"text" TEXT default NULL,
	"external_source" TEXT default NULL,
	"treport_custom_sql_id" INTEGER default 0,
	"header_definition" TEXT default NULL,
	"column_separator" TEXT default NULL,
	"line_separator" TEXT default NULL,
	"time_from" TIME without time zone default '00:00:00',
	"time_to" TIME without time zone default '00:00:00',
	"monday" SMALLINT NOT NULL default 1,
	"tuesday" SMALLINT NOT NULL default 1,
	"wednesday" SMALLINT NOT NULL default 1,
	"thursday" SMALLINT NOT NULL default 1,
	"friday" SMALLINT NOT NULL default 1,
	"saturday" SMALLINT NOT NULL default 1,
	"sunday" SMALLINT NOT NULL default 1,
	"only_display_wrong" SMALLINT NOT NULL default 0,
	"top_n" INTEGER NOT NULL default 0,
	"top_n_value" INTEGER NOT NULL default 10,
	"exception_condition" INTEGER NOT NULL default 0,
	"exception_condition_value" DOUBLE PRECISION NOT NULL default 0,
	"show_resume" INTEGER NOT NULL default 0,
	"order_uptodown" INTEGER NOT NULL default 0,
	"show_graph" INTEGER NOT NULL default 0,
	"group_by_agent" INTEGER NOT NULL default 0,
	"style" TEXT NOT NULL DEFAULT '',
	"id_group" INTEGER NOT NULL default 0,
	"id_module_group" INTEGER NOT NULL default 0,
	"server_name" TEXT DEFAULT ''
);

CREATE TABLE "treport_content_sla_combined" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_report_content" INTEGER NOT NULL  REFERENCES treport_content("id_rc") ON UPDATE CASCADE ON DELETE CASCADE,
	"id_agent_module" INTEGER NOT NULL,
	"sla_max" DOUBLE PRECISION NOT NULL default 0,
	"sla_min" DOUBLE PRECISION NOT NULL default 0,
	"sla_limit" DOUBLE PRECISION NOT NULL default 0,
	"server_name" TEXT DEFAULT ''
);

CREATE TABLE "treport_content_item" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_report_content" INTEGER NOT NULL,
	"id_agent_module" INTEGER NOT NULL,
	"server_name" TEXT DEFAULT ''
);

CREATE TABLE "treport_custom_sql" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(150) NOT NULL default '',
	"sql" TEXT default NULL
);

CREATE TABLE "tlayout" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(50)  NOT NULL,
	"id_group" INTEGER NOT NULL,
	"background" varchar(200)  NOT NULL,
	"fullscreen" SMALLINT NOT NULL default 0,
	"height" INTEGER NOT NULL default 0,
	"width" INTEGER NOT NULL default 0
);

CREATE TABLE "tlayout_data" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_layout" INTEGER NOT NULL default 0,
	"pos_x" INTEGER NOT NULL default 0,
	"pos_y" INTEGER NOT NULL default 0,
	"height" INTEGER NOT NULL default 0,
	"width" INTEGER NOT NULL default 0,
	"label" varchar(200) DEFAULT '',
	"image" varchar(200) DEFAULT '',
	"type" SMALLINT NOT NULL default 0,
	"period" INTEGER NOT NULL default 3600,
	"id_agente_modulo" BIGINT NOT NULL default 0,
	"id_agent" INTEGER NOT NULL default 0,
	"id_layout_linked" INTEGER NOT NULL default 0,
	"parent_item" INTEGER NOT NULL default 0,
	"label_color" varchar(20) DEFAULT '',
	"no_link_color" SMALLINT NOT NULL default 0
);

CREATE TABLE "tplugin" (
  	"id" SERIAL NOT NULL PRIMARY KEY,
  	"name" varchar(200) NOT NULL,
  	"description" TEXT,
  	"max_timeout" INTEGER NOT NULL default 0,
  	"execute" varchar(250) NOT NULL,
  	"net_dst_opt" varchar(50) default '',
  	"net_port_opt" varchar(50) default '',
  	"user_opt" varchar(50) default '',
  	"pass_opt" varchar(50) default '',
  	"plugin_type" SMALLINT NOT NULL default 0
); 

CREATE TABLE "tmodule" (
	"id_module" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default ''
);

CREATE TYPE type_tserver_export_connect_mode AS ENUM ('tentacle', 'ssh', 'local');
CREATE TABLE "tserver_export" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(100) NOT NULL default '',
	"preffix" varchar(100) NOT NULL default '',
	"interval" INTEGER NOT NULL default 300,
	"ip_server" varchar(100) NOT NULL default '',
	"connect_mode" type_tserver_export_connect_mode default 'local',
	"id_export_server" INTEGER default NULL ,
	"user" varchar(100) NOT NULL default '',
	"pass" varchar(100) NOT NULL default '',
	"port" INTEGER NOT NULL default 0,
	"directory" varchar(100) NOT NULL default '',
	"options" varchar(100) NOT NULL default '',
	--Number of hours of diference with the server timezone
	"timezone_offset" SMALLINT NOT NULL default 0
);

-- id_export_server is real pandora fms export server process that manages this server
-- id is the "destination" server to export
CREATE TABLE "tserver_export_data" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_export_server" INTEGER NOT NULL default 0,
	"agent_name" varchar(100) NOT NULL default '',
	"module_name" varchar(100) NOT NULL default '',
	"module_type" varchar(100) NOT NULL default '',
	"data" varchar(255) default NULL, 
	"timestamp" TIMESTAMP without time zone default '1970-01-01 00:00:00'
);

CREATE TABLE "tplanned_downtime" (
	"id" BIGSERIAL NOT NULL PRIMARY KEY,
	"name" VARCHAR( 100 ) NOT NULL,
	"description" TEXT NOT NULL,
	"date_from" BIGINT NOT NULL default 0,
	"date_to" BIGINT NOT NULL default 0,
	"executed" SMALLINT NOT NULL default 0,
	"id_group" BIGINT NOT NULL default 0,
	"only_alerts" SMALLINT NOT NULL default 0
);

CREATE TABLE "tplanned_downtime_agents" (
	"id" BIGSERIAL NOT NULL PRIMARY KEY,
	"id_agent" BIGINT NOT NULL default 0,
	"id_downtime" BIGINT NOT NULL default 0
);

-- GIS extension Tables

-- -----------------------------------------------------
-- Table "tgis_data_history"
-- -----------------------------------------------------
--Table to store historical GIS information of the agents
CREATE TABLE "tgis_data_history" (
	--key of the table
	"id_tgis_data" SERIAL NOT NULL PRIMARY KEY,
	"longitude" DOUBLE PRECISION NOT NULL,
	"latitude" DOUBLE PRECISION NOT NULL,
	"altitude" DOUBLE PRECISION NOT NULL,
	--timestamp on wich the agente started to be in this position
	"start_timestamp"  TIMESTAMP without time zone DEFAULT CURRENT_TIMESTAMP,
	--timestamp on wich the agent was placed for last time on this position
	"end_timestamp"  TIMESTAMP without time zone default '1970-01-01 00:00:00',
	--description of the region correoponding to this placemnt
	"description" TEXT DEFAULT NULL,
	-- 0 to show that the position cames from the agent, 1 to show that the position was established manualy
	"manual_placement" SMALLINT NOT NULL default 0,
	-- Number of data packages received with this position from the start_timestampa to the_end_timestamp
	"number_of_packages" INTEGER NOT NULL default 1,
	--reference to the agent
	"tagente_id_agente" INTEGER NOT NULL 
);
CREATE INDEX "tgis_data_history_start_timestamp_idx" ON "tgis_data_history"("start_timestamp");
CREATE INDEX "tgis_data_history_end_timestamp_idx" ON "tgis_data_history"("end_timestamp");

-- -----------------------------------------------------
-- Table "tgis_data_status"
-- -----------------------------------------------------
--Table to store last GIS information of the agents
CREATE TABLE "tgis_data_status" (
	--Reference to the agent
	"tagente_id_agente" INTEGER NOT NULL REFERENCES "tagente"("id_agente") ON DELETE CASCADE ON UPDATE NO ACTION,
	--Last received longitude
	"current_longitude" DOUBLE PRECISION NOT NULL,
	--Last received latitude 
	"current_latitude" DOUBLE PRECISION NOT NULL,
	--Last received altitude 
	"current_altitude" DOUBLE PRECISION NOT NULL,
	--Reference longitude to see if the agent has moved
	"stored_longitude" DOUBLE PRECISION NOT NULL,
	--Reference latitude to see if the agent has moved
	"stored_latitude" DOUBLE PRECISION NOT NULL,
	--Reference altitude to see if the agent has moved
	"stored_altitude" DOUBLE PRECISION DEFAULT NULL,
	--Number of data packages received with this position since start_timestampa
	"number_of_packages" INTEGER NOT NULL default 1, 
	--Timestamp on wich the agente started to be in this position
	"start_timestamp" TIMESTAMP without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	--0 to show that the position cames from the agent, 1 to show that the position was established manualy
	"manual_placement" SMALLINT NOT NULL default 0, 
	--description of the region correoponding to this placemnt
	"description" TEXT NULL,
  PRIMARY KEY("tagente_id_agente")
);
CREATE INDEX "tgis_data_status_start_timestamp_idx" ON "tgis_data_status"("start_timestamp");
CREATE INDEX "tgis_data_status_tagente_id_agente_idx" ON "tgis_data_status"("tagente_id_agente");

-- -----------------------------------------------------
-- Table "tgis_map"
-- -----------------------------------------------------
--Table containing information about a gis map
CREATE TABLE "tgis_map" (
    --table identifier
	"id_tgis_map" SERIAL NOT NULL PRIMARY KEY,
	--Name of the map
	"map_name" VARCHAR(63) NOT NULL,
	--longitude of the center of the map when it\'s loaded
	"initial_longitude" DOUBLE PRECISION DEFAULT NULL,
	--latitude of the center of the map when it\'s loaded
	"initial_latitude" DOUBLE PRECISION DEFAULT NULL,
	--altitude of the center of the map when it\'s loaded
	"initial_altitude" DOUBLE PRECISION DEFAULT NULL,
	--Zoom level to show when the map is loaded.
	"zoom_level"  SMALLINT NOT NULL default 1,
	--path on the server to the background image of the map
	"map_background" VARCHAR(127) DEFAULT NULL,
	--default longitude for the agents placed on the map
	"default_longitude" DOUBLE PRECISION DEFAULT NULL,
	--default latitude for the agents placed on the map
	"default_latitude" DOUBLE PRECISION DEFAULT NULL,
	--default altitude for the agents placed on the map
	"default_altitude" DOUBLE PRECISION DEFAULT NULL,
	--Group that owns the map
	"group_id" INTEGER NOT NULL default 0,
	--1 if this is the default map, 0 in other case
	"default_map" SMALLINT NOT NULL default 0
);
CREATE INDEX "tgis_map_tagente_map_name_idx" ON "tgis_map"("map_name");

-- -----------------------------------------------------
-- Table "tgis_map_connection"
-- -----------------------------------------------------
--Table to store the map connection information
CREATE TABLE "tgis_map_connection" (
	--table id
	"id_tmap_connection" SERIAL NOT NULL PRIMARY KEY,
	--Name of the connection (name of the base layer)
	"conection_name" VARCHAR(45) DEFAULT NULL,
	--Type of map server to connect
	"connection_type" VARCHAR(45) DEFAULT NULL, 
	--connection information (this can probably change to fit better the possible connection parameters)
	"conection_data" TEXT DEFAULT NULL, 
	--Number of zoom levels available
	"num_zoom_levels" SMALLINT DEFAULT NULL, 
	--Default Zoom Level for the connection
	"default_zoom_level" SMALLINT NOT NULL default 16,
	--default longitude for the agents placed on the map
	"default_longitude" DOUBLE PRECISION DEFAULT NULL,
	--default latitude for the agents placed on the map
	"default_latitude" DOUBLE PRECISION DEFAULT NULL,
	--default altitude for the agents placed on the map
	"default_altitude" DOUBLE PRECISION DEFAULT NULL,
	--longitude of the center of the map when it\'s loaded
	"initial_longitude" DOUBLE PRECISION DEFAULT NULL,
	--latitude of the center of the map when it\'s loaded
	"initial_latitude" DOUBLE PRECISION DEFAULT NULL, 
	--altitude of the center of the map when it\'s loaded
	"initial_altitude" DOUBLE PRECISION DEFAULT NULL, 
	--Group that owns the map
	"group_id" INTEGER NOT NULL default 0 
);

-- -----------------------------------------------------
-- Table "tgis_map_has_tgis_map_connection"
-- -----------------------------------------------------
--Table to asociate a connection to a gis map
CREATE TABLE "tgis_map_has_tgis_map_connection" (
	--reference to tgis_map
	"tgis_map_id_tgis_map" INTEGER NOT NULL REFERENCES "tgis_map"("id_tgis_map") ON DELETE CASCADE ON UPDATE NO ACTION,
	--reference to tgis_map_connection
	"tgis_map_connection_id_tmap_connection" INTEGER NOT NULL REFERENCES "tgis_map_connection" ("id_tmap_connection") ON DELETE CASCADE ON UPDATE NO ACTION, 
	--Last Modification Time of the Connection
	"modification_time" TIMESTAMP without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	--Flag to mark the default map connection of a map
	"default_map_connection" SMALLINT NOT NULL default 0,
  PRIMARY KEY ("tgis_map_id_tgis_map", "tgis_map_connection_id_tmap_connection")
);
CREATE INDEX "tgis_map_has_tgis_map_connection_map_tgis_map_id_tgis_map_idx" ON "tgis_map_has_tgis_map_connection"("tgis_map_id_tgis_map");
CREATE INDEX "tgis_map_has_tgis_map_connection_map_tgis_map_connection_id_tmap_connection_idx" ON "tgis_map_has_tgis_map_connection"("tgis_map_connection_id_tmap_connection");
--This function is for to tranlate "ON UPDATE CURRENT_TIMESTAMP" of MySQL.
	--It is in only one line because the parser of Pandora installer execute the code at the end with ;
CREATE OR REPLACE FUNCTION update_tgis_map_has_tgis_map_connection_modification_time() RETURNS TRIGGER AS $$ BEGIN NEW.modification_time = now(); RETURN NEW; END; $$ language 'plpgsql';
CREATE TRIGGER trigger_tgis_map_has_tgis_map_connection_modification_time BEFORE UPDATE ON tgis_map_has_tgis_map_connection FOR EACH ROW EXECUTE PROCEDURE update_tgis_map_has_tgis_map_connection_modification_time();

-- -----------------------------------------------------
-- Table "tgis_map_layer"
-- -----------------------------------------------------
--Table containing information about the map layers
CREATE TABLE "tgis_map_layer" (
	--table id
	"id_tmap_layer" SERIAL NOT NULL PRIMARY KEY,
	--Name of the layer
	"layer_name" VARCHAR(45) NOT NULL, 
	--True if the layer must be shown
	"view_layer" SMALLINT NOT NULL default 1, 
	--Number of order of the layer in the layer stack, bigger means upper on the stack.\n
	"layer_stack_order" SMALLINT NOT NULL default 0, 
	--reference to the map containing the layer
	"tgis_map_id_tgis_map" INTEGER NOT NULL default 0 REFERENCES "tgis_map"("id_tgis_map") ON DELETE CASCADE ON UPDATE NO ACTION, 
	--reference to the group shown in the layer
	"tgrupo_id_grupo" BIGINT NOT NULL 
);
CREATE INDEX "tgis_map_layer_id_tmap_layer_idx" ON "tgis_map_layer"("id_tmap_layer");


-- -----------------------------------------------------
-- Table "tgis_map_layer_has_tagente"
-- -----------------------------------------------------
--Table to define wich agents are shown in a layer
CREATE TABLE "tgis_map_layer_has_tagente" (
	"tgis_map_layer_id_tmap_layer" INTEGER NOT NULL REFERENCES "tgis_map_layer"("id_tmap_layer") ON DELETE CASCADE ON UPDATE NO ACTION,
	"tagente_id_agente" INTEGER NOT NULL REFERENCES "tagente"("id_agente") ON DELETE CASCADE ON UPDATE NO ACTION,
  PRIMARY KEY ("tgis_map_layer_id_tmap_layer", "tagente_id_agente")
);
CREATE INDEX "tgis_map_layer_has_tagente_tgis_map_layer_id_tmap_layer_idx" ON "tgis_map_layer_has_tagente"("tgis_map_layer_id_tmap_layer");
CREATE INDEX "tgis_map_layer_has_tagente_tagente_id_agente_idx" ON "tgis_map_layer_has_tagente"("tagente_id_agente");

-- -----------------------------------------------------
-- Table "tgroup_stat"
-- -----------------------------------------------------
--Table to store global system stats per group
CREATE TABLE "tgroup_stat" (
	"id_group" INTEGER NOT NULL default 0 PRIMARY KEY,
	"modules" INTEGER NOT NULL default 0,
	"normal" INTEGER NOT NULL default 0,
	"critical" INTEGER NOT NULL default 0,
	"warning" INTEGER NOT NULL default 0,
	"unknown" INTEGER NOT NULL default 0,
	"non-init" INTEGER NOT NULL default 0,
	"alerts" INTEGER NOT NULL default 0,
	"alerts_fired" INTEGER NOT NULL default 0,
	"agents" INTEGER NOT NULL default 0,
	"agents_unknown" INTEGER NOT NULL default 0,
	"utimestamp" INTEGER NOT NULL default 0
);

-- -----------------------------------------------------
-- Table "tnetwork_map"
-- -----------------------------------------------------
CREATE TABLE "tnetwork_map" (
	"id_networkmap" SERIAL NOT NULL PRIMARY KEY,
	"id_user" VARCHAR(60)  NOT NULL,
	"name" VARCHAR(100)  NOT NULL,
	"type" VARCHAR(20)  NOT NULL,
	"layout" VARCHAR(20)  NOT NULL,
	"nooverlap" SMALLINT NOT NULL default 0,
	"simple" SMALLINT NOT NULL default 0,
	"regenerate" SMALLINT NOT NULL default 1,
	"font_size" INTEGER NOT NULL default 12,
	"id_group" INTEGER NOT NULL default 0,
	"id_module_group" INTEGER NOT NULL default 0,  
	"id_policy" INTEGER NOT NULL default 0,
	"depth" VARCHAR(20) NOT NULL,
	"only_modules_with_alerts" SMALLINT NOT NULL default 0,
	"hide_policy_modules" SMALLINT NOT NULL default 0,
	"zoom" DOUBLE PRECISION default 1,
	"distance_nodes" DOUBLE PRECISION default 2.5,
	"center" INTEGER NOT NULL default 0,
	"contracted_nodes" TEXT,
	"show_snmp_modules" SMALLINT NOT NULL default 0
);

-- -----------------------------------------------------
-- Table "tsnmp_filter"
-- -----------------------------------------------------
CREATE TABLE "tsnmp_filter" (
	"id_snmp_filter" SERIAL NOT NULL PRIMARY KEY,
	"description" varchar(255) default '',
	"filter" varchar(255) default ''
);

-- -----------------------------------------------------
-- Table "tagent_custom_fields"
-- -----------------------------------------------------
CREATE TABLE "tagent_custom_fields" (
	"id_field" SERIAL NOT NULL PRIMARY KEY,
	"name" varchar(45) NOT NULL default '',
	"display_on_front" SMALLINT NOT NULL default 0
);

-- -----------------------------------------------------
-- Table "tagent_custom_data"
-- -----------------------------------------------------
CREATE TABLE "tagent_custom_data" (
	"id_field" INTEGER NOT NULL REFERENCES tagent_custom_fields("id_field") ON UPDATE CASCADE ON DELETE CASCADE,
	"id_agent" INTEGER NOT NULL REFERENCES tagente("id_agente") ON UPDATE CASCADE ON DELETE CASCADE,
	"description" text default '',
  PRIMARY KEY  ("id_field", "id_agent")
);

-- -----------------------------------------------------
-- Table "ttag"
-- -----------------------------------------------------

CREATE TABLE "ttag" ( 
 "id_tag" SERIAL NOT NULL PRIMARY KEY, 
 "name" VARCHAR(100) NOT NULL default '', 
 "description" text NOT NULL default '', 
 "url" text NOT NULL default ''
); 

-- -----------------------------------------------------
-- Table "ttag_module"
-- -----------------------------------------------------

CREATE TABLE "ttag_module" ( 
 "id_tag" INTEGER NOT NULL, 
 "id_agente_modulo" INTEGER NOT NULL DEFAULT 0, 
   PRIMARY KEY  (id_tag, id_agente_modulo)
); 

CREATE INDEX "ttag_module_id_ag_modulo_idx" ON "ttag_module"("id_agente_modulo");

-- -----------------------------------------------------
-- Table "ttag_policy_module"
-- -----------------------------------------------------

CREATE TABLE "ttag_policy_module" ( 
 "id_tag" INTEGER NOT NULL, 
 "id_policy_module" INTEGER NOT NULL DEFAULT 0, 
   PRIMARY KEY  (id_tag, id_policy_module)
); 

CREATE INDEX "ttag_poli_mod_id_pol_mo_idx" ON "ttag_policy_module"("id_policy_module");

-- -----------------------------------------------------
-- Table "ttag_event"
-- -----------------------------------------------------

CREATE TABLE "ttag_event" ( 
 id_tag INTEGER NOT NULL, 
 id_evento BIGINT NOT NULL DEFAULT 0, 
   PRIMARY KEY  (id_tag, id_evento)
); 

CREATE INDEX "ttag_event_id_evento_idx" ON "ttag_event"("id_evento");
