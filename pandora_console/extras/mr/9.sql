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
ALTER TABLE tmetaconsole_agent ADD COLUMN `safe_mode_module` int(10) unsigned NOT NULL default '0';

alter table tlayout_data add column element_group int(10) not null default 0;

alter table tlayout_data add column id_layout_linked_weight int(10) not null default 0;

ALTER TABLE tlayout_data ADD COLUMN show_on_top tinyint(1) default 0;

ALTER TABLE `tdashboard` ADD COLUMN `cells_slideshow` TINYINT(1) NOT NULL default 0;

COMMIT;
