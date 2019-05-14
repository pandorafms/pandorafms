START TRANSACTION;

ALTER TABLE `talert_commands` ADD COLUMN `fields_hidden` text;

ALTER TABLE `talert_templates` MODIFY COLUMN `type` ENUM('regex','max_min','max','min','equal','not_equal','warning','critical','onchange','unknown','always','not_normal');

DELETE FROM `tevent_response` WHERE `name` LIKE 'Create&#x20;Integria&#x20;IMS&#x20;incident&#x20;from&#x20;event';

COMMIT;
