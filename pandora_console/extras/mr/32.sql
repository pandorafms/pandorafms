START TRANSACTION

ALTER TABLE `tdatabase` MODIFY `last_error` text;
ALTER TABLE `tdatabase` MODIFY `host` VARCHAR(255) DEFAULT '';
ALTER TABLE `tdatabase` ADD COLUMN `label` VARCHAR(255) DEFAULT '';
ALTER TABLE `tdatabase` MODIFY `os_user` VARCHAR(255) DEFAULT '';
ALTER TABLE `tdatabase` MODIFY `db_port` INT UNSIGNED NOT NULL DEFAULT 3306;
ALTER TABLE `tdatabase` MODIFY `os_port` INT UNSIGNED NOT NULL DEFAULT 22;
ALTER TABLE `tdatabase` ADD COLUMN `ssh_key` TEXT;
ALTER TABLE `tdatabase` ADD COLUMN `ssh_pubkey` TEXT;

UPDATE `tdatabase` set `label`=`host`;

COMMIT