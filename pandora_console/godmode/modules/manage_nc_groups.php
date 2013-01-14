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
		"Trying to access SNMP Group Management");
	require ("general/noaccess.php");
	return;
}

// Header
ui_print_page_header (__('Module management').' &raquo; '. __('Component group management'), "", false, "component_groups", true);


require_once ($config['homedir'] . '/include/functions_network_components.php');
require_once ($config['homedir'] . '/include/functions_component_groups.php');

$create = (bool) get_parameter ('create');
$update = (bool) get_parameter ('update');
$delete = (bool) get_parameter ('delete');
$new = (bool) get_parameter ('new');
$id = (int) get_parameter ('id');
$multiple_delete = (bool)get_parameter('multiple_delete', 0);
	
if ($create) {
	$name = (string) get_parameter ('name');
	$parent = (int) get_parameter ('parent');
	
	if ($name == '') {
		ui_print_error_message (__('Could not be created. Blank name'));
		require_once ('manage_nc_groups_form.php');
		return;
	} else {
		$result = db_process_sql_insert ('tnetwork_component_group',
			array ('name' => $name,
				'parent' => $parent));
		ui_print_result_message ($result,
			__('Successfully created'),
			__('Could not be created'));
		}
}

if ($update) {
	$name = (string) get_parameter ('name');
	$parent = (int) get_parameter ('parent');
	
	if ($name == '') {
                ui_print_error_message (__('Not updated. Blank name'));
        } else {
		$result = db_process_sql_update ('tnetwork_component_group',
			array ('name' => $name,
				'parent' => $parent),
			array ('id_sg' => $id));
		ui_print_result_message ($result,
			__('Successfully updated'),
			__('Not updated. Error updating data'));
	}
}

if ($delete) {
	$parent_id = db_get_value_filter('parent', 'tnetwork_component_group', array('id_sg' => $id));
	
	$result1 = db_process_sql_update('tnetwork_component_group', array('parent' => $parent_id), array('parent' => $id));

	$result = db_process_sql_delete ('tnetwork_component_group',
		array ('id_sg' => $id));
		
	if (($result !== false) and ($result1 !== false)) $result = true;
	else $result = false;
		
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}

if ($multiple_delete) {
	$ids = (array)get_parameter('delete_multiple', array());
	
	db_process_sql_begin();
	
	foreach ($ids as $id) {
		$result = db_process_sql_delete ('tnetwork_component_group',
			array ('id_sg' => $id));

		$result1 = db_process_sql_update('tnetwork_component_group', array('parent' => 0), array('parent' => $id));	
	
		if (($result === false) or ($result1 === false)) {
			db_process_sql_rollback();
			break;
		}
	}
	
	if ($result !== false) {
		db_process_sql_commit();
	}
	
	if ($result !== false) $result = true;
	else $result = false;
		
	ui_print_result_message ($result,
		__('Successfully multiple deleted'),
		__('Not deleted. Error deleting multiple data'));
}

if (($id || $new) && !$delete && !$multiple_delete) {
	require_once ('manage_nc_groups_form.php');
	return;
}

$url = ui_get_url_refresh (array ('offset' => false,
	'create' => false,
	'update' => false,
	'delete' => false,
	'new' => false,
	'crt' => false,
	'upd' => false,
	'id' => false));

$filter = array ();

//$filter['offset'] = (int) get_parameter ('offset');
//$filter['limit'] = (int) $config['block_size'];
$filter['order'] = 'parent';

$groups = db_get_all_rows_filter ('tnetwork_component_group', $filter);
if ($groups === false)
	$groups = array ();

$groups_clean = array();
foreach ($groups as $group_key => $group_val) {
	$groups_clean[$group_val['id_sg']] = $group_val;
}

// Format component groups in tree form
$groups = component_groups_get_groups_tree_recursive($groups_clean,0,0);

$table->width = '98%';
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Action') .
	html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->align = array ();
$table->align[1] = 'center';
$table->size = array ();
$table->size[0] = '80%';
$table->size[1] = '50px';
$table->data = array ();

$total_groups = db_get_all_rows_filter ('tnetwork_component_group', false, 'COUNT(*) AS total');
$total_groups = $total_groups[0]['total'];

//ui_pagination ($total_groups, $url);

foreach ($groups as $group) {
	$data = array ();
	
	$tabulation = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $group['deep']);	
	
	$data[0] =  $tabulation . '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&id='.$group['id_sg'].'">'.$group['name'].'</a>';
		
	$data[1] = "<a onclick='if(confirm(\"" . __('Are you sure?') . "\")) return true; else return false;' 
		href='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&delete=1&id=".$group['id_sg']."&offset=0'>" . 
		html_print_image('images/cross.png', true, array('title' => __('Delete'))) . "</a>" .
		html_print_checkbox_extended ('delete_multiple[]', $group['id_sg'], false, false, '', 'class="check_delete"', true);
	
	array_push ($table->data, $data);
}

if(isset($data)) {
	echo "<form method='post' action='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups'>";
	html_print_input_hidden('multiple_delete', 1);
	html_print_table ($table);
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
	echo "</form>";
}
else {
	echo "<div class='nf'>".__('There are no defined component groups')."</div>";
}

echo '<form method="post">';
echo '<div class="action-buttons" style="width: '.$table->width.'; margin-top: 5px;">';
html_print_input_hidden ('new', 1);
html_print_submit_button (__('Create'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';
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
