START TRANSACTION;

ALTER TABLE `tagente_estado` ADD COLUMN `last_status_change` bigint(20) NOT NULL default '0';

UPDATE `tconfig` SET `value`='policy,agent,data_type,module_name,server_type,interval,status,last_status_change,graph,warn,data,timestamp' WHERE `token` = 'status_monitor_fields';


COMMIT;