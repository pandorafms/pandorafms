START TRANSACTION;

ALTER TABLE `tdatabase` ADD COLUMN `ssh_status` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `db_status` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `replication_status` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `replication_delay` BIGINT DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `master` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `utimestamp` BIGINT DEFAULT 0;
ALTER TABLE `tdatabase` ADD COLUMN `mysql_version` VARCHAR(10) DEFAULT '';
ALTER TABLE `tdatabase` ADD COLUMN `pandora_version` VARCHAR(10) DEFAULT '';

COMMIT;
