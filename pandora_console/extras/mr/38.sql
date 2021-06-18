START TRANSACTION;

INSERT INTO `ttipo_modulo` VALUES (38,'web_server_status_code_string',9,'Remote HTTP module to check server status code','mod_web_data.png');

ALTER TABLE trecon_task add column `rcmd_enabled` TINYINT(1) UNSIGNED DEFAULT 0 AFTER `wmi_enabled`;

CREATE TABLE `tnode_relations` (
	`id` int(10) unsigned NOT NULL auto_increment,
    `gateway` VARCHAR(100) NOT NULL,
	`imei` VARCHAR(100) NOT NULL,
	`node_address` VARCHAR(60) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;