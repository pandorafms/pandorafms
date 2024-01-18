START TRANSACTION;

ALTER TABLE `tevent_sound` MODIFY COLUMN `name` text NULL;
ALTER TABLE `tevent_sound` MODIFY COLUMN `sound` text NULL;
ALTER TABLE `treport_content` MODIFY COLUMN `use_prefix_notation` tinyint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `treport_content_template` MODIFY COLUMN `use_prefix_notation` tinyint unsigned NOT NULL DEFAULT 1;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `id_name` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `period` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `ip` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `type` text NULL;
ALTER TABLE `tsesion_filter` MODIFY COLUMN `user` text NULL;

COMMIT;
