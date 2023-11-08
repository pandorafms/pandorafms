START TRANSACTION;

ALTER TABLE tevent_filter ADD private_filter_user text NULL;
ALTER TABLE `ttrap` ADD COLUMN `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0;

UPDATE ttrap SET utimestamp=UNIX_TIMESTAMP(timestamp);

CREATE TABLE IF NOT EXISTS `tlog_alert` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT ,
  `description` MEDIUMTEXT,
  `order` INT UNSIGNED DEFAULT 0,
  `mode` ENUM('PASS','DROP'),
  `field1` TEXT ,
  `field2` TEXT ,
  `field3` TEXT ,
  `field4` TEXT ,
  `field5` TEXT ,
  `field6` TEXT ,
  `field7` TEXT ,
  `field8` TEXT ,
  `field9` TEXT ,
  `field10` TEXT ,
  `time_threshold` INT NOT NULL DEFAULT 86400,
  `max_alerts` INT UNSIGNED NOT NULL DEFAULT 1,
  `min_alerts` INT UNSIGNED NOT NULL DEFAULT 0,
  `time_from` time DEFAULT '00:00:00',
  `time_to` time DEFAULT '00:00:00',
  `monday` TINYINT DEFAULT 1,
  `tuesday` TINYINT DEFAULT 1,
  `wednesday` TINYINT DEFAULT 1,
  `thursday` TINYINT DEFAULT 1,
  `friday` TINYINT DEFAULT 1,
  `saturday` TINYINT DEFAULT 1,
  `sunday` TINYINT DEFAULT 1,
  `recovery_notify` TINYINT DEFAULT 0,
  `field1_recovery` TEXT,
  `field2_recovery` TEXT,
  `field3_recovery` TEXT,
  `field4_recovery` TEXT,
  `field5_recovery` TEXT,
  `field6_recovery` TEXT,
  `field7_recovery` TEXT,
  `field8_recovery` TEXT,
  `field9_recovery` TEXT,
  `field10_recovery` TEXT,
  `id_group` MEDIUMINT UNSIGNED NULL DEFAULT 0,
  `internal_counter` INT DEFAULT 0,
  `last_fired` BIGINT NOT NULL DEFAULT 0,
  `last_reference` BIGINT NOT NULL DEFAULT 0,
  `times_fired` INT NOT NULL DEFAULT 0,
  `disabled` TINYINT DEFAULT 0,
  `standby` TINYINT DEFAULT 0,
  `priority` TINYINT DEFAULT 0,
  `force_execution` TINYINT DEFAULT 0,
  `group_by` enum ('','id_agente','id_agentmodule','id_alert_am','id_grupo') DEFAULT '',
  `special_days` TINYINT DEFAULT 0,
  `disable_event` TINYINT DEFAULT 0,
  `id_template_conditions` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_template_fields` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_evaluation` BIGINT NOT NULL DEFAULT 0,
  `pool_occurrences` INT UNSIGNED NOT NULL DEFAULT 0,
  `schedule` TEXT,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tlog_rule` (
  `id_log_rule` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_log_alert` INT UNSIGNED NOT NULL,
  `operation` ENUM('NOP', 'AND','OR','XOR','NAND','NOR','NXOR'),
  `order` INT UNSIGNED DEFAULT 0,
  `window` INT NOT NULL DEFAULT 0,
  `count` INT NOT NULL DEFAULT 1,
  `name` TEXT,
  `log_content` TEXT,
  `log_source` TEXT,
  `log_agent` TEXT,
  `operator_log_content` TEXT COMMENT 'Operator for log_content',
  `operator_log_source` TEXT COMMENT 'Operator for log_source',
  `operator_log_agent` TEXT COMMENT 'Operator for log_agent',
  PRIMARY KEY  (`id_log_rule`),
  KEY `idx_id_log_alert` (`id_log_alert`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tlog_alert_action` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_log_alert` INT UNSIGNED NOT NULL,
  `id_alert_action` INT UNSIGNED NOT NULL,
  `fires_min` INT UNSIGNED DEFAULT 0,
  `fires_max` INT UNSIGNED DEFAULT 0,
  `module_action_threshold` INT NOT NULL DEFAULT 0,
  `last_execution` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_log_alert`) REFERENCES tlog_alert(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

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

ALTER TABLE tagente_modulo ADD COLUMN `made_enabled` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE tpolicy_modules ADD COLUMN `made_enabled` TINYINT UNSIGNED DEFAULT 0;

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
INSERT INTO tmodule_group (name) SELECT ('Security') WHERE NOT EXISTS (SELECT name FROM tmodule_group WHERE LOWER(name) = 'security');

ALTER TABLE tagente_modulo ADD COLUMN `last_compact` TIMESTAMP NOT NULL DEFAULT 0;

UPDATE `tevent_alert` ea INNER JOIN `tevent_rule` er ON ea.id = er.id_event_alert SET disabled=1 WHERE er.log_agent IS NOT NULL OR er.log_content IS NOT NULL OR er.log_source IS NOT NULL;

ALTER TABLE `tnetwork_explorer_filter`
MODIFY COLUMN `id` INT NOT NULL AUTO_INCREMENT;

-- Add messaging alerts

SET @command_name = 'Pandora&#x20;Google&#x20;chat';
SET @action_name = 'Pandora&#x20;Google&#x20;chat';

-- Get command ID in case it exists
SET @id_command = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
INSERT IGNORE INTO `talert_commands` (`id`, `name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES (@id_command, @command_name, '/usr/share/pandora_server/util/plugin/pandora-gchat-cli&#x20;-u&#x20;&quot;_field1_&quot;&#x20;-d&#x20;&quot;_field2_&quot;&#x20;-t&#x20;&quot;_field3_&quot;&#x20;-D&#x20;&quot;_field4_&quot;', 'Send&#x20;messages&#x20;using&#x20;Google&#x20;chat&#x20;API', 0, '["Google&#x20;chat&#x20;webhook&#x20;URL","Data&#x20;in&#x20;coma&#x20;separate&#x20;keypairs","Title","Description"]', '["","","",""]');

-- Get command ID again in case it has been created
SET @id_command = NULL;
SET @id_action = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
SELECT @id_action := `id` FROM `talert_actions` WHERE `name` = @action_name;
INSERT IGNORE INTO `talert_actions` (`id`, `name`, `id_alert_command`, `field1`, `field2`, `field3`, `field4`, `field5`, `field6`, `field7`, `field8`, `field9`, `field10`, `id_group`, `action_threshold`, `field1_recovery`, `field2_recovery`, `field3_recovery`, `field4_recovery`, `field5_recovery`, `field6_recovery`, `field7_recovery`, `field8_recovery`, `field9_recovery`, `field10_recovery`) VALUES (@id_action, @action_name, @id_command, "", "data=_data_", "[PANDORA] Alert FIRED on _agent_ / _module_", "_agent_ | _module_ | _data_ | _timestamp_", "", "", "", "", "", "", 0, 0, "", "data=_data_", "[PANDORA] Alert RECOVERED on _agent_ / _module_", "_agent_ | _module_ | _data_ | _timestamp_", "", "", "", "", "", "");

SET @command_name = 'Pandora&#x20;Slack';
SET @action_name = 'Pandora&#x20;Slack';

-- Get command ID in case it exists
SET @id_command = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
INSERT IGNORE INTO `talert_commands` (`id`, `name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES (@id_command, @command_name, '/usr/share/pandora_server/util/plugin/pandora-slack-cli&#x20;-t&#x20;&quot;TOKEN&quot;&#x20;-d&#x20;&quot;_field1_&quot;&#x20;-c&#x20;&quot;_field2_&quot;&#x20;-e&#x20;&quot;_field3_&quot;&#x20;-T&#x20;&quot;_field4_&quot;&#x20;-D&#x20;&quot;_field5_&quot;', 'Send&#x20;messages&#x20;using&#x20;Slack&#x20;API', 0, '["Data&#x20;in&#x20;coma&#x20;separate&#x20;keypairs","Slack&#x20;channel&#x20;id/name","Title&#x20;emoji","Title","Description"]', '["","",":red_circle:,Red&#x20;circle;:green_circle:,Green&#x20;circle","",""]');

-- Get command ID again in case it has been created
SET @id_command = NULL;
SET @id_action = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
SELECT @id_action := `id` FROM `talert_actions` WHERE `name` = @action_name;
INSERT IGNORE INTO `talert_actions` (`id`, `name`, `id_alert_command`, `field1`, `field2`, `field3`, `field4`, `field5`, `field6`, `field7`, `field8`, `field9`, `field10`, `id_group`, `action_threshold`, `field1_recovery`, `field2_recovery`, `field3_recovery`, `field4_recovery`, `field5_recovery`, `field6_recovery`, `field7_recovery`, `field8_recovery`, `field9_recovery`, `field10_recovery`) VALUES (@id_action, @action_name, @id_command, "data=_data_", "", ":red_circle:", "[PANDORA] Alert FIRED on _agent_ / _module_", "_agent_ | _module_ | _data_ | _timestamp_", "", "", "", "", "", 0, 0, "data=_data_", "", ":green_circle:", "[PANDORA] Alert RECOVERED on _agent_ / _module_", "_agent_ | _module_ | _data_ | _timestamp_", "", "", "", "", "");

SET @command_name = 'Pandora&#x20;Telegram';
SET @action_name = 'Pandora&#x20;Telegram';

-- Get command ID in case it exists
SET @id_command = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
INSERT IGNORE INTO `talert_commands` (`id`, `name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES (@id_command, @command_name, '/usr/share/pandora_server/util/plugin/pandora-telegram-cli&#x20;-t&#x20;&quot;TOKEN&quot;&#x20;-c&#x20;&quot;_field1_&quot;&#x20;-m&#x20;&quot;_field2_&quot;', 'Send&#x20;messages&#x20;using&#x20;Telegram&#x20;API', 0, '["Chat&#x20;ID","Message"]', '["",""]');

-- Get command ID again in case it has been created
SET @id_command = NULL;
SET @id_action = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
SELECT @id_action := `id` FROM `talert_actions` WHERE `name` = @action_name;
INSERT IGNORE INTO `talert_actions` (`id`, `name`, `id_alert_command`, `field1`, `field2`, `field3`, `field4`, `field5`, `field6`, `field7`, `field8`, `field9`, `field10`, `id_group`, `action_threshold`, `field1_recovery`, `field2_recovery`, `field3_recovery`, `field4_recovery`, `field5_recovery`, `field6_recovery`, `field7_recovery`, `field8_recovery`, `field9_recovery`, `field10_recovery`) VALUES (@id_action, @action_name, @id_command, "", "[PANDORA] Alert FIRED on _agent_ / _module_ / _tiemstamp_ / _data_", "", "", "", "", "", "", "", "", 0, 0, "", "[PANDORA] Alert RECOVERED on _agent_ / _module_ / _tiemstamp_ / _data_", "", "", "", "", "", "", "", "");

SET @command_name = 'Pandora&#x20;ilert';
SET @action_name = 'Pandora&#x20;ilert';

-- Get command ID in case it exists
SET @id_command = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
INSERT IGNORE INTO `talert_commands` (`id`, `name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES (@id_command, @command_name, '/usr/share/pandora_server/util/plugin/pandora_ilert&#x20;-a&#x20;&quot;API_KEY&quot;&#x20;-t&#x20;&quot;_field1_&quot;&#x20;-k&#x20;&quot;_field2_&quot;&#x20;-T&#x20;&quot;_field3_&quot;&#x20;-d&#x20;&quot;_field4_&quot;&#x20;-A&#x20;&quot;_agentname_&quot;&#x20;-m&#x20;&quot;_module_&quot;&#x20;-p&#x20;&quot;_alert_text_severity_&quot;&#x20;-D&#x20;&quot;_data_&quot;&#x20;-C&#x20;&quot;_timestamp_&quot;', 'Send&#x20;SMS&#x20;using&#x20;ilert&#x20;API:&#x20;https://docs.ilert.com/integrations/pandorafms/', 0, '["Event&#x20;type","Event&#x20;title","Title","Description"]', '["alert,Alert;resolved,Resolved","","",""]');

-- Get command ID again in case it has been created
SET @id_command = NULL;
SET @id_action = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
SELECT @id_action := `id` FROM `talert_actions` WHERE `name` = @action_name;
INSERT IGNORE INTO `talert_actions` (`id`, `name`, `id_alert_command`, `field1`, `field2`, `field3`, `field4`, `field5`, `field6`, `field7`, `field8`, `field9`, `field10`, `id_group`, `action_threshold`, `field1_recovery`, `field2_recovery`, `field3_recovery`, `field4_recovery`, `field5_recovery`, `field6_recovery`, `field7_recovery`, `field8_recovery`, `field9_recovery`, `field10_recovery`) VALUES (@id_action, @action_name, @id_command, "alert", "", "[PANDORA] Alert FIRED on _agent_ / _module_", "_agent_ | _module_ | _data_ | _timestamp_", "", "", "", "", "", "", 0, 0, "resolved", "", "[PANDORA] Alert RECOVERED on _agent_ / _module_", "_agent_ | _module_ | _data_ | _timestamp_", "", "", "", "", "", "");

SET @command_name = 'Pandora&#x20;Vonage';
SET @action_name = 'Pandora&#x20;Vonage';

-- Get command ID in case it exists
SET @id_command = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
INSERT IGNORE INTO `talert_commands` (`id`, `name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES (@id_command, @command_name, '/usr/share/pandora_server/util/plugin/pandora_vonage&#x20;-a&#x20;&quot;API_KEY&quot;&#x20;-s&#x20;&quot;SECRET&quot;&#x20;-f&#x20;&quot;FROM_ALIAS&quot;&#x20;-n&#x20;&quot;_field1_&quot;&#x20;-m&#x20;&quot;_field2_&quot;', 'Send&#x20;SMS&#x20;using&#x20;Vonage&#x20;API:&#x20;https://www.vonage.com/communications-apis/sms/', 0, '["Phone&#x20;number","Message"]', '["",""]');

-- Get command ID again in case it has been created
SET @id_command = NULL;
SET @id_action = NULL;
SELECT @id_command := `id` FROM `talert_commands` WHERE `name` = @command_name;
SELECT @id_action := `id` FROM `talert_actions` WHERE `name` = @action_name;
INSERT IGNORE INTO `talert_actions` (`id`, `name`, `id_alert_command`, `field1`, `field2`, `field3`, `field4`, `field5`, `field6`, `field7`, `field8`, `field9`, `field10`, `id_group`, `action_threshold`, `field1_recovery`, `field2_recovery`, `field3_recovery`, `field4_recovery`, `field5_recovery`, `field6_recovery`, `field7_recovery`, `field8_recovery`, `field9_recovery`, `field10_recovery`) VALUES (@id_action, @action_name, @id_command, "", "[PANDORA] Alert FIRED on _agent_ / _module_ / _tiemstamp_ / _data_", "", "", "", "", "", "", "", "", 0, 0, "", "[PANDORA] Alert RECOVERED on _agent_ / _module_ / _tiemstamp_ / _data_", "", "", "", "", "", "", "", "");

COMMIT;
