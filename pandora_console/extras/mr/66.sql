-- Active: 1685706586212@@172.16.0.2@3306@pandora
START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tgraph_analytics_filter` (
`id` INT NOT NULL auto_increment,
`filter_name` VARCHAR(45) NULL,
`user_id` VARCHAR(255) NULL,
`graph_modules` TEXT NULL,
`interval` INT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

UPDATE `twelcome_tip`
	SET title = 'Scheduled&#x20;downtimes',
         url = 'https://pandorafms.com/manual/en/documentation/04_using/11_managing_and_administration#scheduled_downtimes'
	WHERE title = 'planned&#x20;stops';

UPDATE tagente_modulo SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tpolicy_modules SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tnetwork_component SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
ALTER TABLE tagente_modulo ADD COLUMN `made_enabled` TINYINT UNSIGNED DEFAULT 0;
ALTER TABLE tpolicy_modules ADD COLUMN `made_enabled` TINYINT UNSIGNED DEFAULT 0;


COMMIT;
