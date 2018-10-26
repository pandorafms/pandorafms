START TRANSACTION;

ALTER TABLE `tusuario` DROP COLUMN `flash_chart`;

ALTER TABLE tlayout_template MODIFY `name` varchar(600) NOT NULL;

COMMIT;
