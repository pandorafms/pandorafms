START TRANSACTION;

ALTER TABLE `talert_templates`
ADD COLUMN `field16` TEXT NOT NULL AFTER `field15`
,ADD COLUMN `field17` TEXT NOT NULL AFTER `field16`
,ADD COLUMN `field18` TEXT NOT NULL AFTER `field17`
,ADD COLUMN `field19` TEXT NOT NULL AFTER `field18`
,ADD COLUMN `field20` TEXT NOT NULL AFTER `field19`
,ADD COLUMN `field16_recovery` TEXT NOT NULL AFTER `field15_recovery`
,ADD COLUMN `field17_recovery` TEXT NOT NULL AFTER `field16_recovery`
,ADD COLUMN `field18_recovery` TEXT NOT NULL AFTER `field17_recovery`
,ADD COLUMN `field19_recovery` TEXT NOT NULL AFTER `field18_recovery`
,ADD COLUMN `field20_recovery` TEXT NOT NULL AFTER `field19_recovery`;

COMMIT;
