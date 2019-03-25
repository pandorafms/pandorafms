START TRANSACTION;

ALTER TABLE tagent_custom_fields ADD COLUMN `combo_values` VARCHAR(255) DEFAULT '';

ALTER TABLE `treport_content` ADD COLUMN `show_extended_events` tinyint(1) default '0';

ALTER TABLE `trecon_task` ADD COLUMN `summary` text;

COMMIT;
