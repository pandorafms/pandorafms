START TRANSACTION;

ALTER TABLE `tusuario` ADD COLUMN `integria_user_level_user` VARCHAR(60);
ALTER TABLE `tusuario` ADD COLUMN `integria_user_level_pass` VARCHAR(45);
ALTER TABLE `tperfil` DROP COLUMN `incident_view`;
ALTER TABLE `tperfil` DROP COLUMN `incident_edit`;
ALTER TABLE `tperfil` DROP COLUMN `incident_management`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field16` TEXT NOT NULL AFTER `al_field15`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field17` TEXT NOT NULL AFTER `al_field16`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field18` TEXT NOT NULL AFTER `al_field17`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field19` TEXT NOT NULL AFTER `al_field18`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field20` TEXT NOT NULL AFTER `al_field19`;

COMMIT;
