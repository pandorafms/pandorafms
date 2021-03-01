START TRANSACTION;

ALTER TABLE `tinventory_alert` ADD COLUMN `alert_groups` TEXT NOT NULL;
UPDATE `tinventory_alert` t1 INNER JOIN `tinventory_alert` t2 ON t1.id = t2.id SET t1.alert_groups = t2.id_group;

ALTER TABLE `tnotification_source` ADD COLUMN `subtype_blacklist` TEXT;

COMMIT;
