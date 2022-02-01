START TRANSACTION;
ALTER TABLE `tusuario` ADD COLUMN `local_user` tinyint(1) unsigned NOT NULL DEFAULT 0;


COMMIT;