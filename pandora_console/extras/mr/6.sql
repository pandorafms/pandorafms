START TRANSACTION;

ALTER TABLE tagente MODIFY COLUMN `cascade_protection_module` int(10) unsigned NOT NULL default '0';

ALTER TABLE tserver ADD COLUMN exec_proxy tinyint(1) UNSIGNED NOT NULL default 0;

ALTER TABLE tevent_response ADD COLUMN server_to_exec int(10) unsigned NOT NULL DEFAULT 0;

COMMIT;