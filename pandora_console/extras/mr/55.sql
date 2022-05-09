START TRANSACTION;

ALTER TABLE `tuser_double_auth` DROP FOREIGN KEY `tuser_double_auth_ibfk_1`, MODIFY `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tnotification_user` DROP FOREIGN KEY `tnotification_user_ibfk_2`, MODIFY `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tnotification_source_user` DROP FOREIGN KEY `tnotification_source_user_ibfk_2`, MODIFY `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tnotification_source_group_user` DROP FOREIGN KEY `tnotification_source_group_user_ibfk_2`, MODIFY `id_user` VARCHAR(255) NOT NULL;
ALTER TABLE `tvisual_console_elements_cache` DROP FOREIGN KEY `tvisual_console_elements_cache_ibfk_3`, MODIFY `user_id` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `tusuario` MODIFY `id_user` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `tuser_double_auth` ADD CONSTRAINT `tuser_double_auth_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE;
ALTER TABLE `tnotification_user` ADD CONSTRAINT `tnotification_user_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `tnotification_source_user` ADD CONSTRAINT `tnotification_source_user_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `tnotification_source_group_user` ADD CONSTRAINT `tnotification_source_group_user_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `tvisual_console_elements_cache` ADD CONSTRAINT `tvisual_console_elements_cache_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `tusuario` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;
