START TRANSACTION;

ALTER TABLE `tdatabase` MODIFY `last_error` text;
ALTER TABLE `tdatabase` MODIFY `host` VARCHAR(255) DEFAULT '';
ALTER TABLE `tdatabase` ADD COLUMN `label` VARCHAR(255) DEFAULT '';
ALTER TABLE `tdatabase` MODIFY `os_user` VARCHAR(255) DEFAULT '';
ALTER TABLE `tdatabase` MODIFY `db_port` INT UNSIGNED NOT NULL DEFAULT 3306;
ALTER TABLE `tdatabase` MODIFY `os_port` INT UNSIGNED NOT NULL DEFAULT 22;
ALTER TABLE `tdatabase` ADD COLUMN `ssh_key` TEXT;
ALTER TABLE `tdatabase` ADD COLUMN `ssh_pubkey` TEXT;

UPDATE `tdatabase` set `label`=`host`;

UPDATE `tlayout_data` SET `height` = 70 , `width` = 70 WHERE `height` = 0 && `width` = 0 && image NOT LIKE '%dot%' && ((`type` IN (0,5)) ||
(`type` = 10 && `image` IS NOT NULL && `image` != '' && `image` != 'none') ||
(`type` = 11 && `image` IS NOT NULL && `image` != '' && `image` != 'none' && `show_statistics` = 0));


ALTER TABLE `treport_content` ADD COLUMN `uncompressed_module` TINYINT DEFAULT '0';
ALTER TABLE `treport_content_template` ADD COLUMN `uncompressed_module` TINYINT DEFAULT '0';


COMMIT;
