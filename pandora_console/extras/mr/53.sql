START TRANSACTION;

ALTER TABLE `tipam_vlan` ADD COLUMN `custom_id` bigint(20) unsigned DEFAULT NULL;

COMMIT;
