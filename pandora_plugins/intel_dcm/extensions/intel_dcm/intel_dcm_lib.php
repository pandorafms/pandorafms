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

function create_metric_modules($id_agent, $dcm_id) {
	global $config;
	
	$modules_array = array(
		array("name" => "Managed Nodes Energy",
			"desc" => "The total energy consumed by all managed nodes in the specified entity, in Wh",
			"value" => "mnged_nodes_energy"),										
		array("name" => "Managed Nodes Energy Bill",
			"desc" => "The total power bill for all energy consumed by all managed nodes in the specified entity",
			"value" => "mnged_nodes_energy_bill"),			
		array("name" => "IT Equipment Energy",
			"desc" => "The total energy consumed by IT equipment, including managed nodes, unmanaged nodes and other IT equipment in the selected entity, in Wh",
			"value" => "it_eqpmnt_energy"),
		array("name" => "IT Equipment Energy Bill",
			"desc" => "The calculated power bill for IT equipment, including managed nodes, unmanaged nodes and other IT equipment in the selected entity",
			"value" => "it_eqpmnt_energy_bill"),					
		array("name" => "Calculated Cooling Energy",
			"desc" => "The energy needed to cool the selected entity, in Wh",
			"value" => "calc_cooling_energy"),			
		array("name" => "Calculated Cooling Energy Bill",
			"desc" => "The calculated power bill for the energy needed to cool the selected entity",
			"value" => "calc_cooling_energy_bill"),				
		array("name" => "Managed Nodes Power",
			"desc" => "The total average power consumption by the managed nodes in the selected entity, in watts",
			"value" => "mnged_nodes_pwr"),	
		array("name" => "IT Equipment Power",
			"desc" => "Provides the total average power consumption by IT equipment, including managed nodes, unmanaged nodes and other IT equipment in the selected entity in watts",
			"value" => "it_eqpmnt_pwr"),	
		array("name" => "Calculated Cooling Power",
			"desc" => "Provides the average cooling power based on the IT_EQPMNT_PWR multiplied by COOLING_MULT in watts",
			"value" => "calc_cooling_pwr"),	
		array("name" => "Avg. Power Per Dimension",
			"desc" => "The average power consumption per dimension",
			"value" => "avg_pwr_per_dimension"),	
		array("name" => "Derated power",
			"desc" => "Adds the de-rated values of all the nodes in the entity to the nameplate power value of all unmanaged nodes and equipment associated with the entity, as defined by NAMEPLATE_PWR_UNMNGD_EQPMNT",
			"value" => "derated_pwr"),		
		array("name" => "Inlet Temperature Span",
			"desc" => "The average inlet temperature differential between the highest and lowest node temperature in a group (degC/degF)",
			"value" => "inlet_temperature_span"),		
		);
		
			
	$plugin_action = "--action \'metric_data\' --entity_id \'".$dcm_id."\'";
	$plugin_name = io_safe_input("Intel DCM Plugin");
	$id_plugin = db_get_value("id", "tplugin", "name", $plugin_name);

	foreach ($modules_array as $mod) {

		$aux_params = $plugin_action." --value \'".$mod['value']."\'";

		$values = array ('id_tipo_modulo' => 1,
				'descripcion' => $mod['desc'], 
				'tcp_port' => $config['dcm_port'],
				'ip_target' => $config['dcm_server'],
				'plugin_parameter' => $aux_params,
				'id_plugin' => $id_plugin, 
				'max_timeout' => 300, 
				'id_modulo' => 4);

		modules_create_agent_module ($id_agent, io_safe_input($mod['name']), $values);
	}	
}


function create_query_modules($id_agent, $dcm_id) {
	global $config;
	
	$modules_array = array(
		array("name" => "Max. Power",
			"desc" => "The maximum power consumed by any single node/enclosure",
			"value" => "max_pwr"),
		array("name" => "Avg. Power",
			"desc" => "The average power consumption across all nodes/enclosures",
			"value" => "avg_pwr"),
		array("name" => "Min. Power",
			"desc" => "The minimum power consumed by any single node/enclosure",
			"value" => "min_pwr"),												
		array("name" => "Max. Avg. Power",
			"desc" => "The maximum of group sampling (in a monitoring cycle) power in specified aggregation period for the sum of average power measurement in a group of nodes/enclosures within the specified entity",
			"value" => "max_avg_pwr"),
		array("name" => "Total Max. Power",
			"desc" => "The maximum of group sampling (in a monitoring cycle) power in specified aggregation period for sum of maximum power measurement in a group of nodes/enclosures within the specified entity",
			"value" => "total_max_pwr"),
		array("name" => "Total Avg. Power",
			"desc" => "The average (in specified aggregation period) of group power for sum of average power measurement in a group of nodes/enclosures within the specified entity",
			"value" => "total_avg_pwr"),													
		array("name" => "Max. Avg. Power Capping",
			"desc" => "The maximum of group sampling (in a monitoring cycle) power in specified aggregation period for the sum of average power measurement in a group of nodes/enclosures with power capping capability",
			"value" => "max_avg_pwr_cap"),
		array("name" => "Total Max. Power Capping",
			"desc" => "The maximum group sampling (in a monitoring cycle) power in specified aggregation period for sum of maximum power measurement in a group of nodes/enclosures with power capping capability",
			"value" => "total_max_pwr_cap"),																						
		array("name" => "Total Avg. Power Capping",
			"desc" => "The average (in specified aggregation period) of group power for sum of average power measurement in a group of nodes/enclosures with power capping capability",
			"value" => "total_avg_pwr_cap"),											
		array("name" => "Total Min. Power",
			"desc" => "The minimal group sampling (in a monitoring cycle) power in specified aggregation period for sum of minimum power measurement in a group of nodes/enclosures within the specified entity",
			"value" => "total_min_pwr"),											
		array("name" => "Min. Avg. Power",
			"desc" => "The minimal group sampling (in a monitoring cycle) power in specified aggregation period for sum of average power measurement in a group of nodes/enclosures within the specified entity",
			"value" => "min_avg_pwr"),											
		array("name" => "Max. Inlet Temperature",
			"desc" => "The maximum temperature for any single node within the specified entity",
			"value" => "max_inlet_temp"),											
		array("name" => "Avg. Inlet Temperature",
			"desc" => "The average temperature for any single node within the specified entity",
			"value" => "avg_inlet_temp"),											
		array("name" => "Min. Inlet Temperature",
			"desc" => "The minimum temperature for any single node within the specified entity",
			"value" => "min_inlet_temp"),											
		array("name" => "Instantaneous Power",
			"desc" => "The instantaneous power consumption of a specified node/enclosure or the sum of the instantaneous power of the nodes/enclosures within the specified entity",
			"value" => "ins_pwr"),												
		);
	

	$plugin_action = "--action \'query_data\' --entity_id \'".$dcm_id."\'";
	$plugin_name = io_safe_input("Intel DCM Plugin");
	$id_plugin = db_get_value("id", "tplugin", "name", $plugin_name);

	foreach ($modules_array as $mod) {

		$aux_params = $plugin_action." --value \'".$mod['value']."\'";

		$values = array ('id_tipo_modulo' => 1,
				'descripcion' => $mod['desc'], 
				'tcp_port' => $config['dcm_port'],
				'ip_target' => $config['dcm_server'],
				'plugin_parameter' => $aux_params,
				'id_plugin' => $id_plugin, 
				'max_timeout' => 300, 
				'id_modulo' => 4);

		modules_create_agent_module ($id_agent, io_safe_input($mod['name']), $values);
	}	
}

function create_custom_field () {
	
	$result = db_get_value("id_field", "tagent_custom_fields", "name", "DCM_Entity_Id");
	
	if (!$result) {
	
		$result = db_process_sql_insert('tagent_custom_fields', array('name' => "DCM_Entity_Id", 'display_on_front' => 0));	
		
	}
	
	return $result;
}

function create_derated_power_custom_field () {
	
	$result = db_get_value("id_field", "tagent_custom_fields", "name", "DCM_Entity_Derated_Power");
	
	if (!$result) {
	
		$result = db_process_sql_insert('tagent_custom_fields', array('name' => "DCM_Entity_Derated_Power", 'display_on_front' => 0));	
		
	}
	
	return $result;
}

function set_dcm_id ($id_agent, $id_field, $dcm_id) {
	
	$sql = "SELECT description FROM tagent_custom_data WHERE id_field = $id_field AND id_agent = $id_agent";
	$result = db_get_value_sql($sql);

	if (!$result) {
		db_process_sql_insert('tagent_custom_data', array('id_field' => $id_field,'id_agent' => $id_agent, 'description' => $dcm_id));	
		
	} else {
		db_process_sql_update("tagent_custom_data", array('description' => $dcm_id), "id_field = $id_field AND id_agent = $id_agent");
	}
		
}

function set_dcm_derated_power ($id_agent, $id_field, $derated_power) {
	
	$sql = "SELECT description FROM tagent_custom_data WHERE id_field = $id_field AND id_agent = $id_agent";
	$result = db_get_value_sql($sql);

	if (!$result) {
		db_process_sql_insert('tagent_custom_data', array('id_field' => $id_field,'id_agent' => $id_agent, 'description' => $derated_power));	
		
	} else {
		db_process_sql_update("tagent_custom_data", array('description' => $derated_power), "id_field = $id_field AND id_agent = $id_agent");
	}
		
}

function exec_dcm_action ($action, $values=false) {
	global $config;
	
	$plugin_command = db_get_value("execute", "tplugin", "name", io_safe_input("Intel DCM Plugin"));
	
	$plugin_command = io_safe_output($plugin_command);
		
	$command = $plugin_command." --server \"".$config['dcm_server']."\" --port ".$config['dcm_port'];
		
	$command .= " --action \"".$action."\"";
	
	if ($values) {
		
		if (is_array($values)) {
		
			foreach ($values as $key => $val) {
			
				$command .= " --$key \"".$val."\"";
				
			}
			
		} else {
			
			$command .= " --value \"".$values."\"";
		}
		
	}

	$res = shell_exec($command);
	
	return $res;
}

?>
