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

$id = (int)get_parameter('id_networkmap', 0);
$edit_networkmap = (int)get_parameter('edit_networkmap', 0);
$create_networkmap = (int)get_parameter('create_networkmap', 0);

if ($create_networkmap) {
	$id_group = 0;
	$type = MAP_TYPE_NETWORKMAP;
	$subtype = MAP_SUBTYPE_GROUPS;
	$name = "";
	$description = "";
	$source_period = 60 * 5;
	$source = MAP_SOURCE_GROUP;
	$source_data = "";
	$generation_method = MAP_GENERATION_CIRCULAR;
	$show_groups_filter = false;
	$show_module_plugins = false;
	$show_snmp_modules = false;
	$show_modules = false;
	$show_policy_modules = false;
	$show_pandora_nodes = false;
	$show_module_group = false;
	$id_tag = 0;
	$text = "";
}

$not_found = false;
$disabled_select = false;
if ($edit_networkmap) {
	$disabled_select= true;
	$values = db_get_row('tmap', 'id', $id);
	
	if ($values === false) {
		$not_found = true;
	}
	else {
		$id_group = $values['id_group'];
		
		$networkmap_write = check_acl ($config['id_user'], $id_group, "MW");
		$networkmap_manage = check_acl ($config['id_user'], $id_group, "MM");
		
		if (!$networkmap_write && !$networkmap_manage) {
			db_pandora_audit("ACL Violation",
				"Trying to access networkmap");
			require ("general/noaccess.php");
			return;
		}
		
		$type = MAP_TYPE_NETWORKMAP;
		$subtype = $values['subtype'];
		$name = io_safe_output($values['name']);
		$description = io_safe_output($values['description']);
		$source_period = $values['source_period'];
		$source = $values['source'];
		if ($source == 'group') {
			$source_data = $values['source_data'];
		}
		else {
			$source_data = $values['source_data'];
		}
		$generation_method = $values['generation_method'];
		$filter = json_decode($values['filter'], true);
		$show_groups_filter = $filter['show_groups_filter'];
		$show_module_plugins = $filter['show_module_plugins'];
		$show_snmp_modules = $filter['show_snmp_modules'];
		$show_modules = $filter['show_modules'];
		$show_policy_modules = $filter['show_policy_modules'];
		$show_pandora_nodes = $filter['show_pandora_nodes'];
		$show_module_group = $filter['show_module_group'];
		$id_tag = $filter['id_tag'];
		$text = io_safe_output($filter['text']);
	}
}

//+++++++++++++++TABLE TO CREATE/EDIT NETWORKMAP++++++++++++++++++++++

$buttons['list'] = array('active' => false,
	'text' => '<a href="index.php?sec=network&sec2=operation/maps/networkmap_list">' . 
		html_print_image("images/list.png", true,
			array ('title' => __('List of networkmaps'))) .
		'</a>');

if ($create_networkmap) {
	ui_print_page_header(__('Create networkmap'), "images/bricks.png",
		false, "network_list", false, $buttons);
}
else {
	$buttons['edit'] = array('active' => true,
		'text' => '<a href="index.php?sec=maps&sec2=operation/maps/networkmap_editor&edit_networkmap=1&id_networkmap=' . $id . '">' . 
			html_print_image("images/cog.png", true,
				array ('title' => __('Edit networkmap'))) .
			'</a>');
	$buttons['networkmap'] = array('active' => false,
		'text' => '<a href="index.php?sec=network&sec2=operation/maps/networkmap&id=' . $id . '">' . 
			html_print_image("images/op_network.png", true,
				array ('title' => __('View networkmap'))) .
			'</a>');
	
	ui_print_page_header(__('Update networkmap'), "images/bricks.png",
		false, "network_list", false, $buttons);
}

if ($not_found) {
	ui_print_error_message(__('Not found networkmap'));
}
else {
	$table = null;
	$table->id = 'form_editor';
	
	$table->width = '98%';
	$table->class = "databox_color";
	
	$table->head = array();
	
	$table->size = array();
	$table->size[0] = '30%';
	
	$table->style = array ();
	$table->style[0] = 'font-weight: bold; width: 150px;';
	$table->data = array();
	
	$table->data['name'][0] = __('Name');
	$table->data['name'][1] = html_print_input_text ('name', $name, '',
		30, 100, true);
	
	$table->data['group'][0] = __('Group');
	$table->data['group'][1] = html_print_select_groups(false, "AR", true,
		'id_group', $id_group, '', '', 0, true);
	
	$table->data['description'][0] = __('Description');
	$table->data['description'][1] = html_print_textarea("description",
		2, 65, $description, '', true);
	
	$subtypes = array(
		MAP_SUBTYPE_TOPOLOGY => 'Topology',
		MAP_SUBTYPE_POLICIES => 'Policies',
		MAP_SUBTYPE_GROUPS => 'Groups',
		MAP_SUBTYPE_RADIAL_DYNAMIC => 'Radial Dynamic'
		);
	
	$table->data['subtype'][0] = __('Subtype');
	$table->data['subtype'][1] = html_print_select($subtypes, 'subtype',
		$subtype, '', '', 'Topology', true, false, true, '',
		$disabled_select);
	
	$table->data['source'][0] = __('Source type');
	$table->data['source'][1] =
		html_print_radio_button('source', MAP_SOURCE_GROUP, __('Group'), $source, true) .
		html_print_radio_button('source', MAP_SOURCE_IP_MASK, __('CIDR IP mask'), $source, true);
	
	$table->data['source_group'][0] = __('Source');
	$table->data['source_group'][1] = html_print_select_groups(
		false, "AR", true, 'source_group', $source_data, '', '', 0, true);
	
	$table->data['source_ip_mask'][0] = __('Source');
	$table->data['source_ip_mask'][1] = html_print_input_text ('source_ip_mask',
		$source_data, '', 30, 100,true);
	
	$generation_methods = array(
		MAP_GENERATION_RADIAL => 'Radial',
		MAP_GENERATION_PLANO => 'Flat',
		MAP_GENERATION_CIRCULAR => 'Circular',
		MAP_GENERATION_SPRING1 => 'Spring1',
		MAP_GENERATION_SPRING2 => 'Spring2'
		);
	
	$table->data['method_generation'][0] = __('Method generation networkmap');
	$table->data['method_generation'][1] = html_print_select($generation_methods, 'generation_method', $generation_method,
		'', '', 'twopi', true, false, true, '',
		$disabled_select);
	
	
	
	
	
	$table->data[6][0] = __('Refresh time');
	$table->data[6][1] = html_print_input_text ('source_period', $source_period, '', 8,
		20,true);
	
	$table->data[7][0] = __('Show groups filter');
	$table->data[7][1] = html_print_checkbox('show_groups_filter', '1', $show_groups_filter, true);
	
	$table->data[8][0] = __('Show module plugins');
	$table->data[8][1] = html_print_checkbox('show_module_plugins', '1', $show_module_plugins, true);
	
	$table->data[9][0] = __('Show snmp modules');
	$table->data[9][1] = html_print_checkbox('show_snmp_modules', '1', $show_snmp_modules, true);
	
	$table->data[10][0] = __('Show modules');
	$table->data[10][1] = html_print_checkbox('show_modules', '1', $show_modules, true);
	
	$table->data[11][0] = __('Show policy modules');
	$table->data[11][1] = html_print_checkbox('show_policy_modules', '1', $show_policy_modules, true);
	
	$table->data[12][0] = __('Show pandora nodes');
	$table->data[12][1] = html_print_checkbox('show_pandora_nodes', '1', $show_pandora_nodes, true);
	
	$table->data[13][0] = __('Show module group');
	$table->data[13][1] = html_print_checkbox('show_module_group', '1', $show_module_group, true);
	
	$table->data[14][0] = __('Filter by tags');
	$table->data[14][1] = html_print_select (tags_get_user_tags(), "id_tag", $id_tag, '', __('All'), 0, true, false, true, '', false, '');
	
	$table->data[15][0] = __('Filter by text');
	$table->data[15][1] = html_print_input_text ('text', $text, '', 30,
		100,true);
	
	echo '<form method="post" action="index.php?sec=maps&amp;sec2=operation/maps/networkmap_list">';
	
	html_print_table($table);
	
	echo "<div style='width: " . $table->width . "; text-align: right;'>";
	if ($create_networkmap) {
		html_print_input_hidden ('save_networkmap', 1);
		html_print_submit_button (__('Save networkmap'), 'crt', false,
			'class="sub next"');
	}
	else if ($edit_networkmap) {
		html_print_input_hidden ('id_networkmap', $id);
		html_print_input_hidden ('update_networkmap', 1);
		html_print_submit_button (__('Update networkmap'), 'crt', false,
			'class="sub next"');
	}
	echo "</div>";
	echo "</form>";
}
?>
<script type="text/javascript">
	var MAP_SOURCE_GROUP = <?php echo MAP_SOURCE_GROUP; ?>;
	var MAP_SOURCE_IP_MASK = <?php echo MAP_SOURCE_IP_MASK; ?>;
	
	$(function() {
		change_source();
		
		$("input[name='source']").on("change",
			function(event) {
				change_source();
			}
		);
	});
	
	function change_source() {
		var checked_source_group =
			$("input[name='source'][value='" + MAP_SOURCE_GROUP + "']")
				.is(":checked");
		var checked_source_ip_mask =
			$("input[name='source'][value='" + MAP_SOURCE_IP_MASK + "']")
				.is(":checked");
		
		$("#form_editor-source_group").hide();
		$("#form_editor-source_ip_mask").hide();
		if (checked_source_group) {
			$("#form_editor-source_group").show();
		}
		else if (checked_source_ip_mask) {
			$("#form_editor-source_ip_mask").show();
		}
	}
</script>
