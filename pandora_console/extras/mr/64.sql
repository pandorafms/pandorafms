START TRANSACTION;

ALTER TABLE `tnetwork_component` ADD COLUMN `target_ip` VARCHAR(255) NOT NULL DEFAULT '';

UPDATE tnetwork_component
   SET module_enabled=1
WHERE name='Cisco&#x20;_nameOID_&#x20;power&#x20;state';

COMMIT;
