START TRANSACTION;

ALTER TABLE treport_content ADD COLUMN `recursion` TINYINT(1) default NULL;

ALTER TABLE tevent_filter ADD COLUMN `user_comment` text NOT NULL;
ALTER TABLE tevent_filter ADD COLUMN `source` tinytext NOT NULL;
ALTER TABLE tevent_filter ADD COLUMN `id_extra` tinytext NOT NULL;

ALTER TABLE tagente_modulo ALTER COLUMN `parent_module_id` SET default 0;

-- Changes for the 'service like status' feature (Carrefour)
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_status_type` ENUM ('default', 'weight', 'service') DEFAULT 'default';
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_status_as_service_warning` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_status_as_service_critical` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_status_type` ENUM ('default', 'weight', 'service') DEFAULT 'default';
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_status_as_service_warning` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_status_as_service_critical` FLOAT(20, 3) NOT NULL default 0;

CREATE TABLE IF NOT EXISTS `tlog_graph_models` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`title` TEXT NOT NULL,
	`regexp` TEXT NOT NULL,
	`fields` TEXT NOT NULL,
	`average` tinyint(1) NOT NULL default '0',
	PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

COMMIT;