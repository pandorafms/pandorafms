START TRANSACTION;

ALTER TABLE `tnotification_sources` ADD COLUMN `subtype_blacklist` TEXT;

COMMIT;

