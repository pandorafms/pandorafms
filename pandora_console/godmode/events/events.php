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
global $config;

check_login ();

if (! check_acl ($config["id_user"], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event manage");
	require ("general/noaccess.php");
	return;
}

// Gets section to jump to another section
$section = (string) get_parameter ("section", "filter");

// Draws header
$buttons = array(
		'view' => array('active' => false, 
			'text' => '<a href="index.php?sec=eventos&sec2=operation/events/events">' . 
			html_print_image("images/zoom.png", true, array("title" => __('View events'))) . '</a>'),
		'separator' => '',
		'filter' => array('active' => false, 
			'text' => '<a href="index.php?sec=geventos&sec2=godmode/events/events&amp;section=filter">' .
			html_print_image("images/lightning_go.png", true, array ("title" => __('Create filter'))) . '</a>'),
		'responses' => array('active' => false, 	
			'text' => '<a href="index.php?sec=geventos&sec2=godmode/events/events&amp;section=responses">' .
			html_print_image("images/cog.png", true, array ("title" => __('Event responses'))) . '</a>'),
		'fields' => array('active' => false, 	
			'text' => '<a href="index.php?sec=geventos&sec2=godmode/events/events&amp;section=fields">' .
			html_print_image("images/god6.png", true, array ("title" => __('Custom fields'))) . '</a>'),
	);

switch ($section) {
	case 'filter':
		$buttons['filter']['active'] = true;
		$subpage = ' - ' . __('Filters');
		break;
	case 'fields':
		$buttons['fields']['active'] = true;
		$subpage = ' - ' . __('Custom fields');
		break;
	case 'responses':
		$buttons['responses']['active'] = true;
		$subpage = ' - ' . __('Responses');
		break;
	case 'view':
		$buttons['view']['active'] = true;
		break;
	default:
		$buttons['filter']['active'] = true;
		$subpage = ' - ' . __('Filters');
		break;
}

	if (! defined ('METACONSOLE')) {
		ui_print_page_header (__("Manage events") . $subpage, "images/lightning_go.png", false, "", true, $buttons);
	}
	else {
		ui_meta_print_header(__("Manage events") . $subpage, "", $buttons);
	}

include_once($config["homedir"] . '/include/functions_events.php');

switch($section) {
	case 'filter':
		require_once($config["homedir"] . '/godmode/events/event_filter.php');
		break;
	case 'fields':
		require_once($config["homedir"] . '/godmode/events/custom_events.php');
		break;
	case 'responses':
		require_once($config["homedir"] . '/godmode/events/event_responses.php');
		break;
}

?>
