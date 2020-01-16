START TRANSACTION;

ALTER TABLE `tserver` ADD COLUMN `port` int(5) unsigned NOT NULL default 0;
ALTER TABLE `tmap` ADD COLUMN `id_group_map` INT(10) UNSIGNED NOT NULL default 0;
ALTER TABLE `tevent_filter` MODIFY `severity` TEXT NOT NULL;

COMMIT;
