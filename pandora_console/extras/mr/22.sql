START TRANSACTION;

ALTER TABLE `talert_commands` ADD COLUMN `id_group` mediumint(8) unsigned NULL default 0;

COMMIT;
