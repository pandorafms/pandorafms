START TRANSACTION;
ALTER TABLE `tpolicy_queue` MODIFY COLUMN `progress` int(10) NOT NULL default '0';


COMMIT;