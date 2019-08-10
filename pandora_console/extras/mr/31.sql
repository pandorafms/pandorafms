START TRANSACTION;

UPDATE `tconfig` SET `value` = 'mini_severity,evento,id_agente,estado,timestamp' WHERE `token` LIKE 'event_fields';

DELETE FROM `talert_commands` WHERE `id` = 11;

DELETE FROM `tconfig` WHERE `token` LIKE 'integria_enabled';
DELETE FROM `tconfig` WHERE `token` LIKE 'integria_api_password';
DELETE FROM `tconfig` WHERE `token` LIKE 'integria_inventory';
DELETE FROM `tconfig` WHERE `token` LIKE 'integria_url';

ALTER TABLE `tdatabase` MODIFY `last_error` text;
ALTER TABLE `tdatabase` MODIFY `host` VARCHAR(255);
ALTER TABLE `tdatabase` ADD COLUMN `label` VARCHAR(255);
ALTER TABLE `tdatabase` MODIFY `os_user` VARCHAR(255);
ALTER TABLE `tdatabase` MODIFY `db_port` INT UNSIGNED NOT NULL;
ALTER TABLE `tdatabase` MODIFY `os_port` INT UNSIGNED NOT NULL;
ALTER TABLE `tdatabase` ADD COLUMN `ssh_key` TEXT;
ALTER TABLE `tdatabase` ADD COLUMN `ssh_pubkey` TEXT;

COMMIT;