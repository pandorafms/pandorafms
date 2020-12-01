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

UPDATE `tconfig` SET value = '{"0.00000038580247":"Seconds&#x20;to&#x20;months","0.00000165343915":"Seconds&#x20;to&#x20;weeks","0.00001157407407":"Seconds&#x20;to&#x20;days","0.01666666666667":"Seconds&#x20;to&#x20;minutes","0.00000000093132":"Bytes&#x20;to&#x20;Gigabytes","0.00000095367432":"Bytes&#x20;to&#x20;Megabytes","0.00097656250000":"Bytes&#x20;to&#x20;Kilobytes","0.00000001653439":"Timeticks&#x20;to&#x20;weeks","0.00000011574074":"Timeticks&#x20;to&#x20;days"}' WHERE token = 'post_process_custom_values';

COMMIT;