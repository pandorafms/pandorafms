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
	set_alerts_agent_module_force_execution ($id_alert);
}

function validateAlert() {
	$ids = (array) get_parameter_post ("validate", array ());
	$compound_ids = (array) get_parameter_post ("validate_compound", array ());
	
	if (! empty ($ids) || ! empty ($compound_ids)) {
		require_once ("include/functions_alerts.php");
		$result1 = validate_alert_agent_module ($ids);
		$result2 = validate_alert_compound ($compound_ids);
		$result = $result1 || $result2;
		
		print_result_message ($result,
			__('Alert(s) validated'),
			__('Error processing alert(s)'));
	}
}

function printFormFilterAlert($id_group, $filter, $free_search, $url) {
	$table->width = '90%';
	$table->data = array ();
	$table->style = array ();
	
	$table->data[0][0] = __('Group');
	$table->data[0][1] = print_select (get_user_groups (), "ag_group", $id_group,
		'javascript:this.form.submit();', '', '', true);
		
	$alert_status_filter = array();
	$alert_status_filter['all_enabled'] = __('All (Enabled)');
	$alert_status_filter['all'] = __('All');
	$alert_status_filter['fired'] = __('Fired');
	$alert_status_filter['notfired'] = __('Not fired');
	$alert_status_filter['disabled'] = __('Disabled');		
		
	$table->data[0][2] = __('Status');
	$table->data[0][3] = print_select ($alert_status_filter, "filter", $filter, 'javascript:this.form.submit();', '', '', true);
	$table->data[1][0] = __('Free text for search')
		. '<a href="#" class="tip">&nbsp;<span>' . __("Filter by agent name, module name, template name or action name") . '</span></a>';
	$table->data[1][1] = print_input_text('free_search', $free_search, '', 20, 40, true);
	$table->colspan[1][2] = 2;
	$table->data[1][2] = print_submit_button(__('Filter'), 'filter_button', false, 'class="sub search"', true);
	
	echo '<form method="post" action="'.$url.'">';
	print_table ($table);
	echo '</form>';
}
?>
