START TRANSACTION;

ALTER TABLE `tservice` ADD COLUMN `quiet` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tservice` ADD COLUMN `cps` int NOT NULL DEFAULT '0';
ALTER TABLE `tservice` ADD COLUMN `cascade_protection` tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `tagente` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

ALTER TABLE `tmetaconsole_agent` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

ALTER TABLE `tagente_modulo` ADD COLUMN `cps` int NOT NULL DEFAULT '0';

CREATE TABLE `tautoconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tautoconfig_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_autoconfig` int(10) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `operator` enum('AND','OR') DEFAULT 'OR',
  `type` enum('alias','ip-range','group','os','custom-field','script','server-name') DEFAULT 'alias',
  `value` text,
  `custom` text,
  PRIMARY KEY (`id`),
  KEY `id_autoconfig` (`id_autoconfig`),
  CONSTRAINT `tautoconfig_rules_ibfk_1` FOREIGN KEY (`id_autoconfig`) REFERENCES `tautoconfig` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tautoconfig_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_autoconfig` int(10) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `action_type` enum('set-group', 'set-secondary-group', 'apply-policy', 'launch-script', 'launch-event', 'launch-alert-action', 'raw-config') DEFAULT 'launch-event',
  `value` text,
  `custom` text,
  PRIMARY KEY (`id`),
  KEY `id_autoconfig` (`id_autoconfig`),
  CONSTRAINT `tautoconfig_action_ibfk_1` FOREIGN KEY (`id_autoconfig`) REFERENCES `tautoconfig` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


COMMIT;