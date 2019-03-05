START TRANSACTION;

ALTER TABLE `treport_content` ADD COLUMN `show_extended_events` tinyint(1) default '0';

COMMIT;