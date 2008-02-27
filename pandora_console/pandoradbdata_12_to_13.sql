
UPDATE tconfig SET value = '1.3' WHERE token = 'db_scheme_version';
UPDATE tconfig SET value = '1.3' WHERE token = 'db_scheme_build';
INSERT INTO tconfig (token, value) VALUES ('show_unknown','0');
INSERT INTO tconfig (token, value) VALUES ('show_lastalerts','1');
INSERT INTO tconfig (token, value) VALUES ('style','pandora');


UPDATE tconfig_os SET icon_name = 'so_aix.png' WHERE icon_name = 'so_aix.gif';
UPDATE tconfig_os SET icon_name = 'so_linux.png' WHERE icon_name = 'so_linux.gif';
UPDATE tconfig_os SET icon_name = 'so_solaris.png' WHERE icon_name = 'so_solaris.gif';
UPDATE tconfig_os SET icon_name = 'so_hpux.png' WHERE icon_name = 'so_hpux.gif';
UPDATE tconfig_os SET icon_name = 'so_beos.png' WHERE icon_name = 'so_beos.gif';
UPDATE tconfig_os SET icon_name = 'so_cisco.png' WHERE icon_name = 'so_cisco.gif';
UPDATE tconfig_os SET icon_name = 'so_mac.png' WHERE icon_name = 'so_mac.gif';
UPDATE tconfig_os SET icon_name = 'so_win.png' WHERE icon_name = 'so_win.gif';
UPDATE tconfig_os SET icon_name = 'so_other.png' WHERE icon_name = 'so_other.gif';
UPDATE tconfig_os SET icon_name = 'network.png' WHERE icon_name = 'network.gif';

UPDATE tgrupo SET icon = 'world', parent = 0, disabled = 0 WHERE id_grupo = 1;
UPDATE tgrupo SET icon = 'server_database', parent = 0, disabled = 0 WHERE id_grupo = 2;
UPDATE tgrupo SET icon = 'eye', parent = 0, disabled = 0 WHERE id_grupo = 3;
UPDATE tgrupo SET icon = 'firewall', parent = 0, disabled = 0 WHERE id_grupo = 4;
UPDATE tgrupo SET icon = 'database_gear', parent = 0, disabled = 0 WHERE id_grupo = 8;
UPDATE tgrupo SET icon = 'transmit', parent = 0, disabled = 0 WHERE id_grupo = 9;
UPDATE tgrupo SET icon = 'house', parent = 0, disabled = 0 WHERE id_grupo = 10;
UPDATE tgrupo SET icon = 'computer', parent = 0, disabled = 0 WHERE id_grupo = 11;
UPDATE tgrupo SET icon = 'applications', parent = 0, disabled = 0 WHERE id_grupo = 12;

UPDATE ttipo_modulo SET icon = 'mod_data.png' WHERE icon = 'mod_data.gif';
UPDATE ttipo_modulo SET icon = 'mod_proc.png' WHERE icon = 'mod_proc.gif';
UPDATE ttipo_modulo SET icon = 'mod_string.png' WHERE icon = 'mod_string.gif';
UPDATE ttipo_modulo SET icon = 'mod_data_inc.png' WHERE icon = 'mod_data_inc.gif';
UPDATE ttipo_modulo SET icon = 'mod_icmp_proc.png' WHERE icon = 'mod_icmp_proc.gif';
UPDATE ttipo_modulo SET icon = 'mod_icmp_data.png' WHERE icon = 'mod_icmp_data.gif';
UPDATE ttipo_modulo SET icon = 'mod_tcp_data.png' WHERE icon = 'mod_tcp_data.gif';
UPDATE ttipo_modulo SET icon = 'mod_tcp_proc.png' WHERE icon = 'mod_tcp_proc.gif';
UPDATE ttipo_modulo SET icon = 'mod_tcp_string.png' WHERE icon = 'mod_tcp_string.gif';
UPDATE ttipo_modulo SET icon = 'mod_tcp_inc.png' WHERE icon = 'mod_tcp_inc.gif';
UPDATE ttipo_modulo SET icon = 'mod_udp_proc.png' WHERE icon = 'mod_udp_proc.gif';
UPDATE ttipo_modulo SET icon = 'mod_snmp_data.png' WHERE icon = 'mod_snmp_data.gif';
UPDATE ttipo_modulo SET icon = 'mod_snmp_inc.png' WHERE icon = 'mod_snmp_inc.gif';
UPDATE ttipo_modulo SET icon = 'mod_snmp_string.png' WHERE icon = 'mod_snmp_string.gif';
UPDATE ttipo_modulo SET icon = 'mod_snmp_proc.png' WHERE icon = 'mod_snmp_proc.gif';
INSERT INTO `ttipo_modulo` VALUES (100,'keep_alive',-1,'KeepAlive','mod_keepalive.png');

INSERT INTO `tnews` VALUES (1,'admin','Welcome to Pandora FMS 1.3 !','This is our new console, a lot of new features has been added from last version. Please read documentation about it and be free to test any option.\r\n\r\nPandora FMS team.','2007-06-22 13:03:20');


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
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (54,'Catalyst Free Mem','Taken from ftp://ftp.cisco.com/pub/mibs/oid/OLD-CISCO-MEMORY-MIB.oid',2,15,0,0,180,0,'','','public','1.3.6.1.4.1.9.2.1.8',4);

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

--
-- Network profile
--

INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (1,'Basic Network Monitoring','This includes basic SNMP, ICMP, and TCP checks.');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (2,'Basic Monitoring','Only ICMP check');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (3,'Basic DMZ Server monitoring','This group of network checks, checks for default services located on DMZ servers...');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (4,'Full SNMP Monitoring','');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (5,'Linux Server','Full Monitoring of a Linux server services.');

--
-- Network Profile components
--

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

