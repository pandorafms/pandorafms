-- Insert new MySQL APP
SET @current_app_type = 4;
SET @short_name = 'pandorafms.mysql';
SET @name = 'MySQL';
SET @section = 'app';
SET @description = 'Monitor&#x20;MySQL&#x20;databases';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_mysql');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileConf_&#039;&#x20;--target_databases&#x20;&#039;_tempfileTargetDatabases_&#039;&#x20;--target_agents&#x20;&#039;_tempfileTargetAgents_&#039;&#x20;--custom_queries&#x20;&#039;_tempfileCustomQueries_&#039;');

-- Migrate current MySQL tasks configurations
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    WITH RECURSIVE `cte` AS (
        SELECT
            `id_rt`,
            REPLACE(JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbport')), '&#x20;', '') AS `port`,
            1 AS `pos`,
            SUBSTRING_INDEX(
                REPLACE(JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbhost')), '&#x20;', ''),
                ',', 1
            ) AS `host`,
            SUBSTRING(
                REPLACE(JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbhost')), '&#x20;', ''),
                LENGTH(SUBSTRING_INDEX(
                    REPLACE(JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbhost')), '&#x20;', ''),
                    ',', 1
                )) + 2
            ) AS `remaining`
        FROM `trecon_task` WHERE `type` = @current_app_type
        UNION ALL
        SELECT `id_rt`, `port`, `pos` + 1, SUBSTRING_INDEX(`remaining`, ',', 1) AS `host`, SUBSTRING(`remaining`, LENGTH(SUBSTRING_INDEX(`remaining`, ',', 1)) + 2)
        FROM `cte`
        WHERE `remaining` != ''
    )
    SELECT `id_rt`, '_dbstrings_', 'custom', GROUP_CONCAT(CONCAT(`host`, ':', `port`) SEPARATOR ','), 0
    FROM `cte`
    GROUP BY `id_rt`
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
    `id_rt`, '_scanDatabases_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.scan_databases')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_agentPerDatabase_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.agent_per_database')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_prefixAgent_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.prefix_agent')), 0
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
    `id_rt`, '_checkInnodb_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_innodb')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkCache_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_cache')), 0
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
    `id_rt`, '_tempfileConf_', 'custom', 'agents_group_id&#x20;=&#x20;__taskGroupID__&#x0d;&#x0a;interval&#x20;=&#x20;__taskInterval__&#x0d;&#x0a;user&#x20;=&#x20;_dbuser_&#x0d;&#x0a;password&#x20;=&#x20;_dbpass_&#x0d;&#x0a;threads&#x20;=&#x20;_threads_&#x0d;&#x0a;modules_prefix&#x20;=&#x20;_prefixModuleName_&#x0d;&#x0a;execute_custom_queries&#x20;=&#x20;_executeCustomQueries_&#x0d;&#x0a;analyze_connections&#x20;=&#x20;_checkConnections_&#x0d;&#x0a;scan_databases&#x20;=&#x20;_scanDatabases_&#x0d;&#x0a;agent_per_database&#x20;=&#x20;_agentPerDatabase_&#x0d;&#x0a;db_agent_prefix&#x20;=&#x20;_prefixAgent_&#x0d;&#x0a;innodb_stats&#x20;=&#x20;_checkInnodb_&#x0d;&#x0a;engine_uptime&#x20;=&#x20;_checkUptime_&#x0d;&#x0a;query_stats&#x20;=&#x20;_queryStats_&#x0d;&#x0a;cache_stats&#x20;=&#x20;_checkCache_', 1
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

-- Migrate current MySQL tasks
UPDATE `trecon_task`
    SET
        `id_app` = @id_app,
        `setup_complete` = 1,
        `type` = 15
    WHERE `type` = @current_app_type
;