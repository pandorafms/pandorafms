START TRANSACTION;

ALTER TABLE `tagente_modulo` ADD COLUMN `debug_content` varchar(200);

INSERT IGNORE INTO tuser_task VALUES (8, 'cron_task_generate_csv_log', 'a:1:{i:0;a:2:{s:11:"description";s:14:"Send to e-mail";s:4:"type";s:4:"text";}}', 'Send csv log');

ALTER TABLE `talert_snmp` ADD COLUMN `al_field16` TEXT NOT NULL AFTER `al_field15`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field17` TEXT NOT NULL AFTER `al_field16`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field18` TEXT NOT NULL AFTER `al_field17`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field19` TEXT NOT NULL AFTER `al_field18`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field20` TEXT NOT NULL AFTER `al_field19`;

COMMIT;
