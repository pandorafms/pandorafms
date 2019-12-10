START TRANSACTION;

ALTER TABLE `tevent_response` ADD COLUMN `command_timeout` int(5) unsigned NOT NULL DEFAULT 90;

COMMIT;
