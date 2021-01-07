START TRANSACTION;

CREATE TABLE `tinventory_alert`(
    `id` int UNSIGNED NOT NULL auto_increment,
    `id_module_inventory` int(10) NOT NULL,
    `actions` text NOT NULL default '',
	`id_group` mediumint(8) unsigned NULL default 0,
    `condition` enum('WHITE_LIST', 'BLACK_LIST', 'MATCH') NOT NULL default 'WHITE_LIST',
    `value` text NOT NULL default '',
    `name` tinytext NOT NULL default '',
    `description` text NOT NULL default '',
    `time_threshold` int(10) NOT NULL default '0',
    `last_fired` text NOT NULL default '',
    `disable_event` tinyint(1) UNSIGNED default 0,
    `enabled` tinyint(1) UNSIGNED default 1,
	PRIMARY KEY (`id`),
    FOREIGN KEY (`id_module_inventory`) REFERENCES tmodule_inventory(`id_module_inventory`)
		ON DELETE CASCADE ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tagente_modulo` ADD COLUMN `debug_content` varchar(200);

INSERT IGNORE INTO tuser_task VALUES (8, 'cron_task_generate_csv_log', 'a:1:{i:0;a:2:{s:11:"description";s:14:"Send to e-mail";s:4:"type";s:4:"text";}}', 'Send csv log');

ALTER TABLE `talert_snmp` ADD COLUMN `al_field16` TEXT NOT NULL AFTER `al_field15`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field17` TEXT NOT NULL AFTER `al_field16`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field18` TEXT NOT NULL AFTER `al_field17`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field19` TEXT NOT NULL AFTER `al_field18`;
ALTER TABLE `talert_snmp` ADD COLUMN `al_field20` TEXT NOT NULL AFTER `al_field19`;

COMMIT;
