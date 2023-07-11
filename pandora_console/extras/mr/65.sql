START TRANSACTION;

ALTER TABLE `tlayout`
ADD COLUMN `grid_color` VARCHAR(45) NOT NULL DEFAULT '#cccccc' AFTER `maintenance_mode`,
ADD COLUMN `grid_size` VARCHAR(45) NOT NULL DEFAULT '10' AFTER `grid_color`;

ALTER TABLE `tlayout_template`
ADD COLUMN `grid_color` VARCHAR(45) NOT NULL DEFAULT '#cccccc' AFTER `maintenance_mode`,
ADD COLUMN `grid_size` VARCHAR(45) NOT NULL DEFAULT '10' AFTER `grid_color`;

DELETE FROM tconfig WHERE token = 'refr';

ALTER TABLE `tusuario`  ADD COLUMN `session_max_time_expire` INT NOT NULL DEFAULT 0 AFTER `auth_token_secret`;

ALTER TABLE `treport_content`  ADD COLUMN `period_range` INT NULL DEFAULT 0 AFTER `period`;

COMMIT;
