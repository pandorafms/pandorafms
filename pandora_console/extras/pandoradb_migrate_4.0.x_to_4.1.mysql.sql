-- -----------------------------------------------------
-- Table `tnetflow_filter`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tnetflow_filter` (
  `id_sg`  int(10) unsigned NOT NULL auto_increment,
  `id_name` varchar(60) NOT NULL default '0',
  `group` varchar(60),
  `ip_dst` varchar(100),
  `ip_src` varchar(100),
  `dst_port` varchar(100),
  `src_port` varchar(100),
  `aggregate` varchar(60),
  `show_packets` int(1) default '0',
  `show_bytes` int(1) default '0',
  `show_bps` int(1) default '0',
  `show_bpp` int(1) default '0',
PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tnetflow_report`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tnetflow_report` (
  `id_report` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_name` varchar(150) NOT NULL default '',
  `description` TEXT NOT NULL,
  `group` varchar(60),
PRIMARY KEY(`id_report`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tnetflow_options`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tnetflow_options` (
  `id_option`  int(10) unsigned NOT NULL auto_increment,
  `id_name` varchar(60) NOT NULL default '0',
  `description` TEXT NOT NULL default '',
  `path` varchar(200),
  `port` varchar(100),
PRIMARY KEY  (`id_option`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tnetflow_report_content`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tnetflow_report_content` (
   	`id_rc` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_report` INTEGER UNSIGNED NOT NULL default 0,
        `id_filter`  varchar(60),
	`date` bigint(20) NOT NULL default '0',
	`period` int(11) NOT NULL default 0,
	`max` int (11) NOT NULL default 0,
	`show_graph` varchar(60),
	PRIMARY KEY(`id_rc`),
	FOREIGN KEY (`id_report`) REFERENCES tnetflow_report(`id_report`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

ALTER TABLE `tusuario` ADD COLUMN `disabled` int(4) NOT NULL DEFAULT 0;

