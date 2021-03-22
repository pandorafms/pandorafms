START TRANSACTION;


ALTER TABLE `talert_actions` ADD COLUMN `create_wu_integria` TINYINT(1) default NULL;

ALTER TABLE `treport_content` ADD COLUMN `summary` tinyint(1) DEFAULT 0;
ALTER TABLE `treport_content_template` ADD COLUMN `summary` tinyint(1) DEFAULT 0;

ALTER TABLE `tinventory_alert` ADD COLUMN `alert_groups` TEXT NOT NULL;
UPDATE `tinventory_alert` t1 INNER JOIN `tinventory_alert` t2 ON t1.id = t2.id SET t1.alert_groups = t2.id_group;

ALTER TABLE `tnotification_source` ADD COLUMN `subtype_blacklist` TEXT;

SET @plugin_name = 'Network&#x20;bandwidth&#x20;SNMP';
SET @plugin_description = 'Retrieves&#x20;amount&#x20;of&#x20;digital&#x20;information&#x20;sent&#x20;and&#x20;received&#x20;from&#x20;device&#x20;or&#x20;filtered&#x20;&#x20;interface&#x20;index&#x20;over&#x20;a&#x20;particular&#x20;time&#x20;&#40;agent/module&#x20;interval&#41;.';
SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;
INSERT IGNORE INTO `tplugin`  (`id`, `name`, `description`, `max_timeout`, `max_retries`, `execute`, `net_dst_opt`, `net_port_opt`, `user_opt`, `pass_opt`, `plugin_type`, `macros`, `parameters`) VALUES  (@plugin_id,@plugin_name,@plugin_description,300,0,'perl&#x20;/usr/share/pandora_server/util/plugin/pandora_snmp_bandwidth.pl','','','','',0,'{\"1\":{\"macro\":\"_field1_\",\"desc\":\"SNMP&#x20;Version&#40;1,2c,3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Community\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Host\",\"help\":\"\",\"value\":\"_address_\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Port\",\"help\":\"\",\"value\":\"161\",\"hide\":\"\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"Interface&#x20;Index&#x20;&#40;filter&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"6\":{\"macro\":\"_field6_\",\"desc\":\"securityName\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"7\":{\"macro\":\"_field7_\",\"desc\":\"context\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"8\":{\"macro\":\"_field8_\",\"desc\":\"securityLevel\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"9\":{\"macro\":\"_field9_\",\"desc\":\"authProtocol\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"10\":{\"macro\":\"_field10_\",\"desc\":\"authKey\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"11\":{\"macro\":\"_field11_\",\"desc\":\"privProtocol\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"12\":{\"macro\":\"_field12_\",\"desc\":\"privKey\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"13\":{\"macro\":\"_field13_\",\"desc\":\"UniqId\",\"help\":\"This&#x20;plugin&#x20;needs&#x20;to&#x20;store&#x20;information&#x20;in&#x20;temporary&#x20;directory&#x20;to&#x20;calculate&#x20;bandwidth.&#x20;Set&#x20;here&#x20;an&#x20;unique&#x20;identifier&#x20;with&#x20;no&#x20;spaces&#x20;or&#x20;symbols.\",\"value\":\"\",\"hide\":\"\"},\"14\":{\"macro\":\"_field14_\",\"desc\":\"inUsage\",\"help\":\"Retrieve&#x20;input&#x20;usage&#x20;&#40;%&#41;\",\"value\":\"\",\"hide\":\"\"},\"15\":{\"macro\":\"_field15_\",\"desc\":\"outUsage\",\"help\":\"Retrieve&#x20;output&#x20;usage&#x20;&#40;%&#41;\",\"value\":\"\",\"hide\":\"\"}}','-version&#x20;&#039;_field1_&#039;&#x20;-community&#x20;&#039;_field2_&#039;&#x20;-host&#x20;&#039;_field3_&#039;&#x20;-port&#x20;&#039;_field4_&#039;&#x20;-ifIndex&#x20;&#039;_field5_&#039;&#x20;-securityName&#x20;&#039;_field6_&#039;&#x20;-context&#x20;&#039;_field7_&#039;&#x20;-securityLevel&#x20;&#039;_field8_&#039;&#x20;-authProtocol&#x20;&#039;_field9_&#039;&#x20;-authKey&#x20;&#039;_field10_&#039;&#x20;-privProtocol&#x20;&#039;_field11_&#039;&#x20;-privKey&#x20;&#039;_field12_&#039;&#x20;-uniqid&#x20;&#039;_field13_&#039;&#x20;-inUsage&#x20;&#039;_field14_&#039;&#x20;-outUsage&#x20;&#039;_field15_&#039;');

COMMIT;
