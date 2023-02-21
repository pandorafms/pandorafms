START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tconsole` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_console` BIGINT NOT NULL DEFAULT 0,
  `description` TEXT,
  `version` TINYTEXT,
  `last_execution` INT UNSIGNED NOT NULL DEFAULT 0,
  `console_type` TINYINT NOT NULL DEFAULT 0,
  `timezone` TINYTEXT,
  `public_url` TEXT,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tuser_task_scheduled` ADD COLUMN `id_console` BIGINT NOT NULL DEFAULT 0;

COMMIT;
