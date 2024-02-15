-- Active: 1696349857571@@172.16.0.2@3306
START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tdemo_data` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT UNSIGNED NULL DEFAULT NULL,
  `table_name` VARCHAR(64) NULL DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

SET @class_name = 'AgentHive';
SET @unique_name = 'AgentHive';
SET @description = 'Agents hive';
SET @page = 'AgentHive.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'AvgSumMaxMinModule';
SET @unique_name = 'AvgSumMaxMinModule';
SET @description = 'Avg|Sum|Max|Min Module Data';
SET @page = 'AvgSumMaxMinModule.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'BasicChart';
SET @unique_name = 'BasicChart';
SET @description = 'Basic chart';
SET @page = 'BasicChart.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'BlockHistogram';
SET @unique_name = 'BlockHistogram';
SET @description = 'Block histogram';
SET @page = 'BlockHistogram.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ColorModuleTabs';
SET @unique_name = 'ColorModuleTabs';
SET @description = 'Color tabs modules';
SET @page = 'ColorModuleTabs.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'DataMatrix';
SET @unique_name = 'DataMatrix';
SET @description = 'Data Matrix';
SET @page = 'DataMatrix.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'EventCardboard';
SET @unique_name = 'EventCardboard';
SET @description = 'Event cardboard';
SET @page = 'EventCardboard.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'GroupedMeterGraphs';
SET @unique_name = 'GroupedMeterGraphs';
SET @description = 'Grouped meter graphs';
SET @page = 'GroupedMeterGraphs.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ModulesByStatus';
SET @unique_name = 'ModulesByStatus';
SET @description = 'Modules by status';
SET @page = 'ModulesByStatus.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'AgentModuleWidget';
SET @unique_name = 'agent_module';
SET @description = 'Agent/Module View';
SET @page = 'agent_module.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'AlertsFiredWidget';
SET @unique_name = 'alerts_fired';
SET @description = 'Triggered alerts report';
SET @page = 'alerts_fired.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ClockWidget';
SET @unique_name = 'clock';
SET @description = 'Clock';
SET @page = 'clock.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'CustomGraphWidget';
SET @unique_name = 'custom_graph';
SET @description = 'Defined custom graph';
SET @page = 'custom_graph.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'EventsListWidget';
SET @unique_name = 'events_list';
SET @description = 'List of latest events';
SET @page = 'events_list.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'WelcomeWidget';
SET @unique_name = 'example';
SET @description = 'Welcome message to Pandora&#x20;FMS';
SET @page = 'example.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'GraphModuleHistogramWidget';
SET @unique_name = 'graph_module_histogram';
SET @description = 'Module histogram';
SET @page = 'graph_module_histogram.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'GroupsStatusWidget';
SET @unique_name = 'groups_status';
SET @description = 'General group status';
SET @page = 'groups_status.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'GroupsStatusMapWidget';
SET @unique_name = 'groups_status_map';
SET @description = 'Group status map';
SET @page = 'groups_status_map.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'HeatmapWidget';
SET @unique_name = 'heatmap';
SET @description = 'Heatmap';
SET @page = 'heatmap.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'InventoryWidget';
SET @unique_name = 'inventory';
SET @description = 'Inventory';
SET @page = 'inventory.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'MapsMadeByUser';
SET @unique_name = 'maps_made_by_user';
SET @description = 'Visual Console';
SET @page = 'maps_made_by_user.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'MapsStatusWidget';
SET @unique_name = 'maps_status';
SET @description = 'General visual maps report';
SET @page = 'maps_status.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ModuleIconWidget';
SET @unique_name = 'module_icon';
SET @description = 'Icon and module value';
SET @page = 'module_icon.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ModuleStatusWidget';
SET @unique_name = 'module_status';
SET @description = 'Module status';
SET @page = 'module_status.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ModuleTableValueWidget';
SET @unique_name = 'module_table_value';
SET @description = 'Module in a table';
SET @page = 'module_table_value.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ModuleValueWidget';
SET @unique_name = 'module_value';
SET @description = 'Module value';
SET @page = 'module_value.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'MonitorHealthWidget';
SET @unique_name = 'monitor_health';
SET @description = 'Global health info';
SET @page = 'monitor_health.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'Netflow';
SET @unique_name = 'netflow';
SET @description = 'Netflow';
SET @page = 'netflow.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'NetworkMapWidget';
SET @unique_name = 'network_map';
SET @description = 'Network map';
SET @page = 'network_map.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'OsQuickReportWidget';
SET @unique_name = 'os_quick_report';
SET @description = 'OS quick report';
SET @page = 'os_quick_report.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'PostWidget';
SET @unique_name = 'post';
SET @description = 'Panel with a message';
SET @page = 'post.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ReportsWidget';
SET @unique_name = 'reports';
SET @description = 'Custom report';
SET @page = 'reports.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ServiceMapWidget';
SET @unique_name = 'service_map';
SET @description = 'Service map';
SET @page = 'service_map.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ServiceViewWidget';
SET @unique_name = 'service_view';
SET @description = 'Services view';
SET @page = 'service_view.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'SingleGraphWidget';
SET @unique_name = 'single_graph';
SET @description = 'Agent module graph';
SET @page = 'single_graph.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'SLAPercentWidget';
SET @unique_name = 'sla_percent';
SET @description = 'SLA percentage';
SET @page = 'sla_percent.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'SystemGroupStatusWidget';
SET @unique_name = 'system_group_status';
SET @description = 'Groups status';
SET @page = 'system_group_status.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'TacticalWidget';
SET @unique_name = 'tactical';
SET @description = 'Tactical view';
SET @page = 'tactical.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'TopNWidget';
SET @unique_name = 'top_n';
SET @description = 'Top N of agent modules';
SET @page = 'top_n.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'TopNEventByGroupWidget';
SET @unique_name = 'top_n_events_by_group';
SET @description = 'Top N events by agent';
SET @page = 'top_n_events_by_group.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'TopNEventByModuleWidget';
SET @unique_name = 'top_n_events_by_module';
SET @description = 'Top N events by module';
SET @page = 'top_n_events_by_module.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'TreeViewWidget';
SET @unique_name = 'tree_view';
SET @description = 'Tree view';
SET @page = 'tree_view.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'UrlWidget';
SET @unique_name = 'url';
SET @description = 'URL content';
SET @page = 'url.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'WuxWidget';
SET @unique_name = 'wux_transaction';
SET @description = 'Agent WUX transaction';
SET @page = 'wux_transaction.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'WuxStatsWidget';
SET @unique_name = 'wux_transaction_stats';
SET @description = 'WUX transaction stats';
SET @page = 'wux_transaction_stats.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'SecurityHardening';
SET @unique_name = 'security_hardening';
SET @description = 'Security Hardening';
SET @page = 'security_hardening.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

SET @class_name = 'ServiceLevelWidget';
SET @unique_name = 'service_level';
SET @description = 'Service Level';
SET @page = 'service_level.php';
SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = @unique_name;
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,@class_name,@unique_name,@description,'',@page);

DELETE FROM `tconfig` WHERE `token` LIKE 'translate_string_extension_installed';

CREATE TABLE IF NOT EXISTS `textension_translate_string` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lang` VARCHAR(10) NOT NULL ,
  `string` TEXT ,
  `translation` TEXT ,
  PRIMARY KEY (`id`),
  KEY `lang_index` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

DELETE FROM `tconfig` WHERE `token` LIKE 'files_repo_installed';

CREATE TABLE IF NOT EXISTS `tfiles_repo` (
	`id` int(5) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL,
	`description` varchar(500) NULL default '',
	`hash` varchar(8) NULL default '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tfiles_repo_group` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_file` int(5) unsigned NOT NULL,
	`id_group` int(4) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`id_file`) REFERENCES tfiles_repo(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tncm_queue`
ADD COLUMN `id_agent_data` bigint unsigned AFTER `id_script`;

ALTER TABLE `tncm_queue`
ADD COLUMN `snippet` TEXT NULL AFTER `scheduled`;

CREATE TABLE IF NOT EXISTS `tncm_agent_data_template` (
    `id` SERIAL,
    `name` TEXT,
    `vendors` TEXT,
    `models` TEXT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tncm_agent`
ADD COLUMN `id_agent_data_template` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id_template`;

CREATE TABLE IF NOT EXISTS `tncm_agent_data_template_scripts` (
    `id` SERIAL,
    `id_agent_data_template` BIGINT UNSIGNED NOT NULL,
    `id_script` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_agent_data_template`) REFERENCES `tncm_agent_data_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_script`) REFERENCES `tncm_script`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tncm_agent`
ADD COLUMN `agent_data_cron_interval` VARCHAR(100) NULL DEFAULT '' AFTER `cron_interval`;

ALTER TABLE `tncm_agent`
ADD COLUMN `agent_data_event_on_change` INT UNSIGNED NULL DEFAULT NULL AFTER `event_on_change`;

ALTER TABLE `treport_content`
ADD COLUMN `ncm_agents` MEDIUMTEXT NULL AFTER `status_of_check`;

-- Add needed snippet script to queue tasks.
SET @type_snippet = 6;
SELECT @id_snippet := `id` FROM `tncm_script` WHERE `type` = @type_snippet;
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_snippet, @type_snippet, 'snippet');

-- Add new vendor and model
SET @vendor_name = 'Cisco';
SET @model_name = 'Cisco-Generic';
SET @template_name = 'Cisco-Generic';
SET @agent_data_template_name = 'Cisco-Generic';
SET @script_test = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_get_config = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;term&#x20;length&#x20;0&#92;n&#x0d;&#x0a;capture:show&#x20;running-config&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_set_config = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;term&#x20;length&#x20;0&#92;n&#x0d;&#x0a;config&#x20;terminal&#92;n&#x0d;&#x0a;_applyconfigbackup_&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_get_firmware = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;term&#x20;length&#x20;0&#92;n&#x0d;&#x0a;capture:show&#x20;version&#x20;|&#x20;i&#x20;IOS&#x20;Software&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_set_firmware = 'copy&#x20;tftp&#x20;flash&#92;n&#x0d;&#x0a;expect:&#92;]&#92;?&#x0d;&#x0a;_TFTP_SERVER_IP_&#92;n&#x0d;&#x0a;expect:&#92;]&#92;?&#x0d;&#x0a;_SOURCE_FILE_NAME_&#92;n&#x0d;&#x0a;expect:&#92;]&#92;?&#x0d;&#x0a;firmware.bin&#92;n&#x0d;&#x0a;show&#x20;flash&#92;n&#x0d;&#x0a;reload&#92;n&#x0d;&#x0a;expect:confirm&#x0d;&#x0a;y&#92;n&#x0d;&#x0a;config&#x20;terminal&#92;n&#x0d;&#x0a;boot&#x20;system&#x20;firmware.bin&#92;n';
SET @script_custom = '';
SET @script_os_version = @script_get_firmware;

-- Try to insert vendor
INSERT IGNORE INTO `tncm_vendor` (`id`, `name`, `icon`) VALUES ('', @vendor_name, '');
-- Get vendor ID
SELECT @id_vendor := `id` FROM `tncm_vendor` WHERE `name` = @vendor_name;

-- Try to insert model
INSERT IGNORE INTO `tncm_model` (`id`, `id_vendor`, `name`) VALUES ('', @id_vendor, @model_name);
-- Get model ID
SELECT @id_model := `id` FROM `tncm_model` WHERE `id_vendor` = @id_vendor AND `name` = @model_name;

-- Get template ID if exists
SET @id_template = NULL;
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;
-- Try to insert template
INSERT IGNORE INTO `tncm_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_template, @template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get template ID again if inserted
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;

-- Get agent data template ID if exists
SET @id_agent_data_template = NULL;
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;
-- Try to insert agent data template
INSERT IGNORE INTO `tncm_agent_data_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_agent_data_template, @agent_data_template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get agent data template ID again if inserted
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;

-- Get test script ID if exists
SET @id_script_test = NULL;
SET @script_type = 0;
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;
-- Try to insert test script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_test, @script_type, @script_test);
-- Get test script ID again if inserted
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;

-- Get get_config script ID if exists
SET @id_script_get_config = NULL;
SET @script_type = 1;
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;
-- Try to insert get_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_config, @script_type, @script_get_config);
-- Get get_config script ID again if inserted
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;

-- Get set_config script ID if exists
SET @id_script_set_config = NULL;
SET @script_type = 2;
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;
-- Try to insert set_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_config, @script_type, @script_set_config);
-- Get set_config script ID again if inserted
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;

-- Get get_firmware script ID if exists
SET @id_script_get_firmware = NULL;
SET @script_type = 3;
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;
-- Try to insert get_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_firmware, @script_type, @script_get_firmware);
-- Get get_firmware script ID again if inserted
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;

-- Get set_firmware script ID if exists
SET @id_script_set_firmware = NULL;
SET @script_type = 4;
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;
-- Try to insert set_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_firmware, @script_type, @script_set_firmware);
-- Get set_firmware script ID again if inserted
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;

-- Get custom script ID if exists
SET @id_script_custom = NULL;
SET @script_type = 5;
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;
-- Try to insert custom script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_custom, @script_type, @script_custom);
-- Get custom script ID again if inserted
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;

-- Get os_version script ID if exists
SET @id_script_os_version = NULL;
SET @script_type = 7;
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;
-- Try to insert os_version script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_os_version, @script_type, @script_os_version);
-- Get os_version script ID again if inserted
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;

-- Get template scripts ID if exists
SET @id_ts_test = NULL;
SELECT @id_ts_test := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_test;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_test, @id_template, @id_script_test);

-- Get template scripts ID if exists
SET @id_ts_get_config = NULL;
SELECT @id_ts_get_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_config, @id_template, @id_script_get_config);

-- Get template scripts ID if exists
SET @id_ts_set_config = NULL;
SELECT @id_ts_set_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_config, @id_template, @id_script_set_config);

-- Get template scripts ID if exists
SET @id_ts_get_firmware = NULL;
SELECT @id_ts_get_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_firmware, @id_template, @id_script_get_firmware);

-- Get template scripts ID if exists
SET @id_ts_set_firmware = NULL;
SELECT @id_ts_set_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_firmware, @id_template, @id_script_set_firmware);

-- Get template scripts ID if exists
SET @id_ts_custom = NULL;
SELECT @id_ts_custom := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_custom;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_custom, @id_template, @id_script_custom);

-- Get template scripts ID if exists
SET @id_ts_os_version = NULL;
SELECT @id_ts_os_version := `id` FROM `tncm_agent_data_template_scripts` WHERE `id_agent_data_template` = @id_template AND `id_script` = @id_script_os_version;
-- Try to insert
INSERT IGNORE INTO `tncm_agent_data_template_scripts` (`id`, `id_agent_data_template`, `id_script`) VALUES (@id_ts_os_version, @id_agent_data_template, @id_script_os_version);

-- Add new vendor and model
SET @vendor_name = 'Juniper';
SET @model_name = 'Juniper-Generic';
SET @template_name = 'Juniper-Generic';
SET @agent_data_template_name = 'Juniper-Generic';
SET @script_test = 'expect:root@%&#x0d;&#x0a;cli&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_get_config = 'expect:root@%&#x0d;&#x0a;cli&#92;n&#x0d;&#x0a;expect:root&gt;&#x0d;&#x0a;capture:show&#x20;configuration&#x20;|&#x20;no-more&#92;n&#x0d;&#x0a;capture:&#92;n&#x0d;&#x0a;quit&#92;n&#x0d;&#x0a;expect:root@%&#x0d;&#x0a;exit&#92;n';
SET @script_set_config = 'expect:root@%&#x0d;&#x0a;cli&#92;n&#x0d;&#x0a;expect:root&gt;&#x0d;&#x0a;configure&#92;n&#x0d;&#x0a;load&#x20;override&#x20;terminal&#92;n&#x0d;&#x0a;_applyconfigbackup_&#92;n&#x0d;&#x0a;commit&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_get_firmware = 'expect:root@%&#x0d;&#x0a;cli&#92;n&#x0d;&#x0a;expect:root&gt;&#x0d;&#x0a;capture:show&#x20;version|match&#x20;Junos:&#92;n&#x0d;&#x0a;capture:&#x20;&#92;n&#x0d;&#x0a;quit&#92;n&#x0d;&#x0a;expect:root@%&#x0d;&#x0a;exit&#92;n';
SET @script_set_firmware = 'expect:root@%&#x0d;&#x0a;cli&#92;n&#x0d;&#x0a;expect:root&gt;&#x0d;&#x0a;save&#x20;software&#x20;from&#x20;tftp&#x20;_TFTP_SERVER_IP_&#x20;_FIRMWARE_NAME_&#x20;to&#x20;flash&#92;n&#x0d;&#x0a;reset&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_custom = '';
SET @script_os_version = @script_get_firmware;

-- Try to insert vendor
INSERT IGNORE INTO `tncm_vendor` (`id`, `name`, `icon`) VALUES ('', @vendor_name, '');
-- Get vendor ID
SELECT @id_vendor := `id` FROM `tncm_vendor` WHERE `name` = @vendor_name;

-- Try to insert model
INSERT IGNORE INTO `tncm_model` (`id`, `id_vendor`, `name`) VALUES ('', @id_vendor, @model_name);
-- Get model ID
SELECT @id_model := `id` FROM `tncm_model` WHERE `id_vendor` = @id_vendor AND `name` = @model_name;

-- Get template ID if exists
SET @id_template = NULL;
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;
-- Try to insert template
INSERT IGNORE INTO `tncm_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_template, @template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get template ID again if inserted
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;

-- Get agent data template ID if exists
SET @id_agent_data_template = NULL;
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;
-- Try to insert agent data template
INSERT IGNORE INTO `tncm_agent_data_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_agent_data_template, @agent_data_template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get agent data template ID again if inserted
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;

-- Get test script ID if exists
SET @id_script_test = NULL;
SET @script_type = 0;
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;
-- Try to insert test script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_test, @script_type, @script_test);
-- Get test script ID again if inserted
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;

-- Get get_config script ID if exists
SET @id_script_get_config = NULL;
SET @script_type = 1;
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;
-- Try to insert get_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_config, @script_type, @script_get_config);
-- Get get_config script ID again if inserted
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;

-- Get set_config script ID if exists
SET @id_script_set_config = NULL;
SET @script_type = 2;
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;
-- Try to insert set_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_config, @script_type, @script_set_config);
-- Get set_config script ID again if inserted
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;

-- Get get_firmware script ID if exists
SET @id_script_get_firmware = NULL;
SET @script_type = 3;
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;
-- Try to insert get_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_firmware, @script_type, @script_get_firmware);
-- Get get_firmware script ID again if inserted
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;

-- Get set_firmware script ID if exists
SET @id_script_set_firmware = NULL;
SET @script_type = 4;
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;
-- Try to insert set_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_firmware, @script_type, @script_set_firmware);
-- Get set_firmware script ID again if inserted
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;

-- Get custom script ID if exists
SET @id_script_custom = NULL;
SET @script_type = 5;
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;
-- Try to insert custom script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_custom, @script_type, @script_custom);
-- Get custom script ID again if inserted
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;

-- Get os_version script ID if exists
SET @id_script_os_version = NULL;
SET @script_type = 7;
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;
-- Try to insert os_version script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_os_version, @script_type, @script_os_version);
-- Get os_version script ID again if inserted
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;

-- Get template scripts ID if exists
SET @id_ts_test = NULL;
SELECT @id_ts_test := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_test;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_test, @id_template, @id_script_test);

-- Get template scripts ID if exists
SET @id_ts_get_config = NULL;
SELECT @id_ts_get_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_config, @id_template, @id_script_get_config);

-- Get template scripts ID if exists
SET @id_ts_set_config = NULL;
SELECT @id_ts_set_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_config, @id_template, @id_script_set_config);

-- Get template scripts ID if exists
SET @id_ts_get_firmware = NULL;
SELECT @id_ts_get_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_firmware, @id_template, @id_script_get_firmware);

-- Get template scripts ID if exists
SET @id_ts_set_firmware = NULL;
SELECT @id_ts_set_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_firmware, @id_template, @id_script_set_firmware);

-- Get template scripts ID if exists
SET @id_ts_custom = NULL;
SELECT @id_ts_custom := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_custom;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_custom, @id_template, @id_script_custom);

-- Get template scripts ID if exists
SET @id_ts_os_version = NULL;
SELECT @id_ts_os_version := `id` FROM `tncm_agent_data_template_scripts` WHERE `id_agent_data_template` = @id_template AND `id_script` = @id_script_os_version;
-- Try to insert
INSERT IGNORE INTO `tncm_agent_data_template_scripts` (`id`, `id_agent_data_template`, `id_script`) VALUES (@id_ts_os_version, @id_agent_data_template, @id_script_os_version);

-- Add new vendor and model
SET @vendor_name = 'Palo&#x20;Alto';
SET @model_name = 'Palo&#x20;Alto-Generic';
SET @template_name = 'Palo&#x20;Alto-Generic';
SET @agent_data_template_name = 'Palo&#x20;Alto-Generic';
SET @script_test = 'sleep:1&#x0d;&#x0a;exit&#92;n';
SET @script_get_config = 'set&#x20;cli&#x20;pager&#x20;off&#x20;&#92;n&#x0d;&#x0a;capture:show&#x20;config&#x20;running&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_set_config = 'set&#x20;cli&#x20;terminal&#x20;width&#x20;500&#92;n&#x0d;&#x0a;set&#x20;cli&#x20;scripting-mode&#x20;on&#92;n&#x0d;&#x0a;configure&#92;n&#x0d;&#x0a;_applyconfigbackup_&#92;n&#x0d;&#x0a;commit&#92;n';
SET @script_get_firmware = 'set&#x20;cli&#x20;pager&#x20;off&#x20;&#92;n&#x0d;&#x0a;capture:show&#x20;system&#x20;info&#x20;|&#x20;match&#x20;app-version:&#92;n&#x0d;&#x0a;sleep:1&#x20;&#x0d;&#x0a;expect:app-version:&#92;s*&#x0d;&#x0a;exit&#x20;&#92;n';
SET @script_set_firmware = 'tftp&#x20;import&#x20;software&#x20;from&#x20;_TFTP_SERVER_IP_&#x20;file&#x20;_FIRMWARE_NAME_&#92;n&#x0d;&#x0a;request&#x20;system&#x20;software&#x20;install&#x20;version&#92;n&#x0d;&#x0a;reboot&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_custom = '';
SET @script_os_version = @script_get_firmware;

-- Try to insert vendor
INSERT IGNORE INTO `tncm_vendor` (`id`, `name`, `icon`) VALUES ('', @vendor_name, '');
-- Get vendor ID
SELECT @id_vendor := `id` FROM `tncm_vendor` WHERE `name` = @vendor_name;

-- Try to insert model
INSERT IGNORE INTO `tncm_model` (`id`, `id_vendor`, `name`) VALUES ('', @id_vendor, @model_name);
-- Get model ID
SELECT @id_model := `id` FROM `tncm_model` WHERE `id_vendor` = @id_vendor AND `name` = @model_name;

-- Get template ID if exists
SET @id_template = NULL;
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;
-- Try to insert template
INSERT IGNORE INTO `tncm_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_template, @template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get template ID again if inserted
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;

-- Get agent data template ID if exists
SET @id_agent_data_template = NULL;
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;
-- Try to insert agent data template
INSERT IGNORE INTO `tncm_agent_data_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_agent_data_template, @agent_data_template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get agent data template ID again if inserted
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;

-- Get test script ID if exists
SET @id_script_test = NULL;
SET @script_type = 0;
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;
-- Try to insert test script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_test, @script_type, @script_test);
-- Get test script ID again if inserted
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;

-- Get get_config script ID if exists
SET @id_script_get_config = NULL;
SET @script_type = 1;
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;
-- Try to insert get_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_config, @script_type, @script_get_config);
-- Get get_config script ID again if inserted
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;

-- Get set_config script ID if exists
SET @id_script_set_config = NULL;
SET @script_type = 2;
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;
-- Try to insert set_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_config, @script_type, @script_set_config);
-- Get set_config script ID again if inserted
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;

-- Get get_firmware script ID if exists
SET @id_script_get_firmware = NULL;
SET @script_type = 3;
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;
-- Try to insert get_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_firmware, @script_type, @script_get_firmware);
-- Get get_firmware script ID again if inserted
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;

-- Get set_firmware script ID if exists
SET @id_script_set_firmware = NULL;
SET @script_type = 4;
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;
-- Try to insert set_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_firmware, @script_type, @script_set_firmware);
-- Get set_firmware script ID again if inserted
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;

-- Get custom script ID if exists
SET @id_script_custom = NULL;
SET @script_type = 5;
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;
-- Try to insert custom script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_custom, @script_type, @script_custom);
-- Get custom script ID again if inserted
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;

-- Get os_version script ID if exists
SET @id_script_os_version = NULL;
SET @script_type = 7;
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;
-- Try to insert os_version script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_os_version, @script_type, @script_os_version);
-- Get os_version script ID again if inserted
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;

-- Get template scripts ID if exists
SET @id_ts_test = NULL;
SELECT @id_ts_test := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_test;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_test, @id_template, @id_script_test);

-- Get template scripts ID if exists
SET @id_ts_get_config = NULL;
SELECT @id_ts_get_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_config, @id_template, @id_script_get_config);

-- Get template scripts ID if exists
SET @id_ts_set_config = NULL;
SELECT @id_ts_set_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_config, @id_template, @id_script_set_config);

-- Get template scripts ID if exists
SET @id_ts_get_firmware = NULL;
SELECT @id_ts_get_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_firmware, @id_template, @id_script_get_firmware);

-- Get template scripts ID if exists
SET @id_ts_set_firmware = NULL;
SELECT @id_ts_set_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_firmware, @id_template, @id_script_set_firmware);

-- Get template scripts ID if exists
SET @id_ts_custom = NULL;
SELECT @id_ts_custom := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_custom;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_custom, @id_template, @id_script_custom);

-- Get template scripts ID if exists
SET @id_ts_os_version = NULL;
SELECT @id_ts_os_version := `id` FROM `tncm_agent_data_template_scripts` WHERE `id_agent_data_template` = @id_template AND `id_script` = @id_script_os_version;
-- Try to insert
INSERT IGNORE INTO `tncm_agent_data_template_scripts` (`id`, `id_agent_data_template`, `id_script`) VALUES (@id_ts_os_version, @id_agent_data_template, @id_script_os_version);

-- Add new vendor and model
SET @vendor_name = 'A10';
SET @model_name = 'A10-Generic';
SET @template_name = 'A10-Generic';
SET @agent_data_template_name = 'A10-Generic';
SET @script_test = 'sleep:1&#x0d;&#x0a;enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n';
SET @script_get_config = 'sleep:1&#x0d;&#x0a;enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;capture:show&#x20;running-config&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_set_config = 'sleep:1&#x0d;&#x0a;enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;configure&#92;n&#x0d;&#x0a;_applyconfigbackup_&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_get_firmware = 'sleep:1&#x0d;&#x0a;enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;capture:show&#x20;version&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_set_firmware = 'sleep:1&#x0d;&#x0a;enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;configure&#92;n&#x0d;&#x0a;expect:&#40;config&#41;&#x0d;&#x0a;restore&#x20;_TFTP_SERVER_IP_/_FIRMWARE_NAME_&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;expect:skip&#x20;port&#x20;map&#x0d;&#x0a;yes&#92;n&#x0d;&#x0a;expect:&#x20;see&#x20;the&#x20;diff&#x0d;&#x0a;yes&#92;n&#x0d;&#x0a;sleep:1&#x0d;&#x0a;expect:Proceed&#x20;with&#x20;reboot&#x0d;&#x0a;yes&#92;n&#x0d;&#x0a;expect:eof';
SET @script_custom = '';
SET @script_os_version = @script_get_firmware;

-- Try to insert vendor
INSERT IGNORE INTO `tncm_vendor` (`id`, `name`, `icon`) VALUES ('', @vendor_name, '');
-- Get vendor ID
SELECT @id_vendor := `id` FROM `tncm_vendor` WHERE `name` = @vendor_name;

-- Try to insert model
INSERT IGNORE INTO `tncm_model` (`id`, `id_vendor`, `name`) VALUES ('', @id_vendor, @model_name);
-- Get model ID
SELECT @id_model := `id` FROM `tncm_model` WHERE `id_vendor` = @id_vendor AND `name` = @model_name;

-- Get template ID if exists
SET @id_template = NULL;
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;
-- Try to insert template
INSERT IGNORE INTO `tncm_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_template, @template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get template ID again if inserted
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;

-- Get agent data template ID if exists
SET @id_agent_data_template = NULL;
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;
-- Try to insert agent data template
INSERT IGNORE INTO `tncm_agent_data_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_agent_data_template, @agent_data_template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get agent data template ID again if inserted
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;

-- Get test script ID if exists
SET @id_script_test = NULL;
SET @script_type = 0;
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;
-- Try to insert test script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_test, @script_type, @script_test);
-- Get test script ID again if inserted
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;

-- Get get_config script ID if exists
SET @id_script_get_config = NULL;
SET @script_type = 1;
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;
-- Try to insert get_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_config, @script_type, @script_get_config);
-- Get get_config script ID again if inserted
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;

-- Get set_config script ID if exists
SET @id_script_set_config = NULL;
SET @script_type = 2;
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;
-- Try to insert set_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_config, @script_type, @script_set_config);
-- Get set_config script ID again if inserted
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;

-- Get get_firmware script ID if exists
SET @id_script_get_firmware = NULL;
SET @script_type = 3;
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;
-- Try to insert get_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_firmware, @script_type, @script_get_firmware);
-- Get get_firmware script ID again if inserted
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;

-- Get set_firmware script ID if exists
SET @id_script_set_firmware = NULL;
SET @script_type = 4;
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;
-- Try to insert set_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_firmware, @script_type, @script_set_firmware);
-- Get set_firmware script ID again if inserted
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;

-- Get custom script ID if exists
SET @id_script_custom = NULL;
SET @script_type = 5;
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;
-- Try to insert custom script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_custom, @script_type, @script_custom);
-- Get custom script ID again if inserted
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;

-- Get os_version script ID if exists
SET @id_script_os_version = NULL;
SET @script_type = 7;
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;
-- Try to insert os_version script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_os_version, @script_type, @script_os_version);
-- Get os_version script ID again if inserted
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;

-- Get template scripts ID if exists
SET @id_ts_test = NULL;
SELECT @id_ts_test := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_test;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_test, @id_template, @id_script_test);

-- Get template scripts ID if exists
SET @id_ts_get_config = NULL;
SELECT @id_ts_get_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_config, @id_template, @id_script_get_config);

-- Get template scripts ID if exists
SET @id_ts_set_config = NULL;
SELECT @id_ts_set_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_config, @id_template, @id_script_set_config);

-- Get template scripts ID if exists
SET @id_ts_get_firmware = NULL;
SELECT @id_ts_get_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_firmware, @id_template, @id_script_get_firmware);

-- Get template scripts ID if exists
SET @id_ts_set_firmware = NULL;
SELECT @id_ts_set_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_firmware, @id_template, @id_script_set_firmware);

-- Get template scripts ID if exists
SET @id_ts_custom = NULL;
SELECT @id_ts_custom := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_custom;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_custom, @id_template, @id_script_custom);

-- Get template scripts ID if exists
SET @id_ts_os_version = NULL;
SELECT @id_ts_os_version := `id` FROM `tncm_agent_data_template_scripts` WHERE `id_agent_data_template` = @id_template AND `id_script` = @id_script_os_version;
-- Try to insert
INSERT IGNORE INTO `tncm_agent_data_template_scripts` (`id`, `id_agent_data_template`, `id_script`) VALUES (@id_ts_os_version, @id_agent_data_template, @id_script_os_version);

-- Add new vendor and model
SET @vendor_name = 'Alcatel-Lucent Enterprise';
SET @model_name = 'Alcatel-Generic';
SET @template_name = 'Alcatel-Generic';
SET @agent_data_template_name = 'Alcatel-Generic';
SET @script_test = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_get_config = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;capture:admin&#x20;display-config&#92;n&#x0d;&#x0a;logout&#92;n';
SET @script_set_config = '';
SET @script_get_firmware = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;capture:show&#x20;version&#92;n&#x0d;&#x0a;logout&#92;n';
SET @script_set_firmware = '';
SET @script_custom = '';
SET @script_os_version = @script_get_firmware;

-- Try to insert vendor
INSERT IGNORE INTO `tncm_vendor` (`id`, `name`, `icon`) VALUES ('', @vendor_name, '');
-- Get vendor ID
SELECT @id_vendor := `id` FROM `tncm_vendor` WHERE `name` = @vendor_name;

-- Try to insert model
INSERT IGNORE INTO `tncm_model` (`id`, `id_vendor`, `name`) VALUES ('', @id_vendor, @model_name);
-- Get model ID
SELECT @id_model := `id` FROM `tncm_model` WHERE `id_vendor` = @id_vendor AND `name` = @model_name;

-- Get template ID if exists
SET @id_template = NULL;
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;
-- Try to insert template
INSERT IGNORE INTO `tncm_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_template, @template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get template ID again if inserted
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;

-- Get agent data template ID if exists
SET @id_agent_data_template = NULL;
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;
-- Try to insert agent data template
INSERT IGNORE INTO `tncm_agent_data_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_agent_data_template, @agent_data_template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get agent data template ID again if inserted
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;

-- Get test script ID if exists
SET @id_script_test = NULL;
SET @script_type = 0;
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;
-- Try to insert test script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_test, @script_type, @script_test);
-- Get test script ID again if inserted
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;

-- Get get_config script ID if exists
SET @id_script_get_config = NULL;
SET @script_type = 1;
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;
-- Try to insert get_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_config, @script_type, @script_get_config);
-- Get get_config script ID again if inserted
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;

-- Get set_config script ID if exists
SET @id_script_set_config = NULL;
SET @script_type = 2;
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;
-- Try to insert set_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_config, @script_type, @script_set_config);
-- Get set_config script ID again if inserted
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;

-- Get get_firmware script ID if exists
SET @id_script_get_firmware = NULL;
SET @script_type = 3;
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;
-- Try to insert get_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_firmware, @script_type, @script_get_firmware);
-- Get get_firmware script ID again if inserted
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;

-- Get set_firmware script ID if exists
SET @id_script_set_firmware = NULL;
SET @script_type = 4;
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;
-- Try to insert set_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_firmware, @script_type, @script_set_firmware);
-- Get set_firmware script ID again if inserted
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;

-- Get custom script ID if exists
SET @id_script_custom = NULL;
SET @script_type = 5;
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;
-- Try to insert custom script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_custom, @script_type, @script_custom);
-- Get custom script ID again if inserted
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;

-- Get os_version script ID if exists
SET @id_script_os_version = NULL;
SET @script_type = 7;
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;
-- Try to insert os_version script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_os_version, @script_type, @script_os_version);
-- Get os_version script ID again if inserted
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;

-- Get template scripts ID if exists
SET @id_ts_test = NULL;
SELECT @id_ts_test := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_test;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_test, @id_template, @id_script_test);

-- Get template scripts ID if exists
SET @id_ts_get_config = NULL;
SELECT @id_ts_get_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_config, @id_template, @id_script_get_config);

-- Get template scripts ID if exists
SET @id_ts_set_config = NULL;
SELECT @id_ts_set_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_config, @id_template, @id_script_set_config);

-- Get template scripts ID if exists
SET @id_ts_get_firmware = NULL;
SELECT @id_ts_get_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_firmware, @id_template, @id_script_get_firmware);

-- Get template scripts ID if exists
SET @id_ts_set_firmware = NULL;
SELECT @id_ts_set_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_firmware, @id_template, @id_script_set_firmware);

-- Get template scripts ID if exists
SET @id_ts_custom = NULL;
SELECT @id_ts_custom := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_custom;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_custom, @id_template, @id_script_custom);

-- Get template scripts ID if exists
SET @id_ts_os_version = NULL;
SELECT @id_ts_os_version := `id` FROM `tncm_agent_data_template_scripts` WHERE `id_agent_data_template` = @id_template AND `id_script` = @id_script_os_version;
-- Try to insert
INSERT IGNORE INTO `tncm_agent_data_template_scripts` (`id`, `id_agent_data_template`, `id_script`) VALUES (@id_ts_os_version, @id_agent_data_template, @id_script_os_version);

-- Add new vendor and model
SET @vendor_name = 'Aruba';
SET @model_name = 'Aruba-Generic';
SET @template_name = 'Aruba-Generic';
SET @agent_data_template_name = 'Aruba-Generic';
SET @script_test = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_get_config = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;capture:show&#x20;running-config&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_set_config = 'configure&#x20;terminal&#92;n&#x0d;&#x0a;load&#x20;replace&#x20;/var/tmp/file.conf&#92;n&#x0d;&#x0a;end&#92;n&#x0d;&#x0a;write&#x20;memory&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_get_firmware = 'enable&#92;n&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#92;n&#x0d;&#x0a;capture:show&#x20;version&#92;n&#x0d;&#x0a;exit&#92;n';
SET @script_set_firmware = 'copy&#x20;tftp&#x20;flash&#x20;_TFTP_SERVER_IP_&#x20;firmware.bin.swi&#x20;secondary&#92;n&#x0d;&#x0a;boot&#x20;system&#x20;flash&#x20;secondary&#92;n&#x0d;&#x0a;copy&#x20;tftp&#x20;flash&#x20;&#x20;_TFTP_SERVER_IP_&#x20;firmware.bin&#x20;primary&#92;n&#x0d;&#x0a;boot&#x20;system&#x20;flash&#x20;primary&#92;n';
SET @script_custom = '';
SET @script_os_version = @script_get_firmware;

-- Try to insert vendor
INSERT IGNORE INTO `tncm_vendor` (`id`, `name`, `icon`) VALUES ('', @vendor_name, '');
-- Get vendor ID
SELECT @id_vendor := `id` FROM `tncm_vendor` WHERE `name` = @vendor_name;

-- Try to insert model
INSERT IGNORE INTO `tncm_model` (`id`, `id_vendor`, `name`) VALUES ('', @id_vendor, @model_name);
-- Get model ID
SELECT @id_model := `id` FROM `tncm_model` WHERE `id_vendor` = @id_vendor AND `name` = @model_name;

-- Get template ID if exists
SET @id_template = NULL;
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;
-- Try to insert template
INSERT IGNORE INTO `tncm_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_template, @template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get template ID again if inserted
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;

-- Get agent data template ID if exists
SET @id_agent_data_template = NULL;
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;
-- Try to insert agent data template
INSERT IGNORE INTO `tncm_agent_data_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_agent_data_template, @agent_data_template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get agent data template ID again if inserted
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;

-- Get test script ID if exists
SET @id_script_test = NULL;
SET @script_type = 0;
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;
-- Try to insert test script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_test, @script_type, @script_test);
-- Get test script ID again if inserted
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;

-- Get get_config script ID if exists
SET @id_script_get_config = NULL;
SET @script_type = 1;
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;
-- Try to insert get_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_config, @script_type, @script_get_config);
-- Get get_config script ID again if inserted
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;

-- Get set_config script ID if exists
SET @id_script_set_config = NULL;
SET @script_type = 2;
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;
-- Try to insert set_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_config, @script_type, @script_set_config);
-- Get set_config script ID again if inserted
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;

-- Get get_firmware script ID if exists
SET @id_script_get_firmware = NULL;
SET @script_type = 3;
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;
-- Try to insert get_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_firmware, @script_type, @script_get_firmware);
-- Get get_firmware script ID again if inserted
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;

-- Get set_firmware script ID if exists
SET @id_script_set_firmware = NULL;
SET @script_type = 4;
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;
-- Try to insert set_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_firmware, @script_type, @script_set_firmware);
-- Get set_firmware script ID again if inserted
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;

-- Get custom script ID if exists
SET @id_script_custom = NULL;
SET @script_type = 5;
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;
-- Try to insert custom script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_custom, @script_type, @script_custom);
-- Get custom script ID again if inserted
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;

-- Get os_version script ID if exists
SET @id_script_os_version = NULL;
SET @script_type = 7;
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;
-- Try to insert os_version script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_os_version, @script_type, @script_os_version);
-- Get os_version script ID again if inserted
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;

-- Get template scripts ID if exists
SET @id_ts_test = NULL;
SELECT @id_ts_test := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_test;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_test, @id_template, @id_script_test);

-- Get template scripts ID if exists
SET @id_ts_get_config = NULL;
SELECT @id_ts_get_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_config, @id_template, @id_script_get_config);

-- Get template scripts ID if exists
SET @id_ts_set_config = NULL;
SELECT @id_ts_set_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_config, @id_template, @id_script_set_config);

-- Get template scripts ID if exists
SET @id_ts_get_firmware = NULL;
SELECT @id_ts_get_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_firmware, @id_template, @id_script_get_firmware);

-- Get template scripts ID if exists
SET @id_ts_set_firmware = NULL;
SELECT @id_ts_set_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_firmware, @id_template, @id_script_set_firmware);

-- Get template scripts ID if exists
SET @id_ts_custom = NULL;
SELECT @id_ts_custom := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_custom;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_custom, @id_template, @id_script_custom);

-- Get template scripts ID if exists
SET @id_ts_os_version = NULL;
SELECT @id_ts_os_version := `id` FROM `tncm_agent_data_template_scripts` WHERE `id_agent_data_template` = @id_template AND `id_script` = @id_script_os_version;
-- Try to insert
INSERT IGNORE INTO `tncm_agent_data_template_scripts` (`id`, `id_agent_data_template`, `id_script`) VALUES (@id_ts_os_version, @id_agent_data_template, @id_script_os_version);

-- Add new vendor and model
SET @vendor_name = 'Mikrotik';
SET @model_name = 'Mikrotik-Generic';
SET @template_name = 'Mikrotik-Generic';
SET @agent_data_template_name = 'Mikrotik-Generic';
SET @script_test = 'sleep:1&#x0d;&#x0a;exit&#92;n&#92;r';
SET @script_get_config = 'sleep:1&#x0d;&#x0a;capture:system&#x20;resource&#x20;print&#92;n&#92;r&#x20;&#x0d;&#x0a;exit&#92;n&#92;r';
SET @script_set_config = 'sleep:1&#x0d;&#x0a;system&#x20;backup&#x20;load&#x20;name=_nameBackup_&#x20;password=_passwordBackup_&#92;n&#92;r&#x0d;&#x0a;expect:Restore&#x0d;&#x0a;yes&#92;n&#92;r&#x0d;&#x0a;exit&#92;n&#92;r';
SET @script_get_firmware = 'sleep:1&#x0d;&#x0a;capture:/system&#x20;package&#x20;print&#92;n&#92;r&#x20;&#x0d;&#x0a;exit&#92;n&#92;r';
SET @script_set_firmware = 'sleep:1&#x0d;&#x0a;/system&#x20;routerboard&#x20;upgrade&#92;n&#92;r&#x0d;&#x0a;expect:Do&#x0d;&#x0a;yes&#92;n&#92;r&#x0d;&#x0a;exit&#92;n&#92;r';
SET @script_custom = '';
SET @script_os_version = @script_get_firmware;

-- Try to insert vendor
INSERT IGNORE INTO `tncm_vendor` (`id`, `name`, `icon`) VALUES ('', @vendor_name, '');
-- Get vendor ID
SELECT @id_vendor := `id` FROM `tncm_vendor` WHERE `name` = @vendor_name;

-- Try to insert model
INSERT IGNORE INTO `tncm_model` (`id`, `id_vendor`, `name`) VALUES ('', @id_vendor, @model_name);
-- Get model ID
SELECT @id_model := `id` FROM `tncm_model` WHERE `id_vendor` = @id_vendor AND `name` = @model_name;

-- Get template ID if exists
SET @id_template = NULL;
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;
-- Try to insert template
INSERT IGNORE INTO `tncm_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_template, @template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get template ID again if inserted
SELECT @id_template := `id` FROM `tncm_template` WHERE `name` = @template_name;

-- Get agent data template ID if exists
SET @id_agent_data_template = NULL;
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;
-- Try to insert agent data template
INSERT IGNORE INTO `tncm_agent_data_template` (`id`, `name`, `vendors`, `models`) VALUES (@id_agent_data_template, @agent_data_template_name, CONCAT('[',@id_vendor,']'), CONCAT('[',@id_model,']'));
-- Get agent data template ID again if inserted
SELECT @id_agent_data_template := `id` FROM `tncm_agent_data_template` WHERE `name` = @agent_data_template_name;

-- Get test script ID if exists
SET @id_script_test = NULL;
SET @script_type = 0;
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;
-- Try to insert test script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_test, @script_type, @script_test);
-- Get test script ID again if inserted
SELECT @id_script_test := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_test;

-- Get get_config script ID if exists
SET @id_script_get_config = NULL;
SET @script_type = 1;
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;
-- Try to insert get_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_config, @script_type, @script_get_config);
-- Get get_config script ID again if inserted
SELECT @id_script_get_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_config;

-- Get set_config script ID if exists
SET @id_script_set_config = NULL;
SET @script_type = 2;
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;
-- Try to insert set_config script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_config, @script_type, @script_set_config);
-- Get set_config script ID again if inserted
SELECT @id_script_set_config := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_config;

-- Get get_firmware script ID if exists
SET @id_script_get_firmware = NULL;
SET @script_type = 3;
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;
-- Try to insert get_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_get_firmware, @script_type, @script_get_firmware);
-- Get get_firmware script ID again if inserted
SELECT @id_script_get_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_get_firmware;

-- Get set_firmware script ID if exists
SET @id_script_set_firmware = NULL;
SET @script_type = 4;
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;
-- Try to insert set_firmware script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_set_firmware, @script_type, @script_set_firmware);
-- Get set_firmware script ID again if inserted
SELECT @id_script_set_firmware := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_set_firmware;

-- Get custom script ID if exists
SET @id_script_custom = NULL;
SET @script_type = 5;
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;
-- Try to insert custom script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_custom, @script_type, @script_custom);
-- Get custom script ID again if inserted
SELECT @id_script_custom := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_custom;

-- Get os_version script ID if exists
SET @id_script_os_version = NULL;
SET @script_type = 7;
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;
-- Try to insert os_version script
INSERT IGNORE INTO `tncm_script` (`id`, `type`, `content`) VALUES (@id_script_os_version, @script_type, @script_os_version);
-- Get os_version script ID again if inserted
SELECT @id_script_os_version := `id` FROM `tncm_script` WHERE `type` = @script_type AND `content` = @script_os_version;

-- Get template scripts ID if exists
SET @id_ts_test = NULL;
SELECT @id_ts_test := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_test;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_test, @id_template, @id_script_test);

-- Get template scripts ID if exists
SET @id_ts_get_config = NULL;
SELECT @id_ts_get_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_config, @id_template, @id_script_get_config);

-- Get template scripts ID if exists
SET @id_ts_set_config = NULL;
SELECT @id_ts_set_config := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_config;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_config, @id_template, @id_script_set_config);

-- Get template scripts ID if exists
SET @id_ts_get_firmware = NULL;
SELECT @id_ts_get_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_get_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_get_firmware, @id_template, @id_script_get_firmware);

-- Get template scripts ID if exists
SET @id_ts_set_firmware = NULL;
SELECT @id_ts_set_firmware := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_set_firmware;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_set_firmware, @id_template, @id_script_set_firmware);

-- Get template scripts ID if exists
SET @id_ts_custom = NULL;
SELECT @id_ts_custom := `id` FROM `tncm_template_scripts` WHERE `id_template` = @id_template AND `id_script` = @id_script_custom;
-- Try to insert
INSERT IGNORE INTO `tncm_template_scripts` (`id`, `id_template`, `id_script`) VALUES (@id_ts_custom, @id_template, @id_script_custom);

-- Get template scripts ID if exists
SET @id_ts_os_version = NULL;
SELECT @id_ts_os_version := `id` FROM `tncm_agent_data_template_scripts` WHERE `id_agent_data_template` = @id_template AND `id_script` = @id_script_os_version;
-- Try to insert
INSERT IGNORE INTO `tncm_agent_data_template_scripts` (`id`, `id_agent_data_template`, `id_script`) VALUES (@id_ts_os_version, @id_agent_data_template, @id_script_os_version);

ALTER TABLE `tevento`
ADD COLUMN `event_custom_id` TEXT NULL AFTER `module_status`;
-- Telegram and vonage default alerts
UPDATE talert_actions
	SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_'
	WHERE id=9;
UPDATE talert_actions
	SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_'
	WHERE id=11;

SET @exist = (SELECT count(*) FROM information_schema.columns WHERE TABLE_NAME='tmetaconsole_agent' AND COLUMN_NAME='transactional_agent' AND table_schema = DATABASE());
SET @sqlstmt = IF (@exist>0, 'ALTER TABLE `tmetaconsole_agent` DROP COLUMN `transactional_agent`', 'SELECT ""');
prepare stmt from @sqlstmt;
execute stmt;

SET @exist = (SELECT count(*) FROM information_schema.columns WHERE TABLE_NAME='tagente' AND COLUMN_NAME='transactional_agent' AND table_schema = DATABASE());
SET @sqlstmt = IF (@exist>0, 'ALTER TABLE `tagente` DROP COLUMN `transactional_agent`', 'SELECT ""');
prepare stmt from @sqlstmt;
execute stmt;

ALTER TABLE `tlayout_template_data` ADD COLUMN `title` TEXT default '';
ALTER TABLE `tlayout_data` ADD COLUMN `period_chart_options` TEXT default '';
ALTER TABLE `tlayout_template_data` ADD COLUMN `period_chart_options` TEXT default '';

ALTER TABLE `tdashboard`
ADD COLUMN `date_range` TINYINT NOT NULL DEFAULT 0 AFTER `cells_slideshow`,
ADD COLUMN `date_from` INT NOT NULL DEFAULT 0 AFTER `date_range`,
ADD COLUMN `date_to` INT NOT NULL DEFAULT 0 AFTER `date_from`;

-- Delete table tagent_access
DROP TABLE IF EXISTS tagent_access;

ALTER TABLE `tevent_rule` DROP COLUMN `user_comment`;
ALTER TABLE `tevent_rule` DROP COLUMN `operator_user_comment`;

ALTER TABLE treport_content ADD check_unknowns_graph tinyint DEFAULT 0 NULL;

ALTER TABLE `tevent_filter` ADD COLUMN `regex` TEXT NULL AFTER `private_filter_user`;
-- Update macros for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.1' WHERE `short_name` = 'pandorafms.oracle';

CREATE TABLE IF NOT EXISTS `tpolicy_modules_synth` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_agent_module_source` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_agent_module_target` INT UNSIGNED NOT NULL DEFAULT 0,
  `fixed_value` DOUBLE NOT NULL DEFAULT 0,
  `operation` enum ('ADD', 'SUB', 'DIV', 'MUL', 'AVG', 'NOP') NOT NULL DEFAULT 'NOP',
  `order` INT NOT NULL DEFAULT 0,  
  FOREIGN KEY (`id_agent_module_target`) REFERENCES tpolicy_modules(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
);

SET @id_app := (SELECT `id_app` FROM `tdiscovery_apps` WHERE `short_name` = 'pandorafms.oracle');

UPDATE `tdiscovery_apps_tasks_macros` SET `value` = 'agents_group_id=__taskGroupID__ interval=__taskInterval__ user=_dbuser_ password=_dbpass_ thick_mode=_thickMode_ client_path=_clientPath_ threads=_threads_ modules_prefix=_prefixModuleName_ execute_custom_queries=_executeCustomQueries_ analyze_connections=_checkConnections_ engine_uptime=_checkUptime_ query_stats=_queryStats_ cache_stats=_checkCache_ fragmentation_ratio=_checkFragmentation_ check_tablescpaces=_checkTablespaces_' WHERE `macro` = '_tempfileConf_' AND `id_task` IN (SELECT `id_rt` FROM `trecon_task` WHERE `id_app` = @id_app);

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros` (`id_task`, `macro`, `type`, `value`, `temp_conf`) SELECT id_rt, '_thickMode_', 'custom', 0, 0 FROM `trecon_task` WHERE `id_app` = @id_app;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros` (`id_task`, `macro`, `type`, `value`, `temp_conf`) SELECT id_rt, '_clientPath_', 'custom', '', 0 FROM `trecon_task` WHERE `id_app` = @id_app;
UPDATE `trecon_task` SET `setup_complete` = 1 WHERE `id_app` = @id_app;

-- Update lts updates
UPDATE tconfig SET value='1' WHERE token='lts_updates';

SELECT @generic_data := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "generic_data";
SELECT @generic_proc := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "generic_proc";
SELECT @async_data := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "async_data";
SELECT @async_proc := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "async_proc";
UPDATE `tagente_modulo` INNER JOIN `tservice` ON `tagente_modulo`.`custom_integer_1` = `tservice`.`id` SET `tagente_modulo`.`id_tipo_modulo` = @generic_data WHERE `tagente_modulo`.`id_tipo_modulo` = @async_data;
UPDATE `tagente_modulo` INNER JOIN `tservice` ON `tagente_modulo`.`custom_integer_1` = `tservice`.`id` SET `tagente_modulo`.`id_tipo_modulo` = @generic_proc WHERE `tagente_modulo`.`id_tipo_modulo` = @async_proc;

-- Telegram and vonage default alerts
UPDATE talert_actions SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_' WHERE id=9;
UPDATE talert_actions SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_' WHERE id=11;

UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.vmware';

ALTER TABLE `tagente_modulo` ADD COLUMN `ignore_unknown` TINYINT NOT NULL DEFAULT 0;
ALTER TABLE `tpolicy_modules` ADD COLUMN `ignore_unknown` TINYINT NOT NULL DEFAULT 0;

ALTER TABLE `tagente` ADD COLUMN `ignore_unknown` TINYINT NOT NULL DEFAULT 0;
ALTER TABLE `tmetaconsole_agent` ADD COLUMN  `ignore_unknown` TINYINT NOT NULL DEFAULT 0;

DELETE FROM `twelcome_tip` WHERE `title` = 'Automatic&#x20;agent&#x20;provision&#x20;system';

INSERT INTO `twelcome_tip` (`id_lang`,`id_profile`,`title`,`text`,`url`,`enable`) VALUES ('en_GB',0,'Automatic&#x20;agent&#x20;provision&#x20;system','The&#x20;agent&#x20;self-provisioning&#x20;system&#x20;allows&#x20;an&#x20;agent&#x20;recently&#x20;entered&#x20;into&#x20;the&#x20;system&#x20;to&#x20;automatically&#x20;apply&#x20;changes&#x20;to&#x20;their&#x20;configuration&#x20;&#40;such&#x20;as&#x20;moving&#x20;them&#x20;from&#x20;group,&#x20;assigning&#x20;them&#x20;certain&#x20;values&#x20;in&#x20;custom&#x20;fields&#41;&#x20;and&#x20;of&#x20;course&#x20;applying&#x20;certain&#x20;monitoring&#x20;policies.&#x20;It&#x20;is&#x20;one&#x20;of&#x20;the&#x20;most&#x20;powerful&#x20;functionalities,&#x20;aimed&#x20;at&#x20;managing&#x20;very&#x20;large&#x20;system&#x20;parks.','https://pandorafms.com/manual/start?id=en/documentation/02_installation/05_configuration_agents#conf',1);

COMMIT;
