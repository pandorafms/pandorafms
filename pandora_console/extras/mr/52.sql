START TRANSACTION;

ALTER TABLE `tpolicy_queue` MODIFY COLUMN `progress` int(10) NOT NULL default '0';
CREATE INDEX `IDX_tservice_element` ON `tservice_element`(`id_service`,`id_agente_modulo`);
ALTER TABLE `tusuario` ADD COLUMN `local_user` tinyint(1) unsigned NOT NULL DEFAULT 0;

COMMIT;