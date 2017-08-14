START TRANSACTION;

SET @st_oum707 = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'tgraph_source' AND table_schema = DATABASE() AND column_name = 'id_server') > 0,
    "SELECT 1",
    "ALTER TABLE tgraph_source ADD COLUMN id_server int(11) UNSIGNED NOT NULL default 0"
));

PREPARE pr_oum707 FROM @st_oum707;
EXECUTE pr_oum707;
DEALLOCATE PREPARE pr_oum707;

ALTER TABLE tserver_export_data MODIFY `module_name` varchar(600) BINARY NOT NULL default '';

COMMIT;