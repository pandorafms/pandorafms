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

CREATE TABLE IF NOT EXISTS `tncm_template` (
    `id` serial,
    `vendor` text,
    `model` text,
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
    `order` int unsigned not null default 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_template`) REFERENCES `tncm_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_script`) REFERENCES `tncm_script`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tncm_agent` (
    `id_agent` int(10) unsigned NOT NULL,
	`vendor` text,
    `model` text,
    `protocol` int unsigned not null default 0,
    `cred_key` varchar(100),
    `status` int(4) NOT NULL default 5,
	`last_status_change` bigint(20) NOT NULL default 0,
    PRIMARY KEY (`id_agent`),
    FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`cred_key`) REFERENCES `tcredential_store`(`identifier`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tncm_agent_templates` (
    `id_agent` int(10) unsigned NOT NULL,
    `id_template` bigint(20) unsigned NOT NULL,
    `status` int(4) NOT NULL default 5,
	`last_status_change` bigint(20) NOT NULL default 0,
    PRIMARY KEY (`id_agent`, `id_template`),
    FOREIGN KEY (`id_agent`) REFERENCES `tagente`(`id_agente`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_template`) REFERENCES `tncm_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;
