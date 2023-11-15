START TRANSACTION;

UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.vmware';

COMMIT;

