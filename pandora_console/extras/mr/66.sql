START TRANSACTION;

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

COMMIT;
