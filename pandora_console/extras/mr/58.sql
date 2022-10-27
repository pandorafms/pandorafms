START TRANSACTION;

ALTER TABLE `trecon_task` ADD COLUMN `snmp_skip_non_enabled_ifs` TINYINT UNSIGNED DEFAULT 1;
ALTER TABLE `tusuario` ADD COLUMN `auth_token_secret` VARCHAR(45) DEFAULT NULL;

ALTER TABLE `tmodule_inventory` ADD COLUMN `script_mode` INT NOT NULL DEFAULT 2;
ALTER TABLE `tmodule_inventory` ADD COLUMN `script_path` VARCHAR(1000) DEFAULT '';

ALTER TABLE `tevent_filter` ADD COLUMN `search_recursive_groups` INT NOT NULL DEFAULT 0;

COMMIT;
