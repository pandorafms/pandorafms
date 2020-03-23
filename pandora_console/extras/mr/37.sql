START TRANSACTION;

ALTER TABLE `talert_templates` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `tevent_alert` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `talert_snmp` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;

COMMIT;
