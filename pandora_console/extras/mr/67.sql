START TRANSACTION;

-- Delete table tagent_access
DROP TABLE tagent_access;

ALTER TABLE treport_content ADD check_unknowns_graph tinyint DEFAULT 0 NULL;

DELETE FROM `tconfig` WHERE `token` LIKE 'translate_string_extension_installed';

CREATE TABLE IF NOT EXISTS `textension_translate_string` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lang` VARCHAR(10) NOT NULL ,
  `string` TEXT ,
  `translation` TEXT ,
  PRIMARY KEY (`id`),
  KEY `lang_index` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

DELETE FROM `tconfig` WHERE `token` LIKE 'files_repo_installed';

CREATE TABLE IF NOT EXISTS `tfiles_repo` (
	`id` int(5) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`description` varchar(500) NULL default '',
	`hash` varchar(8) NULL default '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tfiles_repo_group` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_file` int(5) unsigned NOT NULL,
	`id_group` int(4) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_file`) REFERENCES tfiles_repo(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

COMMIT;
