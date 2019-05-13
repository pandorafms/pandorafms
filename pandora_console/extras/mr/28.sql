
 START TRANSACTION;

ALTER TABLE `talert_templates` MODIFY COLUMN `type` ENUM('regex','max_min','max','min','equal','not_equal','warning','critical','onchange','unknown','always','not_normal');

DELETE FROM `tevent_response` WHERE `name` LIKE 'Create&#x20;Integria&#x20;IMS&#x20;incident&#x20;from&#x20;event';

ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_login_user` VARCHAR(60);
ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_login_pass` VARCHAR(45);


COMMIT;
