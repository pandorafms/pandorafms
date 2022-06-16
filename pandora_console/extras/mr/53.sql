START TRANSACTION;

ALTER TABLE `tipam_vlan` ADD COLUMN `custom_id` bigint(20) unsigned DEFAULT NULL;
ALTER TABLE `tuser_task_scheduled`ADD COLUMN `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1;

ALTER TABLE `tevent_filter` ADD COLUMN `custom_data` VARCHAR(500) DEFAULT '';
ALTER TABLE `tevent_filter` ADD COLUMN `custom_data_filter_type` TINYINT UNSIGNED DEFAULT 0;

ALTER TABLE tagente MODIFY alias varchar(600) NOT NULL DEFAULT '';
ALTER TABLE tagente MODIFY nombre varchar(600) NOT NULL DEFAULT '';

UPDATE `tuser_task` SET `parameters` = 'a:3:{i:0;a:2:{s:11:"description";s:11:"Description";s:4:"type";s:4:"text";}i:1;a:3:{s:11:"description";s:20:"Save to disk in path";s:4:"type";s:6:"string";s:13:"default_value";s:21:"_%_ATTACHMENT_PATH_%_";}i:2;a:3:{s:11:"description";s:14:"Active backups";s:4:"type";s:6:"number";s:13:"default_value";i:3;}}' WHERE `function_name` = 'cron_task_do_backup';

CREATE TABLE IF NOT EXISTS `tbackup` (
  `id` SERIAL,
  `utimestamp` BIGINT DEFAULT 0,
  `filename` VARCHAR(512) DEFAULT '',
  `id_user` VARCHAR(60) DEFAULT '',
  `description` MEDIUMTEXT,
  `pid` INT UNSIGNED DEFAULT 0,
  `filepath` VARCHAR(512) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `treport_content` ADD COLUMN `macros_definition` TEXT;
ALTER TABLE `treport_content` ADD COLUMN `render_definition` TEXT;
ALTER TABLE `treport_content_template` ADD COLUMN `macros_definition` TEXT;
ALTER TABLE `treport_content_template` ADD COLUMN `render_definition` TEXT;

DROP TABLE IF EXISTS `tupdate_journal`;
DROP TABLE IF EXISTS `tupdate`;
DROP TABLE IF EXISTS `tupdate_package`;

CREATE TABLE `tupdate_journal` (
  `id` SERIAL,
  `utimestamp` BIGINT DEFAULT 0,
  `version` VARCHAR(25) DEFAULT '',
  `type` VARCHAR(25) DEFAULT '',
  `origin` VARCHAR(25) DEFAULT '',
  `id_user` VARCHAR(250) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

COMMIT;
