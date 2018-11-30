START TRANSACTION;

ALTER TABLE `talert_commands` ADD COLUMN `id_group` mediumint(8) unsigned NULL default 0;

ALTER TABLE `tusuario` DROP COLUMN `flash_chart`;

ALTER TABLE `tusuario` ADD COLUMN `default_custom_view` int(10) unsigned NULL default '0';

ALTER TABLE tlayout_template MODIFY `name` varchar(600) NOT NULL;

CREATE TABLE IF NOT EXISTS `tagent_custom_fields_filter` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(600) NOT NULL,
	`id_group` int(10) unsigned default '0',
	`id_custom_field` varchar(600) default '',
	`id_custom_fields_data` varchar(600) default '',
	`id_status` varchar(600) default '',
	`module_search` varchar(600) default '',
	PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

COMMIT;
