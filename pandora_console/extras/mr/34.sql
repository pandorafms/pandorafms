START TRANSACTION;

ALTER TABLE `treport_content` ADD COLUMN `landscape` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content` ADD COLUMN `pagebreak` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content_template` ADD COLUMN `landscape` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `treport_content_template` ADD COLUMN `pagebreak` tinyint(1) UNSIGNED NOT NULL default 0;

ALTER TABLE `tevent_response` ADD COLUMN `command_timeout` int(5) unsigned NOT NULL DEFAULT 90;

COMMIT;
