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

include_once("include/functions_ui.php");
include_once("include/functions_netflow.php");
include_once ("include/functions_users.php");
include_once ("include/functions_groups.php");

check_login ();

if (! check_acl ($config["id_user"], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$id = (int) get_parameter ('id');
$name = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $id);
$update = (string)get_parameter('update', 0);
$create = (string)get_parameter('create', 0);

if ($id){
	$permission = netflow_check_filter_group ($id);
	if (!$permission) { //no tiene permisos para acceder a un filtro
		require ("general/noaccess.php");
		return;
	}
}
	
$buttons['edit'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_edit">'
		. html_print_image ("images/edit.png", true, array ("title" => __('Filter list')))
		. '</a>';
		
$buttons['add'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_edit_form">'
		. html_print_image ("images/add.png", true, array ("title" => __('Add filter')))
		. '</a>';
		
//Header
ui_print_page_header (__('Netflow Filter'), "images/networkmap/so_cisco_new.png", false, "", true, $buttons);

if ($id) {
	$filter = netflow_filter_get_filter ($id);
	$assign_group = $filter['id_group'];
	$name = $filter['id_name'];
	$ip_dst = $filter['ip_dst'];
	$ip_src = $filter['ip_src'];
	$dst_port = $filter['dst_port'];
	$src_port = $filter['src_port'];
	$aggregate = $filter['aggregate'];
	$output = $filter['output'];

} else {
	$name = '';
	$assign_group = '';
	$ip_dst = '';
	$ip_src = '';
	$dst_port = '';
	$src_port = '';
	$aggregate = 'none';
	$output = 'bytes';	
}

if ($update) {
	$name = (string) get_parameter ('name');
	$assign_group = (int) get_parameter ('assign_group');
	$aggregate = get_parameter('aggregate','');
	$output = get_parameter('output','bytes');
	$ip_dst = get_parameter('ip_dst','');
	$ip_src = get_parameter('ip_src','');
	$dst_port = get_parameter('dst_port','');
	$src_port = get_parameter('src_port','');
	
	if ($name == '') {
                ui_print_error_message (__('Not updated. Blank name'));
        } else {
		$result = db_process_sql_update ('tnetflow_filter',
			array ('id_sg' => $id,
				'id_name' => $name,
				'id_group' => $assign_group,
				'aggregate' => $aggregate,
				'ip_dst' => $ip_dst,
				'ip_src' => $ip_src,
				'dst_port' => $dst_port,
				'src_port' => $src_port,
				'output' => $output),
			array ('id_sg' => $id));
			
		ui_print_result_message ($result,
			__('Successfully updated'),
			__('Not updated. Error updating data'));
	}
}

if ($create){
	$name = (string) get_parameter ('name');
	$assign_group = (int) get_parameter ('assign_group');
	$aggregate = get_parameter('aggregate','none');
	$output = get_parameter('output','bytes');
	$ip_dst = get_parameter('ip_dst','');
	$ip_src = get_parameter('ip_src','');
	$dst_port = get_parameter('dst_port','');
	$src_port = get_parameter('src_port','');

		if($name == db_get_value('id_name', 'tnetflow_filter', 'id_name', $name)){	
			$result = false;
		} else {
			$values = array (
				'id_name'=>$name,
				'id_group' => $assign_group,
				'ip_dst'=>$ip_dst,
				'ip_src'=>$ip_src,
				'dst_port'=>$dst_port,
				'src_port'=>$src_port,
				'aggregate'=>$aggregate,
				'output'=>$output
			);
			$result = db_process_sql_insert('tnetflow_filter', $values);
		}
		if ($result === false)
				echo '<h3 class="error">'.__ ('Error creating filter').'</h3>';
			else 
				echo '<h3 class="suc">'.__ ('filter created successfully').'</h3>';
}

$table->width = '80%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = "databox_color";
$table->style[0] = 'vertical-align: top;';

$table->data = array ();
	
$table->data[0][0] = '<b>'.__('Name').'</b>';
$table->data[0][1] = html_print_input_text ('name', $name, false, 20, 80, true);

$own_info = get_user_info ($config['id_user']);
$table->data[1][0] = '<b>'.__('Group').'</b>';
$table->data[1][1] = html_print_select_groups($config['id_user'], "IW",
		$own_info['is_admin'], 'assign_group', $assign_group, '', '', -1, true,
		false, false);
	
$table->data[2][0] = '<b>'.__('Filter:').'</b>';
	
$table->data[3][0] = __('Dst Ip'). ui_print_help_tip (__("Destination IP. A comma separated list of destination ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249"), true);
$table->data[3][1] = html_print_input_text ('ip_dst', $ip_dst, false, 40, 80, true);
	
$table->data[4][0] = __('Src Ip'). ui_print_help_tip (__("Source IP. A comma separated list of source ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249"), true);
$table->data[4][1] = html_print_input_text ('ip_src', $ip_src, false, 40, 80, true);
	
$table->data[5][0] = __('Dst Port'). ui_print_help_tip (__("Destination port. A comma separated list of destination ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22"), true);
$table->data[5][1] = html_print_input_text ('dst_port', $dst_port, false, 40, 80, true);

$table->data[6][0] = __('Src Port'). ui_print_help_tip (__("Source port. A comma separated list of source ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22"), true);
$table->data[6][1] = html_print_input_text ('src_port', $src_port, false, 40, 80, true);

	
$table->data[7][0] = '<b>'.__('Aggregate by').'</b>'. ui_print_help_icon ('aggregate_by', true);
$aggregate_list = array();
$aggregate_list = array ('none' => __('None'), 'proto' => __('Protocol'), 'srcip' =>__('Src Ip Address'), 'dstip' =>__('Dst Ip Address'), 'srcport' =>__('Src Port'), 'dstport' =>__('Dst Port') );

$table->data[7][1] = html_print_select ($aggregate_list, "aggregate", $aggregate, '', '', 0, true, false, true, '', false);
	
$table->data[8][0] = '<b>'.__('Output format').'</b>';
$show_output = array();
$show_output = array ('packets' => __('Packets'), 'bytes' => __('Bytes'), 'bps' =>__('Bits per second'), 'bpp' =>__('Bytes per packet'));
$table->data[8][1] = html_print_select ($show_output, 'output', $output, '', '', 0, true, false, true, '', false);

echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_edit_form">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	html_print_input_hidden ('update', 1);
	html_print_input_hidden ('id', $id);
	html_print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
} else {
	html_print_input_hidden ('create', 1);
	html_print_submit_button (__('Create'), 'crt', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';
?>


