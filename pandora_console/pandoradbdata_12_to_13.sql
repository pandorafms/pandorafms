
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

INSERT INTO `tnetwork_component` VALUES (3,'Sysname','Get name of system using SNMP standard MIB',1,17,0,0,900,0,'','','public','.1.3.6.1.2.1.1.1.0',1);
INSERT INTO `tnetwork_component` VALUES (19,'Power #1','PowerSupply #1 status',6,18,0,0,300,0,'','','public',' .1.3.6.1.4.1.2334.2.1.5.8.0',4);
INSERT INTO `tnetwork_component` VALUES (20,'Power #2','PowerSupply #2 status',6,18,0,0,300,0,'','','public',' .1.3.6.1.4.1.2334.2.1.5.10.0',4);
INSERT INTO `tnetwork_component` VALUES (22,'HSRP Status','Get status of HSRP',2,18,0,0,300,0,'','','public','1.3.6.1.4.1.9.9.106.1.2.1.1.15.12.106',2);
INSERT INTO `tnetwork_component` VALUES (24,'NIC #1 status','Status of NIC#1',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.1',2);
INSERT INTO `tnetwork_component` VALUES (25,'NIC #2 status','Status of NIC #2',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.2',2);
INSERT INTO `tnetwork_component` VALUES (26,'NIC #3 status','Status of NIC #3',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.3',2);
INSERT INTO `tnetwork_component` VALUES (27,'NIC #1 outOctects','Output throughtput on Interface #1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.1',2);
INSERT INTO `tnetwork_component` VALUES (28,'NIC #2 outOctects','Output troughtput on interface #2',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.2',1);
INSERT INTO `tnetwork_component` VALUES (29,'NIC #3 outOctects','Output troughtput on Interface #3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.3',2);
INSERT INTO `tnetwork_component` VALUES (30,'NIC #1 inOctects','Input troughtput on Interface #1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.1',2);
INSERT INTO `tnetwork_component` VALUES (31,'NIC #2 inOctects','Input throughtput for interface #2',10,16,0,0,180,0,'','NULL','public','.1.3.6.1.2.1.2.2.1.10.2',2);
INSERT INTO `tnetwork_component` VALUES (32,'NIC #3 inOctects','Input throught on interface #3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.3',2);
INSERT INTO `tnetwork_component` VALUES (34,'Host Alive','Check if host is alive using ICMP ping check.',10,6,0,0,120,0,'','','','',2);
INSERT INTO `tnetwork_component` VALUES (36,'Host Latency','Get host network latency in miliseconds, using ICMP.',10,7,0,0,180,0,'','','','',2);
INSERT INTO `tnetwork_component` VALUES (37,'Check HTTP Server','Test APACHE2 HTTP service remotely (Protocol response, not only openport)',10,9,0,0,300,80,'GET / HTTP/1.0^M^M','HTTP/1.1 200 OK','','',3);
INSERT INTO `tnetwork_component` VALUES (38,'Check FTP Server','Check FTP protocol, not only check port.',10,9,0,0,300,21,'QUIT','221','','',3);
INSERT INTO `tnetwork_component` VALUES (39,'Check SSH Server','Checks port 22 is opened',10,9,0,0,300,22,'','','','',2);
INSERT INTO `tnetwork_component` VALUES (40,'Check Telnet server','Check telnet port',10,9,0,0,300,23,'','','','',2);
INSERT INTO `tnetwork_component` VALUES (41,'Check SMTP server','Check if SMTP port it&#039;s open',10,9,0,0,300,25,'','','','',2);
INSERT INTO `tnetwork_component` VALUES (42,'Check POP3 server','Check POP3 port.',10,9,0,0,300,110,'','','','',2);
INSERT INTO `tnetwork_component` VALUES (43,'NIC #7 outOctects','Get outcoming octects from NIC #7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.7',2);
INSERT INTO `tnetwork_component` VALUES (44,'NIC #7 inOctects','Get incoming octects from NIC #7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.7',2);
INSERT INTO `tnetwork_component` VALUES (45,'NIC #4 Status','Get status of NIC #4',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.4',2);
INSERT INTO `tnetwork_component` VALUES (46,'NIC #5 Status','Get status of NIC #5',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.5',2);
INSERT INTO `tnetwork_component` VALUES (47,'NIC #6 Status','Get status of NIC #6',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.6',2);
INSERT INTO `tnetwork_component` VALUES (48,'NIC #7 Status','Get status of NIC #7',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.7',2);
INSERT INTO `tnetwork_component` VALUES (49,'CPU User','Linux User CPU Usage  (%)',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.9.0',4);
INSERT INTO `tnetwork_component` VALUES (50,'CPU System','Linux System CPU usage',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.10.0',4);
INSERT INTO `tnetwork_component` VALUES (51,'System Context Change','Linux System Context changes ',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.8.0',4);
INSERT INTO `tnetwork_component` VALUES (52,'System Interrupts','Linux system interrupts ',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.7.0',4);
INSERT INTO `tnetwork_component` VALUES (53,'Sytem IO Sent','Linux System IO Sent ',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.5.0',4);
INSERT INTO `tnetwork_component` VALUES (54,'System IO Recv','Linux System IO Recv ',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.6.0',4);
INSERT INTO `tnetwork_component` VALUES (55,'System SwapIn ','Linux System Swap In',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.11.3.0',1);
INSERT INTO `tnetwork_component` VALUES (56,'System Buffer Memory','Linux System Buffer Memory (used as available memory)',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.4.14.0',4);
INSERT INTO `tnetwork_component` VALUES (57,'System Cached Memory','Linux System Cached Memory (used as free memory)',5,15,0,0,180,0,'','','public','.1.3.6.1.4.1.2021.4.15.0',4);
INSERT INTO `tnetwork_component` VALUES (58,'System Processes','Total system process on any host',12,15,0,0,180,0,'','','public','.1.3.6.1.2.1.25.1.6.0',4);

INSERT INTO `tnetwork_component_group` VALUES (1,'General group',0);
INSERT INTO `tnetwork_component_group` VALUES (2,'Cisco MIBs',10);
INSERT INTO `tnetwork_component_group` VALUES (3,'Nortel MIBS',10);
INSERT INTO `tnetwork_component_group` VALUES (4,'3COM MIBs',10);
INSERT INTO `tnetwork_component_group` VALUES (5,'UNIX MIBs',12);
INSERT INTO `tnetwork_component_group` VALUES (6,'Packetshaper MIBs',10);
INSERT INTO `tnetwork_component_group` VALUES (7,'Nortel BPS 2000 MIBs',3);
INSERT INTO `tnetwork_component_group` VALUES (8,'Cisco Catalyst3750 MIBs',2);
INSERT INTO `tnetwork_component_group` VALUES (9,'Cisco AP120+',2);
INSERT INTO `tnetwork_component_group` VALUES (10,'Network Management',0);
INSERT INTO `tnetwork_component_group` VALUES (11,'Microsoft Windows MIB',12);
INSERT INTO `tnetwork_component_group` VALUES (12,'Operating Systems',0);

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

INSERT INTO `tnews` VALUES (1,'admin','Welcome to Pandora FMS 1.3.1!','This is the new Pandora FMS Console. A lot of new features have been added since last version. Please read the documentation about it, and feel free to test any option.\r\n\r\nThe Pandora FMS Team.','2007-06-22 13:03:20');

INSERT INTO `tnetwork_profile` VALUES (1,'SNMP Basic management','Basic SNMP management (only first interface)');
INSERT INTO `tnetwork_profile` VALUES (2,'Basic Server','Check basic server services and network latency. This checks SSH, FTP and HTTP. Also a ICMP host alive check.');
INSERT INTO `tnetwork_profile` VALUES (3,'Linux SNMP','Linux SNMP Management');

INSERT INTO `tnetwork_profile_component` VALUES (1,24,1);
INSERT INTO `tnetwork_profile_component` VALUES (2,27,1);
INSERT INTO `tnetwork_profile_component` VALUES (3,30,1);
INSERT INTO `tnetwork_profile_component` VALUES (4,37,2);
INSERT INTO `tnetwork_profile_component` VALUES (5,38,2);
INSERT INTO `tnetwork_profile_component` VALUES (6,39,2);
INSERT INTO `tnetwork_profile_component` VALUES (7,36,2);
INSERT INTO `tnetwork_profile_component` VALUES (8,34,2);
INSERT INTO `tnetwork_profile_component` VALUES (9,51,3);
INSERT INTO `tnetwork_profile_component` VALUES (10,52,3);
INSERT INTO `tnetwork_profile_component` VALUES (11,53,3);
INSERT INTO `tnetwork_profile_component` VALUES (12,54,3);
INSERT INTO `tnetwork_profile_component` VALUES (13,55,3);
INSERT INTO `tnetwork_profile_component` VALUES (14,56,3);
INSERT INTO `tnetwork_profile_component` VALUES (15,57,3);


