START TRANSACTION;

-- Changes for the 'service like status' feature (Carrefour)
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_status_type` ENUM ('default', 'weight', 'service') DEFAULT 'default';
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_status_as_service_warning` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_data` ADD COLUMN `linked_layout_status_as_service_critical` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_status_type` ENUM ('default', 'weight', 'service') DEFAULT 'default';
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_status_as_service_warning` FLOAT(20, 3) NOT NULL default 0;
ALTER TABLE `tlayout_template_data` ADD COLUMN `linked_layout_status_as_service_critical` FLOAT(20, 3) NOT NULL default 0;

COMMIT;