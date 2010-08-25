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



// Load global vars
global $config;

require_once ("include/functions_agents.php");

if ($config['flash_charts']) {
	require_once ("include/fgraph.php");
}

check_login ();

$id_agente = get_parameter_get ("id_agente", -1);

if ($id_agente === -1) {
	echo '<h3 class="error">'.__('There was a problem loading agent').'</h3>';
	return;
}

if (! give_acl ($config["id_user"], $agent["id_grupo"], "AR")) {
	audit_db ($config["id_user"], $_SERVER['REMOTE_ADDR'], "ACL Violation", 
			  "Trying to access Agent General Information");
	require_once ("general/noaccess.php");
	return;
}

$table->width = '65%';
$table->head = array ();
$table->head[0] = __('Field');
$table->head[1] = __('Display on front').print_help_tip (__('The fields with display on front enabled will be displayed into the agent details'), true);
$table->head[2] = __('Description');
$table->align = array ();
$table->align[1] = 'center';
$table->align[2] = 'center';
$table->data = array ();

$fields = get_db_all_fields_in_table('tagent_custom_fields');

if($fields === false) $fields = array();

foreach ($fields as $field) {
	
	$data[0] = '<b>'.$field['name'].'</b>';

	if($field['display_on_front']) {
		$data[1] = print_image('images/tick.png', true);
	}else {
		$data[1] = print_image('images/delete.png', true);
	}
		
	$custom_value = get_db_value_filter('description', 'tagent_custom_data', array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
	
	if($custom_value === false || $custom_value == '') {
		$custom_value = '<i>-'.__('empty').'-</i>';
	}
	
	$data[2] = $custom_value;
	
	array_push ($table->data, $data);
}

print_table ($table);
?>
