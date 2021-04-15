START TRANSACTION;

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

UPDATE `talert_commands` SET `fields_descriptions` = '[\"Event&#x20;name\",\"Event&#x20;type\",\"Source\",\"Agent&#x20;name&#x20;or&#x20;_agent_\",\"Event&#x20;severity\",\"ID&#x20;extra\",\"Tags&#x20;separated&#x20;by&#x20;commas\",\"Comments\",\"\",\"\"]' WHERE `name` = "Monitoring&#x20;Event";

UPDATE `tskin` SET `name` = 'Default&#x20;theme' , `relative_path` = 'pandora.css' WHERE `id` = 1;
UPDATE `tskin` SET `name` = 'Black&#x20;theme' , `relative_path` = 'Black&#x20;theme' , `description` = 'Black&#x20;theme' WHERE `id` = 2;

COMMIT;
