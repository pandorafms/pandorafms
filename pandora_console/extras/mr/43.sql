START TRANSACTION;

ALTER TABLE `tagente_modulo` ADD COLUMN `debug_content` varchar(200);

ALTER TABLE `talert_snmp` ADD COLUMN `al_field16` TEXT NOT NULL AFTER `al_field15`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field17` TEXT NOT NULL AFTER `al_field16`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field18` TEXT NOT NULL AFTER `al_field17`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field19` TEXT NOT NULL AFTER `al_field18`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field20` TEXT NOT NULL AFTER `al_field19`;

COMMIT;