START TRANSACTION;

ALTER TABLE `tusuario` ADD COLUMN `auth_token_secret` VARCHAR(45) DEFAULT NULL;

ALTER TABLE `tmodule_inventory` ADD COLUMN `script_mode` INT NOT NULL DEFAULT 2;
ALTER TABLE `tmodule_inventory` ADD COLUMN `script_path` VARCHAR(1000) DEFAULT '';

ALTER TABLE `tevent_filter` ADD COLUMN `search_recursive_groups` INT NOT NULL DEFAULT 0;

ALTER TABLE `talert_template_modules` DROP INDEX `id_agent_module`;
ALTER TABLE `talert_template_modules` ADD UNIQUE (`id_agent_module`, `id_alert_template`, `id_policy_alerts`);

COMMIT;
