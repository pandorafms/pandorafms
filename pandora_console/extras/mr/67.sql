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

COMMIT;
