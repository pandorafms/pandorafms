START TRANSACTION;

ALTER TABLE `trecon_script` ADD COLUMN `type` int NOT NULL default 0;

UPDATE trecon_script SET `type` = 1 WHERE `name`="Discovery.Application.VMware";
UPDATE trecon_script SET `type` = 2 WHERE `name`="Discovery.Application.MySQL";
UPDATE trecon_script SET `type` = 3 WHERE `name`="Discovery.Application.Oracle";
UPDATE trecon_script SET `type` = 100 WHERE `name`="Discovery.Cloud";
UPDATE trecon_script SET `type` = 101 WHERE `name`="Discovery.Cloud.RDS";

COMMIT;