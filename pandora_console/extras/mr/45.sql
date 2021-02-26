START TRANSACTION;

ALTER TABLE `tnotification_source` ADD COLUMN `subtype_blacklist` TEXT;

COMMIT;

