START TRANSACTION;
ALTER TABLE `tpolicy_queue` MODIFY COLUMN `progress` int(10) NOT NULL default '0';
CREATE INDEX `IDX_tservice_element` ON `tservice_element`(`id_service`,`id_agente_modulo`);
ALTER TABLE tevent_response ADD COLUMN display_command tinyint(1) default 0;

ALTER TABLE `talert_templates` ADD COLUMN `schedule` TEXT;
ALTER TABLE `tevent_alert` ADD COLUMN `schedule` TEXT;

SOURCE procedures/alertTemplates.sql;
CALL `migrateRanges`();
DROP PROCEDURE `migrateRanges`;

SOURCE procedures/alertEvents.sql;
CALL `migrateEventRanges`();
DROP PROCEDURE `migrateEventRanges`;

CREATE TABLE IF NOT EXISTS `tpolicy_group_agents` (
    `id` SERIAL,
    `id_policy` INT UNSIGNED,
    `id_agent` INT UNSIGNED,
	`direct` TINYINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (`id_policy`) REFERENCES `tpolicies`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`)
        ON DELETE CASCADE ON UPDATE CASCADE		
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

COMMIT;