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


// Check ACL
if (! check_acl ($config['id_user'], 0, "LW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access SNMP Filter Management");
	require ("general/noaccess.php");
	return;
}

// Global variables
$edit_filter = (int) get_parameter ('edit_filter', -2);
$update_filter = (int) get_parameter ('update_filter', -2);
$delete_filter = (int) get_parameter ('delete_filter', -1);
$description = (string) get_parameter ('description', '');
$filter = (string) get_parameter ('filter', '');
$index_post = (int) get_parameter('index_post', 0);

// Create/update header
if ($edit_filter > -2) {
	if ($edit_filter > -1) {
		ui_print_page_header (__('SNMP Console')." &raquo; ".__('Update filter'), "images/op_snmp.png", false, "", false);
	}
	else {
		ui_print_page_header (__('SNMP Console')." &raquo; ".__('Create filter'), "images/op_snmp.png", false, "", false);
	}
}
else {// Overview header
	ui_print_page_header (__('SNMP Console')." &raquo; ".__('Filter overview'), "images/op_snmp.png", false, "", false);
}

// Create/update filter
if ($update_filter > -2) {
	if ($update_filter > -1) {
		$new_unified_id = (db_get_value_sql("SELECT unified_filters_id FROM tsnmp_filter WHERE id_snmp_filter = " . $update_filter));
		$elements = get_parameter('elements', array());
		
		if ($index_post == 1) {
			$filter = get_parameter('filter_' . $update_filter);
			$values = array('description' => $description, 'filter' => $filter, 'unified_filters_id' => $new_unified_id);
			$result = db_process_sql_update('tsnmp_filter', $values, array('id_snmp_filter' => $update_filter));
		}
		else {
			$elements = explode(",", $elements);
			foreach ($elements as $e) {
				$filter = get_parameter('filter_' . $e);
				$values = array('description' => $description, 'filter' => $filter, 'unified_filters_id' => $new_unified_id);
				$result = db_process_sql_update('tsnmp_filter', $values, array('id_snmp_filter' => $e));
			}
			if (count($elements) == 1) {
				$new_unified_id = (db_get_value_sql("SELECT MAX(unified_filters_id) FROM tsnmp_filter")) + 1;

				$filter = get_parameter('filter_' . $elements[0]);
				$values = array('description' => $description, 'filter' => $filter, 'unified_filters_id' => $new_unified_id);
				$result = db_process_sql_update('tsnmp_filter', $values, array('id_snmp_filter' => $elements[0]));
			}
			for ($i = 1; $i < $index_post; $i++) {
				$filter = get_parameter('filter_' . $i);
				$values = array(
					'description' => $description,
					'filter' => $filter,
					'unified_filters_id' => $new_unified_id);
				$result = db_process_sql_insert('tsnmp_filter', $values);
			}
		}
		if ($result === false) {
			ui_print_error_message (__('There was a problem updating the filter'));
		}
		else {
			ui_print_success_message (__('Successfully updated'));
		}
	}
	else {
		$new_unified_id = (db_get_value_sql("SELECT MAX(unified_filters_id) FROM tsnmp_filter")) + 1;

		if ($index_post == 1) {
			$filter = get_parameter('filter_0');
			$values = array(
					'description' => $description,
					'filter' => $filter,
					'unified_filters_id' => 0);
				$result = db_process_sql_insert('tsnmp_filter', $values);
		}
		else {
			for ($i = 0; $i < $index_post; $i++) {
				$filter = get_parameter('filter_' . $i);
				$values = array(
					'description' => $description,
					'filter' => $filter,
					'unified_filters_id' => $new_unified_id);
				$result = db_process_sql_insert('tsnmp_filter', $values);
			}
		}
		
		if ($result === false) {
			ui_print_error_message (__('There was a problem creating the filter'));
		}
		else {
			ui_print_success_message (__('Successfully created'));
		}
	}
}
else if ($delete_filter > -1) { // Delete
	$filters_to_upd = db_get_all_rows_sql("SELECT * FROM tsnmp_filter WHERE unified_filters_id = (SELECT unified_filters_id FROM tsnmp_filter WHERE id_snmp_filter = " . $delete_filter . ")");
	if (count($filters_to_upd) == 2) {
		foreach ($filters_to_upd as $fil) {
			if ($fil['id_snmp_filter'] != $delete_filter) {
				$values = array('description' => $fil['description'], 'filter' => $fil['filter'], 'unified_filters_id' => 0);
				db_process_sql_update('tsnmp_filter', $values, array('id_snmp_filter' => $fil['id_snmp_filter']));
			}
		}
		
	}
	$result = db_process_sql_delete('tsnmp_filter', array('id_snmp_filter' => $delete_filter));
	if ($result === false) {
		ui_print_error_message (__('There was a problem deleting the filter'));
	}
	else {
		ui_print_success_message (__('Successfully deleted'));
	}
}

// Read filter data from the database
if ($edit_filter > -1) {
	$filter = db_get_row ('tsnmp_filter', 'id_snmp_filter', $edit_filter);
	if ($filter !== false) {
		$description = $filter['description'];
		$filter = $filter['filter'];
	}
}

// Create/update form
if ($edit_filter > -2) {
	$index = $index_post;
	$table->data = array ();
	$table->id = 'filter_table';
	$table->width = '100%';
	$table->class = 'databox filters';
	$table->data[0][0] = __('Description');
	$table->data[0][1] = html_print_input_text ('description', $description, '', 60, 100, true);
	$table->data[1][0] = __('Filter');
	if ($edit_filter > -1) {
		$filters = db_get_all_rows_sql("SELECT * FROM tsnmp_filter WHERE unified_filters_id = (SELECT unified_filters_id FROM tsnmp_filter WHERE id_snmp_filter = " . $edit_filter . ")");
		$j = 1;
		foreach ($filters as $f) {
			if ($j != 1) {
				$table->data[$j][0] = "";
			}
			$table->data[$j][1] = html_print_input_text ('filter_' . $f['id_snmp_filter'], $f['filter'], '', 60, 100, true);
			if ($j == 1) {
				$table->data[$j][1] .= ui_print_help_tip (__("This field contains a substring, could be part of a IP address, a numeric OID, or a plain substring") . SEPARATOR_COLUMN, true);
			}
			$j++;
		}
	}
	else {
		$table->data[1][1] = html_print_input_text ('filter_' . $index, $filter, '', 60, 100, true);
		$table->data[1][1] .= ui_print_help_tip (__("This field contains a substring, could be part of a IP address, a numeric OID, or a plain substring") . SEPARATOR_COLUMN, true);
	}
	$index++;
	echo '<form action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_filters" method="post">';
	html_print_input_hidden ('update_filter', $edit_filter);
	html_print_input_hidden ('index_post', $index);
	if ($edit_filter > -1) {
		$filters_to_post = array();
		foreach ($filters as $fil) {
			$filters_to_post[] = $fil['id_snmp_filter'];
		}
		html_print_input_hidden ('elements', implode(",", $filters_to_post));
	}
	html_print_table ($table);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	html_print_image('images/add.png', false, array('id' => 'add_filter', 'alt' => __('Click to add new filter'), 'title' => __('Click to add new filter'), 'style' => 'float:left;'));
	if ($edit_filter > -1) {
		html_print_submit_button (__('Update'), 'submit_button', false, 'class="sub upd"');
	}
	else {
		html_print_submit_button (__('Create'), 'submit_button', false, 'class="sub upd"');
	}
	echo '</div>';
	echo '</form>';
// Overview
}
else {
	$result = db_get_all_rows_sql("SELECT * FROM tsnmp_filter ORDER BY unified_filters_id ASC");
	if ($result === false) {
		$result = array ();
		require_once ($config['homedir'] . "/general/firts_task/snmp_filters.php");
		return;
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->size = array ();
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "100%";
	$table->class= "databox data";
	$table->align = array ();
	
	$table->head[0] = __('Description');
	$table->head[1] = __('Filter');
	$table->head[2] = __('Function');
	$table->head[3] = __('Action');
	$table->size[3] = "50px";
	$table->align[3] = 'center';
	
	foreach ($result as $row) {
		$data = array ();
		$data[0] = '<a href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_filters&edit_filter='.$row['id_snmp_filter'].'">' . $row['description'] . '</a>';
		$data[1] = $row['filter'];
		if ($row['unified_filters_id'] == 0) {
			$data[2] = "OR";
		}
		else {
			$data[2] = "AND (" . $row['unified_filters_id'] . ")";
		}
		$data[3] = '<a href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_filters&edit_filter='.$row['id_snmp_filter'].'">' .
			html_print_image("images/config.png", true, array("border" => '0', "alt" => __('Update'))) . '</a>' .
			'&nbsp;&nbsp;<a onclick="if (confirm(\'' . __('Are you sure?') . '\')) return true; else return false;" href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_filters&delete_filter='.$row['id_snmp_filter'].'">' .
			html_print_image("images/cross.png", true, array("border" => '0', "alt" => __('Delete'))) . '</a>';
		array_push ($table->data, $data);
	}
	
	if (!empty ($table->data)) {
		html_print_table ($table);
	}
	
	unset ($table);
	
	echo '<div style="text-align:right; width:100%">';
	echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_filters&edit_filter=-1">';
	html_print_submit_button (__('Create'), 'submit_button', false, 'class="sub next"');
	echo '</form></div>';
}
?>

<script type="text/javascript">
	var id = "<?php echo $index; ?>";

	$(document).ready (function () {
		$('#add_filter').click(function(e) {
			$('#filter_table').append('<tr id="filter_table-' + id + '" style="" class="datos"><td id="filter_table-' + id + '-0" style="" class="datos "></td><td id="filter_table-' + id + '-1" style="" class="datos "><input type="text" name="filter_' + id + '" value="" id="text-filter_' + id + '" size="60" maxlength="100"></td></tr>');
			
			id++;

			$('#hidden-index_post').val(id);
		});
	});
</script>
