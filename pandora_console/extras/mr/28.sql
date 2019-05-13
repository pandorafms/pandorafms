START TRANSACTION;

DELETE FROM `tevent_response` WHERE `name` LIKE 'Create&#x20;Integria&#x20;IMS&#x20;incident&#x20;from&#x20;event';

ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_login_user` VARCHAR(60);
ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_login_pass` VARCHAR(45);


COMMIT;
