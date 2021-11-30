START TRANSACTION;

ALTER TABLE tagente_modulo MODIFY debug_content TEXT;

CREATE TABLE IF NOT EXISTS `talert_calendar` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL default '',
	`id_group` INT(10) NOT NULL DEFAULT 0,
	`description` text,
	PRIMARY KEY (`id`),
	UNIQUE (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `talert_calendar` VALUES (1, 'Default', 0, 'Default calendar');

ALTER TABLE `talert_special_days` ADD COLUMN `id_calendar` int(10) unsigned NOT NULL DEFAULT 1;
ALTER TABLE `talert_special_days` ADD COLUMN `day_code` tinyint(2) unsigned NOT NULL DEFAULT 0;

UPDATE `talert_special_days` set `day_code` = 1 WHERE `same_day` = 'monday';
UPDATE `talert_special_days` set `day_code` = 2 WHERE `same_day` = 'tuesday';
UPDATE `talert_special_days` set `day_code` = 3 WHERE `same_day` = 'wednesday';
UPDATE `talert_special_days` set `day_code` = 4 WHERE `same_day` = 'thursday';
UPDATE `talert_special_days` set `day_code` = 5 WHERE `same_day` = 'friday';
UPDATE `talert_special_days` set `day_code` = 6 WHERE `same_day` = 'saturday';
UPDATE `talert_special_days` set `day_code` = 7 WHERE `same_day` = 'sunday';

ALTER TABLE `talert_special_days` DROP COLUMN `same_day`;
ALTER TABLE `talert_special_days` ADD FOREIGN KEY (`id_calendar`) REFERENCES `talert_calendar`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tagent_repository` ADD COLUMN `deployment_timeout` INT UNSIGNED DEFAULT 600 AFTER `path`;

UPDATE `tconfig` c1 JOIN (select count(*) as n FROM `tconfig` c2 WHERE (c2.`token` = "node_metaconsole" AND c2.`value` = 1) OR (c2.`token` = "centralized_management" AND c2.`value` = 1) ) v SET c1. `value` = 0 WHERE c1.token = "autocreate_remote_users" AND v.n = 2;

COMMIT;
