-- ---------------------------------------------------------------------
-- If user was open
-- ---------------------------------------------------------------------

-- -----------------------------------------------------
-- Table `tlocal_component`
-- -----------------------------------------------------
-- tlocal_component is a repository of local modules for
-- physical agents on Windows / Unix physical agents
CREATE TABLE IF NOT EXISTS `tlocal_component` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` text NOT NULL,
	`data` mediumtext NOT NULL,
	`description` varchar(1024) default NULL,
	`id_os` int(10) unsigned default '0',
	`os_version` varchar(100) default '',
	`id_network_component_group` int(10) unsigned NOT NULL default 0,
	`type` smallint(6) NOT NULL default '6',
	`max` bigint(20) NOT NULL default '0',
	`min` bigint(20) NOT NULL default '0',
	`module_interval` mediumint(8) unsigned NOT NULL default '0',
	`id_module_group` tinyint(4) unsigned NOT NULL default '0',
	`history_data` tinyint(1) unsigned default '1',
	`min_warning` double(18,2) default 0,
	`max_warning` double(18,2) default 0,
	`str_warning` text,
	`min_critical` double(18,2) default 0,
	`max_critical` double(18,2) default 0,
	`str_critical` text,
	`min_ff_event` int(4) unsigned default '0',
	`post_process` double(24,15) default 0,
	`unit` text,
	`wizard_level` enum('basic','advanced','nowizard') default 'nowizard',
	`macros` text,
	`critical_instructions` text NOT NULL default '',
	`warning_instructions` text NOT NULL default '',
	`unknown_instructions` text NOT NULL default '',
	`critical_inverse` tinyint(1) unsigned default '0',
	`warning_inverse` tinyint(1) unsigned default '0',
	`id_category` int(10) default 0,
	`tags` text NOT NULL default '',
	`disabled_types_event` TEXT NOT NULL DEFAULT '',
	`min_ff_event_normal` int(4) unsigned default '0',
	`min_ff_event_warning` int(4) unsigned default '0',
	`min_ff_event_critical` int(4) unsigned default '0',
	`each_ff` tinyint(1) unsigned default '0',
	`ff_timeout` int(4) unsigned default '0',
	`dynamic_interval` int(4) unsigned default '0',
	`dynamic_max` int(4) default '0',
	`dynamic_min` int(4) default '0',
	`prediction_sample_window` int(10) default 0,
	`prediction_samples` int(4) default 0,
	`prediction_threshold` int(4) default 0,
	PRIMARY KEY  (`id`),
	FOREIGN KEY (`id_network_component_group`) REFERENCES tnetwork_component_group(`id_sg`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tlocal_component` ADD COLUMN `dynamic_next` bigint(20) NOT NULL default '0';
ALTER TABLE `tlocal_component` ADD COLUMN `dynamic_two_tailed` tinyint(1) unsigned default '0';
ALTER TABLE `tlocal_component` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';

ALTER TABLE `tlocal_component` MODIFY COLUMN `ff_type` tinyint(1) unsigned NULL DEFAULT '0',
	MODIFY COLUMN `dynamic_next` bigint(20) NOT NULL DEFAULT '0',
	MODIFY COLUMN `dynamic_two_tailed` tinyint(1) unsigned NULL DEFAULT '0';

-- -----------------------------------------------------
-- Table `tpolicy_modules`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_modules` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy` int(10) unsigned NOT NULL default '0',
	`configuration_data` mediumtext NOT NULL,
	`id_tipo_modulo` smallint(5) NOT NULL default '0',
	`description` varchar(1024) NOT NULL default '',
	`name` varchar(200) NOT NULL default '',
	`unit` text default '',
	`max` bigint(20) default '0',
	`min` bigint(20) default '0',
	`module_interval` int(4) unsigned default '0',
	`tcp_port` int(4) unsigned default '0',
	`tcp_send` text default '',
	`tcp_rcv` text default '',
	`snmp_community` varchar(100) default '',
	`snmp_oid` varchar(255) default '0',
	`id_module_group` int(4) unsigned default '0',
	`flag` tinyint(1) unsigned default '1',
	`id_module` int(10) default '0',
	`disabled` tinyint(1) unsigned NOT NULL default '0',
	`id_export` smallint(4) unsigned default '0',
	`plugin_user` text default '',
	`plugin_pass` text default '',
	`plugin_parameter` text,
	`id_plugin` int(10) default '0',
	`post_process` double(24,15) default NULL,
	`prediction_module` bigint(14) default '0',
	`max_timeout` int(4) unsigned default '0',
	`max_retries` int(4) unsigned default '0',
	`custom_id` varchar(255) default '',
	`history_data` tinyint(1) unsigned default '1',
	`min_warning` double(18,2) default 0,
	`max_warning` double(18,2) default 0,
	`str_warning` text default '',
	`min_critical` double(18,2) default 0,
	`max_critical` double(18,2) default 0,
	`str_critical` text default '',
	`min_ff_event` int(4) unsigned default '0',
	`custom_string_1` text default '',
	`custom_string_2` text default '',
	`custom_string_3` text default '',
	`custom_integer_1` int(10) default 0,
	`custom_integer_2` int(10) default 0,
	`pending_delete` tinyint(1) default '0',
	`critical_instructions` text NOT NULL default '',
	`warning_instructions` text NOT NULL default '',
	`unknown_instructions` text NOT NULL default '',
	`critical_inverse` tinyint(1) unsigned default '0',
	`warning_inverse` tinyint(1) unsigned default '0',
	`id_category` int(10) default 0,
	`module_ff_interval` int(4) unsigned default '0',
	`quiet` tinyint(1) NOT NULL default '0',
	`cron_interval` varchar(100) default '',
	`macros` text,
	`disabled_types_event` TEXT NOT NULL default '',
	`module_macros` TEXT NOT NULL default '',
	`min_ff_event_normal` int(4) unsigned default '0',
	`min_ff_event_warning` int(4) unsigned default '0',
	`min_ff_event_critical` int(4) unsigned default '0',
	`each_ff` tinyint(1) unsigned default '0',
	`ff_timeout` int(4) unsigned default '0',
	`dynamic_interval` int(4) unsigned default '0',
	`dynamic_max` int(4) default '0',
	`dynamic_min` int(4) default '0',
	`prediction_sample_window` int(10) default 0,
	`prediction_samples` int(4) default 0,
	`prediction_threshold` int(4) default 0,
	PRIMARY KEY  (`id`),
	KEY `main_idx` (`id_policy`),
	UNIQUE (`id_policy`, `name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `tpolicy_modules` ADD COLUMN `dynamic_next` bigint(20) NOT NULL default '0';
ALTER TABLE `tpolicy_modules` ADD COLUMN `dynamic_two_tailed` tinyint(1) unsigned default '0';
ALTER TABLE `tpolicy_modules` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';
ALTER TABLE `tpolicy_modules` MODIFY COLUMN `ip_target` varchar(100) NULL DEFAULT '',
	MODIFY COLUMN `ff_type` tinyint(1) unsigned NULL DEFAULT '0',
	MODIFY COLUMN `dynamic_next` bigint(20) NOT NULL DEFAULT '0',
	MODIFY COLUMN `dynamic_two_tailed` tinyint(1) unsigned NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `tpolicies`
-- ---------------------------------------------------------------------
-- 'status' could be 0 (without changes, updated), 1 (needy update only database) or 2 (needy update database and conf files)
CREATE TABLE IF NOT EXISTS `tpolicies` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` text NOT NULL default '',
	`description` varchar(255) NOT NULL default '',
	`id_group` int(10) unsigned default '0',  
	`status` int(10) unsigned NOT NULL default 0,  
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `tpolicies` ADD COLUMN `force_apply` tinyint(1) default 0;

-- -----------------------------------------------------
-- Table `tpolicy_alerts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_alerts` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy` int(10) unsigned NOT NULL default '0',
	`id_policy_module` int(10) unsigned default '0',
	`id_alert_template` int(10) unsigned default '0',
	`name_extern_module` TEXT NOT NULL default '',
	`disabled` tinyint(1) default '0',
	`standby` tinyint(1) default '0',
	`pending_delete` tinyint(1) default '0',
	PRIMARY KEY  (`id`),
	FOREIGN KEY (`id_alert_template`) REFERENCES talert_templates(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`id_policy`) REFERENCES tpolicies(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tpolicy_agents`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_agents` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy` int(10) unsigned default '0',
	`id_agent` int(10) unsigned default '0',
	`policy_applied` tinyint(1) unsigned default '0',
	`pending_delete` tinyint(1) unsigned default '0',
	`last_apply_utimestamp` int(10) unsigned NOT NULL default 0,
	PRIMARY KEY  (`id`),
	UNIQUE (`id_policy`, `id_agent`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `tpolicy_agents` ADD COLUMN `id_node` int(10) NOT NULL DEFAULT '0';
ALTER TABLE `tpolicy_agents` ADD UNIQUE(`id_policy`, `id_agent`, `id_node`);
ALTER TABLE `tpolicy_agents` DROP INDEX `id_policy`, ADD UNIQUE INDEX `id_policy` (`id_policy`, `id_agent`, `id_node`), DROP INDEX `id_policy_2`;

-- -----------------------------------------------------
-- Table `tpolicy_groups`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_groups` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy` int(10) unsigned default '0',
	`id_group` int(10) unsigned default '0',
	`policy_applied` tinyint(1) unsigned default '0',
	`pending_delete` tinyint(1) unsigned default '0',
	`last_apply_utimestamp` int(10) unsigned NOT NULL default 0,
	PRIMARY KEY  (`id`),
	UNIQUE (`id_policy`, `id_group`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tdashboard`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tdashboard` (
	`id` int(20) unsigned NOT NULL auto_increment,
	`name` varchar(60) NOT NULL default '',
	`id_user` varchar(60) NOT NULL default '',
	`id_group` int(10) NOT NULL default 0,
	`active` tinyint(1) NOT NULL default 0,
	`cells` int(10) unsigned default 0,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tdatabase`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tdatabase` (
	`id` INT(10) unsigned NOT NULL auto_increment,
	`host` VARCHAR(255) default '',
	`label` VARCHAR(255) default '',
	`os_port` INT UNSIGNED NOT NULL DEFAULT 22,
	`os_user` VARCHAR(255) default '',
	`db_port` INT UNSIGNED NOT NULL DEFAULT 3306,
	`status` tinyint(1) unsigned default '0',
	`action` tinyint(1) unsigned default '0',
	`ssh_key` TEXT,
	`ssh_pubkey` TEXT,
	`last_error` TEXT,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ;

-- -----------------------------------------------------
-- Table `twidget`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `twidget` (
	`id` int(20) unsigned NOT NULL auto_increment,
	`class_name` varchar(60) NOT NULL default '',
	`unique_name` varchar(60) NOT NULL default '',
	`description` text NOT NULL default '',
	`options` text NOT NULL default '',
	`page` varchar(120) NOT NULL default '',
	PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `twidget_dashboard`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `twidget_dashboard` (
	`id` int(20) unsigned NOT NULL auto_increment,
	`options` LONGTEXT NOT NULL default '',
	`order` int(3) NOT NULL default 0,
	`id_dashboard` int(20) unsigned NOT NULL default 0,
	`id_widget` int(20) unsigned NOT NULL default 0,
	`prop_width` float(5,3) NOT NULL default 0.32,
	`prop_height` float(5,3) NOT NULL default 0.32,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_dashboard`) REFERENCES tdashboard(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tmodule_inventory`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule_inventory` (
	`id_module_inventory` int(10) NOT NULL auto_increment,
	`id_os` int(10) unsigned default NULL,
	`name` text default '',
	`description` text default '',
	`interpreter` varchar(100) default '',
	`data_format` text default '',
	`code` BLOB NOT NULL,
	`block_mode` int(3) NOT NULL default 0,
	PRIMARY KEY  (`id_module_inventory`),
	FOREIGN KEY (`id_os`) REFERENCES tconfig_os(`id_os`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tagent_module_inventory`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_module_inventory` (
	`id_agent_module_inventory` int(10) NOT NULL auto_increment,
	`id_agente` int(10) unsigned NOT NULL,
	`id_module_inventory` int(10) NOT NULL,
	`target` varchar(100) default '',
	`interval` int(10) unsigned NOT NULL default '3600',
	`username` varchar(100) default '',
	`password` varchar(100) default '',
	`data` MEDIUMBLOB NOT NULL,
	`timestamp` datetime default '1970-01-01 00:00:00',
	`utimestamp` bigint(20) default '0',
	`flag` tinyint(1) unsigned default '1',
	`id_policy_module_inventory` int(10) NOT NULL default '0',
	PRIMARY KEY  (`id_agent_module_inventory`),
	FOREIGN KEY (`id_agente`) REFERENCES tagente(`id_agente`)
		ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`id_module_inventory`) REFERENCES tmodule_inventory(`id_module_inventory`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tagent_module_inventory` ADD COLUMN `custom_fields` MEDIUMBLOB NOT NULL;

-- ---------------------------------------------------------------------
-- Table `tpolicy_modules_inventory`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_modules_inventory` (
	`id` int(10) NOT NULL auto_increment,
	`id_policy` int(10) unsigned NOT NULL,
	`id_module_inventory` int(10) NOT NULL,
	`interval` int(10) unsigned NOT NULL default '3600',
	`username` varchar(100) default '',
	`password` varchar(100) default '',
	`pending_delete` tinyint(1) default '0',
	PRIMARY KEY  (`id`),
	FOREIGN KEY (`id_policy`) REFERENCES tpolicies(`id`)
		ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`id_module_inventory`) REFERENCES tmodule_inventory(`id_module_inventory`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tpolicy_modules_inventory` ADD COLUMN `custom_fields` MEDIUMBLOB NOT NULL;

-- -----------------------------------------------------
-- Table `tagente_datos_inventory`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagente_datos_inventory` (
	`id_agent_module_inventory` int(10) NOT NULL,
	`data` MEDIUMBLOB NOT NULL,
	`utimestamp` bigint(20) default '0',
	`timestamp` datetime default '1970-01-01 00:00:00',
	KEY `idx_id_agent_module` (`id_agent_module_inventory`),
	KEY `idx_utimestamp` USING BTREE (`utimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tinventory_alert`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tinventory_alert`(
    `id` int UNSIGNED NOT NULL auto_increment,
    `id_module_inventory` int(10) NOT NULL,
    `actions` text NOT NULL default '',
	`id_group` mediumint(8) unsigned NULL default 0,
    `condition` enum('WHITE_LIST', 'BLACK_LIST', 'MATCH') NOT NULL default 'WHITE_LIST',
    `value` text NOT NULL default '',
    `name` tinytext NOT NULL default '',
    `description` text NOT NULL default '',
    `time_threshold` int(10) NOT NULL default '0',
    `last_fired` text NOT NULL default '',
    `disable_event` tinyint(1) UNSIGNED default 0,
    `enabled` tinyint(1) UNSIGNED default 1,
	`alert_groups` text NOT NULL default '',
	PRIMARY KEY (`id`),
    FOREIGN KEY (`id_module_inventory`) REFERENCES tmodule_inventory(`id_module_inventory`)
		ON DELETE CASCADE ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `ttrap_custom_values`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ttrap_custom_values` (
	`id` int(10) NOT NULL auto_increment,
	`oid` varchar(255) NOT NULL default '',
	`custom_oid` varchar(255) NOT NULL default '',
	`text` varchar(255) default '',
	`description` varchar(255) default '',
	`severity` tinyint(4) unsigned NOT NULL default '2',
	CONSTRAINT oid_custom_oid UNIQUE(oid, custom_oid),
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tmetaconsole_setup`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmetaconsole_setup` (
	`id` int(10) NOT NULL auto_increment primary key,
	`server_name` text default '',
	`server_url` text default '',
	`dbuser` text default '',
	`dbpass` text default '',
	`dbhost` text default '',
	`dbport` text default '',
	`dbname` text default '',
	`auth_token` text default '',
	`id_group` int(10) unsigned NOT NULL default 0,
	`api_password` text NOT NULL,
	`disabled` tinyint(1) unsigned NOT NULL default '0'
) ENGINE=InnoDB 
COMMENT = 'Table to store metaconsole sources' 
DEFAULT CHARSET=utf8;

ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbuser` text;
ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbpass` text;
ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbhost` text;
ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbport` text;
ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbname` text;

ALTER TABLE `tmetaconsole_setup` MODIFY COLUMN `meta_dbuser` text NULL,
	MODIFY COLUMN `meta_dbpass` text NULL,
	MODIFY COLUMN `meta_dbhost` text NULL,
	MODIFY COLUMN `meta_dbport` text NULL,
	MODIFY COLUMN `meta_dbname` text NULL;

ALTER TABLE `tmetaconsole_setup` ADD COLUMN `server_uid` TEXT NOT NULL default '';

ALTER TABLE `tmetaconsole_setup` ADD COLUMN `unified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tprofile_view`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tprofile_view` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_profile` int(10) unsigned NOT NULL default 0,
	`sec` text default '',
	`sec2` text default '',
	`sec3` text default '',
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB 
COMMENT = 'Table to define by each profile defined in Pandora, to which sec/page has access independently of its ACL (for showing in the console or not). By default have access to all pages allowed by ACL, if forbidden here, then pages are not shown.' 
DEFAULT CHARSET=utf8;


-- ---------------------------------------------------------------------
-- Table `tservice`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tservice` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	`description` text NOT NULL default '',
	`id_group` int(10) unsigned NOT NULL default 0,
	`critical` float(20,3) NOT NULL default 0,
	`warning` float(20,3) NOT NULL default 0,
	`service_interval` float(20,3) NOT NULL default 0,
	`service_value` float(20,3) NOT NULL default 0,
	`status` tinyint(3) NOT NULL default -1,
	`utimestamp` int(10) unsigned NOT NULL default 0,
	`auto_calculate` tinyint(1) unsigned NOT NULL default 1,
	`id_agent_module` int(10) unsigned NOT NULL default 0,
	`sla_interval` float(20,3) NOT NULL default 0,
	`sla_id_module` int(10) unsigned NOT NULL default 0,
	`sla_value_id_module` int(10) unsigned NOT NULL default 0,
	`sla_limit` float(20,3) NOT NULL default 100,
	`id_template_alert_warning` int(10) unsigned NOT NULL default 0,
	`id_template_alert_critical` int(10) unsigned NOT NULL default 0,
	`id_template_alert_unknown` int(10) unsigned NOT NULL default 0,
	`id_template_alert_critical_sla` int(10) unsigned NOT NULL default 0,
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB 
COMMENT = 'Table to define services to monitor' 
DEFAULT CHARSET=utf8;


-- ---------------------------------------------------------------------
-- Table `tservice_element`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tservice_element` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_service` int(10) unsigned NOT NULL,
	`weight_ok` float(20,3) NOT NULL default 0,
	`weight_warning` float(20,3) NOT NULL default 0,
	`weight_critical` float(20,3) NOT NULL default 0,
	`weight_unknown` float(20,3) NOT NULL default 0,
	`description` text NOT NULL default '',
	`id_agente_modulo` int(10) unsigned NOT NULL default 0,
	`id_agent` int(10) unsigned NOT NULL default 0,
	`id_service_child` int(10) unsigned NOT NULL default 0,
	`id_server_meta` int(10)  unsigned NOT NULL default 0,
	PRIMARY KEY  (`id`),
	INDEX `IDX_tservice_element` (`id_service`,`id_agente_modulo`)
) ENGINE=InnoDB 
COMMENT = 'Table to define the modules and the weights of the modules that define a service' 
DEFAULT CHARSET=utf8;


-- ---------------------------------------------------------------------
-- Table `tcollection`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcollection` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	`short_name` varchar(100) NOT NULL default '',
	`id_group` int(10) unsigned NOT NULL default 0,
	`description` mediumtext,
	`status` int(4) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- status: 0 - Not apply
-- status: 1 - Applied

-- ---------------------------------------------------------------------
-- Table `tpolicy_collections`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_collections` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy` int(10) unsigned NOT NULL default '0',
	`id_collection` int(10) unsigned default '0',
	`pending_delete` tinyint(1) default '0',
	PRIMARY KEY  (`id`),
	FOREIGN KEY (`id_policy`) REFERENCES `tpolicies` (`id`)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`id_collection`) REFERENCES `tcollection` (`id`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tpolicy_alerts_actions`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_alerts_actions` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy_alert` int(10) unsigned NOT NULL,
	`id_alert_action` int(10) unsigned NOT NULL,
	`fires_min` int(3) unsigned default 0,
	`fires_max` int(3) unsigned default 0,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_policy_alert`) REFERENCES `tpolicy_alerts` (`id`)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`id_alert_action`) REFERENCES `talert_actions` (`id`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tpolicy_plugins`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_plugins` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy` int(10) unsigned default '0',
	`plugin_exec` TEXT,
	`pending_delete` tinyint(1) default '0',
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tsesion_extended`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tsesion_extended` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_sesion` int(10) unsigned NOT NULL,
	`extended_info` TEXT default '',
	`hash` varchar(255) default '',
	PRIMARY KEY (`id`),
	KEY idx_session (id_sesion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tskin`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tskin` ( 
	`id` int(10) unsigned NOT NULL auto_increment, 
	`name` TEXT NOT NULL DEFAULT '',
	`relative_path` TEXT NOT NULL DEFAULT '', 
	`description` text NOT NULL DEFAULT '',
	`disabled` tinyint(2) NOT NULL default '0', 
	PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE `tskin` SET `name` = 'Default&#x20;theme' , `relative_path` = 'pandora.css' WHERE `id` = 1;
UPDATE `tskin` SET `name` = 'Black&#x20;theme' , `relative_path` = 'Black&#x20;theme' , `description` = 'Black&#x20;theme' WHERE `id` = 2;

-- ---------------------------------------------------------------------
-- Table `tpolicy_queue`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_queue` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy` int(10) unsigned NOT NULL default '0',
	`id_agent` int(10) unsigned NOT NULL default '0',
	`operation` varchar(15) default '',
	`progress` int(10) NOT NULL default '0',
	`end_utimestamp` int(10) unsigned NOT NULL default 0,
	`priority` int(10) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tevent_rule`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_rule` (
	`id_event_rule` int(10) unsigned NOT NULL auto_increment,
	`id_event_alert` int(10) unsigned NOT NULL,
	`operation` enum('NOP', 'AND','OR','XOR','NAND','NOR','NXOR'),
	`order` int(10) unsigned default '0',
	`window` int(10) NOT NULL default '0',
	`count` int(4) NOT NULL default '1',
	`agent` text default '',
	`id_usuario` varchar(100) NOT NULL default '',
	`id_grupo` mediumint(4) NOT NULL default '0',
	`evento` text NOT NULL default '',
	`event_type` enum('','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal') default 'unknown',
	`module` text default '',
	`alert` text default '',
	`criticity` int(4) unsigned NOT NULL default '0',
	`user_comment` text NOT NULL,
	`id_tag` integer(10) unsigned NOT NULL default '0',
	`name` text default '',
	PRIMARY KEY  (`id_event_rule`),
	KEY `idx_id_event_alert` (`id_event_alert`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tevent_rule` ADD COLUMN `group_recursion` INT(1) unsigned default 0;
ALTER TABLE `tevent_rule` ADD COLUMN `log_content` TEXT;
ALTER TABLE `tevent_rule` ADD COLUMN `log_source` TEXT;
ALTER TABLE `tevent_rule` ADD COLUMN `log_agent` TEXT;
ALTER TABLE `tevent_rule` ADD COLUMN `operator_agent` text COMMENT 'Operator for agent';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_id_usuario` text COMMENT 'Operator for id_usuario';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_id_grupo` text COMMENT 'Operator for id_grupo';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_evento` text COMMENT 'Operator for evento';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_event_type` text COMMENT 'Operator for event_type';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_module` text COMMENT 'Operator for module';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_alert` text COMMENT 'Operator for alert';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_criticity` text COMMENT 'Operator for criticity';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_user_comment` text COMMENT 'Operator for user_comment';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_id_tag` text COMMENT 'Operator for id_tag';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_log_content` text COMMENT 'Operator for log_content';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_log_source` text COMMENT 'Operator for log_source';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_log_agent` text COMMENT 'Operator for log_agent';
ALTER TABLE `tevent_rule` MODIFY COLUMN `event_type` enum('','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal') default '';
ALTER TABLE `tevent_rule` MODIFY COLUMN `criticity` int(4) unsigned DEFAULT NULL;
ALTER TABLE `tevent_rule` MODIFY COLUMN `id_grupo` mediumint(4) DEFAULT NULL;

ALTER TABLE `tevent_rule` MODIFY COLUMN `agent` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `id_usuario` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `id_grupo` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `evento` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `event_type` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `module` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `alert` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `criticity` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `user_comment` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `id_tag` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `name` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `group_recursion` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `log_content` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `log_source` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `log_agent` TEXT;

UPDATE `tevent_rule` SET `operator_agent` = "REGEX" WHERE `agent` != '';
UPDATE `tevent_rule` SET `operator_id_usuario` = "REGEX" WHERE `id_usuario` != '';
UPDATE `tevent_rule` SET `operator_id_grupo` = "REGEX" WHERE `id_grupo` > 0;
UPDATE `tevent_rule` SET `operator_evento` = "REGEX" WHERE `evento` != '';
UPDATE `tevent_rule` SET `operator_event_type` = "REGEX" WHERE `event_type` != '';
UPDATE `tevent_rule` SET `operator_module` = "REGEX" WHERE `module` != '';
UPDATE `tevent_rule` SET `operator_alert` = "REGEX" WHERE `alert` != '';
UPDATE `tevent_rule` SET `operator_criticity` = "REGEX" WHERE `criticity` != '99';
UPDATE `tevent_rule` SET `operator_user_comment` = "REGEX" WHERE `user_comment` != '';
UPDATE `tevent_rule` SET `operator_id_tag` = "REGEX" WHERE `id_tag` > 0;
UPDATE `tevent_rule` SET `operator_log_content` = "REGEX" WHERE `log_content` != '';
UPDATE `tevent_rule` SET `operator_log_source` = "REGEX" WHERE `log_source` != '';
UPDATE `tevent_rule` SET `operator_log_agent` = "REGEX" WHERE `log_agent` != '';

-- -----------------------------------------------------
-- Table `tevent_alert`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_alert` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` text default '',
	`description` mediumtext,
	`order` int(10) unsigned default 0,
	`mode` enum('PASS','DROP'),
	`field1` text NOT NULL default '',
	`field2` text NOT NULL default '',
	`field3` text NOT NULL default '',
	`field4` text NOT NULL default '',
	`field5` text NOT NULL default '',
	`field6` text NOT NULL default '',
	`field7` text NOT NULL default '',
	`field8` text NOT NULL default '',
	`field9` text NOT NULL default '',
	`field10` text NOT NULL default '',
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
	`field2_recovery` text NOT NULL default '',
	`field3_recovery` text NOT NULL,
	`id_group` mediumint(8) unsigned NULL default 0,
	`internal_counter` int(4) default '0',
	`last_fired` bigint(20) NOT NULL default '0',
	`last_reference` bigint(20) NOT NULL default '0',
	`times_fired` int(3) NOT NULL default '0',
	`disabled` tinyint(1) default '0',
	`standby` tinyint(1) default '0',
	`priority` tinyint(4) default '0',
	`force_execution` tinyint(1) default '0',
	`group_by` enum ('','id_agente','id_agentmodule','id_alert_am','id_grupo') default '',
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tevent_alert` ADD COLUMN `special_days` tinyint(1) default 0;
ALTER TABLE `tevent_alert` MODIFY COLUMN `time_threshold` int(10) NOT NULL default 86400;
ALTER TABLE `tevent_alert` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `tevent_alert` ADD COLUMN `id_template_conditions` int(10) unsigned NOT NULL default 0;
ALTER TABLE `tevent_alert` ADD COLUMN `id_template_fields` int(10) unsigned NOT NULL default 0;
ALTER TABLE `tevent_alert` ADD COLUMN `last_evaluation` bigint(20) NOT NULL default 0;
ALTER TABLE `tevent_alert` ADD COLUMN `pool_occurrences` int unsigned not null default 0;
ALTER TABLE `tevent_alert` ADD COLUMN `schedule` TEXT;

-- -----------------------------------------------------
-- Table `tevent_alert_action`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_alert_action` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_event_alert` int(10) unsigned NOT NULL,
	`id_alert_action` int(10) unsigned NOT NULL,
	`fires_min` int(3) unsigned default 0,
	`fires_max` int(3) unsigned default 0,
	`module_action_threshold` int(10) NOT NULL default '0',
	`last_execution` bigint(20) NOT NULL default '0',
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_event_alert`) REFERENCES tevent_alert(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- -----------------------------------------------------
-- Table `tmodule_synth`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule_synth` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_agent_module_source` int(10) unsigned NOT NULL DEFAULT 0,
	`id_agent_module_target` int(10) unsigned NOT NULL DEFAULT 0,
	`fixed_value` float NOT NULL DEFAULT 0,
	`operation` enum ('ADD', 'SUB', 'DIV', 'MUL', 'AVG', 'NOP') NOT NULL DEFAULT 'NOP',
	`order` int(11) NOT NULL DEFAULT '0',
	FOREIGN KEY (`id_agent_module_target`) REFERENCES tagente_modulo(`id_agente_modulo`)
		ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- -----------------------------------------------------
-- Table `tnetworkmap_enterprise`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetworkmap_enterprise` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(500) default '',
	`id_group` int(10) unsigned NOT NULL default 0,
	`options` text default '',
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- -----------------------------------------------------
-- Table `tnetworkmap_enterprise_nodes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetworkmap_enterprise_nodes` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_networkmap_enterprise` int(10) unsigned NOT NULL,
	`x` int(10) default 0,
	`y` int(10) default 0,
	`z` int(10) default 0,
	`id_agent` int(10) default 0,
	`id_module` int(10) default 0,
	`id_agent_module` int(10) default 0,
	`parent` int(10) default 0,
	`options` text default '',
	`deleted` int(10) default 0,
	`state` varchar(150) NOT NULL default '',
	PRIMARY KEY (id),
	FOREIGN KEY (`id_networkmap_enterprise`) REFERENCES tnetworkmap_enterprise(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- -----------------------------------------------------
-- Table `tnetworkmap_ent_rel_nodes` (Before `tnetworkmap_enterprise_relation_nodes`)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetworkmap_ent_rel_nodes` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_networkmap_enterprise` int(10) unsigned NOT NULL,
	`parent` int(10) default 0,
	`parent_type` varchar(30) default 'node',
	`child` int(10) default 0,
	`child_type` varchar(30) default 'node',
	`deleted` int(10) default 0,
	PRIMARY KEY (id, id_networkmap_enterprise),
	FOREIGN KEY (`id_networkmap_enterprise`) REFERENCES tnetworkmap_enterprise(`id`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `treport_template`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_template` (
	`id_report` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
	`id_user` varchar(100) NOT NULL default '',
	`name` varchar(150) NOT NULL default '',
	`description` TEXT NOT NULL,
	`private` tinyint(1) UNSIGNED NOT NULL default 0,
	`id_group` mediumint(8) unsigned NULL default NULL,
	`custom_logo` varchar(200)  default NULL,
	`header` MEDIUMTEXT  default NULL,
	`first_page` MEDIUMTEXT default NULL,
	`footer` MEDIUMTEXT default NULL,
	`custom_font` varchar(200) default NULL,
	`metaconsole` tinyint(1) DEFAULT 0,
	`agent_regex` varchar(600) NOT NULL default '',
	`cover_page_render` tinyint(1) NOT NULL DEFAULT 1,
	`index_render` tinyint(1) NOT NULL DEFAULT 1,
	PRIMARY KEY(`id_report`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `treport_content_template`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_template` (
	`id_rc` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_report` INTEGER UNSIGNED NOT NULL default 0,
	`id_gs` INTEGER UNSIGNED NULL default NULL,
	`text_agent_module` text,
	`type` varchar(30) default 'simple_graph',
	`period` int(11) NOT NULL default 0,
	`order` int (11) NOT NULL default 0,
	`description` mediumtext,
	`text_agent` text,
	`text` TEXT,
	`external_source` mediumtext,
	`treport_custom_sql_id` INTEGER UNSIGNED default 0,
	`header_definition` TinyText default NULL,
	`column_separator` TinyText default NULL,
	`line_separator` TinyText default NULL,
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
	`exact_match` tinyint(1) default 0,
	`module_names` TEXT,
	`module_free_text` TEXT,
	`each_agent` tinyint(1) default 1,
	PRIMARY KEY(`id_rc`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE treport_content_template ADD COLUMN `historical_db` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE treport_content_template ADD COLUMN `lapse_calc` tinyint(1) default '0';
ALTER TABLE treport_content_template ADD COLUMN `lapse` int(11) default '300';
ALTER TABLE treport_content_template ADD COLUMN `visual_format` tinyint(1) default '0';
ALTER TABLE treport_content_template ADD COLUMN `hide_no_data` tinyint(1) default '0';
ALTER TABLE `treport_content_template` ADD COLUMN `total_time` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_failed` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_in_ok_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_in_unknown_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_of_not_initialized_module` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_of_downtime` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `total_checks` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `checks_failed` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `checks_in_ok_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `unknown_checks` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `agent_max_value` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `agent_min_value` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `current_month` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `failover_mode` tinyint(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `failover_type` tinyint(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `summary` tinyint(1) DEFAULT 0;
ALTER TABLE `treport_content_template` ADD COLUMN `uncompressed_module` TINYINT DEFAULT '0';
ALTER TABLE `treport_content_template` MODIFY COLUMN `historical_db` tinyint(1) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `lapse_calc` tinyint(1) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `lapse` int(11) unsigned NOT NULL DEFAULT '300',
	MODIFY COLUMN `visual_format` tinyint(1) unsigned NOT NULL DEFAULT '0';
ALTER TABLE `treport_content_template` ADD COLUMN `landscape` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content_template` ADD COLUMN `pagebreak` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content_template` ADD COLUMN `compare_work_time` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content_template` ADD COLUMN `graph_render` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content_template` ADD COLUMN `time_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content_template` ADD COLUMN `checks_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content_template` ADD COLUMN `ipam_network_filter` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `treport_content_template` ADD COLUMN `ipam_alive_ips` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `treport_content_template` ADD COLUMN `ipam_ip_not_assigned_to_agent` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

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

-- -----------------------------------------------------
-- Table `treport_content_sla_com_temp` (treport_content_sla_combined_template)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_sla_com_temp` (
	`id` INTEGER UNSIGNED NOT NULL auto_increment,
	`id_report_content` INTEGER UNSIGNED NOT NULL,
	`text_agent` text,
	`text_agent_module` text,
	`sla_max` double(18,2) NOT NULL default 0,
	`sla_min` double(18,2) NOT NULL default 0,
	`sla_limit` double(18,2) NOT NULL default 0,
	`server_name` text,
	`exact_match` tinyint(1) default 0,
	PRIMARY KEY(`id`),
	FOREIGN KEY (`id_report_content`) REFERENCES treport_content_template(`id_rc`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `treport_content_item_temp` (treport_content_item_template)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_item_temp` (
	`id` INTEGER UNSIGNED NOT NULL auto_increment, 
	`id_report_content` INTEGER UNSIGNED NOT NULL, 
	`text_agent` text,
	`text_agent_module` text,
	`server_name` text,
	`exact_match` tinyint(1) default 0,
	`operation` text,	
	PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tgraph_template`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgraph_template` (
	`id_graph_template` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
	`id_user` TEXT NOT NULL,
	`name` TEXT NOT NULL,
	`description` TEXT NOT NULL,
	`period` int(11) NOT NULL default '0',
	`width` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
	`height` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
	`private` tinyint(1) UNSIGNED NOT NULL default 0,
	`events` tinyint(1) UNSIGNED NOT NULL default 0,
	`stacked` tinyint(1) UNSIGNED NOT NULL default 0,
	`id_group` mediumint(8) unsigned NULL default 0,
	PRIMARY KEY(`id_graph_template`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tgraph_source_template`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgraph_source_template` (
	`id_gs_template` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_template` int(11) NOT NULL default 0,
	`agent` TEXT, 
	`module` TEXT,
	`weight` FLOAT(5,3) NOT NULL DEFAULT 2,
	`exact_match` tinyint(1) default 0, 
	PRIMARY KEY(`id_gs_template`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_event`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmetaconsole_event` (
	`id_evento` bigint(20) unsigned NOT NULL auto_increment,
	`id_source_event` bigint(20) unsigned NOT NULL,
	`id_agente` int(10) NOT NULL default '0',
	`agent_name` varchar(600) BINARY NOT NULL default '',
	`id_usuario` varchar(100) NOT NULL default '0',
	`id_grupo` mediumint(4) NOT NULL default '0',
	`group_name` varchar(100) NOT NULL default '',
	`estado` tinyint(3) unsigned NOT NULL default '0',
	`timestamp` datetime NOT NULL default '1970-01-01 00:00:00',
	`evento` text NOT NULL,
	`utimestamp` bigint(20) NOT NULL default '0',
	`event_type` enum('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change') default 'unknown',
	`id_agentmodule` int(10) NOT NULL default '0',
	`module_name` varchar(600) NOT NULL,
	`id_alert_am` int(10) NOT NULL default '0',
	`alert_template_name` text,
	`criticity` int(4) unsigned NOT NULL default '0',
	`user_comment` text NOT NULL,
	`tags` text NOT NULL,
	`source` tinytext NOT NULL,
	`id_extra` tinytext NOT NULL,
	`critical_instructions` text NOT NULL default '',
	`warning_instructions` text NOT NULL default '',
	`unknown_instructions` text NOT NULL default '',
	`owner_user` VARCHAR(100) NOT NULL DEFAULT '',
	`ack_utimestamp` BIGINT(20) NOT NULL DEFAULT '0',
	`server_id` int(10) NOT NULL,
	`custom_data` TEXT NOT NULL DEFAULT '',
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
-- Criticity: 5 - Minor
-- Criticity: 6 - Major

ALTER TABLE `tmetaconsole_event` ADD COLUMN `data` double(50,5) default NULL;
ALTER TABLE `tmetaconsole_event` ADD COLUMN `module_status` int(4) NOT NULL default '0';
ALTER TABLE `tmetaconsole_event` ADD INDEX `server_id` (`server_id`);
ALTER TABLE `tmetaconsole_event` ADD INDEX `tme_timestamp_idx` (`timestamp`);
ALTER TABLE `tmetaconsole_event` ADD INDEX `tme_module_status_idx` (`module_status`);
ALTER TABLE `tmetaconsole_event` ADD INDEX `tme_criticity_idx` (`criticity`);
ALTER TABLE `tmetaconsole_event` ADD INDEX `tme_agent_name_idx` (`agent_name`);
ALTER TABLE `tmetaconsole_event` MODIFY `data` TINYTEXT default NULL;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_event_history`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmetaconsole_event_history` (
	`id_evento` bigint(20) unsigned NOT NULL auto_increment,
	`id_source_event` bigint(20) unsigned NOT NULL,
	`id_agente` int(10) NOT NULL default '0',
	`agent_name` varchar(600) BINARY NOT NULL default '',
	`id_usuario` varchar(100) NOT NULL default '0',
	`id_grupo` mediumint(4) NOT NULL default '0',
	`group_name` varchar(100) NOT NULL default '',
	`estado` tinyint(3) unsigned NOT NULL default '0',
	`timestamp` datetime NOT NULL default '1970-01-01 00:00:00',
	`evento` text NOT NULL,
	`utimestamp` bigint(20) NOT NULL default '0',
	`event_type` enum('going_unknown','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal', 'configuration_change') default 'unknown',
	`id_agentmodule` int(10) NOT NULL default '0',
	`module_name` varchar(600) NOT NULL,
	`id_alert_am` int(10) NOT NULL default '0',
	`alert_template_name` text,
	`criticity` int(4) unsigned NOT NULL default '0',
	`user_comment` text NOT NULL,
	`tags` text NOT NULL,
	`source` tinytext NOT NULL,
	`id_extra` tinytext NOT NULL,
	`critical_instructions` text NOT NULL default '',
	`warning_instructions` text NOT NULL default '',
	`unknown_instructions` text NOT NULL default '',
	`owner_user` VARCHAR(100) NOT NULL DEFAULT '',
	`ack_utimestamp` BIGINT(20) NOT NULL DEFAULT '0',
	`server_id` int(10) NOT NULL,
	`custom_data` TEXT NOT NULL DEFAULT '',
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
-- Criticity: 5 - Minor
-- Criticity: 6 - Major

ALTER TABLE `tmetaconsole_event_history` ADD COLUMN `data` double(50,5) default NULL;
ALTER TABLE `tmetaconsole_event_history` ADD COLUMN `module_status` int(4) NOT NULL default '0';
ALTER TABLE `tmetaconsole_event_history` ADD INDEX `tmeh_estado_idx` (`estado`);
ALTER TABLE `tmetaconsole_event_history` ADD INDEX `tmeh_timestamp_idx` (`timestamp`);
-- ---------------------------------------------------------------------
-- Table `textension_translate_string`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `textension_translate_string` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`lang` VARCHAR(10) NOT NULL ,
	`string` TEXT NOT NULL DEFAULT '' ,
	`translation` TEXT NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	KEY `lang_index` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tagent_module_log`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_module_log` (
	`id_agent_module_log` int(10) NOT NULL AUTO_INCREMENT,
	`id_agent` int(10) unsigned NOT NULL,
	`source` text NOT NULL,
	`timestamp` datetime DEFAULT '1970-01-01 00:00:00',
	`utimestamp` bigint(20) DEFAULT '0',
	PRIMARY KEY (`id_agent_module_log`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tevent_custom_field`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_custom_field` (
	`id_group` mediumint(4) unsigned NOT NULL,
	`value` text NOT NULL,
	PRIMARY KEY  (`id_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmetaconsole_agent` (
	`id_agente` int(10) unsigned NOT NULL auto_increment,
	`id_tagente` int(10) unsigned NOT NULL,
	`id_tmetaconsole_setup` int(10) NOT NULL,
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
	`remote` tinyint(1) NOT NULL default '0',
	`id_parent` int(10) unsigned default '0',
	`custom_id` varchar(255) default '',
	`server_name` varchar(100) default '',
	`cascade_protection` tinyint(2) NOT NULL default '0',
	`cascade_protection_module` int(10) unsigned default '0',
	`timezone_offset` TINYINT(2) NULL DEFAULT '0' COMMENT 'number of hours of diference with the server timezone' ,
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
	`update_secondary_groups` tinyint(1) NOT NULL default '0',
	`transactional_agent` tinyint(1) NOT NULL default '0',
	`alias` varchar(600) BINARY NOT NULL default '',
	`alias_as_name` tinyint(2) NOT NULL default '0',
	`safe_mode_module` int(10) unsigned NOT NULL default '0',
	`cps` int NOT NULL default 0,
	PRIMARY KEY  (`id_agente`),
	KEY `nombre` (`nombre`(255)),
	KEY `direccion` (`direccion`),
	KEY `id_tagente_idx` (`id_tagente`),
	KEY `disabled` (`disabled`),
	KEY `id_grupo` (`id_grupo`),
	FOREIGN KEY (`id_tmetaconsole_setup`) REFERENCES tmetaconsole_setup(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `tmetaconsole_agent` ADD COLUMN `remote` tinyint(1) NOT NULL DEFAULT '0',
	ADD COLUMN `cascade_protection_module` int(10) unsigned NULL DEFAULT '0',
	ADD COLUMN `transactional_agent` tinyint(1) NOT NULL DEFAULT '0',
	ADD COLUMN `alias` varchar(600) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
	MODIFY COLUMN `update_secondary_groups` tinyint(1) NOT NULL DEFAULT '0',
	MODIFY COLUMN `alias_as_name` tinyint(2) NOT NULL DEFAULT '0',
	ADD INDEX `id_tagente_idx` (`id_tagente`),
	ADD INDEX `tma_id_os_idx` (`id_os`),
    ADD INDEX `tma_server_name_idx` (`server_name`);

-- ---------------------------------------------------------------------
-- Table `ttransaction`
-- ---------------------------------------------------------------------
create table IF NOT EXISTS `ttransaction` (
    `transaction_id` int unsigned NOT NULL auto_increment,
    `agent_id` int(10) unsigned NOT NULL,
    `group_id` int(10) unsigned NOT NULL default '0',
    `description` text,
    `name` varchar(250) NOT NULL,
    `loop_interval` int unsigned NOT NULL default 40,
    `ready` int unsigned NOT NULL default 0,
    `running` int unsigned NOT NULL default 0,
    PRIMARY KEY (`transaction_id`)
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tphase`
-- ---------------------------------------------------------------------
create table IF NOT EXISTS `tphase`(
    `phase_id` int unsigned not null auto_increment,
    `transaction_id` int unsigned not null,
    `agent_id` int(10) unsigned not null,
    `name` varchar(250) not null,
    `idx` int unsigned not null,
    `dependencies` text,
    `enables` text,
    `launch` text,
    `retries` int unsigned default null,
    `timeout` int unsigned default null,
    PRIMARY KEY (`phase_id`,`transaction_id`)
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- MIGRATION
-- ---------------------------------------------------------------------

-- ---------------------------------------------------------------------
-- Table `titem`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `titem` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_map` int(10) unsigned NOT NULL default 0,
	`x` INTEGER NOT NULL default 0,
	`y` INTEGER NOT NULL default 0,
	`z` INTEGER NOT NULL default 0,
	`deleted` INTEGER(1) unsigned NOT NULL default 0,
	`type` INTEGER UNSIGNED NOT NULL default 0,
	`refresh` INTEGER UNSIGNED NOT NULL default 0,
	`source` INTEGER UNSIGNED NOT NULL default 0,
	`source_data` varchar(250) default '',
	`options` TEXT,
	`style` TEXT,
	PRIMARY KEY(`id`)
)  ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `titem` MODIFY COLUMN `source_data` varchar(250) NULL DEFAULT '';

-- ---------------------------------------------------------------------
-- Table `tmap`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmap` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_group` int(10) unsigned NOT NULL default 0,
	`id_user` varchar(250) NOT NULL default '',
	`type` int(10) unsigned NOT NULL default 0,
	`subtype` int(10) unsigned NOT NULL default 0,
	`name` varchar(250) default '',
	`description` TEXT,
	`height` INTEGER UNSIGNED NOT NULL default 0,
	`width` INTEGER UNSIGNED NOT NULL default 0,
	`center_x` INTEGER NOT NULL default 0,
	`center_y` INTEGER NOT NULL default 0,
	`background` varchar(250) default '',
	`background_options` INTEGER UNSIGNED NOT NULL default 0,
	`source_period` INTEGER UNSIGNED NOT NULL default 0,
	`source` INTEGER UNSIGNED NOT NULL default 0,
	`source_data`  varchar(250) default '',
	`generation_method` INTEGER UNSIGNED NOT NULL default 0,
	`generated` INTEGER UNSIGNED NOT NULL default 0,
	`filter` TEXT,
	`id_group_map` INT(10) UNSIGNED NOT NULL default 0,

	PRIMARY KEY(`id`)
)  ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `trel_item`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trel_item` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_parent` int(10) unsigned NOT NULL default 0,
	`id_child` int(10) unsigned NOT NULL default 0,
	`id_map` int(11) unsigned NOT NULL default 0,
	`id_parent_source_data` int(11) unsigned NOT NULL default 0,
	`id_child_source_data` int(11) unsigned NOT NULL default 0,
	`parent_type` int(10) unsigned NOT NULL default 0,
	`child_type` int(10) unsigned NOT NULL default 0,
	`id_item` int(10) unsigned NOT NULL default 0,
	`deleted` int(1) unsigned NOT NULL default 0,
	PRIMARY KEY(`id`)
)  ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `trel_item` MODIFY COLUMN `id_map` int(10) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `id_parent_source_data` int(10) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `id_child_source_data` int(10) unsigned NOT NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------
ALTER TABLE talert_templates ADD COLUMN `min_alerts_reset_counter` tinyint(1) DEFAULT 0;
ALTER TABLE talert_templates ADD COLUMN `field11` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field12` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field13` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field14` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field15` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field16` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field17` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field18` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field19` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field20` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field11_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field12_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field13_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field14_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field15_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field16_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field17_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field18_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field19_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field20_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_templates` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `talert_templates` ADD COLUMN `schedule` TEXT;

-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE `talert_snmp` ADD COLUMN `al_field11` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `al_field12` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `al_field13` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `al_field14` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `al_field15` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `al_field16` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `al_field17` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `al_field18` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `al_field19` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `al_field20` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `talert_snmp` MODIFY COLUMN `al_field11` text NOT NULL,
	MODIFY COLUMN `al_field12` text NOT NULL,
	MODIFY COLUMN `al_field13` text NOT NULL,
	MODIFY COLUMN `al_field14` text NOT NULL,
	MODIFY COLUMN `al_field15` text NOT NULL;


-- ---------------------------------------------------------------------
-- Table `talert_snmp_action`
-- ---------------------------------------------------------------------
ALTER TABLE talert_snmp_action ADD COLUMN `al_field11` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN `al_field12` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN `al_field13` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN `al_field14` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp_action ADD COLUMN `al_field15` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field16` TEXT NOT NULL AFTER `al_field15`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field17` TEXT NOT NULL AFTER `al_field16`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field18` TEXT NOT NULL AFTER `al_field17`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field19` TEXT NOT NULL AFTER `al_field18`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field20` TEXT NOT NULL AFTER `al_field19`;

-- ----------------------------------------------------------------------
-- Table `tserver`
-- ----------------------------------------------------------------------
ALTER TABLE tserver ADD COLUMN `server_keepalive` int(11) DEFAULT 0;
ALTER TABLE `tserver` MODIFY COLUMN `server_keepalive` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `tserver` MODIFY COLUMN `version` varchar(25) NOT NULL DEFAULT '';

-- ----------------------------------------------------------------------
-- Table `tagente_estado`
-- ----------------------------------------------------------------------
ALTER TABLE tagente_estado MODIFY `status_changes` tinyint(4) unsigned default 0;
ALTER TABLE tagente_estado CHANGE `last_known_status` `known_status` tinyint(4) default 0;
ALTER TABLE tagente_estado ADD COLUMN `last_known_status` tinyint(4) default 0;
ALTER TABLE tagente_estado ADD COLUMN last_unknown_update bigint(20) NOT NULL default 0;
ALTER TABLE `tagente_estado` ADD COLUMN `ff_normal` int(4) unsigned default '0';
ALTER TABLE `tagente_estado` ADD COLUMN `ff_warning` int(4) unsigned default '0';
ALTER TABLE `tagente_estado` ADD COLUMN `ff_critical` int(4) unsigned default '0';
ALTER TABLE `tagente_estado` MODIFY COLUMN `datos` mediumtext NOT NULL,
	MODIFY COLUMN `known_status` tinyint(4) NULL DEFAULT '0',
	MODIFY COLUMN `last_known_status` tinyint(4) NULL DEFAULT '0',
	MODIFY COLUMN `last_dynamic_update` bigint(20) NOT NULL DEFAULT '0',
	MODIFY COLUMN `last_unknown_update` bigint(20) NOT NULL DEFAULT '0';
ALTER TABLE `tagente_estado` ADD COLUMN `last_status_change` bigint(20) NOT NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `talert_actions`
-- ---------------------------------------------------------------------
UPDATE talert_actions SET   `field4` = 'integria',
							`field5` = '_agent_:&#x20;_alert_name_',
							`field6` = '1',
							`field7` = '3',
							`field8` = 'copy@dom.com',
							`field9` = 'admin',
							`field10` = '_alert_description_'
WHERE `id` = 4 AND `id_alert_command` = 11;
UPDATE talert_actions SET name='Monitoring&#x20;Event' WHERE name='Pandora&#x20;FMS&#x20;Event';
ALTER TABLE talert_actions ADD COLUMN `field11` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field12` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field13` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field14` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field15` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field16` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field17` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field18` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field19` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field20` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field11_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field12_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field13_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field14_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field15_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field16_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field17_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field18_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field19_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field20_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_actions` ADD COLUMN `previous_name` text;

ALTER TABLE `talert_actions` MODIFY COLUMN `field11` text NOT NULL,
	MODIFY COLUMN `field12` text NOT NULL,
	MODIFY COLUMN `field13` text NOT NULL,
	MODIFY COLUMN `field14` text NOT NULL,
	MODIFY COLUMN `field15` text NOT NULL;

ALTER TABLE `talert_actions` ADD COLUMN `create_wu_integria` TINYINT(1) default NULL;

-- ---------------------------------------------------------------------
-- Table `talert_commands`
-- ---------------------------------------------------------------------
UPDATE `talert_commands` SET `fields_descriptions` = '[\"Integria&#x20;IMS&#x20;API&#x20;path\",\"Integria&#x20;IMS&#x20;API&#x20;pass\",\"Integria&#x20;IMS&#x20;user\",\"Integria&#x20;IMS&#x20;user&#x20;pass\",\"Ticket&#x20;title\",\"Ticket&#x20;group&#x20;ID\",\"Ticket&#x20;priority\",\"Email&#x20;copy\",\"Ticket&#x20;owner\",\"Ticket&#x20;description\"]', `fields_values` = '[\"\",\"\",\"\",\"\",\"\",\"\",\"10,Maintenance;0,Informative;1,Low;2,Medium;3,Serious;4,Very&#x20;Serious\",\"\",\"\",\"\"]' WHERE `id` = 11 AND `name` = 'Integria&#x20;IMS&#x20;Ticket';
UPDATE `talert_commands` SET `description` = 'This&#x20;alert&#x20;send&#x20;an&#x20;email&#x20;using&#x20;internal&#x20;Pandora&#x20;FMS&#x20;Server&#x20;SMTP&#x20;capabilities&#x20;&#40;defined&#x20;in&#x20;each&#x20;server,&#x20;using:&#x0d;&#x0a;_field1_&#x20;as&#x20;destination&#x20;email&#x20;address,&#x20;and&#x0d;&#x0a;_field2_&#x20;as&#x20;subject&#x20;for&#x20;message.&#x20;&#x0d;&#x0a;_field3_&#x20;as&#x20;text&#x20;of&#x20;message.&#x20;&#x0d;&#x0a;_field4_&#x20;as&#x20;content&#x20;type&#x20;&#40;text/plain&#x20;or&#x20;html/text&#41;.', `fields_descriptions` = '[\"Destination&#x20;address\",\"Subject\",\"Text\",\"Content&#x20;Type\",\"\",\"\",\"\",\"\",\"\",\"\"]', `fields_values` = '[\"\",\"\",\"_html_editor_\",\"_content_type_\",\"\",\"\",\"\",\"\",\"\",\"\"]' WHERE id=1;
ALTER TABLE `talert_commands` ADD COLUMN `id_group` mediumint(8) unsigned NULL default 0;
ALTER TABLE `talert_commands` ADD COLUMN `fields_hidden` text;
ALTER TABLE `talert_commands` ADD COLUMN `previous_name` text;

UPDATE `talert_actions` SET `field4` = 'text/html', `field4_recovery` = 'text/html' WHERE id = 1;

DELETE FROM `talert_commands` WHERE `id` = 11;

ALTER TABLE `talert_commands` MODIFY COLUMN `id_group` mediumint(8) unsigned NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `tmap`
-- ---------------------------------------------------------------------
ALTER TABLE `tmap` MODIFY COLUMN `id_user` varchar(250) NOT NULL DEFAULT '';

-- ---------------------------------------------------------------------
-- Table `ttag`
-- ---------------------------------------------------------------------
ALTER TABLE `ttag` ADD COLUMN `previous_name` text NULL;
ALTER TABLE `ttag` MODIFY COLUMN `name` text NOT NULL default '';

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO `tconfig` (`token`, `value`) VALUES ('big_operation_step_datos_purge', '100');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('small_operation_step_datos_purge', '1000');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('days_autodisable_deletion', '30');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('MR', 53);
INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_docs_logo', 'default_docs.png');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_support_logo', 'default_support.png');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_logo_white_bg_preview', 'pandora_logo_head_white_bg.png');
UPDATE tconfig SET value = 'https://licensing.artica.es/pandoraupdate7/server.php' WHERE token='url_update_manager';
DELETE FROM `tconfig` WHERE `token` = 'current_package_enterprise';
INSERT INTO `tconfig` (`token`, `value`) VALUES ('current_package', 761);
INSERT INTO `tconfig` (`token`, `value`) VALUES ('status_monitor_fields', 'policy,agent,data_type,module_name,server_type,interval,status,graph,warn,data,timestamp');
UPDATE `tconfig` SET `value` = 'mini_severity,evento,id_agente,estado,timestamp' WHERE `token` LIKE 'event_fields';
DELETE FROM `tconfig` WHERE `token` LIKE 'integria_api_password';
DELETE FROM `tconfig` WHERE `token` LIKE 'integria_inventory';
DELETE FROM `tconfig` WHERE `token` LIKE 'integria_url';
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_user', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_pass', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_hostname', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_api_pass', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_req_timeout', 5);
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_group', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_criticity', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_creator', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_owner', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_type', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_status', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_title', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_content', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_default_group', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_default_criticity', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_default_creator', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_default_owner', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_incident_type', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_incident_status', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_incident_title', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_incident_content', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('post_process_custom_values', '{"0.00000038580247":"Seconds&#x20;to&#x20;months","0.00000165343915":"Seconds&#x20;to&#x20;weeks","0.00001157407407":"Seconds&#x20;to&#x20;days","0.01666666666667":"Seconds&#x20;to&#x20;minutes","0.00000000093132":"Bytes&#x20;to&#x20;Gigabytes","0.00000095367432":"Bytes&#x20;to&#x20;Megabytes","0.00097656250000":"Bytes&#x20;to&#x20;Kilobytes","0.00000001653439":"Timeticks&#x20;to&#x20;weeks","0.00000011574074":"Timeticks&#x20;to&#x20;days"}');

-- ---------------------------------------------------------------------
-- Table `tconfig_os`
-- ---------------------------------------------------------------------

ALTER TABLE `tconfig_os` ADD COLUMN `previous_name` text NULL;

INSERT INTO `tconfig_os` (`id_os`, `name`, `description`, `icon_name`) VALUES (100, 'Cluster', 'Cluster agent', 'so_cluster.png', '');
	
UPDATE `tagente` SET `id_os` = 100 WHERE `id_os` = 21 and (select `id_os` from `tconfig_os` WHERE `id_os` = 21 and `name` = 'Cluster');

DELETE FROM `tconfig_os` where `id_os` = 21 and `name` = 'Cluster';

-- ---------------------------------------------------------------------
-- Table `tplanned_downtime_agents`
-- ---------------------------------------------------------------------
ALTER TABLE tplanned_downtime_agents ADD COLUMN `manually_disabled` tinyint(1) DEFAULT 0;


-- ---------------------------------------------------------------------
-- Table `tlink`
-- ---------------------------------------------------------------------
UPDATE `tlink` SET `link` = 'http://library.pandorafms.com/' WHERE `name` = 'Module library';
UPDATE `tlink` SET `name` = 'Enterprise Edition' WHERE `id_link` = 0000000002;
UPDATE `tlink` SET `name` = 'Documentation', `link` = 'https://pandorafms.com/manual/' WHERE `id_link` = 0000000001;
UPDATE `tlink` SET `link` = 'http://forums.pandorafms.com/index.php?board=22.0' WHERE `id_link` = 0000000004;
UPDATE `tlink` SET `link` = 'https://github.com/pandorafms/pandorafms/issues' WHERE `id_link` = 0000000003;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------
ALTER TABLE tevent_filter ADD COLUMN `date_from` date DEFAULT NULL;
ALTER TABLE tevent_filter ADD COLUMN `date_to` date DEFAULT NULL;
ALTER TABLE tevent_filter ADD COLUMN `user_comment` text NOT NULL;
ALTER TABLE tevent_filter ADD COLUMN `source` tinytext NOT NULL;
ALTER TABLE tevent_filter ADD COLUMN `id_extra` tinytext NOT NULL;
ALTER TABLE tevent_filter ADD COLUMN `id_source_event` int(10);
ALTER TABLE `tevent_filter` MODIFY COLUMN `user_comment` text NOT NULL;
ALTER TABLE `tevent_filter` MODIFY COLUMN `severity` text NOT NULL;
ALTER TABLE tevent_filter ADD COLUMN `server_id` int(10) NOT NULL default 0;
ALTER TABLE `tevent_filter` ADD COLUMN `time_from` TIME NULL;
ALTER TABLE `tevent_filter` ADD COLUMN `time_to` TIME NULL;
ALTER TABLE `tevent_filter` ADD COLUMN `custom_data` VARCHAR(500) DEFAULT '';
ALTER TABLE `tevent_filter` ADD COLUMN `custom_data_filter_type` TINYINT UNSIGNED DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tusuario`
-- ---------------------------------------------------------------------

ALTER TABLE tusuario ADD COLUMN `id_filter` int(10) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE tusuario ADD CONSTRAINT `fk_id_filter` FOREIGN KEY (`id_filter`) REFERENCES tevent_filter(`id_filter`) ON DELETE SET NULL;
ALTER TABLE tusuario ADD COLUMN `session_time` int(10) signed NOT NULL default '0';
alter table tusuario add autorefresh_white_list text not null default '';
ALTER TABLE tusuario ADD COLUMN `time_autorefresh` int(5) unsigned NOT NULL default '30';
ALTER TABLE `tusuario` DROP COLUMN `flash_chart`;
ALTER TABLE `tusuario` ADD COLUMN `default_custom_view` int(10) unsigned NULL default '0';
ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_level_user` VARCHAR(60);
ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_level_pass` VARCHAR(45);
ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_level_enabled` TINYINT(1);
ALTER TABLE `tusuario` MODIFY COLUMN `default_event_filter` int(10) unsigned NOT NULL DEFAULT '0',
	ADD INDEX `fk_filter_id` (`id_filter`),
	ADD CONSTRAINT `fk_filter_id` FOREIGN KEY `fk_filter_id` (`id_filter`) REFERENCES `tevent_filter` (`id_filter`) ON DELETE SET NULL ON UPDATE RESTRICT,
	DROP FOREIGN KEY `fk_id_filter`,
	DROP INDEX `fk_id_filter`;
ALTER TABLE `tusuario` ADD COLUMN `integria_user_level_user` VARCHAR(60);
ALTER TABLE `tusuario` ADD COLUMN `integria_user_level_pass` VARCHAR(45);
ALTER TABLE `tusuario` ADD COLUMN `local_user` tinyint(1) unsigned NOT NULL DEFAULT 0;


-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_modulo ADD COLUMN `dynamic_next` bigint(20) NOT NULL default '0';
ALTER TABLE tagente_modulo ADD COLUMN `dynamic_two_tailed` tinyint(1) unsigned default '0';
ALTER TABLE tagente_modulo ADD COLUMN `parent_module_id` int(10) unsigned NOT NULL default 0;
ALTER TABLE `tagente_modulo` ADD COLUMN `cps` int NOT NULL default 0;
ALTER TABLE `tagente_modulo` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';
ALTER TABLE `tagente_modulo` DROP COLUMN `ff_normal`,
	DROP COLUMN `ff_warning`,
	DROP COLUMN `ff_critical`,
	MODIFY COLUMN `ff_type` tinyint(1) unsigned NULL DEFAULT '0',
	MODIFY COLUMN `dynamic_next` bigint(20) NOT NULL DEFAULT '0',
	MODIFY COLUMN `dynamic_two_tailed` tinyint(1) unsigned NULL DEFAULT '0';
ALTER TABLE tagente_modulo MODIFY COLUMN `custom_string_1` MEDIUMTEXT;
ALTER TABLE `tagente_modulo` ADD COLUMN `debug_content` TEXT;

-- ---------------------------------------------------------------------
-- Table `tagente_datos`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_datos MODIFY `datos` double(50,5);
ALTER TABLE `tagente_datos` DROP INDEX `data_index1`, ADD INDEX `data_index1` (`id_agente_modulo`, `utimestamp`);

-- ---------------------------------------------------------------------
-- Table `tagente_datos_string`
-- ---------------------------------------------------------------------
ALTER TABLE `tagente_datos_string` MODIFY COLUMN `datos` mediumtext NOT NULL, DROP INDEX `data_string_index_1`, ADD INDEX `data_string_index_1` (`id_agente_modulo`, `utimestamp`);

-- ---------------------------------------------------------------------
-- Table `tagente_datos_inc`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_datos_inc MODIFY `datos` double(50,5);

-- ---------------------------------------------------------------------
-- Table `tnetwork_component`
-- ---------------------------------------------------------------------
ALTER TABLE tnetwork_component ADD COLUMN `dynamic_interval` int(4) unsigned default '0';
ALTER TABLE tnetwork_component ADD COLUMN `dynamic_max` int(4) default '0';
ALTER TABLE tnetwork_component ADD COLUMN `dynamic_min` int(4) default '0';
ALTER TABLE tnetwork_component ADD COLUMN `dynamic_next` bigint(20) NOT NULL default '0';
ALTER TABLE tnetwork_component ADD COLUMN `dynamic_two_tailed` tinyint(1) unsigned default '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';
ALTER TABLE `tnetwork_component` MODIFY COLUMN `ff_type` tinyint(1) unsigned NULL DEFAULT '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `manufacturer_id` varchar(200) NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `protocol` tinytext NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `module_type` tinyint UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `tnetwork_component` ADD COLUMN `execution_type` tinyint UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `tnetwork_component` ADD COLUMN `scan_type` tinyint UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `tnetwork_component` ADD COLUMN `value` text NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `value_operations` text NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `module_enabled` tinyint(1) UNSIGNED DEFAULT '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `name_oid` varchar(255) NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `query_class` varchar(200) NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `query_key_field` varchar(200) NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `scan_filters` text NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `query_filters` text NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `enabled` tinyint(1) UNSIGNED DEFAULT 1;

-- ----------------------------------------------------------------------
-- Table `tpen`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpen` (
  `pen` int(10) unsigned NOT NULL,
  `manufacturer` TEXT,
  `description` TEXT,
  PRIMARY KEY (`pen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnetwork_profile_pen`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_profile_pen` (
  `pen` int(10) unsigned NOT NULL,
  `id_np` int(10) unsigned NOT NULL,
  CONSTRAINT `fk_network_profile_pen_pen` FOREIGN KEY (`pen`)
    REFERENCES `tpen` (`pen`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_network_profile_pen_id_np` FOREIGN KEY (`id_np`)
    REFERENCES `tnetwork_profile` (`id_np`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tagente`
-- ---------------------------------------------------------------------
ALTER TABLE tagente ADD `transactional_agent` tinyint(1) NOT NULL default 0;
ALTER TABLE tagente ADD `remote` tinyint(1) NOT NULL default 0;
ALTER TABLE tagente ADD COLUMN `cascade_protection_module` int(10) unsigned NOT NULL default '0';
ALTER TABLE tagente ADD COLUMN (alias varchar(600) not null default '');
ALTER TABLE tagente ADD `alias_as_name` int(2) unsigned default '0';
ALTER TABLE tagente ADD COLUMN `safe_mode_module` int(10) unsigned NOT NULL default '0';
ALTER TABLE `tagente` ADD COLUMN `cps` int NOT NULL default 0;

UPDATE tagente SET tagente.alias = tagente.nombre;

ALTER TABLE `tagente` MODIFY COLUMN `remote` tinyint(1) NOT NULL DEFAULT '0',
	MODIFY COLUMN `cascade_protection_module` int(10) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `update_secondary_groups` tinyint(1) NOT NULL DEFAULT '0',
	MODIFY COLUMN `alias` varchar(600) NOT NULL DEFAULT '',
	MODIFY COLUMN `nombre` varchar(600) NOT NULL DEFAULT '',
	MODIFY COLUMN `alias_as_name` tinyint(2) NOT NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `tservice`
-- ---------------------------------------------------------------------
ALTER TABLE `tservice` ADD COLUMN `quiet` tinyint(1) NOT NULL default 0;
ALTER TABLE `tservice` ADD COLUMN `cps` int NOT NULL default 0;
ALTER TABLE `tservice` ADD COLUMN `cascade_protection` tinyint(1) NOT NULL default 0;
ALTER TABLE `tservice` ADD COLUMN `evaluate_sla` int(1) NOT NULL default 0;
ALTER TABLE `tservice` ADD COLUMN `is_favourite` tinyint(1) NOT NULL default 0;
ALTER TABLE `tservice` ADD COLUMN `unknown_as_critical` tinyint(1) NOT NULL default 0 AFTER `warning`;

UPDATE tservice SET `is_favourite` = 1 WHERE `name` REGEXP '^[_|.|\[|\(]';
-- ALl previous services are manual now.
UPDATE `tservice` SET `auto_calculate` = 0;

-- ---------------------------------------------------------------------
-- Table `tservice_element`
-- ---------------------------------------------------------------------
ALTER TABLE `tservice_element` ADD COLUMN `rules` text;

-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout ADD `background_color` varchar(50) NOT NULL default '#FFF';
ALTER TABLE tlayout ADD `is_favourite` int(1) NOT NULL DEFAULT 0;
ALTER TABLE tlayout MODIFY `name` varchar(600) NOT NULL;

UPDATE tlayout SET is_favourite = 1 WHERE name REGEXP '^&#40;' OR name REGEXP '^\\[';

ALTER TABLE `tlayout` MODIFY COLUMN `is_favourite` int(10) unsigned NOT NULL DEFAULT '0';

ALTER TABLE `tlayout` ADD COLUMN `auto_adjust` INTEGER UNSIGNED NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout_data ADD `type_graph` varchar(50) NOT NULL default 'area';
ALTER TABLE tlayout_data ADD `label_position` varchar(50) NOT NULL default 'down';
ALTER TABLE tlayout_data ADD COLUMN `show_statistics` tinyint(2) NOT NULL default '0';
ALTER TABLE tlayout_data ADD COLUMN `element_group` int(10) NOT NULL default '0';
ALTER TABLE tlayout_data ADD COLUMN `id_layout_linked_weight` int(10) NOT NULL default '0';
ALTER TABLE tlayout_data ADD COLUMN `show_on_top` tinyint(1) NOT NULL default '0';
ALTER TABLE tlayout_data ADD COLUMN `clock_animation` varchar(60) NOT NULL default "analogic_1";
ALTER TABLE tlayout_data ADD COLUMN `time_format` varchar(60) NOT NULL default "time";
ALTER TABLE tlayout_data ADD COLUMN `timezone` varchar(60) NOT NULL default "Europe/Madrid";
ALTER TABLE tlayout_data ADD COLUMN `show_last_value` tinyint(1) UNSIGNED NULL default '0';
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_status_type` ENUM ('default', 'weight', 'service') DEFAULT 'default';
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_status_as_service_warning` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_status_as_service_critical` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_node_id` INT(10) NOT NULL default 0;
ALTER TABLE `tlayout_data` ADD COLUMN `cache_expiration` INTEGER UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tlayout_data` MODIFY COLUMN `type_graph` varchar(50) NOT NULL DEFAULT 'area',
	MODIFY COLUMN `label_position` varchar(50) NOT NULL DEFAULT 'down',
	MODIFY COLUMN `linked_layout_node_id` int(10) NOT NULL DEFAULT '0',
	MODIFY COLUMN `linked_layout_status_type` enum('default','weight','service') NULL DEFAULT 'default',
	MODIFY COLUMN `element_group` int(10) NOT NULL DEFAULT '0',
	MODIFY COLUMN `linked_layout_status_as_service_warning` float(20,3) NOT NULL DEFAULT '0.000',
	MODIFY COLUMN `linked_layout_status_as_service_critical` float(20,3) NOT NULL DEFAULT '0.000';

-- ---------------------------------------------------------------------
-- Table `tagent_custom_fields`
-- ---------------------------------------------------------------------
INSERT INTO `tagent_custom_fields` (`name`) VALUES ('eHorusID');
ALTER TABLE tagent_custom_fields ADD `is_password_type` tinyint(1) NOT NULL DEFAULT 0; 

-- ---------------------------------------------------------------------
-- Table `tagente_modulo` Fixed problems with blank space 
-- in cron interval and problems with process data from pandora server
-- ---------------------------------------------------------------------
UPDATE tagente_modulo SET cron_interval = '' WHERE cron_interval LIKE '%    %';

-- ---------------------------------------------------------------------
-- Table `tgraph`
-- ---------------------------------------------------------------------
ALTER TABLE tgraph ADD COLUMN `percentil` int(4) unsigned default '0';
ALTER TABLE tgraph ADD COLUMN `summatory_series` tinyint(1) UNSIGNED NOT NULL default '0';
ALTER TABLE tgraph ADD COLUMN `average_series`  tinyint(1) UNSIGNED NOT NULL default '0';
ALTER TABLE tgraph ADD COLUMN `modules_series`  tinyint(1) UNSIGNED NOT NULL default '0';
ALTER TABLE tgraph ADD COLUMN `fullscale` tinyint(1) UNSIGNED NOT NULL default '0';
ALTER TABLE `tgraph` MODIFY COLUMN `percentil` tinyint(1) unsigned NOT NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `tnetflow_filter`
-- ---------------------------------------------------------------------
ALTER TABLE tnetflow_filter ADD COLUMN `router_ip` TEXT NOT NULL DEFAULT "";
UPDATE `tnetflow_filter` SET aggregate="dstip" WHERE aggregate NOT IN ("dstip", "srcip", "dstport", "srcport");

-- ---------------------------------------------------------------------
-- Table `treport_custom_sql`
-- ---------------------------------------------------------------------
UPDATE treport_custom_sql SET `sql` = 'select&#x20;direccion,&#x20;alias,&#x20;comentarios,&#x20;&#40;select&#x20;nombre&#x20;from&#x20;tgrupo&#x20;where&#x20;tgrupo.id_grupo&#x20;=&#x20;tagente.id_grupo&#41;&#x20;as&#x20;`group`&#x20;from&#x20;tagente;' 
	WHERE id = 1;
UPDATE treport_custom_sql SET `sql` = 'select&#x20;&#40;select&#x20;tagente.alias&#x20;from&#x20;tagente&#x20;where&#x20;tagente.id_agente&#x20;=&#x20;tagente_modulo.id_agente&#41;&#x20;as&#x20;agent_nombre,&#x20;nombre&#x20;,&#x20;&#40;select&#x20;tmodule_group.name&#x20;from&#x20;tmodule_group&#x20;where&#x20;tmodule_group.id_mg&#x20;=&#x20;tagente_modulo.id_module_group&#41;&#x20;as&#x20;module_group,&#x20;module_interval&#x20;from&#x20;tagente_modulo&#x20;where&#x20;delete_pending&#x20;=&#x20;0&#x20;order&#x20;by&#x20;nombre;' 
	WHERE id = 2;
UPDATE treport_custom_sql SET `sql` = 'select&#x20;t1.alias&#x20;as&#x20;agent_name,&#x20;t2.nombre&#x20;as&#x20;module_name,&#x20;&#40;select&#x20;talert_templates.name&#x20;from&#x20;talert_templates&#x20;where&#x20;talert_templates.id&#x20;=&#x20;t3.id_alert_template&#41;&#x20;as&#x20;template,&#x20;&#40;select&#x20;group_concat&#40;t02.name&#41;&#x20;from&#x20;talert_template_module_actions&#x20;as&#x20;t01&#x20;inner&#x20;join&#x20;talert_actions&#x20;as&#x20;t02&#x20;on&#x20;t01.id_alert_action&#x20;=&#x20;t02.id&#x20;where&#x20;t01.id_alert_template_module&#x20;=&#x20;t3.id&#x20;group&#x20;by&#x20;t01.id_alert_template_module&#41;&#x20;as&#x20;actions&#x20;from&#x20;tagente&#x20;as&#x20;t1&#x20;inner&#x20;join&#x20;tagente_modulo&#x20;as&#x20;t2&#x20;on&#x20;t1.id_agente&#x20;=&#x20;t2.id_agente&#x20;inner&#x20;join&#x20;talert_template_modules&#x20;as&#x20;t3&#x20;on&#x20;t2.id_agente_modulo&#x20;=&#x20;t3.id_agent_module&#x20;order&#x20;by&#x20;agent_name,&#x20;module_name;' 
	WHERE id = 3;

-- ----------------------------------------------------------------------
-- Table `treport_content`
-- ---------------------------------------------------------------------
ALTER TABLE treport_content ADD COLUMN `historical_db` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE treport_content ADD COLUMN `lapse_calc` tinyint(1) default '0';
ALTER TABLE treport_content ADD COLUMN `lapse` int(11) default '300';
ALTER TABLE treport_content ADD COLUMN `visual_format` tinyint(1) default '0';
ALTER TABLE treport_content ADD COLUMN `hide_no_data` tinyint(1) default '0';
ALTER TABLE treport_content ADD COLUMN `recursion` tinyint(1) default NULL;
ALTER TABLE treport_content ADD COLUMN `show_extended_events` tinyint(1) default '0';
UPDATE `treport_content` SET type="netflow_summary" WHERE type="netflow_pie" OR type="netflow_statistics";
ALTER TABLE `treport_content` ADD COLUMN `total_time` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_failed` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_in_ok_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_in_unknown_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_of_not_initialized_module` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_of_downtime` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `total_checks` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `checks_failed` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `checks_in_ok_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `unknown_checks` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `agent_max_value` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `agent_min_value` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `current_month` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `failover_mode` tinyint(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `failover_type` tinyint(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `summary` tinyint(1) DEFAULT 0;
ALTER table `treport_content` MODIFY COLUMN `name` varchar(300) NULL;
ALTER TABLE `treport_content` ADD COLUMN `uncompressed_module` TINYINT DEFAULT '0';
ALTER TABLE `treport_content` MODIFY COLUMN `historical_db` tinyint(1) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `lapse_calc` tinyint(1) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `lapse` int(11) unsigned NOT NULL DEFAULT '300',
	MODIFY COLUMN `visual_format` tinyint(1) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `failover_mode` tinyint(1) NULL DEFAULT '1',
	MODIFY COLUMN `failover_type` tinyint(1) NULL DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `landscape` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content` ADD COLUMN `pagebreak` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content` ADD COLUMN `compare_work_time` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content` ADD COLUMN `graph_render` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content` MODIFY `external_source` MEDIUMTEXT;
ALTER TABLE `treport_content` ADD COLUMN `time_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `checks_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `ipam_network_filter` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `ipam_alive_ips` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `ipam_ip_not_assigned_to_agent` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tmodule_relationship`
-- ---------------------------------------------------------------------
ALTER TABLE tmodule_relationship ADD COLUMN `id_server` varchar(100) NOT NULL DEFAULT '';
ALTER TABLE `tmodule_relationship` ADD COLUMN `type` ENUM('direct', 'failover') DEFAULT 'direct';
ALTER TABLE `tmodule_relationship` MODIFY COLUMN `id_server` varchar(100) NOT NULL DEFAULT '';

-- ---------------------------------------------------------------------
-- Table `tpolicy_module`
-- ---------------------------------------------------------------------
ALTER TABLE tpolicy_modules ADD COLUMN `ip_target`varchar(100) default '';
ALTER TABLE `tpolicy_modules` ADD COLUMN `cps` int NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent`
-- ---------------------------------------------------------------------
ALTER TABLE tmetaconsole_agent ADD COLUMN `alias_as_name` int(2) unsigned default '0';
ALTER TABLE tmetaconsole_agent ADD COLUMN `safe_mode_module` int(10) unsigned NOT NULL default '0';
ALTER TABLE `tmetaconsole_agent` ADD COLUMN `cps` int NOT NULL default 0;

UPDATE `tmetaconsole_agent` SET tmetaconsole_agent.alias = tmetaconsole_agent.nombre;
-- ---------------------------------------------------------------------
-- Table `twidget_dashboard`
-- ---------------------------------------------------------------------
ALTER TABLE `twidget_dashboard` MODIFY `options` LONGTEXT NOT NULL default "";
ALTER TABLE `twidget_dashboard` ADD COLUMN `position` TEXT NOT NULL default "";

-- ---------------------------------------------------------------------
-- Table `trecon_task`
-- ---------------------------------------------------------------------
ALTER TABLE trecon_task ADD `alias_as_name` int(2) unsigned default '0';
ALTER TABLE trecon_task ADD `snmp_enabled` int(2) unsigned default '0';
ALTER TABLE trecon_task ADD `vlan_enabled` int(2) unsigned default '0';
ALTER TABLE trecon_task ADD `wmi_enabled` tinyint(1) unsigned DEFAULT '0';
ALTER TABLE trecon_task ADD `rcmd_enabled` tinyint(1) unsigned DEFAULT '0';
ALTER TABLE trecon_task ADD `auth_strings` text;
ALTER TABLE trecon_task ADD `autoconfiguration_enabled` tinyint(1) unsigned default '0';
ALTER TABLE trecon_task ADD `summary` text;
ALTER TABLE `trecon_task` ADD COLUMN `type` int(11) NOT NULL DEFAULT '0',
	MODIFY COLUMN `alias_as_name` tinyint(2) NOT NULL DEFAULT '0',
	MODIFY COLUMN `snmp_enabled` tinyint(1) unsigned NULL DEFAULT '0',
	MODIFY COLUMN `vlan_enabled` tinyint(1) unsigned NULL DEFAULT '0',
	MODIFY COLUMN `wmi_enabled` tinyint(1) unsigned NULL DEFAULT '0',
	MODIFY COLUMN `auth_strings` text NULL,
	MODIFY COLUMN `autoconfiguration_enabled` tinyint(1) unsigned NULL DEFAULT '0',
	MODIFY COLUMN `summary` text NULL,
	MODIFY COLUMN `id_network_profile` text,
	CHANGE COLUMN `create_incident` `review_mode` TINYINT(1) UNSIGNED DEFAULT 1,
	ADD COLUMN `subnet_csv` TINYINT(1) UNSIGNED DEFAULT 0;

-- Old recon always report.
UPDATE `trecon_task` SET `review_mode` = 1;

-- ----------------------------------------------------------------------
-- Table `tdiscovery_tmp`
-- ----------------------------------------------------------------------
CREATE TABLE `tdiscovery_tmp_agents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_rt` int(10) unsigned NOT NULL,
  `label` varchar(600) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `data` MEDIUMTEXT,
  `review_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_rt` (`id_rt`),
  INDEX `label` (`label`),
  CONSTRAINT `tdta_trt` FOREIGN KEY (`id_rt`) REFERENCES `trecon_task` (`id_rt`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdiscovery_tmp_connections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_rt` int(10) unsigned NOT NULL,
  `dev_1` text,
  `dev_2` text,
  `if_1` text,
  `if_2` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `twidget` AND Table `twidget_dashboard`
-- ---------------------------------------------------------------------
UPDATE twidget_dashboard SET id_widget = (SELECT id FROM twidget WHERE unique_name = 'graph_module_histogram') WHERE id_widget = (SELECT id FROM twidget WHERE unique_name = 'graph_availability');
DELETE FROM twidget WHERE unique_name = 'graph_availability';
UPDATE `twidget` SET `unique_name`='example' WHERE `class_name` LIKE 'WelcomeWidget';

-- ---------------------------------------------------------------------
-- Table `tbackup` (Extension table. Modify only if exists)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS addcol;
delimiter //
CREATE PROCEDURE addcol()
BEGIN
SET @vv1 = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tbackup');
IF @vv1>0 THEN
	ALTER TABLE tbackup ADD COLUMN `filepath` varchar(512) NOT NULL DEFAULT "";
END IF;
SET @vv2 = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tuser_task_scheduled');
IF @vv2>0 THEN
	ALTER TABLE tuser_task_scheduled MODIFY args TEXT NOT NULL;
	ALTER TABLE tuser_task_scheduled ADD (id_grupo int(10) unsigned NOT NULL Default 0);
END IF;
END;
//
delimiter ;
CALL addcol();
DROP PROCEDURE addcol;

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
UPDATE `tconfig` SET `value` = 'login_logo_v7.png' where `token`='custom_logo_login';

-- ---------------------------------------------------------------------
-- Table `tcontainer`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcontainer` (
	`id_container` mediumint(4) unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL default '',
	`parent` mediumint(4) unsigned NOT NULL default 0,
	`disabled` tinyint(3) unsigned NOT NULL default 0,
	`id_group` mediumint(8) unsigned NULL default 0, 
	`description` TEXT NOT NULL,
 	PRIMARY KEY  (`id_container`),
 	KEY `parent_index` (`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tcontainer` SET `name` = 'Default graph container';

-- ----------------------------------------------------------------------
-- Table `treset_pass_history`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treset_pass_history` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_user` varchar(60) NOT NULL,
	`reset_moment` datetime NOT NULL,
	`success` tinyint(1) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tcontainer_item`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcontainer_item` (
	`id_ci` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_container` mediumint(4) unsigned NOT NULL default 0,
	`type` varchar(30) default 'simple_graph',
	`id_agent` int(10) unsigned NOT NULL default 0,
	`id_agent_module` bigint(14) unsigned NULL default NULL,
	`time_lapse` int(11) NOT NULL default 0,
	`id_graph` INTEGER UNSIGNED default 0,
	`only_average` tinyint (1) unsigned default 0 not null,
	`id_group` INT (10) unsigned NOT NULL DEFAULT 0,
	`id_module_group` INT (10) unsigned NOT NULL DEFAULT 0,
	`agent` varchar(100) NOT NULL default '',
	`module` varchar(100) NOT NULL default '',
	`id_tag` integer(10) unsigned NOT NULL DEFAULT 0,
	`type_graph` tinyint(1) unsigned NOT NULL DEFAULT 0,
	`fullscale` tinyint(1) UNSIGNED NOT NULL default 0,
	PRIMARY KEY(`id_ci`),
	FOREIGN KEY (`id_container`) REFERENCES tcontainer(`id_container`)
	ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE tusuario add default_event_filter int(10) unsigned NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `treset_pass` (
    `id` bigint(10) unsigned NOT NULL auto_increment,
    `id_user` varchar(100) NOT NULL default '',
    `cod_hash` varchar(100) NOT NULL default '',
    `reset_time` int(10) unsigned NOT NULL default 0,
    PRIMARY KEY (`id`) 
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE tgis_map_connection SET conection_data = '{"type":"OSM","url":"http://tile.openstreetmap.org/${z}/${x}/${y}.png"}' where id_tmap_connection = 1;

ALTER TABLE tpolicy_modules MODIFY post_process double(24,15) default 0;

-- ---------------------------------------------------------------------
-- Table `tserver_export`
-- ---------------------------------------------------------------------

ALTER TABLE tserver_export MODIFY `name` varchar(600) BINARY NOT NULL default '';

-- ---------------------------------------------------------------------
-- Table `tgraph_source` column 'id_server'
-- ---------------------------------------------------------------------

ALTER TABLE tgraph_source ADD COLUMN id_server int(11) UNSIGNED NOT NULL default 0;
ALTER TABLE tgraph_source ADD COLUMN `field_order` int(10) NOT NULL default 0;
ALTER TABLE `tgraph_source` MODIFY COLUMN `id_server` int(11) NOT NULL DEFAULT '0',
	MODIFY COLUMN `field_order` int(10) NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `tserver_export_data`
-- ---------------------------------------------------------------------

ALTER TABLE tserver_export_data MODIFY `module_name` varchar(600) NOT NULL default '';

-- ---------------------------------------------------------------------
-- Table `tserver`
-- ---------------------------------------------------------------------
ALTER TABLE tserver ADD COLUMN exec_proxy tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `tserver` ADD COLUMN `port` int(5) unsigned NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tevent_response`
-- ---------------------------------------------------------------------
ALTER TABLE tevent_response ADD COLUMN server_to_exec int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE tevent_response ADD COLUMN command_timeout int(5) unsigned NOT NULL DEFAULT 90;
ALTER TABLE tevent_response ADD COLUMN display_command tinyint(1) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tmodule`
-- ---------------------------------------------------------------------

INSERT INTO tmodule VALUES (8, 'Wux module');
INSERT INTO tmodule VALUES (9, 'Wizard module');

-- ---------------------------------------------------------------------
-- Table `ttipo_modulo`
-- ---------------------------------------------------------------------

INSERT INTO `ttipo_modulo` VALUES
(25,'web_analysis', 8, 'Web analysis data', 'module-wux.png'),
(34,'remote_cmd', 10, 'Remote execution, numeric data', 'mod_remote_cmd.png'),
(35,'remote_cmd_proc', 10, 'Remote execution, boolean data', 'mod_remote_cmd_proc.png'),
(36,'remote_cmd_string', 10, 'Remote execution, alphanumeric data', 'mod_remote_cmd_string.png'),
(37,'remote_cmd_inc', 10, 'Remote execution, incremental data', 'mod_remote_cmd_inc.png'),
(38,'web_server_status_code_string',9,'Remote HTTP module to check server status code','mod_web_data.png');

-- ---------------------------------------------------------------------
-- Table `tdashboard`
-- ---------------------------------------------------------------------
ALTER TABLE `tdashboard` ADD COLUMN `cells_slideshow` TINYINT(1) NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tsnmp_filter`
-- ---------------------------------------------------------------------
ALTER TABLE tsnmp_filter ADD unified_filters_id int(10) NOT NULL DEFAULT 0;

SELECT max(unified_filters_id) INTO @max FROM tsnmp_filter;
UPDATE tsnmp_filter tsf,(SELECT @max:= @max) m SET tsf.unified_filters_id = @max:= @max + 1 where tsf.unified_filters_id=0;

-- ---------------------------------------------------------------------
-- Table `tcluster`
-- ---------------------------------------------------------------------

create table IF NOT EXISTS `tcluster`(
    `id` int unsigned not null auto_increment,
    `name` tinytext not null default '',
    `cluster_type` enum('AA','AP') not null default 'AA',
		`description` text not null default '',
		`group` int(10) unsigned NOT NULL default '0',
		`id_agent` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id`)
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tcluster_item`
-- ---------------------------------------------------------------------

create table IF NOT EXISTS `tcluster_item`(
		`id` int unsigned not null auto_increment,
    `name` tinytext not null default '',
    `item_type` enum('AA','AP')  not null default 'AA',
		`critical_limit` int unsigned NOT NULL default '0',
		`warning_limit` int unsigned NOT NULL default '0',
		`is_critical` tinyint(2) unsigned NOT NULL default '0',
		`id_cluster` int unsigned,
		PRIMARY KEY (`id`),
		FOREIGN KEY (`id_cluster`) REFERENCES tcluster(`id`)
			ON DELETE SET NULL ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tcluster_agent`
-- ---------------------------------------------------------------------

create table IF NOT EXISTS `tcluster_agent`(
    `id_cluster` int unsigned not null,
    `id_agent` int(10) unsigned not null,
		PRIMARY KEY (`id_cluster`,`id_agent`),
		FOREIGN KEY (`id_cluster`) REFERENCES tcluster(`id`)
			ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tprovisioning`
-- ---------------------------------------------------------------------
create table IF NOT EXISTS `tprovisioning`(
    `id` int unsigned NOT NULL auto_increment,
    `name` varchar(100) NOT NULL,
	`description` TEXT default '',
	`order` int(11) NOT NULL default 0,
	`config` TEXT default '',
		PRIMARY KEY (`id`)
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tprovisioning_rules`
-- ---------------------------------------------------------------------
create table IF NOT EXISTS `tprovisioning_rules`(
    `id` int unsigned NOT NULL auto_increment,
    `id_provisioning` int unsigned NOT NULL,
	`order` int(11) NOT NULL default 0,
	`operator` enum('AND','OR') default 'OR',
	`type` enum('alias','ip-range') default 'alias',
	`value` varchar(100) NOT NULL default '',
		PRIMARY KEY (`id`),
		FOREIGN KEY (`id_provisioning`) REFERENCES tprovisioning(`id`)
			ON DELETE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tmigration_queue`
-- ---------------------------------------------------------------------

create table IF NOT EXISTS `tmigration_queue`(
    `id` int unsigned not null auto_increment,
    `id_source_agent` int unsigned not null,
    `id_target_agent` int unsigned not null,
    `id_source_node` int unsigned not null,
    `id_target_node` int unsigned not null,
    `priority` int unsigned default 0,
    `step` int default 0,
    `running` tinyint(2) default 0,
    `active_db_only` tinyint(2) default 0,
    PRIMARY KEY(`id`)
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tmigration_module_queue`
-- ---------------------------------------------------------------------

create table IF NOT EXISTS `tmigration_module_queue`(
    `id` int unsigned not null auto_increment,
    `id_migration` int unsigned not null,
    `id_source_agentmodule` int unsigned not null,
    `id_target_agentmodule` int unsigned not null,
    `last_replication_timestamp` bigint(20) NOT NULL default 0,
    PRIMARY KEY(`id`),
    FOREIGN KEY(`id_migration`) REFERENCES tmigration_queue(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tagent_secondary_group`
-- ---------------------------------------------------------------------

create table IF NOT EXISTS `tagent_secondary_group`(
    `id` int unsigned not null auto_increment,
    `id_agent` int(10) unsigned NOT NULL,
    `id_group` mediumint(4) unsigned NOT NULL,
    PRIMARY KEY(`id`),
    FOREIGN KEY(`id_agent`) REFERENCES tagente(`id_agente`)
        ON DELETE CASCADE,
	FOREIGN KEY(`id_group`) REFERENCES tgrupo(`id_grupo`)
        ON DELETE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent_secondary_group`
-- ---------------------------------------------------------------------
create table IF NOT EXISTS `tmetaconsole_agent_secondary_group`(
    `id` int unsigned not null auto_increment,
    `id_agent` int(10) unsigned NOT NULL,
    `id_tagente` int(10) unsigned NOT NULL,
    `id_tmetaconsole_setup` int(10) NOT NULL,
    `id_group` mediumint(4) unsigned NOT NULL,
    PRIMARY KEY(`id`),
    FOREIGN KEY(`id_agent`) REFERENCES tmetaconsole_agent(`id_agente`)
        ON DELETE CASCADE,
	FOREIGN KEY(`id_group`) REFERENCES tgrupo(`id_grupo`)
        ON DELETE CASCADE,
	FOREIGN KEY (`id_tmetaconsole_setup`) REFERENCES tmetaconsole_setup(`id`)
		ON DELETE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE tagente ADD COLUMN `update_secondary_groups` tinyint(1) NOT NULL default '0';
ALTER TABLE tmetaconsole_agent ADD COLUMN `update_secondary_groups` tinyint(1) NOT NULL default '0';
ALTER TABLE tusuario_perfil ADD COLUMN `no_hierarchy` tinyint(1) NOT NULL default '0';
ALTER TABLE `tmetaconsole_agent_secondary_group` ADD INDEX `id_tagente` (`id_tagente`);

-- ---------------------------------------------------------------------
-- Table `tautoconfig`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tautoconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `description` text,
  `disabled` TINYINT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tautoconfig_rules`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tautoconfig_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_autoconfig` int(10) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `operator` enum('AND','OR') DEFAULT 'OR',
  `type` enum('alias','ip-range','group','os','custom-field','script','server-name') DEFAULT 'alias',
  `value` text,
  `custom` text,
  PRIMARY KEY (`id`),
  KEY `id_autoconfig` (`id_autoconfig`),
  CONSTRAINT `tautoconfig_rules_ibfk_1` FOREIGN KEY (`id_autoconfig`) REFERENCES `tautoconfig` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tautoconfig_actions`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tautoconfig_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_autoconfig` int(10) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `action_type` enum('set-group', 'set-secondary-group', 'apply-policy', 'launch-script', 'launch-event', 'launch-alert-action', 'raw-config') DEFAULT 'launch-event',
  `value` text,
  `custom` text,
  PRIMARY KEY (`id`),
  KEY `id_autoconfig` (`id_autoconfig`),
  CONSTRAINT `tautoconfig_action_ibfk_1` FOREIGN KEY (`id_autoconfig`) REFERENCES `tautoconfig` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tlayout_template`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout_template` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(600) NOT NULL,
	`id_group` INTEGER UNSIGNED NOT NULL,
	`background` varchar(200)  NOT NULL,
	`height` INTEGER UNSIGNED NOT NULL default 0,
	`width` INTEGER UNSIGNED NOT NULL default 0,
	`background_color` varchar(50) NOT NULL default '#FFF',
	`is_favourite` INTEGER UNSIGNED NOT NULL default 0,
	PRIMARY KEY(`id`)
)  ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE tlayout_template MODIFY `name` varchar(600) NOT NULL;
ALTER TABLE `tlayout` ADD COLUMN `auto_adjust` INTEGER UNSIGNED NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tlayout_template_data`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout_template_data` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_layout_template` INTEGER UNSIGNED NOT NULL,
	`pos_x` INTEGER UNSIGNED NOT NULL default 0,
	`pos_y` INTEGER UNSIGNED NOT NULL default 0,
	`height` INTEGER UNSIGNED NOT NULL default 0,
	`width` INTEGER UNSIGNED NOT NULL default 0,
	`label` TEXT,
	`image` varchar(200) DEFAULT "",
	`type` tinyint(1) UNSIGNED NOT NULL default 0,
	`period` INTEGER UNSIGNED NOT NULL default 3600,
	`module_name` text NOT NULL,
	`agent_name` varchar(600) BINARY NOT NULL default '',
	`id_layout_linked` INTEGER unsigned NOT NULL default '0',
	`parent_item` INTEGER UNSIGNED NOT NULL default 0,
	`enable_link` tinyint(1) UNSIGNED NOT NULL default 1,
	`id_metaconsole` int(10) NOT NULL default 0,
	`id_group` INTEGER UNSIGNED NOT NULL default 0,
	`id_custom_graph` INTEGER UNSIGNED NOT NULL default 0,
	`border_width` INTEGER UNSIGNED NOT NULL default 0,
	`type_graph` varchar(50) NOT NULL default 'area',
	`label_position` varchar(50) NOT NULL default 'down',
	`border_color` varchar(200) DEFAULT "",
	`fill_color` varchar(200) DEFAULT "",
	`show_statistics` tinyint(2) NOT NULL default '0',
	`id_layout_linked_weight` int(10) NOT NULL default '0',
	`element_group` int(10) NOT NULL default '0',
	`show_on_top` tinyint(1) NOT NULL default '0',
	`clock_animation` varchar(60) NOT NULL default "analogic_1",
	`time_format` varchar(60) NOT NULL default "time",
	`timezone` varchar(60) NOT NULL default "Europe/Madrid",
	`show_last_value` tinyint(1) UNSIGNED NULL default '0',
	`linked_layout_status_type` ENUM ('default', 'weight', 'service') DEFAULT 'default',
	`linked_layout_status_as_service_warning` FLOAT(20, 3) NOT NULL default 0,
	`linked_layout_status_as_service_critical` FLOAT(20, 3) NOT NULL default 0,
	`linked_layout_node_id` INT(10) NOT NULL default 0,
	`cache_expiration` INTEGER UNSIGNED NOT NULL default 0,
	`title` TEXT default '',
	PRIMARY KEY(`id`),
	FOREIGN KEY (`id_layout_template`) REFERENCES tlayout_template(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tlayout_template_data` MODIFY COLUMN `linked_layout_node_id` int(10) NOT NULL DEFAULT '0',
	MODIFY COLUMN `linked_layout_status_type` enum('default','weight','service') NULL DEFAULT 'default',
	MODIFY COLUMN `linked_layout_status_as_service_warning` float(20,3) NOT NULL DEFAULT '0.000',
	MODIFY COLUMN `linked_layout_status_as_service_critical` float(20,3) NOT NULL DEFAULT '0.000';

-- ---------------------------------------------------------------------
-- Table `tlog_graph_models`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlog_graph_models` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`title` TEXT NOT NULL,
	`regexp` TEXT NOT NULL,
	`fields` TEXT NOT NULL,
	`average` tinyint(1) NOT NULL default '0',
	PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

INSERT INTO tlog_graph_models VALUES (1, 'Apache&#x20;log&#x20;model',
	'^.*?&#92;s+.*&quot;.*?&#92;s&#40;&#92;/.*?&#41;&#92;?.*1.1&quot;&#92;s+&#40;.*?&#41;&#92;s+&#40;.*?&#41;&#92;s+',
	'pagina,&#x20;html_err_code,&#x20;_tiempo_', 1);

INSERT INTO tlog_graph_models VALUES (2, 'Apache&#x20;accesses&#x20;per&#x20;client&#x20;and&#x20;status',
'&#40;.*?&#41;&#92;&#x20;-.*1.1&quot;&#92;&#x20;&#40;&#92;d+&#41;&#92;&#x20;&#92;d+',
'host,status', 1);

INSERT INTO tlog_graph_models VALUES (3, 'Apache&#x20;time&#x20;per&#x20;requester&#x20;and&#x20;html&#x20;code',
'&#40;.*?&#41;&#92;&#x20;-.*1.1&quot;&#92;&#x20;&#40;&#92;d+&#41;&#92;&#x20;&#40;&#92;d+&#41;',
'origin,respose,_time_', 1);

INSERT INTO tlog_graph_models VALUES (4, 'Count&#x20;output',
'.*',
'Coincidences', 0);

INSERT INTO tlog_graph_models VALUES (5, 'Events&#x20;replicated&#x20;to&#x20;metaconsole',
'.*&#x20;&#40;.*?&#41;&#x20;.*&#x20;&#40;&#92;d+&#41;&#x20;events&#x20;replicated&#x20;to&#x20;metaconsole',
'server,_events_', 0);

INSERT INTO tlog_graph_models VALUES (6, 'Pages&#x20;with&#x20;warnings',
'PHP&#x20;Warning:.*in&#x20;&#40;.*?&#41;&#x20;on',
'page', 0);

INSERT INTO tlog_graph_models VALUES (7, 'Users&#x20;login',
'Starting&#x20;Session&#x20;&#92;d+&#92;&#x20;of&#x20;user&#x20;&#40;.*&#41;',
'user', 0);

-- -----------------------------------------------------
-- Add column in table `treport`
-- -----------------------------------------------------
ALTER TABLE `treport` ADD COLUMN `hidden` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `treport` ADD COLUMN `orientation` varchar(25) NOT NULL default 'vertical';
ALTER TABLE `treport` MODIFY COLUMN `hidden` tinyint(1) NULL DEFAULT '0' AFTER `non_interactive`;
ALTER TABLE `treport` ADD COLUMN `cover_page_render` tinyint(1) NOT NULL DEFAULT 1;
ALTER TABLE `treport` ADD COLUMN `index_render` tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE `trecon_task` ADD COLUMN `snmp_version` varchar(5) NOT NULL default '1';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_auth_user` varchar(255) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_auth_pass` varchar(255) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_auth_method` varchar(25) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_privacy_method` varchar(25) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_privacy_pass` varchar(255) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_security_level` varchar(25) NOT NULL default '';
ALTER TABLE trecon_task add column `auto_monitor` TINYINT(1) UNSIGNED DEFAULT 1 AFTER `auth_strings`;
UPDATE `trecon_task` SET `auto_monitor` = 0;

-- ---------------------------------------------------------------------
-- Table `tagent_custom_fields_filter`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagent_custom_fields_filter` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(600) NOT NULL,
	`id_group` int(10) unsigned default '0',
	`id_custom_field` varchar(600) default '',
	`id_custom_fields_data` varchar(600) default '',
	`id_status` varchar(600) default '',
	`module_search` varchar(600) default '',
	`module_status` varchar(600) default '',
	`recursion` int(1) unsigned default '0',
	`group_search` int(10) unsigned default '0',
	PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tevento`
-- ---------------------------------------------------------------------
ALTER TABLE `tevento` ADD COLUMN `data` double(50,5) default NULL;

ALTER TABLE `tevento` ADD COLUMN `module_status` int(4) NOT NULL default '0';

ALTER TABLE `tevento` MODIFY `data` TINYTEXT default NULL;

-- ---------------------------------------------------------------------
-- Table `tevent_extended`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tevent_extended` (
	`id` serial PRIMARY KEY,
	`id_evento` bigint(20) unsigned NOT NULL,
	`external_id` bigint(20) unsigned,
	`utimestamp` bigint(20) NOT NULL default '0',
	`description` text,
	FOREIGN KEY `tevent_ext_fk`(`id_evento`) REFERENCES `tevento`(`id_evento`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tgis_map_layer_groups`
-- -----------------------------------------------------
CREATE TABLE `tgis_map_layer_groups` (
  `layer_id` int(11) NOT NULL,
  `group_id` mediumint(4) unsigned NOT NULL,
  `agent_id` int(10) unsigned NOT NULL COMMENT 'Used to link the position to the group',
  PRIMARY KEY (`layer_id`,`group_id`),
  KEY `group_id` (`group_id`),
  KEY `agent_id` (`agent_id`),
  CONSTRAINT `tgis_map_layer_groups_ibfk_1` FOREIGN KEY (`layer_id`) REFERENCES `tgis_map_layer` (`id_tmap_layer`) ON DELETE CASCADE,
  CONSTRAINT `tgis_map_layer_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `tgrupo` (`id_grupo`) ON DELETE CASCADE,
  CONSTRAINT `tgis_map_layer_groups_ibfk_3` FOREIGN KEY (`agent_id`) REFERENCES `tagente` (`id_agente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tnetwork_matrix`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_matrix` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`source` varchar(60) default '',
	`destination` varchar(60) default '',
	`utimestamp` bigint(20) default 0,
	`bytes` int(18) unsigned default 0,
	`pkts` int(18) unsigned default 0,
	PRIMARY KEY (`id`),
	UNIQUE (`source`, `destination`, `utimestamp`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ;

-- ---------------------------------------------------------------------
-- Table `user_task`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tuser_task` (
	`id` int(20) unsigned NOT NULL auto_increment,
	`function_name` varchar(80) NOT NULL default '',
	`parameters` text NOT NULL default '',
	`name` varchar(60) NOT NULL default '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `user_task_scheduled`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tuser_task_scheduled` (
	`id` int(20) unsigned NOT NULL auto_increment,
	`id_usuario` varchar(60) NOT NULL default '0',
	`id_user_task` int(20) unsigned NOT NULL default '0',
	`args` TEXT NOT NULL,
	`scheduled` enum('no','hourly','daily','weekly','monthly','yearly','custom') default 'no',
	`last_run` int(20) unsigned default '0',
	`custom_data` int(10) NULL default '0',
	`flag_delete` tinyint(1) UNSIGNED NOT NULL default 0,
	`id_grupo` int(10) unsigned NOT NULL default 0,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tnotification_source`
-- -----------------------------------------------------
CREATE TABLE `tnotification_source` (
    `id` serial,
    `description` VARCHAR(255) DEFAULT NULL,
    `icon` text,
    `max_postpone_time` int(11) DEFAULT NULL,
    `enabled` int(1) DEFAULT NULL,
    `user_editable` int(1) DEFAULT NULL,
    `also_mail` int(1) DEFAULT NULL,
	`subtype_blacklist` TEXT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tnotification_source`
--
INSERT INTO `tnotification_source`(`description`, `icon`, `max_postpone_time`, `enabled`, `user_editable`, `also_mail`) VALUES
  ("System&#x20;status", "icono_info_mr.png", 86400, 1, 1, 0),
  ("Message", "icono_info_mr.png", 86400, 1, 1, 0),
  ("Pending&#x20;task", "icono_info_mr.png", 86400, 1, 1, 0),
  ("Advertisement", "icono_info_mr.png", 86400, 1, 1, 0),
  ("Official&#x20;communication", "icono_logo_pandora.png", 86400, 1, 1, 0),
  ("Sugerence", "icono_info_mr.png", 86400, 1, 1, 0);

-- -----------------------------------------------------
-- Table `tmensajes`
-- -----------------------------------------------------
ALTER TABLE `tmensajes` ADD COLUMN `url` TEXT;
ALTER TABLE `tmensajes` ADD COLUMN `response_mode` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `tmensajes` ADD COLUMN `citicity` INT(10) UNSIGNED DEFAULT '0';
ALTER TABLE `tmensajes` ADD COLUMN `id_source` BIGINT(20) UNSIGNED NOT NULL;
ALTER TABLE `tmensajes` ADD COLUMN `subtype` VARCHAR(255) DEFAULT '';
ALTER TABLE `tmensajes` ADD COLUMN `hidden_sent` TINYINT(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tmensajes` ADD CONSTRAINT `tsource_fk` FOREIGN KEY (`id_source`) REFERENCES `tnotification_source` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `tmensajes` DROP COLUMN `id_usuario_destino`,
	ADD UNIQUE INDEX `id_mensaje` (`id_mensaje`);

-- ----------------------------------------------------------------------
-- Table `tnotification_user`
-- ----------------------------------------------------------------------
CREATE TABLE `tnotification_user` (
    `id_mensaje` INT(10) UNSIGNED NOT NULL,
    `id_user` VARCHAR(60) NOT NULL,
    `utimestamp_read` BIGINT(20),
    `utimestamp_erased` BIGINT(20),
    `postpone` INT,
    PRIMARY KEY (`id_mensaje`,`id_user`),
    FOREIGN KEY (`id_mensaje`) REFERENCES `tmensajes`(`id_mensaje`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_user`) REFERENCES `tusuario`(`id_user`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnotification_group`
-- ----------------------------------------------------------------------
CREATE TABLE `tnotification_group` (
	`id_mensaje` INT(10) UNSIGNED NOT NULL,
	`id_group` mediumint(4) UNSIGNED NOT NULL,
	PRIMARY KEY (`id_mensaje`,`id_group`),
	FOREIGN KEY (`id_mensaje`) REFERENCES `tmensajes`(`id_mensaje`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnotification_source_user`
-- ----------------------------------------------------------------------
CREATE TABLE `tnotification_source_user` (
    `id_source` BIGINT(20) UNSIGNED NOT NULL,
    `id_user` VARCHAR(60),
    `enabled` INT(1) DEFAULT NULL,
    `also_mail` INT(1) DEFAULT NULL,
    PRIMARY KEY (`id_source`,`id_user`),
    FOREIGN KEY (`id_source`) REFERENCES `tnotification_source`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_user`) REFERENCES `tusuario`(`id_user`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnotification_source_group`
-- ----------------------------------------------------------------------
CREATE TABLE `tnotification_source_group` (
    `id_source` BIGINT(20) UNSIGNED NOT NULL,
    `id_group` mediumint(4) unsigned NOT NULL,
    PRIMARY KEY (`id_source`,`id_group`),
	INDEX (`id_group`),
    FOREIGN KEY (`id_source`) REFERENCES `tnotification_source`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnotification_source_user`
-- ----------------------------------------------------------------------
CREATE TABLE `tnotification_source_group_user` (
    `id_source` BIGINT(20) UNSIGNED NOT NULL,
    `id_group` mediumint(4) unsigned NOT NULL,
    `id_user` VARCHAR(60),
    `enabled` INT(1) DEFAULT NULL,
    `also_mail` INT(1) DEFAULT NULL,
    PRIMARY KEY (`id_source`,`id_user`),
    FOREIGN KEY (`id_source`) REFERENCES `tnotification_source`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_user`) REFERENCES `tusuario`(`id_user`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_group`) REFERENCES `tnotification_source_group`(`id_group`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Add alert command 'Generate notification'
-- ----------------------------------------------------------------------
INSERT INTO `talert_commands` (`name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES ('Generate&#x20;Notification','Internal&#x20;type','This&#x20;command&#x20;allows&#x20;you&#x20;to&#x20;send&#x20;an&#x20;internal&#x20;notification&#x20;to&#x20;any&#x20;user&#x20;or&#x20;group.',1,'[\"Destination&#x20;user\",\"Destination&#x20;group\",\"Title\",\"Message\",\"Link\",\"Criticity\",\"\",\"\",\"\",\"\",\"\"]','[\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]');
UPDATE `talert_commands` SET `fields_descriptions` = '[\"Event&#x20;name\",\"Event&#x20;type\",\"Source\",\"Agent&#x20;name&#x20;or&#x20;_agent_\",\"Event&#x20;severity\",\"ID&#x20;extra\",\"Tags&#x20;separated&#x20;by&#x20;commas\",\"Comments\",\"\",\"\"]' WHERE `name` = "Monitoring&#x20;Event";

-- ----------------------------------------------------------------------
-- Update message references and pre-configure notifications
-- ----------------------------------------------------------------------
INSERT INTO `tnotification_source_user` (`id_source`, `id_user`, `enabled`, `also_mail`) VALUES ((SELECT `id` FROM `tnotification_source` WHERE `description`="System&#x20;status"), "admin", 1, 0);
INSERT INTO `tnotification_source_group` SELECT `id`,0 FROM `tnotification_source` WHERE `description`="Message";
INSERT INTO `tnotification_user` (`id_mensaje`, `id_user`) SELECT `id_mensaje`, `id_usuario_destino` FROM `tmensajes` WHERE `id_usuario_destino` != '';
INSERT INTO `tnotification_source_user` (`id_source`, `id_user`, `enabled`, `also_mail`) VALUES ((SELECT `id` FROM `tnotification_source` WHERE `description`="Official&#x20;communication"), "admin", 1, 0);
UPDATE `tnotification_source` SET `enabled`=1 WHERE `description` = 'System&#x20;status' OR `description` = 'Official&#x20;communication';

-- ----------------------------------------------------------------------
-- Add custom internal recon scripts
-- ----------------------------------------------------------------------
INSERT INTO `trecon_script` (`name`,`description`,`script`,`macros`) VALUES ('Discovery.Application.VMware', 'Discovery&#x20;Application&#x20;script&#x20;to&#x20;monitor&#x20;VMware&#x20;technologies&#x20;&#40;ESXi,&#x20;VCenter,&#x20;VSphere&#41;', '/usr/share/pandora_server/util/recon_scripts/vmware-plugin.pl', '{"1":{"macro":"_field1_","desc":"Configuration&#x20;file","help":"","value":"","hide":""}}');
INSERT INTO `trecon_script` (`name`,`description`,`script`,`macros`) VALUES ('Discovery.Cloud', 'Discovery&#x20;Cloud&#x20;script&#x20;to&#x20;monitor&#x20;Cloud&#x20;technologies&#x20;&#40;AWS.EC2,&#x20;AWS.S3,&#x20;AWS.RDS,&#x20RDS,&#x20AWS.EKS&#41;', '/usr/share/pandora_server/util/recon_scripts/pcm_client.pl', '{"1":{"macro":"_field1_","desc":"Configuration&#x20;file","help":"","value":"","hide":""}}');
-- ----------------------------------------------------------------------
-- Add column in table `tagent_custom_fields`
-- ----------------------------------------------------------------------
ALTER TABLE tagent_custom_fields ADD COLUMN `combo_values` TEXT NOT NULL DEFAULT '';

-- ----------------------------------------------------------------------
-- Add column in table `tnetflow_filter`
-- ----------------------------------------------------------------------
ALTER TABLE `tnetflow_filter` DROP COLUMN `output`;
ALTER TABLE `tnetflow_filter` MODIFY COLUMN `router_ip` text NOT NULL;

-- ----------------------------------------------------------------------
-- Update table `tuser_task`
-- ----------------------------------------------------------------------
UPDATE tuser_task set parameters = 'a:5:{i:0;a:6:{s:11:\"description\";s:28:\"Report pending to be created\";s:5:\"table\";s:7:\"treport\";s:8:\"field_id\";s:9:\"id_report\";s:10:\"field_name\";s:4:\"name\";s:4:\"type\";s:3:\"int\";s:9:\"acl_group\";s:8:\"id_group\";}i:1;a:2:{s:11:\"description\";s:46:\"Send to email addresses (separated by a comma)\";s:4:\"type\";s:4:\"text\";}i:2;a:2:{s:11:\"description\";s:7:\"Subject\";s:8:\"optional\";i:1;}i:3;a:3:{s:11:\"description\";s:7:\"Message\";s:4:\"type\";s:4:\"text\";s:8:\"optional\";i:1;}i:4;a:2:{s:11:\"description\";s:11:\"Report Type\";s:4:\"type\";s:11:\"report_type\";}}' where function_name = "cron_task_generate_report";
INSERT IGNORE INTO tuser_task VALUES (8, 'cron_task_generate_csv_log', 'a:1:{i:0;a:2:{s:11:"description";s:14:"Send to e-mail";s:4:"type";s:4:"text";}}', 'Send csv log');
UPDATE `tuser_task` SET `parameters`='a:4:{i:0;a:6:{s:11:"description";s:28:"Report pending to be created";s:5:"table";s:7:"treport";s:8:"field_id";s:9:"id_report";s:10:"field_name";s:4:"name";s:4:"type";s:3:"int";s:9:"acl_group";s:8:"id_group";}i:1;a:2:{s:11:"description";s:426:"Save to disk in path<a href="javascript:" class="tip" style="" ><img src="http://172.16.0.2/pandora_console/images/tip_help.png" data-title="The Apache user should have read-write access on this folder. E.g. /var/www/html/pandora_console/attachment" data-use_title_for_force_title="1" class="forced_title" alt="The Apache user should have read-write access on this folder. E.g. /var/www/html/pandora_console/attachment" /></a>";s:4:"type";s:6:"string";}i:2;a:2:{s:11:"description";s:16:"File nane prefix";s:4:"type";s:6:"string";}i:3;a:2:{s:11:"description";s:11:"Report Type";s:4:"type";s:11:"report_type";}}' WHERE `id`=3;
UPDATE `tuser_task`
SET parameters='a:7:{i:0;a:7:{s:11:"description";s:30:"Template pending to be created";s:5:"table";s:16:"treport_template";s:8:"field_id";s:9:"id_report";s:10:"field_name";s:4:"name";s:8:"required";b:1;s:4:"type";s:3:"int";s:9:"acl_group";s:8:"id_group";}i:1;a:7:{s:11:"description";s:6:"Agents";s:5:"table";s:7:"tagente";s:8:"field_id";s:9:"id_agente";s:10:"field_name";s:6:"nombre";s:8:"multiple";b:1;s:4:"type";s:3:"int";s:9:"acl_group";s:8:"id_grupo";}i:2;a:2:{s:11:"description";s:16:"Report per agent";s:10:"select_two";b:1;}i:3;a:2:{s:11:"description";s:11:"Report name";s:4:"type";s:6:"string";}i:4;a:2:{s:11:"description";s:47:"Send to e-mail addresses (separated by a comma)";s:4:"type";s:4:"text";}i:5;a:2:{s:11:"description";s:7:"Subject";s:8:"optional";i:1;}i:6;a:3:{s:11:"description";s:7:"Message";s:4:"type";s:4:"text";s:8:"optional";i:1;}}i:7;a:2:{s:11:"description";s:11:"Report Type";s:4:"type";s:11:"report_type";}}' WHERE id=2;
DELETE FROM `tuser_task` WHERE id = 6;

-- Migrate old tasks
UPDATE `tuser_task_scheduled` SET 
    `args` = REPLACE (`args`, 'a:3', 'a:5'),
    `args`= REPLACE(`args`, 's:15:"first_execution"', 'i:2;s:0:"";i:3;s:3:"PDF";s:15:"first_execution"')
    WHERE `id_user_task` = 3;

UPDATE `tuser_task_scheduled` SET 
    `id_user_task` = 3, 
    `args` = REPLACE (`args`, 'a:3', 'a:5'),
    `args`= REPLACE(`args`, 's:15:"first_execution"', 'i:2;s:0:"";i:3;s:3:"XML";s:15:"first_execution"')
    WHERE `id_user_task` = 6;

	UPDATE `tuser_task_scheduled` SET 
    `args` = REPLACE (`args`, 'a:8', 'a:9'),
    `args`= REPLACE(`args`, 's:15:"first_execution"', 'i:2;s:0:"";i:7;s:3:"PDF";s:15:"first_execution"')
    WHERE `id_user_task` = 2;


-- ----------------------------------------------------------------------
-- ADD message in table 'tnews'
-- ----------------------------------------------------------------------

INSERT INTO `tnews` (`id_news`, `author`, `subject`, `text`, `timestamp`) VALUES (NULL,'admin','Welcome&#x20;to&#x20;Pandora&#x20;FMS&#x20;Console', '&amp;lt;p&#x20;style=&quot;text-align:&#x20;center;&#x20;font-size:&#x20;13px;&quot;&amp;gt;Hello,&#x20;congratulations,&#x20;if&#x20;you&apos;ve&#x20;arrived&#x20;here&#x20;you&#x20;already&#x20;have&#x20;an&#x20;operational&#x20;monitoring&#x20;console.&#x20;Remember&#x20;that&#x20;our&#x20;forums&#x20;and&#x20;online&#x20;documentation&#x20;are&#x20;available&#x20;24x7&#x20;to&#x20;get&#x20;you&#x20;out&#x20;of&#x20;any&#x20;trouble.&#x20;You&#x20;can&#x20;replace&#x20;this&#x20;message&#x20;with&#x20;a&#x20;personalized&#x20;one&#x20;at&#x20;Admin&#x20;tools&#x20;-&amp;amp;gt;&#x20;Site&#x20;news.&amp;lt;/p&amp;gt;&#x20;',NOW());

-- ----------------------------------------------------------------------
-- Alter table `talert_templates`
-- ----------------------------------------------------------------------

ALTER TABLE `talert_templates` ADD COLUMN `previous_name` text;

 ALTER TABLE `talert_templates` MODIFY COLUMN `type` ENUM('regex','max_min','max','min','equal','not_equal','warning','critical','onchange','unknown','always','not_normal');

ALTER TABLE `talert_templates` MODIFY COLUMN `field11` text NOT NULL,
	MODIFY COLUMN `field12` text NOT NULL,
	MODIFY COLUMN `field13` text NOT NULL,
	MODIFY COLUMN `field14` text NOT NULL,
	MODIFY COLUMN `field15` text NOT NULL,
	MODIFY COLUMN `field11_recovery` text NOT NULL,
	MODIFY COLUMN `field12_recovery` text NOT NULL,
	MODIFY COLUMN `field13_recovery` text NOT NULL,
	MODIFY COLUMN `field14_recovery` text NOT NULL,
	MODIFY COLUMN `field15_recovery` text NOT NULL;

-- ---------------------------------------------------------------------
-- Table `tvisual_console_items_cache`
-- ---------------------------------------------------------------------
CREATE TABLE `tvisual_console_elements_cache` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `vc_id` INTEGER UNSIGNED NOT NULL,
    `vc_item_id` INTEGER UNSIGNED NOT NULL,
    `user_id` VARCHAR(60) DEFAULT NULL,
    `data` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`expiration` INTEGER UNSIGNED NOT NULL COMMENT 'Seconds to expire',
    PRIMARY KEY(`id`),
    FOREIGN KEY(`vc_id`) REFERENCES `tlayout`(`id`)
        ON DELETE CASCADE,
    FOREIGN KEY(`vc_item_id`) REFERENCES `tlayout_data`(`id`)
        ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `tusuario`(`id_user`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tcredential_store`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tcredential_store` (
	`identifier` varchar(100) NOT NULL,
	`id_group` mediumint(4) unsigned NOT NULL DEFAULT 0,
	`product` enum('CUSTOM', 'AWS', 'AZURE', 'GOOGLE', 'SAP') default 'CUSTOM',
	`username` text,
	`password` text,
	`extra_1` text,
	`extra_2` text,
	PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `treport_content_sla_combined`
-- ---------------------------------------------------------------------
ALTER TABLE `treport_content_sla_combined` ADD `id_agent_module_failover` int(10) unsigned NOT NULL;

-- ---------------------------------------------------------------------
-- Table `tagent_repository`
-- ---------------------------------------------------------------------
CREATE TABLE `tagent_repository` (
  `id` SERIAL,
  `id_os` INT(10) UNSIGNED DEFAULT 0,
  `arch` ENUM('x64', 'x86') DEFAULT 'x64',
  `version` VARCHAR(10) DEFAULT '',
  `path` text,
  `deployment_timeout` INT UNSIGNED DEFAULT 600,
  `uploaded_by` VARCHAR(100) DEFAULT '',
  `uploaded` bigint(20) NOT NULL DEFAULT 0 COMMENT "When it was uploaded",
  `last_err` text,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_os`) REFERENCES `tconfig_os`(`id_os`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `treport_content_item`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treport_content_item` (
	`id` INTEGER UNSIGNED NOT NULL auto_increment,
	`id_report_content` INTEGER UNSIGNED NOT NULL,
	`id_agent_module` int(10) unsigned NOT NULL,
	`id_agent_module_failover` int(10) unsigned NOT NULL DEFAULT 0,
	`server_name` text,
	`operation` text,
	PRIMARY KEY(`id`),
	FOREIGN KEY (`id_report_content`) REFERENCES treport_content(`id_rc`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tdeployment_hosts`
-- ----------------------------------------------------------------------
CREATE TABLE `tdeployment_hosts` (
  `id` SERIAL,
  `id_cs` VARCHAR(100),
  `ip` VARCHAR(100) NOT NULL UNIQUE,
  `id_os` INT(10) UNSIGNED DEFAULT 0,
  `os_version` VARCHAR(100) DEFAULT '' COMMENT "OS version in STR format",
  `arch` ENUM('x64', 'x86') DEFAULT 'x64',
  `current_agent_version` VARCHAR(100) DEFAULT '' COMMENT "String latest installed agent",
  `target_agent_version_id` BIGINT UNSIGNED,
  `deployed` bigint(20) NOT NULL DEFAULT 0 COMMENT "When it was deployed",
  `server_ip` varchar(100) default NULL COMMENT "Where to point target agent",
  `last_err` text,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_cs`) REFERENCES `tcredential_store`(`identifier`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (`id_os`) REFERENCES `tconfig_os`(`id_os`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`target_agent_version_id`) REFERENCES  `tagent_repository`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tremote_command`
-- ----------------------------------------------------------------------
CREATE TABLE `tremote_command` (
  `id` SERIAL,
  `name` varchar(150) NOT NULL,
  `timeout` int(10) unsigned NOT NULL default 30,
  `retries` int(10) unsigned NOT NULL default 3,
  `preconditions` text,
  `script` text,
  `postconditions` text,
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `id_group` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tremote_command_target`
-- ----------------------------------------------------------------------
CREATE TABLE `tremote_command_target` (
  `id` SERIAL,
  `rcmd_id` bigint unsigned NOT NULL,
  `id_agent` int(10) unsigned NOT NULL,
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `stdout` MEDIUMTEXT,
  `stderr` MEDIUMTEXT,
  `errorlevel` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`rcmd_id`) REFERENCES `tremote_command`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `trecon_script`
-- ---------------------------------------------------------------------
ALTER TABLE `trecon_script` ADD COLUMN `type` int(11) NOT NULL DEFAULT '0';
UPDATE `trecon_script` SET `description`='Specific&#x20;Pandora&#x20;FMS&#x20;Intel&#x20;DCM&#x20;Discovery&#x20;&#40;c&#41;&#x20;Artica&#x20;ST&#x20;2011&#x20;&lt;info@artica.es&gt;&#x0d;&#x0a;&#x0d;&#x0a;Usage:&#x20;./ipmi-recon.pl&#x20;&lt;task_id&gt;&#x20;&lt;group_id&gt;&#x20;&lt;custom_field1&gt;&#x20;&lt;custom_field2&gt;&#x20;&lt;custom_field3&gt;&#x20;&lt;custom_field4&gt;&#x0d;&#x0a;&#x0d;&#x0a;*&#x20;custom_field1&#x20;=&#x20;Network&#x20;i.e.:&#x20;192.168.100.0/24&#x0d;&#x0a;*&#x20;custom_field2&#x20;=&#x20;Username&#x0d;&#x0a;*&#x20;custom_field3&#x20;=&#x20;Password&#x0d;&#x0a;*&#x20;custom_field4&#x20;=&#x20;Additional&#x20;parameters&#x20;i.e.:&#x20;-D&#x20;LAN_2_0' WHERE `name`='IPMI&#x20;Recon';

-- ---------------------------------------------------------------------
-- Table `tusuario_perfil`
-- ---------------------------------------------------------------------
ALTER TABLE `tusuario_perfil` MODIFY COLUMN `no_hierarchy` tinyint(1) NOT NULL DEFAULT '0';


-- Extra tnetwork_component
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('N.&#x20;total&#x20;processes','Number&#x20;of&#x20;running&#x20;processes&#x20;in&#x20;a&#x20;Windows&#x20;system.',11,34,0,0,300,0,'tasklist&#x20;/NH&#x20;|&#x20;find&#x20;/c&#x20;/v&#x20;&quot;&quot;','','','',6,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','windows','',0,0,0.000000000000000,'','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Free&#x20;space&#x20;in&#x20;C:','Free&#x20;space&#x20;available&#x20;in&#x20;C:',11,34,0,0,300,0,'powershell&#x20;$obj=&#40;Get-WmiObject&#x20;-class&#x20;&quot;Win32_LogicalDisk&quot;&#x20;-namespace&#x20;&quot;root&#92;CIMV2&quot;&#41;&#x20;;&#x20;$obj.FreeSpace[0]&#x20;*&#x20;100&#x20;/$obj.Size[0]','','','',4,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','windows','',0,0,0.000000000000000,'%','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;uptime','System&#x20;uptime',43,36,0,0,300,0,'uptime&#x20;|sed&#x20;s/us&#92;.*$//g&#x20;|&#x20;sed&#x20;s/,&#92;.*$//g','','','',4,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','linux','',0,0,0.000000000000000,'','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;processes','Running&#x20;processes',43,34,0,0,300,0,'ps&#x20;elf&#x20;|&#x20;wc&#x20;-l','','','',6,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','linux','',0,0,0.000000000000000,'','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;system&#x20;load','Current&#x20;load&#x20;&#40;5&#x20;min&#41;',43,34,0,0,300,0,'uptime&#x20;|&#x20;awk&#x20;&#039;{print&#x20;$&#40;NF-1&#41;}&#039;&#x20;|&#x20;tr&#x20;-d&#x20;&#039;,&#039;','','','',6,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','linux','',0,0,0.000000000000000,'','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;available&#x20;memory&#x20;percent','Available&#x20;memory&#x20;%',43,34,0,0,300,0,'free&#x20;|&#x20;grep&#x20;Mem&#x20;|&#x20;awk&#x20;&#039;{print&#x20;$NF/$2&#x20;*&#x20;100}&#039;','','','',4,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','linux','',0,0,0.000000000000000,'%','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;available&#x20;disk&#x20;/','Available&#x20;free&#x20;space&#x20;in&#x20;mountpoint&#x20;/',43,34,0,0,300,0,'df&#x20;/&#x20;|&#x20;tail&#x20;-n&#x20;+2&#x20;|&#x20;awk&#x20;&#039;{print&#x20;$&#40;NF-1&#41;}&#039;&#x20;|&#x20;tr&#x20;-d&#x20;&#039;%&#039;','','','',4,2,0,'','','',0,0,1,0.00,0.00,'0.00',0.00,0.00,'',0,'','inherited','',0,0,0.000000000000000,'','nowizard','','nowizard','0','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);

-- 
-- Dumping data for table `tpen`
-- 

INSERT IGNORE INTO `tpen`
VALUES
	(9,'cisco','Cisco&#x20;System'),
	(11,'hp','Hewlett&#x20;Packard'),
	(2021,'general_snmp','U.C.&#x20;Davis,&#x20;ECE&#x20;Dept.&#x20;Tom'),
	(2636,'juniper','Juniper&#x20;Networks'),
	(3375,'f5','F5&#x20;Labs'),
	(8072,'general_snmp','Net&#x20;SNMP'),
	(12356,'fortinet','Fortinet')
;

-- 
-- Dumping data for table `tnetwork_profile` and `tnetwork_profile_pen`
-- 

SET @template_name = 'Network&#x20;Management';
SET @template_description = 'Basic network monitoring template';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Network Management')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Cisco&#x20;MIBS';
SET @template_description = 'Cisco devices monitoring template (SNMP)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Cisco MIBS' OR g.name = 'Catalyst 2900')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);
INSERT INTO tnetwork_profile_pen (pen, id_np) SELECT * FROM (SELECT p.pen pen, np.id_np id_np FROM tnetwork_profile np, tpen p WHERE np.name = @template_name AND (p.pen = 9)) AS tmp WHERE NOT EXISTS (SELECT pp.id_np FROM tnetwork_profile p, tnetwork_profile_pen pp WHERE p.id_np = pp.id_np AND p.name = @template_name);

SET @template_name = 'Linux&#x20;System';
SET @template_description = 'Linux system monitoring template (SNMP)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);

SET @module_group = 'Linux';

INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Linux' OR g.name = 'UCD Mibs (Linux, UCD-SNMP)')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);
INSERT INTO tnetwork_profile_pen (pen, id_np) SELECT * FROM (SELECT p.pen pen, np.id_np id_np FROM tnetwork_profile np, tpen p WHERE np.name = @template_name AND (p.pen = 2021 OR p.pen = 2636)) AS tmp WHERE NOT EXISTS (SELECT pp.id_np FROM tnetwork_profile p, tnetwork_profile_pen pp WHERE p.id_np = pp.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;System';
SET @template_description = 'Windows system monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Microsoft&#x20;Windows' OR g.name = 'Windows System')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Hardware';
SET @template_description = 'Windows hardware monitoring templae (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows Hardware Layer')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Active&#x20;Directory';
SET @template_description = 'Active directory monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows AD' OR g.name = 'AD&#x20;Counters')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;IIS';
SET @template_description = 'IIS monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows IIS' OR g.name = 'IIS&#x20;services')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Exchange';
SET @template_description = 'Exchange monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows Exchange' OR g.name = 'Exchange&#x20;Services' OR g.name = 'Exchange&#x20;TCP&#x20;Ports')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;LDAP';
SET @template_description = 'LDAP monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows LDAP')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;MDSTC';
SET @template_description = 'MDSTC monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows MSDTC')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Printers';
SET @template_description = 'Windows printers monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows Printers')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;DNS';
SET @template_description = 'Windows DNS monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows&#x20;DNS' OR g.name = 'DNS&#x20;Counters')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;MS&#x20;SQL&#x20;Server';
SET @template_description = 'MS SQL Server monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'MS&#x20;SQL&#x20;Server')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Oracle';
SET @template_description = 'Oracle monitoring template';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Oracle')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'MySQL';
SET @template_description = 'MySQL monitoring template';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'MySQL')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Antivirus';
SET @template_description = 'Windows antivirus monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Norton' OR g.name = 'Panda' OR g.name = 'McAfee' OR g.name = 'Bitdefender' OR g.name = 'BullGuard' OR g.name = 'AVG' OR g.name = 'Kaspersky')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);


-- Update widget.

UPDATE twidget SET description='Show a visual console' WHERE class_name='MapsMadeByUser';
UPDATE twidget SET description='Clock' WHERE class_name='ClockWidget';
UPDATE twidget SET description='Group status' WHERE class_name='SystemGroupStatusWidget';

--
-- Modifies tgrupo table.
--

ALTER TABLE tgrupo ADD COLUMN max_agents int(10) NOT NULL DEFAULT 0;

-- ----------------------------------------------------------------------
-- Table `tnode_relations`
-- ----------------------------------------------------------------------
CREATE TABLE `tnode_relations` (
	`id` int(10) unsigned NOT NULL auto_increment,
    `gateway` VARCHAR(100) NOT NULL,
	`imei` VARCHAR(100) NOT NULL,
	`node_address` VARCHAR(60) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------------------
-- New wizards components and plugins
-- ------------------------------------------------------------------------
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';
SET @plugin_description = 'Get&#x20;the&#x20;result&#x20;of&#x20;an&#x20;arithmetic&#x20;operation&#x20;using&#x20;several&#x20;OIDs&#x20;values.';
SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;
INSERT IGNORE INTO `tplugin` (`id`, `name`, `description`, `max_timeout`, `max_retries`, `execute`, `net_dst_opt`, `net_port_opt`, `user_opt`, `pass_opt`, `plugin_type`, `macros`, `parameters`) VALUES (@plugin_id,@plugin_name,@plugin_description,20,0,'/usr/share/pandora_server/util/plugin/wizard_snmp_module',NULL,NULL,NULL,NULL,0,'{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Host\",\"help\":\"\",\"value\":\"_address_\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Port\",\"help\":\"\",\"value\":\"161\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Version\",\"help\":\"1,&#x20;2c,&#x20;3\",\"value\":\"1\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Community\",\"help\":\"\",\"value\":\"public\",\"hide\":\"\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"Security&#x20;level&#x20;&#40;v3&#41;\",\"help\":\"noAuthNoPriv,&#x20;authNoPriv,&#x20;authPriv\",\"value\":\"\",\"hide\":\"\"},\"6\":{\"macro\":\"_field6_\",\"desc\":\"Username&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"7\":{\"macro\":\"_field7_\",\"desc\":\"Authentication&#x20;method&#x20;&#40;v3&#41;\",\"help\":\"MD5,&#x20;SHA\",\"value\":\"\",\"hide\":\"\"},\"8\":{\"macro\":\"_field8_\",\"desc\":\"Authentication&#x20;password&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"9\":{\"macro\":\"_field9_\",\"desc\":\"Privacy&#x20;method&#x20;&#40;v3&#41;\",\"help\":\"DES,&#x20;AES\",\"value\":\"\",\"hide\":\"\"},\"10\":{\"macro\":\"_field10_\",\"desc\":\"Privacy&#x20;password&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"11\":{\"macro\":\"_field11_\",\"desc\":\"OID&#x20;list\",\"help\":\"Comma&#x20;separated&#x20;OIDs&#x20;used\",\"value\":\"\",\"hide\":\"\"},\"12\":{\"macro\":\"_field12_\",\"desc\":\"Operation\",\"help\":\"Aritmetic&#x20;operation&#x20;to&#x20;get&#x20;data.&#x20;Macros&#x20;_oN_&#x20;will&#x20;be&#x20;changed&#x20;by&#x20;OIDs&#x20;in&#x20;list.&#x20;Example:&#x20;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_\",\"value\":\"\",\"hide\":\"\"}}','-host&#x20;&#039;_field1_&#039;&#x20;-port&#x20;&#039;_field2_&#039;&#x20;-version&#x20;&#039;_field3_&#039;&#x20;-community&#x20;&#039;_field4_&#039;&#x20;-secLevel&#x20;&#039;_field5_&#039;&#x20;-user&#x20;&#039;_field6_&#039;&#x20;-authMethod&#x20;&#039;_field7_&#039;&#x20;-authPass&#x20;&#039;_field8_&#039;&#x20;-privMethod&#x20;&#039;_field9_&#039;&#x20;-privPass&#x20;&#039;_field10_&#039;&#x20;-oidList&#x20;&#039;_field11_&#039;&#x20;-operation&#x20;&#039;_field12_&#039;');

SET @plugin_name = 'Wizard&#x20;SNMP&#x20;process';
SET @plugin_description = 'Check&#x20;if&#x20;a&#x20;process&#x20;is&#x20;running&#x20;&#40;1&#41;&#x20;or&#x20;not&#x20;&#40;0&#41;&#x20;in&#x20;OID&#x20;.1.3.6.1.2.1.25.4.2.1.2&#x20;SNMP&#x20;tree.';
SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;
INSERT IGNORE INTO `tplugin` (`id`, `name`, `description`, `max_timeout`, `max_retries`, `execute`, `net_dst_opt`, `net_port_opt`, `user_opt`, `pass_opt`, `plugin_type`, `macros`, `parameters`) VALUES (@plugin_id,@plugin_name,@plugin_description,20,0,'/usr/share/pandora_server/util/plugin/wizard_snmp_process',NULL,NULL,NULL,NULL,0,'{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Host\",\"help\":\"\",\"value\":\"_address_\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Port\",\"help\":\"\",\"value\":\"161\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Version\",\"help\":\"1,&#x20;2c,&#x20;3\",\"value\":\"1\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Community\",\"help\":\"\",\"value\":\"public\",\"hide\":\"\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"Security&#x20;level&#x20;&#40;v3&#41;\",\"help\":\"noAuthNoPriv,&#x20;authNoPriv,&#x20;authPriv\",\"value\":\"\",\"hide\":\"\"},\"6\":{\"macro\":\"_field6_\",\"desc\":\"Username&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"7\":{\"macro\":\"_field7_\",\"desc\":\"Authentication&#x20;method&#x20;&#40;v3&#41;\",\"help\":\"MD5,&#x20;SHA\",\"value\":\"\",\"hide\":\"\"},\"8\":{\"macro\":\"_field8_\",\"desc\":\"Authentication&#x20;password&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"9\":{\"macro\":\"_field9_\",\"desc\":\"Privacy&#x20;method&#x20;&#40;v3&#41;\",\"help\":\"DES,&#x20;AES\",\"value\":\"\",\"hide\":\"\"},\"10\":{\"macro\":\"_field10_\",\"desc\":\"Privacy&#x20;password&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"11\":{\"macro\":\"_field11_\",\"desc\":\"Process\",\"help\":\"Process&#x20;name&#x20;to&#x20;check&#x20;if&#x20;is&#x20;running&#x20;&#40;case&#x20;sensitive&#41;\",\"value\":\"\",\"hide\":\"\"}}','-host&#x20;&#039;_field1_&#039;&#x20;-port&#x20;&#039;_field2_&#039;&#x20;-version&#x20;&#039;_field3_&#039;&#x20;-community&#x20;&#039;_field4_&#039;&#x20;-secLevel&#x20;&#039;_field5_&#039;&#x20;-user&#x20;&#039;_field6_&#039;&#x20;-authMethod&#x20;&#039;_field7_&#039;&#x20;-authPass&#x20;&#039;_field8_&#039;&#x20;-privMethod&#x20;&#039;_field9_&#039;&#x20;-privPass&#x20;&#039;_field10_&#039;&#x20;-process&#x20;&#039;_field11_&#039;');

SET @plugin_name = 'Wizard&#x20;WMI&#x20;module';
SET @plugin_description = 'Get&#x20;the&#x20;result&#x20;of&#x20;an&#x20;arithmetic&#x20;operation&#x20;using&#x20;distinct&#x20;fields&#x20;in&#x20;a&#x20;WMI&#x20;query&#x20;&#40;Query&#x20;must&#x20;return&#x20;only&#x20;1&#x20;row&#41;.';
SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;
INSERT IGNORE INTO `tplugin` (`id`, `name`, `description`, `max_timeout`, `max_retries`, `execute`, `net_dst_opt`, `net_port_opt`, `user_opt`, `pass_opt`, `plugin_type`, `macros`, `parameters`) VALUES (@plugin_id,@plugin_name,@plugin_description,20,0,'/usr/share/pandora_server/util/plugin/wizard_wmi_module',NULL,NULL,NULL,NULL,0,'{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Host\",\"help\":\"\",\"value\":\"_address_\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Namespace&#x20;&#40;Optional&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"User\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Password\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"WMI&#x20;Class\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"6\":{\"macro\":\"_field6_\",\"desc\":\"Fields&#x20;list\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"7\":{\"macro\":\"_field7_\",\"desc\":\"Query&#x20;filter&#x20;&#40;Optional&#41;\",\"help\":\"Use&#x20;single&#x20;quotes&#x20;for&#x20;query&#x20;conditions\",\"value\":\"\",\"hide\":\"\"},\"8\":{\"macro\":\"_field8_\",\"desc\":\"Operation\",\"help\":\"Aritmetic&#x20;operation&#x20;to&#x20;get&#x20;data.&#x20;Macros&#x20;_fN_&#x20;will&#x20;be&#x20;changed&#x20;by&#x20;fields&#x20;in&#x20;list.&#x20;Example:&#x20;&#40;&#40;_f1_&#x20;-&#x20;_f2_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f1_\",\"value\":\"\",\"hide\":\"\"}}','-host&#x20;&#039;_field1_&#039;&#x20;-namespace&#x20;&#039;_field2_&#039;&#x20;-user&#x20;&#039;_field3_&#039;&#x20;-pass&#x20;&#039;_field4_&#039;&#x20;-wmiClass&#x20;&#039;_field5_&#039;&#x20;-fieldsList&#x20;&#039;_field6_&#039;&#x20;-queryFilter&#x20;&quot;_field7_&quot;&#x20;-operation&#x20;&#039;_field8_&#039;&#x20;-wmicPath&#x20;/usr/bin/wmic');

SET @plugin_name = 'Network&#x20;bandwidth&#x20;SNMP';
SET @plugin_description = 'Retrieves&#x20;amount&#x20;of&#x20;digital&#x20;information&#x20;sent&#x20;and&#x20;received&#x20;from&#x20;device&#x20;or&#x20;filtered&#x20;&#x20;interface&#x20;index&#x20;over&#x20;a&#x20;particular&#x20;time&#x20;&#40;agent/module&#x20;interval&#41;.';
SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;
INSERT IGNORE INTO `tplugin`  (`id`, `name`, `description`, `max_timeout`, `max_retries`, `execute`, `net_dst_opt`, `net_port_opt`, `user_opt`, `pass_opt`, `plugin_type`, `macros`, `parameters`) VALUES  (@plugin_id,@plugin_name,@plugin_description,300,0,'perl&#x20;/usr/share/pandora_server/util/plugin/pandora_snmp_bandwidth.pl','','','','',0,'{\"1\":{\"macro\":\"_field1_\",\"desc\":\"SNMP&#x20;Version&#40;1,2c,3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Community\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Host\",\"help\":\"\",\"value\":\"_address_\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Port\",\"help\":\"\",\"value\":\"161\",\"hide\":\"\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"Interface&#x20;Index&#x20;&#40;filter&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"6\":{\"macro\":\"_field6_\",\"desc\":\"securityName\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"7\":{\"macro\":\"_field7_\",\"desc\":\"context\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"8\":{\"macro\":\"_field8_\",\"desc\":\"securityLevel\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"9\":{\"macro\":\"_field9_\",\"desc\":\"authProtocol\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"10\":{\"macro\":\"_field10_\",\"desc\":\"authKey\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"11\":{\"macro\":\"_field11_\",\"desc\":\"privProtocol\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"12\":{\"macro\":\"_field12_\",\"desc\":\"privKey\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"13\":{\"macro\":\"_field13_\",\"desc\":\"UniqId\",\"help\":\"This&#x20;plugin&#x20;needs&#x20;to&#x20;store&#x20;information&#x20;in&#x20;temporary&#x20;directory&#x20;to&#x20;calculate&#x20;bandwidth.&#x20;Set&#x20;here&#x20;an&#x20;unique&#x20;identifier&#x20;with&#x20;no&#x20;spaces&#x20;or&#x20;symbols.\",\"value\":\"\",\"hide\":\"\"},\"14\":{\"macro\":\"_field14_\",\"desc\":\"inUsage\",\"help\":\"Retrieve&#x20;input&#x20;usage&#x20;&#40;%&#41;\",\"value\":\"\",\"hide\":\"\"},\"15\":{\"macro\":\"_field15_\",\"desc\":\"outUsage\",\"help\":\"Retrieve&#x20;output&#x20;usage&#x20;&#40;%&#41;\",\"value\":\"\",\"hide\":\"\"}}','-version&#x20;&#039;_field1_&#039;&#x20;-community&#x20;&#039;_field2_&#039;&#x20;-host&#x20;&#039;_field3_&#039;&#x20;-port&#x20;&#039;_field4_&#039;&#x20;-ifIndex&#x20;&#039;_field5_&#039;&#x20;-securityName&#x20;&#039;_field6_&#039;&#x20;-context&#x20;&#039;_field7_&#039;&#x20;-securityLevel&#x20;&#039;_field8_&#039;&#x20;-authProtocol&#x20;&#039;_field9_&#039;&#x20;-authKey&#x20;&#039;_field10_&#039;&#x20;-privProtocol&#x20;&#039;_field11_&#039;&#x20;-privKey&#x20;&#039;_field12_&#039;&#x20;-uniqid&#x20;&#039;_field13_&#039;&#x20;-inUsage&#x20;&#039;_field14_&#039;&#x20;-outUsage&#x20;&#039;_field15_&#039;');

SET @main_component_group_name = 'Wizard';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @main_component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@main_component_group_name,0);

SELECT @component_group_parent := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @main_component_group_name;

SET @component_group_name = 'CPU';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Memory';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Disk&#x20;devices';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Storage';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Temperature&#x20;sensors';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Processes';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Other';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Power&#x20;supply';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Fans';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Temperature';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Sessions';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'VPN';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Intrussions';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Antivirus';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Services';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Disks';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'CPU';

SET @component_name = 'CPU&#x20;User&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;percentage&#x20;of&#x20;CPU&#x20;time&#x20;spent&#x20;processing&#x20;user-level&#x20;code,&#x20;calculated&#x20;over&#x20;the&#x20;last&#x20;minute';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.9.0','',1,'','','','','',1);

SET @component_name = 'CPU&#x20;System&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;percentage&#x20;of&#x20;CPU&#x20;time&#x20;spent&#x20;processing&#x20;system-level&#x20;code,&#x20;calculated&#x20;over&#x20;the&#x20;last&#x20;minute';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.10.0','',1,'','','','','',1);

SET @component_name = 'CPU&#x20;Idle&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;percentage&#x20;of&#x20;CPU&#x20;time&#x20;spent&#x20;idle,&#x20;calculated&#x20;over&#x20;the&#x20;last&#x20;minute';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.11.0','',0,'','','','','',1);

SET @component_name = 'Load&#x20;average&#x20;-&#x20;_nameOID_';
SET @component_description = 'The&#x20;1,&#x20;5&#x20;or&#x20;15&#x20;minutes&#x20;load&#x20;average';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.10.1.3','',0,'1.3.6.1.4.1.2021.10.1.2','','','','',1);

SET @component_name = 'Cisco&#x20;CPU&#x20;_nameOID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;overall&#x20;CPU&#x20;busy&#x20;percentage&#x20;in&#x20;the&#x20;last&#x20;5&#x20;minute&#x20;period';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',1,2,'1.3.6.1.4.1.9.9.109.1.1.1.1.8','',1,'1.3.6.1.4.1.9.9.109.1.1.1.1.2','','','','',1);

SET @component_name = 'F5&#x20;CPU&#x20;_nameOID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'This&#x20;is&#x20;average&#x20;usage&#x20;ratio&#x20;of&#x20;CPU&#x20;for&#x20;the&#x20;associated&#x20;host&#x20;in&#x20;the&#x20;last&#x20;five&#x20;minutes';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,2,'1.3.6.1.4.1.3375.2.1.7.5.2.1.35','',1,'1.3.6.1.4.1.3375.2.1.7.5.2.1.3','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;CPU&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;average&#x20;usage&#x20;ratio&#x20;of&#x20;CPU&#x20;in&#x20;the&#x20;last&#x20;five&#x20;minutes';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.1.13.1.21','',1,'1.3.6.1.4.1.2636.3.1.13.1.5','','','','',1);

SET @component_name = 'HP&#x20;CPU&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;CPU&#x20;utilization&#x20;in&#x20;percent&#40;%&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','hp',1,1,'1.3.6.1.4.1.11.2.14.11.5.1.9.6.1.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;system&#x20;CPU&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'CPU&#x20;usage&#x20;of&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.1.3.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;CPU&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'CPU&#x20;usage&#x20;of&#x20;the&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.3.2.1.1.5','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'WMI&#x20;_DeviceID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'Load&#x20;capacity&#x20;of&#x20;each&#x20;processor,&#x20;averaged&#x20;to&#x20;the&#x20;last&#x20;second';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','{\"extra_field_1\":\"LoadPercentage\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"\",\"_field11__wmi_field\":\"_field11_\",\"_field12__wmi_field\":\"_field12_\",\"_field9__wmi_field\":\"_field9_\",\"_field10__wmi_field\":\"_field10_\",\"_field7__wmi_field\":\"_field7_\",\"_field8__wmi_field\":\"_field8_\",\"_field5__wmi_field\":\"_field5_\",\"_field6__wmi_field\":\"_field6_\",\"_field3__wmi_field\":\"_field3_\",\"_field4__wmi_field\":\"_field4_\",\"_field1__wmi_field\":\"_field1_\",\"_field2__wmi_field\":\"_field2_\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,2,'','',1,'','Win32_Processor','DeviceID','','{\"scan\":\"\",\"execution\":\"DeviceID&#x20;=&#x20;&#039;_DeviceID_&#039;\",\"field\":\"1\",\"key_string\":\"\"}',1);

SET @component_group_name = 'Memory';

SET @component_name = 'Total&#x20;RAM&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'Total&#x20;real/physical&#x20;memory&#x20;used&#x20;on&#x20;the&#x20;host';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.2021.4.6.0\",\"extra_field_2\":\"1.3.6.1.4.1.2021.4.5.0\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field2__snmp_field\":\"_port_\",\"_field1__snmp_field\":\"_address_\",\"_field4__snmp_field\":\"_community_\",\"_field3__snmp_field\":\"_version_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field12__snmp_field\":\"&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',2,1,'','',1,'','','','','',1);

SET @component_name = 'F5&#x20;host&#x20;RAM&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;host&#x20;memory&#x20;percentage&#x20;currently&#x20;in&#x20;use';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.3375.2.1.7.1.2.0\",\"extra_field_2\":\"1.3.6.1.4.1.3375.2.1.7.1.1.0\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',2,1,'','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Host&#x20;_nameOID_&#x20;RAM&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;host&#x20;memory&#x20;percentage&#x20;currently&#x20;in&#x20;use&#x20;for&#x20;the&#x20;specified&#x20;host';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.3375.2.1.7.4.2.1.3\",\"extra_field_2\":\"1.3.6.1.4.1.3375.2.1.7.4.2.1.2\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',2,2,'','',1,'1.3.6.1.4.1.3375.2.1.7.4.2.1.1','','','','',1);

SET @component_name = 'Fortinet&#x20;system&#x20;RAM&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'Memory&#x20;usage&#x20;of&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.1.4.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;RAM&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'Memory&#x20;usage&#x20;of&#x20;the&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.3.2.1.1.6','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'WMI&#x20;total&#x20;RAM&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'Percentage&#x20;of&#x20;physical&#x20;memory&#x20;currently&#x20;used';
SET @plugin_name = 'Wizard&#x20;WMI&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"TotalVisibleMemorySize\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_wmi_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-namespace&#x20;&quot;_namespace_wmi_&quot;&#x20;-user&#x20;&quot;_user_wmi_&quot;&#x20;-pass&#x20;&quot;_pass_wmi_&quot;&#x20;-wmiClass&#x20;&quot;_class_wmi_&quot;&#x20;-fieldsList&#x20;&quot;_field_wmi_0_,_field_wmi_1_&quot;&#x20;-queryFilter&#x20;&quot;&quot;&#x20;-operation&#x20;&quot;&#40;&#40;_f2_&#x20;-&#x20;_f1_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f2_&quot;&#x20;-wmicPath&#x20;/usr/bin/wmic\",\"value_operation\":\"&#40;&#40;_TotalVisibleMemorySize_&#x20;-&#x20;_FreePhysicalMemory_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_TotalVisibleMemorySize_\",\"server_plugin\":\"',@plugin_id,'\",\"_field2__wmi_field\":\"_namespace_wmi_\",\"_field1__wmi_field\":\"_address_\",\"_field4__wmi_field\":\"_pass_wmi_\",\"_field3__wmi_field\":\"_user_wmi_\",\"_field6__wmi_field\":\"_field_wmi_0_,_field_wmi_1_\",\"_field5__wmi_field\":\"_class_wmi_\",\"_field8__wmi_field\":\"&#40;&#40;_f2_&#x20;-&#x20;_f1_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f2_\",\"_field7__wmi_field\":\"\",\"field0_wmi_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',2,1,'','',1,'','Win32_OperatingSystem','FreePhysicalMemory','','{\"scan\":\"\",\"execution\":\"\",\"field\":\"\",\"key_string\":\"\"}',1);

SET @component_name = 'Total&#x20;Swap&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'Total&#x20;swap&#x20;memory&#x20;used&#x20;on&#x20;the&#x20;host';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.2021.4.4.0\",\"extra_field_2\":\"1.3.6.1.4.1.2021.4.3.0\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',2,1,'','',1,'','','','','',1);

SET @component_name = 'Cisco&#x20;memory&#x20;pool&#x20;_nameOID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'Indicates&#x20;the&#x20;percentage&#x20;of&#x20;bytes&#x20;from&#x20;the&#x20;memory&#x20;pool&#x20;that&#x20;are&#x20;currently&#x20;in&#x20;use&#x20;by&#x20;applications&#x20;on&#x20;the&#x20;managed&#x20;device';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.9.9.48.1.1.1.5\",\"extra_field_2\":\"1.3.6.1.4.1.9.9.48.1.1.1.6\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;&#40;_o1_&#x20;+&#x20;_o2_&#41;&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;&#40;_oid_1_&#x20;+&#x20;_oid_2_&#41;\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / (_o1_ + _o2_)\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',2,2,'','',1,'1.3.6.1.4.1.9.9.48.1.1.1.2','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;memory&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;buffer&#x20;pool&#x20;utilization&#x20;in&#x20;percentage&#x20;of&#x20;this&#x20;subject';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.1.13.1.11','',1,'1.3.6.1.4.1.2636.3.1.13.1.5','','','','',1);

SET @component_name = 'HP&#x20;memory&#x20;slot&#x20;_nameOID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;percentage&#x20;of&#x20;currently&#x20;allocated&#x20;bytes';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.11.2.14.11.5.1.1.2.1.1.1.7\",\"extra_field_2\":\"1.3.6.1.4.1.11.2.14.11.5.1.1.2.1.1.1.5\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','hp',2,2,'','',1,'1.3.6.1.4.1.11.2.14.11.5.1.1.2.1.1.1.1','','','','',1);

SET @component_group_name = 'Disk&#x20;devices';

SET @component_name = 'Disk&#x20;_nameOID_&#x20;bytes&#x20;read';
SET @component_description = 'The&#x20;number&#x20;of&#x20;bytes&#x20;read&#x20;from&#x20;this&#x20;device&#x20;since&#x20;boot';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,16,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'bytes/sec','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.15.1.1.3','',0,'1.3.6.1.4.1.2021.13.15.1.1.2','','','','',1);

SET @component_name = 'Disk&#x20;_nameOID_&#x20;bytes&#x20;written';
SET @component_description = 'The&#x20;number&#x20;of&#x20;bytes&#x20;written&#x20;to&#x20;this&#x20;device&#x20;since&#x20;boot';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,16,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'bytes/sec','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.15.1.1.4','',0,'1.3.6.1.4.1.2021.13.15.1.1.2','','','','',1);

SET @component_name = 'Disk&#x20;_nameOID_&#x20;read&#x20;accesses';
SET @component_description = 'The&#x20;number&#x20;of&#x20;read&#x20;accesses&#x20;from&#x20;this&#x20;device&#x20;since&#x20;boot';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,16,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'accesses/sec','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.15.1.1.5','',0,'1.3.6.1.4.1.2021.13.15.1.1.2','','','','',1);

SET @component_name = 'Disk&#x20;_nameOID_&#x20;write&#x20;accesses';
SET @component_description = 'The&#x20;number&#x20;of&#x20;write&#x20;accesses&#x20;to&#x20;this&#x20;device&#x20;since&#x20;boot';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,16,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'accesses/sec','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.15.1.1.6','',0,'1.3.6.1.4.1.2021.13.15.1.1.2','','','','',1);

SET @component_group_name = 'Storage';

SET @component_name = 'Storage&#x20;_nameOID_&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;amount&#x20;of&#x20;the&#x20;storage&#x20;represented&#x20;by&#x20;this&#x20;entry&#x20;that&#x20;is&#x20;allocated';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.2.1.25.2.3.1.6\",\"extra_field_2\":\"1.3.6.1.2.1.25.2.3.1.5\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',2,2,'','',1,'1.3.6.1.2.1.25.2.3.1.3','','','','',1);

SET @component_group_name = 'Temperature&#x20;sensors';

SET @component_name = 'Temperature&#x20;_nameOID_';
SET @component_description = 'The&#x20;temperature&#x20;of&#x20;this&#x20;sensor';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.16.2.1.3','',0,'1.3.6.1.4.1.2021.13.16.2.1.2','','','','',1);

SET @component_group_name = 'Processes';

SET @component_name = 'Process&#x20;_nameOID_';
SET @component_description = 'Check&#x20;if&#x20;the&#x20;process&#x20;is&#x20;running';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;process';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,2,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.2.1.25.4.2.1.7\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_process&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-process&#x20;&quot;_nameOID_&quot;\",\"value_operation\":\"1\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_nameOID_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',2,2,'','',0,'1.3.6.1.2.1.25.4.2.1.2','','','','',1);

SET @component_name = 'WMI&#x20;Number&#x20;of&#x20;processes';
SET @component_description = 'Number&#x20;of&#x20;process&#x20;contexts&#x20;currently&#x20;loaded&#x20;or&#x20;running&#x20;on&#x20;the&#x20;operating&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,1,'','',1,'','Win32_OperatingSystem','NumberOfProcesses','','{\"scan\":\"\",\"execution\":\"\",\"field\":\"0\",\"key_string\":\"\"}',1);

SET @component_name = 'WMI&#x20;&#x20;process&#x20;_Name_&#x20;running';
SET @component_description = 'Check&#x20;if&#x20;process&#x20;is&#x20;running';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,2,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"Name\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,2,'','',0,'','Win32_Process','Handle','','{\"scan\":\"\",\"execution\":\"Name&#x20;=&#x20;&#039;_Name_&#039;\",\"field\":\"1\",\"key_string\":\"_Name_\"}',1);

SET @component_group_name = 'Other';

SET @component_name = 'Wizard&#x20;system&#x20;uptime';
SET @component_description = 'The&#x20;time&#x20;&#40;in&#x20;hundredths&#x20;of&#x20;a&#x20;second&#41;&#x20;since&#x20;the&#x20;network&#x20;management&#x20;portion&#x20;of&#x20;the&#x20;system&#x20;was&#x20;last&#x20;re-initialized';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;


INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'_timeticks_','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','all',1,1,'1.3.6.1.2.1.25.1.1.0','',1,'','','','','',1);

SET @component_name = 'Wizard&#x20;network&#x20;uptime';
SET @component_description = 'The&#x20;time&#x20;&#40;in&#x20;hundredths&#x20;of&#x20;a&#x20;second&#41;&#x20;since&#x20;the&#x20;network&#x20;management&#x20;portion&#x20;of&#x20;the&#x20;system&#x20;was&#x20;last&#x20;re-initialized';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;


INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'_timeticks_','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','all',1,1,'1.3.6.1.2.1.1.3.0','',1,'','','','','',1);

SET @component_name = 'Blocks&#x20;sent';
SET @component_description = 'Number&#x20;of&#x20;blocks&#x20;sent&#x20;to&#x20;a&#x20;block&#x20;device';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.57.0','',0,'','','','','',1);

SET @component_name = 'Blocks&#x20;received';
SET @component_description = 'Number&#x20;of&#x20;blocks&#x20;received&#x20;from&#x20;a&#x20;block&#x20;device';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.58.0','',0,'','','','','',1);

SET @component_name = 'Interrupts&#x20;processed';
SET @component_description = 'Number&#x20;of&#x20;interrupts&#x20;processed';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.59.0','',0,'','','','','',1);

SET @component_group_name = 'Power&#x20;supply';

SET @component_name = 'Cisco&#x20;_nameOID_&#x20;power&#x20;state';
SET @component_description = 'The&#x20;current&#x20;state&#x20;of&#x20;the&#x20;power&#x20;supply:&#x20;normal&#40;1&#41;,&#x20;warning&#40;2&#41;,&#x20;critical&#40;3&#41;,&#x20;shutdown&#40;4&#41;,&#x20;notPresent&#40;5&#41;,&#x20;notFunctioning&#40;6&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',1,2,'1.3.6.1.4.1.9.9.13.1.5.1.3','',0,'1.3.6.1.4.1.9.9.13.1.5.1.2','','','','',1);

SET @component_name = 'F5&#x20;Power&#x20;supply&#x20;_nameOID_&#x20;status';
SET @component_description = 'The&#x20;status&#x20;of&#x20;the&#x20;indexed&#x20;power&#x20;supply&#x20;on&#x20;the&#x20;system:&#x20;bad&#40;0&#41;,&#x20;good&#40;1&#41;,&#x20;notpresent&#40;2&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,2,'1.3.6.1.4.1.3375.2.1.3.2.2.2.1.2','',1,'1.3.6.1.4.1.3375.2.1.3.2.2.2.1','','','','',1);

SET @component_name = 'WMI&#x20;_Name_&#x20;power&#x20;supply&#x20;state';
SET @component_description = 'State&#x20;of&#x20;the&#x20;power&#x20;supply&#x20;or&#x20;supplies&#x20;when&#x20;last&#x20;booted:&#x20;Other&#x20;&#40;1&#41;,&#x20;Unknown&#x20;&#40;2&#41;,&#x20;Safe&#x20;&#40;3&#41;,&#x20;Warning&#x20;&#40;4&#41;,&#x20;Critical&#x20;&#40;5&#41;,&#x20;Non-recoverable&#x20;&#40;6&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"PowerSupplyState\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,2,'','',0,'','Win32_ComputerSystem','Name','','{\"scan\":\"\",\"execution\":\"Name&#x20;=&#x20;&#039;_Name_&#039;\",\"field\":\"1\",\"key_string\":\"\"}',1);

SET @component_name = 'WMI&#x20;_Name_&#x20;Power&#x20;state';
SET @component_description = 'Current&#x20;power&#x20;state&#x20;of&#x20;a&#x20;computer&#x20;and&#x20;its&#x20;associated&#x20;operating&#x20;system:&#x20;Unknown&#x20;&#40;0&#41;,&#x20;Full&#x20;Power&#x20;&#40;1&#41;,&#x20;Low&#x20;Power&#x20;Mode&#x20;&#40;2&#41;,&#x20;Standby&#x20;&#40;3&#41;,&#x20;Unknown&#x20;&#40;4&#41;,&#x20;Power&#x20;Cycle&#x20;&#40;5&#41;,&#x20;Power&#x20;Off&#x20;&#40;6&#41;,&#x20;Warning&#x20;&#40;7&#41;,&#x20;Hibernate&#x20;&#40;8&#41;,&#x20;Soft&#x20;Off&#x20;&#40;9&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"PowerState\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,1,'','',0,'','Win32_ComputerSystem','Name','','{\"scan\":\"\",\"execution\":\"Name&#x20;=&#x20;&#039;_Name_&#039;\",\"field\":\"1\",\"key_string\":\"\"}',1);

SET @component_group_name = 'Fans';

SET @component_name = 'Cisco&#x20;_nameOID_&#x20;fan&#x20;state';
SET @component_description = 'The&#x20;current&#x20;state&#x20;of&#x20;the&#x20;fan:&#x20;normal&#40;1&#41;,&#x20;warning&#40;2&#41;,&#x20;critical&#40;3&#41;,&#x20;shutdown&#40;4&#41;,&#x20;notPresent&#40;5&#41;,&#x20;notFunctioning&#40;6&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',1,2,'1.3.6.1.4.1.9.9.13.1.4.1.3','',1,'1.3.6.1.4.1.9.9.13.1.4.1.2','','','','',1);

SET @component_name = 'F5&#x20;Fan&#x20;_nameOID_&#x20;status';
SET @component_description = 'The&#x20;status&#x20;of&#x20;the&#x20;indexed&#x20;chassis&#x20;fan&#x20;on&#x20;the&#x20;system:&#x20;bad&#40;0&#41;,&#x20;good&#40;1&#41;,&#x20;notpresent&#40;2&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,2,'1.3.6.1.4.1.3375.2.1.3.2.1.2.1.2','',1,'1.3.6.1.4.1.3375.2.1.3.2.1.2.1.1','','','','',1);

SET @component_name = 'HP&#x20;fan&#x20;tray&#x20;_nameOID_&#x20;state';
SET @component_description = 'Current&#x20;state&#x20;of&#x20;the&#x20;fan:&#x20;failed&#40;0&#41;,&#x20;removed&#40;1&#41;,&#x20;off&#40;2&#41;,&#x20;underspeed&#40;3&#41;,&#x20;overspeed&#40;4&#41;,&#x20;ok&#40;5&#41;,&#x20;maxstate&#40;6&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.11.2.14.11.5.1.54.2.1.1.4','',1,'1.3.6.1.4.1.11.2.14.11.5.1.54.2.1.1.2','','','','',1);

SET @component_group_name = 'Temperature';

SET @component_name = 'Cisco&#x20;_nameOID_&#x20;temperature';
SET @component_description = 'The&#x20;current&#x20;measurement&#x20;of&#x20;the&#x20;testpoint&#x20;being&#x20;instrumented';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',1,2,'1.3.6.1.4.1.9.9.13.1.3.1.3','',1,'1.3.6.1.4.1.9.9.13.1.3.1.2','','','','',1);

SET @component_name = 'F5&#x20;Temperature&#x20;sensor&#x20;_nameOID_';
SET @component_description = 'The&#x20;chassis&#x20;temperature&#x20;of&#x20;the&#x20;indexed&#x20;sensor&#x20;on&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,2,'1.3.6.1.4.1.3375.2.1.3.2.3.2.1.2','',1,'1.3.6.1.4.1.3375.2.1.3.2.3.2.1.1','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;temperature';
SET @component_description = 'The&#x20;temperature&#x20;of&#x20;this&#x20;subject';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.1.13.1.7','',1,'1.3.6.1.4.1.2636.3.1.13.1.5','','','','',1);

SET @component_name = 'HP&#x20;_nameOID_&#x20;temperature';
SET @component_description = 'The&#x20;current&#x20;temperature&#x20;given&#x20;by&#x20;the&#x20;indexed&#x20;chassis';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','hp',1,2,'1.3.6.1.4.1.11.2.14.11.1.2.8.1.1.3','',0,'1.3.6.1.4.1.11.2.14.11.1.2.8.1.1.2','','','','',1);

SET @component_group_name = 'Sessions';

SET @component_name = 'F5&#x20;Current&#x20;auth&#x20;sessions';
SET @component_description = 'The&#x20;current&#x20;number&#x20;of&#x20;concurrent&#x20;auth&#x20;sessions';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.1.1.2.2.3.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;auth&#x20;success&#x20;results';
SET @component_description = 'The&#x20;total&#x20;number&#x20;of&#x20;auth&#x20;success&#x20;results';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.1.1.2.2.5.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;auth&#x20;failure&#x20;results';
SET @component_description = 'The&#x20;total&#x20;number&#x20;of&#x20;auth&#x20;failure&#x20;results';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.1.1.2.2.6.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;auth&#x20;error&#x20;results';
SET @component_description = 'The&#x20;total&#x20;number&#x20;of&#x20;auth&#x20;error&#x20;results';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.1.1.2.2.8.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;ephemeral&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;current&#x20;number&#x20;of&#x20;ephemeral&#x20;sessions&#x20;on&#x20;the&#x20;device';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.1.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;direct&#x20;requests&#x20;count';
SET @component_description = 'The&#x20;number&#x20;of&#x20;direct&#x20;requests&#x20;to&#x20;Fortigate&#x20;local&#x20;stack&#x20;from&#x20;external,&#x20;reflecting&#x20;DOS&#x20;attack&#x20;towards&#x20;the&#x20;Fortigate';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.7.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;clash&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;number&#x20;of&#x20;new&#x20;sessions&#x20;which&#x20;have&#x20;collision&#x20;with&#x20;existing&#x20;sessions.&#x20;This&#x20;generally&#x20;highlights&#x20;a&#x20;shortage&#x20;of&#x20;ports&#x20;or&#x20;IP&#x20;in&#x20;ip-pool&#x20;during&#x20;source&#x20;natting&#x20;&#40;PNAT&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.3.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;expectation&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;number&#x20;of&#x20;current&#x20;expectation&#x20;sessions';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.4.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;sync&#x20;queue&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;sync&#x20;queue&#x20;full&#x20;counter,&#x20;reflecting&#x20;bursts&#x20;on&#x20;the&#x20;sync&#x20;queue';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.5.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;accept&#x20;queue&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;accept&#x20;queue&#x20;full&#x20;counter,&#x20;reflecting&#x20;bursts&#x20;on&#x20;the&#x20;accept&#x20;queue';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.6.0','',1,'','','','','',1);

SET @component_group_name = 'VPN';

SET @component_name = 'F5&#x20;Current&#x20;SSL/VPN&#x20;connections';
SET @component_description = 'The&#x20;total&#x20;current&#x20;SSL/VPN&#x20;connections&#x20;in&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.6.1.5.3.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;SSL/VPN&#x20;bytes&#x20;received';
SET @component_description = 'The&#x20;total&#x20;raw&#x20;bytes&#x20;received&#x20;by&#x20;SSL/VPN&#x20;connections&#x20;in&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'bytes','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.6.1.5.5.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;SSL/VPN&#x20;bytes&#x20;transmitted';
SET @component_description = 'The&#x20;total&#x20;raw&#x20;bytes&#x20;transmitted&#x20;by&#x20;SSL/VPN&#x20;connections&#x20;in&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'bytes','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.6.1.5.6.0','',1,'','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;active&#x20;sites';
SET @component_description = 'The&#x20;number&#x20;of&#x20;active&#x20;sites&#x20;in&#x20;the&#x20;VPN';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.26.1.2.1.9','',1,'1.3.6.1.4.1.2636.3.26.1.2.1.2','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;age';
SET @component_description = 'The&#x20;age&#x20;&#40;i.e.,&#x20;time&#x20;from&#x20;creation&#x20;till&#x20;now&#41;&#x20;of&#x20;this&#x20;VPN&#x20;in&#x20;hundredths&#x20;of&#x20;a&#x20;second';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'_timeticks_','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.26.1.2.1.12','',1,'1.3.6.1.4.1.2636.3.26.1.2.1.2','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;interface&#x20;status';
SET @component_description = 'Status&#x20;of&#x20;this&#x20;interface:&#x20;unknown&#40;0&#41;,&#x20;noLocalInterface&#40;1&#41;,&#x20;disabled&#40;2&#41;,&#x20;encapsulationMismatch&#40;3&#41;,&#x20;down&#40;4&#41;,&#x20;up&#40;5&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.26.1.3.1.10','',1,'1.3.6.1.4.1.2636.3.26.1.3.1.2','','','','',1);

SET @component_group_name = 'Intrussions';

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;intrussions&#x20;detected';
SET @component_description = 'Number&#x20;of&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.1','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;intrussions&#x20;blocked';
SET @component_description = 'Number&#x20;of&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.1','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;critical&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;critical&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.3','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;high&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;high&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.4','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;medium&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;medium&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.5','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;low&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;low&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.6','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;informational&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;informational&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.7','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;signature&#x20;detections';
SET @component_description = 'Number&#x20;of&#x20;intrusions&#x20;detected&#x20;by&#x20;signature&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.8','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;anomaly&#x20;detections';
SET @component_description = 'Number&#x20;of&#x20;intrusions&#x20;DECed&#x20;as&#x20;anomalies&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.9','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_group_name = 'Antivirus';

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;virus&#x20;detected';
SET @component_description = 'Number&#x20;of&#x20;virus&#x20;transmissions&#x20;detected&#x20;in&#x20;the&#x20;virtual&#x20;domain&#x20;since&#x20;start-up';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.8.2.1.1.1','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;virus&#x20;blocked';
SET @component_description = 'Number&#x20;of&#x20;virus&#x20;transmissions&#x20;blocked&#x20;in&#x20;the&#x20;virtual&#x20;domain&#x20;since&#x20;start-up';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.8.2.1.1.2','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;oversized&#x20;detected';
SET @component_description = 'Number&#x20;of&#x20;over-sized&#x20;file&#x20;transmissions&#x20;detected&#x20;in&#x20;the&#x20;virtual&#x20;domain&#x20;since&#x20;start-up';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.8.2.1.1.17','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;oversized&#x20;blocked';
SET @component_description = 'Number&#x20;of&#x20;over-sized&#x20;file&#x20;transmissions&#x20;blocked&#x20;in&#x20;the&#x20;virtual&#x20;domain&#x20;since&#x20;start-up';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.8.2.1.1.18','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_group_name = 'Services';

SET @component_name = 'WMI&#x20;Service&#x20;_Name_&#x20;running';
SET @component_description = '_Caption_';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,2,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"State\",\"extra_field_2\":\"Caption\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,2,'','',0,'','Win32_Service','Name','','{\"scan\":\"\",\"execution\":\"Name&#x20;=&#x20;&#039;_Name_&#039;\",\"field\":\"1\",\"key_string\":\"Running\"}',1);

SET @component_group_name = 'Disks';

SET @component_name = 'WMI&#x20;disk&#x20;_DeviceID_&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'Space&#x20;percentage&#x20;used&#x20;on&#x20;the&#x20;logical&#x20;disk';
SET @plugin_name = 'Wizard&#x20;WMI&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"Size\",\"extra_field_2\":\"FreeSpace\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_wmi_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-namespace&#x20;&quot;_namespace_wmi_&quot;&#x20;-user&#x20;&quot;_user_wmi_&quot;&#x20;-pass&#x20;&quot;_pass_wmi_&quot;&#x20;-wmiClass&#x20;&quot;_class_wmi_&quot;&#x20;-fieldsList&#x20;&quot;_field_wmi_1_,_field_wmi_2_&quot;&#x20;-queryFilter&#x20;&quot;DeviceID&#x20;=&#x20;&#039;_DeviceID_&#039;&quot;&#x20;-operation&#x20;&quot;&#40;&#40;_f1_&#x20;-&#x20;_f2_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f1_&quot;&#x20;-wmicPath&#x20;/usr/bin/wmic\",\"value_operation\":\"&#40;&#40;_Size_&#x20;-&#x20;_FreeSpace_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_Size_\",\"server_plugin\":\"',@plugin_id,'\",\"_field2__wmi_field\":\"_namespace_wmi_\",\"_field1__wmi_field\":\"_address_\",\"_field4__wmi_field\":\"_pass_wmi_\",\"_field3__wmi_field\":\"_user_wmi_\",\"_field6__wmi_field\":\"_field_wmi_1_,_field_wmi_2_\",\"_field5__wmi_field\":\"_class_wmi_\",\"_field8__wmi_field\":\"&#40;&#40;_f1_&#x20;-&#x20;_f2_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f1_\",\"_field7__wmi_field\":\"DeviceID&#x20;=&#x20;&#039;_DeviceID_&#039;\",\"field0_wmi_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',2,2,'','',1,'','Win32_LogicalDisk','DeviceID','','{\"scan\":\"DriveType&#x20;=&#x20;3\",\"execution\":\"\",\"field\":\"\",\"key_string\":\"\"}',1);

INSERT IGNORE INTO `tpen` VALUES (171,'dlink','D-Link Systems, Inc.'),(14988,'mikrotik','MikroTik'),(6486,'alcatel','Alcatel-Lucent Enterprise'),(41112,'ubiquiti','Ubiquiti Networks, Inc.'),(207,'telesis','Allied Telesis, Inc.'),(10002,'frogfoot','Frogfoot Networks'),(2,'ibm','IBM'),(4,'unix','Unix'),(63,'apple','Apple Computer, Inc.'),(674,'dell','Dell Inc.'),(111,'oracle','Oracle'),(116,'hitachi','Hitachi, Ltd.'),(173,'netlink','Netlink'),(188,'ascom','Ascom'),(6574,'synology','Synology Inc.'),(3861,'fujitsu','Fujitsu Network Communications, Inc.'),(53526,'dell','Dell ATC'),(52627,'apple','Apple Inc'),(19464,'hitachi','Hitachi Communication Technologies, Ltd.'),(13062,'ascom','Ascom');

CREATE TABLE IF NOT EXISTS `tipam_network` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`network` varchar(100) NOT NULL default '',
	`name_network` varchar(255) default '',
	`description` text NOT NULL,
	`location` int(10) unsigned NULL,
	`id_recon_task` int(10) unsigned NOT NULL,
	`scan_interval` tinyint(2) default 1,
	`monitoring` tinyint(2) default 0,
	`id_group` mediumint(8) unsigned NULL default 0,
	`lightweight_mode` tinyint(2) default 0,
	`users_operator` text,
	`id_site` bigint unsigned,
	`vrf` int(10) unsigned,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_recon_task`) REFERENCES trecon_task(`id_rt`) ON DELETE CASCADE,
	FOREIGN KEY (`location`) REFERENCES `tipam_network_location`(`id`) ON DELETE CASCADE,
	FOREIGN KEY (`id_site`) REFERENCES `tipam_sites`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
	FOREIGN KEY (`vrf`) REFERENCES `tagente`(`id_agente`) ON DELETE SET NULL ON UPDATE CASCADE
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
	`id_site` bigint unsigned,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_site`) REFERENCES `tipam_sites`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tipam_supernet_network` (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`id_supernet` bigint(20) unsigned NOT NULL,
	`id_network` bigint(20) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_supernet`) REFERENCES tipam_supernet(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (`id_network`) REFERENCES tipam_network(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tipam_network_location` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(100) NOT NULL default '',
	PRIMARY KEY (`id`),
	UNIQUE (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tipam_sites` (
    `id` serial,
    `name` varchar(100) UNIQUE NOT NULL default '',
    `description` text,
    `parent` bigint unsigned null,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`parent`) REFERENCES `tipam_sites`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `tipam_network_location` (`name`) SELECT `location` FROM `tipam_network` WHERE `location` <> '';

SET @insert_type = 3;
SET @insert_name = 'IPAM Recon';
SET @insert_description = 'This script is used to automatically detect network hosts availability and name, used as Recon Custom Script in the recon task. Parameters used are:\n\n* custom_field1 = network. i.e.: 192.168.100.0/24\n* custom_field2 = associated IPAM network id. i.e.: 4. Please do not change this value, it is assigned automatically in IPAM management.\n\nSee documentation for more information.';
SET @insert_script = '/usr/share/pandora_server/util/recon_scripts/IPAMrecon.pl';
SET @insert_macros = '{"1":{"macro":"_field1_","desc":"Network","help":"i.e.:&#x20;192.168.100.0/24","value":"","hide":""}}';
INSERT IGNORE INTO trecon_script (`id_recon_script`,`type`, `name`, `description`, `script`, `macros`)
SELECT `id_recon_script`,`type`, `name`, `description`, `script`, `macros` FROM (
	SELECT `id_recon_script`,`type`, `name`, `description`, `script`, `macros` FROM `trecon_script` WHERE `name` = @insert_name
	UNION
	SELECT (SELECT max(`id_recon_script`)+1 FROM `trecon_script`) AS `id_recon_script`,
	@insert_type as `type`,
	@insert_name as `name`,
	@insert_description as `description`,
	@insert_script as `script`,
	@insert_macros as `macros`
) t limit 1;

DELETE FROM `tconfig` WHERE `token` = 'ipam_installed';

DELETE FROM `tconfig` WHERE `token` = 'ipam_recon_script_id';

-- ----------------------------------------------------------------------
-- Table `tsync_queue`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tsync_queue` (
	`id` serial,
	`sql` MEDIUMTEXT,
	`target` bigint(20) unsigned NOT NULL,
	`utimestamp` bigint(20) default '0',
	`operation` text,
	`table` text,
	`error` MEDIUMTEXT,
	`result` TEXT,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tperfil` DROP COLUMN `incident_view`;
ALTER TABLE `tperfil` DROP COLUMN `incident_edit`;
ALTER TABLE `tperfil` DROP COLUMN `incident_management`;

ALTER TABLE `tperfil` ADD COLUMN `network_config_view`tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tperfil` ADD COLUMN `network_config_edit`tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tperfil` ADD COLUMN `network_config_management`tinyint(1) NOT NULL DEFAULT 0;

-- -----------------------------------------------------
-- Table `talert_execution_queue`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_execution_queue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `data` LONGTEXT,
  `utimestamp` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

UPDATE `tlanguage` SET `name` = 'Deutsch' WHERE `id_language` = 'de';

-- ----------------------------------------------------------------------
-- Table `tncm_vendor`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_vendor` (
    `id` serial,
    `name` varchar(255) UNIQUE,
    `icon` VARCHAR(255) DEFAULT '',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tncm_model`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_model` (
    `id` serial,
    `id_vendor` bigint(20) unsigned NOT NULL,
    `name` varchar(255) UNIQUE,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_vendor`) REFERENCES `tncm_vendor`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tncm_template`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_template` (
    `id` serial,
    `name` text,
    `vendors` text,
    `models` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tncm_script`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_script` (
    `id` serial,
    `type` int unsigned not null default 0,
    `content` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tncm_template_scripts`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_template_scripts` (
    `id` serial,
    `id_template` bigint(20) unsigned NOT NULL,
    `id_script` bigint(20) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_template`) REFERENCES `tncm_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_script`) REFERENCES `tncm_script`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tncm_agent`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_agent` (
    `id_agent` int(10) unsigned NOT NULL,
    `id_vendor` bigint(20) unsigned,
    `id_model` bigint(20) unsigned,
    `protocol` int unsigned not null default 0,
    `cred_key` varchar(100),
    `adv_key` varchar(100),
    `port` int(4) unsigned default 22,
    `status` int(4) NOT NULL default 5,
    `updated_at` bigint(20) NOT NULL default 0,
    `config_backup_id` bigint(20) UNSIGNED DEFAULT NULL,
    `id_template` bigint(20) unsigned,
    `execute_type` int(2) UNSIGNED NOT NULL default 0,
    `execute` int(2) UNSIGNED NOT NULL default 0,
    `cron_interval` varchar(100) default '',
    `event_on_change` int unsigned default null,
    `last_error` text,
    PRIMARY KEY (`id_agent`),
    FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`cred_key`) REFERENCES `tcredential_store`(`identifier`) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (`id_template`) REFERENCES `tncm_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_vendor`) REFERENCES `tncm_vendor`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (`id_model`) REFERENCES `tncm_model`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tncm_agent_data`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_agent_data` (
    `id` serial,
    `id_agent` int(10) unsigned NOT NULL,
    `script_type` int unsigned not null,
    `data` LONGBLOB,
    `status` int(4) NOT NULL default 5,
    `updated_at` bigint(20) NOT NULL default 0,
    FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tncm_queue`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_queue` (
    `id` SERIAL,
    `id_agent` INT(10) UNSIGNED NOT NULL,
    `id_script` BIGINT(20) UNSIGNED NOT NULL,
    `utimestamp` INT UNSIGNED NOT NULL,
    `scheduled` INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_script`) REFERENCES `tncm_script`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tncm_snippet`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_snippet` (
    `id` SERIAL,
    `name` TEXT,
    `content` TEXT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tncm_firmware`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tncm_firmware` (
    `id` SERIAL,
    `name` varchar(255),
    `shortname` varchar(255) unique,
    `vendor` bigint(20) unsigned,
    `models` text,
    `path` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tncm_vendor` VALUES
    (1,'Cisco'),
	(2, 'D-Link Systems, Inc.'),
    (3, 'MikroTik'),
    (4, 'Alcatel-Lucent Enterprise'),
    (5, 'Ubiquiti Networks, Inc.'),
    (6, 'Allied Telesis, Inc.'),
    (7, 'Frogfoot Networks'),
    (8, 'IBM'),
    (9, 'Dell Inc.'),
    (10, 'Hitachi Communication Technologies, Ltd.'),
    (11, 'Netlink'),
    (12, 'Ascom'),
    (13, 'Synology Inc.'),
    (14, 'Fujitsu Network Communications, Inc.');

INSERT INTO `tncm_model` VALUES (1,1,'7200');

INSERT INTO `tncm_template` VALUES (1,'cisco-base','[\"1\"]','[\"1\"]');

INSERT INTO `tncm_script` VALUES 
    (1,0,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;exit'),
    (2,1,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;term&#x20;length&#x20;0&#x0d;&#x0a;capture:show&#x20;running-config&#x0d;&#x0a;exit&#x0d;&#x0a;'),
	(3,2,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;term&#x20;length&#x20;0&#x0d;&#x0a;config&#x20;terminal&#x0d;&#x0a;_applyconfigbackup_&#x0d;&#x0a;exit&#x0d;&#x0a;'),
	(4,3,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;term&#x20;length&#x20;0&#x0d;&#x0a;capture:show&#x20;version&#x20;|&#x20;i&#x20;IOS&#x20;Software&#x0d;&#x0a;exit&#x0d;&#x0a;'),
	(5,5,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;term&#x20;length&#x20;0&#x0d;&#x0a;config&#x20;term&#x0d;&#x0a;end&#x0d;&#x0a;end&#x0d;&#x0a;exit&#x0d;&#x0a;'),
	(6,4,'copy&#x20;tftp&#x20;flash&#x0d;&#x0a;expect:&#92;]&#92;?&#x0d;&#x0a;_TFTP_SERVER_IP_&#x0d;&#x0a;expect:&#92;]&#92;?&#x0d;&#x0a;_SOURCE_FILE_NAME_&#x0d;&#x0a;expect:&#92;]&#92;?&#x0d;&#x0a;_DESTINATION_FILE_NAME_&#x0d;&#x0a;show&#x20;flash&#x0d;&#x0a;reload&#x0d;&#x0a;expect:confirm&#x0d;&#x0a;y&#x0d;&#x0a;config&#x20;terminal&#x0d;&#x0a;boot&#x20;system&#x20;_DESTINATION_FILE_NAME_');
INSERT INTO `tncm_template_scripts`(`id_template`, `id_script`) VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6);

-- ----------------------------------------------------------------------
-- Table `talert_calendar`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `talert_calendar` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL default '',
	`id_group` INT(10) NOT NULL DEFAULT 0,
	`description` text,
	PRIMARY KEY (`id`),
	UNIQUE (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `talert_calendar` VALUES (1, 'Default', 0, 'Default calendar');

ALTER TABLE `talert_special_days` ADD COLUMN `id_calendar` int(10) unsigned NOT NULL DEFAULT 1;
ALTER TABLE `talert_special_days` ADD COLUMN `day_code` tinyint(2) unsigned NOT NULL DEFAULT 0;

UPDATE `talert_special_days` set `day_code` = 1 WHERE `same_day` = 'monday';
UPDATE `talert_special_days` set `day_code` = 2 WHERE `same_day` = 'tuesday';
UPDATE `talert_special_days` set `day_code` = 3 WHERE `same_day` = 'wednesday';
UPDATE `talert_special_days` set `day_code` = 4 WHERE `same_day` = 'thursday';
UPDATE `talert_special_days` set `day_code` = 5 WHERE `same_day` = 'friday';
UPDATE `talert_special_days` set `day_code` = 6 WHERE `same_day` = 'saturday';
UPDATE `talert_special_days` set `day_code` = 7 WHERE `same_day` = 'sunday';

ALTER TABLE `talert_special_days` DROP COLUMN `same_day`;
ALTER TABLE `talert_special_days` ADD FOREIGN KEY (`id_calendar`) REFERENCES `talert_calendar`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE `tconfig` c1 JOIN (select count(*) as n FROM `tconfig` c2 WHERE (c2.`token` = "node_metaconsole" AND c2.`value` = 1) OR (c2.`token` = "centralized_management" AND c2.`value` = 1) ) v SET c1. `value` = 0 WHERE c1.token = "autocreate_remote_users" AND v.n = 2;
