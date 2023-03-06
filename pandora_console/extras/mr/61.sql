START TRANSACTION;

ALTER TABLE `tserver` ADD COLUMN `server_keepalive_utimestamp` BIGINT NOT NULL DEFAULT 0;

ALTER TABLE `tmap` MODIFY COLUMN `id_group` TEXT NOT NULL default '';

CREATE TABLE IF NOT EXISTS `tmonitor_filter` ( 
  `id_filter`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_name` VARCHAR(600) NOT NULL,
  `id_group_filter` INT NOT NULL DEFAULT 0,
  `ag_group` INT NOT NULL DEFAULT 0,
  `recursion` TEXT,
  `status` INT NOT NULL DEFAULT -1,
  `ag_modulename` TEXT,
  `ag_freestring` TEXT,
  `tag_filter` TEXT,
  `moduletype` TEXT,
  `module_option` INT DEFAULT 1,
  `modulegroup` INT NOT NULL DEFAULT -1,
  `min_hours_status` TEXT,
  `datatype` TEXT,
  `not_condition` TEXT,
  `ag_custom_fields` TEXT,
  PRIMARY KEY (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tsesion_filter` (
    `id_filter` INT NOT NULL AUTO_INCREMENT,
    `id_name` TEXT NULL,
    `text` TEXT NULL,
    `period` TEXT NULL,
    `ip` TEXT NULL,
    `type` TEXT NULL,
    `user` TEXT NULL,
    PRIMARY KEY (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE INDEX `tusuario_perfil_user` ON `tusuario_perfil` (`id_usuario`);
CREATE INDEX `tusuario_perfil_group` ON `tusuario_perfil` (`id_grupo`);
CREATE INDEX `tusuario_perfil_profile` ON `tusuario_perfil` (`id_perfil`);
CREATE INDEX `tlayout_data_layout` ON `tlayout_data` (`id_layout`);
CREATE INDEX `taddress_agent_agent` ON `taddress_agent` (`id_agent`);
CREATE INDEX `ttag_name` ON `ttag` (name(15));
CREATE INDEX `tservice_element_service` ON `tservice_element` (`id_service`);
CREATE INDEX `tservice_element_agent` ON `tservice_element` (`id_agent`);
CREATE INDEX `tservice_element_am` ON `tservice_element` (`id_agente_modulo`);
CREATE INDEX `tagent_module_log_agent` ON `tagent_module_log` (`id_agent`);

COMMIT;
