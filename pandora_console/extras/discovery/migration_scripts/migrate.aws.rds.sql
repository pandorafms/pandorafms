-- Insert new RDS APP
SET @current_app_type = 7;
SET @short_name = 'pandorafms.aws.rds';
SET @name = 'Amazon&#x20;RDS';
SET @section = 'cloud';
SET @description = 'Monitor&#x20;AWS&#x20;RDS&#x20;instances';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_aws_rds');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec2_', 'bin/aws_rds');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileRDS_&#039;');

-- Insert new MySQL APP
SET @short_name = 'pandorafms.mysql';
SET @name = 'MySQL';
SET @section = 'app';
SET @description = 'Monitor&#x20;MySQL&#x20;databases';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app_mysql := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_mysql');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES ('', @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileConf_&#039;&#x20;--target_databases&#x20;&#039;_tempfileTargetDatabases_&#039;&#x20;--target_agents&#x20;&#039;_tempfileTargetAgents_&#039;&#x20;--custom_queries&#x20;&#039;_tempfileCustomQueries_&#039;');

-- Insert new Oracle APP
SET @short_name = 'pandorafms.oracle';
SET @name = 'Oracle';
SET @section = 'app';
SET @description = 'Monitor&#x20;Oracle&#x20;databases';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app_oracle := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_oracle');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES ('', @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileConf_&#039;&#x20;--target_databases&#x20;&#039;_tempfileTargetDatabases_&#039;&#x20;--target_agents&#x20;&#039;_tempfileTargetAgents_&#039;&#x20;--custom_queries&#x20;&#039;_tempfileCustomQueries_&#039;');

-- Migrate current RDS tasks configurations
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tentacleIP_', 'custom', '127.0.0.1', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tentaclePort_', 'custom', '41121', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tentacleExtraOpt_', 'custom', '', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_credentials_', 'credentials.aws', `auth_strings`, 0
    FROM `trecon_task`
    WHERE `trecon_task`.`type` = @current_app_type
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
    `id_rt`, '_useProxy_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_proxyUrl_', 'custom', '', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_sslCheck_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_rdsInstance_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbtargets')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_rdsZones_', 'custom', '[]', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_rdsZonesInstance_', 'custom', '[]', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_rdsInstanceSummary_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_rdsCpuPerfSummary_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_rdsIopsPerfSummary_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_rdsDiskPerfSummary_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_rdsNetworkPerfSummary_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileRDS_', 'custom', 'interval=__taskInterval__&#x0d;&#x0a;agents_group_name=__taskGroup__&#x0d;&#x0a;advance_monitoring=_rdsInstanceSummary_&#x0d;&#x0a;cpu_summary=_rdsCpuPerfSummary_&#x0d;&#x0a;iops_summary=_rdsIopsPerfSummary_&#x0d;&#x0a;disk_summary=_rdsDiskPerfSummary_&#x0d;&#x0a;network_summary=_rdsNetworkPerfSummary_&#x0d;&#x0a;aws_instances=_rdsInstance_&#x0d;&#x0a;aws_regions=_rdsZones_&#x0d;&#x0a;creds_b64=_credentials_&#x0d;&#x0a;temporal=__temp__&#x0d;&#x0a;transfer_mode=tentacle&#x0d;&#x0a;tentacle_ip=_tentacleIP_&#x0d;&#x0a;tentacle_port=_tentaclePort_&#x0d;&#x0a;tentacle_opts=_tentacleExtraOpt_&#x0d;&#x0a;threads=_threads_&#x0d;&#x0a;stats_agent=_statsAgent_&#x0d;&#x0a;stats_agent_name=_statsAgentName_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_statsAgent_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_statsAgentName_', 'custom', '', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

-- Migrate current RDS tasks to MySQL tasks
INSERT IGNORE INTO `trecon_task`
    (
        `id_rt`,
        `name`,
        `description`,
        `id_group`,
        `utimestamp`,
        `status`,
        `interval_sweep`,
        `id_recon_server`,
        `disabled`,
        `summary`,
        `type`,
        `id_app`,
        `setup_complete`,
        `field1`
    )
    SELECT
        '',
        CONCAT('MySQL&#x20;-&#x20;', `name`),
        CONCAT('Migrated&#x20;from&#x20;-&#x20;', `name`),
        `id_group`,
        `utimestamp`,
        `status`,
        `interval_sweep`,
        `id_recon_server`,
        `disabled`,
        `summary`,
        `type`,
        @id_app_mysql,
        0,
        `field1`
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbengine')) = 'mysql'
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_dbstrings_', 'custom', REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbtargets')),'","',','),'["',''),'"]',''),'[',''),']',''), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_dbuser_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbuser')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_dbpass_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbpass')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_threads_', 'custom', '1', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_engineAgent_', 'custom', '', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_prefixModuleName_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.prefix_module_name')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_scanDatabases_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.scan_databases')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_agentPerDatabase_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.agent_per_database')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_prefixAgent_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.prefix_agent')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkUptime_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_uptime')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_queryStats_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.query_stats')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkConnections_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_connections')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkInnodb_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_innodb')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkCache_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_cache')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_executeCustomQueries_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.execute_custom_queries')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_customQueries_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.custom_queries')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileConf_', 'custom', 'agents_group_id&#x20;=&#x20;__taskGroupID__&#x0d;&#x0a;interval&#x20;=&#x20;__taskInterval__&#x0d;&#x0a;user&#x20;=&#x20;_dbuser_&#x0d;&#x0a;password&#x20;=&#x20;_dbpass_&#x0d;&#x0a;threads&#x20;=&#x20;_threads_&#x0d;&#x0a;modules_prefix&#x20;=&#x20;_prefixModuleName_&#x0d;&#x0a;execute_custom_queries&#x20;=&#x20;_executeCustomQueries_&#x0d;&#x0a;analyze_connections&#x20;=&#x20;_checkConnections_&#x0d;&#x0a;scan_databases&#x20;=&#x20;_scanDatabases_&#x0d;&#x0a;agent_per_database&#x20;=&#x20;_agentPerDatabase_&#x0d;&#x0a;db_agent_prefix&#x20;=&#x20;_prefixAgent_&#x0d;&#x0a;innodb_stats&#x20;=&#x20;_checkInnodb_&#x0d;&#x0a;engine_uptime&#x20;=&#x20;_checkUptime_&#x0d;&#x0a;query_stats&#x20;=&#x20;_queryStats_&#x0d;&#x0a;cache_stats&#x20;=&#x20;_checkCache_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileTargetDatabases_', 'custom', '_dbstrings_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileTargetAgents_', 'custom', '_engineAgent_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileCustomQueries_', 'custom', '_customQueries_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

-- Migrate current RDS tasks to Oracle tasks
INSERT IGNORE INTO `trecon_task`
    (
        `id_rt`,
        `name`,
        `description`,
        `id_group`,
        `utimestamp`,
        `status`,
        `interval_sweep`,
        `id_recon_server`,
        `disabled`,
        `summary`,
        `type`,
        `id_app`,
        `setup_complete`,
        `field1`
    )
    SELECT
        '',
        CONCAT('Oracle&#x20;-&#x20;', `name`),
        CONCAT('Migrated&#x20;from&#x20;-&#x20;', `name`),
        `id_group`,
        `utimestamp`,
        `status`,
        `interval_sweep`,
        `id_recon_server`,
        `disabled`,
        `summary`,
        `type`,
        @id_app_oracle,
        0,
        `field1`
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbengine')) = 'oracle'
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_dbstrings_', 'custom', REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbtargets')),'","',','),'["',''),'"]',''),'[',''),']',''), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_dbuser_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbuser')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_dbpass_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.dbpass')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_threads_', 'custom', '1', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_engineAgent_', 'custom', '', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_prefixModuleName_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.prefix_module_name')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkUptime_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_uptime')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_queryStats_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.query_stats')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkConnections_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_connections')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkFragmentation_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_fragmentation')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkTablespaces_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_tablespaces')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_checkCache_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.check_cache')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_executeCustomQueries_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.execute_custom_queries')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_customQueries_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), '$.custom_queries')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileConf_', 'custom', 'agents_group_id&#x20;=&#x20;__taskGroupID__&#x0d;&#x0a;interval&#x20;=&#x20;__taskInterval__&#x0d;&#x0a;user&#x20;=&#x20;_dbuser_&#x0d;&#x0a;password&#x20;=&#x20;_dbpass_&#x0d;&#x0a;threads&#x20;=&#x20;_threads_&#x0d;&#x0a;modules_prefix&#x20;=&#x20;_prefixModuleName_&#x0d;&#x0a;execute_custom_queries&#x20;=&#x20;_executeCustomQueries_&#x0d;&#x0a;analyze_connections&#x20;=&#x20;_checkConnections_&#x0d;&#x0a;engine_uptime&#x20;=&#x20;_checkUptime_&#x0d;&#x0a;query_stats&#x20;=&#x20;_queryStats_&#x0d;&#x0a;cache_stats&#x20;=&#x20;_checkCache_&#x0d;&#x0a;fragmentation_ratio&#x20;=&#x20;_checkFragmentation_&#x0d;&#x0a;check_tablescpaces&#x20;=&#x20;_checkTablespaces_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileTargetDatabases_', 'custom', '_dbstrings_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileTargetAgents_', 'custom', '_engineAgent_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileCustomQueries_', 'custom', '_customQueries_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

-- Migrate current RDS tasks
UPDATE `trecon_task`
    SET
        `setup_complete` = 1,
        `type` = 15
    WHERE `type` = @current_app_type AND `id_app` = @id_app_mysql
;

UPDATE `trecon_task`
    SET
        `setup_complete` = 1,
        `type` = 15
    WHERE `type` = @current_app_type AND `id_app` = @id_app_oracle
;

UPDATE `trecon_task`
    SET
        `id_app` = @id_app,
        `setup_complete` = 1,
        `type` = 15
    WHERE `type` = @current_app_type AND `id_app` IS NULL
;