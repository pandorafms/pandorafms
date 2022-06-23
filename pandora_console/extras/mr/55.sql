START TRANSACTION;

ALTER TABLE `tuser_double_auth` DROP FOREIGN KEY `tuser_double_auth_ibfk_1`, MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tnotification_user` DROP FOREIGN KEY `tnotification_user_ibfk_2`, MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tnotification_source_user` DROP FOREIGN KEY `tnotification_source_user_ibfk_2`, MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tnotification_source_group_user` DROP FOREIGN KEY `tnotification_source_group_user_ibfk_2`, MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tvisual_console_elements_cache` DROP FOREIGN KEY `tvisual_console_elements_cache_ibfk_3`, MODIFY COLUMN `user_id` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `tusuario` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `tuser_double_auth` ADD CONSTRAINT `tuser_double_auth_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE;
ALTER TABLE `tnotification_user` ADD CONSTRAINT `tnotification_user_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `tnotification_source_user` ADD CONSTRAINT `tnotification_source_user_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `tnotification_source_group_user` ADD CONSTRAINT `tnotification_source_group_user_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `tvisual_console_elements_cache` ADD CONSTRAINT `tvisual_console_elements_cache_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `tattachment` MODIFY COLUMN `id_usuario` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `tevento` MODIFY COLUMN `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `tincidencia` MODIFY COLUMN `id_usuario` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `tnota` MODIFY COLUMN `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `tsesion` MODIFY COLUMN `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `ttrap` MODIFY COLUMN `id_usuario` VARCHAR(255) DEFAULT '';
ALTER TABLE `tusuario_perfil` MODIFY COLUMN `id_usuario` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `treset_pass_history` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tmensajes` MODIFY COLUMN `id_usuario_origen` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `tgraph` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `treport` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `tplanned_downtime` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `tnetwork_map` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tpassword_history` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tupdate_journal` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `tmap` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `tdashboard` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `treport_template` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `tmetaconsole_event` MODIFY COLUMN `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `tmetaconsole_event_history` MODIFY COLUMN `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `treset_pass` MODIFY COLUMN `id_user` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `tuser_task_scheduled` MODIFY COLUMN `id_usuario` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `tbackup` MODIFY COLUMN `id_user` VARCHAR(255) DEFAULT '';

ALTER TABLE `tservice` ADD COLUMN `enable_sunburst` TINYINT NOT NULL DEFAULT 0;
ALTER TABLE `tservice` ADD COLUMN `asynchronous` TINYINT NOT NULL DEFAULT 0;
ALTER TABLE `tservice` ADD COLUMN `rca` TEXT;

ALTER TABLE `tdashboard` MODIFY `name` TEXT NOT NULL DEFAULT '';

ALTER TABLE `tevent_alert` ADD COLUMN `field1_recovery` TEXT AFTER `recovery_notify`;
ALTER TABLE `tevent_alert` ADD COLUMN `field4_recovery` TEXT AFTER `field3_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field5_recovery` TEXT AFTER `field4_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field6_recovery` TEXT AFTER `field5_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field7_recovery` TEXT AFTER `field6_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field8_recovery` TEXT AFTER `field7_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field9_recovery` TEXT AFTER `field8_recovery`;
ALTER TABLE `tevent_alert` ADD COLUMN `field10_recovery` TEXT AFTER `field9_recovery`;

ALTER TABLE `tagente` ADD COLUMN `satellite_server` INT NOT NULL default 0;
ALTER TABLE `tmetaconsole_agent` ADD COLUMN `satellite_server` INT NOT NULL default 0;

ALTER TABLE `tmetaconsole_setup` DROP COLUMN `last_event_replication`;
DELETE FROM `tconfig` WHERE `token` LIKE 'event_replication';
DELETE FROM `tconfig` WHERE `token` LIKE 'replication_interval';
DELETE FROM `tconfig` WHERE `token` LIKE 'replication_limit';
DELETE FROM `tconfig` WHERE `token` LIKE 'replication_mode';
DELETE FROM `tconfig` WHERE `token` LIKE 'show_events_in_local';

UPDATE tconfig SET value='' WHERE token='lts_name';

SET @st_oum763 = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'tautoconfig' AND table_schema = DATABASE() AND column_name = 'disabled') > 0,
    "SELECT 1",
    "ALTER TABLE `tautoconfig` ADD COLUMN `disabled` TINYINT DEFAULT 0"
));

PREPARE pr_oum763 FROM @st_oum763;
EXECUTE pr_oum763;
DEALLOCATE PREPARE pr_oum763;

COMMIT;

