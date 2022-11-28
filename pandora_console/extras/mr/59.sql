START TRANSACTION;

ALTER TABLE `tevent_filter` MODIFY COLUMN `server_id` TEXT;

UPDATE tconfig SET value = 'Hope' WHERE token LIKE 'lts_name';

COMMIT;