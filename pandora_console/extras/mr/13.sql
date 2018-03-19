START TRANSACTION;

	SET @st_oum720 = (SELECT IF(
		(SELECT value FROM tconfig WHERE token like 'short_module_graph_data') = 1, 
		"UPDATE tconfig SET value = 2 WHERE token LIKE 'short_module_graph_data'", 
		"UPDATE tconfig SET value = '' WHERE token LIKE 'short_module_graph_data'"
	));

	PREPARE pr_oum720 FROM @st_oum720;
	EXECUTE pr_oum720;
	DEALLOCATE PREPARE pr_oum720;
	
	INSERT INTO `tconfig_os` (`id_os`, `name`, `description`, `icon_name`) VALUES (100, 'Cluster', 'Cluster agent', 'so_cluster.png');
	
	UPDATE `tagente` SET `id_os` = 100 WHERE `id_os` = 21 and (select `id_os` from `tconfig_os` WHERE `id_os` = 21 and `name` = 'Cluster');

	DELETE FROM `tconfig_os` where `id_os` = 21 and `name` = 'Cluster';

	CREATE TABLE IF NOT EXISTS `tprovisioning`(
	    `id` int unsigned NOT NULL auto_increment,
	    `name` varchar(100) NOT NULL,
		`description` TEXT default '',
		`order` int(11) NOT NULL default 0,
		`config` TEXT default '',
			PRIMARY KEY (`id`)
	) engine=InnoDB DEFAULT CHARSET=utf8;
	
	CREATE TABLE IF NOT EXISTS `tprovisioning_rules`(
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

	create table IF NOT EXISTS `tmigration_module_queue`(
		`id` int unsigned not null auto_increment,
		`id_migration` int unsigned not null,
		`id_source_agentmodule` int unsigned not null,
		`id_target_agentmodule` int unsigned not null,
		`last_replication_timestamp` bigint(20) NOT NULL default 0,
		PRIMARY KEY(`id`),
		FOREIGN KEY(`id_migration`) REFERENCES tmigration_queue(`id`)
			ON DELETE CASCADE ON UPDATE CASCADE
	) engine=InnoDB DEFAULT CHARSET=utf8;

COMMIT;
