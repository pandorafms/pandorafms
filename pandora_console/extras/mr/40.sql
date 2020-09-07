START TRANSACTION;

UPDATE `talert_commands` SET name='Monitoring&#x20;Event' WHERE name='Pandora FMS Event';

ALTER TABLE `tservice_element` ADD COLUMN `rules` text;
ALTER TABLE `tservice` ADD COLUMN `unknown_as_critical` tinyint(1) NOT NULL default 0 AFTER `warning`;
ALTER TABLE `tserver` MODIFY COLUMN `version` varchar(25) NOT NULL DEFAULT '';

UPDATE `tservice` SET `auto_calculate`=0;

UPDATE `tservice` SET `cps`= `cps` - 1 WHERE `cps` > 0;
UPDATE `tagente` SET `cps`= `cps` - 1 WHERE `cps` > 0;
UPDATE `tagente_modulo` SET `cps`= `cps` - 1 WHERE `cps` > 0;

COMMIT;
