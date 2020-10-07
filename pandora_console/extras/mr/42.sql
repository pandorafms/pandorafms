START TRANSACTION;

ALTER TABLE tmetaconsole_event ADD INDEX `tme_timestamp_idx` (`timestamp`);
ALTER TABLE tmetaconsole_event ADD INDEX `tme_module_status_idx` (`module_status`);
ALTER TABLE tmetaconsole_event ADD INDEX `tme_criticity_idx` (`criticity`);
ALTER TABLE tmetaconsole_event ADD INDEX `tme_agent_name_idx` (`agent_name`);

ALTER TABLE tmetaconsole_agent ADD INDEX `tma_id_os_idx` (`id_os`);
ALTER TABLE tmetaconsole_agent ADD INDEX `tma_server_name_idx` (`server_name`);

ALTER TABLE tmetaconsole_event_history ADD INDEX `tmeh_estado_idx` (`estado`);
ALTER TABLE tmetaconsole_event_history ADD INDEX `tmeh_timestamp_idx` (`timestamp`);

ALTER TABLE talert_actions ADD COLUMN `field16` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field17` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field18` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field19` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field20` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field16_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field17_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field18_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field19_recovery` TEXT NOT NULL DEFAULT "";
ALTER TABLE talert_actions ADD COLUMN `field20_recovery` TEXT NOT NULL DEFAULT "";

ALTER TABLE `treport_content` add column `graph_render` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content_template` add column `graph_render` tinyint(1) UNSIGNED NOT NULL default 0;

ALTER TABLE `treport` ADD COLUMN `cover_page_render` tinyint(1) NOT NULL DEFAULT 1;
ALTER TABLE `treport` ADD COLUMN `index_render` tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE `treport_template` ADD COLUMN `cover_page_render` tinyint(1) NOT NULL DEFAULT 1;
ALTER TABLE `treport_template` ADD COLUMN `index_render` tinyint(1) NOT NULL DEFAULT 1;

COMMIT;