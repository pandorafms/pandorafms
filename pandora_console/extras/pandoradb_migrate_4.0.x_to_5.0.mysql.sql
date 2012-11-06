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
-- Table `tincidencia`
-- ---------------------------------------------------------------------
ALTER TABLE `tincidencia` ADD COLUMN `id_agent` int(10) unsigned NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tagente`
-- ---------------------------------------------------------------------
ALTER TABLE `tagente` ADD COLUMN `url_address` mediumtext NULL;
ALTER TABLE `tagente` ADD COLUMN `quiet` tinyint(1) NOT NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `talert_special_days`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_special_days` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`date` date NOT NULL DEFAULT '0000-00-00',
	`same_day` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL DEFAULT 'sunday',
	`description` text,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------
ALTER TABLE `talert_templates` ADD COLUMN `special_day` tinyint(1) DEFAULT '0';

------------------------------------------------------------------------
-- Table `tplanned_downtime`
------------------------------------------------------------------------
ALTER TABLE `tplanned_downtime` ADD COLUMN `monday` tinyint(1) default 0;
ALTER TABLE `tplanned_downtime` ADD COLUMN `tuesday` tinyint(1) default 0;
ALTER TABLE `tplanned_downtime` ADD COLUMN `wednesday` tinyint(1) default 0;
ALTER TABLE `tplanned_downtime` ADD COLUMN `thursday` tinyint(1) default 0;
ALTER TABLE `tplanned_downtime` ADD COLUMN `friday` tinyint(1) default 0;
ALTER TABLE `tplanned_downtime` ADD COLUMN `saturday` tinyint(1) default 0;
ALTER TABLE `tplanned_downtime` ADD COLUMN `sunday` tinyint(1) default 0;
ALTER TABLE `tplanned_downtime` ADD COLUMN `periodically_time_from` time NULL default NULL;
ALTER TABLE `tplanned_downtime` ADD COLUMN `periodically_time_to` time NULL default NULL;
ALTER TABLE `tplanned_downtime` ADD COLUMN `periodically_day_from` int(100) unsigned default NULL;
ALTER TABLE `tplanned_downtime` ADD COLUMN `periodically_day_to` int(100) unsigned default NULL;
ALTER TABLE `tplanned_downtime` ADD COLUMN `type_downtime` varchar(100) NOT NULL default 'disabled_agents_alerts';
ALTER TABLE `tplanned_downtime` ADD COLUMN `type_execution` varchar(100) NOT NULL default 'once';
ALTER TABLE `tplanned_downtime` ADD COLUMN `type_periodicity` varchar(100) NOT NULL default 'weekly';

------------------------------------------------------------------------
-- Table `tplanned_downtime_agents`
------------------------------------------------------------------------
DELETE FROM tplanned_downtime_agents
	WHERE id_downtime NOT IN (SELECT id FROM tplanned_downtime);
ALTER TABLE tplanned_downtime_agents  MODIFY `id_downtime` mediumint(8) NOT NULL;
ALTER TABLE tplanned_downtime_agents
	ADD FOREIGN KEY(`id_downtime`) REFERENCES tplanned_downtime(`id`)
	ON DELETE CASCADE;
ALTER TABLE `tplanned_downtime_agents` ADD COLUMN `all_modules` tinyint(1) default 1;

------------------------------------------------------------------------
-- Table `tplanned_downtime_modules`
------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tplanned_downtime_modules` (
	`id` int(20) unsigned NOT NULL auto_increment,
	`id_agent` mediumint(8) unsigned NOT NULL default '0',
	`id_agent_module` int(10) NOT NULL, 
	`id_downtime` mediumint(8) NOT NULL default '0',
	PRIMARY KEY  (`id`),
	FOREIGN KEY (`id_downtime`) REFERENCES tplanned_downtime(`id`)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

------------------------------------------------------------------------
-- Table `tevento`
------------------------------------------------------------------------
ALTER TABLE `tevento` ADD COLUMN (`source` tinytext NOT NULL, `id_extra` tinytext NOT NULL);
ALTER TABLE `tevento` MODIFY COLUMN `event_type` ENUM('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal','configuration_change')  CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT 'unknown';
ALTER TABLE `tevento` ADD COLUMN `critical_instructions` TEXT NOT NULL DEFAULT '';
ALTER TABLE `tevento` ADD COLUMN `warning_instructions` TEXT NOT NULL DEFAULT '';
ALTER TABLE `tevento` ADD COLUMN `unknown_instructions` TEXT NOT NULL DEFAULT '';
ALTER TABLE `tevento` ADD COLUMN `owner_user` VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE `tevento` ADD COLUMN `ack_utimestamp` BIGINT(20) NOT NULL DEFAULT '0';

------------------------------------------------------------------------
-- Table `tgrupo`
------------------------------------------------------------------------
ALTER TABLE `tgrupo` ADD COLUMN `description` text;
ALTER TABLE `tgrupo` ADD COLUMN `contact` text;
ALTER TABLE `tgrupo` ADD COLUMN `other` text;

------------------------------------------------------------------------
-- Table `talert_snmp`
------------------------------------------------------------------------
ALTER TABLE `talert_snmp` ADD COLUMN (`_snmp_f1_` text, `_snmp_f2_` text, `_snmp_f3_` text,
	`_snmp_f4_` text, `_snmp_f5_` text, `_snmp_f6_` text, `trap_type` int(11) NOT NULL default '-1',
	`single_value` varchar(255) DEFAULT '');

------------------------------------------------------------------------
-- Table `tagente_modulo`
------------------------------------------------------------------------
ALTER TABLE `tagente_modulo` ADD COLUMN `module_ff_interval` int(4) unsigned DEFAULT '0';
ALTER TABLE `tagente_modulo` CHANGE COLUMN `post_process` `post_process` double(18,5) DEFAULT NULL;
ALTER TABLE `tagente_modulo` ADD COLUMN `wizard_level` enum('basic','advanced','custom','nowizard') DEFAULT 'nowizard';
ALTER TABLE `tagente_modulo` ADD COLUMN `macros` text;
ALTER TABLE `tagente_modulo` ADD COLUMN `quiet` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tagente_modulo` ADD COLUMN `critical_instructions` TEXT NOT NULL DEFAULT '';
ALTER TABLE `tagente_modulo` ADD COLUMN `warning_instructions` TEXT NOT NULL DEFAULT '';
ALTER TABLE `tagente_modulo` ADD COLUMN `unknown_instructions` TEXT NOT NULL DEFAULT '';
ALTER TABLE `tagente_modulo` ADD COLUMN `critical_inverse` tinyint(1) unsigned default '0';
ALTER TABLE `tagente_modulo` ADD COLUMN `warning_inverse` tinyint(1) unsigned default '0';
ALTER TABLE `tagente_modulo` ADD COLUMN `cron_interval` varchar(100) default '';
ALTER TABLE `tagente_modulo` ADD COLUMN `max_retries` int(4) UNSIGNED NOT NULL default 0;

-- Move the number of retries for web modules from plugin_pass to max_retries
UPDATE `tagente_modulo` SET max_retries=plugin_pass WHERE id_modulo=7;

------------------------------------------------------------------------
-- Table `tnetwork_component`
------------------------------------------------------------------------
ALTER TABLE `tnetwork_component` CHANGE COLUMN `post_process` `post_process` double(18,5) default NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `unit` TEXT  NOT NULL AFTER `post_process`;
ALTER TABLE `tnetwork_component` ADD COLUMN `wizard_level` enum('basic','advanced','custom','nowizard') default 'nowizard';
ALTER TABLE `tnetwork_component` ADD COLUMN `only_metaconsole` tinyint(1) unsigned default '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `macros` text;
ALTER TABLE `tnetwork_component` ADD COLUMN `critical_instructions` TEXT NOT NULL default '';
ALTER TABLE `tnetwork_component` ADD COLUMN `warning_instructions` TEXT NOT NULL default '';
ALTER TABLE `tnetwork_component` ADD COLUMN `unknown_instructions` TEXT NOT NULL default '';
ALTER TABLE `tnetwork_component` ADD COLUMN `critical_inverse` tinyint(1) unsigned default '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `warning_inverse` tinyint(1) unsigned default '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `max_retries` int(4) UNSIGNED NOT NULL default 0;

------------------------------------------------------------------------
-- Table `tgraph_source` Alter table to allow negative values in weight
------------------------------------------------------------------------
ALTER TABLE tgraph_source MODIFY weight FLOAT(5,3) NOT NULL DEFAULT '0.000';

------------------------------------------------------------------------
-- Table `tevent_filter`
------------------------------------------------------------------------
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
	`tag` varchar(600) NOT NULL default '',
	`filter_only_alert` int(10) NOT NULL default -1, 
	PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

------------------------------------------------------------------------
-- Table `tconfig`
------------------------------------------------------------------------
ALTER TABLE tconfig MODIFY value TEXT NOT NULL;
-- Join the all ips of "list_ACL_IPs_for_API_%" in one row (now We have a field "value" with hudge size)
INSERT INTO tconfig (token, `value`) SELECT 'list_ACL_IPs_for_API', GROUP_CONCAT(`value` SEPARATOR ';') AS `value` FROM tconfig WHERE token LIKE "list_ACL_IPs_for_API%";
INSERT INTO `tconfig` (`token`, `value`) VALUES ('event_fields', 'evento,id_agente,estado,timestamp');
DELETE FROM tconfig WHERE token LIKE "list_ACL_IPs_for_API_%";
INSERT INTO `tconfig` (`token`, `value`) VALUES
	('enable_pass_policy', 0),
	('pass_size', 4),
	('pass_needs_numbers', 0),
	('pass_needs_symbols', 0),
	('pass_expire', 0),
	('first_login', 0),
	('mins_fail_pass', 5),
	('number_attempts', 5),
	('enable_pass_policy_admin', 0),
	('enable_pass_history', 0),
	('compare_pass', 3),
	('meta_style', 'meta_pandora'),
	('enable_refr', 0);
UPDATE tconfig SET `value`='comparation' WHERE `token`= 'prominent_time';

------------------------------------------------------------------------
-- Table `treport_content_item`
------------------------------------------------------------------------
ALTER TABLE treport_content_item ADD FOREIGN KEY (`id_report_content`) REFERENCES treport_content(`id_rc`) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `treport_content_item` ADD COLUMN `operation` TEXT;
ALTER TABLE treport ADD COLUMN `id_template` INTEGER UNSIGNED DEFAULT 0;
ALTER TABLE treport ADD COLUMN `id_group_edit` mediumint(8) unsigned NULL DEFAULT 0;
ALTER TABLE treport ADD COLUMN `metaconsole` tinyint(1) DEFAULT 0;

------------------------------------------------------------------------
-- Table `tgraph`
------------------------------------------------------------------------
ALTER TABLE `tgraph` ADD COLUMN `id_graph_template` int(11) NOT NULL DEFAULT 0;

------------------------------------------------------------------------
-- Table `ttipo_modulo`
------------------------------------------------------------------------
UPDATE ttipo_modulo SET descripcion='Generic data' WHERE id_tipo=1;
UPDATE ttipo_modulo SET descripcion='Generic data incremental' WHERE id_tipo=4;

------------------------------------------------------------------------
-- Table `tusuario`
------------------------------------------------------------------------
ALTER TABLE `tusuario` ADD COLUMN `section` TEXT NOT NULL;
INSERT INTO `tusuario` (`section`) VALUES ('Default');
ALTER TABLE `tusuario` ADD COLUMN `data_section` TEXT NOT NULL;
ALTER TABLE `tusuario` ADD COLUMN `disabled` int(4) NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `shortcut` tinyint(1) DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `shortcut_data` text;
ALTER TABLE `tusuario` ADD COLUMN `not_login` tinyint(1) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `force_change_pass` tinyint(1) DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `last_pass_change` DATETIME  NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `last_failed_login` DATETIME  NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `failed_attempt` int(4) NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `login_blocked` tinyint(1) DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `metaconsole_access` enum('basic','advanced','custom','all','only_console') DEFAULT 'only_console';

------------------------------------------------------------------------
-- Table `tmensajes`
------------------------------------------------------------------------
ALTER TABLE `tmensajes` MODIFY COLUMN `mensaje` TEXT NOT NULL;

------------------------------------------------------------------------
-- Table `talert_compound`
------------------------------------------------------------------------
ALTER TABLE `talert_compound` ADD COLUMN `special_day` tinyint(1) DEFAULT '0';

------------------------------------------------------------------------
-- Table `talert_commands`
------------------------------------------------------------------------
INSERT INTO `talert_commands` (`name`, `command`, `description`, `internal`) VALUES ('Validate Event','Internal type','This alert validate the events matched with a module given the agent name (_field1_) and module name (_field2_)', 1);

------------------------------------------------------------------------
-- Table `tpassword_history`
------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpassword_history` (
	`id_pass`  int(10) unsigned NOT NULL auto_increment,
	`id_user` varchar(60) NOT NULL,
	`password` varchar(45) default NULL,
	`date_begin` DATETIME  NOT NULL DEFAULT 0,
	`date_end` DATETIME  NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id_pass`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

------------------------------------------------------------------------
-- Table `tplugin`
------------------------------------------------------------------------
ALTER TABLE tplugin ADD `macros` text;
ALTER TABLE tplugin ADD `parameters` text;
ALTER TABLE tplugin ADD `max_retries` int(4) UNSIGNED NOT NULL default 0;

------------------------------------------------------------------------
-- Table `trecon_script`
------------------------------------------------------------------------
UPDATE trecon_script SET `description`='This&#x20;script&#x20;is&#x20;used&#x20;to&#x20;automatically&#x20;detect&#x20;SNMP&#x20;Interfaces&#x20;on&#x20;devices,&#x20;used&#x20;as&#x20;Recon&#x20;Custom&#x20;Script&#x20;in&#x20;the&#x20;recon&#x20;task.&#x20;Parameters&#x20;used&#x20;are:&#x0d;&#x0a;&#x0d;&#x0a;*&#x20;custom_field1&#x20;=&#x20;network.&#x20;i.e.:&#x20;192.168.100.0/24&#x0d;&#x0a;*&#x20;custom_field2&#x20;=&#x20;several&#x20;communities&#x20;separated&#x20;by&#x20;comma.&#x20;For&#x20;example:&#x20;snmp_community,public,private&#x20;&#x0d;&#x0a;*&#x20;custom_field3&#x20;=&#x20;optative&#x20;parameter&#x20;to&#x20;force&#x20;process&#x20;downed&#x20;interfaces&#x20;&#40;use:&#x20;&#039;-a&#039;&#41;.&#x20;Only&#x20;up&#x20;interfaces&#x20;are&#x20;processed&#x20;by&#x20;default&#x20;&#x0d;&#x0a;&#x0d;&#x0a;See&#x20;documentation&#x20;for&#x20;more&#x20;information.'
	WHERE id_recon_script = 1;

------------------------------------------------------------------------
-- Table `trecon_task
------------------------------------------------------------------------
ALTER TABLE trecon_task MODIFY subnet TEXT NOT NULL DEFAULT '';
ALTER TABLE trecon_task MODIFY field1 TEXT NOT NULL DEFAULT '';

------------------------------------------------------------------------
-- Table `tlayout_data
------------------------------------------------------------------------
ALTER TABLE tlayout_data ADD COLUMN `enable_link` tinyint(1) UNSIGNED NOT  NULL default 1;

------------------------------------------------------------------------
-- Table `tnetwork_map`
------------------------------------------------------------------------
ALTER TABLE tnetwork_map ADD `text_filter` VARCHAR(100)  NOT NULL DEFAULT "";
ALTER TABLE tnetwork_map ADD `dont_show_subgroups` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE tnetwork_map ADD `pandoras_children` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE tnetwork_map ADD `show_groups` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE tnetwork_map ADD `show_modules` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

-- ----------------------------------------------------------------------
-- Table `tagente_estado`
-- ----------------------------------------------------------------------
ALTER TABLE `tagente_estado` ADD COLUMN `last_known_status` tinyint(4) NOT NULL DEFAULT 0;
ALTER TABLE `tagente_estado` ADD COLUMN `last_error` int(4) NOT NULL DEFAULT '0',

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
-- Table `talert_actions`
-- ----------------------------------------------------------------------
ALTER TABLE `talert_actions` ADD COLUMN `field4` TEXT NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field5` TEXT NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field6` TEXT NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field7` TEXT NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field8` TEXT NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field9` TEXT NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field10` TEXT NOT NULL;

-- ----------------------------------------------------------------------
-- Table `talert_templates`
-- ----------------------------------------------------------------------
ALTER TABLE `talert_templates` ADD COLUMN `field4` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field5` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field6` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field7` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field8` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field9` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field10` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field4_recovery` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field5_recovery` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field6_recovery` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field7_recovery` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field8_recovery` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field9_recovery` TEXT NOT NULL;
ALTER TABLE `talert_templates` ADD COLUMN `field10_recovery` TEXT NOT NULL;

-- ----------------------------------------------------------------------
-- Table `talert_commands`
-- ----------------------------------------------------------------------
ALTER TABLE `talert_commands` ADD COLUMN `fields_descriptions` TEXT;
ALTER TABLE `talert_commands` ADD COLUMN `fields_values` TEXT;
