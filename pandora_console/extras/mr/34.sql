START TRANSACTION;

CREATE TABLE `tremote_command` (
  `id` SERIAL,
  `name` varchar(150) NOT NULL,
  `timeout` int(10) unsigned NOT NULL default 30,
  `retries` int(10) unsigned NOT NULL default 3,
  `preconditions` text,
  `script` text,
  `postconditions` text,
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `id_group` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tremote_command_target` (
  `rcmd_id` SERIAL,
  `id_agente` int(10) unsigned NOT NULL,
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `stdout` text,
  `stderr` text,
  `errorlevel` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY (`rcmd_id`),
  FOREIGN KEY (`rcmd_id`) REFERENCES `tremote_command`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;