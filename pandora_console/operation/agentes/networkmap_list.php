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

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Networkmap builder");
	include ("general/noaccess.php");
	exit;
}

require_once ('include/functions_networkmap.php');

ui_print_page_header(__('Network map'), "images/bricks.png", false, "network_map", false);

// Delete networkmap action
$id_networkmap = get_parameter ('id_networkmap', 0);
$delete_networkmap = get_parameter ('delete_networkmap', 0);

if ($delete_networkmap) {
	$result = networkmap_delete_networkmap($id_networkmap);
	$message = ui_print_result_message ($result,
		__('Network map deleted successfully'),
		__('Could not delete network map'), '', true);
		
	echo $message;
	
	$id_networkmap = 0;
}

// Filter form
$group_search = get_parameter('group_search', '0');
$type_search = get_parameter('type_filter', '0');

echo '<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/networkmap_list">';
echo "<table style='width: 100%' class='databox'>";
echo "<tr><td class='datos' >";
echo __('Group');
echo "</td><td class='datos'>";
html_print_select_groups($config['id_user'], 'AR', true, 'group_search', $group_search);
echo "</td><td class='datos'>";
echo __('Type');
echo "</td><td class='datos'>";
$networkmap_filter_types = networkmap_get_filter_types();
html_print_select($networkmap_filter_types, 'type_filter', $type_search, '', __('All'), 0, false);
echo "</td>";
echo "<td class='datos'>";
html_print_submit_button (__('Filter'), 'crt', false, 'class="sub search"');
echo "</td></tr></table>";
echo "</form>";

// Display table
$table = null;
$table->width = "100%";

$table->style = array();
$table->style[] = '';
$table->style[] = 'text-align: center;';
$table->style[1] = 'text-align: center;';
$table->style[2] = 'text-align: center;';
$table->style[3] = 'text-align: center;';
$table->style[4] = 'text-align: center;';

$table->size = array();
$table->size[] = '80%';
$table->size[] = '60px';
$table->size[] = '30px';

if (check_acl ($config["id_user"], 0, "AW")) {
	$table->size[] = '30px';
	$table->size[] = '30px';
}

$table->head = array();
$table->head[] = __('Name');
$table->head[] = __('Type');
$table->head[] = __('Group');
if (check_acl ($config["id_user"], 0, "AW")) {
	$table->head[] = __('Edit');
	$table->head[] = __('Delete');
}
$id_groups = array_keys(users_get_groups());

// Create filter
$where = array();
$where['id_group'] = $id_groups;
// Order by type field
$where['order'] = 'type';

if ($group_search != '0')
	$where['id_group'] = $group_search;

if ($type_search != '0')
	$where['type'] = $type_search;

$network_maps = db_get_all_rows_filter('tnetwork_map',
	$where);

if ($network_maps === false) {
	echo "<div class='warn'>" . __('Not networkmap defined.') .
		"</div>";
}
else {
	$table->data = array();
	foreach ($network_maps as $network_map) {
		// If enterprise not loaded then skip this code
		if ($network_map['type'] == 'policies' and (!defined('PANDORA_ENTERPRISE')))
			continue;
		
		$data = array();
		$data[] = '<b><a href="index.php?sec=network&sec2=operation/agentes/networkmap&tab=view&id_networkmap=' . $network_map['id_networkmap'] . '">' . $network_map['name'] . '</a></b>';
		$data[] = $network_map['type'];
		
		$data[] = ui_print_group_icon ($network_map['id_group'], true);
		if (check_acl ($config["id_user"], 0, "AW")) {
			$data[] = '<a href="index.php?sec=network&sec2=operation/agentes/networkmap&tab=edit&edit_networkmap=1&id_networkmap=' . $network_map['id_networkmap'] . '" alt="' . __('Config') . '">' . html_print_image("images/config.png", true) . '</a>';
			$data[] = '<a href="index.php?sec=network&sec2=operation/agentes/networkmap_list&delete_networkmap=1&id_networkmap=' . $network_map['id_networkmap'] . '" alt="' . __('Delete') . '" onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' . html_print_image('images/cross.png', true) . '</a>';
		}
		
		$table->data[] = $data;
	}
	
	html_print_table($table);
}

// Create networkmap form
$networkmap_types = networkmap_get_types();
echo "<table style='width: 100%' class='databox'>";
echo "<tr><td class='datos' style='width: 50%'>";
echo '<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/networkmap">';
html_print_input_hidden('add_networkmap', 1);
html_print_select($networkmap_types, 'tab', 'topology', '');
echo "</td><td class='datos'>";
html_print_submit_button (__('Create networkmap'), 'crt', false, 'class="sub next"');
echo "</form>";
echo "</td></tr></table>";

?>
