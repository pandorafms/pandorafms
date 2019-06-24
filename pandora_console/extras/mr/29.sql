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


INSERT INTO `tcredential_store` (`identifier`, `id_group`, `product`, `username`, `password`) VALUES ("imported_aws_account", 0, "AWS", (SELECT `value` FROM `tconfig` WHERE `token` = "aws_access_key_id" LIMIT 1), (SELECT `value` FROM `tconfig` WHERE `token` = "aws_secret_access_key" LIMIT 1));


COMMIT;
