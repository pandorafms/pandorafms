START TRANSACTION;

ALTER TABLE `tusuario` MODIFY COLUMN `integria_user_level_pass` TEXT;

DROP TABLE `tincidencia`;
DROP TABLE `tnota`;
DROP TABLE `tattachment`;

COMMIT;