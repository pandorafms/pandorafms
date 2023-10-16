-- Insert new S3 APP
SET @current_app_type = 14;
SET @short_name = 'pandorafms.aws.s3';
SET @name = 'Amazon&#x20;S3';
SET @section = 'cloud';
SET @description = 'Monitor&#x20;AWS&#x20;S3&#x20;buckets';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_aws_s3');
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec2_', 'bin/aws_s3');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;--conf&#x20;&#039;_tempfileS3_&#039;');

-- Migrate current S3 tasks configurations
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

SET @position = 3;
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_monitoring',
        '_s3Monitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_size',
        '_s3Size_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_items',
        '_s3Items_',
        NULL
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_monitoring',
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", -1),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_size',
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", -1),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_items',
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", -1),
        "0"
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
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_monitoring',
        '_s3Monitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_size',
        '_s3Size_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_items',
        '_s3Items_',
        NULL
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_monitoring',
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", -1),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_size',
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", -1),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_items',
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", -1),
        "0"
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
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_monitoring',
        '_s3Monitoring_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_size',
        '_s3Size_',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_items',
        '_s3Items_',
        NULL
    )
    )
    ),
    'custom',
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_monitoring',
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", -1),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_size',
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", -1),
    IF(
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", 1) = 's3_items',
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(
            CONCAT(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",1),REPLACE(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",1),REPLACE(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(CONVERT(FROM_BASE64(field1) USING UTF8MB4),"s3_zone ",-1),"\n",1),"\n"),"")),"s3_bucket ",-1),"\n",1),"\n"),""))
        , "\n", @position), "\n", -1), " ", -1),
        "0"
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

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_s3Monitoring_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_s3Size_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_s3Items_', 'custom', 0, 0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @param = 's3_bucket';
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    '_s3Bucket_',
    'custom', 
    REPLACE(
        REPLACE(
            CONCAT(
                '["',
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                SUBSTRING_INDEX(
                                    CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                    CONCAT(@param, " "), 1
                                ),
                                ""
                            ),
                            REPLACE(
                                SUBSTRING_INDEX(
                                    REPLACE(
                                        CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                        SUBSTRING_INDEX(
                                            CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                            CONCAT(@param, " "), 1
                                        ),
                                        ""
                                    ),
                                    CONCAT(@param, " "), -1
                                ),
                                SUBSTRING_INDEX(SUBSTRING_INDEX(
                                    REPLACE(
                                        CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                        SUBSTRING_INDEX(
                                            CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                            CONCAT(@param, " "), 1
                                        ),
                                        ""
                                    ),
                                    CONCAT(@param, " "), -1
                                ), "\n", 1),
                                ""
                            ),
                            "\n"
                        ),
                        CONCAT(@param, " "),
                        ""
                    ),
                    "\n",
                    '","'
                ),
                '"]'
            ),
            ',""]',
            ']'
        ),
        '[""]',
        '[]'
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

SET @param = 's3_zone';
INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`,
    '_s3Zone_',
    'custom', 
    REPLACE(
        REPLACE(
            CONCAT(
                '["',
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                SUBSTRING_INDEX(
                                    CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                    CONCAT(@param, " "), 1
                                ),
                                ""
                            ),
                            REPLACE(
                                SUBSTRING_INDEX(
                                    REPLACE(
                                        CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                        SUBSTRING_INDEX(
                                            CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                            CONCAT(@param, " "), 1
                                        ),
                                        ""
                                    ),
                                    CONCAT(@param, " "), -1
                                ),
                                SUBSTRING_INDEX(SUBSTRING_INDEX(
                                    REPLACE(
                                        CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                        SUBSTRING_INDEX(
                                            CONVERT(FROM_BASE64(`field1`) USING UTF8MB4),
                                            CONCAT(@param, " "), 1
                                        ),
                                        ""
                                    ),
                                    CONCAT(@param, " "), -1
                                ), "\n", 1),
                                ""
                            ),
                            "\n"
                        ),
                        CONCAT(@param, " "),
                        ""
                    ),
                    "\n",
                    '","'
                ),
                '"]'
            ),
            ',""]',
            ']'
        ),
        '[""]',
        '[]'
    ),
    0
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
    SELECT
    `id_rt`, '_tempfileS3_', 'custom', 'interval=__taskInterval__&#x0d;&#x0a;agents_group_name=__taskGroup__&#x0d;&#x0a;aws_regions=_s3Zone_&#x0d;&#x0a;aws_buckets=_s3Bucket_&#x0d;&#x0a;s3_monitoring=_s3Monitoring_&#x0d;&#x0a;size_monitoring=_s3Size_&#x0d;&#x0a;items_monitoring=_s3Items_&#x0d;&#x0a;creds_b64=_credentials_&#x0d;&#x0a;temporal=__temp__&#x0d;&#x0a;transfer_mode=tentacle&#x0d;&#x0a;tentacle_ip=_tentacleIP_&#x0d;&#x0a;tentacle_port=_tentaclePort_&#x0d;&#x0a;tentacle_opts=_tentacleExtraOpt_&#x0d;&#x0a;threads=_threads_&#x0d;&#x0a;stats_agent=_statsAgent_&#x0d;&#x0a;stats_agent_name=_statsAgentName_', 1
    FROM `trecon_task`
    WHERE `type` = @current_app_type
;

-- Delete NULL macros
DELETE FROM `tdiscovery_apps_tasks_macros` WHERE `macro` = '';

-- Migrate current S3 tasks
UPDATE `trecon_task`
    SET
        `id_app` = @id_app,
        `setup_complete` = 1,
        `type` = 15
    WHERE `type` = @current_app_type
;