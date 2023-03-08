START TRANSACTION;

UPDATE tconfig_os SET `icon_name` = 'linux@os.svg' WHERE `id_os` = 1;
UPDATE tconfig_os SET `icon_name` = 'solaris@os.svg' WHERE `id_os` = 2;
UPDATE tconfig_os SET `icon_name` = 'aix@os.svg' WHERE `id_os` = 3;
UPDATE tconfig_os SET `icon_name` = 'freebsd@os.svg' WHERE `id_os` = 4;
UPDATE tconfig_os SET `icon_name` = 'HP@os.svg' WHERE `id_os` = 5;
UPDATE tconfig_os SET `icon_name` = 'cisco@os.svg' WHERE `id_os` = 7;
UPDATE tconfig_os SET `icon_name` = 'apple@os.svg' WHERE `id_os` = 8;
UPDATE tconfig_os SET `icon_name` = 'windows@os.svg' WHERE `id_os` = 9;
UPDATE tconfig_os SET `icon_name` = 'other-OS@os.svg' WHERE `id_os` = 10;
UPDATE tconfig_os SET `icon_name` = 'network-server@os.svg' WHERE `id_os` = 11;
UPDATE tconfig_os SET `icon_name` = 'network-server@os.svg' WHERE `id_os` = 12;
UPDATE tconfig_os SET `icon_name` = 'network-server@os.svg' WHERE `id_os` = 13;
UPDATE tconfig_os SET `icon_name` = 'embedded@os.svg' WHERE `id_os` = 14;
UPDATE tconfig_os SET `icon_name` = 'android@os.svg' WHERE `id_os` = 15;
UPDATE tconfig_os SET `icon_name` = 'vmware@os.svg' WHERE `id_os` = 16;
UPDATE tconfig_os SET `icon_name` = 'routers@os.svg' WHERE `id_os` = 17;
UPDATE tconfig_os SET `icon_name` = 'switch@os.svg' WHERE `id_os` = 18;
UPDATE tconfig_os SET `icon_name` = 'satellite@os.svg' WHERE `id_os` = 19;
UPDATE tconfig_os SET `icon_name` = 'mainframe@os.svg' WHERE `id_os` = 20;
UPDATE tconfig_os SET `icon_name` = 'cluster@os.svg' WHERE `id_os` = 100;

UPDATE tgrupo SET `icon` = 'servers@groups.svg' WHERE `id_grupo` = 2;
UPDATE tgrupo SET `icon` = 'firewall@groups.svg' WHERE `id_grupo` = 4;
UPDATE tgrupo SET `icon` = 'database@groups.svg' WHERE `id_grupo` = 8;
UPDATE tgrupo SET `icon` = 'network@groups.svg' WHERE `id_grupo` = 9;
UPDATE tgrupo SET `icon` = 'unknown@groups.svg' WHERE `id_grupo` = 10;
UPDATE tgrupo SET `icon` = 'workstation@groups.svg' WHERE `id_grupo` = 11;
UPDATE tgrupo SET `icon` = 'applications@groups.svg' WHERE `id_grupo` = 12;
UPDATE tgrupo SET `icon` = 'web@groups.svg' WHERE `id_grupo` = 13;

UPDATE `ttipo_modulo` SET `icon` = 'data-server@svg.svg' WHERE `id_tipo` = 1;
UPDATE `ttipo_modulo` SET `icon` = 'generic-boolean@svg.svg' WHERE `id_tipo` = 2;
UPDATE `ttipo_modulo` SET `icon` = 'generic-string@svg.svg' WHERE `id_tipo` = 3;
UPDATE `ttipo_modulo` SET `icon` = 'data-server@svg.svg' WHERE `id_tipo` = 4;
UPDATE `ttipo_modulo` SET `icon` = 'data-server@svg.svg' WHERE `id_tipo` = 5;
UPDATE `ttipo_modulo` SET `icon` = 'ICMP-network-boolean-data@svg.svg' WHERE `id_tipo` = 6;
UPDATE `ttipo_modulo` SET `icon` = 'ICMP-network-latency@svg.svg' WHERE `id_tipo` = 7;
UPDATE `ttipo_modulo` SET `icon` = 'TCP-network-numeric-data@svg.svg' WHERE `id_tipo` = 8;
UPDATE `ttipo_modulo` SET `icon` = 'TCP-network-boolean-data@svg.svg' WHERE `id_tipo` = 9;
UPDATE `ttipo_modulo` SET `icon` = 'TCP-network-alphanumeric-data@svg.svg' WHERE `id_tipo` = 10;
UPDATE `ttipo_modulo` SET `icon` = 'TCP-network-incremental-data@svg.svg' WHERE `id_tipo` = 11;
UPDATE `ttipo_modulo` SET `icon` = 'SNMP-network-numeric-data@svg.svg' WHERE `id_tipo` = 15;
UPDATE `ttipo_modulo` SET `icon` = 'SNMP-network-incremental-data@svg.svg' WHERE `id_tipo` = 16;
UPDATE `ttipo_modulo` SET `icon` = 'SNMP-network-alphanumeric-data@svg.svg' WHERE `id_tipo` = 17;
UPDATE `ttipo_modulo` SET `icon` = 'SNMP-network-incremental-data@svg.svg' WHERE `id_tipo` = 18;
UPDATE `ttipo_modulo` SET `icon` = 'asynchronus-data@svg.svg' WHERE `id_tipo` = 21;
UPDATE `ttipo_modulo` SET `icon` = 'asynchronus-data@svg.svg' WHERE `id_tipo` = 22;
UPDATE `ttipo_modulo` SET `icon` = 'asynchronus-data@svg.svg' WHERE `id_tipo` = 23;
UPDATE `ttipo_modulo` SET `icon` = 'wux@svg.svg' WHERE `id_tipo` = 25;
UPDATE `ttipo_modulo` SET `icon` = 'server-web@svg.svg' WHERE `id_tipo` = 30;
UPDATE `ttipo_modulo` SET `icon` = 'web-analisys-data@svg.svg' WHERE `id_tipo` = 31;
UPDATE `ttipo_modulo` SET `icon` = 'server-web@svg.svg' WHERE `id_tipo` = 32;
UPDATE `ttipo_modulo` SET `icon` = 'server-web@svg.svg' WHERE `id_tipo` = 33;
UPDATE `ttipo_modulo` SET `icon` = 'remote-execution-numeric-data@svg.svg' WHERE `id_tipo` = 34;
UPDATE `ttipo_modulo` SET `icon` = 'remote-execution-boolean-data@svg.svg' WHERE `id_tipo` = 35;
UPDATE `ttipo_modulo` SET `icon` = 'remote-execution-alphanumeric-data@svg.svg' WHERE `id_tipo` = 36;
UPDATE `ttipo_modulo` SET `icon` = 'remote-execution-incremental-data@svg.svg' WHERE `id_tipo` = 37;
UPDATE `ttipo_modulo` SET `icon` = 'server-web@svg.svg' WHERE `id_tipo` = 38;
UPDATE `ttipo_modulo` SET `icon` = 'keepalive@svg.svg' WHERE `id_tipo` = 100;

CREATE TABLE IF NOT EXISTS `tagent_filter` (
  `id_filter`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_name` VARCHAR(600) NOT NULL,
  `id_group_filter` INT NOT NULL DEFAULT 0,
  `group_id` INT NOT NULL DEFAULT 0,
  `recursion` TEXT,
  `status` INT NOT NULL DEFAULT -1,
  `search` TEXT,
  `id_os` INT NOT NULL DEFAULT 0,
  `policies` TEXT,
  `search_custom` TEXT,
  `ag_custom_fields` TEXT,
  PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE `tevent_sound` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` TEXT NULL,
    `sound` TEXT NULL,
    `active` TINYINT NOT NULL DEFAULT '1',
PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE INDEX agente_modulo_estado ON tevento (estado, id_agentmodule);
CREATE INDEX idx_disabled ON talert_template_modules (disabled);

INSERT INTO `treport_custom_sql` (`name`, `sql`) VALUES ('Agent&#x20;safe&#x20;mode&#x20;not&#x20;enable', 'select&#x20;alias&#x20;from&#x20;tagente&#x20;where&#x20;safe_mode_module&#x20;=&#x20;0');

CREATE TABLE IF NOT EXISTS `tfavmenu_user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_user` VARCHAR(255) NOT NULL,
  `id_element` TEXT,
  `url` TEXT NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `section` VARCHAR(255) NOT NULL,
PRIMARY KEY (`id`));

COMMIT;
