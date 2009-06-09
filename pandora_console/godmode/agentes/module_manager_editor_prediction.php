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

$extra_title = __('Prediction server module');

$data = array ();
$data[0] = __('Source module');
$data[0] .= print_help_icon ('prediction_source_module', true);
$groups = get_user_groups ($config["id_user"], "AR");
$agents = get_group_agents (array_keys ($groups));

if ($prediction_module) {
	$prediction_id_agent = get_agentmodule_agent ($prediction_module);
	$prediction_id_group = get_agent_group ($prediction_id_agent);
} else {
	$prediction_id_agent = $id_agente;
	$prediction_id_group = get_agent_group ($id_agente);
}
$modules = get_agent_modules ($prediction_id_agent, false, 'disabled = 0 AND history_data = 1');

$data[1] = '<label for="prediction_id_group">'.__('Group').'</label>';
$data[1] .= print_select ($groups, 'prediction_id_group', $prediction_id_group, '',
	'', '', true);
$data[1] .= ' <span id="agent_loading" class="invisible">';
$data[1] .= '<img src="images/spinner.gif" />';
$data[1] .= '</span>';
$data[1] .= '<label for="prediction_id_agent">'.__('Agent').'</label>';
$data[1] .= print_select ($agents, 'prediction_id_agent', $prediction_id_agent, '',
	'', '', true);
$data[1] .= ' <span id="module_loading" class="invisible">';
$data[1] .= '<img src="images/spinner.gif" />';
$data[1] .= '</span>';
$data[1] .= '<label for="prediction_module">'.__('Module').'</label>';
$data[1] .= print_select ($modules, 'prediction_module', $prediction_module, '',
	'', '', true);

$table_simple->colspan['prediction_module'][1] = 3;

push_table_simple ($data, 'prediction_module');

/* Removed common useless parameter */
unset ($table_simple->data[2]);
unset ($table_simple->data[3]);
unset ($table_advanced->data[3]);
unset ($table_advanced->data[2][2]);
unset ($table_advanced->data[2][3]);
?>
