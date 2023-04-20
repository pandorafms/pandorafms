START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tsesion_filter_log_viewer` (
    `id_filter` INT NOT NULL AUTO_INCREMENT,
    `id_name` TEXT NULL,
    `id_search_mode` INT NULL,
    `order` VARCHAR(45) NULL,
    `search` VARCHAR(255) NULL,
    `group_id` INT NULL,
    `date_range` TINYINT NULL,
    `start_date_defined` VARCHAR(45) NULL,
    `start_date_time` VARCHAR(45) NULL,
    `start_date_date` VARCHAR(45) NULL,
    `start_date_date_range` VARCHAR(45) NULL,
    `start_date_time_range` VARCHAR(45) NULL,
    `end_date_date_range` VARCHAR(45) NULL,
    `end_date_time_range` VARCHAR(45) NULL,
    `agent` VARCHAR(255) NULL,
    `source` VARCHAR(255) NULL,
    `display_mode` INT NULL,
    `capture_model` INT NULL,
    `graph_type` INT NULL,
    PRIMARY KEY (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

COMMIT;
