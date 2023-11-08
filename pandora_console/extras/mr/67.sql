START TRANSACTION;

SET @exist = (SELECT count(*) FROM information_schema.columns WHERE TABLE_NAME='tmetaconsole_agent' AND COLUMN_NAME='transactional_agent' AND table_schema = DATABASE());
SET @sqlstmt = IF (@exist>0, 'ALTER TABLE `tmetaconsole_agent` DROP COLUMN `transactional_agent`', 'SELECT ""');
prepare stmt from @sqlstmt;
execute stmt;

SET @exist = (SELECT count(*) FROM information_schema.columns WHERE TABLE_NAME='tagente' AND COLUMN_NAME='transactional_agent' AND table_schema = DATABASE());
SET @sqlstmt = IF (@exist>0, 'ALTER TABLE `tagente` DROP COLUMN `transactional_agent`', 'SELECT ""');
prepare stmt from @sqlstmt;
execute stmt;

COMMIT;
