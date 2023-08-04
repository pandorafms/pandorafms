START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tdiscovery_apps` (
  `id_app` int(10) auto_increment,
  `short_name` varchar(250) NOT NULL DEFAULT '',
  `name` varchar(250) NOT NULL DEFAULT '',
  `section` varchar(250) NOT NULL DEFAULT 'custom',
  `description` varchar(250) NOT NULL DEFAULT '',
  `version` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_app`),
  UNIQUE (`short_name`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tdiscovery_apps_scripts` (
  `id_app` int(10),
  `macro` varchar(250) NOT NULL DEFAULT '',
  `value` text NOT NULL DEFAULT '',
  PRIMARY KEY (`id_app`, `macro`),
  FOREIGN KEY (`id_app`) REFERENCES tdiscovery_apps(`id_app`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tdiscovery_apps_executions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_app` int(10),
  `execution` text NOT NULL DEFAULT '',
  PRIMARY KEY (`id`, `id_app`),
  FOREIGN KEY (`id_app`) REFERENCES tdiscovery_apps(`id_app`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tdiscovery_apps_tasks_macros` (
  `id_task` int(10) unsigned NOT NULL,
  `macro` varchar(250) NOT NULL DEFAULT '',
  `type` varchar(250) NOT NULL DEFAULT 'custom',
  `value` text NOT NULL DEFAULT '',
  `temp_conf` tinyint unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_task`, `macro`),
  FOREIGN KEY (`id_task`) REFERENCES trecon_task(`id_rt`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;


ALTER TABLE `trecon_task`
  ADD COLUMN `id_app` int(10),
  ADD COLUMN `setup_complete` tinyint unsigned NOT NULL DEFAULT 0,
  ADD COLUMN `executions_timeout` int unsigned NOT NULL DEFAULT 60,
  ADD FOREIGN KEY (`id_app`) REFERENCES tdiscovery_apps(`id_app`) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `tnetwork_explorer_filter` (
`id` INT NOT NULL,
`filter_name` VARCHAR(45) NULL,
`top` VARCHAR(45) NULL,
`action` VARCHAR(45) NULL,
`advanced_filter` TEXT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tnetwork_usage_filter` (
`id` INT NOT NULL auto_increment,
`filter_name` VARCHAR(45) NULL,
`top` VARCHAR(45) NULL,
`action` VARCHAR(45) NULL,
`advanced_filter` TEXT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tlayout`
ADD COLUMN `grid_color` VARCHAR(45) NOT NULL DEFAULT '#cccccc' AFTER `maintenance_mode`,
ADD COLUMN `grid_size` VARCHAR(45) NOT NULL DEFAULT '10' AFTER `grid_color`;

ALTER TABLE `tlayout_template`
ADD COLUMN `grid_color` VARCHAR(45) NOT NULL DEFAULT '#cccccc' AFTER `maintenance_mode`,
ADD COLUMN `grid_size` VARCHAR(45) NOT NULL DEFAULT '10' AFTER `grid_color`;


DELETE FROM tconfig WHERE token = 'refr';

INSERT INTO `tmodule_inventory` (`id_module_inventory`, `id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) VALUES (37,2,'CPU','CPU','','Brand;Clock;Model','',0,2);

INSERT INTO `tmodule_inventory` (`id_module_inventory`, `id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) VALUES (38,2,'RAM','RAM','','Size','',0,2);

INSERT INTO `tmodule_inventory` (`id_module_inventory`, `id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) VALUES (39,2,'NIC','NIC','','NIC;Mac;Speed','',0,2);

INSERT INTO `tmodule_inventory` (`id_module_inventory`, `id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) VALUES (40,2,'Software','Software','','PKGINST;VERSION;NAME','',0,2);

ALTER TABLE `treport_content`  ADD COLUMN `period_range` INT NULL DEFAULT 0 AFTER `period`;

CREATE TABLE IF NOT EXISTS `tevent_comment` (
  `id` serial PRIMARY KEY,
  `id_event` BIGINT UNSIGNED NOT NULL,
  `utimestamp` BIGINT NOT NULL DEFAULT 0,
  `comment` TEXT,
  `id_user` VARCHAR(255) DEFAULT NULL,
  `action` TEXT,
  FOREIGN KEY (`id_event`) REFERENCES `tevento`(`id_evento`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_user`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

INSERT INTO `tevent_comment` (`id_event`, `utimestamp`, `comment`, `id_user`, `action`)
SELECT * FROM (
  SELECT tevento.id_evento AS `id_event`,
  JSON_UNQUOTE(JSON_EXTRACT(tevento.user_comment, CONCAT('$[',n.num,'].utimestamp'))) AS `utimestamp`,
  JSON_UNQUOTE(JSON_EXTRACT(tevento.user_comment, CONCAT('$[',n.num,'].comment'))) AS `comment`,
  JSON_UNQUOTE(JSON_EXTRACT(tevento.user_comment, CONCAT('$[',n.num,'].id_user'))) AS  `id_user`,
  JSON_UNQUOTE(JSON_EXTRACT(tevento.user_comment, CONCAT('$[',n.num,'].action'))) AS `action`
  FROM tevento
  INNER JOIN (SELECT 0 num UNION ALL SELECT 1 UNION ALL SELECT 2) n
    ON n.num < JSON_LENGTH(tevento.user_comment)
  WHERE tevento.user_comment != ""
) t order by utimestamp DESC;

ALTER TABLE tevento DROP COLUMN user_comment;

COMMIT;
