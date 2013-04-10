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

global $config;

include_once($config['homedir'] . "/include/functions_profile.php");
include_once($config['homedir'] . '/include/functions_users.php');
include_once ($config['homedir'] . '/include/functions_groups.php');

$searchUsers = check_acl($config['id_user'], 0, "UM");

if (!$users || !$searchUsers) {
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
	$table->head[0] = __('User ID') . ' ' . 
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=id_user&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectUserIDUp)) . '</a>' .
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=id_user&sort=down">' . html_print_image("images/sort_down.png", true, array("style"=> $selectUserIDDown)) . '</a>';
	$table->head[1] = __('Name') . ' ' . 
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=name&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp)) . '</a>' .
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=name&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown)) . '</a>';
	$table->head[2] = __('Email') . ' ' . 
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=email&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectEmailUp)) . '</a>' .
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=email&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectEmailDown)) . '</a>';
	$table->head[3] = __('Last contact') . ' ' . 
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=last_contact&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectLastContactUp)) . '</a>' .
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=last_contact&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectLastContactDown)) . '</a>';
	$table->head[4] = __('Profile') . ' ' . 
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=profile&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectProfileUp)) . '</a>' .
		'<a href="index.php?search_category=users&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=profile&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectProfileDown)) . '</a>';
	$table->head[5] = __('Description');
	
	$table->data = array ();
	
	foreach ($users as $user) {
		$userIDCell = "<a href='?sec=gusuarios&sec2=godmode/users/configure_user&id=" .
			$user['id_user'] . "'>" . $user['id_user'] . "</a>";
		
		if ($user["is_admin"]) {
			$profileCell = html_print_image ("images/user_suit.png", true,
			array ("alt" => __('Admin'),
				"title" => __('Administrator'))).'&nbsp;';
		}
		else {
			$profileCell = html_print_image ("images/user_green.png", true,
			array ("alt" => __('User'),
				"title" => __('Standard User'))).'&nbsp;';
		}
		$profileCell .= '<a href="#" class="tip"><span>';
		$result = db_get_all_rows_field_filter ("tusuario_perfil", "id_usuario", $user['id_user']);
		if ($result !== false) {
			foreach ($result as $row) {
				$profileCell .= profile_get_name ($row["id_perfil"]);
				$profileCell .= " / ";
				$profileCell .= groups_get_name ($row["id_grupo"]);
				$profileCell .= "<br />";
			}
		}
		else {
			$profileCell .= __('The user doesn\'t have any assigned profile/group');
		}
		$profileCell .= "</span></a>";
		
		array_push($table->data, array(
			$userIDCell,
			$user['fullname'],
			"<a href='mailto:" . $user['email'] . "'>" . $user['email'] . "</a>",
			ui_print_timestamp ($user["last_connect"], true),
			$profileCell,
			$user['comments']));
	}
	
	echo "<br />";ui_pagination ($totalUsers);
	html_print_table ($table); unset($table);
	ui_pagination ($totalUsers);
}
?>
