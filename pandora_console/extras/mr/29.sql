START TRANSACTION;

DELETE FROM `ttipo_modulo` WHERE `nombre` LIKE 'log4x';

CREATE TABLE IF NOT EXISTS `tcredential_store` (
	`identifier` varchar(100) NOT NULL,
	`id_group` mediumint(4) unsigned NOT NULL DEFAULT 0,
	`product` enum('CUSTOM', 'AWS', 'AZURE', 'GOOGLE') default 'CUSTOM',
	`username` text,
	`password` text,
	`extra_1` text,
	`extra_2` text,
	PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


COMMIT;
