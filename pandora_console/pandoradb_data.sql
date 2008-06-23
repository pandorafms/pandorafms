-- Pandora FMS - the Free Monitoring System
-- ========================================
-- Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
-- Please see http://pandora.sourceforge.net for full contribution list

-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License
-- as published by the Free Software Foundation for version 2.
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

-- Database Data for Pandora FMS 2.0

-- PLEASE NO NOT USE MULTILINE COMMENTS 
-- Because Pandora Installer don't understand them
-- and fails creating database !!!



--
-- Dumping data for table `talerta`
--

INSERT INTO `talerta` VALUES (1,'Compound only', 'Internal type', 'This alert will not be executed individually');
INSERT INTO `talerta` VALUES (2,'eMail','Internal type', 'This alert send an email using internal Pandora FMS Server SMTP capabilities (defined in each server, using:\r\n_field1_ as destination email address, and\r\n_field2_ as subject for message. \r\n_field3_ as text of message.');
INSERT INTO `talerta` VALUES (3,'Internal Audit','Internal type','This alert save alert in Pandora interal audit system. Fields are static and only _field1_ is used.');
INSERT INTO `talerta` VALUES (4,'Pandora FMS Event','Internal type','This alert create an special event into Pandora FMS event manager.');

INSERT INTO `talerta` VALUES (5,'Pandora FMS Alertlog','echo _timestamp_ pandora _agent_ _data_ _field1_ _field2_ >> /var/log/pandora/pandora_alert.log','This is a default alert to write alerts in a standard ASCII  plaintext log file in /var/log/pandora/pandora_alert.log\r\n');
INSERT INTO `talerta` VALUES (6,'SNMP Trap','/usr/bin/snmptrap -v 1 -c trap_public 192.168.0.4 1.1.1.1.1.1.1.1 _agent_ _field1_','Send a SNMPTRAP to 192.168.0.4. Please review config and adapt to your needs, this is only a sample, not functional itself.');
INSERT INTO `talerta` VALUES (7,'Syslog','logger -p daemon.alert Pandora Alert _agent_ _data_ _field1_ _field2_','Uses field1 and field2 to generate Syslog alert in facility daemon with "alert" level.');
INSERT INTO `talerta` VALUES (8,'Sound Alert','/usr/bin/play /usr/share/sounds/alarm.wav','');
INSERT INTO `talerta` VALUES (9,'Jabber Alert','echo _field3_ | sendxmpp -r _field1_ --chatroom _field2_','Send jabber alert to chat room in a predefined server (configure first .sendxmpprc file). Uses field3 as text message, field1 as useralias for source message, and field2 for chatroom name');


--
-- Dumping data for table `tconfig`
--


/*!40000 ALTER TABLE `tconfig` DISABLE KEYS */;
LOCK TABLES `tconfig` WRITE;
INSERT INTO `tconfig` VALUES 
(1,'language_code','en'),
(3,'block_size','20'),
(4,'days_purge','60'),
(5,'days_compact','15'),
(6,'graph_res','5'),
(7,'step_compact','1'),
(8,'db_scheme_version','2.0'),
(9,'db_scheme_build','PD80619'),
(13,'show_unknown','0'),
(14,'show_lastalerts','1'),
(15,'style','pandora'),
(16, 'remote_config', '/var/spool/pandora/data_in'),
(17, 'graph_color1', '#38B800'),
(18, 'graph_color2', '#42D100'),
(19, 'graph_color3', '#89FF09')
;
UNLOCK TABLES;
/*!40000 ALTER TABLE `tconfig` ENABLE KEYS */;

--
-- Dumping data for table `tconfig_os`
--


/*!40000 ALTER TABLE `tconfig_os` DISABLE KEYS */;
LOCK TABLES `tconfig_os` WRITE;
INSERT INTO `tconfig_os` VALUES 
(1,'Linux','Linux: All versions','so_linux.png'),
(2,'Solaris','Sun Solaris','so_solaris.png'),
(3,'AIX','IBM AIX','so_aix.png'),
(4,'BSD','OpenBSD, FreeBSD and Others','so_bsd.png'),
(5,'HP-UX','HP-UX Unix OS','so_hpux.png'),
(7,'Cisco','CISCO IOS','so_cisco.png'),
(8,'MacOS','MAC OS','so_mac.png'),
(9,'Windows','Microsoft Windows OS','so_win.png'),
(10,'Other','Other SO','so_other.png'),
(11,'Network','Pandora Network Agent','network.png');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tconfig_os` ENABLE KEYS */;

--
-- Dumping data for table `tgrupo`
--

LOCK TABLES `tgrupo` WRITE;
INSERT INTO `tgrupo` VALUES 
(1,'All','world',0,0),
(2,'Servers','server_database',0,0),
(4,'Firewalls','firewall',0,0),
(8,'Databases','database_gear',0,0),
(9,'Network','transmit',0,0),
(10,'Unknown','world',0,0),
(11,'Workstations','computer',0,0),
(12,'Applications','applications',0,0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `tgrupo` ENABLE KEYS */;

--
-- Dumping data for table `tlanguage`
--


/*!40000 ALTER TABLE `tlanguage` DISABLE KEYS */;
LOCK TABLES `tlanguage` WRITE;
INSERT INTO `tlanguage` VALUES ('en','English');
--INSERT INTO `tlanguage` VALUES ('es_es','Espa&ntilde;ol');
--INSERT INTO `tlanguage` VALUES ('de','Deutch');
--INSERT INTO `tlanguage` VALUES ('fr','Fran&ccedil;ais');
--INSERT INTO `tlanguage` VALUES ('pt_br','Portugu&ecirc;s-Brasil'); 

UNLOCK TABLES;
/*!40000 ALTER TABLE `tlanguage` ENABLE KEYS */;

--
-- Dumping data for table `tlink`
--


/*!40000 ALTER TABLE `tlink` DISABLE KEYS */;
LOCK TABLES `tlink` WRITE;
INSERT INTO `tlink` VALUES 
(0000000001,'GeekTools','www.geektools.com'),
(0000000002,'CentralOPS','http://www.centralops.net/'),
(0000000003,'Pandora FMS','http://pandora.sourceforge.net'),
(0000000004,'Babel Enterprise','http://babel.sourceforge.net'),
(0000000006,'Openideas','http://www.openideas.info'),
(0000000007,'Google','http://www.google.com'),
(0000000008,'ArticaST','http://www.artica.es');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tlink` ENABLE KEYS */;

--
-- Dumping data for table `tmodule_group`
--


/*!40000 ALTER TABLE `tmodule_group` DISABLE KEYS */;
LOCK TABLES `tmodule_group` WRITE;
INSERT INTO `tmodule_group` VALUES 
(1,'General'),
(2,'Networking'),
(3,'Application'),
(4,'System'),
(5,'Miscellaneous');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tmodule_group` ENABLE KEYS */;


--
-- Dumping data for table `torigen`
--
INSERT INTO `torigen` VALUES ('Operating System event'),('IDS events'),('Firewall records'),('Database event'),('Application data'),('Logfiles'),('Other data source'),('Pandora FMS Event'),('User report'),('Unknown source');

--
-- Dumping data for table `ttipo_modulo`
--

INSERT INTO `ttipo_modulo` VALUES 
(1,'generic_data',0,'Generic module to adquire numeric data','mod_data.png'),
(2,'generic_proc',1,'Generic module to adquire boolean data','mod_proc.png'),
(3,'generic_data_string',2,'Generic module to adquire alphanumeric data','mod_string.png'),
(4,'generic_data_inc',0,'Generic module to adquire numeric incremental data','mod_data_inc.png'),

(6,'remote_icmp_proc',4,'Remote ICMP network agent, boolean data','mod_icmp_proc.png'),
(7,'remote_icmp',3,'Remote ICMP network agent (latency)','mod_icmp_data.png'),
(8,'remote_tcp',3,'Remote TCP network agent, numeric data','mod_tcp_data.png'),
(9,'remote_tcp_proc',4,'Remote TCP network agent, boolean data','mod_tcp_proc.png'),
(10,'remote_tcp_string',5,'Remote TCP network agent, alphanumeric data','mod_tcp_string.png'),
(11,'remote_tcp_inc',3,'Remote TCP network agent, incremental data','mod_tcp_inc.png'),
(15,'remote_snmp',3,'Remote SNMP network agent, numeric data','mod_snmp_data.png'),
(16,'remote_snmp_inc',3,'Remote SNMP network agent, incremental data','mod_snmp_inc.png'),
(17,'remote_snmp_string',5,'Remote SNMP network agent, alphanumeric data','mod_snmp_string.png'),
(18,'remote_snmp_proc',4,'Remote SNMP network agent, boolean data','mod_snmp_proc.png'), 

(19,'image_jpg',9,'Image JPG data', 'mod_image_jpg.png'), 
(20,'image_png',9,'Image PNG data', 'mod_image_png.png'), 
(21,'async_proc', 7, 'Asyncronous proc data', 'mod_async_proc.png'), 
(22,'async_data', 6, 'Asyncronous numeric data', 'mod_async_data.png'), 
(23,'async_string', 8, 'Asyncronous string data', 'mod_async_string.png'),
(24,'async_inc', 6, 'Asyncronous incremental data', 'mod_async_inc.png'),  
(100,'keep_alive',-1,'KeepAlive','mod_keepalive.png');

/* Categoria field is used to segregate several types (plugin, agents, network) on their data
  types, could be used or could be avoided and use directly primary key (id_tipo) */

--
-- Dumping data for table `tusuario`
--

INSERT INTO `tusuario` VALUES ('admin','Default Admin','1da7ee7d45b96d0e1f45ee4ee23da560','Admin Pandora','2007-03-27 18:59:39','admin_pandora@nowhere.net','555-555-555',1);

--
-- Dumping data for table `tusuario_perfil`
--

INSERT INTO `tusuario_perfil` VALUES (1,'admin',5,1,'admin');

--
-- Dumping data for table `tperfil`
--

INSERT INTO `tperfil` VALUES (1,'Operator (Read)',0,1,0,1,0,0,0,0,0,0),(2,'Operator (Write)',1,1,0,1,0,0,0,0,0,0),(3,'Chief Operator',1,1,1,1,0,0,0,0,0,0),(4,'Group coordinator',1,1,1,1,1,1,1,0,0,0),(5,'Pandora Administrator',1,1,1,1,1,1,1,1,1,1);

INSERT INTO `tnews` VALUES (1,'admin','Welcome to Pandora FMS 2.0!','This is the new Pandora FMS Console. A lot of new features have been added since last version. Please read the documentation about it, and feel free to test any option.\r\n\r\nThe Pandora FMS Team.','2007-06-22 13:03:20');

INSERT INTO tmodule VALUES (1,'Agent module');
INSERT INTO tmodule VALUES (2,'Network module');
INSERT INTO tmodule VALUES (4,'Plugin module');
INSERT INTO tmodule VALUES (5,'Prediction module');
INSERT INTO tmodule VALUES (6,'WMI module');

INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (1,'OS Total process','Total process in Operating System (UNIX MIB)',13,15,0,0,180,0,'','','public','HOST-RESOURCES-MIB::hrSystemProcesses.0 ',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (2,'OS CPU Load (1 min)','CPU Load in Operating System (UNIX MIB)',13,15,0,0,180,0,'','','public','UCD-SNMP-MIB::laLoad.1',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (3,'Sysname','Get name of system using SNMP standard MIB',1,17,0,0,900,0,'','','public','.1.3.6.1.2.1.1.1.0',1);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (4,'OS Users','Active users in Operating System (UNIX MIB)',13,15,0,0,180,0,'','','public','HOST-RESOURCES-MIB::hrSystemNumUsers.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (11,'Catalyst CPU Usage (5min)','Cisco Catalyst Switches CPU Usage. Taken from ftp://ftp.cisco.com/pub/mibs/oid/OLD-CISCO-CPU-MIB.oid',2,15,0,0,180,0,'','','public','1.3.6.1.4.1.9.2.1.58',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (22,'HSRP Status','Get status of HSRP',2,18,0,0,180,0,'','','public','1.3.6.1.4.1.9.9.106.1.2.1.1.15.12.106',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (24,'NIC #1 status','Status of NIC#1',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.1',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (25,'NIC #2 status','Status of NIC #2',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.2',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (26,'NIC #3 status','Status of NIC #3',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.3',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (27,'NIC #1 outOctects','Output throughtput on Interface #1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.1',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (28,'NIC #2 outOctects','Output troughtput on interface #2',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.2',1);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (29,'NIC #3 outOctects','Output troughtput on Interface #3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.3',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (30,'NIC #1 inOctects','Input troughtput on Interface #1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.1',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (31,'NIC #2 inOctects','Input throughtput for interface #2',10,16,0,0,180,0,'','NULL','public','.1.3.6.1.2.1.2.2.1.10.2',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (32,'NIC #3 inOctects','Input throught on interface #3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.3',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (34,'Host Alive','Check if host is alive using ICMP ping check.',10,6,0,0,120,0,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (36,'Host Latency','Get host network latency in miliseconds, using ICMP.',10,7,0,0,180,0,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (37,'Check HTTP Server','Test APACHE2 HTTP service remotely (Protocol response, not only openport)',10,9,0,0,300,80,'GET / HTTP/1.0^M^M','HTTP/1.1 200 OK','','',3);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (38,'Check FTP Server','Check FTP protocol, not only check port.',10,9,0,0,300,21,'QUIT','221','','',3);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (39,'Check SSH Server','Checks port 22 is opened',10,9,0,0,300,22,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (40,'Check Telnet server','Check telnet port',10,9,0,0,300,23,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (41,'Check SMTP server','Check if SMTP port it&#039;s open',10,9,0,0,300,25,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (42,'Check POP3 server','Check POP3 port.',10,9,0,0,300,110,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (43,'NIC #7 outOctects','Get outcoming octects from NIC #7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.7',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (44,'NIC #7 inOctects','Get incoming octects from NIC #7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.7',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (45,'NIC #4 Status','Get status of NIC #4',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.4',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (46,'NIC #5 Status','Get status of NIC #5',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.5',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (47,'NIC #6 Status','Get status of NIC #6',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.6',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (48,'NIC #7 Status','Get status of NIC #7',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.7',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (49,'OS CPU Load (5 min)','CPU load on a 5 min average interval. UCD-SNMP Mib (Usually for all Linux and some UNIX)',13,15,0,0,180,0,'','','public','UCD-SNMP-MIB::laLoad.2',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (50,'System Description','Get system description (all mibs).',1,17,0,0,9000,0,'','','public','SNMPv2-MIB::sysDescr.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (51,'OS Raw Interrupts','Get system raw interrupts from SO',13,16,0,0,180,0,'','','public','UCD-SNMP-MIB::ssRawInterrupts.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (52,'OS IO Signals sent','IO Signals sent by Kernel',13,16,0,0,180,0,'','','public','UCD-SNMP-MIB::ssIOSent.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (53,'System Uptime','Sistem uptime in timeticks',1,15,0,0,180,0,'','','public','HOST-RESOURCES-MIB::hrSystemUptime.0',4);
INSERT INTO `tnetwork_component` VALUES (54,'System IO Recv','Linux System IO Recv ',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.6.0',4);
INSERT INTO `tnetwork_component` VALUES (55,'System SwapIn ','Linux System Swap In',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.3.0',1);
INSERT INTO `tnetwork_component` VALUES (56,'System Buffer Memory','Linux System Buffer Memory (used as available\nmemory)',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.4.14.0',4);
INSERT INTO `tnetwork_component` VALUES (57,'System Cached Memory','Linux System Cached Memory (used as free\nmemory)',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.4.15.0',4);
INSERT INTO `tnetwork_component` VALUES (58,'System Processes','Total system process on any host',12,15,0,0,180,0,'','','public','.1.3.6.1.2.1.25.1.6.0',4);
INSERT INTO `tnetwork_component` VALUES (59,'CPU User','Linux User CPU Usage (%)',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.9.0',4);
INSERT INTO `tnetwork_component` VALUES (60,'CPU System','Linux System CPU usage',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.10.0',4);
INSERT INTO `tnetwork_component` VALUES (177,'System Context Change','Linux System Context changes ',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.8.0',4);
INSERT INTO `tnetwork_component` VALUES (178,'System Interrupts','Linux system interrupts ',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.7.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (176,'Catalyst Free Mem','Taken from ftp://ftp.cisco.com/pub/mibs/oid/OLD-CISCO-MEMORY-MIB.oid',2,15,0,0,180,0,'','','public','1.3.6.1.4.1.9.2.1.8',4);

INSERT INTO `tnetwork_component` VALUES (61,'GigabitEthernet1/0/1 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10101',2);
INSERT INTO `tnetwork_component` VALUES (62,'GigabitEthernet1/0/2 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10102',2);
INSERT INTO `tnetwork_component` VALUES (63,'GigabitEthernet1/0/3 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10103',2);
INSERT INTO `tnetwork_component` VALUES (64,'GigabitEthernet1/0/4 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10104',2);
INSERT INTO `tnetwork_component` VALUES (65,'GigabitEthernet1/0/5 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10105',2);
INSERT INTO `tnetwork_component` VALUES (66,'GigabitEthernet1/0/6 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10106',2);
INSERT INTO `tnetwork_component` VALUES (67,'GigabitEthernet1/0/7 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10107',2);
INSERT INTO `tnetwork_component` VALUES (68,'GigabitEthernet1/0/8 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10108',2);
INSERT INTO `tnetwork_component` VALUES (69,'GigabitEthernet1/0/9 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10109',2);
INSERT INTO `tnetwork_component` VALUES (70,'GigabitEthernet1/0/10 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10110',2);
INSERT INTO `tnetwork_component` VALUES (71,'GigabitEthernet1/0/11 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10111',2);
INSERT INTO `tnetwork_component` VALUES (72,'GigabitEthernet1/0/12 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10112',2);
INSERT INTO `tnetwork_component` VALUES (73,'GigabitEthernet1/0/13 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10113',2);
INSERT INTO `tnetwork_component` VALUES (74,'GigabitEthernet1/0/14 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10114',2);
INSERT INTO `tnetwork_component` VALUES (75,'GigabitEthernet1/0/15 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10115',2);
INSERT INTO `tnetwork_component` VALUES (76,'GigabitEthernet1/0/16 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10116',2);
INSERT INTO `tnetwork_component` VALUES (77,'GigabitEthernet1/0/17 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10117',2);
INSERT INTO `tnetwork_component` VALUES (78,'GigabitEthernet1/0/18 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10118',2);
INSERT INTO `tnetwork_component` VALUES (79,'GigabitEthernet1/0/19 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10119',2);
INSERT INTO `tnetwork_component` VALUES (80,'GigabitEthernet1/0/20 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10120',2);
INSERT INTO `tnetwork_component` VALUES (81,'GigabitEthernet1/0/21 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10121',2);
INSERT INTO `tnetwork_component` VALUES (82,'GigabitEthernet1/0/22 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10122',2);
INSERT INTO `tnetwork_component` VALUES (83,'GigabitEthernet1/0/23 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10123',2);
INSERT INTO `tnetwork_component` VALUES (84,'GigabitEthernet1/0/24 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10124',2);
INSERT INTO `tnetwork_component` VALUES (85,'GigabitEthernet1/0/25 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10125',2);
INSERT INTO `tnetwork_component` VALUES (86,'GigabitEthernet1/0/26 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10126',2);
INSERT INTO `tnetwork_component` VALUES (87,'GigabitEthernet1/0/27 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10127',2);
INSERT INTO `tnetwork_component` VALUES (88,'GigabitEthernet1/0/28 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10128',2);
INSERT INTO `tnetwork_component` VALUES (90,'GigabitEthernet2/0/1 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10601',2);
INSERT INTO `tnetwork_component` VALUES (91,'GigabitEthernet2/0/2 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10602',2);
INSERT INTO `tnetwork_component` VALUES (92,'GigabitEthernet2/0/3 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10603',2);
INSERT INTO `tnetwork_component` VALUES (93,'GigabitEthernet2/0/4 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10604',2);
INSERT INTO `tnetwork_component` VALUES (94,'GigabitEthernet2/0/5 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10605',2);
INSERT INTO `tnetwork_component` VALUES (95,'GigabitEthernet2/0/6 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10606',2);
INSERT INTO `tnetwork_component` VALUES (96,'GigabitEthernet2/0/7 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10607',2);
INSERT INTO `tnetwork_component` VALUES (97,'GigabitEthernet2/0/8 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10608',2);
INSERT INTO `tnetwork_component` VALUES (98,'GigabitEthernet2/0/9 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10609',2);
INSERT INTO `tnetwork_component` VALUES (99,'GigabitEthernet2/0/10 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10610',2);
INSERT INTO `tnetwork_component` VALUES (100,'GigabitEthernet2/0/11 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10611',2);
INSERT INTO `tnetwork_component` VALUES (101,'GigabitEthernet2/0/12 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10612',2);
INSERT INTO `tnetwork_component` VALUES (102,'GigabitEthernet2/0/13 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10613',2);
INSERT INTO `tnetwork_component` VALUES (103,'GigabitEthernet2/0/14 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10614',2);
INSERT INTO `tnetwork_component` VALUES (104,'GigabitEthernet2/0/15 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10615',2);
INSERT INTO `tnetwork_component` VALUES (105,'GigabitEthernet2/0/16 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10616',2);
INSERT INTO `tnetwork_component` VALUES (106,'GigabitEthernet2/0/17 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10617',2);
INSERT INTO `tnetwork_component` VALUES (107,'GigabitEthernet2/0/18 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10618',2);
INSERT INTO `tnetwork_component` VALUES (108,'GigabitEthernet2/0/19 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10619',2);
INSERT INTO `tnetwork_component` VALUES (109,'GigabitEthernet2/0/20 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10620',2);
INSERT INTO `tnetwork_component` VALUES (110,'GigabitEthernet2/0/21 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10621',2);
INSERT INTO `tnetwork_component` VALUES (111,'GigabitEthernet2/0/22 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10622',2);
INSERT INTO `tnetwork_component` VALUES (112,'GigabitEthernet2/0/23 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10623',2);
INSERT INTO `tnetwork_component` VALUES (113,'GigabitEthernet2/0/24 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10624',2);
INSERT INTO `tnetwork_component` VALUES (114,'GigabitEthernet2/0/25 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10625',2);
INSERT INTO `tnetwork_component` VALUES (115,'GigabitEthernet2/0/26 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10626',2);
INSERT INTO `tnetwork_component` VALUES (116,'GigabitEthernet2/0/27 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10627',2);
INSERT INTO `tnetwork_component` VALUES (117,'GigabitEthernet2/0/28 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10628',2);
INSERT INTO `tnetwork_component` VALUES (152,'GigabitEthernet1/0/35 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10135',2);
INSERT INTO `tnetwork_component` VALUES (153,'GigabitEthernet1/0/36 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10136',2);
INSERT INTO `tnetwork_component` VALUES (154,'GigabitEthernet1/0/37 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10137',2);
INSERT INTO `tnetwork_component` VALUES (155,'GigabitEthernet1/0/38 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10138',2);
INSERT INTO `tnetwork_component` VALUES (156,'GigabitEthernet1/0/39 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10139',2);
INSERT INTO `tnetwork_component` VALUES (157,'GigabitEthernet1/0/40 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10140',2);
INSERT INTO `tnetwork_component` VALUES (158,'GigabitEthernet1/0/41 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10141',2);
INSERT INTO `tnetwork_component` VALUES (159,'GigabitEthernet1/0/42 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10142',2);
INSERT INTO `tnetwork_component` VALUES (160,'GigabitEthernet1/0/43 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10143',2);
INSERT INTO `tnetwork_component` VALUES (161,'GigabitEthernet1/0/44 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10144',2);
INSERT INTO `tnetwork_component` VALUES (162,'GigabitEthernet1/0/45 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10145',2);
INSERT INTO `tnetwork_component` VALUES (163,'GigabitEthernet1/0/46 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10146',2);
INSERT INTO `tnetwork_component` VALUES (164,'GigabitEthernet1/0/47 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10147',2);
INSERT INTO `tnetwork_component` VALUES (165,'GigabitEthernet1/0/48 Status','',2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10148',2);
INSERT INTO `tnetwork_component` VALUES (170,'GigabitEthernet1/0/29 Status',NULL,2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10129',2);
INSERT INTO `tnetwork_component` VALUES (171,'GigabitEthernet1/0/30 Status',NULL,2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10130',2);
INSERT INTO `tnetwork_component` VALUES (172,'GigabitEthernet1/0/31 Status',NULL,2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10131',2);
INSERT INTO `tnetwork_component` VALUES (173,'GigabitEthernet1/0/32 Status',NULL,2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10132',2);
INSERT INTO `tnetwork_component` VALUES (174,'GigabitEthernet1/0/33 Status',NULL,2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10133',2);
INSERT INTO `tnetwork_component` VALUES (175,'GigabitEthernet1/0/34 Status',NULL,2,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.10134',2);




--
-- Dumping data for table `tnetwork_component_group`
--

INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (1,'General group',0);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (2,'Cisco MIBs',10);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (5,'UNIX MIBs',12);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (10,'Network Management',0);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (11,'Microsoft Windows MIB',12);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (12,'Operating Systems',0);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (13,'UCD Mibs (Linux, UCD-SNMP)',12);


-- Network profile

INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (1,'Basic Network Monitoring','This includes basic SNMP, ICMP, and TCP checks.');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (2,'Basic Monitoring','Only ICMP check');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (3,'Basic DMZ Server monitoring','This group of network checks, checks for default services located on DMZ servers...');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (4,'Full SNMP Monitoring','');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (5,'Linux Server','Full Monitoring of a Linux server services.');

INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (1,24,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (2,25,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (3,27,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (4,28,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (5,30,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (6,31,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (7,34,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (8,39,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (9,34,2);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (10,34,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (11,37,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (12,39,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (13,38,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (14,24,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (15,3,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (16,24,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (17,25,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (18,26,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (19,27,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (20,28,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (21,29,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (22,30,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (23,31,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (24,32,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (25,45,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (26,46,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (27,47,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (28,48,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (29,3,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (30,50,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (31,53,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (32,24,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (33,30,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (34,27,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (35,34,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (36,1,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (37,2,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (38,49,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (39,4,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (40,51,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (41,52,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (42,39,5);


