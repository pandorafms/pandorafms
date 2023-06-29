-- Pandora FMS - the Flexible Monitoring System
-- ============================================
-- Copyright (c) 2005-2021 Artica Soluciones Tecnológicas, http://www.artica.es
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
  `id_a` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(60) NOT NULL DEFAULT '',
  `ip_pack` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_a`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `taddress_agent`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `taddress_agent` (
  `id_ag` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_a` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_ag`),
  INDEX `taddress_agent_agent` (`id_agent`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tagente`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente` (
  `id_agente` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(600) NOT NULL DEFAULT '',
  `direccion` VARCHAR(100) DEFAULT NULL,
  `comentarios` VARCHAR(255) DEFAULT '',
  `id_grupo` INT UNSIGNED NOT NULL DEFAULT 0,
  `ultimo_contacto` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `modo` TINYINT NOT NULL DEFAULT 0,
  `intervalo` INT UNSIGNED NOT NULL DEFAULT 300,
  `id_os` INT UNSIGNED DEFAULT 0,
  `os_version` VARCHAR(100) DEFAULT '',
  `agent_version` VARCHAR(100) DEFAULT '',
  `ultimo_contacto_remoto` DATETIME DEFAULT '1970-01-01 00:00:00',
  `disabled` TINYINT NOT NULL DEFAULT 0,
  `remote` TINYINT NOT NULL DEFAULT 0,
  `id_parent` INT UNSIGNED DEFAULT 0,
  `custom_id` VARCHAR(255) DEFAULT '',
  `server_name` VARCHAR(100) DEFAULT '',
  `cascade_protection` TINYINT NOT NULL DEFAULT 0,
  `cascade_protection_module` INT UNSIGNED NOT NULL DEFAULT 0,
  `timezone_offset` TINYINT NULL DEFAULT 0 COMMENT 'nuber of hours of diference with the server timezone',
  `icon_path` VARCHAR(127) NULL DEFAULT NULL COMMENT 'path in the server to the image of the icon representing the agent' ,
  `update_gis_data` TINYINT NOT NULL DEFAULT 1 COMMENT 'set it to one to update the position data (altitude, longitude, latitude) when getting information from the agent or to 0 to keep the last value and do not update it',
  `url_address` MEDIUMTEXT NULL,
  `quiet` TINYINT NOT NULL DEFAULT 0,
  `normal_count` BIGINT NOT NULL DEFAULT 0,
  `warning_count` BIGINT NOT NULL DEFAULT 0,
  `critical_count` BIGINT NOT NULL DEFAULT 0,
  `unknown_count` BIGINT NOT NULL DEFAULT 0,
  `notinit_count` BIGINT NOT NULL DEFAULT 0,
  `total_count` BIGINT NOT NULL DEFAULT 0,
  `fired_count` BIGINT NOT NULL DEFAULT 0,
  `update_module_count` TINYINT NOT NULL DEFAULT 0,
  `update_alert_count` TINYINT NOT NULL DEFAULT 0,
  `update_secondary_groups` TINYINT NOT NULL DEFAULT 0,
  `alias` VARCHAR(600) NOT NULL DEFAULT '',
  `transactional_agent` TINYINT NOT NULL DEFAULT 0,
  `alias_as_name` TINYINT NOT NULL DEFAULT 0,
  `safe_mode_module` INT UNSIGNED NOT NULL DEFAULT 0,
  `cps` INT NOT NULL DEFAULT 0,
  `satellite_server` INT NOT NULL DEFAULT 0,
  `fixed_ip` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_agente`),
  KEY `nombre` (`nombre`(255)),
  KEY `direccion` (`direccion`),
  KEY `disabled` (`disabled`),
  KEY `id_grupo` (`id_grupo`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4 ;

-- ---------------------------------------------------------------------
-- Table `tagente_datos`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos` (
  `id_agente_modulo` INT UNSIGNED NOT NULL DEFAULT 0,
  `datos` DOUBLE DEFAULT NULL,
  `utimestamp` BIGINT DEFAULT 0,
  KEY `data_index1` (`id_agente_modulo`, `utimestamp`),
  KEY `idx_utimestamp` USING BTREE (`utimestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4 ;

-- ---------------------------------------------------------------------
-- Table `tagente_datos_inc`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos_inc` (
  `id_agente_modulo` INT UNSIGNED NOT NULL DEFAULT 0,
  `datos` DOUBLE DEFAULT NULL,
  `utimestamp` INT UNSIGNED DEFAULT 0,
  KEY `data_inc_index_1` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tagente_datos_string`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos_string` (
  `id_agente_modulo` INT UNSIGNED NOT NULL DEFAULT 0,
  `datos` MEDIUMTEXT,
  `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  KEY `data_string_index_1` (`id_agente_modulo`, `utimestamp`),
  KEY `idx_utimestamp` USING BTREE (`utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tagente_datos_log4x`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos_log4x` (
  `id_tagente_datos_log4x` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agente_modulo` INT UNSIGNED NOT NULL DEFAULT 0,
  
  `severity` TEXT,
  `message` TEXT,
  `stacktrace` TEXT,
  
  `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_tagente_datos_log4x`),
  KEY `data_log4x_index_1` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tagente_estado`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_estado` (
  `id_agente_estado` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agente_modulo` INT NOT NULL DEFAULT 0,
  `datos` MEDIUMTEXT,
  `timestamp` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `estado` INT NOT NULL DEFAULT 0,
  `known_status` TINYINT DEFAULT 0,
  `id_agente` INT NOT NULL DEFAULT 0,
  `last_try` DATETIME DEFAULT NULL,
  `utimestamp` BIGINT NOT NULL DEFAULT 0,
  `current_interval` INT UNSIGNED NOT NULL DEFAULT 0,
  `running_by` SMALLINT UNSIGNED DEFAULT 0,
  `last_execution_try` BIGINT NOT NULL DEFAULT 0,
  `status_changes` TINYINT UNSIGNED DEFAULT 0,
  `last_status` TINYINT DEFAULT 0,
  `last_known_status` TINYINT DEFAULT 0,
  `last_error` INT NOT NULL DEFAULT 0,
  `ff_start_utimestamp` BIGINT DEFAULT 0,
  `ff_normal` INT UNSIGNED DEFAULT 0,
  `ff_warning` INT UNSIGNED DEFAULT 0,
  `ff_critical` INT UNSIGNED DEFAULT 0,
  `last_dynamic_update` BIGINT NOT NULL DEFAULT 0,
  `last_unknown_update` BIGINT NOT NULL DEFAULT 0,
  `last_status_change` BIGINT NOT NULL DEFAULT 0,
  `warning_count` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY  (`id_agente_estado`),
  KEY `status_index_1` (`id_agente_modulo`),
  KEY `idx_agente` (`id_agente`),
  KEY `running_by` (`running_by`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;
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
  `id_agente_modulo` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agente` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_tipo_modulo` SMALLINT NOT NULL DEFAULT 0,
  `descripcion` TEXT,
  `extended_info` TEXT,
  `nombre` TEXT,
  `unit` TEXT,
  `id_policy_module` INT UNSIGNED NOT NULL DEFAULT 0,
  `max` BIGINT DEFAULT 0,
  `min` BIGINT DEFAULT 0,
  `module_interval` INT UNSIGNED DEFAULT 0,
  `cron_interval` VARCHAR(100) DEFAULT '',
  `module_ff_interval` INT UNSIGNED DEFAULT 0,
  `tcp_port` INT UNSIGNED DEFAULT 0,
  `tcp_send` TEXT,
  `tcp_rcv` TEXT,
  `snmp_community` VARCHAR(100) DEFAULT '',
  `snmp_oid` VARCHAR(255) DEFAULT '0',
  `ip_target` VARCHAR(100) DEFAULT '',
  `id_module_group` INT UNSIGNED DEFAULT 0,
  `flag` TINYINT UNSIGNED DEFAULT 1,
  `id_modulo` INT UNSIGNED DEFAULT 0,
  `disabled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_export` SMALLINT UNSIGNED DEFAULT 0,
  `plugin_user` TEXT,
  `plugin_pass` TEXT,
  `plugin_parameter` TEXT,
  `id_plugin` INT DEFAULT 0,
  `post_process` DOUBLE DEFAULT 0,
  `prediction_module` BIGINT DEFAULT 0,
  `max_timeout` INT UNSIGNED DEFAULT 0,
  `max_retries` INT UNSIGNED DEFAULT 0,
  `custom_id` VARCHAR(255) DEFAULT '',
  `history_data` TINYINT UNSIGNED DEFAULT 1,
  `min_warning` DOUBLE DEFAULT 0,
  `max_warning` DOUBLE DEFAULT 0,
  `str_warning` TEXT,
  `min_critical` DOUBLE DEFAULT 0,
  `max_critical` DOUBLE DEFAULT 0,
  `str_critical` TEXT,
  `min_ff_event` INT UNSIGNED DEFAULT 0,
  `delete_pending` INT UNSIGNED DEFAULT 0,
  `policy_linked` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `policy_adopted` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `custom_string_1` MEDIUMTEXT,
  `custom_string_2` TEXT,
  `custom_string_3` TEXT,
  `custom_integer_1` INT DEFAULT 0,
  `custom_integer_2` INT DEFAULT 0,
  `wizard_level` ENUM('basic','advanced','nowizard') DEFAULT 'nowizard',
  `macros` TEXT,
  `critical_instructions` TEXT,
  `warning_instructions` TEXT,
  `unknown_instructions` TEXT,
  `quiet` TINYINT NOT NULL DEFAULT 0,
  `critical_inverse` TINYINT UNSIGNED DEFAULT 0,
  `warning_inverse` TINYINT UNSIGNED DEFAULT 0,
  `id_category` INT DEFAULT 0,
  `disabled_types_event` TEXT,
  `module_macros` TEXT,
  `min_ff_event_normal` INT UNSIGNED DEFAULT 0,
  `min_ff_event_warning` INT UNSIGNED DEFAULT 0,
  `min_ff_event_critical` INT UNSIGNED DEFAULT 0,
  `ff_type` TINYINT UNSIGNED DEFAULT 0,
  `each_ff` TINYINT UNSIGNED DEFAULT 0,
  `ff_timeout` INT UNSIGNED DEFAULT 0,
  `dynamic_interval` INT UNSIGNED DEFAULT 0,
  `dynamic_max` INT DEFAULT 0,
  `dynamic_min` INT DEFAULT 0,
  `dynamic_next` BIGINT NOT NULL DEFAULT 0,
  `dynamic_two_tailed` TINYINT UNSIGNED DEFAULT 0,
  `prediction_sample_window` INT DEFAULT 0,
  `prediction_samples` INT DEFAULT 0,
  `prediction_threshold` INT DEFAULT 0,
  `parent_module_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `cps` INT NOT NULL DEFAULT 0,
  `debug_content` TEXT,
  `percentage_critical` TINYINT UNSIGNED DEFAULT 0,
  `percentage_warning` TINYINT UNSIGNED DEFAULT 0,
  `warning_time` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY  (`id_agente_modulo`),
  KEY `main_idx` (`id_agente_modulo`,`id_agente`),
  KEY `tam_agente` (`id_agente`),
  KEY `id_tipo_modulo` (`id_tipo_modulo`),
  KEY `disabled` (`disabled`),
  KEY `module` (`id_modulo`),
  KEY `nombre` (`nombre` (255)),
  KEY `module_group` (`id_module_group`) using btree
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;
-- snmp_oid is also used for WMI query

-- -----------------------------------------------------
-- Table `tagent_access`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_access` (
  `id_agent` INT UNSIGNED NOT NULL DEFAULT 0,
  `utimestamp` BIGINT NOT NULL DEFAULT 0,
  KEY `agent_index` (`id_agent`),
  KEY `idx_utimestamp` USING BTREE (`utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `talert_snmp`
-- -----------------------------------------------------
CREATE TABLE  IF NOT EXISTS  `talert_snmp` (
  `id_as` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_alert` INT UNSIGNED NOT NULL DEFAULT 0,
  `al_field1` TEXT,
  `al_field2` TEXT,
  `al_field3` TEXT,
  `al_field4` TEXT,
  `al_field5` TEXT,
  `al_field6` TEXT,
  `al_field7` TEXT,
  `al_field8` TEXT,
  `al_field9` TEXT,
  `al_field10` TEXT,
  `al_field11` TEXT,
  `al_field12` TEXT,
  `al_field13` TEXT,
  `al_field14` TEXT,
  `al_field15` TEXT,
  `al_field16` TEXT,
  `al_field17` TEXT,
  `al_field18` TEXT,
  `al_field19` TEXT,
  `al_field20` TEXT,
  `description` VARCHAR(255) DEFAULT '',
  `alert_type` INT UNSIGNED NOT NULL DEFAULT 0,
  `agent` VARCHAR(100) DEFAULT '',
  `custom_oid` TEXT,
  `oid` VARCHAR(255) NOT NULL DEFAULT '',
  `time_threshold` INT NOT NULL DEFAULT 0,
  `times_fired` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_fired` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `max_alerts` INT NOT NULL DEFAULT 1,
  `min_alerts` INT NOT NULL DEFAULT 1,
  `internal_counter` INT UNSIGNED NOT NULL DEFAULT 0,
  `priority` TINYINT DEFAULT 0,
  `_snmp_f1_` TEXT, 
  `_snmp_f2_` TEXT, 
  `_snmp_f3_` TEXT,
  `_snmp_f4_` TEXT, 
  `_snmp_f5_` TEXT, 
  `_snmp_f6_` TEXT,
  `_snmp_f7_` TEXT,
  `_snmp_f8_` TEXT,
  `_snmp_f9_` TEXT,
  `_snmp_f10_` TEXT,
  `_snmp_f11_` TEXT,
  `_snmp_f12_` TEXT,
  `_snmp_f13_` TEXT,
  `_snmp_f14_` TEXT,
  `_snmp_f15_` TEXT,
  `_snmp_f16_` TEXT,
  `_snmp_f17_` TEXT,
  `_snmp_f18_` TEXT,
  `_snmp_f19_` TEXT,
  `_snmp_f20_` TEXT,
  `trap_type` INT NOT NULL DEFAULT -1,
  `single_value` VARCHAR(255) DEFAULT '', 
  `position` INT UNSIGNED NOT NULL DEFAULT 0,
  `disable_event` TINYINT DEFAULT 0,
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `order_1` INT UNSIGNED NOT NULL DEFAULT 1,
  `order_2` INT UNSIGNED NOT NULL DEFAULT 2,
  `order_3` INT UNSIGNED NOT NULL DEFAULT 3,
  `order_4` INT UNSIGNED NOT NULL DEFAULT 4,
  `order_5` INT UNSIGNED NOT NULL DEFAULT 5,
  `order_6` INT UNSIGNED NOT NULL DEFAULT 6,
  `order_7` INT UNSIGNED NOT NULL DEFAULT 7,
  `order_8` INT UNSIGNED NOT NULL DEFAULT 8,
  `order_9` INT UNSIGNED NOT NULL DEFAULT 9,
  `order_10` INT UNSIGNED NOT NULL DEFAULT 10,
  `order_11` INT UNSIGNED NOT NULL DEFAULT 11,
  `order_12` INT UNSIGNED NOT NULL DEFAULT 12,
  `order_13` INT UNSIGNED NOT NULL DEFAULT 13,
  `order_14` INT UNSIGNED NOT NULL DEFAULT 14,
  `order_15` INT UNSIGNED NOT NULL DEFAULT 15,
  `order_16` INT UNSIGNED NOT NULL DEFAULT 16,
  `order_17` INT UNSIGNED NOT NULL DEFAULT 17,
  `order_18` INT UNSIGNED NOT NULL DEFAULT 18,
  `order_19` INT UNSIGNED NOT NULL DEFAULT 19,
  `order_20` INT UNSIGNED NOT NULL DEFAULT 20,
  PRIMARY KEY  (`id_as`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `talert_commands`
-- -----------------------------------------------------
CREATE TABLE  IF NOT EXISTS `talert_commands` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `command` TEXT,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `description` TEXT,
  `internal` TINYINT DEFAULT 0,
  `fields_descriptions` TEXT,
  `fields_values` TEXT,
  `fields_hidden` TEXT,
  `previous_name` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `talert_actions`
-- -----------------------------------------------------
CREATE TABLE  IF NOT EXISTS `talert_actions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT,
  `id_alert_command` INT UNSIGNED NULL DEFAULT 0,
  `field1` TEXT,
  `field2` TEXT,
  `field3` TEXT,
  `field4` TEXT,
  `field5` TEXT,
  `field6` TEXT,
  `field7` TEXT,
  `field8` TEXT,
  `field9` TEXT,
  `field10` TEXT,
  `field11` TEXT,
  `field12` TEXT,
  `field13` TEXT,
  `field14` TEXT,
  `field15` TEXT,
  `field16` TEXT,
  `field17` TEXT,
  `field18` TEXT,
  `field19` TEXT,
  `field20` TEXT,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `action_threshold` INT NOT NULL DEFAULT 0,
  `field1_recovery` TEXT,
  `field2_recovery` TEXT,
  `field3_recovery` TEXT,
  `field4_recovery` TEXT,
  `field5_recovery` TEXT,
  `field6_recovery` TEXT,
  `field7_recovery` TEXT,
  `field8_recovery` TEXT,
  `field9_recovery` TEXT,
  `field10_recovery` TEXT,
  `field11_recovery` TEXT,
  `field12_recovery` TEXT,
  `field13_recovery` TEXT,
  `field14_recovery` TEXT,
  `field15_recovery` TEXT,
  `field16_recovery` TEXT,
  `field17_recovery` TEXT,
  `field18_recovery` TEXT,
  `field19_recovery` TEXT,
  `field20_recovery` TEXT,
  `previous_name` TEXT,
  `create_wu_integria` TINYINT DEFAULT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_alert_command`) REFERENCES talert_commands(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `talert_templates`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT,
  `description` MEDIUMTEXT,
  `id_alert_action` INT UNSIGNED NULL,
  `field1` TEXT,
  `field2` TEXT,
  `field3` TEXT,
  `field4` TEXT,
  `field5` TEXT,
  `field6` TEXT,
  `field7` TEXT,
  `field8` TEXT,
  `field9` TEXT,
  `field10` TEXT,
  `field11` TEXT,
  `field12` TEXT,
  `field13` TEXT,
  `field14` TEXT,
  `field15` TEXT,
  `field16` TEXT,
  `field17` TEXT,
  `field18` TEXT,
  `field19` TEXT,
  `field20` TEXT,
  `type` ENUM ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal', 'warning', 'critical', 'onchange', 'unknown', 'always', 'not_normal'),
  `value` VARCHAR(255) DEFAULT '',
  `matches_value` TINYINT DEFAULT 0,
  `max_value` DOUBLE DEFAULT NULL,
  `min_value` DOUBLE DEFAULT NULL,
  `time_threshold` INT NOT NULL DEFAULT 0,
  `max_alerts` INT UNSIGNED NOT NULL DEFAULT 1,
  `min_alerts` INT UNSIGNED NOT NULL DEFAULT 0,
  `time_from` time DEFAULT '00:00:00',
  `time_to` time DEFAULT '00:00:00',
  `monday` TINYINT DEFAULT 1,
  `tuesday` TINYINT DEFAULT 1,
  `wednesday` TINYINT DEFAULT 1,
  `thursday` TINYINT DEFAULT 1,
  `friday` TINYINT DEFAULT 1,
  `saturday` TINYINT DEFAULT 1,
  `sunday` TINYINT DEFAULT 1,
  `recovery_notify` TINYINT DEFAULT 0,
  `field1_recovery` TEXT,
  `field2_recovery` TEXT,
  `field3_recovery` TEXT,
  `field4_recovery` TEXT,
  `field5_recovery` TEXT,
  `field6_recovery` TEXT,
  `field7_recovery` TEXT,
  `field8_recovery` TEXT,
  `field9_recovery` TEXT,
  `field10_recovery` TEXT,
  `field11_recovery` TEXT,
  `field12_recovery` TEXT,
  `field13_recovery` TEXT,
  `field14_recovery` TEXT,
  `field15_recovery` TEXT,
  `field16_recovery` TEXT,
  `field17_recovery` TEXT,
  `field18_recovery` TEXT,
  `field19_recovery` TEXT,
  `field20_recovery` TEXT,
  `priority` TINYINT DEFAULT 0,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `special_day` TINYINT DEFAULT 0,
  `wizard_level` ENUM('basic','advanced','nowizard') DEFAULT 'nowizard',
  `min_alerts_reset_counter` TINYINT DEFAULT 0,
  `disable_event` TINYINT DEFAULT 0,
  `previous_name` TEXT,
  `schedule` TEXT,
  PRIMARY KEY  (`id`),
  KEY `idx_template_action` (`id_alert_action`),
  FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `talert_template_modules`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_template_modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agent_module` INT UNSIGNED NOT NULL,
  `id_alert_template` INT UNSIGNED NOT NULL,
  `id_policy_alerts` INT UNSIGNED NOT NULL DEFAULT 0,
  `internal_counter` INT DEFAULT 0,
  `last_fired` BIGINT NOT NULL DEFAULT 0,
  `last_reference` BIGINT NOT NULL DEFAULT 0,
  `times_fired` INT NOT NULL DEFAULT 0,
  `disabled` TINYINT DEFAULT 0,
  `standby` TINYINT DEFAULT 0,
  `priority` TINYINT DEFAULT 0,
  `force_execution` TINYINT DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_template_module` (`id_agent_module`),
  FOREIGN KEY (`id_agent_module`) REFERENCES tagente_modulo(`id_agente_modulo`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_alert_template`) REFERENCES talert_templates(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE (`id_agent_module`, `id_alert_template`, `id_policy_alerts`),
  INDEX force_execution (`force_execution`),
  INDEX idx_disabled (disabled)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `talert_template_module_actions`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_template_module_actions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_alert_template_module` INT UNSIGNED NOT NULL,
  `id_alert_action` INT UNSIGNED NOT NULL,
  `fires_min` INT UNSIGNED DEFAULT 0,
  `fires_max` INT UNSIGNED DEFAULT 0,
  `module_action_threshold` INT NOT NULL DEFAULT 0,
  `last_execution` BIGINT NOT NULL DEFAULT 0,
  `recovered` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_alert_template_module`) REFERENCES talert_template_modules(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `talert_calendar`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_calendar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `id_group` INT NOT NULL DEFAULT 0,
  `description` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `talert_special_days`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_special_days` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_calendar` INT UNSIGNED NOT NULL DEFAULT 1,
  `id_group` INT NOT NULL DEFAULT 0,
  `date` date NOT NULL DEFAULT '1970-01-01',
  `day_code` TINYINT NOT NULL,
  `description` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_calendar`) REFERENCES talert_calendar(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `talert_execution_queue`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_execution_queue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `data` LONGTEXT,
  `utimestamp` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tattachment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tattachment` (
  `id_attachment` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_incidencia` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_usuario` VARCHAR(255) NOT NULL DEFAULT '',
  `filename` VARCHAR(255) NOT NULL DEFAULT '',
  `description` VARCHAR(150) DEFAULT '',
  `size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_attachment`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tconfig` (
  `id_config` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(100) NOT NULL DEFAULT '',
  `value` TEXT,
  PRIMARY KEY  (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tconfig_os`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS  `tconfig_os` (
  `id_os` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `description` VARCHAR(250) DEFAULT '',
  `icon_name` VARCHAR(100) DEFAULT '',
  `previous_name` TEXT NULL,
  PRIMARY KEY  (`id_os`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tcontainer`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcontainer` (
  `id_container` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `parent` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `disabled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0, 
  `description` TEXT,
   PRIMARY KEY  (`id_container`),
   KEY `parent_index` (`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tcontainer_item`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcontainer_item` (
  `id_ci` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_container` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `type` VARCHAR(30) DEFAULT 'simple_graph',
  `id_agent` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent_module` BIGINT UNSIGNED NULL DEFAULT NULL,
  `time_lapse` INT NOT NULL DEFAULT 0,
  `id_graph` INT UNSIGNED DEFAULT 0,
  `only_average` TINYINT UNSIGNED DEFAULT 0 NOT NULL,
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_module_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `agent` VARCHAR(100) NOT NULL DEFAULT '',
  `module` VARCHAR(100) NOT NULL DEFAULT '',
  `id_tag` INT UNSIGNED NOT NULL DEFAULT 0,
  `type_graph` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `fullscale` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`id_ci`),
  FOREIGN KEY (`id_container`) REFERENCES `tcontainer`(`id_container`)
  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tevento`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevento` (
  `id_evento` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agente` INT NOT NULL DEFAULT 0,
  `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0',
  `id_grupo` MEDIUMINT NOT NULL DEFAULT 0,
  `estado` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `timestamp` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `evento` TEXT,
  `utimestamp` BIGINT NOT NULL DEFAULT 0,
  `event_type` ENUM('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change', 'ncm') DEFAULT 'unknown',
  `id_agentmodule` INT NOT NULL DEFAULT 0,
  `id_alert_am` INT NOT NULL DEFAULT 0,
  `criticity` INT UNSIGNED NOT NULL DEFAULT 0,
  `user_comment` TEXT,
  `tags` TEXT,
  `source` TINYTEXT,
  `id_extra` TINYTEXT,
  `critical_instructions` TEXT,
  `warning_instructions` TEXT,
  `unknown_instructions` TEXT,
  `owner_user` VARCHAR(100) NOT NULL DEFAULT '',
  `ack_utimestamp` BIGINT NOT NULL DEFAULT 0,
  `custom_data` TEXT,
  `data` TINYTEXT,
  `module_status` INT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_evento`),
  KEY `idx_agente` (`id_agente`),
  KEY `idx_agentmodule` (`id_agentmodule`),
  KEY `idx_utimestamp` USING BTREE (`utimestamp`),
  INDEX `agente_modulo_estado`(`estado`, `id_agentmodule`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
-- Criticity: 0 - Maintance (grey)
-- Criticity: 1 - Informational (blue)
-- Criticity: 2 - Normal (green) (status 0)
-- Criticity: 3 - Warning (yellow) (status 2)
-- Criticity: 4 - Critical (red) (status 1)

-- ---------------------------------------------------------------------
-- Table `tevent_extended`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_extended` (
  `id` serial PRIMARY KEY,
  `id_evento` BIGINT UNSIGNED NOT NULL,
  `external_id` BIGINT UNSIGNED,
  `utimestamp` BIGINT NOT NULL DEFAULT 0,
  `description` TEXT,
  FOREIGN KEY `tevent_ext_fk`(`id_evento`) REFERENCES `tevento`(`id_evento`)
  ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tgrupo`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgrupo` (
  `id_grupo` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL DEFAULT '',
  `icon` VARCHAR(50) DEFAULT 'world',
  `parent` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `propagate` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `disabled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `custom_id` VARCHAR(255) DEFAULT '',
  `id_skin` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT,
  `contact` TEXT,
  `other` TEXT,
  `password` VARCHAR(45) DEFAULT '',
  `max_agents` INT UNSIGNED NOT NULL DEFAULT 0,
   PRIMARY KEY  (`id_grupo`),
   KEY `parent_index` (`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tcredential_store`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcredential_store` (
  `identifier` VARCHAR(100) NOT NULL,
  `id_group` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `product` ENUM('CUSTOM', 'AWS', 'AZURE', 'GOOGLE', 'SAP', 'WMI', 'SNMP') DEFAULT 'CUSTOM',
  `username` TEXT,
  `password` TEXT,
  `extra_1` TEXT,
  `extra_2` TEXT,
  PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tincidencia`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tincidencia` (
  `id_incidencia` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inicio` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `cierre` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `titulo` TEXT,
  `descripcion` TEXT,
  `id_usuario` VARCHAR(255) NOT NULL DEFAULT '',
  `origen` VARCHAR(100) NOT NULL DEFAULT '',
  `estado` INT NOT NULL DEFAULT 0,
  `prioridad` INT NOT NULL DEFAULT 0,
  `id_grupo` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `id_creator` VARCHAR(60) DEFAULT NULL,
  `id_lastupdate` VARCHAR(60) DEFAULT NULL,
  `id_agente_modulo` BIGINT NOT NULL,
  `notify_email` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent` INT UNSIGNED NULL DEFAULT 0, 
  PRIMARY KEY  (`id_incidencia`),
  KEY `incident_index_1` (`id_usuario`,`id_incidencia`),
  KEY `id_agente_modulo` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tlanguage`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlanguage` (
  `id_language` VARCHAR(6) NOT NULL DEFAULT '',
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY  (`id_language`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tlink`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlink` (
  `id_link` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `link` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY  (`id_link`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tmodule_group`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule_group` (
  `id_mg` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL DEFAULT '',
  PRIMARY KEY  (`id_mg`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- This table was moved cause the `tmodule_relationship` will add
-- a foreign key for the trecon_task(id_rt)
-- ----------------------------------------------------------------------
-- Table `trecon_task`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trecon_task` (
  `id_rt` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `description` VARCHAR(250) NOT NULL DEFAULT '',
  `subnet` TEXT,
  `id_network_profile` TEXT,
  `review_mode` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `id_group` INT UNSIGNED NOT NULL DEFAULT 1,
  `utimestamp` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `status` TINYINT NOT NULL DEFAULT 0,
  `interval_sweep` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_recon_server` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_os` TINYINT NOT NULL DEFAULT 0,
  `recon_ports` VARCHAR(250) NOT NULL DEFAULT '',
  `snmp_community` VARCHAR(64) NOT NULL DEFAULT 'public',
  `id_recon_script` INT,
  `field1` TEXT,
  `field2` VARCHAR(250) NOT NULL DEFAULT '',
  `field3` VARCHAR(250) NOT NULL DEFAULT '',
  `field4` VARCHAR(250) NOT NULL DEFAULT '',
  `os_detect` TINYINT UNSIGNED DEFAULT 0,
  `resolve_names` TINYINT UNSIGNED DEFAULT 0,
  `parent_detection` TINYINT UNSIGNED DEFAULT 0,
  `parent_recursion` TINYINT UNSIGNED DEFAULT 0,
  `disabled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `macros` TEXT,
  `alias_as_name` TINYINT NOT NULL DEFAULT 0,
  `snmp_enabled` TINYINT UNSIGNED DEFAULT 0,
  `vlan_enabled` TINYINT UNSIGNED DEFAULT 0,
  `snmp_version` VARCHAR(5) NOT NULL DEFAULT 1,
  `snmp_auth_user` VARCHAR(255) NOT NULL DEFAULT '',
  `snmp_auth_pass` VARCHAR(255) NOT NULL DEFAULT '',
  `snmp_auth_method` VARCHAR(25) NOT NULL DEFAULT '',
  `snmp_privacy_method` VARCHAR(25) NOT NULL DEFAULT '',
  `snmp_privacy_pass` VARCHAR(255) NOT NULL DEFAULT '',
  `snmp_security_level` VARCHAR(25) NOT NULL DEFAULT '',
  `wmi_enabled` TINYINT UNSIGNED DEFAULT 0,
  `rcmd_enabled` TINYINT UNSIGNED DEFAULT 0,
  `auth_strings` TEXT,
  `auto_monitor` TINYINT UNSIGNED DEFAULT 1,
  `autoconfiguration_enabled` TINYINT UNSIGNED DEFAULT 0,
  `summary` TEXT,
  `type` INT NOT NULL DEFAULT 0,
  `subnet_csv` TINYINT UNSIGNED DEFAULT 0,
  `snmp_skip_non_enabled_ifs` TINYINT UNSIGNED DEFAULT 1,
  PRIMARY KEY  (`id_rt`),
  KEY `recon_task_daemon` (`id_recon_server`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tdiscovery_tmp`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tdiscovery_tmp_agents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_rt` INT UNSIGNED NOT NULL,
  `label` VARCHAR(600) NOT NULL DEFAULT '',
  `data` MEDIUMTEXT,
  `review_date` DATETIME DEFAULT NULL,
  `created` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_rt` (`id_rt`),
  INDEX `label` (`label`),
  CONSTRAINT `tdta_trt` FOREIGN KEY (`id_rt`) REFERENCES `trecon_task` (`id_rt`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tdiscovery_tmp_connections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_rt` INT UNSIGNED NOT NULL,
  `dev_1` TEXT,
  `dev_2` TEXT,
  `if_1` TEXT,
  `if_2` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tmodule_relationship`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule_relationship` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_rt` INT UNSIGNED DEFAULT NULL,
  `id_server` VARCHAR(100) NOT NULL DEFAULT '',
  `module_a` INT UNSIGNED NOT NULL,
  `module_b` INT UNSIGNED NOT NULL,
  `disable_update` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `type` ENUM('direct', 'failover') DEFAULT 'direct',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`module_a`) REFERENCES tagente_modulo(`id_agente_modulo`)
    ON DELETE CASCADE,
  FOREIGN KEY (`module_b`) REFERENCES tagente_modulo(`id_agente_modulo`)
    ON DELETE CASCADE,
  FOREIGN KEY (`id_rt`) REFERENCES trecon_task(`id_rt`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnetwork_component`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_component` (
  `id_nc` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT,
  `description` TEXT,
  `id_group` INT NOT NULL DEFAULT 1,
  `type` SMALLINT NOT NULL DEFAULT 6,
  `max` BIGINT NOT NULL DEFAULT 0,
  `min` BIGINT NOT NULL DEFAULT 0,
  `module_interval` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `tcp_port` INT UNSIGNED NOT NULL DEFAULT 0,
  `tcp_send` TEXT,
  `tcp_rcv` TEXT,
  `snmp_community` VARCHAR(255) NOT NULL DEFAULT 'NULL',
  `snmp_oid` VARCHAR(400) NOT NULL,
  `id_module_group` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_modulo` INT UNSIGNED DEFAULT 0,
  `id_plugin` INT unsigned DEFAULT 0,
  `plugin_user` TEXT,
  `plugin_pass` TEXT,
  `plugin_parameter` TEXT,
  `max_timeout` INT UNSIGNED DEFAULT 0,
  `max_retries` INT UNSIGNED DEFAULT 0,
  `history_data` TINYINT UNSIGNED DEFAULT 1,
  `min_warning` DOUBLE DEFAULT 0,
  `max_warning` DOUBLE DEFAULT 0,
  `str_warning` TEXT,
  `min_critical` DOUBLE DEFAULT 0,
  `max_critical` DOUBLE DEFAULT 0,
  `str_critical` TEXT,
  `min_ff_event` INT UNSIGNED DEFAULT 0,
  `custom_string_1` TEXT,
  `custom_string_2` TEXT,
  `custom_string_3` TEXT,
  `custom_integer_1` INT DEFAULT 0,
  `custom_integer_2` INT DEFAULT 0,
  `post_process` DOUBLE DEFAULT 0,
  `unit` TEXT,
  `wizard_level` ENUM('basic','advanced','nowizard') DEFAULT 'nowizard',
  `macros` TEXT,
  `critical_instructions` TEXT,
  `warning_instructions` TEXT,
  `unknown_instructions` TEXT,
  `critical_inverse` TINYINT UNSIGNED DEFAULT 0,
  `warning_inverse` TINYINT UNSIGNED DEFAULT 0,
  `id_category` INT DEFAULT 0,
  `tags` TEXT,
  `disabled_types_event` TEXT,
  `module_macros` TEXT,
  `min_ff_event_normal` INT UNSIGNED DEFAULT 0,
  `min_ff_event_warning` INT UNSIGNED DEFAULT 0,
  `min_ff_event_critical` INT UNSIGNED DEFAULT 0,
  `ff_type` TINYINT UNSIGNED DEFAULT 0,
  `each_ff` TINYINT UNSIGNED DEFAULT 0,
  `dynamic_interval` INT UNSIGNED DEFAULT 0,
  `dynamic_max` INT DEFAULT 0,
  `dynamic_min` INT DEFAULT 0,
  `dynamic_next` BIGINT NOT NULL DEFAULT 0,
  `dynamic_two_tailed` TINYINT UNSIGNED DEFAULT 0,
  `module_type` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `protocol` TINYTEXT,
  `manufacturer_id` VARCHAR(200),
  `execution_type` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `scan_type` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `value` TEXT,
  `value_operations` TEXT,
  `module_enabled` TINYINT UNSIGNED DEFAULT 0,
  `name_oid` VARCHAR(255) DEFAULT '',
  `query_class` VARCHAR(200) DEFAULT '',
  `query_key_field` VARCHAR(200) DEFAULT '',
  `scan_filters` TEXT,
  `query_filters` TEXT,
  `enabled` TINYINT UNSIGNED DEFAULT 1,
  `percentage_critical` TINYINT UNSIGNED DEFAULT 0,
  `percentage_warning` TINYINT UNSIGNED DEFAULT 0,
  `warning_time` INT UNSIGNED DEFAULT 0,
  `target_ip` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY  (`id_nc`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnetwork_component_group`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_component_group` (
  `id_sg`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL DEFAULT '',
  `parent` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnetwork_profile`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_profile` (
  `id_np`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `description` VARCHAR(250) DEFAULT '',
  PRIMARY KEY  (`id_np`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnetwork_profile_component`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_profile_component` (
  `id_nc` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `id_np` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  KEY `id_np` (`id_np`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tpen`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpen` (
  `pen` INT UNSIGNED NOT NULL,
  `manufacturer` TEXT,
  `description` TEXT,
  PRIMARY KEY (`pen`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnetwork_profile_pen`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_profile_pen` (
  `pen` INT UNSIGNED NOT NULL,
  `id_np` INT UNSIGNED NOT NULL,
  CONSTRAINT `fk_network_profile_pen_pen` FOREIGN KEY (`pen`)
  REFERENCES `tpen` (`pen`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_network_profile_pen_id_np` FOREIGN KEY (`id_np`)
  REFERENCES `tnetwork_profile` (`id_np`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnota`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnota` (
  `id_nota` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_incident` BIGINT UNSIGNED NOT NULL,
  `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nota` MEDIUMTEXT,
  PRIMARY KEY  (`id_nota`),
  KEY `id_incident` (`id_incident`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `torigen`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `torigen` (
  `origen` VARCHAR(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tperfil`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tperfil` (
  `id_perfil` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT,
  `agent_view` TINYINT NOT NULL DEFAULT 0,
  `agent_edit` TINYINT NOT NULL DEFAULT 0,
  `alert_edit` TINYINT NOT NULL DEFAULT 0,
  `user_management` TINYINT NOT NULL DEFAULT 0,
  `db_management` TINYINT NOT NULL DEFAULT 0,
  `alert_management` TINYINT NOT NULL DEFAULT 0,
  `pandora_management` TINYINT NOT NULL DEFAULT 0,
  `report_view` TINYINT NOT NULL DEFAULT 0,
  `report_edit` TINYINT NOT NULL DEFAULT 0,
  `report_management` TINYINT NOT NULL DEFAULT 0,
  `event_view` TINYINT NOT NULL DEFAULT 0,
  `event_edit` TINYINT NOT NULL DEFAULT 0,
  `event_management` TINYINT NOT NULL DEFAULT 0,
  `agent_disable` TINYINT NOT NULL DEFAULT 0,
  `map_view` TINYINT NOT NULL DEFAULT 0,
  `map_edit` TINYINT NOT NULL DEFAULT 0,
  `map_management` TINYINT NOT NULL DEFAULT 0,
  `vconsole_view` TINYINT NOT NULL DEFAULT 0,
  `vconsole_edit` TINYINT NOT NULL DEFAULT 0,
  `vconsole_management` TINYINT NOT NULL DEFAULT 0,
  `network_config_view`TINYINT NOT NULL DEFAULT 0,
  `network_config_edit`TINYINT NOT NULL DEFAULT 0,
  `network_config_management`TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_perfil`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `trecon_script`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trecon_script` (
  `id_recon_script` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) DEFAULT '',
  `description` TEXT,
  `script` VARCHAR(250) DEFAULT '',
  `macros` TEXT,
  `type` INT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_recon_script`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tserver`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tserver` (
  `id_server` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `ip_address` VARCHAR(100) NOT NULL DEFAULT '',
  `status` INT NOT NULL DEFAULT 0,
  `laststart` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `keepalive` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `snmp_server` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `network_server` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `data_server` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `master` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `checksum` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `description` VARCHAR(255) DEFAULT NULL,
  `recon_server` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `version` VARCHAR(25) NOT NULL DEFAULT '',
  `plugin_server` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `prediction_server` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `wmi_server` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `export_server` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `server_type` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `queued_modules` INT UNSIGNED NOT NULL DEFAULT 0,
  `threads` INT UNSIGNED NOT NULL DEFAULT 0,
  `lag_time` INT NOT NULL DEFAULT 0,
  `lag_modules` INT NOT NULL DEFAULT 0,
  `total_modules_running` INT NOT NULL DEFAULT 0,
  `my_modules` INT NOT NULL DEFAULT 0,
  `server_keepalive` INT NOT NULL DEFAULT 0,
  `stat_utimestamp` BIGINT NOT NULL DEFAULT 0,
  `exec_proxy` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `port` INT UNSIGNED NOT NULL DEFAULT 0,
  `server_keepalive_utimestamp` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_server`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
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
  `id_sesion` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0',
  `ip_origen` VARCHAR(100) NOT NULL DEFAULT '',
  `accion` VARCHAR(100) NOT NULL DEFAULT '',
  `descripcion` TEXT,
  `fecha` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `utimestamp` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_sesion`),
  KEY `idx_user` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `ttipo_modulo`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttipo_modulo` (
  `id_tipo` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL DEFAULT '',
  `categoria` INT NOT NULL DEFAULT 0,
  `descripcion` VARCHAR(100) NOT NULL DEFAULT '',
  `icon` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY  (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `ttrap`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttrap` (
  `id_trap` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(50) NOT NULL DEFAULT '',
  `oid` TEXT,
  `oid_custom` TEXT,
  `type` INT NOT NULL DEFAULT 0,
  `type_custom` VARCHAR(100) DEFAULT '',
  `value` TEXT,
  `value_custom` TEXT,
  `alerted` SMALLINT NOT NULL DEFAULT 0,
  `status` SMALLINT NOT NULL DEFAULT 0,
  `id_usuario` VARCHAR(255) DEFAULT '',
  `timestamp` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `priority` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `text` VARCHAR(255) DEFAULT '',
  `description` VARCHAR(255) DEFAULT '',
  `severity` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  PRIMARY KEY  (`id_trap`),
  INDEX timestamp (`timestamp`),
  INDEX status (`status`),
  INDEX source (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_filter` (
  `id_filter`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group_filter` INT NOT NULL DEFAULT 0,
  `id_name` VARCHAR(600) NOT NULL,
  `id_group` INT NOT NULL DEFAULT 0,
  `event_type` TEXT,
  `severity` TEXT,
  `status` INT NOT NULL DEFAULT -1,
  `search` TEXT,
  `not_search` INT NOT NULL DEFAULT 0,
  `text_agent` TEXT,
  `id_agent` INT DEFAULT 0,
  `id_agent_module` INT DEFAULT 0,
  `pagination` INT NOT NULL DEFAULT 25,
  `event_view_hr` INT NOT NULL DEFAULT 8,
  `id_user_ack` TEXT,
  `group_rep` INT NOT NULL DEFAULT 0,
  `tag_with` TEXT,
  `tag_without` TEXT,
  `filter_only_alert` INT NOT NULL DEFAULT -1,
  `search_secondary_groups` INT NOT NULL DEFAULT 0,
  `search_recursive_groups` INT NOT NULL DEFAULT 0,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `source` TINYTEXT,
  `id_extra` TINYTEXT,
  `user_comment` TEXT,
  `id_source_event` INT  NULL DEFAULT 0,
  `server_id` TEXT,
  `time_from` TIME NULL,
  `time_to` TIME NULL,
  `custom_data` VARCHAR(500) DEFAULT '',
  `custom_data_filter_type` TINYINT UNSIGNED DEFAULT 0,
  `owner_user` TEXT,
  PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tusuario`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tusuario` (
  `id_user` VARCHAR(255) NOT NULL DEFAULT '0',
  `fullname` VARCHAR(255) NOT NULL,
  `firstname` VARCHAR(255) NOT NULL,
  `lastname` VARCHAR(255) NOT NULL,
  `middlename` VARCHAR(255) NOT NULL,
  `password` VARCHAR(60) DEFAULT NULL,
  `comments` VARCHAR(200) DEFAULT NULL,
  `last_connect` BIGINT NOT NULL DEFAULT 0,
  `registered` BIGINT NOT NULL DEFAULT 0,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(100) DEFAULT NULL,
  `is_admin` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `language` VARCHAR(10) DEFAULT NULL,
  `timezone` VARCHAR(50) DEFAULT '',
  `block_size` INT NOT NULL DEFAULT 20,
  `id_skin` INT UNSIGNED NOT NULL DEFAULT 0,
  `disabled` INT NOT NULL DEFAULT 0,
  `shortcut` TINYINT DEFAULT 0,
  `shortcut_data` TEXT,
  `section` TEXT,
  `data_section` TEXT,
  `metaconsole_section` VARCHAR(255) NOT NULL DEFAULT 'Default',
  `metaconsole_data_section` VARCHAR(255) NOT NULL DEFAULT '',
  `force_change_pass` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `last_pass_change` DATETIME,
  `last_failed_login` DATETIME,
  `failed_attempt` INT NOT NULL DEFAULT 0,
  `login_blocked` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `metaconsole_access` ENUM('basic','advanced') DEFAULT 'basic',
  `not_login` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `local_user` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `metaconsole_agents_manager` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `metaconsole_access_node` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `strict_acl` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_filter`  INT UNSIGNED NULL DEFAULT NULL,
  `session_time` INT signed NOT NULL DEFAULT 0,
  `default_event_filter` INT UNSIGNED NOT NULL DEFAULT 0,
  `metaconsole_default_event_filter` INT UNSIGNED NOT NULL DEFAULT 0,
  `show_tips_startup` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `autorefresh_white_list` TEXT ,
  `time_autorefresh` INT UNSIGNED NOT NULL DEFAULT 30,
  `default_custom_view` INT UNSIGNED NULL DEFAULT 0,
  `ehorus_user_level_user` VARCHAR(60),
  `ehorus_user_level_pass` VARCHAR(45),
  `ehorus_user_level_enabled` TINYINT,
  `integria_user_level_user` VARCHAR(60),
  `integria_user_level_pass` VARCHAR(45),
  `api_token` VARCHAR(255) NOT NULL DEFAULT '',
  `allowed_ip_active` TINYINT UNSIGNED DEFAULT 0,
  `allowed_ip_list` TEXT,
  `auth_token_secret` VARCHAR(45) DEFAULT NULL,
  CONSTRAINT `fk_filter_id` FOREIGN KEY (`id_filter`) REFERENCES tevent_filter (`id_filter`) ON DELETE SET NULL,
  UNIQUE KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tusuario_perfil`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tusuario_perfil` (
  `id_up` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` VARCHAR(255) NOT NULL DEFAULT '',
  `id_perfil` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_grupo` INT NOT NULL DEFAULT 0,
  `no_hierarchy` TINYINT NOT NULL DEFAULT 0,
  `assigned_by` VARCHAR(100) NOT NULL DEFAULT '',
  `id_policy` INT UNSIGNED NOT NULL DEFAULT 0,
  `tags` TEXT,
  PRIMARY KEY  (`id_up`),
  INDEX `tusuario_perfil_user` (`id_usuario`),
  INDEX `tusuario_perfil_group` (`id_grupo`),
  INDEX `tusuario_perfil_profile` (`id_perfil`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tuser_double_auth`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tuser_double_auth` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL,
  `secret` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`id_user`),
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `treset_pass_history`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treset_pass_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL,
  `reset_moment` DATETIME NOT NULL,
  `success` TINYINT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tnotification_source`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnotification_source` (
  `id` SERIAL,
  `description` VARCHAR(255) DEFAULT NULL,
  `icon` TEXT,
  `max_postpone_time` INT DEFAULT NULL,
  `enabled` INT DEFAULT NULL,
  `user_editable` INT DEFAULT NULL,
  `also_mail` INT DEFAULT NULL,
  `subtype_blacklist` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tmensajes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmensajes` (
  `id_mensaje` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario_origen` VARCHAR(255) NOT NULL DEFAULT '',
  `mensaje` TEXT,
  `timestamp` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `subject` VARCHAR(255) NOT NULL DEFAULT '',
  `estado` INT UNSIGNED NOT NULL DEFAULT 0,
  `url` TEXT,
  `response_mode` VARCHAR(200) DEFAULT NULL,
  `citicity` INT UNSIGNED DEFAULT 0,
  `id_source` BIGINT UNSIGNED NOT NULL,
  `subtype` VARCHAR(255) DEFAULT '',
  `hidden_sent` TINYINT UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id_mensaje`),
  UNIQUE KEY `id_mensaje` (`id_mensaje`),
  KEY `tsource_fk` (`id_source`),
  CONSTRAINT `tsource_fk` FOREIGN KEY (`id_source`) REFERENCES `tnotification_source` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnotification_user`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnotification_user` (
  `id_mensaje` INT UNSIGNED NOT NULL,
  `id_user` VARCHAR(255) NOT NULL,
  `utimestamp_read` BIGINT,
  `utimestamp_erased` BIGINT,
  `postpone` INT,
  PRIMARY KEY (`id_mensaje`,`id_user`),
  FOREIGN KEY (`id_mensaje`) REFERENCES `tmensajes`(`id_mensaje`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES `tusuario`(`id_user`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnotification_group`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnotification_group` (
  `id_mensaje` INT UNSIGNED NOT NULL,
  `id_group` MEDIUMINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_mensaje`,`id_group`),
  FOREIGN KEY (`id_mensaje`) REFERENCES `tmensajes`(`id_mensaje`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnotification_source_user`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnotification_source_user` (
  `id_source` BIGINT UNSIGNED NOT NULL,
  `id_user` VARCHAR(255),
  `enabled` INT DEFAULT NULL,
  `also_mail` INT DEFAULT NULL,
  PRIMARY KEY (`id_source`,`id_user`),
  FOREIGN KEY (`id_source`) REFERENCES `tnotification_source`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES `tusuario`(`id_user`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnotification_source_group`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnotification_source_group` (
  `id_source` BIGINT UNSIGNED NOT NULL,
  `id_group` MEDIUMINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_source`,`id_group`),
  INDEX (`id_group`),
  FOREIGN KEY (`id_source`) REFERENCES `tnotification_source`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnotification_source_user`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnotification_source_group_user` (
  `id_source` BIGINT UNSIGNED NOT NULL,
  `id_group` MEDIUMINT UNSIGNED NOT NULL,
  `id_user` VARCHAR(255),
  `enabled` INT DEFAULT NULL,
  `also_mail` INT DEFAULT NULL,
  PRIMARY KEY (`id_source`,`id_user`),
  FOREIGN KEY (`id_source`) REFERENCES `tnotification_source`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES `tusuario`(`id_user`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_group`) REFERENCES `tnotification_source_group`(`id_group`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnews`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnews` (
  `id_news` INT UNSIGNED NOT NULL  AUTO_INCREMENT,
  `author` VARCHAR(255)  NOT NULL DEFAULT '',
  `subject` VARCHAR(255)  NOT NULL DEFAULT '',
  `text` TEXT,
  `timestamp` DATETIME,
  `id_group` INT NOT NULL DEFAULT 0,
  `modal` TINYINT DEFAULT 0,
  `expire` TINYINT DEFAULT 0,
  `expire_timestamp` DATETIME,
  PRIMARY KEY(`id_news`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tgraph`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgraph` (
  `id_graph` INT UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL DEFAULT '',
  `name` VARCHAR(150) NOT NULL DEFAULT '',
  `description` TEXT,
  `period` INT NOT NULL DEFAULT 0,
  `width` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `height` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `private` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `events` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `stacked` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `id_graph_template` INT NOT NULL DEFAULT 0,
  `percentil` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `summatory_series` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `average_series` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `modules_series` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `fullscale` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`id_graph`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tgraph_source`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgraph_source` (
  `id_gs` INT UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_graph` INT NOT NULL DEFAULT 0,
  `id_server` INT NOT NULL DEFAULT 0,
  `id_agent_module` INT NOT NULL DEFAULT 0,
  `weight` DOUBLE NOT NULL DEFAULT 0,
  `label` VARCHAR(150) DEFAULT '',
  `field_order` INT DEFAULT 0,
  PRIMARY KEY(`id_gs`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `treport`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport` (
  `id_report` INT UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL DEFAULT '',
  `name` VARCHAR(150) NOT NULL DEFAULT '',
  `description` TEXT,
  `private` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT NULL,
  `custom_logo` VARCHAR(200)  DEFAULT NULL,
  `header` MEDIUMTEXT,
  `first_page` MEDIUMTEXT,
  `footer` MEDIUMTEXT,
  `custom_font` VARCHAR(200) DEFAULT NULL,
  `id_template` INT UNSIGNED DEFAULT 0,
  `id_group_edit` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `metaconsole` TINYINT DEFAULT 0,
  `non_interactive` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `hidden` TINYINT DEFAULT 0,
  `orientation` VARCHAR(25) NOT NULL DEFAULT 'vertical',
  `cover_page_render` TINYINT NOT NULL DEFAULT 1,
  `index_render` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY(`id_report`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `treport_content`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content` (
  `id_rc` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_report` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_gs` INT UNSIGNED NULL DEFAULT NULL,
  `id_agent_module` BIGINT UNSIGNED NULL DEFAULT NULL,
  `type` VARCHAR(30) DEFAULT 'simple_graph',
  `period` INT NOT NULL DEFAULT 0,
  `order` INT NOT NULL DEFAULT 0,
  `name` VARCHAR(300) NULL,
  `description` MEDIUMTEXT,
  `id_agent` INT UNSIGNED NOT NULL DEFAULT 0,
  `text` TEXT,
  `external_source` MEDIUMTEXT,
  `treport_custom_sql_id` INT UNSIGNED DEFAULT 0,
  `header_definition` TINYTEXT,
  `column_separator` TINYTEXT,
  `line_separator` TINYTEXT,
  `time_from` time DEFAULT '00:00:00',
  `time_to` time DEFAULT '00:00:00',
  `monday` TINYINT DEFAULT 1,
  `tuesday` TINYINT DEFAULT 1,
  `wednesday` TINYINT DEFAULT 1,
  `thursday` TINYINT DEFAULT 1,
  `friday` TINYINT DEFAULT 1,
  `saturday` TINYINT DEFAULT 1,
  `sunday` TINYINT DEFAULT 1,
  `only_display_wrong` TINYINT unsigned DEFAULT 0 NOT NULL,
  `top_n` INT NOT NULL DEFAULT 0,
  `top_n_value` INT NOT NULL DEFAULT 10,
  `exception_condition` INT NOT NULL DEFAULT 0,
  `exception_condition_value` DOUBLE NOT NULL DEFAULT 0,
  `show_resume` INT NOT NULL DEFAULT 0,
  `order_uptodown` INT NOT NULL DEFAULT 0,
  `show_graph` INT NOT NULL DEFAULT 0,
  `group_by_agent` INT NOT NULL DEFAULT 0,
  `style` TEXT,
  `id_group` INT unsigned NOT NULL DEFAULT 0,
  `id_module_group` INT unsigned NOT NULL DEFAULT 0,
  `server_name` TEXT,
  `historical_db` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `lapse_calc` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `lapse` INT UNSIGNED NOT NULL DEFAULT 300,
  `visual_format` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `hide_no_data` TINYINT DEFAULT 0,
  `recursion` TINYINT DEFAULT NULL,
  `show_extended_events` TINYINT DEFAULT 0,
  `total_time` TINYINT DEFAULT 1,
  `time_failed` TINYINT DEFAULT 1,
  `time_in_ok_status` TINYINT DEFAULT 1,
  `time_in_warning_status` TINYINT DEFAULT 0,
  `time_in_unknown_status` TINYINT DEFAULT 1,
  `time_of_not_initialized_module` TINYINT DEFAULT 1,
  `time_of_downtime` TINYINT DEFAULT 1,
  `total_checks` TINYINT DEFAULT 1,
  `checks_failed` TINYINT DEFAULT 1,
  `checks_in_ok_status` TINYINT DEFAULT 1,
  `checks_in_warning_status` TINYINT DEFAULT 0,
  `unknown_checks` TINYINT DEFAULT 1,
  `agent_max_value` TINYINT DEFAULT 1,
  `agent_min_value` TINYINT DEFAULT 1,
  `current_month` TINYINT DEFAULT 1,
  `failover_mode` TINYINT DEFAULT 1,
  `failover_type` TINYINT DEFAULT 1,
  `uncompressed_module` TINYINT DEFAULT 0,
  `summary` TINYINT DEFAULT 0,
  `landscape` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `pagebreak` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `compare_work_time` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `graph_render` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `ipam_network_filter` INT UNSIGNED DEFAULT 0,
  `ipam_alive_ips` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `ipam_ip_not_assigned_to_agent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `macros_definition` TEXT,
  `render_definition` TEXT,
  `use_prefix_notation` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY(`id_rc`),
  FOREIGN KEY (`id_report`) REFERENCES treport(`id_report`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `treport_content_sla_combined`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_sla_combined` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_report_content` INT UNSIGNED NOT NULL,
  `id_agent_module` INT UNSIGNED NOT NULL,
  `id_agent_module_failover` INT UNSIGNED NOT NULL,
  `sla_max` DOUBLE NOT NULL DEFAULT 0,
  `sla_min` DOUBLE NOT NULL DEFAULT 0,
  `sla_limit` DOUBLE NOT NULL DEFAULT 0,
  `server_name` TEXT,
  PRIMARY KEY(`id`),
  FOREIGN KEY (`id_report_content`) REFERENCES treport_content(`id_rc`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `treport_content_item`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_item` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_report_content` INT UNSIGNED NOT NULL,
  `id_agent_module` INT UNSIGNED NOT NULL,
  `id_agent_module_failover` INT UNSIGNED NOT NULL DEFAULT 0,
  `server_name` TEXT,
  `operation` TEXT,
  PRIMARY KEY(`id`),
  FOREIGN KEY (`id_report_content`) REFERENCES treport_content(`id_rc`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `treport_custom_sql`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_custom_sql` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL DEFAULT '',
  `sql` TEXT,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(600)  NOT NULL,
  `id_group` INT UNSIGNED NOT NULL,
  `background` VARCHAR(200)  NOT NULL,
  `height` INT UNSIGNED NOT NULL DEFAULT 0,
  `width` INT UNSIGNED NOT NULL DEFAULT 0,
  `background_color` VARCHAR(50) NOT NULL DEFAULT '#FFF',
  `is_favourite` INT UNSIGNED NOT NULL DEFAULT 0,
  `auto_adjust` INT UNSIGNED NOT NULL DEFAULT 0,
  `maintenance_mode` TEXT,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout_data` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_layout` INT UNSIGNED NOT NULL DEFAULT 0,
  `pos_x` INT UNSIGNED NOT NULL DEFAULT 0,
  `pos_y` INT UNSIGNED NOT NULL DEFAULT 0,
  `height` INT UNSIGNED NOT NULL DEFAULT 0,
  `width` INT UNSIGNED NOT NULL DEFAULT 0,
  `label` TEXT,
  `image` VARCHAR(200) DEFAULT '',
  `type` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `period` INT UNSIGNED NOT NULL DEFAULT 3600,
  `id_agente_modulo` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_layout_linked` INT unsigned NOT NULL DEFAULT 0,
  `parent_item` INT UNSIGNED NOT NULL DEFAULT 0,
  `enable_link` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `id_metaconsole` INT NOT NULL DEFAULT 0,
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_custom_graph` INT UNSIGNED NOT NULL DEFAULT 0,
  `border_width` INT UNSIGNED NOT NULL DEFAULT 0,
  `type_graph` VARCHAR(50) NOT NULL DEFAULT 'area',
  `label_position` VARCHAR(50) NOT NULL DEFAULT 'down',
  `border_color` VARCHAR(200) DEFAULT '',
  `fill_color` VARCHAR(200) DEFAULT '',
  `recursive_group` TINYINT NOT NULL DEFAULT 0,
  `show_statistics` TINYINT NOT NULL DEFAULT 0,
  `linked_layout_node_id` INT NOT NULL DEFAULT 0,
  `linked_layout_status_type` ENUM ('default', 'weight', 'service') DEFAULT 'default',
  `id_layout_linked_weight` INT NOT NULL DEFAULT 0,
  `linked_layout_status_as_service_warning` DOUBLE NOT NULL DEFAULT 0,
  `linked_layout_status_as_service_critical` DOUBLE NOT NULL DEFAULT 0,
  `element_group` INT NOT NULL DEFAULT 0,
  `show_on_top` TINYINT NOT NULL DEFAULT 0,
  `clock_animation` VARCHAR(60) NOT NULL DEFAULT 'analogic_1',
  `time_format` VARCHAR(60) NOT NULL DEFAULT 'time',
  `timezone` VARCHAR(60) NOT NULL DEFAULT 'Europe/Madrid',
  `show_last_value` TINYINT UNSIGNED NULL DEFAULT 0,
  `cache_expiration` INT UNSIGNED NOT NULL DEFAULT 0,
  `title` TEXT ,
  PRIMARY KEY(`id`),
  INDEX `tlayout_data_layout` (`id_layout`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tplugin`
-- ---------------------------------------------------------------------
-- The fields 'net_dst_opt', 'net_port_opt', 'user_opt' and
-- 'pass_opt' are deprecated for the 5.1.
CREATE TABLE IF NOT EXISTS `tplugin` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `description` MEDIUMTEXT,
  `max_timeout` INT UNSIGNED NOT NULL DEFAULT 0,
  `max_retries` INT UNSIGNED NOT NULL DEFAULT 0,
  `execute` VARCHAR(250) NOT NULL,
  `net_dst_opt` VARCHAR(50) DEFAULT '',
  `net_port_opt` VARCHAR(50) DEFAULT '',
  `user_opt` VARCHAR(50) DEFAULT '',
  `pass_opt` VARCHAR(50) DEFAULT '',
  `plugin_type` INT UNSIGNED NOT NULL DEFAULT 0,
  `macros` TEXT,
  `parameters` TEXT,
  `no_delete` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4; 

-- ---------------------------------------------------------------------
-- Table `tmodule`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule` (
  `id_module` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_module`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tserver_export`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tserver_export` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(600) NOT NULL DEFAULT '',
  `preffix` VARCHAR(100) NOT NULL DEFAULT '',
  `interval` INT UNSIGNED NOT NULL DEFAULT 300,
  `ip_server` VARCHAR(100) NOT NULL DEFAULT '',
  `connect_mode` enum ('tentacle', 'ssh', 'local') DEFAULT 'local',
  `id_export_server` INT UNSIGNED DEFAULT NULL,
  `user` VARCHAR(100) NOT NULL DEFAULT '',
  `pass` VARCHAR(100) NOT NULL DEFAULT '',
  `port` INT UNSIGNED DEFAULT 0,
  `directory` VARCHAR(100) NOT NULL DEFAULT '',
  `options` VARCHAR(100) NOT NULL DEFAULT '',
  `timezone_offset` TINYINT NULL DEFAULT 0 COMMENT 'Number of hours of difference with the server timezone',
  PRIMARY KEY  (`id`),
  INDEX id_export_server (`id_export_server`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tserver_export_data`
-- ---------------------------------------------------------------------
-- id_export_server is real pandora fms export server process that manages this server
-- id is the 'destination' server to export
CREATE TABLE IF NOT EXISTS `tserver_export_data` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_export_server` INT UNSIGNED DEFAULT NULL,
  `agent_name` VARCHAR(100) NOT NULL DEFAULT '',
  `module_name` VARCHAR(600) NOT NULL DEFAULT '',
  `module_type` VARCHAR(100) NOT NULL DEFAULT '',
  `data` VARCHAR(255) DEFAULT NULL, 
  `timestamp` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tplanned_downtime`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tplanned_downtime` (
  `id` MEDIUMINT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR( 100 ) NOT NULL,
  `description` TEXT,
  `date_from` BIGINT NOT NULL DEFAULT 0,
  `date_to` BIGINT NOT NULL DEFAULT 0,
  `executed` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `only_alerts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `monday` TINYINT DEFAULT 0,
  `tuesday` TINYINT DEFAULT 0,
  `wednesday` TINYINT DEFAULT 0,
  `thursday` TINYINT DEFAULT 0,
  `friday` TINYINT DEFAULT 0,
  `saturday` TINYINT DEFAULT 0,
  `sunday` TINYINT DEFAULT 0,
  `periodically_time_from` time NULL DEFAULT NULL,
  `periodically_time_to` time NULL DEFAULT NULL,
  `periodically_day_from` INT UNSIGNED DEFAULT NULL,
  `periodically_day_to` INT UNSIGNED DEFAULT NULL,
  `type_downtime` VARCHAR(100) NOT NULL DEFAULT 'disabled_agents_alerts',
  `type_execution` VARCHAR(100) NOT NULL DEFAULT 'once',
  `type_periodicity` VARCHAR(100) NOT NULL DEFAULT 'weekly',
  `id_user` VARCHAR(255) NOT NULL DEFAULT '0',
  `cron_interval_from` VARCHAR(100) DEFAULT '',
  `cron_interval_to` VARCHAR(100) DEFAULT '',
  PRIMARY KEY (  `id` ) 
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tplanned_downtime_agents`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tplanned_downtime_agents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agent` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `id_downtime` MEDIUMINT NOT NULL DEFAULT 0,
  `all_modules` TINYINT DEFAULT 1,
  `manually_disabled` TINYINT DEFAULT 0,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_downtime`) REFERENCES tplanned_downtime(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tplanned_downtime_modules`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tplanned_downtime_modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agent` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent_module` INT NOT NULL, 
  `id_downtime` MEDIUMINT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_downtime`) REFERENCES tplanned_downtime(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

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
  `manual_placement` TINYINT NOT NULL DEFAULT 0 COMMENT '0 to show that the position cames from the agent, 1 to show that the position was established manualy' ,
  `number_of_packages` INT NOT NULL DEFAULT 1 COMMENT 'Number of data packages received with this position from the start_timestampa to the_end_timestamp' ,
  `tagente_id_agente` INT UNSIGNED NOT NULL COMMENT 'reference to the agent' ,
  PRIMARY KEY (`id_tgis_data`) ,
  INDEX `start_timestamp_index` USING BTREE (`start_timestamp` ASC),
  INDEX `end_timestamp_index` USING BTREE (`end_timestamp` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='Table to store historical GIS information of the agents';


-- ----------------------------------------------------------------------
-- Table `tgis_data_status`
-- ----------------------------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_data_status` (
  `tagente_id_agente` INT UNSIGNED NOT NULL COMMENT 'Reference to the agent' ,
  `current_longitude` DOUBLE NOT NULL COMMENT 'Last received longitude',
  `current_latitude` DOUBLE NOT NULL COMMENT 'Last received latitude',
  `current_altitude` DOUBLE NULL COMMENT 'Last received altitude',
  `stored_longitude` DOUBLE NOT NULL COMMENT 'Reference longitude to see if the agent has moved',
  `stored_latitude` DOUBLE NOT NULL COMMENT 'Reference latitude to see if the agent has moved',
  `stored_altitude` DOUBLE NULL COMMENT 'Reference altitude to see if the agent has moved',
  `number_of_packages` INT NOT NULL DEFAULT 1 COMMENT 'Number of data packages received with this position since start_timestampa' ,
  `start_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp on wich the agente started to be in this position' ,
  `manual_placement` TINYINT NOT NULL DEFAULT 0 COMMENT '0 to show that the position cames from the agent, 1 to show that the position was established manualy' ,
  `description` TEXT NULL COMMENT 'description of the region correoponding to this placemnt' ,
  PRIMARY KEY (`tagente_id_agente`) ,
  INDEX `start_timestamp_index` USING BTREE (`start_timestamp` ASC),
  INDEX `fk_tgisdata_tagente1` (`tagente_id_agente` ASC) ,
  CONSTRAINT `fk_tgisdata_tagente1`
    FOREIGN KEY (`tagente_id_agente` )
    REFERENCES `tagente` (`id_agente` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='Table to store last GIS information of the agents';

-- ----------------------------------------------------------------------
-- Table `tgis_map`
-- ----------------------------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map` (
  `id_tgis_map` INT NOT NULL AUTO_INCREMENT COMMENT 'table identifier' ,
  `map_name` VARCHAR(63) NOT NULL COMMENT 'Name of the map' ,
  `initial_longitude` DOUBLE NULL COMMENT "longitude of the center of the map when it\'s loaded",
  `initial_latitude` DOUBLE NULL COMMENT "latitude of the center of the map when it\'s loaded",
  `initial_altitude` DOUBLE NULL COMMENT "altitude of the center of the map when it\'s loaded",
  `zoom_level` TINYINT NULL DEFAULT 1 COMMENT 'Zoom level to show when the map is loaded.',
  `map_background` VARCHAR(127) NULL COMMENT 'path on the server to the background image of the map',
  `default_longitude` DOUBLE NULL COMMENT 'DEFAULT longitude for the agents placed on the map',
  `default_latitude` DOUBLE NULL COMMENT 'DEFAULT latitude for the agents placed on the map',
  `default_altitude` DOUBLE NULL COMMENT 'DEFAULT altitude for the agents placed on the map',
  `group_id` INT NOT NULL DEFAULT 0 COMMENT 'Group that owns the map' ,
  `default_map` TINYINT NULL DEFAULT 0 COMMENT '1 if this is the DEFAULT map, 0 in other case',
  PRIMARY KEY (`id_tgis_map`),
  INDEX `map_name_index` (`map_name` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='Table containing information about a gis map';

-- ---------------------------------------------------------------------
-- Table `tgis_map_connection`
-- ---------------------------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_connection` (
  `id_tmap_connection` INT NOT NULL AUTO_INCREMENT COMMENT 'table id',
  `conection_name` VARCHAR(45) NULL COMMENT 'Name of the connection (name of the base layer)',
  `connection_type` VARCHAR(45) NULL COMMENT 'Type of map server to connect',
  `conection_data` TEXT NULL COMMENT 'connection information (this can probably change to fit better the possible connection parameters)',
  `num_zoom_levels` TINYINT NULL COMMENT 'Number of zoom levels available',
  `default_zoom_level` TINYINT NOT NULL DEFAULT 16 COMMENT 'DEFAULT Zoom Level for the connection',
  `default_longitude` DOUBLE NULL COMMENT 'DEFAULT longitude for the agents placed on the map',
  `default_latitude` DOUBLE NULL COMMENT 'DEFAULT latitude for the agents placed on the map',
  `default_altitude` DOUBLE NULL COMMENT 'DEFAULT altitude for the agents placed on the map',
  `initial_longitude` DOUBLE NULL COMMENT "longitude of the center of the map when it\'s loaded",
  `initial_latitude` DOUBLE NULL COMMENT "latitude of the center of the map when it\'s loaded",
  `initial_altitude` DOUBLE NULL COMMENT "altitude of the center of the map when it\'s loaded",
  `group_id` INT NOT NULL DEFAULT 0 COMMENT 'Group that owns the map',
  PRIMARY KEY (`id_tmap_connection`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='Table to store the map connection information';

-- -----------------------------------------------------
-- Table `tgis_map_has_tgis_map_con` (tgis_map_has_tgis_map_connection)
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_has_tgis_map_con` (
  `tgis_map_id_tgis_map` INT NOT NULL COMMENT 'reference to tgis_map',
  `tgis_map_con_id_tmap_con` INT NOT NULL COMMENT 'reference to tgis_map_connection',
  `modification_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Modification Time of the Connection',
  `default_map_connection` TINYINT NULL DEFAULT FALSE COMMENT 'Flag to mark the DEFAULT map connection of a map',
  PRIMARY KEY (`tgis_map_id_tgis_map`, `tgis_map_con_id_tmap_con`),
  INDEX `fk_tgis_map_has_tgis_map_connection_tgis_map1` (`tgis_map_id_tgis_map` ASC),
  INDEX `fk_tgis_map_has_tgis_map_connection_tgis_map_connection1` (`tgis_map_con_id_tmap_con` ASC),
  CONSTRAINT `fk_tgis_map_has_tgis_map_connection_tgis_map1`
    FOREIGN KEY (`tgis_map_id_tgis_map`)
    REFERENCES `tgis_map` (`id_tgis_map`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tgis_map_has_tgis_map_connection_tgis_map_connection1`
    FOREIGN KEY (`tgis_map_con_id_tmap_con`)
    REFERENCES `tgis_map_connection` (`id_tmap_connection`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='Table to asociate a connection to a gis map';

-- -----------------------------------------------------
-- Table `tgis_map_layer`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_layer` (
  `id_tmap_layer` INT NOT NULL AUTO_INCREMENT COMMENT 'table id',
  `layer_name` VARCHAR(45) NOT NULL COMMENT 'Name of the layer ',
  `view_layer` TINYINT NOT NULL DEFAULT TRUE COMMENT 'True if the layer must be shown',
  `layer_stack_order` TINYINT NULL DEFAULT 0 COMMENT 'Number of order of the layer in the layer stack, bigger means upper on the stack.',
  `tgis_map_id_tgis_map` INT NOT NULL COMMENT 'reference to the map containing the layer',
  `tgrupo_id_grupo` MEDIUMINT NOT NULL COMMENT 'reference to the group shown in the layer',
  PRIMARY KEY (`id_tmap_layer`),
  INDEX `fk_tmap_layer_tgis_map1` (`tgis_map_id_tgis_map` ASC),
  CONSTRAINT `fk_tmap_layer_tgis_map1`
    FOREIGN KEY (`tgis_map_id_tgis_map` )
    REFERENCES `tgis_map` (`id_tgis_map` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='Table containing information about the map layers';

-- -----------------------------------------------------
-- Table `tgis_map_layer_has_tagente`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_layer_has_tagente` (
  `tgis_map_layer_id_tmap_layer` INT NOT NULL,
  `tagente_id_agente` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`tgis_map_layer_id_tmap_layer`, `tagente_id_agente`),
  INDEX `fk_tgis_map_layer_has_tagente_tgis_map_layer1` (`tgis_map_layer_id_tmap_layer` ASC),
  INDEX `fk_tgis_map_layer_has_tagente_tagente1` (`tagente_id_agente` ASC),
  CONSTRAINT `fk_tgis_map_layer_has_tagente_tgis_map_layer1`
    FOREIGN KEY (`tgis_map_layer_id_tmap_layer` )
    REFERENCES `tgis_map_layer` (`id_tmap_layer` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tgis_map_layer_has_tagente_tagente1`
    FOREIGN KEY (`tagente_id_agente` )
    REFERENCES `tagente` (`id_agente` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='Table to define wich agents are shown in a layer';

-- -----------------------------------------------------
-- Table `tgis_map_layer_groups`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgis_map_layer_groups` (
  `layer_id` INT NOT NULL,
  `group_id` MEDIUMINT UNSIGNED NOT NULL,
  `agent_id` INT UNSIGNED NOT NULL COMMENT 'Used to link the position to the group',
  PRIMARY KEY (`layer_id`, `group_id`),
  FOREIGN KEY (`layer_id`)
    REFERENCES `tgis_map_layer` (`id_tmap_layer`)
    ON DELETE CASCADE,
  FOREIGN KEY (`group_id`)
    REFERENCES `tgrupo` (`id_grupo`)
    ON DELETE CASCADE,
  FOREIGN KEY (`agent_id`)
    REFERENCES `tagente` (`id_agente`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tgroup_stat`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgroup_stat` (
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `modules` INT UNSIGNED NOT NULL DEFAULT 0,
  `normal` INT UNSIGNED NOT NULL DEFAULT 0,
  `critical` INT UNSIGNED NOT NULL DEFAULT 0,
  `warning` INT UNSIGNED NOT NULL DEFAULT 0,
  `unknown` INT UNSIGNED NOT NULL DEFAULT 0,
  `non-init` INT UNSIGNED NOT NULL DEFAULT 0,
  `alerts` INT UNSIGNED NOT NULL DEFAULT 0,
  `alerts_fired` INT UNSIGNED NOT NULL DEFAULT 0,
  `agents` INT UNSIGNED NOT NULL DEFAULT 0,
  `agents_unknown` INT UNSIGNED NOT NULL DEFAULT 0,
  `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_group`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT = 'Table to store global system stats per group';

-- ----------------------------------------------------------------------
-- Table `tnetwork_map`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_map` (
  `id_networkmap` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` VARCHAR(255)  NOT NULL,
  `name` VARCHAR(100)  NOT NULL,
  `type` VARCHAR(20)  NOT NULL,
  `layout` VARCHAR(20)  NOT NULL,
  `nooverlap` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `simple` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `regenerate` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `font_size` INT UNSIGNED NOT NULL DEFAULT 12,
  `id_group` INT  NOT NULL DEFAULT 0,
  `id_module_group` INT  NOT NULL DEFAULT 0,  
  `id_policy` INT  NOT NULL DEFAULT 0,
  `depth` VARCHAR(20)  NOT NULL,
  `only_modules_with_alerts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `hide_policy_modules` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `zoom` DOUBLE NOT NULL DEFAULT 1,
  `distance_nodes` DOUBLE NOT NULL DEFAULT 2.5,
  `center` INT UNSIGNED NOT NULL DEFAULT 0,
  `contracted_nodes` TEXT,
  `show_snmp_modules` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `text_filter` VARCHAR(100)  NOT NULL DEFAULT '',
  `dont_show_subgroups` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `pandoras_children` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `show_groups` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `show_modules` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent` INT  NOT NULL DEFAULT 0,
  `server_name` VARCHAR(100)  NOT NULL,
  `show_modulegroup` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `l2_network` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_tag` INT DEFAULT 0,
  `store_group` INT DEFAULT 0,
  PRIMARY KEY  (`id_networkmap`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tsnmp_filter`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tsnmp_filter` (
  `id_snmp_filter` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `description` VARCHAR(255) DEFAULT '',
  `filter` VARCHAR(255) DEFAULT '',
  `unified_filters_id` INT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_snmp_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tagent_custom_fields`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_custom_fields` (
  `id_field` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL DEFAULT '',
  `display_on_front` TINYINT NOT NULL DEFAULT 0,
  `is_password_type` TINYINT NOT NULL DEFAULT 0,
  `combo_values` TEXT ,
  `is_link_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_field`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tagent_custom_data`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_custom_data` (
  `id_field` INT UNSIGNED NOT NULL,
  `id_agent` INT UNSIGNED NOT NULL,
  `description` TEXT,
  FOREIGN KEY (`id_field`) REFERENCES tagent_custom_fields(`id_field`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_agent`) REFERENCES tagente(`id_agente`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY  (`id_field`, `id_agent`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `ttag`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttag` ( 
  `id_tag` INT unsigned NOT NULL AUTO_INCREMENT, 
  `name` TEXT , 
  `description` TEXT, 
  `url` MEDIUMTEXT,
  `email` TEXT NULL,
  `phone` TEXT NULL,
  `previous_name` TEXT NULL,
  PRIMARY KEY  (`id_tag`),
  INDEX `ttag_name` (name(15))
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4; 

-- -----------------------------------------------------
-- Table `ttag_module`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttag_module` (
  `id_tag` INT NOT NULL,
  `id_agente_modulo` INT NOT NULL DEFAULT 0,
  `id_policy_module` INT NOT NULL DEFAULT 0,
  PRIMARY KEY  (id_tag, id_agente_modulo),
  KEY `idx_id_agente_modulo` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4; 

-- ---------------------------------------------------------------------
-- Table `ttag_policy_module`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttag_policy_module` ( 
  `id_tag` INT NOT NULL, 
  `id_policy_module` INT NOT NULL DEFAULT 0, 
  PRIMARY KEY  (id_tag, id_policy_module),
  KEY `idx_id_policy_module` (`id_policy_module`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4; 

-- ---------------------------------------------------------------------
-- Table `tnetflow_filter`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetflow_filter` (
  `id_sg`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_name` VARCHAR(600) NOT NULL DEFAULT '0',
  `id_group` INT,
  `ip_dst` TEXT,
  `ip_src` TEXT,
  `dst_port` TEXT,
  `src_port` TEXT,
  `router_ip` TEXT,
  `advanced_filter` TEXT,
  `filter_args` TEXT,
  `aggregate` VARCHAR(60),
  `netflow_monitoring` TINYINT UNSIGNED NOT NULL default 0,
  `traffic_max` INTEGER NOT NULL default 0,
  `traffic_critical` FLOAT(20,2) NOT NULL default 0,
  `traffic_warning` FLOAT(20,2) NOT NULL default 0,
  `netflow_monitoring_interval` INT UNSIGNED NOT NULL DEFAULT 300,
  `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tnetflow_report`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetflow_report` (
  `id_report` INT UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_name` VARCHAR(150) NOT NULL DEFAULT '',
  `description` TEXT,
  `id_group` INT,
  `server_name` TEXT,
  PRIMARY KEY(`id_report`)  
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tnetflow_report_content`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetflow_report_content` (
  `id_rc` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_report` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_filter` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT,
  `date` BIGINT NOT NULL DEFAULT 0,
  `period` INT NOT NULL DEFAULT 0,
  `max` INT NOT NULL DEFAULT 0,
  `show_graph` VARCHAR(60),
  `order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY(`id_rc`),
  FOREIGN KEY (`id_report`) REFERENCES tnetflow_report(`id_report`)
  ON DELETE CASCADE,
  FOREIGN KEY (`id_filter`) REFERENCES tnetflow_filter(`id_sg`)
  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tpassword_history`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpassword_history` (
  `id_pass`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL,
  `password` VARCHAR(45) DEFAULT NULL,
  `date_begin` DATETIME,
  `date_end` DATETIME,
  PRIMARY KEY  (`id_pass`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tevent_response`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_response` (
  `id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(600) NOT NULL DEFAULT '',
  `description` TEXT,
  `target` TEXT,
  `type` VARCHAR(60) NOT NULL,
  `id_group` MEDIUMINT NOT NULL DEFAULT 0,
  `modal_width` INT  NOT NULL DEFAULT 0,
  `modal_height` INT  NOT NULL DEFAULT 0,
  `new_window` TINYINT  NOT NULL DEFAULT 0,
  `params` TEXT  NOT NULL,
  `server_to_exec` INT UNSIGNED NOT NULL DEFAULT 0,
  `command_timeout` INT UNSIGNED NOT NULL DEFAULT 90,
  `display_command` TINYINT DEFAULT 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tcategory`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcategory` ( 
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
  `name` VARCHAR(600) NOT NULL DEFAULT '', 
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4; 

-- ---------------------------------------------------------------------
-- Table `tupdate_settings`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tupdate_settings` ( 
  `key` VARCHAR(255) DEFAULT '', 
  `value` VARCHAR(255) DEFAULT '', PRIMARY KEY (`key`) 
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tupdate_journal`
-- ---------------------------------------------------------------------
CREATE TABLE `tupdate_journal` (
  `id` SERIAL,
  `utimestamp` BIGINT DEFAULT 0,
  `version` VARCHAR(25) DEFAULT '',
  `type` VARCHAR(25) DEFAULT '',
  `origin` VARCHAR(25) DEFAULT '',
  `id_user` VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `talert_snmp_action`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS  `talert_snmp_action` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_alert_snmp` INT UNSIGNED NOT NULL DEFAULT 0,
  `alert_type` INT UNSIGNED NOT NULL DEFAULT 0,
  `al_field1` TEXT,
  `al_field2` TEXT,
  `al_field3` TEXT,
  `al_field4` TEXT,
  `al_field5` TEXT,
  `al_field6` TEXT,
  `al_field7` TEXT,
  `al_field8` TEXT,
  `al_field9` TEXT,
  `al_field10` TEXT,
  `al_field11` TEXT,
  `al_field12` TEXT,
  `al_field13` TEXT,
  `al_field14` TEXT,
  `al_field15` TEXT,
  `al_field16` TEXT,
  `al_field17` TEXT,
  `al_field18` TEXT,
  `al_field19` TEXT,
  `al_field20` TEXT,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tsessions_php`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tsessions_php` (
  `id_session` CHAR(52) NOT NULL,
  `last_active` INT NOT NULL,
  `data` TEXT,
  PRIMARY KEY (`id_session`)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tmap`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmap` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group` TEXT NOT NULL DEFAULT '',
  `id_user` VARCHAR(255) NOT NULL DEFAULT '',
  `type` INT UNSIGNED NOT NULL DEFAULT 0,
  `subtype` INT UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(250) DEFAULT '',
  `description` TEXT,
  `height` INT UNSIGNED NOT NULL DEFAULT 0,
  `width` INT UNSIGNED NOT NULL DEFAULT 0,
  `center_x` INT NOT NULL DEFAULT 0,
  `center_y` INT NOT NULL DEFAULT 0,
  `background` VARCHAR(250) DEFAULT '',
  `background_options` INT UNSIGNED NOT NULL DEFAULT 0,
  `source_period` INT UNSIGNED NOT NULL DEFAULT 0,
  `source` INT UNSIGNED NOT NULL DEFAULT 0,
  `source_data`  VARCHAR(250) DEFAULT '',
  `generation_method` INT UNSIGNED NOT NULL DEFAULT 0,
  `generated` INT UNSIGNED NOT NULL DEFAULT 0,
  `filter` TEXT,
  `id_group_map` INT UNSIGNED NOT NULL DEFAULT 0,
  `refresh_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `titem`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `titem` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_map` INT UNSIGNED NOT NULL DEFAULT 0,
  `x` INT NOT NULL DEFAULT 0,
  `y` INT NOT NULL DEFAULT 0,
  `z` INT NOT NULL DEFAULT 0,
  `deleted` INT unsigned NOT NULL DEFAULT 0,
  `type` INT UNSIGNED NOT NULL DEFAULT 0,
  `refresh` INT UNSIGNED NOT NULL DEFAULT 0,
  `source` INT UNSIGNED NOT NULL DEFAULT 0,
  `source_data` VARCHAR(250) DEFAULT '',
  `options` TEXT,
  `style` TEXT,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `trel_item`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trel_item` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_parent` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_child` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_map` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_parent_source_data` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_child_source_data` INT UNSIGNED NOT NULL DEFAULT 0,
  `parent_type` INT UNSIGNED NOT NULL DEFAULT 0,
  `child_type` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_item` INT UNSIGNED NOT NULL DEFAULT 0,
  `deleted` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tlocal_component`
-- -----------------------------------------------------
-- tlocal_component is a repository of local modules for
-- physical agents on Windows / Unix physical agents
CREATE TABLE IF NOT EXISTS `tlocal_component` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT,
  `data` MEDIUMTEXT,
  `description` VARCHAR(1024) DEFAULT NULL,
  `id_os` INT UNSIGNED DEFAULT 0,
  `os_version` VARCHAR(100) DEFAULT '',
  `id_network_component_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `type` SMALLINT NOT NULL DEFAULT 6,
  `max` BIGINT NOT NULL DEFAULT 0,
  `min` BIGINT NOT NULL DEFAULT 0,
  `module_interval` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `id_module_group` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `history_data` TINYINT UNSIGNED DEFAULT 1,
  `min_warning` DOUBLE DEFAULT 0,
  `max_warning` DOUBLE DEFAULT 0,
  `str_warning` TEXT,
  `min_critical` DOUBLE DEFAULT 0,
  `max_critical` DOUBLE DEFAULT 0,
  `str_critical` TEXT,
  `min_ff_event` INT UNSIGNED DEFAULT 0,
  `post_process` DOUBLE DEFAULT 0,
  `unit` TEXT,
  `wizard_level` ENUM('basic','advanced','nowizard') DEFAULT 'nowizard',
  `macros` TEXT,
  `critical_instructions` TEXT ,
  `warning_instructions` TEXT ,
  `unknown_instructions` TEXT ,
  `critical_inverse` TINYINT UNSIGNED DEFAULT 0,
  `warning_inverse` TINYINT UNSIGNED DEFAULT 0,
  `id_category` INT DEFAULT 0,
  `tags` TEXT ,
  `disabled_types_event` TEXT ,
  `min_ff_event_normal` INT UNSIGNED DEFAULT 0,
  `min_ff_event_warning` INT UNSIGNED DEFAULT 0,
  `min_ff_event_critical` INT UNSIGNED DEFAULT 0,
  `ff_type` TINYINT UNSIGNED DEFAULT 0,
  `each_ff` TINYINT UNSIGNED DEFAULT 0,
  `ff_timeout` INT UNSIGNED DEFAULT 0,
  `dynamic_interval` INT UNSIGNED DEFAULT 0,
  `dynamic_max` INT DEFAULT 0,
  `dynamic_min` INT DEFAULT 0,
  `dynamic_next` BIGINT NOT NULL DEFAULT 0,
  `dynamic_two_tailed` TINYINT UNSIGNED DEFAULT 0,
  `prediction_sample_window` INT DEFAULT 0,
  `prediction_samples` INT DEFAULT 0,
  `prediction_threshold` INT DEFAULT 0,
  `percentage_critical` TINYINT UNSIGNED DEFAULT 0,
  `percentage_warning` TINYINT UNSIGNED DEFAULT 0,
  `warning_time` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_network_component_group`) REFERENCES tnetwork_component_group(`id_sg`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tpolicy_modules`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_policy` INT UNSIGNED NOT NULL DEFAULT 0,
  `configuration_data` MEDIUMTEXT,
  `id_tipo_modulo` SMALLINT NOT NULL DEFAULT 0,
  `description` VARCHAR(1024) NOT NULL DEFAULT '',
  `name` VARCHAR(200) NOT NULL DEFAULT '',
  `unit` TEXT ,
  `max` BIGINT DEFAULT 0,
  `min` BIGINT DEFAULT 0,
  `module_interval` INT UNSIGNED DEFAULT 0,
  `ip_target` VARCHAR(100) DEFAULT '',
  `tcp_port` INT UNSIGNED DEFAULT 0,
  `tcp_send` TEXT ,
  `tcp_rcv` TEXT ,
  `snmp_community` VARCHAR(100) DEFAULT '',
  `snmp_oid` VARCHAR(255) DEFAULT '0',
  `id_module_group` INT UNSIGNED DEFAULT 0,
  `flag` TINYINT UNSIGNED DEFAULT 1,
  `id_module` INT DEFAULT 0,
  `disabled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_export` SMALLINT UNSIGNED DEFAULT 0,
  `plugin_user` TEXT ,
  `plugin_pass` TEXT ,
  `plugin_parameter` TEXT,
  `id_plugin` INT DEFAULT 0,
  `post_process` DOUBLE DEFAULT 0,
  `prediction_module` BIGINT DEFAULT 0,
  `max_timeout` INT UNSIGNED DEFAULT 0,
  `max_retries` INT UNSIGNED DEFAULT 0,
  `custom_id` VARCHAR(255) DEFAULT '',
  `history_data` TINYINT UNSIGNED DEFAULT 1,
  `min_warning` DOUBLE DEFAULT 0,
  `max_warning` DOUBLE DEFAULT 0,
  `str_warning` TEXT ,
  `min_critical` DOUBLE DEFAULT 0,
  `max_critical` DOUBLE DEFAULT 0,
  `str_critical` TEXT ,
  `min_ff_event` INT UNSIGNED DEFAULT 0,
  `custom_string_1` TEXT ,
  `custom_string_2` TEXT ,
  `custom_string_3` TEXT ,
  `custom_integer_1` INT DEFAULT 0,
  `custom_integer_2` INT DEFAULT 0,
  `pending_delete` TINYINT DEFAULT 0,
  `critical_instructions` TEXT ,
  `warning_instructions` TEXT ,
  `unknown_instructions` TEXT ,
  `critical_inverse` TINYINT UNSIGNED DEFAULT 0,
  `warning_inverse` TINYINT UNSIGNED DEFAULT 0,
  `id_category` INT DEFAULT 0,
  `module_ff_interval` INT UNSIGNED DEFAULT 0,
  `quiet` TINYINT NOT NULL DEFAULT 0,
  `cron_interval` VARCHAR(100) DEFAULT '',
  `macros` TEXT,
  `disabled_types_event` TEXT ,
  `module_macros` TEXT ,
  `min_ff_event_normal` INT UNSIGNED DEFAULT 0,
  `min_ff_event_warning` INT UNSIGNED DEFAULT 0,
  `min_ff_event_critical` INT UNSIGNED DEFAULT 0,
  `ff_type` TINYINT UNSIGNED DEFAULT 0,
  `each_ff` TINYINT UNSIGNED DEFAULT 0,
  `ff_timeout` INT UNSIGNED DEFAULT 0,
  `dynamic_interval` INT UNSIGNED DEFAULT 0,
  `dynamic_max` INT DEFAULT 0,
  `dynamic_min` INT DEFAULT 0,
  `dynamic_next` BIGINT NOT NULL DEFAULT 0,
  `dynamic_two_tailed` TINYINT UNSIGNED DEFAULT 0,
  `prediction_sample_window` INT DEFAULT 0,
  `prediction_samples` INT DEFAULT 0,
  `prediction_threshold` INT DEFAULT 0,
  `cps` INT NOT NULL DEFAULT 0,
  `percentage_warning` TINYINT UNSIGNED DEFAULT 0,
  `percentage_critical` TINYINT UNSIGNED DEFAULT 0,
  `warning_time` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY  (`id`),
  KEY `main_idx` (`id_policy`),
  UNIQUE (`id_policy`, `name`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tpolicies`
-- ---------------------------------------------------------------------
-- 'status' could be 0 (without changes, updated), 1 (needy update only database) or 2 (needy update database and conf files)
CREATE TABLE IF NOT EXISTS `tpolicies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT ,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `id_group` INT UNSIGNED DEFAULT 0,
  `status` INT UNSIGNED NOT NULL DEFAULT 0,
  `force_apply` TINYINT DEFAULT 0,
  `apply_to_secondary_groups` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tpolicy_alerts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_alerts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_policy` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_policy_module` INT UNSIGNED DEFAULT 0,
  `id_alert_template` INT UNSIGNED DEFAULT 0,
  `name_extern_module` TEXT ,
  `disabled` TINYINT DEFAULT 0,
  `standby` TINYINT DEFAULT 0,
  `pending_delete` TINYINT DEFAULT 0,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_alert_template`) REFERENCES talert_templates(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_policy`) REFERENCES tpolicies(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tpolicy_agents`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_agents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_policy` INT UNSIGNED DEFAULT 0,
  `id_agent` INT UNSIGNED DEFAULT 0,
  `policy_applied` TINYINT UNSIGNED DEFAULT 0,
  `pending_delete` TINYINT UNSIGNED DEFAULT 0,
  `last_apply_utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_node` INT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`),
  UNIQUE (`id_policy`, `id_agent`, `id_node`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tpolicy_groups`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_policy` INT UNSIGNED DEFAULT 0,
  `id_group` INT UNSIGNED DEFAULT 0,
  `policy_applied` TINYINT UNSIGNED DEFAULT 0,
  `pending_delete` TINYINT UNSIGNED DEFAULT 0,
  `last_apply_utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`),
  UNIQUE (`id_policy`, `id_group`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;


-- -----------------------------------------------------
-- Table `tpolicy_group_agents`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_group_agents` (
    `id` SERIAL,
    `id_policy` INT UNSIGNED,
    `id_agent` INT UNSIGNED,
	`direct` TINYINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (`id_policy`) REFERENCES `tpolicies`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`)
        ON DELETE CASCADE ON UPDATE CASCADE		
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tdashboard`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tdashboard` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL DEFAULT '',
  `id_user` VARCHAR(255) NOT NULL DEFAULT '',
  `id_group` INT NOT NULL DEFAULT 0,
  `active` TINYINT NOT NULL DEFAULT 0,
  `cells` INT UNSIGNED DEFAULT 0,
  `cells_slideshow` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tdatabase`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tdatabase` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `host` VARCHAR(255) DEFAULT '',
  `label` VARCHAR(255) DEFAULT '',
  `os_port` INT UNSIGNED NOT NULL DEFAULT 22,
  `os_user` VARCHAR(255) DEFAULT '',
  `db_port` INT UNSIGNED NOT NULL DEFAULT 3306,
  `status` TINYINT UNSIGNED DEFAULT 0,
  `action` TINYINT UNSIGNED DEFAULT 0,
  `ssh_key` TEXT,
  `ssh_pubkey` TEXT,
  `ssh_status` TINYINT UNSIGNED DEFAULT 0,
  `last_error` TEXT,
  `db_status` TINYINT UNSIGNED DEFAULT 0,
  `replication_status` TINYINT UNSIGNED DEFAULT 0,
  `replication_delay` BIGINT DEFAULT 0,
  `master` TINYINT UNSIGNED DEFAULT 0,
  `utimestamp` BIGINT DEFAULT 0,
  `mysql_version` VARCHAR(10) DEFAULT '',
  `pandora_version` VARCHAR(10) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `twidget`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `twidget` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_name` VARCHAR(60) NOT NULL DEFAULT '',
  `unique_name` VARCHAR(60) NOT NULL DEFAULT '',
  `description` TEXT ,
  `options` TEXT ,
  `page` VARCHAR(120) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `twidget_dashboard`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `twidget_dashboard` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `position` TEXT ,
  `options` LONGTEXT ,
  `order` INT NOT NULL DEFAULT 0,
  `id_dashboard` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_widget` INT UNSIGNED NOT NULL DEFAULT 0,
  `prop_width` DOUBLE NOT NULL DEFAULT 0.32,
  `prop_height` DOUBLE NOT NULL DEFAULT 0.32,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_dashboard`) REFERENCES tdashboard(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tmodule_inventory`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule_inventory` (
  `id_module_inventory` INT NOT NULL AUTO_INCREMENT,
  `id_os` INT UNSIGNED DEFAULT NULL,
  `name` TEXT ,
  `description` TEXT ,
  `interpreter` VARCHAR(100) DEFAULT '',
  `data_format` TEXT ,
  `code` BLOB NOT NULL,
  `block_mode` INT NOT NULL DEFAULT 0,
  `script_mode` INT NOT NULL DEFAULT 1,
  `script_path` VARCHAR(1000) DEFAULT '',
  PRIMARY KEY  (`id_module_inventory`),
  FOREIGN KEY (`id_os`) REFERENCES tconfig_os(`id_os`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tagent_module_inventory`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_module_inventory` (
  `id_agent_module_inventory` INT NOT NULL AUTO_INCREMENT,
  `id_agente` INT UNSIGNED NOT NULL,
  `id_module_inventory` INT NOT NULL,
  `target` VARCHAR(100) DEFAULT '',
  `interval` INT UNSIGNED NOT NULL DEFAULT 3600,
  `username` VARCHAR(100) DEFAULT '',
  `password` VARCHAR(100) DEFAULT '',
  `data` MEDIUMBLOB NOT NULL,
  `timestamp` DATETIME DEFAULT '1970-01-01 00:00:00',
  `utimestamp` BIGINT DEFAULT 0,
  `flag` TINYINT UNSIGNED DEFAULT 1,
  `id_policy_module_inventory` INT NOT NULL DEFAULT 0,
  `custom_fields` MEDIUMBLOB NOT NULL,
  PRIMARY KEY  (`id_agent_module_inventory`),
  FOREIGN KEY (`id_agente`) REFERENCES tagente(`id_agente`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_module_inventory`) REFERENCES tmodule_inventory(`id_module_inventory`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tinventory_alert`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tinventory_alert`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_module_inventory` INT NOT NULL,
  `actions` TEXT ,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `condition` ENUM('WHITE_LIST', 'BLACK_LIST', 'MATCH') NOT NULL DEFAULT 'WHITE_LIST',
  `value` TEXT ,
  `name` TINYTEXT ,
  `description` TEXT ,
  `time_threshold` INT NOT NULL DEFAULT 0,
  `last_fired` TEXT ,
  `disable_event` TINYINT UNSIGNED DEFAULT 0,
  `enabled` TINYINT UNSIGNED DEFAULT 1,
  `alert_groups` TEXT ,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_module_inventory`) REFERENCES tmodule_inventory(`id_module_inventory`)
    ON DELETE CASCADE ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tpolicy_modules_inventory`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_modules_inventory` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_policy` INT UNSIGNED NOT NULL,
  `id_module_inventory` INT NOT NULL,
  `interval` INT UNSIGNED NOT NULL DEFAULT 3600,
  `username` VARCHAR(100) DEFAULT '',
  `password` VARCHAR(100) DEFAULT '',
  `pending_delete` TINYINT DEFAULT 0,
  `custom_fields` MEDIUMBLOB NOT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_policy`) REFERENCES tpolicies(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_module_inventory`) REFERENCES tmodule_inventory(`id_module_inventory`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tagente_datos_inventory`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos_inventory` (
  `id_agent_module_inventory` INT NOT NULL,
  `data` MEDIUMBLOB NOT NULL,
  `utimestamp` BIGINT DEFAULT 0,
  `timestamp` DATETIME DEFAULT '1970-01-01 00:00:00',
  KEY `idx_id_agent_module` (`id_agent_module_inventory`),
  KEY `idx_utimestamp` USING BTREE (`utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `ttrap_custom_values`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttrap_custom_values` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `oid` VARCHAR(255) NOT NULL DEFAULT '',
  `custom_oid` VARCHAR(255) NOT NULL DEFAULT '',
  `text` VARCHAR(255) DEFAULT '',
  `description` VARCHAR(255) DEFAULT '',
  `severity` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  CONSTRAINT oid_custom_oid UNIQUE(oid, custom_oid),
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tmetaconsole_setup`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmetaconsole_setup` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `server_name` TEXT,
  `server_url` TEXT,
  `dbuser` TEXT,
  `dbpass` TEXT,
  `dbhost` TEXT,
  `dbport` TEXT,
  `dbname` TEXT,
  `meta_dbuser` TEXT,
  `meta_dbpass` TEXT,
  `meta_dbhost` TEXT,
  `meta_dbport` TEXT,
  `meta_dbname` TEXT,
  `auth_token` TEXT,
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `api_password` TEXT,
  `disabled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `unified` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `server_uid` TEXT ,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB
COMMENT = 'Table to store metaconsole sources'
DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tprofile_view`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tprofile_view` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_profile` INT UNSIGNED NOT NULL DEFAULT 0,
  `sec` TEXT ,
  `sec2` TEXT ,
  `sec3` TEXT ,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB 
COMMENT = 'Table to define by each profile defined in Pandora, to which sec/page has access independently of its ACL (for showing in the console or not). By DEFAULT have access to all pages allowed by ACL, if forbidden here, then pages are not shown.' 
DEFAULT CHARSET=UTF8MB4;


-- ---------------------------------------------------------------------
-- Table `tservice`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tservice` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `description` TEXT ,
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `critical` DOUBLE NOT NULL DEFAULT 0,
  `warning` DOUBLE NOT NULL DEFAULT 0,
  `unknown_as_critical` TINYINT NOT NULL DEFAULT 0,
  `service_interval` DOUBLE NOT NULL DEFAULT 0,
  `service_value` DOUBLE NOT NULL DEFAULT 0,
  `status` TINYINT NOT NULL DEFAULT -1,
  `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  `auto_calculate` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `id_agent_module` INT UNSIGNED NOT NULL DEFAULT 0,
  `sla_interval` DOUBLE NOT NULL DEFAULT 0,
  `sla_id_module` INT UNSIGNED NOT NULL DEFAULT 0,
  `sla_value_id_module` INT UNSIGNED NOT NULL DEFAULT 0,
  `sla_limit` DOUBLE NOT NULL DEFAULT 100,
  `id_template_alert_warning` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_template_alert_critical` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_template_alert_unknown` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_template_alert_critical_sla` INT UNSIGNED NOT NULL DEFAULT 0,
  `quiet` TINYINT NOT NULL DEFAULT 0,
  `cps` INT NOT NULL DEFAULT 0,
  `cascade_protection` TINYINT NOT NULL DEFAULT 0,
  `evaluate_sla` INT NOT NULL DEFAULT 0,
  `is_favourite` TINYINT NOT NULL DEFAULT 0,
  `enable_sunburst` TINYINT NOT NULL DEFAULT 0,
  `asynchronous` TINYINT NOT NULL DEFAULT 0,
  `rca` TEXT,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB 
COMMENT = 'Table to define services to monitor' 
DEFAULT CHARSET=UTF8MB4;


-- ---------------------------------------------------------------------
-- Table `tservice_element`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tservice_element` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_service` INT UNSIGNED NOT NULL,
  `weight_ok` DOUBLE NOT NULL DEFAULT 0,
  `weight_warning` DOUBLE NOT NULL DEFAULT 0,
  `weight_critical` DOUBLE NOT NULL DEFAULT 0,
  `weight_unknown` DOUBLE NOT NULL DEFAULT 0,
  `description` TEXT ,
  `id_agente_modulo` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_service_child` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_server_meta` INT  unsigned NOT NULL DEFAULT 0,
  `rules` TEXT,
  PRIMARY KEY  (`id`),
  INDEX `IDX_tservice_element` (`id_service`,`id_agente_modulo`),
  INDEX `tservice_element_service` (`id_service`),
  INDEX `tservice_element_agent` (`id_agent`),
  INDEX `tservice_element_am` (`id_agente_modulo`)
) ENGINE=InnoDB 
COMMENT = 'Table to define the modules and the weights of the modules that define a service' 
DEFAULT CHARSET=UTF8MB4;


-- ---------------------------------------------------------------------
-- Table `tcollection`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcollection` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `short_name` VARCHAR(100) NOT NULL DEFAULT '',
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` MEDIUMTEXT,
  `status` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
-- status: 0 - Not apply
-- status: 1 - Applied

-- ---------------------------------------------------------------------
-- Table `tpolicy_collections`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_collections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_policy` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_collection` INT UNSIGNED DEFAULT 0,
  `pending_delete` TINYINT DEFAULT 0,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_policy`) REFERENCES `tpolicies` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_collection`) REFERENCES `tcollection` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tpolicy_alerts_actions`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_alerts_actions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_policy_alert` INT UNSIGNED NOT NULL,
  `id_alert_action` INT UNSIGNED NOT NULL,
  `fires_min` INT UNSIGNED DEFAULT 0,
  `fires_max` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_policy_alert`) REFERENCES `tpolicy_alerts` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_alert_action`) REFERENCES `talert_actions` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tpolicy_plugins`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_plugins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_policy` INT UNSIGNED DEFAULT 0,
  `plugin_exec` TEXT,
  `pending_delete` TINYINT DEFAULT 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tsesion_extended`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tsesion_extended` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_sesion` INT UNSIGNED NOT NULL,
  `extended_info` TEXT ,
  `hash` VARCHAR(255) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY idx_session (id_sesion)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tskin`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tskin` ( 
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
  `name` TEXT ,
  `relative_path` TEXT , 
  `description` TEXT ,
  `disabled` TINYINT NOT NULL DEFAULT 0, 
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tpolicy_queue`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_queue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_policy` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent` INT UNSIGNED NOT NULL DEFAULT 0,
  `operation` VARCHAR(15) DEFAULT '',
  `progress` INT NOT NULL DEFAULT 0,
  `end_utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  `priority` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tevent_rule`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_rule` (
  `id_event_rule` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_event_alert` INT UNSIGNED NOT NULL,
  `operation` ENUM('NOP', 'AND','OR','XOR','NAND','NOR','NXOR'),
  `order` INT UNSIGNED DEFAULT 0,
  `window` INT NOT NULL DEFAULT 0,
  `count` INT NOT NULL DEFAULT 1,
  `agent` TEXT,
  `id_usuario` TEXT,
  `id_grupo` TEXT,
  `evento` TEXT,
  `event_type` TEXT,
  `module` TEXT,
  `alert` TEXT,
  `criticity` TEXT,
  `user_comment` TEXT,
  `id_tag` TEXT,
  `name` TEXT,
  `group_recursion` TEXT,
  `log_content` TEXT,
  `log_source` TEXT,
  `log_agent` TEXT,
  `operator_agent` TEXT COMMENT 'Operator for agent',
  `operator_id_usuario` TEXT COMMENT 'Operator for id_usuario',
  `operator_id_grupo` TEXT COMMENT 'Operator for id_grupo',
  `operator_evento` TEXT COMMENT 'Operator for evento',
  `operator_event_type` TEXT COMMENT 'Operator for event_type',
  `operator_module` TEXT COMMENT 'Operator for module',
  `operator_alert` TEXT COMMENT 'Operator for alert',
  `operator_criticity` TEXT COMMENT 'Operator for criticity',
  `operator_user_comment` TEXT COMMENT 'Operator for user_comment',
  `operator_id_tag` TEXT COMMENT 'Operator for id_tag',
  `operator_log_content` TEXT COMMENT 'Operator for log_content',
  `operator_log_source` TEXT COMMENT 'Operator for log_source',
  `operator_log_agent` TEXT COMMENT 'Operator for log_agent',
  PRIMARY KEY  (`id_event_rule`),
  KEY `idx_id_event_alert` (`id_event_alert`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tevent_alert`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_alert` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT ,
  `description` MEDIUMTEXT,
  `order` INT UNSIGNED DEFAULT 0,
  `mode` ENUM('PASS','DROP'),
  `field1` TEXT ,
  `field2` TEXT ,
  `field3` TEXT ,
  `field4` TEXT ,
  `field5` TEXT ,
  `field6` TEXT ,
  `field7` TEXT ,
  `field8` TEXT ,
  `field9` TEXT ,
  `field10` TEXT ,
  `time_threshold` INT NOT NULL DEFAULT 86400,
  `max_alerts` INT UNSIGNED NOT NULL DEFAULT 1,
  `min_alerts` INT UNSIGNED NOT NULL DEFAULT 0,
  `time_from` time DEFAULT '00:00:00',
  `time_to` time DEFAULT '00:00:00',
  `monday` TINYINT DEFAULT 1,
  `tuesday` TINYINT DEFAULT 1,
  `wednesday` TINYINT DEFAULT 1,
  `thursday` TINYINT DEFAULT 1,
  `friday` TINYINT DEFAULT 1,
  `saturday` TINYINT DEFAULT 1,
  `sunday` TINYINT DEFAULT 1,
  `recovery_notify` TINYINT DEFAULT 0,
  `field1_recovery` TEXT,
  `field2_recovery` TEXT,
  `field3_recovery` TEXT,
  `field4_recovery` TEXT,
  `field5_recovery` TEXT,
  `field6_recovery` TEXT,
  `field7_recovery` TEXT,
  `field8_recovery` TEXT,
  `field9_recovery` TEXT,
  `field10_recovery` TEXT,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `internal_counter` INT DEFAULT 0,
  `last_fired` BIGINT NOT NULL DEFAULT 0,
  `last_reference` BIGINT NOT NULL DEFAULT 0,
  `times_fired` INT NOT NULL DEFAULT 0,
  `disabled` TINYINT DEFAULT 0,
  `standby` TINYINT DEFAULT 0,
  `priority` TINYINT DEFAULT 0,
  `force_execution` TINYINT DEFAULT 0,
  `group_by` enum ('','id_agente','id_agentmodule','id_alert_am','id_grupo') DEFAULT '',
  `special_days` TINYINT DEFAULT 0,
  `disable_event` TINYINT DEFAULT 0,
  `id_template_conditions` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_template_fields` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_evaluation` BIGINT NOT NULL DEFAULT 0,
  `pool_occurrences` INT UNSIGNED NOT NULL DEFAULT 0,
  `schedule` TEXT,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tevent_alert_action`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_alert_action` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_event_alert` INT UNSIGNED NOT NULL,
  `id_alert_action` INT UNSIGNED NOT NULL,
  `fires_min` INT UNSIGNED DEFAULT 0,
  `fires_max` INT UNSIGNED DEFAULT 0,
  `module_action_threshold` INT NOT NULL DEFAULT 0,
  `last_execution` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_event_alert`) REFERENCES tevent_alert(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;


-- -----------------------------------------------------
-- Table `tmodule_synth`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule_synth` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agent_module_source` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent_module_target` INT UNSIGNED NOT NULL DEFAULT 0,
  `fixed_value` DOUBLE NOT NULL DEFAULT 0,
  `operation` enum ('ADD', 'SUB', 'DIV', 'MUL', 'AVG', 'NOP') NOT NULL DEFAULT 'NOP',
  `order` INT NOT NULL DEFAULT 0,
  FOREIGN KEY (`id_agent_module_target`) REFERENCES tagente_modulo(`id_agente_modulo`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;


-- -----------------------------------------------------
-- Table `tnetworkmap_enterprise`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetworkmap_enterprise` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(500) DEFAULT '',
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `options` TEXT ,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;


-- -----------------------------------------------------
-- Table `tnetworkmap_enterprise_nodes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetworkmap_enterprise_nodes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_networkmap_enterprise` INT UNSIGNED NOT NULL,
  `x` INT DEFAULT 0,
  `y` INT DEFAULT 0,
  `z` INT DEFAULT 0,
  `id_agent` INT DEFAULT 0,
  `id_module` INT DEFAULT 0,
  `id_agent_module` INT DEFAULT 0,
  `parent` INT DEFAULT 0,
  `options` TEXT ,
  `deleted` INT DEFAULT 0,
  `state` VARCHAR(150) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  FOREIGN KEY (`id_networkmap_enterprise`) REFERENCES tnetworkmap_enterprise(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;


-- -----------------------------------------------------
-- Table `tnetworkmap_ent_rel_nodes` (Before `tnetworkmap_enterprise_relation_nodes`)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetworkmap_ent_rel_nodes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_networkmap_enterprise` INT UNSIGNED NOT NULL,
  `parent` INT DEFAULT 0,
  `parent_type` VARCHAR(30) DEFAULT 'node',
  `child` INT DEFAULT 0,
  `child_type` VARCHAR(30) DEFAULT 'node',
  `deleted` INT DEFAULT 0,
  PRIMARY KEY (id, id_networkmap_enterprise),
  FOREIGN KEY (`id_networkmap_enterprise`) REFERENCES tnetworkmap_enterprise(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `treport_template`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_template` (
  `id_report` INT UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL DEFAULT '',
  `name` VARCHAR(150) NOT NULL DEFAULT '',
  `description` TEXT,
  `private` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT NULL,
  `custom_logo` VARCHAR(200)  DEFAULT NULL,
  `header` MEDIUMTEXT  ,
  `first_page` MEDIUMTEXT ,
  `footer` MEDIUMTEXT ,
  `custom_font` VARCHAR(200) DEFAULT NULL,
  `metaconsole` TINYINT DEFAULT 0,
  `agent_regex` VARCHAR(600) NOT NULL DEFAULT '',
  `cover_page_render` TINYINT NOT NULL DEFAULT 1,
  `index_render` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY(`id_report`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `treport_content_template`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_template` (
  `id_rc` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_report` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_gs` INT UNSIGNED NULL DEFAULT NULL,
  `text_agent_module` TEXT,
  `type` VARCHAR(30) DEFAULT 'simple_graph',
  `period` INT NOT NULL DEFAULT 0,
  `order` INT NOT NULL DEFAULT 0,
  `description` MEDIUMTEXT, 
  `text_agent` TEXT,
  `text` TEXT,
  `external_source` MEDIUMTEXT,
  `treport_custom_sql_id` INT UNSIGNED DEFAULT 0,
  `header_definition` TINYTEXT ,
  `column_separator` TINYTEXT ,
  `line_separator` TINYTEXT ,
  `time_from` time DEFAULT '00:00:00',
  `time_to` time DEFAULT '00:00:00',
  `monday` TINYINT DEFAULT 1,
  `tuesday` TINYINT DEFAULT 1,
  `wednesday` TINYINT DEFAULT 1,
  `thursday` TINYINT DEFAULT 1,
  `friday` TINYINT DEFAULT 1,
  `saturday` TINYINT DEFAULT 1,
  `sunday` TINYINT DEFAULT 1,
  `only_display_wrong` TINYINT unsigned DEFAULT 0 NOT NULL,
  `top_n` INT NOT NULL DEFAULT 0,
  `top_n_value` INT NOT NULL DEFAULT 10,
  `exception_condition` INT NOT NULL DEFAULT 0,
  `exception_condition_value` DOUBLE NOT NULL DEFAULT 0,
  `show_resume` INT NOT NULL DEFAULT 0,
  `order_uptodown` INT NOT NULL DEFAULT 0,
  `show_graph` INT NOT NULL DEFAULT 0,
  `group_by_agent` INT NOT NULL DEFAULT 0,
  `style` TEXT,
  `id_group` INT unsigned NOT NULL DEFAULT 0,
  `id_module_group` INT unsigned NOT NULL DEFAULT 0,
  `server_name` TEXT,
  `exact_match` TINYINT DEFAULT 0,
  `module_names` TEXT,
  `module_free_text` TEXT,
  `each_agent` TINYINT DEFAULT 1,
  `historical_db` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `lapse_calc` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `lapse` INT UNSIGNED NOT NULL DEFAULT 300,
  `visual_format` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `hide_no_data` TINYINT DEFAULT 0,
  `total_time` TINYINT DEFAULT 1,
  `time_failed` TINYINT DEFAULT 1,
  `time_in_ok_status` TINYINT DEFAULT 1,
  `time_in_warning_status` TINYINT DEFAULT 0,
  `time_in_unknown_status` TINYINT DEFAULT 1,
  `time_of_not_initialized_module` TINYINT DEFAULT 1,
  `time_of_downtime` TINYINT DEFAULT 1,
  `total_checks` TINYINT DEFAULT 1,
  `checks_failed` TINYINT DEFAULT 1,
  `checks_in_ok_status` TINYINT DEFAULT 1,
  `checks_in_warning_status` TINYINT DEFAULT 0,
  `unknown_checks` TINYINT DEFAULT 1,
  `agent_max_value` TINYINT DEFAULT 1,
  `agent_min_value` TINYINT DEFAULT 1,
  `current_month` TINYINT DEFAULT 1,
  `failover_mode` TINYINT DEFAULT 1,
  `failover_type` TINYINT DEFAULT 1,
  `summary` TINYINT DEFAULT 0,
  `uncompressed_module` TINYINT DEFAULT 0,
  `landscape` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `pagebreak` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `compare_work_time` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `graph_render` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `ipam_network_filter` INT UNSIGNED DEFAULT 0,
  `ipam_alive_ips` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `ipam_ip_not_assigned_to_agent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `macros_definition` TEXT,
  `render_definition` TEXT,
  `use_prefix_notation` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY(`id_rc`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `treport_content_sla_com_temp` (treport_content_sla_combined_template)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_sla_com_temp` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_report_content` INT UNSIGNED NOT NULL,
  `text_agent` TEXT,
  `text_agent_module` TEXT,
  `sla_max` DOUBLE NOT NULL DEFAULT 0,
  `sla_min` DOUBLE NOT NULL DEFAULT 0,
  `sla_limit` DOUBLE NOT NULL DEFAULT 0,
  `server_name` TEXT,
  `exact_match` TINYINT DEFAULT 0,
  PRIMARY KEY(`id`),
  FOREIGN KEY (`id_report_content`) REFERENCES treport_content_template(`id_rc`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `treport_content_item_temp` (treport_content_item_template)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_item_temp` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
  `id_report_content` INT UNSIGNED NOT NULL, 
  `text_agent` TEXT,
  `text_agent_module` TEXT,
  `server_name` TEXT,
  `exact_match` TINYINT DEFAULT 0,
  `operation` TEXT,  
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tgraph_template`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgraph_template` (
  `id_graph_template` INT UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` TEXT,
  `name` TEXT,
  `description` TEXT,
  `period` INT NOT NULL DEFAULT 0,
  `width` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `height` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `private` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `events` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `stacked` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  PRIMARY KEY(`id_graph_template`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tgraph_source_template`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgraph_source_template` (
  `id_gs_template` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_template` INT NOT NULL DEFAULT 0,
  `agent` TEXT, 
  `module` TEXT,
  `weight` DOUBLE NOT NULL DEFAULT 2,
  `exact_match` TINYINT DEFAULT 0, 
  PRIMARY KEY(`id_gs_template`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `textension_translate_string`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `textension_translate_string` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lang` VARCHAR(10) NOT NULL ,
  `string` TEXT ,
  `translation` TEXT ,
  PRIMARY KEY (`id`),
  KEY `lang_index` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tagent_module_log`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_module_log` (
  `id_agent_module_log` INT NOT NULL AUTO_INCREMENT,
  `id_agent` INT UNSIGNED NOT NULL,
  `source` TEXT,
  `timestamp` DATETIME DEFAULT '1970-01-01 00:00:00',
  `utimestamp` BIGINT DEFAULT 0,
  PRIMARY KEY (`id_agent_module_log`),
  INDEX `tagent_module_log_agent` (`id_agent`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tevent_custom_field`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_custom_field` (
  `id_group` MEDIUMINT UNSIGNED NOT NULL,
  `value` TEXT,
  PRIMARY KEY  (`id_group`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmetaconsole_agent` (
  `id_agente` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tagente` INT UNSIGNED NOT NULL,
  `id_tmetaconsole_setup` INT NOT NULL,
  `nombre` VARCHAR(600) NOT NULL DEFAULT '',
  `direccion` VARCHAR(100) DEFAULT NULL,
  `comentarios` VARCHAR(255) DEFAULT '',
  `id_grupo` INT UNSIGNED NOT NULL DEFAULT 0,
  `ultimo_contacto` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `modo` TINYINT NOT NULL DEFAULT 0,
  `intervalo` INT UNSIGNED NOT NULL DEFAULT 300,
  `id_os` INT UNSIGNED DEFAULT 0,
  `os_version` VARCHAR(100) DEFAULT '',
  `agent_version` VARCHAR(100) DEFAULT '',
  `ultimo_contacto_remoto` DATETIME DEFAULT '1970-01-01 00:00:00',
  `disabled` TINYINT NOT NULL DEFAULT 0,
  `remote` TINYINT NOT NULL DEFAULT 0,
  `id_parent` INT UNSIGNED DEFAULT 0,
  `custom_id` VARCHAR(255) DEFAULT '',
  `server_name` VARCHAR(100) DEFAULT '',
  `cascade_protection` TINYINT NOT NULL DEFAULT 0,
  `cascade_protection_module` INT UNSIGNED DEFAULT 0,
  `timezone_offset` TINYINT NULL DEFAULT 0 COMMENT 'number of hours of diference with the server timezone',
  `icon_path` VARCHAR(127) NULL DEFAULT NULL COMMENT 'path in the server to the image of the icon representing the agent' ,
  `update_gis_data` TINYINT NOT NULL DEFAULT 1 COMMENT 'set it to one to update the position data (altitude, longitude, latitude) when getting information from the agent or to 0 to keep the last value and do not update it',
  `url_address` MEDIUMTEXT NULL,
  `quiet` TINYINT NOT NULL DEFAULT 0,
  `normal_count` BIGINT NOT NULL DEFAULT 0,
  `warning_count` BIGINT NOT NULL DEFAULT 0,
  `critical_count` BIGINT NOT NULL DEFAULT 0,
  `unknown_count` BIGINT NOT NULL DEFAULT 0,
  `notinit_count` BIGINT NOT NULL DEFAULT 0,
  `total_count` BIGINT NOT NULL DEFAULT 0,
  `fired_count` BIGINT NOT NULL DEFAULT 0,
  `update_module_count` TINYINT NOT NULL DEFAULT 0,
  `update_alert_count` TINYINT NOT NULL DEFAULT 0,
  `update_secondary_groups` TINYINT NOT NULL DEFAULT 0,
  `transactional_agent` TINYINT NOT NULL DEFAULT 0,
  `alias` VARCHAR(600) NOT NULL DEFAULT '',
  `alias_as_name` TINYINT NOT NULL DEFAULT 0,
  `safe_mode_module` INT UNSIGNED NOT NULL DEFAULT 0,
  `cps` INT NOT NULL DEFAULT 0,
  `satellite_server` INT NOT NULL DEFAULT 0,
  `fixed_ip` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_agente`),
  KEY `nombre` (`nombre`(255)),
  KEY `direccion` (`direccion`),
  KEY `id_tagente_idx` (`id_tagente`),
  KEY `disabled` (`disabled`),
  KEY `id_grupo` (`id_grupo`),
  KEY `tma_id_os_idx` (`id_os`),
  KEY `tma_server_name_idx` (`server_name`),
  FOREIGN KEY (`id_tmetaconsole_setup`) REFERENCES tmetaconsole_setup(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `treset_pass`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treset_pass` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL DEFAULT '',
  `cod_hash` VARCHAR(100) NOT NULL DEFAULT '',
  `reset_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tcluster`
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tcluster`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TINYTEXT ,
  `cluster_type` ENUM('AA','AP') NOT NULL DEFAULT 'AA',
    `description` TEXT ,
    `group` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_agent` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tcluster_item`
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tcluster_item`(
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TINYTEXT ,
  `item_type` ENUM('AA','AP')  NOT NULL DEFAULT 'AA',
    `critical_limit` INT UNSIGNED NOT NULL DEFAULT 0,
    `warning_limit` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_critical` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `id_cluster` INT UNSIGNED,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_cluster`) REFERENCES tcluster(`id`)
      ON DELETE SET NULL ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tcluster_agent`
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tcluster_agent`(
  `id_cluster` INT UNSIGNED NOT NULL,
  `id_agent` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_cluster`,`id_agent`),
    FOREIGN KEY (`id_cluster`) REFERENCES tcluster(`id`)
      ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tprovisioning`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tprovisioning`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT ,
  `order` INT NOT NULL DEFAULT 0,
  `config` TEXT ,
    PRIMARY KEY (`id`)
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tprovisioning_rules`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tprovisioning_rules`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_provisioning` INT UNSIGNED NOT NULL,
  `order` INT NOT NULL DEFAULT 0,
  `operator` ENUM('AND','OR') DEFAULT 'OR',
  `type` ENUM('alias','ip-range') DEFAULT 'alias',
  `value` VARCHAR(100) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_provisioning`) REFERENCES tprovisioning(`id`)
      ON DELETE CASCADE
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tmigration_queue`
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tmigration_queue`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_source_agent` INT UNSIGNED NOT NULL,
  `id_target_agent` INT UNSIGNED NOT NULL,
  `id_source_node` INT UNSIGNED NOT NULL,
  `id_target_node` INT UNSIGNED NOT NULL,
  `priority` INT UNSIGNED DEFAULT 0,
  `step` INT DEFAULT 0,
  `running` TINYINT DEFAULT 0,
  `active_db_only` TINYINT DEFAULT 0,
  PRIMARY KEY(`id`)
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tmigration_module_queue`
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tmigration_module_queue`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_migration` INT UNSIGNED NOT NULL,
  `id_source_agentmodule` INT UNSIGNED NOT NULL,
  `id_target_agentmodule` INT UNSIGNED NOT NULL,
  `last_replication_timestamp` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY(`id`),
  FOREIGN KEY(`id_migration`) REFERENCES tmigration_queue(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tagent_secondary_group`
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tagent_secondary_group`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agent` INT UNSIGNED NOT NULL,
  `id_group` MEDIUMINT UNSIGNED NOT NULL,
  PRIMARY KEY(`id`),
  FOREIGN KEY(`id_agent`) REFERENCES tagente(`id_agente`)
    ON DELETE CASCADE,
  FOREIGN KEY(`id_group`) REFERENCES tgrupo(`id_grupo`)
    ON DELETE CASCADE
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent_secondary_group`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmetaconsole_agent_secondary_group`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agent` INT UNSIGNED NOT NULL,
  `id_tagente` INT UNSIGNED NOT NULL,
  `id_tmetaconsole_setup` INT NOT NULL,
  `id_group` MEDIUMINT UNSIGNED NOT NULL,
  PRIMARY KEY(`id`),
  KEY `id_tagente` (`id_tagente`),
  FOREIGN KEY(`id_agent`) REFERENCES tmetaconsole_agent(`id_agente`)
    ON DELETE CASCADE,
  FOREIGN KEY(`id_group`) REFERENCES tgrupo(`id_grupo`)
    ON DELETE CASCADE,
  FOREIGN KEY (`id_tmetaconsole_setup`) REFERENCES tmetaconsole_setup(`id`)
    ON DELETE CASCADE
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tautoconfig`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tautoconfig` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `order` INT NOT NULL DEFAULT 0,
  `description` TEXT,
  `disabled` TINYINT DEFAULT 0,
  `type_execution` VARCHAR(100) NOT NULL DEFAULT 'start',
  `type_periodicity` VARCHAR(100) NOT NULL DEFAULT 'weekly',
  `monday` TINYINT DEFAULT 0,
  `tuesday` TINYINT DEFAULT 0,
  `wednesday` TINYINT DEFAULT 0,
  `thursday` TINYINT DEFAULT 0,
  `friday` TINYINT DEFAULT 0,
  `saturday` TINYINT DEFAULT 0,
  `sunday` TINYINT DEFAULT 0,
  `periodically_day_from` INT UNSIGNED DEFAULT NULL,
  `periodically_time_from` time NULL DEFAULT NULL,
  `executed` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tautoconfig_rules`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tautoconfig_rules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_autoconfig` INT UNSIGNED NOT NULL,
  `order` INT NOT NULL DEFAULT 0,
  `operator` ENUM('AND','OR') DEFAULT 'OR',
  `type` ENUM('alias','ip-range','group','os','custom-field','script','server-name') DEFAULT 'alias',
  `value` TEXT,
  `custom` TEXT,
  PRIMARY KEY (`id`),
  KEY `id_autoconfig` (`id_autoconfig`),
  CONSTRAINT `tautoconfig_rules_ibfk_1` FOREIGN KEY (`id_autoconfig`) REFERENCES `tautoconfig` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tautoconfig_actions`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tautoconfig_actions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_autoconfig` INT UNSIGNED NOT NULL,
  `order` INT NOT NULL DEFAULT 0,
  `action_type` ENUM('set-group', 'set-secondary-group', 'apply-policy', 'launch-script', 'launch-event', 'launch-alert-action', 'raw-config') DEFAULT 'launch-event',
  `value` TEXT,
  `custom` TEXT,
  PRIMARY KEY (`id`),
  KEY `id_autoconfig` (`id_autoconfig`),
  CONSTRAINT `tautoconfig_action_ibfk_1` FOREIGN KEY (`id_autoconfig`) REFERENCES `tautoconfig` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tlayout_template`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout_template` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(600)  NOT NULL,
  `id_group` INT UNSIGNED NOT NULL,
  `background` VARCHAR(200)  NOT NULL,
  `height` INT UNSIGNED NOT NULL DEFAULT 0,
  `width` INT UNSIGNED NOT NULL DEFAULT 0,
  `background_color` VARCHAR(50) NOT NULL DEFAULT '#FFF',
  `is_favourite` INT UNSIGNED NOT NULL DEFAULT 0,
  `auto_adjust` INT UNSIGNED NOT NULL DEFAULT 0,
  `maintenance_mode` TEXT,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tlayout_template_data`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout_template_data` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_layout_template` INT UNSIGNED NOT NULL,
  `pos_x` INT UNSIGNED NOT NULL DEFAULT 0,
  `pos_y` INT UNSIGNED NOT NULL DEFAULT 0,
  `height` INT UNSIGNED NOT NULL DEFAULT 0,
  `width` INT UNSIGNED NOT NULL DEFAULT 0,
  `label` TEXT,
  `image` VARCHAR(200) DEFAULT '',
  `type` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `period` INT UNSIGNED NOT NULL DEFAULT 3600,
  `module_name` TEXT,
  `agent_name` VARCHAR(600) NOT NULL DEFAULT '',
  `id_layout_linked` INT unsigned NOT NULL DEFAULT 0,
  `parent_item` INT UNSIGNED NOT NULL DEFAULT 0,
  `enable_link` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `id_metaconsole` INT NOT NULL DEFAULT 0,
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_custom_graph` INT UNSIGNED NOT NULL DEFAULT 0,
  `border_width` INT UNSIGNED NOT NULL DEFAULT 0,
  `type_graph` VARCHAR(50) NOT NULL DEFAULT 'area',
  `label_position` VARCHAR(50) NOT NULL DEFAULT 'down',
  `border_color` VARCHAR(200) DEFAULT '',
  `fill_color` VARCHAR(200) DEFAULT '',
  `recursive_group` TINYINT NOT NULL DEFAULT '0',
  `show_statistics` TINYINT NOT NULL DEFAULT 0,
  `linked_layout_node_id` INT NOT NULL DEFAULT 0,
  `linked_layout_status_type` ENUM ('default', 'weight', 'service') DEFAULT 'default',
  `id_layout_linked_weight` INT NOT NULL DEFAULT 0,
  `linked_layout_status_as_service_warning` DOUBLE NOT NULL DEFAULT 0,
  `linked_layout_status_as_service_critical` DOUBLE NOT NULL DEFAULT 0,
  `element_group` INT NOT NULL DEFAULT 0,
  `show_on_top` TINYINT NOT NULL DEFAULT 0,
  `clock_animation` VARCHAR(60) NOT NULL DEFAULT 'analogic_1',
  `time_format` VARCHAR(60) NOT NULL DEFAULT 'time',
  `timezone` VARCHAR(60) NOT NULL DEFAULT 'Europe/Madrid',
  `show_last_value` TINYINT UNSIGNED NULL DEFAULT 0,
  `cache_expiration` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`id`),
  FOREIGN KEY (`id_layout_template`) REFERENCES tlayout_template(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tlog_graph_models`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlog_graph_models` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` TEXT,
  `regexp` TEXT,
  `fields` TEXT,
  `average` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tagent_custom_fields_filter`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_custom_fields_filter` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(600) NOT NULL,
  `id_group` INT UNSIGNED DEFAULT 0,
  `id_custom_field` VARCHAR(600) DEFAULT '',
  `id_custom_fields_data` VARCHAR(600) DEFAULT '',
  `id_status` VARCHAR(600) DEFAULT '',
  `module_search` VARCHAR(600) DEFAULT '',
  `module_status` VARCHAR(600) DEFAULT '',
  `recursion` INT UNSIGNED DEFAULT 0,
  `group_search` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- -----------------------------------------------------
-- Table `tnetwork_matrix`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_matrix` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(60) DEFAULT '',
  `destination` VARCHAR(60) DEFAULT '',
  `utimestamp` BIGINT DEFAULT 0,
  `bytes` INT UNSIGNED DEFAULT 0,
  `pkts` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE (`source`, `destination`, `utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 ;

-- ---------------------------------------------------------------------
-- Table `user_task`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tuser_task` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `function_name` VARCHAR(80) NOT NULL DEFAULT '',
  `parameters` TEXT ,
  `name` VARCHAR(60) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `user_task_scheduled`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tuser_task_scheduled` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0',
  `id_user_task` INT UNSIGNED NOT NULL DEFAULT 0,
  `args` TEXT,
  `scheduled` ENUM('no','hourly','daily','weekly','monthly','yearly','custom') DEFAULT 'no',
  `last_run` INT UNSIGNED DEFAULT 0,
  `custom_data` INT NULL DEFAULT 0,
  `flag_delete` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_grupo` INT UNSIGNED NOT NULL DEFAULT 0,
  `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `id_console` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tvisual_console_items_cache`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tvisual_console_elements_cache` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vc_id` INT UNSIGNED NOT NULL,
  `vc_item_id` INT UNSIGNED NOT NULL,
  `user_id` VARCHAR(255) DEFAULT NULL,
  `data` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expiration` INT UNSIGNED NOT NULL COMMENT 'Seconds to expire',
  PRIMARY KEY(`id`),
  FOREIGN KEY(`vc_id`) REFERENCES `tlayout`(`id`)
    ON DELETE CASCADE,
  FOREIGN KEY(`vc_item_id`) REFERENCES `tlayout_data`(`id`)
    ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `tusuario`(`id_user`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tagent_repository`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_repository` (
  `id` SERIAL,
  `id_os` INT UNSIGNED DEFAULT 0,
  `arch` ENUM('x64', 'x86') DEFAULT 'x64',
  `version` VARCHAR(10) DEFAULT '',
  `path` TEXT,
  `deployment_timeout` INT UNSIGNED DEFAULT 600,
  `uploaded_by` VARCHAR(100) DEFAULT '',
  `uploaded` BIGINT NOT NULL DEFAULT 0 COMMENT 'When it was uploaded',
  `last_err` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_os`) REFERENCES `tconfig_os`(`id_os`)
  ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tdeployment_hosts`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tdeployment_hosts` (
  `id` SERIAL,
  `id_cs` VARCHAR(100),
  `ip` VARCHAR(100) NOT NULL UNIQUE,
  `id_os` INT UNSIGNED DEFAULT 0,
  `os_version` VARCHAR(100) DEFAULT '' COMMENT 'OS version in STR format',
  `arch` ENUM('x64', 'x86') DEFAULT 'x64',
  `current_agent_version` VARCHAR(100) DEFAULT '' COMMENT 'String latest installed agent',
  `target_agent_version_id` BIGINT UNSIGNED,
  `deployed` BIGINT NOT NULL DEFAULT 0 COMMENT 'When it was deployed',
  `server_ip` VARCHAR(100) DEFAULT NULL COMMENT 'Where to point target agent',
  `last_err` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_cs`) REFERENCES `tcredential_store`(`identifier`)
  ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (`id_os`) REFERENCES `tconfig_os`(`id_os`)
  ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`target_agent_version_id`) REFERENCES  `tagent_repository`(`id`)
  ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tremote_command`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tremote_command` (
  `id` SERIAL,
  `name` VARCHAR(150) NOT NULL,
  `timeout` INT UNSIGNED NOT NULL DEFAULT 30,
  `retries` INT UNSIGNED NOT NULL DEFAULT 3,
  `preconditions` TEXT,
  `script` TEXT,
  `postconditions` TEXT,
  `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_group` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tremote_command_target`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tremote_command_target` (
  `id` SERIAL,
  `rcmd_id` BIGINT UNSIGNED NOT NULL,
  `id_agent` INT UNSIGNED NOT NULL,
  `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0,
  `stdout` MEDIUMTEXT,
  `stderr` MEDIUMTEXT,
  `errorlevel` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`rcmd_id`) REFERENCES `tremote_command`(`id`)
  ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tnode_relations`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnode_relations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gateway` VARCHAR(100) NOT NULL,
  `imei` VARCHAR(100) NOT NULL,
  `node_address` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tipam_network_location`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipam_network_location` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tipam_sites`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipam_sites` (
  `id` SERIAL,
  `name` VARCHAR(100) UNIQUE NOT NULL DEFAULT '',
  `description` TEXT,
  `parent` BIGINT UNSIGNED null,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`parent`) REFERENCES `tipam_sites`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tipam_network`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipam_network` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `network` VARCHAR(100) NOT NULL DEFAULT '',
  `name_network` VARCHAR(255) DEFAULT '',
  `description` TEXT,
  `location` INT UNSIGNED NULL,
  `id_recon_task` INT UNSIGNED DEFAULT 0,
  `scan_interval` TINYINT DEFAULT 1,
  `monitoring` TINYINT DEFAULT 0,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `lightweight_mode` TINYINT DEFAULT 0,
  `users_operator` TEXT,
  `id_site` BIGINT UNSIGNED,
  `vrf` INT UNSIGNED,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_recon_task`) REFERENCES trecon_task(`id_rt`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`location`) REFERENCES `tipam_network_location`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_site`) REFERENCES `tipam_sites`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`vrf`) REFERENCES `tagente`(`id_agente`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tipam_ip`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipam_ip` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_network` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent` INT UNSIGNED NOT NULL,
  `forced_agent` TINYINT NOT NULL DEFAULT 0,
  `ip` VARCHAR(100) NOT NULL DEFAULT '',
  `ip_dec` INT UNSIGNED NOT NULL,
  `id_os` INT UNSIGNED NOT NULL,
  `forced_os` TINYINT NOT NULL DEFAULT 0,
  `hostname` TINYTEXT,
  `forced_hostname` TINYINT NOT NULL DEFAULT 0,
  `comments` TEXT,
  `alive` TINYINT NOT NULL DEFAULT 0,
  `managed` TINYINT NOT NULL DEFAULT 0,
  `reserved` TINYINT NOT NULL DEFAULT 0,
  `time_last_check` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `time_create` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `users_operator` TEXT,
  `time_last_edit` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  `enabled` TINYINT NOT NULL DEFAULT 1,
  `generate_events` TINYINT NOT NULL DEFAULT 0,
  `leased` TINYINT DEFAULT 0,
  `leased_expiration` BIGINT DEFAULT 0,
  `mac_address` VARCHAR(20) DEFAULT NULL,
  `leased_mode` TINYINT DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_network`) REFERENCES tipam_network(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tipam_vlan`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipam_vlan` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(250) NOT NULL,
  `description` TEXT,
  `custom_id` bigint(20) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tipam_vlan_network`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipam_vlan_network` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_vlan` BIGINT UNSIGNED NOT NULL,
  `id_network` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_vlan`) REFERENCES `tipam_vlan`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_network`) REFERENCES `tipam_network`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tipam_supernet`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipam_supernet` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(250) NOT NULL,
  `description` TEXT ,
  `address` VARCHAR(250) NOT NULL,
  `mask` VARCHAR(250) NOT NULL,
  `subneting_mask` VARCHAR(250) DEFAULT '',
  `id_site` BIGINT UNSIGNED,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_site`) REFERENCES `tipam_sites`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tipam_supernet_network`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipam_supernet_network` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_supernet` BIGINT UNSIGNED NOT NULL,
  `id_network` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_supernet`) REFERENCES `tipam_supernet`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_network`) REFERENCES `tipam_network`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tsync_queue`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tsync_queue` (
  `id` SERIAL,
  `sql` MEDIUMTEXT,
  `target` BIGINT UNSIGNED NOT NULL,
  `utimestamp` BIGINT DEFAULT 0,
  `operation` TEXT,
  `table` TEXT,
  `error` MEDIUMTEXT,
  `result` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_vendor`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_vendor` (
  `id` SERIAL,
  `name` VARCHAR(255) UNIQUE,
  `icon` VARCHAR(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_model`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_model` (
  `id` SERIAL,
  `id_vendor` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) UNIQUE,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_vendor`) REFERENCES `tncm_vendor`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_template`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_template` (
  `id` SERIAL,
  `name` TEXT,
  `vendors` TEXT,
  `models` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_script`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_script` (
  `id` SERIAL,
  `type` INT UNSIGNED NOT NULL DEFAULT 0,
  `content` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_template_scripts`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_template_scripts` (
  `id` SERIAL,
  `id_template` BIGINT UNSIGNED NOT NULL,
  `id_script` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_template`) REFERENCES `tncm_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_script`) REFERENCES `tncm_script`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_agent`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_agent` (
  `id_agent` INT UNSIGNED NOT NULL,
  `id_vendor` BIGINT UNSIGNED,
  `id_model` BIGINT UNSIGNED,
  `protocol` INT UNSIGNED NOT NULL DEFAULT 0,
  `cred_key` VARCHAR(100),
  `adv_key` VARCHAR(100),
  `port` INT UNSIGNED DEFAULT 22,
  `status` INT NOT NULL DEFAULT 5,
  `updated_at` BIGINT NOT NULL DEFAULT 0,
  `config_backup_id` BIGINT UNSIGNED DEFAULT NULL,
  `id_template` BIGINT UNSIGNED,
  `execute_type` INT UNSIGNED NOT NULL DEFAULT 0,
  `execute` INT UNSIGNED NOT NULL DEFAULT 0,
  `cron_interval` VARCHAR(100) DEFAULT '',
  `event_on_change` INT UNSIGNED DEFAULT null,
  `last_error` TEXT,
  PRIMARY KEY (`id_agent`),
  FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`cred_key`) REFERENCES `tcredential_store`(`identifier`) ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (`id_template`) REFERENCES `tncm_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_vendor`) REFERENCES `tncm_vendor`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (`id_model`) REFERENCES `tncm_model`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_agent_data`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_agent_data` (
  `id` SERIAL,
  `id_agent` INT UNSIGNED NOT NULL,
  `script_type` INT UNSIGNED NOT NULL,
  `data` LONGBLOB,
  `status` INT NOT NULL DEFAULT 5,
  `updated_at` BIGINT NOT NULL DEFAULT 0,
  FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_queue`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_queue` (
  `id` SERIAL,
  `id_agent` INT UNSIGNED NOT NULL,
  `id_script` BIGINT UNSIGNED NOT NULL,
  `utimestamp` INT UNSIGNED NOT NULL,
  `scheduled` INT UNSIGNED DEFAULT NULL,
  FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_script`) REFERENCES `tncm_script`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_snippet`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_snippet` (
  `id` SERIAL,
  `name` TEXT,
  `content` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tncm_firmware`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_firmware` (
  `id` SERIAL,
  `name` VARCHAR(255),
  `shortname` VARCHAR(255) unique,
  `vendor` BIGINT UNSIGNED,
  `models` TEXT,
  `path` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ----------------------------------------------------------------------
-- Table `tbackup`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbackup` (
  `id` SERIAL,
  `utimestamp` BIGINT DEFAULT 0,
  `filename` VARCHAR(512) DEFAULT '',
  `id_user` VARCHAR(255) DEFAULT '',
  `description` MEDIUMTEXT,
  `pid` INT UNSIGNED DEFAULT 0,
  `filepath` VARCHAR(512) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tmonitor_filter`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmonitor_filter` (
  `id_filter`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_name` VARCHAR(600) NOT NULL,
  `id_group_filter` INT NOT NULL DEFAULT 0,
  `ag_group` INT NOT NULL DEFAULT 0,
  `recursion` TEXT,
  `status` INT NOT NULL DEFAULT -1,
  `ag_modulename` TEXT,
  `ag_freestring` TEXT,
  `tag_filter` TEXT,
  `moduletype` TEXT,
  `module_option` INT DEFAULT 1,
  `modulegroup` INT NOT NULL DEFAULT -1,
  `min_hours_status` TEXT,
  `datatype` TEXT,
  `not_condition` TEXT,
  `ag_custom_fields` TEXT,
  PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tconsole`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tconsole` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_console` BIGINT NOT NULL DEFAULT 0,
  `description` TEXT,
  `version` TINYTEXT,
  `last_execution` INT UNSIGNED NOT NULL DEFAULT 0,
  `console_type` TINYINT NOT NULL DEFAULT 0,
  `timezone` TINYTEXT,
  `public_url` TEXT,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tagent_filter`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_filter` (
  `id_filter`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_name` VARCHAR(600) NOT NULL,
  `id_group_filter` INT NOT NULL DEFAULT 0,
  `group_id` INT NOT NULL DEFAULT 0,
  `recursion` TEXT,
  `status` INT NOT NULL DEFAULT -1,
  `search` TEXT,
  `id_os` INT NOT NULL DEFAULT 0,
  `policies` TEXT,
  `search_custom` TEXT,
  `ag_custom_fields` TEXT,
  PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tevent_sound`
-- ---------------------------------------------------------------------
CREATE TABLE `tevent_sound` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` TEXT NULL,
    `sound` TEXT NULL,
    `active` TINYINT NOT NULL DEFAULT '1',
PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tsesion_filter`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tsesion_filter` (
    `id_filter` INT NOT NULL AUTO_INCREMENT,
    `id_name` TEXT NULL,
    `text` TEXT NULL,
    `period` TEXT NULL,
    `ip` TEXT NULL,
    `type` TEXT NULL,
    `user` TEXT NULL,
    PRIMARY KEY (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;


CREATE TABLE IF NOT EXISTS `twelcome_tip` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_lang` VARCHAR(20) NULL,
  `id_profile` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `text` TEXT NOT NULL,
  `url` VARCHAR(255) NULL,
  `enable` TINYINT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;


CREATE TABLE IF NOT EXISTS `twelcome_tip_file` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `twelcome_tip_file` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `twelcome_tip_file`
    FOREIGN KEY (`twelcome_tip_file`)
    REFERENCES `twelcome_tip` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

-- ---------------------------------------------------------------------
-- Table `tfavmenu_user`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tfavmenu_user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL,
  `id_element` TEXT,
  `url` TEXT NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `section` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`));

-- ---------------------------------------------------------------------
-- Table `tsesion_filter_log_viewer`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tsesion_filter_log_viewer` (
  `id_filter` INT NOT NULL AUTO_INCREMENT,
  `id_name` TEXT NULL,
  `id_group_filter` TEXT NULL,
  `id_search_mode` INT NULL,
  `order` VARCHAR(45) NULL,
  `search` VARCHAR(255) NULL,
  `group_id` INT NULL,
  `date_range` TINYINT NULL,
  `start_date_defined` VARCHAR(45) NULL,
  `start_date_time` VARCHAR(45) NULL,
  `start_date_date` VARCHAR(45) NULL,
  `start_date_date_range` VARCHAR(45) NULL,
  `start_date_time_range` VARCHAR(45) NULL,
  `end_date_date_range` VARCHAR(45) NULL,
  `end_date_time_range` VARCHAR(45) NULL,
  `agent` VARCHAR(255) NULL,
  `source` VARCHAR(255) NULL,
  `display_mode` INT NULL,
  `capture_model` INT NULL,
  `graph_type` INT NULL,
  PRIMARY KEY (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
