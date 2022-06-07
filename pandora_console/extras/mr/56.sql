START TRANSACTION;

ALTER TABLE `tusuario` DROP COLUMN `metaconsole_assigned_server`;

COMMIT;
