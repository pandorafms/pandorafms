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

extensions_add_opemode_tab_agent ('intel_dcm_agent_view', 'Intel DCM Agent View' , ENTERPRISE_DIR."/extensions/intel_dcm/intel_blue_small.png","main_intel_dcm_agent_view");


function main_intel_dcm_agent_view () {
	global $config;
	
	$id_agent = get_parameter("id_agente");
	$id_field = create_custom_field();	
	$sql = "SELECT description FROM tagent_custom_data WHERE id_field = $id_field AND id_agent = $id_agent";
	$dcm_id = db_get_value_sql($sql);	
	
	
	if (!$dcm_id) {
		ui_print_error_message (__('This agent hasn\'t got a DCM entity associated'));
		return;
	}
			
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "800px";
	$table->class = "databox";
	
	$table->head = array();
	$table->data = array();
	
	$table->head[0] = __('Power stats');
	$table->head[1] = __('Average power demand'); 
	
	$data = array();
	
	$data_period = 604800;
	
	$id_agent = get_parameter("id_agente");
	$agent_interval = agents_get_interval($id_agent);
	
	//Caculate current power stats
	$module = modules_get_agentmodule_id (io_safe_input("Avg. Power"), $id_agent);	
	$avg_power = modules_get_last_value ($module['id_agente_modulo']);
	
	
	
	$module = modules_get_agentmodule_id (io_safe_input("Avg. Inlet Temperature"), $id_agent);	
	$avg_temp = modules_get_last_value ($module['id_agente_modulo']);
		
	$module = modules_get_agentmodule_id (io_safe_input("Managed Nodes Energy"), $id_agent);
	$aux = modules_get_agentmodule_data ($module['id_agente_modulo'], $data_period);

	$num = 0;
	$sum = 0;
	foreach ($aux as $v) {
		if ($v["data"] > 0) {
			$num++;			
			$sum = $sum +$v["data"];
		}
	}
	$avg = 0;
	if ($num != 0) {
		$avg = $sum/$num;
	} else {
		$avg = $sum;
	}	
	$mnged_energy = $avg;
	
	$module = modules_get_agentmodule_id (io_safe_input("Managed Nodes Energy Bill"), $id_agent);
	$aux = modules_get_agentmodule_data ($module['id_agente_modulo'], $data_period);
	
	$num = 0;
	$sum = 0;
	foreach ($aux as $v) {
		if ($v["data"] > 0) {
			$num++;			
			$sum = $sum +$v["data"];
		}
	}
	$avg = 0;
	if ($num != 0) {
		$avg = $sum/$num;
	} else {
		$avg = $sum;
	}	
	$mnged_energy_bill = $avg;

	$module = modules_get_agentmodule_id (io_safe_input("Calculated Cooling Energy"), $id_agent);
	$aux = modules_get_agentmodule_data ($module['id_agente_modulo'], $data_period);
	
	$num = 0;
	$sum = 0;
	foreach ($aux as $v) {
		if ($v["data"] > 0) {
			$num++;			
			$sum = $sum +$v["data"];
		}
	}
	$avg = 0;
	if ($num != 0) {
		$avg = $sum/$num;
	} else {
		$avg = $sum;
	}	
	$cooling_energy = $avg;
	
	$module = modules_get_agentmodule_id (io_safe_input("Calculated Cooling Energy Bill"), $id_agent);
	$aux = modules_get_agentmodule_data ($module['id_agente_modulo'], $data_period);

	$num = 0;
	$sum = 0;
	foreach ($aux as $v) {
		if ($v["data"] > 0) {
			$num++;			
			$sum = $sum +$v["data"];
		}
	}
	$avg = 0;
	if ($num != 0) {
		$avg = $sum/$num;
	} else {
		$avg = $sum;
	}	
	$cooling_energy_bill = $avg;
	
	//Calculate power utilization	
	$id_field_derated_power = create_derated_power_custom_field();
	$sql = "SELECT description FROM tagent_custom_data WHERE id_field = $id_field_derated_power AND id_agent = $id_agent";
	$derated_power = db_get_value_sql($sql);	
	
	$percent = number_format(($avg_power/$derated_power)*100, 2);	

	$data[0] = "<b>".__("Power utilization")." $percent%</b>";
	$data[0] .= progress_bar($percent, 400, 30, "", 2);
	$data[0] .= "<br><br>";
	$data[0] .= "<b>".__("Current stats")."</b>";
	$data[0] .= "<br><br>";
	$data[0] .= __("Power demand").": <b>".number_format($avg_power, 2)." Wh</b>";
	$data[0] .= "<br>";
	$data[0] .= __("Inlet temp").": <b>".number_format($avg_temp, 2)." ºC</b>";
	$data[0] .= "<br><br><br>";
	$data[0] .= "<b>".__("Last week summary")."</b>";
	$data[0] .= "<br><br>";
	$data[0] .= __("Equipment energy consumed").": <b>".number_format($mnged_energy, 2)." Wh</b>";
	$data[0] .= "<br>";
	$data[0] .= __("Equipment energy bill").": <b>".number_format($mnged_energy_bill, 2)." €</b>";
	$data[0] .= "<br>";
	$data[0] .= __("Calculated cooling energy").": <b>".number_format($cooling_energy, 2)." Wh</b>";
	$data[0] .= "<br>";
	$data[0] .= __("Calculated cooling energy bill").": <b>".number_format($cooling_energy_bill, 2)." €</b>";	
	
	//Print avg. power graph		
	$start_date = date("Y-m-d");
	$date = get_system_time ();
	$draw_events = false;
	$draw_alerts = false;
	$period = 7200;

	$module = modules_get_agentmodule_id (io_safe_input("Avg. Power"), $id_agent);		
	$unit = modules_get_unit ($module['id_agente_modulo']);

	$params_1 =array(
		'agent_module_id'     => $module['id_agente_modulo'],
		'period'              => 7200,
		'show_events'         => $draw_events,
		'width'               => 400,
		'height'              => 250,
		'title'               => $module['nombre'],
		'unit_name'           => null,
		'show_alerts'         => $draw_alerts,
		'pure'                => false,
		'date'                => $date,
		'unit'                => $unit
	);

	$data[1] = grafico_modulo_sparse($params_1);
		
	array_push ($table->data, $data);
		
	echo "<center>";
	html_print_table($table);
	echo "</center>";
	
	
	$table->head = array();
	$table->data = array();
	
	
	$table->head[0] = __("Average inlet temperature");
	$table->head[1] = __("Calculated cooling power");
	
	$data = array();
	
	$module = modules_get_agentmodule_id (io_safe_input("Avg. Inlet Temperature"), $id_agent);		
	$unit = modules_get_unit ($module['id_agente_modulo']);
	$params_2 =array(
		'agent_module_id'     => $module['id_agente_modulo'],
		'period'              => 7200,
		'show_events'         => $draw_events,
		'width'               => 400,
		'height'              => 250,
		'title'               => $module['nombre'],
		'unit_name'           => null,
		'show_alerts'         => $draw_alerts,
		'pure'                => false,
		'date'                => $date,
		'unit'                => $unit
	);
	$data[0] = grafico_modulo_sparse($params_2);

	$module = modules_get_agentmodule_id (io_safe_input("Calculated Cooling Power"), $id_agent);		
	$unit = modules_get_unit ($module['id_agente_modulo']);

	$params_3 =array(
		'agent_module_id'     => $module['id_agente_modulo'],
		'period'              => 7200,
		'show_events'         => $draw_events,
		'width'               => 400,
		'height'              => 250,
		'title'               => $module['nombre'],
		'unit_name'           => null,
		'show_alerts'         => $draw_alerts,
		'pure'                => false,
		'date'                => $date,
		'unit'                => $unit
	);

	$data[1] = grafico_modulo_sparse($params_3);

	array_push ($table->data, $data);

	echo "<center>";
	html_print_table($table);
	echo "</center>";
}
?>
