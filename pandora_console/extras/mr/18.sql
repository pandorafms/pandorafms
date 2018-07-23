START TRANSACTION;

ALTER TABLE `tservice` ADD COLUMN `quiet` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tservice` ADD COLUMN `cps` int NOT NULL DEFAULT '0';
ALTER TABLE `tservice` ADD COLUMN `cascade_protection` tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `tagente` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

ALTER TABLE `tmetaconsole_agent` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

ALTER TABLE `tagente_modulo` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

ALTER TABLE `tservice` ADD COLUMN `evaluate_sla` int(1) NOT NULL DEFAULT '0';

ALTER TABLE `tpolicy_modules` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

DROP INDEX id_policy ON `tpolicy_agents`;
ALTER TABLE `tpolicy_agents` ADD COLUMN `id_node` int(10) NOT NULL DEFAULT '0';
ALTER TABLE `tpolicy_agents` ADD UNIQUE(`id_policy`, `id_agent`, `id_node`);

ALTER TABLE `tevento` ADD COLUMN `data` double(22,5) default NULL;

ALTER TABLE `tmetaconsole_event` ADD COLUMN `data` double(22,5) default NULL;

ALTER TABLE `tmetaconsole_event_history` ADD COLUMN `data` double(22,5) default NULL;

ALTER TABLE `tevento` ADD COLUMN `module_status` int(4) NOT NULL default '0';

ALTER TABLE `tmetaconsole_event` ADD COLUMN `module_status` int(4) NOT NULL default '0';

ALTER TABLE `tmetaconsole_event_history` ADD COLUMN `module_status` int(4) NOT NULL default '0';

COMMIT;