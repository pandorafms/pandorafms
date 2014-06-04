-- ---------------------------------------------------------------------
-- Table `talert_templates`
-- ---------------------------------------------------------------------
ALTER TABLE `talert_templates` ADD COLUMN `field1_recovery` text NOT NULL;

-- ---------------------------------------------------------------------
-- Table `talert_actions`
-- ---------------------------------------------------------------------
ALTER TABLE `talert_actions` ADD COLUMN `field1_recovery` text NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field2_recovery` text NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field3_recovery` text NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field4_recovery` text NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field5_recovery` text NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field6_recovery` text NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field7_recovery` text NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field8_recovery` text NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field9_recovery` text NOT NULL;
ALTER TABLE `talert_actions` ADD COLUMN `field10_recovery` text NOT NULL;

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO `tconfig` (`token`, `value`) VALUES 
('graph_color4', '#FF66CC'),
('graph_color5', '#CC0000'),
('graph_color6', '#0033FF'),
('graph_color7', '#99FF99'),
('graph_color8', '#330066'),
('graph_color9', '#66FFFF'),
('graph_color10', '#6666FF');

UPDATE tconfig SET `value`='#FFFF00' WHERE `token`='graph_color2';
UPDATE tconfig SET `value`='#FF6600' WHERE `token`='graph_color3';

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
ALTER TABLE tgraph_source MODIFY COLUMN `weight` float(8,3) NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tconfig_os`
-- ---------------------------------------------------------------------
INSERT INTO `tconfig_os` (`name`, `description`, `icon_name`) VALUES ('Router', 'Generic router', 'so_router.png');
INSERT INTO `tconfig_os` (`name`, `description`, `icon_name`) VALUES ('Switch', 'Generic switch', 'so_switch.png');
INSERT INTO `tconfig_os` (`name`, `description`, `icon_name`) VALUES ('Satellite', 'Satellite agent', 'satellite.png');


-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE `pandora`.`tagente_modulo` MODIFY COLUMN `post_process` DOUBLE  DEFAULT NULL;
/* 2014/05/21 */
ALTER TABLE `tagente_modulo` ADD COLUMN `min_ff_event_normal` int(4) unsigned default '0';
ALTER TABLE `tagente_modulo` ADD COLUMN `min_ff_event_warning` int(4) unsigned default '0';
ALTER TABLE `tagente_modulo` ADD COLUMN `min_ff_event_critical` int(4) unsigned default '0';
ALTER TABLE `tagente_modulo` ADD COLUMN `each_ff` tinyint(1) unsigned default '0';
/* 2014/05/31 */
ALTER TABLE `tagente_modulo` ADD COLUMN `ff_timeout` int(4) unsigned default '0';

/* 2014/03/18 */
-- ---------------------------------------------------------------------
-- Table `tmodule_relationship`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tmodule_relationship` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`module_a` int(10) unsigned NOT NULL,
	`module_b` int(10) unsigned NOT NULL,
	`disable_update` tinyint(1) unsigned NOT NULL default '0',
	PRIMARY KEY (`id`),
	FOREIGN KEY (`module_a`) REFERENCES tagente_modulo(`id_agente_modulo`)
		ON DELETE CASCADE,
	FOREIGN KEY (`module_b`) REFERENCES tagente_modulo(`id_agente_modulo`)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE `talert_snmp` ADD COLUMN `id_group` int(10) unsigned NOT NULL default '0';

/* 2014/03/19 */
-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f11_` text;
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f12_` text;
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f13_` text;
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f14_` text;
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f15_` text;
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f16_` text;
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f17_` text;
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f18_` text;
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f19_` text;
ALTER TABLE `talert_snmp` ADD COLUMN `_snmp_f20_` text;

ALTER TABLE `tnetwork_map` ADD COLUMN `l2_network` tinyint(1) unsigned NOT NULL default '0';

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
ALTER TABLE `tlayout_data` ADD COLUMN `id_group` INTEGER UNSIGNED NOT NULL default 0;
ALTER TABLE `tlayout_data` ADD COLUMN `id_custom_graph` INTEGER UNSIGNED NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `talert_snmp`
-- ---------------------------------------------------------------------
ALTER TABLE `talert_snmp` ADD COLUMN `order_1` int(10) unsigned NOT NULL default 1;
ALTER TABLE `talert_snmp` ADD COLUMN `order_2` int(10) unsigned NOT NULL default 2;
ALTER TABLE `talert_snmp` ADD COLUMN `order_3` int(10) unsigned NOT NULL default 3;
ALTER TABLE `talert_snmp` ADD COLUMN `order_4` int(10) unsigned NOT NULL default 4;
ALTER TABLE `talert_snmp` ADD COLUMN `order_5` int(10) unsigned NOT NULL default 5;
ALTER TABLE `talert_snmp` ADD COLUMN `order_6` int(10) unsigned NOT NULL default 6;
ALTER TABLE `talert_snmp` ADD COLUMN `order_7` int(10) unsigned NOT NULL default 7;
ALTER TABLE `talert_snmp` ADD COLUMN `order_8` int(10) unsigned NOT NULL default 8;
ALTER TABLE `talert_snmp` ADD COLUMN `order_9` int(10) unsigned NOT NULL default 9;
ALTER TABLE `talert_snmp` ADD COLUMN `order_10` int(10) unsigned NOT NULL default 10;
ALTER TABLE `talert_snmp` ADD COLUMN `order_11` int(10) unsigned NOT NULL default 11;
ALTER TABLE `talert_snmp` ADD COLUMN `order_12` int(10) unsigned NOT NULL default 12;
ALTER TABLE `talert_snmp` ADD COLUMN `order_13` int(10) unsigned NOT NULL default 13;
ALTER TABLE `talert_snmp` ADD COLUMN `order_14` int(10) unsigned NOT NULL default 14;
ALTER TABLE `talert_snmp` ADD COLUMN `order_15` int(10) unsigned NOT NULL default 15;
ALTER TABLE `talert_snmp` ADD COLUMN `order_16` int(10) unsigned NOT NULL default 16;
ALTER TABLE `talert_snmp` ADD COLUMN `order_17` int(10) unsigned NOT NULL default 17;
ALTER TABLE `talert_snmp` ADD COLUMN `order_18` int(10) unsigned NOT NULL default 18;
ALTER TABLE `talert_snmp` ADD COLUMN `order_19` int(10) unsigned NOT NULL default 19;
ALTER TABLE `talert_snmp` ADD COLUMN `order_20` int(10) unsigned NOT NULL default 20;

-- ---------------------------------------------------------------------
-- Table `talert_snmp_action`
-- ---------------------------------------------------------------------
CREATE TABLE  IF NOT EXISTS  `talert_snmp_action` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_alert_snmp` int(10) unsigned NOT NULL default '0',
	`alert_type` int(2) unsigned NOT NULL default '0',
	`al_field1` text NOT NULL,
	`al_field2` text NOT NULL,
	`al_field3` text NOT NULL,
	`al_field4` text NOT NULL,
	`al_field5` text NOT NULL,
	`al_field6` text NOT NULL,
	`al_field7` text NOT NULL,
	`al_field8` text NOT NULL,
	`al_field9` text NOT NULL,
	`al_field10` text NOT NULL,
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `treport`
-- ---------------------------------------------------------------------
ALTER TABLE `treport` ADD COLUMN `non_interactive` tinyint(1) UNSIGNED NOT NULL default 0;

/* 2014/04/11 */
-- ---------------------------------------------------------------------
-- Table `trecon_script` and `trecon_task`
-- ---------------------------------------------------------------------
ALTER TABLE `trecon_script` ADD COLUMN `macros` TEXT;
ALTER TABLE `trecon_task` ADD COLUMN `macros` TEXT;

-- ---------------------------------------------------------------------
-- Table `trecon_script`
-- ---------------------------------------------------------------------
INSERT INTO `trecon_script` (`name`, `description`, `script`, `macros`) VALUES ('SNMP&#x20;L2&#x20;Recon','Pandora&#x20;FMS&#x20;SNMP&#x20;Recon&#x20;Plugin&#x20;for&#x20;level&#x20;2&#x20;network&#x20;topology&#x20;discovery.&#x0d;&#x0a;&#40;c&#41;&#x20;Artica&#x20;ST&#x20;2014&#x20;&lt;info@artica.es&gt;&#x0d;&#x0a;&#x0d;&#x0a;Usage:&#x0d;&#x0a;&#x0d;&#x0a;&#x20;&#x20;&#x20;./snmp-recon.pl&#x20;&lt;task_id&gt;&#x20;&lt;group_id&gt;&#x20;&lt;create_incident&gt;&#x20;&lt;custom_field1&gt;&#x20;&lt;custom_field2&gt;&#x20;[custom_field3]&#x20;[custom_field4]&#x0d;&#x0a;&#x0d;&#x0a;&#x20;*&#x20;custom_field1&#x20;=&#x20;comma&#x20;separated&#x20;list&#x20;of&#x20;networks&#x20;&#40;i.e.:&#x20;192.168.1.0/24,192.168.2.0/24&#41;&#x0d;&#x0a;&#x20;*&#x20;custom_field2&#x20;=&#x20;comma&#x20;separated&#x20;list&#x20;of&#x20;snmp&#x20;communities&#x20;to&#x20;try.&#x0d;&#x0a;&#x20;*&#x20;custom_field3&#x20;=&#x20;a&#x20;router&#x20;in&#x20;the&#x20;network.&#x20;Optional&#x20;but&#x20;recommended.&#x0d;&#x0a;&#x0d;&#x0a;&#x20;*&#x20;custom_field4&#x20;=&#x20;set&#x20;to&#x20;-a&#x20;to&#x20;add&#x20;all&#x20;network&#x20;interfaces&#x20;&#40;by&#x20;default&#x20;only&#x20;interfaces&#x20;that&#x20;are&#x20;up&#x20;are&#x20;added&#41;.&#x0d;&#x0a;&#x0d;&#x0a;&#x20;Additional&#x20;information:&#x0d;&#x0a;When&#x20;the&#x20;script&#x20;is&#x20;called&#x20;from&#x20;a&#x20;recon&#x20;task&#x20;the&#x20;task_id,&#x20;group_id&#x20;and&#x20;create_incident&#x20;parameters&#x20;are&#x20;automatically&#x20;filled&#x20;by&#x20;the&#x20;Pandora&#x20;FMS&#x20;Server.','/usr/share/pandora_server/util/recon_scripts/snmp-recon.pl','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Network\",\"help\":\"Comma&#x20;separated&#x20;list&#x20;of&#x20;networks&#x20;&#40;i.e.:&#x20;192.168.1.0/24,192.168.2.0/24&#41;\",\"value\":\"\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Community\",\"help\":\"Comma&#x20;separated&#x20;list&#x20;of&#x20;snmp&#x20;communities&#x20;to&#x20;try.\",\"value\":\"\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Router\",\"help\":\"A&#x20;router&#x20;in&#x20;the&#x20;network.&#x20;Optional&#x20;but&#x20;recommended.\",\"value\":\"\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Optional&#x20;parameter\",\"help\":\"Set&#x20;to&#x20;-a&#x20;to&#x20;add&#x20;all&#x20;network&#x20;interfaces&#x20;&#40;by&#x20;default&#x20;only&#x20;interfaces&#x20;that&#x20;are&#x20;up&#x20;are&#x20;added&#41;.\",\"value\":\"\",\"hide\":\"\"}}');

INSERT INTO `trecon_script` (`name`, `description`, `script`, `macros`) VALUES ('WMI&#x20;Recon&#x20;Script','This&#x20;script&#x20;is&#x20;used&#x20;to&#x20;automatically&#x20;gather&#x20;host&#x20;information&#x20;via&#x20;WMI.&#x0d;&#x0a;Available&#x20;parameters:&#x0d;&#x0a;&#x0d;&#x0a;*&#x20;Network&#x20;=&#x20;network&#x20;to&#x20;scan&#x20;&#40;e.g.&#x20;192.168.100.0/24&#41;.&#x0d;&#x0a;*&#x20;WMI&#x20;auth&#x20;=&#x20;comma&#x20;separated&#x20;list&#x20;of&#x20;WMI&#x20;authentication&#x20;tokens&#x20;in&#x20;the&#x20;format&#x20;username%password&#x20;&#40;e.g.&#x20;Administrador%pass&#41;.&#x0d;&#x0a;&#x0d;&#x0a;See&#x20;the&#x20;documentation&#x20;for&#x20;more&#x20;information.','/usr/share/pandora_server/util/recon_scripts/wmi-recon.pl','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Network\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"WMI&#x20;auth\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"}}');

/* 2014/04/10 */
ALTER TABLE `treport_content` ADD COLUMN `name` varchar(150) NULL;

/* 2014/05/05 */
-- ---------------------------------------------------------------------
-- Table `tlink`
-- ---------------------------------------------------------------------
UPDATE `tlink` SET `link`='http://wiki.pandorafms.com/?title=Pandora' WHERE `name`='Pandora FMS Manual';

/* 2014/05/07 */
-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO `tconfig` (`token`, `value`) VALUES 
('custom_report_front', 0),
('custom_report_front_font', 'FreeSans.ttf'),
('custom_report_front_logo', 'images/pandora_logo_white.jpg'),
('custom_report_front_header', ''),
('custom_report_front_footer', '');

/* 2014/05/19 */
-- ---------------------------------------------------------------------
-- Table `tnetwork_profile`
-- ---------------------------------------------------------------------
DELETE FROM `tnetwork_profile` WHERE `id_np`=1;
DELETE FROM `tnetwork_profile` WHERE `id_np`=4;
DELETE FROM `tnetwork_profile` WHERE `id_np`=5;
DELETE FROM `tnetwork_profile` WHERE `id_np`=6;

/* 2014/05/19 */
-- ---------------------------------------------------------------------
-- Table `tnetwork_profile_component`
-- ---------------------------------------------------------------------
DELETE FROM `tnetwork_profile_component` WHERE `id_np`=1;
DELETE FROM `tnetwork_profile_component` WHERE `id_np`=4;
DELETE FROM `tnetwork_profile_component` WHERE `id_np`=5;
DELETE FROM `tnetwork_profile_component` WHERE `id_np`=6;
DELETE FROM `tnetwork_profile_component` WHERE `id_nc`=24 AND `id_np`=3;

/* 2014/05/25 */
-- ---------------------------------------------------------------------
-- Table `tnetwork_component`
-- ---------------------------------------------------------------------
ALTER TABLE `tnetwork_component` ADD COLUMN `min_ff_event_normal` int(4) unsigned default '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `min_ff_event_warning` int(4) unsigned default '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `min_ff_event_critical` int(4) unsigned default '0';
ALTER TABLE `tnetwork_component` ADD COLUMN `each_ff` tinyint(1) unsigned default '0';

/* 2014/05/30 */
-- ---------------------------------------------------------------------
-- Table `tnews`
-- ---------------------------------------------------------------------
ALTER TABLE `tnews` ADD COLUMN `id_group` int(10) NOT NULL default 0;
ALTER TABLE `tnews` ADD COLUMN `modal` tinyint(1) DEFAULT 0;
ALTER TABLE `tnews` ADD COLUMN `expire` tinyint(1) DEFAULT 0;
ALTER TABLE `tnews` ADD COLUMN `expire_timestamp` DATETIME  NOT NULL DEFAULT 0;

/* 2014/05/31 */
-- ---------------------------------------------------------------------
-- Table `tagente_estado`
-- ---------------------------------------------------------------------
ALTER TABLE `tagente_estado` ADD COLUMN `ff_start_utimestamp` bigint(20) default 0;

/* 2014/06/04 */
-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_modulo ADD COLUMN `ff_timeout` int(4) unsigned default '0';
