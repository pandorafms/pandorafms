START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tuser_task` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `function_name` VARCHAR(80) NOT NULL DEFAULT '',
  `parameters` TEXT ,
  `name` VARCHAR(60) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;


CREATE TABLE IF NOT EXISTS `tuser_task_scheduled` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0',
  `id_user_task` INT UNSIGNED NOT NULL DEFAULT 0,
  `args` TEXT,
  `scheduled` ENUM('no','hourly','daily','weekly','monthly','yearly','custom') DEFAULT 'no',
  `last_run` INT UNSIGNED DEFAULT 0,
  `custom_data` INT NULL DEFAULT 0,
  `flag_delete` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `id_grupo` INT UNSIGNED NOT NULL DEFAULT 0,
  `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tautoconfig` ADD COLUMN `type_execution` VARCHAR(100) NOT NULL DEFAULT 'start';
ALTER TABLE `tautoconfig` ADD COLUMN `type_periodicity` VARCHAR(100) NOT NULL DEFAULT 'weekly';
ALTER TABLE `tautoconfig` ADD COLUMN `monday` TINYINT DEFAULT 0;
ALTER TABLE `tautoconfig` ADD COLUMN `tuesday` TINYINT DEFAULT 0;
ALTER TABLE `tautoconfig` ADD COLUMN `wednesday` TINYINT DEFAULT 0;
ALTER TABLE `tautoconfig` ADD COLUMN `thursday` TINYINT DEFAULT 0;
ALTER TABLE `tautoconfig` ADD COLUMN `friday` TINYINT DEFAULT 0;
ALTER TABLE `tautoconfig` ADD COLUMN `saturday` TINYINT DEFAULT 0;
ALTER TABLE `tautoconfig` ADD COLUMN `sunday` TINYINT DEFAULT 0;
ALTER TABLE `tautoconfig` ADD COLUMN `periodically_day_from` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `tautoconfig` ADD COLUMN `periodically_time_from` time NULL DEFAULT NULL;
ALTER TABLE `tautoconfig` ADD COLUMN `executed` TINYINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `tusuario` DROP COLUMN `metaconsole_assigned_server`;

ALTER TABLE `tagente` ADD COLUMN `fixed_ip` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tmetaconsole_agent` ADD COLUMN `fixed_ip` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tipam_network` DROP FOREIGN KEY `tipam_network_ibfk_1`;
ALTER TABLE `tipam_network` MODIFY COLUMN `id_recon_task` INT UNSIGNED DEFAULT 0;
ALTER TABLE `tipam_network` ADD CONSTRAINT `tipam_network_ibfk_1` FOREIGN KEY (`id_recon_task`) REFERENCES trecon_task(`id_rt`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `tevent_filter` ADD COLUMN `search_secondary_groups` INT NOT NULL DEFAULT 0;

COMMIT;
