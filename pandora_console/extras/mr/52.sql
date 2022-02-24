START TRANSACTION;

ALTER TABLE `tpolicy_queue` MODIFY COLUMN `progress` int(10) NOT NULL default '0';
CREATE INDEX `IDX_tservice_element` ON `tservice_element`(`id_service`,`id_agente_modulo`);
ALTER TABLE `tusuario` ADD COLUMN `local_user` tinyint(1) unsigned NOT NULL DEFAULT 0;
ALTER TABLE tevent_response ADD COLUMN display_command tinyint(1) default 0;

ALTER TABLE `talert_execution_queue` 
  DROP COLUMN `id_alert_template_module`,
  DROP COLUMN `alert_mode`,
  DROP COLUMN `extra_macros`,
  MODIFY COLUMN `data` LONGTEXT;

ALTER TABLE `talert_templates` ADD COLUMN `schedule` TEXT;
ALTER TABLE `tevent_alert` ADD COLUMN `schedule` TEXT;

ALTER TABLE `tautoconfig` ADD COLUMN `disabled` TINYINT DEFAULT 0;

COMMIT;
