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
	
	if (!empty($ids)) {
		require_once ("include/functions_alerts.php");
		$result = alerts_validate_alert_agent_module ($ids);
		
		ui_print_result_message ($result,
			__('Alert(s) validated'),
			__('Error processing alert(s)'));
	}
}

function printFormFilterAlert($id_group, $filter, $free_search, $url, $filter_standby = false, $tag_filter = false, $return = false, $strict_user = false) {
	
	global $config;
	require_once ($config['homedir'] . "/include/functions_tags.php");
	
	$table->width = '100%';
	$table->data = array ();
	$table->style = array ();
	
	$table->data[0][0] = __('Group');
	$table->data[0][1] = html_print_select_groups($config['id_user'], "AR", true, "ag_group", $id_group, '', '', '', true, false, false, '', false, '', false, false, 'id_grupo', $strict_user);
	
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
	
	$table->data[0][4] = __('Tags') . ui_print_help_tip(__('Only it is show tags in use.'), true);

	$tags = tags_get_user_tags();

	if (empty($tags)) {
		$table->data[0][4] .= __('No tags');
	}
	else {
		$table->data[0][4] .= html_print_select ($tags, "tag_filter", $tag_filter, '', __('All'), '', true, false, true, '', false, 'width: 150px;');
	}

	$table->data[1][0] = __('Free text for search') .
		ui_print_help_tip(
			__("Filter by agent name, module name, template name or action name"),
			true);
	$table->data[1][1] = html_print_input_text('free_search', $free_search, '', 20, 40, true);
	$table->data[1][2] = __('Standby');
	$table->data[1][3] = html_print_select ($alert_standby, "filter_standby", $filter_standby, '', '', '', true);
	$table->data[1][4] = html_print_submit_button(__('Filter'), 'filter_button', false, 'class="sub filter"', true);
	
	$data = '<form method="post" action="'.$url.'">';
	$data .= html_print_table ($table, true);
	$data .= '</form>';
	
	if ($return) {
		return $data;
	}
	else {
		echo $data;
	}
}
?>
