START TRANSACTION;

ALTER TABLE `tusuario` CHANGE COLUMN `metaconsole_data_section` `metaconsole_data_section` TEXT NOT NULL DEFAULT '' ;

ALTER TABLE `tmensajes` ADD COLUMN `icon_notification` VARCHAR(250) NULL DEFAULT NULL AFTER `url`;


COMMIT;