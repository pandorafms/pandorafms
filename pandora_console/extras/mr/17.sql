START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tdatabase` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`host` varchar(100) default '',
	`os_port` int(4) unsigned default '22',
	`os_user` varchar(100) default '',
	`db_port` int(4) unsigned default '3306',
	`status` tinyint(1) unsigned default '0',
	`action` tinyint(1) unsigned default '0',
	`last_error` varchar(255) default '',
	PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ;

COMMIT;