START TRANSACTION;
ALTER TABLE tusuario ADD COLUMN `time_autorefresh` int(5) unsigned NOT NULL default '30';
COMMIT;