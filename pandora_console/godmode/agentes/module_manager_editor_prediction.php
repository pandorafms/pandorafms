<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// General startup for established session
if (!isset ($id_agente)) {
	die ("Not Authorized");
}

$extra_title = __('Prediction server module');

$data = array ();
$data[0] = __('Source module');
$data[0] .= pandora_help ('prediction_source_module', true);
$agents = get_group_agents (array_keys (get_user_groups ($config["id_user"], "AW")));
$fields = array ();
foreach ($agents as $agent_id => $agent_name) {
	$modules = get_agent_modules ($agent_id, false, 'disabled = 0 AND history_data = 1');
	foreach ($modules as $module_id => $module_name) {
		$fields[$module_id] = $agent_name.' / '.$module_name;
	}
}
$data[1] = print_select ($fields, 'prediction_module', $prediction_module, '',
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
