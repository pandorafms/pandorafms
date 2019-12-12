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
	`disabled` tinyint(1) unsigned NOT NULL default '0',
	`last_event_replication` bigint(20) default '0'
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
	PRIMARY KEY  (`id`)
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

-- ---------------------------------------------------------------------
-- Table `tpolicy_queue`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpolicy_queue` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy` int(10) unsigned NOT NULL default '0',
	`id_agent` int(10) unsigned NOT NULL default '0',
	`operation` varchar(15) default '',
	`progress` int(10) unsigned NOT NULL default '0',
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
	`external_source` Text,
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
ALTER TABLE `treport_content_template` ADD COLUMN `uncompressed_module` TINYINT DEFAULT '0';
ALTER TABLE `treport_content_template` MODIFY COLUMN `historical_db` tinyint(1) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `lapse_calc` tinyint(1) unsigned NOT NULL DEFAULT '0',
	MODIFY COLUMN `lapse` int(11) unsigned NOT NULL DEFAULT '300',
	MODIFY COLUMN `visual_format` tinyint(1) unsigned NOT NULL DEFAULT '0';
ALTER TABLE `treport_content_template` ADD COLUMN `landscape` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content_template` ADD COLUMN `pagebreak` tinyint(1) UNSIGNED NOT NULL default 0;

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

ALTER TABLE `tmetaconsole_event` ADD COLUMN `data` double(22,5) default NULL;
ALTER TABLE `tmetaconsole_event` ADD COLUMN `module_status` int(4) NOT NULL default '0';
ALTER TABLE `tmetaconsole_event` ADD INDEX `server_id` (`server_id`);

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

ALTER TABLE `tmetaconsole_event_history` ADD COLUMN `data` double(22,5) default NULL;
ALTER TABLE `tmetaconsole_event_history` ADD COLUMN `module_status` int(4) NOT NULL default '0';
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
	ADD INDEX `id_tagente_idx` (`id_tagente`);

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
ALTER TABLE talert_templates ADD COLUMN `field11_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field12_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field13_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field14_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_templates ADD COLUMN `field15_recovery` TEXT NOT NULL DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE talert_snmp ADD COLUMN `al_field11` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN `al_field12` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN `al_field13` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN `al_field14` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_snmp ADD COLUMN `al_field15` TEXT NOT NULL DEFAULT "";
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

-- ----------------------------------------------------------------------
-- Table `tserver`
-- ----------------------------------------------------------------------
ALTER TABLE tserver ADD COLUMN `server_keepalive` int(11) DEFAULT 0;
ALTER TABLE `tserver` MODIFY COLUMN `server_keepalive` int(11) NOT NULL DEFAULT '0';

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
ALTER TABLE talert_actions ADD COLUMN `field11_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field12_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field13_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field14_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field15_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE `talert_actions` MODIFY COLUMN `field11` text NOT NULL,
	MODIFY COLUMN `field12` text NOT NULL,
	MODIFY COLUMN `field13` text NOT NULL,
	MODIFY COLUMN `field14` text NOT NULL,
	MODIFY COLUMN `field15` text NOT NULL;

-- ---------------------------------------------------------------------
-- Table `talert_commands`
-- ---------------------------------------------------------------------
UPDATE `talert_commands` SET `fields_descriptions` = '[\"Integria&#x20;IMS&#x20;API&#x20;path\",\"Integria&#x20;IMS&#x20;API&#x20;pass\",\"Integria&#x20;IMS&#x20;user\",\"Integria&#x20;IMS&#x20;user&#x20;pass\",\"Ticket&#x20;title\",\"Ticket&#x20;group&#x20;ID\",\"Ticket&#x20;priority\",\"Email&#x20;copy\",\"Ticket&#x20;owner\",\"Ticket&#x20;description\"]', `fields_values` = '[\"\",\"\",\"\",\"\",\"\",\"\",\"10,Maintenance;0,Informative;1,Low;2,Medium;3,Serious;4,Very&#x20;Serious\",\"\",\"\",\"\"]' WHERE `id` = 11 AND `name` = 'Integria&#x20;IMS&#x20;Ticket';
UPDATE `talert_commands` SET `description` = 'This&#x20;alert&#x20;send&#x20;an&#x20;email&#x20;using&#x20;internal&#x20;Pandora&#x20;FMS&#x20;Server&#x20;SMTP&#x20;capabilities&#x20;&#40;defined&#x20;in&#x20;each&#x20;server,&#x20;using:&#x0d;&#x0a;_field1_&#x20;as&#x20;destination&#x20;email&#x20;address,&#x20;and&#x0d;&#x0a;_field2_&#x20;as&#x20;subject&#x20;for&#x20;message.&#x20;&#x0d;&#x0a;_field3_&#x20;as&#x20;text&#x20;of&#x20;message.&#x20;&#x0d;&#x0a;_field4_&#x20;as&#x20;content&#x20;type&#x20;&#40;text/plain&#x20;or&#x20;html/text&#41;.', `fields_descriptions` = '[\"Destination&#x20;address\",\"Subject\",\"Text\",\"Content&#x20;Type\",\"\",\"\",\"\",\"\",\"\",\"\"]', `fields_values` = '[\"\",\"\",\"_html_editor_\",\"_content_type_\",\"\",\"\",\"\",\"\",\"\",\"\"]' WHERE id=1;
ALTER TABLE `talert_commands` ADD COLUMN `id_group` mediumint(8) unsigned NULL default 0;
ALTER TABLE `talert_commands` ADD COLUMN `fields_hidden` text;

UPDATE `talert_actions` SET `field4` = 'text/html', `field4_recovery` = 'text/html' WHERE id = 1;

DELETE FROM `talert_commands` WHERE `id` = 11;

ALTER TABLE `talert_commands` MODIFY COLUMN `id_group` mediumint(8) unsigned NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `tmap`
-- ---------------------------------------------------------------------
ALTER TABLE `tmap` MODIFY COLUMN `id_user` varchar(250) NOT NULL DEFAULT '';

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO `tconfig` (`token`, `value`) VALUES ('big_operation_step_datos_purge', '100');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('small_operation_step_datos_purge', '1000');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('days_autodisable_deletion', '30');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('MR', 34);
INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_docs_logo', 'default_docs.png');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_support_logo', 'default_support.png');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_logo_white_bg_preview', 'pandora_logo_head_white_bg.png');
UPDATE tconfig SET value = 'https://licensing.artica.es/pandoraupdate7/server.php' WHERE token='url_update_manager';
DELETE FROM `tconfig` WHERE `token` = 'current_package_enterprise';
INSERT INTO `tconfig` (`token`, `value`) VALUES ('current_package_enterprise', '742');
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

-- ---------------------------------------------------------------------
-- Table `tconfig_os`
-- ---------------------------------------------------------------------

INSERT INTO `tconfig_os` (`id_os`, `name`, `description`, `icon_name`) VALUES (100, 'Cluster', 'Cluster agent', 'so_cluster.png');
	
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
UPDATE `tlink` SET `name` = 'Documentation', `link` = 'http://wiki.pandorafms.com/' WHERE `id_link` = 0000000001;
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

-- ---------------------------------------------------------------------
-- Table `tagente_datos`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_datos MODIFY `datos` double(22,5);
ALTER TABLE `tagente_datos` DROP INDEX `data_index1`, ADD INDEX `data_index1` (`id_agente_modulo`, `utimestamp`);

-- ---------------------------------------------------------------------
-- Table `tagente_datos_string`
-- ---------------------------------------------------------------------
ALTER TABLE `tagente_datos_string` MODIFY COLUMN `datos` mediumtext NOT NULL, DROP INDEX `data_string_index_1`, ADD INDEX `data_string_index_1` (`id_agente_modulo`, `utimestamp`);

-- ---------------------------------------------------------------------
-- Table `tagente_datos_inc`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_datos_inc MODIFY `datos` double(22,5);

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
	MODIFY COLUMN `alias` varchar(600) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
	MODIFY COLUMN `alias_as_name` tinyint(2) NOT NULL DEFAULT '0';

-- ---------------------------------------------------------------------
-- Table `tservice`
-- ---------------------------------------------------------------------
ALTER TABLE `tservice` ADD COLUMN `quiet` tinyint(1) NOT NULL default 0;
ALTER TABLE `tservice` ADD COLUMN `cps` int NOT NULL default 0;
ALTER TABLE `tservice` ADD COLUMN `cascade_protection` tinyint(1) NOT NULL default 0;
ALTER TABLE `tservice` ADD COLUMN `evaluate_sla` int(1) NOT NULL default 0;
ALTER TABLE `tservice` ADD COLUMN `is_favourite` tinyint(1) NOT NULL default 0;
UPDATE tservice SET `is_favourite` = 1 WHERE `name` REGEXP '^[_|.|\[|\(]';

-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout ADD `background_color` varchar(50) NOT NULL default '#FFF';
ALTER TABLE tlayout ADD `is_favourite` int(1) NOT NULL DEFAULT 0;
ALTER TABLE tlayout MODIFY `name` varchar(600) NOT NULL;

UPDATE tlayout SET is_favourite = 1 WHERE name REGEXP '^&#40;' OR name REGEXP '^\\[';

ALTER TABLE `tlayout` MODIFY COLUMN `is_favourite` int(10) unsigned NOT NULL DEFAULT '0';

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
ALTER TABLE twidget_dashboard MODIFY options LONGTEXT NOT NULL default "";

-- ---------------------------------------------------------------------
-- Table `trecon_task`
-- ---------------------------------------------------------------------
ALTER TABLE trecon_task ADD `alias_as_name` int(2) unsigned default '0';
ALTER TABLE trecon_task ADD `snmp_enabled` int(2) unsigned default '0';
ALTER TABLE trecon_task ADD `vlan_enabled` int(2) unsigned default '0';
ALTER TABLE trecon_task ADD `wmi_enabled` tinyint(1) unsigned DEFAULT '0';
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
	MODIFY COLUMN `summary` text NULL;

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

-- ---------------------------------------------------------------------
-- Table `tevent_response`
-- ---------------------------------------------------------------------
ALTER TABLE tevent_response ADD COLUMN server_to_exec int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE tevent_response ADD COLUMN command_timeout int(5) unsigned NOT NULL DEFAULT 90;

-- ---------------------------------------------------------------------
-- Table `tmodule`
-- ---------------------------------------------------------------------

INSERT INTO tmodule VALUES (8, 'Wux module');

-- ---------------------------------------------------------------------
-- Table `ttipo_modulo`
-- ---------------------------------------------------------------------

INSERT INTO `ttipo_modulo` VALUES
(25,'web_analysis', 8, 'Web analysis data', 'module-wux.png'),
(34,'remote_cmd', 10, 'Remote execution, numeric data', 'mod_remote_cmd.png'),
(35,'remote_cmd_proc', 10, 'Remote execution, boolean data', 'mod_remote_cmd_proc.png'),
(36,'remote_cmd_string', 10, 'Remote execution, alphanumeric data', 'mod_remote_cmd_string.png'),
(37,'remote_cmd_inc', 10, 'Remote execution, incremental data', 'mod_remote_cmd_inc.png');

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

ALTER TABLE `trecon_task` ADD COLUMN `snmp_version` varchar(5) NOT NULL default '1';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_auth_user` varchar(255) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_auth_pass` varchar(255) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_auth_method` varchar(25) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_privacy_method` varchar(25) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_privacy_pass` varchar(255) NOT NULL default '';
ALTER TABLE `trecon_task` ADD COLUMN `snmp_security_level` varchar(25) NOT NULL default '';

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
ALTER TABLE `tevento` ADD COLUMN `data` double(22,5) default NULL;

ALTER TABLE `tevento` ADD COLUMN `module_status` int(4) NOT NULL default '0';

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
ALTER TABLE tagent_custom_fields ADD COLUMN `combo_values` VARCHAR(255) DEFAULT '';

-- ----------------------------------------------------------------------
-- Add column in table `tnetflow_filter`
-- ----------------------------------------------------------------------
ALTER TABLE `tnetflow_filter` DROP COLUMN `output`;
ALTER TABLE `tnetflow_filter` MODIFY COLUMN `router_ip` text NOT NULL;

-- ----------------------------------------------------------------------
-- Update table `tuser_task`
-- ----------------------------------------------------------------------
UPDATE tuser_task set parameters = 'a:5:{i:0;a:6:{s:11:\"description\";s:28:\"Report pending to be created\";s:5:\"table\";s:7:\"treport\";s:8:\"field_id\";s:9:\"id_report\";s:10:\"field_name\";s:4:\"name\";s:4:\"type\";s:3:\"int\";s:9:\"acl_group\";s:8:\"id_group\";}i:1;a:2:{s:11:\"description\";s:46:\"Send to email addresses (separated by a comma)\";s:4:\"type\";s:4:\"text\";}i:2;a:2:{s:11:\"description\";s:7:\"Subject\";s:8:\"optional\";i:1;}i:3;a:3:{s:11:\"description\";s:7:\"Message\";s:4:\"type\";s:4:\"text\";s:8:\"optional\";i:1;}i:4;a:2:{s:11:\"description\";s:11:\"Report Type\";s:4:\"type\";s:11:\"report_type\";}}' where function_name = "cron_task_generate_report";

-- ----------------------------------------------------------------------
-- ADD message in table 'tnews'
-- ----------------------------------------------------------------------

INSERT INTO `tnews` (`id_news`, `author`, `subject`, `text`, `timestamp`) VALUES (NULL,'admin','Welcome&#x20;to&#x20;Pandora&#x20;FMS&#x20;Console', '&amp;lt;p&#x20;style=&quot;text-align:&#x20;center;&#x20;font-size:&#x20;13px;&quot;&amp;gt;Hello,&#x20;congratulations,&#x20;if&#x20;you&apos;ve&#x20;arrived&#x20;here&#x20;you&#x20;already&#x20;have&#x20;an&#x20;operational&#x20;monitoring&#x20;console.&#x20;Remember&#x20;that&#x20;our&#x20;forums&#x20;and&#x20;online&#x20;documentation&#x20;are&#x20;available&#x20;24x7&#x20;to&#x20;get&#x20;you&#x20;out&#x20;of&#x20;any&#x20;trouble.&#x20;You&#x20;can&#x20;replace&#x20;this&#x20;message&#x20;with&#x20;a&#x20;personalized&#x20;one&#x20;at&#x20;Admin&#x20;tools&#x20;-&amp;amp;gt;&#x20;Site&#x20;news.&amp;lt;/p&amp;gt;&#x20;',NOW());

-- ----------------------------------------------------------------------
-- Alter table `talert_templates`
-- ----------------------------------------------------------------------

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
  `uploaded_by` VARCHAR(100) DEFAULT '',
  `uploaded` bigint(20) NOT NULL DEFAULT 0 COMMENT "When it was uploaded",
  `last_err` text,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_os`) REFERENCES `tconfig_os`(`id_os`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
=========
-- ---------------------------------------------------------------------
-- Table `trecon_script`
-- ---------------------------------------------------------------------
ALTER TABLE `trecon_script` ADD COLUMN `type` int(11) NOT NULL DEFAULT '0';

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
