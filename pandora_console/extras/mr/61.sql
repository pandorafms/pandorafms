START TRANSACTION;

ALTER TABLE `tserver` ADD COLUMN `server_keepalive_utimestamp` BIGINT NOT NULL DEFAULT 0;

COMMIT;
