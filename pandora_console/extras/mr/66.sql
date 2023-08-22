START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tgraph_analytics_filter` (
`id` INT NOT NULL auto_increment,
`filter_name` VARCHAR(45) NULL,
`user_id` VARCHAR(255) NULL,
`graph_modules` TEXT NULL,
`interval` INT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

COMMIT;
