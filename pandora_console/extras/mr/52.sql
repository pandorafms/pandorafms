START TRANSACTION;
ALTER TABLE `tpolicy_queue` MODIFY COLUMN `progress` int(10) NOT NULL default '0';
CREATE INDEX `IDX_tservice_element` ON `tservice_element`(`id_service`,`id_agente_modulo`);

ALTER TABLE `talert_templates` ADD COLUMN `schedule` TEXT;
ALTER TABLE `tevent_alert` ADD COLUMN `schedule` TEXT;

SOURCE procedures/alertTemplates.sql;
CALL `migrateRanges`();
DROP PROCEDURE `migrateRanges`;

SOURCE procedures/alertEvents.sql;
CALL `migrateEventRanges`();
DROP PROCEDURE `migrateEventRanges`;

COMMIT;