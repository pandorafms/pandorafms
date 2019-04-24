
 START TRANSACTION;

 ALTER TABLE `talert_templates` MODIFY COLUMN `type` ENUM('regex','max_min','max','min','equal','not_equal','warning','critical','onchange','unknown','always','not_normal');

COMMIT;