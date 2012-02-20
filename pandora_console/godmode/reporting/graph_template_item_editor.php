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

global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

$id_template = get_parameter('id',0);
$delete = get_parameter('delete',0);

$create = get_parameter('add',0);

$buttons['template_list'] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_list">'
		. html_print_image ("images/god6.png", true, array ("title" => __('Template list')))
		. '</a>';
// Header
ui_print_page_header (__('Graph template editor'), "", false, "", true, $buttons);

if ($create) {
	if (!$id_template) {
		ui_print_error_message ('Not created. Blank template.');
	} else {
		$agent = get_parameter('agent', '');
		$module = get_parameter('module', '');
		$match = get_parameter('match', 0);
		$weight = get_parameter('weight', 1);
		
		if ($module != '') { 
			$values = array (
					'id_template' => $id_template,
					'agent' => $agent,
					'module' => $module,
					'exact_match' => $match,
					'weight' => $weight
					);
			$id_gs_template = db_process_sql_insert('tgraph_source_template', $values);	
			if ($id_gs_template === false) {
				ui_print_error_message ('Error creating template');
			} else {
				ui_print_success_message ('Template created successfully');
			}
		} else {
			ui_print_error_message ('Not created. Blank module');
		}
	}
}

if ($delete) {
	$id_gs_template = get_parameter('id_gs_template');
	$id_template = get_parameter('id_template');

	$result = db_process_sql_delete ('tgraph_source_template',
		array ('id_gs_template' => $id_gs_template));

	if ($result !== false) {
		$result = true;
	} else {
		$result = false;
	}
		
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}

if ($id_template) {
	$sql = "SELECT * FROM tgraph_source_template where id_template=$id_template";
	$templates = db_get_all_rows_sql($sql);
	if ($templates != false) {
		$table_aux->width = '90%';

		$table_aux->size = array();
		$table_aux->size[0] = '40%';
		$table_aux->size[1] = '30%';
		$table_aux->size[2] = '20%';
		$table_aux->size[3] = '30px';
	
		$table_aux->head[0] = __('Agent');
		$table_aux->align[0] = 'center';
		$table_aux->head[1] = __('Module');
		$table_aux->align[1] = 'center';
		$table_aux->head[2] = __('Weight');
		$table_aux->align[2] = 'center';
		$table_aux->head[3] = __('Delete');
		$table_aux->align[3] = 'center';
	
		$table_aux->data = array();
		
		foreach ($templates as $template) {
			$data = array();
			
			$data[0] = $template['agent'];
			$data[1] = $template['module'];
			$data[2] = $template['weight'];
			$data[3] = "<a onclick='if(confirm(\"" . __('Are you sure?') . "\")) return true; else return false;' 
				href='index.php?sec=greporting&sec2=godmode/reporting/graph_template_item_editor&delete=1&id_gs_template=".$template['id_gs_template']."&id_template=".$template['id_template']."&offset=0'>" . 
				html_print_image('images/cross.png', true, array('title' => __('Delete'))) . "</a>";
			
			array_push ($table_aux->data, $data);
		}
			
		html_print_table($table_aux);
	}
}

//Configuration form
$table->width = '90%';

$table->size = array();
$table->size[0] = '40%';
$table->size[1] = '40%';

$table->data = array();

$table->data[0][0] = '<b>'.__('Agent').'</b>';
$table->data[1][0] = html_print_input_text('agent', '', '', 30, 255, true);
$table->data[0][1] = '<b>'.__('Module').'</b>';
$table->data[1][1] = html_print_input_text('module', '', '', 30, 255, true);
$table->data[2][0] = '<b>'.__('Weight').'</b>';
$table->data[2][0] .= '&nbsp;&nbsp;&nbsp;&nbsp;'.html_print_input_text('weight', 2, '', 3, 5, true);
$table->data[2][1] = __('Exact match');
$table->data[2][1] .= html_print_checkbox('match', 1, 0, true);

echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/graph_template_item_editor&add=1&id='.$id_template.'">';
html_print_table($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';

html_print_submit_button (__('Add'), 'crt', false, 'class="sub add"');

echo '</div>';
echo '</form>';
?>
