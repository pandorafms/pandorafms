START TRANSACTION;

CREATE INDEX `tmetaconsole_event_timestamp_idx` ON tmetaconsole_event (`timestamp`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tmetaconsole_event_module_status_idx` ON tmetaconsole_event (`module_status`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tmetaconsole_event_criticity_idx` ON tmetaconsole_event (`criticity`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tmetaconsole_event_agent_name_idx` ON tmetaconsole_event (`agent_name`) ALGORITHM DEFAULT LOCK DEFAULT;

CREATE INDEX `tmetaconsole_agent_id_os_idx` ON tmetaconsole_agent (`id_os`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tmetaconsole_agent_server_name_idx` ON tmetaconsole_agent (`server_name`) ALGORITHM DEFAULT LOCK DEFAULT;

CREATE INDEX `tmetaconsole_event_history_timestamp_idx` ON tmetaconsole_event_history (`timestamp`) ALGORITHM DEFAULT LOCK DEFAULT;
CREATE INDEX `tmetaconsole_event_history_estado_idx` ON tmetaconsole_event_history (`estado`) ALGORITHM DEFAULT LOCK DEFAULT;

COMMIT;