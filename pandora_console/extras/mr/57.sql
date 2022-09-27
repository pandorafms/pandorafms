START TRANSACTION;

ALTER TABLE `tplanned_downtime` ADD COLUMN `cron_interval_from` VARCHAR(100) DEFAULT '';
ALTER TABLE `tplanned_downtime` ADD COLUMN `cron_interval_to` VARCHAR(100) DEFAULT '';

SET @id_config := (SELECT id_config FROM tconfig WHERE `token` = 'metaconsole_node_id' AND `value` IS NOT NULL ORDER BY id_config DESC LIMIT 1);
DELETE FROM tconfig WHERE `token` = 'metaconsole_node_id' AND (id_config < @id_config OR `value` IS NULL);

ALTER TABLE `tpolicies` ADD COLUMN `apply_to_secondary_groups` TINYINT NOT NULL DEFAULT 0;

COMMIT;