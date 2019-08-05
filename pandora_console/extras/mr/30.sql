START TRANSACTION;

ALTER TABLE `treport_content_sla_combined` ADD `id_agent_module_failover` int(10) unsigned NOT NULL;

ALTER TABLE `treport_content` ADD COLUMN `failover_mode` tinyint(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `failover_type` tinyint(1) DEFAULT '0';

ALTER TABLE `treport_content_template` ADD COLUMN `failover_mode` tinyint(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `failover_type` tinyint(1) DEFAULT '1';

ALTER TABLE `tmodule_relationship` ADD COLUMN `type` ENUM('direct', 'failover') DEFAULT 'direct';

ALTER TABLE `treport_content` MODIFY COLUMN `name` varchar(300) NULL;

CREATE TABLE `tagent_repository` (
  `id` SERIAL,
  `id_os` INT(10) UNSIGNED DEFAULT 0,
  `arch` ENUM('x64', 'x86') DEFAULT 'x64',
  `version` VARCHAR(10) DEFAULT '',
  `path` text,
  `uploaded_by` VARCHAR(100) DEFAULT '',
  `uploaded` bigint(20) NOT NULL DEFAULT 0 COMMENT "When it was uploaded",
  `last_err` text,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_os`) REFERENCES `tconfig_os`(`id_os`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdeployment_hosts` (
  `id` SERIAL,
  `id_cs` VARCHAR(100),
  `ip` VARCHAR(100) NOT NULL UNIQUE,
  `id_os` INT(10) UNSIGNED DEFAULT 0,
  `os_version` VARCHAR(100) DEFAULT '' COMMENT "OS version in STR format",
  `arch` ENUM('x64', 'x86') DEFAULT 'x64',
  `current_agent_version` VARCHAR(100) DEFAULT '' COMMENT "String latest installed agent",
  `target_agent_version_id` BIGINT UNSIGNED,
  `deployed` bigint(20) NOT NULL DEFAULT 0 COMMENT "When it was deployed",
  `server_ip` varchar(100) default NULL COMMENT "Where to point target agent",
  `last_err` text,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_cs`) REFERENCES `tcredential_store`(`identifier`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  FOREIGN KEY (`id_os`) REFERENCES `tconfig_os`(`id_os`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`target_agent_version_id`) REFERENCES  `tagent_repository`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


COMMIT;
