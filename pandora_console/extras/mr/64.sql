START TRANSACTION;

UPDATE `tnetwork_component`
   SET module_enabled=1
WHERE name='Cisco&#x20;_nameOID_&#x20;power&#x20;state';

ALTER TABLE `tlayout_data`
ADD COLUMN `recursive_group` TINYINT NOT NULL DEFAULT '0' AFTER `fill_color`;

ALTER TABLE `tusuario`
ADD COLUMN `metaconsole_section` VARCHAR(255) NOT NULL DEFAULT 'Default' AFTER `data_section`;

ALTER TABLE `tusuario`
ADD COLUMN `metaconsole_data_section` VARCHAR(255) NOT NULL DEFAULT '' AFTER `metaconsole_section`;

COMMIT;
