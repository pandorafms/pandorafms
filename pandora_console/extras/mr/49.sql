START TRANSACTION;

UPDATE `tconfig` set value = 'Lato-Regular.ttf' WHERE token LIKE 'custom_report_front_font';
UPDATE `tconfig` set value = 'Lato-Regular.ttf' WHERE token LIKE 'fontpath';

ALTER TABLE `tevent_alert` ADD COLUMN `last_evaluation` bigint(20) NOT NULL default 0;
ALTER TABLE `tevent_alert` ADD COLUMN `pool_occurrences` int unsigned not null default 0;

COMMIT;
