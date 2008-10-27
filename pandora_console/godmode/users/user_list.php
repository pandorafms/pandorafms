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

// Load globar vars
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "UM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access User Management");
	require ("general/noaccess.php");
	exit;
}

if (isset($_GET["borrar_usuario"])) { // if delete user
	$nombre = get_parameter_get ("borrar_usuario");
	// Delete user
	// Delete cols from table tgrupo_usuario
	
	$sql = "DELETE FROM tgrupo_usuario WHERE usuario = '".$nombre."'";
	$result = process_sql ($sql);
	$sql = "DELETE FROM tusuario WHERE id_usuario = '".$nombre."'";
	$result = process_sql ($sql);
	if ($result === false) {
		echo '<h3 class="error">'.__('There was a problem deleting user').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('User successfully deleted').'</h3>';
	}
}

echo '<h2>'.__('User management').' &gt; '.__('Users defined in Pandora').'</h2>';

$table->width = 700;
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";

$table->head = array ();
$table->size = array ();
$table->data = array ();
$table->align = array ();

$table->head[0] = __('User ID');

$table->head[1] = __('Last contact');
$table->align[1] = "center";

$table->head[2] = __('Profile');
$table->align[2] = "center";

$table->head[3] = __('Name');
$table->align[3] = "center";

$table->head[4] = __('Description');
$table->align[4] = "center";

$table->head[5] = __('Delete');
$table->align[5] = "center";

$result = get_db_all_rows_in_table ('tusuario');

foreach ($result as $row) {
	$data = array ();

	$data[0] = '<a href="index.php?sec=gusuarios&sec2=godmode/users/configure_user&id_usuario_mio='.$row["id_usuario"].'"><b>'.$row["id_usuario"].'</b></a>';
	$data[1] = $row["fecha_registro"];
	if ($row["nivel"] == 1) {
		$data[2] = '<img src="images/user_suit.png" />';
	} else {
		$data[2] = '<img src="images/user_green.png" />';
	}
	
	$data[2] .= '<a href="#" class="tip"><span>';
	$profiles = get_db_all_rows_field_filter ("tusuario_perfil", "id_usuario", $row["id_usuario"]);
	if ($profiles === false) {
		$data[2] .= __('This user doesn\'t have any assigned profile/group');
		$profiles = array ();
	}
	
	foreach ($profiles as $profile) {
		$data[2] .= dame_perfil ($profile["id_perfil"])." / ";
		$data[2] .= dame_grupo ($profile["id_grupo"])."<br />";
	}
	
	$data[2] .= "</span></a>";
	
	$data[3] = substr ($row["nombre_real"], 0, 16);
	$data[4] = $row["comentarios"];

	$data[5] = '<a href="index.php?sec=gagente&sec2=godmode/users/user_list&borrar_usuario='.$row["id_usuario"].'" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">';
	$data[5] .= '<img border="0" src="images/cross.png" /></a>';
	array_push ($table->data, $data);
}

print_table ($table);
unset ($table);

echo '<div style="width:680px; text-align:right"><form method="post" action="index.php?sec=gusuarios&sec2=godmode/users/configure_user&alta=1">';
print_submit_button (__('Create user'), "crt", false, 'class="sub next"');
echo "</form></div>";
?>
