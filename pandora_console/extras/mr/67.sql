START TRANSACTION;

-- Delete table tagent_access
DROP TABLE tagent_access;

ALTER TABLE treport_content ADD check_unknowns_graph tinyint DEFAULT 0 NULL;

UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.vmware';

COMMIT;
