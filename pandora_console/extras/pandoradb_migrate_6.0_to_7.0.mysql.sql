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
	`dynamic_next` bigint(20) NOT NULL default '0',
	`dynamic_two_tailed` tinyint(1) unsigned default '0',
	`prediction_sample_window` int(10) default 0,
	`prediction_samples` int(4) default 0,
	`prediction_threshold` int(4) default 0,
	PRIMARY KEY  (`id`),
	FOREIGN KEY (`id_network_component_group`) REFERENCES tnetwork_component_group(`id_sg`)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
	`dynamic_next` bigint(20) NOT NULL default '0',
	`dynamic_two_tailed` tinyint(1) unsigned default '0',
	`prediction_sample_window` int(10) default 0,
	`prediction_samples` int(4) default 0,
	`prediction_threshold` int(4) default 0,
	PRIMARY KEY  (`id`),
	KEY `main_idx` (`id_policy`),
	UNIQUE (`id_policy`, `name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

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
	`transactional_agent` tinyint(1) NOT NULL default '0',
	`alias` varchar(600) BINARY NOT NULL default '',
	PRIMARY KEY  (`id_agente`),
	KEY `nombre` (`nombre`(255)),
	KEY `direccion` (`direccion`),
	KEY `disabled` (`disabled`),
	KEY `id_grupo` (`id_grupo`),
	FOREIGN KEY (`id_tmetaconsole_setup`) REFERENCES tmetaconsole_setup(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

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

-- ----------------------------------------------------------------------
-- Table `tagente_estado`
-- ----------------------------------------------------------------------
ALTER TABLE tagente_estado MODIFY `status_changes` tinyint(4) unsigned default 0;
ALTER TABLE tagente_estado CHANGE `last_known_status` `known_status` tinyint(4) default 0;
ALTER TABLE tagente_estado ADD COLUMN `last_known_status` tinyint(4) default 0;

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

-- ---------------------------------------------------------------------
-- Table `talert_commands`
-- ---------------------------------------------------------------------
UPDATE `talert_commands` SET `fields_descriptions` = '[\"Integria&#x20;IMS&#x20;API&#x20;path\",\"Integria&#x20;IMS&#x20;API&#x20;pass\",\"Integria&#x20;IMS&#x20;user\",\"Integria&#x20;IMS&#x20;user&#x20;pass\",\"Ticket&#x20;title\",\"Ticket&#x20;group&#x20;ID\",\"Ticket&#x20;priority\",\"Email&#x20;copy\",\"Ticket&#x20;owner\",\"Ticket&#x20;description\"]', `fields_values` = '[\"\",\"\",\"\",\"\",\"\",\"\",\"10,Maintenance;0,Informative;1,Low;2,Medium;3,Serious;4,Very&#x20;Serious\",\"\",\"\",\"\"]' WHERE `id` = 11 AND `name` = 'Integria&#x20;IMS&#x20;Ticket';

-- ---------------------------------------------------------------------
-- Table `tmap`
-- ---------------------------------------------------------------------
ALTER TABLE tmap MODIFY `id_user` varchar(128);

-- ---------------------------------------------------------------------
-- Table `titem`
-- ---------------------------------------------------------------------
ALTER TABLE titem MODIFY `source_data` int(10) unsigned;

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO `tconfig` (`token`, `value`) VALUES ('big_operation_step_datos_purge', '100');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('small_operation_step_datos_purge', '1000');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('days_autodisable_deletion', '30');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('MR', 1);
UPDATE tconfig SET value = 'https://licensing.artica.es/pandoraupdate7/server.php' WHERE token='url_update_manager';
DELETE FROM `tconfig` WHERE `token` = 'current_package_enterprise';
INSERT INTO `tconfig` (`token`, `value`) VALUES ('current_package_enterprise', '704');

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
-- ---------------------------------------------------------------------
-- Table `tusuario`
-- ---------------------------------------------------------------------

ALTER TABLE tusuario ADD COLUMN `id_filter` int(10) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE tusuario ADD CONSTRAINT `fk_id_filter` FOREIGN KEY (`id_filter`) REFERENCES tevent_filter(`id_filter`) ON DELETE SET NULL;
ALTER TABLE tusuario ADD COLUMN `session_time` int(10) signed NOT NULL default '0';

-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_modulo ADD COLUMN `dynamic_next` bigint(20) NOT NULL default '0';
ALTER TABLE tagente_modulo ADD COLUMN `dynamic_two_tailed` tinyint(1) unsigned default '0';
ALTER TABLE tagente_modulo ADD COLUMN `parent_module_id` int(10) unsigned NOT NULL;

-- ---------------------------------------------------------------------
-- Table `tagente_datos`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_datos MODIFY `datos` double(22,5);

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

-- ---------------------------------------------------------------------
-- Table `tagente`
-- ---------------------------------------------------------------------
ALTER TABLE tagente ADD `transactional_agent` tinyint(1) NOT NULL default 0;
ALTER TABLE tagente ADD `remote` tinyint(1) NOT NULL default 0;
ALTER TABLE tagente ADD `cascade_protection_module` int(10) unsigned default '0';
ALTER TABLE tagente ADD COLUMN (alias varchar(600) not null default '');
ALTER TABLE tagente ADD `alias_as_name` int(2) unsigned default '0';

UPDATE tagente SET tagente.alias = tagente.nombre;
-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout ADD `background_color` varchar(50) NOT NULL default '#FFF';

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
ALTER TABLE tlayout_data ADD `type_graph` varchar(50) NOT NULL default 'area';
ALTER TABLE tlayout_data ADD `label_position` varchar(50) NOT NULL default 'down';

-- ---------------------------------------------------------------------
-- Table `tagent_custom_fields`
-- ---------------------------------------------------------------------
INSERT INTO `tagent_custom_fields` (`name`) VALUES ('eHorusID');

-- ---------------------------------------------------------------------
-- Table `tagente_modulo` Fixed problems with blank space 
-- in cron interval and problems with process data from pandora server
-- ---------------------------------------------------------------------
UPDATE tagente_modulo SET cron_interval = '' WHERE cron_interval LIKE '%    %';

-- ---------------------------------------------------------------------
-- Table `tgraph`
-- ---------------------------------------------------------------------
ALTER TABLE tgraph ADD COLUMN `percentil` int(4) unsigned default '0';

-- ---------------------------------------------------------------------
-- Table `tnetflow_filter`
-- ---------------------------------------------------------------------
ALTER TABLE tnetflow_filter ADD COLUMN `router_ip` TEXT NOT NULL DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `treport_custom_sql`
-- ---------------------------------------------------------------------
UPDATE treport_custom_sql SET `sql` = 'select&#x20;direccion,&#x20;alias,&#x20;comentarios,&#x20;&#40;select&#x20;nombre&#x20;from&#x20;tgrupo&#x20;where&#x20;tgrupo.id_grupo&#x20;=&#x20;tagente.id_grupo&#41;&#x20;as&#x20;`group`&#x20;from&#x20;tagente;' 
	WHERE id = 1;
UPDATE treport_custom_sql SET `sql` = 'select&#x20;&#40;select&#x20;tagente.alias&#x20;from&#x20;tagente&#x20;where&#x20;tagente.id_agente&#x20;=&#x20;tagente_modulo.id_agente&#41;&#x20;as&#x20;agent_nombre,&#x20;nombre&#x20;,&#x20;&#40;select&#x20;tmodule_group.name&#x20;from&#x20;tmodule_group&#x20;where&#x20;tmodule_group.id_mg&#x20;=&#x20;tagente_modulo.id_module_group&#41;&#x20;as&#x20;module_group,&#x20;module_interval&#x20;from&#x20;tagente_modulo&#x20;where&#x20;delete_pending&#x20;=&#x20;0&#x20;order&#x20;by&#x20;nombre;' 
	WHERE id = 2;
UPDATE treport_custom_sql SET `sql` = 'select&#x20;t1.alias&#x20;as&#x20;agent_name,&#x20;t2.nombre&#x20;as&#x20;module_name,&#x20;&#40;select&#x20;talert_templates.name&#x20;from&#x20;talert_templates&#x20;where&#x20;talert_templates.id&#x20;=&#x20;t3.id_alert_template&#41;&#x20;as&#x20;template,&#x20;&#40;select&#x20;group_concat&#40;t02.name&#41;&#x20;from&#x20;talert_template_module_actions&#x20;as&#x20;t01&#x20;inner&#x20;join&#x20;talert_actions&#x20;as&#x20;t02&#x20;on&#x20;t01.id_alert_action&#x20;=&#x20;t02.id&#x20;where&#x20;t01.id_alert_template_module&#x20;=&#x20;t3.id&#x20;group&#x20;by&#x20;t01.id_alert_template_module&#41;&#x20;as&#x20;actions&#x20;from&#x20;tagente&#x20;as&#x20;t1&#x20;inner&#x20;join&#x20;tagente_modulo&#x20;as&#x20;t2&#x20;on&#x20;t1.id_agente&#x20;=&#x20;t2.id_agente&#x20;inner&#x20;join&#x20;talert_template_modules&#x20;as&#x20;t3&#x20;on&#x20;t2.id_agente_modulo&#x20;=&#x20;t3.id_agent_module&#x20;order&#x20;by&#x20;agent_name,&#x20;module_name;' 
	WHERE id = 3;

-- ---------------------------------------------------------------------
-- Table `tmodule_relationship`
-- ---------------------------------------------------------------------
ALTER TABLE tmodule_relationship ADD COLUMN `id_server` varchar(100) NOT NULL DEFAULT '';

-- Table `tlocal_component`
-- ---------------------------------------------------------------------
ALTER TABLE tlocal_component ADD COLUMN `dynamic_next` bigint(20) NOT NULL default '0';
ALTER TABLE tlocal_component ADD COLUMN `dynamic_two_tailed` tinyint(1) unsigned default '0';

-- ---------------------------------------------------------------------
-- Table `tpolicy_module`
-- ---------------------------------------------------------------------
ALTER TABLE tpolicy_modules ADD COLUMN `dynamic_next` bigint(20) NOT NULL default '0';
ALTER TABLE tpolicy_modules ADD COLUMN `dynamic_two_tailed` tinyint(1) unsigned default '0';

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent`
-- ---------------------------------------------------------------------
ALTER TABLE tmetaconsole_agent ADD COLUMN `remote` tinyint(1) NOT NULL default '0';
ALTER TABLE tmetaconsole_agent ADD COLUMN `cascade_protection_module` int(10) default '0';
ALTER TABLE tmetaconsole_agent ADD COLUMN `transactional_agent` tinyint(1) NOT NULL default '0';
ALTER TABLE tmetaconsole_agent ADD COLUMN `alias` VARCHAR(600) not null DEFAULT '';
ALTER TABLE tmetaconsole_agent ADD COLUMN `alias_as_name` int(2) unsigned default '0';

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

-- ---------------------------------------------------------------------
-- Table `twidget` AND Table `twidget_dashboard`
-- ---------------------------------------------------------------------
UPDATE twidget_dashboard SET id_widget = (SELECT id FROM twidget WHERE unique_name = 'graph_module_histogram') WHERE id_widget = (SELECT id FROM twidget WHERE unique_name = 'graph_availability');
DELETE FROM twidget WHERE unique_name = 'graph_availability';

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
END;
//
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
