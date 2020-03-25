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

CREATE TABLE `tpen` (
  `id_np` int(10) unsigned NOT NULL,
  `pen` int(10) unsigned NOT NULL,
  `manufacturer` TEXT NOT NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`id_np`,`pen`),
  CONSTRAINT `fk_np_id` FOREIGN KEY (`id_np`)
    REFERENCES `tnetwork_profile` (`id_np`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;
