START TRANSACTION;

ALTER TABLE `tusuario`
ADD COLUMN `stop_lts_modal` TINYINT NOT NULL DEFAULT 0 AFTER `session_max_time_expire`;

COMMIT;