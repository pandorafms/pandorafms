-- Insert new VMware APP
SET @current_app_type = 3;
SET @short_name = 'pandorafms.vmware';
SET @name = 'VMware';
SET @section = 'app';
SET @description = 'Monitor&#x20;ESXi&#x20;hosts,&#x20;datastores&#x20;and&#x20;VMs&#x20;from&#x20;a&#x20;specific&#x20;datacenter';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_vmware');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec2_', 'bin/vmware_instances');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;&#039;_tempfileVMware_&#039;&#x20;--as_discovery_plugin&#x20;1');

-- Migrate current VMware tasks configurations
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tentacleIP_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(`field2`, '$.tentacle_ip')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tentaclePort_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(`field2`, '$.tentacle_port')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tentacleExtraOpt_', 'custom', JSON_UNQUOTE(JSON_EXTRACT(`field2`, '$.tentacle_opts')), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 2;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_server_', 'custom', SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 8), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 3;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_datacenter_', 'custom', SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 12), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 4;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_user_', 'custom', SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 6;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_useEncryptedPassword_', 'custom', SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 24), 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 7;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        '_pass_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        '_threads_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        '_reconInterval_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        '_virtualNetworkMonitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        '_retrySend_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        '_eventMode_',
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        '_extraSettings_',
        NULL
    )
    )
    )
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 9),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 16),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        "1",
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        SUBSTRING(SUBSTRING_INDEX(REPLACE(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position - 1), ""), "Reject", 1), 22),
        "0"
    )
    )
    )
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 8;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        '_pass_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        '_threads_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        '_reconInterval_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        '_virtualNetworkMonitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        '_retrySend_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        '_eventMode_',
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        '_extraSettings_',
        NULL
    )
    )
    )
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 9),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 16),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        "1",
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        SUBSTRING(SUBSTRING_INDEX(REPLACE(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position - 1), ""), "Reject", 1), 22),
        "0"
    )
    )
    )
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 9;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        '_pass_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        '_threads_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        '_reconInterval_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        '_virtualNetworkMonitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        '_retrySend_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        '_eventMode_',
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        '_extraSettings_',
        NULL
    )
    )
    )
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 9),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 16),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        "1",
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        SUBSTRING(SUBSTRING_INDEX(REPLACE(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position - 1), ""), "Reject", 1), 22),
        "0"
    )
    )
    )
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 10;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        '_pass_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        '_threads_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        '_reconInterval_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        '_virtualNetworkMonitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        '_retrySend_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        '_eventMode_',
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        '_extraSettings_',
        NULL
    )
    )
    )
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 9),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 16),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        "1",
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        SUBSTRING(SUBSTRING_INDEX(REPLACE(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position - 1), ""), "Reject", 1), 22),
        "0"
    )
    )
    )
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 11;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        '_pass_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        '_threads_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        '_reconInterval_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        '_virtualNetworkMonitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        '_retrySend_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        '_eventMode_',
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        '_extraSettings_',
        NULL
    )
    )
    )
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 9),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 16),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        "1",
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        SUBSTRING(SUBSTRING_INDEX(REPLACE(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position - 1), ""), "Reject", 1), 22),
        "0"
    )
    )
    )
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 12;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        '_pass_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        '_threads_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        '_reconInterval_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        '_virtualNetworkMonitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        '_retrySend_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        '_eventMode_',
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        '_extraSettings_',
        NULL
    )
    )
    )
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 9),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 16),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        "1",
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        SUBSTRING(SUBSTRING_INDEX(REPLACE(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position - 1), ""), "Reject", 1), 22),
        "0"
    )
    )
    )
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 13;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        '_pass_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        '_threads_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        '_reconInterval_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        '_virtualNetworkMonitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        '_retrySend_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        '_eventMode_',
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        '_extraSettings_',
        NULL
    )
    )
    )
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 9),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 16),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        "1",
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        SUBSTRING(SUBSTRING_INDEX(REPLACE(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position - 1), ""), "Reject", 1), 22),
        "0"
    )
    )
    )
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 14;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        '_pass_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        '_threads_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        '_reconInterval_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        '_virtualNetworkMonitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        '_retrySend_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        '_eventMode_',
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        '_extraSettings_',
        NULL
    )
    )
    )
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 9),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 16),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        "1",
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        SUBSTRING(SUBSTRING_INDEX(REPLACE(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position - 1), ""), "Reject", 1), 22),
        "0"
    )
    )
    )
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 15;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        '_pass_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        '_threads_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        '_reconInterval_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        '_virtualNetworkMonitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        '_retrySend_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        '_eventMode_',
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        '_extraSettings_',
        NULL
    )
    )
    )
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "pass",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 6),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "threads",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 9),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "recon_interval",
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), 16),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "virtual_network_monitoring",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "retry_send",
        "1",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1) = "event_mode",
        "1",
    IF(
        SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position), "\n", -1), " ", 1), 1, 20) = "#__EXTRA__SETTINGS__",
        SUBSTRING(SUBSTRING_INDEX(REPLACE(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "\n", @position - 1), ""), "Reject", 1), 22),
        "0"
    )
    )
    )
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 2;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datastore",
        '_scanDatastore_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datacenter",
        '_scanDatacenter_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_esx",
        '_scanESX_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_vm",
        '_scanVM_',
        NULL
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datastore",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datacenter",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_esx",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_vm",
        "0",
        ""
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 3;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datastore",
        '_scanDatastore_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datacenter",
        '_scanDatacenter_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_esx",
        '_scanESX_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_vm",
        '_scanVM_',
        NULL
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datastore",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datacenter",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_esx",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_vm",
        "0",
        ""
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 4;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datastore",
        '_scanDatastore_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datacenter",
        '_scanDatacenter_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_esx",
        '_scanESX_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_vm",
        '_scanVM_',
        NULL
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datastore",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datacenter",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_esx",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_vm",
        "0",
        ""
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = 5;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datastore",
        '_scanDatastore_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datacenter",
        '_scanDatacenter_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_esx",
        '_scanESX_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_vm",
        '_scanVM_',
        NULL
    )
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datastore",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_datacenter",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_esx",
        "0",
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(`field1`) USING UTF8MB4), "Reject", -1), "\n", @position), "\n", -1) = "all_vm",
        "0",
        ""
    )
    )
    )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_threads_', 'custom', 5, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_virtualNetworkMonitoring_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_retrySend_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_eventMode_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_extraSettings_', 'custom', '', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_scanDatastore_', 'custom', 1, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_scanDatacenter_', 'custom', 1, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_scanESX_', 'custom', 1, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_scanVM_', 'custom', 1, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_monitorExclusiveAgents_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_exclusiveAgents_', 'custom', '[]', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_exclusiveESXi_', 'custom', '[]', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_exclusiveDatastores_', 'custom', '[]', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_exclusiveVMs_', 'custom', '[]', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileVMware_', 'custom', 'Configuration&#x0d;&#x0a;server _server_&#x0d;&#x0a;datacenter _datacenter_&#x0d;&#x0a;user _user_&#x0d;&#x0a;group __taskGroup__&#x0d;&#x0a;use_encrypted_password _useEncryptedPassword_&#x0d;&#x0a;interval __taskInterval__&#x0d;&#x0a;pass _pass_&#x0d;&#x0a;threads _threads_&#x0d;&#x0a;event_mode _eventMode_&#x0d;&#x0a;retry_send _retrySend_&#x0d;&#x0a;virtual_network_monitoring _virtualNetworkMonitoring_&#x0d;&#x0a;recon_interval _reconInterval_&#x0d;&#x0a;&#x0d;&#x0a;scan_datastore _scanDatastore_&#x0d;&#x0a;scan_datacenter _scanDatacenter_&#x0d;&#x0a;scan_esx _scanESX_&#x0d;&#x0a;scan_vm _scanVM_&#x0d;&#x0a;&#x0d;&#x0a;logfile __temp__/tmp_discovery.__taskMD5__.log&#x0d;&#x0a;entities_list __temp__/tmp_discovery.__taskMD5__.entities&#x0d;&#x0a;event_pointer_file __temp__/tmp_discovery.__taskMD5__.events&#x0d;&#x0a;temporal __temp__&#x0d;&#x0a;transfer_mode tentacle&#x0d;&#x0a;tentacle_ip _tentacleIP_&#x0d;&#x0a;tentacle_port _tentaclePort_&#x0d;&#x0a;tentacle_opts _tentacleExtraOpt_&#x0d;&#x0a;local_folder __incomingDir__&#x0d;&#x0a;pandora_url __consoleAPIURL__&#x0d;&#x0a;api_pass __consoleAPIPass__&#x0d;&#x0a;api_user __consoleUser__&#x0d;&#x0a;apiuser_pass __consolePass__&#x0d;&#x0a;&#x0d;&#x0a;_extraSettings_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

-- Delete NULL macros
DELETE FROM `tdiscovery_apps_tasks_macros` WHERE `macro` = '';

-- Migrate current VMware tasks
UPDATE `trecon_task`
    SET
        `id_app` = @id_app,
        `setup_complete` = 1,
        `type` = 15
    WHERE `type` = @current_app_type
;