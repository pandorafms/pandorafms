-- Pandora FMS - the Flexible Monitoring System
-- ============================================
-- Copyright (c) 2005-2009 Artica Soluciones Tecnológicas, http://www.artica.es
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
-- Pandora FMS official tables for 2.0 version              --
-- -----------------------------------------------------------

CREATE TABLE IF NOT EXISTS `taddress` (
  `id_a` int(10) unsigned NOT NULL auto_increment,
  `ip` varchar(60) NOT NULL default '',
  `ip_pack` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_a`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `taddress_agent` (
  `id_ag` bigint(20) unsigned NOT NULL auto_increment,
  `id_a` bigint(20) unsigned NOT NULL default '0',
  `id_agent` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_ag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tagente` (
  `id_agente` int(10) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `direccion` varchar(100) default NULL,
  `comentarios` varchar(255) default '',
  `id_grupo` int(10) unsigned NOT NULL default '0',
  `ultimo_contacto` datetime NOT NULL default '0000-00-00 00:00:00',
  `modo` tinyint(1) NOT NULL default '0',
  `intervalo` int(11) unsigned NOT NULL default '300',
  `id_os` int(10) unsigned default '0',
  `os_version` varchar(100) default '',
  `agent_version` varchar(100) default '',
  `ultimo_contacto_remoto` datetime default '0000-00-00 00:00:00',
  `disabled` tinyint(2) NOT NULL default '0',
  `id_network_server` smallint(4) unsigned default '0',
  `id_plugin_server` smallint(4) unsigned default '0',
  `id_prediction_server` smallint(4) unsigned default '0',
  `id_wmi_server` smallint(4) unsigned default '0',
  `id_parent` int(10) unsigned default '0',
  `custom_id` varchar(255) default '',
  PRIMARY KEY  (`id_agente`),
  KEY `nombre` (`nombre`),
  KEY `direccion` (`direccion`),
  KEY `disabled` (`disabled`),
  KEY `id_grupo` (`id_grupo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `tagente_datos` (
  `id_agente_datos` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(10) unsigned NOT NULL default '0',
  `datos` double(18,2) default NULL,
  `utimestamp` bigint(20) default '0',
  PRIMARY KEY  (`id_agente_datos`),
  KEY `data_index1` (`id_agente_modulo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `tagente_datos_inc` (
  `id_adi` int(10) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(10) unsigned NOT NULL default '0',
  `datos` double(18,2) default NULL,
  `utimestamp` int(20) unsigned default '0',
  PRIMARY KEY  (`id_adi`),
  KEY `data_inc_index_1` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tagente_datos_string` (
  `id_tagente_datos_string` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(10) unsigned NOT NULL default '0',
  `datos` text NOT NULL,
  `utimestamp` int(20) unsigned NOT NULL default 0,
  PRIMARY KEY  (`id_tagente_datos_string`),
  KEY `data_string_index_1` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- delete "cambio" not used anymore
CREATE TABLE `tagente_estado` (
  `id_agente_estado` int(10) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(10) NOT NULL default '0',
  `datos` varchar(255) NOT NULL default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `estado` int(4) NOT NULL default '0',
  `id_agente` int(10) NOT NULL default '0',
  `last_try` datetime default NULL,
  `utimestamp` bigint(20) NOT NULL default '0',
  `current_interval` int(8) unsigned NOT NULL default '0',
  `running_by` smallint(4) unsigned default '0',
  `last_execution_try` bigint(20) NOT NULL default '0',
  `status_changes` tinyint(4) default 0,
  `last_status` tinyint(4) default 0,
  PRIMARY KEY  (`id_agente_estado`),
  KEY `status_index_1` (`id_agente_modulo`),
  KEY `status_index_2` (`id_agente_modulo`,`estado`),
  KEY `current_interval` (`current_interval`),
  KEY `running_by` (`running_by`),
  KEY `last_execution_try` (`last_execution_try`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- Probably last_execution_try index is not useful and loads more than benefits

-- id_modulo now uses tmodule 
-- ---------------------------
-- 1 - Data server modules (agent related modules)
-- 2 - Network server modules
-- 4 - Plugin server
-- 5 - Predictive server
-- 6 - WMI server


CREATE TABLE IF NOT EXISTS `tagente_modulo` (
  `id_agente_modulo` int(10) unsigned NOT NULL auto_increment,
  `id_agente` int(10) unsigned NOT NULL default '0',
  `id_tipo_modulo` smallint(5) NOT NULL default '0',
  `descripcion` varchar(100) NOT NULL default '',
  `nombre` varchar(100) NOT NULL default '',
  `max` bigint(20) default '0',
  `min` bigint(20) default '0',
  `module_interval` int(4) unsigned default '0',
  `tcp_port` int(4) unsigned default '0',
  `tcp_send` varchar(255) default '',
  `tcp_rcv` varchar(255) default '',
  `snmp_community` varchar(100) default '',
  `snmp_oid` varchar(255) default '0',
  `ip_target` varchar(100) default '',
  `id_module_group` int(4) unsigned default '0',
  `flag` tinyint(1) unsigned default '1',
  `id_modulo` int(10) unsigned default '0',
  `disabled` tinyint(1) unsigned default '0',
  `id_export` smallint(4) unsigned default '0',
  `plugin_user` varchar(250) default '',
  `plugin_pass` varchar(250) default '',
  `plugin_parameter` text,
  `id_plugin` int(10) default '0',
  `post_process` double(18,13) default NULL,
  `prediction_module` bigint(14) default '0',
  `max_timeout` int(4) unsigned default '0',
  `custom_id` varchar(255) default '',
  `history_data` tinyint(1) unsigned default '1',
  `min_warning` double(18,2) default 0,
  `max_warning` double(18,2) default 0,
  `min_critical` double(18,2) default 0,
  `max_critical` double(18,2) default 0,
  `min_ff_event` int(4) unsigned default '0',
  `delete_pending` int(1) unsigned default 0,
  PRIMARY KEY  (`id_agente_modulo`),
  KEY `main_idx` (`id_agente_modulo`,`id_agente`),
  KEY `tam_agente` (`id_agente`),
  KEY `id_tipo_modulo` (`id_tipo_modulo`),
  KEY `disabled` (`disabled`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- snmp_oid is also used for WMI query

CREATE TABLE IF NOT EXISTS `tagent_access` (
  `id_ac` bigint(20) unsigned NOT NULL auto_increment,
  `id_agent` int(10) unsigned NOT NULL default '0',
  `utimestamp` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id_ac`),
  KEY `agent_index` (`id_agent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  IF NOT EXISTS  `talert_snmp` (
  `id_as` int(10) unsigned NOT NULL auto_increment,
  `id_alert` int(10) unsigned NOT NULL default '0',
  `al_field1` varchar(100) NOT NULL default '',
  `al_field2` varchar(255) NOT NULL default '',
  `al_field3` varchar(255) NOT NULL default '',
  `description` varchar(255) default '',
  `alert_type` int(2) unsigned NOT NULL default '0',
  `agent` varchar(100) default '',
  `custom_oid` varchar(200) default '',
  `oid` varchar(255) NOT NULL default '',
  `time_threshold` int(11) NOT NULL default '0',
  `times_fired` int(2) unsigned NOT NULL default '0',
  `last_fired` datetime NOT NULL default '0000-00-00 00:00:00',
  `max_alerts` int(11) NOT NULL default '1',
  `min_alerts` int(11) NOT NULL default '1',
  `internal_counter` int(2) unsigned NOT NULL default '0',
  `priority` tinyint(4) default '0',
  PRIMARY KEY  (`id_as`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  IF NOT EXISTS `talert_commands` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `command` varchar(500) default '',
  `description` varchar(255) default '',
  `internal` tinyint(1) default 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  IF NOT EXISTS `talert_actions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) default '',
  `id_alert_command` int(10) unsigned NOT NULL,
  `field1` varchar(255) NOT NULL default '',
  `field2` varchar(255) default '',
  `field3` varchar(255) default '',
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_alert_command`) REFERENCES talert_commands(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `talert_templates` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) default '',
  `description` mediumtext default '',
  `id_alert_action` int(10) unsigned NULL,
  `field1` varchar(255) default '',
  `field2` varchar(255) default '',
  `field3` mediumtext NOT NULL,
  `type` ENUM ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal'),
  `value` varchar(255) default '',
  `max_value` double(18,2) default NULL,
  `min_value` double(18,2) default NULL,
  `time_threshold` int(10) NOT NULL default '0',
  `max_alerts` int(4) unsigned NOT NULL default '1',
  `module_type` int(10) unsigned NOT NULL default '0',
  `min_alerts` int(4) unsigned NOT NULL default '0',
  `alert_text` varchar(255) default '',
  `time_from` time default '00:00:00',
  `time_to` time default '00:00:00',
  `monday` tinyint(1) default '1',
  `tuesday` tinyint(1) default '1',
  `wednesday` tinyint(1) default '1',
  `thursday` tinyint(1) default '1',
  `friday` tinyint(1) default '1',
  `saturday` tinyint(1) default '1',
  `sunday` tinyint(1) default '1',
  `recovery_notify` tinyint(1) default '0',
  `field2_recovery` varchar(255) NOT NULL default '',
  `field3_recovery` mediumtext NOT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `talert_template_modules` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_agent_module` int(10) unsigned NOT NULL,
  `id_alert_template` int(10) unsigned NOT NULL,
  `internal_counter` int(4) default '0',
  `last_fired` bigint(20) NOT NULL default '0',
  `times_fired` int(3) NOT NULL default '0',
  `disabled` tinyint(1) default '0',
  `priority` tinyint(4) default '0',
  `force_execution` tinyint(1) default '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_agent_module`) REFERENCES tagente_modulo(`id_agente_modulo`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_alert_template`) REFERENCES talert_templates(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE (`id_agent_module`, `id_alert_template`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `talert_template_module_actions` (
  `id_alert_template_module` int(10) unsigned NOT NULL,
  `id_alert_action` int(10) unsigned NOT NULL,
  `fires_min` int(3) unsigned default 0,
  `fires_max` int(3) unsigned default 0,
  FOREIGN KEY (`id_alert_template_module`) REFERENCES talert_template_modules(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  IF NOT EXISTS `tcompound_alert` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_aam` int(10) unsigned NOT NULL default '0',
  `operation` enum('NOP', 'AND','OR','XOR','NAND','NOR','NXOR'),
  PRIMARY KEY  (`id`, `id_aam`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `talerta_agente_modulo` (
  `id_aam` int(10) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(10) unsigned NOT NULL default '0',
  `id_alerta` int(10) unsigned NOT NULL default '0',
  `al_campo1` varchar(255) default '',
  `al_campo2` varchar(255) default '',
  `al_campo3` mediumtext NOT NULL,
  `descripcion` varchar(255) default '',
  `dis_max` double(18,2) default NULL,
  `dis_min` double(18,2) default NULL,
  `time_threshold` int(10) NOT NULL default '0',
  `last_fired` datetime NOT NULL default '0000-00-00 00:00:00',
  `max_alerts` int(4) unsigned NOT NULL default '1',
  `times_fired` int(3) NOT NULL default '0',
  `module_type` int(10) unsigned NOT NULL default '0',
  `min_alerts` int(4) unsigned NOT NULL default '0',
  `internal_counter` int(4) default '0',
  `alert_text` varchar(255) default '',
  `disable` tinyint(3) default '0',
  `time_from` time default '00:00:00',
  `time_to` time default '00:00:00',
  `id_agent` int(10) default NULL,
  `monday` tinyint(1) default '1',
  `tuesday` tinyint(1) default '1',
  `wednesday` tinyint(1) default '1',
  `thursday` tinyint(1) default '1',
  `friday` tinyint(1) default '1',
  `saturday` tinyint(1) default '1',
  `sunday` tinyint(1) default '1',
  `recovery_notify` tinyint(1) default '0',
  `priority` tinyint(4) default '0',
  `al_f2_recovery` varchar(255) NOT NULL default '',
  `al_f3_recovery` mediumtext NOT NULL,
  `flag` tinyint(1) unsigned default '0',
  PRIMARY KEY  (`id_aam`),
  KEY `id_agente_modulo` (`id_agente_modulo`),
  KEY `disable` (`disable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- Priority : 0 - Maintance (grey)
-- Priority : 1 - Low (green)
-- Priority : 2 - Normal (blue)
-- Priority : 3 - Warning (yellow)
-- Priority : 4 - Critical (red)

CREATE TABLE IF NOT EXISTS `tattachment` (
  `id_attachment` int(10) unsigned NOT NULL auto_increment,
  `id_incidencia` int(10) unsigned NOT NULL default '0',
  `id_usuario` varchar(60) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `description` varchar(150) default '',
  `size` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_attachment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tconfig` (
  `id_config` int(10) unsigned NOT NULL auto_increment,
  `token` varchar(100) NOT NULL default '',
  `value` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS  `tconfig_os` (
  `id_os` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) default '',
  `icon_name` varchar(100) default '',
  PRIMARY KEY  (`id_os`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tevento` (
  `id_evento` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente` int(10) NOT NULL default '0',
  `id_usuario` varchar(100) NOT NULL default '0',
  `id_grupo` mediumint(4) NOT NULL default '0',
  `estado` tinyint(3) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `evento` varchar(255) NOT NULL default '',
  `utimestamp` bigint(20) NOT NULL default '0',
  `event_type` enum('unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal') default 'unknown',
  `id_agentmodule` int(10) NOT NULL default '0',
  `id_alert_am` int(10) NOT NULL default '0',
  `criticity` int(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_evento`),
  KEY `indice_1` (`id_agente`,`id_evento`),
  KEY `indice_2` (`utimestamp`,`id_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Criticity: 0 - Maintance (grey)
-- Criticity: 1 - Informational (blue)
-- Criticity: 2 - Normal (green) (status 0)
-- Criticity: 3 - Warning (yellow) (status 2)
-- Criticity: 4 - Critical (red) (status 1)

CREATE TABLE IF NOT EXISTS `tgrupo` (
  `id_grupo` mediumint(4) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `icon` varchar(50) default NULL,
  `parent` mediumint(4) unsigned NOT NULL default '0',
  `disabled` tinyint(3) unsigned NOT NULL default '0',
  `custom_id` varchar(255) default '',
  PRIMARY KEY  (`id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tincidencia` (
  `id_incidencia` bigint(6) unsigned zerofill NOT NULL auto_increment,
  `inicio` datetime NOT NULL default '0000-00-00 00:00:00',
  `cierre` datetime NOT NULL default '0000-00-00 00:00:00',
  `titulo` varchar(100) NOT NULL default '',
  `descripcion` mediumtext NOT NULL,
  `id_usuario` varchar(60) NOT NULL default '',
  `origen` varchar(100) NOT NULL default '',
  `estado` int(10) NOT NULL default '0',
  `prioridad` int(10) NOT NULL default '0',
  `id_grupo` mediumint(4) unsigned NOT NULL default '0',
  `actualizacion` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `id_creator` varchar(60) default NULL,
  `id_lastupdate` varchar(60) default NULL,
  `id_agente_modulo` bigint(100) NOT NULL,
  `notify_email` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_incidencia`),
  KEY `incident_index_1` (`id_usuario`,`id_incidencia`),
  KEY `id_agente_modulo` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS`tlanguage` (
  `id_language` varchar(6) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tlink` (
  `id_link` int(10) unsigned zerofill NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tmensajes` (
  `id_mensaje` int(10) unsigned NOT NULL auto_increment,
  `id_usuario_origen` varchar(60) NOT NULL default '',
  `id_usuario_destino` varchar(60) NOT NULL default '',
  `mensaje` tinytext NOT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `subject` varchar(255) NOT NULL default '',
  `estado` int(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_mensaje`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tmodule_group` (
  `id_mg` tinyint(4) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  PRIMARY KEY  (`id_mg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tnetwork_component` (
  `id_nc` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(80) NOT NULL,
  `description` varchar(250) default NULL,
  `id_group` int(6) NOT NULL default '1',
  `type` smallint(6) NOT NULL default '6',
  `max` bigint(20) NOT NULL default '0',
  `min` bigint(20) NOT NULL default '0',
  `module_interval` mediumint(8) unsigned NOT NULL default '0',
  `tcp_port` int(10) unsigned NOT NULL default '0',
  `tcp_send` varchar(255) NOT NULL,
  `tcp_rcv` varchar(255) NOT NULL default 'NULL',
  `snmp_community` varchar(255) NOT NULL default 'NULL',
  `snmp_oid` varchar(400) NOT NULL,
  `id_module_group` tinyint(4) unsigned NOT NULL default '0',
  `id_modulo` int(10) unsigned default '0',
  `plugin_user` varchar(250) default '',
  `plugin_pass` varchar(250) default '',
  `plugin_parameter` text,
  `max_timeout` tinyint(3) unsigned default '0',
  `history_data` tinyint(1) unsigned default '1',
  `min_warning` double(18,13) default 0,
  `max_warning` double(18,13) default 0,
  `min_critical` double(18,13) default 0,
  `max_critical` double(18,13) default 0,
  `min_ff_event` int(4) unsigned default '0',
  PRIMARY KEY  (`id_nc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tnetwork_component_group` (
  `id_sg`  int(10) unsigned NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `parent` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tnetwork_profile` (
  `id_np`  int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) default '',
  PRIMARY KEY  (`id_np`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tnetwork_profile_component` (
  `id_nc` mediumint(8) unsigned NOT NULL default '0',
  `id_np` mediumint(8) unsigned NOT NULL default '0',
  KEY `id_np` (`id_np`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tnota` (
  `id_nota` bigint(6) unsigned zerofill NOT NULL auto_increment,
  `id_incident` bigint(6) unsigned zerofill NOT NULL,
  `id_usuario` varchar(100) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `nota` mediumtext NOT NULL,
  PRIMARY KEY  (`id_nota`),
  KEY `id_incident` (`id_incident`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `torigen` (
  `origen` varchar(100) NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tperfil` (
  `id_perfil` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(60) NOT NULL default '',
  `incident_edit` tinyint(3) NOT NULL default '0',
  `incident_view` tinyint(3) NOT NULL default '0',
  `incident_management` tinyint(3) NOT NULL default '0',
  `agent_view` tinyint(3) NOT NULL default '0',
  `agent_edit` tinyint(3) NOT NULL default '0',
  `alert_edit` tinyint(3) NOT NULL default '0',
  `user_management` tinyint(3) NOT NULL default '0',
  `db_management` tinyint(3) NOT NULL default '0',
  `alert_management` tinyint(3) NOT NULL default '0',
  `pandora_management` tinyint(3) NOT NULL default '0',
  PRIMARY KEY  (`id_perfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `trecon_task` (
  `id_rt` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) NOT NULL default '',
  `subnet` varchar(64) NOT NULL default '',
  `id_network_profile` int(10) unsigned NOT NULL default '0',
  `create_incident` tinyint(3) unsigned NOT NULL default '0',
  `id_group` int(10) unsigned NOT NULL default '1',
  `utimestamp` bigint(20) unsigned NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `interval_sweep` int(10) unsigned NOT NULL default '0',
  `id_recon_server` int(10) unsigned NOT NULL default '0',
  `id_os` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id_rt`),
  KEY `recon_task_daemon` (`id_recon_server`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tserver` (
  `id_server` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `ip_address` varchar(100) NOT NULL default '',
  `status` int(11) NOT NULL default '0',
  `laststart` datetime NOT NULL default '0000-00-00 00:00:00',
  `keepalive` datetime NOT NULL default '0000-00-00 00:00:00',
  `snmp_server` tinyint(3) unsigned NOT NULL default '0',
  `network_server` tinyint(3) unsigned NOT NULL default '0',
  `data_server` tinyint(3) unsigned NOT NULL default '0',
  `master` tinyint(3) unsigned NOT NULL default '0',
  `checksum` tinyint(3) unsigned NOT NULL default '0',
  `description` varchar(255) default NULL,
  `recon_server` tinyint(3) unsigned NOT NULL default '0',
  `version` varchar(20) NOT NULL default '',
  `plugin_server` tinyint(3) unsigned NOT NULL default '0',
  `prediction_server` tinyint(3) unsigned NOT NULL default '0',
  `wmi_server` tinyint(3) unsigned NOT NULL default '0',
  `export_server` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_server`),
	KEY `name` (`name`),
	KEY `keepalive` (`keepalive`),
	KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tsesion` (
  `ID_sesion` bigint(4) unsigned NOT NULL auto_increment,
  `ID_usuario` varchar(60) NOT NULL default '0',
  `IP_origen` varchar(100) NOT NULL default '',
  `accion` varchar(100) NOT NULL default '',
  `descripcion` varchar(200) NOT NULL default '',
  `fecha` datetime NOT NULL default '0000-00-00 00:00:00',
  `utimestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ID_sesion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `ttipo_modulo` (
  `id_tipo` smallint(5) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `categoria` int(11) NOT NULL default '0',
  `descripcion` varchar(100) NOT NULL default '',
  `icon` varchar(100) default NULL,
  PRIMARY KEY  (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `ttrap` (
  `id_trap` bigint(20) unsigned NOT NULL auto_increment,
  `source` varchar(50) NOT NULL default '',
  `oid` varchar(255) NOT NULL default '',
  `oid_custom` varchar(255) default '',
  `type` int(11) NOT NULL default '0',
  `type_custom` varchar(100) default '',
  `value` varchar(255) default '',
  `value_custom` varchar(255) default '',
  `alerted` smallint(6) NOT NULL default '0',
  `status` smallint(6) NOT NULL default '0',
  `id_usuario` varchar(150) default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `priority` tinyint(4) unsigned NOT NULL default '2',
  PRIMARY KEY  (`id_trap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tusuario` (
  `id_usuario` varchar(60) NOT NULL default '0',
  `nombre_real` varchar(125) NOT NULL default '',
  `password` varchar(45) default NULL,
  `comentarios` varchar(200) default NULL,
  `fecha_registro` datetime NOT NULL default '0000-00-00 00:00:00',
  `direccion` varchar(100) default '',
  `telefono` varchar(100) default '',
  `nivel` tinyint(1) NOT NULL default '0',
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tusuario_perfil` (
  `id_up` bigint(10) unsigned NOT NULL auto_increment,
  `id_usuario` varchar(100) NOT NULL default '',
  `id_perfil` int(10) unsigned NOT NULL default '0',
  `id_grupo` int(10) NOT NULL default '0',
  `assigned_by` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_up`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tnews` (
  `id_news` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `author` varchar(255)  NOT NULL DEFAULT '',
  `subject` varchar(255)  NOT NULL DEFAULT '',
  `text` TEXT NOT NULL,
  `timestamp` DATETIME  NOT NULL DEFAULT 0,
  PRIMARY KEY(`id_news`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tgraph` (
  `id_graph` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` varchar(100) NOT NULL default '',
  `name` varchar(150) NOT NULL default '',
  `description` TEXT NOT NULL,
  `period` int(11) NOT NULL default '0',
  `width` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `height` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `private` tinyint(1) UNSIGNED NOT NULL default 0,
  `events` tinyint(1) UNSIGNED NOT NULL default 0,
  `stacked` tinyint(1) UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id_graph`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tgraph_source` (
  `id_gs` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_graph` int(11) NOT NULL default 0,
  `id_agent_module` int(11) NOT NULL default 0,
  `weight` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`id_gs`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `treport` (
  `id_report` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` varchar(100) NOT NULL default '',
  `name` varchar(150) NOT NULL default '',
  `description` TEXT NOT NULL,
  `private` tinyint(1) UNSIGNED NOT NULL default 0,
  `id_group` mediumint(8) unsigned NULL default NULL,
  PRIMARY KEY(`id_report`),
  FOREIGN KEY (`id_group`) REFERENCES tgrupo(`id_grupo`)
   ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `treport_content` (
  `id_rc` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_report` INTEGER UNSIGNED NOT NULL default 0,
  `id_gs` INTEGER UNSIGNED NULL default NULL,
  `id_agent_module` bigint(14) unsigned NULL default NULL,
  `type` enum ('simple_graph', 'custom_graph', 'SLA', 'event_report', 'alert_report', 'monitor_report', 'avg_value', 'max_value', 'min_value', 'sumatory', 'general_group_report', 'monitor_health', 'agents_detailed') default 'simple_graph',
  `period` int(11) NOT NULL default 0,
  `order` int (11) NOT NULL default 0,
  PRIMARY KEY(`id_rc`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `treport_content_sla_combined` (
  `id` INTEGER UNSIGNED NOT NULL auto_increment,
  `id_report_content` INTEGER UNSIGNED NOT NULL,
  `id_agent_module` int(10) unsigned NOT NULL,
  `sla_max` int(11) NOT NULL default 0,
  `sla_min` int(11) NOT NULL default 0,
  `sla_limit` int(11) NOT NULL default 0,
  PRIMARY KEY(`id`),
  FOREIGN KEY (`id_report_content`) REFERENCES treport_content(`id_rc`)
     ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_agent_module`) REFERENCES tagente_modulo(`id_agente_modulo`)
     ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tlayout` (
  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50)  NOT NULL,
  `id_group` INTEGER UNSIGNED NOT NULL,
  `background` varchar(200)  NOT NULL,
  `fullscreen` tinyint(1) UNSIGNED NOT NULL default 0,
  `height` INTEGER UNSIGNED NOT NULL default 0,
  `width` INTEGER UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id`)
)  ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tlayout_data` (
  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_layout` INTEGER UNSIGNED NOT NULL default 0,
  `pos_x` INTEGER UNSIGNED NOT NULL default 0,
  `pos_y` INTEGER UNSIGNED NOT NULL default 0,
  `height` INTEGER UNSIGNED NOT NULL default 0,
  `width` INTEGER UNSIGNED NOT NULL default 0,
  `label` varchar(200) DEFAULT "",
  `image` varchar(200) DEFAULT "",
  `type` tinyint(1) UNSIGNED NOT NULL default 0,
  `period` INTEGER UNSIGNED NOT NULL default 3600,
  `id_agente_modulo` mediumint(8) unsigned NOT NULL default '0',
  `id_layout_linked` INTEGER unsigned NOT NULL default '0',
  `parent_item` INTEGER UNSIGNED NOT NULL default 0,
  `label_color` varchar(20) DEFAULT "",
  `no_link_color` tinyint(1) UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS tplugin (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(200) NOT NULL,
    `description` mediumtext default "",
    `max_timeout` int(4) UNSIGNED NOT NULL default 0,
    `execute`varchar(250) NOT NULL,
    `net_dst_opt` varchar(50) default '',
    `net_port_opt` varchar(50) default '',
    `user_opt` varchar(50) default '',
    `pass_opt` varchar(50) default '',
    PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 


CREATE TABLE IF NOT EXISTS `tmodule` (
  `id_module` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY (`id_module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `tserver_export` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `preffix` varchar(100) NOT NULL default '',
  `interval` int(5) unsigned NOT NULL default '300',
  `ip_server` varchar(100) NOT NULL default '',
  `connect_mode` enum ('tentacle', 'ssh', 'local') default 'local',
  `id_export_server` int(10) unsigned default NULL,
  `user` varchar(100) NOT NULL default '',
  `pass` varchar(100) NOT NULL default '',
  `port` int(4) unsigned default '0',
  `directory` varchar(100) NOT NULL default '',
  `options` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- id_export_server is real pandora fms export server process that manages this server
-- id is the "destination" server to export

CREATE TABLE IF NOT EXISTS `tserver_export_data` (
  `id` int(20) unsigned NOT NULL auto_increment,
  `id_export_server` int(10) unsigned default NULL,
  `agent_name` varchar(100) NOT NULL default '',
  `module_name` varchar(100) NOT NULL default '',
  `module_type` varchar(100) NOT NULL default '',
  `data` varchar(255) default NULL, 
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tplanned_downtime` (
  `id` MEDIUMINT( 8 ) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR( 100 ) NOT NULL,
  `description` TEXT NOT NULL,
  `date_from` bigint(20) NOT NULL default '0',
  `date_to` bigint(20) NOT NULL default '0',
  `executed` tinyint(1) UNSIGNED NOT NULL default 0,
  PRIMARY KEY (  `id` ) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tplanned_downtime_agents` (
  `id` int(20) unsigned NOT NULL auto_increment,
  `id_agent` mediumint(8) unsigned NOT NULL default '0',
  `id_downtime` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
