START TRANSACTION;

ALTER TABLE `tservice` ADD COLUMN `is_favourite` tinyint(1) NOT NULL default 0;
UPDATE tservice SET `is_favourite` = 1 WHERE `name` REGEXP '^[_|.|\[|\(]';

COMMIT;