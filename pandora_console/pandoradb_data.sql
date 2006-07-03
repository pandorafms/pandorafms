-- MySQL dump 9.11
--
-- Host: localhost    Database: pandora
-- ------------------------------------------------------
-- Server version	4.0.24_Debian-10-log

--
-- Dumping data for table `talerta`
--

INSERT INTO `talerta` VALUES (1,'eMail','echo _field3_ | mail -s _field2_ _field1_','Send email from Pandora Server. mail is a default command on all &quot;standard&quot; Unix systems, using:\r\n_field1_ as destination email address, and\r\n_field2_ as subject for message. \r\n_field3_ as text of message.');
INSERT INTO `talerta` VALUES (2,'LogFile','echo _timestamp_ pandora _field1_ _field2_  &gt;&gt; /var/log/pandora_alert.log','This is a default alert to write alerts in a standard ASCII  plaintext log file in /var/log/pandora_alert.log\r\n');
INSERT INTO `talerta` VALUES (3,'Internal Audit','','This alert save alert in Pandora internal audit system. Fields are static and only _field1_ is used.');
INSERT INTO `talerta` VALUES (4,'SNMP Trap','/usr/bin/snmptrap -v 1 -c trap_public 192.168.0.4 1.1.1.1.1.1.1.1 _agent_ _field1_','Send a SNMPTRAP to 192.168.0.4. Please review config and adapt to your needs, this is only a sample, not functional itself.');
INSERT INTO `talerta` VALUES (5,'SMS Text','echo _field2_ | mail -s PANDORA_field1_ slerena@vodafone.es','Send SMS via e-mail gateway. Use _field1_ for a short SMS text (35 chars) and _field2_ for text message (full SMS)');
INSERT INTO `talerta` VALUES (6,'Syslog','/usr/bin/logger -pri daemon.alert Pandora Alert _agent_ _field1_ _field2_','Uses _field1_ and _field2_ to generate a Syslog alert in facility &quot;daemon&quot; with &quot;alert&quot; level.');

--
-- Dumping data for table `tconfig`
--

INSERT INTO `tconfig` VALUES (1,'language_code','en');
INSERT INTO `tconfig` VALUES (3,'block_size','20');
INSERT INTO `tconfig` VALUES (4,'days_purge','60');
INSERT INTO `tconfig` VALUES (5,'days_compact','15');
INSERT INTO `tconfig` VALUES (6,'graph_res','2');
INSERT INTO `tconfig` VALUES (7,'step_compact','1');
INSERT INTO `tconfig` VALUES (8,'db_scheme_version','1.1');
INSERT INTO `tconfig` VALUES (9,'db_scheme_build','PD60126');
INSERT INTO `tconfig` VALUES (10,'graph_order','1');
INSERT INTO `tconfig` VALUES (11,'truetype','0');
INSERT INTO `tconfig` VALUES (12,'bgimage','background2.jpg');
--
-- Dumping data for table `tconfig_os`
--

INSERT INTO `tconfig_os` VALUES (1,'Linux','Linux: All versions','so_linux.gif');
INSERT INTO `tconfig_os` VALUES (2,'Solaris','Sun Solaris','so_solaris.gif');
INSERT INTO `tconfig_os` VALUES (3,'AIX','IBM AIX','so_aix.gif');
INSERT INTO `tconfig_os` VALUES (4,'BSD','OpenBSD, FreeBSD and Others','so_bsd.gif');
INSERT INTO `tconfig_os` VALUES (5,'HP-UX','HP-UX Unix OS','so_hpux.gif');
INSERT INTO `tconfig_os` VALUES (6,'BeOS','BeOS','so_beos.gif');
INSERT INTO `tconfig_os` VALUES (7,'Cisco','CISCO IOS','so_cisco.gif');
INSERT INTO `tconfig_os` VALUES (8,'MacOS','MAC OS','so_mac.gif');
INSERT INTO `tconfig_os` VALUES (9,'Windows','Microsoft Windows OS','so_win.gif');
INSERT INTO `tconfig_os` VALUES (10,'Other','Other SO','so_other.gif');
INSERT INTO `tconfig_os` VALUES (11,'Network','Pandora Network Agent','network.gif');
--
-- Dumping data for table `tgrupo`
--

INSERT INTO `tgrupo` VALUES (1,'All','');
INSERT INTO `tgrupo` VALUES (2,'Servers','servers');
INSERT INTO `tgrupo` VALUES (3,'IDS','ids');
INSERT INTO `tgrupo` VALUES (4,'Firewalls','firewall');
INSERT INTO `tgrupo` VALUES (8,'Databases','db');
INSERT INTO `tgrupo` VALUES (9,'Comms','comms');
INSERT INTO `tgrupo` VALUES (10,'Others','others');
INSERT INTO `tgrupo` VALUES (11,'Workstations','workstation');
INSERT INTO `tgrupo` VALUES (12,'Applications','apps');

--
-- Dumping data for table `tlink`
--

INSERT INTO `tlink` VALUES (0000000001,'GeekTools','www.geektools.com');
INSERT INTO `tlink` VALUES (0000000002,'CentralOPS','http://www.centralops.net/');
INSERT INTO `tlink` VALUES (0000000003,'Pandora Project','http://pandora.sourceforge.net');
INSERT INTO `tlink` VALUES (0000000004,'Babel Project','http://babel.sourceforge.net');
INSERT INTO `tlink` VALUES (0000000005,'Google','http://www.google.com');

--
-- Dumping data for table `torigen`
--

INSERT INTO `torigen` VALUES ('Operating System event');
INSERT INTO `torigen` VALUES ('IDS events');
INSERT INTO `torigen` VALUES ('Firewall records');
INSERT INTO `torigen` VALUES ('Database event');
INSERT INTO `torigen` VALUES ('Application data');
INSERT INTO `torigen` VALUES ('Logfiles');
INSERT INTO `torigen` VALUES ('Other data source');
INSERT INTO `torigen` VALUES ('Pandora event');
INSERT INTO `torigen` VALUES ('User report');
INSERT INTO `torigen` VALUES ('Unknown source');

--
-- Dumping data for table `tperfil`
--

INSERT INTO `tperfil` VALUES (1,'Operator (Read)',0,1,0,1,0,0,0,0,0,0);
INSERT INTO `tperfil` VALUES (2,'Operator (Write)',1,1,0,1,0,0,0,0,0,0);
INSERT INTO `tperfil` VALUES (3,'Chief Operator',1,1,1,1,0,0,0,0,0,0);
INSERT INTO `tperfil` VALUES (4,'Group coordinator',1,1,1,1,1,1,1,0,0,0);
INSERT INTO `tperfil` VALUES (5,'Pandora Administrator',1,1,1,1,1,1,1,1,1,1);

--
-- Dumping data for table `ttipo_modulo`
--

INSERT INTO `ttipo_modulo` VALUES (1,'generic_data',0,'Generic module to adquire numeric data','mod_data.gif');
INSERT INTO `ttipo_modulo` VALUES (2,'generic_proc',1,'Generic module to adquire boolean data','mod_proc.gif');
INSERT INTO `ttipo_modulo` VALUES (3,'generic_data_string',0,'Generic module to adquire alphanumeric data','mod_string.gif');
INSERT INTO `ttipo_modulo` VALUES (4,'generic_data_inc',0,'Generic module to adquire numeric incremental data','mod_data_inc.gif');
INSERT INTO `ttipo_modulo` VALUES (6,'remote_icmp_proc',3,'Remote ICMP network agent, boolean data','mod_icmp_proc.gif');
INSERT INTO `ttipo_modulo` VALUES (7,'remote_icmp',2,'Remote ICMP network agent (latency)','mod_icmp_data.gif');
INSERT INTO `ttipo_modulo` VALUES (8,'remote_tcp',2,'Remote TCP network agent, numeric data','mod_tcp_data.gif');
INSERT INTO `ttipo_modulo` VALUES (9,'remote_tcp_proc',3,'Remote TCP network agent, boolean data','mod_tcp_proc.gif');
INSERT INTO `ttipo_modulo` VALUES (10,'remote_tcp_string',2,'Remote TCP network agent, alphanumeric data','mod_tcp_string.gif');
INSERT INTO `ttipo_modulo` VALUES (11,'remote_tcp_inc',2,'Remote TCP network agent, incremental data','mod_tcp_inc.gif');
INSERT INTO `ttipo_modulo` VALUES (12,'remote_udp_proc',3,'Remote UDP network agent, boolean data','mod_udp_proc.gif');
INSERT INTO `ttipo_modulo` VALUES (15,'remote_snmp',2,'Remote SNMP network agent, numeric data','mod_snmp_data.gif');
INSERT INTO `ttipo_modulo` VALUES (16,'remote_snmp_inc',2,'Remote SNMP network agent, incremental data','mod_snmp_inc.gif');
INSERT INTO `ttipo_modulo` VALUES (17,'remote_snmp_string',2,'Remote SNMP network agent, alphanumeric data','mod_snmp_string.gif');
INSERT INTO `ttipo_modulo` VALUES (18,'remote_snmp_proc',1,'Remote SNMP network agent, boolean data','mod_snmp_proc.gif');

--
-- Dumping data for table `tusuario`
--

INSERT INTO `tusuario` VALUES ('admin','Default Admin','1da7ee7d45b96d0e1f45ee4ee23da560','Admin Pandora','2005-12-18 20:13:10','admin_pandora@nowhere.net','555-555-555',1);
INSERT INTO `tusuario` VALUES ('demo','Demo user','fe01ce2a7fbac8fafaed7c982a04e229','Please do not change anything in this user, so other users can connect with it.\r\n\r\nThanks.','2005-12-20 17:46:48','demo@nowhere.net','+4555435435',0);

--
-- Dumping data for table `tusuario_perfil`
--

INSERT INTO `tusuario_perfil` VALUES (1,'demo',1,1,'admin');
INSERT INTO `tusuario_perfil` VALUES (2,'admin',5,1,'admin');


--
-- Dumping data for table `tlanguage`
--


INSERT INTO `tlanguage` VALUES ('bb','Bable');
INSERT INTO `tlanguage` VALUES ('en','English');
INSERT INTO `tlanguage` VALUES ('es_es','Espa&ntilde;ol');
INSERT INTO `tlanguage` VALUES ('es_la','Espa&ntilde;ol-Latinoam&eacute;rica');
INSERT INTO `tlanguage` VALUES ('eu','Euskera');
INSERT INTO `tlanguage` VALUES ('pt_br','Portuguese-Brazil');
INSERT INTO `tlanguage` VALUES ('fr','Fran&ccedil;ais');
INSERT INTO `tlanguage` VALUES ('ca','Catal&agrave;');

--
-- Dumping data for table `tmodule_group`
--

INSERT INTO `tmodule_group` VALUES ('1','General');
INSERT INTO `tmodule_group` VALUES ('2','Networking');
INSERT INTO `tmodule_group` VALUES ('3','Application');
INSERT INTO `tmodule_group` VALUES ('4','System');
INSERT INTO `tmodule_group` VALUES ('5','Miscellaneous');
