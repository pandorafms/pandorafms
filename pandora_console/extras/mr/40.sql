START TRANSACTION;

ALTER TABLE `talert_templates` ADD COLUMN `previous_name` text;
ALTER TABLE `talert_actions` ADD COLUMN `previous_name` text;
ALTER TABLE `talert_commands` ADD COLUMN `previous_name` text;
ALTER TABLE `ttag` ADD COLUMN `previous_name` text default '';
ALTER TABLE `tconfig_os` ADD COLUMN `previous_name` text default '';

ALTER TABLE `tservice_element` ADD COLUMN `rules` text;
ALTER TABLE `tservice` ADD COLUMN `unknown_as_critical` tinyint(1) NOT NULL default 0 AFTER `warning`;

UPDATE `tservice` SET `auto_calculate`=0;
UPDATE `tservice` SET `cps`= `cps` - 1 WHERE `cps` > 0;
UPDATE `tagente` SET `cps`= `cps` - 1 WHERE `cps` > 0;
UPDATE `tagente_modulo` SET `cps`= `cps` - 1 WHERE `cps` > 0;

COMMIT;
