START TRANSACTION;

ALTER TABLE `tmetaconsole_setup` ADD COLUMN `unified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tlayout` ADD COLUMN `auto_adjust` INTEGER UNSIGNED NOT NULL default 0;
ALTER TABLE `tlayout_data` ADD COLUMN `title` TEXT default '';

CREATE TABLE IF NOT EXISTS `talert_execution_queue` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_alert_template_module` int(10) unsigned NOT NULL,
	`alert_mode` tinyint(1) NOT NULL,
	`data` mediumtext NOT NULL,
	`extra_macros` text,
	`utimestamp` bigint(20) NOT NULL default '0',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tsync_queue` (
	`id` serial,
	`sql` MEDIUMTEXT,
	`target` bigint(20) unsigned NOT NULL,
	`utimestamp` bigint(20) default '0',
	`operation` text,
	`table` text,
	`error` MEDIUMTEXT,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE `tlink` SET `link` = 'https://pandorafms.com/manual/' WHERE `id_link` = 0000000001;

UPDATE `tuser_task_scheduled` SET
    `args`= REPLACE(`args`, 's:15:"first_execution"', 'i:8;s:3:"PDF";s:15:"first_execution"'),
    `args` = REPLACE (`args`, 'a:8', 'a:10'),
	`args` = REPLACE (`args`, 'i:6', 'i:7'),
	`args` = REPLACE (`args`, 'i:5', 'i:6'),
	`args` = REPLACE (`args`, 'i:4', 'i:5'),
	`args` = REPLACE (`args`, 'i:3', 'i:4'),
	`args` = REPLACE (`args`, 'i:2', 'i:2;s:0:"";i:3')
    WHERE `id_user_task` IN (SELECT id from tuser_task WHERE function_name = 'cron_task_generate_report_by_template');

UPDATE `tconfig` SET `value` = 0 WHERE `token` = 'centralized_management';

DELETE ta FROM `tagente` ta LEFT JOIN `tgrupo` tg on ta.`id_grupo` = tg.`id_grupo` WHERE tg.`id_grupo` IS NULL;

COMMIT;
