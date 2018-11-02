START TRANSACTION;

ALTER TABLE `treport` ADD COLUMN `orientation` varchar(25) NOT NULL default 'vertical';

COMMIT;
