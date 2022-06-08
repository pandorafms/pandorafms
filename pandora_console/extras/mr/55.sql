START TRANSACTION;

ALTER TABLE `tservice` ADD COLUMN `enable_sunburst` tinyint(1) NOT NULL default 0;

ALTER TABLE `tdashboard` MODIFY `name` TEXT NOT NULL DEFAULT '';

ALTER TABLE `tagente` ADD COLUMN `satellite_server` INT NOT NULL default 0;
ALTER TABLE `tmetaconsole_agent` ADD COLUMN `satellite_server` INT NOT NULL default 0;

COMMIT;

