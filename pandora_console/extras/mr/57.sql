START TRANSACTION;

ALTER TABLE `tplanned_downtime` ADD COLUMN `cron_interval_from` VARCHAR(100) DEFAULT '';
ALTER TABLE `tplanned_downtime` ADD COLUMN `cron_interval_to` VARCHAR(100) DEFAULT '';

SET @id_config := (SELECT id_config FROM tconfig WHERE `token` = 'metaconsole_node_id' AND `value` IS NOT NULL ORDER BY id_config DESC LIMIT 1);
DELETE FROM tconfig WHERE `token` = 'metaconsole_node_id' AND (id_config < @id_config OR `value` IS NULL);

INSERT INTO `talert_commands` (`name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES ('Send&#x20;report&#x20;by&#x20;e-mail','Internal&#x20;type','This&#x20;command&#x20;allows&#x20;you&#x20;to&#x20;send&#x20;a&#x20;report&#x20;by&#x20;email.',1,'[\"Report\",\"e-mail&#x20;address\",\"Subject\",\"Text\",\"Report&#x20;type\",\"\",\"\",\"\",\"\",\"\"]','[\"_reports_\",\"\",\"\",\"_html_editor_\",\"xml,XML;pdf,PDF;json,JSON;csv,CSV\",\"\",\"\",\"\",\"\",\"\"]');
INSERT INTO `talert_commands` (`name`, `command`, `description`, `internal`, `fields_descriptions`, `fields_values`) VALUES ('Send&#x20;report&#x20;by&#x20;e-mail&#x20;(from&#x20;template)','Internal&#x20;type','This&#x20;command&#x20;allows&#x20;you&#x20;to&#x20;send&#x20;a&#x20;report&#x20;generated&#x20;from&#x20;a&#x20;template&#x20;by&#x20;email.',1,'[\"Template\",\"Regexp&#x20;agent&#x20;filter\",\"e-mail&#x20;address\",\"Subject\",\"Text\",\"Report&#x20;type\",\"\",\"\",\"\",\"\"]','[\"_report_templates_\",\"\",\"\",\"\",\"_html_editor_\",\"xml,XML;pdf,PDF;json,JSON;csv,CSV\",\"\",\"\",\"\",\"\"]');

COMMIT;