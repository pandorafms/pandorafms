<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function forceExecution($id_group) {
	global $config;
	
	require_once ("include/functions_alerts.php");
	$id_alert = (int) get_parameter ('id_alert');
	alerts_agent_module_force_execution ($id_alert);
}

function validateAlert() {
	$ids = (array) get_parameter_post ("validate", array ());
	$compound_ids = (array) get_parameter_post ("validate_compound", array ());
	
	if (! empty ($ids) || ! empty ($compound_ids)) {
		require_once ("include/functions_alerts.php");
		$result1 = alerts_validate_alert_agent_module ($ids);
		$result2 = alerts_validate_alert_compound ($compound_ids);
		$result = $result1 || $result2;
		
		ui_print_result_message ($result,
			__('Alert(s) validated'),
			__('Error processing alert(s)'));
	}
}

function printFormFilterAlert($id_group, $filter, $free_search, $url, $filter_standby = false, $return = false) {
	$table->width = '90%';
	$table->data = array ();
	$table->style = array ();
	
	$table->data[0][0] = __('Group');
	$table->data[0][1] = html_print_select_groups(false, "AR", true, "ag_group", $id_group, '', '', '', true);
		
	$alert_status_filter = array();
	$alert_status_filter['all_enabled'] = __('All (Enabled)');
	$alert_status_filter['all'] = __('All');
	$alert_status_filter['fired'] = __('Fired');
	$alert_status_filter['notfired'] = __('Not fired');
	$alert_status_filter['disabled'] = __('Disabled');		
	
	$alert_standby = array();
	$alert_standby['all'] = __('All');
	$alert_standby['standby_on'] = __('Standby on');
	$alert_standby['standby_off'] = __('Standby off');
		
	$table->data[0][2] = __('Status');
	$table->data[0][3] = html_print_select ($alert_status_filter, "filter", $filter, '', '', '', true);
	$table->data[0][4] = '';
	$table->data[1][0] = __('Free text for search')
		. '<a href="#" class="tip">&nbsp;<span>' . __("Filter by agent name, module name, template name or action name") . '</span></a>';
	$table->data[1][1] = html_print_input_text('free_search', $free_search, '', 20, 40, true);
	$table->data[1][2] = __('Standby');
	$table->data[1][3] = html_print_select ($alert_standby, "filter_standby", $filter_standby, '', '', '', true);
	$table->data[1][4] = html_print_submit_button(__('Filter'), 'filter_button', false, 'class="sub search"', true);
	
	$data = '<form method="post" action="'.$url.'">';
	$data .= html_print_table ($table, true);
	$data .= '</form>';
	
	if($return) {
		return $data;
	}
	else {
		echo $data;
	}
}
?>
