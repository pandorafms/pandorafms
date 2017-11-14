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

include_once('intel_dcm/intel_dcm_lib.php');

//Check if DCM plugin fields exist in database
function create_tcongif_structures () {

	$aux = db_get_value("value", "tconfig", "token", "dcm_server");
	
	if ($aux === false) {
		db_process_sql_insert("tconfig", array("token" => "dcm_server", 
								"value" => ""));
								
	}
		
	$aux = db_get_value("value", "tconfig", "token", "dcm_port");		
	
	if ($aux === false) {
		db_process_sql_insert("tconfig", array("token" => "dcm_port", 
								"value" => ""));
								
	}	
}

function main_intel_dcm() {
	global $config;
	
	ui_print_page_header (__("Intel DCM Setup"), "images/setup.png", false, "", true, "");	
		
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
		db_pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");

		return;
	}
	
	$activate_monitoring = get_parameter("activate_monitoring");
	$power_interval = get_parameter("power_interval");
	$thermal_interval = get_parameter("thermal_interval");
	$power_granularity = get_parameter("power_granularity");
	$thermal_granularity = get_parameter("thermal_granularity");	
	$multiplier = get_parameter("multiplier");
	$cost = get_parameter("cost");	
	$update = get_parameter("update");
	$dcm_server = get_parameter("dcm_server", "");
	$dcm_port = get_parameter("dcm_port", "");
	
	
	create_tcongif_structures();
			
	//Update info
	if ($update) {
		
		//Info from pandora database	
		
		//Otherwise the field value is updated		
		db_process_sql_update("tconfig", array("value" => $dcm_server), array("token" => "dcm_server"));

		db_process_sql_update("tconfig", array("value" => $dcm_port), array("token" => "dcm_port"));

		//Update info on Intel DCM	
		exec_dcm_action("set_power_sampling", $power_interval);
		exec_dcm_action("set_thermal_sampling", $thermal_interval);		
		exec_dcm_action("set_power_granularity", $power_granularity);
		exec_dcm_action("set_thermal_granularity", $thermal_granularity);
		exec_dcm_action("set_cooling_multiplier", $multiplier);
		exec_dcm_action("set_power_cost", $cost);		
		
		if ($activate_monitoring) {
			exec_dcm_action("resume_monitoring");
			
		} else {
			exec_dcm_action("suspend_monitoring");
			
		}
	}
	
	$activate_dcm_plugin = db_get_value("value", "tconfig", "token", "active_dcm_plugin");	
	$dcm_server = db_get_value("value", "tconfig", "token", "dcm_server");		
	$dcm_port = db_get_value("value", "tconfig", "token", "dcm_port");
	
	//Get current value from DCM API
	$power_interval = exec_dcm_action("get_power_sampling");
	$thermal_interval = exec_dcm_action("get_thermal_sampling");
	$power_granularity = exec_dcm_action("get_power_granularity");
	$thermal_granularity = exec_dcm_action("get_thermal_granularity");
	$multiplier = exec_dcm_action("get_cooling_multiplier");
	$cost = exec_dcm_action("get_power_cost");
	$activate_monitoring = exec_dcm_action("status_monitoring");
	
	$measure_values = array("0" => "0",
							"30" => "30",
							"60" => "60",
							"180" => "180",
							"360" => "360",
							"600" => "600");
							
	$storage_values = array("30" => "30",
							"60" => "60",
							"180" => "180",
							"360" => "360",
							"600" => "600",
							"1800" => "1800",
							"3600" => "3600");
		
	$table->width = '98%';
	$table->data = array ();	

	
	$table->data[0][0] = __('Server');

	$table->data[0][1] = html_print_input_text ('dcm_server', $dcm_server, '', 30, 100, true);	
	
	$table->data[1][0] = __('Port');

	$table->data[1][1] = html_print_input_text ('dcm_port', $dcm_port, '', 30, 100, true);			
	
	$table->data[2][0] = __('Activate/Suspend Monitoring');
	
	$table->data[2][1] = html_print_checkbox ("activate_monitoring", "1", $activate_monitoring, true);

	$table->data[3][0] = __('Power Measuring Period (seconds)');

	$table->data[3][1] = html_print_select ($measure_values, "power_interval", $power_interval, '', '', '', true);
	
	$table->data[4][0] = __('Thermal Measuring Period (seconds)');

	$table->data[4][1] = html_print_select ($measure_values, "thermal_interval", $thermal_interval, '', '', '', true);
	
	$table->data[5][0] = __('Power Data Storage Granularity (seconds)');
	
	$table->data[5][1] = html_print_select ($storage_values, "power_granularity", $power_granularity, '', '', '', true);		
	
	$table->data[6][0] = __('Thermal Data Storage Granularity (seconds)');

	$table->data[6][1] = html_print_select ($storage_values, "thermal_granularity", $thermal_granularity, '', '', '', true);		
	
	$table->data[7][0] = __('Cooling/Power Multiplier');

	$table->data[7][1] = html_print_input_text ('multiplier', $multiplier, '', 30, 100, true);	
	
	$table->data[8][0] = __('Power Cost');

	$table->data[8][1] = html_print_input_text ('cost', $cost, '', 30, 100, true);		
	
	echo '<form id="form_setup" method="post">';
	html_print_input_hidden ('update', 1);
	html_print_table ($table);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	html_print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
	echo '</div>';
	echo '</form>';	
}

extensions_add_godmode_menu_option (__("Intel DCM Setup"), "PM", "gsetup", null);
extensions_add_godmode_function('main_intel_dcm');
?>
