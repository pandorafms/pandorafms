START TRANSACTION;

UPDATE `tnetwork_component`
   SET module_enabled=1
WHERE name='Cisco&#x20;_nameOID_&#x20;power&#x20;state';

ALTER TABLE `tlayout_data`
ADD COLUMN `recursive_group` TINYINT NOT NULL DEFAULT '0' AFTER `fill_color`;

COMMIT;
