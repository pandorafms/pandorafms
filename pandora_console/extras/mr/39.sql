START TRANSACTION;

ALTER TABLE `tnetwork_component` ADD COLUMN `manufacturer_id` varchar(200) NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `protocol` tinytext NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `module_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `tnetwork_component` ADD COLUMN `execution_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `tnetwork_component` ADD COLUMN `scan_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `tnetwork_component` ADD COLUMN `value` text NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `value_operations` text NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `module_enabled` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tnetwork_component` ADD COLUMN `name_oid` varchar(255) NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `query_class` varchar(200) NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `query_key_field` varchar(200) NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `scan_filters` text NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `query_filters` text NOT NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `enabled` tinyint(1) UNSIGNED DEFAULT 1;

INSERT INTO tmodule VALUES (9, 'Wizard&#x20;module');

SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';
SET @plugin_description = 'Get&#x20;the&#x20;result&#x20;of&#x20;an&#x20;arithmetic&#x20;operation&#x20;using&#x20;several&#x20;OIDs&#x20;values.';
SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;
INSERT IGNORE INTO `tplugin` (`id`, `name`, `description`, `max_timeout`, `max_retries`, `execute`, `net_dst_opt`, `net_port_opt`, `user_opt`, `pass_opt`, `plugin_type`, `macros`, `parameters`) VALUES (@plugin_id,@plugin_name,@plugin_description,20,0,'/usr/share/pandora_server/util/plugin/wizard_snmp_module',NULL,NULL,NULL,NULL,0,'{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Host\",\"help\":\"\",\"value\":\"_address_\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Port\",\"help\":\"\",\"value\":\"161\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Version\",\"help\":\"1,&#x20;2c,&#x20;3\",\"value\":\"1\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Community\",\"help\":\"\",\"value\":\"public\",\"hide\":\"\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"Security&#x20;level&#x20;&#40;v3&#41;\",\"help\":\"noAuthNoPriv,&#x20;authNoPriv,&#x20;authPriv\",\"value\":\"\",\"hide\":\"\"},\"6\":{\"macro\":\"_field6_\",\"desc\":\"Username&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"7\":{\"macro\":\"_field7_\",\"desc\":\"Authentication&#x20;method&#x20;&#40;v3&#41;\",\"help\":\"MD5,&#x20;SHA\",\"value\":\"\",\"hide\":\"\"},\"8\":{\"macro\":\"_field8_\",\"desc\":\"Authentication&#x20;password&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"9\":{\"macro\":\"_field9_\",\"desc\":\"Privacy&#x20;method&#x20;&#40;v3&#41;\",\"help\":\"DES,&#x20;AES\",\"value\":\"\",\"hide\":\"\"},\"10\":{\"macro\":\"_field10_\",\"desc\":\"Privacy&#x20;password&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"11\":{\"macro\":\"_field11_\",\"desc\":\"OID&#x20;list\",\"help\":\"Comma&#x20;separated&#x20;OIDs&#x20;used\",\"value\":\"\",\"hide\":\"\"},\"12\":{\"macro\":\"_field12_\",\"desc\":\"Operation\",\"help\":\"Aritmetic&#x20;operation&#x20;to&#x20;get&#x20;data.&#x20;Macros&#x20;_oN_&#x20;will&#x20;be&#x20;changed&#x20;by&#x20;OIDs&#x20;in&#x20;list.&#x20;Example:&#x20;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_\",\"value\":\"\",\"hide\":\"\"}}','-host&#x20;&#039;_field1_&#039;&#x20;-port&#x20;&#039;_field2_&#039;&#x20;-version&#x20;&#039;_field3_&#039;&#x20;-community&#x20;&#039;_field4_&#039;&#x20;-secLevel&#x20;&#039;_field5_&#039;&#x20;-user&#x20;&#039;_field6_&#039;&#x20;-authMethod&#x20;&#039;_field7_&#039;&#x20;-authPass&#x20;&#039;_field8_&#039;&#x20;-privMethod&#x20;&#039;_field9_&#039;&#x20;-privPass&#x20;&#039;_field10_&#039;&#x20;-oidList&#x20;&#039;_field11_&#039;&#x20;-operation&#x20;&#039;_field12_&#039;');

SET @plugin_name = 'Wizard&#x20;SNMP&#x20;process';
SET @plugin_description = 'Check&#x20;if&#x20;a&#x20;process&#x20;is&#x20;running&#x20;&#40;1&#41;&#x20;or&#x20;not&#x20;&#40;0&#41;&#x20;in&#x20;OID&#x20;.1.3.6.1.2.1.25.4.2.1.2&#x20;SNMP&#x20;tree.';
SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;
INSERT IGNORE INTO `tplugin` (`id`, `name`, `description`, `max_timeout`, `max_retries`, `execute`, `net_dst_opt`, `net_port_opt`, `user_opt`, `pass_opt`, `plugin_type`, `macros`, `parameters`) VALUES (@plugin_id,@plugin_name,@plugin_description,20,0,'/usr/share/pandora_server/util/plugin/wizard_snmp_process',NULL,NULL,NULL,NULL,0,'{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Host\",\"help\":\"\",\"value\":\"_address_\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Port\",\"help\":\"\",\"value\":\"161\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Version\",\"help\":\"1,&#x20;2c,&#x20;3\",\"value\":\"1\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Community\",\"help\":\"\",\"value\":\"public\",\"hide\":\"\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"Security&#x20;level&#x20;&#40;v3&#41;\",\"help\":\"noAuthNoPriv,&#x20;authNoPriv,&#x20;authPriv\",\"value\":\"\",\"hide\":\"\"},\"6\":{\"macro\":\"_field6_\",\"desc\":\"Username&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"7\":{\"macro\":\"_field7_\",\"desc\":\"Authentication&#x20;method&#x20;&#40;v3&#41;\",\"help\":\"MD5,&#x20;SHA\",\"value\":\"\",\"hide\":\"\"},\"8\":{\"macro\":\"_field8_\",\"desc\":\"Authentication&#x20;password&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"9\":{\"macro\":\"_field9_\",\"desc\":\"Privacy&#x20;method&#x20;&#40;v3&#41;\",\"help\":\"DES,&#x20;AES\",\"value\":\"\",\"hide\":\"\"},\"10\":{\"macro\":\"_field10_\",\"desc\":\"Privacy&#x20;password&#x20;&#40;v3&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"11\":{\"macro\":\"_field11_\",\"desc\":\"Process\",\"help\":\"Process&#x20;name&#x20;to&#x20;check&#x20;if&#x20;is&#x20;running&#x20;&#40;case&#x20;sensitive&#41;\",\"value\":\"\",\"hide\":\"\"}}','-host&#x20;&#039;_field1_&#039;&#x20;-port&#x20;&#039;_field2_&#039;&#x20;-version&#x20;&#039;_field3_&#039;&#x20;-community&#x20;&#039;_field4_&#039;&#x20;-secLevel&#x20;&#039;_field5_&#039;&#x20;-user&#x20;&#039;_field6_&#039;&#x20;-authMethod&#x20;&#039;_field7_&#039;&#x20;-authPass&#x20;&#039;_field8_&#039;&#x20;-privMethod&#x20;&#039;_field9_&#039;&#x20;-privPass&#x20;&#039;_field10_&#039;&#x20;-process&#x20;&#039;_field11_&#039;');

SET @plugin_name = 'Wizard&#x20;WMI&#x20;module';
SET @plugin_description = 'Get&#x20;the&#x20;result&#x20;of&#x20;an&#x20;arithmetic&#x20;operation&#x20;using&#x20;distinct&#x20;fields&#x20;in&#x20;a&#x20;WMI&#x20;query&#x20;&#40;Query&#x20;must&#x20;return&#x20;only&#x20;1&#x20;row&#41;.';
SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;
INSERT IGNORE INTO `tplugin` (`id`, `name`, `description`, `max_timeout`, `max_retries`, `execute`, `net_dst_opt`, `net_port_opt`, `user_opt`, `pass_opt`, `plugin_type`, `macros`, `parameters`) VALUES (@plugin_id,@plugin_name,@plugin_description,20,0,'/usr/share/pandora_server/util/plugin/wizard_wmi_module',NULL,NULL,NULL,NULL,0,'{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Host\",\"help\":\"\",\"value\":\"_address_\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Namespace&#x20;&#40;Optional&#41;\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"User\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Password\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"WMI&#x20;Class\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"6\":{\"macro\":\"_field6_\",\"desc\":\"Fields&#x20;list\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"7\":{\"macro\":\"_field7_\",\"desc\":\"Query&#x20;filter&#x20;&#40;Optional&#41;\",\"help\":\"Use&#x20;single&#x20;quotes&#x20;for&#x20;query&#x20;conditions\",\"value\":\"\",\"hide\":\"\"},\"8\":{\"macro\":\"_field8_\",\"desc\":\"Operation\",\"help\":\"Aritmetic&#x20;operation&#x20;to&#x20;get&#x20;data.&#x20;Macros&#x20;_fN_&#x20;will&#x20;be&#x20;changed&#x20;by&#x20;fields&#x20;in&#x20;list.&#x20;Example:&#x20;&#40;&#40;_f1_&#x20;-&#x20;_f2_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f1_\",\"value\":\"\",\"hide\":\"\"}}','-host&#x20;&#039;_field1_&#039;&#x20;-namespace&#x20;&#039;_field2_&#039;&#x20;-user&#x20;&#039;_field3_&#039;&#x20;-pass&#x20;&#039;_field4_&#039;&#x20;-wmiClass&#x20;&#039;_field5_&#039;&#x20;-fieldsList&#x20;&#039;_field6_&#039;&#x20;-queryFilter&#x20;&quot;_field7_&quot;&#x20;-operation&#x20;&#039;_field8_&#039;&#x20;-wmicPath&#x20;/usr/bin/wmic');

SET @main_component_group_name = 'Wizard';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @main_component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@main_component_group_name,0);

SELECT @component_group_parent := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @main_component_group_name;

SET @component_group_name = 'CPU';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Memory';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Disk&#x20;devices';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Storage';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Temperature&#x20;sensors';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Processes';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Other';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Power&#x20;supply';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Fans';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Temperature';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Sessions';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'VPN';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Intrussions';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Antivirus';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Services';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'Disks';
SET @component_id = '';
SELECT @component_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;
INSERT IGNORE INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (@component_id,@component_group_name,@component_group_parent);

SET @component_group_name = 'CPU';

SET @component_name = 'CPU&#x20;User&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;percentage&#x20;of&#x20;CPU&#x20;time&#x20;spent&#x20;processing&#x20;user-level&#x20;code,&#x20;calculated&#x20;over&#x20;the&#x20;last&#x20;minute';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.9.0','',1,'','','','','',1);

SET @component_name = 'CPU&#x20;System&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;percentage&#x20;of&#x20;CPU&#x20;time&#x20;spent&#x20;processing&#x20;system-level&#x20;code,&#x20;calculated&#x20;over&#x20;the&#x20;last&#x20;minute';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.10.0','',1,'','','','','',1);

SET @component_name = 'CPU&#x20;Idle&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;percentage&#x20;of&#x20;CPU&#x20;time&#x20;spent&#x20;idle,&#x20;calculated&#x20;over&#x20;the&#x20;last&#x20;minute';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.11.0','',0,'','','','','',1);

SET @component_name = 'Load&#x20;average&#x20;-&#x20;_nameOID_';
SET @component_description = 'The&#x20;1,&#x20;5&#x20;or&#x20;15&#x20;minutes&#x20;load&#x20;average';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.10.1.3','',0,'1.3.6.1.4.1.2021.10.1.2','','','','',1);

SET @component_name = 'Cisco&#x20;CPU&#x20;_nameOID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;overall&#x20;CPU&#x20;busy&#x20;percentage&#x20;in&#x20;the&#x20;last&#x20;5&#x20;minute&#x20;period';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',1,2,'1.3.6.1.4.1.9.9.109.1.1.1.1.8','',1,'1.3.6.1.4.1.9.9.109.1.1.1.1.2','','','','',1);

SET @component_name = 'F5&#x20;CPU&#x20;_nameOID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'This&#x20;is&#x20;average&#x20;usage&#x20;ratio&#x20;of&#x20;CPU&#x20;for&#x20;the&#x20;associated&#x20;host&#x20;in&#x20;the&#x20;last&#x20;five&#x20;minutes';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,2,'1.3.6.1.4.1.3375.2.1.7.5.2.1.35','',1,'1.3.6.1.4.1.3375.2.1.7.5.2.1.3','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;CPU&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;average&#x20;usage&#x20;ratio&#x20;of&#x20;CPU&#x20;in&#x20;the&#x20;last&#x20;five&#x20;minutes';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.1.13.1.21','',1,'1.3.6.1.4.1.2636.3.1.13.1.5','','','','',1);

SET @component_name = 'HP&#x20;CPU&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;CPU&#x20;utilization&#x20;in&#x20;percent&#40;%&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','hp',1,1,'1.3.6.1.4.1.11.2.14.11.5.1.9.6.1.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;system&#x20;CPU&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'CPU&#x20;usage&#x20;of&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.1.3.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;CPU&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'CPU&#x20;usage&#x20;of&#x20;the&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.3.2.1.1.5','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'WMI&#x20;_DeviceID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'Load&#x20;capacity&#x20;of&#x20;each&#x20;processor,&#x20;averaged&#x20;to&#x20;the&#x20;last&#x20;second';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','{\"extra_field_1\":\"LoadPercentage\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"\",\"_field11__wmi_field\":\"_field11_\",\"_field12__wmi_field\":\"_field12_\",\"_field9__wmi_field\":\"_field9_\",\"_field10__wmi_field\":\"_field10_\",\"_field7__wmi_field\":\"_field7_\",\"_field8__wmi_field\":\"_field8_\",\"_field5__wmi_field\":\"_field5_\",\"_field6__wmi_field\":\"_field6_\",\"_field3__wmi_field\":\"_field3_\",\"_field4__wmi_field\":\"_field4_\",\"_field1__wmi_field\":\"_field1_\",\"_field2__wmi_field\":\"_field2_\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,2,'','',1,'','Win32_Processor','DeviceID','','{\"scan\":\"\",\"execution\":\"DeviceID&#x20;=&#x20;&#039;_DeviceID_&#039;\",\"field\":\"1\",\"key_string\":\"\"}',1);

SET @component_group_name = 'Memory';

SET @component_name = 'Total&#x20;RAM&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'Total&#x20;real/physical&#x20;memory&#x20;used&#x20;on&#x20;the&#x20;host';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.2021.4.6.0\",\"extra_field_2\":\"1.3.6.1.4.1.2021.4.5.0\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field2__snmp_field\":\"_port_\",\"_field1__snmp_field\":\"_address_\",\"_field4__snmp_field\":\"_community_\",\"_field3__snmp_field\":\"_version_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field12__snmp_field\":\"&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',2,1,'','',1,'','','','','',1);

SET @component_name = 'F5&#x20;host&#x20;RAM&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;host&#x20;memory&#x20;percentage&#x20;currently&#x20;in&#x20;use';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.3375.2.1.7.1.2.0\",\"extra_field_2\":\"1.3.6.1.4.1.3375.2.1.7.1.1.0\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',2,1,'','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Host&#x20;_nameOID_&#x20;RAM&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;host&#x20;memory&#x20;percentage&#x20;currently&#x20;in&#x20;use&#x20;for&#x20;the&#x20;specified&#x20;host';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.3375.2.1.7.4.2.1.3\",\"extra_field_2\":\"1.3.6.1.4.1.3375.2.1.7.4.2.1.2\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',2,2,'','',1,'1.3.6.1.4.1.3375.2.1.7.4.2.1.1','','','','',1);

SET @component_name = 'Fortinet&#x20;system&#x20;RAM&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'Memory&#x20;usage&#x20;of&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.1.4.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;RAM&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'Memory&#x20;usage&#x20;of&#x20;the&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.3.2.1.1.6','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'WMI&#x20;total&#x20;RAM&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'Percentage&#x20;of&#x20;physical&#x20;memory&#x20;currently&#x20;used';
SET @plugin_name = 'Wizard&#x20;WMI&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"TotalVisibleMemorySize\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_wmi_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-namespace&#x20;&quot;_namespace_wmi_&quot;&#x20;-user&#x20;&quot;_user_wmi_&quot;&#x20;-pass&#x20;&quot;_pass_wmi_&quot;&#x20;-wmiClass&#x20;&quot;_class_wmi_&quot;&#x20;-fieldsList&#x20;&quot;_field_wmi_0_,_field_wmi_1_&quot;&#x20;-queryFilter&#x20;&quot;&quot;&#x20;-operation&#x20;&quot;&#40;&#40;_f2_&#x20;-&#x20;_f1_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f2_&quot;&#x20;-wmicPath&#x20;/usr/bin/wmic\",\"value_operation\":\"&#40;&#40;_TotalVisibleMemorySize_&#x20;-&#x20;_FreePhysicalMemory_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_TotalVisibleMemorySize_\",\"server_plugin\":\"',@plugin_id,'\",\"_field2__wmi_field\":\"_namespace_wmi_\",\"_field1__wmi_field\":\"_address_\",\"_field4__wmi_field\":\"_pass_wmi_\",\"_field3__wmi_field\":\"_user_wmi_\",\"_field6__wmi_field\":\"_field_wmi_0_,_field_wmi_1_\",\"_field5__wmi_field\":\"_class_wmi_\",\"_field8__wmi_field\":\"&#40;&#40;_f2_&#x20;-&#x20;_f1_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f2_\",\"_field7__wmi_field\":\"\",\"field0_wmi_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',2,1,'','',1,'','Win32_OperatingSystem','FreePhysicalMemory','','{\"scan\":\"\",\"execution\":\"\",\"field\":\"\",\"key_string\":\"\"}',1);

SET @component_name = 'Total&#x20;Swap&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'Total&#x20;swap&#x20;memory&#x20;used&#x20;on&#x20;the&#x20;host';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.2021.4.4.0\",\"extra_field_2\":\"1.3.6.1.4.1.2021.4.3.0\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',2,1,'','',1,'','','','','',1);

SET @component_name = 'Cisco&#x20;memory&#x20;pool&#x20;_nameOID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'Indicates&#x20;the&#x20;percentage&#x20;of&#x20;bytes&#x20;from&#x20;the&#x20;memory&#x20;pool&#x20;that&#x20;are&#x20;currently&#x20;in&#x20;use&#x20;by&#x20;applications&#x20;on&#x20;the&#x20;managed&#x20;device';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.9.9.48.1.1.1.5\",\"extra_field_2\":\"1.3.6.1.4.1.9.9.48.1.1.1.6\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;&#40;_o1_&#x20;+&#x20;_o2_&#41;&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;&#40;_oid_1_&#x20;+&#x20;_oid_2_&#41;\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / (_o1_ + _o2_)\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',2,2,'','',1,'1.3.6.1.4.1.9.9.48.1.1.1.2','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;memory&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;buffer&#x20;pool&#x20;utilization&#x20;in&#x20;percentage&#x20;of&#x20;this&#x20;subject';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.1.13.1.11','',1,'1.3.6.1.4.1.2636.3.1.13.1.5','','','','',1);

SET @component_name = 'HP&#x20;memory&#x20;slot&#x20;_nameOID_&#x20;usage&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;percentage&#x20;of&#x20;currently&#x20;allocated&#x20;bytes';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.4.1.11.2.14.11.5.1.1.2.1.1.1.7\",\"extra_field_2\":\"1.3.6.1.4.1.11.2.14.11.5.1.1.2.1.1.1.5\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','hp',2,2,'','',1,'1.3.6.1.4.1.11.2.14.11.5.1.1.2.1.1.1.1','','','','',1);

SET @component_group_name = 'Disk&#x20;devices';

SET @component_name = 'Disk&#x20;_nameOID_&#x20;bytes&#x20;read';
SET @component_description = 'The&#x20;number&#x20;of&#x20;bytes&#x20;read&#x20;from&#x20;this&#x20;device&#x20;since&#x20;boot';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,16,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'bytes/sec','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.15.1.1.3','',0,'1.3.6.1.4.1.2021.13.15.1.1.2','','','','',1);

SET @component_name = 'Disk&#x20;_nameOID_&#x20;bytes&#x20;written';
SET @component_description = 'The&#x20;number&#x20;of&#x20;bytes&#x20;written&#x20;to&#x20;this&#x20;device&#x20;since&#x20;boot';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,16,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'bytes/sec','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.15.1.1.4','',0,'1.3.6.1.4.1.2021.13.15.1.1.2','','','','',1);

SET @component_name = 'Disk&#x20;_nameOID_&#x20;read&#x20;accesses';
SET @component_description = 'The&#x20;number&#x20;of&#x20;read&#x20;accesses&#x20;from&#x20;this&#x20;device&#x20;since&#x20;boot';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,16,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'accesses/sec','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.15.1.1.5','',0,'1.3.6.1.4.1.2021.13.15.1.1.2','','','','',1);

SET @component_name = 'Disk&#x20;_nameOID_&#x20;write&#x20;accesses';
SET @component_description = 'The&#x20;number&#x20;of&#x20;write&#x20;accesses&#x20;to&#x20;this&#x20;device&#x20;since&#x20;boot';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,16,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'accesses/sec','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.15.1.1.6','',0,'1.3.6.1.4.1.2021.13.15.1.1.2','','','','',1);

SET @component_group_name = 'Storage';

SET @component_name = 'Storage&#x20;_nameOID_&#x20;&#40;%&#41;';
SET @component_description = 'The&#x20;amount&#x20;of&#x20;the&#x20;storage&#x20;represented&#x20;by&#x20;this&#x20;entry&#x20;that&#x20;is&#x20;allocated';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.2.1.25.2.3.1.6\",\"extra_field_2\":\"1.3.6.1.2.1.25.2.3.1.5\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-oidList&#x20;&quot;_oid_1_,_oid_2_&quot;&#x20;-operation&#x20;&quot;&#40;_o1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_o2_&quot;\",\"value_operation\":\"&#40;_oid_1_&#x20;*&#x20;100&#41;&#x20;/&#x20;_oid_2_\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_oid_1_,_oid_2_\",\"_field12__snmp_field\":\"(_o1_ * 100) / _o2_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',2,2,'','',1,'1.3.6.1.2.1.25.2.3.1.3','','','','',1);

SET @component_group_name = 'Temperature&#x20;sensors';

SET @component_name = 'Temperature&#x20;_nameOID_';
SET @component_description = 'The&#x20;temperature&#x20;of&#x20;this&#x20;sensor';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,2,'1.3.6.1.4.1.2021.13.16.2.1.3','',0,'1.3.6.1.4.1.2021.13.16.2.1.2','','','','',1);

SET @component_group_name = 'Processes';

SET @component_name = 'Process&#x20;_nameOID_';
SET @component_description = 'Check&#x20;if&#x20;the&#x20;process&#x20;is&#x20;running';
SET @plugin_name = 'Wizard&#x20;SNMP&#x20;process';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,2,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard',CONCAT('{\"extra_field_1\":\"1.3.6.1.2.1.25.4.2.1.7\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_snmp_process&#x20;-host&#x20;&quot;_address_&quot;&#x20;-port&#x20;&quot;_port_&quot;&#x20;-version&#x20;&quot;_version_&quot;&#x20;-community&#x20;&quot;_community_&quot;&#x20;-secLevel&#x20;&quot;_sec_level_&quot;&#x20;-user&#x20;&quot;_auth_user_&quot;&#x20;-authMethod&#x20;&quot;_auth_method_&quot;&#x20;-authPass&#x20;&quot;_auth_pass_&quot;&#x20;-privMethod&#x20;&quot;_priv_method_&quot;&#x20;-privPass&#x20;&quot;_priv_pass_&quot;&#x20;-process&#x20;&quot;_nameOID_&quot;\",\"value_operation\":\"1\",\"server_plugin\":\"',@plugin_id,'\",\"_field11__snmp_field\":\"_nameOID_\",\"_field9__snmp_field\":\"_priv_method_\",\"_field10__snmp_field\":\"_priv_pass_\",\"_field7__snmp_field\":\"_auth_method_\",\"_field8__snmp_field\":\"_auth_pass_\",\"_field5__snmp_field\":\"_sec_level_\",\"_field6__snmp_field\":\"_auth_user_\",\"_field3__snmp_field\":\"_version_\",\"_field4__snmp_field\":\"_community_\",\"_field1__snmp_field\":\"_address_\",\"_field2__snmp_field\":\"_port_\",\"field0_snmp_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',2,2,'','',0,'1.3.6.1.2.1.25.4.2.1.2','','','','',1);

SET @component_name = 'WMI&#x20;Number&#x20;of&#x20;processes';
SET @component_description = 'Number&#x20;of&#x20;process&#x20;contexts&#x20;currently&#x20;loaded&#x20;or&#x20;running&#x20;on&#x20;the&#x20;operating&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,1,'','',1,'','Win32_OperatingSystem','NumberOfProcesses','','{\"scan\":\"\",\"execution\":\"\",\"field\":\"0\",\"key_string\":\"\"}',1);

SET @component_name = 'WMI&#x20;&#x20;process&#x20;_Name_&#x20;running';
SET @component_description = 'Check&#x20;if&#x20;process&#x20;is&#x20;running';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,2,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"Name\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,2,'','',0,'','Win32_Process','Handle','','{\"scan\":\"\",\"execution\":\"Name&#x20;=&#x20;&#039;_Name_&#039;\",\"field\":\"1\",\"key_string\":\"_Name_\"}',1);

SET @component_group_name = 'Other';

SET @component_name = 'Wizard&#x20;system&#x20;uptime';
SET @component_description = 'The&#x20;time&#x20;&#40;in&#x20;hundredths&#x20;of&#x20;a&#x20;second&#41;&#x20;since&#x20;the&#x20;network&#x20;management&#x20;portion&#x20;of&#x20;the&#x20;system&#x20;was&#x20;last&#x20;re-initialized';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;


INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'_timeticks_','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','all',1,1,'1.3.6.1.2.1.25.1.1.0','',1,'','','','','',1);

SET @component_name = 'Wizard&#x20;network&#x20;uptime';
SET @component_description = 'The&#x20;time&#x20;&#40;in&#x20;hundredths&#x20;of&#x20;a&#x20;second&#41;&#x20;since&#x20;the&#x20;network&#x20;management&#x20;portion&#x20;of&#x20;the&#x20;system&#x20;was&#x20;last&#x20;re-initialized';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;


INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'_timeticks_','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','all',1,1,'1.3.6.1.2.1.1.3.0','',1,'','','','','',1);

SET @component_name = 'Blocks&#x20;sent';
SET @component_description = 'Number&#x20;of&#x20;blocks&#x20;sent&#x20;to&#x20;a&#x20;block&#x20;device';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.57.0','',0,'','','','','',1);

SET @component_name = 'Blocks&#x20;received';
SET @component_description = 'Number&#x20;of&#x20;blocks&#x20;received&#x20;from&#x20;a&#x20;block&#x20;device';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.58.0','',0,'','','','','',1);

SET @component_name = 'Interrupts&#x20;processed';
SET @component_description = 'Number&#x20;of&#x20;interrupts&#x20;processed';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','general_snmp',1,1,'1.3.6.1.4.1.2021.11.59.0','',0,'','','','','',1);

SET @component_group_name = 'Power&#x20;supply';

SET @component_name = 'Cisco&#x20;_nameOID_&#x20;power&#x20;state';
SET @component_description = 'The&#x20;current&#x20;state&#x20;of&#x20;the&#x20;power&#x20;supply:&#x20;normal&#40;1&#41;,&#x20;warning&#40;2&#41;,&#x20;critical&#40;3&#41;,&#x20;shutdown&#40;4&#41;,&#x20;notPresent&#40;5&#41;,&#x20;notFunctioning&#40;6&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',1,2,'1.3.6.1.4.1.9.9.13.1.5.1.3','',0,'1.3.6.1.4.1.9.9.13.1.5.1.2','','','','',1);

SET @component_name = 'F5&#x20;Power&#x20;supply&#x20;_nameOID_&#x20;status';
SET @component_description = 'The&#x20;status&#x20;of&#x20;the&#x20;indexed&#x20;power&#x20;supply&#x20;on&#x20;the&#x20;system:&#x20;bad&#40;0&#41;,&#x20;good&#40;1&#41;,&#x20;notpresent&#40;2&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,2,'1.3.6.1.4.1.3375.2.1.3.2.2.2.1.2','',1,'1.3.6.1.4.1.3375.2.1.3.2.2.2.1','','','','',1);

SET @component_name = 'WMI&#x20;_Name_&#x20;power&#x20;supply&#x20;state';
SET @component_description = 'State&#x20;of&#x20;the&#x20;power&#x20;supply&#x20;or&#x20;supplies&#x20;when&#x20;last&#x20;booted:&#x20;Other&#x20;&#40;1&#41;,&#x20;Unknown&#x20;&#40;2&#41;,&#x20;Safe&#x20;&#40;3&#41;,&#x20;Warning&#x20;&#40;4&#41;,&#x20;Critical&#x20;&#40;5&#41;,&#x20;Non-recoverable&#x20;&#40;6&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"PowerSupplyState\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,2,'','',0,'','Win32_ComputerSystem','Name','','{\"scan\":\"\",\"execution\":\"Name&#x20;=&#x20;&#039;_Name_&#039;\",\"field\":\"1\",\"key_string\":\"\"}',1);

SET @component_name = 'WMI&#x20;_Name_&#x20;Power&#x20;state';
SET @component_description = 'Current&#x20;power&#x20;state&#x20;of&#x20;a&#x20;computer&#x20;and&#x20;its&#x20;associated&#x20;operating&#x20;system:&#x20;Unknown&#x20;&#40;0&#41;,&#x20;Full&#x20;Power&#x20;&#40;1&#41;,&#x20;Low&#x20;Power&#x20;Mode&#x20;&#40;2&#41;,&#x20;Standby&#x20;&#40;3&#41;,&#x20;Unknown&#x20;&#40;4&#41;,&#x20;Power&#x20;Cycle&#x20;&#40;5&#41;,&#x20;Power&#x20;Off&#x20;&#40;6&#41;,&#x20;Warning&#x20;&#40;7&#41;,&#x20;Hibernate&#x20;&#40;8&#41;,&#x20;Soft&#x20;Off&#x20;&#40;9&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"PowerState\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,1,'','',0,'','Win32_ComputerSystem','Name','','{\"scan\":\"\",\"execution\":\"Name&#x20;=&#x20;&#039;_Name_&#039;\",\"field\":\"1\",\"key_string\":\"\"}',1);

SET @component_group_name = 'Fans';

SET @component_name = 'Cisco&#x20;_nameOID_&#x20;fan&#x20;state';
SET @component_description = 'The&#x20;current&#x20;state&#x20;of&#x20;the&#x20;fan:&#x20;normal&#40;1&#41;,&#x20;warning&#40;2&#41;,&#x20;critical&#40;3&#41;,&#x20;shutdown&#40;4&#41;,&#x20;notPresent&#40;5&#41;,&#x20;notFunctioning&#40;6&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',1,2,'1.3.6.1.4.1.9.9.13.1.4.1.3','',1,'1.3.6.1.4.1.9.9.13.1.4.1.2','','','','',1);

SET @component_name = 'F5&#x20;Fan&#x20;_nameOID_&#x20;status';
SET @component_description = 'The&#x20;status&#x20;of&#x20;the&#x20;indexed&#x20;chassis&#x20;fan&#x20;on&#x20;the&#x20;system:&#x20;bad&#40;0&#41;,&#x20;good&#40;1&#41;,&#x20;notpresent&#40;2&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,2,'1.3.6.1.4.1.3375.2.1.3.2.1.2.1.2','',1,'1.3.6.1.4.1.3375.2.1.3.2.1.2.1.1','','','','',1);

SET @component_name = 'HP&#x20;fan&#x20;tray&#x20;_nameOID_&#x20;state';
SET @component_description = 'Current&#x20;state&#x20;of&#x20;the&#x20;fan:&#x20;failed&#40;0&#41;,&#x20;removed&#40;1&#41;,&#x20;off&#40;2&#41;,&#x20;underspeed&#40;3&#41;,&#x20;overspeed&#40;4&#41;,&#x20;ok&#40;5&#41;,&#x20;maxstate&#40;6&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.11.2.14.11.5.1.54.2.1.1.4','',1,'1.3.6.1.4.1.11.2.14.11.5.1.54.2.1.1.2','','','','',1);

SET @component_group_name = 'Temperature';

SET @component_name = 'Cisco&#x20;_nameOID_&#x20;temperature';
SET @component_description = 'The&#x20;current&#x20;measurement&#x20;of&#x20;the&#x20;testpoint&#x20;being&#x20;instrumented';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','cisco',1,2,'1.3.6.1.4.1.9.9.13.1.3.1.3','',1,'1.3.6.1.4.1.9.9.13.1.3.1.2','','','','',1);

SET @component_name = 'F5&#x20;Temperature&#x20;sensor&#x20;_nameOID_';
SET @component_description = 'The&#x20;chassis&#x20;temperature&#x20;of&#x20;the&#x20;indexed&#x20;sensor&#x20;on&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,2,'1.3.6.1.4.1.3375.2.1.3.2.3.2.1.2','',1,'1.3.6.1.4.1.3375.2.1.3.2.3.2.1.1','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;temperature';
SET @component_description = 'The&#x20;temperature&#x20;of&#x20;this&#x20;subject';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.1.13.1.7','',1,'1.3.6.1.4.1.2636.3.1.13.1.5','','','','',1);

SET @component_name = 'HP&#x20;_nameOID_&#x20;temperature';
SET @component_description = 'The&#x20;current&#x20;temperature&#x20;given&#x20;by&#x20;the&#x20;indexed&#x20;chassis';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'&#xba;C','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','hp',1,2,'1.3.6.1.4.1.11.2.14.11.1.2.8.1.1.3','',0,'1.3.6.1.4.1.11.2.14.11.1.2.8.1.1.2','','','','',1);

SET @component_group_name = 'Sessions';

SET @component_name = 'F5&#x20;Current&#x20;auth&#x20;sessions';
SET @component_description = 'The&#x20;current&#x20;number&#x20;of&#x20;concurrent&#x20;auth&#x20;sessions';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.1.1.2.2.3.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;auth&#x20;success&#x20;results';
SET @component_description = 'The&#x20;total&#x20;number&#x20;of&#x20;auth&#x20;success&#x20;results';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.1.1.2.2.5.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;auth&#x20;failure&#x20;results';
SET @component_description = 'The&#x20;total&#x20;number&#x20;of&#x20;auth&#x20;failure&#x20;results';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.1.1.2.2.6.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;auth&#x20;error&#x20;results';
SET @component_description = 'The&#x20;total&#x20;number&#x20;of&#x20;auth&#x20;error&#x20;results';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.1.1.2.2.8.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;ephemeral&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;current&#x20;number&#x20;of&#x20;ephemeral&#x20;sessions&#x20;on&#x20;the&#x20;device';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.1.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;direct&#x20;requests&#x20;count';
SET @component_description = 'The&#x20;number&#x20;of&#x20;direct&#x20;requests&#x20;to&#x20;Fortigate&#x20;local&#x20;stack&#x20;from&#x20;external,&#x20;reflecting&#x20;DOS&#x20;attack&#x20;towards&#x20;the&#x20;Fortigate';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.7.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;clash&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;number&#x20;of&#x20;new&#x20;sessions&#x20;which&#x20;have&#x20;collision&#x20;with&#x20;existing&#x20;sessions.&#x20;This&#x20;generally&#x20;highlights&#x20;a&#x20;shortage&#x20;of&#x20;ports&#x20;or&#x20;IP&#x20;in&#x20;ip-pool&#x20;during&#x20;source&#x20;natting&#x20;&#40;PNAT&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.3.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;expectation&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;number&#x20;of&#x20;current&#x20;expectation&#x20;sessions';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.4.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;sync&#x20;queue&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;sync&#x20;queue&#x20;full&#x20;counter,&#x20;reflecting&#x20;bursts&#x20;on&#x20;the&#x20;sync&#x20;queue';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.5.0','',1,'','','','','',1);

SET @component_name = 'Fortinet&#x20;accept&#x20;queue&#x20;sessions&#x20;count';
SET @component_description = 'The&#x20;accept&#x20;queue&#x20;full&#x20;counter,&#x20;reflecting&#x20;bursts&#x20;on&#x20;the&#x20;accept&#x20;queue';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,1,'1.3.6.1.4.1.12356.101.4.6.2.6.0','',1,'','','','','',1);

SET @component_group_name = 'VPN';

SET @component_name = 'F5&#x20;Current&#x20;SSL/VPN&#x20;connections';
SET @component_description = 'The&#x20;total&#x20;current&#x20;SSL/VPN&#x20;connections&#x20;in&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.6.1.5.3.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;SSL/VPN&#x20;bytes&#x20;received';
SET @component_description = 'The&#x20;total&#x20;raw&#x20;bytes&#x20;received&#x20;by&#x20;SSL/VPN&#x20;connections&#x20;in&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'bytes','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.6.1.5.5.0','',1,'','','','','',1);

SET @component_name = 'F5&#x20;Total&#x20;SSL/VPN&#x20;bytes&#x20;transmitted';
SET @component_description = 'The&#x20;total&#x20;raw&#x20;bytes&#x20;transmitted&#x20;by&#x20;SSL/VPN&#x20;connections&#x20;in&#x20;the&#x20;system';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'bytes','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','f5',1,1,'1.3.6.1.4.1.3375.2.6.1.5.6.0','',1,'','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;active&#x20;sites';
SET @component_description = 'The&#x20;number&#x20;of&#x20;active&#x20;sites&#x20;in&#x20;the&#x20;VPN';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.26.1.2.1.9','',1,'1.3.6.1.4.1.2636.3.26.1.2.1.2','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;age';
SET @component_description = 'The&#x20;age&#x20;&#40;i.e.,&#x20;time&#x20;from&#x20;creation&#x20;till&#x20;now&#41;&#x20;of&#x20;this&#x20;VPN&#x20;in&#x20;hundredths&#x20;of&#x20;a&#x20;second';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'_timeticks_','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.26.1.2.1.12','',1,'1.3.6.1.4.1.2636.3.26.1.2.1.2','','','','',1);

SET @component_name = 'Juniper&#x20;_nameOID_&#x20;interface&#x20;status';
SET @component_description = 'Status&#x20;of&#x20;this&#x20;interface:&#x20;unknown&#40;0&#41;,&#x20;noLocalInterface&#40;1&#41;,&#x20;disabled&#40;2&#41;,&#x20;encapsulationMismatch&#40;3&#41;,&#x20;down&#40;4&#41;,&#x20;up&#40;5&#41;';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','juniper',1,2,'1.3.6.1.4.1.2636.3.26.1.3.1.10','',1,'1.3.6.1.4.1.2636.3.26.1.3.1.2','','','','',1);

SET @component_group_name = 'Intrussions';

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;intrussions&#x20;detected';
SET @component_description = 'Number&#x20;of&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.1','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;intrussions&#x20;blocked';
SET @component_description = 'Number&#x20;of&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.1','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;critical&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;critical&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.3','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;high&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;high&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.4','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;medium&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;medium&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.5','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;low&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;low&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.6','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;informational&#x20;severity&#x20;intrussions';
SET @component_description = 'Number&#x20;of&#x20;informational&#x20;severity&#x20;intrusions&#x20;detected&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.7','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;signature&#x20;detections';
SET @component_description = 'Number&#x20;of&#x20;intrusions&#x20;detected&#x20;by&#x20;signature&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.8','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;anomaly&#x20;detections';
SET @component_description = 'Number&#x20;of&#x20;intrusions&#x20;DECed&#x20;as&#x20;anomalies&#x20;since&#x20;start-up&#x20;in&#x20;this&#x20;virtual&#x20;domain';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.9.2.1.1.9','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_group_name = 'Antivirus';

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;virus&#x20;detected';
SET @component_description = 'Number&#x20;of&#x20;virus&#x20;transmissions&#x20;detected&#x20;in&#x20;the&#x20;virtual&#x20;domain&#x20;since&#x20;start-up';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.8.2.1.1.1','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;virus&#x20;blocked';
SET @component_description = 'Number&#x20;of&#x20;virus&#x20;transmissions&#x20;blocked&#x20;in&#x20;the&#x20;virtual&#x20;domain&#x20;since&#x20;start-up';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.8.2.1.1.2','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;oversized&#x20;detected';
SET @component_description = 'Number&#x20;of&#x20;over-sized&#x20;file&#x20;transmissions&#x20;detected&#x20;in&#x20;the&#x20;virtual&#x20;domain&#x20;since&#x20;start-up';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.8.2.1.1.17','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_name = 'Fortinet&#x20;virtual&#x20;domain&#x20;_nameOID_&#x20;oversized&#x20;blocked';
SET @component_description = 'Number&#x20;of&#x20;over-sized&#x20;file&#x20;transmissions&#x20;blocked&#x20;in&#x20;the&#x20;virtual&#x20;domain&#x20;since&#x20;start-up';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,15,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','Array','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'snmp','fortinet',1,2,'1.3.6.1.4.1.12356.101.8.2.1.1.18','',1,'1.3.6.1.4.1.12356.101.3.2.1.1.2','','','','',1);

SET @component_group_name = 'Services';

SET @component_name = 'WMI&#x20;Service&#x20;_Name_&#x20;running';
SET @component_description = '_Caption_';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,2,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','nowizard','{\"extra_field_1\":\"State\",\"extra_field_2\":\"Caption\",\"satellite_execution\":\"\",\"value_operation\":\"\",\"server_plugin\":\"0\",\"field0_wmi_field\":\"\"}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',1,2,'','',0,'','Win32_Service','Name','','{\"scan\":\"\",\"execution\":\"Name&#x20;=&#x20;&#039;_Name_&#039;\",\"field\":\"1\",\"key_string\":\"Running\"}',1);

SET @component_group_name = 'Disks';

SET @component_name = 'WMI&#x20;disk&#x20;_DeviceID_&#x20;used&#x20;&#40;%&#41;';
SET @component_description = 'Space&#x20;percentage&#x20;used&#x20;on&#x20;the&#x20;logical&#x20;disk';
SET @plugin_name = 'Wizard&#x20;WMI&#x20;module';

SET @component_id = '';
SELECT @component_id := `id_nc` FROM `tnetwork_component` WHERE `name` = @component_name;

SET @group_id = '';
SELECT @group_id := `id_sg` FROM `tnetwork_component_group` WHERE `name` = @component_group_name;

SET @plugin_id = '';
SELECT @plugin_id := `id` FROM `tplugin` WHERE `name` = @plugin_name;

INSERT IGNORE INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `ff_type`, `each_ff`, `dynamic_interval`, `dynamic_max`, `dynamic_min`, `dynamic_next`, `dynamic_two_tailed`, `module_type`, `protocol`, `manufacturer_id`, `execution_type`, `scan_type`, `value`, `value_operations`, `module_enabled`, `name_oid`, `query_class`, `query_key_field`, `scan_filters`, `query_filters`, `enabled`) VALUES (@component_id,@component_name,@component_description,@group_id,1,0,0,0,0,'','','','',0,9,0,'','','',0,0,0,80.00,90.00,'',90.00,0.00,'',0,'','','',0,0,0.000000000000000,'%','nowizard',CONCAT('{\"extra_field_1\":\"Size\",\"extra_field_2\":\"FreeSpace\",\"satellite_execution\":\"/etc/pandora/satellite_plugins/wizard_wmi_module&#x20;-host&#x20;&quot;_address_&quot;&#x20;-namespace&#x20;&quot;_namespace_wmi_&quot;&#x20;-user&#x20;&quot;_user_wmi_&quot;&#x20;-pass&#x20;&quot;_pass_wmi_&quot;&#x20;-wmiClass&#x20;&quot;_class_wmi_&quot;&#x20;-fieldsList&#x20;&quot;_field_wmi_1_,_field_wmi_2_&quot;&#x20;-queryFilter&#x20;&quot;DeviceID&#x20;=&#x20;&#039;_DeviceID_&#039;&quot;&#x20;-operation&#x20;&quot;&#40;&#40;_f1_&#x20;-&#x20;_f2_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f1_&quot;&#x20;-wmicPath&#x20;/usr/bin/wmic\",\"value_operation\":\"&#40;&#40;_Size_&#x20;-&#x20;_FreeSpace_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_Size_\",\"server_plugin\":\"',@plugin_id,'\",\"_field2__wmi_field\":\"_namespace_wmi_\",\"_field1__wmi_field\":\"_address_\",\"_field4__wmi_field\":\"_pass_wmi_\",\"_field3__wmi_field\":\"_user_wmi_\",\"_field6__wmi_field\":\"_field_wmi_1_,_field_wmi_2_\",\"_field5__wmi_field\":\"_class_wmi_\",\"_field8__wmi_field\":\"&#40;&#40;_f1_&#x20;-&#x20;_f2_&#41;&#x20;*&#x20;100&#41;&#x20;/&#x20;_f1_\",\"_field7__wmi_field\":\"DeviceID&#x20;=&#x20;&#039;_DeviceID_&#039;\",\"field0_wmi_field\":\"\"}'),'','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0,0,0,0,0,0,0,1,'wmi','',2,2,'','',1,'','Win32_LogicalDisk','DeviceID','','{\"scan\":\"DriveType&#x20;=&#x20;3\",\"execution\":\"\",\"field\":\"\",\"key_string\":\"\"}',1);

INSERT IGNORE INTO `tpen` VALUES (171,'dlink','D-Link Systems, Inc.'),(14988,'mikrotik','MikroTik'),(6486,'alcatel','Alcatel-Lucent Enterprise'),(41112,'ubiquiti','Ubiquiti Networks, Inc.'),(207,'telesis','Allied Telesis, Inc.'),(10002,'frogfoot','Frogfoot Networks'),(2,'ibm','IBM'),(4,'unix','Unix'),(63,'apple','Apple Computer, Inc.'),(674,'dell','Dell Inc.'),(111,'oracle','Oracle'),(116,'hitachi','Hitachi, Ltd.'),(173,'netlink','Netlink'),(188,'ascom','Ascom'),(6574,'synology','Synology Inc.'),(3861,'fujitsu','Fujitsu Network Communications, Inc.'),(53526,'dell','Dell ATC'),(52627,'apple','Apple Inc'),(19464,'hitachi','Hitachi Communication Technologies, Ltd.'),(13062,'ascom','Ascom');

ALTER TABLE `tmensajes` ADD COLUMN `hidden_sent` TINYINT(1) UNSIGNED DEFAULT 0;

COMMIT;
