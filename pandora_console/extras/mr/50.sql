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

ALTER TABLE `tperfil` ADD COLUMN `network_config_view`tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tperfil` ADD COLUMN `network_config_edit`tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tperfil` ADD COLUMN `network_config_management`tinyint(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `tncm_vendor` (
    `id` serial,
    `name` varchar(255) UNIQUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tncm_model` (
    `id` serial,
    `id_vendor` bigint(20) unsigned NOT NULL,
    `name` varchar(255) UNIQUE,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_vendor`) REFERENCES `tncm_vendor`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tncm_template` (
    `id` serial,
    `name` text,
    `vendors` text,
    `models` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tncm_script` (
    `id` serial,
    `type` int unsigned not null default 0,
    `content` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tncm_template_scripts` (
    `id` serial,
    `id_template` bigint(20) unsigned NOT NULL,
    `id_script` bigint(20) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_template`) REFERENCES `tncm_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_script`) REFERENCES `tncm_script`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tncm_agent` (
    `id_agent` int(10) unsigned NOT NULL,
    `id_vendor` bigint(20) unsigned,
    `id_model` bigint(20) unsigned,
    `protocol` int unsigned not null default 0,
    `cred_key` varchar(100),
    `adv_key` varchar(100),
    `port` int(4) unsigned default 22,
    `status` int(4) NOT NULL default 5,
    `updated_at` bigint(20) NOT NULL default 0,
    `config_backup_id` bigint(20) UNSIGNED DEFAULT NULL,
    `id_template` bigint(20) unsigned,
    `execute_type` int(2) UNSIGNED NOT NULL default 0,
    `execute` int(2) UNSIGNED NOT NULL default 0,
    `last_error` text,
    PRIMARY KEY (`id_agent`),
    FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`cred_key`) REFERENCES `tcredential_store`(`identifier`) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (`id_template`) REFERENCES `tncm_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_vendor`) REFERENCES `tncm_vendor`(`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (`id_model`) REFERENCES `tncm_model`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tncm_agent_data` (
    `id` serial,
    `id_agent` int(10) unsigned NOT NULL,
    `script_type` int unsigned not null,
    `data` LONGBLOB,
    `status` int(4) NOT NULL default 5,
    `updated_at` bigint(20) NOT NULL default 0,
    FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `tncm_vendor` VALUES
    (1,'Cisco'),
	(2, 'D-Link Systems, Inc.'),
    (3, 'MikroTik'),
    (4, 'Alcatel-Lucent Enterprise'),
    (5, 'Ubiquiti Networks, Inc.'),
    (6, 'Allied Telesis, Inc.'),
    (7, 'Frogfoot Networks'),
    (8, 'IBM'),
    (9, 'Dell Inc.'),
    (10, 'Hitachi Communication Technologies, Ltd.'),
    (11, 'Netlink'),
    (12, 'Ascom'),
    (13, 'Synology Inc.'),
    (14, 'Fujitsu Network Communications, Inc.');

INSERT IGNORE INTO `tncm_model` VALUES (1,1,'7200');

INSERT IGNORE INTO `tncm_template` VALUES (1,'cisco-base','[\"1\"]','[\"1\"]');

INSERT IGNORE INTO `tncm_script` VALUES 
    (1,0,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;exit'),
    (2,1,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;term&#x20;length&#x20;0&#x0d;&#x0a;capture:show&#x20;running-config&#x0d;&#x0a;exit&#x0d;&#x0a;'),
	(3,2,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;term&#x20;length&#x20;0&#x0d;&#x0a;config&#x20;terminal&#x0d;&#x0a;_applyconfigbackup_&#x0d;&#x0a;exit&#x0d;&#x0a;'),
	(4,3,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;term&#x20;length&#x20;0&#x0d;&#x0a;capture:show&#x20;version&#x20;|&#x20;i&#x20;IOS&#x20;Software&#x0d;&#x0a;exit&#x0d;&#x0a;'),
	(5,5,'enable&#x0d;&#x0a;expect:Password:&#92;s*&#x0d;&#x0a;_enablepass_&#x0d;&#x0a;term&#x20;length&#x20;0&#x0d;&#x0a;config&#x20;term&#x0d;&#x0a;end&#x0d;&#x0a;end&#x0d;&#x0a;exit&#x0d;&#x0a;');

INSERT INTO `tncm_template_scripts`(`id_template`, `id_script`) VALUES (1,1),(1,2),(1,3),(1,4),(1,5);

ALTER TABLE `tevent_alert` ADD COLUMN `last_evaluation` bigint(20) NOT NULL default 0;
ALTER TABLE `tevent_alert` ADD COLUMN `pool_occurrences` int unsigned not null default 0;

COMMIT;
