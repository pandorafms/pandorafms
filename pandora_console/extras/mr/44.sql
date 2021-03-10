START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tipam_network` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`network` varchar(100) NOT NULL default '',
	`name_network` varchar(255) default '',
	`description` text NOT NULL,
	`location` tinytext NOT NULL,
	`id_recon_task` int(10) unsigned NOT NULL,
	`scan_interval` tinyint(2) default 1,
	`monitoring` tinyint(2) default 0,
	`id_group` mediumint(8) unsigned NULL default 0,
	`lightweight_mode` tinyint(2) default 0,
	`users_operator` text,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_recon_task`) REFERENCES trecon_task(`id_rt`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tipam_ip` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`id_network` bigint(20) unsigned NOT NULL default 0,
	`id_agent` int(10) unsigned NOT NULL,
	`forced_agent` tinyint(2) NOT NULL default '0',
	`ip` varchar(100) NOT NULL default '',
	`ip_dec` int(10) unsigned NOT NULL,
	`id_os` int(10) unsigned NOT NULL,
	`forced_os` tinyint(2) NOT NULL default '0',
	`hostname` tinytext NOT NULL,
	`forced_hostname` tinyint(2) NOT NULL default '0',
	`comments` text NOT NULL,
	`alive` tinyint(2) NOT NULL default '0',
	`managed` tinyint(2) NOT NULL default '0',
	`reserved` tinyint(2) NOT NULL default '0',
	`time_last_check` datetime NOT NULL default '1970-01-01 00:00:00',
	`time_create` datetime NOT NULL default '1970-01-01 00:00:00',
	`users_operator` text,
	`time_last_edit` datetime NOT NULL default '1970-01-01 00:00:00',
	`enabled` tinyint(2) NOT NULL default '1',
	`generate_events` tinyint(2) NOT NULL default '0',
	`leased` tinyint(2) DEFAULT '0',
	`leased_expiration` bigint(20) DEFAULT '0',
	`mac_address` varchar(20) DEFAULT NULL,
	`leased_mode` tinyint(2) DEFAULT '0',
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_network`) REFERENCES tipam_network(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tipam_vlan` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`name` varchar(250) NOT NULL,
	`description` text,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tipam_vlan_network` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`id_vlan` bigint(20) unsigned NOT NULL,
	`id_network` bigint(20) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_vlan`) REFERENCES tipam_vlan(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`id_network`) REFERENCES tipam_network(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tipam_supernet` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`name` varchar(250) NOT NULL,
	`description` text default '',
	`address` varchar(250) NOT NULL,
	`mask` varchar(250) NOT NULL,
	`subneting_mask` varchar(250) default '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tipam_supernet_network` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`id_supernet` bigint(20) unsigned NOT NULL,
	`id_network` bigint(20) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_supernet`) REFERENCES tipam_supernet(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`id_network`) REFERENCES tipam_network(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET @insert_type = 3;
SET @insert_name = 'IPAM Recon';
SET @insert_description = 'This script is used to automatically detect network hosts availability and name, used as Recon Custom Script in the recon task. Parameters used are:\n\n* custom_field1 = network. i.e.: 192.168.100.0/24\n* custom_field2 = associated IPAM network id. i.e.: 4. Please do not change this value, it is assigned automatically in IPAM management.\n\nSee documentation for more information.';
SET @insert_script = '/usr/share/pandora_server/util/recon_scripts/IPAMrecon.pl';
SET @insert_macros = '{"1":{"macro":"_field1_","desc":"Network","help":"i.e.:&#x20;192.168.100.0/24","value":"","hide":""}}';
INSERT IGNORE INTO trecon_script (`id_recon_script`,`type`, `name`, `description`, `script`, `macros`) SELECT `id_recon_script`,`type`, `name`, `description`, `script`, `macros` FROM (SELECT `id_recon_script`,`type`, `name`, `description`, `script`, `macros` FROM `trecon_script` WHERE `name` = @insert_name UNION SELECT (SELECT max(`id_recon_script`)+1 FROM `trecon_script`) AS `id_recon_script`, @insert_type as `type`, @insert_name as `name`, @insert_description as `description`, @insert_script as `script`, @insert_macros as `macros`) t limit 1;

DELETE FROM `tconfig` WHERE `token` = 'ipam_installed';

DELETE FROM `tconfig` WHERE `token` = 'ipam_recon_script_id';

UPDATE `talert_commands` SET `fields_descriptions` = '[\"Event&#x20;text\",\"Event&#x20;type\",\"Source\",\"Agent&#x20;name&#x20;or&#x20;_agent_\",\"Event&#x20;severity\",\"ID&#x20;extra\",\"Tags&#x20;separated&#x20;by&#x20;commas\",\"Comments\",\"\",\"\"]' WHERE `id` = 3;

ALTER TABLE `talert_templates`
ADD COLUMN `field16` TEXT NOT NULL AFTER `field15`
,ADD COLUMN `field17` TEXT NOT NULL AFTER `field16`
,ADD COLUMN `field18` TEXT NOT NULL AFTER `field17`
,ADD COLUMN `field19` TEXT NOT NULL AFTER `field18`
,ADD COLUMN `field20` TEXT NOT NULL AFTER `field19`
,ADD COLUMN `field16_recovery` TEXT NOT NULL AFTER `field15_recovery`
,ADD COLUMN `field17_recovery` TEXT NOT NULL AFTER `field16_recovery`
,ADD COLUMN `field18_recovery` TEXT NOT NULL AFTER `field17_recovery`
,ADD COLUMN `field19_recovery` TEXT NOT NULL AFTER `field18_recovery`
,ADD COLUMN `field20_recovery` TEXT NOT NULL AFTER `field19_recovery`;

UPDATE `trecon_script` SET `description`='Specific&#x20;Pandora&#x20;FMS&#x20;Intel&#x20;DCM&#x20;Discovery&#x20;&#40;c&#41;&#x20;Artica&#x20;ST&#x20;2011&#x20;&lt;info@artica.es&gt;&#x0d;&#x0a;&#x0d;&#x0a;Usage:&#x20;./ipmi-recon.pl&#x20;&lt;task_id&gt;&#x20;&lt;group_id&gt;&#x20;&lt;custom_field1&gt;&#x20;&lt;custom_field2&gt;&#x20;&lt;custom_field3&gt;&#x20;&lt;custom_field4&gt;&#x0d;&#x0a;&#x0d;&#x0a;*&#x20;custom_field1&#x20;=&#x20;Network&#x20;i.e.:&#x20;192.168.100.0/24&#x0d;&#x0a;*&#x20;custom_field2&#x20;=&#x20;Username&#x0d;&#x0a;*&#x20;custom_field3&#x20;=&#x20;Password&#x0d;&#x0a;*&#x20;custom_field4&#x20;=&#x20;Additional&#x20;parameters&#x20;i.e.:&#x20;-D&#x20;LAN_2_0' WHERE `name`='IPMI&#x20;Recon';

ALTER TABLE `trecon_task` MODIFY COLUMN `review_mode` TINYINT(1) UNSIGNED DEFAULT 1;

DELETE FROM `tuser_task` WHERE id = 6;

UPDATE `tuser_task` SET `parameters`='a:4:{i:0;a:6:{s:11:"description";s:28:"Report pending to be created";s:5:"table";s:7:"treport";s:8:"field_id";s:9:"id_report";s:10:"field_name";s:4:"name";s:4:"type";s:3:"int";s:9:"acl_group";s:8:"id_group";}i:1;a:2:{s:11:"description";s:426:"Save to disk in path<a href="javascript:" class="tip" style="" ><img src="http://172.16.0.2/pandora_console/images/tip_help.png" data-title="The Apache user should have read-write access on this folder. E.g. /var/www/html/pandora_console/attachment" data-use_title_for_force_title="1" class="forced_title" alt="The Apache user should have read-write access on this folder. E.g. /var/www/html/pandora_console/attachment" /></a>";s:4:"type";s:6:"string";}i:2;a:2:{s:11:"description";s:16:"File nane prefix";s:4:"type";s:6:"string";}i:3;a:2:{s:11:"description";s:11:"Report Type";s:4:"type";s:11:"report_type";}}' WHERE `id`=3;

UPDATE `tuser_task_scheduled` SET 
    `args` = REPLACE (`args`, 'a:3', 'a:5'),
    `args`= REPLACE(`args`, 's:15:"first_execution"', 'i:2;s:0:"";i:3;s:3:"PDF";s:15:"first_execution"')
    WHERE `id_user_task` = 3;

UPDATE `tuser_task_scheduled` SET 
    `id_user_task` = 3, 
    `args` = REPLACE (`args`, 'a:3', 'a:5'),
    `args`= REPLACE(`args`, 's:15:"first_execution"', 'i:2;s:0:"";i:3;s:3:"XML";s:15:"first_execution"')
    WHERE `id_user_task` = 6;

ALTER TABLE `ttag` MODIFY COLUMN `name` text NOT NULL default '';

COMMIT;
