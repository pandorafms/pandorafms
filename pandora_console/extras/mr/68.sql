START TRANSACTION;

-- Update version for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.oracle';

ALTER TABLE `tusuario` CHANGE COLUMN `metaconsole_data_section` `metaconsole_data_section` TEXT NOT NULL DEFAULT '' ;

COMMIT;