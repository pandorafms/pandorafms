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

UPDATE `twelcome_tip`
	SET title = 'Scheduled&#x20;downtimes',
         url = 'https://pandorafms.com/manual/en/documentation/04_using/11_managing_and_administration#scheduled_downtimes'
	WHERE title = 'planned&#x20;stops';

UPDATE tagente_modulo SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tpolicy_modules SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tnetwork_component SET `tcp_send` = '2c' WHERE `tcp_send` = '2';

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

ALTER TABLE `treport_content`  ADD COLUMN `cat_security_hardening` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content`  ADD COLUMN `ignore_skipped` INT NOT NULL DEFAULT 0;
ALTER TABLE `treport_content`  ADD COLUMN `status_of_check` TINYTEXT;

COMMIT;
