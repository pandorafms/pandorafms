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

if (! check_acl ($config["id_user"], 0, "EW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event manage");
	require ("general/noaccess.php");
	return;
}

// Gets section to jump to another section
$section = (string) get_parameter ("section", "filter");

// Draws header
$buttons['view'] = array('active' => false, 
			'text' => '<a href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'">' . 
			html_print_image("images/operation.png", true, array("title" => __('View events'))) . '</a>',
			'operation' => true);
			
$buttons['filter'] = array('active' => false, 
			'text' => '<a href="index.php?sec=geventos&sec2=godmode/events/events&amp;section=filter&amp;pure='.$config['pure'].'">' .
			html_print_image("images/filter_mc.png", true, array ("title" => __('Create filter'))) . '</a>');

if (check_acl ($config["id_user"], 0, "PM")) {
	$buttons['responses'] = array('active' => false,
				'text' => '<a href="index.php?sec=geventos&sec2=godmode/events/events&amp;section=responses&amp;pure='.$config['pure'].'">' .
				html_print_image("images/event_responses.png", true, array ("title" => __('Event responses'))) . '</a>');
	
	if (! defined ('METACONSOLE')) {
		$buttons['fields'] = array('active' => false,
				'text' => '<a href="index.php?sec=eventos&sec2=godmode/events/events&amp;section=fields&amp;pure='.$config['pure'].'">' .
				html_print_image("images/custom_columns.png", true, array ("title" => __('Custom fields'))) . '</a>');
	}
	else {
		$buttons['fields'] = array('active' => false,
				'text' => '<a href="index.php?sec=eventos&sec2=event/custom_events&amp;section=fields&amp;pure='.$config['pure'].'">' .
				html_print_image("images/custom_columns.png", true, array ("title" => __('Custom fields'))) . '</a>');
	}
}

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
		ui_print_page_header (__("Manage events") . $subpage, "images/gm_events.png", false, "", true, $buttons);
	}
	else {
		ui_meta_print_header(__("Manage events") . $subpage, "", $buttons);
	}

include_once($config["homedir"] . '/include/functions_events.php');

switch($section) {
	case 'edit_filter':
		require_once($config["homedir"] . '/godmode/events/event_edit_filter.php');
		break;
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
