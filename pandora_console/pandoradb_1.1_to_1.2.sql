-- Pandora 1.1 to 1.2 SQL Migration script
-- !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
-- CAUTION, BEFORE RUNNING THIS FILE, READ DOCUMENTATION 
-- ABOUT HOW TO MIGRATE FROM PANDORA 1.1 to 1.2 VERSION
-- !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

-- Quick guide for migration:
-- 1th
-- DUMP all tagente_datos records to a safe file
-- for example
-- mysqldump --no-create-info -u root -p pandora tagente_datos > /tmp/pandora.conv.tmp
-- 2th
-- Run this SQL script, for example
-- cat pandoradb_1.1_to_1.2.sql | mysql -u root -p -D pandora
-- 3th
-- Reimport data from first step
-- cat /tmp/pandora.conv.tmp | mysql -u root -p -D pandora
-- 4th
-- Delete /tmp/pandora.conv.tmp file (rm -f /tmp/pandora.conv.tmp)

CREATE TABLE `tagent_access` (
  `id_ac` bigint(20) unsigned NOT NULL auto_increment,
  `id_agent` int(11) NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_ac`),
  KEY `agent_index` (`id_agent`)
) TYPE=InnoDB;

ALTER TABLE talerta_agente_modulo MODIFY COLUMN al_campo3 tinytext default '';
ALTER TABLE tagente ADD column agent_type int(2) unsigned NOT NULL default '0';
ALTER TABLE tagente ADD column id_server int(4) unsigned NOT NULL default '0';
ALTER TABLE tagente_datos_string MODIFY COLUMN datos mediumtext NOT NULL;
ALTER TABLE tagente_estado ADD column `id_agente` int(11) NOT NULL default '0' AFTER estado;
ALTER TABLE tagente_estado ADD column `last_try` datetime default NULL AFTER id_agente;
ALTER TABLE tagente_modulo ADD column `module_interval` int(4) unsigned default '0' AFTER min;
ALTER TABLE tagente_modulo ADD column `tcp_port` int(4) unsigned default '0' AFTER module_interval;
ALTER TABLE tagente_modulo ADD column `tcp_send` varchar(150) default '' AFTER  tcp_port;
ALTER TABLE tagente_modulo ADD column `tcp_rcv` varchar(100) default '' AFTER  tcp_send;
ALTER TABLE tagente_modulo ADD column `snmp_community` varchar(100) default '' AFTER  tcp_rcv;
ALTER TABLE tagente_modulo ADD column `snmp_oid` varchar(255) default '0' AFTER  snmp_community;
ALTER TABLE tagente_modulo ADD column `ip_target` varchar(100) default '' AFTER  snmp_oid;
ALTER TABLE tagente_modulo ADD column `id_module_group` int(4) unsigned default '0' AFTER  ip_target;
ALTER TABLE tagente_modulo ADD column `flag` tinyint(3) unsigned default '0' AFTER  id_module_group;

CREATE TABLE `talert_snmp` (
  `id_as` int(10) unsigned NOT NULL auto_increment,
  `id_alert` int(10) unsigned NOT NULL default '0',
  `al_field1` varchar(100) NOT NULL default '',
  `al_field2` varchar(255) NOT NULL default '',
  `al_field3` varchar(255) NOT NULL default '',
  `description` varchar(255) default '',
  `alert_type` int(2) unsigned NOT NULL default '0',
  `agent` varchar(100) default '',
  `custom_oid` varchar(200) default '',
  `oid` varchar(255) NOT NULL default '',
  `time_threshold` int(11) NOT NULL default '0',
  `times_fired` int(2) unsigned NOT NULL default '0',
  `last_fired` datetime NOT NULL default '0000-00-00 00:00:00',
  `max_alerts` int(11) NOT NULL default '1',
  `min_alerts` int(11) NOT NULL default '1',
  `internal_counter` int(2) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_as`)
) TYPE=InnoDB;

ALTER TABLE talerta_agente_modulo ADD column `module_type` int(11) NOT NULL default '0' AFTER  times_fired;
ALTER TABLE talerta_agente_modulo ADD column `min_alerts` int(4) NOT NULL default '0' AFTER  module_type;
ALTER TABLE talerta_agente_modulo ADD column `internal_counter` int(4) default '0' AFTER  min_alerts;
ALTER TABLE tgrupo ADD column `icon` varchar(50) default NULL AFTER  nombre;

CREATE TABLE `tlanguage` (
  `id_language` char(5) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_language`)
) TYPE=InnoDB;

CREATE TABLE `tmensajes` (
  `id_mensaje` bigint(20) unsigned NOT NULL auto_increment,
  `id_usuario_origen` varchar(100) NOT NULL default '',
  `id_usuario_destino` varchar(100) NOT NULL default '',
  `mensaje` mediumtext NOT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `subject` varchar(255) NOT NULL default '',
  `estado` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_mensaje`)
) TYPE=InnoDB;

CREATE TABLE `tmodule_group` (
  `id_mg` bigint(20) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  PRIMARY KEY  (`id_mg`)
) TYPE=InnoDB;

CREATE TABLE `tserver` (
  `id_server` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `ip_address` varchar(100) NOT NULL default '',
  `status` int(11) NOT NULL default '0',
  `laststart` datetime NOT NULL default '0000-00-00 00:00:00',
  `keepalive` datetime NOT NULL default '0000-00-00 00:00:00',
  `snmp_server` int(6) NOT NULL default '1',
  `network_server` int(11) NOT NULL default '0',
  `data_server` int(11) NOT NULL default '0',
  `master` smallint(6) NOT NULL default '1',
  `checksum` smallint(6) NOT NULL default '1',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_server`)
) TYPE=InnoDB;

ALTER TABLE ttipo_modulo ADD column `icon` varchar(100) default NULL AFTER descripcion;

CREATE TABLE `ttrap` (
  `id_trap` bigint(20) unsigned NOT NULL auto_increment,
  `source` varchar(50) NOT NULL default '',
  `oid` varchar(255) NOT NULL default '',
  `oid_custom` varchar(255) default '',
  `type` int(11) NOT NULL default '0',
  `type_custom` varchar(100) default '',
  `value` varchar(255) default '',
  `value_custom` varchar(255) default '',
  `alerted` smallint(6) NOT NULL default '0',
  `status` smallint(6) NOT NULL default '0',
  `id_usuario` varchar(150) default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_trap`)
) TYPE=InnoDB COMMENT='SNMP Trap table';

UPDATE tconfig set value = "1.2" where token = "db_scheme_version";
UPDATE tconfig set value = "en" where token = "language_code";
INSERT INTO tconfig (token,value) VALUES ('db_scheme_buile','PD060926');
INSERT INTO tconfig (token,value) VALUES ('truetype','0');
INSERT INTO tconfig (token,value) VALUES ('graph_order','1');

INSERT INTO `tlanguage` VALUES ('bb','Bable');
INSERT INTO `tlanguage` VALUES ('en','English');
INSERT INTO `tlanguage` VALUES ('es_es','Espa&ntilde;ol');
INSERT INTO `tlanguage` VALUES ('es_la','Espa&ntilde;ol-Latinoam&eacute;rica');
INSERT INTO `tlanguage` VALUES ('eu','Euskera');
INSERT INTO `tlanguage` VALUES ('pt_br','Portuguese-Brazil');
INSERT INTO `tlanguage` VALUES ('fr','Fran&ccedil;ais');
INSERT INTO `tlanguage` VALUES ('ca','Catal&agrave;');

INSERT INTO `tmodule_group` VALUES (1,'General');
INSERT INTO `tmodule_group` VALUES (2,'Networking');
INSERT INTO `tmodule_group` VALUES (3,'Application');
INSERT INTO `tmodule_group` VALUES (4,'System');
INSERT INTO `tmodule_group` VALUES (5,'Miscellaneous');

UPDATE ttipo_modulo set icon = "mod_data.png" where nombre = "generic_data";
UPDATE ttipo_modulo set icon = "mod_proc.png" where nombre = "generic_proc";
UPDATE ttipo_modulo set icon = "mod_string.png" where nombre = "generic_data_string";
UPDATE ttipo_modulo set icon = "mod_data_inc.png" where nombre = "generic_data_inc";
INSERT INTO `ttipo_modulo` VALUES (6,'remote_icmp_proc',3,'Remote ICMP network agent, boolean data','mod_icmp_proc.png');
INSERT INTO `ttipo_modulo` VALUES (7,'remote_icmp',2,'Remote ICMP network agent (latency)','mod_icmp_data.png');
INSERT INTO `ttipo_modulo` VALUES (8,'remote_tcp',2,'Remote TCP network agent, numeric data','mod_tcp_data.png');
INSERT INTO `ttipo_modulo` VALUES (9,'remote_tcp_proc',3,'Remote TCP network agent, boolean data','mod_tcp_proc.png');
INSERT INTO `ttipo_modulo` VALUES (10,'remote_tcp_string',2,'Remote TCP network agent, alphanumeric data','mod_tcp_string.png');
INSERT INTO `ttipo_modulo` VALUES (11,'remote_tcp_inc',2,'Remote TCP network agent, incremental data','mod_tcp_inc.png');
INSERT INTO `ttipo_modulo` VALUES (12,'remote_udp_proc',3,'Remote UDP network agent, boolean data','mod_udp_proc.png');
INSERT INTO `ttipo_modulo` VALUES (15,'remote_snmp',2,'Remote SNMP network agent, numeric data','mod_snmp_data.png');
INSERT INTO `ttipo_modulo` VALUES (16,'remote_snmp_inc',2,'Remote SNMP network agent, incremental data','mod_snmp_inc.png');
INSERT INTO `ttipo_modulo` VALUES (17,'remote_snmp_string',2,'Remote SNMP network agent, alphanumeric data','mod_snmp_string.png');
INSERT INTO `ttipo_modulo` VALUES (18,'remote_snmp_proc',1,'Remote SNMP network agent, boolean data','mod_snmp_proc.png');

UPDATE tgrupo set icon = "others";
UPDATE tgrupo set icon = "servers" where nombre = "Servers";
UPDATE tgrupo set icon = "ids" where nombre = "IDS";
UPDATE tgrupo set icon = "firewall" where nombre = "Firewall";
UPDATE tgrupo set icon = "db" where nombre = "Databases";
UPDATE tgrupo set icon = "comms" where nombre = "Comms";
UPDATE tgrupo set icon = "others" where nombre like "Other%";
UPDATE tgrupo set icon = "workstation" where nombre = "Workstations";
UPDATE tgrupo set icon = "apps" where nombre = "Applications";
INSERT INTO `tconfig_os` VALUES ('Network','Pandora Network Agent','network.png');

DROP TABLE tagente_datos;
CREATE TABLE `tagente_datos` (
  `id_agente_datos` bigint(10) unsigned NOT NULL auto_increment,
  `id_agente_modulo` bigint(4) NOT NULL default '0',
  `datos` double(18,2) default NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_agente` bigint(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_agente_datos`),
  KEY `data_index_1` (`id_agente_modulo`),
  KEY `data_index_2` (`id_agente`),
  KEY `data_index_3` (`timestamp`)
) TYPE=InnoDB;
