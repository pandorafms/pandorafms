START TRANSACTION;

ALTER TABLE trecon_task MODIFY COLUMN `id_network_profile` TEXT;
ALTER TABLE `trecon_task` CHANGE COLUMN `create_incident` `direct_report` TINYINT(1) UNSIGNED DEFAULT 0;
UPDATE `trecon_task` SET `direct_report` = 1;
ALTER TABLE `tnetwork_profile` ADD COLUMN `pen` TEXT;

CREATE TABLE `tdiscovery_tmp_agents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_rt` int(10) unsigned NOT NULL,
  `label` varchar(600) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `data` text,
  `review_date` datetime DEFAULT NULL,
  `created` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id_rt` (`id_rt`),
  INDEX `label` (`label`),
  CONSTRAINT `tdta_trt` FOREIGN KEY (`id_rt`) REFERENCES `trecon_task` (`id_rt`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdiscovery_tmp_connections` (
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

ALTE

COMMIT;
