START TRANSACTION;

UPDATE `talert_commands` SET `fields_descriptions` = '[\"Event&#x20;text\",\"Event&#x20;type\",\"Source\",\"Agent&#x20;name&#x20;or&#x20;_agent_\",\"Event&#x20;severity\",\"ID&#x20;extra\",\"Tags&#x20;separated&#x20;by&#x20;commas\",\"Comments\",\"\",\"\"]' WHERE `id` = 3;

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

ALTER TABLE `trecon_task` MODIFY COLUMN `review_mode` TINYINT(1) UNSIGNED DEFAULT 1;

COMMIT;
