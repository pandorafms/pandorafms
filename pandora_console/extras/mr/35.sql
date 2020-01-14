START TRANSACTION;

ALTER TABLE `tserver` ADD COLUMN `port` int(5) unsigned NOT NULL default 0;

COMMIT;
