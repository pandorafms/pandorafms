START TRANSACTION;

ALTER TABLE `tusuario`  ADD COLUMN `session_max_time_expire` INT NOT NULL DEFAULT 0 AFTER `auth_token_secret`;

COMMIT;