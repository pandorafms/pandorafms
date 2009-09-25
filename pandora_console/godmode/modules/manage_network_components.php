<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_network_components.php');

$type = (int) get_parameter ('type');
$name = (string) get_parameter ('name');
$description = (string) get_parameter ('description');
$max = (int) get_parameter ('max');
$min = (int) get_parameter ('min');
$tcp_send = (string) get_parameter ('tcp_send');
$tcp_rcv = (string) get_parameter ('tcp_rcv');
$tcp_port = (int) get_parameter ('tcp_port');
$snmp_oid = (string) get_parameter ('snmp_oid');
$snmp_community = (string) get_parameter ('snmp_community');
$id_module_group = (int) get_parameter ('id_module_group');
$module_interval = (int) get_parameter ('module_interval');
$id_group = (int) get_parameter ('id_group');
$plugin_user = (string) get_parameter ('plugin_user');
$plugin_pass = (string) get_parameter ('plugin_pass');
$plugin_parameter = (string) get_parameter ('plugin_parameter');
$max_timeout = (int) get_parameter ('max_timeout');
$id_modulo = (int) get_parameter ('id_component_type');
$id_plugin = (int) get_parameter ('id_plugin');
$min_warning = (int) get_parameter ('min_warning');
$max_warning = (int) get_parameter ('max_warning');
$min_critical = (int) get_parameter ('min_critical');
$max_critical = (int) get_parameter ('max_critical');
$ff_event = (int) get_parameter ('ff_event');
$history_data = (bool) get_parameter ('history_data');
$id = (int) get_parameter ('id');

$create_component = (bool) get_parameter ('create_component');
$update_component = (bool) get_parameter ('update_component');
$delete_component = (bool) get_parameter ('delete_component');
$new_component = (bool) get_parameter ('new_component');
$duplicate_network_component = (bool) get_parameter ('duplicate_network_component');
$delete_multiple = (bool) get_parameter('delete_multiple');

if ($duplicate_network_component) {
	$source_id = (int) get_parameter ('source_id');
	
	$id = duplicate_network_component ($source_id);
	print_result_message ($id,
		__('Successfully created from %s', get_network_component_name ($source_id)),
		__('Could not be created'));
	
	//List unset for jump the bug in the pagination (TODO) that the make another
	//copy for each pass into pages.
	unset($_GET['source_id']);
	unset($_GET['duplicate_network_component']);
	
	$id = 0;
}

if ($create_component) {
	$id = create_network_component ($name, $type, $id_group, 
		array ('description' => $description,
			'module_interval' => $module_interval,
			'max' => $max,
			'min' => $min,
			'tcp_send' => $tcp_send,
			'tcp_rcv' => $tcp_rcv,
			'tcp_port' => $tcp_port,
			'snmp_oid' => $snmp_oid,
			'snmp_community' => $snmp_community,
			'id_module_group' => $id_module_group,
			'id_modulo' => $id_modulo,
			'id_plugin' => $id_plugin,
			'plugin_user' => $plugin_user,
			'plugin_pass' => $plugin_pass,
			'plugin_parameter' => $plugin_parameter,
			'max_timeout' => $max_timeout,
			'history_data' => $history_data,
			'min_warning' => $min_warning,
			'max_warning' => $max_warning,
			'min_critical' => $min_critical,
			'max_critical' => $max_critical,
			'min_ff_event' => $ff_event));
	if ($id === false) {
		print_error_message (__('Could not be created'));
		include_once ('godmode/modules/manage_network_components_form.php');
		return;
	}
	print_success_message (__('Created successfully'));
	$id = 0;
}

if ($update_component) {
	$id = (int) get_parameter ('id');
	
	$result = update_network_component ($id,
		array ('type' => $type,
			'name' => $name,
			'id_group' => $id_group,
			'description' => $description,
			'module_interval' => $module_interval,
			'max' => $max,
			'min' => $min,
			'tcp_send' => $tcp_send,
			'tcp_rcv' => $tcp_rcv,
			'tcp_port' => $tcp_port,
			'snmp_oid' => $snmp_oid,
			'snmp_community' => $snmp_community,
			'id_module_group' => $id_module_group,
			'id_modulo' => $id_modulo,
			'id_plugin' => $id_plugin,
			'plugin_user' => $plugin_user,
			'plugin_pass' => $plugin_pass,
			'plugin_parameter' => $plugin_parameter,
			'max_timeout' => $max_timeout,
			'history_data' => $history_data,
			'min_warning' => $min_warning,
			'max_warning' => $max_warning,
			'min_critical' => $min_critical,
			'max_critical' => $max_critical,
			'min_ff_event' => $ff_event));
	if ($result === false) {
		print_error_message (__('Could not be updated'));
		include_once ('godmode/modules/manage_network_components_form.php');
		return;
	}
	print_success_message (__('Updated successfully'));
	
	$id = 0;
}

if ($delete_component) {
	$id = (int) get_parameter ('id');
	
	$result = delete_network_component ($id);
	
	print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
	$id = 0;
}

if ($id || $new_component) {
	include_once ('godmode/modules/manage_network_components_form.php');
	return;
}

$url = get_url_refresh (array ('offset' => false,
	'id' => false,
	'create_component' => false,
	'update_component' => false,
	'delete_component' => false,
	'id_network_component' => false,
	'upd' => false,
	'crt' => false,
	'type' => false,
	'name' => false,
	'description' => false,
	'max' => false,
	'min' => false,
	'tcp_send' => false,
	'tcp_rcv' => false,
	'tcp_port' => false,
	'snmp_oid' => false,
	'snmp_community' => false,
	'id_module_group' => false,
	'module_interval' => false,
	'id_group' => false,
	'plugin_user' => false,
	'plugin_pass' => false,
	'plugin_parameter' => false,
	'max_timeout' => false,
	'id_modulo' => false,
	'id_plugin' => false,
	'history_data' => false,
	'min_warning' => false,
	'max_warning' => false,
	'min_critical' => false,
	'max_critical' => false,
	'ff_event' => false,
	'id_component_type' => false));

echo "<h2>".__('Module management')." &raquo; ";
echo __('Module component management')."</h2>";

$search_id_group = (int) get_parameter ('search_id_group');
$search_string = (string) get_parameter ('search_string');

$table->width = '600px';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';
$table->data = array ();

$table->data[0][0] = __('Group');
$table->data[0][1] = print_select (get_network_component_groups (),
	'search_id_group', $search_id_group, '', __('All'), 0, true, false, false);
$table->data[0][2] = __('Search');
$table->data[0][3] = print_input_text ('search_string', $search_string, '', 25,
	255, true);
$table->data[0][4] = '<div class="action-buttons">';
$table->data[0][4] .= print_submit_button (__('Search'), 'search', false,
	'class="sub search"', true);
$table->data[0][4] .= '</div>';

echo '<form method="post" action="'.$url.'">';
print_table ($table);
echo '</form>';

$filter = array ();
if ($search_id_group)
	$filter['id_group'] = $search_id_group;
if ($search_string != '')
	$filter[] = '(name LIKE "%'.$search_string.'%" OR description LIKE "%'.$search_string.'%" OR tcp_send LIKE "%'.$search_string.'%" OR tcp_rcv LIKE "%'.$search_string.'%")';

$total_components = get_network_components (false, $filter, 'COUNT(*) AS total');
$total_components = $total_components[0]['total'];
pagination ($total_components, $url);
$filter['offset'] = (int) get_parameter ('offset');
$filter['limit'] = (int) $config['block_size'];
$components = get_network_components (false, $filter,
	array ('id_nc', 'name', 'description', 'id_group', 'type', 'max', 'min',
		'module_interval'));
if ($components === false)
	$components = array ();

unset ($table);
$table->width = '95%';
$table->head = array ();
$table->head[0] = __('Module name');
$table->head[1] = __('Type');
$table->head[2] = __('Interval');
$table->head[3] = __('Description');
$table->head[4] = __('Group');
$table->head[5] = __('Max/Min');
$table->head[6] = __('Action');
$table->size = array ();
$table->size[6] = '50px';
$table->data = array ();

foreach ($components as $component) {
	$data = array ();
	
	if ($component['max'] == $component['min'] && $component['max'] == 0) {
		$component['max'] = $component['min'] = __('N/A');
	}
	
	$data[0] = '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&id='.$component['id_nc'].'">';
	$data[0] .= $component['name'];
	$data[0] .= '</a>';
	$data[1] = print_moduletype_icon ($component['type'], true);
	$data[2] = $component['module_interval'] ? $component['module_interval'] : __('N/A	');
	$data[3] = substr ($component['description'], 0, 30);
	$data[4] = get_network_component_group_name ($component['id_group']);
	$data[5] = $component['max']." / ".$component['min'];
	
	$data[6] = '<a style="display: inline; float: left" href="' . $url . '&search_id_group='  . $search_id_group .
		'search_string=' . $search_string . '&duplicate_network_component=1&source_id=' . $component['id_nc'] . '">' . 
		print_input_image ('dup', 'images/copy.png', 1, '', true, array ('title' => __('Duplicate'))) . '</a>';
//	$data[6] .= '<form method="post" action="index.php?sec=galertas&sec2=godmode/modules/manage_network_components" style="display: inline; float: left">';
//	$data[6] .= print_input_hidden ('duplicate_network_component', 1, true);
//	$data[6] .= print_input_hidden ('source_id', $component['id_nc'], true);
//	$data[6] .= print_input_image ('dup', 'images/copy.png', 1, '', true, array ('title' => __('Duplicate')));
//	$data[6] .= '</form> ';
	$data[6] .= '<a href="' . $url . '&delete_component=1&id=' . $component['id_nc'] . '&search_id_group='  . $search_id_group .
		'search_string=' . $search_string . 
		'" onclick="if (! confirm (\''.__('Are you sure?').'\')) return false" >' . 
		print_input_image ('delete', 'images/cross.png', 1, '', true, array ('title' => __('Delete'))) . '</a>';
//	$data[6] .= '<form method="post" action="'.$url.'" onsubmit="if (! confirm (\''.__('Are you sure?').'\')) return false">';
//	$data[6] .= print_input_hidden ('delete_component', 1, true);
//	$data[6] .= print_input_hidden ('id', $component['id_nc'], true);
//	$data[6] .= print_input_hidden ('search_id_group', $search_id_group, true);
//	$data[6] .= print_input_hidden ('search_string', $search_string, true);
//	$data[6] .= print_input_image ('delete', 'images/cross.png', 1, '', true,
//			array ('title' => __('Delete')));
//	$data[6] .= '</form>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<form method="post" action="'.$url.'">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_input_hidden ('new_component', 1);
print_select (array (2 => __('Create a new network component'),
	4 => __('Create a new plugin component'),
	6 => __('Create a new WMI component')),
	'id_component_type', '', '', '', '', '');
print_submit_button (__('Create'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>'
?>
