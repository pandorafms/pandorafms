START TRANSACTION;

ALTER TABLE `tlayout` 
ADD COLUMN `grid_color` VARCHAR(45) NOT NULL DEFAULT '#cccccc' AFTER `maintenance_mode`,
ADD COLUMN `grid_size` VARCHAR(45) NOT NULL DEFAULT '10px' AFTER `grid_color`;

COMMIT;
