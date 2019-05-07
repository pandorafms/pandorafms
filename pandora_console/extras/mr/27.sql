START TRANSACTION;

ALTER TABLE `tnetflow_filter` DROP COLUMN `output`;

ALTER TABLE `tagente_modulo` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';
ALTER TABLE `tlocal_component` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';
ALTER TABLE `tpolicy_modules` ADD COLUMN `ff_type` tinyint(1) unsigned default '0';

ALTER TABLE `tagente_estado` ADD COLUMN `ff_normal` int(4) unsigned default '0';
ALTER TABLE `tagente_estado` ADD COLUMN `ff_warning` int(4) unsigned default '0';
ALTER TABLE `tagente_estado` ADD COLUMN `ff_critical` int(4) unsigned default '0';

UPDATE tuser_task SET parameters = 'a:5:{i:0;a:6:{s:11:\"description\";s:28:\"Report pending to be created\";s:5:\"table\";s:7:\"treport\";s:8:\"field_id\";s:9:\"id_report\";s:10:\"field_name\";s:4:\"name\";s:4:\"type\";s:3:\"int\";s:9:\"acl_group\";s:8:\"id_group\";}i:1;a:2:{s:11:\"description\";s:46:\"Send to email addresses (separated by a comma)\";s:4:\"type\";s:4:\"text\";}i:2;a:2:{s:11:\"description\";s:7:\"Subject\";s:8:\"optional\";i:1;}i:3;a:3:{s:11:\"description\";s:7:\"Message\";s:4:\"type\";s:4:\"text\";s:8:\"optional\";i:1;}i:4;a:2:{s:11:\"description\";s:11:\"Report Type\";s:4:\"type\";s:11:\"report_type\";}}' where function_name = "cron_task_generate_report";

ALTER TABLE `treport_content` ADD COLUMN `total_time` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_failed` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_in_ok_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_in_unknown_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_of_not_initialized_module` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `time_of_downtime` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `total_checks` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `checks_failed` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `checks_in_ok_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `unknown_checks` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `agent_max_value` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content` ADD COLUMN `agent_min_value` TINYINT(1) DEFAULT '1';

ALTER TABLE `treport_content_template` ADD COLUMN `total_time` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_failed` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_in_ok_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_in_unknown_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_of_not_initialized_module` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `time_of_downtime` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `total_checks` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `checks_failed` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `checks_in_ok_status` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `unknown_checks` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `agent_max_value` TINYINT(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `agent_min_value` TINYINT(1) DEFAULT '1';

ALTER TABLE `trecon_script` ADD COLUMN `type` int NOT NULL default 0;
ALTER TABLE `trecon_task` ADD COLUMN `type` int NOT NULL default 0;

UPDATE `trecon_script` SET `type` = 1 WHERE `name`="Discovery.Application.VMware";
UPDATE `trecon_script` SET `type` = 2 WHERE `name`="Discovery.Cloud";
UPDATE `trecon_script` SET `type` = 3 WHERE `name` LIKE "IPAM%Recon";
UPDATE `trecon_script` SET `type` = 4 WHERE `name` LIKE "IPMI%Recon";

UPDATE `trecon_task` SET `type`=3 WHERE `description`="Discovery.Application.VMware";
UPDATE `trecon_task` SET `type`=2 WHERE `description`="Discovery.Cloud";
UPDATE `trecon_task` SET `type`=7 WHERE `description`="Discovery.Cloud.RDS";

COMMIT;
