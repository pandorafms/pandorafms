START TRANSACTION;

ALTER TABLE `trecon_script` ADD COLUMN `type` int NOT NULL default 0;
ALTER TABLE `trecon_task` ADD COLUMN `type` int NOT NULL default 0;

UPDATE `trecon_script` SET `type` = 1 WHERE `name`="Discovery.Application.VMware";
UPDATE `trecon_script` SET `type` = 2 WHERE `name`="Discovery.Cloud";
UPDATE `trecon_script` SET `type` = 3 WHERE `name` LIKE "IPAM%Recon";
UPDATE `trecon_script` SET `type` = 4 WHERE `name` LIKE "IPMI%Recon";


COMMIT;