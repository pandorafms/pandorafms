START TRANSACTION;

ALTER TABLE `treport_content` ADD COLUMN `current_month` TINYINT(1) DEFAULT '1';

ALTER TABLE `treport_content_template` ADD COLUMN `current_month` TINYINT(1) DEFAULT '1';

COMMIT;