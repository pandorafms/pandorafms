START TRANSACTION;

ALTER TABLE `treport_content_template` ADD COLUMN `time_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content_template` ADD COLUMN `checks_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `time_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `checks_in_warning_status` TINYINT(1) DEFAULT '0';

COMMIT;
