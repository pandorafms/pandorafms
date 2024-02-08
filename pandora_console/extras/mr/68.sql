START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tmerge_error` (
    `id` int(10) NOT NULL auto_increment,
    `id_node` int(10) default 0,
    `phase` int(10) default 0,
    `step` int(10) default 0,
    `msg` LONGTEXT default "",
    `action` text default "",
    `utimestamp` int(20) unsigned NOT NULL default 0,
    PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tmerge_error` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `tmerge_steps` (
    `id` int(10) NOT NULL auto_increment,
    `id_node` int(10) default 0,
    `phase` int(10) default 0,
    `total` int(10) default 0,
    `step` int(10) default 0,
    `debug` varchar(1024) default "",
    `action` varchar(100) default "",
    `affected` varchar(100) default "",
    `query` mediumtext default "",
    `utimestamp` int(20) unsigned NOT NULL default 0,
    PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tmerge_steps` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `tmerge_queries` (
    `steps` int(10) NOT NULL auto_increment,
    `action` varchar(100) default "",
    `affected` varchar(100) default "",
    `utimestamp` int(20) unsigned NOT NULL default 0,
    `query` LONGTEXT NOT NULL default "",
    PRIMARY KEY  (`steps`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tmerge_queries` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

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

UPDATE `tncm_template` SET `vendors` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM vendors))), '"]'), `models` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM models))), '"]');
UPDATE `tncm_agent_data_template` SET `vendors` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM vendors))), '"]'), `models` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM models))), '"]');

-- Update version for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.oracle';

SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = 'GisMap';
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,'GisMap','GisMap','Gis map','','GisMap.php');

COMMIT;