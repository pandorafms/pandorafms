START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tsync_queue` (
	`id` serial,
	`sql` MEDIUMTEXT,
	`target` bigint(20) unsigned NOT NULL,
	`utimestamp` bigint(20) default '0',
	`operation` text,
	`table` text,
	`error` MEDIUMTEXT,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SOURCE './procedures/updateSnmpAlerts.sql';

UPDATE `tlink` SET `link` = 'https://pandorafms.com/manual/' WHERE `id_link` = 0000000001;

UPDATE pandora.tuser_task
SET parameters='a:7:{i:0;a:7:{s:11:"description";s:30:"Template pending to be created";s:5:"table";s:16:"treport_template";s:8:"field_id";s:9:"id_report";s:10:"field_name";s:4:"name";s:8:"required";b:1;s:4:"type";s:3:"int";s:9:"acl_group";s:8:"id_group";}i:1;a:7:{s:11:"description";s:6:"Agents";s:5:"table";s:7:"tagente";s:8:"field_id";s:9:"id_agente";s:10:"field_name";s:6:"nombre";s:8:"multiple";b:1;s:4:"type";s:3:"int";s:9:"acl_group";s:8:"id_grupo";}i:2;a:2:{s:11:"description";s:16:"Report per agent";s:10:"select_two";b:1;}i:3;a:2:{s:11:"description";s:11:"Report name";s:4:"type";s:6:"string";}i:4;a:2:{s:11:"description";s:47:"Send to e-mail addresses (separated by a comma)";s:4:"type";s:4:"text";}i:5;a:2:{s:11:"description";s:7:"Subject";s:8:"optional";i:1;}i:6;a:3:{s:11:"description";s:7:"Message";s:4:"type";s:4:"text";s:8:"optional";i:1;}}i:7;a:2:{s:11:"description";s:11:"Report Type";s:4:"type";s:11:"report_type";}}'
WHERE id=2;

UPDATE `tuser_task_scheduled` SET 
    `args` = REPLACE (`args`, 'a:8', 'a:9'),
    `args`= REPLACE(`args`, 's:15:"first_execution"', 'i:2;s:0:"";i:7;s:3:"PDF";s:15:"first_execution"')
    WHERE `id_user_task` = 2;

COMMIT;
