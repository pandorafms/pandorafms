START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tsync_queue` (
	`id` serial,
	`sql` MEDIUMTEXT,
	`target` bigint(20) unsigned NOT NULL,
	`utimestamp` bigint(20) default '0',
	`error` MEDIUMTEXT,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


COMMIT;