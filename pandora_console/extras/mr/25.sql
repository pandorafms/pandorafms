START TRANSACTION;

UPDATE `twidget` SET `unique_name`='example' WHERE `class_name` LIKE 'WelcomeWidget';

INSERT INTO `tconfig` (`token`, `value`) VALUES ('status_monitor_fields', 'policy,agent,data_type,module_name,server_type,interval,status,graph,warn,data,timestamp');

ALTER TABLE `trecon_task` ADD COLUMN `wmi_enabled` tinyint(1) unsigned DEFAULT '0';
ALTER TABLE `trecon_task` ADD COLUMN `auth_strings` text;
ALTER TABLE `trecon_task` ADD COLUMN `autoconfiguration_enabled` tinyint(1) unsigned default '0';


INSERT INTO `trecon_script` (`name`,`description`,`script`,`macros`) VALUES ('Discovery.Application.VMware', 'Discovery&#x20;Application&#x20;script&#x20;to&#x20;monitor&#x20;VMware&#x20;technologies&#x20;&#40;ESXi,&#x20;VCenter,&#x20;VSphere&#41;', '/usr/share/pandora_server/util/recon_scripts/vmware-plugin.pl', '{"1":{"macro":"_field1_","desc":"Configuration&#x20;file","help":"","value":"","hide":""}}');
INSERT INTO `trecon_script` (`name`,`description`,`script`,`macros`) VALUES ('Discovery.Cloud', 'Discovery&#x20;Cloud&#x20;script&#x20;to&#x20;monitor&#x20;Cloud&#x20;technologies&#x20;&#40;AWS.EC2,&#x20;AWS.S3,&#x20;AWS.RDS,&#x20RDS,&#x20AWS.EKS&#41;', '/usr/share/pandora_server/util/recon_scripts/pcm_client.pl', '{"1":{"macro":"_field1_","desc":"Configuration&#x20;file","help":"","value":"","hide":""}}');

CREATE TABLE IF NOT EXISTS `tevent_extended` (
    `id` serial PRIMARY KEY,
    `id_evento` bigint(20) unsigned NOT NULL,
    `external_id` bigint(20) unsigned,
    `utimestamp` bigint(20) NOT NULL default '0',
    `description` text,
    FOREIGN KEY `tevent_ext_fk`(`id_evento`) REFERENCES `tevento`(`id_evento`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tnotification_source` (
    `id` serial,
    `description` VARCHAR(255) DEFAULT NULL,
    `icon` text,
    `max_postpone_time` int(11) DEFAULT NULL,
    `enabled` int(1) DEFAULT NULL,
    `user_editable` int(1) DEFAULT NULL,
    `also_mail` int(1) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tnotification_source`
--
INSERT INTO `tnotification_source`(`description`, `icon`, `max_postpone_time`, `enabled`, `user_editable`, `also_mail`) VALUES
  ("System&#x20;status", "icono_info_mr.png", 86400, 1, 1, 0),
  ("Message", "icono_info_mr.png", 86400, 1, 1, 0),
  ("Pending&#x20;task", "icono_info_mr.png", 86400, 1, 1, 0),
  ("Advertisement", "icono_info_mr.png", 86400, 1, 1, 0),
  ("Official&#x20;communication", "icono_info_mr.png", 86400, 1, 1, 0),
  ("Sugerence", "icono_info_mr.png", 86400, 1, 1, 0);

-- -----------------------------------------------------
-- Table `tmensajes`
-- -----------------------------------------------------
ALTER TABLE `tmensajes` ADD COLUMN `url` TEXT;
ALTER TABLE `tmensajes` ADD COLUMN `response_mode` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `tmensajes` ADD COLUMN `citicity` INT(10) UNSIGNED DEFAULT '0';
ALTER TABLE `tmensajes` ADD COLUMN `id_source` BIGINT(20) UNSIGNED NOT NULL;
ALTER TABLE `tmensajes` ADD COLUMN `subtype` VARCHAR(255) DEFAULT '';
ALTER TABLE `tmensajes` ADD INDEX (`id_source`);
UPDATE `tmensajes` SET `id_source`=(SELECT `id` FROM `tnotification_source` WHERE `description` = "Message");
ALTER TABLE `tmensajes` ADD CONSTRAINT `tsource_fk` FOREIGN KEY (`id_source`) REFERENCES `tnotification_source` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


CREATE TABLE IF NOT EXISTS `tnotification_user` (
    `id_mensaje` INT(10) UNSIGNED NOT NULL,
    `id_user` VARCHAR(60) NOT NULL,
    `utimestamp_read` BIGINT(20),
    `utimestamp_erased` BIGINT(20),
    `postpone` INT,
    PRIMARY KEY (`id_mensaje`,`id_user`),
    FOREIGN KEY (`id_mensaje`) REFERENCES `tmensajes`(`id_mensaje`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_user`) REFERENCES `tusuario`(`id_user`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tnotification_group` (
	`id_mensaje` INT(10) UNSIGNED NOT NULL,
	`id_group` mediumint(4) UNSIGNED NOT NULL,
	PRIMARY KEY (`id_mensaje`,`id_group`),
	FOREIGN KEY (`id_mensaje`) REFERENCES `tmensajes`(`id_mensaje`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tnotification_source_user` (
    `id_source` BIGINT(20) UNSIGNED NOT NULL,
    `id_user` VARCHAR(60),
    `enabled` INT(1) DEFAULT NULL,
    `also_mail` INT(1) DEFAULT NULL,
    PRIMARY KEY (`id_source`,`id_user`),
    FOREIGN KEY (`id_source`) REFERENCES `tnotification_source`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_user`) REFERENCES `tusuario`(`id_user`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tnotification_source_group` (
    `id_source` BIGINT(20) UNSIGNED NOT NULL,
    `id_group` mediumint(4) unsigned NOT NULL,
    PRIMARY KEY (`id_source`,`id_group`),
	INDEX (`id_group`),
    FOREIGN KEY (`id_source`) REFERENCES `tnotification_source`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS  `tnotification_source_group_user`(
    `id_source` BIGINT(20) UNSIGNED NOT NULL,
    `id_group` mediumint(4) unsigned NOT NULL,
    `id_user` VARCHAR(60),
    `enabled` INT(1) DEFAULT NULL,
    `also_mail` INT(1) DEFAULT NULL,
    PRIMARY KEY (`id_source`,`id_user`),
    FOREIGN KEY (`id_source`) REFERENCES `tnotification_source`(`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_user`) REFERENCES `tusuario`(`id_user`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_group`) REFERENCES `tnotification_source_group`(`id_group`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `talert_commands` (`name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES ('Generate&#x20;Notification','Internal&#x20;type','This&#x20;command&#x20;allows&#x20;you&#x20;to&#x20;send&#x20;an&#x20;internal&#x20;notification&#x20;to&#x20;any&#x20;user&#x20;or&#x20;group.',1,'[\"Destination&#x20;user\",\"Destination&#x20;group\",\"Title\",\"Message\",\"Link\",\"Criticity\",\"\",\"\",\"\",\"\",\"\"]','[\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]');

INSERT INTO `tnotification_source_user` (`id_source`, `id_user`, `enabled`, `also_mail`) VALUES ((SELECT `id` FROM `tnotification_source` WHERE `description`="System&#x20;status"), "admin", 1, 0);
INSERT INTO `tnotification_source_group` SELECT `id`,0 FROM `tnotification_source` WHERE `description`="Message";
INSERT INTO `tnotification_user` (`id_mensaje`, `id_user`) SELECT `id_mensaje`, `id_usuario_destino` FROM `tmensajes` WHERE `id_usuario_destino` != '';

INSERT INTO tlog_graph_models (`title`,`regexp`,`fields`,`average`) VALUES ('Apache&#x20;accesses&#x20;per&#x20;client&#x20;and&#x20;status',
'&#40;.*?&#41;&#92;&#x20;-.*1.1&quot;&#92;&#x20;&#40;&#92;d+&#41;&#92;&#x20;&#92;d+',
'host,status', 1);

INSERT INTO tlog_graph_models (`title`,`regexp`,`fields`,`average`) VALUES ('Apache&#x20;time&#x20;per&#x20;requester&#x20;and&#x20;html&#x20;code',
'&#40;.*?&#41;&#92;&#x20;-.*1.1&quot;&#92;&#x20;&#40;&#92;d+&#41;&#92;&#x20;&#40;&#92;d+&#41;',
'origin,respose,_time_', 1);

INSERT INTO tlog_graph_models (`title`,`regexp`,`fields`,`average`) VALUES ('Count&#x20;output',
'.*',
'Coincidences', 0);

INSERT INTO tlog_graph_models (`title`,`regexp`,`fields`,`average`) VALUES ('Events&#x20;replicated&#x20;to&#x20;metaconsole',
'.*&#x20;&#40;.*?&#41;&#x20;.*&#x20;&#40;&#92;d+&#41;&#x20;events&#x20;replicated&#x20;to&#x20;metaconsole',
'server,_events_', 0);

INSERT INTO tlog_graph_models (`title`,`regexp`,`fields`,`average`) VALUES ('Pages&#x20;with&#x20;warnings',
'PHP&#x20;Warning:.*in&#x20;&#40;.*?&#41;&#x20;on',
'page', 0);

INSERT INTO tlog_graph_models (`title`,`regexp`,`fields`,`average`) VALUES ('Users&#x20;login',
'Starting&#x20;Session&#x20;&#92;d+&#92;&#x20;of&#x20;user&#x20;&#40;.*&#41;',
'user', 0);

COMMIT;
