START TRANSACTION;

ALTER TABLE `tpolicies` ADD COLUMN `force_apply` tinyint(1) default 0;

COMMIT;