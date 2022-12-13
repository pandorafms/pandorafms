START TRANSACTION;

ALTER TABLE `tagent_custom_fields` ADD `is_link_enabled` TINYINT(1) NOT NULL DEFAULT 0;

COMMIT;
