START TRANSACTION;

ALTER TABLE `tservice_element` ADD COLUMN `rules` text;

UPDATE `tservice` SET `auto_calculate`=0;

COMMIT;