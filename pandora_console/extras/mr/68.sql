START TRANSACTION;

ALTER TABLE `tusuario` CHANGE COLUMN `metaconsole_data_section` `metaconsole_data_section` TEXT NOT NULL DEFAULT '' ;

COMMIT;