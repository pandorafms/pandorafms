START TRANSACTION;

-- Update version for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.oracle';

ALTER TABLE `tevent_sound` MODIFY COLUMN `name` text NULL;
ALTER TABLE `tevent_sound` MODIFY COLUMN `sound` text NULL;
ALTER TABLE `treport_content` MODIFY COLUMN `use_prefix_notation` tinyint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `treport_content_template` MODIFY COLUMN `use_prefix_notation` tinyint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `id_name` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `ip` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `type` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `user` text NULL;
ALTER TABLE `tncm_agent_data`
ADD COLUMN `id_agent_data` int not null default 0 AFTER `script_type`;
ALTER TABLE `tusuario` CHANGE COLUMN `metaconsole_data_section` `metaconsole_data_section` TEXT NOT NULL DEFAULT '' ;
ALTER TABLE `tmensajes` ADD COLUMN `icon_notification` VARCHAR(250) NULL DEFAULT NULL AFTER `url`;

COMMIT;