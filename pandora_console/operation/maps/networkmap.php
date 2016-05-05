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

// ACL for the general permission
$networkmaps_read = check_acl ($config['id_user'], 0, "MR");
$networkmaps_write = check_acl ($config['id_user'], 0, "MW");
$networkmaps_manage = check_acl ($config['id_user'], 0, "MM");

if (!$networkmaps_read && !$networkmaps_write && !$networkmaps_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access Networkmap builder");
	if (is_ajax()) {
		return;
	}
	else {
		include ("general/noaccess.php");
		exit;
	}
}

$id = (int)get_parameter('id', 0);

require_once($config['homedir'] . '/include/functions_migration.php');
require_once($config['homedir'] . '/include/class/Map.class.php');
require_once($config['homedir'] . '/include/class/Networkmap.class.php');
enterprise_include('include/class/NetworkmapEnterprise.class.php');



//~ require_once('include/browscap/php-local-browscap.php');
if (is_metaconsole()) {
	$buttons['list'] = array('active' => true,
		'text' => '<a href="index.php?sec=screen&sec2=screens/screens&action=networkmap">' .
			html_print_image("images/list.png", true,
				array ('title' => __('List of networkmaps'))) .
			'</a>');
	
	$title_header = __('Network map &raquo; %s', Map::getName($id));
	
	$buttons['edit'] = array('active' => true,
		'text' => '<a href="index.php?sec=screen&sec2=screens/screens&action=networkmap&edit_networkmap=1&id_networkmap=' . $id . '">' . 
			html_print_image("images/cog.png", true,
				array ('title' => __('Edit networkmap'))) .
			'</a>');
	if (enterprise_installed()) {
		$buttons['deleted'] = array('active' => false,
			'text' => '<a href="index.php?sec=screen&sec2=screens/screens&action=networkmap&list_deleted=1&id_networkmap=' . $id . '">' . 
				html_print_image("images/list.png", true,
					array ('title' => __('Deleted list'))) .
				'</a>');
	}
	$buttons['networkmap'] = array('active' => false,
		'text' => '<a href="index.php?sec=screen&sec2=screens/screens&action=networkmap&id=' . $id . '">' . 
			html_print_image("images/op_network.png", true,
				array ('title' => __('View networkmap'))) .
			'</a>');
	
	// Bread crumbs
	ui_meta_add_breadcrumb(
		array('link' =>
			'index.php?sec=screen&sec2=screens/screens&action=networkmap',
		'text' => $title_header));
	
	ui_meta_print_page_header($nav_bar);
	
	//Print header
	ui_meta_print_header($title_header, "", $buttons);
}
else {
	$buttons['list'] = array('active' => false,
	'text' => '<a href="index.php?sec=network&sec2=operation/maps/networkmap_list">' . 
		html_print_image("images/list.png", true,
			array ('title' => __('List of networkmaps'))) .
		'</a>');
	$buttons['edit'] = array('active' => false,
			'text' => '<a href="index.php?sec=maps&sec2=operation/maps/networkmap_editor&edit_networkmap=1&id_networkmap=' . $id . '">' . 
				html_print_image("images/cog.png", true,
					array ('title' => __('Edit networkmap'))) .
				'</a>');
	if (enterprise_installed()) {
		$buttons['deleted'] = array('active' => false,
				'text' => '<a href="index.php?sec=maps&sec2=enterprise/operation/maps/networkmap_list_deleted&&id_networkmap=' . $id . '">' . 
					html_print_image("images/list.png", true,
						array ('title' => __('Deleted list'))) .
				'</a>');
	}
	$buttons['networkmap'] = array('active' => true,
		'text' => '<a href="index.php?sec=network&sec2=operation/maps/networkmap&id=' . $id . '">' . 
			html_print_image("images/op_network.png", true,
				array ('title' => __('View networkmap'))) .
			'</a>');
	
	ui_print_page_header(
		__('Network map &raquo; %s', Map::getName($id)),
		"images/op_network.png",
		false,
		"network_map",
		false,
		$buttons);
}

if (empty($id)) {
	ui_print_error_message(__('Not found networkmap.'));
	
	return;
}
else {
	if (enterprise_installed()) {
		$networkmap = new NetworkmapEnterprise($id);
	}
	else {
		$networkmap = new Networkmap($id);
	}
	
	if (MAP_TYPE_NETWORKMAP === $networkmap->getType()) {
		if (MAP_SUBTYPE_RADIAL_DYNAMIC === $networkmap->getSubtype()) {
			global $id_networkmap;
			global $store_group;
			global $group;
			global $activeTab;
			
			$id_networkmap = $networkmap->getId();
			$store_group = $networkmap->getGroup();
			$group = $networkmap->getSourceGroup();
			$activeTab = "radial_dynamic";
			
			require_once('operation/agentes/networkmap.dinamic.php');
		}
		else {
			$networkmap->show();
		}
	}
	else {
		ui_print_error_message(__('Not found networkmap.'));
	}
}

?>
