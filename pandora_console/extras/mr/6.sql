START TRANSACTION;

ALTER TABLE tagente MODIFY COLUMN `cascade_protection_module` int(10) unsigned NOT NULL default '0';

INSERT INTO tmodule VALUES (8, 'Wux&#x20;module');

COMMIT;
