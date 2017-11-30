START TRANSACTION;

ALTER TABLE tsnmp_filter ADD unified_filters_id int(10) NOT NULL DEFAULT 0;
ALTER TABLE treport_content_template ADD COLUMN hide_no_data tinyint(1) DEFAULT 0;
ALTER TABLE tgraph_source ADD COLUMN `order` int(10) NOT NULL default 0;

COMMIT;