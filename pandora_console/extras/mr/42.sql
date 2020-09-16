START TRANSACTION;

ALTER TABLE tmetaconsole_event ADD INDEX `tme_timestamp_idx` (`timestamp`);
ALTER TABLE tmetaconsole_event ADD INDEX `tme_module_status_idx` (`module_status`);
ALTER TABLE tmetaconsole_event ADD INDEX `tme_criticity_idx` (`criticity`);
ALTER TABLE tmetaconsole_event ADD INDEX `tme_agent_name_idx` (`agent_name`);

ALTER TABLE tmetaconsole_agent ADD INDEX `tma_id_os_idx` (`id_os`);
ALTER TABLE tmetaconsole_agent ADD INDEX `tma_server_name_idx` (`server_name`);

ALTER TABLE tmetaconsole_event_history ADD INDEX `tmeh_estado_idx` (`estado`);
ALTER TABLE tmetaconsole_event_history ADD INDEX `tmeh_timestamp_idx` (`timestamp`);

COMMIT;