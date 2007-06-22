--
-- Dumping data for table `talerta`
--


/*!40000 ALTER TABLE `talerta` DISABLE KEYS */;
LOCK TABLES `talerta` WRITE;
INSERT INTO `talerta` VALUES (1,'eMail','echo _field3_ | sendmail -s _field2_ _field1_','Send email from Pandora Server. mail is a default command on all &quot;standard&quot; Unix systems, using:\r\n_field1_ as destination email address, and\r\n_field2_ as subject for message. \r\n_field3_ as text of message.'),(2,'LogFile','echo _timestamp_ pandora _field1_ _field2_  &gt;&gt; /var/log/pandora_alert.log','This is a default alert to write alerts in a standard ASCII  plaintext log file in /var/log/pandora_alert.log\r\n'),(3,'Internal Audit','','This alert save alert in Pandora interal audit system. Fields are static and only _field1_ is used.'),(4,'SNMP Trap','/usr/bin/snmptrap -v 1 -c trap_public 192.168.0.4 1.1.1.1.1.1.1.1 _agent_ _field1_','Send a SNMPTRAP to 192.168.0.4. Please review config and adapt to your needs, this is only a sample, not functional itself.'),(5,'SMS Text','echo _field2_ | mail -s PANDORA_field1_ slerena@vodafone.es','Send SMS via e-mail gateway. Use field1 for a short SMS text (35 chars) and field 2 for text message (full SMS)'),(6,'Syslog','/usr/bin/logger -pri daemon.alert Pandora Alert _agent_ _field1_ _field2_','Uses field1 and field2 to generate Syslog alert in facility &quot;daemon&quot; with &quot;alert&quot; level.'),(7,'Sound Alert','/usr/bin/play /usr/share/sounds/alarm.wav','');
UNLOCK TABLES;
/*!40000 ALTER TABLE `talerta` ENABLE KEYS */;

--
-- Dumping data for table `tconfig`
--


/*!40000 ALTER TABLE `tconfig` DISABLE KEYS */;
LOCK TABLES `tconfig` WRITE;
INSERT INTO `tconfig` VALUES (1,'language_code','en'),(3,'block_size','20'),(4,'days_purge','60'),(5,'days_compact','15'),(6,'graph_res','5'),(7,'step_compact','1'),(8,'db_scheme_version','1.3'),(9,'db_scheme_build','PD60525'),(12,'bgimage','background4.jpg'),(13,'show_unknown','0'),(14,'show_lastalerts','1'),(15,'style','pandora');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tconfig` ENABLE KEYS */;

--
-- Dumping data for table `tconfig_os`
--


/*!40000 ALTER TABLE `tconfig_os` DISABLE KEYS */;
LOCK TABLES `tconfig_os` WRITE;
INSERT INTO `tconfig_os` VALUES (1,'GNU/Linux','Linux: All versions','so_linux.gif'),(2,'Solaris','Sun Solaris','so_solaris.gif'),(3,'AIX','IBM AIX','so_aix.gif'),(4,'BSD','OpenBSD, FreeBSD and Others','so_bsd.gif'),(5,'HP-UX','HP-UX Unix OS','so_hpux.gif'),(6,'BeOS','BeOS','so_beos.gif'),(7,'Cisco','CISCO IOS','so_cisco.gif'),(8,'MacOS','MAC OS','so_mac.gif'),(9,'Windows','Microsoft Windows OS','so_win.gif'),(10,'Other','Other SO','so_other.gif'),(11,'Network','Pandora Network Agent','network.gif');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tconfig_os` ENABLE KEYS */;

--
-- Dumping data for table `tgrupo`
--

LOCK TABLES `tgrupo` WRITE;
INSERT INTO `tgrupo` VALUES (1,'All','world',0,0);
INSERT INTO `tgrupo` VALUES (2,'Servers','server_database',0,0);
INSERT INTO `tgrupo` VALUES (3,'IDS','eye',0,0);
INSERT INTO `tgrupo` VALUES (4,'Firewalls','firewall',0,0);
INSERT INTO `tgrupo` VALUES (8,'Databases','database_gear',0,0);
INSERT INTO `tgrupo` VALUES (9,'Comms','transmit',0,0);
INSERT INTO `tgrupo` VALUES (10,'Others','house',0,0);
INSERT INTO `tgrupo` VALUES (11,'Workstations','computer',0,0);
INSERT INTO `tgrupo` VALUES (12,'Applications','applications',0,0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `tgrupo` ENABLE KEYS */;

--
-- Dumping data for table `tlanguage`
--


/*!40000 ALTER TABLE `tlanguage` DISABLE KEYS */;
LOCK TABLES `tlanguage` WRITE;
INSERT INTO `tlanguage` VALUES ('bb','Bable'),('ca','Catal&agrave;'),('de','Deutch'),('en','English'),('es_es','Espa&ntilde;ol'),('es_gl','Gallego'),('es_la','Espa&ntilde;ol-Latinoam&eacute;rica'),('eu','Euskera'),('fr','Fran&ccedil;ais'),('pt_br','Portuguese-Brazil');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tlanguage` ENABLE KEYS */;

--
-- Dumping data for table `tlink`
--


/*!40000 ALTER TABLE `tlink` DISABLE KEYS */;
LOCK TABLES `tlink` WRITE;
INSERT INTO `tlink` VALUES (0000000001,'GeekTools','www.geektools.com'),(0000000002,'CentralOPS','http://www.centralops.net/'),(0000000003,'Pandora Project','http://pandora.sourceforge.net'),(0000000004,'Babel Project','http://babel.sourceforge.net'),(0000000005,'Google','http://www.google.com');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tlink` ENABLE KEYS */;

--
-- Dumping data for table `tmodule_group`
--


/*!40000 ALTER TABLE `tmodule_group` DISABLE KEYS */;
LOCK TABLES `tmodule_group` WRITE;
INSERT INTO `tmodule_group` VALUES (1,'General'),(2,'Networking'),(3,'Application'),(4,'System'),(5,'Miscellaneous');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tmodule_group` ENABLE KEYS */;

--
-- Dumping data for table `tnetwork_component`
--

INSERT INTO `tnetwork_component` VALUES (1,'OS Total process','Total process in Operating System (UNIX MIB)',5,8,0,0,0,0,'','NULL','NULL','',0),(2,'OS CPU Load','CPU Load in Operating System (UNIX MIB)',5,9,0,0,0,0,'','NULL','NULL','',0),(3,'Sysname','Get name of system using SNMP standard MIB',1,17,0,0,900,0,'','','public','.1.3.6.1.2.1.1.1.0',1),(4,'OS Users','Active users in Operating System (UNIX MIB)',5,6,0,0,0,0,'','NULL','NULL','',0),(5,'CiscoAP Wifi traffic','Cisco AP AP120',9,6,0,0,0,0,'','NULL','NULL','',0),(6,'CiscoAP Wifi errors','Get errors con WiFi for Cisco AP AP120',9,16,0,0,300,0,'','','public','',2),(7,'CiscoAP RAM','Get RAM available on device',9,15,0,0,300,0,'','','public','',4),(8,'CiscoAP Ethernet OUT','Cisco AP AP120',9,6,0,0,0,0,'','NULL','NULL','',0),(9,'CiscoAP Ethernet IN','Cisco AP AP120',9,6,0,0,0,0,'','NULL','NULL','',0),(10,'CiscoAP CPU Usage','Cisco AP AP120',9,6,0,0,0,0,'','NULL','NULL','',0),(11,'Cisco Catalyst CPU Usage','Cisco Catalyst 3750',8,6,0,0,0,0,'','NULL','NULL','',0),(12,'FlashFree ','Cisco Catalyst 3750',8,6,0,0,0,0,'','NULL','NULL','',0),(13,'RAM_Usage','Cisco Catalyst 3750',8,6,0,0,0,0,'','NULL','NULL','',0),(16,'CPU Usage','',7,6,0,0,0,0,'','NULL','NULL','',0),(17,'Memory available','',7,6,0,0,0,0,'','NULL','NULL','',0),(18,'Configuration changes','',7,6,0,0,0,0,'','NULL','NULL','',0),(19,'Power #1','',6,18,0,0,180,0,'','','public',' .1.3.6.1.4.1.2334.2.1.5.8.0',4),(20,'Power #2','',6,18,0,0,180,0,'','','public',' .1.3.6.1.4.1.2334.2.1.5.10.0',4),(21,'User concurrence','',6,6,0,0,0,0,'','NULL','NULL','',0),(22,'HSRP Status','Get status of HSRP',2,18,0,0,180,0,'','','public','1.3.6.1.4.1.9.9.106.1.2.1.1.15.12.106',2),(23,'Num. of classes','',6,6,0,0,0,0,'','NULL','NULL','',0),(24,'NIC #1 status','Status of NIC#1',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.1',2),(25,'NIC #2 status','Status of NIC #2',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.2',2),(26,'NIC #3 status','Status of NIC #3',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.3',2),(27,'NIC #1 outOctects','Output throughtput on Interface #1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.1',2),(28,'NIC #2 outOctects','Output troughtput on interface #2',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.2',1),(29,'NIC #3 outOctects','Output troughtput on Interface #3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.3',2),(30,'NIC #1 inOctects','Input troughtput on Interface #1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.1',2),(31,'NIC #2 inOctects','Input throughtput for interface #2',10,16,0,0,180,0,'','NULL','public','.1.3.6.1.2.1.2.2.1.10.2',2),(32,'NIC #3 inOctects','Input throught on interface #3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.3',2),(34,'Host Alive','Check if host is alive using ICMP ping check.',10,6,0,0,120,0,'','','','',2),(36,'Host Latency','Get host network latency in miliseconds, using ICMP.',10,7,0,0,180,0,'','','','',2),(37,'Check HTTP Server','Test APACHE2 HTTP service remotely (Protocol response, not only openport)',10,9,0,0,300,80,'GET / HTTP/1.0^M^M','HTTP/1.1 200 OK','','',3),(38,'Check FTP Server','Check FTP protocol, not only check port.',10,9,0,0,300,21,'QUIT','221','','',3),(39,'Check SSH Server','Checks port 22 is opened',10,9,0,0,300,22,'','','','',2),(40,'Check Telnet server','Check telnet port',10,9,0,0,300,23,'','','','',2),(41,'Check SMTP server','Check if SMTP port it&#039;s open',10,9,0,0,300,25,'','','','',2),(42,'Check POP3 server','Check POP3 port.',10,9,0,0,300,110,'','','','',2),(43,'NIC #7 outOctects','Get outcoming octects from NIC #7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.7',2),(44,'NIC #7 inOctects','Get incoming octects from NIC #7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.7',2),(45,'NIC #4 Status','Get status of NIC #4',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.4',2),(46,'NIC #5 Status','Get status of NIC #5',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.5',2),(47,'NIC #6 Status','Get status of NIC #6',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.6',2),(48,'NIC #7 Status','Get status of NIC #7',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.7',2);

--
-- Dumping data for table `tnetwork_component_group`
--

INSERT INTO `tnetwork_component_group` VALUES (1,'General group',0),(2,'Cisco MIBs',10),(3,'Nortel MIBS',10),(4,'3COM MIBs',10),(5,'UNIX MIBs',12),(6,'Packetshaper MIBs',10),(7,'Nortel BPS 2000 MIBs',3),(8,'Cisco Catalyst3750 MIBs',2),(9,'Cisco AP120+',2),(10,'Network Management',0),(11,'Microsoft Windows MIB',12),(12,'Operating Systems',0);

--
-- Dumping data for table `torigen`
--


INSERT INTO `torigen` VALUES ('Operating System event'),('IDS events'),('Firewall records'),('Database event'),('Application data'),('Logfiles'),('Other data source'),('Pandora FMS Event'),('User report'),('Unknown source');

--
-- Dumping data for table `ttipo_modulo`
--

INSERT INTO `ttipo_modulo` VALUES (1,'generic_data',0,'Generic module to adquire numeric data','mod_data.gif'),(2,'generic_proc',1,'Generic module to adquire boolean data','mod_proc.gif'),(3,'generic_data_string',0,'Generic module to adquire alphanumeric data','mod_string.gif'),(4,'generic_data_inc',0,'Generic module to adquire numeric incremental data','mod_data_inc.gif'),(6,'remote_icmp_proc',3,'Remote ICMP network agent, boolean data','mod_icmp_proc.gif'),(7,'remote_icmp',2,'Remote ICMP network agent (latency)','mod_icmp_data.gif'),(8,'remote_tcp',2,'Remote TCP network agent, numeric data','mod_tcp_data.gif'),(9,'remote_tcp_proc',3,'Remote TCP network agent, boolean data','mod_tcp_proc.gif'),(10,'remote_tcp_string',2,'Remote TCP network agent, alphanumeric data','mod_tcp_string.gif'),(11,'remote_tcp_inc',2,'Remote TCP network agent, incremental data','mod_tcp_inc.gif'),(15,'remote_snmp',2,'Remote SNMP network agent, numeric data','mod_snmp_data.gif'),(16,'remote_snmp_inc',2,'Remote SNMP network agent, incremental data','mod_snmp_inc.gif'),(17,'remote_snmp_string',2,'Remote SNMP network agent, alphanumeric data','mod_snmp_string.gif'),(18,'remote_snmp_proc',1,'Remote SNMP network agent, boolean data','mod_snmp_proc.gif');

--
-- Dumping data for table `tusuario`
--

INSERT INTO `tusuario` VALUES ('admin','Default Admin','1da7ee7d45b96d0e1f45ee4ee23da560','Admin Pandora','2007-03-27 18:59:39','admin_pandora@nowhere.net','555-555-555',1),('demo','Demo user','fe01ce2a7fbac8fafaed7c982a04e229','Please don\\&#039;t change anything in this user, so other users can connect with it.\r\n\r\nThanks.','2007-03-20 13:00:05','demo@nowhere.net','+4555435435',0);

--
-- Dumping data for table `tusuario_perfil`
--

INSERT INTO `tusuario_perfil` VALUES (1,'demo',1,1,'admin'),(2,'admin',5,1,'admin');

--
-- Dumping data for table `tperfil`
--

INSERT INTO `tperfil` VALUES (1,'Operator (Read)',0,1,0,1,0,0,0,0,0,0),(2,'Operator (Write)',1,1,0,1,0,0,0,0,0,0),(3,'Chief Operator',1,1,1,1,0,0,0,0,0,0),(4,'Group coordinator',1,1,1,1,1,1,1,0,0,0),(5,'Pandora Administrator',1,1,1,1,1,1,1,1,1,1);

INSERT INTO `tnews` VALUES (1,'admin','Welcome to Pandora FMS 1.3 !','This is our new console, a lot of new features has been added from last version. Please read documentation about it and be free to test any option.\r\n\r\nPandora FMS team.','2007-06-22 13:03:20');
