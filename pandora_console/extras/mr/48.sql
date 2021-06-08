START TRANSACTION;

ALTER TABLE `tevento` MODIFY `data` TINYTEXT default NULL;
ALTER TABLE `tmetaconsole_event` MODIFY `data` TINYTEXT default NULL;

COMMIT;
