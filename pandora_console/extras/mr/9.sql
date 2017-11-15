START TRANSACTION;

SET @st_oum708 = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tuser_task_scheduled') > 0,
    "ALTER TABLE tuser_task_scheduled MODIFY args TEXT NOT NULL",
    "SELECT 1"
));

PREPARE pr_oum708 FROM @st_oum708;
EXECUTE pr_oum708;
DEALLOCATE PREPARE pr_oum708;

ALTER TABLE tagente ADD COLUMN `safe_mode_module` int(10) unsigned NOT NULL default '0';

ALTER TABLE `tdashboard` ADD COLUMN `cells_slideshow` TINYINT(1) NOT NULL default 0;

COMMIT;