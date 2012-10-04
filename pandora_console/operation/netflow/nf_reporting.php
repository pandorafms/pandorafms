<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

include_once($config['homedir'] . "/include/functions_ui.php");
include_once($config['homedir'] . "/include/functions_db.php");
include_once($config['homedir'] . "/include/functions_netflow.php");
include_once($config['homedir'] . "/include/functions_html.php");

check_login ();

if (! check_acl ($config["id_user"], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}
$write_permissions = check_acl ($config["id_user"], 0, "AW");
		
//Header
if (! defined ('METACONSOLE')) {
	ui_print_page_header (__('Netflow Reporting'), "images/networkmap/so_cisco_new.png", false, "", false);
} else {
	$nav_bar = array(array('link' => 'index.php?sec=main', 'text' => __('Main')),
		array('link' => 'index.php?sec=netf&sec2=' . $config['homedir'] . '/operation/netflow/nf_reporting', 'text' => __('Netflow reports')));

	ui_meta_print_page_header($nav_bar);
}

$delete_report = get_parameter ('delete_report', false);
$report_id = (int) get_parameter ('report_id', 0);

if ($delete_report && $report_id != 0 && $write_permissions) {
	$result = db_process_sql_delete ('tnetflow_report',
		array ('id_report' => $report_id));
		
	if ($result !== false) $result = true;
	else $result = false;
		
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}

/*
$filter = array ();

$filter['offset'] = (int) get_parameter ('offset');
$filter['limit'] = (int) $config['block_size'];
*/

// Get group list that user has access
$groups_user = users_get_groups ($config['id_user'], "AR", true, true);

$groups_id = array();
foreach($groups_user as $key => $groups){
	$groups_id[] = $groups['id_grupo'];
}
//$sql = "SELECT * FROM tnetflow_report WHERE 'group' IN (\"".implode('","',$groups_id)."\")";
$sql = "SELECT * FROM tnetflow_report WHERE id_group IN (".implode(',',$groups_id).")";
$reports = db_get_all_rows_sql($sql);

if ($reports == false){
	$reports = array();
}

$table->width = '98%';
$table->head = array ();
$table->head[0] = __('Report name');
$table->head[1]= __('Description');
$table->head[2] = __('Group');
if (defined ('METACONSOLE') && $write_permissions) {
	$table->head[3] = '<span title="Operations">' . __('Op.') . '</span>';
}

$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->align = array ();
$table->align[2] = 'center';
$table->size = array ();
$table->size[0] = '30%';
$table->size[2] = '10px';
if (defined ('METACONSOLE') && $write_permissions) {
	$table->size[3] = '85px';
}
$table->data = array ();

$total_reports = db_get_all_rows_filter ('tnetflow_report', false, 'COUNT(*) AS total');
$total_reports = $total_reports[0]['total'];

//ui_pagination ($total_reports, $url);

foreach ($reports as $report) {
	$data = array ();

	$data[0] = '<a href="' . $config['homeurl'] . 'index.php?sec=netf&sec2=' . $config['homedir'] . '/operation/netflow/nf_view&id='.$report['id_report'].'">'.$report['id_name'].'</a>';
	$data[1] = $report['description'];
	
	if (! defined ('METACONSOLE')) {
		$data[2] = ui_print_group_icon($report['id_group'], true);
	} else {
		// No link to the group page in the metaconsole
		$data[2] = ui_print_group_icon($report['id_group'], true, 'groups_small', '', false);
	}

	if (defined ('METACONSOLE') && $write_permissions) {
		$data[3] = '<form method="post" action="' . $config['homeurl'] . 'index.php?sec=netf&sec2=' . $config['homedir'] . '/godmode/netflow/nf_report_form&id=' . $report['id_report'] . '" style="display:inline">';
		$data[3] .= html_print_input_image ('edit', 'images/config.png', 1, '', true, array ('title' => __('Edit')));
		$data[3] .= '</form>';

		$data[3] .= '&nbsp;&nbsp;<form method="post" action="' . $config['homeurl'] . 'index.php?sec=netf&sec2=' . $config['homedir'] . '/godmode/netflow/nf_item_list&id=' . $report['id_report'] . '" style="display:inline">';
		$data[3] .= html_print_input_image ('edit', 'images/god6.png', 1, '', true, array ('title' => __('Items')));
		$data[3] .= '</form>';
					
		$data[3] .= '&nbsp;&nbsp;<form method="post" style="display:inline" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
		$data[3] .= html_print_input_hidden ('report_id', $report['id_report'], true);
		$data[3] .= html_print_input_hidden ('delete_report', true, true);
		$data[3] .= html_print_input_image ('delete', 'images/cross.png', 1, '',
							true, array ('title' => __('Delete')));
		$data[3] .= '</form>';
	}

	array_push ($table->data, $data);
}

html_print_table ($table);

echo '<form method="post" action="' . $config['homeurl'] . 'index.php?sec=netf&sec2=' . $config['homedir'] . '/godmode/netflow/nf_report_form">';
		echo '<div class="action-buttons" style="width: '.$table->width.'">';
		html_print_submit_button (__('Create report'), 'crt', false, 'class="sub wand"');
		echo "</div>";
		echo "</form>";

?>
