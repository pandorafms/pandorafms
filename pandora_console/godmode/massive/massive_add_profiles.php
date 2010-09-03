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

if (! check_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access massive alert deletion");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');

$create_profiles = (int) get_parameter ('create_profiles');

if($create_profiles) {
	$profiles_id = get_parameter ('profiles_id', -1);
	$groups_id = get_parameter ('groups_id', -1);
	$users_id = get_parameter ('users_id', -1);
	$n_added = 0;

	if($profiles_id == -1 || $groups_id == -1 || $users_id == -1){
		$result = false;
	}else{ 
		foreach($profiles_id as $profile){
			foreach($groups_id as $group){
				foreach($users_id as $user){
					$profile_data = get_db_row_filter ("tusuario_perfil", array("id_usuario" => $user, "id_perfil" => $profile, "id_grupo" => $group));
					// If the profile doesnt exist, we create it
					if ($profile_data === false) {
						audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "User management",
							"Added profile for user ".safe_input($user));
						$return = create_user_profile ($user, $profile, $group);
						if($return !== false){
							$n_added ++;
						}
					}
				}
			}
		}
	}
	
	print_result_message ($n_added > 0,
		__('Profiles added successfully').'('.$n_added.')',
		__('Profiles cannot be added'));
}

print_table ($table);

unset($table);

$table->width = '90%';
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
$data[0] = '<form method="post" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_users&option=add_profiles">';
$data[0] .= print_select (get_profiles (), 'profiles_id[]', '', '', '',
	'', true, true, false, '', false, 'width: 100%');
$data[1] = print_select_groups($config['id_user'], "UM", true,
	'groups_id[]', '', '', '', '', true, true, false, '', false, 'width: 100%');
$data[2] = '<span id="alerts_loading" class="invisible">';
$data[2] .= '<img src="images/spinner.png" />';
$data[2] .= '</span>';
$users_profiles = "";

$data[2] .= print_select (get_users_info(), 'users_id[]', '', '', '', 
    '', true, true, true, '', false, 'width: 100%');


array_push ($table->data, $data);

print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
print_input_hidden ('create_profiles', 1);
print_submit_button (__('Create'), 'go', false, 'class="sub add"');
echo '</div>';

echo '</form>';

unset ($table);

?>
