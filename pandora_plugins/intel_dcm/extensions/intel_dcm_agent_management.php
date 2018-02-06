<?php
// ______                 __                     _______ _______ _______
//|   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
//|    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
//|___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================

include_once ('intel_dcm/intel_dcm_lib.php');

extensions_add_godmode_tab_agent ('intel_dcm_agent_management', 'Intel DCM Agent Setup' , ENTERPRISE_DIR."/extensions/intel_dcm/intel_blue_small.png","main_intel_dcm_agent_management");


function main_intel_dcm_agent_management () {
	global $config;
	
	$table->width = '98%';
	$table->data = array ();	
		
	$type = get_parameter("type");	
	$connection_name = get_parameter("connection_name");
	$derated_power = get_parameter("derated_power");	
	$action = get_parameter("action");
	$del = get_parameter("delete_button");
	$id_agent = get_parameter("id_agente");
	$dcm_id = get_parameter("dcm_id");
	
	$id_field = create_custom_field();	
	
	$id_field_derated_power = create_derated_power_custom_field();
	
	if ($del) {
		$action = "delete";
	}

	switch ($action) {
		case "create":
		case "update";

			if (!$type || !$connection_name || !$derated_power) {
				ui_print_error_message (__('The fields: Type, Connection Name and Derrated Power are requiered'));
				
			} else {
						
				$agent_name = io_safe_output(agents_get_name ($id_agent));
				$agent_address = agents_get_address($id_agent);
											
				if ($action == "create") {
					
					$values = array("type" => $type,
								"value" => $agent_name,
								"address" => $agent_address,
								"derated_power" => $derated_power,
								"connector" => $connection_name);					
				
					$res = exec_dcm_action("add_entity", $values);

					if ($res) {
						set_dcm_id ($id_agent, $id_field, $res);				
						
						set_dcm_derated_power($id_agent, $id_field_derated_power, $derated_power);
										
						create_query_modules($id_agent, $res);
						
						create_metric_modules($id_agent, $res);
						
					}
					
					ui_print_result_message ($res,
					__('DCM Entity created'),
					__('Error creating DCM Entity'));
					
				} else {
					$values = array("value" => $agent_name,
								"address" => $agent_address,
								"derated_power" => $derated_power,
								"entity_id" => $dcm_id);					
				
					$res = exec_dcm_action("update_entity", $values);
					
					if ($res) {
						set_dcm_derated_power($id_agent, $id_field_derated_power, $derated_power);
					}
					
					ui_print_result_message ($res,
					__('DCM Entity updated'),
					__('Error updating DCM Entity'));
										
				}
			}
			
			break;

		case "delete":
			$res = exec_dcm_action("delete_entity", array("entity_id" => $dcm_id));
			
			if ($res) {
				$res = db_process_sql_delete("tagent_custom_data", "id_field = $id_field AND id_agent = $id_agent");
				$res = db_process_sql_delete("tagent_custom_data", "id_field = $id_field_derated_power AND id_agent = $id_agent");
			}
			
			ui_print_result_message ($res,
			__('DCM Entity deleted'),
			__('Error deleting DCM Entity'));
			
			$type = "";
			$connection_name = "";
			$derated_power = "";
			break;
	}
	

	$type_values = array("NODE" => "Server",
						"ENCLOSURE" => "Enclosure",
						"RACK" => "Rack",
						"ROW" => "Row",
						"ROOM" => "Room",
						"DATACENTER" => "Datacenter",
						"LOGICAL_GROUP" => "Logical Group",
						"DCM_SERVER" => "DCM Server");
				
	$connector_str = exec_dcm_action("connector_list");
	$connector_list = array();
	if ($connector_str && !$connector_str == null) {
		
		$connector_list = explode ("|", $connector_str);
	} else {
		ui_print_error_message (__('Error getting connector list'));
	}
	
	$connection_values = array();
	
	if ($connector_list) {
	
		foreach ($connector_list as $connector) {
			
			$aux = explode(":", $connector);
			
			$aux[0] = trim($aux[0] , "\"");
			$aux[1] = trim($aux[1], "\"");
			
			$connection_values[$aux[1]] = $aux[0];
		}
	}
	
	//Get dcm agent data
	$sql = "SELECT id_field FROM tagent_custom_fields WHERE name = 'DCM_Entity_Id'";
	$id_field = db_get_value_sql($sql);
	$sql = "SELECT description FROM tagent_custom_data WHERE id_field = $id_field AND id_agent = $id_agent";
	$dcm_id = db_get_value_sql($sql);
	
	//If we have a DCM ID then get information from DCM Server
	if ($dcm_id) {
		
		$entity_str = exec_dcm_action("entity_properties", array("entity_id" => $dcm_id));
			
		if (!$entity_str) {
			ui_print_error_message (__('Error getting entity information'));
		} else {
			$entity_properties = explode ("|", $entity_str);
			
			foreach ($entity_properties as $prop) {
				
				$aux = explode(":", $prop);
				
				$aux[0] = trim($aux[0] , "\"");
				$aux[1] = trim($aux[1], "\"");

				switch($aux[0]) {
					case "CONNECTOR_NAME":
						$connection_name = $aux[1];
						break;
					case "DERATED_PWR":
						$derated_power = $aux[1];
						break;
					case "DEVICE_TYPE":
						$type = $aux[1];
						break;
				}
			}
		}
		
	}

	$table->data[0][0] = __('Type');

	$table->data[0][1] = html_print_select ($type_values, "type", $type, '', '', '', true);	
	
	$table->data[1][0] = __('Connection Name');	

	$table->data[1][1] = html_print_select ($connection_values, "connection_name", $connection_name, '', '', '', true);			
	
	$table->data[2][0] = __('Derated Power');
	
	$table->data[2][1] = html_print_input_text ('derated_power', $derated_power, '', 30, 100, true);
	
	echo '<form id="form_setup" method="post">';
	
	if ($dcm_id) {
		echo "<br>";
		echo '<div class="action-buttons" style="width: '.$table->width.'">';		
		html_print_input_hidden ('dcm_id', $dcm_id);			
		html_print_submit_button (__('Delete Entity'), 'delete_button', false, 'class="sub delete"');	
		echo '</div>';
	}	
	
	html_print_table ($table);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	
	if ($dcm_id) {
		html_print_input_hidden ('action', "update");	
		html_print_input_hidden ('dcm_id', $dcm_id);			
		html_print_submit_button (__('Update Entity'), 'update_button', false, 'class="sub upd"');
	} else {
		html_print_input_hidden ('action', "create");	
		html_print_submit_button (__('Create Entity'), 'create_button', false, 'class="sub wand"');
	}
	echo '</div>';
	
	echo '</form>';		
}
?>
