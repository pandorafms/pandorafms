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
check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access massive profile addition");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once($config['homedir'] . "/include/functions_profile.php");
require_once($config['homedir'] . "/include/functions_users.php");

$create_profiles = (int) get_parameter ('create_profiles');

if ($create_profiles) {
	$profiles_id = get_parameter ('profiles_id', -1);
	$groups_id = get_parameter ('groups_id', -1);
	$users_id = get_parameter ('users_id', -1);
	$n_added = 0;

	if ($profiles_id == -1 || $groups_id == -1 || $users_id == -1) {
		$result = false;
	}
	else { 
		foreach ($profiles_id as $profile) {
			foreach ($groups_id as $group) {
				foreach ($users_id as $user) {
					$profile_data = db_get_row_filter ("tusuario_perfil", array("id_usuario" => $user, "id_perfil" => $profile, "id_grupo" => $group));
					// If the profile doesnt exist, we create it
					if ($profile_data === false) {
						db_pandora_audit("User management",
							"Added profile for user ".io_safe_input($user));
						$return = profile_create_user_profile ($user, $profile, $group);
						if ($return !== false) {
							$n_added ++;
						}
					}
				}
			}
		}
	}
	
	if ($n_added > 0) {
		db_pandora_audit("Masive management", "Add profiles", false, false,
			'Profiles: ' . 	json_encode($profiles_id) . ' Groups: ' . json_encode($groups_id) . 'Users: ' . json_encode($users_id));
	}
	else {
		db_pandora_audit("Masive management", "Fail to try add profiles", false, false,
			'Profiles: ' . 	json_encode($profiles_id) . ' Groups: ' . json_encode($groups_id) . 'Users: ' . json_encode($users_id));
	}
	
	ui_print_result_message ($n_added > 0,
		__('Profiles added successfully').'('.$n_added.')',
		__('Profiles cannot be added'));
}

html_print_table ($table);

unset($table);

$table->width = '98%';
$table->data = array ();
$table->head = array ();
$table->align = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[1] = 'font-weight: bold';
$table->head[0] = __('Profile name');
$table->head[1] = __('Group');
$table->head[2] = __('Users');
$table->align[2] = 'center';
$table->size[0] = '34%';
$table->size[1] = '33%';
$table->size[2] = '33%';

$data = array ();
$data[0] = '<form method="post" id="form_profiles" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_users&option=add_profiles">';
$data[0] .= html_print_select (profile_get_profiles (), 'profiles_id[]', '', '', '',
	'', true, true, false, '', false, 'width: 100%');
$data[1] = html_print_select_groups($config['id_user'], "UM", true,
	'groups_id[]', '', '', '', '', true, true, false, '', false, 'width: 100%');
$data[2] = '<span id="alerts_loading" class="invisible">';
$data[2] .= html_print_image('images/spinner.png', true);
$data[2] .= '</span>';
$users_profiles = "";

$data[2] .= html_print_select (users_get_info(), 'users_id[]', '', '', '', 
	'', true, true, true, '', false, 'width: 100%');


array_push ($table->data, $data);

html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
html_print_input_hidden ('create_profiles', 1);
html_print_submit_button (__('Create'), 'go', false, 'class="sub add"');
echo '</div>';

echo '</form>';

unset ($table);

?>
