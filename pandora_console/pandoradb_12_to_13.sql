ALTER TABLE tagent_access ADD column `utimestamp` bigint(20) NOT NULL default '0';
ALTER TABLE tagente_datos MODIFY COLUMN `id_agente` mediumint(8) unsigned NOT NULL default '0';
ALTER TABLE tagente_datos ADD column `utimestamp` int(10) unsigned default '0';

ALTER TABLE tagente_datos DROP key data_index_2;
ALTER TABLE tagente_datos DROP key data_index_3;
ALTER TABLE tagente_datos DROP key data_index_1;
ALTER TABLE tagente_datos ADD KEY `data_index2` (`id_agente`,`id_agente_modulo`);
ALTER TABLE tagente_datos_inc ADD column `utimestamp` int(10) unsigned default '0';
ALTER TABLE tagente_datos_string ADD column `utimestamp` int(10) unsigned default '0';
ALTER TABLE tagente_datos_string DROP KEY `data_string_index_3`;
ALTER TABLE tagente_estado ADD column `utimestamp` bigint(20) NOT NULL default '0';
ALTER TABLE tagente_estado ADD column `current_interval` int(10) unsigned NOT NULL default '0';
ALTER TABLE tagente_estado ADD column `running_by` int(10) unsigned NULL default 0;
ALTER TABLE tagente_estado ADD column `last_execution_try` bigint(20) NOT NULL default '0';
ALTER TABLE tagente_modulo MODIFY COLUMN `flag` tinyint(3) unsigned default '1';
ALTER TABLE tagente_modulo ADD COLUMN `id_modulo` int(11) unsigned NULL default 0;
ALTER TABLE tagente_modulo ADD KEY `tam_agente` (`id_agente`);

ALTER TABLE talerta_agente_modulo ADD COLUMN `alert_text` varchar(255) default '';
ALTER TABLE talerta_agente_modulo ADD COLUMN `disable` int(4) default '0';
ALTER TABLE talerta_agente_modulo ADD COLUMN `time_from` TIME default '00:00:00';
ALTER TABLE talerta_agente_modulo ADD COLUMN `time_to` TIME default '00:00:00';

ALTER TABLE talerta_agente_modulo MODIFY COLUMN `dis_max` double(18,2) default NULL;
ALTER TABLE talerta_agente_modulo MODIFY COLUMN `dis_min` double(18,2) default NULL;
ALTER TABLE tagente_modulo ADD COLUMN `alert_text` varchar(255) default '';
ALTER TABLE tagente_modulo ADD COLUMN `disable` int(4) default '0';
ALTER TABLE tagente_modulo ADD COLUMN `time_from` int(8) default '0';
ALTER TABLE tagente_modulo ADD COLUMN `time_to` int(8) default '0';
ALTER TABLE `tagente_modulo` DROP PRIMARY KEY ,ADD PRIMARY KEY ( `id_agente_modulo` , `id_agente` );
ALTER TABLE tevento ADD COLUMN `utimestamp` bigint(20) unsigned NOT NULL default '0';
ALTER TABLE tgrupo ADD COLUMN `parent` tinyint(4) NOT NULL default '-1';
ALTER TABLE tgrupo ADD COLUMN `disabled` tinyint(4) NOT NULL default '0';
ALTER TABLE tincidencia ADD COLUMN `notify_email` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE tlanguage MODIFY COLUMN `name` varchar(100) NOT NULL default '';
ALTER TABLE tserver ADD COLUMN `recon_server` tinyint(3) unsigned NOT NULL default '0';
ALTER TABLE tserver ADD COLUMN `version` varchar(20) NOT NULL default '';
ALTER TABLE tsesion ADD COLUMN `utimestamp` bigint(20) unsigned NOT NULL default '0';

CREATE TABLE `taddress` (
  `id_a` bigint(20) unsigned NOT NULL auto_increment,
  `ip` varchar(15) NOT NULL default '',
  `ip_pack` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_a`)
) ENGINE=InnoDB;

CREATE TABLE `taddress_agent` (
  `id_ag` bigint(20) unsigned NOT NULL auto_increment,
  `id_a` bigint(20) unsigned NOT NULL default '0',
  `id_agent` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_ag`)
) ENGINE=InnoDB;

CREATE TABLE `tmodule` (
  `id_module` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY (`id_module`)
) ENGINE=InnoDB;

CREATE TABLE `tnetwork_component` (
  `id_nc` mediumint(12) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `description` varchar(250) default NULL,
  `id_group` mediumint(9) NOT NULL default '1',
  `type` smallint(6) NOT NULL default '6',
  `max` bigint(20) NOT NULL default '0',
  `min` bigint(20) NOT NULL default '0',
  `module_interval` mediumint(8) unsigned NOT NULL default '0',
  `tcp_port` int(10) unsigned NOT NULL default '0',
  `tcp_send` varchar(250) NOT NULL,
  `tcp_rcv` varchar(250) NOT NULL default 'NULL',
  `snmp_community` varchar(250) NOT NULL default 'NULL',
  `snmp_oid` blob NOT NULL,
  `id_module_group` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id_nc`)
) ENGINE=InnoDB;


CREATE TABLE `tnetwork_component_group` (
  `id_sg` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `parent` mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB;


CREATE TABLE `tnetwork_profile` (
  `id_np` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) default '',
  PRIMARY KEY  (`id_np`)
) ENGINE=InnoDB;


CREATE TABLE `tnetwork_profile_component` (
  `id_npc` mediumint(8) unsigned NOT NULL auto_increment,
  `id_nc` mediumint(8) unsigned NOT NULL default '0',
  `id_np` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_npc`)
) ENGINE=InnoDB;

CREATE TABLE `trecon_task` (
  `id_rt` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) NOT NULL default '',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `subnet` varchar(64) NOT NULL default '',
  `id_network_server` int(10) unsigned NOT NULL default '0',
  `id_network_profile` int(10) unsigned NOT NULL default '0',
  `create_incident` tinyint(3) unsigned NOT NULL default '0',
  `id_group` int(10) unsigned NOT NULL default '1',
  `utimestamp` bigint(20) unsigned NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `interval_sweep` int(10) unsigned NOT NULL default '0',
  `id_network_server_assigned` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_rt`)
) ENGINE=InnoDB;


CREATE TABLE `tnews` (
  `id_news` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `author` varchar(255)  NOT NULL DEFAULT '',
  `subject` varchar(255)  NOT NULL DEFAULT '',
  `text` TEXT NOT NULL,
  `timestamp` DATETIME  NOT NULL DEFAULT 0,
  PRIMARY KEY(`id_news`)
) ENGINE = InnoDB;

CREATE TABLE `tgraph` (
  `id_graph` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` varchar(100) NOT NULL default '',
  `name` varchar(150) NOT NULL default '',
  `description` TEXT NOT NULL,
  `period` int(11) NOT NULL default '0',
  `width` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `height` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `private` tinyint(1) UNSIGNED NOT NULL default 0,
  `events` tinyint(1) UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id_graph`)
) ENGINE = InnoDB;

CREATE TABLE `tgraph_source` (
  `id_gs` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_graph` int(11) NOT NULL default 0,
  `id_agent_module` int(11) NOT NULL default 0,
  `weight` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`id_gs`)
) ENGINE = InnoDB;


CREATE TABLE `treport` (
  `id_report` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` varchar(100) NOT NULL default '',
  `name` varchar(150) NOT NULL default '',
  `description` TEXT NOT NULL,
  `private` tinyint(1) UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id_report`)
) ENGINE = InnoDB;

CREATE TABLE `treport_content` (
  `id_rc` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_report` INTEGER UNSIGNED NOT NULL default 0,
  `id_gs` INTEGER UNSIGNED NOT NULL default 0,
  `id_agent_module` int(11) NOT NULL default 0,
  `type` tinyint(1) UNSIGNED NOT NULL default 0,
  `period` int(11) NOT NULL default 0,
  `sla_max` int(11) NOT NULL default 0,
  `sla_min` int(11) NOT NULL default 0,
  `sla_limit` int(11) NOT NULL default 0,
  PRIMARY KEY(`id_rc`)
) ENGINE = InnoDB;

CREATE TABLE `tlayout` (
  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50)  NOT NULL,
  `id_group` INTEGER UNSIGNED NOT NULL,
  `background` varchar(200)  NOT NULL,
  `fullscreen` tinyint(1) UNSIGNED NOT NULL default 0,
  `height` INTEGER UNSIGNED NOT NULL default 0,
  `width` INTEGER UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id`)
)  ENGINE = InnoDB;

CREATE TABLE `tlayout_data` (
  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_layout` INTEGER UNSIGNED NOT NULL default 0,
  `pos_x` INTEGER UNSIGNED NOT NULL default 0,
  `pos_y` INTEGER UNSIGNED NOT NULL default 0,
  `height` INTEGER UNSIGNED NOT NULL default 0,
  `width` INTEGER UNSIGNED NOT NULL default 0,
  `label` varchar(200) DEFAULT "",
  `image` varchar(200) DEFAULT "",
  `type` tinyint(1) UNSIGNED NOT NULL default 0,
  `period` INTEGER UNSIGNED NOT NULL default 3600,
  `id_agente_modulo` mediumint(8) unsigned NOT NULL default '0',
  `id_layout_linked` INTEGER unsigned NOT NULL default '0',
  `parent_item` INTEGER UNSIGNED NOT NULL default 0,
  `label_color` varchar(20) DEFAULT "",
  `no_link_color` tinyint(1) UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id`)
) ENGINE = InnoDB;
