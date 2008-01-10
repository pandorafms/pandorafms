
----------------------------------------------------------------------
-- Database schema modifications to upgrade from 1.3 to 1.4 version
----------------------------------------------------------------------

-- Old tables deteled

--DROP TABLE tmodule; 
--DROP TABLE talerta_agente_modulo;
-- There is not migration code yet, do not delete without make backup !


-- New tables

CREATE TABLE `tagent_data_image` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` mediumint(8) unsigned NOT NULL default '0',
  `blob` blob NOT NULL,
  `filename` varchar(255) default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_agente` mediumint(8) unsigned NOT NULL default '0',
  `utimestamp` int(10) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `img_idx2` (`id_agente`,`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE tnotification (
        `id` int(11) unsigned NOT NULL auto_increment,
        `name` varchar(255) default '',
        `description` varchar(255) default '',
        `id_alerta` int(11) NOT NULL default '0',
        `id_agent` int(11) NOT NULL default '0',
        `al_f1` varchar(255) default '',
        `al_f2` mediumtext NOT NULL,
        `al_f3` mediumtext NOT NULL,
        `alrec_f1` varchar(255) default '',
        `alrec_f2` mediumtext NOT NULL,
        `alrec_f3` mediumtext NOT NULL,
        `recovery_notify` tinyint(3) default '0',
        `disabled` tinyint(3) default '0',
        `last_fired` datetime NOT NULL default '0000-00-00 00:00:00',
        PRIMARY KEY  (`id_aam`),
        KEY `tnotif_indx_1` (`id_alerta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tnotification_component` (
        `id` int(11) unsigned NOT NULL auto_increment,
        `id_notification` int(11) NOT NULL default '0',
        `id_agente_modulo` int(11) NOT NULL default '0',
        `dis_max` double(18,2) default NULL,
        `dis_min` double(18,2) default NULL,
        `alert_text` varchar(255) default '',
        `time_threshold` int(11) NOT NULL default '0',
        `last_fired` datetime NOT NULL default '0000-00-00 00:00:00',
        `max_alerts` int(4) NOT NULL default '1',
        `min_alerts` int(4) NOT NULL default '0',
        `logical_type` tinyint(3) NOT NULL default '0',
-- 0 OR, 1 AND, 2 NOT
        `internal_counter` int(4) default '0',
        `times_fired` int(11) NOT NULL default '0',
        `disabled` int(4) default '0',
        `time_from` TIME default '00:00:00',
        `time_to` TIME default '00:00:00',
        PRIMARY KEY  (`id_aam`),
        KEY `tnotifcom_indx_1` (`id_notification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Updated tables

ALTER TABLE tagente_modulo ADD COLUMN `disable` tinyint(3) unsigned NULL default 0;
ALTER TABLE tagente_modulo ADD COLUMN `export` tinyint(3) unsigned default '0';
ALTER TABLE tagente ADD COLUMN `id_parent` mediumint(8) unsigned default '0';
