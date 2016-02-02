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

require_once('include/functions_migration.php');

ui_print_page_header(__('Network map'), "images/op_network.png", false, "network_map", false);

////////////////////////////////////////////////////////////////////////
// It is dirty but at the moment (minor release is not)
// this place is the place for migration
////////////////////////////////////////////////////////////////////////

$migrate_open_networkmaps = (int)get_parameter('migrate_open_networkmaps');

if ($migrate_open_networkmaps)
	migration_open_networkmaps();

?>
<br />
<a href="index.php?sec=network&sec2=operation/maps/networkmap_list&migrate_open_networkmaps=1">(temp, this is for minor relases) migrate open networkmaps</a>
<br />
<br />
<?php
////////////////////////////////////////////////////////////////////////

$delete_networkmap = (bool)get_parameter('delete_networkmap', 0);
$duplicate_networkmap = (bool)get_parameter('duplicate_networkmap', 0);
$update_networkmap = (bool)get_parameter('update_networkmap', 0);
$save_networkmap = (bool)get_parameter('save_networkmap', 0);

if ($save_networkmap) {
	$id_group = (int) get_parameter('id_group', 0);

	$networkmap_write = check_acl ($config['id_user'], $id_group, "MW");
	$networkmap_manage = check_acl ($config['id_user'], $id_group, "MM");

	if (!$networkmap_write && !$networkmap_manage) {
		db_pandora_audit("ACL Violation",
			"Trying to access networkmap enterprise");
		require ("general/noaccess.php");
		return;
	}

	$type = MAP_TYPE_NETWORKMAP;
	$subtype = (int) get_parameter('subtype', MAP_SUBTYPE_GROUPS);
	$name = (string) get_parameter('name', "");
	$description = (string) get_parameter('description', "");
	$source_period = (int) get_parameter('source_period', 60 * 5);
	$source = (int) get_parameter('source', MAP_SOURCE_GROUP);
	$source_data = get_parameter('source_data', 'group');
	$generation_method = get_parameter('generation_method', MAP_GENERATION_CIRCULAR);
	$show_groups_filter = get_parameter('show_groups_filter', false);
	$show_module_plugins = get_parameter('show_module_plugins', false);
	$show_snmp_modules = get_parameter('show_snmp_modules', false);
	$show_modules = get_parameter('show_modules', false);
	$show_policy_modules = get_parameter('show_policy_modules', false);
	$show_pandora_nodes = get_parameter('show_pandora_nodes', false);
	$show_module_group = get_parameter('show_module_group', false);
	$id_tag = get_parameter('id_tag', 0);
	$text = get_parameter('text', "");

	$values = array();
	$values['name'] = $name;
	$values['id_group'] = $id_group;
	$values['subtype'] = $subtype;
	$values['type'] = $$type;
	$values['description'] = $description;
	$values['source_period'] = $source_period;
	$values['source_data'] = $source_data;
	$values['generation_method'] = $generation_method;

	$filter = array();
	$filter['show_groups_filter'] = 60;
	$filter['show_module_plugins'] = $show_module_plugins;
	$filter['show_snmp_modules'] = $show_snmp_modules;
	$filter['show_modules'] = $show_modules;
	$filter['show_policy_modules'] = $show_policy_modules;
	$filter['show_pandora_nodes'] = $show_pandora_nodes;
	$filter['show_module_group'] = $show_module_group;
	$filter['id_tag'] = $id_tag;
	$filter['text'] = $text;
	$values['filter'] = json_encode($filter);

	$result_add = false;
	if (!empty($name)) {
		$result_add = maps_save_map($values);
	}

	ui_print_result_message ($result_add,
		__('Successfully created'),
		__('Could not be created'));
}
else if ($delete_networkmap || $duplicate_networkmap || $update_networkmap) {
	$id = (int)get_parameter('id_networkmap', 0);

	if (empty($id)) {
		db_pandora_audit("ACL Violation",
			"Trying to access networkmap enterprise");
		require ("general/noaccess.php");
		return;
	}

	$id_group_old = db_get_value('id_group', 'tmap', 'id', $id);
	if ($id_group_old === false) {
		db_pandora_audit("ACL Violation",
			"Trying to accessnode graph builder");
		require ("general/noaccess.php");
		return;
	}

	$networkmap_write_old_group = check_acl ($config['id_user'], $id_group_old, "MW");
	$networkmap_manage_old_group = check_acl ($config['id_user'], $id_group_old, "MM");

	if (!$networkmap_write_old_group && !$networkmap_manage_old_group) {
		db_pandora_audit("ACL Violation",
			"Trying to access networkmap");
		require ("general/noaccess.php");
		return;
	}

	if ($delete_networkmap) {
		$result_delete = maps_delete_map($id);

		if ($result_delete) {
			db_pandora_audit( "Networkmap management",
				"Delete networkmap #$id");
		}
		else {
			db_pandora_audit( "Networkmap management",
				"Fail try to delete networkmap #$id");
		}

		ui_print_result_message ($result_delete,
			__('Successfully deleted'),
			__('Could not be deleted'));
	}

	else if ($duplicate_networkmap) {
		$result_duplicate = maps_duplicate_map($id);

		ui_print_result_message ($result,
			__('Successfully duplicate'),
			__('Could not be duplicate'));
	}

	else if ($update_networkmap) {
		$name = (string) get_parameter('name', "");
		$description = (string) get_parameter('description', "");
		$source_period = (int) get_parameter('source_period', 60 * 5);
		$source = (int) get_parameter('source', MAP_SOURCE_GROUP);
		$source_data = get_parameter('source_data', 'group');
		$show_groups_filter = get_parameter('show_groups_filter', false);
		$show_module_plugins = get_parameter('show_module_plugins', false);
		$show_snmp_modules = get_parameter('show_snmp_modules', false);
		$show_modules = get_parameter('show_modules', false);
		$show_policy_modules = get_parameter('show_policy_modules', false);
		$show_pandora_nodes = get_parameter('show_pandora_nodes', false);
		$show_module_group = get_parameter('show_module_group', false);
		$id_tag = get_parameter('id_tag', 0);
		$text = get_parameter('text', "");

		$values = array();
		$values['name'] = $name;
		$values['id_group'] = $id_group;
		$values['description'] = $description;
		$values['source_period'] = $source_period;
		$values['source_data'] = $source_data;

		$filter = array();
		$filter['show_groups_filter'] = 60;
		$filter['show_module_plugins'] = $show_module_plugins;
		$filter['show_snmp_modules'] = $show_snmp_modules;
		$filter['show_modules'] = $show_modules;
		$filter['show_policy_modules'] = $show_policy_modules;
		$filter['show_pandora_nodes'] = $show_pandora_nodes;
		$filter['show_module_group'] = $show_module_group;
		$filter['id_tag'] = $id_tag;
		$filter['text'] = $text;
		$values['filter'] = json_encode($filter);

		$result_add = false;
		if (!empty($name)) {
			$result_add = maps_update_map($id, $values);
		}

		ui_print_result_message ($result_add,
			__('Successfully updated'),
			__('Could not be updated'));
	}
}

//+++++++++++++++TABLE AND EDIT/CREATION BUTTONS++++++++++++++++++++++
$table = new stdClass();
$table->width = "100%";
$table->class = "databox data";
$table->headstyle['name'] = 'text-align: center;';
$table->headstyle['type'] = 'text-align: center;';
if (enterprise_installed()) {
	$table->headstyle['nodes'] = 'text-align: center;';
}
$table->headstyle['group'] = 'text-align: center;';
$table->headstyle['copy'] = 'text-align: center;';
$table->headstyle['edit'] = 'text-align: center;';
$table->headstyle['delete'] = 'text-align: center;';

$table->style = array();
$table->style['name'] = 'text-align: left;';
$table->style['type'] = 'text-align: center;';
if (enterprise_installed()) {
	$table->style['nodes'] = 'text-align: center;';
}
$table->style['group'] = 'text-align: center;';
$table->style['copy'] = 'text-align: center;';
$table->style['edit'] = 'text-align: center;';
$table->style['delete'] = 'text-align: center;';

$table->size = array();
$table->size['name'] = '60%';
$table->size['type'] = '30px';
if (enterprise_installed()) {
	$table->size['nodes'] = '30px';
}
$table->size['group'] = '30px';
$table->size['copy'] = '30px';
$table->size['edit'] = '30px';
$table->size['delete'] = '30px';

$table->head = array();
$table->head['name'] = __('Name');
$table->head['type'] = __('Type');
if (enterprise_installed()) {
	$table->head['nodes'] = __('Nodes');
}
$table->head['group'] = __('Group');
$table->head['copy'] = __('Copy');
$table->head['edit'] = __('Edit');
$table->head['delete'] = __('Delete');

$networkmaps = maps_get_maps(array('type' => MAP_TYPE_NETWORKMAP));

if (empty($networkmaps)) {
	ui_print_info_message (
		array('no_close'=>true,
			'message'=> __('There are no networkmaps defined.') ) );
}
else {
	foreach ($networkmaps as $networkmap) {
		$data = array();
		
		$data['name'] = $networkmap['name'];
		
		$data['name'] = '<a href="index.php?' .
			'sec=maps&amp;' .
			'sec2=operation/maps/networkmap_editor&' .
			'id_networkmap=' . $networkmap['id'] .'">' .
			$networkmap['name'] . '</a>';
		
		$data['type'] = maps_get_subtype_string($networkmap['subtype']);
		
		
		if (enterprise_installed()) {
			if ($networkmap['generated']) {
				$data['nodes'] = maps_get_count_nodes($networkmap['id']);
			}
			else {
				$data['nodes'] = __('Pending to generate');
			}
		}
		
		if (!empty($networkmap['id_user'])) {
			$data['group'] = __('Private for (%s)', $networkmap['id_user']);
		}
		else {
			$data['groups'] =
				ui_print_group_icon($networkmap['id_group'], true);
		}
		
		$data['copy'] = '<a href="index.php?' .
			'sec=maps&amp;' .
			'sec2=operation/maps/networkmap_list&' .
			'duplicate_networkmap=1&id_networkmap=' . $networkmap['id'] . '" alt="' . __('Copy') . '">' .
			html_print_image("images/copy.png", true) . '</a>';
		
		$data['edit'] = '<a href="index.php?' .
			'sec=maps&amp;' .
			'sec2=operation/maps/networkmap_editor&' .
			'edit_networkmap=1&id_networkmap=' . $networkmap['id'] .'">' .
			html_print_image("images/edit.png", true) . '</a>';
		
		$data['delete'] = '<a href="index.php?' .
			'sec=maps&amp;' .
			'sec2=operation/maps/networkmap_list&' .
			'delete_networkmap=1&id_networkmap=' . $networkmap['id'] . '" alt="' . __('Delete') .
			'" onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' .
			html_print_image('images/cross.png', true) . '</a>';
		
		$table->data[] = $data;
	}
	html_print_table($table);
}

echo '<form method="post" style="float:right;" action="index.php?sec=maps&amp;sec2=operation/maps/networkmap_editor">';
html_print_input_hidden ('create_networkmap', 1);
html_print_submit_button (__('Create'), "crt", false, 'class="sub next"');
echo '</form>';

?>


<script type="text/javascript">
</script>
