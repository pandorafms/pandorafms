START TRANSACTION;

ALTER TABLE tlayout_data ADD COLUMN `show_last_value` tinyint(1) UNSIGNED NULL default '0';
ALTER TABLE tlayout MODIFY `name` varchar(600) NOT NULL;

ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbuser` text;
ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbpass` text;
ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbhost` text;
ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbport` text;
ALTER TABLE tmetaconsole_setup ADD COLUMN `meta_dbname` text;

COMMIT;
