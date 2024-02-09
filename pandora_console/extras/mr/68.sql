START TRANSACTION;

-- Update version for plugin oracle
UPDATE `tdiscovery_apps` SET `version` = '1.2' WHERE `short_name` = 'pandorafms.oracle';

ALTER TABLE `tncm_agent_data`
ADD COLUMN `id_agent_data` int not null default 0 AFTER `script_type`;

ALTER TABLE `tusuario` CHANGE COLUMN `metaconsole_data_section` `metaconsole_data_section` TEXT NOT NULL DEFAULT '' ;

ALTER TABLE `tmensajes` ADD COLUMN `icon_notification` VARCHAR(250) NULL DEFAULT NULL AFTER `url`;

ALTER TABLE `tdemo_data` MODIFY `item_id` TEXT;

UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_os": "',`item_id`,'"}') WHERE `table_name` = "tconfig_os" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_agente": "',`item_id`,'"}') WHERE `table_name` = "tagente" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_grupo": "',`item_id`,'"}') WHERE `table_name` = "tgrupo" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_agente_modulo": "',`item_id`,'"}') WHERE `table_name` = "tagente_modulo" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_module_inventory": "',`item_id`,'"}') WHERE `table_name` = "tmodule_inventory" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_agent_module_inventory": "',`item_id`,'"}') WHERE `table_name` = "tagent_module_inventory" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_graph": "',`item_id`,'"}') WHERE `table_name` = "tgraph" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tmap" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_report": "',`item_id`,'"}') WHERE `table_name` = "treport" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_rc": "',`item_id`,'"}') WHERE `table_name` = "treport_content" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "treport_content_sla_combined" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tservice" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tservice_element" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_trap": "',`item_id`,'"}') WHERE `table_name` = "ttrap" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "titem" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_gs": "',`item_id`,'"}') WHERE `table_name` = "tgraph_source" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "twidget_dashboard" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tdashboard" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tlayout" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tlayout_data" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_agente_estado": "',`item_id`,'"}') WHERE `table_name` = "tagente_estado" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "trel_item" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id": "',`item_id`,'"}') WHERE `table_name` = "tplugin" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"tagente_id_agente": "',`item_id`,'"}') WHERE `table_name` = "tgis_data_status" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_tgis_map": "',`item_id`,'"}') WHERE `table_name` = "tgis_map" AND CAST(`item_id` AS UNSIGNED) != 0;
UPDATE `tdemo_data` SET `item_id` = CONCAT('{"id_tmap_layer": "',`item_id`,'"}') WHERE `table_name` = "tgis_map_layer" AND CAST(`item_id` AS UNSIGNED) != 0;

COMMIT;