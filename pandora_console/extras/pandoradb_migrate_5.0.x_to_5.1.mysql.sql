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
INSERT INTO `tconfig_os` VALUES (17, 'Router', 'Generic router', 'so_router.png');
INSERT INTO `tconfig_os` VALUES (18, 'Switch', 'Generic switch', 'so_switch.png');

-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE `pandora`.`tagente_modulo` MODIFY COLUMN `post_process` DOUBLE  DEFAULT NULL;

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
