-- Insert new XenServer APP
SET @short_name = 'pandorafms.xenserver';
SET @name = 'XenServer';
SET @section = 'app';
SET @description = 'Monitor&#x20;hosts,&#x20;storages&#x20;and&#x20;VMs&#x20;from&#x20;a&#x20;specific&#x20;XenServer';
SET @version = '1.0';
INSERT IGNORE INTO `tdiscovery_apps` (`id_app`, `short_name`, `name`, `section`, `description`, `version`) VALUES ('', @short_name, @name, @section, @description, @version);
SELECT @id_app := `id_app` FROM `tdiscovery_apps` WHERE `short_name` = @short_name;

-- Insert into tdiscovery_apps_scripts
INSERT IGNORE INTO `tdiscovery_apps_scripts` (`id_app`, `macro`, `value`) VALUES (@id_app, '_exec1_', 'bin/pandora_xenserver');

-- Insert into tdiscovery_apps_executions
INSERT IGNORE INTO `tdiscovery_apps_executions` (`id`, `id_app`, `execution`) VALUES (1, @id_app, '&#039;_exec1_&#039;&#x20;-c&#x20;&#039;_tempfileXenServer_&#039;&#x20;--as_discovery_plugin&#x20;1');