START TRANSACTION;

ALTER TABLE `tevent_filter` ADD COLUMN `search_secondary_groups` INT NOT NULL DEFAULT 0;

COMMIT;
