START TRANSACTION;

ALTER TABLE `treport_content` add column `compare_work_time` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content_template` add column `compare_work_time` tinyint(1) UNSIGNED NOT NULL default 0;

ALTER TABLE `talert_templates` ADD COLUMN `previous_name` text;
ALTER TABLE `talert_actions` ADD COLUMN `previous_name` text;
ALTER TABLE `talert_commands` ADD COLUMN `previous_name` text;
ALTER TABLE `ttag` ADD COLUMN `previous_name` text NULL;
ALTER TABLE `tconfig_os` ADD COLUMN `previous_name` text NULL;

COMMIT;