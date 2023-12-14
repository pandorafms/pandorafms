START TRANSACTION;

ALTER TABLE `tevento`
ADD COLUMN `event_custom_id` TEXT NULL AFTER `module_status`;

SET @exist = (SELECT count(*) FROM information_schema.columns WHERE TABLE_NAME='tmetaconsole_agent' AND COLUMN_NAME='transactional_agent' AND table_schema = DATABASE());
SET @sqlstmt = IF (@exist>0, 'ALTER TABLE `tmetaconsole_agent` DROP COLUMN `transactional_agent`', 'SELECT ""');
prepare stmt from @sqlstmt;
execute stmt;

SET @exist = (SELECT count(*) FROM information_schema.columns WHERE TABLE_NAME='tagente' AND COLUMN_NAME='transactional_agent' AND table_schema = DATABASE());
SET @sqlstmt = IF (@exist>0, 'ALTER TABLE `tagente` DROP COLUMN `transactional_agent`', 'SELECT ""');
prepare stmt from @sqlstmt;
execute stmt;

ALTER TABLE `tlayout_template_data` ADD COLUMN `title` TEXT default '';
ALTER TABLE `tlayout_data` ADD COLUMN `period_chart_options` TEXT default '';
ALTER TABLE `tlayout_template_data` ADD COLUMN `period_chart_options` TEXT default '';

ALTER TABLE `tdashboard`
ADD COLUMN `date_range` TINYINT NOT NULL DEFAULT 0 AFTER `cells_slideshow`,
ADD COLUMN `date_from` INT NOT NULL DEFAULT 0 AFTER `date_range`,
ADD COLUMN `date_to` INT NOT NULL DEFAULT 0 AFTER `date_from`;

-- Delete table tagent_access
DROP TABLE IF EXISTS tagent_access;

ALTER TABLE `tevent_rule` DROP COLUMN `user_comment`;
ALTER TABLE `tevent_rule` DROP COLUMN `operator_user_comment`;

ALTER TABLE treport_content ADD check_unknowns_graph tinyint DEFAULT 0 NULL;

ALTER TABLE `tevent_filter` ADD COLUMN `regex` TEXT NULL AFTER `private_filter_user`;
-- Update macros for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.1' WHERE `short_name` = 'pandorafms.oracle';

SET @id_app := (SELECT `id_app` FROM `tdiscovery_apps` WHERE `short_name` = 'pandorafms.oracle');

UPDATE `tdiscovery_apps_tasks_macros` SET `value` = 'agents_group_id=__taskGroupID__ interval=__taskInterval__ user=_dbuser_ password=_dbpass_ thick_mode=_thickMode_ client_path=_clientPath_ threads=_threads_ modules_prefix=_prefixModuleName_ execute_custom_queries=_executeCustomQueries_ analyze_connections=_checkConnections_ engine_uptime=_checkUptime_ query_stats=_queryStats_ cache_stats=_checkCache_ fragmentation_ratio=_checkFragmentation_ check_tablescpaces=_checkTablespaces_' WHERE `macro` = '_tempfileConf_' AND `id_task` IN (SELECT `id_rt` FROM `trecon_task` WHERE `id_app` = @id_app);

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros` (`id_task`, `macro`, `type`, `value`, `temp_conf`) SELECT id_rt, '_thickMode_', 'custom', 0, 0 FROM `trecon_task` WHERE `id_app` = @id_app;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros` (`id_task`, `macro`, `type`, `value`, `temp_conf`) SELECT id_rt, '_clientPath_', 'custom', '', 0 FROM `trecon_task` WHERE `id_app` = @id_app;
UPDATE `trecon_task` SET `setup_complete` = 1 WHERE `id_app` = @id_app;

-- Update lts updates
UPDATE tconfig SET value='1' WHERE token='lts_updates';

SELECT @generic_data := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "generic_data";
SELECT @generic_proc := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "generic_proc";
SELECT @async_data := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "async_data";
SELECT @async_proc := `id_tipo` FROM `ttipo_modulo` WHERE `nombre` = "async_proc";
UPDATE `tagente_modulo` INNER JOIN `tservice` ON `tagente_modulo`.`custom_integer_1` = `tservice`.`id` SET `tagente_modulo`.`id_tipo_modulo` = @generic_data WHERE `tagente_modulo`.`id_tipo_modulo` = @async_data;
UPDATE `tagente_modulo` INNER JOIN `tservice` ON `tagente_modulo`.`custom_integer_1` = `tservice`.`id` SET `tagente_modulo`.`id_tipo_modulo` = @generic_proc WHERE `tagente_modulo`.`id_tipo_modulo` = @async_proc;

-- Telegram and vonage default alerts
UPDATE talert_actions SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_' WHERE id=9;
UPDATE talert_actions SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_' WHERE id=11;

UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.vmware';

COMMIT;
