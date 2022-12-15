START TRANSACTION;

ALTER TABLE `trecon_task` ADD COLUMN `snmp_skip_non_enabled_ifs` TINYINT UNSIGNED DEFAULT 1;

ALTER TABLE `tlayout` ADD COLUMN `maintenance_mode` TEXT;

ALTER TABLE `tusuario` ADD COLUMN `auth_token_secret` VARCHAR(45) DEFAULT NULL;

ALTER TABLE `tmodule_inventory` ADD COLUMN `script_mode` INT NOT NULL DEFAULT 2;
ALTER TABLE `tmodule_inventory` ADD COLUMN `script_path` VARCHAR(1000) DEFAULT '';

ALTER TABLE `tevent_filter` ADD COLUMN `search_recursive_groups` INT NOT NULL DEFAULT 0;

ALTER TABLE `tagente_modulo` ADD COLUMN `warning_time` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `tnetwork_component` ADD COLUMN `warning_time` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `tlocal_component` ADD COLUMN `warning_time` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `tpolicy_modules` ADD COLUMN `warning_time` int(10) UNSIGNED DEFAULT 0;

ALTER TABLE `tagente_estado` ADD COLUMN `warning_count` int(10) UNSIGNED DEFAULT 0;

ALTER TABLE `tcredential_store` MODIFY COLUMN `product` ENUM('CUSTOM', 'AWS', 'AZURE', 'GOOGLE', 'SAP', 'WMI', 'SNMP') DEFAULT 'CUSTOM';

ALTER TABLE `talert_template_modules` DROP INDEX `id_agent_module`;
ALTER TABLE `talert_template_modules` ADD UNIQUE (`id_agent_module`, `id_alert_template`, `id_policy_alerts`);

COMMIT;
