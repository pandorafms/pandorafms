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

global $config;

require_once ($config['homedir'] . '/include/functions_visual_map.php');

// ACL for the general permission
$vconsoles_read = check_acl ($config['id_user'], 0, "VR");
$vconsoles_write = check_acl ($config['id_user'], 0, "VW");
$vconsoles_manage = check_acl ($config['id_user'], 0, "VM");

if (!$vconsoles_read && !$vconsoles_write && !$vconsoles_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access map builder");
	require ("general/noaccess.php");
	exit;
}

$pure = (int)get_parameter('pure', 0);
$hack_metaconsole = '';
if (defined('METACONSOLE'))
	$hack_metaconsole = '../../';

$buttons['visual_console'] = array(
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/map_builder">' .
                html_print_image ("images/visual_console.png", true, array ("title" => __('Visual Console List'))) .'</a>'
);

$buttons['visual_console_favorite'] = array(
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_favorite">' .
                html_print_image ("images/list.png", true, array ("title" => __('Visual Favourite Console'))) .'</a>'
);

$buttons['visual_console_template'] = array(
    'active' => true,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_template">' .
                html_print_image ("images/templates.png", true, array ("title" => __('Visual Console Template'))) .'</a>'
);

$buttons['visual_console_template_wizard'] = array(
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_template_wizard">' .
                html_print_image ("images/wand.png", true, array ("title" => __('Visual Console Template Wizard'))) .'</a>'
);

if (!defined('METACONSOLE')) {
	ui_print_page_header(
		__('Reporting') .' &raquo; ' . __('Visual Console'),
		"images/op_reporting.png",
		false,
		"map_builder",
		false,
		$buttons
	);
}


?>