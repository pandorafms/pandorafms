-- Insert new MSSQL APP
SET @current_app_type = 12;
SET @short_name = 'pandorafms.mssql';
SET @name = 'Microsoft&#x20;SQL&#x20;Server';
SET @section = 'app';
SET @description = 'Monitor&#x20;Microsoft&#x20;SQL&#x20;Server&#x20;databases';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_mssql');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileConf_&#039;&#x20;--target_databases&#x20;&#039;_tempfileTargetDatabases_&#039;&#x20;--target_agents&#x20;&#039;_tempfileTargetAgents_&#039;&#x20;--custom_queries&#x20;&#039;_tempfileCustomQueries_&#039;');

-- Migrate current MSSQL tasks configurations
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_dbstrings_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbstrings')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_dbuser_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbuser')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_dbpass_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbpass')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_threads_', 'custom', 1, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_engineAgent_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.engine_agent')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_prefixModuleName_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.prefix_module_name')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkUptime_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_uptime')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_queryStats_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.query_stats')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkConnections_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_connections')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_executeCustomQueries_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.execute_custom_queries')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_customQueries_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.custom_queries')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileConf_', 'custom', 'agents_group_id&#x20;=&#x20;__taskGroupID__&#x0d;&#x0a;interval&#x20;=&#x20;__taskInterval__&#x0d;&#x0a;user&#x20;=&#x20;_dbuser_&#x0d;&#x0a;password&#x20;=&#x20;_dbpass_&#x0d;&#x0a;threads&#x20;=&#x20;_threads_&#x0d;&#x0a;modules_prefix&#x20;=&#x20;_prefixModuleName_&#x0d;&#x0a;execute_custom_queries&#x20;=&#x20;_executeCustomQueries_&#x0d;&#x0a;analyze_connections&#x20;=&#x20;_checkConnections_&#x0d;&#x0a;engine_uptime&#x20;=&#x20;_checkUptime_&#x0d;&#x0a;query_stats&#x20;=&#x20;_queryStats_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileTargetDatabases_', 'custom', '_dbstrings_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileTargetAgents_', 'custom', '_engineAgent_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileCustomQueries_', 'custom', '_customQueries_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

-- Migrate current MSSQL tasks
UPDATE `trecon_task`
    SET
        `id_app` = @id_app,
        `setup_complete` = 1,
        `type` = 15
    WHERE `type` = @current_app_type
;