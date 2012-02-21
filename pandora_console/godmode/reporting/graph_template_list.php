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

// Load global variables
global $config;

require_once ('include/functions_reporting.php');

// Check user credentials
check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Inventory Module Management");
	require ("general/noaccess.php");
	return;
}

$buttons['graph_list'] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graphs">'
		. html_print_image ("images/god6.png", true, array ("title" => __('Graph list')))
		. '</a>';

$buttons['wizard'] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_wizard">'
		. html_print_image ("images/wand.png", true, array ("title" => __('Wizard')))
		. '</a>';
		
// Header
ui_print_page_header (__('Graph template management'), "", false, "", true, $buttons);

$delete = get_parameter ('delete_template', 0);
$id_template = get_parameter('id', 0);
$multiple_delete = (bool)get_parameter('multiple_delete', 0);

if ($delete) {
	$result = db_process_sql_delete ('tgraph_template',
		array ('id_graph_template' => $id_template));
		
	if ($result !== false)
		$result = true;
	else
		$result = false;
		
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}

if ($multiple_delete) {
	$ids = (array)get_parameter('delete_multiple', array());
	
	db_process_sql_begin();
	
	foreach ($ids as $id) {
		$result = db_process_sql_delete ('tgraph_template',
			array ('id_graph_template' => $id));
	
		if ($result === false) {
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
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}


$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$return_all_group = true;
else
	$return_all_group = false;
	
$templates = reporting_template_graphs_get_user ($config['id_user'], false, $return_all_group, "IW");

if (! empty ($templates)) {
	$table->width = '98%';
	$tale->class = 'databox_frame';
	$table->align = array ();
	$table->align[3] = 'center';
	$table->head = array ();
	$table->head[0] = __('Template name');
	$table->head[1] = __('Description');
	$table->head[3] = __('Group');
	$table->size[3] = '50px';
	if (check_acl ($config['id_user'], 0, "AW")) {
		$table->align[4] = 'center';
		$table->head[4] = __('Action'). html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
		$table->size[4] = '50px';
	}
	$table->data = array ();
	
	foreach ($templates as $template) {
		$data = array ();

		$data[0] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_editor&id='.
			$template['id_graph_template'].'">'.$template['name'].'</a>';
		$data[1] = $template["description"];
		
		$data[3] = ui_print_group_icon($template['id_group'],true);
		
		if (check_acl ($config['id_user'], 0, "AW")) {
			$data[4] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_list&delete_template=1&id='
				.$template['id_graph_template'].'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
					return false;">' . html_print_image("images/cross.png", true) . '</a>'. 
				html_print_checkbox_extended ('delete_multiple[]', $template['id_graph_template'], false, false, '', 'class="check_delete"', true);
		}
		
		array_push ($table->data, $data);
	}
	
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_template_list'>";
	html_print_input_hidden('multiple_delete', 1);
	html_print_table ($table);
	echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table->width . "'>";
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
	echo "</form>";
}
else {
	echo "<div class='nf'>".__('There are no defined graph templates')."</div>";
}

echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/graph_template_editor">';
echo '<div class="action-buttons" style="width: 98%;">';
html_print_submit_button (__('Create template'), 'create', false, 'class="sub next"');
echo "</div>";
echo "</form>";

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
