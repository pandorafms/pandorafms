START TRANSACTION;

ALTER TABLE `tmap` ADD COLUMN `id_group_map` INT(10) UNSIGNED NOT NULL default 0;
ALTER TABLE `tevent_filter` MODIFY `severity` TEXT NOT NULL;
ALTER TABLE `treport_content_item` ADD `id_agent_module_failover` int(10) unsigned NOT NULL DEFAULT 0;

COMMIT;
