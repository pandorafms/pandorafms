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

INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_enabled', 0);
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_user', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_pass', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_hostname', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_api_pass', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('integria_req_timeout', 5);
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_group', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_criticity', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_creator', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('default_owner', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_type', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_status', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_title', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('incident_content', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_default_group', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_default_criticity', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_default_creator', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_default_owner', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_incident_type', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_incident_status', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_incident_title', '');
INSERT INTO `tconfig` (`token`, `value`) VALUES ('cr_incident_content', '');

ALTER TABLE `treport_content` ADD COLUMN `uncompressed_module` TINYINT DEFAULT '0';
ALTER TABLE `treport_content_template` ADD COLUMN `uncompressed_module` TINYINT DEFAULT '0';

COMMIT;
