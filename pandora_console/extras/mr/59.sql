START TRANSACTION;

ALTER TABLE `tevent_filter` MODIFY COLUMN `server_id` TEXT;

COMMIT;
