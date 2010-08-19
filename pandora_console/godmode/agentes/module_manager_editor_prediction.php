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
enterprise_include_once('include/functions_policies.php');
enterprise_include_once('godmode/agentes/module_manager_editor_prediction.php');

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$page = get_parameter('page', '');
$id_agente = get_parameter('id_agente', '');
$agent_name = get_parameter('agent_name', get_agent_name($id_agente));
$id_agente_modulo= get_parameter('id_agent_module',0);
$sql = 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = '.$id_agente_modulo;
$row = get_db_row_sql($sql);
$is_service = false;
if ($row !== false && is_array($row)) {
	$prediction_module = $row['prediction_module'];
	// Services are an Enterprise feature.
	$service_select  = $row['custom_integer_1'];
	if ($service_select > 0) {
		$is_service = true;
	}
}
else {
	$service_select = 0;
}
if (strstr($page, "policy_modules") === false) {
	if ($config['enterprise_installed'])
		$disabledBecauseInPolicy = isModuleInPolicy($id_agent_module) && isModuleLinked($id_agent_module);
	else
		$disabledBecauseInPolicy = false;
	if ($disabledBecauseInPolicy)
		$disabledTextBecauseInPolicy = 'disabled = "disabled"';
}

$extra_title = __('Prediction server module');

$data = array ();
$data[0] = __('Source module');
$data[0] .= print_help_icon ('prediction_source_module', true);
$data[1] = '';
// Services are an Enterprise feature.
$module_service_selector = enterprise_hook('get_module_service_selector', array($is_service));  
if ($module_service_selector !== ENTERPRISE_NOT_HOOK) {
	$data[1] = $module_service_selector;
}
$data[1] .= '<div id="module_data" style="top:1em; float:left; width:50%;">';
$data[1] .= print_label(__("Agent"),'agent_name', true)."<br/>";
$sql = "SELECT id_agente, nombre FROM tagente";
// TODO: ACL Filter
$data[1] .= print_input_text_extended ('agent_name',$agent_name, 'text_agent_name', '', 30, 100, $is_service, '',
                            array('style' => 'background: url(images/lightning.png) no-repeat right;'), true, false);
$data[1] .= '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>&nbsp; <br/>';
$data[1] .= print_label(__("Module"),'prediction_module',true);
if($id_agente) {
	$sql = "SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE history_data = 1 AND id_agente =  ".$id_agente;
    $data[1] .= print_select_from_sql($sql, 'prediction_module', $prediction_module, false, __('Select Module'), 0, true, false, true, $is_service);
}
else {
	$data[1] .= '<select id="prediction_module" name="prediction_module" disabled="disabled"><option value="0">Select an Agent first</option></select>';
}
$data[1] .= print_input_hidden ('id_agente', $id_agente, true);
$data[1] .= '</div>';

// Services are an Enterprise feature.
$selector_form = enterprise_hook('get_selector_form', array($is_service, $service_select));
if ($selector_form !== ENTERPRISE_NOT_HOOK) {
    $data[1] .= $selector_form;
}

$table_simple->colspan['prediction_module'][1] = 3;

push_table_simple ($data, 'prediction_module');

/* Removed common useless parameter */
unset ($table_advanced->data[3]);
unset ($table_advanced->data[2][2]);
unset ($table_advanced->data[2][3]);
?>
<script type="text/javascript">
$(document).ready(function() {agent_module_autocomplete ("#text_agent_name", "#id_agente", "#prediction_module")});

<?php enterprise_hook('print_services_javascript'); ?>

</script>
