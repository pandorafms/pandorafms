START TRANSACTION;

ALTER TABLE tpolicy_group_agents CONVERT TO CHARACTER SET UTF8MB4;
ALTER TABLE tevent_sound CONVERT TO CHARACTER SET UTF8MB4;
ALTER TABLE tsesion_filter CONVERT TO CHARACTER SET UTF8MB4;
CREATE TABLE IF NOT EXISTS `tsesion_filter` (
    `id_filter` INT NOT NULL AUTO_INCREMENT,
    `id_name` TEXT NULL,
    `text` TEXT NULL,
    `period` TEXT NULL,
    `ip` TEXT NULL,
    `type` TEXT NULL,
    `user` TEXT NULL,
    PRIMARY KEY (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tsesion_filter_log_viewer` (
    `id_filter` INT NOT NULL AUTO_INCREMENT,
    `id_name` TEXT NULL,
    `id_group_filter` TEXT NULL,
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

ALTER TABLE `talert_template_module_actions` ADD COLUMN `recovered` TINYINT NOT NULL DEFAULT 0;

COMMIT;
