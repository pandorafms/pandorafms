<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



// Load global vars
global $config;

require_once ("include/functions_agents.php");

check_login ();

$id_agente = get_parameter_get ("id_agente", -1);

if ($id_agente === -1) {
	echo '<h3 class="error">'.__('There was a problem loading agent').'</h3>';
	return;
}

if (! check_acl ($config["id_user"], $agent["id_grupo"], "AR")) {
	db_pandora_audit("ACL Violation", 
			  "Trying to access Agent General Information");
	require_once ("general/noaccess.php");
	return;
}


$fields = db_get_all_fields_in_table('tagent_custom_fields');

if($fields === false) {
	$fields = array();
} else {
	$table->width = '98%';
	$table->head = array ();
	$table->head[0] = __('Field');
	$table->head[1] = __('Display on front') . ui_print_help_tip (__('The fields with display on front enabled will be displayed into the agent details'), true);
	$table->head[2] = __('Description');
	$table->align = array ();
	$table->align[1] = 'center';
	$table->align[2] = 'center';
	$table->data = array ();

	foreach ($fields as $field) {
	
		$data[0] = '<b>'.$field['name'].'</b>';

		if($field['display_on_front']) {
			$data[1] = html_print_image('images/tick.png', true);
		}else {
			$data[1] = html_print_image('images/delete.png', true);
		}
		
		$custom_value = db_get_value_filter('description', 'tagent_custom_data', array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
	
		if($custom_value === false || $custom_value == '') {
			$custom_value = '<i>-'.__('empty').'-</i>';
		}
	
		$data[2] = $custom_value;
	
		array_push ($table->data, $data);
	}

	html_print_table ($table);
}
?>
