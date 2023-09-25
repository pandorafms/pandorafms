START TRANSACTION;

ALTER TABLE `tevento`
ADD COLUMN `event_custom_id` TEXT NULL AFTER `module_status`;

COMMIT;
