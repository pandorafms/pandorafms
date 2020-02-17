START TRANSACTION;

ALTER TABLE `treport_template` ADD COLUMN `agent_regex` varchar(600) NOT NULL default '';
ALTER TABLE `tlayout_template_data` ADD COLUMN `cache_expiration` INTEGER UNSIGNED NOT NULL DEFAULT 0;

INSERT INTO `ttipo_modulo` VALUES
(34,'remote_cmd', 10, 'Remote execution, numeric data', 'mod_remote_cmd.png'),
(35,'remote_cmd_proc', 10, 'Remote execution, boolean data', 'mod_remote_cmd_proc.png'),
(36,'remote_cmd_string', 10, 'Remote execution, alphanumeric data', 'mod_remote_cmd_string.png'),
(37,'remote_cmd_inc', 10, 'Remote execution, incremental data', 'mod_remote_cmd_inc.png');

INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('N.&#x20;total&#x20;processes','Number&#x20;of&#x20;running&#x20;processes&#x20;in&#x20;a&#x20;Windows&#x20;system.',11,34,0,0,300,0,'tasklist&#x20;/NH&#x20;|&#x20;find&#x20;/c&#x20;/v&#x20;&quot;&quot;','','','',6,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','windows','',0,0,0.000000000000000,'','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Free&#x20;space&#x20;in&#x20;C:','Free&#x20;space&#x20;available&#x20;in&#x20;C:',11,34,0,0,300,0,'powershell&#x20;$obj=&#40;Get-WmiObject&#x20;-class&#x20;&quot;Win32_LogicalDisk&quot;&#x20;-namespace&#x20;&quot;root&#92;CIMV2&quot;&#41;&#x20;;&#x20;$obj.FreeSpace[0]&#x20;*&#x20;100&#x20;/$obj.Size[0]','','','',4,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','windows','',0,0,0.000000000000000,'%','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;uptime','System&#x20;uptime',43,36,0,0,300,0,'uptime&#x20;|sed&#x20;s/us&#92;.*$//g&#x20;|&#x20;sed&#x20;s/,&#92;.*$//g','','','',4,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','linux','',0,0,0.000000000000000,'','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;processes','Running&#x20;processes',43,34,0,0,300,0,'ps&#x20;elf&#x20;|&#x20;wc&#x20;-l','','','',6,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','linux','',0,0,0.000000000000000,'','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;system&#x20;load','Current&#x20;load&#x20;&#40;5&#x20;min&#41;',43,34,0,0,300,0,'uptime&#x20;|&#x20;awk&#x20;&#039;{print&#x20;$&#40;NF-1&#41;}&#039;&#x20;|&#x20;tr&#x20;-d&#x20;&#039;,&#039;','','','',6,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','linux','',0,0,0.000000000000000,'','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;available&#x20;memory&#x20;percent','Available&#x20;memory&#x20;%',43,34,0,0,300,0,'free&#x20;|&#x20;grep&#x20;Mem&#x20;|&#x20;awk&#x20;&#039;{print&#x20;$NF/$2&#x20;*&#x20;100}&#039;','','','',4,2,0,'','','',0,0,1,0.00,0.00,'',0.00,0.00,'',0,'','linux','',0,0,0.000000000000000,'%','nowizard','','','','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);
INSERT INTO `tnetwork_component` (`name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`, `id_modulo`, `id_plugin`, `plugin_user`, `plugin_pass`, `plugin_parameter`, `max_timeout`, `max_retries`, `history_data`, `min_warning`, `max_warning`, `max_critical`, `str_warning`, `min_ff_event`, `min_critical`, `custom_string_2`, `str_critical`, `custom_integer_1`, `custom_string_1`, `post_process`, `custom_string_3`, `wizard_level`, `custom_integer_2`, `critical_instructions`, `unit`, `unknown_instructions`, `macros`, `warning_inverse`, `warning_instructions`, `tags`, `critical_inverse`, `module_macros`, `id_category`, `min_ff_event_warning`, `disabled_types_event`, `ff_type`, `min_ff_event_normal`, `dynamic_interval`, `min_ff_event_critical`, `dynamic_min`, `each_ff`, `dynamic_two_tailed`, `dynamic_max`, `dynamic_next`) VALUES ('Linux&#x20;available&#x20;disk&#x20;/','Available&#x20;free&#x20;space&#x20;in&#x20;mountpoint&#x20;/',43,34,0,0,300,0,'df&#x20;/&#x20;|&#x20;tail&#x20;-n&#x20;+2&#x20;|&#x20;awk&#x20;&#039;{print&#x20;$&#40;NF-1&#41;}&#039;&#x20;|&#x20;tr&#x20;-d&#x20;&#039;%&#039;','','','',4,2,0,'','','',0,0,1,0.00,0.00,'0.00',0.00,0.00,'',0,'','inherited','',0,0,0.000000000000000,'','nowizard','','nowizard','0','',0,0,0,'','{\"going_unknown\":1}','',0,0,0,0,0,0,0,0,0,0);

ALTER TABLE `tevent_rule` MODIFY COLUMN `event_type` enum('','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal') default '';
ALTER TABLE `tevent_rule` MODIFY COLUMN `criticity` int(4) unsigned DEFAULT NULL;
ALTER TABLE `tevent_rule` MODIFY COLUMN `id_grupo` mediumint(4) DEFAULT NULL;

ALTER TABLE `tevent_rule` ADD COLUMN `log_content` TEXT;
ALTER TABLE `tevent_rule` ADD COLUMN `log_source` TEXT;
ALTER TABLE `tevent_rule` ADD COLUMN `log_agent` TEXT;

ALTER TABLE `tevent_rule` ADD COLUMN `operator_agent` text COMMENT 'Operator for agent';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_id_usuario` text COMMENT 'Operator for id_usuario';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_id_grupo` text COMMENT 'Operator for id_grupo';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_evento` text COMMENT 'Operator for evento';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_event_type` text COMMENT 'Operator for event_type';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_module` text COMMENT 'Operator for module';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_alert` text COMMENT 'Operator for alert';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_criticity` text COMMENT 'Operator for criticity';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_user_comment` text COMMENT 'Operator for user_comment';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_id_tag` text COMMENT 'Operator for id_tag';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_log_content` text COMMENT 'Operator for log_content';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_log_source` text COMMENT 'Operator for log_source';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_log_agent` text COMMENT 'Operator for log_agent';

UPDATE `tevent_rule` SET `operator_agent` = "REGEX" WHERE `agent` != '';
UPDATE `tevent_rule` SET `operator_id_usuario` = "REGEX" WHERE `id_usuario` != '';
UPDATE `tevent_rule` SET `operator_id_grupo` = "REGEX" WHERE `id_grupo` > 0;
UPDATE `tevent_rule` SET `operator_evento` = "REGEX" WHERE `evento` != '';
UPDATE `tevent_rule` SET `operator_event_type` = "REGEX" WHERE `event_type` != '';
UPDATE `tevent_rule` SET `operator_module` = "REGEX" WHERE `module` != '';
UPDATE `tevent_rule` SET `operator_alert` = "REGEX" WHERE `alert` != '';
UPDATE `tevent_rule` SET `operator_criticity` = "REGEX" WHERE `criticity` != '99';
UPDATE `tevent_rule` SET `operator_user_comment` = "REGEX" WHERE `user_comment` != '';
UPDATE `tevent_rule` SET `operator_id_tag` = "REGEX" WHERE `id_tag` > 0;
UPDATE `tevent_rule` SET `operator_log_content` = "REGEX" WHERE `log_content` != '';
UPDATE `tevent_rule` SET `operator_log_source` = "REGEX" WHERE `log_source` != '';
UPDATE `tevent_rule` SET `operator_log_agent` = "REGEX" WHERE `log_agent` != '';

ALTER TABLE `tevent_alert` ADD COLUMN `special_days` tinyint(1) default 0;
ALTER TABLE `tevent_alert` MODIFY COLUMN `time_threshold` int(10) NOT NULL default 86400;

CREATE TABLE `tremote_command` (
  `id` SERIAL,
  `name` varchar(150) NOT NULL,
  `timeout` int(10) unsigned NOT NULL default 30,
  `retries` int(10) unsigned NOT NULL default 3,
  `preconditions` text,
  `script` text,
  `postconditions` text,
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `id_group` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tremote_command_target` (
  `id` SERIAL,
  `rcmd_id` bigint unsigned NOT NULL,
  `id_agent` int(10) unsigned NOT NULL,
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `stdout` MEDIUMTEXT,
  `stderr` MEDIUMTEXT,
  `errorlevel` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`rcmd_id`) REFERENCES `tremote_command`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tconfig`(`token`, `value`) VALUES ('welcome_state', -1);


ALTER TABLE `tcredential_store` MODIFY COLUMN `product` enum('CUSTOM', 'AWS', 'AZURE', 'GOOGLE', 'SAP') default 'CUSTOM';
ALTER TABLE `tevent_filter` ADD COLUMN `id_source_event` int(10);


ALTER TABLE `tmetaconsole_agent_secondary_group` ADD INDEX `id_tagente` (`id_tagente`);
ALTER TABLE `tmetaconsole_event` ADD INDEX `server_id` (`server_id`);

COMMIT;
