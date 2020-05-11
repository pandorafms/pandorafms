START TRANSACTION;

ALTER TABLE tagente_modulo MODIFY COLUMN `custom_string_1` MEDIUMTEXT;

ALTER TABLE `trecon_task` MODIFY COLUMN `id_network_profile` TEXT;
ALTER TABLE `trecon_task` CHANGE COLUMN `create_incident` `review_mode` TINYINT(1) UNSIGNED DEFAULT 0;
ALTER TABLE `trecon_task` ADD COLUMN `subnet_csv` TINYINT(1) UNSIGNED DEFAULT 1;

UPDATE `trecon_task` SET `review_mode` = 1;
ALTER TABLE trecon_task add column `auto_monitor` TINYINT(1) UNSIGNED DEFAULT 1 AFTER `auth_strings`;
UPDATE `trecon_task` SET `auto_monitor` = 0;

CREATE TABLE `tdiscovery_tmp_agents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_rt` int(10) unsigned NOT NULL,
  `label` varchar(600) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `data` MEDIUMTEXT,
  `review_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_rt` (`id_rt`),
  INDEX `label` (`label`),
  CONSTRAINT `tdta_trt` FOREIGN KEY (`id_rt`) REFERENCES `trecon_task` (`id_rt`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tdiscovery_tmp_connections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_rt` int(10) unsigned NOT NULL,
  `dev_1` text,
  `dev_2` text,
  `if_1` text,
  `if_2` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tpen` (
  `pen` int(10) unsigned NOT NULL,
  `manufacturer` TEXT,
  `description` TEXT,
  PRIMARY KEY (`pen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tnetwork_profile_pen` (
  `pen` int(10) unsigned NOT NULL,
  `id_np` int(10) unsigned NOT NULL,
  CONSTRAINT `fk_network_profile_pen_pen` FOREIGN KEY (`pen`)
    REFERENCES `tpen` (`pen`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_network_profile_pen_id_np` FOREIGN KEY (`id_np`)
    REFERENCES `tnetwork_profile` (`id_np`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `twidget_dashboard` ADD COLUMN `position` TEXT NOT NULL default '';

ALTER TABLE `tagente_estado` ADD COLUMN `last_status_change` bigint(20) NOT NULL default '0';

UPDATE `tconfig` SET `value`='policy,agent,data_type,module_name,server_type,interval,status,last_status_change,graph,warn,data,timestamp' WHERE `token` = 'status_monitor_fields';

ALTER TABLE `talert_templates` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `tevent_alert` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;
ALTER TABLE `talert_snmp` ADD COLUMN `disable_event` tinyint(1) DEFAULT 0;

UPDATE twidget SET description='Show a visual console' WHERE class_name='MapsMadeByUser';
UPDATE twidget SET description='Clock' WHERE class_name='ClockWidget';
UPDATE twidget SET description='Group status' WHERE class_name='SystemGroupStatusWidget';

INSERT IGNORE INTO `tpen` VALUES (9,'cisco','Cisco&#x20;System'),(11,'hp','Hewlett&#x20;Packard'),(2021,'general_snmp','U.C.&#x20;Davis,&#x20;ECE&#x20;Dept.&#x20;Tom'),(2636,'juniper','Juniper&#x20;Networks'),(3375,'f5','F5&#x20;Labs'),(8072,'general_snmp','Net&#x20;SNMP'),(12356,'fortinet','Fortinet');

SET @template_name = 'Network&#x20;Management';
SET @template_description = 'Basic network monitoring template';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Network Management')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Cisco&#x20;MIBS';
SET @template_description = 'Cisco devices monitoring template (SNMP)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Cisco MIBS' OR g.name = 'Catalyst 2900')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);
INSERT INTO tnetwork_profile_pen (pen, id_np) SELECT * FROM (SELECT p.pen pen, np.id_np id_np FROM tnetwork_profile np, tpen p WHERE np.name = @template_name AND (p.pen = 9)) AS tmp WHERE NOT EXISTS (SELECT pp.id_np FROM tnetwork_profile p, tnetwork_profile_pen pp WHERE p.id_np = pp.id_np AND p.name = @template_name);

SET @template_name = 'Linux&#x20;System';
SET @template_description = 'Linux system monitoring template (SNMP)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);

SET @module_group = 'Linux';

INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Linux' OR g.name = 'UCD Mibs (Linux, UCD-SNMP)')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);
INSERT INTO tnetwork_profile_pen (pen, id_np) SELECT * FROM (SELECT p.pen pen, np.id_np id_np FROM tnetwork_profile np, tpen p WHERE np.name = @template_name AND (p.pen = 2021 OR p.pen = 2636)) AS tmp WHERE NOT EXISTS (SELECT pp.id_np FROM tnetwork_profile p, tnetwork_profile_pen pp WHERE p.id_np = pp.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;System';
SET @template_description = 'Windows system monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Microsoft&#x20;Windows' OR g.name = 'Windows System')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Hardware';
SET @template_description = 'Windows hardware monitoring templae (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows Hardware Layer')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Active&#x20;Directory';
SET @template_description = 'Active directory monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows AD' OR g.name = 'AD&#x20;Counters')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;IIS';
SET @template_description = 'IIS monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows IIS' OR g.name = 'IIS&#x20;services')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Exchange';
SET @template_description = 'Exchange monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows Exchange' OR g.name = 'Exchange&#x20;Services' OR g.name = 'Exchange&#x20;TCP&#x20;Ports')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;LDAP';
SET @template_description = 'LDAP monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows LDAP')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;MDSTC';
SET @template_description = 'MDSTC monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows MSDTC')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Printers';
SET @template_description = 'Windows printers monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows Printers')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;DNS';
SET @template_description = 'Windows DNS monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Windows&#x20;DNS' OR g.name = 'DNS&#x20;Counters')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;MS&#x20;SQL&#x20;Server';
SET @template_description = 'MS SQL Server monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'MS&#x20;SQL&#x20;Server')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Oracle';
SET @template_description = 'Oracle monitoring template';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Oracle')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'MySQL';
SET @template_description = 'MySQL monitoring template';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'MySQL')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

SET @template_name = 'Windows&#x20;Antivirus';
SET @template_description = 'Windows antivirus monitoring template (WMI)';

INSERT INTO tnetwork_profile (id_np, name, description) SELECT * FROM (SELECT '' id_np, @template_name name, @template_description description) AS tmp WHERE NOT EXISTS (SELECT id_np FROM tnetwork_profile WHERE name = @template_name);
INSERT INTO tnetwork_profile_component (id_nc, id_np) SELECT * FROM (SELECT c.id_nc id_nc, p.id_np id_np FROM tnetwork_profile p, tnetwork_component c, tnetwork_component_group g WHERE g.id_sg = c.id_group AND p.name = @template_name AND (g.name = 'Norton' OR g.name = 'Panda' OR g.name = 'McAfee' OR g.name = 'Bitdefender' OR g.name = 'BullGuard' OR g.name = 'AVG' OR g.name = 'Kaspersky')) AS tmp WHERE NOT EXISTS (SELECT pc.id_np FROM tnetwork_profile p, tnetwork_profile_component pc WHERE p.id_np = pc.id_np AND p.name = @template_name);

COMMIT;
