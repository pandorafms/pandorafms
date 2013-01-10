<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

function users_extension_main() {
	users_extension_main_god(false);
}

function users_extension_main_god ($god = true) {
	global $config;
	
	if (isset($config["id_user"])) {
		if (!check_acl ($config["id_user"], 0, "UM")) {
			return;
		}
	}
	
	// Header
	ui_print_page_header (__("Users connected"), "images/group.png", false, "", $god);

	// Get user conected last 5 minutes
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = "SELECT id_user, last_connect
				FROM tusuario
				WHERE last_connect > (UNIX_TIMESTAMP(NOW()) - 300) ORDER BY last_connect DESC";
		break;
		case "postgresql":
			$sql = "SELECT id_user, last_connect
				FROM tusuario
				WHERE last_connect > (ceil(date_part('epoch', CURRENT_TIMESTAMP)) - 300) ORDER BY last_connect DESC";
		break;
		case "oracle":
			$sql = "SELECT id_user, last_connect
				FROM tusuario
				WHERE last_connect > (ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - 300) ORDER BY last_connect DESC";
		break;
	}
		
	$rows = db_get_all_rows_sql ($sql);

	if (empty ($rows)) {
		$rows = array ();
		echo "<div class='nf'>".__('No other users connected')."</div>";
	}
	else {
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = '98%';
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
			// Get ip_origin of the last login of the user
			switch ($config["dbtype"]) {
				case "mysql":
				case "postgresql":
					$ip_origin = db_get_value_sql(sprintf("SELECT ip_origen 
																	FROM tsesion 
																	WHERE id_usuario = '%s'
																	AND descripcion = '" . io_safe_input('Logged in') . "' 
																	ORDER BY fecha DESC",$row["id_user"]));
				break;
				case "oracle":
					$ip_origin = db_get_value_sql(sprintf("SELECT ip_origen 
																	FROM tsesion 
																	WHERE id_usuario = '%s'
																	AND to_char(descripcion) = '" . io_safe_input('Logged in') . "' 
																	ORDER BY fecha DESC",$row["id_user"]));
				break;
			}
														
			if ($rowPair)
				$table->rowclass[$iterator] = 'rowPair';
			else
				$table->rowclass[$iterator] = 'rowOdd';
			$rowPair = !$rowPair;
			$iterator++;

			$data = array ();
			$data[0] = '<a href="index.php?sec=gusuarios&amp;sec2=godmode/users/configure_user&amp;id='.$row["id_user"].'">'.$row["id_user"].'</a>';
			$data[1] = $ip_origin;
			$data[2] = date($config["date_format"], $row['last_connect']);
			array_push ($table->data, $data);
		}

		html_print_table ($table);
	}
}
extensions_add_godmode_menu_option (__('Users connected'), 'UM','gusuarios',"users/icon.png", "v1r1");

if (isset($config["id_user"])) {
	if (check_acl ($config["id_user"], 0, "UM")) {
		extensions_add_operation_menu_option(__('Users connected'), 'workspace',"users/icon.png", "v1r1");
	}
}

extensions_add_godmode_function('users_extension_main_god');
extensions_add_main_function('users_extension_main');

?>
