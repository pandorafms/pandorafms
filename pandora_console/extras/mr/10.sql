START TRANSACTION;

ALTER TABLE tsnmp_filter ADD unified_filters_id int(10) NOT NULL DEFAULT 0;
ALTER TABLE treport_content_template ADD COLUMN hide_no_data tinyint(1) DEFAULT 0;
ALTER TABLE tgraph_source ADD COLUMN `field_order` int(10) NOT NULL default 0;
UPDATE tgraph_source c, (SELECT @n := 0) m SET c.field_order = @n := @n + 1;

ALTER TABLE tgraph ADD COLUMN `summatory_series` tinyint(1) UNSIGNED NOT NULL default '0';
ALTER TABLE tgraph ADD COLUMN `average_series`  tinyint(1) UNSIGNED NOT NULL default '0';
ALTER TABLE tgraph ADD COLUMN `modules_series`  tinyint(1) UNSIGNED NOT NULL default '0';

COMMIT;