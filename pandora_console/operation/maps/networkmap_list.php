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

$buttons['list'] = array('active' => true,
	'text' => '<a href="index.php?sec=network&sec2=operation/maps/networkmap_list">' .
		html_print_image("images/list.png", true,
			array ('title' => __('List of networkmaps'))) .
		'</a>');

ui_print_page_header(
	__('Network map'),
	"images/op_network.png",
	false,
	"network_map",
	false,
	$buttons);

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
$multiple_delete = (bool)get_parameter('multiple_delete', 0);
$duplicate_networkmap = (bool)get_parameter('duplicate_networkmap', 0);
$update_networkmap = (bool)get_parameter('update_networkmap', 0);
$save_networkmap = (bool)get_parameter('save_networkmap', 0);

if ($multiple_delete) {
	$ids_multiple_delete = json_decode(
		io_safe_output(
			get_parameter('ids_multiple_delete',
				json_encode(array())
			)
		),
		true);
	
	$total = count($ids_multiple_delete);
	$count = 0;
	foreach ($ids_multiple_delete as $id) {
		if (maps_delete_map($id)) {
			$count++;
		}
	}
	
	ui_print_result_message (($total > 0 && $total == $count),
		__('Successfully deleted'),
		__('Could not be deleted'));
}

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
	$source_data = (string) get_parameter('source_data', MAP_SOURCE_GROUP);
	switch ($source_data) {
		case MAP_SOURCE_GROUP:
			$source = (string) get_parameter('source_group', 0);
			break;
		case MAP_SOURCE_IP_MASK:
			$source = (string) get_parameter('source_ip_mask', 0);
			break;
	}
	$width = (int) get_parameter('width', 800);
	$height = (int) get_parameter('height', 800);
	$generation_method = (int) get_parameter('generation_method', MAP_GENERATION_CIRCULAR);
	$show_groups_filter = (int) get_parameter('show_groups_filter', false);
	$show_module_plugins = (int) get_parameter('show_module_plugins', false);
	$show_snmp_modules = (int) get_parameter('show_snmp_modules', false);
	$show_modules = (int) get_parameter('show_modules', false);
	$show_policy_modules = (int) get_parameter('show_policy_modules', false);
	$show_pandora_nodes = (int) get_parameter('show_pandora_nodes', false);
	$show_module_group = (int) get_parameter('show_module_group', false);
	$id_tag = (int) get_parameter('id_tag', 0);
	$text = (string) get_parameter('text', "");
	
	$values = array();
	$values['name'] = $name;
	$values['id_user'] = $config['id_user'];
	$values['id_group'] = $id_group;
	$values['subtype'] = $subtype;
	$values['type'] = $type;
	$values['description'] = $description;
	$values['source_period'] = $source_period;
	$values['source_data'] = $source_data;
	$values['generation_method'] = $generation_method;
	$values['width'] = $width;
	$values['height'] = $height;
	$values['source'] = $source;
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
	$networkmap_names = db_get_all_rows_sql("SELECT name FROM tmap");
	
	$same_name = false;
	foreach ($networkmap_names as $networkmap_name) {
		if ($networkmap_name == $name) {
			$same_name = true;
		}
	}
	
	if (!empty($name) && !$same_name) {
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
		
		if (!$result_delete) {
			db_pandora_audit( "Networkmap management",
				"Fail try to delete networkmap #$id");
		}
		else {
			db_pandora_audit( "Networkmap management",
				"Delete networkmap #$id");
		}
		
		ui_print_result_message ($result_delete,
			__('Successfully deleted'),
			__('Could not be deleted'));
	}
	
	else if ($duplicate_networkmap) {
		$result_duplicate = maps_duplicate_map($id);
		
		ui_print_result_message ($result_duplicate,
			__('Successfully duplicate'),
			__('Could not be duplicate'));
	}
	
	else if ($update_networkmap) {
		$id_group = (int) get_parameter('id_group', 0);
		$name = (string) get_parameter('name', "");
		$description = (string) get_parameter('description', "");
		$source_period = (int) get_parameter('source_period', 60 * 5);
		$source = (int) get_parameter('source', MAP_SOURCE_GROUP);
		$source_data = (string) get_parameter('source_data', 'group');
		$width = (int) get_parameter('width', 800);
		$height = (int) get_parameter('height', 800);
		$show_groups_filter = (int) get_parameter('show_groups_filter', false);
		$show_module_plugins = (int) get_parameter('show_module_plugins', false);
		$show_snmp_modules = (int) get_parameter('show_snmp_modules', false);
		$show_modules = (int) get_parameter('show_modules', false);
		$show_policy_modules = (int) get_parameter('show_policy_modules', false);
		$show_pandora_nodes = (int) get_parameter('show_pandora_nodes', false);
		$show_module_group = (int) get_parameter('show_module_group', false);
		$id_tag = (int) get_parameter('id_tag', 0);
		$text = (string) get_parameter('text', "");
		
		$values = array();
		$values['name'] = $name;
		$values['id_group'] = $id_group;
		$values['description'] = $description;
		$values['source_period'] = $source_period;
		$values['source_data'] = $source_data;
		$values['source'] = $source;
		$values['width'] = $width;
		$values['height'] = $height;
		$filter = array();
		$filter['show_groups_filter'] = $show_groups_filter;
		$filter['show_module_plugins'] = $show_module_plugins;
		$filter['show_snmp_modules'] = $show_snmp_modules;
		$filter['show_modules'] = $show_modules;
		$filter['show_policy_modules'] = $show_policy_modules;
		$filter['show_pandora_nodes'] = $show_pandora_nodes;
		$filter['show_module_group'] = $show_module_group;
		$filter['id_tag'] = $id_tag;
		$filter['text'] = $text;
		$values['filter'] = json_encode($filter);
		
		$result_update = false;
		if (!empty($name)) {
			$result_update = maps_update_map($id, $values);
		}
		
		ui_print_result_message ($result_update,
			__('Successfully updated'),
			__('Could not be updated'));
	}
}

//+++++++++++++++TABLE AND EDIT/CREATION BUTTONS++++++++++++++++++++++
$table = new stdClass();
$table->id = "list_networkmaps";
$table->width = "100%";
$table->class = "databox data";

$table->style = array();
$table->style['name'] = 'text-align: left;';
$table->style['type'] = 'text-align: left;';
if (enterprise_installed()) {
	$table->style['nodes'] = 'text-align: left;';
}
$table->style['group'] = 'text-align: center;';
$table->style['copy'] = 'text-align: left;';
$table->style['edit'] = 'text-align: left;';
$table->style['delete'] = 'text-align: left;';

$table->size = array();
$table->size[0] = '60%';
$table->size[1] = '60px';
$table->size[2] = '70px';

$table->head = array();
$table->head['name'] = __('Name');
$table->head['type'] = __('Type');
if (enterprise_installed()) {
	$table->head['nodes'] = __('Nodes');
}
$table->head['group'] = __('Group');
$table->head['copy'] = __('Copy');
$table->head['edit'] = __('Edit');
$table->head['delete'] = __('Delete') .
	html_print_checkbox('delete_all', 0, false, true, false, 'checkbox_delete_all();');

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
			'sec2=operation/maps/networkmap&' .
			'id=' . $networkmap['id'] .'">' .
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
		$data['delete'] .=
			html_print_checkbox("delete_id[" . $networkmap['id'] . "]",
				1, false, true);
		
		
		$table->data[] = $data;
	}
	html_print_table($table);
}
echo '<form id="multiple_delete" method="post" style="float:right;" action="index.php?sec=network&sec2=operation/maps/networkmap_list">';
html_print_input_hidden ('multiple_delete', 1);
html_print_input_hidden ('ids_multiple_delete', "");
html_print_button(__('Delete'), 'del', false, 'submit_multiple_delete();', 'class="sub delete"');
echo '</form>';

echo '<form method="post" style="float:right; margin-right: 10px;" action="index.php?sec=maps&amp;sec2=operation/maps/networkmap_editor">';
html_print_input_hidden ('create_networkmap', 1);
html_print_submit_button (__('Create'), "crt", false, 'class="sub next"');
echo '</form>';


?>


<script type="text/javascript">
	function submit_multiple_delete() {
		var ids_multiple_delete = [];
		$.each(
			$("#list_networkmaps tbody input[type='checkbox']:checked"),
			function(i,e) {
				ids_multiple_delete.push(
					$(e).attr("name").match(/\[(.*)\]/)[1]);
			}
		);
		
		$("input[name='ids_multiple_delete']").val(
			JSON.stringify(ids_multiple_delete));
		
		if (confirm('<?php echo __('Are you sure?');?>'))
			$("#multiple_delete").submit();
	}
	
	function checkbox_delete_all() {
		if ($("input[name='delete_all']").prop("checked")) {
			$("#list_networkmaps tbody input[type='checkbox']")
				.prop("checked", true);
		}
		else {
			$("#list_networkmaps tbody input[type='checkbox']")
				.prop("checked", false);
		}
	}
</script>
