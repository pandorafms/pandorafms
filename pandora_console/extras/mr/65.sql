START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tnetwork_explorer_filter` (
`id` INT NOT NULL,
`filter_name` VARCHAR(45) NULL,
`top` VARCHAR(45) NULL,
`action` VARCHAR(45) NULL,
`advanced_filter` TEXT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tnetwork_usage_filter` (
`id` INT NOT NULL auto_increment,
`filter_name` VARCHAR(45) NULL,
`top` VARCHAR(45) NULL,
`action` VARCHAR(45) NULL,
`advanced_filter` TEXT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tlayout`
ADD COLUMN `grid_color` VARCHAR(45) NOT NULL DEFAULT '#cccccc' AFTER `maintenance_mode`,
ADD COLUMN `grid_size` VARCHAR(45) NOT NULL DEFAULT '10' AFTER `grid_color`;

ALTER TABLE `tlayout_template`
ADD COLUMN `grid_color` VARCHAR(45) NOT NULL DEFAULT '#cccccc' AFTER `maintenance_mode`,
ADD COLUMN `grid_size` VARCHAR(45) NOT NULL DEFAULT '10' AFTER `grid_color`;

DELETE FROM tconfig WHERE token = 'refr';

COMMIT;
