START TRANSACTION;

ALTER TABLE tcontainer_item ADD COLUMN type_graph tinyint(1) unsigned default '0';

ALTER TABLE tlayout_data ADD COLUMN `clock_animation` varchar(60) NOT NULL default "analogic_1";
ALTER TABLE tlayout_data ADD COLUMN `time_format` varchar(60) NOT NULL default "time";
ALTER TABLE tlayout_data ADD COLUMN `timezone` varchar(60) NOT NULL default "Europe/Madrid";

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
		PRIMARY KEY (`id`),
		FOREIGN KEY (`id_agent`) REFERENCES tagente(`id_agente`)
			ON UPDATE CASCADE
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
		FOREIGN KEY (`id_agent`) REFERENCES tagente(`id_agente`)
			ON UPDATE CASCADE,
		FOREIGN KEY (`id_cluster`) REFERENCES tcluster(`id`)
			ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

COMMIT;