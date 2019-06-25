START TRANSACTION;

ALTER TABLE `tmetaconsole_agent` ADD INDEX `id_tagente_idx` (`id_tagente`);

DELETE FROM `ttipo_modulo` WHERE `nombre` LIKE 'log4x';


COMMIT;
