<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function users_extension_main () {
	print_page_header (__("Users connected"));

	$sql = "SELECT id_usuario, ip_origen, fecha, accion FROM tsesion WHERE descripcion = 'Logged in' AND utimestamp > (UNIX_TIMESTAMP(NOW()) - 3600) GROUP BY id_usuario, ip_origen, accion";

	$rows = get_db_all_rows_sql ($sql);
	if (empty ($rows)) {
		$rows = array ();
	}

	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = 600;
	$table->class = "databox";
	$table->size = array ();
	$table->data = array ();
	$table->head = array ();

	$table->head[0] = __('User');
	$table->head[1] = __('IP');
	$table->head[2] = __('Date');

	$rowPair = true;
	$iterator = 0;

	// Get data
	foreach ($rows as $row) {
		if ($rowPair)
			$table->rowclass[$iterator] = 'rowPair';
		else
			$table->rowclass[$iterator] = 'rowOdd';
		$rowPair = !$rowPair;
		$iterator++;

		$data = array ();
		$data[0] = $row["id_usuario"];
		$data[1] = $row["ip_origen"];
		$data[2] = $row["fecha"];
		array_push ($table->data, $data);
	}

	print_table ($table);

}
function users_extension_main_god () {
	echo "<h2>".__('Extensions'). " &raquo; ".__("Users connected"). "</h2>";

	$sql = "SELECT id_usuario, ip_origen, fecha, accion FROM tsesion WHERE descripcion = 'Logged in' AND utimestamp > (UNIX_TIMESTAMP(NOW()) - 3600) GROUP BY id_usuario, ip_origen, accion";

	$rows = get_db_all_rows_sql ($sql);
	if (empty ($rows)) {
		$rows = array ();
	}

	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = 600;
	$table->class = "databox";
	$table->size = array ();
	$table->data = array ();
	$table->head = array ();

	$table->head[0] = __('User');
	$table->head[1] = __('IP');
	$table->head[2] = __('Date');

	$rowPair = true;
	$iterator = 0;

	// Get data
	foreach ($rows as $row) {
		if ($rowPair)
			$table->rowclass[$iterator] = 'rowPair';
		else
			$table->rowclass[$iterator] = 'rowOdd';
		$rowPair = !$rowPair;
		$iterator++;

		$data = array ();
		$data[0] = '<a href="index.php?sec=gusuarios&amp;sec2=godmode/users/configure_user&amp;id='.$row["id_usuario"].'">'.$row["id_usuario"].'</a>';
		$data[1] = $row["ip_origen"];
		$data[2] = $row["fecha"];
		array_push ($table->data, $data);
	}

	print_table ($table);

}
add_godmode_menu_option (__('Users connected'), 'UM','gusuarios',"users/icon.png");
add_operation_menu_option(__('Users connected'), 'usuarios',"users/icon.png");

add_extension_godmode_function ('users_extension_main_god');
add_extension_main_function ('users_extension_main');

?>
