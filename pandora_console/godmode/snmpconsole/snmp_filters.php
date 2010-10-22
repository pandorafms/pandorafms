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
if (! give_acl ($config['id_user'], 0, "LW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
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

// Create/update header
if ($edit_filter > -2) {
	if ($edit_filter > -1) {
		print_page_header (__('SNMP Console')." &raquo; ".__('Update filter'), "images/computer_error.png", false, "", true);
	} else {
		print_page_header (__('SNMP Console')." &raquo; ".__('Create filter'), "images/computer_error.png", false, "", true);
	}
// Overview header
} else {
	print_page_header (__('SNMP Console')." &raquo; ".__('Filter overview'), "images/computer_error.png", false, "", true);
}

// Create/update filter
if ($update_filter > -2) {
	if ($update_filter > -1) {
		$sql = sprintf ("UPDATE tsnmp_filter SET description = '%s', filter = '%s' WHERE id_snmp_filter = %d", $description, $filter, $update_filter);
		if (process_sql ($sql) === false) {
			print_error_message (__('There was a problem updating the filter'));
		} else {
			print_success_message (__('Successfully updated'));
		}
	} else {
		$sql = sprintf ("INSERT INTO tsnmp_filter (description, filter) VALUES ('%s', '%s')", $description, $filter);		
		if (process_sql ($sql) === false) {
			print_error_message (__('There was a problem creating the filter'));
		} else {
			print_success_message (__('Successfully created'));
		}
	}
// Delete
} else if ($delete_filter > -1) {
	$sql = sprintf ("DELETE FROM tsnmp_filter WHERE id_snmp_filter = %d", $delete_filter);
	if (process_sql ($sql) === false) {
		print_error_message (__('There was a problem deleting the filter'));
	} else {
		print_success_message (__('Successfully deleted'));
	}
}

// Read filter data from the database
if ($edit_filter > -1) {
	$filter = get_db_row ('tsnmp_filter', 'id_snmp_filter', $edit_filter);
	if ($filter !== false) {
		$description = $filter['description'];
		$filter = $filter['filter'];
	}
}

// Create/update form
if ($edit_filter > -2) {
	$table->data = array ();
	$table->width = '90%';
	$table->data[0][0] = __('Description');
	$table->data[0][1] = print_input_text ('description', $description, '', 60, 100, true);
	$table->data[1][0] = __('Filter');
	$table->data[1][1] = print_input_text ('filter', $filter, '', 60, 100, true);

	echo '<form action="index.php?sec=gsnmpconsole&sec2=godmode/snmpconsole/snmp_filters" method="post">';
	print_input_hidden ('update_filter', $edit_filter);
	print_table ($table);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	if ($edit_filter > -1) {
		print_submit_button (__('Update'), 'submit_button', false, 'class="sub upd"');
	} else {
		print_submit_button (__('Create'), 'submit_button', false, 'class="sub upd"');
	}
	echo '</div>';
	echo '</form>';
// Overview
} else {
	$result = get_db_all_rows_in_table ("tsnmp_filter");
	if ($result === false) {
		$result = array ();
		echo "<div class='nf'>".__('There are no SNMP filters')."</div>";
	}
	
	$table->data = array ();
	$table->head = array ();
	$table->size = array ();
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "750px";
	$table->class= "databox";
	$table->align = array ();

	$table->head[0] = __('Description');	
	$table->head[1] = __('Filter');
	$table->head[2] = __('Action');
	$table->size[2] = "50px";
	$table->align[2] = 'center';

	foreach ($result as $row) {
		$data = array ();
		$data[0] = $row['description'];
		$data[1] = $row['filter'];
		$data[2] = '<a href="index.php?sec=gsnmpconsole&sec2=godmode/snmpconsole/snmp_filters&delete_filter='.$row['id_snmp_filter'].'">
				<img src="images/cross.png" border="0" alt="'.__('Delete').'"></a>&nbsp;
				<a href="index.php?sec=gsnmpconsole&sec2=godmode/snmpconsole/snmp_filters&edit_filter='.$row['id_snmp_filter'].'">
				<img src="images/config.png" border="0" alt="'.__('Update').'"></a>';
		array_push ($table->data, $data);
	}

	if (!empty ($table->data)) {
		print_table ($table);
	}
	
	unset ($table);	
	
	echo '<div style="text-align:right; width:740px">';
	echo '<form name="agente" method="post" action="index.php?sec=gsnmpconsole&sec2=godmode/snmpconsole/snmp_filters&edit_filter=-1">';
	print_submit_button (__('Create'), 'submit_button', false, 'class="sub next"');
	echo '</form></div>';
}

?>
