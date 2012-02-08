-- -----------------------------------------------------
-- Table `tnetflow_filter`
-- -----------------------------------------------------

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

-- -----------------------------------------------------
-- Table `tnetflow_report`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tnetflow_report` (
  `id_report` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_name` varchar(150) NOT NULL default '',
  `description` TEXT NOT NULL,
  `id_group` int(10),
PRIMARY KEY(`id_report`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tnetflow_report_content`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tnetflow_report_content` (
   	`id_rc` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_report` INTEGER UNSIGNED NOT NULL default 0,
    `id_filter` INTEGER UNSIGNED NOT NULL default 0,
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

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

ALTER TABLE `tusuario` ADD COLUMN `disabled` int(4) NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `shortcut` tinyint(1) DEFAULT 0;

-- -----------------------------------------------------
-- Table `tincidencia`
-- -----------------------------------------------------

ALTER TABLE `tincidencia` ADD COLUMN `id_agent` int(10) unsigned NULL default 0;

-- -----------------------------------------------------
-- Table `tagente`
-- -----------------------------------------------------

ALTER TABLE `tagente` ADD COLUMN `url_address` mediumtext NULL default '';

-- -----------------------------------------------------
-- Table `talert_special_days`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `talert_special_days` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`date` date NOT NULL DEFAULT '0000-00-00',
	`same_day` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL DEFAULT 'sunday',
	`description` text,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_templates`
-- -----------------------------------------------------

ALTER TABLE `talert_templates` ADD COLUMN `special_day` tinyint(1) DEFAULT '0';

-- -----------------------------------------------------
-- Table `tplanned_downtime_agents`
-- -----------------------------------------------------
DELETE FROM tplanned_downtime_agents
WHERE id_downtime NOT IN (SELECT id FROM tplanned_downtime);

ALTER TABLE tplanned_downtime_agents
ADD FOREIGN KEY(`id_downtime`) REFERENCES tplanned_downtime(`id`)
ON DELETE CASCADE;

-- -----------------------------------------------------
-- Table `tevento`
-- -----------------------------------------------------

ALTER TABLE `tevento` ADD COLUMN (`source` tinytext NOT NULL DEFAULT '',
`id_extra` tinytext NOT NULL DEFAULT '');

-- -----------------------------------------------------
-- Table `talert_snmp`
-- -----------------------------------------------------
ALTER TABLE `talert_snmp` ADD COLUMN (`_snmp_f1_` text DEFAULT '', `_snmp_f2_` text DEFAULT '', `_snmp_f3_` text DEFAULT '',
`_snmp_f4_` text DEFAULT '', `_snmp_f5_` text DEFAULT '', `_snmp_f6_` text DEFAULT '', `trap_type` int(11) NOT NULL default '-1',
`single_value` varchar(255) DEFAULT '');

-- -----------------------------------------------------
-- Table `tagente_modulo`
-- -----------------------------------------------------
ALTER TABLE `tagente_modulo` ADD COLUMN `module_ff_interval` int(4) unsigned default '0';
ALTER TABLE `tagente_modulo` CHANGE COLUMN `post_process` `post_process` double(18,5) default NULL;

-- -----------------------------------------------------
-- Table `tnetwork_component`
-- -----------------------------------------------------
ALTER TABLE `tnetwork_component` CHANGE COLUMN `post_process` `post_process` double(18,5) default NULL;

-- -----------------------------------------------------
-- Table `tgraph_source` Alter table to allow negative values in weight
-- -----------------------------------------------------
ALTER TABLE tgraph_source MODIFY weight FLOAT(5,3) NOT NULL DEFAULT '0.000';

-- -----------------------------------------------------
-- Table `tevent_filter`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tevent_filter` (
  `id_filter`  int(10) unsigned NOT NULL auto_increment,
  `id_name` varchar(600) NOT NULL,
  `id_group` int(10) NOT NULL default 0,
  `event_type` text NOT NULL default '',
  `severity` int(10) NOT NULL default -1,
  `status` int(10) NOT NULL default -1,
  `search` TEXT default '',
  `text_agent` TEXT default '', 
  `pagination` int(10) NOT NULL default 25,
  `event_view_hr` int(10) NOT NULL default 8,
  `id_user_ack` TEXT,
  `group_rep` int(10) NOT NULL default 0,
  `tag` varchar(600) NOT NULL default '',
  `filter_only_alert` int(10) NOT NULL default -1, 
PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
