START TRANSACTION;

ALTER TABLE `tusuario` ADD COLUMN `integria_user_level_user` VARCHAR(60);
ALTER TABLE `tusuario` ADD COLUMN `integria_user_level_pass` VARCHAR(45);

COMMIT;
