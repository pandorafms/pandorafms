START TRANSACTION;

ALTER TABLE `tgrupo` ADD COLUMN `max_agents` int(10) NOT NULL DEFAULT 0;
ALTER TABLE `tagent_custom_fields` MODIFY COLUMN `combo_values` TEXT NOT NULL DEFAULT '';
ALTER TABLE `treport_content` MODIFY `external_source` MEDIUMTEXT;
ALTER TABLE `treport_content_template` MODIFY `external_source` MEDIUMTEXT;

ALTER TABLE `tevent_rule` MODIFY COLUMN `agent` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `id_usuario` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `id_grupo` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `evento` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `event_type` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `module` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `alert` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `criticity` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `user_comment` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `id_tag` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `name` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `group_recursion` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `log_content` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `log_source` TEXT;
ALTER TABLE `tevent_rule` MODIFY COLUMN `log_agent` TEXT;
ALTER TABLE tevent_filter ADD COLUMN `server_id` int(10) NOT NULL default 0;

UPDATE `talert_commands` SET `fields_descriptions` = '[\"Event&#x20;name\",\"Event&#x20;type\",\"Source\",\"Agent&#x20;name&#x20;or&#x20;_agent_\",\"Event&#x20;severity\",\"ID&#x20;extra\",\"Tags&#x20;separated&#x20;by&#x20;commas\",\"Comments\",\"\",\"\"]' WHERE `name` = "Monitoring&#x20;Event";
UPDATE `tskin` SET `name` = 'Default&#x20;theme' , `relative_path` = 'pandora.css' WHERE `id` = 1;
UPDATE `tskin` SET `name` = 'Black&#x20;theme' , `relative_path` = 'Black&#x20;theme' , `description` = 'Black&#x20;theme' WHERE `id` = 2;

UPDATE `tevent_rule` SET `criticity` = NULL, `operator_criticity` = NULL WHERE `criticity` = 99;
UPDATE `tevent_rule` SET `id_grupo` = NULL, `operator_id_grupo` = NULL WHERE `id_grupo` = 0;
UPDATE `tevent_rule` SET `id_tag` = NULL, `operator_id_tag` = NULL WHERE `id_tag` = 0;
UPDATE `tevent_rule` SET `operator_criticity` = '==' WHERE `criticity` != 99 AND `criticity` IS NOT NULL AND `criticity` != '';
UPDATE `tevent_rule` SET `operator_id_grupo` = '==' WHERE `id_grupo` != 0 AND `id_grupo` IS NOT NULL AND `id_grupo` != '';
UPDATE `tevent_rule` SET `operator_id_tag` = '==' WHERE `id_tag` != 0 AND `id_tag` IS NOT NULL AND `id_tag` != '';

COMMIT;
