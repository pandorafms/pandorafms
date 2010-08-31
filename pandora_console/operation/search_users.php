<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

$searchUsers = check_acl($config['id_user'], 0, "UM");

$users = false;
if ($searchUsers) {
	$sql = "SELECT id_user, fullname, firstname, lastname, middlename, email, last_connect, is_admin, comments FROM tusuario
		WHERE fullname LIKE '%" . $stringSearchSQL . "%' OR
			id_user LIKE '%" . $stringSearchSQL . "%' OR
			firstname LIKE '%" . $stringSearchSQL . "%' OR
			lastname LIKE '%" . $stringSearchSQL . "%' OR
			middlename LIKE '%" . $stringSearchSQL . "%' OR
			email LIKE '%" . $stringSearchSQL . "%'
		LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	$users = process_sql($sql);
	
	if($users !== false) {
		//Check ACLs
		$users_id = array();
		foreach($users as $key => $user){
				if (!check_acl ($config["id_user"], get_user_groups ($user["id_user"]), "UM") && $config["id_user"] != $user["id_user"]) {
					unset($users[$key]);
				} else {
					$users_id[] = $user["id_user"];
				}
		}
		
		if(!$users_id) {
			$user_condition = "";
		}else {
			// Condition with the visible agents
			$user_condition = " AND id_user IN (\"".implode('","',$users_id)."\")";
		}
		
		$sql = "SELECT COUNT(id_user) AS count FROM tusuario
			WHERE (fullname LIKE '%" . $stringSearchSQL . "%' OR
				firstname LIKE '%" . $stringSearchSQL . "%' OR
				lastname LIKE '%" . $stringSearchSQL . "%' OR
				middlename LIKE '%" . $stringSearchSQL . "%' OR
				email LIKE '%" . $stringSearchSQL . "%')".$user_condition;	
		$totalUsers = get_db_row_sql($sql);

		$totalUsers = $totalUsers['count'];
	}
}

if (!$users) {
		echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
	$table->align = array ();
	$table->align[4] = "center";
	
	$table->head = array ();
	$table->head[0] = __('User ID');
	$table->head[1] = __('Name');
	$table->head[2] = __('Email');
	$table->head[3] = __('Last contact');
	$table->head[4] = __('Profile');
	$table->head[5] = __('Description');

	$table->data = array ();
	
	foreach ($users as $user) {
		$userIDCell = "<a href='?sec=gusuarios&sec2=godmode/users/configure_user&id=" .
				$user['id_user'] . "'>" . $user['id_user'] . "</a>";
		
		if ($user["is_admin"]) {
			$profileCell = print_image ("images/user_suit.png", true,
			array ("alt" => __('Admin'),
				"title" => __('Administrator'))).'&nbsp;';
		} else {
			$profileCell = print_image ("images/user_green.png", true,
			array ("alt" => __('User'),
				"title" => __('Standard User'))).'&nbsp;';
		}
		$profileCell .= '<a href="#" class="tip"><span>';
		$result = get_db_all_rows_field_filter ("tusuario_perfil", "id_usuario", $user['id_user']);
		if ($result !== false) {
			foreach ($result as $row) {
				$profileCell .= get_profile_name ($row["id_perfil"]);
				$profileCell .= " / ";
				$profileCell .= get_group_name ($row["id_grupo"]);
				$profileCell .= "<br />";
			}
		} else {
			$profileCell .= __('The user doesn\'t have any assigned profile/group');
		}
		$profileCell .= "</span></a>";
		
		array_push($table->data, array(
			$userIDCell,
			$user['fullname'],
			"<a href='mailto:" . $user['email'] . "'>" . $user['email'] . "</a>",
			print_timestamp ($user["last_connect"], true),
			$profileCell,
			$user['comments']));
	}

	echo "<br />";pagination ($totalUsers);
	print_table ($table); unset($table);
	pagination ($totalUsers);
}
?>
