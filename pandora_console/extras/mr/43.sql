START TRANSACTION;

ALTER TABLE `tagente_modulo` ADD COLUMN `debug_content` varchar(200);

COMMIT;