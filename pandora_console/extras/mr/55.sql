START TRANSACTION;

ALTER TABLE `tservice` ADD COLUMN `enable_sunburst` tinyint(1) NOT NULL default 0;

ALTER TABLE `tdashboard` MODIFY `name` TEXT NOT NULL DEFAULT '';

SET @st_oum763 = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'tautoconfig' AND table_schema = DATABASE() AND column_name = 'disabled') > 0,
    "SELECT 1",
    "ALTER TABLE `tautoconfig` ADD COLUMN `disabled` TINYINT DEFAULT 0"
));

PREPARE pr_oum763 FROM @st_oum763;
EXECUTE pr_oum763;
DEALLOCATE PREPARE pr_oum763;

COMMIT;
