START TRANSACTION;

ALTER TABLE `talert_commands` ADD COLUMN `fields_hidden` text;

COMMIT;
