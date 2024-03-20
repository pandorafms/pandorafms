START TRANSACTION;

CREATE TABLE IF NOT EXISTS `ttoken` (
  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` TEXT NOT NULL,
  `uuid` TEXT NOT NULL,
  `challenge` TEXT NOT NULL,
  `id_user` varchar(60) NOT NULL default '',
  `validity` datetime,
  `last_usage` datetime,
  PRIMARY KEY(`id`),
  FOREIGN KEY (`id_user`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4;

-- Watch out! The following field migration must be done before altering the corresponding table.
UPDATE `tevent_filter`
SET `search` = `regex`,
    `regex` = '1'
WHERE `regex` IS NOT NULL AND `regex` != '';

-- Watch out! The following alter command must be done after the previous update of this table.
ALTER TABLE `tevent_filter` MODIFY COLUMN `regex` TINYINT unsigned NOT NULL DEFAULT 0;

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

-- Update version for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.oracle';
-- Update version for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.3' WHERE `short_name` = 'pandorafms.vmware';

ALTER TABLE `tevent_sound` MODIFY COLUMN `name` text NULL;
ALTER TABLE `tevent_sound` MODIFY COLUMN `sound` text NULL;
ALTER TABLE `treport_content` MODIFY COLUMN `use_prefix_notation` tinyint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `treport_content_template` MODIFY COLUMN `use_prefix_notation` tinyint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `id_name` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `ip` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `type` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `user` text NULL;

SET @st_oum776 = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'tncm_agent_data' AND table_schema = DATABASE() AND column_name = 'id_agent_data') > 0,
    "SELECT 1",
	"ALTER TABLE `tncm_agent_data` ADD COLUMN `id_agent_data` int not null default 0 AFTER `script_type`"
));

PREPARE pr_oum776 FROM @st_oum776;
EXECUTE pr_oum776;
DEALLOCATE PREPARE pr_oum776;

ALTER TABLE `tusuario` CHANGE COLUMN `metaconsole_data_section` `metaconsole_data_section` TEXT NOT NULL DEFAULT '' ;
ALTER TABLE `tmensajes` ADD COLUMN `icon_notification` VARCHAR(250) NULL DEFAULT NULL AFTER `url`;

ALTER TABLE `tdemo_data` MODIFY `item_id` TEXT NOT NULL DEFAULT '';

UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_os": "',`item_id`,'"}') WHERE `table_name` = "tconfig_os" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_agente": "',`item_id`,'"}') WHERE `table_name` = "tagente" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_grupo": "',`item_id`,'"}') WHERE `table_name` = "tgrupo" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_agente_modulo": "',`item_id`,'"}') WHERE `table_name` = "tagente_modulo" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_module_inventory": "',`item_id`,'"}') WHERE `table_name` = "tmodule_inventory" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_agent_module_inventory": "',`item_id`,'"}') WHERE `table_name` = "tagent_module_inventory" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_graph": "',`item_id`,'"}') WHERE `table_name` = "tgraph" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tmap" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_report": "',`item_id`,'"}') WHERE `table_name` = "treport" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_rc": "',`item_id`,'"}') WHERE `table_name` = "treport_content" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "treport_content_sla_combined" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tservice" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tservice_element" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_trap": "',`item_id`,'"}') WHERE `table_name` = "ttrap" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "titem" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_gs": "',`item_id`,'"}') WHERE `table_name` = "tgraph_source" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "twidget_dashboard" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tdashboard" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tlayout" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tlayout_data" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_agente_estado": "',`item_id`,'"}') WHERE `table_name` = "tagente_estado" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "trel_item" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tplugin" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"tagente_id_agente": "',`item_id`,'"}') WHERE `table_name` = "tgis_data_status" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_tgis_map": "',`item_id`,'"}') WHERE `table_name` = "tgis_map" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_tmap_layer": "',`item_id`,'"}') WHERE `table_name` = "tgis_map_layer" AND CAST(`item_id` AS UNSIGNED) != 0;

ALTER TABLE `tagente_modulo` ADD COLUMN `disabled_by_safe_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0;

UPDATE `tncm_template` SET `vendors` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM vendors))), '"]'), `models` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM models))), '"]');
UPDATE `tncm_agent_data_template` SET `vendors` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM vendors))), '"]'), `models` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM models))), '"]');

-- Update version for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.oracle';
-- Update version for plugin mysql
UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.mysql';


SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = 'GisMap';
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,'GisMap','GisMap','Gis map','','GisMap.php');

SET @class_name = 'ITSMIncidences';
SET @unique_name = 'ITSMIncidences';
SET @description = 'Pandora ITSM tickets';
SET @page = 'ITSMIncidences.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

-- Create SNMPv3 credentials for recon tasks and update them
SET @creds_name = 'Recon-SNMP-creds-';
INSERT IGNORE INTO `tcredential_store` (`identifier`, `id_group`, `product`, `extra_1`)
    SELECT
        CONCAT(@creds_name,`id_rt`)  AS `identifier`,
        `id_group`,
        'SNMP' AS `product`,
        CONCAT(
            '{',
            '"community":"',`snmp_community`,'",',
            '"version":"',`snmp_version`,'",',
            '"securityLevelV3":"',`snmp_security_level`,'",',
            '"authUserV3":"',`snmp_auth_user`,'",',
            '"authMethodV3":"',`snmp_auth_method`,'",',
            '"authPassV3":"',`snmp_auth_pass`,'",',
            '"privacyMethodV3":"',`snmp_privacy_method`,'",',
            '"privacyPassV3":"',`snmp_privacy_pass`,'"',
            '}'
        ) AS `extra1`
    FROM `trecon_task` WHERE `snmp_version` = 3 AND `snmp_enabled` = 1
;
UPDATE `trecon_task` SET `auth_strings` = IF(`auth_strings` = '',CONCAT(@creds_name,`id_rt`),CONCAT(@creds_name,`id_rt`,',',`auth_strings`)) WHERE `snmp_version` = 3 AND `snmp_enabled` = 1;

ALTER TABLE `tdatabase` ADD COLUMN `disabled` TINYINT NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `tmetaconsole_ha_databases` (
  `node_id` int NOT NULL,
  `host` varchar(255) DEFAULT '',
  `master` tinyint unsigned DEFAULT '0',
  PRIMARY KEY (`node_id`, `host`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;
ALTER TABLE `tserver` ADD COLUMN `disabled` BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE `tuser_task_scheduled` ADD COLUMN `id_report` INT NULL AFTER `id_user_task`;
ALTER TABLE `tuser_task_scheduled` ADD COLUMN `name` VARCHAR(255) NULL AFTER `id_user_task`;


COMMIT;