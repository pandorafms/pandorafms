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

check_login ();
//The ajax is in
// include/ajax/update_manager.ajax.php

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}

$tab = get_parameter('tab', 'online');

$buttons = array(
	'setup' => array(
		'active' => ($tab == 'setup') ? true : false,
		'text' => '<a href="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=setup">' . 
			html_print_image ("images/gm_setup.png", true, array ("title" => __('Options'))) .'</a>'),
	'offline' => array(
		'active' => ($tab == 'offline') ? true : false,
		'text' => '<a href="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=offline">' . 
			html_print_image ("images/box.disabled.png", true, array ("title" => __('Offline update manager'))) .'</a>'),
	'online' => array(
		'active' => ($tab == 'online') ? true : false,
		'text' => '<a href="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=online">' . 
			html_print_image("images/op_gis.png", true, array ("title" => __('Online update manager'))) .'</a>'),
	'messages' => array(
		'active' => ($tab == 'messages') ? true : false,
		'text' => '<a href="index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=messages">' . 
			html_print_image("images/hourglass.png", true, array ("title" => __('Update manager messages'))) .'</a>')
	);

switch ($tab) {
	case 'setup':
		$title = __('Update manager » Setup');
		break;
	case 'offline':
		$title = __('Update manager » Offline');
		break;
	case 'online':
		$title = __('Update manager » Online');
		break;
	case 'messages':
		$title = __('Update manager » Messages');
		break;
}

ui_print_page_header($title,
	"images/gm_setup.png", false, "", true, $buttons);

switch ($tab) {
	case 'setup':
		require($config['homedir'] . "/godmode/update_manager/update_manager.setup.php");
		break;
	case 'offline':
		require($config['homedir'] . "/godmode/update_manager/update_manager.offline.php");
		break;
	case 'messages':
		require($config['homedir'] . "/godmode/update_manager/update_manager.messages.php");
		break;
	case 'online':
	default:
		require($config['homedir'] . "/godmode/update_manager/update_manager.online.php");
		break;
}
?>