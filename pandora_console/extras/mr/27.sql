START TRANSACTION;

ALTER TABLE `tagente_modulo` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';
ALTER TABLE `tlocal_component` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';
ALTER TABLE `tpolicy_modules` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';

ALTER TABLE `tagente_estado` ADD COLUMN `ff_normal` int(4) unsigned default '0';
ALTER TABLE `tagente_estado` ADD COLUMN `ff_warning` int(4) unsigned default '0';
ALTER TABLE `tagente_estado` ADD COLUMN `ff_critical` int(4) unsigned default '0';

COMMIT;