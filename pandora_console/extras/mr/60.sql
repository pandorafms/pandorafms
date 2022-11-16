START TRANSACTION;

ALTER TABLE `tagent_custom_fields` ADD `is_link_enabled` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `tagent_custom_fields` ADD COLUMN `link_text` VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE `tagent_custom_fields` ADD COLUMN `link_url` VARCHAR(2048) NOT NULL DEFAULT '';

COMMIT;
