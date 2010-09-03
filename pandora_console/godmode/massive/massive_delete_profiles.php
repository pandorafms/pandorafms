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

if (is_ajax ()) {
	$get_users = (bool) get_parameter ('get_users');
	
	if ($get_users) {
		$id_group = get_parameter ('id_group');
		$id_profile = get_parameter ('id_profile');

		$profile_data = get_db_all_rows_filter ("tusuario_perfil", array("id_perfil" => $id_profile[0], "id_grupo" => $id_group[0]));
		
		echo json_encode (index_array ($profile_data, 'id_up', 'id_usuario'));
		return;
	}
	return;
}

$delete_profiles = (int) get_parameter ('delete_profiles');

if($delete_profiles) {
	$profiles_id = get_parameter ('profiles_id', -1);
	$groups_id = get_parameter ('groups_id', -1);
	$users_id = get_parameter ('users_id', -1);

	if($profiles_id == -1 || $groups_id == -1 || $users_id == -1){
		$result = false;
	}else{ 
		foreach($profiles_id as $profile){
			foreach($groups_id as $group){
				foreach($users_id as $id_up){
					$user = (string) get_db_value_filter ('id_usuario', 'tusuario_perfil', array('id_up' => $id_up));

					audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "User management",
						"Deleted profile for user ".safe_input($user));

					$return = delete_user_profile ($user, $id_up);
				}
			}
		}
	}
	
	print_result_message ($result,
		__('Profiles deleted successfully'),
		__('Profiles cannot be deleted'));
}

print_table ($table);

unset($table);

$table->width = '90%';
$table->data = array ();
$table->head = array ();
$table->align = array ();
$table->style = array ();
$table->style[0] = 'vertical-align: top';
$table->style[1] = 'vertical-align: top';
$table->head[0] = __('Profile name');
$table->head[1] = __('Group');
$table->head[2] = __('Users');
$table->align[2] = 'center';
$table->size[0] = '34%';
$table->size[1] = '33%';
$table->size[2] = '33%';

$data = array ();
$data[0] = '<form method="post" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_users&option=delete_profiles">';
$data[0] .= print_select (get_profiles (), 'profiles_id[]', '', '', '',
	'', true, false, false, '', false, 'width: 100%');
$data[1] = print_select_groups($config['id_user'], "UM", true,
	'groups_id[]', '', '', '', '', true, false, false, '', false, 'width: 100%');
$data[2] = '<span id="users_loading" class="invisible">';
$data[2] .= '<img src="images/spinner.png" />';
$data[2] .= '</span>';
$users_profiles = "";

$data[2] .= print_select (array(), 'users_id[]', '', '', '', 
    '', true, true, true, '', false, 'width: 100%');


array_push ($table->data, $data);

print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
print_input_hidden ('delete_profiles', 1);
print_submit_button (__('Delete'), 'del', false, 'class="sub delete"');
echo '</div>';

echo '</form>';

unset ($table);

echo '<h3 class="error invisible" id="message"> </h3>';

require_jquery_file ('form');
require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	
	function update_users() {
		var $select = $("#users_id").disable ();
		$("#users_loading").show ();
		$("option", $select).remove ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/massive/massive_delete_profiles",
			"get_users" : 1,
			"id_group[]" : $("#groups_id").attr("value"),
			"id_profile[]" : $("#profiles_id").attr("value")
			},
			function (data, status) {
				options = "";
				jQuery.each (data, function (id, value) {
					options += "<option value=\""+id+"\">"+value+"</option>";
				});
				$("#users_id").append (options);
				$("#users_loading").hide ();
				$select.enable ();
			},
			"json"
		);
	}

	$("#groups_id").change (function () {
		update_users();
	});
	
	$("#profiles_id").change (function () {
		update_users();
	});
});
/* ]]> */
</script>
