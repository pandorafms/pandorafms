-- Pandora FMS - the Flexible Monitoring System
-- ============================================
-- Copyright (c) 2011 Artica Soluciones Tecnológicas, http://www.artica.es
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

-- -----------------------------------------------------
-- Table `tgrupo`
-- -----------------------------------------------------
ALTER TABLE `tgrupo` MODIFY `nombre` text;
ALTER TABLE `tgrupo` ADD COLUMN `id_skin` int(10) unsigned NOT NULL;

-- -----------------------------------------------------
-- Table `tnetwork_component`
-- -----------------------------------------------------
ALTER TABLE `tnetwork_component` ADD COLUMN `post_process` double(18,13) DEFAULT 0;
ALTER TABLE `tnetwork_component` ADD COLUMN `str_warning` text DEFAULT '';
ALTER TABLE `tnetwork_component` ADD COLUMN `str_critical` text DEFAULT '';

-- -----------------------------------------------------
-- Table `treport_content`
-- -----------------------------------------------------
ALTER TABLE `treport_content` ADD COLUMN `only_display_wrong` tinyint(1) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `top_n` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `top_n_value` INT NOT NULL DEFAULT 10;
ALTER TABLE `treport_content` ADD COLUMN `exception_condition` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `exception_condition_value` DOUBLE (18,6) NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `show_resume` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `order_uptodown` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `show_graph` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `group_by_agent` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `id_group` int (10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `id_module_group` int (10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `style` TEXT NOT NULL DEFAULT '';
ALTER TABLE `treport_content` ADD COLUMN `server_name` TEXT DEFAULT '';

-- -----------------------------------------------------
-- Table `treport_content_item`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_item` (
  `id` INTEGER UNSIGNED NOT NULL auto_increment, 
  `id_report_content` INTEGER UNSIGNED NOT NULL, 
  `id_agent_module` int(10) unsigned NOT NULL,
  `server_name` TEXT DEFAULT '',
  PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------
ALTER TABLE `tusuario` ADD COLUMN `block_size` int(4) NOT NULL DEFAULT 20;
ALTER TABLE `tusuario` ADD COLUMN `flash_chart` int(4) NOT NULL DEFAULT 1;
ALTER TABLE `tusuario` ADD COLUMN `id_skin` int(10) unsigned NOT NULL;
UPDATE `tusuario` SET `language` = "default" WHERE `language` IS NULL;

-- -----------------------------------------------------
-- Table `talert_actions`
-- -----------------------------------------------------
ALTER TABLE `talert_actions` ADD COLUMN `action_threshold` int(10) NOT NULL DEFAULT '0';

-- -----------------------------------------------------
-- Table `talert_template_module_actions`
-- -----------------------------------------------------
ALTER TABLE `talert_template_module_actions` ADD COLUMN `module_action_threshold` int(10) NOT NULL DEFAULT '0';
ALTER TABLE `talert_template_module_actions` ADD COLUMN `last_execution` bigint(20) NOT NULL DEFAULT '0';

-- -----------------------------------------------------
-- Table `treport_content_sla_combined`
-- -----------------------------------------------------
ALTER TABLE `treport_content_sla_combined` ADD COLUMN `server_name` TEXT DEFAULT '';
ALTER TABLE `treport_content_sla_combined` DROP FOREIGN KEY treport_content_sla_combined_ibfk_2;

-- -----------------------------------------------------
-- Table `tperfil`
-- -----------------------------------------------------
ALTER TABLE `tperfil` MODIFY `name` TEXT NOT NULL DEFAULT '';

-- -----------------------------------------------------
-- Table `tsesion`
-- -----------------------------------------------------

ALTER TABLE `tsesion` CHANGE `ID_sesion` `id_sesion` bigint(20) unsigned NOT NULL auto_increment;
ALTER TABLE `tsesion` CHANGE `ID_usuario` `id_usuario` varchar(60) NOT NULL default '0';
ALTER TABLE `tsesion` CHANGE `IP_origen` `ip_origen` varchar(100) NOT NULL default '';

-- -----------------------------------------------------
-- Table `tusuario_perfil`
-- -----------------------------------------------------

ALTER TABLE tusuario_perfil ADD `id_policy` int(10) unsigned NOT NULL default 0;

-- -----------------------------------------------------
-- Table `tevento`
-- -----------------------------------------------------

ALTER TABLE `tevento` MODIFY `event_type` enum('unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change') default 'unknown';
ALTER TABLE tevento ADD INDEX criticity (`criticity`);
ALTER TABLE tevento ADD INDEX estado (`estado`);

-- -----------------------------------------------------
-- Change the value "0000-00-00 00:00:00" that Pandora use as zero or null date value
-- for the value "01-01-1970 00:00:00".
-- -----------------------------------------------------
UPDATE `tagente` SET `ultimo_contacto` = "01-01-1970 00:00:00" WHERE `ultimo_contacto` = "0000-00-00 00:00:00";
UPDATE `tagente` SET `ultimo_contacto_remoto` = "01-01-1970 00:00:00" WHERE `ultimo_contacto_remoto` = "0000-00-00 00:00:00";
UPDATE `tagente_estado` SET `timestamp` = "01-01-1970 00:00:00" WHERE `timestamp` = "0000-00-00 00:00:00";
UPDATE `tagente_estado` SET `last_try` = "01-01-1970 00:00:00" WHERE `last_try` = "0000-00-00 00:00:00";
UPDATE `talert_snmp` SET `last_fired` = "01-01-1970 00:00:00" WHERE `last_fired` = "0000-00-00 00:00:00";
UPDATE `tevento` SET `timestamp` = "01-01-1970 00:00:00" WHERE `timestamp` = "0000-00-00 00:00:00";
UPDATE `tincidencia` SET `inicio` = "01-01-1970 00:00:00" WHERE `inicio` = "0000-00-00 00:00:00";
UPDATE `tincidencia` SET `cierre` = "01-01-1970 00:00:00" WHERE `cierre` = "0000-00-00 00:00:00";
UPDATE `tserver` SET `laststart` = "01-01-1970 00:00:00" WHERE `laststart` = "0000-00-00 00:00:00";
UPDATE `tserver` SET `keepalive` = "01-01-1970 00:00:00" WHERE `keepalive` = "0000-00-00 00:00:00";
UPDATE `ttrap` SET `timestamp` = "01-01-1970 00:00:00" WHERE `timestamp` = "0000-00-00 00:00:00";
UPDATE `tnews` SET `timestamp` = "01-01-1970 00:00:00" WHERE `timestamp` = "0000-00-00 00:00:00";
UPDATE `tserver_export_data` SET `timestamp` = "01-01-1970 00:00:00" WHERE `timestamp` = "0000-00-00 00:00:00";

-- -----------------------------------------------------
-- Table `ttag`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `ttag` ( 
 `id_tag` integer(10) unsigned NOT NULL auto_increment, 
 `name` varchar(100) NOT NULL default '', 
 `description` text NOT NULL default '', 
 `url` mediumtext NOT NULL default '', 
 PRIMARY KEY  (`id_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

-- -----------------------------------------------------
-- Table `ttag_module`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `ttag_module` ( 
 `id_tag` int(10) NOT NULL, 
 `id_agente_modulo` int(10) NOT NULL DEFAULT 0, 
   PRIMARY KEY  (id_tag, id_agente_modulo),
   KEY `idx_id_agente_modulo` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

-- -----------------------------------------------------
-- Table `ttag_policy_module`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `ttag_policy_module` ( 
 `id_tag` int(10) NOT NULL, 
 `id_policy_module` int(10) NOT NULL DEFAULT 0, 
   PRIMARY KEY  (id_tag, id_policy_module),
   KEY `idx_id_policy_module` (`id_policy_module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

-- -----------------------------------------------------
-- Table `ttag_event`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `ttag_event` ( 
 `id_tag` int(10) NOT NULL, 
 `id_evento` bigint(20) NOT NULL DEFAULT 0, 
   PRIMARY KEY  (id_tag, id_evento),
   KEY `idx_id_evento` (`id_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

-- -----------------------------------------------------
-- Table `tagente_modulo`
-- -----------------------------------------------------

ALTER TABLE `tagente_modulo` ADD COLUMN (`unit` text DEFAULT '');
ALTER TABLE `tagente_modulo` ADD COLUMN (`str_warning` text DEFAULT '');
ALTER TABLE `tagente_modulo` ADD COLUMN (`str_critical` text DEFAULT '');
ALTER TABLE `tagente_modulo` ADD COLUMN (`extended_info` text DEFAULT '');
ALTER TABLE `tagente_modulo` ADD INDEX module (`id_modulo`);
ALTER TABLE `tagente_modulo` ADD INDEX nombre (`nombre` (255));
CREATE INDEX `module_group` using btree on tagente_modulo (`id_module_group`);

-- -----------------------------------------------------
-- Table `tevento`
-- -----------------------------------------------------

ALTER TABLE `tevento` ADD COLUMN (`tags` text NOT NULL);

-- -----------------------------------------------------
-- Table `tnetwork_map`
-- -----------------------------------------------------

ALTER TABLE `tnetwork_map` ADD COLUMN `show_snmp_modules` TINYINT(1) UNSIGNED  NOT NULL DEFAULT 0;

-- -----------------------------------------------------
-- Table `trecon_task`
-- -----------------------------------------------------

ALTER TABLE `trecon_task` ADD COLUMN `disabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `trecon_task` ADD COLUMN `os_detect` tinyint(1) unsigned default '0';
ALTER TABLE `trecon_task` ADD COLUMN `resolve_names` tinyint(1) unsigned default '0';
ALTER TABLE `trecon_task` ADD COLUMN `parent_detection` tinyint(1) unsigned default '0';
ALTER TABLE `trecon_task` ADD COLUMN `parent_recursion` tinyint(1) unsigned default '0';

-- -----------------------------------------------------
-- Table `tplanned_downtime`
-- -----------------------------------------------------

ALTER TABLE `tplanned_downtime` ADD COLUMN `only_alerts` TINYINT(1) NOT NULL DEFAULT 0;

-- -----------------------------------------------------
-- Table `talert_templates`
-- -----------------------------------------------------

ALTER TABLE `talert_templates` MODIFY COLUMN `type` ENUM('regex','max_min','max','min','equal','not_equal','warning','critical','onchange','unknown', 'always') DEFAULT NULL;

-- -----------------------------------------------------
-- Table `tagente_modulo` to adapt the fields use to new prediction types and future modifications
-- -----------------------------------------------------

UPDATE tagente_modulo SET prediction_module = 2 WHERE custom_integer_1 <> 0 AND prediction_module <> 0;

UPDATE tagente_modulo SET custom_integer_1 = prediction_module AND prediction_module = 1 WHERE custom_integer_1 = 0 AND prediction_module <> 0;

-- -----------------------------------------------------
-- Table `tagente_modulo` to set delete_pending name to the delete pending modules to clean possible database errors
-- -----------------------------------------------------

UPDATE tagente_modulo SET nombre = 'delete_pending' WHERE delete_pending = 1;

-- -----------------------------------------------------
-- Table `talert_template_modules`
-- -----------------------------------------------------
ALTER TABLE talert_template_modules ADD INDEX force_execution (`force_execution`);

-- -----------------------------------------------------
-- Table `tserver_export_data`
-- -----------------------------------------------------
ALTER TABLE tserver_export ADD INDEX id_export_server (`id_export_server`);

-- -----------------------------------------------------
-- Table `ttrap`
-- -----------------------------------------------------
ALTER TABLE ttrap ADD INDEX timestamp (`timestamp`);
ALTER TABLE ttrap ADD INDEX status (`status`);

-- -----------------------------------------------------
-- Table `talert_snmp`
-- -----------------------------------------------------

ALTER TABLE `talert_snmp` MODIFY COLUMN `custom_oid` text DEFAULT '';


-- -----------------------------------------------------
-- Table `tconfig_os`
-- -----------------------------------------------------
INSERT INTO `tconfig_os` (`name`, `description`, `icon_name`) VALUES ('VMware', 'VMware Architecture', 'so_vmware.png');

UPDATE tconfig SET value='4.0' WHERE token = 'db_scheme_version';
UPDATE tconfig SET value='PD110923 (3.2 Migrate)' WHERE token = 'db_scheme_build';


-- -----------------------------------------------------
-- Encode empty space entities (Added 17th May 2012)
-- -----------------------------------------------------
UPDATE tnetwork_component SET name = REPLACE(name,' ','&#x20;')
UPDATE tlocal_component SET name = REPLACE(name,' ','&#x20;')
