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

require_once ($config['homedir'] . '/include/functions_visual_map.php');

// ACL for the general permission
$vconsoles_read   = check_acl ($config['id_user'], 0, "VR");
$vconsoles_write  = check_acl ($config['id_user'], 0, "VW");
$vconsoles_manage = check_acl ($config['id_user'], 0, "VM");

if (!$vconsoles_read && !$vconsoles_write && !$vconsoles_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access map builder");
	require ("general/noaccess.php");
	exit;
}

$pure = (int)get_parameter('pure', 0);
$hack_metaconsole = '';
if (defined('METACONSOLE'))
	$hack_metaconsole = '../../';

$buttons['visual_console'] = array(
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/map_builder">' .
                html_print_image ("images/visual_console.png", true, array ("title" => __('Visual Console List'))) .'</a>'
);

$buttons['visual_console_favorite'] = array(
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_favorite">' .
                html_print_image ("images/list.png", true, array ("title" => __('Visual Favourite Console'))) .'</a>'
);

$buttons['visual_console_template'] = array(
    'active' => true,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_template">' .
                html_print_image ("images/templates.png", true, array ("title" => __('Visual Console Template'))) .'</a>'
);

$buttons['visual_console_template_wizard'] = array(
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_template_wizard">' .
                html_print_image ("images/wand.png", true, array ("title" => __('Visual Console Template Wizard'))) .'</a>'
);

if (!defined('METACONSOLE')) {
	ui_print_page_header(
		__('Visual Console') .' &raquo; ' . __('Template'),
		"images/op_reporting.png",
		false,
		"map_builder",
		false,
		$buttons
	);
}

$id_layout     = (int) get_parameter ('id_layout', 0);
$name_template = (string) get_parameter ('name_template', '');
$group         = (int) get_parameter ('group', 0);
$action        = (string) get_parameter ('action', '');

if($action == "create_template"){
    if(!$id_layout){
        ui_print_error_message(__('visual console has not been selected'));
    }

    $result = visual_map_create_template($id_layout, $name_template, $group);

    if(!$result){
        ui_print_error_message(__('Error. Error created template'));
    }
    else{
        ui_print_success_message(__('Successfully created template'));
    }
}

if($action == "delete_template"){
    if(!$id_layout){
        ui_print_error_message(__('visual console has not been selected'));
    }

    $result = visual_map_delete_template($id_layout);

    if(!$result){
        ui_print_error_message(__('Error. Error delete template'));
    }
    else{
        ui_print_success_message(__('Successfully delete template'));
    }
}

$visual_console_array = visual_map_get_user_layouts($config['id_user'], true);

if (!check_acl ($config['id_user'], 0, "VR")){
	$return_all_group = false;
}
else{
    $return_all_group = true;
}

$table = '<form method="post" action="" enctype="multipart/form-data" style="margin-bottom: 20px;">';
$table .= "<table border=0 cellpadding=4 cellspacing=4 class='databox filters' width=100%>";
	$table .= "<tr>";
		$table .= "<td align='left'>";
		$table .= "<b>" . __("Create From") . ":</b>";
		$table .= "</td>";
		$table .= "<td align='left'>";
		$table .= html_print_select($visual_console_array, 'id_layout', $id_layout, '', __('none'), 0, true);
		$table .=  "</td>";
	$table .= "</tr>";
	$table .= "<tr>";
		$table .= "<td align='left'>";
		$table .= "<b>" . __("Name") . ":</b>";
		$table .= "</td>";
		$table .= "<td align='left'>";
		$table .= html_print_input_text ('name_template', $name_template, '', 50, 255, true);
		$table .= "</td>";
	$table .= "</tr>";
	$table .= "<tr>";
		$table .= "<td align='left'>";
		$table .= '<b>' . __("Group") . ':</b>';
		$table .= "</td>";
        $table .= "<td align='left'>";
        $table .= html_print_select_groups(false, "AR", $return_all_group, "group", $group, 'this.form.submit();', '', 0, true, false, true, '', false);
		$table .= "</td>";
	$table .=  "</tr>";
$table .=  "</table>";

if (check_acl ($config['id_user'], 0, "RW")) {
	$table .= '<div class="action-buttons" style="width: 100%;">';
	$table .= html_print_input_hidden('action', 'create_template', true);
	$table .= html_print_submit_button (__('Create template'), 'apply', false, 'class="sub next"', true);
	$table .= '</div>';
}
$table .=  '</form>';

echo ui_toggle($table, __('Create New Template'), '', false, true);

$array_template_visual_console = visual_map_get_user_layout_templates($config['id_user']);

if($array_template_visual_console && is_array($array_template_visual_console)){
    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'databox data';
    $table->data = array ();
    $table->head = array ();
    $table->head[0] = __('Name');
    $table->head[1] = __('Group');
    $table->head[2] = __('Fovourite');
    $table->head[3] = __('Delete');
    $table->size[3] = "6%";

    $table->align = array ();
    $table->align[0] = 'left';
    $table->align[1] = 'left';
    $table->align[2] = 'left';
    $table->align[3] = 'left';

    foreach ($array_template_visual_console as $key => $value) {
        $data = array ();
        $data[0] = $value['name'];
        $data[1] = $value['id_group'];
        $data[2] = $value['is_favourite'];
        $data[3] = '<a class="delete_visualmap" href="index.php?sec=network&sec2=godmode/reporting/visual_console_template&action=delete_template&id_layout='.$value['id'].'">'.html_print_image ("images/cross.png", true).'</a>';
        array_push ($table->data, $data);
    }

    html_print_table ($table);
}
else{
    ui_print_info_message(__('No data to show'));
}


?>