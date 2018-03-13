START TRANSACTION;

	SET @st_oum720 = (SELECT IF(
		(SELECT value FROM tconfig WHERE token like 'short_module_graph_data') = 1, 
		"UPDATE tconfig SET value = 2 WHERE token LIKE 'short_module_graph_data'", 
		"UPDATE tconfig SET value = '' WHERE token LIKE 'short_module_graph_data'"
	));

	PREPARE pr_oum720 FROM @st_oum720;
	EXECUTE pr_oum720;
	DEALLOCATE PREPARE pr_oum720;
	
	INSERT INTO `tconfig_os` (`id_os`, `name`, `description`, `icon_name`) VALUES (21, 'Cluster', 'Cluster agent', 'so_cluster.png');

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
COMMIT;
