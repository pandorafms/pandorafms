START TRANSACTION;

ALTER TABLE tcontainer_item ADD COLUMN type_graph tinyint(1) unsigned default '0';

ALTER TABLE tlayout_data ADD COLUMN `clock_animation` varchar(60) NOT NULL default "analogic_1";
ALTER TABLE tlayout_data ADD COLUMN `time_format` varchar(60) NOT NULL default "time";
ALTER TABLE tlayout_data ADD COLUMN `timezone` varchar(60) NOT NULL default "Europe/Madrid";

COMMIT;