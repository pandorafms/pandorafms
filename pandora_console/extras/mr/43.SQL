START TRANSACTION;

ALTER TABLE `tagente_modulo` ADD COLUMN `debug` tinyint(1) UNSIGNED NOT NULL default 0;
ALTER TABLE `tagente_modulo` ADD COLUMN `debug_content` varchar(200);

COMMIT;