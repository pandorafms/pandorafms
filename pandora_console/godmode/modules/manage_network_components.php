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

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

// Header
ui_print_page_header (__('Module management').' &raquo; '.__('Network component management'), "", false, "network_component", true);

require_once ('include/functions_network_components.php');
require_once ($config['homedir'].'/include/functions_component_groups.php');

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
$str_warning = (string) get_parameter ('str_warning');
$min_critical = (int) get_parameter ('min_critical');
$max_critical = (int) get_parameter ('max_critical');
$str_critical = (string) get_parameter ('str_critical');
$ff_event = (int) get_parameter ('ff_event');
$history_data = (bool) get_parameter ('history_data');
$post_process = (float) get_parameter('post_process');
$id = (int) get_parameter ('id');

$snmp_version = (string) get_parameter('snmp_version');
$snmp3_auth_user = (string) get_parameter('snmp3_auth_user');
$snmp3_auth_pass = (string) get_parameter('snmp3_auth_pass');
$snmp3_auth_method = (string) get_parameter('snmp3_auth_method');
$snmp3_privacy_method = (string) get_parameter('snmp3_privacy_method');
$snmp3_privacy_pass = (string) get_parameter('snmp3_privacy_pass');
$snmp3_security_level = (string) get_parameter('snmp3_security_level');

$create_component = (bool) get_parameter ('create_component');
$update_component = (bool) get_parameter ('update_component');
$delete_component = (bool) get_parameter ('delete_component');
$new_component = (bool) get_parameter ('new_component');
$duplicate_network_component = (bool) get_parameter ('duplicate_network_component');
$delete_multiple = (bool) get_parameter('delete_multiple');
$multiple_delete = (bool)get_parameter('multiple_delete', 0);

if ($duplicate_network_component) {
	$source_id = (int) get_parameter ('source_id');
	
	$id = network_components_duplicate_network_component ($source_id);
	ui_print_result_message ($id,
		__('Successfully created from %s', network_components_get_name ($source_id)),
		__('Could not be created'));
	
	//List unset for jump the bug in the pagination (TODO) that the make another
	//copy for each pass into pages.
	unset($_GET['source_id']);
	unset($_GET['duplicate_network_component']);
	
	$id = 0;
}

if ($create_component) {
	$custom_string_1 = '';
	$custom_string_2 = '';
	$custom_string_3 = '';
        $name_check = db_get_value ('name', 'tnetwork_component', 'name', $name);
	if ($type >= 15 && $type <= 18) {
		// New support for snmp v3
		$tcp_send = $snmp_version;
		$plugin_user = $snmp3_auth_user;
		$plugin_pass = $snmp3_auth_pass;
		$plugin_parameter = $snmp3_auth_method;
		$custom_string_1 = $snmp3_privacy_method;
		$custom_string_2 = $snmp3_privacy_pass;
		$custom_string_3 = $snmp3_security_level;
                $name_check = db_get_value ('name', 'tnetwork_component', 'name', $name);
	}
	if ($name && !$name_check) {
	
		$id = network_components_create_network_component ($name, $type, $id_group, 
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
				'str_warning' => $str_warning,
				'min_critical' => $min_critical,
				'max_critical' => $max_critical,
				'str_critical' => $str_critical,
				'min_ff_event' => $ff_event,
				'custom_string_1' => $custom_string_1,
				'custom_string_2' => $custom_string_2,
				'custom_string_3' => $custom_string_3,
				'post_process' => $post_process));
	}
	else {
	$id = '';
	}
	if ($id === false || !$id) {
		ui_print_error_message (__('Could not be created'));
		include_once ('godmode/modules/manage_network_components_form.php');
		return;
	}
	ui_print_success_message (__('Created successfully'));
	$id = 0;
}

if ($update_component) {
	$id = (int) get_parameter ('id');
	
	$custom_string_1 = '';
	$custom_string_2 = '';
	$custom_string_3 = '';
        //$name_check = db_get_value ('name', 'tnetwork_component', 'name', $name);
	if ($type >= 15 && $type <= 18) {
		// New support for snmp v3
		$tcp_send = $snmp_version;
		$plugin_user = $snmp3_auth_user;
		$plugin_pass = $snmp3_auth_pass;
		$plugin_parameter = $snmp3_auth_method;
		$custom_string_1 = $snmp3_privacy_method;
		$custom_string_2 = $snmp3_privacy_pass;
		$custom_string_3 = $snmp3_security_level;
		//$name_check = db_get_value ('name', 'tnetwork_component', 'name', $name); 
	}
	if (!empty($name)) { 
		$result = network_components_update_network_component ($id,
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
				'str_warning' => $str_warning,
				'min_critical' => $min_critical,
				'max_critical' => $max_critical,
				'str_critical' => $str_critical,
				'min_ff_event' => $ff_event,
				'custom_string_1' => $custom_string_1,
				'custom_string_2' => $custom_string_2,
				'custom_string_3' => $custom_string_3,
				'post_process' => $post_process));
	}
	else {
		$result = '';
	}
	if ($result === false || !$result) {
		ui_print_error_message (__('Could not be updated'));
		include_once ('godmode/modules/manage_network_components_form.php');
		return;
	}

	ui_print_success_message (__('Updated successfully'));
	
	$id = 0;
}

if ($delete_component) {
	$id = (int) get_parameter ('id');
	
	$result = network_components_delete_network_component ($id);
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
	$id = 0;
}

if ($multiple_delete) {
	$ids = (array)get_parameter('delete_multiple', array());
	
	db_process_sql_begin();
	
	foreach ($ids as $id) {
		$result = network_components_delete_network_component ($id);
		
		if ($result === false) {
			db_process_sql_rollback();
			break;
		}
	}
	
	if ($result !== false) {
		db_process_sql_commit();
	}
		
	ui_print_result_message ($result,
		__('Successfully multiple deleted'),
		__('Not deleted. Error deleting multiple data'));
	
	$id = 0;
}

if ($id || $new_component) {
	include_once ('godmode/modules/manage_network_components_form.php');
	return;
}

$url = ui_get_url_refresh (array ('offset' => false,
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
	'str_warning' => false,
	'min_critical' => false,
	'max_critical' => false,
	'str_critical' => false,
	'ff_event' => false,
	'id_component_type' => false));


$search_id_group = (int) get_parameter ('search_id_group');
$search_string = (string) get_parameter ('search_string');

$table->width = '98%';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';
$table->data = array ();

$table->data[0][0] = __('Group');

$component_groups  = network_components_get_groups ();

if ($component_groups === false)
	$component_groups = array();

foreach ($component_groups as $component_group_key => $component_group_val) {
	$num_components = db_get_num_rows('SELECT id_nc
										FROM tnetwork_component 
										WHERE id_group = ' . $component_group_key);
					
	$childs = component_groups_get_childrens($component_group_key);
	
	$num_components_childs = 0;
	
	if ($childs !== false) {
	
		foreach ($childs as $child) {
			
			$num_components_childs += db_get_num_rows('SELECT id 
									FROM tlocal_component 
									WHERE id_network_component_group = ' . $child['id_sg']);
		
		}
	
	}					
										
	// Only show component groups with local components
	if ($num_components  == 0 && $num_components_childs == 0)
		unset($component_groups[$component_group_key]);
}

$table->data[0][1] = html_print_select ($component_groups,
	'search_id_group', $search_id_group, '', __('All'), 0, true, false, false);
$table->data[0][2] = __('Search');
$table->data[0][3] = html_print_input_text ('search_string', $search_string, '', 25,
	255, true);
$table->data[0][4] = '<div class="action-buttons">';
$table->data[0][4] .= html_print_submit_button (__('Search'), 'search', false,
	'class="sub search"', true);
$table->data[0][4] .= '</div>';

echo '<form method="post" action="'.$url.'">';
html_print_table ($table);
echo '</form>';

$filter = array ();
if ($search_id_group)
	$filter['id_group'] = $search_id_group;
if ($search_string != '')
	$filter[] = '(name LIKE "%'.$search_string.'%" OR description LIKE "%'.$search_string.'%" OR tcp_send LIKE "%'.$search_string.'%" OR tcp_rcv LIKE "%'.$search_string.'%")';

$total_components = network_components_get_network_components (false, $filter, 'COUNT(*) AS total');
$total_components = $total_components[0]['total'];
ui_pagination ($total_components, $url);
$filter['offset'] = (int) get_parameter ('offset');
$filter['limit'] = (int) $config['block_size'];
$components = network_components_get_network_components (false, $filter,
	array ('id_nc', 'name', 'description', 'id_group', 'type', 'max', 'min',
		'module_interval'));
if ($components === false)
	$components = array ();

unset ($table);

$table->width = '100%';
$table->head = array ();
$table->head[0] = __('Module name');
$table->head[1] = __('Type');
$table->head[3] = __('Description');
$table->head[4] = __('Group');
$table->head[5] = __('Max/Min');
$table->head[6] = __('Action') .
	html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
$table->size = array ();
$table->size[6] = '60px';
$table->align[6] = 'center';
$table->data = array ();

foreach ($components as $component) {
	$data = array ();
	
	if ($component['max'] == $component['min'] && $component['max'] == 0) {
		$component['max'] = $component['min'] = __('N/A');
	}
	
	$data[0] = '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&id='.$component['id_nc'].'">';
	$data[0] .= io_safe_output($component['name']);
	$data[0] .= '</a>';
	$data[1] = ui_print_moduletype_icon ($component['type'], true);
	$data[3] = "<span style='font-size: 8px'>". mb_strimwidth (io_safe_output($component['description']), 0, 60, "...") . "</span>";
	$data[4] = network_components_get_group_name ($component['id_group']);
	$data[5] = $component['max']." / ".$component['min'];
	
	$data[6] = '<a style="display: inline; float: left" href="' . $url . '&search_id_group=' . $search_id_group .
		'search_string=' . $search_string . '&duplicate_network_component=1&source_id=' . $component['id_nc'] . '">' . 
		html_print_image('images/copy.png', true, array('alt' => __('Duplicate'), 'title' => __('Duplicate'))) . '</a>';
	$data[6] .= '<a href="' . $url . '&delete_component=1&id=' . $component['id_nc'] . '&search_id_group=' . $search_id_group .
		'search_string=' . $search_string . 
		'" onclick="if (! confirm (\''.__('Are you sure?').'\')) return false" >' . 
		html_print_image('images/cross.png', true, array('alt' => __('Delete'), 'title' => __('Delete'))) . '</a>' .
		html_print_checkbox_extended ('delete_multiple[]', $component['id_nc'], false, false, '', 'class="check_delete"', true);
	
	array_push ($table->data, $data);
}

if(isset($data)) {
	echo "<form method='post' action='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&search_id_group=0search_string='>";
	html_print_input_hidden('multiple_delete', 1);
	html_print_table ($table);
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
	echo "</form>";
}
else {
	echo "<div class='nf'>".__('There are no defined network components')."</div>";
}

echo '<form method="post" action="'.$url.'">';
echo '<div class="action-buttons" style="width: '.$table->width.';margin-top: 5px;">';
html_print_input_hidden ('new_component', 1);
html_print_select (array (2 => __('Create a new network component'),
	4 => __('Create a new plugin component'),
	6 => __('Create a new WMI component')),
	'id_component_type', '', '', '', '', '');
html_print_submit_button (__('Create'), 'crt', false, 'class="sub next" style="margin-left: 5px;"');
echo '</div>';
echo '</form>'
?>
<script type="text/javascript">
function check_all_checkboxes() {
	if ($("input[name=all_delete]").attr('checked')) {
		$(".check_delete").attr('checked', true);
	}
	else {
		$(".check_delete").attr('checked', false);
	}
}
</script>
