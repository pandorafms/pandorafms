START TRANSACTION;

ALTER TABLE `tncm_queue`
ADD COLUMN `id_agent_data` bigint unsigned AFTER `id_script`;

CREATE TABLE IF NOT EXISTS `tncm_agent_data_template` (
    `id` SERIAL,
    `name` TEXT,
    `vendors` TEXT,
    `models` TEXT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tncm_agent`
ADD COLUMN `id_agent_data_template` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id_template`;

CREATE TABLE IF NOT EXISTS `tncm_agent_data_template_scripts` (
    `id` SERIAL,
    `id_agent_data_template` BIGINT UNSIGNED NOT NULL,
    `id_script` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_agent_data_template`) REFERENCES `tncm_agent_data_template`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`id_script`) REFERENCES `tncm_script`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tncm_agent`
ADD COLUMN `agent_data_cron_interval` VARCHAR(100) NULL DEFAULT '' AFTER `cron_interval`;

ALTER TABLE `tncm_agent`
ADD COLUMN `agent_data_event_on_change` INT UNSIGNED NULL DEFAULT NULL AFTER `event_on_change`;


COMMIT;
