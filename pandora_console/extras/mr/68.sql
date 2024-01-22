START TRANSACTION;

ALTER TABLE `tncm_agent_data`
ADD COLUMN `id_agent_data` int not null default 0 AFTER `script_type`;

ALTER TABLE `tusuario` CHANGE COLUMN `metaconsole_data_section` `metaconsole_data_section` TEXT NOT NULL DEFAULT '' ;

ALTER TABLE `tmensajes` ADD COLUMN `icon_notification` VARCHAR(250) NULL DEFAULT NULL AFTER `url`;


COMMIT;