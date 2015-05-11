CREATE TABLE IF NOT EXISTS `tfiles_repo` (
	`id` int(5) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`description` varchar(500) NULL default '',
	`hash` varchar(8) NULL default '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tfiles_repo_group` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_file` int(5) unsigned NOT NULL,
	`id_group` int(4) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_file`) REFERENCES tfiles_repo(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;