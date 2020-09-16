START TRANSACTION;

CREATE INDEX `tme_timestamp_idx` ON tmetaconsole_event (`timestamp`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tme_module_status_idx` ON tmetaconsole_event (`module_status`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tme_criticity_idx` ON tmetaconsole_event (`criticity`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tme_agent_name_idx` ON tmetaconsole_event (`agent_name`) ALGORITHM DEFAULT LOCK DEFAULT;

CREATE INDEX `tma_id_os_idx` ON tmetaconsole_agent (`id_os`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tma_server_name_idx` ON tmetaconsole_agent (`server_name`) ALGORITHM DEFAULT LOCK DEFAULT;

CREATE INDEX `tmeh_estado_idx` ON tmetaconsole_event_history (`timestamp`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tmeh_timestamp_idx` ON tmetaconsole_event_history (`estado`) ALGORITHM DEFAULT LOCK DEFAULT;

COMMIT;