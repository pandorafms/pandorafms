START TRANSACTION;

INSERT INTO `tconfig` (`token`, `value`) VALUES ('status_monitor_fields', 'policy,agent,data_type,module_name,server_type,interval,status,graph,warn,data,timestamp');

COMMIT;