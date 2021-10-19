START TRANSACTION;

ALTER TABLE `treport_content` ADD COLUMN `ipam_network_filter` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `ipam_alive_ips` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `treport_content` ADD COLUMN `ipam_ip_not_assigned_to_agent` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `treport_content_template` ADD COLUMN `ipam_network_filter` int(10) UNSIGNED DEFAULT 0;
ALTER TABLE `treport_content_template` ADD COLUMN `ipam_alive_ips` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `treport_content_template` ADD COLUMN `ipam_ip_not_assigned_to_agent` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `tevent_alert` ADD COLUMN `id_template_conditions` int(10) unsigned NOT NULL default 0;
ALTER TABLE `tevent_alert` ADD COLUMN `id_template_fields` int(10) unsigned NOT NULL default 0;
ALTER TABLE `tevent_filter` ADD COLUMN `time_from` TIME NULL;
ALTER TABLE `tevent_filter` ADD COLUMN `time_to` TIME NULL;
ALTER TABLE `treport_content_template` ADD COLUMN `time_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content_template` ADD COLUMN `checks_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `time_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `checks_in_warning_status` TINYINT(1) DEFAULT '0';
ALTER TABLE `tlayout_template` ADD COLUMN `auto_adjust` INTEGER UNSIGNED NOT NULL default 0;

INSERT INTO `treport_content` (id_report, id_gs, id_agent_module, type, period, `order`, name, description, id_agent, `text`, external_source, treport_custom_sql_id, header_definition, column_separator, line_separator, time_from, time_to, style, server_name, time_in_warning_status, checks_in_warning_status, failover_mode) SELECT id_report, 0, id_agent_module, 'availability', period, `order`, name, description, id_agent, NULL, NULL, treport_custom_sql_id, header_definition, column_separator, line_separator, time_from, time_to, '{&quot;show_in_same_row&quot;:0,&quot;hide_notinit_agents&quot;:0,&quot;priority_mode&quot;:1,&quot;dyn_height&quot;:&quot;230&quot;}', server_name, 1, 1, 0 FROM treport_content WHERE type = 'histogram_data';
INSERT INTO `treport_content_item` (id_report_content, id_agent_module, id_agent_module_failover, operation, server_name) SELECT id_rc, id_agent_module, 0, '', server_name FROM treport_content WHERE type = 'availability' AND id_agent <> 0 AND id_agent_module <> 0;
DELETE FROM `treport_content` WHERE type = 'histogram_data';

COMMIT;
