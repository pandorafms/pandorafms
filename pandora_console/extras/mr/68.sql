START TRANSACTION;

-- Update version for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.oracle';

ALTER TABLE `tncm_agent_data`
ADD COLUMN `id_agent_data` int not null default 0 AFTER `script_type`;

ALTER TABLE `tusuario` CHANGE COLUMN `metaconsole_data_section` `metaconsole_data_section` TEXT NOT NULL DEFAULT '' ;

ALTER TABLE `tmensajes` ADD COLUMN `icon_notification` VARCHAR(250) NULL DEFAULT NULL AFTER `url`;

UPDATE `tncm_template` SET `vendors` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM vendors))), '"]'), `models` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM models))), '"]');
UPDATE `tncm_agent_data_template` SET `vendors` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM vendors))), '"]'), `models` = CONCAT('["', TRIM(BOTH '"' FROM TRIM(BOTH ']' FROM TRIM(BOTH '[' FROM models))), '"]');

COMMIT;