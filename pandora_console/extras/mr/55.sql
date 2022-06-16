START TRANSACTION;

ALTER TABLE `tservice` ADD COLUMN `enable_sunburst` tinyint(1) NOT NULL default 0;

ALTER TABLE `tdashboard` MODIFY `name` TEXT NOT NULL DEFAULT '';

ALTER TABLE `tevent_alert` ADD COLUMN `field1_recovery` text DEFAULT '' AFTER `recovery_notify`;
ALTER TABLE `tevent_alert` ADD COLUMN `field4_recovery` text DEFAULT '' AFTER `field3_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field5_recovery` text DEFAULT '' AFTER `field4_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field6_recovery` text DEFAULT '' AFTER `field5_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field7_recovery` text DEFAULT '' AFTER `field6_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field8_recovery` text DEFAULT '' AFTER `field7_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field0_recovery` text DEFAULT '' AFTER `field8_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field10_recovery` text DEFAULT '' AFTER `field9_recovery`;

COMMIT;
