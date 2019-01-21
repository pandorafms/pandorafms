START TRANSACTION;

ALTER TABLE `tagent_custom_fields_filter` ADD COLUMN `group_search` int(10) unsigned default '0';

ALTER TABLE `tagent_custom_fields_filter` ADD COLUMN `module_status` varchar(600) default '';

ALTER TABLE `tagent_custom_fields_filter` ADD COLUMN `recursion` int(1) unsigned default '0';

ALTER TABLE `tevent_rule` ADD COLUMN `group_recursion` INT(1) unsigned default 0;

COMMIT;