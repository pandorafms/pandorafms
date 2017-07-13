START TRANSACTION;

SET @st_oum706 = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'treport_content' AND table_schema = DATABASE() AND column_name = 'historical_db') > 0,
    "SELECT 1",
	"ALTER TABLE treport_content ADD COLUMN historical_db tinyint(1) UNSIGNED NOT NULL default 0"
));

PREPARE pr_oum706 FROM @st_oum706;
EXECUTE pr_oum706;
DEALLOCATE PREPARE pr_oum706;

SET @st_oum706 = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'tpolicy_modules' AND table_schema = DATABASE() AND column_name = 'ip_target') > 0,
    "SELECT 1",
	"ALTER TABLE tpolicy_modules ADD COLUMN ip_target varchar(100) default ''"
));

PREPARE pr_oum706 FROM @st_oum706;
EXECUTE pr_oum706;
DEALLOCATE PREPARE pr_oum706;

COMMIT;

