START TRANSACTION;

ALTER TABLE `tservice` ADD COLUMN `quiet` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tservice` ADD COLUMN `cps` int NOT NULL DEFAULT '0';
ALTER TABLE `tservice` ADD COLUMN `cascade_protection` tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `tagente` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

ALTER TABLE `tmetaconsole_agent` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

ALTER TABLE `tagente_modulo` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

ALTER TABLE `tservice` ADD COLUMN `evaluate_sla` int(1) NOT NULL DEFAULT '0';

ALTER TABLE `tpolicy_modules` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

COMMIT;