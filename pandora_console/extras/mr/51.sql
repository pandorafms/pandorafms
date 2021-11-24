START TRANSACTION;

ALTER TABLE `tagente_modulo` ADD COLUMN `percentage_critical` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tagente_modulo` ADD COLUMN `percentage_warning` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tnetwork_component` ADD COLUMN `percentage_critical` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tnetwork_component` ADD COLUMN `percentage_warning` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tlocal_component` ADD COLUMN `percentage_critical` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tlocal_component` ADD COLUMN `percentage_warning` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tpolicy_modules` ADD COLUMN `percentage_warning` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tpolicy_modules` ADD COLUMN `percentage_critical` tinyint(1) UNSIGNED DEFAULT 0;





COMMIT;