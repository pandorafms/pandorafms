START TRANSACTION;

UPDATE tagente_modulo SET `tcp_send` = '2c' WHERE `tcp_send` = '2';

COMMIT;
