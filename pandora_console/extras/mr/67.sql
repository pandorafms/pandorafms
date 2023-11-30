START TRANSACTION;

ALTER TABLE `tevento`
ADD COLUMN `event_custom_id` TEXT NULL AFTER `module_status`;

-- Telegram and vonage default alerts
UPDATE talert_actions
	SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_'
	WHERE id=9;
UPDATE talert_actions
	SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_'
	WHERE id=11;
-- Delete table tagent_access
DROP TABLE tagent_access;

ALTER TABLE treport_content ADD check_unknowns_graph tinyint DEFAULT 0 NULL;

-- Update macros for plugin oracle

UPDATE `tdiscovery_apps` SET `version` = '1.1' WHERE `short_name` = 'pandorafms.oracle';

SET @id_app := (SELECT `id_app` FROM `tdiscovery_apps` WHERE `short_name` = 'pandorafms.oracle');

UPDATE `tdiscovery_apps_tasks_macros` SET `value` = 'agents_group_id=__taskGroupID__ interval=__taskInterval__ user=_dbuser_ password=_dbpass_ thick_mode=_thickMode_ client_path=_clientPath_ threads=_threads_ modules_prefix=_prefixModuleName_ execute_custom_queries=_executeCustomQueries_ analyze_connections=_checkConnections_ engine_uptime=_checkUptime_ query_stats=_queryStats_ cache_stats=_checkCache_ fragmentation_ratio=_checkFragmentation_ check_tablescpaces=_checkTablespaces_' WHERE `macro` = '_tempfileConf_' AND `id_task` IN (SELECT `id_rt` FROM `trecon_task` WHERE `id_app` = @id_app);

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros` (`id_task`, `macro`, `type`, `value`, `temp_conf`) SELECT id_rt, '_thickMode_', 'custom', 0, 0 FROM `trecon_task` WHERE `id_app` = @id_app;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros` (`id_task`, `macro`, `type`, `value`, `temp_conf`) SELECT id_rt, '_clientPath_', 'custom', '', 0 FROM `trecon_task` WHERE `id_app` = @id_app;
UPDATE `trecon_task` SET `setup_complete` = 1 WHERE `id_app` = @id_app;


COMMIT;
