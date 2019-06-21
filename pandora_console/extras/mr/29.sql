START TRANSACTION;

DELETE FROM `ttipo_modulo` WHERE `nombre` LIKE 'log4x';

ALTER TABLE tevent_filter ADD column id_source_event int(10);

COMMIT;
