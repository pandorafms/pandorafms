START TRANSACTION;

ALTER TABLE tsnmp_filter ADD unified_filters_id int(10) NOT NULL DEFAULT 0;

COMMIT;