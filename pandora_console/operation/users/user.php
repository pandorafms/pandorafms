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


// Load global vars
require_once ("include/config.php");

check_login ();

echo '<h2>'.__('Pandora users').' &gt; '.__('Users defined in Pandora').'</h2>';

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = 700;
$table->class = "databox";
$table->head = array ();
$table->data = array ();
$table->align = array ();

$table->head[0] = __('User ID');
$table->head[1] = __('Name');
$table->head[2] = __('Last contact');
$table->head[3] = __('Profile');
$table->head[4] = __('Description');

$table->align[2] = "center";
$table->align[3] = "center";

$info = array ();
if (give_acl ($config["id_user"], 0, "UM") == 1) {
	$info = get_users ();
} else {
	$info[$config["id_user"]] = get_user_info ($config["id_user"]);
}

foreach ($info as $user_id => $user_info) {
	$data[0] = '<a href="index.php?sec=usuarios&amp;sec2=operation/users/user_edit&amp;id='.$user_id.'">'.$user_id.'</a>';
	$data[1] = $user_info["fullname"].'<a href="#" class="tip"><span>';
	$data[1] .= __('First name').': '.$user_info["firstname"].'<br />';
	$data[1] .= __('Last name').': '.$user_info["lastname"].'<br />';
	$data[1] .= __('Phone').': '.$user_info["phone"].'<br />';
	$data[1] .= __('E-mail').': '.$user_info["email"].'<br />';
	$data[1] .= '</span></a>';
	$data[2] = print_timestamp ($user_info["last_connect"], true);
	
	if ($user_info["is_admin"]) {
		$data[3] = print_image ("images/user_suit.png", true, array ("alt" => __('Admin'), "title" => __('Administrator'))).'&nbsp;';
	} else {
		$data[3] = print_image ("images/user_green.png", true, array ("alt" => __('User'), "title" => __('Standard User'))).'&nbsp;';
	}
	
	$data[3] .= '<a href="#" class="tip"><span>';
	$result = get_db_all_rows_field_filter ("tusuario_perfil", "id_usuario", $user_id);
	if (!empty ($result)) {
		foreach ($result as $row) {
			$data[3] .= get_profile_name ($row["id_perfil"]);
			$data[3] .= " / ";
			$data[3] .= get_group_name ($row["id_grupo"]);
			$data[3] .= "<br />";
		}
	} else {
		$data[3] .= __('The user doesn\'t have any assigned profile/group');
	}
	$data[3] .= "</span></a>";
	
	$data[4] = print_string_substr ($user_info["comments"], 24, true);
	
	array_push ($table->data, $data);
}

print_table ($table);
unset ($table);

echo '<h3>'.__('Profiles defined in Pandora').'</h3>';

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = 'databox';
$table->width = 700;

$table->head = array ();
$table->data = array ();
$table->size = array ();

$table->head[0] = __('Profiles');

$table->head[1] = "IR".print_help_tip (__('System incidents reading'), true);
$table->head[2] = "IW".print_help_tip (__('System incidents writing'), true);
$table->head[3] = "IM".print_help_tip (__('System incidents management'), true);
$table->head[4] = "AR".print_help_tip (__('Agents reading'), true);
$table->head[5] = "AW".print_help_tip (__('Agents management'), true);
$table->head[6] = "LW".print_help_tip (__('Alerts editing'), true);
$table->head[7] = "UM".print_help_tip (__('Users management'), true);
$table->head[8] = "DM".print_help_tip (__('Database management'), true);
$table->head[9] = "LM".print_help_tip (__('Alerts management'), true);
$table->head[10] = "PM".print_help_tip (__('Systems management'), true);

$table->size[1] = 40;
$table->size[2] = 40;
$table->size[3] = 40;
$table->size[4] = 40;
$table->size[5] = 40;
$table->size[6] = 40;
$table->size[7] = 40;
$table->size[8] = 40;
$table->size[9] = 40;
$table->size[10] = 40;

$profiles = get_db_all_rows_in_table ("tperfil");

$img = print_image ("images/ok.png", true, array ("border" => 0)); 

foreach ($profiles as $profile) {
	$data[0] = $profile["name"];

	$data[1] = ($profile["incident_view"] ? $img : '');
	$data[2] = ($profile["incident_edit"] ? $img : '');
	$data[3] = ($profile["incident_management"] ? $img : '');
	$data[4] = ($profile["agent_view"] ? $img : '');
	$data[5] = ($profile["agent_edit"] ? $img : '');
	$data[6] = ($profile["alert_edit"] ? $img : '');
	$data[7] = ($profile["user_management"] ? $img : '');
	$data[8] = ($profile["db_management"] ? $img : '');
	$data[9] = ($profile["alert_management"] ? $img : '');
	$data[10] = ($profile["pandora_management"] ? $img : '');

	array_push ($table->data, $data);
}

print_table ($table);
unset ($table);
?>