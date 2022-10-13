START TRANSACTION;

ALTER TABLE `tlayout` ADD COLUMN `maintenance_mode` TEXT NULL;

ALTER TABLE `tmodule_inventory` ADD COLUMN `script_mode` INT NOT NULL DEFAULT 2;
ALTER TABLE `tmodule_inventory` ADD COLUMN `script_path` VARCHAR(1000) DEFAULT '';

COMMIT;
