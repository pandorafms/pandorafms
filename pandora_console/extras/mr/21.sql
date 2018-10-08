START TRANSACTION;

ALTER TABLE tlayout_data ADD COLUMN `show_last_value` tinyint(1) UNSIGNED NULL default '0';
ALTER TABLE tlayout MODIFY `name` varchar(600) NOT NULL;

COMMIT;
