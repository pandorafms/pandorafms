START TRANSACTION;

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

COMMIT;
