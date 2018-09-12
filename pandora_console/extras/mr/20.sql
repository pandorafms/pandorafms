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
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_node_id` INT(10) NOT NULL default 0;
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_status_type` ENUM ('default', 'weight', 'service') DEFAULT 'default';
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_status_as_service_warning` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_status_as_service_critical` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_node_id` INT(10) NOT NULL default 0;

-- -----------------------------------------------------
-- Add column in table `treport`
-- -----------------------------------------------------

ALTER TABLE `treport` ADD COLUMN `hidden` tinyint(1) NOT NULL DEFAULT 0;

COMMIT;