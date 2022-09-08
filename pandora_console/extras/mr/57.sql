START TRANSACTION;

ALTER TABLE `tplanned_downtime` ADD COLUMN `cron_interval_from` VARCHAR(100) DEFAULT '';
ALTER TABLE `tplanned_downtime` ADD COLUMN `cron_interval_to` VARCHAR(100) DEFAULT '';

ALTER TABLE `tplugin` ADD COLUMN `no_delete` TINYINT NOT NULL DEFAULT 0;

SET @id_config := (SELECT id_config FROM tconfig WHERE `token` = 'metaconsole_node_id' AND `value` IS NOT NULL ORDER BY id_config DESC LIMIT 1);
DELETE FROM tconfig WHERE `token` = 'metaconsole_node_id' AND (id_config < @id_config OR `value` IS NULL);

UPDATE `tplugin` set `no_delete` = 1 WHERE `name` IN ('IPMI&#x20;Plugin', 'DNS&#x20;Plugin', 'UDP&#x20;port&#x20;check', 'SMTP&#x20;Check', 'MySQL&#x20;Plugin', 'SNMP&#x20;remote', 'Packet&#x20;Loss', 'Wizard&#x20;SNMP&#x20;module', 'Wizard&#x20;SNMP&#x20;process', 'Wizard&#x20;WMI&#x20;module', 'Network&#x20;bandwidth&#x20;SNMP');

COMMIT;