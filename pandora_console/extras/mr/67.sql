START TRANSACTION;

SELECT @generic_data := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "generic_data";
SELECT @generic_proc := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "generic_proc";
SELECT @async_data := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "async_data";
SELECT @async_proc := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "async_proc";
UPDATE `tagente_modulo` INNER JOIN `tservice` ON `tagente_modulo`.`custom_integer_1` = `tservice`.`id` SET `tagente_modulo`.`id_tipo_modulo` = @generic_data WHERE `tagente_modulo`.`id_tipo_modulo` = @async_data;
UPDATE `tagente_modulo` INNER JOIN `tservice` ON `tagente_modulo`.`custom_integer_1` = `tservice`.`id` SET `tagente_modulo`.`id_tipo_modulo` = @generic_proc WHERE `tagente_modulo`.`id_tipo_modulo` = @async_proc;

COMMIT;