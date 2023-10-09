-- Insert new SAP APP
SET @current_app_type = 10;
SET @short_name = 'pandorafms.sap.deset';
SET @name = 'SAP&#x20;R3&#x20;-&#x20;Deset';
SET @section = 'app';
SET @description = 'Monitor&#x20;SAP&#x20;R3&#x20;environments';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_sap_deset');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_java_', 'bin/lib/jre/bin/java');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileConf_&#039;&#x20;--custom_modules&#x20;&#039;_tempfileCustomModules_&#039;');

-- Migrate current SAP tasks configurations
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_server_', 'custom', `subnet`, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_sapClient_', 'custom', `field3`, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_sapSystem_', 'custom', `field2`, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_sapLicense_', 'custom', `field4`, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_credentials_', 'credentials.sap', `auth_strings`, 0
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
    `id_rt`, '_customModules_', 'custom', '[]', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_targetAgent_', 'custom', '', 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    '_customModulesDefinition_',
    'custom',
    JSON_UNQUOTE(
      JSON_EXTRACT(
        REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            REPLACE(
            CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
            '"160"',
            '""'
            ),
            '"109"',
            '""'
            ),
            '"111"',
            '""'
            ),
            '"113"',
            '""'
            ),
            '"121"',
            '""'
            ),
            '"104"',
            '""'
            ),
            '"105"',
            '""'
            ),
            '"150"',
            '""'
            ),
            '"151"',
            '""'
            ),
            '"102"',
            '""'
            ),
            '"103"',
            '""'
            ),
            '"192"',
            '""'
            ),
            '"195"',
            '""'
            ),
            '"116"',
            '""'
            ),
            '","',
            ""
        ),
        '$[0]'
      )
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @position = -1;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -2;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -3;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -4;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -5;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -6;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -7;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -8;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -9;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -10;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -11;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -12;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -13;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

SET @position = -14;
UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task` AS `main_trecon_task`
    SET `tdiscovery_apps_tasks_macros`.`value` = JSON_MERGE(
        `value`,
        (
            SELECT
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '160',
                    '"160"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '109',
                    '"109"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '111',
                    '"111"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '113',
                    '"113"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '121',
                    '"121"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '104',
                    '"104"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '105',
                    '"105"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '150',
                    '"150"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '151',
                    '"151"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '102',
                    '"102"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '103',
                    '"103"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '192',
                    '"192"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '195',
                    '"195"',
                IF(
                    SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(SUBSTRING(SUBSTRING(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4), 1, LENGTH(CONVERT(FROM_BASE64(`trecon_task`.`field1`) USING UTF8MB4))-2), 3), '","', "\n"), "\n", @position), "\n", 1) = '116',
                    '"116"',
                    '""'
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
                )
            FROM `trecon_task`
            WHERE
                `trecon_task`.`type` = @current_app_type AND
                `trecon_task`.`id_rt` = `main_trecon_task`.`id_rt`
        )
    )
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `main_trecon_task`.`id_rt` AND
    `main_trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task`
    SET
    `tdiscovery_apps_tasks_macros`.`value` = REPLACE(REPLACE(REPLACE(`value`, '"", ', ''), '""', ''), ', ]', ']')
    WHERE 
    `tdiscovery_apps_tasks_macros`.`id_task` = `trecon_task`.`id_rt` AND
    `trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_customModules_'
;

UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task`
    SET
    `tdiscovery_apps_tasks_macros`.`value` = (
        SELECT `tconfig`.`value` FROM `tconfig` WHERE `tconfig`.`token` = 'sap_license'
    )
    WHERE
    `tdiscovery_apps_tasks_macros`.`id_task` = `trecon_task`.`id_rt` AND
    `trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_sapLicense_' AND
    `tdiscovery_apps_tasks_macros`.`value` = ''
;

UPDATE `tdiscovery_apps_tasks_macros`, `trecon_task`
    SET
    `tdiscovery_apps_tasks_macros`.`value` = 'trial'
    WHERE
    `tdiscovery_apps_tasks_macros`.`id_task` = `trecon_task`.`id_rt` AND
    `trecon_task`.`type` = @current_app_type AND
    `tdiscovery_apps_tasks_macros`.`macro` = '_sapLicense_' AND
    `tdiscovery_apps_tasks_macros`.`value` = ''
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileConf_', 'custom', 'agents_group_id&#x20;=&#x20;__taskGroupID__&#x0d;&#x0a;threads&#x20;=&#x20;_threads_&#x0d;&#x0a;interval&#x20;=&#x20;__taskInterval__&#x0d;&#x0a;server&#x20;=&#x20;_server_&#x0d;&#x0a;client&#x20;=&#x20;_sapClient_&#x0d;&#x0a;system&#x20;=&#x20;_sapSystem_&#x0d;&#x0a;license&#x20;=&#x20;_sapLicense_&#x0d;&#x0a;credentials&#x20;=&#x20;_credentials_&#x0d;&#x0a;agent&#x20;=&#x20;_targetAgent_&#x0d;&#x0a;modules&#x20;=&#x20;_customModules_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileCustomModules_', 'custom', '_customModulesDefinition_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

-- Migrate current SAP tasks
UPDATE `trecon_task`
    SET
        `id_app` = @id_app,
        `setup_complete` = 1,
        `type` = 15
    WHERE `type` = @current_app_type
;