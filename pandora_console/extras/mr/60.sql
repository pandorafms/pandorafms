START TRANSACTION;

ALTER TABLE `tagent_custom_fields` ADD `is_link_enabled` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `tevent_filter` ADD COLUMN `owner_user` TEXT;
ALTER TABLE `tevent_filter` ADD COLUMN `not_search` INT NOT NULL DEFAULT 0;

COMMIT;
