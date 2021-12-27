-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------

ALTER TABLE tlayout DROP COLUMN fullscreen;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------

ALTER TABLE tlayout_data DROP COLUMN no_link_color;
ALTER TABLE tlayout_data DROP COLUMN label_color;
ALTER TABLE tlayout_data ADD COLUMN `border_width` INTEGER UNSIGNED NOT NULL default 0;
ALTER TABLE tlayout_data ADD COLUMN `border_color` varchar(200) DEFAULT "";
ALTER TABLE tlayout_data ADD COLUMN `fill_color` varchar(200) DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `tconfig_os`
-- ---------------------------------------------------------------------

INSERT INTO `tconfig_os` (`name`, `description`, `icon_name`) VALUES ('Mainframe', 'Mainframe agent', 'so_mainframe.png');

-- ---------------------------------------------------------------------
-- Table `ttag_module`
-- ---------------------------------------------------------------------
ALTER TABLE ttag_module ADD COLUMN `id_policy_module` int(10) NOT NULL DEFAULT 0;

UPDATE ttag_module t1
SET t1.id_policy_module = (
	SELECT t2.id_policy_module
	FROM tagente_modulo t2
	WHERE t1.id_agente_modulo = t2.id_agente_modulo);

/* 2014/12/10 */
-- ----------------------------------------------------------------------
-- Table `tuser_double_auth`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tuser_double_auth` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_user` varchar(60) NOT NULL,
	`secret` varchar(20) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`id_user`),
	FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `ttipo_modulo`
-- ----------------------------------------------------------------------
INSERT INTO `ttipo_modulo` VALUES (5,'generic_data_inc_abs',0,'Generic numeric incremental (absolute)','mod_data_inc_abs.png');

-- ---------------------------------------------------------------------
-- Table `tusuario`
-- ---------------------------------------------------------------------
ALTER TABLE `tusuario` ADD COLUMN `strict_acl` tinyint(1) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `talert_commands`
-- ---------------------------------------------------------------------
UPDATE `talert_commands` SET `fields_descriptions` = '[\"Destination&#x20;address\",\"Subject\",\"Text\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]', `fields_values` = '[\"\",\"\",\"_html_editor_\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]' WHERE `id` = 1 AND `name` = 'eMail';

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO `tconfig` (`token`, `value`) VALUES  ('post_process_custom_values', '{"0.00000038580247":"Seconds&#x20;to&#x20;months","0.00000165343915":"Seconds&#x20;to&#x20;weeks","0.00001157407407":"Seconds&#x20;to&#x20;days","0.01666666666667":"Seconds&#x20;to&#x20;minutes","0.00000000093132":"Bytes&#x20;to&#x20;Gigabytes","0.00000095367432":"Bytes&#x20;to&#x20;Megabytes","0.0009765625":"Bytes&#x20;to&#x20;Kilobytes","0.00000001653439":"Timeticks&#x20;to&#x20;weeks","0.00000011574074":"Timeticks&#x20;to&#x20;days"}');
UPDATE `tconfig` SET value = 'v6.0dev' WHERE token = 'db_scheme_version';
UPDATE `tconfig` SET value = 'https://firefly.artica.es/pandoraupdate6/server.php' WHERE token = 'url_update_manager';

-- ---------------------------------------------------------------------
-- Table `tnetwork_map`
-- ---------------------------------------------------------------------
ALTER TABLE `tnetwork_map` ADD COLUMN `id_tag` int(11) DEFAULT 0;
ALTER TABLE `tnetwork_map` ADD COLUMN `store_group` int(11) DEFAULT 0;
UPDATE `tnetwork_map` SET `store_group` = `id_group`;

-- ---------------------------------------------------------------------
-- Table `tperfil`
-- ---------------------------------------------------------------------
ALTER TABLE `tperfil` ADD COLUMN `map_view` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tperfil` ADD COLUMN `map_edit` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tperfil` ADD COLUMN `map_management` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tperfil` ADD COLUMN `vconsole_view` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tperfil` ADD COLUMN `vconsole_edit` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tperfil` ADD COLUMN `vconsole_management` tinyint(1) NOT NULL DEFAULT 0;

UPDATE `tperfil` SET `map_view` = 1, `vconsole_view` = 1 WHERE `report_view` = 1;
UPDATE `tperfil` SET `map_edit` = 1, `vconsole_edit` = 1 WHERE `report_edit` = 1;
UPDATE `tperfil` SET `map_management` = 1, `vconsole_management` = 1 WHERE `report_management` = 1;

-- ---------------------------------------------------------------------
-- Table `tsessions_php`
-- ---------------------------------------------------------------------
CREATE TABLE tsessions_php (
	`id_session` CHAR(52) NOT NULL,
	`last_active` INTEGER NOT NULL,
	`data` TEXT,
	PRIMARY KEY (`id_session`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tevent_response`
-- ---------------------------------------------------------------------
INSERT INTO `tevent_response` VALUES (6,'Ping&#x20;to&#x20;module&#x20;agent&#x20;host','Ping&#x20;to&#x20;the&#x20;module&#x20;agent&#x20;host','ping&#x20;-c&#x20;5&#x20;_module_address_','command',0,620,500,0,'');

-- ---------------------------------------------------------------------
-- Table `tplugin`
-- ---------------------------------------------------------------------
UPDATE `tplugin`
	SET `macros` = '{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Target&#x20;IP\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Sensor\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"5\":{\"macro\":\"_field5_\",\"desc\":\"Additional&#x20;Options\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"}}',
	`parameters` = '-h&#x20;_field1_&#x20;-u&#x20;_field2_&#x20;-p&#x20;_field3_&#x20;-s&#x20;_field4_&#x20;--&#x20;_field5_'
WHERE `id` = 1 AND `name` = 'IPMI&#x20;Plugin';

-- ---------------------------------------------------------------------
-- Table `trecon_script`
-- ---------------------------------------------------------------------
UPDATE `trecon_script` SET 
	`description` = 'Specific&#x20;Pandora&#x20;FMS&#x20;Intel&#x20;DCM&#x20;Discovery&#x20;&#40;c&#41;&#x20;Artica&#x20;ST&#x20;2011&#x20;&lt;info@artica.es&gt;&#x0d;&#x0a;&#x0d;&#x0a;Usage:&#x20;./ipmi-recon.pl&#x20;&lt;task_id&gt;&#x20;&lt;group_id&gt;&#x20;&lt;create_incident_flag&gt;&#x20;&lt;custom_field1&gt;&#x20;&lt;custom_field2&gt;&#x20;&lt;custom_field3&gt;&#x20;&lt;custom_field4&gt;&#x0d;&#x0a;&#x0d;&#x0a;*&#x20;custom_field1&#x20;=&#x20;Network&#x20;i.e.:&#x20;192.168.100.0/24&#x0d;&#x0a;*&#x20;custom_field2&#x20;=&#x20;Username&#x0d;&#x0a;*&#x20;custom_field3&#x20;=&#x20;Password&#x0d;&#x0a;*&#x20;custom_field4&#x20;=&#x20;Additional&#x20;parameters&#x20;i.e.:&#x20;-D&#x20;LAN_2_0',
	`macros` = '{\"1\":{\"macro\":\"_field1_\",\"desc\":\"Network\",\"help\":\"i.e.:&#x20;192.168.100.0/24\",\"value\":\"\",\"hide\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"\",\"value\":\"\",\"hide\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"\",\"value\":\"\",\"hide\":\"1\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Additional&#x20;parameters\",\"help\":\"Optional&#x20;additional&#x20;parameters&#x20;such&#x20;as&#x20;-D&#x20;LAN_2_0&#x20;to&#x20;use&#x20;IPMI&#x20;ver&#x20;2.0&#x20;instead&#x20;of&#x20;1.5.&#x20;&#x20;These&#x20;options&#x20;will&#x20;also&#x20;be&#x20;passed&#x20;to&#x20;the&#x20;IPMI&#x20;plugin&#x20;when&#x20;the&#x20;current&#x20;values&#x20;are&#x20;read.\",\"value\":\"\",\"hide\":\"\"}}'
WHERE `id_recon_script` = 2 AND `name` = 'IPMI&#x20;Recon';

-- ---------------------------------------------------------------------
-- Table `tnetwork_component`
-- ---------------------------------------------------------------------

UPDATE tnetwork_component SET snmp_oid ='SELECT&#x20;DNSHostName&#x20;FROM&#x20;Win32_ComputerSystem' WHERE id_nc = 204 AND name = 'Hostname';
UPDATE `tnetwork_component` set `tcp_port`=0 WHERE id_nc=207;
UPDATE `tnetwork_component` set `tcp_port`=0 WHERE id_nc=219;
DELETE FROM `tnetwork_component` WHERE id_nc IN (766, 767, 768, 769, 770, 771, 772, 773, 774, 775, 776, 777, 778, 779);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (766,'DBSelects','Number&#x20;of&#x20;selects&#x20;on&#x20;database',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Com_select\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (767,'DBUpdates','Number&#x20;of&#x20;updates&#x20;on&#x20;database',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Com_update\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (768,'InnoDB_Rows_Read','Rows&#x20;read&#x20;on&#x20;InnoDB&#x20;engine',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Innodb_rows_read\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (769,'DB_Connections','Current&#x20;connections&#x20;on&#x20;database',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Connections\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (770,'Qcache_not_cached','Cache&#x20;hit&#x20;missing&#x20;&#40;queries&#x20;not&#x20;cached&#41;',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Qcache_not_cached\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (771,'Table_locks_waited','Table&#x20;locks&#x20;waited',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Table_locks_waited\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (772,'Slow_launch_threads','Slow&#x20;launch&#x20;threads',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Slow_launch_threads\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (773,'Qcache_hits','Queries&#x20;cached&#x20;successfully',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Qcache_hits\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (774,'Innodb_data_pending_reads','InnoDB&#x20;engine&#x20;pending&#x20;reads',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Innodb_data_pending_reads\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (775,'Aborted_connects','Aborted&#x20;connection&#x20;attempts',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Aborted_connects\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (776,'Bytes_received','Bytes&#x20;received&#x20;by&#x20;database&#x20;&#40;global&#41;',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Bytes_received\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (777,'Bytes_sent','Bytes_sent&#x20;by&#x20;database&#x20;&#40;global&#41;',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Bytes_sent\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (778,'MySQL_Updates','Updates&#x20;per&#x20;second',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Com_update\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `str_warning`, `min_critical`, `max_critical`, `str_critical`, `min_ff_event`, `custom_string_1`, `custom_string_2`, `custom_string_3`, `custom_integer_1`, `custom_integer_2`, `post_process`, `unit`, `wizard_level`, `macros`, `critical_instructions`, `warning_instructions`, `unknown_instructions`, `critical_inverse`, `warning_inverse`, `id_category`, `tags`, `disabled_types_event`, `module_macros`, `min_ff_event_normal`, `min_ff_event_warning`, `min_ff_event_critical`, `each_ff`) VALUES (779,'MySQL_Deletes','Deletes&#x20;per&#x20;second',42,4,0,0,300,0,'','','','',6,4,6,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','','',0,0,0.000000000000000,'','basic','{\"1\":{\"macro\":\"_field1_\",\"desc\":\"IP&#x20;address\",\"help\":\"IP&#x20;address\",\"value\":\"\"},\"2\":{\"macro\":\"_field2_\",\"desc\":\"Username\",\"help\":\"Username&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"3\":{\"macro\":\"_field3_\",\"desc\":\"Password\",\"help\":\"Password&#x20;to&#x20;access&#x20;to&#x20;database\",\"value\":\"\"},\"4\":{\"macro\":\"_field4_\",\"desc\":\"Query&#x20;string\",\"help\":\"Query&#x20;string&#x20;of&#x20;global&#x20;status.&#x20;For&#x20;example&#x20;&#039;Aborted_connects&#039;&#x20;or&#x20;&#039;Innodb_rows_read&#039;\",\"value\":\"Com_delete\"}}','','','',0,0,0,'','{\"going_unknown\":0}','',0,0,0,0);

-- -----------------------------------------------------
-- Table `tgis_map_has_tgis_map_con` (tgis_map_has_tgis_map_connection)
-- -----------------------------------------------------
-- Changed the table and a column name cause oracle doesn't support plus 30 characters identifiers
CREATE  TABLE IF NOT EXISTS `tgis_map_has_tgis_map_con` (
	`tgis_map_id_tgis_map` INT NOT NULL COMMENT 'reference to tgis_map',
	`tgis_map_con_id_tmap_con` INT NOT NULL COMMENT 'reference to tgis_map_connection',
	`modification_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Modification Time of the Connection',
	`default_map_connection` TINYINT(1) NULL DEFAULT FALSE COMMENT 'Flag to mark the default map connection of a map',
	PRIMARY KEY (`tgis_map_id_tgis_map`, `tgis_map_con_id_tmap_con`),
	INDEX `fk_tgis_map_has_tgis_map_connection_tgis_map1` (`tgis_map_id_tgis_map` ASC),
	INDEX `fk_tgis_map_has_tgis_map_connection_tgis_map_connection1` (`tgis_map_con_id_tmap_con` ASC),
	FOREIGN KEY (`tgis_map_id_tgis_map`) REFERENCES `tgis_map` (`id_tgis_map`) ON DELETE CASCADE,
	FOREIGN KEY (`tgis_map_con_id_tmap_con`) REFERENCES `tgis_map_connection` (`id_tmap_connection`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

INSERT INTO `tgis_map_has_tgis_map_con` SELECT * FROM `tgis_map_has_tgis_map_connection`;
DROP TABLE `tgis_map_has_tgis_map_connection`;

ALTER TABLE `tmodule_relationship` ADD FOREIGN KEY (`id_rt`) REFERENCES trecon_task(`id_rt`) ON DELETE CASCADE;
ALTER TABLE tmodule_relationship MODIFY `id_rt` int(10) unsigned NULL default NULL;

-- ---------------------------------------------------------------------
-- Table `tagente_modulo`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_modulo ADD COLUMN `dynamic_interval` int(4) unsigned default '0';
ALTER TABLE tagente_modulo ADD COLUMN `dynamic_max` bigint(20) default '0';
ALTER TABLE tagente_modulo ADD COLUMN `dynamic_min` bigint(20) default '0';
ALTER TABLE tagente_modulo ADD COLUMN `prediction_sample_window` int(10) default 0;
ALTER TABLE tagente_modulo ADD COLUMN `prediction_samples` int(4) default 0;
ALTER TABLE tagente_modulo ADD COLUMN `prediction_threshold` int(4) default 0;
ALTER TABLE tagente_modulo ADD COLUMN `percentage_critical` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE tagente_modulo ADD COLUMN `percentage_warning` tinyint(1) UNSIGNED DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tagente_estado`
-- ---------------------------------------------------------------------
ALTER TABLE tagente_estado ADD COLUMN `last_dynamic_update` bigint(20) NOT NULL default '0';

-- ---------------------------------------------------------------------
-- Table `tgraph_source`
-- ---------------------------------------------------------------------	
ALTER TABLE tgraph_source ADD COLUMN `label` varchar(150) DEFAULT '';
ALTER TABLE tgraph_source ADD COLUMN `id_server` int(11) NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------	
ALTER TABLE tevent_filter ADD COLUMN `id_agent_module` int(25) DEFAULT 0;
ALTER TABLE tevent_filter ADD COLUMN `id_agent` int(25) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------	
ALTER TABLE `tnetwork_component` ADD COLUMN `percentage_critical` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tnetwork_component` ADD COLUMN `percentage_warning` tinyint(1) UNSIGNED DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------	
ALTER TABLE `tlocal_component` ADD COLUMN `percentage_critical` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tlocal_component` ADD COLUMN `percentage_warning` tinyint(1) UNSIGNED DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `tevent_filter`
-- ---------------------------------------------------------------------	
ALTER TABLE `tpolicy_modules` ADD COLUMN `percentage_critical` tinyint(1) UNSIGNED DEFAULT 0;
ALTER TABLE `tpolicy_modules` ADD COLUMN `percentage_warning` tinyint(1) UNSIGNED DEFAULT 0;
