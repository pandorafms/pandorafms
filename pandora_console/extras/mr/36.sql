START TRANSACTION;

ALTER TABLE `tmetaconsole_setup` ADD COLUMN `server_uid` TEXT NOT NULL default '';
SET @st_oum744 = (SELECT IF(
    (SELECT COUNT(*) FROM tconfig WHERE token LIKE 'server_unique_identifier') > 0,
    "SELECT 1",
	"INSERT INTO `tconfig` (`token`, `value`) VALUES ('server_unique_identifier', replace(uuid(),'-',''))"
));

PREPARE pr_oum744 FROM @st_oum744;
EXECUTE pr_oum744;
DEALLOCATE PREPARE pr_oum744;

ALTER TABLE `tpolicies` ADD COLUMN `force_apply` tinyint(1) default 0;

COMMIT;