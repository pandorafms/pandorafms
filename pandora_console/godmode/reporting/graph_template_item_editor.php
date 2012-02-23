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
$multiple_delete = (bool)get_parameter('multiple_delete', 0);
$change_weight = (bool)get_parameter('change_weight', 0);
$create = get_parameter('add',0);

$buttons['graph_list'] = array('active' => false,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/graphs">' .
		html_print_image("images/god6.png", true, array ("title" => __('Graph list'))) .'</a>');

$buttons['wizard'] = array('active' => false,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_wizard">' .
		html_print_image("images/wand.png", true, array ("title" => __('Wizard'))) .'</a>');

$buttons['template'] = array('active' => false,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_list">' .
		html_print_image("images/paste_plain.png", true, array ("title" => __('Templates'))) .'</a>');
		
$buttons['template_editor'] = array('active' => true,
		'text' => '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_editor&id='.$id_template.'">' .
		html_print_image("images/config.png", true, array ("title" => __('Template editor'))) .'</a>');
	
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

if ($multiple_delete) {
	$ids = (array)get_parameter('delete_multiple', array());
	
	db_process_sql_begin();
	
	foreach ($ids as $id) {
		$result = db_process_sql_delete ('tgraph_source_template',
			array ('id_gs_template' => $id));
	
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

if ($change_weight) {
	$new_weight = get_parameter('new_weight');
	$id_gs_template = get_parameter ('id_gs_template');
	$value = array (
		'weight' => $new_weight
		);
	$result = db_process_sql_update('tgraph_source_template', $value, array('id_gs_template'=>$id_gs_template)); 
}
			
if ($id_template) {
	$sql = "SELECT * FROM tgraph_source_template where id_template=$id_template";
	$templates = db_get_all_rows_sql($sql);
	if ($templates != false) {
		$table_aux->width = '98%';

		$table_aux->size = array();
		//$table_aux->size[0] = '40%';
		$table_aux->size[1] = '40%';
		$table_aux->size[2] = '30%';
		$table_aux->size[3] = '20%';
		$table_aux->size[4] = '60px';
	
		//$table_aux->head[0] = __('Agent');
		//$table_aux->align[0] = 'center';
		$table_aux->head[1] = __('Module');
		$table_aux->align[1] = 'center';
		$table_aux->head[2] = __('Weight');
		$table_aux->align[2] = 'center';
		$table_aux->head[3] = __('Exact match');
		$table_aux->align[3] = 'center';
		$table_aux->head[4] = __('Action') . html_print_checkbox('all_delete', 0, false, true, false, 'check_all_checkboxes();');
		$table_aux->align[4] = 'center';
	
		$table_aux->data = array();
		
		foreach ($templates as $template) {
			$data = array();
			
			//$data[0] = $template['agent'];
			$data[1] = $template['module'];
			
			$dec_weight = $template['weight']-0.125;
			$inc_weight = $template['weight']+0.125;
			$data[2] = "<a href='index.php?sec=greporting&sec2=godmode/reporting/graph_template_item_editor&id=".$template['id_template']."&change_weight=1&new_weight=".$dec_weight."&id_gs_template=". $template['id_gs_template']. "'>".
				html_print_image('images/down.png', true, array ('title' => __('Decrease Weight')))."</a>".
				$template['weight']. 
				"<a href='index.php?sec=greporting&sec2=godmode/reporting/graph_template_item_editor&id=".$template['id_template']."&change_weight=1&new_weight=".$inc_weight."&id_gs_template=". $template['id_gs_template']. "'>".
				html_print_image('images/up.png', true, array ('title' => __('Increase Weight')))."</a>";
				
			if ($template['exact_match'])
				$data[3] = __('Yes');
			else 
				$data[3] = __('No');
			
			$data[4] = "<a onclick='if(confirm(\"" . __('Are you sure?') . "\")) return true; else return false;' 
				href='index.php?sec=greporting&sec2=godmode/reporting/graph_template_item_editor&delete=1&id_gs_template=".$template['id_gs_template']."&id_template=".$template['id_template']."&offset=0'>" . 
				html_print_image('images/cross.png', true, array('title' => __('Delete'))) . "</a>" .
				html_print_checkbox_extended ('delete_multiple[]', $template['id_gs_template'], false, false, '', 'class="check_delete"', true);
					
			array_push ($table_aux->data, $data);
		}
		
		if(isset($data)) {
			echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_template_item_editor'>";
			html_print_input_hidden('multiple_delete', 1);
			html_print_table ($table_aux);
			echo "<div style='padding-bottom: 20px; text-align: right; width:" . $table_aux->width . "'>";
			html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
			echo "</div>";
			echo "</form>";
		}
			
	}
}

//Configuration form
$table->width = '98%';

$table->data = array();

//$table->data[0][0] = '<b>'.__('Agent').'</b>';
//$table->data[1][0] = html_print_input_text('agent', '', '', 30, 255, true);
$table->data[0][0] = '<b>'.__('Module').'</b>'."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
$table->data[0][0] .= "&nbsp;&nbsp;&nbsp;&nbsp;".html_print_input_text('module', '', '', 30, 255, true);
$table->data[1][0] = '<b>'.__('Weight').'</b>'."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
$table->data[1][0] .= '&nbsp;&nbsp;&nbsp;&nbsp;'.html_print_input_text('weight', 2, '', 3, 5, true);
$table->data[2][0] = __('Exact match')."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
$table->data[2][0] .= html_print_checkbox('match', 1, 0, true);

echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/graph_template_item_editor&add=1&id='.$id_template.'">';
html_print_table($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';

html_print_submit_button (__('Add'), 'crt', false, 'class="sub add"');

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
