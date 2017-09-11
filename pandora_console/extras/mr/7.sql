
START TRANSACTION;
ALTER TABLE tlayout_data ADD COLUMN `show_statistics` tinyint(2) NOT NULL default '0';
COMMIT;