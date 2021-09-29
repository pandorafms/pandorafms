START TRANSACTION;

ALTER TABLE `tevent_alert` ADD COLUMN `id_template_conditions` int(10) unsigned NOT NULL default 0;
ALTER TABLE `tevent_alert` ADD COLUMN `id_template_fields` int(10) unsigned NOT NULL default 0;
ALTER TABLE `tevent_filter` ADD COLUMN `time_from` TIME NULL;
ALTER TABLE `tevent_filter` ADD COLUMN `time_to` TIME NULL;

ALTER TABLE `tevent_alert` ADD COLUMN `last_evaluation` bigint(20) NOT NULL default 0;
ALTER TABLE `tevent_alert` ADD COLUMN `pool_occurrences` int unsigned not null default 0;

COMMIT;