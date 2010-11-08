-- Pandora FMS - the Flexible Monitoring System
-- ============================================
-- Copyright (c) 2010 Artica Soluciones Tecnológicas, http://www.artica.es
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
ALTER TABLE `tgrupo` ADD COLUMN `propagate` tinyint(1) unsigned NOT NULL default '0';

-- -----------------------------------------------------
-- Table `treport_content`
-- -----------------------------------------------------
ALTER TABLE `treport_content` ADD COLUMN `time_from` time default '00:00:00';
ALTER TABLE `treport_content` ADD COLUMN `time_to` time default '00:00:00';
ALTER TABLE `treport_content` ADD COLUMN `monday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `tuesday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `wednesday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `thursday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `friday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `saturday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `sunday` tinyint(1) default 1;
ALTER TABLE `treport_content` MODIFY external_source TEXT default NULL;

-- -----------------------------------------------------
-- Table `tnetwork_map`
-- -----------------------------------------------------

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
   PRIMARY KEY  (`id_networkmap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tagente_modulo`
-- -----------------------------------------------------

ALTER TABLE `tagente_modulo` ADD COLUMN `id_policy_module` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `nombre`;
ALTER TABLE `tagente_modulo` ADD COLUMN `policy_linked` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `delete_pending`;
ALTER TABLE `tagente_modulo` ADD COLUMN `policy_adopted` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

-- -----------------------------------------------------
-- Table `talert_template_modules`
-- -----------------------------------------------------
ALTER TABLE `talert_template_modules` ADD COLUMN `id_policy_alerts` int(10) unsigned NOT NULL default '0';
ALTER TABLE `talert_template_modules` ADD COLUMN `standby` tinyint(1) default '0';

-- -----------------------------------------------------
-- Table `tsnmp_filter`
-- -----------------------------------------------------
CREATE TABLE  IF NOT EXISTS  `tsnmp_filter` (
  `id_snmp_filter` int(10) unsigned NOT NULL auto_increment,
  `description` varchar(255) default '',
  `filter` varchar(255) default '',
  PRIMARY KEY  (`id_snmp_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_templates`
-- -----------------------------------------------------
ALTER TABLE `talert_templates` MODIFY COLUMN `type` ENUM ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal', 'warning', 'critical', 'onchange', 'unknown');

ALTER TABLE trecon_task ADD `snmp_community` varchar(64) NOT NULL default 'public';

ALTER TABLE ttrap MODIFY value_custom text default '';
ALTER TABLE ttrap MODIFY value text default '';
-- -----------------------------------------------------
-- Table `tagent_custom_fields`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tagent_custom_fields` (
  `id_field` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(45) NOT NULL default '',
  `display_on_front` tinyint(1) NOT NULL default 0,
  PRIMARY KEY  (`id_field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tagent_custom_data`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tagent_custom_data` (
  `id_field` int(10) unsigned NOT NULL,
  `id_agent` int(10) unsigned NOT NULL,
  `description` text default '',
  FOREIGN KEY (`id_field`) REFERENCES tagent_custom_fields(`id_field`)
	ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_agent`) REFERENCES tagente(`id_agente`)
	ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY  (`id_field`, `id_agent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tagent_custom_data`
-- -----------------------------------------------------

ALTER TABLE `tevento` MODIFY COLUMN `user_comment` TEXT NOT NULL;




-- -----------------------------------------------------------------------
-- Data insertion --
-- -----------------------------------------------------------------------

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------
INSERT INTO tconfig (`token`, `value`) VALUES ('sound_alert', 'include/sounds/air_shock_alarm.wav');
INSERT INTO tconfig (`token`, `value`) VALUES ('sound_critical', 'include/sounds/Star_Trek_emergency_simulation.wav');
INSERT INTO tconfig (`token`, `value`) VALUES ('sound_warning', 'include/sounds/negativebeep.wav');

-- -----------------------------------------------------
-- Table `trecon_script`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `trecon_script` (
  `id_recon_script` int(10) NOT NULL auto_increment,
  `name` varchar(100) default '',
  `description` TEXT default NULL,
  `script` varchar(250) default '',
  PRIMARY KEY  (`id_recon_script`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `trecon_task`
-- -----------------------------------------------------
ALTER TABLE trecon_task ADD COLUMN `id_recon_script` int(10);
ALTER TABLE trecon_task ADD COLUMN `field1` varchar(250) NOT NULL default '';
ALTER TABLE trecon_task ADD COLUMN `field2` varchar(250) NOT NULL default '';
ALTER TABLE trecon_task ADD COLUMN `field3` varchar(250) NOT NULL default '';
ALTER TABLE trecon_task ADD COLUMN `field4` varchar(250) NOT NULL default '';
ALTER TABLE trecon_task ADD FOREIGN KEY (`id_recon_script`) REFERENCES trecon_script(`id_recon_script`) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE tagente_modulo MODIFY `descripcion` TEXT NOT NULL default '';
ALTER TABLE tagente_modulo MODIFY `tcp_send` TEXT default '';
ALTER TABLE tagente_modulo MODIFY `tcp_rcv` TEXT default '';


-- -----------------------------------------------------
-- Table `tnews`
-- -----------------------------------------------------

UPDATE tnews SET subject='Welcome to Pandora FMS 3.2!',text='This is the new Pandora FMS Console. A lot of new features have been added since last version. Please read the documentation about it, and feel free to test any option.\r\n\r\nThe Pandora FMS Team.',timestamp=NOW() WHERE id_news = '1';
