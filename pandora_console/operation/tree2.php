<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

require_once("include/class/tree.class.php");

$tab = get_parameter('tab', 'group');
$search = get_parameter('search', '');
$status = get_parameter('status', AGENT_STATUS_ALL);


// ---------------------Tabs -------------------------------------------
$url = 'index.php?' .
	'sec=estado&' .
	'sec2=operation/tree&' .
	'refr=0&' .
	'pure=' . (int)get_parameter('pure', 0) . '&' .
	'tab=%s';

$tabs = array();
$tabs['os'] = array(
	'text' => "<a href='" . sprintf($url, "os") . "'>" .
		html_print_image("images/operating_system.png", true,
			array("title" => __('OS'))) . "</a>",
	'active' => ($tab == "os"));

$tabs['group'] = array(
	'text' => "<a href='" . sprintf($url, "group") . "'>" .
		html_print_image("images/group.png", true,
			array("title" => __('Groups'))) . "</a>",
	'active' => ($tab == "group"));

$tabs['module_group'] = array(
	'text' => "<a href='" . sprintf($url, "module_group") . "'>" .
		html_print_image("images/module_group.png", true,
			array("title" => __('Module groups'))) . "</a>",
	'active' => ($tab == "module_group"));

$tabs['module'] = array(
	'text' => "<a href='" . sprintf($url, "module") . "'>" .
		html_print_image("images/brick.png", true,
			array("title" => __('Modules'))) . "</a>",
	'active' => ($tab == "module"));

$tabs['tag'] = array(
	'text' => "<a href='" . sprintf($url, "tag") . "'>" .
		html_print_image("images/tag.png", true,
			array("title" => __('Tags'))) . "</a>",
	'active' => ($tab == "tag"));

$header_title = "";
switch ($tab) {
	case 'os':
		$header_title =
			__('Tree view - Sort the agents by OS');
		break;
	case 'group':
		$header_title =
			__('Tree view - Sort the agents by groups');
		break;
	case 'module_group':
		$header_title =
			__('Tree view - Sort the agents by module groups');
		break;
	case 'module':
		$header_title =
			__('Tree view - Sort the agents by modules');
		break;
	case 'tag':
		$header_title =
			__('Tree view - Sort the agents by tags');
		break;
}

ui_print_page_header(
	$header_title, "images/extensions.png", false, "", false, $tabs);
// ---------------------Tabs -------------------------------------------


// --------------------- form filter -----------------------------------
$table = null;
$table->width = "100%";

$table->data[0][0] = __('Agent status');
$fields = array ();
$fields[AGENT_STATUS_ALL] = __('All'); //default
$fields[AGENT_STATUS_NORMAL] = __('Normal'); 
$fields[AGENT_STATUS_WARNING] = __('Warning');
$fields[AGENT_STATUS_CRITICAL] = __('Critical');
$fields[AGENT_STATUS_UNKNOWN] = __('Unknown');
$fields[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data[0][1] = html_print_select($fields,
	"status",
	$status,
	'',
	'',
	0,
	true);
$table->data[0][2] = __('Search agent');
$table->data[0][3] = html_print_input_text(
	"search", $search, '', 40, 30, true);
$table->data[0][4] = html_print_submit_button(
	__('Filter'), "uptbutton", false, 'class="sub search"', true);

html_print_table($table);

// --------------------- form filter -----------------------------------



$tree = new Tree($tab);
$tree->set_filter(array(
	'status' => $status,
	'search' => $search));
$json_tree = $tree->get_json();

html_debug_print($json_tree);
?>