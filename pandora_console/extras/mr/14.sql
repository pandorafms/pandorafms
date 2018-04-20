START TRANSACTION;

UPDATE `tagente` SET `id_os` = 100 WHERE `id_os` = 21 and (select `id_os` from `tconfig_os` WHERE `id_os` = 21 and `name` = 'Cluster');

DELETE FROM `tconfig_os` where `id_os` = 21 and `name` = 'Cluster';

SET @st_oum721 = (SELECT IF(
	(SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tuser_task_scheduled') > 0, 
	"ALTER TABLE tuser_task_scheduled ADD (id_grupo int(10) unsigned NOT NULL Default 0)", 
	"0"
));

PREPARE pr_oum721 FROM @st_oum721;
EXECUTE pr_oum721;
DEALLOCATE PREPARE pr_oum721;

COMMIT;