START TRANSACTION;

SET @widget_id = NULL;
SELECT @widget_id := `id` FROM `twidget` WHERE `unique_name` = 'GisMap';
INSERT IGNORE INTO `twidget` (`id`,`class_name`,`unique_name`,`description`,`options`,`page`) VALUES (@widget_id,'GisMap','GisMap','Gis map','','GisMap.php');

COMMIT;