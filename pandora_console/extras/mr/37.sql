START TRANSACTION;

ALTER TABLE `twidget_dashboard` ADD COLUMN `position` TEXT NOT NULL default '';

ALTER TABLE `tagente_estado` ADD COLUMN `last_status_change` bigint(20) NOT NULL default '0';

UPDATE `tconfig` SET `value`='policy,agent,data_type,module_name,server_type,interval,status,last_status_change,graph,warn,data,timestamp' WHERE `token` = 'status_monitor_fields';

ALTER TABLE `talert_templates` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `tevent_alert` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `talert_snmp` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;

COMMIT;
