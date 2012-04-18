-- Pandora FMS - the Flexible Monitoring System
-- ============================================
-- Copyright (c) 2011 Artica Soluciones Tecnologicas, http://www.artica.es
-- Please see http://www.pandorafms.org for full contribution list

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

-- Database Data for Pandora FMS 4.0dev

-- PLEASE NO NOT USE MULTILINE COMMENTS 
-- Because Pandora Installer don't understand them
-- and fails creating database !!!


-- Dumping data for table `talert_commands`
--

INSERT INTO `talert_commands` VALUES (1,'eMail','Internal type', 'This alert send an email using internal Pandora FMS Server SMTP capabilities (defined in each server, using:\r\n_field1_ as destination email address, and\r\n_field2_ as subject for message. \r\n_field3_ as text of message.', 1);
INSERT INTO `talert_commands` VALUES (2,'Internal Audit','Internal type','This alert save alert in Pandora interal audit system. Fields are static and only _field1_ is used.', 1);
INSERT INTO `talert_commands` VALUES (3,'Pandora FMS Event','Internal type','This alert create an special event into Pandora FMS event manager.', 1);
INSERT INTO `talert_commands` VALUES (4,'Pandora FMS Alertlog','echo _timestamp_ pandora _agent_ _data_ _field1_ _field2_ >> /var/log/pandora/pandora_alert.log','This is a default alert to write alerts in a standard ASCII  plaintext log file in /var/log/pandora/pandora_alert.log\r\n', 0);
INSERT INTO `talert_commands` VALUES (5,'SNMP Trap','/usr/bin/snmptrap -v 1 -c trap_public 192.168.0.4 1.1.1.1.1.1.1.1 _agent_ _field1_','Send a SNMPTRAP to 192.168.0.4. Please review config and adapt to your needs, this is only a sample, not functional itself.', 0);
INSERT INTO `talert_commands` VALUES (6,'Syslog','logger -p daemon.alert Pandora Alert _agent_ _data_ _field1_ _field2_','Uses field1 and field2 to generate Syslog alert in facility daemon with "alert" level.', 0);
INSERT INTO `talert_commands` VALUES (7,'Sound Alert','/usr/bin/play /usr/share/sounds/alarm.wav','', 0);
INSERT INTO `talert_commands` VALUES (8,'Jabber Alert','echo _field3_ | sendxmpp -r _field1_ --chatroom _field2_','Send jabber alert to chat room in a predefined server (configure first .sendxmpprc file). Uses field3 as text message, field1 as useralias for source message, and field2 for chatroom name', 0);
INSERT INTO `talert_commands` VALUES (9,'SMS','sendsms _field1_ _field2_','Send SMS using the Pandora FMS standard SMS device, using smstools.  Uses field2 as text message, field1 as destination phone (include international prefix!)', 0);

--
-- Dumping data for table `tconfig`
--

LOCK TABLES `tconfig` WRITE;
INSERT INTO `tconfig` (`token`, `value`) VALUES 
('language','en_GB'),
('block_size','20'),
('days_purge','60'),
('days_delete_unknown','0'),
('days_compact','90'),
('graph_res','5'),
('step_compact','1'),
('db_scheme_version','4.0.1'),
('db_scheme_build','PD111213'),
('show_unknown','0'),
('show_lastalerts','1'),
('style','pandora'),
('remote_config', '/var/spool/pandora/data_in'),
('graph_color1', '#38B800'),
('graph_color2', '#42D100'),
('graph_color3', '#89FF09'),
('sla_period', '604800'),
('trap2agent', '0'),
('date_format', 'F j, Y, g:i a'),
('event_view_hr', 8),
('loginhash_pwd', ''),
('trap2agent', 0),
('prominent_time', 0),
('timesource', 'system'),
('realtimestats', '1'),
('stats_interval', '60'),
('activate_gis', '0'),
('timezone', 'Europe/Berlin'),
('string_purge', 7),
('audit_purge', 15),
('trap_purge', 7),
('event_purge', 15),
('gis_purge', 15),
('sound_alert', 'include/sounds/air_shock_alarm.wav'),
('sound_critical', 'include/sounds/Star_Trek_emergency_simulation.wav'),
('sound_warning', 'include/sounds/negativebeep.wav'),
('integria_enabled', '0'),
('integria_api_password', ''),
('integria_inventory', '0'),
('integria_url', '');

UNLOCK TABLES;

--
-- Dumping data for table `tconfig_os`
--

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
(11,'Network','Pandora FMS Network Agent','network.png'),
(12,'Web Server','Web Server/Application','network.png'),
(13,'Sensor',' Pandora FMS Hardware Agent (Sensor)','network.png'),
(14,'Embedded','Embedded device running a Pandora FMS agent','embedded.png'),
(15,'Android','Android agent','android.png'),
(16, 'VMware', 'VMware Architecture', 'so_vmware.png');
UNLOCK TABLES;


--
-- Dumping data for table `tgrupo`
--

LOCK TABLES `tgrupo` WRITE;
INSERT INTO `tgrupo` VALUES 
(2,'Servers','server_database',0,0,0,'',1),
(4,'Firewalls','firewall',0,0,0,'',1),
(8,'Databases','database_gear',0,0,0,'',1),
(9,'Network','transmit',0,0,0,'',1),
(10,'Unknown','world',0,0,0,'',1),
(11,'Workstations','computer',0,0,0,'',1),
(12,'Applications','applications',0,0,0,'',1),
(13,'Web','world',0,0,0,'',1);
UNLOCK TABLES;


--
-- Dumping data for table `tlanguage`
--



LOCK TABLES `tlanguage` WRITE;
INSERT INTO `tlanguage` VALUES ('en_GB','English');
INSERT INTO `tlanguage` VALUES ('es','Español');
INSERT INTO `tlanguage` VALUES ('ar','العربية');
INSERT INTO `tlanguage` VALUES ('ast','Asturianu');
INSERT INTO `tlanguage` VALUES ('bn', 'বাংলা');
INSERT INTO `tlanguage` VALUES ('ca','Catalá');
INSERT INTO `tlanguage` VALUES ('cs','Česky');
INSERT INTO `tlanguage` VALUES ('da','Dansk');
INSERT INTO `tlanguage` VALUES ('de','Deutch');
INSERT INTO `tlanguage` VALUES ('eu','Euskara');
INSERT INTO `tlanguage` VALUES ('el','Ελληνικά');
INSERT INTO `tlanguage` VALUES ('fi','Suomi');
INSERT INTO `tlanguage` VALUES ('fr','Français');
INSERT INTO `tlanguage` VALUES ('gl','Galego');
INSERT INTO `tlanguage` VALUES ('he','עברית');
INSERT INTO `tlanguage` VALUES ('hi','हिन्दी');
INSERT INTO `tlanguage` VALUES ('hu','Magyar');
INSERT INTO `tlanguage` VALUES ('it','Italiano');
INSERT INTO `tlanguage` VALUES ('ja','日本語');
INSERT INTO `tlanguage` VALUES ('ko','한국어');
INSERT INTO `tlanguage` VALUES ('nl','Nederlands');
INSERT INTO `tlanguage` VALUES ('pl','Polski');
INSERT INTO `tlanguage` VALUES ('pt_BR','Português-Brasil');
INSERT INTO `tlanguage` VALUES ('pt','Português');
INSERT INTO `tlanguage` VALUES ('ro','Română');
INSERT INTO `tlanguage` VALUES ('ru','Русский');
INSERT INTO `tlanguage` VALUES ('sk','Slovenčina');
INSERT INTO `tlanguage` VALUES ('sl','Slovenščina');
INSERT INTO `tlanguage` VALUES ('sv','Svenska');
INSERT INTO `tlanguage` VALUES ('te','తెలుగు');
INSERT INTO `tlanguage` VALUES ('tr','Türkçe');
INSERT INTO `tlanguage` VALUES ('uk','Українська');
INSERT INTO `tlanguage` VALUES ('zh_CN','简化字');
INSERT INTO `tlanguage` VALUES ('zh_TW','簡化字');

UNLOCK TABLES;

--
-- Dumping data for table `tlink`
--


LOCK TABLES `tlink` WRITE;
INSERT INTO `tlink` VALUES 
(1,'Pandora FMS Manual','https://openideas.info/wiki/index.php?title=Pandora'),
(2,'Pandora FMS','http://pandorafms.org'),
(3,'Report a bug','https://sourceforge.net/tracker/?func=add&amp;group_id=155200&amp;atid=794852'),
(4,'Suggest new feature','http://sourceforge.net/tracker/?group_id=155200&amp;atid=794855'),
(5,'Module library','http://pandorafms.org/?sec=community&amp;sec2=repository&amp;lng=es'),
(6,'Commercial support','http://pandorafms.com');

UNLOCK TABLES;

--
-- Dumping data for table `tmodule_group`
--

LOCK TABLES `tmodule_group` WRITE;
INSERT INTO `tmodule_group` VALUES 
(1,'General'),
(2,'Networking'),
(3,'Application'),
(4,'System'),
(5,'Miscellaneous'),
(6,'Performance'),
(7,'Database'),
(8,'Enviromental'),
(9,'Users');

UNLOCK TABLES;

--
-- Dumping data for table `torigen`
--

INSERT INTO `torigen` VALUES 
('Operating System event'),
('Firewall records'),
('Database event'),
('Application data'),
('Logfiles'),
('Other data source'),
('Pandora FMS Event'),
('User report'),
('Unknown source');

--
-- Dumping data for table `ttipo_modulo`
--

-- Identifiers 30 and 31 are reserved for Enterprise data types
INSERT INTO `ttipo_modulo` VALUES 
(1,'generic_data',0,'Generic numeric','mod_data.png'),
(2,'generic_proc',1,'Generic boolean','mod_proc.png'),
(3,'generic_data_string',2,'Generic string','mod_string.png'),
(4,'generic_data_inc',0,'Generic numeric incremental','mod_data_inc.png'),

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
(21,'async_proc', 7, 'Asyncronous proc data', 'mod_async_proc.png'), 
(22,'async_data', 6, 'Asyncronous numeric data', 'mod_async_data.png'), 
(23,'async_string', 8, 'Asyncronous string data', 'mod_async_string.png'),
(24,'log4x',0,'Log4x','mod_log4x.png'),
(100,'keep_alive',-1,'KeepAlive','mod_keepalive.png');

-- Categoria field is used to segregate several types
-- (plugin, agents, network) on their data
-- types, could be used or could be avoided and use directly primary key (id_tipo) 

--
-- Dumping data for table `tusuario`
--
INSERT INTO `tusuario` (`id_user`, `fullname`, `firstname`, `lastname`, `middlename`, `password`, `comments`, `last_connect`, `registered`, `email`, `phone`, `is_admin`, `flash_chart`, `language`, `block_size`) VALUES
('admin', 'Pandora', 'Pandora', 'Admin', '', '1da7ee7d45b96d0e1f45ee4ee23da560', 'Admin Pandora', 1232642121, 0, 'admin@example.com', '555-555-5555', 1, -1, 'default', 0);

--
-- Dumping data for table `tusuario_perfil`
--

INSERT INTO `tusuario_perfil` VALUES (1,'admin',5,0,'admin',0);

--
-- Dumping data for table `tperfil`
--

INSERT INTO `tperfil` VALUES (1,'Operator&#x20;&#40;Read&#41;',0,1,0,1,0,0,0,0,0,0),(2,'Operator&#x20;&#40;Write&#41;',1,1,0,1,0,0,0,0,0,0),(3,'Chief&#x20;Operator',1,1,1,1,0,0,0,0,0,0),(4,'Group&#x20;coordinator',1,1,1,1,1,1,1,0,1,0),(5,'Pandora&#x20;Administrator',1,1,1,1,1,1,1,1,1,1);

--
-- Dumping data for table `tnews`
--

INSERT INTO `tnews` VALUES (1,'admin','Welcome to Pandora FMS 4.0!','This is the new Pandora FMS Console. A lot of new features have been added since last version. Please read the documentation about it, and feel free to test any option.\r\n\r\nThe Pandora FMS Team.',NOW()),
(2,'admin','New Pandora FMS Agent Features','Feel free to test our new features for both Windows and Linux agents: Proxy and Broker modes.',NOW());

INSERT INTO tmodule VALUES (1,'Agent&#x20;module');
INSERT INTO tmodule VALUES (2,'Network&#x20;module');
INSERT INTO tmodule VALUES (4,'Plugin&#x20;module');
INSERT INTO tmodule VALUES (5,'Prediction&#x20;module');
INSERT INTO tmodule VALUES (6,'WMI&#x20;module');

INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (1,'OS&#x20;Total&#x20;process','Total&#x20;process&#x20;in&#x20;Operating&#x20;System&#x20;(UNIX&#x20;MIB)',13,15,0,0,180,0,'','','public','HOST-RESOURCES-MIB::hrSystemProcesses.0 ',4,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (2,'OS&#x20;CPU&#x20;Load&#x20;(1&#x20;min)','CPU&#x20;Load&#x20;in&#x20;Operating&#x20;System&#x20;(UNIX&#x20;MIB)',13,15,0,0,180,0,'','','public','UCD-SNMP-MIB::laLoad.1',4,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (3,'Sysname','Get&#x20;name&#x20;of&#x20;system&#x20;using&#x20;SNMP&#x20;standard&#x20;MIB',1,17,0,0,900,0,'','','public','.1.3.6.1.2.1.1.1.0',1,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (4,'OS&#x20;Users','Active&#x20;users&#x20;in&#x20;Operating&#x20;System&#x20;(UNIX&#x20;MIB)',13,15,0,0,180,0,'','','public','HOST-RESOURCES-MIB::hrSystemNumUsers.0',4,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (11,'Catalyst&#x20;CPU&#x20;Usage&#x20;(5min)','Cisco&#x20;Catayst&#x20;Switches&#x20;CPU&#x20;Usage.&#x20;Taken&#x20;from&#x20;ftp://ftp.cisco.com/pub/mibs/oid/OLD-CISCO-CPU-MIB.oid',2,15,0,0,180,0,'','','public','1.3.6.1.4.1.9.2.1.58',4,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (22,'HSRP&#x20;Status','Get&#x20;status&#x20;of&#x20;HSRP',2,18,0,0,180,0,'','','public','1.3.6.1.4.1.9.9.106.1.2.1.1.15.12.106',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (24,'NIC&#x20;#1&#x20;status','Status&#x20;of&#x20;NIC#1',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.1',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (25,'NIC&#x20;#2&#x20;status','Status&#x20;of&#x20;NIC&#x20;#2',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.2',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (26,'NIC&#x20;#3&#x20;status','Status&#x20;of&#x20;NIC&#x20;#3',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.3',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (27,'NIC&#x20;#1&#x20;outOctects','Output&#x20;throughtput&#x20;on&#x20;Interface&#x20;#1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.1',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (28,'NIC&#x20;#2&#x20;outOctects','Output&#x20;troughtput&#x20;on&#x20;interface&#x20;#2',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.2',1,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (29,'NIC&#x20;#3&#x20;outOctects','Output&#x20;troughtput&#x20;on&#x20;Interface&#x20;#3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.3',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (30,'NIC&#x20;#1&#x20;inOctects','Input&#x20;troughtput&#x20;on&#x20;Interface&#x20;#1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.1',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (31,'NIC&#x20;#2&#x20;inOctects','Input&#x20;throughtput&#x20;for&#x20;interface #2',10,16,0,0,180,0,'','NULL','public','.1.3.6.1.2.1.2.2.1.10.2',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (32,'NIC&#x20;#3&#x20;inOctects','Input&#x20;throught&#x20;on&#x20;interface&#x20;#3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.3',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (34,'Host&#x20;Alive','Check&#x20;if&#x20;host&#x20;is&#x20;alive&#x20;using&#x20;ICMP&#x20;ping&#x20;check.',10,6,0,0,120,0,'','','','',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (36,'Host&#x20;Latency','Get&#x20;host&#x20;network&#x20;latency&#x20;in&#x20;miliseconds,&#x20;using&#x20;ICMP.',10,7,0,0,180,0,'','','','',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (37,'Check&#x20;HTTP&#x20;Server','Test&#x20;APACHE2&#x20;HTTP&#x20;service&#x20;remotely&#x20;(Protocol&#x20;response,&#x20;not&#x20;only&#x20;openport)',10,9,0,0,300,80,'GET / HTTP/1.0^M^M','HTTP/1.1 200 OK','','',3,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (38,'Check&#x20;FTP&#x20;Server','Check&#x20;FTP&#x20;protocol,&#x20;not&#x20;only&#x20;check&#x20;port.',10,9,0,0,300,21,'QUIT','220','','',3,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (39,'Check&#x20;SSH&#x20;Server','Checks&#x20;port&#x20;22&#x20;is&#x20;opened',10,9,0,0,300,22,'','','','',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (40,'Check&#x20;Telnet&#x20;server','Check&#x20;telnet&#x20;port',10,9,0,0,300,23,'','','','',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (41,'Check&#x20;SMTP&#x20;server','Check&#x20;if&#x20;SMTP&#x20;port&#x20;it&#039;s&#x20;open',10,9,0,0,300,25,'','','','',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (42,'Check&#x20;POP3&#x20;server','Check&#x20;POP3&#x20;port.',10,9,0,0,300,110,'','','','',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (43,'NIC&#x20;#7&#x20;outOctects','Get&#x20;outcoming&#x20;octects&#x20;from NIC #7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.7',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (44,'NIC&#x20;#7&#x20;inOctects','Get&#x20;incoming&#x20;octects&#x20;from&#x20;NIC&#x20;#7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.7',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (45,'NIC&#x20;#4&#x20;Status','Get&#x20;status&#x20;of&#x20;NIC&#x20;#4',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.4',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (46,'NIC&#x20;#5&#x20;Status','Get&#x20;status&#x20;of&#x20;NIC&#x20;#5',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.5',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (47,'NIC&#x20;#6&#x20;Status','Get&#x20;status&#x20;of&#x20;NIC&#x20;#6',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.6',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (48,'NIC&#x20;#7&#x20;Status','Get&#x20;status&#x20;of&#x20;NIC&#x20;#7',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.7',2,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (49,'OS&#x20;CPU&#x20;Load&#x20;(5&#x20;min)','CPU&#x20;load&#x20;on&#x20;a&#x20;5&#x20;min&#x20;average&#x20;interval.&#x20;UCD-SNMP&#x20;Mib&#x20;(Usually&#x20;for&#x20;all&#x20;Linux&#x20;and&#x20;some&#x20;UNIX)',13,15,0,0,180,0,'','','public','UCD-SNMP-MIB::laLoad.2',4,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (50,'System&#x20;Description','Get&#x20;system&#x20;description&#x20;(all&#x20;mibs).',1,17,0,0,9000,0,'','','public','SNMPv2-MIB::sysDescr.0',4,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (51,'OS&#x20;Raw&#x20;Interrupts','Get&#x20;system&#x20;raw&#x20;interrupts&#x20;from&#x20;SO',13,16,0,0,180,0,'','','public','UCD-SNMP-MIB::ssRawInterrupts.0',4,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (52,'OS&#x20;IO&#x20;Signals&#x20;sent','IO&#x20;Signals&#x20;sent&#x20;by&#x20;Kernel',13,16,0,0,180,0,'','','public','UCD-SNMP-MIB::ssIOSent.0',4,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (53,'System&#x20;Uptime','Sistem&#x20;uptime&#x20;in&#x20;timeticks',1,15,0,0,180,0,'','','public','HOST-RESOURCES-MIB::hrSystemUptime.0',4,2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`) VALUES (176,'Catalyst&#x20;Free&#x20;Mem','Taken&#x20;from&#x20;ftp://ftp.cisco.com/pub/mibs/oid/OLD-CISCO-MEMORY-MIB.oid',2,15,0,0,180,0,'','','public','1.3.6.1.4.1.9.2.1.8',4,2);

-- WMI components
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `plugin_user`, `max_timeout`) VALUES (200, 'CPU&#x20;load', 'CPU0&#x20;load&#x20;average', 14, 1, 100, 0, 300, 1, '', '', '', 'SELECT&#x20;LoadPercentage&#x20;from&#x20;Win32_Processor&#x20;WHERE&#x20;DeviceID&#x20;=&#x20;&quot;CPU0&quot;', 1, 6, 'Administrator', 10);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `plugin_user`, `max_timeout`) VALUES (201, 'Free&#x20;RAM', 'Available&#x20;RAM&#x20;memory&#x20;in&#x20;bytes', 14, 1, 0, 0, 300, 0, '', '', '', 'SELECT&#x20;AvailableBytes&#x20;from&#x20;Win32_PerfRawData_PerfOS_Memory', 1, 6, 'Administrator', 10);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `plugin_user`, `max_timeout`) VALUES (202, 'Windows&#x20;version', 'Operating&#x20;system&#x20;version', 14, 3, 0, 0, 300, 1, '', '', '', 'SELECT&#x20;Caption&#x20;FROM&#x20;Win32_OperatingSystem', 1, 6, 'Administrator', 10);


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
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (14,'WMI',12);


-- Network profile

INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (1,'Basic Network Monitoring','This includes basic SNMP, ICMP, and TCP checks.');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (2,'Basic Monitoring','Only ICMP check');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (3,'Basic DMZ Server monitoring','This group of network checks, checks for default services located on DMZ servers...');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (4,'Full SNMP Monitoring','');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (5,'Linux Server','Full Monitoring of a Linux server services.');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (6,'Basic WMI monitoring','Basic monitoring of a Windows host.');

INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (24,1);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (25,1);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (27,1);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (28,1);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (30,1);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (31,1);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (34,1);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (39,1);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (34,2);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (34,3);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (37,3);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (39,3);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (38,3);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (24,3);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (3,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (24,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (25,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (26,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (27,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (28,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (29,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (30,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (31,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (32,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (45,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (46,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (47,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (48,4);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (3,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (50,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (53,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (24,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (30,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (27,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (34,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (1,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (2,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (49,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (4,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (51,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (52,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (39,5);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (200,6);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (201,6);
INSERT INTO `tnetwork_profile_component` (`id_nc`, `id_np`) VALUES (202,6);


-- GIS Data
INSERT INTO `tgis_map` VALUES (1,'Sample',-3.708187,40.42056,0,16,'',-3.708187,40.42056,0,0,1);
INSERT INTO `tgis_map_connection` VALUES (1,'OpenStreetMap','OSM','{\"type\":\"OSM\",\"url\":\"http://tile.openstreetmap.org/${z}/${x}/${y}.png\"}',19,16,-3.708187,40.42056,0,-3.708187,40.42056,0,0);
INSERT INTO `tgis_map_has_tgis_map_connection` VALUES (1,1,'2010-03-01 09:46:48',1);
INSERT INTO `tgis_map_layer` VALUES (1,'Group All',1,0,1,0);

-- example alert template

INSERT INTO `talert_commands` VALUES (10,'Remote&#x20;agent&#x20;control','/usr/share/pandora_server/udp_client.pl _address_ 41122 &quot;_field1_&quot;','This command is used to send commands to the Pandora FMS agents with the UDP server enabled. The UDP server is used to order agents (Windows and UNIX) to &quot;refresh&quot; the agent execution: that means, to force the agent to execute and send data to s',0);

INSERT INTO `talert_actions` VALUES (1,'Mail&#x20;to&#x20;XXX',1,'yourmail@domain.es','[PANDORA] Alert from agent _agent_ on module _module_','',0,0);

INSERT INTO `talert_actions` VALUES (2,'Restart&#x20;agent',10,'REFRESH AGENT','','',0,0);

INSERT INTO `talert_actions` VALUES (3,'Pandora&#x20;FMS&#x20;Event',3,'','','',0,0);

INSERT INTO `talert_templates` VALUES (1,'Critical&#x20;condition','This is a generic alert template to fire on condition CRITICAL',1,'','','Hello, this is an automated email coming from Pandora FMS\r\n\r\nThis alert has been fired because a CRITICAL condition in one of your monitored items:\r\n\r\nAgent : _agent_\r\nModule: _module_\r\nModule description: _moduledescription_\r\nTimestamp _timestamp_\r\nCurrent value: _data_\r\n\r\nThanks for your time.\r\n\r\nBest regards\r\nPandora FMS\r\n','critical','',1,0.00,0.00,86400,1,0,'12:00:00','12:00:00',1,1,1,1,1,1,1,1,'[PANDORA] Alert RECOVERED for CRITICAL status on _agent_ / _module_','Hello, this is an automated email coming from Pandora FMS\r\n\r\nThis alert has been RECOVERED from a CRITICAL condition in one of your monitored items:\r\n\r\nAgent : _agent_\r\nModule: _module_\r\nModule description: _moduledescription_\r\nTimestamp _timestamp_\r\nCurrent value: _data_\r\n\r\nThanks for your time.\r\n\r\nBest regards\r\nPandora FMS\r\n',4,0);

INSERT INTO `talert_templates` VALUES (2,'Manual&#x20;alert','This is a template used to fire manual alerts, condition defined here never will be executed. Use this template to assign to your actions/commands used to do remote management (Agent restart, execute commands on server, etc).',NULL,'','','','max_min','',1,0.00,1.00,86400,1,0,'12:00:00','12:00:00',1,1,1,1,1,1,1,0,'','',1,0);

INSERT INTO `talert_templates` VALUES (3,'Warning&#x20;condition','This&#x20;is&#x20;a&#x20;generic&#x20;alert&#x20;template&#x20;to&#x20;fire&#x20;on&#x20;WARNING&#x20;condition.',1,'','','Hello,&#x20;this&#x20;is&#x20;an&#x20;automated&#x20;email&#x20;coming&#x20;from&#x20;Pandora&#x20;FMS&#x0d;&#x0a;&#x0d;&#x0a;This&#x20;alert&#x20;has&#x20;been&#x20;fired&#x20;because&#x20;a&#x20;WARNING&#x20;condition&#x20;in&#x20;one&#x20;of&#x20;your&#x20;monitored&#x20;items:&#x0d;&#x0a;&#x0d;&#x0a;Agent&#x20;:&#x20;_agent_&#x0d;&#x0a;Module:&#x20;_module_&#x0d;&#x0a;Module&#x20;description:&#x20;_moduledescription_&#x0d;&#x0a;Timestamp&#x20;_timestamp_&#x0d;&#x0a;Current&#x20;value:&#x20;_data_&#x0d;&#x0a;&#x0d;&#x0a;Thanks&#x20;for&#x20;your&#x20;time.&#x0d;&#x0a;&#x0d;&#x0a;Best&#x20;regards&#x0d;&#x0a;Pandora&#x20;FMS&#x0d;&#x0a;','warning','',1,0.00,0.00,86400,1,0,'12:00:00','12:00:00',1,1,1,1,1,1,1,1,'[PANDORA]&#x20;Alert&#x20;RECOVERED&#x20;for&#x20;WARNING&#x20;status&#x20;on&#x20;_agent_&#x20;/&#x20;_module_','Hello,&#x20;this&#x20;is&#x20;an&#x20;automated&#x20;email&#x20;coming&#x20;from&#x20;Pandora&#x20;FMS&#x0d;&#x0a;&#x0d;&#x0a;This&#x20;alert&#x20;has&#x20;been&#x20;RECOVERED&#x20;from&#x20;a&#x20;WARNING&#x20;condition&#x20;in&#x20;one&#x20;of&#x20;your&#x20;monitored&#x20;items:&#x0d;&#x0a;&#x0d;&#x0a;Agent&#x20;:&#x20;_agent_&#x0d;&#x0a;Module:&#x20;_module_&#x0d;&#x0a;Module&#x20;description:&#x20;_moduledescription_&#x0d;&#x0a;Timestamp&#x20;_timestamp_&#x0d;&#x0a;Current&#x20;value:&#x20;_data_&#x0d;&#x0a;&#x0d;&#x0a;Thanks&#x20;for&#x20;your&#x20;time.&#x0d;&#x0a;&#x0d;&#x0a;Best&#x20;regards&#x0d;&#x0a;Pandora&#x20;FMS&#x0d;&#x0a;',3,0);

-- treport_custom_sql Data
INSERT INTO `treport_custom_sql` (`id`, `name`, `sql`) VALUES (1, 'Monitoring&#x20;Report&#x20;Agent', 'select&#x20;direccion,&#x20;nombre,&#x20;comentarios,&#x20;&#40;select&#x20;nombre&#x20;from&#x20;tgrupo&#x20;where&#x20;tgrupo.id_grupo&#x20;=&#x20;tagente.id_grupo&#41;&#x20;as&#x20;`group`&#x20;from&#x20;tagente;');
INSERT INTO `treport_custom_sql` (`id`, `name`, `sql`) VALUES (2, 'Monitoring&#x20;Report&#x20;Modules', 'select&#x20;&#40;select&#x20;tagente.nombre&#x20;from&#x20;tagente&#x20;where&#x20;tagente.id_agente&#x20;=&#x20;tagente_modulo.id_agente&#41;&#x20;as&#x20;agent_name,&#x20;nombre&#x20;,&#x20;&#40;select&#x20;tmodule_group.name&#x20;from&#x20;tmodule_group&#x20;where&#x20;tmodule_group.id_mg&#x20;=&#x20;tagente_modulo.id_module_group&#41;&#x20;as&#x20;module_group,&#x20;module_interval&#x20;from&#x20;tagente_modulo&#x20;where&#x20;delete_pending&#x20;=&#x20;0&#x20;order&#x20;by&#x20;agent_name;');
INSERT INTO `treport_custom_sql` (`id`, `name`, `sql`) VALUES (3, 'Monitoring&#x20;Report&#x20;Alerts', 'select&#x20;t1.nombre&#x20;as&#x20;agent_name,&#x20;t2.nombre&#x20;as&#x20;module_name,&#x20;&#40;select&#x20;talert_templates.name&#x20;from&#x20;talert_templates&#x20;where&#x20;talert_templates.id&#x20;=&#x20;t3.id_alert_template&#41;&#x20;as&#x20;template,&#x20;&#40;select&#x20;group_concat&#40;t02.name&#41;&#x20;from&#x20;talert_template_module_actions&#x20;as&#x20;t01&#x20;inner&#x20;join&#x20;talert_actions&#x20;as&#x20;t02&#x20;on&#x20;t01.id_alert_action&#x20;=&#x20;t02.id&#x20;where&#x20;t01.id_alert_template_module&#x20;=&#x20;t3.id&#x20;group&#x20;by&#x20;t01.id_alert_template_module&#41;&#x20;as&#x20;actions&#x20;from&#x20;tagente&#x20;as&#x20;t1&#x20;inner&#x20;join&#x20;tagente_modulo&#x20;as&#x20;t2&#x20;on&#x20;t1.id_agente&#x20;=&#x20;t2.id_agente&#x20;inner&#x20;join&#x20;talert_template_modules&#x20;as&#x20;t3&#x20;on&#x20;t2.id_agente_modulo&#x20;=&#x20;t3.id_agent_module&#x20;order&#x20;by&#x20;agent_name,&#x20;module_name;');
INSERT INTO `treport_custom_sql` (`id`, `name`, `sql`) VALUES (4, 'Group&#x20;view', 'select&#x20;t1.nombre,&#x20;&#40;select&#x20;count&#40;t3.id_agente&#41;&#x20;from&#x20;tagente&#x20;as&#x20;t3&#x20;where&#x20;t1.id_grupo&#x20;=&#x20;t3.id_grupo&#41;&#x20;as&#x20;agents,&#x20;&#40;SELECT&#x20;COUNT&#40;t4.id_agente&#41;&#x20;FROM&#x20;tagente&#x20;as&#x20;t4&#x20;WHERE&#x20;t4.id_grupo&#x20;=&#x20;t1.id_grupo&#x20;AND&#x20;t4.disabled&#x20;=&#x20;0&#x20;AND&#x20;t4.ultimo_contacto&#x20;&lt;&#x20;NOW&#40;&#41;&#x20;-&#x20;&#40;intervalo&#x20;/&#x20;&#40;1/2&#41;&#41;&#41;&#x20;as&#x20;agent_unknown,&#x20;&#40;SELECT&#x20;COUNT&#40;tagente_estado.id_agente_estado&#41;&#x20;FROM&#x20;tagente_estado,&#x20;tagente,&#x20;tagente_modulo&#x20;WHERE&#x20;tagente.id_grupo&#x20;=&#x20;t1.id_grupo&#x20;AND&#x20;tagente.disabled&#x20;=&#x20;0&#x20;AND&#x20;tagente.id_agente&#x20;=&#x20;tagente_estado.id_agente&#x20;AND&#x20;tagente_estado.id_agente_modulo&#x20;=&#x20;tagente_modulo.id_agente_modulo&#x20;AND&#x20;tagente_modulo.disabled&#x20;=&#x20;0&#x20;AND&#x20;utimestamp&#x20;&gt;&#x20;0&#x20;AND&#x20;tagente_modulo.id_tipo_modulo&#x20;NOT&#x20;IN&#40;21,22,23,24,100&#41;&#x20;AND&#x20;&#40;UNIX_TIMESTAMP&#40;NOW&#40;&#41;&#41;&#x20;-&#x20;tagente_estado.utimestamp&#41;&#x20;&gt;=&#x20;&#40;tagente_estado.current_interval&#x20;/&#x20;&#40;1/2&#41;&#41;&#41;&#x20;as&#x20;monitor_unknow,&#x20;&#40;SELECT&#x20;COUNT&#40;tagente_estado.id_agente_estado&#41;&#x20;FROM&#x20;tagente_estado,&#x20;tagente,&#x20;tagente_modulo&#x20;WHERE&#x20;tagente.id_grupo&#x20;=&#x20;t1.id_grupo&#x20;AND&#x20;tagente.disabled&#x20;=&#x20;0&#x20;AND&#x20;tagente.id_agente&#x20;=&#x20;tagente_estado.id_agente&#x20;AND&#x20;tagente_estado.id_agente_modulo&#x20;=&#x20;tagente_modulo.id_agente_modulo&#x20;AND&#x20;tagente_modulo.disabled&#x20;=&#x20;0&#x20;AND&#x20;tagente_modulo.id_tipo_modulo&#x20;NOT&#x20;IN&#x20;&#40;21,22,23,24&#41;&#x20;AND&#x20;utimestamp&#x20;=&#x20;0&#41;&#x20;as&#x20;monitor_no_init,&#x20;&#40;SELECT&#x20;COUNT&#40;tagente_estado.id_agente_estado&#41;&#x20;FROM&#x20;tagente_estado,&#x20;tagente,&#x20;tagente_modulo&#x20;WHERE&#x20;tagente.id_grupo&#x20;=&#x20;t1.id_grupo&#x20;AND&#x20;tagente.disabled&#x20;=&#x20;0&#x20;AND&#x20;tagente_estado.id_agente&#x20;=&#x20;tagente.id_agente&#x20;AND&#x20;tagente_estado.id_agente_modulo&#x20;=&#x20;tagente_modulo.id_agente_modulo&#x20;AND&#x20;tagente_modulo.disabled&#x20;=&#x20;0&#x20;AND&#x20;estado&#x20;=&#x20;0&#x20;AND&#x20;&#40;&#40;UNIX_TIMESTAMP&#40;NOW&#40;&#41;&#41;&#x20;-&#x20;tagente_estado.utimestamp&#41;&#x20;&lt;&#x20;&#40;tagente_estado.current_interval&#x20;/&#x20;&#40;1/2&#41;&#41;&#x20;OR&#x20;&#40;tagente_modulo.id_tipo_modulo&#x20;IN&#40;21,22,23,24,100&#41;&#41;&#41;&#x20;AND&#x20;&#40;utimestamp&#x20;&gt;&#x20;0&#x20;OR&#x20;&#40;tagente_modulo.id_tipo_modulo&#x20;IN&#40;21,22,23,24&#41;&#41;&#41;&#41;&#x20;as&#x20;monitor_ok,&#x20;&#40;SELECT&#x20;COUNT&#40;tagente_estado.id_agente_estado&#41;&#x20;FROM&#x20;tagente_estado,&#x20;tagente,&#x20;tagente_modulo&#x20;WHERE&#x20;tagente.id_grupo&#x20;=&#x20;t1.id_grupo&#x20;AND&#x20;tagente.disabled&#x20;=&#x20;0&#x20;AND&#x20;tagente_estado.id_agente&#x20;=&#x20;tagente.id_agente&#x20;AND&#x20;tagente_estado.id_agente_modulo&#x20;=&#x20;tagente_modulo.id_agente_modulo&#x20;AND&#x20;tagente_modulo.disabled&#x20;=&#x20;0&#x20;AND&#x20;estado&#x20;=&#x20;1&#x20;AND&#x20;&#40;&#40;UNIX_TIMESTAMP&#40;NOW&#40;&#41;&#41;&#x20;-&#x20;tagente_estado.utimestamp&#41;&#x20;&lt;&#x20;&#40;tagente_estado.current_interval&#x20;/&#x20;&#40;1/2&#41;&#41;&#x20;OR&#x20;&#40;tagente_modulo.id_tipo_modulo&#x20;IN&#40;21,22,23,24,100&#41;&#41;&#41;&#x20;AND&#x20;utimestamp&#x20;&gt;&#x20;0&#41;&#x20;as&#x20;monitor_critical,&#x20;&#40;SELECT&#x20;COUNT&#40;talert_template_modules.id&#41;&#x20;FROM&#x20;talert_template_modules,&#x20;tagente_modulo,&#x20;tagente_estado,&#x20;tagente&#x20;WHERE&#x20;tagente.id_grupo&#x20;=&#x20;t1.id_grupo&#x20;AND&#x20;tagente_modulo.id_agente&#x20;=&#x20;tagente.id_agente&#x20;AND&#x20;tagente_estado.id_agente_modulo&#x20;=&#x20;tagente_modulo.id_agente_modulo&#x20;AND&#x20;tagente_modulo.disabled&#x20;=&#x20;0&#x20;AND&#x20;tagente.disabled&#x20;=&#x20;0&#x20;AND&#x20;talert_template_modules.id_agent_module&#x20;=&#x20;tagente_modulo.id_agente_modulo&#x20;AND&#x20;times_fired&#x20;&gt;&#x20;0&#41;&#x20;as&#x20;monitor_alert_fired&#x20;from&#x20;tgrupo&#x20;as&#x20;t1&#x20;where&#x20;0&#x20;&lt;&#x20;&#40;select&#x20;count&#40;t2.id_agente&#41;&#x20;from&#x20;tagente&#x20;as&#x20;t2&#x20;where&#x20;t1.id_grupo&#x20;=&#x20;t2.id_grupo&#41;');

INSERT INTO `trecon_script` VALUES (1,'SNMP&#x20;Recon&#x20;Script','This&#x20;script&#x20;is&#x20;used&#x20;to&#x20;automatically&#x20;detect&#x20;SNMP&#x20;Interfaces&#x20;on&#x20;devices,&#x20;used&#x20;as&#x20;Recon&#x20;Custom&#x20;Script&#x20;in&#x20;the&#x20;recon&#x20;task.&#x20;Parameters&#x20;used&#x20;are:&#x0d;&#x0a;&#x0d;&#x0a;*&#x20;custom_field1&#x20;=&#x20;network.&#x20;i.e.:&#x20;192.168.100.0/24&#x0d;&#x0a;*&#x20;custom_field2&#x20;=&#x20;snmp_community.&#x20;&#x0d;&#x0a;*&#x20;custom_field3&#x20;=&#x20;optative&#x20;parameter&#x20;to&#x20;force&#x20;process&#x20;downed&#x20;interfaces&#x20;&#40;use:&#x20;&#039;-a&#039;&#41;.&#x20;Only&#x20;up&#x20;interfaces&#x20;are&#x20;processed&#x20;by&#x20;default&#x20;&#x0d;&#x0a;&#x0d;&#x0a;See&#x20;documentation&#x20;for&#x20;more&#x20;information.','/usr/share/pandora_server/util/recon_scripts/snmpdevices.pl');

INSERT INTO `trecon_script` VALUES
(2,'IMPI Recon', 'Specific Pandora FMS Intel DCM Discovery (c) Artica ST 2011 <info@artica.es> Usage: ./ipmi-recon.pl <task_id> <group_id> <create_incident_flag> <custom_field1> <custom_field2> <custom_field3> * custom_field1 = network. i.e.: 192.168.100.0/24 * custom_field2 = username * custom_fiedl3 = password ', '/usr/share/pandora_server/util/recon_scripts/ipmi-recon.pl');

INSERT INTO `tplugin` (`id`, `name`, `description`, `max_timeout`, `execute`, `net_dst_opt`, `net_port_opt`, `user_opt`, `pass_opt`, `plugin_type`) VALUES (1,'IPMI&#x20;Plugin','Plugin&#x20;to&#x20;get&#x20;IPMI&#x20;monitors&#x20;from&#x20;a&#x20;IPMI&#x20;Device.',0,'/usr/share/pandora_server/util/plugin/ipmi-plugin.pl','-h','','-u','-p',0),(2,'DNS&#x20;Plugin','This&#x20;plugin&#x20;is&#x20;used&#x20;to&#x20;check&#x20;if&#x20;a&#x20;specific&#x20;domain&#x20;return&#x20;a&#x20;specific&#x20;IP&#x20;address,&#x20;and&#x20;to&#x20;check&#x20;how&#x20;time&#x20;&#40;milisecs&#41;&#x20;takes&#x20;the&#x20;DNS&#x20;to&#x20;answer.&#x20;Use&#x20;IP&#x20;address&#x20;parameter&#x20;to&#x20;specify&#x20;the&#x20;IP&#x20;of&#x20;your&#x20;domain.&#x20;Use&#x20;these&#x20;custom&#x20;parameters&#x20;for&#x20;the&#x20;other&#x20;parameters:&#x0d;&#x0a;&#x0d;&#x0a;-d&#x20;domain&#x20;to&#x20;check&#x20;&#40;for&#x20;example&#x20;pandorafms.com&#41;&#x0d;&#x0a;-s&#x20;DNS&#x20;Server&#x20;to&#x20;check&#x20;&#x20;&#40;for&#x20;example&#x20;8.8.8.8&#41;&#x0d;&#x0a;&#x0d;&#x0a;Optional&#x20;parameters:&#x0d;&#x0a;&#x0d;&#x0a;-t&#x20;Do&#x20;a&#x20;DNS&#x20;time&#x20;response&#x20;check&#x20;instead&#x20;DNS&#x20;resolve&#x20;test&#x0d;&#x0a;&#x0d;&#x0a;',15,'/usr/share/pandora_server/util/plugin/dns_plugin.sh','-i','','','',0),(3,'UDP&#x20;port&#x20;check','Check&#x20;a&#x20;remote&#x20;UDP&#x20;port&#x20;&#40;by&#x20;using&#x20;NMAP&#41;.&#x20;Use&#x20;IP&#x20;address&#x20;and&#x20;Port&#x20;options.',5,'/usr/share/pandora_server/util/plugin/udp_nmap_plugin.sh','-t','-p','','',0),(4,'SMTP&#x20;Check','This&#x20;plugin&#x20;is&#x20;used&#x20;to&#x20;send&#x20;a&#x20;mail&#x20;to&#x20;a&#x20;SMTP&#x20;server&#x20;and&#x20;check&#x20;if&#x20;works.&#x20;Parameters&#x20;in&#x20;the&#x20;plugin&#x20;&#x0d;&#x0a;IP&#x20;Addres&#x20;-&#x20;SMTP&#x20;Server&#x20;IP&#x20;address&#x0d;&#x0a;User&#x20;-&#x20;AUTH&#x20;login&#x20;&#x20;&#x0d;&#x0a;Pass&#x20;-&#x20;AUTH&#x20;password&#x0d;&#x0a;Port&#x20;-&#x20;SMTP&#x20;port&#x20;&#40;optional&#41;&#x0d;&#x0a;&#x0d;&#x0a;Optional&#x20;parameters&#x20;&#x0d;&#x0a;&#x0d;&#x0a;&#x20;-d&#x20;Destination&#x20;email&#x0d;&#x0a;&#x20;-f&#x20;Email&#x20;of&#x20;the&#x20;sender&#x0d;&#x0a;&#x20;-a&#x20;Autentication&#x20;system,&#x20;could&#x20;be&#x20;LOGIN,&#x20;PLAIN,&#x20;CRAM-MD5&#x20;or&#x20;DIGEST-MD&#x0d;&#x0a;&#x0d;&#x0a;&#x0d;&#x0a;',10,'/usr/share/pandora_server/util/plugin/SMTP_check.pl','-h','-o','-u','-p',0);


