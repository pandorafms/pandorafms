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

-- Priority : 0 - Maintance (grey)
-- Priority : 1 - Low (green)
-- Priority : 2 - Normal (blue)
-- Priority : 3 - Warning (yellow)
-- Priority : 4 - Critical (red)

-- ---------------------------------------------------------------------
-- Table `taddress`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `taddress` (
	`id_a` int(10) unsigned NOT NULL auto_increment,
	`ip` varchar(60) NOT NULL default '',
	`ip_pack` int(10) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id_a`),
	KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `taddress_agent`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `taddress_agent` (
	`id_ag` bigint(20) unsigned NOT NULL auto_increment,
	`id_a` bigint(20) unsigned NOT NULL default '0',
	`id_agent` mediumint(8) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id_ag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tagente`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente` (
	`id_agente` int(10) unsigned NOT NULL auto_increment,
	`nombre` varchar(600) BINARY NOT NULL default '',
	`direccion` varchar(100) default NULL,
	`comentarios` varchar(255) default '',
	`id_grupo` int(10) unsigned NOT NULL default '0',
	`ultimo_contacto` datetime NOT NULL default '1970-01-01 00:00:00',
	`modo` tinyint(1) NOT NULL default '0',
	`intervalo` int(11) unsigned NOT NULL default '300',
	`id_os` int(10) unsigned default '0',
	`os_version` varchar(100) default '',
	`agent_version` varchar(100) default '',
	`ultimo_contacto_remoto` datetime default '1970-01-01 00:00:00',
	`disabled` tinyint(2) NOT NULL default '0',
	`id_parent` int(10) unsigned default '0',
	`custom_id` varchar(255) default '',
	`server_name` varchar(100) default '',
	`cascade_protection` tinyint(2) NOT NULL default '0',
	`timezone_offset` TINYINT(2) NULL DEFAULT '0' COMMENT 'nuber of hours of diference with the server timezone' ,
	`icon_path` VARCHAR(127) NULL DEFAULT NULL COMMENT 'path in the server to the image of the icon representing the agent' ,
	`update_gis_data` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'set it to one to update the position data (altitude, longitude, latitude) when getting information from the agent or to 0 to keep the last value and do not update it' ,
	`url_address` mediumtext NULL,
	`quiet` tinyint(1) NOT NULL default '0',
	`normal_count` bigint(20) NOT NULL default '0',
	`warning_count` bigint(20) NOT NULL default '0',
	`critical_count` bigint(20) NOT NULL default '0',
	`unknown_count` bigint(20) NOT NULL default '0',
	`notinit_count` bigint(20) NOT NULL default '0',
	`total_count` bigint(20) NOT NULL default '0',
	`fired_count` bigint(20) NOT NULL default '0',
	`update_module_count` tinyint(1) NOT NULL default '0',
	`update_alert_count` tinyint(1) NOT NULL default '0',
	PRIMARY KEY  (`id_agente`),
	KEY `nombre` (`nombre`(255)),
	KEY `direccion` (`direccion`),
	KEY `disabled` (`disabled`),
	KEY `id_grupo` (`id_grupo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- ---------------------------------------------------------------------
-- Table `tagente_datos`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos` (
	`id_agente_modulo` int(10) unsigned NOT NULL default '0',
	`datos` double(22,2) default NULL,
	`utimestamp` bigint(20) default '0',
	KEY `data_index1` (`id_agente_modulo`),
	KEY `idx_utimestamp` USING BTREE (`utimestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- ---------------------------------------------------------------------
-- Table `tagente_datos_inc`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos_inc` (
	`id_agente_modulo` int(10) unsigned NOT NULL default '0',
	`datos` double(22,2) default NULL,
	`utimestamp` int(20) unsigned default '0',
	KEY `data_inc_index_1` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tagente_datos_string`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos_string` (
	`id_agente_modulo` int(10) unsigned NOT NULL default '0',
	`datos` text NOT NULL,
	`utimestamp` int(20) unsigned NOT NULL default 0,
	KEY `data_string_index_1` (`id_agente_modulo`),
	KEY `idx_utimestamp` USING BTREE (`utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tagente_datos_log4x`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos_log4x` (
	`id_tagente_datos_log4x` bigint(20) unsigned NOT NULL auto_increment,
	`id_agente_modulo` int(10) unsigned NOT NULL default '0',
	
	`severity` text NOT NULL,
	`message` text NOT NULL,
	`stacktrace` text NOT NULL,
	
	`utimestamp` int(20) unsigned NOT NULL default 0,
	PRIMARY KEY  (`id_tagente_datos_log4x`),
	KEY `data_log4x_index_1` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tagente_estado`
-- -----------------------------------------------------
CREATE TABLE `tagente_estado` (
	`id_agente_estado` int(10) unsigned NOT NULL auto_increment,
	`id_agente_modulo` int(10) NOT NULL default '0',
	`datos` text NOT NULL,
	`timestamp` datetime NOT NULL default '1970-01-01 00:00:00',
	`estado` int(4) NOT NULL default '0',
	`id_agente` int(10) NOT NULL default '0',
	`last_try` datetime default NULL,
	`utimestamp` bigint(20) NOT NULL default '0',
	`current_interval` int(8) unsigned NOT NULL default '0',
	`running_by` smallint(4) unsigned default '0',
	`last_execution_try` bigint(20) NOT NULL default '0',
	`status_changes` tinyint(4) default 0,
	`last_status` tinyint(4) default 0,
	`last_known_status` tinyint(4) default 0,
	`last_error` int(4) NOT NULL default '0',
	`ff_start_utimestamp` bigint(20) default 0,
	PRIMARY KEY  (`id_agente_estado`),
	KEY `status_index_1` (`id_agente_modulo`),
	KEY `idx_agente` (`id_agente`),
	KEY `running_by` (`running_by`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
-- Probably last_execution_try index is not useful and loads more than benefits

-- -----------------------------------------------------
-- Table `tagente_modulo`
-- -----------------------------------------------------
-- id_modulo now uses tmodule 
-- ---------------------------
-- 1 - Data server modules (agent related modules)
-- 2 - Network server modules
-- 4 - Plugin server
-- 5 - Predictive server
-- 6 - WMI server
-- 7 - WEB Server (enteprise)

CREATE TABLE IF NOT EXISTS `tagente_modulo` (
	`id_agente_modulo` int(10) unsigned NOT NULL auto_increment,
	`id_agente` int(10) unsigned NOT NULL default '0',
	`id_tipo_modulo` smallint(5) NOT NULL default '0',
	`descripcion` TEXT NOT NULL,
	`extended_info` TEXT NOT NULL,
	`nombre` text NOT NULL,
	`unit` text,
	`id_policy_module` INTEGER unsigned NOT NULL default '0',
	`max` bigint(20) default '0',
	`min` bigint(20) default '0',
	`module_interval` int(4) unsigned default '0',
	`cron_interval` varchar(100) default '',
	`module_ff_interval` int(4) unsigned default '0',
	`tcp_port` int(4) unsigned default '0',
	`tcp_send` TEXT,
	`tcp_rcv` TEXT,
	`snmp_community` varchar(100) default '',
	`snmp_oid` varchar(255) default '0',
	`ip_target` varchar(100) default '',
	`id_module_group` int(4) unsigned default '0',
	`flag` tinyint(1) unsigned default '1',
	`id_modulo` int(10) unsigned default '0',
	`disabled` tinyint(1) unsigned NOT NULL default '0',
	`id_export` smallint(4) unsigned default '0',
	`plugin_user` text,
	`plugin_pass` text,
	`plugin_parameter` text,
	`id_plugin` int(10) default '0',
	`post_process` double(24,15) default 0,
	`prediction_module` bigint(14) default '0',
	`max_timeout` int(4) unsigned default '0',
	`max_retries` int(4) unsigned default '0',
	`custom_id` varchar(255) default '',
	`history_data` tinyint(1) unsigned default '1',
	`min_warning` double(18,2) default 0,
	`max_warning` double(18,2) default 0,
	`str_warning` text,
	`min_critical` double(18,2) default 0,
	`max_critical` double(18,2) default 0,
	`str_critical` text,
	`min_ff_event` int(4) unsigned default '0',
	`delete_pending` int(1) unsigned default 0,
	`policy_linked` tinyint(1) unsigned not null default 0,
	`policy_adopted` tinyint(1) unsigned not null default 0,
	`custom_string_1` text,
	`custom_string_2` text,
	`custom_string_3` text,
	`custom_integer_1` int(10) default 0,
	`custom_integer_2` int(10) default 0,
	`wizard_level` enum('basic','advanced','nowizard') default 'nowizard',
	`macros` text,
	`critical_instructions` text NOT NULL,
	`warning_instructions` text NOT NULL,
	`unknown_instructions` text NOT NULL,
	`quiet` tinyint(1) NOT NULL default '0',
	`critical_inverse` tinyint(1) unsigned default '0',
	`warning_inverse` tinyint(1) unsigned default '0',
	`id_category` int(10) default 0,
	`disabled_types_event` TEXT NOT NULL,
	`module_macros` TEXT NOT NULL,
	`min_ff_event_normal` int(4) unsigned default '0',
	`min_ff_event_warning` int(4) unsigned default '0',
	`min_ff_event_critical` int(4) unsigned default '0',
	`each_ff` tinyint(1) unsigned default '0',
	`ff_timeout` int(4) unsigned default '0',
	PRIMARY KEY  (`id_agente_modulo`),
	KEY `main_idx` (`id_agente_modulo`,`id_agente`),
	KEY `tam_agente` (`id_agente`),
	KEY `id_tipo_modulo` (`id_tipo_modulo`),
	KEY `disabled` (`disabled`),
	KEY `module` (`id_modulo`),
	KEY `nombre` (`nombre` (255)),
	KEY `module_group` (`id_module_group`) using btree
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
-- snmp_oid is also used for WMI query

-- -----------------------------------------------------
-- Table `tagent_access`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_access` (
	`id_agent` int(10) unsigned NOT NULL default '0',
	`utimestamp` bigint(20) NOT NULL default '0',
	KEY `agent_index` (`id_agent`),
	KEY `idx_utimestamp` USING BTREE (`utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_snmp`
-- -----------------------------------------------------
CREATE TABLE  IF NOT EXISTS  `talert_snmp` (
	`id_as` int(10) unsigned NOT NULL auto_increment,
	`id_alert` int(10) unsigned NOT NULL default '0',
	`al_field1` text NOT NULL,
	`al_field2` text NOT NULL,
	`al_field3` text NOT NULL,
	`al_field4` text NOT NULL,
	`al_field5` text NOT NULL,
	`al_field6` text NOT NULL,
	`al_field7` text NOT NULL,
	`al_field8` text NOT NULL,
	`al_field9` text NOT NULL,
	`al_field10` text NOT NULL,
	`description` varchar(255) default '',
	`alert_type` int(2) unsigned NOT NULL default '0',
	`agent` varchar(100) default '',
	`custom_oid` text,
	`oid` varchar(255) NOT NULL default '',
	`time_threshold` int(11) NOT NULL default '0',
	`times_fired` int(2) unsigned NOT NULL default '0',
	`last_fired` datetime NOT NULL default '1970-01-01 00:00:00',
	`max_alerts` int(11) NOT NULL default '1',
	`min_alerts` int(11) NOT NULL default '1',
	`internal_counter` int(2) unsigned NOT NULL default '0',
	`priority` tinyint(4) default '0',
	`_snmp_f1_` text, 
	`_snmp_f2_` text, 
	`_snmp_f3_` text,
	`_snmp_f4_` text, 
	`_snmp_f5_` text, 
	`_snmp_f6_` text,
	`_snmp_f7_` text,
	`_snmp_f8_` text,
	`_snmp_f9_` text,
	`_snmp_f10_` text,
	`_snmp_f11_` text,
	`_snmp_f12_` text,
	`_snmp_f13_` text,
	`_snmp_f14_` text,
	`_snmp_f15_` text,
	`_snmp_f16_` text,
	`_snmp_f17_` text,
	`_snmp_f18_` text,
	`_snmp_f19_` text,
	`_snmp_f20_` text,
	`trap_type` int(11) NOT NULL default '-1',
	`single_value` varchar(255) default '', 
	`position` int(10) unsigned NOT NULL default '0',
	`id_group` int(10) unsigned NOT NULL default '0',
	`order_1` int(10) unsigned NOT NULL default 1,
	`order_2` int(10) unsigned NOT NULL default 2,
	`order_3` int(10) unsigned NOT NULL default 3,
	`order_4` int(10) unsigned NOT NULL default 4,
	`order_5` int(10) unsigned NOT NULL default 5,
	`order_6` int(10) unsigned NOT NULL default 6,
	`order_7` int(10) unsigned NOT NULL default 7,
	`order_8` int(10) unsigned NOT NULL default 8,
	`order_9` int(10) unsigned NOT NULL default 9,
	`order_10` int(10) unsigned NOT NULL default 10,
	`order_11` int(10) unsigned NOT NULL default 11,
	`order_12` int(10) unsigned NOT NULL default 12,
	`order_13` int(10) unsigned NOT NULL default 13,
	`order_14` int(10) unsigned NOT NULL default 14,
	`order_15` int(10) unsigned NOT NULL default 15,
	`order_16` int(10) unsigned NOT NULL default 16,
	`order_17` int(10) unsigned NOT NULL default 17,
	`order_18` int(10) unsigned NOT NULL default 18,
	`order_19` int(10) unsigned NOT NULL default 19,
	`order_20` int(10) unsigned NOT NULL default 20,
	PRIMARY KEY  (`id_as`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_commands`
-- -----------------------------------------------------
CREATE TABLE  IF NOT EXISTS `talert_commands` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	`command` text,
	`description` text,
	`internal` tinyint(1) default 0,
	`fields_descriptions` TEXT,
	`fields_values` TEXT,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_actions`
-- -----------------------------------------------------
CREATE TABLE  IF NOT EXISTS `talert_actions` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` text,
	`id_alert_command` int(10) unsigned NULL default 0,
	`field1` text NOT NULL,
	`field2` text NOT NULL,
	`field3` text NOT NULL,
	`field4` text NOT NULL,
	`field5` text NOT NULL,
	`field6` text NOT NULL,
	`field7` text NOT NULL,
	`field8` text NOT NULL,
	`field9` text NOT NULL,
	`field10` text NOT NULL,
	`id_group` mediumint(8) unsigned NULL default 0,
	`action_threshold` int(10) NOT NULL default '0',
	`field1_recovery` text NOT NULL,
	`field2_recovery` text NOT NULL,
	`field3_recovery` text NOT NULL,
	`field4_recovery` text NOT NULL,
	`field5_recovery` text NOT NULL,
	`field6_recovery` text NOT NULL,
	`field7_recovery` text NOT NULL,
	`field8_recovery` text NOT NULL,
	`field9_recovery` text NOT NULL,
	`field10_recovery` text NOT NULL,
	PRIMARY KEY  (`id`),
	FOREIGN KEY (`id_alert_command`) REFERENCES talert_commands(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_templates`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_templates` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` text,
	`description` mediumtext,
	`id_alert_action` int(10) unsigned NULL,
	`field1` text NOT NULL,
	`field2` text NOT NULL,
	`field3` text NOT NULL,
	`field4` text NOT NULL,
	`field5` text NOT NULL,
	`field6` text NOT NULL,
	`field7` text NOT NULL,
	`field8` text NOT NULL,
	`field9` text NOT NULL,
	`field10` text NOT NULL,
	`type` ENUM ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal', 'warning', 'critical', 'onchange', 'unknown', 'always'),
	`value` varchar(255) default '',
	`matches_value` tinyint(1) default 0,
	`max_value` double(18,2) default NULL,
	`min_value` double(18,2) default NULL,
	`time_threshold` int(10) NOT NULL default '0',
	`max_alerts` int(4) unsigned NOT NULL default '1',
	`min_alerts` int(4) unsigned NOT NULL default '0',
	`time_from` time default '00:00:00',
	`time_to` time default '00:00:00',
	`monday` tinyint(1) default 1,
	`tuesday` tinyint(1) default 1,
	`wednesday` tinyint(1) default 1,
	`thursday` tinyint(1) default 1,
	`friday` tinyint(1) default 1,
	`saturday` tinyint(1) default 1,
	`sunday` tinyint(1) default 1,
	`recovery_notify` tinyint(1) default '0',
	`field1_recovery` text NOT NULL,
	`field2_recovery` text NOT NULL,
	`field3_recovery` text NOT NULL,
	`field4_recovery` text NOT NULL,
	`field5_recovery` text NOT NULL,
	`field6_recovery` text NOT NULL,
	`field7_recovery` text NOT NULL,
	`field8_recovery` text NOT NULL,
	`field9_recovery` text NOT NULL,
	`field10_recovery` text NOT NULL,
	`priority` tinyint(4) default '0',
	`id_group` mediumint(8) unsigned NULL default 0,
	`special_day` tinyint(1) default 0,
	`wizard_level` enum('basic','advanced','nowizard') default 'nowizard',
	PRIMARY KEY  (`id`),
	KEY `idx_template_action` (`id_alert_action`),
	FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
		ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_template_modules`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_template_modules` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_agent_module` int(10) unsigned NOT NULL,
	`id_alert_template` int(10) unsigned NOT NULL,
	`id_policy_alerts` int(10) unsigned NOT NULL default '0',
	`internal_counter` int(4) default '0',
	`last_fired` bigint(20) NOT NULL default '0',
	`last_reference` bigint(20) NOT NULL default '0',
	`times_fired` int(3) NOT NULL default '0',
	`disabled` tinyint(1) default '0',
	`standby` tinyint(1) default '0',
	`priority` tinyint(4) default '0',
	`force_execution` tinyint(1) default '0',
	PRIMARY KEY (`id`),
	KEY `idx_template_module` (`id_agent_module`),
	FOREIGN KEY (`id_agent_module`) REFERENCES tagente_modulo(`id_agente_modulo`)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`id_alert_template`) REFERENCES talert_templates(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE,
	UNIQUE (`id_agent_module`, `id_alert_template`),
	INDEX force_execution (`force_execution`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_template_module_actions`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_template_module_actions` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_alert_template_module` int(10) unsigned NOT NULL,
	`id_alert_action` int(10) unsigned NOT NULL,
	`fires_min` int(3) unsigned default 0,
	`fires_max` int(3) unsigned default 0,
	`module_action_threshold` int(10) NOT NULL default '0',
	`last_execution` bigint(20) NOT NULL default '0',
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_alert_template_module`) REFERENCES talert_template_modules(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_special_days`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_special_days` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`id_group` INT(10) NOT NULL DEFAULT 0,
	`date` date NOT NULL DEFAULT '1970-01-01',
	`same_day` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL DEFAULT 'sunday',
	`description` text,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tattachment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tattachment` (
	`id_attachment` int(10) unsigned NOT NULL auto_increment,
	`id_incidencia` int(10) unsigned NOT NULL default '0',
	`id_usuario` varchar(60) NOT NULL default '',
	`filename` varchar(255) NOT NULL default '',
	`description` varchar(150) default '',
	`size` bigint(20) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id_attachment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tconfig` (
	`id_config` int(10) unsigned NOT NULL auto_increment,
	`token` varchar(100) NOT NULL default '',
	`value` text NOT NULL,
	PRIMARY KEY  (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tconfig_os`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS  `tconfig_os` (
	`id_os` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	`description` varchar(250) default '',
	`icon_name` varchar(100) default '',
	PRIMARY KEY  (`id_os`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tevento`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevento` (
	`id_evento` bigint(20) unsigned NOT NULL auto_increment,
	`id_agente` int(10) NOT NULL default '0',
	`id_usuario` varchar(100) NOT NULL default '0',
	`id_grupo` mediumint(4) NOT NULL default '0',
	`estado` tinyint(3) unsigned NOT NULL default '0',
	`timestamp` datetime NOT NULL default '1970-01-01 00:00:00',
	`evento` text NOT NULL,
	`utimestamp` bigint(20) NOT NULL default '0',
	`event_type` enum('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change') default 'unknown',
	`id_agentmodule` int(10) NOT NULL default '0',
	`id_alert_am` int(10) NOT NULL default '0',
	`criticity` int(4) unsigned NOT NULL default '0',
	`user_comment` text NOT NULL,
	`tags` text NOT NULL,
	`source` tinytext NOT NULL,
	`id_extra` tinytext NOT NULL,
	`critical_instructions` text NOT NULL,
	`warning_instructions` text NOT NULL,
	`unknown_instructions` text NOT NULL,
	`owner_user` VARCHAR(100) NOT NULL DEFAULT '',
	`ack_utimestamp` BIGINT(20) NOT NULL DEFAULT '0',
	`custom_data` TEXT NOT NULL,
	PRIMARY KEY  (`id_evento`),
	KEY `idx_agente` (`id_agente`),
	KEY `idx_agentmodule` (`id_agentmodule`),
	KEY `idx_utimestamp` USING BTREE (`utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- Criticity: 0 - Maintance (grey)
-- Criticity: 1 - Informational (blue)
-- Criticity: 2 - Normal (green) (status 0)
-- Criticity: 3 - Warning (yellow) (status 2)
-- Criticity: 4 - Critical (red) (status 1)

-- ---------------------------------------------------------------------
-- Table `tgrupo`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgrupo` (
	`id_grupo` mediumint(4) unsigned NOT NULL auto_increment,
	`nombre` varchar(100) NOT NULL default '',
	`icon` varchar(50) default 'world',
	`parent` mediumint(4) unsigned NOT NULL default '0',
	`propagate` tinyint(1) unsigned NOT NULL default '0',
	`disabled` tinyint(3) unsigned NOT NULL default '0',
	`custom_id` varchar(255) default '',
	`id_skin` int(10) unsigned NOT NULL default '0',
	`description` text,
	`contact` text,
	`other` text,
 	PRIMARY KEY  (`id_grupo`),
 	KEY `parent_index` (`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tincidencia`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tincidencia` (
	`id_incidencia` bigint(6) unsigned zerofill NOT NULL auto_increment,
	`inicio` datetime NOT NULL default '1970-01-01 00:00:00',
	`cierre` datetime NOT NULL default '1970-01-01 00:00:00',
	`titulo` text NOT NULL,
	`descripcion` text NOT NULL,
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
	`id_agent` int(10) unsigned NULL default 0, 
	PRIMARY KEY  (`id_incidencia`),
	KEY `incident_index_1` (`id_usuario`,`id_incidencia`),
	KEY `id_agente_modulo` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tlanguage`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlanguage` (
	`id_language` varchar(6) NOT NULL default '',
	`name` varchar(100) NOT NULL default '',
	PRIMARY KEY  (`id_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tlink`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlink` (
	`id_link` int(10) unsigned zerofill NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	`link` varchar(255) NOT NULL default '',
	PRIMARY KEY  (`id_link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tmensajes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmensajes` (
	`id_mensaje` int(10) unsigned NOT NULL auto_increment,
	`id_usuario_origen` varchar(60) NOT NULL default '',
	`id_usuario_destino` varchar(60) NOT NULL default '',
	`mensaje` text NOT NULL,
	`timestamp` bigint (20) unsigned NOT NULL default '0',
	`subject` varchar(255) NOT NULL default '',
	`estado` int(4) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id_mensaje`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tmodule_group`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule_group` (
	`id_mg` tinyint(4) unsigned NOT NULL auto_increment,
	`name` varchar(150) NOT NULL default '',
	PRIMARY KEY  (`id_mg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tmodule_relationship`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule_relationship` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`module_a` int(10) unsigned NOT NULL,
	`module_b` int(10) unsigned NOT NULL,
	`disable_update` tinyint(1) unsigned NOT NULL default '0',
	`id_rt` int(10) unsigned NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`module_a`) REFERENCES tagente_modulo(`id_agente_modulo`)
		ON DELETE CASCADE,
	FOREIGN KEY (`module_b`) REFERENCES tagente_modulo(`id_agente_modulo`)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnetwork_component`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_component` (
	`id_nc` int(10) unsigned NOT NULL auto_increment,
	`name` text NOT NULL,
	`description` TEXT NOT NULL,
	`id_group` int(6) NOT NULL default '1',
	`type` smallint(6) NOT NULL default '6',
	`max` bigint(20) NOT NULL default '0',
	`min` bigint(20) NOT NULL default '0',
	`module_interval` mediumint(8) unsigned NOT NULL default '0',
	`tcp_port` int(10) unsigned NOT NULL default '0',
	`tcp_send` text NOT NULL,
	`tcp_rcv` text NOT NULL,
	`snmp_community` varchar(255) NOT NULL default 'NULL',
	`snmp_oid` varchar(400) NOT NULL,
	`id_module_group` tinyint(4) unsigned NOT NULL default '0',
	`id_modulo` int(10) unsigned default '0',
	`id_plugin` INTEGER unsigned default '0',
	`plugin_user` text,
	`plugin_pass` text,
	`plugin_parameter` text,
	`max_timeout` int(4) unsigned default '0',
	`max_retries` int(4) unsigned default '0',
	`history_data` tinyint(1) unsigned default '1',
	`min_warning` double(18,2) default 0,
	`max_warning` double(18,2) default 0,
	`str_warning` text,
	`min_critical` double(18,2) default 0,
	`max_critical` double(18,2) default 0,
	`str_critical` text,
	`min_ff_event` int(4) unsigned default '0',
	`custom_string_1` text,
	`custom_string_2` text,
	`custom_string_3` text,
	`custom_integer_1` int(10) default 0,
	`custom_integer_2` int(10) default 0,
	`post_process` double(24,15) default 0,
	`unit` text,
	`wizard_level` enum('basic','advanced','nowizard') default 'nowizard',
	`macros` text,
	`critical_instructions` text NOT NULL,
	`warning_instructions` text NOT NULL,
	`unknown_instructions` text NOT NULL,
	`critical_inverse` tinyint(1) unsigned default '0',
	`warning_inverse` tinyint(1) unsigned default '0',
	`id_category` int(10) default 0,
	`tags` text NOT NULL,
	`disabled_types_event` TEXT NOT NULL,
	`module_macros` TEXT NOT NULL,
	`min_ff_event_normal` int(4) unsigned default '0',
	`min_ff_event_warning` int(4) unsigned default '0',
	`min_ff_event_critical` int(4) unsigned default '0',
	`each_ff` tinyint(1) unsigned default '0',
	PRIMARY KEY  (`id_nc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnetwork_component_group`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_component_group` (
	`id_sg`  int(10) unsigned NOT NULL auto_increment,
	`name` varchar(200) NOT NULL default '',
	`parent` mediumint(8) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnetwork_profile`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_profile` (
	`id_np`  int(10) unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	`description` varchar(250) default '',
	PRIMARY KEY  (`id_np`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnetwork_profile_component`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_profile_component` (
	`id_nc` mediumint(8) unsigned NOT NULL default '0',
	`id_np` mediumint(8) unsigned NOT NULL default '0',
	KEY `id_np` (`id_np`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnota`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnota` (
	`id_nota` bigint(6) unsigned zerofill NOT NULL auto_increment,
	`id_incident` bigint(6) unsigned zerofill NOT NULL,
	`id_usuario` varchar(100) NOT NULL default '0',
	`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
	`nota` mediumtext NOT NULL,
	PRIMARY KEY  (`id_nota`),
	KEY `id_incident` (`id_incident`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `torigen`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `torigen` (
	`origen` varchar(100) NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tperfil`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tperfil` (
	`id_perfil` int(10) unsigned NOT NULL auto_increment,
	`name` TEXT NOT NULL,
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
	`report_view` tinyint(3) NOT NULL default '0',
	`report_edit` tinyint(3) NOT NULL default '0',
	`report_management` tinyint(3) NOT NULL default '0',
	`event_view` tinyint(3) NOT NULL default '0',
	`event_edit` tinyint(3) NOT NULL default '0',
	`event_management` tinyint(3) NOT NULL default '0',
	`agent_disable` tinyint(3) NOT NULL default '0',
	PRIMARY KEY  (`id_perfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `trecon_script`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trecon_script` (
	`id_recon_script` int(10) NOT NULL auto_increment,
	`name` varchar(100) default '',
	`description` TEXT,
	`script` varchar(250) default '',
	`macros` TEXT,
	PRIMARY KEY  (`id_recon_script`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `trecon_task`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trecon_task` (
	`id_rt` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	`description` varchar(250) NOT NULL default '',
	`subnet` text NOT NULL,
	`id_network_profile` int(10) unsigned NOT NULL default '0',
	`create_incident` tinyint(3) unsigned NOT NULL default '0',
	`id_group` int(10) unsigned NOT NULL default '1',
	`utimestamp` bigint(20) unsigned NOT NULL default '0',
	`status` tinyint(4) NOT NULL default '0',
	`interval_sweep` int(10) unsigned NOT NULL default '0',
	`id_recon_server` int(10) unsigned NOT NULL default '0',
	`id_os` tinyint(4) NOT NULL default '0',
	`recon_ports` varchar(250) NOT NULL default '',
	`snmp_community` varchar(64) NOT NULL default 'public',
	`id_recon_script` int(10),
	`field1` text NOT NULL,
	`field2` varchar(250) NOT NULL default '',
	`field3` varchar(250) NOT NULL default '',
	`field4` varchar(250) NOT NULL default '',
	`os_detect` tinyint(1) unsigned default '0',
	`resolve_names` tinyint(1) unsigned default '0',
	`parent_detection` tinyint(1) unsigned default '0',
	`parent_recursion` tinyint(1) unsigned default '0',
	`disabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`macros` TEXT,
	PRIMARY KEY  (`id_rt`),
	KEY `recon_task_daemon` (`id_recon_server`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tserver`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tserver` (
	`id_server` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	`ip_address` varchar(100) NOT NULL default '',
	`status` int(11) NOT NULL default '0',
	`laststart` datetime NOT NULL default '1970-01-01 00:00:00',
	`keepalive` datetime NOT NULL default '1970-01-01 00:00:00',
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
	`server_type` tinyint(3) unsigned NOT NULL default '0',
	`queued_modules` int(5) unsigned NOT NULL default '0',
	`threads` int(5) unsigned NOT NULL default '0',
	`lag_time` int(11) NOT NULL default 0,
	`lag_modules` int(11) NOT NULL default 0,
	`total_modules_running` int(11) NOT NULL default 0,
	`my_modules` int(11) NOT NULL default 0,
	`stat_utimestamp` bigint(20) NOT NULL default '0',
	PRIMARY KEY  (`id_server`),
	KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
CREATE TABLE IF NOT EXISTS `tsesion` (
	`id_sesion` bigint(20) unsigned NOT NULL auto_increment,
	`id_usuario` varchar(60) NOT NULL default '0',
	`ip_origen` varchar(100) NOT NULL default '',
	`accion` varchar(100) NOT NULL default '',
	`descripcion` text NOT NULL,
	`fecha` datetime NOT NULL default '1970-01-01 00:00:00',
	`utimestamp` bigint(20) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id_sesion`),
	KEY `idx_user` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `ttipo_modulo`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttipo_modulo` (
	`id_tipo` smallint(5) unsigned NOT NULL auto_increment,
	`nombre` varchar(100) NOT NULL default '',
	`categoria` int(11) NOT NULL default '0',
	`descripcion` varchar(100) NOT NULL default '',
	`icon` varchar(100) default NULL,
	PRIMARY KEY  (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `ttrap`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttrap` (
	`id_trap` bigint(20) unsigned NOT NULL auto_increment,
	`source` varchar(50) NOT NULL default '',
	`oid` text NOT NULL,
	`oid_custom` text,
	`type` int(11) NOT NULL default '0',
	`type_custom` varchar(100) default '',
	`value` text,
	`value_custom` text,
	`alerted` smallint(6) NOT NULL default '0',
	`status` smallint(6) NOT NULL default '0',
	`id_usuario` varchar(150) default '',
	`timestamp` datetime NOT NULL default '1970-01-01 00:00:00',
	`priority` tinyint(4) unsigned NOT NULL default '2',
	`text` varchar(255) default '',
	`description` varchar(255) default '',
	`severity` tinyint(4) unsigned NOT NULL default '2',
	PRIMARY KEY  (`id_trap`),
	INDEX timestamp (`timestamp`),
	INDEX status (`status`),
	INDEX source (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tusuario`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tusuario` (
	`id_user` varchar(60) NOT NULL default '0',
	`fullname` varchar(255) NOT NULL,
	`firstname` varchar(255) NOT NULL,
	`lastname` varchar(255) NOT NULL,
	`middlename` varchar(255) NOT NULL,
	`password` varchar(45) default NULL,
	`comments` varchar(200) default NULL,
	`last_connect` bigint(20) NOT NULL default '0',
	`registered` bigint(20) NOT NULL default '0',
	`email` varchar(100) default NULL,
	`phone` varchar(100) default NULL,
	`is_admin` tinyint(1) unsigned NOT NULL default '0',
	`language` varchar(10) default NULL,
	`timezone` varchar(50) default '',
	`block_size` int(4) NOT NULL DEFAULT 20,
	`flash_chart` int(4) NOT NULL DEFAULT 1,
	`id_skin` int(10) unsigned NOT NULL DEFAULT 0,
	`disabled` int(4) NOT NULL DEFAULT 0,
	`shortcut` tinyint(1) DEFAULT 0,
	`shortcut_data` text,
	`section` TEXT NOT NULL,
	`data_section` TEXT NOT NULL,
	`force_change_pass` tinyint(1) unsigned NOT NULL default 0,
	`last_pass_change` DATETIME  NOT NULL DEFAULT 0,
	`last_failed_login` DATETIME  NOT NULL DEFAULT 0,
	`failed_attempt` int(4) NOT NULL DEFAULT 0,
	`login_blocked` tinyint(1) unsigned NOT NULL default 0,
	`metaconsole_access` enum('basic','advanced') default 'basic',
	`not_login` tinyint(1) unsigned NOT NULL DEFAULT 0,
	`metaconsole_agents_manager` tinyint(1) unsigned NOT NULL default 0,
	`metaconsole_assigned_server` int(10) unsigned NOT NULL default 0,
	`metaconsole_access_node` tinyint(1) unsigned NOT NULL default 0,
	UNIQUE KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tusuario_perfil`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tusuario_perfil` (
	`id_up` bigint(10) unsigned NOT NULL auto_increment,
	`id_usuario` varchar(100) NOT NULL default '',
	`id_perfil` int(10) unsigned NOT NULL default '0',
	`id_grupo` int(10) NOT NULL default '0',
	`assigned_by` varchar(100) NOT NULL default '',
	`id_policy` int(10) unsigned NOT NULL default '0',
	`tags` text NOT NULL,
	PRIMARY KEY  (`id_up`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tuser_double_auth`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tuser_double_auth` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_user` varchar(60) NOT NULL,
	`secret` varchar(20) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`id_user`),
	FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnews`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnews` (
	`id_news` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
	`author` varchar(255)  NOT NULL DEFAULT '',
	`subject` varchar(255)  NOT NULL DEFAULT '',
	`text` TEXT NOT NULL,
	`timestamp` DATETIME  NOT NULL DEFAULT 0,
	`id_group` int(10) NOT NULL default 0,
	`modal` tinyint(1) DEFAULT 0,
	`expire` tinyint(1) DEFAULT 0,
	`expire_timestamp` DATETIME  NOT NULL DEFAULT 0,
	PRIMARY KEY(`id_news`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tgraph`
-- ----------------------------------------------------------------------
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
	`id_group` mediumint(8) unsigned NULL default 0,
	`id_graph_template` int(11) NOT NULL default 0,
	PRIMARY KEY(`id_graph`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tgraph_source`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgraph_source` (
	`id_gs` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
	`id_graph` int(11) NOT NULL default 0,
	`id_agent_module` int(11) NOT NULL default 0,
	`weight` float(8,3) NOT NULL DEFAULT 0,
	PRIMARY KEY(`id_gs`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `treport`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport` (
	`id_report` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
	`id_user` varchar(100) NOT NULL default '',
	`name` varchar(150) NOT NULL default '',
	`description` TEXT NOT NULL,
	`private` tinyint(1) UNSIGNED NOT NULL default 0,
	`id_group` mediumint(8) unsigned NULL default NULL,
	`custom_logo` varchar(200)  default NULL,
	`header` MEDIUMTEXT,
	`first_page` MEDIUMTEXT,
	`footer` MEDIUMTEXT,
	`custom_font` varchar(200) default NULL,
	`id_template` INTEGER UNSIGNED DEFAULT 0,
	`id_group_edit` mediumint(8) unsigned NULL DEFAULT 0,
	`metaconsole` tinyint(1) DEFAULT 0,
	`non_interactive` tinyint(1) UNSIGNED NOT NULL default 0,
	PRIMARY KEY(`id_report`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `treport_content`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content` (
	`id_rc` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_report` INTEGER UNSIGNED NOT NULL default 0,
	`id_gs` INTEGER UNSIGNED NULL default NULL,
	`id_agent_module` bigint(14) unsigned NULL default NULL,
	`type` varchar(30) default 'simple_graph',
	`period` int(11) NOT NULL default 0,
	`order` int (11) NOT NULL default 0,
	`name` varchar(150) NULL,
	`description` mediumtext, 
	`id_agent` int(10) unsigned NOT NULL default 0,
	`text` TEXT,
	`external_source` Text,
	`treport_custom_sql_id` INTEGER UNSIGNED default 0,
	`header_definition` TinyText,
	`column_separator` TinyText,
	`line_separator` TinyText,
	`time_from` time default '00:00:00',
	`time_to` time default '00:00:00',
	`monday` tinyint(1) default 1,
	`tuesday` tinyint(1) default 1,
	`wednesday` tinyint(1) default 1,
	`thursday` tinyint(1) default 1,
	`friday` tinyint(1) default 1,
	`saturday` tinyint(1) default 1,
	`sunday` tinyint(1) default 1,
	`only_display_wrong` tinyint (1) unsigned default 0 not null,
	`top_n` INT NOT NULL default 0,
	`top_n_value` INT NOT NULL default 10,
	`exception_condition` INT NOT NULL default 0,
	`exception_condition_value` DOUBLE (18,6) NOT NULL default 0,
	`show_resume` INT NOT NULL default 0,
	`order_uptodown` INT NOT NULL default 0,
	`show_graph` INT NOT NULL default 0,
	`group_by_agent` INT NOT NULL default 0,
	`style` TEXT NOT NULL,
	`id_group` INT (10) unsigned NOT NULL DEFAULT 0,
	`id_module_group` INT (10) unsigned NOT NULL DEFAULT 0,
	`server_name` text,
	PRIMARY KEY(`id_rc`),
	FOREIGN KEY (`id_report`) REFERENCES treport(`id_report`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `treport_content_sla_combined`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_sla_combined` (
	`id` INTEGER UNSIGNED NOT NULL auto_increment,
	`id_report_content` INTEGER UNSIGNED NOT NULL,
	`id_agent_module` int(10) unsigned NOT NULL,
	`sla_max` double(18,2) NOT NULL default 0,
	`sla_min` double(18,2) NOT NULL default 0,
	`sla_limit` double(18,2) NOT NULL default 0,
	`server_name` text,
	PRIMARY KEY(`id`),
	FOREIGN KEY (`id_report_content`) REFERENCES treport_content(`id_rc`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `treport_content_item`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_item` (
	`id` INTEGER UNSIGNED NOT NULL auto_increment, 
	`id_report_content` INTEGER UNSIGNED NOT NULL, 
	`id_agent_module` int(10) unsigned NOT NULL, 
	`server_name` text,
	`operation` text,
	PRIMARY KEY(`id`),
	FOREIGN KEY (`id_report_content`) REFERENCES treport_content(`id_rc`)
		ON UPDATE CASCADE ON DELETE CASCADE 
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `treport_custom_sql`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_custom_sql` (
	`id` INTEGER UNSIGNED NOT NULL auto_increment,
	`name` varchar(150) NOT NULL default '',
	`sql` TEXT,
	PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(50)  NOT NULL,
	`id_group` INTEGER UNSIGNED NOT NULL,
	`background` varchar(200)  NOT NULL,
	`height` INTEGER UNSIGNED NOT NULL default 0,
	`width` INTEGER UNSIGNED NOT NULL default 0,
	PRIMARY KEY(`id`)
)  ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout_data` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_layout` INTEGER UNSIGNED NOT NULL default 0,
	`pos_x` INTEGER UNSIGNED NOT NULL default 0,
	`pos_y` INTEGER UNSIGNED NOT NULL default 0,
	`height` INTEGER UNSIGNED NOT NULL default 0,
	`width` INTEGER UNSIGNED NOT NULL default 0,
	`label` TEXT,
	`image` varchar(200) DEFAULT "",
	`type` tinyint(1) UNSIGNED NOT NULL default 0,
	`period` INTEGER UNSIGNED NOT NULL default 3600,
	`id_agente_modulo` mediumint(8) unsigned NOT NULL default '0',
	`id_agent` int(10) unsigned NOT NULL default 0,
	`id_layout_linked` INTEGER unsigned NOT NULL default '0',
	`parent_item` INTEGER UNSIGNED NOT NULL default 0,
	`enable_link` tinyint(1) UNSIGNED NOT NULL default 1,
	`id_metaconsole` int(10) NOT NULL default 0,
	`id_group` INTEGER UNSIGNED NOT NULL default 0,
	`id_custom_graph` INTEGER UNSIGNED NOT NULL default 0,
	`border_width` INTEGER UNSIGNED NOT NULL default 0,
	`border_color` varchar(200) DEFAULT "",
	`fill_color` varchar(200) DEFAULT "",
	PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tplugin`
-- ---------------------------------------------------------------------
-- The fields "net_dst_opt", "net_port_opt", "user_opt" and
-- "pass_opt" are deprecated for the 5.1.
CREATE TABLE IF NOT EXISTS `tplugin` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(200) NOT NULL,
	`description` mediumtext,
	`max_timeout` int(4) UNSIGNED NOT NULL default 0,
	`max_retries` int(4) UNSIGNED NOT NULL default 0,
	`execute` varchar(250) NOT NULL,
	`net_dst_opt` varchar(50) default '',
	`net_port_opt` varchar(50) default '',
	`user_opt` varchar(50) default '',
	`pass_opt` varchar(50) default '',
	`plugin_type` int(2) UNSIGNED NOT NULL default 0,
	`macros` text,
	`parameters` text,
	PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 

-- ---------------------------------------------------------------------
-- Table `tmodule`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule` (
	`id_module` int(11) unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	PRIMARY KEY (`id_module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tserver_export`
-- ---------------------------------------------------------------------
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
	`timezone_offset` TINYINT(2) NULL DEFAULT '0' COMMENT 'Number of hours of diference with the server timezone' ,
	PRIMARY KEY  (`id`),
	INDEX id_export_server (`id_export_server`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tserver_export_data`
-- ---------------------------------------------------------------------
-- id_export_server is real pandora fms export server process that manages this server
-- id is the "destination" server to export
CREATE TABLE IF NOT EXISTS `tserver_export_data` (
	`id` int(20) unsigned NOT NULL auto_increment,
	`id_export_server` int(10) unsigned default NULL,
	`agent_name` varchar(100) NOT NULL default '',
	`module_name` varchar(100) NOT NULL default '',
	`module_type` varchar(100) NOT NULL default '',
	`data` varchar(255) default NULL, 
	`timestamp` datetime NOT NULL default '1970-01-01 00:00:00',
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tplanned_downtime`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tplanned_downtime` (
	`id` MEDIUMINT( 8 ) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR( 100 ) NOT NULL,
	`description` TEXT NOT NULL,
	`date_from` bigint(20) NOT NULL default '0',
	`date_to` bigint(20) NOT NULL default '0',
	`executed` tinyint(1) UNSIGNED NOT NULL default 0,
	`id_group` mediumint(8) unsigned NULL default 0,
	`only_alerts` tinyint(1) UNSIGNED NOT NULL default 0,
	`monday` tinyint(1) default 0,
	`tuesday` tinyint(1) default 0,
	`wednesday` tinyint(1) default 0,
	`thursday` tinyint(1) default 0,
	`friday` tinyint(1) default 0,
	`saturday` tinyint(1) default 0,
	`sunday` tinyint(1) default 0,
	`periodically_time_from` time NULL default NULL,
	`periodically_time_to` time NULL default NULL,
	`periodically_day_from` int(100) unsigned default NULL,
	`periodically_day_to` int(100) unsigned default NULL,
	`type_downtime` varchar(100) NOT NULL default 'disabled_agents_alerts',
	`type_execution` varchar(100) NOT NULL default 'once',
	`type_periodicity` varchar(100) NOT NULL default 'weekly',
	`id_user` varchar(100) NOT NULL default '0',
	PRIMARY KEY (  `id` ) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tplanned_downtime_agents`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tplanned_downtime_agents` (
	`id` int(20) unsigned NOT NULL auto_increment,
	`id_agent` mediumint(8) unsigned NOT NULL default '0',
	`id_downtime` mediumint(8) NOT NULL default '0',
	`all_modules` tinyint(1) default 1,
	PRIMARY KEY  (`id`),
	FOREIGN KEY (`id_downtime`) REFERENCES tplanned_downtime(`id`)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tplanned_downtime_modules`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tplanned_downtime_modules` (
	`id` int(20) unsigned NOT NULL auto_increment,
	`id_agent` mediumint(8) unsigned NOT NULL default '0',
	`id_agent_module` int(10) NOT NULL, 
	`id_downtime` mediumint(8) NOT NULL default '0',
	PRIMARY KEY  (`id`),
	FOREIGN KEY (`id_downtime`) REFERENCES tplanned_downtime(`id`)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- GIS extension Tables
-- ----------------------------------------------------------------------
-- Table `tgis_data_history`
-- ----------------------------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_data_history` (
	`id_tgis_data` INT NOT NULL AUTO_INCREMENT COMMENT 'key of the table' ,
	`longitude` DOUBLE NOT NULL ,
	`latitude` DOUBLE NOT NULL ,
	`altitude` DOUBLE NULL ,
	`start_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'timestamp on wich the agente started to be in this position' ,
	`end_timestamp` TIMESTAMP NULL COMMENT 'timestamp on wich the agent was placed for last time on this position' ,
	`description` TEXT NULL COMMENT 'description of the region correoponding to this placemnt' ,
	`manual_placement` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 to show that the position cames from the agent, 1 to show that the position was established manualy' ,
	`number_of_packages` INT NOT NULL DEFAULT 1 COMMENT 'Number of data packages received with this position from the start_timestampa to the_end_timestamp' ,
	`tagente_id_agente` INT(10) UNSIGNED NOT NULL COMMENT 'reference to the agent' ,
	PRIMARY KEY (`id_tgis_data`) ,
	INDEX `start_timestamp_index` USING BTREE (`start_timestamp` ASC),
	INDEX `end_timestamp_index` USING BTREE (`end_timestamp` ASC) )
ENGINE = InnoDB
COMMENT = 'Table to store historical GIS information of the agents';


-- ----------------------------------------------------------------------
-- Table `tgis_data_status`
-- ----------------------------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_data_status` (
	`tagente_id_agente` INT(10) UNSIGNED NOT NULL COMMENT 'Reference to the agent' ,
	`current_longitude` DOUBLE NOT NULL COMMENT 'Last received longitude',
	`current_latitude` DOUBLE NOT NULL COMMENT 'Last received latitude',
	`current_altitude` DOUBLE NULL COMMENT 'Last received altitude',
	`stored_longitude` DOUBLE NOT NULL COMMENT 'Reference longitude to see if the agent has moved',
	`stored_latitude` DOUBLE NOT NULL COMMENT 'Reference latitude to see if the agent has moved',
	`stored_altitude` DOUBLE NULL COMMENT 'Reference altitude to see if the agent has moved',
	`number_of_packages` INT NOT NULL DEFAULT 1 COMMENT 'Number of data packages received with this position since start_timestampa' ,
	`start_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp on wich the agente started to be in this position' ,
	`manual_placement` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 to show that the position cames from the agent, 1 to show that the position was established manualy' ,
	`description` TEXT NULL COMMENT 'description of the region correoponding to this placemnt' ,
	PRIMARY KEY (`tagente_id_agente`) ,
	INDEX `start_timestamp_index` USING BTREE (`start_timestamp` ASC),
	INDEX `fk_tgisdata_tagente1` (`tagente_id_agente` ASC) ,
	CONSTRAINT `fk_tgisdata_tagente1`
		FOREIGN KEY (`tagente_id_agente` )
		REFERENCES `tagente` (`id_agente` )
		ON DELETE CASCADE
		ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Table to store last GIS information of the agents';

-- ----------------------------------------------------------------------
-- Table `tgis_map`
-- ----------------------------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map` (
	`id_tgis_map` INT NOT NULL AUTO_INCREMENT COMMENT 'table identifier' ,
	`map_name` VARCHAR(63) NOT NULL COMMENT 'Name of the map' ,
	`initial_longitude` DOUBLE NULL COMMENT "longitude of the center of the map when it\'s loaded" ,
	`initial_latitude` DOUBLE NULL COMMENT "latitude of the center of the map when it\'s loaded" ,
	`initial_altitude` DOUBLE NULL COMMENT "altitude of the center of the map when it\'s loaded" ,
	`zoom_level` TINYINT(2) NULL DEFAULT '1' COMMENT 'Zoom level to show when the map is loaded.' ,
	`map_background` VARCHAR(127) NULL COMMENT 'path on the server to the background image of the map' ,
	`default_longitude` DOUBLE NULL COMMENT 'default longitude for the agents placed on the map' ,
	`default_latitude` DOUBLE NULL COMMENT 'default latitude for the agents placed on the map' ,
	`default_altitude` DOUBLE NULL COMMENT 'default altitude for the agents placed on the map' ,
	`group_id` INT(10) NOT NULL DEFAULT 0 COMMENT 'Group that owns the map' ,
	`default_map` TINYINT(1) NULL DEFAULT 0 COMMENT '1 if this is the default map, 0 in other case',
	PRIMARY KEY (`id_tgis_map`),
	INDEX `map_name_index` (`map_name` ASC)
)
ENGINE = InnoDB
COMMENT = 'Table containing information about a gis map';

-- ---------------------------------------------------------------------
-- Table `tgis_map_connection`
-- ---------------------------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_connection` (
	`id_tmap_connection` INT NOT NULL AUTO_INCREMENT COMMENT 'table id' ,
	`conection_name` VARCHAR(45) NULL COMMENT 'Name of the connection (name of the base layer)' ,
	`connection_type` VARCHAR(45) NULL COMMENT 'Type of map server to connect' ,
	`conection_data` TEXT NULL COMMENT 'connection information (this can probably change to fit better the possible connection parameters)' ,
	`num_zoom_levels` TINYINT(2) NULL COMMENT 'Number of zoom levels available' ,
	`default_zoom_level` TINYINT(2) NOT NULL DEFAULT 16 COMMENT 'Default Zoom Level for the connection' ,
	`default_longitude` DOUBLE NULL COMMENT 'default longitude for the agents placed on the map' ,
	`default_latitude` DOUBLE NULL COMMENT 'default latitude for the agents placed on the map' ,
	`default_altitude` DOUBLE NULL COMMENT 'default altitude for the agents placed on the map' ,
	`initial_longitude` DOUBLE NULL COMMENT "longitude of the center of the map when it\'s loaded" ,
	`initial_latitude` DOUBLE NULL COMMENT "latitude of the center of the map when it\'s loaded" ,
	`initial_altitude` DOUBLE NULL COMMENT "altitude of the center of the map when it\'s loaded" ,
	`group_id` INT(10) NOT NULL DEFAULT 0 COMMENT 'Group that owns the map',
	PRIMARY KEY (`id_tmap_connection`) )
ENGINE = InnoDB
COMMENT = 'Table to store the map connection information';

-- -----------------------------------------------------
-- Table `tgis_map_has_tgis_map_connection`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_has_tgis_map_connection` (
	`tgis_map_id_tgis_map` INT NOT NULL COMMENT 'reference to tgis_map' ,
	`tgis_map_connection_id_tmap_connection` INT NOT NULL COMMENT 'reference to tgis_map_connection' ,
	`modification_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Modification Time of the Connection' ,
	`default_map_connection` TINYINT(1) NULL DEFAULT FALSE COMMENT 'Flag to mark the default map connection of a map' ,
	PRIMARY KEY (`tgis_map_id_tgis_map`, `tgis_map_connection_id_tmap_connection`) ,
	INDEX `fk_tgis_map_has_tgis_map_connection_tgis_map1` (`tgis_map_id_tgis_map` ASC) ,
	INDEX `fk_tgis_map_has_tgis_map_connection_tgis_map_connection1` (`tgis_map_connection_id_tmap_connection` ASC) ,
	CONSTRAINT `fk_tgis_map_has_tgis_map_connection_tgis_map1`
		FOREIGN KEY (`tgis_map_id_tgis_map` )
		REFERENCES `tgis_map` (`id_tgis_map` )
		ON DELETE CASCADE
		ON UPDATE NO ACTION,
	CONSTRAINT `fk_tgis_map_has_tgis_map_connection_tgis_map_connection1`
		FOREIGN KEY (`tgis_map_connection_id_tmap_connection` )
		REFERENCES `tgis_map_connection` (`id_tmap_connection` )
		ON DELETE CASCADE
		ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Table to asociate a connection to a gis map';

-- -----------------------------------------------------
-- Table `tgis_map_layer`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_layer` (
	`id_tmap_layer` INT NOT NULL AUTO_INCREMENT COMMENT 'table id' ,
	`layer_name` VARCHAR(45) NOT NULL COMMENT 'Name of the layer ' ,
	`view_layer` TINYINT(1) NOT NULL DEFAULT TRUE COMMENT 'True if the layer must be shown' ,
	`layer_stack_order` TINYINT(3) NULL DEFAULT 0 COMMENT 'Number of order of the layer in the layer stack, bigger means upper on the stack.\n' ,
	`tgis_map_id_tgis_map` INT NOT NULL COMMENT 'reference to the map containing the layer' ,
	`tgrupo_id_grupo` MEDIUMINT(4) NOT NULL COMMENT 'reference to the group shown in the layer' ,
	PRIMARY KEY (`id_tmap_layer`) ,
	INDEX `fk_tmap_layer_tgis_map1` (`tgis_map_id_tgis_map` ASC) ,
	CONSTRAINT `fk_tmap_layer_tgis_map1`
		FOREIGN KEY (`tgis_map_id_tgis_map` )
		REFERENCES `tgis_map` (`id_tgis_map` )
		ON DELETE CASCADE
		ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Table containing information about the map layers';

-- -----------------------------------------------------
-- Table `tgis_map_layer_has_tagente`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_layer_has_tagente` (
	`tgis_map_layer_id_tmap_layer` INT NOT NULL ,
	`tagente_id_agente` INT(10) UNSIGNED NOT NULL ,
	PRIMARY KEY (`tgis_map_layer_id_tmap_layer`, `tagente_id_agente`) ,
	INDEX `fk_tgis_map_layer_has_tagente_tgis_map_layer1` (`tgis_map_layer_id_tmap_layer` ASC) ,
	INDEX `fk_tgis_map_layer_has_tagente_tagente1` (`tagente_id_agente` ASC) ,
	CONSTRAINT `fk_tgis_map_layer_has_tagente_tgis_map_layer1`
		FOREIGN KEY (`tgis_map_layer_id_tmap_layer` )
		REFERENCES `tgis_map_layer` (`id_tmap_layer` )
		ON DELETE CASCADE
		ON UPDATE NO ACTION,
	CONSTRAINT `fk_tgis_map_layer_has_tagente_tagente1`
		FOREIGN KEY (`tagente_id_agente` )
		REFERENCES `tagente` (`id_agente` )
		ON DELETE CASCADE
		ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Table to define wich agents are shown in a layer';

-- ----------------------------------------------------------------------
-- Table `tgroup_stat`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgroup_stat` (
	`id_group` int(10) unsigned NOT NULL default '0',
	`modules` int(10) unsigned NOT NULL default '0',
	`normal` int(10) unsigned NOT NULL default '0',
	`critical` int(10) unsigned NOT NULL default '0',
	`warning` int(10) unsigned NOT NULL default '0',
	`unknown` int(10) unsigned NOT NULL default '0',
	`non-init` int(10) unsigned NOT NULL default '0',
	`alerts` int(10) unsigned NOT NULL default '0',
	`alerts_fired` int(10) unsigned NOT NULL default '0',
	`agents` int(10) unsigned NOT NULL default '0',
	`agents_unknown` int(10) unsigned NOT NULL default '0',
	`utimestamp` int(20) unsigned NOT NULL default 0,
	PRIMARY KEY  (`id_group`)
) ENGINE=InnoDB 
COMMENT = 'Table to store global system stats per group' 
DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnetwork_map`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_map` (
	`id_networkmap` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_user` VARCHAR(60)  NOT NULL,
	`name` VARCHAR(100)  NOT NULL,
	`type` VARCHAR(20)  NOT NULL,
	`layout` VARCHAR(20)  NOT NULL,
	`nooverlap` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`simple` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`regenerate` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
	`font_size` INT UNSIGNED NOT NULL DEFAULT 12,
	`id_group` INT  NOT NULL DEFAULT 0,
	`id_module_group` INT  NOT NULL DEFAULT 0,  
	`id_policy` INT  NOT NULL DEFAULT 0,
	`depth` VARCHAR(20)  NOT NULL,
	`only_modules_with_alerts` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`hide_policy_modules` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`zoom` FLOAT UNSIGNED NOT NULL DEFAULT 1,
	`distance_nodes` FLOAT UNSIGNED NOT NULL DEFAULT 2.5,
	`center` INT UNSIGNED NOT NULL DEFAULT 0,
	`contracted_nodes` TEXT,
	`show_snmp_modules` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`text_filter` VARCHAR(100)  NOT NULL DEFAULT "",
	`dont_show_subgroups` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`pandoras_children` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`show_groups` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`show_modules` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`id_agent` INT  NOT NULL DEFAULT 0,
	`server_name` VARCHAR(100)  NOT NULL,
	`show_modulegroup` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	`l2_network` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id_networkmap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tsnmp_filter`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tsnmp_filter` (
	`id_snmp_filter` int(10) unsigned NOT NULL auto_increment,
	`description` varchar(255) default '',
	`filter` varchar(255) default '',
	PRIMARY KEY  (`id_snmp_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tagent_custom_fields`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_custom_fields` (
	`id_field` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(45) NOT NULL default '',
	`display_on_front` tinyint(1) NOT NULL default 0,
	PRIMARY KEY  (`id_field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tagent_custom_data`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_custom_data` (
	`id_field` int(10) unsigned NOT NULL,
	`id_agent` int(10) unsigned NOT NULL,
	`description` text,
	FOREIGN KEY (`id_field`) REFERENCES tagent_custom_fields(`id_field`)
		ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`id_agent`) REFERENCES tagente(`id_agente`)
		ON UPDATE CASCADE ON DELETE CASCADE,
	PRIMARY KEY  (`id_field`, `id_agent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `ttag`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttag` ( 
	`id_tag` integer(10) unsigned NOT NULL auto_increment, 
	`name` varchar(100) NOT NULL default '', 
	`description` text NOT NULL, 
	`url` mediumtext NOT NULL, 
	`email` text NULL,
	`phone` text NULL,
	PRIMARY KEY  (`id_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

-- -----------------------------------------------------
-- Table `ttag_module`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttag_module` (
	`id_tag` int(10) NOT NULL,
	`id_agente_modulo` int(10) NOT NULL DEFAULT 0,
	`id_policy_module` int(10) NOT NULL DEFAULT 0,
	PRIMARY KEY  (id_tag, id_agente_modulo),
	KEY `idx_id_agente_modulo` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

-- ---------------------------------------------------------------------
-- Table `ttag_policy_module`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttag_policy_module` ( 
	`id_tag` int(10) NOT NULL, 
	`id_policy_module` int(10) NOT NULL DEFAULT 0, 
	PRIMARY KEY  (id_tag, id_policy_module),
	KEY `idx_id_policy_module` (`id_policy_module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

-- ---------------------------------------------------------------------
-- Table `tnetflow_filter`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetflow_filter` (
	`id_sg`  int(10) unsigned NOT NULL auto_increment,
	`id_name` varchar(600) NOT NULL default '0',
	`id_group` int(10),
	`ip_dst` TEXT NOT NULL,
	`ip_src` TEXT NOT NULL,
	`dst_port` TEXT NOT NULL,
	`src_port` TEXT NOT NULL,
	`advanced_filter` TEXT NOT NULL,
	`filter_args` TEXT NOT NULL,
	`aggregate` varchar(60),
	`output` varchar(60),
	PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tnetflow_report`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetflow_report` (
	`id_report` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
	`id_name` varchar(150) NOT NULL default '',
	`description` TEXT NOT NULL,
	`id_group` int(10),
	`server_name` TEXT NOT NULL,
	PRIMARY KEY(`id_report`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tnetflow_report_content`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetflow_report_content` (
	`id_rc` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_report` INTEGER UNSIGNED NOT NULL default 0,
	`id_filter` INTEGER UNSIGNED NOT NULL default 0,
	`description` TEXT NOT NULL,
	`date` bigint(20) NOT NULL default '0',
	`period` int(11) NOT NULL default 0,
	`max` int (11) NOT NULL default 0,
	`show_graph` varchar(60),
	`order` int (11) NOT NULL default 0,
	PRIMARY KEY(`id_rc`),
	FOREIGN KEY (`id_report`) REFERENCES tnetflow_report(`id_report`)
	ON DELETE CASCADE,
	FOREIGN KEY (`id_filter`) REFERENCES tnetflow_filter(`id_sg`)
	ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_filter` (
	`id_filter`  int(10) unsigned NOT NULL auto_increment,
	`id_group_filter` int(10) NOT NULL default 0,
	`id_name` varchar(600) NOT NULL,
	`id_group` int(10) NOT NULL default 0,
	`event_type` text NOT NULL,
	`severity` int(10) NOT NULL default -1,
	`status` int(10) NOT NULL default -1,
	`search` TEXT,
	`text_agent` TEXT, 
	`pagination` int(10) NOT NULL default 25,
	`event_view_hr` int(10) NOT NULL default 8,
	`id_user_ack` TEXT,
	`group_rep` int(10) NOT NULL default 0,
	`tag_with` text NOT NULL,
	`tag_without` text NOT NULL,
	`filter_only_alert` int(10) NOT NULL default -1, 
	PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tpassword_history`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpassword_history` (
	`id_pass`  int(10) unsigned NOT NULL auto_increment,
	`id_user` varchar(60) NOT NULL,
	`password` varchar(45) default NULL,
	`date_begin` DATETIME  NOT NULL DEFAULT 0,
	`date_end` DATETIME  NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id_pass`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tevent_response`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_response` (
	`id`  int(10) unsigned NOT NULL auto_increment,
	`name` varchar(600) NOT NULL default '',
	`description` TEXT NOT NULL,
	`target` TEXT NOT NULL,
	`type` varchar(60) NOT NULL,
	`id_group` MEDIUMINT(4) NOT NULL default 0,
	`modal_width` INTEGER  NOT NULL DEFAULT 0,
	`modal_height` INTEGER  NOT NULL DEFAULT 0,
	`new_window` TINYINT(4)  NOT NULL DEFAULT 0,
	`params` TEXT  NOT NULL,
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tcategory`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcategory` ( 
	`id` int(10) unsigned NOT NULL auto_increment, 
	`name` varchar(600) NOT NULL default '', 
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

-- ---------------------------------------------------------------------
-- Table `tupdate_settings`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tupdate_settings` ( 
	`key` varchar(255) default '', 
	`value` varchar(255) default '', PRIMARY KEY (`key`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tupdate_package`
-- ---------------------------------------------------------------------
CREATE TABLE `tupdate_package` (  
	id int(11) unsigned NOT NULL auto_increment,  
	timestamp datetime NOT NULL,  
	description varchar(255) default '',  PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tupdate`
-- ---------------------------------------------------------------------
CREATE TABLE `tupdate` (  
	id int(11) unsigned NOT NULL auto_increment,  
	type enum('code', 'db_data', 'db_schema', 'binary'),  
	id_update_package int(11) unsigned NOT NULL default 0,  
	filename  varchar(250) default '',  
	checksum  varchar(250) default '',  
	previous_checksum  varchar(250) default '',  
	svn_version int(4) unsigned NOT NULL default 0,  
	data LONGTEXT,  
	data_rollback LONGTEXT,  
	description TEXT,  
	db_table_name varchar(140) default '',  
	db_field_name varchar(140) default '',  
	db_field_value varchar(1024) default '',  PRIMARY KEY  (`id`),  
	FOREIGN KEY (`id_update_package`) REFERENCES tupdate_package(`id`)   ON UPDATE CASCADE ON DELETE CASCADE 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tupdate_journal`
-- ---------------------------------------------------------------------
CREATE TABLE `tupdate_journal` (  
	id int(11) unsigned NOT NULL auto_increment,  
	id_update int(11) unsigned NOT NULL default 0,  PRIMARY KEY  (`id`),  
	FOREIGN KEY (`id_update`) REFERENCES tupdate(`id`)   ON UPDATE CASCADE ON DELETE CASCADE 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `talert_snmp_action`
-- ---------------------------------------------------------------------
CREATE TABLE  IF NOT EXISTS  `talert_snmp_action` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_alert_snmp` int(10) unsigned NOT NULL default '0',
	`alert_type` int(2) unsigned NOT NULL default '0',
	`al_field1` text NOT NULL,
	`al_field2` text NOT NULL,
	`al_field3` text NOT NULL,
	`al_field4` text NOT NULL,
	`al_field5` text NOT NULL,
	`al_field6` text NOT NULL,
	`al_field7` text NOT NULL,
	`al_field8` text NOT NULL,
	`al_field9` text NOT NULL,
	`al_field10` text NOT NULL,
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
