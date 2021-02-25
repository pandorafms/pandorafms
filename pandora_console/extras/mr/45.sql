START TRANSACTION;

ALTER TABLE `talert_actions` ADD COLUMN `create_wu_integria` TINYINT(1) default NULL;

COMMIT;
