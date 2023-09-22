START TRANSACTION;

ALTER TABLE `tevento`
ADD COLUMN `custom_field` TEXT NULL AFTER `module_status`;

COMMIT;
