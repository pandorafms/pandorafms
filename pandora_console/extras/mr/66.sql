START TRANSACTION;

ALTER TABLE tevent_filter ADD private_filter_user text NULL;
ALTER TABLE `ttrap` ADD COLUMN `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0;

UPDATE ttrap SET utimestamp=UNIX_TIMESTAMP(timestamp);

CREATE TABLE IF NOT EXISTS `tgraph_analytics_filter` (
`id` INT NOT NULL auto_increment,
`filter_name` VARCHAR(45) NULL,
`user_id` VARCHAR(255) NULL,
`graph_modules` TEXT NULL,
`interval` INT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tconfig_os_version` (
  `id_os_version` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product` TEXT,
  `version` TEXT,
  `end_of_support` VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY  (`id_os_version`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tusuario` MODIFY COLUMN `integria_user_level_pass` TEXT;

DROP TABLE `tincidencia`;
DROP TABLE `tnota`;
DROP TABLE `tattachment`;

ALTER TABLE `talert_commands` ADD CONSTRAINT UNIQUE (`name`);

ALTER TABLE `talert_actions` MODIFY COLUMN `name` VARCHAR(500);
ALTER TABLE `talert_actions` ADD CONSTRAINT UNIQUE (`name`);

SET @command_name = 'Pandora&#x20;ITSM&#x20;Ticket';
SET @command_description = 'Create&#x20;a&#x20;ticket&#x20;in&#x20;Pandora&#x20;ITSM';
SET @action_name = 'Create&#x20;Pandora&#x20;ITSM&#x20;ticket';

UPDATE `talert_commands` SET `name` = @command_name, `description` = @command_description WHERE `name` = 'Integria&#x20;IMS&#x20;Ticket' AND `internal` = 1;
INSERT IGNORE INTO `talert_commands` (`name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES (@command_name,'Internal&#x20;type',@command_description,1,'["Ticket&#x20;title","Ticket&#x20;group&#x20;ID","Ticket&#x20;priority","Ticket&#x20;owner","Ticket&#x20;type","Ticket&#x20;status","Ticket&#x20;description","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_"]','["", "_ITSM_groups_", "_ITSM_priorities_","_ITSM_users_","_ITSM_types_","_ITSM_status_","_html_editor_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_","_custom_field_ITSM_"]');

SELECT @id_alert_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
UPDATE `talert_actions` SET `name` = @action_name WHERE `name` = 'Create&#x20;Integria&#x20;IMS&#x20;ticket' AND `id_alert_command` = @id_alert_command;
INSERT IGNORE INTO `talert_actions` (`name`, `id_alert_command`) VALUES (@action_name,@id_alert_command);

SET @event_response_name = 'Create&#x20;ticket&#x20;in&#x20;Pandora&#x20;ITSM&#x20;from&#x20;event';
SET @event_response_description = 'Create&#x20;a&#x20;ticket&#x20;in&#x20;Pandora&#x20;ITSM&#x20;from&#x20;an&#x20;event';
SET @event_response_target = 'index.php?sec=manageTickets&amp;sec2=operation/ITSM/itsm&amp;operation=edit&amp;from_event=_event_id_';
SET @event_response_type = 'url';
SET @event_response_id_group = 0;
SET @event_response_modal_width = 0;
SET @event_response_modal_height = 0;
SET @event_response_new_window = 1;
SET @event_response_params = '';
SET @event_response_server_to_exec = 0;
SET @event_response_command_timeout = 90;
SET @event_response_display_command = 1;
UPDATE `tevent_response` SET `name` = @event_response_name, `description` = @event_response_description, `target` = @event_response_target, `display_command` = @event_response_display_command WHERE `name` = 'Create&#x20;ticket&#x20;in&#x20;IntegriaIMS&#x20;from&#x20;event';
INSERT IGNORE INTO `tevent_response` (`name`, `description`, `target`,`type`,`id_group`,`modal_width`,`modal_height`,`new_window`,`params`,`server_to_exec`,`command_timeout`,`display_command`) VALUES (@event_response_name, @event_response_description, @event_response_target, @event_response_type, @event_response_id_group, @event_response_modal_width, @event_response_modal_height, @event_response_new_window, @event_response_params, @event_response_server_to_exec, @event_response_command_timeout, @event_response_display_command);

UPDATE `twelcome_tip`
	SET title = 'Scheduled&#x20;downtimes',
         url = 'https://pandorafms.com/manual/en/documentation/04_using/11_managing_and_administration#scheduled_downtimes'
	WHERE title = 'planned&#x20;stops';

UPDATE tagente_modulo SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tpolicy_modules SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tnetwork_component SET `tcp_send` = '2c' WHERE `tcp_send` = '2';

ALTER TABLE talert_templates
ADD COLUMN `time_window` ENUM ('thirty_days','this_month','seven_days','this_week','one_day','today'),
ADD COLUMN `math_function` ENUM ('avg', 'min', 'max', 'sum'),
ADD COLUMN `condition` ENUM ('lower', 'greater', 'equal'),
MODIFY COLUMN `type` ENUM ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal', 'warning', 'critical', 'onchange', 'unknown', 'always', 'not_normal', 'complex');

ALTER TABLE `tsesion_filter_log_viewer`
CHANGE COLUMN `date_range` `custom_date` INT NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_defined` `date` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_time` `date_text` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_date` `date_units` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_date_range` `date_init` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_time_range` `time_init` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `end_date_date_range` `date_end` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `end_date_time_range` `time_end` VARCHAR(45) NULL DEFAULT NULL ;

ALTER TABLE `tsesion_filter`
CHANGE COLUMN `period` `date_text` VARCHAR(45) NULL DEFAULT NULL AFTER `user`;

ALTER TABLE `tsesion_filter`
ADD COLUMN `custom_date` INT NULL AFTER `user`,
ADD COLUMN `date` VARCHAR(45) NULL AFTER `custom_date`,
ADD COLUMN `date_units` VARCHAR(45) NULL AFTER `date_text`,
ADD COLUMN `date_init` VARCHAR(45) NULL AFTER `date_units`,
ADD COLUMN `time_init` VARCHAR(45) NULL AFTER `date_init`,
ADD COLUMN `date_end` VARCHAR(45) NULL AFTER `time_init`,
ADD COLUMN `time_end` VARCHAR(45) NULL AFTER `date_end`;

INSERT INTO `tconfig_os_version` (`id_os_version`, `product`, `version`, `end_of_support`) VALUES (1,'Windows.*','7.*','2020/01/14');
INSERT INTO `tconfig_os_version` (`id_os_version`, `product`, `version`, `end_of_support`) VALUES (2,'Cisco.*','IOS 3.4.3','2017/05/12');
INSERT INTO `tconfig_os_version` (`id_os_version`, `product`, `version`, `end_of_support`) VALUES (3,'Linux.*','Centos 7.*','2022/01/01');

UPDATE `tdiscovery_apps` SET `version` = '1.1' WHERE `short_name` = 'pandorafms.vmware';

-- Insert new Proxmox APP
SET @short_name = 'pandorafms.proxmox';
SET @name = 'Proxmox';
SET @section = 'app';
SET @description = 'Monitor&#x20;Proxmox&#x20;VMs,&#x20;LXC,&#x20;backups&#x20;and&#x20;nodes&#x20;from&#x20;a&#x20;specific&#x20;host';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_proxmox');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;-g&#x20;&#039;__taskGroup__&#039;&#x20;--host&#x20;&#039;_host_&#039;&#x20;--port&#x20;&#039;_port_&#039;&#x20;--user&#x20;&#039;_user_&#039;&#x20;--password&#x20;&#039;_password_&#039;&#x20;--vm&#x20;&#039;_scanVM_&#039;&#x20;--lxc&#x20;&#039;_scanLXC_&#039;&#x20;--backups&#x20;&#039;_scanBackups_&#039;&#x20;--nodes&#x20;&#039;_scanNodes_&#039;&#x20;--transfer_mode&#x20;tentacle&#x20;--tentacle_address&#x20;&#039;_tentacleIP_&#039;&#x20;--tentacle_port&#x20;&#039;_tentaclePort_&#039;&#x20;--as_discovery_plugin&#x20;1');

-- Insert new SAP APP
SET @short_name = 'pandorafms.sap.deset';
SET @name = 'SAP&#x20;R3&#x20;-&#x20;Deset';
SET @section = 'app';
SET @description = 'Monitor&#x20;SAP&#x20;R3&#x20;environments';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_sap_deset');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_java_', 'bin/lib/jre/bin/java');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileConf_&#039;&#x20;--custom_modules&#x20;&#039;_tempfileCustomModules_&#039;');

-- Insert new EC2 APP
SET @short_name = 'pandorafms.aws.ec2';
SET @name = 'Amazon&#x20;EC2';
SET @section = 'cloud';
SET @description = 'Monitor&#x20;AWS&#x20;EC2&#x20;instances';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_aws_ec2');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec2_', 'bin/aws_ec2');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileEC2_&#039;');

-- Insert new RDS APP
SET @short_name = 'pandorafms.aws.rds';
SET @name = 'Amazon&#x20;RDS';
SET @section = 'cloud';
SET @description = 'Monitor&#x20;AWS&#x20;RDS&#x20;instances';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_aws_rds');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec2_', 'bin/aws_rds');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileRDS_&#039;');

-- Insert new S3 APP
SET @short_name = 'pandorafms.aws.s3';
SET @name = 'Amazon&#x20;S3';
SET @section = 'cloud';
SET @description = 'Monitor&#x20;AWS&#x20;S3&#x20;buckets';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_aws_s3');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec2_', 'bin/aws_s3');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileS3_&#039;');

-- Insert new Azure APP
SET @short_name = 'pandorafms.azure.mc';
SET @name = 'Azure&#x20;Microsoft&#x20;Compute';
SET @section = 'cloud';
SET @description = 'Monitor&#x20;Azure&#x20;Microsoft&#x20;Compute&#x20;VMs';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_azure_mc');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec2_', 'bin/azure_vm');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileAzureMC_&#039;');

-- Insert new Google APP
SET @short_name = 'pandorafms.gcp.ce';
SET @name = 'Google&#x20;Cloud&#x20;Compute&#x20;Engine';
SET @section = 'cloud';
SET @description = 'Monitor&#x20;Google&#x20;Cloud&#x20;Platform&#x20;Compute&#x20;Engine&#x20;VMs';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_gcp_ce');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec2_', 'bin/google_instances');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileGoogleCE_&#039;');

ALTER TABLE `treport_content`  ADD COLUMN `cat_security_hardening` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content`  ADD COLUMN `ignore_skipped` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content`  ADD COLUMN `status_of_check` TINYTEXT;

ALTER TABLE `tservice` ADD COLUMN `enable_horizontal_tree` TINYINT NOT NULL DEFAULT 0;
INSERT INTO tmodule_group (name) SELECT ('Security') WHERE NOT EXISTS (SELECT name FROM tmodule_group WHERE LOWER(name) = 'security');

SET @tmodule_name = 'CPU';
SET @tmodule_description = 'CPU';
SET @id_os = 2;

INSERT INTO tmodule_inventory (`id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) 
SELECT * FROM (SELECT @id_os id_os, @tmodule_name name, @tmodule_description description, '' interpreter, 'Brand;Clock;Model' data_format, '' code, '0' block_mode, 2 script_mode) AS tmp 
WHERE NOT EXISTS (SELECT name, description FROM tmodule_inventory WHERE name = @tmodule_name and description = @tmodule_description and id_os = @id_os);

SET @tmodule_name = 'RAM';
SET @tmodule_description = 'RAM';
SET @id_os = 2;

INSERT INTO tmodule_inventory (`id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) 
SELECT * FROM (SELECT @id_os id_os, @tmodule_name name, @tmodule_description description, '' interpreter, 'Size' data_format, '' code, '0' block_mode, 2 script_mode) AS tmp 
WHERE NOT EXISTS (SELECT name, description FROM tmodule_inventory WHERE name = @tmodule_name and description = @tmodule_description and id_os = @id_os);

SET @tmodule_name = 'NIC';
SET @tmodule_description = 'NIC';
SET @id_os = 2;

INSERT INTO tmodule_inventory (`id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) 
SELECT * FROM (SELECT @id_os id_os, @tmodule_name name, @tmodule_description description, '' interpreter, 'NIC;Mac;Speed' data_format, '' code, '0' block_mode, 2 script_mode) AS tmp 
WHERE NOT EXISTS (SELECT name, description FROM tmodule_inventory WHERE name = @tmodule_name and description = @tmodule_description and id_os = @id_os);

SET @tmodule_name = 'Software';
SET @tmodule_description = 'Software';
SET @id_os = 2;

INSERT INTO tmodule_inventory (`id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) 
SELECT * FROM (SELECT @id_os id_os, @tmodule_name name, @tmodule_description description, '' interpreter, 'PKGINST;VERSION;NAME' data_format, '' code, '0' block_mode, 2 script_mode) AS tmp 
WHERE NOT EXISTS (SELECT name, description FROM tmodule_inventory WHERE name = @tmodule_name and description = @tmodule_description and id_os = @id_os);

SET @tmodule_name = 'Security';
SET @tmodule_description = 'Hardening&#x20;plugin&#x20;for&#x20;security&#x20;compliance&#x20;analysis';
SET @id_os = 1;

INSERT INTO tmodule_inventory (`id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`)
SELECT * FROM (SELECT @id_os id_os, @tmodule_name name, @tmodule_description description, '' interpreter, 'ID:STATUS' data_format, '' code, '0' block_mode, 2 script_mode) AS tmp 
WHERE NOT EXISTS (SELECT name, description FROM tmodule_inventory WHERE name = @tmodule_name and description = @tmodule_description and id_os = @id_os);

SET @tmodule_name = 'Security';
SET @tmodule_description = 'Hardening&#x20;plugin&#x20;for&#x20;security&#x20;compliance&#x20;analysis';
SET @id_os = 9;

INSERT INTO tmodule_inventory (`id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`)
SELECT * FROM (SELECT @id_os id_os, @tmodule_name name, @tmodule_description description, '' interpreter, 'ID:STATUS' data_format, '' code, '0' block_mode, 2 script_mode) AS tmp 
WHERE NOT EXISTS (SELECT name, description FROM tmodule_inventory WHERE name = @tmodule_name and description = @tmodule_description and id_os = @id_os);

COMMIT;
