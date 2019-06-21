START TRANSACTION;

ALTER TABLE tevent_filter ADD column id_source_event int(10);

COMMIT;
