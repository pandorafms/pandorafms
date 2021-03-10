START TRANSACTION;

ALTER TABLE `treport_content` ADD COLUMN `summary` tinyint(1) DEFAULT 0;
ALTER TABLE `treport_content_template` ADD COLUMN `summary` tinyint(1) DEFAULT 0;

ALTER TABLE `tinventory_alert` ADD COLUMN `alert_groups` TEXT NOT NULL;
UPDATE `tinventory_alert` t1 INNER JOIN `tinventory_alert` t2 ON t1.id = t2.id SET t1.alert_groups = t2.id_group;

ALTER TABLE `tnotification_source` ADD COLUMN `subtype_blacklist` TEXT;

COMMIT;
