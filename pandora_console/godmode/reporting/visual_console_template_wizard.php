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
$vconsoles_read = check_acl ($config['id_user'], 0, "VR");
$vconsoles_write = check_acl ($config['id_user'], 0, "VW");
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
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_template">' .
                html_print_image ("images/templates.png", true, array ("title" => __('Visual Console Template'))) .'</a>'
);

$buttons['visual_console_template_wizard'] = array(
    'active' => true,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_template_wizard">' .
                html_print_image ("images/wand.png", true, array ("title" => __('Visual Console Template Wizard'))) .'</a>'
);

if (!defined('METACONSOLE')) {
	ui_print_page_header(
		__('Reporting') .' &raquo; ' . __('Visual Console'),
		"images/op_reporting.png",
		false,
		"map_builder",
		false,
		$buttons
	);
}


$templates = reporting_enterprise_get_template_reports(array ('order' => 'id_group, name'), array('id_report', 'name'), true);

$template_select = array();
if ($templates === false)
    $template_select = array();
else {
    $groups = array(__('All') => 0);
    foreach ($templates as $template) {
        $id_group = $template['id_group'];
        $group_name = '';

        if (!isset($groups[$id_group]))
            $groups[$id_group] = groups_get_name($id_group, true);

        if (!empty($groups[$id_group]))
            $group_name = $groups[$id_group];

        $template_select[$template['id_report']] = array('optgroup' => $group_name, 'name' => $template['name']);
    }
}

if (is_metaconsole()) {
    $keys_field = 'nombre';
}
else {
    $keys_field = 'id_grupo';
}

$attr_available = array('id' => 'image-select_all_available', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
$attr_apply     = array('id' => 'image-select_all_apply', 'title' => __('Select all'), 'style' => 'cursor: pointer;');

$table = '<form method="post" action="" enctype="multipart/form-data">';
$table .= "<table border=0 cellpadding=4 cellspacing=4 class='databox filters' width=100%>";
	$table .= "<tr>";
		$table .= "<td align='left'>";
		    $table .= "<b>" . __('Templates') . ":</b>";
		$table .= "</td>";
		$table .= "<td align='left'>";
		    $table .= html_print_select($template_select, 'templates', $template_selected, '', __('None'), '0', true, false, true, '', false, 'width:180px;');
        $table .=  "</td>";
        $table .= "<td align='left'>";
		    $table .= "<b>" . __('Report name') . " " .
                ui_print_help_tip(__('Left in blank if you want to use default name: Template name - agents (num agents) - Date'), true) . ":</b>";
		$table .= "</td>";
		$table .= "<td align='left'>";
		    $table .= html_print_input_text ('template_report_name', '', '', 80, 150, true);
        $table .= "</td>";
    $table .= "</tr>";

    $table .= "<tr>";
        $table .= "<td align='left'>";
            $table .= '<b>' . __('Filter group') . ':</b>';
        $table .= "</td>";
        $table .= "<td align='left'>";
            $table .= html_print_select_groups(
                false, "RR", users_can_manage_group_all("RR"),
                'group', '', '', '', 0, true, false, false,
                '', false, false, false, false, $keys_field
            );
        $table .= "</td>";
        $table .= "<td align='left'>";
            $table .= "<b>" . __('Target group') . ":</b>";
        $table .= "</td>";
        $table .= "<td align='left'>";
            $table .= html_print_select_groups(
                false, "RR", users_can_manage_group_all("RR"),
                'template_report_group', '', '', '', 0, true,
                false, false, '', false, false, false, false,
                $keys_field
            );
        $table .= "</td>";
	$table .= "</tr>";

    $table .= "<tr>";
		$table .= "<td align='left'>";
		$table .= '<b>' . __('Filter agent') . ':</b>';
		$table .= "</td>";
        $table .= "<td align='left'>";
        $table .= html_print_input_text ('agent_filter', $agent_filter, '', 20, 150, true);
        $table .= "</td>";
        $table .= "<td align='left'>";
        $table .= '';
        $table .= "</td>";
        $table .= "<td align='left'>";
        $table .= '';
        $table .= "</td>";
    $table .=  "</tr>";
    $table .= "<tr>";
        $table .= "<td align='left' colspan=2>";
        $table .= "<b>" . __('Agents available')."</b>&nbsp;&nbsp;" .
                html_print_image ('images/tick.png', true, $attr_available, false, true);
        $table .= "</td>";
        $table .= "<td align='left' colspan=2>";
        $table .= "<b>" . __('Agents to apply')."</b>&nbsp;&nbsp;" .
                html_print_image ('images/tick.png', true, $attr_apply, false, true);
        $table .= "</td>";
    $table .=  "</tr>";

    $table .= "<tr>";
        $table .= "<td align='left' colspan=2>";
		    $option_style = array();
            $template_agents_in = array();
            $template_agents_all = array();
            $template_agents_out = array();
            $template_agents_out = array_diff_key($template_agents_all, $template_agents_in);
            $template_agents_in_keys = array_keys($template_agents_in);
            $template_agents_out_keys = array_keys($template_agents_out);

            $table .= html_print_select ($template_agents_out, 'id_agents[]', 0, false, '', '', true, true, true, '', false, 'width: 100%;', $option_style);
		$table .= "</td>";
        $table .= "<td align='left'>";
            $table .= html_print_image ('images/darrowright.png', true, array ('id' => 'right', 'title' => __('Add agents to template')));
            $table .= html_print_image ('images/darrowleft.png', true, array ('id' => 'left', 'title' => __('Undo agents to template')));
        $table .= "</td>";
        $table .= "<td align='left'>";
        $table .= $option_style = array();
        //Agents applied to the template
        $table .= html_print_select ($template_agents_in, 'id_agents2[]', 0, false, '', '', true, true, true, '', false, 'width: 100%;', $option_style);
        $table .= "</td>";
    $table .=  "</tr>";
$table .=  "</table>";

if (check_acl ($config['id_user'], 0, "RW")) {
	$table .= '<div class="action-buttons" style="width: 100%;">';
	$table .= html_print_input_hidden('action', 'create_template');
	$table .= html_print_submit_button (__('Apply template'), 'apply', false, 'class="sub next"', true);
	$table .= '</div>';
}
$table .=  '</form>';

echo $table;

?>