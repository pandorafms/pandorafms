START TRANSACTION;

ALTER TABLE `talert_commands` ADD COLUMN `id_group` mediumint(8) unsigned NULL default 0;

ALTER TABLE `tusuario` DROP COLUMN `flash_chart`;

ALTER TABLE tlayout_template MODIFY `name` varchar(600) NOT NULL;

COMMIT;
