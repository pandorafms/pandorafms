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
	$id_group = GROUP_ALL;
	$type = MAP_TYPE_NETWORKMAP;
	$subtype = MAP_SUBTYPE_GROUPS;
	$name = "";
	$description = "";
	$width = NETWORKMAP_DEFAULT_WIDTH;
	$height = NETWORKMAP_DEFAULT_HEIGHT;
	$source_period = MAP_REFRESH_TIME;
	$source = MAP_SOURCE_GROUP;
	$source_data = '';
	$generation_method = MAP_GENERATION_CIRCULAR;
	
	// Filters
	$id_tag = 0;
	$text = "";
	$show_pandora_nodes = false;
	$show_agents = false;
	$show_modules = false;
	$module_group = 0;
	$show_module_group = false;
	$only_snmp_modules = false;
	$only_modules_with_alerts = false;
	$only_policy_modules = false;
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
		$width = $values['width'];
		$height = $values['height'];
		$source_period = $values['source_period'];
		$source = $values['source'];
		$source_data = $values['source_data'];
		$generation_method = $values['generation_method'];
		
		$filter = json_decode($values['filter'], true);
		$id_tag = $filter['id_tag'];
		$text = io_safe_output($filter['text']);
		$show_pandora_nodes = $filter['show_pandora_nodes'];
		$show_agents = $filter['show_agents'];
		$show_modules = $filter['show_modules'];
		$module_group = $filter['module_group'];
		$show_module_group = $filter['show_module_group'];
		$only_snmp_modules = $filter['only_snmp_modules'];
		$only_modules_with_alerts = $filter['only_modules_with_alerts'];
		$only_policy_modules = $filter['only_policy_modules'];
	}
}

//+++++++++++++++TABLE TO CREATE/EDIT NETWORKMAP++++++++++++++++++++++
if (is_metaconsole()) {
	$buttons['list'] = array('active' => true,
		'text' => '<a href="index.php?sec=screen&sec2=screens/screens&action=networkmap">' .
			html_print_image("images/list.png", true,
				array ('title' => __('List of networkmaps'))) .
			'</a>');
	
	if ($create_networkmap) {
		$title_header = __('Create networkmap');
	}
	else {
		$title_header = __('Update networkmap');
		
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
	}
	
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
		if (enterprise_installed()) {
			$buttons['deleted'] = array('active' => false,
				'text' => '<a href="index.php?sec=maps&sec2=enterprise/operation/maps/networkmap_list_deleted&&id_networkmap=' . $id . '">' . 
					html_print_image("images/list.png", true,
						array ('title' => __('Deleted list'))) .
					'</a>');
		}
		$buttons['networkmap'] = array('active' => false,
			'text' => '<a href="index.php?sec=network&sec2=operation/maps/networkmap&id=' . $id . '">' . 
				html_print_image("images/op_network.png", true,
					array ('title' => __('View networkmap'))) .
				'</a>');
		
		ui_print_page_header(__('Update networkmap'), "images/bricks.png",
			false, "network_list", false, $buttons);
	}
}

if ($not_found) {
	ui_print_error_message(__('Not found networkmap'));
}
else {
	if ($edit_networkmap && enterprise_installed()) {
		echo '<form method="post" onsubmit="javascript: return alert_refresh_all_networkmap();" action="index.php?sec=maps&amp;sec2=operation/maps/networkmap_list">';
	}
	else {
		echo '<form method="post" action="index.php?sec=maps&amp;sec2=operation/maps/networkmap_list">';
	}
	
	
	
	$table = new stdClass();
	$table->id = 'form_editor';
	$table->width = '100%';
	$table->class = 'databox filters';
	
	$table->head = array();
	
	if (is_metaconsole()) {
		$table->head[0] = __("Create visual console");
		$table->head_colspan[0] = 5;
		$table->headstyle[0] = 'text-align: center';
		$table->align[0] = 'left';
		$table->align[1] = 'left';
	}
	
	
	$table->size = array();
	$table->size[0] = '30%';
	
	$table->style = array ();
	$table->style[0] = 'font-weight: bold; width: 150px;';
	
	// ----- Main configuration ----------------------------------------
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
	
	$table->data['size'][0] = __('Size of networkmap (Width x Height)');
	$table->data['size'][1] = html_print_input_text ('width', $width, '', 4,
		10,true) . " x ";
	$table->data['size'][1] .= html_print_input_text ('height', $height, '',
		4, 10,true);
	
	$table->data[7][0] = __('Refresh time');
	$table->data[7][1] = html_print_input_text ('source_period',
		$source_period, '', 8, 20, true);
	
	echo '<fieldset><legend>' . __('Main') . '</legend>';
	html_print_table($table);
	echo '</fieldset>';
	
	
	
	// ----- Source configuration --------------------------------------
	$table->data = array();
	
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
	
	echo '<fieldset><legend>' . __('Source') . '</legend>';
	html_print_table($table);
	echo '</fieldset>';
	
	
	
	// ----- Filter configuration --------------------------------------
	$table->data = array();
	
	$table->data["filter_by_tag"][0] = __('Filter by tags');
	$table->data["filter_by_tag"][1] = html_print_select(
		tags_get_user_tags(), "id_tag", $id_tag, '', __('All'), 0, true, false, true, '', false, '');
	
	$table->data["filter_by_text"][0] = __('Filter by text');
	$table->data["filter_by_text"][1] = html_print_input_text ('text',
		$text, '', 30, 100,true);
	
	//~ if (is_metaconsole()) {
		//~ $table->data[13][0] = __('Show pandora nodes');
		//~ $table->data[13][1] = html_print_checkbox('show_pandora_nodes', '1',
			//~ $show_pandora_nodes, true);
	//~ }
	
	$table->data['show_agents'][0] = __('Show agents');
	$table->data['show_agents'][1] = html_print_checkbox(
		'show_agents', '1', $show_agents, true);
	
	$table->data['show_modules'][0] = __('Show modules');
	$table->data['show_modules'][1] = html_print_checkbox(
		'show_modules', '1', $show_modules, true);
	
	$table->data['filter_module_group'][0] = __('Filter by module group');
	$table->data['filter_module_group'][1] = html_print_select_from_sql ('
		SELECT id_mg, name
		FROM tmodule_group', 'module_group', $module_group, '', 'All', 0, true);
	
	$table->data['show_module_group'][0] = __('Show module group');
	$table->data['show_module_group'][1] = html_print_checkbox(
		'show_module_group', '1', $show_module_group, true);
	
	$table->data['only_snmp_modules'][0] = __('Filter only snmp modules');
	$table->data['only_snmp_modules'][1] = html_print_checkbox(
		'only_snmp_modules', '1', $only_snmp_modules, true);
	
	$table->data['only_modules_with_alerts'][0] = __('Filter only modules with alerts');
	$table->data['only_modules_with_alerts'][1] = html_print_checkbox(
		'only_modules_with_alerts', '1', $only_modules_with_alerts, true);
	
	$table->data['only_policy_modules'][0] = __('Filter only policy modules');
	$table->data['only_policy_modules'][1] = html_print_checkbox(
		'only_policy_modules', '1', $only_policy_modules, true);
	
	
	echo '<fieldset><legend>' . __('Filters') . '</legend>';
	html_print_table($table);
	echo '</fieldset>';
	
	
	
	echo "<div style='width: " . $table->width . "; text-align: right;'>";
	if ($create_networkmap) {
		html_print_input_hidden ('save_networkmap', 1);
		html_print_submit_button (__('Save networkmap'), 'crt', false,
			'class="sub next"');
	}
	else if ($edit_networkmap) {
		if (enterprise_installed()) {
			ui_print_help_tip(__("Update the config of enterprise networkmap refresh all data and lost the changes."));
		}
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
	// id in the edition
	var id_networkmap = <?php echo $id; ?>;
	
	// Old values in the edition
	var old_filter = {};
	old_filter['id_tag'] = <?php echo $id_tag; ?>;
	old_filter['text'] = "<?php echo $text; ?>";
	old_filter['show_pandora_nodes'] = <?php echo (int)$show_pandora_nodes; ?>;
	old_filter['show_agents'] = <?php echo (int)$show_agents; ?>;
	old_filter['show_modules'] = <?php echo (int)$show_modules; ?>;
	old_filter['module_group'] = <?php echo (int)$module_group; ?>;
	old_filter['show_module_group'] = <?php echo (int)$show_module_group; ?>;
	old_filter['only_snmp_modules'] = <?php echo (int)$only_snmp_modules; ?>;
	old_filter['only_modules_with_alerts'] = <?php echo (int)$only_modules_with_alerts; ?>;
	old_filter['only_policy_modules'] = <?php echo (int)$only_policy_modules; ?>;
	
	//Constants
	var MAP_SOURCE_GROUP = <?php echo MAP_SOURCE_GROUP; ?>;
	var MAP_SOURCE_IP_MASK = <?php echo MAP_SOURCE_IP_MASK; ?>;
	
	var MAP_SUBTYPE_TOPOLOGY = <?php echo MAP_SUBTYPE_TOPOLOGY; ?>;
	var MAP_SUBTYPE_POLICIES = <?php echo MAP_SUBTYPE_POLICIES; ?>;
	var MAP_SUBTYPE_GROUPS = <?php echo MAP_SUBTYPE_GROUPS; ?>;
	var MAP_SUBTYPE_RADIAL_DYNAMIC = <?php echo MAP_SUBTYPE_RADIAL_DYNAMIC; ?>;
	
	$(function() {
		change_source();
		change_subtype();
		change_show_agents();
		change_show_modules();
		
		$("input[name='source']").on("change",
			function(event) {
				change_source();
			}
		);
		
		$("select[name='subtype']").on("change",
			function(event) {
				change_subtype();
			}
		);
		
		$("input[name='show_agents']").on("change",
			function(event) {
				change_show_agents();
			}
		);
		$("input[name='show_modules']").on("change",
			function(event) {
				change_show_modules();
			}
		);
		
		// Set the form editor initial values when is update
		if (id_networkmap) {
			$("input[name='show_agents']")
				.prop("checked", old_filter['show_agents'])
				.trigger("change");
			
			$("input[name='show_modules']")
				.prop("checked", old_filter['show_modules'])
				.trigger("change");
		}
	});
	
	function change_show_agents() {
		if ($("input[name='show_agents']").prop("checked")) {
			$("input[name='show_modules']").prop("checked", false)
				.trigger("change");
			$("#form_editor-show_modules").show();
		}
		else {
			$("input[name='show_modules']").prop("checked", false)
				.trigger("change");
			$("#form_editor-show_modules").hide();
		}
	}
	
	function change_show_modules() {
		var subtype = parseInt($("select[name='subtype']").val());
		
		if ($("input[name='show_modules']").prop("checked")) {
			$("#form_editor-filter_module_group").show();
			$("#form_editor-show_module_group").show();
			$("#form_editor-only_modules_with_alerts").show();
			
			switch (subtype) {
				case MAP_SUBTYPE_GROUPS:
					$("#form_editor-only_policy_modules").show();
					break;
				case MAP_SUBTYPE_POLICIES:
					break;
				case MAP_SUBTYPE_RADIAL_DYNAMIC:
					break;
				case MAP_SUBTYPE_TOPOLOGY:
					$("#form_editor-only_snmp_modules").show();
					break;
			}
		}
		else {
			$("#form_editor-filter_module_group").hide();
			$("#form_editor-show_module_group").hide();
			$("#form_editor-only_modules_with_alerts").hide();
			
			$("#form_editor-only_policy_modules").hide();
			
			$("#form_editor-only_snmp_modules").hide();
		}
	}
	
	function change_subtype() {
		var subtype = parseInt($("select[name='subtype']").val());
		
		$("#form_editor-show_agents").show();
		$("#form_editor-source").show();
		$("#form_editor-size").show();
		$("#form_editor-7").show();
		switch (subtype) {
			case MAP_SUBTYPE_GROUPS:
				$("#form_editor-only_snmp_modules").hide();
				$("input[name='show_modules']")
					.prop("checked", false)
					.trigger("change");
				break;
			case MAP_SUBTYPE_POLICIES:
				$("#form_editor-only_snmp_modules").hide();
				$("#form_editor-only_policy_modules").hide();
				break;
			case MAP_SUBTYPE_RADIAL_DYNAMIC:
				$("#form_editor-filter_by_tag").hide();
				$("#form_editor-filter_by_text").hide();
				$("#form_editor-show_agents").hide();
				$("#form_editor-show_modules").hide();
				$("#form_editor-show_module_group").hide();
				$("#form_editor-only_snmp_modules").hide();
				$("#form_editor-only_policy_modules").hide();
				$("#form_editor-source").hide();
				
				$("#form_editor-filter_module_group").show();
				$("#form_editor-size").hide();
				$("#form_editor-7").hide();
				break;
			case MAP_SUBTYPE_TOPOLOGY:
				// Forever show agents
				$("input[name='show_agents']")
					.prop("checked", true)
					.trigger("change");
				$("#form_editor-show_agents").hide();
				break;
		}
	}
	
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
	
	function alert_refresh_all_networkmap() {
		return confirm("<?php echo __("Do you want refresh the map and lost the changes?");?>");
	}
</script>
