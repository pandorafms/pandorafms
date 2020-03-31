START TRANSACTION;

ALTER TABLE trecon_task MODIFY COLUMN `id_network_profile` TEXT;
ALTER TABLE `trecon_task` CHANGE COLUMN `create_incident` `review_mode` TINYINT(1) UNSIGNED DEFAULT 0;
UPDATE `trecon_task` SET `review_mode` = 1;
ALTER TABLE trecon_task add column `auto_monitor` TINYINT(1) UNSIGNED DEFAULT 1 AFTER `auth_strings`;
UPDATE `trecon_task` SET `auto_monitor` = 0;

CREATE TABLE `tdiscovery_tmp_agents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_rt` int(10) unsigned NOT NULL,
  `label` varchar(600) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `data` text,
  `review_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_rt` (`id_rt`),
  INDEX `label` (`label`),
  CONSTRAINT `tdta_trt` FOREIGN KEY (`id_rt`) REFERENCES `trecon_task` (`id_rt`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdiscovery_tmp_connections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_rt` int(10) unsigned NOT NULL,
  `id1` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id2` int(10) unsigned NOT NULL,
  `if1` text,
  `if2` text,
  PRIMARY KEY (`id1`,`id2`),
  CONSTRAINT `tdtc_trt` FOREIGN KEY (`id_rt`)
    REFERENCES `trecon_task` (`id_rt`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tdtc_tdta1` FOREIGN KEY (`id1`)
    REFERENCES `tdiscovery_tmp_agents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tdtc_tdta2` FOREIGN KEY (`id2`)
    REFERENCES `tdiscovery_tmp_agents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- New table `tpen`
-- ----------------------------------------------------------------------
CREATE TABLE `tpen` (
  `pen` int(10) unsigned NOT NULL,
  `manufacturer` TEXT,
  `description` TEXT,
  PRIMARY KEY (`pen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `tnetwork_profile_pen`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tnetwork_profile_pen` (
  `pen` int(10) unsigned NOT NULL,
  `id_np` int(10) unsigned NOT NULL,
  CONSTRAINT `fk_network_profile_pen_pen` FOREIGN KEY (`pen`)
    REFERENCES `tpen` (`pen`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_network_profile_pen_id_np` FOREIGN KEY (`id_np`)
    REFERENCES `tnetwork_profile` (`id_np`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `twidget_dashboard` ADD COLUMN `position` TEXT NOT NULL default '';

ALTER TABLE `tagente_estado` ADD COLUMN `last_status_change` bigint(20) NOT NULL default '0';

UPDATE `tconfig` SET `value`='policy,agent,data_type,module_name,server_type,interval,status,last_status_change,graph,warn,data,timestamp' WHERE `token` = 'status_monitor_fields';

ALTER TABLE `talert_templates` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `tevent_alert` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `talert_snmp` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;

COMMIT;
