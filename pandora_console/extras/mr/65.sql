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

DELETE FROM tconfig WHERE token = 'refr';

COMMIT;
