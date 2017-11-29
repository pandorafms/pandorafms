START TRANSACTION;
ALTER TABLE tusuario ADD COLUMN `time_autorefresh` int(5) unsigned NOT NULL default '30';
ALTER TABLE treport_content ADD COLUMN lapse_calc tinyint(1) default '0';
ALTER TABLE treport_content ADD COLUMN lapse int(11) default '300';
ALTER TABLE treport_content ADD COLUMN visual_format tinyint(1) default '0';
ALTER TABLE treport_content_template ADD COLUMN lapse_calc tinyint(1) default '0';
ALTER TABLE treport_content_template ADD COLUMN lapse int(11) default '300';
ALTER TABLE treport_content_template ADD COLUMN visual_format tinyint(1) default '0';

UPDATE `talert_commands` 
SET `description` = 'This&#x20;alert&#x20;send&#x20;an&#x20;email&#x20;using&#x20;internal&#x20;Pandora&#x20;FMS&#x20;Server&#x20;SMTP&#x20;capabilities&#x20;&#40;defined&#x20;in&#x20;each&#x20;server,&#x20;using:&#x0d;&#x0a;_field1_&#x20;as&#x20;destination&#x20;email&#x20;address,&#x20;and&#x0d;&#x0a;_field2_&#x20;as&#x20;subject&#x20;for&#x20;message.&#x20;&#x0d;&#x0a;_field3_&#x20;as&#x20;text&#x20;of&#x20;message.&#x20;&#x0d;&#x0a;_field4_&#x20;as&#x20;content&#x20;type&#x20;&#40;text/plain&#x20;or&#x20;html/text&#41;.',
    `fields_descriptions` = '[\"Destination&#x20;address\",\"Subject\",\"Text\",\"Content&#x20;Type\",\"\",\"\",\"\",\"\",\"\",\"\"]',
    `fields_values` = '[\"\",\"\",\"_html_editor_\",\"_content_type_\",\"\",\"\",\"\",\"\",\"\",\"\"]'
WHERE id=1;

UPDATE `talert_actions`
SET `field4` = 'text/html',
    `field4_recovery` = 'text/html'
WHERE id = 1;

COMMIT;